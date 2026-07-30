<?php
/* controlling google Management API related calls */
if (!defined('ABSPATH')) {
  die;
}
/*
 * Declaring Class
 */
class GA4WP_Google_Analytics_Data_API
{
  /* initiating variables */
  protected $request_uri;
  protected $request_headers = array();
  protected $response_code;
  protected $response_message;
  protected $response_headers;
  protected $raw_response_body;
  protected $response;
  protected $property_id;
  protected $access_token = '';
  public    $last_error = '';

  /**
   * Sets up the GA4 Data API client for the configured property.
   *
   * Resolves the current property ID, builds the `:batchRunReports` request
   * URI, and prepares the Authorization/Content-Type request headers.
   *
   * @param string $access_token OAuth access token used to authorize API requests.
   */
  public function __construct($access_token)
  {
    $this->property_id   = $this->get_property_id();
    $this->access_token  = is_string($access_token) ? $access_token : '';
    $this->request_uri = 'https://analyticsdata.googleapis.com/v1beta/properties/' . $this->property_id . ':batchRunReports';
    $this->request_headers['authorization'] = sprintf('Bearer %s', $this->access_token);
    $this->request_headers['Content-Type'] = 'application/json';
  }

  /**
   * Extracts the numeric GA4 property ID from the `ga4wp_auth_settings` option.
   *
   * The stored `property_id` value is expected in the form
   * `properties/{id}|...`; this parses out the `{id}` segment.
   *
   * @return string|int The numeric property ID, or 0 if not configured/parseable.
   */
  public function get_property_id()
  {
    if (get_option('ga4wp_auth_settings')) {
      $auth_settings = get_option('ga4wp_auth_settings');
      if (isset($auth_settings['property_id'])) {
        $property = $auth_settings['property_id'];
        $pieces = explode('|', $property);
        $property_id_text = $pieces[0];
        $text_pieces = explode('/', $property_id_text);
        if (isset($text_pieces[1]) && !empty($text_pieces[1])) {
          return $text_pieces[1];
        } else {
          return 0;
        }
      } else {
        return 0;
      }
    } else {
      return 0;
    }
  }

  /**
   * Sends a `:batchRunReports` request body to the GA4 Data API and parses the
   * response into a per-request array of dimension/metric data.
   *
   * Retries the HTTP request once on failure. On a WP_Error/API error, logs
   * failure state to the `ga4wp_refresh_token_fail` option (used elsewhere to
   * trigger a token refresh/re-auth flow on repeated unauthorized/forbidden
   * responses) and returns false. On success, walks `$response->reports[]` and,
   * for each report's rows, buckets values by report index using the
   * dimension value(s) as key: named `gawp_date_range_N` ranges are summed
   * into KPI stat totals (keyed off the tab's `ga4wp_dash_stats_data_ga4_*`
   * settings array), `current_period`/`previous_period` named ranges are
   * split into 'current'/'previous' keys (joining multiple non-daterange
   * dimensions with `|||`), and legacy/dimensionless rows are stored more directly.
   *
   * @param string $body   JSON-encoded `:batchRunReports` request body.
   * @param string $tab_id Dashboard tab identifier, used to resolve the
   *                       matching `ga4wp_dash_stats_data_ga4_*` settings array.
   * @return array|false Parsed per-request data array, or false on request/API failure.
   */
  public function get_require_stat_data($body, $tab_id)
  {
    // No point round-tripping to Google with a request that's guaranteed to
    // come back 401 — this happens whenever the access token couldn't be
    // refreshed for THIS request (see GA4WP_Auth::get_valid_token()), which
    // isn't necessarily a dead connection; refresh is retried on the next
    // request. Skip the network call and fail fast with a clear reason.
    if (empty($this->access_token)) {
      $this->last_error = 'No valid access token available. Your connection may still be refreshing — please try again in a moment.';
      return false;
    }
    $this->reset_response();
    $i = 0;
    while (1) {
      $response = wp_remote_post($this->request_uri, $this->get_request_args($body));
      if (is_wp_error($response)) {
        break;
      }
      if (!empty($response) && is_array($response)) {
        if (isset($response['response']['code']) && ((int) $response['response']['code'] < 300)) {
          break;
        }
      }
      if ($i > 0) {
        break;
      }
      $i++;
    }
    try {
      $response = $this->handle_response($response);
    } catch (Exception $e) {
      $error_message = $e->getMessage();
      $this->last_error = $error_message;
      if (stripos($error_message, 'unauthorized') !== false) {
        $process_status = get_option('ga4wp_refresh_token_fail');
        if (empty($process_status)) {
          update_option('ga4wp_refresh_token_fail', 'retry');
        } else {
          update_option('ga4wp_refresh_token_fail', 'dead');
          delete_option('ga4wp_granted_scopes');
        }
      }
      if (stripos($error_message, 'forbidden') !== false) {
        $process_status = get_option('ga4wp_refresh_token_fail');
        if (empty($process_status)) {
          update_option('ga4wp_refresh_token_fail', 'retry');
        } else {
          update_option('ga4wp_refresh_token_fail', 'dead');
          delete_option('ga4wp_granted_scopes');
        }
      }
      $response = false;
    }
    $i = 0;
    if (isset($response->reports) && !empty($response->reports)) {
      foreach ($response->reports as $report) {
        if (isset($report->rows) && !empty($report->rows)) {
          foreach ($report->rows as $object) {
            if (isset($object->dimensionValues[0]) && !empty($object->dimensionValues[0])) {
              $date_range_count = count($object->dimensionValues) - 1;
              if ($date_range_count > 0) {
                $last_dim_val = $object->dimensionValues[$date_range_count]->value ?? '';
                if (strpos($last_dim_val, 'gawp_date_range_') !== false) {
                  $f_value = strpos($last_dim_val, 'gawp_date_range_0') !== false ? 0 : 1;
                  if ($tab_id === 'googleAds') {
                    // The googleAds stats request carries a sessionCampaignName dimension
                    // (required by GA4 for advertiserAd* metrics), so each date range comes
                    // back as one row per campaign — sum them into the KPI tile totals.
                    $array_name = 'ga4wp_dash_stats_data_ga4_' . $tab_id;
                    $stats_array_ga4 = array_keys(GA4WP_Settings::get_instance()->$array_name);
                    $j = 0;
                    foreach ($object->metricValues as $key) {
                      foreach ($key as $key2 => $value) {
                        $data[$i][$stats_array_ga4[$j]][$f_value] = ($data[$i][$stats_array_ga4[$j]][$f_value] ?? 0) + round((float) $value, 2);
                      }
                      $j++;
                    }
                  } else {
                    // Legacy named ranges (stats / purchaseJourney) — store raw metric objects
                    $data[$i][$object->dimensionValues[0]->value][$f_value] = $object->metricValues;
                  }
                } else {
                  // Named comparison ranges (current_period / previous_period)
                  // When multiple non-daterange dims exist, join them with ||| as a composite key.
                  if ($date_range_count === 1) {
                    $dimension_name = $object->dimensionValues[0]->value ?? '';
                  } else {
                    $dim_parts = [];
                    for ($d = 0; $d < $date_range_count; $d++) {
                      $dim_parts[] = $object->dimensionValues[$d]->value ?? '';
                    }
                    $dimension_name = implode('|||', $dim_parts);
                  }
                  $date_range      = $last_dim_val;
                  $all_metric_vals = array_map(fn($mv) => (float) ($mv->value ?? 0), $object->metricValues);
                  // Scalar for single-metric, array for multi-metric
                  $metric_value    = count($all_metric_vals) === 1 ? $all_metric_vals[0] : $all_metric_vals;

                  if ($date_range === 'current_period') {
                    $data[$i][$dimension_name]['current'] = $metric_value;
                  } elseif ($date_range === 'previous_period') {
                    $data[$i][$dimension_name]['previous'] = $metric_value;
                  }
                }
              } else {
                $test = 'gawp_date_range_';
                if (strpos($object->dimensionValues[0]->value, $test) !== false) {
                  $k = (int) filter_var($object->dimensionValues[0]->value, FILTER_SANITIZE_NUMBER_INT);
                  $array_name = 'ga4wp_dash_stats_data_ga4_' . $tab_id;
                  $stats_array_ga4 = array_keys(GA4WP_Settings::get_instance()->$array_name);
                  $j = 0;
                  foreach ($object->metricValues as $key) {
                    foreach ($key as $key2 => $value) {
                      $data[$i][$stats_array_ga4[$j]][$k] = round($value, 2);
                    }
                    $j++;
                  }
                  /* } else {
                $data[$i][$object->dimensionValues[0]->value] = $object->metricValues;
              } */
                  /* new else condition */
                } elseif (!isset($object->dimensionValues[1]->value)) {
                  $data[$i][$object->dimensionValues[0]->value] = $object->metricValues;
                } else {
                  $dimension_name  = $object->dimensionValues[0]->value ?? '';
                  $date_range      = $object->dimensionValues[1]->value ?? '';
                  $all_metric_vals = array_map(fn($mv) => (float) ($mv->value ?? 0), $object->metricValues);
                  // Single metric → store scalar; multiple metrics → store array
                  $metric_value = count($all_metric_vals) === 1 ? $all_metric_vals[0] : $all_metric_vals;

                  if ($date_range === 'current_period') {
                    $data[$i][$dimension_name]['current'] = $metric_value;
                  }

                  if ($date_range === 'previous_period') {
                    $data[$i][$dimension_name]['previous'] = $metric_value;
                  }
                }
              }
            } else {
              $array_name = 'ga4wp_dash_stats_data_ga4_' . $tab_id;
              $stats_array_ga4 = array_keys(GA4WP_Settings::get_instance()->$array_name);
              $j = 0;
              foreach ($object->metricValues as $key => $key2) {
                foreach ($key2 as $name => $value) {
                  $data[$i][$stats_array_ga4[$j]] = round($value, 2);
                }
                $j++;
              }
            }
          }
        } else {
          $data[$i] = false;
        }
        $i++;
      }
    } else {
      $data = false;
    }
    return $data;
  }

  /**
   * Builds a raw JSON `"metrics":[...]` fragment from a tab's KPI stat keys.
   *
   * Reads the metric names from GA4WP_Settings::$ga4wp_dash_stats_data_ga4_{$tab_id}.
   *
   * @param string $tab_id Dashboard tab identifier.
   * @return string A `"metrics":[{"name":"..."},...]` JSON fragment (metrics array may be empty).
   */
  public function get_metrics_data($tab_id)
  {
    $array_name = 'ga4wp_dash_stats_data_ga4_' . $tab_id;
    $metrics_loop_array = GA4WP_Settings::get_instance()->$array_name;
    $items = [];
    if (is_array($metrics_loop_array)) {
      foreach ($metrics_loop_array as $stat_name => $stat) {
        $items[] = '{"name":"' . $stat_name . '"}';
      }
    }
    return '"metrics":[' . implode(',', $items) . ']';
  }

  /**
   * Checks whether the given dashboard tab has any KPI stat metrics configured.
   *
   * @param string $tab_id Dashboard tab identifier.
   * @return bool True if GA4WP_Settings::$ga4wp_dash_stats_data_ga4_{$tab_id} is a non-empty array.
   */
  public function has_metrics_data($tab_id)
  {
    $array_name = 'ga4wp_dash_stats_data_ga4_' . $tab_id;
    $arr = GA4WP_Settings::get_instance()->$array_name;
    return is_array($arr) && !empty($arr);
  }

  /**
   * Builds a raw JSON `"metrics":[...]` fragment from an arbitrary list of metric names.
   *
   * @param array|string $metrics_array One or more GA4 metric names.
   * @return string A `"metrics":[{"name":"..."},...]` JSON fragment.
   */
  public function get_array_metrics_data($metrics_array)
  {
    $items = [];
    foreach ((array) $metrics_array as $stat_name) {
      $items[] = '{"name":"' . $stat_name . '"}';
    }
    return '"metrics":[' . implode(',', $items) . ']';
  }

  /**
   * Builds and runs the full batchRunReports request for one dashboard tab,
   * then reshapes the parsed response into the per-report-type data GA4WP's
   * dashboard chart/table widgets expect.
   *
   * Reads the tab's report definitions from
   * GA4WP_Settings::$ga4wp_report_request_ga4_{$tab_id} and, per report name,
   * builds one GA4 report request: KPI 'stats' (with a comparison date range,
   * plus a required sessionCampaignName dimension for Google Ads), the
   * WooCommerce 'purchaseJourney'/'overallProductPerformance' funnel/ranking
   * reports, the generic 'dateViseVisitors' time series, the multi-slot
   * 'videoPerformance' and 'overallFormConversion' reports (separate
   * current/previous and start/progress/complete or submit/view requests),
   * the Search Console and UTM Campaign reports, the Click Tracking reports
   * (filtered to a single event/parameter value), and a generic
   * dimension-based report as the fallback via build_dimension_report_request().
   * All requests are batched into a single `:batchRunReports` call via
   * get_require_stat_data(). After parsing, applies tab-specific
   * post-processing: rescaling 0-1 fraction metrics to 0-100 percentages for
   * 'search_console' and 'utm_campaign', and merging the multi-slot raw
   * results into one row per form (for 'form_tracking') or per video title
   * (for 'video_tracking').
   *
   * @param string $start_date Report start date (Y-m-d or a GA4 relative date string).
   * @param string $end_date   Report end date (Y-m-d or a GA4 relative date string).
   * @param string $tab_id     Dashboard tab identifier whose report definitions to run.
   * @return array|false Parsed dashboard data keyed by report name (shape
   *                     varies per report type), or false on API failure.
   */
  public function get_dashboard_data($start_date, $end_date, $tab_id)
  {
    $array_name = 'ga4wp_report_request_ga4_' . $tab_id;
    $request_loop_array = GA4WP_Settings::get_instance()->$array_name;

    $stats_request_json = null;
    $chart_requests     = [];
    $has_stats          = false;
    // Google Ads / Adsense dimensions are incompatible with comparison date ranges.
    //$use_comparison     = !in_array($tab_id, ['googleAds']);

    foreach ($request_loop_array as $report_name => $report_parameters) {
      if ($report_name == 'stats') {
        if (!$this->has_metrics_data($tab_id)) {
          continue;
        }
        $has_stats = true;
        $metrics   = $this->get_metrics_data($tab_id);
        $cmp       = $this->get_compare_date_range($start_date, $end_date);
        // Google Ads metrics (advertiserAdCost/Clicks/Impressions) are only compatible with
        // a Google Ads dimension — GA4 rejects a dimensionless request with a 400 error.
        $stats_dims = ($tab_id === 'googleAds') ? ',"dimensions":[{"name":"sessionCampaignName"}]' : '';
        // Stats always need two named date ranges so the parser stores [0=>cur, 1=>prev] arrays.
        // $use_comparison only governs chart requests; stats comparison is safe for all tabs.
        $stats_request_json = '{"dateRanges":[{"startDate":"' . $start_date . '","endDate":"' . $end_date . '","name":"gawp_date_range_0"},{"startDate":"' . $cmp['start'] . '","endDate":"' . $cmp['end'] . '","name":"gawp_date_range_1"}],' . $metrics . $stats_dims . ',"keepEmptyRows":true}';
      } elseif ($report_name == 'purchaseJourney') {
        $cmp     = $this->get_compare_date_range($start_date, $end_date);
        $metrics = $this->get_array_metrics_data($report_parameters[0]);
        $chart_requests[] = '{"dateRanges":[{"startDate":"' . $start_date . '","endDate":"' . $end_date . '","name":"current_period"},{"startDate":"' . $cmp['start'] . '","endDate":"' . $cmp['end'] . '","name":"previous_period"}],' . $metrics . ',"dimensions":[{"name":"' . $report_parameters[1] . '"}],"keepEmptyRows":true}';
      } elseif ($report_name == 'overallProductPerformance') {
        $cmp     = $this->get_compare_date_range($start_date, $end_date);
        $metrics = $this->get_array_metrics_data($report_parameters[0]);
        $chart_requests[] = '{"dateRanges":[{"startDate":"' . $start_date . '","endDate":"' . $end_date . '","name":"current_period"},{"startDate":"' . $cmp['start'] . '","endDate":"' . $cmp['end'] . '","name":"previous_period"}],' . $metrics . ',"dimensions":[{"name":"' . $report_parameters[1] . '"}],"orderBys":[{"desc":true,"metric":{"metricName":"' . $report_parameters[2] . '"}}],"keepEmptyRows":true}';
      } elseif ($report_name == 'dateViseVisitors') {
        $metrics = $this->get_array_metrics_data($report_parameters[0]);
        $chart_requests[] = '{"dateRanges":[{"startDate":"' . $start_date . '","endDate":"' . $end_date . '"}],' . $metrics . ',"dimensions":[{"name":"' . $report_parameters[1] . '"}],"orderBys":[{"dimension":{"dimensionName":"date"}}],"keepEmptyRows":true}';
      } elseif ($report_name == 'videoPerformance') {
        $cmp        = $this->get_compare_date_range($start_date, $end_date);
        $both       = [
          ['startDate' => $start_date,   'endDate' => $end_date,       'name' => 'current_period'],
          ['startDate' => $cmp['start'], 'endDate' => $cmp['end'],     'name' => 'previous_period'],
        ];
        $dim    = [['name' => 'videoTitle']];
        $met    = [['name' => 'eventCount']];
        $order  = [['desc' => true, 'metric' => ['metricName' => 'eventCount']]];
        $start_f = ['filter' => ['fieldName' => 'eventName', 'stringFilter' => ['matchType' => 'EXACT', 'value' => 'video_start']]];
        $prog_f  = ['filter' => ['fieldName' => 'eventName', 'stringFilter' => ['matchType' => 'EXACT', 'value' => 'video_progress']]];
        $comp_f  = ['filter' => ['fieldName' => 'eventName', 'stringFilter' => ['matchType' => 'EXACT', 'value' => 'video_complete']]];
        // Slot 0: video_start (current + previous)
        $chart_requests[] = wp_json_encode(['dateRanges' => $both, 'metrics' => $met, 'dimensions' => $dim, 'dimensionFilter' => $start_f, 'orderBys' => $order, 'limit' => 50]);
        // Slot 1: video_progress (current + previous)
        $chart_requests[] = wp_json_encode(['dateRanges' => $both, 'metrics' => $met, 'dimensions' => $dim, 'dimensionFilter' => $prog_f,  'orderBys' => $order, 'limit' => 50]);
        // Slot 2: video_complete (current + previous)
        $chart_requests[] = wp_json_encode(['dateRanges' => $both, 'metrics' => $met, 'dimensions' => $dim, 'dimensionFilter' => $comp_f,  'orderBys' => $order, 'limit' => 50]);
        // Slot 3: videoTitle + videoUrl mapping (video_start, current period only)
        $url_dim          = [['name' => 'videoTitle'], ['name' => 'videoUrl']];
        $chart_requests[] = wp_json_encode(['dateRanges' => $both, 'metrics' => $met, 'dimensions' => $url_dim, 'dimensionFilter' => $start_f, 'orderBys' => $order, 'limit' => 50]);
      } elseif ($report_name == 'overallFormConversion') {
        $cmp        = $this->get_compare_date_range($start_date, $end_date);
        $cur_range  = [['startDate' => $start_date,   'endDate' => $end_date]];
        $prev_range = [['startDate' => $cmp['start'], 'endDate' => $cmp['end']]];
        $dim        = [['name' => 'customEvent:ga4wp_form_name']];
        $met        = [['name' => 'eventCount']];
        $order      = [['desc' => true, 'metric' => ['metricName' => 'eventCount']]];
        $sub_filter = ['filter' => ['fieldName' => 'eventName', 'stringFilter' => ['matchType' => 'EXACT', 'value' => 'form_submit']]];
        $view_filter = ['filter' => ['fieldName' => 'eventName', 'stringFilter' => ['matchType' => 'EXACT', 'value' => 'form_view']]];
        // Slot 0: form_submit current period
        $chart_requests[] = wp_json_encode(['dateRanges' => $cur_range,  'metrics' => $met, 'dimensions' => $dim, 'dimensionFilter' => $sub_filter,  'orderBys' => $order, 'limit' => 30]);
        // Slot 1: form_view current period
        $chart_requests[] = wp_json_encode(['dateRanges' => $cur_range,  'metrics' => $met, 'dimensions' => $dim, 'dimensionFilter' => $view_filter, 'orderBys' => $order, 'limit' => 30]);
        // Slot 2: form_submit previous period
        $chart_requests[] = wp_json_encode(['dateRanges' => $prev_range, 'metrics' => $met, 'dimensions' => $dim, 'dimensionFilter' => $sub_filter,  'orderBys' => $order, 'limit' => 30]);
        // Slot 3: form_view previous period
        $chart_requests[] = wp_json_encode(['dateRanges' => $prev_range, 'metrics' => $met, 'dimensions' => $dim, 'dimensionFilter' => $view_filter, 'orderBys' => $order, 'limit' => 30]);
      } elseif (in_array($report_name, ['queriesReport', 'monthReport', 'deviceReport'], true)) {
        // Search Console reports — array of 4 organic-search metrics against either a
        // single dimension (month/device) or two dimensions (query + landing page).
        $cmp      = $this->get_compare_date_range($start_date, $end_date);
        $both     = [
          ['startDate' => $start_date,   'endDate' => $end_date,   'name' => 'current_period'],
          ['startDate' => $cmp['start'], 'endDate' => $cmp['end'], 'name' => 'previous_period'],
        ];
        $met      = array_map(fn($m) => ['name' => $m], (array) $report_parameters[0]);
        $dim      = array_map(fn($d) => ['name' => $d], (array) $report_parameters[1]);
        // Chronological order for the monthly trend; impressions-desc for queries/device.
        $order    = ($report_name === 'monthReport')
          ? [['dimension' => ['dimensionName' => 'yearMonth']]]
          : [['desc' => true, 'metric' => ['metricName' => 'organicGoogleSearchImpressions']]];
        $chart_requests[] = wp_json_encode(['dateRanges' => $both, 'metrics' => $met, 'dimensions' => $dim, 'orderBys' => $order, 'keepEmptyRows' => true, 'limit' => 50]);
      } elseif (in_array($report_name, ['campaignSourceMedium', 'campaignTerm'], true)) {
        // UTM Campaign reports — 4 metrics against two session-scoped campaign dimensions.
        $cmp      = $this->get_compare_date_range($start_date, $end_date);
        $both     = [
          ['startDate' => $start_date,   'endDate' => $end_date,   'name' => 'current_period'],
          ['startDate' => $cmp['start'], 'endDate' => $cmp['end'], 'name' => 'previous_period'],
        ];
        $met      = array_map(fn($m) => ['name' => $m], (array) $report_parameters[0]);
        $dim      = array_map(fn($d) => ['name' => $d], (array) $report_parameters[1]);
        $order    = [['desc' => true, 'metric' => ['metricName' => 'sessions']]];
        $chart_requests[] = wp_json_encode(['dateRanges' => $both, 'metrics' => $met, 'dimensions' => $dim, 'orderBys' => $order, 'keepEmptyRows' => true, 'limit' => 50]);
      } elseif (in_array($report_name, ['outboundClicksReport', 'fileDownloadsReport'], true)) {
        // Click Tracking reports — GA4 Enhanced Measurement events, filtered to a single
        // event/parameter value (outbound=true, or eventName=file_download).
        $cmp          = $this->get_compare_date_range($start_date, $end_date);
        $both         = [
          ['startDate' => $start_date,   'endDate' => $end_date,   'name' => 'current_period'],
          ['startDate' => $cmp['start'], 'endDate' => $cmp['end'], 'name' => 'previous_period'],
        ];
        $met          = array_map(fn($m) => ['name' => $m], (array) $report_parameters[0]);
        $dim          = array_map(fn($d) => ['name' => $d], (array) $report_parameters[1]);
        $filter_field = $report_parameters[2][0];
        $filter_value = $report_parameters[2][1];
        $dim_filter   = ['filter' => ['fieldName' => $filter_field, 'stringFilter' => ['matchType' => 'EXACT', 'value' => $filter_value]]];
        $order        = [['desc' => true, 'metric' => ['metricName' => 'eventCount']]];
        $chart_requests[] = wp_json_encode(['dateRanges' => $both, 'metrics' => $met, 'dimensions' => $dim, 'dimensionFilter' => $dim_filter, 'orderBys' => $order, 'keepEmptyRows' => true, 'limit' => 50]);
      } else {
        $request = $this->build_dimension_report_request(
          $start_date,
          $end_date,
          $report_parameters[0],
          $report_parameters[1],
          $report_parameters[2],
          30,
        );
        $chart_requests[] = wp_json_encode($request);
      }
    }

    $all_requests = $has_stats && $stats_request_json
      ? array_merge([$stats_request_json], $chart_requests)
      : $chart_requests;

    $report_request = '{"requests":[' . implode(',', $all_requests) . ']}';
    $data = $this->get_require_stat_data($report_request, $tab_id);

    // search_console: CTR (4th metric slot) comes back as a 0-1 fraction from the API;
    // scale it to a 0-100 number to match the "CTR %" column header used by the table.
    if ($tab_id === 'search_console' && is_array($data)) {
      foreach ($data as &$rows) {
        if (!is_array($rows)) continue;
        foreach ($rows as &$row) {
          if (isset($row['current'][3])) $row['current'][3] = round($row['current'][3] * 100, 2);
          if (isset($row['previous'][3])) $row['previous'][3] = round($row['previous'][3] * 100, 2);
        }
        unset($row);
      }
      unset($rows);
    }

    // utm_campaign: engagementRate (2nd metric slot) comes back as a 0-1 fraction from the
    // API; scale it to a 0-100 number to match the "Engagement Rate %" column header.
    if ($tab_id === 'utm_campaign' && is_array($data)) {
      foreach ($data as &$rows) {
        if (!is_array($rows)) continue;
        foreach ($rows as &$row) {
          if (isset($row['current'][1])) $row['current'][1] = round($row['current'][1] * 100, 2);
          if (isset($row['previous'][1])) $row['previous'][1] = round($row['previous'][1] * 100, 2);
        }
        unset($row);
      }
      unset($rows);
    }

    // form_tracking: merge 4 single-range slots into one multi-metric entry per form.
    // Slots: 0=submit_cur, 1=view_cur, 2=submit_prev, 3=view_prev
    if ($tab_id === 'form_tracking' && is_array($data) && count($data) >= 4) {
      // Single date-range reports: parser stores val as metricValues array [{value:"42"}]
      $extract = function ($slot) use ($data) {
        $out = [];
        if (!is_array($data[$slot])) return $out;
        foreach ($data[$slot] as $form => $val) {
          if (is_array($val) && isset($val[0]) && is_object($val[0])) {
            $out[$form] = (float) ($val[0]->value ?? 0);
          } elseif (is_array($val) && isset($val['current'])) {
            $out[$form] = (float) $val['current'];
          } elseif (is_numeric($val)) {
            $out[$form] = (float) $val;
          } else {
            $out[$form] = 0.0;
          }
        }
        return $out;
      };
      $sub_cur_map  = $extract(0);
      $view_cur_map = $extract(1);
      $sub_prev_map = $extract(2);
      $view_prev_map = $extract(3);
      $all_forms = array_unique(array_merge(
        array_keys($sub_cur_map),
        array_keys($view_cur_map),
        array_keys($sub_prev_map),
        array_keys($view_prev_map)
      ));
      $merged = [];
      foreach ($all_forms as $form) {
        if ($form === '' || $form === '(not set)') continue;
        $sub_cur   = $sub_cur_map[$form]   ?? 0.0;
        $view_cur  = $view_cur_map[$form]  ?? 0.0;
        $sub_prev  = $sub_prev_map[$form]  ?? 0.0;
        $view_prev = $view_prev_map[$form] ?? 0.0;
        $rate_cur  = $view_cur  > 0 ? round($sub_cur  / $view_cur  * 100, 1) : 0.0;
        $rate_prev = $view_prev > 0 ? round($sub_prev / $view_prev * 100, 1) : 0.0;
        $merged[$form] = [
          'current'  => [$view_cur, $sub_cur, $rate_cur],
          'previous' => [$view_prev, $sub_prev, $rate_prev],
        ];
      }
      $data = [$merged];
    }

    // video_tracking: merge 3 named-range slots into one multi-metric entry per video title.
    // Slots: 0=video_start, 1=video_progress, 2=video_complete, 3=title|||url mapping.
    if ($tab_id === 'video_tracking' && is_array($data) && count($data) >= 3) {
      $extract = function ($slot, $period) use ($data) {
        $out = [];
        if (!is_array($data[$slot])) return $out;
        foreach ($data[$slot] as $title => $val) {
          $out[$title] = (is_array($val) && isset($val[$period])) ? (float) $val[$period] : 0.0;
        }
        return $out;
      };
      $start_cur  = $extract(0, 'current');
      $prog_cur   = $extract(1, 'current');
      $comp_cur   = $extract(2, 'current');
      $start_prev = $extract(0, 'previous');
      $prog_prev  = $extract(1, 'previous');
      $comp_prev  = $extract(2, 'previous');
      // Build title->url map from slot 3 composite keys ("title|||url")
      $url_map = [];
      if (isset($data[3]) && is_array($data[3])) {
        foreach (array_keys($data[3]) as $composite) {
          $parts = explode('|||', $composite, 2);
          if (count($parts) === 2 && $parts[0] !== '') {
            $url_map[$parts[0]] = $parts[1];
          }
        }
      }
      $all_titles = array_unique(array_merge(
        array_keys($start_cur),
        array_keys($comp_cur),
        array_keys($start_prev),
        array_keys($comp_prev)
      ));
      $merged = [];
      foreach ($all_titles as $title) {
        if ($title === '' || $title === '(not set)') continue;
        $sc = $start_cur[$title]  ?? 0.0;
        $pc = $prog_cur[$title]   ?? 0.0;
        $cc = $comp_cur[$title]   ?? 0.0;
        $sp = $start_prev[$title] ?? 0.0;
        $pp = $prog_prev[$title]  ?? 0.0;
        $cp = $comp_prev[$title]  ?? 0.0;
        $comp_rate_cur  = $sc > 0 ? round($cc / $sc * 100, 1)                                       : 0.0;
        $avg_watch_cur  = $sc > 0 ? min(100.0, round(($cc * 100 + ($pc - 4 * $cc) * 17) / $sc, 1)) : 0.0;
        $comp_rate_prev = $sp > 0 ? round($cp / $sp * 100, 1)                                       : 0.0;
        $avg_watch_prev = $sp > 0 ? min(100.0, round(($cp * 100 + ($pp - 4 * $cp) * 17) / $sp, 1)) : 0.0;
        $merged[$title] = [
          'current'  => [$sc, $cc, $comp_rate_cur,  $avg_watch_cur],
          'previous' => [$sp, $cp, $comp_rate_prev, $avg_watch_prev],
          'url'      => $url_map[$title] ?? '',
        ];
      }
      $data = [$merged];
    }

    return $data;
  }
  /**
   * Computes the previous comparison period immediately preceding the given date range.
   *
   * The comparison period has the same length (in days) as [$start_date, $end_date]
   * and ends the day before $start_date.
   *
   * @param string $start_date Start date of the current period (Y-m-d or strtotime()-parseable).
   * @param string $end_date   End date of the current period (Y-m-d or strtotime()-parseable).
   * @return array{start: string, end: string} The previous period's start/end dates (Y-m-d).
   */
  private function get_compare_date_range($start_date, $end_date)
  {
    $start = strtotime($start_date);
    $end   = strtotime($end_date);
    $days_between = ceil(abs($end - $start) / DAY_IN_SECONDS) + 1;
    return [
      'start' => date('Y-m-d', strtotime("-{$days_between} days", $start)),
      'end'   => date('Y-m-d', strtotime("-{$days_between} days", $end)),
    ];
  }
  /**
   * Builds a single-dimension, single-metric GA4 report request array, sorted
   * descending by an order metric.
   *
   * @param string $start_date      Report start date.
   * @param string $end_date        Report end date.
   * @param string $metric          Metric name to request.
   * @param string $dimension       Dimension name to request.
   * @param string $order_metric    Metric name to sort results by (descending).
   * @param int    $limit           Maximum number of rows to return. Default 10.
   * @param bool   $with_comparison Whether to add a named 'current_period'/'previous_period'
   *                                pair of date ranges (via get_compare_date_range()) instead
   *                                of a single unnamed range. Default true.
   * @return array The report request body, ready to be JSON-encoded.
   */
  private function build_dimension_report_request($start_date, $end_date, $metric, $dimension, $order_metric, $limit = 10, $with_comparison = true)
  {
    if ($with_comparison) {
      $cmp = $this->get_compare_date_range($start_date, $end_date);
      $date_ranges = [
        ['startDate' => $start_date,    'endDate' => $end_date,      'name' => 'current_period'],
        ['startDate' => $cmp['start'],  'endDate' => $cmp['end'],    'name' => 'previous_period'],
      ];
    } else {
      $date_ranges = [
        ['startDate' => $start_date, 'endDate' => $end_date],
      ];
    }

    return [
      'dateRanges'  => $date_ranges,
      'metrics'     => [['name' => $metric]],
      'dimensions'  => [['name' => $dimension]],
      'orderBys'    => [['desc' => true, 'metric' => ['metricName' => $order_metric]]],
      'limit'       => $limit,
    ];
  }
  /**
   * Builds the wp_remote_post() args array used for GA4 Data API requests.
   *
   * @param string $body Raw JSON request body.
   * @return array{method: string, timeout: int, redirection: int, httpversion: string, sslverify: bool, user-agent: string, headers: array, body: string}
   */
  protected function get_request_args($body)
  {

    $args = array(
      'method' => 'POST',
      'timeout' => 45,
      'redirection' => 0,
      'httpversion' => '1.1',
      'sslverify' => true,
      'user-agent' => $this->get_request_user_agent(),
      'headers' => $this->request_headers,
      'body' => $body,
    );
    return $args;
  }

  /**
   * Builds the User-Agent string sent with GA4 Data API requests.
   *
   * Falls back to a `GA4WP/{version} (WordPress/{version})` string unless
   * function_exists() resolves ga4wp_get_user_agent()'s return value as a
   * callable function name.
   *
   * @return string The user-agent string to send with the request.
   */
  protected function get_request_user_agent()
  {
    if (function_exists($this->ga4wp_get_user_agent())) {
      $user_agent = htmlentities($this->ga4wp_get_user_agent(), ENT_QUOTES, 'UTF-8');
    } else {
      $user_agent = sprintf('%s/%s (WordPress/%s)', 'GA4WP', GA4WP_VERSION, $GLOBALS['wp_version']);
    }
    return $user_agent;
  }

  /**
   * Returns the current request's HTTP User-Agent header, lowercased.
   *
   * @return string The lowercased `$_SERVER['HTTP_USER_AGENT']` value, or an empty string if not set.
   */
  public function ga4wp_get_user_agent()
  {
    return isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
  }

  /**
   * Validates an HTTP response from wp_remote_post() and decodes its JSON body.
   *
   * Stores the response code/message/headers/raw body on the instance for
   * later inspection. Throws on a WP_Error or a non-200 response.
   *
   * @param array|\WP_Error $response The wp_remote_post()/wp_remote_get() response.
   * @return object The JSON-decoded response body.
   * @throws \Exception If $response is a WP_Error, or the HTTP status code is not 200.
   */
  protected function handle_response($response)
  {
    if (is_wp_error($response)) {
      throw new Exception($response->get_error_message());
    }
    $this->response_code = wp_remote_retrieve_response_code($response);
    $this->response_message = wp_remote_retrieve_response_message($response);
    $this->raw_response_body = wp_remote_retrieve_body($response);
    $this->response_headers = wp_remote_retrieve_headers($response);
    if ($this->response_code == 200) {
      $this->response = json_decode($this->raw_response_body);
    } else {
      $this->response = '';
      throw new Exception("{$this->response_message} | {$this->raw_response_body}");
    }
    return $this->response;
  }

  /**
   * Clears the cached response state (code, message, raw body, decoded response)
   * before a new request is made.
   */
  protected function reset_response()
  {
    $this->response_code = null;
    $this->response_message = null;
    $this->raw_response_body = null;
    $this->response = null;
  }
}

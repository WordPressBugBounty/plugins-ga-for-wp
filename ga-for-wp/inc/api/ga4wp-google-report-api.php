<?php
/* controlling dashboard related calls */
if (!defined('ABSPATH')) {
  die;
}
/*
 * Declaring Class
 */
class GA4WP_Google_Report_API
{
  /* initiating variables */
  protected $request_uri;
  protected $request_headers = array();
  protected $response_code;
  protected $response_headers;
  protected $response_message;
  protected $raw_response_body;
  protected $response;
  protected $access_token = '';
  public    $last_error = '';

  /**
   * Configure the request URI and auth/content-type headers for the Google Analytics
   * Reporting API v4 batchGet endpoint.
   *
   * @param string $access_token OAuth bearer token used to authenticate requests; treated
   *                             as empty if not a string.
   */
  public function __construct($access_token)
  {
    $this->request_uri  = 'https://analyticsreporting.googleapis.com/v4/reports:batchGet';
    $this->access_token = is_string($access_token) ? $access_token : '';
    $this->request_headers['authorization'] = sprintf('Bearer %s', $this->access_token);
    $this->request_headers['Content-Type'] = 'application/json';
  }

  /**
   * Send a batchGet request to the Reporting API v4 and reshape the response into
   * the per-tab data arrays consumed by the dashboard views.
   *
   * Retries the POST request once if the first attempt errors or returns a
   * non-2xx response. Decodes and validates the response via
   * handle_response(); on an "unauthorized"/"forbidden" API error, tracks a
   * retry/failure state in the 'ga4wp_refresh_token_fail' option (and clears
   * 'ga4wp_granted_scopes' once retries are exhausted) so the auth flow can
   * detect a broken refresh token. For each report row, either keys the
   * value by its first dimension, or — when there are no dimensions — maps
   * metric values onto the stat keys from the matching
   * `ga4wp_dash_stats_data_{$tab_id}` settings array (falling back to the
   * report's `totals` block when there are no rows at all).
   *
   * @param string $body   JSON-encoded batchGet request body.
   * @param string $tab_id Dashboard tab identifier (e.g. 'audience', 'acquisition') used to
   *                       look up the matching stats/metrics definitions on GA4WP_Settings.
   * @return array|false Per-report data arrays, `false` for a report with neither rows nor
   *                     totals, or an overall `false` if the whole request failed or returned no reports.
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
      error_log($e->getMessage(), 0);
      $error_message = $e->getMessage();
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
        if (isset($report->data->rows) && !empty($report->data->rows)) {
          foreach ($report->data->rows as $object) {
            if (isset($object->dimensions[0]) && !empty($object->dimensions[0])) {
              $data[$i][$object->dimensions[0]] = $object->metrics[0]->values[0];
            } else {
              $array_name = 'ga4wp_dash_stats_data_' . $tab_id;
              $stats_array = array_keys(GA4WP_Settings::get_instance()->$array_name);
              $k = 0;
              foreach ($object->metrics as $metric) {
                $j = 0;
                foreach ($metric->values as $value) {
                  $data[$i][$stats_array[$j]][$k] = round($value, 3);
                  $j++;
                }
                $k++;
              }
            }
          }
        } elseif (isset($report->data->totals) && !empty($report->data->totals) && ($i == 0)) {
          $k = 0;
          foreach ($report->data->totals as $object) {
            $array_name = 'ga4wp_dash_stats_data_' . $tab_id;
            $stats_array = array_keys(GA4WP_Settings::get_instance()->$array_name);
            $j = 0;
            foreach ($stats_array as $stat) {
              $data[$i][$stat][$k] = round($object->values[$j], 2);
              $j++;
            }
            $k++;
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
   * Build the "metrics" fragment of a Reporting API v4 request body for a dashboard tab's stats.
   *
   * @param string $tab_id Dashboard tab identifier used to look up the matching
   *                       `ga4wp_dash_stats_data_{$tab_id}` settings array.
   * @return string A raw (not JSON-encoded) `"metrics": [...]` fragment listing one
   *                `ga:<stat_name>` expression per configured stat.
   */
  public function get_metrics_data($tab_id)
  {
    $array_name = 'ga4wp_dash_stats_data_' . $tab_id;
    $metrics = '"metrics": [';
    $metrics_loop_array = GA4WP_Settings::get_instance()->$array_name;
    foreach ($metrics_loop_array as $stat_name => $stat) {
      $metrics .= '{"expression": "ga:' . $stat_name . '"},';
    }
    $metrics .= ']';
    return $metrics;
  }

  /**
   * Build and send the full batchGet request for a dashboard tab's reports, and return the parsed data.
   *
   * Iterates the tab's `ga4wp_report_request_{$tab_id}` definitions, building
   * one reportRequest per entry (each comparing the requested date range
   * against an equal-length prior period), with special-cased handling for
   * the 'stats' entry (uses get_metrics_data() and ascending order isn't
   * applicable) and the 'dateViseVisitors' entry (ascending order, no page
   * size limit) versus all other entries (descending order, capped at 10
   * rows). Delegates the actual request/parsing to get_require_stat_data().
   *
   * @param string $view_id    Google Analytics (Universal Analytics) view ID.
   * @param string $start_date Report range start date (Y-m-d).
   * @param string $end_date   Report range end date (Y-m-d).
   * @param string $tab_id     Dashboard tab identifier used to look up the matching
   *                           `ga4wp_report_request_{$tab_id}` settings array.
   * @return array|false Parsed report data as returned by get_require_stat_data().
   */
  public function get_dashboard_data($view_id, $start_date, $end_date, $tab_id)
  {
    $report_request = '{';
    $array_name = 'ga4wp_report_request_' . $tab_id;
    $request_loop_array = GA4WP_Settings::get_instance()->$array_name;
    foreach ($request_loop_array as $report_name => $report_parameters) {
      $start = strtotime($start_date);
      $end = strtotime($end_date);
      $days_between = ceil(abs($end - $start) / 86400) + 1;
      $cmp_start_date = date_format(date_sub(date_create($start_date), date_interval_create_from_date_string($days_between . ' days')), 'Y-m-d');
      $cmp_end_date = date_format(date_sub(date_create($end_date), date_interval_create_from_date_string($days_between . ' days')), 'Y-m-d');
      if ($report_name == 'stats') {
        $metrics = $this->get_metrics_data($tab_id);
        $report_request .= '"reportRequests":
                  {"viewId":"' . $view_id . '",
                    "dateRanges": [
                      {"startDate": "' . $start_date . '", "endDate": "' . $end_date . '"},
                      {"startDate": "' . $cmp_start_date . '", "endDate": "' . $cmp_end_date . '"}
                    ],
                    ' . $metrics . ',
                      "includeEmptyRows": true,
                  },';
      } elseif ($report_name == 'dateViseVisitors') {
        $report_request .= '"reportRequests":
          {"viewId":"' . $view_id . '",
            "dateRanges": [
              {"startDate": "' . $start_date . '", "endDate": "' . $end_date . '"},
              {"startDate": "' . $cmp_start_date . '", "endDate": "' . $cmp_end_date . '"}
            ],
            "metrics": [
                {"expression": "ga:' . $report_parameters[0] . '"},
            ],
            "dimensions" :[
                {"name": "ga:' . $report_parameters[1] . '"},
            ],
            "orderBys" :[
                {"fieldName":"ga:' . $report_parameters[2] . '",
                "sortOrder": "ASCENDING",
              }
            ],
            "includeEmptyRows": true,
          },';
      } else {
        $report_request .= '"reportRequests":
          {"viewId":"' . $view_id . '",
            "dateRanges": [
              {"startDate": "' . $start_date . '", "endDate": "' . $end_date . '"},
              {"startDate": "' . $cmp_start_date . '", "endDate": "' . $cmp_end_date . '"}
            ],
            "metrics": [
                {"expression": "ga:' . $report_parameters[0] . '"},
            ],
            "dimensions" :[
                {"name": "ga:' . $report_parameters[1] . '"},
            ],
            "orderBys" :[
                {"fieldName":"ga:' . $report_parameters[2] . '",
                 "sortOrder": "DESCENDING",
               }
            ],
            "pageSize" :10,
          },';
      }
    }
    $report_request .= '}';
    $require_data = $this->get_require_stat_data($report_request, $tab_id);
    return $require_data;
  }

  /**
   * Build the argument array passed to wp_remote_post() for an API request.
   *
   * @param string $body Raw request body (JSON string) to send.
   * @return array Arguments for wp_remote_post(), including method, timeout, headers and body.
   */
  protected function get_request_args($body)
  {

    $args = array(
      'method' => 'POST',
      'timeout' => 25,
      'redirection' => 0,
      'httpversion' => '1.0',
      'sslverify' => true,
      'user-agent' => $this->get_request_user_agent(),
      'headers' => $this->request_headers,
      'body' => $body,
    );
    return $args;
  }

  /**
   * Get the user-agent string to send with API requests.
   *
   * Uses the current request's HTTP User-Agent (via ga4wp_get_user_agent())
   * when available, falling back to a "GA4WP/<version> (WordPress/<version>)"
   * string otherwise.
   *
   * @return string The user-agent string for the outgoing request.
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
   * Get the current visitor's HTTP User-Agent string from the server, lowercased.
   *
   * @return string The lowercased $_SERVER['HTTP_USER_AGENT'] value, or '' if not set.
   */
  public function ga4wp_get_user_agent()
  {
    return isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
  }

  /**
   * Validate an HTTP API response and decode its JSON body.
   *
   * Populates $this->response_code, $this->response_message,
   * $this->raw_response_body and $this->response_headers from the response.
   *
   * @param array|WP_Error $response The result of a wp_remote_post()/wp_remote_get() call.
   * @return object The decoded JSON response body.
   * @throws Exception If $response is a WP_Error, or the HTTP status code isn't 200.
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
      throw new Exception($this->response_message);
    }
    return $this->response;
  }

  /**
   * Reset the stored response state (code, message, raw body and decoded response)
   * before making a new API request.
   */
  protected function reset_response()
  {
    $this->response_code = null;
    $this->response_message = null;
    $this->raw_response_body = null;
    $this->response = null;
  }
}

<?php
/* controlling google Management API related calls */
if (!defined('ABSPATH')) {
  die;
}
/*
 * Declaring Class
 */
class GA4WP_Google_Management_API
{
  /* initiating variables */
  protected $request_uri;
  protected $request_headers = array();
  protected $response_code;
  protected $response_headers;
  protected $response_message;
  protected $raw_response_body;
  protected $response;

  /* Human-readable message from the last failed request — e.g. surfaces
   * Google's own error text (including "terms of service" / consent
   * messages) so callers can display it. */
  protected $last_error = '';

  /**
   * Initialize the API client with the base request URI and auth headers.
   *
   * @param string $access_token OAuth access token used to authorize requests
   *                             against the Google Analytics Management/Admin API.
   */
  public function __construct($access_token)
  {
    $this->request_uri = 'https://www.googleapis.com/analytics/v3/management';
    $this->request_headers['authorization'] = sprintf('Bearer %s', is_string($access_token) ? $access_token : '');
    $this->request_headers['Content-Type'] = 'application/json';
  }

  /**
   * Get the human-readable message from the last failed request.
   *
   * @return string The last error message, or an empty string if none.
   */
  public function get_last_error()
  {
    return $this->last_error;
  }

  /* ── Measurement Protocol (available on both free and premium tiers) ──── */

  /**
   * Fetch existing Measurement Protocol secrets for a GA4 data stream.
   *
   * Retries the request up to 3 times if a successful (< 300) HTTP status
   * isn't received on the first attempt.
   *
   * @param string $property_name GA4 property/data-stream resource name,
   *                               e.g. "properties/123/dataStreams/456".
   * @return object|string|null Decoded JSON response object on success, an
   *                             empty string on a handled error, or null if
   *                             the request never completed.
   */
  public function get_measurement_protocall($property_name)
  {
    $this->reset_response();
    $i = 0;
    while (1) {
      $this->response = wp_safe_remote_request(
        "https://analyticsadmin.googleapis.com/v1beta/{$property_name}/measurementProtocolSecrets",
        $this->get_custom_protocall_request_args()
      );
      if (!empty($this->response) && is_array($this->response)) {
        if (isset($this->response['response']['code']) && ((int) $this->response['response']['code'] < 300)) {
          break;
        }
      }
      if ($i > 1) break;
      $i++;
    }
    try {
      $this->response = $this->handle_response($this->response);
    } catch (Exception $e) {
      error_log($e->getMessage(), 0);
    }
    return $this->response;
  }

  /**
   * Create a new Measurement Protocol secret key for a GA4 data stream.
   *
   * Retries the request up to 3 times if a successful (< 300) HTTP status
   * isn't received on the first attempt. On failure, extracts Google's own
   * error message (e.g. consent/terms-of-service rejections) from the raw
   * response body into `$last_error`.
   *
   * @param string $property_name GA4 property/data-stream resource name,
   *                               e.g. "properties/123/dataStreams/456".
   * @return object|null Decoded JSON response object on success, or null on failure.
   */
  public function create_measurement_protocall($property_name)
  {
    $this->reset_response();
    $this->last_error = '';
    $i = 0;
    while (1) {
      $this->response = wp_safe_remote_request(
        "https://analyticsadmin.googleapis.com/v1beta/{$property_name}/measurementProtocolSecrets",
        $this->custom_protocall_request_args()
      );
      if (!empty($this->response) && is_array($this->response)) {
        if (isset($this->response['response']['code']) && ((int) $this->response['response']['code'] < 300)) {
          break;
        }
      }
      if ($i > 1) break;
      $i++;
    }
    try {
      $this->response = $this->handle_response($this->response);
    } catch (Exception $e) {
      error_log($e->getMessage(), 0);
      /* raw_response_body is populated by handle_response() before it throws —
       * pull Google's own error.message out of it (e.g. consent/terms-of-service
       * rejections) instead of just the generic HTTP status text. */
      $body = json_decode((string) $this->raw_response_body, true);
      $this->last_error = $body['error']['message'] ?? $e->getMessage();
      $this->response   = null;
    }
    return $this->response;
  }

  /**
   * Build the wp_safe_remote_request() args for fetching Measurement Protocol secrets (GET).
   *
   * @return array Request args array (method, timeout, headers, etc.) for wp_safe_remote_request().
   */
  protected function get_custom_protocall_request_args()
  {
    return array(
      'method'      => 'GET',
      'timeout'     => 8,
      'redirection' => 0,
      'httpversion' => '1.1',
      'sslverify'   => $this->should_verify_ssl(),
      'user-agent'  => $this->get_request_user_agent(),
      'headers'     => $this->request_headers,
      'cookies'     => array(),
    );
  }

  /**
   * Build the wp_safe_remote_request() args for creating a Measurement Protocol secret (POST).
   *
   * @return array Request args array (method, timeout, headers, JSON body, etc.)
   *               for wp_safe_remote_request().
   */
  protected function custom_protocall_request_args()
  {
    /* wp_json_encode(), not a hand-built string — the previous literal had a
     * trailing comma before the closing brace, which is invalid JSON and made
     * Google reject every create request. */
    $body = wp_json_encode(array('displayName' => 'GA4WP_secret_Key'));
    return array(
      'method'      => 'POST',
      'timeout'     => 8,
      'redirection' => 0,
      'httpversion' => '1.1',
      'sslverify'   => $this->should_verify_ssl(),
      'user-agent'  => $this->get_request_user_agent(),
      'headers'     => $this->request_headers,
      'body'        => $body,
      'cookies'     => array(),
    );
  }

  /**
   * Fetch GA4 (v1beta) account summaries for the authorized user.
   *
   * @return object|false Decoded JSON response object on success, or false on failure.
   */
  public function get_g4_account_summaries()
  {
    $this->reset_response();
    $args            = $this->get_request_args();
    $args['timeout'] = 10;
    $this->response  = wp_safe_remote_request('https://analyticsadmin.googleapis.com/v1beta/accountSummaries', $args);
    try {
      $this->response = $this->handle_response($this->response);
    } catch (Exception $e) {
      error_log($e->getMessage(), 0);
      $this->response = false;
    }
    return $this->response;
  }
  /**
   * Fetch GA4 property metadata (timezone, currency, industry, service level).
   *
   * @param string $property_name GA4 property resource name, e.g. "properties/123".
   * @return array|null Decoded JSON response as an associative array, or null on failure.
   */
  public function get_ga4_property_details($property_name)
  {
    $url      = "https://analyticsadmin.googleapis.com/v1beta/{$property_name}";
    $response = wp_safe_remote_get($url, array('headers' => $this->request_headers, 'timeout' => 8, 'sslverify' => $this->should_verify_ssl()));
    if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) >= 300) {
      return null;
    }
    return json_decode(wp_remote_retrieve_body($response), true);
  }

  /**
   * Fetch GA4 data retention settings and marketing preferences for a property.
   *
   * @param string $property_name GA4 property resource name, e.g. "properties/123".
   * @return array|null Decoded JSON response as an associative array, or null on failure.
   */
  public function get_ga4_data_retention($property_name)
  {
    $url      = "https://analyticsadmin.googleapis.com/v1beta/{$property_name}/dataRetentionSettings";
    $response = wp_safe_remote_get($url, array('headers' => $this->request_headers, 'timeout' => 8, 'sslverify' => $this->should_verify_ssl()));
    if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) >= 300) {
      return null;
    }
    return json_decode(wp_remote_retrieve_body($response), true);
  }

  /**
   * Fetch the list of data streams (web/app) configured for a GA4 property.
   *
   * @param string $property_name GA4 property resource name, e.g. "properties/123".
   * @return object|string|null Decoded JSON response object on success, an empty
   *                             string on a handled error, or null if the request
   *                             never completed.
   */
  public function get_web_data_streams($property_name)
  {
    $this->reset_response();
    $args            = $this->get_request_args();
    $args['timeout'] = 8;
    $this->response  = wp_safe_remote_request("https://analyticsadmin.googleapis.com/v1beta/{$property_name}/dataStreams", $args);
    try {
      $this->response = $this->handle_response($this->response);
    } catch (Exception $e) {
      error_log($e->getMessage(), 0);
    }
    return $this->response;
  }

  /**
   * List GA4 accounts via the v1beta accounts endpoint.
   *
   * More stable than the v1alpha accountSummaries endpoint for simple account listing.
   *
   * @return array|null Decoded JSON response as an associative array, or null on failure.
   */
  public function get_ga4_accounts_list()
  {
    $response = wp_safe_remote_get(
      'https://analyticsadmin.googleapis.com/v1beta/accounts',
      array('headers' => $this->request_headers, 'timeout' => 10, 'sslverify' => $this->should_verify_ssl())
    );
    if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) >= 300) {
      return null;
    }
    return json_decode(wp_remote_retrieve_body($response), true);
  }

  /**
   * Fetch a single GA4 property, typically used to look up its parent account.
   *
   * @param string $property_resource GA4 property resource name, e.g. "properties/123".
   * @return array|null Decoded JSON response as an associative array, or null on failure.
   */
  public function get_ga4_property($property_resource)
  {
    $response = wp_safe_remote_get(
      "https://analyticsadmin.googleapis.com/v1beta/{$property_resource}",
      array('headers' => $this->request_headers, 'timeout' => 8, 'sslverify' => $this->should_verify_ssl())
    );
    if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) >= 300) {
      return null;
    }
    return json_decode(wp_remote_retrieve_body($response), true);
  }

  /**
   * Create a new GA4 account via the provisionAccountTicket endpoint.
   *
   * The user must be redirected to the returned URL to accept Google's terms
   * of service before the account is actually created. Handles both the
   * current API response shape (`accountTicketId`) and older/nested shapes
   * (`name` / `accountTicket`).
   *
   * @param string $display_name Display name for the new GA4 account.
   * @param string $region_code  ISO region/country code for the account.
   * @param string $redirect_uri Optional URL to redirect back to after ToS acceptance.
   * @return array{type:string, redirect_uri?:string, http_code?:int, message?:string}
   *               On success: ['type' => 'ticket', 'redirect_uri' => string].
   *               On failure: ['type' => 'error', 'http_code' => int, 'message' => string].
   */
  public function create_ga4_account($display_name, $region_code, $redirect_uri = '')
  {
    $args = $this->get_request_args();
    $args['method'] = 'POST';

    $args['body'] = wp_json_encode(array(
      'account'     => array('displayName' => $display_name, 'regionCode' => $region_code),
      'redirectUri' => $redirect_uri,
    ));
    $r2    = wp_safe_remote_request('https://analyticsadmin.googleapis.com/v1beta/accounts:provisionAccountTicket', $args);
    if (!is_wp_error($r2)) {
      $code2    = (int) wp_remote_retrieve_response_code($r2);
      $raw_body = wp_remote_retrieve_body($r2);
      $body2    = json_decode($raw_body, true);
      if ($code2 < 300) {
        // API returns { "accountTicketId": "xxx" }
        $ticket_id = $body2['accountTicketId'] ?? '';
        // Also handle older formats: top-level name or nested accountTicket
        if (empty($ticket_id)) {
          $ticket    = isset($body2['name']) ? $body2 : ($body2['accountTicket'] ?? array());
          $name      = $ticket['name'] ?? '';
          $ticket_id = $name ? str_replace('accountTickets/', '', $name) : '';
          if (!empty($ticket['redirectUri'])) {
            return array('type' => 'ticket', 'redirect_uri' => $ticket['redirectUri']);
          }
        }
        if (!empty($ticket_id)) {
          $provision_url = 'https://analytics.google.com/analytics/web/?_account_provision_redirect=' . rawurlencode($ticket_id);
          return array('type' => 'ticket', 'redirect_uri' => $provision_url);
        }
        return array('type' => 'error', 'http_code' => $code2, 'message' => 'Unexpected response. Raw: ' . $raw_body);
      }
      $api_msg  = $body2['error']['message'] ?? wp_remote_retrieve_response_message($r2);
      $api_code = $body2['error']['code']    ?? $code2;
      return array('type' => 'error', 'http_code' => $api_code, 'message' => $api_msg . ' Raw: ' . $raw_body);
    }

    return array('type' => 'error', 'http_code' => 0, 'message' => $r2->get_error_message());
  }

  /**
   * Create a new GA4 property under an existing account.
   *
   * @param string $account_resource Parent account resource name, e.g. "accounts/123".
   * @param string $display_name     Display name for the new property.
   * @param string $timezone         IANA timezone identifier for the property.
   * @param string $currency         ISO currency code for the property.
   * @return array|null Decoded JSON response as an associative array, or null on failure.
   */
  public function create_ga4_property($account_resource, $display_name, $timezone, $currency)
  {
    $body           = wp_json_encode(array(
      'parent'       => $account_resource,
      'displayName'  => $display_name,
      'timeZone'     => $timezone,
      'currencyCode' => $currency,
    ));
    $args           = $this->get_request_args();
    $args['method'] = 'POST';
    $args['body']   = $body;
    $response = wp_safe_remote_request('https://analyticsadmin.googleapis.com/v1beta/properties', $args);
    if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) >= 300) {
      return null;
    }
    return json_decode(wp_remote_retrieve_body($response), true);
  }

  /**
   * Create a web data stream for a GA4 property.
   *
   * @param string $property_resource   GA4 property resource name, e.g. "properties/123".
   * @param string $stream_display_name Display name for the new data stream.
   * @param string $website_url         Default URI of the site the stream tracks.
   * @return array|null Decoded JSON response as an associative array, or null on failure.
   */
  public function create_ga4_web_data_stream($property_resource, $stream_display_name, $website_url)
  {
    $body           = wp_json_encode(array(
      'type'          => 'WEB_DATA_STREAM',
      'displayName'   => $stream_display_name,
      'webStreamData' => array('defaultUri' => $website_url),
    ));
    $args           = $this->get_request_args();
    $args['method'] = 'POST';
    $args['body']   = $body;
    $response = wp_safe_remote_request("https://analyticsadmin.googleapis.com/v1beta/{$property_resource}/dataStreams", $args);
    if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) >= 300) {
      return null;
    }
    return json_decode(wp_remote_retrieve_body($response), true);
  }

  /**
   * Enable all enhanced measurement settings for a web data stream.
   *
   * Best-effort PATCH — the response is not checked or returned, so callers
   * cannot tell whether this succeeded.
   *
   * @param string $stream_resource Data stream resource name, e.g. "properties/123/dataStreams/456".
   */
  public function patch_enhanced_measurement($stream_resource)
  {
    $body = wp_json_encode(array(
      'streamEnabled'          => true,
      'scrollsEnabled'         => true,
      'outboundClicksEnabled'  => true,
      'siteSearchEnabled'      => true,
      'videoEngagementEnabled' => true,
      'fileDownloadsEnabled'   => true,
    ));
    $mask           = 'streamEnabled,scrollsEnabled,outboundClicksEnabled,siteSearchEnabled,videoEngagementEnabled,fileDownloadsEnabled';
    $args           = $this->get_request_args();
    $args['method'] = 'PATCH';
    $args['body']   = $body;
    wp_safe_remote_request(
      "https://analyticsadmin.googleapis.com/v1beta/{$stream_resource}/enhancedMeasurementSettings?updateMask={$mask}",
      $args
    );
  }

  /**
   * Parse a wp_safe_remote_request()/wp_safe_remote_get() response.
   *
   * Populates `$response_code`, `$response_message`, `$raw_response_body` and
   * `$response_headers` as a side effect, then either returns the decoded JSON
   * body (on HTTP 200) or throws with the response message otherwise.
   *
   * @param array|WP_Error $response Raw response from wp_safe_remote_request()/wp_safe_remote_get().
   * @return mixed Decoded JSON response object.
   * @throws Exception If `$response` is a WP_Error, or the HTTP status isn't 200.
   */
  protected function handle_response($response)
  {
    if (is_wp_error($response)) {
      throw new Exception($this->response->get_error_message());
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
   * Determine whether outgoing requests should verify the SSL certificate.
   *
   * SSL verification is skipped on localhost/XAMPP-style local environments
   * (localhost, 127.0.0.1, ::1, empty host, or a `.local`/`.dev` hostname)
   * where the CA bundle is typically not configured.
   *
   * @return bool True if SSL verification should be performed, false to skip it.
   */
  protected function should_verify_ssl()
  {
    $host = (string) parse_url((string) get_option('siteurl', ''), PHP_URL_HOST);
    if (in_array($host, array('localhost', '127.0.0.1', '::1', ''), true)) {
      return false;
    }
    if (substr($host, -6) === '.local' || substr($host, -4) === '.dev') {
      return false;
    }
    return true;
  }

  /**
   * Build the default wp_safe_remote_request() args used for GET API calls.
   *
   * @return array Request args array (method, timeout, headers, etc.) for wp_safe_remote_request().
   */
  protected function get_request_args()
  {
    $args = array(
      'method' => 'GET',
      'timeout' => 8,
      'redirection' => 0,
      'httpversion' => '1.1',
      'sslverify' => $this->should_verify_ssl(),
      'blocking' => true,
      'user-agent' => $this->get_request_user_agent(),
      'headers' => $this->request_headers,
      'body' => '',
      'cookies' => array(),
    );
    return $args;
  }

  /**
   * Build the User-Agent header string sent with outgoing API requests.
   *
   * @return string HTML-entity-escaped user agent string if a function named after
   *                 `ga4wp_get_user_agent()`'s return value exists, otherwise a
   *                 default "TrueAna/{version} (WordPress/{version})" string.
   */
  protected function get_request_user_agent()
  {
    if (function_exists($this->ga4wp_get_user_agent())) {
      $user_agent = htmlentities($this->ga4wp_get_user_agent(), ENT_QUOTES, 'UTF-8');
    } else {
      $user_agent = sprintf('%s/%s (WordPress/%s)', 'TrueAna', GA4WP_VERSION, $GLOBALS['wp_version']);
    }
    return $user_agent;
  }
  /**
   * Get the requesting browser's user agent string from the server environment.
   *
   * @return string Lowercased value of `$_SERVER['HTTP_USER_AGENT']`, or an
   *                 empty string if it isn't set.
   */
  public function ga4wp_get_user_agent()
  {
    return isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
  }

  /**
   * Reset the stored response state before making a new API request.
   */
  protected function reset_response()
  {
    $this->response_code = null;
    $this->response_message = null;
    $this->raw_response_body = null;
    $this->response = null;
  }
}

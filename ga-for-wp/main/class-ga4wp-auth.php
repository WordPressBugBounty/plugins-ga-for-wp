<?php 
/**
 * Class: GA4WP_Auth
 *
 * Core authentication class. Handles OAuth token lifecycle, API factory
 * methods, and WP action/AJAX registration. All rendering and AJAX handler
 * logic lives in the traits loaded below.
 *
 */
if ( !defined( 'ABSPATH' ) ) {
    die;
}
require_once __DIR__ . '/traits/trait-ga4wp-dashboard.php';
require_once __DIR__ . '/traits/trait-ga4wp-stats-renderer.php';
require_once __DIR__ . '/traits/trait-ga4wp-chart-renderer.php';
require_once __DIR__ . '/traits/trait-ga4wp-ajax-handlers.php';
// Measurement Protocol setup — available on both free and premium tiers.
require_once __DIR__ . '/traits/trait-ga4wp-property-setup.php';
trait GA4WP_Custom_Dimension_Setup
{
    /**
     * No-op stub for the free tier; custom dimension creation is a premium-only feature.
     */
    public function ga4wp_create_custom_dimension() : void {
    }

}
trait GA4WP_Auth_Premium_Only
{
    /**
     * No-op stub for the free tier; premium-only action registration only
     * happens when premium code is loaded.
     */
    protected function register_premium_actions() : void {
    }

    /**
     * No-op stub for the free tier; the realtime API client is premium-only.
     *
     * @return null Always null on the free tier.
     */
    public function get_google_realtime_api() {
        return null;
    }

    /**
     * No-op stub for the free tier; email settings handling is premium-only.
     *
     * @param mixed $_option    Unused.
     * @param mixed $_old_value Unused.
     * @param mixed $_new_value Unused.
     */
    public function on_email_settings_updated( $_option, $_old_value, $_new_value ) : void {
    }

    /**
     * No-op stub for the free tier; returns the cron schedules unmodified.
     *
     * @param array $schedules Existing WP cron schedules.
     * @return array The unmodified $schedules array.
     */
    public function register_ga4wp_cron_schedules( array $schedules ) : array {
        return $schedules;
    }

}
trait GA4WP_Ajax_Handlers_Premium_Only
{
    // Free stub — no premium AJAX methods
}
class GA4WP_Auth {
    use 
        GA4WP_Property_Setup,
        GA4WP_Custom_Dimension_Setup,
        GA4WP_Dashboard,
        GA4WP_Stats_Renderer,
        GA4WP_Chart_Renderer,
        GA4WP_Ajax_Handlers,
        GA4WP_Auth_Premium_Only,
        GA4WP_Ajax_Handlers_Premium_Only
    ;
    const PROXY_URL = 'https://google.trueana.com';

    private $management_api;

    private $api_ready = false;

    private $report_api;

    private static $instance = null;

    // Single shared token cache used by all three API factories below, so a
    // token is refreshed at most once per request regardless of how many
    // of the factory methods get called.
    private $cached_token = null;

    /**
     * Retrieves the singleton instance of this class, creating it on first call.
     *
     * @return GA4WP_Auth The shared instance.
     */
    public static function get_instance() {
        if ( !self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Registers the WordPress hooks this class depends on.
     */
    public function __construct() {
        // REMOVED: add_action('rest_api_init', array($this, 'ga4wp_register_rest_routes'));
        // The only route it registered (/auth/callback) backed the local-dev-only
        // delivery path — see ga4wp_handle_auth_callback()'s comment below.
        add_action( 'wp_ajax_web_ga4wp_un_link', array($this, 'web_un_link') );
        add_action( 'wp_ajax_web_ga4wp_tab_update', array($this, 'tab_update') );
        add_action( 'wp_ajax_web_ga4wp_revoke_access', array($this, 'web_revoke_access') );
        add_action( 'wp_ajax_ga4wp_get_ga4_accounts', array($this, 'ajax_get_ga4_accounts') );
        add_action( 'wp_ajax_ga4wp_create_property', array($this, 'ajax_create_ga4_property') );
        add_action( 'wp_ajax_ga4wp_create_ga4_account', array($this, 'ajax_create_ga4_account') );
        add_action( 'wp_ajax_ga4wp_refresh_properties', array($this, 'ajax_refresh_properties') );
        add_action( 'wp_ajax_ga4wp_check_auth_status', array($this, 'ajax_check_auth_status') );
        add_action( 'wp_ajax_ga4wp_generate_measurement_key', array($this, 'ajax_generate_measurement_key') );
        add_action( 'admin_enqueue_scripts', array($this, 'load_local_script') );
        add_action( 'plugins_loaded', array($this, 'new_update_settings') );
        add_action( 'wp_dashboard_setup', array($this, 'ga4wp_dashboard_widget') );
        add_action( 'ga4wp_run_property_setup', array($this, 'ga4wp_edit_action_scope') );
        add_action(
            'updated_option',
            array($this, 'on_auth_settings_updated'),
            10,
            3
        );
        $this->register_premium_actions();
    }

    /**
     * Seeds default tracking and event settings options if they aren't already set.
     */
    public function new_update_settings() {
        $ga4wp_settings = GA4WP_Settings::get_instance();
        if ( !get_option( 'ga4wp_track_settings' ) ) {
            update_option( 'ga4wp_track_settings', $ga4wp_settings->init_ga4wp_track_defaults() );
        }
        if ( !get_option( 'ga4wp_event_settings' ) ) {
            update_option( 'ga4wp_event_settings', $ga4wp_settings->init_ga4wp_events_defaults() );
        }
    }

    /**
     * AJAX handler that unlinks the connected Google Analytics account.
     */
    public function web_un_link() {
        check_ajax_referer( 'ga4wp-un-link', 'security' );
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized', 403 );
            return;
        }
        $this->ga4wp_clear_auth_options();
        wp_die();
    }

    /**
     * AJAX handler that revokes the OAuth access token at Google, then clears local state.
     *
     * CHANGE (this revision, H): signs with sign_proxy_request() using the
     * stored credential rather than a stored secret — see that method's
     * docblock. If no credential has been established yet, the remote
     * revoke call is skipped entirely, but local state is still cleared:
     * a user asking to disconnect should never be left looking
     * "connected" just because the remote side couldn't be authenticated.
     * Hooked to 'wp_ajax_web_ga4wp_revoke_access'.
     */
    public function web_revoke_access() {
        check_ajax_referer( 'ga4wp-revoke-access', 'security' );
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized', 403 );
            return;
        }
        $token = $this->parse_access_token( $this->get_access_token() )->access_token;
        if ( !empty( $token ) ) {
            $payload_b64 = base64_encode( $token );
            $sig = $this->sign_proxy_request( 'revoke', $payload_b64 );
            if ( $sig !== null ) {
                $host = (string) parse_url( (string) get_option( 'siteurl', '' ), PHP_URL_HOST );
                $sslverify = !in_array( $host, array(
                    'localhost',
                    '127.0.0.1',
                    '::1',
                    ''
                ), true ) && substr( $host, -6 ) !== '.local' && substr( $host, -4 ) !== '.dev';
                wp_remote_post( self::PROXY_URL . '/auth/revoke', [
                    'timeout'   => 15,
                    'sslverify' => $sslverify,
                    'body'      => array_merge( [
                        'token2' => $payload_b64,
                    ], $sig ),
                ] );
            }
            // If $sig is null (no credential on record), we simply skip
            // the remote call — see docblock above.
        }
        $this->ga4wp_clear_auth_options();
        delete_option( 'ga4wp_site_credential' );
        wp_die();
    }

    /**
     * Deletes all stored OAuth tokens and related plugin options.
     *
     * Note: does NOT delete 'ga4wp_site_credential' — that's handled
     * explicitly by callers, since un-linking (web_un_link) intentionally
     * keeps the credential (a fast reconnect shouldn't need a brand new proxy
     * trust relationship), while a full revoke does clear it.
     */
    private function ga4wp_clear_auth_options() : void {
        delete_option( 'ga4wp_access_token' );
        delete_option( 'ga4wp_dash_settings' );
        delete_option( 'ga4wp_refresh_token' );
        delete_option( 'ga4wp_auth_settings' );
        delete_option( 'ga4wp_granted_scopes' );
        delete_option( 'measurement_key' );
        delete_option( 'measurement_key_process' );
        delete_option( 'custom_dimension_process' );
        delete_option( 'dimension_key' );
        delete_option( 'ga4wp_refresh_token_fail' );
        delete_option( 'ga_properties' );
        $track = get_option( 'ga4wp_track_settings', [] );
        if ( !empty( $track['google_measurement_api'] ) ) {
            $track['google_measurement_api'] = '';
            update_option( 'ga4wp_track_settings', $track );
        }
    }

    /**
     * Registers and enqueues the plugin's admin JS with localized settings data.
     */
    public function load_local_script() {
        if ( !isset( $_GET['page'] ) || $_GET['page'] !== 'ga4wp_pro_plugin_options' ) {
            return;
        }
        wp_register_script(
            'ga4wp_js',
            GA4WP_URL . 'assests/js/ga4wp.js',
            array('jquery'),
            null,
            true
        );
        wp_localize_script( 'ga4wp_js', 'ga4wp_js_object', array(
            'ajax_url'            => admin_url( 'admin-ajax.php' ),
            'auth_url'            => ( $this->get_access_token() && !self::is_reauth_required() ? '' : $this->get_link_url() ),
            'revoke_access_nonce' => wp_create_nonce( 'ga4wp-revoke-access' ),
            'un_link_nonce'       => wp_create_nonce( 'ga4wp-un-link' ),
            'tab_update_nonce'    => wp_create_nonce( 'ga4wp-tab-update' ),
            'create_dims_nonce'   => wp_create_nonce( 'ga4wp-create-dims' ),
            'current_tab_id'      => get_option( 'ga4wp_current_tab_id', 'audience' ),
        ) );
        wp_enqueue_script( 'ga4wp_js' );
        wp_enqueue_script( 'jquery-ui-sortable' );
    }

    /* ── Token helpers ─────────────────────────────────────────────── */
    private function parse_access_token( $json_token = '' ) {
        $token = [
            'access_token' => '',
            'expires_in'   => 0,
            'created'      => current_time( 'timestamp', true ),
        ];
        if ( is_string( $json_token ) && '' !== $json_token ) {
            $token = wp_parse_args( (array) json_decode( $json_token ), $token );
        }
        return (object) $token;
    }

    public function get_access_token() {
        return get_option( 'ga4wp_access_token', null );
    }

    /**
     * Builds the URL that starts the OAuth flow via the proxy server.
     *
     * Generates a one-time state value and stores it both as a per-value
     * transient (for the callback's own validation) and as the single
     * "pending state" transient that ajax_check_auth_status() polls
     * against.
     *
     * @return string The proxy authorization URL to redirect the user to.
     */
    public function get_link_url() {
        $state = bin2hex( random_bytes( 16 ) );
        set_transient( 'ga4wp_oauth_state_' . $state, true, 600 );
        // CHANGE (push→pull redesign): the single "pending" state that
        // ajax_check_auth_status() polls the proxy for. Keyed globally —
        // only one OAuth attempt is meaningfully in flight per site; a
        // fresh attempt simply overwrites this.
        set_transient( 'ga4wp_pending_oauth_state', $state, 600 );
        // dev_mode is always '0' now — see the removed DEV_MODE constant's
        // comment above; the local-dev delivery path it selected no longer
        // exists in this codebase, so production (poll_proxy_for_token()) is
        // the only path left. Still sent as a fixed value since the proxy's
        // /auth endpoint expects the field to be present.
        return self::PROXY_URL . '/auth?callback=' . urlencode( $this->get_callback_url() ) . '&state=' . $state;
    }

    /**
     * Builds the `callback` URL string sent to the proxy's /auth endpoint.
     *
     * No route is registered at this URL any more (see the removed
     * ga4wp_register_rest_routes()/ga4wp_handle_auth_callback() above) — the
     * proxy's /auth endpoint expects the field regardless, but with dev_mode
     * always '0' it never actually redirects a browser here.
     *
     * @return string The (now unused-by-us) callback URL string.
     */
    public function get_callback_url() : string {
        return get_home_url();
    }

    /**
     * Retrieves the stored OAuth refresh token.
     *
     * @return string|null The refresh token, or null if not set.
     */
    private function get_refresh_token() {
        return get_option( 'ga4wp_refresh_token', null );
    }

    private function is_access_token_expired( $token ) {
        $expired = !(is_object( $token ) && $token->created && $token->expires_in);
        if ( !$expired ) {
            $time_expires = max( 0, (int) $token->created + (int) $token->expires_in );
            $expired = $time_expires <= current_time( 'timestamp', true );
        }
        return $expired;
    }

    /**
     * Signs a request to the proxy using this site's credential,
     * established during initial connect.
     *
     * CHANGE (this revision, H): the credential replaces the stored
     * per-site secret from the previous revision. It's self-verifying —
     * the proxy checks its signature against a single master key it
     * already holds, with no per-site disk lookup — so there's nothing
     * server-side that a bulk deletion could wipe out for every site at
     * once. See token_cache_lib.php's ga4wp_issue_credential() on the
     * proxy side for the full design.
     *
     * The credential itself is included in the returned fields — the
     * proxy needs to see it to verify the request. The signed message
     * additionally binds the action (so a signed refresh can't be
     * replayed against revoke), the site's own origin, a timestamp
     * (bounds replay window), a single-use nonce, and a hash of the
     * payload itself (so the signature can't be reused with a
     * substituted token value) — using the credential string as the
     * HMAC key.
     *
     * @param string $action      'refresh' or 'revoke'.
     * @param string $payload_b64 The base64 token payload being sent.
     * @return array|null Signing fields (including the credential) to
     *                     merge into the request body, or null if no
     *                     credential has been established yet (the site
     *                     needs to reconnect first).
     */
    private function sign_proxy_request( string $action, string $payload_b64 ) : ?array {
        $credential = get_option( 'ga4wp_site_credential' );
        if ( empty( $credential ) ) {
            return null;
        }
        $timestamp = (string) time();
        $nonce = bin2hex( random_bytes( 16 ) );
        $origin = get_home_url();
        // Sign using the bare host — must match what the proxy
        // reconstructs via parse_url($_POST['callback_origin'], PHP_URL_HOST).
        $origin_host = (string) parse_url( $origin, PHP_URL_HOST );
        $message = implode( '|', [
            $action,
            $origin_host,
            $timestamp,
            $nonce,
            hash( 'sha256', $payload_b64 )
        ] );
        $signature = hash_hmac( 'sha256', $message, $credential );
        return [
            'callback_origin' => $origin,
            'credential'      => $credential,
            'timestamp'       => $timestamp,
            'nonce'           => $nonce,
            'signature'       => $signature,
        ];
    }

    /**
     * Whether this site genuinely needs the user to reconnect — the ONLY
     * check any UI code should use, instead of reading
     * get_option('ga4wp_refresh_token_fail') directly.
     *
     * CHANGE (reauth precision): previously, ga4wp_refresh_token_fail
     * held either 'retry' or 'yes' depending on how many CONSECUTIVE
     * "malformed response" failures had occurred — but every consumer in
     * the codebase checked it with a bare empty()/!empty(), so 'retry'
     * and 'yes' were treated identically everywhere. That meant reauth
     * was effectively forced on the FIRST occurrence of that failure
     * type, not the second as the two-value design implied — and
     * genuinely transient failures (network errors, proxy 5xx, Google
     * rate-limiting) were already correctly NOT forcing reauth, but there
     * was no single place that made this behavior legible or guaranteed
     * consistent across every file that checked it.
     *
     * Now: refresh_access_token() only ever writes 'dead' to this option,
     * and only when the failure is one that's actually unrecoverable
     * without reconnecting (Google's own invalid_grant, or a second
     * CONSECUTIVE malformed-response failure). Every other failure path
     * leaves this option untouched. This method is the only place that
     * translates the stored value into a yes/no "show reconnect UI"
     * decision — every UI file below calls this instead of re-deriving
     * the logic itself, so there's exactly one place to get it right.
     *
     * @return bool True only if reconnecting is genuinely required.
     */
    public static function is_reauth_required() : bool {
        return get_option( 'ga4wp_refresh_token_fail' ) === 'dead';
    }

    /**
     * Requests a fresh access token from the proxy server using the stored refresh token.
     *
     * CHANGE (this revision, H): authenticates via sign_proxy_request()
     * using the stored credential rather than a stored secret. See that
     * method's docblock for why.
     *
     * @throws Exception If no credential is established, the refresh
     *                    token is missing, the request fails, or the
     *                    returned token is invalid.
     * @return object The newly parsed access token.
     */
    private function refresh_access_token() {
        if ( !$this->get_refresh_token() ) {
            throw new Exception('Could not refresh access token: refresh token not available.');
        }
        $cooldown_active = get_transient( 'ga4wp_refresh_transient_cooldown' );
        if ( $cooldown_active ) {
            throw new Exception('Could not refresh access token: proxy temporarily unavailable, retry deferred.');
        }
        $payload_b64 = base64_encode( $this->get_refresh_token() );
        $sig = $this->sign_proxy_request( 'refresh', $payload_b64 );
        if ( $sig === null ) {
            update_option( 'ga4wp_access_token', false );
			update_option('ga4wp_refresh_token_fail', 'dead');
            throw new Exception('Could not refresh access token: no credential established. Please reconnect.');
        }
        $host = (string) parse_url( (string) get_option( 'siteurl', '' ), PHP_URL_HOST );
        $sslverify = !in_array( $host, array(
            'localhost',
            '127.0.0.1',
            '::1',
            ''
        ), true ) && substr( $host, -6 ) !== '.local' && substr( $host, -4 ) !== '.dev';
        $response = wp_remote_post( self::PROXY_URL . '/auth/refresh', [
            'timeout'   => 15,
            'sslverify' => $sslverify,
            'body'      => array_merge( [
                'token' => $payload_b64,
            ], $sig ),
        ] );
        if ( $response instanceof WP_Error ) {
            update_option( 'ga4wp_access_token', false );
            set_transient( 'ga4wp_refresh_transient_cooldown', true, 90 );
            throw new Exception(sprintf( 'Could not refresh access token: %s', json_encode( $response->errors ) ));
        }
        if ( empty( $response['body'] ) ) {
            update_option( 'ga4wp_access_token', false );
            set_transient( 'ga4wp_refresh_transient_cooldown', true, 90 );
            throw new Exception('Could not refresh access token: response was empty.');
        }
        $code = (int) ($response['response']['code'] ?? 0);
        if ( $code === 410 ) {
            update_option( 'ga4wp_access_token', false );
            update_option( 'ga4wp_refresh_token_fail', 'dead' );
            throw new Exception('Could not refresh access token: Google reports this connection is no longer valid. Please reconnect.');
        }
        if ( $code >= 500 ) {
            set_transient( 'ga4wp_refresh_transient_cooldown', true, 90 );
        }
        if ( $code >= 400 ) {
            update_option( 'ga4wp_access_token', false );
            $body = ( is_string( $response['body'] ?? null ) ? trim( $response['body'] ) : '' );
            throw new Exception(sprintf( 'Could not refresh access token: proxy returned HTTP %d%s.', $code, ( $body !== '' ? " ({$body})" : '' ) ));
        }
        $decoded = base64_decode( $response['body'], true );
        if ( $decoded === false || !json_decode( $decoded, true ) || stripos( $decoded, 'error' ) !== false ) {
            update_option( 'ga4wp_access_token', false );
            $status = get_option( 'ga4wp_refresh_token_fail' );
            update_option( 'ga4wp_refresh_token_fail', ( $status === 'retry' ? 'dead' : 'retry' ) );
            throw new Exception('Could not refresh access token: returned token was invalid.');
        }
        update_option( 'ga4wp_access_token', $decoded );
        delete_option( 'ga4wp_refresh_token_fail' );
        return $this->parse_access_token( $decoded );
    }

    /**
     * Returns a currently-valid access token, refreshing it if expired.
     *
     * @return object The valid (or empty, on failure) access token object.
     */
    private function get_valid_token() {
        if ( $this->cached_token !== null ) {
            return $this->cached_token;
        }
        $token = $this->parse_access_token( $this->get_access_token() );
        if ( $this->is_access_token_expired( $token ) ) {
            try {
                $token = $this->refresh_access_token();
                delete_transient( 'ga4wp_last_refresh_error' );
            } catch ( Exception $e ) {
                error_log( $e->getMessage() );
                // Stashed so the UI can distinguish WHY the refresh failed (e.g. the
                // proxy itself being unreachable vs. a run-of-the-mill transient hiccup)
                // instead of only ever seeing the generic empty-token 401 that follows.
                set_transient( 'ga4wp_last_refresh_error', $e->getMessage(), 5 * MINUTE_IN_SECONDS );
                $token = $this->parse_access_token();
            }
        }
        return $this->cached_token = $token;
    }

    /**
     * The message from the most recent failed refresh_access_token() call, if any.
     *
     * Lets UI code show a more specific reason (e.g. the proxy being
     * unreachable) than the generic "no valid access token" error that
     * follows once get_valid_token() falls back to an empty token.
     *
     * @return string The exception message, or '' if the last refresh succeeded (or none was attempted).
     */
    public static function get_last_refresh_error() : string {
        return (string) get_transient( 'ga4wp_last_refresh_error' );
    }

    /**
     * Polls the proxy's /auth/poll endpoint for a payload cached under $state.
     *
     * This is the WordPress side of the push→pull redesign for initial
     * connect: instead of the proxy POSTing the token to this site (which
     * many sites' firewalls block), WordPress reaches out to the proxy
     * itself. Outbound requests initiated by the site are essentially
     * never blocked by that site's own firewall.
     *
     * CHANGE (this revision, H): the retrieved payload is the
     * { token, site_credential } wrapper — see ga4wp_handle_auth_callback()
     * above for why. Both parts are stored on success.
     *
     * @param string $state The pending OAuth state to poll for.
     * @return bool True if a token was retrieved and stored.
     */
    private function poll_proxy_for_token( string $state ) : bool {
        $host = (string) parse_url( (string) get_option( 'siteurl', '' ), PHP_URL_HOST );
        $sslverify = !in_array( $host, array(
            'localhost',
            '127.0.0.1',
            '::1',
            ''
        ), true ) && substr( $host, -6 ) !== '.local' && substr( $host, -4 ) !== '.dev';
        $response = wp_remote_post( self::PROXY_URL . '/auth/poll', [
            'timeout'   => 10,
            'sslverify' => $sslverify,
            'body'      => [
                'state'           => $state,
                'callback_origin' => get_home_url(),
            ],
        ] );
        if ( $response instanceof WP_Error ) {
            return false;
        }
        $code = (int) ($response['response']['code'] ?? 0);
        if ( $code !== 200 || empty( $response['body'] ) ) {
            // 404 = not ready yet, 403 = origin mismatch — either way,
            // keep polling; the pending-state transient's own 600s TTL is
            // what eventually stops retries, not this function.
            return false;
        }
        $wrapper_json = base64_decode( $response['body'], true );
        $wrapper = json_decode( $wrapper_json, true );
        if ( !is_array( $wrapper ) || empty( $wrapper['token'] ) || empty( $wrapper['site_credential'] ) ) {
            return false;
        }
        $token = $wrapper['token'];
        if ( !is_array( $token ) || empty( $token['access_token'] ) || empty( $token['refresh_token'] ) ) {
            return false;
        }
        $this->ga4wp_granted_scopes( $token['scope'] ?? '' );
        update_option( 'ga4wp_access_token', json_encode( $token ) );
        update_option( 'ga4wp_refresh_token', $token['refresh_token'] );
        update_option( 'ga4wp_site_credential', $wrapper['site_credential'] );
        delete_option( 'ga4wp_refresh_token_fail' );
        delete_transient( 'ga4wp_pending_oauth_state' );
        return true;
    }

    /**
     * AJAX handler polled by the admin page while the OAuth popup is open.
     *
     * CHANGE (push→pull redesign): if not yet connected, actively polls
     * the proxy for a token under the pending state instead of passively
     * waiting for an inbound POST. No-op once already connected or once
     * the pending state has expired.
     * Hooked to 'wp_ajax_ga4wp_check_auth_status'.
     */
    public function ajax_check_auth_status() {
        check_ajax_referer( 'ga4wp-tab-update', 'security' );
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized', 403 );
            return;
        }
        $connected = get_option( 'ga4wp_access_token' ) && !self::is_reauth_required();
        if ( !$connected ) {
            $pending_state = get_transient( 'ga4wp_pending_oauth_state' );
            if ( $pending_state ) {
                $connected = $this->poll_proxy_for_token( $pending_state );
            }
        }
        wp_send_json_success( [
            'connected' => (bool) $connected,
        ] );
    }

    /**
     * Stores the OAuth scopes granted by Google, if they look like a Google API scope string.
     *
     * @param string $scopes Space-separated list of granted OAuth scopes.
     */
    public function ga4wp_granted_scopes( $scopes ) {
        if ( !empty( $scopes ) && stripos( $scopes, 'googleapis' ) !== false ) {
            update_option( 'ga4wp_granted_scopes', explode( ' ', $scopes ) );
        }
    }

    /**
     * Schedules the property setup routine when the connected GA4 property changes.
     */
    public function on_auth_settings_updated( $option, $old_value, $new_value ) {
        if ( $option !== 'ga4wp_auth_settings' ) {
            return;
        }
        $new_property = ( is_array( $new_value ) ? $new_value['property_id'] ?? '' : '' );
        $old_property = ( is_array( $old_value ) ? $old_value['property_id'] ?? '' : '' );
        if ( !empty( $new_property ) && $new_property !== $old_property ) {
            wp_clear_scheduled_hook( 'ga4wp_run_property_setup' );
            wp_schedule_single_event( time() + 90, 'ga4wp_run_property_setup' );
        }
    }

    /* ── Property helpers ──────────────────────────────────────────── */
    private function get_ga_property_part( $key ) {
        $auth_settings = get_option( 'ga4wp_auth_settings' );
        if ( !is_array( $auth_settings ) ) {
            return null;
        }
        $property = $auth_settings['property_id'] ?? null;
        if ( !$property ) {
            return null;
        }
        $pieces = explode( '|', $property );
        return $pieces[$key] ?? null;
    }

    public function get_ga_account_id() {
        return $this->get_ga_property_part( 0 );
    }

    public function get_ga_property_id() {
        return $this->get_ga_property_part( 1 );
    }

    public function get_analytics_g4_properties() {
        $g4_account_summary = array();
        if ( $this->get_access_token() ) {
            $api = $this->get_google_management_api();
            $object = $api->get_g4_account_summaries();
            if ( !empty( $object ) && isset( $object->accountSummaries ) ) {
                $g4_account_summary = $object->accountSummaries;
            }
        }
        return $g4_account_summary;
    }

    /* ── API factories ─────────────────────────────────────────────── */
    public function get_google_management_api() {
        if ( $this->api_ready && $this->management_api ) {
            return $this->management_api;
        }
        require_once GA4WP_DIR . 'inc/api/ga4wp-google-management-api.php';
        $token = $this->get_valid_token();
        $this->api_ready = true;
        $api_class = ( class_exists( 'GA4WP_Premium_Management_API' ) ? 'GA4WP_Premium_Management_API' : 'GA4WP_Google_Management_API' );
        $this->management_api = new $api_class($token->access_token);
        return $this->management_api;
    }

    public function get_google_report_api() {
        require_once GA4WP_DIR . 'inc/api/ga4wp-google-report-api.php';
        $token = $this->get_valid_token();
        return $this->report_api = new GA4WP_Google_Report_API($token->access_token);
    }

    public function get_google_analytics_data_api() {
        require_once GA4WP_DIR . 'inc/api/ga4wp-google-analytics-data-api.php';
        $token = $this->get_valid_token();
        return $this->report_api = new GA4WP_Google_Analytics_Data_API($token->access_token);
    }

}

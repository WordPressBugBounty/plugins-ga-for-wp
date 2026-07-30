<?php
/* Adding Main functionality of plugin here */
if (!defined('ABSPATH')) {
	die;
}

if (function_exists('gfw_fs') && gfw_fs()->can_use_premium_code__premium_only()) {
	require_once GA4WP_DIR . 'main/traits/trait-ga4wp-main__premium_only.php';
} else {
	trait GA4WP_Main_Premium_Only
	{
		/**
		 * Free-version fallback: custom dimension values are a premium-only feature.
		 *
		 * @return array Always an empty array in the free version.
		 */
		private function get_ga4wp_custom_dimension_values(): array
		{
			return [];
		}
		/**
		 * Free-version fallback: outputting advanced pixel tags is a premium-only feature.
		 *
		 * @param array $advance_options The `ga4wp_advance_settings` option value.
		 */
		protected function output_advanced_pixel_tags(array $advance_options): void {}
		/**
		 * Free-version fallback: tracking product-view pixel conversions is a premium-only feature.
		 *
		 * @param int|string $product_id  The viewed product's ID.
		 * @param float      $wc_price    The product's price.
		 * @param string     $wc_currency The store's currency code.
		 */
		protected function track_product_view_pixels($product_id, $wc_price, $wc_currency): void {}
		/**
		 * Free-version fallback: tracking add-to-cart pixel conversions is a premium-only feature.
		 *
		 * @param int|string $product_id  The added product's ID.
		 * @param float      $wc_price    The product's price.
		 * @param string     $wc_currency The store's currency code.
		 */
		protected function track_add_to_cart_pixels($product_id, $wc_price, $wc_currency): void {}
		/**
		 * Free-version fallback: additional (premium-only) pixel purchase conversions.
		 *
		 * @param int   $order_id The completed order's ID.
		 * @param array $contents Order line items as `array( array( 'id' => ..., 'quantity' => ... ), ... )`.
		 */
		public function add_premium_pixel_conversions($order_id, $contents): void {}
		/**
		 * Free-version fallback: classifies an order for subscription-aware purchase tracking.
		 *
		 * Subscription detection (renewals, plan switches, etc.) is a premium-only
		 * feature, so the free version always reports a plain WooCommerce purchase.
		 *
		 * @param WC_Order $order The order being tracked.
		 * @return array Always `array( 'conversion_type' => 'woo_reg', 'subscription_id' => '', 'renewal_count' => null )` in the free version.
		 */
		protected function get_purchase_subscription_params($order): array
		{
			return ['conversion_type' => 'woo_reg', 'subscription_id' => '', 'renewal_count' => null];
		}
		/**
		 * Free-version fallback: purchase conversion-type/subscription custom
		 * dimensions (ga4wp_conversion_type, ga4wp_subscription_id,
		 * ga4wp_subscription_renewal_count) are a premium-only feature.
		 *
		 * @param WC_Order $order The completed order.
		 * @return array Always an empty array in the free version.
		 */
		protected function get_purchase_conversion_dimension_params($order): array
		{
			return [];
		}
		/**
		 * Free-version fallback: the "ga4wp_user_id" USER-scoped custom dimension is a premium-only feature.
		 *
		 * @param int|string $customer_id The WP/customer user ID to report.
		 * @return string Always '' in the free version.
		 */
		private function get_ga4wp_user_id_dimension_value($customer_id)
		{
			return '';
		}
		/**
		 * Free-version fallback: the "ga4wp_user_role" USER-scoped custom dimension is a premium-only feature.
		 *
		 * @param int|null $user_id Explicit WP user ID to look up, or null for the current session user.
		 * @return string Always '' in the free version.
		 */
		private function get_ga4wp_user_role_dimension_value($user_id = null)
		{
			return '';
		}
		/**
		 * Free-version fallback: sending a subscription lifecycle event is a premium-only feature.
		 *
		 * @param string $event_name The subscription event name to send.
		 * @param mixed  $sub        The subscription object involved.
		 */
		private function send_subscription_event(string $event_name, $sub): void {}
		/**
		 * Free-version fallback for the `woocommerce_subscription_payment_failed` hook.
		 *
		 * @param mixed $subscription The subscription involved.
		 * @param mixed $last_order   The related order, if any.
		 */
		public function subscription_payment_failed($subscription, $last_order = null): void {}
		/**
		 * Free-version fallback for the subscription renewal-payment-failed hook.
		 *
		 * @param mixed $subscription  The subscription involved.
		 * @param mixed $renewal_order The renewal order, if any.
		 */
		public function subscription_renewal_failed($subscription, $renewal_order = null): void {}
		/**
		 * Free-version fallback for the `woocommerce_subscription_cancelled` hook.
		 *
		 * @param mixed $subscription The cancelled subscription.
		 */
		public function subscription_cancelled($subscription): void {}
		/**
		 * Free-version fallback for the `woocommerce_subscription_expired` hook.
		 *
		 * @param mixed $subscription The expired subscription.
		 */
		public function subscription_expired($subscription): void {}
		/**
		 * Free-version fallback for the `woocommerce_subscription_on-hold` hook.
		 *
		 * @param mixed $subscription The subscription put on hold.
		 */
		public function subscription_on_hold($subscription): void {}
		/**
		 * Free-version fallback for the subscription trial-ended hook.
		 *
		 * @param int $subscription_id The subscription's post ID.
		 */
		public function subscription_trial_ended($subscription_id): void {}
		/**
		 * Free-version fallback for the subscription expiration-scheduled hook.
		 *
		 * @param int $subscription_id The subscription's post ID.
		 */
		public function subscription_expiration_scheduled($subscription_id): void {}
		/**
		 * Free-version fallback for the subscription prepaid-term-ended hook.
		 *
		 * @param int $subscription_id The subscription's post ID.
		 */
		public function subscription_prepaid_term_ended($subscription_id): void {}
		/**
		 * Free-version fallback for the `woocommerce_checkout_subscription_created` hook.
		 *
		 * @param mixed $subscription   The newly created subscription.
		 * @param mixed $request        The originating order/request, if any.
		 * @param mixed $recurring_cart The recurring cart used to create it, if any.
		 */
		public function subscription_created($subscription, $request = null, $recurring_cart = null): void {}
		/**
		 * Free-version fallback for the subscription plan-switched hook.
		 *
		 * @param mixed $subscription The subscription whose plan changed.
		 * @param mixed $switch_order The order that recorded the switch, if any.
		 */
		public function subscription_plan_switched($subscription, $switch_order = null): void {}
	}
}

/*
 * Declaring Class
 */
class GA4WP_Main
{
	use GA4WP_Main_Premium_Only;
	/* initiating variables */
	private $event_hooks;
	private $javascript = '';
	private $event_settings;
	private $params = array();
	private $data = array();
	private $tracking_id;
	private $cid;
	private $api_secret;
	private $loop_items;
	private static $queued_js = array();

	/**
	 * Wires up all WordPress/WooCommerce hooks the class listens on.
	 *
	 * Bails out early if tracking is disabled (no tracking ID, or
	 * disable_tracking() returns true). Otherwise walks the event-to-hook map
	 * from get_event_hooks() and registers each event method as an action or
	 * filter (respecting an optional leading arg-count and a 'filter' marker in
	 * the hook config), then registers the gtag snippet, cid-cookie, and
	 * queued-JS output hooks.
	 */
	public function __construct()
	{
		if ($this->get_tracking_id()) {
			if ($this->disable_tracking()) {
				return;
			}
			$this->get_event_hooks();
			foreach ($this->event_hooks as $key => $value) {
				if (is_array($value)) {
					$number_args = null;
					if (is_int($value[0])) {
						$number_args = $value[0];
						unset($value[0]);
						$value = array_values($value);
					}
					if ($value[0] == 'filter') {
						unset($value[0]);
						$value = array_values($value);
						foreach ($value as $single_hook) {
							if (array_key_exists($key, $this->event_settings)) {
								if (isset($number_args)) {
									add_filter($single_hook, array($this, $key), 10, $number_args);
								} else {
									add_filter($single_hook, array($this, $key));
								}
							}
						}
					} else {
						foreach ($value as $single_hook) {
							if (array_key_exists($key, $this->event_settings)) {
								if (isset($number_args)) {
									add_action($single_hook, array($this, $key), 10, $number_args);
								} else {
									add_action($single_hook, array($this, $key));
								}
							}
						}
					}
				} else {
					if (array_key_exists($key, $this->event_settings)) {
						add_action($value, array($this, $key));
					}
				}
			}
			add_action('wp_head', array($this, 'get_tracking_code'), 9);
			add_action('admin_head', array($this, 'get_special_tracking_code'), 9);
			add_action('login_head', array($this, 'get_tracking_code'), 9);
			add_action('woocommerce_before_shop_loop_item', array($this, 'product_impression'));
			/* Ensure the cookie exists / is fresh before anything else runs this request */
			add_action('init', function () {
				if (empty($_COOKIE['_ga4wp_cid']) && empty($_COOKIE['_ga'])) {
					$this->get_cid(); // triggers generation + cookie set
				}
			}, 1);
			add_action('wp_footer', array($this, 'ga4wp_add_this_script_footer'), 0);
			add_action('admin_footer', array($this, 'ga4wp_add_this_script_admin_footer'), 0);
			// Register this OUTSIDE the get_tracking_id() conditional in __construct,
			// so cleanup keeps running even if tracking is later disabled.
			add_action('init', function () {
				if (!wp_next_scheduled('ga4wp_cleanup_stale_events')) {
					wp_schedule_event(time(), 'hourly', 'ga4wp_cleanup_stale_events');
				}
			});

			add_action('ga4wp_cleanup_stale_events', function () {
				global $wpdb;
				$now = time();
				$stale_timeouts = $wpdb->get_col($wpdb->prepare(
					"SELECT option_name FROM {$wpdb->options}
         WHERE option_name LIKE %s AND option_value < %d",
					$wpdb->esc_like('_transient_timeout_ga4wp_evt_') . '%',
					$now
				));
				foreach ($stale_timeouts as $timeout_name) {
					$key = str_replace('_transient_timeout_', '', $timeout_name);
					delete_transient($key);
				}
			});
		}
	}
	/**
	 * Outputs the queued tracking JS in wp-admin, if admin tracking is enabled.
	 *
	 * Hooked to `admin_footer`; delegates to ga4wp_add_this_script_footer() when
	 * the "track admin pages" setting is on and a tracking ID is configured.
	 */
	public function ga4wp_add_this_script_admin_footer()
	{
		$tracking_options = get_option('ga4wp_track_settings');
		if (empty($tracking_options['track_admin_pages']) || ! $this->get_tracking_id()) {
		} else {
			$this->ga4wp_add_this_script_footer();
		}
	}
	/**
	 * Resolves and caches the GA4 measurement/property ID from plugin settings.
	 *
	 * Also populates $this->api_secret (Measurement Protocol secret) and, when
	 * an ID is found, $this->tracking_id and $this->cid, as a side effect.
	 *
	 * @return string|false The GA4 tracking/property ID, or false if none is configured.
	 */
	public function get_tracking_id()
	{
		if (get_option('ga4wp_auth_settings')) {
			$auth_settings = get_option('ga4wp_auth_settings');
			if (!empty($auth_settings['api_secret'])) {
				$this->api_secret = $auth_settings['api_secret'];
			} else {
				$measurement_key = get_option('measurement_key');
				if (!empty($measurement_key)) {
					$this->api_secret = $measurement_key;
				} else {
					$this->api_secret = false;
				}
			}
			if (isset($auth_settings['property_id'])) {
				$property = $auth_settings['property_id'];
				$pieces = explode('|', $property);
				$this->tracking_id = $pieces[1];
				$this->cid = $this->get_cid();
				return $pieces[1];
			} else {
				if (isset($auth_settings['tracking_id'])) {
					$this->tracking_id = $auth_settings['tracking_id'];
					$this->cid = $this->get_cid();
					return $auth_settings['tracking_id'];
				} else {
					return false;
				}
			}
		} else {
			return false;
		}
	}

	/**
	 * Echoes the front-end gtag.js snippet (and optional Facebook Pixel code) into <head>.
	 *
	 * Hooked to `wp_head` and `login_head`. Builds the base gtag snippet, then
	 * layers on Consent Mode v2 defaults, user/event custom dimensions, user-id,
	 * page-view and IP-anonymization options, debug mode, and a Google Ads
	 * config call, all driven by the `ga4wp_track_settings`/`ga4wp_advance_settings`
	 * options. Also outputs the Facebook Pixel base code and PageView event when
	 * enabled, and delegates to output_advanced_pixel_tags() for premium-only
	 * pixel snippets.
	 */
	public function get_tracking_code()
	{
		if ($this->disable_tracking()) {
			return;
		}
		$tracking_id = esc_js($this->get_tracking_id());
		$gtag_code_snippet = '<!-- Google Analytics Code Snippet By TrueAna --><script async src="https://www.googletagmanager.com/gtag/js?id=' . $tracking_id . '"></script>
		<script>
		  window.dataLayer = window.dataLayer || [];
		  function gtag(){dataLayer.push(arguments);}
		  gtag(\'js\', new Date());';
		$addon_values = array();
		if ($tracking_options = get_option('ga4wp_track_settings')) {
			if (isset($tracking_options['track_ga_consent']) && $tracking_options['track_ga_consent']) {
				$cookie_path   = esc_js(COOKIEPATH);
				$cookie_secure = is_ssl() ? '; Secure' : '';
				$gtag_code_snippet .= 'gtag("consent", "default", {
				ad_storage: "denied",
				ad_user_data: "denied",
				analytics_storage: "denied",
				functionality_storage: "denied",
				personalization_storage: "denied",
				security_storage: "granted",
				wait_for_update: 500,
				});
				gtag("set", "ads_data_redaction", true);
				gtag("set", "url_passthrough", true);
				/* ── GA4WP: persist consent state for server-side MP calls ── */
				(function(){
				var _cookiePath = "' . $cookie_path . '";
				var _cookieSecure = "' . $cookie_secure . '";

				function _ga4wpSetConsentCookie(state){
					var exp = new Date(Date.now() + 365*86400000).toUTCString();
					var val = encodeURIComponent(JSON.stringify(state));
					document.cookie = "_ga4wp_cs=" + val + "; expires=" + exp + "; path=" + _cookiePath + "; SameSite=Lax" + _cookieSecure;
				}

				/* Default state mirrors the gtag default above (all denied) */
				var _consentState = {
					analytics_storage: "denied",
					ad_storage: "denied",
					ad_user_data: "denied",
					ad_personalization: "denied"
				};
				_ga4wpSetConsentCookie(_consentState);

				window.dataLayer = window.dataLayer || [];
				var _origPush = window.dataLayer.push;

				window.dataLayer.push = function(){
					var args = arguments[0];
					if (args && args[0] === "consent" && args[1] === "update" && args[2]) {
					var s = args[2];
					var changed = false;
					["analytics_storage", "ad_storage", "ad_user_data", "ad_personalization"].forEach(function(key){
						if (typeof s[key] !== "undefined") {
						_consentState[key] = s[key]; // "granted" or "denied"
						changed = true;
						}
					});
					if (changed) {
						_ga4wpSetConsentCookie(_consentState);
					}
					}
					return _origPush.apply(window.dataLayer, arguments);
				};
				})();';
			}
			if (isset($tracking_options['track_interest']) && $tracking_options['track_interest']) {
				$gtag_code_snippet .= 'gtag("set", "allow_ad_personalization_signals", false);
				gtag("set", "allow_google_signals", false);';
			}
			if (isset($tracking_options['not_track_user_id']) && $tracking_options['not_track_user_id']) {
				// do nothing
			} else {
				if (is_user_logged_in()) {
					$user_id = esc_js(get_current_user_id());
					$addon_values[] = "'user_id':'{$user_id}'";
				}
			}
			if (isset($tracking_options['not_track_pageviews']) && $tracking_options['not_track_pageviews']) {
				$addon_values[] = "'send_page_view': false";
			}
			if (isset($tracking_options['enhanced_link_attribution']) && $tracking_options['enhanced_link_attribution']) {
				$addon_values[] = "'link_attribution': true";
			}
			if (isset($tracking_options['anonymize_ip']) && $tracking_options['anonymize_ip']) {
				$addon_values[] = "'anonymize_ip': true";
			}
		}
		$advance_options = get_option('ga4wp_advance_settings');
		$track_options_debug = get_option('ga4wp_track_settings');
		if ($track_options_debug && !empty($track_options_debug['google_analytics_debug_mode'])) {
			$addon_values[] = "'debug_mode': true";
		}
		if (!empty($advance_options['google_adword']) && !empty($advance_options['google_adword_code'])) {
			$gtag_code_snippet .= "gtag('config', '" . esc_js($advance_options['google_adword_code']) . "');";
		}
		$custom_dimension_values = $this->get_ga4wp_custom_dimension_values();
		foreach (($custom_dimension_values['event'] ?? []) as $key => $value) {
			$addon_values[] = "'" . esc_js($key) . "':" . wp_json_encode((string) $value);
		}
		// USER-scoped dimensions (e.g. ga4wp_user_role, ga4wp_user_id) are not
		// populated correctly when sent as event/config params — GA4 requires
		// them via a dedicated user_properties call, set before the config call
		// below so they're attached to the automatic pageview event too.
		$user_dimension_values = $custom_dimension_values['user'] ?? [];
		if (!empty($user_dimension_values)) {
			$user_prop_pairs = [];
			foreach ($user_dimension_values as $key => $value) {
				$user_prop_pairs[] = "'" . esc_js($key) . "':" . wp_json_encode((string) $value);
			}
			$gtag_code_snippet .= "gtag('set', 'user_properties', {" . implode(',', $user_prop_pairs) . "});";
		}
		if (!empty($addon_values) && is_array($addon_values)) {
			$addon_code = implode(',', $addon_values);
			$gtag_code_snippet .= "gtag('config', '{$tracking_id}', {{$addon_code}});";
		} else {
			$gtag_code_snippet .= "gtag('config', '{$tracking_id}');";
		}
		$gtag_code_snippet .= "</script> <!-- End of Google Analytics Code Snippet by TrueAna -->";
		$gtag_code_snippet = apply_filters('ga4wp_gtag_code_snippet', $gtag_code_snippet, $tracking_options, $advance_options);
		echo $gtag_code_snippet;
		if (!empty($advance_options['facebook_pixel']) && !empty($advance_options['facebook_pixel_code'])) {
			$fb_id = esc_js($advance_options['facebook_pixel_code']);
?>
			<!-- Facebook Pixel Code By GA4WP -->
			<script>
				! function(f, b, e, v, n, t, s) {
					if (f.fbq) return;
					n = f.fbq = function() {
						n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments)
					};
					if (!f._fbq) f._fbq = n;
					n.push = n;
					n.loaded = !0;
					n.version = '2.0';
					n.queue = [];
					t = b.createElement(e);
					t.async = !0;
					t.src = v;
					s = b.getElementsByTagName(e)[0];
					s.parentNode.insertBefore(t, s)
				}(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
				fbq('init', '<?php echo $fb_id; ?>');
				fbq('track', 'PageView');
			</script>
			<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?php echo $fb_id; ?>&ev=PageView&noscript=1" /></noscript>
			<!-- End Facebook Pixel Code -->
<?php
		}
		$this->output_advanced_pixel_tags($advance_options ?: []);
	}

	/**
	 * Echoes a second, admin-only gtag.js snippet into wp-admin's <head>.
	 *
	 * Hooked to `admin_head`. Mirrors get_tracking_code() but disables the
	 * automatic page-view event and skips Facebook Pixel / custom dimensions,
	 * since it exists only to let the "track admin pages" setting also fire a
	 * Google Ads config call. No-ops if admin-page tracking is off or no
	 * tracking ID is configured.
	 */
	public function get_special_tracking_code()
	{
		$tracking_options = get_option('ga4wp_track_settings');
		if (empty($tracking_options['track_admin_pages']) || ! $this->get_tracking_id()) {
			return;
		}
		$tracking_id = esc_js($this->get_tracking_id());
		$gtag_code_snippet = '<!-- Google Analytics Code Snippet for Admin Side By TrueAna --><script async src="https://www.googletagmanager.com/gtag/js?id=' . $tracking_id . '"></script>
			<script>
			  window.dataLayer = window.dataLayer || [];
			  function gtag(){dataLayer.push(arguments);}
			  gtag(\'js\', new Date());';
		$addon_values = array();
		if ($tracking_options = get_option('ga4wp_track_settings')) {
			$addon_values[] = "'send_page_view': false";
			if (isset($tracking_options['anonymize_ip']) && $tracking_options['anonymize_ip']) {
				$addon_values[] = "'anonymize_ip': true";
			}
		}
		$advance_options = get_option('ga4wp_advance_settings');
		$track_options_debug = get_option('ga4wp_track_settings');
		if ($track_options_debug && !empty($track_options_debug['google_analytics_debug_mode'])) {
			$addon_values[] = "'debug_mode': true";
		}
		if ($advance_options) {
			if (isset($advance_options['google_adword']) && $advance_options['google_adword'] && isset($advance_options['google_adword_code']) && ($advance_options['google_adword_code'] !== '')) {
				$gtag_code_snippet .= "gtag('config', '{$advance_options['google_adword_code']}');";
			}
		}
		if (!empty($addon_values) && is_array($addon_values)) {
			$addon_code = implode(',', $addon_values);
			$gtag_code_snippet .= "gtag('config', '{$tracking_id}', {{$addon_code}});";
		} else {
			$gtag_code_snippet .= "gtag('config', '{$tracking_id}');";
		}
		$advance_options = get_option('ga4wp_advance_settings');
		$gtag_code_snippet .= "</script> <!-- end of Google Analytics Code Snippetfor Admin by TrueAna -->";
		$gtag_code_snippet = apply_filters('ga4wp_admin_gtag_code_snippet', $gtag_code_snippet, $tracking_options, $advance_options);
		echo $gtag_code_snippet;
	}

	/**
	 * Loads the event-to-hook map and event settings from GA4WP_Settings (or saved options).
	 *
	 * Populates $this->event_hooks and $this->event_settings, which __construct()
	 * then uses to register the actual add_action()/add_filter() calls.
	 */
	public function get_event_hooks()
	{
		$settings = GA4WP_Settings::get_instance();
		$this->event_hooks = $settings->ga4wp_event_hooks;
		$this->event_settings = $settings->ga4wp_event_settings;
		if (get_option('ga4wp_event_settings')) {
			$this->event_settings = get_option('ga4wp_event_settings');
		}
	}

	/**
	 * Queues a single tracking event's JS snippet in its own transient for pickup on the next request.
	 *
	 * Storing each event as its own transient (rather than reading, appending to,
	 * and rewriting a shared value) removes the read-modify-write race condition
	 * that existed with a single shared transient — each set_transient() call
	 * here is atomic.
	 *
	 * @param string $ana_code The gtag()/fbq() JS snippet to queue for output.
	 */
	public function ga4wp_set_transient($ana_code)
	{
		$identity = is_user_logged_in() ? ('u_' . get_current_user_id()) : ('c_' . $this->get_cid());

		// uniqid with more_entropy avoids collisions even for two events queued
		// in the same microsecond by the same visitor.
		$key = 'ga4wp_evt_' . md5($identity) . '_' . str_replace('.', '', uniqid('', true));
		// Short TTL: these are meant to be flushed on the very next page load,
		// not to persist. 180s covers slow redirects (e.g. payment gateways)
		// without letting stale rows linger.
		set_transient($key, wp_json_encode(array('identity' => $identity, 'code' => $ana_code)), 300);
	}
	/**
	 * Returns a stable client ID for the current visitor.
	 *
	 * Persists the id in a cookie so the SAME id is used across requests (and
	 * across the anonymous → logged-in transition). Prefers the real GA client
	 * id from the `_ga` cookie, falls back to a previously-issued `_ga4wp_cid`
	 * cookie, and otherwise generates and persists a new UUIDv4-style id.
	 *
	 * @return string The client id. Never null/empty.
	 */
	private function get_cid()
	{
		if (!empty($this->cid)) {
			return $this->cid; // per-request cache
		}

		// 1) Real GA client id, if consent/cookie present
		if (isset($_COOKIE['_ga'])) {
			$ga_cookie_data = filter_var($_COOKIE['_ga'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$parts = explode('.', $ga_cookie_data);
			if (is_array($parts) && count($parts) > 3 && strlen($parts[2]) > 3 && strlen($parts[3]) > 3) {
				$this->cid = $parts[2] . '.' . $parts[3];
				return $this->cid;
			}
		}

		// 2) Our own previously-issued fallback cookie
		if (!empty($_COOKIE['_ga4wp_cid'])) {
			$this->cid = sanitize_text_field(wp_unslash($_COOKIE['_ga4wp_cid']));
			return $this->cid;
		}

		// 3) Generate + persist a new one
		$bytes = random_bytes(16);
		$bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
		$bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
		$this->cid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));

		$this->persist_cid_cookie($this->cid);
		$_COOKIE['_ga4wp_cid'] = $this->cid; // usable immediately for this request

		return $this->cid;
	}

	/**
	 * Persists the client id cookie as early as possible.
	 *
	 * Meant to be called from the 'init' hook (priority 1) so it runs before any
	 * output — calling this lazily from inside get_cid() risks "headers already
	 * sent" if get_cid() is first invoked mid-render. No-ops silently if headers
	 * were already sent.
	 *
	 * @param string $cid The client id value to store in the `_ga4wp_cid` cookie.
	 */
	public function persist_cid_cookie($cid)
	{
		if (headers_sent()) {
			return; // can't set now; next request will generate/find it again
		}
		setcookie(
			'_ga4wp_cid',
			$cid,
			time() + 2 * YEAR_IN_SECONDS,
			COOKIEPATH ?: '/',
			COOKIE_DOMAIN,
			is_ssl(),
			true // httponly
		);
	}
	/**
	 * Sweeps up any transient-queued tracking events for the current visitor and outputs them.
	 *
	 * Hooked to `wp_footer` (priority 0). Skips background/AJAX/cron/REST
	 * requests and cart-mutation POSTs (see ga4wp_is_cart_mutation_request())
	 * since those don't render a footer. Looks up transients keyed by the
	 * current user id and/or client id (so events queued anonymously before
	 * login are still picked up), merges their JS into self::$queued_js wrapped
	 * in try/catch, enqueues it as an inline script, then deletes the consumed
	 * option rows (both the transient and its timeout row) directly via $wpdb
	 * so they don't re-fire on the next page load.
	 */
	public function ga4wp_add_this_script_footer()
	{
		global $wpdb;
		$is_background = wp_doing_ajax()
			|| wp_doing_cron()
			|| (defined('REST_REQUEST') && REST_REQUEST)
			|| $this->ga4wp_is_cart_mutation_request();

		if ($is_background) {
			return;
		}
		$identities = array();
		if (is_user_logged_in()) {
			$identities[] = 'u_' . get_current_user_id();
			$identities[] = 'c_' . $this->get_cid(); // sweep pre-login events too
		} else {
			$identities[] = 'c_' . $this->get_cid();
		}

		$like_clauses = array();
		$params = array();
		foreach ($identities as $identity) {
			$like_clauses[] = 'option_name LIKE %s';
			$params[] = $wpdb->esc_like('_transient_ga4wp_evt_' . md5($identity)) . '%';
		}

		$sql = "SELECT option_id, option_name, option_value FROM {$wpdb->options}
            WHERE (" . implode(' OR ', $like_clauses) . ")
            ORDER BY option_id ASC";
		$rows = $wpdb->get_results($wpdb->prepare($sql, $params));
		if (empty($rows)) {
			return;
		}

		$ids_to_delete = array();
		$timeout_names_to_delete = array();

		foreach ($rows as $row) {
			$decoded = json_decode($row->option_value, true);
			if (is_array($decoded) && isset($decoded['code'])) {
				// each fragment so one bad event can't break the others.
				self::$queued_js[] = 'try{' . $decoded['code'] . '}catch(e){}';
			}
			$ids_to_delete[] = (int) $row->option_id;
			$timeout_names_to_delete[] = str_replace('_transient_', '_transient_timeout_', $row->option_name);
		}
		if (empty(self::$queued_js)) {
			return;
		}
		$code = implode("\n", self::$queued_js);
		self::$queued_js = array();
		if (!empty($code)) {
			wp_register_script('ga4wp-inline-js', false, array('jquery'), GA4WP_VERSION, true);
			wp_enqueue_script('ga4wp-inline-js'); // safe to call even if already enqueued
			wp_add_inline_script('ga4wp-inline-js', 'jQuery(function($){ ' . $code . ' });');
		}

		$id_placeholders = implode(',', array_fill(0, count($ids_to_delete), '%d'));
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_id IN ({$id_placeholders})", $ids_to_delete));

		if (!empty($timeout_names_to_delete)) {
			$name_placeholders = implode(',', array_fill(0, count($timeout_names_to_delete), '%s'));
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name IN ({$name_placeholders})", $timeout_names_to_delete));
		}
	}
	/**
	 * Determines whether the current request is a WooCommerce cart AJAX mutation.
	 *
	 * Used to skip firing footer tracking JS on requests that only update the
	 * cart (quantity change, coupon apply/remove) and don't render a full page.
	 *
	 * @return bool True if this is a POST request updating the cart or a coupon.
	 */
	private function ga4wp_is_cart_mutation_request()
	{
		if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
			return false;
		}
		return isset($_POST['update_cart'])
			|| isset($_POST['apply_coupon'])
			|| isset($_POST['remove_coupon'])
			|| isset($_POST['coupon_code'])
			|| isset($_POST['calc_shipping']);
	}
	/**
	 * Determines whether tracking should be suppressed for the current request.
	 *
	 * Tracking is disabled whenever no tracking ID is configured. It's also
	 * disabled for a logged-in user who can manage WooCommerce (shop
	 * managers/admins), unless the "track admin" setting explicitly opts them
	 * back in.
	 *
	 * @return bool True if tracking should be skipped.
	 */
	private function disable_tracking()
	{
		if ($this->get_tracking_id()) {
			$disable_tracking = false;
		} else {
			$disable_tracking = true;
		}
		$user_id = get_current_user_id();
		if ($user_id && user_can($user_id, 'manage_woocommerce')) {
			$tracking_options = get_option('ga4wp_track_settings');
			if (isset($tracking_options['track_admin']) && $tracking_options['track_admin'] && is_user_logged_in() && $this->get_tracking_id()) {
				$disable_tracking = false;
			} else {
				$settings = GA4WP_Settings::get_instance();
				$track_default_settings = $settings->ga4wp_tracking_settings;
				if (empty($tracking_options) && isset($track_default_settings['track_admin']) && $track_default_settings['track_admin'] && is_user_logged_in() && $this->get_tracking_id()) {
					$disable_tracking = false;
				} else {
					$disable_tracking = true;
				}
			}
		}
		return $disable_tracking;
	}

	/**
	 * Guards against firing the same page-view-triggered event twice for one navigation.
	 *
	 * Compares the current request URI's path against the previously stored
	 * `ga4wp_old_url` option (falling back to the HTTP referer when no stored
	 * URL exists yet), updating the option as a side effect whenever the path
	 * changes.
	 *
	 * @return bool|null True if this looks like a new page load and the event
	 *                    should fire, false if it looks like a repeat of the
	 *                    same page, or null in the edge case where neither a
	 *                    referer nor a stored URL differs from the current path.
	 */
	private function avoid_multi_trigger()
	{
		if (!isset($_SERVER['HTTP_REFERER'])) {
			if (isset($_SERVER['REQUEST_URI'])) {
				update_option('ga4wp_old_url', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
			}
			return true;
		}
		if (get_option('ga4wp_old_url')) {
			$ga4wp_old_url = get_option('ga4wp_old_url');
			if (($ga4wp_old_url !== parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
				update_option('ga4wp_old_url', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
				return true;
			} else {
				return false;
			}
		} else {
			if ((parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH) !== parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
				update_option('ga4wp_old_url', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
				return true;
			}
		}
	}

	/**
	 * Builds a comma-separated string of a product's variation/default attribute values.
	 *
	 * Accepts either a product ID or a WC_Product instance. Returns the
	 * specific variation's attribute values for a 'variation' product, or the
	 * parent's configured default attributes for a 'variable' product.
	 *
	 * @param int|\WC_Product $product Product ID or product object.
	 * @return string The attribute values joined by commas, or '' if the
	 *                product can't be resolved or has no relevant attributes.
	 */
	public function get_product_variation_attributes($product)
	{
		if (!$product instanceof \WC_Product) {
			$product = wc_get_product($product);
		}
		if (!$product) {
			return '';
		}
		$variant = '';
		if ('variation' === $product->get_type()) {
			$variant = implode(',', array_values($product->get_variation_attributes()));
		} elseif ('variable' === $product->get_type()) {
			global $woocommerce;
			$attributes = $product->get_default_attributes();
			$variant = implode(', ', array_values($attributes));
		}
		return $variant;
	}

	/**
	 * Returns the current request's User-Agent header, lower-cased.
	 *
	 * @return string The lower-cased User-Agent string, or '' if not present.
	 */
	public function ga4wp_get_user_agent()
	{
		return isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
	}

	/**
	 * Accumulates product-list impressions and fires a single `view_item_list`
	 * event once the current shop/category/tag/cart/related-products loop finishes.
	 *
	 * Hooked to `woocommerce_before_shop_loop_item`. No-ops unless the current
	 * page matches the "track product single/archive" settings and a global
	 * $product is available. Each call appends the current loop product to
	 * $this->loop_items (as an array when a Measurement Protocol api_secret is
	 * configured, or as a hand-built JS object string otherwise); when the
	 * loop's last item is reached (detected via $woocommerce_loop's
	 * per_page/total/columns/related-products counters) it sends the
	 * accumulated view_item_list event via making_remote_request() or queues
	 * the equivalent gtag() call as a transient.
	 */
	public function product_impression()
	{
		$tracking_options = get_option('ga4wp_track_settings');
		if (!((isset($tracking_options['product_single_track']) && is_product()) || (isset($tracking_options['product_archive_track']) && (is_shop() || is_product_taxonomy() || is_product_category() || is_product_tag() || is_cart())))) {
			return;
		}
		global $product, $woocommerce_loop, $woocommerce;
		if (!$product instanceof \WC_Product) {
			return;
		}
		if (!empty($this->api_secret)) {
			global $woocommerce_loop;
			$current_total = (($woocommerce_loop['current_page'] - 1) * $woocommerce_loop['per_page']) + ($woocommerce_loop['loop']);
			$item_list_name = $this->ga4wp_esc($this->get_list_name());
			$item_list_id = $this->ga4wp_esc(strtolower(str_replace(' ', '_', $item_list_name)));
			$item_data[] = $this->get_product_details($product->get_id());
			$loop_item = array(
				'item_id'        => $this->ga4wp_esc($item_data[0]['item_id']),
				'item_name'      => $this->ga4wp_esc($item_data[0]['item_name']),
				'currency'       => $this->ga4wp_esc(get_woocommerce_currency()),
				'item_list_id'   => $this->ga4wp_esc($item_list_id),
				'item_list_name' => $this->ga4wp_esc($item_list_name),
				'price'          => $this->ga4wp_esc($item_data[0]['price']),
				'quantity'       => $this->ga4wp_esc($item_data[0]['quantity']),
				'index'          => $this->ga4wp_esc($current_total),
			);
			foreach (array('item_category', 'item_category2', 'item_category3', 'item_category4', 'item_category5') as $cat_key) {
				if (! empty($item_data[0][$cat_key])) {
					$loop_item[$cat_key] = $item_data[0][$cat_key];
				}
			}
			$this->loop_items[] = $loop_item;
			if (isset($woocommerce_loop['per_page']) && !empty($woocommerce_loop['per_page'])) {
				if ($woocommerce_loop['per_page'] < $woocommerce_loop['total']) {
					if ($woocommerce_loop['loop'] == $woocommerce_loop['per_page']) {
						$this->data = $this->init_default_params();
						$this->data['events'][0] = array(
							'name' => 'view_item_list',
							'params' => array(
								'items' => $this->loop_items,
								'item_list_name' => $this->ga4wp_esc($item_list_name),
								'item_list_id' => $this->ga4wp_esc($item_list_id),
							),
						);
						$this->making_remote_request();
						$this->params = null;
						$this->loop_items = null;
						$this->data = null;
					} elseif ($current_total == $woocommerce_loop['total']) {
						$this->data = $this->init_default_params();
						$this->data['events'][0] = array(
							'name' => 'view_item_list',
							'params' => array(
								'items' => $this->loop_items,
								'item_list_name' => $this->ga4wp_esc($item_list_name),
								'item_list_id' => $this->ga4wp_esc($item_list_id),
							),
						);
						$this->making_remote_request();
						$this->params = null;
						$this->loop_items = null;
						$this->data = null;
					}
				} else {
					if ($woocommerce_loop['loop'] == $woocommerce_loop['total']) {
						$this->data = $this->init_default_params();
						$this->data['events'][0] = array(
							'name' => 'view_item_list',
							'params' => array(
								'items' => $this->loop_items,
								'item_list_name' => $this->ga4wp_esc($item_list_name),
								'item_list_id' => $this->ga4wp_esc($item_list_id),
							),
						);
						$this->making_remote_request();
						$this->params = null;
						$this->loop_items = null;
						$this->data = null;
					}
				}
			} else {
				if ($woocommerce_loop['loop'] == $woocommerce_loop['columns']) {
					$this->data = $this->init_default_params();
					$this->data['events'][0] = array(
						'name' => 'view_item_list',
						'params' => array(
							'items' => $this->loop_items,
							'item_list_name' => $this->ga4wp_esc($item_list_name),
							'item_list_id' => $this->ga4wp_esc($item_list_id),
						),
					);
					$this->making_remote_request();
					$this->params = null;
					$this->loop_items = null;
					$this->data = null;
				}
			}
		} else {
			global $product, $woocommerce, $woocommerce_loop;
			$current_total = (($woocommerce_loop['current_page'] - 1) * $woocommerce_loop['per_page']) + ($woocommerce_loop['loop']);
			$item_data[] = $this->get_product_details($product->get_id());
			$item_list_name = $this->get_list_name();
			$item_list_id = strtolower(str_replace(' ', '_', $item_list_name));
			$cat_js = '';
			foreach (array('item_category', 'item_category2', 'item_category3', 'item_category4', 'item_category5') as $cat_key) {
				if (! empty($item_data[0][$cat_key])) {
					$cat_js .= $cat_key . ': "' . $this->ga4wp_esc($item_data[0][$cat_key]) . '", ';
				}
			}
			$this->loop_items .= '{
				item_id: "' . $this->ga4wp_esc($item_data[0]['item_id']) . '",
				item_name: "' . $this->ga4wp_esc($item_data[0]['item_name']) . '",
				currency: "' . $this->ga4wp_esc(get_woocommerce_currency()) . '",
				' . $cat_js . '
				item_list_id: "' . $this->ga4wp_esc($item_list_id) . '",
				item_list_name: "' . $this->ga4wp_esc($item_list_name) . '",
				price: ' . $this->ga4wp_esc($item_data[0]['price']) . ',
				quantity: ' . $this->ga4wp_esc($item_data[0]['quantity']) . ',
				},';
			if (isset($woocommerce_loop['per_page']) && !empty($woocommerce_loop['per_page'])) {
				if ($woocommerce_loop['loop'] == $woocommerce_loop['per_page']) {
					$ga4wp_analytics_code = 'gtag("event", "view_item_list", {
						item_list_id: "' . $this->ga4wp_esc($item_list_id) . '",
						item_list_name: "' . $this->ga4wp_esc($item_list_name) . '",
						items: [' . $this->loop_items . ']
					});';
					$this->ga4wp_set_transient($ga4wp_analytics_code);
				} elseif ($current_total == $woocommerce_loop['total']) {
					$ga4wp_analytics_code = 'gtag("event", "view_item_list", {
						item_list_id: "' . $this->ga4wp_esc($item_list_id) . '",
						item_list_name: "' . $this->ga4wp_esc($item_list_name) . '",
						items: [' . $this->loop_items . ']
					});';
					$this->ga4wp_set_transient($ga4wp_analytics_code);
				} else {
					if ($woocommerce_loop['loop'] == $woocommerce_loop['total']) {
						$ga4wp_analytics_code = 'gtag("event", "view_item_list", {
							item_list_id: "' . $this->ga4wp_esc($item_list_id) . '",
							item_list_name: "' . $this->ga4wp_esc($item_list_name) . '",
							items: [' . $this->loop_items . ']
						});';
						$this->ga4wp_set_transient($ga4wp_analytics_code);
					}
				}
			} else {
				if ($woocommerce_loop['loop'] == $woocommerce_loop['columns']) {
					$ga4wp_analytics_code = 'gtag("event", "view_item_list", {
						item_list_id: "' . $this->ga4wp_esc($item_list_id) . '",
						item_list_name: "' . $this->ga4wp_esc($item_list_name) . '",
						items: [' . $this->loop_items . ']
					});';
					$this->ga4wp_set_transient($ga4wp_analytics_code);
				} else {
					if ($woocommerce_loop['name'] == 'related') {
						$related = wc_get_related_products($product->get_id());
						if (is_array($related)) {
							$related_count = count($related);
							if (($related_count > 0) && ($woocommerce_loop['loop'] == $related_count)) {
								$ga4wp_analytics_code = 'gtag("event", "view_item_list", {
									item_list_id: "' . $this->ga4wp_esc($item_list_id) . '",
									item_list_name: "' . $this->ga4wp_esc($item_list_name) . '",
									items: [' . $this->loop_items . ']
								});';
								$this->ga4wp_set_transient($ga4wp_analytics_code);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Determines a human-readable name for the current product listing context.
	 *
	 * @return string One of 'Search', 'Shop', 'Product Category', 'Product Tag',
	 *                'Archive', 'Product Page', 'cart page', or '' if none apply.
	 */
	public function get_list_name()
	{
		$list_name = '';
		if (is_search()) {
			$list_name = 'Search';
		} elseif (is_shop()) {
			$list_name = 'Shop';
		} elseif (is_product_category()) {
			$list_name = 'Product Category';
		} elseif (is_product_tag()) {
			$list_name = 'Product Tag';
		} elseif (is_archive()) {
			$list_name = 'Archive';
		} elseif (is_single()) {
			$list_name = 'Product Page';
		} elseif (is_cart()) {
			$list_name = 'cart page';
		}
		return $list_name;
	}

	/**
	 * Builds the base Measurement Protocol payload shared by all server-side events.
	 *
	 * Sets the client_id (and Consent Mode state, when enabled) on $this->data,
	 * plus the current user's `user_id` and the `ga4wp_user_id`/`ga4wp_user_role`
	 * custom dimension user_properties (subject to the "Do Not Track User ID"
	 * setting and premium availability).
	 *
	 * @param bool $track_user Whether to include the logged-in user's user_id
	 *                         and ga4wp_user_id dimension. Default true.
	 * @return array The (partially built) event payload, i.e. the new value of $this->data.
	 */
	private function init_default_params($track_user = true)
	{
		$this->data['client_id'] = $this->cid;
		$tracking_options = get_option('ga4wp_track_settings');
		if (!empty($tracking_options['track_ga_consent'])) {
			$this->data['consent'] = $this->ga4wp_get_consent_for_mp();
		}
		if (isset($tracking_options['not_track_user_id']) && $tracking_options['not_track_user_id']) {
			/* do nothing */
		} elseif ($track_user && (is_user_logged_in())) {
			$this->data['user_id'] = esc_js(get_current_user_id());

			/* ga4wp_user_id is registered in GA4 as a USER-scoped custom dimension —
			 * per the Measurement Protocol schema it must be sent via user_properties,
			 * not as an event param (events[].params is for EVENT-scoped dimensions). */
			$dim_value = $this->get_ga4wp_user_id_dimension_value(get_current_user_id());
			if ($dim_value !== '') {
				$this->data['user_properties']['ga4wp_user_id'] = array('value' => $dim_value);
			}
		}

		/* ga4wp_user_role applies to guests too (unlike ga4wp_user_id) and has no
		 * "Do Not Track" opt-out, so it's set independently of the branch above. */
		$role_value = $this->get_ga4wp_user_role_dimension_value();
		if ($role_value !== '') {
			$this->data['user_properties']['ga4wp_user_role'] = array('value' => $role_value);
		}

		return $this->data;
	}

	/**
	 * Builds the wp_safe_remote_request() args used to POST an event to the Measurement Protocol.
	 *
	 * Adds a `debug_mode` param to every queued event when GA4 debug mode is
	 * enabled in settings, then JSON-encodes $this->data as the request body.
	 *
	 * @return array The HTTP request arguments (method, timeout, headers, JSON body).
	 */
	protected function get_request_args()
	{
		if (function_exists($this->ga4wp_get_user_agent())) {
			$user_agent = $this->ga4wp_get_user_agent();
		} else {
			$user_agent = sprintf('%s/%s (WordPress/%s)', 'GA4WP', GA4WP_VERSION, $GLOBALS['wp_version']);
		}
		$track_options_mp = get_option('ga4wp_track_settings');
		if (!empty($track_options_mp['google_analytics_debug_mode'])) {
			if (isset($this->data['events']) && is_array($this->data['events'])) {
				foreach ($this->data['events'] as $group => &$data) {
					if (isset($data['params']) && is_array($data['params'])) {
						$data['params']['debug_mode'] = 1;
					} else {
						$data['params']['debug_mode'] = 1;
					}
				}
			}
		}
		$args = array(
			'method' => 'POST',
			'timeout' => MINUTE_IN_SECONDS,
			'redirection' => 0,
			'sslverify' => true,
			'user-agent' => $user_agent,
			'body' => json_encode($this->data),
		);
		return $args;
	}

	/**
	 * Builds the GA4 "item" array for a single product, for use in event params.
	 *
	 * @param int|string $product_id Product (or variation) ID to describe.
	 * @param int        $quantity   Quantity to report; negative values are made
	 *                               positive and 0 is treated as 1.
	 * @param int        $i          Unused positional index (kept for call-site compatibility).
	 * @return array|string The item array (item_id, item_name, quantity,
	 *                       item_variant, price, item_category[2-5], index —
	 *                       empty values stripped), or '' if $product_id doesn't
	 *                       resolve to a WC_Product.
	 */
	private function get_product_details($product_id, $quantity = 1, $i = 1)
	{
		global $woocommerce_loop;
		$product = wc_get_product($product_id);
		if ($product instanceof \WC_Product) {
			$product_identifier = ($sku = $product->get_sku()) ? $sku : $product_id;
			$categories = wc_get_product_terms($product_id, 'product_cat', array('orderby' => 'parent', 'order' => 'DESC'));
			if (is_array($categories) && !empty($categories)) {
				foreach ($categories as $j => $category) {
					if (!is_object($category) || empty($category->name)) continue;
					if ($j === 0) {
						$item['item_category'] = $this->ga4wp_esc($category->name);
					} elseif ($j < 5) {
						// GA4 supports item_category2 through item_category5
						$item['item_category' . ($j + 1)] = $this->ga4wp_esc($category->name);
					}
				}
			}
			if ($quantity < 0) {
				$quantity = $quantity * (-1);
			} elseif ($quantity == 0) {
				$quantity = 1;
			}
			$item['item_id'] = $this->ga4wp_esc(strval($product_identifier));
			$item['item_name'] = $this->ga4wp_esc($product->get_title());
			$item['quantity'] = $this->ga4wp_esc($quantity);
			$item['item_variant'] = $this->ga4wp_esc($this->get_product_variation_attributes($product));
			$item['price'] = $this->ga4wp_esc($product->get_price());
			$item['index'] = $this->ga4wp_esc(isset($woocommerce_loop['loop']) ? $woocommerce_loop['loop'] : '');
			foreach ($item as $key => $value) {
				if (empty($value)) {
					unset($item[$key]);
				}
			}
			return $item;
		}
		return '';
	}

	/**
	 * Returns true when it is safe to send a Measurement Protocol event.
	 *
	 * When Google Consent Mode v2 is active the client-side gtag wrapper writes
	 * a `_ga4wp_cs` cookie: "1" = analytics_storage granted, "0" = denied.
	 * If the cookie is absent (first visit before JS has run) we default to
	 * denied, matching the "denied" default set in the gtag consent snippet.
	 * When consent mode is disabled the check is skipped entirely.
	 *
	 * @return bool True if the event may be sent.
	 */
	private function is_analytics_consent_granted()
	{
		$tracking_options = get_option('ga4wp_track_settings') ?: [];
		if (empty($tracking_options['track_ga_consent'])) {
			return true; // Consent mode not active — send freely
		}
		if (empty($_COOKIE['_ga4wp_cs'])) {
			return false; // Cookie not yet set (first visit) — default denied, matches gtag default
		}
		$decoded = json_decode(wp_unslash($_COOKIE['_ga4wp_cs']), true);
		if (!is_array($decoded) || !isset($decoded['analytics_storage'])) {
			return false; // Malformed/unexpected cookie — fail closed
		}
		return $decoded['analytics_storage'] === 'granted';
	}
	/**
	 * Builds the Measurement Protocol `consent` object from the visitor's stored consent-mode cookie.
	 *
	 * Reads the `_ga4wp_cs` cookie (written client-side by the Consent Mode
	 * wrapper in get_tracking_code()), falling back to "denied" for both fields
	 * when the cookie is absent or malformed.
	 *
	 * @return array The consent object with 'ad_user_data'/'ad_personalization'
	 *               keys set to 'GRANTED' or 'DENIED', matching the
	 *               Measurement Protocol schema.
	 */
	private function ga4wp_get_consent_for_mp()
	{
		$default = array(
			'analytics_storage'   => 'denied',
			'ad_storage'          => 'denied',
			'ad_user_data'        => 'denied',
			'ad_personalization'  => 'denied',
		);

		if (empty($_COOKIE['_ga4wp_cs'])) {
			$state = $default;
		} else {
			$decoded = json_decode(wp_unslash($_COOKIE['_ga4wp_cs']), true);
			$state   = is_array($decoded) ? array_merge($default, $decoded) : $default;
		}

		// MP consent object only supports these two fields, uppercase enum values.
		return array(
			'ad_user_data'       => ($state['ad_user_data'] === 'granted') ? 'GRANTED' : 'DENIED',
			'ad_personalization' => ($state['ad_personalization'] === 'granted') ? 'GRANTED' : 'DENIED',
		);
	}
	/**
	 * Sends the currently-built event payload to the GA4 Measurement Protocol, with retries.
	 *
	 * No-ops if Consent Mode analytics_storage isn't granted (see
	 * is_analytics_consent_granted()), or if the tracking ID isn't a GA4
	 * ("G-...") ID with an API secret configured. Retries the POST up to 3
	 * times until a response with a < 300 status code is received.
	 */
	private function making_remote_request()
	{
		/* Respect Google Consent Mode v2: skip MP call if analytics_storage denied */
		if (! $this->is_analytics_consent_granted()) {
			return;
		}

		$remote_url = null;
		if ((strpos((string) $this->tracking_id, 'G') !== false) && !empty($this->api_secret)) {

			$remote_url = 'https://www.google-analytics.com/mp/collect?measurement_id=' . $this->tracking_id . '&api_secret=' . $this->api_secret;
		}
		if (!empty($remote_url)) {
			$args = $this->get_request_args();
			$response = null;
			$i = 0;
			while (1) {
				$response = wp_safe_remote_request(untrailingslashit($remote_url), $args);
				if (!empty($response) && is_array($response)) {
					if (isset($response['response']['code']) && ((int) $response['response']['code'] < 300)) {
						break;
					}
				}
				if ($i > 1) {
					break;
				}
				$i++;
			}
		}
	}

	/**
	 * Records a `login` event, either via Measurement Protocol or a queued gtag() call.
	 *
	 * The `method` param reflects where the login happened: 'checkout' or
	 * 'myaccount' for WooCommerce sites, 'wplogin' otherwise.
	 *
	 * @param string  $user_login The username that logged in (unused; kept for hook signature compatibility).
	 * @param WP_User $user       The logged-in user object (unused; kept for hook signature compatibility).
	 */
	public function user_login($user_login, $user)
	{

		if (!empty($this->api_secret)) {
			if (class_exists('WooCommerce')) {
				if (is_checkout()) {
					$this->data['events'][0] = array(
						'name' => 'login',
						'params' => array(
							'method' => 'checkout',
						),
					);
				} else {
					$this->data['events'][0] = array(
						'name' => 'login',
						'params' => array(
							'method' => 'myaccount',
						),
					);
				}
			} else {
				$this->data['events'][0] = array(
					'name' => 'login',
					'params' => array(
						'method' => 'wplogin',
					),
				);
			}
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			if (class_exists('WooCommerce')) {
				if (is_checkout()) {
					$ga4wp_analytics_code = 'gtag("event", "login", {
								method: "checkout"
							});';
				} else {
					$ga4wp_analytics_code = 'gtag("event", "login", {
								method: "myaccount"
							});';
				}
			} else {
				$ga4wp_analytics_code = 'gtag("event", "login", {
							method: "myaccount"
						});';
			}
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
	}

	/**
	 * Records a `logout` event, either via Measurement Protocol or a queued gtag() call.
	 */
	public function user_logout()
	{
		if (!empty($this->api_secret)) {
			$this->data = $this->init_default_params();
			$this->data['events'][0] = array(
				'name' => 'logout',
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			$ga4wp_analytics_code = 'gtag("event", "logout", {});';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
	}

	/**
	 * Records a `viewed_signup_form` event, either via Measurement Protocol or a queued gtag() call.
	 */
	public function viewed_signup_form()
	{
		/* if (!$this->avoid_multi_trigger()) {
			return;
		} */
		if (!empty($this->api_secret)) {
			$this->data = $this->init_default_params();
			$this->data['events'][0] = array(
				'name' => 'viewed_signup_form',
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			$ga4wp_analytics_code = 'gtag("event", "viewed_signup_form", {});';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
	}

	/**
	 * Records a `sign_up` event, either via Measurement Protocol or a queued gtag() call.
	 *
	 * The `method` param is 'checkout' or 'myaccount' for WooCommerce sites, or
	 * 'wp-signup' otherwise.
	 */
	public function user_signup()
	{
		if (!empty($this->api_secret)) {
			$this->data = $this->init_default_params();
			if (class_exists('WooCommerce')) {
				if (is_checkout()) {
					$this->data['events'][0] = array(
						'name' => 'sign_up',
						'params' => array(
							'method' => 'checkout',
						),
					);
				} else {
					$this->data['events'][0] = array(
						'name' => 'sign_up',
						'params' => array(
							'method' => 'myaccount',
						),
					);
				}
			} else {
				$this->data['events'][0] = array(
					'name' => 'sign_up',
					'params' => array(
						'method' => 'wp-signup',
					),
				);
			}
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			if (class_exists('WooCommerce')) {
				if (is_checkout()) {
					$ga4wp_analytics_code = 'gtag("event", "sign_up", { method: "checkout"});';
					$this->ga4wp_set_transient($ga4wp_analytics_code);
				} else {
					$ga4wp_analytics_code = 'gtag("event", "sign_up", { method: "myaccount"});';
					$this->ga4wp_set_transient($ga4wp_analytics_code);
				}
			} else {
				$ga4wp_analytics_code = 'gtag("event", "sign_up", { method: "myaccount"});';
				$this->ga4wp_set_transient($ga4wp_analytics_code);
			}
		}
	}

	/**
	 * Records a `viewed_account` event, either via Measurement Protocol or a queued gtag() call.
	 */
	public function viewed_account()
	{
		/* if (!$this->avoid_multi_trigger()) {
			return;
		} */
		if (!empty($this->api_secret)) {
			$this->data = $this->init_default_params();
			$this->data['events'][0] = array(
				'name' => 'viewed_account',
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			$ga4wp_analytics_code = 'gtag("event", "viewed_account", {});';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
	}

	/**
	 * Records a `viewed_order` event, either via Measurement Protocol or a queued gtag() call.
	 *
	 * @param int $order_id The viewed order's ID (unused; kept for hook signature compatibility).
	 */
	public function viewed_order($order_id)
	{
		/* if (!$this->avoid_multi_trigger()) {
			return;
		} */
		if (!empty($this->api_secret)) {
			$this->data = $this->init_default_params();
			$this->data['events'][0] = array(
				'name' => 'viewed_order',
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			$ga4wp_analytics_code = 'gtag("event", "viewed_order", {});';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
	}

	/**
	 * Records a `changed_password` event, either via Measurement Protocol or a queued gtag() call.
	 */
	public function changed_password()
	{
		if (!empty($this->api_secret)) {
			$this->data = $this->init_default_params();
			$this->data['events'][0] = array(
				'name' => 'changed_password',
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			$ga4wp_analytics_code = 'gtag("event", "changed_password", {});';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
	}

	/**
	 * Records a `wrote_review` event when a newly posted comment is a product review.
	 *
	 * @param int $comment_ID The ID of the comment that was just saved.
	 */
	public function wrote_review($comment_ID)
	{
		$comment = get_comment($comment_ID);
		$post_ID = $comment->comment_post_ID;
		$type = get_post_type($post_ID);
		if ('product' == $type) {
			if (!empty($this->api_secret)) {
				$this->data = $this->init_default_params();
				$this->data['events'][0] = array(
					'name' => 'wrote_review',
				);
				$this->making_remote_request();
				$this->params = null;
				$this->data = null;
			} else {
				$ga4wp_analytics_code = 'gtag("event", "wrote_review", {});';
				$this->ga4wp_set_transient($ga4wp_analytics_code);
			}
		}
	}

	/**
	 * Records a `commented` event when a newly posted comment is on a blog post.
	 *
	 * @param int $comment_ID The ID of the comment that was just saved.
	 */
	public function commented($comment_ID)
	{
		$comment = get_comment($comment_ID);
		$post_ID = $comment->comment_post_ID;
		$type = get_post_type($post_ID);
		if ('post' == $type) {
			if (!empty($this->api_secret)) {
				$this->data = $this->init_default_params();
				$this->data['events'][0] = array(
					'name' => 'commented',
				);
				$this->making_remote_request();
				$this->params = null;
				$this->data = null;
			} else {
				$ga4wp_analytics_code = 'gtag("event", "commented", {});';
				$this->ga4wp_set_transient($ga4wp_analytics_code);
			}
		}
	}

	/**
	 * Records a `viewed_shop` event when the main shop archive page is viewed.
	 *
	 * Guarded by avoid_multi_trigger() so it only fires once per navigation to
	 * the shop page.
	 */
	public function viewed_shop()
	{
		if (class_exists('WooCommerce')) {
			if (is_shop()) {
				if (!$this->avoid_multi_trigger()) {
					return;
				}
				if (!empty($this->api_secret)) {
					$this->data = $this->init_default_params();
					$this->data['events'][0] = array(
						'name' => 'viewed_shop',
					);
					$this->making_remote_request();
					$this->params = null;
					$this->data = null;
				} else {
					$ga4wp_analytics_code = 'gtag("event", "viewed_shop", {});';
					$this->ga4wp_set_transient($ga4wp_analytics_code);
				}
			}
		}
	}

	/**
	 * Records a `view_cart` event with the current cart's items and total value.
	 *
	 * Guarded by avoid_multi_trigger(). Sent via Measurement Protocol when an
	 * api_secret is configured, otherwise queued as a gtag() call.
	 */
	public function viewed_cart()
	{
		if (is_cart()) {
			if (!$this->avoid_multi_trigger()) {
				return;
			}
			$items_data = array();
			foreach (WC()->cart->get_cart() as $item) {
				$i = 0;
				$i++;
				$product_id = !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
				$items_data[] = $this->get_product_details($product_id, $item['quantity'], $i);
			}
			$cart_value = floatval(preg_replace('#[^\d.]#', '', WC()->cart->get_cart_contents_total()));
			$params = array(
				'currency' => $this->ga4wp_esc(get_woocommerce_currency()),
				'items' => $items_data,
				'value' => $this->ga4wp_esc($cart_value),
			);
			foreach ($params as $key => $value) {
				if (empty($value)) {
					unset($params[$key]);
				}
			}
			if (!empty($this->api_secret)) {
				$this->data = $this->init_default_params();
				$this->data['events'][0] = array(
					'name' => 'view_cart',
					'params' => $params,
				);
				$this->making_remote_request();
				$this->params = null;
				$this->data = null;
			} else {
				$params = json_encode($params);
				$ga4wp_analytics_code = 'gtag("event", "view_cart", ' . $params . ');';
				$this->ga4wp_set_transient($ga4wp_analytics_code);
			}
		}
	}

	/**
	 * Records a `view_item` event for the product being viewed, plus optional pixel tracking.
	 *
	 * Guarded by avoid_multi_trigger(). Also queues a Facebook Pixel
	 * `ViewContent` event when enabled, and delegates to
	 * track_product_view_pixels() for premium-only pixel integrations.
	 */
	public function viewed_product()
	{
		if (!$this->avoid_multi_trigger()) {
			return;
		}
		$product_id = get_the_ID();
		$product = wc_get_product($product_id);
		$items_data[] = $this->get_product_details($product_id);
		$this->data = $this->init_default_params();
		$item_data[] = $this->get_product_details($product_id);
		if (!empty($this->api_secret)) {
			$this->data['events'][0] = array(
				'name' => 'view_item',
				'params' => array(
					'currency' => $this->ga4wp_esc(get_woocommerce_currency()),
					'items' => $item_data,
					'value' => $this->ga4wp_esc($product->get_price()),
				),
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			$items_data = json_encode($items_data);
			$ga4wp_analytics_code = 'gtag("event", "view_item", {
				currency: "' . $this->ga4wp_esc(get_woocommerce_currency()) . '",
				value:' . $this->ga4wp_esc($product->get_price()) . ',
				items:' . $items_data . '
			});';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
		$wc_currency     = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '';
		$wc_price        = floor($product->get_price());
		$advance_options = get_option('ga4wp_advance_settings');
		if (!empty($advance_options['facebook_pixel']) && !empty($advance_options['facebook_pixel_code'])) {
			$this->ga4wp_set_transient("fbq('track', 'ViewContent',{
				value: " . $this->ga4wp_esc($wc_price) . ",
				currency: '" . $this->ga4wp_esc($wc_currency) . "',
				content_ids: " . $this->ga4wp_esc($product_id) . ",
				content_type: 'product'
			});");
		}
		$this->track_product_view_pixels($product_id, $wc_price, $wc_currency);
	}

	/**
	 * Records an `add_to_cart` event for the item just added, plus optional pixel tracking.
	 *
	 * Hooked to WooCommerce's add-to-cart action. No-ops if the cart
	 * item/product can't be resolved. Also queues a Facebook Pixel `AddToCart`
	 * event when enabled, and delegates to track_add_to_cart_pixels() for
	 * premium-only pixel integrations.
	 *
	 * @param string $cart_item_key The WC cart item key of the item that was added.
	 */
	public function added_product($cart_item_key)
	{
		$item = WC()->cart->cart_contents[$cart_item_key];
		$product_id = !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
		if (!$product_id) {
			return;
		}
		$product = wc_get_product($product_id);
		if (!empty($this->api_secret)) {
			$this->data = $this->init_default_params();
			$item_data[] = $this->get_product_details($product_id);
			$this->data['events'][0] = array(
				'name' => 'add_to_cart',
				'params' => array(
					'currency' => $this->ga4wp_esc(get_woocommerce_currency()),
					'items' => $item_data,
					'value' => $this->ga4wp_esc($product->get_price()),
				),
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			$items_data[] = $this->get_product_details($product_id);
			$items_data = json_encode($items_data);
			$ga4wp_analytics_code = 'gtag("event", "add_to_cart", {
				currency: "' . $this->ga4wp_esc(get_woocommerce_currency()) . '",
				value:' . $this->ga4wp_esc($product->get_price()) . ',
				items:' . $items_data . '
			});';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
		$wc_currency     = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '';
		$wc_price        = floor($product->get_price());
		$advance_options = get_option('ga4wp_advance_settings');
		if (!empty($advance_options['facebook_pixel']) && !empty($advance_options['facebook_pixel_code'])) {
			$this->ga4wp_set_transient("fbq('track', 'AddToCart',{
				value: " . $this->ga4wp_esc($wc_price) . ",
				currency:'" . $this->ga4wp_esc($wc_currency) . "',
				content_ids: " . $this->ga4wp_esc($product_id) . ",
				content_type: 'product'
			});");
		}
		$this->track_add_to_cart_pixels($product_id, $wc_price, $wc_currency);
	}

	/**
	 * Records a `remove_from_cart` event for the item just removed from the cart.
	 *
	 * @param string $cart_item_key The WC cart item key of the item that was removed.
	 */
	public function removed_product($cart_item_key)
	{
		if (isset(WC()->cart->cart_contents[$cart_item_key])) {
			$item = WC()->cart->cart_contents[$cart_item_key];
			$product_id = !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
			if (!$product_id) {
				return;
			}
			$product = wc_get_product($product_id);
			if (!empty($this->api_secret)) {
				$this->data = $this->init_default_params();
				$item_data[] = $this->get_product_details($product_id);
				$this->data['events'][0] = array(
					'name' => 'remove_from_cart',
					'params' => array(
						'currency' => get_woocommerce_currency(),
						'items' => $item_data,
						'value' => $product->get_price(),
					),
				);
				$this->making_remote_request();
				$this->params = null;
				$this->data = null;
			} else {
				$items_data[] = $this->get_product_details($product_id);
				$items_data = json_encode($items_data);
				$ga4wp_analytics_code = 'gtag("event", "remove_from_cart", {
					currency: "' . get_woocommerce_currency() . '",
					value:' . $product->get_price() . ',
					items:' . $items_data . '
				});';
				$this->ga4wp_set_transient($ga4wp_analytics_code);
			}
		}
	}

	/**
	 * Records a `changed_cart_quantity` event when a cart line item's quantity is updated.
	 *
	 * @param string $cart_item_key The WC cart item key whose quantity changed.
	 * @param int    $quantity      The new quantity (unused; kept for hook signature compatibility).
	 */
	public function changed_quantity($cart_item_key, $quantity)
	{
		if (isset(WC()->cart->cart_contents[$cart_item_key])) {
			$item = WC()->cart->cart_contents[$cart_item_key];
			$product_id = !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
			if (!$product_id) {
				return;
			}
			$product = wc_get_product($product_id);
			if (!empty($this->api_secret)) {
				$this->data = $this->init_default_params();
				$this->data['events'][0] = array(
					'name' => 'changed_cart_quantity',
				);
				$this->making_remote_request();
				$this->params = null;
				$this->data = null;
			} else {
				$ga4wp_analytics_code = 'gtag("event", "changed_cart_quantity", {});';
				$this->ga4wp_set_transient($ga4wp_analytics_code);
			}
		}
	}

	/**
	 * Records an `estimated_shipping` event, either via Measurement Protocol or a queued gtag() call.
	 */
	public function estimated_shipping()
	{
		if (!empty($this->api_secret)) {
			$this->data = $this->init_default_params();
			$this->data['events'][0] = array(
				'name' => 'estimated_shipping',
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			$ga4wp_analytics_code = 'gtag("event", "estimated_shipping", {});';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
	}

	/**
	 * Records a `user_login_errors` event and passes the error message through unchanged.
	 *
	 * Intended as a filter callback on the login error message.
	 *
	 * @param string $error_msg The login error message being filtered.
	 * @return string The same $error_msg that was passed in.
	 */
	public function user_login_errors($error_msg)
	{
		if (!empty($this->api_secret)) {
			$this->data = $this->init_default_params();
			$this->data['events'][0] = array(
				'name' => 'user_login_errors',
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			$ga4wp_analytics_code = 'gtag("event", "user_login_errors", {});';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
		return $error_msg;
	}

	/**
	 * Records a `lost_password` event and passes the message through unchanged.
	 *
	 * Intended as a filter callback on the lost-password confirmation message.
	 *
	 * @param string $lost_password_msg The message being filtered.
	 * @return string The same $lost_password_msg that was passed in.
	 */
	public function lost_password($lost_password_msg)
	{
		if (!empty($this->api_secret)) {
			$this->data = $this->init_default_params();
			$this->data['events'][0] = array(
				'name' => 'lost_password',
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			$ga4wp_analytics_code = 'gtag("event", "lost_password", {});';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
		return $lost_password_msg;
	}

	/**
	 * Records a `wrong_coupon_applied` event and passes the error message through unchanged.
	 *
	 * Intended as a filter callback on WooCommerce's coupon error message.
	 *
	 * @param string $error_msg The coupon error message being filtered.
	 * @param int    $err_code  The WooCommerce coupon error code (unused; kept for hook signature compatibility).
	 * @param mixed  $coupon    The coupon involved (unused; kept for hook signature compatibility).
	 * @return string The same $error_msg that was passed in.
	 */
	public function wrong_coupon_applied($error_msg, $err_code, $coupon)
	{
		if (!empty($this->api_secret)) {
			$this->data = $this->init_default_params();
			$this->data['events'][0] = array(
				'name' => 'wrong_coupon_applied',
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			$ga4wp_analytics_code = 'gtag("event", "wrong_coupon_applied", {});';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
		return $error_msg;
	}

	/**
	 * Records an `applied_coupon` event when a coupon is successfully applied to the cart.
	 *
	 * @param string $coupon_code The applied coupon's code (unused; kept for hook signature compatibility).
	 */
	public function applied_coupon($coupon_code)
	{
		if (!empty($this->api_secret)) {
			$this->data = $this->init_default_params();
			$this->data['events'][0] = array(
				'name' => 'applied_coupon',
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			$ga4wp_analytics_code = 'gtag("event", "applied_coupon", {});';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
	}

	/**
	 * Records a `removed_coupon` event when a coupon is removed from the cart.
	 *
	 * @param string $coupon_code The removed coupon's code (unused; kept for hook signature compatibility).
	 */
	public function removed_coupon($coupon_code)
	{
		if (!empty($this->api_secret)) {
			$this->data = $this->init_default_params();
			$this->data['events'][0] = array(
				'name' => 'removed_coupon',
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			$ga4wp_analytics_code = 'gtag("event", "removed_coupon", {});';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
	}

	/**
	 * Records a `begin_checkout` event with the current cart's items, coupons, and value.
	 *
	 * Guarded by avoid_multi_trigger(). Sent via Measurement Protocol when an
	 * api_secret is configured, otherwise queued as a gtag() call.
	 */
	public function begin_checkout()
	{
		if (!$this->avoid_multi_trigger()) {
			return;
		}
		if (!empty($this->api_secret)) {
			foreach (WC()->cart->get_cart() as $item) {
				$i = 0;
				$i++;
				$product_id = ! empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
				$items_data[] = $this->get_product_details($product_id, $item['quantity'], $i);
			}
			$this->data = $this->init_default_params();
			$checkout_value = floatval(preg_replace('#[^\d.,]#', '', WC()->cart->get_cart_total()));
			$applied_coupons = WC()->cart->get_applied_coupons();
			$coupon_code = '';
			foreach ($applied_coupons as $coupon) {
				$coupon_code .= $coupon . '/';
			}
			if (!empty($coupon_code)) {
				$coupon_code = trim($coupon_code, '/');
			}
			$params = array(
				'coupon' => $this->ga4wp_esc($coupon_code),
				'currency' => $this->ga4wp_esc(get_woocommerce_currency()),
				'items' => $items_data,
				'value' => $this->ga4wp_esc($checkout_value),
			);
			foreach ($params as $key => $value) {
				if (empty($value)) {
					unset($params[$key]);
				}
			}
			$this->data['events'][0] = array(
				'name' => 'begin_checkout',
				'params' => $params,
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			foreach (WC()->cart->get_cart() as $item) {
				$i = 0;
				$i++;
				$product_id = !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
				$items_data[] = $this->get_product_details($product_id, $item['quantity'], $i);
			}
			$checkout_value = floatval(preg_replace('#[^\d.,]#', '', WC()->cart->get_cart_contents_total()));
			$applied_coupons = WC()->cart->get_applied_coupons();
			$coupon_code = '';
			foreach ($applied_coupons as $coupon) {
				$coupon_code .= $coupon . '/';
			}
			if (!empty($coupon_code)) {
				$coupon_code = trim($coupon_code, '/');
			}
			$params = array(
				'coupon' => $this->ga4wp_esc($coupon_code),
				'currency' => $this->ga4wp_esc(get_woocommerce_currency()),
				'items' => $items_data,
				'value' => $this->ga4wp_esc($checkout_value),
			);
			foreach ($params as $key => $value) {
				if (empty($value)) {
					unset($params[$key]);
				}
			}
			$params = json_encode($params);
			$ga4wp_analytics_code = 'gtag("event", "begin_checkout",' . $params . ');';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
	}

	/**
	 * Queues client-side JS that fires a `filled_checkout_form` event once required checkout fields are filled.
	 *
	 * The generated JS watches billing field changes and validates email/phone
	 * formats, plus listens for the `checkout_place_order` event as a fallback,
	 * so the event fires at most once per checkout attempt.
	 */
	public function filled_checkout_form()
	{
		$live_js = '';
		$option_name = is_user_logged_in() ? 'Registered User' : 'Guest';
		$live_js = "gtag( 'event','filled_checkout_form');";
		$added_js = "
			var user_info_fired = false;
			var all_filled = true;
			jQuery( 'form.checkout' ).on( 'change', 'input', function() {
				if(!user_info_fired){
					jQuery('input[id|=\'billing\']').each(function(){
						if (!all_filled){
							return;
						}
						if (!jQuery(this).val()){
							if(jQuery(this).attr('type')=='email'){
								if ( !isEmail( this.value )){
									all_filled = false;
									return;
								}
							}
							if(jQuery(this).attr('type')=='phone'){
								if ( !isPhone( this.value )){
									all_filled = false;
									return;
								}
							}
							if(!(jQuery(this).attr('id').includes('company') || jQuery(this).attr('id').includes('address_2'))){
								all_filled = false;
								return;
							}
						}
					});
					if(all_filled){
						user_info_fired = true;
						{$live_js}
					}
				}
			});
			function isEmail(email) {
							var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
							return regex.test(email);
			}
			function isPhone(phone) {
							var regex = /[0-9\-\(\)\s]+/;
							return regex.test(phone);
			}
			jQuery( 'form.checkout' ).on( 'checkout_place_order', function() { if ( !user_info_fired ) {user_info_fired = true;{$live_js}}});";
		if (!empty($added_js)) {
			$this->ga4wp_set_transient($added_js);
		}
	}

	/**
	 * Queues client-side JS that fires an `add_shipping_info` event when the
	 * shopper changes their selected shipping method at checkout.
	 *
	 * Builds the current cart's items/coupon/value into a JS helper function,
	 * then emits JS that calls it on shipping-method change and again as a
	 * fallback on `checkout_place_order` if no change was ever tracked.
	 */
	public function added_shipping_method()
	{
		if (WC()->cart->get_cart_contents_count() > 0) {
			foreach (WC()->cart->get_cart() as $item) {
				$i = 0;
				$i++;
				$product_id = !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
				$items_data[] = $this->get_product_details($product_id, $item['quantity'], $i);
			}
		} else {
			$items_data = array();
		}
		$applied_coupons = WC()->cart->get_applied_coupons();
		$coupon_code = '';
		foreach ($applied_coupons as $coupon) {
			$coupon_code .= $coupon . '/';
		}
		if (!empty($coupon_code)) {
			$coupon_code = trim($coupon_code, '/');
		}
		$items_data = json_encode($items_data);
		$checkout_value = floatval(preg_replace('#[^\d.,]#', '', WC()->cart->get_cart_contents_total()));
		if (!empty($coupon_code)) {
			$live_js = "function get_shipping_event (shipping_method) {
							return gtag( 'event','add_shipping_info',{
								shipping_tier :shipping_method,
								coupon: " . $this->ga4wp_esc($coupon_code) . ",
								items:" . $items_data . ",
								value: " . $this->ga4wp_esc($checkout_value) . ",
							});
						}";
		} else {
			$live_js = "function get_shipping_event (shipping_method) {
				return gtag( 'event','add_shipping_info',{
					shipping_tier :shipping_method,
					items:" . $items_data . ",
					value: " . $this->ga4wp_esc($checkout_value) . ",
				});
			}";
		}
		$js = '';
		$js = $live_js;
		$js .= "var selected_shipping_method = jQuery( 'input[name^=\'shipping_method\']:checked' ).val();";
		$js .= "var shipping_method_tracked = false; var shipping_method = '';";
		$js .= "jQuery( 'form.checkout' ).on( 'click', 'input[name^=\'shipping_method\']', function( e ) { if ( selected_shipping_method !== this.value ) { shipping_method = this.value; shipping_method_tracked = true; if(shipping_method){get_shipping_event(shipping_method);} selected_shipping_method = this.value; } });";
		$js .= "jQuery( 'form.checkout' ).on( 'checkout_place_order', function() { if ( !shipping_method_tracked ) {shipping_method = selected_shipping_method ; shipping_method_tracked = true; if(shipping_method){get_shipping_event(shipping_method);} } });";
		$this->ga4wp_set_transient($js);
	}

	/**
	 * Queues client-side JS that fires an `add_payment_info` event when the
	 * shopper changes their selected payment method at checkout.
	 *
	 * Builds the current cart's items/coupon/value into a JS helper function,
	 * then emits JS that calls it on payment-method change and again as a
	 * fallback on `checkout_place_order` if no change was ever tracked.
	 */
	public function added_payment_method()
	{
		if (WC()->cart->get_cart_contents_count() > 0) {
			foreach (WC()->cart->get_cart() as $item) {
				$i = 0;
				$i++;
				$product_id = !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
				$items_data[] = $this->get_product_details($product_id, $item['quantity'], $i);
			}
		} else {
			$items_data = array();
		}
		$applied_coupons = WC()->cart->get_applied_coupons();
		$coupon_code = '';
		foreach ($applied_coupons as $coupon) {
			$coupon_code .= $coupon . '/';
		}
		if (!empty($coupon_code)) {
			$coupon_code = trim($coupon_code, '/');
		}
		$items_data = json_encode($items_data);
		$checkout_value = floatval(preg_replace('#[^\d.,]#', '', WC()->cart->get_cart_contents_total()));
		if (!empty($coupon_code)) {
			$live_js = "function get_paymnet_event (payment_method) {
							return gtag( 'event','add_payment_info',{
							payment_type :payment_method,
							coupon: " . $this->ga4wp_esc($coupon_code) . ",
							items:" . $items_data . ",
							value: " . $this->ga4wp_esc($checkout_value) . ",
							});
						}";
		} else {
			$live_js = "function get_paymnet_event (payment_method) {
				return gtag( 'event','add_payment_info',{
				payment_type :payment_method,
				items:" . $items_data . ",
				value: " . $this->ga4wp_esc($checkout_value) . ",
				});
			}";
		}
		$js = '';
		$js = $live_js;
		$js .= "var selected_payment_method = jQuery( 'input[name=\'payment_method\']:checked' ).val();";
		$js .= "var payment_method_tracked = false; var payment_method = '';";
		$js .= "jQuery( 'form.checkout' ).on( 'click', 'input[name=\'payment_method\']', function( e ) { if ( selected_payment_method !== this.value ) { payment_method = this.value; payment_method_tracked = true; if(payment_method){get_paymnet_event(payment_method);} selected_payment_method = this.value; } });";
		$js .= "jQuery( 'form.checkout' ).on( 'checkout_place_order', function() { if ( !payment_method_tracked ) {payment_method = selected_payment_method ; payment_method_tracked = true; if(payment_method){get_paymnet_event(payment_method);} } });";
		$this->ga4wp_set_transient($js);
	}

	/**
	 * Records a `processing_payment` event once an order enters payment processing.
	 *
	 * @param int $order_id The order entering the processing-payment status.
	 */
	public function processing_payment($order_id)
	{
		$order = wc_get_order($order_id);
		if ($order instanceof WC_Order) {
			if (!empty($this->api_secret)) {
				$this->data = $this->init_default_params();
				$this->data['events'][0] = array(
					'name' => 'processing_payment',
				);
				$this->making_remote_request();
				$this->params = null;
				$this->data = null;
			} else {
				$ga4wp_analytics_code = 'gtag("event", "processing_payment", {});';
				$this->ga4wp_set_transient($ga4wp_analytics_code);
			}
		}
	}

	/**
	 * Records an `order_cancelled` event when an order is cancelled.
	 *
	 * @param int $order_id The cancelled order's ID (unused; kept for hook signature compatibility).
	 */
	public function order_cancelled($order_id)
	{
		if (!empty($this->api_secret)) {
			$this->data = $this->init_default_params();
			$this->data['events'][0] = array(
				'name' => 'order_cancelled',
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			$ga4wp_analytics_code = 'gtag("event", "order_cancelled", {});';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
	}

	/**
	 * Records an `order_failed` event when an order's status changes to failed.
	 *
	 * @param int      $order_id The failed order's ID (unused; kept for hook signature compatibility).
	 * @param WC_Order $order    The failed order object.
	 */
	public function order_failed($order_id, $order)
	{
		if ($order instanceof WC_Order) {
			if (!empty($this->api_secret)) {
				$this->data = $this->init_default_params();
				$this->data['events'][0] = array(
					'name' => 'order_failed',
				);
				$this->making_remote_request();
				$this->params = null;
				$this->data = null;
			} else {
				$ga4wp_analytics_code = 'gtag("event", "order_failed", {});';
				$this->ga4wp_set_transient($ga4wp_analytics_code);
			}
		}
	}

	/**
	 * Records a `purchase` event for a completed order, and triggers conversion tracking.
	 *
	 * No-ops if the order can't be found or has already been tracked (tracked
	 * via the `ga4wp_already_tracked` order meta, set at the end of this
	 * method). Skips on-hold orders when the corresponding setting is enabled.
	 * Classifies the order via get_purchase_conversion_dimension_params() to
	 * attach conversion-type/subscription custom-dimension params (premium
	 * only; always empty in the free version), sends the purchase
	 * event via Measurement Protocol or a queued gtag() call (including
	 * USER-scoped ga4wp_user_id/ga4wp_user_role dimensions keyed off the
	 * order's customer, not the current session), marks the order as tracked,
	 * then delegates to adding_conversion_info() for Google Ads/Facebook/
	 * premium pixel conversion tracking.
	 *
	 * @param int $order_id The completed order's ID.
	 */
	public function completed_purchase($order_id)
	{
		$order = wc_get_order($order_id);
		if (!$order || ('yes' === get_post_meta($order_id, 'ga4wp_already_tracked', true))) {
			return;
		}
		if ($tracking_options = get_option('ga4wp_track_settings')) {
			$order_status = $order->get_status();
			if (($order_status == 'on-hold') && isset($tracking_options['disable_on_hold_conversion'])) {
				return;
			}
		}
		$coupons_list = '';
		if ($order->get_coupon_codes()) {
			$i = 1;
			foreach ($order->get_coupon_codes() as $coupon) {
				if ($i > 1) {
					$coupons_list .= ',';
				}
				$coupons_list .= $coupon;
				$i++;
			}
			$this->params['tcc'] = $coupons_list;
		}
		$customer_id      = (string) $order->get_customer_id();
		$dimension_params = $this->get_purchase_conversion_dimension_params($order);

		if (!empty($this->api_secret)) {
			$i = 0;
			$contents = array();
			foreach ($order->get_items() as $item) {
				$i++;
				$product_id = ! empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
				$items_data[] = $this->get_product_details($product_id, $item['qty'], $i);
				$contents[] = array(
					'id' => $product_id,
					'quantity' => $item['qty'],
				);
			}
			$params = array(
				'coupon' => $this->ga4wp_esc($coupons_list),
				'currency' => $this->ga4wp_esc(get_woocommerce_currency()),
				'items' => $items_data,
				'transaction_id' => $this->ga4wp_esc($order->get_order_number()),
				'value' => $this->ga4wp_esc($order->get_total()),
				'shipping' => $this->ga4wp_esc($order->get_total_shipping()),
				'tax' => $this->ga4wp_esc($order->get_total_tax()),
			);
			foreach ($params as $key => $value) {
				if (empty($value)) {
					unset($params[$key]);
				}
			}
			// ga4wp_conversion_type / ga4wp_subscription_id / ga4wp_subscription_renewal_count
			// are premium-only custom dimensions — $dimension_params is always empty in the free version.
			$params = array_merge($params, $dimension_params);
			$this->data = $this->init_default_params();
			// ga4wp_user_id / ga4wp_user_role are USER-scoped in GA4 — set via
			// user_properties using the order's customer_id (not the current session
			// user, which may be empty on an async/webhook-driven order completion).
			// This overrides whatever init_default_params() set from the session.
			$dim_value = $this->get_ga4wp_user_id_dimension_value($customer_id);
			if ($dim_value !== '') {
				$this->data['user_properties']['ga4wp_user_id'] = array('value' => $dim_value);
			}
			$role_value = $this->get_ga4wp_user_role_dimension_value($customer_id);
			if ($role_value !== '') {
				$this->data['user_properties']['ga4wp_user_role'] = array('value' => $role_value);
			}
			$this->data['events'][0] = array(
				'name' => 'purchase',
				'params' => $params,
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			$i = 0;
			$contents = array();
			foreach ($order->get_items() as $item) {
				$i++;
				$product_id = !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
				$items_data[] = $this->get_product_details($product_id, $item['qty'], $i);
				$contents[] = array(
					'id' => $this->ga4wp_esc($product_id),
					'quantity' => $this->ga4wp_esc($item['qty']),
				);
			}
			$params = array(
				'coupon' => $this->ga4wp_esc($coupons_list),
				'currency' => $this->ga4wp_esc(get_woocommerce_currency()),
				'items' => $items_data,
				'transaction_id' => $this->ga4wp_esc($order->get_order_number()),
				'value' => $this->ga4wp_esc($order->get_total()),
				'shipping' => $this->ga4wp_esc($order->get_total_shipping()),
				'tax' => $this->ga4wp_esc($order->get_total_tax()),
			);
			foreach ($params as $key => $value) {
				if (empty($value)) {
					unset($params[$key]);
				}
			}
			// ga4wp_conversion_type / ga4wp_subscription_id / ga4wp_subscription_renewal_count
			// are premium-only custom dimensions — $dimension_params is always empty in the free version.
			$params = array_merge($params, $dimension_params);
			$params = json_encode($params);
			$ga4wp_analytics_code = '';
			// ga4wp_user_id / ga4wp_user_role are USER-scoped in GA4 — gtag.js event
			// params are always EVENT-scoped, so both must be set via a separate
			// user_properties call, keyed off the order's customer_id.
			$user_props = array();
			$dim_value = $this->get_ga4wp_user_id_dimension_value($customer_id);
			if ($dim_value !== '') {
				$user_props[] = '"ga4wp_user_id":' . json_encode($dim_value);
			}
			$role_value = $this->get_ga4wp_user_role_dimension_value($customer_id);
			if ($role_value !== '') {
				$user_props[] = '"ga4wp_user_role":' . json_encode($role_value);
			}
			if (!empty($user_props)) {
				$ga4wp_analytics_code .= 'gtag("set","user_properties",{' . implode(',', $user_props) . '});';
			}
			$ga4wp_analytics_code .= 'gtag("event", "purchase",' . $params . ');';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
		update_post_meta($order->get_id(), 'ga4wp_already_tracked', 'yes');
		$this->adding_conversion_info($order_id, $contents);
	}

	/**
	 * Fires Google Ads and Facebook Pixel purchase-conversion tracking for an order.
	 *
	 * No-ops if the order can't be found or advanced settings aren't
	 * configured. Queues a Google Ads `conversion` gtag() event when a
	 * conversion ID/label is set, and a Facebook Pixel `Purchase` event when
	 * Facebook Pixel is enabled, then delegates to
	 * add_premium_pixel_conversions() for premium-only pixels.
	 *
	 * @param int   $order_id The completed order's ID.
	 * @param array $contents Order line items as `array( array( 'id' => ..., 'quantity' => ... ), ... )`.
	 */
	public function adding_conversion_info($order_id, $contents)
	{
		$order = wc_get_order($order_id);
		if (!$order instanceof WC_Order) return;

		$advance_options = get_option('ga4wp_advance_settings');
		if (!$advance_options) return;

		if (
			!empty($advance_options['google_adword']) &&
			!empty($advance_options['google_adword_code']) &&
			!empty($advance_options['google_adword_label'])
		) {
			$this->ga4wp_set_transient("gtag('event', 'conversion', {
			'send_to': '" . $this->ga4wp_esc($advance_options['google_adword_code'] . '/' . $advance_options['google_adword_label']) . "',
			'value': " . $this->ga4wp_esc(floor($order->get_total())) . ",
			'currency': '" . $this->ga4wp_esc($order->get_currency()) . "',
			'transaction_id': '" . $this->ga4wp_esc($order->get_transaction_id()) . "'
			});");
		}
		if (!empty($advance_options['facebook_pixel']) && !empty($advance_options['facebook_pixel_code'])) {
			$this->ga4wp_set_transient("fbq('track', 'Purchase',{
			value: " . $this->ga4wp_esc(floor($order->get_total())) . ",
			currency: '" . $this->ga4wp_esc($order->get_currency()) . "',
			contents: " . json_encode($contents) . ",
			content_type: 'product'
			});");
		}
		$this->add_premium_pixel_conversions($order_id, $contents);
	}

	/**
	 * Records a `refund` event when an order refund is created.
	 *
	 * No-ops if this refund was already tracked (via the
	 * `ga4wp_refund_already_tracked` comment meta, set at the end of this
	 * method) or either the order or refund object can't be resolved. Includes
	 * refunded items in the event only when their combined value matches the
	 * refund amount (a full/line-item refund); otherwise only the total refund
	 * value is reported. Sent via Measurement Protocol when an api_secret is
	 * configured, otherwise queued as a gtag() call.
	 *
	 * @param int $order_id  The order being refunded.
	 * @param int $refund_id The WC_Order_Refund post ID created for this refund.
	 */
	public function order_refunded($order_id, $refund_id)
	{
		if ('yes' === get_post_meta($refund_id, 'ga4wp_refund_already_tracked')) {
			return;
		}
		$order = wc_get_order($order_id);
		$refund = wc_get_order($refund_id);
		if (($order instanceof WC_Order) && ($refund instanceof WC_Order_Refund)) {
			if (method_exists($refund, 'get_reason') && $refund->get_reason()) {
				$reason = $order->get_order_number() . ' : ' . $refund->get_reason();
			} else {
				$reason = $order->get_order_number() . ' : Refund reason is not set';
			}
			$coupons_list = '';
			if ($order->get_coupon_codes()) {
				$i = 1;
				foreach ($order->get_coupon_codes() as $coupon) {
					if ($i > 1) {
						$coupons_list .= ',';
					}
					$coupons_list .= $coupon;
					$i++;
				}
				$this->params['tcc'] = $coupons_list;
			}
			if (!empty($this->api_secret)) {
				$i = 0;
				$refund_items_data = null;
				$contents = array();
				$items = $refund->get_items();
				if (! empty($items)) {
					foreach ($items as $item) {
						$i++;
						$product_id = ! empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
						$refund_items_data[] = $this->get_product_details($product_id, $item['qty'], $i);
					}
				}
				$total_refund = 0;
				$refund_value = $refund->get_amount();
				if (!empty($refund_items_data) && is_array($refund_items_data)) {
					foreach ($refund_items_data as $refund_item) {
						$total_refund = $total_refund + ($refund_item['quantity'] * $refund_item['price']);
					}
				}
				if ($total_refund == $refund_value) {
					$params = array(
						'coupon' => $this->ga4wp_esc($coupons_list),
						'currency' => $this->ga4wp_esc(get_woocommerce_currency()),
						'items' => $refund_items_data,
						'transaction_id' => $this->ga4wp_esc($order->get_order_number()),
						'value' => $this->ga4wp_esc($refund->get_amount()),
					);
				} else {
					$params = array(
						'coupon' => $this->ga4wp_esc($coupons_list),
						'currency' => $this->ga4wp_esc(get_woocommerce_currency()),
						'transaction_id' => $this->ga4wp_esc($order->get_order_number()),
						'value' => $this->ga4wp_esc($refund->get_amount()),
					);
				}
				foreach ($params as $key => $value) {
					if (empty($value)) {
						unset($params[$key]);
					}
				}
				$this->data = $this->init_default_params();
				$this->data['events'][0] = array(
					'name' => 'refund',
					'params' => $params,
				);
				$this->making_remote_request();
				$this->params = null;
				$this->data = null;
			} else {
				$i = 0;
				$refund_items_data = null;
				$contents = array();
				$items = $refund->get_items();
				if (!empty($items)) {
					foreach ($items as $item) {
						$i++;
						$product_id = !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
						$refund_items_data[] = $this->get_product_details($product_id, $item['qty'], $i);
					}
				}
				$total_refund = 0;
				$refund_value = $refund->get_amount();
				if (!empty($refund_items_data) && is_array($refund_items_data)) {
					foreach ($refund_items_data as $refund_item) {
						$total_refund = $total_refund + ($refund_item['quantity'] * $refund_item['price']);
					}
				}
				if ($total_refund == $refund_value) {
					$params = array(
						'coupon' => $this->ga4wp_esc($coupons_list),
						'currency' => $this->ga4wp_esc(get_woocommerce_currency()),
						'items' => $refund_items_data,
						'transaction_id' => $this->ga4wp_esc($order->get_order_number()),
						'value' => $this->ga4wp_esc($refund->get_amount()),
					);
				} else {
					$params = array(
						'coupon' => $this->ga4wp_esc($coupons_list),
						'currency' => $this->ga4wp_esc(get_woocommerce_currency()),
						'transaction_id' => $this->ga4wp_esc($order->get_order_number()),
						'value' => $this->ga4wp_esc($refund->get_amount()),
					);
				}
				foreach ($params as $key => $value) {
					if (empty($value)) {
						unset($params[$key]);
					}
				}
				$params = json_encode($params);
				$ga4wp_analytics_code = 'gtag("event", "refund",' . $params . ');';
				$this->ga4wp_set_transient($ga4wp_analytics_code);
			}
			update_post_meta($refund_id, 'ga4wp_refund_already_tracked', 'yes');
		}
	}

	/**
	 * Records an `error_occured` event for a caught PHP-side error.
	 *
	 * @param array $error The error details (only checked for being an array; not otherwise used).
	 */
	public function log_error($error)
	{
		if (!is_array($error)) {
			return;
		}
		if (!empty($this->api_secret)) {
			$this->data = $this->init_default_params();
			$this->data['events'][0] = array(
				'name' => 'error_occured',
			);
			$this->making_remote_request();
			$this->params = null;
			$this->data = null;
		} else {
			$ga4wp_analytics_code = 'gtag("event", "error_occured", {});';
			$this->ga4wp_set_transient($ga4wp_analytics_code);
		}
	}

	/**
	 * Sanitizes a string for safe inline embedding inside a JS string literal.
	 *
	 * Strips characters that could break out of a quoted JS string or inject
	 * markup/statements (", ;, <, >) and collapses whitespace.
	 *
	 * @param string $string The raw value to sanitize.
	 * @return string The sanitized string, or the original value unchanged if empty/falsy.
	 */
	public function ga4wp_esc($string)
	{
		if (!empty($string)) {
			// Backslash must be stripped alongside the other characters — left alone, a
			// trailing backslash escapes the closing quote of the JS string literal this
			// value is embedded in, letting the rest of the string bleed into surrounding
			// script code (e.g. `"Evil Product\"` never actually closes the string).
			$string = str_replace(array('"', ';', '<', '>', '\\'), ' ', $string);
			$string = trim(preg_replace('/\s+/', ' ', $string));
		}
		return $string;
	}
}

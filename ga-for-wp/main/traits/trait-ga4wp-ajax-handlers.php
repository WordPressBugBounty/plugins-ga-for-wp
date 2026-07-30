<?php

/**
 * Trait: GA4WP_Ajax_Handlers
 *
 * All wp_ajax_* handlers that are not pure authentication actions:
 *   - tab_update         — serves per-tab HTML for the settings page
 *   - ajax_get_ga4_accounts
 *   - ajax_create_ga4_account
 *   - ajax_create_ga4_property
 *
 * Premium-only AJAX methods (explore reports, custom dims) live in
 * trait-ga4wp-ajax-handlers__premium_only.php (GA4WP_Ajax_Handlers_Premium_Only).
 */
if (! defined('ABSPATH')) die;

trait GA4WP_Ajax_Handlers
{

	/* ── Tab update (serves HTML for each settings/dashboard tab) ─ */

	/**
	 * AJAX handler that renders the HTML for a single settings/dashboard tab.
	 *
	 * Reads the requested tab id from $_POST['tab'], persists it as the
	 * "current tab" option, and echoes either a settings form (tracking,
	 * advanced, events, dashboard, custom dimensions, widgets, email) or —
	 * for report tabs — the cached/fetched chart data rendered as charts,
	 * tables, or stat blocks. Delegates to
	 * handle_premium_dashboard_tab() first when available so premium-only
	 * report tabs can take over. Always terminates the request via
	 * wp_die() instead of returning.
	 */
	public function tab_update()
	{
		check_ajax_referer('ga4wp-tab-update', 'security');
		if (! current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized', 403);
		}
		set_time_limit(0);
		$tab_id = str_replace(array('#', '-tab'), '', $_POST['tab']);
		update_option('ga4wp_current_tab_id', $tab_id);

		if (stripos($tab_id, 'et-') !== false) {

			if (stripos($tab_id, 'track') !== false) {
				$ga4wp_settings = GA4WP_Settings::get_instance();
				$defaults_track = $ga4wp_settings->init_ga4wp_track_defaults();
				if (! get_option('ga4wp_track_settings')) {
					$ga4wp_track_settings = $defaults_track;
					update_option('ga4wp_track_settings', $defaults_track);
				} else {
					$ga4wp_track_settings = get_option('ga4wp_track_settings');
				} ?>
				<form action="" method="POST">
					<div class="ga4wp-row ga4s-wrap">
						<div class="ga4wp-col s12 m6">
							<div class="ga4s-section">
								<div class="ga4s-section-head"><span class="material-icons-round">manage_search</span>
									<div>
										<div class="ga4s-section-title"><?php _e('General Tracking', 'ga-for-wp-text'); ?></div>
										<div class="ga4s-section-desc"><?php _e('Control what activity gets tracked on your website.', 'ga-for-wp-text'); ?></div>
									</div>
								</div>
								<div class="ga4s-rows">
									<div class="ga4s-row">
										<div class="ga4s-row-info">
											<div class="ga4s-row-label"><?php _e('Track Admins', 'ga-for-wp-text'); ?></div>
											<div class="ga4s-row-desc"><?php _e('Include admin activity in Analytics tracking.', 'ga-for-wp-text'); ?></div>
										</div>
										<div class="switch"><label><input type="checkbox" name="ga4wp_track_settings[track_admin]" value="yes" <?php checked(isset($ga4wp_track_settings['track_admin']) && $ga4wp_track_settings['track_admin']); ?>><span class="lever"></span></label></div>
									</div>
									<div class="ga4s-row">
										<div class="ga4s-row-info">
											<div class="ga4s-row-label"><?php _e('Track Admin Pages', 'ga-for-wp-text'); ?></div>
											<div class="ga4s-row-desc"><?php _e('Add the Google tag to wp-admin pages so activity inside the WordPress dashboard is tracked. Off by default.', 'ga-for-wp-text'); ?></div>
										</div>
										<div class="switch"><label><input type="checkbox" name="ga4wp_track_settings[track_admin_pages]" value="yes" <?php checked(isset($ga4wp_track_settings['track_admin_pages']) && $ga4wp_track_settings['track_admin_pages']); ?>><span class="lever"></span></label></div>
									</div>
									<div class="ga4s-row">
										<div class="ga4s-row-info">
											<div class="ga4s-row-label"><?php _e('Do Not Track Pageviews', 'ga-for-wp-text'); ?></div>
											<div class="ga4s-row-desc"><?php _e('Stop tracking basic pageview events for the website.', 'ga-for-wp-text'); ?></div>
										</div>
										<div class="switch"><label><input type="checkbox" name="ga4wp_track_settings[not_track_pageviews]" value="yes" <?php checked(isset($ga4wp_track_settings['not_track_pageviews']) && $ga4wp_track_settings['not_track_pageviews']); ?>><span class="lever"></span></label></div>
									</div>
									<div class="ga4s-row">
										<div class="ga4s-row-info">
											<div class="ga4s-row-label"><?php _e('Enhanced Link Attribution', 'ga-for-wp-text'); ?></div>
											<div class="ga4s-row-desc"><?php _e('Differentiate links the user interacted with using enhanced attribution.', 'ga-for-wp-text'); ?></div>
										</div>
										<div class="switch"><label><input type="checkbox" name="ga4wp_track_settings[enhanced_link_attribution]" value="yes" <?php checked(isset($ga4wp_track_settings['enhanced_link_attribution']) && $ga4wp_track_settings['enhanced_link_attribution']); ?>><span class="lever"></span></label></div>
									</div>
								</div>
							</div>
						</div>
						<?php if (class_exists('WooCommerce')) : ?>
							<div class="ga4wp-col s12 m6">
								<div class="ga4s-section">
									<div class="ga4s-section-head"><span class="material-icons-round">shopping_cart</span>
										<div>
											<div class="ga4s-section-title"><?php _e('WooCommerce', 'ga-for-wp-text'); ?></div>
											<div class="ga4s-section-desc"><?php _e('Fine-tune how WooCommerce product events are tracked.', 'ga-for-wp-text'); ?></div>
										</div>
									</div>
									<div class="ga4s-rows">
										<div class="ga4s-row">
											<div class="ga4s-row-info">
												<div class="ga4s-row-label"><?php _e('Single Product Pages', 'ga-for-wp-text'); ?></div>
												<div class="ga4s-row-desc"><?php _e('Track product impressions on Single Product pages.', 'ga-for-wp-text'); ?></div>
											</div>
											<div class="switch"><label><input type="checkbox" name="ga4wp_track_settings[product_single_track]" value="yes" <?php checked(isset($ga4wp_track_settings['product_single_track']) && $ga4wp_track_settings['product_single_track']); ?>><span class="lever"></span></label></div>
										</div>
										<div class="ga4s-row">
											<div class="ga4s-row-info">
												<div class="ga4s-row-label"><?php _e('Archive Pages', 'ga-for-wp-text'); ?></div>
												<div class="ga4s-row-desc"><?php _e('Track product impressions on Archive/category pages.', 'ga-for-wp-text'); ?></div>
											</div>
											<div class="switch"><label><input type="checkbox" name="ga4wp_track_settings[product_archive_track]" value="yes" <?php checked(isset($ga4wp_track_settings['product_archive_track']) && $ga4wp_track_settings['product_archive_track']); ?>><span class="lever"></span></label></div>
										</div>
										<div class="ga4s-row">
											<div class="ga4s-row-info">
												<div class="ga4s-row-label"><?php _e('Skip On-Hold Transactions', 'ga-for-wp-text'); ?></div>
												<div class="ga4s-row-desc"><?php _e('Do not count on-hold orders as conversions.', 'ga-for-wp-text'); ?></div>
											</div>
											<div class="switch"><label><input type="checkbox" name="ga4wp_track_settings[disable_on_hold_conversion]" value="yes" <?php checked(isset($ga4wp_track_settings['disable_on_hold_conversion']) && $ga4wp_track_settings['disable_on_hold_conversion']); ?>><span class="lever"></span></label></div>
										</div>
									</div>
								</div>
							</div>
						<?php endif; ?>
						<div class="ga4wp-col s12 m6">
							<div class="ga4s-section">
								<div class="ga4s-section-head"><span class="material-icons-round">verified_user</span>
									<div>
										<div class="ga4s-section-title"><?php _e('GDPR &amp; Privacy', 'ga-for-wp-text'); ?></div>
										<div class="ga4s-section-desc"><?php _e('Manage privacy-related tracking controls.', 'ga-for-wp-text'); ?></div>
									</div>
								</div>
								<div class="ga4s-rows">
									<div class="ga4s-row">
										<div class="ga4s-row-info">
											<div class="ga4s-row-label"><?php _e('Anonymize IP Addresses', 'ga-for-wp-text'); ?></div>
											<div class="ga4s-row-desc"><?php _e('Mask the last octet of IP addresses before sending to GA.', 'ga-for-wp-text'); ?></div>
										</div>
										<div class="switch"><label><input type="checkbox" name="ga4wp_track_settings[anonymize_ip]" value="yes" <?php checked(isset($ga4wp_track_settings['anonymize_ip']) && $ga4wp_track_settings['anonymize_ip']); ?>><span class="lever"></span></label></div>
									</div>
									<div class="ga4s-row">
										<div class="ga4s-row-info">
											<div class="ga4s-row-label"><?php _e('Disable Demographics &amp; Remarketing', 'ga-for-wp-text'); ?></div>
											<div class="ga4s-row-desc"><?php _e('Disable tracking users by interest and demographics.', 'ga-for-wp-text'); ?></div>
										</div>
										<div class="switch"><label><input type="checkbox" name="ga4wp_track_settings[track_interest]" value="yes" <?php checked(isset($ga4wp_track_settings['track_interest']) && $ga4wp_track_settings['track_interest']); ?>><span class="lever"></span></label></div>
									</div>
									<div class="ga4s-row">
										<div class="ga4s-row-info">
											<div class="ga4s-row-label"><?php _e('Do Not Track User ID', 'ga-for-wp-text'); ?></div>
											<div class="ga4s-row-desc"><?php _e('Prevent sending WordPress user IDs to Google Analytics.', 'ga-for-wp-text'); ?></div>
										</div>
										<div class="switch"><label><input type="checkbox" name="ga4wp_track_settings[not_track_user_id]" value="yes" <?php checked(isset($ga4wp_track_settings['not_track_user_id']) && $ga4wp_track_settings['not_track_user_id']); ?>><span class="lever"></span></label></div>
									</div>
									<div class="ga4s-row">
										<div class="ga4s-row-info">
											<div class="ga4s-row-label"><?php _e('Google Consent Mode', 'ga-for-wp-text'); ?></div>
											<div class="ga4s-row-desc"><?php _e('Respect user consent signals before collecting sensitive data.', 'ga-for-wp-text'); ?></div>
										</div>
										<div class="switch"><label><input type="checkbox" name="ga4wp_track_settings[track_ga_consent]" value="yes" <?php checked(isset($ga4wp_track_settings['track_ga_consent']) && $ga4wp_track_settings['track_ga_consent']); ?>><span class="lever"></span></label></div>
									</div>
								</div>
							</div>
						</div>
						<?php $ga4wp_track_settings_tr = get_option('ga4wp_track_settings') ?: [];
						$measurement_key_tr = get_option('measurement_key');
						if (! empty($measurement_key_tr)) $ga4wp_track_settings_tr['google_measurement_api'] = $measurement_key_tr;
						$mp_auth_settings_tr = get_option('ga4wp_auth_settings');
						$mp_has_edit_scope_tr = false;
						foreach ((array) get_option('ga4wp_granted_scopes', []) as $mp_scope_tr) {
							if (stripos($mp_scope_tr, 'analytics.edit') !== false) {
								$mp_has_edit_scope_tr = true;
								break;
							}
						} ?>
						<div class="ga4wp-col s12 m6">
							<div class="ga4s-section">
								<div class="ga4s-section-head"><span class="material-icons-round">api</span>
									<div>
										<div class="ga4s-section-title"><?php _e('Measurement Protocol', 'ga-for-wp-text'); ?></div>
										<div class="ga4s-section-desc"><?php _e('Enable server-side event sending via the GA4 Measurement Protocol.', 'ga-for-wp-text'); ?></div>
									</div>
								</div>
								<div class="ga4s-rows">
									<div class="ga4s-input-row"><label class="ga4s-input-label"><?php _e('API Secret Key', 'ga-for-wp-text'); ?></label>
										<div style="display:flex;gap:8px;align-items:flex-start;">
											<div class="input-field" style="margin:0;flex:1;min-width:0;"><input class="validate" placeholder="XXXXXXXXXXXXXXXXXXXXXXX" name="ga4wp_track_settings[google_measurement_api]" type="text" value="<?php echo esc_attr($ga4wp_track_settings_tr['google_measurement_api'] ?? ''); ?>"></div>
											<?php if (! empty($mp_auth_settings_tr['property_id']) && $mp_has_edit_scope_tr) : ?>
												<button type="button" class="ga4wp-status-create-all-btn" id="ga4wp-generate-mp-key-inline" data-nonce="<?php echo esc_attr(wp_create_nonce('ga4wp-tab-update')); ?>" style="flex-shrink:0;margin:0;">
													<span class="material-icons-round">autorenew</span>
													<?php _e('Generate', 'ga-for-wp-text'); ?>
												</button>
											<?php elseif (! empty($mp_auth_settings_tr['property_id'])) : ?>
												<span class="ga4wp-status-edit-scope-notice" style="flex-shrink:0;" title="<?php esc_attr_e('Re-link your Google account and grant the analytics.edit permission to enable this action.', 'ga-for-wp-text'); ?>">
													<span class="material-icons-round" style="font-size:15px;vertical-align:-3px;color:var(--ga-amber,#F59E0B);">lock</span>
													<?php _e('analytics.edit scope required', 'ga-for-wp-text'); ?>
												</span>
											<?php endif; ?>
										</div>
										<div class="ga4s-input-hint"><a href="https://trueana.com/how-to-get-mesurement-id-for-property-in-google-analytics-version-4/" target="_blank"><?php _e('How to get your Measurement Protocol API secret?', 'ga-for-wp-text'); ?></a></div>
									</div>
								</div>
							</div>
						</div>
						<div class="ga4wp-col s12 m6">
							<div class="ga4s-section">
								<div class="ga4s-section-head"><span class="material-icons-round">bug_report</span>
									<div>
										<div class="ga4s-section-title"><?php _e('Google Analytics Debug Mode', 'ga-for-wp-text'); ?></div>
										<div class="ga4s-section-desc"><?php _e('Send events to the GA4 DebugView for testing.', 'ga-for-wp-text'); ?></div>
									</div>
								</div>
								<div class="ga4s-rows">
									<div class="ga4s-row">
										<div class="ga4s-row-info">
											<div class="ga4s-row-label"><?php _e('Enable Debug Mode', 'ga-for-wp-text'); ?></div>
											<div class="ga4s-row-desc"><a href="https://trueana.com/debug-view-support/" target="_blank"><?php _e('Learn more about GA4 Debug Mode', 'ga-for-wp-text'); ?></a></div>
										</div>
										<div class="switch"><label><input type="checkbox" name="ga4wp_track_settings[google_analytics_debug_mode]" value="yes" <?php checked(isset($ga4wp_track_settings_tr['google_analytics_debug_mode']) && $ga4wp_track_settings_tr['google_analytics_debug_mode']); ?>><span class="lever"></span></label></div>
									</div>
								</div>
							</div>
						</div>
						<div class="ga4wp-col s12 ga4s-actions">
							<button class="ga4s-save-btn" type="submit" name="ga4wp_track_settings[ga4wp_track_submit]" value="submit"><span class="material-icons-round">save</span><?php _e('Save Tracking Settings', 'ga-for-wp-text'); ?></button>
						</div>
						<?php wp_nonce_field('ga4wp_track_submit', 'ga4wp_nonce_header'); ?>
					</div>
				</form>

			<?php } elseif (stripos($tab_id, 'advanced') !== false) {
				$ga4wp_advance_settings = get_option('ga4wp_advance_settings') ?: null;
			?>
				<form action="" method="POST">
					<div class="ga4wp-row ga4s-wrap" style="display:flex;flex-wrap:wrap;align-items:stretch;margin-left:-0.75rem;margin-right:-0.75rem;">
						<div class="ga4wp-col s12 m6">
							<div class="ga4s-section">
								<div class="ga4s-section-head"><span class="material-icons-round">facebook</span>
									<div>
										<div class="ga4s-section-title"><?php _e('Facebook Pixel', 'ga-for-wp-text'); ?></div>
										<div class="ga4s-section-desc"><?php _e('Track visitors on Facebook via Meta Pixel.', 'ga-for-wp-text'); ?></div>
									</div>
								</div>
								<div class="ga4s-rows">
									<div class="ga4s-row">
										<div class="ga4s-row-info">
											<div class="ga4s-row-label"><?php _e('Enable Facebook Pixel', 'ga-for-wp-text'); ?></div>
											<div class="ga4s-row-desc"><?php _e('Inject Meta Pixel snippet on all pages.', 'ga-for-wp-text'); ?></div>
										</div>
										<div class="switch"><label><input type="checkbox" name="ga4wp_advance_settings[facebook_pixel]" value="yes" <?php checked(isset($ga4wp_advance_settings['facebook_pixel']) && $ga4wp_advance_settings['facebook_pixel']); ?>><span class="lever"></span></label></div>
									</div>
									<div class="ga4s-input-row"><label class="ga4s-input-label"><?php _e('Pixel ID', 'ga-for-wp-text'); ?></label>
										<div class="input-field" style="margin:0;"><input class="validate" placeholder="XXXXXXXXXX" name="ga4wp_advance_settings[facebook_pixel_code]" type="text" value="<?php echo esc_attr($ga4wp_advance_settings['facebook_pixel_code'] ?? ''); ?>"></div>
										<div class="ga4s-input-hint"><a href="https://trueana.com/get-facebook-pixel-code" target="_blank"><?php _e('How to get your Facebook Pixel ID?', 'ga-for-wp-text'); ?></a></div>
									</div>
								</div>
							</div>
						</div>
						<div class="ga4wp-col s12 m6">
							<div class="ga4s-section">
								<div class="ga4s-section-head"><span class="material-icons-round">ads_click</span>
									<div>
										<div class="ga4s-section-title"><?php _e('Google Ads Conversion Tracking', 'ga-for-wp-text'); ?></div>
										<div class="ga4s-section-desc"><?php _e('Track conversions back to Google Ads campaigns.', 'ga-for-wp-text'); ?></div>
									</div>
								</div>
								<div class="ga4s-rows">
									<div class="ga4s-row">
										<div class="ga4s-row-info">
											<div class="ga4s-row-label"><?php _e('Enable Google Ads Conversion Tracking', 'ga-for-wp-text'); ?></div>
										</div>
										<div class="switch"><label><input type="checkbox" name="ga4wp_advance_settings[google_adword]" value="yes" <?php checked(isset($ga4wp_advance_settings['google_adword']) && $ga4wp_advance_settings['google_adword']); ?>><span class="lever"></span></label></div>
									</div>
									<div class="ga4wp-row ga4s-2col">
										<div class="ga4wp-col s12 m6"><label class="ga4s-input-label"><?php _e('Conversion ID', 'ga-for-wp-text'); ?></label>
											<div class="input-field" style="margin:0;"><input class="validate" placeholder="AW-CONVERSION_ID" name="ga4wp_advance_settings[google_adword_code]" type="text" value="<?php echo esc_attr($ga4wp_advance_settings['google_adword_code'] ?? ''); ?>"></div>
										</div>
										<div class="ga4wp-col s12 m6"><label class="ga4s-input-label"><?php _e('Conversion Label', 'ga-for-wp-text'); ?></label>
											<div class="input-field" style="margin:0;"><input class="validate" placeholder="AW-CONVERSION_LABEL" name="ga4wp_advance_settings[google_adword_label]" type="text" value="<?php echo esc_attr($ga4wp_advance_settings['google_adword_label'] ?? ''); ?>"></div>
										</div>
									</div>
									<div class="ga4s-input-hint" style="padding:8px 0 14px"><a href="https://trueana.com/get-google-ads-conversion-id-label" target="_blank"><?php _e('How to get Conversion ID and Label?', 'ga-for-wp-text'); ?></a></div>
								</div>
							</div>
						</div>
						<?php if (method_exists($this, 'render_premium_advanced_integrations')) {
							$this->render_premium_advanced_integrations($ga4wp_advance_settings);
						} ?>

						<div class="ga4wp-col s12 ga4s-actions">
							<button class="ga4s-save-btn" type="submit" name="ga4wp_advance_submit" value="submit"><span class="material-icons-round">save</span><?php _e('Save Advanced Settings', 'ga-for-wp-text'); ?></button>
						</div>
						<?php wp_nonce_field('ga4wp_advance_submit', 'ga4wp_nonce_header'); ?>
					</div>
				</form>

				<?php } elseif (stripos($tab_id, 'events') !== false) {
				$ga4wp_settings       = GA4WP_Settings::get_instance();
				$defaults             = $ga4wp_settings->init_ga4wp_events_defaults();
				$ga4wp_event_settings = get_option('ga4wp_event_settings') ?: $defaults;
				$has_woo              = class_exists('WooCommerce');
				$has_wcs              = class_exists('WC_Subscriptions');
				$is_premium           = function_exists('gfw_fs') && gfw_fs()->can_use_premium_code__premium_only();

				/* ── Event metadata: label + description ── */
				$event_meta = [
					/* General */
					'user_login'        => [__('User Login',           'ga-for-wp-text'), __('Fired when a registered user logs in.',                         'ga-for-wp-text')],
					'user_login_errors' => [__('Login Errors',         'ga-for-wp-text'), __('Fired when a login attempt fails.',                              'ga-for-wp-text')],
					'user_logout'       => [__('User Logout',          'ga-for-wp-text'), __('Fired when a user logs out.',                                    'ga-for-wp-text')],
					'user_signup'       => [__('User Sign-up',         'ga-for-wp-text'), __('Fired when a new user account is created.',                      'ga-for-wp-text')],
					'wrote_review'      => [__('Wrote Review',         'ga-for-wp-text'), __('Fired when a product review is submitted.',                      'ga-for-wp-text')],
					'commented'         => [__('Commented',            'ga-for-wp-text'), __('Fired when a comment is posted.',                                'ga-for-wp-text')],
					'log_error'         => [__('Error Logged',         'ga-for-wp-text'), __('Fired when a WooCommerce shutdown error is recorded.',           'ga-for-wp-text')],
					/* WooCommerce */
					'viewed_signup_form'    => [__('Viewed Sign-up Form',       'ga-for-wp-text'), __('Fired when the WooCommerce registration form is shown.',              'ga-for-wp-text')],
					'viewed_shop'           => [__('Viewed Shop',               'ga-for-wp-text'), __('Fired on shop, category, and product archive pages.',                 'ga-for-wp-text')],
					'viewed_product'        => [__('Viewed Product',            'ga-for-wp-text'), __('Fired when a single product page is viewed.',                         'ga-for-wp-text')],
					'added_product'         => [__('Added to Cart',             'ga-for-wp-text'), __('Fired when a product is added to the cart.',                          'ga-for-wp-text')],
					'removed_product'       => [__('Removed from Cart',         'ga-for-wp-text'), __('Fired when a product is removed from the cart.',                      'ga-for-wp-text')],
					'changed_quantity'      => [__('Changed Cart Quantity',     'ga-for-wp-text'), __('Fired when a cart item quantity is updated.',                         'ga-for-wp-text')],
					'viewed_cart'           => [__('Viewed Cart',               'ga-for-wp-text'), __('Fired when the cart page is viewed.',                                 'ga-for-wp-text')],
					'wrong_coupon_applied'  => [__('Invalid Coupon',            'ga-for-wp-text'), __('Fired when an invalid coupon code is entered.',                       'ga-for-wp-text')],
					'applied_coupon'        => [__('Coupon Applied',            'ga-for-wp-text'), __('Fired when a coupon is successfully applied.',                        'ga-for-wp-text')],
					'removed_coupon'        => [__('Coupon Removed',            'ga-for-wp-text'), __('Fired when a coupon is removed from the cart.',                       'ga-for-wp-text')],
					'begin_checkout'        => [__('Began Checkout',            'ga-for-wp-text'), __('Fired when the checkout form is loaded.',                             'ga-for-wp-text')],
					'filled_checkout_form'  => [__('Filled Checkout Form',      'ga-for-wp-text'), __('Fired when checkout form fields are being filled.',                   'ga-for-wp-text')],
					'added_payment_method'  => [__('Added Payment Method',      'ga-for-wp-text'), __('Fired when a payment method is selected at checkout.',                'ga-for-wp-text')],
					'added_shipping_method' => [__('Added Shipping Method',     'ga-for-wp-text'), __('Fired when a shipping method is chosen.',                            'ga-for-wp-text')],
					'order_failed'          => [__('Order Failed',              'ga-for-wp-text'), __('Fired when an order transitions to failed status.',                   'ga-for-wp-text')],
					'processing_payment'    => [__('Processing Payment',        'ga-for-wp-text'), __('Fired when checkout is submitted and the order is created.',          'ga-for-wp-text')],
					'completed_purchase'    => [__('Purchase Completed',        'ga-for-wp-text'), __('Fired when an order reaches completed or processing status.',         'ga-for-wp-text')],
					'viewed_account'        => [__('Viewed Account',            'ga-for-wp-text'), __('Fired on the My Account page.',                                      'ga-for-wp-text')],
					'viewed_order'          => [__('Viewed Order',              'ga-for-wp-text'), __('Fired when a customer views an order detail page.',                   'ga-for-wp-text')],
					'changed_password'      => [__('Changed Password',          'ga-for-wp-text'), __('Fired when a user updates their account password.',                   'ga-for-wp-text')],
					'lost_password'         => [__('Lost Password',             'ga-for-wp-text'), __('Fired when a password reset is requested.',                           'ga-for-wp-text')],
					'estimated_shipping'    => [__('Estimated Shipping',        'ga-for-wp-text'), __('Fired when shipping is calculated in the cart.',                      'ga-for-wp-text')],
					'order_cancelled'       => [__('Order Cancelled',           'ga-for-wp-text'), __('Fired when an order is cancelled.',                                   'ga-for-wp-text')],
					'order_refunded'        => [__('Order Refunded',            'ga-for-wp-text'), __('Fired when an order is fully or partially refunded.',                 'ga-for-wp-text')],
					/* WooCommerce Subscriptions */
					'subscription_created'              => [__('Subscription Created',       'ga-for-wp-text'), __('Fired when a new subscription is placed.',                          'ga-for-wp-text')],
					'subscription_payment_failed'       => [__('Payment Failed',             'ga-for-wp-text'), __('Fired when a subscription payment attempt fails.',                   'ga-for-wp-text')],
					'subscription_renewal_failed'       => [__('Renewal Payment Failed',     'ga-for-wp-text'), __('Fired when a renewal payment fails.',                               'ga-for-wp-text')],
					'subscription_cancelled'            => [__('Subscription Cancelled',     'ga-for-wp-text'), __('Fired when a subscription is cancelled.',                           'ga-for-wp-text')],
					'subscription_expired'              => [__('Subscription Expired',       'ga-for-wp-text'), __('Fired when a subscription reaches its expiration date.',             'ga-for-wp-text')],
					'subscription_on_hold'              => [__('Subscription On Hold',       'ga-for-wp-text'), __('Fired when a subscription is paused.',                              'ga-for-wp-text')],
					'subscription_trial_ended'          => [__('Trial Period Ended',         'ga-for-wp-text'), __('Fired when a free trial period ends.',                              'ga-for-wp-text')],
					'subscription_expiration_scheduled' => [__('Expiration Scheduled',       'ga-for-wp-text'), __('Fired on the subscription\'s scheduled expiration date.',           'ga-for-wp-text')],
					'subscription_prepaid_term_ended'   => [__('Prepaid Term Ended',         'ga-for-wp-text'), __('Fired when a prepaid subscription period ends.',                    'ga-for-wp-text')],
					'subscription_plan_switched'        => [__('Plan Switched',              'ga-for-wp-text'), __('Fired when a subscriber upgrades or downgrades their plan.',        'ga-for-wp-text')],
				];

				/* ── Helper: render a grid of event toggle rows ── */
				$render_event_grid = function (array $keys) use ($defaults, $ga4wp_event_settings, $event_meta) {
					foreach ($keys as $key) :
						if (! array_key_exists($key, $defaults)) continue;
						$meta    = $event_meta[$key] ?? [ucwords(str_replace('_', ' ', $key)), ''];
						$enabled = isset($ga4wp_event_settings[$key]) ? (bool) $ga4wp_event_settings[$key] : true;
				?>
						<div class="ga4wp-col s12 m6">
							<div class="ga4s-row">
								<div class="ga4s-row-info">
									<div class="ga4s-row-label"><?php echo esc_html($meta[0]); ?></div>
									<?php if (! empty($meta[1])) : ?>
										<div class="ga4s-row-desc"><?php echo esc_html($meta[1]); ?></div>
									<?php endif; ?>
								</div>
								<div class="switch"><label>
										<input type="checkbox" name="ga4wp_event_settings[<?php echo esc_attr($key); ?>]" value="yes" <?php checked($enabled); ?>>
										<span class="lever"></span>
									</label></div>
							</div>
						</div>
				<?php
					endforeach;
				};
				?>
				<form action="" method="POST">
					<div class="ga4wp-row ga4s-wrap">

						<!-- ── General Events ── -->
						<div class="ga4wp-col s12">
							<div class="ga4s-section">
								<div class="ga4s-section-head">
									<span class="material-icons-round">bolt</span>
									<div>
										<div class="ga4s-section-title"><?php _e('General Events', 'ga-for-wp-text'); ?></div>
										<div class="ga4s-section-desc"><?php _e('Core user interaction events tracked on every WordPress site.', 'ga-for-wp-text'); ?></div>
									</div>
								</div>
								<div class="ga4wp-row ga4s-events-grid">
									<?php $render_event_grid(['user_login', 'user_login_errors', 'user_logout', 'user_signup', 'wrote_review', 'commented', 'log_error']); ?>
								</div>
							</div>
						</div>

						<!-- ── WooCommerce Events (only when WC active) ── -->
						<?php if ($has_woo) : ?>
							<div class="ga4wp-col s12">
								<div class="ga4s-section">
									<div class="ga4s-section-head">
										<span class="material-icons-round">shopping_cart</span>
										<div>
											<div class="ga4s-section-title"><?php _e('WooCommerce Events', 'ga-for-wp-text'); ?></div>
											<div class="ga4s-section-desc"><?php _e('Store interactions — browsing, cart, checkout, and order lifecycle events.', 'ga-for-wp-text'); ?></div>
										</div>
									</div>
									<div class="ga4wp-row ga4s-events-grid">
										<?php $render_event_grid([
											'viewed_signup_form',
											'viewed_shop',
											'viewed_product',
											'added_product',
											'removed_product',
											'changed_quantity',
											'viewed_cart',
											'wrong_coupon_applied',
											'applied_coupon',
											'removed_coupon',
											'begin_checkout',
											'filled_checkout_form',
											'added_payment_method',
											'added_shipping_method',
											'order_failed',
											'processing_payment',
											'completed_purchase',
											'viewed_account',
											'viewed_order',
											'changed_password',
											'lost_password',
											'estimated_shipping',
											'order_cancelled',
											'order_refunded',
										]); ?>
									</div>
								</div>
							</div>
						<?php endif; ?>

						<!-- ── WooCommerce Subscriptions Events (premium only, and only when WCS active) ── -->
						<?php if ($has_wcs && $is_premium) : ?>
							<div class="ga4wp-col s12">
								<div class="ga4s-section">
									<div class="ga4s-section-head">
										<span class="material-icons-round">autorenew</span>
										<div>
											<div class="ga4s-section-title"><?php _e('WooCommerce Subscriptions Events', 'ga-for-wp-text'); ?></div>
											<div class="ga4s-section-desc"><?php _e('Subscription lifecycle events — creation, renewals, cancellations, and plan changes.', 'ga-for-wp-text'); ?></div>
										</div>
									</div>
									<div class="ga4wp-row ga4s-events-grid">
										<?php $render_event_grid([
											'subscription_created',
											'subscription_payment_failed',
											'subscription_renewal_failed',
											'subscription_cancelled',
											'subscription_expired',
											'subscription_on_hold',
											'subscription_trial_ended',
											'subscription_expiration_scheduled',
											'subscription_prepaid_term_ended',
											'subscription_plan_switched',
										]); ?>
									</div>
								</div>
							</div>
						<?php endif; ?>

						<div class="ga4wp-col s12 ga4s-actions">
							<button class="ga4s-save-btn" type="submit" name="ga4wp_event_settings[ga4wp_event_submit]" value="submit">
								<span class="material-icons-round">save</span><?php _e('Save Event Settings', 'ga-for-wp-text'); ?>
							</button>
						</div>
						<?php wp_nonce_field('ga4wp_event_submit', 'ga4wp_nonce_header'); ?>
					</div>
				</form>

			<?php } elseif (stripos($tab_id, 'dashboard') !== false) {
				$ga4wp_settings           = GA4WP_Settings::get_instance();
				$defaults_dash_tabs       = $ga4wp_settings->init_ga4wp_dashboard_defaults();
				$ga4wp_dashboard_settings = get_option('ga4wp_dashboard_settings') ?: $defaults_dash_tabs;
				$saved_tab_order          = get_option('ga4wp_tab_order', []);
				if (! empty($saved_tab_order) && is_array($saved_tab_order)) {
					$ordered_defaults = [];
					foreach ($saved_tab_order as $_k) {
						if (array_key_exists($_k, $defaults_dash_tabs)) $ordered_defaults[$_k] = $defaults_dash_tabs[$_k];
					}
					foreach ($defaults_dash_tabs as $_k => $_v) {
						if (! array_key_exists($_k, $ordered_defaults)) $ordered_defaults[$_k] = $_v;
					}
					$defaults_dash_tabs = $ordered_defaults;
				}
				$is_free = function_exists('gfw_fs') && gfw_fs()->is_not_paying() && ! gfw_fs()->is_trial();
				$free_tabs = ['audience', 'acquisition', 'behavior'];
				$tab_meta = array(
					'realtime'          => array(__('Real-Time Visitors', 'ga-for-wp-text'),       __('Live visitor count, active pages, and real-time traffic sources.', 'ga-for-wp-text'), false),
					'audience'          => array(__('Audience & Demographics', 'ga-for-wp-text'),  __('Country, language, device, and age-group overview reports.', 'ga-for-wp-text'), false),
					'acquisition'       => array(__('Acquisition & Traffic', 'ga-for-wp-text'),    __('Channel, source/medium, and screen-size breakdown reports.', 'ga-for-wp-text'), false),
					'behavior'          => array(__('Behavior & Engagement', 'ga-for-wp-text'),    __('Page performance, average time on page, and demographic reports.', 'ga-for-wp-text'), false),
					'form_tracking'     => array(__('Form Tracking', 'ga-for-wp-text'),            __('Form views, submissions, and conversion rate broken down by form name.', 'ga-for-wp-text'), false),
					'video_tracking'    => array(__('Video Tracking', 'ga-for-wp-text'),           __('Video start count, completion rate, and average watch depth per video title.', 'ga-for-wp-text'), false),
					'conversion'        => array(__('WooCommerce Conversions', 'ga-for-wp-text'),  __('Product revenue, source revenue, device share, and regional sales.', 'ga-for-wp-text'), true),
					'journey'           => array(__('Customer Journey', 'ga-for-wp-text'),         __('User journey funnel and path analysis reports.', 'ga-for-wp-text'), true),
					'performance'       => array(__('Store Performance', 'ga-for-wp-text'),        __('Cart abandonment, checkout funnel, and average order value.', 'ga-for-wp-text'), true),
					'woo_subscriptions' => array(__('WooCommerce Subscriptions', 'ga-for-wp-text'), __('MRR, ARR, churn rate, renewals, and subscriber lifecycle analytics.', 'ga-for-wp-text'), true),
					'googleAds'         => array(__('Google Ads', 'ga-for-wp-text'),               __('Ad group cost, success, search query, and distribution network reports.', 'ga-for-wp-text'), false),
					'googleAdsense'     => array(__('Google AdSense', 'ga-for-wp-text'),           __('AdSense revenue and ad clicks broken down by page title.', 'ga-for-wp-text'), false),
					'content'           => array(__('Content Performance', 'ga-for-wp-text'),      __('Content performance based on author, categories, tags and other reports.', 'ga-for-wp-text'), false),
					'search_console'    => array(__('Search Console', 'ga-for-wp-text'),          __('Organic search queries, monthly trend, and device breakdown from Search Console.', 'ga-for-wp-text'), false),
					'utm_campaign'      => array(__('Campaign Performance', 'ga-for-wp-text'),    __('Sessions, engagement rate, conversions, and revenue by campaign source/medium and term.', 'ga-for-wp-text'), false),
					'click_tracking'    => array(__('Click Tracking', 'ga-for-wp-text'),         __('Outbound link clicks and file downloads, broken down by destination and page.', 'ga-for-wp-text'), false),
				);
			?>
				<form action="" method="POST">
					<div class="ga4wp-row ga4s-wrap">
						<div class="ga4wp-col s12">
							<div class="ga4s-section">
								<div class="ga4s-section-head">
									<span class="material-icons-round">dashboard</span>
									<div>
										<div class="ga4s-section-title"><?php _e('Dashboard Report Tabs', 'ga-for-wp-text'); ?></div>
										<div class="ga4s-section-desc"><?php _e('Choose which report tabs are visible on the Analytics Dashboard. Disabled tabs will be hidden from the dashboard navigation.', 'ga-for-wp-text'); ?></div>
									</div>
								</div>
								<?php if ($is_free) : ?>
									<div style="display:flex;align-items:center;gap:10px;padding:10px 14px;margin:0 0 16px;background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;">
										<span class="material-icons-round" style="color:#EA580C;font-size:18px;flex-shrink:0;">workspace_premium</span>
										<span style="font-size:12.5px;color:#9A3412;"><?php _e('Pro report tabs are locked. Upgrade to enable real-time, form tracking, ads, and WooCommerce reports.', 'ga-for-wp-text'); ?>
											<a href="<?php echo esc_url(gfw_fs()->get_upgrade_url()); ?>" style="font-weight:600;color:#EA580C;text-decoration:underline;margin-left:4px;"><?php _e('Upgrade', 'ga-for-wp-text'); ?></a>
										</span>
									</div>
								<?php endif; ?>
								<?php
								// Build unified display order: saved order (if any), then any default/SR tabs not yet listed.
								$saved_explore_reports = get_option('ga4wp_saved_explore_reports', []);
								$saved_order           = get_option('ga4wp_tab_order', []);
								$display_tabs          = $is_free ? $tab_meta : $defaults_dash_tabs;
								$display_order         = [];
								foreach ($saved_order as $_k) {
									if (array_key_exists($_k, $defaults_dash_tabs) || array_key_exists($_k, $saved_explore_reports)) $display_order[] = $_k;
								}
								foreach ($defaults_dash_tabs as $_k => $_v) {
									if (! in_array($_k, $display_order, true)) $display_order[] = $_k;
								}
								foreach ($saved_explore_reports as $_k => $_v) {
									if (! in_array($_k, $display_order, true)) $display_order[] = $_k;
								}
								$initial_tab_order = implode(',', $display_order);
								?>
								<div id="ga4wp-dash-tab-sortable" style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px;">
									<?php foreach ($display_order as $order_key) :
										if (array_key_exists($order_key, $display_tabs)) :
											$key    = $order_key;
											$meta   = $tab_meta[$key] ?? array(ucwords(str_replace('_', ' ', $key)), '', false);
											$is_woo = $meta[2];
											$is_pro = $is_free && ! in_array($key, $free_tabs, true);
											if ($is_woo && ! class_exists('WooCommerce')) continue;
									?>
											<div class="ga4wp-sortable-item<?php echo $is_pro ? ' ga4wp-pro-locked' : ''; ?>" data-tab-key="<?php echo esc_attr($key); ?>">
												<div class="ga4s-row">
													<?php if (! $is_pro) : ?>
														<span class="material-icons-round ga4wp-drag-handle" style="cursor:grab;color:#94A3B8;font-size:20px;margin-right:8px;flex-shrink:0;align-self:center;">drag_indicator</span>
													<?php else : ?>
														<span class="material-icons-round" style="color:#CBD5E1;font-size:20px;margin-right:8px;flex-shrink:0;align-self:center;">lock</span>
													<?php endif; ?>
													<div class="ga4s-row-info">
														<div class="ga4s-row-label">
															<?php echo esc_html($meta[0]); ?>
															<?php if ($is_woo) : ?><span class="new" style="background:var(--ga-teal);margin-left:6px;">WooCommerce</span><?php endif; ?>
															<?php if ($is_pro) : ?><span class="new" style="background:#F59E0B;margin-left:6px;">Pro</span><?php endif; ?>
														</div>
														<div class="ga4s-row-desc"><?php echo esc_html($meta[1]); ?></div>
													</div>
													<div class="switch">
														<label>
															<input type="checkbox"
																<?php if (! $is_pro) : ?>
																name="ga4wp_dashboard_settings[<?php echo esc_attr($key); ?>]" value="yes"
																<?php checked(! empty($ga4wp_dashboard_settings[$key])); ?>
																<?php else : ?>
																disabled checked
																<?php endif; ?>>
															<span class="lever"></span>
														</label>
													</div>
												</div>
											</div>
										<?php
										elseif (isset($saved_explore_reports[$order_key])) :
											$rid  = $order_key;
											$rcfg = $saved_explore_reports[$rid];
										?>
											<div class="ga4wp-sortable-item ga4wp-sortable-sr" data-tab-key="<?php echo esc_attr($rid); ?>">
												<div class="ga4s-row">
													<span class="material-icons-round ga4wp-drag-handle" style="cursor:grab;color:#94A3B8;font-size:20px;margin-right:4px;flex-shrink:0;align-self:center;">drag_indicator</span>
													<span class="material-icons-round" style="color:#2563EB;font-size:16px;margin-right:6px;flex-shrink:0;align-self:center;">bookmark</span>
													<div class="ga4s-row-info">
														<div class="ga4s-row-label"><?php echo esc_html($rcfg['name']); ?></div>
														<div class="ga4s-row-desc"><?php
																					if (! empty($rcfg['description'])) {
																						echo esc_html($rcfg['description']);
																					} else {
																						$parts = array_merge($rcfg['dimensions'] ?? [], $rcfg['metrics'] ?? []);
																						echo esc_html(implode(', ', array_slice($parts, 0, 5)) . (count($parts) > 5 ? '…' : ''));
																					}
																					?></div>
													</div>
													<div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
														<div class="switch"><label>
																<input type="checkbox" name="ga4wp_saved_report_enabled[<?php echo esc_attr($rid); ?>]" value="yes" <?php checked($rcfg['enabled'] ?? true); ?>>
																<span class="lever"></span>
															</label></div>
														<button type="button"
															data-ec="<?php echo esc_attr(wp_json_encode(['edit_id' => $rid, 'name' => $rcfg['name'], 'description' => $rcfg['description'] ?? '', 'dimensions' => $rcfg['dimensions'] ?? [], 'metrics' => $rcfg['metrics'] ?? [], 'start' => $rcfg['start'] ?? '30daysAgo', 'end' => $rcfg['end'] ?? 'yesterday', 'viz' => $rcfg['viz'] ?? 'table', 'limit' => (int)($rcfg['limit'] ?? 25)])); ?>"
															data-eu="<?php echo esc_attr(admin_url('admin.php?page=ga4wp_pro_plugin_options&view=explore__premium_only')); ?>"
															onclick="try{localStorage.setItem('ga4wp_explore_prefill',this.dataset.ec)}catch(e){}; window.location.href=this.dataset.eu;"
															style="display:inline-flex;align-items:center;gap:3px;padding:4px 6px;border:1px solid #BFDBFE;background:#EFF6FF;border-radius:6px;cursor:pointer;color:#2563EB;"
															title="<?php esc_attr_e('Edit in Explore', 'ga-for-wp-text'); ?>">
															<span class="material-icons-round" style="font-size:14px;">edit</span>
														</button>
														<button type="button" class="ga4wp-del-saved-report-cfg"
															data-id="<?php echo esc_attr($rid); ?>"
															data-nonce="<?php echo esc_attr(wp_create_nonce('ga4wp-tab-update')); ?>"
															style="display:inline-flex;align-items:center;padding:4px 6px;border:1px solid #FCA5A5;background:#FEF2F2;border-radius:6px;cursor:pointer;color:#E11D48;"
															title="<?php esc_attr_e('Delete', 'ga-for-wp-text'); ?>">
															<span class="material-icons-round" style="font-size:14px;">delete</span>
														</button>
													</div>
												</div>
											</div>
									<?php endif;
									endforeach; ?>
								</div>
							</div>
							<script>
								jQuery(function($) {
									$(document).on('click', '.ga4wp-del-saved-report-cfg', function() {
										if (!confirm(<?php echo json_encode(__('Delete this saved report? This cannot be undone.', 'ga-for-wp-text')); ?>)) return;
										var $item = $(this).closest('.ga4wp-sortable-item'),
											id = $(this).data('id'),
											nonce = $(this).data('nonce');
										$(this).prop('disabled', true);
										$.post(ga4wp_js_object.ajax_url, {
											action: 'ga4wp_delete_explore_report',
											security: nonce,
											report_id: id
										}, function(r) {
											if (r && r.success) {
												M.toast({
													html: <?php echo json_encode(__('Report deleted.', 'ga-for-wp-text')); ?>,
													classes: 'rounded teal',
													displayLength: 3000
												});
												$item.fadeOut(300, function() {
													$(this).remove();
												});
											} else {
												M.toast({
													html: (r && r.data) || 'Error',
													classes: 'rounded red',
													displayLength: 4000
												});
											}
										}, 'json');
									});
								});
							</script>
							<input type="hidden" name="ga4wp_tab_order" id="ga4wp_tab_order_input" value="<?php echo esc_attr($initial_tab_order); ?>">
							<style>
								#ga4wp-dash-tab-sortable .ga4wp-sortable-placeholder {
									background: #F1F5F9;
									border: 2px dashed #CBD5E1;
									border-radius: 10px;
									min-height: 68px;
									visibility: visible !important;
								}

								#ga4wp-dash-tab-sortable .ui-sortable-helper {
									box-shadow: 0 8px 28px rgba(0, 0, 0, .18);
									border-radius: 10px;
									background: #fff;
									opacity: .97;
								}

								.ga4wp-drag-handle:hover {
									color: #475569 !important;
								}

								.ga4wp-drag-handle:active {
									cursor: grabbing !important;
								}

								.ga4wp-pro-locked {
									opacity: .65;
									pointer-events: none;
									cursor: default;
								}

								.ga4wp-pro-locked .switch input[disabled]+.lever {
									background-color: #E2E8F0 !important;
								}

								.ga4wp-pro-locked .switch input[disabled]:checked+.lever {
									background-color: #FCD34D !important;
								}
							</style>
							<script>
								jQuery(function($) {
									var $grid = $('#ga4wp-dash-tab-sortable');
									if (!$grid.length || !$.fn.sortable) return;
									$grid.sortable({
										items: '.ga4wp-sortable-item:not(.ga4wp-pro-locked)',
										handle: '.ga4wp-drag-handle',
										helper: 'clone',
										appendTo: 'body',
										zIndex: 9999,
										tolerance: 'pointer',
										forceHelperSize: true,
										forcePlaceholderSize: true,
										placeholder: 'ga4wp-sortable-placeholder',
										start: function(e, ui) {
											ui.placeholder.css('height', ui.item.outerHeight() + 'px');
											ui.helper.css('width', ui.item.outerWidth() + 'px');
										},
										update: function() {
											var order = [];
											$('#ga4wp-dash-tab-sortable .ga4wp-sortable-item').each(function() {
												order.push($(this).data('tab-key'));
											});
											$('#ga4wp_tab_order_input').val(order.join(','));
										}
									}).disableSelection();
								});
							</script>
						</div>
					</div>
					<div class="ga4wp-col s12 ga4s-actions">
						<button class="ga4s-save-btn" type="submit" name="ga4wp_dashboard_settings[ga4wp_dashboard_submit]" value="submit">
							<span class="material-icons-round">save</span><?php _e('Save Dashboard Settings', 'ga-for-wp-text'); ?>
						</button>
					</div>
					<?php wp_nonce_field('ga4wp_dashboard_submit', 'ga4wp_nonce_header'); ?>
				</form>

				<?php } elseif (stripos($tab_id, 'custom-dims') !== false) {

				$is_free_dims = function_exists('gfw_fs') && gfw_fs()->is_not_paying() && ! gfw_fs()->is_trial();
				if ($is_free_dims) : ?>
					<div style="display:flex;flex-direction:column;gap:8px;padding:8px 0 0;">
						<div class="ga4wp-dash-pro-header" style="margin:0;">
							<span class="material-icons-round">data_object</span>
							<div>
								<strong><?php _e('Custom Dimensions — WordPress Data in GA4', 'ga-for-wp-text'); ?></strong>
								<span><?php _e('Register WordPress-specific dimensions in your GA4 property to segment reports by author, category, post type, user role, and more.', 'ga-for-wp-text'); ?></span>
							</div>
						</div>
						<style>
							.ga4wp-upsell-tiles .ga4wp-col {
								padding: 0
							}

							.ga4wp-upsell-tiles .ga4wp-info-box {
								margin-bottom: 0 !important;
								height: 100%;
								box-sizing: border-box
							}
						</style>
						<div class="ga4wp-upsell-tiles" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
							<div class="ga4wp-info-box valign-wrapper">
								<div class="ga4wp-col s3 l2"><span class="material-icons-round ga4wp-info-icon">person</span></div>
								<div class="ga4wp-col s9 l10">
									<p class="ga4wp-info-title"><?php _e('Author & User Tracking', 'ga-for-wp-text'); ?></p>
									<p class="ga4wp-info-description"><?php _e('Send Author ID and User ID dimensions so GA4 reports can be filtered by content creator or logged-in user.', 'ga-for-wp-text'); ?></p>
								</div>
							</div>
							<div class="ga4wp-info-box valign-wrapper">
								<div class="ga4wp-col s3 l2"><span class="material-icons-round ga4wp-info-icon">sell</span></div>
								<div class="ga4wp-col s9 l10">
									<p class="ga4wp-info-title"><?php _e('Post Category & Tag', 'ga-for-wp-text'); ?></p>
									<p class="ga4wp-info-description"><?php _e('Track which categories and tags drive the most traffic — enabling content-strategy reports inside GA4.', 'ga-for-wp-text'); ?></p>
								</div>
							</div>
							<div class="ga4wp-info-box valign-wrapper">
								<div class="ga4wp-col s3 l2"><span class="material-icons-round ga4wp-info-icon">article</span></div>
								<div class="ga4wp-col s9 l10">
									<p class="ga4wp-info-title"><?php _e('Post Type Dimension', 'ga-for-wp-text'); ?></p>
									<p class="ga4wp-info-description"><?php _e('Distinguish between posts, pages, products, and custom post types in your GA4 reports.', 'ga-for-wp-text'); ?></p>
								</div>
							</div>
							<div class="ga4wp-info-box valign-wrapper">
								<div class="ga4wp-col s3 l2"><span class="material-icons-round ga4wp-info-icon">admin_panel_settings</span></div>
								<div class="ga4wp-col s9 l10">
									<p class="ga4wp-info-title"><?php _e('User Role Dimension', 'ga-for-wp-text'); ?></p>
									<p class="ga4wp-info-description"><?php _e('See how admins, editors, subscribers, and guests behave differently on your site.', 'ga-for-wp-text'); ?></p>
								</div>
							</div>
							<div class="ga4wp-info-box valign-wrapper">
								<div class="ga4wp-col s3 l2"><span class="material-icons-round ga4wp-info-icon">dynamic_form</span></div>
								<div class="ga4wp-col s9 l10">
									<p class="ga4wp-info-title"><?php _e('Form & Subscription Dimensions', 'ga-for-wp-text'); ?></p>
									<p class="ga4wp-info-description"><?php _e('Pass Form ID, Form Name, Conversion Type, and Subscription ID alongside events for richer reporting.', 'ga-for-wp-text'); ?></p>
								</div>
							</div>
							<div class="ga4wp-info-box valign-wrapper">
								<div class="ga4wp-col s3 l2"><span class="material-icons-round ga4wp-info-icon">auto_fix_high</span></div>
								<div class="ga4wp-col s9 l10">
									<p class="ga4wp-info-title"><?php _e('One-Click GA4 Registration', 'ga-for-wp-text'); ?></p>
									<p class="ga4wp-info-description"><?php _e('Generate all required custom dimensions directly in your GA4 property from inside WordPress — no manual setup needed.', 'ga-for-wp-text'); ?></p>
								</div>
							</div>
						</div>
						<div class="center-align" style="padding:16px 0 24px;">
							<p style="color:var(--ga-text2);font-size:13px;margin-bottom:14px;"><?php _e('Upgrade to Pro to enable Custom Dimensions and unlock WordPress-native GA4 segmentation.', 'ga-for-wp-text'); ?></p>
							<a class="btn upgrade-btn waves-effect waves-light" href="<?php echo esc_url(gfw_fs()->get_upgrade_url()); ?>" target="_blank">
								<span class="material-icons-round left">workspace_premium</span><?php _e('Upgrade to Pro', 'ga-for-wp-text'); ?>
							</a>
						</div>
					</div>
				<?php else :
					if (method_exists($this, 'render_custom_dimensions_settings')) {
						$this->render_custom_dimensions_settings();
					}
				endif;
			} elseif (stripos($tab_id, 'widgets') !== false) {
				$widget_settings = get_option('ga4wp_widget_settings', []);
				$widgets = [
					'overview_report'  => ['title' => __('Overview Report', 'ga-for-wp-text'),   'desc' => __('Users and new users trend line chart on the WordPress dashboard.', 'ga-for-wp-text'),           'icon' => 'show_chart'],
					'country_report'   => ['title' => __('Users by Country', 'ga-for-wp-text'),  'desc' => __('Bar chart showing user distribution by country.', 'ga-for-wp-text'),                          'icon' => 'public'],
					'language_report'  => ['title' => __('Users by Language', 'ga-for-wp-text'), 'desc' => __('Bar chart showing user distribution by browser language.', 'ga-for-wp-text'),                 'icon' => 'translate'],
					'device_report'    => ['title' => __('Users by Device', 'ga-for-wp-text'),   'desc' => __('Donut chart showing user distribution by device category.', 'ga-for-wp-text'),               'icon' => 'devices'],
					'quick_stats'      => ['title' => __('Quick Stats', 'ga-for-wp-text'),        'desc' => __('Summary cards for sessions, pageviews, and total users at a glance.', 'ga-for-wp-text'),     'icon' => 'speed'],
				];
				?>
				<form action="" method="POST">
					<div class="ga4wp-row ga4s-wrap">
						<div class="ga4wp-col s12">
							<div class="ga4s-section">
								<div class="ga4s-section-head">
									<span class="material-icons-round">widgets</span>
									<div>
										<div class="ga4s-section-title"><?php _e('WordPress Dashboard Widgets', 'ga-for-wp-text'); ?></div>
										<div class="ga4s-section-desc"><?php _e('Choose which TrueAna widgets are shown on the WordPress admin dashboard.', 'ga-for-wp-text'); ?></div>
									</div>
								</div>
								<div class="ga4s-rows" style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px;">
									<?php foreach ($widgets as $key => $w) :
										$enabled = ! isset($widget_settings[$key]) || $widget_settings[$key] !== 'no'; ?>
										<div class="ga4s-row">
											<div class="ga4s-row-info">
												<div class="ga4s-row-label">
													<span class="material-icons-round" style="font-size:15px;vertical-align:middle;margin-right:4px;color:#2563EB;"><?php echo esc_html($w['icon']); ?></span>
													<?php echo esc_html($w['title']); ?>
												</div>
												<div class="ga4s-row-desc"><?php echo esc_html($w['desc']); ?></div>
											</div>
											<div class="switch">
												<label>
													<input type="checkbox" name="ga4wp_widget_settings[<?php echo esc_attr($key); ?>]" value="yes" <?php checked($enabled); ?>>
													<span class="lever"></span>
												</label>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					</div>
					<div class="ga4wp-col s12 ga4s-actions">
						<button class="ga4s-save-btn" type="submit" name="ga4wp_widget_submit" value="submit">
							<span class="material-icons-round">save</span><?php _e('Save Widget Settings', 'ga-for-wp-text'); ?>
						</button>
					</div>
					<?php wp_nonce_field('ga4wp_widget_submit', 'ga4wp_nonce_header'); ?>
				</form>

				<?php } elseif (stripos($tab_id, 'email') !== false) {
				$is_free_email = function_exists('gfw_fs') && gfw_fs()->is_not_paying() && ! gfw_fs()->is_trial();
				if ($is_free_email) : ?>
					<div style="display:flex;flex-direction:column;gap:8px;padding:8px 0 0;">
						<div class="ga4wp-dash-pro-header" style="margin:0;">
							<span class="material-icons-round">email</span>
							<div>
								<strong><?php _e('Email Analytics Reports — Scheduled Summaries', 'ga-for-wp-text'); ?></strong>
								<span><?php _e('Receive automated GA4 analytics summaries in your inbox on a daily, weekly, or monthly schedule — without opening WordPress.', 'ga-for-wp-text'); ?></span>
							</div>
						</div>
						<style>
							.ga4wp-upsell-tiles .ga4wp-col {
								padding: 0
							}

							.ga4wp-upsell-tiles .ga4wp-info-box {
								margin-bottom: 0 !important;
								height: 100%;
								box-sizing: border-box
							}
						</style>
						<div class="ga4wp-upsell-tiles" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
							<div class="ga4wp-info-box valign-wrapper">
								<div class="ga4wp-col s3 l2"><span class="material-icons-round ga4wp-info-icon">schedule_send</span></div>
								<div class="ga4wp-col s9 l10">
									<p class="ga4wp-info-title"><?php _e('Scheduled Email Reports', 'ga-for-wp-text'); ?></p>
									<p class="ga4wp-info-description"><?php _e('Set daily, weekly, or monthly reports to deliver automatically — no manual checks required.', 'ga-for-wp-text'); ?></p>
								</div>
							</div>
							<div class="ga4wp-info-box valign-wrapper">
								<div class="ga4wp-col s3 l2"><span class="material-icons-round ga4wp-info-icon">groups</span></div>
								<div class="ga4wp-col s9 l10">
									<p class="ga4wp-info-title"><?php _e('Multiple Recipients', 'ga-for-wp-text'); ?></p>
									<p class="ga4wp-info-description"><?php _e('Send reports to your entire team — add as many email addresses as you need.', 'ga-for-wp-text'); ?></p>
								</div>
							</div>
							<div class="ga4wp-info-box valign-wrapper">
								<div class="ga4wp-col s3 l2"><span class="material-icons-round ga4wp-info-icon">checklist</span></div>
								<div class="ga4wp-col s9 l10">
									<p class="ga4wp-info-title"><?php _e('Choose Report Sections', 'ga-for-wp-text'); ?></p>
									<p class="ga4wp-info-description"><?php _e('Select which sections to include: Audience, Acquisition, Behavior, Conversions, and more.', 'ga-for-wp-text'); ?></p>
								</div>
							</div>
							<div class="ga4wp-info-box valign-wrapper">
								<div class="ga4wp-col s3 l2"><span class="material-icons-round ga4wp-info-icon">tune</span></div>
								<div class="ga4wp-col s9 l10">
									<p class="ga4wp-info-title"><?php _e('Custom Metrics Selection', 'ga-for-wp-text'); ?></p>
									<p class="ga4wp-info-description"><?php _e('Pick exactly which KPIs appear in your email — users, sessions, pageviews, revenue, and more.', 'ga-for-wp-text'); ?></p>
								</div>
							</div>
							<div class="ga4wp-info-box valign-wrapper">
								<div class="ga4wp-col s3 l2"><span class="material-icons-round ga4wp-info-icon">compare_arrows</span></div>
								<div class="ga4wp-col s9 l10">
									<p class="ga4wp-info-title"><?php _e('Period Comparison', 'ga-for-wp-text'); ?></p>
									<p class="ga4wp-info-description"><?php _e('Each report compares the current period to the previous one so trends are immediately visible.', 'ga-for-wp-text'); ?></p>
								</div>
							</div>
							<div class="ga4wp-info-box valign-wrapper">
								<div class="ga4wp-col s3 l2"><span class="material-icons-round ga4wp-info-icon">preview</span></div>
								<div class="ga4wp-col s9 l10">
									<p class="ga4wp-info-title"><?php _e('Test Before You Send', 'ga-for-wp-text'); ?></p>
									<p class="ga4wp-info-description"><?php _e('Send a test email at any time to preview exactly what your team will receive in their inbox.', 'ga-for-wp-text'); ?></p>
								</div>
							</div>
						</div>
						<div class="center-align" style="padding:16px 0 24px;">
							<p style="color:var(--ga-text2);font-size:13px;margin-bottom:14px;"><?php _e('Upgrade to Pro to enable scheduled Email Analytics Reports for your team.', 'ga-for-wp-text'); ?></p>
							<a class="btn upgrade-btn waves-effect waves-light" href="<?php echo esc_url(gfw_fs()->get_upgrade_url()); ?>" target="_blank">
								<span class="material-icons-round left">workspace_premium</span><?php _e('Upgrade to Pro', 'ga-for-wp-text'); ?>
							</a>
						</div>
					</div>
<?php else :
					if (method_exists($this, 'render_email_settings_tab')) {
						$this->render_email_settings_tab();
					}
				endif;
			}
		} else {
			if (method_exists($this, 'handle_premium_dashboard_tab') && $this->handle_premium_dashboard_tab($tab_id)) {
				return;
			}

			if (GA4WP_Auth::is_reauth_required()) {
				$auth_url = admin_url('admin.php?page=ga4wp_pro_plugin_options&view=auth');
				echo '<script>window.location.href = ' . wp_json_encode($auth_url) . ';</script>';
				wp_die();
			}

			$property_id         = $this->get_ga_property_id();
			$ga4wp_dash_settings = $this->get_current_dash_settings();
			$currency_symbol     = class_exists('WooCommerce') ? get_woocommerce_currency_symbol() : '$';
			$missing_dims_notice = '';
			$preflight_filtered  = false;

			if (! empty($ga4wp_dash_settings['report_view'])) {
				$new_api         = $this->get_google_report_api();
				$transient_name  = $this->ga4wp_create_transient_name($ga4wp_dash_settings['report_view'], $property_id, $ga4wp_dash_settings['report_from'], $ga4wp_dash_settings['report_to'], $tab_id);
				$transient_value = get_transient($transient_name);
				if ($transient_value == false) {
					$transient_value = $new_api->get_dashboard_data($ga4wp_dash_settings['report_view'], $ga4wp_dash_settings['report_from'], $ga4wp_dash_settings['report_to'], $tab_id);
					if (isset($transient_value) && is_array($transient_value)) set_transient($transient_name, $transient_value, 120);
				}
				$stats_data       = $transient_value;
				$chart_data_array = GA4WP_Settings::get_instance()->{'ga4wp_report_chart_data_' . $tab_id};
			} else {
				$new_api = $this->get_google_analytics_data_api();

				// Pre-flight: remove any per-report custom dimension that hasn't been created
				// in GA4 yet. GA4 rejects the entire batchRunReports call if even one report
				// references an unregistered customEvent: dimension, so we filter those reports
				// out before building the batch. If nothing remains, show an actionable notice.
				if (in_array($tab_id, ['form_tracking', 'content'], true)) {
					$generated    = (string) get_option('custom_dimension_generated', '');
					$settings_obj = GA4WP_Settings::get_instance();
					$req_key      = 'ga4wp_report_request_ga4_' . $tab_id;
					$chart_key    = 'ga4wp_report_chart_data_ga4_' . $tab_id;
					$req_arr      = (array) ($settings_obj->$req_key   ?: []);
					$chart_arr    = (array) ($settings_obj->$chart_key ?: []);
					$dim_labels   = [
						'ga4wp_form_name'  => __('Form Name',     'ga-for-wp-text'),
						'ga4wp_form_id'    => __('Form ID',       'ga-for-wp-text'),
						'ga4wp_author_id'  => __('Author ID',     'ga-for-wp-text'),
						'ga4wp_category'   => __('Post Category', 'ga-for-wp-text'),
						'ga4wp_tag'        => __('Post Tag',      'ga-for-wp-text'),
						'ga4wp_post_type'  => __('Post Type',     'ga-for-wp-text'),
					];
					$missing_labels = [];

					foreach ($req_arr as $report_name => $params) {
						$raw_dim = is_array($params) ? ($params[1] ?? '') : '';
						if (strpos($raw_dim, 'customEvent:') === false) continue;
						$dim_key = substr($raw_dim, strlen('customEvent:'));
						if ($dim_key && stripos($generated, $dim_key) === false) {
							unset($req_arr[$report_name]);
							unset($chart_arr[$report_name]);
							$missing_labels[]   = $dim_labels[$dim_key] ?? $dim_key;
							$preflight_filtered = true;
						}
					}

					if (! empty($missing_labels)) {
						$status_url   = admin_url('admin.php?page=ga4wp_pro_plugin_options&view=status');
						$missing_list = implode(', ', $missing_labels);

						if (empty($req_arr)) {
							// Nothing left to fetch — show a full actionable notice and stop.
							echo '<div class="ga4wp-row"><div class="ga4wp-col s12">';
							echo '<div style="margin:32px auto;max-width:680px;background:#FFF7ED;border:1px solid #FED7AA;border-radius:14px;padding:28px 32px;">';
							echo '<div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">';
							echo '<span class="material-icons-round" style="color:#EA580C;font-size:26px;">data_object</span>';
							echo '<span style="font-size:15px;font-weight:700;color:#0F172A;">' . esc_html__('Custom Dimensions Required', 'ga-for-wp-text') . '</span>';
							echo '</div>';
							echo '<p style="font-size:13px;color:#475569;margin:0 0 14px;">';
							echo wp_kses(
								sprintf(
									/* translators: %s: comma-separated list of dimension names */
									__('This report requires the following custom dimensions to be registered in your GA4 property before data can appear: <strong>%s</strong>.', 'ga-for-wp-text'),
									esc_html($missing_list)
								),
								['strong' => []]
							);
							echo '</p>';
							echo '<a href="' . esc_url($status_url) . '" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#EA580C;color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">';
							echo '<span class="material-icons-round" style="font-size:16px;">open_in_new</span>';
							echo esc_html__('Go to Status Tab to Create Them', 'ga-for-wp-text');
							echo '</a>';
							echo '</div></div></div>';
							wp_die();
						}

						// Partial: some dims present — show a soft banner alongside the data.
						$missing_dims_notice  = '<div class="ga4wp-col s12" style="margin-bottom:12px;">';
						$missing_dims_notice .= '<div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;padding:14px 18px;display:flex;align-items:center;gap:10px;font-size:12px;color:#92400E;">';
						$missing_dims_notice .= '<span class="material-icons-round" style="font-size:18px;color:#EA580C;flex-shrink:0;">info</span>';
						$missing_dims_notice .= '<span>';
						$missing_dims_notice .= wp_kses(
							sprintf(
								/* translators: %s: comma-separated list of dimension names */
								__('Some reports are hidden because these custom dimensions are not yet created in GA4: <strong>%s</strong>.', 'ga-for-wp-text'),
								esc_html($missing_list)
							),
							['strong' => []]
						);
						$missing_dims_notice .= ' <a href="' . esc_url($status_url) . '" style="color:#EA580C;font-weight:600;">' . esc_html__('Create them in the Status tab.', 'ga-for-wp-text') . '</a>';
						$missing_dims_notice .= '</span></div></div>';

						// Override the settings singleton so get_dashboard_data() only batches
						// the reports whose dimensions exist. Positional indices in $stats_data
						// must match $chart_arr — overriding both keeps them in sync.
						$settings_obj->$req_key   = $req_arr;
						$settings_obj->$chart_key = $chart_arr;
					}
				}

				$transient_name  = $this->ga4wp_create_transient_name(false, $property_id, $ga4wp_dash_settings['report_from'], $ga4wp_dash_settings['report_to'], $tab_id);
				// If we filtered reports out, bust the transient: stale cached data may have
				// more positional slots than $chart_data_array, causing index mismatches.
				if ($preflight_filtered) {
					delete_transient($transient_name);
				}
				$transient_value = get_transient($transient_name);
				if ($transient_value == false) {
					$transient_value = $new_api->get_dashboard_data($ga4wp_dash_settings['report_from'], $ga4wp_dash_settings['report_to'], $tab_id);
					if (isset($transient_value) && is_array($transient_value)) set_transient($transient_name, $transient_value, 120);
				}
				$stats_data       = $transient_value;
				$chart_data_array = GA4WP_Settings::get_instance()->{'ga4wp_report_chart_data_ga4_' . $tab_id};
			}

			$i = 0;
			if (! empty($stats_data) && ! empty($chart_data_array)) {
				echo '<div class="ga4wp-row">';
				if ($missing_dims_notice) echo $missing_dims_notice;
				foreach ($chart_data_array as $chart_name => $chart_parameters) {
					if ($chart_name == 'stats') {
						if (! empty($ga4wp_dash_settings['report_view'])) {
							$stats_array = GA4WP_Settings::get_instance()->{'ga4wp_dash_stats_data_' . $tab_id};
						} else {
							$stats_array = GA4WP_Settings::get_instance()->{'ga4wp_dash_stats_data_ga4_' . $tab_id};
						}
						if (empty($stats_array) || ! is_array($stats_array)) {
							continue;
						}
						echo '<div class="ga4wp-col s12 chart_box">';
						$this->publish_stat_data($stats_data[$i], $stats_array, $currency_symbol);
						echo '</div>';
					} else {
						if (! isset($stats_data[$i]) || ! is_array($stats_data[$i])) $stats_data[$i] = [];
						$full  = in_array($chart_parameters[1], ['Total Users on Date', 'Overview Report', 'Search Queries Report', 'Campaign Performance by Source/Medium', 'Campaign Performance by Term', 'Outbound Click Tracking', 'File Download Tracking']);
						$col   = $full ? 's12' : 's12 m6 l6';
						if ($chart_parameters[0] == 'bar') {
							echo '<div class="ga4wp-col ' . $col . ' chart_box">';
							$this->publish_simple_bar_chart('chartdiv' . $i . $tab_id, $chart_parameters[1], $chart_parameters[2], $chart_parameters[3], $stats_data[$i], $chart_parameters[4]);
							echo '</div>';
						} elseif ($chart_parameters[0] == 'line') {
							echo '<div class="ga4wp-col ' . $col . ' chart_box">';
							$this->publish_simple_line_chart('chartdiv' . $i . $tab_id, $chart_parameters[1], $chart_parameters[2], $chart_parameters[3], $stats_data[$i], $chart_parameters[4]);
							echo '</div>';
						} elseif ($chart_parameters[0] == 'doughnut') {
							echo '<div class="ga4wp-col s12 m6 l6 chart_box">';
							$this->publish_simple_doughnut_chart('chartdiv' . $i . $tab_id, $chart_parameters[1], $stats_data[$i], $chart_parameters[4]);
							echo '</div>';
						} elseif ($chart_parameters[0] == 'large_table') {
							echo '<div class="ga4wp-col s12 chart_box">';
							$this->publish_simple_table('chartdiv' . $i . $tab_id, $chart_parameters[1], $chart_parameters[2], $chart_parameters[3], $stats_data[$i], $chart_parameters[4]);
							echo '</div>';
						} elseif ($chart_parameters[0] == 'table') {
							echo '<div class="ga4wp-col ' . $col . ' chart_box">';
							if ($chart_name === 'bestAuthor') {
								$resolved = [];
								foreach ($stats_data[$i] as $author_key => $val) {
									if ($author_key === '(not set)') continue;
									$user = is_numeric($author_key) ? get_userdata((int) $author_key) : false;
									$resolved[$user ? $user->display_name : $author_key] = $val;
								}
								$stats_data[$i] = $resolved;
							} elseif (in_array($chart_name, ['bestCategory', 'bestTag', 'bestPostType'])) {
								$stats_data[$i] = array_filter($stats_data[$i], fn($k) => $k !== '(not set)', ARRAY_FILTER_USE_KEY);
							}
							$this->publish_simple_table('chartdiv' . $i . $tab_id, $chart_parameters[1], $chart_parameters[2], $chart_parameters[3], $stats_data[$i], $chart_parameters[4]);
							echo '</div>';
						} elseif ($chart_parameters[0] == 'funnel') {
							echo '<div class="ga4wp-col s12 chart_box">';
							$this->publish_simple_funnel_chart('chartdiv' . ($i + 1) . $tab_id, $chart_parameters[1], $chart_parameters[2], $chart_parameters[3], $stats_data[$i], $chart_parameters[4]);
							echo '</div>';
						}
					}
					$i++;
				}
				echo '</div>';
			} else {
				$api_error   = property_exists($new_api, 'last_error') ? ($new_api->last_error ?? '') : '';
				$reauth_url  = admin_url('admin.php?page=ga4wp_pro_plugin_options&view=auth');
				$needs_reauth = false;
				if (! empty($api_error)) {
					$err = strtolower($api_error);
					if (strpos($err, 'unauthenticated') !== false || strpos($err, 'invalid_grant') !== false || strpos($err, 'token') !== false) {
						// GA4WP_Auth::is_reauth_required() is the single authoritative signal for
						// whether reconnecting is genuinely required (Google's own invalid_grant, or
						// two consecutive malformed refresh-proxy responses — see its docblock). A
						// bare "unauthenticated"/token error here just means THIS request's access
						// token happened to be stale; refresh_access_token() already retries that
						// automatically via the proxy on the next request, so treating every such
						// error as "please reconnect" ignored the very recoverability the proxy is
						// built to provide.
						if (GA4WP_Auth::is_reauth_required()) {
							$err_desc     = __('Your access token has expired or been revoked. Please re-link your Google Analytics account from the Authentication tab.', 'ga-for-wp-text');
							$needs_reauth = true;
						} elseif (stripos(GA4WP_Auth::get_last_refresh_error(), 'proxy returned http 404') !== false) {
							// The proxy route itself is missing/unreachable — a bigger deal than a
							// single stale token, so this gets its own, more specific message.
							$err_desc = __('There was a temporary problem authenticating with Google Analytics. May be proxy is down for while please try again in 15 min.', 'ga-for-wp-text');
						} else {
							$err_desc = __('There was a temporary problem authenticating with Google Analytics. This usually resolves itself within 2-3 minutes as your access token is automatically refreshed — please reload this tab. If it keeps happening, you can <b>un-link</b> your account first and then re-link again from the Authentication tab.', 'ga-for-wp-text');
						}
					} elseif (strpos($err, 'permission_denied') !== false || strpos($err, '403') !== false) {
						$err_desc     = __('Permission denied. Your account may lack the required Analytics scopes. Please re-link and grant both read and edit permissions.', 'ga-for-wp-text');
						$needs_reauth = true;
					} elseif (strpos($err, 'invalid_argument') !== false) {
						$_status_url = admin_url('admin.php?page=ga4wp_pro_plugin_options&view=status');
						if ($tab_id === 'form_tracking') {
							$err_desc = sprintf(
								/* translators: %s: URL to Status tab */
								__('The Form Tracking report requires the <strong>Form Name</strong> and <strong>Form ID</strong> custom dimensions to be registered in your GA4 property. <a href="%s">Visit the Status tab to create them automatically.</a>', 'ga-for-wp-text'),
								esc_url($_status_url)
							);
						} elseif ($tab_id === 'content') {
							$err_desc = sprintf(
								/* translators: %s: URL to Status tab */
								__('The Content Performance report requires custom dimensions (Author ID, Post Category, Post Tag, Post Type) to be registered in your GA4 property. <a href="%s">Visit the Status tab to create them automatically.</a>', 'ga-for-wp-text'),
								esc_url($_status_url)
							);
						} elseif ($tab_id === 'search_console') {
							$err_desc = __('The Search Console report requires Search Console to be linked to your GA4 property. In Google Analytics, go to Admin > Product Links > Search Console Links to connect it, then check back once data starts flowing in (this can take a day or two after linking).', 'ga-for-wp-text');
						} elseif ($tab_id === 'click_tracking') {
							$err_desc = __('The Click Tracking report requires "Outbound clicks" and "File downloads" enabled under GA4 Admin > Data Streams > Enhanced measurement.', 'ga-for-wp-text');
						} else {
							$err_desc = __('Invalid configuration. One or more dimensions or metrics are unsupported for this report. Check your custom dimension settings.', 'ga-for-wp-text');
						}
					} elseif (strpos($err, 'not_found') !== false || strpos($err, '404') !== false) {
						$err_desc = __('The requested Google Analytics property was not found. Verify your property ID in Authentication settings.', 'ga-for-wp-text');
					} else {
						$err_desc = $api_error;
					}
					$show_raw = ($err_desc !== $api_error);
				} else {
					$err_desc = __('No data was returned for this report. This can happen if the date range has no traffic, or if a required linked service (e.g. Google Ads or AdSense) is not connected to your GA4 property.', 'ga-for-wp-text');
					$show_raw = false;
				}
				echo '<div style="margin:32px auto;max-width:680px;background:#fff;border:1px solid #FECDD3;border-radius:14px;padding:28px 32px;">';
				echo '  <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">';
				echo '    <span class="material-icons-round" style="color:#E11D48;font-size:26px;">error_outline</span>';
				echo '    <span style="font-size:15px;font-weight:700;color:#0F172A;">' . esc_html__('Data could not be loaded', 'ga-for-wp-text') . '</span>';
				echo '  </div>';
				echo '  <p style="font-size:13px;color:#475569;margin:0 0 10px;">' . wp_kses($err_desc, ['strong' => [], 'b' => [], 'a' => ['href' => [], 'style' => []], 'br' => []]) . '</p>';
				if ($needs_reauth) {
					echo '  <a href="' . esc_url($reauth_url) . '" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#E11D48;color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;margin-bottom:4px;">';
					echo '    <span class="material-icons-round" style="font-size:16px;">link</span>';
					echo esc_html__('Re-authenticate Now', 'ga-for-wp-text');
					echo '  </a>';
				}
				if ($show_raw) {
					echo '  <details style="margin-top:12px;"><summary style="font-size:12px;font-weight:600;color:#64748B;cursor:pointer;">' . esc_html__('Show API error details', 'ga-for-wp-text') . '</summary>';
					echo '  <pre style="margin-top:8px;font-size:11px;color:#64748B;background:#F8FAFC;border:1px solid #E4E8F2;border-radius:8px;padding:10px 14px;overflow:auto;white-space:pre-wrap;word-break:break-all;">' . esc_html($api_error) . '</pre></details>';
				}
				echo '</div>';
			}
			echo '</div>';
		}
		wp_die();
	}

	/* ── GA4 Account List ──────────────────────────────────── */

	/**
	 * AJAX handler that returns the list of GA4 accounts available to the
	 * linked Google user.
	 *
	 * Tries, in order: the Admin API account list, account summaries, and
	 * finally the locally cached `ga_properties` option (resolving each
	 * cached property to its parent account) as a last-resort fallback.
	 * PHP warnings raised while calling the API are captured and logged
	 * rather than allowed to corrupt the JSON response. Sends a JSON
	 * success response with an array of `resourceName => displayName`
	 * pairs (possibly empty), or a JSON error response if the API/token
	 * connection is unavailable.
	 */
	public function ajax_get_ga4_accounts()
	{
		check_ajax_referer('ga4wp_create_property_nonce', 'nonce');
		if (! current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized');
			return;
		}

		$accounts = array();
		$warnings = [];

		set_error_handler(function ($errno, $errstr) use (&$warnings) {
			$warnings[] = $errstr;
			return true;
		});

		try {
			$api = $this->get_google_management_api();
			if (! $api) {
				restore_error_handler();
				wp_send_json_error(__('Could not connect to Google Analytics. Please re-link your account.', 'ga-for-wp-text'));
				return;
			}

			$result = $api->get_ga4_accounts_list();
			if (is_array($result) && ! empty($result['accounts']) && is_array($result['accounts'])) {
				foreach ($result['accounts'] as $acc) {
					if (! empty($acc['name']) && ! empty($acc['displayName'])) $accounts[$acc['name']] = $acc['displayName'];
				}
			}

			if (empty($accounts)) {
				$obj = $api->get_g4_account_summaries();
				if (! empty($obj) && isset($obj->accountSummaries)) {
					foreach ($obj->accountSummaries as $acc) {
						if (! empty($acc->account) && ! empty($acc->displayName)) $accounts[$acc->account] = $acc->displayName;
					}
				}
			}

			if (empty($accounts)) {
				$cached_props = get_option('ga_properties', array());
				if (! empty($cached_props) && is_array($cached_props)) {
					foreach ($cached_props as $prop_group) {
						if (! is_array($prop_group)) continue;
						foreach (array_keys($prop_group) as $stream_key) {
							$parts            = explode('|', $stream_key);
							$stream_resource  = $parts[0] ?? '';
							$segs             = explode('/', $stream_resource);
							if (count($segs) >= 2 && $segs[0] === 'properties') {
								$property_resource = $segs[0] . '/' . $segs[1];
								$prop_data         = $api->get_ga4_property($property_resource);
								if (is_array($prop_data) && ! empty($prop_data['account']) && ! empty($prop_data['displayName'])) {
									$accounts[$prop_data['account']] = $prop_data['displayName'];
								}
								break 2;
							}
						}
					}
				}
			}
		} catch (\Throwable $e) {
			error_log('TrueAna ajax_get_ga4_accounts exception: ' . $e->getMessage());
		}

		restore_error_handler();

		if (! empty($warnings)) {
			error_log('TrueAna get_ga4_accounts PHP warnings: ' . implode('; ', $warnings));
		}

		wp_send_json_success($accounts);
	}

	/* ── Create GA4 Account ────────────────────────────────── */

	/**
	 * AJAX handler that creates a new Google Analytics account.
	 *
	 * Reads `account_name` and `region_code` from $_POST, requires an
	 * account name, and calls the Management API to create the account.
	 * Sends a JSON error (distinguishing a permission/scope failure from
	 * a generic API failure) on failure, or a JSON success response with
	 * the API result on success.
	 */
	public function ajax_create_ga4_account()
	{
		check_ajax_referer('ga4wp_create_property_nonce', 'nonce');
		if (! current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized');
			return;
		}

		$name   = sanitize_text_field(wp_unslash($_POST['account_name'] ?? ''));
		$region = sanitize_text_field(wp_unslash($_POST['region_code']  ?? 'US'));
		if (empty($name)) {
			wp_send_json_error(__('Account name is required.', 'ga-for-wp-text'));
			return;
		}

		$api = $this->get_google_management_api();
		if (! $api) {
			wp_send_json_error(__('Could not connect to Google Analytics. Please re-link your account.', 'ga-for-wp-text'));
			return;
		}

		$redirect_uri = admin_url('admin.php?page=ga4wp_pro_plugin_options&view=auth');
		$result       = $api->create_ga4_account($name, $region, $redirect_uri);
		if (empty($result) || $result['type'] === 'error') {
			$http_code = $result['http_code'] ?? 0;
			$api_msg   = $result['message']   ?? '';
			if ($http_code === 403 || stripos($api_msg, 'permission') !== false || stripos($api_msg, 'forbidden') !== false) {
				wp_send_json_error(array('reason' => 'scope', 'message' => sprintf(__('Permission denied (HTTP 403). Your linked Google account may lack the analytics.edit scope. Try re-linking your account. API error: %s', 'ga-for-wp-text'), $api_msg)));
			} else {
				if ($api_msg) {
					/* translators: 1: HTTP status code, 2: raw API error message */
					$message = sprintf(__('Google API error (HTTP %1$d): %2$s', 'ga-for-wp-text'), $http_code, $api_msg);
				} else {
					$message = __('Account creation failed. You can create an account directly at analytics.google.com.', 'ga-for-wp-text');
				}
				wp_send_json_error(array('reason' => 'api', 'message' => $message));
			}
			return;
		}
		wp_send_json_success($result);
	}

	/* ── Create GA4 Property + Web Stream ─────────────────── */

	/**
	 * AJAX handler that creates a new GA4 property and its web data stream
	 * in one step.
	 *
	 * Reads `account`, `property_name`, `timezone`, `currency`,
	 * `website_url`, and `enhanced_measurement` from $_POST, validates the
	 * required fields, creates the property via the Management API, then
	 * creates a web data stream on it and optionally patches on enhanced
	 * measurement. Clears the cached `ga_properties` option on success so
	 * the new property/stream is picked up on next fetch. PHP
	 * warnings/notices raised during the process are captured and logged
	 * instead of leaking into the JSON response. Sends a JSON error with
	 * a descriptive message on any failure, or a JSON success response
	 * with the new `property_id` (stream resource + measurement id),
	 * `measurement_id`, and `display_name`.
	 */
	public function ajax_create_ga4_property()
	{
		check_ajax_referer('ga4wp_create_property_nonce', 'nonce');
		if (! current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized');
			return;
		}

		$error    = null;
		$result   = null;
		$warnings = [];

		/* Capture PHP warnings/notices so they cannot corrupt the JSON body.
		   wp_send_json_* sets Content-Type:application/json; any stray output
		   before the JSON causes jQuery to fire .fail() instead of the success cb. */
		set_error_handler(function ($errno, $errstr) use (&$warnings) {
			$warnings[] = $errstr;
			return true;
		});

		try {
			set_time_limit(90);

			$account  = sanitize_text_field(wp_unslash($_POST['account']       ?? ''));
			$name     = sanitize_text_field(wp_unslash($_POST['property_name'] ?? ''));
			$timezone = sanitize_text_field(wp_unslash($_POST['timezone']      ?? 'America/New_York'));
			$currency = sanitize_text_field(wp_unslash($_POST['currency']      ?? 'USD'));
			$url      = esc_url_raw(wp_unslash($_POST['website_url']           ?? ''));
			$enhanced = ! empty($_POST['enhanced_measurement']);

			if (empty($account) || empty($name) || empty($url)) {
				$error = __('Account, property name, and website URL are required.', 'ga-for-wp-text');
			} else {
				$api = $this->get_google_management_api();
				if (! $api) {
					$error = __('Could not connect to Google Analytics. Please re-link your account.', 'ga-for-wp-text');
				} else {
					$property = $api->create_ga4_property($account, $name, $timezone, $currency);
					if (! is_array($property) || empty($property['name'])) {
						$error = __('Failed to create property. Please check your Google Analytics permissions and try again.', 'ga-for-wp-text');
					} else {
						$property_resource = $property['name'];
						$stream            = $api->create_ga4_web_data_stream($property_resource, $name, $url);
						if (! is_array($stream) || empty($stream['name']) || empty($stream['webStreamData']) || empty($stream['webStreamData']['measurementId'])) {
							$error = __('Property created but data stream setup failed. Please check Google Analytics and try connecting manually.', 'ga-for-wp-text');
						} else {
							$stream_resource = $stream['name'];
							$measurement_id  = $stream['webStreamData']['measurementId'];
							if ($enhanced) {
								$api->patch_enhanced_measurement($stream_resource);
							}
							delete_option('ga_properties');
							$result = array(
								'property_id'    => $stream_resource . '|' . $measurement_id,
								'measurement_id' => $measurement_id,
								'display_name'   => isset($property['displayName']) ? $property['displayName'] : $name,
							);
						}
					}
				}
			}
		} catch (\Throwable $e) {
			error_log('TrueAna ajax_create_ga4_property exception: ' . $e->getMessage());
			$error = __('An unexpected error occurred. Please try again.', 'ga-for-wp-text');
		}

		restore_error_handler();

		if (! empty($warnings)) {
			error_log('TrueAna create_property PHP warnings: ' . implode('; ', $warnings));
		}

		if ($error !== null) {
			wp_send_json_error($error);
		} else {
			wp_send_json_success($result);
		}
	}

	/**
	 * AJAX handler that clears the cached `ga_properties` option.
	 *
	 * Forces the next accounts/properties lookup to re-fetch from the
	 * Google Analytics Management API instead of using stale cached data.
	 */
	public function ajax_refresh_properties()
	{
		check_ajax_referer('ga4wp_create_property_nonce', 'nonce');
		if (! current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized');
			return;
		}
		delete_option('ga_properties');
		wp_send_json_success();
	}

	/* ── Measurement Protocol (available on both free and premium tiers) ── */

	/**
	 * AJAX handler that manually (re)generates the Measurement Protocol API
	 * secret for the connected GA4 data stream.
	 *
	 * Triggered from the Status page "Generate"/"Retry" button. Requires an
	 * existing GA4 property connection and access token, then delegates to
	 * ga4wp_get_mesurement_key() (which reuses an existing secret rather
	 * than creating a duplicate) while buffering and logging any stray
	 * output so it can't corrupt the JSON response. On success, also syncs
	 * the retrieved secret into the `ga4wp_track_settings` option and sends
	 * a JSON success response with the `secret` and its last 4 characters
	 * (`secret_last4`); sends a JSON error otherwise.
	 */
	public function ajax_generate_measurement_key()
	{
		check_ajax_referer('ga4wp-tab-update', 'security');
		if (! current_user_can('manage_options')) {
			wp_send_json_error(__('Unauthorized', 'ga-for-wp-text'));
			return;
		}

		$auth_settings = get_option('ga4wp_auth_settings');
		if (empty($auth_settings['property_id'])) {
			wp_send_json_error(__('No GA4 property connected.', 'ga-for-wp-text'));
			return;
		}

		$token = $this->get_access_token();
		if (! $token) {
			wp_send_json_error(__('No access token available. Please authenticate with Google first.', 'ga-for-wp-text'));
			return;
		}

		/* The GA4 Admin API calls below can be slow (a couple of retry loops with
		 * their own timeouts each) — avoid a default max_execution_time cutoff
		 * killing the request after the secret has already been saved but before
		 * the response is sent. */
		set_time_limit(0);

		/* ga4wp_get_mesurement_key() (GA4WP_Property_Setup trait) fetches existing
		 * secrets first and only creates a new "GA4WP_secret_Key" if none is found —
		 * reused here so Retry never creates a duplicate secret on GA4's side.
		 *
		 * Wrapped in an output buffer: any stray PHP notice/warning from deep in
		 * the API call chain would otherwise get echoed into this AJAX response
		 * and corrupt the JSON, which jQuery reports to the user as a generic
		 * "network error" even though the secret was actually saved successfully. */
		ob_start();
		$this->ga4wp_get_mesurement_key();
		$stray_output = ob_get_clean();
		if ($stray_output !== '') {
			error_log('GA4WP measurement key generation produced unexpected output: ' . $stray_output);
		}

		$secret = get_option('measurement_key');
		if (empty($secret)) {
			$error = get_option('measurement_key_error') ?: __('Unknown error creating the Measurement Protocol secret.', 'ga-for-wp-text');
			wp_send_json_error($error);
			return;
		}

		/* Keep the Tracking Settings "API Secret Key" field in sync so it reflects
		 * the same value without requiring a manual paste. */
		$track = get_option('ga4wp_track_settings', []);
		if (! is_array($track)) {
			$track = [];
		}
		$track['google_measurement_api'] = $secret;
		update_option('ga4wp_track_settings', $track);

		wp_send_json_success(array(
			'secret'      => $secret,
			'secret_last4' => substr($secret, -4),
		));
	}
}

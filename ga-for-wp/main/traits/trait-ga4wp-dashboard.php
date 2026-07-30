<?php

/**
 * Trait: GA4WP_Dashboard
 *
 * WordPress admin dashboard widget registration, data fetching,
 * transient naming, and date-range helpers for the dash settings.
 */
if (! defined('ABSPATH')) die;

trait GA4WP_Dashboard
{

	/* Used by ga4wp_dashboard_widget() and ga4wp_dashboard_help() */
	private $transient_value;

	/**
	 * Register the plugin's widgets on the WordPress admin dashboard.
	 *
	 * Hooked into `wp_dashboard_setup`. When a GA4 property is connected
	 * and the refresh token is valid, adds one dashboard widget per report
	 * defined in GA4WP_Settings::ga4wp_dash_data_ga4_widget, skipping any
	 * that have been disabled via the `ga4wp_widget_settings` option.
	 * When no property is connected, adds a single "complete setup"
	 * prompt widget instead.
	 */
	public function ga4wp_dashboard_widget()
	{
		global $wp_meta_boxes;
		if ($auth_settings = get_option('ga4wp_auth_settings')) {
			if (isset($auth_settings['property_id']) && !GA4WP_Auth::is_reauth_required()) {
				$dash_data_widget = GA4WP_Settings::get_instance()->ga4wp_dash_data_ga4_widget;
				$widget_settings = get_option('ga4wp_widget_settings', []);
				$key_map         = ['overview_report', 'country_report', 'language_report', 'device_report', 'quick_stats'];
				$i = 0;
				foreach ($dash_data_widget as $widget_title => $widget_data) {
					$setting_key = $key_map[$i] ?? null;
					$i++;
					if ($setting_key && isset($widget_settings[$setting_key]) && $widget_settings[$setting_key] === 'no') {
						continue;
					}
					wp_add_dashboard_widget('ga4wp_status_widget' . $i, $widget_title, array($this, 'ga4wp_dashboard_help'), '', $widget_data, '', 'high');
				}
			}
		} else {
			wp_add_dashboard_widget('ga4wp_status_widget_6', 'GA4WP Setup', array($this, 'ga4wp_dashboard_help6'));
		}
	}

	/**
	 * Fetch (or return the cached) data needed to render the WP dashboard widgets.
	 *
	 * Resolves the current dash settings and property id, then requests the
	 * last-30-days-to-yesterday data for the 'dash' tab (premium) or the
	 * 'audience' tab (free — same report shape/order, different name) from
	 * either the report-view API or the standard GA4 data API, caching the
	 * result in a transient for 120 seconds keyed by ga4wp_create_transient_name().
	 *
	 * @return array|false The dashboard data array, or false if nothing could be fetched.
	 */
	public function g4wp_required_dashboard_data()
	{
		$ga4wp_dash_settings = $this->get_current_dash_settings();
		$property_id         = $this->get_ga_property_id();
		$dash_end            = date('Y-m-d', strtotime('-1 day'));
		$dash_start          = date('Y-m-d', strtotime('-30 day'));
		// The 'dash' report definitions are premium-only; the free version's
		// 'audience' definitions cover the same reports (same shape/order) under a
		// different name, so the WP dashboard widgets must key off whichever exists.
		$tab_id              = (function_exists('gfw_fs') && gfw_fs()->can_use_premium_code__premium_only()) ? 'dash' : 'audience';

		if (! empty($ga4wp_dash_settings['report_view'])) {
			$new_api        = $this->get_google_report_api();
			$transient_name = $this->ga4wp_create_transient_name($ga4wp_dash_settings['report_view'], $property_id, $dash_start, $dash_end, $tab_id);
			$transient_value = get_transient($transient_name);
			if ($transient_value == false) {
				$transient_value = $new_api->get_dashboard_data($ga4wp_dash_settings['report_view'], $dash_start, $dash_end, $tab_id);
				if (isset($transient_value) && is_array($transient_value)) {
					set_transient($transient_name, $transient_value, 120);
				}
			}
		} else {
			$new_api         = $this->get_google_analytics_data_api();
			$transient_name  = $this->ga4wp_create_transient_name(false, $property_id, $dash_start, $dash_end, $tab_id);
			$transient_value = get_transient($transient_name);
			if ($transient_value == false) {
				$transient_value = $new_api->get_dashboard_data($dash_start, $dash_end, $tab_id);
				if (isset($transient_value) && is_array($transient_value)) {
					set_transient($transient_name, $transient_value, 120);
				}
			}
		}
		return $transient_value;
	}

	/**
	 * Build a stable, version-busted transient key for cached report data.
	 *
	 * Hashes the given parameters (plus a hardcoded version string bumped
	 * whenever the cached data shape changes) into a single md5 key, so
	 * old-shaped cached values are automatically invalidated after a
	 * version bump.
	 *
	 * @param string|false $view_id     Universal Analytics view id, or false when using the GA4 data API.
	 * @param string       $property_id GA4 property id.
	 * @param string       $start_date  Report start date (Y-m-d).
	 * @param string       $end_date    Report end date (Y-m-d).
	 * @param string       $tab_id      Identifier of the tab/report the data belongs to.
	 * @return string The md5 transient key.
	 */
	public function ga4wp_create_transient_name($view_id, $property_id, $start_date, $end_date, $tab_id)
	{
		// v9: bust cache after content tab gained bestCategory/bestTag/bestPostType reports
		$v = 'v9';
		if ($view_id) {
			return md5(serialize(array($view_id, $property_id, $start_date, $end_date, $tab_id, $v)));
		}
		return md5(serialize(array($property_id, $start_date, $end_date, $tab_id, $v)));
	}

	/**
	 * Render a single WP dashboard widget's contents (chart or stats block).
	 *
	 * Used as the callback passed to wp_add_dashboard_widget() for each
	 * report widget. Lazily loads and caches the shared dashboard data on
	 * the instance, suppresses chart header actions (export/info icons
	 * don't work reliably inside WP dashboard widgets), then renders a
	 * line/bar/doughnut chart or a stats block depending on
	 * $widget_data_array['args'], followed by a "Last 30 Days" footer.
	 *
	 * @param mixed $var                Unused; passed by wp_add_dashboard_widget() but not read.
	 * @param array $widget_data_array  Widget callback args array; 'args' holds the widget's [type, title, x, y, dataKey, options] tuple.
	 */
	public function ga4wp_dashboard_help($var, $widget_data_array)
	{
		if ($this->transient_value === null) {
			$this->transient_value = $this->g4wp_required_dashboard_data();
		}
		$widget_data = $widget_data_array['args'];
		// The export/info icons rely on JS wiring that doesn't work reliably inside
		// WP dashboard widgets, so hide them here (chart-renderer honors this flag).
		$this->suppress_chart_header_actions = true;
		if ($widget_data['1'] == 'line') {
			echo '<div class="dash_chartbox_' . $widget_data[4] . '"></div>';
			$this->publish_simple_line_chart('dash_chartbox_' . $widget_data['4'], $widget_data['0'], $widget_data['2'], $widget_data['3'], $this->transient_value[$widget_data['4']], $widget_data['5']);
		} elseif ($widget_data['1'] == 'bar') {
			echo '<div class="dash_chartbox_' . $widget_data[4] . '"></div>';
			$this->publish_simple_bar_chart('dash_chartbox_' . $widget_data['4'], $widget_data['0'], $widget_data['2'], $widget_data['3'], $this->transient_value[$widget_data['4']], $widget_data['5']);
		} elseif ($widget_data['1'] == 'doughnut') {
			echo '<div class="dash_chartbox_' . $widget_data[4] . '"></div>';
			$this->publish_simple_doughnut_chart('dash_chartbox_' . $widget_data['4'], $widget_data['0'], $this->transient_value[$widget_data['4']], $widget_data['5']);
		} elseif ($widget_data['1'] == 'stats') {
			$this->publish_stat_data_2($this->transient_value[$widget_data['4']]);
		}
		echo '<hr><b>Data Period:</b> Last 30 Days';
	}

	/**
	 * Render the "complete setup" prompt widget shown when no GA4 account is linked.
	 *
	 * Used as the callback for the 'ga4wp_status_widget_6' dashboard widget
	 * added by ga4wp_dashboard_widget(). Echoes a plugin logo and a button
	 * linking to the plugin's settings page (network admin URL on
	 * multisite network admin, otherwise the regular admin URL).
	 */
	public function ga4wp_dashboard_help6()
	{
		if (is_multisite() && is_network_admin()) {
			$button_url = network_admin_url('admin.php?page=ga4wp_pro_plugin_options');
		} else {
			$button_url = admin_url('admin.php?page=ga4wp_pro_plugin_options');
		}
?>
		<div class="ga4wp-row valign-wrapper">
			<div class="ga4wp-col s4">
				<img class="responsive-img small-plugin-image" src="<?php echo GA4WP_URL . 'assests/images/truana-mark.svg'; ?>">
			</div>
			<div class="ga4wp-col s8">
				<p><?php _e('You\'re almost there! Once you complete GA4WP setup you start receiving different facts and reports from Google Analytics for Website Here.', 'ga-for-wp-text'); ?></p>
				<a href="<?php echo $button_url; ?>" class="button button-primary"><?php _e('Complete Setup', 'ga-for-wp-text'); ?></a>
			</div>
		</div>
<?php
	}

	/**
	 * Get the dashboard settings with the report date range resolved.
	 *
	 * Reads the `ga4wp_dash_settings` option (falling back to plugin
	 * defaults if unset), then computes concrete `report_from`/`report_to`
	 * dates from the saved `report_frame` value (Yesterday, Last 7 days,
	 * Today, Current Year, Custom Range, or a default 30-day window).
	 *
	 * @return array The dash settings array with `report_from` and `report_to` populated.
	 */
	public function get_current_dash_settings()
	{
		$ga4wp_dash_settings = get_option('ga4wp_dash_settings');
		if (empty($ga4wp_dash_settings)) {
			$ga4wp_dash_settings = GA4WP_Settings::get_instance()->init_ga4wp_dash_defaults();
		}
		$frame = $ga4wp_dash_settings['report_frame'] ?? '';
		if ($frame == 'Yesterday') {
			$ga4wp_dash_settings['report_to']   = date('Y-m-d', strtotime('-1 day'));
			$ga4wp_dash_settings['report_from'] = date('Y-m-d', strtotime('-1 day'));
		} elseif ($frame == 'Last 7 days') {
			$ga4wp_dash_settings['report_to']   = date('Y-m-d', strtotime('-1 day'));
			$ga4wp_dash_settings['report_from'] = date('Y-m-d', strtotime('-7 day'));
		} elseif ($frame == 'Today') {
			$ga4wp_dash_settings['report_to']   = date('Y-m-d', strtotime('now'));
			$ga4wp_dash_settings['report_from'] = date('Y-m-d', strtotime('now'));
		} elseif ($frame == 'Current Year') {
			$ga4wp_dash_settings['report_to']   = date('Y-m-d', strtotime('now'));
			$ga4wp_dash_settings['report_from'] = date('Y') . '-01-01';
		} elseif ($frame == 'Custom Range') {
			if (! isset($ga4wp_dash_settings['report_to']) || ! isset($ga4wp_dash_settings['report_from'])) {
				$ga4wp_dash_settings['report_to']   = date('Y-m-d', strtotime('-1 day'));
				$ga4wp_dash_settings['report_from'] = date('Y-m-d', strtotime('-30 day'));
			}
		} else {
			$ga4wp_dash_settings['report_to']   = date('Y-m-d', strtotime('-1 day'));
			$ga4wp_dash_settings['report_from'] = date('Y-m-d', strtotime('-30 day'));
		}
		return $ga4wp_dash_settings;
	}
}

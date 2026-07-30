<?php
/*class for creating plugin menu and tabs */
if (!defined('ABSPATH')) {
	die;
}
/*
 * Declaring Class
 */
class GA4WP_Admin
{
	public $pages;

	/**
	 * Wire up the admin integration: load the view classes and register
	 * the WordPress hooks that add the plugin's menu page, enqueue its
	 * scripts/styles, and add the "Settings" link on the Plugins page.
	 */
	public function __construct()
	{
		/* ading admin view class */
		$this->includes();
		/* adding stylesheets and scripts of plugin */
		add_action('admin_enqueue_scripts', array($this, 'ga4wp_enqueue_scripts'));
		/* adding plugin link in wp-menu */
		add_action('admin_menu', array($this, 'add_menu_pages'));
		/* adding links to plugin on pluings page*/
		add_filter('plugin_action_links_' . GA4WP_BASENAME, array($this, 'settings_link'));
	}

	/**
	 * Register the plugin's admin menu page.
	 *
	 * Hooked into `admin_menu`. Instantiates GA4WP_Admin_View, which adds
	 * the actual WP admin menu entry, and stores it on $this->pages so it
	 * can be referenced elsewhere.
	 */
	public function add_menu_pages()
	{
		$title = __('TrueAna', 'ga-for-wp-text');
		$this->pages['ga4wp'] = new GA4WP_Admin_View($title, 'ga4wp_pro_plugin_options');
	}

	/**
	 * Enqueue the plugin's admin scripts and styles.
	 *
	 * Hooked into `admin_enqueue_scripts`. Registers all plugin assets,
	 * then conditionally enqueues the Materialize UI assets on the
	 * plugin's own settings page, the charting/export assets on the
	 * settings page and the WP dashboard screen, and the Freemius UI
	 * override styles/fonts on every admin page (and any ga-for-wp-*
	 * Freemius sub-page). The AJAX helper script is always enqueued.
	 */
	public function ga4wp_enqueue_scripts()
	{
		$screen = get_current_screen();
		$this->register_scripts();
		if (isset($_GET['page']) && ($_GET['page'] == 'ga4wp_pro_plugin_options')) {
			wp_enqueue_script('ga4wp_material_js');
			wp_enqueue_style('ga4wp_material_css');
		}
		if ((isset($_GET['page']) && ($_GET['page'] == 'ga4wp_pro_plugin_options')) || ($screen->id == "dashboard")) {
			wp_enqueue_style('ga4wp_fonts');
			wp_enqueue_style('ga4wp_icons');
			wp_enqueue_style('ga4wp_css');
			wp_enqueue_script('ga4wp_chart_js');
			wp_enqueue_script('ga4wp_export_js');
		}
		wp_enqueue_script('ga4wp_ajax_js');

		// Freemius UI overrides: always load on admin (notices appear everywhere);
		// fonts are pulled in as a dependency so they load on Freemius sub-pages too.
		wp_enqueue_style('ga4wp_freemius_css');

		// Also enqueue fonts+icons on any ga-for-wp-* Freemius sub-page
		// (account, contact, pricing) so Sora is available there.
		$current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
		if (strpos($current_page, 'ga-for-wp') !== false) {
			wp_enqueue_style('ga4wp_fonts');
			wp_enqueue_style('ga4wp_icons');
		}
	}

	/**
	 * Register (but do not enqueue) all styles and scripts used by the plugin's admin UI.
	 *
	 * Cache-busts locally vendored assets using each file's mtime (falling
	 * back to GA4WP_VERSION if the file can't be found) so browsers/CDNs
	 * pick up changes without needing a manual version bump. Registers
	 * fonts, icons, Materialize, the plugin's own CSS, Freemius UI
	 * override CSS, Chart.js, and the jsPDF/autoTable + export script
	 * bundle used for exporting charts.
	 */
	private function register_scripts()
	{
		// Cache-bust plugin-owned local files off their own mtime, not a hand-maintained
		// version string — otherwise a forgotten version bump means browsers/CDNs keep
		// serving a stale cached copy after the file on disk has already changed.
		$asset_ver = function ($rel_path) {
			$file = GA4WP_DIR . $rel_path;
			return file_exists($file) ? (string) filemtime($file) : GA4WP_VERSION;
		};

		// Modern fonts — vendored locally (vendor/google-fonts, vendor/material-icons) so the
		// admin UI doesn't depend on fonts.googleapis.com/fonts.gstatic.com being reachable.
		wp_register_style('ga4wp_fonts', GA4WP_URL . 'vendor/google-fonts/fonts.css', false, $asset_ver('vendor/google-fonts/fonts.css'));
		wp_register_style('ga4wp_icons', GA4WP_URL . 'vendor/material-icons/icons.css', false, $asset_ver('vendor/material-icons/icons.css'));
		// Materialize (keep for existing functionality) — vendored locally
		wp_register_style('ga4wp_material_css', GA4WP_URL . 'vendor/materialize/materialize.min.css', false, $asset_ver('vendor/materialize/materialize.min.css'));
		// Modern plugin CSS — loads after Materialize so it wins on specificity
		wp_register_style('ga4wp_css', GA4WP_URL . 'assests/css/ga4wp.css', array('ga4wp_material_css', 'ga4wp_fonts', 'ga4wp_icons'), $asset_ver('assests/css/ga4wp.css'));
		// Freemius UI overrides — enqueued on all admin pages (covers notices) + fonts on ga-for-wp pages
		wp_register_style('ga4wp_freemius_css', GA4WP_URL . 'assests/css/ga4wp-freemius.css', ['ga4wp_fonts'], $asset_ver('assests/css/ga4wp-freemius.css'));
		wp_register_script('ga4wp_material_js', GA4WP_URL . 'vendor/materialize/materialize.min.js', array('jquery'), $asset_ver('vendor/materialize/materialize.min.js'), true);
		// Chart.js 4 — vendored locally (vendor/chartjs)
		wp_register_script('ga4wp_chart_js', GA4WP_URL . 'vendor/chartjs/chart.umd.js', array(), $asset_ver('vendor/chartjs/chart.umd.js'), false);
		// jsPDF + autoTable for PDF export — vendored locally (vendor/jspdf, vendor/jspdf-autotable)
		wp_register_script('ga4wp_jspdf', GA4WP_URL . 'vendor/jspdf/jspdf.umd.min.js', [], $asset_ver('vendor/jspdf/jspdf.umd.min.js'), true);
		wp_register_script('ga4wp_jspdf_autotable', GA4WP_URL . 'vendor/jspdf-autotable/jspdf.plugin.autotable.min.js', ['ga4wp_jspdf'], $asset_ver('vendor/jspdf-autotable/jspdf.plugin.autotable.min.js'), true);
		wp_register_script('ga4wp_export_js', GA4WP_URL . 'assests/js/ga4wp-export.js', ['ga4wp_chart_js', 'ga4wp_jspdf_autotable'], $asset_ver('assests/js/ga4wp-export.js'), true);
	}

	/**
	 * Build/add the plugin's "Settings" link on the WP Plugins list page.
	 *
	 * Hooked into `plugin_action_links_{GA4WP_BASENAME}` as the default
	 * usage (returns $links with the settings link prepended), but can
	 * also be called directly to just resolve the settings page URL.
	 *
	 * @param array $links       Existing plugin action links.
	 * @param bool  $url_only    When true, return only the settings page URL instead of the links array.
	 * @param bool  $networkwide When true (with $url_only and multisite), force the network admin settings URL.
	 * @return array|string The links array with the settings link added, or the settings URL string when $url_only is true.
	 */
	public function settings_link($links, $url_only = false, $networkwide = false)
	{
		$settings_page = is_multisite() && is_network_admin() ? network_admin_url('admin.php?page=ga4wp_pro_plugin_options') : menu_page_url('ga4wp_pro_plugin_options', false);
		/* If networkwide setting url is needed. */
		$settings_page = $url_only && $networkwide && is_multisite() ? network_admin_url('admin.php?page=ga4wp_pro_plugin_options') : $settings_page;
		$settings = '<a href="' . $settings_page . '">' . __('Settings', 'ga-for-wp-text') . '</a>';
		/* Return only settings page link. */
		if ($url_only) {
			return $settings_page;
		}
		if (!empty($links)) {
			array_unshift($links, $settings);
		} else {
			$links = array($settings);
		}
		return $links;
	}

	/**
	 * Load the class files needed for the plugin's tabbed admin view system.
	 */
	private function includes()
	{
		/* main view class */
		include_once GA4WP_DIR . 'inc/abstract-ga4wp-view.php';
		include_once GA4WP_DIR . 'inc/class-ga4wp-admin-view.php';
	}
}

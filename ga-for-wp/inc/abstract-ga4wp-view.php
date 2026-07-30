<?php
/* abstract view class for admin main view for plugin */
if (!defined('ABSPATH')) {
	die;
}
/*
 * Declaring Class
 */
abstract class GA4WP_View
{
	/* initiating variables */
	private $slug;
	private $page_id = null;
	protected $tabs = array();

	/**
	 * Register the plugin's top-level admin menu page and hook its "on load" callback.
	 *
	 * @param string $title   Menu/page title.
	 * @param string $slug    Menu slug used to build the page URL and identify it.
	 * @param bool   $submenu Unused; accepted for API compatibility but not read.
	 */
	public function __construct($title, $slug = 'ga4wp_pro_plugin_options', $submenu = false)
	{
		$this->slug = $slug;
		$this->page_id = add_menu_page(
			$title,
			$title,
			'manage_options',
			$this->slug,
			array($this, 'render'),
			GA4WP_URL . 'assests/images/truana-mark-white.svg',
			2.000001
		);
		add_action('load-' . $this->page_id, array($this, 'on_load'));
	}

	/**
	 * Get the admin page's menu slug.
	 *
	 * @return string The menu slug passed to the constructor.
	 */
	public function get_slug()
	{
		return $this->slug;
	}

	/**
	 * Render and echo the view template file for a given tab.
	 *
	 * Looks up `inc/view/{$name}.php`, extracts $options as local variables
	 * for the template to use (normalizing an 'id' option that contains
	 * slashes into a safe 'id' HTML-attribute form while preserving the
	 * original in 'orig_id'), buffers its output, and echoes it. Does
	 * nothing if the file doesn't exist.
	 *
	 * @param string $name    Tab/view name, used to resolve the template file.
	 * @param array  $options Variables to make available to the template.
	 */
	public function view($name, $options = array())
	{
		$file = GA4WP_DIR . "inc/view/{$name}.php";
		$content = '';
		if (is_file($file)) {
			ob_start();
			if (isset($options['id'])) {
				$options['orig_id'] = $options['id'];
				$options['id'] = str_replace('/', '-', $options['id']);
			}
			extract($options);
			include $file;
			$content = ob_get_clean();
		}
		echo $content;
	}

	/**
	 * Resolve which tab should currently be displayed on the settings page.
	 *
	 * Prefers the `view` query arg when it names a valid tab. Otherwise,
	 * if the site is authenticated (has auth settings and no refresh-token
	 * failure), defaults to the dashboard tab (free or premium variant
	 * depending on plan) when a property is connected, or the settings tab
	 * when it isn't; if not authenticated, falls back to the first
	 * registered tab.
	 *
	 * @return string|false The resolved tab key, or false if there are no tabs.
	 */
	public function get_current_tab()
	{
		$tabs = $this->get_tabs();
		if (isset($_GET['view']) && array_key_exists(wp_unslash($_GET['view']), $tabs)) {
			return wp_unslash($_GET['view']);
		}
		if (empty($tabs)) {
			return false;
		}
		reset($tabs);
		$auth_settings = get_option('ga4wp_auth_settings');
		if (($auth_settings) && !GA4WP_Auth::is_reauth_required()) {
			//if ($auth_settings = get_option('ga4wp_auth_settings')) {
			if (isset($auth_settings['property_id'])) {
				if (gfw_fs()->is_not_paying() && !(gfw_fs()->is_trial()) || (!gfw_fs()->is_premium())) {
					return 'dash';
				} else {
					return 'dash__premium_only';
				}
			} else {
				return 'settings';
			}
		} else {
			return key($tabs);
		}
	}

	/**
	 * Build the admin URL for a given tab.
	 *
	 * @param string $tab Tab key to link to; must be a registered tab.
	 * @return string The tab's admin URL (network admin URL on a multisite network admin screen), or an empty string if $tab isn't registered.
	 */
	public function get_tab_url($tab)
	{
		$tabs = $this->get_tabs();
		if (!isset($tabs[$tab])) {
			return '';
		}
		if (is_multisite() && is_network_admin()) {
			return network_admin_url('admin.php?page=' . $this->slug . '&view=' . $tab);
		} else {
			return admin_url('admin.php?page=' . $this->slug . '&view=' . $tab);
		}
	}

	/**
	 * Get the registered tabs, filterable by other code.
	 *
	 * @return array Map of tab key => tab label, filtered through `ga4wp_admin_page_tabs_{$slug}`.
	 */
	protected function get_tabs()
	{
		return apply_filters('ga4wp_admin_page_tabs_' . $this->slug, $this->tabs);
	}
}

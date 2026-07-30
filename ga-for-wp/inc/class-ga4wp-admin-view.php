<?php
if (!defined('ABSPATH')) {
  die;
}

class GA4WP_Admin_View extends GA4WP_View
{
  /**
   * Build the tab list shown on the plugin's settings page.
   *
   * Hooked into the `load-{$page_id}` action (see GA4WP_View::__construct()).
   * When the site isn't authenticated (or its refresh token has failed),
   * only the Authentication tab is shown. Once authenticated, adds the
   * full free or premium tab set (Dashboard, Settings/Explore, Status,
   * Advanced Integration, UTM Builder, Support, Upgrade, Un-Link) with
   * tab keys/labels differing by plan.
   */
  public function on_load()
  {
    $this->tabs = array('auth' => __('Authentication', 'ga-for-wp-text'));
    $auth_settings = get_option('ga4wp_auth_settings');
    $is_free = gfw_fs()->is_not_paying() && !(gfw_fs()->is_trial()) || (!gfw_fs()->is_premium());
    // A manually-configured setup (a plain Measurement ID typed in, no OAuth property
    // linked) has no `property_id` and therefore no Google API access — the Dashboard
    // and Status tabs both pull their data through that API, so they can only ever
    // error out (Dashboard) or show an empty placeholder (Status) for these setups.
    // Only show them once a real property_id is on file.
    $has_property = !empty($auth_settings['property_id']);
    if (($auth_settings) && !GA4WP_Auth::is_reauth_required()) {
      if ($is_free) {
        $this->tabs = array(
          'dash'                 => __('Dashboard', 'ga-for-wp-text'),
          'settings'             => __('Settings', 'ga-for-wp-text'),
          'status'               => __('Status', 'ga-for-wp-text'),
          'advanced_integration' => __('Advanced Integration', 'ga-for-wp-text'),
          'explore'              => __('Explore', 'ga-for-wp-text'),
          'utm_builder'          => __('UTM Builder', 'ga-for-wp-text'),
          'support'              => __('Support', 'ga-for-wp-text'),
          'upgrade'              => __('Upgrade to Pro', 'ga-for-wp-text'),
          'unlink'               => __('Un-Link Google Analytics', 'ga-for-wp-text'),
        );
        if (!$has_property) {
          unset($this->tabs['dash'], $this->tabs['status']);
        }
      } else {
        $this->tabs = array(
          'dash__premium_only'                 => __('Dashboard', 'ga-for-wp-text'),
          'settings'                            => __('Settings', 'ga-for-wp-text'),
          'explore__premium_only'               => __('Explore', 'ga-for-wp-text'),
          'status'                              => __('Status', 'ga-for-wp-text'),
          'utm_builder__premium_only'           => __('UTM Builder', 'ga-for-wp-text'),
          'advanced_integration__premium_only'  => __('Advanced Integration', 'ga-for-wp-text'),
          'support'                              => __('Support', 'ga-for-wp-text'),
          'unlink'                               => __('Un-Link Google Analytics', 'ga-for-wp-text'),
        );
        if (!$has_property) {
          unset($this->tabs['dash__premium_only'], $this->tabs['status']);
        }
      }
    } else {
      // Advanced Integration manages independent third-party pixels (Facebook, Google
      // Ads conversion, TikTok, Pinterest, LinkedIn, Snapchat) — none of it touches the
      // GA4 OAuth connection, so keep it reachable even before GA4 is linked or while
      // re-authentication is required, instead of hiding it behind the Authentication tab.
      $this->tabs[$is_free ? 'advanced_integration' : 'advanced_integration__premium_only'] = __('Advanced Integration', 'ga-for-wp-text');
    }
  }

  /**
   * Get the Material icon name to display for a given tab.
   *
   * @param string $tab Tab key.
   * @return string Material icon name, or 'circle' if the tab has no mapped icon.
   */
  private function tab_icon($tab)
  {
    $map = array(
      'dash' => 'bar_chart',
      'dash__premium_only' => 'analytics',
      'auth' => 'lock',
      'settings' => 'tune',
      'support' => 'help_outline',
      'upgrade' => 'workspace_premium',
      'unlink' => 'link_off',
      'explore__premium_only' => 'explore',
      'explore'               => 'explore',
      'status'                => 'monitor_heart',
      'utm_builder__premium_only' => 'link',
      'utm_builder'           => 'link',
      'advanced_integration'              => 'extension',
      'advanced_integration__premium_only' => 'extension',
      'woo_subscriptions'     => 'subscriptions',
    );
    return isset($map[$tab]) ? $map[$tab] : 'circle';
  }

  /**
   * Render the plugin's admin settings page shell.
   *
   * This is the callback registered as the admin menu page's render
   * function. Outputs the top nav bar (brand, connection status pill,
   * docs/upgrade/un-link links), the tab bar, the un-link confirmation
   * modal, and finally the active tab's content by resolving its options
   * via view_options() and rendering its template via view().
   */
  public function render()
  {
    $auth_settings            = get_option('ga4wp_auth_settings');
    $is_authed   = $auth_settings && !GA4WP_Auth::is_reauth_required();
    $is_warn     = $auth_settings && GA4WP_Auth::is_reauth_required();
    $tracking_id = $this->get_tracking_id();
    $current_tab = $this->get_current_tab();
    // Manual (Measurement ID) connects never go through Google OAuth, so there's no
    // access token on Google's side to revoke — "Un-Link & Remove All Settings" (which
    // calls Google's token-revoke endpoint) has nothing to do and would just confuse
    // users into thinking it accomplishes more than the plain Un-Link button already does.
    $has_property = !empty($auth_settings['property_id']);
?>
    <link href="<?php echo esc_url(GA4WP_URL . 'vendor/google-fonts/fonts.css'); ?>" rel="stylesheet">
    <link href="<?php echo esc_url(GA4WP_URL . 'vendor/material-icons/icons.css'); ?>" rel="stylesheet">

    <div id="ga4wp-admin-wrap">

      <!-- TOPDIV -->
      <div class="ga4wp-row ga4wp-topnav-div">
        <div class="ga4wp-nav-left ga4wp-col s12 m6 l6">
          <div class="ga4wp-brand">
            <div class="ga4wp-brand-icon">
              <img src="<?php echo esc_url(GA4WP_URL . 'assests/images/truana-mark.svg'); ?>" alt="TrueAna" style="width:16px;height:16px;object-fit:contain;display:block;">
            </div>
            TrueAna
          </div>
          <?php if ($tracking_id) : ?>
            <span class="ga4wp-prop-badge"><?php echo esc_html($tracking_id); ?></span>
          <?php endif; ?>
        </div>

        <div class="ga4wp-nav-right ga4wp-col s12 m6 l6">
          <?php if ($is_authed) : ?>
            <div class="ga4wp-conn-pill is-connected">
              <div class="ga4wp-conn-dot"></div><?php _e('Connected', 'ga-for-wp-text'); ?>
            </div>
          <?php elseif ($is_warn) : ?>
            <div class="ga4wp-conn-pill is-warning">
              <div class="ga4wp-conn-dot"></div><?php _e('Re-auth needed', 'ga-for-wp-text'); ?>
            </div>
          <?php else : ?>
            <div class="ga4wp-conn-pill is-off">
              <div class="ga4wp-conn-dot"></div><?php _e('Not connected', 'ga-for-wp-text'); ?>
            </div>
          <?php endif; ?>
          <div class="ga4wp-nav-divider"></div>
          <a href="https://trueana.com/documentation/" target="_blank" class="ga4wp-nbtn">
            <span class="material-icons-round">menu_book</span><?php _e('Docs', 'ga-for-wp-text'); ?>
          </a>
          <?php if (gfw_fs()->is_not_paying() && !(gfw_fs()->is_trial())) : ?>
            <a href="<?php echo esc_url(gfw_fs()->get_upgrade_url()); ?>" class="ga4wp-nbtn ga4wp-nbtn-upgrade">
              <span class="material-icons-round">workspace_premium</span><?php _e('Upgrade', 'ga-for-wp-text'); ?>
            </a>
          <?php endif; ?>
          <a href="#modal1" class="ga4wp-nbtn ga4wp-nbtn-danger modal-trigger">
            <span class="material-icons-round">link_off</span><?php _e('Un-Link', 'ga-for-wp-text'); ?>
          </a>
        </div>
      </div>

      <!-- TAB BAR -->
      <div class="ga4wp-tab-bar">
        <?php foreach ($this->get_tabs() as $tab => $name) :
          if ($tab === 'unlink') continue;
          $is_active = ($tab === $current_tab);
          $icon      = $this->tab_icon($tab);
          if ($tab === 'upgrade') :
            $href      = esc_url(gfw_fs()->get_upgrade_url());
            $extra_att = ' target="_blank"';
            $extra_cls = ' ga4wp-tab-upgrade';
          else :
            $href      = esc_url($this->get_tab_url($tab));
            $extra_att = '';
            $extra_cls = '';
          endif;
        ?>
          <a href="<?php echo $href; ?>" <?php echo $extra_att; ?>
            class="ga4wp-tab-a<?php echo $is_active ? ' ga4wp-tab-active' : '';
                              echo $extra_cls; ?>">
            <span class="material-icons-round"><?php echo esc_html($icon); ?></span>
            <?php echo esc_html($name); ?>
            <?php if (in_array($tab, ['explore', 'utm_builder'], true)) : ?>
              <span class="material-icons" style="font-size:13px;color:#F59E0B;vertical-align:middle;margin-left:2px;">workspace_premium</span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- UNLINK MODAL -->
      <div id="modal1" class="modal ga4wp-modal">
        <div class="modal-content ga4wp-modal-content">
          <div class="ga4wp-modal-icon">
            <span class="material-icons-round">link_off</span>
          </div>
          <h5><?php _e('Un-Link Google Analytics', 'ga-for-wp-text'); ?></h5>
          <p><?php _e('Are you sure you wish to un-link Google Analytics from your website? This will stop all tracking immediately.', 'ga-for-wp-text'); ?></p>
        </div>
        <div class="modal-footer ga4wp-modal-footer">
          <div class="ga4wp-row ga4wp-modal-btn-row">
            <div class="ga4wp-col s12 <?php echo $has_property ? 'm3' : 'm6'; ?> ga4wp-modal-btn-col">
              <a class="modal-close ga4wp-modal-btn ga4wp-modal-btn-cancel">
                <?php _e('Cancel', 'ga-for-wp-text'); ?>
              </a>
            </div>
            <?php if ($has_property) : ?>
              <div class="ga4wp-col s12 m6 ga4wp-modal-btn-col">
                <a class="modal-close ga4wp-modal-btn ga4wp-modal-btn-violet GA4WP-access-revoke">
                  <span class="material-icons-round">delete_sweep</span>
                  <?php _e('Un-Link & Remove All Settings', 'ga-for-wp-text'); ?>
                </a>
              </div>
            <?php endif; ?>
            <div class="ga4wp-col s12 <?php echo $has_property ? 'm3' : 'm6'; ?> ga4wp-modal-btn-col">
              <a class="modal-close ga4wp-modal-btn ga4wp-modal-btn-danger GA4WP-un-link">
                <span class="material-icons-round">link_off</span>
                <?php _e('Un-Link', 'ga-for-wp-text'); ?>
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- MAIN CONTENT -->
      <div class="ga4wp-admin-content">
        <?php
        $tab_options = $this->view_options($current_tab);
        $this->view($current_tab, $tab_options);
        ?>
      </div>

    </div>
<?php
  }

  /**
   * Fetch (or return the cached) data needed by the Status tab.
   *
   * Resolves the connected GA4 property's resource name from the saved
   * `ga4wp_auth_settings`, returning an empty-shaped result if no valid
   * property is connected. Otherwise fetches property details, data
   * retention settings, and registered custom dimensions via the
   * Management API, caching the combined result in a transient for one
   * day.
   *
   * @return array{property_name: string, property_details: mixed, data_retention: mixed, custom_dims: array} Status tab data; also includes `required_dims` and `fetched_at` when a property is connected.
   */
  private function get_status_options()
  {
    $auth_settings = get_option('ga4wp_auth_settings');
    $empty         = array('property_name' => '', 'property_details' => null, 'data_retention' => null, 'custom_dims' => array());

    if (empty($auth_settings['property_id'])) {
      return $empty;
    }

    $pieces       = explode('|', $auth_settings['property_id']);
    $stream_parts = explode('/', $pieces[0] ?? '');
    $property_name = (count($stream_parts) >= 2) ? ($stream_parts[0] . '/' . $stream_parts[1]) : '';

    if (empty($property_name) || stripos($property_name, 'properties') === false) {
      return $empty;
    }

    $cache_key = 'ga4wp_status_' . md5($property_name);
    $cached    = get_transient($cache_key);
    if ($cached !== false) {
      return $cached;
    }

    $auth = GA4WP_Auth::get_instance();
    $api  = $auth->get_google_management_api();

    $options = array(
      'property_name'    => $property_name,
      'property_details' => $api ? $api->get_ga4_property_details($property_name) : null,
      'data_retention'   => $api ? $api->get_ga4_data_retention($property_name) : null,
      'custom_dims'      => ($api && method_exists($api, 'get_custom_dimensions') ? $api->get_custom_dimensions($property_name) : array()) ?: array(),
      'required_dims'    => GA4WP_Settings::get_instance()->ga4wp_custom_dimensions,
      'fetched_at'       => time(),
    );

    set_transient($cache_key, $options, DAY_IN_SECONDS);
    return $options;
  }

  /**
   * Get the connected property's tracking/measurement id for display.
   *
   * Reads the `ga4wp_auth_settings` option and extracts the measurement
   * id from a GA4 `property_id` (formatted as `stream_resource|measurement_id`),
   * falling back to a plain `tracking_id` for manually-configured setups.
   *
   * @return string|false The measurement/tracking id, or false if no auth settings are saved.
   */
  public function get_tracking_id()
  {
    if (get_option('ga4wp_auth_settings')) {
      $auth = get_option('ga4wp_auth_settings');
      if (isset($auth['property_id'])) {
        $pieces = explode('|', $auth['property_id']);
        return $pieces[1];
      } elseif (isset($auth['tracking_id'])) {
        return $auth['tracking_id'];
      }
    }
    return false;
  }

  /**
   * Resolve the options array to pass to a tab's view template.
   *
   * @param string $tab Tab key being rendered.
   * @return array Options for the given tab's template (e.g. defaults, cached GA properties, status data); empty array for tabs that need none.
   */
  private function view_options($tab)
  {
    $ga4wp_settings = GA4WP_Settings::get_instance();
    $ga4wp_auth     = GA4WP_Auth::get_instance();
    switch ($tab) {
      case 'dash':
      case 'dash__premium_only':
        return array('defaults' => $ga4wp_settings->init_ga4wp_dash_defaults());
      case 'auth':
        if (!empty($ga_properties = get_option('ga_properties'))) {
          return array('ga_properties' => $ga_properties, 'analytics_properties' => false, 'analytics_g4_properties' => false, 'defaults' => $ga4wp_settings->init_ga4wp_auth_defaults());
        } else {
          return array('analytics_g4_properties' => $ga4wp_auth->get_analytics_g4_properties(), 'defaults' => $ga4wp_settings->init_ga4wp_auth_defaults());
        }
      case 'settings':
        return array('defaults' => $ga4wp_settings->init_ga4wp_track_defaults());
      case 'explore__premium_only':
      case 'explore':
        return array();
      case 'support':
        return array();
      case 'status':
        return $this->get_status_options();
      case 'utm_builder__premium_only':
      case 'utm_builder':
        return array('site_url' => GA4WP_SITE_URL);
      case 'advanced_integration':
      case 'advanced_integration__premium_only':
        return array();
      default:
        return array();
    }
  }
}

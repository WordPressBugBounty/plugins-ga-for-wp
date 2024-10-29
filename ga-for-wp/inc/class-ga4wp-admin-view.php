<?php
/* Adding main view of plugin */
if (!defined('ABSPATH')) {
  die;
}
/*
 * Declaring Class
 */
class GA4WP_Admin_View extends GA4WP_View
{
  /* adding tabs array */
  public function on_load()
  {
    $this->tabs = array(
      'auth' => __('Authentication', 'ga-for-wp-text'),
    );
    $auth_settings = get_option('ga4wp_auth_settings');
    $ga4wp_refresh_token_fail = get_option('ga4wp_refresh_token_fail');
    if (($auth_settings) && ($ga4wp_refresh_token_fail != 'yes')) {
      if (gfw_fs()->is_not_paying() && !(gfw_fs()->is_trial()) || (!gfw_fs()->is_premium())) {
        $this->tabs = array(
          'settings' => __('Settings', 'ga-for-wp-text'),
        );
        if (isset($auth_settings['property_id']) && (strpos($auth_settings['property_id'], 'UA') !== false)) {
          $this->tabs = array_merge(array('dash' => __('Dashboard', 'ga-for-wp-text')), $this->tabs);
        } elseif (isset($auth_settings['property_id']) && (strpos($auth_settings['property_id'], 'G') !== false)) {
          $this->tabs = array_merge(array('dash' => __('Dashboard', 'ga-for-wp-text')), $this->tabs);
        }
        $this->tabs = array_merge($this->tabs, array('upgrade' => __('Upgrade to Pro', 'ga-for-wp-text')));
        $this->tabs = array_merge($this->tabs, array('unlink' => __('Un-Link Google Analytics', 'ga-for-wp-text')));
      } else {
        $this->tabs = array(
          'settings' => __('Settings', 'ga-for-wp-text'),
          'support' => __('Support', 'ga-for-wp-text'),
        );
        if (isset($auth_settings['property_id']) && (strpos($auth_settings['property_id'], 'UA') !== false)) {
          $this->tabs = array_merge(array('dash__premium_only' => __('Dashboard', 'ga-for-wp-text')), $this->tabs);
        } elseif (isset($auth_settings['property_id']) && (strpos($auth_settings['property_id'], 'G') !== false)) {
          $this->tabs = array_merge(array('dash__premium_only' => __('Dashboard', 'ga-for-wp-text')), $this->tabs);
        }
        $this->tabs = array_merge($this->tabs, array('unlink' => __('Un-Link Google Analytics', 'ga-for-wp-text')));
      }
    }
  }

  /* rendering tabs for plugin settings */
  public function render()
  {
    ?>
    <div class="ga4wp-row">
      <div class="ga4wp-col s12 m12 l12 xl12">
        <div class="ga4wp-col s12 m2 l2 xl2 center-align">
          <img class="responsive-img small-plugin-image" src="<?php echo GA4WP_URL . 'assests/images/GA4WP.png'; ?>">
          <?php if ($G_id = $this->get_tracking_id()) { ?>
            <span class="badge blue white-text center">
              <?php echo $G_id; ?>
            </span>
          <?php } ?>
        </div>
        <ul class="ga4wp-col s12 m10 l10 xl8 collection menu-collection">
          <?php
          foreach ($this->get_tabs() as $tab => $name) {
            if (($tab != 'unlink') && ($tab != 'upgrade')) { ?>
              <a href="<?php echo esc_url($this->get_tab_url($tab)); ?>"
                class="collection-item <?php echo ($tab === $this->get_current_tab() ? 'white z-depth-1' : null); ?>">
                <li><span>
                    <?php echo esc_html($name); ?>
                  </span>
                  <?php
                  if ($tab == 'auth') {
                    $auth_settings = get_option('ga4wp_auth_settings');
                    $ga4wp_refresh_token_fail = get_option('ga4wp_refresh_token_fail');
                    if($auth_settings && ($ga4wp_refresh_token_fail != 'yes')){
                    //if (get_option('ga4wp_auth_settings')) {
                      echo '<i class="material-icons right">check_circle</i>';
                    }elseif($auth_settings && ($ga4wp_refresh_token_fail == 'yes')){
                      //if (get_option('ga4wp_auth_settings')) {
                        echo '<i class="material-icons right yellow-text">info</i>';
                    }else {
                      echo '<i class="material-icons right info">info</i>';
                    }
                  } elseif ($tab == 'upgrade') {
                    echo '<i class="material-icons right shopping_cart">shopping_cart</i>';
                  } else {
                    echo '<i class="material-icons right">check_circle</i>';
                  } ?>
                </li>
              </a>
            <?php } elseif ($tab == 'unlink') { ?>
              <a href="#modal1"
                class="collection-item modal-trigger <?php echo ($tab === $this->get_current_tab() ? 'white z-depth-1' : null); ?>">
                <li><span>
                    <?php echo esc_html($name); ?>
                  </span>
                  <i class="material-icons right red-text">error</i>
                </li>
              </a>
            <?php } elseif ($tab == 'upgrade') { ?>
              <a href="https://ga4wp.com/pricing/"
                class="collection-item <?php echo ($tab === $this->get_current_tab() ? 'white z-depth-1' : null); ?>">
                <li><span>
                    <?php echo esc_html($name); ?>
                  </span>
                  <i class="material-icons right red-text">shopping_cart</i>
                </li>
              </a>
            <?php }
          }
          ?>
        </ul>
        <div class="ga4wp-col s12 m12 l12 xl2">
        </div>  
        <div id="modal1" class="modal">
          <div class="modal-content">
            <h5>
              <?php _e('Un-Link Google Analysis from website', 'ga-for-wp-text'); ?>
            </h5>
            <p>
              <?php _e('Are you sure you wish to un-link Google Analysis from Website?', 'ga-for-wp-text'); ?>
            </p>
          </div>
          <div class="modal-footer">
            <a class="modal-close waves-effect waves-light btn GA4WP-un-link">
              <?php _e('Un-Link Google Analytics', 'ga-for-wp-text'); ?>
            </a>
            <a class="modal-close waves-effect waves-green btn GA4WP-access-revoke">
              <?php _e('Un-Link and Remove All Settings', 'ga-for-wp-text'); ?>
            </a>
            <a class="modal-close waves-effect waves-green btn">
              <?php _e('Cancel', 'ga-for-wp-text'); ?>
            </a>
          </div>
        </div>
      </div>
      <div class="content-pad">
        <div class="ga4wp-col s12 m12 l12 xl12 white main-content">
          <?php
          $current_tab = $this->get_current_tab();
          $tab_options = $this->view_options($current_tab);
          $this->view($current_tab, $tab_options);
          ?>
        </div>
      </div>
      <?php
  }
  /* getting tracking id */
  public function get_tracking_id()
  {
    if (get_option('ga4wp_auth_settings')) {
      $auth_settings = get_option('ga4wp_auth_settings');
      if (isset($auth_settings['property_id'])) {
        $property = $auth_settings['property_id'];
        $pieces = explode('|', $property);
        return $pieces[1];
      } else {
        if (isset($auth_settings['tracking_id'])) {
          return $auth_settings['tracking_id'];
        } else {
          return false;
        }
      }
    } else {
      return false;
    }
  }
  /* suppling setting and some required values */
  private function view_options($tab)
  {
    $ga4wp_settings = GA4WP_Settings::get_instance();
    $ga4wp_auth = GA4WP_Auth::get_instance();
    switch ($tab) {
      case 'dash':
        return array(
          'defaults' => $ga4wp_settings->init_ga4wp_dash_defaults(),
          //'property_views' => $ga4wp_auth->get_analytics_property_views(),
        );
      case 'dash__premium_only':
        return array(
          'defaults' => $ga4wp_settings->init_ga4wp_dash_defaults(),
          //'property_views' => $ga4wp_auth->get_analytics_property_views(),
        );
      case 'auth':
        if(!empty($ga_properties = get_option('ga_properties'))){
          return array(
            'ga_properties' => $ga_properties,
            'analytics_properties' => false,
            'analytics_g4_properties' => false,
            'defaults' => $ga4wp_settings->init_ga4wp_auth_defaults(),
          );
        }else{
          return array(
            //'analytics_properties' => $ga4wp_auth->get_analytics_properties(),
            'analytics_g4_properties' => $ga4wp_auth->get_analytics_g4_properties(),
            'defaults' => $ga4wp_settings->init_ga4wp_auth_defaults(),
          );
        }
      case 'settings':
        return array(
          'defaults' => $ga4wp_settings->init_ga4wp_track_defaults(),
        );
      case 'support':
        return array();
      default:
        return array();
    }
  }
}
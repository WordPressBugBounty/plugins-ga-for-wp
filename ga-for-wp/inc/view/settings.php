<?php
/* controlling view of dashboard*/
if (!defined('ABSPATH')) {
  die;
}

/* intiating variables */
$errors = '';

/* getting dashboard settings value */
if (!get_option('ga4wp_settings')) {
  $ga4wp_settings = $defaults;
  update_option('ga4wp_settings', $defaults);
} else {
  $ga4wp_settings = get_option('ga4wp_settings');
}

/* storing Event settings */
if (isset($_POST['ga4wp_event_settings']) && wp_verify_nonce($_POST['ga4wp_nonce_header'], 'ga4wp_event_submit') && current_user_can('manage_options')) {
  $ga4wp_event_settings_save = GA4WP_Settings::get_instance()->parse_ga4wp_bool_settings($_POST['ga4wp_event_settings']);
  if ($ga4wp_event_settings_save) {
    update_option('ga4wp_event_settings', $ga4wp_event_settings_save);
    echo '<script>
        jQuery(document).ready(function(){
           M.toast({html:"' . esc_js(__('Setting Saved!', 'ga-for-wp-text')) . '", classes:"rounded teal", displayLength:4000});
        });
    </script>';
    $ga4wp_event_settings = $ga4wp_event_settings_save;
  } else {
    $errors .= __('Error while saving data!', 'ga-for-wp-text') . '<br>';
    $ga4wp_event_settings = $ga4wp_event_settings_save;
  }
}

/* saving tracking value on successful submission */
if (isset($_POST['ga4wp_track_settings']) && wp_verify_nonce($_POST['ga4wp_nonce_header'], 'ga4wp_track_submit') && current_user_can('manage_options')) {
    $raw_track = $_POST['ga4wp_track_settings'];
    $mp_key = sanitize_text_field(wp_unslash($raw_track['google_measurement_api'] ?? ''));
    $mp_key = str_replace(' ', '', $mp_key);
    unset($raw_track['google_measurement_api']);
    $ga4wp_track_settings_save = GA4WP_Settings::get_instance()->parse_ga4wp_bool_settings($raw_track);
    if ($ga4wp_track_settings_save !== false) {
      $ga4wp_track_settings_save['google_measurement_api'] = $mp_key;
      update_option('ga4wp_track_settings', $ga4wp_track_settings_save);
      if ($mp_key) { update_option('measurement_key', $mp_key); } else { delete_option('measurement_key'); }
      echo '<script>
            jQuery(document).ready(function(){
               M.toast({html:"' . esc_js(__('Setting Saved!', 'ga-for-wp-text')) . '", classes:"rounded teal", displayLength:4000});
            });
        </script>';
      $ga4wp_track_settings = $ga4wp_track_settings_save;
    } else {
      $errors .= __('Error while saving data!', 'ga-for-wp-text') . '<br>';
      $ga4wp_track_settings = $ga4wp_track_settings_save;
    }
}
/* storing Dashboard tab visibility settings (premium only) */
if (isset($_POST['ga4wp_dashboard_settings']) && wp_verify_nonce($_POST['ga4wp_nonce_header'], 'ga4wp_dashboard_submit') && current_user_can('manage_options')) {
  $ga4wp_dashboard_settings_save = GA4WP_Settings::get_instance()->parse_ga4wp_dashboard_settings($_POST['ga4wp_dashboard_settings']);
  if ($ga4wp_dashboard_settings_save !== false) {
    update_option('ga4wp_dashboard_settings', $ga4wp_dashboard_settings_save);
    // Save drag-and-drop tab order
    if (!empty($_POST['ga4wp_tab_order'])) {
      $default_keys     = array_keys(GA4WP_Settings::get_instance()->init_ga4wp_dashboard_defaults());
      $saved_report_ids = array_keys(get_option('ga4wp_saved_explore_reports', []));
      $valid_keys       = array_merge($default_keys, $saved_report_ids);
      $raw_order        = array_map('sanitize_key', explode(',', sanitize_text_field(wp_unslash($_POST['ga4wp_tab_order']))));
      $clean_order      = array_values(array_intersect($raw_order, $valid_keys));
      if (!empty($clean_order)) update_option('ga4wp_tab_order', $clean_order);
    }
    // Save enabled/disabled state for saved explore reports
    $saved_reports = get_option('ga4wp_saved_explore_reports', []);
    if (!empty($saved_reports)) {
      $enabled_raw = is_array($_POST['ga4wp_saved_report_enabled'] ?? null) ? $_POST['ga4wp_saved_report_enabled'] : [];
      foreach ($saved_reports as $rid => &$rcfg) {
        $rcfg['enabled'] = isset($enabled_raw[$rid]) && $enabled_raw[$rid] === 'yes';
      }
      unset($rcfg);
      update_option('ga4wp_saved_explore_reports', $saved_reports);
    }
    echo '<script>
        jQuery(document).ready(function(){
           M.toast({html:"' . esc_js(__('Setting Saved!', 'ga-for-wp-text')) . '", classes:"rounded teal", displayLength:4000});
        });
    </script>';
  } else {
    $errors .= __('Error while saving dashboard settings!', 'ga-for-wp-text') . '<br>';
  }
}

/* storing WordPress Dashboard Widget visibility settings */
if (isset($_POST['ga4wp_widget_submit']) && wp_verify_nonce($_POST['ga4wp_nonce_header'], 'ga4wp_widget_submit') && current_user_can('manage_options')) {
  $allowed = ['overview_report', 'country_report', 'language_report', 'device_report', 'quick_stats'];
  $raw     = is_array($_POST['ga4wp_widget_settings'] ?? null) ? $_POST['ga4wp_widget_settings'] : [];
  $saved   = [];
  foreach ($allowed as $k) {
    $saved[$k] = (isset($raw[$k]) && $raw[$k] === 'yes') ? 'yes' : 'no';
  }
  update_option('ga4wp_widget_settings', $saved);
  echo '<script>jQuery(document).ready(function(){ M.toast({html:"' . esc_js(__('Setting Saved!', 'ga-for-wp-text')) . '", classes:"rounded teal", displayLength:4000}); });</script>';
}

/* saving Custom Dimension enabled/disabled state (premium only) */
if (isset($_POST['ga4wp_custom_dim_submit']) && wp_verify_nonce($_POST['ga4wp_nonce_header'], 'ga4wp_custom_dim_submit') && current_user_can('manage_options') && function_exists('gfw_fs') && gfw_fs()->can_use_premium_code__premium_only()) {
  $all_keys = array_keys(GA4WP_Settings::get_instance()->ga4wp_custom_dimensions);
  $enabled  = is_array($_POST['ga4wp_custom_dim_enabled'] ?? null) ? array_map('sanitize_key', $_POST['ga4wp_custom_dim_enabled']) : [];
  $settings = [];
  foreach ($all_keys as $k) {
    $settings[$k] = in_array($k, $enabled, true);
  }
  update_option('ga4wp_custom_dimension_settings', $settings);
  echo '<script>jQuery(document).ready(function(){ M.toast({html:"' . esc_js(__('Custom Dimension settings saved!', 'ga-for-wp-text')) . '", classes:"rounded teal", displayLength:4000}); });</script>';
}

/* storing Email report settings (premium only) */
if (isset($_POST['ga4wp_email_submit']) && wp_verify_nonce($_POST['ga4wp_nonce_header'], 'ga4wp_email_submit') && current_user_can('manage_options') && function_exists('gfw_fs') && gfw_fs()->can_use_premium_code__premium_only()) {
  $raw   = isset($_POST['ga4wp_email_settings']) && is_array($_POST['ga4wp_email_settings'])
           ? $_POST['ga4wp_email_settings'] : [];
  $saved = GA4WP_Settings::get_instance()->parse_ga4wp_email_settings($raw);
  update_option('ga4wp_email_settings', $saved);
  echo '<script>jQuery(document).ready(function(){ M.toast({html:"' . esc_js(__('Email settings saved!', 'ga-for-wp-text')) . '", classes:"rounded teal", displayLength:4000}); });</script>';
}

if (isset($_POST['ga4wp_advance_submit']) && wp_verify_nonce($_POST['ga4wp_nonce_header'], 'ga4wp_advance_submit') && current_user_can('manage_options')) {
  if (!empty($_POST['ga4wp_advance_settings'])) {
    if (isset($_POST['ga4wp_advance_settings']['facebook_pixel_code']) && isset($_POST['ga4wp_advance_settings']['facebook_pixel'])) {
      if (empty($_POST['ga4wp_advance_settings']['facebook_pixel_code'])) {
        $errors .= __('Please supply proper Facebook Pixel code!', 'ga-for-wp-text') . '<br>';
      }
    }
    if (isset($_POST['ga4wp_advance_settings']['google_adword_code']) && isset($_POST['ga4wp_advance_settings']['google_adword'])) {
      if (empty($_POST['ga4wp_advance_settings']['google_adword_code'])) {
        $errors .= __('Please supply proper Google Adword code!', 'ga-for-wp-text') . '<br>';
      }
      if (!isset($_POST['ga4wp_advance_settings']['google_adword_label']) || empty($_POST['ga4wp_advance_settings']['google_adword_label'])) {
        $errors .= __('Please supply proper Google Adword Label!', 'ga-for-wp-text') . '<br>';
      }
    }
    if (empty($errors)) {
      $ga4wp_advance_settings_save = GA4WP_Settings::get_instance()->parse_ga4wp_advance_settings($_POST['ga4wp_advance_settings']);
      if ($ga4wp_advance_settings_save) {
        update_option('ga4wp_advance_settings', $ga4wp_advance_settings_save);
        echo '<script>
              jQuery(document).ready(function(){
                 M.toast({html:"' . esc_js(__('Setting Saved!', 'ga-for-wp-text')) . '", classes:"rounded teal", displayLength:4000});
              });
          </script>';
        $ga4wp_advance_settings = $_POST['ga4wp_advance_settings'];
      } else {
        $errors .= __('Error while saving data! May be data is not in proper format. Please correct Data formats.', 'ga-for-wp-text') . '<br>';
        $ga4wp_advance_settings = $_POST['ga4wp_advance_settings'];
      }
    } else {
      $ga4wp_advance_settings = $_POST['ga4wp_advance_settings'];
    }
  } else {
    $errors .= __('there is nothing new to save', 'ga-for-wp-text');
  }
}
/* displaying errors */
if (strlen($errors) > 0) {
  echo '<script>
            jQuery(document).ready(function(){
               M.toast({html:"' . esc_js(__('Please correct following Errors:', 'ga-for-wp-text')) . '", classes:"rounded red", displayLength:6000});
               M.toast({html:"' . esc_js(strip_tags($errors)) . '", classes:"rounded red", displayLength:8000});
            });
        </script>';
}
// Dashboard report tabs (audience/acquisition/etc.) and their WP-admin widget
// counterparts are all fetched through the Google API against a linked GA4
// property. A manually-configured setup (plain Measurement ID, no property_id)
// has no such property, so both settings panes would only manage features the
// site can't actually use.
$ga4wp_auth_for_dash = get_option('ga4wp_auth_settings');
$ga4wp_has_property  = !empty($ga4wp_auth_for_dash['property_id']);
?>

<div class="ga4wp-col s12 ga4wp-options">
  <div class="ga4wp-tab-bar ga4wp-settings-tabs">
    <a class="ga4wp-tab-a" href="#set-tracking" data-settab>
      <span class="material-icons-round">tune</span><?php _e('Tracking', 'ga-for-wp-text'); ?>
    </a>
    <a class="ga4wp-tab-a" href="#set-events" data-settab>
      <span class="material-icons-round">bolt</span><?php _e('Events', 'ga-for-wp-text'); ?>
    </a>
    <?php if ($ga4wp_has_property) : ?>
    <a class="ga4wp-tab-a" href="#set-widgets" data-settab>
      <span class="material-icons-round">widgets</span><?php _e('Widgets', 'ga-for-wp-text'); ?>
    </a>
    <a class="ga4wp-tab-a" href="#set-dashboard" data-settab>
      <span class="material-icons-round">dashboard</span><?php _e('Dashboard', 'ga-for-wp-text'); ?>
    </a>
    <?php endif; ?>
    <a class="ga4wp-tab-a" href="#set-custom-dims" data-settab>
      <span class="material-icons-round">data_object</span><?php _e('Custom Dimensions', 'ga-for-wp-text'); ?>
      <?php if ( ! ( function_exists('gfw_fs') && gfw_fs()->can_use_premium_code__premium_only() ) ) : ?>
      <span class="material-icons" style="font-size:13px;color:#F59E0B;vertical-align:middle;margin-left:2px;">workspace_premium</span>
      <?php endif; ?>
    </a>
    <a class="ga4wp-tab-a" href="#set-email" data-settab>
      <span class="material-icons-round">email</span><?php _e('Email', 'ga-for-wp-text'); ?>
      <?php if ( ! ( function_exists('gfw_fs') && gfw_fs()->can_use_premium_code__premium_only() ) ) : ?>
      <span class="material-icons" style="font-size:13px;color:#F59E0B;vertical-align:middle;margin-left:2px;">workspace_premium</span>
      <?php endif; ?>
    </a>
  </div>
  <div id="set-tracking" class="ga4wp-col s12 ga4wp-settings-pane" style="display:none;"></div>
  <div id="set-events" class="ga4wp-col s12 ga4wp-settings-pane" style="display:none;"></div>
  <?php if ($ga4wp_has_property) : ?>
  <div id="set-widgets" class="ga4wp-col s12 ga4wp-settings-pane" style="display:none;"></div>
  <div id="set-dashboard" class="ga4wp-col s12 ga4wp-settings-pane" style="display:none;"></div>
  <?php endif; ?>
  <div id="set-custom-dims" class="ga4wp-col s12 ga4wp-settings-pane" style="display:none;"></div>
  <div id="set-email" class="ga4wp-col s12 ga4wp-settings-pane" style="display:none;"></div>
</div>
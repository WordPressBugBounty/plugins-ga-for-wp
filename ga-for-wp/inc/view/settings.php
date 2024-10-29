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
if (isset($_POST['ga4wp_event_settings']) && wp_verify_nonce($_POST['ga4wp_nonce_header'], 'ga4wp_event_submit')) {
  $ga4wp_event_settings_save = GA4WP_Settings::get_instance()->parse_ga4wp_bool_settings($_POST['ga4wp_event_settings']);
  if ($ga4wp_event_settings_save) {
    update_option('ga4wp_event_settings', $ga4wp_event_settings_save);
    echo '<script>
        jQuery(document).ready(function(){
           M.toast({html: ' . __('Setting Saved!', 'ga-for-wp-text') . ', classes: \'rounded teal\', displayLength:4000});
        });
    </script>';
    $ga4wp_event_settings = $ga4wp_event_settings_save;
  } else {
    $errors .= __('Error while saving data!', 'ga-for-wp-text') . '<br>';
    $ga4wp_event_settings = $ga4wp_event_settings_save;
  }
}

/* saving tracking value on successful submission */
if (isset($_POST['ga4wp_track_settings']) && wp_verify_nonce($_POST['ga4wp_nonce_header'], 'ga4wp_track_submit')) {
    $ga4wp_track_settings_save = GA4WP_Settings::get_instance()->parse_ga4wp_bool_settings($_POST['ga4wp_track_settings']);
    if ($ga4wp_track_settings_save) {
      update_option('ga4wp_track_settings', $ga4wp_track_settings_save);
      echo '<script>
            jQuery(document).ready(function(){
               M.toast({html:' . __('Setting Saved!', 'ga-for-wp-text') . ', classes: \'rounded teal\', displayLength:4000});
            });
        </script>';
      $ga4wp_track_settings = $ga4wp_track_settings_save;
    } else {
      $errors .= __('Error while saving data!', 'ga-for-wp-text') . '<br>';
      $ga4wp_track_settings = $ga4wp_track_settings_save;
    }
}
if (isset($_POST['ga4wp_advance_submit']) && wp_verify_nonce($_POST['ga4wp_nonce_header'], 'ga4wp_advance_submit')) {
  if (!empty($_POST['ga4wp_advance_settings'])) {
    //if (isset($_POST['ga4wp_advance_settings']['google_measuremnt']) && isset($_POST['ga4wp_advance_settings']['google_measuremnt'])) {
      if (!empty($_POST['ga4wp_advance_settings']['google_measurement_api'])) {
        $google_measurement_api = str_replace(' ', '', $_POST['ga4wp_advance_settings']['google_measurement_api']);
        update_option('measurement_key',$google_measurement_api);
      }else{
        delete_option('measurement_key');
      }
    //}
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
                 M.toast({html: ' . __('Setting Saved!', 'ga-for-wp-text') . ', classes: \'rounded teal\', displayLength:4000});
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
               M.toast({html: ' . __('Please correct following Errors:', 'ga-for-wp-text') . ', classes: \'rounded red\', displayLength:6000});
               M.toast({html: ' . $errors . ', classes: \'rounded red\', displayLength:8000});
            });
        </script>';
}
?>

<div class="ga4wp-col s12 ga4wp-options">
  <div class="ga4wp-col s12 top-mar">
    <div class="ga4wp-col m6 s12">
      <h5 class="left zero-mar">Settings</h5>
    </div>
    <div class="ga4wp-col m6 s12">
      <?php if (gfw_fs()->is_not_paying() && !(gfw_fs()->is_trial())) { ?>
        <a class="waves-effect waves-light btn right upgrade-btn" style="margin-left:15px"
          href="https://ga4wp.com/pricing/"><?php _e('Upgrade to Pro!', 'ga-for-wp-text'); ?></a>
      <?php } ?>
      <a class="waves-effect waves-light btn right" href="https://ga4wp.com/documentation/" target="_blank"><i
          class="material-icons left">book</i>
        <?php _e('Documentation', 'ga-for-wp-text'); ?>
      </a>
    </div>
  </div>
  <div class="clearfix"></div>
  <div class="divider top-mar" style="margin-bottom:20px"></div>
  <div class="ga4wp-row">
    <ul class="tabs">
      <li class="tab ga4wp-col m4 s4"><a id="set-tracking-tab" href="#set-tracking">
          <?php _e('Tracking Settings', 'ga-for-wp-text'); ?>
        </a></li>
      <li class="tab ga4wp-col m4 s4"><a id="set-events-tab" href="#set-events">
          <?php _e('Events Settings', 'ga-for-wp-text'); ?>
        </a></li>
      <li class="tab ga4wp-col m4 s4"><a id="set-advanced-tab" href="#set-advanced">
          <?php _e('Advanced Integrations', 'ga-for-wp-text'); ?>
        </a></li>
    </ul>
  </div>
  <div id="set-tracking" class="ga4wp-col s12"></div>
  <div id="set-events" class="ga4wp-col s12"></div>
  <div id="set-advanced" class="ga4wp-col s12"></div>
  <div class="clearfix"></div>
</div>
<?php
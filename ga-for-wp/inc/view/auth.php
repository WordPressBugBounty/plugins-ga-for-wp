<?php
/* controlling view of Authentication settings*/
if (!defined('ABSPATH')) {
  die;
}
/* intiating variables */
$errors = '';
$manual_tracking = false;

/* getting settings values*/
$ga4wp_auth_settings = get_option('ga4wp_auth_settings') ?: $defaults;

/* storing Authentication settings */
if (isset($_POST['ga4wp_auth_submit']) && wp_verify_nonce($_POST['ga4wp_nonce_header'], 'ga4wp_auth_submit') && current_user_can('manage_options')) {
  $ga4wp_auth_settings_save = GA4WP_Settings::get_instance()->parse_ga4wp_auth_settings($_POST['ga4wp_auth_settings']);
  if (isset($ga4wp_auth_settings_save['agreement'])) {
    if (!empty($ga4wp_auth_settings_save['property_id']) || !empty($ga4wp_auth_settings_save['tracking_id'])) {
      if (isset($ga4wp_auth_settings_save['property_id'])) {
        if (strpos((string)$ga4wp_auth_settings_save['property_id'], 'G') !== false) {
          unset($ga4wp_auth_settings_save['tracking_id']);
          unset($ga4wp_auth_settings_save['manual_tracking']);
          update_option('ga4wp_auth_settings', $ga4wp_auth_settings_save);
          echo '<script>
                jQuery(document).ready(function(){
                   M.toast({html:"' . __('Setting Saved!', 'ga-for-wp-text') . '", classes: "rounded teal", displayLength:4000});
                   setTimeout(function(){window.location.reload(1);}, 1000);
                });
            </script>';
          $ga4wp_auth_settings = $ga4wp_auth_settings_save;
        } else {
          if ($ga4wp_auth_settings_save) {
            unset($ga4wp_auth_settings_save['tracking_id']);
            unset($ga4wp_auth_settings_save['manual_tracking']);
            update_option('ga4wp_auth_settings', $ga4wp_auth_settings_save);
            echo '<script>
                jQuery(document).ready(function(){
                   M.toast({html: "' . __('Setting Saved!', 'ga-for-wp-text') . '", classes:"rounded teal", displayLength:4000});
                   setTimeout(function(){window.location.reload(1);}, 1000);
                });
            </script>';
            $ga4wp_auth_settings = $ga4wp_auth_settings_save;
          } else {
            $errors .= __('Please complete Google Analytics Website Linking Process!', 'ga-for-wp-text') . '<br>';
            $ga4wp_auth_settings = $ga4wp_auth_settings_save;
          }
        }
      } else {
        $manual_tracking = true;
        if (isset($ga4wp_auth_settings_save['manual_tracking'])) {
          if (strpos((string)$ga4wp_auth_settings_save['tracking_id'], 'G') !== false) {
            update_option('ga4wp_auth_settings', $ga4wp_auth_settings_save);
            echo '<script>
                      jQuery(document).ready(function(){
                        M.toast({html:"' . __('Setting Saved!', 'ga-for-wp-text') . '", classes: "rounded teal", displayLength:4000});
                        setTimeout(function(){window.location.reload(1);}, 1000);
                      });
                  </script>';
            $ga4wp_auth_settings = $ga4wp_auth_settings_save;
          } else {
            update_option('ga4wp_auth_settings', $ga4wp_auth_settings_save);
            echo '<script>
                      jQuery(document).ready(function(){
                        M.toast({html:"' . __('Setting Saved!', 'ga-for-wp-text') . '", classes: "rounded teal", displayLength:4000});
                        setTimeout(function(){window.location.reload(1);}, 1000);
                      });
                  </script>';
            $ga4wp_auth_settings = $ga4wp_auth_settings_save;
          }
        } else {
          $errors .= __('Please confirm using manual tracking or use auto connect facility', 'ga-for-wp-text') . '<br>';
          if (get_option('ga4wp_auth_settings')) {
            delete_option('ga4wp_auth_settings');
            $ga4wp_auth_settings = $ga4wp_auth_settings_save;
          }
          $ga4wp_auth_settings = $ga4wp_auth_settings_save;
        }
      }
    } else {
      $errors .= __('Please complete Google Analytics Website Linking Process!', 'ga-for-wp-text') . '<br>';
      if (get_option('ga4wp_auth_settings')) {
        delete_option('ga4wp_auth_settings');
        $ga4wp_auth_settings = $ga4wp_auth_settings_save;
        header('Refresh:0');
      }
      $ga4wp_auth_settings = $ga4wp_auth_settings_save;
    }
  } else {
    $errors .= __('Please agree with privacy policy and terms of service of plugin!', 'ga-for-wp-text') . '<br>';
    $ga4wp_auth_settings = $ga4wp_auth_settings_save;
  }
} else {
  if ((($ga4wp_auth_settings['tracking_id'] ?? false) || ($ga4wp_auth_settings['manual_tracking'] ?? false)) && empty($ga4wp_auth_settings['property_id'])) {
    $manual_tracking = true;
  }
  if (!empty($analytics_properties)) {
    $manual_tracking = false;
  }
}
/* display error messages */
if (strlen($errors) > 0) {
  echo '<script>
            jQuery(document).ready(function(){
               M.toast({html: "' . esc_js(__('Please correct following Errors:', 'ga-for-wp-text')) . '", classes: "rounded red", displayLength:6000});
               M.toast({html: "' . esc_js(strip_tags($errors)) . '", classes: "rounded red", displayLength:8000});
            });
        </script>';
}
/* creating Authentication forms */
$selector = ((!empty($analytics_g4_properties) && is_array($analytics_g4_properties)) || (!empty($ga_properties) && is_array($ga_properties)));
$has_token    = (bool) get_option('ga4wp_access_token');
$has_property = !empty($ga4wp_auth_settings['property_id'] ?? '');
$has_tracking = !empty($ga4wp_auth_settings['tracking_id'] ?? '');
$is_reauth    = GA4WP_Auth::is_reauth_required();

/* Build GA4 accounts list for create-property form (available when user just auth'd) */
$ga4wp_create_accounts = array();
if (!empty($analytics_g4_properties) && is_array($analytics_g4_properties)) {
  foreach ($analytics_g4_properties as $_acc_s) {
    if (!empty($_acc_s->account) && !empty($_acc_s->displayName)) {
      $ga4wp_create_accounts[$_acc_s->account] = $_acc_s->displayName;
    }
  }
}

$ga4wp_regions = array(
  'US' => 'United States',
  'GB' => 'United Kingdom',
  'CA' => 'Canada',
  'AU' => 'Australia',
  'IN' => 'India',
  'DE' => 'Germany',
  'FR' => 'France',
  'ES' => 'Spain',
  'IT' => 'Italy',
  'NL' => 'Netherlands',
  'SE' => 'Sweden',
  'NO' => 'Norway',
  'DK' => 'Denmark',
  'FI' => 'Finland',
  'PL' => 'Poland',
  'BR' => 'Brazil',
  'MX' => 'Mexico',
  'AR' => 'Argentina',
  'CO' => 'Colombia',
  'CL' => 'Chile',
  'JP' => 'Japan',
  'KR' => 'South Korea',
  'CN' => 'China',
  'SG' => 'Singapore',
  'MY' => 'Malaysia',
  'ID' => 'Indonesia',
  'TH' => 'Thailand',
  'PH' => 'Philippines',
  'VN' => 'Vietnam',
  'NZ' => 'New Zealand',
  'ZA' => 'South Africa',
  'NG' => 'Nigeria',
  'EG' => 'Egypt',
  'AE' => 'United Arab Emirates',
  'SA' => 'Saudi Arabia',
  'TR' => 'Turkey',
  'RU' => 'Russia',
  'UA' => 'Ukraine',
  'CH' => 'Switzerland',
  'AT' => 'Austria',
  'BE' => 'Belgium',
  'PT' => 'Portugal',
  'CZ' => 'Czech Republic',
  'RO' => 'Romania',
  'HU' => 'Hungary',
  'GR' => 'Greece',
  'IL' => 'Israel',
  'PK' => 'Pakistan',
  'BD' => 'Bangladesh',
  'HK' => 'Hong Kong',
  'TW' => 'Taiwan',
);

$ga4wp_timezones = array(
  'UTC'                  => 'UTC',
  'America/New_York'     => 'Eastern Time (New York)',
  'America/Chicago'      => 'Central Time (Chicago)',
  'America/Denver'       => 'Mountain Time (Denver)',
  'America/Los_Angeles'  => 'Pacific Time (Los Angeles)',
  'America/Anchorage'    => 'Alaska',
  'Pacific/Honolulu'     => 'Hawaii',
  'America/Toronto'      => 'Eastern Time (Toronto)',
  'America/Vancouver'    => 'Pacific Time (Vancouver)',
  'America/Sao_Paulo'    => 'São Paulo',
  'America/Mexico_City'  => 'Mexico City',
  'Europe/London'        => 'London',
  'Europe/Paris'         => 'Paris',
  'Europe/Berlin'        => 'Berlin',
  'Europe/Madrid'        => 'Madrid',
  'Europe/Rome'          => 'Rome',
  'Europe/Amsterdam'     => 'Amsterdam',
  'Europe/Warsaw'        => 'Warsaw',
  'Europe/Istanbul'      => 'Istanbul',
  'Europe/Moscow'        => 'Moscow',
  'Asia/Dubai'           => 'Dubai',
  'Asia/Kolkata'         => 'India (IST)',
  'Asia/Dhaka'           => 'Dhaka',
  'Asia/Bangkok'         => 'Bangkok',
  'Asia/Singapore'       => 'Singapore',
  'Asia/Shanghai'        => 'China (CST)',
  'Asia/Tokyo'           => 'Tokyo',
  'Asia/Seoul'           => 'Seoul',
  'Australia/Sydney'     => 'Sydney',
  'Pacific/Auckland'     => 'Auckland',
);

$ga4wp_currencies = array(
  'USD' => 'USD — US Dollar',
  'EUR' => 'EUR — Euro',
  'GBP' => 'GBP — British Pound',
  'JPY' => 'JPY — Japanese Yen',
  'AUD' => 'AUD — Australian Dollar',
  'CAD' => 'CAD — Canadian Dollar',
  'CHF' => 'CHF — Swiss Franc',
  'CNY' => 'CNY — Chinese Yuan',
  'INR' => 'INR — Indian Rupee',
  'BRL' => 'BRL — Brazilian Real',
  'MXN' => 'MXN — Mexican Peso',
  'KRW' => 'KRW — South Korean Won',
  'SGD' => 'SGD — Singapore Dollar',
  'SEK' => 'SEK — Swedish Krona',
  'NOK' => 'NOK — Norwegian Krone',
  'DKK' => 'DKK — Danish Krone',
  'PLN' => 'PLN — Polish Zloty',
  'TRY' => 'TRY — Turkish Lira',
  'ZAR' => 'ZAR — South African Rand',
  'AED' => 'AED — UAE Dirham',
  'THB' => 'THB — Thai Baht',
  'IDR' => 'IDR — Indonesian Rupiah',
  'MYR' => 'MYR — Malaysian Ringgit',
  'PHP' => 'PHP — Philippine Peso',
  'NZD' => 'NZD — New Zealand Dollar',
  'HKD' => 'HKD — Hong Kong Dollar',
);
?>
<div class="ga4wp-col s12 ga4wp-options ga4wp-auth-page">
  <form action="" method="POST">
    <div class="progress">
      <div class="indeterminate"></div>
    </div>
    <div class="ga4wp-auth-center">
      <?php if (!$has_token && !$is_reauth) { ?>
        <!-- ── 1. Connection Method (flat) — first-time setup only, not shown when
             re-authenticating an already-configured connection ── -->
        <div class="ga4wp-auth-method-bar">
          <div class="ga4wp-auth-method-inner">
            <div class="ga4wp-auth-method-title">
              <span class="material-icons-round">sync_alt</span>
              <?php _e('Connection Method', 'ga-for-wp-text'); ?>
            </div>
            <p class="ga4wp-auth-method-hint"><?php _e('Auto Connect signs in with Google. Manual Connect uses a Measurement ID you enter directly.', 'ga-for-wp-text'); ?></p>
            <div class="ga4wp-auth-method-switch">
              <span class="ga4wp-auth-method-opt ga4wp-auth-method-opt-auto<?php echo (!isset($ga4wp_auth_settings['manual_tracking']) || !$ga4wp_auth_settings['manual_tracking']) ? ' active' : ''; ?>">
                <?php _e('Auto', 'ga-for-wp-text'); ?>
                <span class="ga4wp-auth-recommended"><?php _e('Recommended', 'ga-for-wp-text'); ?></span>
              </span>
              <?php $switch_cls = ($has_tracking && !$has_property) ? 'off' : ''; ?>
              <div class="switch <?php echo $switch_cls; ?>">
                <label>
                  <input <?php if (!$has_property && $has_token) {
                            echo 'disabled';
                          } ?>
                    id="ga4wp_auth_settings[manual_tracking]"
                    class="check_manual"
                    name="ga4wp_auth_settings[manual_tracking]"
                    value="yes" type="checkbox"
                    <?php checked(isset($ga4wp_auth_settings['manual_tracking']) && $ga4wp_auth_settings['manual_tracking']); ?>>
                  <span class="lever"></span>
                </label>
              </div>
              <span class="ga4wp-auth-method-opt ga4wp-auth-method-opt-manual<?php echo (isset($ga4wp_auth_settings['manual_tracking']) && $ga4wp_auth_settings['manual_tracking']) ? ' active' : ''; ?>">
                <?php _e('Manual', 'ga-for-wp-text'); ?>
              </span>
            </div>
          </div>
        </div>
      <?php } ?>

      <div id="auto" class="ga4wp-col s12">

        <!-- ── Re-auth required ── -->
        <?php if ($is_reauth) { ?>
          <div class="ga4wp-row ga4s-wrap">
            <div class="ga4wp-col s12">
              <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:14px;padding:32px 28px;text-align:center;max-width:520px;margin:40px auto 0;">
                <span class="material-icons-round" style="color:#D97706;font-size:40px;display:block;margin-bottom:12px;">lock_reset</span>
                <div style="font-size:16px;font-weight:700;color:#0F172A;margin-bottom:10px;"><?php _e('Re-authentication required', 'ga-for-wp-text'); ?></div>
                <p style="font-size:13px;color:#92400E;margin:0 0 24px;"><?php _e('Your Google Analytics access token has expired or been revoked. Re-link your account to restore access to all reports and features.', 'ga-for-wp-text'); ?></p>
                <a class="ga4wp-auth-google-btn GA4WP-authenticate" style="display:inline-flex;">
                  <img width="18" alt="Google" src="<?php echo esc_url(GA4WP_URL . 'assests/images/google-icon.png'); ?>">
                  <?php _e('Re-Link with Google Account', 'ga-for-wp-text'); ?>
                </a>
              </div>
            </div>
          </div>
        <?php } ?>

        <!-- ── 2. Before You Connect — first-time setup only, not shown when
             re-authenticating an already-configured connection ── -->
        <?php if (!$has_token && !$is_reauth) { ?>
          <div class="ga4wp-row ga4s-wrap">
            <div class="ga4wp-col s12">
              <div class="ga4s-section ga4wp-auth-prereq">
                <div class="ga4s-section-head">
                  <span class="material-icons-round">fact_check</span>
                  <div>
                    <div class="ga4s-section-title"><?php _e('Before you connect', 'ga-for-wp-text'); ?></div>
                    <div class="ga4s-section-desc"><?php _e('Make sure these requirements are met before linking your account', 'ga-for-wp-text'); ?></div>
                  </div>
                </div>
                <div class="ga4s-rows">
                  <div class="ga4s-row">
                    <div class="ga4wp-auth-req-item">
                      <span class="material-icons-round ga4wp-auth-req-icon">check_circle</span>
                      <div>
                        <div class="ga4s-row-label"><?php _e('Active Google Analytics account', 'ga-for-wp-text'); ?></div>
                        <div class="ga4s-row-hint"><?php _e("Don't have one?", 'ga-for-wp-text'); ?> <a href="https://marketingplatform.google.com/about/analytics/" target="_blank" style="color:var(--ga-brand);"><?php _e('Sign up here', 'ga-for-wp-text'); ?></a></div>
                      </div>
                    </div>
                  </div>
                  <div class="ga4s-row">
                    <div class="ga4wp-auth-req-item">
                      <span class="material-icons-round ga4wp-auth-req-icon">check_circle</span>
                      <div>
                        <div class="ga4s-row-label"><?php _e('Active property configured in your GA account', 'ga-for-wp-text'); ?></div>
                        <div class="ga4s-row-hint"><?php _e('At least one GA4 property must be set up for your website', 'ga-for-wp-text'); ?></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php } ?>

        <!-- ── 3. Step notice after OAuth (no property selected yet) ── -->
        <?php if (!$has_property && $has_token && !$is_reauth) { ?>
          <div class="ga4wp-auth-step-notice auto-connect">
            <span class="material-icons-round">info</span>
            <span><?php _e('Google account linked! Choose an existing Analytics property below, or create a new one.', 'ga-for-wp-text'); ?></span>
          </div>
        <?php } ?>

        <!-- ── 4. Auto connect: Google button (shown in auto mode when no token) ── -->
        <?php if (!$has_token) { ?>
          <div class="auto-connect ga4wp-auth-google-row">
            <a class="ga4wp-auth-google-btn GA4WP-authenticate">
              <img width="18" alt="Google" src="<?php echo esc_url(GA4WP_URL . 'assests/images/google-icon.png'); ?>">
              <?php echo $selector ? __('Re-Link with Google Account', 'ga-for-wp-text') : __('Link with Google Analytics', 'ga-for-wp-text'); ?>
            </a>
          </div>
        <?php } ?>

        <!-- ── 5. Manual connect: Measurement ID — hidden by default; JS shows when manual mode active ── -->
        <div class="manual-connect ga4wp-row ga4s-wrap" style="display:<?php echo $manual_tracking ? 'block' : 'none'; ?>;">
          <div class="ga4wp-col s12">
            <div class="ga4s-section">
              <div class="ga4s-section-head">
                <span class="material-icons-round">edit</span>
                <div>
                  <div class="ga4s-section-title"><?php _e('Enter Measurement ID', 'ga-for-wp-text'); ?></div>
                  <div class="ga4s-section-desc"><?php _e('Paste your GA4 Measurement ID (G-XXXXXXXX) directly', 'ga-for-wp-text'); ?></div>
                </div>
              </div>
              <div class="ga4s-rows">
                <div class="ga4s-row">
                  <div class="ga4wp-auth-input-wrap">
                    <?php if ($has_tracking && !$has_property) { ?>
                      <div class="ga4wp-auth-id-badge">
                        <span class="material-icons-round">analytics</span>
                        <?php echo esc_html($ga4wp_auth_settings['tracking_id']); ?>
                      </div>
                      <input name="ga4wp_auth_settings[tracking_id]" type="hidden" value="<?php echo esc_attr($ga4wp_auth_settings['tracking_id']); ?>" class="tracking-id off">
                    <?php } else { ?>
                      <div class="input-field ga4wp-auth-field">
                        <input placeholder="G-XXXXXXXX"
                          id="ga4wp_auth_settings[tracking_id]"
                          name="ga4wp_auth_settings[tracking_id]"
                          type="text"
                          value="<?php echo esc_attr($ga4wp_auth_settings['tracking_id'] ?? ''); ?>"
                          class="tracking-id validate">
                        <span class="helper-text"><?php _e('Your GA4 Data Stream Measurement ID from Google Analytics', 'ga-for-wp-text'); ?></span>
                      </div>
                    <?php } ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── 6. Property selector (after OAuth, shown in auto mode) ── -->
        <?php if ($has_token && ($selector || !$has_property) && !$is_reauth) {
          /* Build $ga_properties from API response if not already built */
          if (isset($analytics_g4_properties) && !empty($analytics_g4_properties) && is_array($analytics_g4_properties)) {
            $api = GA4WP_Auth::get_instance()->get_google_management_api();
            foreach ($analytics_g4_properties as $account_summary) {
              if (isset($account_summary->propertySummaries)) {
                foreach ($account_summary->propertySummaries as $property) {
                  $web_data_streams = $api->get_web_data_streams($property->property);
                  if (isset($web_data_streams->dataStreams) && !empty($web_data_streams->dataStreams)) {
                    foreach ($web_data_streams->dataStreams as $web_data_stream) {
                      $optgroup = $property->displayName;
                      if (!isset($ga_properties[$optgroup])) $ga_properties[$optgroup] = [];
                      if (isset($web_data_stream->webStreamData->measurementId) && !empty($web_data_stream->webStreamData->measurementId))
                        $ga_properties[$optgroup][$web_data_stream->name . '|' . $web_data_stream->webStreamData->measurementId] = sprintf('%s (%s)', $web_data_stream->displayName, $web_data_stream->webStreamData->measurementId);
                      natcasesort($ga_properties[$optgroup]);
                    }
                  }
                }
              }
            }
          }
          if (isset($analytics_properties) && !empty($analytics_properties) && is_array($analytics_properties)) {
            foreach ($analytics_properties as $account_summary) {
              if (!isset($account_summary->kind, $account_summary->id, $account_summary->name, $account_summary->webProperties)) continue;
              if ('analytics#accountSummary' !== $account_summary->kind) continue;
              foreach ($account_summary->webProperties as $property) {
                if (!isset($property->kind, $property->id, $property->name)) continue;
                if ('analytics#webPropertySummary' !== $property->kind) continue;
                $optgroup = $account_summary->name;
                if (!isset($ga_properties[$optgroup])) $ga_properties[$optgroup] = [];
                $ga_properties[$optgroup][$account_summary->id . '|' . $property->id] = sprintf('%s (%s)', $property->name, $property->id);
                natcasesort($ga_properties[$optgroup]);
              }
            }
          }
          if (isset($ga_properties) && !empty($ga_properties)) update_option('ga_properties', $ga_properties);
        ?>
          <div class="auto-connect">

            <?php if ($has_property) {
              $pieces = explode('|', $ga4wp_auth_settings['property_id']);
              $display_id = $pieces[1] ?? $ga4wp_auth_settings['property_id'];
            ?>
              <!-- Connected: show badge + hidden input -->
              <div class="ga4wp-auth-connected-pill">
                <span class="material-icons-round">check_circle</span>
                <span><?php echo esc_html($display_id); ?></span>
              </div>
              <input id="ga4wp_auth_settings[property_id]" name="ga4wp_auth_settings[property_id]" type="hidden" value="<?php echo esc_attr($ga4wp_auth_settings['property_id']); ?>">
            <?php } else { ?>

              <?php $ga4wp_has_existing_props = !empty($ga_properties); ?>

              <!-- Not yet selected: show property dropdown (only when properties exist) -->
              <?php if ($ga4wp_has_existing_props) { ?>
                <div class="ga4wp-row ga4s-wrap">
                  <div class="ga4wp-col s12">
                    <div class="ga4s-section">
                      <div class="ga4s-section-head">
                        <span class="material-icons-round">analytics</span>
                        <div>
                          <div class="ga4s-section-title"><?php _e('Select Analytics Property', 'ga-for-wp-text'); ?></div>
                          <div class="ga4s-section-desc"><?php _e('Choose the property and data stream to link with this website', 'ga-for-wp-text'); ?></div>
                        </div>
                      </div>
                      <div class="ga4s-rows">
                        <div class="ga4s-row">
                          <div class="ga4wp-auth-input-wrap">
                            <div class="input-field ga4wp-auth-field">
                              <select id="ga4wp_auth_settings[property_id]" name="ga4wp_auth_settings[property_id]" class="property-id">
                                <?php foreach ($ga_properties as $optgroup_title => $optgroup_options) { ?>
                                  <optgroup label="<?php echo esc_attr($optgroup_title); ?>">
                                    <?php foreach ($optgroup_options as $key => $val) {
                                      $sel = (($ga4wp_auth_settings['property_id'] ?? '') == $key) ? 'selected="selected"' : '';
                                      echo '<option value="' . esc_attr($key) . '" ' . $sel . '>' . esc_html($val) . '</option>';
                                    } ?>
                                  </optgroup>
                                <?php } ?>
                              </select>
                              <span class="helper-text"><?php _e('Choose the property to track data for.', 'ga-for-wp-text'); ?></span>
                            </div>
                            <div style="margin-top:4px;text-align:right;">
                              <a href="#" id="ga4wp-refresh-props" style="font-size:11.5px;color:var(--ga-brand);text-decoration:none;display:inline-flex;align-items:center;gap:3px;opacity:.8;">
                                <span class="material-icons-round" style="font-size:13px;">refresh</span>
                                <?php _e('Refresh list', 'ga-for-wp-text'); ?>
                              </a>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php } else { ?>
                <!-- No properties found notice -->
                <div class="ga4wp-row ga4s-wrap">
                  <div class="ga4wp-col s12">
                    <div class="ga4wp-auth-step-notice" style="margin-bottom:0;">
                      <span class="material-icons-round">add_circle_outline</span>
                      <span><?php _e("No Analytics properties found in your account. Create one below to get started.", 'ga-for-wp-text'); ?></span>
                    </div>
                  </div>
                </div>
              <?php } ?>

              <!-- Create New Property toggle + form -->
              <div class="ga4wp-row ga4s-wrap" style="margin-top:6px;">
                <div class="ga4wp-col s12">
                  <?php if ($ga4wp_has_existing_props) { ?>
                    <a href="#" id="ga4wp-new-prop-toggle" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--ga-brand);text-decoration:none;padding:6px 0;">
                      <span class="material-icons-round" style="font-size:16px;">add_circle_outline</span>
                      <?php _e('Create a new property instead', 'ga-for-wp-text'); ?>
                    </a>
                  <?php } ?>

                  <div id="ga4wp-new-prop-form" <?php echo $ga4wp_has_existing_props ? 'style="display:none;"' : ''; ?>>
                    <div class="ga4s-section" style="border:1px solid var(--ga-brand);margin-top:<?php echo $ga4wp_has_existing_props ? '8px' : '0'; ?>;">
                      <div class="ga4s-section-head">
                        <span class="material-icons-round">add_business</span>
                        <div>
                          <div class="ga4s-section-title"><?php _e('Create New Analytics Property', 'ga-for-wp-text'); ?></div>
                          <div class="ga4s-section-desc"><?php _e('Set up a fresh GA4 property and web data stream', 'ga-for-wp-text'); ?></div>
                        </div>
                      </div>
                      <div class="ga4s-rows">

                        <!-- Account -->
                        <div class="ga4s-row">
                          <div class="ga4wp-auth-input-wrap">
                            <div class="input-field ga4wp-auth-field">
                              <select id="ga4wp-new-prop-account" <?php echo empty($ga4wp_create_accounts) ? 'data-needs-load="1"' : ''; ?>>
                                <?php if (!empty($ga4wp_create_accounts)) {
                                  foreach ($ga4wp_create_accounts as $res => $label) {
                                    echo '<option value="' . esc_attr($res) . '">' . esc_html($label) . '</option>';
                                  }
                                } else { ?>
                                  <option value=""><?php _e('Loading accounts…', 'ga-for-wp-text'); ?></option>
                                <?php } ?>
                              </select>
                              <label for="ga4wp-new-prop-account"><?php _e('Google Analytics Account', 'ga-for-wp-text'); ?></label>
                            </div>
                          </div>
                        </div>

                        <!-- No accounts found: link to create at Google Analytics -->
                        <div id="ga4wp-create-account-wrap" style="display:none;">
                          <div style="background:#F8FAFF;border:1px dashed #93C5FD;border-radius:8px;padding:12px 16px;margin-bottom:4px;">
                            <div style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#1E40AF;margin-bottom:8px;">
                              <span class="material-icons-round" style="font-size:16px;">info</span>
                              <?php _e('No Google Analytics accounts found', 'ga-for-wp-text'); ?>
                            </div>
                            <p style="font-size:12px;color:#475569;margin:0 0 10px;"><?php _e("Your Google account doesn't have a Google Analytics account yet. Please create one at Google Analytics, then come back and reload.", 'ga-for-wp-text'); ?></p>
                            <p style="margin:0;font-size:13px;">
                              <a href="https://analytics.google.com/analytics/web/#/provision" target="_blank" style="color:var(--ga-brand);font-weight:500;display:inline-flex;align-items:center;gap:4px;">
                                <span class="material-icons-round" style="font-size:14px;">open_in_new</span>
                                <?php _e('Create account at Google Analytics', 'ga-for-wp-text'); ?>
                              </a>
                              &nbsp;&middot;&nbsp;
                              <a href="#" id="ga4wp-reload-accounts" style="color:var(--ga-brand);"><?php _e('Reload accounts', 'ga-for-wp-text'); ?></a>
                            </p>
                          </div>
                        </div>

                        <!-- Property name -->
                        <div class="ga4s-row">
                          <div class="ga4wp-auth-input-wrap">
                            <div class="input-field ga4wp-auth-field">
                              <input type="text" id="ga4wp-new-prop-name" placeholder="<?php esc_attr_e('My Website', 'ga-for-wp-text'); ?>">
                              <label for="ga4wp-new-prop-name"><?php _e('Property Name', 'ga-for-wp-text'); ?></label>
                            </div>
                          </div>
                        </div>

                        <!-- Website URL -->
                        <div class="ga4s-row">
                          <div class="ga4wp-auth-input-wrap">
                            <div class="input-field ga4wp-auth-field">
                              <input type="url" id="ga4wp-new-prop-url" value="<?php echo esc_attr(get_site_url()); ?>">
                              <label for="ga4wp-new-prop-url" class="active"><?php _e('Website URL', 'ga-for-wp-text'); ?></label>
                            </div>
                          </div>
                        </div>

                        <!-- Timezone + Currency side by side -->
                        <div class="ga4s-row" style="display:flex;gap:12px;flex-wrap:wrap;">
                          <div class="ga4wp-auth-input-wrap" style="flex:1;min-width:160px;">
                            <div class="input-field ga4wp-auth-field">
                              <select id="ga4wp-new-prop-timezone">
                                <?php foreach ($ga4wp_timezones as $tz_val => $tz_label) {
                                  $sel = ($tz_val === 'America/New_York') ? ' selected' : '';
                                  echo '<option value="' . esc_attr($tz_val) . '"' . $sel . '>' . esc_html($tz_label) . '</option>';
                                } ?>
                              </select>
                              <label for="ga4wp-new-prop-timezone"><?php _e('Timezone', 'ga-for-wp-text'); ?></label>
                            </div>
                          </div>
                          <div class="ga4wp-auth-input-wrap" style="flex:1;min-width:140px;">
                            <div class="input-field ga4wp-auth-field">
                              <select id="ga4wp-new-prop-currency">
                                <?php foreach ($ga4wp_currencies as $cur_val => $cur_label) {
                                  $sel = ($cur_val === 'USD') ? ' selected' : '';
                                  echo '<option value="' . esc_attr($cur_val) . '"' . $sel . '>' . esc_html($cur_label) . '</option>';
                                } ?>
                              </select>
                              <label for="ga4wp-new-prop-currency"><?php _e('Currency', 'ga-for-wp-text'); ?></label>
                            </div>
                          </div>
                        </div>

                        <!-- Enhanced Measurement toggle -->
                        <div class="ga4s-row">
                          <div style="display:flex;align-items:center;gap:12px;">
                            <div class="switch">
                              <label>
                                <input type="checkbox" id="ga4wp-new-prop-enhanced" checked>
                                <span class="lever"></span>
                              </label>
                            </div>
                            <div>
                              <div style="font-size:13px;font-weight:500;"><?php _e('Enable Enhanced Measurement', 'ga-for-wp-text'); ?></div>
                              <div style="font-size:12px;color:var(--ga-muted);"><?php _e('Auto-tracks scrolls, outbound clicks, video plays, file downloads, and site search', 'ga-for-wp-text'); ?></div>
                            </div>
                          </div>
                        </div>

                        <!-- Submit -->
                        <div class="ga4s-row" style="padding-top:4px;">
                          <button type="button" id="ga4wp-create-prop-submit" class="ga4wp-auth-submit-btn" style="width:auto;padding:0 20px;">
                            <span class="material-icons-round">add_circle</span>
                            <?php _e('Create Property', 'ga-for-wp-text'); ?>
                          </button>
                          <span id="ga4wp-create-prop-spinner" style="display:none;margin-left:10px;vertical-align:middle;">
                            <div class="preloader-wrapper small active" style="width:20px;height:20px;">
                              <div class="spinner-layer spinner-teal-only">
                                <div class="circle-clipper left">
                                  <div class="circle"></div>
                                </div>
                                <div class="gap-patch">
                                  <div class="circle"></div>
                                </div>
                                <div class="circle-clipper right">
                                  <div class="circle"></div>
                                </div>
                              </div>
                            </div>
                          </span>
                        </div>

                      </div>
                    </div>
                  </div><!-- #ga4wp-new-prop-form -->
                </div>
              </div>

            <?php } ?>

            <!-- Granted scopes -->
            <?php
            $ana_edit = false;
            $scopes = get_option('ga4wp_granted_scopes');
            if (isset($scopes) && !empty($scopes)) { ?>
              <div class="ga4wp-row ga4s-wrap">
                <div class="ga4wp-col s12">
                  <div class="ga4s-section">
                    <div class="ga4s-section-head">
                      <span class="material-icons-round">shield</span>
                      <div>
                        <div class="ga4s-section-title"><?php _e('Granted Permissions', 'ga-for-wp-text'); ?></div>
                        <div class="ga4s-section-desc"><?php _e('OAuth scopes authorized by your Google account', 'ga-for-wp-text'); ?></div>
                      </div>
                    </div>
                    <div class="ga4s-rows ga4wp-auth-scopes-rows">
                      <?php
                      $i = 0;
                      $scope_c = count($scopes);
                      foreach ($scopes as $scope) {
                        if (stripos($scope, 'analytics') !== false) {
                          $i++;
                          if ($i == $scope_c) $ana = true;
                        }
                        if (stripos($scope, 'analytics.edit') !== false) $ana_edit = true; ?>
                        <div class="ga4s-row">
                          <div class="ga4wp-auth-scope-tag">
                            <span class="material-icons-round">verified</span>
                            <?php echo esc_html($scope); ?>
                          </div>
                        </div>
                      <?php } ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php } ?>

            <!-- Missing scope warning -->
            <?php if (!($ana ?? false)) { ?>
              <div class="ga4wp-auth-scope-warn">
                <span class="material-icons-round">warning_amber</span>
                <div>
                  <strong><?php _e('Both read and edit scopes required', 'ga-for-wp-text'); ?></strong>
                  <p><?php _e('These scopes are required for dashboard, on-site reports, and edit actions. Please re-link your account and grant both permissions.', 'ga-for-wp-text'); ?></p>
                </div>
              </div>
              <div class="ga4wp-auth-google-row">
                <a class="ga4wp-auth-google-btn GA4WP-authenticate">
                  <img width="18" alt="Google" src="<?php echo esc_url(GA4WP_URL . 'assests/images/google-icon.png'); ?>">
                  <?php _e('Re-Link with Google Account', 'ga-for-wp-text'); ?>
                </a>
              </div>
            <?php } ?>

          </div><!-- .auto-connect -->
        <?php } ?>

      </div><!-- #auto -->

      <!-- ── Agreement + Submit ── -->
      <?php $show_footer = ($manual_tracking || $has_token) && !$is_reauth; ?>
      <div class="ga4wp-auth-footer" id="ga4wp-auth-footer" <?php if (!$show_footer) echo ' style="display:none;"'; ?>>
        <div class="ga4wp-auth-agreement-row">
          <label class="ga4wp-auth-agreement">
            <input type="checkbox"
              id="ga4wp_auth_settings[agreement]"
              name="ga4wp_auth_settings[agreement]"
              value="yes"
              <?php checked(isset($ga4wp_auth_settings['agreement']) && $ga4wp_auth_settings['agreement']); ?> />
            <span>
              <?php _e('I agree with the ', 'ga-for-wp-text'); ?>
              <a href="https://trueana.com/privacy-policy/" target="_blank"><?php _e('privacy policy', 'ga-for-wp-text'); ?></a>
              <?php _e('and', 'ga-for-wp-text'); ?>
              <a href="https://trueana.com/terms-of-service/" target="_blank"><?php _e('terms of service', 'ga-for-wp-text'); ?></a>
              <?php _e('of TrueAna.', 'ga-for-wp-text'); ?>
            </span>
          </label>
        </div>
        <button class="ga4wp-auth-submit-btn" type="submit" name="ga4wp_auth_submit">
          <span class="material-icons-round">save</span>
          <?php _e('Save Settings', 'ga-for-wp-text'); ?>
        </button>
        <p class="ga4wp-auth-footer-sub">
          <span class="material-icons-round" style="font-size:12px;vertical-align:-2px;color:var(--ga-emerald);">lock</span>
          <?php _e('Your data is secured by Google OAuth 2.0', 'ga-for-wp-text'); ?>
        </p>
      </div>
    </div><!-- /.ga4wp-auth-center -->
    <script>
      (function($) {
        var hasToken = <?php echo $has_token ? 'true' : 'false'; ?>;
        $(document).on('change', '.check_manual', function() {
          if ($(this).is(':checked') || hasToken) {
            $('#ga4wp-auth-footer').show();
          } else {
            $('#ga4wp-auth-footer').hide();
          }
        });
      })(jQuery);
    </script>
    <script>
      (function($) {
        var createPropNonce = '<?php echo esc_js(wp_create_nonce('ga4wp_create_property_nonce')); ?>';

        /* Toggle create form */
        $(document).on('click', '#ga4wp-new-prop-toggle', function(e) {
          e.preventDefault();
          var $form = $('#ga4wp-new-prop-form');
          if ($form.is(':visible')) {
            $form.slideUp(200);
            $(this).find('.material-icons-round').text('add_circle_outline');
          } else {
            $form.slideDown(200, function() {
              M.FormSelect.init($('#ga4wp-new-prop-account')[0]);
              M.FormSelect.init($('#ga4wp-new-prop-timezone')[0]);
              M.FormSelect.init($('#ga4wp-new-prop-currency')[0]);
              M.updateTextFields();
            });
            $(this).find('.material-icons-round').text('remove_circle_outline');
            /* lazy-load accounts if not yet populated */
            loadAccountsIfNeeded();
          }
        });

        /* Init selects when no-properties form is shown on page load */
        <?php if (empty($ga4wp_has_existing_props) && !empty($has_token) && !$has_property) { ?>
          $(document).ready(function() {
            M.FormSelect.init($('#ga4wp-new-prop-account')[0]);
            M.FormSelect.init($('#ga4wp-new-prop-timezone')[0]);
            M.FormSelect.init($('#ga4wp-new-prop-currency')[0]);
            M.updateTextFields();
            loadAccountsIfNeeded();
          });
        <?php } ?>

        /**
         * Lazily populate the "Create New Property" account dropdown.
         *
         * No-ops unless the select still carries the `data-needs-load` flag
         * (set when no accounts were embedded server-side), so the accounts
         * list is only fetched from the `ga4wp_get_ga4_accounts` AJAX action
         * once. Repopulates the dropdown options on success (showing the
         * "no accounts found" notice/link when the account list is empty),
         * or an error option on failure, then re-inits the Materialize
         * select and clears the load flag either way.
         */
        function loadAccountsIfNeeded() {
          var $sel = $('#ga4wp-new-prop-account');
          if (!$sel.data('needs-load')) return;
          $.post(ajaxurl, {
            action: 'ga4wp_get_ga4_accounts',
            nonce: createPropNonce
          }, function(res) {
            if (res && res.success) {
              $sel.empty();
              var keys = res.data ? Object.keys(res.data) : [];
              if (keys.length > 0) {
                $.each(res.data, function(val, label) {
                  $sel.append($('<option>').val(val).text(label));
                });
                $('#ga4wp-create-account-wrap').slideUp(150);
              } else {
                $sel.append($('<option>').val('').text('<?php echo esc_js(__('— no accounts found —', 'ga-for-wp-text')); ?>'));
                $('#ga4wp-create-account-wrap').slideDown(200);
              }
            } else {
              $sel.empty().append($('<option>').val('').text('<?php echo esc_js(__('Error loading accounts', 'ga-for-wp-text')); ?>'));
              M.toast({
                html: '<?php echo esc_js(__('Could not load accounts. Please re-link your Google account or try again.', 'ga-for-wp-text')); ?>',
                classes: 'rounded red',
                displayLength: 6000
              });
            }
            $sel.removeData('needs-load').removeAttr('data-needs-load');
            M.FormSelect.init($sel[0]);
          }).fail(function() {
            $sel.empty().append($('<option>').val('').text('<?php echo esc_js(__('Error loading accounts', 'ga-for-wp-text')); ?>'));
            $sel.removeData('needs-load').removeAttr('data-needs-load');
            M.FormSelect.init($sel[0]);
            M.toast({
              html: '<?php echo esc_js(__('Could not load accounts. Please re-link your Google account or try again.', 'ga-for-wp-text')); ?>',
              classes: 'rounded red',
              displayLength: 6000
            });
          });
        }

        /* Init property selector after AJAX content load */
        $(document).ready(function() {
          var $propSel = $('.property-id');
          if ($propSel.length) M.FormSelect.init($propSel[0]);
        });

        /* Refresh cached property list */
        $(document).on('click', '#ga4wp-refresh-props', function(e) {
          e.preventDefault();
          var $link = $(this);
          $link.css('opacity', '.4').css('pointer-events', 'none');
          $.post(ajaxurl, {
            action: 'ga4wp_refresh_properties',
            nonce: createPropNonce
          }, function() {
            window.location.reload();
          }).fail(function() {
            $link.css('opacity', '').css('pointer-events', '');
          });
        });

        /* Reload accounts */
        $(document).on('click', '#ga4wp-reload-accounts', function(e) {
          e.preventDefault();
          var $sel = $('#ga4wp-new-prop-account');
          $sel.data('needs-load', true);
          loadAccountsIfNeeded();
        });

        /* Create property submit */
        $(document).on('click', '#ga4wp-create-prop-submit', function(e) {
          e.preventDefault();
          var account = $('#ga4wp-new-prop-account').val();
          var propName = $.trim($('#ga4wp-new-prop-name').val());
          var url = $.trim($('#ga4wp-new-prop-url').val());
          var timezone = $('#ga4wp-new-prop-timezone').val();
          var currency = $('#ga4wp-new-prop-currency').val();
          var enhanced = $('#ga4wp-new-prop-enhanced').is(':checked') ? '1' : '';

          if (!account) {
            M.toast({
              html: '<?php echo esc_js(__('Please select a Google Analytics account.', 'ga-for-wp-text')); ?>',
              classes: 'rounded red',
              displayLength: 4000
            });
            return;
          }
          if (!propName) {
            M.toast({
              html: '<?php echo esc_js(__('Please enter a property name.', 'ga-for-wp-text')); ?>',
              classes: 'rounded red',
              displayLength: 4000
            });
            return;
          }
          if (!url) {
            M.toast({
              html: '<?php echo esc_js(__('Please enter the website URL.', 'ga-for-wp-text')); ?>',
              classes: 'rounded red',
              displayLength: 4000
            });
            return;
          }

          $('#ga4wp-create-prop-submit').addClass('disabled');
          $('#ga4wp-create-prop-spinner').show();

          $.post(ajaxurl, {
            action: 'ga4wp_create_property',
            nonce: createPropNonce,
            account: account,
            property_name: propName,
            website_url: url,
            timezone: timezone,
            currency: currency,
            enhanced_measurement: enhanced
          }, function(res) {
            $('#ga4wp-create-prop-submit').removeClass('disabled');
            $('#ga4wp-create-prop-spinner').hide();
            if (res.success) {
              var pid = res.data.property_id;
              var mid = res.data.measurement_id;
              var label = res.data.display_name + ' (' + mid + ')';
              /* Add to existing dropdown if present, otherwise create hidden input */
              var $existing = $('select[name="ga4wp_auth_settings[property_id]"]');
              if ($existing.length) {
                $existing.append($('<option>').val(pid).text(label));
                $existing.val(pid);
                M.FormSelect.init($existing[0]);
              } else {
                /* No dropdown (was no-properties state): inject hidden input so form can save */
                $('form').append($('<input>').attr({
                  type: 'hidden',
                  name: 'ga4wp_auth_settings[property_id]',
                  value: pid
                }));
                /* Also show a connected pill */
                $('#ga4wp-new-prop-form').before(
                  '<div class="ga4wp-auth-connected-pill" style="margin-bottom:8px;">' +
                  '<span class="material-icons-round">check_circle</span>' +
                  '<span>' + mid + '</span></div>'
                );
              }
              $('#ga4wp-new-prop-form').slideUp(200);
              $('#ga4wp-auth-footer').show();
              M.toast({
                html: '<?php echo esc_js(__('Property created! Click Save Settings to link it.', 'ga-for-wp-text')); ?>',
                classes: 'rounded teal',
                displayLength: 6000
              });
            } else {
              M.toast({
                html: (res.data || '<?php echo esc_js(__('Error creating property. Please try again.', 'ga-for-wp-text')); ?>'),
                classes: 'rounded red',
                displayLength: 7000
              });
            }
          }).fail(function(jqXHR, textStatus, errorThrown) {
            $('#ga4wp-create-prop-submit').removeClass('disabled');
            $('#ga4wp-create-prop-spinner').hide();
            console.error('TrueAna create_property AJAX fail — status:', jqXHR.status, textStatus, errorThrown);
            console.log('TrueAna response body:', jqXHR.responseText ? jqXHR.responseText.substring(0, 500) : '(empty)');
            M.toast({
              html: '<?php echo esc_js(__('Network error. Please try again.', 'ga-for-wp-text')); ?>',
              classes: 'rounded red',
              displayLength: 5000
            });
          });
        });

      })(jQuery);
    </script>

    <?php wp_nonce_field('ga4wp_auth_submit', 'ga4wp_nonce_header'); ?>
  </form>
</div>
<?php

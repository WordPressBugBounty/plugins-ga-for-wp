<?php
/* controlling view of dashboard (free plan) */
if (!defined('ABSPATH')) {
  die;
}

$errors = '';

/* Respects Settings > Dashboard Report Tabs — a disabled tab must not appear
 * here, and the drag-and-drop order saved there must be honored too. */
$ga4wp_dashboard_settings = get_option('ga4wp_dashboard_settings') ?: GA4WP_Settings::get_instance()->init_ga4wp_dashboard_defaults();
$free_tab_labels = array(
  'audience'    => __('Audience', 'ga-for-wp-text'),
  'acquisition' => __('Acquisition', 'ga-for-wp-text'),
  'behavior'    => __('Behavior', 'ga-for-wp-text'),
);
$saved_tab_order = get_option('ga4wp_tab_order', []);
$free_tab_order  = [];
foreach ((array) $saved_tab_order as $_k) {
  if (isset($free_tab_labels[$_k])) $free_tab_order[] = $_k;
}
foreach (array_keys($free_tab_labels) as $_k) {
  if (!in_array($_k, $free_tab_order, true)) $free_tab_order[] = $_k;
}

if (!get_option('ga4wp_dash_settings')) {
  $ga4wp_dash_settings = $defaults;
  update_option('ga4wp_dash_settings', $defaults);
} else {
  $ga4wp_dash_settings = GA4WP_Auth::get_instance()->get_current_dash_settings();
}

if (isset($_POST['ga4wp_dash_submit']) && wp_verify_nonce($_POST['ga4wp_nonce_header'], 'ga4wp_dash_submit') && current_user_can('manage_options')) {
  if (!empty($_POST['ga4wp_dash_settings'])) {
    $ga4wp_dash_settings_save = GA4WP_Settings::get_instance()->parse_ga4wp_dash_settings($_POST['ga4wp_dash_settings']);
    if ($ga4wp_dash_settings_save) {
      if ($ga4wp_dash_settings_save['report_frame'] === 'Yesterday') {
        $ga4wp_dash_settings_save['report_to']   = date('Y-m-d', strtotime('-1 day'));
        $ga4wp_dash_settings_save['report_from'] = date('Y-m-d', strtotime('-1 day'));
      } elseif ($ga4wp_dash_settings_save['report_frame'] === 'Last 7 days') {
        $ga4wp_dash_settings_save['report_to']   = date('Y-m-d', strtotime('-1 day'));
        $ga4wp_dash_settings_save['report_from'] = date('Y-m-d', strtotime('-8 day'));
      } elseif ($ga4wp_dash_settings_save['report_frame'] === 'Today') {
        $ga4wp_dash_settings_save['report_to']   = date('Y-m-d');
        $ga4wp_dash_settings_save['report_from'] = date('Y-m-d');
      } elseif ($ga4wp_dash_settings_save['report_frame'] === 'Current Year') {
        $ga4wp_dash_settings_save['report_to']   = date('Y-m-d');
        $ga4wp_dash_settings_save['report_from'] = date('Y') . '-01-01';
      } elseif ($ga4wp_dash_settings_save['report_frame'] === 'Custom Range') {
        if (empty($ga4wp_dash_settings_save['report_to']) || empty($ga4wp_dash_settings_save['report_from'])) {
          $ga4wp_dash_settings_save['report_to']   = date('Y-m-d', strtotime('-1 day'));
          $ga4wp_dash_settings_save['report_from'] = date('Y-m-d', strtotime('-30 day'));
        }
      } else {
        $ga4wp_dash_settings_save['report_to']   = date('Y-m-d', strtotime('-1 day'));
        $ga4wp_dash_settings_save['report_from'] = date('Y-m-d', strtotime('-31 day'));
      }
      update_option('ga4wp_dash_settings', $ga4wp_dash_settings_save);
      echo '<script>
          jQuery(document).ready(function(){
             M.toast({html:"' . __('Setting Saved!', 'ga-for-wp-text') . '", classes:"rounded teal", displayLength:4000});
          });
      </script>';
      $ga4wp_dash_settings = $ga4wp_dash_settings_save;
    } else {
      $errors .= __('Error while saving data!', 'ga-for-wp-text') . '<br>';
      $ga4wp_dash_settings = $ga4wp_dash_settings_save;
    }
  }
}

if (strlen($errors) > 0) {
  echo '<script>
            jQuery(document).ready(function(){
               M.toast({html:"' . esc_js(__('Please correct following Errors:', 'ga-for-wp-text')) . '", classes:"rounded red", displayLength:6000});
               M.toast({html:"' . esc_js(strip_tags($errors)) . '", classes:"rounded red", displayLength:8000});
            });
        </script>';
}
?>

<div class="ga4wp-col s12 ga4wp-options">
  <?php
  $frame   = $ga4wp_dash_settings['report_frame'] ?? 'Last 7 days';
  $periods = [
    'Today'        => 'Today',
    'Yesterday'    => 'Yesterday',
    'Last 7 days'  => '7 Days',
    'Last 30 days' => '30 Days',
    'Custom Range' => 'Custom',
  ];

  $report_from = !empty($ga4wp_dash_settings['report_from']) ? $ga4wp_dash_settings['report_from'] : date('Y-m-d', strtotime('-7 days'));
  $report_to   = !empty($ga4wp_dash_settings['report_to'])   ? $ga4wp_dash_settings['report_to']   : date('Y-m-d', strtotime('-1 day'));
  $from_ts     = strtotime($report_from) ?: strtotime('-7 days');
  $to_ts       = strtotime($report_to)   ?: strtotime('-1 day');
  $days_range  = max(1, (int)(($to_ts - $from_ts) / 86400) + 1);
  $fmt_from    = (date('Y', $from_ts) !== date('Y', $to_ts)) ? date('M j, Y', $from_ts) : date('M j', $from_ts);
  $fmt_to      = date('M j, Y', $to_ts);
  $current_range_display = $fmt_from . ' – ' . $fmt_to;
  $comp_to_ts            = $from_ts - 86400;
  $comp_from_ts          = $comp_to_ts - ($days_range - 1) * 86400;
  $comp_fmt_from         = (date('Y', $comp_from_ts) !== date('Y', $comp_to_ts)) ? date('M j, Y', $comp_from_ts) : date('M j', $comp_from_ts);
  $compare_range_display = $comp_fmt_from . ' – ' . date('M j, Y', $comp_to_ts);
  ?>

  <!-- ── Topbar: title + filter ── -->
  <div class="ga4wp-dash-topbar">
    <div class="ga4wp-dash-left">
      <h2 class="ga4wp-dash-title" id="ga4wp-dash-title"><?php _e('Analytics Dashboard', 'ga-for-wp-text'); ?></h2>
      <div class="ga4wp-dash-range">
        <span class="material-icons-round">calendar_today</span>
        <span><?php echo esc_html($current_range_display); ?></span>
        <span class="ga4wp-range-vs">vs</span>
        <span><?php echo esc_html($compare_range_display); ?></span>
      </div>
    </div>
    <div class="ga4wp-dash-right">
      <form action="" method="POST" id="ga4wp-filter-form">
        <div class="ga4wp-period-group">
          <?php foreach ($periods as $value => $label) : ?>
            <button type="button"
              class="ga4wp-period-opt<?php echo ($frame === $value) ? ' active' : ''; ?>"
              data-period="<?php echo esc_attr($value); ?>">
              <?php echo esc_html($label); ?>
            </button>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="ga4wp_dash_settings[report_frame]" id="ga4wp-period-input"
          value="<?php echo esc_attr($frame); ?>">
        <div class="ga4wp-date-chip<?php echo ($frame === 'Custom Range') ? ' visible' : ''; ?>" id="ga4wp-date-chip">
          <span class="ga4wp-date-label"><?php _e('From', 'ga-for-wp-text'); ?></span>
          <input type="text" name="ga4wp_dash_settings[report_from]" class="datepicker ga4wp-date-input" id="from"
            value="<?php echo esc_attr($report_from); ?>">
          <span class="ga4wp-date-sep">→</span>
          <span class="ga4wp-date-label"><?php _e('To', 'ga-for-wp-text'); ?></span>
          <input type="text" name="ga4wp_dash_settings[report_to]" class="datepicker ga4wp-date-input" id="to"
            value="<?php echo esc_attr($report_to); ?>">
        </div>
        <button class="ga4wp-filter-apply" type="submit" name="ga4wp_dash_submit" value="submit">
          <span class="material-icons-round">refresh</span>
          <?php _e('Apply', 'ga-for-wp-text'); ?>
        </button>
        <input type="hidden" name="ga4wp_dash_submit" value="submit">
        <?php wp_nonce_field('ga4wp_dash_submit', 'ga4wp_nonce_header'); ?>
      </form>
    </div>
  </div>

  <!-- ── Range info strip ── -->
  <div class="ga4wp-range-info">
    <span class="material-icons-round">info</span>
    <?php _e('Showing', 'ga-for-wp-text'); ?>
    <strong><?php echo esc_html($current_range_display); ?></strong>
    <?php _e('compared to', 'ga-for-wp-text'); ?>
    <strong><?php echo esc_html($compare_range_display); ?></strong>
  </div>
  <?php if ($frame === 'Today') : ?>
    <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;margin:8px 0 0;background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;">
      <span class="material-icons-round" style="color:#EA580C;font-size:18px;flex-shrink:0;">schedule</span>
      <span style="font-size:12.5px;color:#9A3412;"><?php _e("Today's data is still being processed by Google Analytics and may be incomplete or change as more data comes in over the next few hours. For fully accurate reporting, use Yesterday or a longer date range.", 'ga-for-wp-text'); ?></span>
    </div>
  <?php endif; ?>
  <div class="divider" style="margin:4px 0 12px"></div>

  <!-- ── Tabs ── -->
  <div class="ga4wp-row">
    <ul class="tabs">
      <?php foreach ($free_tab_order as $_ftk) :
        if (empty($ga4wp_dashboard_settings[$_ftk])) continue;
      ?>
      <li class="tab ga4wp-col s4 m2">
        <a id="<?php echo esc_attr($_ftk); ?>-tab" href="#<?php echo esc_attr($_ftk); ?>">
          <span><?php echo esc_html($free_tab_labels[$_ftk]); ?></span>
        </a>
      </li>
      <?php endforeach; ?>
      <li class="tab ga4wp-col s4 m2">
        <a id="realtime-pro-tab" href="#realtime-pro">
          <span><?php _e('Real-Time', 'ga-for-wp-text'); ?></span>
          <span class="material-icons ga4wp_pro_icon info">workspace_premium</span>
        </a>
      </li>
      <li class="tab ga4wp-col s4 m2">
        <a id="form_tracking-pro-tab" href="#form_tracking-pro">
          <span><?php _e('Form Tracking', 'ga-for-wp-text'); ?></span>
          <span class="material-icons ga4wp_pro_icon info">workspace_premium</span>
        </a>
      </li>
      <li class="tab ga4wp-col s4 m2">
        <a id="video_tracking-pro-tab" href="#video_tracking-pro">
          <span><?php _e('Video Tracking', 'ga-for-wp-text'); ?></span>
          <span class="material-icons ga4wp_pro_icon info">workspace_premium</span>
        </a>
      </li>
      <?php if (class_exists('WooCommerce')) : ?>
        <li class="tab ga4wp-col s4 m2">
          <a id="conversion-pro-tab" href="#conversion-pro">
            <span><?php _e('Conversion', 'ga-for-wp-text'); ?></span>
            <span class="material-icons ga4wp_pro_icon info">workspace_premium</span>
          </a>
        </li>
        <li class="tab ga4wp-col s4 m2">
          <a id="journey-pro-tab" href="#journey-pro">
            <span><?php _e('Purchase Journey', 'ga-for-wp-text'); ?></span>
            <span class="material-icons ga4wp_pro_icon info">workspace_premium</span>
          </a>
        </li>
        <li class="tab ga4wp-col s4 m2">
          <a id="performance-pro-tab" href="#performance-pro">
            <span><?php _e('Product Performance', 'ga-for-wp-text'); ?></span>
            <span class="material-icons ga4wp_pro_icon info">workspace_premium</span>
          </a>
        </li>
        <li class="tab ga4wp-col s4 m2">
          <a id="woo_subscriptions-pro-tab" href="#woo_subscriptions-pro">
            <span><?php _e('Subscriptions', 'ga-for-wp-text'); ?></span>
            <span class="material-icons ga4wp_pro_icon info">workspace_premium</span>
          </a>
        </li>
      <?php endif; ?>
      <li class="tab ga4wp-col s4 m2">
        <a id="content-pro-tab" href="#content-pro">
          <span><?php _e('Content', 'ga-for-wp-text'); ?></span>
          <span class="material-icons ga4wp_pro_icon info">workspace_premium</span>
        </a>
      </li>
      <li class="tab ga4wp-col s4 m2">
        <a id="search_console-pro-tab" href="#search_console-pro">
          <span><?php _e('Search Console', 'ga-for-wp-text'); ?></span>
          <span class="material-icons ga4wp_pro_icon info">workspace_premium</span>
        </a>
      </li>
      <li class="tab ga4wp-col s4 m2">
        <a id="utm_campaign-pro-tab" href="#utm_campaign-pro">
          <span><?php _e('Campaign Performance', 'ga-for-wp-text'); ?></span>
          <span class="material-icons ga4wp_pro_icon info">workspace_premium</span>
        </a>
      </li>
      <li class="tab ga4wp-col s4 m2">
        <a id="click_tracking-pro-tab" href="#click_tracking-pro">
          <span><?php _e('Click Tracking', 'ga-for-wp-text'); ?></span>
          <span class="material-icons ga4wp_pro_icon info">workspace_premium</span>
        </a>
      </li>
      <li class="tab ga4wp-col s4 m2">
        <a id="googleAds-pro-tab" href="#googleAds-pro">
          <span><?php _e('Google Ads', 'ga-for-wp-text'); ?></span>
          <span class="material-icons ga4wp_pro_icon info">workspace_premium</span>
        </a>
      </li>
      <li class="tab ga4wp-col s4 m2">
        <a id="googleAdsense-pro-tab" href="#googleAdsense-pro">
          <span><?php _e('Google Adsense', 'ga-for-wp-text'); ?></span>
          <span class="material-icons ga4wp_pro_icon info">workspace_premium</span>
        </a>
      </li>
      <li class="tab ga4wp-col s4 m2">
        <a href="#upgrade-pro">
          <?php _e('Upgrade', 'ga-for-wp-text'); ?>
          <span class="material-icons ga4wp_pro_icon info">workspace_premium</span>
        </a>
      </li>
    </ul>
  </div>

  <!-- ── Dashboard tab (live data) ── -->
  <?php foreach ($free_tab_order as $_ftk) :
    if (empty($ga4wp_dashboard_settings[$_ftk])) continue;
  ?>
  <div id="<?php echo esc_attr($_ftk); ?>" class="ga4wp-col s12"></div>
  <?php endforeach; ?>

  <!-- ── Pro upsell tabs ── -->
  <?php
  $pro_tabs = GA4WP_Settings::get_instance()->ga4wp_pro_tabs;

  foreach ($pro_tabs as $tab_id => $tab) :
    if (!empty($tab['woo']) && !class_exists('WooCommerce')) continue;
  ?>
    <div id="<?php echo esc_attr($tab_id); ?>" class="ga4wp-col s12">
      <div class="ga4wp-dash-pro-header">
        <span class="material-icons-round">workspace_premium</span>
        <div>
          <strong><?php echo esc_html__($tab['title'], 'ga-for-wp-text'); ?></strong>
          <span><?php _e('Available in the Pro version', 'ga-for-wp-text'); ?></span>
        </div>
      </div>
      <div class="ga4wp-row ga4wp-upsell-tiles">
        <?php foreach ($tab['items'] as $item) : ?>
          <div class="ga4wp-info-box valign-wrapper">
            <div class="ga4wp-col s3 l2">
              <?php if (strpos($item[0], '.') !== false) : ?>
                <img class="ga4wp-info-img" src="<?php echo esc_url(GA4WP_URL . 'assests/images/' . $item[0]); ?>">
              <?php else : ?>
                <span class="material-icons-round ga4wp-info-icon"><?php echo esc_html($item[0]); ?></span>
              <?php endif; ?>
            </div>
            <div class="ga4wp-col s9 l10">
              <p class="ga4wp-info-title"><?php echo esc_html($item[1]); ?></p>
              <p class="ga4wp-info-description"><?php echo esc_html($item[2]); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="center-align" style="padding:12px 0 20px;">
        <a class="btn upgrade-btn waves-effect waves-light" href="<?php echo esc_url(gfw_fs()->get_upgrade_url()); ?>" target="_blank">
          <span class="material-icons-round left">workspace_premium</span>
          <?php _e('Upgrade to Pro', 'ga-for-wp-text'); ?>
        </a>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- ── Upgrade tab ── -->
  <div id="upgrade-pro" class="ga4wp-col s12">
    <div class="ga4wp-dash-pro-header">
      <span class="material-icons-round">workspace_premium</span>
      <div>
        <strong><?php _e('Unlock All Reports', 'ga-for-wp-text'); ?></strong>
        <span><?php _e('Upgrade to TrueAna Pro for audience, acquisition, behavior, conversion, and ads reports.', 'ga-for-wp-text'); ?></span>
      </div>
    </div>
    <div class="ga4wp-row ga4wp-upsell-tiles">
      <?php
      $features = GA4WP_Settings::get_instance()->ga4wp_features_list;
      foreach ($features as $image => $feature) :
        $pro = $feature[2] ? ' <sup>pro</sup>' : '';
      ?>
        <div class="ga4wp-info-box valign-wrapper">
          <div class="ga4wp-col s3 l2">
            <img class="ga4wp-info-img" src="<?php echo esc_url(GA4WP_URL . 'assests/images/truana-mark.svg'); ?>">
          </div>
          <div class="ga4wp-col s9 l10">
            <p class="ga4wp-info-title"><?php echo esc_html($feature[0]); ?><?php echo $pro; ?></p>
            <p class="ga4wp-info-description"><?php echo esc_html($feature[1]); ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="center-align" style="padding:16px 0 20px;">
      <p style="color:var(--ga-text2);font-size:13px;margin-bottom:14px;">
        <?php _e('Upgrade to unlock all reports and analytics for your website.', 'ga-for-wp-text'); ?>
      </p>
      <a class="btn upgrade-btn waves-effect waves-light" href="<?php echo esc_url(gfw_fs()->get_upgrade_url()); ?>" target="_blank">
        <span class="material-icons-round left">workspace_premium</span>
        <?php _e('Upgrade Now', 'ga-for-wp-text'); ?>
      </a>
    </div>
  </div>

  <div class="clearfix"></div>
</div>
<?php

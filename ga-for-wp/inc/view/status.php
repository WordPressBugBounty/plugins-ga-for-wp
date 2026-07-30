<?php
if (!defined('ABSPATH')) {
  die;
}

/* Variables passed from view_options():
 *   $property_name    — "properties/XXXXXXXXX"
 *   $property_details — array from GA4 Admin API or null
 *   $data_retention   — array from GA4 Admin API or null
 *   $custom_dims      — array of custom dimension objects
 */

$auth_settings = get_option('ga4wp_auth_settings');
$tracking_id   = '';
if (!empty($auth_settings['property_id'])) {
  $pieces      = explode('|', $auth_settings['property_id']);
  $tracking_id = $pieces[1] ?? '';
} elseif (!empty($auth_settings['tracking_id'])) {
  $tracking_id = $auth_settings['tracking_id'];
}

/* Check whether analytics.edit scope has been granted */
$has_edit_scope = false;
$granted_scopes = get_option('ga4wp_granted_scopes', []);
if (!empty($granted_scopes)) {
  foreach ($granted_scopes as $scope) {
    if (stripos($scope, 'analytics.edit') !== false) {
      $has_edit_scope = true;
      break;
    }
  }
}

/* Helpers */
$retention_labels = array(
  'TWO_MONTHS'          => __('2 Months', 'ga-for-wp-text'),
  'FOURTEEN_MONTHS'     => __('14 Months', 'ga-for-wp-text'),
  'TWENTY_SIX_MONTHS'   => __('26 Months', 'ga-for-wp-text'),
  'THIRTY_EIGHT_MONTHS' => __('38 Months', 'ga-for-wp-text'),
  'FIFTY_MONTHS'        => __('50 Months', 'ga-for-wp-text'),
);

$service_labels = array(
  'GOOGLE_ANALYTICS_STANDARD' => __('Standard', 'ga-for-wp-text'),
  'GOOGLE_ANALYTICS_360'      => __('Analytics 360', 'ga-for-wp-text'),
);

$industry_labels = array(
  'AUTOMOTIVE'                  => 'Automotive',
  'BUSINESS_AND_INDUSTRIAL_MARKETS' => 'Business & Industrial',
  'FINANCE'                     => 'Finance',
  'HEALTHCARE'                  => 'Healthcare',
  'TECHNOLOGY'                  => 'Technology',
  'TRAVEL'                      => 'Travel',
  'OTHER'                       => 'Other',
  'ARTS_AND_ENTERTAINMENT'      => 'Arts & Entertainment',
  'BEAUTY_AND_FITNESS'          => 'Beauty & Fitness',
  'BOOKS_AND_LITERATURE'        => 'Books & Literature',
  'FOOD_AND_DRINK'              => 'Food & Drink',
  'GAMES'                       => 'Games',
  'HOBBIES_AND_LEISURE'         => 'Hobbies & Leisure',
  'HOME_AND_GARDEN'             => 'Home & Garden',
  'INTERNET_AND_TELECOM'        => 'Internet & Telecom',
  'LAW_AND_GOVERNMENT'          => 'Law & Government',
  'NEWS'                        => 'News',
  'ONLINE_COMMUNITIES'          => 'Online Communities',
  'PEOPLE_AND_SOCIETY'          => 'People & Society',
  'PETS_AND_ANIMALS'            => 'Pets & Animals',
  'REAL_ESTATE'                 => 'Real Estate',
  'REFERENCE'                   => 'Reference',
  'SCIENCE'                     => 'Science',
  'SHOPPING'                    => 'Shopping',
  'SPORTS'                      => 'Sports',
);

$not_available = '<span class="ga4wp-status-na">' . __('N/A', 'ga-for-wp-text') . '</span>';
?>

<div class="ga4wp-col s12 ga4wp-options ga4wp-status-page">

  <?php if (empty($property_name)) : ?>
    <div class="ga4wp-status-empty">
      <span class="material-icons-round">link_off</span>
      <p><?php _e('No GA4 property connected. Please complete authentication first.', 'ga-for-wp-text'); ?></p>
    </div>

  <?php else : ?>

    <!-- ── Refresh notice ── -->
    <div class="ga4wp-status-refresh-bar">
      <span class="material-icons-round">info</span>
      <span><?php _e('Property data is cached for 24 hours.', 'ga-for-wp-text'); ?></span>
      <?php if (!empty($fetched_at)) : ?>
        <span class="ga4wp-status-fetched-at">
          <span class="material-icons-round">schedule</span>
          <?php
          printf(
            /* translators: %s: formatted date/time */
            __('Last fetched: %s', 'ga-for-wp-text'),
            '<strong>' . esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $fetched_at, wp_timezone())) . '</strong>'
          );
          ?>
        </span>
      <?php endif; ?>
      <a href="<?php echo esc_url(add_query_arg('ga4wp_flush_status', '1')); ?>" class="ga4wp-status-refresh-link">
        <span class="material-icons-round">refresh</span><?php _e('Refresh now', 'ga-for-wp-text'); ?>
      </a>
    </div>
    <?php
    if (!empty($_GET['ga4wp_flush_status'])) {
      delete_transient('ga4wp_status_' . md5($property_name));
      echo '<script>window.location.href = window.location.href.replace(/[?&]ga4wp_flush_status=1/, "");</script>';
    }
    ?>

    <div class="ga4wp-status-grid">

      <!-- ── 1. Property Overview ── -->
      <div class="ga4wp-row ga4s-wrap">
        <div class="ga4wp-col s12">
          <div class="ga4s-section">
            <div class="ga4s-section-head">
              <span class="material-icons-round">analytics</span>
              <div>
                <div class="ga4s-section-title"><?php _e('Property Overview', 'ga-for-wp-text'); ?></div>
                <div class="ga4s-section-desc"><?php _e('Core details about your connected GA4 property', 'ga-for-wp-text'); ?></div>
              </div>
            </div>
            <div class="ga4s-rows">

              <div class="ga4s-row">
                <div class="ga4wp-status-row-label">
                  <span class="material-icons-round">badge</span>
                  <?php _e('Display Name', 'ga-for-wp-text'); ?>
                </div>
                <div class="ga4wp-status-row-value">
                  <?php echo !empty($property_details['displayName']) ? esc_html($property_details['displayName']) : $not_available; ?>
                </div>
              </div>

              <div class="ga4s-row">
                <div class="ga4wp-status-row-label">
                  <span class="material-icons-round">fingerprint</span>
                  <?php _e('Measurement ID', 'ga-for-wp-text'); ?>
                </div>
                <div class="ga4wp-status-row-value">
                  <?php if ($tracking_id) : ?>
                    <span class="ga4wp-status-id-chip"><?php echo esc_html($tracking_id); ?></span>
                  <?php else : echo $not_available; endif; ?>
                </div>
              </div>

              <div class="ga4s-row">
                <div class="ga4wp-status-row-label">
                  <span class="material-icons-round">account_tree</span>
                  <?php _e('Property Resource', 'ga-for-wp-text'); ?>
                </div>
                <div class="ga4wp-status-row-value">
                  <?php echo !empty($property_name) ? '<code class="ga4wp-status-code">' . esc_html($property_name) . '</code>' : $not_available; ?>
                </div>
              </div>

              <div class="ga4s-row">
                <div class="ga4wp-status-row-label">
                  <span class="material-icons-round">workspace_premium</span>
                  <?php _e('Service Level', 'ga-for-wp-text'); ?>
                </div>
                <div class="ga4wp-status-row-value">
                  <?php
                  $svc = $property_details['serviceLevel'] ?? '';
                  echo $svc ? '<span class="ga4wp-status-badge ga4wp-status-badge-blue">' . esc_html($service_labels[$svc] ?? ucwords(strtolower(str_replace('_', ' ', $svc)))) . '</span>' : $not_available;
                  ?>
                </div>
              </div>

              <div class="ga4s-row">
                <div class="ga4wp-status-row-label">
                  <span class="material-icons-round">category</span>
                  <?php _e('Industry Category', 'ga-for-wp-text'); ?>
                </div>
                <div class="ga4wp-status-row-value">
                  <?php
                  $ind = $property_details['industryCategory'] ?? '';
                  echo $ind ? esc_html($industry_labels[$ind] ?? ucwords(strtolower(str_replace('_', ' ', $ind)))) : $not_available;
                  ?>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- ── 2. Regional Settings ── -->
      <?php
      /* ── WordPress timezone ── */
      $wp_tz_string = get_option('timezone_string');
      $wp_gmt_offset = get_option('gmt_offset');
      if (!empty($wp_tz_string)) {
        $wp_timezone = $wp_tz_string;
      } elseif ($wp_gmt_offset !== '') {
        $offset       = (float) $wp_gmt_offset;
        $sign         = $offset >= 0 ? '+' : '-';
        $abs          = abs($offset);
        $hours        = (int) $abs;
        $mins         = ($abs - $hours) * 60;
        $wp_timezone  = sprintf('UTC%s%02d:%02d', $sign, $hours, $mins);
      } else {
        $wp_timezone = 'UTC';
      }

      /* ── WooCommerce currency ── */
      $woo_active   = class_exists('WooCommerce') && function_exists('get_woocommerce_currency');
      $woo_currency = $woo_active ? call_user_func('get_woocommerce_currency') : null;

      /* ── GA4 values ── */
      $ga4_tz  = $property_details['timeZone']    ?? '';
      $ga4_cur = $property_details['currencyCode'] ?? '';

      /* ── Timezone offset resolver (handles both IANA names and UTC±HH:MM strings) ── */
      $ga4wp_tz_offset = function ($tz_str) {
        if (empty($tz_str)) return null;
        if (preg_match('/^UTC([+-])(\d{1,2}):(\d{2})$/', $tz_str, $m)) {
          return (($m[1] === '+') ? 1 : -1) * ((int) $m[2] * 60 + (int) $m[3]);
        }
        if ($tz_str === 'UTC' || $tz_str === 'UTC+00:00') return 0;
        try {
          $dtz = new DateTimeZone($tz_str);
          return (int) ($dtz->getOffset(new DateTime('now', $dtz)) / 60);
        } catch (Exception $e) {
          return null;
        }
      };

      /* ── Match checks ── */
      $tz_match  = !empty($ga4_tz) && $ga4wp_tz_offset($ga4_tz) !== null
                   && $ga4wp_tz_offset($ga4_tz) === $ga4wp_tz_offset($wp_timezone);
      $cur_match = !empty($ga4_cur) && $woo_active && $ga4_cur === $woo_currency;
      ?>
      <div class="ga4wp-row ga4s-wrap">
        <div class="ga4wp-col s12">
          <div class="ga4s-section">
            <div class="ga4s-section-head">
              <span class="material-icons-round">public</span>
              <div>
                <div class="ga4s-section-title"><?php _e('Regional Settings', 'ga-for-wp-text'); ?></div>
                <div class="ga4s-section-desc"><?php _e('Timezone and currency compared across GA4, WordPress, and WooCommerce', 'ga-for-wp-text'); ?></div>
              </div>
            </div>

            <!-- Timezone comparison -->
            <div class="ga4wp-status-compare-block">
              <div class="ga4wp-status-compare-header">
                <span class="material-icons-round">schedule</span>
                <span><?php _e('Timezone', 'ga-for-wp-text'); ?></span>
                <?php if (!empty($ga4_tz)) : ?>
                  <?php if ($tz_match) : ?>
                    <span class="ga4wp-status-match-chip ga4wp-status-match-ok">
                      <span class="material-icons-round">check_circle</span><?php _e('Match', 'ga-for-wp-text'); ?>
                    </span>
                  <?php else : ?>
                    <span class="ga4wp-status-match-chip ga4wp-status-match-warn">
                      <span class="material-icons-round">warning_amber</span><?php _e('Mismatch', 'ga-for-wp-text'); ?>
                    </span>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
              <div class="ga4wp-status-compare-cols">
                <div class="ga4wp-status-compare-col">
                  <div class="ga4wp-status-compare-source">
                    <img src="<?php echo esc_url(GA4WP_URL . 'assests/images/truana-mark.svg'); ?>" width="14" alt="GA4"> GA4
                  </div>
                  <?php if (!empty($ga4_tz)) : ?>
                    <span class="ga4wp-status-badge ga4wp-status-badge-teal"><?php echo esc_html($ga4_tz); ?></span>
                  <?php else : echo $not_available; endif; ?>
                </div>
                <div class="ga4wp-status-compare-arrow material-icons-round">sync_alt</div>
                <div class="ga4wp-status-compare-col">
                  <div class="ga4wp-status-compare-source">
                    <span class="material-icons-round" style="font-size:13px;">wordpress</span> WordPress
                  </div>
                  <span class="ga4wp-status-badge <?php echo $tz_match ? 'ga4wp-status-badge-teal' : 'ga4wp-status-badge-gray'; ?>">
                    <?php echo esc_html($wp_timezone); ?>
                  </span>
                </div>
              </div>
              <?php if (!empty($ga4_tz) && !$tz_match) : ?>
                <div class="ga4wp-status-compare-hint">
                  <span class="material-icons-round">info</span>
                  <?php _e('Timezone mismatch may cause date discrepancies in your GA4 reports. Update your WordPress timezone under', 'ga-for-wp-text'); ?>
                  <a href="<?php echo esc_url(admin_url('options-general.php')); ?>" target="_blank"><?php _e('Settings → General', 'ga-for-wp-text'); ?></a>.
                </div>
              <?php endif; ?>
            </div>

            <div class="divider" style="margin:0 16px"></div>

            <!-- Currency comparison -->
            <div class="ga4wp-status-compare-block">
              <div class="ga4wp-status-compare-header">
                <span class="material-icons-round">payments</span>
                <span><?php _e('Currency', 'ga-for-wp-text'); ?></span>
                <?php if (!empty($ga4_cur) && $woo_active) : ?>
                  <?php if ($cur_match) : ?>
                    <span class="ga4wp-status-match-chip ga4wp-status-match-ok">
                      <span class="material-icons-round">check_circle</span><?php _e('Match', 'ga-for-wp-text'); ?>
                    </span>
                  <?php else : ?>
                    <span class="ga4wp-status-match-chip ga4wp-status-match-warn">
                      <span class="material-icons-round">warning_amber</span><?php _e('Mismatch', 'ga-for-wp-text'); ?>
                    </span>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
              <div class="ga4wp-status-compare-cols">
                <div class="ga4wp-status-compare-col">
                  <div class="ga4wp-status-compare-source">
                    <img src="<?php echo esc_url(GA4WP_URL . 'assests/images/truana-mark.svg'); ?>" width="14" alt="GA4"> GA4
                  </div>
                  <?php if (!empty($ga4_cur)) : ?>
                    <span class="ga4wp-status-badge ga4wp-status-badge-green"><?php echo esc_html($ga4_cur); ?></span>
                  <?php else : echo $not_available; endif; ?>
                </div>
                <div class="ga4wp-status-compare-arrow material-icons-round">sync_alt</div>
                <div class="ga4wp-status-compare-col">
                  <div class="ga4wp-status-compare-source">
                    <span class="material-icons-round" style="font-size:13px;">shopping_cart</span> WooCommerce
                  </div>
                  <?php if ($woo_active) : ?>
                    <span class="ga4wp-status-badge <?php echo $cur_match ? 'ga4wp-status-badge-green' : 'ga4wp-status-badge-gray'; ?>">
                      <?php echo esc_html($woo_currency); ?>
                    </span>
                  <?php else : ?>
                    <span class="ga4wp-status-na"><?php _e('WooCommerce not active', 'ga-for-wp-text'); ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <?php if (!empty($ga4_cur) && $woo_active && !$cur_match) : ?>
                <div class="ga4wp-status-compare-hint">
                  <span class="material-icons-round">info</span>
                  <?php _e('Currency mismatch may cause revenue discrepancies. Update your WooCommerce currency under', 'ga-for-wp-text'); ?>
                  <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=general')); ?>" target="_blank"><?php _e('WooCommerce → Settings', 'ga-for-wp-text'); ?></a>.
                </div>
              <?php endif; ?>
            </div>

          </div>
        </div>
      </div>

      <!-- ── 3. Data & Marketing Preferences ── -->
      <div class="ga4wp-row ga4s-wrap">
        <div class="ga4wp-col s12">
          <div class="ga4s-section">
            <div class="ga4s-section-head">
              <span class="material-icons-round">manage_history</span>
              <div>
                <div class="ga4s-section-title"><?php _e('Data & Marketing Preferences', 'ga-for-wp-text'); ?></div>
                <div class="ga4s-section-desc"><?php _e('Data retention and user reset settings for this property', 'ga-for-wp-text'); ?></div>
              </div>
            </div>
            <div class="ga4s-rows">

              <div class="ga4s-row">
                <div class="ga4wp-status-row-label">
                  <span class="material-icons-round">history</span>
                  <?php _e('Event Data Retention', 'ga-for-wp-text'); ?>
                </div>
                <div class="ga4wp-status-row-value">
                  <?php
                  $ret = $data_retention['eventDataRetention'] ?? '';
                  echo $ret ? '<span class="ga4wp-status-badge ga4wp-status-badge-violet">' . esc_html($retention_labels[$ret] ?? $ret) . '</span>' : $not_available;
                  ?>
                </div>
              </div>

              <div class="ga4s-row">
                <div class="ga4wp-status-row-label">
                  <span class="material-icons-round">person_off</span>
                  <?php _e('Reset User Data on New Activity', 'ga-for-wp-text'); ?>
                </div>
                <div class="ga4wp-status-row-value">
                  <?php if (isset($data_retention['resetUserDataOnNewActivity'])) : ?>
                    <?php if ($data_retention['resetUserDataOnNewActivity']) : ?>
                      <span class="ga4wp-status-badge ga4wp-status-badge-green"><?php _e('Enabled', 'ga-for-wp-text'); ?></span>
                    <?php else : ?>
                      <span class="ga4wp-status-badge ga4wp-status-badge-gray"><?php _e('Disabled', 'ga-for-wp-text'); ?></span>
                    <?php endif; ?>
                  <?php else : echo $not_available; endif; ?>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

      <?php
      $required_dims = $required_dims ?? array();
      $existing_keys = array_keys($custom_dims);
      $total_req     = count($required_dims);
      $total_created = count(array_intersect(array_keys($required_dims), $existing_keys));
      $all_created   = $total_req > 0 && $total_created === $total_req;

      /* Build merged list: plugin-required dims first (created or not), then external dims */
      $merged_dims = array();
      foreach ($required_dims as $param => $req) {
        $in_ga4 = isset($custom_dims[$param]);
        $merged_dims[$param] = array(
          'displayName'   => $in_ga4 ? ($custom_dims[$param]['displayName'] ?? $param) : ($req[0] ?? $param),
          'scope'         => $in_ga4 ? ($custom_dims[$param]['scope']       ?? $req[1]) : $req[1],
          'description'   => $in_ga4 ? ($custom_dims[$param]['description'] ?? $req[2] ?? '') : ($req[2] ?? ''),
          'is_plugin_req' => true,
          'is_created'    => $in_ga4,
          'req_scope'     => strtoupper($req[1] ?? 'EVENT'),
          'ga4_scope'     => $in_ga4 ? strtoupper($custom_dims[$param]['scope'] ?? '') : null,
        );
      }
      foreach ($custom_dims as $param => $dim) {
        if (!isset($required_dims[$param])) {
          $merged_dims[$param] = array(
            'displayName'   => $dim['displayName'] ?? $param,
            'scope'         => $dim['scope']        ?? 'EVENT',
            'description'   => $dim['description']  ?? '',
            'is_plugin_req' => false,
            'is_created'    => true,
            'req_scope'     => null,
            'ga4_scope'     => strtoupper($dim['scope'] ?? ''),
          );
        }
      }
      ?>

      <!-- ── 4. All Custom Dimensions (combined) — premium only ── -->
      <?php $is_free_status = function_exists('gfw_fs') && gfw_fs()->is_not_paying() && !gfw_fs()->is_trial(); ?>
      <?php if (!$is_free_status) : ?>
      <div class="ga4wp-row ga4s-wrap">
        <div class="ga4wp-col s12">
          <div class="ga4s-section">
            <div class="ga4s-section-head">
              <span class="material-icons-round">dataset</span>
              <div>
                <div class="ga4s-section-title">
                  <?php _e('All Custom Dimensions', 'ga-for-wp-text'); ?>
                  <?php if (!empty($merged_dims)) : ?>
                    <span class="ga4wp-status-count-chip"><?php echo count($merged_dims); ?></span>
                  <?php endif; ?>
                  <?php if ($total_req > 0) : ?>
                    <span class="ga4wp-status-count-chip ga4wp-status-dim-counter"
                      style="background:<?php echo $all_created ? 'var(--ga-emerald)' : 'var(--ga-brand)'; ?>">
                      <?php echo $total_created . '/' . $total_req; ?> <?php _e('Plugin', 'ga-for-wp-text'); ?>
                    </span>
                  <?php endif; ?>
                </div>
                <div class="ga4s-section-desc"><?php _e('All custom dimensions on this GA4 property — plugin-required listed first, followed by any external dimensions.', 'ga-for-wp-text'); ?></div>
              </div>
              <?php if (!$all_created) : ?>
                <?php if ($has_edit_scope) : ?>
                  <button class="ga4wp-status-create-all-btn" id="ga4wp-create-all-dims">
                    <span class="material-icons-round">add_circle</span>
                    <?php _e('Create Missing', 'ga-for-wp-text'); ?>
                  </button>
                <?php else : ?>
                  <span class="ga4wp-status-edit-scope-notice" title="<?php esc_attr_e('Re-link your Google account and grant the analytics.edit permission to enable this action.', 'ga-for-wp-text'); ?>">
                    <span class="material-icons-round" style="font-size:15px;vertical-align:-3px;color:var(--ga-amber,#F59E0B);">lock</span>
                    <?php _e('analytics.edit scope required', 'ga-for-wp-text'); ?>
                  </span>
                <?php endif; ?>
              <?php endif; ?>
            </div>

            <?php if (!empty($merged_dims)) : ?>
              <table class="striped responsive-table ga4wp-status-mat-table">
                <thead>
                  <tr>
                    <th><?php _e('Parameter Name', 'ga-for-wp-text'); ?></th>
                    <th><?php _e('Display Name', 'ga-for-wp-text'); ?></th>
                    <th><?php _e('Scope', 'ga-for-wp-text'); ?></th>
                    <th><?php _e('Description', 'ga-for-wp-text'); ?></th>
                    <th><?php _e('Plugin Status', 'ga-for-wp-text'); ?></th>
                    <th><?php _e('GA4 Status', 'ga-for-wp-text'); ?></th>
                    <th><?php _e('Scope Status', 'ga-for-wp-text'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($merged_dims as $param => $dim) :
                    $scope_label   = ucfirst(strtolower($dim['scope']));
                    $desc          = !empty($dim['description']) ? $dim['description'] : '—';
                    $scope_matched = $dim['is_plugin_req'] && $dim['is_created'] && $dim['ga4_scope'] === $dim['req_scope'];
                    $scope_bad     = $dim['is_plugin_req'] && $dim['is_created'] && $dim['ga4_scope'] !== $dim['req_scope'];
                  ?>
                    <tr class="ga4wp-status-req-row" data-param="<?php echo esc_attr($param); ?>">
                      <td><code class="ga4wp-status-code"><?php echo esc_html($param); ?></code></td>
                      <td><?php echo esc_html($dim['displayName']); ?></td>
                      <td><span class="ga4wp-status-badge ga4wp-status-badge-gray"><?php echo esc_html($scope_label); ?></span></td>
                      <td class="ga4wp-status-dim-desc"><?php echo esc_html($desc); ?></td>
                      <td class="ga4wp-status-cell">
                        <?php if ($dim['is_plugin_req']) : ?>
                          <span class="ga4wp-status-badge ga4wp-status-badge-green">
                            <span class="material-icons-round">extension</span>
                            <?php _e('Plugin Required', 'ga-for-wp-text'); ?>
                          </span>
                        <?php else : ?>
                          <span class="ga4wp-status-badge ga4wp-status-badge-gray">
                            <span class="material-icons-round">tune</span>
                            <?php _e('External', 'ga-for-wp-text'); ?>
                          </span>
                        <?php endif; ?>
                      </td>
                      <td class="ga4wp-status-cell ga4wp-status-dim-create-cell">
                        <?php if (!$dim['is_plugin_req']) : ?>
                          <span class="ga4wp-status-na">—</span>
                        <?php elseif ($dim['is_created']) : ?>
                          <span class="ga4wp-status-badge ga4wp-status-badge-green">
                            <span class="material-icons-round">check_circle</span>
                            <?php _e('Created', 'ga-for-wp-text'); ?>
                          </span>
                        <?php else : ?>
                          <span class="ga4wp-status-badge ga4wp-status-badge-amber">
                            <span class="material-icons-round">schedule</span>
                            <?php _e('Not Created', 'ga-for-wp-text'); ?>
                          </span>
                        <?php endif; ?>
                      </td>
                      <td class="ga4wp-status-cell">
                        <?php if (!$dim['is_plugin_req']) : ?>
                          <span class="ga4wp-status-na">—</span>
                        <?php elseif ($scope_matched) : ?>
                          <span class="ga4wp-status-badge ga4wp-status-badge-teal">
                            <span class="material-icons-round">verified</span>
                            <?php _e('Matched', 'ga-for-wp-text'); ?>
                          </span>
                        <?php elseif ($scope_bad) : ?>
                          <span class="ga4wp-status-badge ga4wp-status-badge-amber">
                            <span class="material-icons-round">warning_amber</span>
                            <?php _e('Mismatch', 'ga-for-wp-text'); ?>
                          </span>
                        <?php else : ?>
                          <span class="ga4wp-status-na">—</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else : ?>
              <div class="ga4wp-status-empty-dims">
                <span class="material-icons-round">info</span>
                <span><?php _e('No custom dimensions found on this GA4 property.', 'ga-for-wp-text'); ?></span>
              </div>
            <?php endif; ?>

          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- ── 5. Measurement Protocol (available on both free and premium tiers) ── -->
      <?php
      $mp_key   = get_option('measurement_key');
      $mp_error = get_option('measurement_key_error');
      ?>
      <div class="ga4wp-row ga4s-wrap">
        <div class="ga4wp-col s12">
          <div class="ga4s-section">
            <div class="ga4s-section-head">
              <span class="material-icons-round">vpn_key</span>
              <div>
                <div class="ga4s-section-title"><?php _e('Measurement Protocol Secret', 'ga-for-wp-text'); ?></div>
                <div class="ga4s-section-desc"><?php _e('A server-side API secret used so events (e.g. WooCommerce order confirmations) still reach GA4 even after the browser has already navigated away.', 'ga-for-wp-text'); ?></div>
              </div>
              <?php if (empty($mp_key)) : ?>
                <?php if ($has_edit_scope) : ?>
                  <button class="ga4wp-status-create-all-btn" id="ga4wp-generate-mp-key" data-nonce="<?php echo esc_attr(wp_create_nonce('ga4wp-tab-update')); ?>">
                    <span class="material-icons-round">add_circle</span>
                    <?php echo $mp_error ? esc_html__('Retry', 'ga-for-wp-text') : esc_html__('Generate Now', 'ga-for-wp-text'); ?>
                  </button>
                <?php else : ?>
                  <span class="ga4wp-status-edit-scope-notice" title="<?php esc_attr_e('Re-link your Google account and grant the analytics.edit permission to enable this action.', 'ga-for-wp-text'); ?>">
                    <span class="material-icons-round" style="font-size:15px;vertical-align:-3px;color:var(--ga-amber,#F59E0B);">lock</span>
                    <?php _e('analytics.edit scope required', 'ga-for-wp-text'); ?>
                  </span>
                <?php endif; ?>
              <?php endif; ?>
            </div>
            <div class="ga4s-rows">
              <div class="ga4s-row">
                <div class="ga4wp-status-row-label">
                  <span class="material-icons-round">key</span>
                  <?php _e('Secret Status', 'ga-for-wp-text'); ?>
                </div>
                <div class="ga4wp-status-row-value" id="ga4wp-mp-key-status">
                  <?php if (!empty($mp_key)) : ?>
                    <span class="ga4wp-status-badge ga4wp-status-badge-green">
                      <span class="material-icons-round">check_circle</span><?php _e('Generated', 'ga-for-wp-text'); ?>
                    </span>
                    <code class="ga4wp-status-code" id="ga4wp-mp-key-value">••••<?php echo esc_html(substr($mp_key, -4)); ?></code>
                    <button type="button" class="ga4wp-status-copy-btn" id="ga4wp-mp-key-copy" data-secret="<?php echo esc_attr($mp_key); ?>" title="<?php esc_attr_e('Copy secret', 'ga-for-wp-text'); ?>">
                      <span class="material-icons-round">content_copy</span>
                    </button>
                  <?php elseif (!empty($mp_error)) : ?>
                    <span class="ga4wp-status-badge ga4wp-status-badge-red">
                      <span class="material-icons-round">error_outline</span><?php _e('Failed', 'ga-for-wp-text'); ?>
                    </span>
                  <?php else : ?>
                    <span class="ga4wp-status-badge ga4wp-status-badge-amber">
                      <span class="material-icons-round">schedule</span><?php _e('Not Generated', 'ga-for-wp-text'); ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <?php if (!empty($mp_error)) : ?>
                <div class="ga4wp-status-empty-dims" id="ga4wp-mp-key-error" style="color:#B45309;">
                  <span class="material-icons-round">warning_amber</span>
                  <span>
                    <?php
                    printf(
                      /* translators: %s: raw error message returned by the Google Analytics Admin API */
                      __('Google Analytics rejected the request: <strong>%s</strong>. ', 'ga-for-wp-text'),
                      esc_html($mp_error)
                    );
                    _e('This usually means the Measurement Protocol terms have not been accepted for this property yet. Open Google Analytics → Admin → Data Streams → your stream → Measurement Protocol API secrets, create one secret there to accept the terms, then use Retry above.', 'ga-for-wp-text');
                    ?>
                    <a href="https://analytics.google.com/analytics/web/" target="_blank" rel="noopener"><?php _e('Open Google Analytics', 'ga-for-wp-text'); ?></a>
                  </span>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div><!-- .ga4wp-status-grid -->

  <?php endif; ?>

</div>

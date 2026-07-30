<?php
if (! defined('ABSPATH')) die;
?>
<div class="ga4wp-col s12 ga4wp-options" style="display:flex;flex-direction:column;gap:8px;padding-top:8px !important;">

  <div class="ga4wp-dash-pro-header" style="margin:0;">
    <span class="material-icons-round">explore</span>
    <div>
      <strong><?php _e('Explore — Custom Report Builder', 'ga-for-wp-text'); ?></strong>
      <span><?php _e('Build any GA4 report you can imagine. Pick dimensions, metrics, chart types, date ranges, and save reports for later — all from inside WordPress.', 'ga-for-wp-text'); ?></span>
    </div>
  </div>

  <style>
    .ga4wp-upsell-tiles .ga4wp-col {
      padding: 0
    }

    .ga4wp-upsell-tiles .ga4wp-info-box {
      margin-bottom: 0 !important;
      height: 100%;
      box-sizing: border-box
    }
  </style>
  <div class="ga4wp-upsell-tiles" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">

    <div class="ga4wp-info-box valign-wrapper">
      <div class="ga4wp-col s3 l2">
        <span class="material-icons-round ga4wp-info-icon">dashboard_customize</span>
      </div>
      <div class="ga4wp-col s9 l10">
        <p class="ga4wp-info-title"><?php _e('Drag-and-Drop Report Builder', 'ga-for-wp-text'); ?></p>
        <p class="ga4wp-info-description"><?php _e('Choose any GA4 dimension and metric combination. Mix and match to create reports that answer your exact questions.', 'ga-for-wp-text'); ?></p>
      </div>
    </div>

    <div class="ga4wp-info-box valign-wrapper">
      <div class="ga4wp-col s3 l2">
        <span class="material-icons-round ga4wp-info-icon">insert_chart</span>
      </div>
      <div class="ga4wp-col s9 l10">
        <p class="ga4wp-info-title"><?php _e('Multiple Visualization Types', 'ga-for-wp-text'); ?></p>
        <p class="ga4wp-info-description"><?php _e('Render results as a table, bar chart, line chart, or doughnut chart — switch views without re-running the query.', 'ga-for-wp-text'); ?></p>
      </div>
    </div>

    <div class="ga4wp-info-box valign-wrapper">
      <div class="ga4wp-col s3 l2">
        <span class="material-icons-round ga4wp-info-icon">date_range</span>
      </div>
      <div class="ga4wp-col s9 l10">
        <p class="ga4wp-info-title"><?php _e('Custom Date Ranges', 'ga-for-wp-text'); ?></p>
        <p class="ga4wp-info-description"><?php _e('Run reports for any date window — today, last 7 days, last 90 days, or any custom range you define.', 'ga-for-wp-text'); ?></p>
      </div>
    </div>

    <div class="ga4wp-info-box valign-wrapper">
      <div class="ga4wp-col s3 l2">
        <span class="material-icons-round ga4wp-info-icon">view_column</span>
      </div>
      <div class="ga4wp-col s9 l10">
        <p class="ga4wp-info-title"><?php _e('Custom Dimension Support', 'ga-for-wp-text'); ?></p>
        <p class="ga4wp-info-description"><?php _e('Include custom dimensions registered in your GA4 property — author, post type, category, and more — as report dimensions.', 'ga-for-wp-text'); ?></p>
      </div>
    </div>

    <div class="ga4wp-info-box valign-wrapper">
      <div class="ga4wp-col s3 l2">
        <span class="material-icons-round ga4wp-info-icon">bookmark</span>
      </div>
      <div class="ga4wp-col s9 l10">
        <p class="ga4wp-info-title"><?php _e('Save & Name Reports', 'ga-for-wp-text'); ?></p>
        <p class="ga4wp-info-description"><?php _e('Bookmark any custom report with a name and description. Saved reports appear in your Dashboard Settings for quick re-access.', 'ga-for-wp-text'); ?></p>
      </div>
    </div>

    <div class="ga4wp-info-box valign-wrapper">
      <div class="ga4wp-col s3 l2">
        <span class="material-icons-round ga4wp-info-icon">sort</span>
      </div>
      <div class="ga4wp-col s9 l10">
        <p class="ga4wp-info-title"><?php _e('Row Limit & Sorting', 'ga-for-wp-text'); ?></p>
        <p class="ga4wp-info-description"><?php _e('Control how many rows to fetch (up to 500) and sort by any metric column — descending or ascending — in a single click.', 'ga-for-wp-text'); ?></p>
      </div>
    </div>

  </div>

  <div class="center-align" style="padding:16px 0 24px;">
    <p style="color:var(--ga-text2);font-size:13px;margin-bottom:14px;">
      <?php _e('Upgrade to Pro to unlock the Explore report builder and build unlimited custom GA4 reports.', 'ga-for-wp-text'); ?>
    </p>
    <a class="btn upgrade-btn waves-effect waves-light" href="<?php echo esc_url(gfw_fs()->get_upgrade_url()); ?>" target="_blank">
      <span class="material-icons-round left">workspace_premium</span>
      <?php _e('Upgrade to Pro', 'ga-for-wp-text'); ?>
    </a>
  </div>

</div>
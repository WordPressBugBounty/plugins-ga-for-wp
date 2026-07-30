<?php
if (! defined('ABSPATH')) die;
/* $site_url is passed via view_options() */
?>
<div class="ga4wp-col s12 ga4wp-options" style="display:flex;flex-direction:column;gap:8px;padding-top:8px !important;">

  <div class="ga4wp-dash-pro-header" style="margin:0;">
    <span class="material-icons-round">link</span>
    <div>
      <strong><?php _e('UTM Builder — Campaign URL Generator', 'ga-for-wp-text'); ?></strong>
      <span><?php _e('Build properly tagged campaign URLs for Google Analytics 4 without leaving WordPress. Track every marketing channel with precision.', 'ga-for-wp-text'); ?></span>
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
        <span class="material-icons-round ga4wp-info-icon">add_link</span>
      </div>
      <div class="ga4wp-col s9 l10">
        <p class="ga4wp-info-title"><?php _e('Campaign URL Builder', 'ga-for-wp-text'); ?></p>
        <p class="ga4wp-info-description"><?php _e('Fill in source, medium, campaign name, term, and content to instantly generate a GA4-ready tagged URL for any destination page.', 'ga-for-wp-text'); ?></p>
      </div>
    </div>

    <div class="ga4wp-info-box valign-wrapper">
      <div class="ga4wp-col s3 l2">
        <span class="material-icons-round ga4wp-info-icon">campaign</span>
      </div>
      <div class="ga4wp-col s9 l10">
        <p class="ga4wp-info-title"><?php _e('All UTM Parameters Supported', 'ga-for-wp-text'); ?></p>
        <p class="ga4wp-info-description"><?php _e('Covers utm_source, utm_medium, utm_campaign, utm_term, and utm_content — everything GA4 recognises for campaign attribution.', 'ga-for-wp-text'); ?></p>
      </div>
    </div>

    <div class="ga4wp-info-box valign-wrapper">
      <div class="ga4wp-col s3 l2">
        <span class="material-icons-round ga4wp-info-icon">content_copy</span>
      </div>
      <div class="ga4wp-col s9 l10">
        <p class="ga4wp-info-title"><?php _e('One-Click Copy', 'ga-for-wp-text'); ?></p>
        <p class="ga4wp-info-description"><?php _e('The generated URL updates live as you type. Copy it to clipboard in one click — ready to paste into email campaigns, ads, or social posts.', 'ga-for-wp-text'); ?></p>
      </div>
    </div>

    <div class="ga4wp-info-box valign-wrapper">
      <div class="ga4wp-col s3 l2">
        <span class="material-icons-round ga4wp-info-icon">history</span>
      </div>
      <div class="ga4wp-col s9 l10">
        <p class="ga4wp-info-title"><?php _e('Campaign History', 'ga-for-wp-text'); ?></p>
        <p class="ga4wp-info-description"><?php _e('Previously generated URLs are saved in a history panel so you can copy or edit past campaigns without rebuilding them from scratch.', 'ga-for-wp-text'); ?></p>
      </div>
    </div>

    <div class="ga4wp-info-box valign-wrapper" style="grid-column:1 / -1;">
      <div class="ga4wp-col s3 l2">
        <span class="material-icons-round ga4wp-info-icon">analytics</span>
      </div>
      <div class="ga4wp-col s9 l10">
        <p class="ga4wp-info-title"><?php _e('Google Analytics Integration', 'ga-for-wp-text'); ?></p>
        <p class="ga4wp-info-description"><?php _e('Tagged URLs flow directly into your GA4 Acquisition reports — source, medium, and campaign data appear automatically once visitors click your links.', 'ga-for-wp-text'); ?></p>
      </div>
    </div>

  </div>

  <div class="center-align" style="padding:16px 0 24px;">
    <p style="color:var(--ga-text2);font-size:13px;margin-bottom:14px;">
      <?php _e('Upgrade to Pro to unlock the UTM Builder and start tracking every marketing campaign with precision.', 'ga-for-wp-text'); ?>
    </p>
    <a class="btn upgrade-btn waves-effect waves-light" href="<?php echo esc_url(gfw_fs()->get_upgrade_url()); ?>" target="_blank">
      <span class="material-icons-round left">workspace_premium</span>
      <?php _e('Upgrade to Pro', 'ga-for-wp-text'); ?>
    </a>
  </div>

</div>
<?php
if (! defined('ABSPATH')) die;

$adv_errors = '';

/* ── Save handler (free: facebook_pixel + google_adword only) ── */
if (isset($_POST['ga4wp_advance_submit']) && wp_verify_nonce($_POST['ga4wp_nonce_header'], 'ga4wp_advance_submit') && current_user_can('manage_options')) {
	if (! empty($_POST['ga4wp_advance_settings'])) {
		$_POST['ga4wp_advance_settings'] = array_intersect_key(
			$_POST['ga4wp_advance_settings'],
			array_flip(['facebook_pixel', 'facebook_pixel_code', 'google_adword', 'google_adword_code', 'google_adword_label'])
		);
		if (isset($_POST['ga4wp_advance_settings']['google_adword'])) {
			if (empty($_POST['ga4wp_advance_settings']['google_adword_code']))
				$adv_errors .= __('Please supply proper Google Adword code!', 'ga-for-wp-text') . '<br>';
			if (empty($_POST['ga4wp_advance_settings']['google_adword_label'] ?? ''))
				$adv_errors .= __('Please supply proper Google Adword Label!', 'ga-for-wp-text') . '<br>';
		}
		if (empty($adv_errors)) {
			$adv_save = GA4WP_Settings::get_instance()->parse_ga4wp_advance_settings($_POST['ga4wp_advance_settings']);
			if ($adv_save) {
				$existing = get_option('ga4wp_advance_settings') ?: [];
				foreach (['bing_uet', 'bing_uet_code', 'tiktok_pixel', 'tiktok_pixel_code', 'pinterest_pixel', 'pinterest_pixel_code', 'linkedin_insight', 'linkedin_insight_code', 'snapchat_pixel', 'snapchat_pixel_code'] as $_k) {
					$adv_save[$_k] = $existing[$_k] ?? false;
				}
				update_option('ga4wp_advance_settings', $adv_save);
				echo '<script>jQuery(document).ready(function(){ M.toast({html:"' . esc_js(__('Setting Saved!', 'ga-for-wp-text')) . '", classes:"rounded teal", displayLength:4000}); });</script>';
			} else {
				$adv_errors .= __('Error while saving data!', 'ga-for-wp-text');
			}
		}
	} else {
		$adv_errors .= __('Nothing to save.', 'ga-for-wp-text');
	}
}

if ($adv_errors) {
	echo '<script>jQuery(document).ready(function(){
		M.toast({html:"' . esc_js(strip_tags($adv_errors)) . '", classes:"rounded red", displayLength:6000});
	});</script>';
}

$ga4wp_advance_settings = get_option('ga4wp_advance_settings') ?: [];
?>
<div class="ga4wp-col s12 ga4wp-options" style="display:flex;flex-direction:column;gap:8px;padding-top:8px !important;">

	<div class="ga4wp-dash-pro-header" style="margin:0;">
		<span class="material-icons-round">extension</span>
		<div><strong><?php _e('Advanced Integration', 'ga-for-wp-text'); ?></strong><span><?php _e('Connect third-party tracking pixels and conversion tags to your website.', 'ga-for-wp-text'); ?></span></div>
	</div>

	<form action="" method="POST">
		<div class="ga4wp-row ga4s-wrap" style="display:flex;flex-wrap:wrap;align-items:stretch;margin-left:-0.75rem;margin-right:-0.75rem;">

			<!-- Facebook Pixel -->
			<div class="ga4wp-col s12 m6">
				<div class="ga4s-section">
					<div class="ga4s-section-head"><span class="material-icons-round">facebook</span>
						<div>
							<div class="ga4s-section-title"><?php _e('Facebook Pixel', 'ga-for-wp-text'); ?></div>
							<div class="ga4s-section-desc"><?php _e('Track visitors on Facebook via Meta Pixel.', 'ga-for-wp-text'); ?></div>
						</div>
					</div>
					<div class="ga4s-rows">
						<div class="ga4s-row">
							<div class="ga4s-row-info">
								<div class="ga4s-row-label"><?php _e('Enable Facebook Pixel', 'ga-for-wp-text'); ?></div>
								<div class="ga4s-row-desc"><?php _e('Inject Meta Pixel snippet on all pages.', 'ga-for-wp-text'); ?></div>
							</div>
							<div class="switch"><label><input type="checkbox" name="ga4wp_advance_settings[facebook_pixel]" value="yes" <?php checked(! empty($ga4wp_advance_settings['facebook_pixel'])); ?>><span class="lever"></span></label></div>
						</div>
						<div class="ga4s-input-row"><label class="ga4s-input-label"><?php _e('Pixel ID', 'ga-for-wp-text'); ?></label>
							<div class="input-field" style="margin:0;"><input class="validate" placeholder="XXXXXXXXXX" name="ga4wp_advance_settings[facebook_pixel_code]" type="text" value="<?php echo esc_attr($ga4wp_advance_settings['facebook_pixel_code'] ?? ''); ?>"></div>
							<div class="ga4s-input-hint"><a href="https://trueana.com/get-facebook-pixel-code" target="_blank"><?php _e('How to get your Facebook Pixel ID?', 'ga-for-wp-text'); ?></a></div>
						</div>
					</div>
				</div>
			</div>

			<!-- Google Ads Conversion Tracking -->
			<div class="ga4wp-col s12 m6">
				<div class="ga4s-section">
					<div class="ga4s-section-head"><span class="material-icons-round">ads_click</span>
						<div>
							<div class="ga4s-section-title"><?php _e('Google Ads Conversion Tracking', 'ga-for-wp-text'); ?></div>
							<div class="ga4s-section-desc"><?php _e('Track conversions back to Google Ads campaigns.', 'ga-for-wp-text'); ?></div>
						</div>
					</div>
					<div class="ga4s-rows">
						<div class="ga4s-row">
							<div class="ga4s-row-info">
								<div class="ga4s-row-label"><?php _e('Enable Google Ads Conversion Tracking', 'ga-for-wp-text'); ?></div>
							</div>
							<div class="switch"><label><input type="checkbox" name="ga4wp_advance_settings[google_adword]" value="yes" <?php checked(! empty($ga4wp_advance_settings['google_adword'])); ?>><span class="lever"></span></label></div>
						</div>
						<div class="ga4wp-row ga4s-2col">
							<div class="ga4wp-col s12 m6"><label class="ga4s-input-label"><?php _e('Conversion ID', 'ga-for-wp-text'); ?></label>
								<div class="input-field" style="margin:0;"><input class="validate" placeholder="AW-CONVERSION_ID" name="ga4wp_advance_settings[google_adword_code]" type="text" value="<?php echo esc_attr($ga4wp_advance_settings['google_adword_code'] ?? ''); ?>"></div>
							</div>
							<div class="ga4wp-col s12 m6"><label class="ga4s-input-label"><?php _e('Conversion Label', 'ga-for-wp-text'); ?></label>
								<div class="input-field" style="margin:0;"><input class="validate" placeholder="AW-CONVERSION_LABEL" name="ga4wp_advance_settings[google_adword_label]" type="text" value="<?php echo esc_attr($ga4wp_advance_settings['google_adword_label'] ?? ''); ?>"></div>
							</div>
						</div>
						<div class="ga4s-input-hint" style="padding:8px 0 14px"><a href="https://trueana.com/get-google-ads-conversion-id-label" target="_blank"><?php _e('How to get Conversion ID and Label?', 'ga-for-wp-text'); ?></a></div>
					</div>
				</div>
			</div>

			<div class="ga4wp-col s12 ga4s-actions">
				<button class="ga4s-save-btn" type="submit" name="ga4wp_advance_submit" value="submit">
					<span class="material-icons-round">save</span><?php _e('Save Advanced Settings', 'ga-for-wp-text'); ?>
				</button>
			</div>
			<?php wp_nonce_field('ga4wp_advance_submit', 'ga4wp_nonce_header'); ?>
		</div>
	</form>

	<!-- Pro upsell -->
	<div class="ga4wp-dash-pro-header" style="margin:8px 0 0;">
		<span class="material-icons-round">workspace_premium</span>
		<div><strong><?php _e('Unlock More Integrations with Pro', 'ga-for-wp-text'); ?></strong><span><?php _e('Upgrade to connect additional advertising and analytics pixels across all major platforms.', 'ga-for-wp-text'); ?></span></div>
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

		.ga4wp-adv-icon {
			width: 40px;
			height: 40px;
			border-radius: 10px;
			background: var(--ga-brand-soft);
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
			margin: 0 auto
		}
	</style>
	<div class="ga4wp-upsell-tiles" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">

		<div class="ga4wp-info-box valign-wrapper">
			<div class="ga4wp-col s3 l2">
				<div class="ga4wp-adv-icon"><span class="material-icons-round" style="color:var(--ga-brand);font-size:20px;">search</span></div>
			</div>
			<div class="ga4wp-col s9 l10">
				<p class="ga4wp-info-title"><?php _e('Bing / Microsoft UET', 'ga-for-wp-text'); ?></p>
				<p class="ga4wp-info-description"><?php _e('Track Microsoft Ads conversions and build remarketing audiences via the Universal Event Tracking tag.', 'ga-for-wp-text'); ?></p>
			</div>
		</div>

		<div class="ga4wp-info-box valign-wrapper">
			<div class="ga4wp-col s3 l2">
				<div class="ga4wp-adv-icon"><span class="material-icons-round" style="color:var(--ga-brand);font-size:20px;">play_circle</span></div>
			</div>
			<div class="ga4wp-col s9 l10">
				<p class="ga4wp-info-title"><?php _e('TikTok Pixel', 'ga-for-wp-text'); ?></p>
				<p class="ga4wp-info-description"><?php _e('Measure TikTok ad performance, track purchase events, and create custom audiences for retargeting.', 'ga-for-wp-text'); ?></p>
			</div>
		</div>

		<div class="ga4wp-info-box valign-wrapper">
			<div class="ga4wp-col s3 l2">
				<div class="ga4wp-adv-icon"><span class="material-icons-round" style="color:var(--ga-brand);font-size:20px;">push_pin</span></div>
			</div>
			<div class="ga4wp-col s9 l10">
				<p class="ga4wp-info-title"><?php _e('Pinterest Tag', 'ga-for-wp-text'); ?></p>
				<p class="ga4wp-info-description"><?php _e('Track conversions from Pinterest ads and build retargeting audiences from your site visitors.', 'ga-for-wp-text'); ?></p>
			</div>
		</div>

		<div class="ga4wp-info-box valign-wrapper">
			<div class="ga4wp-col s3 l2">
				<div class="ga4wp-adv-icon"><span class="material-icons-round" style="color:var(--ga-brand);font-size:20px;">work</span></div>
			</div>
			<div class="ga4wp-col s9 l10">
				<p class="ga4wp-info-title"><?php _e('LinkedIn Insight Tag', 'ga-for-wp-text'); ?></p>
				<p class="ga4wp-info-description"><?php _e('Measure B2B ad conversions on LinkedIn and build matched audience segments from your visitors.', 'ga-for-wp-text'); ?></p>
			</div>
		</div>

		<div class="ga4wp-info-box valign-wrapper">
			<div class="ga4wp-col s3 l2">
				<div class="ga4wp-adv-icon"><span class="material-icons-round" style="color:var(--ga-brand);font-size:20px;">filter_vintage</span></div>
			</div>
			<div class="ga4wp-col s9 l10">
				<p class="ga4wp-info-title"><?php _e('Snapchat Pixel', 'ga-for-wp-text'); ?></p>
				<p class="ga4wp-info-description"><?php _e('Track Snapchat ad conversions, measure campaign ROI, and create audiences for Snap retargeting.', 'ga-for-wp-text'); ?></p>
			</div>
		</div>

	</div>

	<div class="center-align" style="padding:16px 0 24px;">
		<p style="color:var(--ga-text2);font-size:13px;margin-bottom:14px;">
			<?php _e('Upgrade to Pro to unlock all integrations and track every advertising channel from one place.', 'ga-for-wp-text'); ?>
		</p>
		<a class="btn upgrade-btn waves-effect waves-light" href="<?php echo esc_url(gfw_fs()->get_upgrade_url()); ?>">
			<span class="material-icons-round left">workspace_premium</span>
			<?php _e('Upgrade to Pro', 'ga-for-wp-text'); ?>
		</a>
	</div>

</div>
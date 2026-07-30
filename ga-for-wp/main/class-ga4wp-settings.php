<?php
/*class for creating settings fields and prasing them before saving*/
if (! defined('ABSPATH')) {
	die;
}
/*
 * Declaring Class
 */
class GA4WP_Settings
{
	/*initiating variables */
	private static $instance = null;
	private $fevicon_url;
	/* general dashboard */
	/* Audience dashboard */
	public $ga4wp_dash_stats_data_ga4_audience;
	public $ga4wp_report_request_ga4_audience;
	public $ga4wp_report_chart_data_ga4_audience;
	/* Acquisition dashboard */
	public $ga4wp_report_request_ga4_acquisition;
	public $ga4wp_report_chart_data_ga4_acquisition;
	/* Behavior dashboard */
	public $ga4wp_report_request_ga4_behavior;
	public $ga4wp_report_chart_data_ga4_behavior;
	public $ga4wp_dash_data_ga4_widget;
	/* Genral Settings */
	public $ga4wp_tracking_settings;
	public $ga4wp_event_settings;
	public $ga4wp_dash_settings;
	public $ga4wp_auth_settings;
	public $ga4wp_advance_settings;
	public $ga4wp_event_hooks;
	public $ga4wp_features_list;
	public $ga4wp_custom_dimensions = array();
	public $ga4wp_pro_tabs;

	/**
	 * Populate all default settings, event-hook maps and report/dashboard
	 * configuration arrays used throughout the free tier.
	 *
	 * Builds the tracking, event, dashboard, authentication and advance
	 * settings defaults (varying by whether WooCommerce is active), the
	 * event-to-hook mapping, the report request/chart definitions for the
	 * audience/acquisition/behavior dashboards, the features list shown on
	 * the upsell/about screen, and the pro-tab definitions used to render
	 * locked "pro" report tabs on the free dashboard.
	 */
	public function __construct()
	{
		$this->fevicon_url = get_site_icon_url(75);
		if (class_exists('WooCommerce')) {
			/* Tracking Settings */
			$this->ga4wp_tracking_settings = array(
				'track_admin' => true,
				'not_track_pageviews' => false,
				'enhanced_link_attribution' => true,
				'product_single_track' => true,
				'product_archive_track' => true,
				'disable_on_hold_conversion' => true,
				'anonymize_ip' => true,
				'track_interest' => false,
				'not_track_user_id' => true,
				'track_ga_consent' => false,
			);
			/* Event Tracking Settings */
			$this->ga4wp_event_settings = array(
				'user_login' => true,
				'user_login_errors' => true,
				'user_logout' => true,
				'viewed_signup_form' => true,
				'user_signup' => true,
				'viewed_shop' => true,
				'viewed_product' => true,
				'added_product' => true,
				'removed_product' => true,
				'changed_quantity' => true,
				'viewed_cart' => true,
				'wrong_coupon_applied' => true,
				'applied_coupon' => true,
				'removed_coupon' => true,
				'begin_checkout' => true,
				'filled_checkout_form' => true,
				'added_payment_method' => true,
				'added_shipping_method' => true,
				'order_failed' => true,
				'processing_payment' => true,
				'completed_purchase' => true,
				'wrote_review' => true,
				'commented' => true,
				'viewed_account' => true,
				'viewed_order' => true,
				'changed_password' => true,
				'lost_password' => true,
				'estimated_shipping' => true,
				'order_cancelled' => true,
				'order_refunded' => true,
				'log_error' => true,
			);
			/* Hooks Associated with events */
			$this->ga4wp_event_hooks = array(
				'user_login' =>  array(2, 'wp_login'),
				'user_login_errors' => array('filter', 'login_errors'),
				'user_logout' => 'wp_logout',
				'viewed_signup_form' => 'woocommerce_register_form',
				'user_signup' => 'user_register',
				'viewed_shop' => 'wp_head',
				'viewed_product' => 'woocommerce_after_single_product_summary',
				'added_product' => 'woocommerce_add_to_cart',
				'removed_product' => 'woocommerce_remove_cart_item',
				'changed_quantity' =>  array(2, 'woocommerce_after_cart_item_quantity_update'),
				'viewed_cart' => array('woocommerce_cart_is_empty', 'woocommerce_after_cart_contents'),
				'wrong_coupon_applied' => array(3, 'filter', 'woocommerce_coupon_error'),
				'applied_coupon' => 'woocommerce_applied_coupon',
				'removed_coupon' => 'woocommerce_removed_coupon',
				'begin_checkout' => 'woocommerce_after_checkout_form',
				'filled_checkout_form' => 'woocommerce_after_checkout_form',
				'added_shipping_method' => 'woocommerce_after_checkout_form',
				'added_payment_method' => 'woocommerce_after_checkout_form',
				'order_failed' => array(2, 'woocommerce_order_status_failed'),
				'processing_payment' => 'woocommerce_checkout_order_processed',
				'completed_purchase' => array('woocommerce_order_status_on-hold', 'woocommerce_payment_complete', 'woocommerce_order_status_processing', 'woocommerce_order_status_completed', 'woocommerce_thankyou'),
				'wrote_review' => 'comment_post',
				'commented' => 'comment_post',
				'viewed_account' => 'woocommerce_after_my_account',
				'viewed_order' => 'woocommerce_view_order',
				'changed_password' => 'woocommerce_save_account_details',
				'lost_password' =>  array('filter', 'woocommerce_lost_password_confirmation_message'),
				'estimated_shipping' => 'woocommerce_calculated_shipping',
				'order_cancelled' => 'woocommerce_cancelled_order',
				'order_refunded' => array(2, 'woocommerce_order_refunded'),
				'log_error' => 'woocommerce_shutdown_error',
			);
		} else {
			/* General Dahboard */

			/* Tracking Settings */
			$this->ga4wp_tracking_settings = array(
				'track_admin' => true,
				'not_track_pageviews' => false,
				'enhanced_link_attribution' => true,
				'anonymize_ip' => true,
				'track_interest' => false,
				'not_track_user_id' => true,
				'track_ga_consent' => false,
			);
			/* Event Tracking Settings */
			$this->ga4wp_event_settings = array(
				'user_login' => true,
				'user_login_errors' => true,
				'user_logout' => true,
				'wrote_review' => true,
				'commented' => true,
				'log_error' => true,
			);
			/* Hooks Associated with events */
			$this->ga4wp_event_hooks = array(
				'user_login' =>  array(2, 'wp_login'),
				'user_login_errors' => array('filter', 'login_errors'),
				'user_logout' => 'wp_logout',
				'wrote_review' => 'comment_post',
				'commented' => 'comment_post',
				'log_error' => 'woocommerce_shutdown_error',
			);
		}
		/* Dashboard Data Widgets */
		$this->ga4wp_dash_data_ga4_widget = array(
			'TrueAna: Overview Report' => array('Overview Report', 'line', 'No. of Users', array('total users', 'new users'), 1, 'description'),
			'TrueAna: Users By Country Report' => array('Users By Country Report', 'bar', 'Country', 'No. of Users', 2, 'description'),
			'TrueAna: Users By Language Report' => array('Users By Language Report', 'bar', 'Language', 'No. of Users', 3, 'description'),
			'TrueAna: Users By Device Category Report' => array('Users By Device Category Report', 'doughnut', 'Device Category', 'No. of Users', 4, 'description'),
			'TrueAna: Quick Stats' => array('Quick Stats', 'stats', 'Stats', 'No. of Users', 0, 'description'),
		);
		/* Audience Dashboard */
		$this->ga4wp_dash_stats_data_ga4_audience = array(
			'totalUsers' => array('people_alt', 'Users', '', false, true),
			'newUsers' => array('group_add', 'New Users', '', false, true),
			'sessions' => array('hourglass_bottom', 'Total Sessions', '', false, true),
			'sessionsPerUser' => array('timelapse', 'Sessions/User', '', false, true),
			'screenPageViews' => array('pageview', 'Pageviews', '', false, true),
			'averageSessionDuration' => array('timer', 'Avg. Session Duration', 's', false, true),
			'engagementRate' => array('timeline', 'Engagement Rate', '', false, true),
			'screenPageViewsPerUser' => array('find_in_page', 'Pageviews/User', '', false, true),
		);
		$this->ga4wp_report_request_ga4_audience = array(
			'stats' => array(),
			'dateViseVisitors' => array(array('totalUsers', 'newUsers'), 'date', 'date'),
			'countryViseVisitors' => array('totalUsers', 'country', 'totalUsers'),
			'languageViseVisitors' => array('totalUsers', 'language', 'totalUsers'),
			'deviceViseVisitors' => array('totalUsers', 'deviceCategory', 'totalUsers'),
		);
		$this->ga4wp_report_chart_data_ga4_audience = array(
			'stats' => array(),
			'dateViseVisitors' => array('line', 'Overview Report', 'Date', array('Users', 'New Users'), 'This report shows no. of users and from howmany were new users visited website for specific date over the period of time.'),
			'countryViseVisitors' => array('table', 'Country Based Users Report', 'Country', array('No. of Users'), 'This reports categories users to different countries based on their location for specific period of time. '),
			'languageViseVisitors' => array('table', 'Language Based Users Report', 'Language', array('No. of Users'), 'This reports categories users based on their browser language for specific period of time.'),
			'deviceViseVisitors' => array('doughnut', 'Device Based Users Report', '', '', 'This report categories users based of their device category for specific period of time.'),
		);
		/* Acquisition Dashboard */
		$this->ga4wp_report_request_ga4_acquisition = array(
			'channelsReport' => array('activeUsers', 'sessionDefaultChannelGrouping', 'activeUsers'),
			'sourceReport' => array('activeUsers', 'sessionSource', 'activeUsers'),
			'referralsReport' => array('activeUsers', 'screenResolution', 'activeUsers'),
			'mediumReport' => array('activeUsers', 'sessionMedium', 'activeUsers'),
		);
		$this->ga4wp_report_chart_data_ga4_acquisition = array(
			'channelsReport' => array('doughnut', 'Users Based on Channels', 'Channels', 'No. of Users', 'This report shows analysis about which channel contributed most traffic for website for specified period of time.'),
			'sourceReport' => array('table', 'Users Based on Source', 'Source', 'No. of Users', 'This report classify users based on source/medium by using users reached website for specific period of time.'),
			'screenSizeReport' => array('table', 'Users Based on Screen Sizes', 'Screen Resolution', 'No. of Users', 'This report classify different screen sizes users were using for browsing website over period of time.'),
			'mediumReport' => array('bar', 'Users Based on Medium', 'Medium', 'No. of Users', 'This report classify users based on medium by using users reached website for specific period of time.'),
		);
		/* Behavior Dashboard */
		$this->ga4wp_report_request_ga4_behavior = array(
			'topPageReport' => array('totalUsers', 'pageTitle', 'totalUsers'),
			'timeOnPageReport' => array('userEngagementDuration', 'pageTitle', 'userEngagementDuration'),
			'usersAgeGroup' => array('totalUsers', 'userAgeBracket', 'totalUsers'),
			'genderReport' => array('totalUsers', 'userGender', 'totalUsers'),
			'osReport' => array('totalUsers', 'operatingSystem', 'totalUsers'),
		);
		$this->ga4wp_report_chart_data_ga4_behavior = array(
			'topPageReport' => array('table', 'Page Performance Report', 'Page Title', 'No. of Users', 'This report shows which pages have maxium visitors over the period of time.'),
			'timeOnPageReport' => array('table', 'Avg. Time Spend of Page', 'Page Title', 'Time on Page', 'The total amount of time your website page was in the foreground of users\' devices for specified time period, shown as seconds, minutes, hours, or days depending on length.'),
			'usersAgeGroup' => array('bar', 'Users based on Age-Group', 'Age Group', 'No. of Users', 'This is devision of traffic based on age groups over the period of time.'),
			'genderReport' => array('doughnut', 'Gender Based User Report', '', '', 'Gender based division of Traffic over the period of time.'),
			'osReport' => array('bar', 'Operating System Report', 'Operating Report', 'No. of Users', 'Operating Report', 'No. of Users', 'This report shows highly used operating system by website users for specified period of time.'),
		);
		/* Dashboard settings */
		$this->ga4wp_dash_settings = array(
			'report_view' => '',
			'report_frame' => 'Last 30 days',
			'report_from' => '',
			'report_to' => '',
		);
		/* Authentication Settings */
		$this->ga4wp_auth_settings = array(
			'trackind_id' => '',
			'property_id' => '',
			'api_secret' => '',
			'manual_tracking' => false,
			'agreement' => true,
		);
		/* Advance Settings */
		$this->ga4wp_advance_settings = array(
			'facebook_pixel'          => false,
			'facebook_pixel_code'     => '',
			'google_adword'           => false,
			'google_adword_code'      => '',
			'google_adword_label'     => '',
			'google_measurement'      => true,
		);
		/* ga4wp features list */
		$this->ga4wp_features_list = array(
			'0'  => array('Easy To Connect', 'One-click Google OAuth login connects your GA4 property in seconds — no manual gtag or GTM setup required.', false),
			'1'  => array('Audience, Acquisition & Behavior Reports', 'See who visits your site, where they come from, and what they do — right inside your WordPress dashboard.', false),
			'2'  => array('Connection Status & Diagnostics', 'A dedicated Status page checks your property, tracking setup, timezone/currency match and custom dimensions at a glance.', false),
			'3'  => array('Custom Dashboard Widgets', 'Reorder and toggle which report widgets show up on your Analytics Dashboard.', false),
			'4'  => array('One-Click Export', 'Download any dashboard chart or table as a PNG, PDF or CSV straight from its export menu.', false),
			'5'  => array('Facebook Pixel & Google Ads Conversion Tracking', 'Add your Pixel ID and Ads Conversion code and start tracking conversions with no code required.', false),
			'6'  => array('Regular Updates', 'Actively maintained to stay compatible with the latest GA4 and WordPress changes.', false),
			'7'  => array('Real-Time Reports', 'See active users, live page views, live visitor locations and active traffic sources as they happen.', true),
			'8'  => array('Form Tracking Reports', 'Automatically track form submissions, views and conversion rate across every form on your site.', true),
			'9'  => array('Video Tracking Reports', 'Monitor plays, average watch time and completion rate for embedded videos.', true),
			'10' => array('Conversion & Purchase Journey Reports', 'See WooCommerce revenue, checkout funnels and conversion paths tied directly to your GA4 data.', true),
			'11' => array('Product Performance & Subscriptions Reports', 'Understand your top-performing products and track recurring WooCommerce Subscriptions revenue.', true),
			'12' => array('Content Reports', 'Find your best (and worst) performing pages and posts by views, engagement and conversions.', true),
			'13' => array('Google Ads & Google Adsense Reports', 'Track ad spend performance and Adsense revenue side-by-side with the rest of your analytics.', true),
			'14' => array('5 Additional Pixel Integrations', 'Bing UET, TikTok, Pinterest, LinkedIn and Snapchat tracking — on top of the free Facebook Pixel and Google Ads integration.', true),
			'15' => array('Automatic Custom Dimensions', 'We register the GA4 custom dimensions your reports need — author, category, tag, post type, user role, form ID/name, user ID, conversion type and subscription data — directly on your property.', true),
			'16' => array('UTM / Campaign URL Builder', 'Build properly tagged campaign URLs with a live preview and a saved history panel for every past campaign.', true),
			'17' => array('Explore Reports', 'Build your own custom GA4 reports by combining any dimension and metric — table, bar, line or doughnut views — and save them for quick re-access.', true),
			'18' => array('Automated Email Reports', 'Get scheduled analytics summaries delivered straight to your inbox.', true),
			'19' => array('Search Console Reports', 'See organic search queries, monthly trend and device breakdown pulled straight from Search Console.', true),
			'20' => array('Campaign Performance Reports', 'Track sessions, engagement rate, conversions and revenue for every campaign by source/medium and by term.', true),
			'21' => array('Click Tracking Reports', 'See outbound link clicks and file downloads, broken down by destination, file name, and the page they happened on.', true),
		);
		/* Pro upsell tab definitions — shared with free dashboard view */
		$this->ga4wp_pro_tabs = [
			'realtime-pro' => [
				'title' => 'Real-Time Reports',
				'items' => [
					['timer',          'Active Users Right Now',  'See how many visitors are on your site at this exact moment.'],
					['pageview',       'Real-Time Page Views',    'Monitor which pages are being viewed in real time.'],
					['public',         'Live Visitor Locations',  'Country and city breakdown of currently active visitors.'],
					['travel_explore', 'Active Traffic Sources',  'Discover what sources are driving live traffic right now.'],
				],
			],
			'form_tracking-pro' => [
				'title' => 'Form Tracking Reports',
				'items' => [
					['assignment_turned_in', 'Form Submission Report', 'Track every form submission across your entire site.'],
					['trending_up',          'Form Conversion Rate',   'See which forms convert best and where users drop off.'],
					['compare_arrows',       'Form Views vs Submits',  'Compare how many users view each form versus complete it.'],
					['leaderboard',          'Top Performing Forms',   'Identify your highest-converting forms at a glance.'],
				],
			],
			'video_tracking-pro' => [
				'title' => 'Video Tracking Reports',
				'items' => [
					['play_circle',  'Video Play Report',      'Track how many times each video has been played.'],
					['timer',        'Video Watch Time',       'Understand average watch time per video.'],
					['check_circle', 'Video Completion Rate',  'See what percentage of viewers watch each video to the end.'],
					['leaderboard',  'Top Performing Videos',  'Identify which videos engage and retain users the most.'],
				],
			],
			'conversion-pro' => [
				'title' => 'Conversion Reports',
				'woo'   => true,
				'items' => [
					['inventory',      'Product Base Revenue Report',      'Revenue generated from individual products.'],
					['payments',       'Source Base Revenue Report',       'Income origins broken down by traffic source.'],
					['devices_other',  'Device Base Conversion Share',     'Revenue share and conversions by device category.'],
					['map',            'State/Region Base Revenue Report', 'Revenue by state or region.'],
				],
			],
			'journey-pro' => [
				'title' => 'Purchase Journey Reports',
				'woo'   => true,
				'items' => [
					['add_shopping_cart',      'Add to Cart Funnel',     'Track the path from product view to cart addition.'],
					['shopping_cart_checkout', 'Checkout Funnel',        'Identify exactly where customers drop off during checkout.'],
					['credit_card',            'Payment Method Report',  'See which payment methods your customers prefer most.'],
					['trending_up',            'Funnel Conversion Rate', 'Overall conversion rate from visitor to completed purchase.'],
				],
			],
			'performance-pro' => [
				'title' => 'Product Performance Reports',
				'woo'   => true,
				'items' => [
					['local_mall',  'Best Selling Products',   'Products that generate the most revenue over time.'],
					['category',    'Product Category Report', 'Revenue and traffic broken down by product category.'],
					['visibility',  'Product View Rate',       'Products most frequently viewed by your customers.'],
					['payments',    'Revenue Per Product',     'Average revenue contribution per individual product.'],
				],
			],
			'woo_subscriptions-pro' => [
				'title' => 'WooCommerce Subscriptions',
				'woo'   => true,
				'items' => [
					['payments',    'Monthly Recurring Revenue (MRR)', 'Track your MRR and ARR trends in a single view.'],
					['people_alt',  'Subscriber Lifecycle',            'Active, on-hold, churned, and expired subscriber counts.'],
					['autorenew',   'Churn & Renewal Rate',            'Monitor churn rates and renewal success over time.'],
					['show_chart',  'Subscription Revenue Trend',      'Monthly subscription revenue chart across six months.'],
				],
			],
			'content-pro' => [
				'title' => 'Content Reports',
				'items' => [
					['leaderboard', 'Best Performing Posts',  'Posts driving the most traffic and engagement.'],
					['category',    'Category Report',        'Traffic breakdown by post category.'],
					['sell',        'Tag Performance Report', 'Which tags attract the most visitors.'],
					['timer',       'Avg. Time on Content',  'How long users spend reading each content type.'],
				],
			],
			'search_console-pro' => [
				'title' => 'Search Console Reports',
				'items' => [
					['search',      'Search Queries Report',        'Search queries and landing pages driving organic Google Search traffic, with average position, impressions, clicks and CTR.'],
					['show_chart',  'Monthly Trend Report',         'Organic search performance broken down month by month.'],
					['devices',     'Device Breakdown Report',      'Organic search performance broken down by device category.'],
				],
			],
			'utm_campaign-pro' => [
				'title' => 'Campaign Performance Reports',
				'items' => [
					['travel_explore', 'Campaign by Source/Medium', 'Sessions, engagement rate, conversions and revenue broken down by campaign and traffic source/medium.'],
					['campaign',       'Campaign by Term',          'Sessions, engagement rate, conversions and revenue broken down by campaign and UTM term.'],
				],
			],
			'click_tracking-pro' => [
				'title' => 'Click Tracking Reports',
				'items' => [
					['open_in_new',   'Outbound Click Tracking', 'Outbound link clicks broken down by destination URL, link text, and the page the click happened on.'],
					['file_download', 'File Download Tracking',  'File downloads broken down by file name, file extension, and the page the download link was on.'],
				],
			],
			'googleAds-pro' => [
				'title' => 'Google Ads Reports',
				'items' => [
					['attach_money', 'Ad Group Cost Report',                'Ad costs broken down by ad group.'],
					['trending_up',  'Ad Group Success Report',             'Traffic contribution of each ad group.'],
					['search',       'Ad Search Query Reports',             'Queries that triggered the most ad clicks.'],
					['hub',          'Ad Distribution Network Performance', 'Ad clicks broken down by ad slot.'],
				],
			],
			'googleAdsense-pro' => [
				'title' => 'Google AdSense Reports',
				'items' => [
					['payments',  'AdSense Revenue by Page Title', 'Ad revenue generated per page.'],
					['ads_click', 'Ad Clicks by Page Title',       'Pages with the most ad clicks.'],
				],
			],
		];
	}
	/**
	 * Get the default dashboard tab visibility settings.
	 *
	 * The set of tabs enabled by default differs depending on whether
	 * WooCommerce is active (WooCommerce-only tabs such as conversion,
	 * journey, performance and woo_subscriptions are only included when
	 * WooCommerce is present).
	 *
	 * @return array Associative array of dashboard tab keys to their default boolean visibility.
	 */
	public function init_ga4wp_dashboard_defaults()
	{
		if (class_exists('WooCommerce')) {
			$defaults = array(
				'realtime' => true,
				'audience' => true,
				'acquisition' => true,
				'behavior' => true,
				'form_tracking' => true,
				'video_tracking' => true,
				'conversion' => true,
				'journey' => true,
				'performance' => true,
				'woo_subscriptions' => true,
				'googleAds' => true,
				'googleAdsense' => true,
				'content' => true,
				'search_console' => true,
				'utm_campaign' => true,
				'click_tracking' => true,
			);
		} else {
			$defaults = array(
				'realtime' => true,
				'audience' => true,
				'acquisition' => true,
				'behavior' => true,
				'form_tracking' => true,
				'video_tracking' => true,
				'googleAds' => true,
				'googleAdsense' => true,
				'content' => true,
				'search_console' => true,
				'utm_campaign' => true,
				'click_tracking' => true,
			);
		}
		return $defaults;
	}
	/**
	 * Get the shared singleton instance of this settings class, creating it on first call.
	 *
	 * @return GA4WP_Settings The singleton instance.
	 */
	public static function get_instance()
	{
		if (! self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Sanitize and validate the "advance settings" (pixel/conversion tracking) array before saving.
	 *
	 * @param array $settings Raw advance settings values, typically from $_POST.
	 * @return array The settings filtered through FILTER_VALIDATE_BOOLEAN for toggle fields and
	 *               FILTER_SANITIZE_FULL_SPECIAL_CHARS for code/text fields.
	 */
	public function parse_ga4wp_advance_settings($settings)
	{
		$bool  = array('filter' => FILTER_VALIDATE_BOOLEAN,          'flags' => FILTER_REQUIRE_SCALAR);
		$text  = array('filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS, 'flags' => FILTER_REQUIRE_SCALAR);
		$args = array(
			'google_measurement'          => $bool,
			'google_adword'               => $bool,
			'google_adword_code'          => $text,
			'google_adword_label'         => $text,
			'facebook_pixel'              => $bool,
			'facebook_pixel_code'         => $text,
			'bing_uet'                    => $bool,
			'bing_uet_code'               => $text,
			'tiktok_pixel'                => $bool,
			'tiktok_pixel_code'           => $text,
			'pinterest_pixel'             => $bool,
			'pinterest_pixel_code'        => $text,
			'linkedin_insight'            => $bool,
			'linkedin_insight_code'       => $text,
			'snapchat_pixel'              => $bool,
			'snapchat_pixel_code'         => $text,
		);
		$settings = filter_var_array($settings, $args);
		return $settings;
	}


	/**
	 * Sanitize and validate the authentication settings (tracking/property IDs, secrets, flags) before saving.
	 *
	 * @param array $settings Raw authentication settings values, typically from $_POST.
	 * @return array The settings filtered per-field with FILTER_SANITIZE_FULL_SPECIAL_CHARS for
	 *               IDs/secrets and FILTER_VALIDATE_BOOLEAN for the tracking/agreement flags.
	 */
	public function parse_ga4wp_auth_settings($settings)
	{
		$args = array(
			'tracking_id' => array(
				'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
				'flags'  => FILTER_REQUIRE_SCALAR,
			),
			'property_id'          => array(
				'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
				'flags'  => FILTER_REQUIRE_SCALAR,
			),
			'api_secret'          => array(
				'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
				'flags'  => FILTER_REQUIRE_SCALAR,
			),
			'manual_tracking'          => array(
				'filter' => FILTER_VALIDATE_BOOLEAN,
				'flags'  => FILTER_REQUIRE_SCALAR,
			),
			'agreement'          => array(
				'filter' => FILTER_VALIDATE_BOOLEAN,
				'flags'  => FILTER_REQUIRE_SCALAR,
			),
		);
		$settings = filter_var_array($settings, $args);
		return $settings;
	}

	/**
	 * Cast every value of a settings array to a boolean, used for the event tracking toggles.
	 *
	 * @param array $settings Raw settings values, typically from $_POST.
	 * @return array The settings with every value run through FILTER_VALIDATE_BOOLEAN.
	 */
	public function parse_ga4wp_bool_settings($settings)
	{
		$settings = filter_var_array($settings, FILTER_VALIDATE_BOOLEAN);
		return $settings;
	}

	/**
	 * Parse the posted dashboard tab visibility settings into booleans.
	 *
	 * Iterates over the known dashboard tab keys (from init_ga4wp_dashboard_defaults())
	 * and marks a tab enabled only when its posted value is the string 'yes', so that
	 * unchecked checkboxes (which are simply absent from $settings) resolve to false.
	 *
	 * @param array $settings Raw posted dashboard settings, typically from $_POST.
	 * @return array Associative array of dashboard tab keys to booleans.
	 */
	public function parse_ga4wp_dashboard_settings($settings)
	{
		$defaults = $this->init_ga4wp_dashboard_defaults();
		$parsed   = [];
		foreach (array_keys($defaults) as $key) {
			$parsed[$key] = isset($settings[$key]) && $settings[$key] === 'yes';
		}
		return $parsed;
	}

	/**
	 * Get the default dashboard date-range settings.
	 *
	 * @return array Defaults with report_view empty, report_frame set to 'Last 7 days', and
	 *               report_from/report_to computed as 7 days ago through yesterday.
	 */
	public function init_ga4wp_dash_defaults()
	{
		$defaults = array(
			'report_view'  => '',
			'report_frame' => 'Last 7 days',
			'report_from'  => date('Y-m-d', strtotime('-7 days')),
			'report_to'    => date('Y-m-d', strtotime('-1 day')),
		);
		return $defaults;
	}

	/**
	 * Sanitize the dashboard date-range settings before saving.
	 *
	 * @param array $settings Raw dashboard settings values, typically from $_POST.
	 * @return array The settings with every value run through FILTER_SANITIZE_FULL_SPECIAL_CHARS.
	 */
	public function parse_ga4wp_dash_settings($settings)
	{
		$settings = filter_var_array($settings, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		return $settings;
	}

	/**
	 * Get the default authentication settings.
	 *
	 * @return array Defaults with empty tracking/property/api-secret values, manual_tracking
	 *               disabled, and agreement accepted.
	 */
	public function init_ga4wp_auth_defaults()
	{
		$defaults = array(
			'trackind_id' => '',
			'property_id' => '',
			'api_secret' => '',
			'manual_tracking' => false,
			'agreement' => true,
		);
		return $defaults;
	}

	/**
	 * Get the default advance settings (third-party pixel/conversion tracking).
	 *
	 * @return array Defaults with every pixel/conversion integration disabled and its code field empty.
	 */
	public function init_ga4wp_advance_defaults()
	{
		$defaults = array(
			'facebook_pixel'          => false,
			'facebook_pixel_code'     => '',
			'google_adword'           => false,
			'google_adword_code'      => '',
			'google_adword_label'     => '',
		);
		return $defaults;
	}

	/**
	 * Get the default tracking settings.
	 *
	 * WooCommerce-only options (product single/archive tracking, on-hold conversion
	 * handling) are only included in the returned defaults when WooCommerce is active.
	 *
	 * @return array Associative array of tracking setting keys to their default values.
	 */
	public function init_ga4wp_track_defaults()
	{
		if (class_exists('WooCommerce')) {
			$defaults = array(
				'track_admin' => true,
				'track_admin_pages' => false,
				'not_track_pageviews' => false,
				'enhanced_link_attribution' => true,
				'product_single_track' => true,
				'product_archive_track' => true,
				'disable_on_hold_conversion' => true,
				'anonymize_ip' => true,
				'track_interest' => false,
				'not_track_user_id' => true,
				'track_ga_consent' => false,
				'google_measurement_api' => '',
				'google_analytics_debug_mode' => false,
			);
		} else {
			$defaults = array(
				'track_admin' => true,
				'track_admin_pages' => false,
				'not_track_pageviews' => false,
				'enhanced_link_attribution' => true,
				'anonymize_ip' => true,
				'track_interest' => false,
				'not_track_user_id' => true,
				'track_ga_consent' => false,
				'google_measurement_api' => '',
				'google_analytics_debug_mode' => false,
			);
		}
		return $defaults;
	}

	/**
	 * Get the default event tracking settings.
	 *
	 * Starts from a base set of always-available events, then merges in the
	 * WooCommerce-specific events when WooCommerce is active, and the
	 * WooCommerce Subscriptions events on top of that when WC_Subscriptions
	 * is active.
	 *
	 * @return array Associative array of event keys to their default enabled state (all true).
	 */
	public function init_ga4wp_events_defaults()
	{
		$defaults = array(
			'user_login'        => true,
			'user_login_errors' => true,
			'user_logout'       => true,
			'user_signup'       => true,
			'wrote_review'      => true,
			'commented'         => true,
			'log_error'         => true,
		);

		if (class_exists('WooCommerce')) {
			$defaults = array_merge($defaults, array(
				'viewed_signup_form'    => true,
				'viewed_shop'           => true,
				'viewed_product'        => true,
				'added_product'         => true,
				'removed_product'       => true,
				'changed_quantity'      => true,
				'viewed_cart'           => true,
				'wrong_coupon_applied'  => true,
				'applied_coupon'        => true,
				'removed_coupon'        => true,
				'begin_checkout'        => true,
				'filled_checkout_form'  => true,
				'added_payment_method'  => true,
				'added_shipping_method' => true,
				'order_failed'          => true,
				'processing_payment'    => true,
				'completed_purchase'    => true,
				'viewed_account'        => true,
				'viewed_order'          => true,
				'changed_password'      => true,
				'lost_password'         => true,
				'estimated_shipping'    => true,
				'order_cancelled'       => true,
				'order_refunded'        => true,
			));
		}
		return $defaults;
	}
}

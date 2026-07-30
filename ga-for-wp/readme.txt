=== TrueAna – True Analytics Dashboard ===
Contributors: passionatebrains
Donate link: https://trueana.com
Tags: google analytics, google analytics 4, ga4, woocommerce analytics, facebook pixel
Requires at least: 5.7
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 3.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect Google Analytics to WordPress in few clicks. Track all important WooCommerce and WordPress events with a real GA4 dashboard built into wp-admin.

== Description ==

Google Analytics 4 wasn't built with WordPress or WooCommerce in mind.

Getting real store and website data into GA4 usually means wiring up Google Tag Manager, hand-coding dataLayer pushes for every events, and then leaving your WordPress dashboard entirely just to check how everything performing.

**That's the gap TrueAna closes.**

[TrueAna](https://trueana.com/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) connects your site to Google Analytics 4 with a single OAuth sign-in, fires 35+ WordPress & WooCommerce events automatically with zero configuration, and puts real GA4 report data — the same numbers you'd see in analytics.google.com — directly inside your WordPress admin.

No Tag Manager. No custom dataLayer code. No switching tabs to check how your website is doing.

**Who's it for?**
* **Content sites & bloggers** — get audience, acquisition, and behavior reports plus GA4 dashboard widgets right on the WordPress home screen, no separate login required.
* **WooCommerce store owners** — see product views, cart abandonment, checkout drop-off, and revenue by traffic source without leaving wp-admin, and fire Facebook, Google Ads, TikTok(Pro), and Pinterest pixels(Pro) off the same events.
* **Agencies & freelancers** — connect a client's GA4 property once and hand them a dashboard they can actually read, instead of teaching them the GA4 interface.
* **Subscription businesses** — track the full [WooCommerce Subscriptions](https://trueana.com/woocommerce-subscription-reports/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) lifecycle (renewals, plan switches, churn) as GA4 events, and watch MRR and churn rate in a dedicated dashboard (Pro).
* **Publishers with multiple authors** — see which authors, categories, tags, and post types actually drive pageviews with [Content Analytics Reports](https://trueana.com/content-performance-reports/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt), instead of digging through GA4's generic page-path view (Pro).
* **Lead-gen & SaaS sites** — track form views and submissions per form, with conversion rate against the previous period, so you know which forms are actually producing leads (Pro).
* **Marketers running landing pages** — [track clicks](https://trueana.com/click-tracking-reports/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) on any link or button site-wide, from CTA buttons to outbound links, without touching Tag Manager (Pro).
* **Startups & early-stage founders** — get straight to traffic source, channel, and acquisition data from day one to see what's working, without setting up GA4 Explorations or waiting on an analyst.
* **Video-driven sites & course creators** — see plays, completion rate, and average watch depth per video across YouTube, Vimeo, and HTML5 embeds, so you know which videos actually hold attention (Pro).
* **Ad-funded content sites** — pull Google AdSense revenue and ad clicks by page title into the same dashboard as your traffic data, instead of cross-referencing two separate logins (Pro).
* **Paid traffic managers** — build and track UTM campaign links from inside wp-admin, then watch performance roll up in Acquisition Reports without a separate spreadsheet (Pro).
* **Multi-site consultants & in-house teams** — save [custom GA4 Explore-style reports](https://trueana.com/custom-report-builder/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) as pinned dashboard tabs and reorder them per site, so recurring client or stakeholder reviews take minutes, not a rebuild each time (Pro).

= How It Works =

TrueAna talks to Google's APIs on your behalf using OAuth 2.0. After you connect your Google account, the plugin uses your access token to:

1. **Send tracking events** to GA4 via the standard `gtag.js` snippet, and server-side via the GA4 Measurement Protocol when an API secret is configured.
2. **Pull report data** from the GA4 Data API and render it inside your WordPress admin dashboard.
3. **Manage custom dimensions** in GA4 through the Management API (Pro only).

No Analytics data passes through a third-party proxy server. Every Google Analytics Data API call goes directly from your WordPress site to Google.

= APIs Used =

* **Google Analytics Data API** — powers all dashboard reports inside WordPress (audience, acquisition, behavior, and all Pro report tabs).
* **GA4 Measurement Protocol** — server-side event delivery when an API secret is configured, so events still fire when JavaScript is blocked.
* **Google Analytics Management API v3** — Pro only; auto-creates custom dimensions in your GA4 property with a single click.
* **Google OAuth 2.0**  — secure sign-in; TrueAna requests only the Analytics read/write scopes it needs.
* **gtag.js (Google tag)** — Google's standard tracking snippet, loaded on your site's frontend to send events and pageviews to GA4 and Google Ads.
* **TrueAna Proxy Service** - The plugin uses a proxy service for OAuth authentication with Google services.

= What You Can Track =

* **GA4 connection** — one-click OAuth or manual Measurement ID + API Secret entry, User ID tracking, IP anonymization, Google Consent Mode v2, enhanced link attribution.
* **User activity** — login (with checkout vs. My Account context), login errors, logout, registration form views, new sign-ups, reviews, comments.
* **WooCommerce shopping journey** — every step from product view to purchase, refunds, and coupon usage (30+ events, all free — see the full list below).
* **Conversion pixels** — Facebook Pixel and Google Ads out of the box; Bing, TikTok, Pinterest, LinkedIn Insight, and Snapchat in Pro.
* **Subscriptions** — the complete WooCommerce Subscriptions lifecycle, from creation to cancellation (Pro).
* **Custom dimensions** — author, category, tag, post type, user role, form ID/name, subscription ID, and more, auto-registered in GA4 (Pro).

= Free Features =

**GA4 Connection**

* One-click OAuth sign-in or manual Measurement ID entry
* Supports GA4 property IDs (G-XXXXXXXX) and manual API Secret for server-side Measurement Protocol
* User ID tracking to follow logged-in users across sessions
* IP anonymization for GDPR compliance
* Google Consent Mode v2 — respects visitor consent before firing any tracking
* Enhanced link attribution

**Event Tracking**

* User login (with checkout vs. My Account context), login errors, logout
* Registration form view, new user sign-up
* Product review written, comment posted
* WooCommerce error logging

**WooCommerce Event Tracking — 30+ events, all free**

TrueAna tracks the complete WooCommerce shopping journey out of the box. Every event is individually toggled on/off from Settings:

* Shop archive / category page viewed with product impressions
* Product detail page viewed with price, variant, and category
* Item added to cart with quantity and price
* Item removed from cart
* Cart item quantity updated
* Cart page loaded with basket total
* Coupon successfully applied with discount value
* Failed coupon attempt with error message
* Coupon removed from cart
* Checkout page loaded with basket total
* Billing and shipping fields completed
* Shipping method selected at checkout
* Payment method selected at checkout
* Order submitted, awaiting payment
* Payment failed with order value
* Successful purchase with full order data (items, revenue, tax, shipping, coupons)
* Order moved to cancelled status
* Refund issued with refunded amount
* Shipping cost calculated in cart
* My Account page visited
* Order detail page visited
* Account password updated
* Password reset requested
* New WooCommerce account created

**Conversion Pixels — free**

* **Facebook Pixel** — PageView on all pages; ViewContent on product pages; AddToCart on cart events; Purchase on order completion
* **Google Ads Conversion** — conversion event with revenue value and transaction ID on order completion

**Built-in Analytics Dashboard — free tabs**

* **Audience Reports** — Users, new users, sessions, engagement rate, avg. session duration; breakdown by country, language, and device category
* **Acquisition Reports** — Active users by channel group, traffic source, medium, and screen resolution
* **Behavior Reports** — Top pages by traffic, time on page, age group, gender, and operating system
* **WordPress Dashboard Widgets** — Quick stats, overview chart, users by country, device, and language — visible right on the WP Dashboard home screen

= Pro Features =

> **[Upgrade to TrueAna Pro](https://trueana.com/pricing/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt)** to unlock subscription event tracking, 14 additional dashboard reports, custom dimensions, and five more ad pixel integrations — everything a growing store needs to move past basic pageviews.

**WooCommerce Subscriptions Event Tracking (Pro)**

Requires the WooCommerce Subscriptions plugin. TrueAna Pro tracks the complete subscription lifecycle — 10 events automatically fired via WooCommerce Subscriptions hooks:

* New subscription started at checkout
* Plan changed, upgraded, or downgraded
* Recurring payment failure
* Renewal payment declined
* Subscriber cancels the subscription
* Subscription reached its end date
* Subscription paused
* Free trial period ended
* Upcoming expiration flagged
* Prepaid period completed

Every subscription event includes custom dimension parameters — automatically populated once custom dimensions are set up.

**[Custom Dimensions](https://trueana.com/custom-dimensions/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) — Pro, 11 dimensions auto-created in GA4**

TrueAna Pro registers the following dimensions in your GA4 property with one click. No manual setup in the Google Analytics UI is needed:

* Author ID of the page or post writer
* Post or product category
* Post or product tag
* WordPress post type
* Current logged-in user role
* ID of the submitted form
* Name of the submitted form
* TrueAna persistent user identifier
* new / renewal / re-subscription
* WooCommerce Subscription ID
* Number of renewals for the subscription

These dimensions unlock the Pro dashboard reports for Content, Forms, and Subscriptions.

**[Advanced Pixel Integrations](https://trueana.com/pixel-ads-conversion-tracking/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) — Pro**

In addition to the free Facebook Pixel and Google Ads, Pro adds five more advertising platforms. Each pixel fires in sync with the same WooCommerce hooks as GA4, keeping revenue and product data consistent across every platform:

* **Bing UET Pixel** — Page load, ViewProduct, AddToCart, Purchase
* **TikTok Pixel** — PageView, ViewContent, AddToCart, Purchase
* **Pinterest Tag** — PageVisit, ViewCategory, AddToCart, Checkout
* **LinkedIn Insight Tag** — Site-wide page tracking + Purchase event
* **Snapchat Pixel** — PAGE_VIEW, VIEW_CONTENT, ADD_CART, PURCHASE

**Pro Dashboard Reports**

The following dashboard tabs show an upgrade prompt in the free version. Pro unlocks real GA4 data for each:

* **Real-Time Reports** — Active users right now, live page views, live visitor locations, active traffic sources
* **Form Tracking Reports** — Views, submissions, and conversion rate per form vs. the previous period (powered by `ga4wp_form_id` and `ga4wp_form_name` custom dimensions)
* **Video Tracking Reports** — Video plays, completion count, completion rate, and average watch depth per video title
* **WooCommerce Conversion Reports** — Total revenue, transactions, revenue per user, session conversion rate; breakdowns by product, traffic source, device, and state/region
* **Purchase Journey / Funnel Reports** — Product views → add to cart → checkout → purchase funnel by device category with previous-period comparison
* **Product Performance Reports** — Best-selling products ranked by revenue, product views, cart additions, and purchases
* **WooCommerce Subscriptions Dashboard** — Subscriber MRR/ARR trend, active/churned/on-hold subscriber counts, churn rate, renewal rate, and subscription revenue chart
* **Content Analytics Reports** — Top authors, categories, tags, and post types ranked by pageviews (powered by custom dimensions)
* **Google Ads Reports** — Ad group cost, ad group traffic, search query report, ad distribution network performance
* **Google AdSense Reports** — AdSense revenue by page title, ad clicks by page title

**Saved Explore Reports (Pro)**

Pin custom-built GA4 Explore-style reports as persistent dashboard tabs. Enable or disable individual saved reports and reorder all dashboard tabs via drag-and-drop.

**Scheduled Email Reports (Pro)**

Receive automated GA4 summary reports by email on a schedule you define — daily, weekly, or monthly — without logging into WordPress.

**UTM Builder (Pro)**

Built-in UTM parameter generator for building properly formatted campaign tracking URLs directly inside your WordPress dashboard.

= Free vs Pro at a Glance =

**Connection & Tracking**

* GA4 OAuth sign-in and manual Measurement ID — Free & Pro
* GA4 Measurement Protocol (server-side events) — Free & Pro
* User ID tracking, IP anonymization, Google Consent Mode v2 — Free & Pro
* Enhanced link attribution — Free & Pro

**Event Tracking**

* Login, logout, login errors, sign-up, registration form view — Free & Pro
* Write review, post comment, WooCommerce error log — Free & Pro
* 30+ WooCommerce GA4 events (view, cart, checkout, purchase, refund, etc.) — Free & Pro
* WooCommerce Subscriptions lifecycle events (10 events) — Pro only

**Conversion Pixels**

* Facebook Pixel (PageView, ViewContent, AddToCart, Purchase) — Free & Pro
* Google Ads conversion tracking — Free & Pro
* Bing UET, TikTok, Pinterest, LinkedIn Insight, Snapchat pixels — Pro only

**Custom Dimensions**

* Custom dimensions in GA4 (11 dimensions, one-click auto-create) — Pro only

**Dashboard Reports**

* Audience Reports (users, sessions, country, language, device) — Free & Pro
* Acquisition Reports (channels, sources, mediums) — Free & Pro
* Behavior Reports (top pages, time on page, age group, gender, OS) — Free & Pro
* WordPress Dashboard Widgets — Free & Pro
* [Real-Time Reports](https://trueana.com/realtime-reports/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) — Pro only
* [Form Tracking Reports](https://trueana.com/form-conversion-reports/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) — Pro only
* [Video Tracking Reports](https://trueana.com/video-performance-reports/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) — Pro only
* [WooCommerce Purchase Journey Funnel, Conversion & Revenue Reports](https://trueana.com/woocommerce-reports/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) — Pro only
* [Campaign Performance Reports](https://trueana.com/campaign-performance-reports/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) — Pro only
* [WooCommerce Subscriptions Dashboard](https://trueana.com/woocommerce-subscription-reports/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) — Pro only
* [Content Analytics Reports](https://trueana.com/content-performance-reports/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) (author, category, tag, post type) — Pro only
* [Google Ads Reports](https://trueana.com/google-ads-reports/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) — Pro only
* [Google AdSense Reports](https://trueana.com/google-adsense-reports/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) — Pro only

**Other Pro Features**

* [Custom Explore Reports](https://trueana.com/custom-report-builder/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) with [drag-and-drop tab ordering](https://trueana.com/drag-and-drop-dashboard-layout/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) — Pro only
* [Scheduled Email Reports (daily / weekly / monthly)](https://trueana.com/scheduled-email-reports/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) — Pro only
* [UTM Builder](https://trueana.com/utm-builder/?utm_source=WordPress.org&utm_medium=Referral&utm_campaign=ReadMe.txt) — Pro only

= Why TrueAna Instead Of... =

* **Site Kit by Google alternative** — Site Kit connects GA4 but leaves WooCommerce event tracking, custom dimensions, and subscription reporting to you. TrueAna fires 30+ WooCommerce events out of the box with nothing to configure in Tag Manager.
* **Tag Manager + GTM container setup alternative** — Skip building and maintaining triggers and variables by hand. TrueAna ships the WooCommerce and subscription event map already built and tested.
* **MonsterInsights or ExactMetrics or GA Google Analytics alternative** — TrueAna's free tier already includes 30+ WooCommerce events, Audience/Acquisition/Behavior dashboard reports, and full Google Consent Mode v2 support — no upgrade needed just to get compliant support, accurate tracking. Server-side Measurement Protocol delivery also means events still reach GA4 even when a visitor's browser blocks `gtag.js` due to some js errors, so your numbers hold up better than JS-only tracking. Pro is reserved for what genuinely needs it super powers to understand stats in more detailed way.
* **A self-hosted analytics plugin alternative** — Self-hosted tools write every pageview and other data straight into your WordPress database, so your site's own performance degrades as traffic and data grow, and large sites eventually need manual pruning just to keep the DB manageable. They also can't feed Google Ads, Facebook Ads, or GA4-based remarketing and lookalike audiences, so you lose the marketing usability of having sales and conversion data inside the ad platforms you actually spend on. If you want to move off Google Analytics entirely, that trade-off may be worth it — but TrueAna is built for the opposite goal: getting more out of the GA4 property, and the ad platform integrations, you already have. TrueAna doesn't store visitor data on your server at all; it reads and displays data live from your own GA4 account.

= Requirements =

* WordPress 5.7 or higher
* PHP 7.4 or higher
* A Google Analytics 4 property (G-XXXXXXXX)
* A Google account with access to your GA4 property

= Privacy =

TrueAna does not collect or store visitor data on your server beyond what WordPress and WooCommerce already store. Analytics data lives entirely in your Google Analytics account; the plugin reads it via the GA4 Data API solely to display inside your dashboard.

IP anonymization and Google Consent Mode v2 are available as one-click toggles. When Consent Mode is active the plugin sets a lightweight first-party cookie to carry the visitor's consent state into server-side Measurement Protocol requests if enabled.

== Installation ==

**Get connected in about 2 minutes**

1. Upload the plugin folder to `/wp-content/plugins/` or install directly from the WordPress Plugins screen.
2. Activate the plugin through **Plugins → Installed Plugins**.
3. Go to **TrueAna → Connect** and click **Sign in with Google**.
4. Complete the OAuth flow with a Google account that has access to your GA4 property.
5. Select your GA4 property from the dropdown and save.
6. Visit **TrueAna → Settings → Event Settings** to enable or disable individual events. All 30+ WooCommerce events are on by default — nothing else to configure.
7. (Pro) Visit **TrueAna → Settings → Custom Dimensions** and click **Create Missing Dimensions** to register all 11 custom dimensions in GA4 in one click.

Prefer not to use OAuth? Enter a Measurement ID and API Secret manually under **TrueAna → Connect → Manual Setup** — see the FAQ below.

== Frequently Asked Questions ==

= Do I need a GA4 property? =

Yes. TrueAna works exclusively with Google Analytics 4 (GA4). 

= Can I connect without the OAuth sign-in? =

Yes. Enter a Measurement ID (G-XXXXXXXX) and an API Secret manually under **TrueAna → Connect → Manual Setup**. Manual mode delivers events server-side via the GA4 Measurement Protocol. The analytics dashboard requires OAuth because the GA4 Data API needs a valid user access token.

= Is WooCommerce event tracking free? =

Yes. All 30+ WooCommerce GA4 events — including product views, add to cart, checkout steps, purchase, refunds, coupons, and more — are included in the free version. WooCommerce Subscriptions lifecycle events (10 additional events) require Pro.

= Which pixels are free? =

Facebook Pixel (PageView, ViewContent, AddToCart, Purchase) and Google Ads conversion tracking are included in the free version. Bing UET, TikTok, Pinterest, LinkedIn Insight, and Snapchat pixels are Pro features.

= What dashboard reports are available for free? =

The free version includes real GA4 data for three report sections: Audience, Acquisition, and Behavior. All other dashboard tabs — Real-Time, Form Tracking, Video Tracking, Conversion, Journey, Performance, Subscriptions, Content, Google Ads, and AdSense — show an upgrade prompt in the free version and are unlocked with Pro.

= Does Pro track WooCommerce Subscriptions renewals? =

Yes. TrueAna Pro integrates with the WooCommerce Subscriptions plugin and tracks 10 lifecycle events. Each event includes the subscription ID, renewal count, and a conversion type parameter (new / renewal / re-subscription) so you can segment new versus recurring revenue directly in GA4.

= What are custom dimensions and why do I need them? =

Custom dimensions extend GA4 events with extra parameters — such as the post author, product category, form name, or subscription ID. TrueAna Pro registers 11 custom dimensions in your GA4 property automatically and populates them on every relevant event. They are required for the Content Analytics, Form Tracking, and Subscriptions dashboard reports.

= Can I choose which events to track? =

Yes. Every event has an individual on/off toggle under **TrueAna → Settings → Event Settings**. Disable any event you do not need.

= Does the plugin slow down my site? =

No. The `gtag.js` snippet is the same script Google provides natively and is loaded asynchronously. Server-side Measurement Protocol calls happen after the response is sent to the visitor. Dashboard reports are fetched only in the WordPress admin and never affect the frontend.

= Is it GDPR compliant? =

TrueAna includes IP anonymization and Google Consent Mode v2 support. When Consent Mode is enabled, tracking fires in a cookieless mode until the visitor grants consent. Full GDPR compliance depends on your overall consent management setup — TrueAna does not replace a dedicated Cookie Consent plugin.

= Where do I find my GA4 Property ID? =

In Google Analytics go to **Admin → Property Settings**. The Property ID is a number (e.g., `123456789`). After OAuth sign-in TrueAna also lists available properties in a dropdown so you can select it directly.

= Does it require Google Tag Manager? =

No. TrueAna loads `gtag.js` and fires all events directly — you don't need a Tag Manager container, triggers, or variables set up for WooCommerce or subscription tracking to work.

= Will TrueAna conflict with Site Kit by Google or another analytics plugin? =

TrueAna manages its own OAuth connection and its own copy of `gtag.js`. Running it alongside another plugin that also injects `gtag.js` can lead to duplicate pageview counts in GA4, so we recommend using TrueAna as your single GA4 connection on the site.


== Screenshots ==

1. Easy Auto Connect — link your Google Analytics account in a few clicks, no manual Measurement ID required.
2. Select your GA4 property and data stream after linking your Google account.
3. Advanced Integration — enable Facebook Pixel and Google Ads Conversion Tracking alongside GA4.
4. Overview report — traffic trend for Users and New Users over the selected date range.
5. At-a-glance dashboard stats — Users, New Users, Sessions, Engagement Rate, Pageviews, and more.
6. Users based on acquisition channel — Direct, Organic Search, Organic Social, Referral, and AI Assistant traffic.
7. Users based on medium — breakdown of none, organic, referral, social, and AI-assistant traffic.
8. Users based on source — top referring sources.
9. Device-based users report — Desktop, Mobile, and Tablet breakdown.
10. Operating system report — Windows, Macintosh, iOS, Android, Linux, and Chrome OS usage.
11. Users based on screen size — most common screen resolutions among visitors.
12. Country-based users report — top countries by visitor count.
13. Language-based users report — top languages among visitors.
14. General tracking settings — control admin tracking, pageview tracking, and enhanced link attribution.
15. WooCommerce event settings — fine-tune product impression and conversion tracking.
16. GDPR & privacy controls — IP anonymization, Consent Mode, and user ID tracking options.

== Changelog ==

= 3.0.0 =
* New: Separate Audience, Acquisition, and Behavior report dashboards.
* New: Period-over-period comparison for all stats, so you can spot trends at a glance.
* New: Status tab that shows your current analytics settings and flags any discrepancies.
* New: Improved UI and documentation to help you find the data you need, faster.
* New: Faster, simpler GA4 integration — create a new GA4 property directly from your dashboard.
* Improved: General security hardening across the plugin.
* Improved: Google Consent Mode v2 support with a first-party cookie for server-side consent propagation.
* Improved: GA4 Measurement Protocol server-side event delivery with debug mode support.
* Improved: All settings save handlers now require `manage_options` capability for stronger authorization checks.
* Improved: Premium JavaScript split into a separate, conditionally-loaded file.
* Fixed: Base class load-order error affecting the premium Google Management API extension.
* Visit [trueana.com](https://trueana.com) for full feature details.

= 2.10.0 =
Latest Freemius SDK Update
Updated some minor changes

= 2.9.1 =
Fixing the css issue
 
= 2.9.0 =
Latest Freemius SDK Update

= 2.8.0 =
Tested with latest v6.8 of WP
Updated some minor changes

= 2.7.0 =
Latest Freemius SDK Update

= 2.6.0 =
Latest Freemius SDK Update
Minor tracking issues resolved

= 2.5.1 =
Resolved Measurement Api issue for WooCommerce
Fixed other minor issues

= 2.5.0 =
Tested with WC v9.2.3
Latest Freemius SDK Update
Fixing Product Impression Issues

= 2.4.0 =
Tested with WordPress v6.6
Tested with WC v9.1.4
Latest Freemius SDK Update

= 2.3.2 =
Latest Freemius SDK Update
Minor UI update

= 2.3.1 =
Analytics Dashboard bug fix
Latest Freemius SDK Update

= 2.3.0 =
Added more dashboard features
Removed support for Universal Analytics
Removed support for Google Optimize
Tested with WordPress v6.5

= 2.2.2 =
Tested with WordPress v6.4.3
Tested with WC v8.6.1
Latest Freemius SDK Update

= 2.2.1 =
Tested with WordPress v6.4
Latest Freemius SDK Update

= 2.2.0 =
Removing support for connecting UA property in auto mode
Latest Freemius SDK 2.5.10

= 2.1.2 =
Latest Freemius SDK 2.5.5

= 2.1.1 =
Minor Security Update

= 2.1.0 =
Latest Freemius SDK 2.5.3

= 2.0.0 =
Added in-site dashboard
Added support for Facebook pixel, Google optimize and Google Ads

== Upgrade Notice ==

= 3.0.0 =
We're now TrueAna! A drastic upgrade to tracking accuracy, overall features, and the number of prebuilt reports. This update may require you to reconnect your Google Analytics connection. Visit TrueAna.com for full details of features.
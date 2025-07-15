<?php

/*
Plugin Name: GA4WP 
Plugin URI: https://ga4wp.com/
Description: Google Analytics Dashboard for WordPress Plugin by GA4WP is lightweight, easy to connect and comes with plenty of great features.
Author: Passionate Brains
Version: 2.9.1
WC requires at least: 3.7.0
WC tested up to: 9.8.1
Author URI: https://ga4wp.com/
License: GPLv2 or later
*/
/* initiating plugin */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( function_exists( 'gfw_fs' ) ) {
    gfw_fs()->set_basename( false, __FILE__ );
} else {
    if ( !function_exists( 'gfw_fs' ) ) {
        function gfw_fs() {
            global $gfw_fs;
            if ( !isset( $gfw_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $gfw_fs = fs_dynamic_init( array(
                    'id'             => '7658',
                    'slug'           => 'ga-for-wp',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_09b4dbf1e09214afa3b86d9150d8a',
                    'is_premium'     => false,
                    'premium_suffix' => 'pro',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'trial'          => array(
                        'days'               => 3,
                        'is_require_payment' => false,
                    ),
                    'menu'           => array(
                        'slug'       => 'ga4wp_pro_plugin_options',
                        'first-path' => 'admin.php?page=ga4wp_pro_plugin_options',
                        'support'    => false,
                    ),
                    'is_live'        => true,
                ) );
            }
            return $gfw_fs;
        }

        // Init Freemius.
        gfw_fs();
        // Signal that SDK was initiated.
        do_action( 'gfw_fs_loaded' );
    }
    /* Defining some of constant which will be helpful throughout */
    if ( !defined( 'GA4WP_BASENAME' ) ) {
        define( 'GA4WP_BASENAME', plugin_basename( __FILE__ ) );
    }
    if ( !defined( 'GA4WP_DIR' ) ) {
        define( 'GA4WP_DIR', plugin_dir_path( __FILE__ ) );
    }
    if ( !defined( 'GA4WP_URL' ) ) {
        define( 'GA4WP_URL', plugin_dir_url( __FILE__ ) );
    }
    if ( !defined( 'GA4WP_SITE_URL' ) ) {
        define( 'GA4WP_SITE_URL', site_url() );
    }
    if ( !defined( 'GA4WP_SITE_DOMAIN' ) ) {
        define( 'GA4WP_SITE_DOMAIN', trim( str_ireplace( array('http://', 'https://'), '', trim( GA4WP_SITE_URL, '/' ) ) ) );
    }
    if ( !defined( 'GA4WP_PREFIX' ) ) {
        define( 'GA4WP_PREFIX', 'GA4WP_' );
    }
    if ( !defined( 'GA4WP_VERSION' ) ) {
        define( 'GA4WP_VERSION', '2.9.1' );
    }
    add_action( 'before_woocommerce_init', function () {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    } );
    /* Definining main class */
    if ( !class_exists( 'GA4WP' ) ) {
        class GA4WP {
            private static $instance = null;

            private $settings;

            private $main;

            private $admin;

            private $auth;

            public static function get_instance() {
                if ( !self::$instance ) {
                    self::$instance = new self();
                }
                return self::$instance;
            }

            private function __construct() {
                if ( $this->ga4wp_compat_checker() ) {
                    $this->includes();
                    $this->init();
                }
            }

            /*loads other support classes*/
            private function includes() {
                /* Settings class. */
                require_once GA4WP_DIR . 'main/class-ga4wp-settings.php';
                /* Include core class. */
                require_once GA4WP_DIR . 'main/class-ga4wp-main.php';
                /* Include admin class. */
                require_once GA4WP_DIR . 'main/class-ga4wp-admin.php';
                /* Include auth class. */
                require_once GA4WP_DIR . 'main/class-ga4wp-auth.php';
            }

            /* init support classes*/
            private function init() {
                $this->settings = new GA4WP_Settings();
                $this->main = new GA4WP_Main();
                $this->admin = new GA4WP_Admin();
                $this->auth = new GA4WP_Auth();
            }

            /* returning setting class object */
            public function settings() {
                return $this->settings;
            }

            /* returning main class object */
            public function main() {
                return $this->main;
            }

            /* returning admin class object */
            public function admin() {
                return $this->admin;
            }

            /* returning auth class object */
            public function auth() {
                return $this->auth;
            }

            /* checking compatibility for plugin to get activated and working */
            public function ga4wp_compat_checker() {
                global $wp_version;
                $error = '';
                $nwpv = implode( '.', array_slice( explode( '.', $wp_version ), 0, 2 ) );
                #getiing wp version upto 2 decimal points
                # php version requirements
                if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
                    $error = 'GA4WP: Google Analytics for Wordpress requires PHP 7.0 or higher. You’re still on ' . PHP_VERSION;
                }
                # wp version requirements
                if ( $nwpv < '5.0' ) {
                    $error = 'GA4WP: Google Analytics for Wordpress requires WP 5.0 or higher. You’re still on ' . $wp_version;
                }
                if ( is_plugin_active( plugin_basename( __FILE__ ) ) && !empty( $error ) || !empty( $error ) ) {
                    if ( isset( $_GET['activate'] ) ) {
                        unset($_GET['activate']);
                    }
                    add_action( 'admin_notices', function () use($error) {
                        echo '<div class="notice notice-error is-dismissible"><p><strong>' . $error . '</strong></p></div>';
                    } );
                    return false;
                } else {
                    return true;
                }
            }

        }

    }
    add_action( 'plugins_loaded', array('GA4WP', 'get_instance') );
}
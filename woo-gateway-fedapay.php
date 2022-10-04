<?php
/**
 * Plugin Name: FedaPay Gateway for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/woo-gateway-fedapay/
 * Description: Take credit card and mobile money payments on your store using Fedapay.
 * Author: Fedapay
 * Author URI: https://fedapay.com/
 * Requires at least: 4.4
 * Tested up to: 5.8
 * WC requires at least: 2.6
 * WC tested up to: 5.5.2
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: woo-gateway-fedapay
 * Domain Path: /languages
 * Version: 0.3.4
 *
 */

if (! defined('ABSPATH') ) {
    exit;
}

define( 'WC_FEDAPAY_GATEWAY_VERSION', '0.3.4' );

function wc_fedapay_gateway() {
    static $plugin;

    if ( ! isset( $plugin ) ) {
        require_once(plugin_dir_path(__FILE__) . 'includes/class-wc-fedapay-plugin.php');

        $plugin = new WC_Fedapay_Plugin( __FILE__, WC_FEDAPAY_GATEWAY_VERSION );
        $plugin->init();
    }

    return $plugin;
}

wc_fedapay_gateway()->init();

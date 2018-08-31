<?php
/**
 * Plugin Name:     Woocommerce Gateway Fedapay
  * Plugin URI: https://wordpress.org/plugins/woocommerce-gateway-fedapay/
 * Description: Take credit card payments on your store using Fedapay.
 * Author: WooCommerce
 * Author URI: https://fedapay.com/
 * Requires at least: 4.4
 * Tested up to: 4.9
 * WC requires at least: 2.6
 * WC tested up to: 3.3
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:     woocommerce-gateway-fedapay

 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Woocommerce_Gateway_Fedapay
 */

if ( ! defined( 'ABSPATH' ) ) {
 	exit;
 }

 // Load the plugin.
 require plugin_dir_path( __FILE__ ) . 'includes/class-wc-fedapay.php';

 /**
  * Begins execution of the plugin.
  *
  * Since everything within the plugin is registered via hooks,
  * then kicking off the plugin from this point in the file does
  * not affect the page life cycle.
  *
  * @since    0.1.0
  */
//  function run_woocommerce_gateway_fedapay() {
//  	WC_Fedapay_Gateway::get_instance();
//  }
//  run_woocommerce_gateway_fedapay();

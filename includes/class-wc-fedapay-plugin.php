<?php

/**
* Main class
*/

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

class WC_Fedapay_Plugin
{
    /**
     * Filepath of main plugin file.
     *
     * @var string
     */
    public $file;

    /**
     * Plugin version.
     *
     * @var string
     */
    public $version;

    public function __constructor($file, $version)
    {
        $this->file = $file;
        $this->version = $version;
    }

    /**
     * Init plugin
     */
    public function init()
    {
        die('init');
        // Require Client
        require_once(plugin_dir_path( $this->file ) . 'includes/class-wc-fedapay-gateway.php');

        // Load gateway class
        add_filter('woocommerce_payment_gateways', array( $this, 'add_fedapay_gateway_class' ));

        // Register activation hook
        register_activation_hook($this->file, array( $this, 'woocommerce_addon_activate' ) );

        // Load translations
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain' ) );
    }

    /**
     * Add FedaPay Gateway to the liste of WooCommerce gateways
     */
    public function add_fedapay_gateway_class($gateways)
    {
        $gateways[] = 'WC_Fedapay_Gateway';

        return $gateways;
    }

    /**
     * Check if this Wordpress installation support FedaPay dependencies
     */
    public function woocommerce_addon_activate()
    {
        if (!function_exists('curl_exec')) {
            wp_die('<pre>This plugin requires PHP CURL library installled in order to be activated </pre>');
        }
    }

    /**
     * Load translations
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            'woocommerce-gateway-fedapay',
            false,
            basename( plugin_dir_path( dirname( $this->file ) ) ) . '/languages/'
        );
    }
}

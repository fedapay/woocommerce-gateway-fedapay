<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
* Main class
*/

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

class WC_Fedapay_Block_Checkout extends AbstractPaymentMethodType
{
    private $gateway;

    protected $name = 'woo_gateway_fedapay'; // your payment gateway name

    public function initialize()
    {
      $this->gateway = new WC_Fedapay_Gateway();
      $this->settings = $this->gateway->settings;
    }

    public function is_active()
    {
      return $this->gateway->is_available();
    }

    public function get_supported_features(): array
    {
      return $this->gateway->supports;
    }

    public function get_payment_method_script_handles()
    {
        $asset_path = plugin_dir_path(__FILE__) . '/build/index.asset.php';
        $version = WC_FEDAPAY_GATEWAY_VERSION;
        $dependencies = array();

        if ( file_exists( $asset_path ) ) {
            $asset = require $asset_path;

            $version = is_array( $asset ) && isset( $asset['version'] )
                ? $asset['version']
                : $version;

            $dependencies = is_array( $asset ) && isset( $asset['dependencies'] )
                ? $asset['dependencies']
                : $dependencies;
        }

        wp_register_script(
            'wc-gateway-fedapay-blocks-integration',
            wc_fedapay_gateway()->plugin_url . 'build/index.js',
            $dependencies,
            $version,
            true
        );

        if ( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( 'wc-gateway-fedapay-blocks-integration', 'woo-gateway-fedapay', WC_FEDAPAY_PLUGIN_FILE_PATH. 'languages/' );
        }

        return [ 'wc-gateway-fedapay-blocks-integration' ];
    }

    public function get_payment_method_data()
    {
        return [
            'title' => $this->gateway->method_title,
            'logo_url' => $this->settings['icon_url'],
            'description' => $this->gateway->method_description,
            'supports' => array_filter( $this->get_supported_features(), array( $this->gateway, 'supports' ) )
        ];
    }
}

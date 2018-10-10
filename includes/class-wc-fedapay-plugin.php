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

    /**
     * Notice .
     *
     * @var string
     */
    public $error_message;

    public function __construct($file, $version)
    {
        $this->file = $file;
        $this->version = $version;
    }

    /**
     * Load gateway class
     */
    protected function _load_gateway_class()
    {
        require_once(plugin_dir_path($this->file) . 'includes/class-wc-fedapay-gateway.php');
    }

    /**
     * Load translations
     */
    protected function _load_plugin_textdomain()
    {
        load_plugin_textdomain(
            'woo-gateway-fedapay',
            false,
            dirname(plugin_basename($this->file)) . '/languages/'
        );
    }

    /**
     * Check plugin dependencies
     */
    protected function _check_dependencies()
    {
        if (! class_exists( 'WooCommerce' )) {
            throw new Exception(__('WooCommerce FedaPay Gateway requires WooCommerce to be activated', 'woo-gateway-fedapay'));
        }

        if (version_compare(WooCommerce::instance()->version, '2.5', '<')) {
            throw new Exception(__('WooCommerce FedaPay Gateway requires WooCommerce version 2.5 or greater', 'woo-gateway-fedapay'));
        }

        if (! function_exists('curl_init')) {
            throw new Exception(__('WooCommerce FedaPay Gateway requires cURL to be installed on your server', 'woo-gateway-fedapay'));
        }
    }

    /**
     * Init plugin
     */
    public function init()
    {
        // Bootstrap
        add_action( 'plugins_loaded', array( $this, 'bootstrap' ) );
    }

    /**
     * Bootstrap plugin
     */
    public function bootstrap()
    {
        try {
            $this->_load_plugin_textdomain();
            $this->_check_dependencies();
            $this->_load_gateway_class();

            // Load gateway class
            add_filter('woocommerce_payment_gateways', array( $this, 'add_fedapay_gateway_class' ));
        } catch (Exception $e) {
            $this->error_message = $e->getMessage();

            add_action('admin_notices', array( $this, 'show_bootstrap_warning' ));
        }
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
     * Show warnings
     */
    public function show_bootstrap_warning()
    {
        if (! empty( $this->error_message )) {
            ?>
            <div class="notice notice-warning is-dismissible ppec-dismiss-bootstrap-warning-message">
                <p>
                    <strong><?php echo esc_html( $this->error_message ); ?></strong>
                </p>
            </div>
            <?php
        }
    }
}

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
     * Plugin url.
     *
     * @var string
     */
    public $plugin_url;

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

        $this->plugin_url = trailingslashit( plugin_dir_url( $this->file ) );
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
        if (! class_exists('WooCommerce')) {
            throw new Exception(__('FedaPay Gateway for WooCommerce requires WooCommerce to be activated', 'woo-gateway-fedapay'));
        }

        if (version_compare(WooCommerce::instance()->version, '2.5', '<')) {
            throw new Exception(__('FedaPay Gateway for WooCommerce requires WooCommerce version 2.5 or greater', 'woo-gateway-fedapay'));
        }

        if (! function_exists('curl_init')) {
            throw new Exception(__('FedaPay Gateway for WooCommerce requires cURL to be installed on your server', 'woo-gateway-fedapay'));
        }
    }

    /**
     * Init plugin
     */
    public function init()
    {
        // Bootstrap
        add_action('plugins_loaded', array( $this, 'wc_fedapay_update_db_check' ));
        add_action('plugins_loaded', array( $this, 'bootstrap' ));
        add_filter( 'plugin_action_links_' . plugin_basename( $this->file ), array( $this, 'plugin_action_links' ) );

        add_action('wp_ajax_wc_fedapay_gateway_dismiss_notice_message', array( $this, 'ajax_dismiss_notice' ));
    }

    public function wc_fedapay_update_db_check()
    {
        if ( get_site_option( 'wc_fedapay_db_version' ) != WC_FEDAPAY_GATEWAY_VERSION ) {
            $this->install();
        }
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
            delete_option('wc_fedapay_gateway_bootstrap_warning_message');
        } catch (Exception $e) {
            update_option('wc_fedapay_gateway_bootstrap_warning_message', $e->getMessage());

            add_action('admin_notices', array( $this, 'show_bootstrap_warning' ));
        }
    }

    /**
     * Create table to store transactions made with FedaPay
     */

    public function install()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wc_fedapay_orders_transactions';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
            fedapay_orders_transactions_id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            transaction_id int(11) NOT NULL,
            order_id int(11) NOT NULL,
            hash varchar(255) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (fedapay_orders_transactions_id),
            KEY order_id (order_id),
            KEY transaction_id (transaction_id)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        add_option( 'wc_fedapay_db_version', WC_FEDAPAY_GATEWAY_VERSION );
    }

    /**
     * Add FedaPay Gateway to the list of WooCommerce gateways
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
        $dependencies_message = get_option('wc_fedapay_gateway_bootstrap_warning_message', '');

        if (! empty($dependencies_message) && 'yes' !== get_option('wc_fedapay_gateway_bootstrap_warning_message_dismissed', 'no' )) {
            ?>
            <div class="notice notice-warning is-dismissible wc-fedapay-gateway-dismiss-bootstrap-warning-message">
                <p>
                    <strong><?php echo esc_html($dependencies_message); ?></strong>
                </p>
            </div>
            <script>
            ( function( $ ) {
                $( '.wc-fedapay-gateway-dismiss-bootstrap-warning-message' ).on( 'click', '.notice-dismiss', function() {
                    jQuery.post( "<?php echo esc_url(admin_url('admin-ajax.php')); ?>", {
                        action: "wc_fedapay_gateway_dismiss_notice_message",
                        dismiss_action: "wc_fedapay_gateway_dismiss_bootstrap_warning_message",
                        nonce: "<?php echo esc_js(wp_create_nonce('wc_fedapay_gateway_dismiss_notice')); ?>"
                    } );
                } );
            } )( jQuery );
            </script>
            <?php

        }
    }

    /**
     * Dismiss ajax notices
     */
    public function ajax_dismiss_notice()
    {
        if (empty($_POST['dismiss_action'])) {
            return;
        }

        check_ajax_referer('wc_fedapay_gateway_dismiss_notice', 'nonce');
        switch ($_POST['dismiss_action']) {
            case 'wc_fedapay_gateway_dismiss_bootstrap_warning_message':
                update_option('wc_fedapay_gateway_bootstrap_warning_message_dismissed', 'yes');
                break;
        }
        wp_die();
    }


    /**
     * Add relevant links to plugins page.
     *
     * @since 1.2.0
     *
     * @param array $links Plugin action links
     *
     * @return array Plugin action links
     */
    public function plugin_action_links( $links )
    {
        $plugin_links = array();

        if ( function_exists( 'WC' ) ) {
            $setting_url = $this->get_admin_setting_link();
            $plugin_links[] = '<a href="' . esc_url( $setting_url ) . '">' . esc_html__( 'Settings', 'woo-gateway-fedapay' ) . '</a>';
        }

        $plugin_links[] = '<a href="https://docs.fedapay.com/plugins/woocommerce" target="_blank">' . esc_html__( 'Docs', 'woo-gateway-fedapay' ) . '</a>';

        return array_merge( $plugin_links, $links );
    }


    /**
     * Link to settings screen.
     */
    public function get_admin_setting_link()
    {
        return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo_gateway_fedapay' );
    }
}

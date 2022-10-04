<?php

/**
* Main class
*/

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

class WC_Fedapay_Gateway extends WC_Payment_Gateway
{
    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    public function __construct()
    {
        $this->id = 'woo_gateway_fedapay';
        $this->has_fields = false;
        $this->method_title = __('FedaPay', 'woo-gateway-fedapay');
        $this->order_button_text = __('Continue to payment', 'woo-gateway-fedapay');
        $this->method_description = __('FedaPay Payment Gateway Plug-in for WooCommerce', 'woo-gateway-fedapay');

        $this->supports = ['products'];
        $this->currencies = ['XOF', 'GNF', 'EUR'];

        // Method for loading fedapay-php-lib
        $this->get_fedapay_sdk();

        // Method for loading all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        if (empty($this->settings['icon_url'])) {
            $this->update_option('icon_url', plugins_url('../assets/img/fedapay.svg', __FILE__));
        }

        // Turn these settings into variables we can use
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        $this->set_icon();

        // Setup FedaPay SDK
        $this->setupFedaPaySdk($this->testmode, $this->fedapay_testsecretkey, $this->fedapay_livesecretkey);

        // Lets check for SSL
        add_action('admin_notices', array( $this, 'do_ssl_check' ));
        add_action('admin_notices', array( $this, 'currency_check' ));

        // Save settings
        if (is_admin()) {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        }

        add_action( 'woocommerce_api_'. strtolower(get_class($this)), array( $this, 'check_order_status' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
    }

    /**
     * Set WC_Fedapay_Gateway icon variable
     */
    private function set_icon()
    {
        if (filter_var( $this->icon_url, FILTER_VALIDATE_URL ) !== false) {
            $url = $this->icon_url;
        } else {
            $url = wp_get_attachment_url( $this->icon_url );
        }

        $this->icon = $this->append_url_version( $url );
    }

    /**
     * Append version to a specific url
     */
    private function append_url_version($url)
    {
        $url = trim($url);
        if (strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }

        return $url .= 'v=' . wc_fedapay_gateway()->version;
    }

    /**
     * Enqueues admin scripts.
     */
    public function admin_scripts()
    {
        // Image upload.
        wp_enqueue_media();

        wp_enqueue_script( 'woocommerce_fedapay_admin', wc_fedapay_gateway()->plugin_url . 'assets/js/wc-fedapay-admin.js', array( 'jquery' ), wc_fedapay_gateway()->version, true );
    }

    /**
     * Enqueues payment scripts.
     */
    public function payment_scripts()
    {
        $public_key = $this->is_true($this->testmode) ? $this->fedapay_testpublickey : $this->fedapay_livepublickey;

        $fedapay_params = array(
            'public_key'    => $public_key,
        );

        wp_register_script( 'fedapay_checkout_js', 'https://cdn.fedapay.com/checkout.js', '', '1.1.2', true );
        wp_register_script( 'woocommerce_fedapay', wc_fedapay_gateway()->plugin_url . 'assets/js/wc-fedapay.js', array( 'jquery', 'fedapay_checkout_js' ), wc_fedapay_gateway()->version, true );

        wp_localize_script( 'woocommerce_fedapay', 'wc_fedapay_params', apply_filters( 'wc_fedapay_params', $fedapay_params ) );
        wp_enqueue_script( 'woocommerce_fedapay' );
    }

    /**
     * Init fedapay sdk
     */
    private function get_fedapay_sdk()
    {
        if (! class_exists('Fedapay\Fedapay')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'vendor/fedapay-php/init.php';
        }
    }

    /**
     * Setup FedaPay SDK
     */
    private function setupFedaPaySdk($test_mode, $test_sk, $live_sk)
    {
        if ($test_mode == 'yes') {
            \FedaPay\FedaPay::setApiKey($test_sk);
            \FedaPay\FedaPay::setEnvironment('sandbox');
        } else {
            \FedaPay\FedaPay::setApiKey($live_sk);
            \FedaPay\FedaPay::setEnvironment('live');
        }
    }

    /**
     * Verify if provided currency is supported by FedaPay
     * @param string $currency
     * @return bool
     */
    private function isValideCurrency($currency)
    {
        return in_array($currency, $this->currencies);
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields =  include plugin_dir_path(__FILE__) . '/settings-fedapay.php';
    }

    /**
     * To make sure that the currency used one the store
     * is the one we actually support
     */
    public function currency_check()
    {
        $currency = get_woocommerce_currency();

        if (!$this->isValideCurrency($currency)) {
            echo "<div class=\"error\"><p>". sprintf(__('<strong>%s</strong> does not support the currency you are currently using. Please set the currency of your shop on XOF (FCFA) <a href="%s">here.</a>', 'woo-gateway-fedapay'), $this->method_title, admin_url('admin.php?page=wc-settings&tab=general')),"</p></div>";
        }
    }

    /**
     * Store each order with its transaction id
     */
    public function addOrderTransaction($order_id, $transaction_id, $hash)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wc_fedapay_orders_transactions';

        $wpdb->insert(
            $table_name,
            array(
                'transaction_id' => $transaction_id,
                'order_id' => $order_id,
                'hash' => $hash,
                'created_at' => current_time( 'mysql' ),
            )
        );
    }

    /**
     * Retrieve order with transaction info from database
     */
    public function getOrderTransaction($order_id, $transaction_id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wc_fedapay_orders_transactions';
        $info =  $wpdb->get_results(
            "SELECT * FROM `" . $table_name . "` ".
            "WHERE `order_id` = '" . (int) $order_id . "' ".
            "AND `transaction_id` = '" . (int) $transaction_id . "' LIMIT 1"
        );

        if (isset($info[0])) {
            return $info[0];
        }

        return null;
    }

    /**
     * We're processing the payments here
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order      = wc_get_order($order_id);
        $amount     = (int) $order->get_total();
        $firstname  = sanitize_text_field($order->billing_first_name);
        $lastname   = sanitize_text_field($order->billing_last_name);
        $email      = sanitize_email($order->billing_email);

        $token = md5(uniqid());
        $hash = md5($order_id . $amount . $order->currency . $token);

        $callback_url = home_url('/') . 'wc-api/' . get_class($this) . '/?wcfpg_order_id=' . $order_id . '&wcfpg_token=' . $token;
        $description = 'Commande ' . $order->id;

        foreach ( $order->get_items() as $item ) {
            $description .= ', Article: ' . $item->get_name();
            break; // Use juste first item name
        }

        if (!$this->isValideCurrency($order->currency)) {
            wc_add_notice( sprintf( __( "%s only supports XOF as currency for now. Please select XOF currrency or contact the store manager.", 'woo-gateway-fedapay' ), $this->method_title ), 'error' );
        }

        try {
            $transaction = \FedaPay\Transaction::create(array(
                'description' => $description,
                'amount' => $amount,
                'currency' => array( 'iso' => $order->currency ),
                'callback_url' => $callback_url,
                'customer' => [
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'email' => $email
                ]
            ));

            $this->addOrderTransaction($order_id, $transaction->id, $hash);

            return [
                'result'   => 'success',
                'redirect' => $this->getRedirectUrl($transaction)
            ];
        } catch (\Exception $e) {
            $this->displayErrors($e);
        }
    }

    /**
     *  Check if we are forcing SSL on checkout page
     */
    public function do_ssl_check()
    {
        if ($this->enabled == "yes") {
            if (get_option('woocommerce_force_ssl_checkout') == "no") {
                echo "<div class=\"error\"><p>". sprintf(__('<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href="%s">forcing the checkout pages to be secured.</a>', 'woo-gateway-fedapay'), $this->method_title, admin_url('admin.php?page=wc-settings&tab=advanced')) ."</p></div>";
            }
        }
    }

    /**
     * Check Order status on callback
     */
    public function check_order_status()
    {
        global $woocommerce;

        $order_id = 0;
        $transaction_id = 0;
        $token = null;


        $order_id = (int) filter_input(INPUT_GET, 'wcfpg_order_id', FILTER_SANITIZE_NUMBER_INT);
        $transaction_id = (int) filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        $token = (string) filter_input(INPUT_GET, 'wcfpg_token', FILTER_SANITIZE_STRING );


        $order = wc_get_order($order_id);
        $order_transaction = $this->getOrderTransaction($order_id, $transaction_id);
        $url = wc_get_checkout_url();

        if ($order && $order_transaction) {
            try {
                $transaction = \FedaPay\Transaction::retrieve($transaction_id);
                $hash = md5($order_id . $transaction->amount . $order->currency . $token);

                if ($hash && $hash === $order_transaction->hash) {
                    $this->updateOrderStatus($order, $transaction->status);

                    if ($transaction->status == 'approved') {
                        $woocommerce->cart->empty_cart();
                        $url = $this->get_return_url($order);
                    }

                    return wp_redirect($url);;
                }
            } catch (\Exception $e) {
                $this->displayErrors($e);
            }
        }

        $this->updateOrderStatus($order);
        wp_redirect($url);
    }

    /**
     * Display payment request errors
     * @param \Exception $e
     */
    private function displayErrors(\Exception $e)
    {
        wc_add_notice(__('Payment error: '. $e->getMessage(), 'woo-gateway-fedapay'), 'error');

        if ($e instanceof \FedaPay\Error\ApiConnection && $e->hasErrors()) {
            foreach ($e->getErrors() as $key => $errors) {
                foreach ($errors as $error) {
                    wc_add_notice(__($key . ' ' . $error, 'woo-gateway-fedapay'), 'error');
                }
            }
        }
    }

    /**
     * Update order status
     *
     * @param $order order
     * @param $transaction_status FedaPay transaction status
     */
    private function updateOrderStatus($order, $transaction_status = null)
    {
        switch ($transaction_status) {
            case 'approved':
                $order->update_status('completed');
                wc_add_notice(__('Transaction completed successfully', 'woo-gateway-fedapay'), 'success');
                $order->add_order_note(__('Hey, the order has been completed. Thanks!', 'woo-gateway-fedapay'), true);
                break;
            case 'canceled':
            case 'declined':
                $order->update_status('cancelled', 'Error:');
                $order->add_order_note(__('Hey, the order has been cancelled. Try again!', 'woo-gateway-fedapay'), true);
                wc_add_notice(__('Transaction has been cancelled: Try again!', 'woo-gateway-fedapay'), 'error');
                break;
            default:
                $order->add_order_note(__('Hey, the order payment failed. Try again!', 'woo-gateway-fedapay'), true);
                wc_add_notice(__('Transaction failed: Try again!', 'woo-gateway-fedapay'), 'error');
                break;
        }
    }

    /**
     * Return the redirect uri according to settings
     *
     * @param $transaction FedaPay transaction object
     *
     * @return string
     */
    private function getRedirectUrl($transaction)
    {
        if (
            $this->is_true($this->checkoutmodale) &&
            (
                ($this->is_true($this->testmode) && $this->fedapay_testpublickey) ||
                (!$this->is_true($this->testmode) && $this->fedapay_livepublickey)
            )
        ) {
            $redirect_url = sprintf( '#fedapay-confirm-%s:%s', $transaction->id, rawurlencode( $transaction->callback_url ) );
        } else {
            $redirect_url = $transaction->generateToken()->url;
        }

        return $redirect_url;
    }

    /**
     * Generate Image HTML.
     *
     * @param  mixed $key
     * @param  mixed $data
     * @since  1.5.0
     * @return string
     */
    public function generate_image_html( $key, $data ) {
        $field_key = $this->get_field_key( $key );
        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
        );

        $data  = wp_parse_args( $data, $defaults );
        $value = $this->get_option( $key );

        // Hide show add remove buttons.
        $maybe_hide_add_style    = '';
        $maybe_hide_remove_style = '';

        // For backwards compatibility (customers that already have set a url)
        $value_is_url            = filter_var( $value, FILTER_VALIDATE_URL ) !== false;

        if ( empty( $value ) || $value_is_url ) {
            $maybe_hide_remove_style = 'display: none;';
        } else {
            $maybe_hide_add_style = 'display: none;';
        }

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); ?></label>
            </th>

            <td class="image-component-wrapper">
                <div class="image-preview-wrapper">
                    <?php
                    if ( ! $value_is_url ) {
                        echo wp_get_attachment_image( $value, 'thumbnail' );
                    } else {
                    ?>
                        <img src="<?php echo esc_url($value); ?>" />
                    <?php
                    }
                    ?>
                </div>

                <button
                    class="button wc_fedapay_gateway_image_upload"
                    data-field-id="<?php echo esc_attr( $field_key ); ?>"
                    data-media-frame-title="<?php echo esc_attr( __( 'Select an image to upload', 'woo-gateway-fedapay' ) ); ?>"
                    data-media-frame-button="<?php echo esc_attr( __( 'Use this image', 'woo-gateway-fedapay' ) ); ?>"
                    data-add-image-text="<?php echo esc_attr( __( 'Add image', 'woo-gateway-fedapay' ) ); ?>"
                    style="<?php echo esc_attr( $maybe_hide_add_style ); ?>"
                >
                    <?php echo esc_html__( 'Add image', 'woo-gateway-fedapay' ); ?>
                </button>

                <button
                    class="button wc_fedapay_gateway_image_remove"
                    data-field-id="<?php echo esc_attr( $field_key ); ?>"
                    style="<?php echo esc_attr( $maybe_hide_remove_style ); ?>"
                >
                    <?php echo esc_html__( 'Remove image', 'woo-gateway-fedapay' ); ?>
                </button>

                <input type="hidden"
                    name="<?php echo esc_attr( $field_key ); ?>"
                    id="<?php echo esc_attr( $field_key ); ?>"
                    value="<?php echo esc_attr( $value ); ?>"
                />
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    private function is_true($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

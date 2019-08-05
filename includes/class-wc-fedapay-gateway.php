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
        $this->icon = plugins_url('../assets/img/fedapay.svg', __FILE__) ;
        $this->has_fields = false;
        $this->method_title = 'Woocommerce Fedapay Gateway';
        $this->order_button_text = __('Continue to payment', 'woo-gateway-fedapay');
        $this->method_description = __('Fedapay Payment Gateway Plug-in for WooCommerce', 'woo-gateway-fedapay');

        $this->supports = ['products'];

        // Method for loading fedapay-php-lib
        $this->get_fedapay_sdk();

        // Method for loading all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();


        // Turn these settings into variables we can use
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        // Setup FedaPay SDK
        $this->setupFedaPaySdk($this->testmode, $this->fedapay_testsecretkey, $this->fedapay_livesecretkey);

        // Lets check for SSL
        add_action('admin_notices', array( $this, 'do_ssl_check' ));
        add_action('admin_notices', array( $this, 'currency_check' ));

        // Save settings
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));
        }

        add_action('woocommerce_api_'. strtolower(get_class($this)), array( $this, 'check_order_status' ));
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

        if ($currency != 'XOF') {
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
        global $woocommerce;

        $order      = wc_get_order($order_id);
        $amount     = (int) $order->get_total();
        $phone      = $order->billing_phone;
        $firstname  = $order->billing_first_name;
        $lastname   = $order->billing_last_name;
        $email      = $order->billing_email;

        $token = md5(uniqid());
        $hash = md5($order_id . $amount . $order->currency . $token);

        $callback_url = home_url('/') . 'wc-api/' . get_class($this) . '/?order_id=' . $order_id . '&token=' . $token;
        $order_number = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 4);
        $order_number = strtoupper($order_number);

        if ($order->currency !== 'XOF') {
            wc_add_notice( sprintf( __( "%s only supports XOF as currency for now. Please select XOF currrency or contact the store manager.", 'woo-gateway-fedapay' ), $this->method_title ), 'error' );
        }

        try {
            $transaction = \FedaPay\Transaction::create(array(
                'description' => 'Article '.$order_number,
                'amount' => $amount,
                'currency' => array('iso'=>$order->currency),
                'callback_url' => $callback_url,
                'customer' => [
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'email' => $email
                ]
            ));

            $this->addOrderTransaction($order_id, $transaction->id, $hash);

            $token = $transaction->generateToken();
            return [
                'result'   => 'success',
                'redirect' => $token->url
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

        if (isset($_GET['order_id'])) {
            $order_id = $_GET['order_id'];
        }

        if (isset($_GET['id'])) {
            $transaction_id = $_GET['id'];
        }

        if (isset($_GET['token'])) {
            $token = $_GET['token'];
        }

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
     */
    private function updateOrderStatus($order, $transaction_status = null)
    {
        switch($transaction_status) {
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
}

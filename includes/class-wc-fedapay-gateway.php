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
        $this->icon = plugins_url('../assets/img/fedapay.png', __FILE__) ;
        $this->has_fields = false;
        $this->method_title = 'Woocommerce Gateway Fedapay';
        $this->order_button_text = __('Continue to payment', 'woocommerce-gateway-fedapay');
        $this->method_description = __('Fedapay Payment Gateway Plug-in for WooCommerce', 'woocommerce-gateway-fedapay');

        $this->supports = ['products'];

        // Method for loading fedapay-php-lib
        $this->get_fedapay_sdk();

        // Method for loading all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->fedapay_testsecretkey = $this->get_option('fedapay_testsecretkey');
        $this->fedapay_livesecretkey = $this->get_option('fedapay_livesecretkey');

        // Turn these settings into variables we can use
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        if ($this->testmode == 'yes') {
            \FedaPay\FedaPay::setApiKey($this->fedapay_testsecretkey);
            \FedaPay\FedaPay::setEnvironment('sandbox');
        } else {
            \FedaPay\FedaPay::setApiKey($this->fedapay_livesecretkey);
            \FedaPay\FedaPay::setEnvironment('live');
        }
        // Lets check for SSL
        add_action('admin_notices', array( $this,    'do_ssl_check' ));

        // Save settings
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));
        }

        add_action('woocommerce_api_'.strtolower(get_class($this)), array( $this, 'check_order_status' ));
    }

    private function get_fedapay_sdk()
    {
        if (! class_exists('Fedapay\Fedapay')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'vendor/fedapay-php/init.php';
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
     * We're processing the payments here
     */
    public function process_payment($order_id)
    {
        global $woocommerce;

        $order      = wc_get_order($order_id);
        $amount     = $order->get_total();
        $phone      = $order->billing_phone;
        $firstname  = $order->billing_first_name;
        $lastname   = $order->billing_last_name;
        $email      = $order->billing_email;

        $callback_url = home_url('/') . 'wc-api/' . get_class($this) . '/?order_id=' . $order_id;
        $order_number = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 4);
        $order_number = strtoupper($order_number);

        try {
            $transaction = \FedaPay\Transaction::create([
                "description" => "Article ".$order_number,
                "amount" => (int) $amount,
                "currency" => ["iso" => "XOF"],
                "callback_url" => $callback_url,
                "customer" => [
                    "firstname" => $firstname,
                    "lastname" => $lastname,
                    "email" => $email,
                    "phone_number" => [
                        "number" => $phone,
                        "country" => 'bj'
                    ]
                ]
            ]);

            $token = $transaction->generateToken();
            return [
                'result'   => 'success',
                'redirect' => $token->url
            ];
        } catch (\Exception $e) {
            wc_add_notice(__('Payment error: '. $e->getMessage(), 'woocommerce-gateway-fedapay'), 'error');
            return;
        }
    }

    /**
     *  Check if we are forcing SSL on checkout page
     */
    public function do_ssl_check()
    {
        if ($this->enabled == "yes") {
            if (get_option('woocommerce_force_ssl_checkout') == "no") {
                echo "<div class=\"error\"><p>". sprintf(__('<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href="%s">forcing the checkout pages to be secured.</a>', 'woocommerce-gateway-fedapay'), $this->method_title, admin_url('admin.php?page=wc-settings&tab=advanced')) ."</p></div>";
            }
        }
    }

    public function check_order_status()
    {
        global $woocommerce;

        $order_id = $_GET['order_id'];
        $order = wc_get_order($order_id);

        if (isset($_GET['id'])) {
            $transaction_id = $_GET['id'];
            try {
                $transaction = \FedaPay\Transaction::retrieve($transaction_id);
                switch ($transaction->status) {
                    case 'approved':
                        $order->update_status('completed');
                        wc_add_notice(__('Transaction completed successfully', 'woocommerce-gateway-fedapay'), 'success');
                        $order->add_order_note(__('Hey, the order has been completed. Thanks!', 'woocommerce-gateway-fedapay'), true);
                        $woocommerce->cart->empty_cart();
                        wp_redirect($this->get_return_url($order));
                    break;
                    case 'canceled':
                        $order->update_status('cancelled', 'Error:');
                        $order->add_order_note(__('Hey, the order has been cancelled. Try again!', 'woocommerce-gateway-fedapay'), true);
                        wc_add_notice(__('Transaction has been cancelled: Try again!', 'woocommerce-gateway-fedapay'), 'error');
                        $url = wc_get_checkout_url();
                        wp_redirect($url);
                    break;
                    case 'declined':
                        $order->update_status('failed', 'Error:');
                        $order->add_order_note(__('Hey, the order has been declined. Try again!', 'woocommerce-gateway-fedapay'), true);
                        wc_add_notice(__('Transaction has been declined: Try again!', 'woocommerce-gateway-fedapay'), 'error');
                        $url = wc_get_checkout_url();
                        wp_redirect($url);
                    break;
                }
            } catch (\Exception $e) {
                wc_add_notice(__('Payment error: '.$e->getMessage(), 'woocommerce-gateway-fedapay'), 'error');
            }
            die();
        }
    }
}

<?php

/**
* Main class
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

add_filter( 'woocommerce_payment_gateways', 'add_fedapay_gateway_class' );

function add_fedapay_gateway_class( $gateways ) {
	$gateways[] = 'WC_Fedapay_Gateway';
	return $gateways;
}

add_action( 'plugins_loaded', 'init_fedapay_gateway_class' );
function init_fedapay_gateway_class() {
  if ( ! class_exists( 'WC_Fedapay_Gateway' ) ) {

    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

    class WC_Fedapay_Gateway extends WC_Payment_Gateway{

      /**
      * Plugin version, used for cache-busting of style and script file references.
      *
      * @since   0.1.0
      *
      * @var     string
      */
      public $version = '0.1.0';

      /**
      * Instance of this class.
      *
      * @since    0.1.0
      *
      * @var      object
      */
      protected static $instance = null;

      /**
      * Protected constructor to prevent creating a new instance of the
      * *Singleton* via the `new` operator from outside of this class.
      */
      public function __construct() {
        $plugin_dir = plugin_dir_url(__FILE__);
        $this->id = 'woo_gateway_fedapay';
        $this->icon = apply_filters( 'woocommerce_gateway_icon', $plugin_dir.'../assets/img/fedapay.png' );
        // $this->icon = plugin_dir_path( dirname( __FILE__ ) ) . 'assets/img/fedapay.png';
        $this->has_fields = false;
        $this->method_title = 'Woocommerce Gateway Fedapay';
        $this->order_button_text = __( 'Payer avec Fedapay', 'woocommerce' );
        $this->method_description = 'Woocommerce Fedapay Payment Gateway Plug-in for WooCommerce';

        $this->supports = array(
          'products',
          'refunds',
        );

        // Method for loading fedapay-php-lib
        $this->get_fedapay_sdk();

        // Method for loading all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->enabled = $this->get_option( 'enabled' );
        $this->testmode = 'yes' === $this->get_option( 'testmode' );
        $this->fedapay_testsecretkey = $this->get_option( 'fedapay_testsecretkey' );
        $this->fedapay_livesecretkey = $this->get_option( 'fedapay_livesecretkey' );

        // Turn these settings into variables we can use
        foreach ( $this->settings as $setting_key => $value ) {
          $this->$setting_key = $value;
        }
        if($this->testmode == 'yes')
      {
        \FedaPay\FedaPay::setApiKey($this->fedapay_testsecretkey);
        \FedaPay\FedaPay::setApiBase('https://dev-api.fedapay.com');
      }
      else
      {
       \FedaPay\FedaPay::setApiKey($this->fedapay_livesecretkey);
       \FedaPay\FedaPay::setApiBase('https://api.fedapay.com');
      }
        // Lets check for SSL
        add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );

        // Save settings
        if ( is_admin() ) {
          add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        // We need custom JavaScript to obtain a token
        // add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

        add_action( 'woocommerce_thankyou', array( $this, 'check_order_status' ) );

      }

      /**
       * Return an instance of this class.
       *
       * @since     0.1.0
       *
       * @return    object    A single instance of this class.
       */
      public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance ) {
          self::$instance = new self;
        }

        return self::$instance;
      }

      private function get_fedapay_sdk() {
      if ( ! class_exists( 'Fedapay\Fedapay' ) ) {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/fedapay-php/init.php';
      }
      require(  plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/fedapay-php/vendor/autoload.php');
      }
      /**
       * Initialise Gateway Settings Form Fields.
       */
      public function init_form_fields() {
        $this->form_fields =  include plugin_dir_path( __FILE__ ) . '/settings-fedapay.php';
      }

      /*
		 * We're processing the payments here
		 */
		public function process_payment( $order_id ) {

      global $woocommerce;

      // we need it to get any order detailes
      $order = wc_get_order( $order_id );

      $amount 	= $order->get_total();
      $phone = $order->billing_phone;
      $firstname = $order->billing_first_name;
      $lastname = $order->billing_last_name;
      $email = $order->billing_email;
      $callback_url = $this->get_return_url( $order );
      $order_number = substr( str_shuffle( "0123456789abcdefghijklmnopqrstuvwxyz" ), 0, 4);
      $order_number = strtoupper($order_number);

      try{
        $transaction = \FedaPay\Transaction::create([
                "description" => "Article ".$order_number,
                "amount" => (int)$amount,
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
            return array(
            'result'   => 'success',
            'redirect' => $token->url
            );

      } catch (\FedaPay\Error\ApiConnection $e) {
          wc_add_notice( __('Payment error: '.$e->getMessage(), 'woocommerce'), 'error' );
          return;
     }

    }

      // Check if we are forcing SSL on checkout page
      public function do_ssl_check() {
        if( $this->enabled == "yes" ) {
          if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
            echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";
          }
        }
      }

      public function check_order_status($order_id){
          global $woocommerce;
          if ( ! $order_id ) {
            return;
          }

          $order = wc_get_order( $order_id );
        	if( isset( $_GET['id'] ) ) {
            $transaction_id = $_GET['id'];
             try {
                $transaction = \FedaPay\Transaction::retrieve($transaction_id);
                if ($transaction->status === 'approved') {
                  $order->update_status( 'completed' );
                  $order->add_order_note( 'Hey, your order is paid! Thank you!', true );
                  $woocommerce->cart->empty_cart();
                } else {
                    $order->update_status( 'failed', 'Error:' );
                    $order->add_order_note( 'Hey, your payment is cancelled. Retry please!', true );
                    return;
                }
              } catch(\FedaPay\Error\ApiConnection $e) {
                  wc_add_notice( __('Payment error: '.$e->getMessage(), 'woocommerce'), 'error' );
                  return;
              }
          }
      }
    }
  }
}
<?php

/**
* Main class
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

add_action( 'plugins_loaded', 'init_fedapay_gateway_class' );

function init_fedapay_gateway_class() {
  if ( ! class_exists( 'WC_Fedapay_Gateway' ) ) {

    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

    class WC_Fedapay_Gateway extends WC_Payment_Gateway{

      /**
      * Protected constructor to prevent creating a new instance of the
      * *Singleton* via the `new` operator from outside of this class.
      */
      public function __construct() {
        $plugin_dir = plugin_dir_url(__FILE__);
        $this->id = 'woo_gateway_fedapay';
        $this->icon = apply_filters( 'woocommerce_gateway_icon', $plugin_dir.'../assets/img/fedapay.png' );
        $this->has_fields = false;
        $this->method_title = 'Woocommerce Gateway Fedapay';
        $this->order_button_text = __( 'Continue to payment', 'woocommerce' );
        $this->method_description = 'Woocommerce Fedapay Payment Gateway Plug-in for WooCommerce';

        $this->supports = ['products'];

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

       add_action( 'woocommerce_api_'.strtolower(get_class($this)), array( $this, 'check_order_status' ) );
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

      $order = wc_get_order( $order_id );
      $amount 	= $order->get_total();
      $phone = $order->billing_phone;
      $firstname = $order->billing_first_name;
      $lastname = $order->billing_last_name;
      $email = $order->billing_email;
      $callback_url = home_url( '/' ).'wc-api/'.get_class($this).'/?order_id='.$order_id;
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

      } catch (\Exception $e) {
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

      public function check_order_status(){
          global $woocommerce;

          $order_id = $_GET['order_id'];
          $order = wc_get_order($order_id);

        	if( isset( $_GET['id'] ) ) {
            $transaction_id = $_GET['id'];
             try {
                $transaction = \FedaPay\Transaction::retrieve($transaction_id);
                switch($transaction->status) {
                    case 'approved':
                      $order->update_status( 'completed' );
                      wc_add_notice( __('Paiement bien effectué', 'woocommerce'), 'success' );
                      $order->add_order_note( 'Hey, la commande a été payé. Merci!', true );
                      $woocommerce->cart->empty_cart();
                      wp_redirect($this->get_return_url($order));
                     break;
                    case 'canceled':
                        $order->update_status( 'cancelled', 'Error:' );
                        $order->add_order_note( 'Hey, la commande a été annulée. Veuillez réssayer!', true );
                        wc_add_notice( __('Le paiement a été annulé:Veuillez réssayer!', 'woocommerce'), 'error' );
                        $url = wc_get_checkout_url();
                        wp_redirect($url);
                    break;
                    case 'declined':
                         $order->update_status( 'failed', 'Error:' );
                         $order->add_order_note( 'Hey, votre commande a été déclinée. Veuillez réssayer!', true );
                         wc_add_notice( __('Le paiement a été décliné:Veuillez réssayer!', 'woocommerce'), 'error' );
                         $url = wc_get_checkout_url();
                        wp_redirect($url);
                    break;
                }
              } catch(\Exception $e) {
                  wc_add_notice( __('Payment error: '.$e->getMessage(), 'woocommerce'), 'error' );
              }
              die();
          }
      }
  }
 }
}

function add_fedapay_gateway_class( $gateways ) {
	$gateways[] = 'WC_Fedapay_Gateway';
	return $gateways;
}

add_filter( 'woocommerce_payment_gateways', 'add_fedapay_gateway_class' );


<?php
/**
 * Settings for Fedapay Gateway.
 *
 * @package WooCommerce/Classes/Payment
 */
defined( 'ABSPATH' ) || exit;

return array(
	'enabled'               => array(
		'title'   => __( 'Enable/Disable', 'woocommerce' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable Fedapay', 'woocommerce' ),
		'default' => 'no',
	),
	'title'                 => array(
		'title'       => __( 'Title', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		'default'     => __( 'Fedapay', 'woocommerce' ),
		'desc_tip'    => true,
	),
	'description'           => array(
		'title'       => __( 'Description', 'woocommerce' ),
		'type'        => 'text',
		'desc_tip'    => true,
		'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
		'default'     => __( "Pay via Fedapay; you can pay with your credit card or MTN Mobile Money", 'woocommerce' ),
	),
	'testmode'              => array(
		'title'       => __( 'Fedapay sandbox', 'woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable Fedapay sandbox', 'woocommerce' ),
		'default'     => 'no',
		/* translators: %s: URL */
		'description' => sprintf( __( 'Fedapay sandbox can be used to test payments. Sign up for a <a href="%s">developer account</a>.', 'woocommerce' ), 'https://fedapay.com/' ),
  ),
  'fedapay_testsecretkey' => array(
		  'title' => __( 'Test Secret Key', 'woocommerce' ),
		  'type' => 'password',
		  'description' => __( 'This is the Test Secret Key found in API Keys in Account Dashboard.', 'woocommerce' ),
		  'default' => '',
		  'desc_tip'      => true,
		  'placeholder' => 'Fedapay Test Secret Key'
		  ),
		'fedapay_livesecretkey' => array(
		  'title' => __( 'Live Secret Key', 'woocommerce' ),
		  'type' => 'password',
		  'description' => __( 'This is the Live Secret Key found in API Keys in Account Dashboard.', 'woocommerce' ),
		  'default' => '',
		  'desc_tip'      => true,
		  'placeholder' => 'Fedapay Live Secret Key'
		  )
);
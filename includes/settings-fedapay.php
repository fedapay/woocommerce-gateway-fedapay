<?php
/**
 * Settings for FedaPay Gateway.
 *
 * @package WooCommerce/Classes/Payment
 */
defined('ABSPATH') || exit;

return array(
    'enabled'           => array(
        'title'   => __('Enable/Disable', 'woo-gateway-fedapay'),
        'type'    => 'checkbox',
        'label'   => __('Enable FedaPay', 'woo-gateway-fedapay'),
        'default' => 'no',
    ),
    'title'                 => array(
        'title'       => __('Title', 'woo-gateway-fedapay'),
        'type'        => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'woo-gateway-fedapay'),
        'default'     => __('Mobile Money - Credit cards (FedaPay)', 'woo-gateway-fedapay'),
        'desc_tip'    => true,
    ),
    'description'           => array(
        'title'       => __('Description', 'woo-gateway-fedapay'),
        'type'        => 'text',
        'desc_tip'    => true,
        'description' => __('This controls the description which the user sees during checkout.', 'woo-gateway-fedapay'),
        'default'     => __("Pay via FedaPay; you can pay with your credit card or Mobile Money", 'woo-gateway-fedapay'),
    ),
    'icon_url' => array(
        'title'       => __( 'Logo Image (190×60)', 'woo-gateway-fedapay' ),
        'type'        => 'image',
        'description' => __( 'If you want FedaPay to co-brand the checkout page with your logo, enter the URL of your logo image here.<br/>The image must be no larger than 190x60, GIF, PNG, or JPG format, and should be served over HTTPS.', 'woo-gateway-fedapay' ),
        'default'     => '',
        'desc_tip'    => true,
        'placeholder' => __( 'Optional', 'woo-gateway-fedapay' ),
    ),
    'testmode'              => array(
        'title'       => __('FedaPay sandbox', 'woo-gateway-fedapay'),
        'type'        => 'checkbox',
        'label'       => __('Enable FedaPay sandbox', 'woo-gateway-fedapay'),
        'default'     => 'no',
        'description' => sprintf(__('FedaPay sandbox can be used to test payments. Sign up for a <a target="_blank" href="%s">developer account</a>.', 'woo-gateway-fedapay'), 'https://fedapay.com/'),
    ),
    'fedapay_testpublickey' => array(
        'title' => __('Test Public Key', 'woo-gateway-fedapay'),
        'type' => 'text',
        'description' => __('This is the Test public Key found in API Keys in Account Dashboard.', 'woo-gateway-fedapay'),
        'default' => '',
        'desc_tip'      => true,
        'placeholder' => __('FedaPay Test Public Key', 'woo-gateway-fedapay')
    ),
    'fedapay_livepublickey' => array(
        'title' => __('Live Public Key', 'woo-gateway-fedapay'),
        'type' => 'text',
        'description' => __('This is the Live Public Key found in API Keys in Account Dashboard.', 'woo-gateway-fedapay'),
        'default' => '',
        'desc_tip'      => true,
        'placeholder' => __('FedaPay Live Public Key', 'woo-gateway-fedapay')
    ),
    'fedapay_testsecretkey' => array(
        'title' => __('Test Secret Key', 'woo-gateway-fedapay'),
        'type' => 'password',
        'description' => __('This is the Test Secret Key found in API Keys in Account Dashboard.', 'woo-gateway-fedapay'),
        'default' => '',
        'desc_tip'      => true,
        'placeholder' => __('FedaPay Test Secret Key', 'woo-gateway-fedapay')
    ),
    'fedapay_livesecretkey' => array(
        'title' => __('Live Secret Key', 'woo-gateway-fedapay'),
        'type' => 'password',
        'description' => __('This is the Live Secret Key found in API Keys in Account Dashboard.', 'woo-gateway-fedapay'),
        'default' => '',
        'desc_tip'      => true,
        'placeholder' => __('FedaPay Live Secret Key', 'woo-gateway-fedapay')
    ),
    'checkoutmodale'        => array(
        'title'       => __('Payment modal', 'woo-gateway-fedapay'),
        'type'        => 'checkbox',
        'label'       => __('Enable payment modal', 'woo-gateway-fedapay'),
        'default'     => 'no',
        'description' => sprintf(__('If enabled, a payment modal will open instead of redirecting the user. Warning! This operation needs you to connect your website to FedaPay Checkout. <a target="_blank" href="%s">Learn more</a>', 'woo-gateway-fedapay'), 'https://docs.fedapay.com/paiements/checkout'),
    )
);

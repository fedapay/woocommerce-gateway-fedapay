<?php
/**
 * Settings for Fedapay Gateway.
 *
 * @package WooCommerce/Classes/Payment
 */
defined('ABSPATH') || exit;

return array(
    'enabled'           => array(
        'title'   => __('Enable/Disable', 'woo-gateway-fedapay'),
        'type'    => 'checkbox',
        'label'   => __('Enable Fedapay', 'woo-gateway-fedapay'),
        'default' => 'no',
    ),
    'title'                 => array(
        'title'       => __('Title', 'woo-gateway-fedapay'),
        'type'        => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'woo-gateway-fedapay'),
        'default'     => __('Fedapay', 'woo-gateway-fedapay'),
        'desc_tip'    => true,
    ),
    'description'           => array(
        'title'       => __('Description', 'woo-gateway-fedapay'),
        'type'        => 'text',
        'desc_tip'    => true,
        'description' => __('This controls the description which the user sees during checkout.', 'woo-gateway-fedapay'),
        'default'     => __("Pay via Fedapay; you can pay with your credit card or MTN Mobile Money", 'woo-gateway-fedapay'),
    ),
    'testmode'              => array(
        'title'       => __('Fedapay sandbox', 'woo-gateway-fedapay'),
        'type'        => 'checkbox',
        'label'       => __('Enable Fedapay sandbox', 'woo-gateway-fedapay'),
        'default'     => 'no',
        'description' => sprintf(__('Fedapay sandbox can be used to test payments. Sign up for a <a href="%s">developer account</a>.', 'woo-gateway-fedapay'), 'https://fedapay.com/'),
    ),
    'fedapay_testsecretkey' => array(
        'title' => __('Test Secret Key', 'woo-gateway-fedapay'),
        'type' => 'password',
        'description' => __('This is the Test Secret Key found in API Keys in Account Dashboard.', 'woo-gateway-fedapay'),
        'default' => '',
        'desc_tip'      => true,
        'placeholder' => __('Fedapay Test Secret Key', 'woo-gateway-fedapay')
    ),
    'fedapay_livesecretkey' => array(
        'title' => __('Live Secret Key', 'woo-gateway-fedapay'),
        'type' => 'password',
        'description' => __('This is the Live Secret Key found in API Keys in Account Dashboard.', 'woo-gateway-fedapay'),
        'default' => '',
        'desc_tip'      => true,
        'placeholder' => __('Fedapay Live Secret Key', 'woo-gateway-fedapay')
    )
);

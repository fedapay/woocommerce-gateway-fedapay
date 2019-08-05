<?php

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

$table_name = $wpdb->prefix . 'woo_fedapay_orders_transactions';

$charset_collate = $wpdb->get_charset_collate();

$sql = "DROP TABLE IF EXISTS `$table_name` $charset_collate;";
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );

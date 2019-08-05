<?php

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;

$table_name = $wpdb->prefix . 'wc_fedapay_orders_transactions';

$charset_collate = $wpdb->get_charset_collate();

$sql = "DROP TABLE IF EXISTS `$table_name`;";
$wpdb->query( $sql );

delete_option( 'wc_fedapay_db_version' );

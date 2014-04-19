<?php
if(!defined('ABSPATH') && !defined('WP_INSTALL_PLUGIN'))
	exit();

// Delete table on plugin uninstall.
global $wpdb;

// Delete Sigma Options.
delete_option('sigma_options');

include 'sigma_events.php';
$sigma_events   = new Sigma_Events();

// Delete the registration table.
$table_name     = $wpdb->prefix . $sigma_events->registration_table;
$sql            = "DROP TABLE IF EXISTS $table_name";
$e              = $wpdb->query($sql);

// Delete the payment table.
$table_name     = $wpdb->prefix . $sigma_events->payment_table;
$sql            = "DROP TABLE IF EXISTS $table_name";
$e              = $wpdb->query($sql);
?>

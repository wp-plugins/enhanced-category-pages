<?php
defined( 'ABSPATH' ) OR exit;
//if uninstall not called from WordPress exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

//TODO: move this in a config file
$custompost_name = 'enhancedcategory';
$table_name = 'ecp_x_category';

global $wpdb;

//drop correlation table
$table_name = $wpdb->prefix . $table_name;
$sql = "DROP TABLE IF EXISTS {$table_name}";
$wpdb->query($sql);

//remove ecp
$wpdb->delete('wp_posts', array('post_type' => $custompost_name));

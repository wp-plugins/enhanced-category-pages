<?php
/*
Plugin Name: Enhanced Category Pages
Description: Create custom enhanced pages for categories. Manage category page as a custom post.
Author: Ciprian Amariei, Diana Amitroaei
Version: 0.1
Text Domain: enhanced-category-pages
Text Path: languages
 */

defined( 'ABSPATH' ) OR exit;

include 'autoload.php';

//avoid global variables
call_user_func(
	function () {

		$plugin_url = plugin_dir_url(__FILE__);

		//full path the to plugin directory
		$plugin_directory_name = pathinfo(plugin_basename(__FILE__), PATHINFO_DIRNAME);

		$wpi = \ecp\WP_Integration::getInstance($plugin_url, $plugin_directory_name, __FILE__);

		$wpi->after_init(function () use ($wpi) {
			global $enhanced_category;
			$enhanced_category = $wpi->get_ec();
		});

		$wpi->init();
	}
);

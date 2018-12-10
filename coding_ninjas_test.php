<?php
/**
 * Plugin Name:       Coding Ninjas Test
 * Plugin URI:        http://example.com/plugin-name-uri/
 * Description:       Test plugin
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        http://example.com/
 * Text Domain:       cnt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Checks if  requirements are met
 *
 * @return bool True if requirements are met, false if not
 */
function cnt_requirements_met() {
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' ) ;

	if ( ! is_plugin_active( 'coding-ninjas/coding-ninjas.php' ) ) {
		return false;
	}

	return true;
}

/**
 * Prints an error that the system requirements weren't met.
 */
function cnt_requirements_error() {
	?>
	<div class="notice notice-error is-dismissible">
		<p> Please install and activate <strong>Coding Ninjas Tasks</strong> plugin before activating <strong>Coding Ninjas Test</strong> plugin.</p>
	</div>
	<?php
}

/*
 * Check requirements and load main class
 */
if ( cnt_requirements_met() ) {
	add_action('plugins_loaded', function(){
		if (class_exists('codingninjas\App')) {
			require_once "app/AppTest.php";
			codingninjastest\AppTest::run( __FILE__ );
		}

	});
} else {
	if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
	add_action( 'admin_notices', 'cnt_requirements_error' );
}

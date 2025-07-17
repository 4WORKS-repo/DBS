<?php
/**
 * Debug script for Distance Based Shipping plugin.
 * 
 * This script helps identify and fix common issues with the plugin.
 * Run this script in your WordPress admin area or via command line.
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Debug function to check plugin status.
 */
function dbs_debug_plugin_status() {
	$debug_info = [];
	
	// Check if WooCommerce is active
	$debug_info['woocommerce_active'] = class_exists( 'WooCommerce' );
	
	// Check if required files exist
	$required_files = [
		'includes/functions/database-functions.php',
		'includes/functions/admin-functions.php',
		'includes/functions/shipping-functions.php',
		'includes/functions/distance-functions.php',
		'includes/functions/geocoding-functions.php',
		'includes/functions/ajax-functions.php',
		'includes/functions/translation-functions.php',
		'includes/class-dbs-shipping-method.php',
		'includes/class-dbs-rest-api.php',
	];
	
	$debug_info['missing_files'] = [];
	foreach ( $required_files as $file ) {
		$file_path = DBS_PLUGIN_PATH . $file;
		if ( ! file_exists( $file_path ) ) {
			$debug_info['missing_files'][] = $file;
		}
	}
	
	// Check if database tables exist
	global $wpdb;
	$tables = dbs_get_table_names();
	$debug_info['missing_tables'] = [];
	foreach ( $tables as $table_name ) {
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
		if ( ! $table_exists ) {
			$debug_info['missing_tables'][] = $table_name;
		}
	}
	
	// Check if functions are available
	$required_functions = [
		'dbs_create_database_tables',
		'dbs_init_admin',
		'dbs_init_ajax_handlers',
		'dbs_calculate_distance',
		'dbs_geocode_address',
	];
	
	$debug_info['missing_functions'] = [];
	foreach ( $required_functions as $function ) {
		if ( ! function_exists( $function ) ) {
			$debug_info['missing_functions'][] = $function;
		}
	}
	
	// Check if classes are available
	$required_classes = [
		'DBS_Shipping_Method',
		'DBS_REST_API',
	];
	
	$debug_info['missing_classes'] = [];
	foreach ( $required_classes as $class ) {
		if ( ! class_exists( $class ) ) {
			$debug_info['missing_classes'][] = $class;
		}
	}
	
	// Check plugin options
	$debug_info['plugin_options'] = [
		'dbs_map_service' => get_option( 'dbs_map_service', 'not_set' ),
		'dbs_distance_unit' => get_option( 'dbs_distance_unit', 'not_set' ),
		'dbs_enable_caching' => get_option( 'dbs_enable_caching', 'not_set' ),
		'dbs_debug_mode' => get_option( 'dbs_debug_mode', 'not_set' ),
	];
	
	// Check if stores exist
	$stores = dbs_get_stores( false );
	$debug_info['stores_count'] = count( $stores );
	
	// Check if shipping rules exist
	$rules = dbs_get_shipping_rules( false );
	$debug_info['rules_count'] = count( $rules );
	
	return $debug_info;
}

/**
 * Fix common plugin issues.
 */
function dbs_fix_plugin_issues() {
	$fixes_applied = [];
	
	// Create database tables if missing
	if ( function_exists( 'dbs_create_database_tables' ) ) {
		dbs_create_database_tables();
		$fixes_applied[] = 'Database tables created/updated';
	}
	
	// Set default options if missing
	$default_options = [
		'dbs_map_service' => 'openstreetmap',
		'dbs_distance_unit' => 'km',
		'dbs_enable_caching' => '1',
		'dbs_cache_duration' => '24',
		'dbs_fallback_rate' => '10',
		'dbs_debug_mode' => '0',
	];
	
	foreach ( $default_options as $key => $value ) {
		if ( ! get_option( $key ) ) {
			update_option( $key, $value );
			$fixes_applied[] = "Option '{$key}' set to default value";
		}
	}
	
	// Insert default store if none exist
	$stores = dbs_get_stores( false );
	if ( empty( $stores ) && function_exists( 'dbs_insert_default_store' ) ) {
		dbs_insert_default_store();
		$fixes_applied[] = 'Default store created';
	}
	
	return $fixes_applied;
}

/**
 * Display debug information.
 */
function dbs_display_debug_info() {
	$debug_info = dbs_debug_plugin_status();
	
	echo '<div class="wrap">';
	echo '<h1>Distance Based Shipping - Debug Information</h1>';
	
	// WooCommerce status
	echo '<h2>WooCommerce Status</h2>';
	if ( $debug_info['woocommerce_active'] ) {
		echo '<p style="color: green;">✓ WooCommerce is active</p>';
	} else {
		echo '<p style="color: red;">✗ WooCommerce is not active - Plugin will not work!</p>';
	}
	
	// Missing files
	echo '<h2>Required Files</h2>';
	if ( empty( $debug_info['missing_files'] ) ) {
		echo '<p style="color: green;">✓ All required files exist</p>';
	} else {
		echo '<p style="color: red;">✗ Missing files:</p>';
		echo '<ul>';
		foreach ( $debug_info['missing_files'] as $file ) {
			echo '<li>' . esc_html( $file ) . '</li>';
		}
		echo '</ul>';
	}
	
	// Missing database tables
	echo '<h2>Database Tables</h2>';
	if ( empty( $debug_info['missing_tables'] ) ) {
		echo '<p style="color: green;">✓ All database tables exist</p>';
	} else {
		echo '<p style="color: red;">✗ Missing tables:</p>';
		echo '<ul>';
		foreach ( $debug_info['missing_tables'] as $table ) {
			echo '<li>' . esc_html( $table ) . '</li>';
		}
		echo '</ul>';
	}
	
	// Missing functions
	echo '<h2>Required Functions</h2>';
	if ( empty( $debug_info['missing_functions'] ) ) {
		echo '<p style="color: green;">✓ All required functions are available</p>';
	} else {
		echo '<p style="color: red;">✗ Missing functions:</p>';
		echo '<ul>';
		foreach ( $debug_info['missing_functions'] as $function ) {
			echo '<li>' . esc_html( $function ) . '</li>';
		}
		echo '</ul>';
	}
	
	// Missing classes
	echo '<h2>Required Classes</h2>';
	if ( empty( $debug_info['missing_classes'] ) ) {
		echo '<p style="color: green;">✓ All required classes are available</p>';
	} else {
		echo '<p style="color: red;">✗ Missing classes:</p>';
		echo '<ul>';
		foreach ( $debug_info['missing_classes'] as $class ) {
			echo '<li>' . esc_html( $class ) . '</li>';
		}
		echo '</ul>';
	}
	
	// Plugin options
	echo '<h2>Plugin Options</h2>';
	echo '<table class="widefat">';
	echo '<thead><tr><th>Option</th><th>Value</th></tr></thead>';
	echo '<tbody>';
	foreach ( $debug_info['plugin_options'] as $key => $value ) {
		echo '<tr>';
		echo '<td>' . esc_html( $key ) . '</td>';
		echo '<td>' . esc_html( $value ) . '</td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
	
	// Data counts
	echo '<h2>Data Counts</h2>';
	echo '<p>Stores: ' . esc_html( $debug_info['stores_count'] ) . '</p>';
	echo '<p>Shipping Rules: ' . esc_html( $debug_info['rules_count'] ) . '</p>';
	
	// Fix button
	echo '<h2>Auto-Fix Issues</h2>';
	echo '<form method="post">';
	wp_nonce_field( 'dbs_debug_fix', 'dbs_debug_nonce' );
	echo '<input type="submit" name="dbs_fix_issues" class="button button-primary" value="Fix Common Issues">';
	echo '</form>';
	
	echo '</div>';
}

// Handle fix button
if ( isset( $_POST['dbs_fix_issues'] ) && wp_verify_nonce( $_POST['dbs_debug_nonce'], 'dbs_debug_fix' ) ) {
	$fixes = dbs_fix_plugin_issues();
	
	echo '<div class="wrap">';
	echo '<h1>Distance Based Shipping - Fix Results</h1>';
	
	if ( empty( $fixes ) ) {
		echo '<p style="color: green;">✓ No fixes were needed - plugin appears to be working correctly!</p>';
	} else {
		echo '<p style="color: green;">✓ The following fixes were applied:</p>';
		echo '<ul>';
		foreach ( $fixes as $fix ) {
			echo '<li>' . esc_html( $fix ) . '</li>';
		}
		echo '</ul>';
	}
	
	echo '<p><a href="' . admin_url( 'admin.php?page=distance-shipping' ) . '" class="button">Go to Plugin Dashboard</a></p>';
	echo '</div>';
} else {
	// Display debug information
	dbs_display_debug_info();
}
?> 
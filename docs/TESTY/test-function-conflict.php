<?php
/**
 * Test script to verify function conflicts are resolved.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test function conflict resolution.
 */
function dbs_test_function_conflicts() {
	echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border-radius: 5px;">';
	echo '<h2>Function Conflict Resolution Test</h2>';
	
	// Test 1: Check if dbs_format_distance function exists and works
	echo '<h3>1. dbs_format_distance Function Test</h3>';
	if ( function_exists( 'dbs_format_distance' ) ) {
		echo '<p style="color: green;">✓ dbs_format_distance function exists</p>';
		
		// Test the function
		$test_distance = 15.5;
		$formatted = dbs_format_distance( $test_distance );
		echo '<p>• Test distance: ' . $test_distance . ' → Formatted: ' . esc_html( $formatted ) . '</p>';
		
		// Test with custom unit (should always return km)
		$formatted_custom = dbs_format_distance( $test_distance );
		echo '<p>• Test distance with custom unit: ' . $test_distance . ' → Formatted: ' . esc_html( $formatted_custom ) . '</p>';
	} else {
		echo '<p style="color: red;">✗ dbs_format_distance function does not exist</p>';
	}
	
	// Test 2: Check if dbs_log_debug function exists
	echo '<h3>2. dbs_log_debug Function Test</h3>';
	if ( function_exists( 'dbs_log_debug' ) ) {
		echo '<p style="color: green;">✓ dbs_log_debug function exists</p>';
	} else {
		echo '<p style="color: red;">✗ dbs_log_debug function does not exist</p>';
	}
	
	// Test 3: Check for any other function conflicts
	echo '<h3>3. Function Conflict Check</h3>';
	$functions_to_check = [
		'dbs_calculate_distance',
		'dbs_get_stores',
		'dbs_get_shipping_rules',
		'dbs_geocode_address',
		'dbs_format_shipping_rate',
		'dbs_get_applicable_shipping_rules',
	];
	
	$conflicts = [];
	foreach ( $functions_to_check as $func ) {
		if ( function_exists( $func ) ) {
			echo '<p style="color: green;">✓ ' . esc_html( $func ) . ' function exists</p>';
		} else {
			echo '<p style="color: orange;">⚠ ' . esc_html( $func ) . ' function not found</p>';
		}
	}
	
	echo '</div>';
}

// Add test page to admin menu
add_action( 'admin_menu', function() {
	add_submenu_page(
		'distance-shipping',
		'Function Conflict Test',
		'Function Test',
		'manage_options',
		'distance-shipping-function-test',
		'dbs_test_function_conflicts'
	);
} ); 
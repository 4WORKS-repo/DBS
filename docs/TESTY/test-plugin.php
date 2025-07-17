<?php
/**
 * Test script for Distance Based Shipping plugin.
 * 
 * This script tests basic plugin functionality.
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test plugin functionality.
 */
function dbs_test_plugin_functionality() {
	$test_results = [];
	
	// Test 1: Check if WooCommerce is active
	$test_results['woocommerce'] = class_exists( 'WooCommerce' );
	
	// Test 2: Check if shipping method is registered
	$shipping_methods = apply_filters( 'woocommerce_shipping_methods', [] );
	$test_results['shipping_method_registered'] = isset( $shipping_methods['distance_based'] );
	
	// Test 3: Test distance calculation
	if ( function_exists( 'dbs_calculate_distance' ) ) {
		$distance = dbs_calculate_distance( 'Prague, Czech Republic', 'Brno, Czech Republic' );
		$test_results['distance_calculation'] = ( false !== $distance && $distance > 0 );
		$test_results['distance_value'] = $distance;
	} else {
		$test_results['distance_calculation'] = false;
	}
	
	// Test 4: Test geocoding
	if ( function_exists( 'dbs_geocode_address' ) ) {
		$coordinates = dbs_geocode_address( 'Prague, Czech Republic' );
		$test_results['geocoding'] = ( false !== $coordinates && isset( $coordinates['lat'], $coordinates['lng'] ) );
		$test_results['coordinates'] = $coordinates;
	} else {
		$test_results['geocoding'] = false;
	}
	
	// Test 5: Check if stores exist
	if ( function_exists( 'dbs_get_stores' ) ) {
		$stores = dbs_get_stores( false );
		$test_results['stores_exist'] = ! empty( $stores );
		$test_results['stores_count'] = count( $stores );
	} else {
		$test_results['stores_exist'] = false;
	}
	
	// Test 6: Check if shipping rules exist
	if ( function_exists( 'dbs_get_shipping_rules' ) ) {
		$rules = dbs_get_shipping_rules( false );
		$test_results['rules_exist'] = ! empty( $rules );
		$test_results['rules_count'] = count( $rules );
	} else {
		$test_results['rules_exist'] = false;
	}
	
	// Test 7: Test AJAX handlers
	$test_results['ajax_handlers'] = has_action( 'wp_ajax_dbs_geocode_address' ) && 
									has_action( 'wp_ajax_dbs_test_distance' ) && 
									has_action( 'wp_ajax_dbs_clear_cache' );
	
	return $test_results;
}

/**
 * Display test results.
 */
function dbs_display_test_results() {
	$results = dbs_test_plugin_functionality();
	
	echo '<div class="wrap">';
	echo '<h1>Distance Based Shipping - Plugin Test Results</h1>';
	
	echo '<table class="widefat">';
	echo '<thead><tr><th>Test</th><th>Status</th><th>Details</th></tr></thead>';
	echo '<tbody>';
	
	// WooCommerce test
	echo '<tr>';
	echo '<td>WooCommerce Active</td>';
	echo '<td>' . ( $results['woocommerce'] ? '✓ Pass' : '✗ Fail' ) . '</td>';
	echo '<td>' . ( $results['woocommerce'] ? 'WooCommerce is active' : 'WooCommerce is not active' ) . '</td>';
	echo '</tr>';
	
	// Shipping method test
	echo '<tr>';
	echo '<td>Shipping Method Registered</td>';
	echo '<td>' . ( $results['shipping_method_registered'] ? '✓ Pass' : '✗ Fail' ) . '</td>';
	echo '<td>' . ( $results['shipping_method_registered'] ? 'Distance Based Shipping method is registered' : 'Shipping method not found' ) . '</td>';
	echo '</tr>';
	
	// Distance calculation test
	echo '<tr>';
	echo '<td>Distance Calculation</td>';
	echo '<td>' . ( $results['distance_calculation'] ? '✓ Pass' : '✗ Fail' ) . '</td>';
	echo '<td>' . ( $results['distance_calculation'] ? 'Distance: ' . round( $results['distance_value'], 2 ) . ' km' : 'Distance calculation failed' ) . '</td>';
	echo '</tr>';
	
	// Geocoding test
	echo '<tr>';
	echo '<td>Geocoding</td>';
	echo '<td>' . ( $results['geocoding'] ? '✓ Pass' : '✗ Fail' ) . '</td>';
	echo '<td>' . ( $results['geocoding'] ? 'Coordinates: ' . $results['coordinates']['lat'] . ', ' . $results['coordinates']['lng'] : 'Geocoding failed' ) . '</td>';
	echo '</tr>';
	
	// Stores test
	echo '<tr>';
	echo '<td>Stores</td>';
	echo '<td>' . ( $results['stores_exist'] ? '✓ Pass' : '✗ Fail' ) . '</td>';
	echo '<td>' . $results['stores_count'] . ' stores found</td>';
	echo '</tr>';
	
	// Rules test
	echo '<tr>';
	echo '<td>Shipping Rules</td>';
	echo '<td>' . ( $results['rules_exist'] ? '✓ Pass' : '✗ Fail' ) . '</td>';
	echo '<td>' . $results['rules_count'] . ' rules found</td>';
	echo '</tr>';
	
	// AJAX handlers test
	echo '<tr>';
	echo '<td>AJAX Handlers</td>';
	echo '<td>' . ( $results['ajax_handlers'] ? '✓ Pass' : '✗ Fail' ) . '</td>';
	echo '<td>' . ( $results['ajax_handlers'] ? 'AJAX handlers are registered' : 'AJAX handlers missing' ) . '</td>';
	echo '</tr>';
	
	echo '</tbody></table>';
	
	// Summary
	$passed_tests = array_sum( array_map( function( $value ) {
		return is_bool( $value ) && $value ? 1 : 0;
	}, $results ) );
	
	$total_tests = count( $results );
	
	echo '<h2>Test Summary</h2>';
	echo '<p><strong>' . $passed_tests . ' out of ' . $total_tests . ' tests passed.</strong></p>';
	
	if ( $passed_tests === $total_tests ) {
		echo '<p style="color: green;">✓ All tests passed! The plugin should be working correctly.</p>';
	} else {
		echo '<p style="color: orange;">⚠ Some tests failed. Check the debug page for more details.</p>';
		echo '<p><a href="' . admin_url( 'admin.php?page=distance-shipping-debug' ) . '" class="button">Go to Debug Page</a></p>';
	}
	
	echo '</div>';
}

// Display test results
dbs_display_test_results();
?> 
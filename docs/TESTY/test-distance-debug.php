<?php
/**
 * Debug test for distance calculation issues.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Debug distance calculation page.
 */
function dbs_test_distance_debug_page() {
	echo '<div class="wrap">';
	echo '<h1>Distance Calculation Debug</h1>';
	
	echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border-radius: 5px;">';
	echo '<h2>Distance Calculation Debug</h2>';
	
	// Test 0: Check if WooCommerce shipping zones are working
	echo '<h3>0. WooCommerce Shipping Zones Test</h3>';
	if ( class_exists( 'WooCommerce' ) ) {
		$shipping_zones = WC_Shipping_Zones::get_zones();
		if ( is_array( $shipping_zones ) ) {
			echo '<p style="color: green;">✓ WooCommerce shipping zones are accessible</p>';
			echo '<p>• Found ' . count( $shipping_zones ) . ' shipping zones</p>';
		} else {
			echo '<p style="color: red;">✗ WooCommerce shipping zones are not accessible</p>';
		}
		
		// Test if we can access shipping methods
		$shipping_methods = WC()->shipping()->get_shipping_methods();
		if ( is_array( $shipping_methods ) ) {
			echo '<p style="color: green;">✓ WooCommerce shipping methods are accessible</p>';
			echo '<p>• Found ' . count( $shipping_methods ) . ' shipping methods</p>';
		} else {
			echo '<p style="color: red;">✗ WooCommerce shipping methods are not accessible</p>';
		}
	} else {
		echo '<p style="color: red;">✗ WooCommerce is not active</p>';
	}
	
	$origin = 'Poděbradská 520/24, 190 00 Praha 9';
	$destination = 'Karlova 3, 397 01 Písek';
	
	echo '<h3>Testing Addresses:</h3>';
	echo '<p><strong>Origin:</strong> ' . esc_html( $origin ) . '</p>';
	echo '<p><strong>Destination:</strong> ' . esc_html( $destination ) . '</p>';
	
	// Test 1: Check geocoding
	echo '<h3>1. Testing Geocoding</h3>';
	
	$origin_coords = dbs_geocode_address( $origin );
	if ( $origin_coords ) {
		echo '<p style="color: green;">✓ Origin geocoded successfully</p>';
		echo '<p><strong>Origin Coordinates:</strong> ' . $origin_coords['lat'] . ', ' . $origin_coords['lng'] . '</p>';
		if ( isset( $origin_coords['formatted_address'] ) ) {
			echo '<p><strong>Origin Formatted:</strong> ' . esc_html( $origin_coords['formatted_address'] ) . '</p>';
		}
	} else {
		echo '<p style="color: red;">✗ Origin geocoding failed</p>';
	}
	
	$destination_coords = dbs_geocode_address( $destination );
	if ( $destination_coords ) {
		echo '<p style="color: green;">✓ Destination geocoded successfully</p>';
		echo '<p><strong>Destination Coordinates:</strong> ' . $destination_coords['lat'] . ', ' . $destination_coords['lng'] . '</p>';
		if ( isset( $destination_coords['formatted_address'] ) ) {
			echo '<p><strong>Destination Formatted:</strong> ' . esc_html( $destination_coords['formatted_address'] ) . '</p>';
		}
	} else {
		echo '<p style="color: red;">✗ Destination geocoding failed</p>';
	}
	
	// Test 2: Check distance calculation
	echo '<h3>2. Testing Distance Calculation</h3>';
	
	if ( $origin_coords && $destination_coords ) {
		$distance = dbs_calculate_distance( $origin, $destination );
		
		if ( false !== $distance ) {
			echo '<p style="color: green;">✓ Distance calculated successfully</p>';
			echo '<p><strong>Distance:</strong> ' . dbs_format_distance( $distance ) . '</p>';
		} else {
			echo '<p style="color: red;">✗ Distance calculation failed</p>';
		}
	} else {
		echo '<p style="color: red;">✗ Cannot calculate distance - geocoding failed</p>';
	}
	
	// Test 3: Check map service
	echo '<h3>3. Map Service Configuration</h3>';
	$map_service = get_option( 'dbs_map_service', 'openstreetmap' );
	echo '<p><strong>Map Service:</strong> ' . esc_html( $map_service ) . '</p>';
	
	if ( 'google' === $map_service ) {
		$api_key = get_option( 'dbs_google_api_key' );
		echo '<p><strong>Google API Key:</strong> ' . ( $api_key ? 'Configured' : 'Not configured' ) . '</p>';
	} elseif ( 'bing' === $map_service ) {
		$api_key = get_option( 'dbs_bing_api_key' );
		echo '<p><strong>Bing API Key:</strong> ' . ( $api_key ? 'Configured' : 'Not configured' ) . '</p>';
	}
	
	// Test 4: Check cache
	echo '<h3>4. Cache Status</h3>';
	$cache_enabled = get_option( 'dbs_enable_caching', true );
	echo '<p><strong>Cache Enabled:</strong> ' . ( $cache_enabled ? 'Yes' : 'No' ) . '</p>';
	
	// Test 5: Check debug mode
	echo '<h3>5. Debug Mode</h3>';
	$debug_mode = get_option( 'dbs_debug_mode', 0 );
	echo '<p><strong>Debug Mode:</strong> ' . ( $debug_mode ? 'Enabled' : 'Disabled' ) . '</p>';
	
	echo '</div>';
	echo '</div>';
} 
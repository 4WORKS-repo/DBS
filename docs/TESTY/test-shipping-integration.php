<?php
/**
 * Test script for Distance Based Shipping integration with WooCommerce.
 * 
 * This script tests:
 * 1. Shipping method registration
 * 2. Address handling
 * 3. Distance calculation
 * 4. Rate calculation
 * 5. Cart integration
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test shipping method integration.
 */
function dbs_test_shipping_integration() {
	echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border-radius: 5px;">';
	echo '<h2>Distance Based Shipping - Integration Test</h2>';
	
	// Test 1: Check if WooCommerce is active
	echo '<h3>1. WooCommerce Check</h3>';
	if ( class_exists( 'WooCommerce' ) ) {
		echo '<p style="color: green;">✓ WooCommerce is active</p>';
	} else {
		echo '<p style="color: red;">✗ WooCommerce is not active</p>';
		return;
	}
	
	// Test 2: Check if shipping method is registered
	echo '<h3>2. Shipping Method Registration</h3>';
	$shipping_methods = WC()->shipping()->get_shipping_methods();
	if ( isset( $shipping_methods['distance_based'] ) ) {
		echo '<p style="color: green;">✓ Distance Based Shipping method is registered</p>';
	} else {
		echo '<p style="color: red;">✗ Distance Based Shipping method is not registered</p>';
	}
	
	// Test 3: Check if stores exist
	echo '<h3>3. Store Configuration</h3>';
	$stores = dbs_get_stores( true );
	if ( ! empty( $stores ) ) {
		echo '<p style="color: green;">✓ Found ' . count( $stores ) . ' active store(s)</p>';
		foreach ( $stores as $store ) {
			echo '<p>• Store: ' . esc_html( $store->name ) . ' - ' . esc_html( $store->address ) . '</p>';
		}
	} else {
		echo '<p style="color: red;">✗ No active stores found</p>';
	}
	
	// Test 4: Check if shipping rules exist
	echo '<h3>4. Shipping Rules</h3>';
	$rules = dbs_get_shipping_rules( true );
	if ( ! empty( $rules ) ) {
		echo '<p style="color: green;">✓ Found ' . count( $rules ) . ' active rule(s)</p>';
		foreach ( $rules as $rule ) {
			echo '<p>• Rule: ' . esc_html( $rule->rule_name ) . ' (Distance: ' . $rule->distance_from . '-' . $rule->distance_to . ' ' . get_option( 'dbs_distance_unit', 'km' ) . ')</p>';
		}
	} else {
		echo '<p style="color: red;">✗ No active shipping rules found</p>';
	}
	
	// Test 5: Test distance calculation
	echo '<h3>5. Distance Calculation Test</h3>';
	if ( ! empty( $stores ) ) {
		$test_address = 'Prague, Czech Republic';
		$distance = dbs_calculate_distance( $stores[0]->address, $test_address );
		if ( false !== $distance ) {
			echo '<p style="color: green;">✓ Distance calculation works: ' . $stores[0]->address . ' to ' . $test_address . ' = ' . dbs_format_distance( $distance ) . '</p>';
		} else {
			echo '<p style="color: red;">✗ Distance calculation failed</p>';
		}
	}
	
	// Test 6: Test shipping rate calculation
	echo '<h3>6. Shipping Rate Calculation Test</h3>';
	if ( ! empty( $stores ) && ! empty( $rules ) ) {
		$test_address = 'Prague, Czech Republic';
		$store = dbs_find_nearest_store( $test_address );
		if ( $store ) {
			$distance = dbs_calculate_distance( $store->address, $test_address );
			if ( false !== $distance ) {
				$mock_package = dbs_create_mock_package( $test_address );
				$applicable_rules = dbs_get_applicable_shipping_rules( $distance, $mock_package );
				
				if ( ! empty( $applicable_rules ) ) {
					echo '<p style="color: green;">✓ Found ' . count( $applicable_rules ) . ' applicable rule(s) for distance ' . dbs_format_distance( $distance ) . '</p>';
					
					foreach ( $applicable_rules as $rule ) {
						$rate = dbs_calculate_shipping_rate_from_rule( $rule, $distance, $mock_package );
						if ( $rate ) {
							echo '<p>• Rate: ' . esc_html( $rate['label'] ) . ' - ' . wc_price( $rate['cost'] ) . '</p>';
						}
					}
				} else {
					echo '<p style="color: orange;">⚠ No applicable rules found for distance ' . dbs_format_distance( $distance ) . '</p>';
				}
			} else {
				echo '<p style="color: red;">✗ Could not calculate distance</p>';
			}
		} else {
			echo '<p style="color: red;">✗ Could not find nearest store</p>';
		}
	}
	
	// Test 7: Check cart integration
	echo '<h3>7. Cart Integration</h3>';
	if ( WC()->cart && WC()->cart->needs_shipping() ) {
		echo '<p style="color: green;">✓ Cart needs shipping</p>';
		echo '<p>• Cart total: ' . wc_price( WC()->cart->get_cart_contents_total() ) . '</p>';
		echo '<p>• Cart items: ' . WC()->cart->get_cart_contents_count() . '</p>';
	} else {
		echo '<p style="color: orange;">⚠ Cart does not need shipping or is empty</p>';
	}
	
	// Test 8: Check session handling
	echo '<h3>8. Session Handling</h3>';
	if ( WC()->session ) {
		echo '<p style="color: green;">✓ WooCommerce session is available</p>';
		$shipping_address = WC()->session->get( 'shipping_address' );
		if ( $shipping_address ) {
			echo '<p>• Stored shipping address: ' . esc_html( $shipping_address ) . '</p>';
		} else {
			echo '<p>• No shipping address stored in session</p>';
		}
	} else {
		echo '<p style="color: red;">✗ WooCommerce session not available</p>';
	}
	
	// Test 9: Check plugin settings
	echo '<h3>9. Plugin Settings</h3>';
	$map_service = get_option( 'dbs_map_service', 'openstreetmap' );
	$distance_unit = get_option( 'dbs_distance_unit', 'km' );
	$fallback_rate = get_option( 'dbs_fallback_rate', 10 );
	
	echo '<p>• Map service: ' . esc_html( $map_service ) . '</p>';
	echo '<p>• Distance unit: ' . esc_html( $distance_unit ) . '</p>';
	echo '<p>• Fallback rate: ' . wc_price( $fallback_rate ) . '</p>';
	
	// Test 10: Check hooks
	echo '<h3>10. Hook Integration</h3>';
	$hooks = [
		'woocommerce_shipping_methods' => has_filter( 'woocommerce_shipping_methods', 'dbs_add_shipping_method' ),
		'woocommerce_checkout_update_order_review' => has_action( 'woocommerce_checkout_update_order_review', 'dbs_handle_address_update' ),
		'woocommerce_after_shipping_calculator' => has_action( 'woocommerce_after_shipping_calculator', 'dbs_display_shipping_info' ),
	];
	
	foreach ( $hooks as $hook => $has_hook ) {
		if ( $has_hook ) {
			echo '<p style="color: green;">✓ Hook registered: ' . esc_html( $hook ) . '</p>';
		} else {
			echo '<p style="color: red;">✗ Hook not registered: ' . esc_html( $hook ) . '</p>';
		}
	}
	
	echo '</div>';
}

// Add test page to admin menu
add_action( 'admin_menu', function() {
	add_submenu_page(
		'distance-shipping',
		'Integration Test',
		'Integration Test',
		'manage_options',
		'distance-shipping-test',
		'dbs_test_shipping_integration'
	);
} ); 
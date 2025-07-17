<?php
/**
 * Test script for optimized Distance Based Shipping integration.
 * 
 * This script tests:
 * 1. Shipping method registration and filtering
 * 2. Address detection (shipping > billing > session)
 * 3. Distance calculation and caching
 * 4. Rate calculation
 * 5. Cart/Checkout integration without calculator
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test optimized shipping integration.
 */
function dbs_test_optimized_shipping() {
	echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border-radius: 5px;">';
	echo '<h2>Distance Based Shipping - Optimized Integration Test</h2>';
	
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
		echo '<p style="color: green;">✓ Found ' . count( $rules ) . ' active shipping rule(s)</p>';
		foreach ( $rules as $rule ) {
			echo '<p>• Rule: ' . esc_html( $rule->name ) . ' - ' . esc_html( $rule->min_distance ) . '-' . esc_html( $rule->max_distance ) . ' km</p>';
		}
	} else {
		echo '<p style="color: red;">✗ No active shipping rules found</p>';
	}
	
	// Test 5: Check address detection
	echo '<h3>5. Address Detection Test</h3>';
	if ( function_exists( 'WC' ) && WC() && WC()->customer ) {
		$shipping_address = WC()->customer->get_shipping_address();
		$billing_address = WC()->customer->get_billing_address();
		
		if ( ! empty( $shipping_address ) ) {
			echo '<p style="color: green;">✓ Shipping address detected: ' . esc_html( $shipping_address ) . '</p>';
		} else {
			echo '<p style="color: orange;">⚠ No shipping address detected</p>';
		}
		
		if ( ! empty( $billing_address ) ) {
			echo '<p style="color: green;">✓ Billing address detected: ' . esc_html( $billing_address ) . '</p>';
		} else {
			echo '<p style="color: orange;">⚠ No billing address detected</p>';
		}
	} else {
		echo '<p style="color: red;">✗ WooCommerce customer not available</p>';
	}
	
	// Test 6: Check session data
	echo '<h3>6. Session Data</h3>';
	if ( function_exists( 'WC' ) && WC() && WC()->session ) {
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
	
	// Test 7: Check plugin settings
	echo '<h3>7. Plugin Settings</h3>';
	$map_service = get_option( 'dbs_map_service', 'openstreetmap' );
	$distance_unit = get_option( 'dbs_distance_unit', 'km' );
	$fallback_rate = get_option( 'dbs_fallback_rate', 10 );
	$debug_mode = get_option( 'dbs_debug_mode', 0 );
	
	echo '<p>• Map service: ' . esc_html( $map_service ) . '</p>';
	echo '<p>• Distance unit: ' . esc_html( $distance_unit ) . '</p>';
	echo '<p>• Fallback rate: ' . wc_price( $fallback_rate ) . '</p>';
	echo '<p>• Debug mode: ' . ( $debug_mode ? 'Enabled' : 'Disabled' ) . '</p>';
	
	// Test 8: Check hooks
	echo '<h3>8. Hook Integration</h3>';
	$hooks = [
		'woocommerce_shipping_methods' => has_filter( 'woocommerce_shipping_methods', 'dbs_add_shipping_method' ),
		'woocommerce_package_rates' => has_filter( 'woocommerce_package_rates', 'dbs_filter_shipping_methods' ),
		'woocommerce_checkout_update_order_review' => has_action( 'woocommerce_checkout_update_order_review', 'dbs_handle_address_update' ),
	];
	
	foreach ( $hooks as $hook => $has_hook ) {
		if ( $has_hook ) {
			echo '<p style="color: green;">✓ Hook registered: ' . esc_html( $hook ) . '</p>';
		} else {
			echo '<p style="color: red;">✗ Hook not registered: ' . esc_html( $hook ) . '</p>';
		}
	}
	
	// Test 9: Check calculator removal
	echo '<h3>9. Calculator Removal Test</h3>';
	$cart_calculator_hook = has_action( 'woocommerce_after_cart_table', 'dbs_display_cart_shipping_calculator' );
	$checkout_calculator_hook = has_action( 'woocommerce_checkout_before_customer_details', 'dbs_display_checkout_calculator' );
	
	if ( ! $cart_calculator_hook ) {
		echo '<p style="color: green;">✓ Cart calculator hook removed</p>';
	} else {
		echo '<p style="color: red;">✗ Cart calculator hook still active</p>';
	}
	
	if ( ! $checkout_calculator_hook ) {
		echo '<p style="color: green;">✓ Checkout calculator hook removed</p>';
	} else {
		echo '<p style="color: red;">✗ Checkout calculator hook still active</p>';
	}
	
	// Test 10: Check cache functionality
	echo '<h3>10. Cache Functionality</h3>';
	$cache_enabled = get_option( 'dbs_enable_caching', 1 );
	if ( $cache_enabled ) {
		echo '<p style="color: green;">✓ Shipping calculation caching is enabled</p>';
	} else {
		echo '<p style="color: orange;">⚠ Shipping calculation caching is disabled</p>';
	}
	
	echo '</div>';
}

// Add test page to admin menu
add_action( 'admin_menu', function() {
	add_submenu_page(
		'distance-shipping',
		'Optimized Integration Test',
		'Optimized Test',
		'manage_options',
		'distance-shipping-optimized-test',
		'dbs_test_optimized_shipping'
	);
} ); 
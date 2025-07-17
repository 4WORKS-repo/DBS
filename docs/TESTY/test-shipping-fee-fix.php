<?php
/**
 * Test script for shipping fee duplication fix.
 * 
 * This script tests:
 * 1. Shipping method registration without add_fee()
 * 2. Cart totals calculation
 * 3. Checkout totals calculation
 * 4. Fee removal verification
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test shipping fee duplication fix.
 */
function dbs_test_shipping_fee_fix() {
	echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border-radius: 5px;">';
	echo '<h2>Distance Based Shipping - Shipping Fee Fix Test</h2>';
	
	// Test 1: Check if WooCommerce is active
	echo '<h3>1. WooCommerce Check</h3>';
	if ( class_exists( 'WooCommerce' ) ) {
		echo '<p style="color: green;">✓ WooCommerce is active</p>';
	} else {
		echo '<p style="color: red;">✗ WooCommerce is not active</p>';
		return;
	}
	
	// Test 2: Check shipping method registration
	echo '<h3>2. Shipping Method Registration</h3>';
	$shipping_methods = WC()->shipping()->get_shipping_methods();
	if ( isset( $shipping_methods['distance_based'] ) ) {
		echo '<p style="color: green;">✓ Distance Based Shipping method is registered</p>';
	} else {
		echo '<p style="color: red;">✗ Distance Based Shipping method is not registered</p>';
	}
	
	// Test 3: Check for add_fee() usage
	echo '<h3>3. Add Fee Usage Check</h3>';
	$files_to_check = [
		'includes/functions/shipping-functions.php',
		'includes/functions/ajax-functions.php',
		'includes/class-dbs-shipping-method.php'
	];
	
	$add_fee_found = false;
	foreach ( $files_to_check as $file ) {
		$file_path = DBS_PLUGIN_PATH . $file;
		if ( file_exists( $file_path ) ) {
			$content = file_get_contents( $file_path );
			if ( strpos( $content, 'add_fee' ) !== false ) {
				echo '<p style="color: red;">✗ Found add_fee() usage in: ' . esc_html( $file ) . '</p>';
				$add_fee_found = true;
			} else {
				echo '<p style="color: green;">✓ No add_fee() usage in: ' . esc_html( $file ) . '</p>';
			}
		}
	}
	
	if ( ! $add_fee_found ) {
		echo '<p style="color: green;">✓ All files are clean of add_fee() usage</p>';
	}
	
	// Test 4: Check current cart fees
	echo '<h3>4. Current Cart Fees Check</h3>';
	if ( function_exists( 'WC' ) && WC() && WC()->cart ) {
		$fees = WC()->cart->get_fees();
		$shipping_fees = 0;
		
		foreach ( $fees as $fee ) {
			if ( strpos( $fee->name, 'Doprava' ) !== false || 
				 strpos( $fee->name, 'Shipping' ) !== false ) {
				$shipping_fees++;
				echo '<p style="color: orange;">⚠ Found shipping fee: ' . esc_html( $fee->name ) . ' - ' . wc_price( $fee->amount ) . '</p>';
			}
		}
		
		if ( $shipping_fees === 0 ) {
			echo '<p style="color: green;">✓ No shipping fees found in cart</p>';
		} else {
			echo '<p style="color: red;">✗ Found ' . $shipping_fees . ' shipping fee(s) in cart</p>';
		}
	} else {
		echo '<p style="color: orange;">⚠ WooCommerce cart not available</p>';
	}
	
	// Test 5: Check shipping methods
	echo '<h3>5. Shipping Methods Check</h3>';
	if ( function_exists( 'WC' ) && WC() && WC()->shipping() ) {
		$packages = WC()->shipping()->get_packages();
		if ( ! empty( $packages ) ) {
			foreach ( $packages as $package_key => $package ) {
				echo '<p>Package ' . $package_key . ':</p>';
				if ( ! empty( $package['rates'] ) ) {
					foreach ( $package['rates'] as $rate_id => $rate ) {
						echo '<p style="color: green;">✓ Shipping method: ' . esc_html( $rate->get_label() ) . ' - ' . wc_price( $rate->get_cost() ) . '</p>';
					}
				} else {
					echo '<p style="color: orange;">⚠ No shipping methods available</p>';
				}
			}
		} else {
			echo '<p style="color: orange;">⚠ No shipping packages available</p>';
		}
	} else {
		echo '<p style="color: red;">✗ WooCommerce shipping not available</p>';
	}
	
	// Test 6: Check session data
	echo '<h3>6. Session Data Check</h3>';
	if ( function_exists( 'WC' ) && WC() && WC()->session ) {
		$applied_rate = WC()->session->get( 'dbs_applied_shipping_rate' );
		$distance_info = WC()->session->get( 'dbs_distance_info' );
		$store_info = WC()->session->get( 'dbs_store_info' );
		
		if ( $applied_rate ) {
			echo '<p style="color: orange;">⚠ Found applied shipping rate in session</p>';
		} else {
			echo '<p style="color: green;">✓ No applied shipping rate in session</p>';
		}
		
		if ( $distance_info ) {
			echo '<p>• Distance info: ' . esc_html( $distance_info ) . '</p>';
		}
		
		if ( $store_info ) {
			echo '<p>• Store info: ' . esc_html( $store_info->name ?? 'N/A' ) . '</p>';
		}
	} else {
		echo '<p style="color: red;">✗ WooCommerce session not available</p>';
	}
	
	// Test 7: Check hooks
	echo '<h3>7. Hook Integration Check</h3>';
	$hooks = [
		'woocommerce_package_rates' => has_filter( 'woocommerce_package_rates', 'dbs_filter_shipping_methods' ),
		'woocommerce_shipping_methods' => has_filter( 'woocommerce_shipping_methods', 'dbs_add_shipping_method' ),
	];
	
	foreach ( $hooks as $hook => $has_hook ) {
		if ( $has_hook ) {
			echo '<p style="color: green;">✓ Hook registered: ' . esc_html( $hook ) . '</p>';
		} else {
			echo '<p style="color: red;">✗ Hook not registered: ' . esc_html( $hook ) . '</p>';
		}
	}
	
	// Test 8: Check cart totals
	echo '<h3>8. Cart Totals Check</h3>';
	if ( function_exists( 'WC' ) && WC() && WC()->cart ) {
		$cart_total = WC()->cart->get_total( 'raw' );
		$cart_subtotal = WC()->cart->get_subtotal();
		$shipping_total = WC()->cart->get_shipping_total();
		$fee_total = WC()->cart->get_fee_total();
		
		echo '<p>• Cart total: ' . wc_price( $cart_total ) . '</p>';
		echo '<p>• Cart subtotal: ' . wc_price( $cart_subtotal ) . '</p>';
		echo '<p>• Shipping total: ' . wc_price( $shipping_total ) . '</p>';
		echo '<p>• Fee total: ' . wc_price( $fee_total ) . '</p>';
		
		// Check if shipping is counted twice
		$expected_total = $cart_subtotal + $shipping_total + $fee_total;
		if ( abs( $cart_total - $expected_total ) < 0.01 ) {
			echo '<p style="color: green;">✓ Cart totals are correct (no duplication)</p>';
		} else {
			echo '<p style="color: red;">✗ Cart totals mismatch - possible duplication</p>';
		}
	} else {
		echo '<p style="color: orange;">⚠ WooCommerce cart not available</p>';
	}
	
	echo '</div>';
}

// Add test page to admin menu
add_action( 'admin_menu', function() {
	add_submenu_page(
		'distance-shipping',
		'Shipping Fee Fix Test',
		'Fee Fix Test',
		'manage_options',
		'distance-shipping-fee-fix-test',
		'dbs_test_shipping_fee_fix'
	);
} ); 
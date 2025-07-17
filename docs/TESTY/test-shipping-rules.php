<?php
/**
 * Test script for shipping rules functionality.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test shipping rules functionality.
 */
function dbs_test_shipping_rules() {
	echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border-radius: 5px;">';
	echo '<h2>Shipping Rules Test</h2>';
	
	// Test 1: Check if rules can be saved
	echo '<h3>1. Testing Rule Save Functionality</h3>';
	
	$test_rule_data = [
		'rule_name' => 'Test Rule',
		'distance_from' => 0,
		'distance_to' => 50,
		'base_rate' => 10,
		'per_km_rate' => 2,
		'min_order_amount' => 0,
		'max_order_amount' => 0,
		'product_categories' => [],
		'shipping_classes' => [],
		'is_active' => 1,
		'priority' => 0,
	];
	
	$rule_id = dbs_insert_shipping_rule( $test_rule_data );
	
	if ( $rule_id ) {
		echo '<p style="color: green;">✓ Rule saved successfully with ID: ' . $rule_id . '</p>';
		
		// Test retrieving the rule
		$saved_rule = dbs_get_shipping_rule( $rule_id );
		if ( $saved_rule ) {
			echo '<p style="color: green;">✓ Rule retrieved successfully</p>';
			echo '<p><strong>Rule Name:</strong> ' . esc_html( $saved_rule->rule_name ) . '</p>';
			echo '<p><strong>Base Rate:</strong> ' . esc_html( $saved_rule->base_rate ) . '</p>';
			echo '<p><strong>Per KM Rate:</strong> ' . esc_html( $saved_rule->per_km_rate ) . '</p>';
		} else {
			echo '<p style="color: red;">✗ Failed to retrieve saved rule</p>';
		}
		
		// Test updating the rule
		$update_data = [
			'rule_name' => 'Updated Test Rule',
			'base_rate' => 15,
		];
		
		$update_result = dbs_update_shipping_rule( $rule_id, $update_data );
		if ( $update_result !== false ) {
			echo '<p style="color: green;">✓ Rule updated successfully</p>';
		} else {
			echo '<p style="color: red;">✗ Failed to update rule</p>';
		}
		
		// Clean up - delete the test rule
		$delete_result = dbs_delete_shipping_rule( $rule_id );
		if ( $delete_result !== false ) {
			echo '<p style="color: green;">✓ Test rule deleted successfully</p>';
		} else {
			echo '<p style="color: red;">✗ Failed to delete test rule</p>';
		}
	} else {
		echo '<p style="color: red;">✗ Failed to save rule</p>';
	}
	
	// Test 2: Check shipping calculation
	echo '<h3>2. Testing Shipping Calculation</h3>';
	
	// Create a test rule for calculation
	$calc_rule_data = [
		'rule_name' => 'Calculation Test Rule',
		'distance_from' => 0,
		'distance_to' => 100,
		'base_rate' => 5,
		'per_km_rate' => 1.5,
		'min_order_amount' => 0,
		'max_order_amount' => 0,
		'product_categories' => [],
		'shipping_classes' => [],
		'is_active' => 1,
		'priority' => 0,
	];
	
	$calc_rule_id = dbs_insert_shipping_rule( $calc_rule_data );
	
	if ( $calc_rule_id ) {
		$test_distance = 25; // 25 km
		$expected_cost = 5 + ( 25 * 1.5 ); // base_rate + (distance * per_km_rate)
		
		$mock_package = [
			'contents' => [],
			'contents_cost' => 100,
		];
		
		$rate = dbs_calculate_shipping_rate_from_rule( (object) $calc_rule_data, $test_distance, $mock_package );
		
		if ( $rate && abs( $rate['cost'] - $expected_cost ) < 0.01 ) {
			echo '<p style="color: green;">✓ Shipping calculation correct</p>';
			echo '<p><strong>Distance:</strong> ' . $test_distance . ' km</p>';
			echo '<p><strong>Expected Cost:</strong> ' . $expected_cost . '</p>';
			echo '<p><strong>Calculated Cost:</strong> ' . $rate['cost'] . '</p>';
		} else {
			echo '<p style="color: red;">✗ Shipping calculation incorrect</p>';
			if ( $rate ) {
				echo '<p><strong>Expected:</strong> ' . $expected_cost . '</p>';
				echo '<p><strong>Got:</strong> ' . $rate['cost'] . '</p>';
			}
		}
		
		// Clean up
		dbs_delete_shipping_rule( $calc_rule_id );
	}
	
	// Test 3: Check form submission simulation
	echo '<h3>3. Testing Form Submission</h3>';
	
	// Simulate form data
	$_POST = [
		'_wpnonce' => wp_create_nonce( 'dbs_rule_form' ),
		'action' => 'add',
		'rule_id' => 0,
		'rule_name' => 'Form Test Rule',
		'distance_from' => 0,
		'distance_to' => 30,
		'base_rate' => 8,
		'per_km_rate' => 1.2,
		'min_order_amount' => 0,
		'max_order_amount' => 0,
		'product_categories' => [],
		'shipping_classes' => [],
		'rule_is_active' => 1,
		'rule_priority' => 0,
	];
	
	// Test the form submission handler
	ob_start();
	dbs_handle_rule_form_submission( 'add', 0 );
	$redirect_output = ob_get_clean();
	
	echo '<p style="color: green;">✓ Form submission handler executed</p>';
	echo '<p><strong>Note:</strong> Check if rule was created in admin panel</p>';
	
	echo '</div>';
}

// Add test page to admin menu
add_action( 'admin_menu', function() {
	add_submenu_page(
		'distance-shipping',
		'Shipping Rules Test',
		'Rules Test',
		'manage_options',
		'distance-shipping-rules-test',
		'dbs_test_shipping_rules'
	);
} ); 
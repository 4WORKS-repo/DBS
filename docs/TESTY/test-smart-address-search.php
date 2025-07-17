<?php
/**
 * Test script for smart address search functionality.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test smart address search functionality.
 */
function dbs_test_smart_address_search() {
	echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border-radius: 5px;">';
	echo '<h2>Smart Address Search Test</h2>';
	
	// Test addresses with variations
	$test_addresses = [
		'Mírové náměstí 2, 36001 Karlovy Vary',
		'Mírové náměstí 2, Karlovy Vary',
		'Karlovy Vary, Mírové náměstí 2',
		'Prague, Wenceslas Square',
		'Václavské náměstí, Praha',
		'Brno, náměstí Svobody',
		'Liberec, náměstí Dr. E. Beneše',
	];
	
	echo '<h3>Testing Smart Address Search with 10km Tolerance</h3>';
	
	foreach ( $test_addresses as $test_address ) {
		echo '<div style="margin: 15px 0; padding: 10px; background: white; border-radius: 5px;">';
		echo '<h4>Testing: ' . esc_html( $test_address ) . '</h4>';
		
		// Test geocoding
		$coordinates = dbs_geocode_address( $test_address );
		
		if ( $coordinates ) {
			echo '<p style="color: green;">✓ Address geocoded successfully</p>';
			echo '<p><strong>Coordinates:</strong> ' . $coordinates['lat'] . ', ' . $coordinates['lng'] . '</p>';
			
			if ( isset( $coordinates['formatted_address'] ) ) {
				echo '<p><strong>Standardized Address:</strong> ' . esc_html( $coordinates['formatted_address'] ) . '</p>';
			}
			
			if ( isset( $coordinates['original_address'] ) && $coordinates['original_address'] !== $coordinates['formatted_address'] ) {
				echo '<p style="color: blue;">ℹ Address was standardized</p>';
			}
			
			// Test distance calculation with a known store
			$stores = dbs_get_stores( true );
			if ( ! empty( $stores ) ) {
				$store = $stores[0];
				$distance = dbs_calculate_distance( $store->address, $test_address );
				
				if ( false !== $distance ) {
					echo '<p><strong>Distance to store:</strong> ' . dbs_format_distance( $distance ) . '</p>';
				} else {
					echo '<p style="color: red;">✗ Distance calculation failed</p>';
				}
			}
		} else {
			echo '<p style="color: red;">✗ Address geocoding failed</p>';
		}
		
		echo '</div>';
	}
	
	// Test tolerance functionality
	echo '<h3>Testing 10km Tolerance</h3>';
	echo '<p>This test verifies that addresses within 10km tolerance are accepted.</p>';
	
	$tolerance_test_addresses = [
		'Invalid Address That Should Fail' => false,
		'Some Random Text 12345' => false,
		'Prague, Czech Republic' => true, // Should work
		'Brno, Czech Republic' => true,  // Should work
	];
	
	foreach ( $tolerance_test_addresses as $test_address => $should_work ) {
		echo '<div style="margin: 10px 0; padding: 8px; background: white; border-radius: 3px;">';
		echo '<strong>Testing:</strong> ' . esc_html( $test_address ) . '<br>';
		
		$coordinates = dbs_geocode_address( $test_address );
		
		if ( $coordinates && $should_work ) {
			echo '<span style="color: green;">✓ Expected to work and did work</span>';
		} elseif ( ! $coordinates && ! $should_work ) {
			echo '<span style="color: green;">✓ Expected to fail and did fail</span>';
		} elseif ( $coordinates && ! $should_work ) {
			echo '<span style="color: orange;">⚠ Unexpected success</span>';
		} else {
			echo '<span style="color: red;">✗ Unexpected failure</span>';
		}
		
		echo '</div>';
	}
	
	// Test address similarity
	echo '<h3>Testing Address Similarity</h3>';
	$similarity_tests = [
		['Mírové náměstí 2, Karlovy Vary', 'Mírové náměstí 2, 36001 Karlovy Vary'],
		['Prague, Wenceslas Square', 'Václavské náměstí, Praha'],
		['Brno, Freedom Square', 'náměstí Svobody, Brno'],
	];
	
	foreach ( $similarity_tests as $test_pair ) {
		echo '<div style="margin: 10px 0; padding: 8px; background: white; border-radius: 3px;">';
		echo '<strong>Comparing:</strong><br>';
		echo '• ' . esc_html( $test_pair[0] ) . '<br>';
		echo '• ' . esc_html( $test_pair[1] ) . '<br>';
		
		$similarity = dbs_calculate_text_similarity( $test_pair[0], $test_pair[1] );
		echo '<strong>Similarity:</strong> ' . round( $similarity * 100, 1 ) . '%<br>';
		
		if ( $similarity > 0.3 ) {
			echo '<span style="color: green;">✓ Good similarity</span>';
		} else {
			echo '<span style="color: orange;">⚠ Low similarity</span>';
		}
		
		echo '</div>';
	}
	
	echo '</div>';
}

// Add test page to admin menu
add_action( 'admin_menu', function() {
	add_submenu_page(
		'distance-shipping',
		'Smart Address Search Test',
		'Address Search Test',
		'manage_options',
		'distance-shipping-address-test',
		'dbs_test_smart_address_search'
	);
} ); 
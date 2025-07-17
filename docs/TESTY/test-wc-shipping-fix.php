<?php
/**
 * Test script to verify WooCommerce shipping settings are working properly.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test WooCommerce shipping functionality page.
 */
function dbs_test_wc_shipping_fix_page() {
	echo '<div class="wrap">';
	echo '<h1>WooCommerce Shipping Functionality Test</h1>';
	
	echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border-radius: 5px;">';
	echo '<h2>WooCommerce Shipping Functionality Test</h2>';
	
	// Test 1: Check if WooCommerce is active
	echo '<h3>1. WooCommerce Status</h3>';
	if ( class_exists( 'WooCommerce' ) ) {
		echo '<p style="color: green;">✓ WooCommerce is active</p>';
		echo '<p>• Version: ' . WC()->version . '</p>';
	} else {
		echo '<p style="color: red;">✗ WooCommerce is not active</p>';
		return;
	}
	
	// Test 2: Check shipping zones
	echo '<h3>2. Shipping Zones Test</h3>';
	try {
		$shipping_zones = WC_Shipping_Zones::get_zones();
		if ( is_array( $shipping_zones ) ) {
			echo '<p style="color: green;">✓ Shipping zones are accessible</p>';
			echo '<p>• Found ' . count( $shipping_zones ) . ' shipping zones</p>';
			
			foreach ( $shipping_zones as $zone_id => $zone ) {
				echo '<p>• Zone ' . $zone_id . ': ' . esc_html( $zone['zone_name'] ) . '</p>';
			}
		} else {
			echo '<p style="color: red;">✗ Shipping zones are not accessible</p>';
		}
	} catch ( Exception $e ) {
		echo '<p style="color: red;">✗ Error accessing shipping zones: ' . esc_html( $e->getMessage() ) . '</p>';
	}
	
	// Test 3: Check shipping methods
	echo '<h3>3. Shipping Methods Test</h3>';
	try {
		$shipping_methods = WC()->shipping()->get_shipping_methods();
		if ( is_array( $shipping_methods ) ) {
			echo '<p style="color: green;">✓ Shipping methods are accessible</p>';
			echo '<p>• Found ' . count( $shipping_methods ) . ' shipping methods</p>';
			
			foreach ( $shipping_methods as $method_id => $method ) {
				echo '<p>• Method: ' . esc_html( $method_id ) . ' - ' . esc_html( $method->get_method_title() ) . '</p>';
			}
		} else {
			echo '<p style="color: red;">✗ Shipping methods are not accessible</p>';
		}
	} catch ( Exception $e ) {
		echo '<p style="color: red;">✗ Error accessing shipping methods: ' . esc_html( $e->getMessage() ) . '</p>';
	}
	
	// Test 4: Check if our shipping method is registered
	echo '<h3>4. Distance Based Shipping Method Test</h3>';
	$available_methods = apply_filters( 'woocommerce_shipping_methods', [] );
	if ( isset( $available_methods['distance_based'] ) ) {
		echo '<p style="color: green;">✓ Distance Based Shipping method is registered</p>';
	} else {
		echo '<p style="color: orange;">⚠ Distance Based Shipping method is not registered</p>';
	}
	
	// Test 5: Check admin capabilities
	echo '<h3>5. Admin Capabilities Test</h3>';
	if ( current_user_can( 'manage_woocommerce' ) ) {
		echo '<p style="color: green;">✓ User has WooCommerce management capabilities</p>';
	} else {
		echo '<p style="color: red;">✗ User does not have WooCommerce management capabilities</p>';
	}
	
	// Test 6: Check for any PHP errors
	echo '<h3>6. PHP Error Check</h3>';
	$error_log = ini_get( 'error_log' );
	if ( $error_log && file_exists( $error_log ) ) {
		$recent_errors = file_get_contents( $error_log );
		if ( strpos( $recent_errors, 'WooCommerce' ) !== false || strpos( $recent_errors, 'shipping' ) !== false ) {
			echo '<p style="color: orange;">⚠ Recent errors found in error log related to WooCommerce/shipping</p>';
		} else {
			echo '<p style="color: green;">✓ No recent WooCommerce/shipping errors found</p>';
		}
	} else {
		echo '<p style="color: blue;">ℹ Error log not accessible</p>';
	}
	
	// Test 7: Check plugin hooks
	echo '<h3>7. Plugin Hook Test</h3>';
	$hooks_to_check = [
		'woocommerce_shipping_methods' => has_filter( 'woocommerce_shipping_methods', 'dbs_add_shipping_method' ),
		'woocommerce_checkout_update_order_review' => has_action( 'woocommerce_checkout_update_order_review', 'dbs_handle_address_update' ),
		'woocommerce_after_shipping_calculator' => has_action( 'woocommerce_after_shipping_calculator', 'dbs_display_shipping_info' ),
	];
	
	foreach ( $hooks_to_check as $hook => $has_hook ) {
		if ( $has_hook ) {
			echo '<p style="color: green;">✓ Hook registered: ' . esc_html( $hook ) . '</p>';
		} else {
			echo '<p style="color: blue;">ℹ Hook not registered: ' . esc_html( $hook ) . '</p>';
		}
	}
	
	// Test 8: Check WooCommerce shipping settings accessibility
	echo '<h3>8. WooCommerce Shipping Settings Test</h3>';
	try {
		// Test if we can access WooCommerce shipping settings page
		$shipping_settings_url = admin_url( 'admin.php?page=wc-settings&tab=shipping' );
		echo '<p><strong>Shipping Settings URL:</strong> <a href="' . esc_url( $shipping_settings_url ) . '" target="_blank">' . esc_html( $shipping_settings_url ) . '</a></p>';
		
		// Test if we can access shipping zones
		$shipping_zones_url = admin_url( 'admin.php?page=wc-settings&tab=shipping&section=zones' );
		echo '<p><strong>Shipping Zones URL:</strong> <a href="' . esc_url( $shipping_zones_url ) . '" target="_blank">' . esc_html( $shipping_zones_url ) . '</a></p>';
		
		// Test if current user has access to WooCommerce settings
		if ( current_user_can( 'manage_woocommerce' ) ) {
			echo '<p style="color: green;">✓ User has WooCommerce management permissions</p>';
		} else {
			echo '<p style="color: red;">✗ User does not have WooCommerce management permissions</p>';
		}
		
		// Test if WooCommerce settings are accessible
		if ( class_exists( 'WC_Admin_Settings' ) ) {
			echo '<p style="color: green;">✓ WooCommerce settings class is available</p>';
		} else {
			echo '<p style="color: red;">✗ WooCommerce settings class is not available</p>';
		}
		
	} catch ( Exception $e ) {
		echo '<p style="color: red;">✗ Error testing WooCommerce settings: ' . esc_html( $e->getMessage() ) . '</p>';
	}
	
	// Test 9: Check WooCommerce shipping zones database
	echo '<h3>9. WooCommerce Shipping Zones Database Test</h3>';
	try {
		global $wpdb;
		$zones_table = $wpdb->prefix . 'woocommerce_shipping_zones';
		$zone_locations_table = $wpdb->prefix . 'woocommerce_shipping_zone_locations';
		$zone_methods_table = $wpdb->prefix . 'woocommerce_shipping_zone_methods';
		
		// Check if tables exist
		$zones_exist = $wpdb->get_var( "SHOW TABLES LIKE '{$zones_table}'" );
		$locations_exist = $wpdb->get_var( "SHOW TABLES LIKE '{$zone_locations_table}'" );
		$methods_exist = $wpdb->get_var( "SHOW TABLES LIKE '{$zone_methods_table}'" );
		
		if ( $zones_exist && $locations_exist && $methods_exist ) {
			echo '<p style="color: green;">✓ WooCommerce shipping tables exist</p>';
			
			// Check zone count
			$zone_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$zones_table}" );
			echo '<p>• Found ' . $zone_count . ' shipping zones in database</p>';
			
			// List zones
			$zones = $wpdb->get_results( "SELECT * FROM {$zones_table} ORDER BY zone_order" );
			if ( $zones ) {
				echo '<p><strong>Shipping Zones:</strong></p>';
				echo '<ul>';
				foreach ( $zones as $zone ) {
					echo '<li>Zone ' . $zone->zone_id . ': ' . esc_html( $zone->zone_name ) . '</li>';
				}
				echo '</ul>';
			}
		} else {
			echo '<p style="color: red;">✗ WooCommerce shipping tables missing</p>';
			echo '<p style="color: orange;">⚠ This indicates a WooCommerce installation issue</p>';
		}
		
	} catch ( Exception $e ) {
		echo '<p style="color: red;">✗ Error checking shipping zones database: ' . esc_html( $e->getMessage() ) . '</p>';
	}
	
	// Test 10: Check for any plugin conflicts
	echo '<h3>10. Plugin Conflict Check</h3>';
	try {
		// Check if our plugin hooks are interfering
		$our_hooks = [
			'woocommerce_shipping_methods' => has_filter( 'woocommerce_shipping_methods', 'dbs_add_shipping_method' ),
			'woocommerce_checkout_update_order_review' => has_action( 'woocommerce_checkout_update_order_review', 'dbs_handle_address_update' ),
			'woocommerce_after_shipping_calculator' => has_action( 'woocommerce_after_shipping_calculator', 'dbs_display_shipping_info' ),
		];
		
		foreach ( $our_hooks as $hook => $has_hook ) {
			if ( $has_hook ) {
				echo '<p style="color: green;">✓ Our plugin hook registered: ' . esc_html( $hook ) . '</p>';
			} else {
				echo '<p style="color: blue;">ℹ Our plugin hook not registered: ' . esc_html( $hook ) . '</p>';
			}
		}
		
		// Check for other shipping plugins
		$active_plugins = get_option( 'active_plugins' );
		$shipping_plugins = array_filter( $active_plugins, function( $plugin ) {
			return strpos( $plugin, 'shipping' ) !== false || strpos( $plugin, 'woocommerce' ) !== false;
		} );
		
		if ( count( $shipping_plugins ) > 1 ) {
			echo '<p style="color: orange;">⚠ Found ' . count( $shipping_plugins ) . ' shipping-related plugins:</p>';
			echo '<ul>';
			foreach ( $shipping_plugins as $plugin ) {
				echo '<li>' . esc_html( $plugin ) . '</li>';
			}
			echo '</ul>';
		} else {
			echo '<p style="color: green;">✓ No conflicting shipping plugins detected</p>';
		}
		
	} catch ( Exception $e ) {
		echo '<p style="color: red;">✗ Error checking plugin conflicts: ' . esc_html( $e->getMessage() ) . '</p>';
	}
	
	// Test 11: Check for critical errors
	echo '<h3>11. Critical Error Check</h3>';
	try {
		// Check PHP error log
		$error_log = ini_get( 'error_log' );
		if ( $error_log && file_exists( $error_log ) ) {
			$recent_errors = file_get_contents( $error_log );
			if ( ! empty( $recent_errors ) ) {
				echo '<p style="color: orange;">⚠ Recent errors found in PHP error log:</p>';
				echo '<pre style="background: #f0f0f0; padding: 10px; max-height: 200px; overflow-y: auto;">' . esc_html( substr( $recent_errors, -1000 ) ) . '</pre>';
			} else {
				echo '<p style="color: green;">✓ No recent errors in PHP error log</p>';
			}
		} else {
			echo '<p style="color: blue;">ℹ PHP error log not accessible</p>';
		}
		
		// Check WordPress debug log
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			$debug_log = WP_CONTENT_DIR . '/debug.log';
			if ( file_exists( $debug_log ) ) {
				$debug_errors = file_get_contents( $debug_log );
				if ( ! empty( $debug_errors ) ) {
					echo '<p style="color: orange;">⚠ Recent errors found in WordPress debug log:</p>';
					echo '<pre style="background: #f0f0f0; padding: 10px; max-height: 200px; overflow-y: auto;">' . esc_html( substr( $debug_errors, -1000 ) ) . '</pre>';
				} else {
					echo '<p style="color: green;">✓ No recent errors in WordPress debug log</p>';
				}
			}
		}
		
		// Check for fatal errors in database
		global $wpdb;
		$fatal_errors = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE '%fatal%' OR option_name LIKE '%error%' ORDER BY option_id DESC LIMIT 5" );
		if ( $fatal_errors ) {
			echo '<p style="color: orange;">⚠ Found error-related options in database:</p>';
			foreach ( $fatal_errors as $error ) {
				echo '<p>• ' . esc_html( $error->option_name ) . ': ' . esc_html( substr( $error->option_value, 0, 100 ) ) . '...</p>';
			}
		}
		
	} catch ( Exception $e ) {
		echo '<p style="color: red;">✗ Error checking for critical errors: ' . esc_html( $e->getMessage() ) . '</p>';
	}
	
	// Test 12: Check plugin compatibility
	echo '<h3>12. Plugin Compatibility Check</h3>';
	try {
		// Check if our plugin functions exist
		$required_functions = [
			'dbs_add_shipping_method',
			'dbs_handle_address_update',
			'dbs_display_shipping_info',
			'dbs_enhance_shipping_method_display',
			'dbs_save_distance_info_to_session',
		];
		
		foreach ( $required_functions as $function ) {
			if ( function_exists( $function ) ) {
				echo '<p style="color: green;">✓ Function exists: ' . esc_html( $function ) . '</p>';
			} else {
				echo '<p style="color: red;">✗ Function missing: ' . esc_html( $function ) . '</p>';
			}
		}
		
		// Check if our shipping method class exists
		if ( class_exists( 'DBS_Shipping_Method' ) ) {
			echo '<p style="color: green;">✓ DBS_Shipping_Method class exists</p>';
		} else {
			echo '<p style="color: red;">✗ DBS_Shipping_Method class missing</p>';
		}
		
		// Check if WooCommerce session is available
		if ( function_exists( 'WC' ) && WC()->session ) {
			echo '<p style="color: green;">✓ WooCommerce session is available</p>';
		} else {
			echo '<p style="color: red;">✗ WooCommerce session not available</p>';
		}
		
	} catch ( Exception $e ) {
		echo '<p style="color: red;">✗ Error checking plugin compatibility: ' . esc_html( $e->getMessage() ) . '</p>';
	}
	
	echo '</div>';
	echo '</div>';
} 
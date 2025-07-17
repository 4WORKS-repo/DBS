<?php
/**
 * Standalone fix script for Distance Based Shipping plugin.
 * 
 * This script can be accessed directly to fix plugin issues.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Standalone fix function that can be accessed directly.
 */
function dbs_standalone_fix_page() {
	// Check if user has permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.' );
	}

	echo '<div class="wrap">';
	echo '<h1>Distance Based Shipping - Emergency Fix</h1>';
	
	echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border-radius: 5px; font-family: Arial, sans-serif;">';
	echo '<h1 style="color: #333;">Distance Based Shipping - Standalone Fix</h1>';
	
	$fixes_applied = [];
	
	// Fix 1: Clear problematic transients
	echo '<h3>1. Clearing Problematic Transients</h3>';
	try {
		global $wpdb;
		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->options} 
			 WHERE option_name LIKE '_transient_dbs_%' 
			 OR option_name LIKE '_transient_timeout_dbs_%'"
		);
		$fixes_applied[] = 'Cleared problematic transients (' . $deleted . ' items)';
		echo '<p style="color: green;">✓ Cleared problematic transients (' . $deleted . ' items)</p>';
	} catch ( Exception $e ) {
		echo '<p style="color: red;">✗ Error clearing transients: ' . esc_html( $e->getMessage() ) . '</p>';
	}
	
	// Fix 2: Reset plugin options to defaults
	echo '<h3>2. Resetting Plugin Options</h3>';
	try {
		$default_options = [
			'dbs_map_service'     => 'openstreetmap',
			'dbs_enable_caching'  => '1',
			'dbs_cache_duration'  => '24',
			'dbs_fallback_rate'   => '10',
			'dbs_debug_mode'      => '0',
		];
		
		foreach ( $default_options as $key => $value ) {
			update_option( $key, $value );
		}
		$fixes_applied[] = 'Reset plugin options to defaults';
		echo '<p style="color: green;">✓ Reset plugin options to defaults</p>';
	} catch ( Exception $e ) {
		echo '<p style="color: red;">✗ Error resetting options: ' . esc_html( $e->getMessage() ) . '</p>';
	}
	
	// Fix 3: Clear WordPress cache
	echo '<h3>3. Clearing WordPress Cache</h3>';
	try {
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		if ( function_exists( 'wp_rocket_clean_domain' ) ) {
			wp_rocket_clean_domain();
		}
		$fixes_applied[] = 'Cleared WordPress cache';
		echo '<p style="color: green;">✓ Cleared WordPress cache</p>';
	} catch ( Exception $e ) {
		echo '<p style="color: red;">✗ Error clearing cache: ' . esc_html( $e->getMessage() ) . '</p>';
	}
	
	// Fix 4: Flush rewrite rules
	echo '<h3>4. Flushing Rewrite Rules</h3>';
	try {
		flush_rewrite_rules();
		$fixes_applied[] = 'Flushed rewrite rules';
		echo '<p style="color: green;">✓ Flushed rewrite rules</p>';
	} catch ( Exception $e ) {
		echo '<p style="color: red;">✗ Error flushing rewrite rules: ' . esc_html( $e->getMessage() ) . '</p>';
	}
	
	// Fix 5: Check database tables
	echo '<h3>5. Checking Database Tables</h3>';
	try {
		if ( function_exists( 'dbs_create_database_tables' ) ) {
			dbs_create_database_tables();
			$fixes_applied[] = 'Recreated database tables';
			echo '<p style="color: green;">✓ Database tables checked/recreated</p>';
		} else {
			echo '<p style="color: orange;">⚠ Database functions not available</p>';
		}
	} catch ( Exception $e ) {
		echo '<p style="color: red;">✗ Error with database tables: ' . esc_html( $e->getMessage() ) . '</p>';
	}
	
	// Fix 6: Check WooCommerce functionality
	echo '<h3>6. WooCommerce Functionality Check</h3>';
	try {
		if ( class_exists( 'WooCommerce' ) ) {
			echo '<p style="color: green;">✓ WooCommerce is active</p>';
			
			// Check shipping zones
			$shipping_zones = WC_Shipping_Zones::get_zones();
			if ( is_array( $shipping_zones ) ) {
				echo '<p style="color: green;">✓ Shipping zones are accessible (' . count( $shipping_zones ) . ' zones)</p>';
			} else {
				echo '<p style="color: red;">✗ Shipping zones are not accessible</p>';
			}
			
			// Check shipping methods
			$shipping_methods = WC()->shipping()->get_shipping_methods();
			if ( is_array( $shipping_methods ) ) {
				echo '<p style="color: green;">✓ Shipping methods are accessible (' . count( $shipping_methods ) . ' methods)</p>';
			} else {
				echo '<p style="color: red;">✗ Shipping methods are not accessible</p>';
			}
		} else {
			echo '<p style="color: red;">✗ WooCommerce is not active</p>';
		}
	} catch ( Exception $e ) {
		echo '<p style="color: red;">✗ Error checking WooCommerce: ' . esc_html( $e->getMessage() ) . '</p>';
	}
	
	// Fix 7: Clear WooCommerce shipping cache and conflicts
	echo '<h3>7. WooCommerce Shipping Cache Clear</h3>';
	try {
		if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
			// Clear shipping cache
			WC()->shipping()->reset_shipping_cache();
			$fixes_applied[] = 'Cleared WooCommerce shipping cache';
			echo '<p style="color: green;">✓ Cleared WooCommerce shipping cache</p>';
			
			// Clear any transients that might interfere
			global $wpdb;
			$deleted = $wpdb->query(
				"DELETE FROM {$wpdb->options} 
				 WHERE option_name LIKE '_transient_wc_shipping_%' 
				 OR option_name LIKE '_transient_timeout_wc_shipping_%'"
			);
			if ( $deleted > 0 ) {
				$fixes_applied[] = 'Cleared WooCommerce shipping transients (' . $deleted . ' items)';
				echo '<p style="color: green;">✓ Cleared WooCommerce shipping transients (' . $deleted . ' items)</p>';
			}
		} else {
			echo '<p style="color: orange;">⚠ WooCommerce not available for cache clearing</p>';
		}
	} catch ( Exception $e ) {
		echo '<p style="color: red;">✗ Error clearing WooCommerce cache: ' . esc_html( $e->getMessage() ) . '</p>';
	}
	
	// Fix 8: Restore WooCommerce shipping zones
	echo '<h3>8. Restore WooCommerce Shipping Zones</h3>';
	try {
		if ( class_exists( 'WooCommerce' ) ) {
			// Check if shipping zones table exists and has data
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
				
				// Check if there are any zones
				$zone_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$zones_table}" );
				echo '<p>• Found ' . $zone_count . ' shipping zones in database</p>';
				
				if ( $zone_count == 0 ) {
					// Create default zones if none exist
					echo '<p style="color: orange;">⚠ No shipping zones found, creating default zones...</p>';
					
					// Create "Locations not covered by your other zones" zone
					$wpdb->insert(
						$zones_table,
						array(
							'zone_name' => 'Locations not covered by your other zones',
							'zone_order' => 0
						),
						array( '%s', '%d' )
					);
					
					// Create "Rest of the World" zone
					$wpdb->insert(
						$zones_table,
						array(
							'zone_name' => 'Rest of the World',
							'zone_order' => 1
						),
						array( '%s', '%d' )
					);
					
					$fixes_applied[] = 'Created default WooCommerce shipping zones';
					echo '<p style="color: green;">✓ Created default WooCommerce shipping zones</p>';
				}
			} else {
				echo '<p style="color: red;">✗ WooCommerce shipping tables missing</p>';
				echo '<p style="color: orange;">⚠ This might require WooCommerce reinstallation</p>';
			}
		} else {
			echo '<p style="color: red;">✗ WooCommerce not available</p>';
		}
	} catch ( Exception $e ) {
		echo '<p style="color: red;">✗ Error restoring shipping zones: ' . esc_html( $e->getMessage() ) . '</p>';
	}
	
	// Summary
	echo '<h3>Summary</h3>';
	if ( ! empty( $fixes_applied ) ) {
		echo '<p style="color: green;">✓ Applied ' . count( $fixes_applied ) . ' fixes:</p>';
		echo '<ul>';
		foreach ( $fixes_applied as $fix ) {
			echo '<li>' . esc_html( $fix ) . '</li>';
		}
		echo '</ul>';
		echo '<p><strong>Next steps:</strong></p>';
		echo '<ol>';
		echo '<li>Try accessing the plugin admin pages again</li>';
		echo '<li>If still having issues, deactivate and reactivate the plugin</li>';
		echo '<li>Check the error log for any remaining issues</li>';
		echo '</ol>';
	} else {
		echo '<p style="color: red;">✗ No fixes were applied successfully</p>';
	}
	
	echo '</div>';
	echo '</div>';
} 
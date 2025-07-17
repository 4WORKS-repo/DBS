<?php
/**
 * Test script to verify plugin URLs are working correctly.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test plugin URLs functionality page.
 */
function dbs_test_urls_page() {
	echo '<div class="wrap">';
	echo '<h1>Distance Based Shipping - URL Test</h1>';
	
	echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border-radius: 5px; font-family: Arial, sans-serif;">';
	echo '<h1 style="color: #333;">Distance Based Shipping - URL Test</h1>';
	
	echo '<h3>Plugin URLs Test</h3>';
	
	$urls_to_test = [
		'Main Settings' => admin_url( 'admin.php?page=distance-shipping' ),
		'Store Locations' => admin_url( 'admin.php?page=distance-shipping-stores' ),
		'Shipping Rules' => admin_url( 'admin.php?page=distance-shipping-rules' ),
		'Debug' => admin_url( 'admin.php?page=distance-shipping-debug' ),
		'Distance Debug Test' => admin_url( 'admin.php?page=distance-shipping-debug-distance' ),
		'WC Shipping Test' => admin_url( 'admin.php?page=distance-shipping-wc-test' ),
		'Standalone Fix' => admin_url( 'admin.php?page=dbs-standalone-fix' ),
	];
	
	echo '<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">';
	echo '<tr style="background: #0073aa; color: white;">';
	echo '<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Page</th>';
	echo '<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">URL</th>';
	echo '<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Status</th>';
	echo '</tr>';
	
	foreach ( $urls_to_test as $page_name => $url ) {
		echo '<tr style="border-bottom: 1px solid #ddd;">';
		echo '<td style="padding: 10px; border: 1px solid #ddd;">' . esc_html( $page_name ) . '</td>';
		echo '<td style="padding: 10px; border: 1px solid #ddd;"><a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $url ) . '</a></td>';
		echo '<td style="padding: 10px; border: 1px solid #ddd;">';
		echo '<a href="' . esc_url( $url ) . '" target="_blank" style="background: #0073aa; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">Test URL</a>';
		echo '</td>';
		echo '</tr>';
	}
	
	echo '</table>';
	
	echo '<h3>Instructions</h3>';
	echo '<ol>';
	echo '<li>Click on each "Test URL" link to verify the page loads correctly</li>';
	echo '<li>If any page gives a 404 error, the plugin menu structure needs fixing</li>';
	echo '<li>All pages should load without errors</li>';
	echo '</ol>';
	
	echo '<h3>Expected Results</h3>';
	echo '<ul>';
	echo '<li>✅ Main Settings: Should show the plugin settings page</li>';
	echo '<li>✅ Store Locations: Should show the stores management page</li>';
	echo '<li>✅ Shipping Rules: Should show the shipping rules page</li>';
	echo '<li>✅ Debug: Should show the debug information page</li>';
	echo '<li>✅ Distance Debug Test: Should show distance calculation tests</li>';
	echo '<li>✅ WC Shipping Test: Should show WooCommerce shipping tests</li>';
	echo '<li>✅ Standalone Fix: Should show the fix utility page</li>';
	echo '</ul>';
	
	echo '</div>';
	echo '</div>';
} 
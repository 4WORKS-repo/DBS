<?php
/**
 * Test script to verify cart and checkout integration with smart address handling.
 * 
 * This script tests the integration of distance-based shipping on cart and checkout pages.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Only run in admin
if ( ! is_admin() ) {
    return;
}

function dbs_test_cart_checkout_integration_page() {
    ?>
    <div class="wrap">
        <h1>Test Cart and Checkout Integration</h1>
        
        <div class="card">
            <h2>🎯 Integration Features Test</h2>
            <p>This test verifies that the smart address functionality works correctly on cart and checkout pages.</p>
            
            <h3>✅ Features to Test:</h3>
            <ul>
                <li><strong>Cart Page:</strong> Shipping calculator with smart address handling</li>
                <li><strong>Checkout Page:</strong> Automatic shipping calculation when addresses are entered</li>
                <li><strong>Address Standardization:</strong> Smart address correction and display</li>
                <li><strong>Shipping Rates:</strong> Proper calculation and display of rates</li>
                <li><strong>Total Updates:</strong> Cart totals update with shipping costs</li>
            </ul>
        </div>
        
        <div class="card">
            <h2>🧪 Manual Testing Instructions</h2>
            
            <h3>Cart Page Testing:</h3>
            <ol>
                <li>Go to your shop and add some products to cart</li>
                <li>Navigate to the cart page</li>
                <li>Look for the "📦 Vypočítat dopravní náklady" section</li>
                <li>Enter a test address (e.g., "Prague, Czech Republic")</li>
                <li>Click "Vypočítat dopravu"</li>
                <li>Verify that shipping rates are calculated and displayed</li>
                <li>Check if address standardization info is shown</li>
            </ol>
            
            <h3>Checkout Page Testing:</h3>
            <ol>
                <li>Proceed to checkout from cart</li>
                <li>Fill in billing address fields</li>
                <li>Check if shipping info appears automatically</li>
                <li>Try entering an approximate address (e.g., "Praha" instead of "Prague")</li>
                <li>Verify that the address is standardized and shipping is recalculated</li>
                <li>Check that shipping methods show distance-based options</li>
                <li>Verify that order totals include shipping costs</li>
            </ol>
        </div>
        
        <div class="card">
            <h2>🔧 Technical Verification</h2>
            
            <h3>AJAX Endpoints:</h3>
            <div id="ajax-test-results">
                <p>Click the button below to test AJAX endpoints.</p>
            </div>
            
            <button type="button" id="test-ajax-endpoints" class="button button-primary">
                Test AJAX Endpoints
            </button>
            
            <h3>Database Verification:</h3>
            <div id="database-test-results">
                <p>Click the button below to verify database setup.</p>
            </div>
            
            <button type="button" id="test-database" class="button button-primary">
                Test Database Setup
            </button>
        </div>
        
        <div class="card">
            <h2>📊 Performance Metrics</h2>
            <div id="performance-results">
                <p>Click the button below to test performance.</p>
            </div>
            
            <button type="button" id="test-performance" class="button button-primary">
                Test Performance
            </button>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Test AJAX endpoints
        $('#test-ajax-endpoints').on('click', function() {
            const button = $(this);
            const resultsDiv = $('#ajax-test-results');
            
            button.prop('disabled', true).text('Testing...');
            
            // Test shipping calculation endpoint
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dbs_calculate_shipping',
                    nonce: dbsAdminAjax.nonce,
                    destination: 'Prague, Czech Republic',
                    cart_total: 0
                },
                success: function(response) {
                    let html = '<div class="notice notice-success">';
                    html += '<p><strong>✅ AJAX Endpoints Test Results:</strong></p>';
                    
                    if (response.success) {
                        html += '<p>✅ Shipping calculation endpoint working</p>';
                        html += '<p>✅ Distance: ' + response.data.distance + '</p>';
                        html += '<p>✅ Store: ' + response.data.store + '</p>';
                        html += '<p>✅ Rates found: ' + response.data.total_rates + '</p>';
                        
                        if (response.data.address_standardized) {
                            html += '<p>✅ Address standardization working</p>';
                        }
                    } else {
                        html += '<p>❌ Shipping calculation failed: ' + response.data + '</p>';
                    }
                    
                    html += '</div>';
                    resultsDiv.html(html);
                },
                error: function() {
                    resultsDiv.html(
                        '<div class="notice notice-error">' +
                        '<p><strong>❌ AJAX request failed!</strong></p>' +
                        '</div>'
                    );
                },
                complete: function() {
                    button.prop('disabled', false).text('Test AJAX Endpoints');
                }
            });
        });
        
        // Test database setup
        $('#test-database').on('click', function() {
            const button = $(this);
            const resultsDiv = $('#database-test-results');
            
            button.prop('disabled', true).text('Testing...');
            
            // Test database tables and data
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dbs_test_database',
                    nonce: dbsAdminAjax.nonce
                },
                success: function(response) {
                    let html = '<div class="notice notice-success">';
                    html += '<p><strong>✅ Database Test Results:</strong></p>';
                    
                    if (response.success) {
                        html += '<p>✅ Database tables exist</p>';
                        html += '<p>✅ Shipping rules: ' + response.data.rules_count + '</p>';
                        html += '<p>✅ Stores: ' + response.data.stores_count + '</p>';
                    } else {
                        html += '<p>❌ Database test failed: ' + response.data + '</p>';
                    }
                    
                    html += '</div>';
                    resultsDiv.html(html);
                },
                error: function() {
                    resultsDiv.html(
                        '<div class="notice notice-error">' +
                        '<p><strong>❌ Database test failed!</strong></p>' +
                        '</div>'
                    );
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Database Setup');
                }
            });
        });
        
        // Test performance
        $('#test-performance').on('click', function() {
            const button = $(this);
            const resultsDiv = $('#performance-results');
            
            button.prop('disabled', true).text('Testing...');
            
            const startTime = performance.now();
            
            // Test multiple shipping calculations
            let completed = 0;
            const totalTests = 3;
            const testAddresses = [
                'Prague, Czech Republic',
                'Brno, Czech Republic',
                'Ostrava, Czech Republic'
            ];
            
            testAddresses.forEach(function(address, index) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dbs_calculate_shipping',
                        nonce: dbsAdminAjax.nonce,
                        destination: address,
                        cart_total: 0
                    },
                    success: function(response) {
                        completed++;
                        
                        if (completed === totalTests) {
                            const endTime = performance.now();
                            const duration = endTime - startTime;
                            
                            let html = '<div class="notice notice-success">';
                            html += '<p><strong>✅ Performance Test Results:</strong></p>';
                            html += '<p>✅ Average response time: ' + (duration / totalTests).toFixed(2) + 'ms</p>';
                            html += '<p>✅ Total time: ' + duration.toFixed(2) + 'ms</p>';
                            html += '<p>✅ All ' + totalTests + ' tests completed successfully</p>';
                            html += '</div>';
                            resultsDiv.html(html);
                        }
                    },
                    error: function() {
                        completed++;
                        
                        if (completed === totalTests) {
                            resultsDiv.html(
                                '<div class="notice notice-error">' +
                                '<p><strong>❌ Performance test failed!</strong></p>' +
                                '</div>'
                            );
                        }
                    }
                });
            });
            
            setTimeout(function() {
                button.prop('disabled', false).text('Test Performance');
            }, 5000);
        });
    });
    </script>
    <?php
}

// Add AJAX handler for database test
add_action('wp_ajax_dbs_test_database', 'dbs_ajax_test_database');

function dbs_ajax_test_database() {
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['nonce'], 'dbs_admin_nonce' ) ) {
        wp_send_json_error( 'Security check failed.' );
    }
    
    // Check permissions
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( 'Insufficient permissions.' );
    }
    
    global $wpdb;
    
    try {
        // Check if tables exist
        $rules_table = $wpdb->prefix . 'dbs_shipping_rules';
        $stores_table = $wpdb->prefix . 'dbs_stores';
        
        $rules_count = $wpdb->get_var("SELECT COUNT(*) FROM $rules_table");
        $stores_count = $wpdb->get_var("SELECT COUNT(*) FROM $stores_table");
        
        if ($rules_count === null || $stores_count === null) {
            wp_send_json_error( 'Database tables not found. Please check plugin installation.' );
        }
        
        wp_send_json_success([
            'rules_count' => (int) $rules_count,
            'stores_count' => (int) $stores_count,
            'message' => 'Database setup verified successfully.'
        ]);
        
    } catch (Exception $e) {
        wp_send_json_error( 'Database test failed: ' . $e->getMessage() );
    }
} 
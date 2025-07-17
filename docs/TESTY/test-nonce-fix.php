<?php
/**
 * Test script to verify nonce fix for distance test functionality.
 * 
 * This script tests the AJAX nonce verification for the distance test feature.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Only run in admin
if ( ! is_admin() ) {
    return;
}

function dbs_test_nonce_fix_page() {
    ?>
    <div class="wrap">
        <h1>Test Nonce Fix for Distance Test</h1>
        
        <div class="card">
            <h2>Nonce Verification Test</h2>
            <p>This test verifies that the AJAX nonce verification works correctly for the distance test functionality.</p>
            
            <h3>Test Results:</h3>
            <div id="nonce-test-results">
                <p>Click the button below to test the nonce verification.</p>
            </div>
            
            <button type="button" id="test-nonce-btn" class="button button-primary">
                Test Nonce Verification
            </button>
        </div>
        
        <div class="card">
            <h2>Distance Test with Correct Nonce</h2>
            <p>This test verifies that the distance calculation works with the correct nonce.</p>
            
            <form id="distance-test-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">Origin Address:</th>
                        <td>
                            <input type="text" id="test-origin" name="origin" value="Prague, Czech Republic" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Destination Address:</th>
                        <td>
                            <input type="text" id="test-destination" name="destination" value="Brno, Czech Republic" class="regular-text" />
                        </td>
                    </tr>
                </table>
                
                <button type="submit" class="button button-primary">
                    Test Distance Calculation
                </button>
            </form>
            
            <div id="distance-test-results" style="margin-top: 20px;"></div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Test nonce verification
        $('#test-nonce-btn').on('click', function() {
            const button = $(this);
            const resultsDiv = $('#nonce-test-results');
            
            button.prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dbs_test_distance',
                    nonce: dbsAdminAjax.nonce,
                    origin: 'Prague, Czech Republic',
                    destination: 'Brno, Czech Republic'
                },
                success: function(response) {
                    if (response.success) {
                        resultsDiv.html(
                            '<div class="notice notice-success">' +
                            '<p><strong>✅ Nonce verification passed!</strong></p>' +
                            '<p>Distance: ' + response.data.formatted_distance + '</p>' +
                            '<p>Message: ' + response.data.message + '</p>' +
                            '</div>'
                        );
                    } else {
                        resultsDiv.html(
                            '<div class="notice notice-error">' +
                            '<p><strong>❌ Nonce verification failed!</strong></p>' +
                            '<p>Error: ' + response.data + '</p>' +
                            '</div>'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    resultsDiv.html(
                        '<div class="notice notice-error">' +
                        '<p><strong>❌ AJAX request failed!</strong></p>' +
                        '<p>Status: ' + status + '</p>' +
                        '<p>Error: ' + error + '</p>' +
                        '</div>'
                    );
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Nonce Verification');
                }
            });
        });
        
        // Test distance calculation
        $('#distance-test-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const resultsDiv = $('#distance-test-results');
            
            const origin = $('#test-origin').val().trim();
            const destination = $('#test-destination').val().trim();
            
            if (!origin || !destination) {
                resultsDiv.html(
                    '<div class="notice notice-error">' +
                    '<p>Please enter both addresses.</p>' +
                    '</div>'
                );
                return;
            }
            
            submitBtn.prop('disabled', true).text('Calculating...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dbs_test_distance',
                    nonce: dbsAdminAjax.nonce,
                    origin: origin,
                    destination: destination
                },
                success: function(response) {
                    if (response.success) {
                        let html = '<div class="notice notice-success">';
                        html += '<p><strong>✅ Distance calculation successful!</strong></p>';
                        html += '<p><strong>Distance:</strong> ' + response.data.formatted_distance + '</p>';
                        html += '<p><strong>Unit:</strong> ' + response.data.distance_unit + '</p>';
                        html += '<p><strong>Message:</strong> ' + response.data.message + '</p>';
                        
                        // Show address standardization info if available
                        if (response.data.origin_standardized) {
                            html += '<p><strong>Origin Address Standardized:</strong></p>';
                            html += '<p>Original: ' + response.data.origin_original + '</p>';
                            html += '<p>Used: ' + response.data.origin_used + '</p>';
                        }
                        
                        if (response.data.destination_standardized) {
                            html += '<p><strong>Destination Address Standardized:</strong></p>';
                            html += '<p>Original: ' + response.data.destination_original + '</p>';
                            html += '<p>Used: ' + response.data.destination_used + '</p>';
                        }
                        
                        html += '</div>';
                        resultsDiv.html(html);
                    } else {
                        resultsDiv.html(
                            '<div class="notice notice-error">' +
                            '<p><strong>❌ Distance calculation failed!</strong></p>' +
                            '<p>Error: ' + response.data + '</p>' +
                            '</div>'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    resultsDiv.html(
                        '<div class="notice notice-error">' +
                        '<p><strong>❌ AJAX request failed!</strong></p>' +
                        '<p>Status: ' + status + '</p>' +
                        '<p>Error: ' + error + '</p>' +
                        '</div>'
                    );
                },
                complete: function() {
                    submitBtn.prop('disabled', false).text('Test Distance Calculation');
                }
            });
        });
    });
    </script>
    <?php
} 
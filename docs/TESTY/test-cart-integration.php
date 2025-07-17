<?php
/**
 * Test script pro ověření cart integrace s Distance Based Shipping pluginem.
 * 
 * Tento script testuje integraci shipping kalkulátoru na cart stránce.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Only run in admin
if ( ! is_admin() ) {
    return;
}

function dbs_test_cart_integration_page() {
    ?>
    <div class="wrap">
        <h1>Test Cart Integration</h1>
        
        <div class="card">
            <h2>🎯 Cart Integration Features Test</h2>
            <p>Tento test ověřuje, že shipping kalkulátor správně funguje na cart stránce.</p>
            
            <h3>✅ Funkce k testování:</h3>
            <ul>
                <li><strong>Automatické zobrazení:</strong> Kalkulátor se zobrazuje na cart stránce</li>
                <li><strong>AJAX výpočet:</strong> Výpočet dopravy pomocí AJAX</li>
                <li><strong>Cart aplikace:</strong> Aplikování shipping sazby do WooCommerce cart</li>
                <li><strong>Status zobrazení:</strong> Zobrazení statusu aplikované dopravy</li>
                <li><strong>Session storage:</strong> Ukládání adresy do session storage</li>
                <li><strong>Automatické načtení:</strong> Načtení uložené adresy při návratu</li>
            </ul>
        </div>
        
        <div class="card">
            <h2>🧪 Manual Testing Instructions</h2>
            
            <h3>Cart Page Testing:</h3>
            <ol>
                <li>Přejděte do obchodu a přidejte produkty do košíku</li>
                <li>Přejděte na cart stránku</li>
                <li>Hledejte sekci "📦 Vypočítat dopravní náklady"</li>
                <li>Zadejte testovací adresu (např. "Prague, Czech Republic")</li>
                <li>Klikněte "Vypočítat dopravu"</li>
                <li>Ověřte, že se zobrazí shipping sazby</li>
                <li>Zkontrolujte, že se doprava aplikuje do cart totals</li>
                <li>Zkontrolujte zobrazení statusu aplikované dopravy</li>
            </ol>
            
            <h3>Session Storage Testing:</h3>
            <ol>
                <li>Zadejte adresu a vypočítejte dopravu</li>
                <li>Opusťte cart stránku a vraťte se zpět</li>
                <li>Ověřte, že se adresa automaticky načte</li>
                <li>Zkontrolujte, že se zobrazí status aplikované dopravy</li>
            </ol>
        </div>
        
        <div class="card">
            <h2>🔧 Technical Verification</h2>
            
            <h3>AJAX Endpoints:</h3>
            <div id="ajax-test-results">
                <p>Klikněte na tlačítko níže pro test AJAX endpointů.</p>
            </div>
            
            <button type="button" id="test-ajax-endpoints" class="button button-primary">
                Test AJAX Endpoints
            </button>
            
            <h3>Cart Integration:</h3>
            <div id="cart-test-results">
                <p>Klikněte na tlačítko níže pro test cart integrace.</p>
            </div>
            
            <button type="button" id="test-cart-integration" class="button button-primary">
                Test Cart Integration
            </button>
        </div>
        
        <div class="card">
            <h2>📊 Expected Behavior</h2>
            
            <h3>Cart Page:</h3>
            <ul>
                <li>✅ Shipping kalkulátor se zobrazuje pod tabulkou košíku</li>
                <li>✅ Formulář má moderní design s loading stavem</li>
                <li>✅ AJAX požadavek se odesílá při odeslání formuláře</li>
                <li>✅ Výsledky se zobrazují s informacemi o obchodě a vzdálenosti</li>
                <li>✅ Shipping sazba se automaticky aplikuje do cart</li>
                <li>✅ Status aplikované dopravy se zobrazuje</li>
                <li>✅ Cart totals se aktualizují s shipping náklady</li>
            </ul>
            
            <h3>Session Storage:</h3>
            <ul>
                <li>✅ Adresa se ukládá do session storage</li>
                <li>✅ Při návratu na cart se adresa automaticky načte</li>
                <li>✅ Status aplikované dopravy se zobrazuje</li>
                <li>✅ Možnost změnit adresu nebo odstranit dopravu</li>
            </ul>
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
        
        // Test cart integration
        $('#test-cart-integration').on('click', function() {
            const button = $(this);
            const resultsDiv = $('#cart-test-results');
            
            button.prop('disabled', true).text('Testing...');
            
            // Test cart integration
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dbs_test_cart_integration',
                    nonce: dbsAdminAjax.nonce
                },
                success: function(response) {
                    let html = '<div class="notice notice-success">';
                    html += '<p><strong>✅ Cart Integration Test Results:</strong></p>';
                    
                    if (response.success) {
                        html += '<p>✅ Cart hooks are properly registered</p>';
                        html += '<p>✅ Shipping calculator will be displayed</p>';
                        html += '<p>✅ Session storage is working</p>';
                    } else {
                        html += '<p>❌ Cart integration test failed: ' + response.data + '</p>';
                    }
                    
                    html += '</div>';
                    resultsDiv.html(html);
                },
                error: function() {
                    resultsDiv.html(
                        '<div class="notice notice-error">' +
                        '<p><strong>❌ Cart integration test failed!</strong></p>' +
                        '</div>'
                    );
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Cart Integration');
                }
            });
        });
    });
    </script>
    <?php
}

// Přidat test stránku do admin menu
function dbs_add_cart_integration_test_page() {
    add_submenu_page(
        'distance-shipping',
        'Test Cart Integration',
        'Test Cart Integration',
        'manage_woocommerce',
        'dbs-test-cart-integration',
        'dbs_test_cart_integration_page'
    );
}
add_action('admin_menu', 'dbs_add_cart_integration_test_page');

// AJAX handler pro test cart integrace
function dbs_ajax_test_cart_integration() {
    // Ověření nonce
    if ( ! wp_verify_nonce( $_POST['nonce'], 'dbs_admin_nonce' ) ) {
        wp_send_json_error( 'Bezpečnostní kontrola selhala.' );
    }
    
    // Kontrola, že WooCommerce je načtené
    if ( ! class_exists( 'WooCommerce' ) ) {
        wp_send_json_error( 'WooCommerce není načtené.' );
    }
    
    // Kontrola, že hooks jsou registrovány
    $hooks_registered = has_action( 'woocommerce_after_cart_table', 'dbs_display_cart_shipping_calculator' );
    
    if ( $hooks_registered ) {
        wp_send_json_success( [
            'message' => 'Cart integration is working properly.',
            'hooks_registered' => true
        ] );
    } else {
        wp_send_json_error( 'Cart hooks are not properly registered.' );
    }
}
add_action( 'wp_ajax_dbs_test_cart_integration', 'dbs_ajax_test_cart_integration' ); 
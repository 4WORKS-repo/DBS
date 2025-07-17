<?php
/**
 * Test Checkout Integration
 * 
 * @package Distance_Based_Shipping
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Only load in admin
if (!is_admin()) {
    return;
}

/**
 * Add test page to admin menu
 */
function dbs_add_checkout_test_page() {
    add_submenu_page(
        'dbs-settings',
        'Test Checkout',
        'Test Checkout',
        'manage_options',
        'dbs-test-checkout',
        'dbs_checkout_test_page'
    );
}
add_action('admin_menu', 'dbs_add_checkout_test_page');

/**
 * Test page content
 */
function dbs_checkout_test_page() {
    ?>
    <div class="wrap">
        <h1>Test Checkout Integration</h1>
        <p>Testovací stránka pro checkout integraci pluginu Distance Based Shipping.</p>
        
        <div class="dbs-test-section">
            <h2>1. Test Checkout Kalkulátor</h2>
            <p>Testuje zobrazení kalkulátoru na checkout stránce:</p>
            
            <div class="dbs-test-result">
                <?php
                // Simuluj checkout prostředí
                global $wp_query;
                $wp_query->is_checkout = true;
                
                // Test zobrazení kalkulátoru
                ob_start();
                dbs_display_checkout_calculator();
                $calculator_html = ob_get_clean();
                
                if (!empty($calculator_html)) {
                    echo '<div class="dbs-success">✓ Kalkulátor se zobrazuje správně</div>';
                    echo '<div class="dbs-code-preview">';
                    echo '<h4>HTML výstup:</h4>';
                    echo '<pre>' . esc_html($calculator_html) . '</pre>';
                    echo '</div>';
                } else {
                    echo '<div class="dbs-error">✗ Kalkulátor se nezobrazuje</div>';
                }
                ?>
            </div>
        </div>
        
        <div class="dbs-test-section">
            <h2>2. Test Session Storage</h2>
            <p>Testuje ukládání a načítání dat ze session:</p>
            
            <div class="dbs-test-result">
                <?php
                // Test session storage
                if (function_exists('WC')) {
                    $test_address = 'Testovací adresa 123, Praha, 12000';
                    $test_distance = 15.5;
                    $test_cost = 250.0;
                    
                    // Ulož testovací data
                    WC()->session->set('dbs_shipping_address', $test_address);
                    WC()->session->set('dbs_shipping_distance', $test_distance);
                    WC()->session->set('dbs_shipping_cost', $test_cost);
                    
                    // Načti data
                    $saved_address = WC()->session->get('dbs_shipping_address');
                    $saved_distance = WC()->session->get('dbs_shipping_distance');
                    $saved_cost = WC()->session->get('dbs_shipping_cost');
                    
                    if ($saved_address === $test_address && 
                        $saved_distance === $test_distance && 
                        $saved_cost === $test_cost) {
                        echo '<div class="dbs-success">✓ Session storage funguje správně</div>';
                        echo '<div class="dbs-test-data">';
                        echo '<strong>Uložená adresa:</strong> ' . esc_html($saved_address) . '<br>';
                        echo '<strong>Vzdálenost:</strong> ' . esc_html($saved_distance) . ' km<br>';
                        echo '<strong>Cena:</strong> ' . esc_html($saved_cost) . ' Kč';
                        echo '</div>';
                    } else {
                        echo '<div class="dbs-error">✗ Session storage nefunguje správně</div>';
                    }
                } else {
                    echo '<div class="dbs-error">✗ WooCommerce není dostupné</div>';
                }
                ?>
            </div>
        </div>
        
        <div class="dbs-test-section">
            <h2>3. Test AJAX Handlers</h2>
            <p>Testuje registraci AJAX handlerů:</p>
            
            <div class="dbs-test-result">
                <?php
                $ajax_actions = [
                    'dbs_checkout_calculator',
                    'dbs_apply_checkout_shipping'
                ];
                
                $registered_actions = [];
                foreach ($ajax_actions as $action) {
                    if (has_action("wp_ajax_{$action}") || has_action("wp_ajax_nopriv_{$action}")) {
                        $registered_actions[] = $action;
                    }
                }
                
                if (count($registered_actions) === count($ajax_actions)) {
                    echo '<div class="dbs-success">✓ Všechny AJAX handlery jsou registrovány</div>';
                    echo '<div class="dbs-test-data">';
                    foreach ($registered_actions as $action) {
                        echo '<strong>' . esc_html($action) . '</strong><br>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="dbs-error">✗ Některé AJAX handlery nejsou registrovány</div>';
                    echo '<div class="dbs-test-data">';
                    echo '<strong>Registrováno:</strong> ' . implode(', ', $registered_actions) . '<br>';
                    echo '<strong>Očekáváno:</strong> ' . implode(', ', $ajax_actions);
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        
        <div class="dbs-test-section">
            <h2>4. Test Asset Loading</h2>
            <p>Testuje načítání CSS a JS souborů:</p>
            
            <div class="dbs-test-result">
                <?php
                $assets = [
                    'checkout.js' => DBS_PLUGIN_PATH . 'assets/js/checkout.js',
                    'checkout.css' => DBS_PLUGIN_PATH . 'assets/css/checkout.css'
                ];
                
                $missing_assets = [];
                foreach ($assets as $name => $path) {
                    if (!file_exists($path)) {
                        $missing_assets[] = $name;
                    }
                }
                
                if (empty($missing_assets)) {
                    echo '<div class="dbs-success">✓ Všechny asset soubory existují</div>';
                    echo '<div class="dbs-test-data">';
                    foreach ($assets as $name => $path) {
                        $size = filesize($path);
                        echo '<strong>' . esc_html($name) . '</strong>: ' . esc_html($size) . ' bytes<br>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="dbs-error">✗ Chybí asset soubory</div>';
                    echo '<div class="dbs-test-data">';
                    echo '<strong>Chybí:</strong> ' . implode(', ', $missing_assets);
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        
        <div class="dbs-test-section">
            <h2>5. Test Hook Registration</h2>
            <p>Testuje registraci WordPress hooků:</p>
            
            <div class="dbs-test-result">
                <?php
                $hooks = [
                    'woocommerce_checkout_before_customer_details' => 'dbs_display_checkout_calculator',
                    'woocommerce_checkout_after_customer_details' => 'dbs_display_checkout_shipping_info',
                    'woocommerce_checkout_update_order_review' => 'dbs_validate_checkout_address',
                    'woocommerce_before_checkout_form' => 'dbs_apply_saved_shipping_on_checkout'
                ];
                
                $registered_hooks = [];
                foreach ($hooks as $hook => $callback) {
                    if (has_action($hook, $callback)) {
                        $registered_hooks[] = $hook;
                    }
                }
                
                if (count($registered_hooks) === count($hooks)) {
                    echo '<div class="dbs-success">✓ Všechny hooky jsou registrovány</div>';
                    echo '<div class="dbs-test-data">';
                    foreach ($registered_hooks as $hook) {
                        echo '<strong>' . esc_html($hook) . '</strong><br>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="dbs-error">✗ Některé hooky nejsou registrovány</div>';
                    echo '<div class="dbs-test-data">';
                    echo '<strong>Registrováno:</strong> ' . implode(', ', $registered_hooks) . '<br>';
                    echo '<strong>Očekáváno:</strong> ' . implode(', ', array_keys($hooks));
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        
        <div class="dbs-test-section">
            <h2>6. Test JavaScript Integration</h2>
            <p>Testuje JavaScript funkcionalitu:</p>
            
            <div class="dbs-test-result">
                <div class="dbs-test-js">
                    <h4>Test AJAX požadavků:</h4>
                    <button type="button" id="dbs-test-calculator" class="button">Test Checkout Calculator</button>
                    <button type="button" id="dbs-test-apply-shipping" class="button">Test Apply Shipping</button>
                    <div id="dbs-test-results"></div>
                </div>
            </div>
        </div>
        
        <div class="dbs-test-section">
            <h2>7. Test CSS Styles</h2>
            <p>Testuje CSS styly pro checkout:</p>
            
            <div class="dbs-test-result">
                <div class="dbs-test-css">
                    <h4>Test komponent:</h4>
                    <div class="dbs-checkout-calculator" style="max-width: 500px;">
                        <div class="dbs-calculator-header">
                            <h3>Test Kalkulátor</h3>
                        </div>
                        <div class="dbs-calculator-form">
                            <div class="dbs-form-group">
                                <label>Testovací adresa</label>
                                <input type="text" class="dbs-address-input" value="Testovací adresa 123, Praha">
                            </div>
                            <div class="dbs-form-group">
                                <button type="button" class="dbs-calculate-btn">Test Tlačítko</button>
                            </div>
                        </div>
                        <div class="dbs-calculator-result">
                            <div class="dbs-result-content">
                                <div class="dbs-distance-info">
                                    <span class="dbs-distance-label">Vzdálenost:</span>
                                    <span class="dbs-distance-value">15.5 km</span>
                                </div>
                                <div class="dbs-cost-info">
                                    <span class="dbs-cost-label">Cena dopravy:</span>
                                    <span class="dbs-cost-value">250 Kč</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dbs-test-section">
            <h2>8. Test Error Handling</h2>
            <p>Testuje zpracování chyb:</p>
            
            <div class="dbs-test-result">
                <?php
                // Test chybových stavů
                $error_tests = [
                    'Empty address' => function() {
                        return dbs_validate_checkout_address();
                    },
                    'Invalid session' => function() {
                        if (function_exists('WC') && WC()->session) {
                            WC()->session->__unset('dbs_shipping_address');
                            return true;
                        }
                        return false;
                    }
                ];
                
                foreach ($error_tests as $test_name => $test_func) {
                    try {
                        $result = $test_func();
                        echo '<div class="dbs-success">✓ ' . esc_html($test_name) . ' - OK</div>';
                    } catch (Exception $e) {
                        echo '<div class="dbs-error">✗ ' . esc_html($test_name) . ' - ' . esc_html($e->getMessage()) . '</div>';
                    }
                }
                ?>
            </div>
        </div>
        
        <div class="dbs-test-section">
            <h2>9. Test Performance</h2>
            <p>Testuje výkonnost checkout funkcí:</p>
            
            <div class="dbs-test-result">
                <?php
                // Test výkonnosti
                $start_time = microtime(true);
                
                // Simuluj 100 volání kalkulátoru
                for ($i = 0; $i < 100; $i++) {
                    ob_start();
                    dbs_display_checkout_calculator();
                    ob_end_clean();
                }
                
                $end_time = microtime(true);
                $execution_time = ($end_time - $start_time) * 1000; // v ms
                
                if ($execution_time < 1000) {
                    echo '<div class="dbs-success">✓ Výkonnost je dobrá</div>';
                } else {
                    echo '<div class="dbs-warning">⚠ Výkonnost může být pomalá</div>';
                }
                
                echo '<div class="dbs-test-data">';
                echo '<strong>Čas vykonání:</strong> ' . number_format($execution_time, 2) . ' ms<br>';
                echo '<strong>Průměr na volání:</strong> ' . number_format($execution_time / 100, 2) . ' ms';
                echo '</div>';
                ?>
            </div>
        </div>
        
        <div class="dbs-test-section">
            <h2>10. Test Compatibility</h2>
            <p>Testuje kompatibilitu s různými prostředími:</p>
            
            <div class="dbs-test-result">
                <?php
                $compatibility_tests = [
                    'WooCommerce active' => class_exists('WooCommerce'),
                    'Session available' => function_exists('WC') && WC()->session,
                    'AJAX available' => wp_doing_ajax(),
                    'Admin context' => is_admin(),
                    'Checkout functions loaded' => function_exists('dbs_display_checkout_calculator'),
                    'CSS file exists' => file_exists(DBS_PLUGIN_PATH . 'assets/css/checkout.css'),
                    'JS file exists' => file_exists(DBS_PLUGIN_PATH . 'assets/js/checkout.js')
                ];
                
                $passed_tests = 0;
                foreach ($compatibility_tests as $test_name => $test_result) {
                    if ($test_result) {
                        echo '<div class="dbs-success">✓ ' . esc_html($test_name) . '</div>';
                        $passed_tests++;
                    } else {
                        echo '<div class="dbs-error">✗ ' . esc_html($test_name) . '</div>';
                    }
                }
                
                $total_tests = count($compatibility_tests);
                $success_rate = ($passed_tests / $total_tests) * 100;
                
                echo '<div class="dbs-test-summary">';
                echo '<strong>Úspěšnost:</strong> ' . $passed_tests . '/' . $total_tests . ' (' . number_format($success_rate, 1) . '%)';
                echo '</div>';
                ?>
            </div>
        </div>
    </div>
    
    <style>
        .dbs-test-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #fff;
        }
        
        .dbs-test-result {
            margin: 15px 0;
        }
        
        .dbs-success {
            color: #28a745;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .dbs-error {
            color: #dc3545;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .dbs-warning {
            color: #ffc107;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .dbs-test-data {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
        }
        
        .dbs-code-preview {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .dbs-code-preview pre {
            margin: 0;
            font-size: 11px;
        }
        
        .dbs-test-summary {
            background: #e9ecef;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
            font-weight: bold;
        }
        
        .dbs-test-js button {
            margin: 5px;
        }
        
        #dbs-test-results {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 3px;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        $('#dbs-test-calculator').click(function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dbs_checkout_calculator',
                    address: 'Testovací adresa 123, Praha',
                    nonce: '<?php echo wp_create_nonce("dbs_nonce"); ?>'
                },
                success: function(response) {
                    $('#dbs-test-results').html('<div class="dbs-success">✓ AJAX požadavek úspěšný</div><pre>' + JSON.stringify(response, null, 2) + '</pre>');
                },
                error: function() {
                    $('#dbs-test-results').html('<div class="dbs-error">✗ AJAX požadavek selhal</div>');
                }
            });
        });
        
        $('#dbs-test-apply-shipping').click(function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dbs_apply_checkout_shipping',
                    shipping_method: 'dbs_shipping',
                    shipping_cost: 250,
                    nonce: '<?php echo wp_create_nonce("dbs_nonce"); ?>'
                },
                success: function(response) {
                    $('#dbs-test-results').html('<div class="dbs-success">✓ Apply shipping úspěšný</div><pre>' + JSON.stringify(response, null, 2) + '</pre>');
                },
                error: function() {
                    $('#dbs-test-results').html('<div class="dbs-error">✗ Apply shipping selhal</div>');
                }
            });
        });
    });
    </script>
    <?php
} 
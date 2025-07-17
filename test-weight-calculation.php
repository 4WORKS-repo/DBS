<?php
/**
 * Test váhového výpočtu - ověření že 3kg × 3ks = 9kg
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Test váhového výpočtu
 */
function dbs_test_weight_calculation() {
    // Pouze pro admin a debug mode
    if ( ! is_admin() || ! get_option( 'dbs_debug_mode', 0 ) ) {
        echo '<p>Test je dostupný pouze pro admin v debug módu.</p>';
        return;
    }
    
    echo '<h2>Test váhového výpočtu</h2>';
    echo '<p>Ověřuje, zda se správně počítá hmotnost podle množství produktů.</p>';
    
    // Najít nebo vytvořit testovací produkt
    $test_product = null;
    $products = wc_get_products(['limit' => 1, 'status' => 'publish']);
    
    if (!empty($products)) {
        $test_product = $products[0];
        $original_weight = $test_product->get_weight();
        
        // Nastavit váhu na 3kg pro test
        $test_product->set_weight(3);
        $test_product->save();
        
        echo '<p>Používám produkt: ' . $test_product->get_name() . ' (ID: ' . $test_product->get_id() . ')</p>';
        echo '<p>Nastavil jsem váhu na 3kg pro test</p>';
        
        // Test různých scénářů
        $test_scenarios = [
            ['quantity' => 1, 'expected' => 3],
            ['quantity' => 3, 'expected' => 9],
            ['quantity' => 5, 'expected' => 15],
        ];
        
        echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
        echo '<tr><th>Množství</th><th>Očekávaná váha</th><th>Původní funkce</th><th>Vylepšená funkce</th><th>Status</th></tr>';
        
        foreach ($test_scenarios as $scenario) {
            $quantity = $scenario['quantity'];
            $expected = $scenario['expected'];
            
            // Vytvořit testovací package
            $package = [
                'contents' => [
                    'test_item' => [
                        'data' => $test_product,
                        'quantity' => $quantity,
                        'product_id' => $test_product->get_id(),
                        'variation_id' => 0
                    ]
                ]
            ];
            
            // Test původní funkce (pokud existuje)
            $original_weight = 0;
            if (function_exists('dbs_get_package_weight')) {
                $original_weight = dbs_get_package_weight($package);
            }
            
            // Test vylepšené funkce
            $improved_weight = dbs_get_package_weight_improved($package);
            
            // Určit status
            $status = '';
            if ($improved_weight == $expected) {
                $status = '<span style="color: green;">✅ Správně</span>';
            } else {
                $status = '<span style="color: red;">❌ Chyba</span>';
            }
            
            echo '<tr>';
            echo '<td>' . $quantity . 'ks</td>';
            echo '<td>' . $expected . 'kg</td>';
            echo '<td>' . $original_weight . 'kg</td>';
            echo '<td>' . $improved_weight . 'kg</td>';
            echo '<td>' . $status . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
        // Vrátit původní váhu
        $test_product->set_weight($original_weight);
        $test_product->save();
        
        echo '<p>Váha produktu vrácena na původní hodnotu: ' . $original_weight . 'kg</p>';
        
    } else {
        echo '<p style="color: red;">Žádné produkty nejsou k dispozici pro test</p>';
    }
    
    // Test cache funkcionalita
    echo '<h3>Test cache funkcionalita</h3>';
    
    if (function_exists('dbs_get_cart_hash_improved')) {
        $package1 = [
            'contents' => [
                'test_item' => [
                    'product_id' => 1,
                    'quantity' => 1,
                    'variation_id' => 0
                ]
            ]
        ];
        
        $package2 = [
            'contents' => [
                'test_item' => [
                    'product_id' => 1,
                    'quantity' => 3,
                    'variation_id' => 0
                ]
            ]
        ];
        
        $hash1 = dbs_get_cart_hash_improved($package1);
        $hash2 = dbs_get_cart_hash_improved($package2);
        
        echo '<p>Hash pro 1 kus: ' . substr($hash1, 0, 10) . '...</p>';
        echo '<p>Hash pro 3 kusy: ' . substr($hash2, 0, 10) . '...</p>';
        
        if ($hash1 !== $hash2) {
            echo '<p style="color: green;">✅ Cache hash se liší podle množství (správně)</p>';
        } else {
            echo '<p style="color: red;">❌ Cache hash se neliší podle množství (problém)</p>';
        }
    }
    
    // Test invalidace cache
    echo '<h3>Test invalidace cache</h3>';
    
    if (function_exists('dbs_invalidate_weight_cache')) {
        dbs_invalidate_weight_cache();
        echo '<p style="color: green;">✅ Cache byla invalidována</p>';
    } else {
        echo '<p style="color: red;">❌ Funkce dbs_invalidate_weight_cache neexistuje</p>';
    }
    
    echo '<h3>Závěr</h3>';
    echo '<p>Váhová oprava by měla zajistit:</p>';
    echo '<ul>';
    echo '<li>Správný výpočet hmotnosti podle množství (3kg × 3ks = 9kg)</li>';
    echo '<li>Různé cache hash pro různé množství produktů</li>';
    echo '<li>Automatickou invalidaci cache při změnách košíku</li>';
    echo '</ul>';
} 
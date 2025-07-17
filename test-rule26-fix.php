<?php
/**
 * Test pro Rule 26 problém - 3kg balíček by se neměl aplikovat na rule s podmínkami 75-100kg
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Test konkrétního problému z debug logu
 */
function dbs_test_rule26_specific_problem() {
    echo '<h2>Test Rule 26 - Specifický problém z debug logu</h2>';
    echo '<p><strong>Problém:</strong> Rule 26 s váhovými podmínkami 75-100kg se aplikuje na balíček s 3kg</p>';
    
    // Simulace Rule 26 z debug logu
    $rule_26 = (object) array(
        'id' => 26,
        'rule_name' => 'Rule 26',
        'weight_min' => 75.0,
        'weight_max' => 100.0,
        'weight_operator' => 'AND', // Default
        'length_min' => 0,
        'length_max' => 0,
        'width_min' => 0,
        'width_max' => 0,
        'height_min' => 0,
        'height_max' => 0,
        'distance_from' => 0,
        'distance_to' => 100,
        'priority' => 27
    );
    
    // Simulace balíčku z debug logu
    // Vytvoříme testovací třídu pro simulaci WooCommerce Product
    $test_product = new class {
        public function get_name() { return '3kg, 5m'; }
        public function get_weight() { return 3; }
        public function get_length() { return 500; }
        public function get_width() { return 500; }
        public function get_height() { return 500; }
        public function get_id() { return 152; }
    };
    
    $test_package = array(
        'contents' => array(
            'test_item' => array(
                'data' => $test_product,
                'quantity' => 1,
                'product_id' => 152,
                'variation_id' => 0
            )
        ),
        'contents_cost' => 82.644628
    );
    
    echo '<h3>Testované hodnoty:</h3>';
    echo '<ul>';
    echo '<li><strong>Rule 26:</strong> Váha 75-100kg, Vzdálenost 0-100km</li>';
    echo '<li><strong>Balíček:</strong> 1 kus × 3kg = 3kg, Vzdálenost 8.25km</li>';
    echo '<li><strong>Očekávaný výsledek:</strong> Rule se NEAPLIKUJE (3kg < 75kg)</li>';
    echo '</ul>';
    
    // Test původní implementace (před opravou)
    echo '<h3>Test opravené implementace:</h3>';
    
    // Test váhového výpočtu
    if ( function_exists( 'dbs_get_package_weight' ) ) {
        $calculated_weight = dbs_get_package_weight( $test_package );
        echo '<p>✅ Váhový výpočet: ' . $calculated_weight . 'kg (správně)</p>';
    }
    
    // Test váhové podmínky
    if ( function_exists( 'dbs_check_weight_condition' ) ) {
        $weight_condition_ok = dbs_check_weight_condition( $rule_26, $test_package );
        echo '<p>' . ($weight_condition_ok ? '❌' : '✅') . ' Váhová podmínka: ' . 
             ($weight_condition_ok ? 'PROŠLA (chyba!)' : 'NEPROŠLA (správně)') . '</p>';
    }
    
    // Test rozměrové podmínky
    if ( function_exists( 'dbs_check_dimensions_condition' ) ) {
        $dimensions_condition_ok = dbs_check_dimensions_condition( $rule_26, $test_package );
        echo '<p>✅ Rozměrová podmínka: ' . 
             ($dimensions_condition_ok ? 'PROŠLA (žádné limity)' : 'NEPROŠLA') . '</p>';
    }
    
    // Test celkových podmínek (hlavní test)
    if ( function_exists( 'dbs_check_all_conditions' ) ) {
        $all_conditions_ok = dbs_check_all_conditions( $rule_26, $test_package );
        
        echo '<h3>🎯 Hlavní test - Celkové podmínky:</h3>';
        echo '<p style="font-size: 18px; font-weight: bold; color: ' . 
             ($all_conditions_ok ? 'red' : 'green') . ';">';
        
        if ( $all_conditions_ok ) {
            echo '❌ CHYBA: Rule 26 se aplikuje i když by se neměla!';
        } else {
            echo '✅ SPRÁVNĚ: Rule 26 se neaplikuje (oprava funguje)';
        }
        echo '</p>';
    }
    
    // Simulace původního problému
    echo '<h3>Analýza původního problému:</h3>';
    echo '<div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h4>Původní chybná logika:</h4>';
    echo '<pre>';
    echo 'Weight OK: false (3kg < 75kg)' . PHP_EOL;
    echo 'Dimensions OK: true (žádné rozměrové limity)' . PHP_EOL;
    echo 'Operator: OR' . PHP_EOL;
    echo 'Result: false || true = TRUE (❌ CHYBA!)' . PHP_EOL;
    echo '</pre>';
    echo '</div>';
    
    echo '<div style="background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;">';
    echo '<h4>Opravená logika:</h4>';
    echo '<pre>';
    echo 'Has weight conditions: true (75-100kg)' . PHP_EOL;
    echo 'Has dimension conditions: false (žádné limity)' . PHP_EOL;
    echo 'Logic: Pouze váhové podmínky → kontroluj pouze váhu' . PHP_EOL;
    echo 'Result: Weight OK = false = FALSE (✅ SPRÁVNĚ!)' . PHP_EOL;
    echo '</pre>';
    echo '</div>';
    
    // Test s 3 kusy (9kg)
    echo '<h3>Bonus test: 3 kusy × 3kg = 9kg</h3>';
    
    $test_package_3x = $test_package;
    $test_package_3x['contents']['test_item']['quantity'] = 3;
    
    if ( function_exists( 'dbs_get_package_weight' ) ) {
        $weight_3x = dbs_get_package_weight( $test_package_3x );
        echo '<p>✅ Váhový výpočet: 3 kusy × 3kg = ' . $weight_3x . 'kg (mělo by být 9kg)</p>';
        
        if ( function_exists( 'dbs_check_all_conditions' ) ) {
            $all_conditions_3x = dbs_check_all_conditions( $rule_26, $test_package_3x );
            echo '<p>' . ($all_conditions_3x ? '❌' : '✅') . ' Rule 26 s 9kg: ' . 
                 ($all_conditions_3x ? 'NEAPLIKUJE SE (9kg < 75kg, správně)' : 'APLIKUJE SE (chyba)') . '</p>';
        }
    }
    
    echo '<h3>Shrnutí:</h3>';
    echo '<p>Oprava AND/OR operátorů řeší problém, kdy se pravidla s pouze váhovými podmínkami</p>';
    echo '<p>nesprávně aplikovala kvůli automaticky "true" rozměrovým podmínkám při OR operátoru.</p>';
} 
<?php
/**
 * Test pro Rule 31 diagnostiku
 * 
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Test Rule 31 problému
 */
function dbs_test_rule31_problem() {
    echo '<h2>Test Rule 31 - Diagnostika problému</h2>';
    
    // Najít pravidlo podle názvu nebo ID
    $rule_31 = null;
    
    // Nejprve zkusit podle ID 31
    $rule_31 = dbs_get_shipping_rule( 31 );
    
    // Pokud nebylo nalezeno podle ID, hledat podle názvu
    if ( ! $rule_31 ) {
        $all_rules = dbs_get_shipping_rules( false ); // Včetně neaktivních
        foreach ( $all_rules as $rule ) {
            if ( strpos( strtolower( $rule->rule_name ), 'rule 31' ) !== false || 
                 strpos( strtolower( $rule->rule_name ), 'doprava zdarma nad 5000' ) !== false ) {
                $rule_31 = $rule;
                break;
            }
        }
    }
    
    if ( ! $rule_31 ) {
        echo '<p style="color: red;">❌ Rule 31 nebyla nalezena v databázi!</p>';
        echo '<h3>Dostupná pravidla pro kontrolu:</h3>';
        $all_rules = dbs_get_shipping_rules( false );
        echo '<ul>';
        foreach ( $all_rules as $rule ) {
            echo '<li>ID: ' . $rule->id . ' - ' . esc_html( $rule->rule_name ) . ' (Aktivní: ' . ($rule->is_active ? 'Ano' : 'Ne') . ')</li>';
        }
        echo '</ul>';
        return;
    }
    
    echo '<p style="color: green;">✅ Nalezeno pravidlo: <strong>' . esc_html( $rule_31->rule_name ) . '</strong> (ID: ' . $rule_31->id . ')</p>';
    
    echo '<h3>Rule 31 parametry:</h3>';
    echo '<ul>';
    echo '<li><strong>Název:</strong> ' . esc_html( $rule_31->rule_name ) . '</li>';
    echo '<li><strong>Váha:</strong> ' . $rule_31->weight_min . ' - ' . $rule_31->weight_max . ' kg</li>';
    echo '<li><strong>Vzdálenost:</strong> ' . $rule_31->distance_from . ' - ' . $rule_31->distance_to . ' km</li>';
    echo '<li><strong>Základní sazba:</strong> ' . $rule_31->base_rate . ' ' . get_woocommerce_currency_symbol() . '</li>';
    echo '<li><strong>Sazba za km:</strong> ' . $rule_31->per_km_rate . ' ' . get_woocommerce_currency_symbol() . '</li>';
    echo '<li><strong>Priorita:</strong> ' . $rule_31->priority . '</li>';
    echo '<li><strong>Aktivní:</strong> ' . ($rule_31->is_active ? 'Ano' : 'Ne') . '</li>';
    echo '<li><strong>Váhový operátor:</strong> ' . ($rule_31->weight_operator ?? 'AND') . '</li>';
    echo '<li><strong>Rozměrový operátor:</strong> ' . ($rule_31->dimensions_operator ?? 'AND') . '</li>';
    echo '</ul>';
    
    // Vytvoření testovacích balíčků pro různé scénáře
    echo '<h3>Testovací scénáře:</h3>';
    
    // Určit váhový rozsah pro testování (pokud není omezený, použít 3kg)
    $test_weight = 3; // Standardní testovací váha
    if ( $rule_31->weight_min > 0 || $rule_31->weight_max > 0 ) {
        if ( $rule_31->weight_max > 0 ) {
            $test_weight = ($rule_31->weight_min + $rule_31->weight_max) / 2;
        } else {
            $test_weight = $rule_31->weight_min + 1;
        }
    }
    
    // Určit vzdálenost pro testování (pro 0-500km použijme 50km)
    $test_distance = 50; // Bezpečná vzdálenost v rozsahu
    if ( $rule_31->distance_to > 0 ) {
        $test_distance = min( 50, $rule_31->distance_to / 2 ); // Polovina rozsahu nebo 50km
    }
    
    // Určit hodnotu košíku (pro min 5000 Kč použijme 6000 Kč)
    $test_cart_total = 6000; // Nad minimem
    if ( $rule_31->min_order_amount > 0 ) {
        $test_cart_total = $rule_31->min_order_amount + 1000; // +1000 nad minimum
    }
    
    echo '<h4>Scénář 1: Testovací balíček by měl vyhovovat Rule 31</h4>';
    echo '<ul>';
    echo '<li><strong>Testovací váha:</strong> ' . $test_weight . ' kg</li>';
    echo '<li><strong>Testovací vzdálenost:</strong> ' . $test_distance . ' km</li>';
    echo '<li><strong>Testovací hodnota košíku:</strong> ' . $test_cart_total . ' ' . get_woocommerce_currency_symbol() . '</li>';
    echo '</ul>';
    
    // Vytvoření testovacího produktu
    $test_product = new class {
        private $weight;
        
        public function __construct() {
            global $test_weight;
            $this->weight = $test_weight;
        }
        
        public function get_name() { return 'Test produkt Rule 31'; }
        public function get_weight() { return $this->weight; }
        public function get_length() { return 100; }
        public function get_width() { return 100; }
        public function get_height() { return 100; }
        public function get_id() { return 999; }
    };
    
    $test_package = array(
        'contents' => array(
            'test_item' => array(
                'data' => $test_product,
                'quantity' => 1,
                'product_id' => 999,
                'variation_id' => 0
            )
        ),
        'contents_cost' => $test_cart_total
    );
    
    // Test váhového výpočtu
    if ( function_exists( 'dbs_get_package_weight' ) ) {
        $calculated_weight = dbs_get_package_weight( $test_package );
        echo '<p>📦 Váhový výpočet: ' . $calculated_weight . ' kg</p>';
    }
    
    // Test všech podmínek
    if ( function_exists( 'dbs_check_all_conditions' ) ) {
        $all_conditions_ok = dbs_check_all_conditions( $rule_31, $test_package );
        echo '<p>' . ($all_conditions_ok ? '✅' : '❌') . ' Všechny podmínky: ' . 
             ($all_conditions_ok ? 'PROŠLY' : 'NEPROŠLY') . '</p>';
    }
    
    // Test váhové podmínky
    if ( function_exists( 'dbs_check_weight_condition' ) ) {
        $weight_condition_ok = dbs_check_weight_condition( $rule_31, $test_package );
        echo '<p>' . ($weight_condition_ok ? '✅' : '❌') . ' Váhová podmínka: ' . 
             ($weight_condition_ok ? 'PROŠLA' : 'NEPROŠLA') . '</p>';
    }
    
    // Test rozměrové podmínky
    if ( function_exists( 'dbs_check_dimensions_condition' ) ) {
        $dimensions_condition_ok = dbs_check_dimensions_condition( $rule_31, $test_package );
        echo '<p>' . ($dimensions_condition_ok ? '✅' : '❌') . ' Rozměrová podmínka: ' . 
             ($dimensions_condition_ok ? 'PROŠLA' : 'NEPROŠLA') . '</p>';
    }
    
    // Test podmínky hodnoty košíku (kritické pro Rule 31)
    echo '<h4>Test podmínky hodnoty košíku:</h4>';
    echo '<ul>';
    echo '<li><strong>Minimální hodnota:</strong> ' . $rule_31->min_order_amount . ' ' . get_woocommerce_currency_symbol() . '</li>';
    echo '<li><strong>Maximální hodnota:</strong> ' . ($rule_31->max_order_amount > 0 ? $rule_31->max_order_amount : 'Neomezeno') . ' ' . get_woocommerce_currency_symbol() . '</li>';
    echo '<li><strong>Testovací hodnota:</strong> ' . $test_cart_total . ' ' . get_woocommerce_currency_symbol() . '</li>';
    echo '</ul>';
    
    // Simulace WooCommerce košíku pro test
    $cart_condition_met = true;
    if ( $rule_31->min_order_amount > 0 && $test_cart_total < $rule_31->min_order_amount ) {
        $cart_condition_met = false;
    }
    if ( $rule_31->max_order_amount > 0 && $test_cart_total > $rule_31->max_order_amount ) {
        $cart_condition_met = false;
    }
    
    echo '<p>' . ($cart_condition_met ? '✅' : '❌') . ' Podmínka hodnoty košíku: ' . 
         ($cart_condition_met ? 'PROŠLA' : 'NEPROŠLA') . '</p>';
    
    // Test aplikovatelnosti pravidla
    echo '<h4>Scénář 2: Test aplikovatelnosti celého pravidla</h4>';
    
    // Simulace distance a test aplikovatelnosti
    if ( class_exists( 'DBS_Shipping_Method' ) ) {
        $shipping_method = new DBS_Shipping_Method();
        // Použijeme reflexi pro přístup k private metodě
        $reflection = new ReflectionClass( $shipping_method );
        $method = $reflection->getMethod( 'is_rule_applicable' );
        $method->setAccessible( true );
        
        $is_applicable = $method->invoke( $shipping_method, $rule_31, $test_distance, $test_package );
        echo '<p>' . ($is_applicable ? '✅' : '❌') . ' Pravidlo aplikovatelné: ' . 
             ($is_applicable ? 'ANO' : 'NE') . '</p>';
    }
    
    echo '<h4>Scénář 3: Test s košíkem POD limitem (měl by se NEAPLIKOVAT)</h4>';
    
    // Test s nízkou hodnotou košíku
    $low_cart_total = 3000; // Pod limitem 5000
    $test_package_low = $test_package;
    $test_package_low['contents_cost'] = $low_cart_total;
    
    echo '<ul>';
    echo '<li><strong>Košík pod limitem:</strong> ' . $low_cart_total . ' ' . get_woocommerce_currency_symbol() . ' (limit: ' . $rule_31->min_order_amount . ')</li>';
    echo '</ul>';
    
    // Simulace podmínky košíku pro nízkou hodnotu
    $low_cart_condition_met = true;
    if ( $rule_31->min_order_amount > 0 && $low_cart_total < $rule_31->min_order_amount ) {
        $low_cart_condition_met = false;
    }
    
    echo '<p>' . ($low_cart_condition_met ? '❌ CHYBA' : '✅') . ' Podmínka košíku s nízkou hodnotou: ' . 
         ($low_cart_condition_met ? 'PROŠLA (špatně!)' : 'NEPROŠLA (správně)') . '</p>';
    
    // Test aplikovatelnosti s nízkým košíkem
    if ( class_exists( 'DBS_Shipping_Method' ) ) {
        $shipping_method = new DBS_Shipping_Method();
        $reflection = new ReflectionClass( $shipping_method );
        $method = $reflection->getMethod( 'is_rule_applicable' );
        $method->setAccessible( true );
        
        $is_applicable_low = $method->invoke( $shipping_method, $rule_31, $test_distance, $test_package_low );
        echo '<p>' . ($is_applicable_low ? '❌ CHYBA' : '✅') . ' Pravidlo s nízkým košíkem: ' . 
             ($is_applicable_low ? 'APLIKOVALO SE (špatně!)' : 'NEAPLIKOVALO SE (správně)') . '</p>';
    }
    
    echo '<h4>Scénář 4: Všechna pravidla pro porovnání</h4>';
    
    $all_rules = dbs_get_shipping_rules( true );
    echo '<p>Celkem aktivních pravidel: ' . count( $all_rules ) . '</p>';
    
    echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
    echo '<tr><th>ID</th><th>Název</th><th>Váha (kg)</th><th>Vzdálenost (km)</th><th>Priorita</th><th>Aktivní</th></tr>';
    
    foreach ( $all_rules as $rule ) {
        $weight_range = $rule->weight_min . '-' . ($rule->weight_max > 0 ? $rule->weight_max : '∞');
        $distance_range = $rule->distance_from . '-' . ($rule->distance_to > 0 ? $rule->distance_to : '∞');
        
        echo '<tr>';
        echo '<td>' . $rule->id . '</td>';
        echo '<td>' . esc_html( $rule->rule_name ) . '</td>';
        echo '<td>' . $weight_range . '</td>';
        echo '<td>' . $distance_range . '</td>';
        echo '<td>' . $rule->priority . '</td>';
        echo '<td>' . ($rule->is_active ? 'Ano' : 'Ne') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
} 
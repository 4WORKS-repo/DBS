<?php
/**
 * Oprava AND/OR operátorů pro váhové a rozměrové podmínky
 * 
 * Opravuje problém kdy se nerespektovaly AND/OR operátory při kombinaci
 * hmotnostních a rozměrových podmínek v shipping rules
 * 
 * @package DistanceBasedShipping
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Opravená kontrola hmotnostních podmínek s respektováním operátorů
 * 
 * @param object $rule Shipping rule
 * @param array $package WooCommerce package
 * @return bool True pokud podmínka je splněna
 */
function dbs_check_weight_condition_with_operators( $rule, $package ) {
    // Použití vylepšené funkce pro výpočet hmotnosti
    $package_weight = function_exists( 'dbs_get_package_weight_improved' ) 
        ? dbs_get_package_weight_improved( $package )
        : dbs_get_package_weight( $package );
    
    $weight_min = isset( $rule->weight_min ) ? (float) $rule->weight_min : 0;
    $weight_max = isset( $rule->weight_max ) ? (float) $rule->weight_max : 0;
    $weight_operator = isset( $rule->weight_operator ) ? $rule->weight_operator : 'AND';
    
    // Debug informace
    if ( get_option( 'dbs_debug_mode', 0 ) ) {
        error_log( sprintf( 
            'DBS Operators Fix: Weight condition - Package: %skg, Min: %skg, Max: %skg, Operator: %s',
            $package_weight,
            $weight_min,
            $weight_max,
            $weight_operator
        ) );
    }
    
    // Pokud nejsou nastaveny hmotnostní limity, podmínka je splněna
    if ( $weight_min <= 0 && $weight_max <= 0 ) {
        return true;
    }
    
    // Kontrola minimální hmotnosti
    $min_condition = ( $weight_min <= 0 ) || ( $package_weight >= $weight_min );
    
    // Kontrola maximální hmotnosti
    $max_condition = ( $weight_max <= 0 ) || ( $package_weight <= $weight_max );
    
    // Aplikace operátoru
    $result = false;
    if ( $weight_operator === 'OR' ) {
        // OR: splněna pokud je splněna alespoň jedna podmínka
        if ( $weight_min > 0 && $weight_max > 0 ) {
            $result = $min_condition || $max_condition;
        } elseif ( $weight_min > 0 ) {
            $result = $min_condition;
        } elseif ( $weight_max > 0 ) {
            $result = $max_condition;
        } else {
            $result = true;
        }
    } else {
        // AND: splněna pokud jsou splněny všechny podmínky
        $result = $min_condition && $max_condition;
    }
    
    if ( get_option( 'dbs_debug_mode', 0 ) ) {
        error_log( sprintf( 
            'DBS Operators Fix: Weight result - Min: %s, Max: %s, Operator: %s, Result: %s',
            $min_condition ? 'true' : 'false',
            $max_condition ? 'true' : 'false',
            $weight_operator,
            $result ? 'true' : 'false'
        ) );
    }
    
    return $result;
}

/**
 * Opravená kontrola rozměrových podmínek s respektováním operátorů
 * 
 * @param object $rule Shipping rule
 * @param array $package WooCommerce package
 * @return bool True pokud podmínka je splněna
 */
function dbs_check_dimensions_condition_with_operators( $rule, $package ) {
    // Získání rozměrů balíčku
    $package_dimensions = dbs_get_package_dimensions( $package );
    
    $length_min = isset( $rule->length_min ) ? (float) $rule->length_min : 0;
    $length_max = isset( $rule->length_max ) ? (float) $rule->length_max : 0;
    $width_min = isset( $rule->width_min ) ? (float) $rule->width_min : 0;
    $width_max = isset( $rule->width_max ) ? (float) $rule->width_max : 0;
    $height_min = isset( $rule->height_min ) ? (float) $rule->height_min : 0;
    $height_max = isset( $rule->height_max ) ? (float) $rule->height_max : 0;
    $dimensions_operator = isset( $rule->dimensions_operator ) ? $rule->dimensions_operator : 'AND';
    
    // Debug informace
    if ( get_option( 'dbs_debug_mode', 0 ) ) {
        error_log( sprintf( 
            'DBS Operators Fix: Dimensions condition - Package: %sx%sx%s, Operator: %s',
            $package_dimensions['length'],
            $package_dimensions['width'],
            $package_dimensions['height'],
            $dimensions_operator
        ) );
    }
    
    // Pokud nejsou nastaveny rozměrové limity, podmínka je splněna
    if ( $length_min <= 0 && $length_max <= 0 && 
         $width_min <= 0 && $width_max <= 0 && 
         $height_min <= 0 && $height_max <= 0 ) {
        return true;
    }
    
    // Kontrola jednotlivých rozměrů
    $length_ok = ( $length_min <= 0 || $package_dimensions['length'] >= $length_min ) &&
                 ( $length_max <= 0 || $package_dimensions['length'] <= $length_max );
    
    $width_ok = ( $width_min <= 0 || $package_dimensions['width'] >= $width_min ) &&
                ( $width_max <= 0 || $package_dimensions['width'] <= $width_max );
    
    $height_ok = ( $height_min <= 0 || $package_dimensions['height'] >= $height_min ) &&
                 ( $height_max <= 0 || $package_dimensions['height'] <= $height_max );
    
    // Aplikace operátoru
    $result = false;
    if ( $dimensions_operator === 'OR' ) {
        // OR: splněna pokud je splněna alespoň jedna rozměrová podmínka
        $result = $length_ok || $width_ok || $height_ok;
    } else {
        // AND: splněna pokud jsou splněny všechny rozměrové podmínky
        $result = $length_ok && $width_ok && $height_ok;
    }
    
    if ( get_option( 'dbs_debug_mode', 0 ) ) {
        error_log( sprintf( 
            'DBS Operators Fix: Dimensions result - Length: %s, Width: %s, Height: %s, Operator: %s, Result: %s',
            $length_ok ? 'true' : 'false',
            $width_ok ? 'true' : 'false',
            $height_ok ? 'true' : 'false',
            $dimensions_operator,
            $result ? 'true' : 'false'
        ) );
    }
    
    return $result;
}

/**
 * Opravená kontrola všech podmínek s respektováním operátorů
 * 
 * @param object $rule Shipping rule
 * @param array $package WooCommerce package
 * @return bool True pokud jsou splněny všechny podmínky
 */
function dbs_check_all_conditions_with_fixed_operators( $rule, $package ) {
    // Kontrola hmotnostních podmínek
    $weight_ok = dbs_check_weight_condition_with_operators( $rule, $package );
    
    // Kontrola rozměrových podmínek
    $dimensions_ok = dbs_check_dimensions_condition_with_operators( $rule, $package );
    
    // Kontrola dalších podmínek (kategorie, shipping classes, hodnota objednávky)
    $other_conditions_ok = true;
    
    // Kategorie produktů
    if ( isset( $rule->product_categories ) && ! empty( $rule->product_categories ) ) {
        $other_conditions_ok = $other_conditions_ok && dbs_check_product_categories( $rule, $package );
    }
    
    // Shipping classes
    if ( isset( $rule->shipping_classes ) && ! empty( $rule->shipping_classes ) ) {
        $other_conditions_ok = $other_conditions_ok && dbs_check_shipping_classes( $rule, $package );
    }
    
    // Hodnota objednávky
    if ( isset( $rule->min_order_amount ) && $rule->min_order_amount > 0 ) {
        $order_value = isset( $package['contents_cost'] ) ? $package['contents_cost'] : 0;
        $other_conditions_ok = $other_conditions_ok && ( $order_value >= $rule->min_order_amount );
    }
    
    if ( isset( $rule->max_order_amount ) && $rule->max_order_amount > 0 ) {
        $order_value = isset( $package['contents_cost'] ) ? $package['contents_cost'] : 0;
        $other_conditions_ok = $other_conditions_ok && ( $order_value <= $rule->max_order_amount );
    }
    
    // Zkontrolovat, které podmínky jsou skutečně definované
    $has_weight_conditions = ( isset( $rule->weight_min ) && $rule->weight_min > 0 ) || 
                            ( isset( $rule->weight_max ) && $rule->weight_max > 0 );
    
    $has_dimension_conditions = ( isset( $rule->length_min ) && $rule->length_min > 0 ) ||
                               ( isset( $rule->length_max ) && $rule->length_max > 0 ) ||
                               ( isset( $rule->width_min ) && $rule->width_min > 0 ) ||
                               ( isset( $rule->width_max ) && $rule->width_max > 0 ) ||
                               ( isset( $rule->height_min ) && $rule->height_min > 0 ) ||
                               ( isset( $rule->height_max ) && $rule->height_max > 0 );
    
    // Kombinace váhových a rozměrových podmínek
    $weight_operator = isset( $rule->weight_operator ) ? $rule->weight_operator : 'AND';
    
    $physical_conditions_ok = true; // Default pro případ, že nejsou žádné podmínky
    
    if ( $has_weight_conditions && $has_dimension_conditions ) {
        // Obě podmínky jsou definované - použij operátor
        if ( $weight_operator === 'OR' ) {
            $physical_conditions_ok = $weight_ok || $dimensions_ok;
        } else {
            $physical_conditions_ok = $weight_ok && $dimensions_ok;
        }
    } elseif ( $has_weight_conditions ) {
        // Pouze váhové podmínky - kontroluj pouze váhu
        $physical_conditions_ok = $weight_ok;
    } elseif ( $has_dimension_conditions ) {
        // Pouze rozměrové podmínky - kontroluj pouze rozměry
        $physical_conditions_ok = $dimensions_ok;
    }
    // Pokud nejsou žádné fyzické podmínky, physical_conditions_ok zůstává true
    
    // Celkový výsledek
    $result = $physical_conditions_ok && $other_conditions_ok;
    
    if ( get_option( 'dbs_debug_mode', 0 ) ) {
        error_log( sprintf( 
            'DBS Operators Fix: All conditions - Weight: %s (has conditions: %s), Dimensions: %s (has conditions: %s), Others: %s, Physical operator: %s, Physical result: %s, Final result: %s',
            $weight_ok ? 'true' : 'false',
            $has_weight_conditions ? 'true' : 'false',
            $dimensions_ok ? 'true' : 'false',
            $has_dimension_conditions ? 'true' : 'false',
            $other_conditions_ok ? 'true' : 'false',
            $weight_operator,
            $physical_conditions_ok ? 'true' : 'false',
            $result ? 'true' : 'false'
        ) );
    }
    
    return $result;
}

/**
 * Funkce pro získání rozměrů balíčku - používá existující implementaci z product-functions.php
 * Duplikátní funkce odstraněna kvůli konfliktu s product-functions.php:98
 * 
 * Existující implementace je dostupná v product-functions.php a je více robustní
 */

/**
 * Test funkcionalita operátorů
 * 
 * @return array Výsledky testů
 */
function dbs_test_weight_operators_fix() {
    $results = array();
    
    // Test 1: Pravidlo pouze s váhovými podmínkami (Rule 26 problém)
    $test_rule_weight_only = (object) array(
        'weight_min' => 75,
        'weight_max' => 100,
        'weight_operator' => 'AND',
        'length_min' => 0,
        'length_max' => 0,
        'width_min' => 0,
        'width_max' => 0,
        'height_min' => 0,
        'height_max' => 0
    );
    
    $test_package_3kg = array(
        'contents' => array(
            array(
                'data' => (object) array( 'get_weight' => function() { return 3; } ),
                'quantity' => 1
            )
        )
    );
    
    $result_weight_only = dbs_check_all_conditions_with_fixed_operators( $test_rule_weight_only, $test_package_3kg );
    $results['weight_only_rule_26_problem'] = array(
        'rule' => 'Rule 26 (75-100kg)',
        'package' => '3kg',
        'expected' => false,
        'actual' => $result_weight_only,
        'passed' => !$result_weight_only
    );
    
    // Test 2: OR operátor s oběma podmínkami
    $test_rule_both = (object) array(
        'weight_min' => 75,
        'weight_max' => 100,
        'length_min' => 10,
        'length_max' => 50,
        'weight_operator' => 'OR'
    );
    
    $result_both_or = dbs_check_all_conditions_with_fixed_operators( $test_rule_both, $test_package_3kg );
    $results['both_conditions_or'] = array(
        'rule' => 'Weight 75-100kg OR Length 10-50cm',
        'package' => '3kg',
        'expected' => false, // Ani váha ani délka nesplňuje podmínky
        'actual' => $result_both_or,
        'passed' => !$result_both_or
    );
    
    // Test 3: AND operátor pro hmotnost v rozsahu
    $test_rule_valid = (object) array(
        'weight_min' => 0,
        'weight_max' => 5,
        'weight_operator' => 'AND'
    );
    
    $result_valid = dbs_check_all_conditions_with_fixed_operators( $test_rule_valid, $test_package_3kg );
    $results['valid_weight_rule'] = array(
        'rule' => 'Weight 0-5kg',
        'package' => '3kg',
        'expected' => true,
        'actual' => $result_valid,
        'passed' => $result_valid
    );
    
    return $results;
}

/**
 * Aktivace opravy operátorů
 */
function dbs_activate_weight_operators_fix() {
    if ( get_option( 'dbs_debug_mode', 0 ) ) {
        error_log( 'DBS: Weight operators fix applied with improved weight function' );
    }
    
    // Označit že oprava je aktivní
    update_option( 'dbs_weight_operators_fix_active', true );
    
    // Přepsat původní funkce pomocí filters
    add_filter( 'dbs_check_weight_condition', 'dbs_check_weight_condition_with_operators', 10, 2 );
    add_filter( 'dbs_check_dimensions_condition', 'dbs_check_dimensions_condition_with_operators', 10, 2 );
    add_filter( 'dbs_check_all_conditions', 'dbs_check_all_conditions_with_fixed_operators', 10, 2 );
}

/**
 * Integrace s weight-sync-fix
 */
function dbs_integrate_weight_operators_fix() {
    // Pokud je aktivní weight sync fix, použij vylepšenou funkci
    if ( get_option( 'dbs_weight_fix_active', false ) ) {
        if ( get_option( 'dbs_debug_mode', 0 ) ) {
            error_log( 'DBS: Weight operators fix integrated' );
        }
    }
    
    // Aktivace opravy
    dbs_activate_weight_operators_fix();
    
    if ( get_option( 'dbs_debug_mode', 0 ) ) {
        error_log( 'DBS: Weight operators fix activated' );
    }
}

// Aktivace opravy
dbs_integrate_weight_operators_fix(); 
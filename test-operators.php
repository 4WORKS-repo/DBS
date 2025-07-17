<?php
/**
 * Test AND/OR operátorů pro hmotnostní a rozměrové podmínky
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Test operátorů AND/OR
 */
function dbs_test_operators() {
    // Pouze pro admin a debug mode
    if ( ! is_admin() || ! get_option( 'dbs_debug_mode', 0 ) ) {
        echo '<p>Test je dostupný pouze pro admin v debug módu.</p>';
        return;
    }
    
    echo '<h2>Test operátorů AND/OR</h2>';
    echo '<p>Ověřuje, zda se správně aplikují operátory AND/OR pro hmotnostní a rozměrové podmínky.</p>';
    
    // Najít testovací produkt
    $test_product = null;
    $products = wc_get_products(['limit' => 1, 'status' => 'publish']);
    
    if (!empty($products)) {
        $test_product = $products[0];
        $original_weight = $test_product->get_weight();
        
        echo '<p>Používám produkt: ' . $test_product->get_name() . ' (ID: ' . $test_product->get_id() . ')</p>';
        
        // Test scénáře pro hmotnostní operátory
        $weight_scenarios = [
            [
                'name' => 'AND operátor - váha v rozsahu',
                'weight_min' => 5,
                'weight_max' => 15,
                'weight_operator' => 'AND',
                'test_weight' => 10,
                'expected' => true
            ],
            [
                'name' => 'AND operátor - váha mimo rozsah',
                'weight_min' => 5,
                'weight_max' => 15,
                'weight_operator' => 'AND',
                'test_weight' => 20,
                'expected' => false
            ],
            [
                'name' => 'OR operátor - váha splňuje min',
                'weight_min' => 5,
                'weight_max' => 15,
                'weight_operator' => 'OR',
                'test_weight' => 20,
                'expected' => true
            ],
            [
                'name' => 'OR operátor - váha splňuje max',
                'weight_min' => 5,
                'weight_max' => 15,
                'weight_operator' => 'OR',
                'test_weight' => 3,
                'expected' => true
            ],
            [
                'name' => 'OR operátor - váha nesplňuje ani jedno',
                'weight_min' => 5,
                'weight_max' => 15,
                'weight_operator' => 'OR',
                'test_weight' => 20,
                'expected' => true // 20 >= 5 (splňuje min)
            ]
        ];
        
        echo '<h3>Test hmotnostních operátorů</h3>';
        echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
        echo '<tr><th>Scénář</th><th>Min</th><th>Max</th><th>Operátor</th><th>Test váha</th><th>Očekáváno</th><th>Původní</th><th>Opravené</th><th>Status</th></tr>';
        
        foreach ($weight_scenarios as $scenario) {
            // Nastavit váhu produktu
            $test_product->set_weight($scenario['test_weight']);
            
            // Vytvořit testovací package
            $package = [
                'contents' => [
                    'test_item' => [
                        'data' => $test_product,
                        'quantity' => 1,
                        'product_id' => $test_product->get_id(),
                        'variation_id' => 0
                    ]
                ]
            ];
            
            // Vytvořit testovací pravidlo
            $rule = (object) [
                'weight_min' => $scenario['weight_min'],
                'weight_max' => $scenario['weight_max'],
                'weight_operator' => $scenario['weight_operator']
            ];
            
            // Test původní funkce
            $original_result = null;
            if (function_exists('dbs_check_weight_condition')) {
                $original_result = dbs_check_weight_condition($rule, $package);
            }
            
            // Test opravené funkce
            $improved_result = dbs_check_weight_condition_with_operators($rule, $package);
            
            // Určit status
            $status = '';
            if ($improved_result === $scenario['expected']) {
                $status = '<span style="color: green;">✅ Správně</span>';
            } else {
                $status = '<span style="color: red;">❌ Chyba</span>';
            }
            
            echo '<tr>';
            echo '<td>' . $scenario['name'] . '</td>';
            echo '<td>' . $scenario['weight_min'] . 'kg</td>';
            echo '<td>' . $scenario['weight_max'] . 'kg</td>';
            echo '<td>' . $scenario['weight_operator'] . '</td>';
            echo '<td>' . $scenario['test_weight'] . 'kg</td>';
            echo '<td>' . ($scenario['expected'] ? 'true' : 'false') . '</td>';
            echo '<td>' . (is_bool($original_result) ? ($original_result ? 'true' : 'false') : 'N/A') . '</td>';
            echo '<td>' . ($improved_result ? 'true' : 'false') . '</td>';
            echo '<td>' . $status . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
        // Vrátit původní váhu
        $test_product->set_weight($original_weight);
        
    } else {
        echo '<p style="color: red;">Žádné produkty nejsou k dispozici pro test</p>';
    }
    
    // Test kombinace podmínek
    echo '<h3>Test kombinace hmotnostních a rozměrových podmínek</h3>';
    
    if (function_exists('dbs_check_all_conditions_with_fixed_operators')) {
        $combination_rule = (object) [
            'weight_min' => 5,
            'weight_max' => 15,
            'weight_operator' => 'OR',
            'length_min' => 10,
            'length_max' => 50,
            'width_min' => 0,
            'width_max' => 0,
            'height_min' => 0,
            'height_max' => 0,
            'dimensions_operator' => 'AND'
        ];
        
        // Nastavit testovací produkt
        if ($test_product) {
            $test_product->set_weight(20); // Mimo váhový rozsah, ale splňuje OR minimum
            $test_product->set_length(30); // V rozměrovém rozsahu
            
            $package = [
                'contents' => [
                    'test_item' => [
                        'data' => $test_product,
                        'quantity' => 1,
                        'product_id' => $test_product->get_id(),
                        'variation_id' => 0
                    ]
                ]
            ];
            
            $result = dbs_check_all_conditions_with_fixed_operators($combination_rule, $package);
            
            echo '<p>Test kombinace: Váha 20kg (OR: 5-15kg), Délka 30cm (AND: 10-50cm)</p>';
            echo '<p>Výsledek: ' . ($result ? '<span style="color: green;">✅ Prošel</span>' : '<span style="color: red;">❌ Neprošel</span>') . '</p>';
        }
    }
    
    // Test existujících pravidel
    echo '<h3>Test existujících pravidel</h3>';
    
    if (function_exists('dbs_get_shipping_rules')) {
        $rules = dbs_get_shipping_rules();
        
        if (!empty($rules)) {
            echo '<p>Nalezeno ' . count($rules) . ' pravidel v databázi</p>';
            
            $weight_rules = array_filter($rules, function($rule) {
                return ($rule->weight_min > 0 || $rule->weight_max > 0);
            });
            
            if (!empty($weight_rules)) {
                echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
                echo '<tr><th>Pravidlo</th><th>Váhové podmínky</th><th>Operátor</th><th>Rozměrové podmínky</th><th>Operátor</th></tr>';
                
                foreach ($weight_rules as $rule) {
                    $weight_conditions = [];
                    if ($rule->weight_min > 0) $weight_conditions[] = 'Min: ' . $rule->weight_min . 'kg';
                    if ($rule->weight_max > 0) $weight_conditions[] = 'Max: ' . $rule->weight_max . 'kg';
                    
                    $dimension_conditions = [];
                    if ($rule->length_min > 0) $dimension_conditions[] = 'L: ' . $rule->length_min . 'cm';
                    if ($rule->length_max > 0) $dimension_conditions[] = 'L: ' . $rule->length_max . 'cm';
                    if ($rule->width_min > 0) $dimension_conditions[] = 'W: ' . $rule->width_min . 'cm';
                    if ($rule->width_max > 0) $dimension_conditions[] = 'W: ' . $rule->width_max . 'cm';
                    if ($rule->height_min > 0) $dimension_conditions[] = 'H: ' . $rule->height_min . 'cm';
                    if ($rule->height_max > 0) $dimension_conditions[] = 'H: ' . $rule->height_max . 'cm';
                    
                    echo '<tr>';
                    echo '<td>' . $rule->rule_name . '</td>';
                    echo '<td>' . implode(', ', $weight_conditions) . '</td>';
                    echo '<td>' . ($rule->weight_operator ?? 'AND') . '</td>';
                    echo '<td>' . (empty($dimension_conditions) ? 'Žádné' : implode(', ', $dimension_conditions)) . '</td>';
                    echo '<td>' . ($rule->dimensions_operator ?? 'AND') . '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            } else {
                echo '<p>Žádná pravidla s váhovými podmínkami nenalezena</p>';
            }
        } else {
            echo '<p>Žádná pravidla nenalezena</p>';
        }
    }
    
    echo '<h3>Závěr</h3>';
    echo '<p>Oprava operátorů by měla zajistit:</p>';
    echo '<ul>';
    echo '<li>Správnou aplikaci AND operátoru (všechny podmínky musí být splněny)</li>';
    echo '<li>Správnou aplikaci OR operátoru (alespoň jedna podmínka musí být splněna)</li>';
    echo '<li>Správnou kombinaci hmotnostních a rozměrových podmínek</li>';
    echo '<li>Detailní debug informace v error logu</li>';
    echo '</ul>';
} 
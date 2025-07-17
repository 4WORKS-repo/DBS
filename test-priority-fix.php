<?php
/**
 * Testovací skript pro ověření opravy řazení pravidel podle priority
 * 
 * Tento skript testuje, zda se pravidla správně řadí podle priority
 * a zda se aplikuje pravidlo s nejvyšší prioritou.
 */

// Načtení WordPress
require_once( dirname( __FILE__ ) . '/../../../wp-load.php' );

// Kontrola, že WooCommerce je aktivní
if ( ! class_exists( 'WooCommerce' ) ) {
    die( 'WooCommerce není aktivní' );
}

// Zapnutí debug módu
update_option( 'dbs_debug_mode', 1 );

echo "<h1>Test opravy řazení pravidel podle priority</h1>\n";

// Funkce pro vytvoření testovacího balíčku
function create_test_package( $product_id, $quantity ) {
    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        return null;
    }
    
    return [
        'contents' => [
            [
                'key' => 'test_item_' . $product_id,
                'product_id' => $product_id,
                'variation_id' => 0,
                'quantity' => $quantity,
                'data' => $product,
                'line_tax_data' => [],
                'line_subtotal' => $product->get_price() * $quantity,
                'line_subtotal_tax' => 0,
                'line_total' => $product->get_price() * $quantity,
                'line_tax' => 0,
            ]
        ],
        'contents_cost' => $product->get_price() * $quantity,
        'applied_coupons' => [],
        'user' => [
            'ID' => get_current_user_id(),
        ],
        'destination' => [
            'country' => 'CZ',
            'state' => '',
            'postcode' => '12000',
            'city' => 'Praha',
            'address' => 'Testovací adresa 123',
            'address_2' => '',
        ],
    ];
}

// Funkce pro testování scénáře
function test_scenario( $scenario_name, $product_id, $quantity, $expected_weight ) {
    echo "<h2>Test: $scenario_name</h2>\n";
    echo "<p>Produkt ID: $product_id, Množství: $quantity, Očekávaná hmotnost: $expected_weight kg</p>\n";
    
    $package = create_test_package( $product_id, $quantity );
    if ( ! $package ) {
        echo "<p style='color: red;'>Chyba: Produkt nebyl nalezen</p>\n";
        return;
    }
    
    // Výpočet hmotnosti
    $weight = dbs_get_package_weight( $package );
    echo "<p>Vypočítaná hmotnost: $weight kg</p>\n";
    
    // Získání všech pravidel
    $rules = dbs_get_shipping_rules();
    echo "<p>Celkem pravidel: " . count( $rules ) . "</p>\n";
    
    // Testování každého pravidla
    $applicable_rules = [];
    foreach ( $rules as $rule ) {
        $weight_ok = dbs_check_weight_condition( $rule, $package );
        $status = $weight_ok ? 'Aplikuje se' : 'Neaplikuje se';
        $color = $weight_ok ? 'green' : 'red';
        
        echo "<p style='color: $color;'>Rule {$rule->id} ({$rule->rule_name}): $status - Priorita: {$rule->priority}, Hmotnost: {$rule->weight_min}-{$rule->weight_max}kg</p>\n";
        
        if ( $weight_ok ) {
            $applicable_rules[] = $rule;
        }
    }
    
    // Výběr pravidla s nejvyšší prioritou
    if ( ! empty( $applicable_rules ) ) {
        usort( $applicable_rules, function( $a, $b ) {
            return $b->priority - $a->priority; // Sestupně podle priority
        } );
        
        $selected_rule = $applicable_rules[0];
        echo "<p style='color: blue; font-weight: bold;'>Vybrané pravidlo: Rule {$selected_rule->id} ({$selected_rule->rule_name}) - Priorita: {$selected_rule->priority}</p>\n";
        
        // Zobrazení všech aplikovatelných pravidel seřazených podle priority
        echo "<p><strong>Aplikovatelná pravidla seřazená podle priority:</strong></p>\n";
        foreach ( $applicable_rules as $rule ) {
            echo "<p>- Rule {$rule->id} ({$rule->rule_name}): Priorita {$rule->priority}, Hmotnost {$rule->weight_min}-{$rule->weight_max}kg</p>\n";
        }
    } else {
        echo "<p style='color: red; font-weight: bold;'>Žádné pravidlo se neaplikuje!</p>\n";
    }
    
    echo "<hr>\n";
}

// Najít produkt s hmotností 1kg
$test_product_id = null;
$products = wc_get_products( [
    'limit' => 100,
    'status' => 'publish'
] );

foreach ( $products as $product ) {
    if ( $product->get_weight() == 1 ) {
        $test_product_id = $product->get_id();
        break;
    }
}

if ( ! $test_product_id ) {
    echo "<p style='color: red;'>Nebyl nalezen produkt s hmotností 1kg. Vytvořím testovací produkt.</p>\n";
    
    // Vytvoření testovacího produktu
    $product = new WC_Product_Simple();
    $product->set_name( 'Testovací produkt 1kg' );
    $product->set_price( 100 );
    $product->set_weight( 1 );
    $product->set_status( 'publish' );
    $test_product_id = $product->save();
    
    echo "<p>Vytvořen testovací produkt s ID: $test_product_id</p>\n";
}

echo "<p>Používám produkt ID: $test_product_id</p>\n";

// Testování scénářů
test_scenario( "1 kus produktu 1kg", $test_product_id, 1, 1 );
test_scenario( "3 kusy produktu 1kg", $test_product_id, 3, 3 );

echo "<h2>Očekávané výsledky</h2>\n";
echo "<p><strong>Pro 1 kus (1kg):</strong></p>\n";
echo "<ul>\n";
echo "<li>Rule 1 (0-5kg) by se měla aplikovat</li>\n";
echo "<li>Rule 3 (6-9kg) by se neměla aplikovat</li>\n";
echo "</ul>\n";

echo "<p><strong>Pro 3 kusy (3kg):</strong></p>\n";
echo "<ul>\n";
echo "<li>Rule 1 (0-5kg) by se měla aplikovat</li>\n";
echo "<li>Rule 3 (6-9kg) by se neměla aplikovat</li>\n";
echo "<li>Pokud se aplikují obě pravidla, měla by se použít ta s vyšší prioritou</li>\n";
echo "</ul>\n";

echo "<h2>Debug log</h2>\n";
echo "<p>Zkontrolujte debug log pro detailní informace o řazení pravidel.</p>\n"; 
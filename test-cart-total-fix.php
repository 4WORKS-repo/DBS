<?php
/**
 * Testovací skript pro ověření opravy shipping cache při změně cart total
 * 
 * Tento skript testuje, zda se shipping pravidla správně aktualizují při změně cart total.
 */

// Načtení WordPress
require_once('../../../wp-load.php');

// Kontrola, že WooCommerce je aktivní
if (!class_exists('WooCommerce')) {
    die('WooCommerce není aktivní.');
}

// Kontrola, že plugin je aktivní
if (!function_exists('dbs_get_shipping_rules')) {
    die('Distance Based Shipping plugin není aktivní.');
}

echo "<h1>Test opravy shipping cache při změně cart total</h1>\n";

// 1. Test registrace nového hooku
echo "<h2>1. Test registrace hooku pro cart total</h2>\n";
echo "Kontroluji, zda je registrován hook pro sledování změn cart total...\n";

$cart_total_hook = has_action('woocommerce_cart_updated', 'dbs_trigger_shipping_recalculation_cart_total');
if ($cart_total_hook) {
    echo "✅ Hook woocommerce_cart_updated -> dbs_trigger_shipping_recalculation_cart_total je registrován s prioritou {$cart_total_hook}\n";
} else {
    echo "❌ Hook woocommerce_cart_updated -> dbs_trigger_shipping_recalculation_cart_total není registrován\n";
}

// 2. Test funkce pro cart total
echo "<h2>2. Test funkce pro cart total</h2>\n";

if (function_exists('dbs_trigger_shipping_recalculation_cart_total')) {
    echo "✅ Funkce dbs_trigger_shipping_recalculation_cart_total existuje\n";
} else {
    echo "❌ Funkce dbs_trigger_shipping_recalculation_cart_total neexistuje\n";
}

// 3. Test shipping pravidel s cart total podmínkami
echo "<h2>3. Test shipping pravidel s cart total podmínkami</h2>\n";
$rules = dbs_get_shipping_rules(true);
if (!empty($rules)) {
    echo "✅ Nalezeno " . count($rules) . " shipping pravidel\n";
    
    // Zobrazit pravidla s cart total podmínkami
    $cart_total_rules = array_filter($rules, function($rule) {
        return !empty($rule->min_order_amount) || !empty($rule->max_order_amount);
    });
    
    if (!empty($cart_total_rules)) {
        echo "✅ Nalezeno " . count($cart_total_rules) . " pravidel s cart total podmínkami:\n";
        foreach ($cart_total_rules as $rule) {
            echo "  - Rule {$rule->id}: min_order_amount={$rule->min_order_amount}, max_order_amount={$rule->max_order_amount}, priority={$rule->priority}\n";
        }
    } else {
        echo "⚠️ Žádná pravidla s cart total podmínkami\n";
    }
} else {
    echo "❌ Žádná shipping pravidla nejsou nalezena\n";
}

// 4. Test cache funkcí
echo "<h2>4. Test cache funkcí</h2>\n";

if (function_exists('WC') && WC() && WC()->session) {
    echo "✅ WooCommerce session je dostupné\n";
    
    // Test vyčištění cache
    WC()->session->__unset('shipping_for_package_0');
    WC()->session->__unset('shipping_for_package_1');
    WC()->session->__unset('shipping_for_package_2');
    WC()->session->__unset('shipping_for_package_3');
    WC()->session->__unset('shipping_for_package_4');
    WC()->session->__unset('shipping_for_package_5');
    WC()->session->__unset('dbs_shipping_distance');
    WC()->session->__unset('dbs_shipping_cost');
    WC()->session->__unset('dbs_shipping_method');
    WC()->session->__unset('dbs_applied_shipping_rate');
    WC()->session->__unset('dbs_last_cart_total');
    
    echo "✅ Cache proměnné byly vyčištěny\n";
} else {
    echo "❌ WooCommerce session není dostupné\n";
}

// 5. Test debug módu
echo "<h2>5. Test debug módu</h2>\n";
$debug_mode = get_option('dbs_debug_mode', 0);
if ($debug_mode) {
    echo "✅ Debug mód je zapnutý\n";
} else {
    echo "⚠️ Debug mód je vypnutý (doporučuji zapnout pro testování)\n";
}

// 6. Doporučení pro testování
echo "<h2>6. Doporučení pro testování</h2>\n";
echo "<p><strong>Pro testování opravy cart total:</strong></p>\n";
echo "<ol>\n";
echo "<li>Zapněte debug mód v admin rozhraní</li>\n";
echo "<li>Přidejte produkt do košíku s hodnotou pod 5000 Kč</li>\n";
echo "<li>Zkontrolujte, že se aplikuje placená doprava</li>\n";
echo "<li>Změňte množství tak, aby cart total přesáhl 5000 Kč</li>\n";
echo "<li>Zkontrolujte, zda se doprava změnila na zdarma</li>\n";
echo "<li>Zkontrolujte debug log pro zprávy o invalidaci cache</li>\n";
echo "</ol>\n";

echo "<h2>Test dokončen</h2>\n";
echo "<p>Pokud všechny testy prošly úspěšně, oprava by měla fungovat.</p>\n";
echo "<p>Pro ověření v praxi zkuste změnit množství produktu v košíku a sledujte, zda se shipping pravidlo aktualizuje podle cart total.</p>\n"; 
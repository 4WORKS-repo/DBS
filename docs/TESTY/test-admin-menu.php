<?php
/**
 * Testovací soubor pro ověření admin menu
 */

// Načtení WordPress
require_once('../../../wp-load.php');

echo "<h1>Test Admin Menu</h1>";

// Kontrola, že WooCommerce je načtené
if (!class_exists('WooCommerce')) {
    echo '<p style="color: red;">WooCommerce není načtené</p>';
    exit;
}

echo "<h2>1. Kontrola dostupnosti funkcí</h2>";

$functions = [
    'dbs_debug_shipping_rules' => function_exists('dbs_debug_shipping_rules'),
    'dbs_weight_dimensions_test_page' => function_exists('dbs_weight_dimensions_test_page'),
    'dbs_quick_weight_dimensions_test' => function_exists('dbs_quick_weight_dimensions_test'),
];

echo '<table border="1" style="border-collapse: collapse; width: 100%; margin: 10px 0;">';
echo '<tr><th>Funkce</th><th>Dostupnost</th></tr>';
foreach ($functions as $function => $available) {
    $status = $available ? '✅ Dostupná' : '❌ Nedostupná';
    $color = $available ? 'green' : 'red';
    echo "<tr><td>{$function}</td><td style='color: {$color};'>{$status}</td></tr>";
}
echo '</table>';

echo "<h2>2. Test debug funkce</h2>";
echo '<div style="background: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 5px;">';

if (function_exists('dbs_debug_shipping_rules')) {
    ob_start();
    dbs_debug_shipping_rules();
    $output = ob_get_clean();
    echo $output;
} else {
    echo '<p style="color: red;">Funkce dbs_debug_shipping_rules není dostupná</p>';
}

echo '</div>';

echo "<h2>3. Test rychlého testu</h2>";
echo '<div style="background: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 5px;">';

if (function_exists('dbs_quick_weight_dimensions_test')) {
    ob_start();
    dbs_quick_weight_dimensions_test();
    $output = ob_get_clean();
    echo $output;
} else {
    echo '<p style="color: red;">Funkce dbs_quick_weight_dimensions_test není dostupná</p>';
}

echo '</div>';

echo "<h2>4. Informace o URL</h2>";
echo '<p><strong>Debug Rules URL:</strong> ' . admin_url('admin.php?page=distance-shipping-debug-rules') . '</p>';
echo '<p><strong>Test Hmotnost URL:</strong> ' . admin_url('admin.php?page=distance-shipping-weight-test') . '</p>';

echo "<h2>5. Shrnutí</h2>";
echo '<p>✅ Testovací stránky by měly být nyní dostupné v WordPress adminu:</p>';
echo '<ul>';
echo '<li><strong>Debug Rules:</strong> WordPress Admin → Distance Shipping → Debug Rules</li>';
echo '<li><strong>Test Hmotnost:</strong> WordPress Admin → Distance Shipping → Test Hmotnost</li>';
echo '</ul>';
?> 
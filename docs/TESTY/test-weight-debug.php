<?php
/**
 * Debug skript pro testování hmotnostních podmínek
 * 
 * Tento skript simuluje košík s 500 kusy produktu o hmotnosti 1.12kg
 * a zobrazí, jaké pravidla jsou aplikovatelná a proč.
 */

// Načtení WordPress
require_once('../../../wp-load.php');

// Zajištění, že WooCommerce je načteno
if (!class_exists('WooCommerce')) {
    die('WooCommerce není načteno');
}

// Simulace produktu s hmotností 1.12kg
$product_weight = 1.12;
$quantity = 500;
$total_weight = $product_weight * $quantity;

echo "<h1>Debug hmotnostních podmínek</h1>";
echo "<p><strong>Hmotnost produktu:</strong> {$product_weight} kg</p>";
echo "<p><strong>Množství:</strong> {$quantity} kusů</p>";
echo "<p><strong>Celková hmotnost:</strong> {$total_weight} kg</p>";

// Simulace balíčku
$package = [
    'contents' => [
        [
            'data' => (object) [
                'get_weight' => function() use ($product_weight) { return $product_weight; },
                'get_length' => function() { return 10; },
                'get_width' => function() { return 10; },
                'get_height' => function() { return 10; },
            ],
            'quantity' => $quantity,
        ]
    ],
    'destination' => [
        'city' => 'Praha',
        'postcode' => '12000',
        'country' => 'CZ'
    ]
];

// Získání všech pravidel
$all_rules = dbs_get_shipping_rules(true);

echo "<h2>Všechna pravidla:</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Název</th><th>Priorita</th><th>Vzdálenost</th><th>Hmotnost min</th><th>Hmotnost max</th><th>Aktivní</th></tr>";

foreach ($all_rules as $rule) {
    $active = $rule->is_active ? 'Ano' : 'Ne';
    echo "<tr>";
    echo "<td>{$rule->id}</td>";
    echo "<td>{$rule->rule_name}</td>";
    echo "<td>{$rule->priority}</td>";
    echo "<td>{$rule->distance_from} - {$rule->distance_to}</td>";
    echo "<td>{$rule->weight_min}</td>";
    echo "<td>{$rule->weight_max}</td>";
    echo "<td>{$active}</td>";
    echo "</tr>";
}
echo "</table>";

// Testování s vzdáleností 10km
$distance = 10.0;

echo "<h2>Testování s vzdáleností {$distance} km:</h2>";

// Získání hmotnosti balíčku
$package_weight = dbs_get_package_weight($package);
echo "<p><strong>Vypočítaná hmotnost balíčku:</strong> {$package_weight} kg</p>";

// Testování každého pravidla
echo "<h3>Testování pravidel:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Název</th><th>Vzdálenost OK</th><th>Hmotnost OK</th><th>Rozměry OK</th><th>Všechny podmínky OK</th><th>Aplikovatelné</th></tr>";

$applicable_rules = [];

foreach ($all_rules as $rule) {
    // Kontrola vzdálenosti
    $distance_ok = ($distance >= $rule->distance_from) && 
                   ($rule->distance_to <= 0 || $distance <= $rule->distance_to);
    
    // Kontrola hmotnosti
    $weight_ok = dbs_check_weight_condition($rule, $package);
    
    // Kontrola rozměrů
    $dimensions_ok = dbs_check_dimensions_condition($rule, $package);
    
    // Kontrola všech podmínek
    $all_conditions_ok = dbs_check_all_conditions($rule, $package);
    
    // Je pravidlo aplikovatelné?
    $is_applicable = $distance_ok && $all_conditions_ok;
    
    if ($is_applicable) {
        $applicable_rules[] = $rule;
    }
    
    $distance_status = $distance_ok ? '✓' : '✗';
    $weight_status = $weight_ok ? '✓' : '✗';
    $dimensions_status = $dimensions_ok ? '✓' : '✗';
    $all_status = $all_conditions_ok ? '✓' : '✗';
    $applicable_status = $is_applicable ? '✓' : '✗';
    
    echo "<tr>";
    echo "<td>{$rule->id}</td>";
    echo "<td>{$rule->rule_name}</td>";
    echo "<td>{$distance_status}</td>";
    echo "<td>{$weight_status}</td>";
    echo "<td>{$dimensions_status}</td>";
    echo "<td>{$all_status}</td>";
    echo "<td>{$applicable_status}</td>";
    echo "</tr>";
}
echo "</table>";

// Seřazení aplikovatelných pravidel podle priority
usort($applicable_rules, function($a, $b) {
    return $a->priority <=> $b->priority;
});

echo "<h3>Aplikovatelná pravidla (seřazena podle priority):</h3>";
if (empty($applicable_rules)) {
    echo "<p><strong>Žádná pravidla nejsou aplikovatelná!</strong></p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Priorita</th><th>ID</th><th>Název</th><th>Vzdálenost</th><th>Hmotnost</th><th>Rozměry</th></tr>";
    
    foreach ($applicable_rules as $rule) {
        $weight_range = "{$rule->weight_min} - {$rule->weight_max} kg";
        $distance_range = "{$rule->distance_from} - {$rule->distance_to} km";
        $dimensions_range = "D: {$rule->length_min}-{$rule->length_max}, Š: {$rule->width_min}-{$rule->width_max}, V: {$rule->height_min}-{$rule->height_max}";
        
        echo "<tr>";
        echo "<td>{$rule->priority}</td>";
        echo "<td>{$rule->id}</td>";
        echo "<td>{$rule->rule_name}</td>";
        echo "<td>{$distance_range}</td>";
        echo "<td>{$weight_range}</td>";
        echo "<td>{$dimensions_range}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Zobrazení vybraného pravidla
    $selected_rule = $applicable_rules[0];
    echo "<h3>Vybrané pravidlo (nejvyšší priorita):</h3>";
    echo "<p><strong>ID:</strong> {$selected_rule->id}</p>";
    echo "<p><strong>Název:</strong> {$selected_rule->rule_name}</p>";
    echo "<p><strong>Priorita:</strong> {$selected_rule->priority}</p>";
}

// Debug informace o balíčku
echo "<h3>Debug informace o balíčku:</h3>";
$package_info = dbs_get_package_info($package);
echo "<p><strong>Hmotnost:</strong> {$package_info['weight_formatted']}</p>";
echo "<p><strong>Rozměry:</strong> {$package_info['dimensions_formatted']}</p>";

// Zobrazení všech podmínek pro Rule 3
echo "<h3>Detailní analýza Rule 3:</h3>";
$rule_3 = null;
foreach ($all_rules as $rule) {
    if ($rule->id == 3) {
        $rule_3 = $rule;
        break;
    }
}

if ($rule_3) {
    echo "<p><strong>Rule 3 podmínky:</strong></p>";
    echo "<ul>";
    echo "<li>Vzdálenost: {$rule_3->distance_from} - {$rule_3->distance_to} km</li>";
    echo "<li>Hmotnost: {$rule_3->weight_min} - {$rule_3->weight_max} kg</li>";
    echo "<li>Rozměry: D {$rule_3->length_min}-{$rule_3->length_max}, Š {$rule_3->width_min}-{$rule_3->width_max}, V {$rule_3->height_min}-{$rule_3->height_max}</li>";
    echo "<li>Priorita: {$rule_3->priority}</li>";
    echo "</ul>";
    
    // Testování podmínek
    $distance_ok = ($distance >= $rule_3->distance_from) && 
                   ($rule_3->distance_to <= 0 || $distance <= $rule_3->distance_to);
    $weight_ok = dbs_check_weight_condition($rule_3, $package);
    $dimensions_ok = dbs_check_dimensions_condition($rule_3, $package);
    
    echo "<p><strong>Testování podmínek:</strong></p>";
    echo "<ul>";
    echo "<li>Vzdálenost ({$distance} km): " . ($distance_ok ? '✓ OK' : '✗ NE') . "</li>";
    echo "<li>Hmotnost ({$package_weight} kg): " . ($weight_ok ? '✓ OK' : '✗ NE') . "</li>";
    echo "<li>Rozměry: " . ($dimensions_ok ? '✓ OK' : '✗ NE') . "</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><em>Debug skript dokončen.</em></p>";
?> 
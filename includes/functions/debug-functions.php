<?php
/**
 * Debug funkce pro Distance Based Shipping plugin.
 *
 * Soubor: includes/functions/debug-functions.php
 *
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Debug funkce pro testování shipping pravidel.
 */
function dbs_debug_shipping_rules() {
	echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border-radius: 5px;">';
	echo '<h2>Debug Shipping Rules</h2>';

	// Test adresa (90km vzdálenost)
	$test_address = "Brněnská 3163/38 695 01 Hodonín";

	echo "<h3>Testovací adresa: {$test_address}</h3>";

	// 1. Najít nejbližší obchod
	$nearest_store = dbs_find_nearest_store($test_address);
	if ($nearest_store) {
		echo "<h4>Nejbližší obchod:</h4>";
		echo "<p>Název: {$nearest_store->name}</p>";
		echo "<p>Adresa: {$nearest_store->address}</p>";
	} else {
		echo "<p style='color: red;'>Nenalezen žádný obchod!</p>";
		echo '</div>';
		return;
	}

	// 2. Vypočítat vzdálenost
	$distance = dbs_calculate_distance($nearest_store->address, $test_address);
	if ($distance !== false) {
		echo "<h4>Vzdálenost:</h4>";
		echo "<p>{$distance} km</p>";
	} else {
		echo "<p style='color: red;'>Nepodařilo se vypočítat vzdálenost!</p>";
		echo '</div>';
		return;
	}

	// 3. Získat všechna pravidla
	$all_rules = dbs_get_shipping_rules(true);
	echo "<h4>Všechna aktivní pravidla ({$distance} km):</h4>";
	echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
	echo "<tr><th>ID</th><th>Název</th><th>Od</th><th>Do</th><th>Základní sazba</th><th>Sazba/km</th><th>Priorita</th><th>Hmotnost</th><th>Rozměry</th><th>Aplikovatelné</th></tr>";

	foreach ($all_rules as $rule) {
		$is_applicable = dbs_is_shipping_rule_applicable($rule, $distance, []);
		$applicable_text = $is_applicable ? "ANO" : "NE";
		$applicable_color = $is_applicable ? "green" : "red";
		
		echo "<tr>";
		echo "<td>{$rule->id}</td>";
		echo "<td>{$rule->rule_name}</td>";
		echo "<td>{$rule->distance_from} km</td>";
		echo "<td>{$rule->distance_to} km</td>";
		echo "<td>{$rule->base_rate} Kč</td>";
		echo "<td>{$rule->per_km_rate} Kč/km</td>";
		echo "<td>{$rule->priority}</td>";
		
		// Hmotnost
		$weight_info = '';
		if (isset($rule->weight_min) && $rule->weight_min > 0) {
			$weight_info .= "≥ {$rule->weight_min} kg";
		}
		if (isset($rule->weight_max) && $rule->weight_max > 0) {
			if ($weight_info) $weight_info .= ' ';
			$weight_info .= "≤ {$rule->weight_max} kg";
		}
		if (!$weight_info) $weight_info = 'N/A';
		echo "<td>{$weight_info}</td>";
		
		// Rozměry
		$dimensions_info = '';
		if (isset($rule->length_min) && $rule->length_min > 0) {
			$dimensions_info .= "L≥{$rule->length_min}cm ";
		}
		if (isset($rule->width_min) && $rule->width_min > 0) {
			$dimensions_info .= "Š≥{$rule->width_min}cm ";
		}
		if (isset($rule->height_min) && $rule->height_min > 0) {
			$dimensions_info .= "V≥{$rule->height_min}cm";
		}
		if (!$dimensions_info) $dimensions_info = 'N/A';
		echo "<td>{$dimensions_info}</td>";
		
		echo "<td style='color: {$applicable_color}; font-weight: bold;'>{$applicable_text}</td>";
		echo "</tr>";
	}
	echo "</table>";

	// 4. Získat aplikovatelná pravidla
	$applicable_rules = dbs_get_applicable_shipping_rules($distance, []);
	echo "<h4>Aplikovatelná pravidla:</h4>";
	if (empty($applicable_rules)) {
		echo "<p style='color: red;'>Žádná pravidla nejsou aplikovatelná!</p>";
	} else {
		echo "<ul>";
		foreach ($applicable_rules as $rule) {
			$rate = dbs_calculate_shipping_rate_from_rule($rule, $distance, []);
			$cost = $rate ? $rate['cost'] : 'N/A';
			echo "<li><strong>{$rule->rule_name}</strong> - {$cost} Kč (Priorita: {$rule->priority})</li>";
		}
		echo "</ul>";
	}

	// 5. Test mock package
	$mock_package = dbs_create_mock_package($test_address);
	echo "<h4>Mock package:</h4>";
	echo "<pre>" . print_r($mock_package, true) . "</pre>";

	// 5.1. Informace o hmotnosti a rozměrech
	if (function_exists('dbs_get_package_info')) {
		$package_info = dbs_get_package_info($mock_package);
		echo "<h4>Informace o balíčku:</h4>";
		echo "<p><strong>Hmotnost:</strong> {$package_info['weight_formatted']}</p>";
		echo "<p><strong>Rozměry:</strong> {$package_info['dimensions_formatted']}</p>";
	}

	// 6. Test s mock package
	$applicable_rules_with_package = dbs_get_applicable_shipping_rules($distance, $mock_package);
	echo "<h4>Aplikovatelná pravidla s mock package:</h4>";
	if (empty($applicable_rules_with_package)) {
		echo "<p style='color: red;'>Žádná pravidla nejsou aplikovatelná s mock package!</p>";
	} else {
		echo "<ul>";
		foreach ($applicable_rules_with_package as $rule) {
			$rate = dbs_calculate_shipping_rate_from_rule($rule, $distance, $mock_package);
			$cost = $rate ? $rate['cost'] : 'N/A';
			echo "<li><strong>{$rule->rule_name}</strong> - {$cost} Kč (Priorita: {$rule->priority})</li>";
		}
		echo "</ul>";
	}

	echo "<h4>Debug informace:</h4>";
	echo "<p>WooCommerce cart total: " . (WC()->cart ? WC()->cart->get_cart_contents_total() : 'N/A') . "</p>";
	echo "<p>Počet pravidel v databázi: " . count($all_rules) . "</p>";
	echo "<p>Počet aplikovatelných pravidel: " . count($applicable_rules) . "</p>";
	echo "<p>Počet aplikovatelných pravidel s package: " . count($applicable_rules_with_package) . "</p>";

	// Přidat rychlý test nových funkcí
	dbs_quick_weight_dimensions_test();

	echo '</div>';
}

// Stránky jsou nyní registrovány v admin-functions.php



/**
 * Přidá rychlý test nových funkcí do debug stránky.
 */
function dbs_quick_weight_dimensions_test() {
	echo '<h3>Rychlý test nových funkcí</h3>';
	
	// Test 1: Kontrola funkcí
	echo '<h4>1. Kontrola dostupnosti funkcí</h4>';
	$functions = [
		'dbs_get_package_weight' => function_exists('dbs_get_package_weight'),
		'dbs_get_package_dimensions' => function_exists('dbs_get_package_dimensions'),
		'dbs_check_weight_condition' => function_exists('dbs_check_weight_condition'),
		'dbs_check_dimensions_condition' => function_exists('dbs_check_dimensions_condition'),
		'dbs_check_all_conditions' => function_exists('dbs_check_all_conditions'),
		'dbs_get_package_info' => function_exists('dbs_get_package_info'),
	];
	
	echo '<table border="1" style="border-collapse: collapse; width: 100%; margin: 10px 0;">';
	echo '<tr><th>Funkce</th><th>Dostupnost</th></tr>';
	foreach ($functions as $function => $available) {
		$status = $available ? '✅ Dostupná' : '❌ Nedostupná';
		$color = $available ? 'green' : 'red';
		echo "<tr><td>{$function}</td><td style='color: {$color};'>{$status}</td></tr>";
	}
	echo '</table>';
	
	// Test 2: Kontrola databázových sloupců
	echo '<h4>2. Kontrola databázových sloupců</h4>';
	global $wpdb;
	$tables = dbs_get_table_names();
	$table_name = $tables['rules'];
	
	$columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
	$column_names = array_column($columns, 'Field');
	
	$new_columns = [
		'weight_min', 'weight_max', 'weight_operator',
		'length_min', 'length_max', 'width_min', 'width_max', 'height_min', 'height_max', 'dimensions_operator'
	];
	
	echo '<table border="1" style="border-collapse: collapse; width: 100%; margin: 10px 0;">';
	echo '<tr><th>Sloupec</th><th>Dostupnost</th></tr>';
	foreach ($new_columns as $column) {
		$available = in_array($column, $column_names);
		$status = $available ? '✅ Dostupný' : '❌ Nedostupný';
		$color = $available ? 'green' : 'red';
		echo "<tr><td>{$column}</td><td style='color: {$color};'>{$status}</td></tr>";
	}
	echo '</table>';
	
	// Test 3: Vytvoření testovacího balíčku
	echo '<h4>3. Test balíčku</h4>';
	$test_package = [
		'contents' => [
			[
				'data' => (object) [
					'get_weight' => function() { return 35; },
					'get_length' => function() { return 120; },
					'get_width' => function() { return 80; },
					'get_height' => function() { return 60; },
				],
				'quantity' => 1,
				'line_total' => 500,
			]
		],
		'destination' => [
			'address' => 'Test Address',
			'city' => 'Test City',
			'postcode' => '12345',
			'country' => 'CZ',
		],
	];
	
	if (function_exists('dbs_get_package_info')) {
		$info = dbs_get_package_info($test_package);
		echo "<p><strong>Hmotnost:</strong> {$info['weight_formatted']}</p>";
		echo "<p><strong>Rozměry:</strong> {$info['dimensions_formatted']}</p>";
	} else {
		echo '<p style="color: red;">Funkce dbs_get_package_info není dostupná</p>';
	}
}

// Stránky jsou nyní registrovány v admin-functions.php 
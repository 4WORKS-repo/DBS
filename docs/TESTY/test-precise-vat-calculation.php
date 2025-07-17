<?php
/**
 * Testovací skript pro ověření přesnosti DPH výpočtů
 * 
 * Soubor: test-precise-vat-calculation.php
 * 
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Kontrola, zda je uživatel admin
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( __( 'Nemáte oprávnění k přístupu na tuto stránku.', 'distance-shipping' ) );
}

/**
 * Simulace přesného výpočtu DPH z pluginu
 */
function dbs_test_calculate_precise_net_price( float $brutto_price ): float {
	$vat_rate = 0.21; // 21% VAT
	
	// Calculate net price with higher precision
	$net_price = $brutto_price / ( 1 + $vat_rate );
	
	// Round to 6 decimal places for precision
	$net_price = round( $net_price, 6 );
	
	// Force rounding up to ensure we don't get less than the target brutto
	$net_price = ceil( $net_price * 100 ) / 100;
	
	// Verify the calculation
	$calculated_brutto = round( $net_price * ( 1 + $vat_rate ), 2 );
	
	// If there's still a mismatch, adjust the net price
	if ( $calculated_brutto !== $brutto_price ) {
		// Try alternative calculation method
		$net_price = $brutto_price - round( $brutto_price * $vat_rate / ( 1 + $vat_rate ), 2 );
		$net_price = round( $net_price, 2 );
		
		// Final verification
		$final_brutto = round( $net_price * ( 1 + $vat_rate ), 2 );
		
		if ( $final_brutto !== $brutto_price ) {
			// Log warning if we still can't get exact match
			error_log( 'DBS: Warning - Could not achieve exact brutto match. Target: ' . $brutto_price . ', Achieved: ' . $final_brutto );
		}
	}
	
	return $net_price;
}

/**
 * Testovací funkce pro porovnání metod
 */
function dbs_test_vat_calculation_methods( float $brutto_price ): array {
	$vat_rate = 0.21;
	
	// Metoda 1: Jednoduché dělení
	$method1_net = round( $brutto_price / 1.21, 2 );
	$method1_brutto = round( $method1_net * 1.21, 2 );
	
	// Metoda 2: Přesný výpočet
	$method2_net = dbs_test_calculate_precise_net_price( $brutto_price );
	$method2_brutto = round( $method2_net * 1.21, 2 );
	
	// Metoda 3: Alternativní výpočet
	$method3_net = $brutto_price - round( $brutto_price * $vat_rate / ( 1 + $vat_rate ), 2 );
	$method3_net = round( $method3_net, 2 );
	$method3_brutto = round( $method3_net * 1.21, 2 );
	
	return [
		'method1' => [
			'name' => 'Jednoduché dělení',
			'net' => $method1_net,
			'brutto' => $method1_brutto,
			'exact_match' => $method1_brutto === $brutto_price
		],
		'method2' => [
			'name' => 'Přesný výpočet (plugin)',
			'net' => $method2_net,
			'brutto' => $method2_brutto,
			'exact_match' => $method2_brutto === $brutto_price
		],
		'method3' => [
			'name' => 'Alternativní výpočet',
			'net' => $method3_net,
			'brutto' => $method3_brutto,
			'exact_match' => $method3_brutto === $brutto_price
		]
	];
}

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Test přesnosti DPH výpočtů', 'distance-shipping' ); ?></h1>
	
	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'Tento test ověřuje přesnost různých metod výpočtu DPH a identifikuje floating-point chyby.', 'distance-shipping' ); ?></strong></p>
	</div>

	<?php
	// Testovací ceny
	$test_prices = [100, 50, 25, 75, 150, 200, 99.99, 100.01, 0.99, 1.00];
	
	echo '<div class="dbs-test-section">';
	echo '<h2>' . esc_html__( 'Testovací výsledky', 'distance-shipping' ) . '</h2>';
	
	echo '<table class="wp-list-table widefat fixed striped">';
	echo '<thead>';
	echo '<tr>';
	echo '<th>' . esc_html__( 'Zadaná cena (brutto)', 'distance-shipping' ) . '</th>';
	echo '<th>' . esc_html__( 'Metoda', 'distance-shipping' ) . '</th>';
	echo '<th>' . esc_html__( 'Vypočítaná netto', 'distance-shipping' ) . '</th>';
	echo '<th>' . esc_html__( 'Výsledná brutto', 'distance-shipping' ) . '</th>';
	echo '<th>' . esc_html__( 'Přesnost', 'distance-shipping' ) . '</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';
	
	foreach ( $test_prices as $price ) {
		$results = dbs_test_vat_calculation_methods( $price );
		
		foreach ( $results as $method_key => $result ) {
			$status_class = $result['exact_match'] ? 'success' : 'error';
			$status_text = $result['exact_match'] ? '✓ Přesné' : '✗ Chyba';
			
			echo '<tr>';
			echo '<td>' . esc_html( number_format( $price, 2 ) ) . ' Kč</td>';
			echo '<td>' . esc_html( $result['name'] ) . '</td>';
			echo '<td>' . esc_html( number_format( $result['net'], 2 ) ) . ' Kč</td>';
			echo '<td>' . esc_html( number_format( $result['brutto'], 2 ) ) . ' Kč</td>';
			echo '<td class="' . esc_attr( $status_class ) . '">' . esc_html( $status_text ) . '</td>';
			echo '</tr>';
		}
	}
	
	echo '</tbody>';
	echo '</table>';
	echo '</div>';

	// Statistiky
	echo '<div class="dbs-test-section">';
	echo '<h2>' . esc_html__( 'Statistiky přesnosti', 'distance-shipping' ) . '</h2>';
	
	$total_tests = count( $test_prices );
	$method_stats = [];
	
	foreach ( ['method1', 'method2', 'method3'] as $method_key ) {
		$exact_matches = 0;
		$total_error = 0;
		
		foreach ( $test_prices as $price ) {
			$results = dbs_test_vat_calculation_methods( $price );
			$result = $results[ $method_key ];
			
			if ( $result['exact_match'] ) {
				$exact_matches++;
			} else {
				$total_error += abs( $result['brutto'] - $price );
			}
		}
		
		$method_stats[ $method_key ] = [
			'exact_matches' => $exact_matches,
			'accuracy_percent' => round( ( $exact_matches / $total_tests ) * 100, 1 ),
			'average_error' => $total_error / ( $total_tests - $exact_matches ),
			'method_name' => $results[ $method_key ]['name']
		];
	}
	
	echo '<table class="form-table">';
	foreach ( $method_stats as $method_key => $stats ) {
		echo '<tr>';
		echo '<th>' . esc_html( $stats['method_name'] ) . '</th>';
		echo '<td>';
		echo '<strong>' . esc_html__( 'Přesné shody:', 'distance-shipping' ) . '</strong> ' . esc_html( $stats['exact_matches'] ) . '/' . esc_html( $total_tests ) . ' (' . esc_html( $stats['accuracy_percent'] ) . '%)<br>';
		if ( $stats['exact_matches'] < $total_tests ) {
			echo '<strong>' . esc_html__( 'Průměrná chyba:', 'distance-shipping' ) . '</strong> ' . esc_html( number_format( $stats['average_error'], 2 ) ) . ' Kč';
		}
		echo '</td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '</div>';

	// Doporučení
	echo '<div class="dbs-test-section">';
	echo '<h2>' . esc_html__( 'Doporučení', 'distance-shipping' ) . '</h2>';
	
	$best_method = array_reduce( $method_stats, function( $carry, $item ) {
		if ( $carry === null || $item['exact_matches'] > $carry['exact_matches'] ) {
			return $item;
		}
		return $carry;
	});
	
	echo '<div style="background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px;">';
	echo '<h3 style="margin-top: 0; color: #155724;">' . esc_html__( 'Nejlepší metoda:', 'distance-shipping' ) . ' ' . esc_html( $best_method['method_name'] ) . '</h3>';
	echo '<p><strong>' . esc_html__( 'Přesnost:', 'distance-shipping' ) . '</strong> ' . esc_html( $best_method['accuracy_percent'] ) . '%</p>';
	echo '<p><strong>' . esc_html__( 'Přesné shody:', 'distance-shipping' ) . '</strong> ' . esc_html( $best_method['exact_matches'] ) . '/' . esc_html( $total_tests ) . '</p>';
	
	if ( $best_method['exact_matches'] < $total_tests ) {
		echo '<p><strong>' . esc_html__( 'Průměrná chyba:', 'distance-shipping' ) . '</strong> ' . esc_html( number_format( $best_method['average_error'], 2 ) ) . ' Kč</p>';
	}
	echo '</div>';
	echo '</div>';

	// Aktuální nastavení
	$adjust_shipping_for_vat = get_option( 'dbs_adjust_shipping_for_vat', 0 );
	echo '<div class="dbs-test-section">';
	echo '<h2>' . esc_html__( 'Aktuální nastavení pluginu', 'distance-shipping' ) . '</h2>';
	echo '<table class="form-table">';
	echo '<tr>';
	echo '<th>' . esc_html__( 'DPH přepínač:', 'distance-shipping' ) . '</th>';
	echo '<td>' . ( $adjust_shipping_for_vat ? '<span style="color: green;">✓ Aktivní</span>' : '<span style="color: red;">✗ Neaktivní</span>' ) . '</td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>' . esc_html__( 'Debug režim:', 'distance-shipping' ) . '</th>';
	echo '<td>' . ( get_option( 'dbs_debug_mode', 0 ) ? '<span style="color: green;">✓ Aktivní</span>' : '<span style="color: red;">✗ Neaktivní</span>' ) . '</td>';
	echo '</tr>';
	echo '</table>';
	echo '</div>';
	?>

	<div class="dbs-test-section">
		<h2><?php esc_html_e( 'Rychlé akce', 'distance-shipping' ); ?></h2>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-settings' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Nastavení pluginu', 'distance-shipping' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-test-vat' ) ); ?>" class="button">
				<?php esc_html_e( 'Test DPH', 'distance-shipping' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-rules' ) ); ?>" class="button">
				<?php esc_html_e( 'Dopravní pravidla', 'distance-shipping' ); ?>
			</a>
		</p>
	</div>
</div>

<style>
.dbs-test-section {
	background: #fff;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
	padding: 20px;
	margin-bottom: 20px;
}

.dbs-test-section h2 {
	margin-top: 0;
	padding-bottom: 10px;
	border-bottom: 1px solid #eee;
}

.success {
	color: #155724;
	font-weight: bold;
}

.error {
	color: #721c24;
	font-weight: bold;
}

.form-table th {
	width: 200px;
	font-weight: bold;
}

.form-table td {
	padding: 8px 10px;
}

.wp-list-table th {
	font-weight: bold;
}

.wp-list-table td {
	vertical-align: middle;
}
</style> 
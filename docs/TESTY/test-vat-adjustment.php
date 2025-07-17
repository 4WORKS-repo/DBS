<?php
/**
 * Testovací skript pro ověření funkčnosti DPH přepínače
 * 
 * Soubor: test-vat-adjustment.php
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

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Test DPH přepínače', 'distance-shipping' ); ?></h1>
	
	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'Tento test ověřuje funkčnost přepínače pro automatické přizpůsobení cen dopravy pro DPH.', 'distance-shipping' ); ?></strong></p>
	</div>

	<?php
	// Získání aktuálního nastavení
	$adjust_shipping_for_vat = get_option( 'dbs_adjust_shipping_for_vat', 0 );
	$test_price = 100.00; // Testovací cena 100 Kč
	
	echo '<div class="dbs-test-section">';
	echo '<h2>' . esc_html__( 'Aktuální nastavení', 'distance-shipping' ) . '</h2>';
	echo '<table class="form-table">';
	echo '<tr>';
	echo '<th>' . esc_html__( 'Přepínač DPH:', 'distance-shipping' ) . '</th>';
	echo '<td>' . ( $adjust_shipping_for_vat ? '<span style="color: green;">✓ Zapnuto</span>' : '<span style="color: red;">✗ Vypnuto</span>' ) . '</td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>' . esc_html__( 'Testovací cena:', 'distance-shipping' ) . '</th>';
	echo '<td>' . esc_html( number_format( $test_price, 2 ) ) . ' Kč</td>';
	echo '</tr>';
	echo '</table>';
	echo '</div>';

	// Simulace výpočtu
	echo '<div class="dbs-test-section">';
	echo '<h2>' . esc_html__( 'Simulace výpočtu', 'distance-shipping' ) . '</h2>';
	
	if ( $adjust_shipping_for_vat ) {
		$adjusted_price = round( $test_price / 1.21, 2 );
		$final_price = round( $adjusted_price * 1.21, 2 );
		
		echo '<div style="background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin: 10px 0;">';
		echo '<h3 style="margin-top: 0; color: #155724;">' . esc_html__( 'DPH režim aktivní', 'distance-shipping' ) . '</h3>';
		echo '<ul>';
		echo '<li><strong>' . esc_html__( 'Zadaná cena (brutto):', 'distance-shipping' ) . '</strong> ' . esc_html( number_format( $test_price, 2 ) ) . ' Kč</li>';
		echo '<li><strong>' . esc_html__( 'Interně použito (netto):', 'distance-shipping' ) . '</strong> ' . esc_html( number_format( $adjusted_price, 2 ) ) . ' Kč</li>';
		echo '<li><strong>' . esc_html__( 'WooCommerce přidá DPH:', 'distance-shipping' ) . '</strong> ' . esc_html( number_format( $adjusted_price, 2 ) ) . ' × 1.21 = ' . esc_html( number_format( $final_price, 2 ) ) . ' Kč</li>';
		echo '<li><strong>' . esc_html__( 'Výsledek:', 'distance-shipping' ) . '</strong> <span style="color: green;">' . esc_html( number_format( $final_price, 2 ) ) . ' Kč</span></li>';
		echo '</ul>';
		echo '</div>';
	} else {
		echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin: 10px 0;">';
		echo '<h3 style="margin-top: 0; color: #856404;">' . esc_html__( 'DPH režim neaktivní', 'distance-shipping' ) . '</h3>';
		echo '<ul>';
		echo '<li><strong>' . esc_html__( 'Zadaná cena:', 'distance-shipping' ) . '</strong> ' . esc_html( number_format( $test_price, 2 ) ) . ' Kč</li>';
		echo '<li><strong>' . esc_html__( 'WooCommerce přidá DPH:', 'distance-shipping' ) . '</strong> ' . esc_html( number_format( $test_price, 2 ) ) . ' × 1.21 = ' . esc_html( number_format( $test_price * 1.21, 2 ) ) . ' Kč</li>';
		echo '<li><strong>' . esc_html__( 'Výsledek:', 'distance-shipping' ) . '</strong> <span style="color: red;">' . esc_html( number_format( $test_price * 1.21, 2 ) ) . ' Kč</span></li>';
		echo '</ul>';
		echo '</div>';
	}
	echo '</div>';

	// Testovací příkazy
	echo '<div class="dbs-test-section">';
	echo '<h2>' . esc_html__( 'Testovací příkazy', 'distance-shipping' ) . '</h2>';
	echo '<p>' . esc_html__( 'Pro testování funkčnosti použijte tyto kroky:', 'distance-shipping' ) . '</p>';
	echo '<ol>';
	echo '<li>' . esc_html__( 'Přejděte do nastavení pluginu a zapněte/vypněte přepínač "Přizpůsobit ceny pro DPH"', 'distance-shipping' ) . '</li>';
	echo '<li>' . esc_html__( 'Vytvořte nebo upravte dopravní pravidlo s cenou 100 Kč', 'distance-shipping' ) . '</li>';
	echo '<li>' . esc_html__( 'Přejděte do WooCommerce a přidejte produkt do košíku', 'distance-shipping' ) . '</li>';
	echo '<li>' . esc_html__( 'Zadejte adresu pro dopravu', 'distance-shipping' ) . '</li>';
	echo '<li>' . esc_html__( 'Zkontrolujte, zda se cena dopravy zobrazuje správně', 'distance-shipping' ) . '</li>';
	echo '</ol>';
	echo '</div>';

	// Debug informace
	if ( get_option( 'dbs_debug_mode', 0 ) ) {
		echo '<div class="dbs-test-section">';
		echo '<h2>' . esc_html__( 'Debug informace', 'distance-shipping' ) . '</h2>';
		echo '<p>' . esc_html__( 'Debug režim je aktivní. Podrobné informace o výpočtech najdete v error_log.', 'distance-shipping' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Cesta k error_log:', 'distance-shipping' ) . '</strong> ' . esc_html( ini_get( 'error_log' ) ?: __( 'Není nastavena', 'distance-shipping' ) ) . '</p>';
		echo '</div>';
	}
	?>

	<div class="dbs-test-section">
		<h2><?php esc_html_e( 'Rychlé akce', 'distance-shipping' ); ?></h2>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-settings' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Nastavení pluginu', 'distance-shipping' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-rules' ) ); ?>" class="button">
				<?php esc_html_e( 'Dopravní pravidla', 'distance-shipping' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>" class="button">
				<?php esc_html_e( 'WooCommerce doprava', 'distance-shipping' ); ?>
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

.form-table th {
	width: 200px;
	font-weight: bold;
}

.form-table td {
	padding: 8px 10px;
}

.form-table ul {
	margin: 0;
	padding-left: 20px;
}

.form-table li {
	margin-bottom: 5px;
}
</style> 
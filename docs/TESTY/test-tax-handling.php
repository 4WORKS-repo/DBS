<?php
/**
 * Testovací skript pro ověření zpracování DPH na shipping sazbách.
 * 
 * Tento soubor slouží k testování, zda naše shipping sazby správně
 * zpracovávají DPH a nezobrazují duplicitní náklady.
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Testovací funkce pro ověření zpracování DPH.
 */
function dbs_test_tax_handling() {
	// Kontrola, že WooCommerce je načtené
	if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
		echo '<div style="background: #ffebee; padding: 10px; margin: 10px; border: 1px solid #f44336;">';
		echo '<strong>Chyba:</strong> WooCommerce není načtené nebo cart není dostupné.';
		echo '</div>';
		return;
	}
	
	// Kontrola, že cart má položky
	if ( WC()->cart->is_empty() ) {
		echo '<div style="background: #fff3e0; padding: 10px; margin: 10px; border: 1px solid #ff9800;">';
		echo '<strong>Upozornění:</strong> Košík je prázdný. Přidejte produkty pro testování shipping metod.';
		echo '</div>';
		return;
	}
	
	echo '<div style="background: #e8f5e8; padding: 15px; margin: 10px; border: 1px solid #4caf50;">';
	echo '<h3>Testování Zpracování DPH</h3>';
	
	// Získat dostupné shipping metody
	$packages = WC()->shipping()->get_packages();
	
	if ( empty( $packages ) ) {
		echo '<p><strong>Žádné shipping balíčky nejsou dostupné.</strong></p>';
		echo '</div>';
		return;
	}
	
	$package = $packages[0]; // První balíček
	$rates = $package['rates'];
	
	echo '<p><strong>Počet dostupných shipping metod:</strong> ' . count( $rates ) . '</p>';
	
	if ( empty( $rates ) ) {
		echo '<p><strong>Žádné shipping metody nejsou dostupné.</strong></p>';
		echo '</div>';
		return;
	}
	
	echo '<h4>Dostupné shipping metody s DPH:</h4>';
	echo '<ul>';
	
	$has_custom_method = false;
	$tax_issues_found = false;
	
	foreach ( $rates as $rate_id => $rate ) {
		$is_custom = (
			strpos( $rate_id, 'distance_based' ) !== false ||
			strpos( $rate_id, 'dbs_' ) !== false ||
			strpos( $rate_id, 'distance_based_shipping' ) !== false ||
			( isset( $rate->method_id ) && strpos( $rate->method_id, 'distance_based' ) !== false ) ||
			( isset( $rate->method_id ) && strpos( $rate->method_id, 'dbs_' ) !== false )
		);
		
		$style = $is_custom ? 'color: #4caf50; font-weight: bold;' : 'color: #666;';
		
		echo '<li style="' . $style . '">';
		echo '<strong>ID:</strong> ' . esc_html( $rate_id ) . '<br>';
		echo '<strong>Název:</strong> ' . esc_html( $rate->label ) . '<br>';
		echo '<strong>Cena:</strong> ' . wc_price( $rate->cost ) . '<br>';
		echo '<strong>Tax Status:</strong> ' . ( isset( $rate->tax_status ) ? esc_html( $rate->tax_status ) : 'N/A' ) . '<br>';
		echo '<strong>Calc Tax:</strong> ' . ( isset( $rate->calc_tax ) ? esc_html( $rate->calc_tax ) : 'N/A' ) . '<br>';
		
		// Kontrola meta dat
		if ( isset( $rate->meta_data ) && is_array( $rate->meta_data ) ) {
			echo '<strong>Meta Data:</strong><br>';
			foreach ( $rate->meta_data as $key => $value ) {
				echo '- ' . esc_html( $key ) . ': ' . esc_html( is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value ) . '<br>';
			}
		}
		
		// Kontrola, zda je cena správně nastavena
		if ( $is_custom ) {
			$has_custom_method = true;
			
			// Kontrola, zda je cena považována za brutto
			$price_includes_tax = isset( $rate->meta_data['price_includes_tax'] ) && $rate->meta_data['price_includes_tax'];
			$is_brutto_price = isset( $rate->meta_data['is_brutto_price'] ) && $rate->meta_data['is_brutto_price'];
			
			if ( $price_includes_tax && $is_brutto_price ) {
				echo '<span style="color: #4caf50;"><strong>✓</strong> Cena je označena jako brutto</span><br>';
			} else {
				echo '<span style="color: #f44336;"><strong>✗</strong> Cena není označena jako brutto</span><br>';
				$tax_issues_found = true;
			}
		}
		
		echo '</li>';
	}
	
	echo '</ul>';
	
	// Shrnutí
	echo '<h4>Shrnutí:</h4>';
	
	if ( $has_custom_method ) {
		echo '<p style="color: #4caf50;"><strong>✓</strong> Naše custom shipping metoda je dostupná.</p>';
	} else {
		echo '<p style="color: #f44336;"><strong>✗</strong> Naše custom shipping metoda není dostupná.</p>';
	}
	
	if ( $tax_issues_found ) {
		echo '<p style="color: #f44336;"><strong>✗</strong> Nalezeny problémy s DPH - ceny nejsou označeny jako brutto.</p>';
	} else {
		echo '<p style="color: #4caf50;"><strong>✓</strong> DPH je správně nastaveno - ceny jsou označeny jako brutto.</p>';
	}
	
	// Kontrola nastavení
	$price_includes_tax = get_option( 'dbs_price_includes_tax', '1' );
	$tax_status = get_option( 'dbs_tax_status', 'none' );
	
	echo '<h4>Nastavení DPH:</h4>';
	echo '<p><strong>Ceny včetně DPH:</strong> ' . ( $price_includes_tax ? 'Zapnuto' : 'Vypnuto' ) . '</p>';
	echo '<p><strong>Tax Status:</strong> ' . esc_html( $tax_status ) . '</p>';
	
	if ( $has_custom_method && ! $tax_issues_found ) {
		echo '<p style="color: #4caf50; font-weight: bold;"><strong>✓ Úspěch!</strong> DPH je správně nastaveno.</p>';
	} else {
		echo '<p style="color: #f44336; font-weight: bold;"><strong>✗ Problém!</strong> DPH není správně nastaveno.</p>';
	}
	
	echo '</div>';
}

/**
 * Přidá testovací stránku do admin menu.
 */
function dbs_add_tax_test_page() {
	add_submenu_page(
		'woocommerce',
		'DBS Test Tax',
		'DBS Test Tax',
		'manage_woocommerce',
		'dbs-test-tax',
		'dbs_tax_test_page'
	);
}

/**
 * Zobrazí testovací stránku.
 */
function dbs_tax_test_page() {
	echo '<div class="wrap">';
	echo '<h1>DBS Test Tax Handling</h1>';
	echo '<p>Tato stránka slouží k testování, zda naše shipping sazby správně zpracovávají DPH.</p>';
	
	dbs_test_tax_handling();
	
	echo '<h3>Instrukce pro testování:</h3>';
	echo '<ol>';
	echo '<li>Přidejte produkty do košíku</li>';
	echo '<li>Přejděte na stránku pokladny (Checkout)</li>';
	echo '<li>Zkontrolujte, zda se zobrazuje správná cena dopravy (bez přidaného DPH)</li>';
	echo '<li>Ověřte, zda se cena shoduje s nastavením v pravidlech (50 Kč → 50 Kč, ne 60.50 Kč)</li>';
	echo '</ol>';
	
	echo '<h3>Očekávané chování:</h3>';
	echo '<ul>';
	echo '<li>Cena 50 Kč by se měla zobrazovat jako 50 Kč (ne 60.50 Kč)</li>';
	echo '<li>Cena 100 Kč by se měla zobrazovat jako 100 Kč (ne 121 Kč)</li>';
	echo '<li>Meta data by měla obsahovat <code>price_includes_tax: true</code></li>';
	echo '<li>Meta data by měla obsahovat <code>is_brutto_price: true</code></li>';
	echo '</ul>';
	
	echo '<h3>Debug informace:</h3>';
	echo '<p><strong>WP_DEBUG:</strong> ' . ( defined( 'WP_DEBUG' ) && WP_DEBUG ? 'Zapnuto' : 'Vypnuto' ) . '</p>';
	echo '<p><strong>WooCommerce verze:</strong> ' . ( defined( 'WC_VERSION' ) ? WC_VERSION : 'Neznámá' ) . '</p>';
	echo '<p><strong>Plugin verze:</strong> ' . ( defined( 'DBS_PLUGIN_VERSION' ) ? DBS_PLUGIN_VERSION : 'Neznámá' ) . '</p>';
	
	echo '</div>';
}

// Přidat testovací stránku pouze v admin
if ( is_admin() ) {
	add_action( 'admin_menu', 'dbs_add_tax_test_page' );
} 
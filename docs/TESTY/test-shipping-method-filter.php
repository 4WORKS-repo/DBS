<?php
/**
 * Testovací skript pro ověření filtrování shipping metod.
 * 
 * Tento soubor slouží k testování, zda naše filtry správně odstraňují
 * výchozí WooCommerce shipping metody a ponechávají pouze naše custom metody.
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Testovací funkce pro ověření shipping metod.
 */
function dbs_test_shipping_methods() {
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
	echo '<h3>Testování Shipping Metod</h3>';
	
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
	
	echo '<h4>Dostupné shipping metody:</h4>';
	echo '<ul>';
	
	$has_custom_method = false;
	$has_default_method = false;
	
	foreach ( $rates as $rate_id => $rate ) {
		$is_custom = (
			strpos( $rate_id, 'distance_based' ) !== false ||
			strpos( $rate_id, 'dbs_' ) !== false ||
			strpos( $rate_id, 'distance_based_shipping' ) !== false ||
			( isset( $rate->method_id ) && strpos( $rate->method_id, 'distance_based' ) !== false ) ||
			( isset( $rate->method_id ) && strpos( $rate->method_id, 'dbs_' ) !== false )
		);
		
		$is_default = (
			strpos( $rate_id, 'flat_rate' ) !== false ||
			strpos( $rate_id, 'free_shipping' ) !== false ||
			strpos( $rate_id, 'local_pickup' ) !== false ||
			( isset( $rate->method_id ) && strpos( $rate->method_id, 'flat_rate' ) !== false ) ||
			( isset( $rate->method_id ) && strpos( $rate->method_id, 'free_shipping' ) !== false ) ||
			( isset( $rate->method_id ) && strpos( $rate->method_id, 'local_pickup' ) !== false )
		);
		
		$style = $is_custom ? 'color: #4caf50; font-weight: bold;' : ( $is_default ? 'color: #f44336;' : 'color: #666;' );
		
		echo '<li style="' . $style . '">';
		echo '<strong>ID:</strong> ' . esc_html( $rate_id ) . '<br>';
		echo '<strong>Název:</strong> ' . esc_html( $rate->label ) . '<br>';
		echo '<strong>Cena:</strong> ' . wc_price( $rate->cost ) . '<br>';
		echo '<strong>Typ:</strong> ' . ( $is_custom ? 'Custom' : ( $is_default ? 'Default' : 'Other' ) );
		echo '</li>';
		
		if ( $is_custom ) {
			$has_custom_method = true;
		}
		if ( $is_default ) {
			$has_default_method = true;
		}
	}
	
	echo '</ul>';
	
	// Shrnutí
	echo '<h4>Shrnutí:</h4>';
	
	if ( $has_custom_method ) {
		echo '<p style="color: #4caf50;"><strong>✓</strong> Naše custom shipping metoda je dostupná.</p>';
	} else {
		echo '<p style="color: #f44336;"><strong>✗</strong> Naše custom shipping metoda není dostupná.</p>';
	}
	
	if ( $has_default_method ) {
		echo '<p style="color: #f44336;"><strong>✗</strong> Výchozí WooCommerce shipping metody jsou stále dostupné - filtr nefunguje správně.</p>';
	} else {
		echo '<p style="color: #4caf50;"><strong>✓</strong> Výchozí WooCommerce shipping metody byly úspěšně odstraněny.</p>';
	}
	
	if ( $has_custom_method && ! $has_default_method ) {
		echo '<p style="color: #4caf50; font-weight: bold;"><strong>✓ Úspěch!</strong> Filtry fungují správně - pouze naše custom metoda je dostupná.</p>';
	} else {
		echo '<p style="color: #f44336; font-weight: bold;"><strong>✗ Problém!</strong> Filtry nefungují správně.</p>';
	}
	
	echo '</div>';
}

/**
 * Přidá testovací stránku do admin menu.
 */
function dbs_add_test_page() {
	add_submenu_page(
		'woocommerce',
		'DBS Test Shipping',
		'DBS Test Shipping',
		'manage_woocommerce',
		'dbs-test-shipping',
		'dbs_test_shipping_page'
	);
}

/**
 * Zobrazí testovací stránku.
 */
function dbs_test_shipping_page() {
	echo '<div class="wrap">';
	echo '<h1>DBS Test Shipping Methods</h1>';
	echo '<p>Tato stránka slouží k testování, zda naše filtry správně odstraňují výchozí WooCommerce shipping metody.</p>';
	
	dbs_test_shipping_methods();
	
	echo '<h3>Instrukce pro testování:</h3>';
	echo '<ol>';
	echo '<li>Přidejte produkty do košíku</li>';
	echo '<li>Přejděte na stránku pokladny (Checkout)</li>';
	echo '<li>Zkontrolujte, zda se zobrazuje pouze vaše custom shipping metoda</li>';
	echo '<li>Zkontrolujte, zda se nezobrazují výchozí WooCommerce metody (Flat Rate, Free Shipping, Local Pickup)</li>';
	echo '</ol>';
	
	echo '<h3>Debug informace:</h3>';
	echo '<p><strong>WP_DEBUG:</strong> ' . ( defined( 'WP_DEBUG' ) && WP_DEBUG ? 'Zapnuto' : 'Vypnuto' ) . '</p>';
	echo '<p><strong>WooCommerce verze:</strong> ' . ( defined( 'WC_VERSION' ) ? WC_VERSION : 'Neznámá' ) . '</p>';
	echo '<p><strong>Plugin verze:</strong> ' . ( defined( 'DBS_PLUGIN_VERSION' ) ? DBS_PLUGIN_VERSION : 'Neznámá' ) . '</p>';
	
	echo '</div>';
}

// Přidat testovací stránku pouze v admin
if ( is_admin() ) {
	add_action( 'admin_menu', 'dbs_add_test_page' );
} 
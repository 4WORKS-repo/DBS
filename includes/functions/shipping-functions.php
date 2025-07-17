<?php
/**
 * Dopravní funkce pro Distance Based Shipping plugin.
 *
 * Soubor: includes/functions/shipping-functions.php
 *
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Získá aplikovatelná pravidla pro danou vzdálenost a balíček.
 *
 * @param float $distance Vzdálenost v nakonfigurovaných jednotkách.
 * @param array $package WooCommerce balíček.
 * @return array Pole aplikovatelných pravidel.
 */
function dbs_get_applicable_shipping_rules( $distance, array $package ) {
	$all_rules = dbs_get_shipping_rules( true );
	$applicable_rules = array();

	foreach ( $all_rules as $rule ) {
		if ( dbs_is_shipping_rule_applicable( $rule, $distance, $package ) ) {
			$applicable_rules[] = $rule;
		}
	}

	// Seřazení podle priority - všechna pravidla jsou považována za plugin pravidla.
	usort( $applicable_rules, function( $a, $b ) {
		// Všechna pravidla jsou považována za plugin pravidla
		$a_is_plugin = true;
		$b_is_plugin = true;
		
		// Pokud je jedno plugin pravidlo a druhé ne, plugin má prioritu
		if ( $a_is_plugin && ! $b_is_plugin ) {
			return -1;
		}
		if ( ! $a_is_plugin && $b_is_plugin ) {
			return 1;
		}
		
		// Pokud jsou obě plugin pravidla nebo obě ne, řadit podle priority
		return $a->priority - $b->priority;
	} );

	// Označit všechna pravidla jako plugin pravidla pro frontend.
	foreach ( $applicable_rules as $rule ) {
		$rule->is_plugin_rule = true;
	}

	return $applicable_rules;
}

/**
 * Zkontroluje zda je pravidlo aplikovatelné pro aktuální podmínky.
 *
 * @param object $rule Dopravní pravidlo.
 * @param float  $distance Vzdálenost.
 * @param array  $package WooCommerce balíček.
 * @return bool True pokud je pravidlo aplikovatelné.
 */
function dbs_is_shipping_rule_applicable( $rule, $distance, array $package ) {
	// Kontrola rozsahu vzdálenosti.
	if ( $distance < $rule->distance_from ) {
		return false;
	}

	if ( $rule->distance_to > 0 && $distance > $rule->distance_to ) {
		return false;
	}

	// Kontrola částky objednávky.
	$cart_total = dbs_get_package_total( $package );
	
	if ( $rule->min_order_amount > 0 && $cart_total < $rule->min_order_amount ) {
		return false;
	}

	if ( $rule->max_order_amount > 0 && $cart_total > $rule->max_order_amount ) {
		return false;
	}

	// Kontrola kategorií produktů.
	if ( ! empty( $rule->product_categories ) ) {
		$rule_categories = maybe_unserialize( $rule->product_categories );
		if ( ! dbs_package_has_product_categories( $package, $rule_categories ) ) {
			return false;
		}
	}

	// Kontrola dopravních tříd.
	if ( ! empty( $rule->shipping_classes ) ) {
		$rule_classes = maybe_unserialize( $rule->shipping_classes );
		if ( ! dbs_package_has_shipping_classes( $package, $rule_classes ) ) {
			return false;
		}
	}

	// Kontrola hmotnosti a rozměrů (nové podmínky)
	if ( function_exists( 'dbs_check_all_conditions' ) ) {
		if ( ! dbs_check_all_conditions( $rule, $package ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Získá celkovou hodnotu balíčku.
 *
 * @param array $package WooCommerce balíček.
 * @return float Celková hodnota balíčku.
 */
function dbs_get_package_total( array $package ) {
	$total = 0;

	if ( ! empty( $package['contents'] ) ) {
		foreach ( $package['contents'] as $item ) {
			$total += $item['line_total'];
		}
	} else {
		// Fallback na WC_Cart pokud balíček neobsahuje contents.
		if ( WC()->cart ) {
			$total = WC()->cart->get_cart_contents_total();
		}
	}

	return (float) $total;
}

/**
 * Zkontroluje zda balíček obsahuje produkty ze specifikovaných kategorií.
 *
 * @param array $package WooCommerce balíček.
 * @param array $categories ID kategorií k ověření.
 * @return bool True pokud balíček obsahuje produkty z kategorií.
 */
function dbs_package_has_product_categories( array $package, array $categories ): bool {
	if ( empty( $categories ) ) {
		return true;
	}

	foreach ( $package['contents'] as $item ) {
		$product = $item['data'];
		$product_categories = wp_get_post_terms( $product->get_id(), 'product_cat', [ 'fields' => 'ids' ] );
		
		if ( array_intersect( $product_categories, $categories ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Zkontroluje zda balíček obsahuje produkty se specifikovanými dopravními třídami.
 *
 * @param array $package WooCommerce balíček.
 * @param array $classes ID dopravních tříd k ověření.
 * @return bool True pokud balíček obsahuje produkty s dopravními třídami.
 */
function dbs_package_has_shipping_classes( array $package, array $classes ): bool {
	if ( empty( $classes ) ) {
		return true;
	}

	foreach ( $package['contents'] as $item ) {
		$product = $item['data'];
		$shipping_class_id = $product->get_shipping_class_id();
		
		if ( in_array( $shipping_class_id, $classes, true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Vypočítá dopravní sazbu z pravidla.
 *
 * @param object $rule Dopravní pravidlo.
 * @param float  $distance Vzdálenost.
 * @param array  $package WooCommerce balíček.
 * @return array|null Pole sazby nebo null.
 */
function dbs_calculate_shipping_rate_from_rule( object $rule, float $distance, array $package ): ?array {
	$base_rate = (float) $rule->base_rate;
	$per_km_rate = (float) $rule->per_km_rate;

	$total_cost = $base_rate + ( $distance * $per_km_rate );

	// Aplikování filtrů pro vlastní výpočty.
	$total_cost = apply_filters( 'dbs_calculate_shipping_cost', $total_cost, $rule, $distance, $package );

	// Zajištění nezáporné ceny.
	if ( $total_cost < 0 ) {
		$total_cost = 0;
	}

	return [
		'id'        => 'distance_based_rule_' . $rule->id,
		'label'     => $rule->rule_name,
		'cost'      => $total_cost,
		'calc_tax'  => 'per_order',
		'meta_data' => [
			'distance'      => $distance,
			'distance_unit' => 'km',
			'rule_id'       => $rule->id,
			'base_rate'     => $base_rate,
			'per_km_rate'   => $per_km_rate,
		],
	];
}

/**
 * Získá záložní dopravní sazbu.
 *
 * @return array|null Pole záložní sazby nebo null.
 */
function dbs_get_fallback_shipping_rate(): ?array {
	$fallback_rate = (float) get_option( 'dbs_fallback_rate', 10 );

	if ( $fallback_rate <= 0 ) {
		return null;
	}

	return [
		'id'        => 'distance_based_fallback',
		'label'     => __( 'Standard Shipping', 'distance-shipping' ),
		'cost'      => $fallback_rate,
		'calc_tax'  => 'per_order',
		'meta_data' => [
			'fallback' => true,
		],
	];
}

/**
 * Získá adresu z balíčku.
 *
 * @param array $package WooCommerce balíček.
 * @return string Adresa nebo prázdný řetězec.
 */
function dbs_build_destination_address_from_package( array $package ): string {
	$destination_parts = [];

	if ( ! empty( $package['destination']['address'] ) ) {
		$destination_parts[] = $package['destination']['address'];
	}

	if ( ! empty( $package['destination']['address_2'] ) ) {
		$destination_parts[] = $package['destination']['address_2'];
	}

	if ( ! empty( $package['destination']['city'] ) ) {
		$destination_parts[] = $package['destination']['city'];
	}

	if ( ! empty( $package['destination']['state'] ) ) {
		$destination_parts[] = $package['destination']['state'];
	}

	if ( ! empty( $package['destination']['postcode'] ) ) {
		$destination_parts[] = $package['destination']['postcode'];
	}

	if ( ! empty( $package['destination']['country'] ) ) {
		$countries = WC()->countries->get_countries();
		if ( isset( $countries[ $package['destination']['country'] ] ) ) {
			$destination_parts[] = $countries[ $package['destination']['country'] ];
		}
	}

	return implode( ', ', array_filter( $destination_parts ) );
}

/**
 * Najde nejbližší obchod k cílové adrese.
 *
 * @param string $destination Cílová adresa.
 * @return object|null Objekt obchodu nebo null.
 */
function dbs_find_nearest_store( string $destination ): ?object {
	$stores = dbs_get_stores( true );
	
	if ( empty( $stores ) ) {
		return null;
	}

	// Pokud je pouze jeden obchod, vrátíme ho.
	if ( count( $stores ) === 1 ) {
		return $stores[0];
	}

	// Pro více obchodů najdeme nejbližší.
	$nearest_store = null;
	$shortest_distance = PHP_FLOAT_MAX;

	foreach ( $stores as $store ) {
		$distance = dbs_calculate_distance( $store->address, $destination );
		
		if ( false !== $distance && $distance < $shortest_distance ) {
			$shortest_distance = $distance;
			$nearest_store = $store;
		}
	}

	return $nearest_store;
}

/**
 * Zkontroluje zda je dopravní metoda dostupná.
 *
 * @param array $package WooCommerce balíček.
 * @return bool True pokud je metoda dostupná.
 */
function dbs_is_shipping_method_available( array $package ): bool {
	// Kontrola zda má balíček obsah.
	if ( empty( $package['contents'] ) ) {
		return false;
	}

	// Kontrola zda má balíček adresu.
	$destination = dbs_build_destination_address_from_package( $package );
	if ( empty( $destination ) ) {
		return false;
	}

	// Kontrola zda existují obchody.
	$stores = dbs_get_stores( true );
	if ( empty( $stores ) ) {
		return false;
	}

	// Kontrola zda existují pravidla.
	$rules = dbs_get_shipping_rules( true );
	if ( empty( $rules ) ) {
		return false;
	}

	return true;
}

/**
 * Formátuje dopravní sazbu pro zobrazení.
 *
 * @param array $rate Pole sazby.
 * @return array Formátovaná sazba.
 */
function dbs_format_shipping_rate( array $rate ) {
	$rate['formatted_cost'] = wc_price( $rate['cost'] );
	
	if ( ! empty( $rate['meta_data']['distance'] ) ) {
		$distance = $rate['meta_data']['distance'];
		$unit = $rate['meta_data']['distance_unit'] ?? 'km';
		$rate['distance_info'] = sprintf( '%.1f %s', $distance, $unit );
	}

	return $rate;
}

/**
 * Zaloguje výpočet dopravy pro debugging.
 *
 * @param string $origin Výchozí adresa.
 * @param string $destination Cílová adresa.
 * @param float $distance Vzdálenost.
 * @param array $rates Dopravní sazby.
 * @return void
 */
function dbs_log_shipping_calculation( $origin, $destination, $distance, array $rates ) {
	if ( ! get_option( 'dbs_debug_mode', 0 ) ) {
		return;
	}

	$log_data = [
		'origin' => $origin,
		'destination' => $destination,
		'distance' => $distance,
		'rates_count' => count( $rates ),
		'timestamp' => current_time( 'mysql' ),
	];

	dbs_log_debug( 'Shipping calculation: ' . wp_json_encode( $log_data ) );
}

/**
 * Přidá hooky pro lepší integraci s WooCommerce.
 *
 * @return void
 */
function dbs_add_woocommerce_hooks(): void {
	// Ensure WooCommerce is fully loaded
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	
	try {
		// Zakázat výchozí shipping metody
		dbs_disable_default_shipping_methods();
		
		// Hook pro aktualizaci dopravy při změně adresy - much higher priority
		add_action( 'woocommerce_checkout_update_order_review', 'dbs_handle_address_update', 50 );
		
		// Hook pro zobrazení informací o dopravě - much higher priority
		add_action( 'woocommerce_after_shipping_calculator', 'dbs_display_shipping_info', 50 );
		
		// Hook pro vylepšení zobrazení dopravních metod - much higher priority
		add_filter( 'woocommerce_shipping_method_title', 'dbs_enhance_shipping_method_display', 50, 2 );
		
		// Hook pro uložení informací o vzdálenosti do session - much higher priority
		add_action( 'woocommerce_shipping_method_chosen', 'dbs_save_distance_info_to_session', 50 );
		
		// Hook pro zobrazení shipping informací na cart stránce
		add_action( 'woocommerce_cart_collaterals', 'dbs_display_cart_shipping_info', 15 );
		
		// Hook pro aktualizaci cart totals při změně shipping
		add_action( 'woocommerce_cart_updated', 'dbs_handle_cart_update', 50 );
		
		// Hook pro zobrazení shipping informací na checkout
		add_action( 'woocommerce_checkout_before_order_review_heading', 'dbs_display_checkout_shipping_info', 10 );
		
		// Hook pro validaci adresy na checkout
		add_action( 'woocommerce_checkout_process', 'dbs_validate_checkout_address', 10 );
		
		// Hook pro automatické aplikování shipping sazby
		add_action( 'woocommerce_before_calculate_totals', 'dbs_apply_stored_shipping_rate', 50 );
		
	} catch ( Exception $e ) {
		// Log error but don't break WooCommerce
		error_log( 'DBS WooCommerce Hooks Error: ' . $e->getMessage() );
	}
}

/**
 * Zpracuje aktualizaci adresy.
 *
 * @param string $post_data POST data z checkout formuláře.
 * @return void
 */
function dbs_handle_address_update( $post_data ): void {
	parse_str( $post_data, $data );
	
	// Check if WC and session are available before accessing
	if ( function_exists( 'WC' ) && WC() && WC()->session ) {
		// Uložíme adresu do session pro pozdější použití
		if ( ! empty( $data['shipping_address_1'] ) ) {
			$shipping_address = implode( ', ', array_filter( [
				$data['shipping_address_1'] ?? '',
				$data['shipping_address_2'] ?? '',
				$data['shipping_city'] ?? '',
				$data['shipping_state'] ?? '',
				$data['shipping_postcode'] ?? '',
				$data['shipping_country'] ?? '',
			] ) );
			
			if ( ! empty( $shipping_address ) ) {
				WC()->session->set( 'shipping_address', $shipping_address );
			}
		}
	}
}

/**
 * Zobrazí informace o dopravě.
 *
 * @return void
 */
function dbs_display_shipping_info(): void {
	// Check if WC, cart, and session are available before accessing
	if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart || ! WC()->session ) {
		return;
	}
	
	if ( ! WC()->cart->needs_shipping() ) {
		return;
	}

	$stores = dbs_get_stores( true );
	if ( empty( $stores ) ) {
		return;
	}

	// echo '<div class="dbs-shipping-info" style="margin: 10px 0; padding: 10px; background: #f7f7f7; border-radius: 4px;">';
	// echo '<p><strong>' . esc_html__( 'Distance Based Shipping', 'distance-shipping' ) . '</strong></p>';
	// echo '<p>' . esc_html__( 'Shipping rates are calculated based on distance from our stores to your address.', 'distance-shipping' ) . '</p>';
	// if ( count( $stores ) > 1 ) {
	// 	echo '<p>' . esc_html__( 'We will automatically select the nearest store for shipping calculation.', 'distance-shipping' ) . '</p>';
	// }
	// echo '</div>';
}

/**
 * Vylepší zobrazení dopravní metody.
 *
 * @param string $title Název metody.
 * @param object $method Objekt dopravní metody.
 * @return string Vylepšený název.
 */
function dbs_enhance_shipping_method_display( string $title, $method ): string {
	if ( $method->id === 'distance_based' ) {
		// Přidáme informace o vzdálenosti pokud jsou dostupné
		// Check if WC and session are available before accessing
		if ( function_exists( 'WC' ) && WC() && WC()->session ) {
			$distance_info = WC()->session->get( 'dbs_distance_info' );
			if ( $distance_info ) {
				$title .= ' (' . esc_html( $distance_info ) . ')';
			}
		}
	}
	
	return $title;
}

/**
 * Uloží informace o vzdálenosti do session.
 *
 * @param string $method_id ID vybrané dopravní metody.
 * @return void
 */
function dbs_save_distance_info_to_session( $method_id ): void {
	if ( $method_id === 'distance_based' ) {
		// Check if WC and session are available before accessing
		if ( function_exists( 'WC' ) && WC() && WC()->session ) {
			// Zde můžeme uložit dodatečné informace o vzdálenosti
			// které budou použity při zobrazení metody
			$distance_info = WC()->session->get( 'dbs_calculated_distance' );
			if ( $distance_info ) {
				WC()->session->set( 'dbs_distance_info', $distance_info );
			}
		}
	}
}

/**
 * Získá informace o nejbližším obchodě a vzdálenosti.
 *
 * @param string $destination Cílová adresa.
 * @return array|null Informace o obchodě a vzdálenosti nebo null.
 */
function dbs_get_nearest_store_info( string $destination ): ?array {
	$store = dbs_find_nearest_store( $destination );
	if ( ! $store ) {
		return null;
	}

	$distance = dbs_calculate_distance( $store->address, $destination );
	if ( false === $distance ) {
		return null;
	}

	return [
		'store' => $store,
		'distance' => $distance,
		'formatted_distance' => dbs_format_distance( $distance ),
	];
}

/**
 * Zobrazí shipping kalkulátor na cart stránce.
 *
 * @return void
 */
function dbs_display_cart_shipping_calculator(): void {
	// Kontrola, že jsme na cart stránce
	if ( ! is_cart() ) {
		return;
	}
	
	// Kontrola, že WooCommerce je načtené
	if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
		return;
	}
	
	// Kontrola, že cart má položky
	if ( WC()->cart->is_empty() ) {
		return;
	}
	
	// Zobrazit shipping kalkulátor
	echo '<div class="dbs-cart-shipping-calculator-wrapper">';
	echo '<div class="dbs-cart-shipping-calculator">';
	echo '<div class="dbs-calculator-header">';
	echo '<h3>' . esc_html__( 'Kalkulátor dopravy', 'distance-shipping' ) . '</h3>';
	echo '</div>';
	echo '<div class="dbs-calculator-form">';
	echo '<div class="dbs-form-group">';
	echo '<label for="dbs-cart-address">' . esc_html__( 'Doručovací adresa', 'distance-shipping' ) . '</label>';
	echo '<input type="text" id="dbs-cart-address" class="dbs-address-input" placeholder="' . esc_attr__( 'Zadejte adresu pro výpočet dopravy', 'distance-shipping' ) . '">';
	echo '<div class="dbs-address-suggestions" id="dbs-cart-suggestions"></div>';
	echo '</div>';
	echo '<div class="dbs-form-group">';
	echo '<button type="button" id="dbs-cart-calculate" class="dbs-calculate-btn">';
	echo '<span class="dbs-btn-text">' . esc_html__( 'Vypočítat dopravu', 'distance-shipping' ) . '</span>';
	echo '<span class="dbs-btn-loading" style="display: none;">';
	echo '<span class="dbs-spinner"></span>' . esc_html__( 'Počítám...', 'distance-shipping' );
	echo '</span>';
	echo '</button>';
	echo '</div>';
	echo '</div>';
	echo '<div class="dbs-calculator-result" id="dbs-cart-result" style="display: none;">';
	echo '<div class="dbs-result-content">';
	echo '<div class="dbs-distance-info">';
	echo '<span class="dbs-distance-label">' . esc_html__( 'Vzdálenost:', 'distance-shipping' ) . '</span>';
	echo '<span class="dbs-distance-value"></span>';
	echo '</div>';
	echo '<div class="dbs-cost-info">';
	echo '<span class="dbs-cost-label">' . esc_html__( 'Cena dopravy:', 'distance-shipping' ) . '</span>';
	echo '<span class="dbs-cost-value"></span>';
	echo '</div>';
	echo '<div class="dbs-delivery-info">';
	echo '<span class="dbs-delivery-label">' . esc_html__( 'Doba doručení:', 'distance-shipping' ) . '</span>';
	echo '<span class="dbs-delivery-value"></span>';
	echo '</div>';
	echo '</div>';
	echo '<div class="dbs-result-actions">';
	echo '<button type="button" class="dbs-apply-shipping-btn">' . esc_html__( 'Aplikovat dopravu', 'distance-shipping' ) . '</button>';
	echo '</div>';
	echo '</div>';
	echo '<div class="dbs-calculator-error" id="dbs-cart-error" style="display: none;">';
	echo '<div class="dbs-error-message"></div>';
	echo '</div>';
	echo '</div>';
	echo '</div>';
}

/**
 * Zobrazí shipping informace na cart stránce.
 *
 * @return void
 */
function dbs_display_cart_shipping_info(): void {
	// Kontrola, že jsme na cart stránce
	if ( ! is_cart() ) {
		return;
	}
	
	// Kontrola, že WooCommerce je načtené
	if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
		return;
	}
	
	// Kontrola, že cart má položky
	if ( WC()->cart->is_empty() ) {
		return;
	}
	
	// Zobrazit informace o aplikované shipping sazbě
	if ( WC()->session ) {
		$applied_rate = WC()->session->get( 'dbs_applied_shipping_rate' );
		$distance_info = WC()->session->get( 'dbs_distance_info' );
		$store_info = WC()->session->get( 'dbs_store_info' );
		
		if ( $applied_rate ) {
			echo '<div class="dbs-cart-shipping-info">';
			echo '<h3>' . esc_html__( 'Informace o dopravě', 'distance-shipping' ) . '</h3>';
			echo '<div class="dbs-shipping-details">';
			
			if ( $store_info ) {
				echo '<p><strong>' . esc_html__( 'Nejbližší obchod:', 'distance-shipping' ) . '</strong> ' . esc_html( $store_info->name ) . '</p>';
			}
			
			if ( $distance_info ) {
				echo '<p><strong>' . esc_html__( 'Vzdálenost:', 'distance-shipping' ) . '</strong> ' . esc_html( $distance_info ) . '</p>';
			}
			
			echo '<p><strong>' . esc_html__( 'Dopravní sazba:', 'distance-shipping' ) . '</strong> ' . esc_html( $applied_rate['label'] ) . ' - ' . wc_price( $applied_rate['cost'] ) . '</p>';
			
			echo '</div>';
			echo '</div>';
		}
	}
}

/**
 * Zpracuje aktualizaci cart.
 *
 * @return void
 */
function dbs_handle_cart_update(): void {
	// Kontrola, že WooCommerce je načtené
	if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
		return;
	}
	
	// Kontrola, že cart má položky
	if ( WC()->cart->is_empty() ) {
		// Vymazat shipping data pokud je cart prázdný
		if ( WC()->session ) {
			WC()->session->__unset( 'dbs_applied_shipping_rate' );
			WC()->session->__unset( 'dbs_distance_info' );
			WC()->session->__unset( 'dbs_store_info' );
		}
		return;
	}
	
	// Shipping sazba se nyní aplikuje pouze přes nativní WooCommerce shipping systém
}

/**
 * Zobrazí shipping informace na checkout stránce.
 *
 * @return void
 */
function dbs_display_checkout_shipping_info(): void {
	// Kontrola, že jsme na checkout stránce
	if ( ! is_checkout() ) {
		return;
	}
	
	// Kontrola, že WooCommerce je načtené
	if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
		return;
	}
	
	// Kontrola, že cart má položky
	if ( WC()->cart->is_empty() ) {
		return;
	}
	
	// Zobrazit informace o aplikované shipping sazbě
	if ( WC()->session ) {
		$applied_rate = WC()->session->get( 'dbs_applied_shipping_rate' );
		$distance_info = WC()->session->get( 'dbs_distance_info' );
		$store_info = WC()->session->get( 'dbs_store_info' );
		
		if ( $applied_rate ) {
			echo '<div class="dbs-checkout-shipping-info">';
			echo '<h3>' . esc_html__( 'Informace o dopravě', 'distance-shipping' ) . '</h3>';
			echo '<div class="dbs-shipping-details">';
			
			if ( $store_info ) {
				echo '<p><strong>' . esc_html__( 'Nejbližší obchod:', 'distance-shipping' ) . '</strong> ' . esc_html( $store_info->name ) . '</p>';
			}
			
			if ( $distance_info ) {
				echo '<p><strong>' . esc_html__( 'Vzdálenost:', 'distance-shipping' ) . '</strong> ' . esc_html( $distance_info ) . '</p>';
			}
			
			echo '<p><strong>' . esc_html__( 'Dopravní sazba:', 'distance-shipping' ) . '</strong> ' . esc_html( $applied_rate['label'] ) . ' - ' . wc_price( $applied_rate['cost'] ) . '</p>';
			
			echo '</div>';
			echo '</div>';
		}
	}
}

/**
 * Validuje adresu na checkout.
 *
 * @return void
 */
function dbs_validate_checkout_address(): void {
	// Kontrola, že WooCommerce je načtené
	if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
		return;
	}
	
	// Získat shipping adresu
	$shipping_address = '';
	$shipping_fields = [
		'shipping_address_1',
		'shipping_city',
		'shipping_postcode',
		'shipping_country'
	];
	
	foreach ( $shipping_fields as $field ) {
		$value = sanitize_text_field( $_POST[ $field ] ?? '' );
		if ( ! empty( $value ) ) {
			$shipping_address .= $value . ', ';
		}
	}
	
	$shipping_address = rtrim( $shipping_address, ', ' );
	
	// Pokud je shipping adresa prázdná, použít billing adresu
	if ( empty( $shipping_address ) ) {
		$billing_fields = [
			'billing_address_1',
			'billing_city',
			'billing_postcode',
			'billing_country'
		];
		
		foreach ( $billing_fields as $field ) {
			$value = sanitize_text_field( $_POST[ $field ] ?? '' );
			if ( ! empty( $value ) ) {
				$shipping_address .= $value . ', ';
			}
		}
		
		$shipping_address = rtrim( $shipping_address, ', ' );
	}
	
	// Validovat adresu
	if ( ! empty( $shipping_address ) ) {
		$validation_result = dbs_validate_address_format( $shipping_address );
		
		if ( ! $validation_result['is_valid'] ) {
			wc_add_notice( $validation_result['message'], 'error' );
		}
	}
}

/**
 * Automaticky aplikuje uloženou shipping sazbu.
 *
 * @return void
 */
function dbs_apply_stored_shipping_rate(): void {
	// Kontrola, že WooCommerce je načtené
	if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
		return;
	}
	
	// Kontrola, že cart má položky
	if ( WC()->cart->is_empty() ) {
		return;
	}
	
	// Shipping sazba se nyní aplikuje pouze přes nativní WooCommerce shipping systém
}

/**
 * Odstraní všechny ostatní shipping metody a ponechá pouze naši Distance Based Shipping.
 * Tato funkce se spustí s vysokou prioritou, aby přepsala všechny ostatní metody.
 *
 * @param array $rates Dostupné shipping metody.
 * @return array Upravené shipping metody.
 */
function dbs_filter_shipping_methods( $rates ) {
	// Kontrola, že WooCommerce je načtené
	if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
		return $rates;
	}
	
	// Kontrola, že cart má položky
	if ( WC()->cart->is_empty() ) {
		return $rates;
	}
	
	// Najít naši Distance Based Shipping metodu
	$dbs_rate = null;
	$dbs_rate_id = null;
	
	foreach ( $rates as $rate_id => $rate ) {
		// Kontrola různých možných názvů naší metody
		if ( 
			strpos( $rate_id, 'distance_based' ) !== false ||
			strpos( $rate_id, 'dbs_' ) !== false ||
			strpos( $rate_id, 'distance_based_shipping' ) !== false ||
			( dbs_has_method_id( $rate ) && strpos( $rate->method_id, 'distance_based' ) !== false ) ||
			( dbs_has_method_id( $rate ) && strpos( $rate->method_id, 'dbs_' ) !== false )
		) {
			$dbs_rate = $rate;
			$dbs_rate_id = $rate_id;
			break;
		}
	}
	
	// Pokud máme naši metodu, vrátit pouze ji a odstranit všechny ostatní
	if ( $dbs_rate ) {
		// Log pro debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'DBS: Našel jsem naši shipping metodu: ' . $dbs_rate_id );
			error_log( 'DBS: Odstraňuji všechny ostatní shipping metody' );
		}
		
		// Vrátit pouze naši metodu
		return array( $dbs_rate_id => $dbs_rate );
	}
	
	// Pokud nemáme naši metodu, ale máme nějaké rates, zkusit odstranit výchozí metody
	if ( ! empty( $rates ) ) {
		$filtered_rates = array();
		
		foreach ( $rates as $rate_id => $rate ) {
			// Zachovat pouze naše custom metody, odstranit výchozí WooCommerce metody
			if ( 
				strpos( $rate_id, 'flat_rate' ) === false &&
				strpos( $rate_id, 'free_shipping' ) === false &&
				strpos( $rate_id, 'local_pickup' ) === false &&
				! dbs_has_method_id( $rate ) || 
				( 
					strpos( $rate->method_id, 'flat_rate' ) === false &&
					strpos( $rate->method_id, 'free_shipping' ) === false &&
					strpos( $rate->method_id, 'local_pickup' ) === false
				)
			) {
				$filtered_rates[ $rate_id ] = $rate;
			}
		}
		
		// Pokud máme nějaké filtrované rates, vrátit je
		if ( ! empty( $filtered_rates ) ) {
			return $filtered_rates;
		}
	}
	
	// Pokud nemáme žádné naše metody, vrátit prázdné pole
	// Tím zabráníme zobrazení výchozích WooCommerce metod
	return array();
}
add_filter( 'woocommerce_package_rates', 'dbs_filter_shipping_methods', 999 );

/**
 * Agresivní filtr pro odstranění výchozích WooCommerce shipping metod.
 * Tento filtr se spustí s velmi vysokou prioritou.
 *
 * @param array $rates Dostupné shipping metody.
 * @return array Upravené shipping metody.
 */
function dbs_aggressive_filter_shipping_methods( $rates ) {
	// Kontrola, že WooCommerce je načtené
	if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
		return $rates;
	}
	
	// Kontrola, že cart má položky
	if ( WC()->cart->is_empty() ) {
		return $rates;
	}
	
	// Kontrola, zda máme nějakou naši custom metodu
	$has_custom_method = false;
	foreach ( $rates as $rate_id => $rate ) {
		if ( 
			strpos( $rate_id, 'distance_based' ) !== false ||
			strpos( $rate_id, 'dbs_' ) !== false ||
			strpos( $rate_id, 'distance_based_shipping' ) !== false ||
			( dbs_has_method_id( $rate ) && strpos( $rate->method_id, 'distance_based' ) !== false ) ||
			( dbs_has_method_id( $rate ) && strpos( $rate->method_id, 'dbs_' ) !== false )
		) {
			$has_custom_method = true;
			break;
		}
	}
	
	// Pokud máme naši custom metodu, odstranit všechny výchozí WooCommerce metody
	if ( $has_custom_method ) {
		$filtered_rates = array();
		
		foreach ( $rates as $rate_id => $rate ) {
			// Zachovat pouze naše custom metody
			if ( 
				strpos( $rate_id, 'distance_based' ) !== false ||
				strpos( $rate_id, 'dbs_' ) !== false ||
				strpos( $rate_id, 'distance_based_shipping' ) !== false ||
				( dbs_has_method_id( $rate ) && strpos( $rate->method_id, 'distance_based' ) !== false ) ||
				( dbs_has_method_id( $rate ) && strpos( $rate->method_id, 'dbs_' ) !== false )
			) {
				$filtered_rates[ $rate_id ] = $rate;
			}
		}
		
		// Log pro debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'DBS: Agresivní filtr - našel jsem custom metodu, odstraňuji výchozí metody' );
			error_log( 'DBS: Původní počet metod: ' . count( $rates ) . ', po filtrování: ' . count( $filtered_rates ) );
		}
		
		return $filtered_rates;
	}
	
	// Pokud nemáme naši custom metodu, vrátit původní rates
	return $rates;
}
add_filter( 'woocommerce_package_rates', 'dbs_aggressive_filter_shipping_methods', 999 );

/**
 * Bezpečně zkontroluje, zda má shipping rate method_id vlastnost.
 *
 * @param object $rate Shipping rate object.
 * @return bool True pokud má method_id.
 */
function dbs_has_method_id( $rate ): bool {
	// Bezpečná kontrola pomocí array_key_exists místo isset
	return is_object( $rate ) && property_exists( $rate, 'method_id' ) && ! empty( $rate->method_id );
}

/**
 * Zakáže výchozí WooCommerce shipping metody když je naše custom metoda aktivní.
 *
 * @return void
 */
function dbs_disable_default_shipping_methods(): void {
	// Kontrola, že WooCommerce je načtené
	if ( ! function_exists( 'WC' ) || ! WC() ) {
		return;
	}
	
	// Kontrola, že cart je dostupné a načtené
	if ( ! WC()->cart || ! did_action( 'woocommerce_cart_loaded_from_session' ) ) {
		return;
	}
	
	// Kontrola, že cart má položky
	if ( WC()->cart->is_empty() ) {
		return;
	}
	
	// Zakázat výchozí shipping metody
	add_filter( 'woocommerce_shipping_methods', function( $methods ) {
		// Odstranit výchozí metody
		unset( $methods['flat_rate'] );
		unset( $methods['free_shipping'] );
		unset( $methods['local_pickup'] );
		
		// Log pro debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'DBS: Zakazuji výchozí shipping metody' );
		}
		
		return $methods;
	}, 999 );
}

/**
 * Přidá hook pro zakázání výchozích shipping metod.
 *
 * @return void
 */
function dbs_add_shipping_method_disable_hooks(): void {
	// Spustit zakázání metod při načtení shipping
	add_action( 'woocommerce_shipping_init', 'dbs_disable_default_shipping_methods', 999 );
	
	// Také při načtení cart
	add_action( 'woocommerce_cart_loaded_from_session', 'dbs_disable_default_shipping_methods', 999 );
	
	// A při checkout
	add_action( 'woocommerce_checkout_init', 'dbs_disable_default_shipping_methods', 999 );
}
add_action( 'wp_loaded', 'dbs_add_shipping_method_disable_hooks', 10 );

/**
 * Zajistí, že naše shipping sazby jsou považovány za brutto (včetně DPH).
 *
 * @param array $rates Shipping rates.
 * @return array Upravené shipping rates.
 */
function dbs_ensure_shipping_rates_include_tax( $rates ) {
	// Kontrola, že WooCommerce je načtené
	if ( ! function_exists( 'WC' ) || ! WC() ) {
		return $rates;
	}
	
	// Kontrola, že cart je dostupné a načtené
	if ( ! WC()->cart || ! did_action( 'woocommerce_cart_loaded_from_session' ) ) {
		return $rates;
	}
	
	// Kontrola, že cart má položky
	if ( WC()->cart->is_empty() ) {
		return $rates;
	}
	
	foreach ( $rates as $rate_id => $rate ) {
		// Kontrola, zda je to naše custom shipping metoda
		if ( 
			strpos( $rate_id, 'distance_based' ) !== false ||
			strpos( $rate_id, 'dbs_' ) !== false ||
			strpos( $rate_id, 'distance_based_shipping' ) !== false ||
			( dbs_has_method_id( $rate ) && strpos( $rate->method_id, 'distance_based' ) !== false ) ||
			( dbs_has_method_id( $rate ) && strpos( $rate->method_id, 'dbs_' ) !== false )
		) {
			// Zajistíme, že cena je považována za brutto
			$rate->cost = (float) $rate->cost;
			
			// Nastavíme meta data pro označení, že cena je včetně DPH
			// Použijeme bezpečný způsob pro nastavení meta_data
			if ( method_exists( $rate, 'get_meta_data' ) ) {
				$meta_data = $rate->get_meta_data();
				if ( ! is_array( $meta_data ) ) {
					$meta_data = array();
				}
				$meta_data['price_includes_tax'] = true;
				$meta_data['is_brutto_price'] = true;
				
				// Použijeme add_meta_data místo set_meta_data
				if ( method_exists( $rate, 'add_meta_data' ) ) {
					$rate->add_meta_data( 'price_includes_tax', true );
					$rate->add_meta_data( 'is_brutto_price', true );
				}
			}
			
			// Debug logging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'DBS: Shipping rate marked as brutto - Rate ID: ' . $rate_id . ', Cost: ' . $rate->cost );
			}
		}
	}
	
	return $rates;
}
add_filter( 'woocommerce_package_rates', 'dbs_ensure_shipping_rates_include_tax', 1000 );

/**
 * Zajistí, že WooCommerce nebude aplikovat DPH na naše shipping sazby.
 *
 * @param bool $tax_status Tax status.
 * @param object $rate Shipping rate object.
 * @return bool Tax status.
 */
function dbs_control_shipping_tax_status( $tax_status, $rate ) {
	// Kontrola, zda je to naše custom shipping metoda
	if ( 
		strpos( $rate->id, 'distance_based' ) !== false ||
		strpos( $rate->id, 'dbs_' ) !== false ||
		strpos( $rate->id, 'distance_based_shipping' ) !== false
	) {
		// Nastavíme jako 'none' aby WooCommerce nepřidávalo DPH
		$tax_status = 'none';
		
		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'DBS: Shipping tax status set to none for rate: ' . $rate->id );
		}
	}
	
	return $tax_status;
}
add_filter( 'woocommerce_shipping_rate_tax_status', 'dbs_control_shipping_tax_status', 10, 2 );

/**
 * Zajistí, že naše shipping sazby nebudou znovu zdaněny.
 *
 * @param float $cost Shipping cost.
 * @param object $rate Shipping rate object.
 * @return float Shipping cost.
 */
function dbs_prevent_double_taxation( $cost, $rate ) {
	// Kontrola, zda je to naše custom shipping metoda
	if ( 
		strpos( $rate->id, 'distance_based' ) !== false ||
		strpos( $rate->id, 'dbs_' ) !== false ||
		strpos( $rate->id, 'distance_based_shipping' ) !== false
	) {
		// Vrátíme původní cenu bez přidání DPH
		$original_cost = (float) $cost;
		
		// Debug logging - pouze jednou za request
		static $logged_rates = array();
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! in_array( $rate->id, $logged_rates ) ) {
			error_log( 'DBS: Preventing double taxation - Original cost: ' . $original_cost . ' for rate: ' . $rate->id );
			$logged_rates[] = $rate->id;
		}
		
		return $original_cost;
	}
	
	return $cost;
}
add_filter( 'woocommerce_shipping_rate_cost', 'dbs_prevent_double_taxation', 10, 2 );
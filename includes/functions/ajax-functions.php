<?php
/**
 * AJAX funkce pro Distance Based Shipping plugin.
 *
 * Soubor: includes/functions/ajax-functions.php
 *
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inicializuje AJAX handlery.
 *
 * @return void
 */
function dbs_init_ajax_handlers(): void {
	// AJAX akce pro přihlášené uživatele.
	add_action( 'wp_ajax_dbs_geocode_address', 'dbs_ajax_geocode_address' );
	add_action( 'wp_ajax_dbs_test_distance', 'dbs_ajax_test_distance' );
	add_action( 'wp_ajax_dbs_clear_cache', 'dbs_ajax_clear_cache' );
	add_action( 'wp_ajax_dbs_update_store_coordinates', 'dbs_ajax_update_store_coordinates' );
	add_action( 'wp_ajax_dbs_validate_api_key', 'dbs_ajax_validate_api_key' );
	add_action( 'wp_ajax_dbs_test_api_key', 'dbs_ajax_validate_api_key' ); // Alias for test_api_key
	add_action( 'wp_ajax_dbs_test_cart_integration', 'dbs_ajax_test_cart_integration' );

	// AJAX akce pro nepřihlášené uživatele (frontend).
	add_action( 'wp_ajax_nopriv_dbs_calculate_shipping', 'dbs_ajax_calculate_shipping' );
	add_action( 'wp_ajax_dbs_calculate_shipping', 'dbs_ajax_calculate_shipping' );
	add_action( 'wp_ajax_nopriv_dbs_apply_shipping_to_cart', 'dbs_ajax_apply_shipping_to_cart' );
	add_action( 'wp_ajax_dbs_apply_shipping_to_cart', 'dbs_ajax_apply_shipping_to_cart' );
	add_action( 'wp_ajax_nopriv_dbs_remove_shipping_from_cart', 'dbs_ajax_remove_shipping_from_cart' );
	add_action( 'wp_ajax_dbs_remove_shipping_from_cart', 'dbs_ajax_remove_shipping_from_cart' );
	add_action( 'wp_ajax_nopriv_dbs_validate_address', 'dbs_ajax_validate_address' );
	add_action( 'wp_ajax_dbs_validate_address', 'dbs_ajax_validate_address' );
	
	// AJAX akce pro invalidaci shipping cache
	add_action( 'wp_ajax_nopriv_dbs_invalidate_shipping_cache', 'dbs_ajax_invalidate_shipping_cache' );
	add_action( 'wp_ajax_dbs_invalidate_shipping_cache', 'dbs_ajax_invalidate_shipping_cache' );
}

/**
 * AJAX handler pro geokódování adresy.
 *
 * @return void
 */
function dbs_ajax_geocode_address(): void {
	// Ověření nonce.
	if ( ! wp_verify_nonce( $_POST['nonce'], 'dbs_admin_nonce' ) ) {
		wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'distance-shipping' ) );
	}

	// Kontrola oprávnění.
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( __( 'Nemáte oprávnění k této akci.', 'distance-shipping' ) );
	}

	$address = sanitize_textarea_field( $_POST['address'] ?? '' );

	if ( empty( $address ) ) {
		wp_send_json_error( __( 'Adresa je povinná.', 'distance-shipping' ) );
	}

	$coordinates = dbs_geocode_address( $address );

	if ( $coordinates ) {
		wp_send_json_success( [
			'latitude'  => $coordinates['lat'],
			'longitude' => $coordinates['lng'],
			'message'   => __( 'Adresa byla úspěšně geokódována.', 'distance-shipping' ),
		] );
	} else {
		wp_send_json_error( __( 'Nepodařilo se geokódovat adresu.', 'distance-shipping' ) );
	}
}

/**
 * AJAX handler pro testování vzdálenosti.
 *
 * @return void
 */
function dbs_ajax_test_distance(): void {
	// Kontrola nonce.
	if ( ! wp_verify_nonce( $_POST['nonce'], 'dbs_admin_nonce' ) ) {
		wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'distance-shipping' ) );
	}

	$origin = sanitize_text_field( $_POST['origin'] ?? '' );
	$destination = sanitize_text_field( $_POST['destination'] ?? '' );

	if ( empty( $origin ) || empty( $destination ) ) {
		wp_send_json_error( __( 'Zadejte prosím obě adresy.', 'distance-shipping' ) );
	}

	// Vypočítáme vzdálenost s inteligentním vyhledáváním
	$distance = dbs_calculate_distance( $origin, $destination );

	if ( false === $distance ) {
		wp_send_json_error( __( 'Nepodařilo se vypočítat vzdálenost. Zkontrolujte adresy a zkuste to znovu.', 'distance-shipping' ) );
	}

	// Získáme informace o použitých adresách
	$origin_address_used = null;
	$destination_address_used = null;
	$origin_address_original = null;
	$destination_address_original = null;
	
	if ( WC()->session ) {
		$origin_address_used = WC()->session->get( 'dbs_origin_address_used' );
		$destination_address_used = WC()->session->get( 'dbs_destination_address_used' );
		$origin_address_original = WC()->session->get( 'dbs_origin_address_original' );
		$destination_address_original = WC()->session->get( 'dbs_destination_address_original' );
	}

	$response_data = [
		'distance' => $distance,
		'formatted_distance' => dbs_format_distance( $distance ),
		'distance_unit' => 'km',
		'message' => sprintf(
			__( 'Vzdálenost byla úspěšně vypočítána: %s', 'distance-shipping' ),
			dbs_format_distance( $distance )
		),
	];

	// Přidáme informace o standardizaci adres
	if ( $origin_address_used && $origin_address_original && $origin_address_used !== $origin_address_original ) {
		$response_data['origin_standardized'] = true;
		$response_data['origin_original'] = $origin_address_original;
		$response_data['origin_used'] = $origin_address_used;
	}

	if ( $destination_address_used && $destination_address_original && $destination_address_used !== $destination_address_original ) {
		$response_data['destination_standardized'] = true;
		$response_data['destination_original'] = $destination_address_original;
		$response_data['destination_used'] = $destination_address_used;
	}

	wp_send_json_success( $response_data );
}

/**
 * AJAX handler pro vyčištění cache.
 *
 * @return void
 */
function dbs_ajax_clear_cache(): void {
	// Ověření nonce.
	if ( ! wp_verify_nonce( $_POST['nonce'], 'dbs_admin_nonce' ) ) {
		wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'distance-shipping' ) );
	}

	// Kontrola oprávnění.
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( __( 'Nemáte oprávnění k této akci.', 'distance-shipping' ) );
	}

	$cache_type = sanitize_text_field( $_POST['cache_type'] ?? 'all' );

	switch ( $cache_type ) {
		case 'distance':
			$result = dbs_cleanup_cache();
			$message = __( 'Cache vzdáleností byla vyčištěna.', 'distance-shipping' );
			break;
		case 'geocoding':
			dbs_clear_geocoding_cache();
			$result = true;
			$message = __( 'Cache geokódování byla vyčištěna.', 'distance-shipping' );
			break;
		case 'all':
		default:
			dbs_cleanup_cache();
			dbs_clear_geocoding_cache();
			$result = true;
			$message = __( 'Všechny cache byly vyčištěny.', 'distance-shipping' );
			break;
	}

	if ( $result ) {
		wp_send_json_success( [ 'message' => $message ] );
	} else {
		wp_send_json_error( __( 'Nepodařilo se vyčistit cache.', 'distance-shipping' ) );
	}
}

/**
 * AJAX handler pro aktualizaci souřadnic obchodů.
 *
 * @return void
 */
function dbs_ajax_update_store_coordinates(): void {
	// Ověření nonce.
	if ( ! wp_verify_nonce( $_POST['nonce'], 'dbs_admin_nonce' ) ) {
		wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'distance-shipping' ) );
	}

	// Kontrola oprávnění.
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( __( 'Nemáte oprávnění k této akci.', 'distance-shipping' ) );
	}

	$store_id = (int) ( $_POST['store_id'] ?? 0 );

	if ( $store_id > 0 ) {
		// Aktualizace konkrétního obchodu.
		$store = dbs_get_store( $store_id );
		if ( ! $store ) {
			wp_send_json_error( __( 'Obchod nebyl nalezen.', 'distance-shipping' ) );
		}

		$coordinates = dbs_geocode_address( $store->address );
		if ( $coordinates ) {
			$result = dbs_update_store( $store_id, [
				'name'      => $store->name,
				'address'   => $store->address,
				'latitude'  => $coordinates['lat'],
				'longitude' => $coordinates['lng'],
				'is_active' => $store->is_active,
			] );

			if ( false !== $result ) {
				wp_send_json_success( [
					'latitude'  => $coordinates['lat'],
					'longitude' => $coordinates['lng'],
					'message'   => __( 'Souřadnice obchodu byly aktualizovány.', 'distance-shipping' ),
				] );
			} else {
				wp_send_json_error( __( 'Nepodařilo se aktualizovat souřadnice obchodu.', 'distance-shipping' ) );
			}
		} else {
			wp_send_json_error( __( 'Nepodařilo se geokódovat adresu obchodu.', 'distance-shipping' ) );
		}
	} else {
		// Aktualizace všech obchodů.
		$results = dbs_update_all_store_coordinates();
		
		if ( $results['updated'] > 0 || $results['failed'] === 0 ) {
			wp_send_json_success( [
				'updated' => $results['updated'],
				'failed'  => $results['failed'],
				'errors'  => $results['errors'],
				'message' => sprintf(
					__( 'Aktualizováno: %d obchodů, Selhalo: %d obchodů', 'distance-shipping' ),
					$results['updated'],
					$results['failed']
				),
			] );
		} else {
			wp_send_json_error( [
				'updated' => $results['updated'],
				'failed'  => $results['failed'],
				'errors'  => $results['errors'],
				'message' => __( 'Nepodařilo se aktualizovat souřadnice obchodů.', 'distance-shipping' ),
			] );
		}
	}
}

/**
 * AJAX handler pro validaci API klíče.
 *
 * @return void
 */
function dbs_ajax_validate_api_key(): void {
	// Ověření nonce.
	if ( ! wp_verify_nonce( $_POST['nonce'], 'dbs_admin_nonce' ) ) {
		wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'distance-shipping' ) );
	}

	// Kontrola oprávnění.
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( __( 'Nemáte oprávnění k této akci.', 'distance-shipping' ) );
	}

	$service = sanitize_text_field( $_POST['service'] ?? '' );
	$api_key = sanitize_text_field( $_POST['api_key'] ?? '' );

	if ( empty( $service ) || empty( $api_key ) ) {
		wp_send_json_error( __( 'Služba a API klíč jsou povinné.', 'distance-shipping' ) );
	}

	$is_valid = dbs_validate_api_key( $service, $api_key );

	if ( $is_valid ) {
		wp_send_json_success( [
			'message' => __( 'API klíč je platný.', 'distance-shipping' ),
		] );
	} else {
		wp_send_json_error( __( 'API klíč není platný nebo služba není dostupná.', 'distance-shipping' ) );
	}
}

/**
 * AJAX handler pro výpočet dopravních sazeb (frontend).
 *
 * @return void
 */
function dbs_ajax_calculate_shipping(): void {
	// Ověření nonce.
	if ( ! wp_verify_nonce( $_POST['nonce'], 'dbs_nonce' ) ) {
		wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'distance-shipping' ) );
	}

	$destination_address = sanitize_textarea_field( $_POST['destination'] ?? '' );
	$product_id = intval( $_POST['product_id'] ?? 0 ); // Nový parametr pro product detail page

	if ( empty( $destination_address ) ) {
		wp_send_json_error( __( 'Cílová adresa je povinná.', 'distance-shipping' ) );
	}

	// Najdeme nejbližší obchod.
	$nearest_store = dbs_find_nearest_store( $destination_address );
	if ( ! $nearest_store ) {
		wp_send_json_error( __( 'Nepodařilo se najít nejbližší obchod.', 'distance-shipping' ) );
	}

	// Vypočítáme vzdálenost s inteligentním vyhledáváním.
	$distance = dbs_calculate_distance( $nearest_store->address, $destination_address );
	if ( false === $distance ) {
		wp_send_json_error( __( 'Nepodařilo se vypočítat vzdálenost.', 'distance-shipping' ) );
	}

	// Vytvoříme simulovaný balíček pro testování.
	$mock_package = dbs_create_mock_package( $destination_address );

	// Získáme aplikovatelná pravidla.
	$applicable_rules = dbs_get_applicable_shipping_rules( $distance, $mock_package );

	$shipping_rates = [];
	foreach ( $applicable_rules as $rule ) {
		$rate = dbs_calculate_shipping_rate_from_rule( $rule, $distance, $mock_package );
		if ( $rate ) {
			$shipping_rates[] = [
				'id'    => $rate['id'],
				'label' => $rule->rule_name,
				'cost'  => wc_price( $rate['cost'] ),
				'raw_cost' => $rate['cost'],
				'distance' => dbs_format_distance( $distance ),
				'base_rate' => $rule->base_rate,
				'per_km_rate' => $rule->per_km_rate,
				'is_plugin_rule' => isset( $rule->is_plugin_rule ) ? $rule->is_plugin_rule : true,
				'priority' => $rule->priority,
				'calculation' => sprintf(
					__( 'Základní sazba: %s + %s/km × %s = %s', 'distance-shipping' ),
					wc_price( $rule->base_rate ),
					wc_price( $rule->per_km_rate ),
					dbs_format_distance( $distance ),
					wc_price( $rate['cost'] )
				),
			];
		}
	}

	// Pokud žádná pravidla neaplikují, přidáme záložní sazbu.
	if ( empty( $shipping_rates ) ) {
		$fallback_rate = dbs_get_fallback_shipping_rate();
		if ( $fallback_rate ) {
			$shipping_rates[] = [
				'id'    => $fallback_rate['id'],
				'label' => $fallback_rate['label'],
				'cost'  => wc_price( $fallback_rate['cost'] ),
				'raw_cost' => $fallback_rate['cost'],
				'distance' => dbs_format_distance( $distance ),
				'calculation' => __( 'Záložní sazba', 'distance-shipping' ),
			];
		}
	}

	// Získáme standardizovanou adresu z geokódování
	$geocoded_address = dbs_geocode_address( $destination_address );
	$standardized_address = null;
	$original_address = $destination_address;
	
	if ( $geocoded_address && isset( $geocoded_address['formatted_address'] ) ) {
		$standardized_address = $geocoded_address['formatted_address'];
	}

	// Získat informace o balíčku pro zobrazení
	$package_info = null;
	if ( function_exists( 'dbs_get_package_info' ) ) {
		// Pokud je specifikován product_id, použijeme ho pro získání údajů o produktu
		if ( $product_id > 0 ) {
			$package_info = dbs_get_package_info( $mock_package, $product_id );
		} else {
			$package_info = dbs_get_package_info( $mock_package );
		}
	}

	// Uložit informace do session pro pozdější použití
	if ( WC()->session ) {
		WC()->session->set( 'dbs_calculated_distance', $distance );
		WC()->session->set( 'dbs_nearest_store', $nearest_store );
		WC()->session->set( 'dbs_destination_address', $destination_address );
		WC()->session->set( 'dbs_standardized_address', $standardized_address );
		WC()->session->set( 'dbs_shipping_rates', $shipping_rates );
	}

	$response_data = [
		'rates'    => $shipping_rates,
		'distance' => dbs_format_distance( $distance ),
		'store'    => $nearest_store->name,
		'store_address' => $nearest_store->address,
		'message'  => __( 'Dopravní sazby byly úspěšně vypočítány.', 'distance-shipping' ),
		'total_rates' => count( $shipping_rates ),
		'selected_rate' => ! empty( $shipping_rates ) ? $shipping_rates[0] : null,
		'package_info' => $package_info,
		'product_id' => $product_id, // Přidáme product_id do odpovědi pro debug
	];

	// Přidáme standardizovanou adresu pro automatické vyplnění
	if ( $standardized_address && $standardized_address !== $original_address ) {
		$response_data['address_standardized'] = true;
		$response_data['original_address'] = $original_address;
		$response_data['standardized_address'] = $standardized_address;
		$response_data['standardization_message'] = sprintf(
			__( 'Adresa byla standardizována z "%s" na "%s"', 'distance-shipping' ),
			$original_address,
			$standardized_address
		);
	} else {
		// Použijeme původní adresu pokud standardizace není k dispozici
		$response_data['standardized_address'] = $original_address;
	}

	wp_send_json_success( $response_data );
}

/**
 * Aplikuje shipping sazbu přímo do WooCommerce cart.
 */
function dbs_ajax_apply_shipping_to_cart(): void {
	// Ověření nonce.
	if ( ! wp_verify_nonce( $_POST['nonce'], 'dbs_nonce' ) ) {
		wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'distance-shipping' ) );
	}

	$rate_id = sanitize_text_field( $_POST['rate_id'] ?? '' );
	$rate_cost = floatval( $_POST['rate_cost'] ?? 0 );
	$rate_label = sanitize_text_field( $_POST['rate_label'] ?? '' );

	if ( empty( $rate_id ) || $rate_cost <= 0 ) {
		wp_send_json_error( __( 'Neplatné údaje o shipping sazbě.', 'distance-shipping' ) );
	}

	// Kontrola, že WooCommerce je načtené
	if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
		wp_send_json_error( __( 'WooCommerce není načtené.', 'distance-shipping' ) );
	}

	try {
		// Nejprve odstranit předchozí shipping fees
		$fees = WC()->cart->get_fees();
		foreach ( $fees as $fee_key => $fee ) {
			if ( strpos( $fee->name, 'Distance Based Shipping' ) !== false || 
				 strpos( $fee->name, 'Standard Shipping' ) !== false ||
				 strpos( $fee->name, 'Doprava' ) !== false ||
				 strpos( $fee->name, $rate_label ) !== false ) {
				unset( WC()->cart->fees[ $fee_key ] );
			}
		}

		// Uložit shipping sazbu do session pro nativní WooCommerce shipping systém
		WC()->session->set( 'dbs_applied_shipping_rate', [
			'id' => $rate_id,
			'cost' => $rate_cost,
			'label' => $rate_label,
			'timestamp' => time()
		] );

		// Shipping sazba se aplikuje pouze přes nativní WooCommerce shipping systém

		// Uložit informace o vzdálenosti do session
		$distance_info = WC()->session->get( 'dbs_calculated_distance' );
		if ( $distance_info ) {
			WC()->session->set( 'dbs_distance_info', $distance_info );
		}

		// Uložit informace o obchodě do session
		$store_info = WC()->session->get( 'dbs_nearest_store' );
		if ( $store_info ) {
			WC()->session->set( 'dbs_store_info', $store_info );
		}

		// Aktualizovat cart
		WC()->cart->calculate_totals();

		// Získat aktualizované cart totals
		$cart_total = WC()->cart->get_total( 'raw' );
		$shipping_total = WC()->cart->get_shipping_total();
		$tax_total = WC()->cart->get_total_tax();

		wp_send_json_success( [
			'message' => __( 'Shipping sazba byla úspěšně aplikována do košíku.', 'distance-shipping' ),
			'rate_id' => $rate_id,
			'rate_cost' => $rate_cost,
			'rate_label' => $rate_label,
			'cart_total' => $cart_total,
			'shipping_total' => $shipping_total,
			'tax_total' => $tax_total,
			'formatted_total' => WC()->cart->get_cart_total(),
			'formatted_shipping' => WC()->cart->get_cart_shipping_total(),
		] );

	} catch ( Exception $e ) {
		wp_send_json_error( __( 'Chyba při aplikování shipping sazby: ', 'distance-shipping' ) . $e->getMessage() );
	}
}

/**
 * Odstraní shipping fee z WooCommerce cart.
 * Tato funkce je nyní zastaralá - shipping se aplikuje pouze přes nativní WooCommerce shipping systém.
 */
function dbs_ajax_remove_shipping_from_cart(): void {
	// Ověření nonce.
	if ( ! wp_verify_nonce( $_POST['nonce'], 'dbs_nonce' ) ) {
		wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'distance-shipping' ) );
	}

	// Kontrola, že WooCommerce je načtené
	if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
		wp_send_json_error( __( 'WooCommerce není načtené.', 'distance-shipping' ) );
	}

	try {
		// Vymazat shipping data ze session
		WC()->session->__unset( 'dbs_applied_shipping_rate' );
		WC()->session->__unset( 'dbs_distance_info' );

		// Aktualizovat cart - shipping se nyní aplikuje pouze přes nativní WooCommerce shipping systém
		WC()->cart->calculate_totals();

		wp_send_json_success( [
			'message' => __( 'Shipping data byla vymazána ze session.', 'distance-shipping' )
		] );

	} catch ( Exception $e ) {
		wp_send_json_error( __( 'Chyba při vymazávání shipping data: ', 'distance-shipping' ) . $e->getMessage() );
	}
}

/**
 * Vytvoří mock balíček pro testování.
 *
 * @param string $destination_address Cílová adresa.
 * @return array Mock balíček.
 */
function dbs_create_mock_package( string $destination_address ): array {
	// Parsování adresy.
	$address_parts = array_map( 'trim', explode( ',', $destination_address ) );
	
	// Získat skutečnou hodnotu košíku
	$cart_total = 0;
	if ( WC()->cart ) {
		$cart_total = WC()->cart->get_cart_contents_total();
	}
	
	// Vytvořit mock contents z aktuálního košíku
	$contents = [];
	if ( WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$contents[] = [
				'key' => $cart_item_key,
				'product_id' => $cart_item['product_id'],
				'variation_id' => $cart_item['variation_id'],
				'quantity' => $cart_item['quantity'],
				'data' => $cart_item['data'],
				'line_tax_data' => $cart_item['line_tax_data'],
				'line_subtotal' => $cart_item['line_subtotal'],
				'line_subtotal_tax' => $cart_item['line_subtotal_tax'],
				'line_total' => $cart_item['line_total'],
				'line_tax' => $cart_item['line_tax'],
			];
		}
	}
	
	return [
		'contents' => $contents,
		'contents_cost' => $cart_total,
		'applied_coupons' => WC()->cart ? WC()->cart->get_applied_coupons() : [],
		'user' => [
			'ID' => get_current_user_id(),
		],
		'destination' => [
			'country'   => '',
			'state'     => '',
			'postcode'  => '',
			'city'      => '',
			'address'   => $address_parts[0] ?? '',
			'address_2' => '',
		],
	];
}

/**
 * Validuje API klíč pro danou službu.
 *
 * @param string $service Název služby.
 * @param string $api_key API klíč.
 * @return bool True pokud je klíč platný.
 */
function dbs_validate_api_key( string $service, string $api_key ): bool {
	switch ( $service ) {
		case 'google':
			return dbs_validate_google_api_key( $api_key );
		case 'bing':
			return dbs_validate_bing_api_key( $api_key );
		default:
			return false;
	}
}

/**
 * Validuje Google Maps API klíč.
 *
 * @param string $api_key API klíč.
 * @return bool True pokud je klíč platný.
 */
function dbs_validate_google_api_key( string $api_key ): bool {
	$test_url = add_query_arg( [
		'address' => 'Prague, Czech Republic',
		'key'     => $api_key,
	], 'https://maps.googleapis.com/maps/api/geocode/json' );

	$response = wp_remote_get( $test_url, [
		'timeout' => 15,
		'headers' => [
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
		],
	] );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	return ! empty( $data['status'] ) && 'OK' === $data['status'];
}

/**
 * Validuje Bing Maps API klíč.
 *
 * @param string $api_key API klíč.
 * @return bool True pokud je klíč platný.
 */
function dbs_validate_bing_api_key( string $api_key ): bool {
	$test_url = sprintf(
		'https://dev.virtualearth.net/REST/v1/Locations?q=%s&key=%s',
		urlencode( 'Prague, Czech Republic' ),
		$api_key
	);

	$response = wp_remote_get( $test_url, [
		'timeout' => 15,
		'headers' => [
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
		],
	] );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	return ! empty( $data['statusCode'] ) && 200 === $data['statusCode'];
}

/**
 * Získá AJAX URL pro frontend použití.
 *
 * @return string AJAX URL.
 */
function dbs_get_ajax_url(): string {
	return admin_url( 'admin-ajax.php' );
}

/**
 * Vytvoří nonce pro AJAX požadavky.
 *
 * @param string $action Název akce.
 * @return string Nonce hodnota.
 */
function dbs_create_ajax_nonce( string $action = 'dbs_nonce' ): string {
	return wp_create_nonce( $action );
}

/**
 * Zaloguje AJAX chybu pro debugging.
 *
 * @param string $action Název AJAX akce.
 * @param string $error_message Chybová zpráva.
 * @param array  $context Dodatečný kontext.
 * @return void
 */
function dbs_log_ajax_error( string $action, string $error_message, array $context = [] ): void {
	if ( get_option( 'dbs_debug_mode', 0 ) ) {
		$log_data = [
			'action' => $action,
			'error'  => $error_message,
			'context' => $context,
			'timestamp' => current_time( 'mysql' ),
		];

		error_log( 'DBS AJAX Error: ' . wp_json_encode( $log_data ) );
	}
}

/**
 * AJAX handler pro test cart integrace.
 *
 * @return void
 */
function dbs_ajax_test_cart_integration(): void {
	// Ověření nonce.
	if ( ! wp_verify_nonce( $_POST['nonce'], 'dbs_admin_nonce' ) ) {
		wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'distance-shipping' ) );
	}

	// Kontrola, že WooCommerce je načtené
	if ( ! class_exists( 'WooCommerce' ) ) {
		wp_send_json_error( __( 'WooCommerce není načtené.', 'distance-shipping' ) );
	}

	// Kontrola, že hooks jsou registrovány
	$hooks_registered = has_action( 'woocommerce_after_cart_table', 'dbs_display_cart_shipping_calculator' );
	$cart_info_hook = has_action( 'woocommerce_cart_collaterals', 'dbs_display_cart_shipping_info' );

	if ( $hooks_registered && $cart_info_hook ) {
		wp_send_json_success( [
			'message' => __( 'Cart integration is working properly.', 'distance-shipping' ),
			'hooks_registered' => true,
			'cart_calculator_hook' => $hooks_registered,
			'cart_info_hook' => $cart_info_hook
		] );
	} else {
		wp_send_json_error( __( 'Cart hooks are not properly registered.', 'distance-shipping' ) );
	}
}
add_action( 'wp_ajax_dbs_test_cart_integration', 'dbs_ajax_test_cart_integration' );

/**
 * AJAX handler pro validaci adresy.
 *
 * @return void
 */
function dbs_ajax_validate_address(): void {
	// Ověření nonce.
	if ( ! wp_verify_nonce( $_POST['nonce'], 'dbs_nonce' ) ) {
		wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'distance-shipping' ) );
	}

	$address = sanitize_textarea_field( $_POST['address'] ?? '' );

	if ( empty( $address ) ) {
		wp_send_json_error( __( 'Adresa je povinná.', 'distance-shipping' ) );
	}

	// Základní validace adresy
	$validation_result = dbs_validate_address_format( $address );
	
	if ( $validation_result['is_valid'] ) {
		wp_send_json_success( [
			'message' => __( 'Adresa je validní.', 'distance-shipping' ),
			'address' => $address,
			'standardized' => $validation_result['standardized_address'] ?? $address
		] );
	} else {
		wp_send_json_error( $validation_result['message'] );
	}
}

/**
 * Validuje formát adresy.
 *
 * @param string $address Adresa k validaci.
 * @return array Výsledek validace.
 */
function dbs_validate_address_format( string $address ): array {
	// Základní kontroly
	if ( strlen( $address ) < 10 ) {
		return [
			'is_valid' => false,
			'message' => __( 'Adresa je příliš krátká. Zadejte úplnou adresu včetně města a PSČ.', 'distance-shipping' )
		];
	}

	// Kontrola, zda obsahuje město nebo PSČ
	if ( ! preg_match( '/(\d{3}\s?\d{2}|[A-Za-zěščřžýáíéůúňťď\s]+)/', $address ) ) {
		return [
			'is_valid' => false,
			'message' => __( 'Adresa musí obsahovat město nebo PSČ.', 'distance-shipping' )
		];
	}

	// Pokus o geokódování pro validaci
	$geocoded = dbs_geocode_address( $address );
	
	if ( $geocoded && isset( $geocoded['formatted_address'] ) ) {
		return [
			'is_valid' => true,
			'message' => __( 'Adresa je validní a byla standardizována.', 'distance-shipping' ),
			'standardized_address' => $geocoded['formatted_address']
		];
	}

	// Pokud geokódování selhalo, ale adresa vypadá validně
	if ( strlen( $address ) > 15 && strpos( $address, ',' ) !== false ) {
		return [
			'is_valid' => true,
			'message' => __( 'Adresa vypadá validně.', 'distance-shipping' )
		];
	}

	return [
		'is_valid' => false,
		'message' => __( 'Adresa se nepodařilo validovat. Zkontrolujte formát.', 'distance-shipping' )
	];
}

/**
 * AJAX handler pro invalidaci shipping cache.
 *
 * @return void
 */
function dbs_ajax_invalidate_shipping_cache(): void {
	// Ověření nonce.
	if ( ! wp_verify_nonce( $_POST['nonce'], 'dbs_nonce' ) ) {
		wp_send_json_error( __( 'Bezpečnostní kontrola selhala.', 'distance-shipping' ) );
	}

	// Kontrola, že WooCommerce je načtené
	if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
		wp_send_json_error( __( 'WooCommerce není načtené.', 'distance-shipping' ) );
	}

	$reason = sanitize_text_field( $_POST['reason'] ?? 'unknown' );
	$details = $_POST['details'] ?? [];

	// Invalidate all cache
	if ( WC()->cart ) {
		// Force shipping recalculation
		WC()->cart->calculate_shipping();
		
		// Clear all cache using the centralized function
		if ( function_exists( 'dbs_invalidate_all_cache' ) ) {
			dbs_invalidate_all_cache();
		}
		
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Shipping cache invalidated via AJAX - Reason: ' . $reason . ', Details: ' . wp_json_encode( $details ) );
		}
		
		wp_send_json_success( [
			'message' => __( 'Shipping cache byla invalidována.', 'distance-shipping' ),
			'reason' => $reason,
			'details' => $details
		] );
	} else {
		wp_send_json_error( __( 'Nepodařilo se invalidovat shipping cache.', 'distance-shipping' ) );
	}
}
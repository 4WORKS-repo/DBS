<?php
/**
 * Geokódovací funkce pro Distance Based Shipping plugin.
 *
 * Soubor: includes/functions/geocoding-functions.php
 *
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Geokódování adresy na souřadnice s inteligentním vyhledáváním.
 *
 * @param string $address Adresa k geokódování.
 * @return array|false Pole se souřadnicemi [lat, lng] nebo false při chybě.
 */
function dbs_geocode_address( string $address ) {
	// Kontrola prázdné adresy.
	if ( empty( trim( $address ) ) ) {
		return false;
	}

	// Kontrola cache pro geokódování.
	$cache_key = 'dbs_geocode_' . md5( $address );
	$cached_coords = get_transient( $cache_key );
	
	if ( false !== $cached_coords ) {
		return $cached_coords;
	}

	// Získání souřadnic podle zvolené mapové služby.
	$map_service = get_option( 'dbs_map_service', 'openstreetmap' );
	$coordinates = false;

	switch ( $map_service ) {
		case 'google':
			$coordinates = dbs_geocode_google_smart( $address );
			break;
		case 'bing':
			$coordinates = dbs_geocode_bing_smart( $address );
			break;
		case 'openstreetmap':
		default:
			$coordinates = dbs_geocode_openstreetmap_smart( $address );
			break;
	}

	// Uložení do cache na 7 dní.
	if ( $coordinates ) {
		set_transient( $cache_key, $coordinates, 7 * DAY_IN_SECONDS );
	}

	return $coordinates;
}

/**
 * Inteligentní geokódování pomocí Google Maps API.
 *
 * @param string $address Adresa k geokódování.
 * @return array|false Pole se souřadnicemi nebo false při chybě.
 */
function dbs_geocode_google_smart( string $address ) {
	$api_key = get_option( 'dbs_google_api_key' );
	
	if ( empty( $api_key ) ) {
		dbs_log_debug( 'Google Maps API klíč není nakonfigurován pro geokódování' );
		return dbs_geocode_openstreetmap_smart( $address );
	}

	$url = add_query_arg( [
		'address' => urlencode( $address ),
		'key'     => $api_key,
	], 'https://maps.googleapis.com/maps/api/geocode/json' );

	$response = wp_remote_get( $url, [
		'timeout' => 30,
		'headers' => [
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
		],
	] );

	if ( is_wp_error( $response ) ) {
		dbs_log_debug( 'Google Geocoding API požadavek selhal: ' . $response->get_error_message() );
		return dbs_geocode_openstreetmap_smart( $address );
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( empty( $data['results'] ) ) {
		dbs_log_debug( 'Google Geocoding API nevrátilo výsledky pro: ' . $address );
		return false;
	}

	// Najdeme nejlepší shodu s tolerancí 10km
	$best_match = dbs_find_best_address_match( $data['results'], $address, 'google' );
	
	if ( ! $best_match ) {
		dbs_log_debug( 'Google Geocoding API: Žádná vhodná shoda v rámci 10km tolerance pro: ' . $address );
		return false;
	}

	$location = $best_match['geometry']['location'];
	
	return [
		'lat' => (float) $location['lat'],
		'lng' => (float) $location['lng'],
		'formatted_address' => $best_match['formatted_address'],
		'original_address' => $address,
	];
}

/**
 * Inteligentní geokódování pomocí Bing Maps API.
 *
 * @param string $address Adresa k geokódování.
 * @return array|false Pole se souřadnicemi nebo false při chybě.
 */
function dbs_geocode_bing_smart( string $address ) {
	$api_key = get_option( 'dbs_bing_api_key' );
	
	if ( empty( $api_key ) ) {
		dbs_log_debug( 'Bing Maps API klíč není nakonfigurován pro geokódování' );
		return dbs_geocode_openstreetmap_smart( $address );
	}

	$url = sprintf(
		'https://dev.virtualearth.net/REST/v1/Locations?q=%s&key=%s',
		urlencode( $address ),
		$api_key
	);

	$response = wp_remote_get( $url, [
		'timeout' => 30,
		'headers' => [
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
		],
	] );

	if ( is_wp_error( $response ) ) {
		dbs_log_debug( 'Bing Geocoding API požadavek selhal: ' . $response->get_error_message() );
		return dbs_geocode_openstreetmap_smart( $address );
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( empty( $data['resourceSets'][0]['resources'] ) ) {
		dbs_log_debug( 'Bing Geocoding API nevrátilo výsledky pro: ' . $address );
		return false;
	}

	// Najdeme nejlepší shodu s tolerancí 10km
	$best_match = dbs_find_best_address_match( $data['resourceSets'][0]['resources'], $address, 'bing' );
	
	if ( ! $best_match ) {
		dbs_log_debug( 'Bing Geocoding API: Žádná vhodná shoda v rámci 10km tolerance pro: ' . $address );
		return false;
	}

	$coordinates = $best_match['point']['coordinates'];
	
	return [
		'lat' => (float) $coordinates[0],
		'lng' => (float) $coordinates[1],
		'formatted_address' => $best_match['address']['formattedAddress'],
		'original_address' => $address,
	];
}

/**
 * Inteligentní geokódování pomocí OpenStreetMap Nominatim API.
 *
 * @param string $address Adresa k geokódování.
 * @return array|false Pole se souřadnicemi nebo false při chybě.
 */
function dbs_geocode_openstreetmap_smart( string $address ) {
	$url = add_query_arg( [
		'q'      => urlencode( $address ),
		'format' => 'json',
		'limit'  => 10, // Získáme více výsledků pro lepší výběr
	], 'https://nominatim.openstreetmap.org/search' );

	$response = wp_remote_get( $url, [
		'timeout' => 30,
		'headers' => [
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
		],
	] );

	if ( is_wp_error( $response ) ) {
		dbs_log_debug( 'OpenStreetMap Nominatim API požadavek selhal: ' . $response->get_error_message() );
		return false;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( empty( $data ) ) {
		dbs_log_debug( 'OpenStreetMap Nominatim API nevrátilo výsledky pro: ' . $address );
		return false;
	}

	// Najdeme nejlepší shodu s tolerancí 10km
	$best_match = dbs_find_best_address_match( $data, $address, 'openstreetmap' );
	
	if ( ! $best_match ) {
		dbs_log_debug( 'OpenStreetMap Nominatim API: Žádná vhodná shoda v rámci 10km tolerance pro: ' . $address );
		return false;
	}

	return [
		'lat' => (float) $best_match['lat'],
		'lng' => (float) $best_match['lon'],
		'formatted_address' => $best_match['display_name'],
		'original_address' => $address,
	];
}

/**
 * Najde nejlepší shodu adresy v rámci tolerance.
 *
 * @param array $results Výsledky z mapové služby.
 * @param string $original_address Původní adresa.
 * @param string $service Název mapové služby.
 * @return array|false Nejlepší shoda nebo false.
 */
function dbs_find_best_address_match( array $results, string $original_address, string $service ): ?array {
	$tolerance_km = 10; // 10km tolerance
	$best_match = null;
	$shortest_distance = PHP_FLOAT_MAX;

	foreach ( $results as $result ) {
		$formatted_address = dbs_extract_formatted_address( $result, $service );
		$coordinates = dbs_extract_coordinates( $result, $service );
		
		if ( ! $coordinates ) {
			continue;
		}

		// Vypočítáme vzdálenost mezi původní a nalezenou adresou
		$distance = dbs_calculate_address_similarity( $original_address, $formatted_address, $coordinates );
		
		// Kontrola tolerance
		if ( $distance <= $tolerance_km ) {
			if ( $distance < $shortest_distance ) {
				$shortest_distance = $distance;
				$best_match = $result;
				$best_match['calculated_distance'] = $distance;
				$best_match['formatted_address'] = $formatted_address;
			}
		}
	}

	return $best_match;
}

/**
 * Extrahuje formátovanou adresu z výsledku mapové služby.
 *
 * @param array $result Výsledek z mapové služby.
 * @param string $service Název mapové služby.
 * @return string Formátovaná adresa.
 */
function dbs_extract_formatted_address( array $result, string $service ): string {
	switch ( $service ) {
		case 'google':
			return $result['formatted_address'] ?? '';
		case 'bing':
			return $result['address']['formattedAddress'] ?? '';
		case 'openstreetmap':
			return $result['display_name'] ?? '';
		default:
			return '';
	}
}

/**
 * Extrahuje souřadnice z výsledku mapové služby.
 *
 * @param array $result Výsledek z mapové služby.
 * @param string $service Název mapové služby.
 * @return array|false Souřadnice nebo false.
 */
function dbs_extract_coordinates( array $result, string $service ): ?array {
	switch ( $service ) {
		case 'google':
			if ( isset( $result['geometry']['location']['lat'], $result['geometry']['location']['lng'] ) ) {
				return [
					'lat' => (float) $result['geometry']['location']['lat'],
					'lng' => (float) $result['geometry']['location']['lng'],
				];
			}
			break;
		case 'bing':
			if ( isset( $result['point']['coordinates'][0], $result['point']['coordinates'][1] ) ) {
				return [
					'lat' => (float) $result['point']['coordinates'][0],
					'lng' => (float) $result['point']['coordinates'][1],
				];
			}
			break;
		case 'openstreetmap':
			if ( isset( $result['lat'], $result['lon'] ) ) {
				return [
					'lat' => (float) $result['lat'],
					'lng' => (float) $result['lon'],
				];
			}
			break;
	}
	
	return null;
}

/**
 * Vypočítá podobnost mezi dvěma adresami.
 *
 * @param string $address1 První adresa.
 * @param string $address2 Druhá adresa.
 * @param array $coordinates Souřadnice druhé adresy.
 * @return float Vzdálenost v kilometrech.
 */
function dbs_calculate_address_similarity( string $address1, string $address2, array $coordinates ): float {
	// Nejprve zkusíme geokódovat první adresu pro porovnání
	$address1_coords = dbs_geocode_address_simple( $address1 );
	
	if ( ! $address1_coords ) {
		// Pokud se nepodaří geokódovat první adresu, použijeme textovou podobnost
		return dbs_calculate_text_similarity( $address1, $address2 );
	}

	// Vypočítáme vzdálenost mezi souřadnicemi
	return dbs_calculate_distance_haversine( $address1_coords, $coordinates );
}

/**
 * Jednoduché geokódování bez cache a tolerance.
 *
 * @param string $address Adresa k geokódování.
 * @return array|false Souřadnice nebo false.
 */
function dbs_geocode_address_simple( string $address ) {
	$map_service = get_option( 'dbs_map_service', 'openstreetmap' );
	
	switch ( $map_service ) {
		case 'google':
			return dbs_geocode_google_simple( $address );
		case 'bing':
			return dbs_geocode_bing_simple( $address );
		case 'openstreetmap':
		default:
			return dbs_geocode_openstreetmap_simple( $address );
	}
}

/**
 * Jednoduché geokódování pomocí Google Maps API.
 *
 * @param string $address Adresa k geokódování.
 * @return array|false Souřadnice nebo false.
 */
function dbs_geocode_google_simple( string $address ) {
	$api_key = get_option( 'dbs_google_api_key' );
	
	if ( empty( $api_key ) ) {
		return false;
	}

	$url = add_query_arg( [
		'address' => urlencode( $address ),
		'key'     => $api_key,
	], 'https://maps.googleapis.com/maps/api/geocode/json' );

	$response = wp_remote_get( $url, [
		'timeout' => 10,
		'headers' => [
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
		],
	] );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( empty( $data['results'][0]['geometry']['location'] ) ) {
		return false;
	}

	$location = $data['results'][0]['geometry']['location'];
	
	return [
		'lat' => (float) $location['lat'],
		'lng' => (float) $location['lng'],
	];
}

/**
 * Jednoduché geokódování pomocí Bing Maps API.
 *
 * @param string $address Adresa k geokódování.
 * @return array|false Souřadnice nebo false.
 */
function dbs_geocode_bing_simple( string $address ) {
	$api_key = get_option( 'dbs_bing_api_key' );
	
	if ( empty( $api_key ) ) {
		return false;
	}

	$url = sprintf(
		'https://dev.virtualearth.net/REST/v1/Locations?q=%s&key=%s',
		urlencode( $address ),
		$api_key
	);

	$response = wp_remote_get( $url, [
		'timeout' => 10,
		'headers' => [
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
		],
	] );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( empty( $data['resourceSets'][0]['resources'][0]['point']['coordinates'] ) ) {
		return false;
	}

	$coordinates = $data['resourceSets'][0]['resources'][0]['point']['coordinates'];
	
	return [
		'lat' => (float) $coordinates[0],
		'lng' => (float) $coordinates[1],
	];
}

/**
 * Jednoduché geokódování pomocí OpenStreetMap Nominatim API.
 *
 * @param string $address Adresa k geokódování.
 * @return array|false Souřadnice nebo false.
 */
function dbs_geocode_openstreetmap_simple( string $address ) {
	$url = add_query_arg( [
		'q'      => urlencode( $address ),
		'format' => 'json',
		'limit'  => 1,
	], 'https://nominatim.openstreetmap.org/search' );

	$response = wp_remote_get( $url, [
		'timeout' => 10,
		'headers' => [
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
		],
	] );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( empty( $data[0]['lat'] ) || empty( $data[0]['lon'] ) ) {
		return false;
	}

	return [
		'lat' => (float) $data[0]['lat'],
		'lng' => (float) $data[0]['lon'],
	];
}

/**
 * Vypočítá textovou podobnost mezi dvěma adresami.
 *
 * @param string $address1 První adresa.
 * @param string $address2 Druhá adresa.
 * @return float Hodnota podobnosti (0-1).
 */
function dbs_calculate_text_similarity( string $address1, string $address2 ): float {
	// Normalizace adres pro porovnání
	$normalized1 = dbs_normalize_address( $address1 );
	$normalized2 = dbs_normalize_address( $address2 );
	
	// Použijeme Levenshtein vzdálenost pro výpočet podobnosti
	$levenshtein = levenshtein( $normalized1, $normalized2 );
	$max_length = max( strlen( $normalized1 ), strlen( $normalized2 ) );
	
	if ( $max_length === 0 ) {
		return 0;
	}
	
	return 1 - ( $levenshtein / $max_length );
}

/**
 * Normalizuje adresu pro porovnání.
 *
 * @param string $address Adresa k normalizaci.
 * @return string Normalizovaná adresa.
 */
function dbs_normalize_address( string $address ): string {
	// Převedeme na malá písmena
	$normalized = mb_strtolower( $address, 'UTF-8' );
	
	// Odstraníme diakritiku
	$normalized = remove_accents( $normalized );
	
	// Odstraníme čárky, tečky a další interpunkci
	$normalized = preg_replace( '/[^\w\s]/', ' ', $normalized );
	
	// Odstraníme nadbytečné mezery
	$normalized = preg_replace( '/\s+/', ' ', $normalized );
	
	// Odstraníme mezery na začátku a konci
	return trim( $normalized );
}

/**
 * Převede souřadnice na adresu (reverzní geokódování).
 *
 * @param float $latitude Zeměpisná šířka.
 * @param float $longitude Zeměpisná délka.
 * @return string|false Adresa nebo false při chybě.
 */
function dbs_reverse_geocode( float $latitude, float $longitude ) {
	// Ověření platnosti souřadnic.
	if ( ! dbs_validate_coordinates( [ 'lat' => $latitude, 'lng' => $longitude ] ) ) {
		return false;
	}

	// Kontrola cache pro reverzní geokódování.
	$cache_key = 'dbs_reverse_geocode_' . md5( $latitude . ',' . $longitude );
	$cached_address = get_transient( $cache_key );
	
	if ( false !== $cached_address ) {
		return $cached_address;
	}

	// Reverzní geokódování podle zvolené mapové služby.
	$map_service = get_option( 'dbs_map_service', 'openstreetmap' );
	$address = false;

	switch ( $map_service ) {
		case 'google':
			$address = dbs_reverse_geocode_google( $latitude, $longitude );
			break;
		case 'bing':
			$address = dbs_reverse_geocode_bing( $latitude, $longitude );
			break;
		case 'openstreetmap':
		default:
			$address = dbs_reverse_geocode_openstreetmap( $latitude, $longitude );
			break;
	}

	// Uložení do cache na 7 dní.
	if ( $address ) {
		set_transient( $cache_key, $address, 7 * DAY_IN_SECONDS );
	}

	return $address;
}

/**
 * Reverzní geokódování pomocí Google Maps API.
 *
 * @param float $latitude Zeměpisná šířka.
 * @param float $longitude Zeměpisná délka.
 * @return string|false Adresa nebo false při chybě.
 */
function dbs_reverse_geocode_google( float $latitude, float $longitude ) {
	$api_key = get_option( 'dbs_google_api_key' );
	
	if ( empty( $api_key ) ) {
		return dbs_reverse_geocode_openstreetmap( $latitude, $longitude );
	}

	$url = add_query_arg( [
		'latlng' => $latitude . ',' . $longitude,
		'key'    => $api_key,
	], 'https://maps.googleapis.com/maps/api/geocode/json' );

	$response = wp_remote_get( $url, [
		'timeout' => 30,
		'headers' => [
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
		],
	] );

	if ( is_wp_error( $response ) ) {
		return dbs_reverse_geocode_openstreetmap( $latitude, $longitude );
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( empty( $data['results'][0]['formatted_address'] ) ) {
		return dbs_reverse_geocode_openstreetmap( $latitude, $longitude );
	}

	return sanitize_text_field( $data['results'][0]['formatted_address'] );
}

/**
 * Reverzní geokódování pomocí Bing Maps API.
 *
 * @param float $latitude Zeměpisná šířka.
 * @param float $longitude Zeměpisná délka.
 * @return string|false Adresa nebo false při chybě.
 */
function dbs_reverse_geocode_bing( float $latitude, float $longitude ) {
	$api_key = get_option( 'dbs_bing_api_key' );
	
	if ( empty( $api_key ) ) {
		return dbs_reverse_geocode_openstreetmap( $latitude, $longitude );
	}

	$url = sprintf(
		'https://dev.virtualearth.net/REST/v1/Locations/%s,%s?key=%s',
		$latitude,
		$longitude,
		$api_key
	);

	$response = wp_remote_get( $url, [
		'timeout' => 30,
		'headers' => [
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
		],
	] );

	if ( is_wp_error( $response ) ) {
		return dbs_reverse_geocode_openstreetmap( $latitude, $longitude );
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( empty( $data['resourceSets'][0]['resources'][0]['name'] ) ) {
		return dbs_reverse_geocode_openstreetmap( $latitude, $longitude );
	}

	return sanitize_text_field( $data['resourceSets'][0]['resources'][0]['name'] );
}

/**
 * Reverzní geokódování pomocí OpenStreetMap Nominatim API.
 *
 * @param float $latitude Zeměpisná šířka.
 * @param float $longitude Zeměpisná délka.
 * @return string|false Adresa nebo false při chybě.
 */
function dbs_reverse_geocode_openstreetmap( float $latitude, float $longitude ) {
	$url = add_query_arg( [
		'lat'    => $latitude,
		'lon'    => $longitude,
		'format' => 'json',
	], 'https://nominatim.openstreetmap.org/reverse' );

	$response = wp_remote_get( $url, [
		'timeout' => 30,
		'headers' => [
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
		],
	] );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( empty( $data['display_name'] ) ) {
		return false;
	}

	return sanitize_text_field( $data['display_name'] );
}

/**
 * Vyčistí cache geokódování.
 *
 * @return void
 */
function dbs_clear_geocoding_cache(): void {
	global $wpdb;

	// Vymazání všech transientů souvisejících s geokódováním.
	$wpdb->query(
		"DELETE FROM {$wpdb->options} 
		 WHERE option_name LIKE '_transient_dbs_geocode_%' 
		 OR option_name LIKE '_transient_timeout_dbs_geocode_%'
		 OR option_name LIKE '_transient_dbs_reverse_geocode_%'
		 OR option_name LIKE '_transient_timeout_dbs_reverse_geocode_%'"
	);
}

/**
 * Aktualizuje souřadnice pro všechny obchody.
 *
 * @return array Výsledky aktualizace.
 */
function dbs_update_all_store_coordinates(): array {
	$stores = dbs_get_stores( false ); // Získání všech obchodů včetně neaktivních
	$results = [
		'updated' => 0,
		'failed'  => 0,
		'errors'  => [],
	];

	foreach ( $stores as $store ) {
		$coordinates = dbs_geocode_address( $store->address );
		
		if ( $coordinates ) {
			$update_result = dbs_update_store( $store->id, [
				'name'      => $store->name,
				'address'   => $store->address,
				'latitude'  => $coordinates['lat'],
				'longitude' => $coordinates['lng'],
				'is_active' => $store->is_active,
			] );
			
			if ( false !== $update_result ) {
				$results['updated']++;
			} else {
				$results['failed']++;
				$results['errors'][] = sprintf( 'Selhala aktualizace obchodu ID %d', $store->id );
			}
		} else {
			$results['failed']++;
			$results['errors'][] = sprintf( 'Selhalo geokódování adresy pro obchod ID %d: %s', $store->id, $store->address );
		}
	}

	return $results;
}
<?php
/**
 * Funkce pro výpočet vzdálenosti pro Distance Based Shipping plugin.
 *
 * Soubor: includes/functions/distance-functions.php
 *
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vypočítá vzdálenost mezi dvěma adresami.
 *
 * @param string $origin Výchozí adresa.
 * @param string $destination Cílová adresa.
 * @return float|false Vzdálenost v nakonfigurovaných jednotkách nebo false při chybě.
 */
function dbs_calculate_distance( string $origin, string $destination ) {
	// Nejprve zkontrolujeme cache.
	$cache_enabled = (bool) get_option( 'dbs_enable_caching', true );
	
	if ( $cache_enabled ) {
		$cached_distance = dbs_get_distance_from_cache( $origin, $destination );
		if ( $cached_distance !== null ) {
			return $cached_distance;
		}
	}

	// Získáme souřadnice pro obě adresy s inteligentním vyhledáváním.
	$origin_coords = dbs_geocode_address( $origin );
	$destination_coords = dbs_geocode_address( $destination );

	if ( ! $origin_coords || ! $destination_coords ) {
		dbs_log_debug( 'Selhalo geokódování adres: Výchozí: ' . $origin . ', Cílová: ' . $destination );
		return false;
	}

	// Uložíme informace o použitých adresách pro zobrazení uživateli
	if ( WC()->session ) {
		if ( isset( $origin_coords['formatted_address'] ) ) {
			WC()->session->set( 'dbs_origin_address_used', $origin_coords['formatted_address'] );
			WC()->session->set( 'dbs_origin_address_original', $origin_coords['original_address'] );
		}
		
		if ( isset( $destination_coords['formatted_address'] ) ) {
			WC()->session->set( 'dbs_destination_address_used', $destination_coords['formatted_address'] );
			WC()->session->set( 'dbs_destination_address_original', $destination_coords['original_address'] );
		}
	}

	// Vypočítáme vzdálenost pomocí vybrané mapové služby.
	$map_service = get_option( 'dbs_map_service', 'openstreetmap' );
	$distance = false;

	switch ( $map_service ) {
		case 'google':
			$distance = dbs_calculate_distance_google( $origin_coords, $destination_coords );
			break;
		case 'bing':
			$distance = dbs_calculate_distance_bing( $origin_coords, $destination_coords );
			break;
		case 'openstreetmap':
		default:
			$distance = dbs_calculate_distance_haversine( $origin_coords, $destination_coords );
			break;
	}

	// Uložíme výsledek do cache.
	if ( $cache_enabled && false !== $distance ) {
		dbs_cache_distance_result( $origin, $destination, $distance, null, $map_service );
	}

	return $distance;
}

/**
 * Získá vzdálenost z cache.
 *
 * @param string $origin Výchozí adresa.
 * @param string $destination Cílová adresa.
 * @return float|null Uložená vzdálenost nebo null pokud není nalezena.
 */
function dbs_get_distance_from_cache( string $origin, string $destination ): ?float {
	$origin_hash = dbs_generate_location_hash( $origin );
	$destination_hash = dbs_generate_location_hash( $destination );

	$cached = dbs_get_cached_distance( $origin_hash, $destination_hash );
	
	return $cached ? (float) $cached->distance : null;
}

/**
 * Uloží výsledek vzdálenosti do cache.
 *
 * @param string      $origin Výchozí adresa.
 * @param string      $destination Cílová adresa.
 * @param float       $distance Hodnota vzdálenosti.
 * @param int|null    $duration Doba trvání v sekundách.
 * @param string|null $service Použitá mapová služba.
 * @return void
 */
function dbs_cache_distance_result( string $origin, string $destination, float $distance, ?int $duration = null, ?string $service = null ): void {
	$origin_hash = dbs_generate_location_hash( $origin );
	$destination_hash = dbs_generate_location_hash( $destination );

	dbs_cache_distance( $origin_hash, $destination_hash, $distance, $duration, $service );
}

/**
 * Vypočítá vzdálenost pomocí Google Maps API.
 *
 * @param array $origin_coords Souřadnice výchozího bodu [lat, lng].
 * @param array $destination_coords Souřadnice cílového bodu [lat, lng].
 * @return float|false Vzdálenost v kilometrech nebo false při chybě.
 */
function dbs_calculate_distance_google( array $origin_coords, array $destination_coords ) {
	$api_key = get_option( 'dbs_google_api_key' );
	
	if ( empty( $api_key ) ) {
		dbs_log_debug( 'Google Maps API klíč není nakonfigurován' );
		return dbs_calculate_distance_haversine( $origin_coords, $destination_coords );
	}

	$origin = $origin_coords['lat'] . ',' . $origin_coords['lng'];
	$destination = $destination_coords['lat'] . ',' . $destination_coords['lng'];

	$url = add_query_arg( [
		'origins'      => $origin,
		'destinations' => $destination,
		'units'        => 'metric',
		'key'          => $api_key,
	], 'https://maps.googleapis.com/maps/api/distancematrix/json' );

	$response = wp_remote_get( $url, [
		'timeout' => 30,
		'headers' => [
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
		],
	] );

	if ( is_wp_error( $response ) ) {
		dbs_log_debug( 'Google Maps API požadavek selhal: ' . $response->get_error_message() );
		return dbs_calculate_distance_haversine( $origin_coords, $destination_coords );
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( empty( $data['rows'][0]['elements'][0]['distance']['value'] ) ) {
		dbs_log_debug( 'Google Maps API vrátilo neplatnou odpověď: ' . $body );
		return dbs_calculate_distance_haversine( $origin_coords, $destination_coords );
	}

	$distance_meters = $data['rows'][0]['elements'][0]['distance']['value'];
	
	return $distance_meters / 1000; // Převod na kilometry
}

/**
 * Vypočítá vzdálenost pomocí Bing Maps API.
 *
 * @param array $origin_coords Souřadnice výchozího bodu [lat, lng].
 * @param array $destination_coords Souřadnice cílového bodu [lat, lng].
 * @return float|false Vzdálenost v kilometrech nebo false při chybě.
 */
function dbs_calculate_distance_bing( array $origin_coords, array $destination_coords ) {
	$api_key = get_option( 'dbs_bing_api_key' );
	
	if ( empty( $api_key ) ) {
		dbs_log_debug( 'Bing Maps API klíč není nakonfigurován' );
		return dbs_calculate_distance_haversine( $origin_coords, $destination_coords );
	}

	$url = sprintf(
		'https://dev.virtualearth.net/REST/v1/Routes/DistanceMatrix?origins=%s,%s&destinations=%s,%s&travelMode=driving&distanceUnit=km&key=%s',
		$origin_coords['lat'],
		$origin_coords['lng'],
		$destination_coords['lat'],
		$destination_coords['lng'],
		$api_key
	);

	$response = wp_remote_get( $url, [
		'timeout' => 30,
		'headers' => [
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
		],
	] );

	if ( is_wp_error( $response ) ) {
		dbs_log_debug( 'Bing Maps API požadavek selhal: ' . $response->get_error_message() );
		return dbs_calculate_distance_haversine( $origin_coords, $destination_coords );
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( empty( $data['resourceSets'][0]['resources'][0]['results'][0]['travelDistance'] ) ) {
		dbs_log_debug( 'Bing Maps API vrátilo neplatnou odpověď: ' . $body );
		return dbs_calculate_distance_haversine( $origin_coords, $destination_coords );
	}

	return (float) $data['resourceSets'][0]['resources'][0]['results'][0]['travelDistance'];
}

/**
 * Vypočítá vzdálenost pomocí Haversine vzorce (pro OpenStreetMap nebo jako záložní).
 *
 * @param array $origin_coords Souřadnice výchozího bodu [lat, lng].
 * @param array $destination_coords Souřadnice cílového bodu [lat, lng].
 * @return float Vzdálenost v kilometrech.
 */
function dbs_calculate_distance_haversine( array $origin_coords, array $destination_coords ): float {
	$earth_radius_km = 6371;

	$lat1 = deg2rad( $origin_coords['lat'] );
	$lon1 = deg2rad( $origin_coords['lng'] );
	$lat2 = deg2rad( $destination_coords['lat'] );
	$lon2 = deg2rad( $destination_coords['lng'] );

	$dlat = $lat2 - $lat1;
	$dlon = $lon2 - $lon1;

	$a = sin( $dlat / 2 ) * sin( $dlat / 2 ) + cos( $lat1 ) * cos( $lat2 ) * sin( $dlon / 2 ) * sin( $dlon / 2 );
	$c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );

	return $earth_radius_km * $c;
}

/**
 * Zaloguje debug zprávu pokud je debug režim aktivní.
 *
 * @param string $message Zpráva k zalogování.
 * @return void
 */
function dbs_log_debug( string $message ): void {
	if ( get_option( 'dbs_debug_mode', 0 ) ) {
		error_log( '[Distance Based Shipping] ' . $message );
	}
}

/**
 * Převede jednotky vzdálenosti.
 *
 * @param float  $distance Vzdálenost k převodu.
 * @param string $from_unit Výchozí jednotka.
 * @param string $to_unit Cílová jednotka.
 * @return float Převedená vzdálenost.
 */
function dbs_convert_distance_units( float $distance, string $from_unit, string $to_unit ): float {
	// Always return kilometers since we only use km
	return $distance;
}

/**
 * Získá formátovanou vzdálenost s jednotkami.
 *
 * @param float $distance Vzdálenost.
 * @param string|null $unit Jednotka (km/mi) nebo null pro automatické určení.
 * @return string Formátovaná vzdálenost.
 */
function dbs_format_distance( float $distance, ?string $unit = null ): string {
	// Vždy používáme kilometry
	return sprintf( '%.1f km', $distance );
}

/**
 * Ověří platnost souřadnic.
 *
 * @param array $coords Souřadnice k ověření.
 * @return bool True pokud jsou souřadnice platné.
 */
function dbs_validate_coordinates( array $coords ): bool {
	if ( ! isset( $coords['lat'], $coords['lng'] ) ) {
		return false;
	}

	$lat = (float) $coords['lat'];
	$lng = (float) $coords['lng'];

	return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
}
<?php
/**
 * Databázové funkce pro Distance Based Shipping plugin.
 *
 * Soubor: includes/functions/database-functions.php
 *
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Získá názvy tabulek s prefixem.
 *
 * @return array Názvy tabulek.
 */
function dbs_get_table_names(): array {
	global $wpdb;

	return [
		'stores' => $wpdb->prefix . 'dbs_stores',
		'rules'  => $wpdb->prefix . 'dbs_shipping_rules',
		'cache'  => $wpdb->prefix . 'dbs_distance_cache',
	];
}

/**
 * Vytvoří databázové tabulky pro plugin.
 *
 * @return void
 */
function dbs_create_database_tables(): void {
	global $wpdb;

	$tables = dbs_get_table_names();
	$charset_collate = $wpdb->get_charset_collate();

	$sql_queries = [
		dbs_get_stores_table_sql( $tables['stores'], $charset_collate ),
		dbs_get_rules_table_sql( $tables['rules'], $charset_collate ),
		dbs_get_cache_table_sql( $tables['cache'], $charset_collate ),
	];

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	foreach ( $sql_queries as $sql ) {
		dbDelta( $sql );
	}
}

/**
 * Migruje tabulku pravidel pro přidání nových polí.
 *
 * @return void
 */
function dbs_migrate_rules_table(): void {
	global $wpdb;

	$tables = dbs_get_table_names();
	$table_name = $tables['rules'];

	// Kontrola, zda tabulka existuje
	$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
	if ( ! $table_exists ) {
		return;
	}

	// Kontrola, zda už existují nová pole
	$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name}" );
	$column_names = array_column( $columns, 'Field' );

	$new_columns = [
		'weight_min' => "ALTER TABLE {$table_name} ADD COLUMN weight_min decimal(10,3) DEFAULT 0",
		'weight_max' => "ALTER TABLE {$table_name} ADD COLUMN weight_max decimal(10,3) DEFAULT 0",
		'weight_operator' => "ALTER TABLE {$table_name} ADD COLUMN weight_operator enum('AND', 'OR') DEFAULT 'AND'",
		'length_min' => "ALTER TABLE {$table_name} ADD COLUMN length_min decimal(10,2) DEFAULT 0",
		'length_max' => "ALTER TABLE {$table_name} ADD COLUMN length_max decimal(10,2) DEFAULT 0",
		'width_min' => "ALTER TABLE {$table_name} ADD COLUMN width_min decimal(10,2) DEFAULT 0",
		'width_max' => "ALTER TABLE {$table_name} ADD COLUMN width_max decimal(10,2) DEFAULT 0",
		'height_min' => "ALTER TABLE {$table_name} ADD COLUMN height_min decimal(10,2) DEFAULT 0",
		'height_max' => "ALTER TABLE {$table_name} ADD COLUMN height_max decimal(10,2) DEFAULT 0",
		'dimensions_operator' => "ALTER TABLE {$table_name} ADD COLUMN dimensions_operator enum('AND', 'OR') DEFAULT 'AND'",
	];

	foreach ( $new_columns as $column_name => $sql ) {
		if ( ! in_array( $column_name, $column_names, true ) ) {
			$wpdb->query( $sql );
		}
	}
}

/**
 * SQL pro tabulku obchodů.
 *
 * @param string $table_name Název tabulky.
 * @param string $charset_collate Charset collation.
 * @return string SQL dotaz.
 */
function dbs_get_stores_table_sql( string $table_name, string $charset_collate ): string {
	return "CREATE TABLE {$table_name} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL,
		address text NOT NULL,
		latitude decimal(10,8) DEFAULT NULL,
		longitude decimal(11,8) DEFAULT NULL,
		is_active tinyint(1) DEFAULT 1,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_active (is_active),
		KEY idx_coordinates (latitude, longitude)
	) {$charset_collate};";
}

/**
 * Získá SQL pro vytvoření tabulky pravidel.
 *
 * @param string $table_name Název tabulky.
 * @param string $charset_collate Charset a collation.
 * @return string SQL pro vytvoření tabulky.
 */
function dbs_get_rules_table_sql( string $table_name, string $charset_collate ): string {
	return "CREATE TABLE $table_name (
		id int(11) NOT NULL AUTO_INCREMENT,
		rule_name varchar(255) NOT NULL,
		distance_from decimal(10,2) DEFAULT 0,
		distance_to decimal(10,2) DEFAULT 0,
		base_rate decimal(10,2) DEFAULT 0,
		per_km_rate decimal(10,2) DEFAULT 0,
		min_order_amount decimal(10,2) DEFAULT 0,
		max_order_amount decimal(10,2) DEFAULT 0,
		product_categories text,
		shipping_classes text,
		priority int(11) DEFAULT 0,
		is_active tinyint(1) DEFAULT 1,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) $charset_collate;";
}

/**
 * Získá SQL pro vytvoření rozšířené tabulky pravidel s hmotností a rozměry.
 *
 * @param string $table_name Název tabulky.
 * @param string $charset_collate Charset a collation.
 * @return string SQL pro vytvoření tabulky.
 */
function dbs_get_extended_rules_table_sql( string $table_name, string $charset_collate ): string {
	return "CREATE TABLE $table_name (
		id int(11) NOT NULL AUTO_INCREMENT,
		rule_name varchar(255) NOT NULL,
		distance_from decimal(10,2) DEFAULT 0,
		distance_to decimal(10,2) DEFAULT 0,
		base_rate decimal(10,2) DEFAULT 0,
		per_km_rate decimal(10,2) DEFAULT 0,
		min_order_amount decimal(10,2) DEFAULT 0,
		max_order_amount decimal(10,2) DEFAULT 0,
		product_categories text,
		shipping_classes text,
		priority int(11) DEFAULT 0,
		is_active tinyint(1) DEFAULT 1,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		-- Nová pole pro hmotnost a rozměry
		weight_min decimal(10,3) DEFAULT 0,
		weight_max decimal(10,3) DEFAULT 0,
		weight_operator enum('AND', 'OR') DEFAULT 'AND',
		length_min decimal(10,2) DEFAULT 0,
		length_max decimal(10,2) DEFAULT 0,
		width_min decimal(10,2) DEFAULT 0,
		width_max decimal(10,2) DEFAULT 0,
		height_min decimal(10,2) DEFAULT 0,
		height_max decimal(10,2) DEFAULT 0,
		dimensions_operator enum('AND', 'OR') DEFAULT 'AND',
		PRIMARY KEY (id)
	) $charset_collate;";
}

/**
 * SQL pro tabulku cache vzdáleností.
 *
 * @param string $table_name Název tabulky.
 * @param string $charset_collate Charset collation.
 * @return string SQL dotaz.
 */
function dbs_get_cache_table_sql( string $table_name, string $charset_collate ): string {
	return "CREATE TABLE {$table_name} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		origin_hash varchar(32) NOT NULL,
		destination_hash varchar(32) NOT NULL,
		distance decimal(10,2) DEFAULT NULL,
		duration int(11) DEFAULT NULL,
		service varchar(50) DEFAULT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY unique_route (origin_hash, destination_hash),
		KEY idx_created (created_at)
	) {$charset_collate};";
}

/**
 * Vloží výchozí obchod pokud neexistuje.
 *
 * @return void
 */
function dbs_insert_default_store(): void {
	global $wpdb;

	$tables = dbs_get_table_names();
	
	// Kontrola, zda tabulka existuje a je prázdná.
	$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$tables['stores']}'" );
	
	if ( ! $table_exists ) {
		return;
	}

	$stores = dbs_get_stores();

	if ( empty( $stores ) ) {
		$default_address = dbs_build_default_store_address();

		dbs_insert_store( [
			'name'      => __( 'Hlavní obchod', 'distance-shipping' ),
			'address'   => $default_address,
			'is_active' => 1,
		] );
	}
}

/**
 * Sestaví výchozí adresu obchodu z WooCommerce nastavení.
 *
 * @return string Adresa obchodu.
 */
function dbs_build_default_store_address(): string {
	$address_parts = [
		get_option( 'woocommerce_store_address', '' ),
		get_option( 'woocommerce_store_city', '' ),
		get_option( 'woocommerce_store_postcode', '' ),
	];

	$country_code = get_option( 'woocommerce_default_country', 'US' );
	if ( class_exists( 'WC' ) && WC()->countries ) {
		$countries = WC()->countries->get_countries();
		if ( isset( $countries[ $country_code ] ) ) {
			$address_parts[] = $countries[ $country_code ];
		}
	}

	return implode( ', ', array_filter( $address_parts ) );
}

/**
 * Získá obchody z databáze.
 *
 * @param bool $active_only Zda získat pouze aktivní obchody.
 * @return array Data obchodů.
 */
function dbs_get_stores( bool $active_only = true ): array {
	global $wpdb;

	$tables = dbs_get_table_names();
	$where_clause = $active_only ? 'WHERE is_active = 1' : '';

	$results = $wpdb->get_results(
		"SELECT * FROM {$tables['stores']} {$where_clause} ORDER BY name ASC"
	);

	return $results ? $results : [];
}

/**
 * Získá jeden obchod podle ID.
 *
 * @param int $id ID obchodu.
 * @return object|null Data obchodu nebo null.
 */
function dbs_get_store( int $id ): ?object {
	global $wpdb;

	$tables = dbs_get_table_names();

	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$tables['stores']} WHERE id = %d",
			$id
		)
	);
}

/**
 * Vloží nový obchod.
 *
 * @param array $data Data obchodu.
 * @return int|false ID vloženého záznamu nebo false při chybě.
 */
function dbs_insert_store( array $data ) {
	global $wpdb;

	$tables = dbs_get_table_names();
	$insert_data = dbs_sanitize_store_data( $data );

	$result = $wpdb->insert(
		$tables['stores'],
		$insert_data,
		[ '%s', '%s', '%f', '%f', '%d' ]
	);

	return $result ? $wpdb->insert_id : false;
}

/**
 * Aktualizuje existující obchod.
 *
 * @param int   $id ID obchodu.
 * @param array $data Data obchodu.
 * @return int|false Počet aktualizovaných řádků nebo false při chybě.
 */
function dbs_update_store( int $id, array $data ) {
	global $wpdb;

	$tables = dbs_get_table_names();
	$update_data = dbs_sanitize_store_data( $data );

	return $wpdb->update(
		$tables['stores'],
		$update_data,
		[ 'id' => $id ],
		[ '%s', '%s', '%f', '%f', '%d' ],
		[ '%d' ]
	);
}

/**
 * Smaže obchod.
 *
 * @param int $id ID obchodu.
 * @return int|false Počet smazaných řádků nebo false při chybě.
 */
function dbs_delete_store( int $id ) {
	global $wpdb;

	$tables = dbs_get_table_names();

	return $wpdb->delete(
		$tables['stores'],
		[ 'id' => $id ],
		[ '%d' ]
	);
}

/**
 * Sanitizuje data obchodu.
 *
 * @param array $data Surová data obchodu.
 * @return array Sanitizovaná data obchodu.
 */
function dbs_sanitize_store_data( array $data ): array {
	$sanitized = [
		'name'      => sanitize_text_field( $data['name'] ?? '' ),
		'address'   => sanitize_textarea_field( $data['address'] ?? '' ),
		'is_active' => isset( $data['is_active'] ) ? (int) $data['is_active'] : 1,
	];

	if ( ! empty( $data['latitude'] ) ) {
		$sanitized['latitude'] = (float) $data['latitude'];
	}

	if ( ! empty( $data['longitude'] ) ) {
		$sanitized['longitude'] = (float) $data['longitude'];
	}

	return $sanitized;
}

/**
 * Získá dopravní pravidla z databáze.
 *
 * @param bool $active_only Zda získat pouze aktivní pravidla.
 * @return array Data dopravních pravidel.
 */
function dbs_get_shipping_rules( bool $active_only = true ): array {
	global $wpdb;

	$tables = dbs_get_table_names();
	$where_clause = $active_only ? 'WHERE is_active = 1' : '';

	$results = $wpdb->get_results(
		"SELECT * FROM {$tables['rules']} {$where_clause} ORDER BY priority ASC, distance_from ASC"
	);

	return $results ? $results : [];
}

/**
 * Získá jedno dopravní pravidlo podle ID.
 *
 * @param int $id ID pravidla.
 * @return object|null Data pravidla nebo null.
 */
function dbs_get_shipping_rule( int $id ): ?object {
	global $wpdb;

	$tables = dbs_get_table_names();

	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$tables['rules']} WHERE id = %d",
			$id
		)
	);
}

/**
 * Vloží nové dopravní pravidlo.
 *
 * @param array $data Data pravidla.
 * @return int|false ID vloženého záznamu nebo false při chybě.
 */
function dbs_insert_shipping_rule( array $data ) {
	global $wpdb;

	$tables = dbs_get_table_names();
	$insert_data = dbs_sanitize_rule_data( $data );

	$result = $wpdb->insert(
		$tables['rules'],
		$insert_data,
		[ '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%d', '%d', '%f', '%f', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%s' ]
	);

	return $result ? $wpdb->insert_id : false;
}

/**
 * Aktualizuje existující dopravní pravidlo.
 *
 * @param int   $id ID pravidla.
 * @param array $data Data pravidla.
 * @return int|false Počet aktualizovaných řádků nebo false při chybě.
 */
function dbs_update_shipping_rule( int $id, array $data ) {
	global $wpdb;

	$tables = dbs_get_table_names();
	$update_data = dbs_sanitize_rule_data( $data );

	return $wpdb->update(
		$tables['rules'],
		$update_data,
		[ 'id' => $id ],
		[ '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%d', '%d', '%f', '%f', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%s' ],
		[ '%d' ]
	);
}

/**
 * Smaže dopravní pravidlo.
 *
 * @param int $id ID pravidla.
 * @return int|false Počet smazaných řádků nebo false při chybě.
 */
function dbs_delete_shipping_rule( int $id ) {
	global $wpdb;

	$tables = dbs_get_table_names();

	return $wpdb->delete(
		$tables['rules'],
		[ 'id' => $id ],
		[ '%d' ]
	);
}

/**
 * Sanitizuje data dopravního pravidla.
 *
 * @param array $data Surová data pravidla.
 * @return array Sanitizovaná data pravidla.
 */
function dbs_sanitize_rule_data( array $data ): array {
	return [
		'rule_name'          => sanitize_text_field( $data['rule_name'] ?? '' ),
		'distance_from'      => (float) ( $data['distance_from'] ?? 0 ),
		'distance_to'        => (float) ( $data['distance_to'] ?? 0 ),
		'base_rate'          => (float) ( $data['base_rate'] ?? 0 ),
		'per_km_rate'        => (float) ( $data['per_km_rate'] ?? 0 ),
		'min_order_amount'   => (float) ( $data['min_order_amount'] ?? 0 ),
		'max_order_amount'   => (float) ( $data['max_order_amount'] ?? 0 ),
		'product_categories' => ! empty( $data['product_categories'] ) ? serialize( $data['product_categories'] ) : null,
		'shipping_classes'   => ! empty( $data['shipping_classes'] ) ? serialize( $data['shipping_classes'] ) : null,
		'is_active'          => isset( $data['is_active'] ) ? (int) $data['is_active'] : 1,
		'priority'           => (int) ( $data['priority'] ?? 0 ),
		// Nová pole pro hmotnost a rozměry
		'weight_min'         => (float) ( $data['weight_min'] ?? 0 ),
		'weight_max'         => (float) ( $data['weight_max'] ?? 0 ),
		'weight_operator'    => sanitize_text_field( $data['weight_operator'] ?? 'AND' ),
		'length_min'         => (float) ( $data['length_min'] ?? 0 ),
		'length_max'         => (float) ( $data['length_max'] ?? 0 ),
		'width_min'          => (float) ( $data['width_min'] ?? 0 ),
		'width_max'          => (float) ( $data['width_max'] ?? 0 ),
		'height_min'         => (float) ( $data['height_min'] ?? 0 ),
		'height_max'         => (float) ( $data['height_max'] ?? 0 ),
		'dimensions_operator' => sanitize_text_field( $data['dimensions_operator'] ?? 'AND' ),
	];
}

/**
 * Získá uloženou vzdálenost z cache.
 *
 * @param string $origin_hash Hash výchozí lokace.
 * @param string $destination_hash Hash cílové lokace.
 * @return object|null Data uložené vzdálenosti nebo null.
 */
function dbs_get_cached_distance( string $origin_hash, string $destination_hash ): ?object {
	global $wpdb;

	$tables = dbs_get_table_names();
	$cache_duration = (int) get_option( 'dbs_cache_duration', 24 );

	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$tables['cache']} 
			 WHERE origin_hash = %s AND destination_hash = %s 
			 AND created_at > DATE_SUB(NOW(), INTERVAL %d HOUR)",
			$origin_hash,
			$destination_hash,
			$cache_duration
		)
	);
}

/**
 * Uloží vzdálenost do cache.
 *
 * @param string   $origin_hash Hash výchozí lokace.
 * @param string   $destination_hash Hash cílové lokace.
 * @param float    $distance Hodnota vzdálenosti.
 * @param int|null $duration Doba trvání v sekundách.
 * @param string|null $service Použitá mapová služba.
 * @return int|false Počet ovlivněných řádků nebo false při chybě.
 */
function dbs_cache_distance( string $origin_hash, string $destination_hash, float $distance, ?int $duration = null, ?string $service = null ) {
	global $wpdb;

	$tables = dbs_get_table_names();

	return $wpdb->replace(
		$tables['cache'],
		[
			'origin_hash'      => $origin_hash,
			'destination_hash' => $destination_hash,
			'distance'         => $distance,
			'duration'         => $duration,
			'service'          => $service,
		],
		[ '%s', '%s', '%f', '%d', '%s' ]
	);
}

/**
 * Vyčistí staré záznamy z cache.
 *
 * @return int|false Počet smazaných řádků nebo false při chybě.
 */
function dbs_cleanup_cache() {
	global $wpdb;

	$tables = dbs_get_table_names();
	$cache_duration = (int) get_option( 'dbs_cache_duration', 24 );

	return $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$tables['cache']} 
			 WHERE created_at < DATE_SUB(NOW(), INTERVAL %d HOUR)",
			$cache_duration
		)
	);
}

/**
 * Generuje hash pro lokaci.
 *
 * @param string $address Adresa lokace.
 * @return string Hash lokace.
 */
function dbs_generate_location_hash( string $address ): string {
	return md5( strtolower( trim( $address ) ) );
}

/**
 * Získá dostupné kategorie produktů.
 *
 * @return array Pole kategorií.
 */
function dbs_get_product_categories(): array {
	$terms = get_terms( [
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
	] );

	if ( is_wp_error( $terms ) ) {
		return [];
	}

	$categories = [];
	foreach ( $terms as $term ) {
		$categories[ $term->term_id ] = $term->name;
	}

	return $categories;
}

/**
 * Získá dostupné dopravní třídy.
 *
 * @return array Pole dopravních tříd.
 */
function dbs_get_shipping_classes(): array {
	$terms = get_terms( [
		'taxonomy'   => 'product_shipping_class',
		'hide_empty' => false,
	] );

	if ( is_wp_error( $terms ) ) {
		return [];
	}

	$classes = [];
	foreach ( $terms as $term ) {
		$classes[ $term->term_id ] = $term->name;
	}

	return $classes;
}

/**
 * Získá statistiky pluginu.
 *
 * @return array Statistiky pluginu.
 */
function dbs_get_plugin_statistics(): array {
	return [
		'active_stores' => count( dbs_get_stores( true ) ),
		'active_rules'  => count( dbs_get_shipping_rules( true ) ),
		'map_service'   => ucfirst( get_option( 'dbs_map_service', 'openstreetmap' ) ),
		'distance_unit' => get_option( 'dbs_distance_unit', 'km' ),
	];
}
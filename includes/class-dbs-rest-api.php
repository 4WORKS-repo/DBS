<?php
/**
 * REST API třída pro Distance Based Shipping plugin.
 *
 * Soubor: includes/class-dbs-rest-api.php
 *
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Distance Based Shipping REST API třída.
 */
class DBS_REST_API {

	/**
	 * Namespace pro REST API.
	 *
	 * @var string
	 */
	private string $namespace = 'distance-shipping/v1';

	/**
	 * Konstruktor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Registruje REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Endpoint pro výpočet vzdálenosti.
		register_rest_route( $this->namespace, '/calculate-distance', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'calculate_distance' ],
			'permission_callback' => [ $this, 'check_permission' ],
			'args'                => [
				'origin'      => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				],
				'destination' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				],
			],
		] );

		// Endpoint pro geokódování.
		register_rest_route( $this->namespace, '/geocode', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'geocode_address' ],
			'permission_callback' => [ $this, 'check_permission' ],
			'args'                => [
				'address' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				],
			],
		] );

		// Endpoint pro získání obchodů.
		register_rest_route( $this->namespace, '/stores', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_stores' ],
			'permission_callback' => [ $this, 'check_manage_permission' ],
			'args'                => [
				'active_only' => [
					'default'           => true,
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
				],
			],
		] );

		// Endpoint pro vytvoření obchodu.
		register_rest_route( $this->namespace, '/stores', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'create_store' ],
			'permission_callback' => [ $this, 'check_manage_permission' ],
			'args'                => $this->get_store_schema(),
		] );

		// Endpoint pro aktualizaci obchodu.
		register_rest_route( $this->namespace, '/stores/(?P<id>\d+)', [
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => [ $this, 'update_store' ],
			'permission_callback' => [ $this, 'check_manage_permission' ],
			'args'                => $this->get_store_schema(),
		] );

		// Endpoint pro smazání obchodu.
		register_rest_route( $this->namespace, '/stores/(?P<id>\d+)', [
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => [ $this, 'delete_store' ],
			'permission_callback' => [ $this, 'check_manage_permission' ],
		] );

		// Endpoint pro získání pravidel.
		register_rest_route( $this->namespace, '/rules', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_rules' ],
			'permission_callback' => [ $this, 'check_manage_permission' ],
			'args'                => [
				'active_only' => [
					'default'           => true,
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
				],
			],
		] );

		// Endpoint pro vytvoření pravidla.
		register_rest_route( $this->namespace, '/rules', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'create_rule' ],
			'permission_callback' => [ $this, 'check_manage_permission' ],
			'args'                => $this->get_rule_schema(),
		] );

		// Endpoint pro aktualizaci pravidla.
		register_rest_route( $this->namespace, '/rules/(?P<id>\d+)', [
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => [ $this, 'update_rule' ],
			'permission_callback' => [ $this, 'check_manage_permission' ],
			'args'                => $this->get_rule_schema(),
		] );

		// Endpoint pro smazání pravidla.
		register_rest_route( $this->namespace, '/rules/(?P<id>\d+)', [
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => [ $this, 'delete_rule' ],
			'permission_callback' => [ $this, 'check_manage_permission' ],
		] );

		// Endpoint pro výpočet dopravních sazeb.
		register_rest_route( $this->namespace, '/shipping-rates', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'calculate_shipping_rates' ],
			'permission_callback' => '__return_true', // Veřejný endpoint
			'args'                => [
				'destination' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				],
				'cart_total'  => [
					'default'           => 0,
					'type'              => 'number',
					'sanitize_callback' => 'floatval',
				],
			],
		] );
	}

	/**
	 * Kontroluje základní oprávnění.
	 *
	 * @return bool True pokud má uživatel oprávnění.
	 */
	public function check_permission(): bool {
		return current_user_can( 'read' );
	}

	/**
	 * Kontroluje oprávnění pro správu.
	 *
	 * @return bool True pokud má uživatel oprávnění.
	 */
	public function check_manage_permission(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Endpoint pro výpočet vzdálenosti.
	 *
	 * @param WP_REST_Request $request REST požadavek.
	 * @return WP_REST_Response|WP_Error REST odpověď.
	 */
	public function calculate_distance( WP_REST_Request $request ) {
		$origin = $request->get_param( 'origin' );
		$destination = $request->get_param( 'destination' );

		$distance = dbs_calculate_distance( $origin, $destination );

		if ( false === $distance ) {
			return new WP_Error(
				'calculation_failed',
				__( 'Nepodařilo se vypočítat vzdálenost.', 'distance-shipping' ),
				[ 'status' => 400 ]
			);
		}

		return new WP_REST_Response( [
			'distance'          => $distance,
			'formatted_distance' => dbs_format_distance( $distance ),
			'distance_unit'     => get_option( 'dbs_distance_unit', 'km' ),
		] );
	}

	/**
	 * Endpoint pro geokódování adresy.
	 *
	 * @param WP_REST_Request $request REST požadavek.
	 * @return WP_REST_Response|WP_Error REST odpověď.
	 */
	public function geocode_address( WP_REST_Request $request ) {
		$address = $request->get_param( 'address' );

		$coordinates = dbs_geocode_address( $address );

		if ( false === $coordinates ) {
			return new WP_Error(
				'geocoding_failed',
				__( 'Nepodařilo se geokódovat adresu.', 'distance-shipping' ),
				[ 'status' => 400 ]
			);
		}

		return new WP_REST_Response( [
			'latitude'  => $coordinates['lat'],
			'longitude' => $coordinates['lng'],
		] );
	}

	/**
	 * Endpoint pro získání obchodů.
	 *
	 * @param WP_REST_Request $request REST požadavek.
	 * @return WP_REST_Response REST odpověď.
	 */
	public function get_stores( WP_REST_Request $request ): WP_REST_Response {
		$active_only = $request->get_param( 'active_only' );
		$stores = dbs_get_stores( $active_only );

		return new WP_REST_Response( $stores );
	}

	/**
	 * Endpoint pro vytvoření obchodu.
	 *
	 * @param WP_REST_Request $request REST požadavek.
	 * @return WP_REST_Response|WP_Error REST odpověď.
	 */
	public function create_store( WP_REST_Request $request ) {
		$store_data = $this->prepare_store_data( $request );
		$store_id = dbs_insert_store( $store_data );

		if ( false === $store_id ) {
			return new WP_Error(
				'creation_failed',
				__( 'Nepodařilo se vytvořit obchod.', 'distance-shipping' ),
				[ 'status' => 500 ]
			);
		}

		$store = dbs_get_store( $store_id );
		return new WP_REST_Response( $store, 201 );
	}

	/**
	 * Endpoint pro aktualizaci obchodu.
	 *
	 * @param WP_REST_Request $request REST požadavek.
	 * @return WP_REST_Response|WP_Error REST odpověď.
	 */
	public function update_store( WP_REST_Request $request ) {
		$store_id = (int) $request->get_param( 'id' );
		$store = dbs_get_store( $store_id );

		if ( ! $store ) {
			return new WP_Error(
				'store_not_found',
				__( 'Obchod nebyl nalezen.', 'distance-shipping' ),
				[ 'status' => 404 ]
			);
		}

		$store_data = $this->prepare_store_data( $request );
		$result = dbs_update_store( $store_id, $store_data );

		if ( false === $result ) {
			return new WP_Error(
				'update_failed',
				__( 'Nepodařilo se aktualizovat obchod.', 'distance-shipping' ),
				[ 'status' => 500 ]
			);
		}

		$updated_store = dbs_get_store( $store_id );
		return new WP_REST_Response( $updated_store );
	}

	/**
	 * Endpoint pro smazání obchodu.
	 *
	 * @param WP_REST_Request $request REST požadavek.
	 * @return WP_REST_Response|WP_Error REST odpověď.
	 */
	public function delete_store( WP_REST_Request $request ) {
		$store_id = (int) $request->get_param( 'id' );
		$store = dbs_get_store( $store_id );

		if ( ! $store ) {
			return new WP_Error(
				'store_not_found',
				__( 'Obchod nebyl nalezen.', 'distance-shipping' ),
				[ 'status' => 404 ]
			);
		}

		$result = dbs_delete_store( $store_id );

		if ( false === $result ) {
			return new WP_Error(
				'deletion_failed',
				__( 'Nepodařilo se smazat obchod.', 'distance-shipping' ),
				[ 'status' => 500 ]
			);
		}

		return new WP_REST_Response( [
			'deleted' => true,
			'previous' => $store,
		] );
	}

	/**
	 * Endpoint pro získání pravidel.
	 *
	 * @param WP_REST_Request $request REST požadavek.
	 * @return WP_REST_Response REST odpověď.
	 */
	public function get_rules( WP_REST_Request $request ): WP_REST_Response {
		$active_only = $request->get_param( 'active_only' );
		$rules = dbs_get_shipping_rules( $active_only );

		// Deserializace polí kategorií a tříd.
		foreach ( $rules as $rule ) {
			$rule->product_categories = maybe_unserialize( $rule->product_categories ) ?: [];
			$rule->shipping_classes = maybe_unserialize( $rule->shipping_classes ) ?: [];
		}

		return new WP_REST_Response( $rules );
	}

	/**
	 * Endpoint pro vytvoření pravidla.
	 *
	 * @param WP_REST_Request $request REST požadavek.
	 * @return WP_REST_Response|WP_Error REST odpověď.
	 */
	public function create_rule( WP_REST_Request $request ) {
		$rule_data = $this->prepare_rule_data( $request );
		$rule_id = dbs_insert_shipping_rule( $rule_data );

		if ( false === $rule_id ) {
			return new WP_Error(
				'creation_failed',
				__( 'Nepodařilo se vytvořit pravidlo.', 'distance-shipping' ),
				[ 'status' => 500 ]
			);
		}

		$rule = dbs_get_shipping_rule( $rule_id );
		return new WP_REST_Response( $rule, 201 );
	}

	/**
	 * Endpoint pro aktualizaci pravidla.
	 *
	 * @param WP_REST_Request $request REST požadavek.
	 * @return WP_REST_Response|WP_Error REST odpověď.
	 */
	public function update_rule( WP_REST_Request $request ) {
		$rule_id = (int) $request->get_param( 'id' );
		$rule = dbs_get_shipping_rule( $rule_id );

		if ( ! $rule ) {
			return new WP_Error(
				'rule_not_found',
				__( 'Pravidlo nebylo nalezeno.', 'distance-shipping' ),
				[ 'status' => 404 ]
			);
		}

		$rule_data = $this->prepare_rule_data( $request );
		$result = dbs_update_shipping_rule( $rule_id, $rule_data );

		if ( false === $result ) {
			return new WP_Error(
				'update_failed',
				__( 'Nepodařilo se aktualizovat pravidlo.', 'distance-shipping' ),
				[ 'status' => 500 ]
			);
		}

		$updated_rule = dbs_get_shipping_rule( $rule_id );
		return new WP_REST_Response( $updated_rule );
	}

	/**
	 * Endpoint pro smazání pravidla.
	 *
	 * @param WP_REST_Request $request REST požadavek.
	 * @return WP_REST_Response|WP_Error REST odpověď.
	 */
	public function delete_rule( WP_REST_Request $request ) {
		$rule_id = (int) $request->get_param( 'id' );
		$rule = dbs_get_shipping_rule( $rule_id );

		if ( ! $rule ) {
			return new WP_Error(
				'rule_not_found',
				__( 'Pravidlo nebylo nalezeno.', 'distance-shipping' ),
				[ 'status' => 404 ]
			);
		}

		$result = dbs_delete_shipping_rule( $rule_id );

		if ( false === $result ) {
			return new WP_Error(
				'deletion_failed',
				__( 'Nepodařilo se smazat pravidlo.', 'distance-shipping' ),
				[ 'status' => 500 ]
			);
		}

		return new WP_REST_Response( [
			'deleted' => true,
			'previous' => $rule,
		] );
	}

	/**
	 * Endpoint pro výpočet dopravních sazeb.
	 *
	 * @param WP_REST_Request $request REST požadavek.
	 * @return WP_REST_Response|WP_Error REST odpověď.
	 */
	public function calculate_shipping_rates( WP_REST_Request $request ) {
		$destination = $request->get_param( 'destination' );
		$cart_total = $request->get_param( 'cart_total' );

		// Najdeme nejbližší obchod.
		$nearest_store = dbs_find_nearest_store( $destination );
		if ( ! $nearest_store ) {
			return new WP_Error(
				'no_store_found',
				__( 'Nepodařilo se najít nejbližší obchod.', 'distance-shipping' ),
				[ 'status' => 400 ]
			);
		}

		// Vypočítáme vzdálenost.
		$distance = dbs_calculate_distance( $nearest_store->address, $destination );
		if ( false === $distance ) {
			return new WP_Error(
				'distance_calculation_failed',
				__( 'Nepodařilo se vypočítat vzdálenost.', 'distance-shipping' ),
				[ 'status' => 400 ]
			);
		}

		// Vytvoříme mock balíček.
		$mock_package = [
			'contents'      => [],
			'contents_cost' => $cart_total,
			'destination'   => [ 'address' => $destination ],
		];

		// Získáme aplikovatelná pravidla.
		$applicable_rules = dbs_get_applicable_shipping_rules( $distance, $mock_package );
		$shipping_rates = [];

		foreach ( $applicable_rules as $rule ) {
			$rate = dbs_calculate_shipping_rate_from_rule( $rule, $distance, $mock_package );
			if ( $rate ) {
				$shipping_rates[] = [
					'id'       => $rate['id'],
					'label'    => $rate['label'],
					'cost'     => $rate['cost'],
					'distance' => $distance,
				];
			}
		}

		// Pokud žádná pravidla neaplikují, přidáme záložní sazbu.
		if ( empty( $shipping_rates ) ) {
			$fallback_rate = dbs_get_fallback_shipping_rate();
			if ( $fallback_rate ) {
				$shipping_rates[] = [
					'id'       => $fallback_rate['id'],
					'label'    => $fallback_rate['label'],
					'cost'     => $fallback_rate['cost'],
					'distance' => $distance,
				];
			}
		}

		return new WP_REST_Response( [
			'rates'    => $shipping_rates,
			'distance' => $distance,
			'store'    => $nearest_store->name,
		] );
	}

	/**
	 * Připraví data obchodu z požadavku.
	 *
	 * @param WP_REST_Request $request REST požadavek.
	 * @return array Připravená data obchodu.
	 */
	private function prepare_store_data( WP_REST_Request $request ): array {
		$data = [
			'name'    => $request->get_param( 'name' ),
			'address' => $request->get_param( 'address' ),
		];

		if ( $request->has_param( 'latitude' ) ) {
			$data['latitude'] = $request->get_param( 'latitude' );
		}

		if ( $request->has_param( 'longitude' ) ) {
			$data['longitude'] = $request->get_param( 'longitude' );
		}

		if ( $request->has_param( 'is_active' ) ) {
			$data['is_active'] = $request->get_param( 'is_active' );
		}

		return $data;
	}

	/**
	 * Připraví data pravidla z požadavku.
	 *
	 * @param WP_REST_Request $request REST požadavek.
	 * @return array Připravená data pravidla.
	 */
	private function prepare_rule_data( WP_REST_Request $request ): array {
		return [
			'rule_name'          => $request->get_param( 'rule_name' ),
			'distance_from'      => $request->get_param( 'distance_from' ),
			'distance_to'        => $request->get_param( 'distance_to' ),
			'base_rate'          => $request->get_param( 'base_rate' ),
			'per_km_rate'        => $request->get_param( 'per_km_rate' ),
			'min_order_amount'   => $request->get_param( 'min_order_amount' ),
			'max_order_amount'   => $request->get_param( 'max_order_amount' ),
			'product_categories' => $request->get_param( 'product_categories' ) ?: [],
			'shipping_classes'   => $request->get_param( 'shipping_classes' ) ?: [],
			'is_active'          => $request->get_param( 'is_active' ),
			'priority'           => $request->get_param( 'priority' ),
		];
	}

	/**
	 * Získá schéma pro obchod.
	 *
	 * @return array Schéma obchodu.
	 */
	private function get_store_schema(): array {
		return [
			'name' => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'address' => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			],
			'latitude' => [
				'type'              => 'number',
				'sanitize_callback' => 'floatval',
			],
			'longitude' => [
				'type'              => 'number',
				'sanitize_callback' => 'floatval',
			],
			'is_active' => [
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
		];
	}

	/**
	 * Získá schéma pro pravidlo.
	 *
	 * @return array Schéma pravidla.
	 */
	private function get_rule_schema(): array {
		return [
			'rule_name' => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'distance_from' => [
				'type'              => 'number',
				'default'           => 0,
				'sanitize_callback' => 'floatval',
			],
			'distance_to' => [
				'type'              => 'number',
				'default'           => 0,
				'sanitize_callback' => 'floatval',
			],
			'base_rate' => [
				'type'              => 'number',
				'default'           => 0,
				'sanitize_callback' => 'floatval',
			],
			'per_km_rate' => [
				'type'              => 'number',
				'default'           => 0,
				'sanitize_callback' => 'floatval',
			],
			'min_order_amount' => [
				'type'              => 'number',
				'default'           => 0,
				'sanitize_callback' => 'floatval',
			],
			'max_order_amount' => [
				'type'              => 'number',
				'default'           => 0,
				'sanitize_callback' => 'floatval',
			],
			'product_categories' => [
				'type'    => 'array',
				'default' => [],
				'items'   => [
					'type' => 'integer',
				],
			],
			'shipping_classes' => [
				'type'    => 'array',
				'default' => [],
				'items'   => [
					'type' => 'integer',
				],
			],
			'is_active' => [
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			'priority' => [
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			],
		];
	}
}
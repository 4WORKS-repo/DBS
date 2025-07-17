<?php
/**
 * WooCommerce Shipping Method Class for Distance Based Shipping.
 *
 * File: includes/class-dbs-shipping-method.php
 *
 * @package DistanceBasedShipping
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Distance Based Shipping Method Class.
 */
class DBS_Shipping_Method extends WC_Shipping_Method {

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Shipping instance ID.
	 */
	public function __construct( int $instance_id = 0 ) {
		// Ensure WooCommerce is loaded
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		
		$this->id                 = 'distance_based';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Distance Based Shipping', 'distance-shipping' );
		$this->method_description = __( 'Calculate shipping rates based on distance between store and customer address.', 'distance-shipping' );
		$this->supports           = [
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		];

		$this->init();
	}

	/**
	 * Initialize the shipping method.
	 *
	 * @return void
	 */
	public function init(): void {
		// Ensure WooCommerce is loaded
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		
		try {
			// Load the settings API.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables.
			$this->title   = $this->get_option( 'title' );
			$this->enabled = $this->get_option( 'enabled' );

			// Save settings in admin.
			add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
			
			// Add hooks for better cart integration with much higher priority
			add_action( 'woocommerce_after_shipping_calculator', [ $this, 'add_shipping_calculator_info' ], 50 );
			add_filter( 'woocommerce_shipping_method_title', [ $this, 'enhance_shipping_method_title' ], 50, 2 );
			
			// Add debug logging if enabled
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log( 'DBS Shipping Method initialized successfully' );
			}
		} catch ( Exception $e ) {
			// Log error but don't break WooCommerce
			error_log( 'DBS Shipping Method Init Error: ' . $e->getMessage() );
		}
	}

	/**
	 * Initialize form fields.
	 *
	 * @return void
	 */
	public function init_form_fields(): void {
		$this->instance_form_fields = [
			'enabled' => [
				'title'   => __( 'Enable/Disable', 'distance-shipping' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Distance Based Shipping', 'distance-shipping' ),
				'default' => 'yes',
			],
			'title'   => [
				'title'       => __( 'Method Title', 'distance-shipping' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'distance-shipping' ),
				'default'     => __( 'Distance Based Shipping', 'distance-shipping' ),
				'desc_tip'    => true,
			],
		];
	}

	/**
	 * Calculate shipping rates.
	 *
	 * @param array $package Shipping package.
	 * @return void
	 */
	public function calculate_shipping( $package = [] ): void {
		// Check if method is enabled.
		if ( 'yes' !== $this->enabled ) {
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log( 'DBS: Shipping method is disabled' );
			}
			return;
		}

		// Debug logging
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Starting shipping calculation for package: ' . wp_json_encode( $package ) );
		}

		// Get destination address with priority: shipping > billing > session
		$destination = $this->get_optimized_destination_address( $package );
		if ( empty( $destination ) ) {
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log( 'DBS: No destination address found' );
			}
			$this->add_fallback_rate();
			return;
		}

		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Destination address: ' . $destination );
		}

		// Check cache first - include cart data in cache key
		$cart_hash = $this->get_cart_hash( $package );
		$cache_key = 'dbs_shipping_' . md5( $destination . '_' . $cart_hash );
		$cached_result = get_transient( $cache_key );
		
		if ( $cached_result !== false ) {
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log( 'DBS: Using cached shipping calculation' );
			}
			$this->add_rate( $cached_result );
			return;
		}

		// Get nearest store.
		$store = $this->get_nearest_store( $destination );
		if ( ! $store ) {
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log( 'DBS: No store found' );
			}
			$this->add_fallback_rate();
			return;
		}

		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Found store: ' . $store->name . ' at ' . $store->address );
		}

		// Calculate distance.
		$distance = $this->calculate_distance_to_store( $store, $destination );
		if ( false === $distance ) {
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log( 'DBS: Distance calculation failed' );
			}
			$this->add_fallback_rate();
			return;
		}

		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Calculated distance: ' . $distance . ' km' );
		}

		// Get applicable shipping rules.
		$rules = $this->get_applicable_rules( $distance, $package );
		if ( empty( $rules ) ) {
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log( 'DBS: No applicable rules found' );
			}
			$this->add_fallback_rate();
			return;
		}

		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Found ' . count( $rules ) . ' applicable rules' );
		}

		// Calculate shipping rates - use the first applicable rule
		$rule = $rules[0]; // Use the first (highest priority) rule
		$rate = $this->calculate_rate_from_rule( $rule, $distance, $package );
		
		if ( $rate ) {
			// Cache the result for 1 hour
			set_transient( $cache_key, $rate, HOUR_IN_SECONDS );
			
			// Add rate to WooCommerce shipping methods (nativní způsob)
			$this->add_rate( $rate );
			
			// Uložit informace do session pro zobrazení
			if ( function_exists( 'WC' ) && WC() && WC()->session ) {
				WC()->session->set( 'dbs_distance_info', $rate['meta_data']['formatted_distance'] );
				WC()->session->set( 'dbs_store_info', $store );
			}
			
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log( 'DBS: Added rate: ' . $rate['label'] . ' - ' . $rate['cost'] );
			}
		} else {
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log( 'DBS: No rate calculated, using fallback' );
			}
			$this->add_fallback_rate();
		}
	}

	/**
	 * Get optimized destination address.
	 * Prioritizes shipping address, then billing address, then session.
	 *
	 * @param array $package Shipping package.
	 * @return string Destination address.
	 */
	private function get_optimized_destination_address( array $package ): string {
		// Check if shipping address is set
		if ( ! empty( $package['destination']['city'] ) ) {
			return $this->get_destination_address( $package );
		}

		// Try to get billing address if shipping address is not available
		if ( function_exists( 'WC' ) && WC() && WC()->customer ) {
			$billing_address = WC()->customer->get_billing_address();
			if ( ! empty( $billing_address ) ) {
				return $billing_address;
			}
		}

		// Fallback to session
		if ( function_exists( 'WC' ) && WC() && WC()->session ) {
			$shipping_address = WC()->session->get( 'shipping_address' );
			if ( ! empty( $shipping_address ) ) {
				return $shipping_address;
			}
		}

		return '';
	}

	/**
	 * Get destination address from package.
	 *
	 * @param array $package Shipping package.
	 * @return string Destination address.
	 */
	private function get_destination_address( array $package ): string {
		$destination_parts = [];

		// Check if we have a complete address
		if ( ! empty( $package['destination']['city'] ) ) {
			// Build address from components
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
		} else {
			// Try to get from session
			if ( function_exists( 'WC' ) && WC() && WC()->session ) {
				$shipping_address = WC()->session->get( 'shipping_address' );
				if ( ! empty( $shipping_address ) ) {
					return $shipping_address;
				}
			}
		}

		$address = implode( ', ', array_filter( $destination_parts ) );
		
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Built destination address: ' . $address );
		}
		
		return $address;
	}

	/**
	 * Get cart hash for cache key generation.
	 * Includes cart total, weight, and item quantities.
	 *
	 * @param array $package Shipping package.
	 * @return string Cart hash.
	 */
	private function get_cart_hash( array $package ): string {
		$cart_data = [];
		
		// Get cart total
		if ( function_exists( 'WC' ) && WC() && WC()->cart ) {
			$cart_data['total'] = WC()->cart->get_cart_contents_total();
			$cart_data['weight'] = WC()->cart->get_cart_contents_weight();
			$cart_data['item_count'] = WC()->cart->get_cart_contents_count();
		}
		
		// Get package contents
		if ( ! empty( $package['contents'] ) ) {
			$contents_hash = [];
			foreach ( $package['contents'] as $item_key => $item ) {
				$contents_hash[] = $item['product_id'] . '_' . $item['quantity'] . '_' . ( $item['variation_id'] ?? 0 );
			}
			$cart_data['contents'] = implode( '|', $contents_hash );
		}
		
		return md5( wp_json_encode( $cart_data ) );
	}

	/**
	 * Get address from session or user data.
	 *
	 * @return string Address or empty string.
	 */
	private function get_address_from_session(): string {
		// Try to get from session
		$shipping_address = WC()->session->get( 'shipping_address' );
		if ( ! empty( $shipping_address ) ) {
			return $shipping_address;
		}

		// Try to get from user meta
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$shipping_address = get_user_meta( $user_id, 'shipping_address_1', true );
			if ( ! empty( $shipping_address ) ) {
				$shipping_city = get_user_meta( $user_id, 'shipping_city', true );
				$shipping_postcode = get_user_meta( $user_id, 'shipping_postcode', true );
				
				$address_parts = [ $shipping_address ];
				if ( ! empty( $shipping_city ) ) {
					$address_parts[] = $shipping_city;
				}
				if ( ! empty( $shipping_postcode ) ) {
					$address_parts[] = $shipping_postcode;
				}
				
				return implode( ', ', $address_parts );
			}
		}

		return '';
	}

	/**
	 * Get nearest store to destination.
	 *
	 * @param string $destination Destination address.
	 * @return object|null Store object or null.
	 */
	private function get_nearest_store( string $destination ): ?object {
		$stores = dbs_get_stores( true );
		
		if ( empty( $stores ) ) {
			return null;
		}

		// If only one store, return it.
		if ( count( $stores ) === 1 ) {
			return $stores[0];
		}

		// For multiple stores, find the nearest one.
		$nearest_store = null;
		$shortest_distance = PHP_FLOAT_MAX;

		foreach ( $stores as $store ) {
			$distance = $this->calculate_distance_to_store( $store, $destination );
			
			if ( false !== $distance && $distance < $shortest_distance ) {
				$shortest_distance = $distance;
				$nearest_store = $store;
			}
		}

		return $nearest_store;
	}

	/**
	 * Calculate distance between store and destination.
	 *
	 * @param object $store Store object.
	 * @param string $destination Destination address.
	 * @return float|false Distance in configured units or false on failure.
	 */
	private function calculate_distance_to_store( object $store, string $destination ) {
		return dbs_calculate_distance( $store->address, $destination );
	}

	/**
	 * Get applicable shipping rules for distance and package.
	 *
	 * @param float $distance Distance value.
	 * @param array $package Shipping package.
	 * @return array Applicable rules.
	 */
	private function get_applicable_rules( float $distance, array $package ): array {
		$all_rules = dbs_get_shipping_rules( true );
		$applicable_rules = [];

		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Found ' . count( $all_rules ) . ' total rules' );
		}

		foreach ( $all_rules as $rule ) {
			if ( $this->is_rule_applicable( $rule, $distance, $package ) ) {
				$applicable_rules[] = $rule;
				if ( get_option( 'dbs_debug_mode', 0 ) ) {
					error_log( 'DBS: Rule applicable: ' . $rule->rule_name . ' (distance: ' . $rule->distance_from . '-' . $rule->distance_to . ')' );
				}
			} else {
				if ( get_option( 'dbs_debug_mode', 0 ) ) {
					error_log( 'DBS: Rule not applicable: ' . $rule->rule_name . ' (distance: ' . $rule->distance_from . '-' . $rule->distance_to . ')' );
				}
			}
		}

		// Sort by priority (highest priority first).
		usort( $applicable_rules, function( $a, $b ) {
			return $b->priority <=> $a->priority; // Sestupně podle priority
		} );

		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Applicable rules after sorting by priority:' );
			foreach ( $applicable_rules as $rule ) {
				error_log( 'DBS: - Rule: ' . $rule->rule_name . ', Priority: ' . $rule->priority . ', Weight: ' . $rule->weight_min . '-' . $rule->weight_max . 'kg' );
			}
		}

		return $applicable_rules;
	}

	/**
	 * Check if rule is applicable for current conditions.
	 *
	 * @param object $rule Shipping rule.
	 * @param float  $distance Distance value.
	 * @param array  $package Shipping package.
	 * @return bool True if rule is applicable.
	 */
	private function is_rule_applicable( object $rule, float $distance, array $package ): bool {
		// Check distance range.
		if ( $distance < $rule->distance_from ) {
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log( 'DBS: Rule ' . $rule->rule_name . ' - distance too low: ' . $distance . ' < ' . $rule->distance_from );
			}
			return false;
		}
		
		if ( $rule->distance_to > 0 && $distance > $rule->distance_to ) {
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log( 'DBS: Rule ' . $rule->rule_name . ' - distance too high: ' . $distance . ' > ' . $rule->distance_to );
			}
			return false;
		}

		// Check order amount - use package contents_cost if cart is not available (e.g. in admin tests)
		$cart_total = 0;
		if ( function_exists( 'WC' ) && WC() && WC()->cart ) {
			$cart_total = WC()->cart->get_cart_contents_total();
		} elseif ( isset( $package['contents_cost'] ) ) {
			$cart_total = $package['contents_cost'];
		}
		
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Order amount check - Cart total: ' . $cart_total . ', Rule min: ' . $rule->min_order_amount . ', Rule max: ' . $rule->max_order_amount );
		}
		
		if ( $rule->min_order_amount > 0 && $cart_total < $rule->min_order_amount ) {
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log( 'DBS: Rule ' . $rule->rule_name . ' - order amount too low: ' . $cart_total . ' < ' . $rule->min_order_amount );
			}
			return false;
		}

		if ( $rule->max_order_amount > 0 && $cart_total > $rule->max_order_amount ) {
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log( 'DBS: Rule ' . $rule->rule_name . ' - order amount too high: ' . $cart_total . ' > ' . $rule->max_order_amount );
			}
			return false;
		}

		// Check product categories.
		if ( ! empty( $rule->product_categories ) ) {
			$rule_categories = maybe_unserialize( $rule->product_categories );
			if ( ! $this->package_has_categories( $package, $rule_categories ) ) {
				if ( get_option( 'dbs_debug_mode', 0 ) ) {
					error_log( 'DBS: Rule ' . $rule->rule_name . ' - package does not have required categories' );
				}
				return false;
			}
		}

		// Check shipping classes.
		if ( ! empty( $rule->shipping_classes ) ) {
			$rule_classes = maybe_unserialize( $rule->shipping_classes );
			if ( ! $this->package_has_shipping_classes( $package, $rule_classes ) ) {
				if ( get_option( 'dbs_debug_mode', 0 ) ) {
					error_log( 'DBS: Rule ' . $rule->rule_name . ' - package does not have required shipping classes' );
				}
				return false;
			}
		}

		// Check weight and dimensions conditions
		if ( function_exists( 'dbs_check_all_conditions' ) ) {
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log( 'DBS: Checking weight/dimensions conditions for rule: ' . $rule->rule_name );
			}
			if ( ! dbs_check_all_conditions( $rule, $package ) ) {
				if ( get_option( 'dbs_debug_mode', 0 ) ) {
					error_log( 'DBS: Rule ' . $rule->rule_name . ' - weight or dimensions conditions not met' );
				}
				return false;
			}
		}

		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Rule ' . $rule->rule_name . ' is applicable' );
		}
		
		return true;
	}

	/**
	 * Check if package contains products from specified categories.
	 *
	 * @param array $package Shipping package.
	 * @param array $categories Category IDs to check.
	 * @return bool True if package has products from categories.
	 */
	private function package_has_categories( array $package, array $categories ): bool {
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
	 * Check if package contains products with specified shipping classes.
	 *
	 * @param array $package Shipping package.
	 * @param array $classes Shipping class IDs to check.
	 * @return bool True if package has products with shipping classes.
	 */
	private function package_has_shipping_classes( array $package, array $classes ): bool {
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
	 * Calculate shipping rate from rule.
	 *
	 * @param object $rule Shipping rule object.
	 * @param float  $distance Distance value.
	 * @param array  $package Shipping package.
	 * @return array|null Rate array or null.
	 */
	private function calculate_rate_from_rule( object $rule, float $distance, array $package ): ?array {
		$base_rate = (float) $rule->base_rate;
		$per_km_rate = (float) $rule->per_km_rate;

		$total_cost = $base_rate + ( $distance * $per_km_rate );

		// Apply filters for custom calculations.
		$total_cost = apply_filters( 'dbs_calculate_shipping_cost', $total_cost, $rule, $distance, $package );

		if ( $total_cost < 0 ) {
			$total_cost = 0;
		}

		$adjust_shipping_for_vat = get_option( 'dbs_adjust_shipping_for_vat', 0 );
		$original_cost = $total_cost;

		$label = '';

		if ( $adjust_shipping_for_vat && $total_cost > 0 ) {
			// Výpočet netto a finální brutto pro label
			$net = ceil( ($total_cost / 1.21) * 100 ) / 100;
			$final_display_price = round($net * 1.21);
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log("DBS: Brutto: $total_cost, Netto: $net, Zobrazeno: $final_display_price");
			}
			$label = sprintf( __('<span class="dbs_shipping-rule">%s: %d Kč</span>', 'distance-shipping'), $rule->rule_name, $final_display_price );
			// Pro WooCommerce výpočet použijeme přesný netto
			$total_cost = $net;
		} else {
			$final_display_price = round($total_cost);
			$label = sprintf( __('<span class="dbs_shipping-rule">%s: %d Kč</span>', 'distance-shipping'), $rule->rule_name, $final_display_price );
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log("DBS: Brutto (bez úpravy): $total_cost, Zobrazeno: $final_display_price");
			}
		}

		$formatted_distance = dbs_format_distance( $distance );

		$destination_address_used = null;
		$destination_address_original = null;
		if ( function_exists( 'WC' ) && WC() && WC()->session ) {
			$destination_address_used = WC()->session->get( 'dbs_destination_address_used' );
			$destination_address_original = WC()->session->get( 'dbs_destination_address_original' );
		}

		$rate_data = [
			'id'       => $this->id . '_rule_' . $rule->id,
			'label'    => $label,
			'cost'     => $total_cost,
			'calc_tax' => 'per_order',
			'tax_status' => 'taxable',
			'meta_data' => [
				'distance' => $distance,
				'distance_unit' => 'km',
				'rule_id' => $rule->id,
				'formatted_distance' => $formatted_distance,
				'price_includes_tax' => true,
			],
		];

		if ( $destination_address_used ) {
			$rate_data['meta_data']['destination_address_used'] = $destination_address_used;
			if ( $destination_address_original && $destination_address_used !== $destination_address_original ) {
				$rate_data['meta_data']['destination_address_original'] = $destination_address_original;
				$rate_data['meta_data']['address_standardized'] = true;
			}
		}

		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Shipping rate calculated - Base: ' . $base_rate . ', Per km: ' . $per_km_rate . ', Distance: ' . $distance . ', Netto: ' . $total_cost . ', Label: ' . $label );
		}

		return $rate_data;
	}

	/**
	 * Calculate precise net price to avoid floating-point rounding errors.
	 *
	 * @param float $brutto_price The brutto price to convert.
	 * @return float The calculated net price.
	 */
	private function calculate_precise_net_price( float $brutto_price ): float {
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
				if ( get_option( 'dbs_debug_mode', 0 ) ) {
					error_log( 'DBS: Warning - Could not achieve exact brutto match. Target: ' . $brutto_price . ', Achieved: ' . $final_brutto );
				}
			}
		}
		
		return $net_price;
	}

	/**
	 * Add fallback shipping rate.
	 *
	 * @return void
	 */
	private function add_fallback_rate(): void {
		$fallback_rate = (float) get_option( 'dbs_fallback_rate', 10 );

		if ( $fallback_rate > 0 ) {
			// Check if VAT adjustment is enabled
			$adjust_shipping_for_vat = get_option( 'dbs_adjust_shipping_for_vat', 0 );
			$original_rate = $fallback_rate;
			
			if ( $adjust_shipping_for_vat && $fallback_rate > 0 ) {
				// Use precise VAT calculation to avoid floating-point errors
				$fallback_rate = $this->calculate_precise_net_price( $fallback_rate );
				
				// Debug logging
				if ( get_option( 'dbs_debug_mode', 0 ) ) {
					error_log( 'DBS: VAT adjustment applied to fallback rate - Original: ' . $original_rate . ', Adjusted: ' . $fallback_rate );
				}
			}

			$this->add_rate( [
				'id'       => $this->id . '_fallback',
				'label'    => sprintf( __('<span class="dbs-shipping-rule-nazev">%s</span>', 'distance-shipping'), __( 'Standard Shipping', 'distance-shipping' ) ),
				'cost'     => $fallback_rate,
				'calc_tax' => 'per_order',
				'tax_status' => 'taxable', // Explicitně nastavíme jako taxable
				'meta_data' => [
					'fallback' => true,
					'price_includes_tax' => true, // Označíme, že cena je již včetně DPH
				],
			] );
			
			// Debug logging
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log( 'DBS: Fallback shipping rate added - Cost: ' . $fallback_rate );
			}
		}
	}

	/**
	 * Check if shipping method is available.
	 *
	 * @param array $package Shipping package.
	 * @return bool True if available.
	 */
	public function is_available( $package ): bool {
		$is_available = parent::is_available( $package );

		// Additional availability checks can be added here.
		return apply_filters( 'dbs_shipping_method_is_available', $is_available, $package, $this );
	}

	/**
	 * Add shipping calculator info.
	 *
	 * @return void
	 */
	public function add_shipping_calculator_info(): void {
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

		// echo '<div class="dbs-shipping-info">';
		// echo '<p><strong>' . esc_html__( 'Distance Based Shipping', 'distance-shipping' ) . '</strong></p>';
		// echo '<p>' . esc_html__( 'Shipping rates are calculated based on distance from our stores to your address.', 'distance-shipping' ) . '</p>';
		// if ( count( $stores ) > 1 ) {
		// 	echo '<p>' . esc_html__( 'We will automatically select the nearest store for shipping calculation.', 'distance-shipping' ) . '</p>';
		// }
		//
		// $destination_address_used = null;
		// $destination_address_original = null;
		// if ( function_exists( 'WC' ) && WC() && WC()->session ) {
		// 	$destination_address_used = WC()->session->get( 'dbs_destination_address_used' );
		// 	$destination_address_original = WC()->session->get( 'dbs_destination_address_original' );
		// }
		// if ( $destination_address_used && $destination_address_original && $destination_address_used !== $destination_address_original ) {
		// 	echo '<div class="dbs-address-info" style="margin-top: 10px; padding: 8px; background: #e7f3ff; border-left: 3px solid #0073aa; border-radius: 3px;">';
		// 	echo '<p><strong>' . esc_html__( 'Address Standardization', 'distance-shipping' ) . '</strong></p>';
		// 	echo '<p><small>' . esc_html__( 'Original address:', 'distance-shipping' ) . ' ' . esc_html( $destination_address_original ) . '</small></p>';
		// 	echo '<p><small>' . esc_html__( 'Standardized address used:', 'distance-shipping' ) . ' ' . esc_html( $destination_address_used ) . '</small></p>';
		// 	echo '</div>';
		// }
		// echo '</div>';
	}

	/**
	 * Enhance shipping method title with distance info.
	 *
	 * @param string $title Method title.
	 * @param object $method Shipping method object.
	 * @return string Enhanced title.
	 */
	public function enhance_shipping_method_title( string $title, $method ): string {
		if ( $method->id === $this->id ) {
			// Add distance info if available - check for null session
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
	 * Get shipping method admin options HTML.
	 *
	 * @return string Admin options HTML.
	 */
	public function get_admin_options_html() {
		ob_start();
		?>
		<h2><?php echo esc_html( $this->method_title ); ?></h2>
		<p><?php echo esc_html( $this->method_description ); ?></p>
		
		<div class="notice notice-info">
			<p>
				<?php
				printf(
					/* translators: %s: Plugin settings URL */
					esc_html__( 'Nakonfigurujte výpočet vzdálenosti a pravidla pro dopravu v %s.', 'distance-shipping' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=distance-shipping' ) ) . '">' . esc_html__( 'nastavení pluginu', 'distance-shipping' ) . '</a>'
				);
				?>
			</p>
		</div>

		<?php echo $this->get_admin_options(); ?>
		<?php
		return ob_get_clean();
	}
	
	/**
	 * Generate the admin options form for the shipping method.
	 *
	 * @return string HTML form fields.
	 */
	public function get_admin_options() {
		ob_start();
		?>
		<table class="form-table">
			<?php
			// Generate the fields from instance_form_fields
			$this->generate_settings_html( $this->get_instance_form_fields(), false );
			?>
		</table>
		<?php
		return ob_get_clean();
	}
}
<?php
/**
 * Funkce pro práci s hmotností a rozměry produktů.
 *
 * Soubor: includes/functions/product-functions.php
 *
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Získá celkovou hmotnost balíčku.
 *
 * @param array $package WooCommerce balíček.
 * @param int|null $product_id ID produktu pro product detail page (volitelné).
 * @return float Celková hmotnost v kg.
 */
function dbs_get_package_weight( array $package, ?int $product_id = null ): float {
	$total_weight = 0;

	// Debug logging
	if ( get_option( 'dbs_debug_mode', 0 ) ) {
		error_log( 'DBS: Package weight calculation started' );
		error_log( 'DBS: Package contents count: ' . ( ! empty( $package['contents'] ) ? count( $package['contents'] ) : 0 ) );
	}

	// Pokud je specifikován product_id, použijeme pouze tento produkt
	if ( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product ) {
			$product_weight = $product->get_weight();
			if ( $product_weight ) {
				if ( get_option( 'dbs_debug_mode', 0 ) ) {
					error_log( 'DBS: Single product weight: ' . $product_weight . ' kg' );
				}
				return (float) $product_weight;
			}
		}
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Single product weight: 0 kg (no weight set)' );
		}
		return 0;
	}

	if ( ! empty( $package['contents'] ) ) {
		foreach ( $package['contents'] as $item ) {
			$product = $item['data'];
			$quantity = $item['quantity'];
			
			// Získat hmotnost produktu
			$product_weight = $product->get_weight();
			if ( $product_weight ) {
				$item_weight = (float) $product_weight * $quantity;
				$total_weight += $item_weight;
				
				if ( get_option( 'dbs_debug_mode', 0 ) ) {
					error_log( 'DBS: Item weight - Product: ' . $product->get_name() . 
							   ', Weight: ' . $product_weight . ' kg' .
							   ', Quantity: ' . $quantity .
							   ', Total item weight: ' . $item_weight . ' kg' );
				}
			} else {
				if ( get_option( 'dbs_debug_mode', 0 ) ) {
					error_log( 'DBS: Item weight - Product: ' . $product->get_name() . 
							   ', Weight: 0 kg (no weight set)' .
							   ', Quantity: ' . $quantity );
				}
			}
		}
	} else {
		// Fallback na WC_Cart pokud balíček neobsahuje contents
		if ( WC()->cart ) {
			$total_weight = WC()->cart->get_cart_contents_weight();
			if ( get_option( 'dbs_debug_mode', 0 ) ) {
				error_log( 'DBS: Using WC_Cart fallback weight: ' . $total_weight . ' kg' );
			}
		}
	}

	if ( get_option( 'dbs_debug_mode', 0 ) ) {
		error_log( 'DBS: Total package weight: ' . $total_weight . ' kg' );
	}

	return (float) $total_weight;
}

/**
 * Získá celkové rozměry balíčku.
 *
 * @param array $package WooCommerce balíček.
 * @param int|null $product_id ID produktu pro product detail page (volitelné).
 * @return array Rozměry balíčku ['length', 'width', 'height'].
 */
function dbs_get_package_dimensions( array $package, ?int $product_id = null ): array {
	$dimensions = [
		'length' => 0,
		'width'  => 0,
		'height' => 0,
	];

	// Pokud je specifikován product_id, použijeme pouze tento produkt
	if ( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product ) {
			$product_length = $product->get_length();
			$product_width = $product->get_width();
			$product_height = $product->get_height();
			
			if ( $product_length ) {
				$dimensions['length'] = (float) $product_length;
			}
			if ( $product_width ) {
				$dimensions['width'] = (float) $product_width;
			}
			if ( $product_height ) {
				$dimensions['height'] = (float) $product_height;
			}
		}
		return $dimensions;
	}

	if ( ! empty( $package['contents'] ) ) {
		$max_length = 0;
		$max_width = 0;
		$total_height = 0;

		foreach ( $package['contents'] as $item ) {
			$product = $item['data'];
			$quantity = $item['quantity'];
			
			// Získat rozměry produktu
			$product_length = $product->get_length();
			$product_width = $product->get_width();
			$product_height = $product->get_height();
			
			if ( $product_length ) {
				$max_length = max( $max_length, (float) $product_length );
			}
			if ( $product_width ) {
				$max_width = max( $max_width, (float) $product_width );
			}
			if ( $product_height ) {
				$total_height += (float) $product_height * $quantity;
			}
		}

		$dimensions['length'] = $max_length;
		$dimensions['width'] = $max_width;
		$dimensions['height'] = $total_height;
	}

	return $dimensions;
}

/**
 * Zkontroluje, zda hmotnost balíčku vyhovuje pravidlu.
 *
 * @param object $rule Dopravní pravidlo.
 * @param array  $package WooCommerce balíček.
 * @return bool True pokud hmotnost vyhovuje pravidlu.
 */
function dbs_check_weight_condition( object $rule, array $package ): bool {
	// Debug logging
	if ( get_option( 'dbs_debug_mode', 0 ) ) {
		$package_weight = dbs_get_package_weight( $package );
		error_log( 'DBS: Weight check - Rule: ' . $rule->rule_name . 
				   ', Weight min: ' . $rule->weight_min . 
				   ', Weight max: ' . $rule->weight_max . 
				   ', Package weight: ' . $package_weight );
	}

	// Pokud nejsou nastavena pravidla pro hmotnost, pravidlo se aplikuje
	if ( $rule->weight_min <= 0 && $rule->weight_max <= 0 ) {
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Weight check - No weight conditions, rule applies' );
		}
		return true;
	}

	$package_weight = dbs_get_package_weight( $package );
	
	// Kontrola minimální hmotnosti
	if ( $rule->weight_min > 0 && $package_weight < $rule->weight_min ) {
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Weight check - Package weight (' . $package_weight . ') below minimum (' . $rule->weight_min . '), rule rejected' );
		}
		return false;
	}
	
	// Kontrola maximální hmotnosti
	if ( $rule->weight_max > 0 && $package_weight > $rule->weight_max ) {
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Weight check - Package weight (' . $package_weight . ') above maximum (' . $rule->weight_max . '), rule rejected' );
		}
		return false;
	}
	
	if ( get_option( 'dbs_debug_mode', 0 ) ) {
		error_log( 'DBS: Weight check - Package weight (' . $package_weight . ') within range, rule applies' );
	}
	
	return true;
}

/**
 * Zkontroluje, zda rozměry balíčku vyhovují pravidlu.
 *
 * @param object $rule Dopravní pravidlo.
 * @param array  $package WooCommerce balíček.
 * @return bool True pokud rozměry vyhovují pravidlu.
 */
function dbs_check_dimensions_condition( object $rule, array $package ): bool {
	// Debug logging
	if ( get_option( 'dbs_debug_mode', 0 ) ) {
		error_log( 'DBS: Dimensions check - Rule: ' . $rule->rule_name . 
				   ', Length: ' . $rule->length_min . '-' . $rule->length_max . 
				   ', Width: ' . $rule->width_min . '-' . $rule->width_max . 
				   ', Height: ' . $rule->height_min . '-' . $rule->height_max );
	}

	// Pokud nejsou nastavena pravidla pro rozměry, pravidlo se aplikuje
	if ( $rule->length_min <= 0 && $rule->length_max <= 0 &&
		 $rule->width_min <= 0 && $rule->width_max <= 0 &&
		 $rule->height_min <= 0 && $rule->height_max <= 0 ) {
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Dimensions check - No dimension conditions, rule applies' );
		}
		return true;
	}

	$dimensions = dbs_get_package_dimensions( $package );
	
	if ( get_option( 'dbs_debug_mode', 0 ) ) {
		error_log( 'DBS: Dimensions check - Package dimensions: ' . 
				   'L=' . $dimensions['length'] . 
				   ', W=' . $dimensions['width'] . 
				   ', H=' . $dimensions['height'] );
	}
	
	$conditions_met = [];
	
	// Kontrola délky
	if ( $rule->length_min > 0 || $rule->length_max > 0 ) {
		$length_ok = true;
		if ( $rule->length_min > 0 && $dimensions['length'] < $rule->length_min ) {
			$length_ok = false;
		}
		if ( $rule->length_max > 0 && $dimensions['length'] > $rule->length_max ) {
			$length_ok = false;
		}
		$conditions_met[] = $length_ok;
		
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Dimensions check - Length condition: ' . ( $length_ok ? 'OK' : 'FAIL' ) );
		}
	}
	
	// Kontrola šířky
	if ( $rule->width_min > 0 || $rule->width_max > 0 ) {
		$width_ok = true;
		if ( $rule->width_min > 0 && $dimensions['width'] < $rule->width_min ) {
			$width_ok = false;
		}
		if ( $rule->width_max > 0 && $dimensions['width'] > $rule->width_max ) {
			$width_ok = false;
		}
		$conditions_met[] = $width_ok;
		
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Dimensions check - Width condition: ' . ( $width_ok ? 'OK' : 'FAIL' ) );
		}
	}
	
	// Kontrola výšky
	if ( $rule->height_min > 0 || $rule->height_max > 0 ) {
		$height_ok = true;
		if ( $rule->height_min > 0 && $dimensions['height'] < $rule->height_min ) {
			$height_ok = false;
		}
		if ( $rule->height_max > 0 && $dimensions['height'] > $rule->height_max ) {
			$height_ok = false;
		}
		$conditions_met[] = $height_ok;
		
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Dimensions check - Height condition: ' . ( $height_ok ? 'OK' : 'FAIL' ) );
		}
	}
	
	// Pokud nejsou žádné podmínky pro rozměry
	if ( empty( $conditions_met ) ) {
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Dimensions check - No dimension conditions, rule applies' );
		}
		return true;
	}
	
	// Aplikace operátoru (AND/OR)
	$operator = $rule->dimensions_operator ?? 'AND';
	
	if ( $operator === 'AND' ) {
		$result = ! in_array( false, $conditions_met, true );
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Dimensions check - AND operator result: ' . ( $result ? 'true' : 'false' ) );
		}
		return $result;
	} else { // OR
		$result = in_array( true, $conditions_met, true );
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Dimensions check - OR operator result: ' . ( $result ? 'true' : 'false' ) );
		}
		return $result;
	}
}

/**
 * Zkontroluje, zda balíček vyhovuje všem podmínkám pravidla.
 *
 * @param object $rule Dopravní pravidlo.
 * @param array  $package WooCommerce balíček.
 * @return bool True pokud balíček vyhovuje všem podmínkám.
 */
function dbs_check_all_conditions( object $rule, array $package ): bool {
	// Kontrola hmotnosti
	$weight_ok = dbs_check_weight_condition( $rule, $package );
	
	// Kontrola rozměrů
	$dimensions_ok = dbs_check_dimensions_condition( $rule, $package );
	
	// Zkontrolovat, které podmínky jsou skutečně definované
	$has_weight_conditions = ( isset( $rule->weight_min ) && $rule->weight_min > 0 ) || 
	                        ( isset( $rule->weight_max ) && $rule->weight_max > 0 );
	
	$has_dimension_conditions = ( isset( $rule->length_min ) && $rule->length_min > 0 ) ||
	                           ( isset( $rule->length_max ) && $rule->length_max > 0 ) ||
	                           ( isset( $rule->width_min ) && $rule->width_min > 0 ) ||
	                           ( isset( $rule->width_max ) && $rule->width_max > 0 ) ||
	                           ( isset( $rule->height_min ) && $rule->height_min > 0 ) ||
	                           ( isset( $rule->height_max ) && $rule->height_max > 0 );
	
	// Aplikace operátoru pro hmotnost a rozměry
	$weight_operator = $rule->weight_operator ?? 'AND';
	
	$physical_conditions_ok = true; // Default pro případ, že nejsou žádné podmínky
	
	if ( $has_weight_conditions && $has_dimension_conditions ) {
		// Obě podmínky jsou definované - použij operátor
		if ( $weight_operator === 'OR' ) {
			$physical_conditions_ok = $weight_ok || $dimensions_ok;
		} else {
			$physical_conditions_ok = $weight_ok && $dimensions_ok;
		}
	} elseif ( $has_weight_conditions ) {
		// Pouze váhové podmínky - kontroluj pouze váhu
		$physical_conditions_ok = $weight_ok;
	} elseif ( $has_dimension_conditions ) {
		// Pouze rozměrové podmínky - kontroluj pouze rozměry
		$physical_conditions_ok = $dimensions_ok;
	}
	// Pokud nejsou žádné fyzické podmínky, physical_conditions_ok zůstává true
	
	if ( get_option( 'dbs_debug_mode', 0 ) ) {
		error_log( 'DBS: All conditions check - Rule: ' . $rule->rule_name . 
				   ', Weight OK: ' . ( $weight_ok ? 'true' : 'false' ) . 
				   ' (has conditions: ' . ( $has_weight_conditions ? 'true' : 'false' ) . ')' .
				   ', Dimensions OK: ' . ( $dimensions_ok ? 'true' : 'false' ) . 
				   ' (has conditions: ' . ( $has_dimension_conditions ? 'true' : 'false' ) . ')' .
				   ', Operator: ' . $weight_operator );
		
		error_log( 'DBS: Physical conditions result: ' . ( $physical_conditions_ok ? 'true' : 'false' ) );
	}
	
	return $physical_conditions_ok;
}

/**
 * Získá informace o hmotnosti a rozměrech balíčku pro debug.
 *
 * @param array $package WooCommerce balíček.
 * @param int|null $product_id ID produktu pro product detail page (volitelné).
 * @return array Informace o balíčku.
 */
function dbs_get_package_info( array $package, ?int $product_id = null ): array {
	$weight = dbs_get_package_weight( $package, $product_id );
	$dimensions = dbs_get_package_dimensions( $package, $product_id );
	
	return [
		'weight' => $weight,
		'dimensions' => $dimensions,
		'weight_formatted' => sprintf( '%.3f kg', $weight ),
		'dimensions_formatted' => sprintf( '%.2f × %.2f × %.2f cm', $dimensions['length'], $dimensions['width'], $dimensions['height'] ),
	];
}

/**
 * Zaloguje informace o balíčku pro debug.
 *
 * @param array $package WooCommerce balíček.
 * @return void
 */
function dbs_log_package_info( array $package ): void {
	if ( ! get_option( 'dbs_debug_mode', 0 ) ) {
		return;
	}

	$info = dbs_get_package_info( $package );
	
	$log_data = [
		'weight' => $info['weight'],
		'dimensions' => $info['dimensions'],
		'weight_formatted' => $info['weight_formatted'],
		'dimensions_formatted' => $info['dimensions_formatted'],
		'timestamp' => current_time( 'mysql' ),
	];

	dbs_log_debug( 'Package info: ' . wp_json_encode( $log_data ) );
} 
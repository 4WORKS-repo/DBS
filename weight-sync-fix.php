<?php
/**
 * Váhová oprava pro správné počítání produktů
 * 
 * Opravuje problém kdy se nerespektuje množství produktů při výpočtu celkové hmotnosti
 * Příklad: 3kg produkt × 3 kusy = 9kg (ne 3kg)
 * 
 * @package DistanceBasedShipping
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Vylepšený výpočet hmotnosti balíčku s respektováním množství
 * 
 * @param array $package WooCommerce package
 * @return float Celková hmotnost v kg
 */
function dbs_get_package_weight_improved( $package ) {
    $total_weight = 0;
    
    // Debug information
    if ( get_option( 'dbs_debug_mode', 0 ) ) {
        error_log( 'DBS Weight Fix: Starting weight calculation for package' );
    }
    
    // Kontrola zda package obsahuje contents
    if ( ! isset( $package['contents'] ) || empty( $package['contents'] ) ) {
        if ( get_option( 'dbs_debug_mode', 0 ) ) {
            error_log( 'DBS Weight Fix: Package contents are empty' );
        }
        return 0;
    }
    
    // Iterace přes všechny produkty v package
    foreach ( $package['contents'] as $item_key => $item ) {
        $product = null;
        $quantity = 1;
        
        // Získání produktu a množství různými způsoby
        if ( isset( $item['data'] ) && is_object( $item['data'] ) ) {
            $product = $item['data'];
            $quantity = isset( $item['quantity'] ) ? (int) $item['quantity'] : 1;
        } elseif ( isset( $item['product_id'] ) ) {
            $product = wc_get_product( $item['product_id'] );
            $quantity = isset( $item['quantity'] ) ? (int) $item['quantity'] : 1;
        }
        
        if ( ! $product || ! is_object( $product ) ) {
            if ( get_option( 'dbs_debug_mode', 0 ) ) {
                error_log( 'DBS Weight Fix: Product not found for item: ' . $item_key );
            }
            continue;
        }
        
        // Získání hmotnosti produktu
        $product_weight = $product->get_weight();
        
        // Konverze na float a fallback na 0
        $product_weight = $product_weight ? (float) $product_weight : 0;
        
        // Výpočet hmotnosti pro dané množství
        $item_total_weight = $product_weight * $quantity;
        $total_weight += $item_total_weight;
        
        // Debug informace
        if ( get_option( 'dbs_debug_mode', 0 ) ) {
            error_log( sprintf( 
                'DBS Weight Fix: Product %s (ID: %d) - Weight: %skg × Quantity: %d = %skg',
                $product->get_name(),
                $product->get_id(),
                $product_weight,
                $quantity,
                $item_total_weight
            ) );
        }
    }
    
    // Debug celkové hmotnosti
    if ( get_option( 'dbs_debug_mode', 0 ) ) {
        error_log( 'DBS Weight Fix: Total calculated weight: ' . $total_weight . 'kg' );
    }
    
    return $total_weight;
}

/**
 * Vylepšená funkce pro vytvoření hash košíku včetně hmotnosti a množství
 * 
 * @param array $package WooCommerce package
 * @return string Hash košíku
 */
function dbs_get_cart_hash_improved( $package ) {
    $cart_data = array();
    
    if ( isset( $package['contents'] ) && ! empty( $package['contents'] ) ) {
        foreach ( $package['contents'] as $item_key => $item ) {
            $product_id = isset( $item['product_id'] ) ? $item['product_id'] : 0;
            $variation_id = isset( $item['variation_id'] ) ? $item['variation_id'] : 0;
            $quantity = isset( $item['quantity'] ) ? $item['quantity'] : 1;
            
            // Získání hmotnosti produktu
            $product_weight = 0;
            if ( isset( $item['data'] ) && is_object( $item['data'] ) ) {
                $product_weight = $item['data']->get_weight() ?: 0;
            } elseif ( $product_id ) {
                $product = wc_get_product( $product_id );
                if ( $product ) {
                    $product_weight = $product->get_weight() ?: 0;
                }
            }
            
            $cart_data[] = array(
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'quantity' => $quantity,
                'weight' => $product_weight,
                'total_weight' => $product_weight * $quantity
            );
        }
    }
    
    // Přidání informací o celkové hodnotě košíku
    if ( isset( $package['contents_cost'] ) ) {
        $cart_data['contents_cost'] = $package['contents_cost'];
    }
    
    return md5( serialize( $cart_data ) );
}

/**
 * Vylepšená kontrola hmotnostních podmínek s respektováním množství
 * 
 * @param object $rule Shipping rule
 * @param array $package WooCommerce package
 * @return bool True pokud podmínka je splněna
 */
function dbs_check_weight_condition_improved( $rule, $package ) {
    // Použití vylepšené funkce pro výpočet hmotnosti
    $package_weight = dbs_get_package_weight_improved( $package );
    
    $weight_min = isset( $rule->weight_min ) ? (float) $rule->weight_min : 0;
    $weight_max = isset( $rule->weight_max ) ? (float) $rule->weight_max : 0;
    
    // Debug informace
    if ( get_option( 'dbs_debug_mode', 0 ) ) {
        error_log( sprintf( 
            'DBS Weight Fix: Weight condition check - Package: %skg, Rule min: %skg, Rule max: %skg',
            $package_weight,
            $weight_min,
            $weight_max
        ) );
    }
    
    // Pokud nejsou nastaveny hmotnostní limity, podmínka je splněna
    if ( $weight_min <= 0 && $weight_max <= 0 ) {
        return true;
    }
    
    // Kontrola minimální hmotnosti
    $min_ok = ( $weight_min <= 0 ) || ( $package_weight >= $weight_min );
    
    // Kontrola maximální hmotnosti
    $max_ok = ( $weight_max <= 0 ) || ( $package_weight <= $weight_max );
    
    $result = $min_ok && $max_ok;
    
    if ( get_option( 'dbs_debug_mode', 0 ) ) {
        error_log( sprintf( 
            'DBS Weight Fix: Weight condition result - Min OK: %s, Max OK: %s, Overall: %s',
            $min_ok ? 'true' : 'false',
            $max_ok ? 'true' : 'false',
            $result ? 'true' : 'false'
        ) );
    }
    
    return $result;
}

/**
 * Vylepšené cache handling pro košík
 * 
 * @param string $action Akce (add, update, remove)
 * @param mixed $cart_item_key Klíč položky košíku
 * @param mixed $values Hodnoty
 */
function dbs_improved_cart_update_handler( $action = 'update', $cart_item_key = null, $values = null ) {
    if ( get_option( 'dbs_debug_mode', 0 ) ) {
        error_log( 'DBS Weight Fix: Cart update detected - Action: ' . $action );
    }
    
    // Invalidace všech shipping cache
    dbs_invalidate_weight_cache();
    
    // Force refresh shipping options
    if ( function_exists( 'WC' ) && WC()->session ) {
        WC()->session->set( 'shipping_methods', array() );
        WC()->session->set( 'chosen_shipping_methods', array() );
    }
}

/**
 * Rozšířená invalidace cache pro váhové změny
 */
function dbs_invalidate_weight_cache() {
    if ( get_option( 'dbs_debug_mode', 0 ) ) {
        error_log( 'DBS Weight Fix: Invalidating weight-related cache' );
    }
    
    // Použití existující funkce pro základní invalidaci
    if ( function_exists( 'dbs_invalidate_all_cache' ) ) {
        dbs_invalidate_all_cache();
    }
    
    // Dodatečná invalidace pro váhové změny
    if ( function_exists( 'WC' ) && WC()->session ) {
        WC()->session->set( 'shipping_methods', array() );
        WC()->session->set( 'chosen_shipping_methods', array() );
    }
    
    // WordPress object cache
    wp_cache_flush();
}

/**
 * Aktivace váhové opravy
 */
function dbs_activate_weight_sync_fix() {
    if ( get_option( 'dbs_debug_mode', 0 ) ) {
        error_log( 'DBS: Weight sync fix activated' );
    }
    
    // Označit že oprava je aktivní
    update_option( 'dbs_weight_fix_active', true );
    
    // Přepsat původní funkce pomocí filters
    add_filter( 'dbs_get_package_weight', 'dbs_get_package_weight_improved', 10, 1 );
    add_filter( 'dbs_check_weight_condition', 'dbs_check_weight_condition_improved', 10, 2 );
    
    // Přidat hooks pro invalidaci cache při změnách košíku
    add_action( 'woocommerce_cart_item_removed', 'dbs_improved_cart_update_handler', 10, 2 );
    add_action( 'woocommerce_cart_item_restored', 'dbs_improved_cart_update_handler', 10, 2 );
    add_action( 'woocommerce_cart_item_set_quantity', 'dbs_improved_cart_update_handler', 10, 5 );
    add_action( 'woocommerce_cart_updated', 'dbs_improved_cart_update_handler', 10, 0 );
    
    // AJAX handler pro manuální invalidaci cache - používá existující implementaci v ajax-functions.php
    // Registrace AJAX handlerů je už hotová v ajax-functions.php
}

/**
 * AJAX handler pro invalidaci shipping cache - používá existující implementaci v ajax-functions.php
 * Duplikátní funkce odstraněna kvůli konfliktu s ajax-functions.php:832
 */

// Aktivace opravy
dbs_activate_weight_sync_fix(); 
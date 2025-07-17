<?php
/**
 * Checkout Functions
 * 
 * @package Distance_Based_Shipping
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Automaticky zobrazí kalkulátor dopravy na checkout stránce
 */
function dbs_display_checkout_calculator() {
    // Zkontroluj, zda jsme na checkout stránce a ne na cart stránce
    if (!is_checkout() || is_cart()) {
        return;
    }
    
    // Zkontroluj, zda je kalkulátor povolen
    if (!dbs_get_option('show_calculator_on_checkout', true)) {
        return;
    }
    
    // Získej uloženou adresu z session
    $saved_address = WC()->session->get('dbs_shipping_address');
    $saved_distance = WC()->session->get('dbs_shipping_distance');
    $saved_cost = WC()->session->get('dbs_shipping_cost');
    
    ?>
    <div id="dbs-checkout-calculator" class="dbs-calculator dbs-checkout-calculator">
        <div class="dbs-calculator-header">
            <h3><?php esc_html_e('Kalkulátor dopravy', 'distance-based-shipping'); ?></h3>
            <?php if ($saved_address): ?>
                <div class="dbs-saved-info">
                    <span class="dbs-saved-address"><?php echo esc_html($saved_address); ?></span>
                    <?php if ($saved_distance): ?>
                        <span class="dbs-saved-distance">(<?php echo esc_html($saved_distance); ?> km)</span>
                    <?php endif; ?>
                    <?php if ($saved_cost): ?>
                        <span class="dbs-saved-cost"><?php echo wc_price($saved_cost); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="dbs-calculator-form">
            <div class="dbs-form-group">
                <label for="dbs-checkout-address"><?php esc_html_e('Doručovací adresa', 'distance-based-shipping'); ?></label>
                <input type="text" id="dbs-checkout-address" class="dbs-address-input" 
                       placeholder="<?php esc_attr_e('Zadejte adresu pro výpočet dopravy', 'distance-based-shipping'); ?>"
                       value="<?php echo esc_attr($saved_address); ?>">
                <div class="dbs-address-suggestions" id="dbs-checkout-suggestions"></div>
            </div>
            
            <div class="dbs-form-group">
                <button type="button" id="dbs-checkout-calculate" class="dbs-calculate-btn">
                    <span class="dbs-btn-text"><?php esc_html_e('Vypočítat dopravu', 'distance-based-shipping'); ?></span>
                    <span class="dbs-btn-loading" style="display: none;">
                        <span class="dbs-spinner"></span>
                        <?php esc_html_e('Počítám...', 'distance-based-shipping'); ?>
                    </span>
                </button>
            </div>
        </div>
        
        <div class="dbs-calculator-result" id="dbs-checkout-result" style="display: none;">
            <div class="dbs-result-content">
                <div class="dbs-distance-info">
                    <span class="dbs-distance-label"><?php esc_html_e('Vzdálenost:', 'distance-based-shipping'); ?></span>
                    <span class="dbs-distance-value"></span>
                </div>
                <div class="dbs-cost-info">
                    <span class="dbs-cost-label"><?php esc_html_e('Cena dopravy:', 'distance-based-shipping'); ?></span>
                    <span class="dbs-cost-value"></span>
                </div>
                <div class="dbs-delivery-info">
                    <span class="dbs-delivery-label"><?php esc_html_e('Doba doručení:', 'distance-based-shipping'); ?></span>
                    <span class="dbs-delivery-value"></span>
                </div>
            </div>
            <div class="dbs-result-actions">
                <button type="button" class="dbs-apply-shipping-btn">
                    <?php esc_html_e('Aplikovat dopravu', 'distance-based-shipping'); ?>
                </button>
            </div>
        </div>
        
        <div class="dbs-calculator-error" id="dbs-checkout-error" style="display: none;">
            <div class="dbs-error-message"></div>
        </div>
    </div>
    <?php
}

/**
 * Přidá kalkulátor do checkout formuláře
 */
function dbs_add_checkout_calculator() {
    // Odstraněno:
    // add_action('woocommerce_checkout_before_customer_details', 'dbs_display_checkout_calculator');
}
add_action('init', 'dbs_add_checkout_calculator');

/**
 * Automaticky přepočítá dopravu při změně množství a adresy
 */
function dbs_auto_recalculate_shipping_on_quantity_change() {
    // Hook pro změnu množství v košíku
    add_action('woocommerce_cart_item_removed', 'dbs_trigger_shipping_recalculation', 10, 2);
    add_action('woocommerce_cart_item_restored', 'dbs_trigger_shipping_recalculation', 10, 2);
    add_action('woocommerce_cart_item_set_quantity', 'dbs_trigger_shipping_recalculation', 10, 2);
    
    // Hook pro změnu množství na checkout stránce
    add_action('woocommerce_checkout_update_order_review', 'dbs_trigger_shipping_recalculation_checkout', 10);
    
    // Hook pro změnu množství v AJAX
    add_action('wp_ajax_woocommerce_update_order_review', 'dbs_trigger_shipping_recalculation_ajax', 5);
    add_action('wp_ajax_nopriv_woocommerce_update_order_review', 'dbs_trigger_shipping_recalculation_ajax', 5);
    
    // Hook pro změnu adresy
    add_action('woocommerce_checkout_update_order_review', 'dbs_trigger_shipping_recalculation_address', 5);
    
    // Hook pro změnu adresy v AJAX
    add_action('wp_ajax_woocommerce_update_order_review', 'dbs_trigger_shipping_recalculation_address_ajax', 5);
    add_action('wp_ajax_nopriv_woocommerce_update_order_review', 'dbs_trigger_shipping_recalculation_address_ajax', 5);
    
    // Hook pro změnu množství v košíku (cart update)
    add_action('woocommerce_cart_updated', 'dbs_trigger_shipping_recalculation_cart_updated', 10);
    
    // Hook pro změnu množství při AJAX cart update
    add_action('wp_ajax_woocommerce_update_cart', 'dbs_trigger_shipping_recalculation_cart_ajax', 5);
    add_action('wp_ajax_nopriv_woocommerce_update_cart', 'dbs_trigger_shipping_recalculation_cart_ajax', 5);
    
    // Hook pro změnu množství při cart item update
    add_action('woocommerce_cart_item_updated', 'dbs_trigger_shipping_recalculation_cart_item', 10, 3);
    
    // Hook pro sledování změn cart total
    add_action('woocommerce_cart_updated', 'dbs_trigger_shipping_recalculation_cart_total', 5);
    
    // Hook pro uložení adresy z cart formuláře
    add_action('woocommerce_cart_updated', 'dbs_save_cart_shipping_address', 5);
}
add_action('init', 'dbs_auto_recalculate_shipping_on_quantity_change');

/**
 * Invaliduje všechny DBS cache (session + transients)
 */
function dbs_invalidate_all_cache() {
    // Clear WooCommerce session cache
    if ( function_exists( 'WC' ) && WC() && WC()->session ) {
        WC()->session->__unset( 'shipping_for_package_0' );
        WC()->session->__unset( 'shipping_for_package_1' );
        WC()->session->__unset( 'shipping_for_package_2' );
        WC()->session->__unset( 'shipping_for_package_3' );
        WC()->session->__unset( 'shipping_for_package_4' );
        WC()->session->__unset( 'shipping_for_package_5' );
        WC()->session->__unset( 'dbs_shipping_distance' );
        WC()->session->__unset( 'dbs_shipping_cost' );
        WC()->session->__unset( 'dbs_shipping_method' );
        WC()->session->__unset( 'dbs_applied_shipping_rate' );
    }
    
    // Clear WordPress transients (DBS cache)
    global $wpdb;
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_dbs_shipping_%'
        )
    );
    
    if (get_option('dbs_debug_mode', 0)) {
        error_log('DBS: All cache invalidated (session + transients)');
    }
}

/**
 * Spustí přepočet dopravy při změně množství v košíku
 */
function dbs_trigger_shipping_recalculation($cart_item_key = null, $cart_item = null) {
    // Invalidate all cache when cart items change
    if ( WC()->cart ) {
        // Force shipping recalculation
        WC()->cart->calculate_shipping();
        
        // Clear all cache
        dbs_invalidate_all_cache();
        
        if (get_option('dbs_debug_mode', 0)) {
            error_log('DBS: Shipping cache invalidated due to cart item change');
        }
    }
}

/**
 * Spustí přepočet dopravy při změně množství na checkout stránce
 */
function dbs_trigger_shipping_recalculation_checkout() {
    // Invalidate all cache on checkout updates
    if ( WC()->cart ) {
        // Force shipping recalculation
        WC()->cart->calculate_shipping();
        
        // Clear all cache
        dbs_invalidate_all_cache();
        
        if (get_option('dbs_debug_mode', 0)) {
            error_log('DBS: Shipping cache invalidated on checkout update');
        }
    }
}

/**
 * Spustí přepočet dopravy při AJAX požadavku
 */
function dbs_trigger_shipping_recalculation_ajax() {
    // Invalidate all cache for AJAX requests
    if ( WC()->cart ) {
        // Force shipping recalculation
        WC()->cart->calculate_shipping();
        
        // Clear all cache
        dbs_invalidate_all_cache();
        
        if (get_option('dbs_debug_mode', 0)) {
            error_log('DBS: Shipping cache invalidated due to AJAX request');
        }
    }
}

/**
 * Spustí přepočet dopravy při aktualizaci košíku
 */
function dbs_trigger_shipping_recalculation_cart_updated() {
    // Invalidate all cache
    if ( WC()->cart ) {
        // Force shipping recalculation
        WC()->cart->calculate_shipping();
        
        // Clear all cache
        dbs_invalidate_all_cache();
        
        // Force WooCommerce to recalculate shipping rates
        WC()->cart->get_shipping_packages();
        
        if (get_option('dbs_debug_mode', 0)) {
            error_log('DBS: Shipping cache invalidated due to cart update - Cart total: ' . WC()->cart->get_cart_contents_total());
        }
    }
}

/**
 * Spustí přepočet dopravy při AJAX aktualizaci košíku
 */
function dbs_trigger_shipping_recalculation_cart_ajax() {
    // Invalidate all cache for AJAX cart updates
    if ( WC()->cart ) {
        // Force shipping recalculation
        WC()->cart->calculate_shipping();
        
        // Clear all cache
        dbs_invalidate_all_cache();
        
        if (get_option('dbs_debug_mode', 0)) {
            error_log('DBS: Shipping cache invalidated due to cart AJAX update');
        }
    }
}

/**
 * Spustí přepočet dopravy při změně položky v košíku
 */
function dbs_trigger_shipping_recalculation_cart_item($cart_item_key, $quantity, $old_quantity) {
    // Invalidate all cache when quantity changes significantly
    if ( WC()->cart ) {
        // Force shipping recalculation
        WC()->cart->calculate_shipping();
        
        // Clear all cache
        dbs_invalidate_all_cache();
        
        // Force WooCommerce to recalculate shipping rates
        WC()->cart->get_shipping_packages();
        
        if (get_option('dbs_debug_mode', 0)) {
            error_log('DBS: Shipping cache invalidated due to cart item update - Product: ' . $cart_item_key . ', Quantity: ' . $quantity . ' (was: ' . $old_quantity . ') - Cart total: ' . WC()->cart->get_cart_contents_total());
        }
    }
}

/**
 * Uloží shipping adresu z cart formuláře
 */
function dbs_save_cart_shipping_address() {
    // Zkus získat adresu z cart formuláře
    $cart_address = sanitize_text_field($_POST['dbs_cart_address'] ?? '');
    
    if ($cart_address) {
        WC()->session->set('dbs_cart_shipping_address', $cart_address);
        
        if (get_option('dbs_debug_mode', 0)) {
            error_log('DBS: Cart shipping address saved: ' . $cart_address);
        }
    }
}

/**
 * Získá aktuální shipping adresu
 */
function dbs_get_current_shipping_address() {
    // Zkus získat adresu z checkout formuláře
    $billing_address = sanitize_text_field($_POST['billing_address_1'] ?? '');
    $billing_city = sanitize_text_field($_POST['billing_city'] ?? '');
    $billing_postcode = sanitize_text_field($_POST['billing_postcode'] ?? '');
    
    if ($billing_address && $billing_city && $billing_postcode) {
        return trim($billing_address . ', ' . $billing_city . ', ' . $billing_postcode);
    }
    
    // Zkus získat uloženou adresu ze session
    $saved_address = WC()->session->get('dbs_shipping_address');
    if ($saved_address) {
        return $saved_address;
    }
    
    // Zkus získat adresu z cart session (pro cart stránku)
    $cart_address = WC()->session->get('dbs_cart_shipping_address');
    if ($cart_address) {
        return $cart_address;
    }
    
    return null;
}

/**
 * Vypočítá a aplikuje shipping pro danou adresu
 */
function dbs_calculate_and_apply_shipping($address) {
    if (!$address) {
        return false;
    }
    
    // Vypočítej vzdálenost
    $distance = dbs_calculate_distance($address);
    if ($distance === false) {
        return false;
    }
    
    // Najdi vhodné pravidlo
    $rule = dbs_find_applicable_rule($distance);
    if (!$rule) {
        return false;
    }
    
    // Vypočítej cenu dopravy
    $shipping_cost = dbs_calculate_shipping_cost($rule->base_rate, $rule->per_km_rate, $distance);
    
    // Ulož do session
    WC()->session->set('dbs_shipping_distance', $distance);
    WC()->session->set('dbs_shipping_cost', $shipping_cost);
    WC()->session->set('dbs_shipping_method', 'distance_based');
    
    return true;
}

/**
 * Spustí přepočet dopravy při změně adresy
 */
function dbs_trigger_shipping_recalculation_address() {
    $address = dbs_get_current_shipping_address();
    
    if ($address) {
        // Zkontroluj, zda se adresa změnila
        $saved_address = WC()->session->get('dbs_shipping_address');
        if ($saved_address !== $address) {
            // Ulož novou adresu
            WC()->session->set('dbs_shipping_address', $address);
            
            // Vymaž staré shipping údaje
            WC()->session->__unset('dbs_shipping_distance');
            WC()->session->__unset('dbs_shipping_cost');
            WC()->session->__unset('dbs_shipping_method');
            
            // Přepočítej dopravu
            dbs_calculate_and_apply_shipping($address);
            
            if (get_option('dbs_debug_mode', 0)) {
                error_log('DBS: Shipping recalculated due to address change - Address: ' . $address);
            }
        }
    }
}

/**
 * Spustí přepočet dopravy při změně adresy v AJAX
 */
function dbs_trigger_shipping_recalculation_address_ajax() {
    $address = dbs_get_current_shipping_address();
    
    if ($address) {
        // Zkontroluj, zda se adresa změnila
        $saved_address = WC()->session->get('dbs_shipping_address');
        if ($saved_address !== $address) {
            // Ulož novou adresu
            WC()->session->set('dbs_shipping_address', $address);
            
            // Vymaž staré shipping údaje
            WC()->session->__unset('dbs_shipping_distance');
            WC()->session->__unset('dbs_shipping_cost');
            WC()->session->__unset('dbs_shipping_method');
            
            // Přepočítej dopravu
            dbs_calculate_and_apply_shipping($address);
            
            if (get_option('dbs_debug_mode', 0)) {
                error_log('DBS: Shipping recalculated due to address change via AJAX - Address: ' . $address);
            }
        }
    }
}

/**
 * Validuje adresu na checkoutu (používá existující funkci z shipping-functions.php)
 */
function dbs_validate_checkout_address_wrapper() {
    // Zkontroluj, zda jsme na checkout stránce a ne na cart stránce
    if (!is_checkout() || is_cart()) {
        return;
    }
    
    // Bezpečně zavolej validační funkci
    if (function_exists('dbs_validate_checkout_address')) {
        dbs_validate_checkout_address();
    } else {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('DBS: Funkce dbs_validate_checkout_address() není dostupná!');
        }
        return;
    }
    
    // Dodatečná logika pro checkout
    $address = sanitize_text_field($_POST['billing_address_1'] ?? '');
    $city = sanitize_text_field($_POST['billing_city'] ?? '');
    $postcode = sanitize_text_field($_POST['billing_postcode'] ?? '');
    
    if (empty($address) || empty($city) || empty($postcode)) {
        return;
    }
    
    $full_address = trim($address . ', ' . $city . ', ' . $postcode);
    
    // Zkontroluj, zda se adresa změnila
    $saved_address = WC()->session->get('dbs_shipping_address');
    if ($saved_address === $full_address) {
        return;
    }
    
    // Ulož novou adresu do session
    WC()->session->set('dbs_shipping_address', $full_address);
    
    // Vymaž staré shipping údaje
    WC()->session->__unset('dbs_shipping_distance');
    WC()->session->__unset('dbs_shipping_cost');
    WC()->session->__unset('dbs_shipping_method');
    
    // Odeber aplikovanou shipping sazbu
    dbs_remove_shipping_method();
}

/**
 * Automaticky aplikuje uloženou shipping sazbu na checkoutu
 */
function dbs_apply_saved_shipping_on_checkout() {
    // Zkontroluj, zda jsme na checkout stránce a ne na cart stránce
    if (!is_checkout() || is_cart()) {
        return;
    }
    
    $saved_method = WC()->session->get('dbs_shipping_method');
    $saved_cost = WC()->session->get('dbs_shipping_cost');
    
    if (!$saved_method || !$saved_cost) {
        return;
    }
    
    // Zkontroluj, zda je shipping metoda stále platná
    $available_methods = WC()->shipping()->get_shipping_methods();
    if (!isset($available_methods[$saved_method])) {
        return;
    }
    
    // Aplikuj shipping metodu
    $chosen_methods = WC()->session->get('chosen_shipping_methods', array());
    $chosen_methods[0] = $saved_method;
    WC()->session->set('chosen_shipping_methods', $chosen_methods);
    
    // Aktualizuj shipping náklady
    $packages = WC()->shipping()->get_packages();
    if (!empty($packages)) {
        $packages[0]['rates'][$saved_method]->cost = $saved_cost;
        WC()->session->set('shipping_for_package_0', $packages[0]);
    }
}

/**
 * Zobrazí informace o dopravě na checkout stránce (používá existující funkci z shipping-functions.php)
 */
function dbs_display_checkout_shipping_info_wrapper() {
    // Zkontroluj, zda jsme na checkout stránce a ne na cart stránce
    if (!is_checkout() || is_cart()) {
        return;
    }
    
    // Bezpečně zavolej funkci z shipping-functions.php
    if (function_exists('dbs_display_checkout_shipping_info')) {
        dbs_display_checkout_shipping_info();
    } else {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('DBS: Funkce dbs_display_checkout_shipping_info() není dostupná!');
        }
        return;
    }
}

/**
 * Přidá shipping informace do checkout formuláře
 */
function dbs_add_checkout_shipping_info() {
    add_action('woocommerce_checkout_after_customer_details', 'dbs_display_checkout_shipping_info_wrapper');
}
add_action('init', 'dbs_add_checkout_shipping_info');

/**
 * AJAX handler pro checkout kalkulátor
 */
function dbs_checkout_calculator_ajax() {
    check_ajax_referer('dbs_nonce', 'nonce');
    
    $address = sanitize_text_field($_POST['address'] ?? '');
    
    if (empty($address)) {
        wp_send_json_error(array(
            'message' => __('Zadejte prosím adresu.', 'distance-based-shipping')
        ));
    }
    
    // Získej nejbližší obchod
    $nearest_store = dbs_get_nearest_store($address);
    
    if (!$nearest_store) {
        wp_send_json_error(array(
            'message' => __('Nepodařilo se najít nejbližší obchod.', 'distance-based-shipping')
        ));
    }
    
    // Vypočti vzdálenost a cenu
    $distance = dbs_calculate_distance($nearest_store['address'], $address);
    $shipping_cost = dbs_calculate_shipping_cost($distance);
    $delivery_time = dbs_calculate_delivery_time($distance);
    
    // Ulož do session
    WC()->session->set('dbs_shipping_address', $address);
    WC()->session->set('dbs_shipping_distance', $distance);
    WC()->session->set('dbs_shipping_cost', $shipping_cost);
    WC()->session->set('dbs_shipping_method', 'dbs_shipping');
    
    wp_send_json_success(array(
        'distance' => $distance,
        'cost' => $shipping_cost,
        'delivery_time' => $delivery_time,
        'store_name' => $nearest_store['name'],
        'store_address' => $nearest_store['address']
    ));
}
add_action('wp_ajax_dbs_checkout_calculator', 'dbs_checkout_calculator_ajax');
add_action('wp_ajax_nopriv_dbs_checkout_calculator', 'dbs_checkout_calculator_ajax');

/**
 * AJAX handler pro aplikování shipping sazby na checkoutu
 */
function dbs_apply_checkout_shipping_ajax() {
    check_ajax_referer('dbs_nonce', 'nonce');
    
    $shipping_method = sanitize_text_field($_POST['shipping_method'] ?? '');
    $shipping_cost = floatval($_POST['shipping_cost'] ?? 0);
    
    if (empty($shipping_method)) {
        wp_send_json_error(array(
            'message' => __('Nebyla vybrána shipping metoda.', 'distance-based-shipping')
        ));
    }
    
    // Aplikuj shipping metodu
    $chosen_methods = WC()->session->get('chosen_shipping_methods', array());
    $chosen_methods[0] = $shipping_method;
    WC()->session->set('chosen_shipping_methods', $chosen_methods);
    
    // Ulož shipping náklady
    WC()->session->set('dbs_shipping_cost', $shipping_cost);
    
    // Aktualizuj shipping náklady v packages
    $packages = WC()->shipping()->get_packages();
    if (!empty($packages)) {
        $packages[0]['rates'][$shipping_method]->cost = $shipping_cost;
        WC()->session->set('shipping_for_package_0', $packages[0]);
    }
    
    wp_send_json_success(array(
        'message' => __('Doprava byla úspěšně aplikována.', 'distance-based-shipping')
    ));
}
add_action('wp_ajax_dbs_apply_checkout_shipping', 'dbs_apply_checkout_shipping_ajax');
add_action('wp_ajax_nopriv_dbs_apply_checkout_shipping', 'dbs_apply_checkout_shipping_ajax');

/**
 * Přidá JavaScript pro checkout kalkulátor
 */
function dbs_enqueue_checkout_scripts() {
    // Zkontroluj, zda jsme na checkout stránce a ne na cart stránce
    if (!is_checkout() || is_cart()) {
        return;
    }
    
    wp_enqueue_script(
        'dbs-checkout',
        plugin_dir_url(__FILE__) . '../../assets/js/checkout.js',
        array('jquery'),
        defined('DBS_PLUGIN_VERSION') ? DBS_PLUGIN_VERSION : '1.0.0',
        true
    );
    
    wp_localize_script('dbs-checkout', 'dbs_checkout', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dbs_nonce'),
        'strings' => array(
            'calculating' => __('Počítám...', 'distance-based-shipping'),
            'error' => __('Došlo k chybě.', 'distance-based-shipping'),
            'success' => __('Doprava byla aplikována.', 'distance-based-shipping')
        )
    ));
}
add_action('wp_enqueue_scripts', 'dbs_enqueue_checkout_scripts');

/**
 * Přidá CSS styly pro checkout kalkulátor
 */
function dbs_enqueue_checkout_styles() {
    // Zkontroluj, zda jsme na checkout stránce a ne na cart stránce
    if (!is_checkout() || is_cart()) {
        return;
    }
    
    wp_enqueue_style(
        'dbs-checkout',
        plugin_dir_url(__FILE__) . '../../assets/css/checkout.css',
        array(),
        defined('DBS_PLUGIN_VERSION') ? DBS_PLUGIN_VERSION : '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'dbs_enqueue_checkout_styles');

/**
 * Hook pro validaci adresy při změně checkout formuláře
 */
function dbs_checkout_address_validation() {
    add_action('woocommerce_checkout_update_order_review', 'dbs_validate_checkout_address_wrapper');
}
add_action('init', 'dbs_checkout_address_validation');

/**
 * Hook pro aplikování uložené shipping sazby
 */
function dbs_apply_saved_shipping_hook() {
    add_action('woocommerce_before_checkout_form', 'dbs_apply_saved_shipping_on_checkout');
}
add_action('init', 'dbs_apply_saved_shipping_hook');

/**
 * Spustí přepočet dopravy při změně cart total
 */
function dbs_trigger_shipping_recalculation_cart_total() {
    // Invalidate all cache when cart total changes
    if ( WC()->cart ) {
        $current_total = WC()->cart->get_cart_contents_total();
        $saved_total = WC()->session->get('dbs_last_cart_total');
        
        // Pokud se cart total změnil významně (více než 1 Kč)
        if ( abs($current_total - $saved_total) > 1 ) {
            // Force shipping recalculation
            WC()->cart->calculate_shipping();
            
            // Clear all cache
            dbs_invalidate_all_cache();
            
            // Force WooCommerce to recalculate shipping rates
            WC()->cart->get_shipping_packages();
            
            // Uložit nový cart total
            WC()->session->set('dbs_last_cart_total', $current_total);
            
            if (get_option('dbs_debug_mode', 0)) {
                error_log('DBS: Shipping cache invalidated due to cart total change - Old total: ' . $saved_total . ', New total: ' . $current_total);
            }
        }
    }
} 
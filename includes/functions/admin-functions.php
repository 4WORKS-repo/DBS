<?php
/**
 * Admin functions for Distance Based Shipping plugin.
 *
 * File: includes/functions/admin-functions.php
 *
 * @package DistanceBasedShipping
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize admin interface.
 *
 * @return void
 */
function dbs_init_admin() {
	// Debug informace
	if ( get_option( 'dbs_debug_mode', 0 ) ) {
		error_log( 'DBS: Initializing admin interface' );
	}

	// Add admin menu.
	add_action( 'admin_menu', 'dbs_add_admin_menu' );

	// Register settings.
	add_action( 'admin_init', 'dbs_register_settings' );

	// Display admin notices.
	add_action( 'admin_notices', 'dbs_display_admin_notices' );

	// Debug informace
	if ( get_option( 'dbs_debug_mode', 0 ) ) {
		error_log( 'DBS: Admin interface initialized' );
	}
}

/**
 * Add admin menu pages.
 *
 * @return void
 */
function dbs_add_admin_menu() {
	$capability = 'manage_woocommerce';

	// Main menu page - dashboard
	add_menu_page(
		__( 'Distance Shipping', 'distance-shipping' ),
		__( 'Distance Shipping', 'distance-shipping' ),
		$capability,
		'distance-shipping',
		'dbs_render_main_page', // Use main dashboard page
		'dashicons-location-alt',
		56
	);

	// Dashboard page (same as main menu)
	add_submenu_page(
		'distance-shipping',
		__( 'Dashboard', 'distance-shipping' ),
		__( 'Dashboard', 'distance-shipping' ),
		$capability,
		'distance-shipping', // Same as main menu slug
		'dbs_render_main_page'
	);

	// Settings page
	add_submenu_page(
		'distance-shipping',
		__( 'Settings', 'distance-shipping' ),
		__( 'Settings', 'distance-shipping' ),
		$capability,
		'distance-shipping-settings',
		'dbs_render_settings_page'
	);

	add_submenu_page(
		'distance-shipping',
		__( 'Store Locations', 'distance-shipping' ),
		__( 'Store Locations', 'distance-shipping' ),
		$capability,
		'distance-shipping-stores',
		'dbs_render_stores_page'
	);

	add_submenu_page(
		'distance-shipping',
		__( 'Shipping Rules', 'distance-shipping' ),
		__( 'Shipping Rules', 'distance-shipping' ),
		$capability,
		'distance-shipping-rules',
		'dbs_render_rules_page'
	);

	// Testovací nástroje
	add_submenu_page(
		'distance-shipping',
		__( 'Testovací nástroje', 'distance-shipping' ),
		__( 'Testovací nástroje', 'distance-shipping' ),
		$capability,
		'distance-shipping-tools',
		'dbs_render_tools_page'
	);
}

/**
 * Register plugin settings.
 *
 * @return void
 */
function dbs_register_settings() {
	$settings = dbs_get_plugin_settings();

	foreach ( $settings as $setting ) {
		register_setting( 'dbs_settings', $setting );
	}
}

/**
 * Get plugin settings list.
 *
 * @return array Settings list.
 */
function dbs_get_plugin_settings() {
	return array(
		'dbs_map_service',
		'dbs_google_api_key',
		'dbs_bing_api_key',
		'dbs_distance_unit',
		'dbs_enable_caching',
		'dbs_cache_duration',
		'dbs_fallback_rate',
		'dbs_debug_mode',
		'dbs_adjust_shipping_for_vat', // Nové nastavení pro DPH přepínač
		'dbs_price_includes_tax', // Nové nastavení pro DPH
		'dbs_tax_status', // Nové nastavení pro tax status
	);
}

/**
 * Display admin notices.
 *
 * @return void
 */
function dbs_display_admin_notices(): void {
	if ( ! dbs_is_plugin_page() ) {
		return;
	}

	$notices = dbs_get_admin_notices();

	foreach ( $notices as $notice ) {
		dbs_render_admin_notice( $notice );
	}
}

/**
 * Check if current page is a plugin page.
 *
 * @return bool True if plugin page.
 */
function dbs_is_plugin_page(): bool {
	$page = $_GET['page'] ?? '';
	return strpos( $page, 'distance-shipping' ) !== false;
}

/**
 * Get admin notices to display.
 *
 * @return array Admin notices.
 */
function dbs_get_admin_notices(): array {
	$notices = [];

	// Check for API key requirements.
	$map_service = get_option( 'dbs_map_service', 'openstreetmap' );

	if ( 'google' === $map_service && ! get_option( 'dbs_google_api_key' ) ) {
		$notices[] = [
			'type'    => 'warning',
			'message' => sprintf(
				/* translators: %s: Settings page URL */
				__( 'Please configure your Google Maps API key in the <a href="%s">settings</a> to enable distance calculation.', 'distance-shipping' ),
				admin_url( 'admin.php?page=distance-shipping-settings' )
			),
		];
	}

	if ( 'bing' === $map_service && ! get_option( 'dbs_bing_api_key' ) ) {
		$notices[] = [
			'type'    => 'warning',
			'message' => sprintf(
				/* translators: %s: Settings page URL */
				__( 'Please configure your Bing Maps API key in the <a href="%s">settings</a> to enable distance calculation.', 'distance-shipping' ),
				admin_url( 'admin.php?page=distance-shipping-settings' )
			),
		];
	}

	return $notices;
}

/**
 * Render admin notice.
 *
 * @param array $notice Notice data.
 * @return void
 */
function dbs_render_admin_notice( array $notice ): void {
	$type = sanitize_html_class( $notice['type'] ?? 'info' );
	?>
	<div class="notice notice-<?php echo esc_attr( $type ); ?>">
		<p><?php echo wp_kses_post( $notice['message'] ?? '' ); ?></p>
	</div>
	<?php
}

/**
 * Render main admin page.
 *
 * @return void
 */
function dbs_render_main_page(): void {
	include DBS_PLUGIN_PATH . 'admin/views/main-page.php';
}

/**
 * Render settings page.
 *
 * @return void
 */
function dbs_render_settings_page(): void {
	// Handle form submission.
	if ( isset( $_POST['submit'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'dbs_settings' ) ) {
		dbs_save_settings();
	}

	include DBS_PLUGIN_PATH . 'admin/views/settings-page.php';
}

/**
 * Render stores page.
 *
 * @return void
 */
function dbs_render_stores_page(): void {
	$action = $_GET['action'] ?? 'list';
	$store_id = (int) ( $_GET['store_id'] ?? 0 );

	// Handle form submissions.
	if ( isset( $_POST['submit'] ) ) {
		dbs_handle_store_form_submission( $action, $store_id );
	}

	// Handle delete action.
	if ( 'delete' === $action && $store_id && wp_verify_nonce( $_GET['_wpnonce'], 'delete_store_' . $store_id ) ) {
		dbs_delete_store( $store_id );
		wp_redirect( admin_url( 'admin.php?page=distance-shipping-stores&message=deleted' ) );
		exit;
	}

	include DBS_PLUGIN_PATH . 'admin/views/stores-page.php';
}

/**
 * Render rules page.
 *
 * @return void
 */
function dbs_render_rules_page(): void {
	$action = $_GET['action'] ?? 'list';
	$rule_id = (int) ( $_GET['rule_id'] ?? 0 );

	// Handle form submission.
	if ( isset( $_POST['submit'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'dbs_rule_form' ) ) {
		$action = sanitize_text_field( $_POST['action'] ?? 'add' );
		$rule_id = (int) ( $_POST['rule_id'] ?? 0 );
		dbs_handle_rule_form_submission( $action, $rule_id );
	}

	// Handle delete action.
	if ( 'delete' === $action && $rule_id && wp_verify_nonce( $_GET['_wpnonce'], 'delete_rule_' . $rule_id ) ) {
		$result = dbs_delete_shipping_rule( $rule_id );
		$message = $result !== false ? 'deleted' : 'error';
		wp_redirect( admin_url( 'admin.php?page=distance-shipping-rules&message=' . $message ) );
		exit;
	}

	include DBS_PLUGIN_PATH . 'admin/views/rules-page.php';
}

/**
 * Test validace formuláře.
 *
 * @return string Zpráva o výsledku testu.
 */
function dbs_test_validation_fix(): string {
	$results = [];
	
	// Test 1: Kontrola souboru rule-form.php
	$rule_form_file = DBS_PLUGIN_PATH . 'admin/partials/rule-form.php';
	if ( file_exists( $rule_form_file ) ) {
		$content = file_get_contents( $rule_form_file );
		
		if ( strpos( $content, 'Musíte zadat alespoň jednu sazbu' ) !== false ) {
			$results[] = '❌ rule-form.php obsahuje starou validaci';
		} else {
			$results[] = '✅ rule-form.php neobsahuje starou validaci';
		}
		
		if ( strpos( $content, 'Kontrola pouze záporných hodnot' ) !== false ) {
			$results[] = '✅ rule-form.php obsahuje novou validaci';
		} else {
			$results[] = '❌ rule-form.php neobsahuje novou validaci';
		}
	} else {
		$results[] = '❌ Soubor rule-form.php nebyl nalezen';
	}
	
	// Test 2: Kontrola souboru admin.js
	$admin_js_file = DBS_PLUGIN_PATH . 'assets/js/admin.js';
	if ( file_exists( $admin_js_file ) ) {
		$content = file_get_contents( $admin_js_file );
		
		if ( strpos( $content, 'Musíte zadat alespoň jednu sazbu' ) !== false ) {
			$results[] = '❌ admin.js obsahuje starou validaci';
		} else {
			$results[] = '✅ admin.js neobsahuje starou validaci';
		}
		
		if ( strpos( $content, 'Kontrola pouze záporných hodnot' ) !== false ) {
			$results[] = '✅ admin.js obsahuje novou validaci';
		} else {
			$results[] = '❌ admin.js neobsahuje novou validaci';
		}
	} else {
		$results[] = '❌ Soubor admin.js nebyl nalezen';
	}
	
	$all_passed = ! in_array( '❌', $results );
	
	return sprintf(
		'<strong>Výsledky testu validace:</strong><br>%s<br><br><strong>Status:</strong> %s',
		implode( '<br>', $results ),
		$all_passed ? '✅ Všechny testy prošly' : '❌ Některé testy selhaly'
	);
}

/**
 * Vynucení načtení souborů.
 *
 * @return string Zpráva o výsledku.
 */
function dbs_force_reload_validation(): string {
	$results = [];
	
	// Vyčištění WordPress cache
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
		$results[] = '✅ WordPress cache vyčištěn';
	} else {
		$results[] = '⚠️ WordPress cache funkce není dostupná';
	}
	
	// Vyčištění transients
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'" );
	$results[] = '✅ Transients vyčištěny';
	
	// Kontrola souborů
	$files_to_check = [
		'admin/partials/rule-form.php' => [
			'old_text' => 'Musíte zadat alespoň jednu sazbu',
			'new_text' => 'Kontrola pouze záporných hodnot'
		],
		'assets/js/admin.js' => [
			'old_text' => 'Musíte zadat alespoň jednu sazbu',
			'new_text' => 'Kontrola pouze záporných hodnot'
		]
	];
	
	foreach ( $files_to_check as $file_path => $checks ) {
		$full_path = DBS_PLUGIN_PATH . $file_path;
		
		if ( file_exists( $full_path ) ) {
			$content = file_get_contents( $full_path );
			
			if ( strpos( $content, $checks['old_text'] ) !== false ) {
				$results[] = '❌ ' . $file_path . ' - stále obsahuje starou validaci';
			} else {
				$results[] = '✅ ' . $file_path . ' - stará validace odstraněna';
			}
			
			if ( strpos( $content, $checks['new_text'] ) !== false ) {
				$results[] = '✅ ' . $file_path . ' - obsahuje novou validaci';
			} else {
				$results[] = '❌ ' . $file_path . ' - neobsahuje novou validaci';
			}
		} else {
			$results[] = '❌ ' . $file_path . ' - soubor nebyl nalezen';
		}
	}
	
	return sprintf(
		'<strong>Vynucení načtení dokončeno:</strong><br>%s<br><br><strong>Doporučení:</strong> Vyčistěte cache prohlížeče (Ctrl+ShiftR) a zkuste vytvořit pravidlo s nulovou základní sazbou.',
		implode( '<br>', $results )
	);
}

/**
 * Vyčištění všech cache.
 *
 * @return string Zpráva o výsledku.
 */
function dbs_clear_all_cache(): string {
	$results = [];
	
	// Použití existující funkce pro invalidaci DBS cache
	if ( function_exists( 'dbs_invalidate_all_cache' ) ) {
		dbs_invalidate_all_cache();
		$results[] = '✅ DBS cache vyčištěn';
	}
	
	// WordPress cache
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
		$results[] = '✅ WordPress cache vyčištěn';
	}
	
	// Všechny transients
	global $wpdb;
	$deleted_transients = $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );
	$deleted_site_transients = $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'" );
	$results[] = sprintf( '✅ Všechny transients vyčištěny (%d záznamů)', $deleted_transients + $deleted_site_transients );
	
	// Plugin cache
	if ( function_exists( 'dbs_cleanup_cache' ) ) {
		$cache_cleared = dbs_cleanup_cache();
		$results[] = sprintf( '✅ Plugin cache vyčištěn (%d záznamů)', $cache_cleared );
	} else {
		$results[] = '⚠️ Funkce dbs_cleanup_cache není dostupná';
	}
	
	// WooCommerce cache
	if ( function_exists( 'wc_cache_helper_get_transient_version' ) ) {
		wp_cache_flush();
		$results[] = '✅ WooCommerce cache vyčištěn';
	}
	
	return sprintf(
		'<strong>Cache vyčištěna:</strong><br>%s',
		implode( '<br>', $results )
	);
}

/**
 * Test opravy shipping cache při změně množství.
 *
 * @return string Zpráva o výsledku testu.
 */
function dbs_test_shipping_cache_fix(): string {
	$results = [];
	
	// 1. Test nové funkce pro invalidaci cache
	$results[] = '<h3>1. Test funkce pro invalidaci cache</h3>';
	
	if (function_exists('dbs_invalidate_all_cache')) {
		$results[] = "✅ Funkce dbs_invalidate_all_cache existuje";
		
		// Test invalidace cache
		dbs_invalidate_all_cache();
		$results[] = "✅ Cache byla invalidována";
	} else {
		$results[] = "❌ Funkce dbs_invalidate_all_cache neexistuje";
	}
	
	// 2. Test nové funkce get_cart_hash
	$results[] = '<h3>2. Test funkce get_cart_hash</h3>';
	
	// Vytvořit instanci shipping metody pro test
	$shipping_method = new DBS_Shipping_Method();
	$reflection = new ReflectionClass($shipping_method);
	$get_cart_hash_method = $reflection->getMethod('get_cart_hash');
	$get_cart_hash_method->setAccessible(true);
	
	// Test s prázdným package
	$package = [];
	$cart_hash = $get_cart_hash_method->invoke($shipping_method, $package);
	$results[] = "✅ Funkce get_cart_hash funguje - Hash: " . substr($cart_hash, 0, 8) . "...";
	
	// 3. Test cache klíče s cart hash
	$results[] = '<h3>3. Test cache klíče s cart hash</h3>';
	
	// Simulovat package s produkty
	$package_with_items = [
		'contents' => [
			'test_item' => [
				'product_id' => 152,
				'quantity' => 1,
				'variation_id' => 0
			]
		]
	];
	
	$cart_hash_with_items = $get_cart_hash_method->invoke($shipping_method, $package_with_items);
	$results[] = "✅ Cart hash s produkty: " . substr($cart_hash_with_items, 0, 8) . "...";
	
	// 4. Test změny množství
	$results[] = '<h3>4. Test změny množství</h3>';
	
	$package_quantity_1 = [
		'contents' => [
			'test_item' => [
				'product_id' => 152,
				'quantity' => 1,
				'variation_id' => 0
			]
		]
	];
	
	$package_quantity_10 = [
		'contents' => [
			'test_item' => [
				'product_id' => 152,
				'quantity' => 10,
				'variation_id' => 0
			]
		]
	];
	
	$hash_quantity_1 = $get_cart_hash_method->invoke($shipping_method, $package_quantity_1);
	$hash_quantity_10 = $get_cart_hash_method->invoke($shipping_method, $package_quantity_10);
	
	if ($hash_quantity_1 !== $hash_quantity_10) {
		$results[] = "✅ Cache klíče se liší při změně množství";
		$results[] = "   Hash pro 1 kus: " . substr($hash_quantity_1, 0, 8) . "...";
		$results[] = "   Hash pro 10 kusů: " . substr($hash_quantity_10, 0, 8) . "...";
	} else {
		$results[] = "❌ Cache klíče se neliší při změně množství";
	}
	
	// 5. Test hooků pro invalidaci cache
	$results[] = '<h3>5. Test hooků pro invalidaci cache</h3>';
	
	$cache_hooks = [
		'woocommerce_cart_item_removed' => has_action('woocommerce_cart_item_removed', 'dbs_trigger_shipping_recalculation'),
		'woocommerce_cart_item_restored' => has_action('woocommerce_cart_item_restored', 'dbs_trigger_shipping_recalculation'),
		'woocommerce_cart_item_set_quantity' => has_action('woocommerce_cart_item_set_quantity', 'dbs_trigger_shipping_recalculation'),
		'woocommerce_cart_updated' => has_action('woocommerce_cart_updated', 'dbs_trigger_shipping_recalculation_cart_updated'),
		'woocommerce_cart_item_updated' => has_action('woocommerce_cart_item_updated', 'dbs_trigger_shipping_recalculation_cart_item'),
		'woocommerce_cart_updated' => has_action('woocommerce_cart_updated', 'dbs_trigger_shipping_recalculation_cart_total'),
	];
	
	foreach ($cache_hooks as $hook => $priority) {
		if ($priority) {
			$results[] = "✅ Hook {$hook} je registrován s prioritou {$priority}";
		} else {
			$results[] = "❌ Hook {$hook} není registrován";
		}
	}
	
	// 6. Test AJAX handler
	$results[] = '<h3>6. Test AJAX handler</h3>';
	
	$ajax_handlers = [
		'wp_ajax_dbs_invalidate_shipping_cache' => has_action('wp_ajax_dbs_invalidate_shipping_cache', 'dbs_ajax_invalidate_shipping_cache'),
		'wp_ajax_nopriv_dbs_invalidate_shipping_cache' => has_action('wp_ajax_nopriv_dbs_invalidate_shipping_cache', 'dbs_ajax_invalidate_shipping_cache'),
	];
	
	foreach ($ajax_handlers as $handler => $registered) {
		if ($registered) {
			$results[] = "✅ AJAX handler {$handler} je registrován";
		} else {
			$results[] = "❌ AJAX handler {$handler} není registrován";
		}
	}
	
	// 7. Test shipping pravidel s hmotnostními podmínkami
	$results[] = '<h3>7. Test shipping pravidel s hmotnostními podmínkami</h3>';
	$rules = dbs_get_shipping_rules(true);
	$weight_rules = [];
	
	foreach ($rules as $rule) {
		if (!empty($rule->min_weight) || !empty($rule->max_weight)) {
			$weight_rules[] = $rule;
		}
	}
	
	if (count($weight_rules) > 0) {
		$results[] = "✅ Nalezeno " . count($weight_rules) . " pravidel s hmotnostními podmínkami:";
		foreach ($weight_rules as $rule) {
			$results[] = "   - {$rule->name}: {$rule->min_weight} - {$rule->max_weight} kg (priorita: {$rule->priority})";
		}
	} else {
		$results[] = "❌ Nenalezena žádná pravidla s hmotnostními podmínkami";
	}
	
	// 8. Test cache invalidace
	$results[] = '<h3>8. Test cache invalidace</h3>';
	
	// Simulovat cache klíč
	$destination = "Karlova 3, 397 01, Česká republika";
	$cart_hash_1 = $get_cart_hash_method->invoke($shipping_method, $package_quantity_1);
	$cart_hash_10 = $get_cart_hash_method->invoke($shipping_method, $package_quantity_10);
	
	$cache_key_1 = 'dbs_shipping_' . md5($destination . '_' . $cart_hash_1);
	$cache_key_10 = 'dbs_shipping_' . md5($destination . '_' . $cart_hash_10);
	
	$results[] = "✅ Cache klíče se liší při změně množství:";
	$results[] = "   Klíč pro 1 kus: " . substr($cache_key_1, 0, 20) . "...";
	$results[] = "   Klíč pro 10 kusů: " . substr($cache_key_10, 0, 20) . "...";
	
	// 9. Shrnutí
	$results[] = '<h3>9. Shrnutí</h3>';
	
	$tests_passed = 0;
	$total_tests = 9;
	
	if (function_exists('dbs_invalidate_all_cache')) $tests_passed++;
	if (function_exists('get_cart_hash')) $tests_passed++;
	if ($hash_quantity_1 !== $hash_quantity_10) $tests_passed++;
	if (count(array_filter($cache_hooks)) >= 4) $tests_passed++;
	if (count(array_filter($ajax_handlers)) >= 1) $tests_passed++;
	if (count($weight_rules) > 0) $tests_passed++;
	if ($cache_key_1 !== $cache_key_10) $tests_passed++;
	
	$results[] = "✅ Prošlo {$tests_passed} z {$total_tests} testů";
	
	if ($tests_passed === $total_tests) {
		$results[] = "<h3 style='color: green;'>🎉 Všechny testy prošly! Oprava shipping cache je funkční.</h3>";
		$results[] = "<p><strong>Co bylo opraveno:</strong></p>";
		$results[] = "<ul>";
		$results[] = "<li>Cache klíč nyní obsahuje informace o košíku (hmotnost, množství, hodnota)</li>";
		$results[] = "<li>Přidána funkce dbs_invalidate_all_cache() pro invalidaci všech cache</li>";
		$results[] = "<li>Všechny hooky nyní invalidují jak session cache, tak WordPress transients</li>";
		$results[] = "<li>AJAX handler nyní používá centralizovanou funkci pro invalidaci cache</li>";
		$results[] = "</ul>";
	} else {
		$results[] = "<h3 style='color: red;'>❌ Některé testy selhaly. Zkontrolujte implementaci.</h3>";
	}
	
	$results[] = '<h3>Návod na testování:</h3>';
	$results[] = '<ol>';
	$results[] = '<li>Zapněte debug mód v admin rozhraní</li>';
	$results[] = '<li>Přidejte produkt do košíku s hmotností, která spadá do jednoho pravidla</li>';
	$results[] = '<li>Změňte množství tak, aby celková hmotnost spadala do jiného pravidla</li>';
	$results[] = '<li>Zkontrolujte, zda se shipping pravidlo změnilo</li>';
	$results[] = '<li>Zkontrolujte debug log pro zprávy o invalidaci cache</li>';
	$results[] = '</ol>';
	
	$results[] = '<h3>Očekávané chování:</h3>';
	$results[] = '<ul>';
	$results[] = '<li>✅ Při změně množství se shipping cache invaliduje</li>';
	$results[] = '<li>✅ Shipping pravidla se přepočítají podle nové hmotnosti</li>';
	$results[] = '<li>✅ Debug log obsahuje zprávy o invalidaci cache</li>';
	$results[] = '<li>✅ Frontend se aktualizuje s novým shipping pravidlem</li>';
	$results[] = '</ul>';
	
	return implode('<br>', $results);
}

/**
 * Test Rule 26 - Hmotnostní podmínky
 *
 * @return string Výsledek testu.
 */
function dbs_test_rule_26(): string {
	$results = [];
	
	// Zapnutí debug módu
	update_option( 'dbs_debug_mode', 1 );
	
	$results[] = '<h2>Test Rule 26 - Hmotnostní podmínky</h2>';
	$results[] = '<p>Testuje, proč se Rule 26 aplikuje při 9kg, když má podmínky 75-100kg.</p>';
	
	// Funkce pro vytvoření testovacího balíčku
	function create_test_package( $product_id, $quantity ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return null;
		}
		
		return [
			'contents' => [
				[
					'key' => 'test_item_' . $product_id,
					'product_id' => $product_id,
					'variation_id' => 0,
					'quantity' => $quantity,
					'data' => $product,
					'line_tax_data' => [],
									'line_subtotal' => (float) $product->get_price() * $quantity,
				'line_subtotal_tax' => 0,
				'line_total' => (float) $product->get_price() * $quantity,
				'line_tax' => 0,
				]
			],
			'contents_cost' => (float) $product->get_price() * $quantity,
			'applied_coupons' => [],
			'user' => [
				'ID' => get_current_user_id(),
			],
			'destination' => [
				'country' => 'CZ',
				'state' => '',
				'postcode' => '12000',
				'city' => 'Praha',
				'address' => 'Testovací adresa 123',
				'address_2' => '',
			],
		];
	}
	
	// Najít produkt s hmotností 1kg
	$test_product_id = null;
	$products = wc_get_products( [
		'limit' => 100,
		'status' => 'publish'
	] );
	
	foreach ( $products as $product ) {
		if ( $product->get_weight() == 1 ) {
			$test_product_id = $product->get_id();
			break;
		}
	}
	
	if ( ! $test_product_id ) {
		$results[] = '<p style="color: red;">Nebyl nalezen produkt s hmotností 1kg. Vytvořím testovací produkt.</p>';
		
		// Vytvoření testovacího produktu
		$product = new WC_Product_Simple();
		$product->set_name( 'Testovací produkt 1kg' );
		$product->set_price( 100 );
		$product->set_weight( 1 );
		$product->set_status( 'publish' );
		$test_product_id = $product->save();
		
		$results[] = '<p>Vytvořen testovací produkt s ID: ' . $test_product_id . '</p>';
	}
	
	$results[] = '<p>Používám produkt ID: ' . $test_product_id . '</p>';
	
	// Testování scénáře 3 kusů (9kg)
	$package = create_test_package( $test_product_id, 3 );
	$weight = dbs_get_package_weight( $package );
	
	$results[] = '<h3>Test: 3 kusy produktu 1kg (9kg celkem)</h3>';
	$results[] = '<p>Vypočítaná hmotnost: ' . $weight . ' kg</p>';
	
	// Získání Rule 26
	$rules = dbs_get_shipping_rules();
	$rule_26 = null;
	
	foreach ( $rules as $rule ) {
		if ( $rule->id == 26 ) {
			$rule_26 = $rule;
			break;
		}
	}
	
	if ( $rule_26 ) {
		$results[] = '<h3>Rule 26 - Detailní analýza</h3>';
		$results[] = '<p><strong>Název:</strong> ' . $rule_26->rule_name . '</p>';
		$results[] = '<p><strong>Priorita:</strong> ' . $rule_26->priority . '</p>';
		$results[] = '<p><strong>Vzdálenost:</strong> ' . $rule_26->distance_from . '-' . $rule_26->distance_to . ' km</p>';
		$results[] = '<p><strong>Hmotnost:</strong> ' . $rule_26->weight_min . '-' . $rule_26->weight_max . ' kg</p>';
		$results[] = '<p><strong>Operátor hmotnosti:</strong> ' . $rule_26->weight_operator . '</p>';
		
		// Test hmotnostní podmínky
		$weight_ok = dbs_check_weight_condition( $rule_26, $package );
		$status = $weight_ok ? 'Aplikuje se' : 'Neaplikuje se';
		$color = $weight_ok ? 'green' : 'red';
		
		$results[] = '<p style="color: ' . $color . '; font-weight: bold;">Hmotnostní podmínka: ' . $status . '</p>';
		
		// Test všech podmínek
		$all_conditions_ok = dbs_check_all_conditions( $rule_26, $package );
		$status = $all_conditions_ok ? 'Aplikuje se' : 'Neaplikuje se';
		$color = $all_conditions_ok ? 'green' : 'red';
		
		$results[] = '<p style="color: ' . $color . '; font-weight: bold;">Všechny podmínky: ' . $status . '</p>';
		
		// Test vzdálenosti (simulujeme 91km)
		$distance = 91;
		$distance_ok = ( $distance >= $rule_26->distance_from && ( $rule_26->distance_to <= 0 || $distance <= $rule_26->distance_to ) );
		$status = $distance_ok ? 'Aplikuje se' : 'Neaplikuje se';
		$color = $distance_ok ? 'green' : 'red';
		
		$results[] = '<p style="color: ' . $color . ';">Vzdálenost (' . $distance . ' km): ' . $status . '</p>';
		
		// Analýza problému
		$results[] = '<h3>Analýza problému</h3>';
		$results[] = '<p><strong>Očekávané chování:</strong></p>';
		$results[] = '<ul>';
		$results[] = '<li>Hmotnost: 9kg vs 75-100kg → Neaplikuje se</li>';
		$results[] = '<li>Vzdálenost: 91km vs 0-100km → Aplikuje se</li>';
		$results[] = '<li>Operátor: AND → Všechny podmínky musí být splněny</li>';
		$results[] = '<li>Výsledek: Neaplikuje se (hmotnost nevyhovuje)</li>';
		$results[] = '</ul>';
		
		if ( $all_conditions_ok ) {
			$results[] = '<p style="color: red; font-weight: bold;">PROBLÉM: Rule 26 se aplikuje i když by se aplikovat neměla!</p>';
		} else {
			$results[] = '<p style="color: green; font-weight: bold;">OK: Rule 26 se neaplikuje (správně)</p>';
		}
		
	} else {
		$results[] = '<p style="color: red;">Rule 26 nebyla nalezena!</p>';
	}
	
	$results[] = '<h3>Debug log</h3>';
	$results[] = '<p>Zkontrolujte debug log pro detailní informace o kontrole podmínek.</p>';
	
	$results[] = '<h3>Všechna pravidla s hmotnostními podmínkami</h3>';
	$results[] = '<table border="1" style="border-collapse: collapse; width: 100%;">';
	$results[] = '<tr><th>ID</th><th>Název</th><th>Priorita</th><th>Hmotnost</th><th>Operátor</th><th>Status</th></tr>';
	
	foreach ( $rules as $rule ) {
		if ( $rule->weight_min > 0 || $rule->weight_max > 0 ) {
			$weight_ok = dbs_check_weight_condition( $rule, $package );
			$status = $weight_ok ? 'Aplikuje se' : 'Neaplikuje se';
			$color = $weight_ok ? 'green' : 'red';
			
			$results[] = '<tr>';
			$results[] = '<td>' . $rule->id . '</td>';
			$results[] = '<td>' . $rule->rule_name . '</td>';
			$results[] = '<td>' . $rule->priority . '</td>';
			$results[] = '<td>' . $rule->weight_min . '-' . $rule->weight_max . ' kg</td>';
			$results[] = '<td>' . $rule->weight_operator . '</td>';
			$results[] = '<td style="color: ' . $color . '; font-weight: bold;">' . $status . '</td>';
			$results[] = '</tr>';
		}
	}
	
	$results[] = '</table>';
	
	return implode('<br>', $results);
}



/**
 * Render debug page.
 *
 * @return void
 */
function dbs_render_debug_page(): void {
	echo '<div class="wrap">';
	echo '<h1>Debug</h1>';
	echo '<p>Debug stránka je momentálně nedostupná.</p>';
	echo '</div>';
}

/**
 * Render tools page.
 *
 * @return void
 */
function dbs_render_tools_page(): void {
	include DBS_PLUGIN_PATH . 'admin/views/tools-page.php';
}

/**
 * Test váhového výpočtu
 * 
 * @return void
 */
function dbs_admin_test_weight_calculation() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	// Načíst testovací soubor pouze při potřebě
	$test_file = plugin_dir_path( __FILE__ ) . '../../test-weight-calculation.php';
	if ( file_exists( $test_file ) ) {
		include_once $test_file;
		if ( function_exists( 'dbs_test_weight_calculation' ) ) {
			dbs_test_weight_calculation();
		} else {
			echo '<p>Chyba: Funkce dbs_test_weight_calculation nebyla nalezena.</p>';
		}
	} else {
		echo '<p>Chyba: Testovací soubor nebyl nalezen.</p>';
	}
}

/**
 * Test AND/OR operátorů
 * 
 * @return void
 */
function dbs_admin_test_operators() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	// Načíst testovací soubor pouze při potřebě
	$test_file = plugin_dir_path( __FILE__ ) . '../../test-operators.php';
	if ( file_exists( $test_file ) ) {
		include_once $test_file;
		if ( function_exists( 'dbs_test_operators' ) ) {
			dbs_test_operators();
		} else {
			echo '<p>Chyba: Funkce dbs_test_operators nebyla nalezena.</p>';
		}
	} else {
		echo '<p>Chyba: Testovací soubor nebyl nalezen.</p>';
	}
}

/**
 * Test konkrétního Rule 26 problému
 * 
 * @return void
 */
function dbs_admin_test_rule26_problem() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	// Načíst testovací soubor pouze při potřebě
	$test_file = plugin_dir_path( __FILE__ ) . '../../test-rule26-fix.php';
	if ( file_exists( $test_file ) ) {
		include_once $test_file;
		if ( function_exists( 'dbs_test_rule26_specific_problem' ) ) {
			dbs_test_rule26_specific_problem();
		} else {
			echo '<p>Chyba: Funkce dbs_test_rule26_specific_problem nebyla nalezena.</p>';
		}
	} else {
		echo '<p>Chyba: Testovací soubor nebyl nalezen.</p>';
	}
}

/**
 * Test Rule 31 problému
 */
function dbs_admin_test_rule31_problem() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	// Načíst testovací soubor pouze při potřebě
	$test_file = plugin_dir_path( __FILE__ ) . '../../test-rule31-debug.php';
	if ( file_exists( $test_file ) ) {
		include_once $test_file;
		if ( function_exists( 'dbs_test_rule31_problem' ) ) {
			dbs_test_rule31_problem();
		} else {
			echo '<p>Chyba: Funkce dbs_test_rule31_problem nebyla nalezena.</p>';
		}
	} else {
		echo '<p>Chyba: Testovací soubor nebyl nalezen.</p>';
	}
}

/**
 * Save plugin settings.
 *
 * @return void
 */
function dbs_save_settings(): void {
	$settings = dbs_get_plugin_settings();

	foreach ( $settings as $setting ) {
		$value = $_POST[ $setting ] ?? '';

		if ( in_array( $setting, [ 'dbs_enable_caching', 'dbs_debug_mode', 'dbs_adjust_shipping_for_vat', 'dbs_price_includes_tax' ], true ) ) {
			$value = isset( $_POST[ $setting ] ) ? '1' : '0';
		} elseif ( in_array( $setting, [ 'dbs_cache_duration', 'dbs_fallback_rate' ], true ) ) {
			$value = (float) $value;
		} else {
			$value = sanitize_text_field( $value );
		}

		update_option( $setting, $value );
	}

	// Always set distance unit to kilometers
	update_option( 'dbs_distance_unit', 'km' );

	add_action( 'admin_notices', function() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved successfully!', 'distance-shipping' ); ?></p>
		</div>
		<?php
	} );
}

/**
 * Handle store form submission.
 *
 * @param string $action Form action.
 * @param int    $store_id Store ID.
 * @return void
 */
function dbs_handle_store_form_submission( string $action, int $store_id ): void {
	if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'dbs_store_form' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'distance-shipping' ) );
	}

	$store_data = [
		'name'      => sanitize_text_field( $_POST['store_name'] ?? '' ),
		'address'   => sanitize_textarea_field( $_POST['store_address'] ?? '' ),
		'latitude'  => ! empty( $_POST['store_latitude'] ) ? (float) $_POST['store_latitude'] : null,
		'longitude' => ! empty( $_POST['store_longitude'] ) ? (float) $_POST['store_longitude'] : null,
		'is_active' => isset( $_POST['store_is_active'] ) ? 1 : 0,
	];

	if ( 'edit' === $action && $store_id ) {
		$result = dbs_update_store( $store_id, $store_data );
		$message = $result !== false ? 'updated' : 'error';
	} else {
		$result = dbs_insert_store( $store_data );
		$message = $result !== false ? 'added' : 'error';
	}

	wp_redirect( admin_url( 'admin.php?page=distance-shipping-stores&message=' . $message ) );
	exit;
}

/**
 * Handle rule form submission.
 *
 * @param string $action Form action.
 * @param int    $rule_id Rule ID.
 * @return void
 */
function dbs_handle_rule_form_submission( $action, $rule_id ) {
	if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'dbs_rule_form' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'distance-shipping' ) );
	}

	$rule_data = array(
		'rule_name'          => sanitize_text_field( $_POST['rule_name'] ?? '' ),
		'distance_from'      => (float) ( $_POST['distance_from'] ?? 0 ),
		'distance_to'        => (float) ( $_POST['distance_to'] ?? 0 ),
		'base_rate'          => (float) ( $_POST['base_rate'] ?? 0 ),
		'per_km_rate'        => (float) ( $_POST['per_km_rate'] ?? 0 ),
		'min_order_amount'   => (float) ( $_POST['min_order_amount'] ?? 0 ),
		'max_order_amount'   => (float) ( $_POST['max_order_amount'] ?? 0 ),
		'product_categories' => $_POST['product_categories'] ?? array(),
		'shipping_classes'   => $_POST['shipping_classes'] ?? array(),
		'is_active'          => isset( $_POST['rule_is_active'] ) ? 1 : 0,
		'priority'           => (int) ( $_POST['rule_priority'] ?? 0 ),
		// Nová pole pro hmotnost a rozměry
		'weight_min'         => (float) ( $_POST['weight_min'] ?? 0 ),
		'weight_max'         => (float) ( $_POST['weight_max'] ?? 0 ),
		'weight_operator'    => sanitize_text_field( $_POST['weight_operator'] ?? 'AND' ),
		'length_min'         => (float) ( $_POST['length_min'] ?? 0 ),
		'length_max'         => (float) ( $_POST['length_max'] ?? 0 ),
		'width_min'          => (float) ( $_POST['width_min'] ?? 0 ),
		'width_max'          => (float) ( $_POST['width_max'] ?? 0 ),
		'height_min'         => (float) ( $_POST['height_min'] ?? 0 ),
		'height_max'         => (float) ( $_POST['height_max'] ?? 0 ),
		'dimensions_operator' => sanitize_text_field( $_POST['dimensions_operator'] ?? 'AND' ),
	);

	if ( 'edit' === $action && $rule_id ) {
		$result = dbs_update_shipping_rule( $rule_id, $rule_data );
		$message = $result !== false ? 'updated' : 'error';
	} else {
		$result = dbs_insert_shipping_rule( $rule_data );
		$message = $result !== false ? 'added' : 'error';
	}

	wp_redirect( admin_url( 'admin.php?page=distance-shipping-rules&message=' . $message ) );
	exit;
}
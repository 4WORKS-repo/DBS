<?php
/**
 * Plugin Name: Distance Based Shipping for WooCommerce
 * Plugin URI: https://your-website.com
 * Description: Dynamic shipping rates based on distance calculation between store and customer address. Compatible with Avada theme.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://your-website.com
 * Text Domain: distance-shipping
 * Domain Path: /languages
 * Requires at least: 4.7
 * Tested up to: 6.6
 * WC requires at least: 3.0
 * WC tested up to: 9.0
 * Requires PHP: 7.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'DBS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DBS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'DBS_PLUGIN_VERSION', '1.0.0' );
define( 'DBS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Initialize the plugin.
 *
 * @return void
 */
function dbs_init_plugin() {
	try {
		// Debug informace
		error_log( 'DBS: Plugin initialization started' );
		
		// Check if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			error_log( 'DBS: WooCommerce not found' );
			add_action( 'admin_notices', 'dbs_woocommerce_missing_notice' );
			return;
		}

		error_log( 'DBS: WooCommerce found, loading files' );
		
		// Load required files.
		dbs_load_required_files();

		error_log( 'DBS: Files loaded, initializing components' );
		
		// Initialize components with proper timing and error handling.
		dbs_initialize_components();
		
		error_log( 'DBS: Plugin initialization completed' );
	} catch ( Exception $e ) {
		error_log( 'DBS: Fatal error during plugin initialization: ' . $e->getMessage() );
		error_log( 'DBS: Error trace: ' . $e->getTraceAsString() );
	} catch ( Error $e ) {
		error_log( 'DBS: Fatal error during plugin initialization: ' . $e->getMessage() );
		error_log( 'DBS: Error trace: ' . $e->getTraceAsString() );
	}
}
add_action( 'plugins_loaded', 'dbs_init_plugin', 50 ); // Much higher priority to ensure WooCommerce is fully loaded

/**
 * Load plugin text domain.
 *
 * @return void
 */
function dbs_load_textdomain() {
	load_plugin_textdomain(
		'distance-shipping',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'dbs_load_textdomain' );

/**
 * Load required plugin files.
 *
 * @return void
 */
function dbs_load_required_files() {
	$required_files = [
		'includes/functions/database-functions.php',
		'includes/functions/admin-functions.php',
		'includes/functions/shipping-functions.php',
		'includes/functions/distance-functions.php',
		'includes/functions/geocoding-functions.php',
		'includes/functions/ajax-functions.php',
		'includes/functions/translation-functions.php',
		'includes/functions/debug-functions.php',
		'includes/functions/product-functions.php',
		'includes/functions/checkout-functions.php',
		'includes/class-dbs-shipping-method.php',
		'includes/class-dbs-rest-api.php',
		// Váhová oprava pro správné počítání produktů
		'weight-sync-fix.php',
		// Oprava operátorů AND/OR pro váhové podmínky
		'weight-operators-fix.php',
	];

	foreach ( $required_files as $file ) {
		$file_path = DBS_PLUGIN_PATH . $file;
		if ( file_exists( $file_path ) ) {
			try {
				require_once $file_path;
				error_log( 'DBS: Successfully loaded file: ' . $file );
			} catch ( Exception $e ) {
				error_log( 'DBS: Error loading file ' . $file . ': ' . $e->getMessage() );
			} catch ( Error $e ) {
				error_log( 'DBS: Fatal error loading file ' . $file . ': ' . $e->getMessage() );
			}
		} else {
			error_log( 'DBS: Missing file: ' . $file_path );
		}
	}
}



/**
 * Initialize plugin components.
 *
 * @return void
 */
function dbs_initialize_components() {
	try {
		error_log( 'DBS: Starting component initialization' );
		
		// Initialize database.
		if ( function_exists( 'dbs_create_database_tables' ) ) {
			error_log( 'DBS: Creating database tables' );
			dbs_create_database_tables();
		} else {
			error_log( 'DBS: dbs_create_database_tables function not found' );
		}

		// Migrate rules table for new fields.
		if ( function_exists( 'dbs_migrate_rules_table' ) ) {
			error_log( 'DBS: Migrating rules table' );
			dbs_migrate_rules_table();
		} else {
			error_log( 'DBS: dbs_migrate_rules_table function not found' );
		}

		// Initialize admin interface.
		if ( is_admin() && function_exists( 'dbs_init_admin' ) ) {
			error_log( 'DBS: Initializing admin interface' );
			dbs_init_admin();
		} else {
			error_log( 'DBS: Admin interface not initialized - is_admin: ' . (is_admin() ? 'true' : 'false') . ', function exists: ' . (function_exists('dbs_init_admin') ? 'true' : 'false') );
		}

		// Initialize AJAX handlers.
		if ( function_exists( 'dbs_init_ajax_handlers' ) ) {
			error_log( 'DBS: Initializing AJAX handlers' );
			dbs_init_ajax_handlers();
		}

		// Initialize REST API.
		if ( class_exists( 'DBS_REST_API' ) ) {
			error_log( 'DBS: Initializing REST API' );
			new DBS_REST_API();
		}

		// Add WooCommerce integration hooks with much higher priority
		if ( function_exists( 'dbs_add_woocommerce_hooks' ) ) {
			error_log( 'DBS: Adding WooCommerce hooks' );
			add_action( 'init', 'dbs_add_woocommerce_hooks', 50 );
		}

		// Add shipping method to WooCommerce after WooCommerce is fully loaded
		add_action( 'init', 'dbs_register_shipping_method', 20 );
		
		// Also try registering directly if WooCommerce is already loaded
		if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
			error_log( 'DBS: Adding shipping method filter' );
			add_filter( 'woocommerce_shipping_methods', 'dbs_add_shipping_method', 50 );
		}
		
		// Force register shipping method after WooCommerce is fully loaded
		add_action( 'woocommerce_init', 'dbs_force_register_shipping_method', 20 );

		// Enqueue scripts and styles.
		add_action( 'wp_enqueue_scripts', 'dbs_enqueue_frontend_assets' );
		add_action( 'admin_enqueue_scripts', 'dbs_enqueue_admin_assets' );
		
		error_log( 'DBS: Component initialization completed successfully' );
		
	} catch ( Exception $e ) {
		// Log error but don't break the site
		error_log( 'DBS Plugin Initialization Error: ' . $e->getMessage() );
	}
}

/**
 * Register shipping method with WooCommerce.
 *
 * @return void
 */
function dbs_register_shipping_method() {
	// Ensure WooCommerce is fully loaded
	if ( ! class_exists( 'WooCommerce' ) ) {
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: WooCommerce not available during registration' );
		}
		return;
	}
	
	// Check if our shipping method class exists
	if ( ! class_exists( 'DBS_Shipping_Method' ) ) {
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: DBS_Shipping_Method class not available during registration' );
		}
		return;
	}
	
	// Add shipping method filter
	add_filter( 'woocommerce_shipping_methods', 'dbs_add_shipping_method', 50 );
	
	if ( get_option( 'dbs_debug_mode', 0 ) ) {
		error_log( 'DBS: Shipping method registration hook added' );
	}
}

/**
 * Force register shipping method after WooCommerce is fully initialized.
 *
 * @return void
 */
function dbs_force_register_shipping_method() {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) ) {
		return;
	}
	
	// Check if our shipping method class exists
	if ( ! class_exists( 'DBS_Shipping_Method' ) ) {
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: DBS_Shipping_Method class not available during force registration' );
		}
		return;
	}
	
	// Check if our method is already registered by checking the filter
	$current_methods = apply_filters( 'woocommerce_shipping_methods', array() );
	
	if ( ! isset( $current_methods['distance_based'] ) ) {
		// Force add our method to the filter
		add_filter( 'woocommerce_shipping_methods', function( $methods ) {
			$methods['distance_based'] = 'DBS_Shipping_Method';
			return $methods;
		}, 100 );
		
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Shipping method force registered via filter' );
		}
	}
}

/**
 * Add shipping method to WooCommerce.
 *
 * @param array $methods Existing shipping methods.
 * @return array Modified shipping methods.
 */
function dbs_add_shipping_method( $methods ) {
	// Ensure WooCommerce is fully loaded
	if ( ! class_exists( 'WooCommerce' ) ) {
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: WooCommerce not available in add_shipping_method' );
		}
		return $methods;
	}
	
	// Check if our shipping method class exists
	if ( class_exists( 'DBS_Shipping_Method' ) ) {
		$methods['distance_based'] = 'DBS_Shipping_Method';
		
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: Shipping method added to methods array. Total methods: ' . count( $methods ) );
		}
	} else {
		if ( get_option( 'dbs_debug_mode', 0 ) ) {
			error_log( 'DBS: DBS_Shipping_Method class not found in add_shipping_method' );
		}
	}
	
	return $methods;
}

/**
 * Enqueue frontend assets.
 *
 * @return void
 */
function dbs_enqueue_frontend_assets() {
	wp_enqueue_script(
		'dbs-frontend',
		DBS_PLUGIN_URL . 'assets/js/frontend.js',
		array( 'jquery' ),
		defined('DBS_PLUGIN_VERSION') ? DBS_PLUGIN_VERSION : '1.0.0',
		true
	);

	wp_enqueue_style(
		'dbs-frontend',
		DBS_PLUGIN_URL . 'assets/css/frontend.css',
		array(),
		defined('DBS_PLUGIN_VERSION') ? DBS_PLUGIN_VERSION : '1.0.0'
	);

	wp_localize_script(
		'dbs-frontend',
		'dbsAjax',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'dbs_nonce' ),
			'restUrl' => rest_url( 'distance-shipping/v1/' ),
		)
	);
}

/**
 * Enqueue admin assets.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function dbs_enqueue_admin_assets( $hook ) {
	if ( strpos( $hook, 'distance-shipping' ) === false ) {
		return;
	}

	wp_enqueue_script(
		'dbs-admin',
		DBS_PLUGIN_URL . 'assets/js/admin.js',
		array( 'jquery' ),
		defined('DBS_PLUGIN_VERSION') ? DBS_PLUGIN_VERSION : '1.0.0',
		true
	);

	wp_enqueue_style(
		'dbs-admin',
		DBS_PLUGIN_URL . 'assets/css/admin.css',
		array(),
		defined('DBS_PLUGIN_VERSION') ? DBS_PLUGIN_VERSION : '1.0.0'
	);

	wp_localize_script(
		'dbs-admin',
		'dbsAdminAjax',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'dbs_admin_nonce' ),
			'restUrl' => rest_url( 'distance-shipping/v1/' ),
		)
	);
}

/**
 * Plugin activation hook.
 *
 * @return void
 */
function dbs_activate_plugin() {
	// Create database tables.
	if ( function_exists( 'dbs_create_database_tables' ) ) {
		dbs_create_database_tables();
	}

	// Set default options.
	dbs_set_default_options();

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'dbs_activate_plugin' );

/**
 * Plugin deactivation hook.
 *
 * @return void
 */
function dbs_deactivate_plugin() {
	try {
		// Remove any hooks that might interfere with WooCommerce
		remove_filter( 'woocommerce_shipping_methods', 'dbs_add_shipping_method', 50 );
		remove_action( 'init', 'dbs_register_shipping_method', 20 );
		remove_action( 'woocommerce_checkout_update_order_review', 'dbs_handle_address_update', 50 );
		remove_action( 'woocommerce_after_shipping_calculator', 'dbs_display_shipping_info', 50 );
		remove_filter( 'woocommerce_shipping_method_title', 'dbs_enhance_shipping_method_display', 50 );
		remove_action( 'woocommerce_shipping_method_chosen', 'dbs_save_distance_info_to_session', 50 );
		
		// Clear any cached shipping methods safely
		if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) && WC() && WC()->shipping() ) {
			$shipping = WC()->shipping();
			
			// Try different methods to clear cache safely
			if ( method_exists( $shipping, 'reset_shipping_cache' ) ) {
				$shipping->reset_shipping_cache();
			} elseif ( method_exists( $shipping, 'load_shipping_methods' ) ) {
				$shipping->load_shipping_methods();
			} elseif ( method_exists( $shipping, 'get_shipping_methods' ) ) {
				// Force reload by getting methods
				$shipping->get_shipping_methods();
			}
			
			// Clear any session data related to shipping
			if ( WC()->session ) {
				WC()->session->set( 'chosen_shipping_methods', array() );
				WC()->session->set( 'shipping_methods', array() );
			}
		}
		
		// Clear any transients that might interfere
		global $wpdb;
		try {
			$wpdb->query(
				"DELETE FROM {$wpdb->options} 
				 WHERE option_name LIKE '_transient_dbs_%' 
					 OR option_name LIKE '_transient_timeout_dbs_%'
					 OR option_name LIKE '_transient_wc_shipping_%'
					 OR option_name LIKE '_transient_timeout_wc_shipping_%'"
			);
		} catch ( Exception $e ) {
			// Log error but don't break deactivation
			error_log( 'DBS Deactivation: Failed to clear transients: ' . $e->getMessage() );
		}
		
		// Clear any WooCommerce shipping cache
		wp_cache_delete( 'wc_shipping_methods', 'woocommerce' );
		wp_cache_delete( 'wc_shipping_zones', 'woocommerce' );
		
		// Flush rewrite rules.
		flush_rewrite_rules();
		
	} catch ( Exception $e ) {
		// Log error but don't break deactivation
		error_log( 'DBS Plugin Deactivation Error: ' . $e->getMessage() );
	}
}
register_deactivation_hook( __FILE__, 'dbs_deactivate_plugin' );

/**
 * Set default plugin options.
 *
 * @return void
 */
function dbs_set_default_options() {
	$default_options = array(
		'dbs_map_service'     => 'openstreetmap',
		'dbs_enable_caching'  => '1',
		'dbs_cache_duration'  => '24',
		'dbs_fallback_rate'   => '10',
		'dbs_debug_mode'      => '0',
	);

	foreach ( $default_options as $key => $value ) {
		if ( ! get_option( $key ) ) {
			update_option( $key, $value );
		}
	}
}

/**
 * Display WooCommerce missing notice.
 *
 * @return void
 */
function dbs_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			esc_html_e(
				'Distance Based Shipping requires WooCommerce to be installed and activated.',
				'distance-shipping'
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Compatibility functions for older PHP versions.
 */
if ( ! function_exists( 'array_key_first' ) ) {
	/**
	 * Get the first key of an array.
	 *
	 * @param array $arr Input array.
	 * @return mixed|null First key or null.
	 */
	function array_key_first( array $arr ) {
		foreach ( $arr as $key => $unused ) {
			return $key;
		}
		return null;
	}
}

if ( ! function_exists( 'array_key_last' ) ) {
	/**
	 * Get the last key of an array.
	 *
	 * @param array $arr Input array.
	 * @return mixed|null Last key or null.
	 */
	function array_key_last( array $arr ) {
		return array_key_first( array_reverse( $arr, true ) );
	}
}
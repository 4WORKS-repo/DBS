<?php
/**
 * Překladové funkce pro Distance Based Shipping plugin.
 *
 * Soubor: includes/functions/translation-functions.php
 *
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registruje řetězce pro WPML String Translation.
 *
 * @return void
 */
function dbs_register_wpml_strings(): void {
	if ( ! function_exists( 'icl_register_string' ) ) {
		return;
	}

	// Výchozí řetězce pro frontend
	$default_strings = [
		'calculator_title'       => __( 'Kalkulátor dopravních nákladů', 'distance-shipping' ),
		'calculator_placeholder' => __( 'Zadejte úplnou adresu včetně města a PSČ...', 'distance-shipping' ),
		'calculate_button'       => __( 'Vypočítat dopravu', 'distance-shipping' ),
		'nearest_store'          => __( 'Nejbližší obchod:', 'distance-shipping' ),
		'distance_label'         => __( 'Vzdálenost:', 'distance-shipping' ),
		'available_shipping'     => __( 'Dostupné možnosti dopravy', 'distance-shipping' ),
		'no_shipping_available'  => __( 'Pro zadanou adresu nejsou dostupné žádné možnosti dopravy.', 'distance-shipping' ),
		'calculation_error'      => __( 'Chyba při výpočtu', 'distance-shipping' ),
		'enter_address'          => __( 'Zadejte prosím dodací adresu.', 'distance-shipping' ),
		'server_error'           => __( 'Nastala chyba při komunikaci se serverem.', 'distance-shipping' ),
	];

	foreach ( $default_strings as $key => $string ) {
		icl_register_string( 'distance-shipping', $key, $string );
	}

	// Admin řetězce
	$admin_strings = [
		'admin_page_title'    => __( 'Distance Based Shipping', 'distance-shipping' ),
		'stores_page_title'   => __( 'Lokace obchodů', 'distance-shipping' ),
		'rules_page_title'    => __( 'Dopravní pravidla', 'distance-shipping' ),
		'settings_page_title' => __( 'Nastavení', 'distance-shipping' ),
	];

	foreach ( $admin_strings as $key => $string ) {
		icl_register_string( 'distance-shipping-admin', $key, $string );
	}
}
add_action( 'init', 'dbs_register_wpml_strings' );

/**
 * Získá přeložený řetězec z WPML.
 *
 * @param string $key Klíč řetězce.
 * @param string $default Výchozí hodnota.
 * @param string $context Kontext (domain).
 * @return string Přeložený řetězec.
 */
function dbs_get_translated_string( string $key, string $default = '', string $context = 'distance-shipping' ): string {
	if ( function_exists( 'icl_t' ) ) {
		return icl_t( $context, $key, $default );
	}

	return $default ? $default : $key;
}

/**
 * Registruje obchody a pravidla pro překlad v WPML.
 *
 * @return void
 */
function dbs_register_wpml_content(): void {
	if ( ! function_exists( 'wpml_register_single_string' ) ) {
		return;
	}

	// Registrace názvů obchodů
	$stores = dbs_get_stores( false );
	foreach ( $stores as $store ) {
		wpml_register_single_string(
			'distance-shipping',
			'store_name_' . $store->id,
			$store->name
		);
	}

	// Registrace názvů pravidel
	$rules = dbs_get_shipping_rules( false );
	foreach ( $rules as $rule ) {
		wpml_register_single_string(
			'distance-shipping',
			'rule_name_' . $rule->id,
			$rule->rule_name
		);
	}
}
add_action( 'admin_init', 'dbs_register_wpml_content' );

/**
 * Získá přeložený název obchodu.
 *
 * @param object $store Objekt obchodu.
 * @return string Přeložený název.
 */
function dbs_get_translated_store_name( object $store ): string {
	if ( function_exists( 'icl_t' ) ) {
		return icl_t( 'distance-shipping', 'store_name_' . $store->id, $store->name );
	}

	return $store->name;
}

/**
 * Získá přeložený název pravidla.
 *
 * @param object $rule Objekt pravidla.
 * @return string Přeložený název.
 */
function dbs_get_translated_rule_name( object $rule ): string {
	if ( function_exists( 'icl_t' ) ) {
		return icl_t( 'distance-shipping', 'rule_name_' . $rule->id, $rule->rule_name );
	}

	return $rule->rule_name;
}

/**
 * Upraví výstup dopravní metody pro překlad.
 *
 * @param array $rate Dopravní sazba.
 * @return array Upravená sazba.
 */
function dbs_translate_shipping_rate( array $rate ): array {
	if ( isset( $rate['meta_data']['rule_id'] ) ) {
		$rule = dbs_get_shipping_rule( $rate['meta_data']['rule_id'] );
		if ( $rule ) {
			$rate['label'] = dbs_get_translated_rule_name( $rule );
		}
	}

	return $rate;
}
add_filter( 'dbs_shipping_rate_output', 'dbs_translate_shipping_rate' );

/**
 * Polylang kompatibilita - registrace řetězců.
 *
 * @return void
 */
function dbs_register_polylang_strings(): void {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}

	$strings = [
		'Kalkulátor dopravních nákladů',
		'Zadejte úplnou adresu včetně města a PSČ...',
		'Vypočítat dopravu',
		'Nejbližší obchod:',
		'Vzdálenost:',
		'Dostupné možnosti dopravy',
		'Pro zadanou adresu nejsou dostupné žádné možnosti dopravy.',
		'Chyba při výpočtu',
		'Distance Based Shipping',
	];

	foreach ( $strings as $string ) {
		pll_register_string( 'distance-shipping', $string, 'Distance Based Shipping' );
	}
}
add_action( 'init', 'dbs_register_polylang_strings' );

/**
 * Získá přeložený řetězec v Polylang.
 *
 * @param string $string Původní řetězec.
 * @return string Přeložený řetězec.
 */
function dbs_polylang_translate( string $string ): string {
	if ( function_exists( 'pll__' ) ) {
		return pll__( $string );
	}

	return $string;
}

/**
 * Kompatibilita s qTranslate-X.
 *
 * @param string $string Řetězec k překladu.
 * @return string Přeložený řetězec.
 */
function dbs_qtranslate_translate( string $string ): string {
	if ( function_exists( 'qtranxf_use' ) ) {
		return qtranxf_use( get_locale(), $string );
	}

	return $string;
}

/**
 * Univerzální překladová funkce.
 *
 * @param string $string Řetězec k překladu.
 * @param string $context Kontext.
 * @return string Přeložený řetězec.
 */
function dbs_translate( string $string, string $context = 'distance-shipping' ): string {
	// WPML
	if ( function_exists( 'icl_t' ) ) {
		return icl_t( $context, md5( $string ), $string );
	}

	// Polylang
	if ( function_exists( 'pll__' ) ) {
		return pll__( $string );
	}

	// qTranslate-X
	if ( function_exists( 'qtranxf_use' ) ) {
		return qtranxf_use( get_locale(), $string );
	}

	// Fallback na WordPress standardní překlad
	return __( $string, 'distance-shipping' );
}

/**
 * Získá aktuální jazyk.
 *
 * @return string Kód jazyka.
 */
function dbs_get_current_language(): string {
	// WPML
	if ( function_exists( 'icl_get_current_language' ) ) {
		return icl_get_current_language();
	}

	// Polylang
	if ( function_exists( 'pll_current_language' ) ) {
		return pll_current_language();
	}

	// qTranslate-X
	if ( function_exists( 'qtranxf_getLanguage' ) ) {
		return qtranxf_getLanguage();
	}

	// WordPress locale
	$locale = get_locale();
	return substr( $locale, 0, 2 );
}

/**
 * Získá všechny dostupné jazyky.
 *
 * @return array Pole jazyků.
 */
function dbs_get_available_languages(): array {
	// WPML
	if ( function_exists( 'icl_get_languages' ) ) {
		$languages = icl_get_languages( 'skip_missing=0' );
		return array_keys( $languages );
	}

	// Polylang
	if ( function_exists( 'pll_languages_list' ) ) {
		return pll_languages_list();
	}

	// qTranslate-X
	if ( function_exists( 'qtranxf_getSortedLanguages' ) ) {
		return qtranxf_getSortedLanguages();
	}

	return [ dbs_get_current_language() ];
}

/**
 * Překládá obsah pole podle aktuálního jazyka.
 *
 * @param array $data Data k překladu.
 * @param array $translatable_fields Pole s překladatelnými poli.
 * @return array Přeložená data.
 */
function dbs_translate_array_fields( array $data, array $translatable_fields ): array {
	foreach ( $translatable_fields as $field ) {
		if ( isset( $data[ $field ] ) ) {
			$data[ $field ] = dbs_translate( $data[ $field ] );
		}
	}

	return $data;
}

/**
 * Hook pro registraci překladů při ukládání obchodu.
 *
 * @param int   $store_id ID obchodu.
 * @param array $store_data Data obchodu.
 * @return void
 */
function dbs_register_store_translation( int $store_id, array $store_data ): void {
	if ( function_exists( 'wpml_register_single_string' ) ) {
		wpml_register_single_string(
			'distance-shipping',
			'store_name_' . $store_id,
			$store_data['name']
		);
	}
}
add_action( 'dbs_store_saved', 'dbs_register_store_translation', 10, 2 );

/**
 * Hook pro registraci překladů při ukládání pravidla.
 *
 * @param int   $rule_id ID pravidla.
 * @param array $rule_data Data pravidla.
 * @return void
 */
function dbs_register_rule_translation( int $rule_id, array $rule_data ): void {
	if ( function_exists( 'wpml_register_single_string' ) ) {
		wpml_register_single_string(
			'distance-shipping',
			'rule_name_' . $rule_id,
			$rule_data['rule_name']
		);
	}
}
add_action( 'dbs_rule_saved', 'dbs_register_rule_translation', 10, 2 );

/**
 * Shortcode s podporou pro překlady.
 *
 * @param array $atts Atributy shortcode.
 * @return string HTML výstup.
 */
function dbs_shipping_calculator_shortcode( array $atts ): string {
	$atts = shortcode_atts( [
		'title'       => dbs_translate( 'Kalkulátor dopravních nákladů' ),
		'placeholder' => dbs_translate( 'Zadejte úplnou adresu včetně města a PSČ...' ),
		'button_text' => dbs_translate( 'Vypočítat dopravu' ),
		'show_title'  => 'yes',
		'class'       => '',
	], $atts, 'dbs_shipping_calculator' );

	ob_start();
	?>
	<div class="dbs-shipping-calculator <?php echo esc_attr( $atts['class'] ); ?>">
		<?php if ( 'yes' === $atts['show_title'] ) : ?>
			<h3><?php echo esc_html( $atts['title'] ); ?></h3>
		<?php endif; ?>
		<form class="dbs-calculator-form">
			<div class="dbs-calculator-field">
				<label for="dbs-calc-address"><?php echo esc_html( dbs_translate( 'Dodací adresa:' ) ); ?></label>
				<textarea 
					id="dbs-calc-address" 
					name="destination" 
					rows="3" 
					placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
					required
				></textarea>
			</div>
			<button type="submit" class="dbs-calculator-button">
				<?php echo esc_html( $atts['button_text'] ); ?>
			</button>
		</form>
		<div class="dbs-shipping-results" style="display: none;"></div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'dbs_shipping_calculator', 'dbs_shipping_calculator_shortcode' );

/**
 * Český shortcode pro kalkulátor dopravy.
 *
 * @param array $atts Atributy shortcode.
 * @return string HTML výstup.
 */
function dbs_postovne_checker_shortcode( array $atts ): string {
	$atts = shortcode_atts( [
		'title'       => dbs_translate( 'Kalkulátor poštovného' ),
		'placeholder' => dbs_translate( 'Zadejte úplnou adresu včetně města a PSČ...' ),
		'button_text' => dbs_translate( 'Vypočítat poštovné' ),
		'show_title'  => 'yes',
		'class'       => '',
		'style'       => 'default', // default, compact, modern
	], $atts, 'postovne_checker' );

	ob_start();
	?>
	<div class="dbs-shipping-calculator dbs-postovne-checker <?php echo esc_attr( $atts['class'] ); ?> dbs-style-<?php echo esc_attr( $atts['style'] ); ?>">
		<?php if ( 'yes' === $atts['show_title'] ) : ?>
			<div class="dbs-calculator-header">
				<h3 class="dbs-calculator-title"><?php echo esc_html( $atts['title'] ); ?></h3>
				<p class="dbs-calculator-description"><?php echo esc_html( dbs_translate( 'Zadejte adresu a zjistěte cenu dopravy' ) ); ?></p>
			</div>
		<?php endif; ?>
		
		<form class="dbs-calculator-form">
			<div class="dbs-calculator-field">
				<label for="dbs-postovne-address"><?php echo esc_html( dbs_translate( 'Dodací adresa:' ) ); ?></label>
				<textarea 
					id="dbs-postovne-address" 
					name="destination" 
					rows="3" 
					placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
					required
					class="dbs-address-input"
				></textarea>
				<div class="dbs-field-help">
					<small><?php echo esc_html( dbs_translate( 'Například: Brněnská 3163/38, 695 01 Hodonín' ) ); ?></small>
				</div>
			</div>
			
			<div class="dbs-calculator-actions">
				<button type="submit" class="dbs-calculator-button dbs-calculate-btn">
					<span class="dbs-button-icon"></span>
					<span class="dbs-button-text"><?php echo esc_html( $atts['button_text'] ); ?></span>
				</button>
			</div>
		</form>
		
		<div class="dbs-shipping-results" style="display: none;">
			<div class="dbs-results-content"></div>
		</div>
		
		<div class="dbs-calculator-footer">
			<small class="dbs-calculator-note">
				<?php echo esc_html( dbs_translate( 'Cena dopravy se vypočítá na základě vzdálenosti od nejbližšího obchodu.' ) ); ?>
			</small>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'postovne-checker', 'dbs_postovne_checker_shortcode' );
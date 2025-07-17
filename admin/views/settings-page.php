<?php
/**
 * Stránka nastavení pro Distance Based Shipping plugin.
 *
 * Soubor: admin/views/settings-page.php
 *
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Získání aktuálních nastavení.
$map_service = get_option( 'dbs_map_service', 'openstreetmap' );
$google_api_key = get_option( 'dbs_google_api_key', '' );
$bing_api_key = get_option( 'dbs_bing_api_key', '' );
$distance_unit = get_option( 'dbs_distance_unit', 'km' );
$enable_caching = get_option( 'dbs_enable_caching', 1 );
$cache_duration = get_option( 'dbs_cache_duration', 24 );
$fallback_rate = get_option( 'dbs_fallback_rate', 10 );
$debug_mode = get_option( 'dbs_debug_mode', 0 );
$adjust_shipping_for_vat = get_option( 'dbs_adjust_shipping_for_vat', 0 );
?>

<div class="wrap">
	<h1>
		<span class="dashicons dashicons-admin-settings"></span>
		<?php esc_html_e( 'Nastavení Distance Based Shipping', 'distance-shipping' ); ?>
	</h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'dbs_settings' ); ?>
		
		<div class="dbs-settings-sections">
			<!-- Mapové služby -->
			<div class="dbs-settings-section">
				<h2><?php esc_html_e( 'Mapové služby', 'distance-shipping' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Vyberte mapovou službu pro výpočet vzdáleností a geokódování.', 'distance-shipping' ); ?>
				</p>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Mapová služba', 'distance-shipping' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="radio" name="dbs_map_service" value="openstreetmap" <?php checked( $map_service, 'openstreetmap' ); ?> />
									<strong>OpenStreetMap (Nominatim)</strong> - <?php esc_html_e( 'Zdarma, bez API klíče', 'distance-shipping' ); ?>
								</label>
								<br><br>
								<label>
									<input type="radio" name="dbs_map_service" value="google" <?php checked( $map_service, 'google' ); ?> />
									<strong>Google Maps</strong> - <?php esc_html_e( 'Vyžaduje API klíč', 'distance-shipping' ); ?>
								</label>
								<br><br>
								<label>
									<input type="radio" name="dbs_map_service" value="bing" <?php checked( $map_service, 'bing' ); ?> />
									<strong>Bing Maps</strong> - <?php esc_html_e( 'Vyžaduje API klíč', 'distance-shipping' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>

					<tr id="google-api-row" style="display: <?php echo 'google' === $map_service ? 'table-row' : 'none'; ?>;">
						<th scope="row">
							<label for="dbs_google_api_key"><?php esc_html_e( 'Google Maps API klíč', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="text" id="dbs_google_api_key" name="dbs_google_api_key" value="<?php echo esc_attr( $google_api_key ); ?>" class="regular-text" />
							<p class="description">
								<?php
								printf(
									/* translators: %s: API console URL */
									esc_html__( 'Získejte API klíč z %s. Potřebujete povolit Geocoding API a Distance Matrix API.', 'distance-shipping' ),
									'<a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>'
								);
								?>
							</p>
							<button type="button" class="button dbs-test-api-key" data-service="google">
								<?php esc_html_e( 'Otestovat API klíč', 'distance-shipping' ); ?>
							</button>
						</td>
					</tr>

					<tr id="bing-api-row" style="display: <?php echo 'bing' === $map_service ? 'table-row' : 'none'; ?>;">
						<th scope="row">
							<label for="dbs_bing_api_key"><?php esc_html_e( 'Bing Maps API klíč', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="text" id="dbs_bing_api_key" name="dbs_bing_api_key" value="<?php echo esc_attr( $bing_api_key ); ?>" class="regular-text" />
							<p class="description">
								<?php
								printf(
									/* translators: %s: Bing Maps portal URL */
									esc_html__( 'Získejte API klíč z %s.', 'distance-shipping' ),
									'<a href="https://www.bingmapsportalgov.us/" target="_blank">Bing Maps Dev Center</a>'
								);
								?>
							</p>
							<button type="button" class="button dbs-test-api-key" data-service="bing">
								<?php esc_html_e( 'Otestovat API klíč', 'distance-shipping' ); ?>
							</button>
						</td>
					</tr>
				</table>
			</div>

			<!-- Jednotky a výpočty -->
			<div class="dbs-settings-section">
				<h2><?php esc_html_e( 'Jednotky a výpočty', 'distance-shipping' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Jednotka vzdálenosti', 'distance-shipping' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="radio" name="dbs_distance_unit" value="km" checked disabled />
									<?php esc_html_e( 'Kilometry (km)', 'distance-shipping' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Plugin používá pouze kilometry pro všechny výpočty.', 'distance-shipping' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="dbs_fallback_rate"><?php esc_html_e( 'Záložní sazba', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="dbs_fallback_rate" name="dbs_fallback_rate" value="<?php echo esc_attr( $fallback_rate ); ?>" step="0.01" min="0" class="small-text" />
							<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>
							<p class="description">
								<?php esc_html_e( 'Sazba použitá když se nepodaří vypočítat vzdálenost nebo neexistují žádná pravidla. Nastavte na 0 pro vypnutí.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Cache nastavení -->
			<div class="dbs-settings-section">
				<h2><?php esc_html_e( 'Cache nastavení', 'distance-shipping' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Cache zrychluje plugin uložením výsledků geokódování a výpočtů vzdálenosti.', 'distance-shipping' ); ?>
				</p>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Povolit cache', 'distance-shipping' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="checkbox" name="dbs_enable_caching" value="1" <?php checked( $enable_caching, 1 ); ?> />
									<?php esc_html_e( 'Povolit ukládání výsledků do cache', 'distance-shipping' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Doporučeno pro zlepšení výkonu, zejména při používání placených API.', 'distance-shipping' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="dbs_cache_duration"><?php esc_html_e( 'Doba platnosti cache', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="dbs_cache_duration" name="dbs_cache_duration" value="<?php echo esc_attr( $cache_duration ); ?>" min="1" max="168" class="small-text" />
							<?php esc_html_e( 'hodin', 'distance-shipping' ); ?>
							<p class="description">
								<?php esc_html_e( 'Jak dlouho se budou výsledky ukládat v cache (1-168 hodin).', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Správa cache', 'distance-shipping' ); ?></th>
						<td>
							<button type="button" class="button" id="dbs-clear-cache-btn" data-cache-type="all">
								<?php esc_html_e( 'Vymazat veškerou cache', 'distance-shipping' ); ?>
							</button>
							<button type="button" class="button" id="dbs-clear-distance-cache-btn" data-cache-type="distance">
								<?php esc_html_e( 'Vymazat cache vzdáleností', 'distance-shipping' ); ?>
							</button>
							<button type="button" class="button" id="dbs-clear-geocoding-cache-btn" data-cache-type="geocoding">
								<?php esc_html_e( 'Vymazat cache geokódování', 'distance-shipping' ); ?>
							</button>
						</td>
					</tr>
				</table>
			</div>

			<!-- DPH nastavení -->
			<div class="dbs-settings-section">
				<h2><?php esc_html_e( 'DPH nastavení', 'distance-shipping' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Nastavení pro správu DPH u dopravních sazeb.', 'distance-shipping' ); ?>
				</p>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Přizpůsobit ceny pro DPH', 'distance-shipping' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="checkbox" name="dbs_adjust_shipping_for_vat" value="1" <?php checked( $adjust_shipping_for_vat, 1 ); ?> />
									<?php esc_html_e( 'Automaticky přizpůsobit ceny dopravy pro DPH', 'distance-shipping' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Když je zapnuto, plugin automaticky přepočítá ceny dopravy z brutto na netto (dělení 1.21) před odesláním do WooCommerce. Tím se zajistí, že finální cena včetně DPH bude odpovídat zadané hodnotě.', 'distance-shipping' ); ?>
								</p>
								<div class="dbs-vat-info" style="margin-top: 10px; padding: 10px; background: #f0f8ff; border: 1px solid #b3d9ff; border-radius: 4px;">
									<strong><?php esc_html_e( 'Jak to funguje:', 'distance-shipping' ); ?></strong>
									<ul style="margin: 5px 0 0 20px;">
										<li><?php esc_html_e( 'Zadáte cenu jako finální (brutto) - např. 100 Kč', 'distance-shipping' ); ?></li>
										<li><?php esc_html_e( 'Plugin interně použije: 100 ÷ 1.21 = 82.64 Kč', 'distance-shipping' ); ?></li>
										<li><?php esc_html_e( 'WooCommerce přidá DPH: 82.64 × 1.21 = 100 Kč', 'distance-shipping' ); ?></li>
										<li><?php esc_html_e( 'Výsledek: Zákazník vidí přesně 100 Kč', 'distance-shipping' ); ?></li>
									</ul>
								</div>
							</fieldset>
						</td>
					</tr>
				</table>
			</div>

			<!-- Debug a pokročilé -->
			<div class="dbs-settings-section">
				<h2><?php esc_html_e( 'Debug a pokročilé nastavení', 'distance-shipping' ); ?></h2>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Debug režim', 'distance-shipping' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="checkbox" name="dbs_debug_mode" value="1" <?php checked( $debug_mode, 1 ); ?> />
									<?php esc_html_e( 'Povolit debug logování', 'distance-shipping' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Zapisuje detailní informace do error_log pro debugging. Vypněte v produkčním prostředí.', 'distance-shipping' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<?php submit_button( __( 'Uložit nastavení', 'distance-shipping' ) ); ?>
	</form>
</div>

<style>
.dbs-settings-sections {
	max-width: 1000px;
}

.dbs-settings-section {
	background: #fff;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
	padding: 20px;
	margin-bottom: 20px;
}

.dbs-settings-section h2 {
	margin-top: 0;
	padding-bottom: 10px;
	border-bottom: 1px solid #eee;
}

.dbs-settings-section .description {
	margin-top: 0;
	color: #666;
}

.form-table th {
	width: 220px;
}

.dbs-api-test-result {
	margin-top: 10px;
	padding: 10px;
	border-radius: 4px;
}

.dbs-api-test-result.success {
	background: #d4edda;
	border: 1px solid #c3e6cb;
	color: #155724;
}

.dbs-api-test-result.error {
	background: #f8d7da;
	border: 1px solid #f5c6cb;
	color: #721c24;
}

.dbs-cache-buttons {
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
}

@media (max-width: 782px) {
	.dbs-cache-buttons {
		flex-direction: column;
		align-items: flex-start;
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	// Přepínání zobrazení API klíčů podle vybrané služby
	$('input[name="dbs_map_service"]').on('change', function() {
		var selectedService = $(this).val();
		
		$('#google-api-row, #bing-api-row').hide();
		
		if (selectedService === 'google') {
			$('#google-api-row').show();
		} else if (selectedService === 'bing') {
			$('#bing-api-row').show();
		}
	});

	// Test API klíče
	$('.dbs-test-api-key').on('click', function() {
		var $button = $(this);
		var service = $button.data('service');
		var apiKey = $('#dbs_' + service + '_api_key').val();
		
		if (!apiKey) {
			alert('<?php echo esc_js( __( 'Zadejte prosím API klíč.', 'distance-shipping' ) ); ?>');
			return;
		}

		// Odstranění předchozích výsledků
		$button.siblings('.dbs-api-test-result').remove();

		$.ajax({
			url: dbsAdminAjax.ajaxUrl,
			type: 'POST',
			data: {
				action: 'dbs_test_api_key',
				nonce: dbsAdminAjax.nonce,
				service: service,
				api_key: apiKey
			},
			beforeSend: function() {
				$button.prop('disabled', true).text('<?php echo esc_js( __( 'Testuje se...', 'distance-shipping' ) ); ?>');
			},
			success: function(response) {
				var message = response.success ? 
					'<?php echo esc_js( __( 'API klíč je platný!', 'distance-shipping' ) ); ?>' :
					'<?php echo esc_js( __( 'API klíč není platný nebo služba není dostupná.', 'distance-shipping' ) ); ?>';
				
				$button.after('<div class="dbs-api-test-result ' + (response.success ? 'success' : 'error') + '">' + message + '</div>');
			},
			error: function() {
				$button.after('<div class="dbs-api-test-result error"><?php echo esc_js( __( 'Nastala chyba při testování API klíče.', 'distance-shipping' ) ); ?></div>');
			},
			complete: function() {
				$button.prop('disabled', false).text('<?php echo esc_js( __( 'Otestovat API klíč', 'distance-shipping' ) ); ?>');
			}
		});
	});

	// Vymazání cache
	$('[id^="dbs-clear-"][id$="-cache-btn"]').on('click', function() {
		var $button = $(this);
		var cacheType = $button.data('cache-type');
		
						if (!confirm('<?php echo esc_js( __( 'Opravdu chcete vymazat cache?', 'distance-shipping' ) ); ?>')) {
			return;
		}

		$.ajax({
			url: dbsAdminAjax.ajaxUrl,
			type: 'POST',
			data: {
				action: 'dbs_clear_cache',
				nonce: dbsAdminAjax.nonce,
				cache_type: cacheType
			},
			beforeSend: function() {
				$button.prop('disabled', true).text('<?php echo esc_js( __( 'Mazání...', 'distance-shipping' ) ); ?>');
			},
			success: function(response) {
				if (response.success) {
					alert('<?php echo esc_js( __( 'Cache byla úspěšně vymazána.', 'distance-shipping' ) ); ?>');
				} else {
					alert('<?php echo esc_js( __( 'Chyba:', 'distance-shipping' ) ); ?> ' + response.data);
				}
			},
			error: function() {
				alert('<?php echo esc_js( __( 'Nastala chyba při mazání cache.', 'distance-shipping' ) ); ?>');
			},
			complete: function() {
				$button.prop('disabled', false);
				// Obnovení původního textu
				if (cacheType === 'all') {
					$button.text('<?php echo esc_js( __( 'Vymazat veškerou cache', 'distance-shipping' ) ); ?>');
				} else if (cacheType === 'distance') {
					$button.text('<?php echo esc_js( __( 'Vymazat cache vzdáleností', 'distance-shipping' ) ); ?>');
				} else if (cacheType === 'geocoding') {
					$button.text('<?php echo esc_js( __( 'Vymazat cache geokódování', 'distance-shipping' ) ); ?>');
				}
			}
		});
	});

	// Trigger změny při načtení stránky
	$('input[name="dbs_map_service"]:checked').trigger('change');
});
</script>
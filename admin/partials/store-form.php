<?php
/**
 * Formulář pro obchod v Distance Based Shipping plugin.
 *
 * Soubor: admin/partials/store-form.php
 *
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Výchozí hodnoty pro formulář.
$store_name = $store->name ?? '';
$store_address = $store->address ?? '';
$store_latitude = $store->latitude ?? '';
$store_longitude = $store->longitude ?? '';
$store_is_active = isset( $store->is_active ) ? $store->is_active : 1;
$store_id = $store->id ?? 0;
?>

<div class="dbs-store-form-container">
	<form method="post" action="" id="dbs-store-form">
		<?php wp_nonce_field( 'dbs_store_form' ); ?>
		
		<div class="dbs-form-sections">
			<!-- Základní informace -->
			<div class="dbs-form-section">
				<h2><?php esc_html_e( 'Základní informace', 'distance-shipping' ); ?></h2>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="store_name"><?php esc_html_e( 'Název obchodu', 'distance-shipping' ); ?> <span class="required">*</span></label>
						</th>
						<td>
							<input type="text" id="store_name" name="store_name" value="<?php echo esc_attr( $store_name ); ?>" class="regular-text" required />
							<p class="description">
								<?php esc_html_e( 'Zadejte název nebo identifikátor obchodu.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="store_address"><?php esc_html_e( 'Adresa obchodu', 'distance-shipping' ); ?> <span class="required">*</span></label>
						</th>
						<td>
							<textarea id="store_address" name="store_address" rows="4" class="large-text" required><?php echo esc_textarea( $store_address ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Zadejte úplnou adresu obchodu včetně města, PSČ a země. Čím přesnější adresa, tím lepší výsledky geokódování.', 'distance-shipping' ); ?>
							</p>
							<button type="button" class="button" id="dbs-geocode-address">
								<?php esc_html_e( 'Ověřit a geokódovat adresu', 'distance-shipping' ); ?>
							</button>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Stav obchodu', 'distance-shipping' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="checkbox" name="store_is_active" value="1" <?php checked( $store_is_active, 1 ); ?> />
									<?php esc_html_e( 'Obchod je aktivní', 'distance-shipping' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Pouze aktivní obchody se používají pro výpočet dopravních sazeb.', 'distance-shipping' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
				</table>
			</div>

			<!-- Souřadnice -->
			<div class="dbs-form-section">
				<h2><?php esc_html_e( 'Souřadnice (volitelné)', 'distance-shipping' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Souřadnice se automaticky získají z adresy. Můžete je také zadat ručně pro větší přesnost.', 'distance-shipping' ); ?>
				</p>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="store_latitude"><?php esc_html_e( 'Zeměpisná šířka', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="store_latitude" name="store_latitude" value="<?php echo esc_attr( $store_latitude ); ?>" step="0.000001" min="-90" max="90" class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'Zeměpisná šířka v desetinných stupních (-90 až 90).', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="store_longitude"><?php esc_html_e( 'Zeměpisná délka', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="store_longitude" name="store_longitude" value="<?php echo esc_attr( $store_longitude ); ?>" step="0.000001" min="-180" max="180" class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'Zeměpisná délka v desetinných stupních (-180 až 180).', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<div id="dbs-geocoding-result" style="display: none;">
					<h4><?php esc_html_e( 'Výsledek geokódování:', 'distance-shipping' ); ?></h4>
					<div id="dbs-geocoding-result-content"></div>
				</div>
			</div>

			<!-- Mapa (pokud jsou dostupné souřadnice) -->
			<?php if ( $store_latitude && $store_longitude ) : ?>
			<div class="dbs-form-section">
				<h2><?php esc_html_e( 'Poloha na mapě', 'distance-shipping' ); ?></h2>
				<div id="dbs-store-map" style="height: 300px; border: 1px solid #ddd; border-radius: 4px;">
					<p style="text-align: center; padding-top: 120px; color: #666;">
						<?php esc_html_e( 'Mapa se načítá...', 'distance-shipping' ); ?>
					</p>
				</div>
				<p class="description">
					<?php esc_html_e( 'Poloha obchodu na mapě podle zadaných souřadnic.', 'distance-shipping' ); ?>
				</p>
			</div>
			<?php endif; ?>
		</div>

		<div class="dbs-form-actions">
			<?php submit_button( 
				$is_edit ? __( 'Aktualizovat obchod', 'distance-shipping' ) : __( 'Přidat obchod', 'distance-shipping' ),
				'primary',
				'submit',
				false,
				[ 'id' => 'dbs-submit-store' ]
			); ?>
			
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-stores' ) ); ?>" class="button">
				<?php esc_html_e( 'Zrušit', 'distance-shipping' ); ?>
			</a>

			<?php if ( $is_edit ) : ?>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=distance-shipping-stores&action=delete&store_id=' . $store_id ), 'delete_store_' . $store_id ) ); ?>" 
				   class="button button-link-delete" 
				   onclick="return confirm('<?php esc_attr_e( 'Opravdu chcete smazat tento obchod?', 'distance-shipping' ); ?>');">
					<?php esc_html_e( 'Smazat obchod', 'distance-shipping' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</form>
</div>

<style>
.dbs-store-form-container {
	max-width: 900px;
}

.dbs-form-sections {
	display: flex;
	flex-direction: column;
	gap: 20px;
}

.dbs-form-section {
	background: #fff;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
	padding: 20px;
}

.dbs-form-section h2 {
	margin-top: 0;
	padding-bottom: 10px;
	border-bottom: 1px solid #eee;
}

.required {
	color: #d63638;
}

.dbs-form-actions {
	margin-top: 20px;
	padding: 20px;
	background: #f9f9f9;
	border: 1px solid #ddd;
	border-radius: 4px;
	display: flex;
	gap: 10px;
	align-items: center;
	flex-wrap: wrap;
}

.button-link-delete {
	color: #d63638 !important;
	margin-left: auto;
}

.button-link-delete:hover {
	color: #d63638 !important;
}

#dbs-geocoding-result {
	margin-top: 15px;
	padding: 15px;
	background: #f0f8ff;
	border: 1px solid #b3d9ff;
	border-radius: 4px;
}

.dbs-geocoding-success {
	color: #155724;
	background: #d4edda;
	border-color: #c3e6cb;
}

.dbs-geocoding-error {
	color: #721c24;
	background: #f8d7da;
	border-color: #f5c6cb;
}

@media (max-width: 782px) {
	.dbs-form-actions {
		flex-direction: column;
		align-items: stretch;
	}
	
	.dbs-form-actions .button {
		width: 100%;
		text-align: center;
	}
	
	.button-link-delete {
		margin-left: 0;
		order: 3;
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	// Geokódování adresy
	$('#dbs-geocode-address').on('click', function() {
		var $button = $(this);
		var address = $('#store_address').val().trim();
		
		if (!address) {
			alert('<?php echo esc_js( __( 'Zadejte prosím adresu obchodu.', 'distance-shipping' ) ); ?>');
			return;
		}

		$.ajax({
			url: dbsAdminAjax.ajaxUrl,
			type: 'POST',
			data: {
				action: 'dbs_geocode_address',
				nonce: dbsAdminAjax.nonce,
				address: address
			},
			beforeSend: function() {
				$button.prop('disabled', true).text('<?php echo esc_js( __( 'Geokóduje se...', 'distance-shipping' ) ); ?>');
				$('#dbs-geocoding-result').hide();
			},
			success: function(response) {
				if (response.success) {
					// Aktualizace polí souřadnic
					$('#store_latitude').val(response.data.latitude);
					$('#store_longitude').val(response.data.longitude);
					
					// Zobrazení výsledku
					$('#dbs-geocoding-result-content').html(
						'<p class="dbs-geocoding-success"><strong><?php echo esc_js( __( 'Úspěch!', 'distance-shipping' ) ); ?></strong> ' + response.data.message + '</p>' +
						'<p><strong><?php echo esc_js( __( 'Zeměpisná šířka:', 'distance-shipping' ) ); ?></strong> ' + response.data.latitude + '</p>' +
						'<p><strong><?php echo esc_js( __( 'Zeměpisná délka:', 'distance-shipping' ) ); ?></strong> ' + response.data.longitude + '</p>'
					);
					$('#dbs-geocoding-result').show();
				} else {
					$('#dbs-geocoding-result-content').html(
						'<p class="dbs-geocoding-error"><strong><?php echo esc_js( __( 'Chyba!', 'distance-shipping' ) ); ?></strong> ' + response.data + '</p>'
					);
					$('#dbs-geocoding-result').show();
				}
			},
			error: function() {
				$('#dbs-geocoding-result-content').html(
					'<p class="dbs-geocoding-error"><strong><?php echo esc_js( __( 'Chyba!', 'distance-shipping' ) ); ?></strong> <?php echo esc_js( __( 'Nastala chyba při komunikaci se serverem.', 'distance-shipping' ) ); ?></p>'
				);
				$('#dbs-geocoding-result').show();
			},
			complete: function() {
				$button.prop('disabled', false).text('<?php echo esc_js( __( 'Ověřit a geokódovat adresu', 'distance-shipping' ) ); ?>');
			}
		});
	});

	// Validace formuláře před odesláním
	$('#dbs-store-form').on('submit', function(e) {
		var storeName = $('#store_name').val().trim();
		var storeAddress = $('#store_address').val().trim();
		
		if (!storeName) {
			alert('<?php echo esc_js( __( 'Název obchodu je povinný.', 'distance-shipping' ) ); ?>');
			$('#store_name').focus();
			e.preventDefault();
			return false;
		}
		
		if (!storeAddress) {
			alert('<?php echo esc_js( __( 'Adresa obchodu je povinná.', 'distance-shipping' ) ); ?>');
			$('#store_address').focus();
			e.preventDefault();
			return false;
		}

		// Validace souřadnic pokud jsou vyplněné
		var latitude = $('#store_latitude').val();
		var longitude = $('#store_longitude').val();
		
		if (latitude && (latitude < -90 || latitude > 90)) {
			alert('<?php echo esc_js( __( 'Zeměpisná šířka musí být mezi -90 a 90.', 'distance-shipping' ) ); ?>');
			$('#store_latitude').focus();
			e.preventDefault();
			return false;
		}
		
		if (longitude && (longitude < -180 || longitude > 180)) {
			alert('<?php echo esc_js( __( 'Zeměpisná délka musí být mezi -180 a 180.', 'distance-shipping' ) ); ?>');
			$('#store_longitude').focus();
			e.preventDefault();
			return false;
		}
	});

	// Inicializace mapy pokud jsou dostupné souřadnice
	<?php if ( $store_latitude && $store_longitude ) : ?>
	function initStoreMap() {
		if (typeof L !== 'undefined') {
			var lat = <?php echo esc_js( $store_latitude ); ?>;
			var lng = <?php echo esc_js( $store_longitude ); ?>;
			
			var map = L.map('dbs-store-map').setView([lat, lng], 15);
			
			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				attribution: '© OpenStreetMap contributors'
			}).addTo(map);
			
			L.marker([lat, lng]).addTo(map)
				.bindPopup('<?php echo esc_js( $store_name ); ?>')
				.openPopup();
		} else {
			// Fallback pokud Leaflet není dostupný
			$('#dbs-store-map').html(
				'<p style="text-align: center; padding-top: 120px; color: #666;">' +
				'<?php echo esc_js( __( 'Souřadnice:', 'distance-shipping' ) ); ?> ' + 
				'<?php echo esc_js( number_format( $store_latitude, 6 ) ); ?>, ' +
				'<?php echo esc_js( number_format( $store_longitude, 6 ) ); ?>' +
				'</p>'
			);
		}
	}

	// Načtení Leaflet knihovny pro mapu
	if (!window.L) {
		$('<link>')
			.appendTo('head')
			.attr({
				type: 'text/css',
				rel: 'stylesheet',
				href: 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.css'
			});
		
		$.getScript('https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', function() {
			initStoreMap();
		});
	} else {
		initStoreMap();
	}
	<?php endif; ?>

	// Auto-save při změně souřadnic
	$('#store_latitude, #store_longitude').on('change', function() {
		var latitude = $('#store_latitude').val();
		var longitude = $('#store_longitude').val();
		
		if (latitude && longitude) {
			// Zde by bylo možné aktualizovat mapu v reálném čase
			console.log('Souřadnice změněny:', latitude, longitude);
		}
	});
});
</script>
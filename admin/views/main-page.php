<?php
/**
 * Hlavní admin stránka pro Distance Based Shipping plugin.
 *
 * Soubor: admin/views/main-page.php
 *
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Získání statistik pluginu.
$stats = dbs_get_plugin_statistics();
?>

<div class="wrap">
	<h1>
		<span class="dashicons dashicons-location-alt"></span>
		<?php esc_html_e( 'Distance Based Shipping', 'distance-shipping' ); ?>
	</h1>

	<div class="dbs-admin-content">
		<!-- Přehled -->
		<div class="dbs-overview-section">
			<h2><?php esc_html_e( 'Přehled', 'distance-shipping' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Tento plugin vypočítává dopravní sazby na základě vzdálenosti mezi vašimi obchody a adresami zákazníků.', 'distance-shipping' ); ?>
			</p>
			
			<!-- Statistiky -->
			<div class="dbs-stats-grid">
				<div class="dbs-stat-card">
					<div class="dbs-stat-number"><?php echo esc_html( $stats['active_stores'] ); ?></div>
					<div class="dbs-stat-label"><?php esc_html_e( 'Aktivní obchody', 'distance-shipping' ); ?></div>
				</div>
				
				<div class="dbs-stat-card">
					<div class="dbs-stat-number"><?php echo esc_html( $stats['active_rules'] ); ?></div>
					<div class="dbs-stat-label"><?php esc_html_e( 'Aktivní pravidla', 'distance-shipping' ); ?></div>
				</div>
				
				<div class="dbs-stat-card">
					<div class="dbs-stat-number"><?php echo esc_html( $stats['map_service'] ); ?></div>
					<div class="dbs-stat-label"><?php esc_html_e( 'Mapová služba', 'distance-shipping' ); ?></div>
				</div>
				
				<div class="dbs-stat-card">
					<div class="dbs-stat-number"><?php echo esc_html( strtoupper( $stats['distance_unit'] ) ); ?></div>
					<div class="dbs-stat-label"><?php esc_html_e( 'Jednotka vzdálenosti', 'distance-shipping' ); ?></div>
				</div>
			</div>
		</div>

		<!-- Rychlé akce -->
		<div class="dbs-quick-actions-section">
			<h2><?php esc_html_e( 'Rychlé akce', 'distance-shipping' ); ?></h2>
			
			<div class="dbs-actions-grid">
				<div class="dbs-action-card">
					<h3><?php esc_html_e( 'Nastavení', 'distance-shipping' ); ?></h3>
					<p><?php esc_html_e( 'Konfigurujte mapové služby, jednotky a další základní nastavení.', 'distance-shipping' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-settings' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Otevřít nastavení', 'distance-shipping' ); ?>
					</a>
				</div>
				
				<div class="dbs-action-card">
					<h3><?php esc_html_e( 'Obchody', 'distance-shipping' ); ?></h3>
					<p><?php esc_html_e( 'Spravujte lokace vašich obchodů a jejich adresy.', 'distance-shipping' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-stores' ) ); ?>" class="button">
						<?php esc_html_e( 'Spravovat obchody', 'distance-shipping' ); ?>
					</a>
				</div>
				
				<div class="dbs-action-card">
					<h3><?php esc_html_e( 'Dopravní pravidla', 'distance-shipping' ); ?></h3>
					<p><?php esc_html_e( 'Vytvořte a upravte pravidla pro výpočet dopravních sazeb.', 'distance-shipping' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-rules' ) ); ?>" class="button">
						<?php esc_html_e( 'Spravovat pravidla', 'distance-shipping' ); ?>
					</a>
				</div>
				
				<div class="dbs-action-card">
					<h3><?php esc_html_e( 'Test vzdálenosti', 'distance-shipping' ); ?></h3>
					<p><?php esc_html_e( 'Otestujte výpočet vzdálenosti mezi dvěma adresami.', 'distance-shipping' ); ?></p>
					<button type="button" class="button" id="dbs-test-distance-btn">
						<?php esc_html_e( 'Otevřít test', 'distance-shipping' ); ?>
					</button>
				</div>
			</div>
		</div>

		<!-- Pomocné informace -->
		<div class="dbs-help-section">
			<h2><?php esc_html_e( 'Potřebujete pomoc?', 'distance-shipping' ); ?></h2>
			<div class="dbs-help-grid">
				<div class="dbs-help-card">
					<h4><?php esc_html_e( 'Jak plugin funguje', 'distance-shipping' ); ?></h4>
					<ol>
						<li><?php esc_html_e( 'Nastavte lokace vašich obchodů', 'distance-shipping' ); ?></li>
						<li><?php esc_html_e( 'Vytvořte dopravní pravidla založená na vzdálenosti', 'distance-shipping' ); ?></li>
						<li><?php esc_html_e( 'Plugin automaticky vypočítá sazby při objednávce', 'distance-shipping' ); ?></li>
					</ol>
				</div>
				
				<div class="dbs-help-card">
					<h4><?php esc_html_e( 'Podporované mapové služby', 'distance-shipping' ); ?></h4>
					<ul>
						<li><strong>OpenStreetMap:</strong> <?php esc_html_e( 'Zdarma, bez API klíče', 'distance-shipping' ); ?></li>
						<li><strong>Google Maps:</strong> <?php esc_html_e( 'Vyžaduje API klíč', 'distance-shipping' ); ?></li>
						<li><strong>Bing Maps:</strong> <?php esc_html_e( 'Vyžaduje API klíč', 'distance-shipping' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Modal pro test vzdálenosti -->
<div id="dbs-test-distance-modal" class="dbs-modal" style="display: none;">
	<div class="dbs-modal-content">
		<div class="dbs-modal-header">
			<h3><?php esc_html_e( 'Test výpočtu vzdálenosti', 'distance-shipping' ); ?></h3>
			<button type="button" class="dbs-modal-close">&times;</button>
		</div>
		
		<div class="dbs-modal-body">
			<form id="dbs-test-distance-form">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="test-origin"><?php esc_html_e( 'Výchozí adresa', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<textarea id="test-origin" name="origin" rows="3" class="regular-text" placeholder="<?php esc_attr_e( 'Zadejte výchozí adresu...', 'distance-shipping' ); ?>"></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="test-destination"><?php esc_html_e( 'Cílová adresa', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<textarea id="test-destination" name="destination" rows="3" class="regular-text" placeholder="<?php esc_attr_e( 'Zadejte cílovou adresu...', 'distance-shipping' ); ?>"></textarea>
						</td>
					</tr>
				</table>
				
				<div class="dbs-modal-actions">
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Vypočítat vzdálenost', 'distance-shipping' ); ?>
					</button>
					<button type="button" class="button dbs-modal-close">
						<?php esc_html_e( 'Zrušit', 'distance-shipping' ); ?>
					</button>
				</div>
			</form>
			
			<div id="dbs-test-result" style="display: none;">
				<h4><?php esc_html_e( 'Výsledek:', 'distance-shipping' ); ?></h4>
				<div id="dbs-test-result-content"></div>
			</div>
		</div>
	</div>
</div>

<style>
.dbs-admin-content {
	max-width: 1200px;
}

.dbs-overview-section,
.dbs-quick-actions-section,
.dbs-help-section {
	background: #fff;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
	padding: 20px;
	margin-bottom: 20px;
}

.dbs-stats-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.dbs-stat-card {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
	padding: 20px;
	border-radius: 8px;
	text-align: center;
}

.dbs-stat-number {
	font-size: 2.5em;
	font-weight: bold;
	margin-bottom: 5px;
}

.dbs-stat-label {
	font-size: 0.9em;
	opacity: 0.9;
}

.dbs-actions-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.dbs-action-card {
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	background: #fafafa;
}

.dbs-action-card h3 {
	margin-top: 0;
	color: #23282d;
}

.dbs-action-card p {
	color: #666;
	margin-bottom: 15px;
}

.dbs-help-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.dbs-help-card {
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	background: #f9f9f9;
}

.dbs-help-card h4 {
	margin-top: 0;
	color: #23282d;
}

.dbs-help-card ul,
.dbs-help-card ol {
	color: #666;
}

.dbs-modal {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: rgba(0, 0, 0, 0.5);
	z-index: 100000;
}

.dbs-modal-content {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	background: white;
	border-radius: 8px;
	width: 90%;
	max-width: 600px;
	max-height: 80vh;
	overflow-y: auto;
}

.dbs-modal-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 20px;
	border-bottom: 1px solid #ddd;
}

.dbs-modal-header h3 {
	margin: 0;
}

.dbs-modal-close {
	background: none;
	border: none;
	font-size: 24px;
	cursor: pointer;
	color: #666;
}

.dbs-modal-body {
	padding: 20px;
}

.dbs-modal-actions {
	margin-top: 20px;
	display: flex;
	gap: 10px;
}

#dbs-test-result {
	margin-top: 20px;
	padding: 15px;
	background: #f0f8ff;
	border: 1px solid #b3d9ff;
	border-radius: 4px;
}
</style>

<script>
jQuery(document).ready(function($) {
	// Otevření modalu pro test vzdálenosti
	$('#dbs-test-distance-btn').on('click', function() {
		$('#dbs-test-distance-modal').show();
	});

	// Zavření modalu
	$('.dbs-modal-close').on('click', function() {
		$('#dbs-test-distance-modal').hide();
		$('#dbs-test-result').hide();
	});

	// Kliknutí mimo modal
	$('#dbs-test-distance-modal').on('click', function(e) {
		if (e.target === this) {
			$(this).hide();
			$('#dbs-test-result').hide();
		}
	});

	// Odeslání formuláře pro test vzdálenosti
	$('#dbs-test-distance-form').on('submit', function(e) {
		e.preventDefault();

		var origin = $('#test-origin').val();
		var destination = $('#test-destination').val();

		if (!origin || !destination) {
			alert('<?php echo esc_js( __( 'Zadejte prosím obě adresy.', 'distance-shipping' ) ); ?>');
			return;
		}

		$.ajax({
			url: dbsAdminAjax.ajaxUrl,
			type: 'POST',
			data: {
				action: 'dbs_test_distance',
				nonce: dbsAdminAjax.nonce,
				origin: origin,
				destination: destination
			},
			beforeSend: function() {
				$('#dbs-test-distance-form button[type="submit"]').prop('disabled', true).text('<?php echo esc_js( __( 'Počítám...', 'distance-shipping' ) ); ?>');
			},
			success: function(response) {
				if (response.success) {
					$('#dbs-test-result-content').html(
						'<p><strong><?php echo esc_js( __( 'Vzdálenost:', 'distance-shipping' ) ); ?></strong> ' + response.data.formatted_distance + '</p>' +
						'<p><strong><?php echo esc_js( __( 'Jednotka:', 'distance-shipping' ) ); ?></strong> ' + response.data.distance_unit + '</p>'
					);
					$('#dbs-test-result').show();
				} else {
					alert('<?php echo esc_js( __( 'Chyba:', 'distance-shipping' ) ); ?> ' + response.data);
				}
			},
			error: function() {
				alert('<?php echo esc_js( __( 'Nastala chyba při komunikaci se serverem.', 'distance-shipping' ) ); ?>');
			},
			complete: function() {
				$('#dbs-test-distance-form button[type="submit"]').prop('disabled', false).text('<?php echo esc_js( __( 'Vypočítat vzdálenost', 'distance-shipping' ) ); ?>');
			}
		});
	});
});
</script>
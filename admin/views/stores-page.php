<?php
/**
 * Stránka správy obchodů pro Distance Based Shipping plugin.
 *
 * Soubor: admin/views/stores-page.php
 *
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Získání aktuální akce.
$action = $_GET['action'] ?? 'list';
$store_id = (int) ( $_GET['store_id'] ?? 0 );
$message = $_GET['message'] ?? '';

// Zobrazení zpráv.
if ( $message ) {
	$message_text = '';
	$message_type = 'success';
	
	switch ( $message ) {
		case 'added':
			$message_text = __( 'Obchod byl úspěšně přidán.', 'distance-shipping' );
			break;
		case 'updated':
			$message_text = __( 'Obchod byl úspěšně aktualizován.', 'distance-shipping' );
			break;
		case 'deleted':
			$message_text = __( 'Obchod byl úspěšně smazán.', 'distance-shipping' );
			break;
		case 'error':
			$message_text = __( 'Nastala chyba při zpracování požadavku.', 'distance-shipping' );
			$message_type = 'error';
			break;
	}
	
	if ( $message_text ) {
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', 
			esc_attr( $message_type ), 
			esc_html( $message_text ) 
		);
	}
}

// Zobrazení podle akce.
if ( 'list' === $action ) {
	dbs_render_stores_list();
} elseif ( in_array( $action, [ 'add', 'edit' ], true ) ) {
	$store = null;
	if ( 'edit' === $action && $store_id ) {
		$store = dbs_get_store( $store_id );
		if ( ! $store ) {
			wp_die( esc_html__( 'Obchod nebyl nalezen.', 'distance-shipping' ) );
		}
	}
	dbs_render_store_form( $store );
}

/**
 * Vykreslí seznam obchodů.
 *
 * @return void
 */
function dbs_render_stores_list(): void {
	$stores = dbs_get_stores( false ); // Získání všech obchodů včetně neaktivních
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">
			<span class="dashicons dashicons-store"></span>
			<?php esc_html_e( 'Lokace obchodů', 'distance-shipping' ); ?>
		</h1>
		
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-stores&action=add' ) ); ?>" class="page-title-action">
			<?php esc_html_e( 'Přidat nový obchod', 'distance-shipping' ); ?>
		</a>

		<hr class="wp-header-end">

		<div class="dbs-stores-controls">
			<div class="dbs-bulk-actions">
				<button type="button" class="button" id="dbs-update-all-coordinates">
					<?php esc_html_e( 'Aktualizovat všechny souřadnice', 'distance-shipping' ); ?>
				</button>
				<span class="description">
					<?php esc_html_e( 'Geokóduje všechny adresy obchodů a aktualizuje jejich souřadnice.', 'distance-shipping' ); ?>
				</span>
			</div>
		</div>

		<?php if ( empty( $stores ) ) : ?>
			<div class="dbs-empty-state">
				<div class="dbs-empty-state-icon">
					<span class="dashicons dashicons-store"></span>
				</div>
				<h3><?php esc_html_e( 'Žádné obchody', 'distance-shipping' ); ?></h3>
				<p><?php esc_html_e( 'Ještě jste nepřidali žádné obchody. Přidejte první obchod pro začátek.', 'distance-shipping' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-stores&action=add' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Přidat první obchod', 'distance-shipping' ); ?>
				</a>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-name column-primary">
							<?php esc_html_e( 'Název obchodu', 'distance-shipping' ); ?>
						</th>
						<th scope="col" class="manage-column column-address">
							<?php esc_html_e( 'Adresa', 'distance-shipping' ); ?>
						</th>
						<th scope="col" class="manage-column column-coordinates">
							<?php esc_html_e( 'Souřadnice', 'distance-shipping' ); ?>
						</th>
						<th scope="col" class="manage-column column-status">
							<?php esc_html_e( 'Stav', 'distance-shipping' ); ?>
						</th>
						<th scope="col" class="manage-column column-actions">
							<?php esc_html_e( 'Akce', 'distance-shipping' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $stores as $store ) : ?>
						<tr>
							<td class="column-name column-primary">
								<strong>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-stores&action=edit&store_id=' . $store->id ) ); ?>">
										<?php echo esc_html( $store->name ); ?>
									</a>
								</strong>
								<div class="row-actions">
									<span class="edit">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-stores&action=edit&store_id=' . $store->id ) ); ?>">
											<?php esc_html_e( 'Upravit', 'distance-shipping' ); ?>
										</a>
									</span>
									|
									<span class="trash">
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=distance-shipping-stores&action=delete&store_id=' . $store->id ), 'delete_store_' . $store->id ) ); ?>" 
										   onclick="return confirm('<?php esc_attr_e( 'Opravdu chcete smazat tento obchod?', 'distance-shipping' ); ?>');">
											<?php esc_html_e( 'Smazat', 'distance-shipping' ); ?>
										</a>
									</span>
									|
									<span class="coordinates">
										<a href="#" class="dbs-update-coordinates" data-store-id="<?php echo esc_attr( $store->id ); ?>">
											<?php esc_html_e( 'Aktualizovat souřadnice', 'distance-shipping' ); ?>
										</a>
									</span>
								</div>
								<button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e( 'Zobrazit více detailů', 'distance-shipping' ); ?></span></button>
							</td>
							<td class="column-address" data-colname="<?php esc_attr_e( 'Adresa', 'distance-shipping' ); ?>">
								<?php echo esc_html( $store->address ); ?>
							</td>
							<td class="column-coordinates" data-colname="<?php esc_attr_e( 'Souřadnice', 'distance-shipping' ); ?>">
								<?php if ( $store->latitude && $store->longitude ) : ?>
									<span class="dbs-coordinates" data-store-id="<?php echo esc_attr( $store->id ); ?>">
										<?php echo esc_html( number_format( $store->latitude, 6 ) ); ?>,
										<?php echo esc_html( number_format( $store->longitude, 6 ) ); ?>
									</span>
								<?php else : ?>
									<span class="dbs-no-coordinates">
										<?php esc_html_e( 'Nejsou dostupné', 'distance-shipping' ); ?>
									</span>
								<?php endif; ?>
							</td>
							<td class="column-status" data-colname="<?php esc_attr_e( 'Stav', 'distance-shipping' ); ?>">
								<?php if ( $store->is_active ) : ?>
									<span class="dbs-status dbs-status-active">
										<?php esc_html_e( 'Aktivní', 'distance-shipping' ); ?>
									</span>
								<?php else : ?>
									<span class="dbs-status dbs-status-inactive">
										<?php esc_html_e( 'Neaktivní', 'distance-shipping' ); ?>
									</span>
								<?php endif; ?>
							</td>
							<td class="column-actions" data-colname="<?php esc_attr_e( 'Akce', 'distance-shipping' ); ?>">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-stores&action=edit&store_id=' . $store->id ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Upravit', 'distance-shipping' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<style>
	.dbs-stores-controls {
		margin: 20px 0;
		padding: 15px;
		background: #f9f9f9;
		border: 1px solid #ddd;
		border-radius: 4px;
	}

	.dbs-bulk-actions {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}

	.dbs-empty-state {
		text-align: center;
		padding: 60px 20px;
		background: #fff;
		border: 1px solid #ddd;
		border-radius: 4px;
		margin-top: 20px;
	}

	.dbs-empty-state-icon {
		font-size: 64px;
		color: #ddd;
		margin-bottom: 20px;
	}

	.dbs-empty-state h3 {
		margin: 0 0 10px 0;
		color: #555;
	}

	.dbs-status {
		padding: 4px 8px;
		border-radius: 3px;
		font-size: 12px;
		font-weight: bold;
	}

	.dbs-status-active {
		background: #d4edda;
		color: #155724;
	}

	.dbs-status-inactive {
		background: #f8d7da;
		color: #721c24;
	}

	.dbs-no-coordinates {
		color: #999;
		font-style: italic;
	}

	.column-coordinates {
		font-family: monospace;
	}

	@media (max-width: 782px) {
		.dbs-bulk-actions {
			flex-direction: column;
			align-items: flex-start;
		}
	}
	</style>

	<script>
	jQuery(document).ready(function($) {
		// Aktualizace všech souřadnic
		$('#dbs-update-all-coordinates').on('click', function() {
			var $button = $(this);
			
			if (!confirm('<?php echo esc_js( __( 'Opravdu chcete aktualizovat souřadnice všech obchodů? Toto může chvíli trvat.', 'distance-shipping' ) ); ?>')) {
				return;
			}

			$.ajax({
				url: dbsAdminAjax.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dbs_update_store_coordinates',
					nonce: dbsAdminAjax.nonce
				},
				beforeSend: function() {
					$button.prop('disabled', true).text('<?php echo esc_js( __( 'Aktualizuje se...', 'distance-shipping' ) ); ?>');
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						location.reload();
					} else {
						alert('<?php echo esc_js( __( 'Chyba:', 'distance-shipping' ) ); ?> ' + response.data.message);
					}
				},
				error: function() {
					alert('<?php echo esc_js( __( 'Nastala chyba při komunikaci se serverem.', 'distance-shipping' ) ); ?>');
				},
				complete: function() {
					$button.prop('disabled', false).text('<?php echo esc_js( __( 'Aktualizovat všechny souřadnice', 'distance-shipping' ) ); ?>');
				}
			});
		});

		// Aktualizace souřadnic jednotlivého obchodu
		$('.dbs-update-coordinates').on('click', function(e) {
			e.preventDefault();
			
			var $link = $(this);
			var storeId = $link.data('store-id');
			var $coordinates = $('.dbs-coordinates[data-store-id="' + storeId + '"]');

			$.ajax({
				url: dbsAdminAjax.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dbs_update_store_coordinates',
					nonce: dbsAdminAjax.nonce,
					store_id: storeId
				},
				beforeSend: function() {
					$link.text('<?php echo esc_js( __( 'Aktualizuje se...', 'distance-shipping' ) ); ?>');
				},
				success: function(response) {
					if (response.success) {
						$coordinates.text(parseFloat(response.data.latitude).toFixed(6) + ', ' + parseFloat(response.data.longitude).toFixed(6));
						alert('<?php echo esc_js( __( 'Souřadnice byly úspěšně aktualizovány.', 'distance-shipping' ) ); ?>');
					} else {
						alert('<?php echo esc_js( __( 'Chyba:', 'distance-shipping' ) ); ?> ' + response.data);
					}
				},
				error: function() {
					alert('<?php echo esc_js( __( 'Nastala chyba při komunikaci se serverem.', 'distance-shipping' ) ); ?>');
				},
				complete: function() {
					$link.text('<?php echo esc_js( __( 'Aktualizovat souřadnice', 'distance-shipping' ) ); ?>');
				}
			});
		});
	});
	</script>
	<?php
}

/**
 * Vykreslí formulář pro obchod.
 *
 * @param object|null $store Objekt obchodu nebo null pro nový.
 * @return void
 */
function dbs_render_store_form( ?object $store ): void {
	$is_edit = ! is_null( $store );
	$page_title = $is_edit ? __( 'Upravit obchod', 'distance-shipping' ) : __( 'Přidat nový obchod', 'distance-shipping' );
	$form_action = $is_edit ? 'edit' : 'add';
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">
			<span class="dashicons dashicons-store"></span>
			<?php echo esc_html( $page_title ); ?>
		</h1>
		
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-stores' ) ); ?>" class="page-title-action">
			<?php esc_html_e( 'Zpět na seznam', 'distance-shipping' ); ?>
		</a>

		<hr class="wp-header-end">

		<?php include DBS_PLUGIN_PATH . 'admin/partials/store-form.php'; ?>
	</div>
	<?php
}
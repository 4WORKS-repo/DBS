<?php
/**
 * Stránka správy dopravních pravidel pro Distance Based Shipping plugin.
 *
 * Soubor: admin/views/rules-page.php
 *
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Získání aktuální akce.
$action = $_GET['action'] ?? 'list';
$rule_id = (int) ( $_GET['rule_id'] ?? 0 );
$message = $_GET['message'] ?? '';

// Zobrazení zpráv.
if ( $message ) {
	$message_text = '';
	$message_type = 'success';
	
	switch ( $message ) {
		case 'added':
			$message_text = __( 'Pravidlo bylo úspěšně přidáno.', 'distance-shipping' );
			break;
		case 'updated':
			$message_text = __( 'Pravidlo bylo úspěšně aktualizováno.', 'distance-shipping' );
			break;
		case 'deleted':
			$message_text = __( 'Pravidlo bylo úspěšně smazáno.', 'distance-shipping' );
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
	dbs_render_rules_list();
} elseif ( in_array( $action, [ 'add', 'edit' ], true ) ) {
	$rule = null;
	if ( 'edit' === $action && $rule_id ) {
		$rule = dbs_get_shipping_rule( $rule_id );
		if ( ! $rule ) {
			wp_die( esc_html__( 'Pravidlo nebylo nalezeno.', 'distance-shipping' ) );
		}
	}
	dbs_render_rule_form( $rule );
}

/**
 * Vykreslí seznam dopravních pravidel.
 *
 * @return void
 */
function dbs_render_rules_list(): void {
	$rules = dbs_get_shipping_rules( false ); // Získání všech pravidel včetně neaktivních
	$distance_unit = get_option( 'dbs_distance_unit', 'km' );
	$currency_symbol = get_woocommerce_currency_symbol();
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">
			<span class="dashicons dashicons-admin-settings"></span>
			<?php esc_html_e( 'Dopravní pravidla', 'distance-shipping' ); ?>
		</h1>
		
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-rules&action=add' ) ); ?>" class="page-title-action">
			<?php esc_html_e( 'Přidat nové pravidlo', 'distance-shipping' ); ?>
		</a>

		<hr class="wp-header-end">

		<div class="dbs-rules-help">
			<p class="description">
				<?php esc_html_e( 'Dopravní pravidla definují jak se vypočítávají sazby na základě vzdálenosti. Pravidla se aplikují v pořadí podle priority.', 'distance-shipping' ); ?>
			</p>
		</div>

		<?php if ( empty( $rules ) ) : ?>
			<div class="dbs-empty-state">
				<div class="dbs-empty-state-icon">
					<span class="dashicons dashicons-admin-settings"></span>
				</div>
				<h3><?php esc_html_e( 'Žádná pravidla', 'distance-shipping' ); ?></h3>
				<p><?php esc_html_e( 'Ještě jste nevytvořili žádná dopravní pravidla. Vytvořte první pravidlo pro začátek.', 'distance-shipping' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-rules&action=add' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Vytvořit první pravidlo', 'distance-shipping' ); ?>
				</a>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-name column-primary">
							<?php esc_html_e( 'Název pravidla', 'distance-shipping' ); ?>
						</th>
						<th scope="col" class="manage-column column-distance">
							<?php esc_html_e( 'Rozsah vzdálenosti', 'distance-shipping' ); ?>
						</th>
						<th scope="col" class="manage-column column-rates">
							<?php esc_html_e( 'Sazby', 'distance-shipping' ); ?>
						</th>
						<th scope="col" class="manage-column column-conditions">
							<?php esc_html_e( 'Podmínky', 'distance-shipping' ); ?>
						</th>
						<th scope="col" class="manage-column column-priority">
							<?php esc_html_e( 'Priorita', 'distance-shipping' ); ?>
						</th>
						<th scope="col" class="manage-column column-status">
							<?php esc_html_e( 'Stav', 'distance-shipping' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rules as $rule ) : ?>
						<tr>
							<td class="column-name column-primary">
								<strong>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-rules&action=edit&rule_id=' . $rule->id ) ); ?>">
										<?php echo esc_html( $rule->rule_name ); ?>
									</a>
								</strong>
								<div class="row-actions">
									<span class="edit">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-rules&action=edit&rule_id=' . $rule->id ) ); ?>">
											<?php esc_html_e( 'Upravit', 'distance-shipping' ); ?>
										</a>
									</span>
									|
									<span class="trash">
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=distance-shipping-rules&action=delete&rule_id=' . $rule->id ), 'delete_rule_' . $rule->id ) ); ?>" 
										   onclick="return confirm('<?php esc_attr_e( 'Opravdu chcete smazat toto pravidlo?', 'distance-shipping' ); ?>');">
											<?php esc_html_e( 'Smazat', 'distance-shipping' ); ?>
										</a>
									</span>
								</div>
								<button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e( 'Zobrazit více detailů', 'distance-shipping' ); ?></span></button>
							</td>
							<td class="column-distance" data-colname="<?php esc_attr_e( 'Rozsah vzdálenosti', 'distance-shipping' ); ?>">
								<?php
								$distance_from = number_format( $rule->distance_from, 1 );
								$distance_to = $rule->distance_to > 0 ? number_format( $rule->distance_to, 1 ) : '∞';
								echo esc_html( sprintf( '%s - %s %s', $distance_from, $distance_to, $distance_unit ) );
								?>
							</td>
							<td class="column-rates" data-colname="<?php esc_attr_e( 'Sazby', 'distance-shipping' ); ?>">
								<?php if ( $rule->base_rate > 0 ) : ?>
									<div>
										<strong><?php esc_html_e( 'Základní:', 'distance-shipping' ); ?></strong>
										<?php echo esc_html( $currency_symbol . number_format( $rule->base_rate, 2 ) ); ?>
									</div>
								<?php endif; ?>
								
								<?php 
								$per_km_rate = $rule->per_km_rate;
								if ( $per_km_rate > 0 ) : 
								?>
									<div>
										<strong><?php echo esc_html( sprintf( __( 'Za %s:', 'distance-shipping' ), 'km' ) ); ?></strong>
										<?php echo esc_html( $currency_symbol . number_format( $per_km_rate, 2 ) ); ?>
									</div>
								<?php endif; ?>
							</td>
							<td class="column-conditions" data-colname="<?php esc_attr_e( 'Podmínky', 'distance-shipping' ); ?>">
								<?php
								$conditions = [];
								
								if ( $rule->min_order_amount > 0 ) {
									$conditions[] = sprintf( __( 'Min. %s', 'distance-shipping' ), $currency_symbol . number_format( $rule->min_order_amount, 2 ) );
								}
								
								if ( $rule->max_order_amount > 0 ) {
									$conditions[] = sprintf( __( 'Max. %s', 'distance-shipping' ), $currency_symbol . number_format( $rule->max_order_amount, 2 ) );
								}
								
								if ( $rule->product_categories ) {
									$categories = maybe_unserialize( $rule->product_categories );
									if ( is_array( $categories ) && ! empty( $categories ) ) {
										$conditions[] = sprintf( __( 'Kategorie (%d)', 'distance-shipping' ), count( $categories ) );
									}
								}
								
								if ( $rule->shipping_classes ) {
									$classes = maybe_unserialize( $rule->shipping_classes );
									if ( is_array( $classes ) && ! empty( $classes ) ) {
										$conditions[] = sprintf( __( 'Dopravní třídy (%d)', 'distance-shipping' ), count( $classes ) );
									}
								}
								
								echo empty( $conditions ) ? '—' : esc_html( implode( ', ', $conditions ) );
								?>
							</td>
							<td class="column-priority" data-colname="<?php esc_attr_e( 'Priorita', 'distance-shipping' ); ?>">
								<?php echo esc_html( $rule->priority ); ?>
							</td>
							<td class="column-status" data-colname="<?php esc_attr_e( 'Stav', 'distance-shipping' ); ?>">
								<?php if ( $rule->is_active ) : ?>
									<span class="dbs-status dbs-status-active">
										<?php esc_html_e( 'Aktivní', 'distance-shipping' ); ?>
									</span>
								<?php else : ?>
									<span class="dbs-status dbs-status-inactive">
										<?php esc_html_e( 'Neaktivní', 'distance-shipping' ); ?>
									</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<style>
	.dbs-rules-help {
		margin: 20px 0;
		padding: 15px;
		background: #e7f3ff;
		border: 1px solid #b3d9ff;
		border-radius: 4px;
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

	.column-distance,
	.column-rates,
	.column-conditions {
		font-size: 13px;
	}

	.column-rates > div {
		margin-bottom: 2px;
	}

	.column-priority {
		text-align: center;
		font-weight: bold;
	}

	@media (max-width: 782px) {
		.column-rates > div {
			display: inline-block;
			margin-right: 10px;
		}
	}
	</style>
	<?php
}

/**
 * Vykreslí formulář pro pravidlo.
 *
 * @param object|null $rule Objekt pravidla nebo null pro nové.
 * @return void
 */
function dbs_render_rule_form( ?object $rule ): void {
	$is_edit = ! is_null( $rule );
	$page_title = $is_edit ? __( 'Upravit pravidlo', 'distance-shipping' ) : __( 'Přidat nové pravidlo', 'distance-shipping' );
	$form_action = $is_edit ? 'edit' : 'add';
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">
			<span class="dashicons dashicons-admin-settings"></span>
			<?php echo esc_html( $page_title ); ?>
		</h1>
		
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-rules' ) ); ?>" class="page-title-action">
			<?php esc_html_e( 'Zpět na seznam', 'distance-shipping' ); ?>
		</a>

		<hr class="wp-header-end">

		<?php include DBS_PLUGIN_PATH . 'admin/partials/rule-form.php'; ?>
	</div>
	<?php
}
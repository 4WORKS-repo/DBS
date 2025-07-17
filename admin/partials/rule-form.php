<?php
/**
 * Formulář pro dopravní pravidlo v Distance Based Shipping plugin.
 *
 * Soubor: admin/partials/rule-form.php
 *
 * @package DistanceBasedShipping
 */

// Zabránění přímému přístupu.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Výchozí hodnoty pro formulář.
$rule_name = $rule->rule_name ?? '';
$distance_from = $rule->distance_from ?? 0;
$distance_to = $rule->distance_to ?? 0;
$base_rate = $rule->base_rate ?? 0;
$per_km_rate = $rule->per_km_rate ?? 0;
$min_order_amount = $rule->min_order_amount ?? 0;
$max_order_amount = $rule->max_order_amount ?? 0;
$product_categories = $rule->product_categories ? maybe_unserialize( $rule->product_categories ) : [];
$shipping_classes = $rule->shipping_classes ? maybe_unserialize( $rule->shipping_classes ) : [];
$rule_is_active = isset( $rule->is_active ) ? $rule->is_active : 1;
$rule_priority = $rule->priority ?? 0;
$rule_id = $rule->id ?? 0;

// Získání nastavení DPH
$adjust_shipping_for_vat = get_option( 'dbs_adjust_shipping_for_vat', 0 );

// Získání dostupných kategorií a dopravních tříd.
$available_categories = dbs_get_product_categories();
$available_shipping_classes = dbs_get_shipping_classes();
$distance_unit = get_option( 'dbs_distance_unit', 'km' );
$currency_symbol = get_woocommerce_currency_symbol();
?>

<div class="dbs-rule-form-container">
	<form method="post" action="" id="dbs-rule-form">
		<?php wp_nonce_field( 'dbs_rule_form' ); ?>
		<input type="hidden" name="action" value="<?php echo esc_attr( $is_edit ? 'edit' : 'add' ); ?>" />
		<input type="hidden" name="rule_id" value="<?php echo esc_attr( $rule_id ); ?>" />
		
		<div class="dbs-form-sections">
			<!-- Základní informace -->
			<div class="dbs-form-section">
				<h2><?php esc_html_e( 'Základní informace', 'distance-shipping' ); ?></h2>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="rule_name"><?php esc_html_e( 'Název pravidla', 'distance-shipping' ); ?> <span class="required">*</span></label>
						</th>
						<td>
							<input type="text" id="rule_name" name="rule_name" value="<?php echo esc_attr( $rule_name ); ?>" class="regular-text" required />
							<p class="description">
								<?php esc_html_e( 'Zadejte popisný název pro toto pravidlo.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="rule_priority"><?php esc_html_e( 'Priorita', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="rule_priority" name="rule_priority" value="<?php echo esc_attr( $rule_priority ); ?>" min="0" max="999" class="small-text" />
							<p class="description">
								<?php esc_html_e( 'Pořadí aplikace pravidel. Nižší číslo = vyšší priorita. Výchozí: 0.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Stav pravidla', 'distance-shipping' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="checkbox" name="rule_is_active" value="1" <?php checked( $rule_is_active, 1 ); ?> />
									<?php esc_html_e( 'Pravidlo je aktivní', 'distance-shipping' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Pouze aktivní pravidla se používají pro výpočet dopravních sazeb.', 'distance-shipping' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
				</table>
			</div>

			<!-- Rozsah vzdálenosti -->
			<div class="dbs-form-section">
				<h2><?php esc_html_e( 'Rozsah vzdálenosti', 'distance-shipping' ); ?></h2>
				<p class="description">
					<?php printf( esc_html__( 'Definujte pro jaké vzdálenosti (v %s) se toto pravidlo aplikuje.', 'distance-shipping' ), esc_html( $distance_unit ) ); ?>
				</p>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="distance_from"><?php esc_html_e( 'Vzdálenost od', 'distance-shipping' ); ?> <span class="required">*</span></label>
						</th>
						<td>
							<input type="number" id="distance_from" name="distance_from" value="<?php echo esc_attr( $distance_from ); ?>" step="0.1" min="0" class="small-text" required />
							<?php echo esc_html( $distance_unit ); ?>
							<p class="description">
								<?php esc_html_e( 'Minimální vzdálenost pro aplikaci tohoto pravidla.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="distance_to"><?php esc_html_e( 'Vzdálenost do', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="distance_to" name="distance_to" value="<?php echo esc_attr( $distance_to ); ?>" step="0.1" min="0" class="small-text" />
							<?php echo esc_html( $distance_unit ); ?>
							<p class="description">
								<?php esc_html_e( 'Maximální vzdálenost pro aplikaci tohoto pravidla. Ponechte prázdné pro neomezenou vzdálenost.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Sazby -->
			<div class="dbs-form-section">
				<h2><?php esc_html_e( 'Dopravní sazby', 'distance-shipping' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Definujte jak se vypočítají dopravní náklady. Můžete kombinovat základní sazbu s sazbou za jednotku vzdálenosti.', 'distance-shipping' ); ?>
				</p>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="base_rate"><?php esc_html_e( 'Základní sazba', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="base_rate" name="base_rate" value="<?php echo esc_attr( $base_rate ); ?>" step="0.01" min="0" class="small-text" />
							<?php echo esc_html( $currency_symbol ); ?>
							<p class="description">
								<?php esc_html_e( 'Pevná částka přidaná k dopravnímu bez ohledu na vzdálenost.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="per_km_rate"><?php esc_html_e( 'Sazba za kilometr', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="per_km_rate" name="per_km_rate" value="<?php echo esc_attr( $per_km_rate ); ?>" step="0.01" min="0" class="small-text" />
							<?php echo esc_html( $currency_symbol ); ?>
							<p class="description">
								<?php esc_html_e( 'Částka přidaná za každý kilometr vzdálenosti.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<div class="dbs-rate-calculator">
					<h4><?php esc_html_e( 'Kalkulátor sazeb', 'distance-shipping' ); ?></h4>
					<p>
						<label for="calc_distance"><?php esc_html_e( 'Testovací vzdálenost:', 'distance-shipping' ); ?></label>
						<input type="number" id="calc_distance" step="0.1" min="0" class="small-text" />
						<?php echo esc_html( $distance_unit ); ?>
						<button type="button" id="calculate_rate" class="button"><?php esc_html_e( 'Vypočítat', 'distance-shipping' ); ?></button>
					</p>
					<div id="calc_result" style="display: none;">
						<strong><?php esc_html_e( 'Celková sazba:', 'distance-shipping' ); ?></strong>
						<span id="calc_total"></span>
					</div>
					
					<?php if ( $adjust_shipping_for_vat ) : ?>
						<div class="dbs-vat-notice" style="margin-top: 10px; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 3px; font-size: 12px;">
							<span class="dashicons dashicons-info" style="color: #856404; margin-right: 5px;"></span>
							<strong><?php esc_html_e( 'DPH režim aktivní:', 'distance-shipping' ); ?></strong>
							<?php esc_html_e( 'Ceny budou automaticky přepočítány z brutto na netto (÷1.21) před odesláním do WooCommerce.', 'distance-shipping' ); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Podmínky objednávky -->
			<div class="dbs-form-section">
				<h2><?php esc_html_e( 'Podmínky objednávky', 'distance-shipping' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Volitelné podmínky které musí objednávka splňovat pro aplikaci tohoto pravidla.', 'distance-shipping' ); ?>
				</p>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="min_order_amount"><?php esc_html_e( 'Minimální částka objednávky', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="min_order_amount" name="min_order_amount" value="<?php echo esc_attr( $min_order_amount ); ?>" step="0.01" min="0" class="small-text" />
							<?php echo esc_html( $currency_symbol ); ?>
							<p class="description">
								<?php esc_html_e( 'Pravidlo se aplikuje pouze pokud je hodnota objednávky alespoň tato částka. 0 = bez omezení.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="max_order_amount"><?php esc_html_e( 'Maximální částka objednávky', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="max_order_amount" name="max_order_amount" value="<?php echo esc_attr( $max_order_amount ); ?>" step="0.01" min="0" class="small-text" />
							<?php echo esc_html( $currency_symbol ); ?>
							<p class="description">
								<?php esc_html_e( 'Pravidlo se aplikuje pouze pokud je hodnota objednávky maximálně tato částka. 0 = bez omezení.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Produktové kategorie -->
			<div class="dbs-form-section">
				<h2><?php esc_html_e( 'Produktové kategorie', 'distance-shipping' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Vyberte kategorie produktů pro které se toto pravidlo aplikuje. Ponechte nevybrané pro všechny kategorie.', 'distance-shipping' ); ?>
				</p>
				
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Vyberte kategorie', 'distance-shipping' ); ?></th>
						<td>
							<?php if ( empty( $available_categories ) ) : ?>
								<p class="description"><?php esc_html_e( 'Nejsou dostupné žádné produktové kategorie.', 'distance-shipping' ); ?></p>
							<?php else : ?>
								<div class="dbs-checkbox-list">
									<?php foreach ( $available_categories as $cat_id => $cat_name ) : ?>
										<label class="dbs-checkbox-item">
											<input type="checkbox" name="product_categories[]" value="<?php echo esc_attr( $cat_id ); ?>" <?php checked( in_array( $cat_id, $product_categories, true ) ); ?> />
											<?php echo esc_html( $cat_name ); ?>
										</label>
									<?php endforeach; ?>
								</div>
								<p class="description">
									<button type="button" id="select_all_categories" class="button button-small"><?php esc_html_e( 'Vybrat vše', 'distance-shipping' ); ?></button>
									<button type="button" id="deselect_all_categories" class="button button-small"><?php esc_html_e( 'Zrušit výběr', 'distance-shipping' ); ?></button>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</div>

			<!-- Hmotnost produktů -->
			<div class="dbs-form-section">
				<h2><?php esc_html_e( 'Hmotnost produktů', 'distance-shipping' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Definujte podmínky pro hmotnost produktů. Pravidlo se aplikuje pouze pokud celková hmotnost balíčku vyhovuje těmto podmínkám.', 'distance-shipping' ); ?>
				</p>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="weight_min"><?php esc_html_e( 'Minimální hmotnost', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="weight_min" name="weight_min" value="<?php echo esc_attr( $rule->weight_min ?? 0 ); ?>" step="0.1" min="0" class="small-text" />
							kg
							<p class="description">
								<?php esc_html_e( 'Pravidlo se aplikuje pouze pokud je celková hmotnost balíčku alespoň tato hodnota. 0 = bez omezení.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="weight_max"><?php esc_html_e( 'Maximální hmotnost', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="weight_max" name="weight_max" value="<?php echo esc_attr( $rule->weight_max ?? 0 ); ?>" step="0.1" min="0" class="small-text" />
							kg
							<p class="description">
								<?php esc_html_e( 'Pravidlo se aplikuje pouze pokud je celková hmotnost balíčku maximálně tato hodnota. 0 = bez omezení.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="weight_operator"><?php esc_html_e( 'Operátor pro hmotnost', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<select id="weight_operator" name="weight_operator">
								<option value="AND" <?php selected( $rule->weight_operator ?? 'AND', 'AND' ); ?>><?php esc_html_e( 'AND - Všechny podmínky musí být splněny', 'distance-shipping' ); ?></option>
								<option value="OR" <?php selected( $rule->weight_operator ?? 'AND', 'OR' ); ?>><?php esc_html_e( 'OR - Alespoň jedna podmínka musí být splněna', 'distance-shipping' ); ?></option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Určuje jak se kombinují podmínky hmotnosti s ostatními podmínkami (rozměry).', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Rozměry produktů -->
			<div class="dbs-form-section">
				<h2><?php esc_html_e( 'Rozměry produktů', 'distance-shipping' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Definujte podmínky pro rozměry produktů. Pravidlo se aplikuje pouze pokud rozměry balíčku vyhovují těmto podmínkám.', 'distance-shipping' ); ?>
				</p>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="length_min"><?php esc_html_e( 'Minimální délka', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="length_min" name="length_min" value="<?php echo esc_attr( $rule->length_min ?? 0 ); ?>" step="0.1" min="0" class="small-text" />
							cm
							<p class="description">
								<?php esc_html_e( 'Pravidlo se aplikuje pouze pokud je největší délka produktu alespoň tato hodnota. 0 = bez omezení.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="length_max"><?php esc_html_e( 'Maximální délka', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="length_max" name="length_max" value="<?php echo esc_attr( $rule->length_max ?? 0 ); ?>" step="0.1" min="0" class="small-text" />
							cm
							<p class="description">
								<?php esc_html_e( 'Pravidlo se aplikuje pouze pokud je největší délka produktu maximálně tato hodnota. 0 = bez omezení.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="width_min"><?php esc_html_e( 'Minimální šířka', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="width_min" name="width_min" value="<?php echo esc_attr( $rule->width_min ?? 0 ); ?>" step="0.1" min="0" class="small-text" />
							cm
							<p class="description">
								<?php esc_html_e( 'Pravidlo se aplikuje pouze pokud je největší šířka produktu alespoň tato hodnota. 0 = bez omezení.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="width_max"><?php esc_html_e( 'Maximální šířka', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="width_max" name="width_max" value="<?php echo esc_attr( $rule->width_max ?? 0 ); ?>" step="0.1" min="0" class="small-text" />
							cm
							<p class="description">
								<?php esc_html_e( 'Pravidlo se aplikuje pouze pokud je největší šířka produktu maximálně tato hodnota. 0 = bez omezení.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="height_min"><?php esc_html_e( 'Minimální výška', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="height_min" name="height_min" value="<?php echo esc_attr( $rule->height_min ?? 0 ); ?>" step="0.1" min="0" class="small-text" />
							cm
							<p class="description">
								<?php esc_html_e( 'Pravidlo se aplikuje pouze pokud je celková výška balíčku alespoň tato hodnota. 0 = bez omezení.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="height_max"><?php esc_html_e( 'Maximální výška', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<input type="number" id="height_max" name="height_max" value="<?php echo esc_attr( $rule->height_max ?? 0 ); ?>" step="0.1" min="0" class="small-text" />
							cm
							<p class="description">
								<?php esc_html_e( 'Pravidlo se aplikuje pouze pokud je celková výška balíčku maximálně tato hodnota. 0 = bez omezení.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="dimensions_operator"><?php esc_html_e( 'Operátor pro rozměry', 'distance-shipping' ); ?></label>
						</th>
						<td>
							<select id="dimensions_operator" name="dimensions_operator">
								<option value="AND" <?php selected( $rule->dimensions_operator ?? 'AND', 'AND' ); ?>><?php esc_html_e( 'AND - Všechny podmínky musí být splněny', 'distance-shipping' ); ?></option>
								<option value="OR" <?php selected( $rule->dimensions_operator ?? 'AND', 'OR' ); ?>><?php esc_html_e( 'OR - Alespoň jedna podmínka musí být splněna', 'distance-shipping' ); ?></option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Určuje jak se kombinují podmínky rozměrů mezi sebou.', 'distance-shipping' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Dopravní třídy -->
			<div class="dbs-form-section">
				<h2><?php esc_html_e( 'Dopravní třídy', 'distance-shipping' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Vyberte dopravní třídy pro které se toto pravidlo aplikuje. Ponechte nevybrané pro všechny třídy.', 'distance-shipping' ); ?>
				</p>
				
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Vyberte dopravní třídy', 'distance-shipping' ); ?></th>
						<td>
							<?php if ( empty( $available_shipping_classes ) ) : ?>
								<p class="description"><?php esc_html_e( 'Nejsou dostupné žádné dopravní třídy.', 'distance-shipping' ); ?></p>
							<?php else : ?>
								<div class="dbs-checkbox-list">
									<?php foreach ( $available_shipping_classes as $class_id => $class_name ) : ?>
										<label class="dbs-checkbox-item">
											<input type="checkbox" name="shipping_classes[]" value="<?php echo esc_attr( $class_id ); ?>" <?php checked( in_array( $class_id, $shipping_classes, true ) ); ?> />
											<?php echo esc_html( $class_name ); ?>
										</label>
									<?php endforeach; ?>
								</div>
								<p class="description">
									<button type="button" id="select_all_classes" class="button button-small"><?php esc_html_e( 'Vybrat vše', 'distance-shipping' ); ?></button>
									<button type="button" id="deselect_all_classes" class="button button-small"><?php esc_html_e( 'Zrušit výběr', 'distance-shipping' ); ?></button>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div class="dbs-form-actions">
			<?php submit_button( 
				$is_edit ? __( 'Aktualizovat pravidlo', 'distance-shipping' ) : __( 'Přidat pravidlo', 'distance-shipping' ),
				'primary',
				'submit',
				false,
				[ 'id' => 'dbs-submit-rule' ]
			); ?>
			
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=distance-shipping-rules' ) ); ?>" class="button">
				<?php esc_html_e( 'Zrušit', 'distance-shipping' ); ?>
			</a>

			<?php if ( $is_edit ) : ?>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=distance-shipping-rules&action=delete&rule_id=' . $rule_id ), 'delete_rule_' . $rule_id ) ); ?>" 
				   class="button button-link-delete" 
				   onclick="return confirm('<?php esc_attr_e( 'Opravdu chcete smazat toto pravidlo?', 'distance-shipping' ); ?>');">
					<?php esc_html_e( 'Smazat pravidlo', 'distance-shipping' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</form>
</div>

<style>
.dbs-rule-form-container {
	max-width: 1000px;
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

.dbs-rate-calculator {
	margin-top: 20px;
	padding: 15px;
	background: #f8f9fa;
	border: 1px solid #dee2e6;
	border-radius: 4px;
}

.dbs-rate-calculator h4 {
	margin-top: 0;
	margin-bottom: 10px;
}

#calc_result {
	margin-top: 10px;
	padding: 8px;
	background: #e8f5e8;
	border: 1px solid #c3e6cb;
	border-radius: 3px;
}

.dbs-checkbox-list {
	max-height: 200px;
	overflow-y: auto;
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 10px;
	background: #fafafa;
}

.dbs-checkbox-item {
	display: block;
	margin-bottom: 8px;
	padding: 4px;
}

.dbs-checkbox-item:hover {
	background: #f0f0f0;
	border-radius: 3px;
}

.dbs-checkbox-item input[type="checkbox"] {
	margin-right: 8px;
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

	.dbs-checkbox-list {
		max-height: 150px;
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	// Kalkulátor sazeb
	$('#calculate_rate').on('click', function() {
		var distance = parseFloat($('#calc_distance').val()) || 0;
		var baseRate = parseFloat($('#base_rate').val()) || 0;
		var perKmRate = parseFloat($('#per_km_rate').val()) || 0;
		var currencySymbol = '<?php echo esc_js( $currency_symbol ); ?>';
		
		if (distance <= 0) {
			alert('<?php echo esc_js( __( 'Zadejte platnou vzdálenost.', 'distance-shipping' ) ); ?>');
			return;
		}
		
		// Always use kilometers for calculation
		var totalRate = baseRate + (distance * perKmRate);
		
		$('#calc_total').text(currencySymbol + totalRate.toFixed(2));
		$('#calc_result').show();
	});

	// Přepočítání při změně hodnot
	$('#base_rate, #per_km_rate').on('input', function() {
		if ($('#calc_distance').val()) {
			$('#calculate_rate').trigger('click');
		}
	});

	// Výběr/zrušení výběru kategorií
	$('#select_all_categories').on('click', function() {
		$('input[name="product_categories[]"]').prop('checked', true);
	});

	$('#deselect_all_categories').on('click', function() {
		$('input[name="product_categories[]"]').prop('checked', false);
	});

	// Výběr/zrušení výběru dopravních tříd
	$('#select_all_classes').on('click', function() {
		$('input[name="shipping_classes[]"]').prop('checked', true);
	});

	$('#deselect_all_classes').on('click', function() {
		$('input[name="shipping_classes[]"]').prop('checked', false);
	});

	// Validace formuláře před odesláním
	$('#dbs-rule-form').on('submit', function(e) {
		var ruleName = $('#rule_name').val().trim();
		var distanceFrom = parseFloat($('#distance_from').val());
		var distanceTo = parseFloat($('#distance_to').val());
		
		if (!ruleName) {
			alert('<?php echo esc_js( __( 'Název pravidla je povinný.', 'distance-shipping' ) ); ?>');
			$('#rule_name').focus();
			e.preventDefault();
			return false;
		}
		
		if (distanceFrom < 0 || distanceTo < 0) {
			alert('<?php echo esc_js( __( 'Zadejte platnou vzdálenost.', 'distance-shipping' ) ); ?>');
			return;
		}
		
		if (distanceTo <= distanceFrom) {
			alert('<?php echo esc_js( __( 'Vzdálenost do musí být větší než vzdálenost od.', 'distance-shipping' ) ); ?>');
			$('#distance_to').focus();
			e.preventDefault();
			return false;
		}

		// Kontrola sazeb - povolujeme nulové hodnoty pro dopravu zdarma
		var baseRate = parseFloat($('#base_rate').val()) || 0;
		var perKmRate = parseFloat($('#per_km_rate').val()) || 0;
		
		// Kontrola pouze záporných hodnot
		if (baseRate < 0 || perKmRate < 0) {
			alert('<?php echo esc_js( __( 'Sazby nemohou být záporné.', 'distance-shipping' ) ); ?>');
			e.preventDefault();
			return;
		}
	});

	// Automatické označení aktivních polí podle jednotky
	var distanceUnit = '<?php echo esc_js( $distance_unit ); ?>';
	if (distanceUnit === 'km') {
		$('#per_km_rate').addClass('active-rate-field');
	} else {
		// No specific action for per_mile_rate as it's removed
	}
});
</script>

<style>
.active-rate-field {
	border-color: #007cba !important;
	box-shadow: 0 0 0 1px #007cba !important;
}
</style>
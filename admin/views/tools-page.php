<?php
/**
 * Testovací nástroje - Admin stránka
 *
 * @package DistanceBasedShipping
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Zpracování POST požadavků
$action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';
$test_result = '';

if ( $action && wp_verify_nonce( $_POST['_wpnonce'], 'dbs_tools_nonce' ) ) {
	ob_start();
	
	switch ( $action ) {
		case 'test_weight_calculation':
			dbs_admin_test_weight_calculation();
			break;
		case 'test_operators':
			dbs_admin_test_operators();
			break;
		case 'test_rule26_problem':
			dbs_admin_test_rule26_problem();
			break;
		case 'test_rule31_problem':
			dbs_admin_test_rule31_problem();
			break;
		case 'clear_cache':
			echo dbs_clear_all_cache();
			break;
		default:
			echo '<p>Neznámá akce: ' . esc_html( $action ) . '</p>';
	}
	
	$test_result = ob_get_clean();
}
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<div class="notice notice-info">
		<p>
			<strong>Testovací nástroje pro Distance Based Shipping plugin</strong><br>
			Tyto nástroje slouží k testování a diagnostice funkcionalit pluginu.
		</p>
	</div>

	<div class="postbox-container" style="width: 100%;">
		<div class="meta-box-sortables">
			
			<!-- Test váhového výpočtu -->
			<div class="postbox">
				<h2 class="hndle"><span>Test váhového výpočtu</span></h2>
				<div class="inside">
					<p>
						Testuje opravu váhového výpočtu - ověřuje, zda se správně počítá hmotnost podle množství produktů.
						<br><strong>Příklad:</strong> 3kg produkt × 3 kusy = 9kg
					</p>
					<form method="post" action="">
						<?php wp_nonce_field( 'dbs_tools_nonce' ); ?>
						<input type="hidden" name="action" value="test_weight_calculation">
						<p>
							<input type="submit" class="button button-primary" value="Spustit test váhového výpočtu">
						</p>
					</form>
				</div>
			</div>

			<!-- Test operátorů -->
			<div class="postbox">
				<h2 class="hndle"><span>Test AND/OR operátorů</span></h2>
				<div class="inside">
					<p>
						Testuje implementaci AND/OR operátorů pro hmotnostní a rozměrové podmínky v shipping rules.
						<br><strong>Příklad:</strong> Rule s OR operátorem by měla projít i když je splněna jen jedna podmínka.
					</p>
					<form method="post" action="">
						<?php wp_nonce_field( 'dbs_tools_nonce' ); ?>
						<input type="hidden" name="action" value="test_operators">
						<p>
							<input type="submit" class="button button-primary" value="Spustit test AND/OR operátorů">
						</p>
					</form>
				</div>
			</div>

			<!-- Test Rule 26 problému -->
			<div class="postbox">
				<h2 class="hndle"><span>Test Rule 26 problému</span></h2>
				<div class="inside">
					<p>
						Testuje konkrétní problém z vašeho debug logu - Rule 26 s podmínkami 75-100kg se nesprávně aplikovala na balíček s 3kg.
						<br><strong>Scénář:</strong> 1ks × 3kg, Rule 26 (75-100kg) - měla by se NEAPLIKOVAT.
					</p>
					<form method="post" action="">
						<?php wp_nonce_field( 'dbs_tools_nonce' ); ?>
						<input type="hidden" name="action" value="test_rule26_problem">
						<p>
							<input type="submit" class="button button-primary" value="Test Rule 26 problému">
						</p>
					</form>
				</div>
			</div>

			<!-- Test Rule 31 problému -->
			<div class="postbox">
				<h2 class="hndle"><span>Test Rule 31 problému</span></h2>
				<div class="inside">
					<p>
						Diagnostika problému s Rule 31 - když se neaplikuje i když by měla.
						<br><strong>Funkce:</strong> Zobrazí parametry Rule 31 a otestuje všechny podmínky.
					</p>
					<form method="post" action="">
						<?php wp_nonce_field( 'dbs_tools_nonce' ); ?>
						<input type="hidden" name="action" value="test_rule31_problem">
						<p>
							<input type="submit" class="button button-primary" value="Test Rule 31 problému">
						</p>
					</form>
				</div>
			</div>

			<!-- Vyčištění cache -->
			<div class="postbox">
				<h2 class="hndle"><span>Vyčištění cache</span></h2>
				<div class="inside">
					<p>
						Vyčistí všechny cache vztahující se k shipping výpočtům.
						<br><strong>Použití:</strong> Když se shipping pravidla nechová podle očekávání.
					</p>
					<form method="post" action="">
						<?php wp_nonce_field( 'dbs_tools_nonce' ); ?>
						<input type="hidden" name="action" value="clear_cache">
						<p>
							<input type="submit" class="button button-secondary" value="Vyčistit cache">
						</p>
					</form>
				</div>
			</div>

		</div>
	</div>

	<!-- Výsledky testů -->
	<?php if ( ! empty( $test_result ) ) : ?>
		<div class="postbox-container" style="width: 100%;">
			<div class="meta-box-sortables">
				<div class="postbox">
					<h2 class="hndle"><span>Výsledky testu</span></h2>
					<div class="inside">
						<div style="background: #f7f7f7; padding: 15px; border-radius: 5px; max-height: 500px; overflow-y: auto;">
							<?php echo $test_result; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<!-- Informace o implementovaných opravách -->
	<div class="postbox-container" style="width: 100%;">
		<div class="meta-box-sortables">
			<div class="postbox">
				<h2 class="hndle"><span>Implementované opravy</span></h2>
				<div class="inside">
					<h3>1. Váhový výpočet (weight-sync-fix.php)</h3>
					<ul>
						<li>✅ Správný výpočet hmotnosti podle množství produktů</li>
						<li>✅ Vylepšená cache invalidace při změnách košíku</li>
						<li>✅ Detailní debug informace v error logu</li>
					</ul>
					
					<h3>2. AND/OR operátory (weight-operators-fix.php)</h3>
					<ul>
						<li>✅ Implementace AND operátoru pro hmotnostní podmínky</li>
						<li>✅ Implementace OR operátoru pro hmotnostní podmínky</li>
						<li>✅ Implementace AND/OR operátorů pro rozměrové podmínky</li>
						<li>✅ Správná kombinace hmotnostních a rozměrových podmínek</li>
					</ul>
					
					<h3>Status oprav</h3>
					<p>
						<strong>Váhová oprava:</strong> 
						<?php echo get_option( 'dbs_weight_fix_active', false ) ? '<span style="color: green;">✅ Aktivní</span>' : '<span style="color: red;">❌ Neaktivní</span>'; ?>
					</p>
					<p>
						<strong>Operátory oprava:</strong> 
						<?php echo get_option( 'dbs_weight_operators_fix_active', false ) ? '<span style="color: green;">✅ Aktivní</span>' : '<span style="color: red;">❌ Neaktivní</span>'; ?>
					</p>
				</div>
			</div>
		</div>
	</div>

	<!-- Debug informace -->
	<div class="postbox-container" style="width: 100%;">
		<div class="meta-box-sortables">
			<div class="postbox">
				<h2 class="hndle"><span>Debug informace</span></h2>
				<div class="inside">
					<p>
						<strong>Debug mód:</strong> 
						<?php echo get_option( 'dbs_debug_mode', 0 ) ? '<span style="color: green;">✅ Zapnut</span>' : '<span style="color: red;">❌ Vypnut</span>'; ?>
					</p>
					<p>
						<strong>Jak zapnout debug:</strong>
						Jděte do Distance Shipping → Settings → Debug Mode → zaškrtněte a uložte
					</p>
					<p>
						<strong>Kde najít debug logy:</strong>
						wp-content/debug.log (pokud je WP_DEBUG_LOG zapnut)
					</p>
				</div>
			</div>
		</div>
	</div>

</div>

<style>
.dbs-test-results {
	font-family: monospace;
	background: #f0f0f0;
	padding: 10px;
	border-radius: 3px;
	margin: 10px 0;
}

.dbs-test-results table {
	width: 100%;
	border-collapse: collapse;
	margin: 10px 0;
}

.dbs-test-results th,
.dbs-test-results td {
	border: 1px solid #ddd;
	padding: 8px;
	text-align: left;
}

.dbs-test-results th {
	background-color: #f2f2f2;
	font-weight: bold;
}

.dbs-test-results tr:nth-child(even) {
	background-color: #f9f9f9;
}
</style> 
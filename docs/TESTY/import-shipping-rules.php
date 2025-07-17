<?php
/**
 * Import skript pro shipping rules - Distance Based Shipping
 * 
 * Tento skript importuje 31 předpřipravených shipping rules do databáze.
 * Použijte pouze jednou na cílovém webu!
 */

// Zabránění přímému přístupu
if ( ! defined( 'ABSPATH' ) ) {
    // Pokud nejsme v WordPress, načteme ho
    $wp_load_path = dirname( __FILE__ ) . '/wp-load.php';
    if ( file_exists( $wp_load_path ) ) {
        require_once $wp_load_path;
    } else {
        die( 'WordPress není nalezen. Ujistěte se, že je tento skript v kořenovém adresáři WordPress.' );
    }
}

// Kontrola, zda je plugin aktivní
if ( ! function_exists( 'dbs_insert_shipping_rule' ) ) {
    die( 'Plugin Distance Based Shipping není aktivní. Prosím aktivujte plugin před spuštěním importu.' );
}

// Kontrola oprávnění
if ( ! current_user_can( 'manage_woocommerce' ) ) {
    die( 'Nemáte dostatečná oprávnění pro spuštění importu.' );
}

// Funkce pro vymazání všech existujících pravidel
function clear_all_shipping_rules() {
    global $wpdb;
    
    $tables = dbs_get_table_names();
    $table_name = $tables['rules'];
    
    $result = $wpdb->query( "DELETE FROM {$table_name}" );
    
    if ( $result !== false ) {
        return $wpdb->rows_affected;
    }
    
    return false;
}

// Funkce pro import pravidel s upravenými prioritami
function import_shipping_rules() {
    $rules = [
        // Rule 31 - Bezplatná doprava nad 5000 Kč (NEJNIŽŠÍ priorita = 1)
        [
            'rule_name' => 'Rule 31 - Doprava zdarma nad 5000 Kč',
            'distance_from' => 0,
            'distance_to' => 500,
            'base_rate' => 0,
            'per_km_rate' => 0,
            'min_order_amount' => 5000,
            'weight_min' => 0,
            'weight_max' => 100,
            'weight_operator' => 'AND',
            'priority' => 1,
            'is_active' => 1
        ],
        // Rule 1
        [
            'rule_name' => 'Rule 1',
            'distance_from' => 0,
            'distance_to' => 100,
            'base_rate' => 186,
            'per_km_rate' => 0,
            'weight_min' => 0,
            'weight_max' => 5,
            'weight_operator' => 'AND',
            'priority' => 2,
            'is_active' => 1
        ],
        // Rule 2
        [
            'rule_name' => 'Rule 2',
            'distance_from' => 101,
            'distance_to' => 200,
            'base_rate' => 212,
            'per_km_rate' => 0,
            'weight_min' => 0,
            'weight_max' => 5,
            'weight_operator' => 'AND',
            'priority' => 3,
            'is_active' => 1
        ],
        // Rule 3
        [
            'rule_name' => 'Rule 3',
            'distance_from' => 201,
            'distance_to' => 300,
            'base_rate' => 325,
            'per_km_rate' => 0,
            'weight_min' => 0,
            'weight_max' => 5,
            'weight_operator' => 'AND',
            'priority' => 4,
            'is_active' => 1
        ],
        // Rule 4
        [
            'rule_name' => 'Rule 4',
            'distance_from' => 301,
            'distance_to' => 400,
            'base_rate' => 325,
            'per_km_rate' => 0,
            'weight_min' => 0,
            'weight_max' => 5,
            'weight_operator' => 'AND',
            'priority' => 5,
            'is_active' => 1
        ],
        // Rule 5
        [
            'rule_name' => 'Rule 5',
            'distance_from' => 401,
            'distance_to' => 500,
            'base_rate' => 325,
            'per_km_rate' => 0,
            'weight_min' => 0,
            'weight_max' => 5,
            'weight_operator' => 'AND',
            'priority' => 6,
            'is_active' => 1
        ],
        // Rule 6
        [
            'rule_name' => 'Rule 6',
            'distance_from' => 0,
            'distance_to' => 100,
            'base_rate' => 325,
            'per_km_rate' => 0,
            'weight_min' => 5,
            'weight_max' => 15,
            'weight_operator' => 'AND',
            'priority' => 7,
            'is_active' => 1
        ],
        // Rule 7
        [
            'rule_name' => 'Rule 7',
            'distance_from' => 101,
            'distance_to' => 200,
            'base_rate' => 344,
            'per_km_rate' => 0,
            'weight_min' => 5,
            'weight_max' => 15,
            'weight_operator' => 'AND',
            'priority' => 8,
            'is_active' => 1
        ],
        // Rule 8
        [
            'rule_name' => 'Rule 8',
            'distance_from' => 201,
            'distance_to' => 300,
            'base_rate' => 376,
            'per_km_rate' => 0,
            'weight_min' => 5,
            'weight_max' => 15,
            'weight_operator' => 'AND',
            'priority' => 9,
            'is_active' => 1
        ],
        // Rule 9
        [
            'rule_name' => 'Rule 9',
            'distance_from' => 301,
            'distance_to' => 400,
            'base_rate' => 421,
            'per_km_rate' => 0,
            'weight_min' => 5,
            'weight_max' => 15,
            'weight_operator' => 'AND',
            'priority' => 10,
            'is_active' => 1
        ],
        // Rule 10
        [
            'rule_name' => 'Rule 10',
            'distance_from' => 401,
            'distance_to' => 500,
            'base_rate' => 471,
            'per_km_rate' => 0,
            'weight_min' => 5,
            'weight_max' => 15,
            'weight_operator' => 'AND',
            'priority' => 11,
            'is_active' => 1
        ],
        // Rule 11
        [
            'rule_name' => 'Rule 11',
            'distance_from' => 0,
            'distance_to' => 100,
            'base_rate' => 461,
            'per_km_rate' => 0,
            'weight_min' => 15,
            'weight_max' => 30,
            'weight_operator' => 'AND',
            'priority' => 12,
            'is_active' => 1
        ],
        // Rule 12
        [
            'rule_name' => 'Rule 12',
            'distance_from' => 101,
            'distance_to' => 200,
            'base_rate' => 541,
            'per_km_rate' => 0,
            'weight_min' => 15,
            'weight_max' => 30,
            'weight_operator' => 'AND',
            'priority' => 13,
            'is_active' => 1
        ],
        // Rule 13
        [
            'rule_name' => 'Rule 13',
            'distance_from' => 201,
            'distance_to' => 300,
            'base_rate' => 608,
            'per_km_rate' => 0,
            'weight_min' => 15,
            'weight_max' => 30,
            'weight_operator' => 'AND',
            'priority' => 14,
            'is_active' => 1
        ],
        // Rule 14
        [
            'rule_name' => 'Rule 14',
            'distance_from' => 301,
            'distance_to' => 400,
            'base_rate' => 660,
            'per_km_rate' => 0,
            'weight_min' => 15,
            'weight_max' => 30,
            'weight_operator' => 'AND',
            'priority' => 15,
            'is_active' => 1
        ],
        // Rule 15
        [
            'rule_name' => 'Rule 15',
            'distance_from' => 401,
            'distance_to' => 500,
            'base_rate' => 703,
            'per_km_rate' => 0,
            'weight_min' => 15,
            'weight_max' => 30,
            'weight_operator' => 'AND',
            'priority' => 16,
            'is_active' => 1
        ],
        // Rule 16
        [
            'rule_name' => 'Rule 16',
            'distance_from' => 0,
            'distance_to' => 100,
            'base_rate' => 662,
            'per_km_rate' => 0,
            'weight_min' => 30,
            'weight_max' => 50,
            'weight_operator' => 'AND',
            'priority' => 17,
            'is_active' => 1
        ],
        // Rule 17
        [
            'rule_name' => 'Rule 17',
            'distance_from' => 101,
            'distance_to' => 200,
            'base_rate' => 793,
            'per_km_rate' => 0,
            'weight_min' => 30,
            'weight_max' => 50,
            'weight_operator' => 'AND',
            'priority' => 18,
            'is_active' => 1
        ],
        // Rule 18
        [
            'rule_name' => 'Rule 18',
            'distance_from' => 201,
            'distance_to' => 300,
            'base_rate' => 898,
            'per_km_rate' => 0,
            'weight_min' => 30,
            'weight_max' => 50,
            'weight_operator' => 'AND',
            'priority' => 19,
            'is_active' => 1
        ],
        // Rule 19
        [
            'rule_name' => 'Rule 19',
            'distance_from' => 301,
            'distance_to' => 400,
            'base_rate' => 972,
            'per_km_rate' => 0,
            'weight_min' => 30,
            'weight_max' => 50,
            'weight_operator' => 'AND',
            'priority' => 20,
            'is_active' => 1
        ],
        // Rule 20
        [
            'rule_name' => 'Rule 20',
            'distance_from' => 401,
            'distance_to' => 500,
            'base_rate' => 1044,
            'per_km_rate' => 0,
            'weight_min' => 30,
            'weight_max' => 50,
            'weight_operator' => 'AND',
            'priority' => 21,
            'is_active' => 1
        ],
        // Rule 21
        [
            'rule_name' => 'Rule 21',
            'distance_from' => 0,
            'distance_to' => 100,
            'base_rate' => 844,
            'per_km_rate' => 0,
            'weight_min' => 50,
            'weight_max' => 75,
            'weight_operator' => 'AND',
            'priority' => 22,
            'is_active' => 1
        ],
        // Rule 22
        [
            'rule_name' => 'Rule 22',
            'distance_from' => 101,
            'distance_to' => 200,
            'base_rate' => 1024,
            'per_km_rate' => 0,
            'weight_min' => 50,
            'weight_max' => 75,
            'weight_operator' => 'AND',
            'priority' => 23,
            'is_active' => 1
        ],
        // Rule 23
        [
            'rule_name' => 'Rule 23',
            'distance_from' => 201,
            'distance_to' => 300,
            'base_rate' => 1157,
            'per_km_rate' => 0,
            'weight_min' => 50,
            'weight_max' => 75,
            'weight_operator' => 'AND',
            'priority' => 24,
            'is_active' => 1
        ],
        // Rule 24
        [
            'rule_name' => 'Rule 24',
            'distance_from' => 301,
            'distance_to' => 400,
            'base_rate' => 1268,
            'per_km_rate' => 0,
            'weight_min' => 50,
            'weight_max' => 75,
            'weight_operator' => 'AND',
            'priority' => 25,
            'is_active' => 1
        ],
        // Rule 25
        [
            'rule_name' => 'Rule 25',
            'distance_from' => 401,
            'distance_to' => 500,
            'base_rate' => 1359,
            'per_km_rate' => 0,
            'weight_min' => 50,
            'weight_max' => 75,
            'weight_operator' => 'AND',
            'priority' => 26,
            'is_active' => 1
        ],
        // Rule 26
        [
            'rule_name' => 'Rule 26',
            'distance_from' => 0,
            'distance_to' => 100,
            'base_rate' => 999,
            'per_km_rate' => 0,
            'weight_min' => 75,
            'weight_max' => 100,
            'weight_operator' => 'AND',
            'priority' => 27,
            'is_active' => 1
        ],
        // Rule 27
        [
            'rule_name' => 'Rule 27',
            'distance_from' => 101,
            'distance_to' => 200,
            'base_rate' => 1223,
            'per_km_rate' => 0,
            'weight_min' => 75,
            'weight_max' => 100,
            'weight_operator' => 'AND',
            'priority' => 28,
            'is_active' => 1
        ],
        // Rule 28
        [
            'rule_name' => 'Rule 28',
            'distance_from' => 201,
            'distance_to' => 300,
            'base_rate' => 1395,
            'per_km_rate' => 0,
            'weight_min' => 75,
            'weight_max' => 100,
            'weight_operator' => 'AND',
            'priority' => 29,
            'is_active' => 1
        ],
        // Rule 29
        [
            'rule_name' => 'Rule 29',
            'distance_from' => 301,
            'distance_to' => 400,
            'base_rate' => 1530,
            'per_km_rate' => 0,
            'weight_min' => 75,
            'weight_max' => 100,
            'weight_operator' => 'AND',
            'priority' => 30,
            'is_active' => 1
        ],
        // Rule 30
        [
            'rule_name' => 'Rule 30',
            'distance_from' => 401,
            'distance_to' => 500,
            'base_rate' => 1646,
            'per_km_rate' => 0,
            'weight_min' => 75,
            'weight_max' => 100,
            'weight_operator' => 'AND',
            'priority' => 31,
            'is_active' => 1
        ]
    ];

    $imported_count = 0;
    $errors = [];

    foreach ( $rules as $rule_data ) {
        try {
            $rule_id = dbs_insert_shipping_rule( $rule_data );
            if ( $rule_id ) {
                $imported_count++;
                echo "✓ Importováno: {$rule_data['rule_name']} (ID: {$rule_id}, Priorita: {$rule_data['priority']})<br>";
            } else {
                $errors[] = "Chyba při importu: {$rule_data['rule_name']}";
                echo "✗ Chyba při importu: {$rule_data['rule_name']}<br>";
            }
        } catch ( Exception $e ) {
            $errors[] = "Výjimka při importu {$rule_data['rule_name']}: " . $e->getMessage();
            echo "✗ Výjimka při importu {$rule_data['rule_name']}: " . $e->getMessage() . "<br>";
        }
    }

    return [
        'imported' => $imported_count,
        'errors' => $errors
    ];
}

// Spuštění importu
if ( isset( $_GET['run_import'] ) && $_GET['run_import'] === 'yes' ) {
    echo '<h1>Import Shipping Rules</h1>';
    
    // Kontrola, zda máme vymazat existující pravidla
    if ( isset( $_GET['clear_existing'] ) && $_GET['clear_existing'] === 'yes' ) {
        echo '<p>🗑️ Mažu všechna existující shipping rules...</p>';
        $deleted_count = clear_all_shipping_rules();
        if ( $deleted_count !== false ) {
            echo "<p>✓ Smazáno {$deleted_count} existujících pravidel</p>";
        } else {
            echo "<p>⚠️ Chyba při mazání existujících pravidel</p>";
        }
    }
    
    echo '<p>🚀 Spouštím import 31 shipping rules s upravenými prioritami...</p>';
    
    $result = import_shipping_rules();
    
    echo '<h2>Výsledek importu:</h2>';
    echo '<p><strong>Úspěšně importováno:</strong> ' . $result['imported'] . ' pravidel</p>';
    
    if ( ! empty( $result['errors'] ) ) {
        echo '<h3>Chyby:</h3>';
        foreach ( $result['errors'] as $error ) {
            echo '<p style="color: red;">' . esc_html( $error ) . '</p>';
        }
    }
    
    if ( $result['imported'] > 0 ) {
        echo '<p style="color: green; font-weight: bold;">✓ Import byl úspěšně dokončen!</p>';
        echo '<p><strong>Důležité informace o prioritách:</strong></p>';
        echo '<ul>';
        echo '<li>Rule 31 (doprava zdarma nad 5000 Kč) má prioritu 1 - bude použito jako první</li>';
        echo '<li>Ostatní pravidla mají priority 2-31 podle pořadí</li>';
        echo '<li>Plugin vždy použije pravidlo s nejnižší prioritou, které splňuje podmínky</li>';
        echo '</ul>';
        echo '<p><a href="' . admin_url( 'admin.php?page=distance-shipping-rules' ) . '">Zobrazit importovaná pravidla v adminu</a></p>';
    }
    
    echo '<p><strong>DŮLEŽITÉ:</strong> Po úspěšném importu smažte tento soubor z vašeho serveru!</p>';
    
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Import Shipping Rules</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px; }
            .info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 20px 0; border-radius: 5px; }
            .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px; }
            .btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 10px 10px 0; }
            .btn:hover { background: #005a87; }
            .btn-danger { background: #dc3545; }
            .btn-danger:hover { background: #c82333; }
            .btn-success { background: #28a745; }
            .btn-success:hover { background: #218838; }
        </style>
    </head>
    <body>
        <h1>Import Shipping Rules - Distance Based Shipping</h1>
        
        <div class="warning">
            <h3>⚠️ VAROVÁNÍ</h3>
            <p>Tento skript importuje 31 předpřipravených shipping rules do vašeho webu s upravenými prioritami.</p>
            <ul>
                <li>Ujistěte se, že máte zálohu databáze</li>
                <li>Skript spusťte pouze jednou</li>
                <li>Po importu smažte tento soubor z serveru</li>
            </ul>
        </div>
        
        <div class="success">
            <h3>✅ Upravené priority pro správné fungování:</h3>
            <ul>
                <li><strong>Rule 31 (doprava zdarma nad 5000 Kč):</strong> Priorita 1 - bude použito jako první</li>
                <li><strong>Ostatní pravidla (1-30):</strong> Priority 2-31 podle pořadí</li>
                <li><strong>Logika:</strong> Plugin vždy použije pravidlo s nejnižší prioritou, které splňuje podmínky</li>
            </ul>
        </div>
        
        <div class="info">
            <h3>📋 Co bude importováno:</h3>
            <ul>
                <li>30 pravidel podle vzdálenosti a hmotnosti (0-500 km, 0-100 kg)</li>
                <li>1 pravidlo pro bezplatnou dopravu nad 5000 Kč (priorita 1)</li>
                <li>Všechna pravidla budou aktivní</li>
                <li>Priorita nastavena pro správné fungování</li>
            </ul>
        </div>
        
        <h3>🎯 Možnosti importu:</h3>
        
        <p><strong>Pokud jste připraveni, vyberte jednu z možností:</strong></p>
        
        <a href="?run_import=yes&clear_existing=yes" class="btn btn-danger" onclick="return confirm('Opravdu chcete vymazat všechna existující pravidla a importovat nová? Tato akce se nedá vrátit zpět.')">
            🗑️ Vymazat existující pravidla + Import nových
        </a>
        
        <a href="?run_import=yes" class="btn btn-success" onclick="return confirm('Opravdu chcete spustit import? Existující pravidla zůstanou zachována.')">
            ➕ Import nových pravidel (zachovat existující)
        </a>
        
        <p><small>Doporučujeme první možnost pro čistý import s upravenými prioritami.</small></p>
    </body>
    </html>
    <?php
}
?> 
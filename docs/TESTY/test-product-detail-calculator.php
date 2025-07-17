<?php
/**
 * Testovací stránka pro ověření kalkulátoru na product detail page
 */

// Načtení WordPress
require_once('../../../wp-load.php');

// Kontrola, že WooCommerce je načtené
if (!class_exists('WooCommerce')) {
    die('WooCommerce není načtené');
}

// Získat testovací produkt
$test_product = wc_get_product(1); // První produkt v databázi
if (!$test_product) {
    die('Není k dispozici žádný produkt pro testování');
}

?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Kalkulátor na Product Detail Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .product-info {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .test-section {
            margin: 40px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fafafa;
        }
        .test-section h2 {
            color: #0073aa;
            margin-top: 0;
        }
        .debug-info {
            background: #e7f3ff;
            border: 1px solid #0073aa;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .debug-info h3 {
            margin-top: 0;
            color: #0073aa;
        }
        .debug-info pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test - Kalkulátor na Product Detail Page</h1>
        
        <div class="product-info">
            <h2>Testovací produkt</h2>
            <p><strong>Název:</strong> <?php echo esc_html($test_product->get_name()); ?></p>
            <p><strong>ID:</strong> <?php echo esc_html($test_product->get_id()); ?></p>
            <p><strong>Hmotnost:</strong> <?php echo esc_html($test_product->get_weight()); ?> kg</p>
            <p><strong>Rozměry:</strong> <?php echo esc_html($test_product->get_length()); ?> × <?php echo esc_html($test_product->get_width()); ?> × <?php echo esc_html($test_product->get_height()); ?> cm</p>
        </div>

        <div class="debug-info">
            <h3>Debug informace</h3>
            <p><strong>Funkce dostupné:</strong></p>
            <ul>
                <li>dbs_get_package_weight: <?php echo function_exists('dbs_get_package_weight') ? '✅' : '❌'; ?></li>
                <li>dbs_get_package_dimensions: <?php echo function_exists('dbs_get_package_dimensions') ? '✅' : '❌'; ?></li>
                <li>dbs_get_package_info: <?php echo function_exists('dbs_get_package_info') ? '✅' : '❌'; ?></li>
            </ul>
            
            <p><strong>Test funkce s produktem:</strong></p>
            <?php
            if (function_exists('dbs_get_package_info')) {
                $mock_package = ['contents' => []];
                $package_info = dbs_get_package_info($mock_package, $test_product->get_id());
                echo '<pre>' . esc_html(print_r($package_info, true)) . '</pre>';
            }
            ?>
        </div>

        <div class="test-section">
            <h2>1. Test kalkulátoru s produktem</h2>
            <p>Níže je kalkulátor, který by měl zobrazovat správné údaje o produktu:</p>
            <?php echo do_shortcode('[postovne-checker]'); ?>
        </div>

        <div class="test-section">
            <h2>2. Test AJAX volání</h2>
            <p>Otevřete Developer Tools (F12) a podívejte se na Network tab při odeslání formuláře. Měli byste vidět, že se odesílá product_id.</p>
            <div id="ajax-test-result"></div>
        </div>

        <div class="test-section">
            <h2>3. JavaScript test</h2>
            <p>Klikněte na tlačítko pro test JavaScript funkce getCurrentProductId():</p>
            <button onclick="testGetCurrentProductId()" style="padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Test getCurrentProductId()
            </button>
            <div id="js-test-result"></div>
        </div>
    </div>

    <script>
        function testGetCurrentProductId() {
            if (typeof DBSFrontend !== 'undefined' && DBSFrontend.getCurrentProductId) {
                const productId = DBSFrontend.getCurrentProductId();
                document.getElementById('js-test-result').innerHTML = 
                    '<div style="background: #e7f3ff; padding: 10px; border-radius: 5px; margin-top: 10px;">' +
                    '<strong>Výsledek:</strong> Product ID = ' + productId + 
                    '</div>';
            } else {
                document.getElementById('js-test-result').innerHTML = 
                    '<div style="background: #ffe7e7; padding: 10px; border-radius: 5px; margin-top: 10px;">' +
                    '<strong>Chyba:</strong> DBSFrontend.getCurrentProductId není dostupné' +
                    '</div>';
            }
        }

        // Přidat body třídu pro simulaci product detail page
        document.body.className += ' product-<?php echo esc_js($test_product->get_id()); ?>';
    </script>
</body>
</html> 
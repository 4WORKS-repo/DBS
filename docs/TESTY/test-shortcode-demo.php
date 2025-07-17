<?php
/**
 * Testovací stránka pro demonstraci shortcode [postovne-checker]
 */

// Načtení WordPress
require_once('../../../wp-load.php');

// Kontrola, že WooCommerce je načtené
if (!class_exists('WooCommerce')) {
    die('WooCommerce není načtené');
}

?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo - Postovne Checker Shortcode</title>
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
        .demo-section {
            margin: 40px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fafafa;
        }
        .demo-section h2 {
            color: #0073aa;
            margin-top: 0;
        }
        .code-example {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            margin: 10px 0;
            border-left: 4px solid #0073aa;
        }
        .shortcode-output {
            margin: 20px 0;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #0073aa;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .info-box h3 {
            margin-top: 0;
            color: #0073aa;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .feature-list li:before {
            content: "✅ ";
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Demo - Postovne Checker Shortcode</h1>
        
        <div class="info-box">
            <h3>O shortcode [postovne-checker]</h3>
            <p>Tento shortcode vloží moderní kalkulátor dopravních nákladů s českým rozhraním. Kalkulátor automaticky:</p>
            <ul class="feature-list">
                <li>Najde nejbližší obchod k zadané adrese</li>
                <li>Vypočítá vzdálenost pomocí Google Maps API</li>
                <li>Zobrazí dostupné dopravní sazby</li>
                <li>Automaticky vyplní adresu do WooCommerce formulářů</li>
                <li>Vybere nejlevnější dopravní metodu</li>
                <li>Deaktivuje ostatní dopravní metody</li>
            </ul>
        </div>

        <div class="demo-section">
            <h2>1. Základní použití</h2>
            <div class="code-example">
                [postovne-checker]
            </div>
            <div class="shortcode-output">
                <?php echo do_shortcode('[postovne-checker]'); ?>
            </div>
        </div>

        <div class="demo-section">
            <h2>2. S vlastním nadpisem</h2>
            <div class="code-example">
                [postovne-checker title="Vypočítejte si dopravu"]
            </div>
            <div class="shortcode-output">
                <?php echo do_shortcode('[postovne-checker title="Vypočítejte si dopravu"]'); ?>
            </div>
        </div>

        <div class="demo-section">
            <h2>3. Bez nadpisu</h2>
            <div class="code-example">
                [postovne-checker show_title="no"]
            </div>
            <div class="shortcode-output">
                <?php echo do_shortcode('[postovne-checker show_title="no"]'); ?>
            </div>
        </div>

        <div class="demo-section">
            <h2>4. S vlastním textem tlačítka</h2>
            <div class="code-example">
                [postovne-checker button_text="Zjistit cenu dopravy"]
            </div>
            <div class="shortcode-output">
                <?php echo do_shortcode('[postovne-checker button_text="Zjistit cenu dopravy"]'); ?>
            </div>
        </div>

        <div class="demo-section">
            <h2>5. S vlastními CSS třídami</h2>
            <div class="code-example">
                [postovne-checker class="my-custom-calculator" title="Custom Styling"]
            </div>
            <div class="shortcode-output">
                <?php echo do_shortcode('[postovne-checker class="my-custom-calculator" title="Custom Styling"]'); ?>
            </div>
        </div>

        <div class="demo-section">
            <h2>6. Původní shortcode pro srovnání</h2>
            <div class="code-example">
                [dbs_shipping_calculator]
            </div>
            <div class="shortcode-output">
                <?php echo do_shortcode('[dbs_shipping_calculator]'); ?>
            </div>
        </div>

        <div class="info-box">
            <h3>📋 Všechny dostupné parametry</h3>
            <ul>
                <li><strong>title</strong> - Nadpis kalkulátoru (výchozí: "Kalkulátor poštovného")</li>
                <li><strong>placeholder</strong> - Text v input poli (výchozí: "Zadejte úplnou adresu včetně města a PSČ...")</li>
                <li><strong>button_text</strong> - Text tlačítka (výchozí: "Vypočítat poštovné")</li>
                <li><strong>show_title</strong> - Zobrazit nadpis (výchozí: "yes")</li>
                <li><strong>class</strong> - CSS třídy pro styling</li>
                <li><strong>style</strong> - Styl kalkulátoru (výchozí: "default")</li>
            </ul>
        </div>

        <div class="info-box">
            <h3>🎨 Design funkce</h3>
            <ul>
                <li>Moderní gradient pozadí</li>
                <li>Responsivní design pro mobilní zařízení</li>
                <li>Animace a hover efekty</li>
                <li>Backdrop filter pro moderní vzhled</li>
                <li>České rozhraní s ikonami</li>
                <li>Automatické vyplnění adresy do WooCommerce</li>
            </ul>
        </div>

        <div class="info-box">
            <h3>🔧 Technické detaily</h3>
            <ul>
                <li>AJAX komunikace s backendem</li>
                <li>Session storage pro zachování dat</li>
                <li>Integrace s WooCommerce cart/checkout</li>
                <li>Podpora pro Google Maps a Bing Maps API</li>
                <li>Cachování vzdáleností pro lepší výkon</li>
                <li>Debug nástroje pro diagnostiku</li>
            </ul>
        </div>
    </div>

    <script>
        // Přidání informací o načtení
        console.log('Postovne Checker Demo načteno');
        
        // Kontrola, že jQuery je dostupné
        if (typeof jQuery !== 'undefined') {
            console.log('jQuery je dostupné');
        } else {
            console.log('jQuery není dostupné');
        }
        
        // Kontrola, že dbsAjax je dostupné
        if (typeof dbsAjax !== 'undefined') {
            console.log('dbsAjax je dostupné');
        } else {
            console.log('dbsAjax není dostupné');
        }
    </script>
</body>
</html> 
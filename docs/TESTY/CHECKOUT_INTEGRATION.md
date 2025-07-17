# Checkout Integration - Distance Based Shipping

## Přehled

Třetí část implementace pluginu Distance Based Shipping se zaměřuje na integraci s WooCommerce checkout procesem. Implementace zahrnuje automatické zobrazení kalkulátoru dopravy, validaci adresy, aplikování shipping sazby a zobrazení informací o dopravě.

## Implementované Funkce

### 1. Checkout Kalkulátor

**Soubor:** `includes/functions/checkout-functions.php`

#### Hlavní funkce:
- `dbs_display_checkout_calculator()` - Zobrazí kalkulátor na checkout stránce
- `dbs_add_checkout_calculator()` - Přidá kalkulátor do checkout formuláře
- `dbs_validate_checkout_address()` - Validuje adresu na checkoutu
- `dbs_apply_saved_shipping_on_checkout()` - Aplikuje uloženou shipping sazbu

#### Vlastnosti:
- Automatické zobrazení na checkout stránce
- Zobrazení uložené adresy a ceny dopravy
- Real-time validace adresy
- Loading stavy a error handling
- Responzivní design

### 2. JavaScript Funkcionalita

**Soubor:** `assets/js/checkout.js`

#### Hlavní třída:
- `CheckoutCalculator` - Hlavní třída pro checkout funkcionalitu

#### Funkce:
- Automatický výpočet při načtení stránky
- Debounced address input handling
- AJAX komunikace s backendem
- Address suggestions
- Real-time validace
- Loading stavy a error handling

#### Event Handlers:
- Calculate button click
- Apply shipping button click
- Address input changes
- Checkout form updates
- Address suggestions selection

### 3. CSS Styly

**Soubor:** `assets/css/checkout.css`

#### Design Vlastnosti:
- Moderní, čistý design
- Responzivní layout
- Loading animace
- Error a success stavy
- WooCommerce a Avada kompatibilita
- Accessibility features

#### Komponenty:
- Calculator container
- Form elements
- Address suggestions
- Result display
- Error messages
- Loading states

### 4. AJAX Handlers

#### Registrované akce:
- `dbs_checkout_calculator` - Výpočet dopravy
- `dbs_apply_checkout_shipping` - Aplikování shipping sazby

#### Funkce:
- Validace nonce
- Sanitizace vstupů
- Session storage
- Error handling
- JSON response

### 5. Session Storage

#### Ukládané údaje:
- `dbs_shipping_address` - Doručovací adresa
- `dbs_shipping_distance` - Vzdálenost v km
- `dbs_shipping_cost` - Cena dopravy
- `dbs_shipping_method` - Shipping metoda

#### Funkce:
- Automatické ukládání při výpočtu
- Načítání při načtení stránky
- Validace při změně adresy
- Čištění při deaktivaci

### 6. WooCommerce Integrace

#### Hooky:
- `woocommerce_checkout_before_customer_details` - Zobrazení kalkulátoru
- `woocommerce_checkout_after_customer_details` - Zobrazení shipping info
- `woocommerce_checkout_update_order_review` - Validace adresy
- `woocommerce_before_checkout_form` - Aplikování shipping

#### Funkce:
- Automatické zobrazení kalkulátoru
- Validace checkout formuláře
- Aplikování shipping sazby
- Aktualizace checkout total

## Použití

### 1. Automatické Zobrazení

Kalkulátor se automaticky zobrazí na checkout stránce, pokud je povolen v nastavení:

```php
// V admin nastavení
dbs_get_option('show_calculator_on_checkout', true)
```

### 2. Manuální Zobrazení

Pro manuální zobrazení na jakékoliv stránce:

```php
dbs_display_checkout_calculator();
```

### 3. JavaScript Inicializace

```javascript
// Automatická inicializace
$(document).ready(function() {
    if ($('#dbs-checkout-calculator').length) {
        new CheckoutCalculator();
    }
});
```

### 4. AJAX Volání

```javascript
// Výpočet dopravy
$.ajax({
    url: dbs_checkout.ajax_url,
    type: 'POST',
    data: {
        action: 'dbs_checkout_calculator',
        address: 'Adresa',
        nonce: dbs_checkout.nonce
    },
    success: function(response) {
        // Zpracování výsledku
    }
});
```

## Konfigurace

### 1. Admin Nastavení

V admin panelu lze konfigurovat:
- Zobrazení kalkulátoru na checkoutu
- Automatický výpočet
- Validaci adresy
- Session storage

### 2. CSS Customizace

Styly lze upravit pomocí CSS proměnných:
```css
:root {
    --primary-color: #007cba;
    --success-color: #28a745;
    --error-color: #dc3545;
}
```

### 3. JavaScript Events

Dostupné custom events:
```javascript
$(document).on('dbs:calculation:start', function() {
    // Začátek výpočtu
});

$(document).on('dbs:calculation:complete', function(e, data) {
    // Dokončení výpočtu
});

$(document).on('dbs:shipping:applied', function(e, data) {
    // Aplikování dopravy
});
```

## Testování

### 1. Testovací Stránka

Dostupná v admin panelu: **DBS Settings > Test Checkout**

### 2. Testované Funkce:
- Zobrazení kalkulátoru
- Session storage
- AJAX handlery
- Asset loading
- Hook registrace
- JavaScript funkcionalita
- CSS styly
- Error handling
- Performance
- Kompatibilita

### 3. Automatické Testy

```php
// Test session storage
$test_address = 'Testovací adresa 123, Praha, 12000';
WC()->session->set('dbs_shipping_address', $test_address);
$saved_address = WC()->session->get('dbs_shipping_address');

// Test AJAX handlerů
$ajax_actions = ['dbs_checkout_calculator', 'dbs_apply_checkout_shipping'];
foreach ($ajax_actions as $action) {
    if (has_action("wp_ajax_{$action}")) {
        // Handler je registrován
    }
}
```

## Kompatibilita

### 1. WordPress
- WP 4.7+
- PHP 7.0-8.4
- MySQL 5.6+

### 2. WooCommerce
- WC 3.0+
- Kompatibilní s checkout hooks
- Session storage support

### 3. Avada Theme
- Avada 6.0-7.11
- CSS kompatibilita
- JavaScript integrace

### 4. Prohlížeče
- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Bezpečnost

### 1. Nonce Validace
```php
check_ajax_referer('dbs_nonce', 'nonce');
```

### 2. Sanitizace Vstupů
```php
$address = sanitize_text_field($_POST['address'] ?? '');
$shipping_cost = floatval($_POST['shipping_cost'] ?? 0);
```

### 3. Capability Checks
```php
if (!current_user_can('manage_options')) {
    wp_die(__('Unauthorized access', 'distance-based-shipping'));
}
```

### 4. SQL Injection Protection
```php
$wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id);
```

## Performance

### 1. Optimalizace
- Debounced input handling
- Lazy loading assets
- Cached geocoding results
- Session storage

### 2. Monitoring
- Execution time tracking
- Memory usage monitoring
- Error logging
- Performance metrics

### 3. Caching
- Geocoding cache
- Distance calculation cache
- Shipping rules cache
- Session data cache

## Troubleshooting

### 1. Časté Problémy

**Kalkulátor se nezobrazuje:**
- Zkontroluj, zda je na checkout stránce
- Ověř, zda je povolen v nastavení
- Zkontroluj JavaScript chyby

**AJAX požadavky selhávají:**
- Ověř nonce validaci
- Zkontroluj PHP error log
- Ověř WooCommerce session

**Styly se nenačítají:**
- Zkontroluj CSS soubor
- Ověř enqueue funkce
- Zkontroluj cache

### 2. Debug Informace

```php
// Zapněte debug mode
define('DBS_DEBUG', true);

// Zkontroluj logy
error_log('DBS Debug: ' . $message);
```

### 3. Support

Pro technickou podporu:
- Zkontroluj error logy
- Ověř kompatibilitu
- Testuj na staging prostředí
- Kontaktujte vývojáře

## Budoucí Rozšíření

### 1. Plánované Funkce
- Multi-language support
- Advanced caching
- Performance optimization
- Mobile app integration

### 2. API Rozšíření
- REST API endpoints
- Webhook support
- Third-party integrations

### 3. UI/UX Vylepšení
- Advanced animations
- Better mobile experience
- Accessibility improvements
- Custom themes support

## Závěr

Checkout integrace je plně funkční a připravená k použití. Implementace zahrnuje všechny potřebné funkce pro bezpečnou a uživatelsky přívětivou integraci s WooCommerce checkout procesem.

Pro další informace nebo podporu kontaktujte vývojový tým. 
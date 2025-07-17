# Oprava Shipping Cache při Změně Množství

## Problém

Při změně množství produktů v košíku se shipping pravidla neaktualizovala, protože WooCommerce cache nebyla invalidována. Shipping pravidla se aktualizovala pouze při změně adresy.

**Příčina:** Cache klíč se generoval pouze na základě adresy, ale nebral v úvahu změny v košíku (hmotnost, hodnota, množství).

## Řešení

### 1. Rozšíření Cache Klíče

**Soubor:** `includes/class-dbs-shipping-method.php`

**Před:**
```php
$cache_key = 'dbs_shipping_' . md5( $destination );
```

**Po:**
```php
$cart_hash = $this->get_cart_hash( $package );
$cache_key = 'dbs_shipping_' . md5( $destination . '_' . $cart_hash );
```

### 2. Nová Funkce get_cart_hash

Přidána funkce pro generování hash na základě obsahu košíku:

```php
private function get_cart_hash( array $package ): string {
    $cart_data = [];
    
    // Get cart total
    if ( function_exists( 'WC' ) && WC() && WC()->cart ) {
        $cart_data['total'] = WC()->cart->get_cart_contents_total();
        $cart_data['weight'] = WC()->cart->get_cart_contents_weight();
        $cart_data['item_count'] = WC()->cart->get_cart_contents_count();
    }
    
    // Get package contents
    if ( ! empty( $package['contents'] ) ) {
        $contents_hash = [];
        foreach ( $package['contents'] as $item_key => $item ) {
            $contents_hash[] = $item['product_id'] . '_' . $item['quantity'] . '_' . ( $item['variation_id'] ?? 0 );
        }
        $cart_data['contents'] = implode( '|', $contents_hash );
    }
    
    return md5( wp_json_encode( $cart_data ) );
}
```

### 3. Centralizovaná Funkce pro Invalidaci Cache

**Soubor:** `includes/functions/checkout-functions.php`

Přidána funkce `dbs_invalidate_all_cache()`:

```php
function dbs_invalidate_all_cache() {
    // Clear WooCommerce session cache
    if ( function_exists( 'WC' ) && WC() && WC()->session ) {
        WC()->session->__unset( 'shipping_for_package_0' );
        WC()->session->__unset( 'shipping_for_package_1' );
        WC()->session->__unset( 'shipping_for_package_2' );
        WC()->session->__unset( 'shipping_for_package_3' );
        WC()->session->__unset( 'shipping_for_package_4' );
        WC()->session->__unset( 'shipping_for_package_5' );
        WC()->session->__unset( 'dbs_shipping_distance' );
        WC()->session->__unset( 'dbs_shipping_cost' );
        WC()->session->__unset( 'dbs_shipping_method' );
        WC()->session->__unset( 'dbs_applied_shipping_rate' );
    }
    
    // Clear WordPress transients (DBS cache)
    global $wpdb;
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_dbs_shipping_%'
        )
    );
}
```

### 4. Aktualizace Všech Hooků

Všechny funkce pro invalidaci cache nyní používají centralizovanou funkci:

- `dbs_trigger_shipping_recalculation()`
- `dbs_trigger_shipping_recalculation_checkout()`
- `dbs_trigger_shipping_recalculation_ajax()`
- `dbs_trigger_shipping_recalculation_cart_updated()`
- `dbs_trigger_shipping_recalculation_cart_ajax()`
- `dbs_trigger_shipping_recalculation_cart_item()`
- `dbs_trigger_shipping_recalculation_cart_total()`

### 5. Aktualizace AJAX Handler

**Soubor:** `includes/functions/ajax-functions.php`

AJAX handler nyní používá centralizovanou funkci:

```php
function dbs_ajax_invalidate_shipping_cache(): void {
    // ... validation ...
    
    if ( WC()->cart ) {
        WC()->cart->calculate_shipping();
        
        // Clear all cache using the centralized function
        if ( function_exists( 'dbs_invalidate_all_cache' ) ) {
            dbs_invalidate_all_cache();
        }
    }
}
```

## Testování

### Spuštění Testu

```bash
php test-shipping-cache-fix.php
```

### Manuální Test

1. **Zapněte debug mód** v admin rozhraní
2. **Přidejte produkt** do košíku s hmotností, která spadá do jednoho pravidla
3. **Změňte množství** tak, aby celková hmotnost spadala do jiného pravidla
4. **Zkontrolujte**, zda se shipping pravidlo změnilo
5. **Zkontrolujte debug log** pro zprávy o invalidaci cache

### Očekávané Chování

- ✅ Při změně množství se shipping cache invaliduje
- ✅ Shipping pravidla se přepočítají podle nové hmotnosti
- ✅ Debug log obsahuje zprávy o invalidaci cache
- ✅ Frontend se aktualizuje s novým shipping pravidlem

## Debug Zprávy

Při zapnutém debug módu se v logu objeví:

```
DBS: All cache invalidated (session + transients)
DBS: Shipping cache invalidated due to cart item change
DBS: Shipping cache invalidated due to cart update
DBS: Shipping cache invalidated due to cart total change
```

## Kompatibilita

- ✅ WooCommerce 3.0+
- ✅ WordPress 5.0+
- ✅ Avada theme
- ✅ AJAX cart updates
- ✅ Manual quantity changes

## Výkon

- Cache se invaliduje pouze při skutečné změně množství
- Debounce mechanismus zabraňuje příliš častým AJAX požadavkům
- WooCommerce nativní shipping cache systém je respektován
- WordPress transients jsou invalidovány pouze při změně košíku

## Troubleshooting

### Problém: Cache se neinvaliduje

1. Zkontrolujte, zda jsou hooky registrovány:
   ```php
   has_action('woocommerce_cart_updated', 'dbs_trigger_shipping_recalculation_cart_updated')
   ```

2. Zkontrolujte debug log pro chyby

3. Ověřte, zda je AJAX handler registrován:
   ```php
   has_action('wp_ajax_dbs_invalidate_shipping_cache', 'dbs_ajax_invalidate_shipping_cache')
   ```

### Problém: JavaScript nefunguje

1. Zkontrolujte konzoli pro JavaScript chyby
2. Ověřte, zda je `dbs_ajax` objekt dostupný
3. Zkontrolujte, zda jsou event handlery navázány

### Problém: Shipping pravidla se nemění

1. Zkontrolujte, zda máte pravidla s hmotnostními podmínkami
2. Ověřte, zda se celková hmotnost skutečně změnila
3. Zkontrolujte priority pravidel

## Změny v Kódu

### Přidané Funkce

- `get_cart_hash()` - generuje hash na základě obsahu košíku
- `dbs_invalidate_all_cache()` - invaliduje všechny cache

### Upravené Funkce

- `calculate_shipping()` - nyní používá cart hash v cache klíči
- Všechny funkce pro invalidaci cache nyní používají centralizovanou funkci

### Nové Hooky

- Všechny existující hooky nyní invalidují jak session cache, tak WordPress transients

## Výsledek

Po této opravě se shipping pravidla budou správně aktualizovat při změně množství produktů v košíku, protože:

1. **Cache klíč obsahuje informace o košíku** - změna množství vytvoří nový cache klíč
2. **Všechny cache jsou invalidovány** - jak session, tak WordPress transients
3. **Hooky reagují na všechny změny** - množství, cart total, adresa
4. **Debug log poskytuje informace** - o tom, kdy a proč se cache invaliduje 
# Optimalizovaná Integrace Distance Based Shipping

## Přehled

Tato optimalizace implementuje plně nativní integraci s WooCommerce bez vlastních formulářů na Cart/Checkout stránkách. Plugin nyní používá pouze výchozí WooCommerce pole pro adresu a dynamicky počítá cenu dopravy podle vzdálenosti.

## Klíčové Funkce

### 1. **Nativní WooCommerce Integrace**
- ✅ Používá pouze výchozí WooCommerce pole pro adresu
- ✅ Žádné vlastní formuláře na Cart/Checkout
- ✅ Plná integrace s WooCommerce checkout flow

### 2. **Inteligentní Detekce Adresy**
Priorita detekce adresy:
1. **Shipping address** (pokud je vyplněna)
2. **Billing address** (fallback)
3. **Session data** (pokud je uložena)

### 3. **Dynamické Přepsání Shipping Metod**
- ✅ Automaticky odstraní všechny ostatní shipping metody
- ✅ Ponechá pouze naši "Distance Based Shipping" metodu
- ✅ Dynamicky vypočítá cenu podle vzdálenosti

### 4. **Optimalizace Výkonu**
- ✅ Cachování výsledků výpočtu (1 hodina)
- ✅ API volání pouze při změně adresy
- ✅ Minimalizace zbytečných výpočtů

### 5. **Kalkulátor Pouze na Detailu Produktu**
- ✅ Kalkulátor zůstává pouze na detailu produktu
- ✅ Odstraněn z Cart/Checkout stránek
- ✅ Oddělený od logiky košíku/pokladny

## Implementace

### 1. **Hook pro Filtrování Shipping Metod**

```php
function dbs_filter_shipping_methods( $rates ) {
    // Najít naši Distance Based Shipping metodu
    $dbs_rate = null;
    foreach ( $rates as $rate_id => $rate ) {
        if ( strpos( $rate_id, 'distance_based' ) !== false ) {
            $dbs_rate = $rate;
            break;
        }
    }
    
    // Vrátit pouze naši metodu
    if ( $dbs_rate ) {
        return array( $dbs_rate->id => $dbs_rate );
    }
    
    return $rates;
}
add_filter( 'woocommerce_package_rates', 'dbs_filter_shipping_methods', 100 );
```

### 2. **Optimalizovaná Detekce Adresy**

```php
private function get_optimized_destination_address( array $package ): string {
    // 1. Shipping address
    if ( ! empty( $package['destination']['city'] ) ) {
        return $this->get_destination_address( $package );
    }

    // 2. Billing address
    if ( function_exists( 'WC' ) && WC() && WC()->customer ) {
        $billing_address = WC()->customer->get_billing_address();
        if ( ! empty( $billing_address ) ) {
            return $billing_address;
        }
    }

    // 3. Session data
    if ( function_exists( 'WC' ) && WC() && WC()->session ) {
        $shipping_address = WC()->session->get( 'shipping_address' );
        if ( ! empty( $shipping_address ) ) {
            return $shipping_address;
        }
    }

    return '';
}
```

### 3. **Cachování Výsledků**

```php
// Check cache first
$cache_key = 'dbs_shipping_' . md5( $destination );
$cached_result = get_transient( $cache_key );

if ( $cached_result !== false ) {
    $this->add_rate( $cached_result );
    return;
}

// Calculate and cache
$rate = $this->calculate_rate_from_rule( $rule, $distance, $package );
if ( $rate ) {
    set_transient( $cache_key, $rate, HOUR_IN_SECONDS );
    $this->add_rate( $rate );
}
```

### 4. **Odstranění Kalkulátoru z Cart/Checkout**

```php
// Odstraněno z shipping-functions.php:
// add_action( 'woocommerce_after_cart_table', 'dbs_display_cart_shipping_calculator', 20 );

// Odstraněno z checkout-functions.php:
// add_action('woocommerce_checkout_before_customer_details', 'dbs_display_checkout_calculator');

// Upraveno v JS:
insertShippingCalculator: function () {
    // Vkládat pouze na stránce produktu
    if ($('body').hasClass('single-product')) {
        const calculator = this.createShippingCalculatorHTML();
        $(".dbs-shipping-calculator-placeholder").replaceWith(calculator);
    }
}
```

## Použití

### 1. **Automatické Fungování**
Plugin funguje automaticky po aktivaci:
- Detekuje adresu zákazníka
- Počítá vzdálenost a cenu
- Aplikuje shipping metodu
- Aktualizuje celkovou cenu

### 2. **Kalkulátor na Detailu Produktu**
Pro zobrazení kalkulátoru na detailu produktu:
```
[postovne-checker]
```

### 3. **Testování**
Přístup k testovací stránce:
```
WordPress Admin → Distance Shipping → Optimized Test
```

## Výhody

### 1. **Uživatelská Zkušenost**
- ✅ Jednodušší checkout proces
- ✅ Žádné duplicitní formuláře
- ✅ Nativní WooCommerce flow

### 2. **Výkon**
- ✅ Rychlejší načítání stránek
- ✅ Méně API volání
- ✅ Cachování výsledků

### 3. **Kompatibilita**
- ✅ Plná kompatibilita s WooCommerce
- ✅ Funguje s jakýmkoliv tématem
- ✅ Žádné konflikty s jinými pluginy

### 4. **Údržba**
- ✅ Jednodušší kód
- ✅ Méně hooků
- ✅ Lepší debugování

## Nastavení

### 1. **Povolení Debug Modu**
```php
update_option( 'dbs_debug_mode', 1 );
```

### 2. **Nastavení Cachování**
```php
update_option( 'dbs_enable_caching', 1 );
update_option( 'dbs_cache_duration', 24 );
```

### 3. **Fallback Rate**
```php
update_option( 'dbs_fallback_rate', 10 );
```

## Troubleshooting

### 1. **Kalkulátor se stále zobrazuje na Cart/Checkout**
- Zkontrolujte, zda jsou odstraněny všechny hooky
- Ověřte, že JS nevkládá kalkulátor na tyto stránky

### 2. **Shipping metoda se nezobrazuje**
- Zkontrolujte registraci shipping metody
- Ověřte, zda je metoda povolena v zóně

### 3. **Cena se nepočítá**
- Zkontrolujte, zda jsou nastaveny obchody a pravidla
- Ověřte debug logy

### 4. **Pomalý výkon**
- Zkontrolujte cachování
- Ověřte API limity
- Zkontrolujte debug logy

## Závěr

Tato optimalizace poskytuje:
- **Plně nativní integraci** s WooCommerce
- **Optimalizovaný výkon** s cachováním
- **Jednodušší uživatelskou zkušenost**
- **Lepší kompatibilitu** s tématy a pluginy

Plugin nyní funguje jako skutečná nativní WooCommerce shipping metoda s dynamickým výpočtem ceny podle vzdálenosti. 
# Oprava Duplikátu Shipping Fee - Distance Based Shipping

## Problém

Plugin způsoboval duplikát shipping nákladů v košíku a checkout souhrnu:

1. **První shipping fee**: Nativní WooCommerce `<tr class="woocommerce-shipping-totals shipping">`
2. **Druhý shipping fee**: Plugin přidaný přes `<tr class="fee">` pomocí `WC()->cart->add_fee()`

Výsledek: **Nesprávná celková cena** - shipping byl započítán dvakrát.

## Řešení

### 1. **Odstranění `add_fee()` Použití**

Odstranil jsem všechna použití `WC()->cart->add_fee()` z těchto souborů:

#### `includes/functions/shipping-functions.php`
```php
// ODSTRANĚNO:
// WC()->cart->add_fee( $fee_name, $applied_rate['cost'], true, 'standard' );

// NOVĚ:
// Shipping sazba se aplikuje pouze přes nativní WooCommerce shipping systém
// Žádné add_fee() - pouze shipping metody
```

#### `includes/functions/ajax-functions.php`
```php
// ODSTRANĚNO:
// WC()->cart->add_fee( $fee_name, $rate_cost, true, 'standard' );

// NOVĚ:
// Shipping sazba se aplikuje pouze přes nativní WooCommerce shipping systém
// Žádné add_fee() - pouze shipping metody
```

### 2. **Použití Pouze Nativního WooCommerce Shipping Systému**

Plugin nyní používá pouze:
- **`woocommerce_package_rates`** hook pro filtrování shipping metod
- **`$this->add_rate()`** v shipping metodě pro přidání shipping sazby
- **Nativní WooCommerce shipping display** bez duplikátů

### 3. **Optimalizovaná Shipping Metoda**

```php
private function calculate_rate_from_rule( object $rule, float $distance, array $package ): ?array {
    $total_cost = $base_rate + ( $distance * $per_km_rate );
    
    return [
        'id'       => $this->id . '_rule_' . $rule->id,
        'label'    => $rule->rule_name . ' (' . $formatted_distance . ')',
        'cost'     => $total_cost,
        'calc_tax' => 'per_order',
        'meta_data' => [
            'distance' => $distance,
            'distance_unit' => 'km',
            'rule_id' => $rule->id,
            'formatted_distance' => $formatted_distance,
        ],
    ];
}
```

### 4. **Hook pro Filtrování Shipping Metod**

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

## Výsledek

### ✅ **Před Opravou**
```html
<tr class="woocommerce-shipping-totals shipping">
  <th>Distance Based Shipping</th>
  <td>121.00 Kč</td>
</tr>
<tr class="fee">
  <th>Doprava: od 50km, &gt;5kg</th>
  <td>121.00 Kč</td>
</tr>
<!-- Celková cena: 242.00 Kč (špatně) -->
```

### ✅ **Po Opravě**
```html
<tr class="woocommerce-shipping-totals shipping">
  <th>Distance Based Shipping (50 km)</th>
  <td>121.00 Kč</td>
</tr>
<!-- Celková cena: 121.00 Kč (správně) -->
```

## Testování

### 1. **Testovací Stránka**
Přístup: `WordPress Admin → Distance Shipping → Fee Fix Test`

### 2. **Kontrolní Body**
- ✅ Žádné použití `add_fee()` v kódu
- ✅ Pouze nativní WooCommerce shipping metody
- ✅ Správná celková cena v košíku
- ✅ Správná celková cena na checkoutu

### 3. **Manuální Test**
1. Přidejte produkt do košíku
2. Zadejte adresu
3. Zkontrolujte, že se shipping zobrazuje pouze jednou
4. Ověřte, že celková cena je správná

## Výhody

### 1. **Správná Funkčnost**
- ✅ Shipping se započítává pouze jednou
- ✅ Správná celková cena
- ✅ Nativní WooCommerce integrace

### 2. **Lepší UX**
- ✅ Žádné zmatení z duplikátních shipping nákladů
- ✅ Jasné zobrazení shipping metody
- ✅ Konzistentní s WooCommerce

### 3. **Technické Výhody**
- ✅ Méně kódu
- ✅ Lepší výkon
- ✅ Snadnější údržba
- ✅ Lepší kompatibilita

## Troubleshooting

### 1. **Shipping se stále zobrazuje dvakrát**
- Zkontrolujte, zda jsou odstraněny všechny `add_fee()` volání
- Ověřte, že se používá pouze `woocommerce_package_rates` hook

### 2. **Shipping se nezobrazuje vůbec**
- Zkontrolujte registraci shipping metody
- Ověřte, zda je metoda povolena v zóně

### 3. **Nesprávná cena**
- Zkontrolujte výpočet v `calculate_rate_from_rule()`
- Ověřte, zda jsou správně nastavena pravidla

## Závěr

Tato oprava zajistila:
- **Správnou funkčnost** bez duplikátů
- **Nativní WooCommerce integraci**
- **Lepší uživatelskou zkušenost**
- **Snadnější údržbu kódu**

Plugin nyní funguje jako skutečná nativní WooCommerce shipping metoda bez duplikátů. 
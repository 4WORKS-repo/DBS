# Opravy chyb v logu - Distance Based Shipping Plugin

## Přehled oprav

Tento dokument popisuje opravy chyb, které se objevovaly v logu pluginu Distance Based Shipping.

## Opravené chyby

### 1. PHP Notice: Funkce __isset nebyla použita správným způsobem

**Problém:**
```
PHP Notice: Funkce __isset <strong>nebyla použita správným způsobem</strong>. 
Použít `array_key_exists` pro kontrolu meta_data na WC_Shipping_Rate
```

**Příčina:**
- Používání `isset( $rate->method_id )` na WC_Shipping_Rate objektech
- WooCommerce 6.0+ vyžaduje použití `property_exists()` místo `isset()`

**Řešení:**
- Vytvořena pomocná funkce `dbs_has_method_id()` pro bezpečnou kontrolu
- Nahrazeno všech 8 výskytů `isset( $rate->method_id )` na `dbs_has_method_id( $rate )`

**Implementace:**
```php
function dbs_has_method_id( $rate ): bool {
    return is_object( $rate ) && property_exists( $rate, 'method_id' ) && ! empty( $rate->method_id );
}
```

### 2. PHP Notice: Indirect modification of overloaded property

**Problém:**
```
PHP Notice: Indirect modification of overloaded property WC_Shipping_Rate::$meta_data has no effect
```

**Příčina:**
- Přímá modifikace `$rate->meta_data` vlastnosti
- WC_Shipping_Rate používá magické metody pro přístup k meta_data

**Řešení:**
- Použití `get_meta_data()` a `set_meta_data()` metod
- Bezpečné nastavení meta_data pomocí správných metod

**Implementace:**
```php
// Místo:
$rate->meta_data['price_includes_tax'] = true;

// Používáme:
$meta_data = $rate->get_meta_data();
if ( ! is_array( $meta_data ) ) {
    $meta_data = array();
}
$meta_data['price_includes_tax'] = true;
$rate->set_meta_data( $meta_data );
```

### 3. Opakované debug zprávy

**Problém:**
- Funkce `dbs_prevent_double_taxation` logovala stejnou zprávu vícekrát
- Zbytečné zahlcování logu

**Řešení:**
- Přidána static proměnná pro sledování již zalogovaných rate ID
- Každá zpráva se loguje pouze jednou za request

**Implementace:**
```php
static $logged_rates = array();
if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! in_array( $rate->id, $logged_rates ) ) {
    error_log( 'DBS: Preventing double taxation - Original cost: ' . $original_cost . ' for rate: ' . $rate->id );
    $logged_rates[] = $rate->id;
}
```

## Ovlivněné soubory

### includes/functions/shipping-functions.php

**Opravené funkce:**
- `dbs_filter_shipping_methods()` - 2 opravy
- `dbs_aggressive_filter_shipping_methods()` - 4 opravy  
- `dbs_ensure_shipping_rates_include_tax()` - 2 opravy
- `dbs_prevent_double_taxation()` - 1 oprava

**Přidané funkce:**
- `dbs_has_method_id()` - nová pomocná funkce

## Testování oprav

### Spuštění testu

```bash
# V prohlížeči otevřete:
http://vase-domena.cz/wp-content/plugins/distance-based-shipping/test-error-fixes.php
```

### Co test kontroluje

1. **Bezpečná kontrola method_id**
   - Test s objektem s method_id
   - Test s objektem bez method_id
   - Test s null hodnotou

2. **Bezpečné nastavení meta_data**
   - Test mock WC_Shipping_Rate objektu
   - Ověření správného nastavení meta_data

3. **Snížení opakovaných zpráv**
   - Simulace opakovaných volání
   - Ověření, že se zpráva loguje pouze jednou

4. **Kontrola funkčnosti**
   - Ověření existence všech potřebných funkcí
   - Kontrola správné registrace hooků

## Očekávané výsledky

Po aplikování oprav by se v logu neměly objevovat:

- ❌ `PHP Notice: Funkce __isset nebyla použita správným způsobem`
- ❌ `PHP Notice: Indirect modification of overloaded property`
- ❌ Opakované zprávy "Preventing double taxation"

Místo toho by měly být pouze:
- ✅ Správné debug zprávy (jednou za request)
- ✅ Informace o výpočtu dopravy
- ✅ Informace o aplikaci pravidel

## Kompatibilita

**WooCommerce verze:** 6.0+
**PHP verze:** 7.4+
**WordPress verze:** 5.0+

## Doporučení pro nasazení

1. **Záloha:** Před nasazením vytvořte zálohu souboru `shipping-functions.php`
2. **Testování:** Otestujte na staging prostředí
3. **Monitoring:** Sledujte log po nasazení
4. **Cache:** Vyčistěte cache po nasazení

## Kontakt

Pro problémy s opravami kontaktujte vývojáře pluginu.

---

*Dokument vytvořen: 16. července 2025*
*Verze pluginu: 1.0* 
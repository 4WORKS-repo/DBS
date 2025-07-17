# Opravy Konfliktů - Distance Based Shipping

## Problém

Při implementaci checkout integrace došlo ke konfliktu funkcí mezi soubory:
- `includes/functions/shipping-functions.php`
- `includes/functions/checkout-functions.php`

### Chybová zpráva:
```
PHP Fatal error: Cannot redeclare dbs_validate_checkout_address() (previously declared in shipping-functions.php:741) in checkout-functions.php on line 109
```

## Identifikované Duplicitní Funkce

### 1. `dbs_validate_checkout_address()`

**Umístění:**
- `shipping-functions.php:741` - původní implementace
- `checkout-functions.php:109` - duplicitní implementace

**Řešení:**
- Přejmenoval jsem funkci v checkout-functions.php na `dbs_validate_checkout_address_wrapper()`
- Funkce nyní volá původní implementaci z shipping-functions.php
- Aktualizoval jsem hook registraci

### 2. `dbs_display_checkout_shipping_info()`

**Umístění:**
- `shipping-functions.php:692` - původní implementace
- `checkout-functions.php:182` - duplicitní implementace

**Řešení:**
- Přejmenoval jsem funkci v checkout-functions.php na `dbs_display_checkout_shipping_info_wrapper()`
- Funkce nyní volá původní implementaci z shipping-functions.php
- Aktualizoval jsem hook registraci

## Implementované Změny

### 1. Checkout Functions (`checkout-functions.php`)

#### Původní problém:
```php
function dbs_validate_checkout_address() {
    // Duplicitní implementace
}
```

#### Opravená verze:
```php
function dbs_validate_checkout_address_wrapper() {
    if (!is_checkout()) {
        return;
    }
    
    // Použij existující funkci z shipping-functions.php
    dbs_validate_checkout_address();
    
    // Dodatečná logika pro checkout
    // ...
}
```

#### Hook registrace:
```php
function dbs_checkout_address_validation() {
    add_action('woocommerce_checkout_update_order_review', 'dbs_validate_checkout_address_wrapper');
}
```

### 2. Shipping Info Display

#### Původní problém:
```php
function dbs_display_checkout_shipping_info() {
    // Duplicitní implementace
}
```

#### Opravená verze:
```php
function dbs_display_checkout_shipping_info_wrapper() {
    if (!is_checkout()) {
        return;
    }
    
    // Použij existující funkci z shipping-functions.php
    dbs_display_checkout_shipping_info();
}
```

#### Hook registrace:
```php
function dbs_add_checkout_shipping_info() {
    add_action('woocommerce_checkout_after_customer_details', 'dbs_display_checkout_shipping_info_wrapper');
}
```

## Zachované Funkce

### Funkce, které nejsou duplicitní:

1. **`dbs_apply_stored_shipping_rate()`** (shipping-functions.php)
   - Pracuje s cart fees
   - Aplikuje shipping fee do košíku

2. **`dbs_apply_saved_shipping_on_checkout()`** (checkout-functions.php)
   - Pracuje s shipping methods
   - Aplikuje shipping metodu na checkoutu

Tyto funkce slouží různým účelům a nejsou duplicitní.

## Testování

### 1. Syntax Check
```bash
php -l includes/functions/checkout-functions.php
php -l includes/functions/shipping-functions.php
```

**Výsledek:** ✅ Žádné syntax chyby

### 2. Funkční Testy
- ✅ Checkout kalkulátor se zobrazuje
- ✅ Validace adresy funguje
- ✅ Shipping info se zobrazuje
- ✅ AJAX handlery fungují

## Doporučení

### 1. Budoucí Vývoj
- Před přidáním nové funkce vždy zkontrolujte, zda již neexistuje
- Používejte prefixy pro různé kontexty (např. `_wrapper`, `_checkout`, `_cart`)
- Dokumentujte závislosti mezi soubory

### 2. Naming Convention
- `dbs_function_name()` - základní funkce
- `dbs_function_name_wrapper()` - wrapper funkce
- `dbs_function_name_checkout()` - checkout specifická funkce
- `dbs_function_name_cart()` - cart specifická funkce

### 3. Organizace Kódu
- Základní funkce v `shipping-functions.php`
- Specifické funkce v příslušných souborech
- Wrapper funkce pro integraci

## Závěr

Konflikty byly úspěšně vyřešeny:
- ✅ Odstraněny duplicitní funkce
- ✅ Zachována funkcionalita
- ✅ Aktualizovány hook registrace
- ✅ Syntax je v pořádku
- ✅ Plugin je funkční

Plugin je nyní připraven k použití bez konfliktů. 
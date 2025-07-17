# Implementace Testu Shipping Cache do Admin Rozhraní

## Přehled

Testovací skript `test-shipping-cache-fix.php` byl implementován do admin subpage pluginu "Testovací nástroje" pro snadnější přístup a testování.

## Implementované Změny

### 1. Aktualizace Funkce `dbs_test_shipping_cache_fix()`

**Soubor:** `includes/functions/admin-functions.php`

Funkce byla rozšířena o kompletní testy z původního skriptu:

- ✅ Test funkce `dbs_invalidate_all_cache()`
- ✅ Test funkce `get_cart_hash()` s reflexí
- ✅ Test cache klíčů s cart hash
- ✅ Test změny množství a generování různých hashů
- ✅ Test hooků pro invalidaci cache
- ✅ Test AJAX handlerů
- ✅ Test shipping pravidel s hmotnostními podmínkami
- ✅ Test cache invalidace
- ✅ Shrnutí a počítání prošlých testů

### 2. Aktualizace Tools Page

**Soubor:** `admin/views/tools-page.php`

- Přidán lepší popis testu shipping cache
- Rozšířeny instrukce pro testování
- Přidány očekávané chování pro shipping cache test

### 3. Odstranění Původního Skriptu

- Původní `test-shipping-cache-fix.php` byl odstraněn
- Všechny funkce jsou nyní dostupné v admin rozhraní

## Jak Použít

### 1. Přístup k Testu

1. Jděte do **WordPress Admin** → **Distance Based Shipping** → **Testovací nástroje**
2. Najděte sekci **"Test opravy shipping cache"**
3. Klikněte na tlačítko **"Spustit test shipping cache"**

### 2. Interpretace Výsledků

#### ✅ Úspěšné Testy
- Všechny funkce existují a fungují
- Cache klíče se liší při změně množství
- Hooky jsou správně registrovány
- AJAX handlery fungují
- Shipping pravidla s hmotnostními podmínkami existují

#### ❌ Selhané Testy
- Některé funkce chybí nebo nefungují
- Cache klíče se neliší při změně množství
- Hooky nejsou registrovány
- AJAX handlery nefungují
- Chybí pravidla s hmotnostními podmínkami

### 3. Manuální Testování

Po úspěšném testu můžete otestovat funkčnost v praxi:

1. **Zapněte debug mód** v admin rozhraní
2. **Přidejte produkt** do košíku s hmotností, která spadá do jednoho pravidla
3. **Změňte množství** tak, aby celková hmotnost spadala do jiného pravidla
4. **Zkontrolujte**, zda se shipping pravidlo změnilo
5. **Zkontrolujte debug log** pro zprávy o invalidaci cache

## Výhody Implementace do Admin Rozhraní

### ✅ Snadný Přístup
- Test je dostupný přímo v admin rozhraní
- Není potřeba spouštět externí skripty
- Integrované s ostatními nástroji pluginu

### ✅ Bezpečnost
- Používá WordPress nonce pro bezpečnost
- Kontroluje oprávnění uživatele
- Validuje vstupní data

### ✅ Uživatelsky Přívětivé
- Přehledné zobrazení výsledků
- Barevné označení úspěchu/selhání
- Detailní instrukce pro testování

### ✅ Integrace s Debug Módem
- Test automaticky kontroluje debug mód
- Doporučuje zapnutí debug módu pro testování
- Poskytuje informace o logování

## Technické Detaily

### Reflexe pro Test get_cart_hash()

```php
$shipping_method = new DBS_Shipping_Method();
$reflection = new ReflectionClass($shipping_method);
$get_cart_hash_method = $reflection->getMethod('get_cart_hash');
$get_cart_hash_method->setAccessible(true);
```

### Testování Cache Klíčů

```php
$cache_key_1 = 'dbs_shipping_' . md5($destination . '_' . $cart_hash_1);
$cache_key_10 = 'dbs_shipping_' . md5($destination . '_' . $cart_hash_10);
```

### Počítání Prošlých Testů

```php
$tests_passed = 0;
$total_tests = 9;

if (function_exists('dbs_invalidate_all_cache')) $tests_passed++;
if (function_exists('get_cart_hash')) $tests_passed++;
// ... další testy
```

## Troubleshooting

### Problém: Test se nespustí
1. Zkontrolujte, zda máte oprávnění administrátora
2. Zkontrolujte, zda je plugin aktivní
3. Zkontrolujte WordPress nonce

### Problém: Některé testy selhávají
1. Zkontrolujte, zda jsou všechny soubory aktualizované
2. Zkontrolujte, zda jsou hooky registrovány
3. Zkontrolujte debug log pro chyby

### Problém: Funkce neexistují
1. Zkontrolujte, zda jsou všechny soubory načtené
2. Zkontrolujte, zda je plugin správně inicializován
3. Zkontrolujte, zda nejsou konflikty s jinými pluginy

## Kompatibilita

- ✅ WordPress 5.0+
- ✅ WooCommerce 3.0+
- ✅ PHP 7.4+
- ✅ Admin oprávnění

## Výsledek

Testovací skript je nyní plně integrován do admin rozhraní pluginu a poskytuje:

- ✅ Kompletní testování všech funkcí
- ✅ Přehledné zobrazení výsledků
- ✅ Detailní instrukce pro testování
- ✅ Bezpečný přístup přes WordPress admin
- ✅ Integrace s debug módem 
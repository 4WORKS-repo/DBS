# Distance Based Shipping - Filtrování Shipping Metod

## Problém

WooCommerce automaticky přidává výchozí shipping metody (Flat Rate, Free Shipping, Local Pickup) kromě vašich custom shipping metod. To způsobuje, že se zobrazují duplicitní shipping náklady.

## Řešení

Implementovali jsme agresivní systém filtrování, který:

1. **Detekuje vaše custom shipping metody** - hledá metody s ID obsahující:
   - `distance_based`
   - `dbs_`
   - `distance_based_shipping`

2. **Odstraňuje výchozí WooCommerce metody** - automaticky zakazuje:
   - Flat Rate
   - Free Shipping  
   - Local Pickup

3. **Používá více úrovní filtrování**:
   - **Agresivní filtr** (priorita 999) - odstraní všechny výchozí metody
   - **Základní filtr** (priorita 999) - ponechá pouze vaše custom metody
   - **Zakázání metod** - úplně zakáže výchozí metody na úrovni WooCommerce

## Implementované funkce

### 1. `dbs_filter_shipping_methods()`
- Základní filtr pro odstranění výchozích metod
- Priorita: 999
- Zachová pouze vaše custom metody

### 2. `dbs_aggressive_filter_shipping_methods()`
- Agresivní filtr s velmi vysokou prioritou
- Priorita: 999
- Úplně odstraní výchozí WooCommerce metody

### 3. `dbs_disable_default_shipping_methods()`
- Zakáže výchozí metody na úrovni WooCommerce
- Spustí se při načtení shipping, cart a checkout

## Testování

### Automatické testování
1. Přejděte do **WooCommerce > DBS Test Shipping** v admin panelu
2. Přidejte produkty do košíku
3. Zkontrolujte výsledky testu

### Manuální testování
1. Přidejte produkty do košíku
2. Přejděte na stránku pokladny (Checkout)
3. Zkontrolujte, že se zobrazuje pouze vaše custom shipping metoda
4. Ověřte, že se nezobrazují výchozí WooCommerce metody

## Debug informace

Pokud je zapnutý `WP_DEBUG`, uvidíte v logu:
```
DBS: Našel jsem naši shipping metodu: distance_based_1
DBS: Odstraňuji všechny ostatní shipping metody
DBS: Agresivní filtr - našel jsem custom metodu, odstraňuji výchozí metody
DBS: Původní počet metod: 3, po filtrování: 1
DBS: Zakazuji výchozí shipping metody
```

## Řešení problémů

### Problém: Stále se zobrazují výchozí metody
**Řešení:**
1. Zkontrolujte, zda má vaše shipping metoda správné ID (obsahuje `distance_based`, `dbs_` nebo `distance_based_shipping`)
2. Zkontrolujte debug log pro chybové zprávy
3. Zkuste deaktivovat a znovu aktivovat plugin

### Problém: Nezobrazuje se žádná shipping metoda
**Řešení:**
1. Zkontrolujte, zda je vaše shipping metoda správně registrována
2. Ověřte, zda máte produkty v košíku
3. Zkontrolujte, zda je adresa vyplněná

### Problém: Filtry se nespouští
**Řešení:**
1. Zkontrolujte, zda je WooCommerce plně načtené
2. Ověřte, zda jsou funkce správně načtené
3. Zkontrolujte debug log

## Konfigurace

### Priorita filtrů
Filtry jsou nastavené s vysokou prioritou (999) pro zajištění, že se spustí po všech ostatních filtrech.

### Detekce custom metod
Systém detekuje vaše custom metody podle:
- ID metody obsahující `distance_based`
- ID metody obsahující `dbs_`
- ID metody obsahující `distance_based_shipping`
- `method_id` obsahující výše uvedené řetězce

### Zakázané výchozí metody
- `flat_rate`
- `free_shipping`
- `local_pickup`

## Bezpečnost

- Všechny funkce obsahují kontroly existence WooCommerce
- Fallback mechanismy pro případ selhání
- Debug logování pro snadné řešení problémů
- Bezpečné odstranění metod bez poškození WooCommerce

## Kompatibilita

- WooCommerce 3.0+
- WordPress 4.7+
- PHP 7.0+
- Avada theme 6.0+

## Aktualizace

Při aktualizaci pluginu se filtry automaticky znovu načtou. Není potřeba žádná další konfigurace. 
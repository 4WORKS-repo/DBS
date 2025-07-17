# Distance Based Shipping - Zpracování DPH

## Problém

WooCommerce automaticky aplikuje DPH (21%) na shipping sazby, což způsobuje, že vaše nastavené ceny se zobrazují s přidaným DPH:

- **Nastavená cena:** 50 Kč
- **Zobrazená cena:** 60.50 Kč (50 Kč + 21% DPH)

## Řešení

Implementovali jsme systém, který zajistí, že vaše ceny jsou považovány za **brutto** (včetně DPH) a WooCommerce na ně nepřidává další DPH.

## Implementované funkce

### 1. **Nastavení v shipping metodě**
- `tax_status` nastaveno na `'taxable'`
- `meta_data['price_includes_tax']` nastaveno na `true`
- `meta_data['is_brutto_price']` nastaveno na `true`

### 2. **Filtry pro kontrolu DPH**
- **`dbs_ensure_shipping_rates_include_tax()`** - označí ceny jako brutto
- **`dbs_control_shipping_tax_status()`** - nastaví tax status na 'none'
- **`dbs_prevent_double_taxation()`** - zabrání dvojitému zdanění

### 3. **Admin nastavení**
- **`dbs_price_includes_tax`** - checkbox pro zapnutí/vypnutí brutto cen
- **`dbs_tax_status`** - výběr tax status (none, taxable, shipping)

## Jak to funguje

### Před opravou:
```
Nastavená cena: 50 Kč
WooCommerce přidá DPH: 50 Kč + 21% = 60.50 Kč
Zobrazená cena: 60.50 Kč
```

### Po opravě:
```
Nastavená cena: 50 Kč (brutto)
WooCommerce neaplikuje DPH: 50 Kč
Zobrazená cena: 50 Kč
```

## Testování

### Automatické testování
1. Přejděte do **WooCommerce > DBS Test Tax** v admin panelu
2. Přidejte produkty do košíku
3. Zkontrolujte výsledky testu

### Manuální testování
1. Přidejte produkty do košíku
2. Přejděte na stránku pokladny (Checkout)
3. Zkontrolujte, že se zobrazuje správná cena dopravy
4. Ověřte, že cena se shoduje s nastavením v pravidlech

## Debug informace

Pokud je zapnutý `WP_DEBUG`, uvidíte v logu:
```
DBS: Shipping rate calculated - Base: 50, Per km: 0, Distance: 10, Total: 50
DBS: Shipping rate marked as brutto - Rate ID: distance_based_rule_1, Cost: 50
DBS: Shipping tax status set to none for rate: distance_based_rule_1
DBS: Preventing double taxation - Original cost: 50 for rate: distance_based_rule_1
```

## Nastavení

### V admin rozhraní:
1. Přejděte do **Distance Shipping > Settings**
2. Najděte sekci **DPH nastavení**
3. Zaškrtněte **"Ceny včetně DPH"**
4. Vyberte **"Tax Status"** (doporučeno: none)
5. Uložte nastavení

### V kódu:
```php
// Nastavení pro brutto ceny
update_option( 'dbs_price_includes_tax', '1' );
update_option( 'dbs_tax_status', 'none' );
```

## Řešení problémů

### Problém: Stále se zobrazuje cena s DPH
**Řešení:**
1. Zkontrolujte, zda je zapnuté nastavení "Ceny včetně DPH"
2. Zkontrolujte debug log pro chybové zprávy
3. Zkuste deaktivovat a znovu aktivovat plugin

### Problém: Ceny se nezobrazují vůbec
**Řešení:**
1. Zkontrolujte, zda je shipping metoda správně registrována
2. Ověřte, zda máte produkty v košíku
3. Zkontrolujte, zda je adresa vyplněná

### Problém: DPH se aplikuje dvojitě
**Řešení:**
1. Zkontrolujte, zda jsou všechny filtry správně načtené
2. Ověřte, zda jsou meta data správně nastavena
3. Zkontrolujte debug log

## Konfigurace

### Tax Status možnosti:
- **`none`** - žádné DPH (doporučeno pro brutto ceny)
- **`taxable`** - standardní DPH
- **`shipping`** - DPH pouze pro dopravu

### Meta data:
- **`price_includes_tax: true`** - cena je včetně DPH
- **`is_brutto_price: true`** - cena je brutto
- **`tax_status: none`** - žádné DPH

## Bezpečnost

- Všechny funkce obsahují kontroly existence WooCommerce
- Fallback mechanismy pro případ selhání
- Debug logování pro snadné řešení problémů
- Bezpečné nastavení DPH bez poškození WooCommerce

## Kompatibilita

- WooCommerce 3.0+
- WordPress 4.7+
- PHP 7.0+
- Avada theme 6.0+

## Aktualizace

Při aktualizaci pluginu se nastavení DPH automaticky znovu načte. Není potřeba žádná další konfigurace.

## Očekávané výsledky

- ✅ Cena 50 Kč se zobrazuje jako 50 Kč (ne 60.50 Kč)
- ✅ Cena 100 Kč se zobrazuje jako 100 Kč (ne 121 Kč)
- ✅ Meta data obsahují `price_includes_tax: true`
- ✅ Meta data obsahují `is_brutto_price: true`
- ✅ Tax status je nastaven na `none`
- ✅ Žádné dvojité zdanění 
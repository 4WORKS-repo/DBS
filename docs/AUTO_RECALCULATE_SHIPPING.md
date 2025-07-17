# Automatický přepočet dopravy při změně množství

## Přehled

Plugin Distance Based Shipping nyní automaticky přepočítává dopravu při změně množství produktů v košíku nebo na checkout stránce. Tato funkcionalita zajišťuje, že cena dopravy bude vždy odpovídat aktuálnímu obsahu košíku.

## Jak to funguje

### JavaScript sledování změn

Plugin sleduje změny množství pomocí několika event listenerů:

1. **Změna množství v košíku**: Sleduje `input[name*="quantity"]` a `.quantity input`
2. **Aktualizace checkout stránky**: Sleduje `updated_checkout` event
3. **Aktualizace košíku**: Sleduje `updated_cart_totals` event

### PHP hooks pro automatický přepočet

Plugin používá následující WooCommerce hooks:

- `woocommerce_cart_item_removed` - při odstranění produktu
- `woocommerce_cart_item_restored` - při obnovení produktu
- `woocommerce_cart_item_set_quantity` - při změně množství
- `woocommerce_checkout_update_order_review` - při aktualizaci checkout
- `wp_ajax_woocommerce_update_order_review` - při AJAX aktualizaci

### Proces přepočtu

1. **Detekce změny**: Plugin detekuje změnu množství nebo adresy
2. **Získání adresy**: Získá aktuální shipping adresu z formuláře nebo session
3. **Výpočet vzdálenosti**: Vypočítá vzdálenost k zákazníkovi
4. **Aplikace pravidel**: Najde vhodné shipping pravidlo podle hmotnosti a vzdálenosti
5. **Výpočet ceny**: Vypočítá finální cenu dopravy
6. **Aktualizace session**: Uloží nové údaje do WooCommerce session

## Implementované funkce

### JavaScript (assets/js/checkout.js)

- `initializeQuantityChangeHandler()` - inicializuje sledování změn množství
- `handleQuantityChange()` - zpracovává změnu množství
- `handleCheckoutQuantityUpdate()` - zpracovává změnu na checkout stránce
- `handleCartQuantityUpdate()` - zpracovává změnu v košíku
- `recalculateShipping()` - spustí přepočet dopravy
- `getCurrentAddress()` - získá aktuální adresu

### PHP (includes/functions/checkout-functions.php)

- `dbs_auto_recalculate_shipping_on_quantity_change()` - registruje hooks
- `dbs_trigger_shipping_recalculation()` - spustí přepočet při změně množství
- `dbs_trigger_shipping_recalculation_checkout()` - spustí přepočet na checkout
- `dbs_trigger_shipping_recalculation_ajax()` - spustí přepočet via AJAX
- `dbs_trigger_shipping_recalculation_address()` - spustí přepočet při změně adresy
- `dbs_get_current_shipping_address()` - získá aktuální adresu
- `dbs_calculate_and_apply_shipping()` - vypočítá a aplikuje dopravu

## Debug mód

Pokud je zapnut debug mód (`dbs_debug_mode = 1`), plugin loguje:

- Změny množství s detaily produktu
- Přepočty dopravy s adresou
- Změny adresy
- Chyby při výpočtu

## Kompatibilita

- **WooCommerce**: Plně kompatibilní s WooCommerce hooks
- **Témata**: Funguje s jakýmkoliv WooCommerce kompatibilním tématem
- **Pluginy**: Nekonfliktuje s jinými shipping pluginy

## Výhody

1. **Automatické**: Žádné manuální akce ze strany zákazníka
2. **Přesné**: Cena dopravy vždy odpovídá aktuálnímu obsahu košíku
3. **Rychlé**: Debounced events zamezují příliš častým výpočtům
4. **Spolehlivé**: Používá standardní WooCommerce hooks a session

## Testování

Pro testování automatického přepočtu:

1. Přidejte produkty do košíku
2. Zadejte shipping adresu
3. Změňte množství produktů
4. Zkontrolujte, zda se doprava automaticky přepočítala

## Troubleshooting

### Doprava se nepřepočítává

1. Zkontrolujte, zda je zapnut debug mód
2. Zkontrolujte logy pro chyby
3. Ověřte, zda je adresa správně vyplněná
4. Zkontrolujte, zda jsou shipping pravidla správně nastavena

### Příliš časté přepočty

- Plugin používá debounce (1000ms) pro omezení frekvence
- Můžete upravit čas v JavaScript kódu

### Konflikty s jinými pluginy

- Plugin používá standardní WooCommerce hooks
- Pokud nastanou konflikty, zkontrolujte prioritu hooks 
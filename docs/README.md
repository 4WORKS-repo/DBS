# Distance Based Shipping Plugin

Plugin pro WooCommerce, který počítá cenu dopravy na základě vzdálenosti mezi obchodem a zákazníkem.

## Shortcodes

### [postovne-checker]

Vloží moderní kalkulátor dopravních nákladů s českým rozhraním.

#### Použití:
```
[postovne-checker]
```

#### Parametry:
- `title` - Nadpis kalkulátoru (výchozí: "Kalkulátor poštovného")
- `placeholder` - Text v input poli (výchozí: "Zadejte úplnou adresu včetně města a PSČ...")
- `button_text` - Text tlačítka (výchozí: "Vypočítat poštovné")
- `show_title` - Zobrazit nadpis (výchozí: "yes")
- `class` - CSS třídy pro styling
- `style` - Styl kalkulátoru (výchozí: "default")

#### Příklady použití:

**Základní použití:**
```
[postovne-checker]
```

**S vlastním nadpisem:**
```
[postovne-checker title="Vypočítejte si dopravu"]
```

**Bez nadpisu:**
```
[postovne-checker show_title="no"]
```

**S vlastními CSS třídami:**
```
[postovne-checker class="my-custom-calculator"]
```

**S vlastním textem tlačítka:**
```
[postovne-checker button_text="Zjistit cenu dopravy"]
```

#### Funkce:
- ✅ Moderní design s gradientem
- ✅ Responsivní design
- ✅ Automatické vyplnění adresy do WooCommerce
- ✅ Výběr nejlevnější dopravní metody
- ✅ Deaktivace ostatních metod
- ✅ Podpora pro cart a checkout stránky
- ✅ Session storage pro zachování dat
- ✅ České rozhraní

### [dbs_shipping_calculator]

Původní shortcode s jednodušším designem.

#### Použití:
```
[dbs_shipping_calculator]
```

#### Parametry:
- `title` - Nadpis kalkulátoru
- `placeholder` - Text v input poli
- `button_text` - Text tlačítka
- `show_title` - Zobrazit nadpis (výchozí: "yes")
- `class` - CSS třídy

## Instalace

1. Nahrajte plugin do složky `/wp-content/plugins/distance-based-shipping/`
2. Aktivujte plugin v WordPress adminu
3. Nastavte obchody a pravidla v adminu
4. Použijte shortcode `[postovne-checker]` na libovolné stránce

## Konfigurace

### Obchody
- Přidejte adresy obchodů v adminu
- Plugin automaticky najde nejbližší obchod

### Pravidla dopravy
- Nastavte vzdálenostní rozsahy
- Definujte základní sazby a sazby za km
- Nastavte priority pravidel

### API klíče
- Google Maps API pro geokódování
- Bing Maps API pro alternativu

## Podporované funkce

- ✅ Výpočet vzdálenosti pomocí Google Maps/Bing Maps
- ✅ Cachování vzdáleností
- ✅ Automatické geokódování adres
- ✅ Integrace s WooCommerce cart/checkout
- ✅ Responsivní design
- ✅ Podpora pro více jazyků
- ✅ Debug nástroje

## Technické detaily

- **PHP**: 7.0+
- **WordPress**: 4.7+
- **WooCommerce**: 3.0+
- **Databáze**: MySQL 5.6+

## Podpora

Pro technickou podporu kontaktujte vývojáře pluginu. 
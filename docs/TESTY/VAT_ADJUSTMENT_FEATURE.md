# DPH Přepínač - Distance Based Shipping Plugin

## Přehled

Nová funkcionalita přepínače DPH umožňuje automatické přizpůsobení cen dopravy pro správné zobrazení DPH v WooCommerce.

## Jak to funguje

### Když je přepínač ZAPNUTÝ:

1. **Admin zadá cenu jako finální (brutto)** - např. 100 Kč
2. **Plugin automaticky přepočítá** - 100 ÷ 1.21 = 82.64 Kč
3. **WooCommerce přidá DPH** - 82.64 × 1.21 = 100 Kč
4. **Výsledek** - Zákazník vidí přesně 100 Kč

### Když je přepínač VYPNUTÝ:

1. **Admin zadá cenu** - např. 100 Kč
2. **Plugin použije cenu beze změny** - 100 Kč
3. **WooCommerce přidá DPH** - 100 × 1.21 = 121 Kč
4. **Výsledek** - Zákazník vidí 121 Kč

## Implementace

### 1. Admin Nastavení

**Umístění:** `admin/views/settings-page.php`

Přidána nová sekce "DPH nastavení" s:
- Přepínač "Přizpůsobit ceny pro DPH"
- Detailní vysvětlení funkčnosti
- Vizuální příklady výpočtů

### 2. Formulář Pravidel

**Umístění:** `admin/partials/rule-form.php`

Přidána informační zpráva v kalkulátoru sazeb:
- Zobrazuje se pouze když je DPH režim aktivní
- Informuje admina o automatickém přepočtu

### 3. Shipping Metoda

**Umístění:** `includes/class-dbs-shipping-method.php`

Upraveny metody:
- `calculate_rate_from_rule()` - přepočet cen z pravidel
- `add_fallback_rate()` - přepočet záložní sazby

### 4. Nastavení

**Umístění:** `includes/functions/admin-functions.php`

Přidáno nové nastavení:
- `dbs_adjust_shipping_for_vat` - boolean hodnota
- Správné zpracování v `dbs_save_settings()`

## Technické detaily

### Konstanta DPH
- Používá se hodnota **1.21** (21% DPH)
- Výpočet: `round(price / 1.21, 2)`

### Přesný výpočet DPH
Pro eliminaci floating-point chyb se používá pokročilá metoda:
1. **Výpočet s vysokou přesností** - 6 desetinných míst
2. **Nucené zaokrouhlení nahoru** - `ceil(net * 100) / 100`
3. **Ověření výpočtu** - kontrola, zda výsledek odpovídá cílové brutto ceně
4. **Alternativní metoda** - pokud první selže
5. **Warning log** - pokud nelze dosáhnout přesné shody

### Debug Logging
Když je debug režim aktivní, loguje se:
```
DBS: VAT adjustment applied - Original: 100.00, Adjusted: 82.64
```

### Kompatibilita
- Funguje s existujícími pravidly
- Neovlivňuje jiné shipping metody
- Zachovává všechny filtry a hooky

## Testování

### Testovací skripty
**Umístění:** `test-vat-adjustment.php`

Obsahuje:
- Simulaci výpočtů
- Aktuální stav nastavení
- Návod na testování

**Umístění:** `test-precise-vat-calculation.php`

Obsahuje:
- Porovnání různých metod výpočtu DPH
- Statistiky přesnosti
- Identifikaci floating-point chyb
- Doporučení nejlepší metody

### Manuální testování

1. **Zapněte přepínač** v nastavení pluginu
2. **Vytvořte pravidlo** s cenou 100 Kč
3. **Přidejte produkt** do košíku
4. **Zadejte adresu** pro dopravu
5. **Ověřte cenu** - měla by být 100 Kč (ne 121 Kč)

## Konfigurace

### Zapnutí funkce
1. Přejděte do **WooCommerce > Distance Based Shipping > Nastavení**
2. Najděte sekci **"DPH nastavení"**
3. Zaškrtněte **"Přizpůsobit ceny pro DPH"**
4. Klikněte **"Uložit nastavení"**

### Vypnutí funkce
1. Přejděte do nastavení
2. Odškrtněte přepínač
3. Uložte nastavení

## Výhody

1. **Jednoduchost** - Admin zadává finální ceny
2. **Přesnost** - Žádné duplicitní DPH
3. **Flexibilita** - Možnost zapnout/vypnout
4. **Transparentnost** - Jasné informace v admin panelu

## Poznámky

- Funkce se aplikuje na všechna pravidla najednou
- Neovlivňuje existující data
- Lze kdykoliv zapnout/vypnout
- Debug režim poskytuje detailní informace

## Troubleshooting

### Cena se nezobrazuje správně
1. Zkontrolujte, zda je přepínač zapnutý
2. Ověřte debug logy
3. Zkuste vypnout cache

### Floating-point chyby
Pokud se ceny zobrazují jako 99.99 místo 100.00:
1. Použijte testovací skript "Test přesnosti DPH"
2. Ověřte, že plugin používá přesný výpočet
3. Zkontrolujte debug logy pro warning zprávy

### Debug informace
Aktivujte debug režim v nastavení pro detailní logy.

## Řešení Floating-Point Chyb

### Problém
Floating-point aritmetika způsobuje zaokrouhlovací chyby:
- 100 ÷ 1.21 = 82.64462809917355...
- 82.64 × 1.21 = 99.9944 ≈ 99.99

### Řešení
Plugin používá pokročilou metodu:
1. **Výpočet s vysokou přesností** (6 desetinných míst)
2. **Nucené zaokrouhlení nahoru** pro zajištění minimální ceny
3. **Ověření výsledku** a případná korekce
4. **Warning logy** pro nemožné případy

### Výsledek
- **Před:** 100 Kč → 99.99 Kč (chyba)
- **Po:** 100 Kč → 100.00 Kč (přesné)

## Verze

- **Přidáno v:** Plugin verze 1.0+
- **Testováno s:** WooCommerce 5.0+
- **Kompatibilní s:** WordPress 5.0+ 
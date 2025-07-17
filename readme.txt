=== Distance Based Shipping for WooCommerce ===
Contributors: Pat & Mat
Tags: woocommerce, shipping, distance, calculator, avada, czech, doprava, vzdalenost, dopravne
Requires at least: 4.7
Tested up to: 6.6
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Pokročilý plugin pro výpočet dopravních nákladů na základě vzdálenosti mezi obchody a zákazníky. Plně kompatibilní s Avada theme a českými e-shopy.

== Description ==

**Distance Based Shipping for WooCommerce** je nejpokročilejší český plugin pro automatický výpočet dopravních nákladů na základě vzdálenosti. Navržen speciálně pro české e-shopy s podporou Avada theme a pokročilými funkcemi pro komplexní dopravní pravidla.

= 🚀 Hlavní funkce =

**💰 Dynamické dopravní náklady**
* Automatický výpočet na základě vzdálenosti mezi obchody a zákazníky
* Flexibilní dopravní pravidla s rozsahy vzdáleností (0-50km, 50-100km, atd.)
* Základní sazby + sazby za kilometr/míli
* Podmínky podle hodnoty objednávky (min/max částka)
* Váhové a rozměrové limity s AND/OR operátory

**🗺️ Mapové služby**
* **OpenStreetMap (Nominatim)** - zdarma, bez API klíče 👍
* **Google Maps API** - nejpřesnější výsledky, silniční vzdálenosti (ceník)
* **Bing Maps API** - alternativa k Google Maps (ceník)
* Automatické cachování vzdáleností pro rychlost

**🏪 Více obchodů**
* Podpora neomezeného počtu obchodů/skladů
* Automatický výběr nejbližšího obchodu k zákazníkovi
* Ruční zadání souřadnic nebo automatické geokódování (1-click aktualizovat souřadnice všech obchodů/skladů)
* Aktivace/deaktivace jednotlivých obchodů

**⚖️ Pokročilé váhové a rozměrové podmínky**
* Váhové limity (kg) s přesným výpočtem podle množství
* Rozměrové limity (délka × šířka × výška v cm)
* AND/OR operátory pro kombinování podmínek
* Automatické přepočítávání při změně množství

**🎨 Avada Theme kompatibilita**
* Plná kompatibilita s Avada 6.0 až 7.11+
* Fusion Builder podpora
* Responzivní design přizpůsobený Avada stylu
* Seamless integrace s Avada checkout

**💶 DPH/Daňové nastavení**
// Pro případ, že nějaký plugin zasahuje do cen
* Inteligentní přepínač pro správné zobrazení DPH
* Brutto/netto ceny dle českých požadavků
* Automatické přizpůsobení pro WooCommerce tax systém
*! Podpora 21% DPH sazby (Je tam takový výpočet, jestli ne 21%, tak je tahle funkce v prdeli --> lze zapnout/vypnout)

= 🛠️ Pokročilé funkce =

**📊 Shortcodes a kalkulátory**
*! `[postovne-checker]` - kalkulátor s českým rozhraním
*! `[dbs_shipping_calculator]` - základní kalkulátor (odebrat)
* Automatické vyplnění adresy do WooCommerce košíku
* Responsivní design pro všechna zařízení

**⚡ Výkon a optimalizace**
* Inteligentní cache systém (1 hodina TTL)
* Minimalizace API volání ❤️
* Optimalizované databázové dotazy ❤️
* Background processing pro velké e-shopy ❤️

**🔧 Debug a nástroje**
* Pokročilé debug nástroje v admin panelu
* Testování specifických pravidel a scénářů
* Diagnostika problémů s váhovými podmínkami
* Log monitoring a error reporting
* Ve složce Docs jsou dalších desítek testů

**🌐 REST API**
* Kompletní REST API pro externí integrace
* Endpoints pro pravidla, obchody, výpočty
* Webhooks pro real-time synchronizaci ❤️
* Developer-friendly dokumentace

= 📦 Podporované WooCommerce funkce =

* **Shipping zones** - plná kompatibilita se zónami
* **Product categories** - filtrování podle kategorií
* **Shipping classes** - pokročilé dopravní třídy
* **Coupons & discounts** - podpora slev a kupónů
* **Variable products** - správná práce s variantami
* **Cart & checkout** - bezproblémová integrace

**Pro vývojáře:**
* Čistý, dokumentovaný kód
* WordPress Coding Standards
* Type hinting a modern PHP
* Extensible architektura

**Pro zákazníky:**
* Rychlé načítání díky cache (možnost manuál. čištění cache)
* Přesné dopravní náklady
* Transparentní kalkulačky
* Mobile-first design


= Minimální požadavky =

**Minimální:**
* **WordPress:** 4.7+
* **WooCommerce:** 3.0+
* **PHP:** 7.0+
* **MySQL:** 5.6+
* **Avada Theme:** 6.0+ (volitelné)

**Doporučené:**
* **WordPress:** 6.0+
* **WooCommerce:** 7.0+
* **PHP:** 8.1+
* **MySQL:** 8.0+
* **Avada Theme:** 7.8+

= Jak přesné jsou výpočty vzdálenosti? =

**Přesnost závisí na mapové službě:**
* **Google Maps:** Velmi přesné silniční vzdálenosti s traffic
* **Bing Maps:** Přesné silniční vzdálenosti
* **OpenStreetMap:** Přibližné vzdálenosti vzdušnou čarou (±10-20%).

= Kompatibilita s themes =

Plugin je navržen tak, aby fungoval s **jakýmkoliv WordPress theme**. Je speciálně optimalizován pro **Avada theme** ale funguje i s:
* **Elementor** themes
* **Divi** theme  
* **Astra** theme
* **GeneratePress** a další

= Mohu vytvořit vlastní dopravní pravidla? =

Ano! Můžete vytvořit **neomezený počet pravidel** s různými podmínkami:

* **Vzdálenostní rozsahy:** 0-25km, 25-50km, 50-100km, 100km+
* **Cenové podmínky:** Min/max hodnota objednávky
* **Váhové limity:** 0-5kg, 5-15kg, 15kg+
* **Rozměrové limity:** Maximální délka/šířka/výška
* **Product categories:** Pouze určité kategorie
* **Shipping classes:** Pokročilé dopravní třídy
* **Priority:** Řazení důležitosti pravidel

= Jak nastavit dopravné zdarma? =

**Způsob 1: Podle hodnoty objednávky**
1. Vytvořte pravidlo s min. hodnotou (např. 2000 Kč)
2. Nastavte základní sazbu na 0 Kč
3. Nastavte nejvyšší prioritu

**Způsob 2: Podle vzdálenosti**
1. Vytvořte pravidlo pro malé vzdálenosti (0-25km)
2. Nastavte základní sazbu na 0 Kč

= Podporuje plugin kupóny a slevy? =

Ano, plugin **plně podporuje WooCommerce kupóny**:
* Kupóny typu "Free shipping"
* Procentuální slevy na dopravu
* Fixní slevy na dopravu
* Kombinace s product kupóny

= Jak řešit problémy s DPH? =

Plugin má **inteligentní DPH systém**:

1. Zapněte **"Přizpůsobit ceny pro DPH"** v nastavení
2. Zadávejte ceny jako **finální (brutto)** - např. 100 Kč
3. Plugin automaticky přepočítá: 100 ÷ 1.21 = 82.64 Kč
4. WooCommerce přidá DPH: 82.64 × 1.21 = 100 Kč
5. **Výsledek:** Zákazník vidí přesně 100 Kč

= Jak používat shortcodes? =

**Moderní kalkulátor:**
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

**Základní kalkulátor:**
```
*! [dbs_shipping_calculator] (odebrat)
```

= Jak optimalizovat výkon? =

**Plugin je automaticky optimalizován:** (teprv bude)
* **Cache systém:** 1 hodina TTL pro vzdálenosti
* **Minimální API volání:** Pouze při změně adresy
* **Database optimalizace:** Indexované dotazy
* **Background processing:** Pro velké e-shopy

**Ruční optimalizace:**
1. Používejte **OpenStreetMap** místo Google Maps (rychlejší)
2. **Vyčistěte cache** v Nástrojích při problémech
3. **Optimalizujte pravidla** - méně je někdy více
4. **Monitoring** přes Debug nástroje

= Kde najdu technickou podporu? =
www.google.com

Tento plugin byl vytvořen s láskou pro české WooCommerce komunity. 🇵🇭 🇵🇭 🇵🇭

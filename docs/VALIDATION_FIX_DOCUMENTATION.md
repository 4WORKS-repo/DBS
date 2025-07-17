# Oprava validace formuláře pro pravidla dopravy

## Problém
Uživatel nemohl vytvořit pravidlo dopravy s nulovou základní sazbou (doprava zdarma), protože validace formuláře vyžadovala zadání alespoň jedné nenulové sazby.

## Řešení

### 1. Opravené soubory

#### `admin/partials/rule-form.php`
- **Před:** Validace vyžadovala alespoň jednu nenulovou sazbu
- **Po:** Validace povoluje nulové hodnoty, kontroluje pouze záporné hodnoty

```javascript
// Původní validace (zakomentovaná):
// if (!baseRate && !perKmRate) {
//     alert('Musíte zadat alespoň jednu sazbu (základní nebo za vzdálenost).');
//     e.preventDefault();
//     return;
// }

// Nová validace:
if (baseRate < 0 || perKmRate < 0) {
    alert('Sazby nemohou být záporné.');
    e.preventDefault();
    return;
}
```

#### `assets/js/admin.js`
- **Před:** Validace vyžadovala alespoň jednu nenulovou sazbu
- **Po:** Validace povoluje nulové hodnoty, kontroluje pouze záporné hodnoty

```javascript
// Původní validace:
if (baseRate === 0 && perKmRate === 0) {
    this.showNotice(
        "Musíte zadat alespoň jednu sazbu (základní nebo za vzdálenost).",
        "error"
    )
    $("#base_rate").focus()
    return false
}

// Nová validace:
if (baseRate < 0 || perKmRate < 0) {
    this.showNotice(
        "Sazby nemohou být záporné.",
        "error"
    )
    $("#base_rate").focus()
    return false
}
```

### 2. Testovací skripty

#### Testovací nástroje v admin rozhraní
- **WordPress Admin → Distance Shipping → Testovací nástroje**
- Test validace formuláře
- Vynucení načtení souborů
- Vyčištění cache

### 3. Postup řešení

#### Pokud problém přetrvává:

1. **Vyčistit cache prohlížeče**
   - Ctrl+F5 nebo Ctrl+Shift+R
   - Nebo zkusit incognito/private režim

2. **Deaktivovat a znovu aktivovat plugin**
   - WordPress Admin → Plugins → Distance Based Shipping → Deactivate
   - WordPress Admin → Plugins → Distance Based Shipping → Activate

3. **Spustit testovací nástroje**
   - WordPress Admin → Distance Shipping → Testovací nástroje
   - Spustit "Test validace formuláře"

### 4. Testování

#### Správné hodnoty pro test:
- **Název pravidla:** Test doprava zdarma
- **Vzdálenost od:** 0
- **Vzdálenost do:** 10
- **Základní sazba:** 0
- **Sazba za kilometr:** 0

#### Očekávaný výsledek:
- ✅ Pravidlo se uloží bez chyby
- ❌ Pokud se zobrazí "Musíte zadat alespoň jednu sazbu", problém přetrvává

### 5. Cache problémy

Pokud se změny neprojeví, může to být způsobeno:

1. **Cache prohlížeče** - vyčistit cache nebo použít incognito režim
2. **WordPress cache** - deaktivovat cache plugin nebo vyčistit cache
3. **Server cache** - kontaktovat hosting providera
4. **CDN cache** - vyčistit CDN cache pokud používáte

### 6. Kontrola úspěšnosti

Po provedení oprav by mělo být možné:
- ✅ Vytvořit pravidlo s `base_rate = 0` a `per_km_rate = 0`
- ✅ Vytvořit pravidlo s `base_rate = 0` a `per_km_rate > 0`
- ✅ Vytvořit pravidlo s `base_rate > 0` a `per_km_rate = 0`
- ❌ Vytvořit pravidlo se zápornými hodnotami (zobrazí se chyba)

### 7. Odstranění testovacích souborů

Testovací nástroje jsou nyní integrovány přímo do admin rozhraní pluginu a nepotřebují samostatné soubory.

## Shrnutí

Problém s validací formuláře byl vyřešen úpravou JavaScript validace v obou relevantních souborech. Nyní je možné vytvářet pravidla dopravy s nulovými hodnotami pro dopravu zdarma. 
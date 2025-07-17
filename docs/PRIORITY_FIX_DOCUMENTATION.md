# Oprava řazení pravidel podle priority

## Problém

Při testování s produktem 1kg se při zvýšení množství z 1ks na 3ks (hmotnost z 1kg na 3kg) aplikovala Rule 1 místo Rule 3, i když Rule 3 měla vyšší prioritu.

**Příčina:** Pravidla se řadila vzestupně podle priority (`$a->priority <=> $b->priority`), což znamenalo, že pravidlo s nižší prioritou (vyšší číslo) se aplikovalo jako první.

## Řešení

### 1. Oprava řazení pravidel

**Soubor:** `includes/class-dbs-shipping-method.php`

**Před:**
```php
// Sort by priority.
usort( $applicable_rules, function( $a, $b ) {
    return $a->priority <=> $b->priority;
} );
```

**Po:**
```php
// Sort by priority (highest priority first).
usort( $applicable_rules, function( $a, $b ) {
    return $b->priority <=> $a->priority; // Sestupně podle priority
} );
```

### 2. Přidání debug výpisů

Přidány debug výpisy pro sledování:
- Kontroly hmotnostních podmínek pro každé pravidlo
- Seznam aplikovatelných pravidel seřazených podle priority

```php
if ( get_option( 'dbs_debug_mode', 0 ) ) {
    error_log( 'DBS: Checking weight/dimensions conditions for rule: ' . $rule->rule_name );
}

// ...

if ( get_option( 'dbs_debug_mode', 0 ) ) {
    error_log( 'DBS: Applicable rules after sorting by priority:' );
    foreach ( $applicable_rules as $rule ) {
        error_log( 'DBS: - Rule: ' . $rule->rule_name . ', Priority: ' . $rule->priority . ', Weight: ' . $rule->weight_min . '-' . $rule->weight_max . 'kg' );
    }
}
```

## Testování

### Scénář 1: 1 kus produktu 1kg (1kg celkem)
- **Rule 1:** 0-5kg, priorita 10 → Aplikuje se
- **Rule 3:** 6-9kg, priorita 20 → Neaplikuje se (hmotnost mimo rozsah)
- **Výsledek:** Rule 1 (správně)

### Scénář 2: 3 kusy produktu 1kg (3kg celkem)
- **Rule 1:** 0-5kg, priorita 10 → Aplikuje se
- **Rule 3:** 6-9kg, priorita 20 → Neaplikuje se (hmotnost mimo rozsah)
- **Výsledek:** Rule 1 (správně)

### Scénář 3: 6 kusů produktu 1kg (6kg celkem)
- **Rule 1:** 0-5kg, priorita 10 → Neaplikuje se (hmotnost mimo rozsah)
- **Rule 3:** 6-9kg, priorita 20 → Aplikuje se
- **Výsledek:** Rule 3 (správně)

## Očekávané chování

### Před opravou:
- Pravidla se řadila vzestupně podle priority
- Pravidlo s nižší prioritou se aplikovalo jako první
- Při konfliktu se aplikovalo špatné pravidlo

### Po opravě:
- Pravidla se řadí sestupně podle priority
- Pravidlo s nejvyšší prioritou se aplikuje jako první
- Při konfliktu se aplikuje správné pravidlo

## Debug log

Při zapnutém debug módu se zobrazují zprávy:

```
DBS: Checking weight/dimensions conditions for rule: Rule 1
DBS: Weight check - Rule: Rule 1, Weight min: 0, Weight max: 5, Package weight: 3
DBS: Weight check - Package weight (3) within range, rule applies
DBS: Rule Rule 1 is applicable

DBS: Checking weight/dimensions conditions for rule: Rule 3
DBS: Weight check - Rule: Rule 3, Weight min: 6, Weight max: 9, Package weight: 3
DBS: Weight check - Package weight (3) below minimum (6), rule rejected
DBS: Rule Rule 3 - weight or dimensions conditions not met

DBS: Applicable rules after sorting by priority:
DBS: - Rule: Rule 1, Priority: 10, Weight: 0-5kg
```

## Závěr

Oprava zajistila, že:
1. Pravidla se správně řadí podle priority (nejvyšší priorita první)
2. Při konfliktu pravidel se aplikuje pravidlo s nejvyšší prioritou
3. Debug výpisy pomáhají diagnostikovat problémy s aplikací pravidel

Testovací skript `test-priority-fix.php` ověřuje správné chování po opravě. 
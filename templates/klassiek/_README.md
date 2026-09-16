# Preset — Klassiek

Overzicht van alle templates die bij de **Klassiek** preset horen.
Wat hier NIET staat, is niet Klassiek-specifiek.

## Bestanden in deze map

```
klassiek/
├── _README.md           ← Dit bestand
├── home.php             ← Home page template
├── trust.php            ← Trust bar (alleen Klassiek gebruikt dit)
└── hero/
    ├── _context.php     ← Gedeelde setup (variabelen + render-closures)
    ├── split.php        ← Hero variant 1
    ├── stacked.php      ← Hero variant 2
    └── centered.php     ← Hero variant 3
```

## Hero-varianten

Klassiek heeft **3 hero-stijlen**. De klant kiest in de admin (Home-editor → Hero → Stijl) welke actief is.

| Variant     | Layout                                             | Wanneer gebruiken                                        |
| ----------- | -------------------------------------------------- | -------------------------------------------------------- |
| `split`     | Tekst links · booking-form rechts                  | Standaard — conversie-gericht met form direct zichtbaar. |
| `stacked`   | Tekst gecentreerd boven · booking-form full-width  | Als het form ademruimte nodig heeft.                     |
| `centered`  | Alleen tekst + knoppen (geen form in hero)         | Als je het form op `/boeken` wil houden.                 |

Nieuwe variant toevoegen?
1. Maak `hero/{naam}.php` (kopieer een bestaande als startpunt).
2. Voeg de slug toe aan de whitelist in `home.php`.
3. Voeg de optie toe in `class-page-editor.php` → `$hero_styles`.

## Secties op de home

Klassiek gebruikt gedeelde secties uit [`../shared/`](../shared) omdat andere presets ze
ook hergebruiken. `trust.php` daarentegen leeft in deze map — alleen Klassiek gebruikt hem.
Volgorde staat bovenaan `home.php`.

Zichtbaarheid van elke sectie is per klant instelbaar in de Home-editor.

## Hoe deze preset actief wordt

De klant kiest de preset in TaxiTheme admin → **Stijl**. Zodra Klassiek gekozen is:

1. `TaxiTheme_Preset::current()` returned `'klassiek'`.
2. `TaxiTheme_Preset::template_path('home')` zoekt in deze volgorde:
   - `templates/klassiek/home.php` ← wint (deze structuur)
   - `templates/home-klassiek.php` ← legacy fallback
   - `templates/home.php` ← generieke fallback
3. Body class wordt `tt-preset-klassiek` — maar Klassiek heeft geen eigen preset-CSS. De basis-styling uit [`style.css`](../../style.css) volstaat. Kleur-varianten via [`assets/palettes/klassiek-*.css`](../../assets/palettes).

## Palettes voor deze preset

Kleuren-varianten voor Klassiek: [`../../assets/palettes/klassiek-*.css`](../../assets/palettes).

# Preset — Bold

Sales-gedreven landing-page opbouw: fullscreen hero, grote prijskaarten, sticky bel-knop.

## Bestanden in deze map

```
bold/
├── _README.md          ← Dit bestand
├── home.php            ← Home page template
├── diensten.php        ← Diensten sub-page (met services-detail + waarom + sticky-call)
├── tarieven.php        ← Tarieven sub-page (met routes-cards + sticky-call)
├── hero.php            ← Fullscreen dark hero met CTAs
├── routes-cards.php    ← Grote gele prijskaarten
└── sticky-call.php     ← Vaste bel-knop rechtsonder
```

## Wat is Bold-specifiek?

Deze parts hebben alleen Bold nodig — andere presets gebruiken ze niet:

| File               | Waarom Bold-only                                           |
| ------------------ | ---------------------------------------------------------- |
| `hero.php`         | Fullscreen dark hero met display-typografie                |
| `routes-cards.php` | Grote gele prijskaarten — Bold's signature routes-look     |
| `sticky-call.php`  | Sticky bel-knop rechtsonder — sales-driven, alleen Bold    |

## Gedeelde parts

De home + sub-pages laden ook parts uit [`../shared/`](../shared) — die zijn gedeeld
met andere presets (usps, services, waarom, contact-cta, etc). Styling is
preset-specifiek via `.tt-preset-bold` scope in [`../../assets/presets/bold/`](../../assets/presets/bold).

## Sub-pages

`diensten.php` en `tarieven.php` gebruiken de Page-Editor (intro + sections + optionele
extra velden), zelfde als de universele varianten — maar ze voegen `sticky-call` toe
en `tarieven` gebruikt `routes-cards` ipv de standaard routes grid.

## Hoe deze preset geactiveerd wordt

Klant kiest "Bold" in TaxiTheme admin → Stijl. `TaxiTheme_Preset::template_path()` zoekt dan:

1. `templates/bold/{role}.php` ← wint (deze map)
2. `templates/{role}-bold.php` ← legacy, bestaat niet meer voor Bold
3. `templates/{role}.php` ← generieke fallback

## Styling

Zie [`../../assets/presets/bold/_README.md`](../../assets/presets/bold/_README.md)
voor de opgesplitste CSS files.

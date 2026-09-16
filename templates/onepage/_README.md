# Preset — One-page

Dark app-style one-pager met glassmorphism, fixed background, scroll-progress bar.

## Bestanden in deze map

```
onepage/
├── _README.md   ← Dit bestand
├── home.php     ← Home page template
└── hero.php     ← One-page hero (gecentreerde tekst + glass booking form)
```

## Wat is Onepage-specifiek?

| File       | Waarom Onepage-only                                                    |
| ---------- | ---------------------------------------------------------------------- |
| `hero.php` | Gecentreerde hero met pulsing dot + glass booking form                 |

Ook Onepage-only, maar in de main [home.php](home.php) zelf ingebakken (niet in eigen file):
- Fixed background (`.tt-onepage-bg`) met gradient + accent-glow
- Scroll-progress bar bovenaan (klein JS blok onderin)

## Gedeelde parts

Alle overige secties komen uit [`../shared/`](../shared) — usps, services, features,
about, routes, waarom, service-area, faq, post-content, contact-cta.

## Sub-pages

Onepage heeft geen eigen sub-page templates. De klant ziet de universele
[`../diensten.php`](../diensten.php), [`../tarieven.php`](../tarieven.php) etc.
met Onepage-styling via de body-class scoping.

## Hoe deze preset geactiveerd wordt

Klant kiest "One-page" in TaxiTheme admin → Stijl.
`TaxiTheme_Preset::template_path('home')` laadt dan `templates/onepage/home.php`.

## Styling

Zie [`../../assets/presets/onepage/_README.md`](../../assets/presets/onepage/_README.md).

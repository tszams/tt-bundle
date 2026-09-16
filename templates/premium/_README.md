# Preset — Premium

Editorial / Apple-esthetiek: rustige typografie, grote foto's, veel witruimte.
Fotografie-gedreven, tijdloos.

## Bestanden in deze map

```
premium/
├── _README.md   ← Dit bestand
├── home.php     ← Home page template
└── hero.php     ← Cinematic hero met bg-image + gradient overlay
```

## Wat is Premium-specifiek?

| File       | Waarom Premium-only                                            |
| ---------- | -------------------------------------------------------------- |
| `hero.php` | Fullscreen bg-image met dark overlay, wit-op-donker CTA        |

## Gedeelde parts

Home laadt uit [`../shared/`](../shared): about, services, spotlight, features,
steps, usps, waarom, routes, service-area, faq, post-content, contact-cta.

## Sub-pages

Premium heeft geen eigen sub-page templates. De universele
[`../diensten.php`](../diensten.php), [`../tarieven.php`](../tarieven.php) etc.
worden gebruikt met Premium-styling via body-class scoping.

## Hoe deze preset geactiveerd wordt

Klant kiest "Premium" in TaxiTheme admin → Stijl.
`TaxiTheme_Preset::template_path('home')` laadt dan `templates/premium/home.php`.

## Styling

Zie [`../../assets/presets/premium/_README.md`](../../assets/presets/premium/_README.md).

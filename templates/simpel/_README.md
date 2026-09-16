# Preset — Simpel

Flat design: geen radii op cards, geen shadows, geen gradients. Minimalistisch en clean.

## Bestanden in deze map

```
simpel/
├── _README.md      ← Dit bestand
├── home.php        ← Home page template
├── services.php    ← Staxi-style flat cards (icon of image + full-width CTA)
└── hero/
    ├── form.php    ← Hero variant 1: met booking form
    └── image.php   ← Hero variant 2: met car photo
```

## Hero-varianten

Simpel heeft **2 hero-stijlen**. De klant kiest in de admin (Home-editor) welke actief is.

| Variant     | Layout                                    |
| ----------- | ----------------------------------------- |
| `form.php`  | Booking form vult de hero                 |
| `image.php` | Car photo rechts + tekst links            |

## Wat is Simpel-specifiek?

| File            | Waarom Simpel-only                                             |
| --------------- | -------------------------------------------------------------- |
| `hero/*.php`    | Nav-kleur bg, pill-CTAs, flat design                           |
| `services.php`  | Staxi-style flat cards met full-width bottom-CTA               |

Simpel gebruikt zijn eigen services (`simpel/services.php`) in plaats van de gedeelde
`shared/services.php`, omdat de layout wezenlijk anders is (gecentreerd, full-CTA).

## Gedeelde parts

Home laadt ook uit [`../shared/`](../shared): spotlight, routes, waarom,
service-area, post-content, contact-cta.

Wat opvalt: Simpel gebruikt bewust GEEN usps, about, features, faq — dat past niet
bij de "kort en to-the-point" filosofie van deze preset.

## Sub-pages

Simpel heeft geen eigen sub-page templates. De universele
[`../diensten.php`](../diensten.php), [`../tarieven.php`](../tarieven.php) etc.
worden gebruikt met Simpel-styling via body-class scoping.

## Hoe deze preset geactiveerd wordt

Klant kiest "Simpel" in TaxiTheme admin → Stijl.
`TaxiTheme_Preset::template_path('home')` laadt dan `templates/simpel/home.php`.

## Styling

Zie [`../../assets/presets/simpel/_README.md`](../../assets/presets/simpel/_README.md).

# Templates

## Structuur

```
templates/
├── klassiek/    ← Klassiek preset (home, 3 hero varianten, trust)
├── bold/        ← Bold preset (home, sub-pages, hero, routes-cards, sticky-call)
├── onepage/     ← One-page preset (home, hero)
├── premium/     ← Premium preset (home, hero)
├── simpel/      ← Simpel preset (home, 2 hero varianten, services)
├── shared/      ← Componenten gedeeld door meerdere presets
│
├── diensten.php     ← Universele sub-page (elke preset)
├── tarieven.php     ← Universele sub-page
├── faq.php          ← Universele sub-page
├── over-ons.php     ← Universele sub-page
└── contact.php      ← Universele sub-page
```

## Regel

- **Per preset**: alles wat alleen voor die preset relevant is (home, eigen hero, eigen sub-pages, unieke parts)
- **shared/**: HTML-identiek voor alle gebruikers; verschillen lopen via CSS
- **root**: universele sub-pages die door alle presets worden gebruikt

## Hoe routing werkt

`TaxiTheme_Preset::template_path($role)` zoekt in deze volgorde:

1. `templates/{preset}/{role}.php` ← preset-specifiek (wint)
2. `templates/{role}-{preset}.php` ← legacy preset-suffix (bestaat nergens meer)
3. `templates/{role}.php` ← universele fallback

Voorbeelden:
- Preset=Bold + role=home → `bold/home.php`
- Preset=Bold + role=diensten → `bold/diensten.php` (bestaat, dus die wint)
- Preset=Onepage + role=diensten → `diensten.php` (Onepage heeft geen eigen, valt terug op universele)

## Nieuwe preset toevoegen

1. Maak `templates/{nieuwe-preset}/`
2. `home.php` + `_README.md` erin
3. Preset-specifieke parts erin, gedeelde uit `shared/` blijven aanroepen
4. Voeg de slug toe aan `class-preset.php` → `PRESETS`
5. Eventueel `assets/presets/{nieuwe-preset}/*.css` voor styling

## Bestaande preset uitbreiden

- **Nieuwe hero variant**? Voeg toe in `{preset}/hero/` en registreer in de home
- **Preset-specifieke sub-page** (zoals Bold's diensten met sticky-call)? Zet in `{preset}/{role}.php` — Preset::template_path pakt hem automatisch op
- **Nieuwe gedeelde sectie**? Maak in `shared/`, laad uit alle presets die het willen tonen

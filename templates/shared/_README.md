# Shared parts

Components die door meerdere presets worden gebruikt.
HTML is voor alle presets identiek — visuele verschillen lopen via preset-scoped
CSS (`.tt-preset-{slug}` overrides in [`../../assets/presets/{slug}/`](../../assets/presets)).

## Wanneer een part hier hoort

- Als de HTML-structuur voor **alle presets die het gebruiken** identiek is
- Verschillen zijn puur visueel (kleuren, spacing, backgrounds, layout via CSS grid)

Als de HTML wezenlijk anders moet zijn per preset → maak dan een preset-specifieke
kopie in de preset map (zoals `simpel/services.php` naast `shared/services.php`).

## Wie gebruikt wat

| Part                 | Klassiek | Bold | Onepage | Premium | Simpel |
| -------------------- | :------: | :--: | :-----: | :-----: | :----: |
| `about.php`          |    ✓     |  ✓   |    ✓    |    ✓    |   —    |
| `contact-cta.php`    |    ✓     |  ✓   |    ✓    |    ✓    |   ✓    |
| `faq.php`            |    ✓     |  ✓   |    ✓    |    ✓    |   —    |
| `features.php`       |    —     |  —   |    ✓    |    ✓    |   —    |
| `post-content.php`   |    ✓     |  ✓   |    ✓    |    ✓    |   ✓    |
| `routes.php`         |    ✓     |  —   |    ✓    |    ✓    |   ✓    |
| `service-area.php`   |    ✓     |  ✓   |    ✓    |    ✓    |   ✓    |
| `services.php`       |    ✓     |  ✓   |    ✓    |    ✓    |   —    |
| `services-detail.php`|    —     |  ✓   |    —    |    —    |   —    |
| `spotlight.php`      |    ✓     |  ✓   |    ✓    |    ✓    |   ✓    |
| `steps.php`          |    —     |  —   |    —    |    ✓    |   —    |
| `usps.php`           |    ✓     |  ✓   |    ✓    |    ✓    |   —    |
| `waarom.php`         |    ✓     |  ✓   |    ✓    |    ✓    |   ✓    |

**Preset-specifieke alternatieven** die niet hier horen:
- `trust.php` (alleen Klassiek) → [`../klassiek/trust.php`](../klassiek/trust.php)
- `routes-cards.php`, `sticky-call.php` (alleen Bold) → [`../bold/`](../bold)
- `services.php` Staxi-style (alleen Simpel) → [`../simpel/services.php`](../simpel/services.php)

## Sub-page templates

Universele sub-page templates (voor alle presets) staan in `templates/` root:
- `../diensten.php`
- `../tarieven.php`
- `../faq.php`
- `../over-ons.php`
- `../contact.php`

Deze roepen shared parts aan (bv. `services-detail`, `waarom`, `contact-cta`).
Preset-specifieke sub-pages (zoals Bold's eigen diensten met sticky-call) staan
in de preset-map: `../bold/diensten.php`.

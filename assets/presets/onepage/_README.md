# Preset CSS — One-page

Dark app-style one-pager met glassmorphism, fixed background, scroll-progress bar.
Alle regels gescoped op `.tt-preset-onepage`, geconcateneerd tot 1 inline stylesheet.

## Overzicht

| File                    | Wat het style't                                                 |
| ----------------------- | --------------------------------------------------------------- |
| `_base.css`             | Body dark, smallere container, z-index scaffolding              |
| `_section-base.css`     | Algemene sectie basis (transparant, gecentreerde headers)       |
| `background.css`        | Fixed dark gradient + accent-glow bovenaan                      |
| `scroll-progress.css`   | Scroll-progress bar bovenaan (JS in home template)              |
| `header.css`            | Transparant, brand met accent-punt, pill-CTA                    |
| `hero.css`              | Gecentreerde hero met pulsing dot + glass booking form           |
| `usps.css`              | Glass USP-cards                                                  |
| `routes.css`            | Glass route-rijen met accent-pill prijs                          |
| `services.css`          | Glass services-cards                                             |
| `spotlight.css`         | Spotlight (subtle + promo varianten)                             |
| `features.css`          | Alternating image/text glass rows                                |
| `about.css`             | Transparant about-block met witte tekst                          |
| `waarom.css`            | Accent-gradient glass-block                                      |
| `service-area.css`      | Pill-chip layout                                                 |
| `faq.css`               | Glass FAQ-cards                                                  |
| `contact-cta.css`       | Gecentreerde CTA-strip                                           |
| `footer.css`            | Transparant met subtle border-top                                |

## Volgorde

Alfabetisch (`_base.css` eerst dankzij de underscore).

## Legacy

`../onepage.css` bestaat nog als monolitisch bestand — wordt niet meer geladen zolang deze map bestaat. Zie [`../../class-preset.php` → `inline_css()`].

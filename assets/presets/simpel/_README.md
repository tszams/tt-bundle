# Preset CSS — Simpel

Flat design: geen radii op cards, geen shadows, geen gradients. Alleen pill-buttons
hebben rounded corners. Alles minimalistisch en clean. Gescoped op `.tt-preset-simpel`.

## Overzicht

| File                    | Wat het style't                                                 |
| ----------------------- | --------------------------------------------------------------- |
| `_base.css`             | Globale reset — verwijdert radii + shadows overal               |
| `_section-base.css`     | Sectie-ruimte + heading-weight                                  |
| `header.css`            | Nav-kleur bg + uppercase menu + pill-CTA                        |
| `hero.css`              | Beide hero varianten (met car photo + met booking form)         |
| `spotlight.css`         | Flat card (beide varianten)                                     |
| `services.css`          | Fallback services grid (flat cards)                             |
| `services-simpel.css`   | Staxi-style dedicated cards (icon of image + full CTA)          |
| `usps.css`              | Card-less USPs — icoon-vierkant naast tekst                     |
| `routes.css`            | Flat rijen met accent-pill prijs                                |
| `service-area.css`      | Flat chips (geen pill)                                          |
| `contact-cta.css`       | Flat accent-band met pill CTAs                                  |
| `footer.css`            | Minimaal — nav-kleur bg                                         |

## Volgorde

Alfabetisch (`_base.css` eerst dankzij de underscore — belangrijk want deze zet
de globale radii/shadow reset die andere files erop bouwen).

## Legacy

`../simpel.css` blijft als monolitisch bestand — wordt niet meer geladen zolang deze map bestaat.

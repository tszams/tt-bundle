# Preset CSS — Premium

Editorial / Apple-esthetiek. Wit + zwart + subtle accent. Fotografie-gedreven.
Rustig, veel witruimte, tijdloos. Alle regels gescoped op `.tt-preset-premium`.

## Overzicht

| File                | Wat het style't                                         |
| ------------------- | ------------------------------------------------------- |
| `_base.css`             | Wit body + bredere container                            |
| `_section-base.css`     | Algemene section basis (wit bg, padding, headers)       |
| `header.css`            | Wit met dark brand + pill-CTA                           |
| `hero.css`              | Cinematic hero met bg-image + gradient overlay          |
| `features.css`          | Alternating image/text rows met veel witruimte          |
| `services.css`      | Transfeero-stijl services (icon absolute rechtsboven)   |
| `spotlight.css`     | Editorial spotlight + promo variant                     |
| `steps.css`         | Cream card met 3 stappen ("Hoe werkt het")              |
| `usps.css`          | Card-less USPs, icoon boven tekst                       |
| `routes.css`        | Subtle route-list, grijze bg                            |
| `waarom.css`        | Wit met rounded image + accent-checkmarks               |
| `service-area.css`  | Witte pill-chips op grijze bg                           |
| `contact-cta.css`   | Dark strip onderaan                                     |
| `footer.css`        | Wit met accent-hover                                    |

## Volgorde

Alfabetisch. Files met `_` prefix (`_base.css`, `_section-base.css`) laden eerst
zodat basis-styling vóór preset-specifieke overrides komt in de cascade.
Belangrijk voor secties die zowel `.tt-section` als `.tt-contact-cta` als klasse hebben:
zonder deze volgorde zou een wit `.tt-section` bg de dark `.tt-contact-cta` bg overschrijven.

## Legacy

`../premium.css` blijft als monolitisch bestand — wordt niet meer geladen zolang deze map bestaat.

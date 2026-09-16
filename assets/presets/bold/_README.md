# Preset CSS — Bold

Elke `.css` file scoped op `.tt-preset-bold` en dekt één sectie af.
Bij het renderen worden ze **server-side geconcateneerd** tot één inline `<style>` blok — dus 0 extra HTTP requests.

## Overzicht

| File                     | Wat het style't                                              |
| ------------------------ | ------------------------------------------------------------ |
| `_base.css`              | Body-achtergrond (dark)                                      |
| `header.css`             | Transparant → solid header + nav caps + accent-underline    |
| `hero-fullscreen.css`    | Fullscreen dark hero + mobile queries                        |
| `routes-cards.css`       | Grote gele route-kaarten met glow                            |
| `sticky-call.css`        | Vaste bel-knop rechtsonder                                   |
| `page-header.css`        | Dramatic dark header voor sub-pages                          |
| `spotlight.css`          | Subtiele dark card variant                                   |
| `services.css`           | Homepage + sub-page services grid                            |
| `services-detail.css`    | Diensten sub-page: alternating rows                          |
| `page-sections.css`      | De 3 vrije info-blokken op sub-pages                         |
| `service-area.css`       | Dark panel met grote area-cards                              |
| `_section-headings.css`  | Globale `h2` overrides — groter/bolder                       |
| `contact-cta.css`        | Grote accent-band onderaan                                   |

## Volgorde en cascade

Files worden alfabetisch ingelezen. `_base.css` begint met een underscore om altijd eerst te komen (basis-waarden vóór overrides).

Behalve die volgorde is er geen cascade-afhankelijkheid tussen files — elke file style't alleen zijn eigen sectie.

## Legacy

`../bold.css` bestaat nog als 1 monolitisch bestand, maar wordt **niet meer geladen** zodra deze map bestaat. `TaxiTheme_Preset::stylesheet_files()` prefereert altijd de map. Kan later worden verwijderd.

## Nieuwe sectie toevoegen

1. Maak `assets/presets/bold/{sectie}.css`
2. Alle selectors prefixen met `.tt-preset-bold`
3. Klaar — wordt automatisch geladen op de volgende page render

Geen registratie nodig, geen enqueue-code aanpassen.

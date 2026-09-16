# Preset CSS — Klassiek

Klassiek gebruikt de basis-styling uit [`style.css`](../../../style.css) voor het meeste.
Deze map bevat alleen scoped overrides waar Klassiek net iets anders wil dan de basis.

## Overzicht

| File               | Wat het style't                                                         |
| ------------------ | ----------------------------------------------------------------------- |
| `over-hero.css`    | Over ons hero + bottom-CTA licht ipv dark, passend bij Klassiek        |
| `page-header.css`  | Sub-page header FAQ-stijl: transparant, gecentreerd, geen eyebrow       |

## Waarom transparant

De basis `.tt-page-header` heeft een witte achtergrond met eyebrow — dat voelt op
Klassiek dubbel wanneer de eerste sectie eronder ook een `.tt-section__header` met
h2 heeft. Deze override geeft de pagina 1 duidelijke hoofdtitel op de body-crème,
en de sectie-h2's fungeren als subtiele sub-headers.

## Volgorde

Alfabetisch. Geen `_`-prefix files (yet).

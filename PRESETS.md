# Preset design specs

Referentie voor het bouwen van nieuwe componenten. Elke preset heeft een eigen "vibe"
— houd je hieraan zodat presets consistent blijven. Voor palettes: gebruik altijd
`var(--tt-accent)` / `var(--tt-accent-rgb)` — hardcode nooit accent-kleuren.

Body-classes voor scoping: `.tt-preset-klassiek`, `.tt-preset-bold`, `.tt-preset-onepage`, `.tt-preset-premium`, `.tt-preset-simpel`.

---

## Klassiek

**Vibe**: overzichtelijk, warm, betrouwbaar. Traditioneel Nederlands taxibedrijf-gevoel.
Voelt als "de site die er altijd al was". Geen fratsen.

**Palette-defaults**
- Body: `--tt-bg` (crème `#fdfcf7`) — warme achtergrond
- Cards: `--tt-surface` (`#ffffff`) — wit
- Tekst: `--tt-fg` (`#14161f`) diep navy, muted `--tt-muted` (`#6c6f7a`)
- Accent: `--tt-accent` op donker (default geel op navy header)

**Header**: sticky dark navy (`--tt-nav`), altijd zichtbaar. Geen transparency/blur.

**Typografie**
- Headings: weight 800, letter-spacing -0.025em
- H1: clamp(2.25rem, 4.5vw, 3.5rem)
- H2: clamp(1.6rem, 3vw, 2.4rem)
- Body: 16px / line-height 1.65

**Ruimtes**
- Section padding: `80px 0`
- Container: 1180px max
- Grid gaps: 20-24px

**Radii**: 12px (`--tt-radius`) voor cards, 8px voor buttons/small elements

**Cards**: wit bg, 1px `--tt-line` border, radius 12, subtle shadow op hover (`--tt-shadow-lg`).
Hover: `translateY(-4px)`.

**Buttons**: solid accent (`--tt-accent`), donkere tekst, subtle accent-glow shadow.

**Icons**: 44x44 accent-vulling met dark icon-color, radius 10-14.

**Wat NIET**
- Glassmorphism / backdrop-blur
- Hero > 5rem
- Volledige section-bands in accent-kleur
- Uppercase menu

---

## Bold

**Vibe**: landing-page, sales-focused, dramatisch. "Boek nu, geen twijfel". Voelt als
een fitness-app pricing-page of Tesla config. Confident, veel accent.

**Palette-defaults**
- Body: `#0b0d14` (hardcoded — matcht hero-start voor naadloze transparent-header)
- Sections: `#14161f` (darker "layer") of full-bleed accent
- Cards: `rgba(255,255,255, 0.03-0.05)` op dark — glass-ish
- Tekst: pure `#fff` (headings) / `rgba(255,255,255, 0.6-0.75)` (body/muted)
- Accent: prominent, veel plekken (icons, hover, contact-cta band)

**Header**: **transparant** op de hero (blend), wordt `rgba(11,13,20, 0.92)` +
backdrop-blur zodra body `.is-scrolled` (via JS, threshold 40px).

**Typografie**
- Hero title: clamp(3rem, 8vw, 6rem) — massive display, weight **900**
- Section H2: clamp(2rem, 4vw, 3rem), weight 900
- Menu: **UPPERCASE**, letter-spacing 0.1em, size 0.78rem, weight 700

**Ruimtes**
- Section padding: `100px 0` (ruimer dan Klassiek)
- Hero: `min-height: calc(100vh - 80px)` — fullscreen feel
- CTA padding: 18px 32px (fat)

**Radii**: 10-20px voor cards, **999px voor pills** (CTAs, sticky-call)

**Cards**: donkere glass (`rgba(255,255,255,0.03)`), `1px rgba(255,255,255,0.08)` border,
grote radius 20. Hover: `translateY(-6px)` + accent-shadow.

**Buttons**: pill-shape (`border-radius: 999px`) voor prominente CTAs. Sterke
accent-glow shadow: `0 10px 30px -8px rgba(var(--tt-accent-rgb), 0.6)`.

**Icons**: 52x52 met accent-glow shadow (`rgba(var(--tt-accent-rgb), 0.45)`), radius 14.

**Signature elements**
- Sticky call-knop rechtsonder (pill met accent-glow)
- Contact-CTA als full-bleed accent-band met dark button inside
- Header-CTA pill met glow

**Wat NIET**
- Lichte backgrounds (breekt de dramatiek)
- Dunne borders zonder blur/glass-context
- Kleine typografie voor headings
- Small buttons

---

## One-page

**Vibe**: app-style, one-pager, tech / modern. Voelt als een Linear- of Stripe-landing.
Minimaal, veel witruimte-op-dark, subtiel accent. Alles centraal.

**Palette-defaults**
- Body: `#0a0b10` (near-black)
- Fixed background: gradient + accent radial glow (`.tt-onepage-bg`)
- Cards: `rgba(255,255,255, 0.04)` glass met `backdrop-filter: blur(6px)`
- Tekst: `#fff` / `rgba(255,255,255, 0.6-0.9)`
- Accent: **subtiel** — pulse-dot, tag-checks, price-text, icon-fills. Nooit
  full-bleed accent-bands.

**Header**: volledig transparent, geen border, `.` na brand-naam in accent-kleur.

**Typografie**
- Hero title: clamp(2.4rem, 6vw, 3.75rem) — kleiner dan Bold, meer editorial
- Section H2: clamp(1.6rem, 3.5vw, 2.25rem), weight 700
- Eyebrow: uppercase, letter-spacing 0.15em, 0.75rem, met pulserende accent-dot
- Card headings: 1rem, weight 600 (subtiel, niet dramatisch)

**Ruimtes**
- Container: **900px max** (smaller dan andere presets — editorial feel)
- Section padding: `60px 0` (tighter dan Bold)
- Hero: `40px 0 80px` (geen fullscreen)
- Grid gaps: 10-12px (dicht op elkaar)

**Radii**: 16-24px voor cards (soft), 999px voor pills/chips

**Cards**: glass (`rgba(255,255,255,0.04)`), `1px rgba(255,255,255,0.06)` border,
`backdrop-filter: blur(6px)`, radius 20. Hover: `translateY(-3px)`, **geen shadow**.

**Buttons**: pill-shape (999px), klein (padding 10-14px). Hover: `filter: brightness(0.88)`
in plaats van shadow of transform.

**Icons**: 42x42 accent-vulling, radius 12.

**Signature elements**
- Fixed background met gradient + accent-glow (dus content scrollt "over" een stabiel canvas)
- Scroll-progress bar top (2px, accent-kleur)
- Booking-form in glass-card direct onder hero met anchor `#boek`
- Chips voor service-area (pills met accent-icon)

**Wat NIET**
- Shadows (`box-shadow`) — One-page gebruikt border+backdrop-blur voor depth
- Zware/vette typografie
- Full-bleed accent-bands
- Container-breedte > 900px

---

## Premium

**Vibe**: editorial / Apple-esthetiek. High-end chauffeur-service. Rustig, veel witruimte,
foto-gedreven. Voelt als Airbnb, Stripe of Transfeero. "Quiet luxury" — geen loud accent.

**Palette-defaults**
- Body: `#ffffff` puur wit
- Text: `#0f172a` deep navy, muted `#64748b` (slate)
- Section-bg alt: `#f8fafc` (heel licht grijs)
- Accent: subtiel, sparingly gebruikt. Default palette = amber `#f59e0b`, ook mono + emerald opties.

**Header**: wit met dunne bottom-border (`#f1f5f9`), dark CTA (niet accent — accent is te subtiel voor primary button).

**Typografie**
- Hero title: clamp(2.5rem, 5.5vw, 4.5rem) — groot maar niet 900-weight. Gebruik **weight 700**, letter-spacing -0.03em
- Section H2: clamp(1.8rem, 3vw, 2.5rem), weight 700
- Feature titles: clamp(2rem, 3.5vw, 3rem), weight 700
- Body: 1rem-1.05rem, line-height 1.7, weight 400. Muted text in slate (`#64748b`)
- Nav: 0.92rem, weight 500, geen uppercase

**Ruimtes**
- Container: **1200px max** (breed voor foto-content)
- Section padding: **100px** (heel royaal)
- Feature-row padding: 60px vertical, 80px horizontal gap
- Hero: min-height 620px

**Radii**: 16-24px voor cards/images, 999px voor pills

**Cards**: wit bg, `1px #f1f5f9` border, radius 20. Hover: alleen border-color darken + soft shadow. **Geen** transform of glow.

**Buttons**: pill-shape (999px). Primary = **dark** (`#0f172a`) niet accent — accent-kleur is te subtle voor CTA. Ghost = light with dark text.

**Icons**: 44-48px, background = `#f1f5f9` (heel licht grijs) met dark icon-color. **Niet** accent-fill (te loud voor premium).

**Signature elements**
- **Hero met echte foto** — background-image + dark overlay + centered text. Zonder foto: subtle gradient fallback.
- **Features section**: alternating image/text rows (`is-flipped` op oneven rows). Grote afbeeldingen (aspect 4:3, radius 24px) naast typografie.
- **Contact-CTA als dark strip** (`#0f172a`) — één donkere sectie voor contrast in een verder witte pagina.
- **Alt-section-bg**: `#f8fafc` voor services + routes + service-area (subtle grijs) → zorgt voor rhythm van wit → grijs → wit.

**Wat NIET**
- Bold/aggressive accent-shadows of glows
- Uppercase menu
- Weight 900 typografie
- Full-bleed accent-bands (behalve dark contact-CTA)
- Glassmorphism / backdrop-blur
- Solid accent-color CTAs (accent is voor kleine details, niet buttons)
- Trust-bar of sticky-call (te "sales-y")

**Content-vereiste**
- Voor optimaal resultaat: hero-image + 2-3 feature-row images uploaden. Zonder foto's valt hero terug op gradient en verdwijnt features-section.

---

## Componenten × presets — welke variant waar

Overzicht van alle `templates/parts/*.php` en welke preset ze gebruiken. Een variant
is een aparte part-file voor visueel/structureel andere rendering; simpele CSS-verschillen
tussen presets vallen daar niet onder.

| Component     | Variants (part-files)                                   | Klassiek         | Bold                | One-page          | Premium            |
|---------------|---------------------------------------------------------|------------------|---------------------|-------------------|--------------------|
| Hero          | `hero.php`, `hero-fullscreen.php`, `hero-onepage.php`, `hero-premium.php`, `hero-simpel-image.php`, `hero-simpel-form.php` | `hero` (style-setting) | `hero-fullscreen` | `hero-onepage` | `hero-premium` |
| Trust bar     | `trust.php`                                             | ✓                | —                   | —                 | —                  |
| USPs          | `usps.php`                                              | ✓                | ✓                   | ✓                 | ✓                  |
| Services      | `services.php`                                          | ✓                | ✓                   | ✓                 | ✓ (icon rechtsboven + "Details" pill) |
| About         | `about.php`                                             | ✓                | ✓                   | ✓                 | ✓                  |
| FAQ           | `faq.php` (+ `templates/faq.php` voor volledige pagina) | ✓                | ✓                   | ✓                 | ✓                  |
| Features      | `features.php`                                          | —                | —                   | —                 | ✓ (signature — alternating image/text) |
| Steps         | `steps.php`                                             | —                | —                   | —                 | ✓ (cream card "Hoe werkt het") |
| Waarom        | `waarom.php`                                            | ✓                | ✓                   | ✓                 | ✓                  |
| Routes        | `routes.php`, `routes-cards.php`                        | `routes`         | `routes-cards`      | `routes`          | `routes`           |
| Service area  | `service-area.php`                                      | ✓                | ✓                   | ✓                 | ✓                  |
| Post content  | `post-content.php`                                      | ✓                | ✓                   | ✓                 | ✓                  |
| Contact CTA   | `contact-cta.php`                                       | ✓                | ✓ (accent-band)     | ✓                 | ✓ (dark strip)     |
| Sticky call   | `sticky-call.php`                                       | —                | ✓                   | —                 | —                  |
| Fixed bg + progress-bar | inline in `home-onepage.php`                  | —                | —                   | ✓                 | —                  |

**Hero-varianten uitgesplitst** (`hero.php` in Klassiek heeft z'n eigen sub-stijlen):
- `split` — tekst links + booking-form rechts (default)
- `stacked` — tekst gecentreerd boven, booking-form breed onder
- `centered` — alleen tekst + CTAs, geen inline form

**Preset-eigen content-velden** (verborgen in editor buiten de betreffende preset):
- `hero_style`, `hero_accent_enabled`, `hero_bullets` — alleen Klassiek
- `hero_tags` — Klassiek + One-page
- `hero_image_id` — alleen Premium
- `features_items` — alleen Premium (rendert niks in andere presets)
- `steps_items` — alleen Premium (idem)

Alle andere content-velden (bedrijfsgegevens, USPs, waarom, routes, service-area, contact,
services) zijn universeel — één keer invullen, elke preset toont het (of laat het weg als leeg).

---

## Simpel

**Vibe**: flat design, minimalistisch, form-first. Voelt als Staxi of Taxi Service 365 —
kleine taxi-bedrijven die "hier is de knop, klik hem" willen zonder marketing-fluff.

**Palette-defaults**
- Body: uit palette (dark: `#0a0a0a`, licht: `#ffffff`)
- Nav: dark of solid accent bar
- Accent: helderblauw (cyaan of azuur) — vaste taxi-standaard
- Text: hoog contrast, geen muted-tints

**Header**: minimaal — logo + kort nav + pill-CTA. Geen sticky, geen glass.

**Typografie**
- Hero title: clamp(2.2rem, 4.5vw, 3.6rem) — **weight 700**, geen 800/900
- Section H2: weight 700, letter-spacing -0.02em
- Menu: uppercase, 0.04em letter-spacing, weight 600 (subtle)
- Body: 1rem / line-height 1.6

**Ruimtes**
- Section padding: 72px
- Hero padding: 80/100px

**Radii**: **0px overal** (`border-radius: 0` op cards, images, etc). Enige uitzondering:
pill-buttons (`999px`) voor CTAs — dat is de signature "1 rounded element".

**Shadows**: **none**. Zelfs op hover geen box-shadow. Alleen border-color change of `filter: brightness()`.

**Cards**: 1px `--tt-line` border, geen radius, geen shadow. Hover: `border-color: var(--tt-accent)`.

**Buttons**: pill-shape (999px). Primary = accent bg. Ghost = transparent met border. Geen glow.

**Icons**: 42x42, accent-fill, **geen radius** (vierkant blok).

**Signature elements**
- **Twee hero-varianten** (via `hero_simpel_style` setting):
  - `image` — tekst + CTAs links, auto-foto rechts (Taxi Service 365 stijl)
  - `form`  — tekst links, boekingsformulier direct rechts (Staxi stijl)
- **Accent-band contact-CTA** (flat, geen gradient)
- **Uppercase nav** (subtiel corporate)

**Wat NIET**
- Border-radius > 0 op cards, images, sections (alleen op pill-buttons)
- Shadows, glows, gradients (behalve zeer subtiel bij hero-bg)
- 900-weight display type
- Sticky elements
- Glassmorphism / backdrop-blur
- Features/steps sections (te "marketing" voor Simpel)

**Preset-eigen content-velden** (verborgen bij andere presets):
- `hero_simpel_style` — 'image' of 'form'
- `hero_simpel_image_id` — auto-foto voor image-variant

---

## Nieuwe component toevoegen — checklist

1. Bouw eerst de **base**-styling in `style.css` — dat is de Klassiek-look.
2. Voeg preset-scoped overrides toe in `assets/presets/bold.css` en/of `onepage.css`
   waar die "vibe" écht anders is.
3. Gebruik altijd `var(--tt-accent)` en `var(--tt-accent-rgb)` voor accent — nooit
   hardcode `#f5b800` of `rgba(245,184,0,...)`.
4. Voor donkere text-op-accent gebruik `var(--tt-accent-fg)` (palettes kunnen dit
   overriden — bij rood wordt het `#fff`, bij geel `#14161f`).
5. Alle preset-overrides moeten gescoped zijn op `.tt-preset-{slug}` — anders
   lekken ze naar andere presets.
6. Als een nieuw component ook in andere presets moet werken: rendering staat in
   `templates/parts/`, elke preset-template roept 'm aan via `get_template_part()`.

## Nieuw palette toevoegen — checklist

1. Entry in `PALETTES` array in [inc/class-theme-variant.php](inc/class-theme-variant.php)
   met correct `preset` field.
2. CSS-file in `assets/palettes/{slug}.css` met alleen variabele-overrides:
   ```css
   .tt-palette-{slug} {
       --tt-accent:     #xxx;
       --tt-accent-rgb: r, g, b;
       --tt-accent-fg:  #fff of #14161f (afhankelijk van accent-helderheid);
   }
   ```
3. Klaar. Geen andere files nodig — preset-CSS leest deze variabelen automatisch.

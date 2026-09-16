<?php
/**
 * Home — preset "Klassiek".
 * Hero (vast bovenaan) + reorderable secties (via Home Renderer) + contact-cta (vast onder).
 * Sectie-volgorde beheer je via home-editor met up/down knoppen.
 */
get_header();

$hero_style = TaxiTheme_Home_Content::all()['hero_style'] ?? 'split';
if (!in_array($hero_style, ['split', 'stacked', 'centered'], true)) {
    $hero_style = 'split';
}
get_template_part('templates/klassiek/hero/' . $hero_style);

TaxiTheme_Home_Renderer::render_sections();

get_template_part('templates/shared/contact-cta');
get_footer();

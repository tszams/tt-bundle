<?php
/**
 * Home — preset "Simpel".
 * Flat design. Hero + reorderable secties + contact-cta.
 * Simpel gebruikt eigen Staxi-services variant (via renderer overrides).
 */
get_header();

$content    = TaxiTheme_Home_Content::all();
$hero_style = ($content['hero_simpel_style'] ?? 'image') === 'form' ? 'form' : 'image';

get_template_part('templates/simpel/hero/' . $hero_style);

TaxiTheme_Home_Renderer::render_sections();

get_template_part('templates/shared/contact-cta');
get_footer();

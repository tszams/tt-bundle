<?php
/**
 * Home — preset "Premium".
 * Editorial layout. Hero + reorderable secties + contact-cta.
 */
get_header();

get_template_part('templates/premium/hero');

TaxiTheme_Home_Renderer::render_sections();

get_template_part('templates/shared/contact-cta');
get_footer();

<?php
/**
 * Home — preset "One-page".
 * Dark app-style. Hero + reorderable secties + contact-cta.
 * bg + glow + scroll-progress worden globaal ingespoten via header.php.
 */
get_header();

get_template_part('templates/onepage/hero');

TaxiTheme_Home_Renderer::render_sections();

get_template_part('templates/shared/contact-cta');
get_footer();

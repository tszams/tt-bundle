<?php
/**
 * Home — preset "Bold".
 * Hero + Bold-specifieke routes-cards (vast) + reorderable secties + contact-cta + sticky-call.
 */
get_header();

get_template_part('templates/bold/hero');
get_template_part('templates/bold/routes-cards');

TaxiTheme_Home_Renderer::render_sections();

get_template_part('templates/shared/contact-cta');
get_template_part('templates/bold/sticky-call');
?>

<script>
(function () {
    var threshold = 40;
    function update() {
        document.body.classList.toggle('is-scrolled', window.scrollY > threshold);
    }
    window.addEventListener('scroll', update, { passive: true });
    update();
})();
</script>

<?php get_footer(); ?>

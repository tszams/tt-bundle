<?php
/**
 * Privacybeleid-pagina — universeel template.
 * Simpele page-header (titel via .tt-page-header) + WordPress post_content
 * (klant bewerkt dit via de standaard WP editor).
 * Wordt geladen via template_include filter voor pages met _taxitheme_role = 'privacy'.
 */
get_header();
?>
<section class="tt-page-header">
    <div class="tt-container">
        <?php get_template_part('templates/shared/breadcrumbs'); ?>
        <h1 class="tt-page-header__title"><?php the_title(); ?></h1>
    </div>
</section>

<section class="tt-section">
    <div class="tt-container">
        <article class="tt-legal-content">
            <?php while (have_posts()) : the_post(); the_content(); endwhile; ?>
        </article>
    </div>
</section>

<?php get_footer(); ?>

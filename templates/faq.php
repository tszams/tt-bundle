<?php
/**
 * FAQ-pagina — toont alle FAQ-items in een accordion.
 * Wordt geladen via template_include filter voor pages met _taxitheme_role = 'faq'.
 * Gebruikt dezelfde .tt-page-header structuur als andere sub-pages zodat alle
 * preset-scoped headers (Bold dramatic, Premium cinematic, etc) automatisch werken.
 */
get_header();

$city    = TaxiTheme_Company_Info::get('city');
$page_id = get_queried_object_id();
$intro   = TaxiTheme_Page_Meta::get_intro($page_id);
?>
<section class="tt-page-header">
    <div class="tt-container">
        <?php get_template_part('templates/shared/breadcrumbs'); ?>
        <span class="tt-page-header__eyebrow">FAQ<?php echo $city ? ' · ' . esc_html($city) : ''; ?></span>
        <h1 class="tt-page-header__title"><?php the_title(); ?></h1>
        <?php if ($intro !== '') : ?>
            <div class="tt-page-header__intro">
                <?php foreach (preg_split('/\r?\n\r?\n/', trim($intro)) as $p) :
                    $p = trim($p);
                    if ($p !== '') : ?>
                        <p><?php echo nl2br(esc_html($p)); ?></p>
                    <?php endif;
                endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
// Toon alle items, geen header (die staat hierboven).
get_template_part('templates/shared/faq', null, ['all' => true, 'show_header' => false]);

// Optioneel: klant's eigen extra content vanuit de page-editor
get_template_part('templates/shared/post-content');

// Contact-CTA onderaan — helpt bij conversie na FAQ
get_template_part('templates/shared/contact-cta');
?>

<?php get_footer(); ?>

<?php
/**
 * Diensten — preset "Premium".
 * Editorial layout: cinematic dark header, spacious services-detail rows,
 * "Hoe werkt het" steps sectie erna. Fotografie-gedreven.
 */
get_header();

$page_id = get_queried_object_id();
$intro   = TaxiTheme_Page_Meta::get_intro($page_id);
$city    = TaxiTheme_Company_Info::get('city');
?>

<section class="tt-page-header tt-premium-page-header">
    <div class="tt-container">
        <span class="tt-page-header__eyebrow">Diensten<?php echo $city ? ' · ' . esc_html($city) : ''; ?></span>
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
// Detailed services rows — met veel witruimte via Premium CSS overrides
get_template_part('templates/shared/services-detail');

$sections = TaxiTheme_Page_Meta::get_visible_sections($page_id);
if (!empty($sections)) : ?>
    <section class="tt-section tt-page-sections-wrap">
        <div class="tt-container">
            <?php TaxiTheme_Page_Meta::render_sections($page_id); ?>
        </div>
    </section>
<?php endif; ?>

<?php
// Premium-signature: "Hoe werkt het" steps sectie past bij editorial toon
get_template_part('templates/shared/steps');
get_template_part('templates/shared/contact-cta');

get_footer();

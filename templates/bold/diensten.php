<?php
/**
 * Diensten-pagina — Bold preset.
 * Zelfde structuur als universele diensten.php, met sticky-call onderaan (Bold signature).
 * Styling via preset-scoped overrides in bold.css.
 */
get_header();

$content = TaxiTheme_Home_Content::all();
$city    = TaxiTheme_Company_Info::get('city');
$page_id = get_queried_object_id();
$intro   = TaxiTheme_Page_Meta::get_intro($page_id);
?>

<section class="tt-page-header">
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
get_template_part('templates/shared/waarom');
get_template_part('templates/shared/contact-cta');
get_template_part('templates/bold/sticky-call');
?>

<?php get_footer(); ?>

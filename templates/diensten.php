<?php
/**
 * Diensten-pagina — universeel template dat werkt in alle presets.
 * Structuur: header + detailed services rows + page-sections + waarom + contact-CTA.
 * Services data komt uit de homepage-editor (shared). Intro + extra sections zijn per-page.
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
// Detailed services — alternerende image/text rows met uitgebreide info
get_template_part('templates/shared/services-detail');

// Extra info-blokken (per-page — bijv. hoe boeken, wachttijden, extra services)
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
?>

<?php get_footer(); ?>

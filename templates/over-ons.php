<?php
/**
 * Over ons — titel + optionele intro + optionele USP-kaartjes overlap +
 * shared contact-cta banner (met per-page override via page-meta).
 * Werkt hetzelfde als alle andere sub-pages qua CTA.
 */
get_header();

$page_id  = get_queried_object_id();
$intro    = TaxiTheme_Page_Meta::get_intro($page_id);
$extras   = TaxiTheme_Page_Meta::get_over_ons_extras($page_id);
$usps     = TaxiTheme_Page_Meta::get_visible_over_ons_usps($page_id);
$sections = TaxiTheme_Page_Meta::get_visible_sections($page_id);

// Backward-compat: als usps_enabled nooit opgeslagen is (leeg), toon USPs.
// Alleen expliciet "0" verbergt ze.
$show_usps = ($extras['usps_enabled'] ?? '') !== '0' && !empty($usps);
?>

<!-- Page-header (preset-styled) — alleen titel + optionele intro. -->
<section class="tt-page-header <?php echo $show_usps ? 'has-usps' : ''; ?>">
    <div class="tt-container">
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

    <?php if ($show_usps) : ?>
        <div class="tt-container">
            <div class="tt-over-usps">
                <?php foreach ($usps as $u) : ?>
                    <div class="tt-over-usp">
                        <?php if (!empty($u['icon'])) : ?>
                            <span class="tt-over-usp__icon">
                                <?php echo TaxiTheme_Icons::svg($u['icon'], 22); ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($u['title'])) : ?>
                            <h3 class="tt-over-usp__title"><?php echo esc_html($u['title']); ?></h3>
                        <?php endif; ?>
                        <?php if (!empty($u['text'])) : ?>
                            <p class="tt-over-usp__text"><?php echo nl2br(esc_html($u['text'])); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php if ($show_usps) : ?>
    <div class="tt-page-header__usps-spacer" aria-hidden="true"></div>
<?php endif; ?>

<?php if (!empty($sections)) : ?>
    <section class="tt-section tt-page-sections-wrap">
        <div class="tt-container">
            <?php TaxiTheme_Page_Meta::render_sections($page_id); ?>
        </div>
    </section>
<?php endif; ?>

<?php
// Shared CTA-banner met per-page override (title/subtitle/buttons)
get_template_part('templates/shared/contact-cta');
?>

<?php get_footer(); ?>

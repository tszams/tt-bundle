<?php
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
$phone   = TaxiTheme_Company_Info::get('phone');
$email   = TaxiTheme_Company_Info::get('email');
if (empty($content['contact_enabled'])) return;

$phone_clean = $phone ? preg_replace('/[^0-9+]/', '', $phone) : '';

// Defaults uit home + bedrijfsgegevens
$cta_title    = $content['contact_title']    ?? '';
$cta_subtitle = $content['contact_subtitle'] ?? '';
$btn1_label   = $phone ? 'Bel ons'  : '';
$btn1_url     = $phone ? 'tel:' . $phone_clean : '';
$btn2_label   = $email ? 'Mail ons' : '';
$btn2_url     = $email ? 'mailto:' . $email    : '';

// Per-page override: sub-page kan eigen title/subtitle/buttons instellen
if (is_page() && class_exists('TaxiTheme_Page_Meta')) {
    $override = TaxiTheme_Page_Meta::get_cta_override(get_queried_object_id());
    if ($override['title']    !== '') $cta_title    = $override['title'];
    if ($override['subtitle'] !== '') $cta_subtitle = $override['subtitle'];
    if ($override['cta1_label'] !== '') { $btn1_label = $override['cta1_label']; $btn1_url = $override['cta1_url']; }
    if ($override['cta2_label'] !== '') { $btn2_label = $override['cta2_label']; $btn2_url = $override['cta2_url']; }
}

// Als er noch title/subtitle noch buttons zijn, niks tonen
if ($cta_title === '' && $cta_subtitle === '' && $btn1_label === '' && $btn2_label === '') return;
?>
<section class="tt-section tt-contact-cta">
    <div class="tt-container tt-contact-cta__inner">
        <div>
            <?php if ($cta_title !== '') : ?>
                <h2><?php echo esc_html($cta_title); ?></h2>
            <?php endif; ?>
            <?php if ($cta_subtitle !== '') : ?>
                <p><?php echo esc_html($cta_subtitle); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($btn1_label !== '' || $btn2_label !== '') : ?>
            <div class="tt-contact-cta__actions">
                <?php if ($btn1_label !== '') : ?>
                    <a href="<?php echo esc_url($btn1_url); ?>" class="tt-cta"<?php if (strpos($btn1_url, 'http') === 0) echo ' target="_blank" rel="noopener"'; ?>><?php echo esc_html($btn1_label); ?></a>
                <?php endif; ?>
                <?php if ($btn2_label !== '') : ?>
                    <a href="<?php echo esc_url($btn2_url); ?>" class="tt-cta tt-cta--ghost"<?php if (strpos($btn2_url, 'http') === 0) echo ' target="_blank" rel="noopener"'; ?>><?php echo esc_html($btn2_label); ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

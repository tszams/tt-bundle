<?php
/**
 * Contact — universeel template dat werkt in alle presets.
 * Content: page-header (titel + intro uit Page_Meta) + contact-info kaart
 * (uit Bedrijfsgegevens) + sections (max 3 vrije info-blokken uit Page_Meta).
 * Geen homepage-data — alles is page-specifiek of universele bedrijfsinfo.
 */
get_header();

$page_id     = get_queried_object_id();
$intro       = TaxiTheme_Page_Meta::get_intro($page_id);
$phone       = TaxiTheme_Company_Info::get('phone');
$email       = TaxiTheme_Company_Info::get('email');
$address     = TaxiTheme_Company_Info::get('address');
$postcode    = TaxiTheme_Company_Info::get('postcode');
$city        = TaxiTheme_Company_Info::get('city');
$hours_lines = TaxiTheme_Company_Info::formatted_hours();
$phone_clean = $phone ? preg_replace('/[^0-9+]/', '', $phone) : '';

$has_address = $address || $city || $postcode;
$has_contact = $phone || $email || $has_address || !empty($hours_lines);

$maps_url = '';
if ($has_address) {
    $q = trim(trim($address) . ' ' . trim(($postcode ? $postcode . ' ' : '') . $city));
    if ($q !== '') {
        $maps_url = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($q);
    }
}
?>

<section class="tt-page-header">
    <div class="tt-container">
        <span class="tt-page-header__eyebrow">Contact<?php echo $city ? ' · ' . esc_html($city) : ''; ?></span>
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

<?php if ($has_contact) : ?>
    <section class="tt-section tt-contact-info-wrap">
        <div class="tt-container">
            <div class="tt-contact-info">
                <?php if ($phone) : ?>
                    <a href="tel:<?php echo esc_attr($phone_clean); ?>" class="tt-contact-info__card">
                        <span class="tt-contact-info__icon"><?php echo TaxiTheme_Icons::svg('phone', 22); ?></span>
                        <span class="tt-contact-info__label">Telefoon</span>
                        <span class="tt-contact-info__value"><?php echo esc_html($phone); ?></span>
                    </a>
                <?php endif; ?>

                <?php if ($email) : ?>
                    <a href="mailto:<?php echo esc_attr($email); ?>" class="tt-contact-info__card">
                        <span class="tt-contact-info__icon"><?php echo TaxiTheme_Icons::svg('mail', 22); ?></span>
                        <span class="tt-contact-info__label">E-mail</span>
                        <span class="tt-contact-info__value"><?php echo esc_html($email); ?></span>
                    </a>
                <?php endif; ?>

                <?php if ($has_address) : ?>
                    <?php $addr_tag = $maps_url ? 'a' : 'div'; ?>
                    <<?php echo $addr_tag; ?>
                        <?php if ($maps_url) : ?>href="<?php echo esc_url($maps_url); ?>" target="_blank" rel="noopener"<?php endif; ?>
                        class="tt-contact-info__card">
                        <span class="tt-contact-info__icon"><?php echo TaxiTheme_Icons::svg('map-pin', 22); ?></span>
                        <span class="tt-contact-info__label">Adres</span>
                        <span class="tt-contact-info__value">
                            <?php if ($address) : ?>
                                <?php echo esc_html($address); ?><br>
                            <?php endif; ?>
                            <?php echo esc_html(trim($postcode . ' ' . $city)); ?>
                        </span>
                    </<?php echo $addr_tag; ?>>
                <?php endif; ?>

                <?php if (!empty($hours_lines)) : ?>
                    <div class="tt-contact-info__card">
                        <span class="tt-contact-info__icon"><?php echo TaxiTheme_Icons::svg('clock', 22); ?></span>
                        <span class="tt-contact-info__label">Openingstijden</span>
                        <span class="tt-contact-info__value">
                            <?php foreach ($hours_lines as $i => $tt_line) : ?>
                                <?php if ($i > 0) echo '<br>'; ?>
                                <?php echo esc_html($tt_line); ?>
                            <?php endforeach; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php
$sections = TaxiTheme_Page_Meta::get_visible_sections($page_id);
if (!empty($sections)) : ?>
    <section class="tt-section tt-page-sections-wrap">
        <div class="tt-container">
            <?php TaxiTheme_Page_Meta::render_sections($page_id); ?>
        </div>
    </section>
<?php endif; ?>

<?php get_footer(); ?>

<?php
$tt_boeken_id      = TaxiTheme_Installer::get_page_id('boeken');
$tt_boeken_active  = $tt_boeken_id && TaxiTheme_Booking::webapp_page_enabled();
$tt_boeken_url     = $tt_boeken_id ? get_permalink($tt_boeken_id) : home_url('/');
$tt_phone          = TaxiTheme_Company_Info::get('phone');
$tt_phone_clean    = $tt_phone ? preg_replace('/[^0-9+]/', '', $tt_phone) : '';
$tt_name           = TaxiTheme_Company_Info::get('name', get_bloginfo('name'));
$tt_logo_url       = TaxiTheme_Company_Info::logo_url();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php if (class_exists('TaxiTheme_Preset') && TaxiTheme_Preset::current() === 'onepage') : ?>
    <div class="tt-onepage-bg" aria-hidden="true">
        <div class="tt-onepage-bg__gradient"></div>
        <div class="tt-onepage-bg__glow"></div>
    </div>
    <div class="tt-onepage-progress" aria-hidden="true"><div class="tt-onepage-progress__bar"></div></div>
<?php endif; ?>
<header class="tt-header">
    <div class="tt-container tt-header__inner">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="tt-brand<?php echo $tt_logo_url ? ' tt-brand--logo' : ''; ?>">
            <?php if ($tt_logo_url) : ?>
                <img src="<?php echo esc_url($tt_logo_url); ?>" alt="<?php echo esc_attr($tt_name); ?>" class="tt-brand__logo">
            <?php else : ?>
                <?php echo esc_html($tt_name); ?>
            <?php endif; ?>
        </a>
        <nav class="tt-nav">
            <?php
            if (has_nav_menu('primary')) {
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'container'      => false,
                    'depth'          => 1,
                ]);
            }
            ?>
        </nav>
        <?php if ($tt_boeken_active) : ?>
            <a href="<?php echo esc_url($tt_boeken_url); ?>" class="tt-cta tt-header__cta">Boek nu</a>
        <?php elseif ($tt_phone) : ?>
            <a href="tel:<?php echo esc_attr($tt_phone_clean); ?>" class="tt-cta tt-header__cta">Bel <?php echo esc_html($tt_phone); ?></a>
        <?php endif; ?>

        <button type="button" class="tt-hamburger" aria-label="Menu openen" aria-controls="tt-mobile-menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<div id="tt-mobile-menu" class="tt-mobile-menu" aria-hidden="true">
    <div class="tt-mobile-menu__inner">
        <button type="button" class="tt-mobile-menu__close" aria-label="Menu sluiten">×</button>

        <nav class="tt-mobile-menu__nav">
            <?php
            if (has_nav_menu('primary')) {
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'container'      => false,
                    'depth'          => 1,
                ]);
            }
            ?>
        </nav>

        <div class="tt-mobile-menu__actions">
            <?php if ($tt_boeken_active) : ?>
                <a href="<?php echo esc_url($tt_boeken_url); ?>" class="tt-cta tt-mobile-menu__cta">Boek nu</a>
            <?php endif; ?>
            <?php if ($tt_phone) : ?>
                <a href="tel:<?php echo esc_attr($tt_phone_clean); ?>" class="tt-mobile-menu__phone">
                    Bel <?php echo esc_html($tt_phone); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    var btn     = document.querySelector('.tt-hamburger');
    var closer  = document.querySelector('.tt-mobile-menu__close');
    var overlay = document.getElementById('tt-mobile-menu');
    if (!btn || !overlay) return;

    function open() {
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        btn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }
    function close() {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        btn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    btn.addEventListener('click', open);
    if (closer) closer.addEventListener('click', close);
    // Klik op link binnen menu = sluiten
    overlay.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', close); });
    // Klik buiten menu inner = sluiten
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) close();
    });
    // Escape sluit menu
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('is-open')) close();
    });
})();
</script>

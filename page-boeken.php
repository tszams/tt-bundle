<?php
/**
 * Template Name: Booking Page (Webapp)
 *
 * Als de webapp-page toggle in Stijl-tab AAN staat → bare template, webapp
 * neemt volledig over (eigen navbar/UX, fullpage no-scroll).
 * Als UIT → normale page-rendering (met theme header/footer, alleen post_content).
 */

if (!TaxiTheme_Booking::webapp_page_enabled()) {
    get_header(); ?>
    <main class="tt-main">
        <div class="tt-container">
            <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                <h1><?php the_title(); ?></h1>
                <div><?php the_content(); ?></div>
            <?php endwhile; endif; ?>
        </div>
    </main>
    <?php get_footer();
    return;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title><?php wp_title('', true); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url(TaxiTheme_Booking::webapp_css_url()); ?>">
    <?php wp_head(); ?>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; -webkit-tap-highlight-color: transparent; }
    </style>
</head>
<body <?php body_class('tt-webapp-page'); ?>>
    <div id="taxiwebapp-root"></div>
    <script type="module" src="<?php echo esc_url(TaxiTheme_Booking::webapp_url()); ?>"></script>
    <?php wp_footer(); ?>
</body>
</html>

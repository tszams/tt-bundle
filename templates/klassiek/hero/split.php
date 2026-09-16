<?php
/**
 * Klassiek hero — variant "Split".
 * Layout: tekst links, booking-form rechts. Optioneel gele accent-shapes achter het form.
 */
if (!defined('ABSPATH')) exit;

$ctx = require __DIR__ . '/_context.php';
$accent = !empty($ctx['content']['hero_accent_enabled']);
?>
<section class="tt-hero tt-hero--split">
    <div class="tt-container tt-hero__inner">
        <div class="tt-hero__text">
            <?php $ctx['render_text_block'](); $ctx['render_phone_cta'](); ?>
        </div>
        <div class="tt-hero__form-wrap<?php echo $accent ? ' tt-hero__form-wrap--accent' : ''; ?>">
            <?php if ($accent) : ?>
                <div class="tt-hero__image-shape tt-hero__image-shape--1"></div>
                <div class="tt-hero__image-shape tt-hero__image-shape--2"></div>
            <?php endif; ?>
            <p class="tt-hero__form-heading">Boek uw taxi hieronder</p>
            <div class="tt-hero__form"><?php echo $ctx['booking_form_html']; ?></div>
        </div>
    </div>
</section>

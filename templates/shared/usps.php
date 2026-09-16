<?php
if (!defined('ABSPATH')) exit;

$content = TaxiTheme_Home_Content::all();
if (empty($content['usps_enabled']) || empty($content['usps'])) return;
?>
<section class="tt-section tt-usps">
    <div class="tt-container">
        <div class="tt-usps__grid">
            <?php foreach ($content['usps'] as $usp) :
                if (empty($usp['title']) && empty($usp['text'])) continue;
            ?>
                <div class="tt-usp">
                    <?php if (!empty($usp['icon'])) : ?>
                        <div class="tt-usp__icon"><?php echo TaxiTheme_Icons::svg($usp['icon'], 28); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($usp['title'])) : ?>
                        <h3><?php echo esc_html($usp['title']); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($usp['text'])) : ?>
                        <p><?php echo esc_html($usp['text']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

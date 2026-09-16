<?php
if (!defined('ABSPATH')) {
    exit;
}

class TaxiTheme_Admin_Notice {

    public static function init() {
        add_action('admin_notices', [__CLASS__, 'render']);
    }

    public static function render() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Paused state: prominent notice zodat admin niet vergeet dat TaxiTheme uit staat.
        if (TaxiTheme_Installer::is_paused()) {
            $settings_url = esc_url(admin_url('admin.php?page=' . TaxiTheme_Setup::WIZARD_SLUG . '&tab=settings'));
            ?>
            <div class="notice notice-warning" style="border-left-color:#f59e0b;padding:14px 18px;">
                <p style="margin:0;font-size:14px;">
                    <strong>TaxiTheme is uitgeschakeld.</strong>
                    De oude homepage is actief.
                    <a href="<?php echo $settings_url; ?>" style="margin-left:8px;">Weer aanzetten →</a>
                </p>
            </div>
            <?php
            return;
        }

        if (TaxiTheme_Setup::is_completed()) {
            return;
        }

        $screen = get_current_screen();
        if ($screen && $screen->id === 'toplevel_page_' . TaxiTheme_Setup::WIZARD_SLUG) {
            return;
        }

        $url = esc_url(TaxiTheme_Setup::wizard_url(1));
        ?>
        <div class="notice notice-info" style="border-left-color:#0f0f10;padding:16px 20px;">
            <p style="font-size:15px;margin:0 0 12px;">
                <strong>Welkom bij TaxiTheme!</strong>
                Rond de setup af om je taxi-website in te richten.
            </p>
            <p style="margin:0;">
                <a href="<?php echo $url; ?>" class="button button-primary">Start setup</a>
                <a href="#" onclick="return false;" class="button-link" style="margin-left:10px;color:#888;">
                    Later
                </a>
            </p>
        </div>
        <?php
    }
}

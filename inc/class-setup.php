<?php
if (!defined('ABSPATH')) {
    exit;
}

class TaxiTheme_Setup {

    const OPTION_COMPLETED = 'taxitheme_setup_completed';
    const WIZARD_SLUG      = 'taxitheme-setup';

    public static function is_completed() {
        return (bool) get_option(self::OPTION_COMPLETED, false);
    }

    public static function mark_completed() {
        update_option(self::OPTION_COMPLETED, true);
    }

    public static function reset() {
        delete_option(self::OPTION_COMPLETED);
    }

    public static function wizard_url($step = 1) {
        return admin_url('admin.php?page=' . self::WIZARD_SLUG . '&step=' . (int) $step);
    }
}

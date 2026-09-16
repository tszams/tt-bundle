<?php
if (!defined('ABSPATH')) {
    exit;
}

class TaxiTheme_Company_Info {

    const FIELDS = [
        'name'     => ['label' => 'Bedrijfsnaam',   'required' => true,  'type' => 'text'],
        'phone'    => ['label' => 'Telefoonnummer', 'required' => true,  'type' => 'tel'],
        'email'    => ['label' => 'E-mailadres',    'required' => false, 'type' => 'email'],
        'address'  => ['label' => 'Adres',          'required' => false, 'type' => 'text'],
        'postcode' => ['label' => 'Postcode',       'required' => false, 'type' => 'text'],
        'city'     => ['label' => 'Plaats',         'required' => false, 'type' => 'text'],
        'kvk'      => ['label' => 'KvK-nummer',     'required' => false, 'type' => 'text'],
    ];

    const SOCIAL_FIELDS = [
        'social_facebook'  => ['label' => 'Facebook URL',       'type' => 'url', 'placeholder' => 'https://facebook.com/...'],
        'social_instagram' => ['label' => 'Instagram URL',      'type' => 'url', 'placeholder' => 'https://instagram.com/...'],
        'social_linkedin'  => ['label' => 'LinkedIn URL',       'type' => 'url', 'placeholder' => 'https://linkedin.com/company/...'],
        'social_google'    => ['label' => 'Google Business URL','type' => 'url', 'placeholder' => 'https://g.page/...'],
    ];

    const DAYS = [
        'monday'    => 'Maandag',
        'tuesday'   => 'Dinsdag',
        'wednesday' => 'Woensdag',
        'thursday'  => 'Donderdag',
        'friday'    => 'Vrijdag',
        'saturday'  => 'Zaterdag',
        'sunday'    => 'Zondag',
    ];

    public static function option_key($field) {
        return 'taxitheme_company_' . $field;
    }

    public static function get($field, $default = '') {
        return get_option(self::option_key($field), $default);
    }

    public static function all() {
        $out = [];
        foreach (array_keys(self::FIELDS) as $field) {
            $out[$field] = self::get($field);
        }
        return $out;
    }

    public static function socials() {
        $out = [];
        foreach (array_keys(self::SOCIAL_FIELDS) as $field) {
            $out[$field] = self::get($field);
        }
        return $out;
    }

    public static function logo_id() {
        return (int) get_option(self::option_key('logo_id'), 0);
    }

    public static function logo_url($size = 'full') {
        $id = self::logo_id();
        if (!$id) return '';
        $src = wp_get_attachment_image_url($id, $size);
        return $src ?: '';
    }

    public static function has_logo() {
        return self::logo_url() !== '';
    }

    public static function is_247() {
        return (bool) get_option(self::option_key('hours_247'), 1);
    }

    public static function hours() {
        $stored = get_option(self::option_key('hours'), []);
        if (!is_array($stored)) $stored = [];
        $out = [];
        foreach (array_keys(self::DAYS) as $day) {
            $d = $stored[$day] ?? [];
            $out[$day] = [
                'closed' => !empty($d['closed']),
                'open'   => isset($d['open'])  ? sanitize_text_field($d['open'])  : '00:00',
                'close'  => isset($d['close']) ? sanitize_text_field($d['close']) : '23:59',
            ];
        }
        return $out;
    }

    /**
     * Compacte weergave van de openingstijden voor de footer.
     * Groepeert aaneengesloten dagen met identieke tijden en geeft een array
     * met regels terug, bv. ["Ma–Vr: 07:00–22:00", "Za–Zo: Gesloten"].
     * 24/7 → twee losse regels: ["24 uur per dag", "7 dagen per week"].
     */
    public static function formatted_hours() {
        if (self::is_247()) {
            return ['24 uur per dag', '7 dagen per week'];
        }

        $short = [
            'monday'    => 'Ma',
            'tuesday'   => 'Di',
            'wednesday' => 'Wo',
            'thursday'  => 'Do',
            'friday'    => 'Vr',
            'saturday'  => 'Za',
            'sunday'    => 'Zo',
        ];

        $groups  = [];
        $current = null;
        foreach (self::hours() as $day => $spec) {
            $sig = !empty($spec['closed']) ? 'closed' : $spec['open'] . '-' . $spec['close'];
            if ($current && $current['sig'] === $sig) {
                $current['end'] = $day;
                continue;
            }
            if ($current) $groups[] = $current;
            $current = ['start' => $day, 'end' => $day, 'sig' => $sig, 'spec' => $spec];
        }
        if ($current) $groups[] = $current;

        $out = [];
        foreach ($groups as $g) {
            $days = $short[$g['start']];
            if ($g['start'] !== $g['end']) {
                $days .= '–' . $short[$g['end']];
            }
            if (!empty($g['spec']['closed'])) {
                $times = 'Gesloten';
            } elseif ($g['spec']['open'] === '00:00' && $g['spec']['close'] === '23:59') {
                $times = '24 uur';
            } else {
                $times = $g['spec']['open'] . '–' . $g['spec']['close'];
            }
            $out[] = $days . ': ' . $times;
        }
        return $out;
    }

    public static function save(array $input) {
        foreach (self::FIELDS as $field => $meta) {
            if (!isset($input[$field])) {
                continue;
            }
            $value = self::sanitize($input[$field], $meta['type']);
            update_option(self::option_key($field), $value);
        }
        foreach (self::SOCIAL_FIELDS as $field => $meta) {
            if (!isset($input[$field])) {
                continue;
            }
            update_option(self::option_key($field), self::sanitize($input[$field], $meta['type']));
        }

        if (array_key_exists('logo_id', $input)) {
            $logo_id = absint($input['logo_id']);
            if ($logo_id > 0 && get_post_type($logo_id) === 'attachment') {
                update_option(self::option_key('logo_id'), $logo_id);
            } else {
                delete_option(self::option_key('logo_id'));
            }
        }

        if (array_key_exists('hours_247', $input)) {
            update_option(self::option_key('hours_247'), !empty($input['hours_247']) ? 1 : 0);
        }
        if (isset($input['hours']) && is_array($input['hours'])) {
            $out_hours = [];
            foreach (array_keys(self::DAYS) as $day) {
                $d = $input['hours'][$day] ?? [];
                $out_hours[$day] = [
                    'closed' => !empty($d['closed']) ? 1 : 0,
                    'open'   => self::sanitize_time($d['open']  ?? '00:00'),
                    'close'  => self::sanitize_time($d['close'] ?? '23:59'),
                ];
            }
            update_option(self::option_key('hours'), $out_hours);
        }
    }

    private static function sanitize_time($value) {
        $value = trim((string) $value);
        if (preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $value)) return $value;
        return '00:00';
    }

    public static function validate(array $input) {
        $errors = [];
        foreach (self::FIELDS as $field => $meta) {
            if (!empty($meta['required']) && empty(trim($input[$field] ?? ''))) {
                $errors[$field] = $meta['label'] . ' is verplicht.';
            }
            if ($meta['type'] === 'email' && !empty($input[$field]) && !is_email($input[$field])) {
                $errors[$field] = 'Ongeldig e-mailadres.';
            }
        }
        return $errors;
    }

    private static function sanitize($value, $type) {
        switch ($type) {
            case 'email': return sanitize_email($value);
            case 'tel':   return preg_replace('/[^0-9+ \-()]/', '', $value);
            case 'url':   return esc_url_raw(trim($value));
            default:      return sanitize_text_field($value);
        }
    }
}

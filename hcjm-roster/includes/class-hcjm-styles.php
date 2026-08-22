<?php
/**
 * Style customisation: schema, saved-value access, CSS generation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Styles {

    const OPTION_KEY = 'hcjm_styles';

    // -------------------------------------------------------------------------
    // Schema
    // -------------------------------------------------------------------------

    /**
     * Returns the full schema: groups → fields → metadata.
     *
     * field keys:
     *   label   string  Human-readable label
     *   desc    string  Optional hint shown under the control
     *   type    string  'color' | 'range' | 'font' | 'textarea'
     *   default mixed   Default value
     *   var     string  CSS custom-property name to set on :root (color/range/font)
     *   min     int     (range only)
     *   max     int     (range only)
     *   unit    string  (range only) e.g. 'px'
     *
     * @return array<string,array{label:string,fields:array<string,array<string,mixed>>}>
     */
    public static function schema(): array {
        return [
            'general' => [
                'label'  => __( 'Obecné', HCJM_TEXT_DOMAIN ),
                'fields' => [
                    'primary'     => [
                        'label'   => __( 'Primární barva', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Barevná lišta nadpisů sekcí, odznak Domácí, aktivní filtr, primární tlačítka', HCJM_TEXT_DOMAIN ),
                        'type'    => 'color',
                        'default' => '#154999',
                        'var'     => '--_navy-600',
                    ],
                    'accent' => [
                        'label'   => __( 'Akcentová barva', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Číslo dresu hráče', HCJM_TEXT_DOMAIN ),
                        'type'    => 'color',
                        'default' => '#d9000d',
                        'var'     => '--_red-600',
                    ],
                    'font_family' => [
                        'label'   => __( 'Písmo', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Typ písma pro celý shortcode výstup. Google Fonts se načtou automaticky.', HCJM_TEXT_DOMAIN ),
                        'type'    => 'font',
                        'default' => '',
                        'var'     => '--font-body',
                    ],
                ],
            ],
            'surfaces' => [
                'label'  => __( 'Povrchy a pozadí', HCJM_TEXT_DOMAIN ),
                'fields' => [
                    'surface_card'  => [
                        'label'   => __( 'Pozadí karet a řádků', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Karty hráčů, karty realizačního týmu, řádky zápasů', HCJM_TEXT_DOMAIN ),
                        'type'    => 'color',
                        'default' => '#ffffff',
                        'var'     => '--surface-card',
                    ],
                    'surface_page'  => [
                        'label'   => __( 'Stránkové pozadí', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Plocha za kartami a výpisy', HCJM_TEXT_DOMAIN ),
                        'type'    => 'color',
                        'default' => '#f2f5f8',
                        'var'     => '--surface-page',
                    ],
                    'surface_muted' => [
                        'label'   => __( 'Tlumené pozadí', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Placeholder fotek, odznak Venku, muted plochy', HCJM_TEXT_DOMAIN ),
                        'type'    => 'color',
                        'default' => '#e0e8f0',
                        'var'     => '--surface-muted',
                    ],
                ],
            ],
            'borders' => [
                'label'  => __( 'Ohraničení a tvary', HCJM_TEXT_DOMAIN ),
                'fields' => [
                    'border_subtle'  => [
                        'label'   => __( 'Jemný rámeček', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Okraj karet hráčů a řádků zápasů', HCJM_TEXT_DOMAIN ),
                        'type'    => 'color',
                        'default' => '#e0e8f0',
                        'var'     => '--border-subtle',
                    ],
                    'border_default' => [
                        'label'   => __( 'Standardní rámeček', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Levá lišta neutrálního řádku zápasu, odznak Venku', HCJM_TEXT_DOMAIN ),
                        'type'    => 'color',
                        'default' => '#c8d3df',
                        'var'     => '--border-default',
                    ],
                    'radius_lg'      => [
                        'label'   => __( 'Zaoblení karet hráčů', HCJM_TEXT_DOMAIN ),
                        'type'    => 'range',
                        'default' => '14',
                        'min'     => 0,
                        'max'     => 32,
                        'unit'    => 'px',
                        'var'     => '--radius-lg',
                    ],
                    'radius_md'      => [
                        'label'   => __( 'Zaoblení řádků zápasů', HCJM_TEXT_DOMAIN ),
                        'type'    => 'range',
                        'default' => '10',
                        'min'     => 0,
                        'max'     => 24,
                        'unit'    => 'px',
                        'var'     => '--radius-md',
                    ],
                    'radius_sm'      => [
                        'label'   => __( 'Zaoblení malých prvků', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Číslo dresu, malé odznaky', HCJM_TEXT_DOMAIN ),
                        'type'    => 'range',
                        'default' => '6',
                        'min'     => 0,
                        'max'     => 16,
                        'unit'    => 'px',
                        'var'     => '--radius-sm',
                    ],
                    'radius_full'    => [
                        'label'   => __( 'Zaoblení pilulkových prvků', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Filtrační tlačítka, pill odznaky', HCJM_TEXT_DOMAIN ),
                        'type'    => 'range',
                        'default' => '9999',
                        'min'     => 0,
                        'max'     => 9999,
                        'unit'    => 'px',
                        'var'     => '--radius-full',
                    ],
                ],
            ],
            'typography' => [
                'label'  => __( 'Text', HCJM_TEXT_DOMAIN ),
                'fields' => [
                    'text_heading' => [
                        'label'   => __( 'Barva nadpisů', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Příjmení hráče, název soupeře, nadpisy sekcí', HCJM_TEXT_DOMAIN ),
                        'type'    => 'color',
                        'default' => '#0e1117',
                        'var'     => '--text-heading',
                    ],
                    'text_body'    => [
                        'label'   => __( 'Barva textu', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Obecný obsah', HCJM_TEXT_DOMAIN ),
                        'type'    => 'color',
                        'default' => '#1c2130',
                        'var'     => '--text-body',
                    ],
                    'text_muted'   => [
                        'label'   => __( 'Tlumený text', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Křestní jméno, datum, ročník nar., „Plánováno"', HCJM_TEXT_DOMAIN ),
                        'type'    => 'color',
                        'default' => '#5c6a7a',
                        'var'     => '--text-muted',
                    ],
                    'text_link'    => [
                        'label'   => __( 'Barva odkazů', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Role realizačního týmu, interaktivní prvky', HCJM_TEXT_DOMAIN ),
                        'type'    => 'color',
                        'default' => '#154999',
                        'var'     => '--text-link',
                    ],
                ],
            ],
            'matches' => [
                'label'  => __( 'Výsledky zápasů', HCJM_TEXT_DOMAIN ),
                'fields' => [
                    'surface_win'  => [ 'label' => __( 'Výhra — pozadí řádku', HCJM_TEXT_DOMAIN ),        'type' => 'color', 'default' => '#ecfdf5', 'var' => '--surface-win' ],
                    'border_win'   => [ 'label' => __( 'Výhra — barevná lišta / rámeček', HCJM_TEXT_DOMAIN ), 'type' => 'color', 'default' => '#10b981', 'var' => '--border-win' ],
                    'text_win'     => [ 'label' => __( 'Výhra — pill výsledku', HCJM_TEXT_DOMAIN ),        'type' => 'color', 'default' => '#065f46', 'var' => '--text-win' ],
                    'surface_loss' => [ 'label' => __( 'Prohra — pozadí řádku', HCJM_TEXT_DOMAIN ),        'type' => 'color', 'default' => '#ffebee', 'var' => '--surface-loss' ],
                    'border_loss'  => [ 'label' => __( 'Prohra — barevná lišta / rámeček', HCJM_TEXT_DOMAIN ), 'type' => 'color', 'default' => '#d9000d', 'var' => '--border-loss' ],
                    'text_loss'    => [ 'label' => __( 'Prohra — pill výsledku', HCJM_TEXT_DOMAIN ),        'type' => 'color', 'default' => '#a50008', 'var' => '--text-loss' ],
                    'surface_draw' => [ 'label' => __( 'Remíza — pozadí řádku', HCJM_TEXT_DOMAIN ),        'type' => 'color', 'default' => '#fffbeb', 'var' => '--surface-draw' ],
                    'border_draw'  => [ 'label' => __( 'Remíza — barevná lišta / rámeček', HCJM_TEXT_DOMAIN ), 'type' => 'color', 'default' => '#f59e0b', 'var' => '--border-draw' ],
                    'text_draw'    => [ 'label' => __( 'Remíza — pill výsledku', HCJM_TEXT_DOMAIN ),        'type' => 'color', 'default' => '#92400e', 'var' => '--text-draw' ],
                ],
            ],
            'jersey' => [
                'label'  => __( 'Číslo dresu', HCJM_TEXT_DOMAIN ),
                'fields' => [
                    'jersey_font_size' => [
                        'label'   => __( 'Velikost textu čísla dresu (odznak)', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Velikost čísla dresu v odznaku vlevo nahoře na kartě hráče', HCJM_TEXT_DOMAIN ),
                        'type'    => 'range',
                        'default' => '11',
                        'min'     => 7,
                        'max'     => 20,
                        'unit'    => 'px',
                        'var'     => '--jersey-badge-font-size',
                    ],
                    'jersey_placeholder_font_size' => [
                        'label'   => __( 'Velikost textu čísla dresu (zástupný symbol)', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Velikost čísla dresu zobrazovaného místo fotografie hráče', HCJM_TEXT_DOMAIN ),
                        'type'    => 'range',
                        'default' => '3.5',
                        'min'     => 1,
                        'max'     => 8,
                        'step'    => 0.5,
                        'unit'    => 'rem',
                        'var'     => '--jersey-placeholder-font-size',
                    ],
                ],
            ],
            'positions' => [
                'label'  => __( 'Odznaky postů hráčů', HCJM_TEXT_DOMAIN ),
                'fields' => [
                    'tag_gk_bg'     => [ 'label' => __( 'Brankář — pozadí', HCJM_TEXT_DOMAIN ),   'type' => 'color', 'default' => '#fff7e6', 'var' => '--tag-gk-bg' ],
                    'tag_gk_text'   => [ 'label' => __( 'Brankář — text', HCJM_TEXT_DOMAIN ),     'type' => 'color', 'default' => '#b45309', 'var' => '--tag-gk-text' ],
                    'tag_gk_border' => [ 'label' => __( 'Brankář — rámeček', HCJM_TEXT_DOMAIN ), 'type' => 'color', 'default' => '#fbbf24', 'var' => '--tag-gk-border' ],
                    'tag_def_bg'     => [ 'label' => __( 'Obránce — pozadí', HCJM_TEXT_DOMAIN ),   'type' => 'color', 'default' => '#eff6ff', 'var' => '--tag-def-bg' ],
                    'tag_def_text'   => [ 'label' => __( 'Obránce — text', HCJM_TEXT_DOMAIN ),     'type' => 'color', 'default' => '#1d4ed8', 'var' => '--tag-def-text' ],
                    'tag_def_border' => [ 'label' => __( 'Obránce — rámeček', HCJM_TEXT_DOMAIN ), 'type' => 'color', 'default' => '#93c5fd', 'var' => '--tag-def-border' ],
                    'tag_fwd_bg'     => [ 'label' => __( 'Útočník — pozadí', HCJM_TEXT_DOMAIN ),   'type' => 'color', 'default' => '#fef2f2', 'var' => '--tag-fwd-bg' ],
                    'tag_fwd_text'   => [ 'label' => __( 'Útočník — text', HCJM_TEXT_DOMAIN ),     'type' => 'color', 'default' => '#b91c1c', 'var' => '--tag-fwd-text' ],
                    'tag_fwd_border' => [ 'label' => __( 'Útočník — rámeček', HCJM_TEXT_DOMAIN ), 'type' => 'color', 'default' => '#fca5a5', 'var' => '--tag-fwd-border' ],
                ],
            ],
            'branding' => [
                'label'  => __( 'Klub', HCJM_TEXT_DOMAIN ),
                'fields' => [
                    'club_logo' => [
                        'label'   => __( 'Logo klubu', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Logo HC Junior Mělník zobrazené v banneru nadcházejícího zápasu místo iniciál. Doporučený formát: PNG s průhledným pozadím.', HCJM_TEXT_DOMAIN ),
                        'type'    => 'image',
                        'default' => '',
                        'var'     => '',
                    ],
                ],
            ],
            'custom_css' => [
                'label'  => __( 'Vlastní CSS', HCJM_TEXT_DOMAIN ),
                'fields' => [
                    'custom_css' => [
                        'label'   => __( 'Vlastní CSS kód', HCJM_TEXT_DOMAIN ),
                        'desc'    => __( 'Vloží se na konec stylopisu stránky — přepíše výše nastavené hodnoty. Používej třídy jako .hcjm-card, .hcjm-match-row, .hcjm-player-lastname apod.', HCJM_TEXT_DOMAIN ),
                        'type'    => 'textarea',
                        'default' => '',
                        'var'     => '',
                    ],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Font map — key → [CSS stack, Google Fonts family query param]
    // -------------------------------------------------------------------------

    /** @return array<string,array{stack:string,gf:string}> */
    public static function font_options(): array {
        return [
            ''           => [ 'stack' => '',                                                                  'gf' => '', 'label' => __( 'Výchozí (systémové)', HCJM_TEXT_DOMAIN ) ],
            'inter'      => [ 'stack' => '"Inter", sans-serif',                                               'gf' => 'Inter:wght@400;500;600;700;900',           'label' => 'Inter' ],
            'montserrat' => [ 'stack' => '"Montserrat", sans-serif',                                          'gf' => 'Montserrat:wght@400;500;600;700;900',      'label' => 'Montserrat' ],
            'open-sans'  => [ 'stack' => '"Open Sans", sans-serif',                                           'gf' => 'Open+Sans:wght@400;500;600;700',           'label' => 'Open Sans' ],
            'roboto'     => [ 'stack' => '"Roboto", sans-serif',                                              'gf' => 'Roboto:wght@400;500;700;900',              'label' => 'Roboto' ],
            'oswald'     => [ 'stack' => '"Oswald", sans-serif',                                              'gf' => 'Oswald:wght@400;500;600;700',              'label' => 'Oswald' ],
            'raleway'    => [ 'stack' => '"Raleway", sans-serif',                                             'gf' => 'Raleway:wght@400;500;600;700;900',         'label' => 'Raleway' ],
            'nunito'     => [ 'stack' => '"Nunito", sans-serif',                                              'gf' => 'Nunito:wght@400;500;600;700;900',          'label' => 'Nunito' ],
            'lato'       => [ 'stack' => '"Lato", sans-serif',                                                'gf' => 'Lato:wght@400;700;900',                   'label' => 'Lato' ],
            'poppins'    => [ 'stack' => '"Poppins", sans-serif',                                             'gf' => 'Poppins:wght@400;500;600;700;900',         'label' => 'Poppins' ],
            'system'     => [ 'stack' => 'system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif', 'gf' => '',                              'label' => __( 'Systémové (explicitně)', HCJM_TEXT_DOMAIN ) ],
        ];
    }

    // -------------------------------------------------------------------------
    // Data access
    // -------------------------------------------------------------------------

    /** @return array<string,mixed> */
    public static function get_defaults(): array {
        $defaults = [];
        foreach ( self::schema() as $fields ) {
            foreach ( $fields['fields'] as $key => $field ) {
                $defaults[ $key ] = $field['default'];
            }
        }
        return $defaults;
    }

    /** @return array<string,mixed> saved values merged over defaults */
    public static function get_saved(): array {
        $saved = get_option( self::OPTION_KEY, [] );
        if ( ! is_array( $saved ) ) {
            $saved = [];
        }
        return array_merge( self::get_defaults(), $saved );
    }

    // -------------------------------------------------------------------------
    // CSS generation
    // -------------------------------------------------------------------------

    /**
     * Generate the CSS to inject for the given style values.
     * Returns an empty string when nothing is customised.
     *
     * @param array<string,mixed> $styles
     * @return string
     */
    public static function generate_css( array $styles ): string {
        $defaults    = self::get_defaults();
        $root_lines  = [];
        $custom_css  = '';

        foreach ( self::schema() as $group_key => $group ) {
            foreach ( $group['fields'] as $key => $field ) {
                $value = $styles[ $key ] ?? $field['default'];

                if ( $field['type'] === 'textarea' ) {
                    $custom_css = wp_strip_all_tags( (string) $value );
                    continue;
                }

                if ( $field['type'] === 'image' ) {
                    continue;
                }

                // Skip if the value equals the default (nothing to override)
                if ( (string) $value === (string) $field['default'] || $value === '' ) {
                    continue;
                }

                $css_var = $field['var'] ?? '';
                if ( ! $css_var ) {
                    continue;
                }

                if ( $field['type'] === 'color' ) {
                    $safe = sanitize_hex_color( (string) $value );
                    if ( $safe ) {
                        $root_lines[] = "  {$css_var}: {$safe};";
                    }
                } elseif ( $field['type'] === 'range' ) {
                    $unit  = $field['unit'] ?? '';
                    $num   = isset( $field['step'] ) ? (float) $value : (int) $value;
                    $root_lines[] = "  {$css_var}: {$num}{$unit};";
                } elseif ( $field['type'] === 'font' ) {
                    $fonts = self::font_options();
                    $stack = $fonts[ $value ]['stack'] ?? '';
                    if ( $stack ) {
                        $root_lines[] = "  {$css_var}: {$stack};";
                        $root_lines[] = "  --font-heading: {$stack};";
                    }
                }
            }
        }

        $out = '';
        if ( $root_lines ) {
            $out .= ":root {\n" . implode( "\n", $root_lines ) . "\n}\n";
        }
        if ( $custom_css ) {
            $out .= "\n" . $custom_css;
        }

        return $out;
    }

    /**
     * Return the Google Fonts stylesheet URL for the chosen font, or '' if none needed.
     */
    public static function get_font_url( string $font_key ): string {
        $fonts = self::font_options();
        $gf    = $fonts[ $font_key ]['gf'] ?? '';
        if ( ! $gf ) {
            return '';
        }
        return 'https://fonts.googleapis.com/css2?family=' . $gf . '&display=swap';
    }
}

<?php
/**
 * Public-facing asset loader.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Public {

    public function enqueue_assets(): void {
        if ( ! $this->page_has_shortcode() ) {
            return;
        }
        wp_enqueue_style(
            'hcjm-public',
            HCJM_PLUGIN_URL . 'public/css/public.css',
            [],
            HCJM_VERSION
        );
        wp_enqueue_script(
            'hcjm-public',
            HCJM_PLUGIN_URL . 'public/js/public.js',
            [],
            HCJM_VERSION,
            true
        );

        $styles  = HCJM_Styles::get_saved();
        $css     = HCJM_Styles::generate_css( $styles );
        if ( $css ) {
            wp_add_inline_style( 'hcjm-public', $css );
        }

        $font_key = $styles['font_family'] ?? '';
        $font_url = $font_key ? HCJM_Styles::get_font_url( $font_key ) : '';
        if ( $font_url ) {
            wp_enqueue_style( 'hcjm-gfont', $font_url, [ 'hcjm-public' ], null );
        }
    }

    /**
     * Check if the current post/page uses any HCJM shortcode.
     */
    private function page_has_shortcode(): bool {
        global $post;
        if ( ! $post ) {
            return false;
        }
        $shortcodes = [ 'hcjm_roster', 'hcjm_staff', 'hcjm_matches', 'hcjm_next_match', 'hcjm_match_schedule' ];
        foreach ( $shortcodes as $sc ) {
            if ( has_shortcode( $post->post_content, $sc ) ) {
                return true;
            }
        }
        return false;
    }
}

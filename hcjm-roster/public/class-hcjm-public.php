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
    }

    /**
     * Check if the current post/page uses any HCJM shortcode.
     */
    private function page_has_shortcode(): bool {
        global $post;
        if ( ! $post ) {
            return false;
        }
        $shortcodes = [ 'hcjm_roster', 'hcjm_staff', 'hcjm_matches' ];
        foreach ( $shortcodes as $sc ) {
            if ( has_shortcode( $post->post_content, $sc ) ) {
                return true;
            }
        }
        return false;
    }
}

<?php
/**
 * Shortcode registration and rendering.
 *
 * [hcjm_roster team="muzi-a" season="2025-2026"]
 * [hcjm_staff  team="muzi-a" season="2025-2026"]
 * [hcjm_matches team="muzi-a" type="upcoming|past|all" limit="10" season="2025-2026"]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Shortcodes {

    public function register(): void {
        add_shortcode( 'hcjm_roster',     [ $this, 'render_roster' ] );
        add_shortcode( 'hcjm_staff',      [ $this, 'render_staff' ] );
        add_shortcode( 'hcjm_matches',    [ $this, 'render_matches' ] );
        add_shortcode( 'hcjm_next_match', [ $this, 'render_next_match' ] );
    }

    // -------------------------------------------------------------------------
    // [hcjm_roster]
    // -------------------------------------------------------------------------

    /**
     * @param array<string,string>|string $atts
     * @return string
     */
    public function render_roster( $atts ): string {
        $atts = shortcode_atts(
            [
                'team'   => '',
                'season' => HCJM_Matches::current_season(),
            ],
            $atts,
            'hcjm_roster'
        );

        if ( ! $atts['team'] ) {
            return '<p class="hcjm-error">' . esc_html__( 'Chybí parametr team.', HCJM_TEXT_DOMAIN ) . '</p>';
        }

        $team = HCJM_Teams::get_by_slug( $atts['team'] );
        if ( ! $team ) {
            return '<p class="hcjm-error">' . esc_html__( 'Mužstvo nenalezeno.', HCJM_TEXT_DOMAIN ) . '</p>';
        }

        $players     = HCJM_Players::get_for_team( $team->ID, $atts['season'] );
        $has_jersey  = HCJM_Teams::has_jersey_numbers( $team->ID );
        $team_name   = esc_html( $team->post_title );
        $season      = esc_html( $atts['season'] );

        ob_start();
        include HCJM_PLUGIN_DIR . 'templates/roster.php';
        return (string) ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // [hcjm_staff]
    // -------------------------------------------------------------------------

    /**
     * @param array<string,string>|string $atts
     * @return string
     */
    public function render_staff( $atts ): string {
        $atts = shortcode_atts(
            [
                'team'   => '',
                'season' => HCJM_Matches::current_season(),
            ],
            $atts,
            'hcjm_staff'
        );

        if ( ! $atts['team'] ) {
            return '<p class="hcjm-error">' . esc_html__( 'Chybí parametr team.', HCJM_TEXT_DOMAIN ) . '</p>';
        }

        $team = HCJM_Teams::get_by_slug( $atts['team'] );
        if ( ! $team ) {
            return '<p class="hcjm-error">' . esc_html__( 'Mužstvo nenalezeno.', HCJM_TEXT_DOMAIN ) . '</p>';
        }

        $members   = HCJM_Staff::get_for_team( $team->ID, $atts['season'] );
        $team_name = esc_html( $team->post_title );
        $season    = esc_html( $atts['season'] );

        ob_start();
        include HCJM_PLUGIN_DIR . 'templates/staff.php';
        return (string) ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // [hcjm_matches]
    // -------------------------------------------------------------------------

    /**
     * @param array<string,string>|string $atts
     * @return string
     */
    public function render_matches( $atts ): string {
        $atts = shortcode_atts(
            [
                'team'   => '',
                'season' => HCJM_Matches::current_season(),
                'type'   => 'all',
                'limit'  => '0',
            ],
            $atts,
            'hcjm_matches'
        );

        $type  = in_array( $atts['type'], [ 'upcoming', 'past', 'all' ], true ) ? $atts['type'] : 'all';
        $limit = absint( $atts['limit'] );

        if ( ! $atts['team'] ) {
            return '<p class="hcjm-error">' . esc_html__( 'Chybí parametr team.', HCJM_TEXT_DOMAIN ) . '</p>';
        }

        $team = HCJM_Teams::get_by_slug( $atts['team'] );
        if ( ! $team ) {
            return '<p class="hcjm-error">' . esc_html__( 'Mužstvo nenalezeno.', HCJM_TEXT_DOMAIN ) . '</p>';
        }

        $matches   = HCJM_Database::get_matches( $team->ID, $atts['season'], $type, $limit );
        $team_name = esc_html( 'HC Junior Mělník — ' . $team->post_title );
        $season    = esc_html( $atts['season'] );

        ob_start();
        if ( $type === 'past' ) {
            include HCJM_PLUGIN_DIR . 'templates/matches-past.php';
        } else {
            include HCJM_PLUGIN_DIR . 'templates/matches-upcoming.php';
        }
        return (string) ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // [hcjm_next_match]
    // -------------------------------------------------------------------------

    /**
     * @param array<string,string>|string $atts
     * @return string
     */
    public function render_next_match( $atts ): string {
        $atts = shortcode_atts(
            [
                'category'  => '',
                'count'     => '3',
                'countdown' => 'yes',
                'season'    => HCJM_Matches::current_season(),
            ],
            $atts,
            'hcjm_next_match'
        );

        if ( ! $atts['category'] ) {
            return '<p class="hcjm-error">' . esc_html__( 'Chybí parametr category.', HCJM_TEXT_DOMAIN ) . '</p>';
        }

        $team = HCJM_Teams::get_by_slug( $atts['category'] );
        if ( ! $team ) {
            return '<p class="hcjm-error">' . esc_html__( 'Mužstvo nenalezeno.', HCJM_TEXT_DOMAIN ) . '</p>';
        }

        $count     = max( 1, absint( $atts['count'] ) );
        $countdown = strtolower( trim( $atts['countdown'] ) ) !== 'no';
        $matches   = HCJM_Database::get_matches( $team->ID, $atts['season'], 'upcoming', $count );
        $category  = esc_html( $team->post_title );

        ob_start();
        include HCJM_PLUGIN_DIR . 'templates/next-match-banner.php';
        return (string) ob_get_clean();
    }
}

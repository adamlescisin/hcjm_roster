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
        add_shortcode( 'hcjm_roster',          [ $this, 'render_roster' ] );
        add_shortcode( 'hcjm_staff',           [ $this, 'render_staff' ] );
        add_shortcode( 'hcjm_matches',         [ $this, 'render_matches' ] );
        add_shortcode( 'hcjm_next_match',      [ $this, 'render_next_match' ] );
        add_shortcode( 'hcjm_match_schedule',  [ $this, 'render_match_schedule' ] );
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
                'link'      => '',
                'badge'     => 'yes',
            ],
            $atts,
            'hcjm_next_match'
        );

        $team_id  = 0;
        $category = '';
        $team_map = [];

        if ( $atts['category'] ) {
            $team = HCJM_Teams::get_by_slug( $atts['category'] );
            if ( ! $team ) {
                return '<p class="hcjm-error">' . esc_html__( 'Mužstvo nenalezeno.', HCJM_TEXT_DOMAIN ) . '</p>';
            }
            $team_id  = $team->ID;
            $category = esc_html( $team->post_title );
        } else {
            $all_teams = get_posts( [ 'post_type' => 'hcjm_team', 'numberposts' => -1, 'post_status' => 'publish' ] );
            foreach ( $all_teams as $t ) {
                $team_map[ $t->ID ] = $t->post_title;
            }
        }

        $count     = max( 1, absint( $atts['count'] ) );
        $countdown = strtolower( trim( $atts['countdown'] ) ) !== 'no';
        $matches   = HCJM_Database::get_matches( $team_id, $atts['season'], 'upcoming', $count );
        $link      = esc_url_raw( $atts['link'] );

        ob_start();
        include HCJM_PLUGIN_DIR . 'templates/next-match-banner.php';
        return (string) ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // [hcjm_match_schedule]
    // -------------------------------------------------------------------------

    /**
     * Renders a full-club upcoming-match schedule with category filter pills.
     *
     * Attributes:
     *   limit  int     Max matches to show per team category (default 10, 0 = unlimited)
     *   season string  e.g. "2025-2026" (defaults to current season)
     *
     * @param array<string,string>|string $atts
     * @return string
     */
    public function render_match_schedule( $atts ): string {
        $atts = shortcode_atts(
            [
                'limit'  => '10',
                'season' => HCJM_Matches::current_season(),
            ],
            $atts,
            'hcjm_match_schedule'
        );

        $per_team = absint( $atts['limit'] );
        $season   = sanitize_text_field( $atts['season'] );

        $all_teams = HCJM_Teams::get_all();
        $team_ids  = array_map( static fn( $t ) => $t->ID, $all_teams );

        $matches = HCJM_Database::get_upcoming_multi( $team_ids, $season, $per_team );

        // Build a lookup: team_id → post_title
        $team_map = [];
        foreach ( $all_teams as $t ) {
            $team_map[ $t->ID ] = $t->post_title;
        }

        // Collect which team IDs actually appear in the result set (preserving order)
        $active_team_ids = [];
        foreach ( $matches as $m ) {
            $tid = (int) $m->team_id;
            if ( ! in_array( $tid, $active_team_ids, true ) ) {
                $active_team_ids[] = $tid;
            }
        }

        ob_start();
        include HCJM_PLUGIN_DIR . 'templates/match-schedule.php';
        return (string) ob_get_clean();
    }
}

<?php
/**
 * Match helpers: season utilities and result formatting.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Matches {

    /**
     * Get the current season from plugin options.
     *
     * @return string e.g. "2025-2026"
     */
    public static function current_season(): string {
        return (string) get_option( 'hcjm_current_season', '' );
    }

    /**
     * Generate a list of seasons for select dropdowns.
     *
     * @param int $range Number of past seasons to include.
     * @return string[]
     */
    public static function season_list( int $range = 5 ): array {
        $current = self::current_season();
        if ( ! $current ) {
            $year    = (int) gmdate( 'Y' );
            $month   = (int) gmdate( 'm' );
            $current = $month >= 9 ? "{$year}-" . ( $year + 1 ) : ( $year - 1 ) . "-{$year}";
        }

        [ $start_year ] = explode( '-', $current );
        $start_year = (int) $start_year;

        $seasons = [];
        for ( $i = 0; $i <= $range; $i++ ) {
            $y         = $start_year - $i;
            $seasons[] = "{$y}-" . ( $y + 1 );
        }
        return $seasons;
    }

    /**
     * Format a match result string for display.
     *
     * @param object $match DB row.
     * @return string e.g. "3:1" or "–"
     */
    public static function format_score( object $match ): string {
        if ( $match->score_home === null || $match->score_away === null ) {
            return '–';
        }
        return esc_html( $match->score_home . ':' . $match->score_away );
    }

    /**
     * Whether the home side won (used for CSS highlights).
     *
     * @param object $match
     * @return bool|null null if no result yet
     */
    public static function home_won( object $match ): ?bool {
        if ( $match->score_home === null || $match->score_away === null ) {
            return null;
        }
        return (int) $match->score_home > (int) $match->score_away;
    }

    /**
     * Whether HC Junior Mělník won the given match.
     *
     * @param object $match
     * @return bool|null
     */
    public static function hcjm_won( object $match ): ?bool {
        $home_won = self::home_won( $match );
        if ( $home_won === null ) {
            return null;
        }
        return $match->is_home ? $home_won : ! $home_won;
    }

    /**
     * Format the match date for display.
     *
     * @param object $match
     * @return string
     */
    public static function format_date( object $match ): string {
        if ( ! $match->match_date ) {
            return '–';
        }
        $ts = strtotime( $match->match_date );
        if ( ! $ts ) {
            return esc_html( $match->match_date );
        }
        return esc_html( (string) wp_date( 'j. n. Y', $ts ) );
    }
}

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
        // score_home = HCJM's goals, score_away = opponent's goals (always).
        // Hockey notation puts the HOME side first regardless of who we are.
        $ours   = (int) $match->score_home;
        $theirs = (int) $match->score_away;
        if ( $match->is_home ) {
            return esc_html( $ours . ':' . $theirs );
        }
        return esc_html( $theirs . ':' . $ours );
    }

    /**
     * Whether HC Junior Mělník won the given match.
     * score_home is always HCJM's score; is_home does not affect the outcome.
     *
     * @param object $match
     * @return bool|null null if no result yet
     */
    public static function hcjm_won( object $match ): ?bool {
        if ( $match->score_home === null || $match->score_away === null ) {
            return null;
        }
        $ours   = (int) $match->score_home;
        $theirs = (int) $match->score_away;
        if ( $ours === $theirs ) {
            return null; // draw — caller treats null as draw
        }
        return $ours > $theirs;
    }

    /**
     * Get a correct UTC timestamp for a match's stored local date/time.
     *
     * match_date is stored as Czech local time. Using strtotime() treats it as
     * UTC and then wp_date() adds the Prague offset (+2h), causing a 2-hour
     * display shift. DateTimeImmutable + wp_timezone() anchors the stored value
     * to the correct timezone before extracting the Unix timestamp.
     *
     * @param object $match
     * @return int 0 if no date or parse failure
     */
    public static function get_match_timestamp( object $match ): int {
        if ( ! $match->match_date ) {
            return 0;
        }
        try {
            $dt = new DateTimeImmutable( $match->match_date, wp_timezone() );
            return $dt->getTimestamp();
        } catch ( Exception $e ) {
            return 0;
        }
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
        $ts = self::get_match_timestamp( $match );
        if ( ! $ts ) {
            return esc_html( $match->match_date );
        }
        return esc_html( (string) wp_date( 'j. n. Y', $ts ) );
    }
}

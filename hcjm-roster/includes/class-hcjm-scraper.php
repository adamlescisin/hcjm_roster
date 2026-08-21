<?php
/**
 * Scraper: fetches and parses match data from zapasy.ceskyhokej.cz.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Scraper {

    /**
     * Fetch and store matches for a team.
     *
     * @param int    $team_id     WordPress post ID of the hcjm_team.
     * @param string $season      e.g. "2025-2026"
     * @return array{imported:int,errors:string[]} Result summary.
     */
    public static function sync_team( int $team_id, string $season ): array {
        $external_id = HCJM_Teams::get_external_id( $team_id );
        $league_id   = HCJM_Teams::get_league_id( $team_id );

        if ( ! $external_id ) {
            return [ 'imported' => 0, 'errors' => [ __( 'Tým nemá nastavené ID na ceskyhokej.cz', HCJM_TEXT_DOMAIN ) ] ];
        }

        // ceskyhokej.cz uses just the start year of the season
        $season_year = explode( '-', $season )[0];
        $url         = add_query_arg(
            [
                'filter[season]'    => $season_year,
                'filter[team]'      => $external_id,
                'filter[league]'    => $league_id ?: '',
                'filter[direction]' => 'ASC',
                'filter[sort]'      => '',
            ],
            'https://zapasy.ceskyhokej.cz/seznam-zapasu'
        );

        $response = wp_remote_get( $url, [
            'timeout'    => 20,
            'user-agent' => 'Mozilla/5.0 (compatible; HCJM-Roster/1.0; +https://hcjuniormelnik.cz)',
        ] );

        if ( is_wp_error( $response ) ) {
            return [ 'imported' => 0, 'errors' => [ $response->get_error_message() ] ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return [ 'imported' => 0, 'errors' => [ sprintf( __( 'HTTP chyba: %d', HCJM_TEXT_DOMAIN ), $code ) ] ];
        }

        $html   = wp_remote_retrieve_body( $response );
        $parsed = self::parse_html( $html, $team_id, $season, $external_id );

        $imported = 0;
        $errors   = [];
        foreach ( $parsed as $match_data ) {
            $result = HCJM_Database::upsert_match( $match_data );
            if ( $result ) {
                $imported++;
            } else {
                $errors[] = sprintf( __( 'Nepodařilo se uložit zápas: %s', HCJM_TEXT_DOMAIN ), $match_data['external_id'] );
            }
        }

        return [ 'imported' => $imported, 'errors' => $errors ];
    }

    /**
     * Parse the HTML table from ceskyhokej.cz into match data arrays.
     *
     * @param string $html
     * @param int    $team_id
     * @param string $season
     * @param string $external_team_id
     * @return array<int,array<string,mixed>>
     */
    private static function parse_html( string $html, int $team_id, string $season, string $external_team_id ): array {
        $matches = [];

        $dom = new DOMDocument();
        libxml_use_internal_errors( true );
        $dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
        libxml_clear_errors();

        $xpath = new DOMXPath( $dom );

        // Find match rows — ceskyhokej.cz table rows
        $rows = $xpath->query( '//table//tr[td]' );
        if ( ! $rows || $rows->length === 0 ) {
            // Fallback: try to find any table rows with match data
            $rows = $xpath->query( '//*[contains(@class,"match") or contains(@class,"zapas")]' );
        }

        if ( ! $rows ) {
            return $matches;
        }

        foreach ( $rows as $row ) {
            /** @var DOMElement $row */
            $cells = $xpath->query( 'td', $row );
            if ( ! $cells || $cells->length < 4 ) {
                continue;
            }

            $data = self::extract_row_data( $cells, $xpath, $team_id, $season, $external_team_id );
            if ( $data ) {
                $matches[] = $data;
            }
        }

        // If standard table parsing failed, try structured data approach
        if ( empty( $matches ) ) {
            $matches = self::parse_structured( $xpath, $team_id, $season, $external_team_id );
        }

        return $matches;
    }

    /**
     * Extract match data from a table row.
     *
     * @param DOMNodeList $cells
     * @param DOMXPath    $xpath
     * @param int         $team_id
     * @param string      $season
     * @param string      $external_team_id
     * @return array<string,mixed>|null
     */
    private static function extract_row_data( $cells, DOMXPath $xpath, int $team_id, string $season, string $external_team_id ): ?array {
        $texts = [];
        for ( $i = 0; $i < $cells->length; $i++ ) {
            $texts[] = trim( $cells->item( $i )->textContent );
        }

        // Typical ceskyhokej.cz table columns: date | round | home_team | score | away_team
        // We try to detect the date in any cell
        $date_str    = '';
        $round       = '';
        $home_team   = '';
        $away_team   = '';
        $score_text  = '';
        $external_id = '';

        // Try to get a link-based external ID from the row
        $links = $xpath->query( './/a[contains(@href,"detail")]', $cells->item( 0 ) )
              ?: $xpath->query( './/a[contains(@href,"zapas")]', $cells->item( 0 ) );
        if ( $links && $links->length > 0 ) {
            /** @var DOMElement $link */
            $link        = $links->item( 0 );
            $href        = $link->getAttribute( 'href' );
            $external_id = preg_replace( '/[^a-zA-Z0-9\-_]/', '', basename( $href ) );
        }

        // Detect date (pattern: d.m.YYYY or dd.mm.YYYY H:i)
        foreach ( $texts as $idx => $text ) {
            if ( preg_match( '/(\d{1,2}\.\d{1,2}\.\d{4})/', $text, $m ) ) {
                $date_str = $m[1];
                // Try to get time too
                if ( preg_match( '/(\d{1,2}:\d{2})/', $text, $mt ) ) {
                    $date_str .= ' ' . $mt[1];
                }
                break;
            }
        }

        if ( ! $date_str ) {
            return null;
        }

        // Parse date
        $timestamp  = self::parse_czech_date( $date_str );
        $match_date = $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : '';

        // Find teams and score from remaining cells
        foreach ( $texts as $idx => $text ) {
            if ( preg_match( '/^\d+:\d+$/', trim( $text ) ) || preg_match( '/^\d+\s*:\s*\d+$/', trim( $text ) ) ) {
                $score_text = trim( $text );
            }
        }

        // Heuristic: find home/away team names (cells that don't look like dates/scores/rounds)
        $team_cells = [];
        foreach ( $texts as $text ) {
            $clean = trim( $text );
            if (
                $clean &&
                ! preg_match( '/^\d{1,2}\.\d{1,2}\./', $clean ) &&
                ! preg_match( '/^\d+:\d+$/', $clean ) &&
                ! preg_match( '/^\d+\.$/', $clean ) &&
                strlen( $clean ) > 2
            ) {
                $team_cells[] = $clean;
            }
        }

        if ( count( $team_cells ) >= 2 ) {
            $home_team = $team_cells[0];
            $away_team = $team_cells[1];
        }

        // Determine is_home based on whether our team appears as home
        $our_team_lower = strtolower( 'mělník' );
        $is_home        = stripos( $home_team, 'mělník' ) !== false ||
                          stripos( $home_team, 'melnik' ) !== false ||
                          stripos( $home_team, 'junior' ) !== false;

        $opponent = $is_home ? $away_team : $home_team;

        // Parse score
        $score_home = null;
        $score_away = null;
        $status     = 'planned';

        if ( $score_text ) {
            $parts = preg_split( '/\s*:\s*/', $score_text );
            if ( count( $parts ) === 2 && is_numeric( $parts[0] ) && is_numeric( $parts[1] ) ) {
                $score_home = (int) $parts[0];
                $score_away = (int) $parts[1];
                $status     = 'played';
            }
        }

        if ( ! $external_id ) {
            $external_id = md5( $match_date . $home_team . $away_team );
        }

        return [
            'team_id'     => $team_id,
            'season'      => $season,
            'match_date'  => $match_date,
            'is_home'     => $is_home ? 1 : 0,
            'opponent'    => $opponent ?: ( $is_home ? $away_team : $home_team ),
            'score_home'  => $score_home,
            'score_away'  => $score_away,
            'status'      => $status,
            'external_id' => $external_id,
            'round'       => $round,
        ];
    }

    /**
     * Alternative parser for structured / JSON-LD data on the page.
     *
     * @param DOMXPath $xpath
     * @param int      $team_id
     * @param string   $season
     * @param string   $external_team_id
     * @return array<int,array<string,mixed>>
     */
    private static function parse_structured( DOMXPath $xpath, int $team_id, string $season, string $external_team_id ): array {
        $matches  = [];
        $scripts  = $xpath->query( '//script[@type="application/json" or contains(text(),"matches")]' );

        if ( ! $scripts ) {
            return $matches;
        }

        foreach ( $scripts as $script ) {
            $json = $script->textContent;
            $data = json_decode( $json, true );
            if ( ! is_array( $data ) ) {
                continue;
            }

            $items = $data['data'] ?? $data['matches'] ?? $data;
            if ( ! is_array( $items ) ) {
                continue;
            }

            foreach ( $items as $item ) {
                if ( ! is_array( $item ) ) {
                    continue;
                }

                $match_date = sanitize_text_field( $item['datetime'] ?? $item['date'] ?? '' );
                $opponent   = sanitize_text_field( $item['opponent'] ?? $item['awayTeam']['name'] ?? $item['homeTeam']['name'] ?? '' );
                $ext_id     = sanitize_text_field( (string) ( $item['id'] ?? '' ) );

                if ( ! $match_date || ! $opponent ) {
                    continue;
                }

                $score_home = isset( $item['homeScore'] ) ? (int) $item['homeScore'] : null;
                $score_away = isset( $item['awayScore'] ) ? (int) $item['awayScore'] : null;
                $status     = ( $score_home !== null ) ? 'played' : 'planned';
                $is_home    = (bool) ( $item['isHome'] ?? 1 );

                $matches[] = [
                    'team_id'     => $team_id,
                    'season'      => $season,
                    'match_date'  => $match_date,
                    'is_home'     => $is_home ? 1 : 0,
                    'opponent'    => $opponent,
                    'score_home'  => $score_home,
                    'score_away'  => $score_away,
                    'status'      => $status,
                    'external_id' => $ext_id ?: md5( $match_date . $opponent ),
                    'round'       => sanitize_text_field( (string) ( $item['round'] ?? '' ) ),
                ];
            }
        }

        return $matches;
    }

    /**
     * Parse a Czech-formatted date string into a Unix timestamp.
     *
     * @param string $date_str e.g. "15.10.2025" or "15.10.2025 18:00"
     * @return int|null
     */
    private static function parse_czech_date( string $date_str ): ?int {
        $date_str = trim( $date_str );

        // "d.m.Y H:i" or "d.m.Y"
        $ts = false;
        if ( preg_match( '/(\d{1,2})\.(\d{1,2})\.(\d{4})\s*(\d{1,2}:\d{2})?/', $date_str, $m ) ) {
            $iso = sprintf( '%04d-%02d-%02d', $m[3], $m[2], $m[1] );
            if ( ! empty( $m[4] ) ) {
                $iso .= ' ' . $m[4] . ':00';
            } else {
                $iso .= ' 00:00:00';
            }
            $ts = strtotime( $iso );
        }

        return $ts ? (int) $ts : null;
    }

    /**
     * Sync all teams that have an external ID set.
     *
     * @param string $season
     * @return array<int,array{imported:int,errors:string[]}> Keyed by team_id.
     */
    public static function sync_all_teams( string $season ): array {
        $results = [];
        $teams   = HCJM_Teams::get_all();

        foreach ( $teams as $team ) {
            if ( HCJM_Teams::get_external_id( $team->ID ) ) {
                $results[ $team->ID ] = self::sync_team( $team->ID, $season );
            }
        }

        return $results;
    }
}

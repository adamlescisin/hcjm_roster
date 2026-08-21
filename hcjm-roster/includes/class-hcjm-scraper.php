<?php
/**
 * Scraper: fetches and parses match data from zapasy.ceskyhokej.cz.
 *
 * Strategy:
 *  1. Try the JSON API endpoint the SPA frontend calls (api.ceskyhokej.cz).
 *  2. Fall back to HTML table parsing of the list page.
 *  3. If the page looks like an SPA shell (no data), report it as an error so
 *     the admin knows the URL or IDs are wrong rather than seeing "0 imported".
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Scraper {

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Fetch and store matches for one team.
     *
     * @param int    $team_id
     * @param string $season   e.g. "2025-2026"
     * @return array{imported:int,errors:string[],debug:string}
     */
    public static function sync_team( int $team_id, string $season ): array {
        $external_id = HCJM_Teams::get_external_id( $team_id );
        $league_id   = HCJM_Teams::get_league_id( $team_id );

        if ( ! $external_id ) {
            return [ 'imported' => 0, 'errors' => [ __( 'Tým nemá nastavené ID na ceskyhokej.cz', HCJM_TEXT_DOMAIN ) ], 'debug' => '' ];
        }

        // ceskyhokej.cz season filter uses just the start year
        $season_year = explode( '-', $season )[0];

        // ------------------------------------------------------------------
        // Strategy 1: JSON API used by the SPA frontend
        // ------------------------------------------------------------------
        $parsed = self::fetch_api( $external_id, $league_id, $season_year, $team_id, $season );
        $debug  = '';

        if ( $parsed === null ) {
            // ------------------------------------------------------------------
            // Strategy 2: HTML table from the list page
            // ------------------------------------------------------------------
            $list_url = add_query_arg(
                [
                    'filter[season]'       => $season_year,
                    'filter[team]'         => $external_id,
                    'filter[league]'       => $league_id ?: '',
                    'filter[direction]'    => 'ASC',
                    'filter[timeShortcut]' => 'this-season',
                ],
                'https://zapasy.ceskyhokej.cz/seznam-zapasu'
            );

            $response = self::remote_get( $list_url );

            if ( is_wp_error( $response ) ) {
                return [
                    'imported' => 0,
                    'errors'   => [ $response->get_error_message() ],
                    'debug'    => 'URL: ' . $list_url,
                ];
            }

            $code = wp_remote_retrieve_response_code( $response );
            $html = wp_remote_retrieve_body( $response );
            $debug = sprintf( 'URL: %s | HTTP: %d | body: %d B | preview: %s',
                $list_url, $code, strlen( $html ),
                esc_html( substr( strip_tags( $html ), 0, 200 ) )
            );

            if ( $code !== 200 ) {
                return [
                    'imported' => 0,
                    'errors'   => [ sprintf( __( 'HTTP chyba: %d', HCJM_TEXT_DOMAIN ), $code ) ],
                    'debug'    => $debug,
                ];
            }

            $parsed = self::parse_html( $html, $team_id, $season, $external_id );

            // Detect SPA shell: got 200 but zero parseable rows
            if ( $parsed === [] ) {
                $is_spa = self::looks_like_spa( $html );
                $err    = $is_spa
                    ? __( 'Stránka ceskyhokej.cz vrátila prázdný SPA shell (data se načítají JavaScriptem). Zkontroluj ID týmu/ligy nebo kontaktuj správce.', HCJM_TEXT_DOMAIN )
                    : __( 'Na stránce nebyla nalezena žádná data zápasů. Zkontroluj ID týmu a ligy v nastavení mužstva.', HCJM_TEXT_DOMAIN );
                return [ 'imported' => 0, 'errors' => [ $err ], 'debug' => $debug ];
            }
        }

        $imported = 0;
        $errors   = [];
        foreach ( $parsed as $match_data ) {
            $result = HCJM_Database::upsert_match( $match_data );
            if ( $result ) {
                $imported++;
            } else {
                $errors[] = sprintf(
                    __( 'Nepodařilo se uložit zápas: %s', HCJM_TEXT_DOMAIN ),
                    $match_data['external_id']
                );
            }
        }

        return [ 'imported' => $imported, 'errors' => $errors, 'debug' => $debug ];
    }

    /**
     * Sync all teams that have an external ID set.
     *
     * @param string $season
     * @return array<int,array{imported:int,errors:string[],debug:string}> Keyed by team_id.
     */
    public static function sync_all_teams( string $season ): array {
        $results = [];
        foreach ( HCJM_Teams::get_all() as $team ) {
            if ( HCJM_Teams::get_external_id( $team->ID ) ) {
                $results[ $team->ID ] = self::sync_team( $team->ID, $season );
            }
        }
        return $results;
    }

    // -------------------------------------------------------------------------
    // Strategy 1: JSON API
    // -------------------------------------------------------------------------

    /**
     * Try the unofficial JSON API that the ceskyhokej.cz SPA calls.
     * Returns null if the endpoint isn't available or returns unexpected data.
     *
     * @return array<int,array<string,mixed>>|null  null = strategy failed, try next
     */
    private static function fetch_api( string $external_id, string $league_id, string $season_year, int $team_id, string $season ): ?array {
        // Known API base — the SPA at zapasy.ceskyhokej.cz calls this
        $api_url = add_query_arg(
            array_filter( [
                'season' => $season_year,
                'team'   => $external_id,
                'league' => $league_id ?: null,
                'sort'   => 'date',
                'order'  => 'asc',
                'limit'  => 200,
            ] ),
            'https://api.ceskyhokej.cz/v1/matches'
        );

        $response = self::remote_get( $api_url, [ 'Accept' => 'application/json' ] );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return null;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! is_array( $data ) ) {
            return null;
        }

        // The API might wrap results: { data: [...] } or return array directly
        $items = $data['data'] ?? $data['matches'] ?? ( isset( $data[0] ) ? $data : null );
        if ( ! is_array( $items ) ) {
            return null;
        }

        $matches = [];
        foreach ( $items as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }
            $m = self::normalise_api_item( $item, $team_id, $season, $external_id );
            if ( $m ) {
                $matches[] = $m;
            }
        }

        return $matches ?: null; // null = treat as "strategy failed"
    }

    /**
     * Convert an API response item into our internal match array.
     *
     * @param array<string,mixed> $item
     * @return array<string,mixed>|null
     */
    private static function normalise_api_item( array $item, int $team_id, string $season, string $external_team_id ): ?array {
        // Date: try multiple common field names
        $date_raw = $item['datetime'] ?? $item['date'] ?? $item['matchDate'] ?? $item['match_date'] ?? '';
        if ( ! $date_raw ) {
            return null;
        }

        $ts = is_numeric( $date_raw ) ? (int) $date_raw : strtotime( (string) $date_raw );
        if ( ! $ts ) {
            return null;
        }
        $match_date = gmdate( 'Y-m-d H:i:s', $ts );

        $home_name = $item['homeTeam']['name'] ?? $item['home_team'] ?? $item['home'] ?? '';
        $away_name = $item['awayTeam']['name'] ?? $item['away_team'] ?? $item['away'] ?? '';
        $opponent  = $item['opponent'] ?? '';

        $is_home = self::name_is_ours( $home_name );
        if ( ! $opponent ) {
            $opponent = $is_home ? $away_name : $home_name;
        }

        $score_home = isset( $item['homeScore'] ) ? (int) $item['homeScore'] : ( isset( $item['score_home'] ) ? (int) $item['score_home'] : null );
        $score_away = isset( $item['awayScore'] ) ? (int) $item['awayScore'] : ( isset( $item['score_away'] ) ? (int) $item['score_away'] : null );
        $status     = ( $score_home !== null && $score_away !== null ) ? 'played' : 'planned';
        $ext_id     = sanitize_text_field( (string) ( $item['id'] ?? $item['externalId'] ?? '' ) );

        return [
            'team_id'     => $team_id,
            'season'      => $season,
            'match_date'  => $match_date,
            'is_home'     => $is_home ? 1 : 0,
            'opponent'    => sanitize_text_field( $opponent ),
            'score_home'  => $score_home,
            'score_away'  => $score_away,
            'status'      => $status,
            'external_id' => $ext_id ?: md5( $match_date . $home_name . $away_name ),
            'round'       => sanitize_text_field( (string) ( $item['round'] ?? $item['roundNumber'] ?? '' ) ),
        ];
    }

    // -------------------------------------------------------------------------
    // Strategy 2: HTML table parsing
    // -------------------------------------------------------------------------

    /**
     * Parse the HTML table page from ceskyhokej.cz.
     *
     * Expected columns: Datum | Začátek | Domácí | Hosté | Stav (+ optional extras)
     *
     * @param string $html
     * @param int    $team_id
     * @param string $season
     * @param string $external_team_id
     * @return array<int,array<string,mixed>>
     */
    private static function parse_html( string $html, int $team_id, string $season, string $external_team_id ): array {
        $dom = new DOMDocument();
        libxml_use_internal_errors( true );
        $dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
        libxml_clear_errors();

        $xpath   = new DOMXPath( $dom );
        $matches = [];

        // Try every table; use the first one whose headers we can recognise
        $tables = $xpath->query( '//table' );
        if ( ! $tables ) {
            return self::parse_embedded_json( $xpath, $team_id, $season );
        }

        foreach ( $tables as $table ) {
            $header_row = $xpath->query( './/tr[1]', $table );
            if ( ! $header_row || $header_row->length === 0 ) {
                continue;
            }

            $header_cells = $xpath->query( './/th | .//td', $header_row->item( 0 ) );
            if ( ! $header_cells || $header_cells->length < 3 ) {
                continue;
            }

            $col = self::detect_ceskyhokej_columns( $header_cells );

            // Need at least Domácí or Hosté to proceed
            if ( ! isset( $col['home'] ) && ! isset( $col['away'] ) ) {
                continue;
            }

            $rows = $xpath->query( './/tr', $table );
            foreach ( $rows as $i => $row ) {
                if ( $i === 0 ) {
                    continue; // skip header
                }
                $cells = $xpath->query( './/td', $row );
                if ( ! $cells || $cells->length < 3 ) {
                    continue;
                }
                $data = self::extract_named_row( $cells, $xpath, $col, $team_id, $season );
                if ( $data ) {
                    $matches[] = $data;
                }
            }

            if ( ! empty( $matches ) ) {
                return $matches;
            }
        }

        // Fallback: embedded JSON in <script> tags
        if ( empty( $matches ) ) {
            $matches = self::parse_embedded_json( $xpath, $team_id, $season );
        }

        return $matches;
    }

    /**
     * Detect ceskyhokej.cz column indices from the header row.
     *
     * Recognised headers: Datum, Začátek / Čas, Domácí, Hosté, Stav / Výsledek
     *
     * @param DOMNodeList $header_cells
     * @return array<string,int>  keys: date, time, home, away, status
     */
    private static function detect_ceskyhokej_columns( $header_cells ): array {
        $col = [];
        for ( $i = 0; $i < $header_cells->length; $i++ ) {
            $t = mb_strtolower( trim( $header_cells->item( $i )->textContent ) );
            if ( strpos( $t, 'datum' ) !== false || strpos( $t, 'dátum' ) !== false ) {
                $col['date'] = $i;
            } elseif ( strpos( $t, 'začátek' ) !== false || strpos( $t, 'začatek' ) !== false || $t === 'čas' || $t === 'cas' || strpos( $t, 'čas' ) !== false ) {
                $col['time'] = $i;
            } elseif ( strpos( $t, 'domácí' ) !== false || strpos( $t, 'domaci' ) !== false ) {
                $col['home'] = $i;
            } elseif ( strpos( $t, 'hosté' ) !== false || strpos( $t, 'hoste' ) !== false ) {
                $col['away'] = $i;
            } elseif ( strpos( $t, 'stav' ) !== false || strpos( $t, 'výsledek' ) !== false || strpos( $t, 'vysledek' ) !== false || strpos( $t, 'skóre' ) !== false ) {
                $col['status'] = $i;
            }
        }
        return $col;
    }

    /**
     * Extract one match from a data row using the named column map.
     *
     * @param DOMNodeList          $cells
     * @param DOMXPath             $xpath
     * @param array<string,int>    $col     Output of detect_ceskyhokej_columns()
     * @return array<string,mixed>|null
     */
    private static function extract_named_row( $cells, DOMXPath $xpath, array $col, int $team_id, string $season ): ?array {
        $get = static function( string $key ) use ( $cells, $col ): string {
            if ( ! isset( $col[ $key ] ) ) {
                return '';
            }
            $cell = $cells->item( $col[ $key ] );
            return $cell ? trim( $cell->textContent ) : '';
        };

        $date_raw   = $get( 'date' );
        $time_raw   = $get( 'time' );
        $home_name  = trim( $get( 'home' ) );
        $away_name  = trim( $get( 'away' ) );
        $status_raw = trim( $get( 'status' ) );

        if ( ! $home_name && ! $away_name ) {
            return null;
        }

        // Build datetime string: date from Datum cell, time from Začátek cell
        // Začátek may contain multiple times ("18:00 / 17:00") — take the first
        $time_clean = '';
        if ( $time_raw && preg_match( '/(\d{1,2}:\d{2})/', $time_raw, $tm ) ) {
            $time_clean = $tm[1];
        }

        $date_str = trim( $date_raw );
        if ( $time_clean ) {
            $date_str .= ' ' . $time_clean;
        }

        $ts         = self::parse_czech_date( $date_str );
        $match_date = $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : '';

        if ( ! $match_date ) {
            return null;
        }

        // Home/away: "Domácí" = HC Junior Mělník → is_home = true
        $is_home = self::name_is_ours( $home_name );
        $opponent = $is_home ? $away_name : $home_name;

        // Status + score from "Stav" column
        // "Připraveno" / "Nový" / "Schváleno" → planned
        // "X:Y" pattern → played
        $score_home = null;
        $score_away = null;
        $status     = 'planned';

        if ( $status_raw && preg_match( '/(\d+)\s*:\s*(\d+)/', $status_raw, $sm ) ) {
            $score_home = (int) $sm[1];
            $score_away = (int) $sm[2];
            $status     = 'played';
        }

        // External ID from any link in the row
        $external_id = '';
        for ( $i = 0; $i < $cells->length; $i++ ) {
            $links = $xpath->query( './/a[@href]', $cells->item( $i ) );
            if ( $links && $links->length > 0 ) {
                /** @var DOMElement $a */
                $a    = $links->item( 0 );
                $href = $a->getAttribute( 'href' );
                $external_id = preg_replace( '/[^a-zA-Z0-9\-_]/', '', basename( $href ) );
                if ( $external_id ) {
                    break;
                }
            }
        }
        if ( ! $external_id ) {
            $external_id = md5( $match_date . $home_name . $away_name );
        }

        return [
            'team_id'     => $team_id,
            'season'      => $season,
            'match_date'  => $match_date,
            'is_home'     => $is_home ? 1 : 0,
            'opponent'    => sanitize_text_field( $opponent ),
            'score_home'  => $score_home,
            'score_away'  => $score_away,
            'status'      => $status,
            'external_id' => $external_id,
            'round'       => '',
        ];
    }

    /**
     * Try to find match data embedded in <script> tags (window.__NUXT__, __INITIAL_STATE__, etc.)
     *
     * @return array<int,array<string,mixed>>
     */
    private static function parse_embedded_json( DOMXPath $xpath, int $team_id, string $season ): array {
        $matches = [];
        $scripts = $xpath->query( '//script[not(@src)]' );
        if ( ! $scripts ) {
            return $matches;
        }

        foreach ( $scripts as $script ) {
            $content = $script->textContent;
            // Look for patterns like window.__X__ = {...} or JSON arrays/objects
            if ( preg_match( '/\[[\s\S]{50,}\]/', $content, $m ) ) {
                $data = json_decode( $m[0], true );
                if ( is_array( $data ) ) {
                    foreach ( $data as $item ) {
                        if ( ! is_array( $item ) ) {
                            continue;
                        }
                        $norm = self::normalise_api_item( $item, $team_id, $season, '' );
                        if ( $norm ) {
                            $matches[] = $norm;
                        }
                    }
                    if ( $matches ) {
                        return $matches;
                    }
                }
            }
        }
        return $matches;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Detect whether a team name refers to HC Junior Mělník.
     */
    private static function name_is_ours( string $name ): bool {
        $lower = mb_strtolower( $name );
        return strpos( $lower, 'mělník' ) !== false
            || strpos( $lower, 'melnik' ) !== false
            || strpos( $lower, 'junior' ) !== false;
    }

    /**
     * Detect an SPA shell: HTML but essentially no text content.
     */
    private static function looks_like_spa( string $html ): bool {
        $text = strip_tags( $html );
        $text = trim( preg_replace( '/\s+/', ' ', $text ) );
        // Under 200 chars of visible text typically means an SPA skeleton
        return strlen( $text ) < 200;
    }

    /**
     * Parse a Czech-formatted date string into a Unix timestamp.
     *
     * @param string $date_str e.g. "15.10.2025" or "15.10.2025 18:00"
     * @return int|null
     */
    private static function parse_czech_date( string $date_str ): ?int {
        $date_str = trim( $date_str );
        if ( preg_match( '/(\d{1,2})\.(\d{1,2})\.(\d{4})\s*(\d{1,2}:\d{2})?/', $date_str, $m ) ) {
            $iso = sprintf( '%04d-%02d-%02d', $m[3], $m[2], $m[1] );
            $iso .= ! empty( $m[4] ) ? ' ' . $m[4] . ':00' : ' 00:00:00';
            $ts  = strtotime( $iso );
            return $ts ? (int) $ts : null;
        }
        return null;
    }

    /**
     * Wrapper around wp_remote_get with default headers.
     *
     * @param string               $url
     * @param array<string,string> $extra_headers
     * @return WP_Error|array<string,mixed>
     */
    private static function remote_get( string $url, array $extra_headers = [] ) {
        return wp_remote_get( $url, [
            'timeout'    => 20,
            'user-agent' => 'Mozilla/5.0 (compatible; HCJM-Roster/1.0; +https://hcjuniormelnik.cz)',
            'headers'    => array_merge(
                [ 'Accept' => 'text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8' ],
                $extra_headers
            ),
        ] );
    }
}

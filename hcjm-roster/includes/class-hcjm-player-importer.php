<?php
/**
 * Player importer: scrapes hráče from hcjuniormelnik.cz team pages.
 *
 * The import URL is stored per-team (_hcjm_team_import_url) and the scrape
 * runs server-side via wp_remote_get so the WordPress server's network is
 * used (not the local dev environment).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Player_Importer {

    /**
     * Import players for a team from its configured import URL.
     *
     * @param int    $team_id
     * @param string $season
     * @return array{imported:int,skipped:int,errors:string[]}
     */
    public static function import_team( int $team_id, string $season ): array {
        $url = get_post_meta( $team_id, '_hcjm_team_import_url', true );

        if ( ! $url ) {
            return [
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => [ __( 'Tým nemá nastavenou URL pro import hráčů.', HCJM_TEXT_DOMAIN ) ],
            ];
        }

        $response = wp_remote_get( esc_url_raw( $url ), [
            'timeout'    => 20,
            'user-agent' => 'Mozilla/5.0 (compatible; HCJM-Roster/1.0; +https://hcjuniormelnik.cz)',
        ] );

        if ( is_wp_error( $response ) ) {
            return [ 'imported' => 0, 'skipped' => 0, 'errors' => [ $response->get_error_message() ] ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return [
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => [ sprintf( __( 'HTTP chyba %d při načítání %s', HCJM_TEXT_DOMAIN ), $code, $url ) ],
            ];
        }

        $html    = wp_remote_retrieve_body( $response );
        $players = self::parse_players( $html );

        if ( empty( $players ) ) {
            return [
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => [ __( 'Na stránce nebyli nalezeni žádní hráči. Zkontroluj URL nebo kontaktuj správce pluginu.', HCJM_TEXT_DOMAIN ) ],
            ];
        }

        $has_jersey = HCJM_Teams::has_jersey_numbers( $team_id );
        $imported   = 0;
        $skipped    = 0;
        $errors     = [];

        foreach ( $players as $p ) {
            $first_name = $p['first_name'];
            $last_name  = $p['last_name'];

            if ( ! $first_name || ! $last_name ) {
                $skipped++;
                continue;
            }

            // Check for existing player with the same name in this team+season
            $existing = self::find_existing( $team_id, $season, $first_name, $last_name );

            if ( $existing ) {
                // Update existing rather than duplicate
                $player_id = $existing->ID;
                wp_update_post( [
                    'ID'         => $player_id,
                    'post_title' => $last_name . ' ' . $first_name,
                ] );
            } else {
                $player_id = wp_insert_post( [
                    'post_type'   => 'hcjm_player',
                    'post_title'  => $last_name . ' ' . $first_name,
                    'post_status' => 'publish',
                ] );
            }

            if ( ! $player_id || is_wp_error( $player_id ) ) {
                $errors[] = sprintf( __( 'Nepodařilo se uložit hráče: %s %s', HCJM_TEXT_DOMAIN ), $first_name, $last_name );
                continue;
            }

            HCJM_Players::save_meta( (int) $player_id, [
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'birth_year' => $p['birth_year'],
                'position'   => $p['position'],
                'jersey'     => $has_jersey ? $p['jersey'] : '',
                'season'     => $season,
                'team_id'    => $team_id,
                'photo_id'   => $existing ? (int) get_post_meta( (int) $player_id, '_hcjm_photo_id', true ) : 0,
            ] );

            $imported++;
        }

        return [ 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors ];
    }

    /**
     * Parse player rows from the page HTML.
     *
     * Tries multiple strategies:
     *   1. Table with detectable header columns
     *   2. Heuristic row parsing (number + name + year pattern)
     *
     * @param string $html
     * @return array<int,array{first_name:string,last_name:string,birth_year:string,position:string,jersey:string}>
     */
    private static function parse_players( string $html ): array {
        $dom = new DOMDocument();
        libxml_use_internal_errors( true );
        $dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
        libxml_clear_errors();

        $xpath = new DOMXPath( $dom );

        // Strategy 1: table with header row
        $players = self::parse_table_with_headers( $xpath );
        if ( ! empty( $players ) ) {
            return $players;
        }

        // Strategy 2: heuristic table parsing
        return self::parse_heuristic( $xpath );
    }

    /**
     * @param DOMXPath $xpath
     * @return array<int,array<string,string>>
     */
    private static function parse_table_with_headers( DOMXPath $xpath ): array {
        $tables = $xpath->query( '//table' );
        if ( ! $tables ) {
            return [];
        }

        foreach ( $tables as $table ) {
            $header_row = $xpath->query( './/tr[1]', $table );
            if ( ! $header_row || $header_row->length === 0 ) {
                continue;
            }

            $headers = $xpath->query( './/th | .//td', $header_row->item( 0 ) );
            if ( ! $headers || $headers->length < 2 ) {
                continue;
            }

            $col_map = self::detect_columns( $headers );

            // Need at least a name column
            if ( ! isset( $col_map['name'] ) && ! isset( $col_map['firstname'] ) ) {
                continue;
            }

            $rows    = $xpath->query( './/tr', $table );
            $players = [];

            foreach ( $rows as $i => $row ) {
                if ( $i === 0 ) {
                    continue; // skip header
                }
                $cells = $xpath->query( './/td', $row );
                if ( ! $cells || $cells->length < 2 ) {
                    continue;
                }

                $p = self::extract_from_row( $cells, $col_map );
                if ( $p ) {
                    $players[] = $p;
                }
            }

            if ( ! empty( $players ) ) {
                return $players;
            }
        }

        return [];
    }

    /**
     * @param DOMNodeList $headers
     * @return array<string,int>  map of semantic key => cell index
     */
    private static function detect_columns( $headers ): array {
        $map  = [];
        $text = [];
        for ( $i = 0; $i < $headers->length; $i++ ) {
            $text[ $i ] = mb_strtolower( trim( $headers->item( $i )->textContent ) );
        }

        foreach ( $text as $i => $t ) {
            if ( preg_match( '/^(č|číslo|#|dresy|dres)/u', $t ) ) {
                $map['jersey'] = $i;
            } elseif ( preg_match( '/^(jméno|příjmení|hráč|jmeno|prijmeni)/u', $t ) ) {
                $map['name'] = $i;
            } elseif ( preg_match( '/^(křestní|krestni)/u', $t ) ) {
                $map['firstname'] = $i;
            } elseif ( preg_match( '/^(příjmení|prijmeni)/u', $t ) ) {
                $map['lastname'] = $i;
            } elseif ( preg_match( '/^(rok|nar|roč|birth)/u', $t ) ) {
                $map['birth_year'] = $i;
            } elseif ( preg_match( '/^(post|pozice|role|funkce)/u', $t ) ) {
                $map['position'] = $i;
            }
        }

        return $map;
    }

    /**
     * @param DOMNodeList      $cells
     * @param array<string,int> $col_map
     * @return array<string,string>|null
     */
    private static function extract_from_row( $cells, array $col_map ): ?array {
        $get = function( string $key ) use ( $cells, $col_map ): string {
            if ( ! isset( $col_map[ $key ] ) ) {
                return '';
            }
            $idx  = $col_map[ $key ];
            $cell = $cells->item( $idx );
            return $cell ? trim( $cell->textContent ) : '';
        };

        $jersey     = trim( $get( 'jersey' ) );
        $birth_year = trim( $get( 'birth_year' ) );
        $pos_raw    = trim( $get( 'position' ) );
        $position   = self::normalize_position( $pos_raw );

        // Name handling
        if ( isset( $col_map['name'] ) ) {
            $full = trim( $get( 'name' ) );
            [ $first, $last ] = self::split_name( $full );
        } else {
            $first = trim( $get( 'firstname' ) );
            $last  = trim( $get( 'lastname' ) );
        }

        if ( ! $first && ! $last ) {
            return null;
        }

        // Validate birth year
        if ( $birth_year && ! preg_match( '/^\d{4}$/', $birth_year ) ) {
            // Try extracting 4-digit year from text
            preg_match( '/\b(\d{4})\b/', $birth_year, $m );
            $birth_year = $m[1] ?? '';
        }

        return [
            'first_name' => $first,
            'last_name'  => $last,
            'birth_year' => $birth_year,
            'position'   => $position,
            'jersey'     => preg_replace( '/\D/', '', $jersey ),
        ];
    }

    /**
     * Fallback heuristic: scan all table rows for rows that look like player data.
     *
     * @param DOMXPath $xpath
     * @return array<int,array<string,string>>
     */
    private static function parse_heuristic( DOMXPath $xpath ): array {
        $rows    = $xpath->query( '//table//tr[td]' );
        $players = [];

        if ( ! $rows ) {
            return [];
        }

        foreach ( $rows as $row ) {
            $cells = $xpath->query( 'td', $row );
            if ( ! $cells || $cells->length < 2 ) {
                continue;
            }

            $texts = [];
            for ( $i = 0; $i < $cells->length; $i++ ) {
                $texts[] = trim( $cells->item( $i )->textContent );
            }

            $jersey     = '';
            $full_name  = '';
            $birth_year = '';
            $position   = '';

            foreach ( $texts as $t ) {
                if ( preg_match( '/^\d{1,2}$/', $t ) && ! $jersey ) {
                    $jersey = $t;
                } elseif ( preg_match( '/^(19|20)\d{2}$/', $t ) && ! $birth_year ) {
                    $birth_year = $t;
                } elseif ( preg_match( '/^(Brankář|Obránce|Útočník|Brankár|Obranec|Utocnik)/ui', $t ) ) {
                    $position = self::normalize_position( $t );
                } elseif ( preg_match( '/^[A-ZÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ][a-záčďéěíňóřšťúůýž]+\s+[A-ZÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ]/u', $t ) ) {
                    $full_name = $t;
                }
            }

            if ( ! $full_name ) {
                continue;
            }

            [ $first, $last ] = self::split_name( $full_name );

            $players[] = [
                'first_name' => $first,
                'last_name'  => $last,
                'birth_year' => $birth_year,
                'position'   => $position ?: 'utocnik',
                'jersey'     => $jersey,
            ];
        }

        return $players;
    }

    /**
     * Split "Jméno Příjmení" or "Příjmení Jméno" into [first, last].
     * Czech convention on most hockey sites is "Příjmení Jméno".
     *
     * @param string $full
     * @return array{string,string}
     */
    private static function split_name( string $full ): array {
        $parts = preg_split( '/\s+/', trim( $full ), 2 );
        if ( count( $parts ) === 2 ) {
            // Assume "Příjmení Jméno" (last first) as is common in Czech sports
            return [ $parts[1], $parts[0] ];
        }
        return [ $full, '' ];
    }

    /**
     * Normalize a Czech position string to our internal key.
     *
     * @param string $raw
     * @return string  brankár|obrance|utocnik
     */
    private static function normalize_position( string $raw ): string {
        $lower = mb_strtolower( trim( $raw ) );

        if ( strpos( $lower, 'brank' ) !== false ) {
            return 'brankár';
        }
        if ( strpos( $lower, 'obr' ) !== false ) {
            return 'obrance';
        }
        if ( strpos( $lower, 'útočník' ) !== false || strpos( $lower, 'utocnik' ) !== false || strpos( $lower, 'útocník' ) !== false ) {
            return 'utocnik';
        }

        return 'utocnik';
    }

    /**
     * Find an existing player CPT by name in a given team+season.
     *
     * @param int    $team_id
     * @param string $season
     * @param string $first_name
     * @param string $last_name
     * @return WP_Post|null
     */
    private static function find_existing( int $team_id, string $season, string $first_name, string $last_name ): ?WP_Post {
        $existing_players = HCJM_Players::get_for_team( $team_id, $season );

        foreach ( $existing_players as $p ) {
            $m = HCJM_Players::get_meta( $p->ID );
            if (
                mb_strtolower( $m['first_name'] ) === mb_strtolower( $first_name ) &&
                mb_strtolower( $m['last_name'] )  === mb_strtolower( $last_name )
            ) {
                return $p;
            }
        }

        return null;
    }
}

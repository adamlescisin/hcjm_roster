<?php
/**
 * Custom DB table CRUD helpers for hcjm_matches.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Database {

    private static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'hcjm_matches';
    }

    /**
     * Upsert a match row by external_id.
     *
     * @param array<string,mixed> $data
     * @return int|false Inserted/updated row ID or false on failure.
     */
    public static function upsert_match( array $data ) {
        global $wpdb;
        $table = self::table();

        $external_id = sanitize_text_field( $data['external_id'] ?? '' );

        if ( $external_id ) {
            $existing_id = $wpdb->get_var(
                $wpdb->prepare( "SELECT id FROM {$table} WHERE external_id = %s LIMIT 1", $external_id )
            );
        } else {
            $existing_id = null;
        }

        $row = [
            'team_id'     => absint( $data['team_id'] ?? 0 ),
            'season'      => sanitize_text_field( $data['season'] ?? '' ),
            'match_date'  => sanitize_text_field( $data['match_date'] ?? '' ),
            'is_home'     => isset( $data['is_home'] ) ? (int) $data['is_home'] : 1,
            'opponent'    => sanitize_text_field( $data['opponent'] ?? '' ),
            'score_home'  => isset( $data['score_home'] ) && $data['score_home'] !== '' ? absint( $data['score_home'] ) : null,
            'score_away'  => isset( $data['score_away'] ) && $data['score_away'] !== '' ? absint( $data['score_away'] ) : null,
            'status'      => sanitize_text_field( $data['status'] ?? 'planned' ),
            'external_id' => $external_id,
            'round'       => sanitize_text_field( $data['round'] ?? '' ),
        ];

        $formats = [ '%d', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s' ];

        if ( $existing_id ) {
            $wpdb->update( $table, $row, [ 'id' => (int) $existing_id ], $formats, [ '%d' ] );
            return (int) $existing_id;
        } else {
            $result = $wpdb->insert( $table, $row, $formats );
            return $result ? $wpdb->insert_id : false;
        }
    }

    /**
     * Get matches for a team, optionally filtered by season and status.
     *
     * @param int    $team_id
     * @param string $season
     * @param string $type    'upcoming'|'past'|'all'
     * @param int    $limit   0 = unlimited
     * @return array<int,object>
     */
    public static function get_matches( int $team_id, string $season = '', string $type = 'all', int $limit = 0 ): array {
        global $wpdb;
        $table = self::table();
        $now   = current_time( 'mysql' );

        $where   = [];
        $formats = [];

        if ( $team_id > 0 ) {
            $where[] = $wpdb->prepare( 'team_id = %d', $team_id );
        }

        if ( $season ) {
            $where[] = $wpdb->prepare( 'season = %s', $season );
        }

        if ( $type === 'upcoming' ) {
            $where[] = $wpdb->prepare( "(match_date >= %s OR status = 'planned')", $now );
            $order   = 'ASC';
        } elseif ( $type === 'past' ) {
            $where[] = $wpdb->prepare( "match_date < %s AND status = 'played'", $now );
            $order   = 'DESC';
        } else {
            $order = 'ASC';
        }

        $where_sql = $where ? ( ' WHERE ' . implode( ' AND ', $where ) ) : '';
        $sql = 'SELECT * FROM ' . $table . $where_sql . ' ORDER BY match_date ' . $order;

        if ( $limit > 0 ) {
            $sql .= $wpdb->prepare( ' LIMIT %d', $limit );
        }

        return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Get a single match by ID.
     *
     * @param int $id
     * @return object|null
     */
    public static function get_match( int $id ): ?object {
        global $wpdb;
        $table = self::table();
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
    }

    /**
     * Delete a match by ID.
     *
     * @param int $id
     * @return bool
     */
    public static function delete_match( int $id ): bool {
        global $wpdb;
        $table  = self::table();
        $result = $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
        return $result !== false;
    }

    /**
     * Delete all matches for a team+season before re-importing.
     *
     * @param int    $team_id
     * @param string $season
     * @return int Number of rows deleted.
     */
    public static function delete_matches_for_team_season( int $team_id, string $season ): int {
        global $wpdb;
        $table = self::table();
        return (int) $wpdb->delete(
            $table,
            [ 'team_id' => $team_id, 'season' => $season ],
            [ '%d', '%s' ]
        );
    }

    /**
     * Count matches stored for a team+season.
     *
     * @param int    $team_id
     * @param string $season
     * @return int
     */
    public static function count_matches( int $team_id, string $season ): int {
        global $wpdb;
        $table = self::table();
        return (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE team_id = %d AND season = %s", $team_id, $season )
        );
    }

    /**
     * Get all matches (admin view), optionally filtered.
     *
     * @param array<string,mixed> $args
     * @return array<int,object>
     */
    public static function get_all_matches( array $args = [] ): array {
        global $wpdb;
        $table  = self::table();
        $where  = [];
        $limit  = absint( $args['limit'] ?? 200 );
        $offset = absint( $args['offset'] ?? 0 );

        if ( ! empty( $args['team_id'] ) ) {
            $where[] = $wpdb->prepare( 'team_id = %d', (int) $args['team_id'] );
        }
        if ( ! empty( $args['season'] ) ) {
            $where[] = $wpdb->prepare( 'season = %s', $args['season'] );
        }
        if ( ! empty( $args['status'] ) ) {
            $where[] = $wpdb->prepare( 'status = %s', $args['status'] );
        }

        $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';
        $sql       = "SELECT * FROM {$table} {$where_sql} ORDER BY match_date ASC LIMIT %d OFFSET %d";

        return $wpdb->get_results( $wpdb->prepare( $sql, $limit, $offset ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }
}

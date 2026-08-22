<?php
/**
 * Plugin activator: creates DB tables and inserts default team data.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Activator {

    public static function activate(): void {
        self::create_tables();
        self::insert_default_teams();
        self::set_default_options();
        flush_rewrite_rules();
    }

    public static function run_db_upgrade(): void {
        self::create_tables();
    }

    private static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $table   = $wpdb->prefix . 'hcjm_matches';

        $sql = "CREATE TABLE {$table} (
            id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            team_id       BIGINT(20) UNSIGNED NOT NULL,
            season        VARCHAR(20)         NOT NULL DEFAULT '',
            match_date    DATETIME                     DEFAULT NULL,
            is_home       TINYINT(1)          NOT NULL DEFAULT 1,
            opponent      VARCHAR(200)        NOT NULL DEFAULT '',
            score_home    TINYINT(3) UNSIGNED          DEFAULT NULL,
            score_away    TINYINT(3) UNSIGNED          DEFAULT NULL,
            status        VARCHAR(20)         NOT NULL DEFAULT 'planned',
            external_id   VARCHAR(100)        NOT NULL DEFAULT '',
            round         VARCHAR(50)                  DEFAULT NULL,
            is_friendly   TINYINT(1)          NOT NULL DEFAULT 0,
            created_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY team_season (team_id, season),
            KEY match_date  (match_date),
            UNIQUE KEY external_id (external_id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        update_option( 'hcjm_db_version', '1.1.0' );
    }

    private static function insert_default_teams(): void {
        // Only insert if no teams exist yet
        $existing = get_posts( [
            'post_type'      => 'hcjm_team',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
        ] );

        if ( ! empty( $existing ) ) {
            return;
        }

        $teams = [
            [ 'title' => 'Muži A',         'slug' => 'muzi-a',         'jersey' => true  ],
            [ 'title' => 'Dorost',          'slug' => 'dorost',         'jersey' => true  ],
            [ 'title' => 'Starší žáci',     'slug' => 'starsi-zaci',    'jersey' => true  ],
            [ 'title' => 'Mladší žáci',     'slug' => 'mladsi-zaci',    'jersey' => true  ],
            [ 'title' => '4. třída',        'slug' => '4-trida',        'jersey' => false ],
            [ 'title' => '3. třída',        'slug' => '3-trida',        'jersey' => false ],
            [ 'title' => '2. třída',        'slug' => '2-trida',        'jersey' => false ],
            [ 'title' => 'Přípravka',       'slug' => 'pripravka',      'jersey' => false ],
        ];

        foreach ( $teams as $order => $team ) {
            $post_id = wp_insert_post( [
                'post_type'   => 'hcjm_team',
                'post_title'  => $team['title'],
                'post_name'   => $team['slug'],
                'post_status' => 'publish',
                'menu_order'  => $order,
            ] );

            if ( $post_id && ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, '_hcjm_team_slug',          $team['slug'] );
                update_post_meta( $post_id, '_hcjm_team_jersey_numbers', $team['jersey'] ? '1' : '0' );
                update_post_meta( $post_id, '_hcjm_team_external_id',   '' );
                update_post_meta( $post_id, '_hcjm_team_league_id',     '' );
            }
        }
    }

    private static function set_default_options(): void {
        if ( false === get_option( 'hcjm_current_season' ) ) {
            $year = (int) gmdate( 'Y' );
            $month = (int) gmdate( 'm' );
            // Season starts in September
            if ( $month >= 9 ) {
                update_option( 'hcjm_current_season', $year . '-' . ( $year + 1 ) );
            } else {
                update_option( 'hcjm_current_season', ( $year - 1 ) . '-' . $year );
            }
        }
        if ( false === get_option( 'hcjm_cron_interval' ) ) {
            update_option( 'hcjm_cron_interval', 'daily' );
        }
    }
}

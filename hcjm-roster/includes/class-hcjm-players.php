<?php
/**
 * Custom Post Type: hcjm_player — player registration and helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Players {

    public function register_post_type(): void {
        register_post_type( 'hcjm_player', [
            'labels' => [
                'name'          => __( 'Hráči', HCJM_TEXT_DOMAIN ),
                'singular_name' => __( 'Hráč', HCJM_TEXT_DOMAIN ),
                'add_new_item'  => __( 'Přidat hráče', HCJM_TEXT_DOMAIN ),
                'edit_item'     => __( 'Upravit hráče', HCJM_TEXT_DOMAIN ),
            ],
            'public'       => false,
            'show_ui'      => false,
            'show_in_menu' => false,
            'has_archive'  => false,
            'supports'     => [ 'title', 'custom-fields', 'thumbnail' ],
            'rewrite'      => false,
        ] );
    }

    /**
     * Get all players for a team + season, ordered by jersey number then last name.
     *
     * @param int    $team_id
     * @param string $season
     * @return WP_Post[]
     */
    public static function get_for_team( int $team_id, string $season ): array {
        $posts = get_posts( [
            'post_type'      => 'hcjm_player',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'   => '_hcjm_player_team_id',
                    'value' => $team_id,
                    'type'  => 'NUMERIC',
                ],
                [
                    'key'   => '_hcjm_player_season',
                    'value' => $season,
                ],
            ],
        ] );

        usort( $posts, static function ( WP_Post $a, WP_Post $b ): int {
            $num_a = (int) get_post_meta( $a->ID, '_hcjm_player_jersey', true );
            $num_b = (int) get_post_meta( $b->ID, '_hcjm_player_jersey', true );
            if ( $num_a !== $num_b ) {
                return $num_a <=> $num_b;
            }
            $last_a = (string) get_post_meta( $a->ID, '_hcjm_player_last_name', true );
            $last_b = (string) get_post_meta( $b->ID, '_hcjm_player_last_name', true );
            return strcmp( $last_a, $last_b );
        } );

        return $posts;
    }

    /**
     * Get player meta as a structured array.
     *
     * @param int $post_id
     * @return array<string,mixed>
     */
    public static function get_meta( int $post_id ): array {
        return [
            'first_name' => (string) get_post_meta( $post_id, '_hcjm_player_first_name', true ),
            'last_name'  => (string) get_post_meta( $post_id, '_hcjm_player_last_name',  true ),
            'birth_year' => (string) get_post_meta( $post_id, '_hcjm_player_birth_year', true ),
            'position'   => (string) get_post_meta( $post_id, '_hcjm_player_position',   true ),
            'jersey'     => (string) get_post_meta( $post_id, '_hcjm_player_jersey',      true ),
            'photo_id'   => (int)    get_post_meta( $post_id, '_hcjm_player_photo_id',    true ),
            'team_id'    => (int)    get_post_meta( $post_id, '_hcjm_player_team_id',     true ),
            'season'     => (string) get_post_meta( $post_id, '_hcjm_player_season',      true ),
        ];
    }

    /**
     * Save player meta from $_POST data.
     *
     * @param int                 $post_id
     * @param array<string,mixed> $data
     */
    public static function save_meta( int $post_id, array $data ): void {
        update_post_meta( $post_id, '_hcjm_player_first_name', sanitize_text_field( $data['first_name'] ?? '' ) );
        update_post_meta( $post_id, '_hcjm_player_last_name',  sanitize_text_field( $data['last_name']  ?? '' ) );
        update_post_meta( $post_id, '_hcjm_player_birth_year', sanitize_text_field( $data['birth_year'] ?? '' ) );
        update_post_meta( $post_id, '_hcjm_player_position',   sanitize_text_field( $data['position']   ?? '' ) );
        update_post_meta( $post_id, '_hcjm_player_jersey',     sanitize_text_field( $data['jersey']     ?? '' ) );
        update_post_meta( $post_id, '_hcjm_player_photo_id',   absint( $data['photo_id'] ?? 0 ) );
        update_post_meta( $post_id, '_hcjm_player_team_id',    absint( $data['team_id']  ?? 0 ) );
        update_post_meta( $post_id, '_hcjm_player_season',     sanitize_text_field( $data['season']     ?? '' ) );
    }

    /**
     * Valid positions.
     *
     * @return array<string,string>
     */
    public static function positions(): array {
        return [
            'brankár'  => __( 'Brankář', HCJM_TEXT_DOMAIN ),
            'obrance'  => __( 'Obránce', HCJM_TEXT_DOMAIN ),
            'utocnik'  => __( 'Útočník', HCJM_TEXT_DOMAIN ),
        ];
    }
}

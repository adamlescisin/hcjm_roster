<?php
/**
 * Custom Post Type: hcjm_staff — coaching/support staff registration and helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Staff {

    public function register_post_type(): void {
        register_post_type( 'hcjm_staff', [
            'labels' => [
                'name'          => __( 'Realizační tým', HCJM_TEXT_DOMAIN ),
                'singular_name' => __( 'Člen realizačního týmu', HCJM_TEXT_DOMAIN ),
                'add_new_item'  => __( 'Přidat člena', HCJM_TEXT_DOMAIN ),
                'edit_item'     => __( 'Upravit člena', HCJM_TEXT_DOMAIN ),
            ],
            'public'       => false,
            'show_ui'      => false,
            'show_in_menu' => false,
            'has_archive'  => false,
            'supports'     => [ 'title', 'custom-fields' ],
            'rewrite'      => false,
        ] );
    }

    /**
     * Get all staff members for a team + season, ordered by menu_order.
     *
     * @param int    $team_id
     * @param string $season
     * @return WP_Post[]
     */
    public static function get_for_team( int $team_id, string $season ): array {
        return get_posts( [
            'post_type'      => 'hcjm_staff',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'   => '_hcjm_staff_team_id',
                    'value' => $team_id,
                    'type'  => 'NUMERIC',
                ],
                [
                    'key'   => '_hcjm_staff_season',
                    'value' => $season,
                ],
            ],
        ] );
    }

    /**
     * Get staff meta as a structured array.
     *
     * @param int $post_id
     * @return array<string,mixed>
     */
    public static function get_meta( int $post_id ): array {
        return [
            'first_name' => (string) get_post_meta( $post_id, '_hcjm_staff_first_name', true ),
            'last_name'  => (string) get_post_meta( $post_id, '_hcjm_staff_last_name',  true ),
            'role'       => (string) get_post_meta( $post_id, '_hcjm_staff_role',        true ),
            'contact'    => (string) get_post_meta( $post_id, '_hcjm_staff_contact',     true ),
            'photo_id'   => (int)    get_post_meta( $post_id, '_hcjm_staff_photo_id',    true ),
            'team_id'    => (int)    get_post_meta( $post_id, '_hcjm_staff_team_id',     true ),
            'season'     => (string) get_post_meta( $post_id, '_hcjm_staff_season',      true ),
        ];
    }

    /**
     * Save staff meta from form data.
     *
     * @param int                 $post_id
     * @param array<string,mixed> $data
     */
    public static function save_meta( int $post_id, array $data ): void {
        update_post_meta( $post_id, '_hcjm_staff_first_name', sanitize_text_field( $data['first_name'] ?? '' ) );
        update_post_meta( $post_id, '_hcjm_staff_last_name',  sanitize_text_field( $data['last_name']  ?? '' ) );
        update_post_meta( $post_id, '_hcjm_staff_role',       sanitize_text_field( $data['role']       ?? '' ) );
        update_post_meta( $post_id, '_hcjm_staff_contact',    sanitize_text_field( $data['contact']    ?? '' ) );
        update_post_meta( $post_id, '_hcjm_staff_photo_id',   absint( $data['photo_id'] ?? 0 ) );
        update_post_meta( $post_id, '_hcjm_staff_team_id',    absint( $data['team_id']  ?? 0 ) );
        update_post_meta( $post_id, '_hcjm_staff_season',     sanitize_text_field( $data['season']     ?? '' ) );
    }

    /**
     * Predefined roles.
     *
     * @return string[]
     */
    public static function roles(): array {
        return [
            __( 'Hlavní trenér', HCJM_TEXT_DOMAIN ),
            __( 'Asistent trenéra', HCJM_TEXT_DOMAIN ),
            __( 'Vedoucí týmu', HCJM_TEXT_DOMAIN ),
            __( 'Fyzioterapeut', HCJM_TEXT_DOMAIN ),
            __( 'Lékař', HCJM_TEXT_DOMAIN ),
            __( 'Masér', HCJM_TEXT_DOMAIN ),
            __( 'Správce výstroje', HCJM_TEXT_DOMAIN ),
        ];
    }
}

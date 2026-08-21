<?php
/**
 * Custom Post Type: hcjm_team — team registration and helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Teams {

    public function register_post_type(): void {
        register_post_type( 'hcjm_team', [
            'labels' => [
                'name'          => __( 'Mužstva', HCJM_TEXT_DOMAIN ),
                'singular_name' => __( 'Mužstvo', HCJM_TEXT_DOMAIN ),
                'add_new_item'  => __( 'Přidat mužstvo', HCJM_TEXT_DOMAIN ),
                'edit_item'     => __( 'Upravit mužstvo', HCJM_TEXT_DOMAIN ),
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
     * Get all teams, ordered by menu_order.
     *
     * @return WP_Post[]
     */
    public static function get_all(): array {
        return get_posts( [
            'post_type'      => 'hcjm_team',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ] );
    }

    /**
     * Get a team post by its slug meta.
     *
     * @param string $slug
     * @return WP_Post|null
     */
    public static function get_by_slug( string $slug ): ?WP_Post {
        $posts = get_posts( [
            'post_type'      => 'hcjm_team',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'meta_key'       => '_hcjm_team_slug',
            'meta_value'     => sanitize_title( $slug ),
        ] );
        return $posts[0] ?? null;
    }

    /**
     * Get a team post by its ID.
     *
     * @param int $id
     * @return WP_Post|null
     */
    public static function get_by_id( int $id ): ?WP_Post {
        $post = get_post( $id );
        if ( $post && $post->post_type === 'hcjm_team' ) {
            return $post;
        }
        return null;
    }

    /**
     * Whether the team uses jersey numbers.
     *
     * @param int $team_id
     * @return bool
     */
    public static function has_jersey_numbers( int $team_id ): bool {
        return (bool) get_post_meta( $team_id, '_hcjm_team_jersey_numbers', true );
    }

    /**
     * Get external (ceskyhokej.cz) team ID.
     *
     * @param int $team_id
     * @return string
     */
    public static function get_external_id( int $team_id ): string {
        return (string) get_post_meta( $team_id, '_hcjm_team_external_id', true );
    }

    /**
     * Get league ID for scraping.
     *
     * @param int $team_id
     * @return string
     */
    public static function get_league_id( int $team_id ): string {
        return (string) get_post_meta( $team_id, '_hcjm_team_league_id', true );
    }
}

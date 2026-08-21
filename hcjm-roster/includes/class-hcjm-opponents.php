<?php
/**
 * Custom Post Type: hcjm_opponent — opposing team registry.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Opponents {

    public function register_post_type(): void {
        register_post_type( 'hcjm_opponent', [
            'labels' => [
                'name'          => __( 'Soupeři', HCJM_TEXT_DOMAIN ),
                'singular_name' => __( 'Soupeř', HCJM_TEXT_DOMAIN ),
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
     * Get all opponents ordered alphabetically.
     *
     * @return WP_Post[]
     */
    public static function get_all(): array {
        return get_posts( [
            'post_type'      => 'hcjm_opponent',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );
    }

    /**
     * Find an opponent post by exact name (case-insensitive title match).
     *
     * @param string $name
     * @return WP_Post|null
     */
    public static function get_by_name( string $name ): ?WP_Post {
        global $wpdb;
        $id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'hcjm_opponent' AND post_status = 'publish' AND LOWER(post_title) = LOWER(%s) LIMIT 1",
                $name
            )
        );
        return $id ? get_post( (int) $id ) : null;
    }

    /**
     * Get opponent post by ID.
     */
    public static function get_by_id( int $id ): ?WP_Post {
        $post = get_post( $id );
        return ( $post && $post->post_type === 'hcjm_opponent' ) ? $post : null;
    }

    /**
     * Get the logo attachment ID for an opponent.
     */
    public static function get_logo_id( int $id ): int {
        return (int) get_post_meta( $id, '_hcjm_opponent_logo_id', true );
    }

    /**
     * Get the logo URL for an opponent, or empty string if none.
     */
    public static function get_logo_url( int $id, string $size = 'thumbnail' ): string {
        $logo_id = self::get_logo_id( $id );
        if ( ! $logo_id ) {
            return '';
        }
        $url = wp_get_attachment_image_url( $logo_id, $size );
        return $url ?: '';
    }

    /**
     * Get a logo URL by opponent name string (for use in match listings).
     */
    public static function get_logo_url_by_name( string $name, string $size = 'thumbnail' ): string {
        $post = self::get_by_name( $name );
        return $post ? self::get_logo_url( $post->ID, $size ) : '';
    }

    /**
     * Insert opponent posts for every unique opponent name found in hcjm_matches
     * that doesn't already have a corresponding post.
     *
     * @return int Number of new opponents added.
     */
    public static function populate_from_matches(): int {
        global $wpdb;
        $table = $wpdb->prefix . 'hcjm_matches';
        $names = $wpdb->get_col(
            "SELECT DISTINCT opponent FROM {$table} WHERE opponent != '' ORDER BY opponent" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        );

        $added = 0;
        foreach ( $names as $name ) {
            $name = trim( (string) $name );
            if ( ! $name || self::get_by_name( $name ) ) {
                continue;
            }
            $result = wp_insert_post( [
                'post_type'   => 'hcjm_opponent',
                'post_title'  => $name,
                'post_status' => 'publish',
                'post_name'   => sanitize_title( $name ),
            ] );
            if ( $result && ! is_wp_error( $result ) ) {
                $added++;
            }
        }
        return $added;
    }
}

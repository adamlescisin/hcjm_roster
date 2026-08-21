<?php
/**
 * WP-Cron scheduling for automatic match synchronisation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Cron {

    /** Cron hook name */
    const HOOK = 'hcjm_sync_matches';

    public function schedule(): void {
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            $interval = get_option( 'hcjm_cron_interval', 'daily' );
            wp_schedule_event( time(), $interval, self::HOOK );
        }
    }

    /**
     * Called by WP-Cron to sync all teams.
     */
    public function run_sync(): void {
        $season  = HCJM_Matches::current_season();
        if ( ! $season ) {
            return;
        }

        $results = HCJM_Scraper::sync_all_teams( $season );
        $total   = array_sum( array_column( $results, 'imported' ) );

        update_option( 'hcjm_last_sync', [
            'time'     => current_time( 'mysql' ),
            'imported' => $total,
            'details'  => $results,
        ] );
    }

    /**
     * Reschedule cron when interval setting changes.
     *
     * @param string $new_interval
     */
    public static function reschedule( string $new_interval ): void {
        $timestamp = wp_next_scheduled( self::HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::HOOK );
        }
        wp_schedule_event( time(), $new_interval, self::HOOK );
    }
}

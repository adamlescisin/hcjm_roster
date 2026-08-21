<?php
/**
 * Plugin deactivator: cleans up cron jobs.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Deactivator {

    public static function deactivate(): void {
        $timestamp = wp_next_scheduled( 'hcjm_sync_matches' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'hcjm_sync_matches' );
        }
        flush_rewrite_rules();
    }
}

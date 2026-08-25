<?php
/**
 * Plugin Name:       HCJM Roster
 * Plugin URI:        https://hcjuniormělník.cz
 * Description:       Soupiska, personál a zápasy pro HC Junior Mělník
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            HC Junior Mělník
 * Author URI:        https://hcjuniormělník.cz
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       hcjm-roster
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'HCJM_VERSION', '2.3.8' );
define( 'HCJM_PLUGIN_FILE', __FILE__ );
define( 'HCJM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HCJM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HCJM_TEXT_DOMAIN', 'hcjm-roster' );

require_once HCJM_PLUGIN_DIR . 'includes/class-hcjm-loader.php';
require_once HCJM_PLUGIN_DIR . 'includes/class-hcjm-activator.php';
require_once HCJM_PLUGIN_DIR . 'includes/class-hcjm-deactivator.php';
require_once HCJM_PLUGIN_DIR . 'includes/class-hcjm-database.php';
require_once HCJM_PLUGIN_DIR . 'includes/class-hcjm-teams.php';
require_once HCJM_PLUGIN_DIR . 'includes/class-hcjm-opponents.php';
require_once HCJM_PLUGIN_DIR . 'includes/class-hcjm-players.php';
require_once HCJM_PLUGIN_DIR . 'includes/class-hcjm-staff.php';
require_once HCJM_PLUGIN_DIR . 'includes/class-hcjm-matches.php';
require_once HCJM_PLUGIN_DIR . 'includes/class-hcjm-scraper.php';
require_once HCJM_PLUGIN_DIR . 'includes/class-hcjm-player-importer.php';
require_once HCJM_PLUGIN_DIR . 'includes/class-hcjm-cron.php';
require_once HCJM_PLUGIN_DIR . 'includes/class-hcjm-styles.php';
require_once HCJM_PLUGIN_DIR . 'includes/class-hcjm-shortcodes.php';
require_once HCJM_PLUGIN_DIR . 'admin/class-hcjm-admin.php';
require_once HCJM_PLUGIN_DIR . 'public/class-hcjm-public.php';

register_activation_hook( __FILE__, array( 'HCJM_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'HCJM_Deactivator', 'deactivate' ) );

add_action( 'plugins_loaded', static function (): void {
    if ( version_compare( (string) get_option( 'hcjm_db_version', '0' ), '1.1.0', '<' ) ) {
        HCJM_Activator::run_db_upgrade();
    }
} );

/**
 * Main plugin initialisation.
 */
function hcjm_run(): void {
    $loader = new HCJM_Loader();

    // Load translations
    $loader->add_action( 'plugins_loaded', null, 'hcjm_load_textdomain' );

    // CPT registration
    $teams     = new HCJM_Teams();
    $players   = new HCJM_Players();
    $staff     = new HCJM_Staff();
    $opponents = new HCJM_Opponents();

    $loader->add_action( 'init', $teams,     'register_post_type' );
    $loader->add_action( 'init', $players,   'register_post_type' );
    $loader->add_action( 'init', $staff,     'register_post_type' );
    $loader->add_action( 'init', $opponents, 'register_post_type' );

    // Shortcodes
    $shortcodes = new HCJM_Shortcodes();
    $loader->add_action( 'init', $shortcodes, 'register' );

    // Cron
    $cron = new HCJM_Cron();
    $loader->add_action( 'init', $cron, 'schedule' );
    $loader->add_action( 'hcjm_sync_matches', $cron, 'run_sync' );

    // Admin
    if ( is_admin() ) {
        $admin = new HCJM_Admin();
        $loader->add_action( 'admin_menu',             $admin, 'add_menu_pages' );
        $loader->add_action( 'admin_init',             $admin, 'register_settings' );
        $loader->add_action( 'admin_enqueue_scripts',  $admin, 'enqueue_assets' );
        $loader->add_action( 'admin_post_hcjm_save_team',   $admin, 'handle_save_team' );
        $loader->add_action( 'admin_post_hcjm_save_player', $admin, 'handle_save_player' );
        $loader->add_action( 'admin_post_hcjm_delete_player', $admin, 'handle_delete_player' );
        $loader->add_action( 'admin_post_hcjm_save_staff',  $admin, 'handle_save_staff' );
        $loader->add_action( 'admin_post_hcjm_delete_staff', $admin, 'handle_delete_staff' );
        $loader->add_action( 'admin_post_hcjm_sync_matches',    $admin, 'handle_sync_matches' );
        $loader->add_action( 'admin_post_hcjm_import_players',  $admin, 'handle_import_players' );
        $loader->add_action( 'admin_post_hcjm_delete_match', $admin, 'handle_delete_match' );
        $loader->add_action( 'admin_post_hcjm_save_match',       $admin, 'handle_save_match' );
        $loader->add_action( 'admin_post_hcjm_save_opponent',    $admin, 'handle_save_opponent' );
        $loader->add_action( 'admin_post_hcjm_populate_opponents',$admin, 'handle_populate_opponents' );
        $loader->add_action( 'admin_post_hcjm_save_styles',        $admin, 'handle_save_styles' );
        $loader->add_action( 'admin_post_hcjm_reset_styles_group', $admin, 'handle_reset_styles_group' );
    }

    // Public assets
    $public = new HCJM_Public();
    $loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_assets', 99 );

    $loader->run();
}

function hcjm_load_textdomain(): void {
    load_plugin_textdomain(
        HCJM_TEXT_DOMAIN,
        false,
        dirname( plugin_basename( HCJM_PLUGIN_FILE ) ) . '/languages/'
    );
}

hcjm_run();

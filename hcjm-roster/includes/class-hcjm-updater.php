<?php
/**
 * GitHub-based auto-updater for the HCJM Roster plugin.
 *
 * Checks the latest GitHub release and surfaces it through the standard
 * WordPress Plugins → Updates screen. Requires the repo to have at least
 * one published release tagged as "v{version}" (e.g. v2.5.7).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HCJM_Updater {

	private const GITHUB_USER = 'adamlescisin';
	private const GITHUB_REPO = 'hcjm_roster';
	private const PLUGIN_DIR  = 'hcjm-roster';          // installed folder name
	private const CACHE_KEY   = 'hcjm_github_release';
	private const CACHE_TTL   = 12 * HOUR_IN_SECONDS;

	private string $plugin_slug; // e.g. hcjm-roster/hcjm-roster.php

	public function __construct( string $plugin_file ) {
		$this->plugin_slug = plugin_basename( $plugin_file );

		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'inject_update' ] );
		add_filter( 'plugins_api',                           [ $this, 'plugin_info'   ], 10, 3 );
		add_filter( 'upgrader_source_selection',             [ $this, 'fix_source_dir'], 10, 4 );
		add_filter( 'plugin_action_links_' . $this->plugin_slug, [ $this, 'action_links' ] );
		add_action( 'admin_init',                            [ $this, 'handle_check'  ] );
	}

	// -------------------------------------------------------------------------
	// GitHub release fetching (cached)
	// -------------------------------------------------------------------------

	private function get_release(): ?object {
		$cached = get_transient( self::CACHE_KEY );
		if ( $cached !== false ) {
			return $cached ?: null; // '' stored on failure → null
		}

		$url      = 'https://api.github.com/repos/' . self::GITHUB_USER . '/' . self::GITHUB_REPO . '/releases/latest';
		$response = wp_remote_get( $url, [
			'timeout' => 10,
			'headers' => [
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
			],
		] );

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_transient( self::CACHE_KEY, '', self::CACHE_TTL ); // cache failure too
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! $data || empty( $data->tag_name ) ) {
			set_transient( self::CACHE_KEY, '', self::CACHE_TTL );
			return null;
		}

		set_transient( self::CACHE_KEY, $data, self::CACHE_TTL );
		return $data;
	}

	private function release_version( object $release ): string {
		return ltrim( $release->tag_name, 'vV' );
	}

	private function download_url( object $release ): string {
		// Prefer the hcjm-roster.zip asset built by GitHub Actions (correct structure,
		// no redirects). Fall back to the GitHub archive URL if the asset isn't there.
		if ( ! empty( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if ( 'hcjm-roster.zip' === $asset->name && ! empty( $asset->browser_download_url ) ) {
					return $asset->browser_download_url;
				}
			}
		}
		// Fallback: raw archive (may have nested folder — only used for older releases).
		return 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO
			. '/archive/refs/tags/' . rawurlencode( $release->tag_name ) . '.zip';
	}

	// -------------------------------------------------------------------------
	// WP update transient injection
	// -------------------------------------------------------------------------

	public function inject_update( object $transient ): object {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Clear cache when WP forces a fresh check.
		if ( isset( $_GET['force-check'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			delete_transient( self::CACHE_KEY );
		}

		$release = $this->get_release();
		if ( ! $release ) {
			return $transient;
		}

		$latest = $this->release_version( $release );
		if ( version_compare( $latest, HCJM_VERSION, '>' ) ) {
			$transient->response[ $this->plugin_slug ] = (object) [
				'id'          => 'github/' . self::GITHUB_USER . '/' . self::GITHUB_REPO,
				'slug'        => self::PLUGIN_DIR,
				'plugin'      => $this->plugin_slug,
				'new_version' => $latest,
				'url'         => 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO,
				'package'     => $this->download_url( $release ),
				'icons'       => [],
				'banners'     => [],
				'tested'      => '',
				'requires_php'=> '7.4',
			];
		} else {
			// Tell WP the plugin is up to date so it removes stale notices.
			$transient->no_update[ $this->plugin_slug ] = (object) [
				'id'          => 'github/' . self::GITHUB_USER . '/' . self::GITHUB_REPO,
				'slug'        => self::PLUGIN_DIR,
				'plugin'      => $this->plugin_slug,
				'new_version' => $latest,
				'url'         => 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO,
				'package'     => '',
			];
		}

		return $transient;
	}

	// -------------------------------------------------------------------------
	// Plugin details popup (the "View version x.x.x details" modal)
	// -------------------------------------------------------------------------

	public function plugin_info( $result, string $action, object $args ) {
		if ( 'plugin_information' !== $action || ( $args->slug ?? '' ) !== self::PLUGIN_DIR ) {
			return $result;
		}

		$release = $this->get_release();
		if ( ! $release ) {
			return $result;
		}

		$changelog = ! empty( $release->body )
			? '<pre>' . esc_html( $release->body ) . '</pre>'
			: '<p>See <a href="' . esc_url( $release->html_url ) . '">GitHub release</a>.</p>';

		return (object) [
			'name'          => 'HCJM Roster',
			'slug'          => self::PLUGIN_DIR,
			'version'       => $this->release_version( $release ),
			'author'        => 'HC Junior Mělník',
			'homepage'      => 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO,
			'download_link' => $this->download_url( $release ),
			'requires'      => '6.0',
			'requires_php'  => '7.4',
			'last_updated'  => $release->published_at ?? '',
			'sections'      => [ 'changelog' => $changelog ],
		];
	}

	// -------------------------------------------------------------------------
	// "Check for updates" action link on the Plugins page
	// -------------------------------------------------------------------------

	public function action_links( array $links ): array {
		$check_url = wp_nonce_url(
			add_query_arg( 'hcjm_check_update', '1', admin_url( 'plugins.php' ) ),
			'hcjm_check_update'
		);
		$links[] = '<a href="' . esc_url( $check_url ) . '">' . esc_html__( 'Check for updates', 'hcjm-roster' ) . '</a>';
		return $links;
	}

	public function handle_check(): void {
		if (
			! isset( $_GET['hcjm_check_update'] ) ||
			! current_user_can( 'update_plugins' ) ||
			! check_admin_referer( 'hcjm_check_update' )
		) {
			return;
		}
		// Clear cached release info and WP's plugin update transient, then redirect
		// to the Updates page so WP re-checks immediately.
		delete_transient( self::CACHE_KEY );
		delete_site_transient( 'update_plugins' );
		wp_safe_redirect( admin_url( 'update-core.php' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Rename GitHub's auto-generated folder to match the installed directory
	// -------------------------------------------------------------------------

	public function fix_source_dir( string $source, string $remote_source, $upgrader, array $hook_extra = [] ): string {
		// Only act on our own plugin update.
		if ( ( $hook_extra['plugin'] ?? '' ) !== $this->plugin_slug ) {
			return $source;
		}

		global $wp_filesystem;

		$target = trailingslashit( dirname( $source ) ) . self::PLUGIN_DIR . '/';

		// Case A: Actions-built zip extracts directly to hcjm-roster/ — already correct.
		if ( trailingslashit( $source ) === $target ) {
			return $source;
		}

		// Case B: GitHub archive zip extracts to {repo}-{tag}/hcjm-roster/ (nested).
		$nested = trailingslashit( $source ) . self::PLUGIN_DIR . '/';
		if ( $wp_filesystem->exists( $nested ) ) {
			if ( $wp_filesystem->exists( $target ) ) {
				$wp_filesystem->delete( $target, true );
			}
			if ( $wp_filesystem->move( $nested, $target ) ) {
				$wp_filesystem->delete( $source, true );
				return $target;
			}
		}

		// Case C: Archive zip extracts to some other folder name at the root — rename it.
		if ( $wp_filesystem->exists( $target ) ) {
			$wp_filesystem->delete( $target, true );
		}
		if ( $wp_filesystem->move( $source, $target ) ) {
			return $target;
		}

		return $source;
	}
}

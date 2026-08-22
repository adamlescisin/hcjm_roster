<?php
/**
 * Admin panel: menus, settings, CRUD handlers for teams/players/staff/matches.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Admin {

    // -------------------------------------------------------------------------
    // Menu registration
    // -------------------------------------------------------------------------

    public function add_menu_pages(): void {
        add_menu_page(
            __( 'HCJM Roster', HCJM_TEXT_DOMAIN ),
            __( 'HCJM Roster', HCJM_TEXT_DOMAIN ),
            'manage_options',
            'hcjm-roster',
            [ $this, 'page_teams' ],
            'dashicons-groups',
            30
        );

        add_submenu_page( 'hcjm-roster', __( 'Mužstva', HCJM_TEXT_DOMAIN ),      __( 'Mužstva', HCJM_TEXT_DOMAIN ),      'manage_options', 'hcjm-roster',         [ $this, 'page_teams' ] );
        add_submenu_page( 'hcjm-roster', __( 'Zápasy', HCJM_TEXT_DOMAIN ),       __( 'Zápasy', HCJM_TEXT_DOMAIN ),       'manage_options', 'hcjm-matches',        [ $this, 'page_matches' ] );
        add_submenu_page( 'hcjm-roster', __( 'Soupeři', HCJM_TEXT_DOMAIN ),      __( 'Soupeři', HCJM_TEXT_DOMAIN ),      'manage_options', 'hcjm-opponents',      [ $this, 'page_opponents' ] );
        add_submenu_page( 'hcjm-roster', __( 'Vzhled', HCJM_TEXT_DOMAIN ),       __( 'Vzhled', HCJM_TEXT_DOMAIN ),       'manage_options', 'hcjm-styles',         [ $this, 'page_styles' ] );
        add_submenu_page( 'hcjm-roster', __( 'Nastavení', HCJM_TEXT_DOMAIN ),    __( 'Nastavení', HCJM_TEXT_DOMAIN ),    'manage_options', 'hcjm-settings',       [ $this, 'page_settings' ] );
    }

    // -------------------------------------------------------------------------
    // Assets
    // -------------------------------------------------------------------------

    public function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'hcjm' ) === false ) {
            return;
        }
        wp_enqueue_style( 'hcjm-admin', HCJM_PLUGIN_URL . 'admin/css/admin.css', [], HCJM_VERSION );
        wp_enqueue_script( 'hcjm-admin', HCJM_PLUGIN_URL . 'admin/js/admin.js', [ 'jquery' ], HCJM_VERSION, true );
        wp_enqueue_media();

        if ( strpos( $hook, 'hcjm-styles' ) !== false ) {
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'wp-color-picker' );
        }
    }

    // -------------------------------------------------------------------------
    // Settings
    // -------------------------------------------------------------------------

    public function register_settings(): void {
        register_setting( 'hcjm_settings_group', 'hcjm_current_season',      'sanitize_text_field' );
        register_setting( 'hcjm_settings_group', 'hcjm_cron_interval',       'sanitize_text_field' );
        register_setting( 'hcjm_settings_group', 'hcjm_placeholder_avatar',  'absint' );
    }

    // -------------------------------------------------------------------------
    // Page: Teams list
    // -------------------------------------------------------------------------

    public function page_teams(): void {
        $teams       = HCJM_Teams::get_all();
        $edit_id     = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
        $team_id_ctx = isset( $_GET['team'] ) ? absint( $_GET['team'] ) : 0;
        $section     = sanitize_key( $_GET['section'] ?? 'teams' );

        if ( $section === 'edit-team' && $edit_id ) {
            $this->render_team_edit( $edit_id );
            return;
        }
        if ( $section === 'players' && $team_id_ctx ) {
            $this->render_players_list( $team_id_ctx );
            return;
        }
        if ( $section === 'edit-player' && $team_id_ctx ) {
            $player_id = isset( $_GET['player'] ) ? absint( $_GET['player'] ) : 0;
            $this->render_player_edit( $team_id_ctx, $player_id );
            return;
        }
        if ( $section === 'staff' && $team_id_ctx ) {
            $this->render_staff_list( $team_id_ctx );
            return;
        }
        if ( $section === 'edit-staff' && $team_id_ctx ) {
            $staff_id = isset( $_GET['staff'] ) ? absint( $_GET['staff'] ) : 0;
            $this->render_staff_edit( $team_id_ctx, $staff_id );
            return;
        }

        // Default: teams list
        $current_season = HCJM_Matches::current_season();
        ?>
        <div class="wrap hcjm-wrap">
            <h1><?php esc_html_e( 'Mužstva HC Junior Mělník', HCJM_TEXT_DOMAIN ); ?></h1>
            <?php $this->show_notices(); ?>
            <table class="wp-list-table widefat fixed striped hcjm-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Název', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Slug', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Čísla dresů', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'ceskyhokej.cz ID', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Hráči', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Realizační tým', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Akce', HCJM_TEXT_DOMAIN ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $teams as $team ) :
                    $slug      = get_post_meta( $team->ID, '_hcjm_team_slug', true );
                    $jersey    = get_post_meta( $team->ID, '_hcjm_team_jersey_numbers', true );
                    $ext_id    = get_post_meta( $team->ID, '_hcjm_team_external_id', true );
                    $players_c = count( HCJM_Players::get_for_team( $team->ID, $current_season ) );
                    $staff_c   = count( HCJM_Staff::get_for_team( $team->ID, $current_season ) );
                ?>
                    <tr>
                        <td><strong><?php echo esc_html( $team->post_title ); ?></strong></td>
                        <td><code><?php echo esc_html( $slug ); ?></code></td>
                        <td><?php echo $jersey ? '✓' : '–'; ?></td>
                        <td><?php echo $ext_id ? esc_html( $ext_id ) : '<em>' . esc_html__( 'nenastaveno', HCJM_TEXT_DOMAIN ) . '</em>'; ?></td>
                        <td>
                            <a href="<?php echo esc_url( $this->admin_url( [ 'section' => 'players', 'team' => $team->ID ] ) ); ?>">
                                <?php echo esc_html( $players_c ); ?> <?php esc_html_e( 'hráčů', HCJM_TEXT_DOMAIN ); ?>
                            </a>
                        </td>
                        <td>
                            <a href="<?php echo esc_url( $this->admin_url( [ 'section' => 'staff', 'team' => $team->ID ] ) ); ?>">
                                <?php echo esc_html( $staff_c ); ?> <?php esc_html_e( 'členů', HCJM_TEXT_DOMAIN ); ?>
                            </a>
                        </td>
                        <td>
                            <a href="<?php echo esc_url( $this->admin_url( [ 'section' => 'edit-team', 'edit' => $team->ID ] ) ); ?>" class="button button-small">
                                <?php esc_html_e( 'Upravit', HCJM_TEXT_DOMAIN ); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Team edit
    // -------------------------------------------------------------------------

    private function render_team_edit( int $team_id ): void {
        $team = HCJM_Teams::get_by_id( $team_id );
        if ( ! $team ) {
            wp_die( esc_html__( 'Mužstvo nenalezeno.', HCJM_TEXT_DOMAIN ) );
        }
        $slug       = get_post_meta( $team_id, '_hcjm_team_slug', true );
        $jersey     = get_post_meta( $team_id, '_hcjm_team_jersey_numbers', true );
        $ext_id     = get_post_meta( $team_id, '_hcjm_team_external_id', true );
        $league_id  = get_post_meta( $team_id, '_hcjm_team_league_id', true );
        ?>
        <div class="wrap hcjm-wrap">
            <h1><?php echo esc_html( $team->post_title ); ?> &mdash; <?php esc_html_e( 'Nastavení mužstva', HCJM_TEXT_DOMAIN ); ?></h1>
            <a href="<?php echo esc_url( $this->admin_url() ); ?>" class="hcjm-back">&larr; <?php esc_html_e( 'Zpět na mužstva', HCJM_TEXT_DOMAIN ); ?></a>
            <?php $this->show_notices(); ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'hcjm_save_team_' . $team_id, 'hcjm_nonce' ); ?>
                <input type="hidden" name="action"  value="hcjm_save_team">
                <input type="hidden" name="team_id" value="<?php echo esc_attr( $team_id ); ?>">

                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Název mužstva', HCJM_TEXT_DOMAIN ); ?></th>
                        <td><input type="text" name="title" class="regular-text" value="<?php echo esc_attr( $team->post_title ); ?>" required></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Slug (URL klíč)', HCJM_TEXT_DOMAIN ); ?></th>
                        <td><input type="text" name="slug" class="regular-text" value="<?php echo esc_attr( $slug ); ?>" required>
                            <p class="description"><?php esc_html_e( 'Použij v shortcode: team="..."', HCJM_TEXT_DOMAIN ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Zobrazovat čísla dresů', HCJM_TEXT_DOMAIN ); ?></th>
                        <td><label><input type="checkbox" name="jersey_numbers" value="1" <?php checked( $jersey, '1' ); ?>>
                            <?php esc_html_e( 'Ano', HCJM_TEXT_DOMAIN ); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'ID týmu na ceskyhokej.cz', HCJM_TEXT_DOMAIN ); ?></th>
                        <td><input type="text" name="external_id" class="regular-text" value="<?php echo esc_attr( $ext_id ); ?>">
                            <p class="description"><?php esc_html_e( 'Např. 2140 (z URL: filter[team]=2140)', HCJM_TEXT_DOMAIN ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'ID ligy na ceskyhokej.cz', HCJM_TEXT_DOMAIN ); ?></th>
                        <td><input type="text" name="league_id" class="regular-text" value="<?php echo esc_attr( $league_id ); ?>">
                            <p class="description"><?php esc_html_e( 'Např. 208 (z URL: filter[league]=208)', HCJM_TEXT_DOMAIN ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'URL pro import hráčů', HCJM_TEXT_DOMAIN ); ?></th>
                        <td>
                            <?php $import_url = get_post_meta( $team_id, '_hcjm_team_import_url', true ); ?>
                            <input type="url" name="import_url" class="large-text" value="<?php echo esc_attr( $import_url ); ?>"
                                placeholder="https://hcjuniormelnik.cz/muzstvo/...">
                            <p class="description"><?php esc_html_e( 'URL stránky s hráči na hcjuniormelnik.cz. Import se provede ze záložky Hráči.', HCJM_TEXT_DOMAIN ); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Uložit', HCJM_TEXT_DOMAIN ) ); ?>
            </form>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Players list
    // -------------------------------------------------------------------------

    private function render_players_list( int $team_id ): void {
        $team = HCJM_Teams::get_by_id( $team_id );
        if ( ! $team ) {
            wp_die( esc_html__( 'Mužstvo nenalezeno.', HCJM_TEXT_DOMAIN ) );
        }
        $current_season = HCJM_Matches::current_season();
        $season         = isset( $_GET['season'] ) ? sanitize_text_field( $_GET['season'] ) : $current_season;
        $players        = HCJM_Players::get_for_team( $team_id, $season );
        $has_jersey     = HCJM_Teams::has_jersey_numbers( $team_id );
        $seasons        = HCJM_Matches::season_list();
        ?>
        <div class="wrap hcjm-wrap">
            <h1><?php echo esc_html( $team->post_title ); ?> &mdash; <?php esc_html_e( 'Hráči', HCJM_TEXT_DOMAIN ); ?></h1>
            <a href="<?php echo esc_url( $this->admin_url() ); ?>" class="hcjm-back">&larr; <?php esc_html_e( 'Zpět na mužstva', HCJM_TEXT_DOMAIN ); ?></a>
            <?php $this->show_notices(); ?>

            <form method="get" class="hcjm-season-filter">
                <input type="hidden" name="page"    value="hcjm-roster">
                <input type="hidden" name="section" value="players">
                <input type="hidden" name="team"    value="<?php echo esc_attr( $team_id ); ?>">
                <label><?php esc_html_e( 'Sezóna:', HCJM_TEXT_DOMAIN ); ?>
                    <select name="season" onchange="this.form.submit()">
                        <?php foreach ( $seasons as $s ) : ?>
                            <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $s, $season ); ?>><?php echo esc_html( $s ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>

            <div class="hcjm-players-toolbar">
                <a href="<?php echo esc_url( $this->admin_url( [ 'section' => 'edit-player', 'team' => $team_id, 'season' => $season ] ) ); ?>" class="button button-primary">
                    + <?php esc_html_e( 'Přidat hráče', HCJM_TEXT_DOMAIN ); ?>
                </a>
                <?php $import_url = get_post_meta( $team_id, '_hcjm_team_import_url', true ); ?>
                <?php if ( $import_url ) : ?>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
                    <?php wp_nonce_field( 'hcjm_import_players_' . $team_id, 'hcjm_nonce' ); ?>
                    <input type="hidden" name="action"   value="hcjm_import_players">
                    <input type="hidden" name="team_id"  value="<?php echo esc_attr( $team_id ); ?>">
                    <input type="hidden" name="season"   value="<?php echo esc_attr( $season ); ?>">
                    <button type="submit" class="button button-secondary">
                        &#8635; <?php esc_html_e( 'Importovat hráče z webu klubu', HCJM_TEXT_DOMAIN ); ?>
                    </button>
                </form>
                <?php else : ?>
                <span class="description" style="line-height:30px;margin-left:8px">
                    <?php
                    printf(
                        wp_kses(
                            __( '(<a href="%s">Nastav import URL</a> pro automatický import hráčů)', HCJM_TEXT_DOMAIN ),
                            [ 'a' => [ 'href' => [] ] ]
                        ),
                        esc_url( $this->admin_url( [ 'section' => 'edit-team', 'edit' => $team_id ] ) )
                    );
                    ?>
                </span>
                <?php endif; ?>
            </div>

            <table class="wp-list-table widefat fixed striped hcjm-table" style="margin-top:16px">
                <thead>
                    <tr>
                        <?php if ( $has_jersey ) : ?><th style="width:60px">#</th><?php endif; ?>
                        <th><?php esc_html_e( 'Foto', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Příjmení a jméno', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Post', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Ročník nar.', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Akce', HCJM_TEXT_DOMAIN ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $players ) ) : ?>
                    <tr><td colspan="<?php echo $has_jersey ? 6 : 5; ?>"><?php esc_html_e( 'Žádní hráči v této sezóně.', HCJM_TEXT_DOMAIN ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $players as $player ) :
                        $m = HCJM_Players::get_meta( $player->ID );
                        $positions = HCJM_Players::positions();
                    ?>
                        <tr>
                            <?php if ( $has_jersey ) : ?><td><strong><?php echo esc_html( $m['jersey'] ); ?></strong></td><?php endif; ?>
                            <td><?php if ( $m['photo_id'] ) : ?><img src="<?php echo esc_url( wp_get_attachment_thumb_url( $m['photo_id'] ) ); ?>" class="hcjm-thumb"><?php else : ?>&mdash;<?php endif; ?></td>
                            <td><?php echo esc_html( $m['last_name'] . ' ' . $m['first_name'] ); ?></td>
                            <td><?php echo esc_html( $positions[ $m['position'] ] ?? $m['position'] ); ?></td>
                            <td><?php echo esc_html( $m['birth_year'] ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( $this->admin_url( [ 'section' => 'edit-player', 'team' => $team_id, 'player' => $player->ID, 'season' => $season ] ) ); ?>" class="button button-small"><?php esc_html_e( 'Upravit', HCJM_TEXT_DOMAIN ); ?></a>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('<?php esc_attr_e( 'Opravdu smazat?', HCJM_TEXT_DOMAIN ); ?>')">
                                    <?php wp_nonce_field( 'hcjm_delete_player_' . $player->ID, 'hcjm_nonce' ); ?>
                                    <input type="hidden" name="action"    value="hcjm_delete_player">
                                    <input type="hidden" name="player_id" value="<?php echo esc_attr( $player->ID ); ?>">
                                    <input type="hidden" name="team_id"   value="<?php echo esc_attr( $team_id ); ?>">
                                    <input type="hidden" name="season"    value="<?php echo esc_attr( $season ); ?>">
                                    <button type="submit" class="button button-small button-link-delete"><?php esc_html_e( 'Smazat', HCJM_TEXT_DOMAIN ); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Player edit form
    // -------------------------------------------------------------------------

    private function render_player_edit( int $team_id, int $player_id ): void {
        $team = HCJM_Teams::get_by_id( $team_id );
        if ( ! $team ) {
            wp_die( esc_html__( 'Mužstvo nenalezeno.', HCJM_TEXT_DOMAIN ) );
        }

        $current_season = HCJM_Matches::current_season();
        $season         = isset( $_GET['season'] ) ? sanitize_text_field( $_GET['season'] ) : $current_season;
        $has_jersey     = HCJM_Teams::has_jersey_numbers( $team_id );
        $positions      = HCJM_Players::positions();
        $seasons        = HCJM_Matches::season_list();

        $m = [
            'first_name' => '',
            'last_name'  => '',
            'birth_year' => '',
            'position'   => 'utocnik',
            'jersey'     => '',
            'photo_id'   => 0,
            'season'     => $season,
        ];

        if ( $player_id ) {
            $m = HCJM_Players::get_meta( $player_id );
        }
        ?>
        <div class="wrap hcjm-wrap">
            <h1><?php echo $player_id ? esc_html__( 'Upravit hráče', HCJM_TEXT_DOMAIN ) : esc_html__( 'Přidat hráče', HCJM_TEXT_DOMAIN ); ?>
                &mdash; <?php echo esc_html( $team->post_title ); ?></h1>
            <a href="<?php echo esc_url( $this->admin_url( [ 'section' => 'players', 'team' => $team_id, 'season' => $season ] ) ); ?>" class="hcjm-back">&larr; <?php esc_html_e( 'Zpět na hráče', HCJM_TEXT_DOMAIN ); ?></a>
            <?php $this->show_notices(); ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'hcjm_save_player_' . $player_id, 'hcjm_nonce' ); ?>
                <input type="hidden" name="action"    value="hcjm_save_player">
                <input type="hidden" name="player_id" value="<?php echo esc_attr( $player_id ); ?>">
                <input type="hidden" name="team_id"   value="<?php echo esc_attr( $team_id ); ?>">
                <input type="hidden" name="photo_id"  id="hcjm_photo_id" value="<?php echo esc_attr( $m['photo_id'] ); ?>">

                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Sezóna', HCJM_TEXT_DOMAIN ); ?></th>
                        <td>
                            <select name="season">
                                <?php foreach ( $seasons as $s ) : ?>
                                    <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $s, $m['season'] ); ?>><?php echo esc_html( $s ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Jméno', HCJM_TEXT_DOMAIN ); ?></th>
                        <td><input type="text" name="first_name" class="regular-text" value="<?php echo esc_attr( $m['first_name'] ); ?>" required></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Příjmení', HCJM_TEXT_DOMAIN ); ?></th>
                        <td><input type="text" name="last_name" class="regular-text" value="<?php echo esc_attr( $m['last_name'] ); ?>" required></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Post', HCJM_TEXT_DOMAIN ); ?></th>
                        <td>
                            <select name="position">
                                <?php foreach ( $positions as $key => $label ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $m['position'] ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Ročník narození', HCJM_TEXT_DOMAIN ); ?></th>
                        <td><input type="number" name="birth_year" class="small-text" value="<?php echo esc_attr( $m['birth_year'] ); ?>" min="1980" max="2030"></td>
                    </tr>
                    <?php if ( $has_jersey ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Číslo dresu', HCJM_TEXT_DOMAIN ); ?></th>
                        <td><input type="number" name="jersey" class="small-text" value="<?php echo esc_attr( $m['jersey'] ); ?>" min="1" max="99"></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php esc_html_e( 'Foto', HCJM_TEXT_DOMAIN ); ?></th>
                        <td>
                            <div id="hcjm_photo_preview">
                                <?php if ( $m['photo_id'] ) : ?>
                                    <img src="<?php echo esc_url( wp_get_attachment_thumb_url( $m['photo_id'] ) ); ?>" class="hcjm-thumb-lg">
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button hcjm-upload-btn" data-target="hcjm_photo_id" data-preview="hcjm_photo_preview">
                                <?php esc_html_e( 'Vybrat fotku', HCJM_TEXT_DOMAIN ); ?>
                            </button>
                            <?php if ( $m['photo_id'] ) : ?>
                                <button type="button" class="button hcjm-remove-photo" data-target="hcjm_photo_id" data-preview="hcjm_photo_preview">
                                    <?php esc_html_e( 'Odebrat', HCJM_TEXT_DOMAIN ); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Uložit hráče', HCJM_TEXT_DOMAIN ) ); ?>
            </form>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Staff list
    // -------------------------------------------------------------------------

    private function render_staff_list( int $team_id ): void {
        $team = HCJM_Teams::get_by_id( $team_id );
        if ( ! $team ) {
            wp_die( esc_html__( 'Mužstvo nenalezeno.', HCJM_TEXT_DOMAIN ) );
        }
        $current_season = HCJM_Matches::current_season();
        $season         = isset( $_GET['season'] ) ? sanitize_text_field( $_GET['season'] ) : $current_season;
        $members        = HCJM_Staff::get_for_team( $team_id, $season );
        $seasons        = HCJM_Matches::season_list();
        ?>
        <div class="wrap hcjm-wrap">
            <h1><?php echo esc_html( $team->post_title ); ?> &mdash; <?php esc_html_e( 'Realizační tým', HCJM_TEXT_DOMAIN ); ?></h1>
            <a href="<?php echo esc_url( $this->admin_url() ); ?>" class="hcjm-back">&larr; <?php esc_html_e( 'Zpět na mužstva', HCJM_TEXT_DOMAIN ); ?></a>
            <?php $this->show_notices(); ?>

            <form method="get" class="hcjm-season-filter">
                <input type="hidden" name="page"    value="hcjm-roster">
                <input type="hidden" name="section" value="staff">
                <input type="hidden" name="team"    value="<?php echo esc_attr( $team_id ); ?>">
                <label><?php esc_html_e( 'Sezóna:', HCJM_TEXT_DOMAIN ); ?>
                    <select name="season" onchange="this.form.submit()">
                        <?php foreach ( $seasons as $s ) : ?>
                            <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $s, $season ); ?>><?php echo esc_html( $s ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>

            <a href="<?php echo esc_url( $this->admin_url( [ 'section' => 'edit-staff', 'team' => $team_id, 'season' => $season ] ) ); ?>" class="button button-primary hcjm-add-btn">
                + <?php esc_html_e( 'Přidat člena', HCJM_TEXT_DOMAIN ); ?>
            </a>

            <table class="wp-list-table widefat fixed striped hcjm-table" style="margin-top:16px">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Foto', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Jméno', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Role', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Kontakt', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Akce', HCJM_TEXT_DOMAIN ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $members ) ) : ?>
                    <tr><td colspan="5"><?php esc_html_e( 'Žádní členové v této sezóně.', HCJM_TEXT_DOMAIN ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $members as $member ) :
                        $m = HCJM_Staff::get_meta( $member->ID );
                    ?>
                        <tr>
                            <td><?php if ( $m['photo_id'] ) : ?><img src="<?php echo esc_url( wp_get_attachment_thumb_url( $m['photo_id'] ) ); ?>" class="hcjm-thumb"><?php else : ?>&mdash;<?php endif; ?></td>
                            <td><?php echo esc_html( $m['first_name'] . ' ' . $m['last_name'] ); ?></td>
                            <td><?php echo esc_html( $m['role'] ); ?></td>
                            <td><?php echo esc_html( $m['contact'] ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( $this->admin_url( [ 'section' => 'edit-staff', 'team' => $team_id, 'staff' => $member->ID, 'season' => $season ] ) ); ?>" class="button button-small"><?php esc_html_e( 'Upravit', HCJM_TEXT_DOMAIN ); ?></a>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('<?php esc_attr_e( 'Opravdu smazat?', HCJM_TEXT_DOMAIN ); ?>')">
                                    <?php wp_nonce_field( 'hcjm_delete_staff_' . $member->ID, 'hcjm_nonce' ); ?>
                                    <input type="hidden" name="action"   value="hcjm_delete_staff">
                                    <input type="hidden" name="staff_id" value="<?php echo esc_attr( $member->ID ); ?>">
                                    <input type="hidden" name="team_id"  value="<?php echo esc_attr( $team_id ); ?>">
                                    <input type="hidden" name="season"   value="<?php echo esc_attr( $season ); ?>">
                                    <button type="submit" class="button button-small button-link-delete"><?php esc_html_e( 'Smazat', HCJM_TEXT_DOMAIN ); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Staff edit form
    // -------------------------------------------------------------------------

    private function render_staff_edit( int $team_id, int $staff_id ): void {
        $team = HCJM_Teams::get_by_id( $team_id );
        if ( ! $team ) {
            wp_die( esc_html__( 'Mužstvo nenalezeno.', HCJM_TEXT_DOMAIN ) );
        }
        $current_season = HCJM_Matches::current_season();
        $season         = isset( $_GET['season'] ) ? sanitize_text_field( $_GET['season'] ) : $current_season;
        $roles          = HCJM_Staff::roles();
        $seasons        = HCJM_Matches::season_list();

        $m = [
            'first_name' => '',
            'last_name'  => '',
            'role'       => '',
            'contact'    => '',
            'photo_id'   => 0,
            'season'     => $season,
        ];
        if ( $staff_id ) {
            $m = HCJM_Staff::get_meta( $staff_id );
        }
        ?>
        <div class="wrap hcjm-wrap">
            <h1><?php echo $staff_id ? esc_html__( 'Upravit člena', HCJM_TEXT_DOMAIN ) : esc_html__( 'Přidat člena', HCJM_TEXT_DOMAIN ); ?>
                &mdash; <?php echo esc_html( $team->post_title ); ?></h1>
            <a href="<?php echo esc_url( $this->admin_url( [ 'section' => 'staff', 'team' => $team_id, 'season' => $season ] ) ); ?>" class="hcjm-back">&larr; <?php esc_html_e( 'Zpět na realizační tým', HCJM_TEXT_DOMAIN ); ?></a>
            <?php $this->show_notices(); ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'hcjm_save_staff_' . $staff_id, 'hcjm_nonce' ); ?>
                <input type="hidden" name="action"   value="hcjm_save_staff">
                <input type="hidden" name="staff_id" value="<?php echo esc_attr( $staff_id ); ?>">
                <input type="hidden" name="team_id"  value="<?php echo esc_attr( $team_id ); ?>">
                <input type="hidden" name="photo_id" id="hcjm_photo_id" value="<?php echo esc_attr( $m['photo_id'] ); ?>">

                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Sezóna', HCJM_TEXT_DOMAIN ); ?></th>
                        <td>
                            <select name="season">
                                <?php foreach ( $seasons as $s ) : ?>
                                    <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $s, $m['season'] ); ?>><?php echo esc_html( $s ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Jméno', HCJM_TEXT_DOMAIN ); ?></th>
                        <td><input type="text" name="first_name" class="regular-text" value="<?php echo esc_attr( $m['first_name'] ); ?>" required></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Příjmení', HCJM_TEXT_DOMAIN ); ?></th>
                        <td><input type="text" name="last_name" class="regular-text" value="<?php echo esc_attr( $m['last_name'] ); ?>" required></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Role', HCJM_TEXT_DOMAIN ); ?></th>
                        <td>
                            <select name="role">
                                <?php foreach ( $roles as $role ) : ?>
                                    <option value="<?php echo esc_attr( $role ); ?>" <?php selected( $role, $m['role'] ); ?>><?php echo esc_html( $role ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <br><input type="text" name="role_custom" class="regular-text" placeholder="<?php esc_attr_e( 'nebo vlastní role...', HCJM_TEXT_DOMAIN ); ?>" style="margin-top:6px" value="">
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Kontakt', HCJM_TEXT_DOMAIN ); ?></th>
                        <td><input type="text" name="contact" class="regular-text" value="<?php echo esc_attr( $m['contact'] ); ?>" placeholder="<?php esc_attr_e( 'e-mail nebo telefon', HCJM_TEXT_DOMAIN ); ?>"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Foto', HCJM_TEXT_DOMAIN ); ?></th>
                        <td>
                            <div id="hcjm_photo_preview">
                                <?php if ( $m['photo_id'] ) : ?>
                                    <img src="<?php echo esc_url( wp_get_attachment_thumb_url( $m['photo_id'] ) ); ?>" class="hcjm-thumb-lg">
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button hcjm-upload-btn" data-target="hcjm_photo_id" data-preview="hcjm_photo_preview">
                                <?php esc_html_e( 'Vybrat fotku', HCJM_TEXT_DOMAIN ); ?>
                            </button>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Uložit', HCJM_TEXT_DOMAIN ) ); ?>
            </form>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Page: Matches
    // -------------------------------------------------------------------------

    public function page_matches(): void {
        $teams          = HCJM_Teams::get_all();
        $current_season = HCJM_Matches::current_season();
        $season         = isset( $_GET['season'] ) ? sanitize_text_field( $_GET['season'] ) : $current_season;
        $seasons        = HCJM_Matches::season_list();
        $section        = sanitize_key( $_GET['section'] ?? 'matches' );

        if ( $section === 'edit-match' ) {
            $match_id = isset( $_GET['match'] ) ? absint( $_GET['match'] ) : 0;
            $this->render_match_edit( $match_id, $season );
            return;
        }

        $filter_team   = isset( $_GET['filter_team'] ) ? absint( $_GET['filter_team'] ) : 0;
        $filter_status = isset( $_GET['filter_status'] ) ? sanitize_key( $_GET['filter_status'] ) : '';
        if ( ! in_array( $filter_status, [ 'played', 'planned' ], true ) ) {
            $filter_status = '';
        }

        $args = [ 'season' => $season, 'limit' => 200 ];
        if ( $filter_team ) {
            $args['team_id'] = $filter_team;
        }
        if ( $filter_status ) {
            $args['status'] = $filter_status;
        }
        $matches = HCJM_Database::get_all_matches( $args );

        $last_sync = get_option( 'hcjm_last_sync', [] );
        ?>
        <div class="wrap hcjm-wrap">
            <h1><?php esc_html_e( 'Zápasy', HCJM_TEXT_DOMAIN ); ?></h1>
            <?php $this->show_notices(); ?>

            <div class="hcjm-matches-toolbar">
                <form method="get" style="display:inline-flex;gap:8px;align-items:center;flex-wrap:wrap">
                    <input type="hidden" name="page" value="hcjm-matches">
                    <label><?php esc_html_e( 'Sezóna:', HCJM_TEXT_DOMAIN ); ?>
                        <select name="season" onchange="this.form.submit()">
                            <?php foreach ( $seasons as $s ) : ?>
                                <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $s, $season ); ?>><?php echo esc_html( $s ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><?php esc_html_e( 'Kategorie:', HCJM_TEXT_DOMAIN ); ?>
                        <select name="filter_team" onchange="this.form.submit()">
                            <option value="0"><?php esc_html_e( 'Vše', HCJM_TEXT_DOMAIN ); ?></option>
                            <?php foreach ( $teams as $t ) : ?>
                                <option value="<?php echo esc_attr( $t->ID ); ?>" <?php selected( $t->ID, $filter_team ); ?>><?php echo esc_html( $t->post_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><?php esc_html_e( 'Status:', HCJM_TEXT_DOMAIN ); ?>
                        <select name="filter_status" onchange="this.form.submit()">
                            <option value=""><?php esc_html_e( 'Vše', HCJM_TEXT_DOMAIN ); ?></option>
                            <option value="planned" <?php selected( 'planned', $filter_status ); ?>><?php esc_html_e( 'Plánováno', HCJM_TEXT_DOMAIN ); ?></option>
                            <option value="played"  <?php selected( 'played',  $filter_status ); ?>><?php esc_html_e( 'Odehráno', HCJM_TEXT_DOMAIN ); ?></option>
                        </select>
                    </label>
                </form>

                <a href="<?php echo esc_url( admin_url( 'admin.php?page=hcjm-matches&section=edit-match&season=' . urlencode( $season ) ) ); ?>" class="button button-primary">
                    + <?php esc_html_e( 'Přidat zápas', HCJM_TEXT_DOMAIN ); ?>
                </a>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
                    <?php wp_nonce_field( 'hcjm_sync_matches', 'hcjm_nonce' ); ?>
                    <input type="hidden" name="action" value="hcjm_sync_matches">
                    <input type="hidden" name="season" value="<?php echo esc_attr( $season ); ?>">
                    <button type="submit" class="button button-secondary">&#8635; <?php esc_html_e( 'Synchronizovat ze ceskyhokej.cz', HCJM_TEXT_DOMAIN ); ?></button>
                </form>
            </div>

            <?php if ( $last_sync ) : ?>
                <p class="description">
                    <?php
                    printf(
                        esc_html__( 'Poslední synchronizace: %1$s — importováno %2$d zápasů', HCJM_TEXT_DOMAIN ),
                        esc_html( $last_sync['time'] ?? '' ),
                        (int) ( $last_sync['imported'] ?? 0 )
                    );
                    ?>
                </p>
            <?php endif; ?>

            <table class="wp-list-table widefat fixed striped hcjm-table" style="margin-top:16px">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Datum', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Mužstvo', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'D/V', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Soupeř', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Výsledek', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Status', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Akce', HCJM_TEXT_DOMAIN ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $matches ) ) : ?>
                    <tr><td colspan="7"><?php esc_html_e( 'Žádné zápasy.', HCJM_TEXT_DOMAIN ); ?></td></tr>
                <?php else : ?>
                    <?php
                    $team_map = [];
                    foreach ( $teams as $t ) {
                        $team_map[ $t->ID ] = $t->post_title;
                    }
                    foreach ( $matches as $match ) :
                    ?>
                        <tr>
                            <td><?php echo esc_html( HCJM_Matches::format_date( $match ) ); ?></td>
                            <td><?php echo esc_html( $team_map[ $match->team_id ] ?? '–' ); ?></td>
                            <td><?php echo $match->is_home ? '<span class="hcjm-badge hcjm-badge-home">D</span>' : '<span class="hcjm-badge hcjm-badge-away">V</span>'; ?></td>
                            <td><?php echo esc_html( $match->opponent ); ?></td>
                            <td><strong><?php echo HCJM_Matches::format_score( $match ); ?></strong></td>
                            <td>
                                <?php if ( $match->status === 'played' ) : ?>
                                    <span class="hcjm-badge hcjm-badge-played"><?php esc_html_e( 'Odehráno', HCJM_TEXT_DOMAIN ); ?></span>
                                <?php else : ?>
                                    <span class="hcjm-badge hcjm-badge-planned"><?php esc_html_e( 'Plánováno', HCJM_TEXT_DOMAIN ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=hcjm-matches&section=edit-match&match=' . $match->id . '&season=' . urlencode( $season ) ) ); ?>" class="button button-small"><?php esc_html_e( 'Upravit', HCJM_TEXT_DOMAIN ); ?></a>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('<?php esc_attr_e( 'Smazat zápas?', HCJM_TEXT_DOMAIN ); ?>')">
                                    <?php wp_nonce_field( 'hcjm_delete_match_' . $match->id, 'hcjm_nonce' ); ?>
                                    <input type="hidden" name="action"   value="hcjm_delete_match">
                                    <input type="hidden" name="match_id" value="<?php echo esc_attr( $match->id ); ?>">
                                    <input type="hidden" name="season"   value="<?php echo esc_attr( $season ); ?>">
                                    <button type="submit" class="button button-small button-link-delete"><?php esc_html_e( 'Smazat', HCJM_TEXT_DOMAIN ); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Page: Opponents
    // -------------------------------------------------------------------------

    public function page_opponents(): void {
        $section     = sanitize_key( $_GET['section'] ?? 'opponents' );
        $opponent_id = isset( $_GET['opponent'] ) ? absint( $_GET['opponent'] ) : 0;

        if ( $section === 'edit-opponent' ) {
            $this->render_opponent_edit( $opponent_id );
            return;
        }

        $opponents = HCJM_Opponents::get_all();
        ?>
        <div class="wrap hcjm-wrap">
            <h1><?php esc_html_e( 'Soupeři', HCJM_TEXT_DOMAIN ); ?></h1>
            <?php $this->show_notices(); ?>

            <div class="hcjm-players-toolbar" style="display:flex;gap:8px;align-items:center;margin-bottom:16px">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=hcjm-opponents&section=edit-opponent' ) ); ?>" class="button button-primary">
                    + <?php esc_html_e( 'Přidat soupeře', HCJM_TEXT_DOMAIN ); ?>
                </a>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
                    <?php wp_nonce_field( 'hcjm_populate_opponents', 'hcjm_nonce' ); ?>
                    <input type="hidden" name="action" value="hcjm_populate_opponents">
                    <button type="submit" class="button button-secondary">
                        &#8635; <?php esc_html_e( 'Doplnit ze zápasů', HCJM_TEXT_DOMAIN ); ?>
                    </button>
                </form>
                <span class="description" style="line-height:30px">
                    <?php esc_html_e( 'Přidá soupeře, kteří se vyskytují v importovaných zápasech a ještě nejsou v seznamu.', HCJM_TEXT_DOMAIN ); ?>
                </span>
            </div>

            <table class="wp-list-table widefat fixed striped hcjm-table">
                <thead>
                    <tr>
                        <th style="width:60px"><?php esc_html_e( 'Logo', HCJM_TEXT_DOMAIN ); ?></th>
                        <th><?php esc_html_e( 'Název soupeře', HCJM_TEXT_DOMAIN ); ?></th>
                        <th style="width:120px"><?php esc_html_e( 'Akce', HCJM_TEXT_DOMAIN ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( empty( $opponents ) ) : ?>
                    <tr><td colspan="3"><?php esc_html_e( 'Žádní soupeři. Klikni na „Doplnit ze zápasů" pro automatické přidání.', HCJM_TEXT_DOMAIN ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $opponents as $opp ) :
                        $logo_id  = HCJM_Opponents::get_logo_id( $opp->ID );
                        $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';
                    ?>
                        <tr>
                            <td>
                                <?php if ( $logo_url ) : ?>
                                    <img src="<?php echo esc_url( $logo_url ); ?>" style="width:40px;height:40px;object-fit:contain;border-radius:4px;border:1px solid #ddd">
                                <?php else : ?>
                                    <span style="display:inline-block;width:40px;height:40px;border:1px dashed #ccc;border-radius:4px;line-height:38px;text-align:center;color:#999;font-size:18px">?</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo esc_html( $opp->post_title ); ?></strong></td>
                            <td>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=hcjm-opponents&section=edit-opponent&opponent=' . $opp->ID ) ); ?>" class="button button-small">
                                    <?php esc_html_e( 'Upravit', HCJM_TEXT_DOMAIN ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function render_opponent_edit( int $opponent_id ): void {
        $opponent = $opponent_id ? HCJM_Opponents::get_by_id( $opponent_id ) : null;
        $name     = $opponent ? $opponent->post_title : '';
        $logo_id  = $opponent ? HCJM_Opponents::get_logo_id( $opponent_id ) : 0;
        ?>
        <div class="wrap hcjm-wrap">
            <h1><?php echo $opponent_id ? esc_html__( 'Upravit soupeře', HCJM_TEXT_DOMAIN ) : esc_html__( 'Přidat soupeře', HCJM_TEXT_DOMAIN ); ?></h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=hcjm-opponents' ) ); ?>" class="hcjm-back">&larr; <?php esc_html_e( 'Zpět na soupeře', HCJM_TEXT_DOMAIN ); ?></a>
            <?php $this->show_notices(); ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'hcjm_save_opponent_' . $opponent_id, 'hcjm_nonce' ); ?>
                <input type="hidden" name="action"      value="hcjm_save_opponent">
                <input type="hidden" name="opponent_id" value="<?php echo esc_attr( $opponent_id ); ?>">
                <input type="hidden" name="logo_id"     id="hcjm_logo_id" value="<?php echo esc_attr( $logo_id ); ?>">

                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Název soupeře', HCJM_TEXT_DOMAIN ); ?></th>
                        <td><input type="text" name="name" class="regular-text" value="<?php echo esc_attr( $name ); ?>" required></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Logo', HCJM_TEXT_DOMAIN ); ?></th>
                        <td>
                            <div id="hcjm_logo_preview" style="margin-bottom:8px">
                                <?php if ( $logo_id ) : ?>
                                    <img src="<?php echo esc_url( wp_get_attachment_image_url( $logo_id, 'thumbnail' ) ); ?>" style="width:80px;height:80px;object-fit:contain;border:1px solid #ddd;border-radius:6px">
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button hcjm-upload-btn" data-target="hcjm_logo_id" data-preview="hcjm_logo_preview">
                                <?php esc_html_e( 'Vybrat logo', HCJM_TEXT_DOMAIN ); ?>
                            </button>
                            <?php if ( $logo_id ) : ?>
                                <button type="button" class="button hcjm-remove-photo" data-target="hcjm_logo_id" data-preview="hcjm_logo_preview">
                                    <?php esc_html_e( 'Odebrat', HCJM_TEXT_DOMAIN ); ?>
                                </button>
                            <?php endif; ?>
                            <p class="description"><?php esc_html_e( 'Vyberte logo z mediální knihovny.', HCJM_TEXT_DOMAIN ); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Uložit', HCJM_TEXT_DOMAIN ) ); ?>
            </form>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Page: Styles (appearance customisation)
    // -------------------------------------------------------------------------

    public function page_styles(): void {
        $schema  = HCJM_Styles::schema();
        $saved   = HCJM_Styles::get_saved();
        $fonts   = HCJM_Styles::font_options();
        $preview = HCJM_Styles::generate_css( $saved );

        // Group labels for the tab-style navigation
        $group_ids = array_keys( $schema );
        $active_group = sanitize_key( $_GET['style_group'] ?? $group_ids[0] );
        if ( ! isset( $schema[ $active_group ] ) ) {
            $active_group = $group_ids[0];
        }
        ?>
        <div class="wrap hcjm-wrap">
            <h1><?php esc_html_e( 'Vzhled — úprava stylů', HCJM_TEXT_DOMAIN ); ?></h1>
            <?php $this->show_notices(); ?>

            <style>
            .hcjm-style-tabs { display:flex; gap:0; border-bottom:1px solid #c3c4c7; margin-bottom:24px; flex-wrap:wrap; }
            .hcjm-style-tab  { padding:8px 18px; font-size:13px; font-weight:500; color:#50575e; text-decoration:none; border:1px solid transparent; border-bottom:none; border-radius:3px 3px 0 0; margin-bottom:-1px; background:#f0f0f1; }
            .hcjm-style-tab:hover { color:#135e96; background:#fff; }
            .hcjm-style-tab.active { background:#fff; border-color:#c3c4c7; color:#1d2327; }
            .hcjm-style-field { display:grid; grid-template-columns:220px 1fr; gap:12px 24px; align-items:start; padding:14px 0; border-bottom:1px solid #f0f0f1; }
            .hcjm-style-field:last-child { border-bottom:none; }
            .hcjm-style-field label { font-weight:600; font-size:13px; padding-top:6px; }
            .hcjm-style-desc { font-size:12px; color:#646970; margin-top:4px; }
            .hcjm-range-wrap { display:flex; align-items:center; gap:10px; }
            .hcjm-range-wrap input[type=range] { width:200px; }
            .hcjm-range-val { font-weight:600; font-size:13px; min-width:48px; }
            .hcjm-style-preview-block { background:#f6f7f7; border:1px solid #c3c4c7; border-radius:4px; padding:14px 16px; margin-top:24px; }
            .hcjm-style-preview-block h3 { margin:0 0 8px; font-size:13px; color:#50575e; }
            .hcjm-style-preview-block code { display:block; font-size:11px; line-height:1.7; white-space:pre; overflow-x:auto; max-height:280px; background:transparent; border:none; padding:0; }
            .hcjm-color-swatch { display:inline-block; width:14px; height:14px; border-radius:3px; border:1px solid #ccc; vertical-align:middle; margin-right:4px; }
            </style>

            <?php /* Group tabs */ ?>
            <div class="hcjm-style-tabs">
                <?php foreach ( $schema as $gk => $group ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=hcjm-styles&style_group=' . $gk ) ); ?>"
                       class="hcjm-style-tab <?php echo $gk === $active_group ? 'active' : ''; ?>">
                        <?php echo esc_html( $group['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'hcjm_save_styles', 'hcjm_nonce' ); ?>
                <input type="hidden" name="action"       value="hcjm_save_styles">
                <input type="hidden" name="style_group"  value="<?php echo esc_attr( $active_group ); ?>">

                <?php foreach ( $schema[ $active_group ]['fields'] as $key => $field ) :
                    $value = $saved[ $key ] ?? $field['default'];
                ?>
                <div class="hcjm-style-field">
                    <div>
                        <label for="hcjm_style_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
                        <?php if ( ! empty( $field['desc'] ) ) : ?>
                            <p class="hcjm-style-desc"><?php echo esc_html( $field['desc'] ); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                    <?php if ( $field['type'] === 'color' ) : ?>
                        <input type="text"
                               id="hcjm_style_<?php echo esc_attr( $key ); ?>"
                               name="hcjm_styles[<?php echo esc_attr( $key ); ?>]"
                               value="<?php echo esc_attr( $value ); ?>"
                               class="hcjm-color-picker"
                               data-default-color="<?php echo esc_attr( $field['default'] ); ?>">

                    <?php elseif ( $field['type'] === 'range' ) : ?>
                        <div class="hcjm-range-wrap">
                            <input type="range"
                                   id="hcjm_style_<?php echo esc_attr( $key ); ?>"
                                   name="hcjm_styles[<?php echo esc_attr( $key ); ?>]"
                                   value="<?php echo esc_attr( $value ); ?>"
                                   min="<?php echo esc_attr( $field['min'] ); ?>"
                                   max="<?php echo esc_attr( $field['max'] ); ?>"
                                   oninput="document.getElementById('hcjm_rv_<?php echo esc_attr( $key ); ?>').textContent = this.value + '<?php echo esc_js( $field['unit'] ?? '' ); ?>'">
                            <span id="hcjm_rv_<?php echo esc_attr( $key ); ?>" class="hcjm-range-val"><?php echo esc_html( $value . ( $field['unit'] ?? '' ) ); ?></span>
                            <button type="button" class="button button-small"
                                onclick="var el=document.getElementById('hcjm_style_<?php echo esc_attr( $key ); ?>');el.value=<?php echo (int) $field['default']; ?>;document.getElementById('hcjm_rv_<?php echo esc_attr( $key ); ?>').textContent=<?php echo (int) $field['default']; ?>+'<?php echo esc_js( $field['unit'] ?? '' ); ?>'">
                                <?php esc_html_e( 'Reset', HCJM_TEXT_DOMAIN ); ?>
                            </button>
                        </div>

                    <?php elseif ( $field['type'] === 'font' ) : ?>
                        <select id="hcjm_style_<?php echo esc_attr( $key ); ?>"
                                name="hcjm_styles[<?php echo esc_attr( $key ); ?>]">
                            <?php foreach ( $fonts as $fk => $font ) : ?>
                                <option value="<?php echo esc_attr( $fk ); ?>"
                                    <?php selected( $fk, $value ); ?>
                                    style="font-family:<?php echo esc_attr( $font['stack'] ?: 'inherit' ); ?>">
                                    <?php echo esc_html( $font['label'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ( $field['type'] === 'textarea' ) : ?>
                        <textarea id="hcjm_style_<?php echo esc_attr( $key ); ?>"
                                  name="hcjm_styles[<?php echo esc_attr( $key ); ?>]"
                                  rows="12"
                                  class="large-text code"
                                  spellcheck="false"><?php echo esc_textarea( (string) $value ); ?></textarea>

                    <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <p style="margin-top:20px">
                    <?php submit_button( __( 'Uložit skupinu', HCJM_TEXT_DOMAIN ), 'primary', 'submit', false ); ?>
                </p>
            </form>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                  onsubmit="return confirm('<?php esc_attr_e( 'Resetovat celou skupinu na výchozí hodnoty?', HCJM_TEXT_DOMAIN ); ?>')">
                <?php wp_nonce_field( 'hcjm_reset_styles_group', 'hcjm_nonce' ); ?>
                <input type="hidden" name="action"      value="hcjm_reset_styles_group">
                <input type="hidden" name="style_group" value="<?php echo esc_attr( $active_group ); ?>">
                <button type="submit" class="button"><?php esc_html_e( 'Resetovat skupinu', HCJM_TEXT_DOMAIN ); ?></button>
            </form>

            <?php if ( $preview ) : ?>
            <div class="hcjm-style-preview-block">
                <h3><?php esc_html_e( 'Generované CSS (náhled)', HCJM_TEXT_DOMAIN ); ?></h3>
                <code><?php echo esc_html( $preview ); ?></code>
            </div>
            <?php endif; ?>
        </div>

        <script>
        jQuery(function($){
            $('.hcjm-color-picker').wpColorPicker();
        });
        </script>
        <?php
    }

    // -------------------------------------------------------------------------
    // Page: Settings
    // -------------------------------------------------------------------------

    public function page_settings(): void {
        ?>
        <div class="wrap hcjm-wrap">
            <h1><?php esc_html_e( 'Nastavení HCJM Roster', HCJM_TEXT_DOMAIN ); ?></h1>
            <?php $this->show_notices(); ?>
            <form method="post" action="options.php">
                <?php settings_fields( 'hcjm_settings_group' ); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Aktuální sezóna', HCJM_TEXT_DOMAIN ); ?></th>
                        <td>
                            <input type="text" name="hcjm_current_season" class="regular-text"
                                value="<?php echo esc_attr( get_option( 'hcjm_current_season' ) ); ?>"
                                pattern="\d{4}-\d{4}" placeholder="2025-2026" required>
                            <p class="description"><?php esc_html_e( 'Formát: 2025-2026', HCJM_TEXT_DOMAIN ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Interval synchronizace zápasů', HCJM_TEXT_DOMAIN ); ?></th>
                        <td>
                            <select name="hcjm_cron_interval">
                                <option value="hourly"  <?php selected( get_option( 'hcjm_cron_interval' ), 'hourly' ); ?>><?php esc_html_e( 'Každou hodinu', HCJM_TEXT_DOMAIN ); ?></option>
                                <option value="twicedaily" <?php selected( get_option( 'hcjm_cron_interval' ), 'twicedaily' ); ?>><?php esc_html_e( 'Dvakrát denně', HCJM_TEXT_DOMAIN ); ?></option>
                                <option value="daily"   <?php selected( get_option( 'hcjm_cron_interval' ), 'daily' ); ?>><?php esc_html_e( 'Jednou denně', HCJM_TEXT_DOMAIN ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Výchozí fotka hráče', HCJM_TEXT_DOMAIN ); ?></th>
                        <td>
                            <?php
                            $placeholder_id  = (int) get_option( 'hcjm_placeholder_avatar', 0 );
                            $placeholder_url = $placeholder_id ? wp_get_attachment_image_url( $placeholder_id, 'thumbnail' ) : '';
                            ?>
                            <input type="hidden" name="hcjm_placeholder_avatar" id="hcjm_placeholder_avatar" value="<?php echo esc_attr( $placeholder_id ); ?>">
                            <div id="hcjm_placeholder_preview" style="margin-bottom:8px">
                                <?php if ( $placeholder_url ) : ?>
                                    <img src="<?php echo esc_url( $placeholder_url ); ?>" style="width:80px;height:80px;object-fit:cover;border-radius:6px;border:1px solid #ddd">
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button hcjm-upload-btn"
                                data-target="hcjm_placeholder_avatar"
                                data-preview="hcjm_placeholder_preview">
                                <?php esc_html_e( 'Vybrat obrázek', HCJM_TEXT_DOMAIN ); ?>
                            </button>
                            <?php if ( $placeholder_id ) : ?>
                                <button type="button" class="button hcjm-remove-photo"
                                    data-target="hcjm_placeholder_avatar"
                                    data-preview="hcjm_placeholder_preview">
                                    <?php esc_html_e( 'Odebrat', HCJM_TEXT_DOMAIN ); ?>
                                </button>
                            <?php endif; ?>
                            <p class="description"><?php esc_html_e( 'Zobrazí se místo iniciál tam, kde hráč nemá vlastní fotku.', HCJM_TEXT_DOMAIN ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Uložit nastavení', HCJM_TEXT_DOMAIN ) ); ?>
            </form>

            <hr>
            <h2><?php esc_html_e( 'Shortcodes — nápověda', HCJM_TEXT_DOMAIN ); ?></h2>
            <table class="widefat" style="max-width:800px">
                <thead><tr><th><?php esc_html_e( 'Shortcode', HCJM_TEXT_DOMAIN ); ?></th><th><?php esc_html_e( 'Popis', HCJM_TEXT_DOMAIN ); ?></th></tr></thead>
                <tbody>
                    <tr><td><code>[hcjm_roster team="muzi-a"]</code></td><td><?php esc_html_e( 'Soupisky hráčů mužstva', HCJM_TEXT_DOMAIN ); ?></td></tr>
                    <tr><td><code>[hcjm_roster team="muzi-a" season="2024-2025"]</code></td><td><?php esc_html_e( 'Soupisky konkrétní sezóny', HCJM_TEXT_DOMAIN ); ?></td></tr>
                    <tr><td><code>[hcjm_staff team="muzi-a"]</code></td><td><?php esc_html_e( 'Realizační tým mužstva', HCJM_TEXT_DOMAIN ); ?></td></tr>
                    <tr><td><code>[hcjm_matches team="muzi-a" type="upcoming"]</code></td><td><?php esc_html_e( 'Nadcházející zápasy', HCJM_TEXT_DOMAIN ); ?></td></tr>
                    <tr><td><code>[hcjm_matches team="muzi-a" type="past" limit="5"]</code></td><td><?php esc_html_e( 'Poslední výsledky (limit)', HCJM_TEXT_DOMAIN ); ?></td></tr>
                    <tr><td><code>[hcjm_matches team="muzi-a" type="all"]</code></td><td><?php esc_html_e( 'Všechny zápasy', HCJM_TEXT_DOMAIN ); ?></td></tr>
                </tbody>
            </table>

            <?php
            $teams = HCJM_Teams::get_all();
            if ( $teams ) :
            ?>
            <h2 style="margin-top:24px"><?php esc_html_e( 'Slugy mužstev', HCJM_TEXT_DOMAIN ); ?></h2>
            <table class="widefat" style="max-width:400px">
                <thead><tr><th><?php esc_html_e( 'Mužstvo', HCJM_TEXT_DOMAIN ); ?></th><th><?php esc_html_e( 'Slug (team="…")', HCJM_TEXT_DOMAIN ); ?></th></tr></thead>
                <tbody>
                    <?php foreach ( $teams as $t ) : ?>
                        <tr>
                            <td><?php echo esc_html( $t->post_title ); ?></td>
                            <td><code><?php echo esc_html( get_post_meta( $t->ID, '_hcjm_team_slug', true ) ); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Form handlers
    // -------------------------------------------------------------------------

    public function handle_save_team(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nedostatečná oprávnění.', HCJM_TEXT_DOMAIN ) );
        }
        $team_id = absint( $_POST['team_id'] ?? 0 );
        check_admin_referer( 'hcjm_save_team_' . $team_id, 'hcjm_nonce' );

        wp_update_post( [
            'ID'         => $team_id,
            'post_title' => sanitize_text_field( $_POST['title'] ?? '' ),
            'post_name'  => sanitize_title( $_POST['slug'] ?? '' ),
        ] );

        update_post_meta( $team_id, '_hcjm_team_slug',          sanitize_title( $_POST['slug'] ?? '' ) );
        update_post_meta( $team_id, '_hcjm_team_jersey_numbers', isset( $_POST['jersey_numbers'] ) ? '1' : '0' );
        update_post_meta( $team_id, '_hcjm_team_external_id',   sanitize_text_field( $_POST['external_id'] ?? '' ) );
        update_post_meta( $team_id, '_hcjm_team_league_id',     sanitize_text_field( $_POST['league_id'] ?? '' ) );
        update_post_meta( $team_id, '_hcjm_team_import_url',    esc_url_raw( $_POST['import_url'] ?? '' ) );

        wp_safe_redirect( $this->admin_url( [], true ) . '&hcjm_notice=team_saved' );
        exit;
    }

    public function handle_save_player(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nedostatečná oprávnění.', HCJM_TEXT_DOMAIN ) );
        }
        $player_id = absint( $_POST['player_id'] ?? 0 );
        check_admin_referer( 'hcjm_save_player_' . $player_id, 'hcjm_nonce' );

        $team_id    = absint( $_POST['team_id'] ?? 0 );
        $first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
        $last_name  = sanitize_text_field( $_POST['last_name']  ?? '' );

        if ( ! $player_id ) {
            $player_id = wp_insert_post( [
                'post_type'   => 'hcjm_player',
                'post_title'  => $last_name . ' ' . $first_name,
                'post_status' => 'publish',
            ] );
        } else {
            wp_update_post( [
                'ID'         => $player_id,
                'post_title' => $last_name . ' ' . $first_name,
            ] );
        }

        if ( ! $player_id || is_wp_error( $player_id ) ) {
            wp_safe_redirect( $this->admin_url( [ 'section' => 'players', 'team' => $team_id ], true ) . '&hcjm_notice=error' );
            exit;
        }

        HCJM_Players::save_meta( (int) $player_id, $_POST );

        $season = sanitize_text_field( $_POST['season'] ?? HCJM_Matches::current_season() );
        wp_safe_redirect( $this->admin_url( [ 'section' => 'players', 'team' => $team_id, 'season' => $season ], true ) . '&hcjm_notice=player_saved' );
        exit;
    }

    public function handle_delete_player(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nedostatečná oprávnění.', HCJM_TEXT_DOMAIN ) );
        }
        $player_id = absint( $_POST['player_id'] ?? 0 );
        check_admin_referer( 'hcjm_delete_player_' . $player_id, 'hcjm_nonce' );

        wp_delete_post( $player_id, true );

        $team_id = absint( $_POST['team_id'] ?? 0 );
        $season  = sanitize_text_field( $_POST['season'] ?? '' );
        wp_safe_redirect( $this->admin_url( [ 'section' => 'players', 'team' => $team_id, 'season' => $season ], true ) . '&hcjm_notice=player_deleted' );
        exit;
    }

    public function handle_save_staff(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nedostatečná oprávnění.', HCJM_TEXT_DOMAIN ) );
        }
        $staff_id = absint( $_POST['staff_id'] ?? 0 );
        check_admin_referer( 'hcjm_save_staff_' . $staff_id, 'hcjm_nonce' );

        $team_id    = absint( $_POST['team_id'] ?? 0 );
        $first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
        $last_name  = sanitize_text_field( $_POST['last_name']  ?? '' );

        // Custom role overrides dropdown
        $role = sanitize_text_field( $_POST['role_custom'] ?? '' ) ?: sanitize_text_field( $_POST['role'] ?? '' );

        $data         = $_POST;
        $data['role'] = $role;

        if ( ! $staff_id ) {
            $staff_id = wp_insert_post( [
                'post_type'   => 'hcjm_staff',
                'post_title'  => $first_name . ' ' . $last_name,
                'post_status' => 'publish',
            ] );
        } else {
            wp_update_post( [
                'ID'         => $staff_id,
                'post_title' => $first_name . ' ' . $last_name,
            ] );
        }

        if ( ! $staff_id || is_wp_error( $staff_id ) ) {
            wp_safe_redirect( $this->admin_url( [ 'section' => 'staff', 'team' => $team_id ], true ) . '&hcjm_notice=error' );
            exit;
        }

        HCJM_Staff::save_meta( (int) $staff_id, $data );

        $season = sanitize_text_field( $_POST['season'] ?? HCJM_Matches::current_season() );
        wp_safe_redirect( $this->admin_url( [ 'section' => 'staff', 'team' => $team_id, 'season' => $season ], true ) . '&hcjm_notice=staff_saved' );
        exit;
    }

    public function handle_delete_staff(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nedostatečná oprávnění.', HCJM_TEXT_DOMAIN ) );
        }
        $staff_id = absint( $_POST['staff_id'] ?? 0 );
        check_admin_referer( 'hcjm_delete_staff_' . $staff_id, 'hcjm_nonce' );

        wp_delete_post( $staff_id, true );

        $team_id = absint( $_POST['team_id'] ?? 0 );
        $season  = sanitize_text_field( $_POST['season'] ?? '' );
        wp_safe_redirect( $this->admin_url( [ 'section' => 'staff', 'team' => $team_id, 'season' => $season ], true ) . '&hcjm_notice=staff_deleted' );
        exit;
    }

    public function handle_save_opponent(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nedostatečná oprávnění.', HCJM_TEXT_DOMAIN ) );
        }
        $opponent_id = absint( $_POST['opponent_id'] ?? 0 );
        check_admin_referer( 'hcjm_save_opponent_' . $opponent_id, 'hcjm_nonce' );

        $name    = sanitize_text_field( $_POST['name'] ?? '' );
        $logo_id = absint( $_POST['logo_id'] ?? 0 );

        if ( ! $name ) {
            wp_safe_redirect( admin_url( 'admin.php?page=hcjm-opponents' ) . '&hcjm_notice=error' );
            exit;
        }

        if ( $opponent_id ) {
            wp_update_post( [
                'ID'         => $opponent_id,
                'post_title' => $name,
                'post_name'  => sanitize_title( $name ),
            ] );
        } else {
            $opponent_id = wp_insert_post( [
                'post_type'   => 'hcjm_opponent',
                'post_title'  => $name,
                'post_status' => 'publish',
                'post_name'   => sanitize_title( $name ),
            ] );
        }

        if ( ! $opponent_id || is_wp_error( $opponent_id ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=hcjm-opponents' ) . '&hcjm_notice=error' );
            exit;
        }

        if ( $logo_id ) {
            update_post_meta( (int) $opponent_id, '_hcjm_opponent_logo_id', $logo_id );
        } else {
            delete_post_meta( (int) $opponent_id, '_hcjm_opponent_logo_id' );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=hcjm-opponents' ) . '&hcjm_notice=opponent_saved' );
        exit;
    }

    public function handle_populate_opponents(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nedostatečná oprávnění.', HCJM_TEXT_DOMAIN ) );
        }
        check_admin_referer( 'hcjm_populate_opponents', 'hcjm_nonce' );

        $added = HCJM_Opponents::populate_from_matches();

        wp_safe_redirect( admin_url( 'admin.php?page=hcjm-opponents' ) . '&hcjm_notice=opponents_populated&added=' . $added );
        exit;
    }

    public function handle_sync_matches(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nedostatečná oprávnění.', HCJM_TEXT_DOMAIN ) );
        }
        check_admin_referer( 'hcjm_sync_matches', 'hcjm_nonce' );

        $season  = sanitize_text_field( $_POST['season'] ?? HCJM_Matches::current_season() );
        $results = HCJM_Scraper::sync_all_teams( $season );
        $total   = array_sum( array_column( $results, 'imported' ) );

        update_option( 'hcjm_last_sync', [
            'time'     => current_time( 'mysql' ),
            'imported' => $total,
            'details'  => $results,
        ] );

        // Store per-team details for the notice
        set_transient( 'hcjm_sync_result_' . get_current_user_id(), $results, 120 );

        wp_safe_redirect( admin_url( 'admin.php?page=hcjm-matches&season=' . urlencode( $season ) ) . '&hcjm_notice=sync_done&imported=' . $total );
        exit;
    }

    public function handle_import_players(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nedostatečná oprávnění.', HCJM_TEXT_DOMAIN ) );
        }
        $team_id = absint( $_POST['team_id'] ?? 0 );
        check_admin_referer( 'hcjm_import_players_' . $team_id, 'hcjm_nonce' );

        $season = sanitize_text_field( $_POST['season'] ?? HCJM_Matches::current_season() );
        $result = HCJM_Player_Importer::import_team( $team_id, $season );

        $notice = $result['errors']
            ? 'import_error'
            : 'import_done';

        // Store result for display
        set_transient( 'hcjm_import_result_' . get_current_user_id(), $result, 60 );

        wp_safe_redirect(
            $this->admin_url( [ 'section' => 'players', 'team' => $team_id, 'season' => $season ], true )
            . '&hcjm_notice=' . $notice
            . '&imported=' . $result['imported']
            . '&skipped='  . $result['skipped']
        );
        exit;
    }

    public function handle_delete_match(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nedostatečná oprávnění.', HCJM_TEXT_DOMAIN ) );
        }
        $match_id = absint( $_POST['match_id'] ?? 0 );
        check_admin_referer( 'hcjm_delete_match_' . $match_id, 'hcjm_nonce' );

        HCJM_Database::delete_match( $match_id );

        $season = sanitize_text_field( $_POST['season'] ?? '' );
        wp_safe_redirect( admin_url( 'admin.php?page=hcjm-matches&season=' . urlencode( $season ) ) . '&hcjm_notice=match_deleted' );
        exit;
    }

    public function handle_save_match(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nedostatečná oprávnění.', HCJM_TEXT_DOMAIN ) );
        }
        $match_id = absint( $_POST['match_id'] ?? 0 );
        check_admin_referer( 'hcjm_save_match_' . $match_id, 'hcjm_nonce' );

        $team_id    = absint( $_POST['team_id'] ?? 0 );
        $season     = sanitize_text_field( $_POST['season'] ?? HCJM_Matches::current_season() );
        $date_part  = sanitize_text_field( $_POST['match_date_date'] ?? '' );
        $time_part  = sanitize_text_field( $_POST['match_date_time'] ?? '' );
        $is_home    = absint( $_POST['is_home'] ?? 1 );
        $opponent   = sanitize_text_field( $_POST['opponent'] ?? '' );
        $status     = in_array( $_POST['status'] ?? '', [ 'planned', 'played' ], true ) ? $_POST['status'] : 'planned';

        $score_home_raw = $_POST['score_home'] ?? '';
        $score_away_raw = $_POST['score_away'] ?? '';
        $score_home     = ( $status === 'played' && $score_home_raw !== '' ) ? absint( $score_home_raw ) : null;
        $score_away     = ( $status === 'played' && $score_away_raw !== '' ) ? absint( $score_away_raw ) : null;

        $match_date = $date_part ? $date_part . ' ' . ( $time_part ? $time_part . ':00' : '00:00:00' ) : '';

        // Use existing external_id for updates; generate a new one for new manual entries.
        $external_id = sanitize_text_field( $_POST['external_id'] ?? '' );
        if ( ! $external_id ) {
            $external_id = 'manual_' . wp_generate_password( 12, false, false );
        }

        HCJM_Database::upsert_match( [
            'team_id'     => $team_id,
            'season'      => $season,
            'match_date'  => $match_date,
            'is_home'     => $is_home,
            'opponent'    => $opponent,
            'status'      => $status,
            'score_home'  => $score_home,
            'score_away'  => $score_away,
            'external_id' => $external_id,
        ] );

        wp_safe_redirect( admin_url( 'admin.php?page=hcjm-matches&season=' . urlencode( $season ) ) . '&hcjm_notice=match_saved' );
        exit;
    }

    // -------------------------------------------------------------------------
    // Match edit form
    // -------------------------------------------------------------------------

    private function render_match_edit( int $match_id, string $season ): void {
        $teams   = HCJM_Teams::get_all();
        $seasons = HCJM_Matches::season_list();

        $match = $match_id ? HCJM_Database::get_match( $match_id ) : null;

        $team_id_val    = $match ? (int) $match->team_id    : ( $teams ? $teams[0]->ID : 0 );
        $season_val     = $match ? $match->season            : $season;
        $is_home_val    = $match ? (int) $match->is_home     : 1;
        $opponent_val   = $match ? $match->opponent          : '';
        $status_val     = $match ? $match->status            : 'planned';
        $score_home_val = $match ? $match->score_home        : '';
        $score_away_val = $match ? $match->score_away        : '';
        $external_id    = $match ? $match->external_id       : '';

        $date_part = '';
        $time_part = '';
        if ( $match && $match->match_date && $match->match_date !== '0000-00-00 00:00:00' ) {
            $date_part = substr( $match->match_date, 0, 10 );
            $time_part = substr( $match->match_date, 11, 5 );
        }
        ?>
        <div class="wrap hcjm-wrap">
            <h1><?php echo $match_id ? esc_html__( 'Upravit zápas', HCJM_TEXT_DOMAIN ) : esc_html__( 'Přidat zápas', HCJM_TEXT_DOMAIN ); ?></h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=hcjm-matches&season=' . urlencode( $season ) ) ); ?>" class="hcjm-back">&larr; <?php esc_html_e( 'Zpět na zápasy', HCJM_TEXT_DOMAIN ); ?></a>
            <?php $this->show_notices(); ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'hcjm_save_match_' . $match_id, 'hcjm_nonce' ); ?>
                <input type="hidden" name="action"      value="hcjm_save_match">
                <input type="hidden" name="match_id"    value="<?php echo esc_attr( $match_id ); ?>">
                <input type="hidden" name="external_id" value="<?php echo esc_attr( $external_id ); ?>">

                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Mužstvo', HCJM_TEXT_DOMAIN ); ?></th>
                        <td>
                            <select name="team_id" required>
                                <?php foreach ( $teams as $t ) : ?>
                                    <option value="<?php echo esc_attr( $t->ID ); ?>" <?php selected( $t->ID, $team_id_val ); ?>><?php echo esc_html( $t->post_title ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Sezóna', HCJM_TEXT_DOMAIN ); ?></th>
                        <td>
                            <select name="season">
                                <?php foreach ( $seasons as $s ) : ?>
                                    <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $s, $season_val ); ?>><?php echo esc_html( $s ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Datum', HCJM_TEXT_DOMAIN ); ?></th>
                        <td><input type="date" name="match_date_date" class="regular-text" value="<?php echo esc_attr( $date_part ); ?>" required></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Začátek', HCJM_TEXT_DOMAIN ); ?></th>
                        <td><input type="time" name="match_date_time" class="regular-text" value="<?php echo esc_attr( $time_part ); ?>"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Domácí / Venku', HCJM_TEXT_DOMAIN ); ?></th>
                        <td>
                            <label><input type="radio" name="is_home" value="1" <?php checked( $is_home_val, 1 ); ?>> <?php esc_html_e( 'Domácí (D)', HCJM_TEXT_DOMAIN ); ?></label>
                            &nbsp;&nbsp;
                            <label><input type="radio" name="is_home" value="0" <?php checked( $is_home_val, 0 ); ?>> <?php esc_html_e( 'Venku (V)', HCJM_TEXT_DOMAIN ); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Soupeř', HCJM_TEXT_DOMAIN ); ?></th>
                        <td><input type="text" name="opponent" class="regular-text" value="<?php echo esc_attr( $opponent_val ); ?>" required></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Status', HCJM_TEXT_DOMAIN ); ?></th>
                        <td>
                            <label><input type="radio" name="status" value="planned" id="hcjm_status_planned" <?php checked( $status_val, 'planned' ); ?>> <?php esc_html_e( 'Plánováno', HCJM_TEXT_DOMAIN ); ?></label>
                            &nbsp;&nbsp;
                            <label><input type="radio" name="status" value="played"  id="hcjm_status_played"  <?php checked( $status_val, 'played' ); ?>> <?php esc_html_e( 'Odehráno', HCJM_TEXT_DOMAIN ); ?></label>
                        </td>
                    </tr>
                    <tr id="hcjm_score_row" style="<?php echo $status_val !== 'played' ? 'display:none' : ''; ?>">
                        <th><?php esc_html_e( 'Výsledek', HCJM_TEXT_DOMAIN ); ?></th>
                        <td>
                            <input type="number" name="score_home" class="small-text" min="0" max="99" value="<?php echo esc_attr( $score_home_val ); ?>" placeholder="0">
                            &nbsp;:&nbsp;
                            <input type="number" name="score_away" class="small-text" min="0" max="99" value="<?php echo esc_attr( $score_away_val ); ?>" placeholder="0">
                            <p class="description"><?php esc_html_e( 'Naše skóre : skóre soupeře', HCJM_TEXT_DOMAIN ); ?></p>
                        </td>
                    </tr>
                </table>

                <script>
                (function(){
                    var radios = document.querySelectorAll('input[name="status"]');
                    var row    = document.getElementById('hcjm_score_row');
                    radios.forEach(function(r){
                        r.addEventListener('change', function(){
                            row.style.display = (this.value === 'played') ? '' : 'none';
                        });
                    });
                })();
                </script>

                <?php submit_button( $match_id ? __( 'Uložit zápas', HCJM_TEXT_DOMAIN ) : __( 'Přidat zápas', HCJM_TEXT_DOMAIN ) ); ?>
            </form>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param array<string,mixed> $extra
     * @param bool                $absolute
     * @return string
     */
    private function admin_url( array $extra = [], bool $absolute = false ): string {
        $base = $absolute
            ? admin_url( 'admin.php' )
            : '?';
        $params = array_merge( [ 'page' => 'hcjm-roster' ], $extra );
        return $base . ( $absolute ? '?' : '' ) . http_build_query( $params );
    }

    private function show_notices(): void {
        $notice = sanitize_key( $_GET['hcjm_notice'] ?? '' );
        if ( ! $notice ) {
            return;
        }

        $imported = absint( $_GET['imported'] ?? 0 );
        $skipped  = absint( $_GET['skipped']  ?? 0 );

        $messages = [
            'team_saved'    => [ 'success', __( 'Mužstvo bylo uloženo.', HCJM_TEXT_DOMAIN ) ],
            'player_saved'  => [ 'success', __( 'Hráč byl uložen.', HCJM_TEXT_DOMAIN ) ],
            'player_deleted'=> [ 'success', __( 'Hráč byl smazán.', HCJM_TEXT_DOMAIN ) ],
            'staff_saved'   => [ 'success', __( 'Člen realizačního týmu byl uložen.', HCJM_TEXT_DOMAIN ) ],
            'staff_deleted' => [ 'success', __( 'Člen byl smazán.', HCJM_TEXT_DOMAIN ) ],
            'opponent_saved'     => [ 'success', __( 'Soupeř byl uložen.', HCJM_TEXT_DOMAIN ) ],
            'opponents_populated'=> [ 'success', sprintf(
                __( 'Doplnění dokončeno. Přidáno %d nových soupeřů.', HCJM_TEXT_DOMAIN ),
                absint( $_GET['added'] ?? 0 )
            ) ],
            'styles_saved'  => [ 'success', __( 'Styly byly uloženy.', HCJM_TEXT_DOMAIN ) ],
            'styles_reset'  => [ 'success', __( 'Skupina stylů byla resetována na výchozí hodnoty.', HCJM_TEXT_DOMAIN ) ],
            'match_saved'   => [ 'success', __( 'Zápas byl uložen.', HCJM_TEXT_DOMAIN ) ],
            'match_deleted' => [ 'success', __( 'Zápas byl smazán.', HCJM_TEXT_DOMAIN ) ],
            'sync_done'     => [ $imported > 0 ? 'success' : 'warning', sprintf(
                __( 'Synchronizace dokončena. Importováno %d zápasů.', HCJM_TEXT_DOMAIN ), $imported
            ) ],
            'import_done'   => [ 'success', sprintf(
                __( 'Import hráčů dokončen. Importováno: %d, přeskočeno: %d.', HCJM_TEXT_DOMAIN ), $imported, $skipped
            ) ],
            'import_error'  => [ 'warning', sprintf(
                __( 'Import hráčů dokončen s chybami. Importováno: %d.', HCJM_TEXT_DOMAIN ), $imported
            ) ],
            'error'         => [ 'error', __( 'Nastala chyba. Zkuste to znovu.', HCJM_TEXT_DOMAIN ) ],
        ];

        if ( isset( $messages[ $notice ] ) ) {
            [ $type, $text ] = $messages[ $notice ];
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p>',
                esc_attr( $type ),
                esc_html( $text )
            );

            // Show per-team sync detail when available
            if ( $notice === 'sync_done' ) {
                $results = get_transient( 'hcjm_sync_result_' . get_current_user_id() );
                delete_transient( 'hcjm_sync_result_' . get_current_user_id() );

                if ( is_array( $results ) ) {
                    $teams    = HCJM_Teams::get_all();
                    $team_map = [];
                    foreach ( $teams as $t ) {
                        $team_map[ $t->ID ] = $t->post_title;
                    }

                    echo '<table style="border-collapse:collapse;margin-top:8px;font-size:13px">';
                    echo '<thead><tr><th style="text-align:left;padding:2px 12px 2px 0">' . esc_html__( 'Mužstvo', HCJM_TEXT_DOMAIN ) . '</th>'
                       . '<th style="text-align:left;padding:2px 12px 2px 0">' . esc_html__( 'Importováno', HCJM_TEXT_DOMAIN ) . '</th>'
                       . '<th style="text-align:left;padding:2px 0">' . esc_html__( 'Poznámky', HCJM_TEXT_DOMAIN ) . '</th></tr></thead><tbody>';

                    foreach ( $results as $tid => $r ) {
                        $has_errors = ! empty( $r['errors'] );
                        $color      = $has_errors ? '#b32d2e' : ( $r['imported'] > 0 ? '#1e7e34' : '#856404' );
                        echo '<tr>';
                        printf( '<td style="padding:2px 12px 2px 0">%s</td>', esc_html( $team_map[ $tid ] ?? '#' . $tid ) );
                        printf( '<td style="padding:2px 12px 2px 0;color:%s"><strong>%d</strong></td>', esc_attr( $color ), (int) $r['imported'] );
                        $notes = array_merge( $r['errors'] ?? [], $r['imported'] === 0 && empty( $r['errors'] ) ? [ __( 'Žádná data.', HCJM_TEXT_DOMAIN ) ] : [] );
                        printf( '<td style="color:%s">%s</td>', esc_attr( $color ), esc_html( implode( ' | ', $notes ) ) );
                        echo '</tr>';

                        // Show debug info when nothing was imported
                        if ( $r['imported'] === 0 && ! empty( $r['debug'] ) ) {
                            printf(
                                '<tr><td colspan="3" style="font-size:11px;color:#555;padding:0 0 4px 0">%s</td></tr>',
                                esc_html( $r['debug'] )
                            );
                        }
                    }
                    echo '</tbody></table>';
                }
            }

            // Show import detail
            if ( $notice === 'import_error' || $notice === 'import_done' ) {
                $result = get_transient( 'hcjm_import_result_' . get_current_user_id() );
                delete_transient( 'hcjm_import_result_' . get_current_user_id() );
                if ( is_array( $result ) && ! empty( $result['errors'] ) ) {
                    echo '<ul style="margin:.5em 0 0 1.5em;list-style:disc">';
                    foreach ( $result['errors'] as $err ) {
                        echo '<li>' . esc_html( $err ) . '</li>';
                    }
                    echo '</ul>';
                }
            }

            echo '</div>';
        }
    }

    // -------------------------------------------------------------------------
    // Handler: save styles group
    // -------------------------------------------------------------------------

    public function handle_save_styles(): void {
        check_admin_referer( 'hcjm_save_styles', 'hcjm_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nedostatečná oprávnění.', HCJM_TEXT_DOMAIN ) );
        }

        $schema      = HCJM_Styles::schema();
        $fonts       = HCJM_Styles::font_options();
        $group_key   = sanitize_key( $_POST['style_group'] ?? '' );
        $raw_input   = isset( $_POST['hcjm_styles'] ) && is_array( $_POST['hcjm_styles'] ) ? $_POST['hcjm_styles'] : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

        // Load existing saved values so we only update the current group's fields
        $saved = get_option( HCJM_Styles::OPTION_KEY, [] );
        if ( ! is_array( $saved ) ) {
            $saved = [];
        }

        $group_fields = $schema[ $group_key ]['fields'] ?? [];
        foreach ( $group_fields as $key => $field ) {
            $raw = $raw_input[ $key ] ?? '';
            switch ( $field['type'] ) {
                case 'color':
                    $val = sanitize_hex_color( (string) $raw );
                    if ( $val ) {
                        $saved[ $key ] = $val;
                    }
                    break;
                case 'range':
                    $saved[ $key ] = (string) max( (int) $field['min'], min( (int) $field['max'], (int) $raw ) );
                    break;
                case 'font':
                    $saved[ $key ] = isset( $fonts[ (string) $raw ] ) ? (string) $raw : $field['default'];
                    break;
                case 'textarea':
                    $saved[ $key ] = wp_strip_all_tags( (string) $raw );
                    break;
            }
        }

        update_option( HCJM_Styles::OPTION_KEY, $saved );

        wp_safe_redirect( admin_url( 'admin.php?page=hcjm-styles&style_group=' . urlencode( $group_key ) . '&hcjm_notice=styles_saved' ) );
        exit;
    }

    // -------------------------------------------------------------------------
    // Handler: reset one styles group to defaults
    // -------------------------------------------------------------------------

    public function handle_reset_styles_group(): void {
        check_admin_referer( 'hcjm_reset_styles_group', 'hcjm_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Nedostatečná oprávnění.', HCJM_TEXT_DOMAIN ) );
        }

        $schema    = HCJM_Styles::schema();
        $group_key = sanitize_key( $_POST['style_group'] ?? '' );

        $saved = get_option( HCJM_Styles::OPTION_KEY, [] );
        if ( ! is_array( $saved ) ) {
            $saved = [];
        }

        $group_fields = $schema[ $group_key ]['fields'] ?? [];
        foreach ( array_keys( $group_fields ) as $key ) {
            unset( $saved[ $key ] );
        }

        update_option( HCJM_Styles::OPTION_KEY, $saved );

        wp_safe_redirect( admin_url( 'admin.php?page=hcjm-styles&style_group=' . urlencode( $group_key ) . '&hcjm_notice=styles_reset' ) );
        exit;
    }
}

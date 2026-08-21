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
        $filter_team    = isset( $_GET['filter_team'] ) ? absint( $_GET['filter_team'] ) : 0;
        $seasons        = HCJM_Matches::season_list();

        $args    = [ 'season' => $season, 'limit' => 200 ];
        if ( $filter_team ) {
            $args['team_id'] = $filter_team;
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
                    <label><?php esc_html_e( 'Mužstvo:', HCJM_TEXT_DOMAIN ); ?>
                        <select name="filter_team" onchange="this.form.submit()">
                            <option value="0"><?php esc_html_e( 'Vše', HCJM_TEXT_DOMAIN ); ?></option>
                            <?php foreach ( $teams as $t ) : ?>
                                <option value="<?php echo esc_attr( $t->ID ); ?>" <?php selected( $t->ID, $filter_team ); ?>><?php echo esc_html( $t->post_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </form>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
                    <?php wp_nonce_field( 'hcjm_sync_matches', 'hcjm_nonce' ); ?>
                    <input type="hidden" name="action" value="hcjm_sync_matches">
                    <input type="hidden" name="season" value="<?php echo esc_attr( $season ); ?>">
                    <button type="submit" class="button button-primary">&#8635; <?php esc_html_e( 'Synchronizovat ze ceskyhokej.cz', HCJM_TEXT_DOMAIN ); ?></button>
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
        return $base . ( $absolute ? '&' : '' ) . http_build_query( $params );
    }

    private function show_notices(): void {
        $notice = sanitize_key( $_GET['hcjm_notice'] ?? '' );
        if ( ! $notice ) {
            return;
        }

        $messages = [
            'team_saved'    => __( 'Mužstvo bylo uloženo.', HCJM_TEXT_DOMAIN ),
            'player_saved'  => __( 'Hráč byl uložen.', HCJM_TEXT_DOMAIN ),
            'player_deleted'=> __( 'Hráč byl smazán.', HCJM_TEXT_DOMAIN ),
            'staff_saved'   => __( 'Člen realizačního týmu byl uložen.', HCJM_TEXT_DOMAIN ),
            'staff_deleted' => __( 'Člen byl smazán.', HCJM_TEXT_DOMAIN ),
            'match_deleted' => __( 'Zápas byl smazán.', HCJM_TEXT_DOMAIN ),
            'sync_done'     => sprintf(
                __( 'Synchronizace dokončena. Importováno %d zápasů.', HCJM_TEXT_DOMAIN ),
                absint( $_GET['imported'] ?? 0 )
            ),
            'import_done'   => sprintf(
                __( 'Import hráčů dokončen. Importováno: %d, přeskočeno: %d.', HCJM_TEXT_DOMAIN ),
                absint( $_GET['imported'] ?? 0 ),
                absint( $_GET['skipped']  ?? 0 )
            ),
            'import_error'  => sprintf(
                __( 'Import hráčů dokončen s chybami. Importováno: %d. Podrobnosti viz transient hcjm_import_result.', HCJM_TEXT_DOMAIN ),
                absint( $_GET['imported'] ?? 0 )
            ),
            'error'         => __( 'Nastala chyba. Zkuste to znovu.', HCJM_TEXT_DOMAIN ),
        ];

        if ( isset( $messages[ $notice ] ) ) {
            $type = $notice === 'error' ? 'error' : 'success';
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr( $type ),
                esc_html( $messages[ $notice ] )
            );
        }
    }
}

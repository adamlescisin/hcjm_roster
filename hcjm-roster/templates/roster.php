<?php
/**
 * Template: [hcjm_roster]
 *
 * Available variables:
 *   $players      WP_Post[]
 *   $team_name    string (escaped)
 *   $season       string (escaped)
 *   $has_jersey   bool
 *   $show_avatars bool
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$positions          = HCJM_Players::positions();
$placeholder_att_id = (int) get_option( 'hcjm_placeholder_avatar', 0 );
$placeholder_global = $placeholder_att_id ? wp_get_attachment_image_url( $placeholder_att_id, 'medium' ) : '';
$show_avatars       = $show_avatars ?? true;
$uid                = 'hcjm-roster-' . wp_generate_password( 6, false, false );
?>
<div class="hcjm hcjm-roster<?php echo $show_avatars ? '' : ' hcjm-roster-noavatar'; ?>" id="<?php echo esc_attr( $uid ); ?>">
    <?php if ( empty( $players ) ) : ?>
        <p class="hcjm-empty"><?php esc_html_e( 'Soupiska pro tuto sezónu není zatím k dispozici.', HCJM_TEXT_DOMAIN ); ?></p>
    <?php else : ?>

    <?php if ( $has_jersey ) : ?>
    <div class="hcjm-roster-sort" role="group" aria-label="<?php esc_attr_e( 'Řazení', HCJM_TEXT_DOMAIN ); ?>">
        <span class="hcjm-sort-label"><?php esc_html_e( 'Řazení:', HCJM_TEXT_DOMAIN ); ?></span>
        <button class="hcjm-filter-btn hcjm-sort-btn active" data-sort="jersey"><?php esc_html_e( 'Číslo dresu', HCJM_TEXT_DOMAIN ); ?></button>
        <button class="hcjm-filter-btn hcjm-sort-btn" data-sort="last"><?php esc_html_e( 'Příjmení', HCJM_TEXT_DOMAIN ); ?></button>
    </div>
    <?php endif; ?>

    <?php if ( ! $show_avatars ) : ?>
        <!-- Compact list: no avatars -->
        <div class="hcjm-roster-table-wrap">
            <table class="hcjm-roster-table">
                <thead>
                    <tr>
                        <?php if ( $has_jersey ) : ?><th class="hcjm-rt-jersey">#</th><?php endif; ?>
                        <th class="hcjm-rt-name"><?php esc_html_e( 'Hráč', HCJM_TEXT_DOMAIN ); ?></th>
                        <th class="hcjm-rt-pos"><?php esc_html_e( 'Post', HCJM_TEXT_DOMAIN ); ?></th>
                        <th class="hcjm-rt-year"><?php esc_html_e( 'Ročník', HCJM_TEXT_DOMAIN ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $players as $player ) :
                        $m         = HCJM_Players::get_meta( $player->ID );
                        $pos_label = $positions[ $m['position'] ] ?? $m['position'];
                        $jersey_sort = ( $m['jersey'] !== '' ) ? (int) $m['jersey'] : 9999;
                        $last_sort   = mb_strtolower( $m['first_name'] );
                    ?>
                    <tr class="hcjm-rt-row"
                        data-position="<?php echo esc_attr( $m['position'] ); ?>"
                        data-jersey="<?php echo esc_attr( $jersey_sort ); ?>"
                        data-last="<?php echo esc_attr( $last_sort ); ?>">
                        <?php if ( $has_jersey ) : ?>
                            <td class="hcjm-rt-jersey"><strong><?php echo esc_html( $m['jersey'] ); ?></strong></td>
                        <?php endif; ?>
                        <td class="hcjm-rt-name">
                            <span class="hcjm-player-firstname"><?php echo esc_html( $m['last_name'] ); ?></span>
                            <span class="hcjm-player-lastname"><?php echo esc_html( $m['first_name'] ); ?></span>
                        </td>
                        <td class="hcjm-rt-pos">
                            <span class="hcjm-tag hcjm-position hcjm-pos-<?php echo esc_attr( $m['position'] ); ?>"><?php echo esc_html( $pos_label ); ?></span>
                        </td>
                        <td class="hcjm-rt-year"><?php echo $m['birth_year'] ? esc_html( '*' . $m['birth_year'] ) : ''; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <!-- Full card grid with avatars -->
        <div class="hcjm-cards">
            <?php foreach ( $players as $player ) :
                $m         = HCJM_Players::get_meta( $player->ID );
                $photo_url = $m['photo_id'] ? wp_get_attachment_image_url( $m['photo_id'], 'medium' ) : $placeholder_global;
                $pos_label = $positions[ $m['position'] ] ?? $m['position'];
                $initials  = mb_substr( $m['last_name'], 0, 1 ) . mb_substr( $m['first_name'], 0, 1 );
                $jersey_sort = ( $m['jersey'] !== '' ) ? (int) $m['jersey'] : 9999;
                $last_sort   = mb_strtolower( $m['first_name'] );
            ?>
                <div class="hcjm-card hcjm-player-card"
                     data-position="<?php echo esc_attr( $m['position'] ); ?>"
                     data-jersey="<?php echo esc_attr( $jersey_sort ); ?>"
                     data-last="<?php echo esc_attr( $last_sort ); ?>">
                    <div class="hcjm-card-photo">
                        <?php if ( $photo_url ) : ?>
                            <img src="<?php echo esc_url( $photo_url ); ?>"
                                 alt="<?php echo esc_attr( $m['last_name'] . ' ' . $m['first_name'] ); ?>"
                                 loading="lazy"
                                 class="<?php echo ( ! $m['photo_id'] && $placeholder_global ) ? 'hcjm-photo-placeholder-img' : ''; ?>">
                        <?php else : ?>
                            <div class="hcjm-photo-placeholder">
                                <?php if ( $has_jersey && $m['jersey'] ) : ?>
                                    <span class="hcjm-jersey-placeholder"><?php echo esc_html( $m['jersey'] ); ?></span>
                                <?php else : ?>
                                    <span class="hcjm-initials"><?php echo esc_html( $initials ); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ( $has_jersey && $m['jersey'] ) : ?>
                            <span class="hcjm-jersey-badge"><?php echo esc_html( $m['jersey'] ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="hcjm-card-body">
                        <div class="hcjm-player-name">
                            <span class="hcjm-player-firstname"><?php echo esc_html( $m['last_name'] ); ?></span>
                            <span class="hcjm-player-lastname"><?php echo esc_html( $m['first_name'] ); ?></span>
                        </div>
                        <div class="hcjm-player-meta">
                            <span class="hcjm-tag hcjm-position hcjm-pos-<?php echo esc_attr( $m['position'] ); ?>"><?php echo esc_html( $pos_label ); ?></span>
                            <?php if ( $m['birth_year'] ) : ?>
                                <span class="hcjm-birth-year">*<?php echo esc_html( $m['birth_year'] ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ( $has_jersey && ! empty( $players ) ) : ?>
<script>
(function(){
    var wrap = document.getElementById(<?php echo wp_json_encode( $uid ); ?>);
    if (!wrap) return;

    var btns = wrap.querySelectorAll('.hcjm-sort-btn');

    function sortItems(by) {
        var cardGrid = wrap.querySelector('.hcjm-cards');
        if (cardGrid) {
            var cards = Array.from(cardGrid.querySelectorAll('.hcjm-player-card'));
            cards.sort(function(a, b) {
                if (by === 'jersey') {
                    return parseInt(a.dataset.jersey, 10) - parseInt(b.dataset.jersey, 10);
                }
                var la = a.dataset.last || '', lb = b.dataset.last || '';
                return la < lb ? -1 : la > lb ? 1 : 0;
            });
            cards.forEach(function(c){ cardGrid.appendChild(c); });
        }

        var tbody = wrap.querySelector('.hcjm-roster-table tbody');
        if (tbody) {
            var rows = Array.from(tbody.querySelectorAll('.hcjm-rt-row'));
            rows.sort(function(a, b) {
                if (by === 'jersey') {
                    return parseInt(a.dataset.jersey, 10) - parseInt(b.dataset.jersey, 10);
                }
                var la = a.dataset.last || '', lb = b.dataset.last || '';
                return la < lb ? -1 : la > lb ? 1 : 0;
            });
            rows.forEach(function(r){ tbody.appendChild(r); });
        }
    }

    btns.forEach(function(btn){
        btn.addEventListener('click', function(){
            btns.forEach(function(b){ b.classList.remove('active'); });
            btn.classList.add('active');
            sortItems(btn.dataset.sort);
        });
    });
})();
</script>
<?php endif; ?>

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
?>
<div class="hcjm hcjm-roster<?php echo $show_avatars ? '' : ' hcjm-roster-noavatar'; ?>">
    <?php if ( empty( $players ) ) : ?>
        <p class="hcjm-empty"><?php esc_html_e( 'Soupiska pro tuto sezónu není zatím k dispozici.', HCJM_TEXT_DOMAIN ); ?></p>
    <?php elseif ( ! $show_avatars ) : ?>
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
                    ?>
                    <tr class="hcjm-rt-row" data-position="<?php echo esc_attr( $m['position'] ); ?>">
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
            ?>
                <div class="hcjm-card hcjm-player-card" data-position="<?php echo esc_attr( $m['position'] ); ?>">
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
</div>

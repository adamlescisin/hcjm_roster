<?php
/**
 * Template: [hcjm_roster]
 *
 * Available variables:
 *   $players    WP_Post[]
 *   $team_name  string (escaped)
 *   $season     string (escaped)
 *   $has_jersey bool
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$positions          = HCJM_Players::positions();
$placeholder_att_id = (int) get_option( 'hcjm_placeholder_avatar', 0 );
$placeholder_global = $placeholder_att_id ? wp_get_attachment_image_url( $placeholder_att_id, 'medium' ) : '';
?>
<div class="hcjm hcjm-roster">
    <?php if ( empty( $players ) ) : ?>
        <p class="hcjm-empty"><?php esc_html_e( 'Soupiska pro tuto sezónu není zatím k dispozici.', HCJM_TEXT_DOMAIN ); ?></p>
    <?php else : ?>
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

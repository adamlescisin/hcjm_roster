<?php
/**
 * Template: [hcjm_staff]
 *
 * Available variables:
 *   $members    WP_Post[]
 *   $team_name  string (escaped)
 *   $season     string (escaped)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="hcjm hcjm-staff">
    <?php if ( empty( $members ) ) : ?>
        <p class="hcjm-empty"><?php esc_html_e( 'Realizační tým pro tuto sezónu není zatím k dispozici.', HCJM_TEXT_DOMAIN ); ?></p>
    <?php else : ?>
        <?php
        $placeholder_att_id = (int) get_option( 'hcjm_placeholder_avatar', 0 );
        $placeholder_global = $placeholder_att_id ? wp_get_attachment_image_url( $placeholder_att_id, 'medium' ) : '';
        ?>
        <div class="hcjm-cards hcjm-staff-cards">
            <?php foreach ( $members as $member ) :
                $m         = HCJM_Staff::get_meta( $member->ID );
                $photo_url = $m['photo_id'] ? wp_get_attachment_image_url( $m['photo_id'], 'medium' ) : $placeholder_global;
                $initials  = mb_substr( $m['first_name'], 0, 1 ) . mb_substr( $m['last_name'], 0, 1 );
                $is_email  = filter_var( $m['contact'], FILTER_VALIDATE_EMAIL );
                $is_phone  = $m['contact'] && ! $is_email;
            ?>
                <div class="hcjm-card hcjm-staff-card">
                    <div class="hcjm-card-photo">
                        <?php if ( $photo_url ) : ?>
                            <img src="<?php echo esc_url( $photo_url ); ?>"
                                 alt="<?php echo esc_attr( $m['first_name'] . ' ' . $m['last_name'] ); ?>"
                                 loading="lazy"
                                 class="<?php echo ( ! $m['photo_id'] && $placeholder_global ) ? 'hcjm-photo-placeholder-img' : ''; ?>">
                        <?php else : ?>
                            <div class="hcjm-photo-placeholder">
                                <span class="hcjm-initials"><?php echo esc_html( $initials ); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="hcjm-card-body">
                        <div class="hcjm-staff-name">
                            <?php echo esc_html( $m['first_name'] . ' ' . $m['last_name'] ); ?>
                        </div>
                        <div class="hcjm-staff-role"><?php echo esc_html( $m['role'] ); ?></div>
                        <?php if ( $m['contact'] ) : ?>
                            <div class="hcjm-staff-contact">
                                <?php if ( $is_email ) : ?>
                                    <a href="mailto:<?php echo esc_attr( $m['contact'] ); ?>"><?php echo esc_html( $m['contact'] ); ?></a>
                                <?php elseif ( $is_phone ) : ?>
                                    <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $m['contact'] ) ); ?>"><?php echo esc_html( $m['contact'] ); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html( $m['contact'] ); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

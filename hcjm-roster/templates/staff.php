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
        <div class="hcjm-staff-card">
            <?php foreach ( $members as $index => $member ) :
                $m        = HCJM_Staff::get_meta( $member->ID );
                $name     = trim( $m['first_name'] . ' ' . $m['last_name'] );
                $is_email = filter_var( $m['contact'], FILTER_VALIDATE_EMAIL );
                $is_phone = $m['contact'] && ! $is_email;
            ?>
            <div class="hcjm-staff-row<?php echo $index === 0 ? ' hcjm-staff-row-first' : ''; echo $index === count( $members ) - 1 ? ' hcjm-staff-row-last' : ''; ?>">
                <div class="hcjm-staff-info">
                    <?php if ( $name ) : ?>
                        <span class="hcjm-staff-name"><?php echo esc_html( $name ); ?></span>
                    <?php endif; ?>
                    <?php if ( $m['role'] ) : ?>
                        <span class="hcjm-staff-role"><?php echo esc_html( $m['role'] ); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ( $m['contact'] ) : ?>
                <div class="hcjm-staff-contact">
                    <?php if ( $is_email ) : ?>
                        <a href="mailto:<?php echo esc_attr( $m['contact'] ); ?>"><?php echo esc_html( $m['contact'] ); ?></a>
                    <?php elseif ( $is_phone ) : ?>
                        <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $m['contact'] ) ); ?>"><?php echo esc_html( $m['contact'] ); ?></a>
                    <?php else : ?>
                        <span><?php echo esc_html( $m['contact'] ); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

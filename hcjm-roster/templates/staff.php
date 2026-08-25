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
        <div class="hcjm-staff-card" style="background:#1c2130;border:1px solid rgba(255,255,255,.07);border-radius:14px;overflow:hidden;box-shadow:0 6px 20px rgba(0,0,0,.35)">
            <?php foreach ( $members as $index => $member ) :
                $m        = HCJM_Staff::get_meta( $member->ID );
                $name     = trim( $m['first_name'] . ' ' . $m['last_name'] );
                $is_email = filter_var( $m['contact'], FILTER_VALIDATE_EMAIL );
                $is_phone = $m['contact'] && ! $is_email;
            ?>
            <div class="hcjm-staff-row<?php echo $index === 0 ? ' hcjm-staff-row-first' : ''; echo $index === count( $members ) - 1 ? ' hcjm-staff-row-last' : ''; ?>" style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 20px;border-bottom:<?php echo $index === count( $members ) - 1 ? 'none' : '1px solid rgba(255,255,255,.07)'; ?>">
                <div class="hcjm-staff-info" style="display:flex;flex-direction:column;gap:2px;min-width:0">
                    <?php if ( $name ) : ?>
                        <span class="hcjm-staff-name" style="font-weight:700;font-size:.95rem;color:#ffffff"><?php echo esc_html( $name ); ?></span>
                    <?php endif; ?>
                    <?php if ( $m['role'] ) : ?>
                        <span class="hcjm-staff-role" style="font-size:.82rem;color:rgba(255,255,255,.55)"><?php echo esc_html( $m['role'] ); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ( $m['contact'] ) : ?>
                <div class="hcjm-staff-contact" style="font-size:.84rem;color:rgba(255,255,255,.60);white-space:nowrap;flex-shrink:0;text-align:right">
                    <?php if ( $is_email ) : ?>
                        <a href="mailto:<?php echo esc_attr( $m['contact'] ); ?>" style="color:#8db4e8;text-decoration:none"><?php echo esc_html( $m['contact'] ); ?></a>
                    <?php elseif ( $is_phone ) : ?>
                        <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $m['contact'] ) ); ?>" style="color:#8db4e8;text-decoration:none"><?php echo esc_html( $m['contact'] ); ?></a>
                    <?php else : ?>
                        <span style="color:rgba(255,255,255,.60)"><?php echo esc_html( $m['contact'] ); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

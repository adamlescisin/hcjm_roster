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
        <table class="hcjm-staff-table">
            <tbody>
                <?php foreach ( $members as $member ) :
                    $m        = HCJM_Staff::get_meta( $member->ID );
                    $is_email = filter_var( $m['contact'], FILTER_VALIDATE_EMAIL );
                    $is_phone = $m['contact'] && ! $is_email;
                ?>
                <tr class="hcjm-staff-row">
                    <td class="hcjm-staff-td hcjm-staff-td-name">
                        <span class="hcjm-staff-name"><?php echo esc_html( trim( $m['first_name'] . ' ' . $m['last_name'] ) ); ?></span>
                    </td>
                    <td class="hcjm-staff-td hcjm-staff-td-role">
                        <span class="hcjm-staff-role"><?php echo esc_html( $m['role'] ); ?></span>
                    </td>
                    <?php if ( $m['contact'] ) : ?>
                    <td class="hcjm-staff-td hcjm-staff-td-contact">
                        <span class="hcjm-staff-contact">
                            <?php if ( $is_email ) : ?>
                                <a href="mailto:<?php echo esc_attr( $m['contact'] ); ?>"><?php echo esc_html( $m['contact'] ); ?></a>
                            <?php elseif ( $is_phone ) : ?>
                                <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $m['contact'] ) ); ?>"><?php echo esc_html( $m['contact'] ); ?></a>
                            <?php else : ?>
                                <?php echo esc_html( $m['contact'] ); ?>
                            <?php endif; ?>
                        </span>
                    </td>
                    <?php else : ?>
                    <td class="hcjm-staff-td hcjm-staff-td-contact"></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

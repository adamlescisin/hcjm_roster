<?php
/**
 * Template: [hcjm_matches type="upcoming"] and type="all"
 *
 * Available variables:
 *   $matches    object[]   DB rows
 *   $team_name  string
 *   $season     string
 *   $type       string    'upcoming'|'all'
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="hcjm hcjm-matches hcjm-matches-upcoming">
    <?php if ( empty( $matches ) ) : ?>
        <p class="hcjm-empty">
            <?php esc_html_e( 'Žádné nadcházející zápasy.', HCJM_TEXT_DOMAIN ); ?>
        </p>
    <?php else : ?>
        <div class="hcjm-matches-list">
            <?php foreach ( $matches as $match ) :
                $won       = HCJM_Matches::hcjm_won( $match );
                $score     = HCJM_Matches::format_score( $match );
                $date      = HCJM_Matches::format_date( $match );
                $is_played = $match->status === 'played';
                $row_class = '';
                if ( $is_played && $won === true )  $row_class = 'hcjm-win';
                if ( $is_played && $won === false ) $row_class = 'hcjm-loss';
                if ( $is_played && $won === null )   $row_class = 'hcjm-draw';
            ?>
                <div class="hcjm-match-row <?php echo esc_attr( $row_class ); ?>">
                    <div class="hcjm-match-date">
                        <?php echo esc_html( $date ); ?>
                    </div>
                    <div class="hcjm-match-teams">
                        <span class="hcjm-match-badge <?php echo $match->is_home ? 'hcjm-home' : 'hcjm-away'; ?>">
                            <?php echo $match->is_home ? esc_html__( 'Domácí', HCJM_TEXT_DOMAIN ) : esc_html__( 'Hosté', HCJM_TEXT_DOMAIN ); ?>
                        </span>
                        <span class="hcjm-match-opponent"><?php echo esc_html( $match->opponent ); ?></span>
                    </div>
                    <div class="hcjm-match-score">
                        <?php if ( $is_played ) : ?>
                            <strong class="hcjm-score"><?php echo $score; ?></strong>
                            <?php if ( $won === true ) : ?>
                                <span class="hcjm-result hcjm-result-win"><?php esc_html_e( 'Výhra', HCJM_TEXT_DOMAIN ); ?></span>
                            <?php elseif ( $won === false ) : ?>
                                <span class="hcjm-result hcjm-result-loss"><?php esc_html_e( 'Prohra', HCJM_TEXT_DOMAIN ); ?></span>
                            <?php else : ?>
                                <span class="hcjm-result hcjm-result-draw"><?php esc_html_e( 'Remíza', HCJM_TEXT_DOMAIN ); ?></span>
                            <?php endif; ?>
                        <?php else : ?>
                            <span class="hcjm-planned"><?php esc_html_e( 'Plánováno', HCJM_TEXT_DOMAIN ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

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

$days_cs = [
    'Mon' => 'Po', 'Tue' => 'Út', 'Wed' => 'St',
    'Thu' => 'Čt', 'Fri' => 'Pá', 'Sat' => 'So', 'Sun' => 'Ne',
];
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

                // Day-of-week prefix
                $ts         = $match->match_date ? strtotime( $match->match_date ) : 0;
                $day_abbr   = $ts ? ( $days_cs[ date( 'D', $ts ) ] ?? '' ) : '';
                $time_str   = $ts ? date( 'H:i', $ts ) : '';

                $row_class = '';
                if ( $is_played && $won === true )  { $row_class = 'hcjm-win'; }
                if ( $is_played && $won === false )  { $row_class = 'hcjm-loss'; }
                if ( $is_played && $won === null )   { $row_class = 'hcjm-draw'; }
            ?>
                <div class="hcjm-match-row <?php echo esc_attr( $row_class ); ?>">
                    <div class="hcjm-match-date">
                        <?php if ( $day_abbr ) : ?>
                            <strong><?php echo esc_html( $day_abbr . ' ' . $date ); ?></strong>
                            <?php if ( $time_str && $time_str !== '00:00' ) : ?>
                                <?php echo esc_html( $time_str ); ?>
                            <?php endif; ?>
                        <?php else : ?>
                            <?php echo esc_html( $date ); ?>
                        <?php endif; ?>
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

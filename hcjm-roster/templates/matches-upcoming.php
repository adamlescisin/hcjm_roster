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

$days_cs = [ 1 => 'Po', 2 => 'Út', 3 => 'St', 4 => 'Čt', 5 => 'Pá', 6 => 'So', 7 => 'Ne' ];
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
                $day_abbr   = $ts ? ( $days_cs[ (int) wp_date( 'N', $ts ) ] ?? '' ) : '';
                $time_str   = $ts ? (string) wp_date( 'H:i', $ts ) : '';

                $row_class = '';
                if ( $is_played && $won === true )  { $row_class = 'hcjm-win'; }
                if ( $is_played && $won === false )  { $row_class = 'hcjm-loss'; }
                if ( $is_played && $won === null )   { $row_class = 'hcjm-draw'; }
            ?>
                <div class="hcjm-match-row <?php echo esc_attr( $row_class ); ?>">
                    <div class="hcjm-match-date">
                        <?php if ( $day_abbr ) : ?>
                            <strong><?php echo esc_html( $day_abbr . ' ' . $date ); ?></strong>
                        <?php else : ?>
                            <strong><?php echo esc_html( $date ); ?></strong>
                        <?php endif; ?>
                        <?php if ( $time_str && $time_str !== '00:00' ) : ?>
                            <?php echo esc_html( $time_str ); ?>
                        <?php endif; ?>
                    </div>
                    <div class="hcjm-match-teams">
                        <span class="hcjm-match-badge <?php echo $match->is_home ? 'hcjm-home' : 'hcjm-away'; ?>">
                            <?php echo $match->is_home ? esc_html__( 'Domácí', HCJM_TEXT_DOMAIN ) : esc_html__( 'Hosté', HCJM_TEXT_DOMAIN ); ?>
                        </span>
                        <span class="hcjm-match-matchup">
                            <?php if ( $match->is_home ) : ?>
                                <span class="hcjm-match-team hcjm-match-team-us"><?php echo $team_name; ?></span>
                                <span class="hcjm-match-vs">vs.</span>
                                <span class="hcjm-match-team hcjm-match-team-opponent"><?php echo esc_html( $match->opponent ); ?></span>
                            <?php else : ?>
                                <span class="hcjm-match-team hcjm-match-team-opponent"><?php echo esc_html( $match->opponent ); ?></span>
                                <span class="hcjm-match-vs">vs.</span>
                                <span class="hcjm-match-team hcjm-match-team-us"><?php echo $team_name; ?></span>
                            <?php endif; ?>
                        </span>
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

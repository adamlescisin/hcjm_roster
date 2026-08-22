<?php
/**
 * Template: [hcjm_matches type="past"]
 *
 * Available variables:
 *   $matches    object[]
 *   $team_name  string
 *   $season     string
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$days_cs = [
    'Mon' => 'Po', 'Tue' => 'Út', 'Wed' => 'St',
    'Thu' => 'Čt', 'Fri' => 'Pá', 'Sat' => 'So', 'Sun' => 'Ne',
];
?>
<div class="hcjm hcjm-matches hcjm-matches-past">
    <?php if ( empty( $matches ) ) : ?>
        <p class="hcjm-empty"><?php esc_html_e( 'Žádné výsledky.', HCJM_TEXT_DOMAIN ); ?></p>
    <?php else : ?>
        <div class="hcjm-matches-list">
            <?php foreach ( $matches as $match ) :
                $won   = HCJM_Matches::hcjm_won( $match );
                $score = HCJM_Matches::format_score( $match );
                $date  = HCJM_Matches::format_date( $match );

                $ts       = $match->match_date ? strtotime( $match->match_date ) : 0;
                $day_abbr = $ts ? ( $days_cs[ (string) wp_date( 'D', $ts ) ] ?? '' ) : '';

                if ( $won === true )        { $row_class = 'hcjm-win'; }
                elseif ( $won === false )   { $row_class = 'hcjm-loss'; }
                else                        { $row_class = 'hcjm-draw'; }
            ?>
                <div class="hcjm-match-row <?php echo esc_attr( $row_class ); ?>">
                    <div class="hcjm-match-date">
                        <?php if ( $day_abbr ) : ?>
                            <strong><?php echo esc_html( $day_abbr . ' ' . $date ); ?></strong>
                        <?php else : ?>
                            <?php echo esc_html( $date ); ?>
                        <?php endif; ?>
                    </div>
                    <div class="hcjm-match-teams">
                        <span class="hcjm-match-badge <?php echo $match->is_home ? 'hcjm-home' : 'hcjm-away'; ?>">
                            <?php echo $match->is_home ? esc_html__( 'D', HCJM_TEXT_DOMAIN ) : esc_html__( 'V', HCJM_TEXT_DOMAIN ); ?>
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
                        <strong class="hcjm-score"><?php echo $score; ?></strong>
                        <?php if ( $won === true ) : ?>
                            <span class="hcjm-result hcjm-result-win"><?php esc_html_e( 'Výhra', HCJM_TEXT_DOMAIN ); ?></span>
                        <?php elseif ( $won === false ) : ?>
                            <span class="hcjm-result hcjm-result-loss"><?php esc_html_e( 'Prohra', HCJM_TEXT_DOMAIN ); ?></span>
                        <?php else : ?>
                            <span class="hcjm-result hcjm-result-draw"><?php esc_html_e( 'Remíza', HCJM_TEXT_DOMAIN ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

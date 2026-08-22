<?php
/**
 * Template: [hcjm_match_schedule]
 *
 * Available variables:
 *   $matches         object[]          Upcoming match DB rows, ordered by match_date ASC
 *   $team_map        array<int,string> team_id → post_title
 *   $active_team_ids int[]             Team IDs that appear in $matches, in order
 *   $season          string
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $matches ) ) {
    ?>
    <div class="hcjm hcjm-schedule-empty">
        <p><?php esc_html_e( 'Žádné nadcházející zápasy.', HCJM_TEXT_DOMAIN ); ?></p>
    </div>
    <?php
    return;
}

$days_cs = [ 1 => 'Po', 2 => 'Út', 3 => 'St', 4 => 'Čt', 5 => 'Pá', 6 => 'So', 7 => 'Ne' ];

$uid = 'hcjm-sch-' . wp_generate_password( 6, false, false );

$club_logo_id  = absint( HCJM_Styles::get_saved()['club_logo'] ?? 0 );
$club_logo_url = $club_logo_id ? wp_get_attachment_image_url( $club_logo_id, 'thumbnail' ) : '';
?>
<div class="hcjm hcjm-schedule" id="<?php echo esc_attr( $uid ); ?>">

    <?php
    $default_tid = ! empty( $active_team_ids ) ? $active_team_ids[0] : null;
    ?>
    <?php if ( count( $active_team_ids ) > 1 ) : ?>
    <div class="hcjm-schedule-filters" role="group" aria-label="<?php esc_attr_e( 'Filtr kategorie', HCJM_TEXT_DOMAIN ); ?>">
        <?php foreach ( $active_team_ids as $tid ) : ?>
            <button class="hcjm-sch-pill<?php echo $tid === $default_tid ? ' active' : ''; ?>" data-filter="<?php echo esc_attr( $tid ); ?>">
                <?php echo esc_html( $team_map[ $tid ] ?? '' ); ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="hcjm-schedule-list">
        <?php foreach ( $matches as $match ) :
            $ts         = $match->match_date ? strtotime( $match->match_date ) : 0;
            $day_abbr   = $ts ? ( $days_cs[ (int) wp_date( 'N', $ts ) ] ?? '' ) : '';
            $date_str   = $ts ? (string) wp_date( 'j. n. Y', $ts ) : '';
            $time_str   = ( $ts && wp_date( 'H:i', $ts ) !== '00:00' ) ? (string) wp_date( 'H:i', $ts ) : '';
            $round      = ! empty( $match->round ) ? esc_html( $match->round ) : '';
            $tid        = (int) $match->team_id;
            $cat        = esc_html( $team_map[ $tid ] ?? '' );
            $opp_name   = esc_html( $match->opponent );
            $opp_logo   = esc_url( HCJM_Opponents::get_logo_url_by_name( $match->opponent, 'thumbnail' ) );
            $opp_initials = mb_strtoupper( mb_substr( $match->opponent, 0, 3 ) );
            $is_home    = (bool) $match->is_home;
        ?>
        <div class="hcjm-sch-row" data-team-id="<?php echo esc_attr( $tid ); ?>">

            <div class="hcjm-sch-date">
                <?php if ( $day_abbr && $date_str ) : ?>
                    <span class="hcjm-sch-day"><?php echo esc_html( $day_abbr ); ?></span>
                    <span class="hcjm-sch-datenum"><?php echo esc_html( $date_str ); ?></span>
                    <?php if ( $time_str ) : ?>
                        <span class="hcjm-sch-time"><?php echo esc_html( $time_str ); ?></span>
                    <?php endif; ?>
                <?php else : ?>
                    <span class="hcjm-sch-datenum"><?php esc_html_e( 'TBD', HCJM_TEXT_DOMAIN ); ?></span>
                <?php endif; ?>
            </div>

            <div class="hcjm-sch-cat">
                <span class="hcjm-sch-cat-badge"><?php echo $cat; ?></span>
            </div>

            <div class="hcjm-sch-matchup">
                <span class="hcjm-sch-ha-badge <?php echo $is_home ? 'hcjm-home' : 'hcjm-away'; ?>">
                    <?php echo $is_home
                        ? esc_html__( 'D', HCJM_TEXT_DOMAIN )
                        : esc_html__( 'V', HCJM_TEXT_DOMAIN ); ?>
                </span>
                <div class="hcjm-sch-teams">
                    <?php if ( $is_home ) : ?>
                        <!-- Home game: [HCJ logo] Us  vs.  Opponent [opp logo] -->
                        <div class="hcjm-sch-team-block">
                            <div class="hcjm-sch-logo-wrap">
                                <?php if ( $club_logo_url ) : ?>
                                    <img src="<?php echo esc_url( $club_logo_url ); ?>" alt="HC Junior Mělník" class="hcjm-sch-logo-img">
                                <?php else : ?>
                                    <svg viewBox="0 0 40 40" class="hcjm-sch-logo-svg" aria-hidden="true">
                                        <circle cx="20" cy="20" r="19" fill="#1c2130" stroke="rgba(255,255,255,.15)" stroke-width="1"/>
                                        <text x="20" y="25" text-anchor="middle" fill="#ffffff" font-size="10" font-weight="900" font-family="inherit">HCJ</text>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <span class="hcjm-sch-team-us"><?php esc_html_e( 'HC Junior Mělník', HCJM_TEXT_DOMAIN ); ?></span>
                        </div>
                        <span class="hcjm-sch-vs">vs.</span>
                        <div class="hcjm-sch-team-block hcjm-sch-team-block-right">
                            <span class="hcjm-sch-team-opp"><?php echo $opp_name; ?></span>
                            <div class="hcjm-sch-logo-wrap">
                                <?php if ( $opp_logo ) : ?>
                                    <img src="<?php echo $opp_logo; ?>" alt="<?php echo $opp_name; ?>" class="hcjm-sch-logo-img">
                                <?php else : ?>
                                    <svg viewBox="0 0 40 40" class="hcjm-sch-logo-svg" aria-hidden="true">
                                        <circle cx="20" cy="20" r="19" fill="#1c2130" stroke="rgba(255,255,255,.15)" stroke-width="1"/>
                                        <text x="20" y="25" text-anchor="middle" fill="rgba(255,255,255,.7)" font-size="9" font-weight="700" font-family="inherit"><?php echo $opp_initials; ?></text>
                                    </svg>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else : ?>
                        <!-- Away game: [Opp logo] Opponent  vs.  Us [HCJ logo] -->
                        <div class="hcjm-sch-team-block">
                            <div class="hcjm-sch-logo-wrap">
                                <?php if ( $opp_logo ) : ?>
                                    <img src="<?php echo $opp_logo; ?>" alt="<?php echo $opp_name; ?>" class="hcjm-sch-logo-img">
                                <?php else : ?>
                                    <svg viewBox="0 0 40 40" class="hcjm-sch-logo-svg" aria-hidden="true">
                                        <circle cx="20" cy="20" r="19" fill="#1c2130" stroke="rgba(255,255,255,.15)" stroke-width="1"/>
                                        <text x="20" y="25" text-anchor="middle" fill="rgba(255,255,255,.7)" font-size="9" font-weight="700" font-family="inherit"><?php echo $opp_initials; ?></text>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <span class="hcjm-sch-team-opp"><?php echo $opp_name; ?></span>
                        </div>
                        <span class="hcjm-sch-vs">vs.</span>
                        <div class="hcjm-sch-team-block hcjm-sch-team-block-right">
                            <span class="hcjm-sch-team-us"><?php esc_html_e( 'HC Junior Mělník', HCJM_TEXT_DOMAIN ); ?></span>
                            <div class="hcjm-sch-logo-wrap">
                                <?php if ( $club_logo_url ) : ?>
                                    <img src="<?php echo esc_url( $club_logo_url ); ?>" alt="HC Junior Mělník" class="hcjm-sch-logo-img">
                                <?php else : ?>
                                    <svg viewBox="0 0 40 40" class="hcjm-sch-logo-svg" aria-hidden="true">
                                        <circle cx="20" cy="20" r="19" fill="#1c2130" stroke="rgba(255,255,255,.15)" stroke-width="1"/>
                                        <text x="20" y="25" text-anchor="middle" fill="#ffffff" font-size="10" font-weight="900" font-family="inherit">HCJ</text>
                                    </svg>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ( $round ) : ?>
            <div class="hcjm-sch-round"><?php echo $round; ?></div>
            <?php else : ?>
            <div class="hcjm-sch-round"></div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    </div>

</div>

<script>
(function(){
    var wrap = document.getElementById(<?php echo wp_json_encode( $uid ); ?>);
    if (!wrap) return;

    var pills = wrap.querySelectorAll('.hcjm-sch-pill');
    var rows  = wrap.querySelectorAll('.hcjm-sch-row');

    function applyFilter(filter) {
        rows.forEach(function(row){
            row.style.display = (row.dataset.teamId === filter) ? '' : 'none';
        });
    }

    pills.forEach(function(pill){
        pill.addEventListener('click', function(){
            pills.forEach(function(p){ p.classList.remove('active'); });
            pill.classList.add('active');
            applyFilter(pill.dataset.filter);
        });
    });

    // Apply the default filter on load
    <?php if ( $default_tid ) : ?>
    applyFilter(<?php echo wp_json_encode( (string) $default_tid ); ?>);
    <?php endif; ?>
})();
</script>

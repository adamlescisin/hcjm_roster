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

$days_cs = [
    'Mon' => 'Po', 'Tue' => 'Út', 'Wed' => 'St',
    'Thu' => 'Čt', 'Fri' => 'Pá', 'Sat' => 'So', 'Sun' => 'Ne',
];

$uid = 'hcjm-sch-' . wp_generate_password( 6, false, false );
?>
<div class="hcjm hcjm-schedule" id="<?php echo esc_attr( $uid ); ?>">

    <?php if ( count( $active_team_ids ) > 1 ) : ?>
    <div class="hcjm-schedule-filters" role="group" aria-label="<?php esc_attr_e( 'Filtr kategorie', HCJM_TEXT_DOMAIN ); ?>">
        <button class="hcjm-sch-pill active" data-filter="all">
            <?php esc_html_e( 'Všechny', HCJM_TEXT_DOMAIN ); ?>
        </button>
        <?php foreach ( $active_team_ids as $tid ) : ?>
            <button class="hcjm-sch-pill" data-filter="<?php echo esc_attr( $tid ); ?>">
                <?php echo esc_html( $team_map[ $tid ] ?? '' ); ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="hcjm-schedule-list">
        <?php foreach ( $matches as $match ) :
            $ts       = $match->match_date ? strtotime( $match->match_date ) : 0;
            $day_abbr = $ts ? ( $days_cs[ (string) wp_date( 'D', $ts ) ] ?? '' ) : '';
            $date_str = $ts ? (string) wp_date( 'j. n. Y', $ts ) : '';
            $time_str = ( $ts && wp_date( 'H:i', $ts ) !== '00:00' ) ? (string) wp_date( 'H:i', $ts ) : '';
            $round    = ! empty( $match->round ) ? esc_html( $match->round ) : '';
            $tid      = (int) $match->team_id;
            $cat      = esc_html( $team_map[ $tid ] ?? '' );
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
                <span class="hcjm-sch-ha-badge <?php echo $match->is_home ? 'hcjm-home' : 'hcjm-away'; ?>">
                    <?php echo $match->is_home
                        ? esc_html__( 'D', HCJM_TEXT_DOMAIN )
                        : esc_html__( 'V', HCJM_TEXT_DOMAIN ); ?>
                </span>
                <span class="hcjm-sch-teams">
                    <?php if ( $match->is_home ) : ?>
                        <span class="hcjm-sch-team-us"><?php esc_html_e( 'HC Junior Mělník', HCJM_TEXT_DOMAIN ); ?></span>
                        <span class="hcjm-sch-vs">vs.</span>
                        <span class="hcjm-sch-team-opp"><?php echo esc_html( $match->opponent ); ?></span>
                    <?php else : ?>
                        <span class="hcjm-sch-team-opp"><?php echo esc_html( $match->opponent ); ?></span>
                        <span class="hcjm-sch-vs">vs.</span>
                        <span class="hcjm-sch-team-us"><?php esc_html_e( 'HC Junior Mělník', HCJM_TEXT_DOMAIN ); ?></span>
                    <?php endif; ?>
                </span>
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

    pills.forEach(function(pill){
        pill.addEventListener('click', function(){
            pills.forEach(function(p){ p.classList.remove('active'); });
            pill.classList.add('active');
            var filter = pill.dataset.filter;
            rows.forEach(function(row){
                if (filter === 'all' || row.dataset.teamId === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
})();
</script>

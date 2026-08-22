<?php
/**
 * Template: [hcjm_next_match]
 *
 * Available variables:
 *   $matches    object[]   Upcoming matches (DB rows)
 *   $category   string     Team/category label, e.g. "Dorost"
 *   $countdown  bool       Whether to show the countdown timer
 *   $count      int        Max matches to show
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $matches ) ) {
    ?>
    <div class="hcjm hcjm-next-match-empty">
        <p><?php esc_html_e( 'Žádné nadcházející zápasy.', HCJM_TEXT_DOMAIN ); ?></p>
    </div>
    <?php
    return;
}

$days_cs = [
    'Mon' => 'Po', 'Tue' => 'Út', 'Wed' => 'St',
    'Thu' => 'Čt', 'Fri' => 'Pá', 'Sat' => 'So', 'Sun' => 'Ne',
];

$months_cs = [
    1 => 'ledna', 2 => 'února', 3 => 'března', 4 => 'dubna',
    5 => 'května', 6 => 'června', 7 => 'července', 8 => 'srpna',
    9 => 'září', 10 => 'října', 11 => 'listopadu', 12 => 'prosince',
];

$uid = 'hcjm-nm-' . wp_generate_password( 6, false, false );
?>
<div class="hcjm hcjm-next-match-carousel" id="<?php echo esc_attr( $uid ); ?>" data-active="0">

    <?php foreach ( $matches as $index => $match ) :
        $ts       = $match->match_date ? strtotime( $match->match_date ) : 0;
        $day_abbr = $ts ? ( $days_cs[ date( 'D', $ts ) ] ?? '' ) : '';
        $day_num  = $ts ? (int) date( 'j', $ts ) : '';
        $month    = $ts ? ( $months_cs[ (int) date( 'n', $ts ) ] ) : '';
        $year     = $ts ? date( 'Y', $ts ) : '';
        $time_str = ( $ts && date( 'H:i', $ts ) !== '00:00' ) ? date( 'H:i', $ts ) : '';

        $opponent     = esc_html( $match->opponent );
        $opp_logo_url = esc_url( HCJM_Opponents::get_logo_url_by_name( $match->opponent, 'medium' ) );
        $is_home      = (bool) $match->is_home;

        $match_iso = $ts ? date( 'c', $ts ) : '';
    ?>
    <div class="hcjm-nm-slide<?php echo $index === 0 ? ' active' : ''; ?>"
         data-ts="<?php echo esc_attr( $match_iso ); ?>">

        <div class="hcjm-nm-category"><?php echo esc_html( $category ); ?></div>

        <div class="hcjm-nm-matchup">
            <?php if ( $is_home ) : ?>
                <div class="hcjm-nm-team hcjm-nm-team-us">
                    <div class="hcjm-nm-club-logo">
                        <svg viewBox="0 0 48 48" fill="none" aria-hidden="true" class="hcjm-nm-logo-svg">
                            <circle cx="24" cy="24" r="23" fill="rgba(255,255,255,.12)" stroke="rgba(255,255,255,.3)" stroke-width="1.5"/>
                            <text x="24" y="30" text-anchor="middle" fill="white" font-size="14" font-weight="900" font-family="inherit">HCJ</text>
                        </svg>
                    </div>
                    <span class="hcjm-nm-teamlabel">HC Junior Mělník</span>
                </div>
                <div class="hcjm-nm-vs"><span>vs</span></div>
                <div class="hcjm-nm-team hcjm-nm-team-opp">
                    <div class="hcjm-nm-opp-logo">
                        <?php if ( $opp_logo_url ) : ?>
                            <img src="<?php echo $opp_logo_url; ?>" alt="<?php echo $opponent; ?>" class="hcjm-nm-logo-img">
                        <?php else : ?>
                            <svg viewBox="0 0 48 48" fill="none" aria-hidden="true" class="hcjm-nm-logo-svg">
                                <circle cx="24" cy="24" r="23" fill="rgba(255,255,255,.08)" stroke="rgba(255,255,255,.2)" stroke-width="1.5"/>
                                <text x="24" y="30" text-anchor="middle" fill="rgba(255,255,255,.6)" font-size="10" font-weight="700" font-family="inherit"><?php echo mb_strtoupper( mb_substr( $match->opponent, 0, 3 ) ); ?></text>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <span class="hcjm-nm-teamlabel"><?php echo $opponent; ?></span>
                </div>
            <?php else : ?>
                <div class="hcjm-nm-team hcjm-nm-team-opp">
                    <div class="hcjm-nm-opp-logo">
                        <?php if ( $opp_logo_url ) : ?>
                            <img src="<?php echo $opp_logo_url; ?>" alt="<?php echo $opponent; ?>" class="hcjm-nm-logo-img">
                        <?php else : ?>
                            <svg viewBox="0 0 48 48" fill="none" aria-hidden="true" class="hcjm-nm-logo-svg">
                                <circle cx="24" cy="24" r="23" fill="rgba(255,255,255,.08)" stroke="rgba(255,255,255,.2)" stroke-width="1.5"/>
                                <text x="24" y="30" text-anchor="middle" fill="rgba(255,255,255,.6)" font-size="10" font-weight="700" font-family="inherit"><?php echo mb_strtoupper( mb_substr( $match->opponent, 0, 3 ) ); ?></text>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <span class="hcjm-nm-teamlabel"><?php echo $opponent; ?></span>
                </div>
                <div class="hcjm-nm-vs"><span>vs</span></div>
                <div class="hcjm-nm-team hcjm-nm-team-us">
                    <div class="hcjm-nm-club-logo">
                        <svg viewBox="0 0 48 48" fill="none" aria-hidden="true" class="hcjm-nm-logo-svg">
                            <circle cx="24" cy="24" r="23" fill="rgba(255,255,255,.12)" stroke="rgba(255,255,255,.3)" stroke-width="1.5"/>
                            <text x="24" y="30" text-anchor="middle" fill="white" font-size="14" font-weight="900" font-family="inherit">HCJ</text>
                        </svg>
                    </div>
                    <span class="hcjm-nm-teamlabel">HC Junior Mělník</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="hcjm-nm-datetime">
            <span class="hcjm-nm-badge <?php echo $is_home ? 'hcjm-nm-badge-home' : 'hcjm-nm-badge-away'; ?>">
                <?php echo $is_home ? esc_html__( 'Domácí', HCJM_TEXT_DOMAIN ) : esc_html__( 'Hosté', HCJM_TEXT_DOMAIN ); ?>
            </span>
            <?php if ( $ts ) : ?>
                <span class="hcjm-nm-date">
                    <?php echo esc_html( $day_abbr . ' ' . $day_num . '. ' . $month . ' ' . $year ); ?>
                </span>
                <?php if ( $time_str ) : ?>
                    <span class="hcjm-nm-time"><?php echo esc_html( $time_str ); ?></span>
                <?php endif; ?>
            <?php else : ?>
                <span class="hcjm-nm-date"><?php esc_html_e( 'Datum bude upřesněno', HCJM_TEXT_DOMAIN ); ?></span>
            <?php endif; ?>
        </div>

        <?php if ( $countdown && $ts > time() ) : ?>
        <div class="hcjm-nm-countdown" data-target="<?php echo esc_attr( $match_iso ); ?>">
            <div class="hcjm-nm-cd-unit">
                <span class="hcjm-nm-cd-value" data-unit="d">–</span>
                <span class="hcjm-nm-cd-label"><?php esc_html_e( 'dní', HCJM_TEXT_DOMAIN ); ?></span>
            </div>
            <div class="hcjm-nm-cd-sep">:</div>
            <div class="hcjm-nm-cd-unit">
                <span class="hcjm-nm-cd-value" data-unit="h">–</span>
                <span class="hcjm-nm-cd-label"><?php esc_html_e( 'hod', HCJM_TEXT_DOMAIN ); ?></span>
            </div>
            <div class="hcjm-nm-cd-sep">:</div>
            <div class="hcjm-nm-cd-unit">
                <span class="hcjm-nm-cd-value" data-unit="m">–</span>
                <span class="hcjm-nm-cd-label"><?php esc_html_e( 'min', HCJM_TEXT_DOMAIN ); ?></span>
            </div>
            <div class="hcjm-nm-cd-sep">:</div>
            <div class="hcjm-nm-cd-unit">
                <span class="hcjm-nm-cd-value" data-unit="s">–</span>
                <span class="hcjm-nm-cd-label"><?php esc_html_e( 'sek', HCJM_TEXT_DOMAIN ); ?></span>
            </div>
        </div>
        <?php elseif ( $countdown ) : ?>
        <div class="hcjm-nm-started"><?php esc_html_e( 'Zápas právě probíhá nebo již skončil.', HCJM_TEXT_DOMAIN ); ?></div>
        <?php endif; ?>

    </div>
    <?php endforeach; ?>

    <?php if ( count( $matches ) > 1 ) : ?>
    <div class="hcjm-nm-nav">
        <button class="hcjm-nm-prev" aria-label="<?php esc_attr_e( 'Předchozí', HCJM_TEXT_DOMAIN ); ?>">&#8592;</button>
        <div class="hcjm-nm-dots">
            <?php foreach ( $matches as $i => $_ ) : ?>
                <button class="hcjm-nm-dot<?php echo $i === 0 ? ' active' : ''; ?>" data-idx="<?php echo $i; ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Zápas %d', HCJM_TEXT_DOMAIN ), $i + 1 ) ); ?>"></button>
            <?php endforeach; ?>
        </div>
        <button class="hcjm-nm-next" aria-label="<?php esc_attr_e( 'Další', HCJM_TEXT_DOMAIN ); ?>">&#8594;</button>
    </div>
    <?php endif; ?>
</div>

<script>
(function(){
    var wrap = document.getElementById(<?php echo wp_json_encode( $uid ); ?>);
    if (!wrap) return;

    // Carousel
    var slides = wrap.querySelectorAll('.hcjm-nm-slide');
    var dots   = wrap.querySelectorAll('.hcjm-nm-dot');
    var active = 0;

    function goTo(idx){
        slides[active].classList.remove('active');
        if (dots[active]) dots[active].classList.remove('active');
        active = (idx + slides.length) % slides.length;
        slides[active].classList.add('active');
        if (dots[active]) dots[active].classList.add('active');
    }

    var prev = wrap.querySelector('.hcjm-nm-prev');
    var next = wrap.querySelector('.hcjm-nm-next');
    if (prev) prev.addEventListener('click', function(){ goTo(active - 1); });
    if (next) next.addEventListener('click', function(){ goTo(active + 1); });
    dots.forEach(function(dot){ dot.addEventListener('click', function(){ goTo(parseInt(this.dataset.idx)); }); });

    // Countdown timers
    function pad(n){ return n < 10 ? '0'+n : String(n); }

    function updateCountdown(el){
        var target = new Date(el.dataset.target).getTime();
        var now    = Date.now();
        var diff   = target - now;
        if (diff <= 0) {
            el.innerHTML = '<span class="hcjm-nm-started"><?php echo esc_js( __( 'Zápas právě probíhá nebo již skončil.', HCJM_TEXT_DOMAIN ) ); ?></span>';
            return;
        }
        var d = Math.floor(diff / 86400000);
        var h = Math.floor((diff % 86400000) / 3600000);
        var m = Math.floor((diff % 3600000) / 60000);
        var s = Math.floor((diff % 60000) / 1000);
        el.querySelector('[data-unit="d"]').textContent = pad(d);
        el.querySelector('[data-unit="h"]').textContent = pad(h);
        el.querySelector('[data-unit="m"]').textContent = pad(m);
        el.querySelector('[data-unit="s"]').textContent = pad(s);
    }

    var countdowns = wrap.querySelectorAll('.hcjm-nm-countdown');
    if (countdowns.length) {
        countdowns.forEach(updateCountdown);
        setInterval(function(){ countdowns.forEach(updateCountdown); }, 1000);
    }
})();
</script>

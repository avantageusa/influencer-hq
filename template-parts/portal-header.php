<?php
/**
 * Portal Header Template Part
 * 
 * Displays the sticky header with search bar, navigation, and hamburger menu
 * for all portal pages.
 *
 * @package Avantage_Baccarat
 */
?>

<!-- Search Bar -->
<div class="search-bar-container">
    <div class="container" style="max-width: 1024px; padding-left: 20px; padding-right: 20px;">
        <div class="search-bar">
            <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input type="text" class="search-input" placeholder="Search...">
        </div>
    </div>
</div>

<!-- Sticky Header: Hamburger, Logo, Go To Game -->
<div class="sticky-header">
    <div class="container" style="max-width: 1024px; padding-left: 20px; padding-right: 20px; margin-top: 20px; padding-bottom: 30px;">
        <div class="d-flex align-items-center">
            <div style="flex: 1; display: flex; align-items: center; gap: 12px;">
                <button type="button" class="hamburger-menu bg-transparent border-0 p-0" id="hamburgerMenuBtn" aria-expanded="false" aria-controls="hamburgerDropdown" aria-label="Open menu">
                    <svg class="hamburger-menu-icon" width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <line x1="3" y1="6" x2="21" y2="6" stroke="#b7962f" stroke-width="2" stroke-linecap="round"/>
                        <line x1="3" y1="12" x2="21" y2="12" stroke="#b7962f" stroke-width="2" stroke-linecap="round"/>
                        <line x1="3" y1="18" x2="21" y2="18" stroke="#b7962f" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
                <!-- Desktop-only: Help & Language -->
                <div class="desktop-header-left-items">
                    <a href="<?php echo esc_url( home_url('/portal/portal-home/') ); ?>?open=concierge" class="header-help-btn">Help</a>
                    <div class="header-lang-wrap">
                        <button class="header-lang-btn" id="headerLangBtn" aria-label="Select Language">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#E6CFA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="2" y1="12" x2="22" y2="12"></line>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                            </svg>
                        </button>
                        <div class="header-lang-dropdown" id="headerLangDropdown">
                            <a href="#" class="header-lang-option">English</a>
                            <a href="#" class="header-lang-option">Español</a>
                            <a href="#" class="header-lang-option">Français</a>
                            <a href="#" class="header-lang-option">Deutsch</a>
                            <a href="#" class="header-lang-option">中文</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="logo-container text-center">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/logo-tm.png" alt="influencerHQ" class="img-fluid">
                </a>
            </div>
            <?php
            $uid        = get_current_user_id();
            $first_name = get_user_meta( $uid, 'first_name', true );
            $last_name  = get_user_meta( $uid, 'last_name',  true );
            $email      = wp_get_current_user()->user_email;
            $avatar_url = get_avatar_url( $uid, array( 'size' => 200 ) );
            $default_game_url = 'https://qc-game-portal-client-tf-b2c.dev.ae.games/av-baccarat';
            $meta_game_url   = get_user_meta( $uid, 'hq_game_url', true );
            $base_game_url   = ( $meta_game_url !== '' ) ? $meta_game_url : $default_game_url;
            $game_url   = add_query_arg( array(
                'influencerHqAuth' => 'true',
                'hqId'             => 'wpu-' . $uid,
                'hqFirstName'      => $first_name,
                'hqLastName'       => $last_name,
                'hqEmail'          => $email,
                'hqAvatar'         => $avatar_url,
            ), $base_game_url );
            ?>
            <div style="flex: 1; display: flex; align-items: center; justify-content: flex-end; gap: 14px;">
                <!-- Desktop-only: Volume, Login, Register -->
                <div class="desktop-header-right-items">
                    <div class="header-volume-wrap">
                        <button class="header-volume-btn" id="headerVolumeBtn" aria-label="Volume">
                            <svg class="header-volume-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                                <path d="M19.07 4.93a10 10 0 0 1 0 14.14"></path>
                                <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                            </svg>
                        </button>
                        <input type="range" class="header-volume-slider" id="headerVolumeSlider" min="0" max="100" value="70" aria-label="Volume">
                    </div>
                    <a href="<?php echo esc_url( home_url('/influencer-login/') ); ?>" class="header-login-link">Login</a>
                    <a href="<?php echo esc_url( home_url('/influencer-login/') ); ?>" class="header-register-btn">Register Now</a>
                </div>
                <a href="<?php echo esc_url( $game_url ); ?>" target="_blank" rel="noopener noreferrer" class="go-to-game-btn">PLAY</a>
            </div>
        </div>
    </div>
</div>

<!-- Sticky Navigation Tabs -->
<div class="sticky-nav">
    <div class="container" style="max-width: 1024px; padding-left: 20px; padding-right: 20px;">
        <div class="navigation-wrapper">
            <div class="nav-row-1 text-center mb-2">
                <a href="<?php echo home_url('/portal-home'); ?>" class="nav-link-inline <?php echo (is_page('portal-home')) ? 'active' : ''; ?>">Executive Concierge</a>
                <span class="nav-separator">|</span>
                <a href="<?php echo home_url('/portal/equity'); ?>" class="nav-link-inline <?php echo (is_page('portal/equity')) ? 'active' : ''; ?>">Equity</a>
                <span class="nav-separator">|</span>
                <a href="<?php echo home_url('/portal/challenges'); ?>" class="nav-link-inline <?php echo (is_page('portal/challenges')) ? 'active' : ''; ?>">Competition</a>
                <span class="nav-separator">|</span>
                <a href="<?php echo home_url('/portal/live'); ?>" class="nav-link-inline <?php echo (is_page('portal/live')) ? 'active' : ''; ?>">Live Appearance</a>
                <span class="nav-separator">|</span>
                
                <a href="<?php echo home_url('/portal/account'); ?>" class="nav-link-inline <?php echo (is_page('portal/account')) ? 'active' : ''; ?>">Settings</a>
                <span class="nav-separator">|</span>
                <a href="<?php echo home_url('/portal/more'); ?>" class="nav-link-inline <?php echo (is_page('portal/more')) ? 'active' : ''; ?>">More</a>
            </div>
        </div>
    </div>
</div>

<?php
$hm_ic = function ( $file ) {
    return esc_url( get_template_directory_uri() . '/images/hamburger-icons/' . $file );
};
$hm_ch = function ( $tab, $hash = '' ) {
    $u = esc_url( add_query_arg( 'tab', $tab, home_url( '/portal/challenges/' ) ) );
    if ( $hash !== '' ) {
        $u .= '#' . $hash;
    }
    return $u;
};
?>
<div class="hamburger-overlay" id="hamburgerOverlay" aria-hidden="true"></div>
<div class="hamburger-dropdown" id="hamburgerDropdown" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'Main navigation', 'avantage-baccarat' ); ?>">
    <div class="hamburger-drawer-scroll">
        <div class="hamburger-drawer-logo-wrap">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                <img src="<?php echo esc_url( get_template_directory_uri() . '/images/logo-tm.png' ); ?>" alt="influencerHQ" class="hamburger-drawer-logo">
            </a>
        </div>
        <nav class="hm-nav" aria-label="<?php echo esc_attr__( 'Portal navigation', 'avantage-baccarat' ); ?>">
            <div class="hm-top-link-wrap">
                <a href="<?php echo esc_url( home_url( '/portal-home' ) ); ?>" class="hm-top-link <?php echo ( is_page( 'portal-home' ) ) ? 'is-active' : ''; ?>">
                    <img class="hm-ico-img" src="<?php echo $hm_ic( 'icon-concierge.png' ); ?>" width="19" height="19" alt="">
                    <span><?php esc_html_e( 'Executive Concierge', 'avantage-baccarat' ); ?></span>
                </a>
            </div>

            <section class="hm-section">
                <a href="<?php echo esc_url( home_url( '/portal/equity' ) ); ?>" class="hm-sum">
                    <img class="hm-ico-img" src="<?php echo $hm_ic( 'icon-equity.png' ); ?>" width="19" height="19" alt="">
                    <span><?php esc_html_e( 'Equity', 'avantage-baccarat' ); ?></span>
                </a>
                <div class="hm-details-body">
                    <a href="<?php echo esc_url( home_url( '/portal/equity' ) ); ?>#equity-results" class="hm-link hm-bullet"><?php esc_html_e( 'Equity results', 'avantage-baccarat' ); ?></a>
                    <a href="<?php echo esc_url( home_url( '/portal/equity' ) ); ?>#equity-referrals" class="hm-link hm-bullet"><?php esc_html_e( 'My Referrals results', 'avantage-baccarat' ); ?></a>
                </div>
            </section>

            <section class="hm-section">
                <a href="<?php echo esc_url( home_url( '/portal/challenges/' ) ); ?>" class="hm-sum">
                    <img class="hm-ico-img" src="<?php echo $hm_ic( 'icon-competitions.png' ); ?>" width="19" height="19" alt="">
                    <span><?php esc_html_e( 'Competitions', 'avantage-baccarat' ); ?></span>
                </a>
                <div class="hm-details-body hm-tree">
                    <a href="<?php echo $hm_ch( 'private' ); ?>" class="hm-subhead hm-subhead-link">
                        <img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-private.png' ); ?>" width="19" height="19" alt="">
                        <span><?php esc_html_e( 'Private', 'avantage-baccarat' ); ?></span>
                    </a>
                    <a href="<?php echo $hm_ch( 'private', 'cpc-create-btn' ); ?>" class="hm-link hm-bullet hm-indent-1"><?php esc_html_e( 'Create Private Challenge', 'avantage-baccarat' ); ?></a>
                    <a href="<?php echo $hm_ch( 'private', 'private-results' ); ?>" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-chart.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Results', 'avantage-baccarat' ); ?></a>
                    <a href="<?php echo $hm_ch( 'private', 'private-leaderboards' ); ?>" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-leaderboard.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Leaderboards', 'avantage-baccarat' ); ?></a>
                    <a href="<?php echo $hm_ch( 'private', 'private-influencer' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-influencer.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Influencer', 'avantage-baccarat' ); ?></a>
                    <a href="<?php echo $hm_ch( 'private', 'private-follower' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-follower.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Follower', 'avantage-baccarat' ); ?></a>

                    <a href="<?php echo $hm_ch( 'community' ); ?>" class="hm-subhead hm-subhead-link">
                        <img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-community.png' ); ?>" width="16" height="16" alt="">
                        <span><?php esc_html_e( 'Community', 'avantage-baccarat' ); ?></span>
                    </a>
                    <a href="<?php echo $hm_ch( 'community', 'community-results' ); ?>" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-chart.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Results', 'avantage-baccarat' ); ?></a>
                    <a href="<?php echo $hm_ch( 'community', 'community-leaderboards' ); ?>" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-leaderboard.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Leaderboards', 'avantage-baccarat' ); ?></a>
                    <a href="<?php echo $hm_ch( 'community', 'community-influencer' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-influencer.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Influencer', 'avantage-baccarat' ); ?></a>
                    <a href="<?php echo $hm_ch( 'community', 'community-follower' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-follower.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Follower', 'avantage-baccarat' ); ?></a>

                    <a href="<?php echo $hm_ch( 'world' ); ?>" class="hm-subhead hm-subhead-link">
                        <img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-globe.png' ); ?>" width="16" height="16" alt="">
                        <span><?php esc_html_e( 'World', 'avantage-baccarat' ); ?></span>
                    </a>
                    <a href="<?php echo $hm_ch( 'world', 'world-results' ); ?>" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-chart.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Results', 'avantage-baccarat' ); ?></a>
                    <a href="<?php echo $hm_ch( 'world', 'world-leaderboards' ); ?>" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-leaderboard.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Leaderboards', 'avantage-baccarat' ); ?></a>
                    <a href="<?php echo $hm_ch( 'world', 'world-influencer' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-influencer.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Influencer', 'avantage-baccarat' ); ?></a>
                    <a href="<?php echo $hm_ch( 'world', 'world-follower' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-follower.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Follower', 'avantage-baccarat' ); ?></a>

                    <a href="<?php echo $hm_ch( 'leagues' ); ?>" class="hm-subhead hm-subhead-link">
                        <img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-leagues.png' ); ?>" width="16" height="16" alt="">
                        <span><?php esc_html_e( 'Leagues', 'avantage-baccarat' ); ?></span>
                    </a>
                    <a href="<?php echo $hm_ch( 'leagues', 'leagues-international' ); ?>" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-globe-sm.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'International League', 'avantage-baccarat' ); ?></a>
                    <div class="hm-nested-block hm-indent-1">
                        <span class="hm-celeb-label"><?php esc_html_e( 'Celebrity Follower Leagues', 'avantage-baccarat' ); ?></span>
                        <a href="<?php echo $hm_ch( 'leagues', 'leagues-movie-stars' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-movie.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Movie Stars', 'avantage-baccarat' ); ?></a>
                        <a href="<?php echo $hm_ch( 'leagues', 'leagues-music-artists' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-music.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Music Artists', 'avantage-baccarat' ); ?></a>
                        <a href="<?php echo $hm_ch( 'leagues', 'leagues-sports-icons' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-sport.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Sports Icons', 'avantage-baccarat' ); ?></a>
                    </div>
                </div>
            </section>

            <section class="hm-section">
                <a href="<?php echo esc_url( home_url( '/portal/live' ) ); ?>" class="hm-sum">
                    <img class="hm-ico-img" src="<?php echo $hm_ic( 'icon-live.png' ); ?>" width="19" height="19" alt="">
                    <span><?php esc_html_e( 'Live Appearance', 'avantage-baccarat' ); ?></span>
                </a>
                <div class="hm-details-body">
                    <a href="<?php echo esc_url( home_url( '/portal/live' ) ); ?>#live-request" class="hm-link hm-bullet"><?php esc_html_e( 'Request A Live Appearance', 'avantage-baccarat' ); ?></a>
                    <a href="<?php echo esc_url( home_url( '/portal/live' ) ); ?>#kick-schedule" class="hm-link hm-bullet"><?php esc_html_e( 'Report KICK Broadcast Schedule', 'avantage-baccarat' ); ?></a>
                </div>
            </section>

            <div class="hm-top-link-wrap hm-top-link-wrap--spaced">
                <a href="<?php echo esc_url( home_url( '/portal/account' ) ); ?>" class="hm-top-link <?php echo ( is_page( 'portal/account' ) ) ? 'is-active' : ''; ?>">
                    <img class="hm-ico-img" src="<?php echo $hm_ic( 'icon-profile.png' ); ?>" width="19" height="19" alt="">
                    <span><?php esc_html_e( 'Profile', 'avantage-baccarat' ); ?></span>
                </a>
                <a href="<?php echo esc_url( home_url( '/portal/more' ) ); ?>" class="hm-top-link <?php echo ( is_page( 'portal/more' ) ) ? 'is-active' : ''; ?>">
                    <img class="hm-ico-img" src="<?php echo $hm_ic( 'icon-more.png' ); ?>" width="19" height="19" alt="">
                    <span><?php esc_html_e( 'More', 'avantage-baccarat' ); ?></span>
                </a>
            </div>
        </nav>
    </div>
</div>
<script>
(function () {
    // Language dropdown toggle
    var langBtn = document.getElementById('headerLangBtn');
    var langDropdown = document.getElementById('headerLangDropdown');
    if (langBtn && langDropdown) {
        langBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            langDropdown.classList.toggle('open');
        });
        document.addEventListener('click', function () {
            langDropdown.classList.remove('open');
        });
    }

    // Auto-click .concierge-title is handled in page-portal-home.php

    // Volume slider toggle
    var volBtn = document.getElementById('headerVolumeBtn');
    var volSlider = document.getElementById('headerVolumeSlider');
    if (volBtn && volSlider) {
        volBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            volSlider.classList.toggle('open');
        });
        document.addEventListener('click', function () {
            volSlider.classList.remove('open');
        });
        volSlider.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }
}());

(function () {
    function adjustContentPadding() {
        var stickyNav    = document.querySelector('.sticky-nav');
        var stickyHeader = document.querySelector('.sticky-header');
        var searchBar    = document.querySelector('.search-bar-container');
        var content      = document.getElementById('portal-content');
        if (!content) return;

        var bottom = 0;
        if (stickyNav)    bottom = stickyNav.getBoundingClientRect().bottom;
        else if (stickyHeader) bottom = stickyHeader.getBoundingClientRect().bottom;
        else if (searchBar)    bottom = searchBar.getBoundingClientRect().bottom;

        

        if (bottom > 0) content.style.setProperty('padding-top', (bottom + 20) + 'px', 'important');
    }

    document.addEventListener('DOMContentLoaded', adjustContentPadding);
    window.addEventListener('load', adjustContentPadding);

    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(adjustContentPadding, 80);
    });
}());

(function () {
    function initAccordionNavButtons() {
        if (window.innerWidth <= 1024) return;
        // Find every accordion group (each direct accordion wrapper)
        var accordions = document.querySelectorAll('.accordion.custom-accordion, #equityAccordion');
        accordions.forEach(function (accordion) {
            var panels = accordion.querySelectorAll('.accordion-collapse');
            panels.forEach(function (panel, index) {
                var body = panel.querySelector('.accordion-body');
                if (!body) return;

                var wrap = document.createElement('div');
                wrap.className = 'accordion-nav-btns';

                if (index > 0) {
                    var prevBtn = document.createElement('button');
                    prevBtn.className = 'accordion-prev-btn';
                    prevBtn.textContent = 'Previous';
                    prevBtn.addEventListener('click', function () {
                        var prevPanel = panels[index - 1];
                        bootstrap.Collapse.getOrCreateInstance(prevPanel).show();
                    });
                    wrap.appendChild(prevBtn);
                }

                if (index < panels.length - 1) {
                    var nextBtn = document.createElement('button');
                    nextBtn.className = 'accordion-next-btn';
                    nextBtn.textContent = 'Next';
                    nextBtn.addEventListener('click', function () {
                        var nextPanel = panels[index + 1];
                        bootstrap.Collapse.getOrCreateInstance(nextPanel).show();
                    });
                    wrap.appendChild(nextBtn);
                }

                if (wrap.hasChildNodes()) {
                    body.appendChild(wrap);
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAccordionNavButtons);
    } else {
        initAccordionNavButtons();
    }
}());
</script>
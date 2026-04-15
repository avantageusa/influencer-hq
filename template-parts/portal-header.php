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
                <button class="hamburger-menu bg-transparent border-0 p-0">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#3B9FFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
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
                <img src="<?php echo get_template_directory_uri(); ?>/images/logo-tm.png" alt="influencerHQ" class="img-fluid">
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
                
                <a href="<?php echo home_url('/portal/account'); ?>" class="nav-link-inline <?php echo (is_page('portal/account')) ? 'active' : ''; ?>">Profile</a>
                <span class="nav-separator">|</span>
                <a href="<?php echo home_url('/portal/more'); ?>" class="nav-link-inline <?php echo (is_page('portal/more')) ? 'active' : ''; ?>">More</a>
            </div>
        </div>
    </div>
</div>

<!-- Hamburger Dropdown Menu -->
<div class="hamburger-dropdown" id="hamburgerDropdown">
    <ul class="dropdown-menu">
        <li><a href="<?php echo home_url('/portal-home'); ?>" class="dropdown-link <?php echo (is_page('portal-home')) ? 'active' : ''; ?>"><i class="icon-home"></i> Executive Concierge</a></li>
        <li><a href="<?php echo home_url('/portal/equity'); ?>" class="dropdown-link <?php echo (is_page('portal/equity')) ? 'active' : ''; ?>"><i class="icon-equity"></i> Equity</a></li>
        <li><a href="<?php echo home_url('/portal/challenges'); ?>" class="dropdown-link <?php echo (is_page('portal/challenges')) ? 'active' : ''; ?>"><i class="icon-challenges"></i> Competition</a></li>
        <li><a href="<?php echo home_url('/portal/live'); ?>" class="dropdown-link <?php echo (is_page('portal/live')) ? 'active' : ''; ?>"><i class="icon-live"></i> Live Appearance</a></li>
        
        <li><a href="<?php echo home_url('/portal/rankings'); ?>" class="dropdown-link <?php echo (is_page('portal/rankings')) ? 'active' : ''; ?>"><i class="icon-ranking"></i> Rankings</a></li>
        <li><a href="<?php echo home_url('/portal/account'); ?>" class="dropdown-link <?php echo (is_page('portal/account')) ? 'active' : ''; ?>"><i class="icon-profile"></i> Profile</a></li>
        <li><a href="<?php echo home_url('/portal/more'); ?>" class="dropdown-link <?php echo (is_page('portal/more')) ? 'active' : ''; ?>"><i class="icon-more"></i> More</a></li>
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
</script>
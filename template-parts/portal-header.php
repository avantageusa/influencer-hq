<?php
/**
 * Portal Header Template Part
 *
 * Displays the sticky header, navigation, and hamburger menu
 * for all portal pages.
 *
 * Portal zone crawl control: noindex/nofollow meta + X-Robots-Tag on all
 * /portal/* (and portal-home) requests. Hooks register when this file is
 * loaded from functions.php ($ihq_portal_header_include_body = false) before output.
 *
 * @package influencer-hq
 */

if ( ! defined( 'IHQ_PORTAL_ZONE_ROBOTS_HOOKS_REGISTERED' ) ) {
	define( 'IHQ_PORTAL_ZONE_ROBOTS_HOOKS_REGISTERED', true );

	/**
	 * Whether the current front-end request is in the portal URL zone.
	 *
	 * @return bool
	 */
	function ihq_portal_zone_request_is_portal_path() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		$page_id = get_queried_object_id();
		if ( $page_id > 0 ) {
			$template = get_page_template_slug( $page_id );
			if ( is_string( $template ) && strpos( $template, 'page-portal-' ) === 0 ) {
				return true;
			}
		}

		if ( function_exists( 'ihq_portal_turnstile_normalized_path' ) ) {
			$path = ihq_portal_turnstile_normalized_path();
		} else {
			$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
			$path = strtolower( untrailingslashit( (string) parse_url( $uri, PHP_URL_PATH ) ) );
			$site_path = (string) parse_url( home_url( '/' ), PHP_URL_PATH );
			$site_path = strtolower( untrailingslashit( $site_path ) );
			if ( $site_path !== '' && strpos( $path, $site_path ) === 0 ) {
				$path = substr( $path, strlen( $site_path ) );
			}
			$path = ltrim( $path, '/' );
		}

		if ( $path === 'portal-home' || strpos( $path, 'portal-home/' ) === 0 ) {
			return true;
		}

		if ( $path === 'portal' || strpos( $path, 'portal/' ) === 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * @return void
	 */
	function ihq_portal_zone_robots_send_x_robots_tag() {
		if ( ! ihq_portal_zone_request_is_portal_path() ) {
			return;
		}

		if ( headers_sent() ) {
			return;
		}

		header( 'X-Robots-Tag: noindex, nofollow', true );
	}

	/**
	 * @return void
	 */
	function ihq_portal_zone_robots_echo_meta() {
		if ( ! ihq_portal_zone_request_is_portal_path() ) {
			return;
		}

		echo '<meta name="robots" content="noindex, nofollow">';
	}

	add_action( 'send_headers', 'ihq_portal_zone_robots_send_x_robots_tag', 1 );
	add_action( 'wp_head', 'ihq_portal_zone_robots_echo_meta', 1 );
}

global $ihq_portal_header_include_body;
if ( isset( $ihq_portal_header_include_body ) && ! $ihq_portal_header_include_body ) {
	return;
}

?>

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
                    <div class="header-lang-wrap" style="display:none" aria-hidden="true">
                        <button class="header-lang-btn" id="headerLangBtn" aria-label="Select Language">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#E6CFA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="2" y1="12" x2="22" y2="12"></line>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                            </svg>
                        </button>
                        <div class="header-lang-dropdown" id="headerLangDropdown">
                            <a href="#" class="header-lang-option" data-lang="en">English</a>
                            <a href="#" class="header-lang-option" data-lang="es">Español</a>
                            <a href="#" class="header-lang-option" data-lang="fr">Français</a>
                            <a href="#" class="header-lang-option" data-lang="de">Deutsch</a>
                            <a href="#" class="header-lang-option" data-lang="zh">中文</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="logo-container text-center">
                <a href="<?php echo esc_url( home_url('/portal/portal-home/') ); ?>">
                <img src="<?php echo get_template_directory_uri(); ?>/images/logo-home-claude.jpg" alt="influencerHQ" class="img-fluid">
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
            $game_url_args     = array(
                'influencerHqAuth' => 'true',
                'hqId'             => 'wpu-' . $uid,
                'hqFirstName'      => $first_name,
                'hqLastName'       => $last_name,
                'hqEmail'          => $email,
                'hqAvatar'         => $avatar_url,
            );
            if ( $uid > 0 && function_exists( 'ihq_get_hq_sso_code_for_user' ) ) {
                $hq_sso_code = ihq_get_hq_sso_code_for_user( $uid );
                if ( $hq_sso_code !== '' ) {
                    $game_url_args['hqSsoCode'] = $hq_sso_code;
                }
            }
            $game_url = add_query_arg( $game_url_args, $base_game_url );
            ?>
            <div style="flex: 1; display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
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
                    <?php if ( is_user_logged_in() ) : ?>
                        <a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>" class="header-logout-btn"><?php esc_html_e( 'Log out', 'influencer-hq' ); ?></a>
                    <?php else : ?>
                        <button type="button" class="header-login-link portal-header-auth-trigger" id="portalHeaderOpenLogin" data-auth-tab="login"><?php esc_html_e( 'Login', 'influencer-hq' ); ?></button>
                    <?php endif; ?>
                </div>
                <a href="<?php echo esc_url( $game_url ); ?>" target="_blank" rel="noopener noreferrer" style="display:none;"class="go-to-game-btn">PLAY</a>
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
<div class="hamburger-dropdown" id="hamburgerDropdown" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'Main navigation', 'influencer-hq' ); ?>">
    <div class="hamburger-drawer-scroll">
        <div class="hamburger-drawer-logo-wrap">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                <img src="<?php echo esc_url( get_template_directory_uri() . '/images/new-logo-under.png' ); ?>" alt="influencerHQ" class="hamburger-drawer-logo">
            </a>
        </div>
        <nav class="hm-nav" aria-label="<?php echo esc_attr__( 'Portal navigation', 'influencer-hq' ); ?>">
            <div class="hm-top-link-wrap">
                <a href="<?php echo esc_url( home_url( '/portal-home' ) ); ?>" class="hm-top-link <?php echo ( is_page( 'portal-home' ) ) ? 'is-active' : ''; ?>">
                    <img class="hm-ico-img" src="<?php echo $hm_ic( 'icon-concierge.png' ); ?>" width="19" height="19" alt="">
                    <span><?php esc_html_e( 'Executive Concierge', 'influencer-hq' ); ?></span>
                </a>
            </div>

            <section class="hm-section">
                <a href="<?php echo esc_url( home_url( '/portal/equity' ) ); ?>" class="hm-sum">
                    <img class="hm-ico-img" src="<?php echo $hm_ic( 'icon-equity.png' ); ?>" width="19" height="19" alt="">
                    <span><?php esc_html_e( 'Equity', 'influencer-hq' ); ?></span>
                </a>
                <div class="hm-details-body">
                    <a href="<?php echo esc_url( home_url( '/portal/equity' ) ); ?>#equity-results" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-equity-results.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Equity results', 'influencer-hq' ); ?></a>
                    <a href="<?php echo esc_url( home_url( '/portal/equity' ) ); ?>#equity-referrals" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-referrals-results.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'My Referrals results', 'influencer-hq' ); ?></a>
                </div>
            </section>

            <section class="hm-section">
                <a href="<?php echo esc_url( home_url( '/portal/challenges/' ) ); ?>" class="hm-sum">
                    <img class="hm-ico-img" src="<?php echo $hm_ic( 'icon-competitions.png' ); ?>" width="19" height="19" alt="">
                    <span><?php esc_html_e( 'Competitions', 'influencer-hq' ); ?></span>
                </a>
                <div class="hm-details-body hm-tree">
                    <a href="<?php echo $hm_ch( 'private' ); ?>" class="hm-subhead hm-subhead-link">
                        <img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-private.png' ); ?>" width="19" height="19" alt="">
                        <span><?php esc_html_e( 'Private', 'influencer-hq' ); ?></span>
                    </a>
                    <a href="<?php echo $hm_ch( 'private', 'cpc-create-btn' ); ?>" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-create-private-challenge.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Create Private Challenge', 'influencer-hq' ); ?></a>
                    <a href="<?php echo $hm_ch( 'private', 'private-results' ); ?>" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-chart.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Results', 'influencer-hq' ); ?></a>
                    <a href="<?php echo $hm_ch( 'private', 'private-leaderboards' ); ?>" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-leaderboard.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Leaderboards', 'influencer-hq' ); ?></a>
                    <a href="<?php echo $hm_ch( 'private', 'private-influencer' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-influencer.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Influencer', 'influencer-hq' ); ?></a>
                    <a href="<?php echo $hm_ch( 'private', 'private-follower' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-follower.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Follower', 'influencer-hq' ); ?></a>

                    <a href="<?php echo $hm_ch( 'community' ); ?>" class="hm-subhead hm-subhead-link">
                        <img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-community.png' ); ?>" width="16" height="16" alt="">
                        <span><?php esc_html_e( 'Community', 'influencer-hq' ); ?></span>
                    </a>
                    <a href="<?php echo $hm_ch( 'community', 'community-results' ); ?>" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-chart.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Results', 'influencer-hq' ); ?></a>
                    <a href="<?php echo $hm_ch( 'community', 'community-leaderboards' ); ?>" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-leaderboard.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Leaderboards', 'influencer-hq' ); ?></a>
                    <a href="<?php echo $hm_ch( 'community', 'community-influencer' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-influencer.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Influencer', 'influencer-hq' ); ?></a>
                    <a href="<?php echo $hm_ch( 'community', 'community-follower' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-follower.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Follower', 'influencer-hq' ); ?></a>

                    <a href="<?php echo $hm_ch( 'world' ); ?>" class="hm-subhead hm-subhead-link">
                        <img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-globe.png' ); ?>" width="16" height="16" alt="">
                        <span><?php esc_html_e( 'World', 'influencer-hq' ); ?></span>
                    </a>
                    <a href="<?php echo $hm_ch( 'world', 'world-results' ); ?>" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-chart.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Results', 'influencer-hq' ); ?></a>
                    <a href="<?php echo $hm_ch( 'world', 'world-leaderboards' ); ?>" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-leaderboard.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Leaderboards', 'influencer-hq' ); ?></a>
                    <a href="<?php echo $hm_ch( 'world', 'world-influencer' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-influencer.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Influencer', 'influencer-hq' ); ?></a>
                    <a href="<?php echo $hm_ch( 'world', 'world-follower' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-follower.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Follower', 'influencer-hq' ); ?></a>

                    <a href="<?php echo $hm_ch( 'leagues' ); ?>" class="hm-subhead hm-subhead-link">
                        <img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-leagues.png' ); ?>" width="16" height="16" alt="">
                        <span><?php esc_html_e( 'Leagues', 'influencer-hq' ); ?></span>
                    </a>
                    <a href="<?php echo $hm_ch( 'leagues', 'leagues-international' ); ?>" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-globe-sm.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'International League', 'influencer-hq' ); ?></a>
                    <div class="hm-nested-block hm-indent-1">
                        <span class="hm-celeb-label"><?php esc_html_e( 'Celebrity Follower Leagues', 'influencer-hq' ); ?></span>
                        <a href="<?php echo $hm_ch( 'leagues', 'leagues-movie-stars' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-movie.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Movie Stars', 'influencer-hq' ); ?></a>
                        <a href="<?php echo $hm_ch( 'leagues', 'leagues-music-artists' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-music.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Music Artists', 'influencer-hq' ); ?></a>
                        <a href="<?php echo $hm_ch( 'leagues', 'leagues-sports-icons' ); ?>" class="hm-link hm-micro hm-indent-2 hm-icon-row"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-sport.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Sports Icons', 'influencer-hq' ); ?></a>
                    </div>
                </div>
            </section>

            <section class="hm-section">
                <a href="<?php echo esc_url( home_url( '/portal/live' ) ); ?>" class="hm-sum">
                    <img class="hm-ico-img" src="<?php echo $hm_ic( 'icon-live.png' ); ?>" width="19" height="19" alt="">
                    <span><?php esc_html_e( 'Live Appearance', 'influencer-hq' ); ?></span>
                </a>
                <div class="hm-details-body">
                    <a href="<?php echo esc_url( home_url( '/portal/live' ) ); ?>#live-request" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-live-request.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Request A Live Appearance', 'influencer-hq' ); ?></a>
                    <a href="<?php echo esc_url( home_url( '/portal/live' ) ); ?>#kick-schedule" class="hm-link hm-icon-row hm-indent-1"><img class="hm-ico-img hm-ico-img--sm" src="<?php echo $hm_ic( 'icon-kick-schedule.png' ); ?>" width="16" height="16" alt=""><?php esc_html_e( 'Report KICK Broadcast Schedule', 'influencer-hq' ); ?></a>
                </div>
            </section>

            <div class="hm-top-link-wrap hm-top-link-wrap--spaced">
                <a href="<?php echo esc_url( home_url( '/portal/account' ) ); ?>" class="hm-top-link <?php echo ( is_page( 'portal/account' ) ) ? 'is-active' : ''; ?>">
                    <img class="hm-ico-img" src="<?php echo $hm_ic( 'icon-profile.png' ); ?>" width="19" height="19" alt="">
                    <span><?php esc_html_e( 'Profile', 'influencer-hq' ); ?></span>
                </a>
                <a href="<?php echo esc_url( home_url( '/portal/more' ) ); ?>" class="hm-top-link <?php echo ( is_page( 'portal/more' ) ) ? 'is-active' : ''; ?>">
                    <img class="hm-ico-img" src="<?php echo $hm_ic( 'icon-more.png' ); ?>" width="19" height="19" alt="">
                    <span><?php esc_html_e( 'More', 'influencer-hq' ); ?></span>
                </a>
            </div>
        </nav>
    </div>
</div>
<?php get_template_part( 'template-parts/portal', 'auth-modal' ); ?>
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
        // Client-only `<html lang>` for testing ConvAI overrides (portal ElevenLabs session reads primary tag).
        langDropdown.querySelectorAll('.header-lang-option[data-lang]').forEach(function (opt) {
            opt.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var code = opt.getAttribute('data-lang');
                if (!code || !/^[a-z]{2}$/i.test(code)) {
                    return;
                }
                document.documentElement.setAttribute('lang', code.toLowerCase());
                langDropdown.classList.remove('open');
            });
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
        var content      = document.getElementById('portal-content');
        if (!content) return;

        var bottom = 0;
        if (stickyNav)    bottom = stickyNav.getBoundingClientRect().bottom;
        else if (stickyHeader) bottom = stickyHeader.getBoundingClientRect().bottom;

        

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
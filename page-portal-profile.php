<?php
/**
 * Template Name: Portal Profile
 * Description: A custom template for displaying user profile and settings.
 *
 * @package influencer-hq
 */

if ( ! function_exists( 'ihq_parse_platform_handle_pairs' ) ) {
    /**
     * Parse registration platform_handle string (Label: value | Label: value) into label => value pairs.
     *
     * @param string $raw Stored platform_handle meta.
     * @return array<string, string>
     */
    function ihq_parse_platform_handle_pairs( $raw ) {
        $pairs = array();
        $raw   = trim( (string) $raw );
        if ( $raw === '' ) {
            return $pairs;
        }
        $segments = array_map( 'trim', explode( '|', $raw ) );
        foreach ( $segments as $segment ) {
            if ( $segment === '' ) {
                continue;
            }
            $colon_at = strpos( $segment, ':' );
            if ( $colon_at === false ) {
                continue;
            }
            $label = trim( substr( $segment, 0, $colon_at ) );
            $val   = trim( substr( $segment, $colon_at + 1 ) );
            if ( $label !== '' ) {
                $pairs[ $label ] = $val;
            }
        }
        return $pairs;
    }
}

if ( ! function_exists( 'ihq_extract_youtube_video_id' ) ) {
    /**
     * Returns the 11-character YouTube video ID or an empty string.
     *
     * @param string $url Video URL or pasted link.
     */
    function ihq_extract_youtube_video_id( $url ) {
        $url = trim( (string) $url );
        if ( $url === '' ) {
            return '';
        }
        $pattern = '~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([a-zA-Z0-9_-]{11})~';
        if ( preg_match( $pattern, $url, $matches ) ) {
            return $matches[1];
        }
        return '';
    }
}

// Handle Game Portal URL form submission
if ( isset( $_POST['hq_game_url_submit'] ) && is_user_logged_in() ) {
    check_admin_referer( 'hq_game_url_save' );
    $url = isset( $_POST['hq_game_url'] ) ? esc_url_raw( wp_unslash( $_POST['hq_game_url'] ) ) : '';
    update_user_meta( get_current_user_id(), 'hq_game_url', $url );
    $account_url = function_exists( 'ihq_portal_account_url' ) ? ihq_portal_account_url() : trailingslashit( home_url( '/portal/account' ) );
    wp_safe_redirect( add_query_arg( 'hq_saved', '1', $account_url ) );
    exit;
}

// Handle gameplay YouTube URL (stored in user meta; thumbnail derived from ID).
if ( isset( $_POST['ihq_gameplay_video_submit'] ) && is_user_logged_in() ) {
    check_admin_referer( 'ihq_gameplay_video_save' );
    $raw = isset( $_POST['ihq_gameplay_video_url'] ) ? wp_unslash( $_POST['ihq_gameplay_video_url'] ) : '';
    $raw = trim( (string) $raw );
    if ( $raw === '' ) {
        update_user_meta( get_current_user_id(), '_ihq_gameplay_video_url', '' );
        $account_url = function_exists( 'ihq_portal_account_url' ) ? ihq_portal_account_url() : trailingslashit( home_url( '/portal/account' ) );
        wp_safe_redirect( add_query_arg( 'ihq_video_saved', '1', $account_url ) );
        exit;
    }
    $url = esc_url_raw( $raw );
    $account_url = function_exists( 'ihq_portal_account_url' ) ? ihq_portal_account_url() : trailingslashit( home_url( '/portal/account' ) );
    if ( ! $url || ihq_extract_youtube_video_id( $url ) === '' ) {
        wp_safe_redirect( add_query_arg( 'ihq_video_err', '1', $account_url ) );
        exit;
    }
    update_user_meta( get_current_user_id(), '_ihq_gameplay_video_url', $url );
    wp_safe_redirect( add_query_arg( 'ihq_video_saved', '1', $account_url ) );
    exit;
}

get_header();

// Load styles before content to prevent FOUC
get_template_part( 'template-parts/portal-styles' );

$user            = wp_get_current_user();
$display_name    = $user->display_name ?: $user->user_login;
$first_name      = get_user_meta( $user->ID, 'first_name', true ) ?: $user->first_name;
$last_name       = get_user_meta( $user->ID, 'last_name',  true ) ?: $user->last_name;
$user_email      = $user->user_email;
$portal_username = function_exists( 'ihq_get_portal_username' ) ? ihq_get_portal_username( $user->ID ) : '';
$needs_portal_username_setup = function_exists( 'ihq_user_needs_portal_username' ) && ihq_user_needs_portal_username( $user->ID );
$ihq_referral_nonce = wp_create_nonce( 'request_live_appearance_nonce' );
$user_handle     = $portal_username !== ''
	? ( '@' . $portal_username )
	: ( get_user_meta( $user->ID, '_ihq_handle', true ) ?: ( '@' . $user->user_login ) );
$user_country    = get_user_meta( $user->ID, '_ihq_country',   true );
$user_city       = get_user_meta( $user->ID, '_ihq_city',      true );
$user_timezone   = get_user_meta( $user->ID, '_ihq_timezone',  true );
$user_avatar     = get_user_meta( $user->ID, '_ihq_avatar_url', true );
if ( ! $user_avatar ) {
    $user_avatar = get_avatar_url( $user->ID, [ 'size' => 100 ] );
}

$social_handles  = get_user_meta( $user->ID, '_ihq_social_handles',  true );
if ( ! is_array( $social_handles ) )  { $social_handles  = []; }

$platform_handle_raw    = get_user_meta( $user->ID, 'platform_handle', true );
$platform_handle_pairs  = ihq_parse_platform_handle_pairs( $platform_handle_raw );
$ihq_profile_social_placeholder = __( 'handle or URL', 'influencer-hq' );
$ihq_profile_social_platforms   = array(
	array( 'key' => 'kick', 'label' => 'Kick' ),
	array( 'key' => 'facebook', 'label' => 'Facebook' ),
	array( 'key' => 'reddit', 'label' => 'Reddit' ),
	array( 'key' => 'tiktok', 'label' => 'TikTok' ),
	array( 'key' => 'naver-blog', 'label' => 'Naver Blog' ),
	array( 'key' => 'rednote', 'label' => 'Rednote' ),
	array( 'key' => 'bilibili', 'label' => 'Bilibili' ),
	array( 'key' => 'x', 'label' => 'X' ),
	array( 'key' => 'kakao-business', 'label' => 'Kakao B' ),
	array( 'key' => 'twitch', 'label' => 'Twitch' ),
	array( 'key' => 'instagram', 'label' => 'Instagram' ),
	array( 'key' => 'telegram-channel', 'label' => 'Telegram' ),
	array( 'key' => 'ameba', 'label' => 'Ameba' ),
	array( 'key' => 'line', 'label' => 'LINE' ),
	array( 'key' => 'youtube', 'label' => 'YouTube' ),
);

$comm_prefs      = get_user_meta( $user->ID, '_ihq_comm_prefs',      true );
if ( ! is_array( $comm_prefs ) )      { $comm_prefs      = []; }

$celebrity_selections = [
    'movie_stars'   => get_user_meta( $user->ID, '_ihq_cel_movie_stars',   true ) ?: '',
    'music_artists' => get_user_meta( $user->ID, '_ihq_cel_music_artists', true ) ?: '',
    'sports_icons'  => get_user_meta( $user->ID, '_ihq_cel_sports_icons',  true ) ?: '',
];

$intl_league_team = get_user_meta( $user->ID, '_ihq_intl_league_team', true ) ?: '';

$gameplay_video_url = get_user_meta( $user->ID, '_ihq_gameplay_video_url', true );
$gameplay_yt_id     = ihq_extract_youtube_video_id( $gameplay_video_url );

$contact_platforms = [ 'Email', 'Telegram' ];

$_settings_nonce = wp_create_nonce( 'settings_save_nonce' );
?>

    <main id="primary" class="site-main">
        
        <?php get_template_part( 'template-parts/portal-header' ); ?>
        
        <div class="container py-2 sett-wrap" id="portal-content">

            <div class="sett-content">

                <?php
                if ( function_exists( 'ihq_visitor_intent_cookie_name' ) ) {
                    get_template_part( 'template-parts/visitor-intent-test-registry' );
                }
                if ( isset( $_GET['ihq_magic_registered'] ) && $_GET['ihq_magic_registered'] === '1' ) {
                    ?>
                <div class="sett-card" style="margin-bottom:14px;border-color:rgba(40,167,69,.45);">
                    <p style="margin:0;color:#6fcf97;"><?php esc_html_e( 'Account created from magic link. Welcome!', 'influencer-hq' ); ?></p>
                </div>
                    <?php
                }
                if ( isset( $_GET['ihq_visitor_registered'] ) && $_GET['ihq_visitor_registered'] === '1' ) {
                    ?>
                <div class="sett-card" style="margin-bottom:14px;border-color:rgba(40,167,69,.45);">
                    <p style="margin:0;color:#6fcf97;"><?php esc_html_e( 'Account created. Welcome!', 'influencer-hq' ); ?></p>
                </div>
                    <?php
                }
                if ( isset( $_GET['ihq_visitor_existing'] ) && $_GET['ihq_visitor_existing'] === '1' ) {
                    ?>
                <div class="sett-card" style="margin-bottom:14px;border-color:rgba(184,151,47,.45);">
                    <p style="margin:0;color:#e6cfa0;"><?php esc_html_e( 'You are now signed in with your existing account.', 'influencer-hq' ); ?></p>
                </div>
                    <?php
                }
                ?>

                <!-- PROFILE Header -->
                <header class="sett-header">
                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/profile.png" alt="" class="sett-header-icon">
                    <h1 class="sett-title">PROFILE</h1>
                </header>

                <div class="sett-sep"></div>

                <?php
                $ihq_start_session_dump = (string) get_user_meta( $user->ID, 'ihq_oauth_start_session_last', true );
                if ( $ihq_start_session_dump === '' ) {
                    $ihq_start_session_dump = __( 'No start-session response saved yet. Log in to refresh.', 'influencer-hq' );
                }
                ?>
                <div class="sett-card" style="margin-bottom:14px;border:1px dashed rgba(184,151,47,.45);">
                    <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#b8972f;">
                        <?php esc_html_e( 'OAuth start-session response (last login)', 'influencer-hq' ); ?>
                    </p>
                    <pre style="margin:0;padding:10px 12px;max-height:320px;overflow:auto;font-size:11px;line-height:1.45;color:#ddd;background:rgba(0,0,0,.35);border-radius:6px;white-space:pre-wrap;word-break:break-word;"><?php echo esc_html( $ihq_start_session_dump ); ?></pre>
                </div>

                <!-- GAME PORTAL URL -->
                <?php
                $hq_current_url = get_user_meta( $user->ID, 'hq_game_url', true );
                $hq_default_url = 'https://qc-game-portal-client-tf-b2c.dev.ae.games/av-baccarat';
                ?>
                <form method="post" action="" class="hq-game-url-form">
                    <?php wp_nonce_field( 'hq_game_url_save' ); ?>
                    <div class="sett-card" style="margin-bottom:14px;">
                        <div class="sett-row">
                            <label for="hq_game_url" class="sett-row-lbl">Game Portal URL</label>
                            <div class="sett-row-val" style="width:auto;flex:1;">
                                <input
                                    type="url"
                                    id="hq_game_url"
                                    name="hq_game_url"
                                    value="<?php echo esc_attr( $hq_current_url ); ?>"
                                    placeholder="<?php echo esc_attr( $hq_default_url ); ?>"
                                    class="hq-game-url-input"
                                >
                            </div>
                            <button type="submit" name="hq_game_url_submit" class="hq-game-url-save-btn">Save</button>
                        </div>
                        <div class="sett-row" style="border-bottom:none;">
                            <span class="sett-row-lbl" style="color:#616161;font-size:13px;">Default</span>
                            <span style="font-size:13px;color:#616161;flex:1;text-align:right;word-break:break-all;"><?php echo esc_html( $hq_default_url ); ?></span>
                        </div>
                    </div>
                    <?php if ( isset( $_GET['hq_saved'] ) ) : ?>
                    <p style="color:#7CCA8A;font-size:13px;margin:-8px 0 10px;">&#10003; Saved.</p>
                    <?php endif; ?>
                </form>

                <!-- Identity Badge -->
                <div class="sett-identity">
                    <div class="sett-avatar-ring">
                        <img src="<?php echo esc_url( $user_avatar ); ?>" alt="<?php echo esc_attr( $display_name ); ?>" class="sett-avatar-img" id="settAvatarImg">
                    </div>
                    <div class="sett-identity-body">
                        <div class="sett-display-name"><?php echo esc_html( $display_name ); ?></div>
                        <div class="sett-user-handle"><?php echo esc_html( $user_handle ); ?></div>
                        <div class="sett-social-row">
                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/youtube.png" alt="YouTube" class="sett-soc-icon">
                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/x.png" alt="X" class="sett-soc-icon">
                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/tiktok.png" alt="TikTok" class="sett-soc-icon">
                            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/kick.png" alt="Kick" class="sett-soc-icon">
                        </div>
                    </div>
                </div>

                <div class="sett-referral-block">
                    <p class="sett-referral-label"><?php esc_html_e( 'Referral Link', 'influencer-hq' ); ?></p>
                    <div class="sett-referral-wrap">
                        <div class="sett-referral-url" id="profile-referral-url-display"><?php esc_html_e( 'URL will appear here...', 'influencer-hq' ); ?></div>
                        <button type="button" id="profile-referral-copy-btn" class="sett-referral-copy-btn"><?php esc_html_e( 'copy', 'influencer-hq' ); ?></button>
                    </div>
                </div>

                <!-- GAMEPLAY VIDEO PROMOTION -->
                <div class="sett-gameplay-promo">
                    <p class="sett-gameplay-promo-text">
                        <?php esc_html_e( 'Post a link of your favorite gameplay video stream to enable immediate worldwide promotion by Influencer Headquarters.', 'influencer-hq' ); ?>
                    </p>
                    <form method="post" action="" class="ihq-gameplay-video-form">
                        <?php wp_nonce_field( 'ihq_gameplay_video_save' ); ?>
                        <div class="sett-card sett-gameplay-promo-card">
                            <div class="sett-row sett-gameplay-promo-row">
                                <label for="ihq_gameplay_video_url" class="sett-row-lbl"><?php esc_html_e( 'YouTube video', 'influencer-hq' ); ?></label>
                                <div class="sett-row-val sett-gameplay-promo-input-wrap">
                                    <input
                                        type="url"
                                        id="ihq_gameplay_video_url"
                                        name="ihq_gameplay_video_url"
                                        value="<?php echo esc_attr( $gameplay_video_url ); ?>"
                                        placeholder="https://www.youtube.com/watch?v=..."
                                        class="hq-game-url-input"
                                        autocomplete="url"
                                    >
                                </div>
                                <button type="submit" name="ihq_gameplay_video_submit" class="hq-game-url-save-btn"><?php esc_html_e( 'Save', 'influencer-hq' ); ?></button>
                            </div>
                        </div>
                        <?php if ( isset( $_GET['ihq_video_saved'] ) ) : ?>
                            <p class="sett-gameplay-promo-feedback sett-gameplay-promo-feedback--ok">&#10003; <?php esc_html_e( 'Saved.', 'influencer-hq' ); ?></p>
                        <?php endif; ?>
                        <?php if ( isset( $_GET['ihq_video_err'] ) ) : ?>
                            <p class="sett-gameplay-promo-feedback sett-gameplay-promo-feedback--err"><?php esc_html_e( 'Enter a valid YouTube link, or clear the field and save.', 'influencer-hq' ); ?></p>
                        <?php endif; ?>
                    </form>
                    <?php if ( $gameplay_yt_id !== '' ) : ?>
                        <?php
                        $embed_base = 'https://www.youtube.com/embed/' . $gameplay_yt_id;
                        $embed_src  = add_query_arg(
                            [
                                'playsinline' => '1',
                                'controls'    => '1',
                            ],
                            $embed_base
                        );
                        ?>
                        <div class="sett-gameplay-embed-wrap">
                            <iframe
                                class="sett-gameplay-embed"
                                src="<?php echo esc_url( $embed_src ); ?>"
                                title="<?php echo esc_attr__( 'Gameplay video preview', 'influencer-hq' ); ?>"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen
                                loading="lazy"
                            ></iframe>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ACCOUNT INFORMATION -->
                <div class="sett-section-head">
                    <span class="sett-section-title">ACCOUNT &nbsp;INFORMATION</span>
                    <span class="sett-hint"><span class="sett-hint-text">Click to enter / edit</span><span class="sett-info-icon">i<span class="sett-info-tooltip">Entered information is saved by pressing Enter key, or by clicking anywhere outside the input field.</span></span></span>
                </div>

                <div class="sett-card">
                    <div class="sett-row sett-portal-username-row" id="portal-username-setup-zone">
                        <label for="portal-username-input" class="sett-row-lbl"><?php esc_html_e( 'Your username', 'influencer-hq' ); ?></label>
                        <div class="sett-row-val sett-portal-username-val">
                            <input
                                type="text"
                                id="portal-username-input"
                                class="hq-game-url-input sett-portal-username-input"
                                value="<?php echo esc_attr( $portal_username ); ?>"
                                placeholder="<?php esc_attr_e( 'Choose your username', 'influencer-hq' ); ?>"
                                autocomplete="username"
                                maxlength="30"
                                spellcheck="false"
                            >
                            <button type="button" id="portal-username-save-btn" class="hq-game-url-save-btn"><?php esc_html_e( 'Save', 'influencer-hq' ); ?></button>
                        </div>
                    </div>
                    <p class="sett-portal-username-feedback sett-portal-username-feedback--ok" id="portal-username-ok" hidden></p>
                    <p class="sett-portal-username-feedback sett-portal-username-feedback--err" id="portal-username-err" hidden></p>
                    <?php
                    $acct_rows = [
                        [ 'key' => 'first_name', 'label' => 'First Name',             'value' => $first_name,    'type' => 'text'   ],
                        [ 'key' => 'last_name',  'label' => 'Last Name',              'value' => $last_name,     'type' => 'text'   ],
                        [ 'key' => 'email',    'label' => 'Email',                  'value' => $user_email,    'type' => 'email'  ],
                        [ 'key' => 'country',  'label' => 'Country',                'value' => $user_country,  'type' => 'text'   ],
                        [ 'key' => 'city',     'label' => 'City',                   'value' => $user_city,     'type' => 'text'   ],
                        [ 'key' => 'timezone', 'label' => 'Time Zone',              'value' => $user_timezone, 'type' => 'timezone' ],
                        [ 'key' => 'handle',   'label' => 'InfluencerHQ Handle',    'value' => $user_handle,   'type' => 'text'   ],
                        [ 'key' => 'avatar',   'label' => 'Profile Photo or Avatar','value' => '',             'type' => 'avatar' ],
                    ];
                    foreach ( $acct_rows as $row ) :
                    ?>
                    <div class="sett-row">
                        <div class="sett-row-lbl"><?php echo esc_html( $row['label'] ); ?></div>
                        <div class="sett-row-val">
                            <?php if ( $row['type'] === 'avatar' ) : ?>
                                <button type="button" class="sett-change-photo" id="sett-avatar-btn">Add or Change Photo</button>
                            <?php elseif ( $row['type'] === 'timezone' ) : ?>
                                <?php
                                $now = new DateTime('now', new DateTimeZone('UTC'));
                                $tz_list = [];
                                foreach ( DateTimeZone::listIdentifiers( DateTimeZone::ALL ) as $tz_id ) {
                                    $dtz    = new DateTimeZone( $tz_id );
                                    $offset = $dtz->getOffset( $now );
                                    $hours  = (int) floor( abs( $offset ) / 3600 );
                                    $mins   = (int) ( ( abs( $offset ) % 3600 ) / 60 );
                                    $sign   = $offset >= 0 ? '+' : '-';
                                    $parts  = explode( '/', $tz_id );
                                    $city   = str_replace( '_', ' ', end( $parts ) );
                                    $region = count( $parts ) > 1 ? str_replace( '_', ' ', $parts[0] ) : '';
                                    $city_label = $region ? $city . ' (' . $region . ')' : $city;
                                    $label  = sprintf( '%s — UTC%s%02d:%02d', $city_label, $sign, $hours, $mins );
                                    $tz_list[] = [ 'id' => $tz_id, 'offset' => $offset, 'label' => $label, 'city' => $city ];
                                }
                                usort( $tz_list, function( $a, $b ) { return strcasecmp( $a['city'], $b['city'] ); } );
                                ?>
                                <select class="sett-timezone-select" data-group="account" data-field="timezone" data-saved="<?php echo esc_attr( $row['value'] ); ?>">
                                    <option value="">-- Detecting... --</option>
                                    <?php foreach ( $tz_list as $tz_item ) : ?>
                                    <option value="<?php echo esc_attr( $tz_item['id'] ); ?>"<?php selected( $row['value'], $tz_item['id'] ); ?>><?php echo esc_html( $tz_item['label'] ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else : ?>
                                <span class="sett-editable" data-group="account" data-field="<?php echo esc_attr( $row['key'] ); ?>"><?php echo esc_html( $row['value'] ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- SOCIAL MEDIA (from registration platform_handle) -->
                <div class="sett-section-head sett-section-head--comm" id="socialMediaHead" style="cursor:pointer;">
                    <span class="sett-section-title"><?php esc_html_e( 'Social Media You Post On', 'influencer-hq' ); ?></span>
                    <span class="sett-hint"><span class="sett-hint-text"><?php esc_html_e( 'Click a platform, then enter handle or URL', 'influencer-hq' ); ?></span></span>
                    <span class="sett-arrow" id="socialMediaArrow">▼</span>
                </div>

                <div id="socialMediaBody">
                    <div class="sett-card sett-social-profile-card">
                        <div class="sett-social-grid" role="group" aria-label="<?php esc_attr_e( 'Social media platforms', 'influencer-hq' ); ?>">
                            <?php foreach ( $ihq_profile_social_platforms as $ihq_social ) :
                                $ihq_social_val = '';
                                foreach ( $platform_handle_pairs as $pair_label => $pair_val ) {
                                    if ( strcasecmp( $pair_label, $ihq_social['label'] ) === 0 ) {
                                        $ihq_social_val = $pair_val;
                                        break;
                                    }
                                }
                                $ihq_social_selected = $ihq_social_val !== '';
                                ?>
                            <button
                                type="button"
                                class="sett-social-grid-item<?php echo $ihq_social_selected ? ' is-selected' : ''; ?>"
                                id="profile-social-grid-<?php echo esc_attr( $ihq_social['key'] ); ?>"
                                data-social-key="<?php echo esc_attr( $ihq_social['key'] ); ?>"
                                data-social-label="<?php echo esc_attr( $ihq_social['label'] ); ?>"
                                aria-pressed="<?php echo $ihq_social_selected ? 'true' : 'false'; ?>"
                            ><?php echo esc_html( $ihq_social['label'] ); ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="sett-social-inputs" id="profile-social-inputs-panel">
                            <?php foreach ( $ihq_profile_social_platforms as $ihq_social ) :
                                $ihq_social_val = '';
                                foreach ( $platform_handle_pairs as $pair_label => $pair_val ) {
                                    if ( strcasecmp( $pair_label, $ihq_social['label'] ) === 0 ) {
                                        $ihq_social_val = $pair_val;
                                        break;
                                    }
                                }
                                $ihq_social_selected = $ihq_social_val !== '';
                                $ihq_social_remove_aria = sprintf(
                                    /* translators: %s: social platform name */
                                    __( 'Remove %s', 'influencer-hq' ),
                                    $ihq_social['label']
                                );
                                ?>
                            <div class="sett-social-input-row" id="profile-social-entry-<?php echo esc_attr( $ihq_social['key'] ); ?>"<?php echo $ihq_social_selected ? '' : ' hidden'; ?>>
                                <span class="sett-social-input-label"><?php echo esc_html( $ihq_social['label'] ); ?></span>
                                <div class="sett-social-input-field">
                                    <input
                                        class="sett-social-handle-input"
                                        type="text"
                                        data-social-key="<?php echo esc_attr( $ihq_social['key'] ); ?>"
                                        value="<?php echo esc_attr( $ihq_social_val ); ?>"
                                        placeholder="<?php echo esc_attr( $ihq_profile_social_placeholder ); ?>"
                                        aria-label="<?php echo esc_attr( $ihq_social['label'] ); ?>"
                                    >
                                    <button
                                        type="button"
                                        class="sett-social-clear-btn"
                                        data-social-key="<?php echo esc_attr( $ihq_social['key'] ); ?>"
                                        aria-label="<?php echo esc_attr( $ihq_social_remove_aria ); ?>"
                                        <?php echo $ihq_social_val !== '' ? '' : ' hidden'; ?>
                                    >×</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="sett-social-save-hint" id="profile-social-save-hint" aria-live="polite"></p>
                    </div>
                </div>

                <!-- CELEBRITY FOLLOWERS LEAGUES -->
                <div class="sett-section-head sett-section-head--comm celeb-leagues-head" id="celebLeaguesHead" style="cursor:pointer;">
                    <span class="sett-section-title">CELEBRITY &nbsp;FOLLOWERS &nbsp;LEAGUES</span>
                    <span class="sett-arrow" id="celebLeaguesArrow">▼</span>
                </div>

                <div id="celebLeaguesBody">
                    <p class="sett-quote" style="margin-top:0;"><em>Choose your favorite celebrity in all three categories. Influencer Headquarters will be promoting you as a Team Captain with all new game participants.</em></p>

                    <?php
                    $celeb_lists = [
                        'movie_stars'   => ['Leonardo DiCaprio','Fan Bingbing','Scarlett Johansson','Tony Leung','Anya Wong','Maggie Cheung','Iko Uwais','Tom Cruise','Hyun Bin','Chow Yun-fat','Zhang Ziyi','Song Hye-kyo','Gong Yoo','Michelle Yeoh','Donnie Yen','Vicky Chen','Bruce Lee','Gong Li','Liu Yifei','Jackie Chan'],
                        'music_artists' => ['Jolin Tsai','Namewee','IU (Lee Ji-eun)','BTS','Ariana Grande','Bruno Mars','PSY','Blackpink','Twice','Tomorrow X Together','Billie Eilish','Jay Chou','Lisa (BLACKPINK)','Zhou Shen','G-Dragon','Lady Gaga','Taylor Swift','Deng Liqi','Justin Bieber','Ed Sheeran'],
                        'sports_icons'  => ['Son Heung-min','Lionel Messi','Roger Federer','Naomi Osaka','Ding Junhui','Jeremy Lin','Cristiano Ronaldo','Stephen Curry','Michael Jordan','Novak Djokovic','Kento Momota','Sachin Tendulkar','Rafael Nadal','Virat Kohli','Manny Pacquiao','Shohei Ohtani','Yao Ming','LeBron James','Kylian Mbappé','Lee Chong Wei'],
                    ];
                    $celeb_labels = ['movie_stars' => 'Movie Stars', 'music_artists' => 'Music Artists', 'sports_icons' => 'Sports Icons'];
                    ?>

                    <div class="sett-card">
                        <div class="celeb-grid-layout">
                            <?php foreach ( $celeb_labels as $cat => $label ) :
                                $saved = $celebrity_selections[ $cat ] ?? '';
                            ?>
                            <div class="celeb-col">
                                <span class="celeb-col-label"><?php echo esc_html( $label ); ?></span>
                                <select class="celeb-select" data-category="<?php echo esc_attr( $cat ); ?>">
                                    <option value="">Open</option>
                                    <?php foreach ( $celeb_lists[ $cat ] as $name ) : ?>
                                    <option value="<?php echo esc_attr( $name ); ?>"<?php selected( $saved, $name ); ?>><?php echo esc_html( $name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- INTERNATIONAL LEAGUE TEAM -->
                <div class="sett-section-head sett-section-head--comm celeb-leagues-head" id="intlLeagueHead" style="cursor:pointer;">
                    <span class="sett-section-title">CHOOSE YOUR INTERNATIONAL LEAGUE TEAM</span>
                    <span class="sett-arrow" id="intlLeagueArrow">▼</span>
                </div>

                <div id="intlLeagueBody">
                    <div class="sett-card">
                        <div class="celeb-grid-layout" style="grid-template-columns:1fr;">
                            <div class="celeb-col">
                                <span class="celeb-col-label">Country / Region</span>
                                <select class="celeb-select" id="intlLeagueSelect">
                                    <option value="">Open</option>
                                    <?php
                                    $intl_league_regions = ['South Korea','Europe','Malaysia','Thailand','Africa','Singapore','Asia','India','China','Hong Kong','Philippines','Taiwan','United States','Canada','Macao','Pakistan','South America','Japan','Australia','South Africa'];
                                    foreach ( $intl_league_regions as $region ) :
                                    ?>
                                    <option value="<?php echo esc_attr( $region ); ?>"<?php selected( $intl_league_team, $region ); ?>><?php echo esc_html( $region ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quote -->
                <p class="sett-quote"><em>We believe visibility powers competition. The more visible you choose to be, the farther your leadership can travel.</em></p>

                <!-- USERNAME OR CONTACT -->
                <div class="sett-section-head sett-section-head--comm" id="contactHead" style="cursor:pointer;">
                    <span class="sett-section-title">USERNAME OR CONTACT</span>
                    <span class="sett-arrow" id="contactArrow">▼</span>
                </div>

                <div id="contactBody">
                    <div class="sett-card contact-card">
                        <?php
                        foreach ( $contact_platforms as $cp ) :
                            $ckey  = strtolower( $cp );
                            $cval  = $social_handles[ $ckey ] ?? '';
                            if ( $ckey === 'email' && $cval === '' ) {
                                $cval = $user_email;
                            }
                            if ( $ckey === 'telegram' && $cval === '' ) {
                                $tg_handle = get_user_meta( $user->ID, 'communication_username', true );
                                if ( is_string( $tg_handle ) && $tg_handle !== '' ) {
                                    $cval = $tg_handle;
                                }
                            }
                            $ccomm = ! empty( $comm_prefs[ $ckey ] );
                        ?>
                        <div class="contact-row" data-key="<?php echo esc_attr( $ckey ); ?>">
                            <div class="contact-row-main">
                                <span class="contact-row-lbl"><?php echo esc_html( $cp ); ?></span>
                                <span class="contact-row-addval<?php echo $cval ? ' contact-row-addval--filled' : ''; ?>"><?php echo $cval ? esc_html( $cval ) : 'add'; ?></span>
                            </div>
                            <div class="contact-row-expand" style="display:none;">
                                <input type="text" class="contact-input" value="<?php echo esc_attr( $cval ); ?>" placeholder="click to enter / edit">
                                <div class="contact-toggles">
                                    <label class="contact-toggle<?php echo $ccomm ? ' contact-toggle--on' : ''; ?>">
                                        <input type="checkbox" class="contact-toggle-cb" data-type="comm"<?php checked( $ccomm ); ?>>
                                        <span class="contact-toggle-label">Communicate with Me</span>
                                        <span class="contact-toggle-track"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div><!-- .sett-content -->
            
        </div>
        
        <?php get_template_part( 'template-parts/portal-footer' ); ?>

    </main><!-- #main -->


<script>
(function(){
    var _ajax  = <?php echo wp_json_encode( admin_url('admin-ajax.php') ); ?>;
    var _nonce = <?php echo wp_json_encode( $_settings_nonce ); ?>;
    var _referralNonce = <?php echo wp_json_encode( $ihq_referral_nonce ); ?>;
    var _needsPortalUsername = <?php echo $needs_portal_username_setup ? 'true' : 'false'; ?>;
    var _portalUsernameSetupMsg = <?php echo wp_json_encode( __( 'Please create your username to be able to continue your journey on Influencer HQ', 'influencer-hq' ) ); ?>;

    /* ── Referral link (same API as Live Appearance page) ── */
    function setProfileReferralUrl(url) {
        var el = document.getElementById('profile-referral-url-display');
        if (!el) return;
        if (url) {
            el.textContent = url;
            el.classList.add('sett-referral-url--has-value');
        } else {
            el.textContent = 'URL will appear here...';
            el.classList.remove('sett-referral-url--has-value');
        }
    }

    (function loadProfileReferralUrl() {
        var fd = new FormData();
        fd.append('action', 'get_referral_link');
        fd.append('nonce', _referralNonce);
        fetch(_ajax, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success && res.data && res.data.url) {
                    setProfileReferralUrl(res.data.url);
                    return;
                }
                var errMsg = (res.data && res.data.message) ? res.data.message : 'Referral link unavailable.';
                setProfileReferralUrl('');
                var el = document.getElementById('profile-referral-url-display');
                if (el) {
                    el.textContent = errMsg;
                }
            }).catch(function() {
                var el = document.getElementById('profile-referral-url-display');
                if (el) {
                    el.textContent = 'Could not load referral link.';
                }
            });
    })();

    var profileReferralCopyBtn = document.getElementById('profile-referral-copy-btn');
    if (profileReferralCopyBtn) {
        profileReferralCopyBtn.addEventListener('click', function() {
            var txt = document.getElementById('profile-referral-url-display');
            if (!txt || !txt.textContent || txt.textContent === 'URL will appear here...') {
                return;
            }
            navigator.clipboard.writeText(txt.textContent).then(function() {
                profileReferralCopyBtn.textContent = 'copied!';
                window.setTimeout(function() {
                    profileReferralCopyBtn.textContent = 'copy';
                }, 2000);
            });
        });
    }

    /* ── Portal username (mandatory after registration) ── */
    var portalUsernameInput = document.getElementById('portal-username-input');
    var portalUsernameSaveBtn = document.getElementById('portal-username-save-btn');
    var portalUsernameSetupZone = document.getElementById('portal-username-setup-zone');
    var portalUsernameErr = document.getElementById('portal-username-err');
    var portalUsernameOk = document.getElementById('portal-username-ok');
    var settUserHandle = document.querySelector('.sett-user-handle');

    function showPortalUsernameErr(msg) {
        if (!portalUsernameErr) {
            return;
        }
        portalUsernameErr.textContent = msg || '';
        portalUsernameErr.hidden = !msg;
    }

    function clearPortalUsernameFeedback() {
        if (portalUsernameErr) {
            portalUsernameErr.hidden = true;
            portalUsernameErr.textContent = '';
        }
        if (portalUsernameOk) {
            portalUsernameOk.hidden = true;
            portalUsernameOk.textContent = '';
        }
    }

    function isInsidePortalUsernameZone(target) {
        if (!target || !portalUsernameSetupZone) {
            return false;
        }
        return portalUsernameSetupZone.contains(target);
    }

    if (_needsPortalUsername) {
        document.body.classList.add('portal-username-setup-active');
        document.addEventListener('click', function(e) {
            if (!_needsPortalUsername) {
                return;
            }
            if (isInsidePortalUsernameZone(e.target)) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            showPortalUsernameErr(_portalUsernameSetupMsg);
        }, true);
    }

    if (portalUsernameSaveBtn && portalUsernameInput) {
        portalUsernameSaveBtn.addEventListener('click', function() {
            clearPortalUsernameFeedback();
            portalUsernameSaveBtn.disabled = true;
            var fd = new FormData();
            fd.append('action', 'save_portal_username');
            fd.append('nonce', _nonce);
            fd.append('portal_username', portalUsernameInput.value.trim());
            fetch(_ajax, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    portalUsernameSaveBtn.disabled = false;
                    if (!res.success) {
                        var msg = (res.data && res.data.message) ? res.data.message : 'Could not save username.';
                        showPortalUsernameErr(msg);
                        return;
                    }
                    _needsPortalUsername = false;
                    document.body.classList.remove('portal-username-setup-active');
                    if (portalUsernameOk) {
                        portalUsernameOk.textContent = 'Saved.';
                        portalUsernameOk.hidden = false;
                    }
                    if (settUserHandle && res.data && res.data.portal_username) {
                        settUserHandle.textContent = '@' + res.data.portal_username;
                    }
                    if (res.data && res.data.redirect_url) {
                        window.setTimeout(function() {
                            window.location.href = res.data.redirect_url;
                        }, 600);
                    }
                })
                .catch(function() {
                    portalUsernameSaveBtn.disabled = false;
                    showPortalUsernameErr('Network error. Please try again.');
                });
        });
    }

    /* ── Inline edit ───────────────────────────────────── */
    document.querySelectorAll('.sett-editable').forEach(function(el){
        el.addEventListener('click', function(){
            if (el.querySelector('input,textarea')) return;
            var val   = el.textContent.trim();
            var input = document.createElement('input');
            input.type  = 'text';
            input.value = val;
            input.style.cssText = 'background:transparent;border:none;border-bottom:1px solid #b8972f;color:#fff;font-size:16px;text-align:right;width:100%;outline:none;';
            el.textContent = '';
            el.appendChild(input);
            input.focus();
            function commit(){
                var v = input.value.trim();
                el.textContent = v;
                var field = el.dataset.field;
                if ( field === 'first_name' || field === 'last_name' ) {
                    saveFullname();
                } else {
                    save('save_settings_field', { group: el.dataset.group, field: field, value: v });
                }
            }
            input.addEventListener('blur', commit);
            input.addEventListener('keydown', function(e){ if(e.key==='Enter'){ e.preventDefault(); commit(); }});
        });
    });

    /* ── Username or Contact ─────────────────────────── */
    var contactHead  = document.getElementById('contactHead');
    var contactBody  = document.getElementById('contactBody');
    var contactArrow = document.getElementById('contactArrow');
    if (contactHead) {
        contactHead.addEventListener('click', function(){
            var hidden = contactBody.style.display === 'none';
            contactBody.style.display = hidden ? '' : 'none';
            contactArrow.textContent  = hidden ? '▼' : '▲';
        });
    }

    document.querySelectorAll('.contact-row').forEach(function(row){
        var key    = row.dataset.key;
        var main   = row.querySelector('.contact-row-main');
        var expand = row.querySelector('.contact-row-expand');
        var input  = row.querySelector('.contact-input');
        var valEl  = row.querySelector('.contact-row-addval');

        main.addEventListener('click', function(){
            var isOpen = expand.style.display !== 'none';
            document.querySelectorAll('.contact-row-expand').forEach(function(e){ e.style.display = 'none'; });
            if (!isOpen) {
                expand.style.display = '';
                input.focus();
            }
        });

        function commitInput(){
            var v = input.value.trim();
            valEl.textContent = v || 'add';
            v ? valEl.classList.add('contact-row-addval--filled') : valEl.classList.remove('contact-row-addval--filled');
            save('save_settings_field', { group: 'social', field: key, value: v });
        }
        input.addEventListener('blur', commitInput);
        input.addEventListener('keydown', function(e){ if(e.key==='Enter'){ e.preventDefault(); commitInput(); } });
        expand.addEventListener('mousedown', function(e){
            if (e.target !== input) e.preventDefault();
        });

        var commCb = row.querySelector('.contact-toggle-cb[data-type="comm"]');
        if (commCb) {
            commCb.addEventListener('change', function(){
                var lbl = commCb.closest('.contact-toggle');
                commCb.checked ? lbl.classList.add('contact-toggle--on') : lbl.classList.remove('contact-toggle--on');
                save('save_settings_toggle', { group: 'comm', key: key, value: commCb.checked ? 1 : 0 });
            });
        }
    });

    /* ── Avatar upload ─────────────────────────────────── */
    var avatarBtn = document.getElementById('sett-avatar-btn');
    if (avatarBtn) {
        var fileInput = document.createElement('input');
        fileInput.type   = 'file';
        fileInput.accept = 'image/*';
        fileInput.style.display = 'none';
        document.body.appendChild(fileInput);
        avatarBtn.addEventListener('click', function(){ fileInput.click(); });
        fileInput.addEventListener('change', function(){
            var file = fileInput.files[0];
            if (!file) return;
            var fd = new FormData();
            fd.append('action', 'save_settings_avatar');
            fd.append('nonce',  _nonce);
            fd.append('avatar', file);
            fetch(_ajax, { method:'POST', body:fd })
                .then(function(r){ return r.json(); })
                .then(function(res){
                    if (res.success && res.data.url) {
                        var img = document.getElementById('settAvatarImg');
                        if (img) img.src = res.data.url;
                    }
                }).catch(function(){});
        });
    }

    function saveFullname() {
        var fn = document.querySelector('.sett-editable[data-field="first_name"]');
        var ln = document.querySelector('.sett-editable[data-field="last_name"]');
        if (!fn || !ln) return;
        var payload = {
            firstName: fn.textContent.trim(),
            lastName:  ln.textContent.trim(),
        };
        var fd = new FormData();
        fd.append('action', 'ihq_update_fullname');
        fd.append('nonce',  _nonce);
        fd.append('firstName', payload.firstName);
        fd.append('lastName',  payload.lastName);
        fetch(_ajax, { method:'POST', body:fd })
            .then(function(r){ return r.json(); })
            .catch(function(){});
    }

    function save(action, params){
        var fd = new FormData();
        fd.append('action', action);
        fd.append('nonce',  _nonce);
        Object.keys(params).forEach(function(k){ fd.append(k, params[k]); });
        fetch(_ajax, { method:'POST', body:fd }).catch(function(){});
    }

    // Read first/last name from API on load
    fetch(_ajax, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=ihq_get_player_me&nonce=' + encodeURIComponent(_nonce),
    })
    .then(function(r){ return r.json(); })
    .then(function(res) {
        if (res.success && res.data) {
            var d = res.data;
            var fn = document.querySelector('.sett-editable[data-field="first_name"]');
            var ln = document.querySelector('.sett-editable[data-field="last_name"]');
            if (fn && d.firstName !== undefined) fn.textContent = d.firstName;
            if (ln && d.lastName  !== undefined) ln.textContent = d.lastName;
        }
    }).catch(function(){});

    /* ── Social media (platform_handle) ─────────────── */
    var socialMediaHead  = document.getElementById('socialMediaHead');
    var socialMediaBody  = document.getElementById('socialMediaBody');
    var socialMediaArrow = document.getElementById('socialMediaArrow');
    if (socialMediaHead) {
        socialMediaHead.addEventListener('click', function(e){
            if (e.target.closest('.sett-hint')) return;
            var hidden = socialMediaBody.style.display === 'none';
            socialMediaBody.style.display = hidden ? '' : 'none';
            socialMediaArrow.textContent  = hidden ? '▼' : '▲';
        });
    }

    function ihqProfileBuildPlatformHandle() {
        var parts = [];
        document.querySelectorAll('.sett-social-grid-item.is-selected').forEach(function(btn){
            var key = btn.getAttribute('data-social-key');
            var row = key ? document.getElementById('profile-social-entry-' + key) : null;
            var inp = row ? row.querySelector('input.sett-social-handle-input') : null;
            var label = btn.getAttribute('data-social-label') || btn.textContent.trim();
            if (inp && inp.value.trim()) {
                parts.push(label + ': ' + inp.value.trim());
            }
        });
        return parts.join(' | ');
    }

    function ihqProfileSavePlatformHandle() {
        var hint = document.getElementById('profile-social-save-hint');
        save('save_settings_field', {
            group: 'account',
            field: 'platform_handle',
            value: ihqProfileBuildPlatformHandle()
        });
        if (hint) {
            hint.textContent = 'Saved';
            window.setTimeout(function(){ hint.textContent = ''; }, 2000);
        }
    }

    function ihqProfileGetSocialRowParts(key) {
        var row = document.getElementById('profile-social-entry-' + key);
        if (!row) {
            return null;
        }
        return {
            row: row,
            btn: document.getElementById('profile-social-grid-' + key),
            inp: row.querySelector('input.sett-social-handle-input'),
            clearBtn: row.querySelector('.sett-social-clear-btn'),
        };
    }

    function ihqProfileSyncSocialClearBtn(parts) {
        if (!parts || !parts.clearBtn || !parts.inp) {
            return;
        }
        parts.clearBtn.hidden = parts.inp.value.trim() === '';
    }

    function ihqProfileRemoveSocialPlatform(key) {
        var parts = ihqProfileGetSocialRowParts(key);
        if (!parts) {
            return;
        }
        if (parts.inp) {
            parts.inp.value = '';
        }
        if (parts.btn) {
            parts.btn.classList.remove('is-selected');
            parts.btn.setAttribute('aria-pressed', 'false');
        }
        parts.row.hidden = true;
        if (parts.clearBtn) {
            parts.clearBtn.hidden = true;
        }
        ihqProfileSavePlatformHandle();
    }

    function ihqProfileToggleSocialPlatform(key) {
        var parts = ihqProfileGetSocialRowParts(key);
        if (!parts || !parts.btn) {
            return;
        }
        var isSelected = parts.btn.classList.contains('is-selected');
        if (isSelected && parts.inp && parts.inp.value.trim() !== '') {
            return;
        }
        var isOn = !isSelected;
        parts.btn.classList.toggle('is-selected', isOn);
        parts.btn.setAttribute('aria-pressed', isOn ? 'true' : 'false');
        parts.row.hidden = !isOn;
        if (!isOn) {
            if (parts.inp) {
                parts.inp.value = '';
            }
            if (parts.clearBtn) {
                parts.clearBtn.hidden = true;
            }
            ihqProfileSavePlatformHandle();
            return;
        }
        ihqProfileSyncSocialClearBtn(parts);
        if (parts.inp) {
            window.setTimeout(function () {
                parts.inp.focus();
            }, 50);
        }
    }

    document.querySelectorAll('.sett-social-grid-item').forEach(function(btn){
        btn.addEventListener('click', function(){
            var key = btn.getAttribute('data-social-key');
            if (key) ihqProfileToggleSocialPlatform(key);
        });
    });

    document.querySelectorAll('.sett-social-clear-btn').forEach(function(clearBtn){
        clearBtn.addEventListener('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            var key = clearBtn.getAttribute('data-social-key');
            if (key) {
                ihqProfileRemoveSocialPlatform(key);
            }
        });
    });

    document.querySelectorAll('.sett-social-handle-input').forEach(function(inp){
        inp.addEventListener('input', function(){
            var key = inp.getAttribute('data-social-key');
            if (!key) {
                return;
            }
            ihqProfileSyncSocialClearBtn(ihqProfileGetSocialRowParts(key));
        });
        inp.addEventListener('blur', ihqProfileSavePlatformHandle);
        inp.addEventListener('keydown', function(e){
            if (e.key === 'Enter') {
                e.preventDefault();
                inp.blur();
            }
        });
    });

    /* ── Celebrity Followers Leagues ──────────────────── */
    var celebHead  = document.getElementById('celebLeaguesHead');
    var celebBody  = document.getElementById('celebLeaguesBody');
    var celebArrow = document.getElementById('celebLeaguesArrow');
    if (celebHead) {
        celebHead.addEventListener('click', function(){
            var hidden = celebBody.style.display === 'none';
            celebBody.style.display = hidden ? '' : 'none';
            celebArrow.textContent  = hidden ? '▼' : '▲';
        });
    }

    document.querySelectorAll('.celeb-select').forEach(function(sel){
        sel.addEventListener('change', function(){
            save('save_settings_field', { group: 'account', field: 'celebrity_' + sel.dataset.category, value: sel.value });
        });
    });

    /* ── International League Team ────────────────────── */
    var intlLeagueHead  = document.getElementById('intlLeagueHead');
    var intlLeagueBody  = document.getElementById('intlLeagueBody');
    var intlLeagueArrow = document.getElementById('intlLeagueArrow');
    if (intlLeagueHead) {
        intlLeagueHead.addEventListener('click', function(){
            var hidden = intlLeagueBody.style.display === 'none';
            intlLeagueBody.style.display = hidden ? '' : 'none';
            intlLeagueArrow.textContent  = hidden ? '▼' : '▲';
        });
    }

    var intlLeagueSelect = document.getElementById('intlLeagueSelect');
    if (intlLeagueSelect) {
        intlLeagueSelect.addEventListener('change', function(){
            save('save_settings_field', { group: 'account', field: 'intl_league_team', value: intlLeagueSelect.value });
        });
    }

    /* ── Timezone dropdown ────────────────────────────── */
    var tzSelect = document.querySelector('.sett-timezone-select');
    if (tzSelect) {
        var savedTz = tzSelect.dataset.saved || '';

        if (savedTz) {
            // Use the value stored in user meta
            console.log('[Timezone] Loaded from user meta:', savedTz);
            tzSelect.value = savedTz;
        } else {
            // No saved value — detect from browser and write to DB
            try {
                var detected = Intl.DateTimeFormat().resolvedOptions().timeZone;
                var matchOpt = Array.from(tzSelect.options).find(function(o){ return o.value === detected; });
                if (matchOpt) {
                    console.log('[Timezone] None saved in DB. Browser detected:', detected, '— saving now.');
                    tzSelect.value = detected;
                    save('save_settings_field', { group: 'account', field: 'timezone', value: detected });
                } else {
                    console.warn('[Timezone] Browser detected "' + detected + '" but no matching option found in list.');
                }
            } catch(e) {
                console.error('[Timezone] Detection failed:', e);
            }
        }

        tzSelect.addEventListener('change', function(){
            console.log('[Timezone] User changed to:', tzSelect.value);
            save('save_settings_field', { group: 'account', field: 'timezone', value: tzSelect.value });
        });
    }

})();

</script>

<?php
get_template_part( 'template-parts/portal-scripts' );
get_footer();
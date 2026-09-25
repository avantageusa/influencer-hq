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

// Handle OAuth start-session API URL (per-user override; SSO refresh via AJAX button).
if ( isset( $_POST['ihq_oauth_start_session_url_submit'] ) && is_user_logged_in() ) {
    check_admin_referer( 'ihq_oauth_start_session_url_save' );
    $url = isset( $_POST['ihq_oauth_start_session_url'] ) ? esc_url_raw( wp_unslash( $_POST['ihq_oauth_start_session_url'] ) ) : '';
    $account_url = function_exists( 'ihq_portal_account_url' ) ? ihq_portal_account_url() : trailingslashit( home_url( '/portal/account' ) );
    if ( $url === '' ) {
        delete_user_meta( get_current_user_id(), ihq_oauth_start_session_url_meta_key() );
    } else {
        update_user_meta( get_current_user_id(), ihq_oauth_start_session_url_meta_key(), $url );
    }
    wp_safe_redirect( add_query_arg( 'ihq_oauth_url_saved', '1', $account_url ) );
    exit;
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

// Handle video submissions (up to 5 links from any platform).
if ( isset( $_POST['ihq_video_action'] ) && is_user_logged_in() ) {
    check_admin_referer( 'ihq_video_submissions_save' );
    $account_url = function_exists( 'ihq_portal_account_url' ) ? ihq_portal_account_url() : trailingslashit( home_url( '/portal/account' ) );
    $user_id     = get_current_user_id();
    $action      = sanitize_key( wp_unslash( $_POST['ihq_video_action'] ) );
    $items       = ihq_get_video_submissions( $user_id );
    $redirect    = static function ( $code ) use ( $account_url ) {
        wp_safe_redirect( add_query_arg( 'ihq_video', $code, $account_url ) );
        exit;
    };

    if ( $action === 'remove' ) {
        $remove_id = isset( $_POST['ihq_video_id'] ) ? sanitize_text_field( wp_unslash( $_POST['ihq_video_id'] ) ) : '';
        if ( $remove_id === '' ) {
            $redirect( 'err_missing' );
        }
        $next = array();
        $found = false;
        foreach ( $items as $row ) {
            if ( $row['id'] === $remove_id ) {
                $found = true;
                continue;
            }
            $next[] = $row;
        }
        if ( ! $found ) {
            $redirect( 'err_missing' );
        }
        ihq_save_video_submissions( $user_id, $next );
        $redirect( 'removed' );
    }

    $subject = isset( $_POST['ihq_video_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['ihq_video_subject'] ) ) : '';
    $subject = function_exists( 'mb_substr' )
        ? mb_substr( $subject, 0, ihq_video_subject_max_length() )
        : substr( $subject, 0, ihq_video_subject_max_length() );
    if ( $subject === '' ) {
        $redirect( 'err_subject' );
    }

    $url = ihq_sanitize_http_media_url( isset( $_POST['ihq_video_url'] ) ? wp_unslash( $_POST['ihq_video_url'] ) : '' );
    if ( $url === '' ) {
        $redirect( 'err_url' );
    }

    $now = gmdate( 'c' );

    if ( $action === 'replace' ) {
        $replace_id = isset( $_POST['ihq_video_id'] ) ? sanitize_text_field( wp_unslash( $_POST['ihq_video_id'] ) ) : '';
        if ( $replace_id === '' ) {
            $redirect( 'err_missing' );
        }
        $found = false;
        foreach ( $items as $index => $row ) {
            if ( $row['id'] !== $replace_id ) {
                continue;
            }
            $items[ $index ]['url']        = $url;
            $items[ $index ]['subject']    = $subject;
            $items[ $index ]['updated_at'] = $now;
            if ( empty( $items[ $index ]['status'] ) ) {
                $items[ $index ]['status'] = ihq_video_status_auto_promoted();
            }
            $found = true;
            break;
        }
        if ( ! $found ) {
            $redirect( 'err_missing' );
        }
        ihq_save_video_submissions( $user_id, $items );
        $redirect( 'replaced' );
    }

    if ( $action === 'add' ) {
        if ( count( $items ) >= ihq_video_submission_max() ) {
            $redirect( 'err_limit' );
        }
        $items[] = array(
            'id'         => wp_generate_uuid4(),
            'url'        => $url,
            'subject'    => $subject,
            'status'     => ihq_video_status_auto_promoted(),
            'created_at' => $now,
            'updated_at' => $now,
        );
        ihq_save_video_submissions( $user_id, $items );
        $redirect( 'saved' );
    }

    $redirect( 'err_missing' );
}

get_header();

// Load styles before content to prevent FOUC
get_template_part( 'template-parts/portal-styles' );
?>
<style id="ihq-profile-figma">
/* Profile layout — Figma Settings_Desktop (79:4533). Scoped so live can ship this PHP file without merging portal-styles. */
#portal-content.sett-wrap {
    max-width: 1120px;
    margin-left: auto;
    margin-right: auto;
    padding-left: 24px;
    padding-right: 24px;
}
#portal-content .sett-content {
    padding-bottom: 96px;
    font-family: 'Be Vietnam Pro', sans-serif;
}
#portal-content .sett-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 18px;
    padding: 28px 0 12px;
}
#portal-content .sett-header-icon {
    width: clamp(72px, 7vw, 134px);
    height: clamp(72px, 7vw, 134px);
    object-fit: contain;
}
#portal-content .sett-title {
    font-family: 'Cinzel', serif;
    font-size: clamp(40px, 5vw, 63px);
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.04em;
    line-height: 1.1;
    margin: 0;
}
#portal-content .sett-sep {
    height: 9px;
    margin: 8px 0 28px;
    background: radial-gradient(ellipse 55% 100% at 50% 50%, rgba(184,151,47,.9) 0%, rgba(184,151,47,0) 100%);
}
#portal-content .sett-identity {
    display: flex;
    align-items: center;
    gap: clamp(24px, 4vw, 48px);
    padding: 8px 0 36px;
}
#portal-content .sett-avatar-ring {
    width: clamp(150px, 17vw, 326px);
    height: clamp(150px, 17vw, 326px);
    border-radius: 50%;
    border: clamp(6px, 0.6vw, 12px) solid #fff;
    overflow: hidden;
    flex-shrink: 0;
}
#portal-content .sett-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
#portal-content .sett-identity-body {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}
#portal-content .sett-display-name {
    font-family: 'Be Vietnam Pro', sans-serif;
    font-size: clamp(30px, 3.9vw, 74px);
    font-weight: 700;
    color: #fff;
    line-height: 1.06;
}
#portal-content .sett-user-handle {
    font-family: 'Be Vietnam Pro', sans-serif;
    font-size: clamp(24px, 3.4vw, 65px);
    font-weight: 700;
    color: #616161;
    line-height: 1.06;
    overflow-wrap: anywhere;
}
#portal-content .sett-social-row {
    display: flex;
    align-items: center;
    gap: clamp(12px, 1.4vw, 26px);
    margin-top: clamp(10px, 1vw, 20px);
}
#portal-content .sett-soc-icon {
    width: clamp(28px, 2.7vw, 52px);
    height: clamp(28px, 2.7vw, 52px);
    object-fit: contain;
    opacity: 1;
}
#portal-content .sett-gameplay-promo {
    margin: 8px 0 36px;
}
#portal-content .sett-gameplay-promo-text {
    font-family: 'Be Vietnam Pro', sans-serif;
    font-size: clamp(20px, 2vw, 39px);
    font-weight: 600;
    line-height: 1.35;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    margin: 0 0 20px;
    max-width: none;
}
#portal-content .sett-gameplay-with-coach {
    position: relative;
    overflow: visible;
}
#portal-content .sett-gameplay-main {
    width: 100%;
    padding-right: 0;
}
#portal-content .sett-gameplay-promo-card,
#portal-content .sett-card {
    background: #000;
    border: 3px solid #b8972f;
    border-radius: 16px;
    margin-bottom: 16px;
    overflow: hidden;
}
#portal-content .sett-gameplay-promo-card {
    padding: 18px 8px 8px;
}
#portal-content .ihq-video-item-label {
    margin: 0 20px 12px;
    font-size: clamp(15px, 1.35vw, 24px);
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #b8972f;
}
#portal-content .sett-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 64px;
    padding: 10px 28px;
    border-bottom: 3px solid rgba(230,230,230,.85);
    gap: 16px;
}
#portal-content .sett-row:last-of-type {
    border-bottom: none;
}
#portal-content .sett-gameplay-promo-card .sett-row {
    min-height: 56px;
    padding: 8px 20px;
}
#portal-content .sett-row-lbl {
    font-family: 'Be Vietnam Pro', sans-serif;
    font-size: clamp(17px, 1.7vw, 32px);
    font-weight: 400;
    color: #fff;
    flex: 0 1 auto;
    min-width: 0;
    max-width: none;
}
#portal-content .sett-row-val {
    width: auto;
    flex: 1 1 auto;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    min-width: 0;
}
#portal-content .sett-editable,
#portal-content .hq-game-url-input,
#portal-content .sett-timezone-select,
#portal-content .sett-place-select,
#portal-content .celeb-select {
    font-family: 'Be Vietnam Pro', sans-serif;
    font-size: clamp(17px, 1.7vw, 32px);
    color: #fff;
}
#portal-content .hq-game-url-input {
    background: transparent;
    border: none;
    border-bottom: 2px solid #b8972f;
    width: 100%;
    outline: none;
    padding: 6px 4px;
    text-align: right;
}
#portal-content .hq-game-url-save-btn,
#portal-content .sett-referral-copy-btn {
    background: none;
    border: 2px solid #b8972f;
    color: #b8972f;
    font-size: clamp(15px, 1.3vw, 22px);
    padding: 8px 18px;
    border-radius: 8px;
    cursor: pointer;
    flex-shrink: 0;
    margin-left: 8px;
}
#portal-content .ihq-video-preview {
    position: relative;
    margin: 4px 20px 8px;
    border: 1px solid #b8972f;
    border-radius: 10px;
    overflow: hidden;
    aspect-ratio: 16 / 9;
    background: #111;
}
#portal-content .ihq-video-preview iframe,
#portal-content .ihq-video-preview video {
    width: 100%;
    height: 100%;
    border: 0;
    display: block;
    background: #111;
}
#portal-content .ihq-video-item-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 12px 20px 16px;
}
#portal-content .ihq-video-remove-btn {
    border-color: rgba(255,107,107,.7);
    color: #ff8a8a;
}
#portal-content .sett-section-head {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin: clamp(26px, 3vw, 56px) 0 14px;
    padding-left: 4px;
    padding-right: 4px;
}
#portal-content .sett-section-head > .sett-section-title:first-child {
    min-width: 0;
    flex: 1;
}
#portal-content .sett-section-title {
    font-family: 'Be Vietnam Pro', sans-serif;
    font-size: clamp(21px, 2vw, 39px);
    font-weight: 600;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    line-height: 1.25;
}
#portal-content .sett-hint-text {
    font-size: clamp(14px, 1.35vw, 26px);
    color: #919191;
}
#portal-content .sett-arrow {
    font-size: clamp(18px, 1.6vw, 30px);
    color: #fff;
}
#portal-content .sett-quote,
#portal-content .sett-media-ask {
    font-family: 'Be Vietnam Pro', sans-serif;
    font-size: clamp(17px, 1.75vw, 34px);
    font-weight: 400;
    font-style: italic;
    color: #fff;
    line-height: 1.35;
    margin: 10px 0 24px;
}
#portal-content .sett-media-ask {
    font-style: normal;
    font-weight: 700;
    margin: 40px 0 48px;
}
#portal-content .sett-media-ask p {
    margin: 0 0 20px;
}
#portal-content .sett-change-photo {
    font-size: clamp(17px, 1.7vw, 32px);
    color: #919191;
}
#portal-content .profile-coach-fab-host {
    position: absolute;
    z-index: 5;
    right: -8px;
    top: 88px;
    width: 160px;
    height: 160px;
    pointer-events: none;
}
#portal-content .profile-coach-fab-host .ihq-concierge-fab {
    position: absolute !important;
    inset: 0;
    width: 100% !important;
    height: 100% !important;
    margin: 0;
    pointer-events: auto;
    right: auto;
    bottom: auto;
}
#portal-content .celeb-col-label {
    font-size: clamp(15px, 1.4vw, 26px);
}
#portal-content .celeb-select,
#portal-content .sett-place-select,
#portal-content .sett-timezone-select {
    background: #000;
    border: 2px solid #b8972f;
    border-radius: 10px;
    padding: 8px 10px;
    min-height: 48px;
}
#portal-content .contact-card {
    padding: 8px 20px 16px;
    box-shadow: 0 13px 13px rgba(0,0,0,.25);
}
#portal-content .contact-row {
    display: flex;
    align-items: center;
    gap: 0;
    min-height: 72px;
    padding: 0 24px;
    border-bottom: 3px solid rgba(230,230,230,.85);
    background: transparent;
}
#portal-content .contact-row:last-of-type {
    border-bottom: none;
}
#portal-content .contact-row-main {
    display: flex;
    align-items: center;
    gap: 18px;
    flex: 0 0 auto;
    padding: 12px 0;
    background: none;
}
#portal-content .contact-row-main:hover {
    background: none;
}
#portal-content .contact-check {
    margin: 0;
}
/* The gold square is the control; the native checkbox must never render beside it. */
#portal-content .contact-check-input {
    display: none;
}
#portal-content .contact-check-box {
    width: 36px;
    height: 36px;
    border: 3px solid #b8972f;
    border-radius: 6px;
    background: transparent;
    display: inline-block;
    box-sizing: border-box;
}
#portal-content .contact-row.is-selected .contact-check-box,
#portal-content .contact-check-input:checked + .contact-check-box {
    background: #b8972f;
}
#portal-content .contact-row-lbl {
    flex: 0 0 clamp(120px, 13vw, 250px);
    width: clamp(120px, 13vw, 250px);
    max-width: none;
    font-family: 'Be Vietnam Pro', sans-serif;
    font-size: clamp(17px, 1.7vw, 32px);
    font-weight: 400;
    color: #e5e5e5;
}
#portal-content .contact-row-addval {
    font-family: 'Be Vietnam Pro', sans-serif;
    font-size: clamp(15px, 1.35vw, 26px);
    color: #919191;
    font-style: normal;
    min-width: 80px;
}
#portal-content .contact-row-addval--filled {
    color: #919191;
    font-style: normal;
}
#portal-content .contact-row-expand {
    display: flex;
    align-items: center;
    flex: 1;
    min-width: 0;
    padding: 0 0 0 8px;
    border: none;
    background: none;
}
#portal-content .contact-row-expand[hidden] {
    display: none !important;
}
#portal-content .contact-toggles {
    display: none !important;
}
#portal-content .contact-input {
    width: min(470px, 100%);
    max-width: 100%;
    height: clamp(38px, 2.7vw, 52px);
    border: 3px dashed #fff;
    border-radius: 0;
    background: transparent;
    color: #fff;
    font-family: 'Be Vietnam Pro', sans-serif;
    font-size: clamp(15px, 1.35vw, 26px);
    text-align: left;
    padding: 6px 14px;
    outline: none;
}
#portal-content .contact-input::placeholder {
    color: #919191;
    font-style: normal;
}

#portal-content .sett-section-head--account {
    margin: 36px 0 12px;
    padding-left: 4px;
    padding-right: 4px;
}
#portal-content .sett-section-head--account .sett-hint-text {
    font-size: clamp(16px, 1.6vw, 22px);
    color: #919191;
    text-transform: lowercase;
}
#portal-content .sett-section-head--account .sett-info-icon {
    display: none;
}
#portal-content .sett-account-card {
    padding: 10px 8px 18px;
}
#portal-content .sett-account-card .sett-row {
    min-height: 68px;
    padding: 8px 32px;
    border-bottom: 3px solid rgba(230,230,230,.85);
}
#portal-content .sett-account-card .sett-row:last-child {
    border-bottom: none;
}
#portal-content .sett-account-card .sett-row-lbl {
    flex: 0 1 auto;
    max-width: none;
    font-size: clamp(17px, 1.7vw, 32px);
    font-weight: 400;
    white-space: nowrap;
}
/* Values stay on one line like the mock; long emails and referral URLs truncate. */
#portal-content .sett-account-card .sett-row-val > * {
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
#portal-content .sett-account-card .sett-row--name .sett-row-lbl,
#portal-content .sett-account-card .sett-row--name .sett-editable {
    font-weight: 700;
}
#portal-content .sett-account-card .sett-editable {
    text-align: right;
}
#portal-content .sett-account-card .sett-email-link {
    text-decoration: underline;
    text-underline-offset: 3px;
}
#portal-content .sett-account-card .sett-referral-url {
    color: #828282;
    font-style: italic;
    text-decoration: underline;
    text-underline-offset: 3px;
    font-size: clamp(17px, 1.7vw, 32px);
    text-align: right;
}
#portal-content .sett-account-card .sett-referral-url--has-value {
    color: #fff;
    font-style: normal;
}
#portal-content .sett-account-card .sett-referral-copy-btn,
#portal-content .sett-account-card #portal-username-save-btn {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
}
#portal-content .sett-account-card .sett-change-photo {
    font-size: clamp(17px, 1.7vw, 32px);
    color: #828282;
    text-decoration: underline;
    text-underline-offset: 3px;
    background: none;
    border: none;
    padding: 0;
}
#portal-content .sett-account-card .sett-username-at {
    color: #fff;
    font-size: clamp(17px, 1.7vw, 32px);
    font-weight: 400;
    line-height: 1;
}
#portal-content .sett-account-card .sett-portal-username-val {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0;
    width: auto;
}
#portal-content .sett-account-card .sett-portal-username-input {
    width: auto;
    min-width: 8ch;
    max-width: 28ch;
    border: none;
    border-bottom: none;
    padding: 0;
    text-align: left;
}
#portal-content .sett-account-card .sett-place-fields {
    align-items: flex-end;
    width: auto;
}
#portal-content .sett-account-card .sett-place-select,
#portal-content .sett-account-card .sett-timezone-select {
    border: none;
    background: transparent;
    text-align: right;
    text-align-last: right;
    appearance: none;
    -webkit-appearance: none;
    min-height: 0;
    padding: 0;
    max-width: 100%;
    cursor: pointer;
    border-radius: 0;
}
#portal-content .sett-account-card .sett-portal-username-feedback {
    padding: 0 32px 8px;
    margin: 0;
    text-align: right;
}

@media (max-width: 900px) {
    #portal-content .sett-identity {
        flex-direction: column;
        align-items: flex-start;
    }
    #portal-content .profile-coach-fab-host {
        position: relative;
        right: auto;
        top: auto;
        margin: 16px 0 0 auto;
    }
    #portal-content .sett-row {
        flex-wrap: wrap;
        min-height: 0;
        padding: 12px 16px;
    }
}
</style>
<?php

$user            = wp_get_current_user();
$display_name    = $user->display_name ?: $user->user_login;
$first_name      = get_user_meta( $user->ID, 'first_name', true ) ?: $user->first_name;
$last_name       = get_user_meta( $user->ID, 'last_name',  true ) ?: $user->last_name;
$full_name       = trim( $first_name . ' ' . $last_name );
if ( $full_name === '' ) {
    $full_name = $display_name;
}
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
    $user_avatar = get_avatar_url( $user->ID, [ 'size' => 320 ] );
}

$social_handles  = get_user_meta( $user->ID, '_ihq_social_handles',  true );
if ( ! is_array( $social_handles ) )  { $social_handles  = []; }

$platform_handle_raw    = get_user_meta( $user->ID, 'platform_handle', true );
$platform_handle_pairs  = ihq_parse_platform_handle_pairs( $platform_handle_raw );
$ihq_profile_social_placeholder = __( 'handle or URL', 'influencer-hq' );
$ihq_profile_social_platforms   = array(
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

$video_submissions = ihq_get_video_submissions( $user->ID );
$video_feedback    = isset( $_GET['ihq_video'] ) ? sanitize_key( wp_unslash( $_GET['ihq_video'] ) ) : '';

$contact_platforms = array(
    array( 'key' => 'email', 'label' => 'Email' ),
    array( 'key' => 'facebook', 'label' => 'Facebook' ),
    array( 'key' => 'instagram', 'label' => 'Instagram' ),
    array( 'key' => 'kakaotalk', 'label' => 'KakaoTalk' ),
    array( 'key' => 'kick', 'label' => 'KICK' ),
    array( 'key' => 'line', 'label' => 'Line' ),
    array( 'key' => 'tiktok', 'label' => 'TikTok' ),
    array( 'key' => 'twitch', 'label' => 'Twitch' ),
    array( 'key' => 'wechat', 'label' => 'WeChat' ),
    array( 'key' => 'whatsapp', 'label' => 'WhatsApp' ),
    array( 'key' => 'x', 'label' => 'X' ),
);

$ihq_profile_countries = array(
    'Australia', 'Canada', 'China', 'France', 'Germany', 'Hong Kong', 'India', 'Indonesia',
    'Italy', 'Japan', 'Malaysia', 'Mexico', 'Netherlands', 'New Zealand', 'Philippines',
    'Serbia', 'Singapore', 'South Africa', 'South Korea', 'Spain', 'Taiwan', 'Thailand',
    'United Arab Emirates', 'United Kingdom', 'United States', 'Vietnam',
);
$ihq_profile_cities = array(
    'Atlanta', 'Bangkok', 'Beijing', 'Belgrade', 'Hong Kong', 'Jakarta', 'Kuala Lumpur',
    'London', 'Los Angeles', 'Manila', 'Melbourne', 'Mexico City', 'Mumbai', 'New York',
    'Paris', 'Seoul', 'Shanghai', 'Singapore', 'Sydney', 'Taipei', 'Tokyo', 'Toronto',
);

$_settings_nonce = wp_create_nonce( 'settings_save_nonce' );
$ihq_oauth_sso_nonce = wp_create_nonce( 'ihq_oauth_sso_nonce' );
$ihq_oauth_session_url_stored = function_exists( 'ihq_oauth_start_session_url_meta_key' )
    ? (string) get_user_meta( $user->ID, ihq_oauth_start_session_url_meta_key(), true )
    : '';
$ihq_oauth_session_url_default = function_exists( 'ihq_oauth_start_session_default_url' )
    ? ihq_oauth_start_session_default_url()
    : '';
$ihq_current_sso_code = function_exists( 'ihq_get_hq_sso_code_for_user' )
    ? ihq_get_hq_sso_code_for_user( $user->ID )
    : '';
$ihq_resolved_oauth_session_url = function_exists( 'ihq_get_oauth_start_session_url_for_user' )
    ? ihq_get_oauth_start_session_url_for_user( $user->ID )
    : $ihq_oauth_session_url_default;
?>

    <main id="primary" class="site-main">
        
        <?php get_template_part( 'template-parts/portal-header' ); ?>
        
        <div class="container py-2 sett-wrap" id="portal-content">

            <div class="sett-content">

                <?php
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

                <div class="sett-gameplay-promo sett-gameplay-with-coach ihq-video-submissions">
                    <div class="sett-gameplay-main">
                    <p class="sett-gameplay-promo-text">
                        <?php esc_html_e( 'Share up to five video links from any platform. Add a short subject for each one so Influencer HQ knows what to promote. Saved videos go into automatic worldwide promotion immediately.', 'influencer-hq' ); ?>
                    </p>
                    <?php
                    $video_feedback_ok  = array(
                        'saved'    => __( 'Saved. This video is now in automatic promotion.', 'influencer-hq' ),
                        'replaced' => __( 'Updated. The replacement is now in automatic promotion.', 'influencer-hq' ),
                        'removed'  => __( 'Removed. That video is no longer in promotion.', 'influencer-hq' ),
                    );
                    $video_feedback_err = array(
                        'err_url'     => __( 'Enter a valid http or https link from any video platform.', 'influencer-hq' ),
                        'err_subject' => __( 'Subject is required (50 characters max).', 'influencer-hq' ),
                        'err_limit'   => __( 'You already have 5 videos. Remove or replace one to add another.', 'influencer-hq' ),
                        'err_missing' => __( 'That video could not be found. Refresh and try again.', 'influencer-hq' ),
                    );
                    if ( isset( $video_feedback_ok[ $video_feedback ] ) ) :
                        ?>
                        <p class="sett-gameplay-promo-feedback sett-gameplay-promo-feedback--ok" role="status">&#10003; <?php echo esc_html( $video_feedback_ok[ $video_feedback ] ); ?></p>
                    <?php elseif ( isset( $video_feedback_err[ $video_feedback ] ) ) : ?>
                        <p class="sett-gameplay-promo-feedback sett-gameplay-promo-feedback--err" role="alert"><?php echo esc_html( $video_feedback_err[ $video_feedback ] ); ?></p>
                    <?php endif; ?>

                    <?php foreach ( $video_submissions as $index => $submission ) : ?>
                    <form method="post" action="" class="ihq-video-item-form">
                        <?php wp_nonce_field( 'ihq_video_submissions_save' ); ?>
                        <input type="hidden" name="ihq_video_id" value="<?php echo esc_attr( $submission['id'] ); ?>">
                        <div class="sett-card sett-gameplay-promo-card">
                            <p class="ihq-video-item-label"><?php echo esc_html( sprintf( /* translators: %d: slot number */ __( 'Video %d of %d', 'influencer-hq' ), $index + 1, ihq_video_submission_max() ) ); ?></p>
                            <?php
                            $video_preview = function_exists( 'ihq_video_preview_source' )
                                ? ihq_video_preview_source( $submission['url'] )
                                : array( 'kind' => '', 'src' => '' );
                            if ( $video_preview['src'] !== '' ) :
                                ?>
                            <div class="ihq-video-preview">
                                <?php if ( $video_preview['kind'] === 'file' ) : ?>
                                    <video controls playsinline preload="metadata" src="<?php echo esc_url( $video_preview['src'] ); ?>"></video>
                                <?php else : ?>
                                    <iframe
                                        class="sett-gameplay-embed"
                                        src="<?php echo esc_url( $video_preview['src'] ); ?>"
                                        title="<?php esc_attr_e( 'Video preview', 'influencer-hq' ); ?>"
                                        allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                                        allowfullscreen
                                    ></iframe>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <div class="sett-row sett-gameplay-promo-row">
                                <label class="sett-row-lbl" for="ihq-video-subject-<?php echo esc_attr( $submission['id'] ); ?>"><?php esc_html_e( 'Subject', 'influencer-hq' ); ?></label>
                                <div class="sett-row-val sett-gameplay-promo-input-wrap">
                                    <input
                                        type="text"
                                        id="ihq-video-subject-<?php echo esc_attr( $submission['id'] ); ?>"
                                        name="ihq_video_subject"
                                        value="<?php echo esc_attr( $submission['subject'] ); ?>"
                                        maxlength="<?php echo (int) ihq_video_subject_max_length(); ?>"
                                        required
                                        class="hq-game-url-input"
                                    >
                                </div>
                            </div>
                            <div class="sett-row sett-gameplay-promo-row">
                                <label class="sett-row-lbl" for="ihq-video-url-<?php echo esc_attr( $submission['id'] ); ?>"><?php esc_html_e( 'Video link', 'influencer-hq' ); ?></label>
                                <div class="sett-row-val sett-gameplay-promo-input-wrap">
                                    <input
                                        type="url"
                                        id="ihq-video-url-<?php echo esc_attr( $submission['id'] ); ?>"
                                        name="ihq_video_url"
                                        value="<?php echo esc_attr( $submission['url'] ); ?>"
                                        placeholder="<?php esc_attr_e( 'https://', 'influencer-hq' ); ?>"
                                        required
                                        class="hq-game-url-input"
                                        autocomplete="url"
                                    >
                                </div>
                            </div>
                            <div class="ihq-video-item-actions">
                                <button type="submit" name="ihq_video_action" value="replace" class="hq-game-url-save-btn"><?php esc_html_e( 'Replace', 'influencer-hq' ); ?></button>
                                <button type="submit" name="ihq_video_action" value="remove" class="hq-game-url-save-btn ihq-video-remove-btn" data-ihq-video-remove="1"><?php esc_html_e( 'Remove', 'influencer-hq' ); ?></button>
                            </div>
                        </div>
                    </form>
                    <?php endforeach; ?>

                    <?php if ( count( $video_submissions ) < ihq_video_submission_max() ) : ?>
                    <form method="post" action="" class="ihq-video-add-form">
                        <?php wp_nonce_field( 'ihq_video_submissions_save' ); ?>
                        <div class="sett-card sett-gameplay-promo-card">
                            <p class="ihq-video-item-label"><?php esc_html_e( 'Add a video', 'influencer-hq' ); ?></p>
                            <div class="sett-row sett-gameplay-promo-row">
                                <label class="sett-row-lbl" for="ihq_video_subject_new"><?php esc_html_e( 'Subject', 'influencer-hq' ); ?></label>
                                <div class="sett-row-val sett-gameplay-promo-input-wrap">
                                    <input
                                        type="text"
                                        id="ihq_video_subject_new"
                                        name="ihq_video_subject"
                                        value=""
                                        maxlength="<?php echo (int) ihq_video_subject_max_length(); ?>"
                                        required
                                        class="hq-game-url-input"
                                        placeholder="<?php esc_attr_e( 'What should we promote?', 'influencer-hq' ); ?>"
                                    >
                                </div>
                            </div>
                            <div class="sett-row sett-gameplay-promo-row">
                                <label class="sett-row-lbl" for="ihq_video_url_new"><?php esc_html_e( 'Video link', 'influencer-hq' ); ?></label>
                                <div class="sett-row-val sett-gameplay-promo-input-wrap">
                                    <input
                                        type="url"
                                        id="ihq_video_url_new"
                                        name="ihq_video_url"
                                        value=""
                                        placeholder="<?php esc_attr_e( 'https://', 'influencer-hq' ); ?>"
                                        required
                                        class="hq-game-url-input"
                                        autocomplete="url"
                                    >
                                </div>
                            </div>
                            <div class="ihq-video-item-actions">
                                <button type="submit" name="ihq_video_action" value="add" class="hq-game-url-save-btn"><?php esc_html_e( 'Save', 'influencer-hq' ); ?></button>
                            </div>
                        </div>
                    </form>
                    <?php endif; ?>
                    </div>
                    <div class="profile-coach-fab-host" id="profile-coach-fab-host" aria-hidden="true"></div>
                </div>

                <!-- ACCOUNT INFORMATION -->
                <div class="sett-section-head sett-section-head--account">
                    <span class="sett-section-title">ACCOUNT &nbsp;INFORMATION</span>
                    <span class="sett-hint"><span class="sett-hint-text">click to enter / edit</span><span class="sett-info-icon">i<span class="sett-info-tooltip">Entered information is saved by pressing Enter key, or by clicking anywhere outside the input field.</span></span></span>
                </div>

                <div class="sett-card sett-account-card">
                    <div class="sett-row sett-row--name">
                        <div class="sett-row-lbl"><?php esc_html_e( 'Name', 'influencer-hq' ); ?></div>
                        <div class="sett-row-val">
                            <span class="sett-editable" data-group="account" data-field="name"><?php echo esc_html( $full_name ); ?></span>
                        </div>
                    </div>
                    <div class="sett-row sett-portal-username-row" id="portal-username-setup-zone">
                        <label for="portal-username-input" class="sett-row-lbl"><?php esc_html_e( 'Username', 'influencer-hq' ); ?></label>
                        <div class="sett-row-val sett-portal-username-val">
                            <span class="sett-username-at" aria-hidden="true">@</span>
                            <input
                                type="text"
                                id="portal-username-input"
                                class="hq-game-url-input sett-portal-username-input"
                                value="<?php echo esc_attr( $portal_username ); ?>"
                                placeholder="<?php esc_attr_e( 'username', 'influencer-hq' ); ?>"
                                autocomplete="username"
                                maxlength="30"
                                spellcheck="false"
                            >
                            <button type="button" id="portal-username-save-btn" class="hq-game-url-save-btn"><?php esc_html_e( 'Save', 'influencer-hq' ); ?></button>
                        </div>
                    </div>
                    <p class="sett-portal-username-feedback sett-portal-username-feedback--ok" id="portal-username-ok" hidden></p>
                    <p class="sett-portal-username-feedback sett-portal-username-feedback--err" id="portal-username-err" hidden></p>
                    <div class="sett-row">
                        <div class="sett-row-lbl"><?php esc_html_e( 'Follower Referral link:', 'influencer-hq' ); ?></div>
                        <div class="sett-row-val sett-referral-inline">
                            <span class="sett-referral-url" id="profile-follower-referral-url"><?php esc_html_e( 'link appears here', 'influencer-hq' ); ?></span>
                            <button type="button" class="sett-referral-copy-btn" data-referral-target="profile-follower-referral-url"><?php esc_html_e( 'copy', 'influencer-hq' ); ?></button>
                        </div>
                    </div>
                    <div class="sett-row">
                        <div class="sett-row-lbl"><?php esc_html_e( 'Influencer Referral link:', 'influencer-hq' ); ?></div>
                        <div class="sett-row-val sett-referral-inline">
                            <span class="sett-referral-url" id="profile-influencer-referral-url"><?php esc_html_e( 'link appears here', 'influencer-hq' ); ?></span>
                            <button type="button" class="sett-referral-copy-btn" data-referral-target="profile-influencer-referral-url"><?php esc_html_e( 'copy', 'influencer-hq' ); ?></button>
                        </div>
                    </div>
                    <?php
                    $country_options = $ihq_profile_countries;
                    if ( $user_country !== '' && ! in_array( $user_country, $country_options, true ) ) {
                        array_unshift( $country_options, $user_country );
                    }
                    $city_options = $ihq_profile_cities;
                    if ( $user_city !== '' && ! in_array( $user_city, $city_options, true ) ) {
                        array_unshift( $city_options, $user_city );
                    }
                    $country_is_listed = ( $user_country !== '' && in_array( $user_country, $country_options, true ) );
                    $city_is_listed    = ( $user_city !== '' && in_array( $user_city, $city_options, true ) );
                    $acct_rows = [
                        [ 'key' => 'email',    'label' => 'Email',                  'value' => $user_email,    'type' => 'email'  ],
                        [ 'key' => 'country',  'label' => 'Country',                'value' => $user_country,  'type' => 'place'  ],
                        [ 'key' => 'city',     'label' => 'City',                   'value' => $user_city,     'type' => 'place'  ],
                        [ 'key' => 'timezone', 'label' => 'Time Zone',              'value' => $user_timezone, 'type' => 'timezone' ],
                        [ 'key' => 'avatar',   'label' => 'Profile Photo or Avatar','value' => '',             'type' => 'avatar' ],
                    ];
                    foreach ( $acct_rows as $row ) :
                    ?>
                    <div class="sett-row">
                        <div class="sett-row-lbl"><?php echo esc_html( $row['label'] ); ?></div>
                        <div class="sett-row-val">
                            <?php if ( $row['type'] === 'email' ) : ?>
                                <span class="sett-editable sett-email-link" data-group="account" data-field="email"><?php echo esc_html( $row['value'] ); ?></span>
                            <?php elseif ( $row['type'] === 'avatar' ) : ?>
                                <button type="button" class="sett-change-photo" id="sett-avatar-btn"><?php esc_html_e( 'Add or Change Photo', 'influencer-hq' ); ?></button>
                            <?php elseif ( $row['type'] === 'place' ) : ?>
                                <?php
                                $place_options = $row['key'] === 'country' ? $country_options : $city_options;
                                $place_listed  = $row['key'] === 'country' ? $country_is_listed : $city_is_listed;
                                ?>
                                <div class="sett-place-fields">
                                    <select class="sett-place-select" data-group="account" data-field="<?php echo esc_attr( $row['key'] ); ?>">
                                        <option value=""><?php esc_html_e( 'Select', 'influencer-hq' ); ?></option>
                                        <?php foreach ( $place_options as $place_name ) : ?>
                                        <option value="<?php echo esc_attr( $place_name ); ?>"<?php selected( $row['value'], $place_name ); ?>><?php echo esc_html( $place_name ); ?></option>
                                        <?php endforeach; ?>
                                        <option value="__other__"<?php selected( $row['value'] !== '' && ! $place_listed ); ?>><?php esc_html_e( 'Other…', 'influencer-hq' ); ?></option>
                                    </select>
                                    <input
                                        type="text"
                                        class="sett-place-other hq-game-url-input"
                                        data-place-field="<?php echo esc_attr( $row['key'] ); ?>"
                                        value="<?php echo $place_listed ? '' : esc_attr( $row['value'] ); ?>"
                                        placeholder="<?php esc_attr_e( 'Type your own', 'influencer-hq' ); ?>"
                                        <?php echo ( $row['value'] !== '' && ! $place_listed ) ? '' : 'hidden'; ?>
                                    >
                                </div>
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

                <div class="sett-media-ask">
                    <p><?php esc_html_e( 'Send us your face. Let us put you everywhere.', 'influencer-hq' ); ?></p>
                    <p><?php esc_html_e( 'You built something real. Now help us put it in front of the world.', 'influencer-hq' ); ?></p>
                    <p><?php esc_html_e( 'To promote you the way we promised, we need two things from you:', 'influencer-hq' ); ?></p>
                    <p><?php esc_html_e( 'A short video — 30 seconds, headset on, behind your microphone. Speak in your own voice, in your own language, the way you always do. This becomes the foundation for how we introduce you to new audiences across every market we reach.', 'influencer-hq' ); ?></p>
                    <p><?php esc_html_e( 'A still photo — clear, bright, and unmistakably you. This is the face our campaigns put in front of millions of prospective followers. It belongs everywhere your name appears.', 'influencer-hq' ); ?></p>
                    <p><?php esc_html_e( 'Why this matters: every Influencer who shares their video and photo gets put into rotation across our promotion channels. Without them, we can’t promote you the way we want to. With them, you become impossible to overlook.', 'influencer-hq' ); ?></p>
                    <p><?php esc_html_e( 'This is the difference between a name on a list and a presence in the world.', 'influencer-hq' ); ?></p>
                    <p><?php esc_html_e( 'Send us yours. We’ll do the rest.', 'influencer-hq' ); ?></p>
                </div>

                <!-- CELEBRITY FOLLOWERS LEAGUES -->
                <div class="sett-section-head sett-section-head--comm celeb-leagues-head" id="celebLeaguesHead" style="cursor:pointer;">
                    <span class="sett-section-title">CELEBRITY &nbsp;FOLLOWERS &nbsp;LEAGUES</span>
                    <span class="sett-arrow" id="celebLeaguesArrow">▼</span>
                </div>

                <div id="celebLeaguesBody">
                    <p class="sett-quote" style="margin-top:0;"><em><?php esc_html_e( 'Select or Change your favorite celebrity in all three categories. Influencer Headquarters will be promoting you as a Team Captain with all new game participants.', 'influencer-hq' ); ?></em></p>

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
                    <span class="sett-section-title"><?php esc_html_e( 'International League Team', 'influencer-hq' ); ?></span>
                    <span class="sett-arrow" id="intlLeagueArrow">▼</span>
                </div>

                <div id="intlLeagueBody">
                    <p class="sett-quote" style="margin-top:0;"><em><?php esc_html_e( 'Select or Change your favorite team.', 'influencer-hq' ); ?></em></p>
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

                <div class="sett-section-head sett-section-head--comm" id="socialMediaHead" style="cursor:pointer;">
                    <span class="sett-section-title"><?php esc_html_e( 'Social Media Platforms Where You Post', 'influencer-hq' ); ?></span>
                    <span class="sett-arrow" id="socialMediaArrow">▼</span>
                </div>

                <div id="socialMediaBody">
                    <p class="sett-quote" style="margin-top:0;"><em><?php esc_html_e( 'Tell us where you post and your username so we can assist you with creating content tuned to the competition, built to drive more participation from your followers and increase your opportunity to earn equity.', 'influencer-hq' ); ?></em></p>
                    <div class="sett-card contact-card" id="contactBody">
                        <?php
                        foreach ( $contact_platforms as $cp ) :
                            $ckey  = $cp['key'];
                            $clabel = $cp['label'];
                            $cval  = $social_handles[ $ckey ] ?? '';
                            if ( $cval === '' ) {
                                foreach ( $platform_handle_pairs as $pair_label => $pair_val ) {
                                    if ( strcasecmp( $pair_label, $clabel ) === 0 ) {
                                        $cval = $pair_val;
                                        break;
                                    }
                                }
                            }
                            $ccomm = ! empty( $comm_prefs[ $ckey ] );
                            $is_selected = $cval !== '';
                        ?>
                        <div class="contact-row<?php echo $is_selected ? ' is-selected' : ''; ?>" data-key="<?php echo esc_attr( $ckey ); ?>" data-social-label="<?php echo esc_attr( $clabel ); ?>">
                            <div class="contact-row-main">
                                <label class="contact-check">
                                    <input type="checkbox" class="contact-check-input"<?php checked( $is_selected ); ?>>
                                    <span class="contact-check-box" aria-hidden="true"></span>
                                </label>
                                <span class="contact-row-lbl"><?php echo esc_html( $clabel ); ?></span>
                                <span class="contact-row-addval<?php echo $cval ? ' contact-row-addval--filled' : ''; ?>"<?php echo $is_selected ? ' hidden' : ''; ?>><?php echo $cval ? esc_html( $cval ) : esc_html__( 'add', 'influencer-hq' ); ?></span>
                            </div>
                            <div class="contact-row-expand"<?php echo $is_selected ? '' : ' hidden'; ?>>
                                <input type="text" class="contact-input sett-social-handle-input" data-social-key="<?php echo esc_attr( $ckey ); ?>" value="<?php echo esc_attr( $cval ); ?>" placeholder="<?php esc_attr_e( 'click to enter / edit', 'influencer-hq' ); ?>">
                                <div class="contact-toggles" hidden>
                                    <label class="contact-toggle<?php echo $ccomm ? ' contact-toggle--on' : ''; ?>">
                                        <input type="checkbox" class="contact-toggle-cb" data-type="comm"<?php checked( $ccomm ); ?>>
                                        <span class="contact-toggle-label"><?php esc_html_e( 'Communicate with Me', 'influencer-hq' ); ?></span>
                                        <span class="contact-toggle-track"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <details class="sett-card ihq-dev-tools" style="margin-top:32px;padding:0;">
                    <summary style="cursor:pointer;padding:18px 20px;font-size:14px;font-weight:700;letter-spacing:.08em;color:#b8972f;">
                        <?php esc_html_e( 'DEV TOOLS', 'influencer-hq' ); ?>
                    </summary>
                    <div style="padding:0 16px 16px;">
                        <?php
                        if ( function_exists( 'ihq_visitor_intent_cookie_name' ) ) {
                            get_template_part( 'template-parts/visitor-intent-test-registry' );
                        }

                        $ihq_start_session_dump = (string) get_user_meta( $user->ID, 'ihq_oauth_start_session_last', true );
                        if ( $ihq_start_session_dump === '' ) {
                            $ihq_start_session_dump = __( 'No start-session response saved yet. Use Request SSO again below.', 'influencer-hq' );
                        }
                        ?>
                        <form method="post" action="" class="ihq-oauth-session-url-form">
                            <?php wp_nonce_field( 'ihq_oauth_start_session_url_save' ); ?>
                            <div class="sett-card" style="margin-bottom:14px;border:1px dashed rgba(184,151,47,.45);">
                                <p style="margin:0 0 10px;font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#b8972f;">
                                    <?php esc_html_e( 'OAuth start-session API URL', 'influencer-hq' ); ?>
                                </p>
                                <div class="sett-row">
                                    <label for="ihq_oauth_start_session_url" class="sett-row-lbl"><?php esc_html_e( 'API URL', 'influencer-hq' ); ?></label>
                                    <div class="sett-row-val" style="width:auto;flex:1;">
                                        <input
                                            type="url"
                                            id="ihq_oauth_start_session_url"
                                            name="ihq_oauth_start_session_url"
                                            value="<?php echo esc_attr( $ihq_oauth_session_url_stored ); ?>"
                                            placeholder="<?php echo esc_attr( $ihq_oauth_session_url_default ); ?>"
                                            class="hq-game-url-input"
                                        >
                                    </div>
                                    <button type="submit" name="ihq_oauth_start_session_url_submit" class="hq-game-url-save-btn"><?php esc_html_e( 'Save', 'influencer-hq' ); ?></button>
                                </div>
                                <div class="sett-row" style="border-bottom:none;">
                                    <span class="sett-row-lbl" style="color:#616161;font-size:13px;"><?php esc_html_e( 'Active URL', 'influencer-hq' ); ?></span>
                                    <span id="ihq-oauth-active-url" style="font-size:13px;color:#616161;flex:1;text-align:right;word-break:break-all;"><?php echo esc_html( $ihq_resolved_oauth_session_url ); ?></span>
                                </div>
                                <?php if ( isset( $_GET['ihq_oauth_url_saved'] ) ) : ?>
                                <p style="color:#7CCA8A;font-size:13px;margin:10px 0 0;">&#10003; <?php esc_html_e( 'API URL saved. Click Request SSO again to run start-session against this URL.', 'influencer-hq' ); ?></p>
                                <?php endif; ?>
                                <div class="sett-row" style="border-bottom:none;margin-top:12px;align-items:center;">
                                    <span class="sett-row-lbl"><?php esc_html_e( 'SSO code', 'influencer-hq' ); ?></span>
                                    <span id="ihq-oauth-sso-code-display" style="font-size:13px;color:#e6cfa0;flex:1;text-align:right;word-break:break-all;"><?php echo $ihq_current_sso_code !== '' ? esc_html( $ihq_current_sso_code ) : esc_html__( 'Not set yet', 'influencer-hq' ); ?></span>
                                </div>
                                <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
                                    <button type="button" id="ihq-request-sso-again-btn" class="hq-game-url-save-btn"><?php esc_html_e( 'Request SSO again', 'influencer-hq' ); ?></button>
                                    <span id="ihq-request-sso-again-status" style="font-size:13px;color:#b8972f;" hidden></span>
                                </div>
                                <p style="margin:14px 0 8px;font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#b8972f;">
                                    <?php esc_html_e( 'OAuth start-session response (last request)', 'influencer-hq' ); ?>
                                </p>
                                <pre id="ihq-oauth-start-session-dump" style="margin:0;padding:10px 12px;max-height:320px;overflow:auto;font-size:11px;line-height:1.45;color:#ddd;background:rgba(0,0,0,.35);border-radius:6px;white-space:pre-wrap;word-break:break-word;"><?php echo esc_html( $ihq_start_session_dump ); ?></pre>
                            </div>
                        </form>

                        <?php
                        $hq_current_url = get_user_meta( $user->ID, 'hq_game_url', true );
                        $hq_default_url = ihq_env_require_url( 'IHQ_GAME_PORTAL_BASE_URL' );
                        ?>
                        <form method="post" action="" class="hq-game-url-form">
                            <?php wp_nonce_field( 'hq_game_url_save' ); ?>
                            <div class="sett-card" style="margin-bottom:14px;">
                                <div class="sett-row">
                                    <label for="hq_game_url" class="sett-row-lbl"><?php esc_html_e( 'Game Portal URL', 'influencer-hq' ); ?></label>
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
                                    <button type="submit" name="hq_game_url_submit" class="hq-game-url-save-btn"><?php esc_html_e( 'Save', 'influencer-hq' ); ?></button>
                                </div>
                                <div class="sett-row" style="border-bottom:none;">
                                    <span class="sett-row-lbl" style="color:#616161;font-size:13px;"><?php esc_html_e( 'Default', 'influencer-hq' ); ?></span>
                                    <span style="font-size:13px;color:#616161;flex:1;text-align:right;word-break:break-all;"><?php echo esc_html( $hq_default_url ); ?></span>
                                </div>
                            </div>
                            <?php if ( isset( $_GET['hq_saved'] ) ) : ?>
                            <p style="color:#7CCA8A;font-size:13px;margin:-8px 0 10px;">&#10003; <?php esc_html_e( 'Saved.', 'influencer-hq' ); ?></p>
                            <?php endif; ?>
                        </form>
                    </div>
                </details>

            </div><!-- .sett-content -->
            
        </div>
        
        <?php get_template_part( 'template-parts/portal-footer' ); ?>

    </main><!-- #main -->


<script>
(function(){
    var _ajax  = <?php echo wp_json_encode( admin_url('admin-ajax.php') ); ?>;
    var _nonce = <?php echo wp_json_encode( $_settings_nonce ); ?>;
    var _referralNonce = <?php echo wp_json_encode( $ihq_referral_nonce ); ?>;
    var _oauthSsoNonce = <?php echo wp_json_encode( $ihq_oauth_sso_nonce ); ?>;
    var _needsPortalUsername = <?php echo $needs_portal_username_setup ? 'true' : 'false'; ?>;
    var _portalUsernameSetupMsg = <?php echo wp_json_encode( __( 'Please create your username to be able to continue your journey on Influencer HQ', 'influencer-hq' ) ); ?>;

    /* ── Referral links (same API as Live Appearance page) ── */
    function setProfileReferralUrl(elId, url, fallback) {
        var el = document.getElementById(elId);
        if (!el) return;
        if (url) {
            el.textContent = url;
            el.classList.add('sett-referral-url--has-value');
        } else {
            el.textContent = fallback || 'link appears here';
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
                var url = (res.success && res.data && res.data.url) ? res.data.url : '';
                var follower = (res.data && res.data.follower_url) ? res.data.follower_url : url;
                var influencer = (res.data && res.data.influencer_url) ? res.data.influencer_url : url;
                if (url || follower || influencer) {
                    setProfileReferralUrl('profile-follower-referral-url', follower || url);
                    setProfileReferralUrl('profile-influencer-referral-url', influencer || url);
                    return;
                }
                var errMsg = (res.data && res.data.message) ? res.data.message : 'Referral link unavailable.';
                setProfileReferralUrl('profile-follower-referral-url', '', errMsg);
                setProfileReferralUrl('profile-influencer-referral-url', '', errMsg);
            }).catch(function() {
                setProfileReferralUrl('profile-follower-referral-url', '', 'Could not load referral link.');
                setProfileReferralUrl('profile-influencer-referral-url', '', 'Could not load referral link.');
            });
    })();

    document.querySelectorAll('.sett-referral-copy-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = btn.getAttribute('data-referral-target');
            var txt = targetId ? document.getElementById(targetId) : null;
            if (!txt || !txt.classList.contains('sett-referral-url--has-value')) {
                return;
            }
            navigator.clipboard.writeText(txt.textContent).then(function() {
                btn.textContent = 'copied!';
                window.setTimeout(function() {
                    btn.textContent = 'copy';
                }, 2000);
            });
        });
    });

    /* ── OAuth start-session: Request SSO again ── */
    var ihqRequestSsoBtn = document.getElementById('ihq-request-sso-again-btn');
    var ihqRequestSsoStatus = document.getElementById('ihq-request-sso-again-status');
    var ihqOauthDumpEl = document.getElementById('ihq-oauth-start-session-dump');
    var ihqOauthSsoCodeEl = document.getElementById('ihq-oauth-sso-code-display');
    var ihqOauthActiveUrlEl = document.getElementById('ihq-oauth-active-url');

    function ihqSetRequestSsoStatus(msg, isError) {
        if (!ihqRequestSsoStatus) {
            return;
        }
        ihqRequestSsoStatus.textContent = msg || '';
        ihqRequestSsoStatus.hidden = !msg;
        ihqRequestSsoStatus.style.color = isError ? '#f85149' : '#7CCA8A';
    }

    if (ihqRequestSsoBtn) {
        ihqRequestSsoBtn.addEventListener('click', function() {
            ihqSetRequestSsoStatus('', false);
            ihqRequestSsoBtn.disabled = true;
            var fd = new FormData();
            fd.append('action', 'ihq_request_sso_again');
            fd.append('nonce', _oauthSsoNonce);
            if (typeof window.ihqResolveClientCountryIsoAlpha2 === 'function') {
                fd.append('country_iso', window.ihqResolveClientCountryIsoAlpha2());
            }
            fetch(_ajax, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    ihqRequestSsoBtn.disabled = false;
                    var dump = (res.data && res.data.start_session_dump) ? res.data.start_session_dump : '';
                    if (dump && ihqOauthDumpEl) {
                        ihqOauthDumpEl.textContent = dump;
                    }
                    if (res.data && res.data.api_url && ihqOauthActiveUrlEl) {
                        ihqOauthActiveUrlEl.textContent = res.data.api_url;
                    }
                    if (!res.success) {
                        var errMsg = (res.data && res.data.message) ? res.data.message : 'SSO request failed.';
                        ihqSetRequestSsoStatus(errMsg, true);
                        return;
                    }
                    if (res.data && res.data.sso_code && ihqOauthSsoCodeEl) {
                        ihqOauthSsoCodeEl.textContent = res.data.sso_code;
                    }
                    ihqSetRequestSsoStatus((res.data && res.data.message) ? res.data.message : 'SSO request completed.', false);
                })
                .catch(function() {
                    ihqRequestSsoBtn.disabled = false;
                    ihqSetRequestSsoStatus('Network error. Please try again.', true);
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
        function savePortalUsername() {
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
        }
        portalUsernameSaveBtn.addEventListener('click', savePortalUsername);
        portalUsernameInput.addEventListener('blur', savePortalUsername);
        portalUsernameInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                portalUsernameInput.blur();
            }
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
            input.style.cssText = 'background:transparent;border:none;border-bottom:1px solid #b8972f;color:#fff;font-size:inherit;font-family:inherit;font-weight:inherit;text-align:right;width:100%;outline:none;';
            el.textContent = '';
            el.appendChild(input);
            input.focus();
            function commit(){
                var v = input.value.trim();
                el.textContent = v;
                var field = el.dataset.field;
                if ( field === 'name' ) {
                    save('save_settings_field', { group: el.dataset.group, field: 'name', value: v });
                    saveFullnameFromDisplay(v);
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
        var check  = row.querySelector('.contact-check-input');

        function setRowSelected(on) {
            row.classList.toggle('is-selected', on);
            if (check) {
                check.checked = on;
            }
            if (expand) {
                expand.hidden = !on;
            }
            if (valEl) {
                valEl.hidden = on;
            }
            if (!on) {
                input.value = '';
                if (valEl) {
                    valEl.textContent = 'add';
                    valEl.classList.remove('contact-row-addval--filled');
                }
                save('save_settings_field', { group: 'social', field: key, value: '' });
                ihqProfileSavePlatformHandle();
            }
        }

        if (check) {
            check.addEventListener('click', function(e){
                e.stopPropagation();
            });
            check.addEventListener('change', function(){
                setRowSelected(check.checked);
                if (check.checked) {
                    input.focus();
                }
            });
        }

        main.addEventListener('click', function(e){
            if (e.target.closest('.contact-check')) {
                return;
            }
            if (!row.classList.contains('is-selected')) {
                setRowSelected(true);
                input.focus();
            }
        });

        function commitInput(){
            var v = input.value.trim();
            valEl.textContent = v || 'add';
            v ? valEl.classList.add('contact-row-addval--filled') : valEl.classList.remove('contact-row-addval--filled');
            save('save_settings_field', { group: 'social', field: key, value: v });
            ihqProfileSavePlatformHandle();
        }
        input.addEventListener('blur', commitInput);
        input.addEventListener('keydown', function(e){ if(e.key==='Enter'){ e.preventDefault(); commitInput(); } });
        if (expand) {
            expand.addEventListener('mousedown', function(e){
                if (e.target !== input) e.preventDefault();
            });
        }

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

    function saveFullnameFromDisplay(fullName) {
        var parts = (fullName || '').trim().split(/\s+/);
        var firstName = parts.shift() || '';
        var lastName = parts.join(' ');
        var fd = new FormData();
        fd.append('action', 'ihq_update_fullname');
        fd.append('nonce',  _nonce);
        fd.append('firstName', firstName);
        fd.append('lastName',  lastName);
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
            var nameEl = document.querySelector('.sett-editable[data-field="name"]');
            if (nameEl && (d.firstName || d.lastName)) {
                nameEl.textContent = [d.firstName, d.lastName].filter(Boolean).join(' ');
            }
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
        document.querySelectorAll('.contact-row.is-selected').forEach(function(row){
            var inp = row.querySelector('input.sett-social-handle-input');
            var label = row.getAttribute('data-social-label') || '';
            if (inp && inp.value.trim() && label) {
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

    document.querySelectorAll('.sett-place-select').forEach(function(sel){
        var field = sel.getAttribute('data-field');
        var other = document.querySelector('.sett-place-other[data-place-field="' + field + '"]');
        function persistPlace(value) {
            save('save_settings_field', { group: 'account', field: field, value: value });
        }
        sel.addEventListener('change', function(){
            if (sel.value === '__other__') {
                if (other) {
                    other.hidden = false;
                    other.focus();
                }
                return;
            }
            if (other) {
                other.hidden = true;
                other.value = '';
            }
            persistPlace(sel.value);
        });
        if (other) {
            other.addEventListener('blur', function(){
                if (sel.value === '__other__') {
                    persistPlace(other.value.trim());
                }
            });
            other.addEventListener('keydown', function(e){
                if (e.key === 'Enter') {
                    e.preventDefault();
                    other.blur();
                }
            });
        }
    });

    document.querySelectorAll('[data-ihq-video-remove]').forEach(function(btn){
        btn.addEventListener('click', function(e){
            if (!window.confirm(<?php echo wp_json_encode( __( 'Remove this video from automatic promotion?', 'influencer-hq' ) ); ?>)) {
                e.preventDefault();
                return;
            }
            var form = btn.closest('form');
            if (!form) {
                return;
            }
            form.querySelectorAll('[required]').forEach(function(el){
                el.required = false;
            });
        });
    });

    function placeProfileCoachFab() {
        var fab = document.getElementById('ihq-concierge-fab');
        var host = document.getElementById('profile-coach-fab-host');
        if (!fab || !host || fab.parentNode === host) {
            return;
        }
        host.appendChild(fab);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', placeProfileCoachFab);
    } else {
        placeProfileCoachFab();
    }

})();

</script>

<?php
get_template_part( 'template-parts/portal-scripts' );
get_footer();
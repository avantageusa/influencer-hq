<?php
/** QC harness handoff. No tokens or SSO codes are persisted by this bridge. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ihq_harness_config() {
	$names = array( 'IHQ_HARNESS_STAGE', 'IHQ_HARNESS_ORIGIN', 'IHQ_HARNESS_GATEWAY_URL' );
	foreach ( $names as $name ) {
		if ( ! defined( $name ) || ! is_string( constant( $name ) ) ) { return false; }
	}
	if ( IHQ_HARNESS_STAGE !== 'qc' ) { return false; }
	$origin = wp_parse_url( IHQ_HARNESS_ORIGIN );
	$gateway = wp_parse_url( IHQ_HARNESS_GATEWAY_URL );
	if ( ! $origin || ! $gateway || $origin['scheme'] !== 'https' || $gateway['scheme'] !== 'https' ) { return false; }
	foreach ( array( 'user', 'pass', 'query', 'fragment' ) as $key ) {
		if ( isset( $origin[$key] ) || isset( $gateway[$key] ) ) { return false; }
	}
	if ( isset( $origin['path'] ) || empty( $origin['host'] ) || empty( $gateway['host'] ) || substr( IHQ_HARNESS_GATEWAY_URL, -3 ) !== '/qc' ) { return false; }
	return array( 'origin' => IHQ_HARNESS_ORIGIN, 'gateway' => IHQ_HARNESS_GATEWAY_URL );
}

function ihq_harness_read_request( $cookie, $now, $secret ) {
	if ( ! is_string( $cookie ) ) { return false; }
	$parts = explode( '.', $cookie );
	if ( count( $parts ) !== 3 || ! preg_match( '/^[a-f0-9-]{36}$/D', $parts[0] ) || ! ctype_digit( $parts[1] ) ) { return false; }
	$expiry = (int) $parts[1];
	if ( $expiry <= $now || $expiry > $now + 300 || ! hash_equals( hash_hmac( 'sha256', $parts[0] . '.' . $parts[1], $secret ), $parts[2] ) ) { return false; }
	return $parts[0];
}

function ihq_harness_cookie( $value, $expires ) {
	setcookie( 'ihq_harness_request', $value, array( 'expires' => $expires, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax' ) );
}

function ihq_harness_issue_code( $config, $user ) {
	// Reuse the existing start-session protocol and server-side API-key helper.
	// Do not reuse the profile helper: it persists raw token responses and accepts per-user URL overrides.
	$response = wp_remote_post( $config['gateway'] . '/account/oauth/start-session', array(
		'headers' => ihq_oauth_start_session_request_headers(),
		'body' => wp_json_encode( array( 'oauthLoginType' => 'InfluencerHq', 'payload' => array(
			'id' => 'wpu-' . $user->ID, 'firstName' => $user->first_name,
			'lastName' => $user->last_name, 'email' => $user->user_email,
			'countryIso' => ihq_normalize_country_iso_alpha2( get_user_meta( $user->ID, 'ihq_oauth_country_iso', true ) ),
		) ) ),
		'timeout' => 20, 'sslverify' => true, 'redirection' => 0,
	) );
	if ( is_wp_error( $response ) ) { return ''; }
	$status = wp_remote_retrieve_response_code( $response );
	if ( $status < 200 || $status >= 300 ) { return ''; }
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $body ) || ! isset( $body['success'] ) || $body['success'] !== true ) { return ''; }
	return ihq_extract_sso_code_from_start_session_data( isset( $body['data'] ) ? $body['data'] : null );
}

function ihq_harness_bridge() {
	$config = ihq_harness_config();
	$requested = isset( $_GET['ihq_harness_auth'] );
	if ( ! $config ) {
		if ( $requested ) { status_header( 404 ); exit; }
		return;
	}
	$pending = ihq_harness_read_request( isset( $_COOKIE['ihq_harness_request'] ) ? $_COOKIE['ihq_harness_request'] : '', time(), wp_salt( 'auth' ) );
	if ( ! $requested ) {
		if ( $pending && is_user_logged_in() ) {
			wp_safe_redirect( add_query_arg( array( 'ihq_harness_auth' => '1', 'state' => $pending ), home_url( '/' ) ) ); exit;
		}
		return;
	}
	nocache_headers();
	header( 'Referrer-Policy: no-referrer' );
	header( 'X-Frame-Options: DENY' );
	$state = isset( $_GET['state'] ) && is_string( $_GET['state'] ) ? $_GET['state'] : '';
	if ( ! preg_match( '/^[a-f0-9-]{36}$/D', $state ) ) { status_header( 400 ); exit; }
	if ( ! is_user_logged_in() ) {
		$pages = get_pages( array( 'meta_key' => '_wp_page_template', 'meta_value' => 'page-portal-login.php', 'number' => 1 ) );
		if ( empty( $pages ) ) { status_header( 503 ); exit; }
		$value = $state . '.' . ( time() + 300 );
		ihq_harness_cookie( $value . '.' . hash_hmac( 'sha256', $value, wp_salt( 'auth' ) ), time() + 300 );
		wp_safe_redirect( get_permalink( $pages[0] ) ); exit;
	}
	if ( $pending && ! hash_equals( $pending, $state ) ) { status_header( 400 ); exit; }
	ihq_harness_cookie( '', time() - 3600 );
	$code = ihq_harness_issue_code( $config, wp_get_current_user() );
	$message = array( 'type' => 'ihq-harness-session', 'state' => $state );
	if ( $code === '' ) { $message['error'] = 'Sign-in unavailable.'; } else { $message['code'] = $code; }
	$nonce = base64_encode( random_bytes( 18 ) );
	header( "Content-Security-Policy: default-src 'none'; script-src 'nonce-" . $nonce . "'; frame-ancestors 'none'; base-uri 'none'" );
	header( 'Content-Type: text/html; charset=utf-8' );
	echo '<!doctype html><title>IHQ sign-in</title><p>You can close this window.</p><script nonce="' . esc_attr( $nonce ) . '">if(window.opener){window.opener.postMessage(' . wp_json_encode( $message, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ',' . wp_json_encode( $config['origin'] ) . ');window.close();}</script>';
	exit;
}
add_action( 'template_redirect', 'ihq_harness_bridge', 0 );

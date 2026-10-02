<?php
/**
 * Influencer-to-influencer referral attribution (ENGR-6966, epic PO-3367).
 *
 * A visitor who lands on any IHQ page with `?ref=<code>` gets the first-party
 * cookie `ihq_ref` (set in JavaScript by js/ihq-ref-capture.js, last touch
 * wins). Every OAuth start-session sent from that visitor's requests carries
 * the code as `referrerCode` until one succeeds; the cookie is then cleared.
 *
 * The cookie is written in JavaScript, not with PHP setcookie() on page load:
 * PROD sits behind Cloudflare and WP Engine page caching, and cached landers
 * and AI Coach pages never run PHP. Only the clear runs server-side, and it
 * runs on uncached admin-ajax / REST / portal requests.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Cookie name, mirrored in js/ihq-ref-capture.js via the localized config. */
const IHQ_REF_COOKIE_NAME = 'ihq_ref';

/** Query parameter that carries the referrer's code. */
const IHQ_REF_QUERY_PARAM = 'ref';

/** Longest referrer code we store or send; anything longer is truncated. */
const IHQ_REF_MAX_LENGTH = 64;

/** Script handle for the capture script. */
const IHQ_REF_CAPTURE_SCRIPT_HANDLE = 'ihq-ref-capture';

/**
 * Sanitise a raw referrer code: sanitize_text_field() then a 64-character cap.
 * Non-strings become ''.
 *
 * @param mixed $raw Raw value (cookie, query string, stored record).
 * @return string
 */
function ihq_ref_sanitize_code( $raw ) {
	if ( ! is_string( $raw ) ) {
		return '';
	}

	$clean = sanitize_text_field( wp_unslash( $raw ) );
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $clean, 0, IHQ_REF_MAX_LENGTH );
	}
	return substr( $clean, 0, IHQ_REF_MAX_LENGTH );
}

/**
 * The sanitised referrer code from this request's `ihq_ref` cookie, or ''.
 *
 * @return string
 */
function ihq_ref_cookie_code() {
	if ( ! isset( $_COOKIE[ IHQ_REF_COOKIE_NAME ] ) ) {
		return '';
	}
	return ihq_ref_sanitize_code( $_COOKIE[ IHQ_REF_COOKIE_NAME ] );
}

/**
 * The referrer code to send on start-session. An explicit code (e.g. the one
 * stored in a pending email registration, opened in another browser) wins
 * over the cookie; with neither, ''.
 *
 * @param mixed $explicit_code Explicit code from the caller, or ''.
 * @return string
 */
function ihq_ref_resolve_referrer_code( $explicit_code = '' ) {
	$explicit = ihq_ref_sanitize_code( $explicit_code );
	if ( $explicit !== '' ) {
		return $explicit;
	}
	return ihq_ref_cookie_code();
}

/**
 * Options for the Set-Cookie that expires `ihq_ref`. Path and the absence of
 * a domain match what js/ihq-ref-capture.js writes, so the browser treats it
 * as the same cookie.
 *
 * @param bool $secure Whether the request is over https.
 * @return array<string, mixed>
 */
function ihq_ref_expired_cookie_options( $secure ) {
	return array(
		'expires'  => 1,
		'path'     => '/',
		'secure'   => (bool) $secure,
		'httponly' => false,
		'samesite' => 'Lax',
	);
}

/**
 * Send the Set-Cookie header that expires `ihq_ref` in the browser.
 *
 * @param string $name Cookie name.
 * @return void
 */
function ihq_ref_send_expired_cookie( $name ) {
	if ( headers_sent() ) {
		error_log( '[ihq-ref] could not expire the ' . $name . ' cookie: headers already sent' );
		return;
	}
	setcookie( $name, '', ihq_ref_expired_cookie_options( is_ssl() ) );
}

/**
 * Clear the referrer cookie after a successful start-session: expire it in
 * the browser and drop it from $_COOKIE. The unset matters because AI Coach
 * calls start-session twice in one request (create, then sign-in), and the
 * second call must not resend the code. No-op when the request has no cookie.
 *
 * @param callable|null $send_expired_cookie Header writer; tests inject a recorder.
 * @return void
 */
function ihq_ref_clear_cookie( $send_expired_cookie = null ) {
	if ( ! isset( $_COOKIE[ IHQ_REF_COOKIE_NAME ] ) ) {
		return;
	}

	unset( $_COOKIE[ IHQ_REF_COOKIE_NAME ] );

	$writer = is_callable( $send_expired_cookie ) ? $send_expired_cookie : 'ihq_ref_send_expired_cookie';
	call_user_func( $writer, IHQ_REF_COOKIE_NAME );
}

/**
 * Config handed to js/ihq-ref-capture.js.
 *
 * @return array<string, mixed>
 */
function ihq_ref_capture_script_config() {
	return array(
		'cookieName' => IHQ_REF_COOKIE_NAME,
		'queryParam' => IHQ_REF_QUERY_PARAM,
		'cookieDays' => ihq_env_ref_cookie_ttl_days(),
		'maxLength'  => IHQ_REF_MAX_LENGTH,
	);
}

/**
 * Enqueue the capture script on every front-end page, so `?ref` on any IHQ
 * URL is captured, not only on the lander or portal.
 *
 * @return void
 */
function ihq_ref_enqueue_capture_script() {
	$script_path = get_template_directory() . '/js/ihq-ref-capture.js';
	$version     = file_exists( $script_path ) ? (string) filemtime( $script_path ) : '1';

	wp_enqueue_script(
		IHQ_REF_CAPTURE_SCRIPT_HANDLE,
		get_template_directory_uri() . '/js/ihq-ref-capture.js',
		array(),
		$version,
		false
	);

	wp_localize_script(
		IHQ_REF_CAPTURE_SCRIPT_HANDLE,
		'IHQ_REF_CAPTURE',
		ihq_ref_capture_script_config()
	);
}
add_action( 'wp_enqueue_scripts', 'ihq_ref_enqueue_capture_script' );

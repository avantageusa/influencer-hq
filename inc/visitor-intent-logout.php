<?php
/**
 * Reset the guest login state on logout (ENGR-7260).
 *
 * js/ihq-visitor-intent.js keeps the visitor's chosen communication methods in
 * the 30-day `ihq_visitor_intent` cookie and remembers a sent code in
 * `ihq_visitor_code_issued`. Both outlive a WordPress logout, so the header
 * Login gate (js/ihq-registry-gates.js) sees comm methods on file and opens the
 * 6-digit code prompt straight away instead of asking for an account and a
 * channel. Expiring both cookies when the user logs out sends the next Login
 * back to the start of the flow. Mid-login (guest, cookie set, no logout) is
 * untouched.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Cookie names mirrored in js/ihq-visitor-intent.js (COOKIE_NAME, CODE_ISSUED_COOKIE). */
const IHQ_VISITOR_LOGOUT_COOKIES = array( 'ihq_visitor_intent', 'ihq_visitor_code_issued' );

/**
 * Options for the Set-Cookie that expires a visitor-intent cookie. Path `/`
 * and no domain match what js/ihq-visitor-intent.js writes, so the browser
 * treats it as the same cookie.
 *
 * @param bool $secure Whether the request is over https.
 * @return array<string, mixed>
 */
function ihq_visitor_logout_expired_cookie_options( $secure ) {
	return array(
		'expires'  => 1,
		'path'     => '/',
		'secure'   => (bool) $secure,
		'httponly' => false,
		'samesite' => 'Lax',
	);
}

/**
 * Send the Set-Cookie header that expires one cookie in the browser.
 *
 * @param string $name Cookie name.
 * @return void
 */
function ihq_visitor_logout_send_expired_cookie( $name ) {
	if ( headers_sent() ) {
		error_log( '[ihq-visitor-intent] could not expire the ' . $name . ' cookie on logout: headers already sent' );
		return;
	}
	setcookie( $name, '', ihq_visitor_logout_expired_cookie_options( is_ssl() ) );
}

/**
 * Expire the visitor-intent cookies and drop them from $_COOKIE. Runs on
 * `wp_logout`, which every logout link reaches through wp_logout_url().
 * Cookies the request does not carry are skipped.
 *
 * @param callable|null $send_expired_cookie Header writer; tests inject a recorder.
 * @return string[] Names of the cookies that were expired.
 */
function ihq_visitor_logout_clear_cookies( $send_expired_cookie = null ) {
	$writer  = is_callable( $send_expired_cookie ) ? $send_expired_cookie : 'ihq_visitor_logout_send_expired_cookie';
	$cleared = array();

	foreach ( IHQ_VISITOR_LOGOUT_COOKIES as $name ) {
		if ( ! isset( $_COOKIE[ $name ] ) ) {
			continue;
		}
		unset( $_COOKIE[ $name ] );
		call_user_func( $writer, $name );
		$cleared[] = $name;
	}

	return $cleared;
}

/**
 * `wp_logout` passes the user id; the reset does not need it.
 *
 * @return void
 */
function ihq_visitor_logout_on_wp_logout() {
	ihq_visitor_logout_clear_cookies();
}
add_action( 'wp_logout', 'ihq_visitor_logout_on_wp_logout' );

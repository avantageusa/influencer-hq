<?php
/**
 * Influencer auth cookie lifetime (24 hours).
 *
 * WordPress auth uses cookies, not PHP sessions. This filter caps cookie TTL when
 * cookies are issued via wp_set_auth_cookie() / wp_signon() for influencer users.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Auth cookie lifetime for influencer role (seconds). */
const IHQ_INFLUENCER_AUTH_COOKIE_SECONDS = DAY_IN_SECONDS;

/**
 * Limit influencer login cookies to 24 hours.
 *
 * @param int  $expiration Seconds until cookie expires.
 * @param int  $user_id    User ID.
 * @param bool $remember   Whether remember-me was requested.
 * @return int
 */
function ihq_filter_influencer_auth_cookie_expiration( $expiration, $user_id, $remember ) {
	unset( $remember );

	$user = get_userdata( $user_id );
	if ( ! $user || ! function_exists( 'ihq_user_has_influencer_role' ) ) {
		return $expiration;
	}

	if ( ! ihq_user_has_influencer_role( $user ) ) {
		return $expiration;
	}

	return IHQ_INFLUENCER_AUTH_COOKIE_SECONDS;
}

add_filter( 'auth_cookie_expiration', 'ihq_filter_influencer_auth_cookie_expiration', 10, 3 );

<?php
/**
 * Platform ID token freshness for influencerhq-api calls (ENGR-7017).
 *
 * `ihq_id_token` is the Cognito ID token from account-api start-session. It
 * lasts about an hour (`ihq_token_expires`), but a WordPress session lasts
 * days, so most portal visits would otherwise send an expired token and get a
 * 401 from the API Gateway authorizer.
 *
 * Every handler that calls influencerhq-api with the token:
 *
 *     $session  = ihq_platform_session_begin( $wp_user_id );   // refreshes first if expired
 *     $id_token = $session['id_token'];
 *     ...
 *     $response = ihq_platform_send_with_401_retry( $session, function ( $token ) { ... } );
 *
 * The refresh re-runs start-session through ihq_refresh_influencer_oauth_tokens()
 * (inc/email-verification-handler.php), the same call login and the share-link
 * retry already make. A request refreshes at most once; a failed refresh keeps
 * the stored token, so the handler returns the error it returns today.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Refresh this many seconds before `ihq_token_expires`, so the token can't expire in flight. */
const IHQ_PLATFORM_ID_TOKEN_EXPIRY_MARGIN_SECONDS = 60;

/** The only status that triggers a refresh-and-retry: the authorizer's answer to an expired token. */
const IHQ_PLATFORM_HTTP_UNAUTHORIZED = 401;

/**
 * Whether a stored expiry has passed (or is within the margin).
 *
 * A missing or non-numeric expiry is not treated as expired: there is nothing
 * to compare against, and the 401 retry still covers that user.
 *
 * @param mixed $expires_at `ihq_token_expires` meta (Unix seconds).
 * @param int   $now        Current Unix time.
 * @return bool
 */
function ihq_platform_id_token_is_expired( $expires_at, $now ) {
	if ( ! is_numeric( $expires_at ) ) {
		return false;
	}
	return (int) $expires_at - IHQ_PLATFORM_ID_TOKEN_EXPIRY_MARGIN_SECONDS <= (int) $now;
}

/**
 * Re-run start-session for the user with their stored country and return the new ID token.
 *
 * @param int $wp_user_id WordPress user ID.
 * @return string New ID token, or '' when the refresh failed.
 */
function ihq_refresh_platform_id_token( $wp_user_id ) {
	$wp_user_id = (int) $wp_user_id;
	if ( ! function_exists( 'ihq_refresh_influencer_oauth_tokens' ) ) {
		return '';
	}
	$country   = get_user_meta( $wp_user_id, 'ihq_oauth_country_iso', true );
	$refreshed = ihq_refresh_influencer_oauth_tokens( $wp_user_id, is_string( $country ) ? $country : '' );
	if ( true !== $refreshed ) {
		return '';
	}
	$id_token = get_user_meta( $wp_user_id, 'ihq_id_token', true );
	return is_string( $id_token ) ? $id_token : '';
}

/**
 * Start a request: return the user's ID token, refreshed first when it has expired.
 *
 * @param int      $wp_user_id WordPress user ID.
 * @param int|null $now        Current Unix time; defaults to time().
 * @return array{user_id: int, id_token: string, refreshed: bool}
 *         `refreshed` is true once a refresh has been attempted this request,
 *         whether or not it succeeded, so the 401 retry does not try again.
 */
function ihq_platform_session_begin( $wp_user_id, $now = null ) {
	$wp_user_id = (int) $wp_user_id;
	$now        = null === $now ? time() : (int) $now;
	$stored     = get_user_meta( $wp_user_id, 'ihq_id_token', true );
	$session    = array(
		'user_id'   => $wp_user_id,
		'id_token'  => is_string( $stored ) ? $stored : '',
		'refreshed' => false,
	);

	// No token means the user never completed SSO; the handler reports that as today.
	if ( $wp_user_id <= 0 || '' === $session['id_token'] ) {
		return $session;
	}
	if ( ! ihq_platform_id_token_is_expired( get_user_meta( $wp_user_id, 'ihq_token_expires', true ), $now ) ) {
		return $session;
	}

	$session['refreshed'] = true;
	$fresh                = ihq_refresh_platform_id_token( $wp_user_id );
	if ( '' !== $fresh ) {
		$session['id_token'] = $fresh;
	}
	return $session;
}

/**
 * Send a request with the session's token; on a 401, refresh once and resend once.
 *
 * No retry when this request already attempted a refresh (the token was
 * expired at the start), when the refresh fails, or for any status but 401:
 * the first response is returned and the handler reports it as today.
 *
 * @param array    $session From ihq_platform_session_begin().
 * @param callable $send    function ( string $id_token ) returning a wp_remote_* response or WP_Error.
 * @return array|WP_Error The response to report.
 */
function ihq_platform_send_with_401_retry( array $session, callable $send ) {
	$response = $send( $session['id_token'] );
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	if ( IHQ_PLATFORM_HTTP_UNAUTHORIZED !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return $response;
	}
	if ( ! empty( $session['refreshed'] ) ) {
		return $response;
	}

	$fresh = ihq_refresh_platform_id_token( $session['user_id'] );
	if ( '' === $fresh ) {
		return $response;
	}
	return $send( $fresh );
}

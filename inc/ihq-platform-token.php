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
 * the stored token, so the handler returns the error it returns today. Per user,
 * one request refreshes at a time (lock), and after a failure none does for a
 * minute (backoff). A request that finds the lock taken waits for that refresh
 * and uses its token: the profile page loads the share link and the player at
 * once, and the one that lost the lock used to send the expired token.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Refresh this many seconds before `ihq_token_expires`, so the token can't expire in flight. */
const IHQ_PLATFORM_ID_TOKEN_EXPIRY_MARGIN_SECONDS = 60;

/** The only status that triggers a refresh-and-retry: the authorizer's answer to an expired token. */
const IHQ_PLATFORM_HTTP_UNAUTHORIZED = 401;

/**
 * A refresh lock older than this belongs to a request that died mid-refresh; the next request takes it over.
 * start-session times out after 30 s, so a live refresh never holds the lock longer.
 */
const IHQ_PLATFORM_REFRESH_LOCK_TTL_SECONDS = 30;

/** After a failed refresh, skip refreshing for this long, so a start-session outage isn't hit by every request. */
const IHQ_PLATFORM_REFRESH_BACKOFF_SECONDS = 60;

/**
 * How long a request waits for another request's refresh: 40 polls of 250 ms, 10 s in all. start-session
 * normally takes about a second; a refresh still running after 10 s is treated as failed for this request.
 */
const IHQ_PLATFORM_REFRESH_WAIT_POLLS  = 40;
const IHQ_PLATFORM_REFRESH_WAIT_POLL_MS = 250;

const IHQ_PLATFORM_REFRESH_LOCK_OPTION_PREFIX = 'ihq_platform_refresh_lock_';
const IHQ_PLATFORM_REFRESH_BACKOFF_PREFIX     = 'ihq_platform_refresh_backoff_';

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
 * Take the user's refresh lock, so concurrent requests (the profile page loads several at once) run one
 * start-session between them instead of one each.
 *
 * INSERT IGNORE on wp_options is atomic through its unique option_name key; add_option() is not (it
 * checks a cache, then upserts). A lock older than the TTL is taken over.
 *
 * @param int $wp_user_id WordPress user ID.
 * @param int $now        Current Unix time; stored as the lock value.
 * @return bool True when this request now holds the lock.
 */
function ihq_platform_refresh_lock_acquire( $wp_user_id, $now ) {
	global $wpdb;
	$name   = IHQ_PLATFORM_REFRESH_LOCK_OPTION_PREFIX . (int) $wp_user_id;
	$insert = "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')";
	if ( 1 === (int) $wpdb->query( $wpdb->prepare( $insert, $name, (string) $now ) ) ) {
		return true;
	}
	$stale_before = (int) $now - IHQ_PLATFORM_REFRESH_LOCK_TTL_SECONDS;
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value < %d", $name, $stale_before ) );
	return 1 === (int) $wpdb->query( $wpdb->prepare( $insert, $name, (string) $now ) );
}

/**
 * Release the user's refresh lock, but only the one this request took (matched by its timestamp), never a
 * lock another request has taken over since.
 *
 * @param int $wp_user_id WordPress user ID.
 * @param int $locked_at  The value ihq_platform_refresh_lock_acquire() stored.
 */
function ihq_platform_refresh_lock_release( $wp_user_id, $locked_at ) {
	global $wpdb;
	$name = IHQ_PLATFORM_REFRESH_LOCK_OPTION_PREFIX . (int) $wp_user_id;
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $name, (string) $locked_at ) );
}

/**
 * Whether any request holds the user's refresh lock. Read from the table, not the options cache, so a lock
 * another request released is seen as gone.
 *
 * @param int $wp_user_id WordPress user ID.
 * @return bool
 */
function ihq_platform_refresh_lock_is_held( $wp_user_id ) {
	global $wpdb;
	$name = IHQ_PLATFORM_REFRESH_LOCK_OPTION_PREFIX . (int) $wp_user_id;
	return null !== $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name ) );
}

if ( ! function_exists( 'ihq_platform_refresh_pause' ) ) {
	/**
	 * Sleep between lock polls. Defined only when missing, so the tests can poll without sleeping.
	 *
	 * @param int $milliseconds How long to sleep.
	 */
	function ihq_platform_refresh_pause( $milliseconds ) {
		usleep( (int) $milliseconds * 1000 );
	}
}

/**
 * Wait until the request that holds the refresh lock releases it (at most IHQ_PLATFORM_REFRESH_WAIT_POLLS
 * polls), then return the token it stored, or '' when its refresh failed or is still running.
 *
 * @param int $wp_user_id WordPress user ID.
 * @param int $now        Current Unix time.
 * @return string
 */
function ihq_platform_wait_for_other_refresh( $wp_user_id, $now ) {
	for ( $poll = 0; $poll < IHQ_PLATFORM_REFRESH_WAIT_POLLS; $poll++ ) {
		if ( ! ihq_platform_refresh_lock_is_held( $wp_user_id ) ) {
			break;
		}
		ihq_platform_refresh_pause( IHQ_PLATFORM_REFRESH_WAIT_POLL_MS );
	}
	// The other request stored its token in its own process; this request's user-meta cache still holds the
	// token read when it started, so drop it before reading.
	wp_cache_delete( (int) $wp_user_id, 'user_meta' );
	return ihq_platform_valid_stored_token( $wp_user_id, $now );
}

/**
 * The stored ID token when it is still valid, else ''.
 *
 * @param int $wp_user_id WordPress user ID.
 * @param int $now        Current Unix time.
 * @return string
 */
function ihq_platform_valid_stored_token( $wp_user_id, $now ) {
	$id_token = get_user_meta( $wp_user_id, 'ihq_id_token', true );
	if ( ! is_string( $id_token ) ) {
		return '';
	}
	if ( ihq_platform_id_token_is_expired( get_user_meta( $wp_user_id, 'ihq_token_expires', true ), $now ) ) {
		return '';
	}
	return $id_token;
}

/**
 * Re-run start-session for the user with their stored country and return the new ID token.
 *
 * At most one request per user refreshes at a time (lock); a request that finds the lock taken waits for that
 * refresh instead. After a failure no request refreshes for IHQ_PLATFORM_REFRESH_BACKOFF_SECONDS (backoff).
 *
 * @param int      $wp_user_id WordPress user ID.
 * @param int|null $now        Current Unix time; defaults to time().
 * @return string New ID token, or '' when no refresh happened or it failed.
 */
function ihq_refresh_platform_id_token( $wp_user_id, $now = null ) {
	$wp_user_id = (int) $wp_user_id;
	$now        = null === $now ? time() : (int) $now;
	if ( ! function_exists( 'ihq_refresh_influencer_oauth_tokens' ) ) {
		return '';
	}
	$backoff_key = IHQ_PLATFORM_REFRESH_BACKOFF_PREFIX . $wp_user_id;
	if ( false !== get_transient( $backoff_key ) ) {
		return '';
	}
	if ( ! ihq_platform_refresh_lock_acquire( $wp_user_id, $now ) ) {
		return ihq_platform_wait_for_other_refresh( $wp_user_id, $now );
	}

	try {
		$country   = get_user_meta( $wp_user_id, 'ihq_oauth_country_iso', true );
		$refreshed = ihq_refresh_influencer_oauth_tokens( $wp_user_id, is_string( $country ) ? $country : '' );
	} finally {
		ihq_platform_refresh_lock_release( $wp_user_id, $now );
	}
	if ( true !== $refreshed ) {
		set_transient( $backoff_key, $now, IHQ_PLATFORM_REFRESH_BACKOFF_SECONDS );
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
	$fresh                = ihq_refresh_platform_id_token( $wp_user_id, $now );
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
	// The same token back means another request holds the lock and nothing new exists yet: resending would 401 again.
	if ( '' === $fresh || $fresh === $session['id_token'] ) {
		return $response;
	}
	return $send( $fresh );
}

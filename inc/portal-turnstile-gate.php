<?php
/**
 * Guest portal route gate: Cloudflare Turnstile + signed pass cookie for all
 * /portal/* pages (except login, verify, and test API). One pass covers every
 * gated portal route for 1 hour.
 *
 * @package Avantage_Baccarat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Cookie TTL after successful siteverify (seconds). */
const IHQ_PORTAL_TURNSTILE_PASS_TTL = 3600;

/** Signed URL bypass TTL after successful verify (seconds). */
const IHQ_PORTAL_TURNSTILE_PTS_TTL = 120;

/** Query arg carrying a one-time signed pass after verify. */
const IHQ_PORTAL_TURNSTILE_PTS_ARG = 'ihq_pts';

/** Virtual path for Turnstile verify screen (not a gated portal page). */
const IHQ_PORTAL_TURNSTILE_VERIFY_SLUG = 'portal/verify';

/**
 * Absolute path for the dedicated Turnstile gate debug log.
 *
 * @return string
 */
function ihq_portal_turnstile_log_file_path() {
	if ( defined( 'IHQ_PORTAL_TURNSTILE_LOG_FILE' ) && is_string( IHQ_PORTAL_TURNSTILE_LOG_FILE ) && IHQ_PORTAL_TURNSTILE_LOG_FILE !== '' ) {
		return IHQ_PORTAL_TURNSTILE_LOG_FILE;
	}

	return WP_CONTENT_DIR . '/ihq-portal-turnstile-gate.log';
}

/**
 * Whether Turnstile gate debug logging is enabled.
 *
 * @return bool
 */
function ihq_portal_turnstile_log_enabled() {
	if ( defined( 'IHQ_PORTAL_TURNSTILE_LOG' ) ) {
		return (bool) IHQ_PORTAL_TURNSTILE_LOG;
	}

	return true;
}

/**
 * Snapshot of the current request for log context (no secrets).
 *
 * @return array<string, mixed>
 */
function ihq_portal_turnstile_request_snapshot() {
	$home_host = (string) parse_url( home_url( '/' ), PHP_URL_HOST );
	$cookie_name = ihq_portal_turnstile_cookie_name();

	return array(
		'method'           => isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '',
		'request_uri'      => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
		'http_host'        => ihq_portal_turnstile_request_host(),
		'home_host'        => strtolower( $home_host ),
		'normalized_path'  => ihq_portal_turnstile_normalized_path(),
		'is_verify_page'   => ihq_request_is_portal_turnstile_verify_page(),
		'is_portal_route'  => ihq_request_is_portal_route(),
		'is_logged_in'     => is_user_logged_in(),
		'redirect_to_get'  => isset( $_GET['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) ) : '',
		'ts_error_get'     => isset( $_GET['ts_error'] ) ? sanitize_text_field( wp_unslash( $_GET['ts_error'] ) ) : '',
		'pts_present'      => isset( $_GET[ IHQ_PORTAL_TURNSTILE_PTS_ARG ] ),
		'cookie_present'   => isset( $_COOKIE[ $cookie_name ] ),
		'referer'          => isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
		'remote_addr'      => isset( $_SERVER['HTTP_CF_CONNECTING_IP'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) )
			: ( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' ),
	);
}

/**
 * Read-only pass evaluation with reasons (no cookie side effects).
 *
 * @return array<string, mixed>
 */
function ihq_portal_turnstile_pass_status() {
	$name = ihq_portal_turnstile_cookie_name();
	$status = array(
		'valid'            => false,
		'via'              => 'none',
		'cookie_present'   => ! empty( $_COOKIE[ $name ] ) && is_string( $_COOKIE[ $name ] ),
		'cookie_valid'     => false,
		'cookie_expired'   => false,
		'cookie_bad_sig'   => false,
		'pts_present'      => isset( $_GET[ IHQ_PORTAL_TURNSTILE_PTS_ARG ] ),
		'pts_valid'          => false,
		'pts_expired'      => false,
		'pts_bad_sig'      => false,
		'client_pass_valid'=> false,
	);

	if ( $status['cookie_present'] ) {
		$parts = explode( '.', sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) ), 2 );
		if ( count( $parts ) === 2 ) {
			$expires = (int) $parts[0];
			$sig     = $parts[1];
			if ( $expires < time() ) {
				$status['cookie_expired'] = true;
			} elseif ( ! hash_equals( ihq_portal_turnstile_sign_expiry( $expires ), $sig ) ) {
				$status['cookie_bad_sig'] = true;
			} else {
				$status['cookie_valid'] = true;
				$status['valid']        = true;
				$status['via']          = 'cookie';
			}
		}
	}

	if ( ! $status['valid'] && $status['pts_present'] ) {
		$pts = sanitize_text_field( wp_unslash( $_GET[ IHQ_PORTAL_TURNSTILE_PTS_ARG ] ) );
		$parts = explode( '.', $pts, 3 );
		if ( count( $parts ) === 3 ) {
			$expires = (int) $parts[0];
			$sig     = $parts[2];
			$payload = $expires . '.' . $parts[1];
			$expected = hash_hmac( 'sha256', $payload, wp_salt( 'ihq_portal_ts_pts' ) );
			if ( $expires < time() ) {
				$status['pts_expired'] = true;
			} elseif ( ! hash_equals( $expected, $sig ) ) {
				$status['pts_bad_sig'] = true;
			} else {
				$status['pts_valid'] = true;
				$status['valid']     = true;
				$status['via']       = 'pts';
			}
		}
	}

	if ( ! $status['valid'] && ihq_portal_turnstile_client_pass_is_valid() ) {
		$status['client_pass_valid'] = true;
		$status['valid']             = true;
		$status['via']               = 'client_store';
	}

	return $status;
}

/**
 * Append one line to the dedicated Turnstile gate log file.
 *
 * @param string               $event   Short event name.
 * @param array<string, mixed> $context Extra fields (merged with request snapshot).
 */
function ihq_portal_turnstile_log( $event, $context = array() ) {
	if ( ! ihq_portal_turnstile_log_enabled() ) {
		return;
	}

	$line = array(
		'ts'    => gmdate( 'c' ),
		'event' => (string) $event,
	);

	$line = array_merge( $line, ihq_portal_turnstile_request_snapshot(), $context );

	$encoded = wp_json_encode( $line, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( ! is_string( $encoded ) ) {
		return;
	}

	$path = ihq_portal_turnstile_log_file_path();
	$written = @file_put_contents( $path, $encoded . PHP_EOL, FILE_APPEND | LOCK_EX );

	if ( false === $written ) {
		error_log( 'IHQ portal Turnstile gate log write failed: ' . $path . ' event=' . $event );
	}
}

/**
 * Cookie name for portal human-verification pass.
 *
 * @return string
 */
function ihq_portal_turnstile_cookie_name() {
	return 'ihq_portal_turnstile_pass';
}

/**
 * Stable per-visitor key for server-side pass (fallback when cookies do not persist).
 *
 * @return string
 */
function ihq_portal_turnstile_client_fingerprint() {
	$raw_ip = '';
	if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
		$raw_ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$raw_ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}

	$ip_parts = explode( ',', $raw_ip );
	$ip       = trim( $ip_parts[0] );

	$ua = isset( $_SERVER['HTTP_USER_AGENT'] )
		? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
		: '';

	return $ip . '|' . $ua;
}

/**
 * @return string
 */
function ihq_portal_turnstile_client_pass_transient_key() {
	return 'ihq_ptc_' . hash( 'sha256', ihq_portal_turnstile_client_fingerprint() . wp_salt( 'ihq_portal_ts_client' ) );
}

/**
 * Store pass server-side for this visitor (survives missing/blocked cookies).
 *
 * @param string $source Log label.
 */
function ihq_portal_turnstile_store_client_pass( $source = 'unknown' ) {
	$expires = time() + IHQ_PORTAL_TURNSTILE_PASS_TTL;
	set_transient(
		ihq_portal_turnstile_client_pass_transient_key(),
		(string) $expires,
		IHQ_PORTAL_TURNSTILE_PASS_TTL
	);

	ihq_portal_turnstile_log(
		'client_pass_stored',
		array(
			'source'  => $source,
			'expires' => $expires,
		)
	);
}

/**
 * @return bool
 */
function ihq_portal_turnstile_client_pass_is_valid() {
	$stored = get_transient( ihq_portal_turnstile_client_pass_transient_key() );
	if ( $stored === false ) {
		return false;
	}

	return (int) $stored >= time();
}

/**
 * Persist pass in cookie (best effort) and server-side store (reliable fallback).
 *
 * @param string $source Log label.
 */
function ihq_portal_turnstile_persist_pass( $source = 'siteverify' ) {
	ihq_portal_turnstile_set_pass_cookie( $source );
	ihq_portal_turnstile_store_client_pass( $source );
}

/**
 * Portal templates that skip the gate (own Turnstile or dev tools).
 *
 * @return string[]
 */
function ihq_portal_turnstile_exempt_templates() {
	return array(
		'page-portal-login.php',
		'page-portal-testapi.php',
	);
}

/**
 * Request path relative to site home (no leading slash), lowercase.
 *
 * @return string
 */
function ihq_portal_turnstile_normalized_path() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = strtolower( untrailingslashit( (string) parse_url( $uri, PHP_URL_PATH ) ) );

	$site_path = (string) parse_url( home_url( '/' ), PHP_URL_PATH );
	$site_path = strtolower( untrailingslashit( $site_path ) );
	if ( $site_path !== '' && strpos( $path, $site_path ) === 0 ) {
		$path = substr( $path, strlen( $site_path ) );
	}

	return ltrim( $path, '/' );
}

/**
 * @return bool
 */
function ihq_request_is_portal_turnstile_verify_page() {
	$path = ihq_portal_turnstile_normalized_path();
	return $path === IHQ_PORTAL_TURNSTILE_VERIFY_SLUG
		|| strpos( $path, IHQ_PORTAL_TURNSTILE_VERIFY_SLUG . '/' ) === 0;
}

/**
 * Whether the current front-end request targets a portal route.
 *
 * @return bool
 */
function ihq_request_is_portal_route() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return false;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}

	if ( ihq_request_is_portal_turnstile_verify_page() ) {
		return false;
	}

	$page_id = get_queried_object_id();
	if ( $page_id > 0 ) {
		$template = get_page_template_slug( $page_id );
		if ( is_string( $template ) && strpos( $template, 'page-portal-' ) === 0 ) {
			return ! in_array( $template, ihq_portal_turnstile_exempt_templates(), true );
		}
	}

	$path = ihq_portal_turnstile_normalized_path();

	return ihq_portal_turnstile_path_is_gated_portal( $path );
}

/**
 * Request scheme (respects X-Forwarded-Proto behind proxies).
 *
 * @return string http|https
 */
function ihq_portal_turnstile_request_scheme() {
	$scheme = is_ssl() ? 'https' : 'http';
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
		$proto = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) );
		if ( $proto === 'https' ) {
			$scheme = 'https';
		}
	}
	return $scheme;
}

/**
 * Host the visitor is actually using (www vs apex must stay consistent for cookies).
 *
 * @return string
 */
function ihq_portal_turnstile_request_host() {
	if ( empty( $_SERVER['HTTP_HOST'] ) ) {
		return '';
	}
	return strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) );
}

/**
 * Build a URL on the same host the visitor used (avoids www/apex redirect loops).
 *
 * @param string $path Site-relative path, e.g. /portal/verify/.
 * @return string
 */
function ihq_portal_turnstile_request_url( $path ) {
	$path = '/' . ltrim( (string) $path, '/' );
	$host = ihq_portal_turnstile_request_host();
	if ( $host === '' ) {
		return esc_url_raw( home_url( $path ) );
	}

	$home_path = (string) parse_url( home_url( '/' ), PHP_URL_PATH );
	$home_path = untrailingslashit( $home_path );
	if ( $home_path !== '' && strpos( $path, $home_path ) !== 0 ) {
		$path = $home_path . $path;
	}

	return esc_url_raw( ihq_portal_turnstile_request_scheme() . '://' . $host . $path );
}

/**
 * HMAC for signed cookie value.
 *
 * @param int $expires Unix timestamp.
 * @return string
 */
function ihq_portal_turnstile_sign_expiry( $expires ) {
	return hash_hmac( 'sha256', (string) (int) $expires, wp_salt( 'ihq_portal_turnstile' ) );
}

/**
 * Create a short-lived signed bypass token (no server storage; survives load balancers).
 *
 * @return string
 */
function ihq_portal_turnstile_create_pts_token() {
	$expires = time() + IHQ_PORTAL_TURNSTILE_PTS_TTL;
	$nonce   = wp_generate_password( 16, false, false );
	$payload = $expires . '.' . $nonce;
	$sig     = hash_hmac( 'sha256', $payload, wp_salt( 'ihq_portal_ts_pts' ) );
	return $payload . '.' . $sig;
}

/**
 * @param string $token Value from ihq_pts query arg.
 * @return bool
 */
function ihq_portal_turnstile_verify_pts_token( $token ) {
	$token = sanitize_text_field( (string) $token );
	$parts = explode( '.', $token, 3 );
	if ( count( $parts ) !== 3 ) {
		return false;
	}

	$expires = (int) $parts[0];
	$nonce   = $parts[1];
	$sig     = $parts[2];

	if ( $expires < time() || $nonce === '' || $sig === '' ) {
		return false;
	}

	$payload  = $expires . '.' . $nonce;
	$expected = hash_hmac( 'sha256', $payload, wp_salt( 'ihq_portal_ts_pts' ) );

	return hash_equals( $expected, $sig );
}

/**
 * @return bool
 */
function ihq_portal_turnstile_pass_is_valid() {
	$status = ihq_portal_turnstile_pass_status();

	if ( $status['via'] === 'pts' ) {
		ihq_portal_turnstile_persist_pass( 'pts' );
	}

	return $status['valid'];
}

/**
 * Set signed HttpOnly pass cookie (same request + subsequent requests).
 *
 * @param string $source Why the cookie is being set (for logs).
 */
function ihq_portal_turnstile_set_pass_cookie( $source = 'siteverify' ) {
	if ( ! headers_sent() ) {
		nocache_headers();
	}

	$expires = time() + IHQ_PORTAL_TURNSTILE_PASS_TTL;
	$value   = $expires . '.' . ihq_portal_turnstile_sign_expiry( $expires );
	$name    = ihq_portal_turnstile_cookie_name();

	$secure = is_ssl();
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) ) ) {
		$secure = true;
	}

	// Host-only cookie on path / — avoids COOKIE_DOMAIN www/apex mismatches.
	$path   = '/';
	$domain = '';

	if ( PHP_VERSION_ID >= 70300 ) {
		$cookie_opts = array(
			'expires'  => $expires,
			'path'     => $path,
			'secure'   => $secure,
			'httponly' => true,
			'samesite' => 'Lax',
		);
		setcookie( $name, $value, $cookie_opts );
	} else {
		setcookie( $name, $value, $expires, $path, $domain, $secure, true );
	}

	// Duplicate Set-Cookie via header() — some CDN/proxy stacks drop PHP setcookie() only.
	if ( ! headers_sent() && $secure ) {
		header(
			'Set-Cookie: ' . $name . '=' . $value
			. '; Expires=' . gmdate( 'D, d M Y H:i:s', $expires ) . ' GMT'
			. '; Path=/; Secure; HttpOnly; SameSite=Lax',
			false
		);
	}

	$_COOKIE[ $name ] = $value;

	ihq_portal_turnstile_log(
		'cookie_set',
		array(
			'source'         => $source,
			'cookie_name'    => $name,
			'cookie_path'    => $path,
			'cookie_domain'  => $domain,
			'cookie_secure'  => $secure,
			'cookie_expires' => $expires,
			'headers_sent'   => headers_sent(),
		)
	);
}

/**
 * Full URL for redirect back after unlock.
 *
 * @return string
 */
function ihq_portal_turnstile_current_url() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$host = ihq_portal_turnstile_request_host();
	if ( $host === '' ) {
		return esc_url_raw( home_url( $uri ) );
	}
	return esc_url_raw( ihq_portal_turnstile_request_scheme() . '://' . $host . $uri );
}

/**
 * @param string $redirect_to Destination after verify.
 * @return string
 */
function ihq_portal_turnstile_verify_url( $redirect_to = '' ) {
	$url = ihq_portal_turnstile_request_url( '/' . IHQ_PORTAL_TURNSTILE_VERIFY_SLUG . '/' );
	if ( is_string( $redirect_to ) && $redirect_to !== '' ) {
		$url = add_query_arg( 'redirect_to', $redirect_to, $url );
	}
	return $url;
}

/**
 * Normalized paths that are gated (all portal pages except verify + login).
 *
 * @param string $path Path relative to site root, no leading slash (lowercase).
 * @return bool
 */
function ihq_portal_turnstile_path_is_gated_portal( $path ) {
	$path = ltrim( strtolower( untrailingslashit( (string) $path ) ), '/' );

	if ( $path === '' ) {
		return false;
	}

	if ( $path === IHQ_PORTAL_TURNSTILE_VERIFY_SLUG
		|| strpos( $path, IHQ_PORTAL_TURNSTILE_VERIFY_SLUG . '/' ) === 0 ) {
		return false;
	}

	if ( preg_match( '#^portal/login(?:/|$)#', $path ) ) {
		return false;
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
 * Whether a full URL is a safe post-verify redirect (any gated portal page).
 *
 * @param string $url Absolute URL.
 * @return bool
 */
function ihq_portal_turnstile_is_allowed_redirect( $url ) {
	$url = esc_url_raw( (string) $url );
	if ( $url === '' ) {
		return false;
	}

	$home        = wp_parse_url( home_url( '/' ) );
	$target      = wp_parse_url( $url );
	$home_host   = isset( $home['host'] ) ? strtolower( $home['host'] ) : '';
	$target_host = isset( $target['host'] ) ? strtolower( $target['host'] ) : '';
	$current_host = ihq_portal_turnstile_request_host();

	$allowed_hosts = array_filter( array_unique( array( $home_host, $current_host ) ) );
	if ( $target_host === '' || ! in_array( $target_host, $allowed_hosts, true ) ) {
		return false;
	}

	$path = isset( $target['path'] ) ? strtolower( untrailingslashit( $target['path'] ) ) : '';
	$site_path = isset( $home['path'] ) ? strtolower( untrailingslashit( $home['path'] ) ) : '';
	if ( $site_path !== '' && strpos( $path, $site_path ) === 0 ) {
		$path = substr( $path, strlen( $site_path ) );
	}

	return ihq_portal_turnstile_path_is_gated_portal( ltrim( $path, '/' ) );
}

/**
 * Default portal landing URL when no valid redirect_to is provided.
 *
 * @return string
 */
function ihq_portal_turnstile_default_portal_url() {
	return ihq_portal_turnstile_request_url( '/portal/portal-home/' );
}

/**
 * Resolve redirect target from query string.
 *
 * @return string
 */
function ihq_portal_turnstile_resolve_redirect_target() {
	$redirect_to = isset( $_GET['redirect_to'] )
		? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) )
		: '';

	if ( ! ihq_portal_turnstile_is_allowed_redirect( $redirect_to ) ) {
		return ihq_portal_turnstile_default_portal_url();
	}

	return ihq_portal_turnstile_normalize_redirect_host( $redirect_to );
}

/**
 * Keep redirect on the same host the visitor is using.
 *
 * @param string $url Absolute URL.
 * @return string
 */
function ihq_portal_turnstile_normalize_redirect_host( $url ) {
	$host = ihq_portal_turnstile_request_host();
	if ( $host === '' ) {
		return $url;
	}

	$parsed = wp_parse_url( $url );
	if ( ! is_array( $parsed ) || empty( $parsed['host'] ) ) {
		return $url;
	}

	if ( strtolower( $parsed['host'] ) === $host ) {
		return $url;
	}

	$parsed['host'] = $host;
	$scheme         = ihq_portal_turnstile_request_scheme();
	$path           = isset( $parsed['path'] ) ? $parsed['path'] : '';
	$query          = isset( $parsed['query'] ) ? '?' . $parsed['query'] : '';
	$fragment       = isset( $parsed['fragment'] ) ? '#' . $parsed['fragment'] : '';

	return esc_url_raw( $scheme . '://' . $host . $path . $query . $fragment );
}

/**
 * Redirect to an absolute same-host URL (never a bare relative path).
 *
 * @param string $url Absolute or site-relative destination.
 */
function ihq_portal_turnstile_safe_redirect( $url ) {
	$parsed = wp_parse_url( (string) $url );
	if ( ! is_array( $parsed ) || empty( $parsed['host'] ) ) {
		$path = is_string( $url ) ? $url : '/';
		$url  = ihq_portal_turnstile_request_url( $path );
	}

	if ( ! headers_sent() ) {
		nocache_headers();
	}

	ihq_portal_turnstile_log( 'safe_redirect', array( 'location' => $url ) );

	wp_safe_redirect( $url );
	exit;
}

/**
 * One-time bypass token after successful verify (covers cookie edge cases behind proxies/CDN).
 *
 * @param string $redirect_to Destination URL.
 */
function ihq_portal_turnstile_redirect_after_unlock( $redirect_to ) {
	// Server-side pass survives when browsers/CDN drop Set-Cookie on redirects.
	ihq_portal_turnstile_store_client_pass( 'siteverify_redirect' );

	$redirect_to = ihq_portal_turnstile_normalize_redirect_host( $redirect_to );
	$token       = ihq_portal_turnstile_create_pts_token();
	$target      = add_query_arg( IHQ_PORTAL_TURNSTILE_PTS_ARG, $token, $redirect_to );

	ihq_portal_turnstile_log(
		'redirect_after_unlock',
		array(
			'redirect_to'     => $redirect_to,
			'redirect_target' => $target,
			'pts_token_len'   => strlen( $token ),
		)
	);

	ihq_portal_turnstile_safe_redirect( $target );
}

/**
 * Render /portal/verify/ (Turnstile only — not gated).
 */
function ihq_portal_turnstile_render_verify_page() {
	if ( ! ihq_request_is_portal_turnstile_verify_page() ) {
		return;
	}

	$pass_status = ihq_portal_turnstile_pass_status();

	ihq_portal_turnstile_log(
		'verify_page_hit',
		array(
			'pass_status' => $pass_status,
		)
	);

	if ( is_user_logged_in() ) {
		$target = ihq_portal_turnstile_resolve_redirect_target();
		ihq_portal_turnstile_log( 'verify_skip_logged_in', array( 'redirect_to' => $target ) );
		ihq_portal_turnstile_safe_redirect( $target );
	}

	if ( $pass_status['valid'] ) {
		if ( $pass_status['via'] === 'pts' ) {
			ihq_portal_turnstile_persist_pass( 'pts_verify_redirect' );
		}
		$target = ihq_portal_turnstile_resolve_redirect_target();
		ihq_portal_turnstile_log(
			'verify_skip_pass_valid',
			array(
				'pass_via'    => $pass_status['via'],
				'redirect_to' => $target,
			)
		);
		ihq_portal_turnstile_safe_redirect( $target );
	}

	$redirect_to = ihq_portal_turnstile_resolve_redirect_target();
	$site_key    = defined( 'CF_TURNSTILE_SITE_KEY' ) ? CF_TURNSTILE_SITE_KEY : '';
	$post_url    = ihq_portal_turnstile_request_url( '/' . IHQ_PORTAL_TURNSTILE_VERIFY_SLUG . '/' );
	$nonce       = wp_create_nonce( 'ihq_portal_turnstile_unlock' );
	$ts_error    = isset( $_GET['ts_error'] ) && (string) $_GET['ts_error'] === '1';
	$js_path     = get_template_directory() . '/js/ihq-portal-turnstile-gate.js';
	$js_version  = file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1';
	$js_url      = get_template_directory_uri() . '/js/ihq-portal-turnstile-gate.js';
	$logo_url    = get_template_directory_uri() . '/images/logo-home-claude.jpg';

	ihq_portal_turnstile_log(
		'verify_render_interstitial',
		array(
			'redirect_to' => $redirect_to,
			'post_url'    => $post_url,
			'ts_error'    => $ts_error,
			'site_key_set'=> $site_key !== '',
		)
	);

	status_header( 200 );
	nocache_headers();

	include get_template_directory() . '/template-parts/portal-turnstile-interstitial.php';
	exit;
}

add_action( 'template_redirect', 'ihq_portal_turnstile_render_verify_page', 2 );

/**
 * Read Turnstile token from POST without stripping valid token characters.
 *
 * @return string
 */
function ihq_portal_turnstile_read_post_token() {
	if ( ! isset( $_POST['cf-turnstile-response'] ) ) {
		return '';
	}
	$token = wp_unslash( $_POST['cf-turnstile-response'] );
	if ( ! is_string( $token ) ) {
		return '';
	}
	$token = trim( $token );
	if ( $token === '' || strlen( $token ) > 2048 ) {
		return '';
	}
	return $token;
}

/**
 * POST /portal/verify/ — siteverify, set cookie, redirect to portal page.
 */
function ihq_portal_turnstile_handle_verify_post() {
	if ( ! ihq_request_is_portal_turnstile_verify_page() ) {
		return;
	}

	if ( ( $_SERVER['REQUEST_METHOD'] ?? '' ) !== 'POST' ) {
		return;
	}

	if ( empty( $_POST['ihq_portal_ts_unlock'] ) ) {
		return;
	}

	ihq_portal_turnstile_log(
		'verify_post_received',
		array(
			'redirect_to_post' => isset( $_POST['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_POST['redirect_to'] ) ) : '',
			'has_nonce'        => isset( $_POST['nonce'] ),
			'has_ts_token'     => isset( $_POST['cf-turnstile-response'] ),
		)
	);

	check_admin_referer( 'ihq_portal_turnstile_unlock', 'nonce' );

	if ( ! function_exists( 'ihq_turnstile_verify_response' ) ) {
		ihq_portal_turnstile_log( 'verify_post_abort', array( 'reason' => 'turnstile_verify_fn_missing' ) );
		wp_die( esc_html__( 'Verification is not available.', 'avantage-baccarat' ), '', array( 'response' => 503 ) );
	}

	$token = ihq_portal_turnstile_read_post_token();
	$check = ihq_turnstile_verify_response( $token );

	if ( empty( $check['success'] ) ) {
		ihq_portal_turnstile_log(
			'verify_post_siteverify_failed',
			array(
				'token_len'   => strlen( $token ),
				'error_codes' => isset( $check['error_codes'] ) ? $check['error_codes'] : array(),
			)
		);

		$redirect_to = isset( $_POST['redirect_to'] )
			? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) )
			: ihq_portal_turnstile_default_portal_url();

		if ( ! ihq_portal_turnstile_is_allowed_redirect( $redirect_to ) ) {
			$redirect_to = ihq_portal_turnstile_default_portal_url();
		}

		$retry_url = add_query_arg(
			array(
				'ts_error'    => '1',
				'redirect_to' => ihq_portal_turnstile_normalize_redirect_host( $redirect_to ),
			),
			ihq_portal_turnstile_request_url( '/' . IHQ_PORTAL_TURNSTILE_VERIFY_SLUG . '/' )
		);

		ihq_portal_turnstile_log( 'verify_post_retry_redirect', array( 'retry_url' => $retry_url ) );

		ihq_portal_turnstile_safe_redirect( $retry_url );
	}

	ihq_portal_turnstile_log( 'verify_post_siteverify_ok', array( 'token_len' => strlen( $token ) ) );

	$redirect_to = isset( $_POST['redirect_to'] )
		? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) )
		: ihq_portal_turnstile_default_portal_url();

	if ( ! ihq_portal_turnstile_is_allowed_redirect( $redirect_to ) ) {
		$redirect_to = ihq_portal_turnstile_default_portal_url();
	}

	ihq_portal_turnstile_redirect_after_unlock( ihq_portal_turnstile_normalize_redirect_host( $redirect_to ) );
}

add_action( 'template_redirect', 'ihq_portal_turnstile_handle_verify_post', 1 );

/**
 * Redirect guests without pass cookie to /portal/verify/.
 */
function ihq_maybe_portal_turnstile_gate() {
	if ( is_user_logged_in() ) {
		return;
	}

	if ( ! function_exists( 'ihq_turnstile_is_configured' ) || ! ihq_turnstile_is_configured() ) {
		return;
	}

	if ( ! ihq_request_is_portal_route() ) {
		return;
	}

	$pass_status = ihq_portal_turnstile_pass_status();

	if ( $pass_status['valid'] ) {
		if ( $pass_status['via'] === 'pts' ) {
			ihq_portal_turnstile_persist_pass( 'pts_gate' );
		} elseif ( $pass_status['via'] === 'client_store' ) {
			ihq_portal_turnstile_set_pass_cookie( 'client_store_refresh' );
		}
		ihq_portal_turnstile_log(
			'gate_allow',
			array(
				'pass_via' => $pass_status['via'],
			)
		);
		return;
	}

	$verify_url = ihq_portal_turnstile_verify_url( ihq_portal_turnstile_current_url() );

	ihq_portal_turnstile_log(
		'gate_redirect_verify',
		array(
			'pass_status' => $pass_status,
			'verify_url'  => $verify_url,
		)
	);

	ihq_portal_turnstile_safe_redirect( $verify_url );
}

add_action( 'template_redirect', 'ihq_maybe_portal_turnstile_gate', 3 );

/**
 * Prevent CDN from caching portal HTML that sets pass cookies / transients.
 */
function ihq_portal_turnstile_send_headers() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	if ( ! ihq_request_is_portal_route() && ! ihq_request_is_portal_turnstile_verify_page() ) {
		return;
	}

	if ( headers_sent() ) {
		return;
	}

	header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );
	header( 'CDN-Cache-Control: no-store' );
}

add_action( 'send_headers', 'ihq_portal_turnstile_send_headers', 0 );

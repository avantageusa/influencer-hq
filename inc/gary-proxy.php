<?php
/**
 * Gary Coach API proxy — server-to-server bridge for PO-3062 (AI Coach Registration).
 *
 * Gary's Coach API (docs.gary.club/clients/avantage/) is server-to-server only: the
 * signing key must never reach the browser. This file mints the HMAC-signed request
 * on the WP server and exposes our own REST routes for the browser to call instead —
 * same shape as inc/anam-proxy.php's session-token proxy, just for Gary instead of
 * Anam directly.
 *
 * INSTALL: standalone include, does not touch functions.php's other logic. Requires
 * two constants defined in wp-config.php (never commit them):
 *
 *     define( 'GARY_COACH_KEY', 'ck_live_xxxx' );
 *     define( 'GARY_COACH_SECRET', 'xxxx' );
 *
 * (Sami/InfluencerHQ's key+secret, issued by Gary's team — see the "Access for Ivan's
 * team" section of their docs page.) For local wp-env testing, add them to the
 * "config" object in .wp-env.json instead (that file is untracked in this repo).
 *
 * Confirmed-working signing formula (verified via Postman against GET /coach/v1/health
 * before this file was written — see docs/plans/2026-09-01-PO-3108-*.plan.md history /
 * project memory for how this was derived):
 *   - path includes the leading "/coach" segment, e.g. "/coach/v1/session"
 *   - body is the exact JSON string sent, or "" for a bodyless request — the trailing
 *     "." from joining with an empty 4th field is kept, not stripped
 *   - secret is used as a plain UTF-8 string, NOT hex-decoded into raw bytes
 *   - both the key and secret MUST be trimmed — a stray trailing space/newline from
 *     wherever they're pasted silently breaks every signature
 *
 * Endpoints exposed here (all under ihq/v1, all require a valid WP REST nonce, same
 * as anam-proxy.php's session-token route):
 *   POST /wp-json/ihq/v1/coach/session          -> open a session, returns Gary's envelope
 *   POST /wp-json/ihq/v1/coach/{id}/message     -> player asks a question
 *   POST /wp-json/ihq/v1/coach/{id}/close       -> close the session
 *   GET  /wp-json/ihq/v1/coach/health           -> passthrough for connectivity testing
 *
 * NOT wired into js/aicoach-coach-flow.js yet — this is the backend layer only, for
 * local testing ahead of confirming the FE swap-over plan with the team. The current
 * interim direct-Anam-SDK flow is untouched by this file.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

if ( ! defined( 'IHQ_COACH_HOST' ) ) {
	define( 'IHQ_COACH_HOST', 'https://influencerhq.agent.gary.club' );
}

/**
 * Resolve the Coach API key, trimmed, or null if unset.
 *
 * @return string|null
 */
function ihq_coach_key() {
	if ( defined( 'GARY_COACH_KEY' ) && GARY_COACH_KEY ) {
		return trim( GARY_COACH_KEY );
	}
	$env = getenv( 'GARY_COACH_KEY' );
	return $env ? trim( $env ) : null;
}

/**
 * Resolve the Coach API secret, trimmed, or null if unset.
 *
 * @return string|null
 */
function ihq_coach_secret() {
	if ( defined( 'GARY_COACH_SECRET' ) && GARY_COACH_SECRET ) {
		return trim( GARY_COACH_SECRET );
	}
	$env = getenv( 'GARY_COACH_SECRET' );
	return $env ? trim( $env ) : null;
}

/**
 * Sign and send a request to Gary's Coach API.
 *
 * @param string     $method     HTTP method, e.g. 'GET' or 'POST'.
 * @param string     $path       Full path including "/coach", e.g. "/coach/v1/session".
 * @param array|null $body_array Request payload, or null for a bodyless request.
 * @return array{status:int,body:array}|WP_Error
 */
function ihq_coach_request( $method, $path, $body_array = null ) {
	// IHQ_COACH_HOST is a trusted admin-defined constant, not user input — but
	// it's still worth refusing to sign/send anything to it unless it's an
	// absolute https:// origin, same "never trust a bare constant" discipline
	// as inc/ihq-env.php's URL constants. Reuses that file's validator (loaded
	// first in functions.php) instead of a bare prefix check, which would
	// wrongly accept something like "https://" with no host.
	if ( ! ihq_env_is_https_url( IHQ_COACH_HOST ) ) {
		return new WP_Error(
			'coach_host_untrusted',
			'IHQ_COACH_HOST must be an absolute https:// URL.'
		);
	}

	$key    = ihq_coach_key();
	$secret = ihq_coach_secret();
	if ( ! $key || ! $secret ) {
		return new WP_Error(
			'coach_not_configured',
			'GARY_COACH_KEY / GARY_COACH_SECRET are not set. Define them in wp-config.php (or .wp-env.json locally).'
		);
	}

	$body_json = null !== $body_array ? wp_json_encode( $body_array ) : '';
	$timestamp = (string) time();
	$base      = $timestamp . '.' . $method . '.' . $path . '.' . $body_json;
	$signature = 'sha256=' . hash_hmac( 'sha256', $base, $secret );

	$args = array(
		'method'      => $method,
		'timeout'     => 15,
		// wp_remote_request() follows redirects by default and resends the
		// same $args — including these signed Coach headers — to wherever the
		// redirect points. We know Gary's exact host; never follow elsewhere
		// with a live API key attached (CWE-200, flagged by CodeRabbit on
		// PR #34).
		'redirection' => 0,
		'headers'     => array(
			'Content-Type'      => 'application/json',
			'X-Coach-Key'       => $key,
			'X-Coach-Timestamp' => $timestamp,
			'X-Coach-Signature' => $signature,
		),
	);
	if ( '' !== $body_json ) {
		$args['body'] = $body_json;
	}

	$response = wp_remote_request( IHQ_COACH_HOST . $path, $args );
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status = (int) wp_remote_retrieve_response_code( $response );
	$data   = json_decode( wp_remote_retrieve_body( $response ), true );

	return array(
		'status' => $status,
		'body'   => is_array( $data ) ? $data : array(),
	);
}

/**
 * Same permission_callback nonce pattern as anam-proxy.php's session-token route.
 * Not real auth, just "this call originated from a page we rendered."
 *
 * @param WP_REST_Request $request Request.
 * @return bool
 */
function ihq_coach_permission_check( WP_REST_Request $request ) {
	$nonce = $request->get_header( 'X-WP-Nonce' );
	return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
}

/**
 * POST /ihq/v1/coach/session — open a session with Sami.
 *
 * The player.ref is a fresh random UUID per session, never tied to any WP user or
 * identity captured later in the flow — Gary's API requires this to be pseudonymous.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function ihq_coach_handle_open_session( WP_REST_Request $request ) {
	$locale = strtolower( sanitize_text_field( (string) $request->get_param( 'locale' ) ) );

	// Gary's registration surface accepts only reviewed English locale variants
	// and returns a 422 for anything else (confirmed 2026-09-16) — the visitor
	// can still see the UI in any of the 7 supported locales (PO-3103/PO-3104),
	// this only controls what we tell Gary until other translations are
	// approved and released on their side.
	$gary_registration_locales = array( 'en', 'en-us', 'en-gb' );
	if ( ! in_array( $locale, $gary_registration_locales, true ) ) {
		$locale = 'en';
	}

	$payload = array(
		'player' => array(
			'ref'    => wp_generate_uuid4(),
			'locale' => $locale,
		),
		// "video" is optional per Gary's docs — must be requested explicitly or
		// say.video (the Anam session_token the FE needs to stream the avatar) is
		// omitted from the response entirely.
		'want'   => array( 'text', 'audio', 'video' ),
	);

	$result = ihq_coach_request( 'POST', '/coach/v1/session', $payload );
	if ( is_wp_error( $result ) ) {
		return new WP_REST_Response( array( 'error' => $result->get_error_message() ), 502 );
	}

	return new WP_REST_Response( $result['body'], $result['status'] );
}

/**
 * POST /ihq/v1/coach/{session_id}/message — the visitor asks a question.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function ihq_coach_handle_message( WP_REST_Request $request ) {
	$session_id = sanitize_text_field( (string) $request->get_param( 'session_id' ) );

	// Deliberately NOT sanitize_textarea_field() here — this text is never rendered
	// as HTML on our side, it's forwarded verbatim as the conversation content in a
	// JSON payload to Gary. sanitize_textarea_field() rewrites "<"/">" to HTML
	// entities and strips tag-shaped content, which would corrupt a visitor's actual
	// question (e.g. "is 2 < 5 minutes enough?") before the coach ever sees it.
	// wp_check_invalid_utf8() only guards against malformed byte sequences; a length
	// cap protects the per-character TTS budget on a paid third-party API.
	$text = trim( wp_check_invalid_utf8( (string) $request->get_param( 'text' ), true ) );
	if ( strlen( $text ) > 2000 ) {
		$text = substr( $text, 0, 2000 );
	}

	if ( '' === $session_id || '' === $text ) {
		return new WP_REST_Response( array( 'error' => 'session_id and text are required.' ), 400 );
	}

	$result = ihq_coach_request(
		'POST',
		'/coach/v1/session/' . rawurlencode( $session_id ) . '/message',
		array( 'text' => $text )
	);
	if ( is_wp_error( $result ) ) {
		return new WP_REST_Response( array( 'error' => $result->get_error_message() ), 502 );
	}

	return new WP_REST_Response( $result['body'], $result['status'] );
}

/**
 * POST /ihq/v1/coach/{session_id}/close — end the sitting.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function ihq_coach_handle_close( WP_REST_Request $request ) {
	$session_id = sanitize_text_field( (string) $request->get_param( 'session_id' ) );
	if ( '' === $session_id ) {
		return new WP_REST_Response( array( 'error' => 'session_id is required.' ), 400 );
	}

	$result = ihq_coach_request( 'POST', '/coach/v1/session/' . rawurlencode( $session_id ) . '/close', null );
	if ( is_wp_error( $result ) ) {
		return new WP_REST_Response( array( 'error' => $result->get_error_message() ), 502 );
	}

	return new WP_REST_Response( $result['body'], $result['status'] );
}

/**
 * GET /ihq/v1/coach/health — connectivity/config check, mirrors GET /coach/v1/health.
 *
 * @return WP_REST_Response
 */
function ihq_coach_handle_health() {
	$result = ihq_coach_request( 'GET', '/coach/v1/health', null );
	if ( is_wp_error( $result ) ) {
		return new WP_REST_Response( array( 'error' => $result->get_error_message() ), 502 );
	}

	return new WP_REST_Response( $result['body'], $result['status'] );
}

/**
 * GET /ihq/v1/coach/scripts — passthrough for GET /coach/v1/registration/scripts.
 * Lets us check a script's approval status (script_status/review_required per
 * version/stage) directly, without opening a real session each time.
 *
 * @return WP_REST_Response
 */
function ihq_coach_handle_scripts() {
	$result = ihq_coach_request( 'GET', '/coach/v1/registration/scripts', null );
	if ( is_wp_error( $result ) ) {
		return new WP_REST_Response( array( 'error' => $result->get_error_message() ), 502 );
	}

	return new WP_REST_Response( $result['body'], $result['status'] );
}

/**
 * Register the Coach REST routes.
 */
function ihq_coach_register_routes() {
	register_rest_route(
		'ihq/v1',
		'/coach/session',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'ihq_coach_handle_open_session',
			'permission_callback' => 'ihq_coach_permission_check',
		)
	);

	register_rest_route(
		'ihq/v1',
		'/coach/(?P<session_id>[a-zA-Z0-9_\-]+)/message',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'ihq_coach_handle_message',
			'permission_callback' => 'ihq_coach_permission_check',
		)
	);

	register_rest_route(
		'ihq/v1',
		'/coach/(?P<session_id>[a-zA-Z0-9_\-]+)/close',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'ihq_coach_handle_close',
			'permission_callback' => 'ihq_coach_permission_check',
		)
	);

	register_rest_route(
		'ihq/v1',
		'/coach/health',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'ihq_coach_handle_health',
			'permission_callback' => 'ihq_coach_permission_check',
		)
	);

	register_rest_route(
		'ihq/v1',
		'/coach/scripts',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'ihq_coach_handle_scripts',
			'permission_callback' => 'ihq_coach_permission_check',
		)
	);
}
add_action( 'rest_api_init', 'ihq_coach_register_routes' );

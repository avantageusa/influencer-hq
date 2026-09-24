<?php
/**
 * AI Coach registration progress persistence (PO-3102 / FR-11).
 *
 * The ticket attributes this to "Luna's memory capabilities (owned by Gary)" — that
 * doesn't hold up: Gary's own GET /coach/v1/health reports
 * memory_excluded_surfaces: ["registration"] (and even where available it's a 30-day,
 * 24-message buffer, not indefinite retention), and this repo's actual Luna integration
 * (inc/luna-users-rest.php) is a one-way export of COMPLETED registrations that Gary's
 * team pulls — nothing about in-progress state. Separately, inc/gary-proxy.php's
 * player.ref is a fresh random UUID on every session open by design, so even a perfect
 * memory system on Gary's side would never recognize a returning visitor as the same one.
 *
 * This file is a self-contained replacement: a stable pseudonymous visitor ref (a
 * long-lived cookie, same-browser/device only — cross-device resume is explicitly out of
 * scope for v1, see the PO-3102 plan doc) and our own storage. wp_options, not a custom
 * table — this theme has none anywhere, and a single per-visitor JSON blob with no
 * querying/reporting need doesn't justify being the first.
 *
 * POST /wp-json/ihq/v1/aicoach/progress — save/merge partial progress.
 * GET  /wp-json/ihq/v1/aicoach/progress — read current progress (creates the ref cookie
 *      on first visit if it's missing).
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Cookie name for the stable, pseudonymous per-visitor progress ref. */
const IHQ_AICOACH_PROGRESS_COOKIE = 'ihq_aicoach_ref';

/** How long the ref cookie lives — "no expiry" on the progress record itself doesn't help
 * a visitor we can no longer recognize; two years comfortably covers "days, weeks, or
 * months" (Scenario 21) without pretending to solve cross-device/cleared-cookie resume. */
const IHQ_AICOACH_PROGRESS_COOKIE_TTL = 2 * YEAR_IN_SECONDS;

/**
 * A write costs one wp_options row (a cookie-less or forged-cookie POST creates a new one,
 * confirmed live — CodeRabbit review, PR #49) with no per-visitor auth stronger than "sent
 * a valid-looking UUID cookie." Same simple per-IP counter shape as
 * IHQ_AICOACH_REGISTER_THROTTLE_SECONDS (inc/aicoach-register.php), sized generously for a
 * real visitor's whole flow (roughly a save per panel/tier/identity/channels/language
 * change — comfortably under 20 in practice) while still bounding a scripted flood to a
 * low sustained rate. Reads (GET) aren't limited — they don't write anything.
 */
const IHQ_AICOACH_PROGRESS_RATE_LIMIT_MAX_WRITES = 60;
const IHQ_AICOACH_PROGRESS_RATE_LIMIT_WINDOW     = MINUTE_IN_SECONDS;

/** Sanity caps on a POST body — bound how much a single (possibly forged) request can
 * write into one option row. Generous relative to anything the real UI could ever send. */
const IHQ_AICOACH_PROGRESS_MAX_STAGE_LENGTH = 64;
const IHQ_AICOACH_PROGRESS_MAX_FIELD_LENGTH = 190;
const IHQ_AICOACH_PROGRESS_MAX_CHANNELS     = 20;

/**
 * Read the visitor's progress ref from the request cookie, or mint and set a new one.
 *
 * Reads $_COOKIE directly (already sent by the browser on this request) rather than
 * wp_unslash()-ing through $request, since REST requests don't expose cookies as params —
 * matches how WP's own REST nonce cookie auth reads $_COOKIE directly.
 *
 * @return string A valid UUID.
 */
function ihq_aicoach_progress_get_ref() {
	$existing = isset( $_COOKIE[ IHQ_AICOACH_PROGRESS_COOKIE ] )
		? sanitize_text_field( wp_unslash( $_COOKIE[ IHQ_AICOACH_PROGRESS_COOKIE ] ) )
		: '';

	if ( $existing !== '' && preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $existing ) ) {
		return $existing;
	}

	$ref = wp_generate_uuid4();
	// Headers may already be sent in some contexts (e.g. a unit-style call) — best-effort,
	// same discipline as every other cookie-setting call in this theme.
	if ( ! headers_sent() ) {
		setcookie(
			IHQ_AICOACH_PROGRESS_COOKIE,
			$ref,
			array(
				'expires'  => time() + IHQ_AICOACH_PROGRESS_COOKIE_TTL,
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}
	return $ref;
}

/**
 * @param string $ref
 * @return string wp_options key for this visitor's progress record.
 */
function ihq_aicoach_progress_option_key( $ref ) {
	return 'ihq_aicoach_progress_' . $ref;
}

/**
 * @param string $ref
 * @return array Saved progress, or an empty array if nothing's been saved yet.
 */
function ihq_aicoach_progress_load( $ref ) {
	$saved = get_option( ihq_aicoach_progress_option_key( $ref ), array() );
	return is_array( $saved ) ? $saved : array();
}

/**
 * Merge and persist a partial progress update. Only fields present in $partial are
 * touched — e.g. saving a tier selection never clobbers already-captured identity fields.
 *
 * @param string $ref
 * @param array  $partial
 * @return array The full merged record that was saved.
 */
function ihq_aicoach_progress_save( $ref, array $partial ) {
	$current = ihq_aicoach_progress_load( $ref );
	$merged  = array_merge( $current, $partial );
	$merged['updated_at'] = gmdate( 'c' );
	// autoload=no — this loads on exactly one page (the AI Coach landing page's own
	// progress check), never needs to be in the options autoload cache every request.
	update_option( ihq_aicoach_progress_option_key( $ref ), $merged, false );
	return $merged;
}

/**
 * Delete a visitor's progress record — called once registration actually completes
 * (inc/aicoach-register.php), since a real WP account now exists and the draft record
 * has done its job.
 *
 * @param string $ref
 * @return void
 */
function ihq_aicoach_progress_clear( $ref ) {
	if ( $ref !== '' ) {
		delete_option( ihq_aicoach_progress_option_key( $ref ) );
	}
}

/**
 * Whitelist + sanitize a partial progress payload from the REST request body. Same
 * "an agent cannot write arbitrary meta keys" discipline as
 * ihq_aicoach_allowed_channel_keys() in inc/aicoach-register.php.
 *
 * @param array $params Raw REST params.
 * @return array Sanitized partial record, only recognized keys.
 */
function ihq_aicoach_progress_sanitize_partial( array $params ) {
	$out = array();

	// mb_substr(), not substr() — first/last name and username routinely carry
	// multi-byte characters on a 7-language product; a byte-length cut could split
	// one in the middle.
	$cap = function ( $value, $max ) {
		return mb_substr( (string) $value, 0, $max );
	};

	if ( isset( $params['stage'] ) && is_string( $params['stage'] ) ) {
		$stage = $cap( sanitize_text_field( $params['stage'] ), IHQ_AICOACH_PROGRESS_MAX_STAGE_LENGTH );
		if ( $stage !== '' ) {
			$out['stage'] = $stage;
		}
	}

	if ( isset( $params['tier'] ) && in_array( (string) $params['tier'], array( '2', '5', '10' ), true ) ) {
		$out['tier'] = (string) $params['tier'];
	}

	if ( isset( $params['language'] ) && is_string( $params['language'] ) ) {
		$language = $cap( sanitize_key( $params['language'] ), IHQ_AICOACH_PROGRESS_MAX_STAGE_LENGTH );
		if ( $language !== '' ) {
			$out['language'] = $language;
		}
	}

	if ( isset( $params['identity'] ) && is_array( $params['identity'] ) ) {
		$out['identity'] = array(
			'firstName' => $cap( isset( $params['identity']['firstName'] ) ? sanitize_text_field( (string) $params['identity']['firstName'] ) : '', IHQ_AICOACH_PROGRESS_MAX_FIELD_LENGTH ),
			'lastName'  => $cap( isset( $params['identity']['lastName'] ) ? sanitize_text_field( (string) $params['identity']['lastName'] ) : '', IHQ_AICOACH_PROGRESS_MAX_FIELD_LENGTH ),
			'username'  => $cap( isset( $params['identity']['username'] ) ? sanitize_text_field( (string) $params['identity']['username'] ) : '', IHQ_AICOACH_PROGRESS_MAX_FIELD_LENGTH ),
		);
	}

	if ( isset( $params['channels'] ) && is_array( $params['channels'] ) ) {
		$allowed  = function_exists( 'ihq_aicoach_allowed_channel_keys' ) ? ihq_aicoach_allowed_channel_keys() : array();
		$channels = array();
		// array_slice() first — a payload with thousands of entries never even
		// reaches the loop body, not just the final stored array.
		foreach ( array_slice( $params['channels'], 0, IHQ_AICOACH_PROGRESS_MAX_CHANNELS ) as $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['channel'] ) || ! isset( $entry['value'] ) ) {
				continue;
			}
			$channel = sanitize_key( (string) $entry['channel'] );
			if ( $allowed && ! in_array( $channel, $allowed, true ) ) {
				continue;
			}
			$channels[] = array(
				'channel' => $channel,
				'value'   => $cap( sanitize_text_field( (string) $entry['value'] ), IHQ_AICOACH_PROGRESS_MAX_FIELD_LENGTH ),
			);
		}
		$out['channels'] = $channels;
	}

	// FR-18 (PO-3109) isn't built yet — no appointment UI exists to populate this from.
	// Shape reserved so that story doesn't need a second progress-record migration.
	if ( isset( $params['appointment'] ) && is_array( $params['appointment'] ) ) {
		$out['appointment'] = array_map(
			function ( $value ) use ( $cap ) {
				return $cap( sanitize_text_field( (string) $value ), IHQ_AICOACH_PROGRESS_MAX_FIELD_LENGTH );
			},
			wp_array_slice_assoc( $params['appointment'], array( 'date', 'time', 'timezone' ) )
		);
	}

	return $out;
}

/**
 * Simple per-IP write counter, same shape as inc/aicoach-register.php's
 * IHQ_AICOACH_REGISTER_THROTTLE_SECONDS transient — a fixed window, not a true sliding
 * one (set_transient() always restarts the TTL), which is exactly the level of
 * sophistication that file already uses for the same kind of unauthenticated-write abuse.
 *
 * Two known, deliberately-not-fixed-here limitations (CodeRabbit review, PR #49):
 *
 * 1. ihq_get_client_ip_for_rate_limit() (inc/email-verification-handler.php) trusts
 *    X-Forwarded-For/CF-Connecting-IP without a configured trusted-proxy check, so a
 *    request that reaches PHP directly (bypassing Cloudflare) could vary its own IP per
 *    request and get a fresh counter every time. This is the SAME shared helper this
 *    repo's registration throttle and login-code lockout already rely on — hardening it
 *    only here would be cosmetic while leaving those two exactly as exposed, and fixing
 *    it properly needs knowing this host's actual trusted-proxy topology (does WP Engine's
 *    edge only accept Cloudflare origin traffic here?), which isn't something to guess at
 *    for a security-relevant change. A shared-helper fix, if warranted, is a separate,
 *    cross-cutting piece of work, not a PO-3102-scoped one.
 * 2. This check-then-set is not atomic — concurrent requests from the same IP can read
 *    the same count and both pass, allowing bounded overshoot under a real concurrent
 *    burst. The registration throttle and login lockout use the identical non-atomic
 *    transient pattern (this repo does have an atomic INSERT...ON DUPLICATE KEY UPDATE
 *    counter elsewhere, ihq_login_verify_increment_failures(), if a hard cap is ever
 *    needed here). This limiter's job is bounding a sustained flood to a low rate, not
 *    guaranteeing an exact count — a soft cap is enough for that and matches this
 *    codebase's existing convention for the same class of problem.
 *
 * Neither weakens what this specific rate limit is actually for: the empty-partial guard
 * and the sanitizer's length/count caps (both applied regardless of this check) already
 * bound how much a single request — spoofed IP or not — can write.
 *
 * @return bool True if this IP has already hit the write cap for the current window.
 */
function ihq_aicoach_progress_rate_limited() {
	$ip = function_exists( 'ihq_get_client_ip_for_rate_limit' ) ? ihq_get_client_ip_for_rate_limit() : '';
	$key = 'ihq_aicoach_progress_rl_' . md5( $ip !== '' ? $ip : 'unknown' );

	$count = get_transient( $key );
	if ( false === $count ) {
		set_transient( $key, 1, IHQ_AICOACH_PROGRESS_RATE_LIMIT_WINDOW );
		return false;
	}
	if ( (int) $count >= IHQ_AICOACH_PROGRESS_RATE_LIMIT_MAX_WRITES ) {
		return true;
	}
	set_transient( $key, (int) $count + 1, IHQ_AICOACH_PROGRESS_RATE_LIMIT_WINDOW );
	return false;
}

/**
 * permission_callback — same REST nonce every other ihq/v1 coach route already sends.
 *
 * @param WP_REST_Request $request Request.
 * @return bool
 */
function ihq_aicoach_progress_permission_check( WP_REST_Request $request ) {
	$nonce = $request->get_header( 'X-WP-Nonce' );
	return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
}

/**
 * Mark a response as never cacheable by any layer between the visitor and
 * this server. Every visitor's progress record is identified only by an
 * HttpOnly cookie, not by anything in the URL or a per-visitor nonce (a
 * logged-out wp_rest nonce is the same value for every anonymous visitor,
 * so it can't be relied on to vary a cache key either) — a CDN that caches
 * this response by URL alone would hand one visitor's saved identity/
 * channels/tier to whichever other visitor's request happens to land on
 * the same cached entry. Confirmed as a real, not theoretical, risk on
 * this exact site (review feedback, PR #49): this page's own
 * persona-preview GET is already served from Cloudflare's cache across
 * visitors for 10 minutes on influencerhq.co.
 *
 * @param WP_REST_Response $response
 * @return WP_REST_Response The same response, for chaining.
 */
function ihq_aicoach_progress_no_store( WP_REST_Response $response ) {
	$response->header( 'Cache-Control', 'no-store, private' );
	return $response;
}

/**
 * GET /ihq/v1/aicoach/progress
 *
 * @return WP_REST_Response
 */
function ihq_aicoach_progress_handle_get() {
	$ref = ihq_aicoach_progress_get_ref();
	return ihq_aicoach_progress_no_store( new WP_REST_Response( ihq_aicoach_progress_load( $ref ), 200 ) );
}

/**
 * POST /ihq/v1/aicoach/progress
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function ihq_aicoach_progress_handle_post( WP_REST_Request $request ) {
	if ( ihq_aicoach_progress_rate_limited() ) {
		return ihq_aicoach_progress_no_store(
			new WP_REST_Response( array( 'error' => 'Too many requests. Please slow down.' ), 429 )
		);
	}

	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = array();
	}

	$ref     = ihq_aicoach_progress_get_ref();
	$partial = ihq_aicoach_progress_sanitize_partial( $params );
	// A request with nothing recognized sanitizes to an empty array — e.g. a
	// cookie-less/forged-cookie request carrying no real fields at all. Saving it
	// anyway would still call update_option() purely to stamp updated_at, creating
	// (or touching) a wp_options row for a "visitor" who sent nothing worth keeping.
	if ( array() === $partial ) {
		return ihq_aicoach_progress_no_store( new WP_REST_Response( ihq_aicoach_progress_load( $ref ), 200 ) );
	}
	$saved = ihq_aicoach_progress_save( $ref, $partial );

	return ihq_aicoach_progress_no_store( new WP_REST_Response( $saved, 200 ) );
}

/**
 * @return void
 */
function ihq_aicoach_progress_register_routes() {
	register_rest_route(
		'ihq/v1',
		'/aicoach/progress',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'ihq_aicoach_progress_handle_get',
				'permission_callback' => 'ihq_aicoach_progress_permission_check',
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'ihq_aicoach_progress_handle_post',
				'permission_callback' => 'ihq_aicoach_progress_permission_check',
			),
		)
	);
}
add_action( 'rest_api_init', 'ihq_aicoach_progress_register_routes' );

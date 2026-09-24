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

	if ( isset( $params['stage'] ) && is_string( $params['stage'] ) ) {
		$stage = sanitize_text_field( $params['stage'] );
		if ( $stage !== '' ) {
			$out['stage'] = $stage;
		}
	}

	if ( isset( $params['tier'] ) && in_array( (string) $params['tier'], array( '2', '5', '10' ), true ) ) {
		$out['tier'] = (string) $params['tier'];
	}

	if ( isset( $params['language'] ) && is_string( $params['language'] ) ) {
		$language = sanitize_key( $params['language'] );
		if ( $language !== '' ) {
			$out['language'] = $language;
		}
	}

	if ( isset( $params['identity'] ) && is_array( $params['identity'] ) ) {
		$out['identity'] = array(
			'firstName' => isset( $params['identity']['firstName'] ) ? sanitize_text_field( (string) $params['identity']['firstName'] ) : '',
			'lastName'  => isset( $params['identity']['lastName'] ) ? sanitize_text_field( (string) $params['identity']['lastName'] ) : '',
			'username'  => isset( $params['identity']['username'] ) ? sanitize_text_field( (string) $params['identity']['username'] ) : '',
		);
	}

	if ( isset( $params['channels'] ) && is_array( $params['channels'] ) ) {
		$allowed  = function_exists( 'ihq_aicoach_allowed_channel_keys' ) ? ihq_aicoach_allowed_channel_keys() : array();
		$channels = array();
		foreach ( $params['channels'] as $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['channel'] ) || ! isset( $entry['value'] ) ) {
				continue;
			}
			$channel = sanitize_key( (string) $entry['channel'] );
			if ( $allowed && ! in_array( $channel, $allowed, true ) ) {
				continue;
			}
			$channels[] = array(
				'channel' => $channel,
				'value'   => sanitize_text_field( (string) $entry['value'] ),
			);
		}
		$out['channels'] = $channels;
	}

	// FR-18 (PO-3109) isn't built yet — no appointment UI exists to populate this from.
	// Shape reserved so that story doesn't need a second progress-record migration.
	if ( isset( $params['appointment'] ) && is_array( $params['appointment'] ) ) {
		$out['appointment'] = array_map( 'sanitize_text_field', wp_array_slice_assoc( $params['appointment'], array( 'date', 'time', 'timezone' ) ) );
	}

	return $out;
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
 * GET /ihq/v1/aicoach/progress
 *
 * @return WP_REST_Response
 */
function ihq_aicoach_progress_handle_get() {
	$ref = ihq_aicoach_progress_get_ref();
	return new WP_REST_Response( ihq_aicoach_progress_load( $ref ), 200 );
}

/**
 * POST /ihq/v1/aicoach/progress
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function ihq_aicoach_progress_handle_post( WP_REST_Request $request ) {
	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = array();
	}

	$ref     = ihq_aicoach_progress_get_ref();
	$partial = ihq_aicoach_progress_sanitize_partial( $params );
	$saved   = ihq_aicoach_progress_save( $ref, $partial );

	return new WP_REST_Response( $saved, 200 );
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

<?php
/**
 * Ask Sami — Anam real-time avatar proxy (ENGR-5831)
 *
 * WordPress-side re-implementation of game-portal-client's dev-only
 * config/anamDevProxy.js. Mints a short-lived Anam session token server-side so
 * the secret API key NEVER reaches the browser bundle.
 *
 * INSTALL: this is a standalone include so it does NOT overwrite the theme's
 * existing functions.php. Upload it to the theme's inc/ folder, then add ONE
 * line to functions.php alongside the other inc/ requires (~line 418, next to
 * api-ajax-calls.php), matching the theme's existing convention:
 *
 *     require_once get_template_directory() . '/inc/anam-proxy.php';
 *
 * Endpoints (namespace /wp-json/anam/v1):
 *   POST /wp-json/anam/v1/session-token   -> { sessionToken }
 *   GET  /wp-json/anam/v1/persona-preview -> { name, portraitUrl }
 *
 * The API key is read from the ANAM_API_KEY constant. Define it in
 * wp-config.php (never commit it):
 *
 *     define( 'ANAM_API_KEY', 'sk-anam-xxxx' );
 *
 * Optionally override the persona with ANAM_PERSONA_ID.
 *
 * ⚠ UNLIKE the game-portal dev proxy (which only existed on the webpack dev
 * server and was inert in production), THESE ROUTES ARE LIVE IN PRODUCTION the
 * moment the theme is deployed. Token minting costs real Anam minutes, so the
 * permission_callback below is the throttle point — tighten it before this goes
 * anywhere public (see anam_hq_can_mint_token()).
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

if ( ! defined( 'ANAM_HQ_BASE_URL' ) ) {
	define( 'ANAM_HQ_BASE_URL', 'https://api.anam.ai/v1' );
}

/**
 * The "Sammy" Persona (ENGR-5831 / PO-3023) — the InfluencerHQ Executive Coach:
 * a custom avatar built from the new IHQ concierge photo, paired with the
 * "Amanda – Warm Guide" voice and the IHQ coaching system prompt. Edit her via
 * the Anam dashboard/API (PATCH /v1/personas/{id}), not here. (The original
 * game-portal "Sami" persona was 0918371b-2e5a-4071-89d1-35f01859187c.)
 */
if ( ! defined( 'ANAM_HQ_DEFAULT_PERSONA_ID' ) ) {
	define( 'ANAM_HQ_DEFAULT_PERSONA_ID', '32961d83-805c-404a-8c2e-5d29207968de' );
}

/**
 * Resolve the Anam API key, or null if it isn't configured.
 *
 * @return string|null
 */
function anam_hq_api_key() {
	if ( defined( 'ANAM_API_KEY' ) && ANAM_API_KEY ) {
		return ANAM_API_KEY;
	}
	$env = getenv( 'ANAM_API_KEY' );
	return $env ? $env : null;
}

/**
 * Resolve the persona id (constant override wins, else the default).
 *
 * @return string
 */
function anam_hq_persona_id() {
	if ( defined( 'ANAM_PERSONA_ID' ) && ANAM_PERSONA_ID ) {
		return ANAM_PERSONA_ID;
	}
	$env = getenv( 'ANAM_PERSONA_ID' );
	return $env ? $env : ANAM_HQ_DEFAULT_PERSONA_ID;
}

/**
 * Who is allowed to mint a session token / read the persona preview.
 *
 * POC default: open, but requires a valid WP REST nonce so the call has to
 * originate from a page this site actually rendered (the overlay passes the
 * nonce via an X-WP-Nonce header) rather than any random cross-origin caller.
 * This is NOT real auth — for a gated portal, swap in is_user_logged_in() or a
 * capability check, and/or add rate limiting.
 *
 * @param WP_REST_Request $request Incoming request.
 * @return bool
 */
function anam_hq_can_mint_token( WP_REST_Request $request ) {
	$nonce = $request->get_header( 'X-WP-Nonce' );
	return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
}

/**
 * POST /anam/v1/session-token — mint a short-lived Anam session token.
 *
 * @return WP_REST_Response
 */
function anam_hq_handle_session_token() {
	$api_key = anam_hq_api_key();
	if ( ! $api_key ) {
		return new WP_REST_Response(
			array( 'error' => 'ANAM_API_KEY is not set. Define it in wp-config.php.' ),
			501
		);
	}

	// personaConfig.personaId mints a "stateful" token that resolves the saved
	// Persona. A bare top-level { personaId } is the deprecated legacy path and
	// is now rejected by Anam at runtime.
	$response = wp_remote_post(
		ANAM_HQ_BASE_URL . '/auth/session-token',
		array(
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode(
				array( 'personaConfig' => array( 'personaId' => anam_hq_persona_id() ) )
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return new WP_REST_Response( array( 'error' => $response->get_error_message() ), 502 );
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( 200 !== (int) $code || empty( $data['sessionToken'] ) ) {
		return new WP_REST_Response(
			array(
				'error'  => sprintf( 'Anam session-token request failed (HTTP %d)', (int) $code ),
				'detail' => $body,
			),
			502
		);
	}

	return new WP_REST_Response( array( 'sessionToken' => $data['sessionToken'] ), 200 );
}

/**
 * GET /anam/v1/persona-preview — the static portrait + name for the idle face.
 * Purely cosmetic; failures are non-fatal for the overlay.
 *
 * @return WP_REST_Response
 */
function anam_hq_handle_persona_preview() {
	$api_key = anam_hq_api_key();
	if ( ! $api_key ) {
		return new WP_REST_Response( array( 'error' => 'ANAM_API_KEY is not set.' ), 501 );
	}

	$response = wp_remote_get(
		ANAM_HQ_BASE_URL . '/personas/' . anam_hq_persona_id(),
		array(
			'timeout' => 15,
			'headers' => array( 'Authorization' => 'Bearer ' . $api_key ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return new WP_REST_Response( array( 'error' => $response->get_error_message() ), 502 );
	}

	$code = wp_remote_retrieve_response_code( $response );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== (int) $code || ! is_array( $data ) ) {
		return new WP_REST_Response(
			array( 'error' => sprintf( 'Anam persona lookup failed (HTTP %d)', (int) $code ) ),
			502
		);
	}

	$portrait_url = null;
	if ( ! empty( $data['avatar'] ) && is_array( $data['avatar'] ) ) {
		$portrait_url = $data['avatar']['portraitImageUrl'] ?? ( $data['avatar']['imageUrl'] ?? null );
	}

	return new WP_REST_Response(
		array(
			'name'        => $data['name'] ?? 'Sami',
			'portraitUrl' => $portrait_url,
		),
		200
	);
}

/**
 * Register the Anam REST routes.
 */
function anam_hq_register_routes() {
	register_rest_route(
		'anam/v1',
		'/session-token',
		array(
			'methods'             => WP_REST_Server::CREATABLE, // POST
			'callback'            => 'anam_hq_handle_session_token',
			'permission_callback' => 'anam_hq_can_mint_token',
		)
	);

	register_rest_route(
		'anam/v1',
		'/persona-preview',
		array(
			'methods'             => WP_REST_Server::READABLE, // GET
			'callback'            => 'anam_hq_handle_persona_preview',
			'permission_callback' => 'anam_hq_can_mint_token',
		)
	);
}
add_action( 'rest_api_init', 'anam_hq_register_routes' );

/**
 * On the Portal POC page only, suppress the site-wide ElevenLabs "Executive
 * Concierge" FAB so it doesn't collide with the Anam "Sami" video avatar (both
 * are fixed bottom-right circular buttons). The concierge is enqueued at the
 * default priority in influencer_hq_scripts(); this runs later (priority 100)
 * and dequeues its handles for this template. Everywhere else the concierge is
 * untouched. This is the "replace on the POC page" decision (ENGR-5831).
 */
function anam_hq_suppress_concierge_on_poc() {
	if ( ! is_page_template( 'page-portal-poc.php' ) ) {
		return;
	}
	wp_dequeue_script( 'ihq-elevenlabs-concierge' );
	wp_dequeue_script( 'elevenlabs-client' );
	wp_dequeue_style( 'ihq-concierge-fab' );
}
add_action( 'wp_enqueue_scripts', 'anam_hq_suppress_concierge_on_poc', 100 );

/**
 * Enqueue the AI Coach screen-sequence script (PO-3092/PO-3093), page-home-
 * aicoach.php only. Split out of the template into js/aicoach-coach-flow.js
 * per review feedback that the template file was getting too large.
 *
 * The script is an ES module (uses `import`), so it's registered normally
 * and `type="module"` is added to its tag via the script_loader_tag filter
 * below — this theme has no first-class module-script enqueue API.
 */
function ihq_aicoach_enqueue_coach_flow() {
	if ( ! is_page_template( 'page-home-aicoach.php' ) ) {
		return;
	}

	$script_path = get_template_directory() . '/js/aicoach-coach-flow.js';
	$script_ver  = file_exists( $script_path ) ? (string) filemtime( $script_path ) : _S_VERSION;

	wp_register_script(
		'ihq-aicoach-coach-flow',
		get_template_directory_uri() . '/js/aicoach-coach-flow.js',
		array(),
		$script_ver,
		true
	);
	wp_localize_script(
		'ihq-aicoach-coach-flow',
		'AICOACH_SAMI',
		array(
			'restBase'         => esc_url_raw( rest_url( 'anam/v1' ) ),
			// FR-07 — username availability check. Namespace/route/response shape
			// (GET ?username=, expects { available: bool }) is this FE's assumption;
			// confirm with BE once they build the real endpoint (PO-3098 note).
			'identityRestBase' => esc_url_raw( rest_url( 'ihq/v1' ) ),
			'nonce'            => wp_create_nonce( 'wp_rest' ),
			'i18n'             => array(
				'usernameTaken'  => __( 'That username is already taken.', 'influencer-hq' ),
				'identitySaved'  => __( 'Saved', 'influencer-hq' ),
				'channelInvalid' => __( 'Please check this value and try again.', 'influencer-hq' ),
			),
		)
	);
	wp_enqueue_script( 'ihq-aicoach-coach-flow' );
}
add_action( 'wp_enqueue_scripts', 'ihq_aicoach_enqueue_coach_flow' );

/**
 * Add type="module" to ihq-aicoach-coach-flow's <script> tag so its `import`
 * statement works. Scoped to this one handle only.
 *
 * @param string $tag    The <script> tag WordPress generated.
 * @param string $handle The script's registered handle.
 * @return string
 */
function ihq_aicoach_module_script_tag( $tag, $handle ) {
	if ( 'ihq-aicoach-coach-flow' !== $handle ) {
		return $tag;
	}
	return str_replace( ' src=', ' type="module" src=', $tag );
}
add_filter( 'script_loader_tag', 'ihq_aicoach_module_script_tag', 10, 2 );

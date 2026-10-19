<?php
/**
 * Draft posts for onboarding screen 14, written by Sami through Gary (ENGR-7570, PO-3063 T8).
 *
 * Gary's copy-and-paste posting routes on Sami's box (spike ENGR-7562, confirmed by Gary on
 * 2026-10-18) write one post per platform; nothing is published and no social account is
 * connected: the Influencer copies each post and publishes it themselves.
 *
 * POST /wp-json/ihq/v1/onboarding/drafts — `{ platforms?, lang }`. Drafts for the given
 *      platforms (selected on screen 6, at most IHQ_PORTAL_ONBOARDING_DRAFTS_PER_REQUEST) or,
 *      when left out, for the selected platforms that have no draft yet. Proxies to
 *      POST /coach/v1/posting/draft.
 * POST /wp-json/ihq/v1/onboarding/drafts/revise — `{ platform, lang, instruction }` or
 *      `{ platform, lang, audio: { data, mime } }`. Rewrites that platform's stored draft as
 *      asked, typed or spoken. Proxies to POST /coach/v1/posting/revise; Gary transcribes a
 *      recording and the reply's `heard` is passed back.
 *
 * Both answer `{ drafts: { platform: text }, heard? }` with the drafts just written, which
 * are also stored (ihq_portal_onboarding_store_drafts()), so a returning Influencer and the
 * Post page (PO-3190) get them back. A failure answers `{ error: code }`, one of the
 * IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_* codes, and stores nothing.
 *
 * The routes need a logged-in Influencer and the wp_rest nonce. The Coach API key never
 * reaches the browser: requests are signed here by ihq_coach_request() (inc/gary-proxy.php).
 * Every attempt, failed ones included, costs one turn from the box's text budget shared with
 * the live coach (60,000 a day) and one of 300 draft attempts a day per key and influencer.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const IHQ_PORTAL_ONBOARDING_POSTING_DRAFT_PATH  = '/coach/v1/posting/draft';
const IHQ_PORTAL_ONBOARDING_POSTING_REVISE_PATH = '/coach/v1/posting/revise';

/** Gary takes 1 to 8 distinct platforms per request. */
const IHQ_PORTAL_ONBOARDING_DRAFTS_PER_REQUEST = 8;

/**
 * Seconds to wait for Gary. Writing up to eight posts takes far longer than the Coach API's
 * other calls (ihq_coach_request()'s default is 15). The browser gives up a little later.
 */
const IHQ_PORTAL_ONBOARDING_DRAFTS_TIMEOUT = 40;

/** Longest typed change request, in characters. A request, not a post. */
const IHQ_PORTAL_ONBOARDING_INSTRUCTION_MAX_LENGTH = 500;

/** Largest recording accepted, in bytes once decoded: about a minute of compressed speech. */
const IHQ_PORTAL_ONBOARDING_AUDIO_MAX_BYTES = 1048576;

/**
 * Recording types accepted, with the file extension sent to Gary. Gary's contract names
 * audio/webm (Chrome, Firefox, Android); Safari records audio/mp4.
 */
const IHQ_PORTAL_ONBOARDING_AUDIO_TYPES = array(
	'audio/webm' => 'webm',
	'audio/ogg'  => 'ogg',
	'audio/mp4'  => 'm4a',
);

/** What the browser is told when a draft could not be written; it shows a message for each. */
const IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_INVALID     = 'invalid';
const IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_REFUSED     = 'refused';
const IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_LIMIT       = 'limit';
const IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_UNAVAILABLE = 'unavailable';
const IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_FAILED      = 'failed';

/** HTTP status of each error code. */
const IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_STATUS = array(
	'invalid'     => 400,
	'refused'     => 422,
	'limit'       => 429,
	'unavailable' => 503,
	'failed'      => 502,
);

/**
 * Gary's HTTP statuses that carry a meaning for the Influencer (posting_refused,
 * posting_budget_exhausted, posting_failed). Every other failure, auth and replay errors
 * included, is shown as "unavailable": nothing the Influencer can change.
 */
const IHQ_PORTAL_ONBOARDING_GARY_STATUS_ERRORS = array(
	422 => 'refused',
	429 => 'limit',
	502 => 'failed',
);

/** Filter that sets what Sami is asked to write about: [ 'brief' => text, 'link' => url ]. */
const IHQ_PORTAL_ONBOARDING_DRAFT_BRIEF_FILTER = 'ihq_portal_onboarding_draft_brief';

/**
 * PLACEHOLDER until Product (Ivan, evaluation OQ6) gives the brief. Sami writes the post in the
 * request's language whatever the language of the brief.
 */
const IHQ_PORTAL_ONBOARDING_DRAFT_BRIEF_DEFAULT = 'Invite my followers to join me at Influencer HQ, where we play baccarat together and take part in live competitions. Friendly and upbeat, ready to post as it is.';

/** Prefix of the stand-in influencer id used when the account has no usable wp_influencer_guid. */
const IHQ_PORTAL_ONBOARDING_INFLUENCER_ID_PREFIX = 'wpuser_';

/**
 * What Sami is asked to write about, as one setting so that only text changes when Product
 * decides: the brief, and the link to put in the posts ('' for none). The link candidate is the
 * Influencer's referral link (ihq_fetch_referral_link_from_api(), inc/api-ajax-calls.php); it
 * costs a Genius API call with a token refresh, so it is only wired once Product confirms it.
 * A filtered value that is not a usable brief or an https URL falls back to the default.
 *
 * @param int $user_id
 * @return array{brief: string, link: string}
 */
function ihq_portal_onboarding_draft_brief( $user_id ) {
	$default  = array(
		'brief' => IHQ_PORTAL_ONBOARDING_DRAFT_BRIEF_DEFAULT,
		'link'  => '',
	);
	$filtered = apply_filters( IHQ_PORTAL_ONBOARDING_DRAFT_BRIEF_FILTER, $default, (int) $user_id );
	if ( ! is_array( $filtered ) ) {
		return $default;
	}
	$brief = isset( $filtered['brief'] ) && is_string( $filtered['brief'] ) ? trim( $filtered['brief'] ) : '';
	$link  = isset( $filtered['link'] ) && is_string( $filtered['link'] ) ? trim( $filtered['link'] ) : '';
	return array(
		'brief' => '' !== $brief ? $brief : $default['brief'],
		'link'  => ihq_env_is_https_url( $link ) ? $link : '',
	);
}

/**
 * The stable id Gary counts this Influencer's drafts against: the user meta wp_influencer_guid
 * (the id Braze knows them by). inc/braze-integration.php can store an email address there
 * (an existing Braze profile's external_id), and an account that never synced has none; both
 * get a pseudonymous id derived from the user id instead, so no email is sent to Gary.
 *
 * @param int $user_id
 * @return string
 */
function ihq_portal_onboarding_influencer_id( $user_id ) {
	$guid = trim( (string) get_user_meta( (int) $user_id, 'wp_influencer_guid', true ) );
	if ( '' !== $guid && false === strpos( $guid, '@' ) ) {
		return $guid;
	}
	return IHQ_PORTAL_ONBOARDING_INFLUENCER_ID_PREFIX . substr( hash_hmac( 'sha256', (string) (int) $user_id, wp_salt( 'auth' ) ), 0, 24 );
}

/**
 * permission_callback: the progress routes' check (logged in, wp_rest nonce), and the
 * Influencer role, since every call spends the shared Gary budget.
 *
 * @param WP_REST_Request $request
 * @return true|WP_Error
 */
function ihq_portal_onboarding_drafts_permission_check( WP_REST_Request $request ) {
	$allowed = ihq_portal_onboarding_permission_check( $request );
	if ( true !== $allowed ) {
		return $allowed;
	}
	if ( ! ihq_portal_onboarding_is_influencer( get_current_user_id() ) ) {
		return new WP_Error( 'ihq_onboarding_not_influencer', 'Only Influencers can draft posts.', array( 'status' => 403 ) );
	}
	return true;
}

/**
 * The language to write in: the requested one when it is one of the seven, else the saved
 * onboarding language, else English.
 *
 * @param mixed $requested
 * @param array $state
 * @return string
 */
function ihq_portal_onboarding_draft_language( $requested, array $state ) {
	$languages = ihq_portal_onboarding_languages();
	if ( is_string( $requested ) && in_array( $requested, $languages, true ) ) {
		return $requested;
	}
	if ( in_array( $state['language'], $languages, true ) ) {
		return $state['language'];
	}
	return 'en';
}

/**
 * A rejected request's result.
 *
 * @param string $message
 * @return array{platforms: string[], error: string}
 */
function ihq_portal_onboarding_draft_platforms_invalid( $message ) {
	return array(
		'platforms' => array(),
		'error'     => $message,
	);
}

/**
 * The platforms to draft for. Given ones (`platforms` in the body) must be a list of selected
 * platforms, each once, at most IHQ_PORTAL_ONBOARDING_DRAFTS_PER_REQUEST. Left out, the
 * selected platforms that have no draft yet, in screen order, up to that limit; possibly none.
 *
 * @param array $params The JSON body.
 * @param array $state
 * @return array{platforms: string[], error: string} `error` is empty when valid.
 */
function ihq_portal_onboarding_draft_platforms( array $params, array $state ) {
	if ( array() === $state['platforms'] ) {
		return ihq_portal_onboarding_draft_platforms_invalid( 'No platforms selected.' );
	}
	if ( ! array_key_exists( 'platforms', $params ) ) {
		$missing = array_values( array_diff( $state['platforms'], array_keys( $state['drafts'] ) ) );
		return array(
			'platforms' => array_slice( $missing, 0, IHQ_PORTAL_ONBOARDING_DRAFTS_PER_REQUEST ),
			'error'     => '',
		);
	}
	$requested = $params['platforms'];
	if ( ! is_array( $requested ) || array_values( $requested ) !== $requested || array() === $requested ) {
		return ihq_portal_onboarding_draft_platforms_invalid( 'Invalid platforms.' );
	}
	if ( count( $requested ) > IHQ_PORTAL_ONBOARDING_DRAFTS_PER_REQUEST ) {
		return ihq_portal_onboarding_draft_platforms_invalid( 'At most ' . IHQ_PORTAL_ONBOARDING_DRAFTS_PER_REQUEST . ' platforms per request.' );
	}
	foreach ( $requested as $platform ) {
		if ( ! is_string( $platform ) || ! in_array( $platform, $state['platforms'], true ) ) {
			return ihq_portal_onboarding_draft_platforms_invalid( 'Platform not selected.' );
		}
	}
	if ( count( array_unique( $requested ) ) !== count( $requested ) ) {
		return ihq_portal_onboarding_draft_platforms_invalid( 'Duplicate platform.' );
	}
	return array(
		'platforms' => $requested,
		'error'     => '',
	);
}

/**
 * A rejected change request's result.
 *
 * @param string $message
 * @return array{fields: array, error: string}
 */
function ihq_portal_onboarding_revision_invalid( $message ) {
	return array(
		'fields' => array(),
		'error'  => $message,
	);
}

/**
 * The change request as Gary's revise fields: exactly one of a typed `instruction` or a
 * recording (`audio`). Typed text is forwarded as written (it is never shown as HTML), only
 * checked for broken UTF-8 and length in characters. A recording must be strict base64 of an
 * accepted type, at most IHQ_PORTAL_ONBOARDING_AUDIO_MAX_BYTES once decoded.
 *
 * @param array $params The JSON body.
 * @return array{fields: array, error: string} `error` is empty when valid.
 */
function ihq_portal_onboarding_revision_fields( array $params ) {
	$has_text  = array_key_exists( 'instruction', $params );
	$has_audio = array_key_exists( 'audio', $params );
	if ( $has_text === $has_audio ) {
		return ihq_portal_onboarding_revision_invalid( 'Send an instruction or a recording, not both.' );
	}
	if ( $has_text ) {
		return ihq_portal_onboarding_typed_revision( $params['instruction'] );
	}
	return ihq_portal_onboarding_spoken_revision( $params['audio'] );
}

/**
 * @param mixed $instruction
 * @return array{fields: array, error: string}
 */
function ihq_portal_onboarding_typed_revision( $instruction ) {
	if ( ! is_string( $instruction ) ) {
		return ihq_portal_onboarding_revision_invalid( 'Invalid instruction.' );
	}
	$text = trim( wp_check_invalid_utf8( $instruction, true ) );
	if ( '' === $text || mb_strlen( $text ) > IHQ_PORTAL_ONBOARDING_INSTRUCTION_MAX_LENGTH ) {
		return ihq_portal_onboarding_revision_invalid( 'Invalid instruction.' );
	}
	return array(
		'fields' => array( 'instruction' => $text ),
		'error'  => '',
	);
}

/**
 * @param mixed $audio { data: base64, mime }.
 * @return array{fields: array, error: string}
 */
function ihq_portal_onboarding_spoken_revision( $audio ) {
	if ( ! is_array( $audio ) || ! isset( $audio['data'], $audio['mime'] ) || ! is_string( $audio['data'] ) || ! is_string( $audio['mime'] ) ) {
		return ihq_portal_onboarding_revision_invalid( 'Invalid recording.' );
	}
	// MediaRecorder reports a codec too ("audio/webm;codecs=opus"); the type is what is checked.
	$mime = strtolower( trim( explode( ';', $audio['mime'], 2 )[0] ) );
	if ( ! isset( IHQ_PORTAL_ONBOARDING_AUDIO_TYPES[ $mime ] ) ) {
		return ihq_portal_onboarding_revision_invalid( 'Unsupported recording type.' );
	}
	$bytes = base64_decode( $audio['data'], true );
	if ( false === $bytes || '' === $bytes || strlen( $bytes ) > IHQ_PORTAL_ONBOARDING_AUDIO_MAX_BYTES ) {
		return ihq_portal_onboarding_revision_invalid( 'Invalid recording.' );
	}
	return array(
		'fields' => array(
			'audio' => array(
				'data'     => base64_encode( $bytes ),
				'mime'     => $mime,
				'filename' => 'instruction.' . IHQ_PORTAL_ONBOARDING_AUDIO_TYPES[ $mime ],
			),
		),
		'error'  => '',
	);
}

/**
 * Gary's error code from an error body, for the log: `{ error: "code" }`,
 * `{ error: { code } }` or `{ code }`. '' when there is none.
 *
 * @param array $body
 * @return string
 */
function ihq_portal_onboarding_gary_error_code( array $body ) {
	$error = isset( $body['error'] ) ? $body['error'] : null;
	if ( is_array( $error ) && isset( $error['code'] ) ) {
		$error = $error['code'];
	}
	if ( null === $error && isset( $body['code'] ) ) {
		$error = $body['code'];
	}
	return is_string( $error ) ? sanitize_key( $error ) : '';
}

/**
 * Logs a failed Gary call: the route, the status and Gary's error code, never the posts or the
 * Influencer's words.
 *
 * @param string $path
 * @param string $detail
 * @return void
 */
function ihq_portal_onboarding_log_draft_failure( $path, $detail ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a failed paid call must be visible in the server log.
	error_log( 'IHQ onboarding drafts: ' . $path . ' failed: ' . $detail );
}

/**
 * The posts of a Gary reply that can be stored: for a requested platform, each once, with text
 * the store accepts. Anything else in the reply is dropped.
 *
 * @param array    $body
 * @param string[] $platforms The platforms asked for.
 * @return array<string, string> platform => text.
 */
function ihq_portal_onboarding_reply_posts( array $body, array $platforms ) {
	$posts = isset( $body['posts'] ) && ihq_coach_is_json_list( $body['posts'] ) ? $body['posts'] : array();
	$kept  = array();
	foreach ( $posts as $post ) {
		if ( ! is_array( $post ) || ! isset( $post['platform'], $post['text'] ) || ! is_string( $post['platform'] ) ) {
			continue;
		}
		$platform = $post['platform'];
		if ( ! in_array( $platform, $platforms, true ) || isset( $kept[ $platform ] ) ) {
			continue;
		}
		$checked = ihq_portal_onboarding_validate_drafts( array( $platform => $post['text'] ) );
		if ( '' === $checked['error'] ) {
			$kept[ $platform ] = $checked['value'][ $platform ];
		}
	}
	return $kept;
}

/**
 * A failed call's result.
 *
 * @param string $error One of the IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_* codes.
 * @return array{posts: array, heard: string, error: string}
 */
function ihq_portal_onboarding_posting_failure( $error ) {
	return array(
		'posts' => array(),
		'heard' => '',
		'error' => $error,
	);
}

/**
 * Sends one posting request to Gary and reads the posts back.
 *
 * @param string   $path      IHQ_PORTAL_ONBOARDING_POSTING_DRAFT_PATH or _REVISE_PATH.
 * @param array    $payload   The request body.
 * @param string[] $platforms The platforms the reply must cover (at least one of).
 * @return array{posts: array<string, string>, heard: string, error: string} `error` is empty on success.
 */
function ihq_portal_onboarding_posting_call( $path, array $payload, array $platforms ) {
	$result = ihq_coach_request( 'POST', $path, $payload, array(), IHQ_PORTAL_ONBOARDING_DRAFTS_TIMEOUT );
	if ( is_wp_error( $result ) ) {
		ihq_portal_onboarding_log_draft_failure( $path, $result->get_error_code() );
		return ihq_portal_onboarding_posting_failure( IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_UNAVAILABLE );
	}

	$status = (int) $result['status'];
	$body   = $result['body'];
	if ( $status < 200 || $status >= 300 ) {
		ihq_portal_onboarding_log_draft_failure( $path, $status . ' ' . ihq_portal_onboarding_gary_error_code( $body ) );
		$error = IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_UNAVAILABLE;
		if ( isset( IHQ_PORTAL_ONBOARDING_GARY_STATUS_ERRORS[ $status ] ) ) {
			$error = IHQ_PORTAL_ONBOARDING_GARY_STATUS_ERRORS[ $status ];
		}
		return ihq_portal_onboarding_posting_failure( $error );
	}

	$posts = ihq_portal_onboarding_reply_posts( $body, $platforms );
	if ( array() === $posts ) {
		ihq_portal_onboarding_log_draft_failure( $path, $status . ' reply without usable posts' );
		return ihq_portal_onboarding_posting_failure( IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_FAILED );
	}
	return array(
		'posts' => $posts,
		'heard' => isset( $body['heard'] ) && is_string( $body['heard'] ) ? $body['heard'] : '',
		'error' => '',
	);
}

/**
 * An error answer for the browser.
 *
 * @param string $error   One of the IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_* codes.
 * @param string $message For an invalid request: what was wrong.
 * @return WP_REST_Response
 */
function ihq_portal_onboarding_drafts_error( $error, $message = '' ) {
	$body = array( 'error' => $error );
	if ( '' !== $message ) {
		$body['message'] = $message;
	}
	return ihq_portal_onboarding_response( $body, IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_STATUS[ $error ] );
}

/**
 * Stores what Gary wrote and answers with it.
 *
 * @param int   $user_id
 * @param array $call From ihq_portal_onboarding_posting_call(), successful.
 * @return WP_REST_Response
 */
function ihq_portal_onboarding_drafts_success( $user_id, array $call ) {
	// The platform may have been unticked on another device while Gary was writing.
	$stored = ihq_portal_onboarding_store_drafts( $user_id, $call['posts'] );
	if ( is_wp_error( $stored ) ) {
		return ihq_portal_onboarding_drafts_error( IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_FAILED );
	}
	$body = array( 'drafts' => (object) $call['posts'] );
	if ( '' !== $call['heard'] ) {
		$body['heard'] = $call['heard'];
	}
	return ihq_portal_onboarding_response( $body );
}

/**
 * The JSON body, or an empty array when there is none.
 *
 * @param WP_REST_Request $request
 * @return array
 */
function ihq_portal_onboarding_drafts_params( WP_REST_Request $request ) {
	$params = $request->get_json_params();
	return is_array( $params ) ? $params : array();
}

/**
 * POST /ihq/v1/onboarding/drafts
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function ihq_portal_onboarding_handle_drafts( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	$params  = ihq_portal_onboarding_drafts_params( $request );
	$state   = ihq_portal_onboarding_get_state( $user_id );

	$chosen  = ihq_portal_onboarding_draft_platforms( $params, $state );
	if ( '' !== $chosen['error'] ) {
		return ihq_portal_onboarding_drafts_error( IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_INVALID, $chosen['error'] );
	}
	if ( array() === $chosen['platforms'] ) {
		return ihq_portal_onboarding_response( array( 'drafts' => (object) array() ) );
	}

	$brief   = ihq_portal_onboarding_draft_brief( $user_id );
	$payload = array(
		'influencer_id' => ihq_portal_onboarding_influencer_id( $user_id ),
		'lang'          => ihq_portal_onboarding_draft_language( $params['lang'] ?? null, $state ),
		'platforms'     => $chosen['platforms'],
		'brief'         => $brief['brief'],
	);
	if ( '' !== $brief['link'] ) {
		$payload['link'] = $brief['link'];
	}

	$call = ihq_portal_onboarding_posting_call( IHQ_PORTAL_ONBOARDING_POSTING_DRAFT_PATH, $payload, $chosen['platforms'] );
	if ( '' !== $call['error'] ) {
		return ihq_portal_onboarding_drafts_error( $call['error'] );
	}
	return ihq_portal_onboarding_drafts_success( $user_id, $call );
}

/**
 * POST /ihq/v1/onboarding/drafts/revise
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function ihq_portal_onboarding_handle_revise( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	$params  = ihq_portal_onboarding_drafts_params( $request );
	$state   = ihq_portal_onboarding_get_state( $user_id );

	$platform = $params['platform'] ?? null;
	// The previous text is the stored draft, never the browser's copy of it.
	if ( ! is_string( $platform ) || ! isset( $state['drafts'][ $platform ] ) ) {
		return ihq_portal_onboarding_drafts_error( IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_INVALID, 'No draft for that platform.' );
	}
	$revision = ihq_portal_onboarding_revision_fields( $params );
	if ( '' !== $revision['error'] ) {
		return ihq_portal_onboarding_drafts_error( IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_INVALID, $revision['error'] );
	}

	$payload = array_merge(
		array(
			'influencer_id' => ihq_portal_onboarding_influencer_id( $user_id ),
			'lang'          => ihq_portal_onboarding_draft_language( $params['lang'] ?? null, $state ),
			'posts'         => array(
				array(
					'platform' => $platform,
					'text'     => $state['drafts'][ $platform ],
				),
			),
		),
		$revision['fields']
	);

	$call = ihq_portal_onboarding_posting_call( IHQ_PORTAL_ONBOARDING_POSTING_REVISE_PATH, $payload, array( $platform ) );
	if ( '' !== $call['error'] ) {
		return ihq_portal_onboarding_drafts_error( $call['error'] );
	}
	return ihq_portal_onboarding_drafts_success( $user_id, $call );
}

/**
 * Register POST /ihq/v1/onboarding/drafts and /drafts/revise.
 *
 * @return void
 */
function ihq_portal_onboarding_register_drafts_routes() {
	register_rest_route(
		'ihq/v1',
		'/onboarding/drafts',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'ihq_portal_onboarding_handle_drafts',
			'permission_callback' => 'ihq_portal_onboarding_drafts_permission_check',
		)
	);
	register_rest_route(
		'ihq/v1',
		'/onboarding/drafts/revise',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'ihq_portal_onboarding_handle_revise',
			'permission_callback' => 'ihq_portal_onboarding_drafts_permission_check',
		)
	);
}
add_action( 'rest_api_init', 'ihq_portal_onboarding_register_drafts_routes' );

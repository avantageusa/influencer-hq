<?php
/**
 * Portal onboarding progress, stored on the Influencer's account (ENGR-7563, PO-3063 T1).
 *
 * Modelled on inc/aicoach-progress.php (the landing flow's progress store), with two
 * differences the epic asks for (evaluation OQ8): the record belongs to the logged-in
 * WordPress user, not to a cookie, and it never expires. It is user meta, so it is the
 * same from any browser or device and survives logout/login.
 *
 * GET  /wp-json/ihq/v1/onboarding/progress — the current user's state (an empty default
 *      state when nothing has been saved).
 * POST /wp-json/ihq/v1/onboarding/progress — merge a partial update. Every key and every
 *      value is checked against a fixed allowlist first; one bad key or value rejects the
 *      whole request with 400 and nothing is stored. A handle is only accepted for a
 *      selected platform (posted in the same request or already stored), so send
 *      `platforms` with or before `handles`.
 *
 * Both routes need a logged-in user (401 otherwise) and the wp_rest nonce.
 *
 * Other tasks of the epic use the PHP API below rather than the meta keys:
 * ihq_portal_onboarding_get_state(), ihq_portal_onboarding_merge(),
 * ihq_portal_onboarding_mark_complete(), ihq_portal_onboarding_is_complete(). merge()
 * applies the same allowlist as the POST route. Every stored change fires
 * IHQ_PORTAL_ONBOARDING_SAVED_ACTION and completion fires
 * IHQ_PORTAL_ONBOARDING_COMPLETED_ACTION, so the Braze sync (ENGR-7573) can listen
 * without this file knowing about Braze.
 *
 * Stored values are snake_case enums (evaluation EQ1). They are shared with the screens
 * (js/aicoach/onboarding-*.js) and the Braze attribute values, so treat them as a
 * contract: add values, never rename one.
 *
 * Drafts (screen 14) are not stored yet: that task (ENGR-7570) is on hold until Gary's
 * drafting spike is answered, and its field is added with it.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** User meta key holding the onboarding record: everything except completion. */
const IHQ_PORTAL_ONBOARDING_META_KEY = '_ihq_portal_onboarding';

/**
 * User meta key holding the completion time (ISO-8601 UTC), written only by
 * ihq_portal_onboarding_mark_complete(). It is kept apart from the record so a save that
 * read the record before completion can never write `completed => false` back over it.
 */
const IHQ_PORTAL_ONBOARDING_COMPLETED_META_KEY = '_ihq_portal_onboarding_completed_at';

/** The state fields that come from IHQ_PORTAL_ONBOARDING_COMPLETED_META_KEY, never from the record. */
const IHQ_PORTAL_ONBOARDING_COMPLETION_FIELDS = array( 'completed', 'completed_at' );

/** Fires after an onboarding update is stored: ( int $user_id, array $partial, array $state ). */
const IHQ_PORTAL_ONBOARDING_SAVED_ACTION = 'ihq_portal_onboarding_saved';

/** Fires once, when onboarding is marked complete: ( int $user_id, array $state ). */
const IHQ_PORTAL_ONBOARDING_COMPLETED_ACTION = 'ihq_portal_onboarding_completed';

/** WP_Error code ihq_portal_onboarding_merge() returns for an update it rejects. */
const IHQ_PORTAL_ONBOARDING_INVALID_ERROR = 'ihq_onboarding_invalid';

/** HTTP status of a rejected update. */
const IHQ_PORTAL_ONBOARDING_INVALID_STATUS = 400;

/**
 * The AI Coach language preference the registration flow already writes
 * (inc/email-verification-handler.php) and Braze reads as language_preference. The
 * onboarding language is saved there too, so the next visit opens in it (ENGR-7572).
 */
const IHQ_PORTAL_ONBOARDING_LANGUAGE_META_KEY = '_ihq_aicoach_language';

/** Longest handle accepted for one platform, in characters. Sized like the landing flow's form fields. */
const IHQ_PORTAL_ONBOARDING_HANDLE_MAX_LENGTH = 190;

/** The keys a POST may carry. `completed` is not one of them: only the server marks completion. */
const IHQ_PORTAL_ONBOARDING_WRITABLE_KEYS = array( 'stage', 'answers', 'platforms', 'handles', 'language' );

/**
 * The onboarding screens in order (PO-3063 evaluation, T4). The stage is one of these.
 *
 * @return string[]
 */
function ihq_portal_onboarding_stages() {
	return array(
		'game_of_kings',
		'modern_era',
		'baccarat_experience',
		'streaming_experience',
		'equity_knowledge',
		'social_platforms',
		'world_championship',
		'world_tour',
		'classic',
		'equity',
		'how_this_works',
		'active_followers',
		'equity_example',
		'draft_posts',
		'post_action',
		'closing',
	);
}

/**
 * Every answer field and the values it accepts (evaluation EQ1, OQ16). The sub-option
 * fields (streaming_learning_intent, equity_management) are only shown for one parent
 * answer; their values carry the parent in the name so a value alone is unambiguous.
 *
 * @return array<string, string[]>
 */
function ihq_portal_onboarding_answer_values() {
	return array(
		'baccarat_experience'       => array( 'played_a_lot', 'played_some', 'new_player' ),
		'streaming_experience'      => array( 'streamed_a_lot', 'streamed_some', 'new_to_streaming' ),
		'streaming_learning_intent' => array( 'new_ready_to_learn', 'new_maybe_later' ),
		'equity_knowledge'          => array( 'owns_equity', 'understands_equity', 'interested_in_equity' ),
		'equity_management'         => array( 'owns_self_managed', 'owns_managed_by_other' ),
		'active_follower_count'     => array( '100k_plus', '50k_plus', '25k_plus', '10k_plus', 'under_10k' ),
		'post_action_choice'        => array( 'post_now', 'edit_together', 'edit_later' ),
	);
}

/**
 * The 19 social platforms of screen 6 (PO-3207, evaluation OQ15), as the keys the
 * Profile page already uses in _ihq_social_handles where a platform exists there.
 *
 * @return string[]
 */
function ihq_portal_onboarding_platforms() {
	return array(
		'instagram',
		'facebook',
		'tiktok',
		'youtube',
		'bilibili',
		'douyin',
		'kakaotalk',
		'line',
		'linkedin',
		'pinterest',
		'qq',
		'reddit',
		'rednote',
		'snapchat',
		'telegram',
		'wechat',
		'weibo',
		'whatsapp',
		'x',
	);
}

/**
 * The seven onboarding languages; the same codes as js/aicoach/locales.js.
 *
 * @return string[]
 */
function ihq_portal_onboarding_languages() {
	return array( 'en', 'zh', 'yue', 'ja', 'ko', 'th', 'vi' );
}

/**
 * The state of a user who has saved nothing.
 *
 * @return array
 */
function ihq_portal_onboarding_default_state() {
	return array(
		'stage'        => '',
		'answers'      => array(),
		'platforms'    => array(),
		'handles'      => array(),
		'language'     => '',
		'completed'    => false,
		'completed_at' => '',
	);
}

/**
 * The part of a state stored in the record: the state without its completion fields.
 *
 * @param array $state
 * @return array
 */
function ihq_portal_onboarding_record( array $state ) {
	return array_diff_key( $state, array_flip( IHQ_PORTAL_ONBOARDING_COMPLETION_FIELDS ) );
}

/**
 * A user's onboarding state, always with every field present. Completion comes from its
 * own meta key; a `completed` or `completed_at` left in the record is ignored.
 *
 * @param int $user_id
 * @return array
 */
function ihq_portal_onboarding_get_state( $user_id ) {
	$user_id = (int) $user_id;
	$stored  = get_user_meta( $user_id, IHQ_PORTAL_ONBOARDING_META_KEY, true );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	$defaults = ihq_portal_onboarding_default_state();
	$state    = array_merge( $defaults, array_intersect_key( $stored, ihq_portal_onboarding_record( $defaults ) ) );

	$completed_at = get_user_meta( $user_id, IHQ_PORTAL_ONBOARDING_COMPLETED_META_KEY, true );
	if ( is_string( $completed_at ) && '' !== $completed_at ) {
		$state['completed']    = true;
		$state['completed_at'] = $completed_at;
	}
	return $state;
}

/**
 * Whether the user has finished onboarding.
 *
 * @param int $user_id
 * @return bool
 */
function ihq_portal_onboarding_is_complete( $user_id ) {
	$state = ihq_portal_onboarding_get_state( $user_id );
	return true === $state['completed'];
}

/**
 * Check a partial update against the allowlist.
 *
 * @param mixed $params Decoded JSON body.
 * @return array{partial: array, error: string} `error` is empty when the update is valid.
 */
function ihq_portal_onboarding_validate_partial( $params ) {
	if ( ! is_array( $params ) || array() === $params ) {
		return ihq_portal_onboarding_invalid( 'Nothing to save.' );
	}

	$unknown = array_diff( array_keys( $params ), IHQ_PORTAL_ONBOARDING_WRITABLE_KEYS );
	if ( array() !== $unknown ) {
		return ihq_portal_onboarding_invalid( 'Unknown field: ' . implode( ', ', array_map( 'strval', $unknown ) ) . '.' );
	}

	$validators = array(
		'stage'     => 'ihq_portal_onboarding_validate_stage',
		'answers'   => 'ihq_portal_onboarding_validate_answers',
		'platforms' => 'ihq_portal_onboarding_validate_platforms',
		'handles'   => 'ihq_portal_onboarding_validate_handles',
		'language'  => 'ihq_portal_onboarding_validate_language',
	);

	$partial = array();
	foreach ( $params as $key => $value ) {
		$result = call_user_func( $validators[ $key ], $value );
		if ( '' !== $result['error'] ) {
			return ihq_portal_onboarding_invalid( $result['error'] );
		}
		$partial[ $key ] = $result['value'];
	}
	return array(
		'partial' => $partial,
		'error'   => '',
	);
}

/**
 * The validation result for a rejected update.
 *
 * @param string $message
 * @return array{partial: array, error: string}
 */
function ihq_portal_onboarding_invalid( $message ) {
	return array(
		'partial' => array(),
		'error'   => $message,
	);
}

/**
 * The validation result for one field.
 *
 * @param mixed  $value
 * @param string $error Empty when valid.
 * @return array{value: mixed, error: string}
 */
function ihq_portal_onboarding_field_result( $value, $error = '' ) {
	return array(
		'value' => $value,
		'error' => $error,
	);
}

/**
 * The stage: one of ihq_portal_onboarding_stages().
 *
 * @param mixed $value
 * @return array{value: mixed, error: string}
 */
function ihq_portal_onboarding_validate_stage( $value ) {
	if ( ! is_string( $value ) || ! in_array( $value, ihq_portal_onboarding_stages(), true ) ) {
		return ihq_portal_onboarding_field_result( null, 'Invalid stage.' );
	}
	return ihq_portal_onboarding_field_result( $value );
}

/**
 * Answers merge one by one; null clears an answer (a sub-option hidden again when its
 * parent answer changes).
 *
 * @param mixed $value
 * @return array{value: mixed, error: string}
 */
function ihq_portal_onboarding_validate_answers( $value ) {
	if ( ! is_array( $value ) || array() === $value ) {
		return ihq_portal_onboarding_field_result( null, 'Invalid answers.' );
	}
	$allowed = ihq_portal_onboarding_answer_values();
	foreach ( $value as $field => $answer ) {
		if ( ! is_string( $field ) || ! isset( $allowed[ $field ] ) ) {
			return ihq_portal_onboarding_field_result( null, 'Unknown answer: ' . $field . '.' );
		}
		if ( null !== $answer && ! in_array( $answer, $allowed[ $field ], true ) ) {
			return ihq_portal_onboarding_field_result( null, 'Invalid value for ' . $field . '.' );
		}
	}
	return ihq_portal_onboarding_field_result( $value );
}

/**
 * The selected platforms: a list of known platform keys, each at most once.
 *
 * @param mixed $value
 * @return array{value: mixed, error: string}
 */
function ihq_portal_onboarding_validate_platforms( $value ) {
	if ( ! is_array( $value ) || array_values( $value ) !== $value ) {
		return ihq_portal_onboarding_field_result( null, 'Invalid platforms.' );
	}
	foreach ( $value as $platform ) {
		if ( ! is_string( $platform ) || ! in_array( $platform, ihq_portal_onboarding_platforms(), true ) ) {
			return ihq_portal_onboarding_field_result( null, 'Unknown platform.' );
		}
	}
	if ( count( array_unique( $value ) ) !== count( $value ) ) {
		return ihq_portal_onboarding_field_result( null, 'Duplicate platform.' );
	}
	return ihq_portal_onboarding_field_result( $value );
}

/**
 * Handles by platform key. A handle is free text, so it is sanitized and must be
 * non-empty and at most IHQ_PORTAL_ONBOARDING_HANDLE_MAX_LENGTH characters.
 *
 * @param mixed $value
 * @return array{value: mixed, error: string}
 */
function ihq_portal_onboarding_validate_handles( $value ) {
	if ( ! is_array( $value ) ) {
		return ihq_portal_onboarding_field_result( null, 'Invalid handles.' );
	}
	$handles = array();
	foreach ( $value as $platform => $handle ) {
		if ( ! is_string( $platform ) || ! in_array( $platform, ihq_portal_onboarding_platforms(), true ) ) {
			return ihq_portal_onboarding_field_result( null, 'Unknown platform.' );
		}
		if ( ! is_string( $handle ) ) {
			return ihq_portal_onboarding_field_result( null, 'Invalid handle for ' . $platform . '.' );
		}
		$clean = sanitize_text_field( $handle );
		if ( '' === $clean || mb_strlen( $clean ) > IHQ_PORTAL_ONBOARDING_HANDLE_MAX_LENGTH ) {
			return ihq_portal_onboarding_field_result( null, 'Invalid handle for ' . $platform . '.' );
		}
		$handles[ $platform ] = $clean;
	}
	return ihq_portal_onboarding_field_result( $handles );
}

/**
 * The language: one of ihq_portal_onboarding_languages().
 *
 * @param mixed $value
 * @return array{value: mixed, error: string}
 */
function ihq_portal_onboarding_validate_language( $value ) {
	if ( ! is_string( $value ) || ! in_array( $value, ihq_portal_onboarding_languages(), true ) ) {
		return ihq_portal_onboarding_field_result( null, 'Invalid language.' );
	}
	return ihq_portal_onboarding_field_result( $value );
}

/**
 * Apply a validated partial update to a state and return the new state. Pure.
 *
 * Answers merge per field (null removes one). Platforms replace the list. Handles replace
 * the map. Either way only handles of selected platforms are kept, so unticking a platform
 * drops its handle.
 *
 * @param array $state   A full state from ihq_portal_onboarding_get_state().
 * @param array $partial A partial from ihq_portal_onboarding_validate_partial().
 * @return array
 */
function ihq_portal_onboarding_apply( array $state, array $partial ) {
	$next = $state;
	foreach ( array( 'stage', 'platforms', 'handles', 'language' ) as $key ) {
		if ( array_key_exists( $key, $partial ) ) {
			$next[ $key ] = $partial[ $key ];
		}
	}
	if ( isset( $partial['answers'] ) ) {
		$answers = $state['answers'];
		foreach ( $partial['answers'] as $field => $answer ) {
			if ( null === $answer ) {
				unset( $answers[ $field ] );
				continue;
			}
			$answers[ $field ] = $answer;
		}
		$next['answers'] = $answers;
	}
	$next['handles'] = array_intersect_key( $next['handles'], array_flip( $next['platforms'] ) );
	return $next;
}

/**
 * The platforms of posted handles that are not selected once the update is applied
 * (the posted platforms, else the stored ones). Such a handle would be dropped, so the
 * update is rejected instead of losing it silently: send platforms with or before handles.
 *
 * @param array $state   A full state from ihq_portal_onboarding_get_state().
 * @param array $partial A validated partial.
 * @return string[]
 */
function ihq_portal_onboarding_unselected_handles( array $state, array $partial ) {
	if ( ! isset( $partial['handles'] ) ) {
		return array();
	}
	$platforms = $state['platforms'];
	if ( isset( $partial['platforms'] ) ) {
		$platforms = $partial['platforms'];
	}
	return array_values( array_diff( array_keys( $partial['handles'] ), $platforms ) );
}

/**
 * The WP_Error for a rejected update.
 *
 * @param string $message
 * @return WP_Error
 */
function ihq_portal_onboarding_invalid_error( $message ) {
	return new WP_Error( IHQ_PORTAL_ONBOARDING_INVALID_ERROR, $message, array( 'status' => IHQ_PORTAL_ONBOARDING_INVALID_STATUS ) );
}

/**
 * Check a partial update against the allowlist, merge it into the user's record and
 * store it. This is the same check the POST route makes: one bad key or value, or a
 * handle for a platform that is not selected, rejects the whole update and nothing is
 * stored or fired.
 *
 * @param int   $user_id
 * @param mixed $partial An update in the POST body's shape.
 * @return array|WP_Error The full state after the merge, or a WP_Error (status 400).
 */
function ihq_portal_onboarding_merge( $user_id, $partial ) {
	$user_id = (int) $user_id;
	$result  = ihq_portal_onboarding_validate_partial( $partial );
	if ( '' !== $result['error'] ) {
		return ihq_portal_onboarding_invalid_error( $result['error'] );
	}
	$partial = $result['partial'];
	$current = ihq_portal_onboarding_get_state( $user_id );

	$unselected = ihq_portal_onboarding_unselected_handles( $current, $partial );
	if ( array() !== $unselected ) {
		return ihq_portal_onboarding_invalid_error( 'Handle for a platform that is not selected: ' . implode( ', ', $unselected ) . '.' );
	}

	$state = ihq_portal_onboarding_apply( $current, $partial );
	update_user_meta( $user_id, IHQ_PORTAL_ONBOARDING_META_KEY, ihq_portal_onboarding_record( $state ) );
	if ( isset( $partial['language'] ) ) {
		update_user_meta( $user_id, IHQ_PORTAL_ONBOARDING_LANGUAGE_META_KEY, $partial['language'] );
	}

	/**
	 * Fires after an onboarding update is stored.
	 *
	 * @param int   $user_id
	 * @param array $partial The validated update, as sent.
	 * @param array $state   The full state after the merge.
	 */
	do_action( IHQ_PORTAL_ONBOARDING_SAVED_ACTION, $user_id, $partial, $state );
	return $state;
}

/**
 * Mark the user's onboarding complete. Completing twice keeps the first timestamp and
 * fires the action only once. Only the completion meta key is written, so the record and
 * any save in flight are left alone.
 *
 * @param int $user_id
 * @return array The full state.
 */
function ihq_portal_onboarding_mark_complete( $user_id ) {
	$user_id = (int) $user_id;
	$state   = ihq_portal_onboarding_get_state( $user_id );
	if ( true === $state['completed'] ) {
		return $state;
	}
	$state['completed']    = true;
	$state['completed_at'] = gmdate( 'c' );
	update_user_meta( $user_id, IHQ_PORTAL_ONBOARDING_COMPLETED_META_KEY, $state['completed_at'] );

	/**
	 * Fires once, when a user's onboarding is marked complete.
	 *
	 * @param int   $user_id
	 * @param array $state
	 */
	do_action( IHQ_PORTAL_ONBOARDING_COMPLETED_ACTION, $user_id, $state );
	return $state;
}

/**
 * The state as JSON: answers and handles as objects even when empty, so the browser
 * always gets `{}` and never `[]` for them.
 *
 * @param array $state
 * @return array
 */
function ihq_portal_onboarding_public_state( array $state ) {
	$state['answers'] = (object) $state['answers'];
	$state['handles'] = (object) $state['handles'];
	return $state;
}

/**
 * permission_callback for both routes: a logged-in user with a valid wp_rest nonce.
 *
 * @param WP_REST_Request $request
 * @return true|WP_Error
 */
function ihq_portal_onboarding_permission_check( WP_REST_Request $request ) {
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'ihq_onboarding_logged_out', 'You must be logged in.', array( 'status' => 401 ) );
	}
	if ( ! wp_verify_nonce( (string) $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
		return new WP_Error( 'ihq_onboarding_bad_nonce', 'Invalid nonce.', array( 'status' => 403 ) );
	}
	return true;
}

/**
 * Per-user data: never cached by a CDN or the browser (see ihq_aicoach_progress_no_store()).
 *
 * @param array $body
 * @param int   $status
 * @return WP_REST_Response
 */
function ihq_portal_onboarding_response( array $body, $status = 200 ) {
	$response = new WP_REST_Response( $body, $status );
	$response->header( 'Cache-Control', 'no-store, private' );
	return $response;
}

/**
 * GET /ihq/v1/onboarding/progress
 *
 * @return WP_REST_Response
 */
function ihq_portal_onboarding_handle_get() {
	return ihq_portal_onboarding_response( ihq_portal_onboarding_public_state( ihq_portal_onboarding_get_state( get_current_user_id() ) ) );
}

/**
 * POST /ihq/v1/onboarding/progress
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function ihq_portal_onboarding_handle_post( WP_REST_Request $request ) {
	$state = ihq_portal_onboarding_merge( get_current_user_id(), $request->get_json_params() );
	if ( is_wp_error( $state ) ) {
		return ihq_portal_onboarding_response( array( 'error' => $state->get_error_message() ), IHQ_PORTAL_ONBOARDING_INVALID_STATUS );
	}
	return ihq_portal_onboarding_response( ihq_portal_onboarding_public_state( $state ) );
}

/**
 * Register GET and POST /ihq/v1/onboarding/progress.
 *
 * @return void
 */
function ihq_portal_onboarding_register_progress_routes() {
	register_rest_route(
		'ihq/v1',
		'/onboarding/progress',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'ihq_portal_onboarding_handle_get',
				'permission_callback' => 'ihq_portal_onboarding_permission_check',
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'ihq_portal_onboarding_handle_post',
				'permission_callback' => 'ihq_portal_onboarding_permission_check',
			),
		)
	);
}
add_action( 'rest_api_init', 'ihq_portal_onboarding_register_progress_routes' );

<?php
/**
 * AI Coach uninterrupted registration (PO-3257).
 *
 * New emails: create the influencer and sign them in immediately — no 6-digit
 * code, no login screen. Existing emails: send the normal passwordless login
 * code and redirect to portal login (never set a session from email alone).
 *
 * POST /wp-json/ihq/v1/create-account
 *
 * The JS event wrapper in js/ihq-aicoach-events.js is what Luna (and the
 * Let's Continue button) call. This file is the server half of that event.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Seconds between register attempts from the same IP. */
const IHQ_AICOACH_REGISTER_THROTTLE_SECONDS = 45;

/**
 * Channel keys the coach form currently offers. Anything else is dropped so
 * an agent cannot write arbitrary meta keys.
 *
 * @return string[]
 */
function ihq_aicoach_allowed_channel_keys() {
	return array(
		'email',
		'kakaotalk',
		'line',
		'sms',
		'telegram',
		'wechat',
		'whatsapp',
		'zalo',
	);
}

/**
 * Turn a REST body into named registration fields.
 *
 * Accepts the coach-flow camelCase shape and the comm_methods map used by the
 * older modal, so Luna can post whichever it already has.
 *
 * @param array $params Request params.
 * @return array{value?:array,error?:string}
 */
function ihq_aicoach_parse_register_payload( array $params ) {
	$first_name = isset( $params['firstName'] )
		? sanitize_text_field( (string) $params['firstName'] )
		: ( isset( $params['first_name'] ) ? sanitize_text_field( (string) $params['first_name'] ) : '' );
	$last_name = isset( $params['lastName'] )
		? sanitize_text_field( (string) $params['lastName'] )
		: ( isset( $params['last_name'] ) ? sanitize_text_field( (string) $params['last_name'] ) : '' );
	$username_raw = isset( $params['username'] ) ? (string) $params['username'] : '';
	$language     = isset( $params['language'] ) ? sanitize_key( (string) $params['language'] ) : '';
	$duration     = isset( $params['duration'] ) ? sanitize_key( (string) $params['duration'] ) : '';

	$comm_methods = array();
	$allowed      = ihq_aicoach_allowed_channel_keys();

	if ( isset( $params['channels'] ) && is_array( $params['channels'] ) ) {
		foreach ( $params['channels'] as $entry ) {
			if ( is_string( $entry ) ) {
				continue;
			}
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$key = isset( $entry['channel'] ) ? sanitize_key( (string) $entry['channel'] ) : '';
			$val = isset( $entry['value'] ) ? sanitize_text_field( (string) $entry['value'] ) : '';
			if ( $key === '' || $val === '' || ! in_array( $key, $allowed, true ) ) {
				continue;
			}
			$comm_methods[ $key ] = $val;
		}
	}

	if ( $comm_methods === array() && isset( $params['comm_methods'] ) && is_array( $params['comm_methods'] ) ) {
		foreach ( $params['comm_methods'] as $key => $val ) {
			$key = sanitize_key( (string) $key );
			$val = sanitize_text_field( (string) $val );
			if ( $key === '' || $val === '' || ! in_array( $key, $allowed, true ) ) {
				continue;
			}
			$comm_methods[ $key ] = $val;
		}
	}

	$email = '';
	if ( isset( $comm_methods['email'] ) && is_email( $comm_methods['email'] ) ) {
		$email = sanitize_email( $comm_methods['email'] );
	} else {
		foreach ( $comm_methods as $val ) {
			if ( is_email( $val ) ) {
				$email = sanitize_email( $val );
				break;
			}
		}
	}

	if ( $email === '' ) {
		return array(
			'error' => __( 'Please add an email address so we can create your account.', 'influencer-hq' ),
		);
	}

	$username = '';
	if ( $username_raw !== '' && function_exists( 'ihq_normalize_portal_username' ) ) {
		$username = ihq_normalize_portal_username( $username_raw );
	}

	return array(
		'value' => array(
			'email'        => $email,
			'first_name'   => $first_name,
			'last_name'    => $last_name,
			'username'     => $username,
			'comm_methods' => $comm_methods,
			'language'     => $language,
			'duration'     => $duration,
		),
	);
}

/**
 * Sign the user in with the same cookie path a later login uses.
 *
 * @param int    $user_id     WordPress user ID.
 * @param string $country_iso ISO country.
 * @return void
 */
function ihq_aicoach_sign_in_user( $user_id, $country_iso ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return;
	}

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, false );

	if ( function_exists( 'ihq_refresh_influencer_oauth_tokens' ) ) {
		ihq_refresh_influencer_oauth_tokens( $user_id, $country_iso );
	}
}

/**
 * Attach the coach-chosen public handle, or leave the pending flag if none.
 *
 * @param int    $user_id  WordPress user ID.
 * @param string $username Normalized portal username, or empty.
 * @return true|WP_Error
 */
function ihq_aicoach_apply_portal_username( $user_id, $username ) {
	$user_id = (int) $user_id;
	if ( $username === '' ) {
		return true;
	}

	if ( ! function_exists( 'ihq_validate_portal_username_for_save' ) ) {
		return true;
	}

	$valid = ihq_validate_portal_username_for_save( $username, $user_id );
	if ( is_wp_error( $valid ) ) {
		return $valid;
	}

	update_user_meta( $user_id, ihq_portal_username_meta_key(), $username );
	delete_user_meta( $user_id, ihq_portal_username_pending_meta_key() );

	return true;
}

/**
 * permission_callback — same REST nonce the coach flow already sends.
 *
 * @param WP_REST_Request $request Request.
 * @return bool
 */
function ihq_aicoach_register_permission_check( WP_REST_Request $request ) {
	$nonce = $request->get_header( 'X-WP-Nonce' );
	return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
}

/**
 * POST /ihq/v1/create-account — create + sign in (new email) or passwordless login (existing).
 *
 * Turnstile is intentionally not required here: PO-3257's acceptance criteria
 * forbid a verification screen on the *new-user* path. The REST nonce is the
 * CSRF check. Existing emails never receive a session from this route alone.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function ihq_aicoach_handle_create_account( WP_REST_Request $request ) {
	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_params();
	}
	if ( ! is_array( $params ) ) {
		$params = array();
	}

	$parsed = ihq_aicoach_parse_register_payload( $params );
	if ( isset( $parsed['error'] ) ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'error'   => $parsed['error'],
			),
			400
		);
	}

	$payload = $parsed['value'];
	$portal_redirect = function_exists( 'ihq_portal_redirect_url_for_context' )
		? ihq_portal_redirect_url_for_context( 'portal_home' )
		: trailingslashit( home_url( '/portal/portal-home' ) );
	$login_redirect = trailingslashit( home_url( '/portal/login' ) );

	if ( is_user_logged_in() ) {
		return new WP_REST_Response(
			array(
				'success'     => true,
				'redirectUrl' => $portal_redirect,
			),
			200
		);
	}

	$ip           = function_exists( 'ihq_get_client_ip_for_rate_limit' ) ? ihq_get_client_ip_for_rate_limit() : '';
	$throttle_key = 'ihq_aicoach_register_' . md5( $ip !== '' ? $ip : 'unknown' );
	if ( get_transient( $throttle_key ) ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'error'   => __( 'Please wait a moment before trying again.', 'influencer-hq' ),
			),
			429
		);
	}

	$country_iso = function_exists( 'ihq_get_cloudflare_country_iso_alpha2' )
		? ihq_get_cloudflare_country_iso_alpha2()
		: '';

	$existing = get_user_by( 'email', $payload['email'] );
	if ( $existing ) {
		// Prove inbox ownership — never wp_set_auth_cookie / overwrite username here.
		$login_message = __( 'If that email matches an Influencer HQ account, you will receive a sign-in code shortly.', 'influencer-hq' );
		$signup_token  = '';

		$is_influencer = function_exists( 'ihq_user_has_influencer_role' )
			&& ihq_user_has_influencer_role( $existing );

		if ( $is_influencer && function_exists( 'ihq_issue_and_send_login_code_for_influencer' ) ) {
			$result = ihq_issue_and_send_login_code_for_influencer( $existing );
			if ( empty( $result['ok'] ) ) {
				$status = ! empty( $result['throttled'] ) ? 429 : 400;
				return new WP_REST_Response(
					array(
						'success' => false,
						'error'   => isset( $result['error'] )
							? $result['error']
							: __( 'Unable to send a sign-in code. Please try again.', 'influencer-hq' ),
					),
					$status
				);
			}
			if ( isset( $result['message'] ) && is_string( $result['message'] ) && $result['message'] !== '' ) {
				$login_message = $result['message'];
			}
			if ( isset( $result['signup_token'] ) && is_string( $result['signup_token'] ) ) {
				$signup_token = $result['signup_token'];
			}
		}

		set_transient( $throttle_key, 1, IHQ_AICOACH_REGISTER_THROTTLE_SECONDS );

		// Same success shape as a new-user create (no existing:true) so the
		// response alone cannot be used to probe which emails are registered.
		$response = array(
			'success'     => true,
			'redirectUrl' => $login_redirect,
			'message'     => $login_message,
		);
		if ( $signup_token !== '' ) {
			$response['signupToken'] = $signup_token;
		}

		return new WP_REST_Response( $response, 200 );
	}

	if ( $payload['username'] !== '' && function_exists( 'ihq_validate_portal_username_for_save' ) ) {
		$username_ok = ihq_validate_portal_username_for_save( $payload['username'], 0 );
		if ( is_wp_error( $username_ok ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'error'   => $username_ok->get_error_message(),
				),
				400
			);
		}
	}

	$registration = array(
		'email'          => $payload['email'],
		'first_name'     => $payload['first_name'],
		'last_name'      => $payload['last_name'],
		'comm_methods'   => $payload['comm_methods'],
		'challenge_type' => 'maybe_later',
		'country_iso'    => $country_iso,
	);

	$user_id = ihq_create_influencer_user_from_registration_data( $registration );
	if ( is_wp_error( $user_id ) ) {
		return new WP_REST_Response(
			array(
				'success' => false,
				'error'   => $user_id->get_error_message(),
			),
			400
		);
	}

	ihq_aicoach_apply_portal_username( (int) $user_id, $payload['username'] );

	if ( $payload['language'] !== '' ) {
		update_user_meta( (int) $user_id, '_ihq_aicoach_language', $payload['language'] );
	}
	if ( $payload['duration'] !== '' ) {
		update_user_meta( (int) $user_id, '_ihq_aicoach_duration', $payload['duration'] );
	}

	ihq_aicoach_sign_in_user( (int) $user_id, $country_iso );
	set_transient( $throttle_key, 1, IHQ_AICOACH_REGISTER_THROTTLE_SECONDS );

	return new WP_REST_Response(
		array(
			'success'     => true,
			'redirectUrl' => $portal_redirect,
		),
		200
	);
}

/**
 * Register the create-account route.
 *
 * @return void
 */
function ihq_aicoach_register_routes() {
	register_rest_route(
		'ihq/v1',
		'/create-account',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'ihq_aicoach_handle_create_account',
			'permission_callback' => 'ihq_aicoach_register_permission_check',
		)
	);
}
add_action( 'rest_api_init', 'ihq_aicoach_register_routes' );

/**
 * Enqueue the coach-events script on the AI Coach page only.
 *
 * @return void
 */
function ihq_aicoach_enqueue_register_event() {
	if ( ! is_page_template( 'page-home-aicoach.php' ) ) {
		return;
	}

	$script_path = get_template_directory() . '/js/ihq-aicoach-events.js';
	$script_ver  = file_exists( $script_path ) ? (string) filemtime( $script_path ) : _S_VERSION;

	wp_register_script(
		'ihq-aicoach-events',
		get_template_directory_uri() . '/js/ihq-aicoach-events.js',
		array(),
		$script_ver,
		true
	);
	wp_localize_script(
		'ihq-aicoach-events',
		'IHQ_AICOACH_EVENTS',
		array(
			'restUrl' => esc_url_raw( rest_url( 'ihq/v1/create-account' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'i18n'    => array(
				'missingEmail' => __( 'Please add an email address so we can create your account.', 'influencer-hq' ),
				'network'      => __( 'Network error. Please try again.', 'influencer-hq' ),
			),
		)
	);
	wp_enqueue_script( 'ihq-aicoach-events' );
}
add_action( 'wp_enqueue_scripts', 'ihq_aicoach_enqueue_register_event', 5 );

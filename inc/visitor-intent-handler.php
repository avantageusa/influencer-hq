<?php
/**
 * Visitor intent cookie flow: Braze sync + 6-digit registration code.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current request uses a portal page template.
 *
 * @return bool
 */
function ihq_is_portal_page_template() {
	if ( ! is_page() ) {
		return false;
	}

	$template = get_page_template_slug();
	return is_string( $template ) && strpos( $template, 'page-portal-' ) === 0;
}

/** Cookie name mirrored in js/ihq-visitor-intent.js */
function ihq_visitor_intent_cookie_name() {
	return 'ihq_visitor_intent';
}

/** @return int */
function ihq_visitor_intent_ttl_seconds() {
	return 30 * DAY_IN_SECONDS;
}

/** @return int */
function ihq_visitor_verification_code_ttl_seconds() {
	return 15 * MINUTE_IN_SECONDS;
}

/**
 * Stable hash of comm_methods — ties a verification code to cookie comm data.
 *
 * @param array $intent Visitor intent.
 * @return string
 */
function ihq_visitor_intent_comm_fingerprint( array $intent ) {
	$comm = isset( $intent['comm_methods'] ) && is_array( $intent['comm_methods'] ) ? $intent['comm_methods'] : array();
	ksort( $comm );
	$normalized = array();
	foreach ( $comm as $key => $value ) {
		$normalized[ sanitize_key( (string) $key ) ] = trim( (string) $value );
	}

	return hash( 'sha256', wp_json_encode( $normalized ) );
}

/**
 * @return string Six-digit zero-padded code.
 */
function ihq_generate_visitor_verification_code() {
	return str_pad( (string) random_int( 0, 999999 ), 6, '0', STR_PAD_LEFT );
}

/**
 * @param string $code Raw 6-digit code.
 * @return string
 */
function ihq_visitor_verification_code_option_key( $code ) {
	return 'ihq_visitor_verify_' . preg_replace( '/\D/', '', (string) $code );
}

/**
 * @param string $fingerprint Comm fingerprint.
 * @return string
 */
function ihq_visitor_verification_fingerprint_option_key( $fingerprint ) {
	return 'ihq_visitor_verify_fp_' . $fingerprint;
}

/**
 * @param array $intent Visitor intent.
 * @return bool
 */
function ihq_visitor_intent_has_comm_methods( array $intent ) {
	$comm = isset( $intent['comm_methods'] ) && is_array( $intent['comm_methods'] ) ? $intent['comm_methods'] : array();
	foreach ( $comm as $value ) {
		if ( is_string( $value ) && trim( $value ) !== '' ) {
			return true;
		}
	}

	return false;
}

/**
 * @param array $intent Visitor intent.
 * @return array Registration payload for ihq_create_influencer_user_from_registration_data().
 */
function ihq_build_registration_data_from_visitor_intent( array $intent ) {
	$comm_methods   = isset( $intent['comm_methods'] ) && is_array( $intent['comm_methods'] ) ? $intent['comm_methods'] : array();
	$social_handles = isset( $intent['social_handles'] ) && is_array( $intent['social_handles'] ) ? $intent['social_handles'] : array();
	$ratings        = isset( $intent['competition_ratings'] ) && is_array( $intent['competition_ratings'] ) ? $intent['competition_ratings'] : array();
	$challenge      = isset( $intent['challenge_type'] ) ? sanitize_text_field( (string) $intent['challenge_type'] ) : 'maybe_later';
	$country_iso    = isset( $intent['country_iso'] ) ? (string) $intent['country_iso'] : '';

	return array(
		'email'                   => ihq_visitor_intent_extract_email( $intent ),
		'first_name'              => '',
		'last_name'               => '',
		'platform_handle'         => ihq_visitor_intent_build_platform_handle( $intent ),
		'comm_methods'            => $comm_methods,
		'social_handles'          => $social_handles,
		'challenge_type'          => $challenge,
		'competition_preferences' => ihq_visitor_intent_competition_preferences_from_ratings( $ratings ),
		'country_iso'             => $country_iso,
		'ratings'                 => $ratings,
	);
}

/**
 * Create or log in user from visitor intent; apply competition ratings meta.
 *
 * @param array $intent Visitor intent.
 * @return array{ok:bool,user_id?:int,existing?:bool,message?:string}
 */
function ihq_complete_visitor_intent_registration( array $intent ) {
	$registration = ihq_build_registration_data_from_visitor_intent( $intent );
	$email        = $registration['email'];
	$ratings        = $registration['ratings'];
	unset( $registration['ratings'] );

	if ( $email === '' ) {
		return array(
			'ok'      => false,
			'message' => __( 'No email was found in your saved form data. Please complete the modal with an email address.', 'influencer-hq' ),
		);
	}

	if ( email_exists( $email ) ) {
		$existing = get_user_by( 'email', $email );
		if ( $existing ) {
			wp_set_current_user( $existing->ID );
			wp_set_auth_cookie( $existing->ID, false );

			return array(
				'ok'       => true,
				'user_id'  => (int) $existing->ID,
				'existing' => true,
			);
		}
	}

	$user_id = ihq_create_influencer_user_from_registration_data( $registration );
	if ( is_wp_error( $user_id ) ) {
		return array(
			'ok'      => false,
			'message' => $user_id->get_error_message(),
		);
	}

	foreach ( $ratings as $group => $rating ) {
		update_user_meta( (int) $user_id, 'competition_rating_' . sanitize_key( (string) $group ), sanitize_text_field( (string) $rating ) );
	}

	wp_set_current_user( (int) $user_id );
	wp_set_auth_cookie( (int) $user_id, false );

	return array(
		'ok'      => true,
		'user_id' => (int) $user_id,
	);
}

/**
 * @param array $intent Decoded visitor intent.
 * @return string
 */
function ihq_visitor_intent_extract_email( array $intent ) {
	$comm = isset( $intent['comm_methods'] ) && is_array( $intent['comm_methods'] ) ? $intent['comm_methods'] : array();
	if ( ! empty( $comm['email'] ) && is_email( $comm['email'] ) ) {
		return sanitize_email( $comm['email'] );
	}
	foreach ( $comm as $value ) {
		if ( is_string( $value ) && is_email( $value ) ) {
			return sanitize_email( $value );
		}
	}
	return '';
}

/**
 * @param array $intent Visitor intent.
 * @return string
 */
function ihq_visitor_intent_build_platform_handle( array $intent ) {
	if ( ! empty( $intent['platform_handle'] ) ) {
		return sanitize_text_field( (string) $intent['platform_handle'] );
	}
	$handles = isset( $intent['social_handles'] ) && is_array( $intent['social_handles'] ) ? $intent['social_handles'] : array();
	$parts   = array();
	foreach ( $handles as $val ) {
		$val = trim( (string) $val );
		if ( $val !== '' ) {
			$parts[] = $val;
		}
	}
	return implode( ' | ', $parts );
}

/**
 * @param array $ratings e.g. world => "2".
 * @return string
 */
function ihq_visitor_intent_competition_preferences_from_ratings( array $ratings ) {
	$parts = array();
	if ( ! empty( $ratings['world'] ) && (int) $ratings['world'] >= 2 ) {
		$parts[] = 'world-competition';
	}
	if ( ! empty( $ratings['community'] ) && (int) $ratings['community'] >= 2 ) {
		$parts[] = 'community-competition';
	}
	return implode( ',', $parts );
}

/**
 * @param array  $intent             Visitor intent payload.
 * @param string $button_press_url   Where the registration step was triggered.
 * @param string $registration_code  Six-digit verification code (delivered via Braze).
 * @return array Braze /users/track body.
 */
function ihq_build_braze_track_payload_for_visitor_intent( array $intent, $button_press_url, $registration_code ) {
	$email        = ihq_visitor_intent_extract_email( $intent );
	$external_id  = $email !== '' ? $email : ( 'visitor_' . wp_generate_password( 16, false, false ) );
	$first_name   = '';
	$last_name    = '';
	$country_iso  = isset( $intent['country_iso'] ) ? sanitize_text_field( (string) $intent['country_iso'] ) : '';

	$attrs = array_merge(
		array(
			'external_id'              => $external_id,
			'first_name'               => $first_name,
			'last_name'                => $last_name,
			'Language'                 => $country_iso,
			'visitor_intent_json'      => wp_json_encode( $intent ),
			'test_registry_button_url' => esc_url_raw( $button_press_url ),
			'registration_code'        => sanitize_text_field( (string) $registration_code ),
		),
		function_exists( 'ihq_visitor_intent_braze_attribute_extras' )
			? ihq_visitor_intent_braze_attribute_extras( $intent )
			: array()
	);
	if ( $email !== '' ) {
		$attrs['email'] = $email;
	}

	return array(
		'attributes' => array( $attrs ),
		'events'     => array(
			array(
				'external_id' => $external_id,
				'name'        => 'visitor_registration_code_issued',
				'time'        => gmdate( 'c' ),
				'properties'  => array(
					'button_press_url'  => esc_url_raw( $button_press_url ),
					'registration_code' => sanitize_text_field( (string) $registration_code ),
					'source_url'        => isset( $intent['source_url'] ) ? esc_url_raw( (string) $intent['source_url'] ) : '',
				),
			),
		),
	);
}

/**
 * POST visitor intent to Braze REST /users/track.
 *
 * @param array $braze_data Track payload.
 * @return array{ok:bool,code:int,body:string}
 */
function ihq_post_braze_track_payload( array $braze_data ) {
	$braze_response = wp_remote_post(
		ihq_braze_rest_endpoint() . '/users/track',
		array(
			'headers'   => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . ihq_braze_track_api_key(),
			),
			'body'      => wp_json_encode( $braze_data ),
			'timeout'   => 15,
			'sslverify' => true,
		)
	);

	if ( is_wp_error( $braze_response ) ) {
		return array(
			'ok'   => false,
			'code' => 0,
			'body' => $braze_response->get_error_message(),
		);
	}

	$code = (int) wp_remote_retrieve_response_code( $braze_response );
	$body = (string) wp_remote_retrieve_body( $braze_response );

	return array(
		'ok'   => $code >= 200 && $code < 300,
		'code' => $code,
		'body' => $body,
	);
}

/**
 * /portal/account URL for visitor-intent redirects.
 * Page uses Portal Profile template (page-portal-profile.php), not page-portal-account.php.
 *
 * @return string
 */
function ihq_visitor_intent_portal_account_url() {
	if ( function_exists( 'ihq_portal_account_url' ) ) {
		return ihq_portal_account_url();
	}

	$account_page = get_page_by_path( 'portal/account' );
	if ( $account_page instanceof WP_Post && $account_page->post_status === 'publish' ) {
		$permalink = get_permalink( $account_page );
		if ( is_string( $permalink ) && $permalink !== '' ) {
			return $permalink;
		}
	}

	return trailingslashit( home_url( '/portal/account' ) );
}

/**
 * Parse and normalize visitor intent from AJAX POST.
 *
 * @return array{intent:array,button_press_url:string,country_iso:string}|null
 */
function ihq_parse_visitor_intent_ajax_request() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ihq_visitor_intent_nonce' ) ) {
		return null;
	}

	$intent_raw = isset( $_POST['intent_json'] ) ? wp_unslash( $_POST['intent_json'] ) : '';
	$intent     = json_decode( is_string( $intent_raw ) ? $intent_raw : '', true );
	if ( ! is_array( $intent ) ) {
		return null;
	}

	$button_press_url = isset( $_POST['button_press_url'] ) ? esc_url_raw( wp_unslash( $_POST['button_press_url'] ) ) : '';
	$gate_id          = isset( $_POST['gate_id'] ) ? sanitize_key( wp_unslash( $_POST['gate_id'] ) ) : '';
	if ( $gate_id !== '' ) {
		$intent['gate_id'] = $gate_id;
	}

	$country_iso = isset( $_POST['country_iso'] ) ? sanitize_text_field( wp_unslash( $_POST['country_iso'] ) ) : '';
	if ( $country_iso !== '' ) {
		$intent['country_iso'] = function_exists( 'ihq_normalize_country_iso_alpha2' )
			? ihq_normalize_country_iso_alpha2( $country_iso )
			: strtoupper( $country_iso );
	}

	return array(
		'intent'           => $intent,
		'button_press_url' => $button_press_url,
		'country_iso'      => $country_iso,
	);
}

/**
 * Issue a 6-digit code tied to visitor comm_methods; prepare Braze payload.
 *
 * @param array  $intent           Visitor intent.
 * @param string $button_press_url Trigger URL for Braze event.
 * @return array{ok:bool,code?:string,braze_track_payload?:array,braze_response?:array,message?:string,expires_minutes?:int,reused?:bool}
 */
function ihq_issue_visitor_verification_code( array $intent, $button_press_url = '' ) {
	if ( ! ihq_visitor_intent_has_comm_methods( $intent ) ) {
		return array(
			'ok'      => false,
			'message' => __( 'Please choose a communication method first.', 'influencer-hq' ),
		);
	}

	$fingerprint = ihq_visitor_intent_comm_fingerprint( $intent );
	$fp_key      = ihq_visitor_verification_fingerprint_option_key( $fingerprint );
	$existing    = get_option( $fp_key );

	if ( is_array( $existing ) && ! empty( $existing['code'] ) && time() < (int) ( $existing['expires'] ?? 0 ) ) {
		$code          = (string) $existing['code'];
		$braze_payload = ihq_build_braze_track_payload_for_visitor_intent( $intent, $button_press_url, $code );
		$minutes_left  = max( 1, (int) ceil( ( (int) $existing['expires'] - time() ) / MINUTE_IN_SECONDS ) );

		return array(
			'ok'                  => true,
			'code'                => $code,
			'braze_track_payload' => $braze_payload,
			'expires_minutes'     => $minutes_left,
			'reused'              => true,
		);
	}

	if ( is_array( $existing ) && ! empty( $existing['code'] ) ) {
		delete_option( ihq_visitor_verification_code_option_key( (string) $existing['code'] ) );
	}

	$code         = ihq_generate_visitor_verification_code();
	$verify_token = wp_generate_password( 32, false, false );
	$expires      = time() + ihq_visitor_verification_code_ttl_seconds();
	$code_hash    = hash_hmac( 'sha256', $code, wp_salt( 'ihq_visitor_verify' ) . $verify_token );

	$record = array(
		'verify_token' => $verify_token,
		'code_hash'    => $code_hash,
		'fingerprint'  => $fingerprint,
		'intent'       => $intent,
		'created'      => time(),
		'expires'      => $expires,
	);

	update_option( ihq_visitor_verification_code_option_key( $code ), $record, false );
	update_option(
		$fp_key,
		array(
			'code'    => $code,
			'expires' => $expires,
		),
		false
	);

	$braze_payload = ihq_build_braze_track_payload_for_visitor_intent( $intent, $button_press_url, $code );
	$braze_result  = ihq_post_braze_track_payload( $braze_payload );

	if ( ! $braze_result['ok'] ) {
		error_log( 'IHQ visitor verification Braze failed: ' . $braze_result['body'] );
	}

	return array(
		'ok'                  => true,
		'code'                => $code,
		'braze_track_payload' => $braze_payload,
		'braze_response'      => array(
			'ok'   => $braze_result['ok'],
			'code' => $braze_result['code'],
			'body' => $braze_result['body'],
		),
		'expires_minutes'     => (int) ( ihq_visitor_verification_code_ttl_seconds() / MINUTE_IN_SECONDS ),
		'reused'              => false,
	);
}

/**
 * AJAX: issue 6-digit visitor verification code + send first Braze message.
 */
function ihq_handle_issue_visitor_verification_ajax() {
	$parsed = ihq_parse_visitor_intent_ajax_request();
	if ( $parsed === null ) {
		wp_send_json_error( array( 'message' => __( 'Invalid security token or intent data.', 'influencer-hq' ) ) );
		return;
	}

	$result = ihq_issue_visitor_verification_code( $parsed['intent'], $parsed['button_press_url'] );
	if ( empty( $result['ok'] ) ) {
		wp_send_json_error( array( 'message' => $result['message'] ?? __( 'Could not issue verification code.', 'influencer-hq' ) ) );
		return;
	}

	$response = array(
		'braze_track_payload' => $result['braze_track_payload'] ?? array(),
		'expires_minutes'     => $result['expires_minutes'] ?? (int) ( ihq_visitor_verification_code_ttl_seconds() / MINUTE_IN_SECONDS ),
		'reused'              => ! empty( $result['reused'] ),
	);
	if ( ! empty( $result['braze_response'] ) ) {
		$response['braze_response'] = $result['braze_response'];
	}
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! empty( $result['code'] ) ) {
		$response['registration_code_debug'] = $result['code'];
	}

	wp_send_json_success( $response );
}
add_action( 'wp_ajax_ihq_issue_visitor_verification', 'ihq_handle_issue_visitor_verification_ajax' );
add_action( 'wp_ajax_nopriv_ihq_issue_visitor_verification', 'ihq_handle_issue_visitor_verification_ajax' );

/**
 * AJAX: verify 6-digit code and register from cookie intent.
 */
function ihq_handle_verify_visitor_code_ajax() {
	$parsed = ihq_parse_visitor_intent_ajax_request();
	if ( $parsed === null ) {
		wp_send_json_error( array( 'message' => __( 'Invalid security token or intent data.', 'influencer-hq' ) ) );
		return;
	}

	$code_raw = isset( $_POST['code'] ) ? preg_replace( '/\D/', '', (string) wp_unslash( $_POST['code'] ) ) : '';
	if ( strlen( $code_raw ) !== 6 ) {
		wp_send_json_error( array( 'message' => __( 'Enter the 6-digit code we sent you.', 'influencer-hq' ) ) );
		return;
	}

	$opt_key = ihq_visitor_verification_code_option_key( $code_raw );
	$record  = get_option( $opt_key );
	if ( ! is_array( $record ) || empty( $record['code_hash'] ) || empty( $record['verify_token'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid or expired code. Check your communication method and try again.', 'influencer-hq' ) ) );
		return;
	}

	if ( time() > (int) ( $record['expires'] ?? 0 ) ) {
		delete_option( $opt_key );
		$fp_key = ihq_visitor_verification_fingerprint_option_key( (string) ( $record['fingerprint'] ?? '' ) );
		delete_option( $fp_key );
		wp_send_json_error( array( 'message' => __( 'This code has expired. Please request a new code from the lander.', 'influencer-hq' ) ) );
		return;
	}

	$expected_hash = (string) $record['code_hash'];
	$try_hash      = hash_hmac( 'sha256', $code_raw, wp_salt( 'ihq_visitor_verify' ) . (string) $record['verify_token'] );
	if ( ! hash_equals( $expected_hash, $try_hash ) ) {
		wp_send_json_error( array( 'message' => __( 'That code does not match. Check your communication method and try again.', 'influencer-hq' ) ) );
		return;
	}

	$cookie_fingerprint = ihq_visitor_intent_comm_fingerprint( $parsed['intent'] );
	$stored_fingerprint = isset( $record['fingerprint'] ) ? (string) $record['fingerprint'] : '';
	if ( $stored_fingerprint === '' || ! hash_equals( $stored_fingerprint, $cookie_fingerprint ) ) {
		wp_send_json_error( array( 'message' => __( 'This code does not match your saved communication details.', 'influencer-hq' ) ) );
		return;
	}

	$intent = $parsed['intent'];
	if ( $parsed['country_iso'] !== '' ) {
		$intent['country_iso'] = function_exists( 'ihq_normalize_country_iso_alpha2' )
			? ihq_normalize_country_iso_alpha2( $parsed['country_iso'] )
			: strtoupper( $parsed['country_iso'] );
	}

	$result = ihq_complete_visitor_intent_registration( $intent );
	if ( empty( $result['ok'] ) ) {
		wp_send_json_error( array( 'message' => $result['message'] ?? __( 'Registration failed.', 'influencer-hq' ) ) );
		return;
	}

	delete_option( $opt_key );
	delete_option( ihq_visitor_verification_fingerprint_option_key( $stored_fingerprint ) );

	$redirect_url = ihq_visitor_intent_portal_account_url();
	if ( ! empty( $result['existing'] ) ) {
		$redirect_url = add_query_arg( 'ihq_visitor_existing', '1', $redirect_url );
	} else {
		$redirect_url = add_query_arg( 'ihq_visitor_registered', '1', $redirect_url );
	}

	wp_send_json_success(
		array(
			'redirect_url' => $redirect_url,
			'existing'     => ! empty( $result['existing'] ),
		)
	);
}
add_action( 'wp_ajax_ihq_verify_visitor_code', 'ihq_handle_verify_visitor_code_ajax' );
add_action( 'wp_ajax_nopriv_ihq_verify_visitor_code', 'ihq_handle_verify_visitor_code_ajax' );

/**
 * AJAX: TEST REGISTRY — alias for issue visitor verification (debug panel).
 */
function ihq_handle_test_registry_braze_ajax() {
	$parsed = ihq_parse_visitor_intent_ajax_request();
	if ( $parsed === null ) {
		wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'influencer-hq' ) ) );
		return;
	}

	$result = ihq_issue_visitor_verification_code( $parsed['intent'], $parsed['button_press_url'] );
	if ( empty( $result['ok'] ) ) {
		wp_send_json_error( array( 'message' => $result['message'] ?? __( 'Could not issue verification code.', 'influencer-hq' ) ) );
		return;
	}

	$response = array(
		'registration_code'   => $result['code'] ?? '',
		'braze_track_payload' => $result['braze_track_payload'] ?? array(),
		'expires_minutes'     => $result['expires_minutes'] ?? (int) ( ihq_visitor_verification_code_ttl_seconds() / MINUTE_IN_SECONDS ),
	);
	if ( ! empty( $result['braze_response'] ) ) {
		$response['braze_response'] = $result['braze_response'];
	}

	wp_send_json_success( $response );
}
add_action( 'wp_ajax_ihq_test_registry_braze', 'ihq_handle_test_registry_braze_ajax' );
add_action( 'wp_ajax_nopriv_ihq_test_registry_braze', 'ihq_handle_test_registry_braze_ajax' );

/**
 * Magic link (legacy): create influencer user from stored visitor intent.
 */
function ihq_handle_magic_register_request() {
	if ( ! isset( $_GET['ihq_magic_register'] ) ) {
		return;
	}

	$token = sanitize_text_field( wp_unslash( $_GET['ihq_magic_register'] ) );
	if ( $token === '' ) {
		wp_die( esc_html__( 'Invalid registration link.', 'influencer-hq' ), '', array( 'response' => 400 ) );
	}

	$redirect_url = ihq_visitor_intent_portal_account_url();

	$record = get_option( 'ihq_magic_reg_' . $token );
	if ( ! is_array( $record ) || empty( $record['intent'] ) || ! is_array( $record['intent'] ) ) {
		wp_die( esc_html__( 'This registration link is invalid or has expired.', 'influencer-hq' ), '', array( 'response' => 410 ) );
	}

	if ( time() > (int) ( $record['expires'] ?? 0 ) ) {
		delete_option( 'ihq_magic_reg_' . $token );
		wp_die( esc_html__( 'This registration link has expired.', 'influencer-hq' ), '', array( 'response' => 410 ) );
	}

	$intent = $record['intent'];
	$result = ihq_complete_visitor_intent_registration( $intent );

	if ( empty( $result['ok'] ) ) {
		wp_die( esc_html( $result['message'] ?? __( 'Registration failed', 'influencer-hq' ) ), esc_html__( 'Registration failed', 'influencer-hq' ), array( 'response' => 500 ) );
	}

	delete_option( 'ihq_magic_reg_' . $token );

	if ( ! empty( $result['existing'] ) ) {
		wp_safe_redirect( add_query_arg( 'ihq_magic_existing', '1', $redirect_url ) );
		exit;
	}

	wp_safe_redirect( add_query_arg( 'ihq_magic_registered', '1', $redirect_url ) );
	exit;
}
add_action( 'template_redirect', 'ihq_handle_magic_register_request', 0 );

/**
 * Whether visitor-intent JS should load on this request.
 *
 * @return bool
 */
function ihq_visitor_intent_should_enqueue_script() {
	if ( is_page_template( 'page-lander.php' ) ) {
		return true;
	}

	return ihq_is_portal_page_template();
}

/**
 * Enqueue visitor-intent script on lander + all portal page templates.
 */
function ihq_enqueue_visitor_intent_assets() {
	if ( ! ihq_visitor_intent_should_enqueue_script() ) {
		return;
	}

	$script_path = get_template_directory() . '/js/ihq-visitor-intent.js';
	$version     = file_exists( $script_path ) ? (string) filemtime( $script_path ) : '1';

	wp_enqueue_script(
		'ihq-visitor-intent',
		get_template_directory_uri() . '/js/ihq-visitor-intent.js',
		array(),
		$version,
		true
	);

	wp_localize_script(
		'ihq-visitor-intent',
		'IHQ_VISITOR_INTENT',
		array(
			'cookieName' => ihq_visitor_intent_cookie_name(),
			'cookieDays' => 30,
			'accountUrl' => ihq_visitor_intent_portal_account_url(),
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'ihq_visitor_intent_nonce' ),
			'codeExpiresMinutes' => (int) ( ihq_visitor_verification_code_ttl_seconds() / MINUTE_IN_SECONDS ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ihq_enqueue_visitor_intent_assets', 25 );

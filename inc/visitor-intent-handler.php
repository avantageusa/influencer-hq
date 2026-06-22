<?php
/**
 * Visitor intent cookie flow: test Braze sync + magic-link registration.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Cookie name mirrored in js/ihq-visitor-intent.js */
function ihq_visitor_intent_cookie_name() {
	return 'ihq_visitor_intent';
}

/** @return int */
function ihq_visitor_intent_ttl_seconds() {
	return 30 * DAY_IN_SECONDS;
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
 * @param array  $intent           Visitor intent payload.
 * @param string $button_press_url Where TEST REGISTRY was clicked.
 * @param string $magic_link_url   Magic registration URL.
 * @return array Braze /users/track body.
 */
function ihq_build_braze_track_payload_for_visitor_intent( array $intent, $button_press_url, $magic_link_url ) {
	$email        = ihq_visitor_intent_extract_email( $intent );
	$external_id  = $email !== '' ? $email : ( 'visitor_' . wp_generate_password( 16, false, false ) );
	$first_name   = '';
	$last_name    = '';
	$country_iso  = isset( $intent['country_iso'] ) ? sanitize_text_field( (string) $intent['country_iso'] ) : '';

	$attrs = array(
		'external_id'              => $external_id,
		'first_name'               => $first_name,
		'last_name'                => $last_name,
		'Language'                 => $country_iso,
		'visitor_intent_json'      => wp_json_encode( $intent ),
		'test_registry_button_url' => esc_url_raw( $button_press_url ),
		'magic_register_url'       => esc_url_raw( $magic_link_url ),
	);
	if ( $email !== '' ) {
		$attrs['email'] = $email;
	}

	return array(
		'attributes' => array( $attrs ),
		'events'     => array(
			array(
				'external_id' => $external_id,
				'name'        => 'visitor_test_registry',
				'time'        => gmdate( 'c' ),
				'properties'  => array(
					'button_press_url'   => esc_url_raw( $button_press_url ),
					'magic_register_url' => esc_url_raw( $magic_link_url ),
					'source_url'         => isset( $intent['source_url'] ) ? esc_url_raw( (string) $intent['source_url'] ) : '',
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
 * AJAX: TEST REGISTRY — store intent server-side, send Braze, return magic link.
 */
function ihq_handle_test_registry_braze_ajax() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ihq_visitor_intent_nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'influencer-hq' ) ) );
		return;
	}

	$intent_raw = isset( $_POST['intent_json'] ) ? wp_unslash( $_POST['intent_json'] ) : '';
	$intent     = json_decode( is_string( $intent_raw ) ? $intent_raw : '', true );
	if ( ! is_array( $intent ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid intent data.', 'influencer-hq' ) ) );
		return;
	}

	$button_press_url = isset( $_POST['button_press_url'] ) ? esc_url_raw( wp_unslash( $_POST['button_press_url'] ) ) : '';
	$country_iso      = isset( $_POST['country_iso'] ) ? sanitize_text_field( wp_unslash( $_POST['country_iso'] ) ) : '';
	if ( $country_iso !== '' ) {
		$intent['country_iso'] = function_exists( 'ihq_normalize_country_iso_alpha2' )
			? ihq_normalize_country_iso_alpha2( $country_iso )
			: strtoupper( $country_iso );
	}

	$token = wp_generate_password( 48, false, false );
	$magic_link_url = add_query_arg( 'ihq_magic_register', rawurlencode( $token ), home_url( '/' ) );

	$record = array(
		'intent'           => $intent,
		'button_press_url' => $button_press_url,
		'magic_link_url'   => $magic_link_url,
		'created'          => time(),
		'expires'          => time() + ihq_visitor_intent_ttl_seconds(),
	);

	update_option( 'ihq_magic_reg_' . $token, $record, false );

	$braze_payload = ihq_build_braze_track_payload_for_visitor_intent( $intent, $button_press_url, $magic_link_url );
	$braze_result  = ihq_post_braze_track_payload( $braze_payload );

	if ( ! $braze_result['ok'] ) {
		error_log( 'IHQ visitor test registry Braze failed: ' . $braze_result['body'] );
	}

	wp_send_json_success(
		array(
			'magic_register_url'  => $magic_link_url,
			'braze_track_payload' => $braze_payload,
			'braze_response'      => array(
				'ok'   => $braze_result['ok'],
				'code' => $braze_result['code'],
				'body' => $braze_result['body'],
			),
		)
	);
}
add_action( 'wp_ajax_ihq_test_registry_braze', 'ihq_handle_test_registry_braze_ajax' );
add_action( 'wp_ajax_nopriv_ihq_test_registry_braze', 'ihq_handle_test_registry_braze_ajax' );

/**
 * Magic link: create influencer user from stored visitor intent.
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
	$email  = ihq_visitor_intent_extract_email( $intent );
	if ( $email === '' ) {
		wp_die( esc_html__( 'No email was found in your saved form data. Please complete the modal with an email address.', 'influencer-hq' ), '', array( 'response' => 400 ) );
	}

	if ( email_exists( $email ) ) {
		$existing = get_user_by( 'email', $email );
		if ( $existing ) {
			wp_set_current_user( $existing->ID );
			wp_set_auth_cookie( $existing->ID, false );
			delete_option( 'ihq_magic_reg_' . $token );
			wp_safe_redirect( add_query_arg( 'ihq_magic_existing', '1', $redirect_url ) );
			exit;
		}
	}

	$comm_methods = isset( $intent['comm_methods'] ) && is_array( $intent['comm_methods'] ) ? $intent['comm_methods'] : array();
	$ratings      = isset( $intent['competition_ratings'] ) && is_array( $intent['competition_ratings'] ) ? $intent['competition_ratings'] : array();
	$challenge    = isset( $intent['challenge_type'] ) ? sanitize_text_field( (string) $intent['challenge_type'] ) : 'maybe_later';
	$country_iso  = isset( $intent['country_iso'] ) ? (string) $intent['country_iso'] : '';

	$user_id = ihq_create_influencer_user_from_registration_data(
		array(
			'email'                   => $email,
			'first_name'              => '',
			'last_name'               => '',
			'platform_handle'         => ihq_visitor_intent_build_platform_handle( $intent ),
			'comm_methods'            => $comm_methods,
			'challenge_type'          => $challenge,
			'competition_preferences' => ihq_visitor_intent_competition_preferences_from_ratings( $ratings ),
			'country_iso'             => $country_iso,
		)
	);

	if ( is_wp_error( $user_id ) ) {
		wp_die( esc_html( $user_id->get_error_message() ), esc_html__( 'Registration failed', 'influencer-hq' ), array( 'response' => 500 ) );
	}

	foreach ( $ratings as $group => $rating ) {
		update_user_meta( (int) $user_id, 'competition_rating_' . sanitize_key( (string) $group ), sanitize_text_field( (string) $rating ) );
	}

	delete_option( 'ihq_magic_reg_' . $token );

	wp_set_current_user( (int) $user_id );
	wp_set_auth_cookie( (int) $user_id, false );

	wp_safe_redirect( add_query_arg( 'ihq_magic_registered', '1', $redirect_url ) );
	exit;
}
add_action( 'template_redirect', 'ihq_handle_magic_register_request', 0 );

/**
 * Enqueue visitor-intent script on lander + /portal/account (Portal Profile template).
 */
function ihq_enqueue_visitor_intent_assets() {
	// /portal/account uses page-portal-profile.php — not the unused page-portal-account.php shell.
	if ( ! is_page_template( array( 'page-lander.php', 'page-portal-profile.php' ) ) ) {
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
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ihq_enqueue_visitor_intent_assets', 25 );

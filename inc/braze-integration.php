<?php
/**
 * Braze REST integration for influencer registration.
 *
 * Optional wp-config.php overrides:
 *   IHQ_BRAZE_EXPORT_API_KEY — users/export/ids
 *   IHQ_BRAZE_TRACK_API_KEY  — users/track
 *   IHQ_BRAZE_REST_ENDPOINT  — default https://rest.iad-05.braze.com
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return string
 */
function ihq_braze_rest_endpoint() {
	if ( defined( 'IHQ_BRAZE_REST_ENDPOINT' ) && IHQ_BRAZE_REST_ENDPOINT !== '' ) {
		return (string) IHQ_BRAZE_REST_ENDPOINT;
	}
	return 'https://rest.iad-05.braze.com';
}

/**
 * @return string
 */
function ihq_braze_export_api_key() {
	if ( defined( 'IHQ_BRAZE_EXPORT_API_KEY' ) && IHQ_BRAZE_EXPORT_API_KEY !== '' ) {
		return (string) IHQ_BRAZE_EXPORT_API_KEY;
	}
	return '20bea073-5d29-40ca-b7b5-17126a5893c6';
}

/**
 * @return string
 */
function ihq_braze_track_api_key() {
	if ( defined( 'IHQ_BRAZE_TRACK_API_KEY' ) && IHQ_BRAZE_TRACK_API_KEY !== '' ) {
		return (string) IHQ_BRAZE_TRACK_API_KEY;
	}
	return '81adeace-fad5-4566-bdd9-06095acdd3ee';
}

/**
 * Check if a user exists in Braze (for influencer integration).
 *
 * @param string      $email       Target email.
 * @param string|null $external_id Optional external id lookup.
 * @return array{success:bool,exists:bool,error?:string,user_data:?array,full_response?:array}
 */
function check_braze_user_exists_influencer( $email, $external_id = null ) {
	$email = sanitize_email( (string) $email );
	if ( ! is_email( $email ) ) {
		return array(
			'success'   => false,
			'exists'    => false,
			'error'     => 'Invalid email',
			'user_data' => null,
		);
	}

	if ( ! $external_id ) {
		$external_id = $email;
	}

	$payload = array(
		'external_ids' => array( (string) $external_id ),
	);

	$response = wp_remote_post(
		ihq_braze_rest_endpoint() . '/users/export/ids',
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . ihq_braze_export_api_key(),
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 15,
			'sslverify' => true,
		)
	);

	if ( is_wp_error( $response ) ) {
		return array(
			'success'   => false,
			'exists'    => false,
			'error'     => $response->get_error_message(),
			'user_data' => null,
		);
	}

	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );
	$data          = json_decode( $response_body, true );

	if ( $response_code < 200 || $response_code >= 300 ) {
		return array(
			'success'   => false,
			'exists'    => false,
			'error'     => 'API returned error code: ' . $response_code,
			'user_data' => null,
		);
	}

	$user_exists        = false;
	$existing_user_data = null;

	if ( isset( $data['users'] ) && is_array( $data['users'] ) && ! empty( $data['users'] ) ) {
		foreach ( $data['users'] as $user ) {
			if ( isset( $user['email'] ) && $user['email'] === $email ) {
				$user_exists        = true;
				$existing_user_data = $user;
				break;
			}
		}
	}

	return array(
		'success'       => true,
		'exists'        => $user_exists,
		'user_data'     => $existing_user_data,
		'full_response' => is_array( $data ) ? $data : array(),
	);
}

/**
 * Push influencer profile + registration event to Braze.
 *
 * @param int $user_id WordPress user ID.
 * @return bool True when Braze accepted the track request.
 */
function ihq_send_influencer_to_braze( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return false;
	}

	$user = get_userdata( $user_id );
	if ( ! $user || ! is_email( $user->user_email ) ) {
		error_log( 'IHQ Braze: skipped — invalid user ' . $user_id );
		return false;
	}

	$email      = $user->user_email;
	$first_name = (string) get_user_meta( $user_id, 'first_name', true );
	$last_name  = (string) get_user_meta( $user_id, 'last_name', true );
	$country    = (string) get_user_meta( $user_id, 'ihq_oauth_country_iso', true );
	if ( $country === '' ) {
		$country = (string) get_user_meta( $user_id, 'country', true );
	}

	$braze_user_check   = check_braze_user_exists_influencer( $email );
	$external_id_to_use = '';
	$influencer_guid    = (string) get_user_meta( $user_id, 'wp_influencer_guid', true );

	if ( $influencer_guid !== '' ) {
		$external_id_to_use = $influencer_guid;
	} elseif ( $braze_user_check['success'] && $braze_user_check['exists'] ) {
		$existing_braze_user_info = $braze_user_check['user_data'];
		$external_id_to_use       = isset( $existing_braze_user_info['external_id'] ) ? (string) $existing_braze_user_info['external_id'] : $email;
		$influencer_guid          = $external_id_to_use;
	} else {
		$influencer_guid    = 'wpinfluencer_' . bin2hex( random_bytes( 12 ) );
		$external_id_to_use = $influencer_guid;
	}

	update_user_meta( $user_id, 'wp_influencer_guid', $influencer_guid );

	$braze_data = array(
		'attributes' => array(
			array(
				'external_id'        => $external_id_to_use,
				'email'              => $email,
				'first_name'         => $first_name !== '' ? $first_name : $user->display_name,
				'last_name'          => $last_name,
				'Language'           => $country,
				'wp_influencer_guid' => $influencer_guid,
			),
		),
		'events'     => array(
			array(
				'external_id' => $external_id_to_use,
				'name'        => 'influencer_registered',
				'time'        => gmdate( 'c' ),
			),
		),
	);

	error_log( 'IHQ Braze: sending track for user ' . $user_id . ' external_id=' . $external_id_to_use );

	$braze_response = wp_remote_post(
		ihq_braze_rest_endpoint() . '/users/track',
		array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . ihq_braze_track_api_key(),
			),
			'body'    => wp_json_encode( $braze_data ),
			'timeout' => 15,
			'sslverify' => true,
		)
	);

	if ( is_wp_error( $braze_response ) ) {
		error_log( 'IHQ Braze: API error — ' . $braze_response->get_error_message() );
		return false;
	}

	$braze_body = wp_remote_retrieve_body( $braze_response );
	$braze_code = wp_remote_retrieve_response_code( $braze_response );
	error_log( 'IHQ Braze: response code ' . $braze_code . ' body ' . $braze_body );

	return $braze_code >= 200 && $braze_code < 300;
}

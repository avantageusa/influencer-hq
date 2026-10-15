<?php
/**
 * Onboarding answers to Braze (ENGR-7573, PO-3063 T11, PO-3221).
 *
 * Every time the account store (inc/portal-onboarding-progress.php) saves an answer, the matching
 * Braze custom attribute is sent for that Influencer through the existing server-side
 * /users/track integration (inc/braze-integration.php), so a drop-out's answers reach Braze too,
 * not only a finished onboarding's. Completion sends onboarding_completed.
 *
 * Attributes (evaluation OQ17, pending Product; Jenna to confirm privacy for handles):
 * baccarat_experience, streaming_experience, streaming_learning_intent, equity_knowledge,
 * equity_management, social_platforms (array), social_handle_<platform> (one per platform),
 * active_follower_count, post_action_choice, language_preference, onboarding_completed. Values are
 * the store's enum values. A cleared answer (a hidden sub-option) is sent as null, which removes
 * the attribute in Braze.
 *
 * Braze is never allowed to block onboarding: a failure is logged and the save goes on.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Seconds to wait for Braze; the Influencer's save waits this long at most. */
const IHQ_PORTAL_ONBOARDING_BRAZE_TIMEOUT = 5;

/** User meta holding the Braze external_id set at registration (inc/braze-integration.php). */
const IHQ_PORTAL_ONBOARDING_BRAZE_EXTERNAL_ID_META = 'wp_influencer_guid';

/** Prefix of the per-platform handle attribute. */
const IHQ_PORTAL_ONBOARDING_BRAZE_HANDLE_PREFIX = 'social_handle_';

/**
 * The Braze attributes for one onboarding save. Pure.
 *
 * Answers map to attributes of the same name. A save that touches platforms or handles sends the
 * whole picture: social_platforms, and social_handle_<platform> for each of the platforms, null
 * where there is no handle, so Braze never keeps one the Influencer removed.
 *
 * @param array $partial The validated update.
 * @param array $state   The state after it.
 * @return array<string, mixed> Attribute name => value; empty when nothing Braze tracks changed.
 */
function ihq_portal_onboarding_braze_attributes( array $partial, array $state ) {
	$attributes = array();

	if ( isset( $partial['answers'] ) && is_array( $partial['answers'] ) ) {
		foreach ( $partial['answers'] as $field => $value ) {
			$attributes[ $field ] = $value;
		}
	}

	if ( array_key_exists( 'platforms', $partial ) || array_key_exists( 'handles', $partial ) ) {
		$platforms = isset( $state['platforms'] ) && is_array( $state['platforms'] ) ? array_values( $state['platforms'] ) : array();
		$handles   = isset( $state['handles'] ) && is_array( $state['handles'] ) ? $state['handles'] : array();
		$attributes['social_platforms'] = $platforms;
		foreach ( ihq_portal_onboarding_platforms() as $platform ) {
			$attributes[ IHQ_PORTAL_ONBOARDING_BRAZE_HANDLE_PREFIX . $platform ] = isset( $handles[ $platform ] ) ? $handles[ $platform ] : null;
		}
	}

	if ( isset( $partial['language'] ) ) {
		$attributes['language_preference'] = $partial['language'];
	}

	return $attributes;
}

/**
 * Sends attributes for one Influencer to Braze. Never throws; a failure is logged.
 *
 * @param int   $user_id
 * @param array $attributes Attribute name => value.
 * @return bool Whether Braze accepted them.
 */
function ihq_portal_onboarding_braze_send( $user_id, array $attributes ) {
	if ( array() === $attributes ) {
		return false;
	}
	$external_id = (string) get_user_meta( (int) $user_id, IHQ_PORTAL_ONBOARDING_BRAZE_EXTERNAL_ID_META, true );
	if ( '' === $external_id ) {
		error_log( 'IHQ Braze onboarding: skipped, no external_id for user ' . (int) $user_id );
		return false;
	}

	$response = wp_remote_post(
		ihq_braze_rest_endpoint() . '/users/track',
		array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . ihq_braze_track_api_key(),
			),
			'body'    => wp_json_encode( array( 'attributes' => array( array_merge( array( 'external_id' => $external_id ), $attributes ) ) ) ),
			'timeout' => IHQ_PORTAL_ONBOARDING_BRAZE_TIMEOUT,
		)
	);

	if ( is_wp_error( $response ) ) {
		error_log( 'IHQ Braze onboarding: API error for user ' . (int) $user_id . ': ' . $response->get_error_message() );
		return false;
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		error_log( 'IHQ Braze onboarding: response ' . $code . ' for user ' . (int) $user_id . ': ' . wp_remote_retrieve_body( $response ) );
		return false;
	}
	return true;
}

/**
 * Hooked on `ihq_portal_onboarding_saved`.
 *
 * @param int   $user_id
 * @param array $partial
 * @param array $state
 * @return void
 */
function ihq_portal_onboarding_braze_on_saved( $user_id, $partial, $state ) {
	if ( ! is_array( $partial ) || ! is_array( $state ) ) {
		return;
	}
	ihq_portal_onboarding_braze_send( $user_id, ihq_portal_onboarding_braze_attributes( $partial, $state ) );
}
add_action( 'ihq_portal_onboarding_saved', 'ihq_portal_onboarding_braze_on_saved', 20, 3 );

/**
 * Hooked on `ihq_portal_onboarding_completed`.
 *
 * @param int $user_id
 * @return void
 */
function ihq_portal_onboarding_braze_on_completed( $user_id ) {
	ihq_portal_onboarding_braze_send( $user_id, array( 'onboarding_completed' => true ) );
}
add_action( 'ihq_portal_onboarding_completed', 'ihq_portal_onboarding_braze_on_completed', 20, 1 );

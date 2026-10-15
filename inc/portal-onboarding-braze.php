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
 * Braze is never allowed to block onboarding. The listeners only queue the attributes; the queue
 * is sent on `shutdown`, after the response has gone to the browser where PHP-FPM allows it
 * (fastcgi_finish_request()), so a slow Braze does not hold the Influencer's save. A failure is
 * logged with its status code only (never Braze's response body) and the save goes on.
 *
 * Completion is idempotent on the Braze side: it sets the attribute onboarding_completed to true and
 * carries nothing that changes between calls (no timestamp, no event, no counter). The store cannot
 * stop two concurrent completions from both firing the action (user meta has no compare-and-set), and
 * when that happens Braze receives the same value twice and ends in the same state.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Seconds to wait for Braze. The send runs after the response where PHP-FPM allows it; elsewhere the save waits this long at most. */
const IHQ_PORTAL_ONBOARDING_BRAZE_TIMEOUT = 5;

/** User meta holding the Braze external_id set at registration (inc/braze-integration.php). */
const IHQ_PORTAL_ONBOARDING_BRAZE_EXTERNAL_ID_META = 'wp_influencer_guid';

/** Prefix of the per-platform handle attribute. */
const IHQ_PORTAL_ONBOARDING_BRAZE_HANDLE_PREFIX = 'social_handle_';

/** Attribute holding the selected platforms. */
const IHQ_PORTAL_ONBOARDING_BRAZE_PLATFORMS_ATTRIBUTE = 'social_platforms';

/** Attribute holding the onboarding language. */
const IHQ_PORTAL_ONBOARDING_BRAZE_LANGUAGE_ATTRIBUTE = 'language_preference';

/** Attribute set to true once onboarding is complete. */
const IHQ_PORTAL_ONBOARDING_BRAZE_COMPLETED_ATTRIBUTE = 'onboarding_completed';

/** Braze REST path for attribute updates, appended to ihq_braze_rest_endpoint(). */
const IHQ_PORTAL_ONBOARDING_BRAZE_TRACK_PATH = '/users/track';

/** Lowest HTTP status of an accepted request. */
const IHQ_PORTAL_ONBOARDING_BRAZE_SUCCESS_MIN = 200;

/** First HTTP status above the accepted (2xx) range. */
const IHQ_PORTAL_ONBOARDING_BRAZE_SUCCESS_END = 300;

/** Priority of the save and completion listeners: after any listener at the default priority (10). */
const IHQ_PORTAL_ONBOARDING_BRAZE_LISTENER_PRIORITY = 20;

/**
 * The queue is sent last on `shutdown`. fastcgi_finish_request() ends the response, so anything a
 * later shutdown callback printed would be lost; running last means there is no later callback.
 */
const IHQ_PORTAL_ONBOARDING_BRAZE_SHUTDOWN_PRIORITY = PHP_INT_MAX;

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
		$attributes[ IHQ_PORTAL_ONBOARDING_BRAZE_PLATFORMS_ATTRIBUTE ] = $platforms;
		foreach ( ihq_portal_onboarding_platforms() as $platform ) {
			$attributes[ IHQ_PORTAL_ONBOARDING_BRAZE_HANDLE_PREFIX . $platform ] = isset( $handles[ $platform ] ) ? $handles[ $platform ] : null;
		}
	}

	if ( isset( $partial['language'] ) ) {
		$attributes[ IHQ_PORTAL_ONBOARDING_BRAZE_LANGUAGE_ATTRIBUTE ] = $partial['language'];
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
		ihq_braze_rest_endpoint() . IHQ_PORTAL_ONBOARDING_BRAZE_TRACK_PATH,
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
	// The body is not logged: Braze's error text could echo the attributes sent, which include handles.
	if ( $code < IHQ_PORTAL_ONBOARDING_BRAZE_SUCCESS_MIN || $code >= IHQ_PORTAL_ONBOARDING_BRAZE_SUCCESS_END ) {
		error_log( 'IHQ Braze onboarding: response ' . $code . ' for user ' . (int) $user_id );
		return false;
	}
	return true;
}

/**
 * The attribute updates waiting to be sent in this request, in the order they were queued.
 * Returned by reference so the queue and flush functions share one list.
 *
 * @return array<int, array{user_id: int, attributes: array}>
 */
function &ihq_portal_onboarding_braze_queue() {
	static $queue = array();
	return $queue;
}

/**
 * Queues attributes for one Influencer; they are sent on `shutdown`. Nothing is queued when there
 * is nothing to send.
 *
 * @param int   $user_id
 * @param array $attributes Attribute name => value.
 * @return void
 */
function ihq_portal_onboarding_braze_enqueue( $user_id, array $attributes ) {
	if ( array() === $attributes ) {
		return;
	}
	$queue   = &ihq_portal_onboarding_braze_queue();
	$queue[] = array(
		'user_id'    => (int) $user_id,
		'attributes' => $attributes,
	);
}

/**
 * Sends every queued update, in order, and empties the queue. Each send logs its own failure.
 *
 * @return void
 */
function ihq_portal_onboarding_braze_flush() {
	$queue   = &ihq_portal_onboarding_braze_queue();
	$pending = $queue;
	$queue   = array();
	foreach ( $pending as $update ) {
		ihq_portal_onboarding_braze_send( $update['user_id'], $update['attributes'] );
	}
}

/**
 * Hooked on `shutdown`. When something is queued, ends the response first where the server allows
 * it (PHP-FPM), so the Influencer is not kept waiting on Braze, then sends the queue. A request that
 * queued nothing is left alone.
 *
 * @return void
 */
function ihq_portal_onboarding_braze_on_shutdown() {
	if ( array() === ihq_portal_onboarding_braze_queue() ) {
		return;
	}
	if ( function_exists( 'fastcgi_finish_request' ) ) {
		fastcgi_finish_request();
	}
	ihq_portal_onboarding_braze_flush();
}
add_action( 'shutdown', 'ihq_portal_onboarding_braze_on_shutdown', IHQ_PORTAL_ONBOARDING_BRAZE_SHUTDOWN_PRIORITY, 0 );

/**
 * Hooked on IHQ_PORTAL_ONBOARDING_SAVED_ACTION.
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
	ihq_portal_onboarding_braze_enqueue( $user_id, ihq_portal_onboarding_braze_attributes( $partial, $state ) );
}
add_action( IHQ_PORTAL_ONBOARDING_SAVED_ACTION, 'ihq_portal_onboarding_braze_on_saved', IHQ_PORTAL_ONBOARDING_BRAZE_LISTENER_PRIORITY, 3 );

/**
 * Hooked on IHQ_PORTAL_ONBOARDING_COMPLETED_ACTION. Idempotent: see the file header.
 *
 * @param int $user_id
 * @return void
 */
function ihq_portal_onboarding_braze_on_completed( $user_id ) {
	ihq_portal_onboarding_braze_enqueue( $user_id, array( IHQ_PORTAL_ONBOARDING_BRAZE_COMPLETED_ATTRIBUTE => true ) );
}
add_action( IHQ_PORTAL_ONBOARDING_COMPLETED_ACTION, 'ihq_portal_onboarding_braze_on_completed', IHQ_PORTAL_ONBOARDING_BRAZE_LISTENER_PRIORITY, 1 );

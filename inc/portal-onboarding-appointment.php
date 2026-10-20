<?php
/**
 * Appointments booked from the portal onboarding (ENGR-7577, PO-3429 Scenario 37).
 *
 * The onboarding's "Set your appointment" screen books the same appointment as the landing
 * flow (PO-3109, inc/aicoach-appointment.php): the same rules, the same signed link and the
 * same link page. What differs is whom it belongs to. The landing flow keys it to a cookie
 * ref; here it belongs to the logged-in Influencer and is saved on their WordPress account
 * (user meta, like the onboarding progress, decision 2026-10-17), and Join on the link page
 * leads to the portal Coach page. The appointment is booked just before the closing screen,
 * so the onboarding is usually complete by then and Join is a chat with Sami there; the link
 * is not ended by completing the onboarding. A signed-out visitor gets the portal's own
 * sign-in gate on that page (PO-3429).
 *
 *   POST /wp-json/ihq/v1/onboarding/appointment
 *     body { choice, date, time, timeZone }
 *     201 { link, startsAt } | 400 { errors } | 429 | 500, as the landing route
 *
 * Needs a logged-in user and the wp_rest nonce (the onboarding's permission check). One
 * appointment per Influencer: a new one replaces the last, whose link then reads as invalid.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// functions.php loads the portal-onboarding-*.php files in name order, and this one comes
// before the progress store whose permission check it uses.
require_once __DIR__ . '/portal-onboarding-progress.php';

/** User meta key holding the Influencer's current onboarding appointment. */
const IHQ_PORTAL_ONBOARDING_APPOINTMENT_META_KEY = '_ihq_portal_onboarding_appointment';

/** Field of the stored appointment option naming its Influencer (the landing flow's has `ref`). */
const IHQ_PORTAL_ONBOARDING_APPOINTMENT_USER_FIELD = 'userId';

/** Where Join leads: the portal Coach page, which resumes the onboarding (inc/portal-onboarding-gate.php). */
const IHQ_PORTAL_ONBOARDING_APPOINTMENT_JOIN_PATH = '/portal/portal-home/';

/**
 * The Influencer's current appointment.
 *
 * @param int $user_id
 * @return array|null { id, startsAt, timeZone, createdAt }, or null when there is none.
 */
function ihq_portal_onboarding_appointment_get( $user_id ) {
	$stored = get_user_meta( (int) $user_id, IHQ_PORTAL_ONBOARDING_APPOINTMENT_META_KEY, true );
	return is_array( $stored ) && isset( $stored['id'] ) && is_string( $stored['id'] ) ? $stored : null;
}

/**
 * Stores a new appointment for an Influencer and replaces their previous one: an option of
 * its own (so the link page finds it from the id alone) and the user meta. The previous
 * appointment is kept until both writes have succeeded, so a failed write never leaves the
 * Influencer with no appointment, and a link is never handed out for a record not saved.
 *
 * @param int    $user_id
 * @param int    $starts_at Start, in seconds.
 * @param string $time_zone IANA name the Influencer chose, kept for display.
 * @param int    $now       The server clock, in seconds.
 * @return array|WP_Error The stored record, or an error when it could not be saved.
 */
function ihq_portal_onboarding_appointment_create( $user_id, $starts_at, $time_zone, $now ) {
	$user_id  = (int) $user_id;
	$previous = ihq_portal_onboarding_appointment_get( $user_id );

	$id     = wp_generate_uuid4();
	$record = array(
		'id'        => $id,
		'startsAt'  => (int) $starts_at,
		'timeZone'  => $time_zone,
		'createdAt' => gmdate( 'c', $now ),
	);
	$option_key = ihq_aicoach_appointment_option_key( $id );
	// autoload=no: read only by the link page. The id is new, so false is a failed write.
	if ( ! update_option( $option_key, array_merge( $record, array( IHQ_PORTAL_ONBOARDING_APPOINTMENT_USER_FIELD => $user_id ) ), false ) ) {
		return new WP_Error( 'appointment_save_failed', 'The appointment could not be saved.' );
	}

	// update_user_meta() also answers false for a value that did not change, so read it back.
	update_user_meta( $user_id, IHQ_PORTAL_ONBOARDING_APPOINTMENT_META_KEY, $record );
	$stored = ihq_portal_onboarding_appointment_get( $user_id );
	if ( null === $stored || $id !== $stored['id'] ) {
		delete_option( $option_key );
		return new WP_Error( 'appointment_account_save_failed', 'The appointment could not be saved.' );
	}

	if ( null !== $previous ) {
		delete_option( ihq_aicoach_appointment_option_key( $previous['id'] ) );
	}
	return $record;
}

/**
 * POST /ihq/v1/onboarding/appointment
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function ihq_portal_onboarding_appointment_handle_post( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	return ihq_aicoach_appointment_respond(
		$request,
		function ( $starts_at, $time_zone, $now ) use ( $user_id ) {
			return ihq_portal_onboarding_appointment_create( $user_id, $starts_at, $time_zone, $now );
		}
	);
}

/**
 * Join on the link page for an onboarding appointment leads to the portal Coach page. No
 * credential goes with it: whoever is signed in there sees their own onboarding.
 *
 * @param string|null $redirect Null, or what an earlier filter decided.
 * @param array       $record   The stored appointment.
 * @return string|null
 */
function ihq_portal_onboarding_appointment_join_redirect( $redirect, $record ) {
	if ( ! is_array( $record ) || ! isset( $record[ IHQ_PORTAL_ONBOARDING_APPOINTMENT_USER_FIELD ] ) || ! is_int( $record[ IHQ_PORTAL_ONBOARDING_APPOINTMENT_USER_FIELD ] ) ) {
		return $redirect;
	}
	return home_url( IHQ_PORTAL_ONBOARDING_APPOINTMENT_JOIN_PATH );
}
add_filter( 'ihq_aicoach_appointment_join_redirect', 'ihq_portal_onboarding_appointment_join_redirect', 10, 2 );

/**
 * @return void
 */
function ihq_portal_onboarding_appointment_register_route() {
	register_rest_route(
		'ihq/v1',
		'/onboarding/appointment',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'ihq_portal_onboarding_appointment_handle_post',
			'permission_callback' => 'ihq_portal_onboarding_permission_check',
		)
	);
}
add_action( 'rest_api_init', 'ihq_portal_onboarding_appointment_register_route' );

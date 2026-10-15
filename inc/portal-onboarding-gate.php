<?php
/**
 * Onboarding entry gate on the Coach page (ENGR-7564, PO-3063 T2).
 *
 * page-portal-home.php (/portal/portal-home/) calls ihq_portal_onboarding_maybe_render().
 * For a logged-in Influencer who should be onboarded it fires
 * `ihq_portal_onboarding_render_layer` with what the onboarding needs to start at screen 1
 * or resume at the saved stage; the onboarding shell (ENGR-7565) renders on that action.
 * Anyone else sees the Coach page exactly as before: nothing is printed.
 *
 * Who is onboarded (evaluation OQ7, pending Product confirmation): an account created after
 * the release date that has not completed onboarding, whatever path it registered through,
 * and only while the kill switch (ENGR-7561) is on. Accounts created before the release date
 * are treated as complete.
 *
 * The release date is a WordPress option set once at release:
 *
 *     wp option update ihq_portal_onboarding_release_date "2026-11-01T00:00:00Z"
 *
 * Without it nobody is onboarded, so a deploy alone never launches onboarding for existing
 * accounts.
 *
 * POST /wp-json/ihq/v1/onboarding/complete marks onboarding complete (CONTINUE TO IHQ PORTAL
 * on the closing screen, ENGR-7571). The browser then removes the onboarding layer, which
 * leaves the Influencer at the top of the Coach page.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Option holding the release date (anything strtotime() reads; a timezone-less value is UTC). */
const IHQ_PORTAL_ONBOARDING_RELEASE_OPTION = 'ihq_portal_onboarding_release_date';

/** Action the onboarding shell renders on. */
const IHQ_PORTAL_ONBOARDING_RENDER_ACTION = 'ihq_portal_onboarding_render_layer';

/**
 * The release date as a Unix timestamp.
 *
 * @return int|null Null when the option is missing or unreadable.
 */
function ihq_portal_onboarding_release_timestamp() {
	$value = get_option( IHQ_PORTAL_ONBOARDING_RELEASE_OPTION, '' );
	if ( is_int( $value ) ) {
		return $value > 0 ? $value : null;
	}
	if ( ! is_string( $value ) ) {
		return null;
	}
	$value = trim( $value );
	if ( '' === $value ) {
		return null;
	}
	if ( ctype_digit( $value ) ) {
		return (int) $value;
	}
	// A date without a zone is read as UTC, like user_registered, not in the server's zone.
	$has_zone  = 1 === preg_match( '/(Z|[+-]\d{2}:?\d{2}|UTC|GMT)$/i', $value );
	$timestamp = strtotime( $has_zone ? $value : $value . ' UTC' );
	if ( false === $timestamp ) {
		return null;
	}
	return $timestamp;
}

/**
 * Whether the account was created after the release date. user_registered is stored in UTC.
 *
 * @param int $user_id
 * @return bool
 */
function ihq_portal_onboarding_created_after_release( $user_id ) {
	$release = ihq_portal_onboarding_release_timestamp();
	if ( null === $release ) {
		return false;
	}
	$user = get_userdata( (int) $user_id );
	if ( ! $user || empty( $user->user_registered ) ) {
		return false;
	}
	$registered = strtotime( $user->user_registered . ' UTC' );
	return false !== $registered && $registered > $release;
}

/**
 * Whether the kill switch (ENGR-7561) is on. Checked by name so this file works whichever of
 * the two PRs merges first: without the switch module, onboarding stays off.
 *
 * @return bool
 */
function ihq_portal_onboarding_switch_on() {
	return function_exists( 'ihq_portal_onboarding_enabled' ) && ihq_portal_onboarding_enabled();
}

/**
 * Whether opening the Coach page should start or resume onboarding for this user.
 *
 * @param int $user_id
 * @return bool
 */
function ihq_portal_onboarding_should_launch( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return false;
	}
	if ( ! ihq_portal_onboarding_switch_on() ) {
		return false;
	}
	if ( ! ihq_portal_onboarding_created_after_release( $user_id ) ) {
		return false;
	}
	return ! ihq_portal_onboarding_is_complete( $user_id );
}

/**
 * What the onboarding layer needs to start: the saved state (an empty stage means screen 1),
 * the REST routes and the nonce they check.
 *
 * @param int $user_id
 * @return array
 */
function ihq_portal_onboarding_layer_context( $user_id ) {
	return array(
		'state'       => ihq_portal_onboarding_public_state( ihq_portal_onboarding_get_state( $user_id ) ),
		'progressUrl' => rest_url( 'ihq/v1/onboarding/progress' ),
		'completeUrl' => rest_url( 'ihq/v1/onboarding/complete' ),
		'nonce'       => wp_create_nonce( 'wp_rest' ),
	);
}

/**
 * Called by page-portal-home.php: fires the render action when the current user should be
 * onboarded, and does nothing otherwise.
 *
 * @return void
 */
function ihq_portal_onboarding_maybe_render() {
	$user_id = get_current_user_id();
	if ( ! ihq_portal_onboarding_should_launch( $user_id ) ) {
		return;
	}

	/**
	 * Renders the onboarding layer over the Coach page.
	 *
	 * @param array $context See ihq_portal_onboarding_layer_context().
	 */
	do_action( IHQ_PORTAL_ONBOARDING_RENDER_ACTION, ihq_portal_onboarding_layer_context( $user_id ) );
}

/**
 * POST /ihq/v1/onboarding/complete
 *
 * @return WP_REST_Response
 */
function ihq_portal_onboarding_handle_complete() {
	$state = ihq_portal_onboarding_mark_complete( get_current_user_id() );
	return ihq_portal_onboarding_response( ihq_portal_onboarding_public_state( $state ) );
}

/**
 * @return void
 */
function ihq_portal_onboarding_register_complete_route() {
	register_rest_route(
		'ihq/v1',
		'/onboarding/complete',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'ihq_portal_onboarding_handle_complete',
			'permission_callback' => 'ihq_portal_onboarding_permission_check',
		)
	);
}
add_action( 'rest_api_init', 'ihq_portal_onboarding_register_complete_route' );

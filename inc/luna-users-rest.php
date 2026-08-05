<?php
/**
 * Luna users REST export.
 *
 * GET /wp-json/luna/users
 *
 * Auth: HTTP Basic Auth with a WordPress username + password.
 * Access is limited to the WP user login "gary" only (password is the WP account
 * password — never hardcoded here).
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allowed caller login for Luna export.
 */
const IHQ_LUNA_USERS_ALLOWED_LOGIN = 'gary';

/**
 * Parse Basic Auth credentials from the request.
 *
 * @param WP_REST_Request $request Request.
 * @return array{username:string,password:string}|null
 */
function ihq_luna_users_parse_basic_auth( WP_REST_Request $request ) {
	$username = '';
	$password = '';

	if ( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
		$username = (string) wp_unslash( $_SERVER['PHP_AUTH_USER'] );
	}
	if ( isset( $_SERVER['PHP_AUTH_PW'] ) ) {
		$password = (string) wp_unslash( $_SERVER['PHP_AUTH_PW'] );
	}

	if ( $username === '' || $password === '' ) {
		$header = (string) $request->get_header( 'authorization' );
		if ( $header === '' && isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			$header = (string) wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] );
		}
		if ( $header === '' && isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
			$header = (string) wp_unslash( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] );
		}

		if ( stripos( $header, 'basic ' ) === 0 ) {
			$decoded = base64_decode( substr( $header, 6 ), true );
			if ( is_string( $decoded ) && strpos( $decoded, ':' ) !== false ) {
				list( $username, $password ) = explode( ':', $decoded, 2 );
			}
		}
	}

	$username = sanitize_user( $username );
	if ( $username === '' || $password === '' ) {
		return null;
	}

	return array(
		'username' => $username,
		'password' => $password,
	);
}

/**
 * permission_callback — Basic Auth as WP user "gary" only.
 *
 * @param WP_REST_Request $request Request.
 * @return true|WP_Error
 */
function ihq_luna_users_permission_check( WP_REST_Request $request ) {
	$creds = ihq_luna_users_parse_basic_auth( $request );
	if ( null === $creds ) {
		return new WP_Error(
			'luna_rest_unauthorized',
			__( 'Missing Basic Auth credentials.', 'influencer-hq' ),
			array( 'status' => 401 )
		);
	}

	$user = wp_authenticate( $creds['username'], $creds['password'] );
	if ( is_wp_error( $user ) ) {
		return new WP_Error(
			'luna_rest_unauthorized',
			__( 'Invalid username or password.', 'influencer-hq' ),
			array( 'status' => 401 )
		);
	}

	if ( strtolower( (string) $user->user_login ) !== IHQ_LUNA_USERS_ALLOWED_LOGIN ) {
		return new WP_Error(
			'luna_rest_forbidden',
			__( 'This endpoint is restricted.', 'influencer-hq' ),
			array( 'status' => 403 )
		);
	}

	wp_set_current_user( $user->ID );
	return true;
}

/**
 * Build one user's Luna export row from WP + profile AJAX meta.
 *
 * @param WP_User $user User.
 * @return array<string, mixed>
 */
function ihq_luna_users_build_row( WP_User $user ) {
	$social_handles = get_user_meta( $user->ID, '_ihq_social_handles', true );
	if ( ! is_array( $social_handles ) ) {
		$social_handles = array();
	}

	$comm_prefs = get_user_meta( $user->ID, '_ihq_comm_prefs', true );
	if ( ! is_array( $comm_prefs ) ) {
		$comm_prefs = array();
	}

	$account_visible = get_user_meta( $user->ID, '_ihq_account_visible', true );
	if ( ! is_array( $account_visible ) ) {
		$account_visible = array();
	}

	$portal_username = function_exists( 'ihq_get_portal_username' )
		? ihq_get_portal_username( $user->ID )
		: '';

	$preferred_comm = array();
	foreach ( $comm_prefs as $key => $enabled ) {
		if ( empty( $enabled ) ) {
			continue;
		}
		$key = sanitize_key( (string) $key );
		$preferred_comm[] = array(
			'method' => $key,
			'handle' => isset( $social_handles[ $key ] ) ? (string) $social_handles[ $key ] : '',
		);
	}

	return array(
		'id'               => (int) $user->ID,
		'username'         => (string) $user->user_login,
		'email'            => (string) $user->user_email,
		'display_name'     => (string) $user->display_name,
		'first_name'       => (string) get_user_meta( $user->ID, 'first_name', true ),
		'last_name'        => (string) get_user_meta( $user->ID, 'last_name', true ),
		'portal_username'  => (string) $portal_username,
		'handle'           => (string) get_user_meta( $user->ID, '_ihq_handle', true ),
		'platform_handle'  => (string) get_user_meta( $user->ID, 'platform_handle', true ),
		'country'          => (string) get_user_meta( $user->ID, '_ihq_country', true ),
		'city'             => (string) get_user_meta( $user->ID, '_ihq_city', true ),
		'timezone'         => (string) get_user_meta( $user->ID, '_ihq_timezone', true ),
		'avatar_url'       => (string) get_user_meta( $user->ID, '_ihq_avatar_url', true ),
		'preferred_comm'   => $preferred_comm,
		'comm_prefs'       => $comm_prefs,
		'social_handles'   => $social_handles,
		'comm_email'       => (string) get_user_meta( $user->ID, '_ihq_comm_email', true ),
		'celebrity'        => array(
			'movie_stars'   => (string) get_user_meta( $user->ID, '_ihq_cel_movie_stars', true ),
			'music_artists' => (string) get_user_meta( $user->ID, '_ihq_cel_music_artists', true ),
			'sports_icons'  => (string) get_user_meta( $user->ID, '_ihq_cel_sports_icons', true ),
		),
		'intl_league_team' => (string) get_user_meta( $user->ID, '_ihq_intl_league_team', true ),
		'gameplay_video_url' => (string) get_user_meta( $user->ID, '_ihq_gameplay_video_url', true ),
		'account_visible'  => $account_visible,
		'challenge_type'   => (string) get_user_meta( $user->ID, 'challenge_type', true ),
		'registration_date'=> (string) get_user_meta( $user->ID, 'registration_date', true ),
	);
}

/**
 * GET /luna/users — list influencers with profile + preferred comm.
 *
 * @return WP_REST_Response|WP_Error
 */
function ihq_luna_users_handle_get() {
	$query = new WP_User_Query(
		array(
			'number'  => -1,
			'orderby' => 'ID',
			'order'   => 'ASC',
			'fields'  => 'all',
		)
	);

	$users = $query->get_results();
	$rows  = array();

	foreach ( $users as $user ) {
		if ( ! $user instanceof WP_User ) {
			continue;
		}
		$rows[] = ihq_luna_users_build_row( $user );
	}

	return new WP_REST_Response(
		array(
			'count' => count( $rows ),
			'users' => $rows,
		),
		200
	);
}

/**
 * Register Luna REST routes.
 */
function ihq_luna_users_register_routes() {
	register_rest_route(
		'luna',
		'/users',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'ihq_luna_users_handle_get',
			'permission_callback' => 'ihq_luna_users_permission_check',
		)
	);
}
add_action( 'rest_api_init', 'ihq_luna_users_register_routes' );

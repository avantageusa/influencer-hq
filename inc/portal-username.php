<?php
/**
 * Portal username (public-facing handle) for influencer accounts.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User meta key for the public portal username.
 *
 * @return string
 */
function ihq_portal_username_meta_key() {
	return 'portal_username';
}

/**
 * Set on new influencer registration until portal_username is saved.
 *
 * @return string
 */
function ihq_portal_username_pending_meta_key() {
	return 'portal_username_pending';
}

/**
 * @param int $user_id Optional. Defaults to current user.
 * @return string Normalized portal username or empty string.
 */
function ihq_get_portal_username( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( $user_id <= 0 ) {
		return '';
	}
	return sanitize_text_field( (string) get_user_meta( $user_id, ihq_portal_username_meta_key(), true ) );
}

/**
 * @param int $user_id User ID.
 * @return bool
 */
function ihq_user_has_influencer_role_id( $user_id ) {
	$user = get_userdata( (int) $user_id );
	return $user instanceof WP_User && in_array( 'influencer', (array) $user->roles, true );
}

/**
 * Influencer accounts must pick a portal username before using the portal.
 *
 * @param int $user_id Optional. Defaults to current user.
 * @return bool
 */
function ihq_user_needs_portal_username( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( $user_id <= 0 || ! ihq_user_has_influencer_role_id( $user_id ) ) {
		return false;
	}
	if ( ihq_get_portal_username( $user_id ) !== '' ) {
		return false;
	}
	$pending = get_user_meta( $user_id, ihq_portal_username_pending_meta_key(), true );
	return $pending === '1' || $pending === 1 || $pending === true;
}

/**
 * Mark a new influencer as needing portal username setup.
 *
 * @param int $user_id User ID.
 */
function ihq_mark_portal_username_pending( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return;
	}
	update_user_meta( $user_id, ihq_portal_username_pending_meta_key(), '1' );
}

/**
 * Canonical portal account page URL (always /portal/account/, never /portal/profile/).
 *
 * @param array<string, string|int> $query_args Optional query args.
 * @return string
 */
function ihq_portal_account_url( array $query_args = array() ) {
	$url = trailingslashit( home_url( '/portal/account' ) );

	$account_page = get_page_by_path( 'portal/account' );
	if ( $account_page instanceof WP_Post && $account_page->post_status === 'publish' ) {
		$permalink = get_permalink( $account_page );
		if ( is_string( $permalink ) && $permalink !== '' ) {
			$url = $permalink;
		}
	}

	if ( ! empty( $query_args ) ) {
		$url = add_query_arg( $query_args, $url );
	}

	/**
	 * @param string                        $url        Resolved account URL.
	 * @param array<string, string|int>     $query_args Query args passed in.
	 */
	return apply_filters( 'ihq_portal_account_url', $url, $query_args );
}

/**
 * @param array<string, string|int> $query_args Optional query args.
 * @return string
 */
function ihq_portal_profile_url( array $query_args = array() ) {
	return ihq_portal_account_url( $query_args );
}

/**
 * Account URL for mandatory username setup after registration.
 *
 * @return string
 */
function ihq_portal_profile_setup_url() {
	return ihq_portal_account_url(
		array(
			'setup_portal_username' => '1',
		)
	);
}

/**
 * Legacy /portal/profile requests → /portal/account (preserve query string).
 */
function ihq_maybe_redirect_portal_profile_to_account() {
	if ( is_admin() || ! is_page() ) {
		return;
	}

	$page = get_queried_object();
	if ( ! $page instanceof WP_Post ) {
		return;
	}

	$page_uri = get_page_uri( $page );
	if ( $page_uri !== 'portal/profile' ) {
		return;
	}

	$target = ihq_portal_account_url();
	if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
		$target = $target . '?' . wp_unslash( $_SERVER['QUERY_STRING'] );
	}

	wp_safe_redirect( $target, 301 );
	exit;
}
add_action( 'template_redirect', 'ihq_maybe_redirect_portal_profile_to_account', 1 );

/**
 * Stop WordPress canonical redirect from rewriting /portal/account/ to /portal/profile/.
 *
 * @param string|false $redirect_url  Canonical redirect target.
 * @param string       $requested_url Original requested URL.
 * @return string|false
 */
function ihq_prevent_account_canonical_to_profile( $redirect_url, $requested_url ) {
	if ( ! is_string( $requested_url ) ) {
		return $redirect_url;
	}
	if ( strpos( $requested_url, '/portal/account' ) !== false ) {
		return false;
	}
	return $redirect_url;
}
add_filter( 'redirect_canonical', 'ihq_prevent_account_canonical_to_profile', 10, 2 );

/**
 * @param string $raw Raw username input.
 * @return string
 */
function ihq_normalize_portal_username( $raw ) {
	$normalized = strtolower( trim( (string) $raw ) );
	$normalized = preg_replace( '/[^a-z0-9_]/', '', $normalized );
	if ( ! is_string( $normalized ) ) {
		return '';
	}
	return substr( $normalized, 0, 30 );
}

/**
 * @param string $username  Normalized username.
 * @param int    $user_id   User saving the username.
 * @return true|WP_Error
 */
function ihq_validate_portal_username_for_save( $username, $user_id ) {
	$user_id = (int) $user_id;
	if ( $username === '' ) {
		return new WP_Error( 'empty', __( 'Please enter a username.', 'influencer-hq' ) );
	}
	if ( strlen( $username ) < 3 ) {
		return new WP_Error( 'too_short', __( 'Username must be at least 3 characters.', 'influencer-hq' ) );
	}
	if ( strlen( $username ) > 30 ) {
		return new WP_Error( 'too_long', __( 'Username must be 30 characters or fewer.', 'influencer-hq' ) );
	}

	$reserved = array( 'admin', 'root', 'support', 'help', 'influencerhq', 'influencer' );
	if ( in_array( $username, $reserved, true ) ) {
		return new WP_Error( 'reserved', __( 'That username is not available.', 'influencer-hq' ) );
	}

	$existing = get_users(
		array(
			'meta_key'   => ihq_portal_username_meta_key(),
			'meta_value' => $username,
			'number'     => 1,
			'fields'     => 'ID',
		)
	);
	if ( ! empty( $existing ) && (int) $existing[0] !== $user_id ) {
		return new WP_Error( 'taken', __( 'That username is already taken.', 'influencer-hq' ) );
	}

	return true;
}

/**
 * Redirect influencers without a portal username to the profile setup screen.
 */
function ihq_maybe_redirect_to_portal_username_setup() {
	if ( ! is_user_logged_in() || is_admin() ) {
		return;
	}
	if ( ! ihq_user_needs_portal_username() ) {
		return;
	}
	if ( ! is_page() ) {
		return;
	}

	$template = get_page_template_slug();
	$account_templates = array( 'page-portal-profile.php', 'page-portal-account.php' );
	if ( in_array( $template, $account_templates, true ) ) {
		return;
	}
	if ( $template && strpos( $template, 'page-portal-' ) === 0 ) {
		wp_safe_redirect( ihq_portal_profile_setup_url() );
		exit;
	}
}
add_action( 'template_redirect', 'ihq_maybe_redirect_to_portal_username_setup', 5 );

/**
 * AJAX: save portal username (profile setup).
 */
function ihq_save_portal_username_ajax() {
	if ( ! check_ajax_referer( 'settings_save_nonce', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'influencer-hq' ) ), 403 );
	}

	$user_id = get_current_user_id();
	if ( ! $user_id || ! ihq_user_has_influencer_role_id( $user_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Not allowed.', 'influencer-hq' ) ), 403 );
	}

	$username = ihq_normalize_portal_username( wp_unslash( $_POST['portal_username'] ?? '' ) );
	$valid    = ihq_validate_portal_username_for_save( $username, $user_id );
	if ( is_wp_error( $valid ) ) {
		wp_send_json_error( array( 'message' => $valid->get_error_message() ) );
	}

	update_user_meta( $user_id, ihq_portal_username_meta_key(), $username );
	delete_user_meta( $user_id, ihq_portal_username_pending_meta_key() );

	wp_send_json_success(
		array(
			'portal_username' => $username,
			'redirect_url'    => ihq_portal_account_url(),
		)
	);
}
add_action( 'wp_ajax_save_portal_username', 'ihq_save_portal_username_ajax' );

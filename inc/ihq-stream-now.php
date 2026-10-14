<?php
/**
 * STREAM NOW on Live Appearance (ENGR-7346): a link that opens this instance's
 * publisher app in a new tab, carrying the influencer's stored SSO code the way
 * PLAY carries it to the game (template-parts/portal-header.php). The publisher
 * redeems the code, signs the influencer in and gates streaming (eligibility,
 * bans, one live session at a time); IHQ only provides the link.
 *
 * IHQ_PUBLISHER_URL is optional (inc/ihq-env.php): absent means no button, which
 * also makes removing it the kill switch. Keep it out of IHQ_ENV_REQUIRED_KEYS
 * and IHQ_ENV_URL_KEYS — either would stop the whole site on an instance that
 * has no publisher yet.
 *
 * The link reuses today's reusable stored code, as PLAY does. If ENGR-7033 makes
 * SSO codes single-use or short-lived, PLAY and STREAM NOW both need a fresh
 * code per click and change together.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * STREAM NOW href for a user, or '' when the button must not render: a
 * logged-out visitor (user id 0), or IHQ_PUBLISHER_URL unset or not an
 * absolute https:// URL.
 *
 * @param int $user_id WordPress user ID; 0 for a logged-out visitor.
 * @return string Unescaped URL; escape with esc_url() on output.
 */
function ihq_get_stream_now_url( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return '';
	}

	$publisher_url = ihq_env_get( 'IHQ_PUBLISHER_URL' );
	if ( $publisher_url === null ) {
		return '';
	}
	if ( ! ihq_env_is_https_url( $publisher_url ) ) {
		// Not fatal: an optional key only hides its feature. The log tells an operator why the button is gone.
		error_log( '[ihq-stream-now] IHQ_PUBLISHER_URL must be an absolute https:// URL; STREAM NOW is hidden.' );
		return '';
	}

	$sso_code = ihq_get_hq_sso_code_for_user( $user_id );
	if ( $sso_code === '' ) {
		return $publisher_url;
	}
	return add_query_arg( array( 'hqSsoCode' => $sso_code ), $publisher_url );
}

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
 * Keyed hash stored in place of the code; the per-record token stops one
 * precomputed table of 10^6 hashes from covering every record.
 *
 * @param string $code         Six-digit code.
 * @param string $verify_token Random per-record token.
 * @return string
 */
function ihq_visitor_verification_code_hash( $code, $verify_token ) {
	return hash_hmac( 'sha256', (string) $code, wp_salt( 'ihq_visitor_verify' ) . (string) $verify_token );
}

/** Environments (IHQ_ENVIRONMENT) where IHQ_VISITOR_CODE_DEBUG may echo codes. */
const IHQ_VISITOR_CODE_DEBUG_ENVIRONMENTS = array( 'dev', 'qa' );

/** IHQ_VISITOR_CODE_DEBUG value that switches the code echo on. */
const IHQ_VISITOR_CODE_DEBUG_ON = '1';

/** Prefix shared with the login-code fail counters so their cleanup sweep covers these too. */
const IHQ_VISITOR_VERIFY_FAIL_PREFIX = 'ihq_login_verify_fail_';

/** Prefix shared with the login-code fail-counter expiry options. */
const IHQ_VISITOR_VERIFY_FAIL_TIMEOUT_PREFIX = 'ihq_login_verify_fail_timeout_';

/** Wrong visitor codes per email in IHQ_VISITOR_VERIFY_DAILY_WINDOW_SECONDS before a day-long lock. */
const IHQ_VISITOR_VERIFY_DAILY_MAX_FAILURES = 10;

/** Window of the long-horizon per-email failure cap: one day (literal, so the file loads before WordPress's DAY_IN_SECONDS in stub tests). */
const IHQ_VISITOR_VERIFY_DAILY_WINDOW_SECONDS = 86400;

/** Minimum gap between two codes for the same email (same as the registration send). */
const IHQ_VISITOR_ISSUE_THROTTLE_SECONDS = 45;

/**
 * Whether AJAX responses may include the visitor code (ENGR-7634).
 *
 * Developers use the echoed code to sign in as test accounts without reading
 * the mailbox. Echoing it to a logged-out caller is an account takeover, so it
 * needs IHQ_VISITOR_CODE_DEBUG = '1' in wp-config AND a non-production
 * IHQ_ENVIRONMENT. Never define the constant on prod.
 *
 * @return bool
 */
function ihq_visitor_code_debug_enabled() {
	if ( ihq_env_get( 'IHQ_VISITOR_CODE_DEBUG', '' ) !== IHQ_VISITOR_CODE_DEBUG_ON ) {
		return false;
	}

	$environment = strtolower( (string) ihq_env_get( 'IHQ_ENVIRONMENT', '' ) );
	return in_array( $environment, IHQ_VISITOR_CODE_DEBUG_ENVIRONMENTS, true );
}

/**
 * Option name of a pre-ENGR-7634 record keyed by the plaintext code. Only
 * used to delete leftovers.
 *
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
 * Whether a visitor code proves control of an existing account (ENGR-7634).
 *
 * Only a code emailed to the account's own address counts, and only for an
 * influencer — the same rule as the login-code verify. A caller who merely
 * typed someone else's email into the intent never sees that code.
 *
 * @param WP_User|false $user            Existing account for the intent email.
 * @param string        $delivered_email Address the code was emailed to.
 * @return bool
 */
function ihq_visitor_code_may_sign_in_existing_user( $user, $delivered_email ) {
	if ( ! $user instanceof WP_User || ! ihq_user_has_influencer_role( $user ) ) {
		return false;
	}

	$delivered = strtolower( trim( (string) $delivered_email ) );
	if ( $delivered === '' ) {
		return false;
	}

	return hash_equals( strtolower( trim( (string) $user->user_email ) ), $delivered );
}

/**
 * Create or log in user from visitor intent; apply competition ratings meta.
 *
 * @param array  $intent          Visitor intent.
 * @param string $delivered_email Address the verified code was emailed to. An
 *                                existing account is signed in only when this
 *                                is that account's email (ENGR-7634).
 * @return array{ok:bool,user_id?:int,existing?:bool,message?:string}
 */
function ihq_complete_visitor_intent_registration( array $intent, $delivered_email = '' ) {
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

	// A new account must also be for the address the code went to; otherwise
	// a caller's own code could register someone else's email.
	$delivered = strtolower( trim( (string) $delivered_email ) );
	if ( $delivered !== '' && ! hash_equals( strtolower( trim( $email ) ), $delivered ) ) {
		return array(
			'ok'      => false,
			'message' => __( 'We could not sign you in with this code. Please sign in from the login page.', 'influencer-hq' ),
		);
	}

	if ( email_exists( $email ) ) {
		$existing = get_user_by( 'email', $email );
		if ( ! ihq_visitor_code_may_sign_in_existing_user( $existing, $delivered_email ) ) {
			return array(
				'ok'      => false,
				'message' => __( 'We could not sign you in with this code. Please sign in from the login page.', 'influencer-hq' ),
			);
		}
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
 * Server-side portal redirect targets (never embed in lander HTML).
 *
 * @param string $context `account` or `portal_home`.
 * @return string
 */
function ihq_portal_redirect_url_for_context( $context = 'account' ) {
	if ( $context === 'portal_home' ) {
		return trailingslashit( home_url( '/portal/portal-home' ) );
	}

	return ihq_visitor_intent_portal_account_url();
}

/**
 * AJAX: return portal redirect URL after a lander interaction (no URL in page source).
 */
function ihq_handle_get_portal_redirect_url_ajax() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ihq_visitor_intent_nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'influencer-hq' ) ) );
		return;
	}

	$context = isset( $_POST['context'] ) ? sanitize_key( wp_unslash( $_POST['context'] ) ) : 'account';

	wp_send_json_success(
		array(
			'redirect_url' => ihq_portal_redirect_url_for_context( $context ),
		)
	);
}
add_action( 'wp_ajax_ihq_get_portal_redirect_url', 'ihq_handle_get_portal_redirect_url_ajax' );
add_action( 'wp_ajax_nopriv_ihq_get_portal_redirect_url', 'ihq_handle_get_portal_redirect_url_ajax' );

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
 * Email a visitor verification code (PO-3192).
 *
 * Braze is the intended delivery channel — it is the only one that can reach
 * LINE / WhatsApp / WeChat — but it is not configured on every instance, and a
 * code that never arrives reads as a dead button. Mail mirrors the sign-in code
 * message in inc/email-verification-handler.php so both codes look the same.
 *
 * @param string $email        Recipient address.
 * @param string $code         Six-digit code.
 * @param int    $minutes_left Minutes until the code expires.
 * @return bool Whether wp_mail() accepted the message.
 */
function ihq_send_visitor_verification_code_email( $email, $code, $minutes_left ) {
	$subject = __( 'Your Influencer HQ code', 'influencer-hq' );
	$message = '
	<!DOCTYPE html>
	<html><head><meta charset="UTF-8"></head>
	<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:#0a0a0a;color:#f0f0f0;">
	  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:40px 20px;">
	    <tr><td align="center">
	      <table width="560" cellpadding="0" cellspacing="0" style="background:#161612;border:1px solid rgba(240,201,58,.35);border-radius:12px;">
	        <tr><td style="padding:40px 32px;text-align:center;">
	          <h1 style="color:#F0C93A;font-size:26px;margin:0 0 16px;">Influencer HQ</h1>
	          <p style="color:#EAD9B0;font-size:16px;line-height:1.6;margin:0 0 24px;">Your code is:</p>
	          <div style="font-size:36px;font-weight:700;letter-spacing:12px;color:#fff;margin:16px 0 24px;">' . esc_html( $code ) . '</div>
	          <p style="color:#888;font-size:14px;line-height:1.6;margin:0;">This code expires in ' . (int) $minutes_left . ' minutes.</p>
	        </td></tr>
	      </table>
	    </td></tr>
	  </table>
	</body></html>';

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: Influencer HQ <verify@influencerhq.co>',
	);

	$mail_error  = null;
	$failed_hook = function ( $wp_error ) use ( &$mail_error ) {
		$mail_error = $wp_error->get_error_message();
	};

	add_action( 'wp_mail_failed', $failed_hook );
	$sent = wp_mail( $email, $subject, $message, $headers );
	remove_action( 'wp_mail_failed', $failed_hook );

	if ( ! $sent ) {
		error_log( 'IHQ visitor verification email failed for ' . $email . ': ' . ( $mail_error ? $mail_error : 'unknown error' ) );
	}

	return (bool) $sent;
}

/**
 * Deliver an already-issued verification code to the visitor.
 *
 * Failures are returned rather than logged-and-ignored: the gate shows a code
 * box as soon as this succeeds, so reporting success without delivering leaves
 * the visitor waiting for a code that is never coming.
 *
 * @param array  $intent       Visitor intent.
 * @param string $code         Six-digit code.
 * @param int    $minutes_left Minutes until the code expires.
 * @return array{ok:bool,message?:string}
 */
function ihq_deliver_visitor_verification_code( array $intent, $code, $minutes_left ) {
	$email = ihq_visitor_intent_extract_email( $intent );
	if ( $email === '' ) {
		return array(
			'ok'      => false,
			'message' => __( 'We need an email address to send your code. Please add one and try again.', 'influencer-hq' ),
		);
	}

	if ( ! ihq_send_visitor_verification_code_email( $email, $code, $minutes_left ) ) {
		return array(
			'ok'      => false,
			'message' => __( 'We could not send your code. Please try again in a moment.', 'influencer-hq' ),
		);
	}

	return array( 'ok' => true );
}

/**
 * Issue a 6-digit code tied to visitor comm_methods and deliver it by email.
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

	// Each issue now invalidates the previous code, so without a throttle anyone
	// could cancel the code a visitor is about to type (and flood the inbox).
	$throttle_email = strtolower( ihq_visitor_intent_extract_email( $intent ) );
	$throttle_key   = '';
	if ( $throttle_email !== '' ) {
		$throttle_key = 'ihq_visitor_issue_' . md5( $throttle_email );
		if ( get_transient( $throttle_key ) ) {
			return array(
				'ok'      => false,
				'message' => __( 'Please wait a moment before requesting another code.', 'influencer-hq' ),
			);
		}
		set_transient( $throttle_key, 1, IHQ_VISITOR_ISSUE_THROTTLE_SECONDS );
	}

	$fingerprint = ihq_visitor_intent_comm_fingerprint( $intent );
	$fp_key      = ihq_visitor_verification_fingerprint_option_key( $fingerprint );
	$existing    = get_option( $fp_key );

	// Pre-ENGR-7634 records kept the plaintext code here and a second record
	// under an option named after the code; drop that one when we pass by.
	if ( is_array( $existing ) && ! empty( $existing['code'] ) ) {
		delete_option( ihq_visitor_verification_code_option_key( (string) $existing['code'] ) );
	}

	// Only a hash is stored, so a repeat request cannot resend the earlier
	// code: it issues a fresh one and the earlier code stops working. That
	// still covers a code issued while delivery was broken.
	$code            = ihq_generate_visitor_verification_code();
	$verify_token    = wp_generate_password( 32, false, false );
	$expires         = time() + ihq_visitor_verification_code_ttl_seconds();
	$code_hash       = ihq_visitor_verification_code_hash( $code, $verify_token );
	$delivered_email = ihq_visitor_intent_extract_email( $intent );

	$record = array(
		'verify_token'    => $verify_token,
		'code_hash'       => $code_hash,
		'fingerprint'     => $fingerprint,
		'delivered_email' => $delivered_email,
		'intent'          => $intent,
		'created'         => time(),
		'expires'         => $expires,
	);

	update_option( $fp_key, $record, false );

	$braze_payload = ihq_build_braze_track_payload_for_visitor_intent( $intent, $button_press_url, $code );
	$minutes_left  = (int) ( ihq_visitor_verification_code_ttl_seconds() / MINUTE_IN_SECONDS );

	/*
	 * Braze delivery (PO-3192) — kept ready, not deleted. Braze is how this code
	 * reaches LINE / WhatsApp / WeChat, so this comes back on once the
	 * IHQ_BRAZE_REST_ENDPOINT and IHQ_BRAZE_TRACK_API_KEY constants are defined
	 * on every instance. Until then it posted into the void and the visitor got
	 * nothing, so email below is the delivery channel. When restoring this, add
	 * 'braze_response' back to the returned array; the AJAX handler already
	 * passes that key through when present.
	 *
	 * $braze_result = ihq_post_braze_track_payload( $braze_payload );
	 * if ( ! $braze_result['ok'] ) {
	 *     error_log( 'IHQ visitor verification Braze failed: ' . $braze_result['body'] );
	 * }
	 */

	$delivery = ihq_deliver_visitor_verification_code( $intent, $code, $minutes_left );
	if ( ! $delivery['ok'] ) {
		// Nothing was sent, so let the visitor retry straight away. The throttle
		// is set before sending (not after) to keep concurrent requests out.
		if ( $throttle_key !== '' ) {
			delete_transient( $throttle_key );
		}
		return $delivery;
	}

	return array(
		'ok'                  => true,
		'code'                => $code,
		'braze_track_payload' => $braze_payload,
		'expires_minutes'     => $minutes_left,
		'reused'              => false,
	);
}

/**
 * AJAX: issue a 6-digit visitor verification code and email it to the visitor.
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
		'expires_minutes' => $result['expires_minutes'] ?? (int) ( ihq_visitor_verification_code_ttl_seconds() / MINUTE_IN_SECONDS ),
		'reused'          => ! empty( $result['reused'] ),
		'redirect_url'    => ihq_visitor_intent_portal_account_url(),
	);
	if ( ! empty( $result['braze_response'] ) ) {
		$response['braze_response'] = $result['braze_response'];
	}
	// The Braze payload carries the code, so it only goes out with the code echo.
	if ( ihq_visitor_code_debug_enabled() ) {
		$response['braze_track_payload']     = $result['braze_track_payload'] ?? array();
		$response['registration_code_debug'] = $result['code'] ?? '';
	}

	wp_send_json_success( $response );
}

/**
 * Keys for one visitor-verify attempt counter (ENGR-7634).
 *
 * Same option family as the per-IP login-code counters, so
 * ihq_login_verify_increment_failures() and the daily expiry sweep apply.
 *
 * @param string $scope `fingerprint` or `email`.
 * @param string $value Fingerprint or lower-cased email.
 * @return array{fail:string,fail_timeout:string}
 */
function ihq_visitor_verify_attempt_keys( $scope, $value ) {
	$hash = md5( 'visitor_' . $scope . '|' . $value );

	return array(
		'fail'         => IHQ_VISITOR_VERIFY_FAIL_PREFIX . $hash,
		'fail_timeout' => IHQ_VISITOR_VERIFY_FAIL_TIMEOUT_PREFIX . $hash,
	);
}

/**
 * Attempt counters for a code: one per comm fingerprint, one per email.
 *
 * The email counter stops an attacker spreading guesses over fingerprints that
 * differ only in other comm methods but name the same inbox.
 *
 * @param string $fingerprint Comm fingerprint.
 * @param string $email       Email from the intent ('' when none).
 * @return array<int, array{fail:string,fail_timeout:string}>
 */
function ihq_visitor_verify_attempt_counters( $fingerprint, $email ) {
	$counters = array( ihq_visitor_verify_attempt_keys( 'fingerprint', $fingerprint ) );
	if ( $email !== '' ) {
		$counters[] = ihq_visitor_verify_attempt_keys( 'email', strtolower( $email ) );
	}

	return $counters;
}

/**
 * Count this attempt against every counter BEFORE the code is compared, so
 * concurrent requests from many IPs cannot all slip under the cap. A
 * successful verify clears the counters.
 *
 * @param array<int, array{fail:string,fail_timeout:string}> $counters Counter keys.
 * @return bool False when any counter is over IHQ_LOGIN_CODE_MAX_FAILURES.
 */
function ihq_visitor_verify_reserve_attempt( array $counters ) {
	$allowed = true;
	foreach ( $counters as $keys ) {
		if ( ihq_login_verify_increment_failures( $keys ) > IHQ_LOGIN_CODE_MAX_FAILURES ) {
			$allowed = false;
		}
	}

	return $allowed;
}

/**
 * @param array<int, array{fail:string,fail_timeout:string}> $counters Counter keys.
 * @return void
 */
function ihq_visitor_verify_clear_attempts( array $counters ) {
	foreach ( $counters as $keys ) {
		delete_option( $keys['fail'] );
		delete_option( $keys['fail_timeout'] );
	}
}

/**
 * Whether the caller holds the portal Turnstile pass (cookie or server-side
 * store). The code prompt only appears on Turnstile-gated portal pages, so a
 * real visitor always has one; instances without Turnstile keys skip this.
 *
 * @return bool
 */
function ihq_visitor_verify_turnstile_passed() {
	if ( ! function_exists( 'ihq_turnstile_is_configured' ) || ! ihq_turnstile_is_configured() ) {
		return true;
	}
	if ( ! function_exists( 'ihq_portal_turnstile_pass_status' ) ) {
		return false;
	}

	$status = ihq_portal_turnstile_pass_status();
	return ! empty( $status['valid'] );
}
add_action( 'wp_ajax_ihq_issue_visitor_verification', 'ihq_handle_issue_visitor_verification_ajax' );
add_action( 'wp_ajax_nopriv_ihq_issue_visitor_verification', 'ihq_handle_issue_visitor_verification_ajax' );

/**
 * Whether a stored visitor-code record can be verified: hashed code, the
 * address it was delivered to and the intent it was issued for. Pre-ENGR-7634
 * records lack the last two and are treated as expired.
 *
 * @param mixed $record Option value.
 * @return bool
 */
function ihq_visitor_verification_record_is_usable( $record ) {
	return is_array( $record )
		&& ! empty( $record['code_hash'] )
		&& ! empty( $record['verify_token'] )
		&& ! empty( $record['delivered_email'] )
		&& isset( $record['intent'] )
		&& is_array( $record['intent'] );
}

/**
 * AJAX: verify 6-digit code and register from the intent the code was issued for.
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

	if ( ! ihq_visitor_verify_turnstile_passed() ) {
		wp_send_json_error( array( 'message' => __( 'Human verification failed. Please refresh the page and try again.', 'influencer-hq' ) ) );
		return;
	}

	// Same per-IP mutex and lockout as the login-code verify (PO-3262).
	$mutex = ihq_login_verify_acquire_ip_mutex();
	if ( empty( $mutex['acquired'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Please wait a moment and try again.', 'influencer-hq' ) ) );
		return;
	}
	$lock_name = $mutex['name'];

	if ( ihq_login_verify_is_locked_out() ) {
		ihq_login_verify_json_error_releasing_mutex( $lock_name, array( 'message' => ihq_login_verify_lockout_message() ) );
		return;
	}

	$fingerprint = ihq_visitor_intent_comm_fingerprint( $parsed['intent'] );
	$counters    = ihq_visitor_verify_attempt_counters( $fingerprint, ihq_visitor_intent_extract_email( $parsed['intent'] ) );
	if ( ! ihq_visitor_verify_reserve_attempt( $counters ) ) {
		ihq_login_verify_json_error_releasing_mutex( $lock_name, array( 'message' => ihq_login_verify_lockout_message() ) );
		return;
	}

	$fp_key = ihq_visitor_verification_fingerprint_option_key( $fingerprint );
	$record = get_option( $fp_key );
	if ( ! ihq_visitor_verification_record_is_usable( $record ) ) {
		ihq_login_verify_json_error_releasing_mutex(
			$lock_name,
			array( 'message' => __( 'Invalid or expired code. Check your communication method and try again.', 'influencer-hq' ) )
		);
		return;
	}

	if ( time() > (int) ( $record['expires'] ?? 0 ) ) {
		delete_option( $fp_key );
		ihq_login_verify_json_error_releasing_mutex(
			$lock_name,
			array( 'message' => __( 'This code has expired. Please request a new code from the lander.', 'influencer-hq' ) )
		);
		return;
	}

	$daily_keys = ihq_visitor_verify_attempt_keys( 'email_daily', strtolower( (string) $record['delivered_email'] ) );
	if ( (int) get_option( $daily_keys['fail'], 0 ) >= IHQ_VISITOR_VERIFY_DAILY_MAX_FAILURES ) {
		ihq_login_verify_json_error_releasing_mutex( $lock_name, array( 'message' => ihq_login_verify_lockout_message() ) );
		return;
	}

	$try_hash = ihq_visitor_verification_code_hash( $code_raw, (string) $record['verify_token'] );
	if ( ! hash_equals( (string) $record['code_hash'], $try_hash ) ) {
		ihq_login_verify_increment_failures( $daily_keys, IHQ_VISITOR_VERIFY_DAILY_WINDOW_SECONDS );
		// After the cap the code is burnt: the next try needs a fresh code,
		// which lands in the owner's inbox and tells them someone is trying.
		if ( (int) get_option( $counters[0]['fail'], 0 ) >= IHQ_LOGIN_CODE_MAX_FAILURES ) {
			delete_option( $fp_key );
		}
		$failure = ihq_login_verify_record_failure();
		$message = ! empty( $failure['locked'] )
			? ihq_login_verify_lockout_message()
			: __( 'That code does not match. Check your communication method and try again.', 'influencer-hq' );
		ihq_login_verify_json_error_releasing_mutex( $lock_name, array( 'message' => $message ) );
		return;
	}

	// Register from the intent the code was issued for. The posted intent only
	// has to match its fingerprint, and different comm_methods (duplicate or
	// unsanitized keys) can share one fingerprint yet yield another email.
	$intent = $record['intent'];
	if ( $parsed['country_iso'] !== '' ) {
		$intent['country_iso'] = function_exists( 'ihq_normalize_country_iso_alpha2' )
			? ihq_normalize_country_iso_alpha2( $parsed['country_iso'] )
			: strtoupper( $parsed['country_iso'] );
	}

	$result = ihq_complete_visitor_intent_registration( $intent, (string) $record['delivered_email'] );
	if ( empty( $result['ok'] ) ) {
		ihq_login_verify_json_error_releasing_mutex(
			$lock_name,
			array( 'message' => $result['message'] ?? __( 'Registration failed.', 'influencer-hq' ) )
		);
		return;
	}

	delete_option( $fp_key );
	ihq_visitor_verify_clear_attempts( array_merge( $counters, array( $daily_keys ) ) );
	ihq_login_verify_clear_failures();
	ihq_login_verify_release_ip_mutex( $lock_name );

	// The intent is used up and the visitor is signed in. Left in place, the
	// 30-day cookies outlive a session that ends without a logout and reopen
	// the code prompt on the next Login (ENGR-7260).
	if ( function_exists( 'ihq_visitor_logout_clear_cookies' ) ) {
		ihq_visitor_logout_clear_cookies();
	}

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
 * AJAX: TEST REGISTRY — alias for issue visitor verification that echoes the
 * code (developer sign-in as test accounts). Off unless
 * ihq_visitor_code_debug_enabled() (ENGR-7634); nothing in the theme calls it.
 */
function ihq_handle_test_registry_braze_ajax() {
	if ( ! ihq_visitor_code_debug_enabled() ) {
		wp_send_json_error( array( 'message' => __( 'Not available.', 'influencer-hq' ) ) );
		return;
	}

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

	$ihq_visitor_intent_localize = array(
		'cookieName'         => ihq_visitor_intent_cookie_name(),
		'cookieDays'         => 30,
		'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
		'nonce'              => wp_create_nonce( 'ihq_visitor_intent_nonce' ),
		'codeExpiresMinutes' => (int) ( ihq_visitor_verification_code_ttl_seconds() / MINUTE_IN_SECONDS ),
		'line'               => function_exists( 'ihq_line_enrollment_js_config' )
			? ihq_line_enrollment_js_config()
			: array(),
	);

	if ( is_page_template( 'page-lander.php' ) ) {
		$ihq_visitor_intent_localize['isLander'] = true;
	} else {
		$ihq_visitor_intent_localize['accountUrl'] = ihq_visitor_intent_portal_account_url();
	}

	wp_localize_script(
		'ihq-visitor-intent',
		'IHQ_VISITOR_INTENT',
		$ihq_visitor_intent_localize
	);
}
add_action( 'wp_enqueue_scripts', 'ihq_enqueue_visitor_intent_assets', 25 );

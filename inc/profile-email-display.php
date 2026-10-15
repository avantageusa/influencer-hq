<?php
/**
 * What the Profile page's Account card shows for email and channel (ENGR-7611).
 *
 * An influencer who registered without an email has a stand-in address
 * (inc/placeholder-email.php). That address is never shown: the Email row is
 * empty and offers to add an email, and the channel they registered with gets
 * its own row. Same rule as the game's settings (PO-3155, PO-3308).
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display labels for the registration channel keys (comm_methods).
 *
 * @return array<string, string>
 */
function ihq_profile_channel_labels() {
	return array(
		'kakaotalk' => 'KakaoTalk',
		'line'      => 'LINE',
		'sms'       => 'SMS',
		'telegram'  => 'Telegram',
		'wechat'    => 'WeChat',
		'whatsapp'  => 'WhatsApp',
		'zalo'      => 'Zalo',
	);
}

/**
 * The address the Email row shows: the user's own, or empty for a stand-in.
 *
 * @param string $email Stored user_email.
 * @return string
 */
function ihq_profile_email_row_value( $email ) {
	$email = (string) $email;
	if ( function_exists( 'ihq_is_placeholder_email' ) && ihq_is_placeholder_email( $email ) ) {
		return '';
	}
	return $email;
}

/**
 * The channel an influencer without a real email registered with, for its own
 * Profile row; null when the account has a real email or no chat channel.
 *
 * @param int    $user_id WordPress user ID.
 * @param string $email   Stored user_email.
 * @return array{label:string,value:string}|null
 */
function ihq_profile_registered_channel( $user_id, $email ) {
	if ( ihq_profile_email_row_value( $email ) !== '' ) {
		return null;
	}

	$methods = get_user_meta( $user_id, 'communication_methods', true );
	if ( ! is_array( $methods ) ) {
		$methods = array();
	}
	$labels = ihq_profile_channel_labels();

	// Registration stores the first chosen method as preferred; fall back to
	// the first chat channel in case only the methods map was written.
	$key   = sanitize_key( (string) get_user_meta( $user_id, 'preferred_communication', true ) );
	$value = isset( $methods[ $key ] ) ? (string) $methods[ $key ] : '';
	if ( ! isset( $labels[ $key ] ) ) {
		$key = '';
		foreach ( $methods as $method_key => $method_value ) {
			$normalized_key = sanitize_key( (string) $method_key );
			if ( isset( $labels[ $normalized_key ] ) ) {
				$key   = $normalized_key;
				$value = (string) $method_value;
				break;
			}
		}
	}
	if ( $key === '' ) {
		return null;
	}

	// LINE's phase-1 consent marker is not a handle the influencer typed.
	if ( $key === 'line' && defined( 'IHQ_LINE_CONSENT_COMM_MARKER' ) && $value === IHQ_LINE_CONSENT_COMM_MARKER ) {
		$value = '';
	}

	return array(
		'label' => $labels[ $key ],
		'value' => $value,
	);
}

<?php
/**
 * Map lander / visitor-intent comm_methods to IHQ start-session marketing ID fields.
 *
 * Only selected modal methods with a non-empty handle are included — no isMarketing* booleans.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Modal comm_methods key → start-session / Braze marketing ID field.
 *
 * @return array<string, string>
 */
function ihq_comm_method_marketing_id_field_map() {
	return array(
		'whatsapp'  => 'marketingWhatsAppId',
		'kakaotalk' => 'marketingKakaoTalkId',
		'line'      => 'marketingLineId',
		'telegram'  => 'marketingTelegramId',
		'wechat'    => 'marketingWeChatId',
	);
}

/**
 * @param string $comm_key  Modal comm key.
 * @param string $value     Handle entered in modal.
 * @return string Sanitized value for API.
 */
function ihq_sanitize_marketing_comm_id_value( $comm_key, $value ) {
	$value = sanitize_text_field( trim( (string) $value ) );
	if ( $value === '' ) {
		return '';
	}
	if ( $comm_key === 'telegram' ) {
		return sanitize_text_field( ltrim( $value, '@' ) );
	}
	return $value;
}

/**
 * Build sparse marketing ID fields — only keys with entered values.
 *
 * @param array<string, string> $comm_methods Modal selections (key => handle).
 * @return array<string, string>
 */
function ihq_build_marketing_notifications_payload_from_comm_methods( array $comm_methods, array $social_handles = array() ) {
	unset( $social_handles );

	$field_map = ihq_comm_method_marketing_id_field_map();
	$out       = array();

	foreach ( $comm_methods as $raw_key => $raw_value ) {
		$key = strtolower( sanitize_key( (string) $raw_key ) );
		if ( ! isset( $field_map[ $key ] ) ) {
			continue;
		}
		$value = ihq_sanitize_marketing_comm_id_value( $key, $raw_value );
		if ( $value === '' ) {
			continue;
		}
		$out[ $field_map[ $key ] ] = $value;
	}

	return $out;
}

/**
 * Flatten visitor intent for Braze attributes (marketing IDs + optional modal context).
 *
 * @param array<string, mixed> $intent Visitor intent cookie / verification record.
 * @return array<string, mixed>
 */
function ihq_visitor_intent_braze_attribute_extras( array $intent ) {
	$comm_methods = isset( $intent['comm_methods'] ) && is_array( $intent['comm_methods'] )
		? $intent['comm_methods']
		: array();

	$extras = ihq_build_marketing_notifications_payload_from_comm_methods( $comm_methods );

	$platform_handle = function_exists( 'ihq_visitor_intent_build_platform_handle' )
		? ihq_visitor_intent_build_platform_handle( $intent )
		: '';
	if ( $platform_handle !== '' ) {
		$extras['platform_handle'] = $platform_handle;
	}

	$challenge_type = isset( $intent['challenge_type'] ) ? sanitize_text_field( (string) $intent['challenge_type'] ) : '';
	if ( $challenge_type !== '' ) {
		$extras['challenge_type'] = $challenge_type;
	}

	$captured_from = isset( $intent['captured_from'] ) ? sanitize_text_field( (string) $intent['captured_from'] ) : '';
	if ( $captured_from !== '' ) {
		$extras['captured_from'] = $captured_from;
	}

	$gate_id = isset( $intent['gate_id'] ) ? sanitize_key( (string) $intent['gate_id'] ) : '';
	if ( $gate_id !== '' ) {
		$extras['gate_id'] = $gate_id;
	}

	$social_handles = isset( $intent['social_handles'] ) && is_array( $intent['social_handles'] ) ? $intent['social_handles'] : array();
	if ( ! empty( $social_handles ) ) {
		$extras['social_handles_json'] = wp_json_encode( $social_handles );
	}

	if ( ! empty( $intent['competition_ratings'] ) && is_array( $intent['competition_ratings'] ) ) {
		$extras['competition_ratings_json'] = wp_json_encode( $intent['competition_ratings'] );
	}

	return $extras;
}

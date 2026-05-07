<?php
/**
 * Cloudflare Turnstile server-side verification.
 *
 * Keys default below when not set in wp-config.php (allows overriding per environment).
 * For production, prefer wp-config.php defines so secrets stay out of the theme tree if possible.
 *
 * @package Avantage_Baccarat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'CF_TURNSTILE_SITE_KEY' ) ) {
	define( 'CF_TURNSTILE_SITE_KEY', '0x4AAAAAADKv2W3zynFOUECr' );
}

if ( ! defined( 'CF_TURNSTILE_SECRET_KEY' ) ) {
	define( 'CF_TURNSTILE_SECRET_KEY', '0x4AAAAAADKv2ZBP9ERy90sZW2gbanCYB3E' );
}

/** HTTPS POST timeout for siteverify (seconds). */
const IHQ_TURNSTILE_SITEVERIFY_TIMEOUT = 10;

/**
 * Whether both Turnstile keys are set (widget + enforcement enabled).
 *
 * @return bool
 */
function ihq_turnstile_is_configured() {
	return defined( 'CF_TURNSTILE_SITE_KEY' ) && CF_TURNSTILE_SITE_KEY !== ''
		&& defined( 'CF_TURNSTILE_SECRET_KEY' ) && CF_TURNSTILE_SECRET_KEY !== '';
}

/**
 * Verify a Turnstile token with Cloudflare.
 *
 * @param string $token Value from POST cf-turnstile-response.
 * @return array{ success: bool, skipped?: bool, error_codes?: string[] }
 */
function ihq_turnstile_verify_response( $token ) {
	if ( ! ihq_turnstile_is_configured() ) {
		return array(
			'success' => true,
			'skipped' => true,
		);
	}

	$token = is_string( $token ) ? trim( $token ) : '';
	if ( $token === '' ) {
		return array(
			'success'      => false,
			'error_codes'  => array( 'missing-input-response' ),
		);
	}

	$remote_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	$response = wp_remote_post(
		'https://challenges.cloudflare.com/turnstile/v0/siteverify',
		array(
			'timeout' => IHQ_TURNSTILE_SITEVERIFY_TIMEOUT,
			'body'    => array(
				'secret'   => CF_TURNSTILE_SECRET_KEY,
				'response' => $token,
				'remoteip' => $remote_ip,
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return array(
			'success'     => false,
			'error_codes' => array( 'internal-error' ),
		);
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( $code !== 200 || ! is_array( $body ) ) {
		return array(
			'success'     => false,
			'error_codes' => array( 'bad-json' ),
		);
	}

	$ok = ! empty( $body['success'] );
	return array(
		'success'     => $ok,
		'error_codes' => isset( $body['error-codes'] ) && is_array( $body['error-codes'] ) ? $body['error-codes'] : array(),
	);
}

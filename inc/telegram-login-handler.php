<?php
/**
 * Telegram Login (OIDC widget) — server support for the lander / portal modals.
 *
 * Setup (see https://core.telegram.org/bots/telegram-login ):
 * 1. Create a bot in @BotFather; set profile name/logo to match the site.
 * 2. Bot Settings → Web Login → add your site origin (e.g. https://example.com) to Allowed URLs.
 * 3. Copy the numeric Client ID from BotFather and define it in wp-config.php:
 *    define( 'IHQ_TELEGRAM_LOGIN_CLIENT_ID', '123456789' );
 * 4. If the site sends Cross-Origin-Opener-Policy: same-origin, change it to
 *    same-origin-allow-popups or remove it — otherwise the Telegram popup cannot post back.
 *
 * @package Avantage_Baccarat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** JWKS cache TTL (seconds). */
const IHQ_TELEGRAM_JWKS_CACHE_SECONDS = 3600;

/** Issuer claim for Telegram OIDC ID tokens. */
const IHQ_TELEGRAM_OIDC_ISSUER = 'https://oauth.telegram.org';

/**
 * Numeric Telegram Login client ID (bot), or empty if disabled.
 *
 * @return string Digits only, or empty string.
 */
function ihq_telegram_login_get_client_id() {
	if ( defined( 'IHQ_TELEGRAM_LOGIN_CLIENT_ID' ) && IHQ_TELEGRAM_LOGIN_CLIENT_ID !== '' && IHQ_TELEGRAM_LOGIN_CLIENT_ID !== '0' ) {
		$raw = (string) IHQ_TELEGRAM_LOGIN_CLIENT_ID;
		return preg_match( '/^\d+$/', $raw ) ? $raw : '';
	}
	return '';
}

/**
 * Base64url decode (no padding required).
 *
 * @param string $data Base64url string.
 * @return string|false Binary or false on failure.
 */
function ihq_telegram_base64url_decode( $data ) {
	$padded = strtr( $data, '-_', '+/' );
	$pad    = strlen( $padded ) % 4;
	if ( $pad > 0 ) {
		$padded .= str_repeat( '=', 4 - $pad );
	}
	$out = base64_decode( $padded, true );
	return false !== $out ? $out : false;
}

/**
 * ASN.1 DER length prefix for INTEGER / SEQUENCE payloads.
 *
 * @param int $length Byte length.
 * @return string Binary length encoding.
 */
function ihq_telegram_asn1_length( $length ) {
	if ( $length <= 0x7F ) {
		return chr( $length );
	}
	$packed = pack( 'N', $length );
	$packed = ltrim( $packed, "\x00" );
	if ( '' === $packed ) {
		$packed = "\x00";
	}
	return chr( 0x80 | strlen( $packed ) ) . $packed;
}

/**
 * Build PEM-encoded RSA public key from JWK modulus (n) and exponent (e).
 *
 * @param string $n_b64 Base64url modulus.
 * @param string $e_b64 Base64url exponent.
 * @return string|false PEM or false.
 */
function ihq_telegram_jwk_rsa_to_pem( $n_b64, $e_b64 ) {
	$modulus        = ihq_telegram_base64url_decode( $n_b64 );
	$public_exponent = ihq_telegram_base64url_decode( $e_b64 );
	if ( false === $modulus || false === $public_exponent || '' === $modulus || '' === $public_exponent ) {
		return false;
	}

	$mod_enc = pack( 'Ca*a*', 0x02, ihq_telegram_asn1_length( strlen( $modulus ) ), $modulus );
	$exp_enc = pack( 'Ca*a*', 0x02, ihq_telegram_asn1_length( strlen( $public_exponent ) ), $public_exponent );

	$inner = $mod_enc . $exp_enc;
	$seq   = pack( 'Ca*a*', 0x30, ihq_telegram_asn1_length( strlen( $inner ) ), $inner );

	$rsa_oid = hex2bin( '300d06092a864886f70d0101010500' );
	if ( false === $rsa_oid ) {
		return false;
	}

	$bit_string = chr( 0x03 ) . ihq_telegram_asn1_length( strlen( $seq ) + 1 ) . chr( 0x00 ) . $seq;
	$outer      = pack( 'Ca*a*', 0x30, ihq_telegram_asn1_length( strlen( $rsa_oid ) + strlen( $bit_string ) ), $rsa_oid . $bit_string );

	$pem  = "-----BEGIN PUBLIC KEY-----\n";
	$pem .= chunk_split( base64_encode( $outer ), 64, "\n" );
	$pem .= '-----END PUBLIC KEY-----';

	return $pem;
}

/**
 * Fetch Telegram OIDC JWKS (cached).
 *
 * @return array<string,mixed>|WP_Error Decoded JWKS or error.
 */
function ihq_telegram_fetch_jwks() {
	$cache_key = 'ihq_telegram_oidc_jwks';
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) && isset( $cached['keys'] ) ) {
		return $cached;
	}

	$response = wp_remote_get(
		'https://oauth.telegram.org/.well-known/jwks.json',
		array(
			'timeout' => 10,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	if ( 200 !== $code || '' === $body ) {
		return new WP_Error( 'jwks_http', __( 'Could not load Telegram signing keys', 'avantage-baccarat' ) );
	}

	$decoded = json_decode( $body, true );
	if ( ! is_array( $decoded ) || empty( $decoded['keys'] ) ) {
		return new WP_Error( 'jwks_parse', __( 'Invalid Telegram signing keys response', 'avantage-baccarat' ) );
	}

	set_transient( $cache_key, $decoded, IHQ_TELEGRAM_JWKS_CACHE_SECONDS );
	return $decoded;
}

/**
 * Verify Telegram OIDC ID token (RS256) and return claims as array.
 *
 * @param string $jwt             Raw JWT string.
 * @param string $expected_aud    Bot numeric ID (string).
 * @return array<string,mixed>|WP_Error Claims on success.
 */
function ihq_verify_telegram_oidc_id_token( $jwt, $expected_aud ) {
	$jwt = trim( (string) $jwt );
	if ( '' === $jwt ) {
		return new WP_Error( 'empty_token', __( 'Missing ID token', 'avantage-baccarat' ) );
	}

	$parts = explode( '.', $jwt );
	if ( count( $parts ) !== 3 ) {
		return new WP_Error( 'malformed', __( 'Invalid ID token format', 'avantage-baccarat' ) );
	}

	$header_raw = ihq_telegram_base64url_decode( $parts[0] );
	$payload_raw = ihq_telegram_base64url_decode( $parts[1] );
	$sig         = ihq_telegram_base64url_decode( $parts[2] );

	if ( false === $header_raw || false === $payload_raw || false === $sig ) {
		return new WP_Error( 'decode', __( 'Invalid ID token encoding', 'avantage-baccarat' ) );
	}

	$header = json_decode( $header_raw, true );
	if ( ! is_array( $header ) || empty( $header['alg'] ) || empty( $header['kid'] ) ) {
		return new WP_Error( 'header', __( 'Invalid ID token header', 'avantage-baccarat' ) );
	}

	if ( 'RS256' !== $header['alg'] ) {
		return new WP_Error( 'alg', __( 'Unexpected signing algorithm', 'avantage-baccarat' ) );
	}

	$jwks = ihq_telegram_fetch_jwks();
	if ( is_wp_error( $jwks ) ) {
		return $jwks;
	}

	$jwk_match = null;
	foreach ( $jwks['keys'] as $k ) {
		if ( is_array( $k ) && isset( $k['kid'] ) && $k['kid'] === $header['kid'] && isset( $k['n'], $k['e'] ) && ( ! isset( $k['kty'] ) || 'RSA' === $k['kty'] ) ) {
			$jwk_match = $k;
			break;
		}
	}

	if ( null === $jwk_match ) {
		return new WP_Error( 'kid', __( 'Signing key not found', 'avantage-baccarat' ) );
	}

	$pem = ihq_telegram_jwk_rsa_to_pem( $jwk_match['n'], $jwk_match['e'] );
	if ( false === $pem ) {
		return new WP_Error( 'pem', __( 'Could not build public key', 'avantage-baccarat' ) );
	}

	$pub = openssl_pkey_get_public( $pem );
	if ( false === $pub ) {
		return new WP_Error( 'openssl', __( 'Invalid public key material', 'avantage-baccarat' ) );
	}

	$signing_input = $parts[0] . '.' . $parts[1];
	$ok = openssl_verify( $signing_input, $sig, $pub, OPENSSL_ALGO_SHA256 );

	if ( 1 !== $ok ) {
		return new WP_Error( 'sig', __( 'ID token signature could not be verified', 'avantage-baccarat' ) );
	}

	$claims = json_decode( $payload_raw, true );
	if ( ! is_array( $claims ) ) {
		return new WP_Error( 'claims', __( 'Invalid ID token payload', 'avantage-baccarat' ) );
	}

	if ( empty( $claims['iss'] ) || IHQ_TELEGRAM_OIDC_ISSUER !== $claims['iss'] ) {
		return new WP_Error( 'iss', __( 'Invalid token issuer', 'avantage-baccarat' ) );
	}

	$aud = isset( $claims['aud'] ) ? (string) $claims['aud'] : '';
	if ( $aud !== (string) $expected_aud ) {
		return new WP_Error( 'aud', __( 'Invalid token audience', 'avantage-baccarat' ) );
	}

	$now = time();
	$leeway = 90;
	if ( isset( $claims['exp'] ) && (int) $claims['exp'] < ( $now - $leeway ) ) {
		return new WP_Error( 'exp', __( 'ID token has expired', 'avantage-baccarat' ) );
	}
	if ( isset( $claims['iat'] ) && (int) $claims['iat'] > ( $now + $leeway ) ) {
		return new WP_Error( 'iat', __( 'ID token is not yet valid', 'avantage-baccarat' ) );
	}

	return $claims;
}

/**
 * AJAX: issue a one-time nonce for Telegram.Login (browser SDK).
 */
function ihq_handle_telegram_login_nonce_ajax() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ihq_telegram_login_pubkey' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid security token', 'avantage-baccarat' ) ) );
		return;
	}

	if ( '' === ihq_telegram_login_get_client_id() ) {
		wp_send_json_error( array( 'message' => __( 'Telegram login is not configured', 'avantage-baccarat' ) ) );
		return;
	}

	$server_nonce = wp_generate_password( 48, false, false );
	$transient_key = 'ihq_tg_nonce_' . md5( $server_nonce );
	set_transient( $transient_key, array( 'issued' => time() ), 15 * MINUTE_IN_SECONDS );

	wp_send_json_success(
		array(
			'server_nonce' => $server_nonce,
		)
	);
}
add_action( 'wp_ajax_ihq_telegram_login_nonce', 'ihq_handle_telegram_login_nonce_ajax' );
add_action( 'wp_ajax_nopriv_ihq_telegram_login_nonce', 'ihq_handle_telegram_login_nonce_ajax' );

/**
 * AJAX: verify ID token from Telegram Login widget; return normalized @username.
 */
function ihq_handle_verify_telegram_id_token_ajax() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ihq_telegram_login_pubkey' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid security token', 'avantage-baccarat' ) ) );
		return;
	}

	$client_id = ihq_telegram_login_get_client_id();
	if ( '' === $client_id ) {
		wp_send_json_error( array( 'message' => __( 'Telegram login is not configured', 'avantage-baccarat' ) ) );
		return;
	}

	$id_token = isset( $_POST['id_token'] ) ? sanitize_text_field( wp_unslash( $_POST['id_token'] ) ) : '';
	if ( '' === $id_token ) {
		wp_send_json_error( array( 'message' => __( 'Missing ID token', 'avantage-baccarat' ) ) );
		return;
	}

	$claims = ihq_verify_telegram_oidc_id_token( $id_token, $client_id );
	if ( is_wp_error( $claims ) ) {
		wp_send_json_error( array( 'message' => $claims->get_error_message() ) );
		return;
	}

	$nonce = isset( $claims['nonce'] ) ? (string) $claims['nonce'] : '';
	if ( '' === $nonce ) {
		wp_send_json_error( array( 'message' => __( 'Invalid login session', 'avantage-baccarat' ) ) );
		return;
	}

	$transient_key = 'ihq_tg_nonce_' . md5( $nonce );
	$slot          = get_transient( $transient_key );
	if ( ! is_array( $slot ) ) {
		wp_send_json_error( array( 'message' => __( 'Login session expired. Please try again', 'avantage-baccarat' ) ) );
		return;
	}
	delete_transient( $transient_key );

	$preferred = isset( $claims['preferred_username'] ) ? sanitize_text_field( (string) $claims['preferred_username'] ) : '';
	if ( '' === $preferred ) {
		wp_send_json_error( array( 'message' => __( 'Telegram did not return a username for this account', 'avantage-baccarat' ) ) );
		return;
	}

	$handle = '@' . ltrim( $preferred, '@' );

	wp_send_json_success(
		array(
			'telegram_username' => $handle,
		)
	);
}
add_action( 'wp_ajax_ihq_verify_telegram_id_token', 'ihq_handle_verify_telegram_id_token_ajax' );
add_action( 'wp_ajax_nopriv_ihq_verify_telegram_id_token', 'ihq_handle_verify_telegram_id_token_ajax' );

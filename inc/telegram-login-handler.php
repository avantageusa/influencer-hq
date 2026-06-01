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
const IHQ_TELEGRAM_REG_SESSION_TTL = 20 * MINUTE_IN_SECONDS;

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
 * Bot token used for Telegram Bot API sendMessage.
 *
 * @return string
 */
function ihq_telegram_login_get_bot_token() {
	if ( defined( 'IHQ_TELEGRAM_BOT_TOKEN' ) && is_string( IHQ_TELEGRAM_BOT_TOKEN ) ) {
		return trim( (string) IHQ_TELEGRAM_BOT_TOKEN );
	}
	return '';
}

/**
 * Return Telegram registration session data by token.
 *
 * @param string $session_token Session token issued after Telegram auth.
 * @return array<string,mixed>|null
 */
function ihq_get_telegram_registration_session( $session_token ) {
	$session_token = trim( (string) $session_token );
	if ( '' === $session_token ) {
		return null;
	}
	$key  = 'ihq_tg_reg_' . md5( $session_token );
	$data = get_transient( $key );
	return is_array( $data ) ? $data : null;
}

/**
 * Send direct Telegram message via Bot API.
 *
 * @param int    $telegram_user_id Telegram user id.
 * @param string $text             Plaintext message.
 * @return true|WP_Error
 */
function ihq_telegram_send_direct_message( $telegram_user_id, $text ) {
	$bot_token = ihq_telegram_login_get_bot_token();
	if ( '' === $bot_token ) {
		return new WP_Error( 'telegram_bot_token_missing', __( 'Telegram bot token is not configured', 'avantage-baccarat' ) );
	}

	$payload = array(
		'chat_id' => (int) $telegram_user_id,
		'text'    => (string) $text,
	);

	$response = wp_remote_post(
		'https://api.telegram.org/bot' . rawurlencode( $bot_token ) . '/sendMessage',
		array(
			'timeout' => 12,
			'body'    => $payload,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$json = json_decode( $body, true );
	if ( $code < 200 || $code >= 300 || ! is_array( $json ) || empty( $json['ok'] ) ) {
		return new WP_Error( 'telegram_send_failed', __( 'Could not deliver the code to Telegram. Please ensure you started the bot, then try again.', 'avantage-baccarat' ) );
	}

	return true;
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
 * AJAX: verify ID token from Telegram Login widget; return normalized profile + session token.
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
		wp_send_json_error(
			array(
				'message' => __( 'Telegram login succeeded, but this Telegram account has no @username set. Please set a Telegram username in Telegram Settings > Edit Profile > Username, then try again (or choose Email).', 'avantage-baccarat' ),
			)
		);
		return;
	}

	$handle = '@' . ltrim( $preferred, '@' );
	$telegram_user_id = isset( $claims['id'] ) ? (int) $claims['id'] : 0;
	if ( $telegram_user_id <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'Telegram did not return a valid user id', 'avantage-baccarat' ) ) );
		return;
	}

	$display_name = isset( $claims['name'] ) ? sanitize_text_field( (string) $claims['name'] ) : '';
	$first_name   = '';
	$last_name    = '';
	if ( '' !== $display_name ) {
		$name_parts = preg_split( '/\s+/', $display_name );
		if ( is_array( $name_parts ) && ! empty( $name_parts ) ) {
			$first_name = (string) array_shift( $name_parts );
			$last_name  = trim( implode( ' ', $name_parts ) );
		}
	}

	$reg_session_token = wp_generate_password( 40, false, false );
	$reg_session_key   = 'ihq_tg_reg_' . md5( $reg_session_token );
	set_transient(
		$reg_session_key,
		array(
			'telegram_user_id'  => $telegram_user_id,
			'telegram_username' => $handle,
			'first_name'        => $first_name,
			'last_name'         => $last_name,
			'issued_at'         => time(),
		),
		IHQ_TELEGRAM_REG_SESSION_TTL
	);

	wp_send_json_success(
		array(
			'telegram_username'      => $handle,
			'telegram_first_name'    => $first_name,
			'telegram_last_name'     => $last_name,
			'telegram_session_token' => $reg_session_token,
		)
	);
}
add_action( 'wp_ajax_ihq_verify_telegram_id_token', 'ihq_handle_verify_telegram_id_token_ajax' );
add_action( 'wp_ajax_nopriv_ihq_verify_telegram_id_token', 'ihq_handle_verify_telegram_id_token_ajax' );

/**
 * Build a deterministic fake email from Telegram username/id.
 *
 * @param string $telegram_username Username with/without @.
 * @param int    $telegram_user_id  Numeric Telegram user id.
 * @return string
 */
function ihq_telegram_build_fake_email( $telegram_username, $telegram_user_id ) {
	$base = strtolower( ltrim( (string) $telegram_username, '@' ) );
	$base = preg_replace( '/[^a-z0-9._-]/', '', $base );
	if ( '' === $base ) {
		$base = 'tg' . (int) $telegram_user_id;
	}
	$candidate = $base . '@email_telegram.com';
	if ( ! email_exists( $candidate ) ) {
		return $candidate;
	}
	$index = 2;
	while ( $index < 10000 ) {
		$try = $base . $index . '@email_telegram.com';
		if ( ! email_exists( $try ) ) {
			return $try;
		}
		++$index;
	}
	return 'tg' . (int) $telegram_user_id . '@email_telegram.com';
}

/**
 * AJAX: directly register/log-in user from verified Telegram session.
 */
function ihq_handle_register_telegram_user_ajax() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ihq_telegram_login_pubkey' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid security token', 'avantage-baccarat' ) ) );
		return;
	}
	$session_token = isset( $_POST['telegram_session_token'] ) ? sanitize_text_field( wp_unslash( $_POST['telegram_session_token'] ) ) : '';
	$session       = ihq_get_telegram_registration_session( $session_token );
	if ( ! is_array( $session ) || empty( $session['telegram_user_id'] ) || empty( $session['telegram_username'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Telegram session expired. Please authenticate again.', 'avantage-baccarat' ) ) );
		return;
	}

	$telegram_user_id  = (int) $session['telegram_user_id'];
	$telegram_username = (string) $session['telegram_username'];
	$first_name        = isset( $session['first_name'] ) ? sanitize_text_field( (string) $session['first_name'] ) : '';
	$last_name         = isset( $session['last_name'] ) ? sanitize_text_field( (string) $session['last_name'] ) : '';
	$challenge_type    = isset( $_POST['challenge_type'] ) ? sanitize_text_field( wp_unslash( $_POST['challenge_type'] ) ) : '';
	$platform_handle   = isset( $_POST['platform_handle'] ) ? sanitize_text_field( wp_unslash( $_POST['platform_handle'] ) ) : $telegram_username;
	$country_iso       = isset( $_POST['country_iso'] ) ? sanitize_text_field( wp_unslash( $_POST['country_iso'] ) ) : '';

	$email = ihq_telegram_build_fake_email( $telegram_username, $telegram_user_id );
	$created = ihq_create_influencer_user_from_registration_data(
		array(
			'email'           => $email,
			'first_name'      => $first_name,
			'last_name'       => $last_name,
			'platform_handle' => $platform_handle,
			'comm_methods'    => array( 'telegram' => $telegram_username ),
			'challenge_type'  => $challenge_type,
			'country_iso'     => $country_iso,
			'telegram_user_id'=> $telegram_user_id,
		)
	);
	if ( is_wp_error( $created ) ) {
		wp_send_json_error( array( 'message' => $created->get_error_message() ) );
		return;
	}

	delete_transient( 'ihq_tg_reg_' . md5( $session_token ) );
	wp_set_current_user( (int) $created );
	wp_set_auth_cookie( (int) $created, false );
	wp_send_json_success(
		array(
			'redirect_url' => add_query_arg( 'welcome', 'true', home_url( '/portal/portal-home/' ) ),
		)
	);
}
add_action( 'wp_ajax_ihq_register_telegram_user', 'ihq_handle_register_telegram_user_ajax' );
add_action( 'wp_ajax_nopriv_ihq_register_telegram_user', 'ihq_handle_register_telegram_user_ajax' );

/**
 * AJAX: login existing WP user by verified Telegram username.
 */
function ihq_handle_login_telegram_user_ajax() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ihq_telegram_login_pubkey' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid security token', 'avantage-baccarat' ) ) );
		return;
	}
	$session_token = isset( $_POST['telegram_session_token'] ) ? sanitize_text_field( wp_unslash( $_POST['telegram_session_token'] ) ) : '';
	$session       = ihq_get_telegram_registration_session( $session_token );
	if ( ! is_array( $session ) || empty( $session['telegram_username'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Telegram session expired. Please authenticate again.', 'avantage-baccarat' ) ) );
		return;
	}

	$telegram_username = (string) $session['telegram_username'];
	$users = get_users(
		array(
			'meta_key'   => 'communication_username',
			'meta_value' => $telegram_username,
			'number'     => 1,
			'fields'     => array( 'ID' ),
		)
	);
	if ( empty( $users ) || empty( $users[0]->ID ) ) {
		wp_send_json_error( array( 'message' => __( 'No account is linked to this Telegram username yet. Please register first.', 'avantage-baccarat' ) ) );
		return;
	}

	$user_id = (int) $users[0]->ID;
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, false );

	wp_send_json_success(
		array(
			'redirect_url' => home_url( '/portal/portal-home/' ),
		)
	);
}
add_action( 'wp_ajax_ihq_login_telegram_user', 'ihq_handle_login_telegram_user_ajax' );
add_action( 'wp_ajax_nopriv_ihq_login_telegram_user', 'ihq_handle_login_telegram_user_ajax' );

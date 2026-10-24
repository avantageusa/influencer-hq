<?php
/**
 * ENGR-8003 (Steve Wolfe, comment 138440): a Telegram login of an existing
 * influencer re-runs start-session through ihq_refresh_influencer_oauth_tokens()
 * right after signing in, as the email-code and AI Coach logins do, with the
 * country stored at the last start-session. A failed refresh is logged and the
 * user is still signed in. A new Telegram account is not refreshed a second
 * time: its registration already ran start-session.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/telegram-login-refreshes-tokens.test.php
 *
 * Exit code is non-zero on any failure.
 */
ob_start();
$log_file = tempnam( sys_get_temp_dir(), 'ihq-tg-refresh-log' );
ini_set( 'log_errors', '1' );
ini_set( 'error_log', $log_file );

define( 'ABSPATH', '/' );
define( 'MINUTE_IN_SECONDS', 60 );

const VALID_NONCE         = 'nonce-ok';
const SESSION_TOKEN       = 'session-token';
const OWNER_USER_ID       = 7;
const NEW_USER_ID         = 9;
const OWNER_TG_ID         = 424242;
const OTHER_TG_ID         = 515151;
const TG_HANDLE           = '@abc_test_handle';
const STORED_COUNTRY      = 'DE';
const PORTAL_HOME_URL     = 'https://ihq.test/portal/portal-home/';
const PROFILE_SETUP_URL   = 'https://ihq.test/portal/account/?setup_portal_username=1';
const MSG_NOT_LINKED      = 'No account is linked to this Telegram username yet. Please register first.';
const REFRESH_FAILED_LOG  = 'IHQ Telegram login: platform token refresh failed; signed in with the stored tokens';

// --- WordPress and IHQ stubs; every call is recorded in order in $GLOBALS['events']. ---
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function __( $text, $domain = 'default' ) { return $text; }
function sanitize_text_field( $value ) { return trim( (string) $value ); }
function wp_unslash( $value ) { return $value; }
function wp_verify_nonce( $nonce, $action ) {
	return VALID_NONCE === $nonce && 'ihq_telegram_login_pubkey' === $action;
}
function get_transient( $key ) {
	return $GLOBALS['transients'][ $key ] ?? false;
}
function delete_transient( $key ) { $GLOBALS['events'][] = array( 'delete_transient', $key ); }
function home_url( $path = '' ) { return 'https://ihq.test' . $path; }
function email_exists( $email ) { return false; }
function is_wp_error( $value ) { return false; }
function ihq_portal_profile_setup_url() { return PROFILE_SETUP_URL; }
// ENGR-8014 (influencer-hq#156) gates every Telegram handler on this; Telegram is on in every case here.
function ihq_channel_method_refuse_if_off( $method ) { return false; }

/** Mimics get_users() with a single meta_key / meta_value pair and a row limit. */
function get_users( array $args ) {
	$matches = array();
	foreach ( $GLOBALS['user_meta'] as $user_id => $meta ) {
		if ( isset( $meta[ $args['meta_key'] ] ) && (string) $meta[ $args['meta_key'] ] === (string) $args['meta_value'] ) {
			$matches[] = (object) array( 'ID' => (string) $user_id );
		}
	}
	return array_slice( $matches, 0, $args['number'] );
}
function get_user_meta( $user_id, $key, $single = false ) {
	$GLOBALS['events'][] = array( 'get_user_meta', $user_id, $key, $single );
	return $GLOBALS['user_meta'][ $user_id ][ $key ] ?? '';
}
function ihq_create_influencer_user_from_registration_data( array $data ) {
	$GLOBALS['events'][] = array( 'create_user', $data['telegram_user_id'] );
	return NEW_USER_ID;
}
function wp_set_current_user( $user_id ) { $GLOBALS['events'][] = array( 'wp_set_current_user', $user_id ); }
function wp_set_auth_cookie( $user_id, $remember ) { $GLOBALS['events'][] = array( 'wp_set_auth_cookie', $user_id, $remember ); }
function ihq_refresh_influencer_oauth_tokens( $user_id, $country_iso = '' ) {
	$GLOBALS['events'][] = array( 'refresh', $user_id, $country_iso );
	return $GLOBALS['refresh_result'];
}
function wp_send_json_error( $data ) { $GLOBALS['events'][] = array( 'json', false, $data ); }
function wp_send_json_success( $data ) { $GLOBALS['events'][] = array( 'json', true, $data ); }

require __DIR__ . '/../inc/placeholder-email.php';
require __DIR__ . '/../inc/telegram-login-handler.php';

$fail = 0;
$out  = array();

function check( $label, $actual, $expected ) {
	global $fail, $out;
	$ok    = $actual === $expected;
	$out[] = ( $ok ? 'PASS ' : 'FAIL ' ) . $label;
	if ( ! $ok ) {
		$out[] = '  expected: ' . var_export( $expected, true );
		$out[] = '  actual:   ' . var_export( $actual, true );
		$fail++;
	}
}

/**
 * Runs one Telegram AJAX handler against a session for the given Telegram id
 * and returns the ordered events plus what the handler logged.
 *
 * @param array $options {
 *     @type callable $handler        AJAX handler to run.
 *     @type int      $telegram_id    Telegram user id in the verified session.
 *     @type array    $user_meta      Map of user id → meta.
 *     @type mixed    $refresh_result What the token refresh returns.
 * }
 * @return array
 */
function run_handler( array $options ) {
	global $log_file;
	file_put_contents( $log_file, '' );
	$GLOBALS['events']         = array();
	$GLOBALS['user_meta']      = $options['user_meta'];
	$GLOBALS['refresh_result'] = $options['refresh_result'];
	$GLOBALS['transients']     = array(
		'ihq_tg_reg_' . md5( SESSION_TOKEN ) => array(
			'telegram_user_id'  => $options['telegram_id'],
			'telegram_username' => TG_HANDLE,
			'first_name'        => 'Bee',
			'last_name'         => '',
		),
	);
	$_POST = array(
		'nonce'                  => VALID_NONCE,
		'telegram_session_token' => SESSION_TOKEN,
	);
	$options['handler']();
	return array(
		'events' => $GLOBALS['events'],
		'log'    => trim( preg_replace( '/^\[[^\]]*\] /m', '', (string) file_get_contents( $log_file ) ) ),
	);
}

function login_existing( array $owner_meta, $refresh_result ) {
	return run_handler(
		array(
			'handler'        => 'ihq_handle_login_telegram_user_ajax',
			'telegram_id'    => OWNER_TG_ID,
			'user_meta'      => array( OWNER_USER_ID => $owner_meta ),
			'refresh_result' => $refresh_result,
		)
	);
}

function signed_in_and_refreshed( $country_iso ) {
	return array(
		array( 'wp_set_current_user', OWNER_USER_ID ),
		array( 'wp_set_auth_cookie', OWNER_USER_ID, false ),
		array( 'get_user_meta', OWNER_USER_ID, 'ihq_oauth_country_iso', true ),
		array( 'refresh', OWNER_USER_ID, $country_iso ),
		array( 'json', true, array( 'redirect_url' => PORTAL_HOME_URL ) ),
	);
}

$owner_meta = array(
	'telegram_user_id'      => OWNER_TG_ID,
	'ihq_oauth_country_iso' => STORED_COUNTRY,
);

check(
	'an existing account is signed in, then refreshed once with its id and stored country',
	login_existing( $owner_meta, true ),
	array(
		'events' => signed_in_and_refreshed( STORED_COUNTRY ),
		'log'    => '',
	)
);

check(
	'a failed refresh still signs the user in, and is logged without the user id',
	login_existing( $owner_meta, false ),
	array(
		'events' => signed_in_and_refreshed( STORED_COUNTRY ),
		'log'    => REFRESH_FAILED_LOG,
	)
);

check(
	'a refresh answering anything but true is logged as failed',
	login_existing( $owner_meta, 1 )['log'],
	REFRESH_FAILED_LOG
);

check(
	'an account with no stored country is refreshed with an empty country',
	login_existing( array( 'telegram_user_id' => OWNER_TG_ID ), true )['events'],
	signed_in_and_refreshed( '' )
);

check(
	'a stored country that is not a string is refreshed with an empty country',
	login_existing(
		array(
			'telegram_user_id'      => OWNER_TG_ID,
			'ihq_oauth_country_iso' => array( STORED_COUNTRY ),
		),
		true
	)['events'],
	signed_in_and_refreshed( '' )
);

check(
	'a refused login does not refresh anyone',
	run_handler(
		array(
			'handler'        => 'ihq_handle_login_telegram_user_ajax',
			'telegram_id'    => OTHER_TG_ID,
			'user_meta'      => array( OWNER_USER_ID => $owner_meta ),
			'refresh_result' => true,
		)
	),
	array(
		'events' => array( array( 'json', false, array( 'message' => MSG_NOT_LINKED ) ) ),
		'log'    => '',
	)
);

check(
	'a new Telegram account is signed in without a second refresh: registration ran start-session',
	run_handler(
		array(
			'handler'        => 'ihq_handle_register_telegram_user_ajax',
			'telegram_id'    => OWNER_TG_ID,
			'user_meta'      => array(),
			'refresh_result' => true,
		)
	),
	array(
		'events' => array(
			array( 'create_user', OWNER_TG_ID ),
			array( 'delete_transient', 'ihq_tg_reg_' . md5( SESSION_TOKEN ) ),
			array( 'wp_set_current_user', NEW_USER_ID ),
			array( 'wp_set_auth_cookie', NEW_USER_ID, false ),
			array( 'json', true, array( 'redirect_url' => PROFILE_SETUP_URL ) ),
		),
		'log'    => '',
	)
);

unlink( $log_file );
ob_end_clean();
echo implode( "\n", $out ) . "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );

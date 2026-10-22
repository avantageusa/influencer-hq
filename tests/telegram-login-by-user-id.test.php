<?php
/**
 * ENGR-8003: Telegram login signs in only the influencer whose telegram_user_id
 * equals the verified Telegram account's id. A matching @username is never
 * enough, an empty or 0 id is refused before any lookup, and two accounts with
 * the same id are refused.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/telegram-login-by-user-id.test.php
 *
 * Exit code is non-zero on any failure.
 */
ob_start();
$log_file = tempnam( sys_get_temp_dir(), 'ihq-tg-login-log' );
ini_set( 'log_errors', '1' );
ini_set( 'error_log', $log_file );

define( 'ABSPATH', '/' );
define( 'MINUTE_IN_SECONDS', 60 );

const VALID_NONCE      = 'nonce-ok';
const SESSION_TOKEN    = 'session-token';
const OWNER_USER_ID    = 7;
const OTHER_USER_ID    = 8;
const OWNER_TG_ID      = 424242;
const OTHER_TG_ID      = 515151;
const SHARED_HANDLE    = '@abc_test_handle';
const PORTAL_HOME_URL  = 'https://ihq.test/portal/portal-home/';
const MSG_BAD_NONCE    = 'Invalid security token';
const MSG_EXPIRED      = 'Telegram session expired. Please authenticate again.';
const MSG_NOT_LINKED   = 'No account is linked to this Telegram username yet. Please register first.';
const MSG_AMBIGUOUS    = 'This Telegram account is linked to more than one account. Please contact support.';
const AMBIGUOUS_LOG    = 'IHQ Telegram login: refused, more than one account carries the same telegram_user_id';

// --- WordPress stubs; every call is recorded. ---
function add_action( ...$args ) {}
function __( $text, $domain = 'default' ) { return $text; }
function sanitize_text_field( $value ) { return trim( (string) $value ); }
function wp_unslash( $value ) { return $value; }
function wp_verify_nonce( $nonce, $action ) {
	return VALID_NONCE === $nonce && 'ihq_telegram_login_pubkey' === $action;
}
function get_transient( $key ) {
	return $GLOBALS['transients'][ $key ] ?? false;
}
function home_url( $path = '' ) { return 'https://ihq.test' . $path; }

/** Mimics get_users() with a single meta_key / meta_value pair and a row limit. */
function get_users( array $args ) {
	$GLOBALS['calls']['get_users'][] = $args;
	$matches = array();
	foreach ( $GLOBALS['user_meta'] as $user_id => $meta ) {
		if ( isset( $meta[ $args['meta_key'] ] ) && (string) $meta[ $args['meta_key'] ] === (string) $args['meta_value'] ) {
			$matches[] = (object) array( 'ID' => (string) $user_id );
		}
	}
	return array_slice( $matches, 0, $args['number'] );
}
function wp_set_current_user( $user_id ) { $GLOBALS['calls']['wp_set_current_user'][] = $user_id; }
function wp_set_auth_cookie( $user_id, $remember ) { $GLOBALS['calls']['wp_set_auth_cookie'][] = array( $user_id, $remember ); }
function wp_send_json_error( $data ) { $GLOBALS['calls']['json'][] = array( 'success' => false, 'data' => $data ); }
function wp_send_json_success( $data ) { $GLOBALS['calls']['json'][] = array( 'success' => true, 'data' => $data ); }

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
 * Runs the login AJAX handler with the given session and users, and returns
 * every recorded call plus what the handler logged.
 *
 * @param array|null $session   Telegram session transient, null for none.
 * @param array      $user_meta Map of user id → meta.
 * @param string     $nonce     Posted nonce.
 * @return array
 */
function run_login( $session, array $user_meta, $nonce = VALID_NONCE ) {
	global $log_file;
	file_put_contents( $log_file, '' );
	$GLOBALS['calls']      = array(
		'get_users'           => array(),
		'wp_set_current_user' => array(),
		'wp_set_auth_cookie'  => array(),
		'json'                => array(),
	);
	$GLOBALS['user_meta']  = $user_meta;
	$GLOBALS['transients'] = array();
	if ( null !== $session ) {
		$GLOBALS['transients'][ 'ihq_tg_reg_' . md5( SESSION_TOKEN ) ] = $session;
	}
	$_POST = array(
		'nonce'                  => $nonce,
		'telegram_session_token' => SESSION_TOKEN,
	);
	ihq_handle_login_telegram_user_ajax();
	$calls        = $GLOBALS['calls'];
	$calls['log'] = trim( preg_replace( '/^\[[^\]]*\] /m', '', (string) file_get_contents( $log_file ) ) );
	return $calls;
}

function session_for( $telegram_user_id ) {
	return array(
		'telegram_user_id'  => $telegram_user_id,
		'telegram_username' => SHARED_HANDLE,
		'first_name'        => 'Bee',
		'last_name'         => '',
		'issued_at'         => 1700000000,
	);
}

function lookup_args( $telegram_user_id ) {
	return array(
		'meta_key'   => 'telegram_user_id',
		'meta_value' => $telegram_user_id,
		'number'     => 2,
		'fields'     => array( 'ID' ),
	);
}

function refused( $message, array $get_users_calls ) {
	return array(
		'get_users'           => $get_users_calls,
		'wp_set_current_user' => array(),
		'wp_set_auth_cookie'  => array(),
		'json'                => array( array( 'success' => false, 'data' => array( 'message' => $message ) ) ),
		'log'                 => '',
	);
}

// Influencer A typed the shared handle at email registration and carries a Telegram id.
$owner_meta = array(
	'communication_username' => SHARED_HANDLE,
	'telegram_user_id'       => OWNER_TG_ID,
);

check(
	'the account with the matching Telegram id signs in',
	run_login( session_for( OWNER_TG_ID ), array( OWNER_USER_ID => $owner_meta ) ),
	array(
		'get_users'           => array( lookup_args( OWNER_TG_ID ) ),
		'wp_set_current_user' => array( OWNER_USER_ID ),
		'wp_set_auth_cookie'  => array( array( OWNER_USER_ID, false ) ),
		'json'                => array( array( 'success' => true, 'data' => array( 'redirect_url' => PORTAL_HOME_URL ) ) ),
		'log'                 => '',
	)
);

check(
	'a session id held as a numeric string still signs in its owner',
	run_login( session_for( (string) OWNER_TG_ID ), array( OWNER_USER_ID => $owner_meta ) )['wp_set_auth_cookie'],
	array( array( OWNER_USER_ID, false ) )
);

check(
	'a username match with a different Telegram id is refused',
	run_login( session_for( OTHER_TG_ID ), array( OWNER_USER_ID => $owner_meta ) ),
	refused( MSG_NOT_LINKED, array( lookup_args( OTHER_TG_ID ) ) )
);

check(
	'a username match on an account with no Telegram id is refused',
	run_login(
		session_for( OTHER_TG_ID ),
		array( OWNER_USER_ID => array( 'communication_username' => SHARED_HANDLE ) )
	),
	refused( MSG_NOT_LINKED, array( lookup_args( OTHER_TG_ID ) ) )
);

$bad_ids = array(
	'0'           => 0,
	'"0"'         => '0',
	'empty'       => '',
	'null'        => null,
	'negative'    => -5,
	'non-numeric' => 'abc',
);
foreach ( $bad_ids as $label => $bad_id ) {
	check(
		'session id ' . $label . ' is refused before any lookup',
		run_login( session_for( $bad_id ), array( OWNER_USER_ID => $owner_meta ) ),
		refused( MSG_EXPIRED, array() )
	);
}

$session_without_id = session_for( OWNER_TG_ID );
unset( $session_without_id['telegram_user_id'] );
check(
	'a session with no id key is refused before any lookup',
	run_login( $session_without_id, array( OWNER_USER_ID => $owner_meta ) ),
	refused( MSG_EXPIRED, array() )
);

$ambiguous          = refused( MSG_AMBIGUOUS, array( lookup_args( OWNER_TG_ID ) ) );
$ambiguous['log']   = AMBIGUOUS_LOG;
check(
	'two accounts with the same Telegram id are refused and logged without ids',
	run_login(
		session_for( OWNER_TG_ID ),
		array(
			OWNER_USER_ID => $owner_meta,
			OTHER_USER_ID => array( 'telegram_user_id' => OWNER_TG_ID ),
		)
	),
	$ambiguous
);

check(
	'no session is refused as expired',
	run_login( null, array( OWNER_USER_ID => $owner_meta ) ),
	refused( MSG_EXPIRED, array() )
);

check(
	'a bad nonce is refused before the session is read',
	run_login( session_for( OWNER_TG_ID ), array( OWNER_USER_ID => $owner_meta ), 'nonce-bad' ),
	refused( MSG_BAD_NONCE, array() )
);

unlink( $log_file );
ob_end_clean();
echo implode( "\n", $out ) . "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );

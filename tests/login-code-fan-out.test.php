<?php
/**
 * ENGR-8022 tests for the IHQ login code fan-out (inc/login-code-fan-out.php) and
 * the login send that uses it (inc/email-verification-handler.php): the switch,
 * the D3 rate limit, the POST /ihq/v1/messages request, failures that change
 * nothing the influencer sees, the kept code on a failed email, the placeholder
 * guard and the code step's hint. No WordPress bootstrap and no live requests:
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/login-code-fan-out.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );
define( 'IHQ_ENVIRONMENT', 'dev' );
define( 'IHQ_API_BASE_URL', 'https://api.example.test/qc' );
define( 'IHQ_GAME_PORTAL_BASE_URL', 'https://portal.example.test/av-baccarat' );
define( 'IHQ_INFLUENCER_API_KEY', 'account-api-key' );
// IHQ_SERVER_API_KEY comes from putenv() per case, so a case can leave it unset.
define( 'LOG_FILE', sys_get_temp_dir() . '/ihq-login-code-fan-out-' . getmypid() . '.log' );

const MESSAGES_URL    = 'https://api.example.test/qc/ihq/v1/messages';
const SERVER_KEY      = 'ihq-server-key';
const INFLUENCER_ID   = 7;
const INFLUENCER_MAIL = 'ann@example.test';
const STAND_IN_ID     = 8;
const STAND_IN_MAIL   = '0b5c@chat.invalid';
const LEGACY_ID       = 9;
const LEGACY_MAIL     = 'ann@email_telegram.com';
const ADMIN_MAIL      = 'admin@example.test';
const OPTION          = 'ihq_login_code_fan_out_enabled';
const FAN_OUT_KEY     = 'ihq_login_fan_out_7';
const HINT            = "If you've connected a chat app, the code is there too.";
const GENERIC         = 'If that email matches an Influencer HQ account, you will receive a sign-in code shortly.';
const DO_NOT_SHARE    = 'This is your sign-in code. Do not share it with anyone.';
const SENT_BODY       = '{"results":[{"provider":"telegram","status":"sent"},{"provider":"line","status":"not_connected"}]}';
const NOTHING_BODY    = '{"results":[{"provider":"telegram","status":"not_connected"},{"provider":"line","status":"switched_off"}]}';
const FAILED_BODY     = '{"results":[{"provider":"telegram","status":"failed","reason":"quota_exceeded"},{"provider":"line","status":"failed"}]}';

ini_set( 'error_log', LOG_FILE );
register_shutdown_function(
	function () {
		if ( file_exists( LOG_FILE ) ) {
			unlink( LOG_FILE );
		}
	}
);

class WP_Error {
	private $message;
	public function __construct( $code = '', $message = '' ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}
class WP_User {
	public $ID;
	public $user_email;
	public $roles;
	public function __construct( $id, $email, array $roles ) {
		$this->ID         = $id;
		$this->user_email = $email;
		$this->roles      = $roles;
	}
}
class JsonResponse extends Exception {
	public $success;
	public $data;
	public function __construct( $success, $data ) {
		parent::__construct( 'json' );
		$this->success = $success;
		$this->data    = $data;
	}
}

function reset_state() {
	$GLOBALS['options']        = array();
	$GLOBALS['option_reads']   = array();
	$GLOBALS['transients']     = array();
	$GLOBALS['transient_ttls'] = array();
	$GLOBALS['user_meta']      = array();
	$GLOBALS['mail']           = array();
	$GLOBALS['mail_error']     = null;
	$GLOBALS['actions']        = array();
	$GLOBALS['requests']       = array();
	$GLOBALS['responses']      = array();
	$GLOBALS['password_seq']   = 0;
	$GLOBALS['users']          = array(
		INFLUENCER_MAIL => new WP_User( INFLUENCER_ID, INFLUENCER_MAIL, array( 'influencer' ) ),
		STAND_IN_MAIL   => new WP_User( STAND_IN_ID, STAND_IN_MAIL, array( 'influencer' ) ),
		LEGACY_MAIL     => new WP_User( LEGACY_ID, LEGACY_MAIL, array( 'influencer' ) ),
		ADMIN_MAIL      => new WP_User( 6, ADMIN_MAIL, array( 'administrator' ) ),
	);
	$_POST = array();
	putenv( 'IHQ_SERVER_API_KEY=' . SERVER_KEY );
	file_put_contents( LOG_FILE, '' );
}

function switch_on() {
	$GLOBALS['options'][ OPTION ] = '1';
}

function logged() {
	return (string) file_get_contents( LOG_FILE );
}

function queue_response( $response ) {
	$GLOBALS['responses'][] = $response;
}

function ok_response( $body ) {
	return array( 'status' => 200, 'body' => $body );
}

// --- WordPress stubs. ---
function add_action( $hook, $callback ) { $GLOBALS['actions'][ $hook ][] = $callback; }
function add_filter( ...$args ) {}
function remove_action( $hook, $callback ) {
	$GLOBALS['actions'][ $hook ] = array_values(
		array_filter( $GLOBALS['actions'][ $hook ] ?? array(), function ( $kept ) use ( $callback ) { return $kept !== $callback; } )
	);
}
function __( $s, $domain = 'default' ) { return $s; }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_html__( $s, $domain = 'default' ) { return esc_html( $s ); }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function wp_parse_url( $u ) { return parse_url( $u ); }
function wp_unslash( $v ) { return is_string( $v ) ? stripslashes( $v ) : $v; }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function sanitize_email( $s ) { return trim( (string) $s ); }
// Same shape as WordPress core is_email(): no spaces, a dotted domain, no underscore in a domain label.
function is_email( $s ) { return is_string( $s ) && preg_match( '/^[a-z0-9!#$%&\'*+\/=?^_`{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)+$/i', $s ) === 1; }
function get_user_by( $field, $value ) { return $GLOBALS['users'][ strtolower( $value ) ] ?? false; }
function wp_verify_nonce( $nonce, $action ) { return $nonce === 'good-nonce' && $action === 'ihq_login_code_nonce'; }
function wp_generate_password( $length = 12, $special = true, $extra = false ) { return 'token' . ( ++$GLOBALS['password_seq'] ); }
function wp_rand( $min, $max ) { return 42; }
function wp_salt( $scheme = '' ) { return 'salt-' . $scheme; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function get_option( $key, $default = false ) {
	$GLOBALS['option_reads'][] = array( $key, $default );
	return array_key_exists( $key, $GLOBALS['options'] ) ? $GLOBALS['options'][ $key ] : $default;
}
function update_option( $key, $value, $autoload = null ) { $GLOBALS['options'][ $key ] = $value; return true; }
function delete_option( $key ) { unset( $GLOBALS['options'][ $key ] ); return true; }
function get_transient( $key ) { return $GLOBALS['transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl = 0 ) {
	$GLOBALS['transients'][ $key ]     = $value;
	$GLOBALS['transient_ttls'][ $key ] = $ttl;
	return true;
}
function get_user_meta( $id, $key, $single = false ) { return $GLOBALS['user_meta'][ $id ][ $key ] ?? ''; }
function wp_mail( $to, $subject, $message, $headers = array() ) {
	$GLOBALS['mail'][] = array( 'to' => $to, 'subject' => $subject, 'message' => $message );
	if ( null === $GLOBALS['mail_error'] ) {
		return true;
	}
	foreach ( $GLOBALS['actions']['wp_mail_failed'] ?? array() as $callback ) {
		$callback( new WP_Error( 'wp_mail_failed', $GLOBALS['mail_error'] ) );
	}
	return false;
}
function wp_remote_post( $url, $args ) {
	$GLOBALS['requests'][] = array( 'url' => $url, 'args' => $args );
	if ( array() === $GLOBALS['responses'] ) {
		throw new Exception( 'unexpected request to ' . $url );
	}
	return array_shift( $GLOBALS['responses'] );
}
function wp_remote_retrieve_response_code( $response ) { return $response['status']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function wp_send_json_success( $data = null ) { throw new JsonResponse( true, $data ); }
function wp_send_json_error( $data = null ) { throw new JsonResponse( false, $data ); }

require __DIR__ . '/../inc/ihq-env.php';
require __DIR__ . '/../inc/placeholder-email.php';
require __DIR__ . '/../inc/api-ajax-calls.php';
require __DIR__ . '/../inc/login-code-fan-out.php';
require __DIR__ . '/../inc/email-verification-handler.php';

$fail = 0;
function check( $label, $actual, $expected ) {
	global $fail;
	$ok = $actual === $expected;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $label . "\n";
	if ( ! $ok ) {
		echo '  expected: ' . var_export( $expected, true ) . "\n";
		echo '  actual:   ' . var_export( $actual, true ) . "\n";
		$fail++;
	}
}

/** The login email exactly as sent before ENGR-8022 (origin/main @ c45459d). */
function pre_8022_login_email( $code, $minutes_left ) {
	return '
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:#0a0a0a;color:#f0f0f0;">
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:40px 20px;">
        <tr><td align="center">
          <table width="560" cellpadding="0" cellspacing="0" style="background:#161612;border:1px solid rgba(240,201,58,.35);border-radius:12px;">
            <tr><td style="padding:40px 32px;text-align:center;">
              <h1 style="color:#F0C93A;font-size:26px;margin:0 0 16px;">Influencer HQ</h1>
              <p style="color:#EAD9B0;font-size:16px;line-height:1.6;margin:0 0 24px;">Your sign-in code is:</p>
              <div style="font-size:36px;font-weight:700;letter-spacing:12px;color:#fff;margin:16px 0 24px;">' . esc_html( $code ) . '</div>
              <p style="color:#888;font-size:14px;line-height:1.6;margin:0;">This code expires in ' . (int) $minutes_left . ' minutes.</p>
            </td></tr>
          </table>
        </td></tr>
      </table>
    </body></html>';
}

/** The email with the switch on: the old body plus the don't-share line under the expiry line. */
function fan_out_login_email( $code, $minutes_left ) {
	$expires_line = '<p style="color:#888;font-size:14px;line-height:1.6;margin:0;">This code expires in ' . (int) $minutes_left . ' minutes.</p>';
	return str_replace(
		$expires_line,
		$expires_line . "\n" . '              <p style="color:#888;font-size:14px;line-height:1.6;margin:16px 0 0;">' . DO_NOT_SHARE . '</p>',
		pre_8022_login_email( $code, $minutes_left )
	);
}

function influencer() {
	return $GLOBALS['users'][ INFLUENCER_MAIL ];
}

/** Runs the login AJAX handler and returns its JSON answer. */
function send_login_ajax( $email ) {
	$_POST = array( 'nonce' => 'good-nonce', 'email' => $email );
	try {
		ihq_handle_send_login_code_ajax();
	} catch ( JsonResponse $response ) {
		return array( 'success' => $response->success, 'data' => $response->data );
	}
	return null;
}

$code         = '000042';
$token_key    = 'pending_login_code_token1';
$email_map    = 'ihq_pending_login_email_' . md5( INFLUENCER_MAIL );
$throttle_key = 'ihq_login_send_' . md5( INFLUENCER_MAIL );
$now          = 1800000000;

// --- Contract: names other repos and the DEPLOYMENT NOTE use. ---
check( 'option name is the one in the DEPLOYMENT NOTE', IHQ_LOGIN_CODE_FAN_OUT_ENABLED_OPTION, 'ihq_login_code_fan_out_enabled' );
check( 'on values are exactly 1, true, yes, on', IHQ_LOGIN_CODE_FAN_OUT_ON_VALUES, array( '1', 'true', 'yes', 'on' ) );
check( 'route is ENGR-8005 POST /ihq/v1/messages', IHQ_LOGIN_CODE_FAN_OUT_PATH, '/ihq/v1/messages' );
check( 'kind is one_time_code (cross-repo contract)', IHQ_LOGIN_CODE_FAN_OUT_KIND, 'one_time_code' );
check( 'methods is all', IHQ_LOGIN_CODE_FAN_OUT_METHODS, 'all' );
check( 'key constant is IHQ_SERVER_API_KEY', IHQ_LOGIN_CODE_FAN_OUT_API_KEY_NAME, 'IHQ_SERVER_API_KEY' );
check( 'timeout is 3 seconds', IHQ_LOGIN_CODE_FAN_OUT_TIMEOUT_SECONDS, 3 );
check( 'D3: one per 60 seconds', IHQ_LOGIN_CODE_FAN_OUT_MIN_GAP_SECONDS, 60 );
check( 'D3: five per hour', array( IHQ_LOGIN_CODE_FAN_OUT_MAX_PER_WINDOW, IHQ_LOGIN_CODE_FAN_OUT_WINDOW_SECONDS ), array( 5, 3600 ) );

// --- Switch. ---
reset_state();
check( 'off when the option has never been set (default after deploy)', ihq_login_code_fan_out_enabled(), false );
check( 'reads the option with a false default', $GLOBALS['option_reads'], array( array( OPTION, false ) ) );
foreach ( array( '1', 'true', 'TRUE', 'yes', 'on', ' On ', 1, true ) as $value ) {
	$GLOBALS['options'][ OPTION ] = $value;
	check( 'on for ' . var_export( $value, true ), ihq_login_code_fan_out_enabled(), true );
}
foreach ( array( '0', '', 'false', 'no', 'off', '2', '01', 0, 2, false, null, array( '1' ), 1.0 ) as $value ) {
	$GLOBALS['options'][ OPTION ] = $value;
	check( 'off for ' . var_export( $value, true ), ihq_login_code_fan_out_enabled(), false );
}

// --- Hint on the login answer. ---
reset_state();
$answer = array( 'message' => GENERIC );
check( 'off: the answer is returned unchanged', ihq_login_code_fan_out_add_hint( $answer ), $answer );
switch_on();
check( 'on: the answer gains chat_app_hint', ihq_login_code_fan_out_add_hint( $answer ), array( 'message' => GENERIC, 'chat_app_hint' => HINT ) );

// --- D3 window. ---
check( 'recent: nothing stored', ihq_login_code_fan_out_recent_sends( false, $now ), array() );
check( 'recent: damaged value', ihq_login_code_fan_out_recent_sends( 'x', $now ), array() );
check(
	'recent: keeps sends younger than an hour, drops older ones and non-integers',
	ihq_login_code_fan_out_recent_sends( array( $now - 3600, $now - 3599, (string) ( $now - 5 ), $now - 10 ), $now ),
	array( $now - 3599, $now - 10 )
);
check( 'window: empty allows', ihq_login_code_fan_out_window_allows( array(), $now ), true );
check( 'window: four sends an hour allows a fifth', ihq_login_code_fan_out_window_allows( array( $now - 3000, $now - 2000, $now - 1000, $now - 60 ), $now ), true );
check( 'window: five sends an hour refuses a sixth', ihq_login_code_fan_out_window_allows( array( $now - 3000, $now - 2400, $now - 1800, $now - 1200, $now - 600 ), $now ), false );
check( 'window: a send 59 seconds ago refuses', ihq_login_code_fan_out_window_allows( array( $now - 59 ), $now ), false );
check( 'window: a send 60 seconds ago allows', ihq_login_code_fan_out_window_allows( array( $now - 60 ), $now ), true );

reset_state();
check( 'claim: first send takes the slot', ihq_login_code_fan_out_claim_slot( INFLUENCER_ID, $now ), true );
check( 'claim: the time is stored per influencer for an hour', array( $GLOBALS['transients'][ FAN_OUT_KEY ], $GLOBALS['transient_ttls'][ FAN_OUT_KEY ] ), array( array( $now ), 3600 ) );
check( 'claim: 30 seconds later is refused', ihq_login_code_fan_out_claim_slot( INFLUENCER_ID, $now + 30 ), false );
check( 'claim: a refusal stores nothing', $GLOBALS['transients'][ FAN_OUT_KEY ], array( $now ) );
check( 'claim: another influencer is not limited', ihq_login_code_fan_out_claim_slot( 99, $now + 30 ), true );
check( 'claim: 60 seconds later is allowed', ihq_login_code_fan_out_claim_slot( INFLUENCER_ID, $now + 60 ), true );
check( 'claim: expired sends are dropped when storing', $GLOBALS['transients'][ FAN_OUT_KEY ], array( $now, $now + 60 ) );
$GLOBALS['transients'][ FAN_OUT_KEY ] = array( $now - 4000, $now - 3000, $now - 2400, $now - 1800, $now - 1200, $now - 600 );
check( 'claim: the sixth inside an hour is refused', ihq_login_code_fan_out_claim_slot( INFLUENCER_ID, $now ), false );
check( 'claim: still refused while five are younger than an hour', ihq_login_code_fan_out_claim_slot( INFLUENCER_ID, $now + 599 ), false );
check( 'claim: the oldest of the five ageing out frees a slot', ihq_login_code_fan_out_claim_slot( INFLUENCER_ID, $now + 600 ), true );

// --- Locale and request body. ---
reset_state();
check( 'locale: none saved is English', ihq_login_code_fan_out_locale( INFLUENCER_ID ), 'en' );
$expected_locales = array(
	'en'  => 'en',
	'zh'  => 'zh-CN',
	'yue' => 'zh-HK',
	'ja'  => 'ja-JP',
	'ko'  => 'ko-KR',
	'th'  => 'th-TH',
	'vi'  => 'vi-VN',
	'fr'  => 'en',
	'JA'  => 'en',
);
foreach ( $expected_locales as $language => $locale ) {
	$GLOBALS['user_meta'][ INFLUENCER_ID ]['_ihq_aicoach_language'] = $language;
	check( 'locale: ' . $language . ' -> ' . $locale, ihq_login_code_fan_out_locale( INFLUENCER_ID ), $locale );
}
$GLOBALS['user_meta'][ INFLUENCER_ID ]['_ihq_aicoach_language'] = array( 'ja' );
check( 'locale: a non-string is English', ihq_login_code_fan_out_locale( INFLUENCER_ID ), 'en' );
$GLOBALS['user_meta'][ INFLUENCER_ID ]['_ihq_aicoach_language'] = 'ja';
check(
	'request body: the platform username, all methods, one_time_code, the code and the locale',
	ihq_login_code_fan_out_request_body( INFLUENCER_ID, '123456' ),
	array(
		'platformUsername' => 'influencerhq-wpu-7',
		'methods'          => 'all',
		'kind'             => 'one_time_code',
		'text'             => '123456',
		'locale'           => 'ja-JP',
	)
);

// --- Failed methods, for the log. ---
check( 'failed methods: none when a method sent and another is not connected', ihq_login_code_fan_out_failed_methods( SENT_BODY ), array() );
check( 'failed methods: with and without a reason', ihq_login_code_fan_out_failed_methods( FAILED_BODY ), array( 'telegram: quota_exceeded', 'line: no reason given' ) );
check( 'failed methods: not connected and switched off are not failures', ihq_login_code_fan_out_failed_methods( NOTHING_BODY ), array() );
check( 'failed methods: only the failed ones of a mix', ihq_login_code_fan_out_failed_methods( '{"results":[{"provider":"line","status":"sent"},{"provider":"telegram","status":"failed","reason":"blocked"}]}' ), array( 'telegram: blocked' ) );
check( 'failed methods: not JSON', ihq_login_code_fan_out_failed_methods( 'oops' ), array() );
check( 'failed methods: no results list', ihq_login_code_fan_out_failed_methods( '{"results":"failed"}' ), array() );
check( 'failed methods: entries without provider or status are skipped', ihq_login_code_fan_out_failed_methods( '{"results":[{"status":"failed"},{"provider":"line"},"x"]}' ), array() );

// --- Send: true once the request is made, whatever it answers; every failure is logged. ---
reset_state();
queue_response( ok_response( SENT_BODY ) );
check( 'send: a method reporting sent', ihq_login_code_fan_out_send( INFLUENCER_ID, '123456', INFLUENCER_API_BASE ), true );
check(
	'send: one POST to /ihq/v1/messages with the IHQ server key, a 3 s timeout and no redirects',
	$GLOBALS['requests'],
	array(
		array(
			'url'  => MESSAGES_URL,
			'args' => array(
				'headers'     => array(
					'Content-Type' => 'application/json',
					'x-api-key'    => SERVER_KEY,
				),
				'body'        => '{"platformUsername":"influencerhq-wpu-7","methods":"all","kind":"one_time_code","text":"123456","locale":"en"}',
				'timeout'     => 3,
				'redirection' => 0,
			),
		),
	)
);
check( 'send: nothing logged on success', logged(), '' );

reset_state();
queue_response( ok_response( NOTHING_BODY ) );
check( 'send: nothing connected still made the request', ihq_login_code_fan_out_send( INFLUENCER_ID, '123456', INFLUENCER_API_BASE ), true );
check( 'send: nothing connected logs nothing', logged(), '' );

reset_state();
queue_response( ok_response( FAILED_BODY ) );
check( 'send: failed methods still made the request', ihq_login_code_fan_out_send( INFLUENCER_ID, '123456', INFLUENCER_API_BASE ), true );
check( 'send: failed methods are logged with their reasons, never the code', substr_count( logged(), '[ihq-login-fan-out] methods failed for user 7: telegram: quota_exceeded, line: no reason given' ) === 1 && strpos( logged(), '123456' ) === false, true );

reset_state();
queue_response( new WP_Error( 'http_request_failed', 'cURL error 28: timed out' ) );
check( 'send: a timeout still made the request', ihq_login_code_fan_out_send( INFLUENCER_ID, '123456', INFLUENCER_API_BASE ), true );
check( 'send: a transport error is logged', substr_count( logged(), '[ihq-login-fan-out] request failed for user 7: cURL error 28: timed out' ), 1 );

foreach ( array( 401, 502, 199, 300 ) as $status ) {
	reset_state();
	queue_response( array( 'status' => $status, 'body' => FAILED_BODY ) );
	check( 'send: HTTP ' . $status . ' still made the request', ihq_login_code_fan_out_send( INFLUENCER_ID, '123456', INFLUENCER_API_BASE ), true );
	check( 'send: HTTP ' . $status . ' is logged once, and its body is not read', array( substr_count( logged(), '[ihq-login-fan-out] HTTP ' . $status . ' for user 7' ), strpos( logged(), 'methods failed' ) ), array( 1, false ) );
}
foreach ( array( 200, 299 ) as $status ) {
	reset_state();
	queue_response( array( 'status' => $status, 'body' => FAILED_BODY ) );
	check( 'send: HTTP ' . $status . ' is read', ihq_login_code_fan_out_send( INFLUENCER_ID, '123456', INFLUENCER_API_BASE ), true );
	check( 'send: HTTP ' . $status . ' logs its failed methods only', strpos( logged(), '[ihq-login-fan-out] methods failed for user 7: ' ) !== false && strpos( logged(), 'HTTP ' ) === false, true );
}

reset_state();
putenv( 'IHQ_SERVER_API_KEY' );
check( 'send: no IHQ_SERVER_API_KEY makes no request, so the code did not go', ihq_login_code_fan_out_send( INFLUENCER_ID, '123456', INFLUENCER_API_BASE ), false );
check( 'send: no IHQ_SERVER_API_KEY makes no request', $GLOBALS['requests'], array() );
check( 'send: no IHQ_SERVER_API_KEY is logged', substr_count( logged(), '[ihq-login-fan-out] IHQ_SERVER_API_KEY is not set; user 7 got the code by email only' ), 1 );

reset_state();
putenv( 'IHQ_SERVER_API_KEY=' );
check( 'send: an empty IHQ_SERVER_API_KEY (the README template) is not set', array( ihq_login_code_fan_out_send( INFLUENCER_ID, '123456', INFLUENCER_API_BASE ), $GLOBALS['requests'] ), array( false, array() ) );

// --- The fan-out step. ---
reset_state();
check( 'step: off sends nothing', ihq_login_code_fan_out( INFLUENCER_ID, '123456', $now ), false );
check( 'step: off takes no slot', $GLOBALS['transients'], array() );
switch_on();
queue_response( ok_response( SENT_BODY ) );
check( 'step: on sends', ihq_login_code_fan_out( INFLUENCER_ID, '123456', $now ), true );
check( 'step: on takes a slot at now', $GLOBALS['transients'][ FAN_OUT_KEY ], array( $now ) );
check( 'step: inside the window sends nothing', ihq_login_code_fan_out( INFLUENCER_ID, '123456', $now + 10 ), false );
check( 'step: one request in all', count( $GLOBALS['requests'] ), 1 );

// --- Email body. ---
check( 'email: without the warning it is the pre-ENGR-8022 email', ihq_login_code_email_body( '123456', 15, false ), pre_8022_login_email( '123456', 15 ) );
check( 'email: with the warning it adds the do-not-share line', ihq_login_code_email_body( '123456', 15, true ), fan_out_login_email( '123456', 15 ) );

// --- Login send, switch off: today's behaviour. ---
reset_state();
$result = ihq_issue_and_send_login_code_for_influencer( influencer() );
check(
	'off: the answer is today\'s',
	$result,
	array(
		'ok'              => true,
		'signup_token'    => 'token1',
		'expires_minutes' => 15,
		'message'         => GENERIC,
	)
);
check(
	'off: one email, the pre-ENGR-8022 body',
	$GLOBALS['mail'],
	array(
		array(
			'to'      => INFLUENCER_MAIL,
			'subject' => 'Your Influencer HQ sign-in code',
			'message' => pre_8022_login_email( $code, 15 ),
		),
	)
);
check( 'off: no request', $GLOBALS['requests'], array() );
check( 'off: the code is stored', $GLOBALS['options'][ $token_key ]['code_hash'], hash_hmac( 'sha256', $code, 'salt-ihq_login_code' . 'token1' ) );
check( 'off: the email maps to the token', $GLOBALS['options'][ $email_map ], 'token1' );
check( 'off: the 45 s email throttle is set', array( $GLOBALS['transients'][ $throttle_key ], $GLOBALS['transient_ttls'][ $throttle_key ] ), array( 1, 45 ) );
check( 'off: no fan-out slot is taken', isset( $GLOBALS['transients'][ FAN_OUT_KEY ] ), false );

reset_state();
$GLOBALS['mail_error'] = 'smtp down';
$result                = ihq_issue_and_send_login_code_for_influencer( influencer() );
check( 'off, email fails: the error is today\'s', $result, array( 'ok' => false, 'error' => 'smtp down' ) );
check( 'off, email fails: the code is deleted', array_key_exists( $token_key, $GLOBALS['options'] ), false );
check( 'off, email fails: the email map is deleted', array_key_exists( $email_map, $GLOBALS['options'] ), false );
check( 'off, email fails: no throttle is set', isset( $GLOBALS['transients'][ $throttle_key ] ), false );
check( 'off, email fails: logged as today', substr_count( logged(), 'IHQ send_login_code failed for ' . INFLUENCER_MAIL . ': smtp down' ), 1 );
check( 'off, email fails: the failed-mail hook is removed again', $GLOBALS['actions']['wp_mail_failed'], array() );

// --- Placeholder guard: not gated. ---
foreach ( array( 'off' => false, 'on' => true ) as $state => $on ) {
	foreach ( array( STAND_IN_MAIL, LEGACY_MAIL ) as $stand_in ) {
		reset_state();
		if ( $on ) {
			switch_on();
		}
		$result = ihq_issue_and_send_login_code_for_influencer( $GLOBALS['users'][ $stand_in ] );
		check( $state . ': ' . $stand_in . ' is refused like an invalid address', $result, array( 'ok' => false, 'error' => 'Invalid email address' ) );
		check( $state . ': ' . $stand_in . ' is never mailed', $GLOBALS['mail'], array() );
		check( $state . ': ' . $stand_in . ' gets no code and no request', array( $GLOBALS['requests'], array_keys( array_diff_key( $GLOBALS['options'], array( OPTION => true ) ) ) ), array( array(), array() ) );
	}
}

// --- Login send, switch on. ---
reset_state();
switch_on();
$GLOBALS['user_meta'][ INFLUENCER_ID ]['_ihq_aicoach_language'] = 'yue';
queue_response( ok_response( SENT_BODY ) );
$result = ihq_issue_and_send_login_code_for_influencer( influencer() );
check( 'on: the answer is today\'s', $result, array( 'ok' => true, 'signup_token' => 'token1', 'expires_minutes' => 15, 'message' => GENERIC ) );
check( 'on: the email carries the do-not-share line', $GLOBALS['mail'][0]['message'], fan_out_login_email( $code, 15 ) );
check( 'on: one request', count( $GLOBALS['requests'] ), 1 );
check(
	'on: the request carries the emailed code and the influencer\'s locale',
	json_decode( $GLOBALS['requests'][0]['args']['body'], true ),
	array( 'platformUsername' => 'influencerhq-wpu-7', 'methods' => 'all', 'kind' => 'one_time_code', 'text' => $code, 'locale' => 'zh-HK' )
);
check( 'on: a fan-out slot is taken', count( $GLOBALS['transients'][ FAN_OUT_KEY ] ), 1 );

// A second send inside 60 seconds (once the 45 s email throttle has passed): email only.
unset( $GLOBALS['transients'][ $throttle_key ] );
$result = ihq_issue_and_send_login_code_for_influencer( influencer() );
check( 'on, second send within 60 s: answered as usual', $result, array( 'ok' => true, 'signup_token' => 'token2', 'expires_minutes' => 15, 'message' => GENERIC ) );
check( 'on, second send within 60 s: the email goes', count( $GLOBALS['mail'] ), 2 );
check( 'on, second send within 60 s: no second request', count( $GLOBALS['requests'] ), 1 );
check( 'on, second send within 60 s: the first code is replaced', array_key_exists( $token_key, $GLOBALS['options'] ), false );

reset_state();
switch_on();
$started                              = time();
$GLOBALS['transients'][ FAN_OUT_KEY ] = array( $started - 3000, $started - 2400, $started - 1800, $started - 1200, $started - 600 );
$result                               = ihq_issue_and_send_login_code_for_influencer( influencer() );
check( 'on, sixth send within an hour: answered as usual', $result['ok'], true );
check( 'on, sixth send within an hour: the email goes', count( $GLOBALS['mail'] ), 1 );
check( 'on, sixth send within an hour: no request', $GLOBALS['requests'], array() );

// A failed call changes nothing the influencer sees.
foreach ( array(
	'transport error' => new WP_Error( 'http_request_failed', 'cURL error 28: timed out' ),
	'HTTP 502'        => array( 'status' => 502, 'body' => '' ),
	'failed methods'  => ok_response( FAILED_BODY ),
) as $label => $response ) {
	reset_state();
	switch_on();
	queue_response( $response );
	$result = ihq_issue_and_send_login_code_for_influencer( influencer() );
	check( 'on, ' . $label . ': the answer is today\'s', $result, array( 'ok' => true, 'signup_token' => 'token1', 'expires_minutes' => 15, 'message' => GENERIC ) );
	check( 'on, ' . $label . ': the code is kept for the email', $GLOBALS['options'][ $email_map ], 'token1' );
}

// A failed email keeps the code whenever it went to the chat apps, whatever influencerhq-api answered.
foreach ( array(
	'a chat app sent'   => ok_response( SENT_BODY ),
	'nothing connected' => ok_response( NOTHING_BODY ),
	'failed methods'    => ok_response( FAILED_BODY ),
	'HTTP 502'          => array( 'status' => 502, 'body' => '' ),
	'timeout'           => new WP_Error( 'http_request_failed', 'cURL error 28: timed out' ),
) as $label => $response ) {
	reset_state();
	switch_on();
	$GLOBALS['mail_error'] = 'smtp down';
	queue_response( $response );
	$result = ihq_issue_and_send_login_code_for_influencer( influencer() );
	check( 'on, email fails, ' . $label . ': answered as a success', $result, array( 'ok' => true, 'signup_token' => 'token1', 'expires_minutes' => 15, 'message' => GENERIC ) );
	check( 'on, email fails, ' . $label . ': the code was sent to the chat apps', json_decode( $GLOBALS['requests'][0]['args']['body'], true )['text'], $code );
	check( 'on, email fails, ' . $label . ': the code is kept', $GLOBALS['options'][ $token_key ]['code_hash'], hash_hmac( 'sha256', $code, 'salt-ihq_login_code' . 'token1' ) );
	check( 'on, email fails, ' . $label . ': the email map is kept', $GLOBALS['options'][ $email_map ], 'token1' );
	check( 'on, email fails, ' . $label . ': the email throttle is set', $GLOBALS['transients'][ $throttle_key ], 1 );
	check( 'on, email fails, ' . $label . ': the email failure is logged', substr_count( logged(), 'IHQ send_login_code email failed for ' . INFLUENCER_MAIL . ', code kept for the chat apps: smtp down' ), 1 );
	check( 'on, email fails, ' . $label . ': not logged as a failed send', strpos( logged(), 'IHQ send_login_code failed for' ), false );
}

// ...and is handled as today when the code did not go to the chat apps.
reset_state();
switch_on();
$GLOBALS['transients'][ FAN_OUT_KEY ] = array( time() - 10 );
$GLOBALS['mail_error']                = 'smtp down';
$result                               = ihq_issue_and_send_login_code_for_influencer( influencer() );
check( 'on, email fails inside the window: the error is today\'s', $result, array( 'ok' => false, 'error' => 'smtp down' ) );
check( 'on, email fails inside the window: no request', $GLOBALS['requests'], array() );
check( 'on, email fails inside the window: the code is deleted', array( array_key_exists( $token_key, $GLOBALS['options'] ), array_key_exists( $email_map, $GLOBALS['options'] ) ), array( false, false ) );
check( 'on, email fails inside the window: logged as today', substr_count( logged(), 'IHQ send_login_code failed for ' . INFLUENCER_MAIL . ': smtp down' ), 1 );

reset_state();
switch_on();
putenv( 'IHQ_SERVER_API_KEY' );
$GLOBALS['mail_error'] = 'smtp down';
$result                = ihq_issue_and_send_login_code_for_influencer( influencer() );
check( 'on, email fails, no IHQ_SERVER_API_KEY: the error is today\'s', $result, array( 'ok' => false, 'error' => 'smtp down' ) );
check( 'on, email fails, no IHQ_SERVER_API_KEY: the code is deleted', array( array_key_exists( $token_key, $GLOBALS['options'] ), array_key_exists( $email_map, $GLOBALS['options'] ) ), array( false, false ) );

// --- Login AJAX. ---
reset_state();
check(
	'ajax off: an influencer gets today\'s answer',
	send_login_ajax( INFLUENCER_MAIL ),
	array( 'success' => true, 'data' => array( 'signup_token' => 'token1', 'expires_minutes' => 15, 'message' => GENERIC ) )
);
reset_state();
$unknown_answer = array( 'success' => true, 'data' => array( 'signup_token' => '', 'expires_minutes' => 15, 'message' => GENERIC, 'skipped' => true ) );
check( 'ajax off: an unknown address gets today\'s answer', send_login_ajax( 'nobody@example.test' ), $unknown_answer );
reset_state();
check( 'ajax off: a stand-in answers like an unknown address', send_login_ajax( STAND_IN_MAIL ), $unknown_answer );
check( 'ajax off: a stand-in is not mailed', $GLOBALS['mail'], array() );
reset_state();
check( 'ajax: an administrator still gets the unknown answer', send_login_ajax( ADMIN_MAIL ), $unknown_answer );

reset_state();
switch_on();
queue_response( ok_response( SENT_BODY ) );
check(
	'ajax on: an influencer\'s answer carries the hint',
	send_login_ajax( INFLUENCER_MAIL ),
	array( 'success' => true, 'data' => array( 'signup_token' => 'token1', 'expires_minutes' => 15, 'message' => GENERIC, 'chat_app_hint' => HINT ) )
);
reset_state();
switch_on();
$unknown_on = array( 'success' => true, 'data' => array( 'signup_token' => '', 'expires_minutes' => 15, 'message' => GENERIC, 'skipped' => true, 'chat_app_hint' => HINT ) );
check( 'ajax on: an unknown address carries the hint too, so it reveals nothing', send_login_ajax( 'nobody@example.test' ), $unknown_on );
reset_state();
switch_on();
check( 'ajax on: a stand-in answers like an unknown address', send_login_ajax( STAND_IN_MAIL ), $unknown_on );
check( 'ajax on: a stand-in makes no request', $GLOBALS['requests'], array() );

reset_state();
switch_on();
queue_response( new WP_Error( 'http_request_failed', 'down' ) );
check(
	'ajax on: a failed call changes nothing in the answer',
	send_login_ajax( INFLUENCER_MAIL ),
	array( 'success' => true, 'data' => array( 'signup_token' => 'token1', 'expires_minutes' => 15, 'message' => GENERIC, 'chat_app_hint' => HINT ) )
);

reset_state();
$GLOBALS['mail_error'] = 'smtp down';
check( 'ajax off: a failed email is today\'s error', send_login_ajax( INFLUENCER_MAIL ), array( 'success' => false, 'data' => array( 'message' => 'smtp down' ) ) );

// --- Module is loaded before the login handler. ---
$functions_source = file_get_contents( __DIR__ . '/../functions.php' );
$module_at        = strpos( $functions_source, "require_once get_template_directory() . '/inc/login-code-fan-out.php';" );
$handler_at       = strpos( $functions_source, "require_once get_template_directory() . '/inc/email-verification-handler.php';" );
check( 'functions.php loads the fan-out module before the login handler', false !== $module_at && false !== $handler_at && $module_at < $handler_at, true );

echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );

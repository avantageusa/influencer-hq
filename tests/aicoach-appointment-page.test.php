<?php
/**
 * Unit test for inc/aicoach-appointment-page.php (PO-3109, step 5): what the appointment
 * link page knows about a link, what it shows in each state, what the Join button does and
 * how its script is loaded. No WordPress bootstrap: the handful of WP functions the file
 * touches are stubbed.
 *
 *     php tests/aicoach-appointment-page.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );
define( 'YEAR_IN_SECONDS', 365 * 24 * 60 * 60 );
define( 'DAY_IN_SECONDS', 24 * 60 * 60 );
define( 'MINUTE_IN_SECONDS', 60 );
define( '_S_VERSION', '9.9.9' );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

function add_action( $hook, $cb ) { $GLOBALS['actions'][] = array( $hook, $cb ); }
$GLOBALS['actions'] = array();
function __( $text, $domain = '' ) { return $text; }
function wp_unslash( $s ) { return $s; }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_salt( $scheme = 'auth' ) { return 'salt-for-' . $scheme; }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function add_query_arg( $key, $value, $url ) { return $url . '?' . rawurlencode( $key ) . '=' . rawurlencode( $value ); }
function wp_sanitize_stub() {}
function wp_generate_uuid4() {
	static $n = 0;
	$n++;
	return sprintf( '00000000-0000-4000-8000-%012d', $n );
}
// The pages that use a template, for get_pages().
$GLOBALS['pages_by_template'] = array();
function get_pages( $args ) {
	$template = $args['meta_value'] ?? '';
	$found    = $GLOBALS['pages_by_template'][ $template ] ?? array();
	return array_slice( $found, 0, (int) ( $args['number'] ?? 0 ) ?: null );
}
function get_permalink( $page ) { return $page->permalink; }
function wp_verify_nonce( $nonce, $action ) { return 'good-nonce' === $nonce && 'ihq_appointment_join' === $action; }

$GLOBALS['wp_options'] = array();
function get_option( $key, $default = false ) { return $GLOBALS['wp_options'][ $key ] ?? $default; }
function update_option( $key, $value, $autoload = true ) { $GLOBALS['wp_options'][ $key ] = $value; return true; }
function delete_option( $key ) { unset( $GLOBALS['wp_options'][ $key ] ); return true; }

// The script modules the page registers.
$GLOBALS['theme_dir']    = '';
$GLOBALS['is_page']      = true;
$GLOBALS['registered']   = array();
$GLOBALS['enqueued']     = array();
function is_page_template( $template ) { return $GLOBALS['is_page'] && 'page-appointment.php' === $template; }
function get_template_directory() { return $GLOBALS['theme_dir']; }
function get_template_directory_uri() { return 'https://example.test/wp-content/themes/influencer-hq'; }
function wp_register_script_module( $id, $src, $deps = array(), $version = false ) {
	$GLOBALS['registered'][] = array( 'id' => $id, 'src' => $src, 'deps' => $deps, 'version' => $version );
}
function wp_enqueue_script_module( $id ) { $GLOBALS['enqueued'][] = $id; }

require __DIR__ . '/../inc/aicoach-modules.php';
require __DIR__ . '/../inc/aicoach-progress.php';
require __DIR__ . '/../inc/aicoach-appointment.php';
require __DIR__ . '/../inc/aicoach-appointment-page.php';

$now   = gmmktime( 12, 0, 0, 10, 10, 2026 );
$start = $now + 3600; // an hour from now
$ref   = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

// A real appointment, made the way the route makes it.
$record = ihq_aicoach_appointment_create( $ref, $start, 'Europe/Belgrade', $now );
$token  = ihq_aicoach_appointment_make_token( $record['id'], $start );

$invalid = array(
	'state'              => 'invalid',
	'id'                 => null,
	'startsAt'           => null,
	'timeZone'           => '',
	'secondsToStart'     => null,
	'secondsToNextState' => null,
);

// --- page_context() ---

check(
	'a valid link an hour before the start is waiting, with what the page needs',
	array(
		'state'              => 'waiting',
		'id'                 => $record['id'],
		'startsAt'           => $start,
		'timeZone'           => 'Europe/Belgrade',
		'secondsToStart'     => 3600,
		'secondsToNextState' => 3000,
	) === ihq_aicoach_appointment_page_context( $token, $now )
);
check( 'twelve minutes before the start it is still waiting', 'waiting' === ihq_aicoach_appointment_page_context( $token, $start - 12 * 60 )['state'] );
check( 'ten minutes before the start it is join', 'join' === ihq_aicoach_appointment_page_context( $token, $start - 10 * 60 )['state'] );
check( 'after the join window it is missed', 'missed' === ihq_aicoach_appointment_page_context( $token, $start + 30 * 60 )['state'] );
check( 'a day and a bit later it is expired', 'expired' === ihq_aicoach_appointment_page_context( $token, $start + 86401 )['state'] );
check( 'after the start the seconds to the start are zero, never negative', 0 === ihq_aicoach_appointment_page_context( $token, $start + 600 )['secondsToStart'] );
check( 'an expired link has no next state', null === ihq_aicoach_appointment_page_context( $token, $start + 86401 )['secondsToNextState'] );

foreach ( array( null, '', 'not-a-token', array( $token ), 5, $token . 'x', strrev( $token ) ) as $bad ) {
	check( 'a link that is not an appointment is invalid: ' . json_encode( $bad ), $invalid === ihq_aicoach_appointment_page_context( $bad, $now ) );
}

$unknown = ihq_aicoach_appointment_make_token( '12345678-1234-4234-8234-123456789012', $start );
check( 'a correctly signed link for an appointment nobody stored is invalid', $invalid === ihq_aicoach_appointment_page_context( $unknown, $now ) );
$wrong_start = ihq_aicoach_appointment_make_token( $record['id'], $start + 60 );
check( 'a correctly signed link whose start is not the stored one is invalid', $invalid === ihq_aicoach_appointment_page_context( $wrong_start, $now ) );

$replacer = ihq_aicoach_appointment_create( $ref, $start + 7200, 'UTC', $now );
check( 'the link of an appointment a newer one replaced is invalid', $invalid === ihq_aicoach_appointment_page_context( $token, $now ) );
check( 'the newer appointment\'s link is valid', 'waiting' === ihq_aicoach_appointment_page_context( ihq_aicoach_appointment_make_token( $replacer['id'], $start + 7200 ), $now )['state'] );
check( 'nothing tells a bad signature, an unknown id and a replaced appointment apart', ihq_aicoach_appointment_page_context( $token, $now ) === ihq_aicoach_appointment_page_context( 'junk', $now ) && ihq_aicoach_appointment_page_context( $unknown, $now ) === ihq_aicoach_appointment_page_context( $token, $now ) );

ihq_aicoach_appointment_mark_completed( $ref, $now );
$finished_token = ihq_aicoach_appointment_make_token( $replacer['id'], $start + 7200 );
check( 'a finished appointment is ended, even before its time', 'ended' === ihq_aicoach_appointment_page_context( $finished_token, $now )['state'] );
check( 'an ended link has no next state', null === ihq_aicoach_appointment_page_context( $finished_token, $now )['secondsToNextState'] );

$GLOBALS['wp_options'][ 'ihq_aicoach_appointment_' . $replacer['id'] ]['timeZone'] = 5;
check( 'a stored time zone that is not text is an empty one', '' === ihq_aicoach_appointment_page_context( $finished_token, $now )['timeZone'] );

// --- coach_page_url() ---

check( 'with no page using the AI Coach template the address is the home page', 'https://example.test/' === ihq_aicoach_appointment_coach_page_url() );
$GLOBALS['pages_by_template']['page-home-aicoach.php'] = array( (object) array( 'permalink' => 'https://example.test/home-ai-coach/' ), (object) array( 'permalink' => 'https://example.test/other/' ) );
check( 'with a page using it the address is that page\'s permalink', 'https://example.test/home-ai-coach/' === ihq_aicoach_appointment_coach_page_url() );
$GLOBALS['pages_by_template']['page-home-aicoach.php'] = array( (object) array( 'permalink' => 'https://example.test/' ) );
check( 'when it is the front page the address is the home page', 'https://example.test/' === ihq_aicoach_appointment_coach_page_url() );
check( 'a page with another template is not the AI Coach page', 'https://example.test/' === ihq_aicoach_appointment_coach_page_url() && array() === get_pages( array( 'meta_value' => 'page-appointment.php', 'number' => 1 ) ) );
$GLOBALS['pages_by_template'] = array();

// --- display_time() ---

check( 'the time is shown in the zone the visitor chose', 'Saturday, 10 October 2026, 14:30 (Europe/Belgrade)' === ihq_aicoach_appointment_display_time( gmmktime( 12, 30, 0, 10, 10, 2026 ), 'Europe/Belgrade' ) );
check( 'another zone, another wall clock', 'Saturday, 10 October 2026, 21:30 (Asia/Tokyo)' === ihq_aicoach_appointment_display_time( gmmktime( 12, 30, 0, 10, 10, 2026 ), 'Asia/Tokyo' ) );
check( 'no zone is UTC', 'Saturday, 10 October 2026, 12:30 (UTC)' === ihq_aicoach_appointment_display_time( gmmktime( 12, 30, 0, 10, 10, 2026 ), '' ) );
check( 'a zone nobody knows is UTC', 'Saturday, 10 October 2026, 12:30 (UTC)' === ihq_aicoach_appointment_display_time( gmmktime( 12, 30, 0, 10, 10, 2026 ), 'Mars/Phobos' ) );

// --- join_allowed() ---

check( 'join is allowed in the join state', ihq_aicoach_appointment_join_allowed( 'join', false ) && ihq_aicoach_appointment_join_allowed( 'join', true ) );
foreach ( array( 'waiting', 'missed' ) as $state ) {
	check( $state . ': no join while "Start now instead" is off', ! ihq_aicoach_appointment_join_allowed( $state, false ) );
	check( $state . ': join once "Start now instead" is on', ihq_aicoach_appointment_join_allowed( $state, true ) );
}
foreach ( array( 'expired', 'ended', 'invalid' ) as $state ) {
	check( $state . ': never a join', ! ihq_aicoach_appointment_join_allowed( $state, false ) && ! ihq_aicoach_appointment_join_allowed( $state, true ) );
}
check( 'start now is off by default', false === IHQ_AICOACH_APPOINTMENT_START_NOW_ENABLED && ! ihq_aicoach_appointment_join_allowed( 'waiting' ) );

// --- page_view() ---

$start_now = array( 'type' => 'start-now', 'label' => 'Start now instead' );
$joinbtn   = array( 'type' => 'join', 'label' => 'Join your video with Sami' );
$book      = function ( $label ) { return array( 'type' => 'book', 'label' => $label ); };

check( 'waiting, start now off: a title, the line before the countdown and no button', array( 'title' => 'Your appointment is coming up', 'message' => 'Your appointment starts in', 'actions' => array() ) === ihq_aicoach_appointment_page_view( 'waiting', false ) );
check( 'waiting, start now on: the Start now instead button', array( 'title' => 'Your appointment is coming up', 'message' => 'Your appointment starts in', 'actions' => array( $start_now ) ) === ihq_aicoach_appointment_page_view( 'waiting', true ) );
check( 'join: the Join your video with Sami button', array( 'title' => 'Your appointment is ready', 'message' => 'Sami is ready for you.', 'actions' => array( $joinbtn ) ) === ihq_aicoach_appointment_page_view( 'join', false ) );
check( 'missed, start now off: You missed your appointment and a way to book again', array( 'title' => 'You missed your appointment', 'message' => '', 'actions' => array( $book( 'Book a new appointment' ) ) ) === ihq_aicoach_appointment_page_view( 'missed', false ) );
check( 'missed, start now on: Start now instead first, then book again', array( 'title' => 'You missed your appointment', 'message' => '', 'actions' => array( $start_now, $book( 'Book a new appointment' ) ) ) === ihq_aicoach_appointment_page_view( 'missed', true ) );
check( 'expired: This link has expired and Book a new appointment', array( 'title' => 'This link has expired', 'message' => '', 'actions' => array( $book( 'Book a new appointment' ) ) ) === ihq_aicoach_appointment_page_view( 'expired', false ) );
check( 'ended: This session has ended and Book another appointment', array( 'title' => 'This session has ended', 'message' => '', 'actions' => array( $book( 'Book another appointment' ) ) ) === ihq_aicoach_appointment_page_view( 'ended', false ) );
check( 'invalid: This link isn\'t valid and Book an appointment', array( 'title' => "This link isn't valid", 'message' => '', 'actions' => array( $book( 'Book an appointment' ) ) ) === ihq_aicoach_appointment_page_view( 'invalid', false ) );
check( 'a state nobody knows is shown as invalid', ihq_aicoach_appointment_page_view( 'invalid', false ) === ihq_aicoach_appointment_page_view( 'something-else', false ) );
check( 'start now only ever shows on waiting and missed', ihq_aicoach_appointment_page_view( 'join', true ) === ihq_aicoach_appointment_page_view( 'join', false ) && ihq_aicoach_appointment_page_view( 'expired', true ) === ihq_aicoach_appointment_page_view( 'expired', false ) && ihq_aicoach_appointment_page_view( 'ended', true ) === ihq_aicoach_appointment_page_view( 'ended', false ) && ihq_aicoach_appointment_page_view( 'invalid', true ) === ihq_aicoach_appointment_page_view( 'invalid', false ) );

// --- join_decision() ---

$page_url = 'https://example.test/appointment/';
$home_url = 'https://example.test/home-ai-coach/';
$join_ref = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
$join_rec = ihq_aicoach_appointment_create( $join_ref, $start, 'Europe/Belgrade', $now );
$join_tok = ihq_aicoach_appointment_make_token( $join_rec['id'], $start );
$at_join  = $start; // inside the join window
$post     = function ( array $over = array() ) use ( $join_tok ) {
	return array_merge( array( 't' => $join_tok, '_wpnonce' => 'good-nonce' ), $over );
};
$back = function ( $token ) use ( $page_url ) { return array( 'ref' => null, 'redirect' => $page_url . '?t=' . rawurlencode( $token ) ); };

check( 'joining inside the window gives the visitor the stored ref and sends them to the AI Coach page', array( 'ref' => $join_ref, 'redirect' => $home_url ) === ihq_aicoach_appointment_join_decision( $post(), $page_url, $home_url, $at_join, false ) );
check( 'a bad nonce shows the page again', $back( $join_tok ) === ihq_aicoach_appointment_join_decision( $post( array( '_wpnonce' => 'old' ) ), $page_url, $home_url, $at_join, false ) );
check( 'no nonce shows the page again', $back( $join_tok ) === ihq_aicoach_appointment_join_decision( array( 't' => $join_tok ), $page_url, $home_url, $at_join, false ) );
check( 'a nonce that is not text shows the page again', $back( $join_tok ) === ihq_aicoach_appointment_join_decision( $post( array( '_wpnonce' => array( 'good-nonce' ) ) ), $page_url, $home_url, $at_join, false ) );
check( 'an edited token shows the page again and never gives a ref', $back( $join_tok . 'x' ) === ihq_aicoach_appointment_join_decision( $post( array( 't' => $join_tok . 'x' ) ), $page_url, $home_url, $at_join, false ) );
check( 'no token shows the page again', $back( '' ) === ihq_aicoach_appointment_join_decision( array( '_wpnonce' => 'good-nonce' ), $page_url, $home_url, $at_join, false ) );
check( 'a token that is not text shows the page again', $back( '' ) === ihq_aicoach_appointment_join_decision( $post( array( 't' => array( $join_tok ) ) ), $page_url, $home_url, $at_join, false ) );
check( 'too early, nothing happens while start now is off', $back( $join_tok ) === ihq_aicoach_appointment_join_decision( $post(), $page_url, $home_url, $start - 3600, false ) );
check( 'too early with start now on, the visitor joins at once', array( 'ref' => $join_ref, 'redirect' => $home_url ) === ihq_aicoach_appointment_join_decision( $post(), $page_url, $home_url, $start - 3600, true ) );
check( 'too late, nothing happens while start now is off', $back( $join_tok ) === ihq_aicoach_appointment_join_decision( $post(), $page_url, $home_url, $start + 1800, false ) );
check( 'too late with start now on, the visitor joins', array( 'ref' => $join_ref, 'redirect' => $home_url ) === ihq_aicoach_appointment_join_decision( $post(), $page_url, $home_url, $start + 1800, true ) );
check( 'an expired link never joins, even with start now on', $back( $join_tok ) === ihq_aicoach_appointment_join_decision( $post(), $page_url, $home_url, $start + 86401, true ) );
check( 'the last second of the join window joins', array( 'ref' => $join_ref, 'redirect' => $home_url ) === ihq_aicoach_appointment_join_decision( $post(), $page_url, $home_url, $start + 15 * 60 - 1, false ) );
check( 'the first second of the missed window does not', $back( $join_tok ) === ihq_aicoach_appointment_join_decision( $post(), $page_url, $home_url, $start + 15 * 60, false ) );
ihq_aicoach_appointment_mark_completed( $join_ref, $now );
check( 'an ended session never joins, even with start now on', $back( $join_tok ) === ihq_aicoach_appointment_join_decision( $post(), $page_url, $home_url, $at_join, true ) );

$broken_ref = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
$broken     = ihq_aicoach_appointment_create( $broken_ref, $start, 'UTC', $now );
$broken_tok = ihq_aicoach_appointment_make_token( $broken['id'], $start );
$GLOBALS['wp_options'][ 'ihq_aicoach_appointment_' . $broken['id'] ]['ref'] = 'not-a-uuid';
check( 'a stored ref that is not a UUID is never put in a cookie', $back( $broken_tok ) === ihq_aicoach_appointment_join_decision( $post( array( 't' => $broken_tok ) ), $page_url, $home_url, $at_join, false ) );
unset( $GLOBALS['wp_options'][ 'ihq_aicoach_appointment_' . $broken['id'] ]['ref'] );
check( 'an appointment stored without a ref does not join', $back( $broken_tok ) === ihq_aicoach_appointment_join_decision( $post( array( 't' => $broken_tok ) ), $page_url, $home_url, $at_join, false ) );

// --- the page request hook and the script ---

$hooks = array_map( function ( $a ) { return $a[0] . ':' . $a[1]; }, $GLOBALS['actions'] );
check( 'the page request runs on template_redirect', in_array( 'template_redirect:ihq_aicoach_appointment_page_request', $hooks, true ) );
check( 'the script is loaded on wp_enqueue_scripts', in_array( 'wp_enqueue_scripts:ihq_aicoach_appointment_page_enqueue', $hooks, true ) );

$theme = sys_get_temp_dir() . '/ihq-appointment-page-test-' . getmypid();
mkdir( $theme . '/js/aicoach', 0777, true );
file_put_contents( $theme . '/js/aicoach/countdown.js', '// countdown' );
touch( $theme . '/js/aicoach/countdown.js', 1700000001 );
file_put_contents( $theme . '/js/aicoach/appointment-page.js', '// page' );
touch( $theme . '/js/aicoach/appointment-page.js', 1700000002 );
$GLOBALS['theme_dir'] = $theme;

$GLOBALS['is_page'] = false;
ihq_aicoach_appointment_page_enqueue();
check( 'on any other page nothing is registered or enqueued', array() === $GLOBALS['registered'] && array() === $GLOBALS['enqueued'] );

$GLOBALS['is_page'] = true;
ihq_aicoach_appointment_page_enqueue();
check(
	'on the link page the countdown module is registered, then the page module that depends on it',
	array(
		array( 'id' => '@ihq/aicoach/countdown', 'src' => 'https://example.test/wp-content/themes/influencer-hq/js/aicoach/countdown.js', 'deps' => array(), 'version' => '1700000001' ),
		array( 'id' => '@ihq/aicoach/appointment-page', 'src' => 'https://example.test/wp-content/themes/influencer-hq/js/aicoach/appointment-page.js', 'deps' => array( '@ihq/aicoach/countdown' ), 'version' => '1700000002' ),
	) === $GLOBALS['registered']
);
check( 'only the page module is enqueued', array( '@ihq/aicoach/appointment-page' ) === $GLOBALS['enqueued'] );

unlink( $theme . '/js/aicoach/countdown.js' );
$GLOBALS['registered'] = array();
ihq_aicoach_appointment_page_enqueue();
check( 'a missing file falls back to the theme version', '9.9.9' === $GLOBALS['registered'][0]['version'] && '1700000002' === $GLOBALS['registered'][1]['version'] );

// The page script imports the countdown module by the id registered above.
$page_js = file_get_contents( __DIR__ . '/../js/aicoach/appointment-page.js' );
check( 'the page script imports the countdown module by its registered id', 1 === preg_match( "#from '@ihq/aicoach/countdown'#", $page_js ) );

array_map( 'unlink', glob( $theme . '/js/aicoach/*.js' ) );
rmdir( $theme . '/js/aicoach' ); rmdir( $theme . '/js' ); rmdir( $theme );

echo $fail ? "\n$fail FAILED\n" : "\nALL PASS\n";
exit( $fail ? 1 : 0 );

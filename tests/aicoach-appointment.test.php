<?php
/**
 * Unit test for inc/aicoach-appointment.php (PO-3109, step 4). No WordPress
 * bootstrap: the handful of WP functions the file touches are stubbed. The time
 * rules are checked against the same cases as js/aicoach/appointment.test.js, so
 * the browser and the server cannot drift apart.
 *
 *     php tests/aicoach-appointment.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );
define( 'YEAR_IN_SECONDS', 365 * 24 * 60 * 60 );
define( 'DAY_IN_SECONDS', 24 * 60 * 60 );
define( 'MINUTE_IN_SECONDS', 60 );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

function add_action( $hook, $cb ) {}
function wp_unslash( $s ) { return $s; }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $s ) ); }
function wp_array_slice_assoc( $array, $keys ) { return array_intersect_key( $array, array_flip( $keys ) ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_salt( $scheme = 'auth' ) { return 'salt-for-' . $scheme; }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function add_query_arg( $key, $value, $url ) { return $url . '?' . rawurlencode( $key ) . '=' . rawurlencode( $value ); }
function wp_generate_uuid4() {
	static $n = 0;
	$n++;
	return sprintf( '00000000-0000-4000-8000-%012d', $n );
}

$GLOBALS['wp_options'] = array();
function get_option( $key, $default = false ) { return $GLOBALS['wp_options'][ $key ] ?? $default; }
function update_option( $key, $value, $autoload = true ) { $GLOBALS['wp_options'][ $key ] = $value; return true; }
function delete_option( $key ) { unset( $GLOBALS['wp_options'][ $key ] ); return true; }

$GLOBALS['wp_transients'] = array();
function get_transient( $key ) { return $GLOBALS['wp_transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['wp_transients'][ $key ] = $value; return true; }

class WP_REST_Server { const CREATABLE = 'POST'; }
class WP_REST_Response {
	public $data;
	public $status;
	public $headers = array();
	public function __construct( $data = null, $status = 200 ) { $this->data = $data; $this->status = $status; }
	public function header( $name, $value ) { $this->headers[ $name ] = $value; }
}
class WP_REST_Request {
	private $json;
	public function __construct( $json ) { $this->json = $json; }
	public function get_json_params() { return $this->json; }
}

// A cookie-less request would set one; the unit test sends it already.
$_COOKIE['ihq_aicoach_ref'] = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

require __DIR__ . '/../inc/aicoach-progress.php';
require __DIR__ . '/../inc/aicoach-appointment.php';

function iso_utc( $instant ) { return gmdate( 'Y-m-d\TH:i:s.000\Z', $instant ); }

// --- zoned_to_instant(): the cases of ZONE_CASES in appointment.test.js ---

$zone_cases = array(
	array( 'a zone without daylight saving (Tokyo, +9)', '2026-10-08', '10:00', 'Asia/Tokyo', '2026-10-08T01:00:00.000Z' ),
	array( 'a half-hour zone (Kolkata, +5:30)', '2026-10-08', '12:00', 'Asia/Kolkata', '2026-10-08T06:30:00.000Z' ),
	array( 'a negative offset (Los Angeles, summer, -7)', '2026-07-01', '09:00', 'America/Los_Angeles', '2026-07-01T16:00:00.000Z' ),
	array( 'the same zone in winter (-8)', '2026-01-15', '09:00', 'America/Los_Angeles', '2026-01-15T17:00:00.000Z' ),
	array( 'UTC itself', '2026-10-08', '12:00', 'UTC', '2026-10-08T12:00:00.000Z' ),
	array( 'Belgrade in summer time (+2)', '2026-10-24', '12:00', 'Europe/Belgrade', '2026-10-24T10:00:00.000Z' ),
	array( 'Belgrade after clocks went back (+1)', '2026-10-26', '12:00', 'Europe/Belgrade', '2026-10-26T11:00:00.000Z' ),
	array( 'a time that happens twice resolves to the first (New York, 01:30 on 1 Nov)', '2026-11-01', '01:30', 'America/New_York', '2026-11-01T05:30:00.000Z' ),
	array( 'the same repeated hour in Belgrade (02:30 on 25 Oct)', '2026-10-25', '02:30', 'Europe/Belgrade', '2026-10-25T00:30:00.000Z' ),
	array( 'midnight', '2026-10-08', '00:00', 'Asia/Tokyo', '2026-10-07T15:00:00.000Z' ),
	array( 'the last slot of the day', '2026-10-08', '23:30', 'UTC', '2026-10-08T23:30:00.000Z' ),
	array( 'a leap day', '2028-02-29', '12:00', 'UTC', '2028-02-29T12:00:00.000Z' ),
);
foreach ( $zone_cases as $case ) {
	list( $description, $date, $time, $zone, $expected ) = $case;
	$result = ihq_aicoach_appointment_zoned_to_instant( $date, $time, $zone );
	check(
		'zoned_to_instant: ' . $description,
		array( 'ok' => true, 'error' => null, 'at' => $expected ) === array( 'ok' => $result['ok'], 'error' => $result['error'], 'at' => $result['ok'] ? iso_utc( $result['instant'] ) : null )
	);
}

foreach ( array(
	array( 'New York, 02:30 on 8 March (clocks go forward)', '2026-03-08', '02:30', 'America/New_York' ),
	array( 'Belgrade, 02:30 on 29 March (clocks go forward)', '2026-03-29', '02:30', 'Europe/Belgrade' ),
) as $case ) {
	list( $description, $date, $time, $zone ) = $case;
	check(
		'zoned_to_instant refuses a time that does not exist: ' . $description,
		array( 'ok' => false, 'instant' => null, 'error' => 'time-does-not-exist' ) === ihq_aicoach_appointment_zoned_to_instant( $date, $time, $zone )
	);
}
check( 'the minute before a skipped hour is accepted', ihq_aicoach_appointment_zoned_to_instant( '2026-03-08', '01:59', 'America/New_York' )['ok'] );
check( 'the minute after a skipped hour is accepted', ihq_aicoach_appointment_zoned_to_instant( '2026-03-08', '03:00', 'America/New_York' )['ok'] );

foreach ( array(
	array( 'a date that is not YYYY-MM-DD', '08/10/2026', '10:00', 'UTC', 'date-invalid' ),
	array( 'a date with no value', '', '10:00', 'UTC', 'date-invalid' ),
	array( 'a day that does not exist (30 February)', '2026-02-30', '10:00', 'UTC', 'date-invalid' ),
	array( 'a month that does not exist', '2026-13-01', '10:00', 'UTC', 'date-invalid' ),
	array( 'a 29 February in a year that is not a leap year', '2027-02-29', '10:00', 'UTC', 'date-invalid' ),
	array( 'a time that is not HH:mm', '2026-10-08', '10', 'UTC', 'time-invalid' ),
	array( 'an hour past 23', '2026-10-08', '25:00', 'UTC', 'time-invalid' ),
	array( '24:00, which is not a time of day', '2026-10-08', '24:00', 'UTC', 'time-invalid' ),
	array( 'a minute past 59', '2026-10-08', '10:60', 'UTC', 'time-invalid' ),
	array( 'a time zone that does not exist', '2026-10-08', '10:00', 'Mars/Phobos', 'time-zone-invalid' ),
	array( 'an empty time zone', '2026-10-08', '10:00', '', 'time-zone-invalid' ),
) as $case ) {
	list( $description, $date, $time, $zone, $error ) = $case;
	check(
		'zoned_to_instant refuses ' . $description,
		array( 'ok' => false, 'instant' => null, 'error' => $error ) === ihq_aicoach_appointment_zoned_to_instant( $date, $time, $zone )
	);
}

check( 'is_valid_time_zone accepts a real zone and UTC', ihq_aicoach_appointment_is_valid_time_zone( 'Europe/Belgrade' ) && ihq_aicoach_appointment_is_valid_time_zone( 'UTC' ) );
check( 'is_valid_time_zone refuses a made-up zone, blanks and non-strings', ! ihq_aicoach_appointment_is_valid_time_zone( 'Mars/Phobos' ) && ! ihq_aicoach_appointment_is_valid_time_zone( '' ) && ! ihq_aicoach_appointment_is_valid_time_zone( '   ' ) && ! ihq_aicoach_appointment_is_valid_time_zone( null ) && ! ihq_aicoach_appointment_is_valid_time_zone( 5 ) );
check( 'is_valid_time_zone refuses an offset or abbreviation that PHP would parse', ! ihq_aicoach_appointment_is_valid_time_zone( '+02:00' ) && ! ihq_aicoach_appointment_is_valid_time_zone( 'CEST' ) );

// --- validate(): the cases of validateAppointment() ---

$now = gmmktime( 12, 0, 0, 10, 8, 2026 ); // 2026-10-08 12:00:00 UTC
$refusal = function ( array $errors ) { return array( 'ok' => false, 'errors' => $errors, 'startsAt' => null ); };

check( 'In 30 minutes counts from now', array( 'ok' => true, 'errors' => array(), 'startsAt' => $now + 1800 ) === ihq_aicoach_appointment_validate( array( 'choice' => 'in-30-minutes' ), $now ) );
check( 'In an hour counts from now', array( 'ok' => true, 'errors' => array(), 'startsAt' => $now + 3600 ) === ihq_aicoach_appointment_validate( array( 'choice' => 'in-an-hour' ), $now ) );
check( 'the relative choices ignore any Other fields that are still filled in', $now + 3600 === ihq_aicoach_appointment_validate( array( 'choice' => 'in-an-hour', 'date' => '2020-01-01', 'time' => '00:00', 'timeZone' => 'nope' ), $now )['startsAt'] );

foreach ( array( array(), array( 'choice' => '' ), array( 'choice' => 'tomorrow' ), array( 'choice' => 'toString' ), array( 'choice' => array( 'in-an-hour' ) ), array( 'choice' => null ) ) as $selection ) {
	check( 'no choice, or an unknown one, asks the visitor to choose: ' . json_encode( $selection ), $refusal( array( 'choice-missing' ) ) === ihq_aicoach_appointment_validate( $selection, $now ) );
}

check(
	'Other with every field and a future time is accepted and converted to UTC',
	array( 'ok' => true, 'errors' => array(), 'startsAt' => gmmktime( 12, 30, 0, 10, 9, 2026 ) ) === ihq_aicoach_appointment_validate( array( 'choice' => 'other', 'date' => '2026-10-09', 'time' => '14:30', 'timeZone' => 'Europe/Belgrade' ), $now )
);

check( 'Other lists every missing field in screen order', $refusal( array( 'date-missing', 'time-zone-missing', 'time-missing' ) ) === ihq_aicoach_appointment_validate( array( 'choice' => 'other' ), $now ) );
check( 'Other with only a date lists the zone and time', $refusal( array( 'time-zone-missing', 'time-missing' ) ) === ihq_aicoach_appointment_validate( array( 'choice' => 'other', 'date' => '2026-10-09' ), $now ) );
check( 'Other with date and zone lists the time', $refusal( array( 'time-missing' ) ) === ihq_aicoach_appointment_validate( array( 'choice' => 'other', 'date' => '2026-10-09', 'timeZone' => 'UTC' ), $now ) );
check( 'Other with zone and time lists the date', $refusal( array( 'date-missing' ) ) === ihq_aicoach_appointment_validate( array( 'choice' => 'other', 'timeZone' => 'UTC', 'time' => '10:00' ), $now ) );
check( 'Other with date and time lists the zone', $refusal( array( 'time-zone-missing' ) ) === ihq_aicoach_appointment_validate( array( 'choice' => 'other', 'date' => '2026-10-09', 'time' => '10:00' ), $now ) );
check( 'a field that is only spaces counts as missing', $refusal( array( 'date-missing', 'time-zone-missing', 'time-missing' ) ) === ihq_aicoach_appointment_validate( array( 'choice' => 'other', 'date' => '  ', 'time' => ' ', 'timeZone' => "\t" ), $now ) );
check( 'a field that is not a string counts as missing', $refusal( array( 'date-missing', 'time-zone-missing', 'time-missing' ) ) === ihq_aicoach_appointment_validate( array( 'choice' => 'other', 'date' => array( '2026-10-09' ), 'time' => 1000, 'timeZone' => null ), $now ) );
check( 'missing fields are reported before any other check, even for a date in the past', $refusal( array( 'time-missing' ) ) === ihq_aicoach_appointment_validate( array( 'choice' => 'other', 'date' => '2020-01-01', 'timeZone' => 'UTC' ), $now ) );

check( 'Other earlier today is in the past', $refusal( array( 'time-in-past' ) ) === ihq_aicoach_appointment_validate( array( 'choice' => 'other', 'date' => '2026-10-08', 'time' => '11:30', 'timeZone' => 'UTC' ), $now ) );
check( 'Other years ago is in the past', $refusal( array( 'time-in-past' ) ) === ihq_aicoach_appointment_validate( array( 'choice' => 'other', 'date' => '2020-01-01', 'time' => '10:00', 'timeZone' => 'UTC' ), $now ) );
$boundary = array( 'choice' => 'other', 'date' => '2026-10-08', 'time' => '12:00', 'timeZone' => 'UTC' );
check( 'a time exactly equal to now is in the past', $refusal( array( 'time-in-past' ) ) === ihq_aicoach_appointment_validate( $boundary, $now ) );
check( 'a time one second after now is accepted', ihq_aicoach_appointment_validate( $boundary, $now - 1 )['ok'] );
check( 'the past check uses the visitor\'s zone (11:00 in Tokyo is already past)', $refusal( array( 'time-in-past' ) ) === ihq_aicoach_appointment_validate( array( 'choice' => 'other', 'date' => '2026-10-08', 'time' => '11:00', 'timeZone' => 'Asia/Tokyo' ), $now ) );
check( 'the past check uses the visitor\'s zone (23:00 in Tokyo is still ahead)', ihq_aicoach_appointment_validate( array( 'choice' => 'other', 'date' => '2026-10-08', 'time' => '23:00', 'timeZone' => 'Asia/Tokyo' ), $now )['ok'] );
check( 'an invalid zone is reported as that, not as missing', $refusal( array( 'time-zone-invalid' ) ) === ihq_aicoach_appointment_validate( array( 'choice' => 'other', 'date' => '2026-10-09', 'time' => '10:00', 'timeZone' => 'Mars/Phobos' ), $now ) );
check( 'an invalid date is reported as that', $refusal( array( 'date-invalid' ) ) === ihq_aicoach_appointment_validate( array( 'choice' => 'other', 'date' => '2026-02-30', 'time' => '10:00', 'timeZone' => 'UTC' ), $now ) );
check( 'an invalid time is reported as that', $refusal( array( 'time-invalid' ) ) === ihq_aicoach_appointment_validate( array( 'choice' => 'other', 'date' => '2026-10-09', 'time' => '25:00', 'timeZone' => 'UTC' ), $now ) );
check( 'a time that does not exist is refused as such', $refusal( array( 'time-does-not-exist' ) ) === ihq_aicoach_appointment_validate( array( 'choice' => 'other', 'date' => '2026-03-08', 'time' => '02:30', 'timeZone' => 'America/New_York' ), gmmktime( 0, 0, 0, 3, 1, 2026 ) ) );

// --- tokens ---

check( 'the secret falls back to a key derived from the site salts', 'salt-for-ihq_aicoach_appointment' === ihq_aicoach_appointment_secret() );

$id    = '11111111-1111-4111-8111-111111111111';
$token = ihq_aicoach_appointment_make_token( $id, 1791000000 );
check( 'a token has two dot-separated parts', 2 === count( explode( '.', $token ) ) );
check( 'a token carries no characters that need escaping in a URL', 1 === preg_match( '/^[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+$/', $token ) );
check( 'a token verifies and returns the id and start', array( 'id' => $id, 'startsAt' => 1791000000 ) === ihq_aicoach_appointment_verify_token( $token ) );

list( $payload_part, $signature_part ) = explode( '.', $token );
$other_payload = ihq_aicoach_appointment_base64url_encode( json_encode( array( 'i' => $id, 's' => 1791000001 ) ) );
check( 'a changed start time is refused', null === ihq_aicoach_appointment_verify_token( $other_payload . '.' . $signature_part ) );
check( 'a changed signature is refused', null === ihq_aicoach_appointment_verify_token( $payload_part . '.' . strrev( $signature_part ) ) );
check( 'a token with no signature is refused', null === ihq_aicoach_appointment_verify_token( $payload_part ) );
check( 'a token with an empty signature is refused', null === ihq_aicoach_appointment_verify_token( $payload_part . '.' ) );
check( 'a token with three parts is refused', null === ihq_aicoach_appointment_verify_token( $token . '.x' ) );
check( 'a token that is not a string is refused', null === ihq_aicoach_appointment_verify_token( null ) && null === ihq_aicoach_appointment_verify_token( array( $token ) ) && null === ihq_aicoach_appointment_verify_token( 5 ) );
check( 'an empty token is refused', null === ihq_aicoach_appointment_verify_token( '' ) );

$signed = function ( $payload_json ) {
	$part = ihq_aicoach_appointment_base64url_encode( $payload_json );
	return $part . '.' . ihq_aicoach_appointment_base64url_encode( hash_hmac( 'sha256', $part, ihq_aicoach_appointment_secret(), true ) );
};
check( 'a correctly signed payload that is not JSON is refused', null === ihq_aicoach_appointment_verify_token( $signed( 'not json' ) ) );
check( 'a correctly signed payload without a start is refused', null === ihq_aicoach_appointment_verify_token( $signed( json_encode( array( 'i' => $id ) ) ) ) );
check( 'a correctly signed payload with a text start is refused', null === ihq_aicoach_appointment_verify_token( $signed( json_encode( array( 'i' => $id, 's' => '1791000000' ) ) ) ) );
check( 'a correctly signed payload with a numeric id is refused', null === ihq_aicoach_appointment_verify_token( $signed( json_encode( array( 'i' => 5, 's' => 1791000000 ) ) ) ) );
check( 'a correctly signed well-formed payload is accepted', array( 'id' => $id, 'startsAt' => 1791000000 ) === ihq_aicoach_appointment_verify_token( $signed( json_encode( array( 'i' => $id, 's' => 1791000000 ) ) ) ) );

define( 'IHQ_AICOACH_APPOINTMENT_SECRET', 'configured-secret' );
check( 'the configured secret wins over the salts', 'configured-secret' === ihq_aicoach_appointment_secret() );
check( 'a token signed with the salts no longer verifies once a secret is configured', null === ihq_aicoach_appointment_verify_token( $token ) );
$token_configured = ihq_aicoach_appointment_make_token( $id, 1791000000 );
check( 'a token signed with the configured secret verifies', array( 'id' => $id, 'startsAt' => 1791000000 ) === ihq_aicoach_appointment_verify_token( $token_configured ) );
check( 'the two secrets give different tokens for the same appointment', $token !== $token_configured );

// --- link ---

$link = ihq_aicoach_appointment_link( $id, 1791000000 );
check( 'the link points to the appointment page with the token', 'https://example.test/appointment/?t=' . rawurlencode( $token_configured ) === $link );

// --- storage ---

$ref = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$first = ihq_aicoach_appointment_create( $ref, 1791000000, 'Europe/Belgrade', $now );
check( 'create returns the record with a generated id', array( 'id' => '00000000-0000-4000-8000-000000000001', 'startsAt' => 1791000000, 'timeZone' => 'Europe/Belgrade', 'createdAt' => '2026-10-08T12:00:00+00:00' ) === $first );
check( 'the appointment has its own option, with the visitor ref', array_merge( $first, array( 'ref' => $ref ) ) === ihq_aicoach_appointment_load( $first['id'] ) );
check( 'the visitor progress holds the appointment', $first === ihq_aicoach_progress_load( $ref )['appointment'] );
check( 'the options are not autoloaded work (stored by update_option)', isset( $GLOBALS['wp_options'][ 'ihq_aicoach_appointment_' . $first['id'] ] ) );

ihq_aicoach_progress_save( $ref, array( 'tier' => '5' ) );
$second = ihq_aicoach_appointment_create( $ref, 1791003600, 'UTC', $now );
check( 'a new appointment replaces the old one: the old option is gone', null === ihq_aicoach_appointment_load( $first['id'] ) );
check( 'the new appointment is stored', 1791003600 === ihq_aicoach_appointment_load( $second['id'] )['startsAt'] );
check( 'the visitor progress now points to the new one', $second === ihq_aicoach_progress_load( $ref )['appointment'] );
check( 'creating an appointment keeps the rest of the visitor progress', '5' === ihq_aicoach_progress_load( $ref )['tier'] );

$other_ref = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
ihq_aicoach_appointment_create( $other_ref, 1791007200, 'UTC', $now );
check( 'another visitor\'s appointment does not touch this one', 1791003600 === ihq_aicoach_appointment_load( $second['id'] )['startsAt'] );

check( 'load refuses an id that is not a UUID', null === ihq_aicoach_appointment_load( 'not-an-id' ) && null === ihq_aicoach_appointment_load( '../../etc' ) && null === ihq_aicoach_appointment_load( null ) && null === ihq_aicoach_appointment_load( array() ) );
$GLOBALS['wp_options']['ihq_aicoach_appointment_not-an-id'] = array( 'startsAt' => 1 );
check( 'load refuses an id that is not a UUID even when an option of that name exists', null === ihq_aicoach_appointment_load( 'not-an-id' ) );
unset( $GLOBALS['wp_options']['ihq_aicoach_appointment_not-an-id'] );
check( 'load returns null for an id with no appointment', null === ihq_aicoach_appointment_load( 'ffffffff-ffff-4fff-8fff-ffffffffffff' ) );

// --- the browser cannot write an appointment through the progress route ---

check(
	'the progress sanitizer drops an appointment sent by the browser',
	! array_key_exists( 'appointment', ihq_aicoach_progress_sanitize_partial( array( 'appointment' => array( 'date' => '2030-01-01', 'time' => '10:00', 'timezone' => 'UTC' ), 'tier' => '5' ) ) )
);

// --- the route ---

$GLOBALS['wp_options'] = array();
$post = function ( $body ) { return ihq_aicoach_appointment_handle_post( new WP_REST_Request( $body ) ); };

$response = $post( array( 'choice' => 'in-an-hour', 'extra' => 'ignored' ) );
check( 'a valid request is answered with 201', 201 === $response->status );
check( 'the answer is never cached', array( 'Cache-Control' => 'no-store, private' ) === $response->headers );
check( 'the answer carries a link and an ISO start', array( 'link', 'startsAt' ) === array_keys( $response->data ) );
$verified = ihq_aicoach_appointment_verify_token( rawurldecode( explode( '?t=', $response->data['link'] )[1] ) );
check( 'the link\'s token verifies', is_array( $verified ) );
check( 'the start is about an hour from the server clock', abs( strtotime( $response->data['startsAt'] ) - ( time() + 3600 ) ) <= 5 );
check( 'the token\'s start matches the answer', gmdate( 'c', $verified['startsAt'] ) === $response->data['startsAt'] );
check( 'the appointment was stored for the visitor in the cookie', $verified['id'] === ihq_aicoach_progress_load( $_COOKIE['ihq_aicoach_ref'] )['appointment']['id'] );

$response = $post( array( 'choice' => 'other', 'date' => '2020-01-01', 'time' => '10:00', 'timeZone' => 'UTC' ) );
check( 'a past time is answered with 400 and the code', 400 === $response->status && array( 'errors' => array( 'time-in-past' ) ) === $response->data );
check( 'a refusal is never cached either', array( 'Cache-Control' => 'no-store, private' ) === $response->headers );
$before = $GLOBALS['wp_options'];
$post( array( 'choice' => 'other' ) );
check( 'a refused request stores nothing', $before === $GLOBALS['wp_options'] );

$response = $post( array( 'choice' => 'other' ) );
check( 'missing fields come back as codes', 400 === $response->status && array( 'errors' => array( 'date-missing', 'time-zone-missing', 'time-missing' ) ) === $response->data );
check( 'a body that is not an object is a request with no choice', array( 'errors' => array( 'choice-missing' ) ) === $post( null )->data );
check( 'non-string fields are ignored, not trusted', array( 'errors' => array( 'date-missing', 'time-zone-missing', 'time-missing' ) ) === $post( array( 'choice' => 'other', 'date' => array( 'x' ), 'time' => 5, 'timeZone' => true ) )->data );

$long_zone = str_repeat( 'a', 500 );
check( 'an oversized time zone is cut and then refused as invalid', array( 'errors' => array( 'time-zone-invalid' ) ) === $post( array( 'choice' => 'other', 'date' => '2030-01-01', 'time' => '10:00', 'timeZone' => $long_zone ) )->data );

$GLOBALS['wp_transients'] = array();
for ( $i = 0; $i < IHQ_AICOACH_PROGRESS_RATE_LIMIT_MAX_WRITES; $i++ ) {
	$post( array( 'choice' => 'in-an-hour' ) );
}
$limited = $post( array( 'choice' => 'in-an-hour' ) );
check( 'past the write limit the answer is 429', 429 === $limited->status && array( 'error' => 'Too many requests. Please slow down.' ) === $limited->data );
check( 'a limited answer is never cached', array( 'Cache-Control' => 'no-store, private' ) === $limited->headers );

echo $fail ? "\n$fail FAILED\n" : "\nALL PASS\n";
exit( $fail ? 1 : 0 );

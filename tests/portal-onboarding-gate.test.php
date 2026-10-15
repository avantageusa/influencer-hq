<?php
/**
 * Unit test for the onboarding entry gate on the Coach page (ENGR-7564,
 * inc/portal-onboarding-gate.php), on top of the store it reads
 * (inc/portal-onboarding-progress.php). No WordPress bootstrap: options, users,
 * user meta, actions, nonces and the REST classes are stubbed in memory. The
 * kill switch (ENGR-7561) and the Influencer role helper (inc/portal-username.php)
 * are separate modules; each is stubbed here, first absent and then present,
 * because the gate checks them by name.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/portal-onboarding-gate.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

// --- WordPress stubs ---

$GLOBALS['wp_options']    = array();
$GLOBALS['users']         = array(); // user_id => user_registered (UTC, 'Y-m-d H:i:s')
$GLOBALS['roles']         = array(); // user_id => role slugs
$GLOBALS['role_checks']   = array(); // user ids the role helper was asked about
$GLOBALS['user_meta']     = array();
$GLOBALS['actions_fired'] = array();
$GLOBALS['hooks']         = array();
$GLOBALS['routes']        = array();
$GLOBALS['current_user']  = 0;
$GLOBALS['switch_value']  = false;

function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['wp_options'] ) ? $GLOBALS['wp_options'][ $key ] : $default; }
function get_userdata( $user_id ) {
	if ( ! isset( $GLOBALS['users'][ $user_id ] ) ) {
		return false;
	}
	$user                  = new stdClass();
	$user->ID              = $user_id;
	$user->user_registered = $GLOBALS['users'][ $user_id ];
	return $user;
}
function add_action( $hook, $cb ) { $GLOBALS['hooks'][] = array( $hook, $cb ); }
function do_action( $hook, ...$args ) { $GLOBALS['actions_fired'][] = array_merge( array( $hook ), $args ); }
function get_user_meta( $user_id, $key, $single = false ) { return $GLOBALS['user_meta'][ $user_id ][ $key ] ?? ''; }
function update_user_meta( $user_id, $key, $value ) { $GLOBALS['user_meta'][ $user_id ][ $key ] = $value; return true; }
function get_current_user_id() { return $GLOBALS['current_user']; }
function is_user_logged_in() { return $GLOBALS['current_user'] > 0; }
function wp_verify_nonce( $nonce, $action ) { return 'wp_rest' === $action && 'good-nonce' === $nonce ? 1 : false; }
function wp_create_nonce( $action ) { return 'nonce-for-' . $action; }
function rest_url( $path ) { return 'https://example.test/wp-json/' . $path; }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function register_rest_route( $namespace, $route, $args ) { $GLOBALS['routes'][] = array( $namespace, $route, $args ); }

class WP_Error {
	public $data;
	public function __construct( $code = '', $message = '', $data = array() ) { $this->data = $data; }
}
class WP_REST_Server {
	const READABLE  = 'GET';
	const CREATABLE = 'POST';
}
class WP_REST_Response {
	public $data;
	public $status;
	public $headers = array();
	public function __construct( $data = null, $status = 200 ) { $this->data = $data; $this->status = $status; }
	public function header( $key, $value ) { $this->headers[ $key ] = $value; }
}
class WP_REST_Request {
	private $nonce;
	public function __construct( $nonce = 'good-nonce' ) { $this->nonce = $nonce; }
	public function get_header( $key ) { return 'X-WP-Nonce' === $key ? $this->nonce : ''; }
	public function get_json_params() { return null; }
}

require __DIR__ . '/../inc/portal-onboarding-progress.php';
require __DIR__ . '/../inc/portal-onboarding-gate.php';

const RELEASE      = '2026-11-01T00:00:00Z';
const BEFORE       = '2026-10-31 23:59:59';
const AT_RELEASE   = '2026-11-01 00:00:00';
const AFTER        = '2026-11-01 00:00:01';
const NEW_USER     = 7;
const OLD_USER     = 8;
const EXACT_USER   = 9;
const STAFF_USER   = 10;
const INFLUENCER   = array( 'influencer' );
const STAFF        = array( 'administrator' );

function reset_world() {
	$GLOBALS['wp_options']    = array( 'ihq_portal_onboarding_release_date' => RELEASE );
	$GLOBALS['users']         = array( NEW_USER => AFTER, OLD_USER => BEFORE, EXACT_USER => AT_RELEASE, STAFF_USER => AFTER );
	$GLOBALS['roles']         = array( NEW_USER => INFLUENCER, OLD_USER => INFLUENCER, EXACT_USER => INFLUENCER, STAFF_USER => STAFF );
	$GLOBALS['role_checks']   = array();
	$GLOBALS['user_meta']     = array();
	$GLOBALS['actions_fired'] = array();
	$GLOBALS['current_user']  = NEW_USER;
	$GLOBALS['switch_value']  = true;
}

check( 'option and action names', 'ihq_portal_onboarding_release_date' === IHQ_PORTAL_ONBOARDING_RELEASE_OPTION && 'ihq_portal_onboarding_render_layer' === IHQ_PORTAL_ONBOARDING_RENDER_ACTION );

check( 'earliest accepted release instant is 2026-01-01T00:00:00Z', 1767225600 === IHQ_PORTAL_ONBOARDING_EARLIEST_RELEASE );

// --- Influencer role helper absent: nobody is an Influencer, nobody is launched ---

reset_world();
check( 'without the role helper nobody reads as an Influencer', false === ihq_portal_onboarding_is_influencer( NEW_USER ) );
check( 'without the role helper an eligible user is not launched', false === ihq_portal_onboarding_should_launch( NEW_USER ) );

// inc/portal-username.php defines this; stubbed from here on.
if ( ! function_exists( 'ihq_user_has_influencer_role_id' ) ) {
	function ihq_user_has_influencer_role_id( $user_id ) {
		$GLOBALS['role_checks'][] = $user_id;
		return in_array( 'influencer', $GLOBALS['roles'][ $user_id ] ?? array(), true );
	}
}

reset_world();
check( 'an Influencer account reads as an Influencer', true === ihq_portal_onboarding_is_influencer( NEW_USER ) );
check( 'an administrator account does not read as an Influencer', false === ihq_portal_onboarding_is_influencer( STAFF_USER ) );
check( 'the role helper is asked about the given user ids', array( NEW_USER, STAFF_USER ) === $GLOBALS['role_checks'] );

// --- Kill switch module absent (this PR merged before ENGR-7561): nobody is launched ---

reset_world();
check( 'without the kill switch module the switch reads off', false === ihq_portal_onboarding_switch_on() );
check( 'without the kill switch module an eligible user is not launched', false === ihq_portal_onboarding_should_launch( NEW_USER ) );
ihq_portal_onboarding_maybe_render();
check( 'without the kill switch module nothing is rendered', array() === $GLOBALS['actions_fired'] );

// The kill switch module (ENGR-7561) defines this; stubbed from here on.
if ( ! function_exists( 'ihq_portal_onboarding_enabled' ) ) {
	function ihq_portal_onboarding_enabled() { return $GLOBALS['switch_value']; }
}

// --- Release date parsing ---

$dates = array(
	'2026-11-01T00:00:00Z'      => 1793491200,
	'2026-11-01 00:00:00'       => 1793491200, // no zone: UTC
	'2026-11-01T02:00:00+02:00' => 1793491200,
	'  2026-11-01  '            => 1793491200,
	'1793491200'                => 1793491200,
	'2026-11-01T00:00Z'         => 1793491200, // no seconds
	'2026-11-01T02:00:00+0200'  => 1793491200, // offset without a colon
	'1767225600'                => 1767225600, // the earliest accepted instant
	'2026-01-01'                => 1767225600,
);
foreach ( $dates as $raw => $expected ) {
	$GLOBALS['wp_options']['ihq_portal_onboarding_release_date'] = $raw;
	check( 'release date ' . var_export( $raw, true ) . ' parses', $expected === ihq_portal_onboarding_release_timestamp() );
}
$GLOBALS['wp_options']['ihq_portal_onboarding_release_date'] = 1793491200;
check( 'an integer release date is used as is', 1793491200 === ihq_portal_onboarding_release_timestamp() );
$unreadable = array(
	'', '   ', 'soon', array( '2026-11-01' ), 0, -5, null, false,
	'20261101',                  // compact date: would be 1970-08-23 as a timestamp
	'1767225599',                // one second before the earliest accepted instant
	1767225599,
	'2025-12-31T23:59:59Z',
	'1970-01-01T00:00:00Z',
	'tomorrow',                  // relative values move with every request
	'next monday',
	'yesterday',
	'+1 week',
	'2026-02-30',                // impossible date strtotime() would roll into March
	'2026-13-01',
	'2026-11-01T24:00:00Z',
	'2026-11-01T00:60:00Z',
	'2026-11-01 UTC',            // a zone needs a time
	'2026-11-01T00:00:00 GMT',   // not an ISO-8601 zone
	'01/11/2026',
	'2026-11-01T00:00:00Z extra',
);
foreach ( $unreadable as $bad ) {
	$GLOBALS['wp_options']['ihq_portal_onboarding_release_date'] = $bad;
	check( 'release date ' . var_export( $bad, true ) . ' reads as unset', null === ihq_portal_onboarding_release_timestamp() );
}
unset( $GLOBALS['wp_options']['ihq_portal_onboarding_release_date'] );
check( 'a missing release date reads as unset', null === ihq_portal_onboarding_release_timestamp() );

// --- Eligibility cases ---

reset_world();
check( 'eligible: created after the release, not complete, switch on', true === ihq_portal_onboarding_should_launch( NEW_USER ) );

reset_world();
check( 'not launched: post-release account without the Influencer role (administrator)', false === ihq_portal_onboarding_should_launch( STAFF_USER ) );
$GLOBALS['roles'][ NEW_USER ] = array();
check( 'not launched: post-release account with no role', false === ihq_portal_onboarding_should_launch( NEW_USER ) );

reset_world();
check( 'not launched: account created before the release (treated as complete)', false === ihq_portal_onboarding_should_launch( OLD_USER ) );
check( 'not launched: account created exactly at the release instant', false === ihq_portal_onboarding_should_launch( EXACT_USER ) );

reset_world();
$GLOBALS['switch_value'] = false;
check( 'not launched: kill switch off', false === ihq_portal_onboarding_should_launch( NEW_USER ) );

reset_world();
unset( $GLOBALS['wp_options']['ihq_portal_onboarding_release_date'] );
check( 'not launched: no release date set (deploy without the DEPLOYMENT NOTE step)', false === ihq_portal_onboarding_should_launch( NEW_USER ) );

reset_world();
ihq_portal_onboarding_mark_complete( NEW_USER );
check( 'not launched: onboarding already complete', false === ihq_portal_onboarding_should_launch( NEW_USER ) );

reset_world();
check( 'not launched: logged out (user 0)', false === ihq_portal_onboarding_should_launch( 0 ) );
check( 'not launched: unknown user', false === ihq_portal_onboarding_should_launch( 4242 ) );
$GLOBALS['users'][ NEW_USER ] = '';
check( 'not launched: user without a registration date', false === ihq_portal_onboarding_should_launch( NEW_USER ) );
$GLOBALS['users'][ NEW_USER ] = 'garbage';
check( 'not launched: unreadable registration date', false === ihq_portal_onboarding_should_launch( NEW_USER ) );

reset_world();
ihq_portal_onboarding_merge( NEW_USER, array( 'stage' => 'equity' ) );
check( 'still launched mid-way (resume): a saved stage is not completion', true === ihq_portal_onboarding_should_launch( NEW_USER ) );

// --- Rendering: screen 1 for a new user, the saved stage on resume ---

reset_world();
ihq_portal_onboarding_maybe_render();
check( 'an eligible new user gets the layer once', 1 === count( $GLOBALS['actions_fired'] ) && 'ihq_portal_onboarding_render_layer' === $GLOBALS['actions_fired'][0][0] );
$context = $GLOBALS['actions_fired'][0][1];
check( 'the layer context state is the store\'s default state, so the shell starts at screen 1', '{"stage":"","answers":{},"platforms":[],"handles":{},"language":"","completed":false,"completed_at":""}' === json_encode( $context['state'] ) );
check( 'the layer context carries both routes and the wp_rest nonce', array( 'state', 'progressUrl', 'completeUrl', 'nonce' ) === array_keys( $context ) && 'https://example.test/wp-json/ihq/v1/onboarding/progress' === $context['progressUrl'] && 'https://example.test/wp-json/ihq/v1/onboarding/complete' === $context['completeUrl'] && 'nonce-for-wp_rest' === $context['nonce'] );

reset_world();
ihq_portal_onboarding_merge( NEW_USER, array( 'stage' => 'world_tour', 'answers' => array( 'baccarat_experience' => 'played_some' ) ) );
$GLOBALS['actions_fired'] = array();
ihq_portal_onboarding_maybe_render();
check( 'a returning user resumes at the saved stage with saved answers', 1 === count( $GLOBALS['actions_fired'] ) && '{"stage":"world_tour","answers":{"baccarat_experience":"played_some"},"platforms":[],"handles":{},"language":"","completed":false,"completed_at":""}' === json_encode( $GLOBALS['actions_fired'][0][1]['state'] ) );

$not_rendered = array(
	'completed user'      => function () { ihq_portal_onboarding_mark_complete( NEW_USER ); $GLOBALS['actions_fired'] = array(); },
	'pre-release account' => function () { $GLOBALS['current_user'] = OLD_USER; },
	'kill switch off'     => function () { $GLOBALS['switch_value'] = false; },
	'logged out'          => function () { $GLOBALS['current_user'] = 0; },
	'non-Influencer account (administrator)' => function () { $GLOBALS['current_user'] = STAFF_USER; },
);
foreach ( $not_rendered as $label => $setup ) {
	reset_world();
	$setup();
	ihq_portal_onboarding_maybe_render();
	check( 'Coach page unchanged (nothing rendered) for: ' . $label, array() === $GLOBALS['actions_fired'] );
}

// --- Completion route ---

check( 'the completion route is registered on rest_api_init', in_array( array( 'rest_api_init', 'ihq_portal_onboarding_register_complete_route' ), $GLOBALS['hooks'], true ) );
$GLOBALS['routes'] = array();
ihq_portal_onboarding_register_complete_route();
check( 'POST /ihq/v1/onboarding/complete with the store\'s permission check', array(
	array( 'ihq/v1', '/onboarding/complete', array( 'methods' => 'POST', 'callback' => 'ihq_portal_onboarding_handle_complete', 'permission_callback' => 'ihq_portal_onboarding_permission_check' ) ),
) === $GLOBALS['routes'] );

reset_world();
$response = ihq_portal_onboarding_handle_complete();
check( 'completing returns 200', 200 === $response->status );
check( 'completing is never cached', array( 'Cache-Control' => 'no-store, private' ) === $response->headers );
check( 'completing stores the flag', true === ihq_portal_onboarding_is_complete( NEW_USER ) );
check( 'completing returns the completed state', true === $response->data['completed'] );
$GLOBALS['actions_fired'] = array();
ihq_portal_onboarding_maybe_render();
check( 'reopening the Coach page after completing does not start onboarding', array() === $GLOBALS['actions_fired'] );

check( 'the completion route accepts a logged-in request with a good nonce', true === ihq_portal_onboarding_permission_check( new WP_REST_Request() ) );
check( 'the completion route refuses a bad nonce with 403', array( 'status' => 403 ) === ihq_portal_onboarding_permission_check( new WP_REST_Request( 'bad-nonce' ) )->data );
check( 'the completion route refuses a missing nonce with 403', array( 'status' => 403 ) === ihq_portal_onboarding_permission_check( new WP_REST_Request( '' ) )->data );

$GLOBALS['current_user'] = 0;
check( 'the completion route refuses a logged-out request with 401', array( 'status' => 401 ) === ihq_portal_onboarding_permission_check( new WP_REST_Request() )->data );

// --- The Coach page calls the gate ---

$page = file_get_contents( __DIR__ . '/../page-portal-home.php' );
check( 'page-portal-home.php calls ihq_portal_onboarding_maybe_render()', false !== strpos( $page, 'ihq_portal_onboarding_maybe_render()' ) );
check( 'the call comes right after the portal header', strpos( $page, "get_template_part( 'template-parts/portal-header' )" ) < strpos( $page, 'ihq_portal_onboarding_maybe_render();' ) );

echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );

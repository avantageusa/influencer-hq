<?php
/**
 * Unit test for inc/visitor-intent-logout.php (ENGR-7260). No WordPress
 * bootstrap: the WP functions the module touches are stubbed below, e.g.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/visitor-intent-logout.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );

$GLOBALS['actions'] = array();

function add_action( $hook, $callback ) { $GLOBALS['actions'][ $hook ][] = $callback; }

require __DIR__ . '/../inc/visitor-intent-logout.php';

$failures = 0;

function check( $label, $actual, $expected ) {
	global $failures;
	if ( $actual === $expected ) {
		echo "ok   - {$label}\n";
		return;
	}
	$failures++;
	echo "FAIL - {$label}\n  expected: " . var_export( $expected, true ) . "\n  actual:   " . var_export( $actual, true ) . "\n";
}

function recorder() {
	$GLOBALS['expired'] = array();
	return function ( $name ) {
		$GLOBALS['expired'][] = $name;
	};
}

// The hook is registered on wp_logout, so every wp_logout_url() link runs it.
check( 'registers on wp_logout', $GLOBALS['actions'], array( 'wp_logout' => array( 'ihq_visitor_logout_on_wp_logout' ) ) );

check( 'cookie names match js/ihq-visitor-intent.js', IHQ_VISITOR_LOGOUT_COOKIES, array( 'ihq_visitor_intent', 'ihq_visitor_code_issued' ) );

// Logged-in session with both guest cookies left over: both expire.
$_COOKIE = array(
	'ihq_visitor_intent'      => '{"comm_methods":{"email":"a@example.test"}}',
	'ihq_visitor_code_issued' => '1',
	'wordpress_logged_in_x'   => 'session',
	'ihq_ref'                 => 'REF123',
);
$cleared = ihq_visitor_logout_clear_cookies( recorder() );
check( 'both cookies: returns cleared names', $cleared, array( 'ihq_visitor_intent', 'ihq_visitor_code_issued' ) );
check( 'both cookies: Set-Cookie sent for each', $GLOBALS['expired'], array( 'ihq_visitor_intent', 'ihq_visitor_code_issued' ) );
check( 'both cookies: dropped from $_COOKIE, others kept', $_COOKIE, array( 'wordpress_logged_in_x' => 'session', 'ihq_ref' => 'REF123' ) );

// Only the intent cookie present.
$_COOKIE = array( 'ihq_visitor_intent' => '{}' );
$cleared = ihq_visitor_logout_clear_cookies( recorder() );
check( 'intent only: returns cleared name', $cleared, array( 'ihq_visitor_intent' ) );
check( 'intent only: one Set-Cookie', $GLOBALS['expired'], array( 'ihq_visitor_intent' ) );
check( 'intent only: $_COOKIE empty', $_COOKIE, array() );

// Only the code-issued cookie present.
$_COOKIE = array( 'ihq_visitor_code_issued' => '1' );
$cleared = ihq_visitor_logout_clear_cookies( recorder() );
check( 'code-issued only: returns cleared name', $cleared, array( 'ihq_visitor_code_issued' ) );
check( 'code-issued only: one Set-Cookie', $GLOBALS['expired'], array( 'ihq_visitor_code_issued' ) );
check( 'code-issued only: $_COOKIE empty', $_COOKIE, array() );

// Neither cookie: nothing sent, nothing else touched.
$_COOKIE = array( 'wordpress_logged_in_x' => 'session' );
$cleared = ihq_visitor_logout_clear_cookies( recorder() );
check( 'no cookies: returns empty list', $cleared, array() );
check( 'no cookies: no Set-Cookie', $GLOBALS['expired'], array() );
check( 'no cookies: $_COOKIE unchanged', $_COOKIE, array( 'wordpress_logged_in_x' => 'session' ) );

// An empty-string cookie value is still a cookie the browser holds.
$_COOKIE = array( 'ihq_visitor_intent' => '' );
$cleared = ihq_visitor_logout_clear_cookies( recorder() );
check( 'empty value: still expired', $cleared, array( 'ihq_visitor_intent' ) );
check( 'empty value: Set-Cookie sent', $GLOBALS['expired'], array( 'ihq_visitor_intent' ) );

// Expiry options match the JS writer (Path=/, no Domain, SameSite=Lax, readable by JS).
check(
	'expired cookie options over https',
	ihq_visitor_logout_expired_cookie_options( true ),
	array( 'expires' => 1, 'path' => '/', 'secure' => true, 'httponly' => false, 'samesite' => 'Lax' )
);
check(
	'expired cookie options over http',
	ihq_visitor_logout_expired_cookie_options( 0 ),
	array( 'expires' => 1, 'path' => '/', 'secure' => false, 'httponly' => false, 'samesite' => 'Lax' )
);

if ( $failures > 0 ) {
	echo "\n{$failures} failure(s)\n";
	exit( 1 );
}
echo "\nAll visitor-intent logout tests passed\n";

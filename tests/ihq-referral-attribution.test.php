<?php
/**
 * Unit test for inc/ihq-referral-attribution.php and the ihq_ref TTL accessor
 * in inc/ihq-env.php (ENGR-6966). No WordPress bootstrap: the WP functions the
 * modules touch are stubbed below, e.g.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/ihq-referral-attribution.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );
define( 'IHQ_ENVIRONMENT', 'test' );
define( 'IHQ_API_BASE_URL', 'https://api.example.test/qc' );
define( 'IHQ_GAME_PORTAL_BASE_URL', 'https://portal.example.test/av-baccarat' );
define( 'IHQ_INFLUENCER_API_KEY', 'key' );

$GLOBALS['actions']   = array();
$GLOBALS['enqueued']  = array();
$GLOBALS['localized'] = array();

function add_action( $hook, $callback ) { $GLOBALS['actions'][ $hook ][] = $callback; }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function wp_parse_url( $u ) { return parse_url( $u ); }
function esc_html( $s ) { return $s; }
function wp_die( $m ) { throw new RuntimeException( 'wp_die: ' . $m ); }
function wp_unslash( $v ) { return stripslashes( $v ); }
// Close enough to WordPress for these inputs: strip tags, collapse whitespace, trim.
function sanitize_text_field( $s ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( $s ) ) ); }
function get_template_directory() { return __DIR__ . '/..'; }
function get_template_directory_uri() { return 'https://ihq.example.test/wp-content/themes/ihq'; }
function wp_enqueue_script( ...$args ) { $GLOBALS['enqueued'][] = $args; }
function wp_localize_script( ...$args ) { $GLOBALS['localized'][] = $args; }

require __DIR__ . '/../inc/ihq-env.php';
require __DIR__ . '/../inc/ihq-referral-attribution.php';

$fail = 0;
function check( $label, $actual, $expected ) {
	global $fail;
	$ok = $actual === $expected;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $label . "\n";
	if ( ! $ok ) {
		echo '  expected: ' . var_export( $expected, true ) . "\n  actual:   " . var_export( $actual, true ) . "\n";
		$fail++;
	}
}

// --- Sanitising: sanitize_text_field then a 64-character cap. ---
check( 'sanitize: plain code unchanged', ihq_ref_sanitize_code( 'alice_01' ), 'alice_01' );
check( 'sanitize: tags stripped, whitespace trimmed', ihq_ref_sanitize_code( "  <b>alice</b>\n" ), 'alice' );
check( 'sanitize: slashes unslashed', ihq_ref_sanitize_code( "o\\'neil" ), "o'neil" );
check( 'sanitize: 64 chars kept whole', ihq_ref_sanitize_code( str_repeat( 'a', 64 ) ), str_repeat( 'a', 64 ) );
check( 'sanitize: 65 chars capped to 64', ihq_ref_sanitize_code( str_repeat( 'b', 65 ) ), str_repeat( 'b', 64 ) );
check( 'sanitize: cap counts characters, not bytes', ihq_ref_sanitize_code( str_repeat( 'é', 70 ) ), str_repeat( 'é', 64 ) );
check( 'sanitize: array -> empty', ihq_ref_sanitize_code( array( 'x' ) ), '' );
check( 'sanitize: null -> empty', ihq_ref_sanitize_code( null ), '' );
check( 'sanitize: int -> empty', ihq_ref_sanitize_code( 42 ), '' );
check( 'sanitize: tags only -> empty', ihq_ref_sanitize_code( '<script></script>' ), '' );
check( 'max length constant', IHQ_REF_MAX_LENGTH, 64 );
check( 'cookie name constant', IHQ_REF_COOKIE_NAME, 'ihq_ref' );

// --- Reading the cookie. ---
$_COOKIE = array();
check( 'cookie code: absent -> empty', ihq_ref_cookie_code(), '' );
$_COOKIE = array( 'ihq_ref' => ' <i>carol</i> ' );
check( 'cookie code: present -> sanitised', ihq_ref_cookie_code(), 'carol' );
$_COOKIE = array( 'ihq_ref' => array( 'nested' ) );
check( 'cookie code: array cookie -> empty', ihq_ref_cookie_code(), '' );

// --- Resolving: explicit code wins over the cookie. ---
$_COOKIE = array( 'ihq_ref' => 'cookie-code' );
check( 'resolve: explicit wins over cookie', ihq_ref_resolve_referrer_code( 'stored-code' ), 'stored-code' );
check( 'resolve: empty explicit -> cookie', ihq_ref_resolve_referrer_code( '' ), 'cookie-code' );
check( 'resolve: default arg -> cookie', ihq_ref_resolve_referrer_code(), 'cookie-code' );
check( 'resolve: explicit that sanitises to empty -> cookie', ihq_ref_resolve_referrer_code( '<b></b>' ), 'cookie-code' );
check( 'resolve: non-string explicit -> cookie', ihq_ref_resolve_referrer_code( null ), 'cookie-code' );
check( 'resolve: explicit is sanitised', ihq_ref_resolve_referrer_code( str_repeat( 'z', 80 ) ), str_repeat( 'z', 64 ) );
$_COOKIE = array();
check( 'resolve: neither -> empty', ihq_ref_resolve_referrer_code( '' ), '' );
check( 'resolve: explicit, no cookie', ihq_ref_resolve_referrer_code( 'dave' ), 'dave' );

// --- Expired-cookie header options match what the capture script writes. ---
check(
	'expired cookie options, https',
	ihq_ref_expired_cookie_options( true ),
	array( 'expires' => 1, 'path' => '/', 'secure' => true, 'httponly' => false, 'samesite' => 'Lax' )
);
check(
	'expired cookie options, http',
	ihq_ref_expired_cookie_options( false ),
	array( 'expires' => 1, 'path' => '/', 'secure' => false, 'httponly' => false, 'samesite' => 'Lax' )
);

// --- Clearing. ---
$writes   = array();
$recorder = function ( $name ) use ( &$writes ) { $writes[] = $name; };

$_COOKIE = array( 'other' => 'keep' );
ihq_ref_clear_cookie( $recorder );
check( 'clear: no cookie -> no Set-Cookie', $writes, array() );
check( 'clear: no cookie -> other cookies untouched', $_COOKIE, array( 'other' => 'keep' ) );

$_COOKIE = array( 'ihq_ref' => 'alice', 'other' => 'keep' );
ihq_ref_clear_cookie( $recorder );
check( 'clear: cookie removed from $_COOKIE, others kept', $_COOKIE, array( 'other' => 'keep' ) );
check( 'clear: Set-Cookie sent once for ihq_ref', $writes, array( 'ihq_ref' ) );
check( 'clear: cookie code now empty', ihq_ref_cookie_code(), '' );

ihq_ref_clear_cookie( $recorder );
check( 'clear: second clear in the same request is a no-op', $writes, array( 'ihq_ref' ) );

$_COOKIE = array( 'ihq_ref' => '' );
ihq_ref_clear_cookie( $recorder );
check( 'clear: empty-valued cookie still expired', $writes, array( 'ihq_ref', 'ihq_ref' ) );
check( 'clear: empty-valued cookie removed', $_COOKIE, array() );

// --- TTL accessor (inc/ihq-env.php). ---
check( 'ttl default constant', IHQ_REF_COOKIE_TTL_DEFAULT_DAYS, 90 );
check( 'ttl: unset -> 90', ihq_env_ref_cookie_ttl_days(), 90 );
foreach ( array( '30' => 30, '1' => 1, '365' => 365, '0' => 90, '-5' => 90, 'abc' => 90, '12.5' => 90, ' 30' => 90 ) as $raw => $expected ) {
	putenv( 'IHQ_REF_COOKIE_TTL_DAYS=' . $raw );
	check( 'ttl: ' . var_export( (string) $raw, true ) . ' -> ' . $expected, ihq_env_ref_cookie_ttl_days(), $expected );
}
putenv( 'IHQ_REF_COOKIE_TTL_DAYS' );

// --- Capture script config + enqueue. ---
check(
	'capture config (default TTL)',
	ihq_ref_capture_script_config(),
	array( 'cookieName' => 'ihq_ref', 'queryParam' => 'ref', 'cookieDays' => 90, 'maxLength' => 64 )
);
putenv( 'IHQ_REF_COOKIE_TTL_DAYS=14' );
check(
	'capture config (TTL from env)',
	ihq_ref_capture_script_config(),
	array( 'cookieName' => 'ihq_ref', 'queryParam' => 'ref', 'cookieDays' => 14, 'maxLength' => 64 )
);
putenv( 'IHQ_REF_COOKIE_TTL_DAYS' );

check( 'enqueue hooked on wp_enqueue_scripts', $GLOBALS['actions']['wp_enqueue_scripts'], array( 'ihq_ref_enqueue_capture_script' ) );
ihq_ref_enqueue_capture_script();
check(
	'enqueue: script handle, URL, no deps, filemtime version, in head',
	$GLOBALS['enqueued'],
	array(
		array(
			'ihq-ref-capture',
			'https://ihq.example.test/wp-content/themes/ihq/js/ihq-ref-capture.js',
			array(),
			(string) filemtime( __DIR__ . '/../js/ihq-ref-capture.js' ),
			false,
		),
	)
);
check(
	'enqueue: config localized as IHQ_REF_CAPTURE',
	$GLOBALS['localized'],
	array(
		array(
			'ihq-ref-capture',
			'IHQ_REF_CAPTURE',
			array( 'cookieName' => 'ihq_ref', 'queryParam' => 'ref', 'cookieDays' => 90, 'maxLength' => 64 ),
		),
	)
);

echo $fail === 0 ? "ALL PASS\n" : "$fail FAILED\n";
exit( $fail === 0 ? 0 : 1 );

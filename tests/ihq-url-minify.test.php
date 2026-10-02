<?php
/**
 * Unit test for inc/ihq-url-minify.php (ENGR-6966): POST /minify on the
 * influencerhq-api base, shortUrl only when the response carries a shortCode
 * and an https shortUrl, the original URL on every failure. No live requests:
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/ihq-url-minify.test.php
 *
 * Exit code is non-zero on any failure.
 */
ini_set( 'error_log', '/dev/null' );
define( 'ABSPATH', '/' );
define( 'IHQ_ENVIRONMENT', 'test' );
define( 'IHQ_API_BASE_URL', 'https://api.example.test/qc' );
define( 'IHQ_GAME_PORTAL_BASE_URL', 'https://portal.example.test/av-baccarat' );
define( 'IHQ_INFLUENCER_API_KEY', 'key' );

class WP_Error {
	private $message;
	public function __construct( $code = '', $message = '' ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}

function wp_parse_url( $u ) { return parse_url( $u ); }
function wp_json_encode( $v ) { return json_encode( $v ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function wp_remote_post( $url, $args ) { $GLOBALS['requests'][] = array( $url, $args ); return $GLOBALS['response']; }
function wp_remote_retrieve_body( $r ) { return $r['body']; }
function wp_remote_retrieve_response_code( $r ) { return $r['status']; }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function add_action( ...$args ) {}
function esc_html( $s ) { return $s; }
function wp_die( $m ) { throw new RuntimeException( 'wp_die: ' . $m ); }

require __DIR__ . '/../inc/ihq-env.php';
require __DIR__ . '/../inc/ihq-url-minify.php';

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

const ORIGINAL = 'https://ihq.example.test/portal/portal-home/?verify_token=T&action=verify_email';
const API_BASE = 'https://api.example.test/qc';

function minify_with( $response ) {
	$GLOBALS['requests'] = array();
	$GLOBALS['response'] = $response;
	return ihq_minify_url_or_original( ORIGINAL, API_BASE );
}

// Happy path, and the exact request sent.
check(
	'shortUrl returned when shortCode present',
	minify_with( array( 'status' => 200, 'body' => '{"shortUrl":"https://s.example.test/r/abc","shortCode":"abc","qrCodeSvg":"<svg/>"}' ) ),
	'https://s.example.test/r/abc'
);
check(
	'request: POST /minify with originalUrl, short timeout, no redirects, TLS verified',
	$GLOBALS['requests'],
	array(
		array(
			'https://api.example.test/qc/minify',
			array(
				'headers'     => array( 'Content-Type' => 'application/json' ),
				'body'        => json_encode( array( 'originalUrl' => ORIGINAL ) ),
				'timeout'     => 5,
				'redirection' => 0,
				'sslverify'   => true,
			),
		),
	)
);
check( '201 counts as success', minify_with( array( 'status' => 201, 'body' => '{"shortUrl":"https://s.example.test/r/x","shortCode":"x"}' ) ), 'https://s.example.test/r/x' );
check( '299 counts as success', minify_with( array( 'status' => 299, 'body' => '{"shortUrl":"https://s.example.test/r/y","shortCode":"y"}' ) ), 'https://s.example.test/r/y' );

// Every failure returns the original link.
$cases = array(
	'WP_Error'                   => new WP_Error( 'http', 'timed out' ),
	'HTTP 199'                   => array( 'status' => 199, 'body' => '{"shortUrl":"https://s.example.test/r/a","shortCode":"a"}' ),
	'HTTP 300'                   => array( 'status' => 300, 'body' => '{"shortUrl":"https://s.example.test/r/a","shortCode":"a"}' ),
	'HTTP 400 (origin refused)'  => array( 'status' => 400, 'body' => '{"message":"bad origin"}' ),
	'HTTP 429 (throttled)'       => array( 'status' => 429, 'body' => '{}' ),
	'HTTP 500'                   => array( 'status' => 500, 'body' => '' ),
	'no shortCode (not shortened)' => array( 'status' => 200, 'body' => '{"shortUrl":"' . ORIGINAL . '","qrCodeSvg":"<svg/>"}' ),
	'empty shortCode'            => array( 'status' => 200, 'body' => '{"shortUrl":"https://s.example.test/r/a","shortCode":""}' ),
	'non-string shortCode'       => array( 'status' => 200, 'body' => '{"shortUrl":"https://s.example.test/r/a","shortCode":123}' ),
	'missing shortUrl'           => array( 'status' => 200, 'body' => '{"shortCode":"a"}' ),
	'http shortUrl'              => array( 'status' => 200, 'body' => '{"shortUrl":"http://s.example.test/r/a","shortCode":"a"}' ),
	'relative shortUrl'          => array( 'status' => 200, 'body' => '{"shortUrl":"/r/a","shortCode":"a"}' ),
	'non-string shortUrl'        => array( 'status' => 200, 'body' => '{"shortUrl":["x"],"shortCode":"a"}' ),
	'malformed JSON'             => array( 'status' => 200, 'body' => '{not json' ),
	'JSON scalar'                => array( 'status' => 200, 'body' => '"https://s.example.test/r/a"' ),
	'empty body'                 => array( 'status' => 200, 'body' => '' ),
);
foreach ( $cases as $label => $response ) {
	check( "fallback ($label): original link", minify_with( $response ), ORIGINAL );
	check( "fallback ($label): exactly one request", count( $GLOBALS['requests'] ), 1 );
}

echo $fail === 0 ? "ALL PASS\n" : "$fail FAILED\n";
exit( $fail === 0 ? 0 : 1 );

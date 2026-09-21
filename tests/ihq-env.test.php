<?php
/**
 * Unit test for inc/ihq-env.php. No WordPress bootstrap: the handful of WP
 * functions the module touches are stubbed below so the test runs anywhere
 * PHP does, e.g.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/ihq-env.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );
$GLOBALS['died'] = null; $GLOBALS['is_admin'] = false; $GLOBALS['doing_ajax'] = false; $GLOBALS['actions'] = array();
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function esc_html( $s ) { return $s; }
function is_admin() { return $GLOBALS['is_admin']; }
function wp_doing_ajax() { return $GLOBALS['doing_ajax']; }
function wp_parse_url( $u ) { return parse_url( $u ); }
function current_user_can( $c ) { return true; }
function add_action( $h, $f ) { $GLOBALS['actions'][ $h ] = $f; }
function wp_die( $m, $t = '', $a = array() ) { $GLOBALS['died'] = $m; throw new RuntimeException( 'wp_die' ); }

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }
function died_with( $needle ) { return is_string( $GLOBALS['died'] ) && strpos( $GLOBALS['died'], $needle ) !== false; }

// Case 1: nothing configured, front end -> module load must wp_die naming the FIRST missing key.
try { require __DIR__ . '/../inc/ihq-env.php'; check( 'front end dies at load when unconfigured', false ); }
catch ( RuntimeException $e ) { check( 'front end dies at load when unconfigured, names IHQ_ENVIRONMENT', died_with( 'IHQ_ENVIRONMENT' ) ); }
check( 'admin_notices hook registered before the die', isset( $GLOBALS['actions']['admin_notices'] ) );
check( 'INFLUENCER_API_BASE not defined after die', ! defined( 'INFLUENCER_API_BASE' ) );

// Case 2: resolution order — constant beats env, env beats default, empty string counts as unset.
putenv( 'IHQ_ENVIRONMENT=from-env' );
check( 'getenv fallback', ihq_env_get( 'IHQ_ENVIRONMENT' ) === 'from-env' );
define( 'IHQ_ENVIRONMENT', 'from-const' );
check( 'constant wins over env', ihq_env_get( 'IHQ_ENVIRONMENT' ) === 'from-const' );
define( 'CF_TURNSTILE_SITE_KEY', '' );
check( 'empty constant treated as unset -> default', ihq_env_get( 'CF_TURNSTILE_SITE_KEY', 'dflt' ) === 'dflt' );
check( 'unset optional -> null', ihq_env_get( 'IHQ_ELEVENLABS_API_KEY' ) === null );

// Case 3: admin page — no die, returns '' and the notice lists what is missing.
$GLOBALS['is_admin'] = true; $GLOBALS['died'] = null;
check( 'admin page: require returns empty instead of dying', ihq_env_require( 'IHQ_INFLUENCER_API_KEY' ) === '' && $GLOBALS['died'] === null );
$missing = ihq_env_missing_required_keys();
check( 'missing list = the three still unset', $missing === array( 'IHQ_API_BASE_URL', 'IHQ_GAME_PORTAL_BASE_URL', 'IHQ_INFLUENCER_API_KEY' ) );
ob_start(); ihq_env_admin_notice(); $notice = ob_get_clean();
check( 'admin notice names missing keys', strpos( $notice, 'IHQ_GAME_PORTAL_BASE_URL' ) !== false && strpos( $notice, 'notice-error' ) !== false );

// Case 4: admin-ajax is NOT an admin page — it must still die.
$GLOBALS['doing_ajax'] = true; $GLOBALS['died'] = null;
try { ihq_env_require( 'IHQ_INFLUENCER_API_KEY' ); check( 'admin-ajax dies', false ); }
catch ( RuntimeException $e ) { check( 'admin-ajax dies even though is_admin() is true', died_with( 'IHQ_INFLUENCER_API_KEY' ) ); }
$GLOBALS['doing_ajax'] = false;

// Case 5: https validation.
check( 'is_https_url: https ok', ihq_env_is_https_url( 'https://a.example.com/x' ) );
check( 'is_https_url: http rejected', ! ihq_env_is_https_url( 'http://a.example.com/x' ) );
check( 'is_https_url: no host rejected', ! ihq_env_is_https_url( 'https:///x' ) );
check( 'is_https_url: garbage rejected', ! ihq_env_is_https_url( 'not a url' ) && ! ihq_env_is_https_url( '' ) );
putenv( 'IHQ_API_BASE_URL=http://insecure.example.com/qc' );
$GLOBALS['died'] = null;
check( 'require_url refuses http (admin page: returns empty, no die)', ihq_env_require_url( 'IHQ_API_BASE_URL' ) === '' && $GLOBALS['died'] === null );
$GLOBALS['is_admin'] = false;
try { ihq_env_require_url( 'IHQ_API_BASE_URL' ); check( 'require_url refuses http on front end', false ); }
catch ( RuntimeException $e ) { check( 'require_url refuses http on front end, names the key', died_with( 'IHQ_API_BASE_URL' ) && died_with( 'https://' ) ); }
$GLOBALS['is_admin'] = true;
putenv( 'IHQ_API_BASE_URL' );

// Case 5b: load-time contract for the portal base — same validator the module calls at load.
putenv( 'IHQ_GAME_PORTAL_BASE_URL=http://portal.example.com/av-baccarat' );
$GLOBALS['is_admin'] = false; $GLOBALS['died'] = null;
try { ihq_env_require_url( 'IHQ_GAME_PORTAL_BASE_URL' ); check( 'http portal base refused', false ); }
catch ( RuntimeException $e ) { check( 'http portal base refused on front end, names the key', died_with( 'IHQ_GAME_PORTAL_BASE_URL' ) ); }
$GLOBALS['is_admin'] = true;
putenv( 'IHQ_GAME_PORTAL_BASE_URL' );
putenv( 'IHQ_GAME_PORTAL_BASE_URL=http://portal.example.com/av-baccarat' );
$problems = ihq_env_problems();
check( 'problems(): lists missing keys first, then the invalid URL', count( $problems ) === 3 && strpos( $problems[0], 'IHQ_API_BASE_URL' ) !== false && strpos( end( $problems ), 'IHQ_GAME_PORTAL_BASE_URL must be an absolute https://' ) !== false );
ob_start(); ihq_env_admin_notice(); $notice = ob_get_clean();
check( 'admin notice also names the invalid URL', strpos( $notice, 'IHQ_GAME_PORTAL_BASE_URL must be an absolute https://' ) !== false );
putenv( 'IHQ_GAME_PORTAL_BASE_URL' );
check( 'module load fails on problems(), not only missing keys', strpos( file_get_contents( __DIR__ . '/../inc/ihq-env.php' ), '$ihq_env_problems = ihq_env_problems();' ) !== false );

// Case 6: URL helper strips trailing slash.
define( 'IHQ_GAME_PORTAL_BASE_URL', 'https://play.bet5games.com/av-baccarat/' );
check( 'require_url strips trailing slash', ihq_env_require_url( 'IHQ_GAME_PORTAL_BASE_URL' ) === 'https://play.bet5games.com/av-baccarat' );

// Case 7: origin allow-list for per-user override URLs.
$base = 'https://02nvfvonol.execute-api.eu-west-2.amazonaws.com/qc';
check( 'origin match: same host, other path', ihq_env_url_matches_api_origin( $base . '/account/oauth/start-session', $base ) );
check( 'origin match: case-insensitive host', ihq_env_url_matches_api_origin( 'https://02NVFVONOL.execute-api.eu-west-2.amazonaws.com/x', $base ) );
check( 'origin mismatch: other host', ! ihq_env_url_matches_api_origin( 'https://attacker.example.com/account/oauth/start-session', $base ) );
check( 'origin mismatch: http scheme', ! ihq_env_url_matches_api_origin( 'http://02nvfvonol.execute-api.eu-west-2.amazonaws.com/qc', $base ) );
check( 'origin mismatch: explicit non-443 port', ! ihq_env_url_matches_api_origin( 'https://02nvfvonol.execute-api.eu-west-2.amazonaws.com:8443/qc', $base ) );
check( 'origin mismatch: empty', ! ihq_env_url_matches_api_origin( '', $base ) );

// Case 8: fully configured -> no notice.
define( 'IHQ_API_BASE_URL', 'https://x.execute-api.eu-west-2.amazonaws.com/qc' );
define( 'IHQ_INFLUENCER_API_KEY', 'k' );
ob_start(); ihq_env_admin_notice(); $notice = ob_get_clean();
check( 'configured: no admin notice', $notice === '' );
check( 'configured: no problems', ihq_env_problems() === array() );
check( 'ihq_env_name', ihq_env_name() === 'from-const' );

echo $fail ? "\n$fail FAILED\n" : "\nALL PASS\n"; exit( $fail ? 1 : 0 );

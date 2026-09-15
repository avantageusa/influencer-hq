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
$GLOBALS['died'] = null; $GLOBALS['is_admin'] = false; $GLOBALS['actions'] = array();
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function esc_html( $s ) { return $s; }
function is_admin() { return $GLOBALS['is_admin']; }
function current_user_can( $c ) { return true; }
function add_action( $h, $f ) { $GLOBALS['actions'][ $h ] = $f; }
function wp_die( $m, $t = '', $a = array() ) { $GLOBALS['died'] = $m; throw new RuntimeException( 'wp_die' ); }

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

// Case 1: nothing configured, front end -> INFLUENCER_API_BASE define must wp_die naming the key.
try { require __DIR__ . '/../inc/ihq-env.php'; check( 'front end dies when unconfigured', false ); }
catch ( RuntimeException $e ) { check( 'front end dies when unconfigured', strpos( $GLOBALS['died'], 'IHQ_API_BASE_URL' ) !== false ); }
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

// Case 3: admin path — no die, returns '' and the notice lists what is missing.
$GLOBALS['is_admin'] = true; $GLOBALS['died'] = null;
check( 'admin: require returns empty instead of dying', ihq_env_require( 'IHQ_INFLUENCER_API_KEY' ) === '' && $GLOBALS['died'] === null );
$missing = ihq_env_missing_required_keys();
check( 'missing list = the three still unset', $missing === array( 'IHQ_API_BASE_URL', 'IHQ_GAME_PORTAL_BASE_URL', 'IHQ_INFLUENCER_API_KEY' ) );
ob_start(); ihq_env_admin_notice(); $notice = ob_get_clean();
check( 'admin notice names missing keys', strpos( $notice, 'IHQ_GAME_PORTAL_BASE_URL' ) !== false && strpos( $notice, 'notice-error' ) !== false );

// Case 4: URL helper strips trailing slash.
define( 'IHQ_GAME_PORTAL_BASE_URL', 'https://play.bet5games.com/av-baccarat/' );
check( 'require_url strips trailing slash', ihq_env_require_url( 'IHQ_GAME_PORTAL_BASE_URL' ) === 'https://play.bet5games.com/av-baccarat' );

// Case 5: fully configured -> no notice.
define( 'IHQ_API_BASE_URL', 'https://x.execute-api.eu-west-2.amazonaws.com/qc' );
define( 'IHQ_INFLUENCER_API_KEY', 'k' );
ob_start(); ihq_env_admin_notice(); $notice = ob_get_clean();
check( 'configured: no admin notice', $notice === '' );
check( 'ihq_env_name', ihq_env_name() === 'from-const' );

echo $fail ? "\n$fail FAILED\n" : "\nALL PASS\n"; exit( $fail ? 1 : 0 );

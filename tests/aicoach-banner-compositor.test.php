<?php
/**
 * Unit test for inc/aicoach-banner-compositor.php (ENGR-7848). No WordPress bootstrap:
 * get_option() is stubbed over an in-memory table and the theme is a throwaway directory.
 *
 *     php tests/aicoach-banner-compositor.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );

$fail = 0;
// A PHP warning or notice anywhere in the module is a failure: it would reach the production log.
$GLOBALS['php_warnings'] = array();
set_error_handler(
	function ( $number, $message ) {
		$GLOBALS['php_warnings'][] = $message;
		return true;
	}
);
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

$GLOBALS['wp_options']     = array();
$GLOBALS['active_version'] = '';
function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['wp_options'] ) ? $GLOBALS['wp_options'][ $key ] : $default;
}
function get_template_directory() { return $GLOBALS['theme_dir']; }
function get_template_directory_uri() { return 'https://example.test/wp-content/themes/influencer-hq'; }

// The two helpers of inc/aicoach-prerender.php the module reads by name.
function ihq_aicoach_avatar_active_version() { return $GLOBALS['active_version']; }
function ihq_aicoach_avatar_status() {
	return array(
		array( 'version' => '', 'clips' => 63, 'missing' => 0, 'active' => '' === $GLOBALS['active_version'] ),
		array( 'version' => 'v3', 'clips' => 1, 'missing' => 62, 'active' => 'v3' === $GLOBALS['active_version'] ),
	);
}
function ihq_aicoach_avatar_version_video_profile( $version ) {
	$profiles = array( 'v3' => 'sami-framing-20261018', 'v9' => 'some-other-profile' );
	return $profiles[ $version ] ?? '';
}

require __DIR__ . '/../inc/aicoach-banner-compositor.php';

// A throwaway theme with the three hosted files and known modification times.
$theme = sys_get_temp_dir() . '/ihq-banner-compositor-test-' . getmypid();
mkdir( $theme . '/js/vendor/sami-banner', 0777, true );
$GLOBALS['theme_dir'] = $theme;
$mtimes = array(
	'js/vendor/sami-banner/sami-banner-compositor.js' => 1700000101,
	'js/vendor/sami-banner/calibration.json'          => 1700000102,
	'js/vendor/sami-banner/poster.webp'               => 1700000103,
);
function put_files( $theme, $mtimes ) {
	foreach ( $mtimes as $relative_path => $mtime ) {
		file_put_contents( $theme . '/' . $relative_path, 'x' );
		touch( $theme . '/' . $relative_path, $mtime );
	}
}
put_files( $theme, $mtimes );
register_shutdown_function(
	function () use ( $theme, $mtimes ) {
		foreach ( array_keys( $mtimes ) as $relative_path ) {
			@unlink( $theme . '/' . $relative_path );
		}
		@rmdir( $theme . '/js/vendor/sami-banner' );
		@rmdir( $theme . '/js/vendor' );
		@rmdir( $theme . '/js' );
		@rmdir( $theme );
	}
);

function reset_state() {
	$GLOBALS['wp_options']     = array();
	$GLOBALS['active_version'] = '';
}
function switched_on_with_v3() {
	reset_state();
	$GLOBALS['wp_options'][ IHQ_AICOACH_BANNER_COMPOSITOR_OPTION ] = '1';
	$GLOBALS['active_version']                                    = 'v3';
}

// The profile the module requires is the one the prerender maps v3 to.
$prerender_source = file_get_contents( __DIR__ . '/../inc/aicoach-prerender.php' );
check(
	'the required profile is the one inc/aicoach-prerender.php gives v3',
	1 === preg_match( "/'v3'\s*=>\s*'" . preg_quote( IHQ_AICOACH_BANNER_VIDEO_PROFILE, '/' ) . "'/", $prerender_source )
);
check( 'the avatar id is the candidate Gary calibrated for', 'cf6b6e7d-be88-4b9b-a31d-5f065b90cd9a' === IHQ_AICOACH_BANNER_AVATAR_ID );
$calibration = json_decode( file_get_contents( __DIR__ . '/../js/vendor/sami-banner/calibration.json' ), true );
check( 'the hosted calibration is for that avatar', IHQ_AICOACH_BANNER_AVATAR_ID === $calibration['avatar_id'] );
foreach ( IHQ_AICOACH_BANNER_ASSET_FILES as $name => $relative_path ) {
	check( "the real $name file is in the theme", file_exists( __DIR__ . '/../' . $relative_path ) );
}
check( 'functions.php loads the module after the prerender', 1 === preg_match( "#aicoach-prerender\.php';.*aicoach-banner-compositor\.php';#s", file_get_contents( __DIR__ . '/../functions.php' ) ) );
check( 'the flow is given the config as bannerCompositor', false !== strpos( file_get_contents( __DIR__ . '/../inc/anam-proxy.php' ), "'bannerCompositor' => function_exists( 'ihq_aicoach_banner_compositor_config' ) ? ihq_aicoach_banner_compositor_config() : false" ) );

// The switch: only an explicit "on" counts.
$on_values  = array( '1', 'true', 'yes', 'on', 'ON', ' Yes ', 1, true );
$off_values = array( '', '0', 'off', 'false', 'no', 'maybe', 'enabled', 2, 0, false, null, array( '1' ), 1.5 );
foreach ( $on_values as $value ) {
	reset_state();
	$GLOBALS['wp_options'][ IHQ_AICOACH_BANNER_COMPOSITOR_OPTION ] = $value;
	check( 'switch on for ' . var_export( $value, true ), true === ihq_aicoach_banner_compositor_switch_on() );
}
foreach ( $off_values as $value ) {
	reset_state();
	$GLOBALS['wp_options'][ IHQ_AICOACH_BANNER_COMPOSITOR_OPTION ] = $value;
	check( 'switch off for ' . str_replace( "\n", '', var_export( $value, true ) ), false === ihq_aicoach_banner_compositor_switch_on() );
}
reset_state();
check( 'switch off when the option does not exist', false === ihq_aicoach_banner_compositor_switch_on() );

// Enabled needs the switch, the version's profile and the files.
switched_on_with_v3();
check( 'enabled: switch on, v3 served, files present', true === ihq_aicoach_banner_compositor_enabled() );

reset_state();
$GLOBALS['active_version'] = 'v3';
check( 'not enabled with the switch off even though v3 is served', false === ihq_aicoach_banner_compositor_enabled() );

foreach ( array( '', 'v1', 'v2', 'v4', 'v9' ) as $version ) {
	switched_on_with_v3();
	$GLOBALS['active_version'] = $version;
	check( 'not enabled when the served version is ' . var_export( $version, true ), false === ihq_aicoach_banner_compositor_enabled() );
}

foreach ( array_keys( $mtimes ) as $relative_path ) {
	switched_on_with_v3();
	rename( $theme . '/' . $relative_path, $theme . '/' . $relative_path . '.away' );
	check( 'not enabled without ' . basename( $relative_path ), false === ihq_aicoach_banner_compositor_enabled() && false === ihq_aicoach_banner_assets_present() );
	check( 'no config without ' . basename( $relative_path ), false === ihq_aicoach_banner_compositor_config() );
	rename( $theme . '/' . $relative_path . '.away', $theme . '/' . $relative_path );
	touch( $theme . '/' . $relative_path, $mtimes[ $relative_path ] );
}
check( 'assets present again after the files are back', true === ihq_aicoach_banner_assets_present() );

// The live-session profile follows enabled().
switched_on_with_v3();
check( 'video profile while enabled', 'sami-framing-20261018' === ihq_aicoach_banner_compositor_video_profile() );
$GLOBALS['wp_options'][ IHQ_AICOACH_BANNER_COMPOSITOR_OPTION ] = '0';
check( 'no video profile while the switch is off', '' === ihq_aicoach_banner_compositor_video_profile() );
switched_on_with_v3();
$GLOBALS['active_version'] = 'v2';
check( 'no video profile for a version without one', '' === ihq_aicoach_banner_compositor_video_profile() );

// The config the flow gets.
$base       = 'https://example.test/wp-content/themes/influencer-hq/js/vendor/sami-banner/';
$config_now = array(
	'moduleUrl'           => $base . 'sami-banner-compositor.js?ver=1700000101',
	'calibrationUrl'      => $base . 'calibration.json?ver=1700000102',
	'posterUrl'           => $base . 'poster.webp?ver=1700000103',
	'avatarId'            => 'cf6b6e7d-be88-4b9b-a31d-5f065b90cd9a',
	'showDisclosure'      => true,
	'preserveVideoCorner' => true,
);
switched_on_with_v3();
check( 'config: versioned file URLs, the avatar id and both flags on by default', $config_now === ihq_aicoach_banner_compositor_config() );

foreach ( array( '0', 'false', 'no', 'off', ' OFF ', 0, false ) as $value ) {
	switched_on_with_v3();
	$GLOBALS['wp_options'][ IHQ_AICOACH_BANNER_DISCLOSURE_OPTION ] = $value;
	$expected                                                      = $config_now;
	$expected['showDisclosure']                                    = false;
	check( 'config: the box is off for ' . var_export( $value, true ) . ' and the corner stays on', $expected === ihq_aicoach_banner_compositor_config() );

	switched_on_with_v3();
	$GLOBALS['wp_options'][ IHQ_AICOACH_BANNER_VIDEO_CORNER_OPTION ] = $value;
	$expected                                                        = $config_now;
	$expected['preserveVideoCorner']                                 = false;
	check( 'config: the corner is off for ' . var_export( $value, true ) . ' and the box stays on', $expected === ihq_aicoach_banner_compositor_config() );
}
foreach ( array( '1', 'true', 'yes', 'on', '', 'maybe', 5, array( 'off' ), null ) as $value ) {
	switched_on_with_v3();
	$GLOBALS['wp_options'][ IHQ_AICOACH_BANNER_DISCLOSURE_OPTION ]   = $value;
	$GLOBALS['wp_options'][ IHQ_AICOACH_BANNER_VIDEO_CORNER_OPTION ] = $value;
	check( 'config: both flags stay on for ' . str_replace( "\n", '', var_export( $value, true ) ), $config_now === ihq_aicoach_banner_compositor_config() );
}

// The status the settings screen shows.
$all_on = array(
	'switch_on'             => true,
	'served_version'        => 'v3',
	'served_profile'        => 'sami-framing-20261018',
	'profile_matches'       => true,
	'assets_present'        => true,
	'enabled'               => true,
	'show_disclosure'       => true,
	'preserve_video_corner' => true,
	'versions'              => array(
		array( 'version' => '', 'clips' => 63, 'missing' => 0, 'active' => false ),
		array( 'version' => 'v3', 'clips' => 1, 'missing' => 62, 'active' => true ),
	),
);
switched_on_with_v3();
check( 'status: switch on, v3 served, everything present', $all_on === ihq_aicoach_banner_compositor_status() );

reset_state();
$expected                   = $all_on;
$expected['switch_on']      = false;
$expected['enabled']        = false;
$expected['served_version'] = '';
$expected['served_profile'] = '';
$expected['profile_matches'] = false;
$expected['versions'][0]['active'] = true;
$expected['versions'][1]['active'] = false;
check( 'status: nothing set, the original set served', $expected === ihq_aicoach_banner_compositor_status() );

switched_on_with_v3();
$GLOBALS['active_version'] = 'v9';
$status                    = ihq_aicoach_banner_compositor_status();
check( 'status: another version\'s profile is shown but does not match', 'some-other-profile' === $status['served_profile'] && false === $status['profile_matches'] && false === $status['enabled'] );

switched_on_with_v3();
$GLOBALS['wp_options'][ IHQ_AICOACH_BANNER_DISCLOSURE_OPTION ]   = '0';
$GLOBALS['wp_options'][ IHQ_AICOACH_BANNER_VIDEO_CORNER_OPTION ] = 'off';
$status = ihq_aicoach_banner_compositor_status();
check( 'status: the two flags follow their options', false === $status['show_disclosure'] && false === $status['preserve_video_corner'] );

switched_on_with_v3();
rename( $theme . '/js/vendor/sami-banner/poster.webp', $theme . '/js/vendor/sami-banner/poster.webp.away' );
$status = ihq_aicoach_banner_compositor_status();
check( 'status: a missing file shows as missing, the profile still matches', false === $status['assets_present'] && true === $status['profile_matches'] && false === $status['enabled'] );
check( 'blocker: names the missing files', 'The adapter files are missing from the theme (js/vendor/sami-banner/).' === ihq_aicoach_banner_compositor_blocker( $status ) );
rename( $theme . '/js/vendor/sami-banner/poster.webp.away', $theme . '/js/vendor/sami-banner/poster.webp' );
touch( $theme . '/js/vendor/sami-banner/poster.webp', 1700000103 );

check( 'blocker: nothing to report when composed', '' === ihq_aicoach_banner_compositor_blocker( $all_on ) );
$switch_off = $all_on;
$switch_off['switch_on'] = false;
$switch_off['enabled']   = false;
check( 'blocker: nothing to report while the switch is off', '' === ihq_aicoach_banner_compositor_blocker( $switch_off ) );
$wrong_version                    = $all_on;
$wrong_version['enabled']         = false;
$wrong_version['served_version']  = 'v2';
$wrong_version['served_profile']  = '';
$wrong_version['profile_matches'] = false;
check( 'blocker: names the served version and the way to serve v3', 'The avatar version served now is v2, which does not have the sami-framing-20261018 video profile. Render the version that does (v3) and serve it with `wp aicoach activate-avatar v3`.' === ihq_aicoach_banner_compositor_blocker( $wrong_version ) );
$wrong_version['served_version'] = '';
check( 'blocker: calls the original set by name', 'The avatar version served now is the original set, which does not have the sami-framing-20261018 video profile. Render the version that does (v3) and serve it with `wp aicoach activate-avatar v3`.' === ihq_aicoach_banner_compositor_blocker( $wrong_version ) );

// A file replaced after a deploy gets a new URL.
switched_on_with_v3();
touch( $theme . '/js/vendor/sami-banner/poster.webp', 1700009999 );
$config_replaced = ihq_aicoach_banner_compositor_config();
check( 'a replaced poster gets a new version in its URL', $base . 'poster.webp?ver=1700009999' === $config_replaced['posterUrl'] );
touch( $theme . '/js/vendor/sami-banner/poster.webp', 1700000103 );

// Without the file the URL has no version (the module is not enabled in that case).
check( 'asset URL without a version for a file that is not there', 'https://example.test/wp-content/themes/influencer-hq/js/vendor/sami-banner/none.js' === ihq_aicoach_banner_asset_url( 'js/vendor/sami-banner/none.js' ) );

check( 'the module raised no PHP warning or notice', array() === $GLOBALS['php_warnings'] );

echo $fail ? "\n$fail FAILED\n" : "\nALL PASS\n";
exit( $fail ? 1 : 0 );

<?php
/**
 * Unit test for inc/aicoach-banner-compositor-admin.php (ENGR-7848), the Settings screen of the
 * composed banner. No WordPress bootstrap: the WordPress functions the screen calls are stubbed
 * to record what they are asked.
 *
 *     php tests/aicoach-banner-compositor-admin.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

$GLOBALS['actions']      = array();
$GLOBALS['settings']     = array();
$GLOBALS['menu_pages']   = array();
$GLOBALS['settings_field_groups'] = array();
$GLOBALS['submit_buttons'] = 0;
$GLOBALS['can']          = true;
$GLOBALS['status']       = array();
$GLOBALS['blocker']      = '';

function add_action( $hook, $callback ) { $GLOBALS['actions'][] = array( $hook, $callback ); }
function register_setting( $group, $option, $args ) { $GLOBALS['settings'][] = array( $group, $option, $args ); }
function add_options_page( $title, $menu_title, $capability, $slug, $callback ) { $GLOBALS['menu_pages'][] = array( $title, $menu_title, $capability, $slug, $callback ); }
function settings_fields( $group ) { $GLOBALS['settings_field_groups'][] = $group; echo '<input type="hidden" name="option_page" value="' . $group . '">'; }
function submit_button() { ++$GLOBALS['submit_buttons']; echo '<button>Save</button>'; }
function checked( $value ) { if ( $value ) { echo 'checked="checked"'; } }
function current_user_can( $capability ) { $GLOBALS['can_asked'] = $capability; return $GLOBALS['can']; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); }

// The module the screen reads, with its status faked so the screen is tested on its own.
const IHQ_AICOACH_BANNER_COMPOSITOR_OPTION   = 'ihq_aicoach_banner_compositor';
const IHQ_AICOACH_BANNER_DISCLOSURE_OPTION   = 'ihq_aicoach_banner_disclosure';
const IHQ_AICOACH_BANNER_VIDEO_CORNER_OPTION = 'ihq_aicoach_banner_video_corner';
function ihq_aicoach_banner_compositor_status() { return $GLOBALS['status']; }
function ihq_aicoach_banner_compositor_blocker( $status ) { $GLOBALS['blocker_asked_with'] = $status; return $GLOBALS['blocker']; }

require __DIR__ . '/../inc/aicoach-banner-compositor-admin.php';

function status( $changes = array() ) {
	return array_merge(
		array(
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
		),
		$changes
	);
}
function render() {
	ob_start();
	ihq_aicoach_banner_admin_render();
	return preg_replace( '#>\s+<#', '><', ob_get_clean() );
}

// What is hooked.
check( 'the screen registers its settings on admin_init and its menu on admin_menu', array( array( 'admin_init', 'ihq_aicoach_banner_admin_register_settings' ), array( 'admin_menu', 'ihq_aicoach_banner_admin_register_menu' ) ) === $GLOBALS['actions'] );
check( 'functions.php loads the screen only in the admin', 1 === preg_match( "#if \( is_admin\(\) \) \{\s*require_once get_template_directory\(\) \. '/inc/aicoach-banner-compositor-admin\.php';\s*\}#", file_get_contents( __DIR__ . '/../functions.php' ) ) );

ihq_aicoach_banner_admin_register_settings();
$expected_settings = array();
foreach ( array( 'ihq_aicoach_banner_compositor', 'ihq_aicoach_banner_disclosure', 'ihq_aicoach_banner_video_corner' ) as $option ) {
	$expected_settings[] = array( 'ihq-aicoach-banner', $option, array( 'type' => 'string', 'sanitize_callback' => 'ihq_aicoach_banner_admin_sanitize_flag' ) );
}
check( 'the three options are registered in one group with the flag cleaner', $expected_settings === $GLOBALS['settings'] );

ihq_aicoach_banner_admin_register_menu();
check( 'the menu entry is under Settings, for administrators, drawn by the render function', array( array( 'AI Coach banner', 'AI Coach', 'manage_options', 'ihq-aicoach-banner', 'ihq_aicoach_banner_admin_render' ) ) === $GLOBALS['menu_pages'] );

// The flag cleaner: only a posted "1" is on.
check( 'a ticked box ("1") is stored as 1', '1' === ihq_aicoach_banner_admin_sanitize_flag( '1' ) );
foreach ( array( null, '', '0', 'on', 'yes', 'true', 1, true, array( '1' ), ' 1' ) as $value ) {
	check( 'anything else is stored as 0: ' . str_replace( "\n", '', var_export( $value, true ) ), '0' === ihq_aicoach_banner_admin_sanitize_flag( $value ) );
}

// The screen.
$GLOBALS['status'] = status();
$html              = render();
check( 'asks for the manage_options capability', 'manage_options' === $GLOBALS['can_asked'] );
check( 'shows the state of the page', false !== strpos( $html, '<li>Switch: on</li><li>Avatar version served: v3</li><li>Its video profile: sami-framing-20261018</li><li>Adapter files in the theme: present</li><li>Banner composed on the page now: yes</li>' ) );
check( 'lists the clips of each version, the original set first, and marks the served one', false !== strpos( $html, '<tr><td>original set</td><td>63</td><td>0</td><td></td></tr><tr><td>v3</td><td>1</td><td>62</td><td>served now</td></tr>' ) );
check( 'shows no warning when the banner is composed', false === strpos( $html, 'notice-warning' ) );
check( 'the form posts to options.php with the settings group and a save button', false !== strpos( $html, '<form method="post" action="options.php">' ) && array( 'ihq-aicoach-banner' ) === $GLOBALS['settings_field_groups'] && 1 === $GLOBALS['submit_buttons'] );
check( 'the three checkboxes carry the three options, all ticked', 3 === substr_count( $html, 'value="1" checked="checked"' ) && false !== strpos( $html, 'name="ihq_aicoach_banner_compositor"' ) && false !== strpos( $html, 'name="ihq_aicoach_banner_disclosure"' ) && false !== strpos( $html, 'name="ihq_aicoach_banner_video_corner"' ) );
check( 'the blocker is asked about the same status', $GLOBALS['status'] === $GLOBALS['blocker_asked_with'] );

$GLOBALS['status'] = status( array( 'switch_on' => false, 'show_disclosure' => false ) );
$html              = render();
check( 'an unticked box is not checked, the ticked one is', 1 === substr_count( $html, 'value="1" checked="checked"' ) && 1 === preg_match( '/name="ihq_aicoach_banner_video_corner" value="1" checked="checked"/', $html ) && 1 === preg_match( '/name="ihq_aicoach_banner_disclosure" value="1" >/', $html ) && 1 === preg_match( '/name="ihq_aicoach_banner_compositor" value="1" >/', $html ) );
check( 'the state shows the switch off', false !== strpos( $html, '<li>Switch: off</li>' ) );

$GLOBALS['status']  = status( array( 'enabled' => false, 'served_version' => '', 'served_profile' => '', 'assets_present' => false, 'versions' => array() ) );
$GLOBALS['blocker'] = 'Not <b>composed</b> & why';
$html               = render();
check( 'a blocker is shown as an escaped warning', false !== strpos( $html, '<div class="notice notice-warning inline"><p>Not &lt;b&gt;composed&lt;/b&gt; &amp; why</p></div>' ) );
check( 'the original set and "none" and "missing" are spelled out', false !== strpos( $html, 'Avatar version served: the original set' ) && false !== strpos( $html, 'Its video profile: none' ) && false !== strpos( $html, 'Adapter files in the theme: missing' ) && false !== strpos( $html, 'Banner composed on the page now: no' ) );
check( 'no versions gives an empty table body', false !== strpos( $html, '<tbody>' ) && 1 === preg_match( '#<tbody>\s*</tbody>#', $html ) );

$GLOBALS['status']  = status( array( 'versions' => array( array( 'version' => 'v<1>', 'clips' => '4', 'missing' => '2', 'active' => false ) ) ) );
$GLOBALS['blocker'] = '';
$html               = render();
check( 'a version name is escaped and counts are integers', false !== strpos( $html, '<tr><td>v&lt;1&gt;</td><td>4</td><td>2</td><td></td></tr>' ) );

$GLOBALS['can']                  = false;
$GLOBALS['settings_field_groups'] = array();
check( 'a user without the capability gets nothing', '' === render() && array() === $GLOBALS['settings_field_groups'] );

echo $fail ? "\n$fail FAILED\n" : "\nALL PASS\n";
exit( $fail ? 1 : 0 );

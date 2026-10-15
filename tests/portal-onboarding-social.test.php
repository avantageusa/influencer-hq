<?php
/**
 * Unit test for onboarding screen 6's server side (ENGR-7569,
 * inc/portal-onboarding-social.php): handles copied to the Profile page's
 * `_ihq_social_handles`, the Profile platform list, and the keys Profile keeps.
 * No WordPress bootstrap: user meta and actions are stubbed in memory, and the
 * store (inc/portal-onboarding-progress.php) runs for real to fire its action.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/portal-onboarding-social.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

$GLOBALS['user_meta'] = array();
$GLOBALS['actions']   = array();

function add_action( $hook, $cb, $priority = 10, $args = 1 ) { $GLOBALS['actions'][ $hook ][] = array( $cb, $args ); }
function do_action( $hook, ...$args ) {
	foreach ( $GLOBALS['actions'][ $hook ] ?? array() as $entry ) {
		call_user_func_array( $entry[0], array_slice( $args, 0, $entry[1] ) );
	}
}
function get_user_meta( $user_id, $key, $single = false ) { return $GLOBALS['user_meta'][ $user_id ][ $key ] ?? ''; }
function update_user_meta( $user_id, $key, $value ) { $GLOBALS['user_meta'][ $user_id ][ $key ] = $value; return true; }
function sanitize_text_field( $s ) { return trim( (string) $s ); }

require __DIR__ . '/../inc/portal-onboarding-progress.php';
require __DIR__ . '/../inc/portal-onboarding-social.php';

function social( $user_id = 7 ) { return $GLOBALS['user_meta'][ $user_id ]['_ihq_social_handles'] ?? null; }
function save( $partial, $user_id = 7 ) {
	$result = ihq_portal_onboarding_validate_partial( $partial );
	if ( '' !== $result['error'] ) {
		throw new Exception( $result['error'] );
	}
	return ihq_portal_onboarding_merge( $user_id, $result['partial'] );
}

// --- The 19 ---

check( 'the 19 platforms with their names, in screen order, the store\'s keys', array_keys( ihq_portal_onboarding_platform_labels() ) === ihq_portal_onboarding_platforms() );
check( 'platform names', array( 'Instagram', 'Facebook', 'TikTok', 'YouTube', 'Bilibili', 'Douyin', 'KakaoTalk', 'LINE', 'LinkedIn', 'Pinterest', 'QQ', 'Reddit', 'RedNote', 'Snapchat', 'Telegram', 'WeChat', 'Weibo', 'WhatsApp', 'X' ) === array_values( ihq_portal_onboarding_platform_labels() ) );
check( 'the mirror runs on the store\'s saved action with all three arguments', array( array( 'ihq_portal_onboarding_mirror_handles', 3 ) ) === $GLOBALS['actions']['ihq_portal_onboarding_saved'] );

// --- Saving handles copies them to _ihq_social_handles ---

save( array( 'platforms' => array( 'x', 'youtube' ), 'handles' => array( 'x' => '@me', 'youtube' => 'youtube.com/@me' ) ) );
check( 'saved handles appear in _ihq_social_handles', array( 'x' => '@me', 'youtube' => 'youtube.com/@me' ) === social() );
check( 'the onboarding remembers which keys it wrote', array( 'x', 'youtube' ) === $GLOBALS['user_meta'][7]['_ihq_portal_onboarding_mirrored_handles'] );

$GLOBALS['user_meta'] = array( 7 => array( '_ihq_social_handles' => array( 'whatsapp' => '+381600000', 'x' => '@old', 'kick' => 'kicker' ) ) );
save( array( 'platforms' => array( 'x' ), 'handles' => array( 'x' => '@new' ) ) );
check( 'an onboarding handle replaces that platform\'s saved one and keeps every other saved handle', array( 'whatsapp' => '+381600000', 'x' => '@new', 'kick' => 'kicker' ) === social() );

save( array( 'platforms' => array() ) );
check( 'unticking takes back only what the onboarding wrote', array( 'whatsapp' => '+381600000', 'kick' => 'kicker' ) === social() );
check( 'and forgets it wrote it', array() === $GLOBALS['user_meta'][7]['_ihq_portal_onboarding_mirrored_handles'] );

$GLOBALS['user_meta'] = array( 7 => array( '_ihq_social_handles' => array( 'whatsapp' => '+381600000' ) ) );
save( array( 'platforms' => array( 'whatsapp' ) ) );
check( 'ticking a platform without a handle leaves a registration handle alone', array( 'whatsapp' => '+381600000' ) === social() );
save( array( 'platforms' => array() ) );
check( 'unticking a platform the onboarding never wrote leaves its handle alone', array( 'whatsapp' => '+381600000' ) === social() );

$GLOBALS['user_meta'] = array( 7 => array( '_ihq_social_handles' => 'corrupt', '_ihq_portal_onboarding_mirrored_handles' => 'corrupt' ) );
save( array( 'platforms' => array( 'qq' ), 'handles' => array( 'qq' => '12345' ) ) );
check( 'unreadable meta is treated as empty', array( 'qq' => '12345' ) === social() );

$GLOBALS['user_meta'] = array();
save( array( 'stage' => 'classic', 'answers' => array( 'baccarat_experience' => 'played_some' ) ) );
check( 'a save without platforms or handles does not touch _ihq_social_handles', null === social() );
ihq_portal_onboarding_mirror_handles( 7, 'not an array', array() );
check( 'a malformed call does nothing', null === social() );

save( array( 'platforms' => array( 'line' ), 'handles' => array( 'line' => 'a' ) ), 8 );
check( 'handles go to the saving user only', null === social( 7 ) && array( 'line' => 'a' ) === social( 8 ) );

// --- The Profile platform list ---

$names = function ( $rows ) { return array_map( function ( $row ) { return $row['label']; }, $rows ); };
$keys  = function ( $rows ) { return array_map( function ( $row ) { return $row['key']; }, $rows ); };

check( 'Profile lists the 19 when nothing else is saved', ihq_portal_onboarding_platforms() === $keys( ihq_profile_social_platform_rows( array() ) ) );
check( 'Profile lists the 19 for unreadable meta', ihq_portal_onboarding_platforms() === $keys( ihq_profile_social_platform_rows( '' ) ) );
check( 'every row has a key and a label', array( 'key' => 'instagram', 'label' => 'Instagram' ) === ihq_profile_social_platform_rows( array() )[0] );

$rows = ihq_profile_social_platform_rows( array( 'twitch' => 'tw', 'x' => '@x', 'email' => 'a@b.c', 'kick' => '', 'naver-blog' => 'nb', 7 => 'num', 'odd' => array( 'x' ) ) );
check( 'Profile keeps saved platforms outside the 19 after them, in saved order, with their names', array_merge( ihq_portal_onboarding_platforms(), array( 'twitch', 'email', 'naver-blog' ) ) === $keys( $rows ) );
check( 'legacy names and a readable name for anything else', array( 'Twitch', 'Email', 'Naver Blog' ) === array_slice( $names( $rows ), 19 ) );
check( 'an empty saved value outside the 19 is not shown', ! in_array( 'kick', $keys( $rows ), true ) );
check( 'a platform among the 19 is not listed twice', 1 === count( array_keys( $keys( $rows ), 'x', true ) ) );

// --- Profile keeps the 19 when it saves ---

$ajax = file_get_contents( __DIR__ . '/../inc/api-ajax-calls.php' );
$fn   = substr( $ajax, strpos( $ajax, 'function ihq_allowed_contact_keys()' ) );
$fn   = substr( $fn, 0, strpos( $fn, '}' ) );
preg_match_all( "/'([a-z0-9_-]+)'/", $fn, $allowed );
foreach ( ihq_portal_onboarding_platforms() as $platform ) {
	check( 'Profile saves keep ' . $platform, in_array( $platform, $allowed[1], true ) );
}
foreach ( array( 'email', 'kick', 'twitch' ) as $legacy ) {
	check( 'Profile saves still keep ' . $legacy, in_array( $legacy, $allowed[1], true ) );
}

$profile = file_get_contents( __DIR__ . '/../page-portal-profile.php' );
check( 'the Profile page builds its list from the helper', false !== strpos( $profile, '$contact_platforms = ihq_profile_social_platform_rows( $social_handles );' ) );

echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );

<?php
/**
 * Unit test for onboarding screen 6's server side (ENGR-7569,
 * inc/portal-onboarding-social.php): handles copied to the Profile page's
 * `_ihq_social_handles` without overwriting Profile edits, the Profile platform
 * list, and the Profile save (inc/api-ajax-calls.php) accepting exactly the keys
 * that list can show.
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
function update_user_meta( $user_id, $key, $value ) { $GLOBALS['user_meta'][ $user_id ][ $key ] = $value; $GLOBALS['meta_writes'] = ( $GLOBALS['meta_writes'] ?? 0 ) + 1; return true; }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function sanitize_textarea_field( $s ) { return trim( (string) $s ); }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) ); }
function wp_unslash( $v ) { return $v; }
function check_ajax_referer( ...$args ) { return true; }
function get_current_user_id() { return 7; }

/** wp_send_json_* end the request; the stub records the response and stops the handler. */
class StopRequest extends Exception {}
function wp_send_json_success( $data = null ) { $GLOBALS['json'] = array( 'success' => true, 'status' => null ); throw new StopRequest(); }
function wp_send_json_error( $data = null, $status = null ) { $GLOBALS['json'] = array( 'success' => false, 'status' => $status, 'data' => $data ); throw new StopRequest(); }

require __DIR__ . '/../inc/portal-onboarding-progress.php';
require __DIR__ . '/../inc/portal-onboarding-social.php';
require __DIR__ . '/../inc/api-ajax-calls.php';

function social( $user_id = 7 ) { return $GLOBALS['user_meta'][ $user_id ]['_ihq_social_handles'] ?? null; }
function save( $partial, $user_id = 7 ) {
	$result = ihq_portal_onboarding_validate_partial( $partial );
	if ( '' !== $result['error'] ) {
		throw new Exception( $result['error'] );
	}
	return ihq_portal_onboarding_merge( $user_id, $result['partial'] );
}
function mirrored( $user_id = 7 ) { return $GLOBALS['user_meta'][ $user_id ]['_ihq_portal_onboarding_mirrored_handles'] ?? null; }
/** A Profile edit of one social row, as page-portal-profile.php sends it. */
function profile_save( $field, $value ) {
	$_POST = array( 'group' => 'social', 'field' => $field, 'value' => $value, 'nonce' => 'n' );
	$GLOBALS['json'] = null;
	try {
		save_settings_field_ajax();
	} catch ( StopRequest $stop ) {
		return $GLOBALS['json'];
	}
	return null;
}

// --- The 19 ---

check( 'the 19 platforms with their names, in screen order, the store\'s keys', array_keys( ihq_portal_onboarding_platform_labels() ) === ihq_portal_onboarding_platforms() );
check( 'platform names', array( 'Instagram', 'Facebook', 'TikTok', 'YouTube', 'Bilibili', 'Douyin', 'KakaoTalk', 'LINE', 'LinkedIn', 'Pinterest', 'QQ', 'Reddit', 'RedNote', 'Snapchat', 'Telegram', 'WeChat', 'Weibo', 'WhatsApp', 'X' ) === array_values( ihq_portal_onboarding_platform_labels() ) );
check( 'the mirror runs on the store\'s saved action with all three arguments', array( array( 'ihq_portal_onboarding_mirror_handles', 3 ) ) === $GLOBALS['actions'][ IHQ_PORTAL_ONBOARDING_SAVED_ACTION ] );

// --- Saving handles copies them to _ihq_social_handles ---

save( array( 'platforms' => array( 'x', 'youtube' ), 'handles' => array( 'x' => '@me', 'youtube' => 'youtube.com/@me' ) ) );
check( 'saved handles appear in _ihq_social_handles', array( 'x' => '@me', 'youtube' => 'youtube.com/@me' ) === social() );
check( 'the onboarding remembers the handle it wrote for each key', array( 'x' => '@me', 'youtube' => 'youtube.com/@me' ) === mirrored() );

$GLOBALS['user_meta'] = array( 7 => array( '_ihq_social_handles' => array( 'whatsapp' => '+381600000', 'x' => '@old', 'kick' => 'kicker' ) ) );
save( array( 'platforms' => array( 'x' ), 'handles' => array( 'x' => '@new' ) ) );
check( 'an onboarding handle replaces that platform\'s saved one and keeps every other saved handle', array( 'whatsapp' => '+381600000', 'x' => '@new', 'kick' => 'kicker' ) === social() );

save( array( 'platforms' => array() ) );
check( 'unticking takes back only what the onboarding wrote', array( 'whatsapp' => '+381600000', 'kick' => 'kicker' ) === social() );
check( 'and forgets it wrote it', array() === mirrored() );

$GLOBALS['user_meta'] = array( 7 => array( '_ihq_social_handles' => array( 'whatsapp' => '+381600000' ) ) );
save( array( 'platforms' => array( 'whatsapp' ) ) );
check( 'ticking a platform without a handle leaves a registration handle alone', array( 'whatsapp' => '+381600000' ) === social() );
save( array( 'platforms' => array() ) );
check( 'unticking a platform the onboarding never wrote leaves its handle alone', array( 'whatsapp' => '+381600000' ) === social() );

// --- Profile edits are never overwritten by onboarding ---

$GLOBALS['user_meta'] = array();
save( array( 'platforms' => array( 'x', 'qq' ), 'handles' => array( 'x' => '@a', 'qq' => '111' ) ) );
$GLOBALS['user_meta'][7]['_ihq_social_handles']['x'] = '@b';
save( array( 'platforms' => array( 'x', 'qq', 'line' ), 'handles' => array( 'x' => '@a', 'qq' => '111', 'line' => 'ln' ) ) );
check( 'a later onboarding save does not write back a handle edited on Profile, and writes the new one', array( 'x' => '@b', 'qq' => '111', 'line' => 'ln' ) === social() );
check( 'the edited key stays remembered with what the onboarding wrote', array( 'x' => '@a', 'qq' => '111', 'line' => 'ln' ) === mirrored() );

save( array( 'platforms' => array( 'x', 'qq', 'line' ), 'handles' => array( 'x' => '@c', 'qq' => '111', 'line' => 'ln' ) ) );
check( 'changing a Profile-edited handle in onboarding does not overwrite the Profile edit', array( 'x' => '@b', 'qq' => '111', 'line' => 'ln' ) === social() );

save( array( 'platforms' => array( 'qq', 'line' ), 'handles' => array( 'qq' => '111', 'line' => 'ln' ) ) );
check( 'unticking a Profile-edited platform keeps the Profile handle', array( 'x' => '@b', 'qq' => '111', 'line' => 'ln' ) === social() );
check( 'and keeps it remembered, so ticking it again still leaves it alone', array( 'x' => '@a', 'qq' => '111', 'line' => 'ln' ) === mirrored() );
save( array( 'platforms' => array( 'x', 'qq', 'line' ), 'handles' => array( 'x' => '@d', 'qq' => '111', 'line' => 'ln' ) ) );
check( 'ticking a Profile-edited platform again does not overwrite it', array( 'x' => '@b', 'qq' => '111', 'line' => 'ln' ) === social() );

$GLOBALS['user_meta'][7]['_ihq_social_handles']['qq'] = '';
save( array( 'platforms' => array( 'x', 'qq', 'line' ), 'handles' => array( 'x' => '@d', 'qq' => '111', 'line' => 'ln2' ) ) );
check( 'a handle cleared on Profile is not written back', array( 'x' => '@b', 'qq' => '', 'line' => 'ln2' ) === social() );
save( array( 'platforms' => array( 'x', 'line' ), 'handles' => array( 'x' => '@d', 'line' => 'ln2' ) ) );
check( 'unticking a platform cleared on Profile does not delete the cleared entry', array( 'x' => '@b', 'qq' => '', 'line' => 'ln2' ) === social() );

$GLOBALS['user_meta'] = array( 7 => array( '_ihq_social_handles' => array( 'x' => '@a' ), '_ihq_portal_onboarding_mirrored_handles' => array( 'x' => '@a' ) ) );
$GLOBALS['meta_writes'] = 0;
ihq_portal_onboarding_mirror_handles( 7, array( 'platforms' => array( 'x' ) ), array( 'handles' => array( 'x' => '@a' ) ) );
check( 'a save that changes no handle writes no meta', 0 === $GLOBALS['meta_writes'] && array( 'x' => '@a' ) === social() && array( 'x' => '@a' ) === mirrored() );

check(
	'plan: an unchanged handle the onboarding owns is not rewritten',
	array( 'saved' => array( 'x' => '@a' ), 'mirrored' => array( 'x' => '@a' ) ) === ihq_portal_onboarding_plan_mirror( array( 'x' => '@a' ), array( 'x' => '@a' ), array( 'x' => '@a' ) )
);
check(
	'plan: a changed handle the onboarding owns is rewritten',
	array( 'saved' => array( 'x' => '@z' ), 'mirrored' => array( 'x' => '@z' ) ) === ihq_portal_onboarding_plan_mirror( array( 'x' => '@a' ), array( 'x' => '@a' ), array( 'x' => '@z' ) )
);
check(
	'plan: a remembered key missing from the saved handles is not recreated',
	array( 'saved' => array(), 'mirrored' => array( 'x' => '@a' ) ) === ihq_portal_onboarding_plan_mirror( array(), array( 'x' => '@a' ), array( 'x' => '@z' ) )
);

$GLOBALS['user_meta'] = array( 7 => array( '_ihq_portal_onboarding_mirrored_handles' => array( 'x', 'qq' => 5, 'line' => 'ln' ) ) );
check( 'mirrored entries that are not a key and a handle are ignored', array( 'line' => 'ln' ) === ihq_portal_onboarding_mirrored_handles( 7 ) );

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

$rows = ihq_profile_social_platform_rows( array( 'twitch' => 'tw', 'x' => '@x', 'email' => 'a@b.c', 'kick' => '', 'naver-blog' => 'nb', 'myspace' => 'ms', 7 => 'num', 'odd' => array( 'x' ) ) );
check( 'Profile keeps saved platforms from before the 19 after them, in saved order', array_merge( ihq_portal_onboarding_platforms(), array( 'twitch', 'email', 'naver-blog' ) ) === $keys( $rows ) );
check( 'with their names', array( 'Twitch', 'Email', 'Naver Blog' ) === array_slice( $names( $rows ), 19 ) );
check( 'an empty saved value outside the 19 is not shown', ! in_array( 'kick', $keys( $rows ), true ) );
check( 'a saved key that is no known platform is not shown, since Profile could not save it', ! in_array( 'myspace', $keys( $rows ), true ) );
check( 'a platform among the 19 is not listed twice', 1 === count( array_keys( $keys( $rows ), 'x', true ) ) );

$registration = array( 'naver-blog' => 'NB', 'kakao-business' => 'KB', 'telegram-channel' => 'TC', 'ameba' => 'AM' );
check(
	'every registration platform outside the 19 is shown with its name',
	array( 'Naver Blog', 'Kakao Business', 'Telegram Channel', 'Ameba' ) === array_slice( $names( ihq_profile_social_platform_rows( $registration ) ), 19 )
);

// --- Profile saves accept every platform Profile lists, and only those ---

check(
	'the allowlist is the 19 then the platforms from before them',
	array_merge( ihq_portal_onboarding_platforms(), array( 'email', 'kick', 'twitch', 'naver-blog', 'kakao-business', 'telegram-channel', 'ameba' ) ) === ihq_allowed_contact_keys()
);
$listed = $keys( ihq_profile_social_platform_rows( array_fill_keys( array_keys( ihq_profile_legacy_platform_labels() ), 'h' ) ) );
check( 'every key Profile can list is allowed', $listed === ihq_allowed_contact_keys() );

$GLOBALS['user_meta'] = array( 7 => array( '_ihq_social_handles' => array( 'naver-blog' => 'Naver Blog: nb', 'ameba' => 'am', 'x' => '@old', 'myspace' => 'ms' ) ) );
check( 'a Profile save of one of the 19 succeeds', array( 'success' => true, 'status' => null ) === profile_save( 'x', '@new' ) );
check( 'and keeps the registration platforms', array( 'naver-blog' => 'Naver Blog: nb', 'ameba' => 'am', 'x' => '@new' ) === social() );
check( 'an edit of a registration platform is accepted', array( 'success' => true, 'status' => null ) === profile_save( 'naver-blog', 'nb2' ) );
check( 'and saved', array( 'naver-blog' => 'nb2', 'ameba' => 'am', 'x' => '@new' ) === social() );
check( 'an edit of a newly listed platform is accepted', array( 'success' => true, 'status' => null ) === profile_save( 'douyin', 'dy' ) );
check( 'and saved', array( 'naver-blog' => 'nb2', 'ameba' => 'am', 'x' => '@new', 'douyin' => 'dy' ) === social() );
check(
	'an unknown key is refused with a 400',
	array( 'success' => false, 'status' => 400, 'data' => array( 'message' => 'Invalid social field.' ) ) === profile_save( 'myspace', 'ms2' )
);
check( 'and nothing is saved', array( 'naver-blog' => 'nb2', 'ameba' => 'am', 'x' => '@new', 'douyin' => 'dy' ) === social() );

$profile = file_get_contents( __DIR__ . '/../page-portal-profile.php' );
check( 'the Profile page builds its list from the helper', false !== strpos( $profile, '$contact_platforms = ihq_profile_social_platform_rows( $social_handles );' ) );

echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );

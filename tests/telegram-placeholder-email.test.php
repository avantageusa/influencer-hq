<?php
/**
 * ENGR-7549: a Telegram registration's stand-in email uses the reserved
 * domain from inc/placeholder-email.php (<handle>@chat.invalid), never the
 * old @email_telegram.com, so every placeholder check recognises it. Accounts
 * made before keep @email_telegram.com and are still read as Telegram sign-ups
 * through their telegram_user_id.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/telegram-placeholder-email.test.php
 *
 * Exit code is non-zero on any failure.
 */
ob_start();
ini_set( 'error_log', '/dev/null' );

define( 'ABSPATH', '/' );
define( 'MINUTE_IN_SECONDS', 60 );

const TELEGRAM_USER_ID = 424242;

// --- WordPress stubs. ---
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
// Like WordPress's is_email(), filter_var rejects the underscore in email_telegram.com.
function is_email( $email ) { return false !== filter_var( $email, FILTER_VALIDATE_EMAIL ); }

/** Addresses that already belong to a WordPress user. */
function email_exists( $email ) {
	return in_array( $email, $GLOBALS['taken_emails'], true );
}

function get_user_meta( $user_id, $key, $single = false ) {
	return isset( $GLOBALS['user_meta'][ $user_id ][ $key ] ) ? $GLOBALS['user_meta'][ $user_id ][ $key ] : '';
}

function get_userdata( $user_id ) {
	if ( ! isset( $GLOBALS['users'][ $user_id ] ) ) {
		return false;
	}
	return (object) array( 'user_email' => $GLOBALS['users'][ $user_id ] );
}

require __DIR__ . '/../inc/placeholder-email.php';
require __DIR__ . '/../inc/telegram-login-handler.php';
require __DIR__ . '/../inc/influencer-role.php';

$fail = 0;
$out  = array();

function check( $label, $actual, $expected ) {
	global $fail, $out;
	$ok    = $actual === $expected;
	$out[] = ( $ok ? 'PASS ' : 'FAIL ' ) . $label;
	if ( ! $ok ) {
		$out[] = '  expected: ' . var_export( $expected, true );
		$out[] = '  actual:   ' . var_export( $actual, true );
		$fail++;
	}
}

function build_email( $username, array $taken = array() ) {
	$GLOBALS['taken_emails'] = $taken;
	return ihq_telegram_build_fake_email( $username, TELEGRAM_USER_ID );
}

// --- Writer: ihq_telegram_build_fake_email ---
check( 'writer: handle on the stand-in domain', build_email( '@Ann_Lee' ), 'ann_lee@chat.invalid' );
check( 'writer: stand-in is recognised as a placeholder', ihq_is_placeholder_email( build_email( '@Ann_Lee' ) ), true );
check( 'writer: unusable handle falls back to tg<id>', build_email( '@!!!' ), 'tg424242@chat.invalid' );
check( 'writer: taken address gets the next free index', build_email( 'ann', array( 'ann@chat.invalid', 'ann2@chat.invalid' ) ), 'ann3@chat.invalid' );
check(
	'writer: an old @email_telegram.com twin does not block the new address',
	build_email( 'ann', array( 'ann@email_telegram.com' ) ),
	'ann@chat.invalid'
);

$all_taken = array( 'ann@chat.invalid' );
for ( $index = 2; $index < 10000; $index++ ) {
	$all_taken[] = 'ann' . $index . '@chat.invalid';
}
check( 'writer: every index taken falls back to tg<id>', build_email( 'ann', $all_taken ), 'tg424242@chat.invalid' );

// --- Reader: ihq_resolve_user_registration_method ---
$GLOBALS['users']     = array(
	1 => 'ann_lee@chat.invalid',
	2 => 'old_handle@email_telegram.com',
	3 => 'coach-line@chat.invalid',
	4 => 'ann.lee@example.test',
);
$GLOBALS['user_meta'] = array(
	1 => array( 'telegram_user_id' => TELEGRAM_USER_ID ),
	2 => array( 'telegram_user_id' => TELEGRAM_USER_ID + 1 ),
);

check( 'reader: new Telegram account (telegram_user_id) is telegram', ihq_resolve_user_registration_method( 1 ), 'telegram' );
check( 'reader: old @email_telegram.com account (telegram_user_id) is still telegram', ihq_resolve_user_registration_method( 2 ), 'telegram' );
check( 'reader: stand-in without telegram_user_id is not telegram', ihq_resolve_user_registration_method( 3 ), 'email' );
check( 'reader: real email is email', ihq_resolve_user_registration_method( 4 ), 'email' );
check( 'reader: no user id is unknown', ihq_resolve_user_registration_method( 0 ), 'unknown' );

ob_end_clean();
echo implode( "\n", $out ) . "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );

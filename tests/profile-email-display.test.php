<?php
/**
 * ENGR-7611 tests for inc/profile-email-display.php: the Profile page's Email
 * row never shows the stand-in address (<uuid>@chat.invalid), and an account
 * without a real email shows the channel it registered with in its own row.
 * Real inc/placeholder-email.php, WordPress user meta stubbed:
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/profile-email-display.test.php
 *
 * Exit code is non-zero on any failure.
 */
ob_start();
ini_set( 'error_log', '/dev/null' );

define( 'ABSPATH', '/' );
define( 'IHQ_LINE_CONSENT_COMM_MARKER', '__line_consent__' );

const USER_ID        = 42;
const STAND_IN_EMAIL = '3f2b1c9e-1a2b-4c3d-8e9f-0123456789ab@chat.invalid';
const REAL_EMAIL     = 'ann.lee@example.test';

function reset_state( array $meta = array() ) {
	$GLOBALS['user_meta']  = $meta;
	$GLOBALS['meta_reads'] = array();
}

// --- WordPress stubs. ---
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', $s ) ); }
function get_user_meta( $user_id, $key, $single = false ) {
	$GLOBALS['meta_reads'][] = array( $user_id, $key, $single );
	return $GLOBALS['user_meta'][ $key ] ?? '';
}

require __DIR__ . '/../inc/placeholder-email.php';
require __DIR__ . '/../inc/profile-email-display.php';

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

$telegram_meta = array(
	'communication_methods'   => array( 'telegram' => '@ann_lee' ),
	'preferred_communication' => 'telegram',
);

// --- ihq_profile_email_row_value ---
check( 'email row: stand-in is hidden', ihq_profile_email_row_value( STAND_IN_EMAIL ), '' );
check( 'email row: stand-in hidden regardless of case', ihq_profile_email_row_value( ' ABC@Chat.Invalid ' ), '' );
check( 'email row: real email shown as stored', ihq_profile_email_row_value( REAL_EMAIL ), REAL_EMAIL );
check( 'email row: empty stays empty', ihq_profile_email_row_value( '' ), '' );
check( 'email row: null is empty', ihq_profile_email_row_value( null ), '' );
check( 'email row: look-alike domain is a real email', ihq_profile_email_row_value( 'ann@notchat.invalid' ), 'ann@notchat.invalid' );

// --- ihq_profile_registered_channel: real email → no channel row ---
reset_state( $telegram_meta );
check( 'channel: real email → no row', ihq_profile_registered_channel( USER_ID, REAL_EMAIL ), null );
check( 'channel: real email → no meta read', $GLOBALS['meta_reads'], array() );

// --- stand-in + Telegram (the ticket's scenario) ---
reset_state( $telegram_meta );
check(
	'channel: stand-in + Telegram → Telegram row with handle',
	ihq_profile_registered_channel( USER_ID, STAND_IN_EMAIL ),
	array( 'label' => 'Telegram', 'value' => '@ann_lee' )
);
check(
	'channel: reads methods then preferred for the user',
	$GLOBALS['meta_reads'],
	array(
		array( USER_ID, 'communication_methods', true ),
		array( USER_ID, 'preferred_communication', true ),
	)
);

// --- every chat channel gets its label ---
$expected_labels = array(
	'kakaotalk' => 'KakaoTalk',
	'line'      => 'LINE',
	'sms'       => 'SMS',
	'telegram'  => 'Telegram',
	'wechat'    => 'WeChat',
	'whatsapp'  => 'WhatsApp',
	'zalo'      => 'Zalo',
);
check( 'labels: one per registration chat channel', ihq_profile_channel_labels(), $expected_labels );
foreach ( $expected_labels as $key => $label ) {
	reset_state(
		array(
			'communication_methods'   => array( $key => 'h-' . $key ),
			'preferred_communication' => $key,
		)
	);
	check(
		"channel: $key → $label",
		ihq_profile_registered_channel( USER_ID, STAND_IN_EMAIL ),
		array( 'label' => $label, 'value' => 'h-' . $key )
	);
}

// --- preferred key casing and stray characters are normalised ---
reset_state(
	array(
		'communication_methods'   => array( 'whatsapp' => '+15550100' ),
		'preferred_communication' => ' WhatsApp ',
	)
);
check(
	'channel: preferred key normalised',
	ihq_profile_registered_channel( USER_ID, STAND_IN_EMAIL ),
	array( 'label' => 'WhatsApp', 'value' => '+15550100' )
);

// --- no preferred → first chat channel in the methods map ---
reset_state( array( 'communication_methods' => array( 'line' => 'U123', 'telegram' => '@t' ) ) );
check(
	'channel: no preferred → first chat channel',
	ihq_profile_registered_channel( USER_ID, STAND_IN_EMAIL ),
	array( 'label' => 'LINE', 'value' => 'U123' )
);

// --- preferred is email or unknown → skips to a chat channel ---
reset_state(
	array(
		'communication_methods'   => array( 'email' => 'x', 'Kick' => 'k', 'WeChat' => 'wx' ),
		'preferred_communication' => 'email',
	)
);
check(
	'channel: email/unknown preferred and keys skipped, key normalised',
	ihq_profile_registered_channel( USER_ID, STAND_IN_EMAIL ),
	array( 'label' => 'WeChat', 'value' => 'wx' )
);

// --- preferred channel with no stored handle → label only ---
reset_state(
	array(
		'communication_methods'   => array(),
		'preferred_communication' => 'zalo',
	)
);
check(
	'channel: preferred without handle → empty value',
	ihq_profile_registered_channel( USER_ID, STAND_IN_EMAIL ),
	array( 'label' => 'Zalo', 'value' => '' )
);

// --- LINE consent marker is not a handle ---
reset_state(
	array(
		'communication_methods'   => array( 'line' => IHQ_LINE_CONSENT_COMM_MARKER ),
		'preferred_communication' => 'line',
	)
);
check(
	'channel: LINE consent marker hidden',
	ihq_profile_registered_channel( USER_ID, STAND_IN_EMAIL ),
	array( 'label' => 'LINE', 'value' => '' )
);

// --- marker text on a non-LINE channel is shown as typed ---
reset_state(
	array(
		'communication_methods'   => array( 'telegram' => IHQ_LINE_CONSENT_COMM_MARKER ),
		'preferred_communication' => 'telegram',
	)
);
check(
	'channel: marker only special for LINE',
	ihq_profile_registered_channel( USER_ID, STAND_IN_EMAIL ),
	array( 'label' => 'Telegram', 'value' => IHQ_LINE_CONSENT_COMM_MARKER )
);

// --- no chat channel at all → no row ---
reset_state( array( 'communication_methods' => array( 'email' => 'x' ) ) );
check( 'channel: only email method → no row', ihq_profile_registered_channel( USER_ID, STAND_IN_EMAIL ), null );
reset_state( array( 'communication_methods' => 'not-an-array', 'preferred_communication' => 'kick' ) );
check( 'channel: malformed meta → no row', ihq_profile_registered_channel( USER_ID, STAND_IN_EMAIL ), null );
reset_state();
check( 'channel: no meta → no row', ihq_profile_registered_channel( USER_ID, STAND_IN_EMAIL ), null );

// --- an empty stored email (never set) is treated like a stand-in ---
reset_state( $telegram_meta );
check(
	'channel: empty email → channel row',
	ihq_profile_registered_channel( USER_ID, '' ),
	array( 'label' => 'Telegram', 'value' => '@ann_lee' )
);

ob_end_clean();
echo implode( "\n", $out ) . "\n";
echo $fail ? "$fail FAILED\n" : "ALL PASSED\n";
exit( $fail ? 1 : 0 );

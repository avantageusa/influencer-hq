<?php
/**
 * Unit test for PO-3330's Q&A history addition to AI Coach progress
 * persistence (inc/aicoach-progress.php). No WordPress bootstrap: stubs the
 * handful of WP functions the file touches at require-time and the ones
 * ihq_aicoach_progress_sanitize_partial()/save()/load() actually call.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/aicoach-progress.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );
define( 'YEAR_IN_SECONDS', 365 * 24 * 60 * 60 );
define( 'MINUTE_IN_SECONDS', 60 );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

function add_action( $hook, $cb ) {} // routes are never actually registered in this test
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $s ) ); }
function wp_array_slice_assoc( $array, $keys ) { return array_intersect_key( $array, array_flip( $keys ) ); }
function wp_generate_uuid4() { return '00000000-0000-0000-0000-000000000000'; }

$GLOBALS['wp_options'] = array();
function get_option( $key, $default = false ) { return $GLOBALS['wp_options'][ $key ] ?? $default; }
function update_option( $key, $value, $autoload = true ) { $GLOBALS['wp_options'][ $key ] = $value; return true; }
function delete_option( $key ) { unset( $GLOBALS['wp_options'][ $key ] ); return true; }

require __DIR__ . '/../inc/aicoach-progress.php';

// --- sanitize_partial(): valid qaHistory round-trips correctly ---

$sanitized = ihq_aicoach_progress_sanitize_partial( array(
	'qaHistory' => array(
		array( 'question' => 'What is equity?', 'answer' => 'A share of ownership.' ),
		array( 'question' => 'How do I start?', 'answer' => 'Pick a tier and continue.' ),
	),
) );
check( 'qaHistory key is present after sanitizing a valid array', isset( $sanitized['qaHistory'] ) );
check( 'both valid entries survive sanitizing', 2 === count( $sanitized['qaHistory'] ) );
check( 'question text is preserved', 'What is equity?' === $sanitized['qaHistory'][0]['question'] );
check( 'answer text is preserved', 'A share of ownership.' === $sanitized['qaHistory'][0]['answer'] );

// --- sanitize_partial(): malformed entries are dropped, not fatal ---

$sanitized_mixed = ihq_aicoach_progress_sanitize_partial( array(
	'qaHistory' => array(
		array( 'question' => 'Valid one', 'answer' => 'Valid answer' ),
		array( 'question' => 'Missing answer key' ),
		'not an array at all',
		array( 'answer' => 'Missing question key' ),
	),
) );
check( 'only the one well-formed entry survives a mixed/malformed payload', 1 === count( $sanitized_mixed['qaHistory'] ) );
check( 'the surviving entry is the valid one', 'Valid one' === $sanitized_mixed['qaHistory'][0]['question'] );

// --- sanitize_partial(): long text is capped, not rejected outright ---

$long_text = str_repeat( 'a', IHQ_AICOACH_PROGRESS_MAX_QA_TEXT_LENGTH + 500 );
$sanitized_long = ihq_aicoach_progress_sanitize_partial( array(
	'qaHistory' => array( array( 'question' => $long_text, 'answer' => $long_text ) ),
) );
check(
	'an oversized question/answer is capped at IHQ_AICOACH_PROGRESS_MAX_QA_TEXT_LENGTH, not dropped or left uncapped',
	IHQ_AICOACH_PROGRESS_MAX_QA_TEXT_LENGTH === mb_strlen( $sanitized_long['qaHistory'][0]['question'] )
);

// --- sanitize_partial(): an oversized array is capped to the last N entries ---

$many = array();
for ( $i = 0; $i < ihq_aicoach_progress_max_qa_history() + 10; $i++ ) {
	$many[] = array( 'question' => 'q' . $i, 'answer' => 'a' . $i );
}
$sanitized_many = ihq_aicoach_progress_sanitize_partial( array( 'qaHistory' => $many ) );
check(
	'an oversized qaHistory array is capped to ihq_aicoach_progress_max_qa_history() entries',
	ihq_aicoach_progress_max_qa_history() === count( $sanitized_many['qaHistory'] )
);
check(
	'the cap keeps the most RECENT entries (tail), not the oldest',
	'q' . ( ihq_aicoach_progress_max_qa_history() + 9 ) === end( $sanitized_many['qaHistory'] )['question']
);

// --- ihq_aicoach_progress_max_qa_history(): review feedback (PR #76,
// Stefan Vucic/Steve Wolfe) -- this is a wp_option now, not a const, so it
// must actually be tunable without a deploy. ---

check( 'defaults to 20 when the option has never been set', 20 === ihq_aicoach_progress_max_qa_history() );

update_option( 'ihq_aicoach_qa_history_max', 3 );
$few = array();
for ( $i = 0; $i < 5; $i++ ) {
	$few[] = array( 'question' => 'q' . $i, 'answer' => 'a' . $i );
}
$sanitized_few = ihq_aicoach_progress_sanitize_partial( array( 'qaHistory' => $few ) );
check(
	'setting the wp_option to 3 actually changes the applied cap (not a frozen default)',
	3 === count( $sanitized_few['qaHistory'] )
);
delete_option( 'ihq_aicoach_qa_history_max' ); // restore default for any test added after this one

// --- save()/load(): qaHistory persists through the real merge path without
// clobbering, or being clobbered by, unrelated fields already saved ---

$ref = 'test-ref-qa-history';
ihq_aicoach_progress_save( $ref, array( 'tier' => '5' ) );
ihq_aicoach_progress_save( $ref, ihq_aicoach_progress_sanitize_partial( array(
	'qaHistory' => array( array( 'question' => 'Q1', 'answer' => 'A1' ) ),
) ) );
$loaded = ihq_aicoach_progress_load( $ref );
check( 'tier saved earlier survives a later qaHistory-only save (merge, not replace)', '5' === ( $loaded['tier'] ?? null ) );
check( 'qaHistory is present after save+load round-trip', 1 === count( $loaded['qaHistory'] ?? array() ) );

// A later save with a longer qaHistory (the frontend always sends its own
// full array, see aicoach-coach-flow.js) replaces the whole key, matching
// how 'channels' already behaves — not an append.
ihq_aicoach_progress_save( $ref, ihq_aicoach_progress_sanitize_partial( array(
	'qaHistory' => array(
		array( 'question' => 'Q1', 'answer' => 'A1' ),
		array( 'question' => 'Q2', 'answer' => 'A2' ),
	),
) ) );
$loaded_again = ihq_aicoach_progress_load( $ref );
check( 'a subsequent save with the frontend\'s full array replaces qaHistory (2 entries, not 3)', 2 === count( $loaded_again['qaHistory'] ?? array() ) );

echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );

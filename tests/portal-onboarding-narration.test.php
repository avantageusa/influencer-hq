<?php
/**
 * Unit test for Sami's onboarding lines and clips (ENGR-7574, inc/portal-onboarding-narration.php):
 * which lines are used, the render work items made from them, which rendered clips are served
 * and the two filters that hand both to the shell config.
 * No WordPress bootstrap: the filter functions and the pre-render functions it calls are stubbed.
 *
 *     php tests/portal-onboarding-narration.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

$GLOBALS['hooks']          = array();
$GLOBALS['script_filter']  = null; // a callable standing in for the scripts filter, or null for none
$GLOBALS['manifest']       = array();
$GLOBALS['manifest_asked'] = array();
$GLOBALS['urls']           = array();
$GLOBALS['active_version'] = '';

function add_filter( $hook, $cb, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][] = array( $hook, $cb, $priority, $args ); }
function apply_filters( $hook, $value ) {
	if ( 'ihq_portal_onboarding_narration_scripts' === $hook && is_callable( $GLOBALS['script_filter'] ) ) {
		return call_user_func( $GLOBALS['script_filter'], $value );
	}
	return $value;
}
function ihq_portal_onboarding_stages() { return array( 'game_of_kings', 'modern_era', 'equity', 'closing' ); }
function ihq_portal_onboarding_languages() { return array( 'en', 'zh', 'ja' ); }
// The same naming as inc/aicoach-prerender.php: English is the bare key, others get "__<language>".
function ihq_aicoach_prerender_manifest_key( $segment_key, $language = 'en' ) { return 'en' === $language ? $segment_key : "{$segment_key}__{$language}"; }
function ihq_aicoach_avatar_active_version() { return $GLOBALS['active_version']; }
function ihq_aicoach_prerender_load_manifest( $version = '' ) { $GLOBALS['manifest_asked'][] = $version; return $GLOBALS['manifest']; }
function ihq_aicoach_prerender_get_urls( $version = null ) { return $GLOBALS['urls']; }

require __DIR__ . '/../inc/portal-onboarding-narration.php';

function use_scripts( $table ) { $GLOBALS['script_filter'] = function () use ( $table ) { return $table; }; }

// --- Loading ---

check( 'the clips and the lines are added on the shell\'s two filters, once each', array(
	array( 'ihq_portal_onboarding_clips', 'ihq_portal_onboarding_narration_add_clips', 10, 1 ),
	array( 'ihq_portal_onboarding_narration', 'ihq_portal_onboarding_narration_add_lines', 10, 1 ),
) === $GLOBALS['hooks'] );
check( 'no line is approved yet, so the table ships empty', array() === ihq_portal_onboarding_narration_table() );

// --- Which lines are used ---

check( 'with the shipped table there are no lines', array() === ihq_portal_onboarding_narration_scripts() );
check( 'with the shipped table there is nothing to render', array() === ihq_portal_onboarding_narration_work_items() );

$GLOBALS['script_filter'] = function ( $table ) { return 'not a table'; };
check( 'a filter that returns something other than a table gives no lines', array() === ihq_portal_onboarding_narration_scripts() );

use_scripts( array(
	'closing'       => array( 'en' => 'See you soon.' ),
	'game_of_kings' => array(
		'ja' => '  王様のゲーム  ',
		'en' => 'Baccarat is the game of kings.',
		'zh' => '   ',
		'fr' => 'Le jeu des rois.',
		'th' => 'Not one of the languages this test allows.',
	),
	'modern_era'    => array( 'en' => '' ),
	'equity'        => array( 'en' => 42, 'zh' => array( 'nested' ) ),
	'not_a_stage'   => array( 'en' => 'Dropped.' ),
	'equity_example' => 'not a list of languages',
) );
check( 'lines are kept per screen in the onboarding order and per language in the onboarding order, trimmed; blank, non-text, unknown screens and unknown languages are dropped', array(
	'game_of_kings' => array(
		'en' => 'Baccarat is the game of kings.',
		'ja' => '王様のゲーム',
	),
	'closing'       => array( 'en' => 'See you soon.' ),
) === ihq_portal_onboarding_narration_scripts() );

// --- Segment keys and work items ---

check( 'a screen\'s clips are rendered under onboarding_<stage>', 'onboarding_game_of_kings' === ihq_portal_onboarding_narration_segment_key( 'game_of_kings' ) );
check( 'the segment prefix leaves no "__" in a key, which the manifest uses before a language', false === strpos( ihq_portal_onboarding_narration_segment_key( 'game_of_kings' ), '__' ) );
check( 'a fingerprint is the sha256 of the text spoken', hash( 'sha256', 'Hello.' ) === ihq_portal_onboarding_narration_fingerprint( 'Hello.' ) );

check( 'every line becomes one work item, screen by screen, in the pre-render work list shape', array(
	array(
		'manifest_key' => 'onboarding_game_of_kings',
		'segment_key'  => 'onboarding_game_of_kings',
		'language'     => 'en',
		'text'         => 'Baccarat is the game of kings.',
		'sha256'       => hash( 'sha256', 'Baccarat is the game of kings.' ),
	),
	array(
		'manifest_key' => 'onboarding_game_of_kings__ja',
		'segment_key'  => 'onboarding_game_of_kings',
		'language'     => 'ja',
		'text'         => '王様のゲーム',
		'sha256'       => hash( 'sha256', '王様のゲーム' ),
	),
	array(
		'manifest_key' => 'onboarding_closing',
		'segment_key'  => 'onboarding_closing',
		'language'     => 'en',
		'text'         => 'See you soon.',
		'sha256'       => hash( 'sha256', 'See you soon.' ),
	),
) === ihq_portal_onboarding_narration_work_items() );

// --- Which clips are served ---

$scripts = array(
	'game_of_kings' => array( 'en' => 'Kings line.', 'ja' => 'Kings ja.' ),
	'modern_era'    => array( 'en' => 'Modern line.' ),
	'closing'       => array( 'en' => 'Closing line.', 'zh' => 'Closing zh.' ),
);
$manifest = array(
	'onboarding_game_of_kings'     => array( 'fingerprint' => hash( 'sha256', 'Kings line.' ), 'file' => 'onboarding_game_of_kings.mp4' ),
	'onboarding_game_of_kings__ja' => array( 'fingerprint' => hash( 'sha256', 'Kings ja.' ), 'file' => 'onboarding_game_of_kings-ja.mp4' ),
	// Rendered from an older wording of the line.
	'onboarding_modern_era'        => array( 'fingerprint' => hash( 'sha256', 'Old modern line.' ), 'file' => 'onboarding_modern_era.mp4' ),
	// In the manifest, but its file is gone, so the URL list has no entry for it.
	'onboarding_closing'           => array( 'fingerprint' => hash( 'sha256', 'Closing line.' ), 'file' => 'onboarding_closing.mp4' ),
	// A URL exists, but the entry carries no fingerprint.
	'onboarding_closing__zh'       => array( 'file' => 'onboarding_closing-zh.mp4' ),
	// A landing segment in the same manifest.
	'we_believe_1'                 => array( 'fingerprint' => 'abc', 'file' => 'we_believe_1.mp4' ),
);
$urls = array(
	'onboarding_game_of_kings' => array( 'en' => 'https://example.test/k.mp4?v=1', 'ja' => 'https://example.test/k-ja.mp4?v=2' ),
	'onboarding_modern_era'    => array( 'en' => 'https://example.test/m.mp4?v=3' ),
	'onboarding_closing'       => array( 'zh' => 'https://example.test/c-zh.mp4' ),
	'we_believe_1'             => array( 'en' => 'https://example.test/w.mp4' ),
	// Rendered once, but the screen has no line any more.
	'onboarding_equity'        => array( 'en' => 'https://example.test/e.mp4' ),
);
check( 'only clips rendered from the current line, with a file, of a screen that has a line, are served', array(
	'game_of_kings' => array(
		'en' => 'https://example.test/k.mp4?v=1',
		'ja' => 'https://example.test/k-ja.mp4?v=2',
	),
) === ihq_portal_onboarding_narration_clips( $scripts, $manifest, $urls ) );
check( 'no lines serve no clips', array() === ihq_portal_onboarding_narration_clips( array(), $manifest, $urls ) );
check( 'a manifest entry that is not a list is not a match', array() === ihq_portal_onboarding_narration_clips( array( 'modern_era' => array( 'en' => 'Modern line.' ) ), array( 'onboarding_modern_era' => 'broken' ), $urls ) );

// --- The clips filter ---

use_scripts( $scripts );
$GLOBALS['manifest']       = $manifest;
$GLOBALS['urls']           = $urls;
$GLOBALS['active_version'] = 'v2';
$GLOBALS['manifest_asked'] = array();
check( 'the clips filter adds the served clips to what other sources gave, replacing a screen language they share', array(
	'equity'        => array( 'en' => 'from-elsewhere.mp4' ),
	'game_of_kings' => array(
		'en' => 'https://example.test/k.mp4?v=1',
		'zh' => 'kept.mp4',
		'ja' => 'https://example.test/k-ja.mp4?v=2',
	),
) === ihq_portal_onboarding_narration_add_clips( array(
	'equity'        => array( 'en' => 'from-elsewhere.mp4' ),
	'game_of_kings' => array( 'en' => 'replaced.mp4', 'zh' => 'kept.mp4' ),
) ) );
check( 'the clips filter reads the manifest of the avatar version visitors are served', array( 'v2' ) === $GLOBALS['manifest_asked'] );
check( 'the clips filter treats a non-list as nothing from other sources', array(
	'game_of_kings' => array(
		'en' => 'https://example.test/k.mp4?v=1',
		'ja' => 'https://example.test/k-ja.mp4?v=2',
	),
) === ihq_portal_onboarding_narration_add_clips( null ) );

// --- The lines filter ---

check( 'the lines filter adds the lines to what other sources gave', array(
	'equity'        => array( 'en' => 'Other line.' ),
	'game_of_kings' => array( 'en' => 'Kings line.', 'ja' => 'Kings ja.' ),
	'modern_era'    => array( 'en' => 'Modern line.' ),
	'closing'       => array( 'en' => 'Closing line.', 'zh' => 'Closing zh.' ),
) === ihq_portal_onboarding_narration_add_lines( array( 'equity' => array( 'en' => 'Other line.' ) ) ) );
check( 'the lines filter treats a non-list as nothing from other sources', $scripts === ihq_portal_onboarding_narration_add_lines( 'nope' ) );

echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );

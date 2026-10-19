<?php
/**
 * Sami's onboarding lines and their pre-rendered clips (ENGR-7574, PO-3063 T12, PO-3201, PO-3225).
 *
 * One table holds what Sami says on each screen, per language. It feeds both sides, so a clip
 * and its subtitles can never say different words:
 *
 * - The render: every filled line is a work item of `wp aicoach prerender`
 *   (ihq_aicoach_prerender_work_list() in inc/aicoach-prerender.php), which renders it through
 *   Gary's Coach API under the segment key onboarding_<stage> and downloads the clip.
 * - The browser: the shell config carries the lines (`narration`), shown as the captions, and
 *   the rendered clips (`clips`), played with them. A screen with no clip in the language plays
 *   its captions at reading pace; a screen with no line moves on after its reading time.
 *
 * Loaded by functions.php with every other inc/portal-onboarding-*.php file. The functions it
 * calls from other files run only when a filter fires, after everything is loaded.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Segment keys of the onboarding lines start with this, so they never collide with Gary's
 * landing segments in the shared manifest. A segment key must not contain "__", which the
 * manifest uses before a language code.
 */
const IHQ_PORTAL_ONBOARDING_NARRATION_SEGMENT_PREFIX = 'onboarding_';

/**
 * Sami's line per screen and language: [ stage => [ language => text ] ].
 *
 * Empty until Product approves the scripts (evaluation OQ1). Each English line goes in as
 * approved, and each translation only after a native speaker has checked it, because it is
 * rendered and spoken as written. A screen or language left out has no clip and no captions.
 *
 * @return array<string, array<string, string>>
 */
function ihq_portal_onboarding_narration_table() {
	return array();
}

/**
 * The lines to use: the table, through the `ihq_portal_onboarding_narration_scripts` filter (a
 * local site can try a line before it is approved), keeping only the onboarding's own screens
 * and languages and lines that are not blank. Screens come out in the onboarding's order.
 *
 * @return array<string, array<string, string>> [ stage => [ language => text ] ].
 */
function ihq_portal_onboarding_narration_scripts() {
	$table = apply_filters( 'ihq_portal_onboarding_narration_scripts', ihq_portal_onboarding_narration_table() );
	if ( ! is_array( $table ) ) {
		return array();
	}

	$scripts = array();
	foreach ( ihq_portal_onboarding_stages() as $stage ) {
		$lines = isset( $table[ $stage ] ) && is_array( $table[ $stage ] ) ? $table[ $stage ] : array();
		foreach ( ihq_portal_onboarding_languages() as $language ) {
			$text = isset( $lines[ $language ] ) && is_string( $lines[ $language ] ) ? trim( $lines[ $language ] ) : '';
			if ( '' === $text ) {
				continue;
			}
			$scripts[ $stage ][ $language ] = $text;
		}
	}
	return $scripts;
}

/**
 * @param string $stage A stage id.
 * @return string The segment key its clips are rendered under.
 */
function ihq_portal_onboarding_narration_segment_key( $stage ) {
	return IHQ_PORTAL_ONBOARDING_NARRATION_SEGMENT_PREFIX . $stage;
}

/**
 * The hash of a line's text, the work item's sha256. The manifest records a clip's fingerprint
 * through ihq_aicoach_prerender_fingerprint(), which is this hash for English and also carries
 * the language for another; that fingerprint decides whether the render runs again and whether
 * the clip is still served.
 *
 * @param string $text
 * @return string
 */
function ihq_portal_onboarding_narration_fingerprint( $text ) {
	return hash( 'sha256', $text );
}

/**
 * The onboarding lines as items of the pre-render work list (the same shape as the landing
 * segments in ihq_aicoach_prerender_work_list()), screen by screen, English first.
 *
 * @return array<int, array{manifest_key:string, segment_key:string, language:string, text:string, sha256:string}>
 */
function ihq_portal_onboarding_narration_work_items() {
	$items = array();
	foreach ( ihq_portal_onboarding_narration_scripts() as $stage => $lines ) {
		$segment_key = ihq_portal_onboarding_narration_segment_key( $stage );
		foreach ( $lines as $language => $text ) {
			$items[] = array(
				'manifest_key' => ihq_aicoach_prerender_manifest_key( $segment_key, $language ),
				'segment_key'  => $segment_key,
				'language'     => $language,
				'text'         => $text,
				'sha256'       => ihq_portal_onboarding_narration_fingerprint( $text ),
			);
		}
	}
	return $items;
}

/**
 * The onboarding clips that can be served, from the served manifest and the clip URLs built
 * from it. A clip is served only while it was rendered from the line as it is now: after a
 * line changes, its old clip would say different words than the captions, so the screen plays
 * the new captions alone until the next render. The same holds for a clip in a language other
 * than English rendered before its request named its language: it is not served until it is
 * rendered again with its language.
 *
 * @param array<string, array<string, string>> $scripts  From ihq_portal_onboarding_narration_scripts().
 * @param array                                $manifest The served manifest: manifest key => { fingerprint, file, … }.
 * @param array<string, array<string, string>> $urls     From ihq_aicoach_prerender_get_urls(): segment key => language => URL.
 * @return array<string, array<string, string>> [ stage => [ language => url ] ].
 */
function ihq_portal_onboarding_narration_clips( array $scripts, array $manifest, array $urls ) {
	$clips = array();
	foreach ( $scripts as $stage => $lines ) {
		$segment_key = ihq_portal_onboarding_narration_segment_key( $stage );
		foreach ( $lines as $language => $text ) {
			$url = isset( $urls[ $segment_key ][ $language ] ) ? $urls[ $segment_key ][ $language ] : '';
			if ( '' === $url ) {
				continue;
			}
			$entry       = $manifest[ ihq_aicoach_prerender_manifest_key( $segment_key, $language ) ] ?? array();
			$fingerprint = is_array( $entry ) && isset( $entry['fingerprint'] ) ? $entry['fingerprint'] : '';
			if ( ihq_aicoach_prerender_fingerprint( ihq_portal_onboarding_narration_fingerprint( $text ), $language ) !== $fingerprint ) {
				continue;
			}
			$clips[ $stage ][ $language ] = $url;
		}
	}
	return $clips;
}

/**
 * Hooked on `ihq_portal_onboarding_clips`: adds the rendered onboarding clips of the avatar
 * version visitors are served.
 *
 * @param array $clips [ stage => [ language => url ] ] from other sources.
 * @return array
 */
function ihq_portal_onboarding_narration_add_clips( $clips ) {
	$clips = is_array( $clips ) ? $clips : array();
	if ( ! function_exists( 'ihq_aicoach_prerender_get_urls' ) ) {
		return $clips;
	}
	$manifest = ihq_aicoach_prerender_load_manifest( ihq_aicoach_avatar_active_version() );
	$rendered = ihq_portal_onboarding_narration_clips( ihq_portal_onboarding_narration_scripts(), $manifest, ihq_aicoach_prerender_get_urls() );
	return array_replace_recursive( $clips, $rendered );
}
add_filter( 'ihq_portal_onboarding_clips', 'ihq_portal_onboarding_narration_add_clips' );

/**
 * Hooked on `ihq_portal_onboarding_narration`: the lines the browser shows as captions.
 *
 * @param array $narration [ stage => [ language => text ] ] from other sources.
 * @return array
 */
function ihq_portal_onboarding_narration_add_lines( $narration ) {
	$narration = is_array( $narration ) ? $narration : array();
	return array_replace_recursive( $narration, ihq_portal_onboarding_narration_scripts() );
}
add_filter( 'ihq_portal_onboarding_narration', 'ihq_portal_onboarding_narration_add_lines' );

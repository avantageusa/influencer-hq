<?php
/**
 * Pre-rendered avatar video clips for the AI Coach's approved script segments.
 *
 * Gary's registration script (GET /coach/v1/registration/scripts, proxied by
 * ihq_coach_handle_scripts() in inc/gary-proxy.php) has 11 fixed segments,
 * each carrying an approved sha256. Only ONE of them (intro) is spoken live
 * today, via Anam's real-time session — everything after it is a static
 * caption with no voice at all (see the top-of-file note in
 * js/aicoach-coach-flow.js). Per Gary's own recommendation (2026-09-21,
 * Teams): approved, fixed-copy segments are cheaper and more reliably
 * "on-script" as pre-rendered clips (Anam's POST /v1/avatar-videos, a
 * text-to-MP4 render of the exact script, no LLM involved) than live avatar
 * turns; live stays reserved for parts where the visitor actually asks
 * something.
 *
 * This file renders each APPROVED segment to MP4 once (via `wp aicoach
 * prerender`), downloads it locally (the render API's content.url expires —
 * never relied on at runtime), and exposes the resulting URLs to the
 * frontend keyed by Gary's own segment key. js/aicoach-coach-flow.js maps
 * its panel names to these segment keys and plays the clip instead of the
 * static caption when one exists.
 *
 * Rendering is idempotent per segment text: the Idempotency-Key sent to Anam
 * IS the segment's sha256, so re-running the command after Gary approves new
 * copy only re-renders segments whose sha256 actually changed.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

// SCREENS panel name (js/aicoach-coach-flow.js) -> Gary's segment key (GET
// /coach/v1/registration/scripts). Kept here, not derived, because the two
// naming schemes predate each other and don't match everywhere — "home" is
// the tier-selection panel but Gary calls the same narration
// "time_selection", and Gary's "competitions" is one combined segment where
// this page currently shows three separate panels (world/community/private)
// with no single matching clip; that one is deliberately left OUT of this
// map until there's a UI-side decision on how to split (or not split) it.
function ihq_aicoach_prerender_panel_map() {
	return array(
		'believe-1'   => 'we_believe_1',
		'believe-2'   => 'we_believe_2',
		'home'        => 'time_selection',
		'equity-magic' => 'magic_johnson',
		'equity-alix' => 'alix_earle',
		'equity-bts'  => 'bts',
	);
}

/**
 * Local storage directory for downloaded clips, created on first use.
 *
 * @return string Absolute path, no trailing slash.
 */
function ihq_aicoach_prerender_dir() {
	$upload_dir = wp_upload_dir();
	$dir        = trailingslashit( $upload_dir['basedir'] ) . 'aicoach-videos';
	if ( ! file_exists( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	return $dir;
}

/**
 * Public URL for the storage directory.
 *
 * @return string
 */
function ihq_aicoach_prerender_url_base() {
	$upload_dir = wp_upload_dir();
	return trailingslashit( $upload_dir['baseurl'] ) . 'aicoach-videos';
}

/**
 * Path to the manifest mapping segment key -> { sha256, file, rendered_at }.
 *
 * @return string
 */
function ihq_aicoach_prerender_manifest_path() {
	return ihq_aicoach_prerender_dir() . '/manifest.json';
}

/**
 * @return array<string,array{sha256:string,file:string,rendered_at:string}>
 */
function ihq_aicoach_prerender_load_manifest() {
	$path = ihq_aicoach_prerender_manifest_path();
	if ( ! file_exists( $path ) ) {
		return array();
	}
	$decoded = json_decode( (string) file_get_contents( $path ), true );
	return is_array( $decoded ) ? $decoded : array();
}

/**
 * @param array $manifest
 */
function ihq_aicoach_prerender_save_manifest( array $manifest ) {
	file_put_contents( ihq_aicoach_prerender_manifest_path(), wp_json_encode( $manifest, JSON_PRETTY_PRINT ) );
}

/**
 * Segment key -> public clip URL, for whatever has actually been rendered
 * and downloaded so far. Missing keys simply have no clip yet — the caller
 * falls back to the static caption, same as before this feature existed.
 *
 * @return array<string,string>
 */
function ihq_aicoach_prerender_get_urls() {
	$manifest = ihq_aicoach_prerender_load_manifest();
	$urls     = array();
	foreach ( $manifest as $segment_key => $entry ) {
		if ( ! empty( $entry['file'] ) && file_exists( ihq_aicoach_prerender_dir() . '/' . $entry['file'] ) ) {
			$urls[ $segment_key ] = ihq_aicoach_prerender_url_base() . '/' . rawurlencode( $entry['file'] );
		}
	}
	return $urls;
}

/**
 * POST /v1/avatar-videos — start a render job. Uses the saved Sami persona
 * (same one everything else on this page uses) rather than a raw
 * avatar/voice pair, so a persona update (PO-3092's photo/voice fixes)
 * automatically applies to future re-renders too.
 *
 * @param string $script          Exact text to speak (Anam: TTS only, no LLM).
 * @param string $idempotency_key Stable per-script key — this file always
 *                                 passes the segment's own sha256.
 * @return array|WP_Error Decoded response body on success.
 */
function ihq_aicoach_anam_create_video( $script, $idempotency_key ) {
	$api_key = anam_hq_api_key();
	if ( ! $api_key ) {
		return new WP_Error( 'anam_not_configured', 'ANAM_API_KEY is not set.' );
	}

	$response = wp_remote_post(
		ANAM_HQ_BASE_URL . '/avatar-videos',
		array(
			'timeout'     => 30,
			// A redirect would resend the Authorization header (with the live
			// Anam API key) to wherever it points — same CWE-200 discipline
			// already applied to Gary's requests in inc/gary-proxy.php.
			'redirection' => 0,
			'headers'     => array(
				'Authorization'    => 'Bearer ' . $api_key,
				'Content-Type'     => 'application/json',
				'Idempotency-Key'  => $idempotency_key,
			),
			'body'    => wp_json_encode(
				array(
					'personaId' => anam_hq_persona_id(),
					'script'    => $script,
				)
			),
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status = (int) wp_remote_retrieve_response_code( $response );
	$body   = json_decode( wp_remote_retrieve_body( $response ), true );
	// Only 200-299 is a real success. redirection => 0 above means a 3xx no
	// longer gets silently followed — it comes back here instead, and with
	// only a ">= 400" check it would fall through as "success" carrying an
	// empty/non-JSON body, handing the caller a job with no id or status.
	if ( $status < 200 || $status >= 300 ) {
		return new WP_Error(
			'anam_create_video_failed',
			sprintf( 'Anam avatar-video create failed (HTTP %d): %s', $status, wp_remote_retrieve_body( $response ) )
		);
	}
	return $body;
}

/**
 * GET /v1/avatar-videos/{id} — one status poll.
 *
 * @param string $job_id
 * @return array|WP_Error
 */
function ihq_aicoach_anam_get_video( $job_id ) {
	$api_key = anam_hq_api_key();
	if ( ! $api_key ) {
		return new WP_Error( 'anam_not_configured', 'ANAM_API_KEY is not set.' );
	}

	$response = wp_remote_get(
		ANAM_HQ_BASE_URL . '/avatar-videos/' . rawurlencode( $job_id ),
		array(
			'timeout'     => 30,
			'redirection' => 0, // see ihq_aicoach_anam_create_video()'s comment — same CWE-200 concern.
			'headers'     => array( 'Authorization' => 'Bearer ' . $api_key ),
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status = (int) wp_remote_retrieve_response_code( $response );
	$body   = json_decode( wp_remote_retrieve_body( $response ), true );
	// See ihq_aicoach_anam_create_video()'s comment — same 3xx-vs->=400 gap.
	if ( $status < 200 || $status >= 300 ) {
		return new WP_Error(
			'anam_get_video_failed',
			sprintf( 'Anam avatar-video get failed (HTTP %d): %s', $status, wp_remote_retrieve_body( $response ) )
		);
	}
	return $body;
}

/**
 * Render one segment end-to-end: create the job, poll until it's done,
 * download the MP4, and record it in the manifest. Skips the whole thing
 * (returns 'cached') if the manifest already has this exact sha256 stored
 * and the file is still on disk.
 *
 * @param string $segment_key Gary's segment key, e.g. "we_believe_1".
 * @param string $text        Exact approved script text.
 * @param string $sha256      Gary's sha256 for this exact text — also used
 *                             as the Anam Idempotency-Key.
 * @param callable|null $log  Optional fn(string $line) for CLI progress output.
 * @return array{status:string, url?:string, error?:string}
 */
function ihq_aicoach_prerender_segment( $segment_key, $text, $sha256, $log = null ) {
	$log = $log ?: function ( $line ) {};

	// Cache key on sha256 alone would keep serving a clip rendered with a
	// now-stale persona (voice/avatar) after anam_hq_persona_id() changes,
	// since the script text — and therefore its sha256 — never moved. Fold
	// the persona into the identity used for both the cache check and the
	// Anam Idempotency-Key so a persona change forces a fresh render.
	$fingerprint = hash( 'sha256', $sha256 . '|' . anam_hq_persona_id() );

	$manifest = ihq_aicoach_prerender_load_manifest();
	$existing = $manifest[ $segment_key ] ?? null;
	if ( $existing && ( $existing['fingerprint'] ?? '' ) === $fingerprint && file_exists( ihq_aicoach_prerender_dir() . '/' . $existing['file'] ) ) {
		$log( "  {$segment_key}: already rendered for this script+persona, skipping" );
		return array( 'status' => 'cached' );
	}

	$log( "  {$segment_key}: creating render job..." );
	$job = ihq_aicoach_anam_create_video( $text, $fingerprint );
	if ( is_wp_error( $job ) ) {
		return array(
			'status' => 'error',
			'error'  => $job->get_error_message(),
		);
	}

	$job_id     = $job['id'] ?? null;
	$terminal   = array( 'completed', 'failed', 'cancelled' );
	$status_now = $job['status'] ?? 'pending';
	$attempts   = 0;
	// Segments here run 10-40s of speech; renders have been slower than
	// realtime in testing. 60 polls x 5s = up to 5 minutes per segment
	// before giving up, generous on purpose — this runs offline via WP-CLI,
	// not in a visitor-facing request.
	while ( ! in_array( $status_now, $terminal, true ) && $attempts < 60 ) {
		sleep( 5 );
		++$attempts;
		$job = ihq_aicoach_anam_get_video( $job_id );
		if ( is_wp_error( $job ) ) {
			return array(
				'status' => 'error',
				'error'  => $job->get_error_message(),
			);
		}
		$status_now = $job['status'] ?? 'pending';
		$log( "  {$segment_key}: status={$status_now} ({$attempts}/60)" );
	}

	if ( 'completed' !== $status_now ) {
		return array(
			'status' => 'error',
			'error'  => "render did not complete in time (last status: {$status_now})",
		);
	}

	$video_url = $job['content']['url'] ?? null;
	if ( ! $video_url ) {
		return array(
			'status' => 'error',
			'error'  => 'completed but content.url missing',
		);
	}

	$log( "  {$segment_key}: downloading..." );
	$dest_file = ihq_aicoach_prerender_dir() . "/{$segment_key}.mp4";
	$download  = wp_remote_get( $video_url, array( 'timeout' => 60, 'stream' => true, 'filename' => $dest_file ) );
	if ( is_wp_error( $download ) ) {
		return array(
			'status' => 'error',
			'error'  => 'download failed: ' . $download->get_error_message(),
		);
	}
	// wp_remote_get() with 'filename' streams the response body straight to
	// disk regardless of HTTP status — an expired/forbidden content.url would
	// otherwise get saved as a "valid" .mp4 and marked rendered, and every
	// future run would see the file+fingerprint match and keep serving it.
	$download_status = (int) wp_remote_retrieve_response_code( $download );
	// Same 2xx-only discipline as the two Anam API calls above — a terminal
	// 3xx (redirects exhausted, a 304, etc.) would otherwise pass this check
	// and get saved as a "valid" cached clip.
	if ( $download_status < 200 || $download_status >= 300 ) {
		if ( file_exists( $dest_file ) ) {
			wp_delete_file( $dest_file );
		}
		return array(
			'status' => 'error',
			'error'  => "download failed: HTTP {$download_status} from content.url",
		);
	}
	// A 2xx with an empty body (204 No Content, or any response that streams
	// zero bytes) passes the status check above but leaves a 0-byte file —
	// that still satisfies the cache check's file_exists() on every future
	// run, so a genuinely broken render would silently keep "succeeding".
	if ( ! file_exists( $dest_file ) || 0 === filesize( $dest_file ) ) {
		if ( file_exists( $dest_file ) ) {
			wp_delete_file( $dest_file );
		}
		return array(
			'status' => 'error',
			'error'  => "download failed: HTTP {$download_status} but the saved file is empty",
		);
	}

	$manifest[ $segment_key ] = array(
		'fingerprint' => $fingerprint,
		'file'        => "{$segment_key}.mp4",
		'rendered_at' => gmdate( 'c' ),
	);
	ihq_aicoach_prerender_save_manifest( $manifest );

	return array(
		'status' => 'rendered',
		'url'    => ihq_aicoach_prerender_url_base() . "/{$segment_key}.mp4",
	);
}

/**
 * Render every approved segment this page actually uses (per
 * ihq_aicoach_prerender_panel_map()). Called by the WP-CLI command below.
 *
 * @param callable|null $log Optional fn(string $line) for progress output.
 * @return array<string,array>|WP_Error segment_key => result (same shape as
 *                              ihq_aicoach_prerender_segment()'s return), or
 *                              a WP_Error if the scripts fetch itself failed
 *                              — distinct from an empty array, which means
 *                              the fetch succeeded and nothing needed doing.
 */
function ihq_aicoach_prerender_all( $log = null ) {
	$log = $log ?: function ( $line ) {};

	$scripts_result = ihq_coach_request( 'GET', '/coach/v1/registration/scripts', null );
	if ( is_wp_error( $scripts_result ) ) {
		$log( 'Failed to fetch registration scripts: ' . $scripts_result->get_error_message() );
		return $scripts_result;
	}
	// ihq_coach_request() only returns a WP_Error for a transport-level
	// failure (DNS, connection) — a non-2xx from Gary's own API (500, 404,
	// ...) comes back here as a normal array with that status code. Without
	// this check, a Gary-side outage would make $segments silently empty,
	// every wanted segment log "not present... skipping", and the whole run
	// report as a clean, empty success.
	$scripts_status = (int) ( $scripts_result['status'] ?? 0 );
	if ( $scripts_status < 200 || $scripts_status >= 300 ) {
		$log( "Failed to fetch registration scripts: Gary returned HTTP {$scripts_status}" );
		return new WP_Error(
			'coach_scripts_fetch_failed',
			"Gary returned HTTP {$scripts_status} for GET /coach/v1/registration/scripts"
		);
	}

	$segments = $scripts_result['body']['segments'] ?? array();
	$wanted   = array_unique( array_values( ihq_aicoach_prerender_panel_map() ) );
	$results  = array();

	foreach ( $wanted as $segment_key ) {
		$segment = $segments[ $segment_key ] ?? null;
		if ( ! $segment ) {
			$log( "{$segment_key}: not present in Gary's script manifest, skipping" );
			continue;
		}
		if ( 'approved' !== ( $segment['status'] ?? '' ) ) {
			$log( "{$segment_key}: status is '{$segment['status']}', not approved — skipping (held content stays as static caption for now)" );
			continue;
		}
		$log( "{$segment_key}: rendering..." );
		$results[ $segment_key ] = ihq_aicoach_prerender_segment( $segment_key, $segment['text'], $segment['sha256'], $log );
	}

	return $results;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Pre-render AI Coach avatar clips for approved script segments.
	 */
	class IHQ_Aicoach_Prerender_Command {
		/**
		 * Render every approved segment that isn't already cached for its
		 * current sha256.
		 *
		 * ## EXAMPLES
		 *
		 *     wp aicoach prerender
		 *
		 * @when after_wp_load
		 */
		public function prerender( $args, $assoc_args ) {
			$results = ihq_aicoach_prerender_all(
				function ( $line ) {
					WP_CLI::log( $line );
				}
			);

			// A deploy step checking this command's exit code must be able to
			// tell "nothing to render" apart from "the whole run failed" —
			// WP_CLI::success() always exits 0, so either the top-level fetch
			// failing or any per-segment error has to reach WP_CLI::error()
			// instead, or a broken render would silently report as green.
			if ( is_wp_error( $results ) ) {
				WP_CLI::error( 'Failed to fetch registration scripts: ' . $results->get_error_message() );
				return;
			}

			$rendered = 0;
			$cached   = 0;
			$errors   = 0;
			foreach ( $results as $segment_key => $result ) {
				if ( 'rendered' === $result['status'] ) {
					++$rendered;
				} elseif ( 'cached' === $result['status'] ) {
					++$cached;
				} else {
					++$errors;
					WP_CLI::warning( "{$segment_key}: {$result['error']}" );
				}
			}

			$summary = "Rendered: {$rendered}, already cached: {$cached}, errors: {$errors}.";
			if ( $errors > 0 ) {
				WP_CLI::error( "Done with errors. {$summary}" );
				return;
			}
			WP_CLI::success( "Done. {$summary}" );
		}
	}

	WP_CLI::add_command( 'aicoach', 'IHQ_Aicoach_Prerender_Command' );
}

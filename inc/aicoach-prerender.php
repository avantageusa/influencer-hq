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
 * "on-script" as pre-rendered clips than live avatar turns; live stays
 * reserved for parts where the visitor actually asks something.
 *
 * Rendering goes through Gary's own Coach API (POST /coach/v1/videos et al,
 * inc/gary-proxy.php's ihq_coach_request()/ihq_coach_download()) — NOT a
 * direct call to Anam. This file used to call Anam's avatar-videos endpoint
 * directly, passing this theme's own avatar_id/voice_id/avatarModel so the
 * pre-rendered clips would match Gary's live avatar; Gary's email
 * (2026-09-23) asked us to stop doing that and go through the Coach API
 * instead: "No avatar, voice or model ids in your payload. The box pins the
 * same ones the live session uses, so pre-rendered and live match, and if we
 * change provider or model you change nothing." That also means capacity
 * failures (the "All engines are currently at capacity" errors this was
 * originally built to retry around) are now retried on Gary's side, with
 * backoff, under the SAME job id, for up to 2 hours — confirmed against
 * coach-api.openapi.json and coach-client.mjs (Gary's reference client,
 * 2026-09-23) and verified live end-to-end (create, poll, download, and an
 * idempotent replay all confirmed against the real influencerhq box). We
 * never resubmit a slow job; see ihq_aicoach_prerender_segment()'s poll loop.
 *
 * This file renders each APPROVED segment to MP4 once (via `wp aicoach
 * prerender`), downloads it locally (content.url is a path back into the
 * same signed API, not a public CDN link — never relied on at runtime), and
 * exposes the resulting URLs to the frontend keyed by Gary's own segment
 * key. js/aicoach-coach-flow.js maps its panel names to these segment keys
 * and plays the clip instead of the static caption when one exists.
 *
 * Rendering is idempotent per segment text: the Idempotency-Key sent to Gary
 * IS derived from the segment's own sha256, so re-running the command after
 * Gary approves new copy only re-renders segments whose sha256 actually
 * changed. Re-sending the same key for a job still in progress (or already
 * done) is safe and cheap — Gary's API returns the existing job
 * (`idempotent_replayed: true`) instead of starting a second render,
 * confirmed live — which is what lets ihq_aicoach_prerender_segment() below
 * just re-POST every run instead of tracking an in-flight job id anywhere.
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
 * Every URL carries a ?v={fingerprint} cache-buster. The filename itself
 * never changes (always "{segment_key}.mp4"), and re-rendering overwrites
 * it in place — confirmed live (2026-09-23) that Cloudflare's edge cache
 * (public, max-age=31536000 on this upload path) happily keeps serving the
 * pre-re-render bytes under that unchanged URL for a full year otherwise, a
 * new script or a new avatar/voice from Gary is invisible to visitors no
 * matter how successfully it re-rendered server-side. The fingerprint
 * already changes on exactly those occasions (it's what decides whether a
 * re-render happens at all), so reusing it here makes each real content
 * change a genuinely new URL instead of fighting the CDN's cache lifetime.
 *
 * @return array<string,string>
 */
function ihq_aicoach_prerender_get_urls() {
	$manifest = ihq_aicoach_prerender_load_manifest();
	$urls     = array();
	foreach ( $manifest as $segment_key => $entry ) {
		if ( ! empty( $entry['file'] ) && file_exists( ihq_aicoach_prerender_dir() . '/' . $entry['file'] ) ) {
			$url = ihq_aicoach_prerender_url_base() . '/' . rawurlencode( $entry['file'] );
			if ( ! empty( $entry['fingerprint'] ) ) {
				$url = add_query_arg( 'v', substr( $entry['fingerprint'], 0, 12 ), $url );
			}
			$urls[ $segment_key ] = $url;
		}
	}
	return $urls;
}

/**
 * POST /coach/v1/videos — start (or, for an already-known idempotency key,
 * resume/replay) a render job. No avatar/voice/model ids in the payload —
 * per Gary's email (2026-09-23), the box pins whatever the live session
 * currently uses, so this file no longer needs to read or track that config
 * at all.
 *
 * @param string $script          Exact text to speak.
 * @param string $idempotency_key Stable per-script key — see
 *                                 ihq_aicoach_prerender_segment().
 * @param string $segment_key     Gary's segment key, sent as metadata purely
 *                                 for our own visibility (GET /v1/videos),
 *                                 confirmed accepted as a free-form object.
 * @return array|WP_Error Decoded response body on success.
 */
function ihq_aicoach_coach_create_video( $script, $idempotency_key, $segment_key ) {
	$result = ihq_coach_request(
		'POST',
		'/coach/v1/videos',
		array(
			'script'   => $script,
			'metadata' => array( 'segment' => $segment_key ),
		),
		array( 'Idempotency-Key' => $idempotency_key )
	);
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	// is_wp_error() only catches a transport-level failure — a non-2xx from
	// Gary itself comes back as a normal array here. A fresh create is 202;
	// an idempotent replay of an already-known key is 200 (confirmed live,
	// 2026-09-23) — both are success, so this only rejects genuine errors.
	$status = (int) $result['status'];
	if ( $status < 200 || $status >= 300 ) {
		return new WP_Error(
			'coach_video_create_failed',
			sprintf( 'Coach video create failed (HTTP %d): %s', $status, wp_json_encode( $result['body'] ) )
		);
	}
	return $result['body'];
}

/**
 * GET /coach/v1/videos/{id} — one status poll.
 *
 * @param string $job_id
 * @return array|WP_Error
 */
function ihq_aicoach_coach_get_video( $job_id ) {
	$result = ihq_coach_request( 'GET', '/coach/v1/videos/' . rawurlencode( $job_id ), null );
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	$status = (int) $result['status'];
	if ( $status < 200 || $status >= 300 ) {
		return new WP_Error(
			'coach_video_get_failed',
			sprintf( 'Coach video get failed (HTTP %d): %s', $status, wp_json_encode( $result['body'] ) )
		);
	}
	return $result['body'];
}

/**
 * Render one segment end-to-end: create (or resume) the job, poll for a
 * bounded window, download the MP4 once ready, and record it in the
 * manifest. Skips the whole thing (returns 'cached') if the manifest already
 * has this exact sha256 stored and the file is still on disk.
 *
 * @param string        $segment_key Gary's segment key, e.g. "we_believe_1".
 * @param string        $text        Exact approved script text.
 * @param string        $sha256      Gary's sha256 for this exact text —
 *                                    used for both the cache check and the
 *                                    Coach API Idempotency-Key.
 * @param callable|null $log         Optional fn(string $line) for CLI progress output.
 * @return array{status:string, url?:string, error?:string, job_id?:string}
 */
function ihq_aicoach_prerender_segment( $segment_key, $text, $sha256, $log = null ) {
	$log = $log ?: function ( $line ) {};

	$manifest = ihq_aicoach_prerender_load_manifest();
	$existing = $manifest[ $segment_key ] ?? null;
	if ( $existing && ( $existing['fingerprint'] ?? '' ) === $sha256 && file_exists( ihq_aicoach_prerender_dir() . '/' . $existing['file'] ) ) {
		$log( "  {$segment_key}: already rendered for this script, skipping" );
		return array( 'status' => 'cached' );
	}

	// Human-readable + deterministic, matching Gary's own example format
	// (email, 2026-09-23: "Idempotency-Key: ihq-seg-magic-johnson-v1"). Stable
	// per exact script text, so re-running this after Gary approves new copy
	// only touches segments whose sha256 actually changed.
	$idempotency_key = 'ihq-seg-' . $segment_key . '-' . substr( $sha256, 0, 16 );

	$log( "  {$segment_key}: creating (or resuming) render job..." );
	$job = ihq_aicoach_coach_create_video( $text, $idempotency_key, $segment_key );
	if ( is_wp_error( $job ) ) {
		return array(
			'status' => 'error',
			'error'  => $job->get_error_message(),
		);
	}

	$job_id   = $job['id'] ?? null;
	$terminal = array( 'ready', 'failed' );
	// Confirmed live (2026-09-23) and in coach-client.mjs, Gary's own
	// reference client: capacity failures are retried on Gary's side, with
	// backoff, under this SAME job id, for up to 2 hours — while waiting, the
	// job's waiting_reason field is set (e.g. "render_capacity"). We never
	// resubmit a slow job (there's no retry endpoint in this API — POSTing
	// again just idempotently replays this same job, confirmed live). This
	// loop only polls for a bounded window per invocation, short enough to be
	// safe both in a WP-CLI run and inside a single HTTP request (the SFTP
	// one-off trigger script dev uses, since dev has no WP-CLI — see the
	// top-of-file comment). If the window elapses before "ready"/"failed",
	// this returns 'pending', not 'error': the job keeps rendering
	// server-side regardless, and the NEXT run's create call above will
	// idempotently replay this same job and just keep polling it — confirmed
	// live that a replay returns the job's current state instantly, ready or
	// not.
	$max_attempts = 18; // 18 x 5s = 90s.
	$attempts     = 0;
	$status_now   = $job['status'] ?? 'queued';
	while ( ! in_array( $status_now, $terminal, true ) && $attempts < $max_attempts ) {
		sleep( 5 );
		++$attempts;
		$job = ihq_aicoach_coach_get_video( $job_id );
		if ( is_wp_error( $job ) ) {
			return array(
				'status' => 'error',
				'error'  => $job->get_error_message(),
				'job_id' => $job_id,
			);
		}
		$status_now     = $job['status'] ?? 'queued';
		$waiting_reason = $job['waiting_reason'] ?? null;
		$log( "  {$segment_key}: status={$status_now}" . ( $waiting_reason ? " ({$waiting_reason})" : '' ) . " ({$attempts}/{$max_attempts})" );
	}

	if ( 'failed' === $status_now ) {
		// "failed" is final (Gary's email) and carries a stable code —
		// render_capacity, render_unavailable, or render_rejected — surfaced
		// verbatim rather than hardcoding the enum, so a new code Gary adds
		// later still shows up instead of silently falling through.
		$failure_code    = $job['failure']['code'] ?? 'unknown';
		$failure_message = $job['failure']['message'] ?? '';
		return array(
			'status' => 'error',
			'error'  => "render failed ({$failure_code})" . ( $failure_message ? ": {$failure_message}" : '' ),
			'job_id' => $job_id,
		);
	}

	if ( 'ready' !== $status_now ) {
		$log( "  {$segment_key}: still {$status_now} after {$max_attempts} polls — job continues server-side, re-run later to pick it back up" );
		return array(
			'status' => 'pending',
			'error'  => "render still in progress (status: {$status_now})",
			'job_id' => $job_id,
		);
	}

	$content_path = $job['content']['url'] ?? null;
	if ( ! $content_path ) {
		return array(
			'status' => 'error',
			'error'  => 'ready but content.url missing',
			'job_id' => $job_id,
		);
	}

	$log( "  {$segment_key}: downloading..." );
	$dest_file = ihq_aicoach_prerender_dir() . "/{$segment_key}.mp4";
	// A sha256 change now routinely forces a re-render of a segment that
	// already has a perfectly good clip serving live visitors. Streaming
	// straight into $dest_file would truncate that valid file the moment the
	// download starts, before we know the replacement is any good — a failed
	// re-render would then take down a clip that was working fine seconds
	// earlier. Stream to a temp file instead, validate it, and only replace
	// $dest_file once validation passes; on failure, only the temp file is
	// removed and the existing clip is untouched.
	$temp_file = $dest_file . '.tmp';
	// content.url is a path back into this same signed API ("/coach/v1/videos/
	// {id}/content"), not a public CDN link — confirmed live (2026-09-23) — so
	// this needs ihq_coach_download()'s signed GET, not a bare wp_remote_get().
	$download = ihq_coach_download( $content_path, $temp_file );
	if ( is_wp_error( $download ) ) {
		if ( file_exists( $temp_file ) ) {
			wp_delete_file( $temp_file );
		}
		return array(
			'status' => 'error',
			'error'  => 'download failed: ' . $download->get_error_message(),
			'job_id' => $job_id,
		);
	}
	// ihq_coach_download() streams the response body straight to disk
	// regardless of HTTP status — an error response would otherwise get
	// saved as a "valid" .mp4 and marked rendered, and every future run
	// would see the file+fingerprint match and keep serving it.
	$download_status = (int) $download['status'];
	if ( $download_status < 200 || $download_status >= 300 ) {
		if ( file_exists( $temp_file ) ) {
			wp_delete_file( $temp_file );
		}
		return array(
			'status' => 'error',
			'error'  => "download failed: HTTP {$download_status} from content.url",
			'job_id' => $job_id,
		);
	}
	// A 2xx with an empty body (204 No Content, or any response that streams
	// zero bytes) passes the status check above but leaves a 0-byte file —
	// that still satisfies the cache check's file_exists() on every future
	// run, so a genuinely broken render would silently keep "succeeding".
	if ( ! file_exists( $temp_file ) || 0 === filesize( $temp_file ) ) {
		if ( file_exists( $temp_file ) ) {
			wp_delete_file( $temp_file );
		}
		return array(
			'status' => 'error',
			'error'  => "download failed: HTTP {$download_status} but the saved file is empty",
			'job_id' => $job_id,
		);
	}
	// Validation passed — atomically swap the temp file into place. rename()
	// within the same directory replaces $dest_file in one filesystem
	// operation, so a concurrent request never sees a partial/missing file.
	if ( ! @rename( $temp_file, $dest_file ) ) {
		if ( file_exists( $temp_file ) ) {
			wp_delete_file( $temp_file );
		}
		return array(
			'status' => 'error',
			'error'  => 'downloaded clip validated but could not be moved into place',
			'job_id' => $job_id,
		);
	}

	$manifest[ $segment_key ] = array(
		'fingerprint' => $sha256,
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
				WP_CLI::error( 'Prerender failed: ' . $results->get_error_message() );
				return;
			}

			$rendered = 0;
			$cached   = 0;
			$pending  = 0;
			$errors   = 0;
			foreach ( $results as $segment_key => $result ) {
				if ( 'rendered' === $result['status'] ) {
					++$rendered;
				} elseif ( 'cached' === $result['status'] ) {
					++$cached;
				} elseif ( 'pending' === $result['status'] ) {
					// Not a failure — the job is still rendering on Gary's side
					// (possibly waiting out a capacity window, retried
					// automatically under the same job id for up to 2 hours) and
					// this run's poll budget just ran out first. Re-running the
					// command later will idempotently pick the same job back up.
					++$pending;
					WP_CLI::warning( "{$segment_key}: {$result['error']} — re-run later to pick it back up" );
				} else {
					++$errors;
					WP_CLI::warning( "{$segment_key}: {$result['error']}" );
				}
			}

			$summary = "Rendered: {$rendered}, already cached: {$cached}, still pending: {$pending}, errors: {$errors}.";
			if ( $errors > 0 ) {
				WP_CLI::error( "Done with errors. {$summary}" );
				return;
			}
			if ( $pending > 0 ) {
				WP_CLI::warning( "Done, but some segments are still rendering. {$summary}" );
				return;
			}
			WP_CLI::success( "Done. {$summary}" );
		}
	}

	WP_CLI::add_command( 'aicoach', 'IHQ_Aicoach_Prerender_Command' );
}

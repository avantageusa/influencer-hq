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
// "time_selection". Gary's old single combined "competitions" segment (no
// single clip fit this page's three separate world/community/private
// panels) is now 'superseded' — confirmed live against GET
// /coach/v1/registration/scripts — replaced by three separate approved
// segments ('world', 'community', 'private') that match this page's panels
// one for one, text verbatim-identical to COMPETITION_SCREENS in
// js/aicoach-coach-flow.js.
function ihq_aicoach_prerender_panel_map() {
	return array(
		'believe-1'             => 'we_believe_1',
		'believe-2'             => 'we_believe_2',
		'home'                  => 'time_selection',
		'equity-magic'          => 'magic_johnson',
		'equity-alix'           => 'alix_earle',
		'equity-bts'            => 'bts',
		'competition-world'     => 'world',
		'competition-community' => 'community',
		'competition-private'   => 'private',
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
 * The option that holds the avatar version visitors are served. Empty is the original set
 * (the clips rendered before there were versions).
 */
const IHQ_AICOACH_AVATAR_VERSION_OPTION = 'ihq_aicoach_avatar_version';

/** How many 5 second polls one render waits for before it reports "pending" and lets the next run resume it. */
const IHQ_AICOACH_PRERENDER_MAX_POLLS = 18;

/** The shorter wait of a render step run from a web request, which a gateway cuts off at about a minute. */
const IHQ_AICOACH_PRERENDER_STEP_MAX_POLLS = 5;

/**
 * Whether a string can be an avatar version: "v" and one to three digits. A version ends
 * up in file names and in an idempotency key, so nothing else is accepted.
 *
 * @param mixed $version
 * @return bool
 */
function ihq_aicoach_avatar_version_is_valid( $version ) {
	return is_string( $version ) && 1 === preg_match( '/^v[0-9]{1,3}\z/', $version );
}

/**
 * The avatar version visitors are served: the option, or the original set ('') when it is
 * empty, missing or not a valid version.
 *
 * @return string
 */
function ihq_aicoach_avatar_active_version() {
	$version = get_option( IHQ_AICOACH_AVATAR_VERSION_OPTION, '' );
	return ihq_aicoach_avatar_version_is_valid( $version ) ? $version : '';
}

/**
 * Path to the manifest mapping segment key -> { fingerprint, file, rendered_at }. The
 * original set (version '') keeps manifest.json; a version has its own file, so rendering
 * it changes nothing that visitors are served.
 *
 * @param string $version An avatar version, or '' for the original set.
 * @return string
 */
function ihq_aicoach_prerender_manifest_path( $version = '' ) {
	$name = '' === $version ? 'manifest.json' : "manifest-{$version}.json";
	return ihq_aicoach_prerender_dir() . '/' . $name;
}

/**
 * @param string $version An avatar version, or '' for the original set.
 * @return array<string,array{fingerprint:string,file:string,rendered_at:string}>
 */
function ihq_aicoach_prerender_load_manifest( $version = '' ) {
	$path = ihq_aicoach_prerender_manifest_path( $version );
	if ( ! file_exists( $path ) ) {
		return array();
	}
	$decoded = json_decode( (string) file_get_contents( $path ), true );
	return is_array( $decoded ) ? $decoded : array();
}

/**
 * @param array  $manifest
 * @param string $version An avatar version, or '' for the original set.
 */
function ihq_aicoach_prerender_save_manifest( array $manifest, $version = '' ) {
	file_put_contents( ihq_aicoach_prerender_manifest_path( $version ), wp_json_encode( $manifest, JSON_PRETTY_PRINT ) );
}

/**
 * PO-3062's original manifest was keyed by segment_key alone (English only).
 * FR-16 (single avatar/voice, one clip per segment PER LANGUAGE — confirmed
 * with product 2026-09-25: not a different avatar per language, just this
 * same one speaking each language's translated script) needs a second axis.
 * Rather than reshape the manifest into a nested structure (touching every
 * existing reader) or renaming already-rendered/deployed English entries
 * (forcing a pointless re-render), English keeps using the bare segment_key
 * exactly as before; only non-English languages get a suffixed key/filename.
 * A manifest written before this change is still valid — every entry in it
 * is implicitly 'en'.
 *
 * @param string $segment_key
 * @param string $language BCP-47-ish code, e.g. 'ja'. 'en' is the default/
 *                          original language and never suffixed.
 * @return string
 */
function ihq_aicoach_prerender_manifest_key( $segment_key, $language = 'en' ) {
	return 'en' === $language ? $segment_key : "{$segment_key}__{$language}";
}

/**
 * @param string $segment_key
 * @param string $language
 * @param string $version     An avatar version, or '' for the original set, whose names
 *                            are the ones already rendered and deployed.
 * @return string Filename only, no directory.
 */
function ihq_aicoach_prerender_file_name( $segment_key, $language = 'en', $version = '' ) {
	$base   = 'en' === $language ? $segment_key : "{$segment_key}-{$language}";
	$suffix = '' === $version ? '' : ".{$version}";
	return "{$base}{$suffix}.mp4";
}

/**
 * The Idempotency-Key of one render job. Gary matches a job on this key alone, so a
 * different key always starts a new render (his answer, 2026-10-11): the original set
 * keeps the keys it was rendered with, and a version adds its own ending.
 *
 * @param string $segment_key
 * @param string $language
 * @param string $sha256      The hash of the text being spoken.
 * @param string $version     An avatar version, or '' for the original set.
 * @return string
 */
function ihq_aicoach_prerender_idempotency_key( $segment_key, $language, $sha256, $version = '' ) {
	$key = 'en' === $language
		? 'ihq-seg-' . $segment_key . '-' . substr( $sha256, 0, 16 )
		: 'ihq-seg-' . $segment_key . '-' . $language . '-' . substr( $sha256, 0, 16 );
	return '' === $version ? $key : "{$key}-{$version}";
}

/**
 * Segment key -> language -> public clip URL, for whatever has actually been
 * rendered and downloaded so far. A segment/language combination with
 * nothing rendered simply has no entry — the caller falls back (English clip,
 * then the static caption), same "missing just means not ready yet" as
 * before this feature existed.
 *
 * Every URL carries a ?v={fingerprint} cache-buster. The filename itself
 * never changes per segment+language, and re-rendering overwrites it in
 * place — confirmed live (2026-09-23) that Cloudflare's edge cache (public,
 * max-age=31536000 on this upload path) happily keeps serving the
 * pre-re-render bytes under that unchanged URL for a full year otherwise, a
 * new script or a new avatar/voice from Gary is invisible to visitors no
 * matter how successfully it re-rendered server-side. The fingerprint
 * already changes on exactly those occasions (it's what decides whether a
 * re-render happens at all), so reusing it here makes each real content
 * change a genuinely new URL instead of fighting the CDN's cache lifetime.
 *
 * @param string|null $version An avatar version, '' for the original set, or null for
 *                             the one visitors are served.
 * @return array<string,array<string,string>>
 */
function ihq_aicoach_prerender_get_urls( $version = null ) {
	// Only the version visitors are served unless a caller asks about another one.
	$version  = null === $version ? ihq_aicoach_avatar_active_version() : $version;
	$manifest = ihq_aicoach_prerender_load_manifest( $version );
	$urls     = array();
	foreach ( $manifest as $manifest_key => $entry ) {
		if ( empty( $entry['file'] ) || ! file_exists( ihq_aicoach_prerender_dir() . '/' . $entry['file'] ) ) {
			continue;
		}
		// Manifest keys with no "__" are English (see
		// ihq_aicoach_prerender_manifest_key()); anything else splits into
		// segment_key + language on the LAST "__", so a segment_key that
		// itself never contains "__" (true of every real Gary segment key)
		// round-trips unambiguously.
		if ( false === strpos( $manifest_key, '__' ) ) {
			$segment_key = $manifest_key;
			$language    = 'en';
		} else {
			list( $segment_key, $language ) = explode( '__', $manifest_key, 2 );
		}

		$url = ihq_aicoach_prerender_url_base() . '/' . rawurlencode( $entry['file'] );
		if ( ! empty( $entry['fingerprint'] ) ) {
			$url = add_query_arg( 'v', substr( $entry['fingerprint'], 0, 12 ), $url );
		}
		if ( ! isset( $urls[ $segment_key ] ) ) {
			$urls[ $segment_key ] = array();
		}
		$urls[ $segment_key ][ $language ] = $url;
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
 * @param string        $text        Exact approved script text, in $language.
 * @param string        $sha256      For English, Gary's own sha256 for this exact
 *                                    text. For any other language there's no Gary
 *                                    manifest to carry one, so the caller hashes the
 *                                    translated text itself — either way this is
 *                                    used for both the cache check and the Coach
 *                                    API Idempotency-Key.
 * @param string        $language    BCP-47-ish code, e.g. 'ja'. Defaults to 'en'.
 * @param callable|null $log         Optional fn(string $line) for CLI progress output.
 * @param string        $version     An avatar version to render into (its own manifest, file
 *                                    names and idempotency key), or '' for the original set.
 * @param int           $max_polls   How many 5 second polls to wait for the job before
 *                                    reporting 'pending'.
 * @return array{status:string, url?:string, error?:string, job_id?:string}
 */
function ihq_aicoach_prerender_segment( $segment_key, $text, $sha256, $language = 'en', $log = null, $version = '', $max_polls = IHQ_AICOACH_PRERENDER_MAX_POLLS ) {
	$log = $log ?: function ( $line ) {};

	$manifest_key = ihq_aicoach_prerender_manifest_key( $segment_key, $language );
	$file_name    = ihq_aicoach_prerender_file_name( $segment_key, $language, $version );

	$manifest = ihq_aicoach_prerender_load_manifest( $version );
	$existing = $manifest[ $manifest_key ] ?? null;
	if ( $existing && ( $existing['fingerprint'] ?? '' ) === $sha256 && file_exists( ihq_aicoach_prerender_dir() . '/' . $existing['file'] ) ) {
		$log( "  {$manifest_key}: already rendered for this script, skipping" );
		return array( 'status' => 'cached' );
	}

	// Human-readable + deterministic, matching Gary's own example format
	// (email, 2026-09-23: "Idempotency-Key: ihq-seg-magic-johnson-v1"). Stable
	// per exact script text, so re-running this after Gary approves new copy
	// (or a translation changes) only touches segment+language combinations
	// whose sha256 actually changed. 'en' keeps the original key shape
	// unchanged (no language suffix) so already-rendered English clips are
	// never orphaned/re-rendered by this change.
	$idempotency_key = ihq_aicoach_prerender_idempotency_key( $segment_key, $language, $sha256, $version );

	$log( "  {$manifest_key}: creating (or resuming) render job..." );
	$job = ihq_aicoach_coach_create_video( $text, $idempotency_key, $manifest_key );
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
	$max_attempts = max( 0, (int) $max_polls ); // 18 x 5s = 90s by default.
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
		$log( "  {$manifest_key}: status={$status_now}" . ( $waiting_reason ? " ({$waiting_reason})" : '' ) . " ({$attempts}/{$max_attempts})" );
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
		$log( "  {$manifest_key}: still {$status_now} after {$max_attempts} polls — job continues server-side, re-run later to pick it back up" );
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

	$log( "  {$manifest_key}: downloading..." );
	$dest_file = ihq_aicoach_prerender_dir() . "/{$file_name}";
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

	$manifest[ $manifest_key ] = array(
		'fingerprint' => $sha256,
		'file'        => $file_name,
		'rendered_at' => gmdate( 'c' ),
	);
	ihq_aicoach_prerender_save_manifest( $manifest, $version );

	return array(
		'status' => 'rendered',
		'url'    => ihq_aicoach_prerender_url_base() . "/{$file_name}",
	);
}

/**
 * What is to be rendered: every approved segment this page actually uses (per
 * ihq_aicoach_prerender_panel_map()) in English, and in every other language that has a
 * translated script of ours. The same list drives the WP-CLI command and the step-by-step
 * run from a web page, so they cannot disagree about what the set is.
 *
 * @param callable|null $log Optional fn(string $line) for progress output (what is skipped).
 * @return array<int,array{manifest_key:string,segment_key:string,language:string,text:string,sha256:string}>|WP_Error
 *         The items in the order they are rendered, or a WP_Error if the scripts fetch itself
 *         failed, which is not the same as an empty list (the fetch worked and nothing is
 *         wanted).
 */
function ihq_aicoach_prerender_work_list( $log = null ) {
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

	$segments     = $scripts_result['body']['segments'] ?? array();
	$wanted       = array_unique( array_values( ihq_aicoach_prerender_panel_map() ) );
	$translations = function_exists( 'ihq_aicoach_segment_translations' )
		? ihq_aicoach_segment_translations()
		: array();
	$items        = array();

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
		$items[] = array(
			'manifest_key' => ihq_aicoach_prerender_manifest_key( $segment_key, 'en' ),
			'segment_key'  => $segment_key,
			'language'     => 'en',
			'text'         => $segment['text'],
			'sha256'       => $segment['sha256'],
		);

		// FR-16 (single avatar/voice, confirmed with product 2026-09-25 — not a
		// different avatar per language) — render the same approved segment in
		// whatever other languages we have a translated script for.
		// inc/aicoach-segment-translations.php is OUR OWN content, not Gary's:
		// his registration/scripts manifest only ever carries reviewed English
		// (confirmed live, registration_languages: ["en"]), so there's no sha256
		// to borrow for non-English text — hash the translated text itself,
		// which still gives the same "only re-render what actually changed"
		// behavior once a translation gets corrected.
		foreach ( $translations[ $segment_key ] ?? array() as $language => $translated_text ) {
			if ( '' === trim( (string) $translated_text ) ) {
				continue;
			}
			$items[] = array(
				'manifest_key' => ihq_aicoach_prerender_manifest_key( $segment_key, $language ),
				'segment_key'  => $segment_key,
				'language'     => $language,
				'text'         => $translated_text,
				'sha256'       => hash( 'sha256', $translated_text ),
			);
		}
	}

	return $items;
}

/**
 * Render every approved segment this page actually uses (the work list above). Called by the
 * WP-CLI command below.
 *
 * @param callable|null $log     Optional fn(string $line) for progress output.
 * @param string        $version An avatar version to render into, or '' for the original set.
 * @return array<string,array>|WP_Error segment_key => result (same shape as
 *                              ihq_aicoach_prerender_segment()'s return), or
 *                              a WP_Error if the scripts fetch itself failed
 *                              — distinct from an empty array, which means
 *                              the fetch succeeded and nothing needed doing.
 */
function ihq_aicoach_prerender_all( $log = null, $version = '' ) {
	$log   = $log ?: function ( $line ) {};
	$items = ihq_aicoach_prerender_work_list( $log );
	if ( is_wp_error( $items ) ) {
		return $items;
	}

	$results = array();
	foreach ( $items as $item ) {
		$log( "{$item['segment_key']}: rendering ({$item['language']})..." );
		$results[ $item['manifest_key'] ] = ihq_aicoach_prerender_segment(
			$item['segment_key'],
			$item['text'],
			$item['sha256'],
			$item['language'],
			$log,
			$version
		);
	}
	return $results;
}

/**
 * One step of rendering a version from a web page, for a server with no WP-CLI: renders the
 * item at an index of the work list and says how it went, so the page can walk through the
 * list one request at a time. A request is cut off at about a minute, so a step waits only
 * a few polls; an item still rendering is reported as 'pending' and the page asks for the
 * same index again (Gary's job is replayed, not started again, because the key is the same).
 *
 * @param string $version An avatar version such as "v2". The original set is refused: this
 *                        is for rendering a new version.
 * @param int    $index   Position in the work list, from 0.
 * @param int    $max_polls How many 5 second polls the step waits for.
 * @return array|WP_Error {done:bool,index:int,total:int,key?:string,status?:string,error?:string}
 */
function ihq_aicoach_avatar_render_step( $version, $index, $max_polls = IHQ_AICOACH_PRERENDER_STEP_MAX_POLLS ) {
	if ( ! ihq_aicoach_avatar_version_is_valid( $version ) ) {
		return new WP_Error( 'avatar_version_invalid', 'An avatar version is "v" and one to three digits, for example v2.' );
	}
	$items = ihq_aicoach_prerender_work_list();
	if ( is_wp_error( $items ) ) {
		return $items;
	}
	$total = count( $items );
	$index = (int) $index;
	if ( $index < 0 || $index >= $total ) {
		return array( 'done' => true, 'index' => $index, 'total' => $total );
	}

	$item   = $items[ $index ];
	$result = ihq_aicoach_prerender_segment( $item['segment_key'], $item['text'], $item['sha256'], $item['language'], null, $version, $max_polls );
	return array(
		'done'   => false,
		'index'  => $index,
		'total'  => $total,
		'key'    => $item['manifest_key'],
		'status' => $result['status'],
		'error'  => $result['error'] ?? '',
	);
}

/**
 * Whether a clip of a manifest is really there to be served.
 *
 * @param array $entry A manifest entry.
 * @return bool
 */
function ihq_aicoach_prerender_entry_is_servable( $entry ) {
	return is_array( $entry ) && ! empty( $entry['file'] ) && file_exists( ihq_aicoach_prerender_dir() . '/' . $entry['file'] );
}

/**
 * The clips a version still lacks compared with what visitors are served now: every clip
 * the served set can play must exist, as a file, in the version too. The original set is
 * a version for this purpose ('').
 *
 * @param string $version An avatar version, or '' for the original set.
 * @return string[] Manifest keys (segment, or segment__language) the version lacks.
 */
function ihq_aicoach_avatar_version_missing( $version ) {
	$served  = ihq_aicoach_prerender_load_manifest( ihq_aicoach_avatar_active_version() );
	$target  = ihq_aicoach_prerender_load_manifest( $version );
	$missing = array();
	foreach ( $served as $manifest_key => $entry ) {
		if ( ! ihq_aicoach_prerender_entry_is_servable( $entry ) ) {
			continue;
		}
		if ( ! ihq_aicoach_prerender_entry_is_servable( $target[ $manifest_key ] ?? null ) ) {
			$missing[] = $manifest_key;
		}
	}
	sort( $missing );
	return $missing;
}

/**
 * Makes a version the one visitors are served, but only when it is complete: it changes
 * nothing, and lists what is missing, when a clip that is served now has no clip in the
 * version. This is the one step that switches every clip to the new face together; the
 * way back is the same call with the original set ('').
 *
 * @param string $version An avatar version such as "v2", or '' for the original set.
 * @return array{version:string,changed:bool}|WP_Error
 */
function ihq_aicoach_avatar_activate( $version ) {
	if ( '' !== $version && ! ihq_aicoach_avatar_version_is_valid( $version ) ) {
		return new WP_Error( 'avatar_version_invalid', 'An avatar version is "v" and one to three digits, for example v2.' );
	}
	if ( ihq_aicoach_avatar_active_version() === $version ) {
		return array( 'version' => $version, 'changed' => false );
	}
	if ( '' !== $version && array() === ihq_aicoach_prerender_load_manifest( $version ) ) {
		return new WP_Error( 'avatar_version_empty', "Nothing has been rendered for {$version} yet." );
	}
	$missing = ihq_aicoach_avatar_version_missing( $version );
	if ( array() !== $missing ) {
		return new WP_Error(
			'avatar_version_incomplete',
			'Not switched. Clips missing from the version: ' . implode( ', ', $missing ),
			array( 'missing' => $missing )
		);
	}
	update_option( IHQ_AICOACH_AVATAR_VERSION_OPTION, $version );
	return array( 'version' => $version, 'changed' => true );
}

/**
 * The versions that have a manifest, with how many clips each can serve and how many it
 * lacks compared with what is served now.
 *
 * @return array<int,array{version:string,clips:int,missing:int,active:bool}> The original
 *         set first, then the versions in order.
 */
function ihq_aicoach_avatar_status() {
	$versions = array( '' );
	foreach ( (array) glob( ihq_aicoach_prerender_dir() . '/manifest-v*.json' ) as $path ) {
		$version = substr( basename( $path, '.json' ), strlen( 'manifest-' ) );
		if ( ihq_aicoach_avatar_version_is_valid( $version ) ) {
			$versions[] = $version;
		}
	}
	natsort( $versions );

	$active = ihq_aicoach_avatar_active_version();
	$status = array();
	foreach ( $versions as $version ) {
		$clips = 0;
		foreach ( ihq_aicoach_prerender_load_manifest( $version ) as $entry ) {
			if ( ihq_aicoach_prerender_entry_is_servable( $entry ) ) {
				++$clips;
			}
		}
		$status[] = array(
			'version' => $version,
			'clips'   => $clips,
			'missing' => count( ihq_aicoach_avatar_version_missing( $version ) ),
			'active'  => $version === $active,
		);
	}
	return $status;
}

/**
 * The file name of the still portrait (images/aicoach/) for an avatar version: the one made
 * for the version when it exists, otherwise the original. It changes with the clips, in the
 * same step, so the portrait shown while the live avatar connects is never another face.
 *
 * @param string|null $version An avatar version, '' for the original, or null for the one
 *                             visitors are served.
 * @return string
 */
function ihq_aicoach_portrait_file( $version = null ) {
	$version = null === $version ? ihq_aicoach_avatar_active_version() : $version;
	if ( '' === $version ) {
		return 'coach-portrait.webp';
	}
	$versioned = "coach-portrait-{$version}.webp";
	return file_exists( get_template_directory() . '/images/aicoach/' . $versioned ) ? $versioned : 'coach-portrait.webp';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Pre-render AI Coach avatar clips for approved script segments.
	 */
	class IHQ_Aicoach_Prerender_Command {
		/**
		 * Render every approved segment that isn't already cached for its
		 * current sha256, into an avatar version (the one visitors are served
		 * unless one is given). A new version renders into its own files and
		 * changes nothing visitors see until `wp aicoach activate-avatar`.
		 *
		 * ## OPTIONS
		 *
		 * [--avatar-version=<version>]
		 * : An avatar version such as v2, or "original" for the clips rendered
		 * before there were versions. Defaults to the version being served.
		 *
		 * ## EXAMPLES
		 *
		 *     wp aicoach prerender
		 *     wp aicoach prerender --avatar-version=v2
		 *
		 * @when after_wp_load
		 */
		public function prerender( $args, $assoc_args ) {
			$version = self::version_from_argument( $assoc_args['avatar-version'] ?? null, true );
			$results = ihq_aicoach_prerender_all(
				function ( $line ) {
					WP_CLI::log( $line );
				},
				$version
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

		/**
		 * Serve an avatar version to visitors, only when it is complete: every
		 * clip served now must exist in it. Also switches the still portrait.
		 * "original" goes back to the clips rendered before there were versions.
		 *
		 * ## OPTIONS
		 *
		 * <version>
		 * : An avatar version such as v2, or "original".
		 *
		 * ## EXAMPLES
		 *
		 *     wp aicoach activate-avatar v2
		 *     wp aicoach activate-avatar original
		 *
		 * @subcommand activate-avatar
		 * @when after_wp_load
		 */
		public function activate_avatar( $args ) {
			$version = self::version_from_argument( $args[0] ?? null, false );
			$result  = ihq_aicoach_avatar_activate( $version );
			if ( is_wp_error( $result ) ) {
				WP_CLI::error( $result->get_error_message() );
				return;
			}
			$name = '' === $version ? 'original' : $version;
			if ( ! $result['changed'] ) {
				WP_CLI::success( "{$name} is already the version served." );
				return;
			}
			WP_CLI::success( "Visitors are now served {$name}. Purge the cache if the page is cached." );
		}

		/**
		 * Show the avatar versions: which one is served, how many clips each has
		 * and how many it lacks compared with what is served now.
		 *
		 * ## EXAMPLES
		 *
		 *     wp aicoach avatar-status
		 *
		 * @subcommand avatar-status
		 * @when after_wp_load
		 */
		public function avatar_status() {
			foreach ( ihq_aicoach_avatar_status() as $row ) {
				$name = '' === $row['version'] ? 'original' : $row['version'];
				WP_CLI::log( sprintf( '%s%-9s clips: %d, missing: %d', $row['active'] ? '* ' : '  ', $name, $row['clips'], $row['missing'] ) );
			}
			WP_CLI::log( '* is the version visitors are served.' );
		}

		/**
		 * @param string|null $argument         What was typed, or null when nothing was.
		 * @param bool        $default_to_active Whether nothing typed means the version served.
		 * @return string A valid version, or '' for the original set.
		 */
		private static function version_from_argument( $argument, $default_to_active ) {
			if ( null === $argument ) {
				if ( $default_to_active ) {
					return ihq_aicoach_avatar_active_version();
				}
				WP_CLI::error( 'Give a version such as v2, or "original".' );
			}
			if ( 'original' === $argument ) {
				return '';
			}
			if ( ! ihq_aicoach_avatar_version_is_valid( $argument ) ) {
				WP_CLI::error( 'An avatar version is "v" and one to three digits, for example v2, or "original".' );
			}
			return $argument;
		}
	}

	WP_CLI::add_command( 'aicoach', 'IHQ_Aicoach_Prerender_Command' );
}

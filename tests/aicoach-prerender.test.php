<?php
/**
 * Unit test for the avatar versions of inc/aicoach-prerender.php (PO-3062): the names,
 * the manifests and the idempotency keys of a version, which version visitors are served,
 * the checked switch from one version to another and the still portrait that follows it.
 * No WordPress bootstrap: the Coach API calls and the handful of WP functions the file
 * touches are stubbed, and the clips are written to a throwaway folder.
 *
 *     php tests/aicoach-prerender.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

$GLOBALS['tmp']          = sys_get_temp_dir() . '/ihq-prerender-test-' . getmypid();
$GLOBALS['wp_options']   = array();
$GLOBALS['api_calls']    = array();
$GLOBALS['scripts_body'] = array( 'segments' => array() );
$GLOBALS['translations'] = array();
$GLOBALS['scripts_status']        = 200;
$GLOBALS['scripts_transport_error'] = false;
$GLOBALS['post_job_status']       = 'ready'; // what a new job says: 'ready', or 'queued' to be polled
mkdir( $GLOBALS['tmp'] . '/uploads', 0777, true );
mkdir( $GLOBALS['tmp'] . '/theme/images/aicoach', 0777, true );

class WP_Error {
	private $code;
	private $message;
	private $data;
	public function __construct( $code = '', $message = '', $data = null ) { $this->code = $code; $this->message = $message; $this->data = $data; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
	public function get_error_data() { return $this->data; }
}
function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
function wp_upload_dir() { return array( 'basedir' => $GLOBALS['tmp'] . '/uploads', 'baseurl' => 'https://example.test/wp-content/uploads' ); }
function wp_mkdir_p( $dir ) { return is_dir( $dir ) || mkdir( $dir, 0777, true ); }
function trailingslashit( $s ) { return rtrim( $s, '/\\' ) . '/'; }
function wp_json_encode( $v, $flags = 0 ) { return json_encode( $v, $flags ); }
function add_query_arg( $key, $value, $url ) { return $url . '?' . rawurlencode( $key ) . '=' . rawurlencode( $value ); }
function wp_delete_file( $f ) { @unlink( $f ); }
function get_option( $key, $default = false ) { return $GLOBALS['wp_options'][ $key ] ?? $default; }
function update_option( $key, $value, $autoload = true ) { $GLOBALS['wp_options'][ $key ] = $value; return true; }
function get_template_directory() { return $GLOBALS['tmp'] . '/theme'; }
function ihq_aicoach_segment_translations() { return $GLOBALS['translations']; }

// The Coach API: a POST starts a job that is already ready, a GET of the scripts answers
// what the test set up, and a download writes a small file.
function ihq_coach_request( $method, $path, $body = null, $headers = array() ) {
	$GLOBALS['api_calls'][] = array( 'method' => $method, 'path' => $path, 'body' => $body, 'headers' => $headers );
	if ( 'GET' === $method && '/coach/v1/registration/scripts' === $path ) {
		if ( $GLOBALS['scripts_transport_error'] ) {
			return new WP_Error( 'http_request_failed', 'cURL error 6: Could not resolve host' );
		}
		return array( 'status' => $GLOBALS['scripts_status'], 'body' => $GLOBALS['scripts_body'] );
	}
	if ( 'POST' === $method && '/coach/v1/videos' === $path ) {
		return array( 'status' => 202, 'body' => array( 'id' => 'job-' . count( $GLOBALS['api_calls'] ), 'status' => $GLOBALS['post_job_status'], 'content' => array( 'url' => '/coach/v1/videos/x/content' ) ) );
	}
	if ( 'GET' === $method && 0 === strpos( $path, '/coach/v1/videos/' ) ) {
		// A poll: the job is ready by the first one.
		return array( 'status' => 200, 'body' => array( 'status' => 'ready', 'content' => array( 'url' => '/coach/v1/videos/x/content' ) ) );
	}
	return array( 'status' => 404, 'body' => array() );
}
function ihq_coach_download( $path, $dest ) {
	file_put_contents( $dest, 'MP4' );
	return array( 'status' => 200 );
}

require __DIR__ . '/../inc/aicoach-prerender.php';

$dir = $GLOBALS['tmp'] . '/uploads/aicoach-videos';
function posted() { return array_values( array_filter( $GLOBALS['api_calls'], function ( $c ) { return 'POST' === $c['method']; } ) ); }
function reset_calls() { $GLOBALS['api_calls'] = array(); }
function put_clip( $file ) { file_put_contents( $GLOBALS['tmp'] . '/uploads/aicoach-videos/' . $file, 'MP4' ); }
function put_manifest( $version, array $entries ) { ihq_aicoach_prerender_save_manifest( $entries, $version ); }
function entry( $file, $fp = 'fp' ) { return array( 'fingerprint' => $fp, 'file' => $file, 'rendered_at' => '2026-10-11T00:00:00+00:00' ); }

// --- what a version can be ---

foreach ( array( 'v1', 'v2', 'v12', 'v123' ) as $good ) {
	check( "{$good} is an avatar version", ihq_aicoach_avatar_version_is_valid( $good ) );
}
foreach ( array( '', 'v', 'v1234', 'V2', 'v2a', 'v-1', '2', '../v2', 'v2/x', 'v2.mp4', ' v2', 'v2 ', "v2\n", null, 5, array( 'v2' ) ) as $bad ) {
	check( 'not an avatar version: ' . json_encode( $bad ), ! ihq_aicoach_avatar_version_is_valid( $bad ) );
}

// --- the version served ---

check( 'with no option the original set is served', '' === ihq_aicoach_avatar_active_version() );
update_option( IHQ_AICOACH_AVATAR_VERSION_OPTION, 'v2' );
check( 'the option names the version served', 'v2' === ihq_aicoach_avatar_active_version() );
update_option( IHQ_AICOACH_AVATAR_VERSION_OPTION, '../etc' );
check( 'an option that is not a version means the original set', '' === ihq_aicoach_avatar_active_version() );
update_option( IHQ_AICOACH_AVATAR_VERSION_OPTION, array( 'v2' ) );
check( 'an option that is not text means the original set', '' === ihq_aicoach_avatar_active_version() );
update_option( IHQ_AICOACH_AVATAR_VERSION_OPTION, '' );

// --- names, manifests and keys ---

check( 'the original manifest keeps its name', $dir . '/manifest.json' === ihq_aicoach_prerender_manifest_path() && $dir . '/manifest.json' === ihq_aicoach_prerender_manifest_path( '' ) );
check( 'a version has a manifest of its own', $dir . '/manifest-v2.json' === ihq_aicoach_prerender_manifest_path( 'v2' ) );
check( 'English file name, original', 'bts.mp4' === ihq_aicoach_prerender_file_name( 'bts' ) && 'bts.mp4' === ihq_aicoach_prerender_file_name( 'bts', 'en', '' ) );
check( 'other language file name, original', 'bts-ja.mp4' === ihq_aicoach_prerender_file_name( 'bts', 'ja' ) && 'bts-ja.mp4' === ihq_aicoach_prerender_file_name( 'bts', 'ja', '' ) );
check( 'English file name, version', 'bts.v2.mp4' === ihq_aicoach_prerender_file_name( 'bts', 'en', 'v2' ) );
check( 'other language file name, version', 'bts-ja.v2.mp4' === ihq_aicoach_prerender_file_name( 'bts', 'ja', 'v2' ) );

$sha = 'abcdef0123456789abcdef0123456789';
check( 'the original English key is the one already rendered with', 'ihq-seg-bts-abcdef0123456789' === ihq_aicoach_prerender_idempotency_key( 'bts', 'en', $sha ) );
check( 'the original key of another language is the one already rendered with', 'ihq-seg-bts-ja-abcdef0123456789' === ihq_aicoach_prerender_idempotency_key( 'bts', 'ja', $sha, '' ) );
check( 'a version ends the English key with itself', 'ihq-seg-bts-abcdef0123456789-v2' === ihq_aicoach_prerender_idempotency_key( 'bts', 'en', $sha, 'v2' ) );
check( 'a version ends the key of another language with itself', 'ihq-seg-bts-ja-abcdef0123456789-v2' === ihq_aicoach_prerender_idempotency_key( 'bts', 'ja', $sha, 'v2' ) );
check( 'two versions never share a key', ihq_aicoach_prerender_idempotency_key( 'bts', 'en', $sha, 'v2' ) !== ihq_aicoach_prerender_idempotency_key( 'bts', 'en', $sha, 'v3' ) && ihq_aicoach_prerender_idempotency_key( 'bts', 'en', $sha, 'v2' ) !== ihq_aicoach_prerender_idempotency_key( 'bts', 'en', $sha ) );

// --- rendering into a version ---

reset_calls();
$result = ihq_aicoach_prerender_segment( 'bts', 'Some text', 'aaaa1111aaaa1111aaaa1111', 'en' );
check( 'rendering the original set is reported as rendered', 'rendered' === $result['status'] );
check( 'it writes the clip under its original name', file_exists( $dir . '/bts.mp4' ) && ! file_exists( $dir . '/bts.v2.mp4' ) );
check( 'it records it in the original manifest only', array( 'bts' ) === array_keys( ihq_aicoach_prerender_load_manifest() ) && ! file_exists( $dir . '/manifest-v2.json' ) );
check( 'it sent the original key to the Coach API', 'ihq-seg-bts-aaaa1111aaaa1111' === posted()[0]['headers']['Idempotency-Key'] );

reset_calls();
$result = ihq_aicoach_prerender_segment( 'bts', 'Some text', 'aaaa1111aaaa1111aaaa1111', 'en' );
check( 'rendering the same text again is cached and calls nothing', 'cached' === $result['status'] && array() === $GLOBALS['api_calls'] );

reset_calls();
$original_manifest_before = file_get_contents( $dir . '/manifest.json' );
$result = ihq_aicoach_prerender_segment( 'bts', 'Some text', 'aaaa1111aaaa1111aaaa1111', 'en', null, 'v2' );
check( 'the same text is rendered again into a new version (the original clip does not count)', 'rendered' === $result['status'] && 1 === count( posted() ) );
check( 'the Coach API got the key of the version, so it makes a new job', 'ihq-seg-bts-aaaa1111aaaa1111-v2' === posted()[0]['headers']['Idempotency-Key'] );
check( 'the clip is a new file and the original is untouched', file_exists( $dir . '/bts.v2.mp4' ) && file_exists( $dir . '/bts.mp4' ) );
check( 'the version has a manifest of its own', array( 'bts' ) === array_keys( ihq_aicoach_prerender_load_manifest( 'v2' ) ) && 'bts.v2.mp4' === ihq_aicoach_prerender_load_manifest( 'v2' )['bts']['file'] );
check( 'the original manifest did not change', $original_manifest_before === file_get_contents( $dir . '/manifest.json' ) );

reset_calls();
$result = ihq_aicoach_prerender_segment( 'bts', 'Some text', 'aaaa1111aaaa1111aaaa1111', 'en', null, 'v2' );
check( 'the version caches its own clip', 'cached' === $result['status'] && array() === $GLOBALS['api_calls'] );

reset_calls();
$result = ihq_aicoach_prerender_segment( 'bts', 'Changed text', 'bbbb2222bbbb2222bbbb2222', 'ja', null, 'v2' );
check( 'another language into a version uses the language and version in the name and key', 'rendered' === $result['status'] && file_exists( $dir . '/bts-ja.v2.mp4' ) && 'ihq-seg-bts-ja-bbbb2222bbbb2222-v2' === posted()[0]['headers']['Idempotency-Key'] && isset( ihq_aicoach_prerender_load_manifest( 'v2' )['bts__ja'] ) );

// --- what visitors are served ---

check( 'with no option visitors are served the original set', array( 'bts' => array( 'en' => 'https://example.test/wp-content/uploads/aicoach-videos/bts.mp4?v=aaaa1111aaaa' ) ) === ihq_aicoach_prerender_get_urls() );
check( 'asking for a version says what it holds', array(
	'bts' => array(
		'en' => 'https://example.test/wp-content/uploads/aicoach-videos/bts.v2.mp4?v=aaaa1111aaaa',
		'ja' => 'https://example.test/wp-content/uploads/aicoach-videos/bts-ja.v2.mp4?v=bbbb2222bbbb',
	),
) === ihq_aicoach_prerender_get_urls( 'v2' ) );
check( 'asking for the original set by name works', ihq_aicoach_prerender_get_urls( '' ) === ihq_aicoach_prerender_get_urls() );
update_option( IHQ_AICOACH_AVATAR_VERSION_OPTION, 'v2' );
check( 'once a version is the one served, visitors get its clips', ihq_aicoach_prerender_get_urls( 'v2' ) === ihq_aicoach_prerender_get_urls() );
unlink( $dir . '/bts-ja.v2.mp4' );
check( 'a clip whose file is gone is not offered', array( 'en' ) === array_keys( ihq_aicoach_prerender_get_urls()['bts'] ) );
put_clip( 'bts-ja.v2.mp4' );
update_option( IHQ_AICOACH_AVATAR_VERSION_OPTION, '' );

// --- the work list ---

$GLOBALS['scripts_body'] = array(
	'segments' => array(
		'bts'     => array( 'status' => 'approved', 'text' => 'BTS text', 'sha256' => 'sha-bts' ),
		'world'   => array( 'status' => 'approved', 'text' => 'World text', 'sha256' => 'sha-world' ),
		'private' => array( 'status' => 'held', 'text' => 'Held text', 'sha256' => 'sha-private' ),
	),
);
$GLOBALS['translations'] = array( 'bts' => array( 'ja' => 'BTS in Japanese', 'ko' => '   ' ), 'world' => array( 'ja' => 'World in Japanese' ), 'private' => array( 'ja' => 'Private in Japanese' ) );
$logged = array();
$list   = ihq_aicoach_prerender_work_list( function ( $line ) use ( &$logged ) { $logged[] = $line; } );
check( 'the work list is the approved segments, each in English and then in its translations, in the page\'s order', array( 'bts', 'bts__ja', 'world', 'world__ja' ) === array_column( $list, 'manifest_key' ) );
check( 'an item says what to render and how', array(
	'manifest_key' => 'bts__ja',
	'segment_key'  => 'bts',
	'language'     => 'ja',
	'text'         => 'BTS in Japanese',
	'sha256'       => hash( 'sha256', 'BTS in Japanese' ),
) === $list[1] );
check( 'the English item carries Gary\'s own hash and text', 'sha-bts' === $list[0]['sha256'] && 'BTS text' === $list[0]['text'] && 'en' === $list[0]['language'] && 'bts' === $list[0]['segment_key'] );
check( 'a translation that is only spaces is left out, and so is a held segment with its translations', ! in_array( 'bts__ko', array_column( $list, 'manifest_key' ), true ) && ! in_array( 'private__ja', array_column( $list, 'manifest_key' ), true ) );
check( 'what is skipped is logged', in_array( "private: status is 'held', not approved — skipping (held content stays as static caption for now)", $logged, true ) && in_array( "community: not present in Gary's script manifest, skipping", $logged, true ) );
check( 'the list is the same one every time', $list === ihq_aicoach_prerender_work_list() );

$GLOBALS['scripts_status'] = 500;
$bad_status = ihq_aicoach_prerender_work_list();
check( 'a Gary error is an error, not an empty list', is_wp_error( $bad_status ) && 'coach_scripts_fetch_failed' === $bad_status->get_error_code() && false !== strpos( $bad_status->get_error_message(), '500' ) );
$GLOBALS['scripts_status'] = 200;
$GLOBALS['scripts_transport_error'] = true;
$bad_transport = ihq_aicoach_prerender_work_list();
check( 'a network failure is passed on as the error it is', is_wp_error( $bad_transport ) && 'http_request_failed' === $bad_transport->get_error_code() );
$GLOBALS['scripts_transport_error'] = false;
$GLOBALS['scripts_body'] = array( 'segments' => array() );
check( 'nothing wanted is an empty list, not an error', array() === ihq_aicoach_prerender_work_list() );
check( 'rendering everything passes the error on', is_wp_error( ( function () { $GLOBALS['scripts_status'] = 503; $r = ihq_aicoach_prerender_all(); $GLOBALS['scripts_status'] = 200; return $r; } )() ) );

// --- a render step from a web page ---

foreach ( glob( $dir . '/*' ) as $f ) { unlink( $f ); }
$GLOBALS['wp_options'] = array();
$GLOBALS['scripts_body'] = array(
	'segments' => array(
		'bts'   => array( 'status' => 'approved', 'text' => 'BTS text', 'sha256' => 'sha-bts' ),
		'world' => array( 'status' => 'approved', 'text' => 'World text', 'sha256' => 'sha-world' ),
	),
);
$GLOBALS['translations'] = array( 'bts' => array( 'ja' => 'BTS in Japanese' ), 'world' => array( 'ja' => 'World in Japanese' ) );

reset_calls();
foreach ( array( '', 'x', 'v', 'v1234', 'V2', "v2\n", null, 2 ) as $bad_version ) {
	$step_bad = ihq_aicoach_avatar_render_step( $bad_version, 0 );
	check( 'a step with a version that is not one is refused: ' . json_encode( $bad_version ), is_wp_error( $step_bad ) && 'avatar_version_invalid' === $step_bad->get_error_code() );
}
check( 'a refused step calls nothing', 0 === count( posted() ) && array() === array_filter( $GLOBALS['api_calls'], function ( $c ) { return 'POST' === $c['method']; } ) );

reset_calls();
$step0 = ihq_aicoach_avatar_render_step( 'v2', 0 );
check( 'the first step renders the first item into the version and says so', array( 'done' => false, 'index' => 0, 'total' => 4, 'key' => 'bts', 'status' => 'rendered', 'error' => '' ) === $step0 );
check( 'it wrote the version\'s clip and sent the version\'s key', file_exists( $dir . '/bts.v2.mp4' ) && ! file_exists( $dir . '/bts.mp4' ) && 'ihq-seg-bts-sha-bts-v2' === posted()[0]['headers']['Idempotency-Key'] );
$again = ihq_aicoach_avatar_render_step( 'v2', 0 );
check( 'asking for the same step again is cached and starts nothing', 'cached' === $again['status'] && 1 === count( posted() ) );
$step1 = ihq_aicoach_avatar_render_step( 'v2', 1 );
check( 'the next step is the next item', 'bts__ja' === $step1['key'] && 1 === $step1['index'] && 'rendered' === $step1['status'] && file_exists( $dir . '/bts-ja.v2.mp4' ) );
$step_text = ihq_aicoach_avatar_render_step( 'v2', '2' );
check( 'an index given as text is the same index', 'world' === $step_text['key'] && 2 === $step_text['index'] );
$past = ihq_aicoach_avatar_render_step( 'v2', 4 );
check( 'past the last item the run is done and says how many there were', array( 'done' => true, 'index' => 4, 'total' => 4 ) === $past );
$before = count( posted() );
$negative = ihq_aicoach_avatar_render_step( 'v2', -1 );
check( 'a negative index is done as well, and neither starts a render', true === $negative['done'] && $before === count( posted() ) );

$GLOBALS['post_job_status'] = 'queued';
$pending = ihq_aicoach_avatar_render_step( 'v2', 3, 0 );
check( 'a job still rendering is reported as pending, with the step\'s poll count honoured', 'pending' === $pending['status'] && 'world__ja' === $pending['key'] && false !== strpos( $pending['error'], 'still in progress' ) && ! file_exists( $dir . '/world-ja.v2.mp4' ) );
check( 'a pending item is not recorded as rendered', ! isset( ihq_aicoach_prerender_load_manifest( 'v2' )['world__ja'] ) );
$GLOBALS['post_job_status'] = 'ready';
$retry = ihq_aicoach_avatar_render_step( 'v2', 3 );
check( 'asking again once it is ready renders it', 'rendered' === $retry['status'] && file_exists( $dir . '/world-ja.v2.mp4' ) );
check( 'the polls of a step are few and of a run many', 5 === IHQ_AICOACH_PRERENDER_STEP_MAX_POLLS && 18 === IHQ_AICOACH_PRERENDER_MAX_POLLS );
$step_default    = ( new ReflectionFunction( 'ihq_aicoach_avatar_render_step' ) )->getParameters()[2]->getDefaultValue();
$segment_default = ( new ReflectionFunction( 'ihq_aicoach_prerender_segment' ) )->getParameters()[6]->getDefaultValue();
check( 'a step waits for few polls by default, so a web request is not cut off, and a run for many', 5 === $step_default && 18 === $segment_default );

$GLOBALS['scripts_status'] = 502;
$step_down = ihq_aicoach_avatar_render_step( 'v2', 0 );
check( 'a step passes on a Gary outage as an error', is_wp_error( $step_down ) && 'coach_scripts_fetch_failed' === $step_down->get_error_code() );
$GLOBALS['scripts_status'] = 200;
check( 'the four steps together made the whole set', array( 'bts', 'bts__ja', 'world', 'world__ja' ) === array_keys( ihq_aicoach_prerender_load_manifest( 'v2' ) ) );

// --- rendering everything into a version ---

foreach ( glob( $dir . '/*' ) as $f ) { unlink( $f ); }
$GLOBALS['wp_options'] = array();
$GLOBALS['scripts_body'] = array(
	'segments' => array(
		'bts'      => array( 'status' => 'approved', 'text' => 'BTS text', 'sha256' => 'sha-bts' ),
		'world'    => array( 'status' => 'approved', 'text' => 'World text', 'sha256' => 'sha-world' ),
		'private'  => array( 'status' => 'held', 'text' => 'Held text', 'sha256' => 'sha-private' ),
	),
);
$GLOBALS['translations'] = array( 'bts' => array( 'ja' => 'BTS in Japanese', 'ko' => '   ' ), 'world' => array( 'ja' => 'World in Japanese' ) );
reset_calls();
$results = ihq_aicoach_prerender_all( null, 'v2' );
check( 'every approved segment and every translation is rendered into the version', array( 'bts', 'bts__ja', 'world', 'world__ja' ) === array_keys( $results ) && 4 === count( array_filter( $results, function ( $r ) { return 'rendered' === $r['status']; } ) ) );
check( 'all the clips are the version\'s, none is the original\'s', array( 'bts-ja.v2.mp4', 'bts.v2.mp4', 'world-ja.v2.mp4', 'world.v2.mp4' ) === array_map( 'basename', glob( $dir . '/*.mp4' ) ) && ! file_exists( $dir . '/manifest.json' ) );
check( 'every job went out with the version in its key', 4 === count( posted() ) && 4 === count( array_filter( posted(), function ( $c ) { return substr( $c['headers']['Idempotency-Key'], -3 ) === '-v2'; } ) ) );
reset_calls();
$again = ihq_aicoach_prerender_all( null, 'v2' );
check( 'running it again renders nothing', 0 === count( posted() ) && 4 === count( array_filter( $again, function ( $r ) { return 'cached' === $r['status']; } ) ) );
reset_calls();
ihq_aicoach_prerender_all();
check( 'with no version it renders the original set, which the version does not cover', 4 === count( posted() ) && file_exists( $dir . '/manifest.json' ) && file_exists( $dir . '/bts.mp4' ) );


// --- the checked switch ---

check( 'the version is complete: nothing missing', array() === ihq_aicoach_avatar_version_missing( 'v2' ) );
put_manifest( '', array_merge( ihq_aicoach_prerender_load_manifest(), array( 'private' => entry( 'private.mp4' ) ) ) );
put_clip( 'private.mp4' );
check( 'a served clip the version lacks is listed', array( 'private' ) === ihq_aicoach_avatar_version_missing( 'v2' ) );
$refused = ihq_aicoach_avatar_activate( 'v2' );
check( 'the switch is refused and says what is missing', is_wp_error( $refused ) && 'avatar_version_incomplete' === $refused->get_error_code() && false !== strpos( $refused->get_error_message(), 'private' ) && array( 'missing' => array( 'private' ) ) === $refused->get_error_data() );
check( 'a refused switch changes nothing', '' === ihq_aicoach_avatar_active_version() && ! isset( $GLOBALS['wp_options'][ IHQ_AICOACH_AVATAR_VERSION_OPTION ] ) );

put_manifest( 'v2', array_merge( ihq_aicoach_prerender_load_manifest( 'v2' ), array( 'private' => entry( 'private.v2.mp4' ) ) ) );
check( 'a clip in the manifest whose file is not there does not count', array( 'private' ) === ihq_aicoach_avatar_version_missing( 'v2' ) && is_wp_error( ihq_aicoach_avatar_activate( 'v2' ) ) );
put_clip( 'private.v2.mp4' );
check( 'the version is now complete', array() === ihq_aicoach_avatar_version_missing( 'v2' ) );
$done = ihq_aicoach_avatar_activate( 'v2' );
check( 'the switch is made and reported', array( 'version' => 'v2', 'changed' => true ) === $done && 'v2' === ihq_aicoach_avatar_active_version() );
check( 'visitors are now served the version', isset( ihq_aicoach_prerender_get_urls()['private']['en'] ) && false !== strpos( ihq_aicoach_prerender_get_urls()['bts']['en'], 'bts.v2.mp4' ) );
check( 'switching to the version already served changes nothing', array( 'version' => 'v2', 'changed' => false ) === ihq_aicoach_avatar_activate( 'v2' ) );

check( 'the way back works while the original has everything the version serves', array() === ihq_aicoach_avatar_version_missing( '' ) && array( 'version' => '', 'changed' => true ) === ihq_aicoach_avatar_activate( '' ) && '' === ihq_aicoach_avatar_active_version() );
check( 'after going back the original clips are served', false !== strpos( ihq_aicoach_prerender_get_urls()['bts']['en'], 'bts.mp4' ) );
ihq_aicoach_avatar_activate( 'v2' );
put_manifest( 'v2', array_merge( ihq_aicoach_prerender_load_manifest( 'v2' ), array( 'community' => entry( 'community.v2.mp4' ) ) ) );
put_clip( 'community.v2.mp4' );
$no_way_back = ihq_aicoach_avatar_activate( '' );
check( 'going back is refused when the version serves a clip the original lacks', is_wp_error( $no_way_back ) && 'avatar_version_incomplete' === $no_way_back->get_error_code() && 'v2' === ihq_aicoach_avatar_active_version() );

foreach ( array( 'v', 'v1234', 'x', 'V2', '../v2', 'original', null ) as $bad ) {
	$bad_result = ihq_aicoach_avatar_activate( $bad );
	check( 'a version that is not one is refused: ' . json_encode( $bad ), is_wp_error( $bad_result ) && 'avatar_version_invalid' === $bad_result->get_error_code() );
}
check( 'a version nothing was rendered for is refused', 'avatar_version_empty' === ihq_aicoach_avatar_activate( 'v9' )->get_error_code() && 'v2' === ihq_aicoach_avatar_active_version() );

// A served clip whose file is gone cannot be played now, so the version is not held to it.
$served_before = ihq_aicoach_prerender_load_manifest( ihq_aicoach_avatar_active_version() );
put_manifest( 'v2', array_merge( $served_before, array( 'ghost' => entry( 'ghost.v2.mp4' ), 'broken' => 'not an entry' ) ) );
check( 'a served clip whose file is gone, or an entry that is not one, is not required of another version', array() === ihq_aicoach_avatar_version_missing( 'v3' ) || ! in_array( 'ghost', ihq_aicoach_avatar_version_missing( 'v3' ), true ) );
check( 'such a clip is not counted as served either', ! in_array( 'ghost', array_keys( ihq_aicoach_prerender_get_urls() ), true ) && ! in_array( 'broken', array_keys( ihq_aicoach_prerender_get_urls() ), true ) );
put_manifest( 'v2', $served_before );

// --- the status ---

file_put_contents( $dir . '/manifest-vabc.json', '{}' );
file_put_contents( $dir . '/manifest-v10.json', wp_json_encode( array( 'bts' => entry( 'bts.v10.mp4' ) ) ) );
put_clip( 'bts.v10.mp4' );
$status = ihq_aicoach_avatar_status();
check( 'the status lists the original and the versions in order, ignoring a name that is not a version', array( '', 'v2', 'v10' ) === array_column( $status, 'version' ) );
check( 'it says which one is served', array( false, true, false ) === array_column( $status, 'active' ) );
check( 'it counts what each can serve', array( 5, 6, 1 ) === array_column( $status, 'clips' ) );
check( 'it counts what each lacks compared with what is served', array( 1, 0, 5 ) === array_column( $status, 'missing' ) );

// --- the still portrait ---

foreach ( glob( $dir . '/*' ) as $f ) { unlink( $f ); }
$GLOBALS['wp_options'] = array();
check( 'with no version the portrait is the original', 'coach-portrait.webp' === ihq_aicoach_portrait_file() && 'coach-portrait.webp' === ihq_aicoach_portrait_file( '' ) );
check( 'a version with no portrait made for it keeps the original', 'coach-portrait.webp' === ihq_aicoach_portrait_file( 'v2' ) );
file_put_contents( $GLOBALS['tmp'] . '/theme/images/aicoach/coach-portrait-v2.webp', 'x' );
check( 'a version with its portrait uses it', 'coach-portrait-v2.webp' === ihq_aicoach_portrait_file( 'v2' ) );
check( 'another version does not borrow it', 'coach-portrait.webp' === ihq_aicoach_portrait_file( 'v3' ) );
check( 'with no version asked, the portrait is the original until the version is served', 'coach-portrait.webp' === ihq_aicoach_portrait_file() );
update_option( IHQ_AICOACH_AVATAR_VERSION_OPTION, 'v2' );
check( 'once the version is served the portrait changes with it', 'coach-portrait-v2.webp' === ihq_aicoach_portrait_file() );
check( 'the original can still be asked for by name', 'coach-portrait.webp' === ihq_aicoach_portrait_file( '' ) );

function remove_tree( $path ) {
	foreach ( glob( $path . '/*' ) as $f ) { is_dir( $f ) ? remove_tree( $f ) : unlink( $f ); }
	rmdir( $path );
}
remove_tree( $GLOBALS['tmp'] );

echo $fail ? "\n$fail FAILED\n" : "\nALL PASS\n";
exit( $fail ? 1 : 0 );

<?php
/**
 * The appointment link page (PO-3109, FR-18, step 5).
 *
 * The link the visitor copied (inc/aicoach-appointment.php) opens the WordPress page with
 * the slug `appointment` and the template page-appointment.php. Which page it shows comes
 * from the server clock and the stored appointment, never from the browser: waiting,
 * join, missed, expired, ended or invalid. Join gives the visitor back their saved AI Coach
 * progress (the cookie ref stored with the appointment, so it works on a device that has
 * no cookie) and sends them to the AI Coach page, which resumes where they left.
 *
 * A link that is not an appointment (a bad signature, an id nobody stored, an appointment
 * that a newer one replaced) is always "invalid", and the page says nothing that would
 * reveal whether an appointment exists.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Template of the link page. */
const IHQ_AICOACH_APPOINTMENT_PAGE_TEMPLATE = 'page-appointment.php';

/** Template of the AI Coach page, where Join leads and where the Book buttons go. */
const IHQ_AICOACH_APPOINTMENT_COACH_TEMPLATE = 'page-home-aicoach.php';

/** Name of the form field and the nonce action of the Join button. */
const IHQ_AICOACH_APPOINTMENT_JOIN_FIELD        = 'ihq_appointment_join';
const IHQ_AICOACH_APPOINTMENT_JOIN_NONCE_ACTION = 'ihq_appointment_join';

/** setTimeout cannot wait longer than this; a longer wait would fire at once. */
const IHQ_AICOACH_APPOINTMENT_MAX_RELOAD_SECONDS = 2147483;

/** The actions a page can offer. */
const IHQ_AICOACH_APPOINTMENT_ACTION_JOIN      = 'join';
const IHQ_AICOACH_APPOINTMENT_ACTION_START_NOW = 'start-now';
const IHQ_AICOACH_APPOINTMENT_ACTION_BOOK      = 'book';

/**
 * What the page knows about a link.
 *
 * @param mixed $token The `t` parameter of the link.
 * @param int   $now   The server clock, in seconds.
 * @return array{state:string,id:?string,startsAt:?int,timeZone:string,secondsToStart:?int,secondsToNextState:?int}
 */
function ihq_aicoach_appointment_page_context( $token, $now ) {
	$invalid = array(
		'state'              => IHQ_AICOACH_APPOINTMENT_STATE_INVALID,
		'id'                 => null,
		'startsAt'           => null,
		'timeZone'           => '',
		'secondsToStart'     => null,
		'secondsToNextState' => null,
	);

	$verified = ihq_aicoach_appointment_verify_token( $token );
	if ( null === $verified ) {
		return $invalid;
	}
	$record = ihq_aicoach_appointment_load( $verified['id'] );
	// The start in the signed token and the stored one must agree: the id alone is not the link.
	if ( null === $record || ! isset( $record['startsAt'] ) || (int) $record['startsAt'] !== $verified['startsAt'] ) {
		return $invalid;
	}

	$state = ihq_aicoach_appointment_state( $verified['startsAt'], $now, ! empty( $record['completedAt'] ) );
	return array(
		'state'              => $state,
		'id'                 => $verified['id'],
		'startsAt'           => $verified['startsAt'],
		'timeZone'           => isset( $record['timeZone'] ) && is_string( $record['timeZone'] ) ? $record['timeZone'] : '',
		'secondsToStart'     => max( 0, $verified['startsAt'] - $now ),
		'secondsToNextState' => ihq_aicoach_appointment_seconds_to_next_state( $verified['startsAt'], $now, $state ),
	);
}

/**
 * The address of the AI Coach page: the published page that uses its template. It is the
 * front page on some instances and a page of its own on others, so it is looked up
 * rather than assumed.
 *
 * @return string The page's permalink; the home page when no page uses the template.
 */
function ihq_aicoach_appointment_coach_page_url() {
	$pages = get_pages(
		array(
			'meta_key'    => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one small lookup of one page.
			'meta_value'  => IHQ_AICOACH_APPOINTMENT_COACH_TEMPLATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'post_status' => 'publish',
			'number'      => 1,
		)
	);
	return $pages ? get_permalink( $pages[0] ) : home_url( '/' );
}

/**
 * The appointment time as the visitor chose to see it.
 *
 * @param int    $starts_at Start, in seconds.
 * @param string $time_zone IANA name; UTC when empty or unknown.
 * @return string For example "Friday, 9 October 2026, 14:30 (Europe/Belgrade)".
 */
function ihq_aicoach_appointment_display_time( $starts_at, $time_zone ) {
	$name = ihq_aicoach_appointment_is_valid_time_zone( $time_zone ) ? $time_zone : 'UTC';
	$when = ( new DateTimeImmutable( '@' . (int) $starts_at ) )->setTimezone( new DateTimeZone( $name ) );
	return $when->format( 'l, j F Y, H:i' ) . ' (' . $name . ')';
}

/**
 * Whether the Join (or Start now) button works in a state.
 *
 * @param string $state
 * @param bool   $start_now_enabled Whether "Start now instead" is on (a parameter so both
 *                                  settings can be tested).
 * @return bool
 */
function ihq_aicoach_appointment_join_allowed( $state, $start_now_enabled = IHQ_AICOACH_APPOINTMENT_START_NOW_ENABLED ) {
	if ( IHQ_AICOACH_APPOINTMENT_STATE_JOIN === $state ) {
		return true;
	}
	return $start_now_enabled
		&& in_array( $state, array( IHQ_AICOACH_APPOINTMENT_STATE_WAITING, IHQ_AICOACH_APPOINTMENT_STATE_MISSED ), true );
}

/**
 * The text and the actions of the page for a state (English, plain: the ticket's wording).
 *
 * @param string $state
 * @param bool   $start_now_enabled Whether "Start now instead" is on.
 * @return array{title:string,message:string,actions:array<int,array{type:string,label:string}>}
 */
function ihq_aicoach_appointment_page_view( $state, $start_now_enabled = IHQ_AICOACH_APPOINTMENT_START_NOW_ENABLED ) {
	$start_now = array(
		'type'  => IHQ_AICOACH_APPOINTMENT_ACTION_START_NOW,
		'label' => __( 'Start now instead', 'influencer-hq' ),
	);
	$book      = function ( $label ) {
		return array( 'type' => IHQ_AICOACH_APPOINTMENT_ACTION_BOOK, 'label' => $label );
	};

	if ( IHQ_AICOACH_APPOINTMENT_STATE_WAITING === $state ) {
		return array(
			'title'   => __( 'Your appointment is coming up', 'influencer-hq' ),
			'message' => __( 'Your appointment starts in', 'influencer-hq' ),
			'actions' => $start_now_enabled ? array( $start_now ) : array(),
		);
	}
	if ( IHQ_AICOACH_APPOINTMENT_STATE_JOIN === $state ) {
		return array(
			'title'   => __( 'Your appointment is ready', 'influencer-hq' ),
			'message' => __( 'Sami is ready for you.', 'influencer-hq' ),
			'actions' => array(
				array(
					'type'  => IHQ_AICOACH_APPOINTMENT_ACTION_JOIN,
					'label' => __( 'Join your video with Sami', 'influencer-hq' ),
				),
			),
		);
	}
	if ( IHQ_AICOACH_APPOINTMENT_STATE_MISSED === $state ) {
		$actions = $start_now_enabled ? array( $start_now ) : array();
		// The ticket gives no action for a missed link when "Start now instead" is off; a page
		// with nothing to press would be a dead end, so it offers to book again.
		$actions[] = $book( __( 'Book a new appointment', 'influencer-hq' ) );
		return array(
			'title'   => __( 'You missed your appointment', 'influencer-hq' ),
			'message' => '',
			'actions' => $actions,
		);
	}
	if ( IHQ_AICOACH_APPOINTMENT_STATE_EXPIRED === $state ) {
		return array(
			'title'   => __( 'This link has expired', 'influencer-hq' ),
			'message' => '',
			'actions' => array( $book( __( 'Book a new appointment', 'influencer-hq' ) ) ),
		);
	}
	if ( IHQ_AICOACH_APPOINTMENT_STATE_ENDED === $state ) {
		return array(
			'title'   => __( 'This session has ended', 'influencer-hq' ),
			'message' => '',
			'actions' => array( $book( __( 'Book another appointment', 'influencer-hq' ) ) ),
		);
	}
	return array(
		'title'   => __( "This link isn't valid", 'influencer-hq' ),
		'message' => '',
		'actions' => array( $book( __( 'Book an appointment', 'influencer-hq' ) ) ),
	);
}

/**
 * What a Join request does.
 *
 * @param array  $post     The POST fields.
 * @param string $page_url The link page, to go back to when Join is not allowed.
 * @param string $coach_url Where Join leads: the AI Coach page.
 * @param int    $now      The server clock, in seconds.
 * @param bool   $start_now_enabled Whether "Start now instead" is on.
 * @return array{ref:?string,redirect:string} A ref to put in the progress cookie (null for
 *                                           none) and where to send the visitor.
 */
function ihq_aicoach_appointment_join_decision( array $post, $page_url, $coach_url, $now, $start_now_enabled = IHQ_AICOACH_APPOINTMENT_START_NOW_ENABLED ) {
	$token = isset( $post['t'] ) && is_string( $post['t'] ) ? sanitize_text_field( wp_unslash( $post['t'] ) ) : '';
	$back  = array( 'ref' => null, 'redirect' => add_query_arg( IHQ_AICOACH_APPOINTMENT_TOKEN_PARAM, $token, $page_url ) );

	$nonce = isset( $post['_wpnonce'] ) && is_string( $post['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $post['_wpnonce'] ) ) : '';
	// An expired nonce (a page left open for a day) just shows the page again with the current state.
	if ( ! wp_verify_nonce( $nonce, IHQ_AICOACH_APPOINTMENT_JOIN_NONCE_ACTION ) ) {
		return $back;
	}

	$context = ihq_aicoach_appointment_page_context( $token, $now );
	if ( null === $context['id'] || ! ihq_aicoach_appointment_join_allowed( $context['state'], $start_now_enabled ) ) {
		return $back;
	}
	$record = ihq_aicoach_appointment_load( $context['id'] );
	$ref    = is_array( $record ) && isset( $record['ref'] ) && is_string( $record['ref'] ) ? $record['ref'] : '';
	if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $ref ) ) {
		return $back;
	}
	return array( 'ref' => $ref, 'redirect' => $coach_url );
}

/**
 * Headers for every request to the page, and the Join button. The page is one visitor's
 * private link: never cached and never indexed.
 *
 * @return void
 */
function ihq_aicoach_appointment_page_request() {
	if ( ! is_page_template( IHQ_AICOACH_APPOINTMENT_PAGE_TEMPLATE ) ) {
		return;
	}
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow' );

	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
	if ( 'POST' !== $method || ! isset( $_POST[ IHQ_AICOACH_APPOINTMENT_JOIN_FIELD ] ) ) {
		return;
	}

	$decision = ihq_aicoach_appointment_join_decision( $_POST, get_permalink(), ihq_aicoach_appointment_coach_page_url(), time() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the nonce is checked inside.
	if ( null !== $decision['ref'] ) {
		ihq_aicoach_progress_set_ref_cookie( $decision['ref'] );
	}
	wp_safe_redirect( $decision['redirect'] );
	exit;
}
add_action( 'template_redirect', 'ihq_aicoach_appointment_page_request' );

/**
 * The countdown script, only on the link page. A script module with the countdown
 * helper as its static dependency, so WordPress prints it in the import map (the same
 * arrangement as inc/aicoach-modules.php).
 *
 * @return void
 */
function ihq_aicoach_appointment_page_enqueue() {
	if ( ! is_page_template( IHQ_AICOACH_APPOINTMENT_PAGE_TEMPLATE ) ) {
		return;
	}
	$dir = get_template_directory() . '/js/aicoach/';
	$uri = get_template_directory_uri() . '/js/aicoach/';

	$version_of = function ( $name ) use ( $dir ) {
		$path = $dir . $name . '.js';
		return file_exists( $path ) ? (string) filemtime( $path ) : _S_VERSION;
	};

	$countdown_id = IHQ_AICOACH_MODULE_ID_PREFIX . 'countdown';
	$page_id      = IHQ_AICOACH_MODULE_ID_PREFIX . 'appointment-page';
	wp_register_script_module( $countdown_id, $uri . 'countdown.js', array(), $version_of( 'countdown' ) );
	wp_register_script_module( $page_id, $uri . 'appointment-page.js', array( $countdown_id ), $version_of( 'appointment-page' ) );
	wp_enqueue_script_module( $page_id );
}
add_action( 'wp_enqueue_scripts', 'ihq_aicoach_appointment_page_enqueue' );

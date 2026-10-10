<?php
/**
 * AI Coach appointments (PO-3109, FR-18, step 4).
 *
 * When the visitor runs out of time they can book a later conversation with Sami.
 * The browser asks this route for the link; the server decides, because a browser
 * clock and a browser time zone cannot be trusted:
 *
 *   POST /wp-json/ihq/v1/aicoach/appointment
 *     body { choice, date, time, timeZone }
 *     201 { link, startsAt }   a signed link and the start time (ISO 8601, UTC)
 *     400 { errors }           the same codes as js/aicoach/appointment.js
 *     429                      too many requests from this address
 *
 * The rules are a PHP port of validateAppointment() in js/aicoach/appointment.js
 * and are tested against the same cases (tests/aicoach-appointment.test.php).
 *
 * The link carries the appointment id and start time signed with HMAC-SHA256, so an
 * edited link is detectable. It never carries the visitor's cookie ref (the ref is
 * the credential for the visitor's progress). The link page (page-appointment.php,
 * inc/aicoach-appointment-page.php) reads the appointment back with
 * ihq_aicoach_appointment_verify_token() and ihq_aicoach_appointment_load().
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slug of the WordPress page the link points to (template page-appointment.php). The
 * link is built from that page, so it cannot point at an address that does not exist.
 */
const IHQ_AICOACH_APPOINTMENT_PAGE_SLUG = 'appointment';

/** The states of a link page; the page decides what to show from one of these. */
const IHQ_AICOACH_APPOINTMENT_STATE_WAITING = 'waiting';
const IHQ_AICOACH_APPOINTMENT_STATE_JOIN    = 'join';
const IHQ_AICOACH_APPOINTMENT_STATE_MISSED  = 'missed';
const IHQ_AICOACH_APPOINTMENT_STATE_EXPIRED = 'expired';
const IHQ_AICOACH_APPOINTMENT_STATE_ENDED   = 'ended';
const IHQ_AICOACH_APPOINTMENT_STATE_INVALID = 'invalid';

/**
 * The windows around the start (PO-3109, Scenario 32). The ticket leaves the numbers
 * and the exact 15-minute boundary to Marcus and Steve, so these are defaults kept
 * in one place: join opens 10 minutes before the start and closes 15 minutes after
 * it (exactly 15 minutes counts as missed), and a missed link expires 24 hours after
 * the start (exactly 24 hours is still missed).
 */
const IHQ_AICOACH_APPOINTMENT_JOIN_OPENS_BEFORE = 10 * MINUTE_IN_SECONDS;
const IHQ_AICOACH_APPOINTMENT_JOIN_CLOSES_AFTER = 15 * MINUTE_IN_SECONDS;
const IHQ_AICOACH_APPOINTMENT_EXPIRES_AFTER     = DAY_IN_SECONDS;

/** "Start now instead" on the waiting and missed pages: off until it is confirmed. */
const IHQ_AICOACH_APPOINTMENT_START_NOW_ENABLED = false;

/** Query parameter that carries the signed token. */
const IHQ_AICOACH_APPOINTMENT_TOKEN_PARAM = 't';

/** Prefix of the option that holds one appointment, keyed by its id. */
const IHQ_AICOACH_APPOINTMENT_OPTION_PREFIX = 'ihq_aicoach_appointment_';

const IHQ_AICOACH_APPOINTMENT_RELATIVE_MINUTES = array(
	'in-30-minutes' => 30,
	'in-an-hour'    => 60,
);
const IHQ_AICOACH_APPOINTMENT_CHOICE_OTHER     = 'other';

/** Error codes; the strings are shared with js/aicoach/appointment.js (ERROR). */
const IHQ_AICOACH_APPOINTMENT_ERROR_CHOICE_MISSING     = 'choice-missing';
const IHQ_AICOACH_APPOINTMENT_ERROR_DATE_MISSING       = 'date-missing';
const IHQ_AICOACH_APPOINTMENT_ERROR_TIME_ZONE_MISSING  = 'time-zone-missing';
const IHQ_AICOACH_APPOINTMENT_ERROR_TIME_MISSING       = 'time-missing';
const IHQ_AICOACH_APPOINTMENT_ERROR_DATE_INVALID       = 'date-invalid';
const IHQ_AICOACH_APPOINTMENT_ERROR_TIME_INVALID       = 'time-invalid';
const IHQ_AICOACH_APPOINTMENT_ERROR_TIME_ZONE_INVALID  = 'time-zone-invalid';
const IHQ_AICOACH_APPOINTMENT_ERROR_TIME_DOES_NOT_EXIST = 'time-does-not-exist';
const IHQ_AICOACH_APPOINTMENT_ERROR_TIME_IN_PAST       = 'time-in-past';

/** Sanity cap on a time zone name in the request. */
const IHQ_AICOACH_APPOINTMENT_MAX_FIELD_LENGTH = 64;

/**
 * @param mixed $value
 * @return bool True for anything that is not a string with visible characters.
 */
function ihq_aicoach_appointment_is_blank( $value ) {
	return ! is_string( $value ) || '' === trim( $value );
}

/**
 * @param string $time_zone
 * @return bool Whether PHP lists this IANA zone name. Offsets and abbreviations that
 *              DateTimeZone would parse (+02:00, CEST) are refused on purpose. A zone
 *              newer than this server's time zone data, or a legacy alias it dropped,
 *              is refused too; the visitor can pick another name from the list.
 */
function ihq_aicoach_appointment_is_valid_time_zone( $time_zone ) {
	if ( ihq_aicoach_appointment_is_blank( $time_zone ) ) {
		return false;
	}
	return in_array( $time_zone, DateTimeZone::listIdentifiers( DateTimeZone::ALL_WITH_BC ), true );
}

/**
 * The wall-clock text of an instant in a zone, for comparing against what was asked.
 *
 * @param int          $instant
 * @param DateTimeZone $zone
 * @return string Y-m-d H:i
 */
function ihq_aicoach_appointment_wall_clock( $instant, DateTimeZone $zone ) {
	$moment = new DateTimeImmutable( '@' . $instant );
	return $moment->setTimezone( $zone )->format( 'Y-m-d H:i' );
}

/**
 * The instant a wall-clock date and time in a zone refers to. A time that never
 * happens (the hour skipped when clocks go forward) is refused; a time that
 * happens twice (the hour repeated when they go back) resolves to the first,
 * earlier occurrence. Same method as zonedDateTimeToInstant() in appointment.js:
 * the offsets in force a day before and a day after are the only ones that can
 * apply, and only the candidates whose wall clock matches count.
 *
 * @param string $date      YYYY-MM-DD
 * @param string $time      HH:mm, 24-hour
 * @param string $time_zone IANA name
 * @return array{ok:bool,instant:?int,error:?string}
 */
function ihq_aicoach_appointment_zoned_to_instant( $date, $time, $time_zone ) {
	$refused = function ( $error ) {
		return array( 'ok' => false, 'instant' => null, 'error' => $error );
	};

	if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', (string) $date, $date_match ) ) {
		return $refused( IHQ_AICOACH_APPOINTMENT_ERROR_DATE_INVALID );
	}
	if ( ! preg_match( '/^(\d{2}):(\d{2})$/', (string) $time, $time_match ) ) {
		return $refused( IHQ_AICOACH_APPOINTMENT_ERROR_TIME_INVALID );
	}
	if ( ! ihq_aicoach_appointment_is_valid_time_zone( $time_zone ) ) {
		return $refused( IHQ_AICOACH_APPOINTMENT_ERROR_TIME_ZONE_INVALID );
	}

	$year   = (int) $date_match[1];
	$month  = (int) $date_match[2];
	$day    = (int) $date_match[3];
	$hour   = (int) $time_match[1];
	$minute = (int) $time_match[2];
	if ( $hour > 23 || $minute > 59 ) {
		return $refused( IHQ_AICOACH_APPOINTMENT_ERROR_TIME_INVALID );
	}
	// Rejects 2026-02-30, which gmmktime() would silently roll over to March.
	if ( ! checkdate( $month, $day, $year ) ) {
		return $refused( IHQ_AICOACH_APPOINTMENT_ERROR_DATE_INVALID );
	}

	$zone          = new DateTimeZone( $time_zone );
	$wall_as_utc   = gmmktime( $hour, $minute, 0, $month, $day, $year );
	$wanted        = sprintf( '%04d-%02d-%02d %02d:%02d', $year, $month, $day, $hour, $minute );
	$offsets       = array(
		$zone->getOffset( new DateTimeImmutable( '@' . ( $wall_as_utc - DAY_IN_SECONDS ) ) ),
		$zone->getOffset( new DateTimeImmutable( '@' . ( $wall_as_utc + DAY_IN_SECONDS ) ) ),
	);
	$candidates = array();
	foreach ( $offsets as $offset ) {
		$instant = $wall_as_utc - $offset;
		if ( ihq_aicoach_appointment_wall_clock( $instant, $zone ) === $wanted ) {
			$candidates[ $instant ] = $instant;
		}
	}
	if ( array() === $candidates ) {
		return $refused( IHQ_AICOACH_APPOINTMENT_ERROR_TIME_DOES_NOT_EXIST );
	}
	return array( 'ok' => true, 'instant' => min( $candidates ), 'error' => null );
}

/**
 * Decides whether a link may be created for what the visitor chose.
 *
 * @param array $selection { choice, date, time, timeZone }
 * @param int   $now       The server clock, in seconds.
 * @return array{ok:bool,errors:string[],startsAt:?int}
 */
function ihq_aicoach_appointment_validate( array $selection, $now ) {
	$choice    = isset( $selection['choice'] ) ? $selection['choice'] : '';
	$date      = isset( $selection['date'] ) ? $selection['date'] : '';
	$time      = isset( $selection['time'] ) ? $selection['time'] : '';
	$time_zone = isset( $selection['timeZone'] ) ? $selection['timeZone'] : '';

	$refused  = function ( array $errors ) {
		return array( 'ok' => false, 'errors' => $errors, 'startsAt' => null );
	};
	$accepted = function ( $starts_at ) {
		return array( 'ok' => true, 'errors' => array(), 'startsAt' => $starts_at );
	};

	if ( is_string( $choice ) && isset( IHQ_AICOACH_APPOINTMENT_RELATIVE_MINUTES[ $choice ] ) ) {
		return $accepted( $now + IHQ_AICOACH_APPOINTMENT_RELATIVE_MINUTES[ $choice ] * MINUTE_IN_SECONDS );
	}
	if ( IHQ_AICOACH_APPOINTMENT_CHOICE_OTHER !== $choice ) {
		return $refused( array( IHQ_AICOACH_APPOINTMENT_ERROR_CHOICE_MISSING ) );
	}

	// In the order the fields are on screen: Date, Timezone, Appointment Time.
	$missing = array();
	if ( ihq_aicoach_appointment_is_blank( $date ) ) {
		$missing[] = IHQ_AICOACH_APPOINTMENT_ERROR_DATE_MISSING;
	}
	if ( ihq_aicoach_appointment_is_blank( $time_zone ) ) {
		$missing[] = IHQ_AICOACH_APPOINTMENT_ERROR_TIME_ZONE_MISSING;
	}
	if ( ihq_aicoach_appointment_is_blank( $time ) ) {
		$missing[] = IHQ_AICOACH_APPOINTMENT_ERROR_TIME_MISSING;
	}
	if ( array() !== $missing ) {
		return $refused( $missing );
	}

	$resolved = ihq_aicoach_appointment_zoned_to_instant( $date, $time, $time_zone );
	if ( ! $resolved['ok'] ) {
		return $refused( array( $resolved['error'] ) );
	}
	if ( $resolved['instant'] <= $now ) {
		return $refused( array( IHQ_AICOACH_APPOINTMENT_ERROR_TIME_IN_PAST ) );
	}
	return $accepted( $resolved['instant'] );
}

/**
 * @param string $value
 * @return string URL-safe base64 without padding.
 */
function ihq_aicoach_appointment_base64url_encode( $value ) {
	return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
}

/**
 * @param string $value
 * @return string|false The decoded bytes, or false if it is not valid base64url.
 */
function ihq_aicoach_appointment_base64url_decode( $value ) {
	return base64_decode( strtr( $value, '-_', '+/' ), true );
}

/**
 * The key the link is signed with: the constant from configuration, or a key
 * derived from the site's salts when it is not set.
 *
 * @return string
 */
function ihq_aicoach_appointment_secret() {
	if ( defined( 'IHQ_AICOACH_APPOINTMENT_SECRET' ) && '' !== IHQ_AICOACH_APPOINTMENT_SECRET ) {
		return IHQ_AICOACH_APPOINTMENT_SECRET;
	}
	return wp_salt( 'ihq_aicoach_appointment' );
}

/**
 * A signed token for an appointment: the id and the start time, then the signature.
 *
 * @param string $id        Appointment id.
 * @param int    $starts_at Start, in seconds.
 * @return string
 */
function ihq_aicoach_appointment_make_token( $id, $starts_at ) {
	$payload = ihq_aicoach_appointment_base64url_encode( wp_json_encode( array( 'i' => $id, 's' => (int) $starts_at ) ) );
	$signature = ihq_aicoach_appointment_base64url_encode( hash_hmac( 'sha256', $payload, ihq_aicoach_appointment_secret(), true ) );
	return $payload . '.' . $signature;
}

/**
 * Checks a token's signature and shape.
 *
 * @param mixed $token From the link.
 * @return array{id:string,startsAt:int}|null Null for anything not issued by this site.
 */
function ihq_aicoach_appointment_verify_token( $token ) {
	if ( ! is_string( $token ) ) {
		return null;
	}
	$parts = explode( '.', $token );
	if ( 2 !== count( $parts ) ) {
		return null;
	}
	$expected = ihq_aicoach_appointment_base64url_encode( hash_hmac( 'sha256', $parts[0], ihq_aicoach_appointment_secret(), true ) );
	if ( ! hash_equals( $expected, $parts[1] ) ) {
		return null;
	}
	$json    = ihq_aicoach_appointment_base64url_decode( $parts[0] );
	$payload = false === $json ? null : json_decode( $json, true );
	if ( ! is_array( $payload ) || ! isset( $payload['i'], $payload['s'] ) || ! is_string( $payload['i'] ) || ! is_int( $payload['s'] ) ) {
		return null;
	}
	return array( 'id' => $payload['i'], 'startsAt' => $payload['s'] );
}

/**
 * @param string $id
 * @return string wp_options key of one appointment.
 */
function ihq_aicoach_appointment_option_key( $id ) {
	return IHQ_AICOACH_APPOINTMENT_OPTION_PREFIX . $id;
}

/**
 * @param string $id
 * @return array|null The stored appointment, or null if there is none.
 */
function ihq_aicoach_appointment_load( $id ) {
	if ( ! is_string( $id ) || ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id ) ) {
		return null;
	}
	$saved = get_option( ihq_aicoach_appointment_option_key( $id ), null );
	return is_array( $saved ) ? $saved : null;
}

/**
 * Stores a new appointment for a visitor and replaces their previous one. The
 * record lives in the visitor's progress and in an option of its own, so the link
 * page can find the visitor from the id alone on a device that has no cookie.
 *
 * The previous appointment is kept until both writes have succeeded, so a failed
 * write never leaves the visitor with no appointment, and a link is never handed
 * out for a record that was not saved.
 *
 * @param string $ref       The visitor's progress ref.
 * @param int    $starts_at Start, in seconds.
 * @param string $time_zone IANA name the visitor chose, kept for display.
 * @param int    $now       The server clock, in seconds.
 * @return array|WP_Error The stored record, or an error when it could not be saved.
 */
function ihq_aicoach_appointment_create( $ref, $starts_at, $time_zone, $now ) {
	$previous = ihq_aicoach_progress_load( $ref );

	$id     = wp_generate_uuid4();
	$record = array(
		'id'        => $id,
		'startsAt'  => (int) $starts_at,
		'timeZone'  => $time_zone,
		'createdAt' => gmdate( 'c', $now ),
	);
	$option_key = ihq_aicoach_appointment_option_key( $id );
	// autoload=no, like the progress record: read only by the link page. The id is
	// new, so a false answer is a failed write, not an unchanged value.
	if ( ! update_option( $option_key, array_merge( $record, array( 'ref' => $ref ) ), false ) ) {
		return new WP_Error( 'appointment_save_failed', 'The appointment could not be saved.' );
	}

	// ihq_aicoach_progress_save() does not report whether the write worked, and
	// update_option() leaves the cache alone when it did not, so read the record
	// back to see whether it is the new one.
	ihq_aicoach_progress_save( $ref, array( 'appointment' => $record ) );
	$stored = ihq_aicoach_progress_load( $ref );
	if ( ! isset( $stored['appointment']['id'] ) || $id !== $stored['appointment']['id'] ) {
		delete_option( $option_key );
		return new WP_Error( 'appointment_progress_save_failed', 'The appointment could not be saved.' );
	}

	if ( isset( $previous['appointment']['id'] ) ) {
		delete_option( ihq_aicoach_appointment_option_key( $previous['appointment']['id'] ) );
	}
	return $record;
}

/**
 * The address of the link page: the permalink of the published WordPress page with the
 * slug IHQ_AICOACH_APPOINTMENT_PAGE_SLUG.
 *
 * @return string|null Null when that page does not exist or is not published.
 */
function ihq_aicoach_appointment_page_url() {
	$page = get_page_by_path( IHQ_AICOACH_APPOINTMENT_PAGE_SLUG );
	if ( ! $page || 'publish' !== $page->post_status ) {
		return null;
	}
	return get_permalink( $page );
}

/**
 * @param string $page_url  From ihq_aicoach_appointment_page_url().
 * @param string $id        Appointment id.
 * @param int    $starts_at Start, in seconds.
 * @return string The link the visitor copies.
 */
function ihq_aicoach_appointment_link( $page_url, $id, $starts_at ) {
	return add_query_arg(
		IHQ_AICOACH_APPOINTMENT_TOKEN_PARAM,
		ihq_aicoach_appointment_make_token( $id, $starts_at ),
		$page_url
	);
}

/**
 * Which page a link shows right now.
 *
 * @param int  $starts_at Start, in seconds.
 * @param int  $now       The server clock, in seconds.
 * @param bool $completed Whether the visitor has finished the process (registered).
 * @return string One of IHQ_AICOACH_APPOINTMENT_STATE_* (never invalid: that is for a
 *                link that is not an appointment at all).
 */
function ihq_aicoach_appointment_state( $starts_at, $now, $completed = false ) {
	if ( $completed ) {
		return IHQ_AICOACH_APPOINTMENT_STATE_ENDED;
	}
	if ( $now < $starts_at - IHQ_AICOACH_APPOINTMENT_JOIN_OPENS_BEFORE ) {
		return IHQ_AICOACH_APPOINTMENT_STATE_WAITING;
	}
	if ( $now < $starts_at + IHQ_AICOACH_APPOINTMENT_JOIN_CLOSES_AFTER ) {
		return IHQ_AICOACH_APPOINTMENT_STATE_JOIN;
	}
	if ( $now <= $starts_at + IHQ_AICOACH_APPOINTMENT_EXPIRES_AFTER ) {
		return IHQ_AICOACH_APPOINTMENT_STATE_MISSED;
	}
	return IHQ_AICOACH_APPOINTMENT_STATE_EXPIRED;
}

/**
 * How long until the state above changes, so the page can reload and show the next one.
 *
 * @param int    $starts_at Start, in seconds.
 * @param int    $now       The server clock, in seconds.
 * @param string $state     From ihq_aicoach_appointment_state().
 * @return int|null Whole seconds (at least 1), or null when the state never changes.
 */
function ihq_aicoach_appointment_seconds_to_next_state( $starts_at, $now, $state ) {
	$changes_at = null;
	if ( IHQ_AICOACH_APPOINTMENT_STATE_WAITING === $state ) {
		$changes_at = $starts_at - IHQ_AICOACH_APPOINTMENT_JOIN_OPENS_BEFORE;
	} elseif ( IHQ_AICOACH_APPOINTMENT_STATE_JOIN === $state ) {
		$changes_at = $starts_at + IHQ_AICOACH_APPOINTMENT_JOIN_CLOSES_AFTER;
	} elseif ( IHQ_AICOACH_APPOINTMENT_STATE_MISSED === $state ) {
		// The last second of the missed window is EXPIRES_AFTER itself.
		$changes_at = $starts_at + IHQ_AICOACH_APPOINTMENT_EXPIRES_AFTER + 1;
	}
	if ( null === $changes_at ) {
		return null;
	}
	return max( 1, $changes_at - $now );
}

/**
 * Marks a visitor's appointment as finished: the link then shows "This session has
 * ended". Called when the visitor registers, next to the place that clears their
 * progress record.
 *
 * @param string $ref The visitor's progress ref.
 * @param int    $now The server clock, in seconds.
 * @return bool Whether an appointment was marked.
 */
function ihq_aicoach_appointment_mark_completed( $ref, $now ) {
	$progress = ihq_aicoach_progress_load( $ref );
	if ( ! isset( $progress['appointment']['id'] ) ) {
		return false;
	}
	$appointment = ihq_aicoach_appointment_load( $progress['appointment']['id'] );
	if ( null === $appointment ) {
		return false;
	}
	$appointment['completedAt'] = gmdate( 'c', $now );
	return update_option( ihq_aicoach_appointment_option_key( $progress['appointment']['id'] ), $appointment, false );
}

/**
 * @param WP_REST_Response $response
 * @return WP_REST_Response Never cached: it carries one visitor's link.
 */
function ihq_aicoach_appointment_no_store( WP_REST_Response $response ) {
	$response->header( 'Cache-Control', 'no-store, private' );
	return $response;
}

/**
 * POST /ihq/v1/aicoach/appointment
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function ihq_aicoach_appointment_handle_post( WP_REST_Request $request ) {
	// Same per-IP write counter as the progress route: this route writes too.
	if ( ihq_aicoach_progress_rate_limited() ) {
		return ihq_aicoach_appointment_no_store(
			new WP_REST_Response( array( 'error' => 'Too many requests. Please slow down.' ), 429 )
		);
	}

	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = array();
	}
	$selection = array();
	foreach ( array( 'choice', 'date', 'time', 'timeZone' ) as $field ) {
		if ( isset( $params[ $field ] ) && is_string( $params[ $field ] ) ) {
			$selection[ $field ] = mb_substr( sanitize_text_field( $params[ $field ] ), 0, IHQ_AICOACH_APPOINTMENT_MAX_FIELD_LENGTH );
		}
	}

	$now    = time();
	$result = ihq_aicoach_appointment_validate( $selection, $now );
	if ( ! $result['ok'] ) {
		return ihq_aicoach_appointment_no_store(
			new WP_REST_Response( array( 'errors' => $result['errors'] ), 400 )
		);
	}

	// Without the page there is nowhere for the link to go: stop before anything is stored.
	$page_url = ihq_aicoach_appointment_page_url();
	if ( null === $page_url ) {
		return ihq_aicoach_appointment_no_store(
			new WP_REST_Response( array( 'error' => 'The appointment page is not available.' ), 500 )
		);
	}

	$ref    = ihq_aicoach_progress_get_ref();
	$record = ihq_aicoach_appointment_create(
		$ref,
		$result['startsAt'],
		isset( $selection['timeZone'] ) ? $selection['timeZone'] : '',
		$now
	);
	if ( is_wp_error( $record ) ) {
		return ihq_aicoach_appointment_no_store(
			new WP_REST_Response( array( 'error' => $record->get_error_message() ), 500 )
		);
	}

	return ihq_aicoach_appointment_no_store(
		new WP_REST_Response(
			array(
				'link'     => ihq_aicoach_appointment_link( $page_url, $record['id'], $record['startsAt'] ),
				'startsAt' => gmdate( 'c', $record['startsAt'] ),
			),
			201
		)
	);
}

/**
 * @return void
 */
function ihq_aicoach_appointment_register_routes() {
	register_rest_route(
		'ihq/v1',
		'/aicoach/appointment',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'ihq_aicoach_appointment_handle_post',
			'permission_callback' => 'ihq_aicoach_progress_permission_check',
		)
	);
}
add_action( 'rest_api_init', 'ihq_aicoach_appointment_register_routes' );

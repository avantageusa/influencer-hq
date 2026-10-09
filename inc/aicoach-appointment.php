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
 * the credential for the visitor's progress). The link page is a later step; it
 * reads the appointment back with ihq_aicoach_appointment_verify_token() and
 * ihq_aicoach_appointment_load().
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Page the link points to (built in step 5). */
const IHQ_AICOACH_APPOINTMENT_LINK_PATH = '/appointment/';

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
 * @param string $ref       The visitor's progress ref.
 * @param int    $starts_at Start, in seconds.
 * @param string $time_zone IANA name the visitor chose, kept for display.
 * @param int    $now       The server clock, in seconds.
 * @return array The stored record.
 */
function ihq_aicoach_appointment_create( $ref, $starts_at, $time_zone, $now ) {
	$previous = ihq_aicoach_progress_load( $ref );
	if ( isset( $previous['appointment']['id'] ) ) {
		delete_option( ihq_aicoach_appointment_option_key( $previous['appointment']['id'] ) );
	}

	$id     = wp_generate_uuid4();
	$record = array(
		'id'        => $id,
		'startsAt'  => (int) $starts_at,
		'timeZone'  => $time_zone,
		'createdAt' => gmdate( 'c', $now ),
	);
	// autoload=no, like the progress record: read only by the link page.
	update_option( ihq_aicoach_appointment_option_key( $id ), array_merge( $record, array( 'ref' => $ref ) ), false );
	ihq_aicoach_progress_save( $ref, array( 'appointment' => $record ) );
	return $record;
}

/**
 * @param string $id        Appointment id.
 * @param int    $starts_at Start, in seconds.
 * @return string The link the visitor copies.
 */
function ihq_aicoach_appointment_link( $id, $starts_at ) {
	return add_query_arg(
		IHQ_AICOACH_APPOINTMENT_TOKEN_PARAM,
		ihq_aicoach_appointment_make_token( $id, $starts_at ),
		home_url( IHQ_AICOACH_APPOINTMENT_LINK_PATH )
	);
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

	$ref    = ihq_aicoach_progress_get_ref();
	$record = ihq_aicoach_appointment_create(
		$ref,
		$result['startsAt'],
		isset( $selection['timeZone'] ) ? $selection['timeZone'] : '',
		$now
	);

	return ihq_aicoach_appointment_no_store(
		new WP_REST_Response(
			array(
				'link'     => ihq_aicoach_appointment_link( $record['id'], $record['startsAt'] ),
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

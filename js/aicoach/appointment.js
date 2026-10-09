/**
 * Appointment time logic for the AI Coach's scheduling step (PO-3109, FR-18).
 *
 * Pure functions, no DOM: the screen collects a choice and the Other fields,
 * and these decide whether a link may be created and for which instant. The
 * clock is a parameter (`now`, in milliseconds) so the rules can be tested, and
 * the server repeats the same checks before it creates a link, because a browser
 * clock and a browser time zone cannot be trusted.
 *
 * Rules taken from the ticket: a link cannot be created unless every required
 * field is provided and the chosen time is not in the past; otherwise the visitor
 * is told what to correct (the codes in ERROR below; the screen maps them to
 * text). Decisions the ticket leaves open, taken here and easy to change:
 * "In 30 minutes" and "In an hour" count from `now`, which the screen takes at the
 * moment of copying (Ivan: from when the link is created); a time exactly equal to
 * `now` counts as past (Ivan: 15:15 cannot be booked at 15:16). The screen offers
 * any hour and minute, so there is no list of times here.
 */

export const CHOICE = {
	IN_30_MINUTES: 'in-30-minutes',
	IN_AN_HOUR: 'in-an-hour',
	OTHER: 'other',
};

export const ERROR = {
	CHOICE_MISSING: 'choice-missing',
	DATE_MISSING: 'date-missing',
	TIME_ZONE_MISSING: 'time-zone-missing',
	TIME_MISSING: 'time-missing',
	DATE_INVALID: 'date-invalid',
	TIME_INVALID: 'time-invalid',
	TIME_ZONE_INVALID: 'time-zone-invalid',
	TIME_DOES_NOT_EXIST: 'time-does-not-exist',
	TIME_IN_PAST: 'time-in-past',
};

const MS_PER_MINUTE = 60 * 1000;
const MS_PER_DAY = 24 * 60 * MS_PER_MINUTE;
const RELATIVE_CHOICE_MINUTES = {
	[ CHOICE.IN_30_MINUTES ]: 30,
	[ CHOICE.IN_AN_HOUR ]: 60,
};
const DATE_PATTERN = /^(\d{4})-(\d{2})-(\d{2})$/;
const TIME_PATTERN = /^(\d{2}):(\d{2})$/;

function isBlank( value ) {
	return 'string' !== typeof value || '' === value.trim();
}

/**
 * Whether the browser knows this IANA time zone name.
 *
 * @param {string} timeZone
 * @return {boolean}
 */
export function isValidTimeZone( timeZone ) {
	if ( isBlank( timeZone ) ) {
		return false;
	}
	try {
		new Intl.DateTimeFormat( 'en-US', { timeZone } );
		return true;
	} catch ( error ) {
		return false;
	}
}

// The wall-clock fields of an instant in a zone, as numbers.
function wallClockParts( instant, timeZone ) {
	const formatter = new Intl.DateTimeFormat( 'en-US', {
		timeZone,
		hourCycle: 'h23',
		year: 'numeric',
		month: '2-digit',
		day: '2-digit',
		hour: '2-digit',
		minute: '2-digit',
		second: '2-digit',
	} );
	const parts = {};
	formatter.formatToParts( new Date( instant ) ).forEach( function ( part ) {
		parts[ part.type ] = Number( part.value );
	} );
	return parts;
}

// How far the zone's clock is ahead of UTC at this instant, in milliseconds.
function zoneOffsetMs( instant, timeZone ) {
	const parts = wallClockParts( instant, timeZone );
	const wallAsUtc = Date.UTC( parts.year, parts.month - 1, parts.day, parts.hour, parts.minute, parts.second );
	// The formatter drops milliseconds, so compare against the whole second.
	return wallAsUtc - Math.floor( instant / 1000 ) * 1000;
}

function matchesWallClock( instant, timeZone, wanted ) {
	const parts = wallClockParts( instant, timeZone );
	return parts.year === wanted.year
		&& parts.month === wanted.month
		&& parts.day === wanted.day
		&& parts.hour === wanted.hour
		&& parts.minute === wanted.minute;
}

/**
 * The instant a wall-clock date and time in a zone refers to.
 *
 * A time that never happens (the hour skipped when clocks go forward) is
 * refused. A time that happens twice (the hour repeated when clocks go back)
 * resolves to the first, earlier occurrence.
 *
 * @param {Object} fields
 * @param {string} fields.date     YYYY-MM-DD
 * @param {string} fields.time     HH:mm, 24-hour
 * @param {string} fields.timeZone IANA name, for example Europe/Belgrade
 * @return {{ ok: boolean, instant: (number|null), error: (string|null) }}
 */
export function zonedDateTimeToInstant( { date, time, timeZone } ) {
	const dateMatch = DATE_PATTERN.exec( date || '' );
	if ( ! dateMatch ) {
		return { ok: false, instant: null, error: ERROR.DATE_INVALID };
	}
	const timeMatch = TIME_PATTERN.exec( time || '' );
	if ( ! timeMatch ) {
		return { ok: false, instant: null, error: ERROR.TIME_INVALID };
	}
	if ( ! isValidTimeZone( timeZone ) ) {
		return { ok: false, instant: null, error: ERROR.TIME_ZONE_INVALID };
	}

	const wanted = {
		year: Number( dateMatch[ 1 ] ),
		month: Number( dateMatch[ 2 ] ),
		day: Number( dateMatch[ 3 ] ),
		hour: Number( timeMatch[ 1 ] ),
		minute: Number( timeMatch[ 2 ] ),
	};
	if ( wanted.hour > 23 || wanted.minute > 59 ) {
		return { ok: false, instant: null, error: ERROR.TIME_INVALID };
	}
	const wallAsUtc = Date.UTC( wanted.year, wanted.month - 1, wanted.day, wanted.hour, wanted.minute );
	const roundTrip = new Date( wallAsUtc );
	// Rejects 2026-02-30, which Date.UTC would silently roll over to March.
	if ( roundTrip.getUTCFullYear() !== wanted.year
		|| roundTrip.getUTCMonth() !== wanted.month - 1
		|| roundTrip.getUTCDate() !== wanted.day ) {
		return { ok: false, instant: null, error: ERROR.DATE_INVALID };
	}

	// The zone's offset can change within a day. The offsets in force a day
	// before and a day after are the only ones that can apply to this wall time.
	const offsets = [
		zoneOffsetMs( wallAsUtc - MS_PER_DAY, timeZone ),
		zoneOffsetMs( wallAsUtc + MS_PER_DAY, timeZone ),
	];
	const candidates = offsets
		.map( ( offset ) => wallAsUtc - offset )
		.filter( ( instant ) => matchesWallClock( instant, timeZone, wanted ) )
		.sort( ( a, b ) => a - b );

	if ( 0 === candidates.length ) {
		return { ok: false, instant: null, error: ERROR.TIME_DOES_NOT_EXIST };
	}
	return { ok: true, instant: candidates[ 0 ], error: null };
}

/**
 * Decides whether a link may be created for what the visitor chose.
 *
 * @param {Object} selection
 * @param {string} selection.choice   One of CHOICE.
 * @param {string} selection.date     YYYY-MM-DD, for Other.
 * @param {string} selection.time     HH:mm, for Other.
 * @param {string} selection.timeZone IANA name, for Other.
 * @param {number} now                The current time in milliseconds.
 * @return {{ ok: boolean, errors: string[], startsAt: (number|null), startsAtIso: (string|null) }}
 */
export function validateAppointment( selection, now ) {
	const { choice, date, time, timeZone } = selection || {};

	if ( Object.prototype.hasOwnProperty.call( RELATIVE_CHOICE_MINUTES, choice ) ) {
		return accepted( now + RELATIVE_CHOICE_MINUTES[ choice ] * MS_PER_MINUTE );
	}
	if ( CHOICE.OTHER !== choice ) {
		return refused( [ ERROR.CHOICE_MISSING ] );
	}

	// In the order the fields are on screen: Date, Timezone, Appointment Time.
	const missing = [];
	if ( isBlank( date ) ) {
		missing.push( ERROR.DATE_MISSING );
	}
	if ( isBlank( timeZone ) ) {
		missing.push( ERROR.TIME_ZONE_MISSING );
	}
	if ( isBlank( time ) ) {
		missing.push( ERROR.TIME_MISSING );
	}
	if ( missing.length > 0 ) {
		return refused( missing );
	}

	const resolved = zonedDateTimeToInstant( { date, time, timeZone } );
	if ( ! resolved.ok ) {
		return refused( [ resolved.error ] );
	}
	if ( resolved.instant <= now ) {
		return refused( [ ERROR.TIME_IN_PAST ] );
	}
	return accepted( resolved.instant );
}

function accepted( startsAt ) {
	return { ok: true, errors: [], startsAt, startsAtIso: new Date( startsAt ).toISOString() };
}

function refused( errors ) {
	return { ok: false, errors, startsAt: null, startsAtIso: null };
}

/**
 * The time zones offered in the Timezone list, sorted. Falls back to UTC alone
 * on a browser that cannot list them.
 *
 * @param {Object} intl The Intl object; a parameter so tests can replace it.
 * @return {string[]}
 */
export function listTimeZones( intl = Intl ) {
	if ( 'function' !== typeof intl.supportedValuesOf ) {
		return [ 'UTC' ];
	}
	const zones = intl.supportedValuesOf( 'timeZone' ).slice().sort();
	return zones.includes( 'UTC' ) ? zones : [ 'UTC', ...zones ];
}

/**
 * The visitor's own time zone, to preselect, or an empty string if the browser
 * does not say.
 *
 * @param {Object} intl The Intl object; a parameter so tests can replace it.
 * @return {string}
 */
export function getDefaultTimeZone( intl = Intl ) {
	try {
		return intl.DateTimeFormat().resolvedOptions().timeZone || '';
	} catch ( error ) {
		return '';
	}
}

import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
	CHOICE,
	ERROR,
	isValidTimeZone,
	zonedDateTimeToInstant,
	validateAppointment,
	listTimeZones,
	getDefaultTimeZone,
} from './appointment.js';

const NOW = Date.UTC( 2026, 9, 8, 12, 0, 0 ); // 2026-10-08 12:00:00 UTC
const MINUTE = 60 * 1000;

function iso( instant ) {
	return new Date( instant ).toISOString();
}

const ZONE_CASES = [
	// [ description, date, time, timeZone, expected UTC instant ]
	[ 'a zone without daylight saving (Tokyo, +9)', '2026-10-08', '10:00', 'Asia/Tokyo', '2026-10-08T01:00:00.000Z' ],
	[ 'a half-hour zone (Kolkata, +5:30)', '2026-10-08', '12:00', 'Asia/Kolkata', '2026-10-08T06:30:00.000Z' ],
	[ 'a negative offset (Los Angeles, summer, -7)', '2026-07-01', '09:00', 'America/Los_Angeles', '2026-07-01T16:00:00.000Z' ],
	[ 'the same zone in winter (-8)', '2026-01-15', '09:00', 'America/Los_Angeles', '2026-01-15T17:00:00.000Z' ],
	[ 'UTC itself', '2026-10-08', '12:00', 'UTC', '2026-10-08T12:00:00.000Z' ],
	[ 'Belgrade in summer time (+2)', '2026-10-24', '12:00', 'Europe/Belgrade', '2026-10-24T10:00:00.000Z' ],
	[ 'Belgrade after clocks went back (+1)', '2026-10-26', '12:00', 'Europe/Belgrade', '2026-10-26T11:00:00.000Z' ],
	[ 'a time that happens twice resolves to the first (New York, 01:30 on 1 Nov)', '2026-11-01', '01:30', 'America/New_York', '2026-11-01T05:30:00.000Z' ],
	[ 'the same repeated hour in Belgrade (02:30 on 25 Oct)', '2026-10-25', '02:30', 'Europe/Belgrade', '2026-10-25T00:30:00.000Z' ],
	[ 'midnight', '2026-10-08', '00:00', 'Asia/Tokyo', '2026-10-07T15:00:00.000Z' ],
	[ 'the last slot of the day', '2026-10-08', '23:30', 'UTC', '2026-10-08T23:30:00.000Z' ],
	[ 'a leap day', '2028-02-29', '12:00', 'UTC', '2028-02-29T12:00:00.000Z' ],
];

ZONE_CASES.forEach( ( [ description, date, time, timeZone, expected ] ) => {
	test( 'zonedDateTimeToInstant: ' + description, () => {
		const result = zonedDateTimeToInstant( { date, time, timeZone } );
		assert.deepEqual( { ok: result.ok, error: result.error, at: result.ok ? iso( result.instant ) : null }, {
			ok: true,
			error: null,
			at: expected,
		} );
	} );
} );

[
	[ 'New York, 02:30 on 8 March (clocks go forward)', '2026-03-08', '02:30', 'America/New_York' ],
	[ 'Belgrade, 02:30 on 29 March (clocks go forward)', '2026-03-29', '02:30', 'Europe/Belgrade' ],
].forEach( ( [ description, date, time, timeZone ] ) => {
	test( 'zonedDateTimeToInstant refuses a time that does not exist: ' + description, () => {
		assert.deepEqual( zonedDateTimeToInstant( { date, time, timeZone } ), {
			ok: false,
			instant: null,
			error: ERROR.TIME_DOES_NOT_EXIST,
		} );
	} );
} );

test( 'zonedDateTimeToInstant accepts the minute before and after a skipped hour', () => {
	assert.equal( zonedDateTimeToInstant( { date: '2026-03-08', time: '01:59', timeZone: 'America/New_York' } ).ok, true );
	assert.equal( zonedDateTimeToInstant( { date: '2026-03-08', time: '03:00', timeZone: 'America/New_York' } ).ok, true );
} );

[
	[ 'a date that is not YYYY-MM-DD', { date: '08/10/2026', time: '10:00', timeZone: 'UTC' }, ERROR.DATE_INVALID ],
	[ 'a date with no value', { date: '', time: '10:00', timeZone: 'UTC' }, ERROR.DATE_INVALID ],
	[ 'a day that does not exist (30 February)', { date: '2026-02-30', time: '10:00', timeZone: 'UTC' }, ERROR.DATE_INVALID ],
	[ 'a month that does not exist', { date: '2026-13-01', time: '10:00', timeZone: 'UTC' }, ERROR.DATE_INVALID ],
	[ 'a 29 February in a year that is not a leap year', { date: '2027-02-29', time: '10:00', timeZone: 'UTC' }, ERROR.DATE_INVALID ],
	[ 'a time that is not HH:mm', { date: '2026-10-08', time: '10', timeZone: 'UTC' }, ERROR.TIME_INVALID ],
	[ 'an hour past 23', { date: '2026-10-08', time: '25:00', timeZone: 'UTC' }, ERROR.TIME_INVALID ],
	[ '24:00, which is not a time of day', { date: '2026-10-08', time: '24:00', timeZone: 'UTC' }, ERROR.TIME_INVALID ],
	[ 'a minute past 59', { date: '2026-10-08', time: '10:60', timeZone: 'UTC' }, ERROR.TIME_INVALID ],
	[ 'a time zone that does not exist', { date: '2026-10-08', time: '10:00', timeZone: 'Mars/Phobos' }, ERROR.TIME_ZONE_INVALID ],
	[ 'an empty time zone', { date: '2026-10-08', time: '10:00', timeZone: '' }, ERROR.TIME_ZONE_INVALID ],
].forEach( ( [ description, fields, expectedError ] ) => {
	test( 'zonedDateTimeToInstant refuses ' + description, () => {
		assert.deepEqual( zonedDateTimeToInstant( fields ), { ok: false, instant: null, error: expectedError } );
	} );
} );

test( 'isValidTimeZone', () => {
	assert.equal( isValidTimeZone( 'Europe/Belgrade' ), true );
	assert.equal( isValidTimeZone( 'UTC' ), true );
	assert.equal( isValidTimeZone( 'Mars/Phobos' ), false );
	assert.equal( isValidTimeZone( '' ), false );
	assert.equal( isValidTimeZone( '   ' ), false );
	assert.equal( isValidTimeZone( undefined ), false );
	assert.equal( isValidTimeZone( null ), false );
	assert.equal( isValidTimeZone( 5 ), false );
} );

test( 'In 30 minutes and In an hour count from now', () => {
	assert.deepEqual( validateAppointment( { choice: CHOICE.IN_30_MINUTES }, NOW ), {
		ok: true,
		errors: [],
		startsAt: NOW + 30 * MINUTE,
		startsAtIso: '2026-10-08T12:30:00.000Z',
	} );
	assert.deepEqual( validateAppointment( { choice: CHOICE.IN_AN_HOUR }, NOW ), {
		ok: true,
		errors: [],
		startsAt: NOW + 60 * MINUTE,
		startsAtIso: '2026-10-08T13:00:00.000Z',
	} );
} );

test( 'the relative choices ignore any Other fields that are still filled in', () => {
	const result = validateAppointment( { choice: CHOICE.IN_AN_HOUR, date: '2020-01-01', time: '00:00', timeZone: 'nope' }, NOW );
	assert.equal( result.ok, true );
	assert.equal( result.startsAtIso, '2026-10-08T13:00:00.000Z' );
} );

test( 'no choice, or an unknown one, asks the visitor to choose a time', () => {
	const refusal = { ok: false, errors: [ ERROR.CHOICE_MISSING ], startsAt: null, startsAtIso: null };
	assert.deepEqual( validateAppointment( {}, NOW ), refusal );
	assert.deepEqual( validateAppointment( { choice: '' }, NOW ), refusal );
	assert.deepEqual( validateAppointment( { choice: 'tomorrow' }, NOW ), refusal );
	assert.deepEqual( validateAppointment( { choice: 'toString' }, NOW ), refusal );
	assert.deepEqual( validateAppointment( undefined, NOW ), refusal );
	assert.deepEqual( validateAppointment( null, NOW ), refusal );
} );

test( 'Other with every field and a future time is accepted and converted to UTC', () => {
	assert.deepEqual( validateAppointment( { choice: CHOICE.OTHER, date: '2026-10-09', time: '14:30', timeZone: 'Europe/Belgrade' }, NOW ), {
		ok: true,
		errors: [],
		startsAt: Date.UTC( 2026, 9, 9, 12, 30 ),
		startsAtIso: '2026-10-09T12:30:00.000Z',
	} );
} );

test( 'Other lists every missing field, in the order they are on screen', () => {
	const refusal = ( errors ) => ( { ok: false, errors, startsAt: null, startsAtIso: null } );
	assert.deepEqual( validateAppointment( { choice: CHOICE.OTHER }, NOW ), refusal( [ ERROR.DATE_MISSING, ERROR.TIME_ZONE_MISSING, ERROR.TIME_MISSING ] ) );
	assert.deepEqual( validateAppointment( { choice: CHOICE.OTHER, date: '2026-10-09' }, NOW ), refusal( [ ERROR.TIME_ZONE_MISSING, ERROR.TIME_MISSING ] ) );
	assert.deepEqual( validateAppointment( { choice: CHOICE.OTHER, date: '2026-10-09', timeZone: 'UTC' }, NOW ), refusal( [ ERROR.TIME_MISSING ] ) );
	assert.deepEqual( validateAppointment( { choice: CHOICE.OTHER, timeZone: 'UTC', time: '10:00' }, NOW ), refusal( [ ERROR.DATE_MISSING ] ) );
	assert.deepEqual( validateAppointment( { choice: CHOICE.OTHER, date: '2026-10-09', time: '10:00' }, NOW ), refusal( [ ERROR.TIME_ZONE_MISSING ] ) );
} );

test( 'a field that is only spaces counts as missing', () => {
	const result = validateAppointment( { choice: CHOICE.OTHER, date: '  ', time: ' ', timeZone: '\t' }, NOW );
	assert.deepEqual( result.errors, [ ERROR.DATE_MISSING, ERROR.TIME_ZONE_MISSING, ERROR.TIME_MISSING ] );
} );

test( 'missing fields are reported before any other check, even for a date in the past', () => {
	assert.deepEqual( validateAppointment( { choice: CHOICE.OTHER, date: '2020-01-01', timeZone: 'UTC' }, NOW ).errors, [ ERROR.TIME_MISSING ] );
} );

test( 'Other in the past is refused as in the past', () => {
	const refusal = { ok: false, errors: [ ERROR.TIME_IN_PAST ], startsAt: null, startsAtIso: null };
	assert.deepEqual( validateAppointment( { choice: CHOICE.OTHER, date: '2026-10-08', time: '11:30', timeZone: 'UTC' }, NOW ), refusal );
	assert.deepEqual( validateAppointment( { choice: CHOICE.OTHER, date: '2020-01-01', time: '10:00', timeZone: 'UTC' }, NOW ), refusal );
} );

test( 'a time exactly equal to now is in the past, one millisecond later is not', () => {
	const selection = { choice: CHOICE.OTHER, date: '2026-10-08', time: '12:00', timeZone: 'UTC' };
	assert.deepEqual( validateAppointment( selection, NOW ).errors, [ ERROR.TIME_IN_PAST ] );
	assert.equal( validateAppointment( selection, NOW - 1 ).ok, true );
} );

test( 'the past check uses the visitor\'s zone, not the local clock', () => {
	// 11:00 in Tokyo on 8 Oct is 02:00 UTC, already past at 12:00 UTC; 23:00 is 14:00 UTC, still ahead.
	assert.deepEqual( validateAppointment( { choice: CHOICE.OTHER, date: '2026-10-08', time: '11:00', timeZone: 'Asia/Tokyo' }, NOW ).errors, [ ERROR.TIME_IN_PAST ] );
	assert.equal( validateAppointment( { choice: CHOICE.OTHER, date: '2026-10-08', time: '23:00', timeZone: 'Asia/Tokyo' }, NOW ).ok, true );
} );

test( 'Other with an invalid zone, date or time reports that, not a missing field', () => {
	assert.deepEqual( validateAppointment( { choice: CHOICE.OTHER, date: '2026-10-09', time: '10:00', timeZone: 'Mars/Phobos' }, NOW ).errors, [ ERROR.TIME_ZONE_INVALID ] );
	assert.deepEqual( validateAppointment( { choice: CHOICE.OTHER, date: '2026-02-30', time: '10:00', timeZone: 'UTC' }, NOW ).errors, [ ERROR.DATE_INVALID ] );
	assert.deepEqual( validateAppointment( { choice: CHOICE.OTHER, date: '2026-10-09', time: '25:00', timeZone: 'UTC' }, NOW ).errors, [ ERROR.TIME_INVALID ] );
} );

test( 'Other on a time that does not exist (clocks going forward) is refused as such', () => {
	const now = Date.UTC( 2026, 2, 1 );
	assert.deepEqual( validateAppointment( { choice: CHOICE.OTHER, date: '2026-03-08', time: '02:30', timeZone: 'America/New_York' }, now ).errors, [ ERROR.TIME_DOES_NOT_EXIST ] );
} );

test( 'listTimeZones sorts the zones the browser reports and always includes UTC', () => {
	assert.deepEqual( listTimeZones( { supportedValuesOf: () => [ 'Europe/Belgrade', 'Asia/Tokyo', 'America/New_York' ] } ), [
		'UTC',
		'America/New_York',
		'Asia/Tokyo',
		'Europe/Belgrade',
	] );
	assert.deepEqual( listTimeZones( { supportedValuesOf: () => [ 'UTC', 'Asia/Tokyo' ] } ), [ 'Asia/Tokyo', 'UTC' ] );
} );

test( 'listTimeZones does not change the list it was given', () => {
	const given = [ 'Europe/Belgrade', 'Asia/Tokyo' ];
	listTimeZones( { supportedValuesOf: () => given } );
	assert.deepEqual( given, [ 'Europe/Belgrade', 'Asia/Tokyo' ] );
} );

test( 'listTimeZones falls back to UTC when the browser cannot list zones', () => {
	assert.deepEqual( listTimeZones( {} ), [ 'UTC' ] );
} );

test( 'the real zone list is long, sorted and every entry is valid', () => {
	const zones = listTimeZones();
	assert.ok( zones.length > 100 );
	assert.deepEqual( zones.slice( 1 ), zones.slice( 1 ).slice().sort() );
	assert.ok( zones.includes( 'Europe/Belgrade' ) );
	zones.forEach( ( zone ) => assert.equal( isValidTimeZone( zone ), true, zone ) );
} );

test( 'getDefaultTimeZone returns the browser\'s zone, or an empty string', () => {
	assert.equal( getDefaultTimeZone( { DateTimeFormat: () => ( { resolvedOptions: () => ( { timeZone: 'Asia/Tokyo' } ) } ) } ), 'Asia/Tokyo' );
	assert.equal( getDefaultTimeZone( { DateTimeFormat: () => ( { resolvedOptions: () => ( {} ) } ) } ), '' );
	assert.equal( getDefaultTimeZone( { DateTimeFormat: () => {
		throw new Error( 'no Intl' );
	} } ), '' );
	assert.equal( typeof getDefaultTimeZone(), 'string' );
} );

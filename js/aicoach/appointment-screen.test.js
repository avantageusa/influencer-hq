import { test } from 'node:test';
import assert from 'node:assert/strict';
import { ERROR } from './appointment.js';
import { I18N_EN } from './locales.js';
import {
	appointmentErrorKey,
	buildAppointmentLink,
	copyToClipboard,
	timeZoneChoices,
} from './appointment-screen.js';

test( 'every error code of the appointment logic has English text', () => {
	Object.values( ERROR ).forEach( ( code ) => {
		const text = I18N_EN[ appointmentErrorKey( code ) ];
		assert.equal( typeof text, 'string', code );
		assert.notEqual( text, '', code );
	} );
} );

test( 'the error key is the code behind the prefix', () => {
	assert.equal( appointmentErrorKey( ERROR.TIME_IN_PAST ), 'appointmentError-time-in-past' );
	assert.equal( appointmentErrorKey( ERROR.CHOICE_MISSING ), 'appointmentError-choice-missing' );
} );

test( 'the link is the page address with the start time added', () => {
	assert.equal(
		buildAppointmentLink( { pageUrl: 'https://example.test/home-ai-coach/', startsAtIso: '2026-10-09T13:30:00.000Z' } ),
		'https://example.test/home-ai-coach/?appointment=2026-10-09T13%3A30%3A00.000Z'
	);
} );

test( 'the link keeps other query parameters, drops the fragment and replaces an earlier appointment', () => {
	assert.equal(
		buildAppointmentLink( {
			pageUrl: 'https://example.test/home-ai-coach/?ref=abc&appointment=old#section',
			startsAtIso: '2026-10-09T13:30:00.000Z',
		} ),
		'https://example.test/home-ai-coach/?ref=abc&appointment=2026-10-09T13%3A30%3A00.000Z'
	);
} );

test( 'copying writes the text to the clipboard and reports success', async () => {
	const written = [];
	const clipboard = { writeText: async ( text ) => { written.push( text ); } };
	assert.deepEqual( await copyToClipboard( 'the link', clipboard ), { ok: true } );
	assert.deepEqual( written, [ 'the link' ] );
} );

test( 'copying reports a refusal instead of throwing', async () => {
	const clipboard = { writeText: async () => { throw new Error( 'NotAllowedError' ); } };
	assert.deepEqual( await copyToClipboard( 'the link', clipboard ), { ok: false } );
} );

test( 'copying reports a browser without a clipboard', async () => {
	assert.deepEqual( await copyToClipboard( 'the link', undefined ), { ok: false } );
	assert.deepEqual( await copyToClipboard( 'the link', null ), { ok: false } );
	assert.deepEqual( await copyToClipboard( 'the link', {} ), { ok: false } );
} );

test( 'the visitor\'s own zone is selected when the list has it', () => {
	const zones = [ 'UTC', 'Asia/Tokyo', 'Europe/Belgrade' ];
	const result = timeZoneChoices( { zones, defaultZone: 'Europe/Belgrade' } );
	assert.deepEqual( result, { zones: [ 'UTC', 'Asia/Tokyo', 'Europe/Belgrade' ], selected: 'Europe/Belgrade' } );
} );

test( 'a zone the list leaves out is added at the top and selected', () => {
	const zones = [ 'UTC', 'Europe/Kyiv' ];
	const result = timeZoneChoices( { zones, defaultZone: 'Europe/Kiev' } );
	assert.deepEqual( result, { zones: [ 'Europe/Kiev', 'UTC', 'Europe/Kyiv' ], selected: 'Europe/Kiev' } );
} );

test( 'no known own zone selects nothing', () => {
	assert.deepEqual( timeZoneChoices( { zones: [ 'UTC' ], defaultZone: '' } ), { zones: [ 'UTC' ], selected: '' } );
} );

test( 'the zone list handed in is not changed', () => {
	const zones = [ 'UTC' ];
	timeZoneChoices( { zones, defaultZone: 'Europe/Kiev' } );
	timeZoneChoices( { zones, defaultZone: 'UTC' } );
	timeZoneChoices( { zones, defaultZone: '' } );
	assert.deepEqual( zones, [ 'UTC' ] );
} );

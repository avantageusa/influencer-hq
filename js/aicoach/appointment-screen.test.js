import { test } from 'node:test';
import assert from 'node:assert/strict';
import { ERROR } from './appointment.js';
import { I18N_EN } from './locales.js';
import {
	appointmentErrorKey,
	requestAppointmentLink,
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

const SELECTION = { choice: 'other', date: '2026-10-09', time: '14:30', timeZone: 'Europe/Belgrade' };
const URL_OF_ROUTE = 'https://example.test/wp-json/ihq/v1/aicoach/appointment';

// A fetch that records what it was given and answers with a status and a body.
function fakeFetch( status, body, calls = [] ) {
	const fn = async ( url, options ) => {
		calls.push( { url, options } );
		return { status, json: async () => body };
	};
	fn.calls = calls;
	return fn;
}

test( 'the request posts the selection with the nonce and returns the link', async () => {
	const fetchFn = fakeFetch( 201, { link: 'https://example.test/appointment/?t=abc', startsAt: '2026-10-09T12:30:00+00:00' } );
	const result = await requestAppointmentLink( { selection: SELECTION, url: URL_OF_ROUTE, nonce: 'nonce-1', fetchFn } );
	assert.deepEqual( result, { ok: true, link: 'https://example.test/appointment/?t=abc', errors: [], failed: false } );
	assert.deepEqual( fetchFn.calls, [ {
		url: URL_OF_ROUTE,
		options: {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': 'nonce-1' },
			credentials: 'same-origin',
			body: JSON.stringify( SELECTION ),
		},
	} ] );
} );

test( 'a refusal carries the server\'s error codes and is not a failure', async () => {
	const fetchFn = fakeFetch( 400, { errors: [ 'date-missing', 'time-missing' ] } );
	const result = await requestAppointmentLink( { selection: SELECTION, url: URL_OF_ROUTE, nonce: 'n', fetchFn } );
	assert.deepEqual( result, { ok: false, link: null, errors: [ 'date-missing', 'time-missing' ], failed: false } );
} );

test( 'a refusal keeps only the codes that are text', async () => {
	const fetchFn = fakeFetch( 400, { errors: [ 'time-in-past', 5, null, { code: 'x' } ] } );
	const result = await requestAppointmentLink( { selection: SELECTION, url: URL_OF_ROUTE, nonce: 'n', fetchFn } );
	assert.deepEqual( result.errors, [ 'time-in-past' ] );
} );

const FAILED = { ok: false, link: null, errors: [], failed: true };

test( 'a refusal with no usable codes is a failure', async () => {
	const empty = await requestAppointmentLink( { selection: SELECTION, url: URL_OF_ROUTE, nonce: 'n', fetchFn: fakeFetch( 400, { errors: [] } ) } );
	const junk = await requestAppointmentLink( { selection: SELECTION, url: URL_OF_ROUTE, nonce: 'n', fetchFn: fakeFetch( 400, { errors: [ 5 ] } ) } );
	const missing = await requestAppointmentLink( { selection: SELECTION, url: URL_OF_ROUTE, nonce: 'n', fetchFn: fakeFetch( 400, {} ) } );
	assert.deepEqual( [ empty, junk, missing ], [ FAILED, FAILED, FAILED ] );
} );

test( 'a limit, a server error or an unknown status is a failure', async () => {
	for ( const status of [ 429, 500, 403, 200, 404 ] ) {
		const result = await requestAppointmentLink( { selection: SELECTION, url: URL_OF_ROUTE, nonce: 'n', fetchFn: fakeFetch( status, { link: 'https://example.test/x', errors: [ 'time-in-past' ] } ) } );
		assert.deepEqual( result, FAILED, String( status ) );
	}
} );

test( 'a created answer without a link is a failure', async () => {
	for ( const body of [ {}, { link: '' }, { link: 5 }, null ] ) {
		const result = await requestAppointmentLink( { selection: SELECTION, url: URL_OF_ROUTE, nonce: 'n', fetchFn: fakeFetch( 201, body ) } );
		assert.deepEqual( result, FAILED, JSON.stringify( body ) );
	}
} );

test( 'no network is a failure, not a thrown error', async () => {
	const fetchFn = async () => { throw new TypeError( 'Failed to fetch' ); };
	assert.deepEqual( await requestAppointmentLink( { selection: SELECTION, url: URL_OF_ROUTE, nonce: 'n', fetchFn } ), FAILED );
} );

test( 'an answer that is not JSON is a failure', async () => {
	const fetchFn = async () => ( { status: 201, json: async () => { throw new SyntaxError( 'Unexpected token <' ); } } );
	assert.deepEqual( await requestAppointmentLink( { selection: SELECTION, url: URL_OF_ROUTE, nonce: 'n', fetchFn } ), FAILED );
} );

// A clipboard whose writes are recorded; ClipboardItem keeps what it was given.
function fakeClipboard( { write = true, writeText = true, rejectWrite = false, rejectWriteText = false } = {} ) {
	const written = { items: [], texts: [] };
	const clipboard = {};
	if ( write ) {
		clipboard.write = async ( items ) => {
			if ( rejectWrite ) {
				throw new Error( 'NotAllowedError' );
			}
			for ( const item of items ) {
				// Like a browser, wait for the item's content before the write finishes.
				written.items.push( await ( await item.data[ 'text/plain' ] ).text() );
			}
		};
	}
	if ( writeText ) {
		clipboard.writeText = async ( text ) => {
			if ( rejectWriteText ) {
				throw new Error( 'NotAllowedError' );
			}
			written.texts.push( text );
		};
	}
	class ClipboardItemClass {
		constructor( data ) {
			this.data = data;
		}
	}
	return { clipboard, ClipboardItemClass, written };
}

test( 'copying hands the clipboard a pending text at once and finishes when it arrives', async () => {
	const { clipboard, ClipboardItemClass, written } = fakeClipboard();
	let arrive;
	const text = new Promise( ( resolve ) => { arrive = resolve; } );
	const copying = copyToClipboard( text, { clipboard, ClipboardItemClass } );
	assert.deepEqual( written, { items: [], texts: [] } );
	arrive( 'the link' );
	assert.deepEqual( await copying, { ok: true } );
	assert.deepEqual( written, { items: [ 'the link' ], texts: [] } );
} );

test( 'copying a text that is already there works the same way', async () => {
	const { clipboard, ClipboardItemClass, written } = fakeClipboard();
	assert.deepEqual( await copyToClipboard( 'the link', { clipboard, ClipboardItemClass } ), { ok: true } );
	assert.deepEqual( written, { items: [ 'the link' ], texts: [] } );
} );

test( 'without ClipboardItem it waits for the text and writes it plainly', async () => {
	const { clipboard, written } = fakeClipboard();
	assert.deepEqual( await copyToClipboard( Promise.resolve( 'the link' ), { clipboard } ), { ok: true } );
	assert.deepEqual( written, { items: [], texts: [ 'the link' ] } );
} );

test( 'a clipboard with no write() still takes the plain call', async () => {
	const { clipboard, ClipboardItemClass, written } = fakeClipboard( { write: false } );
	assert.deepEqual( await copyToClipboard( 'the link', { clipboard, ClipboardItemClass } ), { ok: true } );
	assert.deepEqual( written, { items: [], texts: [ 'the link' ] } );
} );

test( 'when the item write is refused the plain call is tried', async () => {
	const { clipboard, ClipboardItemClass, written } = fakeClipboard( { rejectWrite: true } );
	assert.deepEqual( await copyToClipboard( 'the link', { clipboard, ClipboardItemClass } ), { ok: true } );
	assert.deepEqual( written, { items: [], texts: [ 'the link' ] } );
} );

test( 'copying reports a refusal of every kind of write instead of throwing', async () => {
	const { clipboard, ClipboardItemClass } = fakeClipboard( { rejectWrite: true, rejectWriteText: true } );
	assert.deepEqual( await copyToClipboard( 'the link', { clipboard, ClipboardItemClass } ), { ok: false } );
	const plainOnly = fakeClipboard( { write: false, rejectWriteText: true } );
	assert.deepEqual( await copyToClipboard( 'the link', { clipboard: plainOnly.clipboard } ), { ok: false } );
} );

test( 'a text that never arrives is a failed copy, whichever write is used', async () => {
	const withItem = fakeClipboard();
	const failing = () => Promise.reject( new Error( 'no link' ) );
	assert.deepEqual( await copyToClipboard( failing(), { clipboard: withItem.clipboard, ClipboardItemClass: withItem.ClipboardItemClass } ), { ok: false } );
	assert.deepEqual( withItem.written, { items: [], texts: [] } );
	const plainOnly = fakeClipboard( { write: false } );
	assert.deepEqual( await copyToClipboard( failing(), { clipboard: plainOnly.clipboard } ), { ok: false } );
	assert.deepEqual( plainOnly.written, { items: [], texts: [] } );
} );

test( 'copying reports a browser without a clipboard, even when the text never arrives', async () => {
	assert.deepEqual( await copyToClipboard( 'the link', {} ), { ok: false } );
	assert.deepEqual( await copyToClipboard( 'the link' ), { ok: false } );
	assert.deepEqual( await copyToClipboard( 'the link', { clipboard: null } ), { ok: false } );
	assert.deepEqual( await copyToClipboard( Promise.reject( new Error( 'no link' ) ), {} ), { ok: false } );
	assert.deepEqual( await copyToClipboard( 'the link', { clipboard: {} } ), { ok: false } );
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

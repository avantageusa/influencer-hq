// ENGR-7511 — js/ihq-aicoach-events.js (a classic script, not a module) run in
// a VM with a stub page and fetch: register() posts when ANY channel has a
// value (an email is no longer required in the browser), and refuses only
// when there is none.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const SCRIPT = readFileSync( fileURLToPath( new URL( '../ihq-aicoach-events.js', import.meta.url ) ), 'utf8' );
const REST_URL = 'https://ihq.example.test/wp-json/ihq/v1/create-account';
const MISSING_CHANNEL = 'Please select at least one communication method.';

// A page with no coach forms: everything register() sends comes from detail.
function loadEvents() {
	const fetchCalls = [];
	const document = {
		getElementById: () => null,
		querySelector: () => null,
		documentElement: { getAttribute: () => 'en' },
		addEventListener: () => {},
	};
	const window = {
		IHQ_AICOACH_EVENTS: { restUrl: REST_URL, nonce: 'n0nce', i18n: { missingChannel: MISSING_CHANNEL } },
		addEventListener: () => {},
		sessionStorage: { setItem: () => {} },
		location: { href: '' },
	};
	const fetch = ( url, init ) => {
		fetchCalls.push( { url, init } );
		return Promise.resolve( { json: () => Promise.resolve( { success: true, redirectUrl: 'https://ihq.example.test/portal/portal-home/' } ) } );
	};
	vm.runInNewContext( SCRIPT, { window, document, fetch } );
	return { events: window.ihqCoachEvents, fetchCalls, window };
}

function postedBody( call ) {
	return JSON.parse( call.init.body );
}

test( 'Telegram only (the reported case) is posted to create-account', async () => {
	const { events, fetchCalls, window } = loadEvents();
	const result = await events.register( {
		firstName: 'Telegram',
		channels: [ { channel: 'telegram', value: '@Avantage123' } ],
		redirect: false,
	} );
	assert.equal( result.success, true );
	assert.equal( result.redirectUrl, 'https://ihq.example.test/portal/portal-home/' );
	assert.equal( fetchCalls.length, 1 );
	assert.equal( fetchCalls[ 0 ].url, REST_URL );
	assert.equal( fetchCalls[ 0 ].init.method, 'POST' );
	assert.equal( fetchCalls[ 0 ].init.headers[ 'X-WP-Nonce' ], 'n0nce' );
	assert.deepEqual( postedBody( fetchCalls[ 0 ] ), {
		firstName: 'Telegram',
		lastName: '',
		username: '',
		channels: [ { channel: 'telegram', value: '@Avantage123' } ],
		language: 'en',
		duration: '',
	} );
	assert.equal( window.location.href, '' );
} );

test( 'a phone-number channel with no "@" is posted too', async () => {
	const { events, fetchCalls } = loadEvents();
	await events.register( { channels: [ { channel: 'whatsapp', value: '+66812345678' } ], redirect: false } );
	assert.equal( fetchCalls.length, 1 );
	assert.deepEqual( postedBody( fetchCalls[ 0 ] ).channels, [ { channel: 'whatsapp', value: '+66812345678' } ] );
} );

test( 'no channel with a value is refused without a request', async () => {
	const { events, fetchCalls } = loadEvents();
	const blankOnly = await events.register( { channels: [ { channel: 'telegram', value: '   ' }, null ], redirect: false } );
	assert.deepEqual( JSON.parse( JSON.stringify( blankOnly ) ), { success: false, error: MISSING_CHANNEL } );
	const none = await events.register( { redirect: false } );
	assert.deepEqual( JSON.parse( JSON.stringify( none ) ), { success: false, error: MISSING_CHANNEL } );
	assert.equal( fetchCalls.length, 0 );
} );

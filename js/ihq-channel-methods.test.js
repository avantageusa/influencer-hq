import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createFakeDocument, build } from './aicoach/fake-dom.js';
import {
	METHOD_DISABLED_ERROR,
	METHOD_ATTRIBUTE,
	UNAVAILABLE_NOTICE_ATTRIBUTE,
	DIVIDER_ATTRIBUTE,
	METHOD_LABELS,
	unavailableMessage,
	methodRefusal,
	hideRefusedMethod,
} from './ihq-channel-methods.js';

/*
 * What the page's AJAX handlers answer in each state of the switch
 * (inc/ihq-channel-methods.php, tests/channel-method-ajax-refusal.test.php):
 * a method on, an absent feature and a failed call with no earlier answer all
 * let the handler run as today; only a method off is refused.
 */
const METHOD_ON_ANSWER = { success: true, data: { signup_token: 'token', expires_minutes: 15 } };
const ABSENT_FEATURE_ANSWER = { success: false, data: { message: 'Invalid email address' } };
const FAILED_CALL_ANSWER = { success: true, data: { server_nonce: 'nonce' } };
const EMAIL_OFF_ANSWER = {
	success: false,
	data: { error: 'method_disabled', method: 'email', message: "Email isn't available right now" },
};
const TELEGRAM_OFF_ANSWER = {
	success: false,
	data: { error: 'method_disabled', method: 'telegram', message: "Telegram isn't available right now" },
};

function setup() {
	const document = createFakeDocument();
	const root = build( document, [
		'div',
		{ id: 'auth-login-step-email' },
		[ 'div', { id: 'email-field', [ METHOD_ATTRIBUTE ]: 'email' } ],
		[ 'button', { id: 'email-send', [ METHOD_ATTRIBUTE ]: 'email' } ],
		[ 'div', { id: 'login-error', class: 'auth-err' } ],
		[ 'div', { id: 'email-unavailable', class: 'auth-err', [ UNAVAILABLE_NOTICE_ATTRIBUTE ]: 'email' } ],
		[ 'div', { id: 'or-divider', [ DIVIDER_ATTRIBUTE ]: '' } ],
		[ 'button', { id: 'telegram-button', [ METHOD_ATTRIBUTE ]: 'telegram' } ],
		[ 'div', { id: 'telegram-error', class: 'auth-err' } ],
		[ 'div', { id: 'telegram-unavailable', class: 'auth-err', [ UNAVAILABLE_NOTICE_ATTRIBUTE ]: 'telegram' } ],
	] );
	const byId = ( id ) => root.querySelector( '#' + id );
	const displays = () => Object.fromEntries(
		[ 'email-field', 'email-send', 'login-error', 'email-unavailable', 'or-divider', 'telegram-button', 'telegram-error', 'telegram-unavailable' ]
			.map( ( id ) => [ id, byId( id ).style.display ] )
	);
	const notices = () => ( {
		email: byId( 'email-unavailable' ).textContent,
		telegram: byId( 'telegram-unavailable' ).textContent,
	} );
	return { root, displays, notices };
}

const NOTHING_HIDDEN = {
	'email-field': undefined,
	'email-send': undefined,
	'login-error': undefined,
	'email-unavailable': undefined,
	'or-divider': undefined,
	'telegram-button': undefined,
	'telegram-error': undefined,
	'telegram-unavailable': undefined,
};
const NO_NOTICE = { email: '', telegram: '' };

test( 'names shared with the server', () => {
	assert.equal( METHOD_DISABLED_ERROR, 'method_disabled' );
	assert.equal( METHOD_ATTRIBUTE, 'data-ihq-channel-method' );
	assert.equal( UNAVAILABLE_NOTICE_ATTRIBUTE, 'data-ihq-channel-method-unavailable' );
	assert.equal( DIVIDER_ATTRIBUTE, 'data-ihq-channel-method-divider' );
	assert.deepEqual( Object.keys( METHOD_LABELS ), [ 'email', 'sms', 'telegram', 'line', 'whatsapp', 'zalo', 'kakaotalk', 'wechat' ] );
	assert.deepEqual( Object.values( METHOD_LABELS ), [ 'Email', 'SMS', 'Telegram', 'LINE', 'WhatsApp', 'Zalo', 'KakaoTalk', 'WeChat' ] );
	assert.equal( Object.isFrozen( METHOD_LABELS ), true );
} );

test( 'method on: the success answer is no refusal and hides nothing', () => {
	const { root, displays, notices } = setup();
	assert.equal( methodRefusal( METHOD_ON_ANSWER ), null );
	assert.equal( hideRefusedMethod( { root, response: METHOD_ON_ANSWER } ), null );
	assert.deepEqual( displays(), NOTHING_HIDDEN );
	assert.deepEqual( notices(), NO_NOTICE );
} );

test( 'method off: email is refused, its controls and the divider hide, its notice shows, Telegram stays', () => {
	const { root, displays, notices } = setup();
	assert.deepEqual( methodRefusal( EMAIL_OFF_ANSWER ), { method: 'email', message: "Email isn't available right now" } );
	assert.deepEqual(
		hideRefusedMethod( { root, response: EMAIL_OFF_ANSWER } ),
		{ method: 'email', message: "Email isn't available right now" }
	);
	assert.deepEqual(
		displays(),
		Object.assign( {}, NOTHING_HIDDEN, { 'email-field': 'none', 'email-send': 'none', 'email-unavailable': 'block', 'or-divider': 'none' } )
	);
	assert.deepEqual( notices(), { email: "Email isn't available right now", telegram: '' } );
} );

test( 'method off: Telegram is refused, its button and the divider hide, its notice shows, email stays', () => {
	const { root, displays, notices } = setup();
	assert.deepEqual(
		hideRefusedMethod( { root, response: TELEGRAM_OFF_ANSWER } ),
		{ method: 'telegram', message: "Telegram isn't available right now" }
	);
	assert.deepEqual(
		displays(),
		Object.assign( {}, NOTHING_HIDDEN, { 'or-divider': 'none', 'telegram-button': 'none', 'telegram-unavailable': 'block' } )
	);
	assert.deepEqual( notices(), { email: '', telegram: "Telegram isn't available right now" } );
} );

test( 'the notice outlives a reset: clearing the error boxes leaves it showing', () => {
	const { root, displays, notices } = setup();
	hideRefusedMethod( { root, response: EMAIL_OFF_ANSWER } );
	// What ihqAuthLoginResetPanels and the Back paths do to the page's error boxes.
	for ( const id of [ 'login-error', 'telegram-error' ] ) {
		const box = root.querySelector( '#' + id );
		box.textContent = '';
		box.style.display = 'none';
	}
	assert.equal( displays()[ 'email-unavailable' ], 'block' );
	assert.deepEqual( notices(), { email: "Email isn't available right now", telegram: '' } );
} );

test( 'absent feature: the handler answers as today, so its errors hide nothing', () => {
	const { root, displays, notices } = setup();
	assert.equal( methodRefusal( ABSENT_FEATURE_ANSWER ), null );
	assert.equal( hideRefusedMethod( { root, response: ABSENT_FEATURE_ANSWER } ), null );
	assert.deepEqual( displays(), NOTHING_HIDDEN );
	assert.deepEqual( notices(), NO_NOTICE );
} );

test( 'failed call: the handler keeps today\'s lists, and a failed AJAX call hides nothing', () => {
	const { root, displays } = setup();
	for ( const answer of [ FAILED_CALL_ANSWER, undefined, null, '', 'not json', 0 ] ) {
		assert.equal( methodRefusal( answer ), null );
		assert.equal( hideRefusedMethod( { root, response: answer } ), null );
	}
	assert.deepEqual( displays(), NOTHING_HIDDEN );
} );

test( 'not a refusal: other error shapes', () => {
	const notRefusals = [
		{ success: false },
		{ success: false, data: null },
		{ success: false, data: 'Invalid security token' },
		{ success: false, data: { error: 'method_not_configured', method: 'email', message: 'x' } },
		{ success: false, data: { method: 'email', message: "Email isn't available right now" } },
		{ success: false, data: { error: 'method_disabled', message: "Email isn't available right now" } },
		{ success: false, data: { error: 'method_disabled', method: 'fax' } },
		{ success: false, data: { error: 'method_disabled', method: 'toString' } },
		{ success: false, data: { error: 'method_disabled', method: 'email"] , [x="' } },
		{ success: true, data: { error: 'method_disabled', method: 'email' } },
	];
	const { root, displays } = setup();
	for ( const answer of notRefusals ) {
		assert.equal( methodRefusal( answer ), null, JSON.stringify( answer ) );
		assert.equal( hideRefusedMethod( { root, response: answer } ), null );
	}
	assert.deepEqual( displays(), NOTHING_HIDDEN );
} );

test( 'a refusal without a message gets the standard one', () => {
	assert.deepEqual(
		methodRefusal( { success: false, data: { error: 'method_disabled', method: 'kakaotalk' } } ),
		{ method: 'kakaotalk', message: "KakaoTalk isn't available right now" }
	);
	assert.deepEqual(
		methodRefusal( { success: false, data: { error: 'method_disabled', method: 'line', message: '' } } ),
		{ method: 'line', message: "LINE isn't available right now" }
	);
	assert.deepEqual(
		methodRefusal( { success: false, data: { error: 'method_disabled', method: 'sms', message: 7 } } ),
		{ method: 'sms', message: "SMS isn't available right now" }
	);
	assert.equal( unavailableMessage( 'wechat' ), "WeChat isn't available right now" );
} );

test( 'the server\'s message wins over the standard one', () => {
	assert.deepEqual(
		methodRefusal( { success: false, data: { error: 'method_disabled', method: 'email', message: 'Le courriel n\'est pas disponible' } } ),
		{ method: 'email', message: 'Le courriel n\'est pas disponible' }
	);
} );

test( 'a refusal with no marked controls hides nothing and still reports the refusal', () => {
	const document = createFakeDocument();
	const root = build( document, [ 'div', {}, [ 'div', { id: 'login-error' } ] ] );
	assert.deepEqual(
		hideRefusedMethod( { root, response: TELEGRAM_OFF_ANSWER } ),
		{ method: 'telegram', message: "Telegram isn't available right now" }
	);
	assert.equal( root.querySelector( '#login-error' ).style.display, undefined );
} );

test( 'the browser build publishes the functions for the inline scripts', async () => {
	globalThis.window = {};
	try {
		await import( './ihq-channel-methods.js?browser' );
		assert.deepEqual( Object.keys( globalThis.window.ihqChannelMethods ), [ 'methodRefusal', 'hideRefusedMethod' ] );
		assert.equal( Object.isFrozen( globalThis.window.ihqChannelMethods ), true );
		assert.deepEqual(
			globalThis.window.ihqChannelMethods.methodRefusal( EMAIL_OFF_ANSWER ),
			{ method: 'email', message: "Email isn't available right now" }
		);
	} finally {
		delete globalThis.window;
	}
} );

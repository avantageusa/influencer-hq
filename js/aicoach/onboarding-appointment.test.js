import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createFakeDocument, createEvent } from './fake-dom.js';
import { createFakeClock } from './fake-clock.js';
import { estimateDurationMs } from './commentary.js';
import { createEmitter } from './onboarding-events.js';
import { createStringTable } from './onboarding-strings.js';
import { createApi } from './onboarding-boot.js';
import { SEQUENCE, BRANCHES, startStage, nextStage } from './onboarding-sequence.js';
import { createEngine, ADVANCE_MODES } from './onboarding-engine.js';
import { CHOICE, ERROR, validateAppointment } from './appointment.js';
import { appointmentErrorKey, requestAppointmentLink, copyToClipboard, timeZoneChoices } from './appointment-screen.js';
import { I18N_EN } from './locales.js';
import { PICK_ADVANCE_DELAY_MS as QUESTIONS_PICK_DELAY_MS } from './onboarding-screens-questions.js';
import {
	APPOINTMENT_STRINGS_EN,
	STAGE,
	TIME_UP_CHOICE,
	PICK_ADVANCE_DELAY_MS,
	COPIED_ADVANCE_DELAY_MS,
	appointmentScreens,
	installAppointment,
} from './onboarding-appointment.js';

const STATE = { stage: '', answers: {}, platforms: [], handles: {}, language: '', drafts: {}, completed: false, completed_at: '' };
const LINK = 'https://example.test/appointment/?t=signed';
// The fake clock starts at 0: 1970-01-01T00:00Z.
const PAST = { date: '1969-12-31', time: '10:00', timeZone: 'UTC' };
const FUTURE = { date: '1970-01-02', time: '10:30', timeZone: 'Asia/Tokyo' };

// Real rules and helpers from the landing flow, with a fixed zone list for the select.
const RULES = {
	CHOICE,
	validateAppointment,
	listTimeZones: () => [ 'Asia/Tokyo', 'Europe/Belgrade', 'UTC' ],
	getDefaultTimeZone: () => 'Europe/Belgrade',
};
const HELPERS = { appointmentErrorKey, requestAppointmentLink, copyToClipboard, timeZoneChoices };

/**
 * @param {Object} [options]
 * @param {string} [options.stage]     Where the onboarding opens.
 * @param {Array}  [options.responses] What the appointment route answers, in order: { status, body }
 *                                     or 'network' for a failed request. 201 with LINK when it runs out.
 * @param {Array}  [options.copies]    Whether each clipboard write works, in order (true when it runs out).
 */
function setup( { stage = 'time_up', responses = [], copies = [] } = {} ) {
	const document = createFakeDocument();
	const content = document.createElement( 'div' );
	document.body.append( content );
	const events = createEmitter();
	const strings = createStringTable();
	const config = { state: { ...STATE, stage }, progressUrl: '/p', completeUrl: '/c', appointmentUrl: '/appointment', nonce: 'n' };
	const api = createApi( { config, events, strings } );
	api.shell = { content, say: () => Promise.resolve(), close: () => {} };
	const posts = [];
	const clock = createFakeClock();
	const engine = createEngine( {
		api,
		sequence: { SEQUENCE, startStage, nextStage },
		document,
		postJson: async ( request ) => {
			posts.push( request.body );
			return { ok: true, status: 200, data: null };
		},
		fetchFn: () => {},
		historyGuard: { install: () => {}, uninstall: () => {} },
		estimateDurationMs,
		setTimeout: clock.setTimeout,
		clearTimeout: clock.clearTimeout,
	} );
	api.screens = { register: engine.register, ADVANCE_MODES };
	api.save = engine.save;

	const requests = [];
	// Each request is held until release() so a test can look at the screen while it is in flight.
	const held = [];
	const fetchFn = ( url, init ) => {
		requests.push( { url, method: init.method, nonce: init.headers[ 'X-WP-Nonce' ], body: JSON.parse( init.body ) } );
		const response = responses.shift() || { status: 201, body: { link: LINK, startsAt: '1970-01-01T00:30:00+00:00' } };
		return new Promise( ( resolve, reject ) => held.push( () => {
			if ( 'network' === response ) {
				reject( new Error( 'offline' ) );
				return;
			}
			resolve( { status: response.status, json: async () => response.body } );
		} ) );
	};
	const written = [];
	const clipboard = {
		writeText: async ( text ) => {
			const works = copies.length > 0 ? copies.shift() : true;
			if ( ! works ) {
				throw new Error( 'denied' );
			}
			written.push( text );
		},
	};
	installAppointment( api, {
		rules: RULES,
		helpers: HELPERS,
		env: { clipboard, ClipboardItemClass: undefined, fetchFn, now: clock.now, setTimeout: clock.setTimeout, clearTimeout: clock.clearTimeout },
	} );
	// The closing screen, so moving on has somewhere to go.
	engine.register( { id: 'closing', advanceMode: ADVANCE_MODES.MANUAL, render: () => ( {} ) } );
	engine.start();

	const screen = () => content.querySelector( 'section' );
	const option = ( value ) => screen().querySelector( `button[data-value="${ value }"]` );
	const fieldOf = ( name ) => screen().querySelector( `[data-field="${ name }"]` );
	const copyButton = () => screen().querySelector( 'button.ihq-onb-appt__copy' );
	const errorLines = () => screen().querySelector( 'div.ihq-onb-appt__errors' ).children.map( ( line ) => line.textContent );
	const status = () => screen().querySelector( 'p.ihq-onb-appt__status' );
	const linkBox = () => screen().querySelector( 'input.ihq-onb-appt__link' );
	// Lets the held requests answer and the screen's awaits run.
	const settle = async () => {
		for ( let round = 0; round < 10; round++ ) {
			held.splice( 0 ).forEach( ( release ) => release() );
			await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
		}
	};
	return { document, api, engine, clock, posts, requests, written, strings, screen, option, fieldOf, copyButton, errorLines, status, linkBox, settle };
}

const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

// --- Contract ---

test( 'the stages come before closing, and Continue is the branch to it', () => {
	assert.deepEqual( Object.values( STAGE ), SEQUENCE.slice( -3, -1 ) );
	assert.deepEqual( BRANCHES.filter( ( branch ) => STAGE.TIME_UP === branch.from ), [ { from: 'time_up', choice: TIME_UP_CHOICE.CONTINUE, to: 'closing' } ] );
	assert.deepEqual( TIME_UP_CHOICE, { CONTINUE: 'continue', APPOINTMENT: 'appointment' } );
	assert.equal( PICK_ADVANCE_DELAY_MS, QUESTIONS_PICK_DELAY_MS );
	assert.equal( COPIED_ADVANCE_DELAY_MS, 4000 );
} );

test( 'every error the rules or the server can give has its message', () => {
	Object.values( ERROR ).forEach( ( code ) => {
		assert.equal( 'string' === typeof APPOINTMENT_STRINGS_EN[ appointmentErrorKey( code ) ], true, code );
	} );
} );

test( 'the wording is the landing flow\'s wherever the landing flow has the same text', () => {
	const shared = Object.keys( APPOINTMENT_STRINGS_EN ).filter( ( key ) => key in I18N_EN );
	assert.deepEqual( shared.sort(), [
		'appointmentCopied', 'appointmentCopy', 'appointmentCopyFailed', 'appointmentDate', 'appointmentHint',
		'appointmentIn30Minutes', 'appointmentInAnHour', 'appointmentOther', 'appointmentReady', 'appointmentRequestFailed',
		'appointmentTime', 'appointmentTimeZone', 'appointmentTitle',
		...Object.values( ERROR ).map( appointmentErrorKey ),
		'timeUpSetAppointment',
	].sort() );
	shared.forEach( ( key ) => assert.equal( APPOINTMENT_STRINGS_EN[ key ], I18N_EN[ key ], key ) );
} );

test( 'two screens: Talk more? is a pick, scheduling moves on only by its own control', () => {
	const definitions = appointmentScreens( { advanceModes: ADVANCE_MODES, rules: RULES, helpers: HELPERS, env: {} } );
	assert.deepEqual( definitions.map( ( definition ) => [ definition.id, definition.advanceMode ] ), [
		[ 'time_up', 'pick' ],
		[ 'appointment', 'manual' ],
	] );
	const registered = [];
	const added = [];
	installAppointment( { strings: { add: ( locale, table ) => added.push( [ locale, table ] ) }, screens: { register: ( definition ) => registered.push( definition.id ), ADVANCE_MODES } }, { rules: RULES, helpers: HELPERS, env: {} } );
	assert.deepEqual( registered, [ 'time_up', 'appointment' ] );
	assert.deepEqual( added, [ [ 'en', APPOINTMENT_STRINGS_EN ] ] );
} );

// --- Talk more? ---

test( 'Talk more? shows its title and two unpicked choices named by it', () => {
	const { screen, option } = setup();
	const title = screen().querySelector( 'h2' );
	assert.equal( title.textContent, 'Talk more?' );
	const group = screen().querySelector( 'div[role="radiogroup"]' );
	assert.equal( group.getAttribute( 'aria-labelledby' ), title.getAttribute( 'id' ) );
	assert.deepEqual( group.querySelectorAll( 'button' ).map( ( button ) => button.textContent ), [ 'Continue to IHQ portal', 'Set an Appointment' ] );
	assert.deepEqual( group.querySelectorAll( 'button' ).map( ( button ) => [ button.getAttribute( 'role' ), button.getAttribute( 'aria-checked' ), button.getAttribute( 'tabindex' ) ] ), [
		[ 'radio', 'false', '0' ],
		[ 'radio', 'false', '-1' ],
	] );
	assert.equal( option( 'continue' ).className, 'ihq-onb-q__option ihq-onb-appt__option' );
	assert.equal( screen().querySelector( 'p.ihq-onb__error' ).hidden, true );
} );

test( 'Set an Appointment goes to scheduling after the pause, and saves only the stage', async () => {
	const { engine, clock, posts, option } = setup();
	option( 'appointment' ).click();
	assert.equal( option( 'appointment' ).getAttribute( 'aria-checked' ), 'true' );
	clock.tick( PICK_ADVANCE_DELAY_MS - 1 );
	assert.equal( engine.current(), 'time_up' );
	clock.tick( 1 );
	assert.equal( engine.current(), 'appointment' );
	await flush();
	assert.deepEqual( posts, [ { stage: 'appointment' } ] );
} );

test( 'Continue to IHQ portal goes to the closing screen', async () => {
	const { engine, clock, posts, option } = setup();
	option( 'continue' ).click();
	clock.tick( PICK_ADVANCE_DELAY_MS );
	assert.equal( engine.current(), 'closing' );
	await flush();
	assert.deepEqual( posts, [ { stage: 'closing' } ] );
} );

test( 'changing the pick restarts the pause; picking the same one again does not', () => {
	const { engine, clock, option } = setup();
	option( 'continue' ).click();
	clock.tick( PICK_ADVANCE_DELAY_MS - 100 );
	option( 'continue' ).click();
	clock.tick( 100 );
	assert.equal( engine.current(), 'closing' );

	const other = setup();
	other.option( 'continue' ).click();
	other.clock.tick( PICK_ADVANCE_DELAY_MS - 100 );
	other.option( 'appointment' ).click();
	assert.equal( other.option( 'continue' ).getAttribute( 'aria-checked' ), 'false' );
	other.clock.tick( 100 );
	assert.equal( other.engine.current(), 'time_up' );
	other.clock.tick( PICK_ADVANCE_DELAY_MS - 100 );
	assert.equal( other.engine.current(), 'appointment' );
} );

test( 'arrow keys move the pick like a click, wrapping around', () => {
	const { document, clock, engine, option } = setup();
	option( 'continue' ).dispatchEvent( createEvent( 'keydown', { key: 'ArrowUp' } ) );
	assert.equal( option( 'appointment' ).getAttribute( 'aria-checked' ), 'true' );
	assert.equal( document.activeElement, option( 'appointment' ) );
	option( 'appointment' ).dispatchEvent( createEvent( 'keydown', { key: 'ArrowDown' } ) );
	assert.equal( option( 'continue' ).getAttribute( 'aria-checked' ), 'true' );
	option( 'continue' ).dispatchEvent( createEvent( 'keydown', { key: 'Enter' } ) );
	assert.equal( option( 'continue' ).getAttribute( 'aria-checked' ), 'true' );
	clock.tick( PICK_ADVANCE_DELAY_MS );
	assert.equal( engine.current(), 'closing' );
} );

test( 'asked to move on with nothing picked, it stays and says so; a pick hides the message', () => {
	const { engine, screen, option } = setup();
	assert.equal( engine.advance(), false );
	const error = screen().querySelector( 'p.ihq-onb__error' );
	assert.equal( error.hidden, false );
	assert.equal( error.textContent, 'Choose one to continue.' );
	option( 'appointment' ).click();
	assert.equal( error.hidden, true );
} );

test( 'moving on from Talk more? before its pause ends cancels the pause', () => {
	const { engine, clock, option } = setup();
	option( 'appointment' ).click();
	assert.equal( clock.pendingCount(), 1 );
	engine.advance();
	assert.equal( engine.current(), 'appointment' );
	assert.equal( clock.pendingCount(), 0 );
} );

// --- Set your appointment ---

test( 'scheduling shows the design\'s text, three unpicked choices, the copy button and the hint; Other\'s fields hidden', () => {
	const { screen, option, copyButton, fieldOf, linkBox } = setup( { stage: 'appointment' } );
	assert.equal( screen().querySelector( 'h2' ).textContent, "Yes, I'm Ready..." );
	const title = screen().querySelector( 'p.ihq-onb-appt__subtitle' );
	assert.equal( title.textContent, 'Set your appointment' );
	const group = screen().querySelector( 'div[role="radiogroup"]' );
	assert.equal( group.getAttribute( 'aria-labelledby' ), title.getAttribute( 'id' ) );
	assert.deepEqual( group.querySelectorAll( 'button' ).map( ( button ) => button.textContent ), [ 'In 30 minutes', 'In an hour', 'Other' ] );
	assert.equal( option( 'in-30-minutes' ).getAttribute( 'aria-checked' ), 'false' );
	assert.equal( screen().querySelector( 'div.ihq-onb-appt__other' ).hidden, true );
	assert.equal( copyButton().textContent, 'Copy Your Appointment Link' );
	assert.equal( copyButton().getAttribute( 'type' ), 'button' );
	assert.equal( screen().querySelector( 'p.ihq-onb-appt__hint' ).textContent, 'Open this link at your appointment time to join your video with Sami.' );
	assert.equal( linkBox().hidden, true );
	assert.equal( linkBox().getAttribute( 'aria-label' ), 'Your appointment link' );
	assert.deepEqual( [ fieldOf( 'date' ).getAttribute( 'type' ), fieldOf( 'time' ).getAttribute( 'type' ), fieldOf( 'time-zone' ).tagName ], [ 'date', 'time', 'SELECT' ] );
} );

test( 'the Timezone list has the zones, with the Influencer\'s own chosen', () => {
	const { fieldOf } = setup( { stage: 'appointment' } );
	assert.deepEqual( fieldOf( 'time-zone' ).querySelectorAll( 'option' ).map( ( option ) => [ option.getAttribute( 'value' ), option.textContent ] ), [
		[ 'Asia/Tokyo', 'Asia/Tokyo' ],
		[ 'Europe/Belgrade', 'Europe/Belgrade' ],
		[ 'UTC', 'UTC' ],
	] );
	assert.equal( fieldOf( 'time-zone' ).value, 'Europe/Belgrade' );
} );

test( 'with no choice, Copy asks nothing of the server, copies nothing and says to choose a time', async () => {
	const { copyButton, errorLines, requests, written, engine, settle } = setup( { stage: 'appointment' } );
	copyButton().click();
	await settle();
	assert.deepEqual( errorLines(), [ 'Please choose when you would like your appointment.' ] );
	assert.deepEqual( requests, [] );
	assert.deepEqual( written, [] );
	assert.equal( copyButton().disabled, false );
	assert.equal( engine.current(), 'appointment' );
} );

for ( const [ value, label ] of [ [ 'in-30-minutes', 'In 30 minutes' ], [ 'in-an-hour', 'In an hour' ] ] ) {
	test( label + ': the link is asked for, copied and confirmed, and the closing screen follows 4 seconds later', async () => {
		const { option, copyButton, status, requests, written, engine, clock, posts, errorLines, settle } = setup( { stage: 'appointment' } );
		option( value ).click();
		assert.equal( option( value ).getAttribute( 'aria-checked' ), 'true' );
		copyButton().click();
		assert.equal( copyButton().disabled, true );
		await settle();
		assert.deepEqual( requests, [ { url: '/appointment', method: 'POST', nonce: 'n', body: { choice: value, date: '', time: '', timeZone: 'Europe/Belgrade' } } ] );
		assert.deepEqual( written, [ LINK ] );
		assert.equal( status().textContent, 'Your appointment link is copied.' );
		assert.equal( status().getAttribute( 'data-onb-string' ), 'appointmentCopied' );
		assert.deepEqual( errorLines(), [] );
		assert.equal( copyButton().disabled, true );
		clock.tick( COPIED_ADVANCE_DELAY_MS - 1 );
		assert.equal( engine.current(), 'appointment' );
		clock.tick( 1 );
		assert.equal( engine.current(), 'closing' );
		await flush();
		assert.deepEqual( posts, [ { stage: 'closing' } ] );
	} );
}

test( 'once copied, more clicks and picks change nothing', async () => {
	const { option, copyButton, requests, written, settle } = setup( { stage: 'appointment' } );
	option( 'in-an-hour' ).click();
	copyButton().click();
	await settle();
	option( 'in-30-minutes' ).click();
	copyButton().disabled = false;
	copyButton().click();
	await settle();
	assert.equal( option( 'in-an-hour' ).getAttribute( 'aria-checked' ), 'true' );
	assert.equal( requests.length, 1 );
	assert.deepEqual( written, [ LINK ] );
} );

test( 'Other swaps the choices for date, time zone and time, and puts focus on the date', () => {
	const { document, screen, option, fieldOf } = setup( { stage: 'appointment' } );
	option( 'other' ).click();
	assert.equal( screen().querySelector( 'div[role="radiogroup"]' ).hidden, true );
	assert.equal( screen().querySelector( 'div.ihq-onb-appt__other' ).hidden, false );
	assert.equal( document.activeElement, fieldOf( 'date' ) );
	assert.deepEqual( screen().querySelectorAll( 'span.ihq-onb-appt__field-label' ).map( ( label ) => label.textContent ), [ 'Date', 'Timezone', 'Appointment Time' ] );
} );

test( 'Other with fields empty: no link, nothing copied, and a line for each missing field', async () => {
	const { option, fieldOf, copyButton, errorLines, requests, written, settle } = setup( { stage: 'appointment' } );
	option( 'other' ).click();
	fieldOf( 'time-zone' ).value = '';
	copyButton().click();
	await settle();
	assert.deepEqual( errorLines(), [ 'Please choose a date.', 'Please choose a time zone.', 'Please choose a time.' ] );
	assert.deepEqual( requests, [] );
	assert.deepEqual( written, [] );
	fieldOf( 'date' ).value = FUTURE.date;
	fieldOf( 'time-zone' ).value = FUTURE.timeZone;
	copyButton().click();
	await settle();
	assert.deepEqual( errorLines(), [ 'Please choose a time.' ] );
	assert.deepEqual( requests, [] );
} );

test( 'Other with a past time: no link, nothing copied, and it says the time has passed', async () => {
	const { option, fieldOf, copyButton, errorLines, requests, written, settle } = setup( { stage: 'appointment' } );
	option( 'other' ).click();
	fieldOf( 'date' ).value = PAST.date;
	fieldOf( 'time' ).value = PAST.time;
	fieldOf( 'time-zone' ).value = PAST.timeZone;
	copyButton().click();
	await settle();
	assert.deepEqual( errorLines(), [ 'That time has already passed. Please choose a later time.' ] );
	assert.deepEqual( requests, [] );
	assert.deepEqual( written, [] );
} );

test( 'Other with a future time: the fields are sent, the link copied, and the closing screen follows', async () => {
	const { option, fieldOf, copyButton, requests, written, status, engine, clock, settle } = setup( { stage: 'appointment' } );
	option( 'other' ).click();
	fieldOf( 'date' ).value = FUTURE.date;
	fieldOf( 'time' ).value = FUTURE.time;
	fieldOf( 'time-zone' ).value = FUTURE.timeZone;
	copyButton().click();
	await settle();
	assert.deepEqual( requests.map( ( request ) => request.body ), [ { choice: 'other', ...FUTURE } ] );
	assert.deepEqual( written, [ LINK ] );
	assert.equal( status().textContent, 'Your appointment link is copied.' );
	clock.tick( COPIED_ADVANCE_DELAY_MS );
	assert.equal( engine.current(), 'closing' );
} );

test( 'a refusal from the server shows its reasons, copies nothing and lets the Influencer try again', async () => {
	const { option, copyButton, errorLines, written, status, engine, clock, settle } = setup( { stage: 'appointment', responses: [ { status: 400, body: { errors: [ 'time-in-past', 'time-zone-invalid' ] } } ] } );
	option( 'in-an-hour' ).click();
	copyButton().click();
	await settle();
	assert.deepEqual( errorLines(), [ 'That time has already passed. Please choose a later time.', 'That time zone is not valid. Please choose another one.' ] );
	assert.deepEqual( written, [] );
	assert.equal( status().textContent, '' );
	assert.equal( copyButton().disabled, false );
	clock.tick( COPIED_ADVANCE_DELAY_MS );
	assert.equal( engine.current(), 'appointment' );
} );

test( 'a request that fails says so and lets the Influencer try again, which asks again', async () => {
	const { option, copyButton, errorLines, written, requests, settle } = setup( { stage: 'appointment', responses: [ 'network', { status: 500, body: { error: 'x' } } ] } );
	option( 'in-an-hour' ).click();
	copyButton().click();
	await settle();
	assert.deepEqual( errorLines(), [ 'We could not create your appointment link. Please try again.' ] );
	assert.equal( copyButton().disabled, false );
	copyButton().click();
	await settle();
	assert.deepEqual( errorLines(), [ 'We could not create your appointment link. Please try again.' ] );
	copyButton().click();
	await settle();
	assert.deepEqual( errorLines(), [] );
	assert.equal( requests.length, 3 );
	assert.deepEqual( written, [ LINK ] );
} );

test( 'a copy that fails shows the link to copy by hand; trying again copies the same link without booking again', async () => {
	const { document, option, copyButton, linkBox, status, requests, written, engine, clock, settle } = setup( { stage: 'appointment', copies: [ false ] } );
	option( 'in-30-minutes' ).click();
	copyButton().click();
	await settle();
	assert.equal( linkBox().hidden, false );
	assert.equal( linkBox().value, LINK );
	assert.equal( document.activeElement, linkBox() );
	assert.equal( document.selectedElement, linkBox() );
	assert.equal( status().textContent, 'We could not copy the link automatically. Please copy it from the box below.' );
	assert.equal( copyButton().disabled, false );
	clock.tick( COPIED_ADVANCE_DELAY_MS );
	assert.equal( engine.current(), 'appointment' );

	copyButton().click();
	await settle();
	assert.equal( requests.length, 1 );
	assert.deepEqual( written, [ LINK ] );
	assert.equal( status().textContent, 'Your appointment link is copied.' );
	clock.tick( COPIED_ADVANCE_DELAY_MS );
	assert.equal( engine.current(), 'closing' );
} );

test( 'choosing again after a failed copy forgets that link, so the next copy books the new choice', async () => {
	const { option, fieldOf, copyButton, linkBox, requests, settle } = setup( { stage: 'appointment', copies: [ false ] } );
	option( 'other' ).click();
	fieldOf( 'date' ).value = FUTURE.date;
	fieldOf( 'time' ).value = FUTURE.time;
	fieldOf( 'time-zone' ).value = FUTURE.timeZone;
	copyButton().click();
	await settle();
	assert.equal( linkBox().hidden, false );
	fieldOf( 'time' ).value = '11:00';
	fieldOf( 'time' ).dispatchEvent( createEvent( 'input' ) );
	assert.equal( linkBox().hidden, true );
	assert.equal( linkBox().value, '' );
	copyButton().click();
	await settle();
	assert.deepEqual( requests.map( ( request ) => request.body.time ), [ '10:30', '11:00' ] );
} );

test( 'a change event forgets the link too, and a new pick clears the old messages', async () => {
	const { option, fieldOf, copyButton, linkBox, errorLines, status, requests, settle } = setup( { stage: 'appointment', copies: [ false ] } );
	copyButton().click();
	await settle();
	assert.equal( errorLines().length, 1 );
	option( 'other' ).click();
	assert.deepEqual( errorLines(), [] );
	fieldOf( 'date' ).value = FUTURE.date;
	fieldOf( 'time' ).value = FUTURE.time;
	fieldOf( 'time-zone' ).value = FUTURE.timeZone;
	copyButton().click();
	await settle();
	assert.equal( status().getAttribute( 'data-onb-string' ), 'appointmentCopyFailed' );
	fieldOf( 'time-zone' ).dispatchEvent( createEvent( 'change' ) );
	assert.equal( linkBox().hidden, true );
	copyButton().click();
	await settle();
	assert.equal( requests.length, 2 );
} );

test( 'the messages follow the language: each line and the status carry their string key', async () => {
	const { option, copyButton, screen, status, settle } = setup( { stage: 'appointment' } );
	copyButton().click();
	await settle();
	assert.deepEqual( screen().querySelector( 'div.ihq-onb-appt__errors' ).children.map( ( line ) => line.getAttribute( 'data-onb-string' ) ), [ 'appointmentError-choice-missing' ] );
	option( 'in-an-hour' ).click();
	copyButton().click();
	await settle();
	assert.equal( status().getAttribute( 'data-onb-string' ), 'appointmentCopied' );
} );

test( 'clearing the messages also drops the status key, so a language change cannot bring it back', async () => {
	const { option, copyButton, status, settle } = setup( { stage: 'appointment', copies: [ false ] } );
	option( 'in-an-hour' ).click();
	copyButton().click();
	await settle();
	assert.equal( status().getAttribute( 'data-onb-string' ), 'appointmentCopyFailed' );
	option( 'in-30-minutes' ).click();
	assert.equal( status().textContent, '' );
	assert.equal( status().hasAttribute( 'data-onb-string' ), false );
} );

test( 'a screen left while its request is in flight writes nothing when the answer comes', async () => {
	const { option, copyButton, status, engine, clock, settle } = setup( { stage: 'appointment' } );
	option( 'in-an-hour' ).click();
	const before = status();
	copyButton().click();
	engine.advance();
	await settle();
	assert.equal( before.textContent, '' );
	assert.equal( clock.pendingCount(), 0 );
	assert.equal( engine.current(), 'closing' );
} );

test( 'moving on during the 4 seconds cancels the timer, so the flow moves once', async () => {
	const { option, copyButton, engine, clock, posts, settle } = setup( { stage: 'appointment' } );
	option( 'in-an-hour' ).click();
	copyButton().click();
	await settle();
	assert.equal( clock.pendingCount(), 1 );
	engine.advance();
	assert.equal( clock.pendingCount(), 0 );
	clock.tick( COPIED_ADVANCE_DELAY_MS );
	await flush();
	assert.deepEqual( posts, [ { stage: 'closing' } ] );
} );

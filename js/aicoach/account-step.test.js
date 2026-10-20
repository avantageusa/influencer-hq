import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createAccountStep } from './account-step.js';

const GENERIC = 'Something went wrong creating your account. Please try again.';
const VISITOR = {
	identity: { firstName: 'Ada', lastName: 'Lovelace', username: 'ada_l' },
	channels: [ { channel: 'email', value: 'ada@example.com' } ],
	language: 'ja',
};

function messageFor( data ) {
	return data && typeof data.error === 'string' ? data.error : '';
}

function setup( { answer, saves } = {} ) {
	const log = [];
	const step = createAccountStep( {
		register: async ( detail ) => {
			log.push( [ 'register', detail ] );
			if ( answer instanceof Error ) {
				throw answer;
			}
			return answer === undefined ? { success: true, redirectUrl: 'https://example.test/portal/portal-home/' } : answer;
		},
		beforeRegister: async () => {
			log.push( [ 'saves flushed' ] );
			if ( saves ) {
				await saves;
			}
		},
		afterCreated: () => log.push( [ 'afterCreated' ] ),
		messageFor,
		genericMessage: GENERIC,
	} );
	return { step, log };
}

test( 'creating the account sends the identity, the channels and the language, and does not redirect', async () => {
	const { step, log } = setup();
	const result = await step.run( VISITOR );
	assert.deepEqual( result, { ok: true, redirectUrl: 'https://example.test/portal/portal-home/' } );
	assert.deepEqual( log[ 1 ], [ 'register', {
		firstName: 'Ada',
		lastName: 'Lovelace',
		username: 'ada_l',
		channels: [ { channel: 'email', value: 'ada@example.com' } ],
		language: 'ja',
		redirect: false,
	} ] );
} );

test( 'the progress saves are flushed before the request, and afterCreated comes after it', async () => {
	const { step, log } = setup();
	await step.run( VISITOR );
	assert.deepEqual( log.map( ( entry ) => entry[ 0 ] ), [ 'saves flushed', 'register', 'afterCreated' ] );
} );

test( 'the request waits for the saves in flight', async () => {
	let release;
	const saves = new Promise( ( resolve ) => {
		release = resolve;
	} );
	const { step, log } = setup( { saves } );
	const pending = step.run( VISITOR );
	await new Promise( ( resolve ) => setTimeout( resolve, 5 ) );
	assert.deepEqual( log.map( ( entry ) => entry[ 0 ] ), [ 'saves flushed' ] );
	release();
	await pending;
	assert.deepEqual( log.map( ( entry ) => entry[ 0 ] ), [ 'saves flushed', 'register', 'afterCreated' ] );
} );

test( 'no channels means the key is left out of what is sent', async () => {
	const { step, log } = setup();
	await step.run( { identity: VISITOR.identity, channels: null, language: 'en' } );
	assert.equal( log[ 1 ][ 1 ].channels, undefined );
} );

test( 'a missing identity sends empty fields instead of failing before the request', async () => {
	const { step, log } = setup();
	await step.run( { language: 'en' } );
	assert.deepEqual( log[ 1 ][ 1 ], { firstName: undefined, lastName: undefined, username: undefined, channels: undefined, language: 'en', redirect: false } );
} );

test( 'the server\'s own reason is passed on when the account was not created', async () => {
	const { step, log } = setup( { answer: { success: false, error: 'That username is already taken.' } } );
	assert.deepEqual( await step.run( VISITOR ), { ok: false, message: 'That username is already taken.' } );
	assert.equal( step.isCreated(), false );
	assert.equal( step.redirectUrl(), null );
	assert.equal( log.some( ( entry ) => entry[ 0 ] === 'afterCreated' ), false );
} );

test( 'an answer with no reason, no success or no address gets the generic message', async () => {
	for ( const answer of [ null, undefined, {}, { success: false }, { success: true }, { success: true, redirectUrl: '' } ] ) {
		const log = [];
		const step = createAccountStep( {
			register: async () => answer,
			beforeRegister: async () => {},
			afterCreated: () => log.push( 'created' ),
			messageFor,
			genericMessage: GENERIC,
		} );
		assert.deepEqual( await step.run( VISITOR ), { ok: false, message: GENERIC }, JSON.stringify( answer ) );
		assert.deepEqual( log, [] );
	}
} );

test( 'a request that throws is a failure, not an exception', async () => {
	const { step } = setup( { answer: new Error( 'network down' ) } );
	assert.deepEqual( await step.run( VISITOR ), { ok: false, message: GENERIC } );
	assert.equal( step.isCreated(), false );
} );

test( 'saves that fail to flush are a failure, not an exception', async () => {
	const step = createAccountStep( {
		register: async () => ( { success: true, redirectUrl: 'x' } ),
		beforeRegister: async () => {
			throw new Error( 'save chain broke' );
		},
		afterCreated: () => {},
		messageFor,
		genericMessage: GENERIC,
	} );
	assert.deepEqual( await step.run( VISITOR ), { ok: false, message: GENERIC } );
} );

test( 'no registration function is a failure with the generic message and sends nothing', async () => {
	const log = [];
	const step = createAccountStep( {
		register: undefined,
		beforeRegister: async () => log.push( 'flushed' ),
		afterCreated: () => log.push( 'created' ),
		messageFor,
		genericMessage: GENERIC,
	} );
	assert.deepEqual( await step.run( VISITOR ), { ok: false, message: GENERIC } );
	assert.deepEqual( log, [] );
} );

test( 'after a failure the visitor can try again and the account is created then', async () => {
	let calls = 0;
	const step = createAccountStep( {
		register: async () => {
			calls++;
			return calls === 1 ? { success: false, error: 'Please wait a moment before trying again.' } : { success: true, redirectUrl: 'https://example.test/portal/portal-home/' };
		},
		beforeRegister: async () => {},
		afterCreated: () => {},
		messageFor,
		genericMessage: GENERIC,
	} );
	assert.deepEqual( await step.run( VISITOR ), { ok: false, message: 'Please wait a moment before trying again.' } );
	assert.deepEqual( await step.run( VISITOR ), { ok: true, redirectUrl: 'https://example.test/portal/portal-home/' } );
	assert.equal( calls, 2 );
} );

test( 'a second call while the first is running shares it and sends one request', async () => {
	const { step, log } = setup();
	const [ first, second ] = await Promise.all( [ step.run( VISITOR ), step.run( VISITOR ) ] );
	assert.deepEqual( first, second );
	assert.equal( log.filter( ( entry ) => entry[ 0 ] === 'register' ).length, 1 );
	assert.equal( log.filter( ( entry ) => entry[ 0 ] === 'afterCreated' ).length, 1 );
} );

test( 'once created, later calls return the same address and send nothing', async () => {
	const { step, log } = setup();
	await step.run( VISITOR );
	const again = await step.run( { language: 'en' } );
	assert.deepEqual( again, { ok: true, redirectUrl: 'https://example.test/portal/portal-home/' } );
	assert.equal( step.isCreated(), true );
	assert.equal( step.redirectUrl(), 'https://example.test/portal/portal-home/' );
	assert.equal( log.filter( ( entry ) => entry[ 0 ] === 'register' ).length, 1 );
	assert.equal( log.filter( ( entry ) => entry[ 0 ] === 'afterCreated' ).length, 1 );
} );

test( 'before anything ran, nothing is created', () => {
	const { step } = setup();
	assert.equal( step.isCreated(), false );
	assert.equal( step.redirectUrl(), null );
} );

test( 'afterCreated gets the answer the server gave, so the page can take what came with it', async () => {
	let received = null;
	const answer = { success: true, redirectUrl: 'https://example.test/portal/portal-home/', restNonce: 'fresh-nonce' };
	const step = createAccountStep( {
		register: async () => answer,
		beforeRegister: async () => {},
		afterCreated: ( data ) => {
			received = data;
		},
		messageFor,
		genericMessage: GENERIC,
	} );
	await step.run( VISITOR );
	assert.deepEqual( received, answer );
} );

test( 'isStarted is true while the request is in flight, after it succeeded, and false after a failure', async () => {
	let finish;
	const pendingAnswer = new Promise( ( resolve ) => {
		finish = resolve;
	} );
	const step = createAccountStep( {
		register: () => pendingAnswer,
		beforeRegister: async () => {},
		afterCreated: () => {},
		messageFor,
		genericMessage: GENERIC,
	} );
	assert.equal( step.isStarted(), false );
	const pending = step.run( VISITOR );
	assert.equal( step.isStarted(), true );
	assert.equal( step.isCreated(), false );
	finish( { success: false, error: 'Please wait a moment before trying again.' } );
	await pending;
	assert.equal( step.isStarted(), false );
	assert.equal( step.isCreated(), false );

	const created = setup();
	assert.equal( created.step.isStarted(), false );
	await created.step.run( VISITOR );
	assert.equal( created.step.isStarted(), true );
	assert.equal( created.step.isCreated(), true );
} );

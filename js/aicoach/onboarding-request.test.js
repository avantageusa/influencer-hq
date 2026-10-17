import { test } from 'node:test';
import assert from 'node:assert/strict';
import { postJson } from './onboarding-request.js';

function fakeFetch( response ) {
	const calls = [];
	const fetchFn = async ( url, init ) => {
		calls.push( [ url, init ] );
		if ( response instanceof Error ) {
			throw response;
		}
		return response;
	};
	return { calls, fetchFn };
}

test( 'posts JSON with the nonce and same-origin cookies, and reads the answer', async () => {
	const { calls, fetchFn } = fakeFetch( { ok: true, status: 200, json: async () => ( { stage: 'equity' } ) } );
	const result = await postJson( { url: '/wp-json/ihq/v1/onboarding/progress', nonce: 'abc', body: { stage: 'equity' }, fetchFn } );
	assert.deepEqual( result, { ok: true, status: 200, data: { stage: 'equity' } } );
	assert.deepEqual( calls, [ [ '/wp-json/ihq/v1/onboarding/progress', {
		method: 'POST',
		credentials: 'same-origin',
		headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': 'abc' },
		body: '{"stage":"equity"}',
	} ] ] );
} );

test( 'an empty body is sent as {}', async () => {
	const { calls, fetchFn } = fakeFetch( { ok: true, status: 200, json: async () => ( {} ) } );
	await postJson( { url: '/c', nonce: 'n', fetchFn } );
	assert.equal( calls[ 0 ][ 1 ].body, '{}' );
} );

test( 'an error status is not ok and keeps the answer', async () => {
	const { fetchFn } = fakeFetch( { ok: false, status: 400, json: async () => ( { error: 'Invalid stage.' } ) } );
	assert.deepEqual( await postJson( { url: '/p', nonce: 'n', body: {}, fetchFn } ), { ok: false, status: 400, data: { error: 'Invalid stage.' } } );
} );

test( 'an answer that is not JSON gives data null', async () => {
	const { fetchFn } = fakeFetch( {
		ok: true,
		status: 200,
		json: async () => {
			throw new SyntaxError( 'bad' );
		},
	} );
	assert.deepEqual( await postJson( { url: '/p', nonce: 'n', fetchFn } ), { ok: true, status: 200, data: null } );
} );

test( 'a network failure never throws', async () => {
	const { fetchFn } = fakeFetch( new TypeError( 'Failed to fetch' ) );
	assert.deepEqual( await postJson( { url: '/p', nonce: 'n', fetchFn } ), { ok: false, status: 0, data: null } );
} );

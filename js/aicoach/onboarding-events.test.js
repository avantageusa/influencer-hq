import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createEmitter } from './onboarding-events.js';

test( 'emit() calls every listener of the type in order with the detail', () => {
	const events = createEmitter();
	const calls = [];
	events.on( 'screen', ( detail ) => calls.push( [ 'a', detail ] ) );
	events.on( 'screen', ( detail ) => calls.push( [ 'b', detail ] ) );
	events.on( 'other', () => calls.push( [ 'other' ] ) );
	events.emit( 'screen', { stage: 'equity' } );
	assert.deepEqual( calls, [ [ 'a', { stage: 'equity' } ], [ 'b', { stage: 'equity' } ] ] );
} );

test( 'emit() of a type nobody listens to does nothing', () => {
	const events = createEmitter();
	assert.doesNotThrow( () => events.emit( 'nothing' ) );
} );

test( 'on() returns a function that removes the listener; off() does too', () => {
	const events = createEmitter();
	const calls = [];
	const first = () => calls.push( 'first' );
	const second = () => calls.push( 'second' );
	const removeFirst = events.on( 'x', first );
	events.on( 'x', second );
	removeFirst();
	events.emit( 'x' );
	events.off( 'x', second );
	events.emit( 'x' );
	events.off( 'never-added', second );
	assert.deepEqual( calls, [ 'second' ] );
} );

test( 'a listener that throws is reported and the others still run', () => {
	const events = createEmitter();
	const calls = [];
	const errors = [];
	const boom = new Error( 'boom' );
	events.on( 'x', () => {
		throw boom;
	} );
	events.on( 'x', () => calls.push( 'after' ) );
	events.emit( 'x', null, { onError: ( error ) => errors.push( error ) } );
	assert.deepEqual( calls, [ 'after' ] );
	assert.deepEqual( errors, [ boom ] );
} );

test( 'a listener added while emitting is not called in that emit', () => {
	const events = createEmitter();
	const calls = [];
	events.on( 'x', () => {
		calls.push( 'outer' );
		events.on( 'x', () => calls.push( 'inner' ) );
	} );
	events.emit( 'x' );
	assert.deepEqual( calls, [ 'outer' ] );
} );

test( 'the same listener added twice is called once', () => {
	const events = createEmitter();
	const calls = [];
	const listener = () => calls.push( 1 );
	events.on( 'x', listener );
	events.on( 'x', listener );
	events.emit( 'x' );
	assert.deepEqual( calls, [ 1 ] );
} );

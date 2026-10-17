import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createHistoryGuard, GUARD_STATE_KEY } from './onboarding-history.js';

// A History and window stand-in: an entry stack, and popstate fired by back(). reloaded
// starts on a guard entry above the Coach page, as a reload during onboarding does.
function setup( { reloaded = false } = {} ) {
	const entries = reloaded ? [ { state: null }, { state: { [ GUARD_STATE_KEY ]: true } } ] : [ { state: null } ];
	let index = entries.length - 1;
	const calls = [];
	const target = new EventTarget();
	const window = {
		addEventListener: ( type, fn ) => {
			calls.push( [ 'add', type ] );
			target.addEventListener( type, fn );
		},
		removeEventListener: ( type, fn ) => {
			calls.push( [ 'remove', type ] );
			target.removeEventListener( type, fn );
		},
	};
	const history = {
		get state() {
			return entries[ index ].state;
		},
		get length() {
			return index + 1;
		},
		pushState( state ) {
			calls.push( [ 'push', state ] );
			entries.splice( index + 1 );
			entries.push( { state } );
			index++;
		},
		back() {
			calls.push( [ 'back' ] );
			if ( index > 0 ) {
				index--;
				target.dispatchEvent( new Event( 'popstate' ) );
			}
		},
	};
	return { history, window, calls, depth: () => index };
}

test( 'the guard marks its entry', () => {
	assert.equal( GUARD_STATE_KEY, 'ihqOnboarding' );
} );

test( 'install pushes one guard entry and listens for popstate', () => {
	const { history, window, calls } = setup();
	const guard = createHistoryGuard( { history, window } );
	guard.install();
	assert.deepEqual( calls, [ [ 'push', { ihqOnboarding: true } ], [ 'add', 'popstate' ] ] );
	assert.equal( guard.isInstalled(), true );
} );

test( 'browser back while onboarding is open leaves the user where they are', () => {
	const { history, window, depth } = setup();
	const guard = createHistoryGuard( { history, window } );
	guard.install();
	history.back();
	assert.equal( depth(), 1 );
	assert.deepEqual( history.state, { ihqOnboarding: true } );
	history.back();
	history.back();
	assert.equal( depth(), 1 );
} );

test( 'installing twice adds nothing', () => {
	const { history, window, calls, depth } = setup();
	const guard = createHistoryGuard( { history, window } );
	guard.install();
	guard.install();
	assert.deepEqual( calls, [ [ 'push', { ihqOnboarding: true } ], [ 'add', 'popstate' ] ] );
	assert.equal( depth(), 1 );
} );

test( 'after a reload on the guard entry, install pushes no second entry and back still holds', () => {
	const { history, window, calls, depth } = setup( { reloaded: true } );
	const guard = createHistoryGuard( { history, window } );
	guard.install();
	assert.deepEqual( calls, [ [ 'add', 'popstate' ] ] );
	assert.equal( depth(), 1 );
	history.back();
	assert.equal( depth(), 1 );
	assert.deepEqual( history.state, { ihqOnboarding: true } );
} );

test( 'after a reload, uninstall leaves the Coach page entry current', () => {
	const { history, window, calls, depth } = setup( { reloaded: true } );
	const guard = createHistoryGuard( { history, window } );
	guard.install();
	guard.uninstall();
	assert.deepEqual( calls, [ [ 'add', 'popstate' ], [ 'remove', 'popstate' ], [ 'back' ] ] );
	assert.equal( depth(), 0 );
	assert.equal( history.state, null );
} );

test( 'uninstall stops guarding and removes the guard entry', () => {
	const { history, window, calls, depth } = setup();
	const guard = createHistoryGuard( { history, window } );
	guard.install();
	guard.uninstall();
	assert.deepEqual( calls, [ [ 'push', { ihqOnboarding: true } ], [ 'add', 'popstate' ], [ 'remove', 'popstate' ], [ 'back' ] ] );
	assert.equal( depth(), 0 );
	assert.equal( guard.isInstalled(), false );
	history.back();
	assert.equal( depth(), 0 );
} );

test( 'uninstall when the current entry is not the guard does not go back', () => {
	const { history, window, calls } = setup();
	const guard = createHistoryGuard( { history, window } );
	guard.install();
	history.pushState( { other: true } );
	guard.uninstall();
	assert.deepEqual( calls.at( -1 ), [ 'remove', 'popstate' ] );
} );

test( 'uninstall without install does nothing', () => {
	const { history, window, calls } = setup();
	createHistoryGuard( { history, window } ).uninstall();
	assert.deepEqual( calls, [] );
} );

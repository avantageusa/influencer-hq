import { test } from 'node:test';
import assert from 'node:assert/strict';
import { formatCountdown, secondsLeft, reloadDelayMs } from './countdown.js';

test( 'the countdown is HH:MM:SS', () => {
	assert.equal( formatCountdown( 0 ), '00:00:00' );
	assert.equal( formatCountdown( 1 ), '00:00:01' );
	assert.equal( formatCountdown( 59 ), '00:00:59' );
	assert.equal( formatCountdown( 60 ), '00:01:00' );
	assert.equal( formatCountdown( 3599 ), '00:59:59' );
	assert.equal( formatCountdown( 3600 ), '01:00:00' );
	assert.equal( formatCountdown( 86399 ), '23:59:59' );
} );

test( 'a day or more puts the days in front', () => {
	assert.equal( formatCountdown( 86400 ), '1d 00:00:00' );
	assert.equal( formatCountdown( 2 * 86400 + 3 * 3600 + 4 * 60 + 5 ), '2d 03:04:05' );
	assert.equal( formatCountdown( 40 * 86400 ), '40d 00:00:00' );
} );

test( 'a part of a second is not counted', () => {
	assert.equal( formatCountdown( 61.9 ), '00:01:01' );
	assert.equal( formatCountdown( 0.9 ), '00:00:00' );
} );

test( 'anything that is not a positive number is zero', () => {
	[ -5, -0.1, NaN, Infinity, -Infinity, undefined, null, 'abc' ].forEach( ( value ) => {
		assert.equal( formatCountdown( value ), '00:00:00', String( value ) );
	} );
} );

test( 'seconds left count down from the server\'s figure by the time the page has been open', () => {
	assert.equal( secondsLeft( 600, 0 ), 600 );
	assert.equal( secondsLeft( 600, 1000 ), 599 );
	assert.equal( secondsLeft( 600, 59000 ), 541 );
	assert.equal( secondsLeft( 600, 600000 ), 0 );
	assert.equal( secondsLeft( 600, 900000 ), 0 );
} );

test( 'a partial second still counts as a second left, so zero means it is time', () => {
	assert.equal( secondsLeft( 10, 9500 ), 1 );
	assert.equal( secondsLeft( 10, 9999 ), 1 );
	assert.equal( secondsLeft( 10, 10000 ), 0 );
} );

test( 'seconds left are zero when either figure is not a number', () => {
	assert.equal( secondsLeft( NaN, 100 ), 0 );
	assert.equal( secondsLeft( 100, NaN ), 0 );
	assert.equal( secondsLeft( undefined, 0 ), 0 );
} );

test( 'the reload waits for the seconds given, in milliseconds, plus a margin past the boundary', () => {
	assert.equal( reloadDelayMs( 1 ), 1500 );
	assert.equal( reloadDelayMs( 600 ), 600500 );
	assert.equal( reloadDelayMs( '90' ), 90500 );
	assert.equal( reloadDelayMs( 0.5 ), 1000 );
} );

test( 'there is nothing to reload for when no next state is given', () => {
	[ null, undefined, '', 'soon', NaN, 0, -3, '-3', Infinity ].forEach( ( value ) => {
		assert.equal( reloadDelayMs( value ), null, String( value ) );
	} );
} );

test( 'a boundary a timer can wait for is waited for in full, margin included', () => {
	assert.equal( reloadDelayMs( 2147482 ), 2147482500 );
	assert.equal( reloadDelayMs( 2147483 ), 2147483500 );
} );

test( 'a boundary too far away for a timer gets the longest wait, so the page reloads and sets it again', () => {
	assert.equal( reloadDelayMs( 2147484 ), 2147483647 );
	assert.equal( reloadDelayMs( 30 * 86400 ), 2147483647 );
	assert.equal( reloadDelayMs( 400 * 86400 ), 2147483647 );
} );

test( 'no wait is ever longer than a timer allows, so none fires at once', () => {
	[ 1, 1000, 2147482, 2147483, 99999999, 1e12 ].forEach( ( seconds ) => {
		const delay = reloadDelayMs( seconds );
		assert.ok( delay > 0 && delay <= 2147483647, String( seconds ) );
	} );
} );

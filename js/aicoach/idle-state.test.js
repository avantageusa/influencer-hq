import { test } from 'node:test';
import assert from 'node:assert/strict';
import { isAvatarIdle, isScreenWithoutClip } from './idle-state.js';

test( 'idle only while the panel is open and no answer video is showing', () => {
	assert.equal( isAvatarIdle( { panelOpen: true, answerVideoActive: false } ), true );
	assert.equal( isAvatarIdle( { panelOpen: true, answerVideoActive: true } ), false );
	assert.equal( isAvatarIdle( { panelOpen: false, answerVideoActive: false } ), false );
	assert.equal( isAvatarIdle( { panelOpen: false, answerVideoActive: true } ), false );
} );

test( 'always returns a boolean, whatever truthy or falsy values come in', () => {
	assert.strictEqual( isAvatarIdle( { panelOpen: 1, answerVideoActive: 0 } ), true );
	assert.strictEqual( isAvatarIdle( { panelOpen: 'yes', answerVideoActive: null } ), true );
	assert.strictEqual( isAvatarIdle( { panelOpen: undefined, answerVideoActive: false } ), false );
	assert.strictEqual( isAvatarIdle( { panelOpen: {}, answerVideoActive: {} } ), false );
} );

test( 'a screen without a clip is idle unless an answer video is showing', () => {
	assert.equal( isAvatarIdle( { panelOpen: false, answerVideoActive: false, screenWithoutClip: true } ), true );
	assert.equal( isAvatarIdle( { panelOpen: false, answerVideoActive: true, screenWithoutClip: true } ), false );
	assert.equal( isAvatarIdle( { panelOpen: true, answerVideoActive: false, screenWithoutClip: true } ), true );
	assert.equal( isAvatarIdle( { panelOpen: false, answerVideoActive: false, screenWithoutClip: false } ), false );
	assert.strictEqual( isAvatarIdle( { panelOpen: false, answerVideoActive: false, screenWithoutClip: 'yes' } ), true );
} );

test( 'the intro and the screens with a clip are not screens without one', () => {
	const clips = [ 'believe-1', 'home', 'equity-bts' ];
	assert.equal( isScreenWithoutClip( 'intro', clips ), false );
	assert.equal( isScreenWithoutClip( 'believe-1', clips ), false );
	assert.equal( isScreenWithoutClip( 'equity-bts', clips ), false );
} );

test( 'identity, channels, the final screen, Time is up and the appointment screens have no clip', () => {
	const clips = [ 'believe-1', 'home', 'equity-bts' ];
	[ 'identity', 'comm-channels', 'final-continue', 'time-up', 'appointment', 'appointment-done' ].forEach( ( key ) => {
		assert.equal( isScreenWithoutClip( key, clips ), true, key );
	} );
} );

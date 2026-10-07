import { test } from 'node:test';
import assert from 'node:assert/strict';
import { isAvatarIdle } from './idle-state.js';

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

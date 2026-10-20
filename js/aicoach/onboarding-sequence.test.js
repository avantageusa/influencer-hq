import { test } from 'node:test';
import assert from 'node:assert/strict';
import { SEQUENCE, BRANCHES, startStage, nextStage } from './onboarding-sequence.js';

const all = () => true;

test( 'the screens in order, Talk more? and the appointment before closing (ENGR-7577)', () => {
	assert.deepEqual( SEQUENCE, [
		'game_of_kings',
		'modern_era',
		'baccarat_experience',
		'streaming_experience',
		'equity_knowledge',
		'social_platforms',
		'world_championship',
		'world_tour',
		'classic',
		'equity',
		'how_this_works',
		'active_followers',
		'equity_example',
		'draft_posts',
		'post_action',
		'time_up',
		'appointment',
		'closing',
	] );
	assert.ok( Object.isFrozen( SEQUENCE ) );
} );

test( 'two branches: edit together goes back to the drafts, Continue on Talk more? skips the appointment', () => {
	assert.deepEqual( BRANCHES, [
		{ from: 'post_action', choice: 'edit_together', to: 'draft_posts' },
		{ from: 'time_up', choice: 'continue', to: 'closing' },
	] );
	assert.ok( Object.isFrozen( BRANCHES ) && BRANCHES.every( ( branch ) => Object.isFrozen( branch ) ) );
} );

test( 'advancing moves exactly one screen forward, through the whole order', () => {
	for ( let index = 0; index < SEQUENCE.length - 1; index++ ) {
		assert.equal( nextStage( { current: SEQUENCE[ index ], isAvailable: all } ), SEQUENCE[ index + 1 ] );
	}
} );

test( 'there is nothing after the closing screen, or after an unknown one', () => {
	assert.equal( nextStage( { current: 'closing', isAvailable: all } ), null );
	assert.equal( nextStage( { current: 'payout', isAvailable: all } ), null );
} );

test( 'edit together returns to the drafts; the other two post action choices go to Talk more?', () => {
	assert.equal( nextStage( { current: 'post_action', choice: 'edit_together', isAvailable: all } ), 'draft_posts' );
	assert.equal( nextStage( { current: 'post_action', choice: 'post_now', isAvailable: all } ), 'time_up' );
	assert.equal( nextStage( { current: 'post_action', choice: 'edit_later', isAvailable: all } ), 'time_up' );
	assert.equal( nextStage( { current: 'post_action', isAvailable: all } ), 'time_up' );
} );

test( 'Talk more?: Continue to IHQ portal goes to closing, Set an Appointment to scheduling, then closing', () => {
	assert.equal( nextStage( { current: 'time_up', choice: 'continue', isAvailable: all } ), 'closing' );
	assert.equal( nextStage( { current: 'time_up', choice: 'appointment', isAvailable: all } ), 'appointment' );
	assert.equal( nextStage( { current: 'appointment', isAvailable: all } ), 'closing' );
} );

test( 'without the appointment screens installed, post action goes straight to closing', () => {
	const noAppointment = ( stage ) => ! [ 'time_up', 'appointment' ].includes( stage );
	assert.equal( nextStage( { current: 'post_action', choice: 'post_now', isAvailable: noAppointment } ), 'closing' );
} );

test( 'the branch choice only applies on its own screen', () => {
	assert.equal( nextStage( { current: 'draft_posts', choice: 'edit_together', isAvailable: all } ), 'post_action' );
} );

test( 'a branch to a screen that is not installed is not taken', () => {
	const noDrafts = ( stage ) => 'draft_posts' !== stage;
	assert.equal( nextStage( { current: 'post_action', choice: 'edit_together', isAvailable: noDrafts } ), 'time_up' );
} );

test( 'screens not installed yet are passed over', () => {
	const held = ( stage ) => ! [ 'draft_posts', 'post_action' ].includes( stage );
	assert.equal( nextStage( { current: 'equity_example', isAvailable: held } ), 'time_up' );
	assert.equal( nextStage( { current: 'closing', isAvailable: held } ), null );
	assert.equal( nextStage( { current: 'equity_example', isAvailable: () => false } ), null );
} );

test( 'a custom order and branches can be passed in (T16 appointment screens)', () => {
	const order = [ 'a', 'b', 'c' ];
	assert.equal( nextStage( { current: 'a', isAvailable: all, order, branches: [] } ), 'b' );
	assert.equal( nextStage( { current: 'c', choice: 'x', isAvailable: all, order, branches: [ { from: 'c', choice: 'x', to: 'a' } ] } ), 'a' );
} );

test( 'onboarding opens at screen 1 for a new user', () => {
	assert.equal( startStage( { saved: '', isAvailable: all } ), 'game_of_kings' );
	assert.equal( startStage( { saved: undefined, isAvailable: all } ), 'game_of_kings' );
} );

test( 'onboarding resumes at the saved stage', () => {
	assert.equal( startStage( { saved: 'world_tour', isAvailable: all } ), 'world_tour' );
	assert.equal( startStage( { saved: 'closing', isAvailable: all } ), 'closing' );
} );

test( 'an unknown saved stage opens screen 1; a saved stage not installed opens the next one that is', () => {
	assert.equal( startStage( { saved: 'payout', isAvailable: all } ), 'game_of_kings' );
	assert.equal( startStage( { saved: 'draft_posts', isAvailable: ( stage ) => ! [ 'draft_posts', 'post_action' ].includes( stage ) } ), 'time_up' );
	assert.equal( startStage( { saved: '', isAvailable: ( stage ) => 'modern_era' === stage } ), 'modern_era' );
} );

test( 'nothing to open when no screen is installed', () => {
	assert.equal( startStage( { saved: '', isAvailable: () => false } ), null );
} );

import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createFakeDocument, build } from './fake-dom.js';
import { createFakeClock } from './fake-clock.js';
import { createCaptionBox, FADE_OUT_MS, CHANGING_CLASS, REDUCED_MOTION_QUERY } from './onboarding-caption-box.js';

const FIRST = { current: 'For over 500 years,', previous: '' };
const SECOND = { current: 'Baccarat has been known as the', previous: 'For over 500 years,' };
const NONE = { current: '', previous: '' };

function setup( { reducedMotion = false, matchMedia = true } = {} ) {
	const document = createFakeDocument();
	const element = build( document, [ 'p', { 'data-onb': 'subtitle', class: 'ihq-onb__subtitle' }, 'stale' ] );
	const clock = createFakeClock();
	const queries = [];
	const window = { setTimeout: clock.setTimeout, clearTimeout: clock.clearTimeout };
	if ( matchMedia ) {
		window.matchMedia = ( query ) => {
			queries.push( query );
			return { matches: reducedMotion };
		};
	}
	const box = createCaptionBox( { element, document, window } );
	const [ previousLine, currentLine ] = element.children;
	const shown = () => ( { current: currentLine.textContent, previous: previousLine.textContent } );
	const fading = () => element.classList.contains( CHANGING_CLASS );
	return { element, clock, queries, box, previousLine, currentLine, shown, fading };
}

test( 'constants', () => {
	assert.equal( FADE_OUT_MS, 180 );
	assert.equal( CHANGING_CLASS, 'is-changing' );
	assert.equal( REDUCED_MOTION_QUERY, '(prefers-reduced-motion: reduce)' );
} );

test( 'the box gets two empty lines: the previous phrase above the current one', () => {
	const { element, previousLine, currentLine, fading } = setup();
	assert.equal( element.children.length, 2 );
	assert.equal( previousLine.tagName, 'SPAN' );
	assert.equal( previousLine.className, 'ihq-onb__phrase ihq-onb__phrase--previous' );
	assert.equal( currentLine.tagName, 'SPAN' );
	assert.equal( currentLine.className, 'ihq-onb__phrase ihq-onb__phrase--current' );
	assert.equal( element.textContent, '' );
	assert.equal( fading(), false );
} );

test( 'a new phrase fades the box out, then swaps the words in and fades back', () => {
	const { box, clock, shown, fading } = setup();
	box.show( FIRST );
	assert.equal( fading(), true );
	assert.deepEqual( shown(), NONE );
	clock.tick( FADE_OUT_MS - 1 );
	assert.deepEqual( shown(), NONE );
	clock.tick( 1 );
	assert.deepEqual( shown(), FIRST );
	assert.equal( fading(), false );
	box.show( SECOND );
	assert.equal( fading(), true );
	assert.deepEqual( shown(), FIRST );
	clock.tick( FADE_OUT_MS );
	assert.deepEqual( shown(), SECOND );
	assert.equal( fading(), false );
	assert.equal( clock.pendingCount(), 0 );
} );

test( 'the same caption again (every tick of the narrator) changes nothing and restarts no fade', () => {
	const { box, clock, shown, fading } = setup();
	box.show( FIRST );
	clock.tick( FADE_OUT_MS );
	box.show( { ...FIRST } );
	assert.equal( fading(), false );
	assert.equal( clock.pendingCount(), 0 );
	assert.deepEqual( shown(), FIRST );
} );

test( 'the same caption again while it is fading in does not restart the fade', () => {
	const { box, clock, shown } = setup();
	box.show( FIRST );
	clock.tick( 100 );
	box.show( FIRST );
	assert.equal( clock.pendingCount(), 1 );
	clock.tick( FADE_OUT_MS - 100 );
	assert.deepEqual( shown(), FIRST );
} );

test( 'a newer caption during the fade-out is the one swapped in, on the same timer', () => {
	const { box, clock, shown, fading } = setup();
	box.show( FIRST );
	clock.tick( 100 );
	box.show( SECOND );
	assert.equal( clock.pendingCount(), 1 );
	clock.tick( FADE_OUT_MS - 100 );
	assert.deepEqual( shown(), SECOND );
	assert.equal( fading(), false );
	assert.equal( clock.pendingCount(), 0 );
} );

test( 'only a change of the previous phrase is still a change', () => {
	const { box, clock, shown } = setup();
	box.show( { current: 'Same.', previous: '' } );
	clock.tick( FADE_OUT_MS );
	box.show( { current: 'Same.', previous: 'Before.' } );
	assert.equal( clock.pendingCount(), 1 );
	clock.tick( FADE_OUT_MS );
	assert.deepEqual( shown(), { current: 'Same.', previous: 'Before.' } );
} );

test( 'clearing fades the box out and leaves both lines empty', () => {
	const { box, clock, shown, fading } = setup();
	box.show( SECOND );
	clock.tick( FADE_OUT_MS );
	box.show( NONE );
	assert.equal( fading(), true );
	clock.tick( FADE_OUT_MS );
	assert.deepEqual( shown(), NONE );
	assert.equal( fading(), false );
} );

test( 'a missing or partial caption counts as empty text', () => {
	const { box, clock, shown } = setup();
	box.show( { current: 'Hi there, friend.' } );
	clock.tick( FADE_OUT_MS );
	assert.deepEqual( shown(), { current: 'Hi there, friend.', previous: '' } );
	box.show( null );
	clock.tick( FADE_OUT_MS );
	assert.deepEqual( shown(), NONE );
	box.show( undefined );
	assert.equal( clock.pendingCount(), 0 );
} );

test( 'with prefers-reduced-motion the words swap at once and nothing fades', () => {
	const { box, clock, queries, shown, fading } = setup( { reducedMotion: true } );
	box.show( FIRST );
	assert.deepEqual( shown(), FIRST );
	assert.equal( fading(), false );
	assert.equal( clock.pendingCount(), 0 );
	box.show( SECOND );
	assert.deepEqual( shown(), SECOND );
	assert.equal( fading(), false );
	assert.deepEqual( queries, [ REDUCED_MOTION_QUERY, REDUCED_MOTION_QUERY ] );
} );

test( 'reduced motion switched on mid-fade cancels the fade and shows the newest words', () => {
	const document = createFakeDocument();
	const element = build( document, [ 'p', {} ] );
	const clock = createFakeClock();
	let reduce = false;
	const window = {
		setTimeout: clock.setTimeout,
		clearTimeout: clock.clearTimeout,
		matchMedia: () => ( { matches: reduce } ),
	};
	const box = createCaptionBox( { element, document, window } );
	box.show( FIRST );
	assert.equal( clock.pendingCount(), 1 );
	reduce = true;
	box.show( SECOND );
	assert.equal( clock.pendingCount(), 0 );
	assert.equal( element.classList.contains( CHANGING_CLASS ), false );
	assert.equal( element.children[ 1 ].textContent, SECOND.current );
	assert.equal( element.children[ 0 ].textContent, SECOND.previous );
	clock.tick( FADE_OUT_MS );
	assert.equal( element.children[ 1 ].textContent, SECOND.current );
} );

test( 'a browser without matchMedia fades', () => {
	const { box, clock, shown, fading } = setup( { matchMedia: false } );
	box.show( FIRST );
	assert.equal( fading(), true );
	clock.tick( FADE_OUT_MS );
	assert.deepEqual( shown(), FIRST );
} );

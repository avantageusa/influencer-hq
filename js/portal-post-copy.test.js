import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createFakeDocument, build } from './aicoach/fake-dom.js';
import { createFakeClock } from './aicoach/fake-clock.js';
import {
	HAS_DRAFT_ATTR,
	COPY_CONFIRMATION_MS,
	COPY_COPIED_LABEL,
	COPY_FAILED_LABEL,
	draftTextFromPanel,
	panelHasCopyableDraft,
	bindPostPanelCopy,
	copyLabelsFromRoot,
} from './portal-post-copy.js';

const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

const FACEBOOK_DRAFT = 'Facebook Draft Post goes here';
const LINE_DRAFT = 'LINE draft post goes here';
const WECHAT_DRAFT = 'WeChat draft post goes here';
const DATE = '08-31-2026';

function panelSpec( { label, draft, hasDraft = true, date = DATE } ) {
	const attrs = { class: 'post-panel', 'aria-label': label };
	if ( hasDraft ) {
		attrs[ HAS_DRAFT_ATTR ] = '';
	}
	return [
		'section',
		attrs,
		[ 'div', { class: 'post-panel-draft' }, draft ],
		[
			'div',
			{ class: 'post-panel-meta' },
			[ 'span', { class: 'post-panel-date' }, date ],
			[ 'button', { type: 'button', class: 'post-panel-copy' }, 'Copy' ],
		],
	];
}

function setup( {
	panels = [
		{ label: 'Facebook', draft: FACEBOOK_DRAFT },
		{ label: 'LINE', draft: LINE_DRAFT },
		{ label: 'WeChat', draft: WECHAT_DRAFT },
	],
	clipboard,
	copiedLabel,
	failedLabel,
} = {} ) {
	const document = createFakeDocument();
	const copied = [];
	const clock = createFakeClock();
	const write = clipboard === undefined
		? async ( text ) => {
			copied.push( text );
		}
		: clipboard;
	const root = build( document, [
		'div',
		{
			class: 'post-page-content',
			'data-copy-copied': COPY_COPIED_LABEL,
			'data-copy-failed': COPY_FAILED_LABEL,
		},
		...panels.map( panelSpec ),
	] );
	document.body.append( root );
	const binding = bindPostPanelCopy( {
		root: document.body,
		clipboard: write,
		timers: clock,
		copiedLabel: copiedLabel || COPY_COPIED_LABEL,
		failedLabel: failedLabel || COPY_FAILED_LABEL,
	} );
	return {
		document,
		copied,
		clock,
		binding,
		panel: ( label ) => document.body.querySelector( `.post-panel[aria-label="${ label }"]` ),
		copy: ( label ) => {
			const panel = document.body.querySelector( `.post-panel[aria-label="${ label }"]` );
			return panel ? panel.querySelector( '.post-panel-copy' ) : null;
		},
	};
}

test( 'draftTextFromPanel is only the draft, not the date or platform label', () => {
	const draft = 'Hello followers\nline two';
	const { panel } = setup( {
		panels: [ { label: 'Facebook', draft } ],
	} );
	assert.equal( draftTextFromPanel( panel( 'Facebook' ) ), draft );
	assert.ok( ! draftTextFromPanel( panel( 'Facebook' ) ).includes( DATE ) );
	assert.ok( ! draftTextFromPanel( panel( 'Facebook' ) ).includes( 'Facebook' ) );
} );

test( 'a default panel (FR-07) has no copyable draft, even with placeholder text', () => {
	const { panel, copy } = setup( {
		panels: [
			{ label: 'Facebook', draft: FACEBOOK_DRAFT, hasDraft: false },
			{ label: 'LINE', draft: LINE_DRAFT, hasDraft: true },
		],
	} );
	assert.equal( panelHasCopyableDraft( panel( 'Facebook' ) ), false );
	assert.equal( panelHasCopyableDraft( panel( 'LINE' ) ), true );
	assert.equal( copy( 'Facebook' ).disabled, true );
	assert.equal( copy( 'Facebook' ).getAttribute( 'tabindex' ), '-1' );
	assert.equal( copy( 'LINE' ).disabled, false );
} );

test( 'an empty marked draft still disables Copy', () => {
	const { copy } = setup( {
		panels: [ { label: 'Facebook', draft: '   ', hasDraft: true } ],
	} );
	assert.equal( copy( 'Facebook' ).disabled, true );
} );

test( 'Copy puts only that panel\'s draft on the clipboard and confirms for COPY_CONFIRMATION_MS', async () => {
	const { copy, copied, clock } = setup();
	const facebook = copy( 'Facebook' );
	assert.equal( facebook.textContent, 'Copy' );
	facebook.click();
	await flush();
	assert.deepEqual( copied, [ FACEBOOK_DRAFT ] );
	assert.ok( ! copied[ 0 ].includes( DATE ) );
	assert.ok( ! copied[ 0 ].includes( '<div' ) );
	assert.equal( facebook.textContent, COPY_COPIED_LABEL );
	assert.equal( copy( 'LINE' ).textContent, 'Copy' );
	clock.tick( COPY_CONFIRMATION_MS - 1 );
	assert.equal( facebook.textContent, COPY_COPIED_LABEL );
	clock.tick( 1 );
	assert.equal( facebook.textContent, 'Copy' );
} );

test( 'LINE Copy does not include the WeChat draft', async () => {
	const { copy, copied } = setup();
	copy( 'LINE' ).click();
	await flush();
	assert.deepEqual( copied, [ LINE_DRAFT ] );
	assert.ok( ! copied[ 0 ].includes( WECHAT_DRAFT ) );
} );

test( 'a second copy restarts the confirmation', async () => {
	const { copy, clock } = setup();
	const facebook = copy( 'Facebook' );
	facebook.click();
	await flush();
	clock.tick( COPY_CONFIRMATION_MS - 1 );
	facebook.click();
	await flush();
	clock.tick( COPY_CONFIRMATION_MS - 1 );
	assert.equal( facebook.textContent, COPY_COPIED_LABEL );
	clock.tick( 1 );
	assert.equal( facebook.textContent, 'Copy' );
} );

test( 'a refused clipboard, or none, says to copy by hand', async () => {
	const refused = setup( {
		clipboard: async () => {
			throw new Error( 'denied' );
		},
	} );
	refused.copy( 'Facebook' ).click();
	await flush();
	assert.equal( refused.copy( 'Facebook' ).textContent, COPY_FAILED_LABEL );

	const none = setup( { clipboard: null } );
	none.copy( 'LINE' ).click();
	await flush();
	assert.equal( none.copy( 'LINE' ).textContent, COPY_FAILED_LABEL );
} );

test( 'a default panel copies nothing when clicked', async () => {
	const { copy, copied } = setup( {
		panels: [ { label: 'Facebook', draft: FACEBOOK_DRAFT, hasDraft: false } ],
	} );
	copy( 'Facebook' ).click();
	await flush();
	assert.deepEqual( copied, [] );
} );

test( 'copyLabelsFromRoot reads the Post page strings', () => {
	const { document } = setup();
	assert.deepEqual( copyLabelsFromRoot( document.body ), {
		copiedLabel: COPY_COPIED_LABEL,
		failedLabel: COPY_FAILED_LABEL,
	} );
} );

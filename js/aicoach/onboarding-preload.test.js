import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createFakeDocument } from './fake-dom.js';
import { createPreloader } from './onboarding-preload.js';

// The onboarding API as the preloader sees it: events, and clip/image lookups in the active language.
function setup( { clips = {}, images = {} } = {} ) {
	const listeners = {};
	let locale = 'en';
	const api = {
		events: {
			on: ( type, listener ) => {
				( listeners[ type ] = listeners[ type ] || [] ).push( listener );
			},
		},
		clipUrl: ( stage ) => ( clips[ stage ] && clips[ stage ][ locale ] ) || '',
		imageUrl: ( stage ) => images[ stage ] || '',
	};
	const emit = ( type, detail ) => ( listeners[ type ] || [] ).forEach( ( listener ) => listener( detail ) );
	const created = [];
	const document = createFakeDocument();
	const createElement = document.createElement;
	document.createElement = ( tag ) => {
		const element = createElement( tag );
		created.push( element );
		return element;
	};
	const preloader = createPreloader( { api, document } );
	return {
		preloader,
		created,
		screen: ( stage, next ) => emit( 'screen', { stage, next } ),
		switchTo: ( code ) => {
			locale = code;
			emit( 'locale', code );
		},
	};
}

const CLIPS = {
	modern_era: { en: 'https://cdn.test/modern-en.mp4', ja: 'https://cdn.test/modern-ja.mp4' },
	baccarat_experience: { en: 'https://cdn.test/bacc-en.mp4' },
};
const IMAGES = { modern_era: 'https://cdn.test/modern.webp' };

test( 'when a screen starts, the next screen clip and hero image are requested', () => {
	const { preloader, created, screen } = setup( { clips: CLIPS, images: IMAGES } );
	screen( 'game_of_kings', 'modern_era' );
	assert.deepEqual( preloader.requested(), [ 'https://cdn.test/modern-en.mp4', 'https://cdn.test/modern.webp' ] );
	const [ video, image ] = created;
	assert.equal( video.tagName, 'VIDEO' );
	assert.equal( video.getAttribute( 'src' ), 'https://cdn.test/modern-en.mp4' );
	assert.equal( video.getAttribute( 'preload' ), 'auto' );
	assert.equal( video.hasAttribute( 'muted' ), true );
	assert.equal( video.muted, true );
	assert.equal( video.hasAttribute( 'playsinline' ), true );
	assert.equal( video.parentNode, null, 'detached: nothing is added to the page' );
	assert.equal( image.tagName, 'IMG' );
	assert.equal( image.getAttribute( 'src' ), 'https://cdn.test/modern.webp' );
} );

test( 'a language switch preloads the next clip in the new language', () => {
	const { preloader, screen, switchTo } = setup( { clips: CLIPS, images: IMAGES } );
	screen( 'game_of_kings', 'modern_era' );
	switchTo( 'ja' );
	assert.deepEqual( preloader.requested(), [ 'https://cdn.test/modern-en.mp4', 'https://cdn.test/modern.webp', 'https://cdn.test/modern-ja.mp4' ] );
	assert.equal( preloader.currentClip().getAttribute( 'src' ), 'https://cdn.test/modern-ja.mp4' );
} );

test( 'only the latest clip keeps loading; the previous one is released', () => {
	const { preloader, created, screen } = setup( { clips: CLIPS } );
	screen( 'game_of_kings', 'modern_era' );
	const first = created[ 0 ];
	screen( 'modern_era', 'baccarat_experience' );
	assert.equal( first.hasAttribute( 'src' ), false );
	assert.equal( preloader.currentClip().getAttribute( 'src' ), 'https://cdn.test/bacc-en.mp4' );
} );

test( 'each URL is requested once', () => {
	const { preloader, created, screen } = setup( { clips: CLIPS, images: IMAGES } );
	screen( 'game_of_kings', 'modern_era' );
	screen( 'game_of_kings', 'modern_era' );
	assert.equal( created.length, 2 );
	assert.equal( preloader.requested().length, 2 );
} );

test( 'nothing is requested for a screen with no clip or image (captions only), or after the last screen', () => {
	const { preloader, created, screen, switchTo } = setup( { clips: CLIPS } );
	screen( 'world_tour', 'classic' );
	screen( 'post_action', null );
	screen( 'closing', undefined );
	switchTo( 'ja' );
	assert.deepEqual( preloader.requested(), [] );
	assert.deepEqual( created, [] );
	assert.equal( preloader.currentClip(), null );
} );

test( 'a language switch before any screen does nothing', () => {
	const { preloader, switchTo } = setup( { clips: CLIPS } );
	switchTo( 'ja' );
	assert.deepEqual( preloader.requested(), [] );
} );

test( 'a malformed screen event does nothing', () => {
	const { preloader } = setup( { clips: CLIPS } );
	const listenersApi = { events: { on: ( type, listener ) => 'screen' === type && listener( undefined ) }, clipUrl: () => 'x', imageUrl: () => '' };
	createPreloader( { api: listenersApi, document: createFakeDocument() } );
	assert.deepEqual( preloader.requested(), [] );
} );

test( 'preload() can be called directly for a stage', () => {
	const { preloader } = setup( { clips: CLIPS, images: IMAGES } );
	preloader.preload( 'modern_era' );
	preloader.preload( null );
	assert.deepEqual( preloader.requested(), [ 'https://cdn.test/modern-en.mp4', 'https://cdn.test/modern.webp' ] );
} );

test( 'the plug-in installs the preloader on the onboarding API', () => {
	const addon = readFileSync( new URL( './onboarding-addon-preload.js', import.meta.url ), 'utf8' );
	assert.ok( addon.includes( "import { createPreloader } from '@ihq/aicoach/onboarding-preload';" ) );
	assert.ok( addon.includes( 'export function install( api ) {' ) );
	assert.ok( addon.includes( 'createPreloader( { api, document } );' ) );
} );

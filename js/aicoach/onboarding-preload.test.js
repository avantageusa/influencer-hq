import { test } from 'node:test';
import assert from 'node:assert/strict';
import { registerHooks } from 'node:module';
import { createFakeDocument } from './fake-dom.js';
import { createPreloader, ONBOARDING_EVENTS } from './onboarding-preload.js';

// In the browser, WordPress's import map resolves "@ihq/aicoach/<name>" to js/aicoach/<name>.js.
// Do the same here so the plug-in can be imported and run as it is shipped.
const AICOACH_SPECIFIER_PREFIX = '@ihq/aicoach/';
registerHooks( {
	resolve: ( specifier, context, nextResolve ) => {
		if ( ! specifier.startsWith( AICOACH_SPECIFIER_PREFIX ) ) {
			return nextResolve( specifier, context );
		}
		const name = specifier.slice( AICOACH_SPECIFIER_PREFIX.length );
		return { url: new URL( './' + name + '.js', import.meta.url ).href, shortCircuit: true };
	},
} );

// The onboarding API as the preloader sees it: events, and clip/image lookups in the active language.
function createFakeApi( { clips = {}, images = {} } = {} ) {
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
	return {
		api,
		listeners,
		screen: ( stage, next ) => emit( 'screen', { stage, next } ),
		switchTo: ( code ) => {
			locale = code;
			emit( 'locale', code );
		},
	};
}

// A fake document that records every element created and, with withLoad, counts load() calls.
function createRecordingDocument( { withLoad = false } = {} ) {
	const created = [];
	const document = createFakeDocument();
	const createElement = document.createElement;
	document.createElement = ( tag ) => {
		const element = createElement( tag );
		if ( withLoad ) {
			element.loadCalls = 0;
			element.load = () => {
				element.loadCalls += 1;
			};
		}
		created.push( element );
		return element;
	};
	return { document, created };
}

function setup( { clips = {}, images = {}, withLoad = false } = {} ) {
	const { api, screen, switchTo } = createFakeApi( { clips, images } );
	const { document, created } = createRecordingDocument( { withLoad } );
	const preloader = createPreloader( { api, document } );
	return { preloader, created, screen, switchTo };
}

const CLIPS = {
	modern_era: { en: 'https://cdn.test/modern-en.mp4', ja: 'https://cdn.test/modern-ja.mp4' },
	baccarat_experience: { en: 'https://cdn.test/bacc-en.mp4' },
};
const IMAGES = { modern_era: 'https://cdn.test/modern.webp' };

test( 'the event names match what the engine and boot modules emit', () => {
	assert.deepEqual( ONBOARDING_EVENTS, { SCREEN: 'screen', LOCALE: 'locale' } );
	assert.equal( Object.isFrozen( ONBOARDING_EVENTS ), true );
} );

test( 'when a screen starts, the next screen clip and hero image are requested', () => {
	const { preloader, created, screen } = setup( { clips: CLIPS, images: IMAGES } );
	screen( 'game_of_kings', 'modern_era' );
	assert.deepEqual( preloader.requested(), [ 'https://cdn.test/modern-en.mp4', 'https://cdn.test/modern.webp' ] );
	assert.equal( created.length, 2 );
	const [ video, image ] = created;
	assert.equal( video.tagName, 'VIDEO' );
	assert.equal( video.getAttribute( 'src' ), 'https://cdn.test/modern-en.mp4' );
	assert.equal( video.getAttribute( 'preload' ), 'auto' );
	assert.equal( video.getAttribute( 'muted' ), '' );
	assert.equal( video.muted, true );
	assert.equal( video.getAttribute( 'playsinline' ), '' );
	assert.equal( video.parentNode, null, 'detached: nothing is added to the page' );
	assert.equal( image.tagName, 'IMG' );
	assert.equal( image.getAttribute( 'src' ), 'https://cdn.test/modern.webp' );
	assert.equal( image.parentNode, null, 'detached: nothing is added to the page' );
	assert.equal( preloader.currentClip(), video );
} );

test( 'a language switch preloads the next clip in the new language', () => {
	const { preloader, created, screen, switchTo } = setup( { clips: CLIPS, images: IMAGES } );
	screen( 'game_of_kings', 'modern_era' );
	switchTo( 'ja' );
	assert.deepEqual( preloader.requested(), [ 'https://cdn.test/modern-en.mp4', 'https://cdn.test/modern.webp', 'https://cdn.test/modern-ja.mp4' ] );
	assert.equal( created.length, 3 );
	assert.equal( created[ 0 ].hasAttribute( 'src' ), false, 'the en clip is released' );
	assert.equal( preloader.currentClip(), created[ 2 ] );
	assert.equal( preloader.currentClip().getAttribute( 'src' ), 'https://cdn.test/modern-ja.mp4' );
} );

test( 'switching back to a language preloads its clip again (en -> ja -> en)', () => {
	const { preloader, created, screen, switchTo } = setup( { clips: CLIPS, images: IMAGES } );
	screen( 'game_of_kings', 'modern_era' );
	switchTo( 'ja' );
	switchTo( 'en' );
	assert.deepEqual( preloader.requested(), [
		'https://cdn.test/modern-en.mp4',
		'https://cdn.test/modern.webp',
		'https://cdn.test/modern-ja.mp4',
		'https://cdn.test/modern-en.mp4',
	] );
	assert.equal( created.length, 4, 'two en clips, one ja clip, one image' );
	const jaClip = created[ 2 ];
	assert.equal( jaClip.getAttribute( 'src' ), null, 'the ja clip is released' );
	assert.equal( preloader.currentClip(), created[ 3 ] );
	assert.equal( preloader.currentClip().getAttribute( 'src' ), 'https://cdn.test/modern-en.mp4' );
} );

test( 'only the latest clip keeps loading; the previous one is released', () => {
	const { preloader, created, screen } = setup( { clips: CLIPS } );
	screen( 'game_of_kings', 'modern_era' );
	const first = created[ 0 ];
	screen( 'modern_era', 'baccarat_experience' );
	assert.equal( first.hasAttribute( 'src' ), false );
	assert.equal( preloader.currentClip(), created[ 1 ] );
	assert.equal( preloader.currentClip().getAttribute( 'src' ), 'https://cdn.test/bacc-en.mp4' );
} );

test( 'a released clip is told to stop fetching with load()', () => {
	const { created, screen } = setup( { clips: CLIPS, withLoad: true } );
	screen( 'game_of_kings', 'modern_era' );
	assert.equal( created[ 0 ].loadCalls, 0 );
	screen( 'modern_era', 'baccarat_experience' );
	assert.equal( created[ 0 ].loadCalls, 1 );
	assert.equal( created[ 1 ].loadCalls, 0 );
} );

test( 'repeating a screen does not request its clip or image again', () => {
	const { preloader, created, screen } = setup( { clips: CLIPS, images: IMAGES } );
	screen( 'game_of_kings', 'modern_era' );
	screen( 'game_of_kings', 'modern_era' );
	assert.equal( created.length, 2 );
	assert.equal( created[ 0 ].getAttribute( 'src' ), 'https://cdn.test/modern-en.mp4', 'the loading clip is kept' );
	assert.deepEqual( preloader.requested(), [ 'https://cdn.test/modern-en.mp4', 'https://cdn.test/modern.webp' ] );
} );

test( 'a hero image is requested once, even after the clip changes', () => {
	const { preloader, created, screen, switchTo } = setup( { clips: CLIPS, images: IMAGES } );
	screen( 'game_of_kings', 'modern_era' );
	switchTo( 'ja' );
	const images = created.filter( ( element ) => 'IMG' === element.tagName );
	assert.equal( images.length, 1 );
	assert.deepEqual( preloader.requested(), [ 'https://cdn.test/modern-en.mp4', 'https://cdn.test/modern.webp', 'https://cdn.test/modern-ja.mp4' ] );
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
	const { preloader, created, switchTo } = setup( { clips: CLIPS } );
	switchTo( 'ja' );
	assert.deepEqual( preloader.requested(), [] );
	assert.deepEqual( created, [] );
} );

test( 'a malformed screen event does nothing', () => {
	const { api, listeners } = createFakeApi( { clips: CLIPS, images: IMAGES } );
	const { document, created } = createRecordingDocument();
	const preloader = createPreloader( { api, document } );
	listeners.screen[ 0 ]( undefined );
	listeners.screen[ 0 ]( null );
	listeners.screen[ 0 ]( {} );
	listeners.locale[ 0 ]( 'ja' );
	assert.deepEqual( preloader.requested(), [] );
	assert.deepEqual( created, [] );
	assert.equal( preloader.currentClip(), null );
} );

test( 'preload() can be called directly for a stage', () => {
	const { preloader } = setup( { clips: CLIPS, images: IMAGES } );
	preloader.preload( 'modern_era' );
	preloader.preload( null );
	assert.deepEqual( preloader.requested(), [ 'https://cdn.test/modern-en.mp4', 'https://cdn.test/modern.webp' ] );
} );

test( 'the plug-in installs the preloader on the onboarding API', async () => {
	const { install } = await import( './onboarding-addon-preload.js' );
	const { api, listeners, screen } = createFakeApi( { clips: CLIPS, images: IMAGES } );
	const { document, created } = createRecordingDocument();
	const preloader = install( api, { document } );
	assert.deepEqual( Object.keys( listeners ), [ 'screen', 'locale' ] );
	assert.equal( listeners.screen.length, 1 );
	assert.equal( listeners.locale.length, 1 );
	screen( 'game_of_kings', 'modern_era' );
	assert.deepEqual( preloader.requested(), [ 'https://cdn.test/modern-en.mp4', 'https://cdn.test/modern.webp' ] );
	assert.equal( created.length, 2 );
} );

test( 'the plug-in uses the page document when none is passed', async () => {
	const { install } = await import( './onboarding-addon-preload.js' );
	const { api, screen } = createFakeApi( { clips: CLIPS } );
	const { document, created } = createRecordingDocument();
	const previousDocument = globalThis.document;
	globalThis.document = document;
	try {
		const preloader = install( api );
		screen( 'game_of_kings', 'modern_era' );
		assert.deepEqual( preloader.requested(), [ 'https://cdn.test/modern-en.mp4' ] );
		assert.equal( created.length, 1 );
	} finally {
		globalThis.document = previousDocument;
	}
} );

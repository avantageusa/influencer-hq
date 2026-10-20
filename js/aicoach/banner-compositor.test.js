import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createFakeDocument } from './fake-dom.js';
import { createBannerCompositor, COMPOSITED_CLASS, BYPASS_CLASS, IDLE_CLASS, WRONG_DIMENSIONS_STATUS } from './banner-compositor.js';

const CONFIG = {
	moduleUrl: 'https://example.test/theme/js/vendor/sami-banner/sami-banner-compositor.js?ver=1',
	calibrationUrl: 'https://example.test/theme/js/vendor/sami-banner/calibration.json?ver=2',
	posterUrl: 'https://example.test/theme/js/vendor/sami-banner/poster.webp?ver=3',
	avatarId: 'cf6b6e7d-be88-4b9b-a31d-5f065b90cd9a',
	showDisclosure: true,
	preserveVideoCorner: true,
};
const POSTER = { naturalWidth: 1152 };
const CALIBRATION = { avatar_id: 'cf6b6e7d-be88-4b9b-a31d-5f065b90cd9a' };

function setup( { config = CONFIG, failures = {}, wrapClasses = '', canvasStatus } = {} ) {
	const document = createFakeDocument();
	const wrap = document.createElement( 'div' );
	wrap.className = wrapClasses;
	const video = document.createElement( 'video' );
	const canvas = document.createElement( 'canvas' );
	if ( undefined !== canvasStatus ) {
		canvas.dataset.status = canvasStatus;
	}
	const log = [];
	const warnings = [];
	const observers = [];

	const banner = {
		draw: () => log.push( [ 'draw' ] ),
		reset: () => log.push( [ 'reset' ] ),
		destroy: () => log.push( [ 'destroy' ] ),
	};
	const compositor = createBannerCompositor( {
		config,
		wrap,
		video,
		canvas,
		loadModule: async ( url ) => {
			log.push( [ 'module', url ] );
			if ( failures.module ) {
				throw new Error( 'module failed' );
			}
			return {
				createSamiBanner: ( args ) => {
					log.push( [ 'create', args ] );
					if ( failures.create ) {
						throw new Error( 'Avatar calibration mismatch' );
					}
					return banner;
				},
			};
		},
		loadPoster: async ( url ) => {
			log.push( [ 'poster', url ] );
			if ( failures.poster ) {
				throw new Error( 'poster failed' );
			}
			return POSTER;
		},
		loadCalibration: async ( url ) => {
			log.push( [ 'calibration', url ] );
			if ( failures.calibration ) {
				throw new Error( 'calibration failed' );
			}
			return CALIBRATION;
		},
		createObserver: ( callback ) => {
			const observer = {
				target: null,
				options: null,
				disconnected: false,
				observe( target, options ) {
					this.target = target;
					this.options = options;
				},
				disconnect() {
					this.disconnected = true;
				},
				fire: () => callback(),
			};
			observers.push( observer );
			return observer;
		},
		logger: { warn: ( ...args ) => warnings.push( args ) },
	} );
	const wrapObserver = () => observers[ 0 ];
	const canvasObserver = () => observers[ 1 ];
	return { compositor, wrap, video, canvas, log, warnings, observers, wrapObserver, canvasObserver, banner };
}

function callsOf( log, name ) {
	return log.filter( ( entry ) => name === entry[ 0 ] );
}

test( 'nothing is loaded without a config, a canvas, a wrapper or a video', async () => {
	for ( const missing of [ 'config', 'canvas', 'wrap', 'video' ] ) {
		const document = createFakeDocument();
		const parts = { config: CONFIG, canvas: document.createElement( 'canvas' ), wrap: document.createElement( 'div' ), video: document.createElement( 'video' ) };
		parts[ missing ] = null;
		const log = [];
		const compositor = createBannerCompositor( {
			...parts,
			loadModule: async () => log.push( 'module' ),
			loadPoster: async () => log.push( 'poster' ),
			loadCalibration: async () => log.push( 'calibration' ),
			createObserver: () => {
				log.push( 'observer' );
				return { observe() {}, disconnect() {} };
			},
		} );
		assert.equal( await compositor.start(), false, 'start without ' + missing );
		assert.deepEqual( log, [], 'nothing loaded without ' + missing );
	}
} );

test( 'starts: loads the three assets, hands the adapter the shared video and the configured options, shows the canvas', async () => {
	const { compositor, wrap, video, canvas, log } = setup();
	assert.equal( await compositor.start(), true );
	assert.deepEqual( callsOf( log, 'module' ), [ [ 'module', CONFIG.moduleUrl ] ] );
	assert.deepEqual( callsOf( log, 'poster' ), [ [ 'poster', CONFIG.posterUrl ] ] );
	assert.deepEqual( callsOf( log, 'calibration' ), [ [ 'calibration', CONFIG.calibrationUrl ] ] );
	assert.deepEqual( callsOf( log, 'create' ), [
		[
			'create',
			{
				canvas,
				video,
				poster: POSTER,
				calibration: CALIBRATION,
				avatarId: CONFIG.avatarId,
				showDisclosure: true,
				preserveVideoCorner: true,
			},
		],
	] );
	assert.equal( wrap.className, COMPOSITED_CLASS );
	assert.deepEqual( callsOf( log, 'reset' ).concat( callsOf( log, 'draw' ), callsOf( log, 'destroy' ) ), [] );
} );

test( 'the box and the corner options reach the adapter as configured', async () => {
	for ( const [ showDisclosure, preserveVideoCorner ] of [ [ false, true ], [ true, false ], [ false, false ] ] ) {
		const { compositor, log } = setup( { config: { ...CONFIG, showDisclosure, preserveVideoCorner } } );
		await compositor.start();
		const args = callsOf( log, 'create' )[ 0 ][ 1 ];
		assert.deepEqual( [ args.showDisclosure, args.preserveVideoCorner ], [ showDisclosure, preserveVideoCorner ] );
	}
} );

test( 'a failure at any step leaves the page on its video: no class, no observers, one warning', async () => {
	for ( const failing of [ 'module', 'poster', 'calibration', 'create' ] ) {
		const { compositor, wrap, observers, warnings, log } = setup( { failures: { [ failing ]: true } } );
		assert.equal( await compositor.start(), false, failing );
		assert.equal( wrap.className, '', failing + ': no class' );
		assert.deepEqual( observers, [], failing + ': no observers' );
		assert.equal( warnings.length, 1, failing + ': one warning' );
		assert.equal( warnings[ 0 ][ 0 ], '[aicoach] the composed banner could not start, the page keeps its video:' );
		assert.equal( warnings[ 0 ][ 1 ].message, 'create' === failing ? 'Avatar calibration mismatch' : failing + ' failed' );
		assert.deepEqual( callsOf( log, 'destroy' ), [], failing + ': nothing to destroy' );
	}
} );

test( 'the adapter is only built when all three assets loaded', async () => {
	for ( const failing of [ 'module', 'poster', 'calibration' ] ) {
		const { compositor, log } = setup( { failures: { [ failing ]: true } } );
		await compositor.start();
		assert.deepEqual( callsOf( log, 'create' ), [], failing );
	}
} );

test( 'watches the wrapper class and the canvas status, and nothing else', async () => {
	const { compositor, wrap, canvas, observers } = setup();
	await compositor.start();
	assert.equal( observers.length, 2 );
	assert.equal( observers[ 0 ].target, wrap );
	assert.deepEqual( observers[ 0 ].options, { attributes: true, attributeFilter: [ 'class' ] } );
	assert.equal( observers[ 1 ].target, canvas );
	assert.deepEqual( observers[ 1 ].options, { attributes: true, attributeFilter: [ 'data-status' ] } );
} );

test( 'going idle shows the poster once, however often the class is rewritten', async () => {
	const { compositor, wrap, log, wrapObserver } = setup();
	await compositor.start();
	wrap.classList.add( IDLE_CLASS );
	wrapObserver().fire();
	assert.deepEqual( callsOf( log, 'reset' ), [ [ 'reset' ] ] );
	wrap.classList.add( 'something-else' );
	wrapObserver().fire();
	assert.deepEqual( callsOf( log, 'reset' ), [ [ 'reset' ] ] );
	assert.deepEqual( callsOf( log, 'draw' ), [] );
} );

test( 'leaving idle draws what the video shows now, and does not reset', async () => {
	const { compositor, wrap, log, wrapObserver } = setup();
	await compositor.start();
	wrap.classList.add( IDLE_CLASS );
	wrapObserver().fire();
	wrap.classList.remove( IDLE_CLASS );
	wrapObserver().fire();
	assert.deepEqual( log.filter( ( entry ) => [ 'reset', 'draw' ].includes( entry[ 0 ] ) ), [ [ 'reset' ], [ 'draw' ] ] );
	wrapObserver().fire();
	assert.deepEqual( callsOf( log, 'draw' ), [ [ 'draw' ] ] );
} );

test( 'a banner that starts while the page is idle is left to the adapter: no reset, then a draw when idle ends', async () => {
	const { compositor, wrap, log, wrapObserver } = setup( { wrapClasses: IDLE_CLASS } );
	await compositor.start();
	assert.deepEqual( callsOf( log, 'reset' ), [] );
	wrap.classList.remove( IDLE_CLASS );
	wrapObserver().fire();
	assert.deepEqual( callsOf( log, 'draw' ), [ [ 'draw' ] ] );
} );

test( 'a frame the calibration does not fit shows the video layer again, and the canvas takes over when it fits', async () => {
	const { compositor, wrap, canvas, canvasObserver } = setup();
	await compositor.start();
	assert.equal( wrap.classList.contains( BYPASS_CLASS ), false );
	canvas.dataset.status = WRONG_DIMENSIONS_STATUS;
	canvasObserver().fire();
	assert.equal( wrap.classList.contains( BYPASS_CLASS ), true );
	assert.equal( wrap.classList.contains( COMPOSITED_CLASS ), true );
	for ( const status of [ 'playing', 'holding', 'poster', 'stopped' ] ) {
		canvas.dataset.status = WRONG_DIMENSIONS_STATUS;
		canvasObserver().fire();
		canvas.dataset.status = status;
		canvasObserver().fire();
		assert.equal( wrap.classList.contains( BYPASS_CLASS ), false, status );
	}
} );

test( 'a canvas that already reports a wrong frame at start is bypassed at once', async () => {
	const { compositor, wrap } = setup( { canvasStatus: WRONG_DIMENSIONS_STATUS } );
	await compositor.start();
	assert.equal( wrap.className, COMPOSITED_CLASS + ' ' + BYPASS_CLASS );
} );

test( 'stop destroys the adapter, disconnects both observers and puts the page back on the video', async () => {
	const { compositor, wrap, canvas, log, observers, canvasObserver } = setup();
	await compositor.start();
	canvas.dataset.status = WRONG_DIMENSIONS_STATUS;
	canvasObserver().fire();
	compositor.stop();
	assert.deepEqual( callsOf( log, 'destroy' ), [ [ 'destroy' ] ] );
	assert.deepEqual( observers.map( ( observer ) => observer.disconnected ), [ true, true ] );
	assert.equal( wrap.className, '' );
	compositor.stop();
	assert.deepEqual( callsOf( log, 'destroy' ), [ [ 'destroy' ] ] );
} );

test( 'stop before start, and stop after a failed start, do nothing', async () => {
	const { compositor, log } = setup();
	compositor.stop();
	assert.deepEqual( log, [] );
	const failed = setup( { failures: { module: true } } );
	await failed.compositor.start();
	failed.compositor.stop();
	assert.deepEqual( callsOf( failed.log, 'destroy' ), [] );
} );

test( 'starting twice loads once and gives both callers the same answer', async () => {
	const { compositor, log } = setup();
	const [ first, second ] = await Promise.all( [ compositor.start(), compositor.start() ] );
	assert.deepEqual( [ first, second ], [ true, true ] );
	assert.equal( callsOf( log, 'module' ).length, 1 );
	assert.equal( callsOf( log, 'create' ).length, 1 );
	assert.equal( await compositor.start(), true );
	assert.equal( callsOf( log, 'create' ).length, 1 );
} );

test( 'the compositor can be started again after it was stopped', async () => {
	const { compositor, wrap, log, observers } = setup();
	await compositor.start();
	compositor.stop();
	assert.equal( await compositor.start(), true );
	assert.equal( callsOf( log, 'create' ).length, 2 );
	assert.equal( wrap.className, COMPOSITED_CLASS );
	assert.equal( observers.length, 4 );
} );

test( 'a failed start is remembered: starting again does not load again', async () => {
	const { compositor, log } = setup( { failures: { module: true } } );
	assert.equal( await compositor.start(), false );
	assert.equal( await compositor.start(), false );
	assert.equal( callsOf( log, 'module' ).length, 1 );
} );

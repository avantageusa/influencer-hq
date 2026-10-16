import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createFakeClock } from './fake-clock.js';
import { createStallWatchdog } from './stall-watchdog.js';
import { buildSentenceCues, subtitleAt } from './onboarding-subtitles.js';
import { createNarrator, sentenceIndexAt, captionOffsetMs, CAPTION_TICK_MS, CLIP_STALL_MS } from './onboarding-narrator.js';

const SCRIPT = 'Aa. Bbbbbb.';

// A <video> stand-in: records what the narrator does, and lets a test fire media events.
function createFakeVideo( { playResults = [] } = {} ) {
	const listeners = new Map();
	const video = new EventTarget();
	video.muted = false;
	video.src = '';
	video.currentTime = 0;
	video.duration = 0;
	video.calls = [];
	video.play = () => {
		video.calls.push( [ 'play', video.muted ] );
		const next = playResults.shift();
		return next ? next() : Promise.resolve();
	};
	video.pause = () => video.calls.push( [ 'pause' ] );
	const add = video.addEventListener.bind( video );
	const remove = video.removeEventListener.bind( video );
	video.addEventListener = ( type, fn ) => {
		listeners.set( type, ( listeners.get( type ) || 0 ) + 1 );
		add( type, fn );
	};
	video.removeEventListener = ( type, fn ) => {
		listeners.set( type, ( listeners.get( type ) || 0 ) - 1 );
		remove( type, fn );
	};
	video.listenerCount = () => Array.from( listeners.values() ).reduce( ( a, b ) => a + b, 0 );
	video.fire = ( type ) => video.dispatchEvent( new Event( type ) );
	return video;
}

function setup( options = {} ) {
	const clock = createFakeClock();
	const video = createFakeVideo( options );
	const subtitles = [];
	const mutedChanges = [];
	const estimates = [];
	const narrator = createNarrator( {
		video,
		onSubtitle: ( text ) => subtitles.push( text ),
		onMutedChange: ( muted ) => mutedChanges.push( muted ),
		buildSentenceCues,
		subtitleAt,
		estimateDurationMs: ( text ) => {
			estimates.push( text );
			return options.estimateMs ?? 1000;
		},
		createStallWatchdog,
		setTimeout: clock.setTimeout,
		clearTimeout: clock.clearTimeout,
		now: clock.now,
	} );
	return { clock, video, subtitles, mutedChanges, estimates, narrator };
}

const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

test( 'constants', () => {
	assert.equal( CAPTION_TICK_MS, 100 );
	assert.equal( CLIP_STALL_MS, 8000 );
} );

test( 'without a clip, subtitles play at reading pace and clear at the end (caption-only fallback)', async () => {
	const { clock, video, subtitles, estimates, narrator } = setup();
	const done = narrator.play( { script: SCRIPT } );
	assert.deepEqual( estimates, [ SCRIPT ] );
	assert.deepEqual( subtitles, [ 'Aa.' ] );
	clock.tick( 200 );
	assert.deepEqual( subtitles, [ 'Aa.', 'Aa.', 'Aa.' ] );
	clock.tick( 100 );
	assert.equal( subtitles.at( -1 ), 'Bbbbbb.' );
	clock.tick( 700 );
	assert.equal( subtitles.at( -1 ), '' );
	assert.deepEqual( await done, { mode: 'captions' } );
	assert.equal( clock.pendingCount(), 0 );
	assert.equal( video.src, '' );
	assert.deepEqual( video.calls, [] );
} );

test( 'with a clip, subtitles follow the clip time and clear when it ends', async () => {
	const { clock, video, subtitles, estimates, narrator } = setup();
	const done = narrator.play( { script: SCRIPT, clipUrl: 'https://cdn.test/1.mp4' } );
	assert.equal( video.src, 'https://cdn.test/1.mp4' );
	assert.deepEqual( video.calls, [ [ 'play', false ] ] );
	video.duration = 2;
	video.fire( 'loadedmetadata' );
	video.currentTime = 0.1;
	video.fire( 'timeupdate' );
	video.currentTime = 1.0;
	video.fire( 'timeupdate' );
	assert.deepEqual( subtitles, [ 'Aa.', 'Bbbbbb.' ] );
	video.fire( 'ended' );
	assert.deepEqual( await done, { mode: 'clip' } );
	assert.equal( subtitles.at( -1 ), '' );
	assert.deepEqual( estimates, [] );
	assert.equal( video.listenerCount(), 0 );
	assert.equal( clock.pendingCount(), 0 );
	assert.deepEqual( video.calls, [ [ 'play', false ], [ 'pause' ] ] );
} );

test( 'mute silences the clip and keeps the subtitles; unmute restores the sound', async () => {
	const { video, subtitles, narrator } = setup();
	narrator.play( { script: SCRIPT, clipUrl: 'clip.mp4' } );
	video.duration = 1;
	video.fire( 'loadedmetadata' );
	narrator.setMuted( true );
	assert.equal( video.muted, true );
	assert.equal( narrator.isMuted(), true );
	video.currentTime = 0.5;
	video.fire( 'timeupdate' );
	assert.deepEqual( subtitles, [ 'Bbbbbb.' ] );
	narrator.setMuted( false );
	assert.equal( video.muted, false );
	assert.equal( narrator.isMuted(), false );
	video.currentTime = 0.1;
	video.fire( 'timeupdate' );
	assert.deepEqual( subtitles, [ 'Bbbbbb.', 'Aa.' ] );
} );

test( 'a muted narrator starts the next clip muted', () => {
	const { video, narrator } = setup();
	narrator.setMuted( true );
	narrator.play( { script: SCRIPT, clipUrl: 'clip.mp4' } );
	assert.deepEqual( video.calls, [ [ 'play', true ] ] );
} );

test( 'mute while captions play changes nothing about the subtitles', () => {
	const { clock, subtitles, narrator } = setup();
	narrator.play( { script: SCRIPT } );
	narrator.setMuted( true );
	clock.tick( 300 );
	assert.equal( subtitles.at( -1 ), 'Bbbbbb.' );
} );

test( 'a clip that errors falls back to captions for the same line', async () => {
	const { clock, video, subtitles, estimates, narrator } = setup();
	const done = narrator.play( { script: SCRIPT, clipUrl: 'missing.mp4' } );
	video.fire( 'error' );
	assert.deepEqual( estimates, [ SCRIPT ] );
	assert.deepEqual( video.calls, [ [ 'play', false ], [ 'pause' ] ] );
	assert.equal( video.listenerCount(), 0 );
	assert.deepEqual( subtitles, [ 'Aa.' ] );
	clock.tick( 1000 );
	assert.deepEqual( await done, { mode: 'captions' } );
	assert.equal( subtitles.at( -1 ), '' );
} );

for ( const duration of [ Infinity, NaN ] ) {
	test( 'a clip reporting its duration as ' + duration + ' spreads the subtitles over the reading-pace length', () => {
		const { video, subtitles, estimates, narrator } = setup();
		narrator.play( { script: SCRIPT, clipUrl: 'live.m3u8' } );
		video.duration = duration;
		video.fire( 'loadedmetadata' );
		assert.deepEqual( estimates, [ SCRIPT ] );
		video.currentTime = 0.1;
		video.fire( 'timeupdate' );
		video.currentTime = 0.5;
		video.fire( 'timeupdate' );
		assert.deepEqual( subtitles, [ 'Aa.', 'Bbbbbb.' ] );
	} );
}

test( 'a clip that breaks off mid-line carries on in captions from the sentence Sami had reached', async () => {
	const { clock, video, subtitles, estimates, narrator } = setup();
	const done = narrator.play( { script: SCRIPT, clipUrl: 'clip.mp4' } );
	video.duration = 2;
	video.fire( 'loadedmetadata' );
	video.currentTime = 1;
	video.fire( 'timeupdate' );
	video.fire( 'error' );
	assert.deepEqual( estimates, [ SCRIPT ] );
	assert.deepEqual( subtitles, [ 'Bbbbbb.', 'Bbbbbb.' ] );
	// 'Bbbbbb.' starts 300 ms into the 1000 ms caption line, so 700 ms are left.
	clock.tick( 600 );
	assert.equal( subtitles.at( -1 ), 'Bbbbbb.' );
	assert.equal( clock.pendingCount(), 1 );
	clock.tick( 100 );
	assert.equal( subtitles.at( -1 ), '' );
	assert.deepEqual( await done, { mode: 'captions' } );
	assert.equal( clock.pendingCount(), 0 );
} );

test( 'sentenceIndexAt: the last sentence that had started, 0 before the first or with no cues', () => {
	const cues = [ { text: 'A.', startMs: 0, endMs: 100 }, { text: 'B.', startMs: 100, endMs: 300 } ];
	assert.equal( sentenceIndexAt( cues, 0 ), 0 );
	assert.equal( sentenceIndexAt( cues, 99 ), 0 );
	assert.equal( sentenceIndexAt( cues, 100 ), 1 );
	assert.equal( sentenceIndexAt( cues, 5000 ), 1 );
	assert.equal( sentenceIndexAt( cues, -1 ), 0 );
	assert.equal( sentenceIndexAt( [], 50 ), 0 );
} );

test( 'captionOffsetMs: where a sentence starts, 0 for one that does not exist', () => {
	const cues = [ { text: 'A.', startMs: 0, endMs: 100 }, { text: 'B.', startMs: 100, endMs: 300 } ];
	assert.equal( captionOffsetMs( cues, 0 ), 0 );
	assert.equal( captionOffsetMs( cues, 1 ), 100 );
	assert.equal( captionOffsetMs( cues, 2 ), 0 );
	assert.equal( captionOffsetMs( [], 0 ), 0 );
} );

test( 'a clip whose play() is rejected for another reason falls back to captions', async () => {
	const { video, estimates, narrator } = setup( { playResults: [ () => Promise.reject( Object.assign( new Error( 'x' ), { name: 'NotSupportedError' } ) ) ] } );
	narrator.play( { script: SCRIPT, clipUrl: 'bad.mp4' } );
	await flush();
	assert.deepEqual( estimates, [ SCRIPT ] );
	assert.deepEqual( video.calls, [ [ 'play', false ], [ 'pause' ] ] );
} );

test( 'autoplay with sound refused: the clip plays muted, the sound button is told, subtitles show', async () => {
	const refused = () => Promise.reject( Object.assign( new Error( 'no' ), { name: 'NotAllowedError' } ) );
	const { video, mutedChanges, subtitles, estimates, narrator } = setup( { playResults: [ refused ] } );
	narrator.play( { script: SCRIPT, clipUrl: 'clip.mp4' } );
	await flush();
	assert.deepEqual( video.calls, [ [ 'play', false ], [ 'play', true ] ] );
	assert.deepEqual( mutedChanges, [ true ] );
	assert.equal( narrator.isMuted(), true );
	assert.deepEqual( estimates, [] );
	video.duration = 1;
	video.fire( 'loadedmetadata' );
	video.currentTime = 0.1;
	video.fire( 'timeupdate' );
	assert.deepEqual( subtitles, [ 'Aa.' ] );
} );

test( 'autoplay refused even muted: captions instead', async () => {
	const refused = () => Promise.reject( Object.assign( new Error( 'no' ), { name: 'NotAllowedError' } ) );
	const { estimates, narrator } = setup( { playResults: [ refused, refused ] } );
	narrator.play( { script: SCRIPT, clipUrl: 'clip.mp4' } );
	await flush();
	await flush();
	assert.deepEqual( estimates, [ SCRIPT ] );
} );

test( 'a clip refused while already muted falls back to captions, no second try', async () => {
	const refused = () => Promise.reject( Object.assign( new Error( 'no' ), { name: 'NotAllowedError' } ) );
	const { video, mutedChanges, estimates, narrator } = setup( { playResults: [ refused ] } );
	narrator.setMuted( true );
	narrator.play( { script: SCRIPT, clipUrl: 'clip.mp4' } );
	await flush();
	assert.deepEqual( mutedChanges, [] );
	assert.deepEqual( estimates, [ SCRIPT ] );
	assert.deepEqual( video.calls, [ [ 'play', true ], [ 'pause' ] ] );
} );

test( 'a clip that never progresses is abandoned for captions after the stall time', () => {
	const { clock, video, estimates, narrator } = setup();
	narrator.play( { script: SCRIPT, clipUrl: 'slow.mp4' } );
	clock.tick( CLIP_STALL_MS - 1 );
	assert.deepEqual( estimates, [] );
	clock.tick( 1 );
	assert.deepEqual( estimates, [ SCRIPT ] );
	assert.deepEqual( video.calls, [ [ 'play', false ], [ 'pause' ] ] );
} );

test( 'progress re-arms the stall watchdog, so a long clip that plays is never cut off', () => {
	const { clock, video, estimates, narrator } = setup();
	narrator.play( { script: SCRIPT, clipUrl: 'long.mp4' } );
	video.duration = 60;
	video.fire( 'loadedmetadata' );
	for ( let second = 0; second < 30; second++ ) {
		clock.tick( 1000 );
		video.currentTime = second;
		video.fire( 'timeupdate' );
	}
	assert.deepEqual( estimates, [] );
} );

test( 'stop() ends the line, clears the subtitle and resolves', async () => {
	const { clock, video, subtitles, narrator } = setup();
	const done = narrator.play( { script: SCRIPT, clipUrl: 'clip.mp4' } );
	narrator.stop();
	assert.deepEqual( await done, { mode: 'clip' } );
	assert.deepEqual( subtitles, [ '' ] );
	assert.equal( video.listenerCount(), 0 );
	assert.equal( clock.pendingCount(), 0 );
	assert.deepEqual( video.calls, [ [ 'play', false ], [ 'pause' ] ] );
} );

test( 'stop() with nothing playing does nothing', () => {
	const { subtitles, narrator } = setup();
	narrator.stop();
	assert.deepEqual( subtitles, [] );
} );

test( 'playing a new line stops the previous one', async () => {
	const { clock, subtitles, narrator } = setup();
	const first = narrator.play( { script: 'First.' } );
	const second = narrator.play( { script: 'Second.' } );
	assert.deepEqual( await first, { mode: 'captions' } );
	assert.deepEqual( subtitles, [ 'First.', '', 'Second.' ] );
	clock.tick( 1000 );
	assert.deepEqual( await second, { mode: 'captions' } );
	assert.equal( clock.pendingCount(), 0 );
} );

test( 'a late play() rejection after the line was stopped changes nothing', async () => {
	let reject;
	const pending = () => new Promise( ( resolve, rejectFn ) => {
		reject = rejectFn;
	} );
	const { estimates, mutedChanges, narrator } = setup( { playResults: [ pending ] } );
	narrator.play( { script: SCRIPT, clipUrl: 'clip.mp4' } );
	narrator.stop();
	reject( Object.assign( new Error( 'aborted' ), { name: 'AbortError' } ) );
	await flush();
	assert.deepEqual( estimates, [] );
	assert.deepEqual( mutedChanges, [] );
} );

test( 'an empty script with no clip resolves straight away', async () => {
	const { subtitles, narrator } = setup( { estimateMs: 0 } );
	assert.deepEqual( await narrator.play( { script: '' } ), { mode: 'captions' } );
	assert.deepEqual( subtitles, [ '' ] );
} );

test( 'onMutedChange is optional', async () => {
	const clock = createFakeClock();
	const video = createFakeVideo( { playResults: [ () => Promise.reject( Object.assign( new Error( 'no' ), { name: 'NotAllowedError' } ) ) ] } );
	const narrator = createNarrator( {
		video,
		onSubtitle: () => {},
		buildSentenceCues,
		subtitleAt,
		estimateDurationMs: () => 1000,
		createStallWatchdog,
		setTimeout: clock.setTimeout,
		clearTimeout: clock.clearTimeout,
		now: clock.now,
	} );
	narrator.play( { script: SCRIPT, clipUrl: 'clip.mp4' } );
	await flush();
	assert.equal( narrator.isMuted(), true );
} );

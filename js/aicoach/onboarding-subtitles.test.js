import { test } from 'node:test';
import assert from 'node:assert/strict';
import { splitSentences, buildSentenceCues, subtitleAt } from './onboarding-subtitles.js';

test( 'splitSentences splits a script into trimmed sentences', () => {
	assert.deepEqual( splitSentences( 'Hello there.  I am Sami! Ready?' ), [ 'Hello there.', 'I am Sami!', 'Ready?' ] );
} );

test( 'splitSentences without Intl.Segmenter splits after end marks followed by a space', () => {
	assert.deepEqual( splitSentences( 'Hello there. I am Sami! Ready? Yes', { Segmenter: null } ), [ 'Hello there.', 'I am Sami!', 'Ready?', 'Yes' ] );
	assert.deepEqual( splitSentences( 'He said "go." Then left.', { Segmenter: null } ), [ 'He said "go."', 'Then left.' ] );
} );

test( 'splitSentences handles Chinese full stops with the segmenter', () => {
	assert.deepEqual( splitSentences( '你好。我是Sami。' ), [ '你好。', '我是Sami。' ] );
} );

test( 'splitSentences of nothing is nothing', () => {
	assert.deepEqual( splitSentences( '' ), [] );
	assert.deepEqual( splitSentences( '   ' ), [] );
	assert.deepEqual( splitSentences( null ), [] );
	assert.deepEqual( splitSentences( undefined ), [] );
} );

test( 'splitSentences collapses line breaks and runs of spaces', () => {
	assert.deepEqual( splitSentences( 'One\n\ntwo.   Three.' ), [ 'One two.', 'Three.' ] );
} );

test( 'buildSentenceCues spreads sentences over the clip by length, back to back', () => {
	// 'Aa.' weighs 3, 'Bbbbbb.' weighs 7: 300 ms and 700 ms of a 1000 ms clip.
	assert.deepEqual( buildSentenceCues( 'Aa. Bbbbbb.', 1000 ), [
		{ text: 'Aa.', startMs: 0, endMs: 300 },
		{ text: 'Bbbbbb.', startMs: 300, endMs: 1000 },
	] );
} );

test( 'buildSentenceCues ignores spaces inside a sentence when weighing it', () => {
	assert.deepEqual( buildSentenceCues( 'A b. Cd.', 600 ), [
		{ text: 'A b.', startMs: 0, endMs: 300 },
		{ text: 'Cd.', startMs: 300, endMs: 600 },
	] );
} );

test( 'buildSentenceCues of an empty script or a clip with no length is empty', () => {
	assert.deepEqual( buildSentenceCues( '', 1000 ), [] );
	assert.deepEqual( buildSentenceCues( 'Hi.', 0 ), [] );
	assert.deepEqual( buildSentenceCues( 'Hi.', -5 ), [] );
	assert.deepEqual( buildSentenceCues( 'Hi.', Number.NaN ), [] );
	assert.deepEqual( buildSentenceCues( 'Hi.', 'soon' ), [] );
} );

test( 'buildSentenceCues accepts a numeric string duration', () => {
	assert.deepEqual( buildSentenceCues( 'Hi.', '500' ), [ { text: 'Hi.', startMs: 0, endMs: 500 } ] );
} );

test( 'subtitleAt shows the sentence being spoken and nothing once the clip has ended', () => {
	const cues = buildSentenceCues( 'Aa. Bbbbbb.', 1000 );
	assert.equal( subtitleAt( cues, -1 ), '' );
	assert.equal( subtitleAt( cues, 0 ), 'Aa.' );
	assert.equal( subtitleAt( cues, 299 ), 'Aa.' );
	assert.equal( subtitleAt( cues, 300 ), 'Bbbbbb.' );
	assert.equal( subtitleAt( cues, 999 ), 'Bbbbbb.' );
	assert.equal( subtitleAt( cues, 1000 ), '' );
	assert.equal( subtitleAt( cues, 5000 ), '' );
} );

test( 'subtitleAt with no cues is empty', () => {
	assert.equal( subtitleAt( [], 10 ), '' );
} );

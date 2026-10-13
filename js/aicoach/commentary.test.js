import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
	MAX_PHRASE_WORDS,
	PHRASE_HOLD_MS,
	WORDS_PER_SECOND,
	buildCues,
	estimateDurationMs,
	splitIntoPhrases,
	tokenize,
	visibleTextAt,
} from './commentary.js';

const TWO_SENTENCES = 'One two. Three four.';

function rounded( cues ) {
	return cues.map( ( cue ) => ( {
		text: cue.text,
		startMs: Math.round( cue.startMs ),
		endMs: Math.round( cue.endMs ),
		words: cue.words.map( ( word ) => ( { text: word.text, lead: word.lead, startMs: Math.round( word.startMs ) } ) ),
	} ) );
}

test( 'tokenize keeps punctuation on its word and remembers the spacing', () => {
	assert.deepEqual( tokenize( "Hi, I'm Sami." ), [
		{ text: 'Hi,', lead: '' },
		{ text: "I'm", lead: ' ' },
		{ text: 'Sami.', lead: ' ' },
	] );
} );

test( 'tokenize collapses runs of spaces to one and ignores the ends', () => {
	assert.deepEqual( tokenize( '  Hello.   World  ' ), [
		{ text: 'Hello.', lead: '' },
		{ text: 'World', lead: ' ' },
	] );
} );

test( 'tokenize of nothing is nothing', () => {
	assert.deepEqual( tokenize( '' ), [] );
	assert.deepEqual( tokenize( null ), [] );
	assert.deepEqual( tokenize( '   ' ), [] );
} );

test( 'a phrase ends at a sentence, and at a comma once it has three words', () => {
	assert.deepEqual(
		splitIntoPhrases( "Hi, I'm Sami, your Coach.  Welcome to InfluencerHQ." ),
		[ "Hi, I'm Sami,", 'your Coach.', 'Welcome to InfluencerHQ.' ]
	);
} );

test( 'a comma too early in a phrase does not end it', () => {
	assert.deepEqual( splitIntoPhrases( 'Yes, we do. Really.' ), [ 'Yes, we do.', 'Really.' ] );
} );

test( 'an ellipsis ends a phrase', () => {
	assert.deepEqual( splitIntoPhrases( 'Click on me to ask... I will answer.' ), [ 'Click on me to ask...', 'I will answer.' ] );
} );

test( 'a long run of words is cut at the longest allowed phrase', () => {
	const words = Array.from( { length: MAX_PHRASE_WORDS + 2 }, ( _, index ) => 'w' + index );
	const phrases = splitIntoPhrases( words.join( ' ' ) );
	assert.equal( phrases.length, 2 );
	assert.equal( phrases[ 0 ].split( ' ' ).length, MAX_PHRASE_WORDS );
	assert.equal( phrases[ 1 ].split( ' ' ).length, 2 );
} );

test( 'a script without spaces is cut into phrases at its full stops and put back as written', () => {
	const script = '你好，我是萨米。欢迎来到InfluencerHQ。';
	const phrases = splitIntoPhrases( script );
	assert.equal( phrases.length, 2 );
	assert.equal( phrases[ 0 ], '你好，我是萨米。' );
	assert.equal( phrases[ 1 ], '欢迎来到InfluencerHQ。' );
	assert.equal( phrases.join( '' ), script );
} );

test( 'a phrase put back together keeps no spaces where the language has none', () => {
	assert.equal( splitIntoPhrases( 'こんにちは。ありがとう。' ).join( '' ), 'こんにちは。ありがとう。' );
} );

test( 'five words take two seconds at the default pace', () => {
	assert.equal( WORDS_PER_SECOND, 2.5 );
	assert.equal( estimateDurationMs( 'one two three four five.' ), 2000 );
} );

test( 'the estimate follows the pace it is given and ignores bare punctuation', () => {
	assert.equal( estimateDurationMs( 'one two three four five.', 5 ), 1000 );
	assert.equal( estimateDurationMs( 'one - two' ), 800 );
	assert.equal( estimateDurationMs( '' ), 0 );
} );

test( 'times are spread over the clip by size of word, with a pause after a full stop', () => {
	// Weights: One 3, "two." 3 + 6 pause, Three 5, "four." 4 + 6 pause = 27 over 10 000 ms.
	assert.deepEqual( rounded( buildCues( TWO_SENTENCES, 10000 ) ), [
		{
			text: 'One two.',
			startMs: 0,
			endMs: 4444,
			words: [
				{ text: 'One', lead: '', startMs: 0 },
				{ text: 'two.', lead: ' ', startMs: 1111 },
			],
		},
		{
			text: 'Three four.',
			startMs: 4444,
			endMs: 10000,
			words: [
				{ text: 'Three', lead: ' ', startMs: 4444 },
				{ text: 'four.', lead: ' ', startMs: 6296 },
			],
		},
	] );
} );

test( 'a lead-in of silence moves every word later and leaves the end where it is', () => {
	const cues = buildCues( TWO_SENTENCES, 12000, { leadInMs: 2000 } );
	assert.deepEqual( rounded( cues ), [
		{
			text: 'One two.',
			startMs: 2000,
			endMs: 6444,
			words: [
				{ text: 'One', lead: '', startMs: 2000 },
				{ text: 'two.', lead: ' ', startMs: 3111 },
			],
		},
		{
			text: 'Three four.',
			startMs: 6444,
			endMs: 12000,
			words: [
				{ text: 'Three', lead: ' ', startMs: 6444 },
				{ text: 'four.', lead: ' ', startMs: 8296 },
			],
		},
	] );
} );

test( 'there are no cues without text, without a length, or when the lead-in uses up the whole clip', () => {
	assert.deepEqual( buildCues( '', 5000 ), [] );
	assert.deepEqual( buildCues( TWO_SENTENCES, 0 ), [] );
	assert.deepEqual( buildCues( TWO_SENTENCES, -5 ), [] );
	assert.deepEqual( buildCues( TWO_SENTENCES, NaN ), [] );
	assert.deepEqual( buildCues( TWO_SENTENCES, 2000, { leadInMs: 2000 } ), [] );
} );

test( 'words appear as they are said and the phrase is replaced by the next one', () => {
	const cues = buildCues( TWO_SENTENCES, 10000 );
	assert.equal( visibleTextAt( cues, 0 ), 'One' );
	assert.equal( visibleTextAt( cues, 1000 ), 'One' );
	assert.equal( visibleTextAt( cues, 1112 ), 'One two.' );
	assert.equal( visibleTextAt( cues, 4443 ), 'One two.' );
	assert.equal( visibleTextAt( cues, 4445 ), 'Three' );
	assert.equal( visibleTextAt( cues, 9999 ), 'Three four.' );
} );

test( 'nothing is shown before the first word, and the last phrase clears after a short hold', () => {
	const cues = buildCues( TWO_SENTENCES, 12000, { leadInMs: 2000 } );
	assert.equal( visibleTextAt( cues, 1000 ), '' );
	assert.equal( visibleTextAt( cues, 11999 + PHRASE_HOLD_MS ), 'Three four.' );
	assert.equal( visibleTextAt( cues, 12000 + PHRASE_HOLD_MS ), '' );
	assert.equal( visibleTextAt( [], 500 ), '' );
} );

test( 'cue times never go backwards and the last phrase ends with the clip', () => {
	const script = "Hi, I'm Sami, your Coach.  Welcome to InfluencerHQ. We believe influencers who help build our company should have the opportunity to earn meaningful equity.";
	const cues = buildCues( script, 20000, { leadInMs: 500 } );
	const starts = cues.flatMap( ( cue ) => cue.words.map( ( word ) => word.startMs ) );
	assert.deepEqual( starts, [ ...starts ].sort( ( a, b ) => a - b ) );
	assert.equal( cues[ 0 ].startMs, 500 );
	assert.equal( Math.round( cues[ cues.length - 1 ].endMs ), 20000 );
	assert.deepEqual( cues.map( ( cue ) => cue.text ).join( ' ' ), script.replace( /\s{2,}/g, ' ' ) );
} );

test( 'the limits are the ones the design was tuned with', () => {
	assert.equal( MAX_PHRASE_WORDS, 8 );
	assert.equal( PHRASE_HOLD_MS, 300 );
} );

test( 'a comma inside a phrase adds a short pause', () => {
	// Weights: "One," 3 + 2.5 pause, two 3, "three." 5 + 6 pause = 19.5 over 19 500 ms, so one weight is one ms.
	const [ cue ] = buildCues( 'One, two three.', 19500 );
	assert.deepEqual( cue.words.map( ( word ) => Math.round( word.startMs ) ), [ 0, 5500, 8500 ] );
	assert.equal( Math.round( cue.endMs ), 19500 );
} );

test( 'a lone dash still takes a moment to say', () => {
	// Weights: one 3, "-" 1, two 3 = 7 over 7 000 ms.
	const [ cue ] = buildCues( 'one - two', 7000 );
	assert.deepEqual( cue.words.map( ( word ) => Math.round( word.startMs ) ), [ 0, 3000, 4000 ] );
} );

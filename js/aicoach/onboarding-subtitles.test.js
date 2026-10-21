import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
	splitSentences,
	splitPhrases,
	displayWidth,
	buildPhraseCues,
	captionAt,
	MAX_PHRASE_CHARS,
	MIN_PHRASE_CHARS,
	PAUSE_WEIGHT,
} from './onboarding-subtitles.js';

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

test( 'constants', () => {
	assert.equal( MAX_PHRASE_CHARS, 48 );
	assert.equal( MIN_PHRASE_CHARS, 12 );
	assert.deepEqual( PAUSE_WEIGHT, { break: 6, clause: 2.5, none: 0 } );
} );

test( 'displayWidth counts CJK and full-width characters as two', () => {
	assert.equal( displayWidth( '' ), 0 );
	assert.equal( displayWidth( 'abc' ), 3 );
	assert.equal( displayWidth( '你好' ), 4 );
	assert.equal( displayWidth( 'a你' ), 3 );
	assert.equal( displayWidth( 'こカ한' ), 6 );
	assert.equal( displayWidth( '、' ), 2 );
	assert.equal( displayWidth( 'Ａ' ), 2 );
	assert.equal( displayWidth( 'é' ), 1 );
} );

test( 'splitPhrases keeps the script\'s own line breaks, however short the lines', () => {
	assert.deepEqual( splitPhrases( 'For over 500 years,\nBaccarat has been known as the\nGame of Kings.' ), [
		'For over 500 years,',
		'Baccarat has been known as the',
		'Game of Kings.',
	] );
	assert.deepEqual( splitPhrases( 'Hi.\r\nGo.' ), [ 'Hi.', 'Go.' ] );
} );

test( 'splitPhrases skips blank lines and collapses spaces inside a line', () => {
	assert.deepEqual( splitPhrases( '\n  First   line here.\n\n \nSecond line here.\n' ), [ 'First line here.', 'Second line here.' ] );
} );

test( 'splitPhrases breaks after a "…" or "..." pause, keeping a closing quote with it', () => {
	assert.deepEqual( splitPhrases( 'Well… let me think about that.' ), [ 'Well…', 'let me think about that.' ] );
	assert.deepEqual( splitPhrases( 'Well... let me think about that.' ), [ 'Well...', 'let me think about that.' ] );
	assert.deepEqual( splitPhrases( '"Wait…" she said quietly to me' ), [ '"Wait…"', 'she said quietly to me' ] );
	assert.deepEqual( splitPhrases( 'And then…' ), [ 'And then…' ] );
} );

test( 'splitPhrases breaks at sentence ends, with or without Intl.Segmenter', () => {
	const expected = [ 'Hello there.', 'I am Sami! Ready?' ];
	assert.deepEqual( splitPhrases( 'Hello there. I am Sami! Ready?' ), expected );
	assert.deepEqual( splitPhrases( 'Hello there. I am Sami! Ready?', { Segmenter: null } ), expected );
} );

test( 'splitPhrases never leaves a very short sentence alone: it joins the next, or the last joins the one before', () => {
	assert.deepEqual( splitPhrases( 'Hi. I am Sami, your coach.' ), [ 'Hi. I am Sami, your coach.' ] );
	assert.deepEqual( splitPhrases( 'Let us start right now. Go!' ), [ 'Let us start right now. Go!' ] );
	assert.deepEqual( splitPhrases( 'Hi. Go.' ), [ 'Hi. Go.' ] );
} );

test( 'splitPhrases splits a long sentence at its clauses, joining short clauses up to the limit', () => {
	assert.deepEqual( splitPhrases( 'Baccarat remains the preferred game, and stands as the game of choice in Asia.' ), [
		'Baccarat remains the preferred game,',
		'and stands as the game of choice in Asia.',
	] );
	assert.deepEqual( splitPhrases( 'One, two, three, four, five, six, seven, eight, nine, ten, eleven, twelve.' ), [
		'One, two, three, four, five, six, seven, eight,',
		'nine, ten, eleven, twelve.',
	] );
} );

test( 'splitPhrases splits a long phrase with no clause at the space nearest its middle', () => {
	assert.deepEqual( splitPhrases( 'Propelled into the Modern Era over 40 years ago by James Bond.' ), [
		'Propelled into the Modern Era',
		'over 40 years ago by James Bond.',
	] );
} );

test( 'a short last clause joins the one before, and the result is split in the middle if too long', () => {
	assert.deepEqual( splitPhrases( 'Baccarat remains the preferred game of high rollers worldwide, and so on.' ), [
		'Baccarat remains the preferred game',
		'of high rollers worldwide, and so on.',
	] );
} );

test( 'splitPhrases makes no split that would leave a side shorter than the minimum', () => {
	const text = 'Supercalifragilisticexpialidocious-and-more-and-more-and-more-and-more x';
	assert.deepEqual( splitPhrases( text ), [ text ] );
} );

test( 'splitPhrases splits Chinese at its clause marks with no spaces added', () => {
	assert.deepEqual( splitPhrases( '百家乐在五百多年来一直被称为王者游戏，詹姆斯·邦德让它进入了现代，如今它仍然是全球豪客的首选游戏。' ), [
		'百家乐在五百多年来一直被称为王者游戏，',
		'詹姆斯·邦德让它进入了现代，',
		'如今它仍然是全球豪客的首选游戏。',
	] );
	assert.deepEqual( splitPhrases( '你好。我是你的教练Sami。' ), [ '你好。我是你的教练Sami。' ] );
} );

test( 'a long CJK phrase with no punctuation or space stays whole and wraps', () => {
	const text = '一二三四五六七八九十一二三四五六七八九十一二三四五六七八九十';
	assert.deepEqual( splitPhrases( text ), [ text ] );
} );

test( 'splitPhrases of nothing is nothing', () => {
	assert.deepEqual( splitPhrases( '' ), [] );
	assert.deepEqual( splitPhrases( ' \n\n ' ), [] );
	assert.deepEqual( splitPhrases( null ), [] );
	assert.deepEqual( splitPhrases( undefined ), [] );
} );

test( 'buildPhraseCues spreads phrases by length plus the pause after a break, back to back', () => {
	// 'Aa.' weighs 3 letters plus a break pause of 6; the last phrase has no pause after it:
	// 9 and 7 of 16.
	assert.deepEqual( buildPhraseCues( 'Aa.\nBbbbbb.', 1600 ), [
		{ text: 'Aa.', startMs: 0, endMs: 900 },
		{ text: 'Bbbbbb.', startMs: 900, endMs: 1600 },
	] );
} );

test( 'buildPhraseCues gives a clause a shorter pause, and a middle split none', () => {
	// 32 letters + clause pause 2.5, then 33 letters: 34.5 and 33 of 67.5.
	assert.deepEqual( buildPhraseCues( 'Baccarat remains the preferred game, and stands as the game of choice in Asia.', 1350 ), [
		{ text: 'Baccarat remains the preferred game,', startMs: 0, endMs: 690 },
		{ text: 'and stands as the game of choice in Asia.', startMs: 690, endMs: 1350 },
	] );
	// 25 letters + no pause, 26 + break pause 6, 12 + nothing: 25, 32 and 12 of 69.
	assert.deepEqual( buildPhraseCues( 'Propelled into the Modern Era over 40 years ago by James Bond.\nYes it is true.', 690 ), [
		{ text: 'Propelled into the Modern Era', startMs: 0, endMs: 250 },
		{ text: 'over 40 years ago by James Bond.', startMs: 250, endMs: 570 },
		{ text: 'Yes it is true.', startMs: 570, endMs: 690 },
	] );
} );

test( 'buildPhraseCues lengthens a phrase to the minimum time, so the cues can run past the duration', () => {
	// 900 and 700 ms by weight: only the second is under 800.
	assert.deepEqual( buildPhraseCues( 'Aa.\nBbbbbb.', 1600, { minPhraseMs: 800 } ), [
		{ text: 'Aa.', startMs: 0, endMs: 900 },
		{ text: 'Bbbbbb.', startMs: 900, endMs: 1700 },
	] );
	assert.deepEqual( buildPhraseCues( 'Aa.\nBbbbbb.', 1600, { minPhraseMs: 700 } ), [
		{ text: 'Aa.', startMs: 0, endMs: 900 },
		{ text: 'Bbbbbb.', startMs: 900, endMs: 1600 },
	] );
	assert.deepEqual( buildPhraseCues( 'Aa.\nBbbbbb.', 1600, { minPhraseMs: 1000 } ), [
		{ text: 'Aa.', startMs: 0, endMs: 1000 },
		{ text: 'Bbbbbb.', startMs: 1000, endMs: 2000 },
	] );
} );

test( 'buildPhraseCues passes the Segmenter option on to the sentence split', () => {
	let constructed = 0;
	class OneSentence {
		constructor() {
			constructed++;
		}
		segment( source ) {
			return [ { segment: source } ];
		}
	}
	assert.deepEqual( buildPhraseCues( 'Hello there. I am Sami! Ready?', 100, { Segmenter: OneSentence } ), [
		{ text: 'Hello there. I am Sami! Ready?', startMs: 0, endMs: 100 },
	] );
	assert.equal( constructed, 1 );
} );

test( 'buildPhraseCues of an empty script or a clip with no length is empty', () => {
	assert.deepEqual( buildPhraseCues( '', 1000 ), [] );
	assert.deepEqual( buildPhraseCues( 'Hi.', 0 ), [] );
	assert.deepEqual( buildPhraseCues( 'Hi.', -5 ), [] );
	assert.deepEqual( buildPhraseCues( 'Hi.', Number.NaN ), [] );
	assert.deepEqual( buildPhraseCues( 'Hi.', 'soon' ), [] );
	assert.deepEqual( buildPhraseCues( '', 1000, { minPhraseMs: 1000 } ), [] );
} );

test( 'buildPhraseCues accepts a numeric string duration', () => {
	assert.deepEqual( buildPhraseCues( 'Hi.', '500' ), [ { text: 'Hi.', startMs: 0, endMs: 500 } ] );
} );

test( 'captionAt shows the phrase being said with the one before it, and nothing outside the line', () => {
	const cues = buildPhraseCues( 'Aa.\nBbbbbb.\nCc.', 1900 );
	// Weights 3 + 6, 7 + 6 and 3 (the last phrase has no pause) of 25: 684, 988 and 228 ms.
	assert.deepEqual( captionAt( cues, -1 ), { current: '', previous: '' } );
	assert.deepEqual( captionAt( cues, 0 ), { current: 'Aa.', previous: '' } );
	assert.deepEqual( captionAt( cues, 683 ), { current: 'Aa.', previous: '' } );
	assert.deepEqual( captionAt( cues, 684 ), { current: 'Bbbbbb.', previous: 'Aa.' } );
	assert.deepEqual( captionAt( cues, 1671 ), { current: 'Bbbbbb.', previous: 'Aa.' } );
	assert.deepEqual( captionAt( cues, 1672 ), { current: 'Cc.', previous: 'Bbbbbb.' } );
	assert.deepEqual( captionAt( cues, 1899 ), { current: 'Cc.', previous: 'Bbbbbb.' } );
	assert.deepEqual( captionAt( cues, 1900 ), { current: '', previous: '' } );
	assert.deepEqual( captionAt( cues, 5000 ), { current: '', previous: '' } );
} );

test( 'captionAt with no cues is empty', () => {
	assert.deepEqual( captionAt( [], 10 ), { current: '', previous: '' } );
} );

/**
 * Phrase-by-phrase subtitles for the portal onboarding's commentary box (ENGR-7565, ENGR-7891).
 *
 * Marcus's commentary delivery instructions (PO-3201): show Sami's line in short natural
 * groups of words as she says them, never a word at a time; give longer phrases more time and
 * hold a phrase through her pause at a break; let the viewer catch up by keeping the previous
 * phrase on screen in a softer style.
 *
 * A phrase ends where the script's author put a break (a new line or a "…" pause) and at the
 * end of a sentence. A piece still too long to read at a glance is split at its clauses, then
 * at the space nearest its middle. Gary's clips carry no timestamps, so each phrase gets a
 * share of the clip proportional to its length plus Sami's pause after it, the same
 * approximation commentary.js makes for the landing flow.
 *
 * No DOM and no imports, so it can be tested without a browser.
 */

/** A phrase wider than this (in narrow characters) is split further: about two lines in the box. */
export const MAX_PHRASE_CHARS = 48;

/**
 * A split is not made when it would leave a side narrower than this, so a phrase is never a
 * lone word. The script's own breaks are always kept: the author chose them.
 */
export const MIN_PHRASE_CHARS = 12;

/**
 * Sami's pause after a phrase, in the unit of "one letter of speech". The values are
 * commentary.js's sentence and clause pauses.
 */
export const PAUSE_WEIGHT = {
	break: 6,
	clause: 2.5,
	none: 0,
};

const LINE_BREAK = /\r?\n/u;
const SPACE_RUN = /\s+/gu;
// A "…" (or "...") pause inside a line, with any closing quote and the spacing after it.
const ELLIPSIS_PAUSE = /(?:…|\.\.\.)["')\]”’」』]*\s*/gu;
// Ends a clause: Latin punctuation needs a space after it, CJK punctuation does not.
const CLAUSE_END = /[,;:—–]["')\]”’」』]*\s+|[，、；：]\s*/gu;
// Characters about twice as wide as a Latin letter on screen.
const WIDE_CHARACTER = /[\p{Script=Han}\p{Script=Hiragana}\p{Script=Katakana}\p{Script=Hangul}　-〿＀-￯]/u;
// Ends a sentence in any of the seven languages, with closing quotes or brackets after it.
const SENTENCE_END = /[.!?…。！？]["')\]”’」』]*\s*$/u;

/**
 * The sentences of a script, trimmed, in order. Uses Intl.Segmenter where it exists
 * (it knows sentence ends in Chinese, Japanese and Thai), else splits after an
 * end-of-sentence mark.
 *
 * @param {string}        text
 * @param {Object}        [options]
 * @param {Function|null} [options.Segmenter] Intl.Segmenter, or null to force the fallback.
 * @return {string[]} The sentences.
 */
export function splitSentences( text, { Segmenter = defaultSegmenter() } = {} ) {
	const source = String( text ?? '' ).replace( SPACE_RUN, ' ' ).trim();
	if ( '' === source ) {
		return [];
	}
	const pieces = 'function' === typeof Segmenter
		? Array.from( new Segmenter( undefined, { granularity: 'sentence' } ).segment( source ), ( part ) => part.segment )
		: splitAfterSentenceEnds( source );
	return pieces.map( ( piece ) => piece.trim() ).filter( ( piece ) => '' !== piece );
}

function defaultSegmenter() {
	return 'undefined' !== typeof Intl && 'function' === typeof Intl.Segmenter ? Intl.Segmenter : null;
}

function splitAfterSentenceEnds( source ) {
	const sentences = [];
	let current = '';
	for ( const character of Array.from( source ) ) {
		current += character;
		if ( ' ' === character && SENTENCE_END.test( current ) ) {
			sentences.push( current );
			current = '';
		}
	}
	sentences.push( current );
	return sentences;
}

/**
 * How wide a phrase is on screen, in narrow characters: a CJK character counts as two.
 *
 * @param {string} text
 * @return {number} The width.
 */
export function displayWidth( text ) {
	return Array.from( text ).reduce( ( sum, character ) => sum + ( WIDE_CHARACTER.test( character ) ? 2 : 1 ), 0 );
}

/**
 * Splits text after every match of a global pattern, each piece trimmed.
 *
 * @param {string} text
 * @param {RegExp} pattern
 * @return {string[]} The non-empty pieces.
 */
function splitAfter( text, pattern ) {
	const pieces = [];
	let start = 0;
	for ( const match of text.matchAll( pattern ) ) {
		const end = match.index + match[ 0 ].length;
		pieces.push( text.slice( start, end ) );
		start = end;
	}
	pieces.push( text.slice( start ) );
	return pieces.map( ( piece ) => piece.trim() ).filter( ( piece ) => '' !== piece );
}

// CJK text runs on with no space between clauses or sentences.
function join( before, after ) {
	const separator = WIDE_CHARACTER.test( Array.from( before ).at( -1 ) ) ? '' : ' ';
	return before + separator + after;
}

/**
 * Joins neighbouring pieces so none is left narrower than MIN_PHRASE_CHARS on its own (a
 * narrow last piece joins the one before). With fill, pieces also join while the result
 * stays within MAX_PHRASE_CHARS.
 *
 * @param {string[]} pieces
 * @param {Object}   options
 * @param {boolean}  options.fill
 * @return {string[]} The groups.
 */
function groupPieces( pieces, { fill } ) {
	const groups = [];
	for ( const piece of pieces ) {
		const last = groups.at( -1 );
		if ( undefined === last ) {
			groups.push( piece );
			continue;
		}
		const lastTooNarrow = displayWidth( last ) < MIN_PHRASE_CHARS;
		const fits = fill && displayWidth( join( last, piece ) ) <= MAX_PHRASE_CHARS;
		if ( lastTooNarrow || fits ) {
			groups[ groups.length - 1 ] = join( last, piece );
			continue;
		}
		groups.push( piece );
	}
	if ( groups.length > 1 && displayWidth( groups.at( -1 ) ) < MIN_PHRASE_CHARS ) {
		const tail = groups.pop();
		groups[ groups.length - 1 ] = join( groups.at( -1 ), tail );
	}
	return groups;
}

/**
 * Halves a phrase at the space nearest its middle until every part fits in MAX_PHRASE_CHARS.
 * A split that would leave a part narrower than MIN_PHRASE_CHARS is not made, so a phrase
 * with no usable space (CJK with no punctuation) stays whole and wraps.
 *
 * @param {string} text
 * @return {string[]} The parts.
 */
function splitInMiddle( text ) {
	const width = displayWidth( text );
	if ( width <= MAX_PHRASE_CHARS ) {
		return [ text ];
	}
	let best = null;
	for ( const match of text.matchAll( SPACE_RUN ) ) {
		const left = text.slice( 0, match.index );
		const right = text.slice( match.index + match[ 0 ].length );
		const leftWidth = displayWidth( left );
		if ( leftWidth < MIN_PHRASE_CHARS || displayWidth( right ) < MIN_PHRASE_CHARS ) {
			continue;
		}
		const distance = Math.abs( leftWidth - width / 2 );
		if ( null === best || distance < best.distance ) {
			best = { left, right, distance };
		}
	}
	if ( null === best ) {
		return [ text ];
	}
	return [ ...splitInMiddle( best.left ), ...splitInMiddle( best.right ) ];
}

/**
 * One sentence as phrases: whole when it fits (its clauses all join back up), else at its
 * clauses, else in the middle.
 *
 * @param {string} sentence
 * @return {Array<{ text: string, pause: string }>} The pause is Sami's after each phrase.
 */
function sentencePhrases( sentence ) {
	const clauses = groupPieces( splitAfter( sentence, CLAUSE_END ), { fill: true } );
	return clauses.flatMap( ( clause, clauseIndex ) => {
		const clausePause = clauseIndex < clauses.length - 1 ? 'clause' : 'break';
		const parts = splitInMiddle( clause );
		return parts.map( ( text, partIndex ) => ( {
			text,
			pause: partIndex < parts.length - 1 ? 'none' : clausePause,
		} ) );
	} );
}

/**
 * The phrases of a script with Sami's pause after each: the script's own line breaks and "…"
 * pauses first, then sentences (a very short one joins its neighbour), then the long-phrase
 * splits.
 *
 * @param {string} text
 * @param {Object} [options] Passed to splitSentences().
 * @return {Array<{ text: string, pause: string }>} The pause after the last phrase is 'none'.
 */
function phraseParts( text, options ) {
	const phrases = [];
	for ( const line of String( text ?? '' ).split( LINE_BREAK ) ) {
		const collapsed = line.replace( SPACE_RUN, ' ' ).trim();
		for ( const piece of splitAfter( collapsed, ELLIPSIS_PAUSE ) ) {
			const sentences = groupPieces( splitSentences( piece, options ), { fill: false } );
			sentences.forEach( ( sentence ) => phrases.push( ...sentencePhrases( sentence ) ) );
		}
	}
	if ( phrases.length > 0 ) {
		phrases[ phrases.length - 1 ] = { ...phrases.at( -1 ), pause: 'none' };
	}
	return phrases;
}

/**
 * The phrases a script is shown in, in order.
 *
 * @param {string} text
 * @param {Object} [options] Passed to splitSentences().
 * @return {string[]} The phrases.
 */
export function splitPhrases( text, options ) {
	return phraseParts( text, options ).map( ( phrase ) => phrase.text );
}

function speechWeight( phrase ) {
	return Math.max( 1, Array.from( phrase.replace( SPACE_RUN, '' ) ).length );
}

/**
 * When each phrase is on screen. Each gets a share of the duration by its length plus Sami's
 * pause after it, so a phrase stays up through the pause until the next one starts.
 *
 * @param {string} text       The script.
 * @param {number} durationMs Length of the clip, or the reading-pace estimate.
 * @param {Object} [options]
 * @param {number} [options.minPhraseMs]      Least time a phrase stays, for reading pace (with
 *                                            a clip the clip's clock wins). A shorter phrase is
 *                                            lengthened, so the cues can end after durationMs.
 * @param {Function|null} [options.Segmenter] Passed to splitSentences().
 * @return {Array<Object>} One { text, startMs, endMs } per phrase, back to back.
 */
export function buildPhraseCues( text, durationMs, { minPhraseMs = 0, ...splitOptions } = {} ) {
	const phrases = phraseParts( text, splitOptions );
	const total = Number( durationMs );
	if ( 0 === phrases.length || ! ( total > 0 ) ) {
		return [];
	}
	const weights = phrases.map( ( phrase ) => speechWeight( phrase.text ) + PAUSE_WEIGHT[ phrase.pause ] );
	const totalWeight = weights.reduce( ( sum, weight ) => sum + weight, 0 );
	let startMs = 0;
	return phrases.map( ( phrase, index ) => {
		const lengthMs = Math.max( minPhraseMs, ( weights[ index ] / totalWeight ) * total );
		const cue = { text: phrase.text, startMs, endMs: startMs + lengthMs };
		startMs = cue.endMs;
		return cue;
	} );
}

/**
 * What the commentary box shows at a moment of the clip: the phrase being said and the one
 * before it, both empty before Sami starts and once she has finished.
 *
 * @param {Array<Object>} cues   From buildPhraseCues().
 * @param {number}        timeMs Time into the clip.
 * @return {{ current: string, previous: string }} The caption.
 */
export function captionAt( cues, timeMs ) {
	const index = cues.findIndex( ( cue ) => timeMs >= cue.startMs && timeMs < cue.endMs );
	if ( -1 === index ) {
		return { current: '', previous: '' };
	}
	const before = cues[ index - 1 ];
	return { current: cues[ index ].text, previous: before ? before.text : '' };
}

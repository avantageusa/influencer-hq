/**
 * The commentary box of the AI Coach flow (PO-3062, design alignment).
 *
 * The design puts what Sami says in a box under her picture: the words appear as she
 * speaks them and clear once a phrase is finished, then the next ones come. This module is
 * the timing: it splits a script into short phrases and says, for any moment of the clip,
 * which words are on screen. It does no DOM work, so it can be tested without a browser;
 * js/aicoach-coach-flow.js feeds it the clip's current time and writes the result.
 *
 * Gary's Coach API does not return word timestamps, so the time of each word is worked out
 * from the clip's length: a word takes a share of the time proportional to its size, and a
 * full stop or a comma adds a short pause. That is close enough to read along with and
 * can be replaced by real timestamps later without touching the callers.
 *
 * Scripts come in seven languages, three of which (Mandarin, Cantonese, Japanese) and Thai
 * are written without spaces, so words are found with Intl.Segmenter where it exists.
 */

/** The longest phrase on screen at once. */
export const MAX_PHRASE_WORDS = 8;

/** A comma only ends a phrase once the phrase already has this many words. */
export const MIN_CLAUSE_WORDS = 3;

/** How long a finished phrase stays before the box clears, in ms. */
export const PHRASE_HOLD_MS = 300;

/** Speaking pace used when the length of the speech is not known (live intro, a clip that did not play). */
export const WORDS_PER_SECOND = 2.5;

// Time weights, in the unit of "one letter of speech": a pause after a sentence or a clause.
const SENTENCE_PAUSE_WEIGHT = 6;
const CLAUSE_PAUSE_WEIGHT = 2.5;

const SENTENCE_END = /[.!?…。！？]["')\]”’]*$/u;
const CLAUSE_END = /[,;:，、；：—–]["')\]”’]*$/u;
const PUNCTUATION_ONLY = /^[\p{P}\p{S}]+$/u;

/**
 * The words of a script with the spacing that came before each one, so a phrase can be put
 * back together exactly as written in any language.
 *
 * @param {string} text
 * @return {Array<{ text: string, lead: string }>}
 */
export function tokenize( text ) {
	const source = String( text || '' );
	const pieces = splitWithSegmenter( source ) || splitOnSpaces( source );
	const tokens = [];
	let pendingLead = '';
	for ( const piece of pieces ) {
		if ( '' === piece.trim() ) {
			pendingLead = ' ';
			continue;
		}
		const last = tokens[ tokens.length - 1 ];
		if ( last && '' === pendingLead && PUNCTUATION_ONLY.test( piece ) ) {
			last.text += piece;
			continue;
		}
		tokens.push( { text: piece, lead: tokens.length > 0 ? pendingLead : '' } );
		pendingLead = '';
	}
	return tokens;
}

function splitWithSegmenter( source ) {
	if ( 'function' !== typeof Intl.Segmenter ) {
		return null;
	}
	const segmenter = new Intl.Segmenter( undefined, { granularity: 'word' } );
	return Array.from( segmenter.segment( source ), ( part ) => part.segment );
}

function splitOnSpaces( source ) {
	return source.split( /(\s+)/ ).filter( ( piece ) => '' !== piece );
}

/**
 * Groups tokens into the phrases shown one at a time: a phrase ends at the end of a sentence,
 * at a comma once it has a few words, or when it reaches MAX_PHRASE_WORDS.
 *
 * @param {Array<{ text: string, lead: string }>} tokens
 * @return {Array<Array<{ text: string, lead: string }>>}
 */
export function groupIntoPhrases( tokens ) {
	const phrases = [];
	let current = [];
	for ( const token of tokens ) {
		current.push( token );
		if ( endsPhrase( token.text, current.length ) ) {
			phrases.push( current );
			current = [];
		}
	}
	if ( current.length > 0 ) {
		phrases.push( current );
	}
	return phrases;
}

function endsPhrase( word, wordCount ) {
	if ( SENTENCE_END.test( word ) ) {
		return true;
	}
	if ( CLAUSE_END.test( word ) && wordCount >= MIN_CLAUSE_WORDS ) {
		return true;
	}
	return wordCount >= MAX_PHRASE_WORDS;
}

/**
 * @param {string} text
 * @return {string[]} The phrases as text.
 */
export function splitIntoPhrases( text ) {
	return groupIntoPhrases( tokenize( text ) ).map( joinTokens );
}

function joinTokens( tokens ) {
	return tokens.map( ( token, index ) => ( 0 === index ? '' : token.lead ) + token.text ).join( '' );
}

/**
 * How long a script takes to say at the default pace, for a screen with no clip to measure.
 *
 * @param {string} text
 * @param {number} [wordsPerSecond]
 * @return {number} Milliseconds.
 */
export function estimateDurationMs( text, wordsPerSecond = WORDS_PER_SECOND ) {
	const words = tokenize( text ).filter( ( token ) => ! PUNCTUATION_ONLY.test( token.text ) ).length;
	return Math.round( ( words / wordsPerSecond ) * 1000 );
}

function weightOf( token ) {
	const letters = Array.from( token.text.replace( /[\p{P}\p{S}]+$/u, '' ) ).length;
	return Math.max( 1, letters );
}

function pauseAfter( token ) {
	if ( SENTENCE_END.test( token.text ) ) {
		return SENTENCE_PAUSE_WEIGHT;
	}
	if ( CLAUSE_END.test( token.text ) ) {
		return CLAUSE_PAUSE_WEIGHT;
	}
	return 0;
}

/**
 * The time each word is said, spread over the clip.
 *
 * @param {string} text         The script.
 * @param {number} durationMs   The length of the clip, or an estimate.
 * @param {Object} [options]
 * @param {number} [options.leadInMs] Silence before Sami starts, so the first word is not early.
 * @return {Array<{ text: string, startMs: number, endMs: number, words: Array<{ text: string, lead: string, startMs: number }> }>}
 */
export function buildCues( text, durationMs, { leadInMs = 0 } = {} ) {
	const phrases = groupIntoPhrases( tokenize( text ) );
	const speechMs = Number( durationMs ) - leadInMs;
	if ( 0 === phrases.length || ! ( speechMs > 0 ) ) {
		return [];
	}

	const totalWeight = phrases.flat().reduce( ( sum, token ) => sum + weightOf( token ) + pauseAfter( token ), 0 );
	const msPerWeight = speechMs / totalWeight;

	let elapsedWeight = 0;
	return phrases.map( ( phrase ) => {
		const words = phrase.map( ( token ) => {
			const startMs = leadInMs + elapsedWeight * msPerWeight;
			elapsedWeight += weightOf( token ) + pauseAfter( token );
			return { text: token.text, lead: token.lead, startMs };
		} );
		return {
			text: joinTokens( phrase ),
			startMs: words[ 0 ].startMs,
			endMs: leadInMs + elapsedWeight * msPerWeight,
			words,
		};
	} );
}

/**
 * What the box shows at a moment of the clip: the words of the current phrase said so far,
 * nothing before the first phrase, and nothing once a phrase has been finished for a moment
 * and the next one has not begun.
 *
 * @param {ReturnType<typeof buildCues>} cues
 * @param {number} timeMs
 * @return {string}
 */
export function visibleTextAt( cues, timeMs ) {
	const cue = currentCue( cues, timeMs );
	if ( ! cue ) {
		return '';
	}
	const said = cue.words.filter( ( word ) => word.startMs <= timeMs );
	return said.map( ( word, index ) => ( 0 === index ? '' : word.lead ) + word.text ).join( '' );
}

function currentCue( cues, timeMs ) {
	for ( let index = cues.length - 1; index >= 0; index-- ) {
		const cue = cues[ index ];
		if ( timeMs < cue.startMs ) {
			continue;
		}
		return timeMs < cue.endMs + PHRASE_HOLD_MS ? cue : null;
	}
	return null;
}

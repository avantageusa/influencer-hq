/**
 * Sentence-level subtitles for the portal onboarding (ENGR-7565, PO-3063 T3).
 *
 * The landing flow shows Sami's words a short phrase at a time (commentary.js). The
 * onboarding shows a whole sentence while it is spoken (evaluation OQ13): each sentence
 * appears when Sami starts it, stays until the next one starts, and the box is empty once
 * the clip has ended. Gary's clips carry no timestamps, so each sentence gets a share of
 * the clip proportional to its length, the same approximation commentary.js makes.
 *
 * No DOM and no imports, so it can be tested without a browser.
 */

// Ends a sentence in any of the seven languages, with closing quotes or brackets after it.
const SENTENCE_END = /[.!?…。！？]["')\]”’」』]*\s*$/u;
const SPACE_RUN = /\s+/gu;

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

function speechWeight( sentence ) {
	return Math.max( 1, Array.from( sentence.replace( SPACE_RUN, '' ) ).length );
}

/**
 * When each sentence is on screen, spread over the clip.
 *
 * @param {string} text       The script.
 * @param {number} durationMs Length of the clip, or the reading-pace estimate.
 * @param {Object} [options]  Passed to splitSentences().
 * @return {Array<Object>} One { text, startMs, endMs } per sentence, back to back.
 */
export function buildSentenceCues( text, durationMs, options ) {
	const sentences = splitSentences( text, options );
	const total = Number( durationMs );
	if ( 0 === sentences.length || ! ( total > 0 ) ) {
		return [];
	}
	const totalWeight = sentences.reduce( ( sum, sentence ) => sum + speechWeight( sentence ), 0 );
	let elapsed = 0;
	return sentences.map( ( sentence ) => {
		const startMs = ( elapsed / totalWeight ) * total;
		elapsed += speechWeight( sentence );
		return { text: sentence, startMs, endMs: ( elapsed / totalWeight ) * total };
	} );
}

/**
 * The sentence on screen at a moment of the clip: none before it starts, the one being
 * spoken while it plays, and none once it has ended.
 *
 * @param {Array<Object>} cues   From buildSentenceCues().
 * @param {number}        timeMs Time into the clip.
 * @return {string} The sentence, or '' when none is being spoken.
 */
export function subtitleAt( cues, timeMs ) {
	for ( const cue of cues ) {
		if ( timeMs >= cue.startMs && timeMs < cue.endMs ) {
			return cue.text;
		}
	}
	return '';
}

import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createFakeDocument } from './fake-dom.js';
import { createEmitter } from './onboarding-events.js';
import { createStringTable } from './onboarding-strings.js';
import { createApi } from './onboarding-boot.js';
import { SEQUENCE, startStage, nextStage } from './onboarding-sequence.js';
import { createEngine, ENGINE_STRINGS_EN } from './onboarding-engine.js';
import {
	TEACHING_STRINGS_EN,
	EQUITY_CHARTS,
	EXAMPLE_STEP_KEYS,
	lineChartSvg,
	tickFraction,
	chartImageSrc,
	chartAltText,
	CHART_ALT_KEYS,
	CHART_ALT_FORMATS,
	teachingScreens,
	install,
} from './onboarding-screens-teaching.js';

const STATE = { stage: '', answers: {}, platforms: [], handles: {}, language: '', completed: false, completed_at: '' };

function relabel( root, t ) {
	root.querySelectorAll( '[data-onb-string]' ).forEach( ( element ) => {
		element.textContent = t( element.getAttribute( 'data-onb-string' ) );
	} );
}

const englishOf = ( key ) => TEACHING_STRINGS_EN[ key ];

// The engine with this module installed, on the fake DOM.
function setup( { stage = 'game_of_kings', images = {}, answers = {} } = {} ) {
	const document = createFakeDocument();
	const content = document.createElement( 'div' );
	document.body.append( content );
	const events = createEmitter();
	const strings = createStringTable();
	strings.add( 'en', ENGINE_STRINGS_EN );
	const api = createApi( { config: { state: { ...STATE, stage, answers }, images, progressUrl: '/p', completeUrl: '/c', nonce: 'n' }, events, strings } );
	api.shell = { content, say: () => Promise.resolve(), close: () => {} };
	// What onboarding-main wires up: a language change re-labels every data-onb-string element.
	events.on( 'locale', () => relabel( content, ( key ) => strings.t( api.getLocale(), key ) ) );
	const engine = createEngine( {
		api,
		sequence: { SEQUENCE, startStage, nextStage },
		document,
		postJson: async () => ( { ok: true, status: 200, data: null } ),
		fetchFn: () => {},
		historyGuard: { install: () => {}, uninstall: () => {} },
	} );
	api.screens = { register: engine.register };
	install( api );
	engine.start();
	return { api, engine, content, strings, events };
}

const screenOf = ( content ) => content.querySelector( 'section' );
const texts = ( element, selector ) => element.querySelectorAll( selector ).map( ( node ) => node.textContent );

test( 'registers screens 1, 2, 7-11 and 13, and only those', () => {
	assert.deepEqual( teachingScreens().map( ( definition ) => definition.id ), [ 'game_of_kings', 'modern_era', 'world_championship', 'world_tour', 'classic', 'equity', 'how_this_works', 'equity_example' ] );
	const registered = [];
	const strings = createStringTable();
	install( { strings, screens: { register: ( definition ) => registered.push( definition.id ) } } );
	assert.equal( registered.length, 8 );
	assert.equal( strings.t( 'en', 'kingsTitle' ), 'The Game of Kings' );
} );

test( 'teaching screens have no narration yet (scripts wait on OQ1) and keep CONTINUE', () => {
	teachingScreens().forEach( ( definition ) => {
		assert.equal( definition.narration, undefined, definition.id );
		assert.equal( definition.hideContinue, undefined, definition.id );
	} );
} );

test( 'screen 1, The Game of Kings: PO-3203 copy, character for character', () => {
	const { content } = setup();
	assert.deepEqual( texts( screenOf( content ), 'h2' ), [ 'The Game of Kings' ] );
	assert.deepEqual( texts( screenOf( content ), 'p' ), [ 'For over 500 years, Baccarat has been known as the Game of Kings.' ] );
} );

test( 'screen 2, The Modern Era: PO-3203 copy', () => {
	const { content } = setup( { stage: 'modern_era' } );
	assert.deepEqual( texts( screenOf( content ), 'h2' ), [ 'The Modern Era' ] );
	assert.deepEqual( texts( screenOf( content ), 'p' ), [
		'Propelled into the modern era over 40 years ago by James Bond',
		"Baccarat remains the preferred game of high rollers worldwide, and stands as Asia's game of choice with over 100 million active players.",
	] );
} );

test( 'screen 7, World Championship: PO-3208 copy', () => {
	const { content } = setup( { stage: 'world_championship' } );
	assert.deepEqual( texts( screenOf( content ), 'h2' ), [ 'World Championship' ] );
	assert.deepEqual( texts( screenOf( content ), 'p' ), [
		'$100,000,000 Guaranteed Payout',
		'Contests and Individual Plays from $1 up',
		'You and all of your followers will receive $100 to get started.',
		"This isn't play money, this is FREE money",
	] );
} );

test( 'screen 8, World Tour: PO-3208 copy', () => {
	const { content } = setup( { stage: 'world_tour' } );
	assert.deepEqual( texts( screenOf( content ), 'h2' ), [ 'World Tour' ] );
	assert.deepEqual( texts( screenOf( content ), 'p' ), [
		'Weekend 3-level tournaments which alternate between 13 International cities throughout the year.',
		'Runs each week from 5 PM Thursday through 10 PM Sunday.',
		'Enter on the $10 or $100 level and win your way into the $1,000 finals.',
		'Example: 1100 Finalists will result in $1,000,000 being distributed to the top 30% of Finalists.',
	] );
} );

test( 'screen 9, Classic: PO-3208 copy', () => {
	const { content } = setup( { stage: 'classic' } );
	assert.deepEqual( texts( screenOf( content ), 'h2' ), [ 'Classic' ] );
	assert.deepEqual( texts( screenOf( content ), 'p' ), [
		'Top 30% Split the Pool at the end of each 1 Hand contest.',
		'Four Entry Fees - $1, $10, $100, $1,000',
		'Example: 110 Finalists in the $10 contest would result in $1,000 being distributed to the top 30% of Finalists.',
	] );
} );

const SHARES_ALT = 'Earned Equity Shares: 0 on SUN, 70 on MON, 70 on TUE, 130 on WED, 250 on THU, 275 on FRI, 400 on SAT';
const VALUE_ALT = 'Value of Shares: $0.20 at START, $0.90 at 6 MOS, $1.20 at 12 MOS, $2.20 at 18 MOS, $4.00 at 24 MOS';

test( 'screen 10, Equity: the title and both charts, in order, each described by its data in the alt text', () => {
	const { content } = setup( { stage: 'equity' } );
	const screen = screenOf( content );
	assert.deepEqual( texts( screen, 'h2' ), [ 'Equity' ] );
	const charts = screen.querySelectorAll( 'img[data-chart]' );
	assert.deepEqual( charts.map( ( chart ) => chart.getAttribute( 'data-chart' ) ), [ 'shares', 'value' ] );
	assert.deepEqual( charts.map( ( chart ) => chart.getAttribute( 'alt' ) ), [ SHARES_ALT, VALUE_ALT ] );
	assert.deepEqual( charts.map( ( chart ) => chart.getAttribute( 'src' ) ), EQUITY_CHARTS.map( ( chart ) => chartImageSrc( chart, englishOf ) ) );
} );

test( 'the charts have the PO-3209 axes', () => {
	const [ shares, value ] = EQUITY_CHARTS;
	assert.deepEqual( shares.xKeys.map( englishOf ), [ 'SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT' ] );
	assert.deepEqual( shares.yTicks, [ 0, 100, 200, 300, 400 ] );
	assert.deepEqual( shares.yKeys.map( englishOf ), [ '0', '100', '200', '300', '400' ] );
	assert.deepEqual( value.xKeys.map( englishOf ), [ 'START', '6 MOS', '12 MOS', '18 MOS', '24 MOS' ] );
	assert.deepEqual( value.yTicks, [ 0.2, 1, 2, 3, 4 ] );
	assert.deepEqual( value.yKeys.map( englishOf ), [ '$.20', '$1', '$2', '$3', '$4' ] );
	EQUITY_CHARTS.forEach( ( chart ) => {
		assert.equal( chart.values.length, chart.xKeys.length, chart.id );
		const outOfRange = chart.values.filter( ( point ) => point < chart.yTicks[ 0 ] || point > chart.yTicks.at( -1 ) );
		assert.deepEqual( outOfRange, [], chart.id );
	} );
} );

// The design's chart box, Figma 46893:117629 "10 Equity 3": 198.5 x 149.8, the plot from x 19.5
// to 177 and from y 126.4 (first tick) to 28.9 (last tick), gridlines dashed, axes and line white.
const SVG_OPEN = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 198.5 149.8" font-family="Inter, sans-serif">' +
	'<rect width="198.5" height="149.8" rx="3.8" fill="#2a2b37"/>';
const svgTitle = ( text ) => `<text x="99.25" y="14" text-anchor="middle" font-size="10" font-weight="700" fill="#ffffff">${ text }</text>` +
	'<rect x="6" y="20.3" width="186.6" height="119.7" fill="#18181b" stroke="#5f5f5f" stroke-width="0.5"/>';
const svgGridRow = ( y ) => `<line x1="19.5" y1="${ y }" x2="191" y2="${ y }" stroke="#4b4b4b" stroke-width="0.5" stroke-dasharray="1 1"/>`;
const svgGridColumn = ( x ) => `<line x1="${ x }" y1="21" x2="${ x }" y2="126.4" stroke="#4b4b4b" stroke-width="0.5" stroke-dasharray="1 1"/>`;
const SVG_AXES = '<path d="M19.5 21V126.4H191" fill="none" stroke="#ffffff" stroke-width="0.5"/>';
const svgYLabel = ( y, label ) => `<text x="17.3" y="${ y }" text-anchor="end" font-size="5" fill="#ffffff">${ label }</text>`;
const svgXLabel = ( x, label ) => `<text x="${ x }" y="134.6" text-anchor="middle" font-size="5" fill="#ffffff">${ label }</text>`;
const svgLine = ( points ) => `<polyline points="${ points }" fill="none" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>`;

test( 'lineChartSvg draws the design\'s box: title, dashed grid, axes, every axis label and one line through every value', () => {
	const svg = lineChartSvg( { title: 'T & <x>', xLabels: [ 'A', 'B', 'C' ], yTicks: [ 0, 10 ], yLabels: [ '0', '10' ], values: [ 0, 3.3, 10 ] } );
	// x from 19.5 to 177, so B is at 98.25, rounded to 98.3; 3.3 is 0.33 of the 97.5 high plot
	// above 126.4: 94.225, rounded to 94.2.
	assert.equal( svg, SVG_OPEN +
		svgTitle( 'T &#38; &#60;x&#62;' ) +
		svgGridRow( 28.9 ) +
		svgGridColumn( 98.3 ) +
		svgGridColumn( 177 ) +
		SVG_AXES +
		svgYLabel( 126.4, '0' ) +
		svgYLabel( 28.9, '10' ) +
		svgXLabel( 19.5, 'A' ) +
		svgXLabel( 98.3, 'B' ) +
		svgXLabel( 177, 'C' ) +
		svgLine( '19.5,126.4 98.3,94.2 177,28.9' ) +
		'</svg>' );
} );

test( 'tickFraction spaces the ticks evenly and places a value between the two around it', () => {
	// Uneven like the value chart's ($.20, $1, $2 ...), with values that are exact in binary.
	const ticks = [ 0.5, 1, 2, 3, 4 ];
	assert.equal( tickFraction( 0.5, ticks ), 0 );
	assert.equal( tickFraction( 1, ticks ), 0.25 );
	assert.equal( tickFraction( 0.75, ticks ), 0.125 );
	assert.equal( tickFraction( 2.5, ticks ), 0.625 );
	assert.equal( tickFraction( 4, ticks ), 1 );
	// Outside the range it carries on the nearest segment's scale.
	assert.equal( tickFraction( 5, ticks ), 1.25 );
	assert.equal( tickFraction( 0, ticks ), -0.25 );
	assert.equal( tickFraction( 5, [ 0, 10 ] ), 0.5 );
} );

test( 'the charts plot the design\'s sample values (Figma 46893:117629, "10 Equity 3")', () => {
	const [ shares, value ] = EQUITY_CHARTS;
	assert.deepEqual( shares.values, [ 0, 70, 70, 130, 250, 275, 400 ] );
	assert.deepEqual( value.values, [ 0.2, 0.9, 1.2, 2.2, 4 ] );
} );

// Ticks evenly spaced on both charts: 126.4, 102.025, 77.65, 53.275, 28.9.
const SVG_GRID_ROWS = svgGridRow( 102 ) + svgGridRow( 77.7 ) + svgGridRow( 53.3 ) + svgGridRow( 28.9 );
const svgYLabels = ( labels ) => [ 126.4, 102, 77.7, 53.3, 28.9 ].map( ( y, index ) => svgYLabel( y, labels[ index ] ) ).join( '' );
const SHARES_SVG = SVG_OPEN +
	svgTitle( 'Earned Equity Shares' ) +
	SVG_GRID_ROWS +
	[ 45.8, 72, 98.3, 124.5, 150.8, 177 ].map( svgGridColumn ).join( '' ) +
	SVG_AXES +
	svgYLabels( [ '0', '100', '200', '300', '400' ] ) +
	svgXLabel( 19.5, 'SUN' ) +
	svgXLabel( 45.8, 'MON' ) +
	svgXLabel( 72, 'TUE' ) +
	svgXLabel( 98.3, 'WED' ) +
	svgXLabel( 124.5, 'THU' ) +
	svgXLabel( 150.8, 'FRI' ) +
	svgXLabel( 177, 'SAT' ) +
	svgLine( '19.5,126.4 45.8,109.3 72,109.3 98.3,94.7 124.5,65.5 150.8,59.4 177,28.9' ) +
	'</svg>';
// $.20 to $1 takes as much height as $1 to $2: 0.9 is 7/8 of the first quarter (105.1), 1.2
// a fifth into the second (97.2), 2.2 a fifth into the third (72.8).
const VALUE_SVG = SVG_OPEN +
	svgTitle( 'Value of Shares' ) +
	SVG_GRID_ROWS +
	[ 58.9, 98.3, 137.6, 177 ].map( svgGridColumn ).join( '' ) +
	SVG_AXES +
	svgYLabels( [ '$.20', '$1', '$2', '$3', '$4' ] ) +
	svgXLabel( 19.5, 'START' ) +
	svgXLabel( 58.9, '6 MOS' ) +
	svgXLabel( 98.3, '12 MOS' ) +
	svgXLabel( 137.6, '18 MOS' ) +
	svgXLabel( 177, '24 MOS' ) +
	svgLine( '19.5,126.4 58.9,105.1 98.3,97.2 137.6,72.8 177,28.9' ) +
	'</svg>';
const dataUrl = ( svg ) => 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent( svg );

test( 'screen 10 draws the design\'s two lines', () => {
	const [ shares, value ] = EQUITY_CHARTS;
	assert.equal( chartImageSrc( shares, englishOf ), dataUrl( SHARES_SVG ) );
	assert.equal( chartImageSrc( value, englishOf ), dataUrl( VALUE_SVG ) );
	const { content } = setup( { stage: 'equity' } );
	const charts = screenOf( content ).querySelectorAll( 'img[data-chart]' );
	assert.deepEqual( charts.map( ( chart ) => chart.getAttribute( 'src' ) ), [ dataUrl( SHARES_SVG ), dataUrl( VALUE_SVG ) ] );
} );

test( 'lineChartSvg with one point puts it on the left axis', () => {
	const svg = lineChartSvg( { title: '', xLabels: [ 'A' ], yTicks: [ 0, 1 ], yLabels: [ '0', '1' ], values: [ 1 ] } );
	assert.equal( svg, SVG_OPEN + svgTitle( '' ) + svgGridRow( 28.9 ) + SVG_AXES + svgYLabel( 126.4, '0' ) + svgYLabel( 28.9, '1' ) + svgXLabel( 19.5, 'A' ) + svgLine( '19.5,28.9' ) + '</svg>' );
} );

test( 'chart text follows the language, English where a translation is missing', () => {
	const t = ( key ) => ( { daySun: '日', sharesChartTitle: '株', sharesAxis400: '四百' }[ key ] || TEACHING_STRINGS_EN[ key ] );
	const [ shares ] = EQUITY_CHARTS;
	const expectedSvg = lineChartSvg( {
		title: '株',
		xLabels: [ '日', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT' ],
		yTicks: shares.yTicks,
		yLabels: [ '0', '100', '200', '300', '四百' ],
		values: shares.values,
	} );
	assert.equal( chartImageSrc( shares, t ), 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent( expectedSvg ) );
} );

test( 'a language change redraws the charts; leaving the screen stops listening', () => {
	const { content, api, strings, engine, events } = setup( { stage: 'equity' } );
	strings.add( 'ja', { sharesChartTitle: '獲得株式' } );
	api.setLocale( 'ja' );
	const chart = screenOf( content ).querySelector( 'img[data-chart="shares"]' );
	assert.equal( chart.getAttribute( 'alt' ), '獲得株式: 0 on SUN, 70 on MON, 70 on TUE, 130 on WED, 250 on THU, 275 on FRI, 400 on SAT' );
	assert.equal( chart.getAttribute( 'src' ), chartImageSrc( EQUITY_CHARTS[ 0 ], ( key ) => strings.t( 'ja', key ) ) );
	engine.advance();
	const before = chart.getAttribute( 'src' );
	api.setLocale( 'en' );
	assert.equal( chart.getAttribute( 'src' ), before );
	assert.doesNotThrow( () => events.emit( 'locale', 'en' ) );
} );

test( 'chartAltText: the title, then each value with its x label, money with cents', () => {
	const [ shares, value ] = EQUITY_CHARTS;
	assert.equal( chartAltText( shares, englishOf ), SHARES_ALT );
	assert.equal( chartAltText( value, englishOf ), VALUE_ALT );
} );

test( 'chartAltText: every word comes from the string table, English where a translation is missing', () => {
	const japanese = {
		valueChartTitle: '株式の価値',
		periodStart: '開始',
		chartAltSummary: '{title}（{points}）',
		chartAltSeparator: '、',
		chartAltPointAt: '{label}に{value}',
	};
	const t = ( key ) => japanese[ key ] || TEACHING_STRINGS_EN[ key ];
	assert.equal( chartAltText( EQUITY_CHARTS[ 1 ], t ), '株式の価値（開始に$0.20、6 MOSに$0.90、12 MOSに$1.20、18 MOSに$2.20、24 MOSに$4.00）' );
	// A language with none of the alt keys reads them in English.
	const titleOnly = ( key ) => ( { sharesChartTitle: '株' }[ key ] || TEACHING_STRINGS_EN[ key ] );
	assert.equal( chartAltText( EQUITY_CHARTS[ 0 ], titleOnly ), '株: 0 on SUN, 70 on MON, 70 on TUE, 130 on WED, 250 on THU, 275 on FRI, 400 on SAT' );
} );

test( 'chartAltText: the currency is a string-table template too', () => {
	const t = ( key ) => ( { chartAltMoney: '{amount} USD' }[ key ] || TEACHING_STRINGS_EN[ key ] );
	assert.equal( chartAltText( EQUITY_CHARTS[ 1 ], t ), 'Value of Shares: 0.20 USD at START, 0.90 USD at 6 MOS, 1.20 USD at 12 MOS, 2.20 USD at 18 MOS, 4.00 USD at 24 MOS' );
} );

test( 'chartAltText follows the values: other values give other alt text', () => {
	const [ shares, value ] = EQUITY_CHARTS;
	assert.equal( chartAltText( { ...shares, values: [ 400, 275, 250, 130, 70, 70, 0 ] }, englishOf ), 'Earned Equity Shares: 400 on SUN, 275 on MON, 250 on TUE, 130 on WED, 70 on THU, 70 on FRI, 0 on SAT' );
	assert.equal( chartAltText( { ...value, values: [ 1, 1.5, 2.25, 3, 3.999 ] }, englishOf ), 'Value of Shares: $1.00 at START, $1.50 at 6 MOS, $2.25 at 12 MOS, $3.00 at 18 MOS, $4.00 at 24 MOS' );
	assert.equal( chartAltText( { ...shares, values: [ 5 ], xKeys: [ 'dayWed' ] }, englishOf ), 'Earned Equity Shares: 5 on WED' );
} );

test( 'chartAltText: a placeholder a template does not fill is left as written; an unknown chart gets its title alone', () => {
	const t = ( key ) => ( { chartAltPointOn: '{value} on {label} {extra}' }[ key ] || TEACHING_STRINGS_EN[ key ] );
	assert.equal( chartAltText( { ...EQUITY_CHARTS[ 0 ], values: [ 1 ], xKeys: [ 'daySun' ] }, t ), 'Earned Equity Shares: 1 on SUN {extra}' );
	assert.equal( chartAltText( { ...EQUITY_CHARTS[ 0 ], id: 'other' }, englishOf ), 'Earned Equity Shares' );
} );

test( 'the alt text keys are in the English table, and each chart has an alt format', () => {
	assert.deepEqual( CHART_ALT_KEYS, { summary: 'chartAltSummary', separator: 'chartAltSeparator', money: 'chartAltMoney' } );
	assert.deepEqual( CHART_ALT_FORMATS, {
		shares: { pointKey: 'chartAltPointOn', valueFormat: 'count' },
		value: { pointKey: 'chartAltPointAt', valueFormat: 'money' },
	} );
	assert.deepEqual( [ 'chartAltSummary', 'chartAltSeparator', 'chartAltPointOn', 'chartAltPointAt', 'chartAltMoney' ].map( englishOf ), [ '{title}: {points}', ', ', '{value} on {label}', '{value} at {label}', '${amount}' ] );
	assert.deepEqual( EQUITY_CHARTS.map( ( chart ) => Object.hasOwn( CHART_ALT_FORMATS, chart.id ) ), [ true, true ] );
} );

test( 'screen 11, How This Works: the title and two bullets only (no chart or table)', () => {
	const { content } = setup( { stage: 'how_this_works' } );
	const screen = screenOf( content );
	assert.deepEqual( texts( screen, 'h2' ), [ 'HOW THIS WORKS' ] );
	assert.deepEqual( texts( screen, 'li' ), [ 'You, your followers, and everyone your followers refer', 'Other Influencers you refer, and their followers' ] );
	assert.deepEqual( screen.querySelectorAll( 'img' ), [] );
	assert.deepEqual( screen.querySelectorAll( 'table' ), [] );
	assert.deepEqual( screen.querySelectorAll( 'p' ), [] );
} );

test( 'screen 13: the PO-3211 worked example, step by step, from 5,000 Followers to the company value', () => {
	const { content } = setup( { stage: 'equity_example' } );
	const screen = screenOf( content );
	assert.deepEqual( texts( screen, 'h2' ), [ 'How Much Equity You Could Earn?' ] );
	assert.deepEqual( texts( screen, 'li' ), [
		'5,000 Followers',
		'10% Play Weekly',
		'= 500 Players',
		'2 Hours of Play/Week/Player',
		'= 1,000 Hours Play/Week',
		'20 Plays/Hour/Player',
		'20,000 Plays/Week',
		'Average Play Amount $10',
		'Total Play/Week = $200,000',
		'52 Weeks a Year = $10.4 Million in play',
		'EQUITY EARNED: $10.4 Million X 1% of play = $104,000',
		'IHQ INITIAL SHARE VALUE: .20 per share',
		'$104,000 equity @ $.20',
		'520,000 Your Shares',
		'COMPANY VALUE: $0.20 → $2.00',
	] );
	assert.equal( screen.querySelector( 'ol' ).className, 'ihq-onb-teach__steps' );
	assert.equal( EXAMPLE_STEP_KEYS.length, 15 );
	assert.deepEqual( texts( screen, 'p' ), [], 'no framing or disclaimer copy' );
} );

test( 'screen 13 shows the same figures whatever the follower answer', () => {
	const steps = [ 'under_10k', '100k_plus', undefined ].map( ( answer ) => {
		const { content } = setup( { stage: 'equity_example', answers: answer ? { active_follower_count: answer } : {} } );
		return texts( screenOf( content ), 'li' );
	} );
	assert.deepEqual( steps[ 0 ], steps[ 1 ] );
	assert.deepEqual( steps[ 1 ], steps[ 2 ] );
} );

test( 'every piece of copy on every screen is a string-table key, so it can be translated', () => {
	const altKeys = [ ...Object.values( CHART_ALT_KEYS ), ...Object.values( CHART_ALT_FORMATS ).map( ( format ) => format.pointKey ) ];
	const chartKeys = [ ...EQUITY_CHARTS.flatMap( ( chart ) => [ chart.titleKey, ...chart.xKeys, ...chart.yKeys ] ), ...altKeys ];
	const renderedKeys = teachingScreens().flatMap( ( definition ) => {
		const { content } = setup( { stage: definition.id } );
		return screenOf( content ).querySelectorAll( 'h2, p, li' ).map( ( element ) => {
			const key = element.getAttribute( 'data-onb-string' );
			assert.equal( element.textContent, TEACHING_STRINGS_EN[ key ], definition.id + ' ' + key );
			return key;
		} );
	} );
	// Every key but the chart labels (drawn into the chart images) and the charts' alt text is
	// on a screen as text.
	assert.deepEqual( renderedKeys, Object.keys( TEACHING_STRINGS_EN ).filter( ( key ) => ! chartKeys.includes( key ) ) );
} );

test( 'a language change re-labels the rendered screen copy', () => {
	const { content, api, strings } = setup( { stage: 'world_tour' } );
	strings.add( 'ko', { tourTitle: '월드 투어' } );
	api.setLocale( 'ko' );
	const screen = screenOf( content );
	assert.deepEqual( texts( screen, 'h2' ), [ '월드 투어' ] );
	// Keys without a Korean entry fall back to English.
	assert.deepEqual( texts( screen, 'p' ), [ 'tourBody1', 'tourBody2', 'tourBody3', 'tourBody4' ].map( englishOf ) );
} );

test( 'a hero image is shown when the server gives one for the screen, and none otherwise', () => {
	const withImage = setup( { stage: 'classic', images: { classic: 'https://cdn.test/classic.webp' } } );
	const hero = screenOf( withImage.content ).querySelector( 'img.ihq-onb-teach__hero' );
	assert.equal( hero.getAttribute( 'src' ), 'https://cdn.test/classic.webp' );
	assert.equal( hero.getAttribute( 'alt' ), '' );
	assert.equal( screenOf( withImage.content ).children[ 0 ], hero );
	const without = setup( { stage: 'classic' } );
	assert.equal( screenOf( without.content ).querySelector( 'img' ), null );
} );

// What inc/portal-onboarding-teaching.php sends: heroes for screens 1 and 2 only.
const SERVER_IMAGES = Object.freeze( {
	game_of_kings: 'https://example.test/wp-content/themes/influencer-hq/images/aicoach/design/onboarding-game-of-kings.webp?v=1',
	modern_era: 'https://example.test/wp-content/themes/influencer-hq/images/aicoach/design/onboarding-modern-era.webp?v=2',
} );
const heroesOf = ( stage ) => {
	const { content } = setup( { stage, images: SERVER_IMAGES } );
	const screen = screenOf( content );
	return screen.querySelectorAll( 'img.ihq-onb-teach__hero' ).map( ( hero ) => ( {
		src: hero.getAttribute( 'src' ),
		alt: hero.getAttribute( 'alt' ),
		first: screen.children[ 0 ] === hero,
	} ) );
};

test( 'screens 1 and 2 show their hero above the title, decorative (empty alt)', () => {
	assert.deepEqual( heroesOf( 'game_of_kings' ), [ { src: SERVER_IMAGES.game_of_kings, alt: '', first: true } ] );
	assert.deepEqual( heroesOf( 'modern_era' ), [ { src: SERVER_IMAGES.modern_era, alt: '', first: true } ] );
} );

test( 'screens 7, 8 and 9 show no hero (the design reuses screen 2\'s picture there, pending Design)', () => {
	[ 'world_championship', 'world_tour', 'classic' ].forEach( ( stage ) => {
		assert.deepEqual( heroesOf( stage ), [], stage );
	} );
} );

test( 'the other teaching screens show no hero either', () => {
	[ 'equity', 'how_this_works', 'equity_example' ].forEach( ( stage ) => {
		assert.deepEqual( heroesOf( stage ), [], stage );
	} );
} );

test( 'teaching screens always let the flow advance', () => {
	const { engine } = setup( { stage: 'game_of_kings' } );
	assert.equal( engine.advance(), true );
	assert.equal( engine.current(), 'modern_era' );
} );

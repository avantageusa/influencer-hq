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
	chartImageSrc,
	teachingScreens,
	install,
} from './onboarding-screens-teaching.js';

const STATE = { stage: '', answers: {}, platforms: [], handles: {}, language: '', completed: false, completed_at: '' };

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

test( 'screen 10, Equity: the title and both charts, in order, with their titles as alt text', () => {
	const { content } = setup( { stage: 'equity' } );
	const screen = screenOf( content );
	assert.deepEqual( texts( screen, 'h2' ), [ 'Equity' ] );
	const charts = screen.querySelectorAll( 'img[data-chart]' );
	assert.deepEqual( charts.map( ( chart ) => chart.getAttribute( 'data-chart' ) ), [ 'shares', 'value' ] );
	assert.deepEqual( charts.map( ( chart ) => chart.getAttribute( 'alt' ) ), [ 'Earned Equity Shares', 'Value of Shares' ] );
	assert.ok( charts.every( ( chart ) => chart.getAttribute( 'src' ).startsWith( 'data:image/svg+xml;charset=utf-8,' ) ) );
} );

test( 'the charts have the PO-3209 axes', () => {
	const [ shares, value ] = EQUITY_CHARTS;
	const t = ( key ) => TEACHING_STRINGS_EN[ key ];
	assert.deepEqual( shares.xKeys.map( t ), [ 'SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT' ] );
	assert.deepEqual( [ shares.yTicks[ 0 ], shares.yTicks.at( -1 ) ], [ 0, 400 ] );
	assert.deepEqual( value.xKeys.map( t ), [ 'START', '6 MOS', '12 MOS', '18 MOS', '24 MOS' ] );
	assert.deepEqual( [ value.yLabels[ 0 ], value.yLabels.at( -1 ) ], [ '$.20', '$4' ] );
	assert.deepEqual( [ value.yTicks[ 0 ], value.yTicks.at( -1 ) ], [ 0.2, 4 ] );
	EQUITY_CHARTS.forEach( ( chart ) => {
		assert.equal( chart.values.length, chart.xKeys.length, chart.id );
		assert.equal( chart.yLabels.length, chart.yTicks.length, chart.id );
		assert.ok( chart.values.every( ( point ) => point >= chart.yTicks[ 0 ] && point <= chart.yTicks.at( -1 ) ), chart.id );
	} );
} );

test( 'lineChartSvg draws the title, every axis label and one line through every value', () => {
	const svg = lineChartSvg( { title: 'T & <x>', xLabels: [ 'A', 'B', 'C' ], yTicks: [ 0, 10 ], yLabels: [ '0', '10' ], values: [ 0, 5, 10 ] } );
	assert.ok( svg.startsWith( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 340 200"' ) );
	assert.ok( svg.includes( '>T &#38; &#60;x&#62;</text>' ) );
	for ( const label of [ '>A<', '>B<', '>C<', '>0<', '>10<' ] ) {
		assert.ok( svg.includes( label ), label );
	}
	// Plot: x from 44 to 328, y from 172 (min) to 28 (max).
	assert.ok( svg.includes( '<polyline points="44,172 186,100 328,28"' ) );
} );

test( 'lineChartSvg with one point puts it on the left axis', () => {
	const svg = lineChartSvg( { title: '', xLabels: [ 'A' ], yTicks: [ 0, 1 ], yLabels: [ '0', '1' ], values: [ 1 ] } );
	assert.ok( svg.includes( '<polyline points="44,28"' ) );
} );

test( 'chart text follows the language, English where a translation is missing', () => {
	const t = ( key ) => ( { daySun: '日', sharesChartTitle: '株' }[ key ] || TEACHING_STRINGS_EN[ key ] );
	const svg = decodeURIComponent( chartImageSrc( EQUITY_CHARTS[ 0 ], t ).split( ',' ).slice( 1 ).join( ',' ) );
	assert.ok( svg.includes( '>株</text>' ) );
	assert.ok( svg.includes( '>日</text>' ) );
	assert.ok( svg.includes( '>MON</text>' ) );
} );

test( 'a language change redraws the charts; leaving the screen stops listening', () => {
	const { content, api, strings, engine, events } = setup( { stage: 'equity' } );
	strings.add( 'ja', { sharesChartTitle: '獲得株式' } );
	api.setLocale( 'ja' );
	const chart = screenOf( content ).querySelector( 'img[data-chart="shares"]' );
	assert.equal( chart.getAttribute( 'alt' ), '獲得株式' );
	assert.ok( decodeURIComponent( chart.getAttribute( 'src' ) ).includes( '>獲得株式</text>' ) );
	engine.advance();
	const before = chart.getAttribute( 'src' );
	api.setLocale( 'en' );
	assert.equal( chart.getAttribute( 'src' ), before );
	assert.doesNotThrow( () => events.emit( 'locale', 'en' ) );
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

test( 'every piece of copy is a string-table key, so it can be translated', () => {
	const { content, api, strings } = setup( { stage: 'world_tour' } );
	const screen = screenOf( content );
	screen.querySelectorAll( 'h2, p' ).forEach( ( element ) => {
		assert.ok( element.getAttribute( 'data-onb-string' ) in TEACHING_STRINGS_EN );
	} );
	strings.add( 'ko', { tourTitle: '월드 투어' } );
	api.setLocale( 'ko' );
	assert.equal( strings.t( 'ko', 'tourTitle' ), '월드 투어' );
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

test( 'teaching screens always let the flow advance', () => {
	const { engine } = setup( { stage: 'game_of_kings' } );
	assert.equal( engine.advance(), true );
	assert.equal( engine.current(), 'modern_era' );
} );

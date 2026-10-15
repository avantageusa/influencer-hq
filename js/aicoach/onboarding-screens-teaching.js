/**
 * The onboarding teaching screens (ENGR-7567, PO-3063 T5): 1 The Game of Kings, 2 The
 * Modern Era, 7 World Championship, 8 World Tour, 9 Classic, 10 Equity (two sample charts),
 * 11 How This Works and 13 How Much Equity You Could Earn? (the fixed worked example).
 *
 * The copy is the approved English from PO-3203, PO-3208, PO-3209 and PO-3211, character
 * for character, and goes through the onboarding string table so it can be translated
 * (ENGR-7572). Copy on 7, 10 and 13 may still change after the legal review (OQ18): change
 * it here, in TEACHING_STRINGS_EN, and nowhere else.
 *
 * Screen 13 never uses the Influencer's own follower answer: the figures are fixed.
 *
 * A plug-in module: the entry installs it, and it registers its screens with the engine.
 */

/** Approved English copy, by string-table key. */
export const TEACHING_STRINGS_EN = {
	kingsTitle: 'The Game of Kings',
	kingsBody: 'For over 500 years, Baccarat has been known as the Game of Kings.',

	modernTitle: 'The Modern Era',
	modernBody1: 'Propelled into the modern era over 40 years ago by James Bond',
	modernBody2: "Baccarat remains the preferred game of high rollers worldwide, and stands as Asia's game of choice with over 100 million active players.",

	championshipTitle: 'World Championship',
	championshipBody1: '$100,000,000 Guaranteed Payout',
	championshipBody2: 'Contests and Individual Plays from $1 up',
	championshipBody3: 'You and all of your followers will receive $100 to get started.',
	championshipBody4: "This isn't play money, this is FREE money",

	tourTitle: 'World Tour',
	tourBody1: 'Weekend 3-level tournaments which alternate between 13 International cities throughout the year.',
	tourBody2: 'Runs each week from 5 PM Thursday through 10 PM Sunday.',
	tourBody3: 'Enter on the $10 or $100 level and win your way into the $1,000 finals.',
	tourBody4: 'Example: 1100 Finalists will result in $1,000,000 being distributed to the top 30% of Finalists.',

	classicTitle: 'Classic',
	classicBody1: 'Top 30% Split the Pool at the end of each 1 Hand contest.',
	classicBody2: 'Four Entry Fees - $1, $10, $100, $1,000',
	classicBody3: 'Example: 110 Finalists in the $10 contest would result in $1,000 being distributed to the top 30% of Finalists.',

	equityTitle: 'Equity',
	sharesChartTitle: 'Earned Equity Shares',
	valueChartTitle: 'Value of Shares',
	daySun: 'SUN',
	dayMon: 'MON',
	dayTue: 'TUE',
	dayWed: 'WED',
	dayThu: 'THU',
	dayFri: 'FRI',
	daySat: 'SAT',
	periodStart: 'START',
	period6: '6 MOS',
	period12: '12 MOS',
	period18: '18 MOS',
	period24: '24 MOS',

	howTitle: 'HOW THIS WORKS',
	howBullet1: 'You, your followers, and everyone your followers refer',
	howBullet2: 'Other Influencers you refer, and their followers',

	exampleTitle: 'How Much Equity You Could Earn?',
	exampleStep1: '5,000 Followers',
	exampleStep2: '10% Play Weekly',
	exampleStep3: '= 500 Players',
	exampleStep4: '2 Hours of Play/Week/Player',
	exampleStep5: '= 1,000 Hours Play/Week',
	exampleStep6: '20 Plays/Hour/Player',
	exampleStep7: '20,000 Plays/Week',
	exampleStep8: 'Average Play Amount $10',
	exampleStep9: 'Total Play/Week = $200,000',
	exampleStep10: '52 Weeks a Year = $10.4 Million in play',
	exampleStep11: 'EQUITY EARNED: $10.4 Million X 1% of play = $104,000',
	exampleStep12: 'IHQ INITIAL SHARE VALUE: .20 per share',
	exampleStep13: '$104,000 equity @ $.20',
	exampleStep14: '520,000 Your Shares',
	exampleStep15: 'COMPANY VALUE: $0.20 → $2.00',
};

/**
 * The two sample charts of screen 10 (PO-3209). The axes are the story's. The plotted values
 * are illustrative sample data, NOT the Influencer's: placeholders until they are read off
 * frame 46497:79385, which was not readable when this was built. Replace the `values` only.
 */
export const EQUITY_CHARTS = Object.freeze( [
	Object.freeze( {
		id: 'shares',
		titleKey: 'sharesChartTitle',
		xKeys: Object.freeze( [ 'daySun', 'dayMon', 'dayTue', 'dayWed', 'dayThu', 'dayFri', 'daySat' ] ),
		yTicks: Object.freeze( [ 0, 100, 200, 300, 400 ] ),
		yLabels: Object.freeze( [ '0', '100', '200', '300', '400' ] ),
		values: Object.freeze( [ 40, 90, 140, 200, 250, 320, 390 ] ),
	} ),
	Object.freeze( {
		id: 'value',
		titleKey: 'valueChartTitle',
		xKeys: Object.freeze( [ 'periodStart', 'period6', 'period12', 'period18', 'period24' ] ),
		yTicks: Object.freeze( [ 0.2, 1, 2, 3, 4 ] ),
		yLabels: Object.freeze( [ '$.20', '$1', '$2', '$3', '$4' ] ),
		values: Object.freeze( [ 0.2, 0.6, 1.2, 2.2, 4 ] ),
	} ),
] );

/** The text-only teaching screens: a title, then paragraphs. */
const TEXT_SCREENS = Object.freeze( {
	game_of_kings: { title: 'kingsTitle', body: [ 'kingsBody' ] },
	modern_era: { title: 'modernTitle', body: [ 'modernBody1', 'modernBody2' ] },
	world_championship: { title: 'championshipTitle', body: [ 'championshipBody1', 'championshipBody2', 'championshipBody3', 'championshipBody4' ] },
	world_tour: { title: 'tourTitle', body: [ 'tourBody1', 'tourBody2', 'tourBody3', 'tourBody4' ] },
	classic: { title: 'classicTitle', body: [ 'classicBody1', 'classicBody2', 'classicBody3' ] },
} );

/** Screen 13, step by step, top to bottom. */
export const EXAMPLE_STEP_KEYS = Object.freeze( Array.from( { length: 15 }, ( unused, index ) => 'exampleStep' + ( index + 1 ) ) );

// Chart geometry, in SVG user units (the image scales to the column width).
const CHART = Object.freeze( { width: 340, height: 200, left: 44, right: 12, top: 28, bottom: 28 } );
const CHART_COLOURS = Object.freeze( { line: '#fdd65b', grid: '#3a3a3a', text: '#ffffff' } );

function escapeXml( value ) {
	return String( value ).replace( /[&<>"']/g, ( character ) => `&#${ character.charCodeAt( 0 ) };` );
}

/**
 * One line chart as SVG markup. Pure, so it is tested as text.
 *
 * @param {Object}   chart
 * @param {string}   chart.title
 * @param {string[]} chart.xLabels
 * @param {number[]} chart.yTicks  Ascending; the first and last are the axis range.
 * @param {string[]} chart.yLabels One per tick.
 * @param {number[]} chart.values  One per x label, within the range.
 * @return {string} The SVG document.
 */
export function lineChartSvg( { title, xLabels, yTicks, yLabels, values } ) {
	const plotWidth = CHART.width - CHART.left - CHART.right;
	const plotHeight = CHART.height - CHART.top - CHART.bottom;
	const min = yTicks[ 0 ];
	const max = yTicks[ yTicks.length - 1 ];
	const xAt = ( index ) => CHART.left + ( xLabels.length > 1 ? ( index / ( xLabels.length - 1 ) ) * plotWidth : 0 );
	const yAt = ( value ) => CHART.top + plotHeight - ( ( ( value - min ) / ( max - min ) ) * plotHeight );
	const round = ( number ) => Math.round( number * 10 ) / 10;

	const grid = yTicks.map( ( tick, index ) => {
		const y = round( yAt( tick ) );
		return `<line x1="${ CHART.left }" y1="${ y }" x2="${ CHART.width - CHART.right }" y2="${ y }" stroke="${ CHART_COLOURS.grid }"/>` +
			`<text x="${ CHART.left - 6 }" y="${ y + 4 }" text-anchor="end" font-size="11" fill="${ CHART_COLOURS.text }">${ escapeXml( yLabels[ index ] ) }</text>`;
	} ).join( '' );
	const xAxis = xLabels.map( ( label, index ) =>
		`<text x="${ round( xAt( index ) ) }" y="${ CHART.height - 8 }" text-anchor="middle" font-size="11" fill="${ CHART_COLOURS.text }">${ escapeXml( label ) }</text>`,
	).join( '' );
	const points = values.map( ( value, index ) => `${ round( xAt( index ) ) },${ round( yAt( value ) ) }` ).join( ' ' );

	return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${ CHART.width } ${ CHART.height }" font-family="sans-serif">` +
		`<text x="${ CHART.width / 2 }" y="16" text-anchor="middle" font-size="13" font-weight="700" fill="${ CHART_COLOURS.text }">${ escapeXml( title ) }</text>` +
		grid +
		xAxis +
		`<polyline points="${ points }" fill="none" stroke="${ CHART_COLOURS.line }" stroke-width="3" stroke-linejoin="round"/>` +
		'</svg>';
}

/**
 * A chart's SVG in the current language, as an image source.
 *
 * @param {Object}   chart One of EQUITY_CHARTS.
 * @param {Function} t     ( key ) => text.
 * @return {string} A data: URL.
 */
export function chartImageSrc( chart, t ) {
	const svg = lineChartSvg( {
		title: t( chart.titleKey ),
		xLabels: chart.xKeys.map( ( key ) => t( key ) ),
		yTicks: chart.yTicks,
		yLabels: chart.yLabels,
		values: chart.values,
	} );
	return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent( svg );
}

function addHero( container, api, stage ) {
	const src = api.imageUrl( stage );
	if ( ! src ) {
		return;
	}
	const image = container.ownerDocument.createElement( 'img' );
	image.className = 'ihq-onb-teach__hero';
	image.setAttribute( 'src', src );
	image.setAttribute( 'alt', '' );
	image.setAttribute( 'loading', 'eager' );
	container.append( image );
}

function renderText( stage, layout ) {
	return function( { container, api, text } ) {
		addHero( container, api, stage );
		container.append( text( 'h2', layout.title, 'ihq-onb-teach__title' ) );
		layout.body.forEach( ( key ) => container.append( text( 'p', key, 'ihq-onb-teach__body' ) ) );
		return {};
	};
}

function renderEquity( { container, api, t, text } ) {
	addHero( container, api, 'equity' );
	container.append( text( 'h2', 'equityTitle', 'ihq-onb-teach__title' ) );
	const images = EQUITY_CHARTS.map( ( chart ) => {
		const image = container.ownerDocument.createElement( 'img' );
		image.className = 'ihq-onb-teach__chart';
		image.setAttribute( 'data-chart', chart.id );
		container.append( image );
		return { chart, image };
	} );
	function draw() {
		images.forEach( ( { chart, image } ) => {
			image.setAttribute( 'src', chartImageSrc( chart, t ) );
			image.setAttribute( 'alt', t( chart.titleKey ) );
		} );
	}
	draw();
	const stop = api.events.on( 'locale', draw );
	return { destroy: stop };
}

function renderHowItWorks( { container, api, text } ) {
	addHero( container, api, 'how_this_works' );
	container.append( text( 'h2', 'howTitle', 'ihq-onb-teach__title' ) );
	const list = container.ownerDocument.createElement( 'ul' );
	list.className = 'ihq-onb-teach__bullets';
	[ 'howBullet1', 'howBullet2' ].forEach( ( key ) => list.append( text( 'li', key ) ) );
	container.append( list );
	return {};
}

function renderExample( { container, api, text } ) {
	addHero( container, api, 'equity_example' );
	container.append( text( 'h2', 'exampleTitle', 'ihq-onb-teach__title' ) );
	const list = container.ownerDocument.createElement( 'ol' );
	list.className = 'ihq-onb-teach__steps';
	EXAMPLE_STEP_KEYS.forEach( ( key ) => list.append( text( 'li', key, 'ihq-onb-teach__step' ) ) );
	container.append( list );
	return {};
}

/**
 * The screen definitions this module registers, in screen order.
 *
 * @return {Object[]} Engine screen definitions.
 */
export function teachingScreens() {
	return [
		{ id: 'game_of_kings', render: renderText( 'game_of_kings', TEXT_SCREENS.game_of_kings ) },
		{ id: 'modern_era', render: renderText( 'modern_era', TEXT_SCREENS.modern_era ) },
		{ id: 'world_championship', render: renderText( 'world_championship', TEXT_SCREENS.world_championship ) },
		{ id: 'world_tour', render: renderText( 'world_tour', TEXT_SCREENS.world_tour ) },
		{ id: 'classic', render: renderText( 'classic', TEXT_SCREENS.classic ) },
		{ id: 'equity', render: renderEquity },
		{ id: 'how_this_works', render: renderHowItWorks },
		{ id: 'equity_example', render: renderExample },
	];
}

/**
 * Adds the English copy and registers the screens.
 *
 * @param {Object} api The onboarding API.
 */
export function install( api ) {
	api.strings.add( 'en', TEACHING_STRINGS_EN );
	teachingScreens().forEach( ( definition ) => api.screens.register( definition ) );
}

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
	sharesAxis0: '0',
	sharesAxis100: '100',
	sharesAxis200: '200',
	sharesAxis300: '300',
	sharesAxis400: '400',
	valueAxis020: '$.20',
	valueAxis1: '$1',
	valueAxis2: '$2',
	valueAxis3: '$3',
	valueAxis4: '$4',
	// The charts' alt text (decision 12): the title, then each plotted value with its x label.
	// Templates, so a language can reorder the words; {name} is filled in by chartAltText().
	chartAltSummary: '{title}: {points}',
	chartAltSeparator: ', ',
	chartAltPointOn: '{value} on {label}',
	chartAltPointAt: '{value} at {label}',
	chartAltMoney: '${amount}',

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
 * The two sample charts of screen 10 (PO-3209). The axes are the story's; every axis label
 * is a string-table key. The plotted values are the design's sample data, NOT the
 * Influencer's: read off the drawn line in Figma 46893:117629, frame "10 Equity 3". The design
 * line is hand-drawn, so the points between the first and the last are a reading of it.
 */
export const EQUITY_CHARTS = Object.freeze( [
	Object.freeze( {
		id: 'shares',
		titleKey: 'sharesChartTitle',
		xKeys: Object.freeze( [ 'daySun', 'dayMon', 'dayTue', 'dayWed', 'dayThu', 'dayFri', 'daySat' ] ),
		yTicks: Object.freeze( [ 0, 100, 200, 300, 400 ] ),
		yKeys: Object.freeze( [ 'sharesAxis0', 'sharesAxis100', 'sharesAxis200', 'sharesAxis300', 'sharesAxis400' ] ),
		values: Object.freeze( [ 0, 70, 70, 130, 250, 275, 400 ] ),
	} ),
	Object.freeze( {
		id: 'value',
		titleKey: 'valueChartTitle',
		xKeys: Object.freeze( [ 'periodStart', 'period6', 'period12', 'period18', 'period24' ] ),
		yTicks: Object.freeze( [ 0.2, 1, 2, 3, 4 ] ),
		yKeys: Object.freeze( [ 'valueAxis020', 'valueAxis1', 'valueAxis2', 'valueAxis3', 'valueAxis4' ] ),
		values: Object.freeze( [ 0.2, 0.9, 1.2, 2.2, 4 ] ),
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

// Chart geometry and type, in SVG user units: the design's chart box ("Earned Equity Shares
// with graph", Figma 46893:117629, "10 Equity 3"), 198.5 x 149.8 px, drawn at that size.
const CHART = Object.freeze( {
	width: 198.5,
	height: 149.8,
	cornerRadius: 3.8,
	titleBaseline: 14,
	titleFontSize: 10,
	// The dark plot area inside the box.
	frame: Object.freeze( { x: 6, y: 20.3, width: 186.6, height: 119.7 } ),
	// The y axis, and the first and last x positions of the line.
	plotLeft: 19.5,
	plotRight: 177,
	// The gridlines and the x axis run on past the last point.
	gridRight: 191,
	// Where the y axis and the vertical gridlines start, and where the first and last y ticks sit.
	gridTop: 21,
	plotTop: 28.9,
	plotBottom: 126.4,
	// Gap between a y label's right edge and the y axis.
	yLabelGap: 2.2,
	xLabelBaseline: 134.6,
	axisFontSize: 5,
	thinLineWidth: 0.5,
	lineWidth: 1.5,
	gridDash: '1 1',
	// Coordinates are rounded to one decimal place (1 / 10 of a unit).
	coordinateScale: 10,
} );
// A data: SVG cannot read CSS custom properties, so the design's colours are written here.
const CHART_COLOURS = Object.freeze( {
	box: '#2a2b37',
	frame: '#18181b',
	frameBorder: '#5f5f5f',
	grid: '#4b4b4b',
	axis: '#ffffff',
	line: '#ffffff',
	text: '#ffffff',
} );

function escapeXml( value ) {
	return String( value ).replace( /[&<>"']/g, ( character ) => `&#${ character.charCodeAt( 0 ) };` );
}

function roundCoordinate( number ) {
	return Math.round( number * CHART.coordinateScale ) / CHART.coordinateScale;
}

function axisText( { x, y, anchor, label } ) {
	return `<text x="${ x }" y="${ y }" text-anchor="${ anchor }" font-size="${ CHART.axisFontSize }" fill="${ CHART_COLOURS.text }">${ escapeXml( label ) }</text>`;
}

/**
 * How far up the y axis a value sits, from 0 (the first tick) to 1 (the last). The design
 * spaces the ticks evenly whatever their values ($.20, $1, $2 ... on the value chart), so a
 * value is placed between the two ticks around it.
 *
 * @param {number}   value
 * @param {number[]} yTicks Ascending, at least two.
 * @return {number} The fraction of the axis height.
 */
export function tickFraction( value, yTicks ) {
	const segments = yTicks.length - 1;
	const below = yTicks.findLastIndex( ( tick ) => tick <= value );
	const segment = Math.min( Math.max( below, 0 ), segments - 1 );
	const low = yTicks[ segment ];
	const high = yTicks[ segment + 1 ];
	return ( segment + ( ( value - low ) / ( high - low ) ) ) / segments;
}

function gridLine( { x1, y1, x2, y2 } ) {
	return `<line x1="${ x1 }" y1="${ y1 }" x2="${ x2 }" y2="${ y2 }" stroke="${ CHART_COLOURS.grid }" stroke-width="${ CHART.thinLineWidth }" stroke-dasharray="${ CHART.gridDash }"/>`;
}

/**
 * One line chart as SVG markup, drawn as the design draws it: a title over a dark plot with
 * dashed gridlines at every tick and every x position, white axes and a white line. Pure, so
 * it is tested as text.
 *
 * @param {Object}   chart
 * @param {string}   chart.title
 * @param {string[]} chart.xLabels
 * @param {number[]} chart.yTicks  Ascending, at least two, drawn evenly spaced; the first and
 *                                 last are the axis range.
 * @param {string[]} chart.yLabels One per tick.
 * @param {number[]} chart.values  One per x label, within the range.
 * @return {string} The SVG document.
 */
export function lineChartSvg( { title, xLabels, yTicks, yLabels, values } ) {
	const plotWidth = CHART.plotRight - CHART.plotLeft;
	const plotHeight = CHART.plotBottom - CHART.plotTop;
	const xAt = ( index ) => roundCoordinate( CHART.plotLeft + ( xLabels.length > 1 ? ( index / ( xLabels.length - 1 ) ) * plotWidth : 0 ) );
	const yAt = ( value ) => roundCoordinate( CHART.plotBottom - ( tickFraction( value, yTicks ) * plotHeight ) );
	const { frame } = CHART;

	// The lowest tick and the first x position are the axes, drawn solid below.
	const horizontalGrid = yTicks.slice( 1 ).map( ( tick ) => gridLine( { x1: CHART.plotLeft, y1: yAt( tick ), x2: CHART.gridRight, y2: yAt( tick ) } ) ).join( '' );
	const verticalGrid = xLabels.slice( 1 ).map( ( label, index ) => gridLine( { x1: xAt( index + 1 ), y1: CHART.gridTop, x2: xAt( index + 1 ), y2: CHART.plotBottom } ) ).join( '' );
	const yAxisLabels = yTicks.map( ( tick, index ) => axisText( { x: roundCoordinate( CHART.plotLeft - CHART.yLabelGap ), y: yAt( tick ), anchor: 'end', label: yLabels[ index ] } ) ).join( '' );
	const xAxisLabels = xLabels.map( ( label, index ) => axisText( { x: xAt( index ), y: CHART.xLabelBaseline, anchor: 'middle', label } ) ).join( '' );
	const points = values.map( ( value, index ) => `${ xAt( index ) },${ yAt( value ) }` ).join( ' ' );

	return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${ CHART.width } ${ CHART.height }" font-family="Inter, sans-serif">` +
		`<rect width="${ CHART.width }" height="${ CHART.height }" rx="${ CHART.cornerRadius }" fill="${ CHART_COLOURS.box }"/>` +
		`<text x="${ CHART.width / 2 }" y="${ CHART.titleBaseline }" text-anchor="middle" font-size="${ CHART.titleFontSize }" font-weight="700" fill="${ CHART_COLOURS.text }">${ escapeXml( title ) }</text>` +
		`<rect x="${ frame.x }" y="${ frame.y }" width="${ frame.width }" height="${ frame.height }" fill="${ CHART_COLOURS.frame }" stroke="${ CHART_COLOURS.frameBorder }" stroke-width="${ CHART.thinLineWidth }"/>` +
		horizontalGrid +
		verticalGrid +
		`<path d="M${ CHART.plotLeft } ${ CHART.gridTop }V${ CHART.plotBottom }H${ CHART.gridRight }" fill="none" stroke="${ CHART_COLOURS.axis }" stroke-width="${ CHART.thinLineWidth }"/>` +
		yAxisLabels +
		xAxisLabels +
		`<polyline points="${ points }" fill="none" stroke="${ CHART_COLOURS.line }" stroke-width="${ CHART.lineWidth }" stroke-linecap="round" stroke-linejoin="round"/>` +
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
		yLabels: chart.yKeys.map( ( key ) => t( key ) ),
		values: chart.values,
	} );
	return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent( svg );
}

/** The string-table keys of the alt text's frame, shared by both charts. */
export const CHART_ALT_KEYS = Object.freeze( {
	summary: 'chartAltSummary',
	separator: 'chartAltSeparator',
	money: 'chartAltMoney',
} );

const VALUE_FORMAT = Object.freeze( { count: 'count', money: 'money' } );

// Money in the alt text always shows cents ($0.20, $4.00): read aloud, "$.20" is unclear.
const MONEY_DECIMALS = 2;

/**
 * How each chart's alt text reads its values, by chart id: the joining phrase between a value
 * and its x label ("on SUN" for days, "at 6 MOS" for periods), and how a value is written.
 */
export const CHART_ALT_FORMATS = Object.freeze( {
	shares: Object.freeze( { pointKey: 'chartAltPointOn', valueFormat: VALUE_FORMAT.count } ),
	value: Object.freeze( { pointKey: 'chartAltPointAt', valueFormat: VALUE_FORMAT.money } ),
} );

/**
 * Fills each {name} in a template from fields; a name with no field is left as written.
 *
 * @param {string}                 template
 * @param {Object<string, string>} fields
 * @return {string} The filled-in text.
 */
function fillTemplate( template, fields ) {
	return template.replace( /\{(\w+)\}/g, ( placeholder, name ) => {
		if ( ! Object.hasOwn( fields, name ) ) {
			return placeholder;
		}
		return fields[ name ];
	} );
}

function formatValue( { value, valueFormat, t } ) {
	if ( valueFormat === VALUE_FORMAT.money ) {
		return fillTemplate( t( CHART_ALT_KEYS.money ), { amount: value.toFixed( MONEY_DECIMALS ) } );
	}
	return String( value );
}

/**
 * A chart's alt text in the current language, built from its own values and x labels, e.g.
 * "Earned Equity Shares: 0 on SUN, 70 on MON, …". It describes the plotted values only. A
 * chart with no entry in CHART_ALT_FORMATS gets its title alone.
 *
 * @param {Object}   chart One of EQUITY_CHARTS.
 * @param {Function} t     ( key ) => text.
 * @return {string} The alt text.
 */
export function chartAltText( chart, t ) {
	const title = t( chart.titleKey );
	const format = CHART_ALT_FORMATS[ chart.id ];
	if ( ! format ) {
		return title;
	}
	const points = chart.values.map( ( value, index ) => fillTemplate( t( format.pointKey ), {
		value: formatValue( { value, valueFormat: format.valueFormat, t } ),
		label: t( chart.xKeys[ index ] ),
	} ) );
	return fillTemplate( t( CHART_ALT_KEYS.summary ), {
		title,
		points: points.join( t( CHART_ALT_KEYS.separator ) ),
	} );
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
			image.setAttribute( 'alt', chartAltText( chart, t ) );
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
	container.append( text( 'h2', 'exampleTitle', 'ihq-onb-teach__title ihq-onb-teach__title--plain' ) );
	const list = container.ownerDocument.createElement( 'ol' );
	list.className = 'ihq-onb-teach__steps';
	EXAMPLE_STEP_KEYS.forEach( ( key ) => list.append( text( 'li', key, 'ihq-onb-teach__step' ) ) );
	container.append( list );
	return {};
}

/**
 * The screen definitions this module registers, in screen order. None declares an advanceMode:
 * a teaching screen moves on when Sami's line ends (NARRATION, the default), and until its
 * script exists (OQ1) after the reading time of its own text (design decision 1).
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

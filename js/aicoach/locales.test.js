import { test } from 'node:test';
import assert from 'node:assert/strict';
import { SUPPORTED_LOCALES, I18N_EN, I18N_TRANSLATIONS, t, detectLocale } from './locales.js';

// PO-3109: the appointment screen's text, English only until translations exist.
const APPOINTMENT_KEYS = [
	'appointmentReady',
	'appointmentTitle',
	'appointmentIn30Minutes',
	'appointmentInAnHour',
	'appointmentOther',
	'appointmentDate',
	'appointmentTimeZone',
	'appointmentTime',
	'appointmentCopy',
	'appointmentHint',
	'appointmentCopied',
	'appointmentRequestFailed',
	'appointmentCopyFailed',
	'appointmentError-choice-missing',
	'appointmentError-date-missing',
	'appointmentError-time-zone-missing',
	'appointmentError-time-missing',
	'appointmentError-date-invalid',
	'appointmentError-time-invalid',
	'appointmentError-time-zone-invalid',
	'appointmentError-time-does-not-exist',
	'appointmentError-time-in-past',
	'appointmentDoneTitle',
	'appointmentDoneMessage',
	'appointmentDoneContinue',
];

const CHANNEL_KEYS = [ 'email', 'kakaotalk', 'line', 'sms', 'telegram', 'wechat', 'whatsapp', 'zalo' ];

// Keys that have no approved translation yet in any language but English;
// t() shows the English copy for them. The flow never invents unapproved copy.
const GAPS_IN_EVERY_LANGUAGE = [
	'tierGroupLabel',
	'magicNike',
	'magicNikeWorth',
	'worldCompetitionName',
	'youFollowers',
	'versus',
	'allInfluencersFollowers',
	'communityCompetitionName',
	'privateChallengeName',
	'friendFollowers',
	'channelsHint',
	'timeUpTitle',
	'timeUpKeepTalking',
	'timeUpSetAppointment',
	...APPOINTMENT_KEYS,
	...CHANNEL_KEYS.map( ( key ) => 'channel-' + key + '-inputLabel' ),
	...CHANNEL_KEYS.map( ( key ) => 'channel-' + key + '-placeholder' ),
];

// The gaps that are specific to one language (see the notes above
// I18N_TRANSLATIONS in locales.js). A new gap, or a gap that gets filled,
// changes this table, so it cannot happen unnoticed.
const EXPECTED_MISSING = {
	zh: GAPS_IN_EVERY_LANGUAGE,
	yue: GAPS_IN_EVERY_LANGUAGE,
	th: GAPS_IN_EVERY_LANGUAGE,
	vi: GAPS_IN_EVERY_LANGUAGE,
	ja: [ 'belief', 'weBelieve', ...GAPS_IN_EVERY_LANGUAGE ],
	ko: [ ...GAPS_IN_EVERY_LANGUAGE, 'firstName', 'lastName' ],
};

function missingKeys( code ) {
	const table = I18N_TRANSLATIONS[ code ];
	return Object.keys( I18N_EN ).filter( ( key ) => ! table[ key ] );
}

test( 'the seven launch languages, in selector order, each with a native label', () => {
	assert.deepEqual(
		SUPPORTED_LOCALES.map( ( locale ) => locale.code ),
		[ 'en', 'zh', 'yue', 'ja', 'ko', 'th', 'vi' ]
	);
	SUPPORTED_LOCALES.forEach( ( locale ) => {
		assert.equal( typeof locale.nativeLabel, 'string' );
		assert.notEqual( locale.nativeLabel, '' );
	} );
	const labels = SUPPORTED_LOCALES.map( ( locale ) => locale.nativeLabel );
	assert.equal( new Set( labels ).size, labels.length );
} );

test( 'every supported language has a table and every table belongs to a supported language', () => {
	assert.deepEqual(
		Object.keys( I18N_TRANSLATIONS ).sort(),
		SUPPORTED_LOCALES.map( ( locale ) => locale.code ).sort()
	);
	assert.equal( I18N_TRANSLATIONS.en, I18N_EN );
} );

test( 'no translation has a key that English lacks (catches typos in a key name)', () => {
	Object.keys( I18N_TRANSLATIONS ).forEach( ( code ) => {
		const orphans = Object.keys( I18N_TRANSLATIONS[ code ] ).filter( ( key ) => ! ( key in I18N_EN ) );
		assert.deepEqual( orphans, [], code );
	} );
} );

test( 'every table value is a string', () => {
	Object.keys( I18N_TRANSLATIONS ).forEach( ( code ) => {
		Object.keys( I18N_TRANSLATIONS[ code ] ).forEach( ( key ) => {
			assert.equal( typeof I18N_TRANSLATIONS[ code ][ key ], 'string', code + '.' + key );
		} );
	} );
} );

test( 'English is complete except for three channel placeholders that are meant to be empty', () => {
	assert.deepEqual( missingKeys( 'en' ).sort(), [
		'channel-kakaotalk-placeholder',
		'channel-wechat-placeholder',
		'channel-zalo-placeholder',
	] );
} );

test( 'the keys each language still lacks are exactly the known, documented gaps', () => {
	Object.keys( EXPECTED_MISSING ).forEach( ( code ) => {
		assert.deepEqual( missingKeys( code ).sort(), [ ...EXPECTED_MISSING[ code ] ].sort(), code );
	} );
} );

test( 't returns the language\'s own copy when it has one', () => {
	assert.equal( t( 'zh', 'belief' ), I18N_TRANSLATIONS.zh.belief );
	assert.equal( t( 'ko', 'weBelieve' ), I18N_TRANSLATIONS.ko.weBelieve );
	assert.notEqual( t( 'zh', 'belief' ), I18N_EN.belief );
} );

test( 't falls back to English for a key the language lacks', () => {
	assert.equal( t( 'zh', 'timeUpTitle' ), I18N_EN.timeUpTitle );
	assert.equal( t( 'ja', 'belief' ), I18N_EN.belief );
	assert.equal( t( 'ko', 'firstName' ), I18N_EN.firstName );
} );

test( 't falls back to English for an unknown language and returns an empty string for an unknown key', () => {
	assert.equal( t( 'fr', 'belief' ), I18N_EN.belief );
	assert.equal( t( undefined, 'belief' ), I18N_EN.belief );
	assert.equal( t( 'zh', 'no-such-key' ), '' );
	assert.equal( t( 'fr', 'no-such-key' ), '' );
} );

test( 't treats an empty translation like a missing one', () => {
	assert.equal( t( 'en', 'channel-zalo-placeholder' ), '' );
	assert.equal( I18N_EN[ 'channel-zalo-placeholder' ], '' );
} );

const DETECT_CASES = [
	[ [ 'en-US' ], 'en' ],
	[ [ 'EN-gb' ], 'en' ],
	[ [ 'ja-JP' ], 'ja' ],
	[ [ 'ko' ], 'ko' ],
	[ [ 'th-TH' ], 'th' ],
	[ [ 'vi-VN' ], 'vi' ],
	[ [ 'zh' ], 'zh' ],
	[ [ 'zh-CN' ], 'zh' ],
	[ [ 'zh-TW' ], 'zh' ],
	[ [ 'zh-Hans-CN' ], 'zh' ],
	[ [ 'zh-HK' ], 'yue' ],
	[ [ 'zh-MO' ], 'yue' ],
	[ [ 'zh-hk' ], 'yue' ],
	[ [ 'zh-Hant-HK' ], 'yue' ],
	[ [ 'yue' ], 'yue' ],
	[ [ 'yue-HK' ], 'yue' ],
	[ [ 'fr-FR' ], 'en' ],
	[ [ 'de', 'es' ], 'en' ],
	[ [ 'fr-FR', 'ja-JP', 'en-US' ], 'ja' ],
	[ [ 'fr', 'zh-HK', 'ja' ], 'yue' ],
	[ [ '', 'ko-KR' ], 'ko' ],
	[ [ '' ], 'en' ],
	[ [], 'en' ],
];

DETECT_CASES.forEach( ( [ tags, expected ] ) => {
	test( 'detectLocale( ' + JSON.stringify( tags ) + ' ) is ' + expected, () => {
		assert.equal( detectLocale( tags ), expected );
	} );
} );

test( 'detectLocale ignores a region for a primary language it does not support', () => {
	assert.equal( detectLocale( [ 'pt-BR' ] ), 'en' );
	assert.equal( detectLocale( [ 'zh-CN-x-hk' ] ), 'zh' );
} );

import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
	SCREENS,
	EQUITY_SCREENS,
	COMPETITION_SCREENS,
	COMM_CHANNELS_SCREEN,
	FINAL_SCREEN,
	CHANNELS,
	getEquityScreensForTier,
	getCompetitionScreensForTier,
	isValidPhoneNumber,
} from './screens.js';

function panels( screens ) {
	return screens.map( ( screen ) => screen.panel );
}

function validatorFor( key ) {
	return CHANNELS.find( ( channel ) => channel.key === key ).validate;
}

test( 'the opening queue is intro, the two belief screens and the time selection, in that order', () => {
	assert.deepEqual( panels( SCREENS ), [ 'intro', 'believe-1', 'believe-2', 'home' ] );
} );

test( 'every screen has a panel name and a non-empty script, and no panel name repeats', () => {
	const all = [
		...SCREENS,
		...Object.values( EQUITY_SCREENS ),
		...Object.values( COMPETITION_SCREENS ),
		COMM_CHANNELS_SCREEN,
		FINAL_SCREEN,
	];
	all.forEach( ( screen ) => {
		assert.equal( typeof screen.panel, 'string' );
		assert.notEqual( screen.panel, '' );
		assert.equal( typeof screen.script, 'string' );
		assert.notEqual( screen.script.trim(), '' );
	} );
	const names = panels( all );
	assert.equal( new Set( names ).size, names.length );
} );

test( 'the queue after identity, channels and the final screen uses their own panel names', () => {
	assert.equal( COMM_CHANNELS_SCREEN.panel, 'comm-channels' );
	assert.equal( FINAL_SCREEN.panel, 'final-continue' );
} );

test( 'the 10-minute tier gets all three equity examples in order', () => {
	assert.deepEqual( panels( getEquityScreensForTier( '10' ) ), [ 'equity-magic', 'equity-alix', 'equity-bts' ] );
} );

test( 'the 5- and 2-minute tiers, and anything unexpected, get BTS only', () => {
	assert.deepEqual( panels( getEquityScreensForTier( '5' ) ), [ 'equity-bts' ] );
	assert.deepEqual( panels( getEquityScreensForTier( '2' ) ), [ 'equity-bts' ] );
	assert.deepEqual( panels( getEquityScreensForTier( undefined ) ), [ 'equity-bts' ] );
	assert.deepEqual( panels( getEquityScreensForTier( 10 ) ), [ 'equity-bts' ] );
} );

test( 'equity screens come back as the shared definitions, not copies', () => {
	assert.equal( getEquityScreensForTier( '10' )[ 0 ], EQUITY_SCREENS.magic );
	assert.equal( getEquityScreensForTier( '5' )[ 0 ], EQUITY_SCREENS.bts );
} );

test( 'the 2-minute tier skips the competition screens', () => {
	assert.deepEqual( getCompetitionScreensForTier( '2' ), [] );
} );

test( 'the 5- and 10-minute tiers get world, community and private in order', () => {
	const expected = [ 'competition-world', 'competition-community', 'competition-private' ];
	assert.deepEqual( panels( getCompetitionScreensForTier( '5' ) ), expected );
	assert.deepEqual( panels( getCompetitionScreensForTier( '10' ) ), expected );
} );

test( 'each call returns a new array, so appending to it cannot change the next call', () => {
	const first = getCompetitionScreensForTier( '5' );
	first.push( { panel: 'extra', script: 'x' } );
	assert.equal( getCompetitionScreensForTier( '5' ).length, 3 );
	const equity = getEquityScreensForTier( '10' );
	equity.pop();
	assert.equal( getEquityScreensForTier( '10' ).length, 3 );
} );

test( 'the eight channels, in display order', () => {
	assert.deepEqual( CHANNELS.map( ( channel ) => channel.key ), [
		'email', 'kakaotalk', 'line', 'sms', 'telegram', 'wechat', 'whatsapp', 'zalo',
	] );
} );

test( 'phone numbers must be E.164: a plus, a non-zero first digit and 8 to 15 digits in all', () => {
	assert.equal( isValidPhoneNumber( '+66812345678' ), true );
	assert.equal( isValidPhoneNumber( '+12345678' ), true );
	assert.equal( isValidPhoneNumber( '+123456789012345' ), true );
	assert.equal( isValidPhoneNumber( '+1234567' ), false );
	assert.equal( isValidPhoneNumber( '+1234567890123456' ), false );
	assert.equal( isValidPhoneNumber( '+0123456789' ), false );
	assert.equal( isValidPhoneNumber( '0812345678' ), false );
	assert.equal( isValidPhoneNumber( '+66 812345678' ), false );
	assert.equal( isValidPhoneNumber( '+6681234567a' ), false );
	assert.equal( isValidPhoneNumber( '' ), false );
} );

test( 'SMS and WhatsApp use the phone number check', () => {
	[ 'sms', 'whatsapp' ].forEach( ( key ) => {
		assert.equal( validatorFor( key )( '+66812345678' ), true, key );
		assert.equal( validatorFor( key )( '66812345678' ), false, key );
		assert.equal( validatorFor( key )( '' ), false, key );
	} );
} );

test( 'email needs something before and after the @ and a dot in the domain', () => {
	const validate = validatorFor( 'email' );
	assert.equal( validate( 'a@b.co' ), true );
	assert.equal( validate( 'first.last+tag@example.com' ), true );
	assert.equal( validate( 'a@b' ), false );
	assert.equal( validate( '@b.co' ), false );
	assert.equal( validate( 'a@.co' ), false );
	assert.equal( validate( 'a b@c.co' ), false );
	assert.equal( validate( 'a@b c.co' ), false );
	assert.equal( validate( '' ), false );
} );

test( 'a Line ID is U followed by exactly 32 hex characters', () => {
	const validate = validatorFor( 'line' );
	assert.equal( validate( 'U' + '0123456789abcdef'.repeat( 2 ) ), true );
	assert.equal( validate( 'U' + 'ABCDEF0123456789'.repeat( 2 ) ), true );
	assert.equal( validate( 'U' + '0'.repeat( 31 ) ), false );
	assert.equal( validate( 'U' + '0'.repeat( 33 ) ), false );
	assert.equal( validate( 'u' + '0'.repeat( 32 ) ), false );
	assert.equal( validate( 'U' + 'g'.repeat( 32 ) ), false );
	assert.equal( validate( '' ), false );
} );

test( 'channels whose format is still TBD accept any non-empty value and reject an empty one', () => {
	[ 'kakaotalk', 'telegram', 'wechat', 'zalo' ].forEach( ( key ) => {
		assert.equal( validatorFor( key )( 'x' ), true, key );
		assert.equal( validatorFor( key )( '@someone' ), true, key );
		assert.equal( validatorFor( key )( '' ), false, key );
	} );
} );

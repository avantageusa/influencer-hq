import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createStringTable, SHELL_STRINGS_EN, FALLBACK_LOCALE } from './onboarding-strings.js';

test( 'the shell strings', () => {
	assert.equal( FALLBACK_LOCALE, 'en' );
	assert.deepEqual( SHELL_STRINGS_EN, {
		menuOpen: 'Open menu',
		menuClose: 'Close menu',
		logOut: 'Log out',
		soundMute: 'Mute Sami',
		soundUnmute: 'Unmute Sami',
		languageSelect: 'Select language',
		saveToHomeScreen: 'SAVE IHQ TO HOME SCREEN',
	} );
} );

test( 't() returns the language own text, else English, else empty', () => {
	const strings = createStringTable();
	strings.add( 'en', { save: 'SAVE', menu: 'Menu' } );
	strings.add( 'ja', { save: '保存' } );
	assert.equal( strings.t( 'ja', 'save' ), '保存' );
	assert.equal( strings.t( 'ja', 'menu' ), 'Menu' );
	assert.equal( strings.t( 'ko', 'save' ), 'SAVE' );
	assert.equal( strings.t( 'ja', 'missing' ), '' );
	assert.equal( strings.t( 'en', 'missing' ), '' );
} );

test( 'an empty translation falls back to English', () => {
	const strings = createStringTable();
	strings.add( 'en', { save: 'SAVE' } );
	strings.add( 'th', { save: '' } );
	assert.equal( strings.t( 'th', 'save' ), 'SAVE' );
	assert.equal( strings.has( 'th', 'save' ), false );
} );

test( 'add() merges tables for a language, a later one overriding', () => {
	const strings = createStringTable();
	strings.add( 'en', { a: '1', b: '2' } );
	strings.add( 'en', { b: '3', c: '4' } );
	assert.deepEqual( strings.keys(), [ 'a', 'b', 'c' ] );
	assert.equal( strings.t( 'en', 'b' ), '3' );
} );

test( 'add() does not keep a reference to the table passed in', () => {
	const strings = createStringTable();
	const table = { a: '1' };
	strings.add( 'en', table );
	table.a = 'changed';
	assert.equal( strings.t( 'en', 'a' ), '1' );
} );

test( 'keys() lists a language, English by default, empty for an unknown one', () => {
	const strings = createStringTable();
	strings.add( 'en', { a: '1', b: '2' } );
	strings.add( 'vi', { a: 'một' } );
	assert.deepEqual( strings.keys(), [ 'a', 'b' ] );
	assert.deepEqual( strings.keys( 'vi' ), [ 'a' ] );
	assert.deepEqual( strings.keys( 'zh' ), [] );
} );

test( 'has() is true only for the language own text', () => {
	const strings = createStringTable();
	strings.add( 'en', { a: '1' } );
	strings.add( 'vi', { a: 'một' } );
	assert.equal( strings.has( 'vi', 'a' ), true );
	assert.equal( strings.has( 'zh', 'a' ), false );
	assert.equal( strings.has( 'en', 'b' ), false );
} );

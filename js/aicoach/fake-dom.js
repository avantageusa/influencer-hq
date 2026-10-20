/**
 * A tiny stand-in for the DOM, for the onboarding tests next to it (the way fake-clock.js
 * stands in for timers). It covers only what the onboarding modules use: creating
 * elements, attributes, text, `hidden`, `disabled`, `value`, `checked`, class lists, focus and
 * select() (recorded on the document as activeElement and selectedElement), events
 * with bubbling, and querySelector(All) for simple selectors (a tag, `.class`, `#id`,
 * `[attr]`, `[attr="value"]`, or one of each joined, like `button[data-x="y"]`).
 *
 * Not a module the browser loads: its name does not start with onboarding-.
 */

const SIMPLE_SELECTOR = /^([a-z0-9-]+)?((?:\.[\w-]+)*)(#[\w-]+)?((?:\[[\w-]+(?:="[^"]*")?\])*)$/i;
const ATTRIBUTE_PART = /\[([\w-]+)(?:="([^"]*)")?\]/g;

function parseSelector( selector ) {
	const match = SIMPLE_SELECTOR.exec( selector.trim() );
	if ( ! match ) {
		throw new Error( 'fake-dom: unsupported selector ' + selector );
	}
	const attributes = [];
	let part;
	ATTRIBUTE_PART.lastIndex = 0;
	while ( ( part = ATTRIBUTE_PART.exec( match[ 4 ] || '' ) ) ) {
		attributes.push( { name: part[ 1 ], value: part[ 2 ] } );
	}
	return {
		tag: match[ 1 ] ? match[ 1 ].toUpperCase() : null,
		classes: ( match[ 2 ] || '' ).split( '.' ).filter( Boolean ),
		id: match[ 3 ] ? match[ 3 ].slice( 1 ) : null,
		attributes,
	};
}

function matches( element, parsed ) {
	if ( parsed.tag && element.tagName !== parsed.tag ) {
		return false;
	}
	if ( parsed.id && element.id !== parsed.id ) {
		return false;
	}
	if ( parsed.classes.some( ( name ) => ! element.classList.contains( name ) ) ) {
		return false;
	}
	return parsed.attributes.every( ( { name, value } ) => {
		if ( ! element.hasAttribute( name ) ) {
			return false;
		}
		return undefined === value || element.getAttribute( name ) === value;
	} );
}

class FakeClassList {
	constructor( element ) {
		this.element = element;
	}
	get names() {
		return ( this.element.getAttribute( 'class' ) || '' ).split( /\s+/ ).filter( Boolean );
	}
	contains( name ) {
		return this.names.includes( name );
	}
	add( ...names ) {
		const next = new Set( this.names );
		names.forEach( ( name ) => next.add( name ) );
		this.element.setAttribute( 'class', Array.from( next ).join( ' ' ) );
	}
	remove( ...names ) {
		this.element.setAttribute( 'class', this.names.filter( ( name ) => ! names.includes( name ) ).join( ' ' ) );
	}
	toggle( name, force ) {
		const on = undefined === force ? ! this.contains( name ) : force;
		if ( on ) {
			this.add( name );
		} else {
			this.remove( name );
		}
		return on;
	}
}

export class FakeElement {
	constructor( tagName, ownerDocument ) {
		this.tagName = tagName.toUpperCase();
		this.ownerDocument = ownerDocument;
		this.attributes = new Map();
		this.children = [];
		this.parentNode = null;
		this.listeners = new Map();
		this.ownText = '';
		this.value = '';
		this.checked = false;
		this.classList = new FakeClassList( this );
		this.style = {};
		this.dataset = new Proxy( {}, {
			get: ( target, key ) => this.getAttribute( 'data-' + String( key ).replace( /[A-Z]/g, ( c ) => '-' + c.toLowerCase() ) ) ?? undefined,
			set: ( target, key, value ) => {
				this.setAttribute( 'data-' + String( key ).replace( /[A-Z]/g, ( c ) => '-' + c.toLowerCase() ), value );
				return true;
			},
		} );
	}

	get id() {
		return this.getAttribute( 'id' ) || '';
	}
	set id( value ) {
		this.setAttribute( 'id', value );
	}
	get className() {
		return this.getAttribute( 'class' ) || '';
	}
	set className( value ) {
		this.setAttribute( 'class', value );
	}
	get hidden() {
		return this.hasAttribute( 'hidden' );
	}
	set hidden( value ) {
		this.toggleAttribute( 'hidden', Boolean( value ) );
	}
	get disabled() {
		return this.hasAttribute( 'disabled' );
	}
	set disabled( value ) {
		this.toggleAttribute( 'disabled', Boolean( value ) );
	}
	get textContent() {
		return this.ownText + this.children.map( ( child ) => child.textContent ).join( '' );
	}
	set textContent( value ) {
		this.children.forEach( ( child ) => {
			child.parentNode = null;
		} );
		this.children = [];
		this.ownText = String( value ?? '' );
	}

	setAttribute( name, value ) {
		this.attributes.set( name, String( value ) );
	}
	getAttribute( name ) {
		return this.attributes.has( name ) ? this.attributes.get( name ) : null;
	}
	hasAttribute( name ) {
		return this.attributes.has( name );
	}
	removeAttribute( name ) {
		this.attributes.delete( name );
	}
	toggleAttribute( name, force ) {
		const on = undefined === force ? ! this.hasAttribute( name ) : force;
		if ( on ) {
			this.setAttribute( name, '' );
		} else {
			this.removeAttribute( name );
		}
		return on;
	}

	append( ...nodes ) {
		nodes.forEach( ( node ) => {
			if ( 'string' === typeof node ) {
				const text = this.ownerDocument.createElement( '#text' );
				text.ownText = node;
				this.append( text );
				return;
			}
			if ( node.parentNode ) {
				node.remove();
			}
			node.parentNode = this;
			this.children.push( node );
		} );
	}
	appendChild( node ) {
		this.append( node );
		return node;
	}
	replaceChildren( ...nodes ) {
		this.textContent = '';
		this.append( ...nodes );
	}
	remove() {
		if ( ! this.parentNode ) {
			return;
		}
		const siblings = this.parentNode.children;
		siblings.splice( siblings.indexOf( this ), 1 );
		this.parentNode = null;
	}
	// True while the element is in the document, as the DOM's isConnected is.
	get isConnected() {
		return this.ownerDocument.documentElement.contains( this );
	}
	contains( node ) {
		let current = node;
		while ( current ) {
			if ( current === this ) {
				return true;
			}
			current = current.parentNode;
		}
		return false;
	}

	descendants() {
		return this.children.flatMap( ( child ) => [ child, ...child.descendants() ] );
	}
	querySelectorAll( selector ) {
		const alternatives = selector.split( ',' ).map( parseSelector );
		return this.descendants().filter( ( element ) => alternatives.some( ( parsed ) => matches( element, parsed ) ) );
	}
	querySelector( selector ) {
		return this.querySelectorAll( selector )[ 0 ] || null;
	}
	closest( selector ) {
		const parsed = parseSelector( selector );
		let current = this;
		while ( current && current.tagName ) {
			if ( matches( current, parsed ) ) {
				return current;
			}
			current = current.parentNode;
		}
		return null;
	}

	addEventListener( type, listener ) {
		if ( ! this.listeners.has( type ) ) {
			this.listeners.set( type, [] );
		}
		this.listeners.get( type ).push( listener );
	}
	removeEventListener( type, listener ) {
		const list = this.listeners.get( type ) || [];
		this.listeners.set( type, list.filter( ( entry ) => entry !== listener ) );
	}
	dispatchEvent( event ) {
		if ( ! event.target ) {
			event.target = this;
		}
		event.currentTarget = this;
		( this.listeners.get( event.type ) || [] ).slice().forEach( ( listener ) => listener.call( this, event ) );
		if ( event.bubbles && ! event.propagationStopped && this.parentNode ) {
			this.parentNode.dispatchEvent( event );
		}
		return ! event.defaultPrevented;
	}
	click() {
		if ( this.disabled ) {
			return;
		}
		this.dispatchEvent( createEvent( 'click' ) );
	}
	focus() {
		this.ownerDocument.activeElement = this;
	}
	select() {
		this.ownerDocument.selectedElement = this;
	}
}

/**
 * @param {string} type
 * @param {Object} [props] Extra event properties (key, …). Bubbles unless bubbles: false.
 * @return {Object} The event.
 */
export function createEvent( type, props = {} ) {
	return {
		type,
		bubbles: true,
		defaultPrevented: false,
		propagationStopped: false,
		target: null,
		preventDefault() {
			this.defaultPrevented = true;
		},
		stopPropagation() {
			this.propagationStopped = true;
		},
		...props,
	};
}

/**
 * @return {Object} A document with documentElement, body, createElement, getElementById.
 */
export function createFakeDocument() {
	const document = {
		activeElement: null,
		createElement: ( tag ) => new FakeElement( tag, document ),
		getElementById: ( id ) => ( document.documentElement.id === id ? document.documentElement : document.documentElement.querySelector( '#' + id ) ),
	};
	document.documentElement = new FakeElement( 'html', document );
	document.body = new FakeElement( 'body', document );
	document.documentElement.append( document.body );
	return document;
}

/**
 * Builds an element tree from a compact description.
 *
 * @param {Object} document From createFakeDocument().
 * @param {Array}  spec     [ tag, attributes, ...children ], children being specs or strings.
 * @return {FakeElement} The root of the tree.
 */
export function build( document, spec ) {
	const [ tag, attributes = {}, ...children ] = spec;
	const element = document.createElement( tag );
	Object.keys( attributes ).forEach( ( name ) => {
		const value = attributes[ name ];
		if ( true === value ) {
			element.setAttribute( name, '' );
			return;
		}
		element.setAttribute( name, value );
	} );
	children.forEach( ( child ) => {
		element.append( 'string' === typeof child ? child : build( document, child ) );
	} );
	return element;
}

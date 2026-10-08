/**
 * A deterministic stand-in for setTimeout / clearTimeout / Date.now, for the
 * tests next to it. Time only moves when tick() is called, and timers fire in
 * due-time order (ties in creation order), so a test controls exactly what
 * has happened by any point.
 */
export function createFakeClock() {
	let time = 0;
	let nextId = 1;
	const pending = new Map();

	function nextDue( limit ) {
		let best = null;
		pending.forEach( function ( entry, id ) {
			if ( entry.at > limit ) {
				return;
			}
			if ( null === best || entry.at < best.entry.at ) {
				best = { id, entry };
			}
		} );
		return best;
	}

	return {
		setTimeout( callback, ms ) {
			const id = nextId++;
			pending.set( id, { callback, at: time + ms } );
			return id;
		},
		clearTimeout( id ) {
			pending.delete( id );
		},
		now() {
			return time;
		},
		tick( ms ) {
			const target = time + ms;
			let due = nextDue( target );
			while ( null !== due ) {
				time = due.entry.at;
				pending.delete( due.id );
				due.entry.callback();
				due = nextDue( target );
			}
			time = target;
		},
		pendingCount() {
			return pending.size;
		},
	};
}

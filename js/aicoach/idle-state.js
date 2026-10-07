/**
 * Whether Sami shows her idle animation (ENGR-7051, extracted in ENGR-7066).
 *
 * Derived from two facts the flow already tracks and recomputed at every
 * transition, never set ad hoc, so it cannot get stuck on: the Ask Sami panel
 * is open, and no lip-synced answer stream is on screen.
 *
 * @param {Object}  state
 * @param {boolean} state.panelOpen         The Ask Sami panel is open.
 * @param {boolean} state.answerVideoActive A lip-synced answer stream is showing.
 * @return {boolean}
 */
export function isAvatarIdle( { panelOpen, answerVideoActive } ) {
	return Boolean( panelOpen ) && ! answerVideoActive;
}

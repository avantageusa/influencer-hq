/**
 * Whether Sami shows her idle animation (ENGR-7051, extracted in ENGR-7066).
 *
 * Derived from facts the flow already tracks and recomputed at every
 * transition, never set ad hoc, so it cannot get stuck on: the Ask Sami panel
 * is open, or the screen has no clip of its own (ENGR-7099: the clip before it
 * would otherwise sit on its last frame), and in both cases no lip-synced answer
 * stream is on screen.
 *
 * @param {Object}  state
 * @param {boolean} state.panelOpen         The Ask Sami panel is open.
 * @param {boolean} state.answerVideoActive A lip-synced answer stream is showing.
 * @param {boolean} [state.screenWithoutClip] The screen on show has no clip (see isScreenWithoutClip()).
 * @return {boolean}
 */
export function isAvatarIdle( { panelOpen, answerVideoActive, screenWithoutClip } ) {
	return ( Boolean( panelOpen ) || Boolean( screenWithoutClip ) ) && ! answerVideoActive;
}

/**
 * Whether a screen is one nobody speaks on: every screen except the intro (the live
 * stream) and the ones with a pre-rendered clip. The identity form, the channels, the
 * final screen, Time is up and the appointment screens are the ones that qualify.
 *
 * @param {string}   panelKey        The screen's panel key.
 * @param {string[]} clipPanelKeys   Panel keys that play a clip.
 * @return {boolean}
 */
export function isScreenWithoutClip( panelKey, clipPanelKeys ) {
	return 'intro' !== panelKey && ! clipPanelKeys.includes( panelKey );
}

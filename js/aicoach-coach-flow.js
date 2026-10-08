/**
 * AI Coach — page-home-aicoach.php screen sequence (Sami avatar + panels).
 * Config expected on window.AICOACH_SAMI (see inc/anam-proxy.php enqueue).
 */
import { createClient, AnamEvent } from 'https://cdn.jsdelivr.net/npm/@anam-ai/js-sdk@4/+esm';
// ENGR-7066 — timing/state logic that used to live in this file, extracted so it
// can be tested without a browser (js/aicoach/*.test.js). The bare specifiers
// are mapped to versioned URLs by the import map WordPress prints for the
// script modules registered in inc/aicoach-modules.php.
import { createSequenceHold } from '@ihq/aicoach/sequence-hold';
import { createStallWatchdog, once } from '@ihq/aicoach/stall-watchdog';
import { isAvatarIdle } from '@ihq/aicoach/idle-state';
import { SUPPORTED_LOCALES, t, detectLocale } from '@ihq/aicoach/locales';
import {
    SCREENS,
    getEquityScreensForTier,
    getCompetitionScreensForTier,
    COMM_CHANNELS_SCREEN,
    CHANNELS,
    FINAL_SCREEN,
} from '@ihq/aicoach/screens';

/*
 * PO-3062 avatar connection — as of 2026-09-15, the avatar/video is opened via
 * a real Gary Coach API session (inc/gary-proxy.php's /coach/session), not a
 * directly-minted Anam token anymore. Gary's response includes an Anam
 * session_token (say.video.session_token) that the same Anam client SDK
 * connects with — Gary is effectively brokering the Anam session for us now.
 *
 * KNOWN, FLAGGED GAP: Gary's API has no "say this exact text" capability — it
 * generates its own contextual response, it doesn't recite a script we hand
 * it. That means only the FIRST thing the avatar says (its own generated
 * opening line, shown live from Gary's session-open response) is real,
 * Gary-spoken content. Every screen after that (We Believe, equity examples,
 * competition types, etc.) still uses this file's existing fixed SCREENS
 * copy, but displayed as a static caption — same pacing as the
 * no-connection fallback path — rather than spoken/lip-synced, since there's
 * no way to make Gary recite it. This is a known content gap, not a bug:
 * confirmed with Filip Milinkovic (2026-09-15) that connecting the avatar
 * should proceed anyway while Sami's Gary-side prompt/config is separately
 * requested to cover IHQ's registration content ("this isn't a technical
 * blocker, continue connecting the API"). Revisit this file once that
 * config lands — at that point some/all of SCREENS' copy may become real
 * Gary `event`/`message` calls instead of static display.
 *
 * FR-01 + FR-02 + FR-03 — on arrival, the coach's own real greeting plays
 * live, then the rest of the fixed sequence (intro, two "We Believe"
 * screens, then the time-selection pitch — this last one spoken over the
 * existing tier-selection UI rather than a separate screen) is shown as
 * static captions per the gap above. Each screen advances to the next after
 * its own reading dwell or its clip's natural 'ended' event; confirming a
 * time tier (FR-03) is its own early-advance, plus it marks the sequence
 * done and starts elapsed-time tracking. Unlike page-portal-poc.php this is
 * NOT tap-to-start — the AC requires the video to begin on load. A visitor
 * tap/click on the stage no longer skips ahead (FR-02's original "narration
 * timing or visitor tap/click" clause is being removed from that ticket,
 * product decision, PO-3062 comment 2026-09-30) — see FR-19's tap-to-interrupt
 * on the stage's click listener, further down this file.
 */
const FADE_MS = 400;
const FALLBACK_READ_MS = 9000; // per-screen dwell for the static-text fallback (no speech to sync against)
// ENGR-7051 — fallback (audio-only) answers: how long playback may go without
// any progress before it is treated as finished. A recording that stalls
// (network hangs, no error) fires neither 'ended' nor 'error'.
const FALLBACK_AUDIO_STALL_MS = 15000;
const AVATAR_VIDEO_ID = 'aicoach-avatar-video';

// PO-3346 — Ask Sami's openPanel() previously only paused the <video>
// element; the sequence's own dwell/safety timers (waitForReadOrSkip(),
// playPrerenderedClip()'s 60s cap) had no idea the panel was open and kept
// counting down, so the sequence could advance to a later screen — or start
// a new clip — while the panel was still open on top of it. Wrapping those
// timers in something pause-aware, instead of a bare setTimeout(), is what
// lets openPanel()/closePanel() below actually stop and resume them instead
// of just hiding the video that was playing.
//
// Deliberately does NOT cover waitForSpeechOrSkip() (the live intro's own
// wait) — pausing our local video rendering doesn't pause the real,
// server-side Gary/Anam conversation, so there's no "resume from where we
// left off" for that one the way there is for a fixed dwell or a local
// clip's safety cap. That wait can therefore still end while the panel is
// open; ENGR-7051 (review feedback, Dejan Arsić) closes the consequence of
// that rather than the wait itself — see waitWhileSequenceHeld(), which
// runFallback() awaits before starting any screen, so the hand-off to the
// next clip is held until the panel closes.
// ENGR-7066 — the pausable timers and the sequence hold are js/aicoach/sequence-hold.js
// now (with the PR #63 rule that a timer created while the hold is on does not
// start counting, and the ENGR-7051 waiters released by resume). The local
// names below keep every call site in this file as it was.
const BROWSER_TIMERS = {
    setTimeout: function ( callback, ms ) {
        return window.setTimeout( callback, ms );
    },
    clearTimeout: function ( timerId ) {
        window.clearTimeout( timerId );
    },
    now: function () {
        return Date.now();
    },
};
const sequenceHold = createSequenceHold( BROWSER_TIMERS );
const createPausableTimeout = sequenceHold.createPausableTimeout;
const pauseSequenceTimers = sequenceHold.pause;
const resumeSequenceTimers = sequenceHold.resume;
const waitWhileSequenceHeld = sequenceHold.waitWhileHeld;
const setSequenceExternalHold = sequenceHold.setExternalHold;
const waitWhileSequenceExternallyHeld = sequenceHold.waitWhileExternallyHeld;

// FR-17 — trigger the time-remaining check once this proportion of the
// selected tier's total duration has elapsed. The ticket explicitly says the
// real threshold "requires confirmation" — 0.8 (80%) is a placeholder pending
// that, not an approved value. Adjust here once it's confirmed.
const TIME_REMAINING_THRESHOLD_RATIO = 0.8;
const TIER_DURATION_MS = { '2': 2 * 60 * 1000, '5': 5 * 60 * 1000, '10': 10 * 60 * 1000 };

// Walks every data-i18n / data-i18n-attr element and applies the given locale's
// copy (falling back to English per t() in js/aicoach/locales.js). Static text/attributes only —
// spoken captions come from Sami's own responses, not this table.
function applyLocale( locale ) {
    document.querySelectorAll( '[data-i18n]' ).forEach( function ( el ) {
        el.textContent = t( locale, el.getAttribute( 'data-i18n' ) );
    } );
    document.querySelectorAll( '[data-i18n-attr]' ).forEach( function ( el ) {
        el.getAttribute( 'data-i18n-attr' ).split( ',' ).forEach( function ( pair ) {
            const [ attr, key ] = pair.split( ':' );
            el.setAttribute( attr, t( locale, key ) );
        } );
    } );
}

// FR-12 — language auto-detected from browser locale, with English fallback
// (Scenario 22/23); the matching itself is detectLocale() in js/aicoach/locales.js.
function detectInitialLocale() {
    const browserTags = ( navigator.languages && navigator.languages.length )
        ? navigator.languages
        : [ navigator.language || '' ];
    return detectLocale( browserTags );
}

const cfg = window.AICOACH_SAMI || {};
// inc/gary-proxy.php's routes live under the same ihq/v1 namespace FR-07/09's
// identity endpoints already use — no new PHP localization needed for this.
const GARY_SESSION_URL = cfg.identityRestBase + '/coach/session';
const garyCloseUrl = ( sessionId ) => cfg.identityRestBase + '/coach/' + encodeURIComponent( sessionId ) + '/close';
const garyMessageUrl = ( sessionId ) => cfg.identityRestBase + '/coach/' + encodeURIComponent( sessionId ) + '/message';

// PO-3062 pre-rendered clips — SCREENS panel name -> Gary's own segment key
// (see inc/aicoach-prerender.php's ihq_aicoach_prerender_panel_map(), kept
// in sync with this by hand; the two naming schemes predate each other).
// "competition-world"/"competition-community"/"competition-private" are now
// mapped — Gary's old single combined "competitions" segment is
// 'superseded' (confirmed live), replaced by three separate approved
// segments matching this page's three panels one for one, text
// verbatim-identical to COMPETITION_SCREENS above.
const PRERENDERED_PANEL_MAP = {
    'believe-1': 'we_believe_1',
    'believe-2': 'we_believe_2',
    home: 'time_selection',
    'equity-magic': 'magic_johnson',
    'equity-alix': 'alix_earle',
    'equity-bts': 'bts',
    'competition-world': 'world',
    'competition-community': 'community',
    'competition-private': 'private',
};

// FR-16 — single avatar/voice for every language (confirmed with product,
// 2026-09-25), so the only thing that changes per language is which clip
// plays. cfg.prerenderedVideos is now segment_key -> language -> url
// (inc/aicoach-prerender.php's ihq_aicoach_prerender_get_urls()); a language
// with no clip yet for this segment falls back to English, same "missing
// just means not ready" degrade this feature has always had — never no clip
// at all if English exists.
function getPrerenderedUrl( panelKey, locale ) {
    const segmentKey = PRERENDERED_PANEL_MAP[ panelKey ];
    if ( ! segmentKey ) {
        return null;
    }
    const perLanguage = ( cfg.prerenderedVideos || {} )[ segmentKey ] || {};
    // The mirror image of getCaptionScript()'s own check just below: if a
    // translation is edited out of inc/aicoach-segment-translations.php
    // AFTER its clip was already rendered, nothing deletes the now-orphaned
    // clip file or its manifest entry — `wp aicoach prerender` only adds,
    // it doesn't prune. Without this, the orphaned localized clip would
    // still get selected here while getCaptionScript() (correctly, since it
    // has no script text for this locale) falls back to the English
    // caption — a non-English video under an English caption, the same
    // mismatch this ticket fixed, from a third direction. Requiring a
    // script to exist before trusting the clip keeps translated text as the
    // single source of truth both functions key off.
    const perLanguageScripts = ( cfg.segmentScripts || {} )[ segmentKey ] || {};
    const hasScript = 'en' === locale || perLanguageScripts[ locale ];
    return ( hasScript && perLanguage[ locale ] ) || perLanguage.en || null;
}

// NFR-03 — the on-screen caption for a pre-rendered segment, in whatever
// language its clip is actually playing. cfg.segmentScripts (localized from
// inc/aicoach-segment-translations.php) has no 'en' entries — English lives
// in SCREENS/EQUITY_SCREENS's own .script strings — so null here (locale not
// translated for this segment yet) means "use the English .script", the same
// fallback getPrerenderedUrl() already gives the video itself.
function getCaptionScript( panelKey, locale ) {
    const segmentKey = PRERENDERED_PANEL_MAP[ panelKey ];
    if ( ! segmentKey ) {
        return null;
    }
    // A translated script can exist (inc/aicoach-segment-translations.php)
    // before `wp aicoach prerender` has actually rendered its clip on this
    // environment — the two ship independently, translations in code, clips
    // via a separate manual render step. Without this check, that gap
    // reintroduces the exact mismatch this ticket fixed, just flipped: an
    // English clip (getPrerenderedUrl()'s own fallback) under a translated
    // caption. Deriving the caption's language from whichever locale the
    // CLIP actually resolved to — not from whether a translation merely
    // exists — keeps the two locked together.
    const perLanguage = ( cfg.segmentScripts || {} )[ segmentKey ] || {};
    const perLanguageVideos = ( cfg.prerenderedVideos || {} )[ segmentKey ] || {};
    const clipLocale = perLanguageVideos[ locale ] ? locale : 'en';
    return perLanguage[ clipLocale ] || null;
}

// PO-3102 — session persistence (inc/aicoach-progress.php). saveProgress() is
// fire-and-forget as far as any caller is concerned — a failed save must never
// block the coach flow. loadProgress() is only ever awaited once, at start,
// before deciding whether this is a fresh visitor or a resume.
const PROGRESS_URL = cfg.identityRestBase + '/aicoach/progress';

// The backend does a read-merge-write on the saved record (inc/aicoach-
// progress.php's ihq_aicoach_progress_save()), not a real per-field update —
// two saveProgress() calls fired close together (e.g. identityForm's submit
// handler saves the captured identity, then its own showPanel('comm-channels')
// call saves the new stage a moment later) can otherwise reach the server as
// overlapping requests and race: whichever read happens first has its write
// clobbered by the other's merge of now-stale data, silently dropping a field
// that was, from the caller's point of view, already saved. Chaining every
// call onto this promise serializes them client-side — each POST only starts
// once the previous one has settled — which is enough to eliminate the overlap
// without needing a lock on the PHP side for what's a low-frequency, per-visitor
// write.
let progressSaveChain = Promise.resolve();

function saveProgress( partial ) {
    progressSaveChain = progressSaveChain.then( function () {
        return fetch( PROGRESS_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
            body: JSON.stringify( partial ),
        } ).catch( function () {} );
    } );
}

async function loadProgress() {
    try {
        const res = await fetch( PROGRESS_URL, {
            credentials: 'same-origin',
            headers: { 'X-WP-Nonce': cfg.nonce },
        } );
        if ( ! res.ok ) {
            return {};
        }
        const data = await res.json();
        return ( data && typeof data === 'object' ) ? data : {};
    } catch ( error ) {
        return {};
    }
}

// PO-3343 — the shared adjustContentPadding() (template-parts/portal-header.php)
// pads #portal-content to (header bottom + 20px), tuned as breathing room for
// portal pages generally. This page's own chrome is already stripped down to
// logo/globe/volume (PO-3062's "hide chrome" work), so that much clearance
// just pushes the avatar/caption down for no benefit here — mirrors the same
// bottom-measurement logic with a smaller buffer instead, page-scoped so the
// shared function (and every other portal page relying on its own 20px) is
// untouched. Registers its own load/resize listeners after portal-header.php's
// inline script already has (this module script necessarily runs later), so
// it always overrides with a freshly-measured value rather than compounding
// off whatever the previous call left behind.
function tightenAicoachTopPadding() {
    const stickyNav = document.querySelector('.sticky-nav');
    const stickyHeader = document.querySelector('.sticky-header');
    const content = document.getElementById('portal-content');
    if ( ! content ) {
        return;
    }
    const navVisible = stickyNav && 'none' !== getComputedStyle( stickyNav ).display;
    let bottom = 0;
    if ( navVisible ) {
        bottom = stickyNav.getBoundingClientRect().bottom;
    } else if ( stickyHeader ) {
        bottom = stickyHeader.getBoundingClientRect().bottom;
    }
    if ( bottom > 0 ) {
        content.style.setProperty( 'padding-top', ( bottom + 4 ) + 'px', 'important' );
    }
}
window.addEventListener( 'load', tightenAicoachTopPadding );
let aicoachPaddingResizeTimer;
window.addEventListener( 'resize', function () {
    clearTimeout( aicoachPaddingResizeTimer );
    aicoachPaddingResizeTimer = window.setTimeout( tightenAicoachTopPadding, 100 );
} );

const stage = document.getElementById('aicoach-stage');
const avatarWrap = document.getElementById('aicoach-avatar-wrap');
const video = document.getElementById('aicoach-avatar-video');
// Reuses the site-wide header volume control (template-parts/portal-header.php)
// instead of a second, dedicated mute button overlaid on the avatar itself —
// that button currently does nothing but open its own slider on every other
// page, so wiring it to this page's avatar audio is a natural fit rather
// than duplicating a control the visitor already sees top-right.
const headerVolumeBtn = document.getElementById('headerVolumeBtn');
const headerVolumeSlider = document.getElementById('headerVolumeSlider');

function getCaptionEl( panelKey ) {
    return stage.querySelector( '.aicoach-panel[data-panel="' + panelKey + '"] .aicoach-caption' );
}

if ( stage && avatarWrap ) {
    const tiers = stage.querySelectorAll( '.aicoach-tier' );
    let isAnimating = false;

    // FR-13 — language selector, injected into the shared header's own nav row
    // rather than editing template-parts/portal-header.php directly. That file
    // already has a .header-lang-wrap element, but it belongs to a different,
    // unrelated feature (the site-wide ElevenLabs concierge widget — see its git
    // history) with its own mismatched language list; reusing or editing it risks
    // breaking that other feature. This script only ever runs on this one page
    // (see ihq_aicoach_enqueue_coach_flow()'s is_page_template check), so injecting
    // here is safe without any extra page check.
    let currentLocale = detectInitialLocale();
    let askSamiWrap = null; // set once buildAskSami() runs below; selectLocale() toggles its visibility
    let askSamiBtn = null;
    let isAnswerPending = function () {
        return false;
    }; // set by buildAskSami(): a question is in flight or its answer is still playing, even with the panel closed
    let closeAskSamiPanel = null; // set once buildAskSami() runs below; selectLocale() calls this before hiding the panel
    let toggleAskSamiPanel = null; // set once buildAskSami() runs below; the stage's tap-to-interrupt listener calls this
    let applyResumedQaHistory = null; // set once buildAskSami() runs below; init() calls this with a resumed visitor's saved Q&A pairs

    function selectLocale( locale ) {
        if ( locale === currentLocale ) {
            return;
        }
        currentLocale = locale;
        applyLocale( locale );
        saveProgress( { language: locale } ); // PO-3102
        document.querySelectorAll( '.aicoach-lang-option' ).forEach( function ( opt ) {
            const isCurrent = opt.dataset.locale === locale;
            opt.classList.toggle( 'is-current', isCurrent );
            if ( isCurrent ) {
                opt.setAttribute( 'aria-current', 'true' );
            } else {
                opt.removeAttribute( 'aria-current' );
            }
        } );
        // FR-19/PO-3330 — Ask Sami's generated answers are English-only (Gary,
        // 2026-09-28); hide the entry point rather than let a visitor ask a
        // question in a language that can only ever get his fixed fallback line.
        if ( askSamiWrap ) {
            // PR #63 (Dejan Arsić) — the header language selector stays
            // clickable while Ask Sami's panel is open, same as the timer
            // race CodeRabbit found. Hiding the panel out from under itself
            // without closing it first left it stuck "open": the sequence
            // timers it paused were never resumed (closePanel() is the only
            // thing that resumes them) and the main video was never resumed
            // either, so a caption screen would stop auto-advancing and the
            // avatar would sit frozen until a manual tap happened to skip it.
            if ( askSamiWrap.classList.contains( 'is-open' ) && 'en' !== locale ) {
                closeAskSamiPanel();
            }
            askSamiWrap.hidden = 'en' !== locale;
        }
        restartCurrentClipForLocale( locale ); // FR-14/PO-3105
        // NOTE — scope boundary: the line above restarts a currently-playing
        // PRE-RENDERED clip (PO-3107) in the new language. It does NOT reconnect
        // the LIVE avatar/voice to a fresh Gary session (Scenario 25's "avatar
        // and voice switch" clause, for the one screen — "intro" — that's a real
        // Gary session rather than a pre-rendered clip). That would mean closing
        // the open session, tearing down the connected Anam client, and opening
        // a new one — not done here, tracked as the next increment on top of
        // this rather than folded in silently.
    }

    // FR-14/PO-3105 — "the current screen's avatar video restarts from the
    // beginning in the newly selected language". Delegates to
    // playPrerenderedClip()'s own activeClipRestart (set for whichever clip is
    // currently in flight) so the SAME pending promise runFallback() is already
    // awaiting for this screen keeps working — including its play()-rejection
    // handling, which matters here: without it, an in-flight play() promise
    // from the clip THIS call is replacing can still reject a moment later and
    // wrongly resolve the screen as "failed", skipping straight to the next
    // one instead of playing the just-restarted clip (confirmed live: without
    // playPrerenderedClip()'s generation guard, a same-screen language switch
    // silently lost the restarted clip to its predecessor's stale rejection).
    // No need to touch sequenceIndex or the sequence loop at all. A no-op when
    // the current screen has no pre-rendered clip (a
    // caption-only or competition-name screen), when nothing is actually in
    // flight, or when video.srcObject is set (the live Gary/Anam WebRTC stream
    // for the "intro" screen — reconnecting that is a separate, bigger piece
    // of work, see selectLocale()'s own note above).
    // Returns true when it restarted the clip, which then plays; false when there
    // was nothing to restart (or the restart was deferred, see below), so the caller
    // knows the clip it paused is still paused (PO-3109, CodeRabbit).
    function restartCurrentClipForLocale( locale ) {
        // PO-3109 (review, Dejan Arsić) — restarting plays the clip, which must not
        // happen under the Time is up screen. Remember it; closeTimeUp() restarts
        // the clip in the new language once the previous screen is back.
        if ( timeUpOpen ) {
            timeUpClipRestartPending = true;
            return false;
        }
        const screen = SCREENS[ sequenceIndex ];
        if ( ! screen || video.srcObject || ! activeClipRestart ) {
            return false;
        }
        const newUrl = getPrerenderedUrl( screen.panel, locale );
        // getPrerenderedUrl() falls back to the English clip when the newly
        // selected locale has none of its own for this segment (same "missing
        // just means not ready" degrade as everywhere else) — if that fallback
        // resolves to the EXACT clip already loaded (e.g. switching between two
        // locales that both lack this segment), restarting would just replay
        // the same English clip from 0 for no reason. Comparing against the
        // resolved URL rather than "does this locale have its own entry" also
        // correctly still restarts when the OLD locale had a real clip playing
        // and the new one falls back to English — that genuinely is a
        // different source, not a no-op.
        if ( ! newUrl || newUrl === video.currentSrc ) {
            return false;
        }
        activeClipRestart( newUrl );
        // PO-3343 — believe-1's clip restarting from 0 means Sami is about to
        // re-say the opening line too, in the new language — re-run the same
        // icons-first intro rather than just patching in the (still-English,
        // still-opening-included) caption text below.
        if ( 'believe-1' === screen.panel ) {
            startBelieveOneIntro();
            return true;
        }
        // NFR-03 — keep the on-screen caption matching whatever the restarted
        // clip is actually saying, same as the initial-load path in
        // runFallback() above.
        const captionEl = getCaptionEl( screen.panel );
        if ( captionEl ) {
            captionEl.textContent = getCaptionScript( screen.panel, locale ) || screen.script;
        }
        return true;
    }

    ( function buildLanguageSelector() {
        const headerRow = document.querySelector( '.desktop-header-left-items' );
        if ( ! headerRow ) {
            return;
        }

        const wrap = document.createElement( 'div' );
        wrap.className = 'aicoach-lang-wrap';

        const btn = document.createElement( 'button' );
        btn.type = 'button';
        btn.className = 'aicoach-lang-btn';
        btn.id = 'aicoachLangBtn';
        btn.setAttribute( 'aria-label', 'Select language' );
        btn.setAttribute( 'aria-haspopup', 'true' );
        btn.setAttribute( 'aria-expanded', 'false' );
        btn.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#E6CFA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>';

        const dropdown = document.createElement( 'div' );
        dropdown.className = 'aicoach-lang-dropdown';
        dropdown.id = 'aicoachLangDropdown';

        SUPPORTED_LOCALES.forEach( function ( loc ) {
            const opt = document.createElement( 'button' );
            opt.type = 'button';
            opt.className = 'aicoach-lang-option';
            opt.dataset.locale = loc.code;
            opt.textContent = loc.nativeLabel;
            opt.lang = loc.code;
            if ( loc.code === currentLocale ) {
                opt.classList.add( 'is-current' );
                opt.setAttribute( 'aria-current', 'true' );
            }
            dropdown.appendChild( opt );
        } );

        wrap.appendChild( btn );
        wrap.appendChild( dropdown );
        headerRow.appendChild( wrap );

        btn.addEventListener( 'click', function ( event ) {
            event.stopPropagation();
            // Dejan Arsic (PR #55 review) — this stopPropagation() means a
            // click here never reaches document's unmuteOnFirstInteraction()
            // listener below. If opening (or picking from, see the dropdown
            // handler) this menu is the visitor's very first interaction with
            // the page, audio would otherwise stay muted indefinitely with no
            // other click ever arriving to satisfy the browser's autoplay
            // gate. Calling it explicitly here counts this click too — it's a
            // no-op once already unmuted.
            unmuteOnFirstInteraction();
            const isOpen = wrap.classList.toggle( 'is-open' );
            btn.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
        } );

        document.addEventListener( 'click', function () {
            wrap.classList.remove( 'is-open' );
            btn.setAttribute( 'aria-expanded', 'false' );
        } );

        dropdown.addEventListener( 'click', function ( event ) {
            const option = event.target.closest( '.aicoach-lang-option' );
            if ( ! option ) {
                return;
            }
            event.stopPropagation();
            unmuteOnFirstInteraction(); // see the same note on the open/close button above
            selectLocale( option.dataset.locale );
            wrap.classList.remove( 'is-open' );
            btn.setAttribute( 'aria-expanded', 'false' );
        } );
    }() );

    // FR-19/PO-3330 — "Ask Sami": lets a visitor interrupt the fixed sequence
    // and ask a question by voice, answered by Gary's /message endpoint
    // (inc/gary-proxy.php's ihq_coach_handle_message()), reusing this visit's
    // already-open garySessionId (stays open for the whole flow — see its
    // declaration below). Confirmed with Gary 2026-09-28: generated answers
    // are English-only for now, so this entry point is hidden outside the
    // 'en' locale (selectLocale() above) rather than shown for a feature that
    // would just return his fixed fallback line.
    //
    // Voice IN, text answer OUT. The question itself never touches OUR
    // backend as audio — the browser's own SpeechRecognition transcribes it
    // to text, which then goes through the exact same /message call typing
    // would have; inc/gary-proxy.php's ihq_coach_handle_message() only ever
    // accepted a `text` field; there's no server-side speech-to-text route
    // to build here. That transcription is NOT necessarily on-device,
    // though — browser speech recognition commonly sends the raw audio to
    // the browser vendor's own remote service (e.g. Chrome's default
    // SpeechRecognition does this unless a newer, explicitly-requested
    // on-device model is used, which this code doesn't request). Flagged in
    // review (CodeRabbit, PR #68) — worth a privacy-policy check with
    // product/legal before treating this as settled, tracked as a separate
    // open question rather than assumed here. An earlier typed-question
    // build was explicitly
    // rejected in review (2026-09-29): "ne treba input polje da bude, samo
    // glasom" (no input field, voice only), which also matches PO-3330's
    // own AC ("ask by voice", decline-mic fallback returns to the flow
    // rather than offering to type instead).
    //
    // The ANSWER stays text-only for now — deliberately does NOT play back
    // say.audio or reconnect the avatar for say.video. Confirmed live
    // (2026-09-28): say.audio is not a playable URL but an object ({ id,
    // url, format, expires_at, ... }) whose "url" (e.g.
    // "/coach/v1/audio/{id}") is a path on Gary's own API host, HMAC-signed
    // the same way every other ihq_coach_request() call is — the visitor's
    // browser can't fetch it directly, and nothing in inc/gary-proxy.php
    // proxies it live today (ihq_coach_download() is a build-time,
    // stream-to-disk tool used by the prerender scripts, not a REST route).
    // Playing the answer back through Sami (live avatar or plain audio)
    // needs that proxy route built first — tracked as a real follow-up, not
    // silently attempted here. The main avatar video is still paused while
    // this panel is open, independent of that — purely so the visitor isn't
    // mid-question while the sequence advances underneath them.
    ( function buildAskSami() {
        const SpeechRecognitionCtor = window.SpeechRecognition || window.webkitSpeechRecognition;

        const wrap = document.createElement( 'div' );
        wrap.className = 'aicoach-ask-wrap';
        wrap.hidden = 'en' !== currentLocale;

        const btn = document.createElement( 'button' );
        btn.type = 'button';
        btn.className = 'aicoach-ask-btn';
        btn.disabled = true; // enabled once start() below has a real garySessionId
        btn.setAttribute( 'aria-haspopup', 'true' );
        btn.setAttribute( 'aria-expanded', 'false' );
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg><span>Ask Sami</span>';

        const panel = document.createElement( 'div' );
        panel.className = 'aicoach-ask-panel';

        const closeBtn = document.createElement( 'button' );
        closeBtn.type = 'button';
        closeBtn.className = 'aicoach-ask-close';
        closeBtn.setAttribute( 'aria-label', 'Close' );
        closeBtn.textContent = '×';

        // PO-3330 — "previous exchanges are available" on a return visit
        // (product decision, Ivan Vladić, 2026-10-01: read-only display only,
        // not reopening a live Gary session — see applyResumedQaHistory()
        // below). Sits above the live answerEl, which keeps showing just the
        // most recent in-flight exchange exactly as it already did.
        const historyEl = document.createElement( 'div' );
        historyEl.className = 'aicoach-ask-history';
        historyEl.hidden = true;

        const answerEl = document.createElement( 'p' );
        answerEl.className = 'aicoach-ask-answer';
        answerEl.setAttribute( 'aria-live', 'polite' );

        const voiceRow = document.createElement( 'div' );
        voiceRow.className = 'aicoach-ask-voice';

        const micBtn = document.createElement( 'button' );
        micBtn.type = 'button';
        micBtn.className = 'aicoach-ask-mic';
        micBtn.setAttribute( 'aria-label', 'Ask Sami by voice' );
        micBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line><line x1="8" y1="23" x2="16" y2="23"></line></svg>';

        // aria-live so screen readers announce state changes without needing
        // focus to move — same reasoning as answerEl above.
        const statusEl = document.createElement( 'p' );
        statusEl.className = 'aicoach-ask-status';
        statusEl.setAttribute( 'aria-live', 'polite' );

        const errorEl = document.createElement( 'p' );
        errorEl.className = 'aicoach-ask-error';
        errorEl.setAttribute( 'role', 'alert' );

        voiceRow.appendChild( micBtn );
        voiceRow.appendChild( statusEl );
        panel.appendChild( closeBtn );
        panel.appendChild( historyEl );
        panel.appendChild( answerEl );
        panel.appendChild( voiceRow );
        panel.appendChild( errorEl );
        wrap.appendChild( btn );
        wrap.appendChild( panel );
        // Sibling of .aicoach-stage, not inside it — .aicoach-stage's own click
        // listener (FR-19 tap-to-interrupt) would otherwise treat every click
        // in here as its own separate toggle attempt on top of this panel's.
        avatarWrap.insertAdjacentElement( 'afterend', wrap );

        askSamiWrap = wrap;
        askSamiBtn = btn;

        let wasPlaying = false; // the main avatar video's state before the panel paused it, restored on close
        let recognition = null; // the in-flight SpeechRecognition instance, if any — see startListening()/stopListening()
        // PO-3346 — spoken video answers (Gary confirmed video/lip-sync is
        // available on this surface, 2026-09-30). qaClient is reused across
        // questions in the same visit rather than reconnected each time
        // (Gary's own guidance — every video session ties up an avatar
        // seat); it clears itself via CONNECTION_CLOSED whenever Anam ends
        // it (the 300s cap, or anything else), so the next question just
        // requests a fresh one. qaAnswering lets closePanel() and
        // speakAnswerOrFallback() coordinate who restores the shared video
        // element instead of racing each other — see both below.
        let qaClient = null;
        let qaClientReady = false;
        // PO-3109 (review, Dejan Arsić) — questions sent whose answer has not arrived
        // yet. Closing the panel does not cancel one (only reopening it does), so
        // its answer can still land later, with qaAnswering false until it does.
        let qaQuestionsInFlight = 0;
        let qaAnswering = false;
        // ENGR-7051 — true only while a lip-synced answer stream is what the
        // shared <video> is actually showing (not merely while an answer is
        // being fetched or played as audio). With the panel being open, this
        // decides whether Sami shows her idle animation — see syncAvatarIdle().
        let qaVideoActive = false;
        // PR #73 (review feedback, Dejan Arsić) — the live MediaStream behind
        // a ready qaClient, captured once on first connect. Anam 4.27.1's
        // streamToVideoElement() throws 'Already streaming' on a second call
        // for the same client (confirmed against the SDK source directly —
        // AnamClient.ts's _isStreaming only resets inside stopStreaming(),
        // which a reused client never gets here), so a follow-up question
        // can't call it again to reattach. Reassigning this cached stream
        // straight onto video.srcObject is a plain DOM operation that never
        // touches Anam's internal state, so it can't hit that guard.
        let qaMediaStream = null;
        // PO-3330 — this visit's Q&A pairs, newest last: whatever resumed
        // from a prior visit (see applyResumedQaHistory()) plus every
        // question successfully answered this session (see askQuestion()).
        // Capped the same way channels/etc already cap what gets saved —
        // matches IHQ_AICOACH_PROGRESS_MAX_QA_HISTORY server-side.
        const QA_HISTORY_MAX = 20;
        let qaHistory = [];

        // PR #76 (review feedback, Dejan Arsić) — takes the entries to show
        // explicitly rather than always reading the full qaHistory: the
        // just-answered pair is already visible in answerEl below, so
        // askQuestion() passes everything EXCEPT that one to avoid showing
        // it twice in the same open panel. A resume has no "current answer"
        // live in answerEl yet, so applyResumedQaHistory() passes the full
        // array.
        function renderQaHistory( entries ) {
            historyEl.innerHTML = '';
            entries.forEach( function ( entry ) {
                const item = document.createElement( 'div' );
                item.className = 'aicoach-ask-history-item';
                const q = document.createElement( 'p' );
                q.className = 'aicoach-ask-history-q';
                q.textContent = entry.question;
                const a = document.createElement( 'p' );
                a.className = 'aicoach-ask-history-a';
                a.textContent = entry.answer;
                item.appendChild( q );
                item.appendChild( a );
                historyEl.appendChild( item );
            } );
            historyEl.hidden = 0 === entries.length;
        }

        // PO-3330 — called from init() (below) with a resumed visitor's
        // saved qaHistory, the same "apply saved state the same way a fresh
        // flow would have set it" pattern as applyResumedIdentity()/
        // applyResumedChannels(). Deliberately does NOT touch garySessionId/
        // qaClientReady — product decided (2026-10-01) that a resumed
        // visitor sees past exchanges but doesn't get a live session
        // re-opened for them.
        //
        // Review feedback (PR #76, Dejan Arsić) — askSamiBtn.disabled is
        // otherwise only ever cleared inside start(), which NONE of
        // PO-3102's resume paths call (confirmed by tracing init(): only a
        // genuinely fresh arrival reaches start(); every resume branch —
        // mid-sequence via runFallback(false), or post-sequence via
        // showPanel(stageKey) — skips it entirely, and nothing later in
        // either path ever opens a session either). Without this, the
        // button — and so the whole panel, history included — would stay
        // permanently unopenable for every resumed visitor, which is
        // exactly the audience this feature exists for. Opening the panel
        // itself needs no live session (it only shows past exchanges);
        // asking a NEW question still correctly does nothing without one —
        // askQuestion()'s existing `!garySessionId` guard already no-ops
        // that silently, the same degrade this app already relies on
        // elsewhere.
        applyResumedQaHistory = function ( history ) {
            if ( ! Array.isArray( history ) || ! history.length ) {
                return;
            }
            qaHistory = history.slice( -QA_HISTORY_MAX );
            renderQaHistory( qaHistory );
            askSamiBtn.disabled = false;
        };
        // PR #68 (CodeRabbit) — bumped by every startListening() call and
        // compared against inside askQuestion()'s response handling, so a
        // /message request left over from a PRIOR listen cycle (e.g. the
        // panel was closed and reopened, or the mic was tapped again, while
        // that request was still in flight) can't write its answer/error
        // into a panel that's since moved on to a newer question.
        let askGeneration = 0;

        // AC — "microphone permission is requested at that moment and not
        // before": recognition.start() is what actually triggers the
        // browser's own permission prompt (the first time; it's remembered
        // by the browser for every call after that on this origin, so later
        // questions never re-prompt — nothing here needs to track that
        // ourselves). A fresh SpeechRecognition instance every call because
        // some browsers throw if you .start() one that's already fired
        // 'end' once, rather than letting it be reused.
        function startListening() {
            // PR #68 (CodeRabbit) — a recognition instance is already active;
            // starting a second one on top of it (e.g. a fast double-click on
            // the mic) would orphan the first, and its OWN onend could later
            // null out the reference to this newer one, breaking
            // stopListening()'s ability to abort it. Ignore instead.
            if ( recognition ) {
                return;
            }
            askGeneration++;
            // PR #68 (CodeRabbit) — a prior askQuestion() may still be
            // in-flight and disabled this on its way out; its own finally
            // block won't re-enable it (the generation check above is
            // exactly what makes it stale), so this new cycle has to do it
            // itself or the mic could stay stuck disabled indefinitely.
            micBtn.disabled = false;
            errorEl.textContent = '';
            answerEl.textContent = '';
            if ( ! SpeechRecognitionCtor ) {
                // AC — browser blocks it entirely: told plainly, returned to
                // the flow (the existing close button already does that).
                statusEl.textContent = "Questions need the microphone for now — this browser doesn't support voice input.";
                return;
            }
            const instance = new SpeechRecognitionCtor();
            recognition = instance;
            instance.lang = 'en-US'; // Ask Sami is English-only for now — see the 'en' gate above
            instance.continuous = false;
            instance.interimResults = false;
            instance.maxAlternatives = 1;
            instance.onstart = function () {
                micBtn.classList.add( 'is-listening' );
                statusEl.textContent = 'Listening…';
            };
            instance.onend = function () {
                micBtn.classList.remove( 'is-listening' );
                // PR #68 (CodeRabbit) — only clear the shared reference if it
                // still points at THIS instance. stopListening() (an explicit
                // close) already nulls it and aborts; abort() still fires
                // 'end' asynchronously afterward, and if the panel was
                // reopened (a new instance started) before that stale 'end'
                // arrives, it must not null out the new one out from under it.
                if ( recognition === instance ) {
                    recognition = null;
                }
            };
            instance.onresult = function ( event ) {
                const transcript = event.results[ 0 ]?.[ 0 ]?.transcript?.trim();
                if ( transcript ) {
                    askQuestion( transcript, askGeneration );
                }
            };
            // PR #68 (CodeRabbit) — an unrecognised utterance is its own
            // 'nomatch' event, not an onerror with error 'no-match' (that
            // string is never an actual SpeechRecognitionErrorEvent.error
            // value per spec) — the old no-match branch below never fired.
            instance.onnomatch = function () {
                statusEl.textContent = "Sorry, I didn't catch that — try again.";
            };
            instance.onerror = function ( event ) {
                if ( 'not-allowed' === event.error || 'service-not-allowed' === event.error ) {
                    // AC — permission declined: told plainly, returned to the flow.
                    statusEl.textContent = 'Questions need the microphone for now.';
                } else if ( 'no-speech' === event.error ) {
                    // AC — paused and said nothing: no answer, nothing
                    // recorded; just let the visitor tap the mic again or
                    // close to continue, same as before they opened this.
                    statusEl.textContent = "Didn't hear anything — tap the mic to try again.";
                } else {
                    statusEl.textContent = 'Something went wrong — tap the mic to try again.';
                }
            };
            try {
                instance.start();
            } catch ( error ) {
                // start() threw synchronously — this instance never actually
                // started (no 'end' coming to clear it), so clear it here or
                // every future startListening() call would see a truthy
                // recognition and silently no-op forever.
                if ( recognition === instance ) {
                    recognition = null;
                }
            }
        }

        function stopListening() {
            if ( recognition ) {
                // PR #68 (CodeRabbit) — abort() still fires 'error' (aborted)
                // and 'end' on THIS instance asynchronously afterward. The
                // recognition === instance identity check inside onend
                // (above) only protects the shared `recognition` reference;
                // it doesn't stop those late events from touching the UI —
                // an 'aborted' onerror or a late onend could still overwrite
                // a NEWER instance's "Listening…" status or wrongly clear
                // its is-listening class if the panel is reopened before
                // they arrive. Detach every handler so a dead instance stays
                // dead.
                recognition.onresult = null;
                recognition.onerror = null;
                recognition.onnomatch = null;
                recognition.onstart = null;
                recognition.onend = null;
                recognition.abort();
                recognition = null;
                micBtn.classList.remove( 'is-listening' );
            }
        }

        // PO-3346 — speaks the answer through the same avatar/video element
        // visitors already see (AC: "same avatar and voice... no perceptible
        // change of speaker"), when Gary provides one. Falls back to Gary's
        // own say.audio when video isn't available (busy avatar seats, etc.
        // — meta.reason explains why) so the visitor still hears Sami, and
        // to the text already shown (by the caller) if neither is present.
        // Never plays say.audio.url alongside video — Gary's own guidance
        // (2026-09-30): it's a separate recording, won't line up with the
        // lips.
        // PR #73 (CodeRabbit) — plays a fallback (non-lip-synced) answer
        // recording and keeps qaAnswering true for its whole duration, not
        // just fire-and-forget: without that, closePanel() had no way to
        // know an answer was still audibly playing and would resume the
        // original clip's audio right on top of it, and the mic would
        // re-enable while the recording was still going.
        function playFallbackAudio( url ) {
            return new Promise( function ( resolve ) {
                qaAnswering = true;
                const audio = new Audio( url );
                // ENGR-7051 (review feedback) — a recording that stalls without
                // erroring fires neither 'ended' nor 'error', so this promise
                // never settled: qaAnswering stayed true, closePanel() kept
                // deferring resumeSequenceTimers() to a finish() that never
                // came, the mic stayed disabled and the sequence stayed held
                // until a reload. Every sign of progress ('timeupdate') re-arms
                // a watchdog; if it fires, playback is abandoned. The watchdog
                // and the call-once guard are js/aicoach/stall-watchdog.js.
                const stallWatchdog = createStallWatchdog( {
                    stallMs: FALLBACK_AUDIO_STALL_MS,
                    onStall: function () {
                        finish();
                    },
                    setTimeout: BROWSER_TIMERS.setTimeout,
                    clearTimeout: BROWSER_TIMERS.clearTimeout,
                } );
                // finish() can be reached more than once: the watchdog fires,
                // then the pause() below rejects the pending play() promise
                // (an AbortError) whose .catch(finish) calls it again.
                const finish = once( function () {
                    stallWatchdog.disarm();
                    audio.removeEventListener( 'ended', finish );
                    audio.removeEventListener( 'error', finish );
                    audio.removeEventListener( 'timeupdate', stallWatchdog.arm );
                    // A stalled recording must not start playing later, over
                    // whatever the sequence has resumed to by then.
                    audio.pause();
                    qaAnswering = false;
                    // Same deferred-resume reasoning as
                    // speakAnswerOrFallback()'s own finally block below — the
                    // panel may have closed while this was still playing, in
                    // which case closePanel() left both the sequence timers
                    // and the video resume to us.
                    if ( ! wrap.classList.contains( 'is-open' ) ) {
                        resumeSequenceTimers();
                        if ( wasPlaying ) {
                            video.play().catch( function () {} );
                        }
                    }
                    resolve();
                } );
                audio.addEventListener( 'ended', finish );
                audio.addEventListener( 'error', finish );
                audio.addEventListener( 'timeupdate', stallWatchdog.arm );
                stallWatchdog.arm(); // also covers a stall before the first byte
                audio.play().catch( finish );
            } );
        }

        // PR #73 (CodeRabbit) — stops a client's stream and detaches its
        // persistent CONNECTION_CLOSED listener before it's discarded, so a
        // failed/abandoned connection doesn't keep an avatar seat tied up
        // and can't touch shared state again later.
        function teardownQaClient( client, persistentCloseHandler ) {
            if ( ! client ) {
                return;
            }
            if ( persistentCloseHandler ) {
                client.removeListener( AnamEvent.CONNECTION_CLOSED, persistentCloseHandler );
            }
            if ( 'function' === typeof client.stopStreaming ) {
                try {
                    client.stopStreaming();
                } catch ( stopError ) {
                    console.warn( '[aicoach] qaClient.stopStreaming() failed:', stopError );
                }
            }
        }

        async function speakAnswerOrFallback( data ) {
            // PR #73 (CodeRabbit) — reopening the panel mid-answer
            // (startListening() re-enables the mic and bumps askGeneration)
            // lets a second question reach this function while an earlier
            // one is still playing. Both calls would otherwise race on the
            // same video element/qaAnswering flag; refuse the overlap
            // instead — the visitor can ask again once the current answer
            // finishes.
            if ( qaAnswering ) {
                return;
            }
            // PO-3109 — never speak an answer under the Time is up screen. The time
            // check waits for a question in flight, so this is only a safety net.
            if ( timeUpOpen ) {
                return;
            }
            // A fresh video envelope, OR an already-connected client from an
            // earlier answer this visit (a follow-up question deliberately
            // requests want:['text'] only — see askQuestion() — so
            // data.say.video is null here even though video is exactly what
            // we're about to reuse).
            if ( ! data.say?.video && ! qaClientReady ) {
                if ( data.say?.audio?.url ) {
                    await playFallbackAudio( data.say.audio.url );
                }
                return;
            }
            // Captured locally (not a shared module variable) so a future
            // change can't reintroduce the same cross-call race the
            // qaAnswering guard above already closes off.
            const qaVideoSnapshot = { status: avatarWrap.dataset.status, src: video.src, currentTime: video.currentTime };
            qaAnswering = true;
            // Captured once and used consistently below instead of
            // re-reading the shared qaClient throughout — a persistent
            // CONNECTION_CLOSED listener can null qaClient out from under
            // this call while it's still in flight (see persistentCloseHandler),
            // and every operation here (talk, wait, teardown) must stay
            // pinned to the exact instance THIS call started with.
            let client = qaClient; // null on first connect, the reused/ready client otherwise
            let persistentCloseHandler = null;
            try {
                if ( ! qaClientReady ) {
                    client = createClient( data.say.video.session_token, { disableInputAudio: true } );
                    qaClient = client;
                    persistentCloseHandler = function () {
                        // Only clear shared state if this instance is still
                        // the current one — a stale instance's own late
                        // CONNECTION_CLOSED must not clobber a newer
                        // client's state (same fix already applied to the
                        // SpeechRecognition lifecycle, PR #68).
                        if ( qaClient === client ) {
                            qaClientReady = false;
                            qaClient = null;
                            qaMediaStream = null;
                            // ENGR-7051 (CodeRabbit) — the stream behind
                            // qaVideoActive is gone with the client; without this
                            // an open panel kept the dead stream's last frame
                            // on screen instead of the idle portrait.
                            qaVideoActive = false;
                            syncAvatarIdle();
                        }
                    };
                    client.addListener( AnamEvent.CONNECTION_CLOSED, persistentCloseHandler );
                    await new Promise( function ( resolve, reject ) {
                        let settled = false;
                        let timeoutId;
                        const onStarted = function () {
                            if ( settled ) {
                                return;
                            }
                            settled = true;
                            client.removeListener( AnamEvent.VIDEO_PLAY_STARTED, onStarted );
                            client.removeListener( AnamEvent.CONNECTION_CLOSED, onClosed );
                            window.clearTimeout( timeoutId );
                            qaClientReady = true;
                            resolve();
                        };
                        const onClosed = function () {
                            if ( settled ) {
                                return;
                            }
                            settled = true;
                            client.removeListener( AnamEvent.VIDEO_PLAY_STARTED, onStarted );
                            client.removeListener( AnamEvent.CONNECTION_CLOSED, onClosed );
                            window.clearTimeout( timeoutId );
                            reject( new Error( 'Anam connection closed before video playback started.' ) );
                        };
                        client.addListener( AnamEvent.VIDEO_PLAY_STARTED, onStarted );
                        client.addListener( AnamEvent.CONNECTION_CLOSED, onClosed );
                        // streamToVideoElement() resolves once it STARTS the
                        // connection, not once video is actually live — a
                        // connection that fails after that point (but before
                        // VIDEO_PLAY_STARTED) would otherwise leave this
                        // promise pending forever, with askQuestion() stuck
                        // and the mic permanently disabled. Same 25s cap as
                        // waitForSpeechOrSkip()'s own safety net.
                        timeoutId = window.setTimeout( onClosed, 25000 );
                        client.streamToVideoElement( AVATAR_VIDEO_ID ).catch( reject );
                    } );
                    // streamToVideoElement() has already set this internally
                    // by the time VIDEO_PLAY_STARTED fires (confirmed against
                    // the SDK source) — cache it now, while we still know
                    // video.srcObject is exactly this client's live stream,
                    // so a later follow-up question can reattach it directly.
                    qaMediaStream = video.srcObject;
                } else {
                    // Reusing an already-connected client for a follow-up
                    // question. The previous answer's own cleanup below
                    // detached the stream from the video element (so the
                    // interrupted clip could be restored), but calling
                    // streamToVideoElement() again to reattach it throws
                    // 'Already streaming' — reassign the cached MediaStream
                    // directly instead.
                    if ( ! qaMediaStream ) {
                        throw new Error( 'qaClientReady but no cached qaMediaStream to reattach.' );
                    }
                    video.srcObject = qaMediaStream;
                }
                avatarWrap.dataset.status = 'live'; // reveals .aicoach-avatar-video over the static portrait, same CSS state as any other playing video
                qaVideoActive = true; // the answer stream is on screen now — drop the idle animation so it shows instead
                syncAvatarIdle();
                await client.talk( data.say.text );
                // ENGR-7051 (review feedback) — waitForQaSpeechOrSkip() can only
                // hear a CONNECTION_CLOSED that arrives after it is called. If the
                // connection dropped while talk() was still in flight, persistentCloseHandler
                // has already cleared qaClient and nothing would ever wake the
                // wait, so it would sit out the full 25 s cap on a dead stream.
                // Fail into the catch below (fallback audio) straight away. No
                // await between this check and the call, so there is no window.
                if ( qaClient !== client ) {
                    throw new Error( 'Anam connection closed while the answer was being sent.' );
                }
                await waitForQaSpeechOrSkip( client, 25000 );
            } catch ( error ) {
                console.warn( '[aicoach] Ask Sami video answer failed, falling back to audio/text:', error );
                // ENGR-7051 (CodeRabbit) — the answer stream is gone as of here
                // (talk() threw, or the connection dropped mid-speech), but
                // the finally block below is what normally clears
                // qaVideoActive, and it runs only AFTER the fallback audio
                // finishes. Without this she would sit on the dead stream's
                // last frame for that whole time instead of idling.
                qaVideoActive = false;
                syncAvatarIdle();
                teardownQaClient( client, persistentCloseHandler );
                if ( qaClient === client ) {
                    qaClientReady = false;
                    qaClient = null;
                    qaMediaStream = null;
                }
                if ( data.say?.audio?.url ) {
                    await playFallbackAudio( data.say.audio.url );
                }
            } finally {
                qaVideoActive = false;
                video.srcObject = null;
                if ( qaVideoSnapshot.src ) {
                    video.src = qaVideoSnapshot.src;
                    // Reassigning .src reloads the element and resets
                    // playback position even for the same URL — restore the
                    // paused frame the visitor actually left off at once the
                    // reloaded resource can seek, or the resume below (or
                    // closePanel()'s own) would restart the clip from 0
                    // instead of resuming it.
                    const targetTime = qaVideoSnapshot.currentTime;
                    video.addEventListener( 'loadedmetadata', function onMeta() {
                        video.removeEventListener( 'loadedmetadata', onMeta );
                        video.currentTime = targetTime;
                    } );
                    // PR #73 (review feedback, Dejan Arsić) — the element's
                    // own autoplay attribute otherwise restarts this clip as
                    // soon as it has enough data, regardless of whether Ask
                    // Sami is still open. playPrerenderedClip()'s own 'ended'
                    // listener is still attached the whole time (it never
                    // fired while paused for the answer), so a clip short
                    // enough to finish while the panel is still open would
                    // wake the sequence loop and advance to the next screen
                    // underneath it. Re-assert paused here — only the
                    // wasPlaying-gated video.play() below (or closePanel()'s
                    // own) is allowed to start it again.
                    video.pause();
                }
                avatarWrap.dataset.status = qaVideoSnapshot.status;
                qaAnswering = false;
                // The restored (re-paused) clip frame must not be what a still-open
                // panel leaves on screen — back to the idle animation, or, if the
                // panel was closed meanwhile, let the video resume below.
                syncAvatarIdle();
                // The panel may have already been closed while this was
                // still speaking — closePanel() deliberately left both the
                // sequence-timer and wasPlaying resume to us in that case
                // (see its own qaAnswering check) to avoid both of us
                // touching shared state at once.
                if ( ! wrap.classList.contains( 'is-open' ) ) {
                    resumeSequenceTimers();
                    if ( wasPlaying ) {
                        video.play().catch( function () {} );
                    }
                }
            }
        }

        async function askQuestion( text, generation ) {
            if ( ! text || ! garySessionId ) {
                return;
            }
            errorEl.textContent = '';
            answerEl.textContent = '';
            statusEl.textContent = 'Thinking…';
            micBtn.disabled = true;
            qaQuestionsInFlight++;
            try {
                const res = await fetch( garyMessageUrl( garySessionId ), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
                    body: JSON.stringify( {
                        text: text,
                        // PO-3346 — once an avatar is already connected from an
                        // earlier answer this visit, ask for text only rather
                        // than tying up another avatar seat for video we
                        // won't use (Gary's own guidance, 2026-09-30).
                        want: qaClientReady ? [ 'text' ] : [ 'text', 'audio', 'video' ],
                    } ),
                } );
                // Same "read as text first" reasoning as openGarySession() — a
                // 502 from Cloudflare/PHP-FPM is an HTML page, not JSON.
                const rawBody = await res.text();
                let data;
                try {
                    data = JSON.parse( rawBody );
                } catch ( parseError ) {
                    throw new Error( 'Ask Sami returned a non-JSON response (HTTP ' + res.status + ').' );
                }
                if ( ! res.ok || ! data.say?.text ) {
                    throw new Error( data.error || 'Ask Sami request failed.' );
                }
                // PR #68 (CodeRabbit) — the panel may have closed and
                // reopened (or been asked a newer question) while this was
                // in flight; a stale answer must not overwrite what's on
                // screen now.
                if ( generation !== askGeneration ) {
                    return;
                }
                answerEl.textContent = data.say.text;
                statusEl.textContent = '';
                // PO-3330 — record the exchange so it's available on a
                // return visit (read-only display, per the product decision
                // above applyResumedQaHistory()). Saved before
                // speakAnswerOrFallback() rather than after — the Q&A itself
                // already succeeded at this point; a slow/failed video
                // answer afterward shouldn't risk losing it.
                qaHistory.push( { question: text, answer: data.say.text } );
                if ( qaHistory.length > QA_HISTORY_MAX ) {
                    qaHistory = qaHistory.slice( -QA_HISTORY_MAX );
                }
                // Review feedback (PR #76, Dejan Arsić) — the pair just
                // pushed is already live in answerEl right above this list;
                // rendering the full qaHistory here showed it a second time.
                // Excluded from the rendered list, not from what's saved —
                // saveProgress() below still sends the complete array.
                renderQaHistory( qaHistory.slice( 0, -1 ) );
                saveProgress( { qaHistory: qaHistory } );
                await speakAnswerOrFallback( data );
            } catch ( error ) {
                if ( generation !== askGeneration ) {
                    return;
                }
                console.warn( '[aicoach] Ask Sami request failed:', error );
                errorEl.textContent = 'Something went wrong — please try again.';
                statusEl.textContent = '';
            } finally {
                qaQuestionsInFlight--;
                if ( generation === askGeneration ) {
                    micBtn.disabled = false;
                }
            }
        }

        // ENGR-7051 — openPanel() pauses the shared <video>, which on its own
        // leaves Sami frozen on whatever frame she was on (often mid-word) for
        // as long as the panel stays open: while listening, while a question is
        // in flight, and again after an answer finishes. While the panel is open
        // and no lip-synced answer is on screen, the avatar wrapper gets
        // .is-idle and the CSS swaps that frame for her neutral portrait with a
        // gentle looping animation. Derived from those two facts and recomputed
        // at every transition, never set ad hoc, so it cannot get stuck on.
        function syncAvatarIdle() {
            avatarWrap.classList.toggle( 'is-idle', isAvatarIdle( {
                panelOpen: wrap.classList.contains( 'is-open' ),
                answerVideoActive: qaVideoActive,
            } ) );
        }

        function openPanel() {
            wrap.classList.add( 'is-open' );
            syncAvatarIdle();
            btn.setAttribute( 'aria-expanded', 'true' );
            // FR-19 — the invite pulse (if one was showing) has done its job
            // the moment the visitor actually opens the panel; the paused
            // dwell behind it still ends normally on its own once the panel
            // closes, via pauseSequenceTimers()/resumeSequenceTimers() below.
            btn.classList.remove( 'is-inviting' );
            wasPlaying = ! video.paused;
            video.pause();
            // PO-3346 — pausing the video alone left the sequence's own
            // dwell/safety timers running, so it could advance to a later
            // screen (or start a new clip) while this panel sat open on top
            // of it. The live intro's own wait still ends on its own, but
            // runFallback() holds the hand-off to the next screen until this
            // panel closes — see waitWhileSequenceHeld().
            pauseSequenceTimers();
            startListening();
        }

        function closePanel() {
            wrap.classList.remove( 'is-open' );
            syncAvatarIdle();
            btn.setAttribute( 'aria-expanded', 'false' );
            stopListening();
            // PR #73 (CodeRabbit) — if a spoken answer (video or fallback
            // audio) is still in progress, resuming the sequence timers here
            // could let a pending dwell/safety-cap fire mid-answer and hand
            // playPrerenderedClip() the shared <video> element out from under
            // it. playFallbackAudio()/speakAnswerOrFallback()'s own cleanup
            // owns this resume once they finish (same deferred-ownership
            // pattern already used below for the wasPlaying video resume).
            if ( ! qaAnswering ) {
                resumeSequenceTimers();
            }
            if ( ! qaAnswering && wasPlaying ) {
                video.play().catch( function () {} );
            }
            // PO-3346 (#7) — the time-remaining prompt may have come due
            // while this panel was open; it was held back by
            // maybeShowTimeRemainingCheck() rather than shown on top of a
            // live Q&A exchange. Re-check now that the panel's closed.
            if ( timeRemainingCheckPending ) {
                timeRemainingCheckPending = false;
                maybeShowTimeRemainingCheck();
            }
        }

        isAnswerPending = function () {
            return qaAnswering || qaQuestionsInFlight > 0;
        };
        closeAskSamiPanel = closePanel; // exposed so selectLocale() can close this before hiding it out from under itself

        // PO-3346 — shared by the button below and the stage's tap-to-interrupt
        // listener (FR-19's "an 'Ask Semi' affordance... producing the same
        // state as the tap"), so both triggers stay identical by construction.
        // Guards against a disabled/hidden button — the stage listener has no
        // native disabled-button protection the way a real <button> click does.
        function togglePanel() {
            if ( btn.disabled || wrap.hidden ) {
                return;
            }
            unmuteOnFirstInteraction(); // see the same note on the language button above
            if ( wrap.classList.contains( 'is-open' ) ) {
                closePanel();
            } else {
                openPanel();
            }
        }
        toggleAskSamiPanel = togglePanel; // exposed for the stage's tap-to-interrupt listener

        btn.addEventListener( 'click', function ( event ) {
            event.stopPropagation();
            togglePanel();
        } );

        closeBtn.addEventListener( 'click', function ( event ) {
            event.stopPropagation();
            closePanel();
        } );

        panel.addEventListener( 'click', function ( event ) {
            event.stopPropagation(); // clicking the mic/close inside the panel must not trigger the document listener below
        } );

        document.addEventListener( 'click', function () {
            if ( wrap.classList.contains( 'is-open' ) ) {
                closePanel();
            }
        } );

        micBtn.addEventListener( 'click', function ( event ) {
            event.stopPropagation();
            startListening(); // re-arm for another question, or retry after a no-speech/no-match/error state
        } );
    }() );

    // Apply the detected locale to the DOM now that the selector (and its
    // is-current marking, set from currentLocale above) exists. A no-op for
    // English/untranslated locales today (t() falls back to I18N_EN either
    // way) but keeps behavior correct once real translations land.
    applyLocale( currentLocale );

    function syncSelected() {
        tiers.forEach( function ( tier ) {
            const input = tier.querySelector( '.aicoach-tier-check' );
            tier.classList.toggle( 'is-selected', Boolean( input && input.checked ) );
        } );
    }

    // PO-3109 — panels that interrupt a screen and give it back, so they are never
    // saved as the visitor's stage.
    const TRANSIENT_PANEL_KEYS = [ 'time-up' ];

    function getActivePanel() {
        return stage.querySelector( '.aicoach-panel.is-active' );
    }

    let pendingPanelKey = null; // a showPanel() call that arrived mid-transition; applied once the current one settles

    // NFR-01 — panels are toggled via opacity/visibility, not display:none (that's
    // what makes the fade transition possible), so the browser's native
    // loading="lazy" never kicks in for images inside them (they're still laid out
    // in the viewport). Heavier images (equity examples) use data-src instead and
    // get hydrated into a real src here, the first time their panel is actually
    // requested — not on initial page load.
    function hydratePanelImages( panel ) {
        panel.querySelectorAll( 'img[data-src]' ).forEach( function ( img ) {
            img.src = img.getAttribute( 'data-src' );
            img.removeAttribute( 'data-src' );
        } );
    }

    // Callers that need to know the requested panel is actually visible
    // (not just "requested") — currently just playPrerenderedClip() below —
    // await showPanel()'s returned promise instead of guessing a fixed delay.
    // Every other call site still just fires it and moves on, which is fine:
    // a Promise nobody awaits behaves exactly like the old `undefined` return.
    let panelActivationWaiters = []; // [{ key, resolve }]

    function resolvePanelWaiters( key ) {
        panelActivationWaiters = panelActivationWaiters.filter( ( waiter ) => {
            if ( waiter.key !== key ) {
                return true;
            }
            waiter.resolve();
            return false;
        } );
    }

    function showPanel( panelKey ) {
        const next = stage.querySelector( '.aicoach-panel[data-panel="' + panelKey + '"]' );
        if ( ! next ) {
            return Promise.resolve();
        }

        // PO-3102 — every real panel transition is "current stage" for Scenario
        // 20's continuous save, not just the 3 form-submit checkpoints. A
        // transient panel (Time is up) is never a place to resume into, so it is
        // not saved: the stage stays the screen the visitor came from.
        if ( TRANSIENT_PANEL_KEYS.indexOf( panelKey ) === -1 ) {
            saveProgress( { stage: panelKey } );
        }

        hydratePanelImages( next );

        if ( isAnimating ) {
            // Don't silently drop this — e.g. FR-08's screen gets queued right after
            // FR-07's own showPanel('identity') call, well within the 800ms fade
            // window on a fast/automated submit. Remember the latest request and
            // apply it once the in-flight transition finishes.
            if ( pendingPanelKey && pendingPanelKey !== panelKey ) {
                // The previously queued key is about to be overwritten and will
                // never show — resolve its waiters now so an awaiting caller
                // doesn't hang forever, same "latest wins" trade-off the
                // fire-and-forget callers already silently accept.
                resolvePanelWaiters( pendingPanelKey );
            }
            pendingPanelKey = panelKey;
            return new Promise( ( resolve ) => {
                panelActivationWaiters.push( { key: panelKey, resolve } );
            } );
        }

        const current = getActivePanel();
        if ( ! current || next === current ) {
            return Promise.resolve();
        }

        isAnimating = true;
        stage.style.minHeight = current.offsetHeight + 'px';

        current.classList.remove( 'is-active' );
        current.setAttribute( 'aria-hidden', 'true' );

        return new Promise( ( resolve ) => {
            window.setTimeout( function () {
                next.classList.add( 'is-active' );
                next.setAttribute( 'aria-hidden', 'false' );
                stage.style.minHeight = next.offsetHeight + 'px';
                resolvePanelWaiters( panelKey );
                resolve();

                window.setTimeout( function () {
                    stage.style.minHeight = '';
                    isAnimating = false;

                    if ( pendingPanelKey && pendingPanelKey !== panelKey ) {
                        const queued = pendingPanelKey;
                        pendingPanelKey = null;
                        showPanel( queued );
                    } else {
                        pendingPanelKey = null;
                    }
                }, FADE_MS );
            }, FADE_MS );
        } );
    }

    let sequenceIndex = 0;
    let sequenceFinished = false;
    let skipCurrent = null; // set while a screen's line is in flight; a tap calls this to advance early
    let activeClipRestart = null; // set while a pre-rendered clip is in flight — FR-14/PO-3105's selectLocale() calls this to swap languages mid-playback

    // FR-03 — timestamp + chosen tier, captured the moment the visitor confirms a
    // selection. This is only the starting mark; FR-17 (time-remaining check) is a
    // separate story and owns the actual countdown/prompt logic built on top of it.
    // Deliberately in-memory only, not persisted — cross-session resume is FR-11's
    // job (Luna), not this one's.
    let elapsedStartedAt = null;
    let selectedTierMinutes = null;
    let tierConfirmed = false; // FR-03 confirm is one-time; ignore further tier clicks after that
    // ENGR-7098 — stops the live intro's Anam client and frees its avatar seat;
    // set by start() once that client exists, a no-op until then and after the
    // first call. The intro connection used to stay open for the rest of the
    // visit, which counts against the account's concurrent-session limit and
    // left no seat for Ask Sami's own video answers. Called when the first
    // pre-rendered clip replaces the intro stream, when start() fails, and on
    // pagehide.
    let releaseIntroClient = function () {};
    let garySessionId = null;  // this visit's Gary coach session id, for the close() call on completion
    let avatarIsLive = false;  // true once Gary's session connected and video started — keeps the avatar
                                // visible (not the idle portrait) through runFallback()'s static captions

    // Where finishSequence() navigates once the current SCREENS queue is fully
    // consumed — this changes as later FRs queue more content after their own
    // predecessor's success handler runs. FR-07 is the first destination (no
    // spoken script of its own); FR-08's success handler clears this back to
    // null since nothing is built after comm-channels yet. Without this, calling
    // finishSequence() a second time (after FR-08's one queued screen finishes)
    // would incorrectly snap back to 'identity' instead of just stopping.
    let sequenceEndDestination = 'identity';

    // FR-19 — "the Coach pauses and invites me to ask anything, and continues
    // on her own if I do not" at 3 defined beats (after the equity example,
    // before identity capture, before communication channels — see the 3
    // call sites). No approved script line exists for this prompt (unlike
    // every spoken screen in this file), so this is deliberately a UI-level
    // nudge — highlighting the existing Ask Sami entry point — rather than
    // invented avatar dialogue. English-only, same as Ask Sami itself
    // (already hidden outside 'en'; nothing extra to gate here).
    // createPausableTimeout() is what makes "continues on her own" actually
    // correct if the visitor opens Ask Sami during the window: pauseSequenceTimers()
    // (already called by openPanel()) pauses this dwell along with everything
    // else, so opening the panel doesn't race the invite into finishing under it.
    const INVITE_DWELL_MS = 6000;

    function inviteQuestionOrSkip() {
        return new Promise( function ( resolve ) {
            if ( ! askSamiBtn || askSamiBtn.disabled || ! askSamiWrap || askSamiWrap.hidden ) {
                resolve();
                return;
            }
            askSamiBtn.classList.add( 'is-inviting' );
            createPausableTimeout( function () {
                askSamiBtn.classList.remove( 'is-inviting' );
                resolve();
            }, INVITE_DWELL_MS );
        } );
    }

    async function finishSequence() {
        if ( sequenceFinished ) {
            return;
        }
        sequenceFinished = true;
        // No coach script exists for identity capture (unlike every screen
        // before it) or anything after it yet, so those steps aren't part of
        // SCREENS — they're plain showPanel() destinations reached once
        // whatever's currently queued in SCREENS runs out. Which
        // destination that is changes as later stories queue more content (see
        // sequenceEndDestination); null means nothing built yet, just stop.
        if ( 'identity' === sequenceEndDestination ) {
            // FR-19 — "before identity capture" invite beat. Also covers the
            // 2-minute tier's "after the equity example" beat, which lands
            // on this exact same moment (no competition screens between
            // them for that tier) — see the loop's own equity-bts check.
            await inviteQuestionOrSkip();
        }
        // PO-3109 (review, Dejan Arsić) — the end destination must not replace the
        // Time is up screen: its external hold would then never be released and
        // the form's Continue would wait on it forever.
        await waitWhileSequenceExternallyHeld();
        if ( sequenceEndDestination ) {
            showPanel( sequenceEndDestination );
        }
    }

    // Use click so a pre-checked tier (e.g. 2 minutes) still opens its story. A click
    // is also the FR-03 "confirm" action: it ends the coach's time-selection line
    // early if she's still mid-sentence (same tap-to-advance mechanism as the We
    // Believe screens), then queues that tier's FR-05 equity examples and (for
    // 5/10-minute tiers) FR-06 competition-type screens onto SCREENS — runFallback's
    // own loop picks them up and shows them in the usual way. If
    // that loop already finished by the time the visitor clicks (they took a
    // moment to decide), nothing is left running to notice the new screens, so
    // resume it explicitly.
    tiers.forEach( function ( tier ) {
        const input = tier.querySelector( '.aicoach-tier-check' );
        if ( ! input ) {
            return;
        }

        tier.addEventListener( 'click', function () {
            if ( tierConfirmed ) {
                return;
            }
            tierConfirmed = true;

            input.checked = true;
            syncSelected();
            const panelKey = input.getAttribute( 'data-panel' );
            if ( ! panelKey ) {
                return;
            }

            elapsedStartedAt = Date.now();
            selectedTierMinutes = panelKey;
            saveProgress( { tier: panelKey } ); // PO-3102
            scheduleTimeRemainingCheck( panelKey );
            const loopAlreadyExited = sequenceFinished;
            SCREENS.push( ...getEquityScreensForTier( panelKey ), ...getCompetitionScreensForTier( panelKey ) );

            if ( skipCurrent ) {
                skipCurrent();
            }

            if ( loopAlreadyExited ) {
                sequenceFinished = false;
                runFallback( avatarIsLive );
            }
        } );
    } );

    syncSelected();

    // Text has nothing to sync against here, so each screen is shown in full
    // and paced by an estimated reading dwell rather than word-by-word reveal.
    // Resumes from sequenceIndex, so a mid-sequence connection drop (or a late
    // tier click after the queue already ran out) picks up wherever playback
    // left off instead of restarting from the intro.
    //
    // keepAvatarLive: true when a real Gary session is connected and the video
    // should stay visible while these static captions play (the normal path,
    // now that Gary has no way to recite this copy itself — see the top-of-file
    // note); false/omitted for a genuine connection failure, which reverts the
    // avatar to the idle portrait like before.
    //
    // fallbackRunning guards against a second concurrent loop racing the same
    // shared sequenceIndex/skipCurrent — Anam's VIDEO_PLAY_STARTED has been
    // observed to fire more than once for a single session (a WebRTC
    // renegotiation, not a real second connection), which would otherwise call
    // this twice and advance two screens per tap instead of one.
    let fallbackRunning = false;

    // FR-02 — narration timing OR visitor tap/click; the stage click handler
    // below calls skipCurrent() to advance early. Shared by runFallback()'s own
    // loop and by the VIDEO_PLAY_STARTED handler, which needs the exact same
    // dwell-or-skip behavior for Gary's opening line before handing off to
    // runFallback() for the rest of the screens.
    function waitForReadOrSkip() {
        return new Promise( ( resolve ) => {
            let settled = false;
            let timer;
            const finish = () => {
                if ( settled ) {
                    return;
                }
                settled = true;
                skipCurrent = null;
                timer.cancel(); // PO-3346 — no-op if it already fired to get here
                resolve();
            };
            skipCurrent = finish;
            timer = createPausableTimeout( finish, FALLBACK_READ_MS );
        } );
    }

    // Live-speech-only counterpart to waitForReadOrSkip(): a fixed dwell has
    // nothing to sync against for the static fallback screens (no audio at
    // all), but Gary's real spoken intro line does — MESSAGE_HISTORY_UPDATED
    // fires once the avatar's full utterance is complete. Measured live:
    // talk() itself resolves almost instantly (it just queues the request),
    // then MESSAGE_HISTORY_UPDATED can take anywhere from ~9s to ~19s
    // depending on reply length — a fixed FALLBACK_READ_MS (9000ms) either
    // cuts her off mid-sentence or leaves the caption sitting long after
    // she's actually finished, which is exactly the "out of sync" feedback
    // this replaces. capMs is a safety net only, in case the event never
    // arrives for some reason (dropped connection, SDK quirk).
    function waitForSpeechOrSkip( client, capMs ) {
        return new Promise( ( resolve ) => {
            let settled = false;
            const finish = () => {
                if ( settled ) {
                    return;
                }
                settled = true;
                skipCurrent = null;
                client.removeListener( AnamEvent.MESSAGE_HISTORY_UPDATED, finish );
                resolve();
            };
            client.addListener( AnamEvent.MESSAGE_HISTORY_UPDATED, finish );
            skipCurrent = finish;
            window.setTimeout( finish, capMs );
        } );
    }

    // PO-3346 — same shape as waitForSpeechOrSkip() above, for Ask Sami's
    // spoken video answers. Deliberately NOT wired into the shared
    // skipCurrent — that's the main sequence loop's state (stage taps, tier
    // clicks); Ask Sami's Q&A is a self-contained side-flow with nothing in
    // the sequence to skip to.
    // PR #73 (CodeRabbit) — also rejects on CONNECTION_CLOSED: without this,
    // a connection dropping mid-speech (after VIDEO_PLAY_STARTED but before
    // the answer's MESSAGE_HISTORY_UPDATED) just sat out the full capMs
    // timeout and then resolved as if the answer had played normally, so the
    // caller's catch/fallback-to-audio path never ran even though we had a
    // working say.audio.url to fall back to.
    function waitForQaSpeechOrSkip( client, capMs ) {
        return new Promise( ( resolve, reject ) => {
            let settled = false;
            let timeoutId;
            const finish = () => {
                if ( settled ) {
                    return;
                }
                settled = true;
                client.removeListener( AnamEvent.MESSAGE_HISTORY_UPDATED, finish );
                client.removeListener( AnamEvent.CONNECTION_CLOSED, onClosed );
                window.clearTimeout( timeoutId );
                resolve();
            };
            const onClosed = () => {
                if ( settled ) {
                    return;
                }
                settled = true;
                client.removeListener( AnamEvent.MESSAGE_HISTORY_UPDATED, finish );
                client.removeListener( AnamEvent.CONNECTION_CLOSED, onClosed );
                window.clearTimeout( timeoutId );
                reject( new Error( 'Anam connection closed while the answer was still speaking.' ) );
            };
            client.addListener( AnamEvent.MESSAGE_HISTORY_UPDATED, finish );
            client.addListener( AnamEvent.CONNECTION_CLOSED, onClosed );
            timeoutId = window.setTimeout( finish, capMs );
        } );
    }

    // Plays a pre-rendered clip (inc/aicoach-prerender.php) in the same
    // <video> element the live avatar uses, resolving true on the clip's
    // natural 'ended' event (or a manual skip) instead of a fixed dwell —
    // the clip's own length already matches its audio exactly, nothing to
    // estimate. Resolves false on a playback error; the caller is
    // responsible for falling back to the ordinary caption dwell in that
    // case (this function only plays video, it doesn't own the caption's
    // timing on failure).
    function playPrerenderedClip( url ) {
        return new Promise( ( resolve ) => {
            let settled = false;
            // FR-14/PO-3105 — bumped by load() on every (re)start, including a
            // restartCurrentClipForLocale() swap mid-playback. A play() promise
            // rejection captures the generation it belongs to when it was issued;
            // if a restart has since loaded a newer clip, that rejection is stale
            // (the interrupted old attempt failing, not the new one) and must be
            // ignored instead of wrongly finishing this screen as "failed" out
            // from under the clip the visitor is now actually watching.
            let generation = 0;
            let safetyTimer = null;
            const finish = ( ok ) => {
                if ( settled ) {
                    return;
                }
                settled = true;
                skipCurrent = null;
                activeClipRestart = null;
                if ( safetyTimer ) {
                    safetyTimer.cancel();
                }
                video.removeEventListener( 'ended', onEnded );
                video.removeEventListener( 'error', onError );
                video.pause(); // a manual skip or the safety cap would otherwise leave it playing into the next screen
                resolve( ok );
            };
            const onEnded = () => finish( true );
            const onError = () => {
                console.warn( '[aicoach] prerendered clip failed to play, falling back to caption dwell:', url );
                finish( false );
            };
            const load = ( clipUrl ) => {
                const myGeneration = ++generation;
                // srcObject (the live WebRTC stream, if any) takes priority over
                // src in the video element — clear it first or a plain MP4 src
                // silently never plays.
                video.srcObject = null;
                // ENGR-7098 — the intro's stream is off the element now, so its
                // avatar seat can be freed without leaving a dead stream on
                // screen. A no-op after the first call and when there is no
                // intro client (resume, failed start).
                releaseIntroClient();
                video.currentTime = 0;
                video.src = clipUrl;
                avatarWrap.dataset.status = 'live'; // same CSS state that reveals .aicoach-avatar-video over the static portrait
                video.play().catch( () => {
                    if ( myGeneration === generation ) {
                        onError();
                    }
                } );
                // FR-14/PO-3105 — a restart mid-playback re-arms this cap from 0
                // too. Without that, a language switch shortly before the
                // original 60s deadline would have the STALE timer fire a few
                // seconds into the restarted clip, cutting it off early even
                // though 'ended' hasn't happened yet for it.
                if ( safetyTimer ) {
                    safetyTimer.cancel();
                }
                // PO-3346 — pause-aware: safety cap — 'ended' should always fire first
                safetyTimer = createPausableTimeout( () => finish( true ), 60000 );
            };
            video.addEventListener( 'ended', onEnded );
            video.addEventListener( 'error', onError );
            load( url );
            skipCurrent = () => finish( true ); // a visitor tap is a normal advance, not a failure
            // FR-14/PO-3105 — selectLocale() calls this (via
            // restartCurrentClipForLocale()) to swap the currently-playing clip's
            // language without disturbing this same pending promise; 'ended' for
            // whichever clip is loaded when it naturally finishes still resolves
            // it exactly as before.
            activeClipRestart = load;
        } );
    }

    // PO-3343 — believe-1 opens on the coin/chart/certificate trio together
    // (matching Figma "Belief 7"), no caption text yet, then switches to the
    // normal icon-free caption once the opening line has had time to be said.
    // Explicitly the "fallback" version Ivan signed off on (2026-09-29): a
    // fixed dwell instead of syncing to the exact second in we_believe_1.mp4
    // where Sami's opening line actually ends.
    //
    // 7200ms, not a guess — measured directly off the real we_believe_1.mp4
    // (English) via Web Audio: decoded the clip and scanned RMS volume in
    // 100ms windows to find the silence gaps between sentences. "Every
    // successful company begins with a set of beliefs. Here's one of ours."
    // (the two sentences meant to go with the icons) ends at the pause
    // starting ~6.0s and finishing ~7.1s, right before "We believe influence
    // is about..." begins — confirmed independently by word-count proportion
    // (those two sentences are ~17% of the script's ~76 words, and 17% of
    // the clip's ~31.8s of actual speech + its 1.8s lead-in silence lands at
    // the same ~7.2s). The original 3500ms guess cut the sentence off
    // mid-word (caught live: "quickly switches ... doesn't finish the
    // opening sentence"). Only measured for English — every other locale's
    // clip has its own pacing and still uses this same constant, the same
    // "good enough fallback" gap already true of the rest of this feature.
    const BELIEVE_1_ICON_PHASE_MS = 7200;
    // Dejan (2026-09-29): once the opening two sentences have been spoken
    // during the icon phase, re-showing them in the text-phase caption would
    // have the visitor reading a line Sami already finished saying several
    // seconds earlier — the same "caption must match what's actually being
    // said right now" reasoning NFR-03 already cares about everywhere else.
    // English-only for the same reason getCaptionScript() itself doesn't
    // attempt this for translated captions: we don't have per-locale sentence
    // boundaries, only a single continuous translated string.
    const BELIEVE_1_OPENING_EN = "Every successful company begins with a set of beliefs. Here's one of ours. ";
    const believeOneEl = document.getElementById( 'aicoach-believe-1' );
    let believeOneIntroTimer = null; // the pending icon->text switch, if any

    function startBelieveOneIntro() {
        if ( ! believeOneEl ) {
            return;
        }
        // A locale switch during the icon phase calls this again (see
        // restartCurrentClipForLocale()) without sequenceIndex changing, so
        // the guard below alone can't tell the old timer it's stale — without
        // clearing it here, BOTH timers fire: whichever was scheduled first
        // flips to the text phase on the OLD schedule, which no longer
        // matches the just-restarted (from 0) clip's real timeline.
        window.clearTimeout( believeOneIntroTimer );
        const myIndex = sequenceIndex;
        believeOneEl.classList.remove( 'is-text-phase' );
        const captionEl = getCaptionEl( 'believe-1' );
        if ( captionEl ) {
            captionEl.textContent = '';
        }
        believeOneIntroTimer = window.setTimeout( function () {
            believeOneIntroTimer = null;
            // A tier confirmation's own early-advance (or a fresh
            // runFallback() from a locale/tier change) may have already moved
            // on to a later screen by the time this fires — applying the
            // text phase to a screen the visitor isn't on anymore would just
            // leave it in the wrong state for whenever they come back around
            // to it.
            if ( sequenceIndex !== myIndex ) {
                return;
            }
            believeOneEl.classList.add( 'is-text-phase' );
            if ( captionEl ) {
                const translated = getCaptionScript( 'believe-1', currentLocale );
                captionEl.textContent = translated || SCREENS[ myIndex ].script.replace( BELIEVE_1_OPENING_EN, '' );
            }
        }, BELIEVE_1_ICON_PHASE_MS );
    }

    async function runFallback( keepAvatarLive ) {
        if ( fallbackRunning ) {
            return;
        }
        fallbackRunning = true;
        if ( ! keepAvatarLive ) {
            avatarWrap.dataset.status = 'idle';
        }
        for ( ; sequenceIndex < SCREENS.length; sequenceIndex++ ) {
            // ENGR-7051 — never start a screen (and so a clip, which has sound)
            // underneath an open Ask Sami panel. A no-op unless the panel is open.
            await waitWhileSequenceHeld();
            const screen = SCREENS[ sequenceIndex ];
            // PO-3343 — believe-1 gets the icons-first opening beat instead of
            // the normal immediate caption; startBelieveOneIntro() (called
            // once the panel is actually visible, below) owns setting its
            // caption text instead of the two spots in this loop that
            // otherwise do it for every screen.
            const isBelieveOne = 'believe-1' === screen.panel;
            const panelReady = showPanel( screen.panel );
            const captionEl = getCaptionEl( screen.panel );
            if ( captionEl && ! isBelieveOne ) {
                // NFR-03 — best-effort immediate text so the caption isn't blank
                // during the fade; re-set below once currentLocale is final for
                // this screen, same reasoning as the clip URL re-resolve just
                // after panelReady.
                captionEl.textContent = getCaptionScript( screen.panel, currentLocale ) || screen.script;
            }
            // PO-3062 — an approved segment with a pre-rendered clip plays it
            // (real voice + lip-sync, dwell = the clip's own length); every
            // other screen keeps the original caption-only fixed dwell.
            if ( getPrerenderedUrl( screen.panel, currentLocale ) ) {
                // Wait for this exact panel to actually be the active one —
                // not just a fixed FADE_MS guess, which breaks if showPanel()
                // had to queue behind another in-flight transition (it can
                // take longer than one FADE_MS in that case; see showPanel()).
                await panelReady;
                // ENGR-7051 (CodeRabbit) — the hold at the top of the loop was
                // checked BEFORE this transition's fade; a visitor can open Ask
                // Sami during it, and playPrerenderedClip() below would then start
                // the clip (with sound) under the open panel. Re-check here, and
                // before the locale re-resolve below so a language picked while
                // held (which closes the panel) is the one that plays.
                await waitWhileSequenceHeld();
                // FR-14/PO-3105 — re-resolve against currentLocale rather than
                // reusing a URL captured before this await: a visitor who picks
                // a different language during showPanel()'s ~800ms fade (before
                // playPrerenderedClip() below has even started, so
                // restartCurrentClipForLocale() has nothing in flight yet to
                // restart) would otherwise still hear this screen start in the
                // language they just left.
                const clipUrl = getPrerenderedUrl( screen.panel, currentLocale );
                if ( isBelieveOne ) {
                    startBelieveOneIntro();
                } else if ( captionEl ) {
                    // NFR-03 — keep the caption in step with whichever clip just
                    // got (re-)resolved above, for the same reason.
                    captionEl.textContent = getCaptionScript( screen.panel, currentLocale ) || screen.script;
                }
                const played = clipUrl && await playPrerenderedClip( clipUrl );
                if ( ! played ) {
                    await waitForReadOrSkip();
                }
            } else {
                // PO-3343 — the same icons-first intro applies even in this
                // no-prerendered-clip fallback (only reachable if believe-1's
                // segment has no rendered video at all, in any locale —
                // shouldn't happen in production, but leaving it dark here
                // would mean icons forever with a blank caption for this
                // screen's whole dwell instead of transitioning to text).
                if ( isBelieveOne ) {
                    await panelReady;
                    startBelieveOneIntro();
                }
                await waitForReadOrSkip();
            }
            if ( sequenceFinished ) {
                fallbackRunning = false;
                return; // a fresh runFallback() call elsewhere already took over
            }
            // FR-19 — "after the equity example" invite beat. Only when
            // something else in SCREENS actually follows it (competition
            // types, 5/10-minute tiers) — for the 2-minute tier, equity-bts
            // is the last queued screen, so this moment and "before identity
            // capture" are the same beat; finishSequence()'s own invite
            // covers it once there instead of twice here.
            if ( 'equity-bts' === screen.panel && sequenceIndex + 1 < SCREENS.length ) {
                await inviteQuestionOrSkip();
            }
        }
        fallbackRunning = false;
        finishSequence();
    }

    // FR-19 (PO-3346) — "Tapping the Coach video pauses her mid-delivery and
    // opens the voice question state on every screen, in every tier and
    // language." Replaces the old FR-02 tap-to-skip (PO-3093's "narration
    // timing or visitor tap/click"): product confirmed (Ivan Vladić,
    // PO-3062 comment, 2026-09-30) that skipping the flow was never
    // actually wanted, and FR-02 is being edited to drop that clause.
    // Screens still advance on their own via each screen's dwell timer or
    // its clip's natural 'ended' event — nothing here replaces that, this
    // listener only ever *interrupted* early advance, it never drove normal
    // advance.
    // Review feedback (PR #72, Dejan Arsić) — two gaps in the first version:
    // 1. Tier clicks had their own exclusion, but comm-channels' checkboxes/
    //    inputs/continue button and the final-continue button did not. Both
    //    of those screens get pushed onto SCREENS and resume this same
    //    sequence loop (see the identity/channels form submit handlers), so
    //    `current` is truthy while they're showing, same as any narration
    //    screen — a tap meant for a form control was also toggling Ask Sami.
    //    Excluded the same way now, by matching any interactive control
    //    rather than one specific class.
    // 2. This was only ever wired on .aicoach-stage. The avatar video sits
    //    in its own sibling element (#aicoach-avatar-wrap), so tapping Sami
    //    herself did nothing but the generic document-level first-interaction
    //    unmute — never the interrupt the AC's own wording names explicitly
    //    ("tapping the Coach video"). Wired on both elements now, via one
    //    shared handler since the state it reads/acts on is identical either
    //    way.
    function handleStageTap( event ) {
        if ( isAnimating ) {
            return; // avoid desyncing the caption/panel if tapped mid-fade
        }
        const current = SCREENS[ sequenceIndex ];
        if ( ! current ) {
            return;
        }
        if ( event.target.closest( '.aicoach-tier, input, button, label, select, textarea' ) ) {
            return;
        }
        if ( toggleAskSamiPanel ) {
            // Ask Sami's own document-level "click outside closes the panel"
            // listener has no target check — without this, opening the panel
            // from here would have it close again immediately, same click,
            // once this event finished bubbling up to document.
            event.stopPropagation();
            // stopPropagation() above also means this click never reaches
            // document's unmuteOnFirstInteraction() listener — same reasoning
            // as the language/Ask Sami buttons already calling it explicitly.
            unmuteOnFirstInteraction();
            toggleAskSamiPanel();
        }
    }
    stage.addEventListener( 'click', handleStageTap );
    avatarWrap.addEventListener( 'click', handleStageTap );

    // Click toggles mute (also opens/closes the slider — see portal-header.php's
    // own handler for that part, unchanged); dragging the slider sets a level
    // directly and drives mute from it, same as any standard volume control.
    headerVolumeBtn?.addEventListener( 'click', function () {
        video.muted = ! video.muted;
    } );
    headerVolumeSlider?.addEventListener( 'input', function () {
        const level = Number( headerVolumeSlider.value ) / 100;
        video.volume = level;
        video.muted = 0 === level;
    } );

    // Browsers block autoplaying audio until the visitor has interacted with
    // the page at least once — there's no way around that for a page that
    // starts talking on arrival with no required tap. The first click/tap/
    // keypress anywhere unmutes it, same as the "tap to unmute" pattern used
    // elsewhere for autoplaying video with sound; the header volume control
    // above is for explicitly muting/adjusting afterward.
    function unmuteOnFirstInteraction() {
        document.removeEventListener( 'click', unmuteOnFirstInteraction );
        document.removeEventListener( 'keydown', unmuteOnFirstInteraction );
        if ( ! video.muted ) {
            return;
        }
        video.muted = false;
    }
    document.addEventListener( 'click', unmuteOnFirstInteraction );
    document.addEventListener( 'keydown', unmuteOnFirstInteraction );

    // FR-07 — identity capture form (First Name, Last Name, Username). No coach
    // narration exists for this screen (no approved script, unlike every prior
    // one), so it's plain form logic: CONTINUE is gated only on all three
    // fields being non-empty (Scenario 10); the username-uniqueness check runs
    // on blur or on submit (Scenario 11) and blocks proceeding if taken.
    //
    // The uniqueness-check endpoint is owned by BE (not built as of this
    // story) — checkUsernameAvailability's contract (GET .../username-available
    // ?username=, expects { available: bool }) is this FE's assumption, and it
    // fails open (treats a network/404 error as "available") purely so this
    // form stays usable end-to-end before BE ships the real endpoint. Confirm
    // the actual contract with BE and remove the fail-open once it's live.
    const identityForm = document.getElementById( 'aicoach-identity-form' );
    const firstNameInput = document.getElementById( 'aicoach-first-name' );
    const lastNameInput = document.getElementById( 'aicoach-last-name' );
    const usernameInput = document.getElementById( 'aicoach-username' );
    const usernameError = document.getElementById( 'aicoach-username-error' );
    const identityContinueBtn = document.getElementById( 'aicoach-form-continue' );

    let capturedIdentity = null;      // { firstName, lastName, username } once FR-07 completes; FR-08/09 read this next
    let usernameCheckedValue = null;  // last username value a check actually ran against
    let usernameAvailable = null;     // null = needs a (re)check, true/false = last check's result
    let usernameCheckToken = 0;       // guards a stale async response from overwriting a newer one

    function identityFieldsFilled() {
        return Boolean(
            firstNameInput?.value.trim() &&
            lastNameInput?.value.trim() &&
            usernameInput?.value.trim()
        );
    }

    function updateIdentityContinueState() {
        if ( identityContinueBtn ) {
            identityContinueBtn.disabled = ! identityFieldsFilled();
        }
    }

    async function checkUsernameAvailability( username ) {
        try {
            const res = await fetch(
                cfg.identityRestBase + '/username-available?username=' + encodeURIComponent( username ),
                { headers: { 'X-WP-Nonce': cfg.nonce } }
            );
            if ( ! res.ok ) {
                throw new Error( 'username-available returned ' + res.status );
            }
            const data = await res.json();
            return Boolean( data.available );
        } catch ( error ) {
            console.warn( '[aicoach] username-available check failed, assuming available (BE endpoint not live yet):', error );
            return true;
        }
    }

    async function runUsernameCheck() {
        const value = usernameInput.value.trim();
        if ( ! value ) {
            return;
        }
        if ( value === usernameCheckedValue && usernameAvailable !== null ) {
            return; // already have a fresh answer for this exact value
        }

        const token = ++usernameCheckToken;
        const available = await checkUsernameAvailability( value );
        if ( token !== usernameCheckToken ) {
            return; // a newer check superseded this one
        }

        usernameCheckedValue = value;
        usernameAvailable = available;
        usernameInput.classList.toggle( 'is-invalid', ! available );
        usernameError.textContent = available ? '' : ( cfg.i18n?.usernameTaken || 'That username is already taken.' );
    }

    if ( identityForm ) {
        [ firstNameInput, lastNameInput ].forEach( function ( input ) {
            input?.addEventListener( 'input', updateIdentityContinueState );
        } );

        usernameInput?.addEventListener( 'input', function () {
            usernameAvailable = null; // the typed value no longer matches what was last checked
            usernameError.textContent = '';
            usernameInput.classList.remove( 'is-invalid' );
            updateIdentityContinueState();
        } );
        usernameInput?.addEventListener( 'blur', runUsernameCheck );

        identityForm.addEventListener( 'submit', async function ( event ) {
            event.preventDefault();
            if ( ! identityFieldsFilled() ) {
                return;
            }
            await runUsernameCheck();
            if ( usernameAvailable === false ) {
                return; // inline error already shown by runUsernameCheck
            }
            capturedIdentity = {
                firstName: firstNameInput.value.trim(),
                lastName: lastNameInput.value.trim(),
                username: usernameInput.value.trim(),
            };
            saveProgress( { identity: capturedIdentity } ); // PO-3102
            identityContinueBtn.disabled = true;
            identityContinueBtn.textContent = cfg.i18n?.identitySaved || 'Saved';

            // FR-19 — "before the communication channels screen" invite beat.
            await inviteQuestionOrSkip();

            // FR-08 — queue the comm-channels screen the same way FR-03's tier
            // confirm queues equity/competition screens: push onto SCREENS and
            // resume whichever runner was active (the loop exited when we first
            // reached 'identity', so it needs an explicit resume here, same as a
            // late tier click in PO-3096. sequenceFinished is always true at this
            // point in real usage (finishSequence() is the only way to reach the
            // identity panel at all, and it sets this first) — the loopAlreadyExited
            // check just keeps this self-consistent with the tier-click pattern
            // rather than relying on that invariant implicitly.
            const loopAlreadyExited = sequenceFinished;
            SCREENS.push( COMM_CHANNELS_SCREEN );
            // Nothing is built after comm-channels yet (that's FR-09) — clear the
            // destination so finishSequence(), once comm-channels' single queued
            // screen finishes, just stops instead of bouncing back to 'identity'.
            sequenceEndDestination = null;
            if ( loopAlreadyExited ) {
                sequenceFinished = false;
                runFallback( avatarIsLive );
            }
        } );
    }

    // FR-08 — communication channels form. Checking a channel reveals its input
    // field (Scenario 14); CONTINUE is gated on at least one channel checked and
    // every checked channel's field passing its format check (Scenario 15).
    const channelsForm = document.getElementById( 'aicoach-channels-form' );
    const channelsHint = document.getElementById( 'aicoach-channels-hint' );
    const channelsContinueBtn = document.getElementById( 'aicoach-channels-continue' );

    let capturedChannels = null; // [{ channel, value }] once FR-08 completes; FR-09 reads this next

    function getChannelParts( key ) {
        const row = channelsForm?.querySelector( '.aicoach-channel[data-channel="' + key + '"]' );
        return {
            row: row,
            checkbox: row?.querySelector( '.aicoach-channel-check' ),
            field: row?.querySelector( '.aicoach-channel-field' ),
            input: row?.querySelector( '.aicoach-channel-input' ),
            error: row?.querySelector( '.aicoach-channel-error' ),
        };
    }

    function checkedChannels() {
        return CHANNELS.map( function ( ch ) {
            return { ch: ch, parts: getChannelParts( ch.key ) };
        } ).filter( function ( entry ) {
            return Boolean( entry.parts.checkbox?.checked );
        } );
    }

    function updateChannelsContinueState() {
        const checked = checkedChannels();
        const valid = checked.length > 0 && checked.every( function ( entry ) {
            return entry.ch.validate( entry.parts.input.value.trim() );
        } );
        if ( channelsContinueBtn ) {
            channelsContinueBtn.disabled = ! valid;
        }
        if ( channelsHint ) {
            channelsHint.classList.toggle( 'is-error', checked.length === 0 );
        }
    }

    if ( channelsForm ) {
        CHANNELS.forEach( function ( ch ) {
            const { checkbox, field, input, error } = getChannelParts( ch.key );
            if ( ! checkbox || ! field || ! input ) {
                return;
            }

            checkbox.addEventListener( 'change', function () {
                field.hidden = ! checkbox.checked;
                if ( ! checkbox.checked ) {
                    input.value = '';
                    input.classList.remove( 'is-invalid' );
                    error.textContent = '';
                }
                updateChannelsContinueState();
            } );

            input.addEventListener( 'input', function () {
                input.classList.remove( 'is-invalid' );
                error.textContent = '';
                updateChannelsContinueState();
            } );

            input.addEventListener( 'blur', function () {
                const value = input.value.trim();
                if ( checkbox.checked && value && ! ch.validate( value ) ) {
                    input.classList.add( 'is-invalid' );
                    error.textContent = cfg.i18n?.channelInvalid || 'Please check this value and try again.';
                }
            } );
        } );

        channelsForm.addEventListener( 'submit', function ( event ) {
            event.preventDefault();
            const checked = checkedChannels();
            if ( checked.length === 0 ) {
                channelsHint?.classList.add( 'is-error' );
                return;
            }

            let allValid = true;
            checked.forEach( function ( entry ) {
                const value = entry.parts.input.value.trim();
                if ( ! entry.ch.validate( value ) ) {
                    allValid = false;
                    entry.parts.input.classList.add( 'is-invalid' );
                    entry.parts.error.textContent = cfg.i18n?.channelInvalid || 'Please check this value and try again.';
                }
            } );
            if ( ! allValid ) {
                return;
            }

            capturedChannels = checked.map( function ( entry ) {
                return { channel: entry.ch.key, value: entry.parts.input.value.trim() };
            } );
            saveProgress( { channels: capturedChannels } ); // PO-3102
            channelsContinueBtn.disabled = true;
            channelsContinueBtn.textContent = cfg.i18n?.identitySaved || 'Saved';

            // FR-09 — queue the final confirmation + account-creation screen, same
            // resume pattern as FR-07 queuing FR-08. sequenceEndDestination stays
            // null: nothing to navigate to on completing this screen's own
            // narration — the actual "next step" is the portal redirect the
            // final-continue button triggers, not another panel.
            const loopAlreadyExited = sequenceFinished;
            SCREENS.push( FINAL_SCREEN );
            if ( loopAlreadyExited ) {
                sequenceFinished = false;
                runFallback( avatarIsLive );
            }
        } );
    }

    // FR-09 / PO-3257 — account creation + portal transfer. Luna and this
    // button share window.ihqCoachEvents.register(); redirect:false so we can
    // close the Gary session before navigating. No fail-open: a missing
    // event module or a failed create must surface, not fake a portal session.
    const finalContinueBtn = document.getElementById( 'aicoach-final-continue' );
    const finalError = document.getElementById( 'aicoach-final-error' );

    finalContinueBtn?.addEventListener( 'click', async function () {
        if ( typeof window.ihqCoachEvents?.register !== 'function' ) {
            if ( finalError ) {
                finalError.textContent = cfg.i18n?.accountCreateErr || 'Something went wrong creating your account. Please try again.';
            }
            return;
        }

        finalContinueBtn.disabled = true;
        if ( finalError ) {
            finalError.textContent = '';
        }

        try {
            // PO-3102 — every prior showPanel()/identity/channels save is chained
            // onto this same promise. Without waiting for it here, a still-in-flight
            // save (e.g. this very panel's own "stage: final-continue" save, fired
            // moments ago) could reach the server AFTER account creation already
            // cleared the progress record, recreating an orphaned row for a visitor
            // who's already a real WP user and will never return to this flow.
            await progressSaveChain;
            const captured = capturedIdentity || {};
            const data = await window.ihqCoachEvents.register( {
                firstName: captured.firstName,
                lastName: captured.lastName,
                username: captured.username,
                channels: capturedChannels || undefined,
                language: currentLocale,
                redirect: false,
            } );
            if ( ! data || ! data.success || ! data.redirectUrl ) {
                throw new Error( ( data && data.error ) || 'create-account failed' );
            }

            // Best-effort cleanup, matches Gary's documented session lifecycle —
            // not awaited so it never delays the redirect the visitor is waiting on.
            if ( garySessionId ) {
                fetch( garyCloseUrl( garySessionId ), { method: 'POST', headers: { 'X-WP-Nonce': cfg.nonce } } )
                    .catch( function () {} );
            }

            window.location.href = data.redirectUrl;
        } catch ( error ) {
            console.warn( '[aicoach] account creation failed:', error );
            if ( finalError ) {
                finalError.textContent = cfg.i18n?.accountCreateErr || 'Something went wrong creating your account. Please try again.';
            }
            finalContinueBtn.disabled = false;
        }
    } );

    // FR-17 — time-remaining check (Scenario 29/30) and the entry to FR-18's
    // appointment scheduling. Once the visitor has used up
    // TIME_REMAINING_THRESHOLD_RATIO of their selected tier, the "Time is up?"
    // panel (PO-3109) takes the stage from whichever screen is showing, with two
    // choices: Keep Talking Now carries on exactly where they were, Set an
    // Appointment goes to scheduling. It is not one of SCREENS (it can interrupt any
    // of them) and is never saved as the visitor's stage (TRANSIENT_PANEL_KEYS).
    // Fires once per session (Scenario 30's "does not fire again"); if the visitor
    // has already completed registration by then, the page has already navigated
    // away to the portal (PO-3100's redirect) and this timer is moot.
    const TIME_UP_PANEL_KEY = 'time-up';
    const TIME_UP_RETRY_MS = 150;
    const TIME_UP_ANSWER_POLL_MS = 1000;
    const timeUpPanel = stage.querySelector( '.aicoach-panel[data-panel="' + TIME_UP_PANEL_KEY + '"]' );
    const timeUpChoices = timeUpPanel ? timeUpPanel.querySelectorAll( '.aicoach-timeup-check' ) : [];
    let timeRemainingPromptShown = false;
    let timeRemainingTimer = null;
    // PO-3330's own AC pulls in two different directions here: elapsed-time
    // tracking against the tier must keep counting THROUGH a Q&A exchange
    // (so the underlying countdown below is deliberately a plain
    // window.setTimeout, NOT one of the pausable sequence timers — it must
    // not pause while Ask Sami is open), but the prompt it triggers must
    // wait for an open Q&A to finish before it's shown on top of it. This
    // flag defers just the SHOWING, not the counting.
    let timeRemainingCheckPending = false;
    let timeUpOpen = false;
    let timeUpReturnPanelKey = null;
    let timeUpWasPlaying = false;
    let timeUpClipRestartPending = false; // a language pick arrived while the screen showed
    let timeUpAskSamiWasDisabled = false;

    function openTimeUp() {
        const current = getActivePanel();
        if ( isAnimating || ! current ) {
            // Mid-transition there is no active panel to come back to; try again
            // once it has settled.
            window.setTimeout( openTimeUp, TIME_UP_RETRY_MS );
            return;
        }
        timeUpOpen = true;
        timeUpReturnPanelKey = current.getAttribute( 'data-panel' );
        timeUpWasPlaying = ! video.paused;
        video.pause();
        // Nothing may advance underneath: the timers stop, and the screen loop
        // waits (external hold) until this panel gives the stage back, even if
        // something else resumes the timers in the meantime.
        pauseSequenceTimers();
        setSequenceExternalHold( true );
        // The paused clip would otherwise sit on whatever frame it was on (see
        // ENGR-7051); show her neutral portrait instead.
        avatarWrap.classList.add( 'is-idle' );
        // The Ask Sami panel must not open on top of a decision (the old modal
        // blocked it by z-index).
        if ( askSamiBtn ) {
            timeUpAskSamiWasDisabled = askSamiBtn.disabled;
            askSamiBtn.disabled = true;
        }
        timeUpChoices.forEach( function ( choice ) {
            choice.checked = false;
        } );
        showPanel( TIME_UP_PANEL_KEY );
    }

    // Gives the stage back to the screen the visitor was on and lets the
    // sequence carry on. Everything that makes the page move again waits until
    // that screen is actually back: showPanel() is queued while the Time is up
    // screen is still fading in, and a clip resumed before then would play (with
    // sound) under this screen (CodeRabbit).
    async function closeTimeUp() {
        // Reset first, so a second choice during the fade does nothing.
        timeUpOpen = false;
        const returnPanelKey = timeUpReturnPanelKey;
        timeUpReturnPanelKey = null;
        if ( returnPanelKey ) {
            await showPanel( returnPanelKey );
        }
        avatarWrap.classList.remove( 'is-idle' );
        if ( askSamiBtn ) {
            askSamiBtn.disabled = timeUpAskSamiWasDisabled;
        }
        resumeSequenceTimers();
        setSequenceExternalHold( false );
        // The visitor may have picked another language meanwhile: restart the clip in
        // it (restartCurrentClipForLocale() also plays it and fixes the caption). If
        // there is nothing to restart (the new language resolves to the clip already
        // loaded, or the screen is the live stream), the clip this screen paused must
        // simply carry on (CodeRabbit).
        let restarted = false;
        if ( timeUpClipRestartPending ) {
            timeUpClipRestartPending = false;
            restarted = restartCurrentClipForLocale( currentLocale );
        }
        if ( ! restarted && timeUpWasPlaying ) {
            video.play().catch( function () {} );
        }
    }

    function maybeShowTimeRemainingCheck() {
        if ( timeRemainingPromptShown ) {
            return;
        }
        // PO-3346 (#7) — don't layer the prompt on top of an open Ask Sami
        // panel; wait for it to close (see closePanel() below) instead.
        if ( askSamiWrap && askSamiWrap.classList.contains( 'is-open' ) ) {
            timeRemainingCheckPending = true;
            return;
        }
        // A question can outlast its closed panel: while it is in flight, and while
        // its answer is spoken, its own cleanup resumes the clip it interrupted,
        // which must not happen under this screen. Wait for it to finish.
        if ( isAnswerPending() ) {
            window.setTimeout( maybeShowTimeRemainingCheck, TIME_UP_ANSWER_POLL_MS );
            return;
        }
        timeRemainingPromptShown = true;
        openTimeUp();
    }

    function scheduleTimeRemainingCheck( tierMinutes ) {
        const totalMs = TIER_DURATION_MS[ tierMinutes ];
        if ( ! totalMs ) {
            return;
        }
        window.clearTimeout( timeRemainingTimer );
        timeRemainingTimer = window.setTimeout( maybeShowTimeRemainingCheck, totalMs * TIME_REMAINING_THRESHOLD_RATIO );
    }

    // FR-18 isn't built yet (PO-3109, later steps): until the scheduling screen
    // exists, Set an Appointment carries on like Keep Talking Now so the visitor is
    // never stuck on this panel. Replace the body with the scheduling entry.
    function startAppointmentScheduling() {
        closeTimeUp();
    }

    timeUpChoices.forEach( function ( choice ) {
        choice.addEventListener( 'change', function () {
            if ( ! timeUpOpen || ! choice.checked ) {
                return;
            }
            if ( 'appointment' === choice.value ) {
                startAppointmentScheduling();
                return;
            }
            // Scenario 30 — continue from the current screen with no loss.
            closeTimeUp();
        } );
    } );

    // Open a Gary Coach API session (inc/gary-proxy.php) and return its parsed
    // envelope — { session: { id }, say: { text, video: { session_token } }, ... }.
    // Throws on any failure; the caller decides what to do (fall back).
    async function openGarySession( locale ) {
        console.log( '[aicoach] POST', GARY_SESSION_URL, { locale: locale } );
        let res;
        try {
            res = await fetch( GARY_SESSION_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
                body: JSON.stringify( { locale: locale } ),
            } );
        } catch ( networkError ) {
            console.error( '[aicoach] /coach/session network failure (no response reached the browser):', networkError );
            throw networkError;
        }
        console.log( '[aicoach] /coach/session HTTP', res.status, res.statusText );

        // A 502 from Cloudflare/PHP-FPM returns an HTML error page, not JSON —
        // res.json() would throw a generic, unhelpful SyntaxError. Read as text
        // first so a non-JSON response is logged with its real status/body
        // instead of masking it behind "Unexpected token '<'...".
        const rawBody = await res.text();
        let data;
        try {
            data = JSON.parse( rawBody );
        } catch ( parseError ) {
            console.error( '[aicoach] /coach/session returned non-JSON body (HTTP ' + res.status + '):', rawBody.slice( 0, 500 ) );
            throw new Error( 'Gary session request returned a non-JSON response (HTTP ' + res.status + ').' );
        }

        if ( ! res.ok || ! data.session?.id || ! data.say?.video?.session_token ) {
            console.error( '[aicoach] /coach/session did not return a usable session:', { status: res.status, data: data } );
            throw new Error( data.error || 'Failed to open Gary session.' );
        }
        return data;
    }

    // ENGR-7098 — a closing tab drops the peer connection anyway, but an
    // explicit stop lets Anam end the session without waiting for it to time
    // out. Wrapped because releaseIntroClient is reassigned by start().
    window.addEventListener( 'pagehide', function () {
        releaseIntroClient();
    } );

    // Auto-start on arrival — no tap required (unlike page-portal-poc.php), for a
    // fresh visitor. A returning visitor with saved progress (PO-3102) skips this
    // entirely — see init() below, which decides whether to call start() at all.
    async function start() {
        avatarWrap.dataset.status = 'connecting';
        try {
            const gary = await openGarySession( currentLocale );
            garySessionId = gary.session.id;
            if ( askSamiBtn ) {
                askSamiBtn.disabled = false; // FR-19/PO-3330 — a real session exists now, /message has something to reach
            }

            // disableInputAudio — this page never uses the visitor's microphone
            // (no voice input UI), and omitting it made Anam request mic
            // permission anyway (seen live as a browser permission prompt / a
            // console "Failed to get microphone permission" error). Denying or
            // dismissing that prompt left the stream connected but silent
            // forever, since data-status never reached "live".
            const client = createClient( gary.say.video.session_token, { disableInputAudio: true } );
            let handledClose = false;
            let videoStarted = false; // VIDEO_PLAY_STARTED has been observed firing more than
                                       // once per session (a WebRTC renegotiation, not a real
                                       // second connection) — guard so a repeat event can't
                                       // reset sequenceIndex/the panel out from under an
                                       // already-in-progress runFallback() loop.

            client.addListener( AnamEvent.VIDEO_PLAY_STARTED, async function () {
                if ( videoStarted ) {
                    return;
                }
                videoStarted = true;

                avatarWrap.dataset.status = 'live';
                avatarIsLive = true;

                // Gary's own opening line is real, live content — show it as the
                // intro caption, and give it the same read-or-skip dwell as every
                // other screen before moving on (there's no endOfSpeech event to
                // sync against here — see the top-of-file note on why). Everything
                // after this falls back to the static SCREENS display.
                showPanel( 'intro' );
                const introCaptionEl = getCaptionEl( 'intro' );
                if ( introCaptionEl ) {
                    introCaptionEl.textContent = gary.say.text || SCREENS[ 0 ].script;
                }

                // VIDEO_PLAY_STARTED is the confirmation that the peer connection
                // is actually open — calling client.talk() any earlier (e.g. right
                // after streamToVideoElement() resolves) hits the SDK before the
                // command channel is ready ("sendTalkCommand: peer connection is
                // null"). The session_token alone starts a connected-but-silent
                // stream; talk() is what makes the avatar speak/lip-sync say.text.
                let talkSucceeded = true;
                try {
                    await client.talk( gary.say.text );
                } catch ( talkError ) {
                    talkSucceeded = false;
                    console.warn( '[aicoach] client.talk() failed, avatar stays silent:', talkError );
                }

                if ( talkSucceeded ) {
                    // 25s cap — observed real completion around 9-19s for a
                    // short reply; generous enough to never cut her off, short
                    // enough to never strand a visitor if the event doesn't
                    // arrive. If talk() itself failed there's no speech to
                    // wait for — fall back to the same fixed dwell every
                    // other (non-speaking) screen uses.
                    await waitForSpeechOrSkip( client, 25000 );
                } else {
                    await waitForReadOrSkip();
                }
                sequenceIndex = 1;
                // The intro's avatar seat is freed once a clip actually replaces
                // its stream (playPrerenderedClip()), not here: with no clip
                // rendered the intro stream stays on screen for the static
                // captions, and a stopped stream renders empty.
                runFallback( true );
            } );
            const introCloseHandler = function ( event ) {
                if ( handledClose || sequenceFinished ) {
                    return;
                }
                handledClose = true;
                avatarIsLive = false;
                console.warn( '[aicoach] Sami CONNECTION_CLOSED before sequence finished', event );
                runFallback();
            };
            client.addListener( AnamEvent.CONNECTION_CLOSED, introCloseHandler );
            releaseIntroClient = function () {
                releaseIntroClient = function () {};
                // stopStreaming() emits CONNECTION_CLOSED before it stops the
                // connection; detach the handler first so a release we asked for
                // is not mistaken for a dropped connection (same order as
                // teardownQaClient()).
                client.removeListener( AnamEvent.CONNECTION_CLOSED, introCloseHandler );
                try {
                    client.stopStreaming();
                } catch ( stopError ) {
                    console.warn( '[aicoach] intro client stopStreaming() failed:', stopError );
                }
            };

            await client.streamToVideoElement( AVATAR_VIDEO_ID );
        } catch ( error ) {
            console.warn( '[aicoach] falling back to static intro text:', error );
            // ENGR-7098 (CodeRabbit) — if streamToVideoElement() is what rejected,
            // the client already exists and may hold a half-open connection;
            // free it before the static fallback takes over. A no-op when the
            // failure came earlier (openGarySession() — no client yet).
            releaseIntroClient();
            // openGarySession() may have succeeded (garySessionId set) even though
            // a later step here failed — best-effort close so that session doesn't
            // stay open on Gary's side for no reason. Token-validation failures
            // never reach this with an id set, since openGarySession() throws
            // before returning one.
            if ( garySessionId ) {
                fetch( garyCloseUrl( garySessionId ), { method: 'POST', headers: { 'X-WP-Nonce': cfg.nonce } } )
                    .catch( function () {} );
                // FR-19/PO-3330 — askSamiBtn may already be enabled at this point
                // (start() flips it right after garySessionId is set, before this
                // await). Without clearing both, Ask Sami would stay clickable
                // against a session Gary just closed on his side.
                garySessionId = null;
                if ( askSamiBtn ) {
                    askSamiBtn.disabled = true;
                }
            }
            runFallback();
        }
    }

    // PO-3102 — resume-on-load helpers. Each applies one piece of saved progress
    // to the same state a normal first-time flow would have set by the point the
    // visitor reaches that stage, so everything downstream (identity/channels
    // submit, final-continue) behaves exactly as if they'd just done it this visit.

    function applyResumedTier( tierMinutes ) {
        selectedTierMinutes = tierMinutes;
        tierConfirmed = true;
        elapsedStartedAt = Date.now();
        scheduleTimeRemainingCheck( tierMinutes );
        SCREENS.push( ...getEquityScreensForTier( tierMinutes ), ...getCompetitionScreensForTier( tierMinutes ) );
        tiers.forEach( function ( tier ) {
            const input = tier.querySelector( '.aicoach-tier-check' );
            if ( input && input.getAttribute( 'data-panel' ) === tierMinutes ) {
                input.checked = true;
            }
        } );
        syncSelected();
    }

    function applyResumedIdentity( identity ) {
        capturedIdentity = identity;
        if ( firstNameInput ) {
            firstNameInput.value = identity.firstName || '';
        }
        if ( lastNameInput ) {
            lastNameInput.value = identity.lastName || '';
        }
        if ( usernameInput ) {
            usernameInput.value = identity.username || '';
        }
        updateIdentityContinueState();
        if ( identityContinueBtn && identityFieldsFilled() ) {
            identityContinueBtn.disabled = true;
            identityContinueBtn.textContent = cfg.i18n?.identitySaved || 'Saved';
        }
    }

    function applyResumedChannels( channels ) {
        capturedChannels = channels;
        channels.forEach( function ( entry ) {
            const { checkbox, field, input } = getChannelParts( entry.channel );
            if ( ! checkbox || ! field || ! input ) {
                return;
            }
            checkbox.checked = true;
            field.hidden = false;
            input.value = entry.value;
        } );
        updateChannelsContinueState();
        if ( channelsContinueBtn && channels.length > 0 ) {
            channelsContinueBtn.disabled = true;
            channelsContinueBtn.textContent = cfg.i18n?.identitySaved || 'Saved';
        }
    }

    // The dynamic SCREENS panel keys in the exact order a fresh flow reaches them
    // for a given tier (or with no tier picked yet) — used to find a saved
    // 'stage's resume index without replaying whatever came before it (Scenario
    // 21). Mirrors exactly what the tier-click handler pushes onto SCREENS.
    function dynamicPanelSequence( tierMinutes ) {
        const tierScreens = tierMinutes
            ? [ ...getEquityScreensForTier( tierMinutes ), ...getCompetitionScreensForTier( tierMinutes ) ].map( function ( s ) { return s.panel; } )
            : [];
        return [ 'intro', 'believe-1', 'believe-2', 'home' ].concat( tierScreens );
    }

    // Panels reached only after the dynamic SCREENS sequence ends — plain
    // showPanel() destinations, not part of SCREENS (see finishSequence()).
    const POST_SEQUENCE_PANELS = [ 'identity', 'comm-channels', 'final-continue' ];

    ( async function init() {
        const progress = await loadProgress();

        // FR-12/13's priority order, already anticipated in detectInitialLocale()'s
        // own comment: saved preference → browser locale → English. currentLocale
        // was already set to the browser/English guess above; selectLocale() (not
        // a bare applyLocale() call) also syncs the language dropdown's own
        // is-current marker — skipping it would leave the dropdown showing the
        // browser-guessed language as "current" while the page's actual text and
        // avatar locale are the resumed one. Its own no-op guard (locale ===
        // currentLocale) only short-circuits when the two already agree, which is
        // harmless — the dropdown already matches in that case.
        if ( progress.language ) {
            selectLocale( progress.language );
        }

        if ( progress.tier ) {
            applyResumedTier( progress.tier );
        }
        if ( progress.identity && ( progress.identity.firstName || progress.identity.lastName || progress.identity.username ) ) {
            applyResumedIdentity( progress.identity );
        }
        if ( progress.channels && progress.channels.length ) {
            applyResumedChannels( progress.channels );
        }
        if ( progress.qaHistory && progress.qaHistory.length && applyResumedQaHistory ) {
            applyResumedQaHistory( progress.qaHistory );
        }

        const stageKey = progress.stage;
        if ( ! stageKey || stageKey === 'intro' ) {
            // Fresh visitor, or never got past the live intro last time — there's
            // no meaningful place to resume "into" (the intro itself is live,
            // real-time content, not something worth resuming mid-sentence), so
            // this is a normal start.
            start();
            return;
        }

        if ( POST_SEQUENCE_PANELS.indexOf( stageKey ) !== -1 ) {
            // Mark the dynamic SCREENS queue as fully consumed so a LATER resume
            // of runFallback() — identity/channels submit's own "loop already
            // exited" pattern, e.g. if the visitor still needs to submit channels
            // from here — only plays newly queued screens (comm-channels/
            // final-continue) instead of replaying the whole sequence from index 0.
            sequenceIndex = SCREENS.length;
            if ( stageKey !== 'identity' ) {
                // Matches what a real identity-form submit already set to reach
                // this far, so finishSequence() (once FINAL_SCREEN finishes)
                // doesn't bounce back to the identity panel.
                sequenceEndDestination = null;
            }
            sequenceFinished = true;
            showPanel( stageKey );
            return;
        }

        const order = dynamicPanelSequence( progress.tier );
        const idx = order.indexOf( stageKey );
        if ( idx === -1 ) {
            // Unrecognized saved stage (e.g. a panel key from an older build) —
            // don't get stuck on it, fall back to a normal fresh start.
            start();
            return;
        }

        // No approved copy exists yet for "this is a continuation" dialogue
        // (Scenario 21) — silent resume for v1: no live Gary session, straight to
        // the exact stage with whatever that panel already shows. runFallback()'s
        // own loop (unchanged) shows + plays sequenceIndex onward, so screens
        // before this one are never replayed.
        sequenceIndex = idx;
        runFallback( false );
    }() );
}

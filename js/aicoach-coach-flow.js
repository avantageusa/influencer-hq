/**
 * AI Coach — page-home-aicoach.php screen sequence (Sami avatar + panels).
 * Config expected on window.AICOACH_SAMI (see inc/anam-proxy.php enqueue).
 */
import { createClient, AnamEvent } from 'https://cdn.jsdelivr.net/npm/@anam-ai/js-sdk@4/+esm';

/*
 * FR-01 + FR-02 — on arrival, the coach speaks a fixed sequence of screens
 * (intro, then two "We Believe" screens) verbatim via Sami's existing Anam
 * avatar (same session-token proxy page-portal-poc.php already uses), with
 * on-screen captions built from the SDK's own MESSAGE_STREAM_EVENT_RECEIVED
 * content stream (Anam's documented captioning pattern — append
 * role="persona" chunks in arrival order). Each screen advances to the next
 * when its line finishes, or immediately on a visitor tap/click (FR-02's
 * "narration timing or visitor tap/click"); after the last screen the page
 * hands off to the existing tier-selection panel. Unlike page-portal-poc.php
 * this is NOT tap-to-start — the AC requires the video to begin on load.
 */
const FADE_MS = 400;
const SCREEN_ADVANCE_DELAY_MS = 1200; // natural pause after a line finishes before the panel swaps
const FALLBACK_READ_MS = 9000;        // per-screen dwell for the static-text fallback (no speech to sync against)
const AVATAR_VIDEO_ID = 'aicoach-avatar-video';

const SCREENS = [
    {
        panel: 'intro',
        script: "Hello. I'm Sami. Your Executive Coach. Welcome to InfluencerHQ. My job is to help you understand one simple idea. We believe influencers who help build our company should have the opportunity to earn meaningful equity. Over the next few minutes, I'll show you why we believe that… how successful people have benefited from ownership… and how InfluencerHQ can help you begin your own journey. You don't need to remember everything today. I'll always be here to answer your questions… continue exactly where we leave off… or meet with you whenever you'd like. Let's begin.",
    },
    {
        panel: 'believe-1',
        script: "Every successful company begins with a set of beliefs. Here's one of ours. We believe influence is about more than creating content. It's about creating lasting value. Most influencers are rewarded for what they do today. We believe influencers who help build tomorrow should also have the opportunity to benefit from what they help create. That's why meaningful equity is at the heart of InfluencerHQ. Let me show you what I mean.",
    },
    {
        panel: 'believe-2',
        script: "There's another belief that's just as important. We believe the people who help create value should have the opportunity to share in that value. Not someday… From the very beginning. That's very different from the traditional way most influencers are rewarded. When meaningful ownership is available, it can become worth far more than a one-time payment. This isn't just our opinion. Let me show you a few real examples.",
    },
];

const cfg = window.AICOACH_SAMI || {};
const SESSION_TOKEN_URL = cfg.restBase + '/session-token';
const PERSONA_PREVIEW_URL = cfg.restBase + '/persona-preview';

const stage = document.getElementById('aicoach-stage');
const avatarWrap = document.getElementById('aicoach-avatar-wrap');
const video = document.getElementById('aicoach-avatar-video');
const portrait = document.getElementById('aicoach-portrait');
const unmuteBtn = document.getElementById('aicoach-unmute');

function getCaptionEl( panelKey ) {
    return stage.querySelector( '.aicoach-panel[data-panel="' + panelKey + '"] .aicoach-caption' );
}

if ( stage && avatarWrap ) {
    const tiers = stage.querySelectorAll( '.aicoach-tier' );
    let isAnimating = false;

    function syncSelected() {
        tiers.forEach( function ( tier ) {
            const input = tier.querySelector( '.aicoach-tier-check' );
            tier.classList.toggle( 'is-selected', Boolean( input && input.checked ) );
        } );
    }

    function getActivePanel() {
        return stage.querySelector( '.aicoach-panel.is-active' );
    }

    function showPanel( panelKey ) {
        const next = stage.querySelector( '.aicoach-panel[data-panel="' + panelKey + '"]' );
        const current = getActivePanel();
        if ( ! next || ! current || next === current || isAnimating ) {
            return;
        }

        isAnimating = true;
        stage.style.minHeight = current.offsetHeight + 'px';

        current.classList.remove( 'is-active' );
        current.setAttribute( 'aria-hidden', 'true' );

        window.setTimeout( function () {
            next.classList.add( 'is-active' );
            next.setAttribute( 'aria-hidden', 'false' );
            stage.style.minHeight = next.offsetHeight + 'px';

            window.setTimeout( function () {
                stage.style.minHeight = '';
                isAnimating = false;
            }, FADE_MS );
        }, FADE_MS );
    }

    // Use click so a pre-checked tier (e.g. 2 minutes) still opens its story.
    tiers.forEach( function ( tier ) {
        const input = tier.querySelector( '.aicoach-tier-check' );
        if ( ! input ) {
            return;
        }

        tier.addEventListener( 'click', function () {
            input.checked = true;
            syncSelected();
            const panelKey = input.getAttribute( 'data-panel' );
            if ( panelKey ) {
                showPanel( panelKey );
            }
        } );
    } );

    syncSelected();

    const sleep = ( ms ) => new Promise( ( resolve ) => window.setTimeout( resolve, ms ) );

    let sequenceIndex = 0;
    let sequenceFinished = false;
    let skipCurrent = null; // set while a screen's line is in flight; a tap calls this to advance early

    function finishSequence() {
        if ( sequenceFinished ) {
            return;
        }
        sequenceFinished = true;
        showPanel( 'home' );
    }

    // Best-effort: use Sami's own portrait as the idle face so it matches the live video.
    ( async function loadPreview() {
        try {
            const res = await fetch( PERSONA_PREVIEW_URL, { headers: { 'X-WP-Nonce': cfg.nonce } } );
            if ( ! res.ok ) {
                return;
            }
            const data = await res.json();
            if ( data.portraitUrl && portrait ) {
                portrait.src = data.portraitUrl;
            }
        } catch ( error ) {
            // No portrait available — the placeholder image stays.
        }
    }() );

    // Text has nothing to sync against here, so each screen is shown in full
    // and paced by an estimated reading dwell rather than word-by-word reveal.
    // Resumes from sequenceIndex, so a mid-sequence connection drop picks up
    // wherever the live playback left off instead of restarting from the intro.
    async function runFallback() {
        avatarWrap.dataset.status = 'idle';
        for ( ; sequenceIndex < SCREENS.length; sequenceIndex++ ) {
            const screen = SCREENS[ sequenceIndex ];
            showPanel( screen.panel );
            const captionEl = getCaptionEl( screen.panel );
            if ( captionEl ) {
                captionEl.textContent = screen.script;
            }
            await sleep( FALLBACK_READ_MS );
        }
        finishSequence();
    }

    // Speaks one screen's line and resolves on endOfSpeech (or an early tap-to-advance).
    function speakScreen( client, screen ) {
        const captionEl = getCaptionEl( screen.panel );
        if ( captionEl ) {
            captionEl.textContent = '';
        }

        let activeUtteranceId = null;
        return new Promise( ( resolve ) => {
            let settled = false;
            const finish = () => {
                if ( settled ) {
                    return;
                }
                settled = true;
                skipCurrent = null;
                client.removeListener( AnamEvent.MESSAGE_STREAM_EVENT_RECEIVED, onEvent );
                resolve();
            };

            // Anam's documented captioning pattern: consecutive persona chunks
            // sharing an utteranceId are appended in arrival order to build the
            // live caption; endOfSpeech on that utterance marks completion.
            function onEvent( evt ) {
                if ( ! evt || evt.role !== 'persona' ) {
                    return;
                }
                if ( activeUtteranceId === null ) {
                    activeUtteranceId = evt.utteranceId;
                }
                if ( evt.utteranceId !== activeUtteranceId ) {
                    return;
                }
                if ( evt.content && captionEl ) {
                    captionEl.textContent += evt.content;
                }
                if ( evt.endOfSpeech ) {
                    finish();
                }
            }

            skipCurrent = finish;
            client.addListener( AnamEvent.MESSAGE_STREAM_EVENT_RECEIVED, onEvent );
            client.talk( screen.script ).catch( finish );
        } );
    }

    async function runSequence( client ) {
        for ( ; sequenceIndex < SCREENS.length; sequenceIndex++ ) {
            const screen = SCREENS[ sequenceIndex ];
            showPanel( screen.panel );
            await speakScreen( client, screen );
            if ( sequenceFinished ) {
                return; // the fallback path already took over mid-sequence
            }
            await sleep( SCREEN_ADVANCE_DELAY_MS );
        }
        finishSequence();
    }

    // Tap/click to skip ahead — FR-02's "narration timing or visitor tap/click".
    // Only applies to the We Believe screens (not the FR-01 intro), and never
    // hijacks clicks on the tier-selection controls once the sequence is done.
    stage.addEventListener( 'click', function ( event ) {
        if ( isAnimating ) {
            return; // avoid desyncing the caption/panel if tapped mid-fade
        }
        const current = SCREENS[ sequenceIndex ];
        if ( ! current || current.panel === 'intro' ) {
            return;
        }
        if ( event.target.closest( '.aicoach-tier' ) ) {
            return;
        }
        if ( skipCurrent ) {
            skipCurrent();
        }
    } );

    unmuteBtn?.addEventListener( 'click', function () {
        video.muted = ! video.muted;
        unmuteBtn.setAttribute( 'aria-pressed', String( ! video.muted ) );
        avatarWrap.dataset.muted = String( video.muted );
    } );

    // Auto-start on arrival — no tap required (unlike page-portal-poc.php).
    ( async function start() {
        avatarWrap.dataset.status = 'connecting';
        try {
            const res = await fetch( SESSION_TOKEN_URL, { method: 'POST', headers: { 'X-WP-Nonce': cfg.nonce } } );
            const data = await res.json();
            if ( ! res.ok || ! data.sessionToken ) {
                throw new Error( data.error || 'Failed to start Sami.' );
            }

            const client = createClient( data.sessionToken );
            let handledClose = false;

            client.addListener( AnamEvent.VIDEO_PLAY_STARTED, function () {
                avatarWrap.dataset.status = 'live';
                runSequence( client );
            } );
            client.addListener( AnamEvent.CONNECTION_CLOSED, function ( event ) {
                if ( handledClose || sequenceFinished ) {
                    return;
                }
                handledClose = true;
                console.warn( '[aicoach] Sami CONNECTION_CLOSED before sequence finished', event );
                runFallback();
            } );

            await client.streamToVideoElement( AVATAR_VIDEO_ID );
        } catch ( error ) {
            console.warn( '[aicoach] falling back to static intro text:', error );
            runFallback();
        }
    }() );
}

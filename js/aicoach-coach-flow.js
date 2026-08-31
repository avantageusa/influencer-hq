/**
 * AI Coach — page-home-aicoach.php screen sequence (Sami avatar + panels).
 * Config expected on window.AICOACH_SAMI (see inc/anam-proxy.php enqueue).
 */
import { createClient, AnamEvent } from 'https://cdn.jsdelivr.net/npm/@anam-ai/js-sdk@4/+esm';

/*
 * FR-01 + FR-02 + FR-03 — on arrival, the coach speaks a fixed sequence of
 * screens (intro, two "We Believe" screens, then the time-selection pitch —
 * this last one spoken over the existing tier-selection UI rather than a
 * separate screen) verbatim via Sami's existing Anam avatar (same session-
 * token proxy page-portal-poc.php already uses), with on-screen captions
 * built from the SDK's own MESSAGE_STREAM_EVENT_RECEIVED content stream
 * (Anam's documented captioning pattern — append role="persona" chunks in
 * arrival order). Each screen advances to the next when its line finishes,
 * or immediately on a visitor tap/click (FR-02's "narration timing or
 * visitor tap/click"); confirming a time tier (FR-03) is the same kind of
 * early-advance, plus it marks the sequence done and starts elapsed-time
 * tracking. Unlike page-portal-poc.php this is NOT tap-to-start — the AC
 * requires the video to begin on load.
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
    {
        // FR-03 — spoken over the existing tier-selection UI (the "home" panel), not a
        // separate screen: the AC has the coach's script play while the three time
        // options are already on screen, not before them.
        panel: 'home',
        script: "Now it's your turn. How much time would you like to spend with me today? Whether you have two minutes… five minutes… or ten minutes… I'll make sure our time together is worthwhile. Simply choose the amount of time that works best for you… and I'll personally guide you every step of the way. And remember… if we don't finish today… we'll simply continue exactly where we leave off. Go ahead… choose the amount of time that's right for you.",
    },
];

// FR-05 — equity examples. Which of these play, and in what order, depends on
// the tier confirmed in FR-03 (see getEquityScreensForTier below); they are
// appended to SCREENS at that point rather than being fixed here upfront.
const EQUITY_SCREENS = {
    magic: {
        panel: 'equity-magic',
        script: "Basketball star Magic Johnson was offered one of the greatest ownership opportunities in history. Instead… he accepted a traditional endorsement. That decision has been estimated to have cost him approximately $5.4 billion in ownership value. No one can predict the future. Not every ownership opportunity succeeds. But when the right ownership opportunity comes along… it can become worth far more than immediate cash. Today… for the first time… influencers are beginning to receive similar ownership opportunities. Let's look at one.",
    },
    alix: {
        panel: 'equity-alix',
        script: "Influencer Alix Earle made a different decision. Instead of accepting only a traditional cash sponsorship… she negotiated an ownership opportunity with Poppi. Less than three years later… PepsiCo acquired Poppi for nearly $2 billion. Her story reminds us that ownership opportunities are no longer limited to athletes, entertainers, or business leaders. Today… influencers also have the opportunity to think beyond immediate cash… and participate in the long-term value they help create. Now… let's look at an international example.",
    },
    bts: {
        panel: 'equity-bts',
        script: "International music group BTS also recognized the power of ownership. Instead of relying only on traditional compensation… they also participated in the long-term value created by what they helped build. Their ownership became worth hundreds of millions of dollars. The lesson isn't about basketball… or social media… or music. It's about recognizing the right ownership opportunity when it comes along. That's exactly why InfluencerHQ was created. Now… let me show you what you can accomplish in just a few minutes.",
    },
};

// FR-05 — Scenario 7/8: 10-minute tier gets all three in order; 5- and
// 2-minute tiers get BTS only.
function getEquityScreensForTier( tierMinutes ) {
    if ( tierMinutes === '10' ) {
        return [ EQUITY_SCREENS.magic, EQUITY_SCREENS.alix, EQUITY_SCREENS.bts ];
    }
    return [ EQUITY_SCREENS.bts ];
}

// FR-06 — competition types explained, no selection requested. 5- and 10-minute
// tiers only; the 2-minute tier skips this section entirely (Scenario 9).
// NOTE: the Private screen's closing/transition line is not yet approved copy
// (the ticket explicitly removed the old "let's choose one" close and flags the
// replacement as pending) — using the given text verbatim, without inventing a
// transition sentence of our own.
const COMPETITION_SCREENS = {
    world: {
        panel: 'competition-world',
        script: "Now let's look at the first way many influencers choose to begin. It's called a World Competition. You and your followers participate together… while competing against other influencers and their communities from around the world. InfluencerHQ already provides the competition format. You don't have to create anything from scratch. Later… inside your Coaching Center… I'll explain exactly how it works and help you decide whether it's the right place for you to begin. Now… let's look at another option.",
    },
    community: {
        panel: 'competition-community',
        script: "Many influencers choose to begin with a Community Competition. It's a simple way to bring together the followers who already support you. Your community stays together… encourages one another… and enjoys participating as a team. Again… InfluencerHQ already provides the competition format. I'll help you get everything set up… step by step. If building your own community first feels right… this may be the perfect place to begin. There's one more option I'd like to show you.",
    },
    private: {
        panel: 'competition-private',
        script: "The third option is called a Private Challenge. It allows you and your followers… to compete with another influencer and their community… someone you already know. Many influencers enjoy Private Challenges because they create friendly competition… encourage engagement… and bring two communities together. Like every competition on InfluencerHQ… the format is already provided. When we continue into your Coaching Center… I'll help you decide whether this is the right place to begin.",
    },
};

function getCompetitionScreensForTier( tierMinutes ) {
    if ( tierMinutes === '2' ) {
        return [];
    }
    return [ COMPETITION_SCREENS.world, COMPETITION_SCREENS.community, COMPETITION_SCREENS.private ];
}

// FR-08 — communication channels. One screen, same for every tier, queued once
// FR-07's identity form succeeds (see the identity submit handler below).
const COMM_CHANNELS_SCREEN = {
    panel: 'comm-channels',
    script: "Before we continue… I'd like to ask one small favor. Please tell me the communication methods you actually use. I'll use them to answer your questions… send reminders… share personalized reports… help you prepare competitions… and continue coaching you as new opportunities become available. Please choose the methods you genuinely use. That way… I'll always know the best way to stay in touch.",
};

// FR-08 — one entry per channel. KakaoTalk/Telegram/WeChat/Zalo's contact-detail
// format is marked "TBD" in the ticket (format/Braze attribute not yet
// confirmed), so those four only require a non-empty value rather than an
// invented strict pattern — Email/SMS/WhatsApp/Line have concrete formats given
// in the ticket, so those get real validation.
const CHANNELS = [
    { key: 'email', validate: ( v ) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( v ) },
    { key: 'kakaotalk', validate: ( v ) => v.length > 0 }, // TBD format
    { key: 'line', validate: ( v ) => /^U[0-9a-fA-F]{32}$/.test( v ) },
    { key: 'sms', validate: ( v ) => /^\+[1-9]\d{7,14}$/.test( v ) },
    { key: 'telegram', validate: ( v ) => v.length > 0 }, // TBD format
    { key: 'wechat', validate: ( v ) => v.length > 0 },   // TBD format
    { key: 'whatsapp', validate: ( v ) => /^\+[1-9]\d{7,14}$/.test( v ) },
    { key: 'zalo', validate: ( v ) => v.length > 0 },     // TBD format
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

    let pendingPanelKey = null; // a showPanel() call that arrived mid-transition; applied once the current one settles

    function showPanel( panelKey ) {
        const next = stage.querySelector( '.aicoach-panel[data-panel="' + panelKey + '"]' );
        if ( ! next ) {
            return;
        }

        if ( isAnimating ) {
            // Don't silently drop this — e.g. FR-08's screen gets queued right after
            // FR-07's own showPanel('identity') call, well within the 800ms fade
            // window on a fast/automated submit. Remember the latest request and
            // apply it once the in-flight transition finishes.
            pendingPanelKey = panelKey;
            return;
        }

        const current = getActivePanel();
        if ( ! current || next === current ) {
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

                if ( pendingPanelKey && pendingPanelKey !== panelKey ) {
                    const queued = pendingPanelKey;
                    pendingPanelKey = null;
                    showPanel( queued );
                } else {
                    pendingPanelKey = null;
                }
            }, FADE_MS );
        }, FADE_MS );
    }

    const sleep = ( ms ) => new Promise( ( resolve ) => window.setTimeout( resolve, ms ) );

    let sequenceIndex = 0;
    let sequenceFinished = false;
    let skipCurrent = null; // set while a screen's line is in flight; a tap calls this to advance early

    // FR-03 — timestamp + chosen tier, captured the moment the visitor confirms a
    // selection. This is only the starting mark; FR-17 (time-remaining check) is a
    // separate story and owns the actual countdown/prompt logic built on top of it.
    // Deliberately in-memory only, not persisted — cross-session resume is FR-11's
    // job (Luna), not this one's.
    let elapsedStartedAt = null;
    let selectedTierMinutes = null;
    let tierConfirmed = false; // FR-03 confirm is one-time; ignore further tier clicks after that
    let activeClient = null;   // set once Anam connects; lets a late tier click resume a finished live sequence
    let usingFallback = false; // true once runFallback has taken over, so a late tier click resumes the right runner

    // Where finishSequence() navigates once the current SCREENS queue is fully
    // consumed — this changes as later FRs queue more content after their own
    // predecessor's success handler runs. FR-07 is the first destination (no
    // spoken script of its own); FR-08's success handler clears this back to
    // null since nothing is built after comm-channels yet. Without this, calling
    // finishSequence() a second time (after FR-08's one queued screen finishes)
    // would incorrectly snap back to 'identity' instead of just stopping.
    let sequenceEndDestination = 'identity';

    function finishSequence() {
        if ( sequenceFinished ) {
            return;
        }
        sequenceFinished = true;
        // No coach script exists for identity capture (unlike every screen
        // before it) or anything after it yet, so those steps aren't part of
        // SCREENS/speakScreen — they're plain showPanel() destinations reached
        // once whatever's currently queued in SCREENS runs out. Which
        // destination that is changes as later stories queue more content (see
        // sequenceEndDestination); null means nothing built yet, just stop.
        if ( sequenceEndDestination ) {
            showPanel( sequenceEndDestination );
        }
    }

    // Use click so a pre-checked tier (e.g. 2 minutes) still opens its story. A click
    // is also the FR-03 "confirm" action: it ends the coach's time-selection line
    // early if she's still mid-sentence (same tap-to-advance mechanism as the We
    // Believe screens), then queues that tier's FR-05 equity examples and (for
    // 5/10-minute tiers) FR-06 competition-type screens onto SCREENS — runSequence's
    // own loop picks them up and shows them in the usual way. If
    // that loop already finished by the time the visitor clicks (they took a
    // moment to decide), nothing is left running to notice the new screens, so
    // resume whichever runner (live or fallback) was active.
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
            const loopAlreadyExited = sequenceFinished;
            SCREENS.push( ...getEquityScreensForTier( panelKey ), ...getCompetitionScreensForTier( panelKey ) );

            if ( skipCurrent ) {
                skipCurrent();
            }

            if ( loopAlreadyExited ) {
                sequenceFinished = false;
                if ( usingFallback ) {
                    runFallback();
                } else if ( activeClient ) {
                    runSequence( activeClient );
                }
            }
        } );
    } );

    syncSelected();

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
        usingFallback = true;
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

    // Tap/click to skip ahead — FR-02's "narration timing or visitor tap/click",
    // covers the We Believe screens and the FR-03 time-selection narration (not
    // the FR-01 intro). Tier clicks have their own handler above and are excluded
    // here so this listener never double-handles them.
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
            identityContinueBtn.disabled = true;
            identityContinueBtn.textContent = cfg.i18n?.identitySaved || 'Saved';

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
                if ( usingFallback ) {
                    runFallback();
                } else if ( activeClient ) {
                    runSequence( activeClient );
                }
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
            channelsContinueBtn.disabled = true;
            channelsContinueBtn.textContent = cfg.i18n?.identitySaved || 'Saved';
            // FR-09 (account creation) is the next story and isn't built yet —
            // nothing further to navigate to, same as FR-07 before this story existed.
        } );
    }

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
            activeClient = client;
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

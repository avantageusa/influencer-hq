/**
 * AI Coach — page-home-aicoach.php screen sequence (Sami avatar + panels).
 * Config expected on window.AICOACH_SAMI (see inc/anam-proxy.php enqueue).
 */
import { createClient, AnamEvent } from 'https://cdn.jsdelivr.net/npm/@anam-ai/js-sdk@4/+esm';

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
 * a reading dwell, or immediately on a visitor tap/click (FR-02's
 * "narration timing or visitor tap/click"); confirming a time tier (FR-03)
 * is the same kind of early-advance, plus it marks the sequence done and
 * starts elapsed-time tracking. Unlike page-portal-poc.php this is NOT
 * tap-to-start — the AC requires the video to begin on load.
 */
const FADE_MS = 400;
const FALLBACK_READ_MS = 9000; // per-screen dwell for the static-text fallback (no speech to sync against)
const AVATAR_VIDEO_ID = 'aicoach-avatar-video';

// FR-17 — trigger the time-remaining check once this proportion of the
// selected tier's total duration has elapsed. The ticket explicitly says the
// real threshold "requires confirmation" — 0.8 (80%) is a placeholder pending
// that, not an approved value. Adjust here once it's confirmed.
const TIME_REMAINING_THRESHOLD_RATIO = 0.8;
const TIER_DURATION_MS = { '2': 2 * 60 * 1000, '5': 5 * 60 * 1000, '10': 10 * 60 * 1000 };

// FR-13 — manual language selector (Scenario 24/25). Native labels are the ones
// given verbatim in the ticket itself ("Proposed native-language labels, subject to
// localisation review") — not invented here. Locale codes match Gary's
// approved_languages exactly (confirmed via GET /coach/v1/health).
const SUPPORTED_LOCALES = [
    { code: 'en', nativeLabel: 'English' },
    { code: 'zh', nativeLabel: '普通话' },
    { code: 'yue', nativeLabel: '廣東話' },
    { code: 'ja', nativeLabel: '日本語' },
    { code: 'ko', nativeLabel: '한국어' },
    { code: 'th', nativeLabel: 'ไทย' },
    { code: 'vi', nativeLabel: 'Tiếng Việt' },
];

// English is the real, approved copy — source of truth for translation. The other
// six languages are deliberately left EMPTY, not filled with invented/machine
// translations: no approved copy exists for them yet. t() below falls back to
// English for any missing key. Fill these in only once real, PO-approved
// translations are supplied — same "never invent unapproved copy" rule this whole
// epic has followed for every other TBD piece of content.
const I18N_EN = {
    belief: 'We believe conversations should be easy.',
    unmuteLabel: "Turn on Sami's voice",
    weBelieve: 'We Believe',
    tierGroupLabel: 'Conversation length',
    'tier-2-duration': '2 minutes',
    'tier-2-item-0': 'Introduction to Competition',
    'tier-2-item-1': 'Choose Communication Method',
    'tier-5-duration': '5 minutes',
    'tier-5-item-0': 'Introduction to Competition',
    'tier-5-item-1': 'Choose Communication Method',
    'tier-5-item-2': 'Full Competition Details',
    'tier-10-duration': '10 minutes',
    'tier-10-item-0': 'Introduction to Competition',
    'tier-10-item-1': 'Choose Communication Method',
    'tier-10-item-2': 'Full Competition Details',
    'tier-10-item-3': 'Setup your First Competition to Earn Equity',
    'tier-10-item-4': 'Help with Creating Posts for Your Followers',
    magicAdidas: 'Adidas $100k cash',
    magicNike: "Nike's 11 cent stock",
    magicNikeWorth: 'Now worth 5.4 billion',
    alixPoppi: 'Said yes to an ownership-based partnership with Poppi',
    turnedDownCash: 'Turned down cash',
    btsOwnership: 'Said yes to shared ownership, valued at 103.6 million',
    worldCompetitionName: 'World Competition',
    youFollowers: 'You + Followers',
    versus: 'versus',
    allInfluencersFollowers: 'All Influencers + Followers',
    communityCompetitionName: 'Community Competition',
    communityCompetitionFormat: 'Weekly competition for your followers only',
    privateChallengeName: 'Private Challenge',
    friendFollowers: 'An Influencer Friend + Followers',
    identityTitle: "Let's Start The Conversation",
    identitySubtitle: 'Let us know who you are',
    firstName: 'First Name',
    lastName: 'Last Name',
    username: 'Username',
    continue: 'Continue',
    channelsHint: 'Please select at least one communication method.',
    letsContinue: "Let's Continue",
    timeCheckText: "Looks like your selected time is almost up. Do you have a few more minutes to finish?",
    yes: 'Yes',
    no: 'No',
    // Mirrors $aicoach_channels in page-home-aicoach.php — kept in sync by hand,
    // there's no single shared source between PHP and this file for it.
    'channel-email-label': 'Email',
    'channel-email-inputLabel': 'Email address',
    'channel-email-placeholder': 'you@example.com',
    'channel-kakaotalk-label': 'KakaoTalk',
    'channel-kakaotalk-inputLabel': 'KakaoTalk ID or phone number',
    'channel-kakaotalk-placeholder': '',
    'channel-line-label': 'Line',
    'channel-line-inputLabel': 'Line ID',
    'channel-line-placeholder': 'U0123456789abcdef0123456789abcdef',
    'channel-sms-label': 'SMS',
    'channel-sms-inputLabel': 'Phone number',
    'channel-sms-placeholder': '+66812345678',
    'channel-telegram-label': 'Telegram',
    'channel-telegram-inputLabel': 'Telegram username or chat ID',
    'channel-telegram-placeholder': '@username',
    'channel-wechat-label': 'WeChat',
    'channel-wechat-inputLabel': 'WeChat ID',
    'channel-wechat-placeholder': '',
    'channel-whatsapp-label': 'WhatsApp',
    'channel-whatsapp-inputLabel': 'Phone number',
    'channel-whatsapp-placeholder': '+66812345678',
    'channel-zalo-label': 'Zalo',
    'channel-zalo-inputLabel': 'Phone number or Zalo ID',
    'channel-zalo-placeholder': '',
};

const I18N_TRANSLATIONS = { en: I18N_EN, zh: {}, yue: {}, ja: {}, ko: {}, th: {}, vi: {} };

function t( locale, key ) {
    const table = I18N_TRANSLATIONS[ locale ] || {};
    return table[ key ] || I18N_EN[ key ] || '';
}

// Walks every data-i18n / data-i18n-attr element and applies the given locale's
// copy (falling back to English per t() above). Static text/attributes only —
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
// (Scenario 22/23). The ticket's priority order is: saved Luna preference →
// browser locale → English. Only the last two are implemented here — Luna
// session persistence (PO-3102) doesn't exist yet, so there's no saved
// preference to read. Revisit this once PO-3102 ships.
function detectInitialLocale() {
    const supportedCodes = SUPPORTED_LOCALES.map( function ( loc ) { return loc.code; } );
    const browserTags = ( navigator.languages && navigator.languages.length )
        ? navigator.languages
        : [ navigator.language || '' ];

    for ( let i = 0; i < browserTags.length; i++ ) {
        const tag = ( browserTags[ i ] || '' ).toLowerCase();
        if ( ! tag ) {
            continue;
        }
        const primary = tag.split( '-' )[ 0 ];
        // Cantonese has no primary subtag browsers actually report — "yue" is
        // valid BCP 47 but essentially never seen in the wild. Hong Kong/Macau
        // region on "zh" is the closest practical signal; any other "zh" region
        // (or bare "zh") is treated as Mandarin, matching SUPPORTED_LOCALES.
        if ( primary === 'yue' ) {
            return 'yue';
        }
        if ( primary === 'zh' && /^zh(?:-[a-z]{4})*-(?:hk|mo)(?:-|$)/.test( tag ) ) {
            return 'yue';
        }
        if ( supportedCodes.indexOf( primary ) !== -1 ) {
            return primary;
        }
    }
    return 'en';
}

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
// E.164 format (e.g. +66812345678) — shared by every channel whose ticket-given
// contact detail is explicitly a phone number, so the pattern isn't duplicated
// per channel (review nit on PO-3099/#11). Telegram isn't included here even
// though it can carry a phone number in practice — the ticket itself defines
// its format as "numeric ID or @username (TBD)", not phone, so it keeps the
// lenient non-empty check until that's confirmed one way or the other.
const isValidPhoneNumber = ( v ) => /^\+[1-9]\d{7,14}$/.test( v );

const CHANNELS = [
    { key: 'email', validate: ( v ) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( v ) },
    { key: 'kakaotalk', validate: ( v ) => v.length > 0 }, // TBD format
    { key: 'line', validate: ( v ) => /^U[0-9a-fA-F]{32}$/.test( v ) },
    { key: 'sms', validate: isValidPhoneNumber },
    { key: 'telegram', validate: ( v ) => v.length > 0 }, // TBD format — see note above
    { key: 'wechat', validate: ( v ) => v.length > 0 },   // TBD format
    { key: 'whatsapp', validate: isValidPhoneNumber },
    { key: 'zalo', validate: ( v ) => v.length > 0 },     // TBD format
];

// FR-09 — final confirmation, queued once FR-08's comm-channels succeeds.
const FINAL_SCREEN = {
    panel: 'final-continue',
    script: "Perfect. Thank you. I now know how you'd like us to stay connected. Everything is ready. From this point forward… I'll continue guiding you inside your personal Coaching Center. That's where we'll review your options… answer your questions… and take the next steps together. Remember… you don't have to learn everything today. We'll continue exactly where we leave off. When you're ready… Let's Continue.",
};

const cfg = window.AICOACH_SAMI || {};
// inc/gary-proxy.php's routes live under the same ihq/v1 namespace FR-07/09's
// identity endpoints already use — no new PHP localization needed for this.
const GARY_SESSION_URL = cfg.identityRestBase + '/coach/session';
const garyCloseUrl = ( sessionId ) => cfg.identityRestBase + '/coach/' + encodeURIComponent( sessionId ) + '/close';
const PERSONA_PREVIEW_URL = cfg.restBase + '/persona-preview';

// PO-3062 pre-rendered clips — SCREENS panel name -> Gary's own segment key
// (see inc/aicoach-prerender.php's ihq_aicoach_prerender_panel_map(), kept
// in sync with this by hand; the two naming schemes predate each other).
// "competition-world"/"competition-community"/"competition-private" are
// deliberately not mapped — Gary's script has one combined "competitions"
// segment where this page shows three separate panels, and there's no
// single clip that fits all three yet.
const PRERENDERED_PANEL_MAP = {
    'believe-1': 'we_believe_1',
    'believe-2': 'we_believe_2',
    home: 'time_selection',
    'equity-magic': 'magic_johnson',
    'equity-alix': 'alix_earle',
    'equity-bts': 'bts',
};

function getPrerenderedUrl( panelKey ) {
    const segmentKey = PRERENDERED_PANEL_MAP[ panelKey ];
    return segmentKey ? ( cfg.prerenderedVideos || {} )[ segmentKey ] || null : null;
}

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

    // FR-13 — language selector, injected into the shared header's own nav row
    // rather than editing template-parts/portal-header.php directly. That file
    // already has a .header-lang-wrap element, but it belongs to a different,
    // unrelated feature (the site-wide ElevenLabs concierge widget — see its git
    // history) with its own mismatched language list; reusing or editing it risks
    // breaking that other feature. This script only ever runs on this one page
    // (see ihq_aicoach_enqueue_coach_flow()'s is_page_template check), so injecting
    // here is safe without any extra page check.
    let currentLocale = detectInitialLocale();

    function selectLocale( locale ) {
        if ( locale === currentLocale ) {
            return;
        }
        currentLocale = locale;
        applyLocale( locale );
        document.querySelectorAll( '.aicoach-lang-option' ).forEach( function ( opt ) {
            const isCurrent = opt.dataset.locale === locale;
            opt.classList.toggle( 'is-current', isCurrent );
            if ( isCurrent ) {
                opt.setAttribute( 'aria-current', 'true' );
            } else {
                opt.removeAttribute( 'aria-current' );
            }
        } );
        // NOTE — scope boundary: this switches the selector state and every
        // data-i18n/data-i18n-attr element's text. It does NOT yet reconnect the
        // live avatar/voice to a fresh Gary session in this locale (Scenario 25's
        // "avatar and voice switch" clause, and FR-14/PO-3105's "video restarts in
        // the new language"). The avatar now DOES open its initial connection via
        // a real Gary session (see start()/openGarySession() below), but switching
        // mid-flow would mean closing that session, tearing down the connected
        // Anam client, and opening a new one — not done here, tracked as the next
        // increment on top of this rather than folded in silently.
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
            selectLocale( option.dataset.locale );
            wrap.classList.remove( 'is-open' );
            btn.setAttribute( 'aria-expanded', 'false' );
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

    function showPanel( panelKey ) {
        const next = stage.querySelector( '.aicoach-panel[data-panel="' + panelKey + '"]' );
        if ( ! next ) {
            return;
        }

        hydratePanelImages( next );

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
    let activeClient = null;   // the connected Anam client (via Gary's session_token), once one exists
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

    function finishSequence() {
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
            const finish = () => {
                if ( settled ) {
                    return;
                }
                settled = true;
                skipCurrent = null;
                resolve();
            };
            skipCurrent = finish;
            window.setTimeout( finish, FALLBACK_READ_MS );
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

    // Plays a pre-rendered clip (inc/aicoach-prerender.php) in the same
    // <video> element the live avatar uses, resolving on the clip's natural
    // 'ended' event (or a manual skip) instead of a fixed dwell — the clip's
    // own length already matches its audio exactly, nothing to estimate.
    // Falls back to the ordinary caption dwell on any playback error, so a
    // missing/broken file never strands a visitor.
    function playPrerenderedClip( url ) {
        return new Promise( ( resolve ) => {
            let settled = false;
            const finish = () => {
                if ( settled ) {
                    return;
                }
                settled = true;
                skipCurrent = null;
                video.removeEventListener( 'ended', onEnded );
                video.removeEventListener( 'error', onError );
                resolve();
            };
            const onEnded = () => finish();
            const onError = () => {
                console.warn( '[aicoach] prerendered clip failed to play, falling back to caption dwell:', url );
                finish();
            };
            // srcObject (the live WebRTC stream, if any) takes priority over
            // src in the video element — clear it first or a plain MP4 src
            // silently never plays.
            video.srcObject = null;
            video.src = url;
            video.addEventListener( 'ended', onEnded );
            video.addEventListener( 'error', onError );
            avatarWrap.dataset.status = 'live'; // same CSS state that reveals .aicoach-avatar-video over the static portrait
            video.play().catch( onError );
            skipCurrent = finish;
            window.setTimeout( finish, 60000 ); // safety cap — 'ended' should always fire first
        } );
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
            const screen = SCREENS[ sequenceIndex ];
            showPanel( screen.panel );
            const captionEl = getCaptionEl( screen.panel );
            if ( captionEl ) {
                captionEl.textContent = screen.script;
            }
            // PO-3062 — an approved segment with a pre-rendered clip plays it
            // (real voice + lip-sync, dwell = the clip's own length); every
            // other screen keeps the original caption-only fixed dwell.
            const clipUrl = getPrerenderedUrl( screen.panel );
            if ( clipUrl ) {
                await playPrerenderedClip( clipUrl );
            } else {
                await waitForReadOrSkip();
            }
            if ( sequenceFinished ) {
                fallbackRunning = false;
                return; // a fresh runFallback() call elsewhere already took over
            }
        }
        fallbackRunning = false;
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

    // Browsers block autoplaying audio until the visitor has interacted with
    // the page at least once — there's no way around that for a page that
    // starts talking on arrival with no required tap. Rather than making
    // visitors hunt for the small unmute button on the avatar, the first
    // click/tap/keypress anywhere unmutes it, same as the "tap to unmute"
    // pattern used elsewhere for autoplaying video with sound.
    function unmuteOnFirstInteraction() {
        document.removeEventListener( 'click', unmuteOnFirstInteraction );
        document.removeEventListener( 'keydown', unmuteOnFirstInteraction );
        if ( ! video.muted ) {
            return;
        }
        video.muted = false;
        unmuteBtn?.setAttribute( 'aria-pressed', 'true' );
        avatarWrap.dataset.muted = 'false';
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

    // FR-17 — time-remaining check (Scenario 29/30). Overlays whichever screen
    // is currently showing (it isn't one of the SCREENS/aicoach-panel entries,
    // since it can interrupt any of them) once the visitor has used up
    // TIME_REMAINING_THRESHOLD_RATIO of their selected tier. Fires once per
    // session (Scenario 30's "does not fire again"); if the visitor has
    // already completed registration by then, the page has already navigated
    // away to the portal (PO-3100's redirect) and this timer is moot — no
    // extra guard needed for that case.
    //
    // "No" is meant to trigger FR-18's appointment scheduling (PO-3109, a
    // separate story, not built yet) — for now it just dismisses; wire the
    // real scheduling flow in here once that story exists.
    const timeCheckOverlay = document.getElementById( 'aicoach-time-check' );
    const timeCheckYesBtn = document.getElementById( 'aicoach-time-check-yes' );
    const timeCheckNoBtn = document.getElementById( 'aicoach-time-check-no' );
    let timeRemainingPromptShown = false;
    let timeRemainingTimer = null;

    function hideTimeRemainingCheck() {
        timeCheckOverlay?.classList.remove( 'is-visible' );
        timeCheckOverlay?.setAttribute( 'aria-hidden', 'true' );
    }

    function scheduleTimeRemainingCheck( tierMinutes ) {
        const totalMs = TIER_DURATION_MS[ tierMinutes ];
        if ( ! totalMs ) {
            return;
        }
        window.clearTimeout( timeRemainingTimer );
        timeRemainingTimer = window.setTimeout( function () {
            if ( timeRemainingPromptShown ) {
                return;
            }
            timeRemainingPromptShown = true;
            timeCheckOverlay?.classList.add( 'is-visible' );
            timeCheckOverlay?.setAttribute( 'aria-hidden', 'false' );
        }, totalMs * TIME_REMAINING_THRESHOLD_RATIO );
    }

    timeCheckYesBtn?.addEventListener( 'click', function () {
        // Scenario 30 — continue from the current screen with no loss; the
        // overlay was layered on top of it, so there's nothing to resume.
        hideTimeRemainingCheck();
    } );

    timeCheckNoBtn?.addEventListener( 'click', function () {
        hideTimeRemainingCheck();
        // FR-18 isn't built yet — nothing further happens until it exists.
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

    // Auto-start on arrival — no tap required (unlike page-portal-poc.php).
    ( async function start() {
        avatarWrap.dataset.status = 'connecting';
        try {
            const gary = await openGarySession( currentLocale );
            garySessionId = gary.session.id;

            // disableInputAudio — this page never uses the visitor's microphone
            // (no voice input UI), and omitting it made Anam request mic
            // permission anyway (seen live as a browser permission prompt / a
            // console "Failed to get microphone permission" error). Denying or
            // dismissing that prompt left the stream connected but silent
            // forever, since data-status never reached "live".
            const client = createClient( gary.say.video.session_token, { disableInputAudio: true } );
            activeClient = client;
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
                runFallback( true );
            } );
            client.addListener( AnamEvent.CONNECTION_CLOSED, function ( event ) {
                if ( handledClose || sequenceFinished ) {
                    return;
                }
                handledClose = true;
                avatarIsLive = false;
                console.warn( '[aicoach] Sami CONNECTION_CLOSED before sequence finished', event );
                runFallback();
            } );

            await client.streamToVideoElement( AVATAR_VIDEO_ID );
        } catch ( error ) {
            console.warn( '[aicoach] falling back to static intro text:', error );
            // openGarySession() may have succeeded (garySessionId set) even though
            // a later step here failed — best-effort close so that session doesn't
            // stay open on Gary's side for no reason. Token-validation failures
            // never reach this with an id set, since openGarySession() throws
            // before returning one.
            if ( garySessionId ) {
                fetch( garyCloseUrl( garySessionId ), { method: 'POST', headers: { 'X-WP-Nonce': cfg.nonce } } )
                    .catch( function () {} );
            }
            runFallback();
        }
    }() );
}

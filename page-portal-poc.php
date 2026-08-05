<?php
/**
 * Template Name: Portal POC
 * Description: Figma communication POC — Sami real-time avatar (Anam) delivering the
 *              IHQ landing script. Scope: intro + two "We Believe" screens + the
 *              2/5/10-minute prompt, then stop (PO-3023 POC demo).
 *
 * @package influencer-hq
 */
get_header();

get_template_part( 'template-parts/portal-styles' );

$theme_uri = get_template_directory_uri();

// REST base + nonce for the Anam token proxy (see inc/anam-proxy.php).
$sami_cfg = array(
	'restBase' => esc_url_raw( rest_url( 'anam/v1' ) ),
	'nonce'    => wp_create_nonce( 'wp_rest' ),
);
?>

    <main id="primary" class="site-main">

        <?php get_template_part( 'template-parts/portal-header' ); ?>

        <div class="container poc-page" style="max-width: 1280px; padding-left: 20px; padding-right: 20px;">

            <!-- Persistent top: tagline + tappable avatar + tap prompt (Figma "ExecutiveConcierge") -->
            <div class="poc-stage-wrap" id="poc-stage-wrap" data-status="idle">

                <div class="poc-avatar-col">
                    <p class="poc-belief"><?php esc_html_e( 'We believe conversations should be easy.', 'influencer-hq' ); ?></p>

                    <div class="poc-avatar" id="poc-avatar" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Tap to talk to Sami', 'influencer-hq' ); ?>">
                        <img class="poc-avatar-portrait" id="poc-avatar-portrait" src="" alt="">
                        <div class="poc-avatar-loading" id="poc-avatar-loading"><div class="poc-avatar-spinner"></div></div>
                        <video class="poc-avatar-video" id="sami-avatar-video" autoplay playsinline></video>
                        <button type="button" class="poc-avatar-stop" id="poc-avatar-stop" aria-label="<?php esc_attr_e( 'Stop Sami', 'influencer-hq' ); ?>">&#10005;</button>
                    </div>

                    <p class="poc-caption" id="poc-caption" aria-live="polite"></p>
                    <p class="poc-error" id="poc-error" hidden></p>
                </div>

                <!-- Swapping content stage: one state visible at a time -->
                <div class="poc-stage" id="poc-stage">

                    <!-- Visible immediately on load (before Sami even starts talking), per request. -->
                    <div class="poc-state is-active" data-state="believe-1">
                        <span class="poc-believe-kicker"><?php esc_html_e( 'We Believe', 'influencer-hq' ); ?></span>
                        <img class="poc-believe-graphic-img" src="<?php echo esc_url( $theme_uri . '/images/poc/belief-coin.png' ); ?>" alt="" aria-hidden="true">
                        <p class="poc-believe-statement"><?php esc_html_e( 'Influence deserves more than short-term payouts.', 'influencer-hq' ); ?></p>
                    </div>

                    <div class="poc-state" data-state="believe-2">
                        <span class="poc-believe-kicker"><?php esc_html_e( 'We Believe', 'influencer-hq' ); ?></span>
                        <img class="poc-believe-graphic-img" src="<?php echo esc_url( $theme_uri . '/images/poc/belief-chart.png' ); ?>" alt="" aria-hidden="true">
                        <p class="poc-believe-statement"><?php esc_html_e( 'When your influence grows a platform, you should share in the value created by that growth.', 'influencer-hq' ); ?></p>
                    </div>

                    <div class="poc-state" data-state="believe-3">
                        <span class="poc-believe-kicker"><?php esc_html_e( 'We Believe', 'influencer-hq' ); ?></span>
                        <img class="poc-believe-graphic-img" src="<?php echo esc_url( $theme_uri . '/images/poc/belief-ownership.png' ); ?>" alt="" aria-hidden="true">
                        <p class="poc-believe-statement"><?php esc_html_e( 'Influencers deserve the opportunity to share in ownership.', 'influencer-hq' ); ?></p>
                    </div>

                    <!-- Time selection — matches Figma "Time Ask": gold square checkbox + gold heading + white bullets -->
                    <div class="poc-state" data-state="time">
                        <div class="poc-time-options" id="poc-time-options" role="group" aria-label="<?php esc_attr_e( 'Choose your session length', 'influencer-hq' ); ?>">
                            <button type="button" class="poc-time-card" data-time="2">
                                <span class="poc-time-head">
                                    <span class="poc-time-box" aria-hidden="true"></span>
                                    <span class="poc-time-num"><?php esc_html_e( '2 Minutes', 'influencer-hq' ); ?></span>
                                </span>
                                <ul class="poc-time-list">
                                    <li><?php esc_html_e( 'Introduction to Competition', 'influencer-hq' ); ?></li>
                                    <li><?php esc_html_e( 'Choose Communication Method', 'influencer-hq' ); ?></li>
                                </ul>
                            </button>
                            <button type="button" class="poc-time-card" data-time="5">
                                <span class="poc-time-head">
                                    <span class="poc-time-box" aria-hidden="true"></span>
                                    <span class="poc-time-num"><?php esc_html_e( '5 Minutes', 'influencer-hq' ); ?></span>
                                </span>
                                <ul class="poc-time-list">
                                    <li><?php esc_html_e( 'Introduction to Competition', 'influencer-hq' ); ?></li>
                                    <li><?php esc_html_e( 'Choose Communication Method', 'influencer-hq' ); ?></li>
                                    <li><?php esc_html_e( 'Full Competition Details', 'influencer-hq' ); ?></li>
                                </ul>
                            </button>
                            <button type="button" class="poc-time-card" data-time="10">
                                <span class="poc-time-head">
                                    <span class="poc-time-box" aria-hidden="true"></span>
                                    <span class="poc-time-num"><?php esc_html_e( '10 Minutes', 'influencer-hq' ); ?></span>
                                </span>
                                <ul class="poc-time-list">
                                    <li><?php esc_html_e( 'Introduction to Competition', 'influencer-hq' ); ?></li>
                                    <li><?php esc_html_e( 'Choose Communication Method', 'influencer-hq' ); ?></li>
                                    <li><?php esc_html_e( 'Full Competition Details', 'influencer-hq' ); ?></li>
                                    <li><?php esc_html_e( 'Set up your First Competition to Earn Equity', 'influencer-hq' ); ?></li>
                                    <li><?php esc_html_e( 'Help with Creating Posts for Your Followers', 'influencer-hq' ); ?></li>
                                </ul>
                            </button>
                        </div>
                    </div>

                </div><!-- .poc-stage -->
            </div><!-- .poc-stage-wrap -->

        </div>

        <?php get_template_part( 'template-parts/portal-footer' ); ?>
    </main><!-- #main -->

<style>
    .poc-page { color: #fff; }

    .poc-stage-wrap {
        max-width: 560px;
        margin: 0 auto;
        /* Top padding clears the fixed portal header + nav bar. */
        padding: 180px 0 60px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }
    .poc-avatar-col {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
    }

    .poc-belief {
        margin: 0 0 28px;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-weight: 700;
        font-size: clamp(1.15rem, 3.4vw, 1.6rem);
        line-height: 1.35;
        color: #fff;
    }

    /* Central tap-to-talk avatar — the live Anam video renders here. */
    .poc-avatar {
        position: relative;
        width: min(300px, 70vw);
        aspect-ratio: 1;
        margin: 0 auto 20px;
        border-radius: 50%;
        overflow: hidden;
        background: #12131a;
        cursor: pointer;
        box-shadow: inset 0 0 30px rgba(0, 0, 0, .5);
    }
    .poc-avatar-portrait,
    .poc-avatar-video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: opacity .32s ease;
    }
    .poc-avatar-loading {
        position: absolute;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(18, 19, 26, .85);
        backdrop-filter: blur(8px);
        z-index: 1;
    }
    .poc-stage-wrap[data-status="connecting"] .poc-avatar-loading { display: flex; }
    .poc-avatar-spinner {
        width: 44px;
        height: 44px;
        border: 3px solid rgba(253, 214, 91, .2);
        border-top-color: #fdd65b;
        border-radius: 50%;
        animation: poc-spin .8s linear infinite;
    }
    @keyframes poc-spin { to { transform: rotate(360deg); } }
    .poc-avatar-video { opacity: 0; }
    .poc-stage-wrap[data-status="live"] .poc-avatar-video { opacity: 1; }
    .poc-stage-wrap[data-status="live"] .poc-avatar-portrait { opacity: 0; }
    .poc-stage-wrap[data-status="connecting"] .poc-avatar-portrait[src]:not([src=""]) { opacity: .4; filter: brightness(.55) blur(1px); }
    .poc-avatar-portrait:not([src]),
    .poc-avatar-portrait[src=""] { opacity: 0; }

    .poc-avatar-stop {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 30px;
        height: 30px;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: none;
        border-radius: 50%;
        background: rgba(0, 0, 0, .5);
        color: #fff;
        font-size: 14px;
        cursor: pointer;
        z-index: 2;
    }
    .poc-avatar-stop:hover { background: rgba(0, 0, 0, .75); }
    .poc-stage-wrap[data-status="live"] .poc-avatar-stop { display: flex; }

    .poc-caption {
        margin: 0 0 8px;
        min-height: 1.2em;
        font-family: 'Inter', 'Be Vietnam Pro', sans-serif;
        font-weight: 700;
        letter-spacing: .03em;
        font-size: clamp(.9rem, 2.6vw, 1.1rem);
        color: rgba(255, 255, 255, .75);
    }
    /* Idle glow invites a tap — no "tap to talk" label needed. */
    .poc-stage-wrap[data-status="idle"] .poc-avatar,
    .poc-stage-wrap[data-status="error"] .poc-avatar {
        animation: poc-avatar-invite 2.6s ease-in-out infinite;
    }
    @keyframes poc-avatar-invite {
        0%, 100% { box-shadow: inset 0 0 30px rgba(0, 0, 0, .5), 0 0 0 0 rgba(253, 214, 91, 0); }
        50%      { box-shadow: inset 0 0 30px rgba(0, 0, 0, .5), 0 0 0 10px rgba(253, 214, 91, .16); }
    }

    .poc-error {
        margin: 8px auto 0;
        max-width: 320px;
        color: #ff8a8a;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: .82rem;
        line-height: 1.35;
    }

    /* Swapping content stage below the avatar. */
    .poc-stage { position: relative; margin-top: 40px; min-height: 46vh; }
    .poc-state {
        display: none;
        flex-direction: column;
        align-items: center;
        gap: 18px;
        width: 100%; /* fill the stage column instead of shrink-wrapping to content */
    }
    .poc-state.is-active { display: flex; animation: poc-state-in .55s cubic-bezier(.16, .84, .44, 1) both; }
    @keyframes poc-state-in {
        from { opacity: 0; transform: translateY(20px) scale(.98); filter: blur(8px); }
        to   { opacity: 1; transform: none; filter: blur(0); }
    }

    /* Staggered entrance for each piece of a belief screen — kicker, then icon (pop), then statement. */
    .poc-state.is-active .poc-believe-kicker {
        animation: poc-kicker-in .5s cubic-bezier(.16, .84, .44, 1) both;
    }
    .poc-state.is-active .poc-believe-statement {
        animation: poc-statement-in .55s cubic-bezier(.16, .84, .44, 1) both;
        animation-delay: .24s;
    }
    .poc-state.is-active .poc-believe-graphic-img {
        animation: poc-pop-in .6s cubic-bezier(.34, 1.56, .64, 1) both;
        animation-delay: .12s;
    }
    @keyframes poc-kicker-in {
        from { opacity: 0; transform: translateY(12px); letter-spacing: .04em; }
        to   { opacity: 1; transform: none; letter-spacing: normal; }
    }
    @keyframes poc-statement-in {
        from { opacity: 0; transform: translateY(14px); filter: blur(6px); }
        to   { opacity: 1; transform: none; filter: blur(0); }
    }
    @keyframes poc-pop-in {
        from { opacity: 0; transform: scale(.6) rotate(-8deg); }
        60%  { opacity: 1; transform: scale(1.08) rotate(2deg); }
        to   { opacity: 1; transform: scale(1) rotate(0); }
    }

    /* Staggered entrance for the three time-selection rows. */
    .poc-state.is-active .poc-time-card { animation: poc-card-in .5s cubic-bezier(.16, .84, .44, 1) both; }
    .poc-state.is-active .poc-time-card:nth-child(1) { animation-delay: 0s; }
    .poc-state.is-active .poc-time-card:nth-child(2) { animation-delay: .12s; }
    .poc-state.is-active .poc-time-card:nth-child(3) { animation-delay: .24s; }
    @keyframes poc-card-in {
        from { opacity: 0; transform: translateX(-24px); }
        to   { opacity: 1; transform: none; }
    }

    .poc-believe-kicker {
        font-family: 'Inter', sans-serif;
        font-weight: 800;
        font-size: clamp(1.5rem, 5vw, 2.1rem);
        color: #fdd65b;
    }
    .poc-believe-graphic {
        position: relative;
        display: inline-block;
        font-size: clamp(3.5rem, 15vw, 6rem);
        line-height: 1;
    }
    /* Real Figma belief graphics (e.g. the crossed-coin PNG). */
    .poc-believe-graphic-img {
        display: block;
        width: clamp(96px, 22vw, 150px);
        height: auto;
    }
    /* Crossed-out coin ("not short-term payouts") — a red slash over the icon. */
    .poc-graphic-no::after {
        content: "";
        position: absolute;
        left: -6%;
        right: -6%;
        top: 50%;
        height: 9px;
        border-radius: 9px;
        background: #e5352b;
        transform: translateY(-50%) rotate(-18deg);
    }
    .poc-believe-statement {
        margin: 0;
        max-width: 100%;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-weight: 800;
        font-size: clamp(1.35rem, 4.2vw, 1.9rem);
        line-height: 1.25;
        color: #fff;
    }

    /* Time selection — Figma "Time Ask": vertical list, gold square checkbox + gold heading + white bullets. */
    .poc-time-options {
        display: flex;
        flex-direction: column;
        gap: 30px;
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
        text-align: left;
    }
    .poc-time-card {
        display: block;
        width: 100%;
        padding: 4px 6px;
        background: transparent;
        border: none;
        color: #fff;
        cursor: pointer;
        text-align: left; /* buttons default to center in the UA stylesheet — override it */
    }
    .poc-time-head { display: flex; align-items: center; gap: 18px; margin-bottom: 12px; }
    .poc-time-box {
        flex: 0 0 auto;
        width: 42px;
        height: 42px;
        border: 3px solid #fdd65b;
        border-radius: 10px;
        background: transparent;
        transition: background .15s ease, box-shadow .15s ease;
    }
    .poc-time-card:hover .poc-time-box { box-shadow: 0 0 0 4px rgba(253, 214, 91, .25); }
    .poc-time-card.is-selected .poc-time-box { background: #fdd65b; }
    .poc-time-num {
        font-family: 'Inter', sans-serif;
        font-weight: 800;
        font-size: clamp(1.6rem, 5.5vw, 2.3rem);
        line-height: 1;
        color: #fdd65b;
    }
    .poc-time-list { margin: 0; padding-left: 24px; list-style: disc; }
    .poc-time-list li {
        font-family: 'Be Vietnam Pro', sans-serif;
        font-weight: 700;
        font-size: clamp(1rem, 2.8vw, 1.25rem);
        line-height: 1.7;
        color: #fff;
    }

    /* "Pointing" highlight for the time options. */
    .poc-point { border-radius: 12px; animation: poc-point-pulse 1.4s ease-in-out infinite; }
    @keyframes poc-point-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(253, 214, 91, 0); }
        50%      { box-shadow: 0 0 0 6px rgba(253, 214, 91, .5), 0 0 26px 8px rgba(253, 214, 91, .35); }
    }

    /* Responsive — desktop / mobile friendly. Phone-first; a little roomier on desktop. */
    @media (min-width: 900px) {
        /* Desktop: 2 columns — avatar on the left, swapping content filling the rest of the row. */
        .poc-stage-wrap {
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 64px;
            max-width: 1240px;
            padding-top: 200px;
        }
        .poc-avatar-col { flex: 0 0 340px; width: 340px; }
        .poc-avatar { width: 300px; height: 400px }
        /* flex-basis 0 (not auto) so this column actually claims the remaining row
           width instead of shrink-wrapping to its content's natural size. */
        .poc-stage { flex: 1 1 0%; min-width: 0; width: 100%; max-width: 720px; min-height: auto; margin-top: 0; }
    }
    @media (max-width: 480px) {
        .poc-stage-wrap { padding: 150px 0 48px; }
        .poc-avatar { width: min(300px, 76vw); }
        .poc-stage { margin-top: 28px; }
        .poc-time-options { gap: 24px; }
        .poc-time-box { width: 36px; height: 36px; }
    }

    /* Hide the site-wide ElevenLabs concierge FAB on this template (belt-and-suspenders;
       primary suppression is in inc/anam-proxy.php). */
    body.page-template-page-portal-poc .ihq-concierge-fab { display: none !important; }
</style>

<script>
    window.SAMI_ANAM = <?php echo wp_json_encode( $sami_cfg ); ?>;
</script>
<script type="module">
import { createClient, AnamEvent } from 'https://cdn.jsdelivr.net/npm/@anam-ai/js-sdk@4/+esm';

/*
 * Sami — scripted playback for the PO-3023 POC demo.
 *
 * The avatar is the central circle; tapping starts a live Anam session and the
 * page drives her through the landing script VERBATIM via talk() (no LLM
 * rephrasing, no mic — deterministic). Each beat can swap the on-screen "state"
 * (intro → believe-1 → believe-2 → time) so the visual matches what she says.
 * Scope stops at the 2/5/10-minute selection. Lines quoted from
 * "IHQ Lander & Script v2"; persona has skipGreeting=true so she never
 * auto-speaks over this sequence.
 */
const cfg = window.SAMI_ANAM || {};
const SESSION_TOKEN_URL = cfg.restBase + '/session-token';
const PERSONA_PREVIEW_URL = cfg.restBase + '/persona-preview';
const VIDEO_ELEMENT_ID = 'sami-avatar-video';

// Beat shape: { state, say, point, dwellAfter, awaitSelection }.
// Each SECTION is a SINGLE talk() utterance (not several short beats), so nothing
// can interrupt mid-section. The greeting is folded into the first section so it
// always plays in full and absorbs the connection/render startup latency of the
// first talk() call.
const SCRIPT = [
    // Greeting + first belief — plays over believe-1 (already visible on load).
    { state: 'believe-1', say: "Hello. I'm Sami, your Executive Coach, and welcome to InfluencerHQ. My job is to help you understand one simple idea — we believe influencers who help build our company should have the opportunity to earn meaningful equity. Every successful company begins with a set of beliefs, and here's one of ours: influence deserves more than short-term payouts." },
    // Belief 2 — share in the value / growth (chart)
    { state: 'believe-2', say: "When your influence grows a platform, you should share in the value created by that growth. Most influencers are rewarded only for what they do today, but we believe those who help build tomorrow should benefit from what they help create." },
    // Belief 3 — share in ownership (certificate)
    { state: 'believe-3', say: "There's another belief that's just as important. The people who help create value should have the opportunity to share in it — not someday, but from the very beginning. When meaningful ownership is available, it can become worth far more than a one-time payment." },
    // Time ask (decision point) — one utterance, highlighting the options as she asks.
    { state: 'time', point: '#poc-time-options', dwellAfter: 400, say: "Now it's your turn. How much time would you like to spend with me today? Whether you have two minutes, five minutes, or ten minutes, I'll make sure our time together is worthwhile. Go ahead — choose the amount of time that's right for you." },
    { awaitSelection: true },
];

// Spoken the moment the visitor picks a length — a distinct reaction per choice.
// After this line the POC demo ends.
const REACTIONS = {
    '2': "Two minutes — perfect. Let's make them count. I'll take it from here, and we'll continue exactly where we leave off.",
    '5': "Five minutes — wonderful. That's plenty of time to get you started. I'll take it from here, and we'll continue exactly where we leave off.",
    '10': "Ten minutes — I love it. We'll do this properly, together. I'll take it from here, and we'll continue exactly where we leave off.",
};

const wrap = document.getElementById('poc-stage-wrap');
const avatar = document.getElementById('poc-avatar');
const stopBtn = document.getElementById('poc-avatar-stop');
const caption = document.getElementById('poc-caption');
const portrait = document.getElementById('poc-avatar-portrait');
const errorEl = document.getElementById('poc-error');
const states = document.querySelectorAll('.poc-state');

let client = null;
let pointEl = null;
let generation = 0;           // bumped on every start()/stop(); stale clients' events are ignored
let connecting = false;       // single-flight lock so two starts never race into two live sessions
let beatIndex = 0;            // current SCRIPT position — preserved across reconnects so we resume
let reconnects = 0;           // consecutive auto-reconnect attempts since last progress
let activeRunClient = null;   // the client the current runScript loop belongs to (guards double-runs)
let abortSpeechWait = null;   // lets a dropped connection unblock a pending say() immediately
let abortTimeWait = null;     // same, for the 2/5/10 selection wait

function setStatus(status) {
    wrap.dataset.status = status;
    caption.textContent = status === 'connecting' ? 'Connecting…' : '';
}

function setState(name) {
    states.forEach((s) => s.classList.toggle('is-active', s.getAttribute('data-state') === name));
}

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
// talk() resolves as soon as the line is ACCEPTED, not when speech ends, so pace
// each line by an estimate of its spoken length. Pure char count underestimates
// multi-sentence lines (TTS inserts real pauses at each . ! ? and , ; : —), which
// lets the next talk() barge in and cut the line off — so weight the punctuation.
const estMs = (text) => {
    const chars = text.length;
    const stops = (text.match(/[.!?…]/g) || []).length;
    const clauses = (text.match(/[,;:—]/g) || []).length;
    return Math.max(1800, chars * 55 + stops * 420 + clauses * 190 + 450);
};

function pointAt(selector) {
    clearPoint();
    const el = document.querySelector(selector);
    if (!el) return;
    el.classList.add('poc-point');
    pointEl = el;
}
function clearPoint() {
    if (pointEl) { pointEl.classList.remove('poc-point'); pointEl = null; }
}

// Speak one line and resolve when she ACTUALLY finishes it: Anam emits a
// MESSAGE_STREAM_EVENT_RECEIVED with role='persona', endOfSpeech=true at the end of
// each utterance. estMs is only a safety cap if that event never arrives, and a
// dropped connection resolves the wait at once via abortSpeechWait.
function say(text) {
    return new Promise((resolve) => {
        let done = false;
        const onEvent = (evt) => { if (evt && evt.role === 'persona' && evt.endOfSpeech) finish(); };
        const finish = () => {
            if (done) return;
            done = true;
            clearTimeout(timer);
            abortSpeechWait = null;
            try { client && client.removeListener(AnamEvent.MESSAGE_STREAM_EVENT_RECEIVED, onEvent); } catch (e) {}
            resolve();
        };
        const timer = setTimeout(finish, estMs(text) + 6000);
        abortSpeechWait = finish;
        if (!client) { finish(); return; }
        client.addListener(AnamEvent.MESSAGE_STREAM_EVENT_RECEIVED, onEvent);
        client.talk(text).catch(() => finish());
    });
}

// Time-selection wait — resolves with '2' | '5' | '10'. pendingTime remembers an early click.
let timeResolver = null;
let pendingTime = null;
function waitForTimeSelection() {
    clearPoint();
    if (pendingTime) { const t = pendingTime; pendingTime = null; return Promise.resolve(t); }
    return new Promise((resolve) => {
        timeResolver = (v) => { timeResolver = null; abortTimeWait = null; resolve(v); };
        abortTimeWait = () => { if (timeResolver) timeResolver(null); };
    });
}
document.querySelectorAll('.poc-time-card').forEach((btn) => {
    btn.addEventListener('click', (event) => {
        event.stopPropagation();
        document.querySelectorAll('.poc-time-card').forEach((b) => b.classList.remove('is-selected'));
        btn.classList.add('is-selected');
        const t = btn.getAttribute('data-time');
        if (timeResolver) timeResolver(t);
        else pendingTime = t;
    });
});

async function runScript() {
    // One loop per live client. Resumes from beatIndex after a reconnect; a stale
    // loop whose client got replaced exits via alive().
    if (!client || activeRunClient === client) return;
    activeRunClient = client;
    const myClient = client;
    const alive = () => client === myClient && wrap.dataset.status === 'live';
    while (beatIndex < SCRIPT.length) {
        if (!alive()) return;
        const beat = SCRIPT[beatIndex];
        if (beat.state) setState(beat.state);
        if (beat.point) pointAt(beat.point); else if (beat.say) clearPoint();
        if (beat.say) {
            await say(beat.say);
            if (!alive()) return; // dropped mid-line → don't advance; resume re-speaks this beat
            await sleep(160);
        }
        if (beat.dwellAfter) await sleep(beat.dwellAfter);
        if (beat.awaitSelection) {
            const choice = await waitForTimeSelection();
            if (!alive()) return;
            await say(REACTIONS[choice] || REACTIONS['2']);
            if (!alive()) return;
        }
        beatIndex++;
        reconnects = 0; // progress made → refresh the reconnect budget
    }
    clearPoint();
    await sleep(1500);
    stop();
}

// Best-effort: use Sami's own portrait as the idle face so it matches the live video.
(async function loadPreview() {
    try {
        const res = await fetch(PERSONA_PREVIEW_URL, { headers: { 'X-WP-Nonce': cfg.nonce } });
        if (!res.ok) return;
        const data = await res.json();
        if (data.portraitUrl) portrait.src = data.portraitUrl;
    } catch (e) { /* no portrait available */ }
})();

async function start(resume) {
    if (connecting) return; // a connection attempt is already in flight — never open a second
    connecting = true;
    setStatus('connecting');
    errorEl.hidden = true;
    if (!resume) { beatIndex = 0; reconnects = 0; }
    // Tear down any prior client BEFORE minting a new one, so two WebRTC/signalling
    // sessions never overlap — overlap is itself a cause of SIGNALLING_CLIENT_CONNECTION_FAILURE.
    const prev = client;
    client = null;
    if (prev) { try { prev.stopStreaming(); } catch (e) {} }
    const myGen = ++generation; // this attempt owns this generation; later starts/stops invalidate it
    try {
        const res = await fetch(SESSION_TOKEN_URL, { method: 'POST', headers: { 'X-WP-Nonce': cfg.nonce } });
        const data = await res.json();
        if (myGen !== generation) return; // superseded while awaiting the token
        if (!res.ok || !data.sessionToken) throw new Error(data.error || 'Failed to start Sami.');
        const c = createClient(data.sessionToken);
        client = c;
        activeRunClient = null; // fresh client → a new runScript loop may take over
        let closedHandled = false; // each client's CONNECTION_CLOSED must act at most once
        c.addListener(AnamEvent.VIDEO_PLAY_STARTED, () => {
            if (myGen !== generation) return; // stale client
            connecting = false;
            setStatus('live');
            runScript();
        });
        c.addListener(AnamEvent.CONNECTION_CLOSED, (event) => {
            if (closedHandled || myGen !== generation) return; // ignore repeat/stale closes
            closedHandled = true;
            console.warn('[Sami] CONNECTION_CLOSED', event);
            client = null;
            connecting = false;
            if (abortSpeechWait) abortSpeechWait();
            if (abortTimeWait) abortTimeWait();
            clearPoint();
            // Unexpected drop (e.g. SIGNALLING_CLIENT_CONNECTION_FAILURE). Reconnect and
            // resume from the current beat — transient WebRTC/signalling failures are common.
            if (beatIndex < SCRIPT.length && reconnects < 4) {
                reconnects++;
                console.warn('[Sami] reconnecting #' + reconnects + ' at beat ' + beatIndex);
                setStatus('connecting');
                setTimeout(() => start(true), 900);
            } else {
                setStatus('idle');
                const reason = event && (event.reason || event.message || event.code || event.errorCode);
                errorEl.textContent = 'Connection lost' + (reason ? ': ' + reason : '') + ' — tap to restart.';
                errorEl.hidden = false;
            }
        });
        c.addListener(AnamEvent.SERVER_WARNING, (event) => console.warn('[Sami] SERVER_WARNING', event));
        await c.streamToVideoElement(VIDEO_ELEMENT_ID);
        if (myGen !== generation) { try { c.stopStreaming(); } catch (e) {} } // superseded during connect
    } catch (error) {
        connecting = false;
        if (myGen !== generation) return; // a newer start/stop already took over
        client = null;
        errorEl.textContent = error instanceof Error ? error.message : String(error);
        errorEl.hidden = false;
        setStatus('error');
    }
}

async function stop() {
    generation++;        // invalidate every current client callback → no drop is treated as a reconnect
    connecting = false;
    const c = client;
    client = null;
    activeRunClient = null;
    beatIndex = 0;
    reconnects = 0;
    if (abortSpeechWait) abortSpeechWait();
    if (abortTimeWait) abortTimeWait();
    clearPoint();
    if (c) { try { await c.stopStreaming(); } catch (e) {} }
    setStatus('idle');
    setState('believe-1'); // reset to the default visible screen
}

// Tap anywhere in the stage to start (when idle); tapping while live does nothing
// so the time cards keep working. The × on the avatar ends the session.
wrap.addEventListener('click', () => {
    const s = wrap.dataset.status;
    if (s === 'idle' || s === 'error') start();
});
avatar.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        const s = wrap.dataset.status;
        if (s === 'idle' || s === 'error') start();
    }
});
stopBtn.addEventListener('click', (event) => { event.stopPropagation(); stop(); });
window.addEventListener('beforeunload', () => { if (client) client.stopStreaming().catch(() => undefined); });
</script>

<?php
get_template_part( 'template-parts/portal-scripts' );
get_footer();

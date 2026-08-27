<?php
/**
 * Template Name: Home AI Coach
 * Description: AI coach landing content inside the portal shell (header/footer).
 *
 * @package influencer-hq
 */
get_header();

get_template_part( 'template-parts/portal-styles' );

$theme_uri = get_template_directory_uri();

// REST base + nonce for the Anam token proxy (see inc/anam-proxy.php) — same
// session-token/persona-preview endpoints page-portal-poc.php already uses.
$aicoach_sami_cfg = array(
	'restBase' => esc_url_raw( rest_url( 'anam/v1' ) ),
	'nonce'    => wp_create_nonce( 'wp_rest' ),
);

$aicoach_img = array(
	'portrait'     => $theme_uri . '/images/aicoach/coach-portrait.png',
	'bts'          => $theme_uri . '/images/aicoach/bts.jpg',
	'magic'        => $theme_uri . '/images/aicoach/magic-johnson.png',
	'check'        => $theme_uri . '/images/aicoach/icon-check.svg',
	'x'            => $theme_uri . '/images/aicoach/icon-x.svg',
	'belief-coin'  => $theme_uri . '/images/aicoach/icon-belief-coin.svg',
	'belief-chart' => $theme_uri . '/images/aicoach/icon-belief-chart.svg',
);

$aicoach_tiers = array(
	array(
		'key'      => '2',
		'duration' => '2 minutes',
		'selected' => true,
		'items'    => array(
			'Introduction to Competition',
			'Choose Communication Method',
		),
	),
	array(
		'key'      => '5',
		'duration' => '5 minutes',
		'selected' => false,
		'items'    => array(
			'Introduction to Competition',
			'Choose Communication Method',
			'Full Competition Details',
		),
	),
	array(
		'key'      => '10',
		'duration' => '10 minutes',
		'selected' => false,
		'items'    => array(
			'Introduction to Competition',
			'Choose Communication Method',
			'Full Competition Details',
			'Setup your First Competition to Earn Equity',
			'Help with Creating Posts for Your Followers',
		),
	),
);
?>

    <main id="primary" class="site-main">

        <?php get_template_part( 'template-parts/portal-header' ); ?>

        <div class="container py-2 aicoach-page" style="max-width: 1024px; padding-left: 20px; padding-right: 20px;">

            <section class="aicoach" aria-label="<?php esc_attr_e( 'AI Coach', 'influencer-hq' ); ?>">

                <p class="aicoach-belief"><?php esc_html_e( 'We believe conversations should be easy.', 'influencer-hq' ); ?></p>

                <div class="aicoach-avatar-wrap" id="aicoach-avatar-wrap" data-status="idle">
                    <img
                        class="aicoach-portrait"
                        id="aicoach-portrait"
                        src="<?php echo esc_url( $aicoach_img['portrait'] ); ?>"
                        alt="<?php esc_attr_e( 'AI Coach', 'influencer-hq' ); ?>"
                        width="452"
                        height="452"
                    >
                    <video class="aicoach-avatar-video" id="aicoach-avatar-video" autoplay playsinline muted></video>
                    <div class="aicoach-avatar-loading" aria-hidden="true"><div class="aicoach-avatar-spinner"></div></div>
                    <button type="button" class="aicoach-unmute" id="aicoach-unmute" aria-pressed="false" aria-label="<?php esc_attr_e( 'Turn on Sami\'s voice', 'influencer-hq' ); ?>">&#128264;</button>
                </div>

                <div class="aicoach-stage" id="aicoach-stage">

                    <div class="aicoach-panel is-active" data-panel="intro" aria-hidden="false">
                        <p class="aicoach-caption" data-caption-for="intro" aria-live="polite"></p>
                    </div>

                    <div class="aicoach-panel" data-panel="believe-1" aria-hidden="true">
                        <div class="aicoach-believe">
                            <img class="aicoach-believe-icon" src="<?php echo esc_url( $aicoach_img['belief-coin'] ); ?>" alt="" width="72" height="72" aria-hidden="true">
                            <span class="aicoach-believe-kicker"><?php esc_html_e( 'We Believe', 'influencer-hq' ); ?></span>
                            <p class="aicoach-caption" data-caption-for="believe-1" aria-live="polite"></p>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="believe-2" aria-hidden="true">
                        <div class="aicoach-believe">
                            <img class="aicoach-believe-icon" src="<?php echo esc_url( $aicoach_img['belief-chart'] ); ?>" alt="" width="72" height="72" aria-hidden="true">
                            <span class="aicoach-believe-kicker"><?php esc_html_e( 'We Believe', 'influencer-hq' ); ?></span>
                            <p class="aicoach-caption" data-caption-for="believe-2" aria-live="polite"></p>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="home" aria-hidden="true">
                        <div class="aicoach-tiers" role="radiogroup" aria-label="<?php esc_attr_e( 'Conversation length', 'influencer-hq' ); ?>">
                            <?php foreach ( $aicoach_tiers as $index => $tier ) : ?>
                            <?php
                            $tier_id = 'aicoach-tier-' . ( $index + 1 );
                            $is_on   = ! empty( $tier['selected'] );
                            ?>
                            <div class="aicoach-tier<?php echo $is_on ? ' is-selected' : ''; ?>">
                                <label class="aicoach-tier-head" for="<?php echo esc_attr( $tier_id ); ?>">
                                    <input
                                        class="aicoach-tier-check"
                                        type="radio"
                                        name="aicoach_duration"
                                        id="<?php echo esc_attr( $tier_id ); ?>"
                                        value="<?php echo esc_attr( $tier['key'] ); ?>"
                                        data-panel="<?php echo esc_attr( $tier['key'] ); ?>"
                                        <?php checked( $is_on ); ?>
                                    >
                                    <span class="aicoach-tier-box" aria-hidden="true"></span>
                                    <span class="aicoach-tier-duration"><?php echo esc_html( $tier['duration'] ); ?></span>
                                </label>
                                <ul class="aicoach-tier-list">
                                    <?php foreach ( $tier['items'] as $item ) : ?>
                                    <li><?php echo esc_html( $item ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="2" aria-hidden="true">
                        <div class="aicoach-story aicoach-story--bts">
                            <h2 class="aicoach-story-title"><?php esc_html_e( 'BTS', 'influencer-hq' ); ?></h2>
                            <div class="aicoach-story-media">
                                <img
                                    class="aicoach-story-photo"
                                    src="<?php echo esc_url( $aicoach_img['bts'] ); ?>"
                                    alt="<?php esc_attr_e( 'BTS', 'influencer-hq' ); ?>"
                                    width="624"
                                    height="471"
                                >
                            </div>
                            <ul class="aicoach-story-points">
                                <li class="aicoach-story-point">
                                    <img class="aicoach-story-icon" src="<?php echo esc_url( $aicoach_img['check'] ); ?>" alt="" width="48" height="45" aria-hidden="true">
                                    <span><?php esc_html_e( 'said yes to shared ownership, valued at 103.6 million', 'influencer-hq' ); ?></span>
                                </li>
                                <li class="aicoach-story-point">
                                    <img class="aicoach-story-icon" src="<?php echo esc_url( $aicoach_img['x'] ); ?>" alt="" width="48" height="48" aria-hidden="true">
                                    <span><?php esc_html_e( 'turned down cash', 'influencer-hq' ); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="5" aria-hidden="true">
                        <div class="aicoach-story aicoach-story--magic">
                            <div class="aicoach-magic">
                                <img
                                    class="aicoach-magic-img"
                                    src="<?php echo esc_url( $aicoach_img['magic'] ); ?>"
                                    alt="<?php esc_attr_e( 'Magic Johnson', 'influencer-hq' ); ?>"
                                    width="596"
                                    height="844"
                                >
                                <h2 class="aicoach-magic-name">
                                    <span><?php esc_html_e( 'Magic', 'influencer-hq' ); ?></span>
                                    <span><?php esc_html_e( 'Johnson', 'influencer-hq' ); ?></span>
                                </h2>
                            </div>
                            <ul class="aicoach-story-points">
                                <li class="aicoach-story-point">
                                    <img class="aicoach-story-icon" src="<?php echo esc_url( $aicoach_img['check'] ); ?>" alt="" width="48" height="45" aria-hidden="true">
                                    <span><?php esc_html_e( 'Adidas $100k cash', 'influencer-hq' ); ?></span>
                                </li>
                                <li class="aicoach-story-point aicoach-story-point--stacked">
                                    <img class="aicoach-story-icon" src="<?php echo esc_url( $aicoach_img['x'] ); ?>" alt="" width="48" height="48" aria-hidden="true">
                                    <span>
                                        <?php esc_html_e( "Nike's 11 cent stock", 'influencer-hq' ); ?>
                                        <span class="aicoach-story-sub"><?php esc_html_e( 'Now worth 5.4 billion', 'influencer-hq' ); ?></span>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="10" aria-hidden="true">
                        <div class="aicoach-story aicoach-story--magic">
                            <h2 class="aicoach-duration-label"><?php esc_html_e( '10 minutes', 'influencer-hq' ); ?></h2>
                            <div class="aicoach-magic">
                                <img
                                    class="aicoach-magic-img"
                                    src="<?php echo esc_url( $aicoach_img['magic'] ); ?>"
                                    alt="<?php esc_attr_e( 'Magic Johnson', 'influencer-hq' ); ?>"
                                    width="596"
                                    height="844"
                                >
                                <p class="aicoach-magic-name" role="presentation">
                                    <span><?php esc_html_e( 'Magic', 'influencer-hq' ); ?></span>
                                    <span><?php esc_html_e( 'Johnson', 'influencer-hq' ); ?></span>
                                </p>
                            </div>
                            <ul class="aicoach-story-points">
                                <li class="aicoach-story-point">
                                    <img class="aicoach-story-icon" src="<?php echo esc_url( $aicoach_img['check'] ); ?>" alt="" width="48" height="45" aria-hidden="true">
                                    <span><?php esc_html_e( 'Adidas $100k cash', 'influencer-hq' ); ?></span>
                                </li>
                                <li class="aicoach-story-point aicoach-story-point--stacked">
                                    <img class="aicoach-story-icon" src="<?php echo esc_url( $aicoach_img['x'] ); ?>" alt="" width="48" height="48" aria-hidden="true">
                                    <span>
                                        <?php esc_html_e( "Nike's 11 cent stock", 'influencer-hq' ); ?>
                                        <span class="aicoach-story-sub"><?php esc_html_e( 'Now worth 5.4 billion', 'influencer-hq' ); ?></span>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>

            </section>

        </div>

        <?php get_template_part( 'template-parts/portal-footer' ); ?>
    </main><!-- #main -->

<style>
    .aicoach {
        max-width: 720px;
        margin: 0 auto 56px;
        padding: 185px 0 40px;
        color: #fff;
        font-family: 'Be Vietnam Pro', sans-serif;
    }

    .aicoach-belief {
        margin: 0 0 28px;
        font-weight: 600;
        font-size: clamp(1.2rem, 3.4vw, 1.55rem);
        line-height: 1.35;
        text-align: center;
        color: #fff;
    }

    .aicoach-avatar-wrap {
        position: relative;
        width: min(280px, 62vw);
        aspect-ratio: 1;
        margin: 0 auto 48px;
        border-radius: 50%;
        overflow: hidden;
        background: #12131a;
    }

    .aicoach-portrait,
    .aicoach-avatar-video {
        position: absolute;
        inset: 0;
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center top;
        transition: opacity 0.32s ease;
    }

    .aicoach-avatar-video { opacity: 0; }
    .aicoach-avatar-wrap[data-status="live"] .aicoach-avatar-video { opacity: 1; }
    .aicoach-avatar-wrap[data-status="live"] .aicoach-portrait { opacity: 0; }
    .aicoach-avatar-wrap[data-status="connecting"] .aicoach-portrait { opacity: .4; filter: brightness(.55) blur(1px); }

    .aicoach-avatar-loading {
        position: absolute;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(18, 19, 26, .85);
        backdrop-filter: blur(8px);
        z-index: 1;
    }

    .aicoach-avatar-wrap[data-status="connecting"] .aicoach-avatar-loading { display: flex; }

    .aicoach-avatar-spinner {
        width: 44px;
        height: 44px;
        border: 3px solid rgba(253, 214, 91, .2);
        border-top-color: #fdd65b;
        border-radius: 50%;
        animation: aicoach-spin 0.8s linear infinite;
    }

    @keyframes aicoach-spin { to { transform: rotate(360deg); } }

    .aicoach-unmute {
        position: absolute;
        bottom: 10px;
        right: 10px;
        width: 34px;
        height: 34px;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: none;
        border-radius: 50%;
        background: rgba(0, 0, 0, .55);
        color: #fff;
        font-size: 16px;
        cursor: pointer;
        z-index: 2;
    }

    .aicoach-unmute:hover { background: rgba(0, 0, 0, .8); }
    .aicoach-avatar-wrap[data-status="live"] .aicoach-unmute { display: flex; }
    .aicoach-avatar-wrap[data-status="live"][data-muted="false"] .aicoach-unmute { opacity: .5; }

    .aicoach-caption {
        max-width: 560px;
        margin: 0 auto;
        min-height: 4.5em;
        font-weight: 600;
        font-size: clamp(1rem, 2.8vw, 1.25rem);
        line-height: 1.5;
        text-align: center;
        color: #fff;
    }

    .aicoach-believe {
        max-width: 560px;
        margin: 0 auto;
        text-align: center;
    }

    .aicoach-believe-icon {
        display: block;
        margin: 0 auto 20px;
        width: 72px;
        height: 72px;
    }

    .aicoach-believe-kicker {
        display: block;
        margin: 0 0 16px;
        font-weight: 800;
        font-size: clamp(1.3rem, 4vw, 1.75rem);
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #fdd65b;
    }

    .aicoach-stage {
        position: relative;
        min-height: 200px;
    }

    .aicoach-panel {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.4s ease, visibility 0.4s ease;
    }

    .aicoach-panel.is-active {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .aicoach-panel:not(.is-active) {
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
    }

    .aicoach-tiers {
        display: flex;
        flex-direction: column;
        gap: 36px;
        max-width: 640px;
        margin: 0 auto;
    }

    .aicoach-tier-head {
        display: flex;
        align-items: center;
        gap: 18px;
        margin: 0 0 14px;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
    }

    .aicoach-tier-check {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .aicoach-tier-box {
        flex-shrink: 0;
        width: 42px;
        height: 42px;
        border: 4px solid #fdd65b;
        border-radius: 10px;
        background: transparent;
        box-sizing: border-box;
    }

    .aicoach-tier.is-selected .aicoach-tier-box,
    .aicoach-tier-head:has(.aicoach-tier-check:checked) .aicoach-tier-box {
        background: #fdd65b;
    }

    .aicoach-tier-duration {
        font-weight: 800;
        font-size: clamp(1.75rem, 5vw, 2.5rem);
        line-height: 1.1;
        text-transform: capitalize;
        color: #fdd65b;
    }

    .aicoach-tier-list {
        margin: 0;
        padding: 0 0 0 1.35em;
        list-style: disc;
        color: #fff;
    }

    .aicoach-tier-list li {
        margin: 0 0 8px;
        font-weight: 600;
        font-size: clamp(1.05rem, 2.6vw, 1.35rem);
        line-height: 1.45;
    }

    .aicoach-tier-list li:last-child {
        margin-bottom: 0;
    }

    .aicoach-story {
        max-width: 640px;
        margin: 0 auto;
        padding-bottom: 24px;
    }

    .aicoach-story-title {
        margin: 0 0 20px;
        font-weight: 800;
        font-size: clamp(2.5rem, 8vw, 4rem);
        line-height: 1.05;
        text-align: center;
        text-transform: uppercase;
        color: #fdd65b;
    }

    .aicoach-duration-label {
        margin: 0 0 18px;
        font-weight: 800;
        font-size: clamp(1.75rem, 5vw, 2.5rem);
        line-height: 1.1;
        text-align: center;
        text-transform: capitalize;
        color: #fdd65b;
    }

    .aicoach-story-media {
        margin: 0 auto 36px;
        max-width: 624px;
    }

    .aicoach-story-photo {
        display: block;
        width: 100%;
        height: auto;
    }

    .aicoach-magic {
        position: relative;
        margin: 0 auto 28px;
        max-width: 596px;
    }

    .aicoach-magic-img {
        display: block;
        width: 100%;
        height: auto;
    }

    .aicoach-magic-name {
        position: absolute;
        left: 38%;
        top: 19%;
        margin: 0;
        font-weight: 800;
        font-size: clamp(2rem, 7vw, 4rem);
        line-height: 1.05;
        text-transform: uppercase;
        color: #fdd65b;
    }

    .aicoach-magic-name span {
        display: block;
    }

    .aicoach-story-points {
        margin: 0;
        padding: 0;
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 28px;
    }

    .aicoach-story-point {
        display: flex;
        align-items: flex-start;
        gap: 18px;
        margin: 0;
    }

    .aicoach-story-icon {
        flex-shrink: 0;
        width: 48px;
        height: 48px;
        object-fit: contain;
    }

    .aicoach-story-point > span {
        flex: 1;
        min-width: 0;
        font-weight: 800;
        font-size: clamp(1.05rem, 3.2vw, 1.5rem);
        line-height: 1.2;
        text-transform: uppercase;
        color: #fff;
        padding-top: 6px;
    }

    .aicoach-story-point > span::before {
        content: '• ';
    }

    .aicoach-story-sub {
        display: block;
        margin-top: 4px;
        padding-left: 0.85em;
        text-transform: none;
        font-weight: 800;
    }

    .aicoach-story-point--stacked .aicoach-story-sub {
        text-transform: none;
    }

    @media (max-width: 520px) {
        .aicoach-tier-head {
            gap: 14px;
        }

        .aicoach-tier-box {
            width: 36px;
            height: 36px;
            border-width: 3px;
            border-radius: 8px;
        }

        .aicoach-tiers {
            gap: 28px;
        }

        .aicoach-story-icon {
            width: 40px;
            height: 40px;
        }

        .aicoach-story-point {
            gap: 12px;
        }

        .aicoach-magic-name {
            left: 36%;
            top: 18%;
        }
    }
</style>

<script>
    window.AICOACH_SAMI = <?php echo wp_json_encode( $aicoach_sami_cfg ); ?>;
</script>
<script type="module">
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
</script>

<?php
get_template_part( 'template-parts/portal-scripts' );
get_footer();

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

$aicoach_img = array(
	'portrait'     => $theme_uri . '/images/aicoach/coach-portrait.png',
	'bts'          => $theme_uri . '/images/aicoach/bts.jpg',
	'magic'        => $theme_uri . '/images/aicoach/magic-johnson.png',
	// Placeholder — no licensed Alix Earle photo exists in this repo yet; swap
	// this src for the real asset once design/legal supplies one.
	'alix'         => $theme_uri . '/images/aicoach/alix-earle-placeholder.svg',
	'check'        => $theme_uri . '/images/aicoach/icon-check.svg',
	'x'            => $theme_uri . '/images/aicoach/icon-x.svg',
	'belief-coin'  => $theme_uri . '/images/aicoach/icon-belief-coin.svg',
	'belief-chart' => $theme_uri . '/images/aicoach/icon-belief-chart.svg',
	'trophy'       => $theme_uri . '/images/aicoach/icon-trophy.svg',
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

// FR-08 — the 8 available communication channels. KakaoTalk, Telegram, WeChat,
// and Zalo's exact contact-detail format/Braze attribute is marked "TBD" in the
// ticket itself, so those four take a plain non-empty value rather than an
// invented strict format (see js/aicoach-coach-flow.js CHANNELS for the
// matching client-side validation).
$aicoach_channels = array(
	array(
		'key'         => 'email',
		'label'       => 'Email',
		'input_label' => 'Email address',
		'input_type'  => 'email',
		'placeholder' => 'you@example.com',
	),
	array(
		'key'         => 'kakaotalk',
		'label'       => 'KakaoTalk',
		'input_label' => 'KakaoTalk ID or phone number',
		'input_type'  => 'text',
		'placeholder' => '',
	),
	array(
		'key'         => 'line',
		'label'       => 'Line',
		'input_label' => 'Line ID',
		'input_type'  => 'text',
		'placeholder' => 'U0123456789abcdef0123456789abcdef',
	),
	array(
		'key'         => 'sms',
		'label'       => 'SMS',
		'input_label' => 'Phone number',
		'input_type'  => 'tel',
		'placeholder' => '+66812345678',
	),
	array(
		'key'         => 'telegram',
		'label'       => 'Telegram',
		'input_label' => 'Telegram username or chat ID',
		'input_type'  => 'text',
		'placeholder' => '@username',
	),
	array(
		'key'         => 'wechat',
		'label'       => 'WeChat',
		'input_label' => 'WeChat ID',
		'input_type'  => 'text',
		'placeholder' => '',
	),
	array(
		'key'         => 'whatsapp',
		'label'       => 'WhatsApp',
		'input_label' => 'Phone number',
		'input_type'  => 'tel',
		'placeholder' => '+66812345678',
	),
	array(
		'key'         => 'zalo',
		'label'       => 'Zalo',
		'input_label' => 'Phone number or Zalo ID',
		'input_type'  => 'text',
		'placeholder' => '',
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
                        <p class="aicoach-caption" data-caption-for="home" aria-live="polite"></p>
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

                    <div class="aicoach-panel" data-panel="equity-magic" aria-hidden="true">
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
                            <p class="aicoach-caption" data-caption-for="equity-magic" aria-live="polite"></p>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="equity-alix" aria-hidden="true">
                        <div class="aicoach-story aicoach-story--magic">
                            <div class="aicoach-magic">
                                <img
                                    class="aicoach-magic-img"
                                    src="<?php echo esc_url( $aicoach_img['alix'] ); ?>"
                                    alt="<?php esc_attr_e( 'Alix Earle', 'influencer-hq' ); ?>"
                                    width="596"
                                    height="844"
                                >
                                <h2 class="aicoach-magic-name">
                                    <span><?php esc_html_e( 'Alix', 'influencer-hq' ); ?></span>
                                    <span><?php esc_html_e( 'Earle', 'influencer-hq' ); ?></span>
                                </h2>
                            </div>
                            <ul class="aicoach-story-points">
                                <li class="aicoach-story-point aicoach-story-point--stacked">
                                    <img class="aicoach-story-icon" src="<?php echo esc_url( $aicoach_img['check'] ); ?>" alt="" width="48" height="45" aria-hidden="true">
                                    <span><?php esc_html_e( 'Said yes to an ownership-based partnership with Poppi', 'influencer-hq' ); ?></span>
                                </li>
                                <li class="aicoach-story-point">
                                    <img class="aicoach-story-icon" src="<?php echo esc_url( $aicoach_img['x'] ); ?>" alt="" width="48" height="48" aria-hidden="true">
                                    <span><?php esc_html_e( 'Turned down cash', 'influencer-hq' ); ?></span>
                                </li>
                            </ul>
                            <p class="aicoach-caption" data-caption-for="equity-alix" aria-live="polite"></p>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="equity-bts" aria-hidden="true">
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
                                    <span><?php esc_html_e( 'Said yes to shared ownership, valued at 103.6 million', 'influencer-hq' ); ?></span>
                                </li>
                                <li class="aicoach-story-point">
                                    <img class="aicoach-story-icon" src="<?php echo esc_url( $aicoach_img['x'] ); ?>" alt="" width="48" height="48" aria-hidden="true">
                                    <span><?php esc_html_e( 'Turned down cash', 'influencer-hq' ); ?></span>
                                </li>
                            </ul>
                            <p class="aicoach-caption" data-caption-for="equity-bts" aria-live="polite"></p>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="competition-world" aria-hidden="true">
                        <div class="aicoach-competition">
                            <img class="aicoach-competition-icon" src="<?php echo esc_url( $aicoach_img['trophy'] ); ?>" alt="" width="72" height="72" aria-hidden="true">
                            <h2 class="aicoach-competition-name"><?php esc_html_e( 'World Competition', 'influencer-hq' ); ?></h2>
                            <p class="aicoach-competition-format">
                                <?php esc_html_e( 'You + Followers', 'influencer-hq' ); ?>
                                <span class="aicoach-competition-vs"><?php esc_html_e( 'versus', 'influencer-hq' ); ?></span>
                                <?php esc_html_e( 'All Influencers + Followers', 'influencer-hq' ); ?>
                            </p>
                            <p class="aicoach-caption" data-caption-for="competition-world" aria-live="polite"></p>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="competition-community" aria-hidden="true">
                        <div class="aicoach-competition">
                            <img class="aicoach-competition-icon" src="<?php echo esc_url( $aicoach_img['trophy'] ); ?>" alt="" width="72" height="72" aria-hidden="true">
                            <h2 class="aicoach-competition-name"><?php esc_html_e( 'Community Competition', 'influencer-hq' ); ?></h2>
                            <p class="aicoach-competition-format"><?php esc_html_e( 'Weekly competition for your followers only', 'influencer-hq' ); ?></p>
                            <p class="aicoach-caption" data-caption-for="competition-community" aria-live="polite"></p>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="competition-private" aria-hidden="true">
                        <div class="aicoach-competition">
                            <img class="aicoach-competition-icon" src="<?php echo esc_url( $aicoach_img['trophy'] ); ?>" alt="" width="72" height="72" aria-hidden="true">
                            <h2 class="aicoach-competition-name"><?php esc_html_e( 'Private Challenge', 'influencer-hq' ); ?></h2>
                            <p class="aicoach-competition-format">
                                <?php esc_html_e( 'You + Followers', 'influencer-hq' ); ?>
                                <span class="aicoach-competition-vs"><?php esc_html_e( 'versus', 'influencer-hq' ); ?></span>
                                <?php esc_html_e( 'An Influencer Friend + Followers', 'influencer-hq' ); ?>
                            </p>
                            <p class="aicoach-caption" data-caption-for="competition-private" aria-live="polite"></p>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="identity" aria-hidden="true">
                        <div class="aicoach-identity">
                            <h2 class="aicoach-identity-title"><?php esc_html_e( "Let's Start The Conversation", 'influencer-hq' ); ?></h2>
                            <p class="aicoach-identity-subtitle"><?php esc_html_e( 'Let us know who you are', 'influencer-hq' ); ?></p>
                            <form class="aicoach-identity-form" id="aicoach-identity-form" novalidate>
                                <label class="aicoach-identity-field">
                                    <span class="aicoach-identity-label"><?php esc_html_e( 'First Name', 'influencer-hq' ); ?></span>
                                    <input type="text" class="aicoach-identity-input" id="aicoach-first-name" autocomplete="given-name" required>
                                </label>
                                <label class="aicoach-identity-field">
                                    <span class="aicoach-identity-label"><?php esc_html_e( 'Last Name', 'influencer-hq' ); ?></span>
                                    <input type="text" class="aicoach-identity-input" id="aicoach-last-name" autocomplete="family-name" required>
                                </label>
                                <label class="aicoach-identity-field">
                                    <span class="aicoach-identity-label"><?php esc_html_e( 'Username', 'influencer-hq' ); ?></span>
                                    <input type="text" class="aicoach-identity-input" id="aicoach-username" autocomplete="username" required>
                                    <span class="aicoach-identity-error" id="aicoach-username-error" role="alert"></span>
                                </label>
                                <button type="submit" class="aicoach-form-continue" id="aicoach-form-continue" disabled>
                                    <?php esc_html_e( 'Continue', 'influencer-hq' ); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="comm-channels" aria-hidden="true">
                        <p class="aicoach-caption" data-caption-for="comm-channels" aria-live="polite"></p>
                        <form class="aicoach-channels" id="aicoach-channels-form" novalidate>
                            <p class="aicoach-channels-hint" id="aicoach-channels-hint">
                                <?php esc_html_e( 'Please select at least one communication method.', 'influencer-hq' ); ?>
                            </p>
                            <div class="aicoach-channel-list">
                                <?php foreach ( $aicoach_channels as $channel ) : ?>
                                <div class="aicoach-channel" data-channel="<?php echo esc_attr( $channel['key'] ); ?>">
                                    <label class="aicoach-channel-toggle">
                                        <input type="checkbox" class="aicoach-channel-check" data-channel="<?php echo esc_attr( $channel['key'] ); ?>">
                                        <span class="aicoach-channel-box" aria-hidden="true"></span>
                                        <span class="aicoach-channel-label"><?php echo esc_html( $channel['label'] ); ?></span>
                                    </label>
                                    <div class="aicoach-channel-field" hidden>
                                        <label class="aicoach-channel-field-label"><?php echo esc_html( $channel['input_label'] ); ?></label>
                                        <input
                                            type="<?php echo esc_attr( $channel['input_type'] ); ?>"
                                            class="aicoach-channel-input"
                                            data-channel="<?php echo esc_attr( $channel['key'] ); ?>"
                                            placeholder="<?php echo esc_attr( $channel['placeholder'] ); ?>"
                                        >
                                        <span class="aicoach-channel-error" role="alert"></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="aicoach-form-continue" id="aicoach-channels-continue" disabled>
                                <?php esc_html_e( 'Continue', 'influencer-hq' ); ?>
                            </button>
                        </form>
                    </div>

                    <div class="aicoach-panel" data-panel="final-continue" aria-hidden="true">
                        <div class="aicoach-final">
                            <p class="aicoach-caption" data-caption-for="final-continue" aria-live="polite"></p>
                            <p class="aicoach-final-error" id="aicoach-final-error" role="alert"></p>
                            <button type="button" class="aicoach-form-continue" id="aicoach-final-continue">
                                <?php esc_html_e( "Let's Continue", 'influencer-hq' ); ?>
                            </button>
                        </div>
                    </div>

                </div>

                <!--
                FR-17 — time-remaining check. Overlays whichever screen is
                currently active rather than being one of the aicoach-panel
                screens, since it can interrupt any of them. Copy and the
                trigger threshold are both explicitly marked as pending
                approval/confirmation in the ticket — placeholder text below,
                see js/aicoach-coach-flow.js for the threshold constant.
                -->
                <div class="aicoach-time-check" id="aicoach-time-check" aria-hidden="true" role="dialog" aria-modal="true">
                    <div class="aicoach-time-check-box">
                        <p class="aicoach-time-check-text">
                            <?php esc_html_e( "Looks like your selected time is almost up. Do you have a few more minutes to finish?", 'influencer-hq' ); ?>
                        </p>
                        <div class="aicoach-time-check-actions">
                            <button type="button" class="aicoach-time-check-btn aicoach-time-check-yes" id="aicoach-time-check-yes">
                                <?php esc_html_e( 'Yes', 'influencer-hq' ); ?>
                            </button>
                            <button type="button" class="aicoach-time-check-btn aicoach-time-check-no" id="aicoach-time-check-no">
                                <?php esc_html_e( 'No', 'influencer-hq' ); ?>
                            </button>
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
        margin: 0 auto 32px;
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

    .aicoach-competition {
        max-width: 560px;
        margin: 0 auto;
        text-align: center;
    }

    .aicoach-competition-icon {
        display: block;
        margin: 0 auto 20px;
        width: 72px;
        height: 72px;
    }

    .aicoach-competition-name {
        margin: 0 0 16px;
        font-weight: 800;
        font-size: clamp(1.5rem, 4.5vw, 2.25rem);
        text-transform: uppercase;
        color: #fdd65b;
    }

    .aicoach-competition-format {
        margin: 0 0 28px;
        font-weight: 700;
        font-size: clamp(1.05rem, 3vw, 1.3rem);
        line-height: 1.4;
        color: #fff;
    }

    .aicoach-competition-vs {
        display: block;
        margin: 4px 0;
        font-weight: 600;
        font-style: italic;
        text-transform: uppercase;
        font-size: clamp(0.85rem, 2.4vw, 1rem);
        color: #a9a9b3;
    }

    .aicoach-identity {
        max-width: 440px;
        margin: 0 auto;
        text-align: center;
    }

    .aicoach-identity-title {
        margin: 0 0 8px;
        font-weight: 800;
        font-size: clamp(1.5rem, 4.5vw, 2rem);
        color: #fdd65b;
    }

    .aicoach-identity-subtitle {
        margin: 0 0 32px;
        font-weight: 600;
        font-size: clamp(1rem, 2.8vw, 1.15rem);
        color: #fff;
    }

    .aicoach-identity-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .aicoach-identity-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
        text-align: left;
    }

    .aicoach-identity-label {
        font-weight: 700;
        font-size: 0.95rem;
        color: #fff;
    }

    .aicoach-identity-input {
        padding: 12px 14px;
        border: 2px solid #3a3b47;
        border-radius: 8px;
        background: #1b1c24;
        color: #fff;
        font-size: 1rem;
        font-family: inherit;
    }

    .aicoach-identity-input:focus {
        outline: none;
        border-color: #fdd65b;
    }

    .aicoach-identity-input.is-invalid {
        border-color: #eb0000;
    }

    .aicoach-identity-error {
        min-height: 1.2em;
        font-weight: 600;
        font-size: 0.85rem;
        color: #eb0000;
    }

    .aicoach-form-continue {
        margin-top: 8px;
        padding: 14px;
        border: none;
        border-radius: 8px;
        background: #fdd65b;
        color: #12131a;
        font-weight: 800;
        font-size: 1.05rem;
        cursor: pointer;
    }

    .aicoach-form-continue:disabled {
        background: #3a3b47;
        color: #7a7b87;
        cursor: not-allowed;
    }

    .aicoach-channels {
        max-width: 440px;
        margin: 0 auto;
    }

    .aicoach-channels-hint {
        margin: 0 0 20px;
        font-weight: 600;
        font-size: 0.9rem;
        text-align: center;
        color: #a9a9b3;
    }

    .aicoach-channels-hint.is-error {
        color: #eb0000;
    }

    .aicoach-channel-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
        margin: 0 0 24px;
    }

    .aicoach-channel {
        padding: 14px 16px;
        border: 2px solid #3a3b47;
        border-radius: 10px;
        background: #1b1c24;
    }

    .aicoach-channel-toggle {
        display: flex;
        align-items: center;
        gap: 14px;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
    }

    .aicoach-channel-check {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .aicoach-channel-box {
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        border: 2px solid #fdd65b;
        border-radius: 6px;
        background: transparent;
        box-sizing: border-box;
    }

    .aicoach-channel-toggle:has(.aicoach-channel-check:checked) .aicoach-channel-box {
        background: #fdd65b;
    }

    .aicoach-channel-label {
        font-weight: 700;
        color: #fff;
    }

    .aicoach-channel-field {
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid #3a3b47;
        text-align: left;
    }

    .aicoach-channel-field-label {
        display: block;
        margin: 0 0 8px;
        font-weight: 600;
        font-size: 0.9rem;
        color: #fff;
    }

    .aicoach-channel-input {
        width: 100%;
        box-sizing: border-box;
        padding: 10px 12px;
        border: 2px solid #3a3b47;
        border-radius: 8px;
        background: #12131a;
        color: #fff;
        font-size: 0.95rem;
        font-family: inherit;
    }

    .aicoach-channel-input:focus {
        outline: none;
        border-color: #fdd65b;
    }

    .aicoach-channel-input.is-invalid {
        border-color: #eb0000;
    }

    .aicoach-channel-error {
        display: block;
        min-height: 1.1em;
        margin-top: 6px;
        font-weight: 600;
        font-size: 0.8rem;
        color: #eb0000;
    }

    .aicoach-final {
        max-width: 440px;
        margin: 0 auto;
        text-align: center;
    }

    .aicoach-final-error {
        min-height: 1.2em;
        margin: 0 0 16px;
        font-weight: 600;
        font-size: 0.9rem;
        color: #eb0000;
    }

    .aicoach-time-check {
        display: none;
        position: fixed;
        inset: 0;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(11, 12, 16, .8);
        backdrop-filter: blur(4px);
        z-index: 10040;
    }

    .aicoach-time-check.is-visible {
        display: flex;
    }

    .aicoach-time-check-box {
        max-width: 380px;
        width: 100%;
        padding: 28px 24px;
        border: 2px solid #fdd65b;
        border-radius: 12px;
        background: #1b1c24;
        text-align: center;
    }

    .aicoach-time-check-text {
        margin: 0 0 24px;
        font-weight: 700;
        font-size: 1.05rem;
        line-height: 1.4;
        color: #fff;
    }

    .aicoach-time-check-actions {
        display: flex;
        gap: 12px;
    }

    .aicoach-time-check-btn {
        flex: 1;
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-weight: 800;
        font-size: 1rem;
        cursor: pointer;
    }

    .aicoach-time-check-yes {
        background: #fdd65b;
        color: #12131a;
    }

    .aicoach-time-check-no {
        background: transparent;
        border: 2px solid #3a3b47;
        color: #fff;
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

<?php
/*
 * The coach-flow script (js/aicoach-coach-flow.js) is enqueued conditionally
 * for this template, along with its AICOACH_SAMI config, in inc/anam-proxy.php.
 */
get_template_part( 'template-parts/portal-scripts' );
get_footer();

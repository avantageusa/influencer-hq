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

// PO-3109 — where "CONTINUE TO IHQ PORTAL" on the appointment's final screen goes:
// the IHQ Coach page, the same address as the "Coach" item of the portal header
// (template-parts/portal-header.php).
$aicoach_portal_url = home_url( '/portal-home' );

// Flagged by Dejan Arsic on PR #53: Cloudflare caches this upload path for a
// full year, and a purge only clears the edge cache, not a browser that
// already has this exact URL cached locally, so swapping a file in place (as
// this one just did, to fix the idle-portrait/live-avatar mismatch) would
// stay invisible to any visitor who loaded this page before the purge. The
// file time in the URL is the cache-buster.
// The Sami portrait follows the avatar version being served (inc/aicoach-prerender.php), so
// it changes together with the pre-rendered clips.
$portrait_file       = ihq_aicoach_portrait_file();
$portrait_image_path = get_template_directory() . '/images/aicoach/' . $portrait_file;
$portrait_image_url  = $theme_uri . '/images/aicoach/' . $portrait_file;
$portrait_mtime      = file_exists( $portrait_image_path ) ? filemtime( $portrait_image_path ) : false;
if ( $portrait_mtime ) {
	$portrait_image_url = add_query_arg( 'v', $portrait_mtime, $portrait_image_url );
}

// PO-3062 (design alignment) — the screen images come from the updated design and live in
// images/aicoach/design/. They have names of their own (not the old files' names), so a
// browser or the edge that cached the old pictures for a year cannot serve them by mistake.
$aicoach_design_dir = $theme_uri . '/images/aicoach/design';

$aicoach_img = array(
	'portrait'           => $portrait_image_url,
	'bts'                => $aicoach_design_dir . '/bts.webp',
	'magic'              => $aicoach_design_dir . '/magic-johnson.webp',
	'alix'               => $aicoach_design_dir . '/alix-earle.webp',
	'check'              => $aicoach_design_dir . '/check.svg',
	'x'                  => $aicoach_design_dir . '/x.svg',
	'belief-coin'        => $aicoach_design_dir . '/belief-coin.webp',
	'belief-chart'       => $aicoach_design_dir . '/belief-chart.webp',
	'belief-certificate' => $aicoach_design_dir . '/belief-certificate.webp',
	'flag-mask'          => $aicoach_design_dir . '/flag-mask.png',
	'back-arrow'         => $aicoach_design_dir . '/back-arrow.svg',
	'globe'              => $aicoach_design_dir . '/globe.webp',
	'sound'              => $aicoach_design_dir . '/sound.svg',
	'banner-desktop'     => $aicoach_design_dir . '/banner-desktop.webp',
	'banner-tablet'      => $aicoach_design_dir . '/banner-tablet.webp',
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

        <div class="container py-2 aicoach-page" id="portal-content">

            <section class="aicoach" aria-label="<?php esc_attr_e( 'AI Coach', 'influencer-hq' ); ?>">

                <?php /* PO-3062 (design alignment) — Sami is a full-width banner under the header, with the commentary box right under it. The Ask Sami button is added to .aicoach-banner by js/aicoach-coach-flow.js. */ ?>
                <div class="aicoach-banner">
                    <div class="aicoach-avatar-wrap" id="aicoach-avatar-wrap" data-status="idle">
                        <?php /* The banner is the design's wide picture on a desktop (1025 px is the desktop breakpoint the portal pages already use), the tablet picture from 600 px up, and the 3:2 portrait on a phone. */ ?>
                        <picture>
                            <source media="(min-width: 1025px)" srcset="<?php echo esc_url( $aicoach_img['banner-desktop'] ); ?>">
                            <source media="(min-width: 600px)" srcset="<?php echo esc_url( $aicoach_img['banner-tablet'] ); ?>">
                            <img
                                class="aicoach-portrait"
                                id="aicoach-portrait"
                                src="<?php echo esc_url( $aicoach_img['portrait'] ); ?>"
                                alt="<?php esc_attr_e( 'AI Coach', 'influencer-hq' ); ?>"
                                width="600"
                                height="400"
                                fetchpriority="high"
                            >
                        </picture>
                        <video class="aicoach-avatar-video" id="aicoach-avatar-video" autoplay playsinline muted></video>
                        <div class="aicoach-avatar-loading" aria-hidden="true"><div class="aicoach-avatar-spinner"></div></div>
                    </div>
                </div>

                <?php /* What Sami is saying: the words appear in the first element as she says them (js/aicoach/commentary.js); the second holds the whole script once, hidden from sight, for a screen reader. */ ?>
                <div class="aicoach-commentary" id="aicoach-commentary">
                    <p class="aicoach-commentary-text" id="aicoach-commentary-text" aria-hidden="true"></p>
                    <p class="aicoach-commentary-sr" id="aicoach-commentary-sr" aria-live="polite"></p>
                </div>

                <div class="aicoach-stage" id="aicoach-stage">

                    <?php /* The intro has no screen content of its own: only Sami and what she says. */ ?>
                    <div class="aicoach-panel is-active" data-panel="intro" aria-hidden="false"></div>

                    <?php /* "We Believe" is one screen for both narrations, so the two panels look the same while the two clips play one after the other. */ ?>
                    <?php foreach ( array( 'believe-1', 'believe-2' ) as $believe_panel ) : ?>
                    <div class="aicoach-panel" data-panel="<?php echo esc_attr( $believe_panel ); ?>" aria-hidden="true">
                        <div class="aicoach-believe">
                            <h2 class="aicoach-believe-title" data-i18n="weBelieve"><?php esc_html_e( 'We Believe', 'influencer-hq' ); ?></h2>
                            <div class="aicoach-believe-icons" aria-hidden="true">
                                <span class="aicoach-believe-coin"><img src="<?php echo esc_url( $aicoach_img['belief-coin'] ); ?>" alt="" width="400" height="400"></span>
                                <img class="aicoach-believe-chart" src="<?php echo esc_url( $aicoach_img['belief-chart'] ); ?>" alt="" width="520" height="423">
                                <span class="aicoach-believe-certificate"><img src="<?php echo esc_url( $aicoach_img['belief-certificate'] ); ?>" alt="" width="400" height="400"></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="aicoach-panel" data-panel="home" aria-hidden="true">
                        <div class="aicoach-tiers" role="radiogroup" data-i18n-attr="aria-label:tierGroupLabel" aria-label="<?php esc_attr_e( 'Conversation length', 'influencer-hq' ); ?>">
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
                                    <span class="aicoach-tier-duration" data-i18n="tier-<?php echo esc_attr( $tier['key'] ); ?>-duration"><?php echo esc_html( $tier['duration'] ); ?></span>
                                </label>
                                <ul class="aicoach-tier-list">
                                    <?php foreach ( $tier['items'] as $item_index => $item ) : ?>
                                    <li data-i18n="tier-<?php echo esc_attr( $tier['key'] ); ?>-item-<?php echo esc_attr( $item_index ); ?>"><?php echo esc_html( $item ); ?></li>
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
                                    data-src="<?php echo esc_url( $aicoach_img['magic'] ); ?>"
                                    alt="<?php esc_attr_e( 'Magic Johnson', 'influencer-hq' ); ?>"
                                    width="500"
                                    height="707"
                                >
                                <h2 class="aicoach-magic-name">
                                    <span><?php esc_html_e( 'Magic', 'influencer-hq' ); ?></span>
                                    <span><?php esc_html_e( 'Johnson', 'influencer-hq' ); ?></span>
                                </h2>
                            </div>
                            <ul class="aicoach-story-points">
                                <li class="aicoach-story-point">
                                    <img class="aicoach-story-icon aicoach-story-icon--check" src="<?php echo esc_url( $aicoach_img['check'] ); ?>" alt="" width="49" height="46" aria-hidden="true">
                                    <span data-i18n="magicAdidas"><?php esc_html_e( 'Adidas $100k cash', 'influencer-hq' ); ?></span>
                                </li>
                                <li class="aicoach-story-point aicoach-story-point--x aicoach-story-point--stacked">
                                    <img class="aicoach-story-icon aicoach-story-icon--cross" src="<?php echo esc_url( $aicoach_img['x'] ); ?>" alt="" width="48" height="48" aria-hidden="true">
                                    <span>
                                        <span data-i18n="magicNike"><?php esc_html_e( "Nike's 11 cent stock", 'influencer-hq' ); ?></span>
                                        <span class="aicoach-story-sub" data-i18n="magicNikeWorth"><?php esc_html_e( 'Now worth 5.4 billion', 'influencer-hq' ); ?></span>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="equity-alix" aria-hidden="true">
                        <div class="aicoach-story aicoach-story--alix">
                            <h2 class="aicoach-story-title"><?php esc_html_e( 'Alix Earle', 'influencer-hq' ); ?></h2>
                            <div class="aicoach-story-media">
                                <img
                                    class="aicoach-story-photo"
                                    src="<?php echo esc_url( $aicoach_img['alix'] ); ?>"
                                    alt="<?php esc_attr_e( 'Alix Earle', 'influencer-hq' ); ?>"
                                    width="711"
                                    height="698"
                                >
                            </div>
                            <ul class="aicoach-story-points">
                                <li class="aicoach-story-point aicoach-story-point--stacked">
                                    <img class="aicoach-story-icon aicoach-story-icon--check" src="<?php echo esc_url( $aicoach_img['check'] ); ?>" alt="" width="49" height="46" aria-hidden="true">
                                    <span data-i18n="alixPoppi"><?php esc_html_e( 'Said yes to an ownership-based partnership with Poppi', 'influencer-hq' ); ?></span>
                                </li>
                                <li class="aicoach-story-point aicoach-story-point--x">
                                    <img class="aicoach-story-icon aicoach-story-icon--cross" src="<?php echo esc_url( $aicoach_img['x'] ); ?>" alt="" width="48" height="48" aria-hidden="true">
                                    <span data-i18n="turnedDownCash"><?php esc_html_e( 'Turned down cash', 'influencer-hq' ); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="equity-bts" aria-hidden="true">
                        <div class="aicoach-story aicoach-story--bts">
                            <h2 class="aicoach-story-title"><?php esc_html_e( 'BTS', 'influencer-hq' ); ?></h2>
                            <div class="aicoach-story-media">
                                <img
                                    class="aicoach-story-photo"
                                    data-src="<?php echo esc_url( $aicoach_img['bts'] ); ?>"
                                    alt="<?php esc_attr_e( 'BTS', 'influencer-hq' ); ?>"
                                    width="520"
                                    height="392"
                                >
                            </div>
                            <ul class="aicoach-story-points">
                                <li class="aicoach-story-point">
                                    <img class="aicoach-story-icon aicoach-story-icon--check" src="<?php echo esc_url( $aicoach_img['check'] ); ?>" alt="" width="49" height="46" aria-hidden="true">
                                    <span data-i18n="btsOwnership"><?php esc_html_e( 'Said yes to shared ownership, valued at 103.6 million', 'influencer-hq' ); ?></span>
                                </li>
                                <li class="aicoach-story-point aicoach-story-point--x">
                                    <img class="aicoach-story-icon aicoach-story-icon--cross" src="<?php echo esc_url( $aicoach_img['x'] ); ?>" alt="" width="48" height="48" aria-hidden="true">
                                    <span data-i18n="turnedDownCash"><?php esc_html_e( 'Turned down cash', 'influencer-hq' ); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="competition-world" aria-hidden="true">
                        <div class="aicoach-competition">
                            <h2 class="aicoach-competition-name" data-i18n="worldCompetitionName"><?php esc_html_e( 'World Competition', 'influencer-hq' ); ?></h2>
                            <span class="aicoach-flag" aria-hidden="true"></span>
                            <p class="aicoach-competition-format">
                                <span data-i18n="weekly"><?php esc_html_e( 'Weekly', 'influencer-hq' ); ?></span>
                                <span data-i18n="youFollowers"><?php esc_html_e( 'You + Followers', 'influencer-hq' ); ?></span>
                                <span data-i18n="versus"><?php esc_html_e( 'versus', 'influencer-hq' ); ?></span>
                                <span data-i18n="allInfluencersFollowers"><?php esc_html_e( 'All Influencers + Followers', 'influencer-hq' ); ?></span>
                            </p>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="competition-community" aria-hidden="true">
                        <div class="aicoach-competition">
                            <h2 class="aicoach-competition-name" data-i18n="communityCompetitionName"><?php esc_html_e( 'Community Competition', 'influencer-hq' ); ?></h2>
                            <span class="aicoach-flag" aria-hidden="true"></span>
                            <p class="aicoach-competition-format" data-i18n="communityCompetitionFormat"><?php esc_html_e( 'Weekly COMPETITION for your FOLLOWERS only', 'influencer-hq' ); ?></p>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="competition-private" aria-hidden="true">
                        <div class="aicoach-competition">
                            <h2 class="aicoach-competition-name" data-i18n="privateChallengeName"><?php esc_html_e( 'Private Challenges', 'influencer-hq' ); ?></h2>
                            <span class="aicoach-flag" aria-hidden="true"></span>
                            <p class="aicoach-competition-format">
                                <span data-i18n="youFollowers"><?php esc_html_e( 'You + Followers', 'influencer-hq' ); ?></span>
                                <span data-i18n="versus"><?php esc_html_e( 'versus', 'influencer-hq' ); ?></span>
                                <span data-i18n="friendFollowers"><?php esc_html_e( 'An Influencer Friend + Followers', 'influencer-hq' ); ?></span>
                            </p>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="identity" aria-hidden="true">
                        <div class="aicoach-identity">
                            <p class="aicoach-belief" data-i18n="belief"><?php esc_html_e( 'We believe conversations should be easy.', 'influencer-hq' ); ?></p>
                            <p class="aicoach-identity-subtitle" data-i18n="identitySubtitle"><?php esc_html_e( 'Let us know who you are', 'influencer-hq' ); ?></p>
                            <h2 class="aicoach-identity-title" data-i18n="identityTitle"><?php esc_html_e( "Let's Start The Conversation", 'influencer-hq' ); ?></h2>
                            <form class="aicoach-identity-form" id="aicoach-identity-form" novalidate>
                                <label class="aicoach-identity-field">
                                    <span class="aicoach-identity-label" data-i18n="firstName"><?php esc_html_e( 'First Name', 'influencer-hq' ); ?></span>
                                    <input type="text" class="aicoach-identity-input" id="aicoach-first-name" autocomplete="given-name" required>
                                </label>
                                <label class="aicoach-identity-field">
                                    <span class="aicoach-identity-label" data-i18n="lastName"><?php esc_html_e( 'Last Name', 'influencer-hq' ); ?></span>
                                    <input type="text" class="aicoach-identity-input" id="aicoach-last-name" autocomplete="family-name" required>
                                </label>
                                <label class="aicoach-identity-field">
                                    <span class="aicoach-identity-label" data-i18n="username"><?php esc_html_e( 'Username', 'influencer-hq' ); ?></span>
                                    <input type="text" class="aicoach-identity-input" id="aicoach-username" autocomplete="username" required>
                                    <span class="aicoach-identity-error" id="aicoach-username-error" role="alert"></span>
                                </label>
                                <button type="submit" class="aicoach-form-continue" id="aicoach-form-continue" disabled data-i18n="continue">
                                    <?php esc_html_e( 'Continue', 'influencer-hq' ); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="aicoach-panel" data-panel="comm-channels" aria-hidden="true">
                        <form class="aicoach-channels" id="aicoach-channels-form" novalidate>
                            <p class="aicoach-volume-note" data-i18n="volumeReminder"><?php esc_html_e( 'Please Remember to Turn Up Your Volume', 'influencer-hq' ); ?></p>
                            <h2 class="aicoach-identity-title" data-i18n="identityTitle"><?php esc_html_e( "Let's Start The Conversation", 'influencer-hq' ); ?></h2>
                            <p class="aicoach-channels-hint" id="aicoach-channels-hint" data-i18n="channelsHint">
                                <?php esc_html_e( 'Please select at least one communication method.', 'influencer-hq' ); ?>
                            </p>
                            <div class="aicoach-channel-list">
                                <?php foreach ( $aicoach_channels as $channel ) : ?>
                                <div class="aicoach-channel" data-channel="<?php echo esc_attr( $channel['key'] ); ?>">
                                    <label class="aicoach-channel-toggle">
                                        <input type="checkbox" class="aicoach-channel-check" data-channel="<?php echo esc_attr( $channel['key'] ); ?>">
                                        <span class="aicoach-channel-box" aria-hidden="true"></span>
                                        <span class="aicoach-channel-label" data-i18n="channel-<?php echo esc_attr( $channel['key'] ); ?>-label"><?php echo esc_html( $channel['label'] ); ?></span>
                                    </label>
                                    <div class="aicoach-channel-field" hidden>
                                        <label class="aicoach-channel-field-label" data-i18n="channel-<?php echo esc_attr( $channel['key'] ); ?>-inputLabel"><?php echo esc_html( $channel['input_label'] ); ?></label>
                                        <input
                                            type="<?php echo esc_attr( $channel['input_type'] ); ?>"
                                            class="aicoach-channel-input"
                                            data-channel="<?php echo esc_attr( $channel['key'] ); ?>"
                                            data-i18n-attr="placeholder:channel-<?php echo esc_attr( $channel['key'] ); ?>-placeholder"
                                            placeholder="<?php echo esc_attr( $channel['placeholder'] ); ?>"
                                        >
                                        <span class="aicoach-channel-error" role="alert"></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="aicoach-form-continue" id="aicoach-channels-continue" disabled data-i18n="continue">
                                <?php esc_html_e( 'Continue', 'influencer-hq' ); ?>
                            </button>
                        </form>
                    </div>

                    <div class="aicoach-panel" data-panel="final-continue" aria-hidden="true">
                        <div class="aicoach-final">
                            <p class="aicoach-final-error" id="aicoach-final-error" role="alert"></p>
                            <button type="button" class="aicoach-form-continue" id="aicoach-final-continue" data-i18n="letsContinue">
                                <?php esc_html_e( "Let's Continue", 'influencer-hq' ); ?>
                            </button>
                        </div>
                    </div>

                    <?php /* PO-3109 — FR-17's time-remaining check, now the "Time is up?" screen. Takes the stage from whichever screen is showing (js/aicoach-coach-flow.js, openTimeUp()) and gives it back; transient, never saved as the visitor's stage. */ ?>
                    <div class="aicoach-panel" data-panel="time-up" aria-hidden="true">
                        <div class="aicoach-timeup">
                            <h2 class="aicoach-timeup-title" id="aicoach-timeup-title" data-i18n="timeUpTitle"><?php esc_html_e( 'Time is up?', 'influencer-hq' ); ?></h2>
                            <div class="aicoach-timeup-choices" role="radiogroup" aria-labelledby="aicoach-timeup-title">
                                <label class="aicoach-timeup-choice">
                                    <input class="aicoach-timeup-check" type="radio" name="aicoach_time_up" value="keep">
                                    <span class="aicoach-timeup-box" aria-hidden="true"></span>
                                    <span class="aicoach-timeup-label" data-i18n="timeUpKeepTalking"><?php esc_html_e( 'Keep Talking Now', 'influencer-hq' ); ?></span>
                                </label>
                                <label class="aicoach-timeup-choice">
                                    <input class="aicoach-timeup-check" type="radio" name="aicoach_time_up" value="appointment">
                                    <span class="aicoach-timeup-box" aria-hidden="true"></span>
                                    <span class="aicoach-timeup-label" data-i18n="timeUpSetAppointment"><?php esc_html_e( 'Set an Appointment', 'influencer-hq' ); ?></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <?php /* PO-3109 — FR-18's "Set your appointment" screen, reached from Time is up (js/aicoach-coach-flow.js, startAppointmentScheduling()). Transient like time-up. Other replaces the three options with the Date / Timezone / Appointment Time fields; there is no way back, the flow only moves forward. */ ?>
                    <div class="aicoach-panel" data-panel="appointment" aria-hidden="true">
                        <div class="aicoach-appt">
                            <h2 class="aicoach-appt-ready" data-i18n="appointmentReady"><?php esc_html_e( "Yes, I'm Ready...", 'influencer-hq' ); ?></h2>
                            <p class="aicoach-appt-title" id="aicoach-appt-title" data-i18n="appointmentTitle"><?php esc_html_e( 'Set your appointment', 'influencer-hq' ); ?></p>
                            <div class="aicoach-timeup-choices aicoach-appt-choices" id="aicoach-appt-choices" role="radiogroup" aria-labelledby="aicoach-appt-title">
                                <label class="aicoach-timeup-choice">
                                    <input class="aicoach-timeup-check aicoach-appt-check" type="radio" name="aicoach_appointment" value="in-30-minutes">
                                    <span class="aicoach-timeup-box" aria-hidden="true"></span>
                                    <span class="aicoach-appt-label" data-i18n="appointmentIn30Minutes"><?php esc_html_e( 'In 30 minutes', 'influencer-hq' ); ?></span>
                                </label>
                                <label class="aicoach-timeup-choice">
                                    <input class="aicoach-timeup-check aicoach-appt-check" type="radio" name="aicoach_appointment" value="in-an-hour">
                                    <span class="aicoach-timeup-box" aria-hidden="true"></span>
                                    <span class="aicoach-appt-label" data-i18n="appointmentInAnHour"><?php esc_html_e( 'In an hour', 'influencer-hq' ); ?></span>
                                </label>
                                <label class="aicoach-timeup-choice">
                                    <input class="aicoach-timeup-check aicoach-appt-check" type="radio" name="aicoach_appointment" value="other">
                                    <span class="aicoach-timeup-box" aria-hidden="true"></span>
                                    <span class="aicoach-appt-label" data-i18n="appointmentOther"><?php esc_html_e( 'Other', 'influencer-hq' ); ?></span>
                                </label>
                            </div>
                            <div class="aicoach-appt-other" id="aicoach-appt-other" hidden>
                                <div class="aicoach-appt-row">
                                    <label class="aicoach-appt-field">
                                        <span class="aicoach-appt-field-label" data-i18n="appointmentDate"><?php esc_html_e( 'Date', 'influencer-hq' ); ?></span>
                                        <input class="aicoach-appt-input" type="date" id="aicoach-appt-date">
                                    </label>
                                    <label class="aicoach-appt-field">
                                        <span class="aicoach-appt-field-label" data-i18n="appointmentTimeZone"><?php esc_html_e( 'Timezone', 'influencer-hq' ); ?></span>
                                        <select class="aicoach-appt-input" id="aicoach-appt-timezone"></select>
                                    </label>
                                </div>
                                <label class="aicoach-appt-field">
                                    <span class="aicoach-appt-field-label" data-i18n="appointmentTime"><?php esc_html_e( 'Appointment Time', 'influencer-hq' ); ?></span>
                                    <input class="aicoach-appt-input" type="time" id="aicoach-appt-time">
                                </label>
                            </div>
                            <p class="aicoach-appt-error" id="aicoach-appt-error" role="alert"></p>
                            <button type="button" class="aicoach-appt-copy" id="aicoach-appt-copy" data-i18n="appointmentCopy"><?php esc_html_e( 'Copy Your Appointment Link', 'influencer-hq' ); ?></button>
                            <p class="aicoach-appt-status" id="aicoach-appt-status" role="status"></p>
                            <input class="aicoach-appt-input aicoach-appt-link" type="text" id="aicoach-appt-link" readonly hidden aria-label="<?php esc_attr_e( 'Appointment link', 'influencer-hq' ); ?>">
                            <p class="aicoach-appt-hint" data-i18n="appointmentHint"><?php esc_html_e( 'Open this link at your appointment time to join your video with Sami.', 'influencer-hq' ); ?></p>
                        </div>
                    </div>

                    <?php /* PO-3109 — the screen after the appointment link is copied (design: "You are all set up"). Transient. The button goes to the IHQ Coach page ($aicoach_portal_url). */ ?>
                    <div class="aicoach-panel" data-panel="appointment-done" aria-hidden="true">
                        <div class="aicoach-appt-done">
                            <p class="aicoach-appt-done-message" data-i18n="appointmentDoneMessage"><?php esc_html_e( "I'll always be here for you 24 hours a day, 7 days a week. If you have not saved IHQ portal address already, make sure to do that now.", 'influencer-hq' ); ?></p>
                            <h2 class="aicoach-appt-ready" data-i18n="appointmentDoneTitle"><?php esc_html_e( 'You are all set up', 'influencer-hq' ); ?></h2>
                            <a class="aicoach-appt-copy aicoach-appt-portal" href="<?php echo esc_url( $aicoach_portal_url ); ?>" data-i18n="appointmentDoneContinue"><?php esc_html_e( 'CONTINUE TO IHQ PORTAL', 'influencer-hq' ); ?></a>
                        </div>
                    </div>

                </div>

            </section>

        </div>

        <?php get_template_part( 'template-parts/portal-footer' ); ?>
    </main><!-- #main -->

<?php /* The design uses the ExtraBold and Black weights, which the shared portal fonts link (template-parts/portal-styles.php) does not load. */ ?>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@800&family=Inter:wght@800;900&display=swap" rel="stylesheet">
<style>
    /* PO-3062 (design alignment) — the frame every screen shares: the header, the banner Sami
    is shown in and the commentary box under it. Sizes are the design's, drawn for a 398 px
    wide phone; the column is capped at 480 px and centred on a wider screen. */
    #portal-content.aicoach-page {
        max-width: 480px;
        padding-left: 0;
        padding-right: 0;
    }

    .aicoach {
        --aicoach-yellow: #fdd65b;
        --aicoach-green: #148942;
        --aicoach-box-green: #138a42;
        --aicoach-commentary-bg: #2d2b2b;
        --aicoach-field-bg: #161612;
        max-width: 480px;
        margin: 0 auto;
        /* NFR-02 — no hardcoded top clearance here: #portal-content (see the
        wrapping .aicoach-page container) gets its padding-top set dynamically
        by adjustContentPadding() in portal-header.php, which measures the
        sticky header's actual rendered height. */
        padding: 0 0 40px;
        color: #fff;
        font-family: 'Be Vietnam Pro', sans-serif;
    }

    .aicoach-banner {
        position: relative;
    }

    .aicoach-avatar-wrap {
        position: relative;
        width: 100%;
        aspect-ratio: 398 / 182;
        margin: 0;
        overflow: hidden;
        background: #12131a;
    }

    /* What Sami is saying (js/aicoach/commentary.js). The box is part of the frame, so it stays
    when nothing is said; it is left out on the identity screen, which the design draws without it. */
    .aicoach-commentary {
        box-sizing: border-box;
        display: flex;
        align-items: center;
        justify-content: center;
        width: min(316px, 79.4%);
        min-height: 86px;
        margin: 6px auto 0;
        padding: 8px 16px;
        border: 1px dashed #fff;
        background: var(--aicoach-commentary-bg);
    }

    .aicoach:has(.aicoach-panel[data-panel="identity"].is-active) .aicoach-commentary {
        display: none;
    }

    .aicoach-commentary-text {
        max-width: 197px;
        margin: 0;
        font-family: 'Inter', 'Be Vietnam Pro', sans-serif;
        font-weight: 300;
        font-style: italic;
        font-size: 14px;
        line-height: 24px;
        text-align: center;
        color: #fff;
    }

    .aicoach-commentary-sr {
        position: absolute;
        width: 1px;
        height: 1px;
        margin: -1px;
        overflow: hidden;
        clip: rect(0 0 0 0);
        white-space: nowrap;
    }

    .aicoach-portrait,
    .aicoach-avatar-video {
        position: absolute;
        inset: 0;
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        /* The 3:2 picture and clips are cropped top and bottom to the banner; keep her head in. */
        object-position: center 10%;
        transition: opacity 0.32s ease;
    }

    .aicoach-avatar-video { opacity: 0; }
    .aicoach-avatar-wrap[data-status="live"] .aicoach-avatar-video { opacity: 1; }
    .aicoach-avatar-wrap[data-status="live"] .aicoach-portrait { opacity: 0; }
    .aicoach-avatar-wrap[data-status="connecting"] .aicoach-portrait { opacity: .4; filter: brightness(.55) blur(1px); }

    /* ENGR-7051 — while Ask Sami's panel is open the shared <video> is paused
    (js/aicoach-coach-flow.js, syncAvatarIdle()), which on its own leaves her
    frozen on whatever frame she was on. .is-idle swaps that frame for her
    neutral portrait with a slow "breathing" loop. The portrait is made opaque
    instantly (transition: none) so the video fades out over it rather than
    both layers dipping through the dark background. Skipped while connecting —
    the loading overlay owns that state. transform-only, inside this wrapper's
    overflow mask, so layout and the banner's size never change. */
    .aicoach-avatar-wrap.is-idle:not([data-status="connecting"]) .aicoach-avatar-video { opacity: 0; }

    .aicoach-avatar-wrap.is-idle:not([data-status="connecting"]) .aicoach-portrait {
        opacity: 1;
        filter: none;
        transition: none;
        transform-origin: 50% 35%;
        will-change: transform;
        animation: aicoach-idle-breathe 5.5s ease-in-out infinite;
    }

    @keyframes aicoach-idle-breathe {
        0%, 100% { transform: scale(1) translateY(0); }
        50% { transform: scale(1.03) translateY(-1%); }
    }

    @media (prefers-reduced-motion: reduce) {
        .aicoach-avatar-wrap.is-idle:not([data-status="connecting"]) .aicoach-portrait {
            animation: none;
            will-change: auto;
        }
    }

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

    /* "We Believe": one screen for both narrations. */
    .aicoach-believe {
        padding-top: 101px;
        text-align: center;
    }

    .aicoach-believe-title {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 41px;
        margin: 0;
        font-family: 'Cinzel', serif;
        font-weight: 700;
        font-size: 22.7px;
        line-height: normal;
        color: #fff;
    }

    .aicoach-believe-icons {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        padding: 0 14.8%;
        margin-top: 39px;
        row-gap: 52px;
    }

    /* The artwork is cropped inside its box the way the design crops it. */
    .aicoach-believe-coin,
    .aicoach-believe-certificate {
        position: relative;
        display: block;
        overflow: hidden;
    }

    .aicoach-believe-coin {
        width: 97.1px;
        height: 95.4px;
    }

    .aicoach-believe-coin img {
        position: absolute;
        left: -27.38%;
        top: -22.42%;
        width: 152.98%;
        height: 155.76%;
        max-width: none;
    }

    .aicoach-believe-chart {
        width: 85.7px;
        height: 69.7px;
        object-fit: cover;
    }

    .aicoach-believe-certificate {
        width: 109.6px;
        height: 80px;
        margin: 0 auto;
    }

    .aicoach-believe-certificate img {
        position: absolute;
        left: -17.56%;
        top: -37.88%;
        width: 134.21%;
        height: 183.84%;
        max-width: none;
    }

    /* The three competitions: name, the gold flag, three or four lines. */
    .aicoach-competition {
        padding-top: 37px;
        text-align: center;
    }

    .aicoach-competition-name {
        max-width: 269px;
        margin: 0 auto;
        font-weight: 800;
        font-size: 32px;
        line-height: 1.2;
        text-transform: uppercase;
        color: var(--aicoach-yellow);
    }

    .aicoach-flag {
        display: block;
        width: 87.9px;
        height: 87.4px;
        margin: 28px auto 0;
        background-image: linear-gradient(155.28deg, #856220 0.18%, #f4e683 34.35%, #bf923d 62.32%, #f1ea82 94.58%, #4e341b 124.45%);
        -webkit-mask: url('<?php echo esc_url( $aicoach_img['flag-mask'] ); ?>') center / 100% 100% no-repeat;
        mask: url('<?php echo esc_url( $aicoach_img['flag-mask'] ); ?>') center / 100% 100% no-repeat;
    }

    .aicoach-competition-format {
        max-width: 221px;
        margin: 35px auto 0;
        font-weight: 800;
        font-size: 20px;
        line-height: 34.4px;
        color: var(--aicoach-yellow);
    }

    .aicoach-competition-format span {
        display: block;
    }

    /* Identity: no commentary box on this screen, so it starts under the banner. */
    .aicoach-identity {
        padding: 31px 0 0;
        text-align: center;
    }

    .aicoach-belief {
        margin: 0 0 8.5px;
        font-weight: 600;
        font-size: 16px;
        line-height: 1.3;
        color: #fff;
    }

    .aicoach-identity-subtitle {
        margin: 0 0 15px;
        font-weight: 600;
        font-size: 13.1px;
        line-height: 21.6px;
        color: #fff;
    }

    .aicoach-identity-title {
        max-width: 260px;
        margin: 0 auto 19.5px;
        font-family: 'Inter', 'Be Vietnam Pro', sans-serif;
        font-weight: 900;
        font-size: 28.5px;
        line-height: 41.8px;
        text-transform: capitalize;
        color: var(--aicoach-yellow);
    }

    .aicoach-identity-form {
        display: flex;
        flex-direction: column;
        gap: 18px;
        width: min(261px, 66%);
        margin: 0 auto;
    }

    /* The label sits inside the field's row, the input is the dashed box beside it. */
    .aicoach-identity-field {
        position: relative;
        box-sizing: border-box;
        display: grid;
        grid-template-columns: 76px minmax(0, 1fr);
        align-items: center;
        height: 44px;
        padding: 0 8px;
        border: 1px solid var(--aicoach-yellow);
        border-radius: 4px;
        background: var(--aicoach-field-bg);
        text-align: left;
    }

    .aicoach-identity-label {
        font-family: 'Inter', 'Be Vietnam Pro', sans-serif;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        color: #fff;
    }

    .aicoach-identity-input {
        box-sizing: border-box;
        width: 100%;
        min-width: 0;
        height: 28px;
        padding: 0 6px;
        border: 1px dashed #fff;
        border-radius: 0;
        background: transparent;
        color: #fff;
        font-family: 'Inter', 'Be Vietnam Pro', sans-serif;
        font-weight: 500;
        /* 16 px: a smaller field makes iOS zoom the page in when it is focused. */
        font-size: 16px;
    }

    .aicoach-identity-input:focus {
        outline: none;
        border-style: solid;
        border-color: var(--aicoach-yellow);
    }

    .aicoach-identity-input.is-invalid {
        border-color: #eb0000;
    }

    .aicoach-identity-error {
        position: absolute;
        left: 0;
        top: calc(100% + 2px);
        font-weight: 600;
        font-size: 12px;
        line-height: 1.2;
        color: #ff6b6b;
    }

    .aicoach-form-continue {
        display: block;
        box-sizing: border-box;
        width: 100%;
        height: 44px;
        margin: 36px auto 0;
        padding: 0 12px;
        border: 1px solid var(--aicoach-green);
        border-radius: 4px;
        background: var(--aicoach-green);
        color: #fff;
        font-family: 'Inter', 'Be Vietnam Pro', sans-serif;
        font-weight: 600;
        font-size: 19.6px;
        text-transform: uppercase;
        cursor: pointer;
    }

    .aicoach-form-continue:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    .aicoach-form-continue:focus-visible {
        outline: 3px solid var(--aicoach-yellow);
        outline-offset: 2px;
    }

    /* Channels: the reminder, the title, then the two-column list. */
    .aicoach-channels {
        padding-top: 15px;
        text-align: center;
    }

    .aicoach-volume-note {
        margin: 0 0 16px;
        font-weight: 600;
        font-size: 16px;
        line-height: 35px;
        color: #fff;
    }

    .aicoach-channels-hint {
        display: none;
        margin: 0 0 12px;
        font-weight: 600;
        font-size: 14px;
        color: #ff6b6b;
    }

    .aicoach-channels-hint.is-error {
        display: block;
    }

    .aicoach-channel-list {
        display: grid;
        grid-template-columns: 57% 1fr;
        row-gap: 22px;
        padding: 16px 9% 0;
        text-align: left;
    }

    /* Every channel is one cell; a field that opens under a ticked channel takes a full row after the list. */
    .aicoach-channel {
        display: contents;
    }

    .aicoach-channel-toggle {
        order: 1;
        display: flex;
        align-items: center;
        gap: 18px;
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
        box-sizing: border-box;
        width: 23px;
        height: 23px;
        border: 2px solid #fff;
        border-radius: 3px;
        background: transparent;
    }

    .aicoach-channel-toggle:has(.aicoach-channel-check:checked) .aicoach-channel-box {
        background: var(--aicoach-box-green);
    }

    .aicoach-channel-toggle:has(.aicoach-channel-check:focus-visible) .aicoach-channel-box {
        outline: 3px solid var(--aicoach-yellow);
        outline-offset: 2px;
    }

    .aicoach-channel-label {
        font-weight: 600;
        font-size: 20px;
        line-height: 30px;
        color: #fff;
    }

    .aicoach-channel-field {
        order: 2;
        grid-column: 1 / -1;
        text-align: left;
    }

    .aicoach-channel-field[hidden] {
        display: none;
    }

    .aicoach-channel-field-label {
        display: block;
        margin: 0 0 6px;
        font-weight: 600;
        font-size: 14px;
        color: #fff;
    }

    .aicoach-channel-input {
        width: 100%;
        box-sizing: border-box;
        padding: 8px 10px;
        border: 1px solid #fff;
        border-radius: 4px;
        background: var(--aicoach-field-bg);
        color: #fff;
        font-size: 16px;
        font-family: 'Inter', 'Be Vietnam Pro', sans-serif;
    }

    .aicoach-channel-input:focus {
        outline: none;
        border-color: var(--aicoach-yellow);
    }

    .aicoach-channel-input.is-invalid {
        border-color: #eb0000;
    }

    .aicoach-channel-error {
        display: block;
        min-height: 1.1em;
        margin-top: 4px;
        font-weight: 600;
        font-size: 12px;
        color: #ff6b6b;
    }

    .aicoach-channels .aicoach-form-continue {
        width: min(261px, 66%);
    }

    .aicoach-final {
        max-width: 440px;
        margin: 0 auto;
        padding-top: 32px;
        text-align: center;
    }

    .aicoach-final .aicoach-form-continue {
        width: min(261px, 66%);
        margin-top: 0;
    }

    .aicoach-final-error {
        min-height: 1.2em;
        margin: 0 0 16px;
        font-weight: 600;
        font-size: 14px;
        color: #ff6b6b;
    }

    /* PO-3109 — "Time is up?" screen (design: Keep Talking Now / Set an
    Appointment). White heading, white-outlined boxes, green when chosen. */
    .aicoach-timeup {
        padding: 8px 0 24px;
        text-align: center;
    }

    .aicoach-timeup-title {
        margin: 0 0 22px;
        font-weight: 700;
        font-size: clamp(1.9rem, 8.5vw, 2.4rem);
        line-height: 1.15;
        color: #fff;
    }

    .aicoach-timeup-choices {
        display: grid;
        gap: 14px;
        width: fit-content;
        margin: 0 auto;
        text-align: left;
    }

    .aicoach-timeup-choice {
        display: flex;
        align-items: center;
        gap: 14px;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
    }

    .aicoach-timeup-check {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .aicoach-timeup-box {
        flex-shrink: 0;
        width: 28px;
        height: 28px;
        border: 2px solid #fff;
        border-radius: 3px;
        background: transparent;
        box-sizing: border-box;
    }

    .aicoach-timeup-choice:has(.aicoach-timeup-check:checked) .aicoach-timeup-box {
        background: #148942;
    }

    .aicoach-timeup-choice:has(.aicoach-timeup-check:focus-visible) .aicoach-timeup-box {
        outline: 3px solid #fdd65b;
        outline-offset: 2px;
    }

    .aicoach-timeup-label {
        font-weight: 600;
        font-size: clamp(1.4rem, 6.3vw, 1.75rem);
        line-height: 1.2;
        color: #fff;
    }

    /* PO-3109 — "Set your appointment" (design 16 to 16c) and the final screen
    (17). The three options reuse the Time is up square choice above; only the
    label is smaller in the design. */
    .aicoach-appt,
    .aicoach-appt-done {
        padding: 8px 0 24px;
        text-align: center;
    }

    .aicoach-appt [hidden],
    .aicoach-appt-done [hidden] {
        display: none;
    }

    .aicoach-appt-ready {
        margin: 0 0 14px;
        font-weight: 700;
        font-size: clamp(1.9rem, 8.5vw, 2.4rem);
        line-height: 1.15;
        color: #fff;
    }

    .aicoach-appt-title {
        margin: 0 0 22px;
        font-weight: 600;
        font-size: clamp(1.5rem, 6.5vw, 1.9rem);
        line-height: 1.2;
        color: #fff;
    }

    .aicoach-appt-choices {
        margin-bottom: 30px;
    }

    .aicoach-appt-label {
        font-weight: 600;
        font-size: clamp(1.1rem, 4.6vw, 1.3rem);
        line-height: 1.2;
        color: #fff;
    }

    .aicoach-appt-other {
        display: grid;
        gap: 12px;
        max-width: 440px;
        margin: 0 auto 30px;
        text-align: left;
    }

    .aicoach-appt-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .aicoach-appt-field {
        display: grid;
        gap: 6px;
        min-width: 0;
    }

    .aicoach-appt-field-label {
        font-weight: 600;
        font-size: 0.9rem;
        color: #fff;
    }

    .aicoach-appt-input {
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
        padding: 10px 12px;
        border: 2px solid #fff;
        border-radius: 4px;
        background: #1b1c20;
        color: #fff;
        font-family: inherit;
        font-weight: 500;
        font-size: clamp(1.05rem, 4.6vw, 1.3rem);
        color-scheme: dark;
    }

    .aicoach-appt-input:focus-visible {
        outline: 3px solid #fdd65b;
        outline-offset: 2px;
    }

    .aicoach-appt-error {
        min-height: 1.2em;
        margin: 0 auto 12px;
        max-width: 440px;
        font-weight: 600;
        font-size: 0.95rem;
        white-space: pre-line;
        color: #ff6b6b;
    }

    .aicoach-appt-copy {
        display: block;
        width: 100%;
        max-width: 440px;
        box-sizing: border-box;
        margin: 0 auto;
        padding: 14px 12px;
        border: none;
        border-radius: 2px;
        background: #148942;
        color: #fff;
        font-family: inherit;
        font-weight: 600;
        font-size: clamp(1.5rem, 7vw, 1.9rem);
        line-height: 1.2;
        text-align: center;
        text-decoration: none;
        cursor: pointer;
    }

    .aicoach-appt-copy:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .aicoach-appt-copy:focus-visible {
        outline: 3px solid #fdd65b;
        outline-offset: 2px;
    }

    .aicoach-appt-status {
        min-height: 1.2em;
        margin: 12px auto 0;
        max-width: 440px;
        font-weight: 600;
        font-size: 1rem;
        color: #fff;
    }

    .aicoach-appt-link {
        max-width: 440px;
        margin: 8px auto 0;
    }

    .aicoach-appt-hint {
        max-width: 440px;
        margin: 14px auto 0;
        font-weight: 500;
        font-size: clamp(0.95rem, 4vw, 1.1rem);
        line-height: 1.4;
        color: #fff;
    }

    /* The design's final button is one line at a smaller size than the copy button. */
    .aicoach-appt-portal {
        font-size: clamp(1.1rem, 5.2vw, 1.5rem);
    }

    .aicoach-appt-done-message {
        max-width: 560px;
        margin: 0 auto 32px;
        font-weight: 600;
        font-size: clamp(1rem, 2.8vw, 1.25rem);
        line-height: 1.5;
        color: #fff;
    }

    .aicoach-appt-done .aicoach-appt-ready {
        margin-bottom: 40px;
    }

    .aicoach-stage {
        position: relative;
        min-height: 200px;
        /* PO-3343 — the real cause of Filip's "the scroll needs to go" report.
        Inactive .aicoach-panel children are position:absolute (so they don't
        add to this element's own auto height), but without this the page
        still scrolled to fit whichever INACTIVE panel was tallest (e.g. the
        comm-channels form, ~700px), regardless of which screen was actually
        showing — every screen inherited empty scrollable space sized to the
        single tallest one in the whole sequence. Clipping here means this
        element's height tracks only the active (in-flow) panel, same as it
        visually always looked like it did. */
        overflow: hidden;
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

    /* The time ask: a yellow box, the length in Cinzel, and what it includes. */
    .aicoach-tiers {
        display: flex;
        flex-direction: column;
        gap: 36px;
        padding: 11px 0 0;
    }

    .aicoach-tier-head {
        display: flex;
        align-items: center;
        gap: 17px;
        padding-left: 11.7%;
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
        box-sizing: border-box;
        width: 27.4px;
        height: 27.4px;
        border: 2px solid var(--aicoach-yellow);
        border-radius: 4px;
        background: transparent;
    }

    .aicoach-tier.is-selected .aicoach-tier-box,
    .aicoach-tier-head:has(.aicoach-tier-check:checked) .aicoach-tier-box {
        background: var(--aicoach-yellow);
    }

    .aicoach-tier-head:has(.aicoach-tier-check:focus-visible) .aicoach-tier-box {
        outline: 3px solid #fff;
        outline-offset: 2px;
    }

    .aicoach-tier-duration {
        font-family: 'Cinzel', serif;
        font-weight: 700;
        font-size: 22.7px;
        line-height: 1.2;
        color: #fff;
    }

    .aicoach-tier-list {
        margin: 14px 0 0 7.3%;
        padding: 0 0 0 24px;
        list-style: disc;
        color: #fff;
    }

    .aicoach-tier-list li {
        margin: 0;
        font-weight: 600;
        font-size: 16px;
        line-height: 21.6px;
    }

    /* BTS, Alix Earle and Magic Johnson: the name, the photo, a tick and a cross. */
    .aicoach-story {
        padding-bottom: 24px;
        text-align: center;
    }

    .aicoach-story--bts {
        padding-top: 45px;
    }

    .aicoach-story--alix {
        padding-top: 37px;
    }

    .aicoach-story--magic {
        padding-top: 0;
    }

    .aicoach-story-title {
        margin: 0;
        font-weight: 800;
        font-size: 26px;
        line-height: 1.2;
        text-transform: uppercase;
        color: var(--aicoach-yellow);
    }

    .aicoach-story-media {
        width: 64.2%;
        margin: 22px auto 0;
    }

    .aicoach-story--alix .aicoach-story-media {
        position: relative;
        left: 5px;
        width: 51.4%;
        margin-top: 18px;
    }

    .aicoach-story-photo {
        display: block;
        width: 100%;
        height: auto;
    }

    /* Magic Johnson: the photo has room at the top and the name sits on its right side. */
    .aicoach-magic {
        position: relative;
        width: 61.3%;
        margin: -13px auto 0;
    }

    .aicoach-magic-img {
        display: block;
        width: 100%;
        height: auto;
    }

    .aicoach-magic-name {
        position: absolute;
        left: 38.2%;
        top: 19.1%;
        margin: 0;
        font-weight: 800;
        font-size: 26.2px;
        line-height: normal;
        text-align: left;
        text-transform: uppercase;
        color: var(--aicoach-yellow);
    }

    .aicoach-magic-name span {
        display: block;
    }

    .aicoach-story-points {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin: 23px 0 0;
        padding: 0 0 0 17.5%;
        list-style: none;
        text-align: left;
    }

    .aicoach-story--alix .aicoach-story-points {
        margin-top: 41px;
        padding-left: 12.5%;
    }

    .aicoach-story--magic .aicoach-story-points {
        margin-top: 0;
        padding-left: 13.5%;
    }

    .aicoach-story--alix .aicoach-story-point > span,
    .aicoach-story--magic .aicoach-story-point > span {
        font-size: 14.73px;
    }

    .aicoach-story-point {
        display: flex;
        align-items: flex-start;
        gap: 26px;
        margin: 0;
    }

    .aicoach-story--magic .aicoach-story-point {
        gap: 19px;
    }

    .aicoach-story--magic .aicoach-story-points {
        gap: 21.4px;
    }

    /* The tick and the cross are drawn a little larger than the space they take, as in the design. */
    .aicoach-story-icon {
        flex-shrink: 0;
        height: auto;
    }

    .aicoach-story-icon--check {
        width: 49.44px;
        margin-right: -6.3px;
    }

    .aicoach-story-icon--cross {
        width: 48px;
        margin-right: -5px;
    }

    .aicoach-story-point > span {
        flex: 1;
        min-width: 0;
        position: relative;
        max-width: 228px;
        padding: 6px 0 0 22.5px;
        font-weight: 800;
        font-size: 15px;
        line-height: 1.003;
        text-transform: uppercase;
        color: #fff;
    }

    /* The bullet hangs to the left of the text, as in the design. */
    .aicoach-story-point--x > span {
        padding-top: 11px;
    }

    .aicoach-story-point > span::before {
        content: '\2022';
        position: absolute;
        left: 5px;
    }

    .aicoach-story-sub {
        display: block;
        padding-left: 0;
        text-transform: uppercase;
    }

    /* FR-13 — language selector, injected by js/aicoach-coach-flow.js into the
    shared header's right-hand group, next to the sound control (this page only, see the JS
    comment for why it's a separate element from the header's own dormant
    .header-lang-wrap, which belongs to an unrelated feature). */
    .aicoach-lang-wrap {
        position: relative;
        display: inline-flex;
    }

    .aicoach-lang-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        padding: 0;
        border: none;
        border-radius: 50%;
        background: transparent;
        cursor: pointer;
    }

    .aicoach-lang-btn:hover,
    .aicoach-lang-btn[aria-expanded="true"] {
        background: rgba(253, 214, 91, .12);
    }

    .aicoach-lang-dropdown {
        display: none;
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        min-width: 160px;
        padding: 6px;
        border: 1px solid #3a3b47;
        border-radius: 10px;
        background: #1b1c24;
        box-shadow: 0 12px 28px rgba(0, 0, 0, .4);
        z-index: 10050;
    }

    .aicoach-lang-wrap.is-open .aicoach-lang-dropdown {
        display: block;
    }

    .aicoach-lang-option {
        display: block;
        width: 100%;
        padding: 8px 12px;
        border: none;
        border-radius: 6px;
        background: transparent;
        color: #fff;
        font-size: .95rem;
        text-align: left;
        cursor: pointer;
    }

    .aicoach-lang-option:hover {
        background: rgba(253, 214, 91, .1);
    }

    .aicoach-lang-option.is-current {
        color: #fdd65b;
        font-weight: 700;
    }

    /* FR-19/PO-3330 — "Ask Sami" question box, injected by
    js/aicoach-coach-flow.js right after .aicoach-avatar-wrap. Same
    dropdown-style show/hide as .aicoach-lang-dropdown above, kept as its own
    block instead of reusing it since this holds a form, not a flat option
    list. */
    .aicoach-ask-wrap {
        position: absolute;
        right: 10px;
        bottom: 10px;
        z-index: 2;
        display: flex;
        justify-content: center;
        margin: 0;
    }

    /* An author "display: flex" rule beats the browser's own [hidden] rule
    (equal specificity, author origin wins) — without this, setting the
    wrap's hidden property (the non-English locale gate above) silently did
    nothing and the button stayed visible everywhere. */
    .aicoach-ask-wrap[hidden] {
        display: none;
    }

    .aicoach-ask-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 14px;
        border: none;
        border-radius: 999px;
        background: #fdd65b;
        color: #12131a;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
    }

    .aicoach-ask-btn:disabled {
        background: #3a3b47;
        color: #7a7b87;
        cursor: not-allowed;
    }

    /* PO-3346 (FR-19) — the proactive "she invites questions" beats (after
    the equity example, before identity capture, before comm-channels): a
    visual nudge toward the existing Ask Sami entry point, not new spoken
    copy (no approved script exists for this prompt). Same pulse mechanic
    as .aicoach-ask-mic.is-listening below. */
    .aicoach-ask-btn.is-inviting {
        animation: aicoach-ask-invite-pulse 1.4s ease-in-out infinite;
    }

    @keyframes aicoach-ask-invite-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(253, 214, 91, .5); }
        50% { box-shadow: 0 0 0 10px rgba(253, 214, 91, 0); }
    }

    @media (prefers-reduced-motion: reduce) {
        .aicoach-ask-btn.is-inviting {
            animation: none;
            box-shadow: 0 0 0 3px rgba(253, 214, 91, .5);
        }
    }

    .aicoach-ask-panel {
        display: none;
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        width: min(320px, calc(100vw - 32px));
        padding: 16px;
        border: 1px solid #3a3b47;
        border-radius: 12px;
        background: #1b1c24;
        box-shadow: 0 12px 28px rgba(0, 0, 0, .4);
        /* Below .aicoach-lang-dropdown's 10050. The Ask Sami button is disabled
        while the Time is up screen is showing (see openTimeUp() in
        js/aicoach-coach-flow.js), so this panel cannot be opened over it. */
        z-index: 10030;
        text-align: left;
    }

    .aicoach-ask-wrap.is-open .aicoach-ask-panel {
        display: block;
    }

    .aicoach-ask-close {
        position: absolute;
        top: 10px;
        right: 12px;
        border: none;
        background: transparent;
        color: #a9a9b3;
        font-size: 1.3rem;
        line-height: 1;
        cursor: pointer;
    }

    /* PO-3330 — past exchanges, read-only, shown above the live answer on a
    resumed visit (or once this visit's own questions start piling up).
    Capped height + scroll so a long history can't grow the panel past what
    .aicoach-ask-panel's own positioning (anchored to the button, not the
    viewport) can gracefully accommodate. */
    .aicoach-ask-history {
        max-height: 180px;
        overflow-y: auto;
        margin: 0 28px 12px 0;
        padding-right: 4px;
    }

    .aicoach-ask-history-item {
        padding: 8px 0;
        border-bottom: 1px solid #2a2b35;
    }

    .aicoach-ask-history-item:first-child {
        padding-top: 0;
    }

    .aicoach-ask-history-q {
        margin: 0 0 4px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #c7c7d1;
    }

    .aicoach-ask-history-a {
        margin: 0;
        font-size: 0.85rem;
        line-height: 1.4;
        color: #a9a9b3;
    }

    .aicoach-ask-answer {
        min-height: 1.4em;
        margin: 0 28px 12px 0;
        font-size: 0.95rem;
        line-height: 1.45;
        color: #fff;
    }

    /* PO-3346 — voice-only replacement for the old text form (review
    feedback, 2026-09-29: "ne treba input polje da bude, samo glasom" — no
    input field, voice only). */
    .aicoach-ask-voice {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .aicoach-ask-mic {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border: none;
        border-radius: 50%;
        background: #fdd65b;
        color: #12131a;
        cursor: pointer;
    }

    .aicoach-ask-mic.is-listening {
        background: #eb0000;
        color: #fff;
        animation: aicoach-mic-pulse 1.4s ease-in-out infinite;
    }

    .aicoach-ask-mic:disabled {
        background: #3a3b47;
        color: #7a7b87;
        cursor: not-allowed;
        animation: none;
    }

    @keyframes aicoach-mic-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(235, 0, 0, .4); }
        50% { box-shadow: 0 0 0 8px rgba(235, 0, 0, 0); }
    }

    .aicoach-ask-status {
        margin: 0;
        min-height: 1.2em;
        font-size: 0.9rem;
        color: #c7c7d1;
    }

    .aicoach-ask-error {
        min-height: 1.2em;
        margin: 8px 0 0;
        font-weight: 600;
        font-size: 0.85rem;
        color: #eb0000;
    }

    /* Ivan's request (2026-09-23): strip the chrome around this specific
    landing flow — Help, Login/Logout, PLAY, the full nav row and both
    footers are visual noise here. Scoped to this page's own body class and
    hidden via CSS rather than touching the shared header/footer
    template-parts (used on every other portal page) or deleting any markup,
    so turning any of it back on later is just deleting the matching line
    below — nothing to rebuild. The hamburger is back (PO-3062 design
    alignment): the updated design draws it. */
    body.page-template-page-home-aicoach-php .header-help-btn,
    body.page-template-page-home-aicoach-php .header-login-link,
    body.page-template-page-home-aicoach-php .header-logout-btn,
    body.page-template-page-home-aicoach-php .go-to-game-btn,
    body.page-template-page-home-aicoach-php .sticky-nav,
    body.page-template-page-home-aicoach-php .portal-footer,
    body.page-template-page-home-aicoach-php footer.bg-dark {
        display: none !important;
    }

    /* PO-3062 (design alignment) — the header as the design draws it, 70 px high: the hamburger
    on the left, the brand in the middle, the sound control and the language globe on the right.
    Scoped to this page like everything above; the shared header markup is the same one every
    portal page uses (the brand is the only part that differs, see template-parts/portal-header.php). */
    body.page-template-page-home-aicoach-php .sticky-header {
        padding: 0 !important;
    }

    body.page-template-page-home-aicoach-php .sticky-header .container {
        max-width: 480px !important;
        margin-top: 0 !important;
        padding: 0 22px !important;
    }

    body.page-template-page-home-aicoach-php .sticky-header .container > .d-flex {
        height: 70px;
    }

    body.page-template-page-home-aicoach-php .desktop-header-left-items {
        display: none !important;
    }

    body.page-template-page-home-aicoach-php .hamburger-menu {
        position: relative;
        width: 21px;
        height: 12px;
        padding: 0;
    }

    body.page-template-page-home-aicoach-php .hamburger-menu-icon {
        display: none;
    }

    /* The menu drawer covers the whole phone width and the header with it, so it carries its own close button. */
    body.page-template-page-home-aicoach-php .hamburger-drawer-close {
        position: absolute;
        top: 10px;
        right: 12px;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: transparent;
        color: #fff;
        font-size: 30px;
        line-height: 1;
        cursor: pointer;
    }

    body.page-template-page-home-aicoach-php .hamburger-drawer-close:focus-visible {
        outline: 3px solid #fdd65b;
    }

    body.page-template-page-home-aicoach-php .hamburger-menu::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 20.24px;
        height: 1.62px;
        border-radius: 3.2px;
        background: #fff;
        box-shadow: 0 4.86px 0 #fff, 0 9.72px 0 #fff;
    }

    .ihq-landing-brand {
        margin-left: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding-top: 5px;
        color: #fff;
        font-family: 'Inter', 'Be Vietnam Pro', sans-serif;
        font-weight: 600;
        font-size: 15px;
        line-height: 1.15;
        text-decoration: none;
    }

    .ihq-landing-brand:hover,
    .ihq-landing-brand:focus-visible {
        color: #fff;
        text-decoration: none;
    }

    .ihq-landing-brand-name {
        flex: 0 0 105px;
        text-align: left;
    }

    .ihq-landing-brand-domain {
        flex: 0 0 75px;
        text-align: right;
    }

    .ihq-landing-brand-mark {
        position: relative;
        flex: 0 0 52px;
        height: 60px;
    }

    .ihq-landing-brand-lines {
        position: absolute;
        left: 8px;
        top: 0;
        width: 39px;
        height: auto;
    }

    .ihq-landing-brand-ih {
        position: absolute;
        left: 0;
        top: 26px;
        font-weight: 800;
        font-size: 29px;
        line-height: 34px;
    }

    .ihq-landing-brand-tm {
        position: absolute;
        left: 37px;
        top: 50px;
        font-weight: 800;
        font-size: 5px;
        line-height: 1;
    }

    body.page-template-page-home-aicoach-php .desktop-header-right-items {
        display: flex !important;
        align-items: center;
        gap: 11px;
    }

    body.page-template-page-home-aicoach-php .header-volume-btn {
        width: 15px;
        height: 16px;
        padding: 0;
        border: 0;
        background: url('<?php echo esc_url( $aicoach_img['sound'] ); ?>') center / contain no-repeat;
    }

    body.page-template-page-home-aicoach-php .header-volume-icon {
        display: none;
    }

    body.page-template-page-home-aicoach-php .header-volume-wrap {
        width: 15px;
    }

    body.page-template-page-home-aicoach-php .aicoach-lang-btn {
        width: 18px;
        height: 18px;
        border-radius: 0;
        background: url('<?php echo esc_url( $aicoach_img['globe'] ); ?>') center / contain no-repeat;
    }

    body.page-template-page-home-aicoach-php .aicoach-lang-btn svg {
        display: none;
    }

    body.page-template-page-home-aicoach-php .aicoach-lang-dropdown {
        left: auto;
        right: 0;
    }

    /* PO-3062 (tablet and desktop) — from 600 px wide the header is the wide design's: a top row with
    the icons and the brand row under it, drawn at 1920 px and scaled with the window (one unit is
    1 px at 1920 wide, and never more). 600 px is a width the portal pages already use as a
    boundary; the phone layout above stays below it. */
    @media (min-width: 600px) {
        body.page-template-page-home-aicoach-php {
            --px: min(0.052083vw, 1px);
        }

        #portal-content.aicoach-page {
            --aicoach-header-gap: 0px;
            max-width: none;
        }

        .aicoach {
            max-width: none;
        }

        /* Header: a top row with the icons, the brand row under it, 218 px high at 1920. */
        body.page-template-page-home-aicoach-php .sticky-header .container {
            max-width: none !important;
            padding: 0 !important;
        }

        body.page-template-page-home-aicoach-php .sticky-header .container > .d-flex {
            position: relative;
            height: calc(218 * var(--px));
            padding: 0;
        }

        body.page-template-page-home-aicoach-php .sticky-header .container > .d-flex > div:first-child {
            position: absolute;
            left: calc(164 * var(--px));
            top: calc(11 * var(--px));
            flex: none;
        }

        body.page-template-page-home-aicoach-php .sticky-header .container > .d-flex > div:last-child {
            position: absolute;
            right: calc(164 * var(--px));
            top: calc(9 * var(--px));
            flex: none;
        }

        body.page-template-page-home-aicoach-php .logo-container {
            position: absolute;
            left: calc(158 * var(--px));
            top: calc(66 * var(--px));
            text-align: left;
        }

        body.page-template-page-home-aicoach-php .hamburger-menu {
            width: calc(41 * var(--px));
            height: calc(26 * var(--px));
        }

        body.page-template-page-home-aicoach-php .hamburger-menu::before {
            width: 100%;
            height: calc(3 * var(--px));
            box-shadow: 0 calc(11.5 * var(--px)) 0 #fff, 0 calc(23 * var(--px)) 0 #fff;
        }

        body.page-template-page-home-aicoach-php .desktop-header-right-items {
            gap: calc(30 * var(--px));
        }

        body.page-template-page-home-aicoach-php .header-volume-btn,
        body.page-template-page-home-aicoach-php .header-volume-wrap {
            width: calc(36 * var(--px));
            height: calc(32 * var(--px));
        }

        body.page-template-page-home-aicoach-php .aicoach-lang-btn {
            width: calc(40 * var(--px));
            height: calc(40 * var(--px));
        }

        /* Brand row: name, mark and domain sit at fixed places of the design; the mark is drawn
        here at 150 x 140 at design size (the phone version has its own pixel sizes). */
        .ihq-landing-brand {
            margin-left: 0;
            padding-top: 0;
            justify-content: flex-start;
            gap: 0;
            font-size: calc(55 * var(--px));
        }

        .ihq-landing-brand-name {
            flex: 0 0 calc(780 * var(--px));
            white-space: nowrap;
        }

        .ihq-landing-brand-mark {
            flex: 0 0 calc(150 * var(--px));
            height: calc(140 * var(--px));
            margin-right: calc(152 * var(--px));
        }

        .logo-container .ihq-landing-brand-lines {
            left: calc(32 * var(--px));
            width: calc(114 * var(--px));
            height: calc(84 * var(--px));
            max-height: none;
        }

        .ihq-landing-brand-ih {
            top: calc(56 * var(--px));
            font-size: calc(60 * var(--px));
            line-height: calc(70 * var(--px));
        }

        .ihq-landing-brand-tm {
            left: calc(102 * var(--px));
            top: calc(112 * var(--px));
            font-size: calc(10 * var(--px));
        }

        .ihq-landing-brand-domain {
            flex: 0 0 auto;
            text-align: left;
            white-space: nowrap;
        }
    }

    /* PO-3062 (tablet, portrait) — the iPad frames (mini 744, iPad 810, Pro 11 834 wide) are one
    design scaled to the screen, so a unit here is 1 px at 744 wide and grows with the window. The
    screens keep the phone's structure in a centred column under a full-width banner; the header is
    the shared wide one above, 101 px high at 744 (the 84.5 px of the header plus a gap to the
    banner). Landscape iPads are 1025 px or wider and take the desktop layout below, which the
    landscape frames are a scaled copy of. */
    @media (min-width: 600px) and (max-width: 1024px) {
        body.page-template-page-home-aicoach-php {
            --tp: 0.134409vw;
        }

        body.page-template-page-home-aicoach-php .sticky-header .container > .d-flex {
            height: calc(260 * var(--px));
        }

        .aicoach-commentary {
            position: relative;
            z-index: 1;
            width: calc(366 * var(--tp));
            min-height: calc(100 * var(--tp));
            margin-top: calc(-11 * var(--tp));
            padding: calc(8 * var(--tp)) calc(16 * var(--tp));
        }

        .aicoach-commentary-text {
            max-width: calc(228 * var(--tp));
            font-size: calc(17 * var(--tp));
            line-height: calc(28.4 * var(--tp));
        }

        .aicoach-ask-wrap {
            right: calc(24 * var(--tp));
            bottom: calc(24 * var(--tp));
        }

        /* The identity frame shows the commentary box, unlike the phone one. */
        .aicoach:has(.aicoach-panel[data-panel="identity"].is-active) .aicoach-commentary {
            display: flex;
        }

        /* The screens are drawn in a column as wide as the phone frame (398 at 744), centred. */
        .aicoach-panel {
            box-sizing: border-box;
            width: calc(398 * var(--tp));
            margin-inline: auto;
        }

        .aicoach-panel[data-panel^="competition-"] {
            width: auto;
            margin-inline: 0;
        }

        /* The time ask. */
        .aicoach-tiers {
            gap: 0;
            padding-top: calc(30 * var(--tp));
        }

        .aicoach-tier + .aicoach-tier {
            margin-top: calc(23 * var(--tp));
        }

        .aicoach-tier-head {
            gap: calc(15 * var(--tp));
            padding-left: calc(31 * var(--tp));
        }

        .aicoach-tier-box {
            width: calc(24 * var(--tp));
            height: calc(24 * var(--tp));
        }

        .aicoach-tier-duration {
            font-size: calc(19.5 * var(--tp));
        }

        .aicoach-tier-list {
            margin: calc(11 * var(--tp)) 0 0;
            padding-left: calc(37 * var(--tp));
        }

        .aicoach-tier-list li {
            font-size: calc(13.4 * var(--tp));
            line-height: calc(20 * var(--tp));
        }

        /* BTS, Alix, Magic. */
        .aicoach-story--bts {
            padding-top: calc(24 * var(--tp));
        }

        .aicoach-story--bts .aicoach-story-title {
            font-size: calc(29 * var(--tp));
        }

        .aicoach-story--bts .aicoach-story-media {
            width: 69.6%;
            margin-top: calc(21 * var(--tp));
        }

        .aicoach-story--bts .aicoach-story-points {
            padding-left: 13.6%;
        }

        /* The three competitions: the name in one line, the flag, the lines. */
        .aicoach-competition {
            padding-top: calc(13 * var(--tp));
        }

        .aicoach-competition-name {
            max-width: none;
            font-size: calc(28 * var(--tp));
        }

        .aicoach-flag {
            width: calc(105 * var(--tp));
            height: calc(104 * var(--tp));
            margin-top: calc(31 * var(--tp));
        }

        .aicoach-competition-format {
            max-width: calc(262 * var(--tp));
            margin-top: calc(32 * var(--tp));
            font-size: calc(24 * var(--tp));
            line-height: calc(41 * var(--tp));
        }

        /* We Believe: the coin and the chart in a row, the certificate under them. */
        .aicoach-believe {
            padding-top: calc(36 * var(--tp));
        }

        .aicoach-believe-title {
            font-size: calc(25.5 * var(--tp));
        }

        .aicoach-believe-icons {
            padding: 0 calc(37 * var(--tp));
            margin-top: calc(50 * var(--tp));
            row-gap: calc(60 * var(--tp));
        }

        .aicoach-believe-coin {
            width: calc(112.5 * var(--tp));
            height: calc(110.5 * var(--tp));
        }

        .aicoach-believe-chart {
            width: calc(99 * var(--tp));
            height: calc(81 * var(--tp));
        }

        .aicoach-believe-certificate {
            width: calc(127 * var(--tp));
            height: calc(93 * var(--tp));
        }

        /* Identity and channels. */
        .aicoach-identity {
            padding-top: calc(32 * var(--tp));
        }

        .aicoach-belief {
            margin-bottom: calc(20 * var(--tp));
            font-size: calc(15 * var(--tp));
        }

        .aicoach-identity-title {
            max-width: calc(260 * var(--tp));
            margin-bottom: calc(14 * var(--tp));
            font-size: calc(23 * var(--tp));
            line-height: calc(35.4 * var(--tp));
        }

        .aicoach-identity-subtitle {
            margin-bottom: calc(18 * var(--tp));
            font-size: calc(11.8 * var(--tp));
        }

        .aicoach-identity-form {
            gap: calc(15.3 * var(--tp));
            width: calc(227 * var(--tp));
        }

        .aicoach-identity-field {
            grid-template-columns: calc(72 * var(--tp)) minmax(0, 1fr);
            height: calc(38 * var(--tp));
        }

        .aicoach-form-continue,
        .aicoach-channels .aicoach-form-continue,
        .aicoach-final .aicoach-form-continue {
            width: calc(227 * var(--tp));
            height: calc(38 * var(--tp));
            margin-top: calc(38 * var(--tp));
            font-size: calc(17.5 * var(--tp));
        }

        .aicoach-channels {
            padding-top: calc(22 * var(--tp));
        }

        .aicoach-volume-note {
            margin-bottom: calc(8 * var(--tp));
            font-size: calc(19 * var(--tp));
            line-height: calc(36 * var(--tp));
        }

        .aicoach-channels .aicoach-identity-title {
            max-width: calc(300 * var(--tp));
            font-size: calc(31 * var(--tp));
            line-height: calc(48.6 * var(--tp));
        }

        .aicoach-channel-list {
            grid-template-columns: calc(212 * var(--tp)) 1fr;
            row-gap: calc(30 * var(--tp));
            padding: calc(24 * var(--tp)) 0 0 calc(8 * var(--tp));
        }

        .aicoach-channel-toggle {
            gap: calc(33 * var(--tp));
        }

        .aicoach-channel-box {
            width: calc(26.6 * var(--tp));
            height: calc(26.6 * var(--tp));
        }

        .aicoach-channel-label {
            font-size: calc(24 * var(--tp));
        }
    }

    /* PO-3062 (desktop) — the wide layout, from the design drawn at 1920 px. 1025 px is the desktop
    breakpoint the portal pages already use, so no new one is added. Every size below is written as
    N * var(--px): one unit is 1 px at 1920 wide and shrinks with the window (and never grows past
    1920), so the layout keeps the design's proportions on a smaller desktop. Text that would become
    too small to read has a floor with max(). The header shows the same items as on a phone
    (hamburger, brand, sound, globe), not the Help / Login / Register links of the frames (Ivan,
    2026-10-14). The wide banner is a still picture; the 3:2 clips are shown in its middle with soft
    edges until it is decided how they should fill it. */
    @media (min-width: 1025px) {
        /* The design's frames are about 1030 px high at 1920 wide and its tallest common screens
        (identity, channels) a little more, so on a window lower than that the unit also follows the
        height (1170 units, never below 0.45 px), and the header, the stage and the banner's Ask
        Sami button sit in a column 1920 units wide, centred. */
        body.page-template-page-home-aicoach-php {
            --px: min(0.052083vw, max(0.08547vh, 0.45px), 1px);
            --aicoach-column: min(100%, calc(1920 * var(--px)));
        }

        body.page-template-page-home-aicoach-php .sticky-header .container {
            width: var(--aicoach-column);
            margin-inline: auto;
        }

        .aicoach {
            padding-bottom: 0;
        }

        /* Banner: the whole width, 1920 x 387 at design size. */
        .aicoach-avatar-wrap {
            aspect-ratio: 1920 / 387;
        }

        .aicoach-avatar-video {
            inset: 0 auto 0 50%;
            width: auto;
            height: 100%;
            aspect-ratio: 3 / 2;
            transform: translateX(-50%);
            -webkit-mask-image: linear-gradient(to right, transparent, #000 14%, #000 86%, transparent);
            mask-image: linear-gradient(to right, transparent, #000 14%, #000 86%, transparent);
        }

        .aicoach-ask-wrap {
            right: calc((100% - var(--aicoach-column)) / 2 + 164 * var(--px));
            bottom: calc(24 * var(--px));
        }

        /* The commentary box overlaps the bottom edge of the banner. */
        .aicoach-commentary {
            display: flex;
            position: relative;
            z-index: 1;
            width: calc(628 * var(--px));
            min-height: calc(134 * var(--px));
            margin-top: calc(-11 * var(--px));
            padding: calc(12 * var(--px)) calc(24 * var(--px));
            border-width: max(1px, calc(2 * var(--px)));
        }

        .aicoach:has(.aicoach-panel[data-panel="identity"].is-active) .aicoach-commentary {
            display: flex;
        }

        .aicoach-commentary-text {
            max-width: none;
            font-family: 'Be Vietnam Pro', sans-serif;
            font-style: normal;
            font-weight: 600;
            font-size: max(14px, calc(28 * var(--px)));
            line-height: 1.43;
        }

        .aicoach-panel {
            box-sizing: border-box;
            padding-inline: calc(136 * var(--px));
        }

        /* We Believe: the title, then the three pictures in a row. */
        .aicoach-believe {
            padding-top: calc(41 * var(--px));
        }

        .aicoach-believe-title {
            min-height: calc(50 * var(--px));
            font-size: max(18px, calc(40 * var(--px)));
        }

        .aicoach-believe-icons {
            flex-wrap: nowrap;
            justify-content: center;
            gap: calc(117 * var(--px));
            padding: 0;
            margin-top: calc(37 * var(--px));
            row-gap: 0;
        }

        .aicoach-believe-coin {
            width: calc(125 * var(--px));
            height: calc(123 * var(--px));
        }

        .aicoach-believe-chart {
            width: calc(141 * var(--px));
            height: calc(115 * var(--px));
        }

        .aicoach-believe-certificate {
            width: calc(132 * var(--px));
            height: calc(96 * var(--px));
            margin: 0;
        }

        /* The time ask: three columns that start where the design puts them (x 172, 826 and 1378 of
        1920). The rows are staggered: the 10-minute one rises beside the commentary box. */
        .aicoach-panel[data-panel="home"] {
            padding-inline: 0;
        }

        /* Some pictures and rows of the design start level with the commentary box. The stage clips
        what sticks out of it (PO-3343), so it reaches up behind the box by this much and pads itself
        back down, which leaves the panels where they were. */
        .aicoach-stage {
            width: var(--aicoach-column);
            margin: calc(-100 * var(--px)) auto 0;
            padding-top: calc(100 * var(--px));
        }

        .aicoach-panel:not(.is-active) {
            top: calc(100 * var(--px));
        }

        .aicoach-tiers {
            display: grid;
            grid-template-columns: calc(826 * var(--px)) calc(552 * var(--px)) 1fr;
            align-items: start;
            gap: 0;
            padding: 0;
        }

        .aicoach-tier:nth-child(1) {
            margin-top: calc(72 * var(--px));
            padding-left: calc(172 * var(--px));
        }

        .aicoach-tier:nth-child(2) {
            margin-top: calc(64 * var(--px));
        }

        .aicoach-tier:nth-child(3) {
            margin-top: calc(-24 * var(--px));
        }

        .aicoach-tier-head {
            gap: calc(24 * var(--px));
            padding-left: 0;
        }

        .aicoach-tier-box {
            width: calc(38 * var(--px));
            height: calc(38 * var(--px));
            border-width: max(2px, calc(3 * var(--px)));
        }

        .aicoach-tier-duration {
            font-size: max(16px, calc(32 * var(--px)));
        }

        .aicoach-tier-list {
            margin-left: calc(30 * var(--px));
        }

        .aicoach-tier-list li {
            font-size: max(13px, calc(22 * var(--px)));
            line-height: calc(34 * var(--px));
        }

        /* BTS, Alix Earle, Magic Johnson: the picture on the left, the name in the middle, the ticks on the right. */
        .aicoach-story {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-areas: "media title points";
            align-items: start;
            padding: 0;
        }

        .aicoach-story--bts,
        .aicoach-story--alix,
        .aicoach-story--magic {
            padding-top: 0;
        }

        .aicoach-story-title {
            grid-area: title;
            margin-top: calc(19 * var(--px));
            font-size: max(20px, calc(44 * var(--px)));
        }

        .aicoach-story-media,
        .aicoach-story--alix .aicoach-story-media {
            grid-area: media;
            left: 0;
            width: calc(404 * var(--px));
            margin: calc(-59 * var(--px)) 0 0;
        }

        .aicoach-magic {
            grid-area: media;
            width: calc(300 * var(--px));
            margin: 0;
        }

        .aicoach-story-points,
        .aicoach-story--alix .aicoach-story-points,
        .aicoach-story--magic .aicoach-story-points {
            grid-area: points;
            gap: calc(16 * var(--px));
            margin: calc(-15 * var(--px)) 0 0;
            padding-left: calc(138 * var(--px));
        }

        .aicoach-story-point,
        .aicoach-story--magic .aicoach-story-point {
            gap: calc(24 * var(--px));
        }

        .aicoach-story-icon--check {
            width: calc(72 * var(--px));
            margin-right: calc(-9 * var(--px));
        }

        .aicoach-story-icon--cross {
            width: calc(70 * var(--px));
            margin-right: calc(-7 * var(--px));
        }

        .aicoach-story-point > span,
        .aicoach-story--alix .aicoach-story-point > span,
        .aicoach-story--magic .aicoach-story-point > span {
            max-width: calc(330 * var(--px));
            padding-left: calc(30 * var(--px));
            font-size: max(12px, calc(22 * var(--px)));
        }

        .aicoach-story-point > span::before {
            left: calc(8 * var(--px));
        }

        /* The three competitions: the flag on the left, the name in the middle, the lines on the right. */
        .aicoach-panel[data-panel^="competition-"] {
            padding-inline: 0;
        }

        .aicoach-competition {
            display: grid;
            grid-template-columns: calc(680 * var(--px)) calc(560 * var(--px)) calc(680 * var(--px));
            grid-template-areas: "flag name format";
            align-items: start;
            padding-top: 0;
        }

        .aicoach-flag {
            grid-area: flag;
            width: calc(190 * var(--px));
            height: calc(190 * var(--px));
            margin: calc(-19 * var(--px)) 0 0 calc(210 * var(--px));
        }

        .aicoach-competition-name {
            grid-area: name;
            max-width: none;
            margin-top: calc(19 * var(--px));
            font-size: max(20px, calc(42 * var(--px)));
        }

        .aicoach-competition-format {
            grid-area: format;
            max-width: none;
            margin: calc(-36 * var(--px)) 0 0;
            font-size: max(14px, calc(30 * var(--px)));
            line-height: calc(52 * var(--px));
        }

        /* Identity and channels: the title under the box, the fields in a row. */
        .aicoach-identity,
        .aicoach-channels {
            padding-top: calc(21 * var(--px));
        }

        .aicoach-identity-title {
            max-width: none;
            margin-bottom: calc(60 * var(--px));
            font-size: max(20px, calc(45.5 * var(--px)));
            line-height: 1.2;
        }

        .aicoach-belief,
        .aicoach-identity-subtitle,
        .aicoach-volume-note {
            display: none;
        }

        .aicoach-identity-form {
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
            align-items: flex-start;
            gap: calc(36 * var(--px));
            width: calc(807 * var(--px));
            min-width: 240px;
            margin-inline: auto;
        }

        .aicoach-identity-field {
            width: calc(245 * var(--px));
            min-width: 0;
            height: calc(60 * var(--px));
            min-height: 44px;
            grid-template-columns: max(76px, calc(100 * var(--px))) minmax(0, 1fr);
        }

        .aicoach-identity-label {
            font-size: max(11px, calc(15 * var(--px)));
        }

        .aicoach-form-continue,
        .aicoach-channels .aicoach-form-continue,
        .aicoach-final .aicoach-form-continue {
            width: calc(245 * var(--px));
            min-width: 160px;
            margin-top: calc(40 * var(--px));
        }

        .aicoach-channel-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: calc(30 * var(--px)) calc(40 * var(--px));
            padding: 0;
        }

        /* A channel is its box above its name, as in the design's row of tiles. */
        .aicoach-channel-toggle {
            flex-direction: column;
            gap: calc(14 * var(--px));
        }

        .aicoach-channel-box {
            width: max(18px, calc(40 * var(--px)));
            height: max(18px, calc(40 * var(--px)));
        }

        .aicoach-channel-label {
            font-size: max(14px, calc(36 * var(--px)));
        }

        .aicoach-channel-field {
            flex-basis: 100%;
            max-width: calc(560 * var(--px));
            min-width: 280px;
            margin: 0 auto;
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

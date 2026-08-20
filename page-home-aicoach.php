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
	'portrait' => $theme_uri . '/images/aicoach/coach-portrait.png',
	'bts'      => $theme_uri . '/images/aicoach/bts.jpg',
	'magic'    => $theme_uri . '/images/aicoach/magic-johnson.png',
	'check'    => $theme_uri . '/images/aicoach/icon-check.svg',
	'x'        => $theme_uri . '/images/aicoach/icon-x.svg',
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

                <div class="aicoach-portrait-wrap">
                    <img
                        class="aicoach-portrait"
                        src="<?php echo esc_url( $aicoach_img['portrait'] ); ?>"
                        alt="<?php esc_attr_e( 'AI Coach', 'influencer-hq' ); ?>"
                        width="452"
                        height="452"
                    >
                </div>

                <div class="aicoach-stage" id="aicoach-stage">

                    <div class="aicoach-panel is-active" data-panel="home" aria-hidden="false">
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

    .aicoach-portrait-wrap {
        width: min(280px, 62vw);
        aspect-ratio: 1;
        margin: 0 auto 48px;
        border-radius: 50%;
        overflow: hidden;
    }

    .aicoach-portrait {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center top;
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
(function () {
    var FADE_MS = 400;
    var stage = document.getElementById('aicoach-stage');
    if (!stage) return;

    var tiers = stage.querySelectorAll('.aicoach-tier');
    var isAnimating = false;

    function syncSelected() {
        tiers.forEach(function (tier) {
            var input = tier.querySelector('.aicoach-tier-check');
            if (input && input.checked) {
                tier.classList.add('is-selected');
            } else {
                tier.classList.remove('is-selected');
            }
        });
    }

    function getActivePanel() {
        return stage.querySelector('.aicoach-panel.is-active');
    }

    function showPanel(panelKey) {
        var next = stage.querySelector('.aicoach-panel[data-panel="' + panelKey + '"]');
        var current = getActivePanel();
        if (!next || !current || next === current || isAnimating) return;

        isAnimating = true;
        stage.style.minHeight = current.offsetHeight + 'px';

        current.classList.remove('is-active');
        current.setAttribute('aria-hidden', 'true');

        window.setTimeout(function () {
            next.classList.add('is-active');
            next.setAttribute('aria-hidden', 'false');
            stage.style.minHeight = next.offsetHeight + 'px';

            window.setTimeout(function () {
                stage.style.minHeight = '';
                isAnimating = false;
            }, FADE_MS);
        }, FADE_MS);
    }

    // Use click so a pre-checked tier (e.g. 2 minutes) still opens its story.
    tiers.forEach(function (tier) {
        var input = tier.querySelector('.aicoach-tier-check');
        if (!input) return;

        tier.addEventListener('click', function () {
            input.checked = true;
            syncSelected();
            var panelKey = input.getAttribute('data-panel');
            if (panelKey) {
                showPanel(panelKey);
            }
        });
    });

    syncSelected();
}());
</script>

<?php
get_template_part( 'template-parts/portal-scripts' );
get_footer();

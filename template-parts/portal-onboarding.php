<?php
/**
 * The portal onboarding layer over the Coach page (ENGR-7565). Rendered by
 * ihq_portal_onboarding_render_shell() (inc/portal-onboarding-shell.php); the behaviour is
 * js/aicoach/onboarding-main.js. Elements the scripts use carry a data-onb name; text the
 * string table can translate carries data-onb-string (text) or data-onb-label (aria-label).
 *
 * Expects $args: config (array), portrait_url (string), css_url (string).
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ihq_onb_config   = isset( $args['config'] ) && is_array( $args['config'] ) ? $args['config'] : array();
$ihq_onb_design   = get_template_directory_uri() . '/images/aicoach/design';
$ihq_onb_logout   = isset( $ihq_onb_config['logoutUrl'] ) ? $ihq_onb_config['logoutUrl'] : wp_logout_url();
$ihq_onb_portrait = isset( $args['portrait_url'] ) ? $args['portrait_url'] : '';
?>
<?php /* In the body on purpose: the layer is only rendered for the few page views that need it. A body stylesheet holds rendering of what follows until it has loaded, so the Coach page does not flash first. */ ?>
<link rel="stylesheet" href="<?php echo esc_url( isset( $args['css_url'] ) ? $args['css_url'] : '' ); ?>">
<?php /* The design's ExtraBold and Black weights, which the shared portal fonts link does not load (same link as page-home-aicoach.php). */ ?>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;600;800&family=Inter:ital,wght@1,300;0,800;0,900&display=swap" rel="stylesheet">
<div class="ihq-onb" id="ihq-onboarding" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Getting started with Sami', 'influencer-hq' ); ?>">
	<div class="ihq-onb__column">

		<?php /* OQ9 — hamburger, logo, sound, globe. No Help link, account control or Login button; Log out is in the hamburger menu. */ ?>
		<header class="ihq-onb__header">
			<div class="ihq-onb__header-side">
				<button type="button" class="ihq-onb__icon-button" data-onb="menu-button" aria-expanded="false" aria-controls="ihq-onb-menu" aria-label="<?php esc_attr_e( 'Open menu', 'influencer-hq' ); ?>">
					<svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<line x1="3" y1="6" x2="21" y2="6" stroke="#b7962f" stroke-width="2" stroke-linecap="round"/>
						<line x1="3" y1="12" x2="21" y2="12" stroke="#b7962f" stroke-width="2" stroke-linecap="round"/>
						<line x1="3" y1="18" x2="21" y2="18" stroke="#b7962f" stroke-width="2" stroke-linecap="round"/>
					</svg>
				</button>
				<nav class="ihq-onb__menu" id="ihq-onb-menu" data-onb="menu" hidden>
					<a class="ihq-onb__menu-link" href="<?php echo esc_url( $ihq_onb_logout ); ?>" data-onb-string="logOut"><?php esc_html_e( 'Log out', 'influencer-hq' ); ?></a>
				</nav>
			</div>
			<span class="ihq-onb__logo">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/images/logo-home-lander.jpg' ); ?>" alt="influencerHQ" width="769" height="98">
			</span>
			<div class="ihq-onb__header-side ihq-onb__header-side--end">
				<button type="button" class="ihq-onb__icon-button" data-onb="sound" aria-pressed="false" aria-label="<?php esc_attr_e( 'Mute Sami', 'influencer-hq' ); ?>">
					<img src="<?php echo esc_url( $ihq_onb_design . '/sound.svg' ); ?>" alt="" width="20" height="17">
				</button>
				<?php /* The globe's language list is filled by the language selector (ENGR-7572). */ ?>
				<div class="ihq-onb__language">
					<button type="button" class="ihq-onb__icon-button" data-onb="globe" aria-haspopup="true" aria-expanded="false" aria-controls="ihq-onb-languages" aria-label="<?php esc_attr_e( 'Select language', 'influencer-hq' ); ?>" data-onb-label="languageSelect">
						<img src="<?php echo esc_url( $ihq_onb_design . '/globe.webp' ); ?>" alt="" width="22" height="22">
					</button>
					<ul class="ihq-onb__languages" id="ihq-onb-languages" data-onb="languages" hidden></ul>
				</div>
			</div>
		</header>

		<div class="ihq-onb__banner">
			<img class="ihq-onb__portrait" src="<?php echo esc_url( $ihq_onb_portrait ); ?>" alt="<?php esc_attr_e( 'Sami, your AI Coach', 'influencer-hq' ); ?>" width="600" height="400" fetchpriority="high">
			<video class="ihq-onb__video" data-onb="video" playsinline preload="auto"></video>
		</div>

		<?php /* What Sami is saying, a sentence at a time; the second element holds the whole line once for a screen reader. */ ?>
		<div class="ihq-onb__commentary">
			<p class="ihq-onb__subtitle" data-onb="subtitle" aria-hidden="true"></p>
			<p class="ihq-onb__sr" data-onb="transcript" aria-live="polite"></p>
		</div>

		<div class="ihq-onb__content" data-onb="content"></div>

		<div class="ihq-onb__cta">
			<?php /* Install behaviour: ENGR-7575 binds every [data-ihq-save-home] button. */ ?>
			<button type="button" class="ihq-onb__button ihq-onb__button--save" data-ihq-save-home data-onb-string="saveToHomeScreen"><?php esc_html_e( 'SAVE IHQ TO HOME SCREEN', 'influencer-hq' ); ?></button>
		</div>

	</div>
	<script type="application/json" id="ihq-onboarding-config"><?php echo wp_json_encode( $ihq_onb_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES ); ?></script>
</div>

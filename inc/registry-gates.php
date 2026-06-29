<?php
/**
 * Portal registry gates (AC#1 / AC#2): guest-only triggers → 6-digit code registration.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Body class for guest portal pages (collapse CSS applies before gate JS runs).
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function ihq_registry_gates_body_class( array $classes ): array {
	if ( ihq_is_portal_page_template() && ! is_user_logged_in() ) {
		$classes[] = 'ihq-portal-guest';
	}

	return $classes;
}
add_filter( 'body_class', 'ihq_registry_gates_body_class' );

/**
 * Enqueue gate script on portal pages for guests (depends on visitor-intent).
 */
function ihq_enqueue_registry_gates_assets() {
	if ( ! ihq_is_portal_page_template() || is_user_logged_in() ) {
		return;
	}

	$script_path = get_template_directory() . '/js/ihq-registry-gates.js';
	$version     = file_exists( $script_path ) ? (string) filemtime( $script_path ) : '1';

	$deps = array( 'ihq-visitor-intent' );
	if ( ! ihq_portal_page_has_inline_conversation_modal() ) {
		$deps[] = 'ihq-conversation-modal';
	}

	wp_enqueue_script(
		'ihq-registry-gates',
		get_template_directory_uri() . '/js/ihq-registry-gates.js',
		$deps,
		$version,
		true
	);

	wp_localize_script(
		'ihq-registry-gates',
		'IHQ_REGISTRY_GATES',
		array(
			'isLoggedIn' => false,
			'message'    => __( 'Please check your preferred method of communication and enter the 6-digit code we sent you.', 'influencer-hq' ),
			'submitLabel' => __( 'Continue', 'influencer-hq' ),
		)
	);

	wp_register_style( 'ihq-registry-gates', false, array(), $version );
	wp_enqueue_style( 'ihq-registry-gates' );
	wp_add_inline_style(
		'ihq-registry-gates',
		'.ihq-registry-gate-backdrop{position:fixed;inset:0;z-index:10040;background:rgba(8,6,4,.5);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);opacity:0;pointer-events:none;transition:opacity .35s ease;}'
		. '.ihq-registry-gate-backdrop.is-visible{opacity:1;pointer-events:auto;}'
		. 'body.ihq-registry-gate-open{overflow:hidden;}'
		. '.ihq-registry-gate-notice{position:fixed;left:50%;top:50%;z-index:10050;max-width:min(520px,calc(100vw - 32px));padding:28px 48px 24px 24px;border-radius:12px;border:2px solid #b8972f;background:rgba(20,18,13,.98);color:#f5e6c8;font-family:"Be Vietnam Pro",sans-serif;font-size:15px;line-height:1.55;text-align:center;box-shadow:0 16px 48px rgba(0,0,0,.55);transform:translate(-50%,-50%) scale(.96);opacity:0;pointer-events:none;transition:transform .35s ease,opacity .35s ease;}'
		. '.ihq-registry-gate-notice.is-visible{transform:translate(-50%,-50%) scale(1);opacity:1;pointer-events:auto;}'
		. '.ihq-registry-gate-notice-text{margin:0;padding:0 8px;}'
		. '.ihq-registry-gate-code-form{margin-top:18px;display:flex;flex-direction:column;align-items:center;gap:12px;}'
		. '.ihq-registry-gate-code-input{width:min(220px,100%);padding:12px 14px;border-radius:8px;border:2px solid rgba(184,151,47,.65);background:rgba(0,0,0,.35);color:#fff;font-family:"Be Vietnam Pro",sans-serif;font-size:22px;font-weight:700;letter-spacing:.28em;text-align:center;}'
		. '.ihq-registry-gate-code-input:focus{outline:none;border-color:#d4b85a;box-shadow:0 0 0 3px rgba(184,151,47,.25);}'
		. '.ihq-registry-gate-code-submit{min-width:160px;padding:10px 20px;border:0;border-radius:8px;background:linear-gradient(180deg,#d4b85a,#b8972f);color:#1a1408;font-family:"Be Vietnam Pro",sans-serif;font-size:14px;font-weight:700;letter-spacing:.04em;cursor:pointer;}'
		. '.ihq-registry-gate-code-submit:hover:not(:disabled){filter:brightness(1.05);}'
		. '.ihq-registry-gate-code-submit:disabled{opacity:.65;cursor:not-allowed;}'
		. '.ihq-registry-gate-notice-error{margin:0;font-size:13px;color:#f5a3a3;}'
		. '.ihq-registry-gate-notice.is-error .ihq-registry-gate-code-input{border-color:#c45c5c;}'
		. '.ihq-registry-gate-notice-close{position:absolute;top:10px;right:12px;width:32px;height:32px;padding:0;border:0;border-radius:6px;background:transparent;color:#e6cfa0;font-size:18px;line-height:1;cursor:pointer;}'
		. '.ihq-registry-gate-notice-close:hover{color:#fff;background:rgba(255,255,255,.08);}'
		. 'body.ihq-portal-guest.page-template-page-portal-equity-php .equity-card .equity-card-body{display:none !important;}'
		. 'body.ihq-portal-guest.page-template-page-portal-equity-php .equity-card .equity-card-header{margin-bottom:0;}'
		. 'body.ihq-portal-guest.page-template-page-portal-challenges-php #world-tab .competition-dropdown .competition-dropdown-body{display:none !important;}'
		. 'body.ihq-portal-guest.page-template-page-portal-challenges-php #cpcCollapse1,'
		. 'body.ihq-portal-guest.page-template-page-portal-challenges-php #cpcCollapse2{display:none !important;}'
		. 'body.ihq-portal-guest.page-template-page-portal-challenges-php #cpcCollapse1.show,'
		. 'body.ihq-portal-guest.page-template-page-portal-challenges-php #cpcCollapse2.show,'
		. 'body.ihq-portal-guest.page-template-page-portal-challenges-php #cpcCollapse1.collapsing,'
		. 'body.ihq-portal-guest.page-template-page-portal-challenges-php #cpcCollapse2.collapsing{display:none !important;}'
		. 'body.ihq-portal-guest.page-template-page-portal-profile-php #socialMediaBody,'
		. 'body.ihq-portal-guest.page-template-page-portal-profile-php #celebLeaguesBody,'
		. 'body.ihq-portal-guest.page-template-page-portal-profile-php #intlLeagueBody,'
		. 'body.ihq-portal-guest.page-template-page-portal-profile-php #contactBody{display:none !important;}'
		. 'body.page-template-page-portal-challenges-php .competition-dropdown.ihq-gate-collapsed .competition-dropdown-body{display:none !important;}'
		. 'body.page-template-page-portal-challenges-php #cpcCollapse1.ihq-gate-force-hidden,'
		. 'body.page-template-page-portal-challenges-php #cpcCollapse2.ihq-gate-force-hidden{display:none !important;}'
		. 'body.page-template-page-portal-equity-php .equity-card.ihq-gate-collapsed .equity-card-body{display:none !important;}'
		. 'body.page-template-page-portal-equity-php .equity-card.ihq-gate-collapsed .equity-card-header{margin-bottom:0;}'
		. 'body.page-template-page-portal-challenges-php .competition-dropdown-header{cursor:pointer;}'
		. 'body.page-template-page-portal-equity-php .equity-card-header{cursor:pointer;}'
		. 'body.page-template-page-portal-challenges-php .cpc-accordion-header{cursor:pointer;}'
	);
}
add_action( 'wp_enqueue_scripts', 'ihq_enqueue_registry_gates_assets', 30 );

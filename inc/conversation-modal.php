<?php
/**
 * Shared conversation modal (markup + assets) — same UI as page-lander.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View data for template-parts/conversation-modal.php.
 *
 * @return array<string, mixed>
 */
function ihq_conversation_modal_view_data() {
	$ihq_modal_comm_placeholder = __( 'handle or URL', 'influencer-hq' );
	$ihq_modal_comm_methods_left  = array(
		array( 'key' => 'botim', 'label' => 'Botim' ),
		array( 'key' => 'email', 'label' => 'Email' ),
		array( 'key' => 'imo', 'label' => 'IMO' ),
		array( 'key' => 'kakaotalk', 'label' => 'KakaoTalk' ),
		array( 'key' => 'line', 'label' => 'Line' ),
		array( 'key' => 'qq', 'label' => 'QQ' ),
		array( 'key' => 'signal', 'label' => 'Signal' ),
	);
	$ihq_modal_comm_methods_right = array(
		array( 'key' => 'sms', 'label' => 'SMS' ),
		array( 'key' => 'telegram', 'label' => 'Telegram' ),
		array( 'key' => 'viber', 'label' => 'Viber' ),
		array( 'key' => 'wechat', 'label' => 'WeChat' ),
		array( 'key' => 'whatsapp', 'label' => 'WhatsApp' ),
		array( 'key' => 'zalo', 'label' => 'Zalo' ),
	);

	$ihq_turnstile_site_modal = '';
	if ( function_exists( 'ihq_turnstile_is_configured' ) && ihq_turnstile_is_configured() && defined( 'CF_TURNSTILE_SITE_KEY' ) ) {
		$ihq_turnstile_site_modal = CF_TURNSTILE_SITE_KEY;
	}

	return array(
		'ihq_modal_comm_placeholder'   => $ihq_modal_comm_placeholder,
		'ihq_modal_comm_methods_left'  => $ihq_modal_comm_methods_left,
		'ihq_modal_comm_methods_right' => $ihq_modal_comm_methods_right,
		'ihq_modal_comm_methods_all'   => array_merge( $ihq_modal_comm_methods_left, $ihq_modal_comm_methods_right ),
		'ihq_modal_social_placeholder'   => $ihq_modal_comm_placeholder,
		'ihq_modal_social_platforms'   => array(
			array( 'key' => 'kick', 'label' => 'Kick' ),
			array( 'key' => 'facebook', 'label' => 'Facebook' ),
			array( 'key' => 'reddit', 'label' => 'Reddit' ),
			array( 'key' => 'tiktok', 'label' => 'TikTok' ),
			array( 'key' => 'naver-blog', 'label' => 'Naver Blog' ),
			array( 'key' => 'rednote', 'label' => 'Rednote' ),
			array( 'key' => 'bilibili', 'label' => 'Bilibili' ),
			array( 'key' => 'x', 'label' => 'X' ),
			array( 'key' => 'kakao-business', 'label' => 'Kakao B' ),
			array( 'key' => 'twitch', 'label' => 'Twitch' ),
			array( 'key' => 'instagram', 'label' => 'Instagram' ),
			array( 'key' => 'telegram-channel', 'label' => 'Telegram' ),
			array( 'key' => 'ameba', 'label' => 'Ameba' ),
			array( 'key' => 'line', 'label' => 'LINE' ),
			array( 'key' => 'youtube', 'label' => 'YouTube' ),
		),
		'ihq_turnstile_site_modal'       => $ihq_turnstile_site_modal,
	);
}

/**
 * Templates that already embed #mainModal inline (skip footer duplicate).
 *
 * @return bool
 */
function ihq_portal_page_has_inline_conversation_modal() {
	if ( ! is_page() ) {
		return false;
	}

	$template = get_page_template_slug();
	return $template === 'page-portal-home-claude.php';
}

/**
 * Enqueue modal styles/scripts for portal guests (lander keeps its inline bundle).
 */
function ihq_enqueue_portal_conversation_modal_assets() {
	if ( ! ihq_is_portal_page_template() || is_user_logged_in() ) {
		return;
	}

	if ( is_page_template( 'page-lander.php' ) || ihq_portal_page_has_inline_conversation_modal() ) {
		return;
	}

	$css_path = get_template_directory() . '/css/homepage-style-claude.css';
	$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1';
	wp_enqueue_style(
		'ihq-conversation-modal',
		get_template_directory_uri() . '/css/homepage-style-claude.css',
		array(),
		$css_ver
	);

	wp_register_style( 'ihq-conversation-modal-portal', false, array( 'ihq-conversation-modal' ), $css_ver );
	wp_enqueue_style( 'ihq-conversation-modal-portal' );
	wp_add_inline_style(
		'ihq-conversation-modal-portal',
		':root{color-scheme:dark;--gl:#FDD65B;--bg3:#161612;--warm:#EAD9B0;}'
		. '.field-error{box-sizing:border-box;border:2px solid rgba(255,75,75,.95)!important;box-shadow:0 0 0 4px rgba(255,75,75,.16);animation:field-error-pulse .9s ease both;}'
		. '@keyframes field-error-pulse{0%,100%{transform:translateX(0)}15%{transform:translateX(-4px)}30%{transform:translateX(4px)}45%{transform:translateX(-2px)}60%{transform:translateX(2px)}75%{transform:translateX(-1px)}}'
		. '#mainModal.overlay{z-index:10060;}'
	);

	$view_data = ihq_conversation_modal_view_data();
	if ( $view_data['ihq_turnstile_site_modal'] !== '' ) {
		wp_enqueue_script(
			'cloudflare-turnstile',
			'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit',
			array(),
			null,
			true
		);
	}

	$script_path = get_template_directory() . '/js/ihq-conversation-modal.js';
	$script_ver  = file_exists( $script_path ) ? (string) filemtime( $script_path ) : '1';

	wp_enqueue_script(
		'ihq-conversation-modal',
		get_template_directory_uri() . '/js/ihq-conversation-modal.js',
		array( 'ihq-visitor-intent' ),
		$script_ver,
		true
	);

	$country_iso = function_exists( 'ihq_get_cloudflare_country_iso_alpha2' )
		? ihq_get_cloudflare_country_iso_alpha2()
		: 'US';

	wp_add_inline_script(
		'ihq-conversation-modal',
		'window.IHQ_CF_COUNTRY_SEED_ISO=' . wp_json_encode( $country_iso ) . ';'
		. 'if(typeof window.ihqResolveClientCountryIsoAlpha2!=="function"){'
		. 'window.ihqResolveClientCountryIsoAlpha2=function(){var seed=typeof window.IHQ_CF_COUNTRY_SEED_ISO==="string"?window.IHQ_CF_COUNTRY_SEED_ISO.trim():"";'
		. 'if(/^[A-Za-z]{2}$/.test(seed))return seed.toUpperCase();return "US";};}',
		'before'
	);

	wp_localize_script(
		'ihq-conversation-modal',
		'IHQ_CONVERSATION_MODAL',
		array(
			'gateMode' => true,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ihq_enqueue_portal_conversation_modal_assets', 28 );

/**
 * Print conversation modal markup in the footer on portal pages for guests.
 */
function ihq_render_portal_conversation_modal_footer() {
	if ( ! ihq_is_portal_page_template() || is_user_logged_in() ) {
		return;
	}

	if ( is_page_template( 'page-lander.php' ) || ihq_portal_page_has_inline_conversation_modal() ) {
		return;
	}

	$view_data = ihq_conversation_modal_view_data();
	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- template-local vars.
	extract( $view_data, EXTR_SKIP );
	include get_template_directory() . '/template-parts/conversation-modal.php';
}
add_action( 'wp_footer', 'ihq_render_portal_conversation_modal_footer', 20 );

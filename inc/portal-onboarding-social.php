<?php
/**
 * Social platforms and handles from onboarding screen 6, shared with the Profile page
 * (ENGR-7569, PO-3063 T7).
 *
 * The onboarding saves the platforms an Influencer ticks and the handle typed for each in its
 * own record (inc/portal-onboarding-progress.php). This file copies those handles into the
 * existing `_ihq_social_handles` user meta, the one the Profile page's "Social Media Platforms
 * Where You Post" section reads and writes (inc/api-ajax-calls.php, save_settings_field_ajax),
 * so Profile and the Post page (PO-3190) see them.
 *
 * The 19 onboarding platforms become the single platform list (evaluation OQ15): Profile lists
 * the 19, plus any platform already saved there that is not one of them, so nothing an
 * Influencer entered before disappears.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The Profile page's social handles meta. */
const IHQ_PORTAL_ONBOARDING_SOCIAL_HANDLES_META = '_ihq_social_handles';

/**
 * Which `_ihq_social_handles` keys the onboarding wrote, so unticking a platform later takes
 * back only what the onboarding put there, never a handle from registration or Profile.
 */
const IHQ_PORTAL_ONBOARDING_MIRRORED_META = '_ihq_portal_onboarding_mirrored_handles';

/**
 * The 19 platforms with the names shown for them, in screen order.
 *
 * @return array<string, string>
 */
function ihq_portal_onboarding_platform_labels() {
	return array(
		'instagram' => 'Instagram',
		'facebook'  => 'Facebook',
		'tiktok'    => 'TikTok',
		'youtube'   => 'YouTube',
		'bilibili'  => 'Bilibili',
		'douyin'    => 'Douyin',
		'kakaotalk' => 'KakaoTalk',
		'line'      => 'LINE',
		'linkedin'  => 'LinkedIn',
		'pinterest' => 'Pinterest',
		'qq'        => 'QQ',
		'reddit'    => 'Reddit',
		'rednote'   => 'RedNote',
		'snapchat'  => 'Snapchat',
		'telegram'  => 'Telegram',
		'wechat'    => 'WeChat',
		'weibo'     => 'Weibo',
		'whatsapp'  => 'WhatsApp',
		'x'         => 'X',
	);
}

/**
 * Names for platforms Profile offered before the 19, shown when one of them is saved.
 *
 * @return array<string, string>
 */
function ihq_profile_legacy_platform_labels() {
	return array(
		'email'  => 'Email',
		'kick'   => 'KICK',
		'twitch' => 'Twitch',
	);
}

/**
 * The rows of the Profile page's platform list: the 19, then every other platform that has a
 * saved handle, in the order it was saved.
 *
 * @param mixed $saved_handles The `_ihq_social_handles` meta.
 * @return array<int, array{key: string, label: string}>
 */
function ihq_profile_social_platform_rows( $saved_handles ) {
	$rows = array();
	foreach ( ihq_portal_onboarding_platform_labels() as $key => $label ) {
		$rows[] = array(
			'key'   => $key,
			'label' => $label,
		);
	}
	if ( ! is_array( $saved_handles ) ) {
		return $rows;
	}
	$legacy = ihq_profile_legacy_platform_labels();
	foreach ( $saved_handles as $key => $value ) {
		if ( ! is_string( $key ) || isset( ihq_portal_onboarding_platform_labels()[ $key ] ) ) {
			continue;
		}
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			continue;
		}
		$rows[] = array(
			'key'   => $key,
			'label' => isset( $legacy[ $key ] ) ? $legacy[ $key ] : ucwords( str_replace( array( '-', '_' ), ' ', $key ) ),
		);
	}
	return $rows;
}

/**
 * Copies the onboarding's handles into `_ihq_social_handles` after an onboarding save that
 * touched platforms or handles. A handle set in onboarding replaces the one saved for that
 * platform; a platform unticked in onboarding loses its handle there only if the onboarding
 * had put it there. Handles for other platforms are left alone.
 *
 * Hooked on `ihq_portal_onboarding_saved` (inc/portal-onboarding-progress.php).
 *
 * @param int   $user_id
 * @param array $partial The validated update.
 * @param array $state   The onboarding state after the update.
 * @return void
 */
function ihq_portal_onboarding_mirror_handles( $user_id, $partial, $state ) {
	if ( ! is_array( $partial ) || ( ! isset( $partial['handles'] ) && ! isset( $partial['platforms'] ) ) ) {
		return;
	}
	$handles = isset( $state['handles'] ) && is_array( $state['handles'] ) ? $state['handles'] : array();

	$saved = get_user_meta( $user_id, IHQ_PORTAL_ONBOARDING_SOCIAL_HANDLES_META, true );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$mirrored = get_user_meta( $user_id, IHQ_PORTAL_ONBOARDING_MIRRORED_META, true );
	if ( ! is_array( $mirrored ) ) {
		$mirrored = array();
	}

	foreach ( $mirrored as $key ) {
		if ( ! isset( $handles[ $key ] ) ) {
			unset( $saved[ $key ] );
		}
	}
	foreach ( $handles as $key => $handle ) {
		$saved[ $key ] = $handle;
	}

	update_user_meta( $user_id, IHQ_PORTAL_ONBOARDING_SOCIAL_HANDLES_META, $saved );
	update_user_meta( $user_id, IHQ_PORTAL_ONBOARDING_MIRRORED_META, array_keys( $handles ) );
}
add_action( 'ihq_portal_onboarding_saved', 'ihq_portal_onboarding_mirror_handles', 10, 3 );

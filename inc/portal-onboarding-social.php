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
 * the 19, plus any platform from before them that already has a saved handle (Profile's old
 * list and the registration modal's grid), so nothing an Influencer entered before disappears.
 * Profile saves accept exactly those keys (ihq_allowed_contact_keys() in inc/api-ajax-calls.php).
 *
 * Precedence between onboarding and Profile: Profile edits are never overwritten by onboarding.
 * Once the onboarding has written a handle, a later Profile edit or clear of it wins, and the
 * onboarding never rewrites or deletes that handle again.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The Profile page's social handles meta. */
const IHQ_PORTAL_ONBOARDING_SOCIAL_HANDLES_META = '_ihq_social_handles';

/**
 * The handle the onboarding last wrote to `_ihq_social_handles`, by platform. A saved handle
 * still equal to it is the onboarding's; one that differs was edited on Profile since, and the
 * onboarding leaves it alone.
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
 * Platforms offered before the 19 and not among them, with the names Profile shows for them
 * when one has a saved handle: Profile's old list, and the registration modal's grid
 * (page-portal-home-claude.php, page-lander.php), which writes its keys to
 * `_ihq_social_handles` at sign-up.
 *
 * @return array<string, string>
 */
function ihq_profile_legacy_platform_labels() {
	return array(
		'email'            => 'Email',
		'kick'             => 'KICK',
		'twitch'           => 'Twitch',
		'naver-blog'       => 'Naver Blog',
		'kakao-business'   => 'Kakao Business',
		'telegram-channel' => 'Telegram Channel',
		'ameba'            => 'Ameba',
	);
}

/**
 * The rows of the Profile page's platform list: the 19, then every platform from before them
 * that has a saved handle, in the order it was saved. A saved key that is neither is not shown,
 * since a Profile save would refuse it.
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
		if ( ! is_string( $key ) || ! isset( $legacy[ $key ] ) ) {
			continue;
		}
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			continue;
		}
		$rows[] = array(
			'key'   => $key,
			'label' => $legacy[ $key ],
		);
	}
	return $rows;
}

/**
 * The handles the onboarding last wrote, by platform. Unreadable meta, or entries that are not
 * a platform key with a handle, count as nothing written.
 *
 * @param int $user_id
 * @return array<string, string>
 */
function ihq_portal_onboarding_mirrored_handles( $user_id ) {
	$stored   = get_user_meta( $user_id, IHQ_PORTAL_ONBOARDING_MIRRORED_META, true );
	$mirrored = array();
	if ( ! is_array( $stored ) ) {
		return $mirrored;
	}
	foreach ( $stored as $key => $handle ) {
		if ( is_string( $key ) && is_string( $handle ) ) {
			$mirrored[ $key ] = $handle;
		}
	}
	return $mirrored;
}

/**
 * Whether the saved handle for a platform is still the one the onboarding wrote, i.e. it was
 * not edited or cleared on Profile since. Pure.
 *
 * @param array  $saved    `_ihq_social_handles`.
 * @param array  $mirrored What the onboarding last wrote, by platform.
 * @param string $key      A platform key in $mirrored.
 * @return bool
 */
function ihq_portal_onboarding_owns_handle( array $saved, array $mirrored, $key ) {
	return isset( $saved[ $key ] ) && $saved[ $key ] === $mirrored[ $key ];
}

/**
 * The `_ihq_social_handles` and mirrored maps after an onboarding save. Pure.
 *
 * - A platform unticked (or whose handle was removed) in onboarding loses its saved handle only
 *   if the onboarding wrote it and Profile has not changed it since.
 * - A handle that changed in this save is written, unless Profile changed the saved one since
 *   the onboarding last wrote it: Profile edits are never overwritten by onboarding. A handle
 *   for a platform the onboarding has not written yet replaces the saved one, so what was typed
 *   in onboarding shows on Profile.
 * - Every other saved handle, from registration or Profile, is left alone.
 *
 * A Profile-edited platform stays in the mirrored map, so ticking it again later still does not
 * overwrite it.
 *
 * @param array $saved    `_ihq_social_handles`.
 * @param array $mirrored What the onboarding last wrote, by platform.
 * @param array $handles  The onboarding's handles after the save, by platform.
 * @return array{saved: array, mirrored: array}
 */
function ihq_portal_onboarding_plan_mirror( array $saved, array $mirrored, array $handles ) {
	foreach ( $mirrored as $key => $written ) {
		if ( isset( $handles[ $key ] ) || ! ihq_portal_onboarding_owns_handle( $saved, $mirrored, $key ) ) {
			continue;
		}
		unset( $saved[ $key ], $mirrored[ $key ] );
	}
	foreach ( $handles as $key => $handle ) {
		if ( isset( $mirrored[ $key ] ) && ! ihq_portal_onboarding_owns_handle( $saved, $mirrored, $key ) ) {
			continue;
		}
		if ( isset( $mirrored[ $key ] ) && $mirrored[ $key ] === $handle ) {
			continue;
		}
		$saved[ $key ]    = $handle;
		$mirrored[ $key ] = $handle;
	}
	return array(
		'saved'    => $saved,
		'mirrored' => $mirrored,
	);
}

/**
 * Copies the onboarding's handles into `_ihq_social_handles` after an onboarding save that
 * touched platforms or handles, by the rules of ihq_portal_onboarding_plan_mirror(). Meta is
 * written only when it changes.
 *
 * Hooked on IHQ_PORTAL_ONBOARDING_SAVED_ACTION (inc/portal-onboarding-progress.php, which the
 * glob loader in functions.php includes before this file).
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
	$mirrored = ihq_portal_onboarding_mirrored_handles( $user_id );

	$plan = ihq_portal_onboarding_plan_mirror( $saved, $mirrored, $handles );
	if ( $plan['saved'] !== $saved ) {
		update_user_meta( $user_id, IHQ_PORTAL_ONBOARDING_SOCIAL_HANDLES_META, $plan['saved'] );
	}
	if ( $plan['mirrored'] !== $mirrored ) {
		update_user_meta( $user_id, IHQ_PORTAL_ONBOARDING_MIRRORED_META, $plan['mirrored'] );
	}
}
add_action( IHQ_PORTAL_ONBOARDING_SAVED_ACTION, 'ihq_portal_onboarding_mirror_handles', 10, 3 );

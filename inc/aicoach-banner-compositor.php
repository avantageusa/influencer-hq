<?php
/**
 * Switch for the AI Coach banner composed by Gary's adapter (ENGR-7848).
 *
 * Gary's `createSamiBanner` (js/vendor/sami-banner/) draws his candidate avatar's video onto a
 * canvas that sits over our banner scene. This file decides whether the page uses it and what it
 * is given. The WordPress option `ihq_aicoach_banner_compositor` is the switch, off by default,
 * so a deploy alone changes nothing on the page:
 *
 *     wp option update ihq_aicoach_banner_compositor 1
 *     wp option delete ihq_aicoach_banner_compositor     (back to off)
 *
 * Even with the option on, the banner is composed only while the avatar version visitors are
 * served has Gary's sami-framing video profile (v3, see IHQ_AICOACH_AVATAR_VERSION_PROFILES) and
 * the hosted files are there. The calibration is valid for that one avatar: composing the clips of
 * another version would leave the rectangle around her head the adapter exists to remove. This
 * function does not count clips. What it relies on is the normal way a version becomes the served
 * one: ihq_aicoach_avatar_activate() refuses a version that lacks a clip the set served at that
 * moment can play. It is not an absolute requirement (a version can be served with fewer clips
 * than another has, if the set it replaces had no more), and a version already served always
 * reports nothing missing against itself.
 *
 * A live session is opened with the same profile while this is on (inc/gary-proxy.php), so the
 * intro and Ask Sami's answers show the same avatar as the clips.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** WordPress option that turns the composed banner on. */
const IHQ_AICOACH_BANNER_COMPOSITOR_OPTION = 'ihq_aicoach_banner_compositor';

/** Option: draw Gary's "AI avatar" box on the banner. On unless explicitly off. */
const IHQ_AICOACH_BANNER_DISCLOSURE_OPTION = 'ihq_aicoach_banner_disclosure';

/** Option: copy the renderer's own bottom-right video corner onto the banner. On unless explicitly off. */
const IHQ_AICOACH_BANNER_VIDEO_CORNER_OPTION = 'ihq_aicoach_banner_video_corner';

/** Option values (lower-cased, trimmed) that count as "on" for the switch; anything else is off. */
const IHQ_AICOACH_BANNER_ON_VALUES = array( '1', 'true', 'yes', 'on' );

/** Option values that turn a default-on flag off; anything else keeps it on. */
const IHQ_AICOACH_BANNER_OFF_VALUES = array( '0', 'false', 'no', 'off' );

/** The video profile the hosted calibration was measured for. */
const IHQ_AICOACH_BANNER_VIDEO_PROFILE = 'sami-framing-20261018';

/** The avatar that profile selects; the adapter refuses a calibration for any other. */
const IHQ_AICOACH_BANNER_AVATAR_ID = 'cf6b6e7d-be88-4b9b-a31d-5f065b90cd9a';

/** The hosted files, relative to the theme directory. */
const IHQ_AICOACH_BANNER_ASSET_FILES = array(
	'module'      => 'js/vendor/sami-banner/sami-banner-compositor.js',
	'calibration' => 'js/vendor/sami-banner/calibration.json',
	'poster'      => 'js/vendor/sami-banner/poster.webp',
);

/**
 * An option value as a lower-cased string, or null when it is not a scalar.
 *
 * @param mixed $value
 * @return string|null
 */
function ihq_aicoach_banner_option_text( $value ) {
	if ( is_bool( $value ) ) {
		return $value ? '1' : '0';
	}
	if ( ! is_scalar( $value ) ) {
		return null;
	}
	return strtolower( trim( (string) $value ) );
}

/**
 * Whether the option is explicitly on. A missing option, an empty string, "0" and anything
 * unrecognised are off, so a typo in a WP-CLI command fails closed.
 *
 * @return bool
 */
function ihq_aicoach_banner_compositor_switch_on() {
	$text = ihq_aicoach_banner_option_text( get_option( IHQ_AICOACH_BANNER_COMPOSITOR_OPTION, false ) );
	return null !== $text && in_array( $text, IHQ_AICOACH_BANNER_ON_VALUES, true );
}

/**
 * A flag that is on unless its option says off.
 *
 * @param string $option Option name.
 * @return bool
 */
function ihq_aicoach_banner_flag_on_by_default( $option ) {
	$text = ihq_aicoach_banner_option_text( get_option( $option, true ) );
	return null === $text || ! in_array( $text, IHQ_AICOACH_BANNER_OFF_VALUES, true );
}

/**
 * Whether the three hosted files exist.
 *
 * @return bool
 */
function ihq_aicoach_banner_assets_present() {
	foreach ( IHQ_AICOACH_BANNER_ASSET_FILES as $relative_path ) {
		if ( ! file_exists( get_template_directory() . '/' . $relative_path ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Whether the page composes the banner: the switch is on, the served avatar version is the one
 * the calibration was measured for, and the hosted files are there.
 *
 * @return bool
 */
function ihq_aicoach_banner_compositor_enabled() {
	if ( ! ihq_aicoach_banner_compositor_switch_on() ) {
		return false;
	}
	if ( ! function_exists( 'ihq_aicoach_avatar_active_version' ) || ! function_exists( 'ihq_aicoach_avatar_version_video_profile' ) ) {
		return false;
	}
	if ( IHQ_AICOACH_BANNER_VIDEO_PROFILE !== ihq_aicoach_avatar_version_video_profile( ihq_aicoach_avatar_active_version() ) ) {
		return false;
	}
	return ihq_aicoach_banner_assets_present();
}

/**
 * The video profile a live session is opened with.
 *
 * @return string The profile, or '' when the banner is not composed.
 */
function ihq_aicoach_banner_compositor_video_profile() {
	return ihq_aicoach_banner_compositor_enabled() ? IHQ_AICOACH_BANNER_VIDEO_PROFILE : '';
}

/**
 * URL of a hosted file, versioned with its modification time so a copy cached for a year by a
 * browser or the edge is not reused after a file is replaced.
 *
 * @param string $relative_path Path relative to the theme directory.
 * @return string
 */
function ihq_aicoach_banner_asset_url( $relative_path ) {
	$url  = get_template_directory_uri() . '/' . $relative_path;
	$path = get_template_directory() . '/' . $relative_path;
	if ( ! file_exists( $path ) ) {
		return $url;
	}
	$version = filemtime( $path );
	return false === $version ? $url : $url . '?ver=' . $version;
}

/**
 * What the flow needs to compose the banner (window.AICOACH_SAMI.bannerCompositor).
 *
 * @return array|false False when the banner is not composed.
 */
function ihq_aicoach_banner_compositor_config() {
	if ( ! ihq_aicoach_banner_compositor_enabled() ) {
		return false;
	}
	return array(
		'moduleUrl'           => ihq_aicoach_banner_asset_url( IHQ_AICOACH_BANNER_ASSET_FILES['module'] ),
		'calibrationUrl'      => ihq_aicoach_banner_asset_url( IHQ_AICOACH_BANNER_ASSET_FILES['calibration'] ),
		'posterUrl'           => ihq_aicoach_banner_asset_url( IHQ_AICOACH_BANNER_ASSET_FILES['poster'] ),
		'avatarId'            => IHQ_AICOACH_BANNER_AVATAR_ID,
		'showDisclosure'      => ihq_aicoach_banner_flag_on_by_default( IHQ_AICOACH_BANNER_DISCLOSURE_OPTION ),
		'preserveVideoCorner' => ihq_aicoach_banner_flag_on_by_default( IHQ_AICOACH_BANNER_VIDEO_CORNER_OPTION ),
	);
}

/**
 * Why the switch is on but the banner is not composed.
 *
 * @param array $status ihq_aicoach_banner_compositor_status().
 * @return string The reason, or '' when there is nothing to report (the switch is off, or the banner is composed).
 */
function ihq_aicoach_banner_compositor_blocker( $status ) {
	if ( ! $status['switch_on'] || $status['enabled'] ) {
		return '';
	}
	if ( ! $status['profile_matches'] ) {
		$served = '' === $status['served_version'] ? 'the original set' : $status['served_version'];
		return sprintf(
			'The avatar version served now is %1$s, which does not have the %2$s video profile. Render the version that does (v3) and serve it with `wp aicoach activate-avatar v3`.',
			$served,
			IHQ_AICOACH_BANNER_VIDEO_PROFILE
		);
	}
	return 'The adapter files are missing from the theme (js/vendor/sami-banner/).';
}

/**
 * What the settings screen shows: the switch, what is served and whether the banner is composed.
 *
 * @return array
 */
function ihq_aicoach_banner_compositor_status() {
	$served_version = function_exists( 'ihq_aicoach_avatar_active_version' ) ? ihq_aicoach_avatar_active_version() : '';
	$served_profile = function_exists( 'ihq_aicoach_avatar_version_video_profile' ) ? ihq_aicoach_avatar_version_video_profile( $served_version ) : '';
	return array(
		'switch_on'             => ihq_aicoach_banner_compositor_switch_on(),
		'served_version'        => $served_version,
		'served_profile'        => $served_profile,
		'profile_matches'       => IHQ_AICOACH_BANNER_VIDEO_PROFILE === $served_profile,
		'assets_present'        => ihq_aicoach_banner_assets_present(),
		'enabled'               => ihq_aicoach_banner_compositor_enabled(),
		'show_disclosure'       => ihq_aicoach_banner_flag_on_by_default( IHQ_AICOACH_BANNER_DISCLOSURE_OPTION ),
		'preserve_video_corner' => ihq_aicoach_banner_flag_on_by_default( IHQ_AICOACH_BANNER_VIDEO_CORNER_OPTION ),
		'versions'              => function_exists( 'ihq_aicoach_avatar_status' ) ? ihq_aicoach_avatar_status() : array(),
	);
}

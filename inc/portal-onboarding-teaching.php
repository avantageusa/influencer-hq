<?php
/**
 * Hero images for the onboarding teaching screens (ENGR-7567), given to the browser through
 * the shell's `ihq_portal_onboarding_images` filter (inc/portal-onboarding-shell.php);
 * js/aicoach/onboarding-screens-teaching.js shows a screen's image above its title.
 *
 * Loaded by functions.php with every other inc/portal-onboarding-*.php file.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hero image file per screen, in images/aicoach/design/. From the design's image fills
 * (Figma 46893:117629, "1 The Game of Kings" and "2 Modern Era"), exported at twice the
 * 240.6 x 156.3 px they are drawn at. Screens 7, 8 and 9 have none on purpose: the design
 * reuses screen 2's picture on them, which looks like a placeholder, until Design gives them
 * their own.
 */
const IHQ_PORTAL_ONBOARDING_TEACHING_HEROES = array(
	'game_of_kings' => 'onboarding-game-of-kings.webp',
	'modern_era'    => 'onboarding-modern-era.webp',
);

/**
 * Adds the teaching screens' hero images to the onboarding config, each versioned by its
 * file time. A file missing from the theme is left out, so its screen shows no image rather
 * than a broken one.
 *
 * @param array $images [ stage => url ] from other screen groups.
 * @return array
 */
function ihq_portal_onboarding_teaching_images( $images ) {
	$images = is_array( $images ) ? $images : array();
	$dir    = get_template_directory() . '/images/aicoach/design/';
	$uri    = get_template_directory_uri() . '/images/aicoach/design/';
	foreach ( IHQ_PORTAL_ONBOARDING_TEACHING_HEROES as $stage => $file ) {
		$path = $dir . $file;
		if ( ! file_exists( $path ) ) {
			continue;
		}
		$images[ $stage ] = add_query_arg( 'v', (string) filemtime( $path ), $uri . $file );
	}
	return $images;
}
add_filter( 'ihq_portal_onboarding_images', 'ihq_portal_onboarding_teaching_images' );

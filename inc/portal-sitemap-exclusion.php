<?php
/**
 * Exclude portal-zone pages from WordPress core sitemaps (/wp-sitemap.xml).
 * Pairs with portal noindex meta + X-Robots-Tag (see template-parts/portal-header.php).
 *
 * @package Avantage_Baccarat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize a URL path to a site-relative slug (no leading slash).
 *
 * @param string $path Absolute or root-relative path.
 * @return string
 */
function ihq_portal_zone_normalize_site_path( $path ) {
	$path = strtolower( untrailingslashit( (string) $path ) );
	$site_path = (string) parse_url( home_url( '/' ), PHP_URL_PATH );
	$site_path = strtolower( untrailingslashit( $site_path ) );
	if ( $site_path !== '' && strpos( $path, $site_path ) === 0 ) {
		$path = substr( $path, strlen( $site_path ) );
	}
	return ltrim( $path, '/' );
}

/**
 * Whether a normalized site path is in the portal URL zone.
 *
 * @param string $path Site-relative path.
 * @return bool
 */
function ihq_portal_zone_path_is_portal_zone( $path ) {
	$path = ihq_portal_zone_normalize_site_path( $path );
	if ( $path === '' ) {
		return false;
	}
	if ( $path === 'portal-home' || strpos( $path, 'portal-home/' ) === 0 ) {
		return true;
	}
	if ( $path === 'portal' || strpos( $path, 'portal/' ) === 0 ) {
		return true;
	}
	return false;
}

/**
 * Whether a page belongs to the portal zone (template or permalink).
 *
 * @param WP_Post $post Page object.
 * @return bool
 */
function ihq_portal_zone_post_is_portal_page( $post ) {
	if ( ! $post instanceof WP_Post || $post->post_type !== 'page' ) {
		return false;
	}

	$template = get_page_template_slug( $post );
	if ( is_string( $template ) && strpos( $template, 'page-portal-' ) === 0 ) {
		return true;
	}

	$permalink_path = (string) parse_url( get_permalink( $post ), PHP_URL_PATH );
	return ihq_portal_zone_path_is_portal_zone( $permalink_path );
}

/**
 * Published portal page IDs to omit from wp-sitemap page lists.
 *
 * @return int[]
 */
function ihq_portal_zone_sitemap_excluded_page_ids() {
	static $cached = null;
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$cached = array();
	$page_ids = get_posts(
		array(
			'post_type'              => 'page',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	foreach ( $page_ids as $page_id ) {
		$post = get_post( (int) $page_id );
		if ( $post && ihq_portal_zone_post_is_portal_page( $post ) ) {
			$cached[] = (int) $page_id;
		}
	}

	return $cached;
}

/**
 * @param array<string, mixed> $args      Query arguments.
 * @param string               $post_type Post type.
 * @return array<string, mixed>
 */
function ihq_portal_zone_filter_wp_sitemaps_posts_query_args( $args, $post_type ) {
	if ( $post_type !== 'page' ) {
		return $args;
	}

	$exclude_ids = ihq_portal_zone_sitemap_excluded_page_ids();
	if ( $exclude_ids === array() ) {
		return $args;
	}

	$existing = isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array();
	$args['post__not_in'] = array_values( array_unique( array_merge( $existing, $exclude_ids ) ) );

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'ihq_portal_zone_filter_wp_sitemaps_posts_query_args', 10, 2 );

/**
 * Belt-and-suspenders: drop any portal page that still appears in the sitemap.
 *
 * @param array<string, string> $entry     Sitemap entry.
 * @param WP_Post               $post      Post object.
 * @param string|null           $post_type Post type.
 * @return array<string, string>
 */
function ihq_portal_zone_filter_wp_sitemaps_posts_entry( $entry, $post, $post_type ) {
	if ( $post_type !== 'page' || ! $post instanceof WP_Post ) {
		return $entry;
	}

	if ( ihq_portal_zone_post_is_portal_page( $post ) ) {
		return array();
	}

	return $entry;
}
add_filter( 'wp_sitemaps_posts_entry', 'ihq_portal_zone_filter_wp_sitemaps_posts_entry', 10, 3 );

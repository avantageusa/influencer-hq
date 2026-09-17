<?php
/**
 * Shared game-portal iframe embed with an availability fallback (PO-2901).
 *
 * The URL must already be built with ihq_build_hq_game_portal_external_url(),
 * which appends the influencerHqAuth flag and the per-session hqSsoCode.
 *
 * The frame uses data-src (not src) so portal-external-embed.js can bind load
 * and error handlers before navigation starts. Assigning src in PHP can race
 * a footer-enqueued script and miss the load event, which falsely trips the
 * 15s fallback.
 *
 * @package influencer-hq
 *
 * @param array $args {
 *     @type string $url        Absolute embed URL. Required; the part renders nothing without it.
 *     @type string $title      Accessible iframe title.
 *     @type string $fallback   Message shown when the embed does not load.
 *     @type string $wrap_class Wrapper class. Default 'portal-leaderboards-iframe-wrap'.
 *     @type string $wrap_id    Wrapper id, used as a scroll anchor by the portal menu.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$embed_url = isset( $args['url'] ) ? (string) $args['url'] : '';
if ( $embed_url === '' ) {
	return;
}

$embed_title = isset( $args['title'] ) && $args['title'] !== ''
	? (string) $args['title']
	: __( 'Influencer HQ', 'influencer-hq' );

$embed_fallback = isset( $args['fallback'] ) && $args['fallback'] !== ''
	? (string) $args['fallback']
	: __( 'This content is temporarily unavailable. Please try again later.', 'influencer-hq' );

$embed_wrap_class = isset( $args['wrap_class'] ) && $args['wrap_class'] !== ''
	? (string) $args['wrap_class']
	: 'portal-leaderboards-iframe-wrap';

$embed_wrap_id = isset( $args['wrap_id'] )
	? sanitize_html_class( (string) $args['wrap_id'] )
	: '';

$embed_script_path = get_template_directory() . '/js/portal-external-embed.js';
wp_enqueue_script(
	'ihq-portal-external-embed',
	get_template_directory_uri() . '/js/portal-external-embed.js',
	array(),
	file_exists( $embed_script_path ) ? (string) filemtime( $embed_script_path ) : _S_VERSION,
	true
);
?>
<div
	class="<?php echo esc_attr( $embed_wrap_class ); ?>"
	<?php if ( $embed_wrap_id !== '' ) : ?>
	id="<?php echo esc_attr( $embed_wrap_id ); ?>"
	<?php endif; ?>
	data-ihq-external-embed
>
	<iframe
		title="<?php echo esc_attr( $embed_title ); ?>"
		data-src="<?php echo esc_url( $embed_url ); ?>"
		loading="lazy"
		referrerpolicy="strict-origin-when-cross-origin"
		allowfullscreen
		data-ihq-embed-frame
	></iframe>
	<p class="portal-embed-fallback" data-ihq-embed-fallback hidden><?php echo esc_html( $embed_fallback ); ?></p>
</div>

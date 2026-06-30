<?php
/**
 * Floating Executive Concierge button (all pages).
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$concierge_img = trailingslashit( get_template_directory_uri() ) . 'images/concierge.png';
$aria_label    = __( 'Talk to Executive Concierge', 'influencer-hq' );
?>
<button
	type="button"
	id="ihq-concierge-fab"
	class="ihq-concierge-fab"
	data-ihq-concierge-trigger
	data-ihq-concierge-agent="auto"
	aria-label="<?php echo esc_attr( $aria_label ); ?>"
	aria-pressed="false"
>
	<img
		class="ihq-concierge-fab-img"
		src="<?php echo esc_url( $concierge_img ); ?>"
		alt=""
		width="56"
		height="56"
		decoding="async"
		loading="lazy"
	>
	<span class="ihq-concierge-fab-ring" aria-hidden="true"></span>
</button>

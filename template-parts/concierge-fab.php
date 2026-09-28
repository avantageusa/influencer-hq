<?php
/**
 * Floating Executive Concierge button (all pages).
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$concierge_img = trailingslashit( get_template_directory_uri() ) . 'images/concierge-coach.png';
$aria_label    = __( 'Talk to Executive Concierge', 'influencer-hq' );
?>
<style>
.ihq-concierge-fab {
	position: fixed;
	right: 20px;
	bottom: 20px;
	z-index: 10030;
	width: 160px;
	height: 160px;
	padding: 0;
	border: 2px solid #b8972f;
	border-radius: 50%;
	background: linear-gradient(145deg, #1f1b14, #0d0b08);
	box-shadow: 0 8px 28px rgba(0, 0, 0, 0.55);
	cursor: grab;
	overflow: visible;
	touch-action: none;
	transition: box-shadow 0.2s ease, border-color 0.2s ease;
}
.ihq-concierge-fab:hover {
	box-shadow: 0 10px 32px rgba(0, 0, 0, 0.65);
	border-color: #d4b85a;
}
.ihq-concierge-fab.is-moved {
	position: fixed !important;
	width: 160px !important;
	height: 160px !important;
}
.ihq-concierge-fab:focus {
	outline: none;
	box-shadow: 0 0 0 3px rgba(184, 151, 47, 0.45), 0 8px 28px rgba(0, 0, 0, 0.55);
}
.ihq-concierge-fab-img {
	display: block;
	width: 100%;
	height: 100%;
	object-fit: cover;
	object-position: center 18%;
	border-radius: 50%;
}
.ihq-concierge-fab-ring {
	position: absolute;
	inset: -4px;
	border-radius: 50%;
	border: 2px solid transparent;
	pointer-events: none;
}
.ihq-concierge-fab.is-connecting .ihq-concierge-fab-ring {
	border-color: rgba(212, 184, 90, 0.55);
	animation: ihq-concierge-pulse 1.2s ease-in-out infinite;
}
.ihq-concierge-fab.is-active .ihq-concierge-fab-ring {
	border-color: #6fcf97;
	box-shadow: 0 0 0 4px rgba(111, 207, 151, 0.25);
}
.ihq-concierge-fab.is-error {
	border-color: #c45c5c;
}
@keyframes ihq-concierge-pulse {
	0%, 100% { transform: scale(1); opacity: 1; }
	50% { transform: scale(1.08); opacity: 0.65; }
}
</style>
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
		width="160"
		height="160"
		draggable="false"
		decoding="async"
		loading="lazy"
	>
	<span class="ihq-concierge-fab-ring" aria-hidden="true"></span>
</button>

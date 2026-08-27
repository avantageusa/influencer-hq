<?php
/**
 * Shared “Why Competition = Equity” accordion — reused on Competition tabs.
 *
 * @package influencer-hq
 *
 * @param array $args {
 *     @type string $instance_id Unique id prefix (required for Bootstrap targets).
 *     @type bool   $expanded    Open by default. Default false.
 *     @type string $parent_id   If set, render as an item inside that accordion id.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$instance_id = isset( $args['instance_id'] ) ? preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $args['instance_id'] ) : '';
if ( $instance_id === '' ) {
	$instance_id = 'comp-equity';
}

$expanded  = ! empty( $args['expanded'] );
$parent_id = isset( $args['parent_id'] ) ? preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $args['parent_id'] ) : '';

$heading_id  = 'heading-' . $instance_id . '-equity';
$collapse_id = 'collapse-' . $instance_id . '-equity';
$wrap_id     = $instance_id . '-equity-accordion';

$btn_class      = $expanded ? 'accordion-button' : 'accordion-button collapsed';
$collapse_class = $expanded ? 'accordion-collapse collapse show' : 'accordion-collapse collapse';
$aria_expanded  = $expanded ? 'true' : 'false';

$render_wrapper = ( $parent_id === '' );
?>
<?php if ( $render_wrapper ) : ?>
<div class="accordion custom-accordion competition-why-equity" id="<?php echo esc_attr( $wrap_id ); ?>">
<?php endif; ?>
	<div class="accordion-item mb-3">
		<h2 class="accordion-header" id="<?php echo esc_attr( $heading_id ); ?>">
			<button
				class="<?php echo esc_attr( $btn_class ); ?>"
				type="button"
				data-bs-toggle="collapse"
				data-bs-target="#<?php echo esc_attr( $collapse_id ); ?>"
				aria-expanded="<?php echo esc_attr( $aria_expanded ); ?>"
				aria-controls="<?php echo esc_attr( $collapse_id ); ?>"
			>
				<span class="question-text"><?php esc_html_e( 'Why Competition = Equity', 'influencer-hq' ); ?></span>
			</button>
		</h2>
		<div
			id="<?php echo esc_attr( $collapse_id ); ?>"
			class="<?php echo esc_attr( $collapse_class ); ?>"
			aria-labelledby="<?php echo esc_attr( $heading_id ); ?>"
			<?php if ( $parent_id !== '' ) : ?>
			data-bs-parent="#<?php echo esc_attr( $parent_id ); ?>"
			<?php elseif ( $render_wrapper ) : ?>
			data-bs-parent="#<?php echo esc_attr( $wrap_id ); ?>"
			<?php endif; ?>
		>
			<div class="accordion-body competition-equity-copy">
				<p><?php esc_html_e( 'We believe competition creates fun.', 'influencer-hq' ); ?></p>
				<p><?php esc_html_e( 'Fun creates the desire to keep playing.', 'influencer-hq' ); ?></p>
				<p><?php esc_html_e( 'And every moment of play creates equity.', 'influencer-hq' ); ?></p>
				<p class="competition-equity-gap"><?php esc_html_e( 'If you can lead — you can own.', 'influencer-hq' ); ?></p>
				<p><?php esc_html_e( 'If you can build momentum — you deserve to share in what you\'ve built.', 'influencer-hq' ); ?></p>
				<p class="competition-equity-gap"><?php esc_html_e( 'That\'s why Competition = Equity.', 'influencer-hq' ); ?></p>
				<p><?php esc_html_e( 'And that\'s why we built this platform for you.', 'influencer-hq' ); ?></p>
			</div>
		</div>
	</div>
<?php if ( $render_wrapper ) : ?>
</div>
<?php endif; ?>

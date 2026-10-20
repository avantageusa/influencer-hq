<?php
/**
 * Settings, AI Coach banner (ENGR-7848): shows what the composed banner depends on and switches
 * it, so it does not need WP-CLI. The options and what they mean are in
 * inc/aicoach-banner-compositor.php; WordPress's own options.php saves the form (capability and
 * nonce are its checks), so this file only draws the screen and cleans the values.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Settings group and screen slug. */
const IHQ_AICOACH_BANNER_ADMIN_SLUG = 'ihq-aicoach-banner';

/** The capability that may see and change the settings. */
const IHQ_AICOACH_BANNER_ADMIN_CAPABILITY = 'manage_options';

/**
 * A checkbox as a stored flag: "1" when ticked, "0" otherwise (an unticked box is not posted at all).
 *
 * @param mixed $value What options.php hands over: the posted value, or null.
 * @return string
 */
function ihq_aicoach_banner_admin_sanitize_flag( $value ) {
	return '1' === $value ? '1' : '0';
}

/**
 * @return void
 */
function ihq_aicoach_banner_admin_register_settings() {
	foreach ( array( IHQ_AICOACH_BANNER_COMPOSITOR_OPTION, IHQ_AICOACH_BANNER_DISCLOSURE_OPTION, IHQ_AICOACH_BANNER_VIDEO_CORNER_OPTION ) as $option ) {
		register_setting(
			IHQ_AICOACH_BANNER_ADMIN_SLUG,
			$option,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'ihq_aicoach_banner_admin_sanitize_flag',
			)
		);
	}
}
add_action( 'admin_init', 'ihq_aicoach_banner_admin_register_settings' );

/**
 * @return void
 */
function ihq_aicoach_banner_admin_register_menu() {
	add_options_page(
		'AI Coach banner',
		'AI Coach',
		IHQ_AICOACH_BANNER_ADMIN_CAPABILITY,
		IHQ_AICOACH_BANNER_ADMIN_SLUG,
		'ihq_aicoach_banner_admin_render'
	);
}
add_action( 'admin_menu', 'ihq_aicoach_banner_admin_register_menu' );

/**
 * One row of the clips table.
 *
 * @param array $version An entry of ihq_aicoach_avatar_status().
 * @return string
 */
function ihq_aicoach_banner_admin_version_row( $version ) {
	$label = '' === $version['version'] ? 'original set' : $version['version'];
	return sprintf(
		'<tr><td>%1$s</td><td>%2$d</td><td>%3$d</td><td>%4$s</td></tr>',
		esc_html( $label ),
		$version['clips'],
		$version['missing'],
		$version['active'] ? 'served now' : ''
	);
}

/**
 * The state of the page, as list items.
 *
 * @param array $status ihq_aicoach_banner_compositor_status().
 * @return string
 */
function ihq_aicoach_banner_admin_state_list( $status ) {
	$served = '' === $status['served_version'] ? 'the original set' : $status['served_version'];
	$items  = array(
		'Switch: ' . ( $status['switch_on'] ? 'on' : 'off' ),
		'Avatar version served: ' . $served,
		'Its video profile: ' . ( '' === $status['served_profile'] ? 'none' : $status['served_profile'] ),
		'Adapter files in the theme: ' . ( $status['assets_present'] ? 'present' : 'missing' ),
		'Banner composed on the page now: ' . ( $status['enabled'] ? 'yes' : 'no' ),
	);
	$html   = '';
	foreach ( $items as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	return $html;
}

/**
 * @return void
 */
function ihq_aicoach_banner_admin_render() {
	if ( ! current_user_can( IHQ_AICOACH_BANNER_ADMIN_CAPABILITY ) ) {
		return;
	}
	$status  = ihq_aicoach_banner_compositor_status();
	$blocker = ihq_aicoach_banner_compositor_blocker( $status );
	?>
	<div class="wrap">
		<h1>AI Coach banner</h1>
		<p>Composes the banner with Gary's adapter: his avatar video over our banner scene. With the switch off the page is exactly as it was.</p>

		<ul style="list-style: disc; margin-left: 1.5em;"><?php echo ihq_aicoach_banner_admin_state_list( $status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every item is escaped in ihq_aicoach_banner_admin_state_list(). ?></ul>

		<?php if ( '' !== $blocker ) : ?>
			<div class="notice notice-warning inline"><p><?php echo esc_html( $blocker ); ?></p></div>
		<?php endif; ?>

		<h2>Clips by avatar version</h2>
		<table class="widefat striped" style="max-width: 36em;">
			<thead><tr><th>Version</th><th>Clips</th><th>Missing</th><th></th></tr></thead>
			<tbody>
				<?php foreach ( $status['versions'] as $version ) : ?>
					<?php echo ihq_aicoach_banner_admin_version_row( $version ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in ihq_aicoach_banner_admin_version_row(). ?>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description">A version can be served only when no clip that is served now is missing from it.</p>

		<form method="post" action="options.php">
			<?php settings_fields( IHQ_AICOACH_BANNER_ADMIN_SLUG ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Compose the banner</th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( IHQ_AICOACH_BANNER_COMPOSITOR_OPTION ); ?>" value="1" <?php checked( $status['switch_on'] ); ?>> On</label></td>
				</tr>
				<tr>
					<th scope="row">"AI avatar" box</th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( IHQ_AICOACH_BANNER_DISCLOSURE_OPTION ); ?>" value="1" <?php checked( $status['show_disclosure'] ); ?>> Draw Gary's "AI avatar" box on the banner</label></td>
				</tr>
				<tr>
					<th scope="row">Video corner</th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( IHQ_AICOACH_BANNER_VIDEO_CORNER_OPTION ); ?>" value="1" <?php checked( $status['preserve_video_corner'] ); ?>> Keep the renderer's own bottom-right corner of the video</label></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

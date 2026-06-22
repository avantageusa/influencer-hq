<?php
/**
 * Minimal interstitial while invisible Turnstile runs (guest portal gate).
 *
 * Expects: $site_key, $redirect_to, $post_url, $nonce, $js_url, $js_version, $logo_url, $ts_error
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Gate passes $post_url; tolerate older includes that used $ajax_url.
if ( ! isset( $post_url ) || $post_url === '' ) {
	if ( isset( $ajax_url ) && $ajax_url !== '' ) {
		$post_url = $ajax_url;
	} else {
		$post_url = home_url( '/portal/verify/' );
	}
}
if ( ! isset( $redirect_to ) || $redirect_to === '' ) {
	$redirect_to = home_url( '/portal/portal-home/' );
}
if ( ! isset( $site_key ) || $site_key === '' ) {
	$site_key = defined( 'CF_TURNSTILE_SITE_KEY' ) ? CF_TURNSTILE_SITE_KEY : '';
}
if ( ! isset( $nonce ) || $nonce === '' ) {
	$nonce = wp_create_nonce( 'ihq_portal_turnstile_unlock' );
}
if ( ! isset( $js_url ) || $js_url === '' ) {
	$js_url = get_template_directory_uri() . '/js/ihq-portal-turnstile-gate.js';
}
if ( ! isset( $js_version ) || $js_version === '' ) {
	$js_path    = get_template_directory() . '/js/ihq-portal-turnstile-gate.js';
	$js_version = file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1';
}
if ( ! isset( $logo_url ) || $logo_url === '' ) {
	$logo_url = get_template_directory_uri() . '/images/logo-home-claude.jpg';
}

$ihq_portal_ts_show_retry = ! empty( $ts_error );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php esc_html_e( 'Verifying…', 'influencer-hq' ); ?> — influencerHQ</title>
	<style>
		body {
			margin: 0;
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			background: #0a0a0a;
			color: #e8e8e8;
			font-family: 'Be Vietnam Pro', system-ui, sans-serif;
		}
		.ihq-portal-ts-wrap {
			text-align: center;
			padding: 32px 24px;
			max-width: 420px;
		}
		.ihq-portal-ts-wrap img {
			max-height: 52px;
			width: auto;
			margin-bottom: 28px;
		}
		.ihq-portal-ts-msg {
			font-size: 1rem;
			line-height: 1.5;
			margin-bottom: 8px;
		}
		.ihq-portal-ts-sub {
			font-size: 0.88rem;
			color: #9a9a9a;
			margin-bottom: 20px;
		}
		.ihq-portal-ts-err {
			display: none;
			color: #f85149;
			font-size: 0.9rem;
			margin-top: 16px;
		}
		#ihq-portal-ts-widget {
			display: flex;
			justify-content: center;
			margin-top: 8px;
		}
		#ihq-portal-ts-retry {
			display: none;
			margin-top: 20px;
			padding: 10px 20px;
			font-size: 0.95rem;
			font-family: inherit;
			cursor: pointer;
			border: 1px solid #444;
			border-radius: 6px;
			background: #1a1a1a;
			color: #e8e8e8;
		}
		#ihq-portal-ts-retry:hover {
			background: #252525;
		}
	</style>
</head>
<body>
	<div class="ihq-portal-ts-wrap">
		<img src="<?php echo esc_url( $logo_url ); ?>" alt="influencerHQ">
		<p class="ihq-portal-ts-msg"><?php esc_html_e( 'Complete the security check below', 'influencer-hq' ); ?></p>
		<p class="ihq-portal-ts-sub"><?php esc_html_e( 'This only takes a moment.', 'influencer-hq' ); ?></p>
		<div id="ihq-portal-ts-widget"></div>
		<button type="button" id="ihq-portal-ts-retry"><?php esc_html_e( 'Try again', 'influencer-hq' ); ?></button>
		<p class="ihq-portal-ts-err" id="ihq-portal-ts-err" role="alert"<?php echo $ihq_portal_ts_show_retry ? ' style="display:block"' : ''; ?>>
			<?php if ( $ihq_portal_ts_show_retry ) : ?>
				<?php esc_html_e( 'Verification failed. Click Try again.', 'influencer-hq' ); ?>
			<?php endif; ?>
		</p>
	</div>
	<script>
	window.IHQ_PORTAL_TURNSTILE_GATE = <?php echo wp_json_encode(
		array(
			'siteKey'        => $site_key,
			'postUrl'        => $post_url,
			'nonce'          => $nonce,
			'redirectTo'     => $redirect_to,
			'showRetryError'     => $ihq_portal_ts_show_retry,
			'requireManualStart' => $ihq_portal_ts_show_retry,
			'errorMessage'       => __( 'Verification failed. Please try again.', 'influencer-hq' ),
			'retryMessage'       => __( 'Verification failed. Click Try again.', 'influencer-hq' ),
		)
	); ?>;
	</script>
	<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
	<script src="<?php echo esc_url( $js_url ); ?>?ver=<?php echo esc_attr( $js_version ); ?>"></script>
</body>
</html>

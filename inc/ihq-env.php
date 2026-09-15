<?php
/**
 * Per-instance environment configuration.
 *
 * The theme is deployed unchanged to several WP Engine environments (dev, QA,
 * PROD). Everything that differs between them — API Gateway base, game portal
 * base, credentials — is read from wp-config.php constants with an environment
 * variable fallback. Nothing environment-specific lives in the theme tree.
 *
 * Required constants (the site refuses to render without them):
 *
 *     define( 'IHQ_ENVIRONMENT',          'dev' );   // dev | qa | prod — diagnostics only
 *     define( 'IHQ_API_BASE_URL',         'https://<id>.execute-api.<region>.amazonaws.com/<stage>' );
 *     define( 'IHQ_GAME_PORTAL_BASE_URL', 'https://<host>/av-baccarat' );
 *     define( 'IHQ_INFLUENCER_API_KEY',   '<x-api-key for /oauth/start-session>' );
 *
 * Optional constants (feature is off when absent):
 *
 *     define( 'CF_TURNSTILE_SITE_KEY',    '...' );
 *     define( 'CF_TURNSTILE_SECRET_KEY',  '...' );
 *     define( 'IHQ_ELEVENLABS_API_KEY',   '...' );
 *     define( 'IHQ_GENIUS_REFERRALS_API_TOKEN', '...' );   // test-form.php only
 *
 * Resolution order is constant → getenv() → default, the same as
 * inc/anam-proxy.php. Do not add a second mechanism.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Constant names the site cannot run without. */
const IHQ_ENV_REQUIRED_KEYS = array(
	'IHQ_ENVIRONMENT',
	'IHQ_API_BASE_URL',
	'IHQ_GAME_PORTAL_BASE_URL',
	'IHQ_INFLUENCER_API_KEY',
);

/**
 * Read one configuration value: wp-config constant first, environment variable
 * second, caller-supplied default last. Empty strings count as unset.
 *
 * @param string     $name    Constant / environment variable name.
 * @param mixed|null $default Returned when the value is not configured.
 * @return mixed|null
 */
function ihq_env_get( $name, $default = null ) {
	if ( defined( $name ) ) {
		$value = constant( $name );
		if ( is_string( $value ) && $value !== '' ) {
			return $value;
		}
	}

	$env = getenv( $name );
	if ( $env !== false && $env !== '' ) {
		return $env;
	}

	return $default;
}

/**
 * Read a configuration value that must be present. Fails loudly when it is
 * not, naming the missing constant so the fix is obvious from the error.
 *
 * @param string $name Constant / environment variable name.
 * @return string
 */
function ihq_env_require( $name ) {
	$value = ihq_env_get( $name );
	if ( $value === null ) {
		ihq_env_fail_missing( $name );
		return '';
	}
	return (string) $value;
}

/**
 * Whether a URL is an absolute https:// URL with a host. Credentials travel
 * to these URLs, so plain http:// is a configuration error, not a choice.
 *
 * @param string $url URL to check.
 * @return bool
 */
function ihq_env_is_https_url( $url ) {
	if ( ! is_string( $url ) || $url === '' ) {
		return false;
	}
	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
		return false;
	}
	return isset( $parts['scheme'] ) && strtolower( $parts['scheme'] ) === 'https';
}

/**
 * URL-shaped config value: must be https:// with a host, trailing slash
 * removed so callers can always append "/path" safely.
 *
 * @param string $name Constant / environment variable name.
 * @return string
 */
function ihq_env_require_url( $name ) {
	$url = ihq_env_require( $name );
	if ( $url === '' ) {
		return '';
	}
	if ( ! ihq_env_is_https_url( $url ) ) {
		ihq_env_fail( sprintf( '%s must be an absolute https:// URL.', $name ) );
		return '';
	}
	return untrailingslashit( $url );
}

/**
 * Whether a URL points at the same origin (scheme + host + port) as this
 * instance's configured API base. Used to refuse per-user URL overrides that
 * would carry the API key somewhere else.
 *
 * @param string $url          Candidate URL.
 * @param string $api_base_url Configured API base to compare against.
 * @return bool
 */
function ihq_env_url_matches_api_origin( $url, $api_base_url ) {
	if ( ! ihq_env_is_https_url( $url ) || ! ihq_env_is_https_url( $api_base_url ) ) {
		return false;
	}
	$candidate = wp_parse_url( $url );
	$base      = wp_parse_url( $api_base_url );

	$candidate_port = isset( $candidate['port'] ) ? (int) $candidate['port'] : 443;
	$base_port      = isset( $base['port'] ) ? (int) $base['port'] : 443;

	return strtolower( $candidate['host'] ) === strtolower( $base['host'] )
		&& $candidate_port === $base_port;
}

/**
 * Names of required constants that are not configured on this instance.
 *
 * @return string[]
 */
function ihq_env_missing_required_keys() {
	$missing = array();
	foreach ( IHQ_ENV_REQUIRED_KEYS as $name ) {
		if ( ihq_env_get( $name ) === null ) {
			$missing[] = $name;
		}
	}
	return $missing;
}

/**
 * Stop the request because the instance is misconfigured.
 *
 * On wp-admin *pages* the site stays usable and an admin notice names the
 * problem, so an operator can see what to fix. Everywhere else — front end,
 * admin-ajax (where is_admin() is also true), REST, cron — the request dies
 * with the message. Serving pages that silently point at the wrong
 * environment is worse than an error page.
 *
 * @param string $message Human-readable reason.
 * @return void
 */
function ihq_env_fail( $message ) {
	error_log( '[ihq-env] ' . $message );

	$is_admin_page = function_exists( 'is_admin' ) && is_admin()
		&& ! ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() );
	if ( $is_admin_page ) {
		return;
	}

	wp_die( esc_html( $message ), 'Influencer HQ configuration error', array( 'response' => 500 ) );
}

/**
 * Stop the request because a required constant is missing.
 *
 * @param string $name The missing constant.
 * @return void
 */
function ihq_env_fail_missing( $name ) {
	ihq_env_fail( sprintf(
		'Influencer HQ is not configured for this instance: define %s in wp-config.php.',
		$name
	) );
}

/**
 * Admin notice listing every missing required constant.
 *
 * @return void
 */
function ihq_env_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$missing = ihq_env_missing_required_keys();
	if ( $missing === array() ) {
		return;
	}
	printf(
		'<div class="notice notice-error"><p><strong>Influencer HQ is not configured for this instance.</strong> Define in wp-config.php: <code>%s</code></p></div>',
		esc_html( implode( ', ', $missing ) )
	);
}
add_action( 'admin_notices', 'ihq_env_admin_notice' );

/**
 * Fail at load time, not on first use: a page that never touches an accessor
 * must not render on a half-configured instance.
 */
$ihq_env_missing = ihq_env_missing_required_keys();
if ( $ihq_env_missing !== array() ) {
	ihq_env_fail_missing( $ihq_env_missing[0] );
}
unset( $ihq_env_missing );

/**
 * The influencerhq-api gateway base for this instance. Defined here, first in
 * load order, so every inc/ file and template can rely on it regardless of
 * where it sits in functions.php's require list.
 */
if ( ! defined( 'INFLUENCER_API_BASE' ) ) {
	define( 'INFLUENCER_API_BASE', ihq_env_require_url( 'IHQ_API_BASE_URL' ) );
}

/**
 * Environment name for logs and diagnostics. Never branch behaviour on it —
 * every environment-specific value has its own constant.
 *
 * @return string
 */
function ihq_env_name() {
	return ihq_env_require( 'IHQ_ENVIRONMENT' );
}

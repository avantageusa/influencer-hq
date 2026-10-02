<?php
/**
 * Shorten a URL through influencerhq-api POST /minify (ENGR-6971), which
 * proxies to url-minification-api. Used for the verification-email link
 * (ENGR-6966). Any failure returns the original URL: a long link that works
 * beats a short one that does not, so minifying never blocks the email.
 *
 * Request:  {"originalUrl": "<url>"}
 * Response: {"shortUrl": "...", "shortCode": "...", "qrCodeSvg": "..."}
 * The minifier returns the original URL as shortUrl and omits shortCode when
 * it cannot shorten, so a missing shortCode means "not minified".
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Path of the minify route under INFLUENCER_API_BASE. */
const IHQ_URL_MINIFY_PATH = '/minify';

/**
 * Seconds to wait for the minifier. The verification email waits on this
 * inside the visitor's AJAX request, so keep it short.
 */
const IHQ_URL_MINIFY_TIMEOUT_SECONDS = 5;

/**
 * Shortened form of a URL, or the URL itself when it cannot be shortened.
 *
 * @param string $url          URL to shorten.
 * @param string $api_base_url influencerhq-api base (INFLUENCER_API_BASE).
 * @return string
 */
function ihq_minify_url_or_original( $url, $api_base_url ) {
	$response = wp_remote_post(
		$api_base_url . IHQ_URL_MINIFY_PATH,
		array(
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'body'        => wp_json_encode( array( 'originalUrl' => $url ) ),
			'timeout'     => IHQ_URL_MINIFY_TIMEOUT_SECONDS,
			'redirection' => 0,
			'sslverify'   => true,
		)
	);

	if ( is_wp_error( $response ) ) {
		error_log( '[ihq-minify] request failed, sending the raw link: ' . $response->get_error_message() );
		return $url;
	}

	$status = (int) wp_remote_retrieve_response_code( $response );
	if ( $status < 200 || $status >= 300 ) {
		error_log( '[ihq-minify] HTTP ' . $status . ', sending the raw link' );
		return $url;
	}

	$short_url = ihq_minify_short_url_from_body( wp_remote_retrieve_body( $response ) );
	if ( $short_url === '' ) {
		error_log( '[ihq-minify] no shortCode / https shortUrl in the response, sending the raw link' );
		return $url;
	}

	return $short_url;
}

/**
 * The shortUrl from a /minify response body, or '' when the body carries no
 * shortCode or no https shortUrl.
 *
 * @param string $body Raw response body.
 * @return string
 */
function ihq_minify_short_url_from_body( $body ) {
	$decoded = json_decode( (string) $body, true );
	if ( ! is_array( $decoded ) ) {
		return '';
	}

	$short_code = isset( $decoded['shortCode'] ) ? $decoded['shortCode'] : '';
	if ( ! is_string( $short_code ) || $short_code === '' ) {
		return '';
	}

	$short_url = isset( $decoded['shortUrl'] ) ? $decoded['shortUrl'] : '';
	if ( ! ihq_env_is_https_url( $short_url ) ) {
		return '';
	}

	return $short_url;
}

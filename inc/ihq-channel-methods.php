<?php
/**
 * Which communication methods IHQ sign-in may use (ENGR-8014, epic ENGR-7866).
 *
 * The switches live in GrowthBook (`communication-methods.ihq.*`), but the
 * theme reads no GrowthBook: influencerhq-api serves them on
 * GET /v1/channels/methods (ENGR-8013), called here server to server.
 *
 *     200 {"methods":{"email":true,"sms":false,…all eight}}  feature present
 *     200 {"methods":null}                                     no feature
 *
 * A method is on only when its value is true; a missing key or a non-boolean
 * value counts as off. With no feature, every method behaves as it did before
 * the switch existed (Q7). A failed call uses the last good answer, and with
 * none, today's lists, so the route not being deployed yet changes nothing.
 *
 * The answer is cached for IHQ_CHANNEL_METHODS_CACHE_SECONDS, so a switch takes
 * effect within that time and a down API is retried at that rate, not on every
 * request.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Path of the methods route under INFLUENCER_API_BASE (contract EQ12). */
const IHQ_CHANNEL_METHODS_PATH = '/v1/channels/methods';

/** How long one answer is used before the route is asked again (ticket: no more than 30 seconds). */
const IHQ_CHANNEL_METHODS_CACHE_SECONDS = 30;

/** Seconds to wait for the route. Page renders wait on this once per cache period, so keep it short. */
const IHQ_CHANNEL_METHODS_TIMEOUT_SECONDS = 3;

/** Transient holding the current answer, wrapped as array( 'methods' => array|null ). */
const IHQ_CHANNEL_METHODS_CACHE_KEY = 'ihq_channel_methods';

/** Option holding the last answer the route gave, used when a later call fails. */
const IHQ_CHANNEL_METHODS_LAST_GOOD_OPTION = 'ihq_channel_methods_last_good';

/** Error code of a refused method; the same name account-api and influencerhq-api use. */
const IHQ_CHANNEL_METHOD_DISABLED_ERROR = 'method_disabled';

/** HTTP status of a refused method. */
const IHQ_CHANNEL_METHOD_REFUSED_STATUS = 403;

/** The eight methods of `communication-methods` (EQ2), in its order. */
const IHQ_CHANNEL_METHODS = array( 'email', 'sms', 'telegram', 'line', 'whatsapp', 'zalo', 'kakaotalk', 'wechat' );

/** Script module that hides a method refused while the page is open (js/ihq-channel-methods.js). */
const IHQ_CHANNEL_METHODS_SCRIPT_MODULE_ID = '@ihq/channel-methods';

/**
 * Parse a methods-route body.
 *
 * @param string $body Raw response body.
 * @return array{ok:bool,methods:array<string,bool>|null} ok false when the body
 *               is not the route's shape; methods null for "no feature".
 */
function ihq_channel_methods_parse_body( $body ) {
	$failed  = array(
		'ok'      => false,
		'methods' => null,
	);
	$decoded = json_decode( (string) $body, true );
	if ( ! is_array( $decoded ) || ! array_key_exists( 'methods', $decoded ) ) {
		return $failed;
	}

	$raw = $decoded['methods'];
	if ( null === $raw ) {
		return array(
			'ok'      => true,
			'methods' => null,
		);
	}
	if ( ! is_array( $raw ) ) {
		return $failed;
	}

	$methods = array();
	foreach ( IHQ_CHANNEL_METHODS as $method ) {
		$value = array_key_exists( $method, $raw ) ? $raw[ $method ] : false;
		if ( ! is_bool( $value ) ) {
			error_log( '[ihq-channel-methods] ' . $method . ' is not a boolean; treating it as off' );
		}
		$methods[ $method ] = true === $value;
	}

	return array(
		'ok'      => true,
		'methods' => $methods,
	);
}

/**
 * Ask influencerhq-api for the methods.
 *
 * @param string $api_base_url influencerhq-api base (INFLUENCER_API_BASE).
 * @return array{ok:bool,methods:array<string,bool>|null}
 */
function ihq_channel_methods_fetch( $api_base_url ) {
	$failed   = array(
		'ok'      => false,
		'methods' => null,
	);
	$response = wp_remote_get(
		$api_base_url . IHQ_CHANNEL_METHODS_PATH,
		array(
			'headers'     => array( 'Accept' => 'application/json' ),
			'timeout'     => IHQ_CHANNEL_METHODS_TIMEOUT_SECONDS,
			'redirection' => 0,
			'sslverify'   => true,
		)
	);

	if ( is_wp_error( $response ) ) {
		error_log( '[ihq-channel-methods] request failed: ' . $response->get_error_message() );
		return $failed;
	}

	$status = (int) wp_remote_retrieve_response_code( $response );
	if ( $status < 200 || $status >= 300 ) {
		error_log( '[ihq-channel-methods] HTTP ' . $status );
		return $failed;
	}

	$parsed = ihq_channel_methods_parse_body( wp_remote_retrieve_body( $response ) );
	if ( ! $parsed['ok'] ) {
		error_log( '[ihq-channel-methods] the body is not {"methods":{…}|null}' );
	}
	return $parsed;
}

/**
 * The last answer the route gave, or null (no feature) when it never answered.
 *
 * @return array<string,bool>|null
 */
function ihq_channel_methods_last_good() {
	$stored = get_option( IHQ_CHANNEL_METHODS_LAST_GOOD_OPTION );
	if ( ! is_array( $stored ) || ! array_key_exists( 'methods', $stored ) ) {
		return null;
	}
	return $stored['methods'];
}

/**
 * The methods IHQ sign-in follows now: the cached answer, else a fresh one,
 * else the last good one, else null.
 *
 * @param string $api_base_url influencerhq-api base (INFLUENCER_API_BASE).
 * @return array<string,bool>|null Method => on; null means "no feature, keep today's lists".
 */
function ihq_channel_methods_current( $api_base_url = INFLUENCER_API_BASE ) {
	$cached = get_transient( IHQ_CHANNEL_METHODS_CACHE_KEY );
	if ( is_array( $cached ) && array_key_exists( 'methods', $cached ) ) {
		return $cached['methods'];
	}

	$fetched = ihq_channel_methods_fetch( $api_base_url );
	if ( $fetched['ok'] ) {
		$methods = $fetched['methods'];
		update_option( IHQ_CHANNEL_METHODS_LAST_GOOD_OPTION, array( 'methods' => $methods ), false );
	} else {
		$methods = ihq_channel_methods_last_good();
	}

	set_transient( IHQ_CHANNEL_METHODS_CACHE_KEY, array( 'methods' => $methods ), IHQ_CHANNEL_METHODS_CACHE_SECONDS );
	return $methods;
}

/**
 * Whether the switch lets a method through. With no feature (null) every
 * method keeps today's behaviour, so the answer is true and the method's own
 * gates (a configured Telegram client id, say) still apply.
 *
 * @param string                  $method  One of IHQ_CHANNEL_METHODS.
 * @param array<string,bool>|null $methods ihq_channel_methods_current().
 * @return bool
 */
function ihq_channel_method_allowed( $method, $methods ) {
	if ( null === $methods ) {
		return true;
	}
	return isset( $methods[ $method ] ) && true === $methods[ $method ];
}

/**
 * Today's method list without the methods the switch turns off. Each entry
 * needs an 'id'; entries keep their order and their other fields.
 *
 * @param array<int,array{id:string}> $entries Method list as the view builds it.
 * @param array<string,bool>|null     $methods ihq_channel_methods_current().
 * @return array<int,array{id:string}>
 */
function ihq_channel_methods_filter_list( array $entries, $methods ) {
	$kept = array();
	foreach ( $entries as $entry ) {
		if ( ihq_channel_method_allowed( $entry['id'], $methods ) ) {
			$kept[] = $entry;
		}
	}
	return $kept;
}

/**
 * Display name of a method, as on the sign-in buttons.
 *
 * @param string $method One of IHQ_CHANNEL_METHODS.
 * @return string
 */
function ihq_channel_method_label( $method ) {
	$labels = array(
		'email'     => __( 'Email', 'influencer-hq' ),
		'sms'       => __( 'SMS', 'influencer-hq' ),
		'telegram'  => __( 'Telegram', 'influencer-hq' ),
		'line'      => __( 'LINE', 'influencer-hq' ),
		'whatsapp'  => __( 'WhatsApp', 'influencer-hq' ),
		'zalo'      => __( 'Zalo', 'influencer-hq' ),
		'kakaotalk' => __( 'KakaoTalk', 'influencer-hq' ),
		'wechat'    => __( 'WeChat', 'influencer-hq' ),
	);
	return isset( $labels[ $method ] ) ? $labels[ $method ] : (string) $method;
}

/**
 * The message a refused method shows (EQ14).
 *
 * @param string $method One of IHQ_CHANNEL_METHODS.
 * @return string
 */
function ihq_channel_method_unavailable_message( $method ) {
	return sprintf(
		/* translators: %s: a communication method, e.g. Email or Telegram */
		__( '%s isn\'t available right now', 'influencer-hq' ),
		ihq_channel_method_label( $method )
	);
}

/**
 * The error body of a refused method.
 *
 * @param string $method One of IHQ_CHANNEL_METHODS.
 * @return array{error:string,method:string,message:string}
 */
function ihq_channel_method_refusal( $method ) {
	return array(
		'error'   => IHQ_CHANNEL_METHOD_DISABLED_ERROR,
		'method'  => $method,
		'message' => ihq_channel_method_unavailable_message( $method ),
	);
}

/**
 * In an AJAX handler: answer 403 method_disabled when the switch turns the
 * method off. The handler returns when this is true.
 *
 * @param string $method One of IHQ_CHANNEL_METHODS.
 * @return bool True when the refusal was sent.
 */
function ihq_channel_method_refuse_if_off( $method ) {
	if ( ihq_channel_method_allowed( $method, ihq_channel_methods_current() ) ) {
		return false;
	}
	wp_send_json_error( ihq_channel_method_refusal( $method ), IHQ_CHANNEL_METHOD_REFUSED_STATUS );
	return true;
}

/**
 * Load js/ihq-channel-methods.js wherever a sign-in form can render: every
 * page a logged-out visitor sees (the portal header's auth modal, Portal Login).
 *
 * @return void
 */
function ihq_channel_methods_enqueue() {
	if ( is_user_logged_in() ) {
		return;
	}
	$path = get_template_directory() . '/js/ihq-channel-methods.js';
	wp_enqueue_script_module(
		IHQ_CHANNEL_METHODS_SCRIPT_MODULE_ID,
		get_template_directory_uri() . '/js/ihq-channel-methods.js',
		array(),
		file_exists( $path ) ? (string) filemtime( $path ) : _S_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'ihq_channel_methods_enqueue' );

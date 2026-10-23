<?php
/**
 * IHQ login code on every verified method (ENGR-8022, epic ENGR-7868).
 *
 * While this switch is on, the code ihq_issue_and_send_login_code_for_influencer()
 * emails also goes to every verified chat app the influencer has connected,
 * through influencerhq-api POST /ihq/v1/messages (ENGR-8005). The theme reads no
 * GrowthBook flags (PO-3063 evaluation, EQ3), so the switch is a WordPress option,
 * like ihq_portal_onboarding_enabled:
 *
 *     wp option update ihq_login_code_fan_out_enabled 1
 *     wp option delete ihq_login_code_fan_out_enabled     (back to off)
 *
 * The option does not exist after a deploy, which reads as off: the login send
 * behaves as it does today. Turn it on only once ENGR-8005 and influencerhq-api's
 * sendMessage grant are deployed on that stage, and wp-config holds
 * IHQ_SERVER_API_KEY (the DEPLOYMENT NOTEs on ENGR-8022).
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** WordPress option that turns the fan-out on. */
const IHQ_LOGIN_CODE_FAN_OUT_ENABLED_OPTION = 'ihq_login_code_fan_out_enabled';

/** Option values (lower-cased, trimmed) that count as "on"; anything else is off. */
const IHQ_LOGIN_CODE_FAN_OUT_ON_VALUES = array( '1', 'true', 'yes', 'on' );

/** influencerhq-api's server-only send route, under INFLUENCER_API_BASE (ENGR-8005). */
const IHQ_LOGIN_CODE_FAN_OUT_PATH = '/ihq/v1/messages';

/**
 * wp-config constant holding the value of SSM /<stage>/influencerhq-api/IHQ_SERVER_API_KEY,
 * which influencerhq-api checks as the x-api-key (ENGR-8008). Not IHQ_INFLUENCER_API_KEY:
 * that one is account-api's SSO key.
 */
const IHQ_LOGIN_CODE_FAN_OUT_API_KEY_NAME = 'IHQ_SERVER_API_KEY';

/** Every verified method; influencerhq-api skips the ones not connected or switched off. */
const IHQ_LOGIN_CODE_FAN_OUT_METHODS = 'all';

/** Sent on each app's approved code template (cross-repo contract name). */
const IHQ_LOGIN_CODE_FAN_OUT_KIND = 'one_time_code';

/** A method's result when the channel service could not send; it carries a reason. */
const IHQ_LOGIN_CODE_FAN_OUT_FAILED = 'failed';

/** Logged for a failed method that came back without a reason. */
const IHQ_LOGIN_CODE_FAN_OUT_NO_REASON = 'no reason given';

/** Key of the code step's line in the login AJAX answer. */
const IHQ_LOGIN_CODE_FAN_OUT_HINT_KEY = 'chat_app_hint';

/**
 * Seconds the login send waits for the route. The visitor's AJAX request waits on
 * it, so keep it short; influencerhq-api finishes a send it has started anyway.
 */
const IHQ_LOGIN_CODE_FAN_OUT_TIMEOUT_SECONDS = 3;

/** D3: at most one fan-out per influencer per minute... */
const IHQ_LOGIN_CODE_FAN_OUT_MIN_GAP_SECONDS = 60;

/** ...and at most five per hour. Inside either limit only the email goes. */
const IHQ_LOGIN_CODE_FAN_OUT_MAX_PER_WINDOW = 5;
const IHQ_LOGIN_CODE_FAN_OUT_WINDOW_SECONDS = 3600;

/** Transient per influencer holding the fan-out times of the last hour. */
const IHQ_LOGIN_CODE_FAN_OUT_TRANSIENT_PREFIX = 'ihq_login_fan_out_';

/** User meta with the influencer's language (one of IHQ_COACH_THEME_LOCALES). */
const IHQ_LOGIN_CODE_FAN_OUT_LANGUAGE_META = '_ihq_aicoach_language';

/**
 * The influencer's language as the BCP 47 tag influencerhq-api expects, named as in
 * the ticket's language table. The channel service falls back by subtag
 * (ja-JP, ja) and then to English.
 */
const IHQ_LOGIN_CODE_FAN_OUT_LOCALES = array(
	'en'  => 'en',
	'zh'  => 'zh-CN',
	'yue' => 'zh-HK',
	'ja'  => 'ja-JP',
	'ko'  => 'ko-KR',
	'th'  => 'th-TH',
	'vi'  => 'vi-VN',
);

/** Locale when the influencer has no saved language. */
const IHQ_LOGIN_CODE_FAN_OUT_DEFAULT_LOCALE = 'en';

/** Prefix of every log line, so the fan-out can be found in the PHP error log. */
const IHQ_LOGIN_CODE_FAN_OUT_LOG_PREFIX = '[ihq-login-fan-out] ';

/**
 * Whether the login code fan-out is switched on.
 *
 * Only an explicit "on" value counts ("1", "true", "yes", "on", 1, true); a
 * missing option, an empty string, "0", "off" or anything unrecognised is off,
 * so a typo in a WP-CLI command fails closed.
 *
 * @return bool
 */
function ihq_login_code_fan_out_enabled() {
	$value = get_option( IHQ_LOGIN_CODE_FAN_OUT_ENABLED_OPTION, false );
	if ( is_bool( $value ) ) {
		return $value;
	}
	if ( is_int( $value ) ) {
		return 1 === $value;
	}
	if ( ! is_string( $value ) ) {
		return false;
	}
	return in_array( strtolower( trim( $value ) ), IHQ_LOGIN_CODE_FAN_OUT_ON_VALUES, true );
}

/**
 * Add the code step's line saying the code is in the influencer's chat apps too to
 * a login AJAX answer. While the switch is off the answer is returned unchanged.
 * The line depends only on the switch, never on the account, so it says nothing
 * about which emails are registered. Product may change the copy (PQ1).
 *
 * @param array $answer The answer's data.
 * @return array
 */
function ihq_login_code_fan_out_add_hint( $answer ) {
	if ( ! ihq_login_code_fan_out_enabled() ) {
		return $answer;
	}
	$answer[ IHQ_LOGIN_CODE_FAN_OUT_HINT_KEY ] = __( "If you've connected a chat app, the code is there too.", 'influencer-hq' );
	return $answer;
}

/**
 * The fan-out times still inside the hourly window.
 *
 * @param mixed $sent_at Stored value: a list of Unix times, or anything else when unset or damaged.
 * @param int   $now     Current Unix time.
 * @return int[]
 */
function ihq_login_code_fan_out_recent_sends( $sent_at, $now ) {
	if ( ! is_array( $sent_at ) ) {
		return array();
	}
	$recent = array();
	foreach ( $sent_at as $time ) {
		if ( is_int( $time ) && $time > $now - IHQ_LOGIN_CODE_FAN_OUT_WINDOW_SECONDS ) {
			$recent[] = $time;
		}
	}
	return $recent;
}

/**
 * Whether another fan-out fits D3: none in the last minute and fewer than five in the last hour.
 *
 * @param int[] $recent Fan-out times inside the hourly window.
 * @param int   $now    Current Unix time.
 * @return bool
 */
function ihq_login_code_fan_out_window_allows( $recent, $now ) {
	if ( count( $recent ) >= IHQ_LOGIN_CODE_FAN_OUT_MAX_PER_WINDOW ) {
		return false;
	}
	foreach ( $recent as $time ) {
		if ( $now - $time < IHQ_LOGIN_CODE_FAN_OUT_MIN_GAP_SECONDS ) {
			return false;
		}
	}
	return true;
}

/**
 * Take a fan-out slot for the influencer, or report that D3's limits are spent.
 *
 * Read then write, so two sends in the same instant can both pass; the 45-second
 * email throttle has the same shape, and the channel service caps sends per subject.
 *
 * @param int $user_id Influencer's WordPress user ID.
 * @param int $now     Current Unix time.
 * @return bool True when the slot was taken.
 */
function ihq_login_code_fan_out_claim_slot( $user_id, $now ) {
	$key    = IHQ_LOGIN_CODE_FAN_OUT_TRANSIENT_PREFIX . (int) $user_id;
	$recent = ihq_login_code_fan_out_recent_sends( get_transient( $key ), $now );
	if ( ! ihq_login_code_fan_out_window_allows( $recent, $now ) ) {
		return false;
	}
	$recent[] = $now;
	set_transient( $key, $recent, IHQ_LOGIN_CODE_FAN_OUT_WINDOW_SECONDS );
	return true;
}

/**
 * The influencer's locale for the chat-app message.
 *
 * @param int $user_id Influencer's WordPress user ID.
 * @return string
 */
function ihq_login_code_fan_out_locale( $user_id ) {
	$language = get_user_meta( $user_id, IHQ_LOGIN_CODE_FAN_OUT_LANGUAGE_META, true );
	if ( ! is_string( $language ) || ! array_key_exists( $language, IHQ_LOGIN_CODE_FAN_OUT_LOCALES ) ) {
		return IHQ_LOGIN_CODE_FAN_OUT_DEFAULT_LOCALE;
	}
	return IHQ_LOGIN_CODE_FAN_OUT_LOCALES[ $language ];
}

/**
 * Body of POST /ihq/v1/messages. platformUsername is the influencer's game account
 * (influencerhq-wpu-<id>), which influencerhq-api finds the connections by.
 *
 * @param int    $user_id Influencer's WordPress user ID.
 * @param string $code    The login code.
 * @return array<string, string>
 */
function ihq_login_code_fan_out_request_body( $user_id, $code ) {
	return array(
		'platformUsername' => ihq_referral_user_id( $user_id ),
		'methods'          => IHQ_LOGIN_CODE_FAN_OUT_METHODS,
		'kind'             => IHQ_LOGIN_CODE_FAN_OUT_KIND,
		'text'             => (string) $code,
		'locale'           => ihq_login_code_fan_out_locale( $user_id ),
	);
}

/**
 * The methods the route reports as failed, for the log.
 *
 * @param string $body Raw response body: {"results": [{"provider", "status", "reason"?}]}.
 * @return string[] "<provider>: <reason>" for each method that failed.
 */
function ihq_login_code_fan_out_failed_methods( $body ) {
	$failed  = array();
	$decoded = json_decode( (string) $body, true );
	if ( ! is_array( $decoded ) || ! isset( $decoded['results'] ) || ! is_array( $decoded['results'] ) ) {
		return $failed;
	}
	foreach ( $decoded['results'] as $result ) {
		if ( ! is_array( $result ) || ! isset( $result['provider'], $result['status'] ) ) {
			continue;
		}
		if ( IHQ_LOGIN_CODE_FAN_OUT_FAILED !== $result['status'] ) {
			continue;
		}
		$reason   = isset( $result['reason'] ) ? (string) $result['reason'] : IHQ_LOGIN_CODE_FAN_OUT_NO_REASON;
		$failed[] = (string) $result['provider'] . ': ' . $reason;
	}
	return $failed;
}

/**
 * Send the code to every verified method. Never throws; every failure is logged
 * without the code or the key, and never shown to the influencer.
 *
 * It waits for the answer only to log failures, and at most
 * IHQ_LOGIN_CODE_FAN_OUT_TIMEOUT_SECONDS. 'blocking' => false would not wait
 * less: WordPress's cURL transport still runs the request to its timeout, and
 * then drops the answer and every error (Requests, Transport/Curl.php).
 *
 * @param int    $user_id      Influencer's WordPress user ID.
 * @param string $code         The login code.
 * @param string $api_base_url influencerhq-api base (INFLUENCER_API_BASE).
 * @return bool Whether the request was made, whatever it answered. False only
 *              when IHQ_SERVER_API_KEY is not set.
 */
function ihq_login_code_fan_out_send( $user_id, $code, $api_base_url ) {
	$api_key = ihq_env_get( IHQ_LOGIN_CODE_FAN_OUT_API_KEY_NAME );
	if ( null === $api_key ) {
		error_log( IHQ_LOGIN_CODE_FAN_OUT_LOG_PREFIX . IHQ_LOGIN_CODE_FAN_OUT_API_KEY_NAME . ' is not set; user ' . (int) $user_id . ' got the code by email only' );
		return false;
	}

	$response = wp_remote_post(
		$api_base_url . IHQ_LOGIN_CODE_FAN_OUT_PATH,
		array(
			'headers'     => array(
				'Content-Type' => 'application/json',
				'x-api-key'    => (string) $api_key,
			),
			'body'        => wp_json_encode( ihq_login_code_fan_out_request_body( $user_id, $code ) ),
			'timeout'     => IHQ_LOGIN_CODE_FAN_OUT_TIMEOUT_SECONDS,
			'redirection' => 0,
		)
	);

	ihq_login_code_fan_out_log_answer( $user_id, $response );
	return true;
}

/**
 * Log what went wrong with the route's answer, if anything. A timeout is logged
 * too, although influencerhq-api may still deliver the code after it.
 *
 * @param int            $user_id  Influencer's WordPress user ID.
 * @param array|WP_Error $response wp_remote_post()'s answer.
 * @return void
 */
function ihq_login_code_fan_out_log_answer( $user_id, $response ) {
	if ( is_wp_error( $response ) ) {
		error_log( IHQ_LOGIN_CODE_FAN_OUT_LOG_PREFIX . 'request failed for user ' . (int) $user_id . ': ' . $response->get_error_message() );
		return;
	}

	$status = (int) wp_remote_retrieve_response_code( $response );
	if ( $status < 200 || $status >= 300 ) {
		error_log( IHQ_LOGIN_CODE_FAN_OUT_LOG_PREFIX . 'HTTP ' . $status . ' for user ' . (int) $user_id );
		return;
	}

	$failed = ihq_login_code_fan_out_failed_methods( wp_remote_retrieve_body( $response ) );
	if ( array() !== $failed ) {
		error_log( IHQ_LOGIN_CODE_FAN_OUT_LOG_PREFIX . 'methods failed for user ' . (int) $user_id . ': ' . implode( ', ', $failed ) );
	}
}

/**
 * The login send's fan-out step: off, rate limited, or sent.
 *
 * The answer is whether the code went to influencerhq-api, never whether a chat
 * app reported it delivered, so a failed or slow call changes nothing the
 * influencer sees (ENGR-8022 Done-when).
 *
 * @param int    $user_id Influencer's WordPress user ID.
 * @param string $code    The login code.
 * @param int    $now     Current Unix time.
 * @return bool Whether the code was sent to influencerhq-api for the chat apps.
 */
function ihq_login_code_fan_out( $user_id, $code, $now ) {
	if ( ! ihq_login_code_fan_out_enabled() ) {
		return false;
	}
	if ( ! ihq_login_code_fan_out_claim_slot( $user_id, $now ) ) {
		return false;
	}
	return ihq_login_code_fan_out_send( $user_id, $code, INFLUENCER_API_BASE );
}

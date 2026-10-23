"""Focused mutations for ENGR-8022 (login code on every verified method), in an isolated temporary theme copy.

Each mutation must make tests/login-code-fan-out.test.php fail. Run from anywhere:

    python3 scripts/test-login-code-fan-out-mutations.py
"""
from pathlib import Path
import shutil
import subprocess
import tempfile

root = Path(__file__).resolve().parents[1]
tests = ["tests/login-code-fan-out.test.php"]
module = "inc/login-code-fan-out.php"
handler = "inc/email-verification-handler.php"
mutations = [
    # The switch.
    (module, "get_option( IHQ_LOGIN_CODE_FAN_OUT_ENABLED_OPTION, false )", "get_option( IHQ_LOGIN_CODE_FAN_OUT_ENABLED_OPTION, true )"),
    (module, "if ( is_bool( $value ) ) {\n\t\treturn $value;", "if ( is_bool( $value ) ) {\n\t\treturn true;"),
    (module, "return 1 === $value;", "return 0 !== $value;"),
    (module, "if ( ! is_string( $value ) ) {\n\t\treturn false;", "if ( ! is_string( $value ) ) {\n\t\treturn true;"),
    (module, "in_array( strtolower( trim( $value ) ), IHQ_LOGIN_CODE_FAN_OUT_ON_VALUES, true )", "in_array( trim( $value ), IHQ_LOGIN_CODE_FAN_OUT_ON_VALUES, true )"),
    (module, "in_array( strtolower( trim( $value ) ), IHQ_LOGIN_CODE_FAN_OUT_ON_VALUES, true )", "in_array( strtolower( $value ), IHQ_LOGIN_CODE_FAN_OUT_ON_VALUES, true )"),
    (module, "in_array( strtolower( trim( $value ) ), IHQ_LOGIN_CODE_FAN_OUT_ON_VALUES, true )", "in_array( strtolower( trim( $value ) ), IHQ_LOGIN_CODE_FAN_OUT_ON_VALUES, false )"),
    # The hint.
    (module, "if ( ! ihq_login_code_fan_out_enabled() ) {\n\t\treturn $answer;", "if ( false ) {\n\t\treturn $answer;"),
    (module, "if ( ! ihq_login_code_fan_out_enabled() ) {\n\t\treturn $answer;", "if ( true ) {\n\t\treturn $answer;"),
    # The D3 window.
    (module, "is_int( $time ) && $time > $now", "$time > $now"),
    (module, "$time > $now - IHQ_LOGIN_CODE_FAN_OUT_WINDOW_SECONDS", "$time >= $now - IHQ_LOGIN_CODE_FAN_OUT_WINDOW_SECONDS"),
    (module, "count( $recent ) >= IHQ_LOGIN_CODE_FAN_OUT_MAX_PER_WINDOW", "count( $recent ) > IHQ_LOGIN_CODE_FAN_OUT_MAX_PER_WINDOW"),
    (module, "$now - $time < IHQ_LOGIN_CODE_FAN_OUT_MIN_GAP_SECONDS", "$now - $time <= IHQ_LOGIN_CODE_FAN_OUT_MIN_GAP_SECONDS"),
    (module, "if ( ! ihq_login_code_fan_out_window_allows( $recent, $now ) ) {", "if ( false ) {"),
    (module, "\t$recent[] = $now;\n", "\n"),
    (module, "set_transient( $key, $recent, IHQ_LOGIN_CODE_FAN_OUT_WINDOW_SECONDS );", "set_transient( $key, $recent, IHQ_LOGIN_CODE_FAN_OUT_MIN_GAP_SECONDS );"),
    (module, "IHQ_LOGIN_CODE_FAN_OUT_TRANSIENT_PREFIX . (int) $user_id;", "IHQ_LOGIN_CODE_FAN_OUT_TRANSIENT_PREFIX;"),
    # The request.
    (module, "! is_string( $language ) || ! array_key_exists", "! array_key_exists"),
    (module, "\t'yue' => 'zh-HK',", "\t'yue' => 'zh-CN',"),
    (module, "'platformUsername' => ihq_referral_user_id( $user_id ),", "'platformUsername' => (string) $user_id,"),
    (module, "'text'             => (string) $code,", "'text'             => '',"),
    (module, "'locale'           => ihq_login_code_fan_out_locale( $user_id ),", "'locale'           => IHQ_LOGIN_CODE_FAN_OUT_DEFAULT_LOCALE,"),
    (module, "'x-api-key'    => (string) $api_key,", "'x-api-key'    => ihq_env_get( 'IHQ_INFLUENCER_API_KEY' ),"),
    (module, "'timeout'     => IHQ_LOGIN_CODE_FAN_OUT_TIMEOUT_SECONDS,", "'timeout'     => 15,"),
    (module, "'redirection' => 0,", "'redirection' => 5,"),
    (module, "$api_base_url . IHQ_LOGIN_CODE_FAN_OUT_PATH,", "$api_base_url . '/notifications/channels/messages',"),
    (module, "if ( null === $api_key ) {\n\t\terror_log", "if ( false ) {\n\t\terror_log"),
    # Logging the answer.
    (module, "if ( IHQ_LOGIN_CODE_FAN_OUT_FAILED !== $result['status'] ) {", "if ( false ) {"),
    (module, "isset( $result['reason'] ) ? (string) $result['reason'] : IHQ_LOGIN_CODE_FAN_OUT_NO_REASON", "IHQ_LOGIN_CODE_FAN_OUT_NO_REASON"),
    (module, "! isset( $result['provider'], $result['status'] )", "! isset( $result['status'] )"),
    (module, "if ( is_wp_error( $response ) ) {\n\t\terror_log", "if ( false ) {\n\t\terror_log"),
    (module, "get_error_message() );\n\t\treturn;\n", "get_error_message() );\n"),
    (module, "$status < 200 || $status >= 300", "$status <= 200 || $status >= 300"),
    (module, "$status < 200 || $status >= 300", "$status < 200 || $status > 300"),
    (module, "' for user ' . (int) $user_id );\n\t\treturn;\n", "' for user ' . (int) $user_id );\n"),
    (module, "if ( array() !== $failed ) {", "if ( false ) {"),
    # What the send answers: the request was made, whatever it answered.
    (module, "\tihq_login_code_fan_out_log_answer( $user_id, $response );\n", "\n"),
    (module, "\tihq_login_code_fan_out_log_answer( $user_id, $response );\n\treturn true;", "\tihq_login_code_fan_out_log_answer( $user_id, $response );\n\treturn false;"),
    (module, "got the code by email only' );\n\t\treturn false;", "got the code by email only' );\n\t\treturn true;"),
    # The fan-out step.
    (module, "if ( ! ihq_login_code_fan_out_enabled() ) {\n\t\treturn false;\n\t}\n\tif ( ! ihq_login_code_fan_out_claim_slot", "if ( false ) {\n\t\treturn false;\n\t}\n\tif ( ! ihq_login_code_fan_out_claim_slot"),
    (module, "if ( ! ihq_login_code_fan_out_claim_slot( $user_id, $now ) ) {", "if ( false ) {"),
    # The login send.
    (handler, "if ( ! is_email( $email ) || ihq_is_placeholder_email( $email ) ) {\n\t\treturn", "if ( ! is_email( $email ) ) {\n\t\treturn"),
    (handler, "ihq_login_code_email_body( $code, $minutes_left, ihq_login_code_fan_out_enabled() )", "ihq_login_code_email_body( $code, $minutes_left, false )"),
    (handler, "ihq_login_code_email_body( $code, $minutes_left, ihq_login_code_fan_out_enabled() )", "ihq_login_code_email_body( $code, $minutes_left, true )"),
    (handler, "\tif ( $warn_not_to_share ) {", "\tif ( ! $warn_not_to_share ) {"),
    (handler, "$code_went_to_chat_apps = ihq_login_code_fan_out( (int) $user->ID, $code, time() );", "$code_went_to_chat_apps = false;"),
    (handler, "$code_went_to_chat_apps = ihq_login_code_fan_out( (int) $user->ID, $code, time() );", "$code_went_to_chat_apps = true;"),
    (handler, "$code_went_to_chat_apps = ihq_login_code_fan_out( (int) $user->ID, $code, time() );", "$code_went_to_chat_apps = ihq_login_code_fan_out( (int) $user->ID, '000000', time() );"),
    (handler, "\t\tif ( ! $code_went_to_chat_apps ) {", "\t\tif ( true ) {"),
    (handler, "\t\tif ( ! $code_went_to_chat_apps ) {", "\t\tif ( false ) {"),
    # The login AJAX.
    (handler, "! ihq_user_has_influencer_role( $user ) || ihq_is_placeholder_email( $email ) ) {", "! ihq_user_has_influencer_role( $user ) ) {"),
    (handler, "        wp_send_json_success(\n            ihq_login_code_fan_out_add_hint(\n                array(\n                    'signup_token'    => '',", "        wp_send_json_success(\n            (\n                array(\n                    'signup_token'    => '',"),
    (handler, "        ihq_login_code_fan_out_add_hint(\n            array(\n                'signup_token'    => $result['signup_token'],", "        (\n            array(\n                'signup_token'    => $result['signup_token'],"),
]


def run(target):
    for test in tests:
        result = subprocess.run(
            ["docker", "run", "--rm", "-v", f"{target}:/app:ro", "-w", "/app", "php:8.2-cli", "php", test],
            capture_output=True,
        )
        if result.returncode != 0:
            return result.returncode
    return 0


with tempfile.TemporaryDirectory(prefix="ihq-fan-out-mutations-") as folder:
    target = Path(folder)
    for part in ("inc", "tests"):
        shutil.copytree(root / part, target / part)
    shutil.copy(root / "functions.php", target / "functions.php")
    assert run(target) == 0, "Baseline failed"
    survivors = []
    for path, before, after in mutations:
        source = (root / path).read_text()
        assert source.count(before) == 1, f"{path}: expected exactly one match for {before!r}"
        (target / path).write_text(source.replace(before, after))
        if run(target) == 0:
            survivors.append(f"{path}: {before!r} -> {after!r}")
        (target / path).write_text(source)
    assert not survivors, "Surviving mutations:\n" + "\n".join(survivors)
print(f"Killed {len(mutations)}/{len(mutations)} login code fan-out mutations")

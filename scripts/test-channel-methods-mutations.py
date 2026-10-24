"""Focused mutations for ENGR-8014 (sign-in methods from influencerhq-api), in an isolated temporary theme copy.

Each PHP mutation must make one of the PHP tests below fail, and each JS mutation
js/ihq-channel-methods.test.js. Needs docker (php:8.2-cli) and node. Run from anywhere:

    python3 scripts/test-channel-methods-mutations.py
"""
from pathlib import Path
import shutil
import subprocess
import tempfile

root = Path(__file__).resolve().parents[1]
php_tests = [
    "tests/ihq-channel-methods.test.php",
    "tests/channel-method-ajax-refusal.test.php",
]
js_test = "js/ihq-channel-methods.test.js"
reader = "inc/ihq-channel-methods.php"
email = "inc/email-verification-handler.php"
telegram = "inc/telegram-login-handler.php"
client = "js/ihq-channel-methods.js"

tg_nonce = (
    "\tif ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ihq_telegram_login_pubkey' ) ) {\n"
    "\t\twp_send_json_error( array( 'message' => __( 'Invalid security token', 'influencer-hq' ) ) );\n"
    "\t\treturn;\n"
    "\t}\n"
)
tg_gate = "\tif ( ihq_channel_method_refuse_if_off( 'telegram' ) ) {\n\t\treturn;\n\t}\n"
login_nonce = (
    "    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ihq_login_code_nonce' ) ) {\n"
    "        wp_send_json_error( array( 'message' => __( 'Invalid security token', 'influencer-hq' ) ) );\n"
    "        return;\n"
    "    }\n\n"
)
email_gate = "    if ( ihq_channel_method_refuse_if_off( 'email' ) ) {\n        return;\n    }\n"


def drop_gate(path, head, gate):
    """Remove one handler's gate, found by the function line that precedes it."""
    return (path, head + gate, head)


mutations = [
    # The route and its request.
    (reader, "const IHQ_CHANNEL_METHODS_PATH = '/v1/channels/methods';", "const IHQ_CHANNEL_METHODS_PATH = '/v1/channel/methods';"),
    (reader, "const IHQ_CHANNEL_METHODS_CACHE_SECONDS = 30;", "const IHQ_CHANNEL_METHODS_CACHE_SECONDS = 31;"),
    (reader, "const IHQ_CHANNEL_METHODS_TIMEOUT_SECONDS = 3;", "const IHQ_CHANNEL_METHODS_TIMEOUT_SECONDS = 4;"),
    (reader, "'redirection' => 0,", "'redirection' => 1,"),
    (reader, "'sslverify'   => true,", "'sslverify'   => false,"),
    (reader, "'headers'     => array( 'Accept' => 'application/json' ),", "'headers'     => array(),"),
    (reader, "const IHQ_CHANNEL_METHODS_SCRIPT_MODULE_ID = '@ihq/channel-methods';", "const IHQ_CHANNEL_METHODS_SCRIPT_MODULE_ID = '@ihq/channel';"),
    # ihq_channel_methods_parse_body()
    (reader, "if ( ! is_array( $decoded ) || ! array_key_exists( 'methods', $decoded ) ) {", "if ( ! is_array( $decoded ) ) {"),
    (reader, "if ( null === $raw ) {", "if ( false ) {"),
    (reader, "if ( ! is_array( $raw ) ) {", "if ( false ) {"),
    (reader, "$value = array_key_exists( $method, $raw ) ? $raw[ $method ] : false;", "$value = array_key_exists( $method, $raw ) ? $raw[ $method ] : true;"),
    (reader, "$methods[ $method ] = true === $value;", "$methods[ $method ] = (bool) $value;"),
    # ihq_channel_methods_fetch()
    (reader, "if ( $status < 200 || $status >= 300 ) {", "if ( $status <= 200 || $status >= 300 ) {"),
    (reader, "if ( $status < 200 || $status >= 300 ) {", "if ( $status < 200 || $status > 300 ) {"),
    (reader, "if ( $status < 200 || $status >= 300 ) {", "if ( $status < 199 || $status >= 300 ) {"),
    (reader, "if ( $status < 200 || $status >= 300 ) {", "if ( $status < 200 || $status >= 299 ) {"),
    (reader, "if ( $status < 200 || $status >= 300 ) {", "if ( false ) {"),
    # ihq_channel_methods_last_good()
    (reader, "if ( ! is_array( $stored ) || ! array_key_exists( 'methods', $stored ) ) {", "if ( true ) {"),
    # ihq_channel_methods_current()
    (reader, "if ( is_array( $cached ) && array_key_exists( 'methods', $cached ) ) {", "if ( is_array( $cached ) ) {"),
    (reader, "if ( is_array( $cached ) && array_key_exists( 'methods', $cached ) ) {", "if ( false ) {"),
    (reader, "if ( $fetched['ok'] ) {", "if ( true ) {"),
    (reader, "if ( $fetched['ok'] ) {", "if ( false ) {"),
    (reader, "update_option( IHQ_CHANNEL_METHODS_LAST_GOOD_OPTION, array( 'methods' => $methods ), false );", "update_option( IHQ_CHANNEL_METHODS_LAST_GOOD_OPTION, array( 'methods' => $methods ), true );"),
    (reader, "update_option( IHQ_CHANNEL_METHODS_LAST_GOOD_OPTION, array( 'methods' => $methods ), false );", ""),
    (reader, "\tset_transient( IHQ_CHANNEL_METHODS_CACHE_KEY, array( 'methods' => $methods ), IHQ_CHANNEL_METHODS_CACHE_SECONDS );\n", "\n"),
    # ihq_channel_method_allowed() / ihq_channel_methods_filter_list()
    (reader, "if ( null === $methods ) {\n\t\treturn true;", "if ( null === $methods ) {\n\t\treturn false;"),
    (reader, "return isset( $methods[ $method ] ) && true === $methods[ $method ];", "return isset( $methods[ $method ] );"),
    (reader, "return isset( $methods[ $method ] ) && true === $methods[ $method ];", "return ! empty( $methods );"),
    (reader, "if ( ihq_channel_method_allowed( $entry['id'], $methods ) ) {", "if ( true ) {"),
    # The refusal.
    (reader, "const IHQ_CHANNEL_METHOD_DISABLED_ERROR = 'method_disabled';", "const IHQ_CHANNEL_METHOD_DISABLED_ERROR = 'method_off';"),
    (reader, "const IHQ_CHANNEL_METHOD_REFUSED_STATUS = 403;", "const IHQ_CHANNEL_METHOD_REFUSED_STATUS = 400;"),
    (reader, "__( '%s isn\\'t available right now', 'influencer-hq' ),", "__( '%s is not available right now', 'influencer-hq' ),"),
    (reader, "'kakaotalk' => __( 'KakaoTalk', 'influencer-hq' ),", "'kakaotalk' => __( 'Kakaotalk', 'influencer-hq' ),"),
    (reader, "return isset( $labels[ $method ] ) ? $labels[ $method ] : (string) $method;", "return isset( $labels[ $method ] ) ? $labels[ $method ] : '';"),
    (reader, "if ( ihq_channel_method_allowed( $method, ihq_channel_methods_current() ) ) {", "if ( true ) {"),
    (reader, "if ( ihq_channel_method_allowed( $method, ihq_channel_methods_current() ) ) {", "if ( false ) {"),
    # ihq_channel_methods_enqueue()
    (reader, "\tif ( is_user_logged_in() ) {\n\t\treturn;\n\t}\n\t$path", "\t$path"),
    (reader, "file_exists( $path ) ? (string) filemtime( $path ) : _S_VERSION", "_S_VERSION"),
    (reader, "add_action( 'wp_enqueue_scripts', 'ihq_channel_methods_enqueue' );", ""),
    # The Telegram-widget handlers.
    drop_gate(telegram, "function ihq_handle_telegram_login_nonce_ajax() {\n" + tg_nonce + "\n", tg_gate),
    drop_gate(telegram, "function ihq_handle_verify_telegram_id_token_ajax() {\n" + tg_nonce + "\n", tg_gate),
    drop_gate(telegram, "function ihq_handle_register_telegram_user_ajax() {\n" + tg_nonce + "\n", tg_gate),
    drop_gate(telegram, "function ihq_handle_login_telegram_user_ajax() {\n" + tg_nonce + "\n", tg_gate),
    # The email-code handlers.
    drop_gate(email, "function ihq_handle_send_login_code_ajax() {\n" + login_nonce, email_gate),
    drop_gate(email, "function ihq_handle_verify_login_code_ajax() {\n" + login_nonce, email_gate),
    (email, "    if ( ihq_channel_method_refuse_if_off( $delivery_method ) ) {\n        return;\n    }\n", ""),
    (email, "$delivery_method        = ihq_registration_code_delivery_method( $comm_primary, $comm_methods );", "$delivery_method        = 'email';"),
    (email, "if ( 'telegram' === $comm_primary && empty( $comm_methods ) ) {", "if ( 'telegram' === $comm_primary ) {"),
    (email, "if ( 'telegram' === $delivery_method ) {", "if ( false ) {"),
    (email, "    if ( ihq_channel_method_refuse_if_off( ihq_pending_registration_delivery_method( $pending ) ) ) {\n        return;\n    }\n", ""),
    (email, "if ( isset( $pending['telegram_user_id'] ) && (int) $pending['telegram_user_id'] > 0 ) {", "if ( isset( $pending['telegram_user_id'] ) && (int) $pending['telegram_user_id'] >= 0 ) {"),
    # js/ihq-channel-methods.js
    (client, "export const METHOD_DISABLED_ERROR = 'method_disabled';", "export const METHOD_DISABLED_ERROR = 'method_off';"),
    (client, "export const METHOD_ATTRIBUTE = 'data-ihq-channel-method';", "export const METHOD_ATTRIBUTE = 'data-channel-method';"),
    (client, "\tkakaotalk: 'KakaoTalk',", "\tkakaotalk: 'Kakaotalk',"),
    (client, "export const METHOD_LABELS = Object.freeze( {", "export const METHOD_LABELS = ( {"),
    (client, "return METHOD_LABELS[ method ] + \" isn't available right now\";", "return method + \" isn't available right now\";"),
    (client, "if ( ! response || true === response.success || ! response.data ) {", "if ( ! response || ! response.data ) {"),
    (client, "if ( ! response || true === response.success || ! response.data ) {", "if ( ! response || true === response.success ) {"),
    (client, "if ( METHOD_DISABLED_ERROR !== data.error ) {", "if ( false ) {"),
    (client, "if ( ! Object.prototype.hasOwnProperty.call( METHOD_LABELS, data.method ) ) {", "if ( ! ( data.method in METHOD_LABELS ) ) {"),
    (client, "if ( ! Object.prototype.hasOwnProperty.call( METHOD_LABELS, data.method ) ) {", "if ( false ) {"),
    (client, "'string' === typeof data.message && '' !== data.message", "'string' === typeof data.message"),
    (client, "'string' === typeof data.message && '' !== data.message", "'' !== data.message"),
    (client, "\t\t? data.message\n", "\t\t? unavailableMessage( data.method )\n"),
    (client, "const controls = '[' + METHOD_ATTRIBUTE + '=\"' + refusal.method + '\"], [' + DIVIDER_ATTRIBUTE + ']';", "const controls = '[' + METHOD_ATTRIBUTE + '], [' + DIVIDER_ATTRIBUTE + ']';"),
    (client, "const controls = '[' + METHOD_ATTRIBUTE + '=\"' + refusal.method + '\"], [' + DIVIDER_ATTRIBUTE + ']';", "const controls = '[' + METHOD_ATTRIBUTE + '=\"' + refusal.method + '\"]';"),
    (client, "export const UNAVAILABLE_NOTICE_ATTRIBUTE = 'data-ihq-channel-method-unavailable';", "export const UNAVAILABLE_NOTICE_ATTRIBUTE = 'data-channel-method-unavailable';"),
    (client, "export const DIVIDER_ATTRIBUTE = 'data-ihq-channel-method-divider';", "export const DIVIDER_ATTRIBUTE = 'data-channel-method-divider';"),
    (client, "const NOTICE_DISPLAY = 'block';", "const NOTICE_DISPLAY = '';"),
    (client, "return '[' + UNAVAILABLE_NOTICE_ATTRIBUTE + '=\"' + method + '\"]';", "return '[' + UNAVAILABLE_NOTICE_ATTRIBUTE + ']';"),
    (client, "\t\tnotice.textContent = refusal.message;\n", ""),
    (client, "\t\tnotice.style.display = NOTICE_DISPLAY;\n", ""),
    (client, "\t\telement.style.display = 'none';", "\t\telement.hidden = true;"),
    (client, "\treturn refusal;\n}\n\n/**", "\treturn null;\n}\n\n/**"),
    (client, "if ( 'undefined' !== typeof window ) {", "if ( false ) {"),
    (client, "window.ihqChannelMethods = Object.freeze( { methodRefusal, hideRefusedMethod, showFailure } );", "window.ihqChannelMethods = { methodRefusal, hideRefusedMethod, showFailure };"),
    (client, "window.ihqChannelMethods = Object.freeze( { methodRefusal, hideRefusedMethod, showFailure } );", "window.ihqChannelMethods = Object.freeze( { methodRefusal, hideRefusedMethod } );"),
    # showFailure: a refusal shows once, in the notice (re-review of 28a29c9)
    (client, "if ( refusal && root.querySelector( noticeSelector( refusal.method ) ) ) {", "if ( root.querySelector( noticeSelector( refusal.method ) ) ) {"),
    (client, "if ( refusal && root.querySelector( noticeSelector( refusal.method ) ) ) {", "if ( refusal ) {"),
    (client, "\t\terrorBox.textContent = '';\n", ""),
    (client, "\t\terrorBox.style.display = 'none';\n\t\treturn refusal;", "\t\treturn refusal;"),
    (client, "\t\terrorBox.style.display = 'none';\n\t\treturn refusal;", "\t\terrorBox.style.display = 'none';\n\t\treturn null;"),
    (client, "\terrorBox.textContent = message;\n", ""),
    (client, "if ( ! errorBox ) {\n\t\treturn refusal;", "if ( ! errorBox ) {\n\t\treturn null;"),
    (client, "const ERROR_DISPLAY = 'block';", "const ERROR_DISPLAY = '';"),
]


def run_php(target):
    for test in php_tests:
        result = subprocess.run(
            ["docker", "run", "--rm", "-v", f"{target}:/app:ro", "-w", "/app", "php:8.2-cli", "php", test],
            capture_output=True,
        )
        if result.returncode != 0:
            return result.returncode
    return 0


def run_js(target):
    result = subprocess.run(["node", "--test", js_test], cwd=target, capture_output=True)
    return result.returncode


def run(target, path):
    if path.endswith(".js"):
        return run_js(target)
    return run_php(target)


with tempfile.TemporaryDirectory(prefix="ihq-channel-methods-mutations-") as folder:
    target = Path(folder)
    for part in ("inc", "tests", "js"):
        shutil.copytree(root / part, target / part)
    assert run_php(target) == 0, "PHP baseline failed"
    assert run_js(target) == 0, "JS baseline failed"
    survivors = []
    for path, before, after in mutations:
        source = (root / path).read_text()
        assert source.count(before) == 1, f"{path}: expected exactly one match for {before!r}"
        (target / path).write_text(source.replace(before, after))
        if run(target, path) == 0:
            survivors.append(f"{path}: {before!r} -> {after!r}")
        (target / path).write_text(source)
    assert not survivors, "Surviving mutations:\n" + "\n".join(survivors)
print(f"Killed {len(mutations)}/{len(mutations)} channel-methods mutations")

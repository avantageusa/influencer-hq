"""Focused mutations for ENGR-7017 platform ID token refresh, in an isolated temporary theme copy.

Each mutation must make tests/platform-id-token-refresh.test.php fail. Run from anywhere:

    python3 scripts/test-platform-token-mutations.py
"""
from pathlib import Path
import shutil
import subprocess
import tempfile

root = Path(__file__).resolve().parents[1]
tests = [
    "tests/platform-id-token-refresh.test.php",
    "tests/registration-first-name-fallback.test.php",
    "tests/oauth-start-session-referrer.test.php",
]
token = "inc/ihq-platform-token.php"
ajax = "inc/api-ajax-calls.php"
handler = "inc/email-verification-handler.php"
expiry_rule = "return (int) $expires_at - IHQ_PLATFORM_ID_TOKEN_EXPIRY_MARGIN_SECONDS <= (int) $now;"
status_guard = "if ( IHQ_PLATFORM_HTTP_UNAUTHORIZED !== (int) wp_remote_retrieve_response_code( $response ) ) {"
mutations = [
    # ihq_platform_id_token_is_expired()
    (token, expiry_rule, "return (int) $expires_at - IHQ_PLATFORM_ID_TOKEN_EXPIRY_MARGIN_SECONDS < (int) $now;"),
    (token, expiry_rule, "return (int) $expires_at <= (int) $now;"),
    (token, "if ( ! is_numeric( $expires_at ) ) {\n\t\treturn false;", "if ( ! is_numeric( $expires_at ) ) {\n\t\treturn true;"),
    # ihq_refresh_platform_id_token()
    (token, "if ( true !== $refreshed ) {", "if ( false ) {"),
    # ihq_refresh_platform_id_token(): backoff and lock
    (token, "if ( false !== get_transient( $backoff_key ) ) {", "if ( false ) {"),
    (token, "if ( ! ihq_platform_refresh_lock_acquire( $wp_user_id, $now ) ) {", "if ( false ) {"),
    (token, "return ihq_platform_valid_stored_token( $wp_user_id, $now );", "return '';"),
    (token, "set_transient( $backoff_key, $now, IHQ_PLATFORM_REFRESH_BACKOFF_SECONDS );", ""),
    (token, "\t\tihq_platform_refresh_lock_release( $wp_user_id, $now );\n", "\n"),
    # ihq_platform_refresh_lock_acquire() / _release()
    (token, "if ( 1 === (int) $wpdb->query( $wpdb->prepare( $insert, $name, (string) $now ) ) ) {", "if ( false ) {"),
    (token, "$stale_before = (int) $now - IHQ_PLATFORM_REFRESH_LOCK_TTL_SECONDS;", "$stale_before = (int) $now;"),
    (token, "$stale_before = (int) $now - IHQ_PLATFORM_REFRESH_LOCK_TTL_SECONDS;", "$stale_before = (int) $now - IHQ_PLATFORM_REFRESH_LOCK_TTL_SECONDS + 1;"),
    (token, "AND option_value = %s\", $name, (string) $locked_at", "AND option_value < %d\", $name, PHP_INT_MAX"),
    # ihq_platform_valid_stored_token()
    (token, "if ( ! is_string( $id_token ) ) {\n\t\treturn '';\n\t}\n\tif ( ihq_platform_id_token_is_expired(", "if ( false ) {\n\t\treturn '';\n\t}\n\tif ( ihq_platform_id_token_is_expired("),
    (token, "if ( ihq_platform_id_token_is_expired( get_user_meta( $wp_user_id, 'ihq_token_expires', true ), $now ) ) {", "if ( false ) {"),
    # ihq_platform_session_begin()
    (token, "\t$session['refreshed'] = true;\n", "\n"),
    (token, "if ( '' !== $fresh ) {", "if ( false ) {"),
    (token, "if ( ! ihq_platform_id_token_is_expired(", "if ( ihq_platform_id_token_is_expired("),
    # ihq_platform_send_with_401_retry()
    (token, status_guard, "if ( 200 === (int) wp_remote_retrieve_response_code( $response ) ) {"),
    (token, status_guard, "if ( false ) {"),
    (token, "if ( ! empty( $session['refreshed'] ) ) {", "if ( false ) {"),
    (token, "if ( '' === $fresh || $fresh === $session['id_token'] ) {", "if ( '' === $fresh ) {"),
    (token, "if ( '' === $fresh || $fresh === $session['id_token'] ) {", "if ( $fresh === $session['id_token'] ) {"),
    (token, "return $send( $fresh );", "return $send( $session['id_token'] );"),
    # get_referral_link_ajax()
    (ajax, "&& ! $session['refreshed'] ) {", ") {"),
    (ajax, "$session = ihq_platform_session_begin( $user_id );", "$session = array( 'refreshed' => false );"),
    # get_referral_link_ajax(): a provisioning 404 without start-session is retryable, not final
    (ajax, "return '' !== $fresh && $fresh !== $previous_id_token;", "return '' !== $fresh;"),
    (ajax, "return '' !== $fresh && $fresh !== $previous_id_token;", "return $fresh !== $previous_id_token;"),
    (ajax, "return '' !== $fresh && $fresh !== $previous_id_token;", "return false;"),
    (ajax, "if ( $provisioning_skipped && IHQ_REFERRAL_HTTP_NOT_FOUND === (int) $status ) {", "if ( false ) {"),
    (ajax, "if ( $provisioning_skipped && IHQ_REFERRAL_HTTP_NOT_FOUND === (int) $status ) {", "if ( $provisioning_skipped ) {"),
    (ajax, "if ( $provisioning_skipped && IHQ_REFERRAL_HTTP_NOT_FOUND === (int) $status ) {", "if ( IHQ_REFERRAL_HTTP_NOT_FOUND === (int) $status ) {"),
    (ajax, "$provisioning_skipped = ! ihq_retry_referral_link_after_oauth_refresh(", "$provisioning_skipped = ihq_retry_referral_link_after_oauth_refresh("),
    # handlers use the session token, not the stored meta
    (ajax, "$session      = ihq_platform_session_begin( $wp_user_id );", "$session      = array( 'user_id' => $wp_user_id, 'id_token' => get_user_meta( $wp_user_id, 'ihq_id_token', true ), 'refreshed' => false );"),
    # ihq_refresh_influencer_oauth_tokens() reports success
    (handler, "time() + (int) ( $ihq_data['ExpiresIn'] ?? 3600 ) );\n    return true;", "time() + (int) ( $ihq_data['ExpiresIn'] ?? 3600 ) );\n    return false;"),
    (handler, "if ( ! $ihq_data || empty( $ihq_data['AccessToken'] ) ) {\n        return false;", "if ( ! $ihq_data || empty( $ihq_data['AccessToken'] ) ) {\n        return true;"),
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


with tempfile.TemporaryDirectory(prefix="ihq-token-mutations-") as folder:
    target = Path(folder)
    for part in ("inc", "tests", "js"):
        shutil.copytree(root / part, target / part)
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
print(f"Killed {len(mutations)}/{len(mutations)} platform token mutations")

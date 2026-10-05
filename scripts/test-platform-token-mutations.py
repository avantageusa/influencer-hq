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
    # ihq_platform_session_begin()
    (token, "\t$session['refreshed'] = true;\n", "\n"),
    (token, "if ( '' !== $fresh ) {", "if ( false ) {"),
    (token, "if ( ! ihq_platform_id_token_is_expired(", "if ( ihq_platform_id_token_is_expired("),
    # ihq_platform_send_with_401_retry()
    (token, status_guard, "if ( 200 === (int) wp_remote_retrieve_response_code( $response ) ) {"),
    (token, status_guard, "if ( false ) {"),
    (token, "if ( ! empty( $session['refreshed'] ) ) {", "if ( false ) {"),
    (token, "if ( '' === $fresh ) {", "if ( false ) {"),
    (token, "return $send( $fresh );", "return $send( $session['id_token'] );"),
    # get_referral_link_ajax()
    (ajax, "&& ! $session['refreshed'] ) {", ") {"),
    (ajax, "$session = ihq_platform_session_begin( $user_id );", "$session = array( 'refreshed' => false );"),
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

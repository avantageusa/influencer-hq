"""Focused mutations for ENGR-6966 referral attribution, in an isolated temporary theme copy.

Each mutation must make at least one of the referral tests fail. Run from anywhere:

    python3 scripts/test-referral-mutations.py
"""
from pathlib import Path
import shutil
import subprocess
import tempfile

root = Path(__file__).resolve().parents[1]
tests = [
    "tests/ihq-referral-attribution.test.php",
    "tests/ihq-url-minify.test.php",
    "tests/oauth-start-session-referrer.test.php",
]
mutations = [
    # inc/ihq-referral-attribution.php
    ("inc/ihq-referral-attribution.php", "const IHQ_REF_MAX_LENGTH = 64;", "const IHQ_REF_MAX_LENGTH = 65;"),
    ("inc/ihq-referral-attribution.php", "return mb_substr( $clean, 0, IHQ_REF_MAX_LENGTH );", "return $clean;"),
    ("inc/ihq-referral-attribution.php", "if ( $explicit !== '' ) {", "if ( $explicit === '' ) {"),
    ("inc/ihq-referral-attribution.php", "'secure'   => (bool) $secure,", "'secure'   => true,"),
    ("inc/ihq-referral-attribution.php", "'path'     => '/',", "'path'     => '/portal',"),
    ("inc/ihq-referral-attribution.php", "'samesite' => 'Lax',", "'samesite' => 'None',"),
    ("inc/ihq-referral-attribution.php", "\tunset( $_COOKIE[ IHQ_REF_COOKIE_NAME ] );\n", "\n"),
    ("inc/ihq-referral-attribution.php", "\tcall_user_func( $writer, IHQ_REF_COOKIE_NAME );\n", "\n"),
    ("inc/ihq-referral-attribution.php", "\t\treturn;\n\t}\n\n\tunset(", "\t}\n\n\tunset("),
    ("inc/ihq-referral-attribution.php", "\t\t$version,\n\t\tfalse\n", "\t\t$version,\n\t\ttrue\n"),
    # inc/ihq-env.php
    ("inc/ihq-env.php", "const IHQ_REF_COOKIE_TTL_DEFAULT_DAYS = 90;", "const IHQ_REF_COOKIE_TTL_DEFAULT_DAYS = 91;"),
    ("inc/ihq-env.php", "! is_string( $raw ) || ! ctype_digit( $raw )", "! is_string( $raw )"),
    ("inc/ihq-env.php", "if ( $days <= 0 ) {", "if ( $days < 0 ) {"),
    # inc/ihq-url-minify.php
    ("inc/ihq-url-minify.php", "$status < 200 || $status >= 300", "$status < 200 || $status > 300"),
    ("inc/ihq-url-minify.php", "$status < 200 || $status >= 300", "$status <= 200 || $status >= 300"),
    ("inc/ihq-url-minify.php", "const IHQ_URL_MINIFY_TIMEOUT_SECONDS = 5;", "const IHQ_URL_MINIFY_TIMEOUT_SECONDS = 30;"),
    ("inc/ihq-url-minify.php", "'redirection' => 0,", "'redirection' => 5,"),
    ("inc/ihq-url-minify.php", "! is_string( $short_code ) || $short_code === ''", "$short_code === ''"),
    ("inc/ihq-url-minify.php", "if ( ! ihq_env_is_https_url( $short_url ) ) {", "if ( $short_url === '' ) {"),
    ("inc/ihq-url-minify.php", "if ( ! is_array( $decoded ) ) {", "if ( $decoded === null ) {"),
    # inc/email-verification-handler.php
    ("inc/email-verification-handler.php", "if ( $referrer_code !== '' ) {", "if ( true ) {"),
    ("inc/email-verification-handler.php", "    ihq_ref_clear_cookie();\n", "\n"),
    ("inc/email-verification-handler.php", "'referrer_code' => ihq_ref_cookie_code(),", "'referrer_code' => '',"),
    ("inc/email-verification-handler.php", "array( 'referrer_code' => $referrer_code )", "array()"),
    ("inc/email-verification-handler.php", "isset( $options['referrer_code'] ) ? $options['referrer_code'] : ''", "''"),
    ("inc/email-verification-handler.php", "$verification_link = ihq_minify_url_or_original( $verification_link, INFLUENCER_API_BASE );", ""),
    ("inc/email-verification-handler.php", "'referrer_code'              => isset( $registration_data['referrer_code'] ) ? (string) $registration_data['referrer_code'] : '',", ""),
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


with tempfile.TemporaryDirectory(prefix="ihq-referral-mutations-") as folder:
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
            survivors.append(f"{path}: {before!r}")
        (target / path).write_text(source)
    assert not survivors, "Surviving mutations:\n" + "\n".join(survivors)
print(f"Killed {len(mutations)}/{len(mutations)} referral attribution mutations")

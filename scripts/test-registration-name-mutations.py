"""Focused mutations for ENGR-7016 first-name fallback, in an isolated temporary theme copy.

Each mutation must make tests/registration-first-name-fallback.test.php fail. Run from anywhere:

    python3 scripts/test-registration-name-mutations.py
"""
from pathlib import Path
import shutil
import subprocess
import tempfile

root = Path(__file__).resolve().parents[1]
tests = [
    "tests/registration-first-name-fallback.test.php",
    "tests/oauth-start-session-referrer.test.php",
]
handler = "inc/email-verification-handler.php"
resolve_call = "$first_name    = ihq_registration_first_name_or_username( $first_name, $created_login );"
mutations = [
    # ihq_registration_first_name_or_username()
    (handler, "if ( trim( $given_name ) !== '' ) {", "if ( $given_name !== '' ) {"),
    (handler, "if ( trim( $given_name ) !== '' ) {", "if ( trim( $given_name ) === '' ) {"),
    (handler, "        return $given_name;\n    }\n    return $username;", "        return trim( $given_name );\n    }\n    return $username;"),
    (handler, "        return $given_name;\n    }\n    return $username;", "        return $given_name;\n    }\n    return '';"),
    (handler, "    $given_name = (string) $first_name;\n    if", "    $given_name = $first_name;\n    if"),
    # ihq_create_influencer_user_from_registration_data()
    (handler, resolve_call, ""),
    (handler, resolve_call, "$first_name    = ihq_registration_first_name_or_username( $first_name, $original_username );"),
    # ENGR-7016 review: the stored (strict-sanitized) login must win over the requested username.
    (handler, "? $created_user->user_login : $username;", "? $username : $username;"),
    (handler, "( $created_user && ! empty( $created_user->user_login ) )", "( $created_user )"),
    (handler, "update_user_meta( $user_id, 'first_name', $first_name );", "update_user_meta( $user_id, 'first_name', '' );"),
    (handler, "        $user_id,\n        $first_name,\n", "        $user_id,\n        '',\n"),
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


with tempfile.TemporaryDirectory(prefix="ihq-name-mutations-") as folder:
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
print(f"Killed {len(mutations)}/{len(mutations)} first-name fallback mutations")

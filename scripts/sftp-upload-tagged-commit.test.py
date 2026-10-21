#!/usr/bin/env python3
"""Filter cases for scripts/sftp-upload-tagged-commit.py. No SFTP, no git."""

import importlib.machinery
import importlib.util
import sys
from pathlib import Path

SCRIPT = Path(__file__).with_name("sftp-upload-tagged-commit.py")
loader = importlib.machinery.SourceFileLoader("sftp_upload_tagged_commit", str(SCRIPT))
spec = importlib.util.spec_from_loader(loader.name, loader)
module = importlib.util.module_from_spec(spec)
loader.exec_module(module)

fail = 0


def check(label, actual, expected):
    global fail
    ok = actual is expected if isinstance(expected, bool) else actual == expected
    print(("PASS " if ok else "FAIL ") + label)
    if not ok:
        print("  expected:", expected)
        print("  actual:  ", actual)
        fail += 1


check("theme php is uploaded", module.should_skip("functions.php"), False)
check("inc path is uploaded", module.should_skip("inc/ihq-platform-token.php"), False)
check("template-parts is uploaded", module.should_skip("template-parts/portal-header.php"), False)
check("tests are skipped", module.should_skip("tests/platform-id-token-refresh.test.php"), True)
check("docs are skipped", module.should_skip("docs/plans/foo.plan.md"), True)
check("workflow is skipped", module.should_skip(".github/workflows/deploy-rfqa.yml"), True)
check("scripts are skipped", module.should_skip("scripts/sftp-upload-tagged-commit.py"), True)

print("ALL PASS" if fail == 0 else f"{fail} FAILED")
sys.exit(0 if fail == 0 else 1)

---
name: Test harness for the Login-page code verify handler
overview: >
  ihq_handle_verify_login_code_ajax() (inc/email-verification-handler.php) had no test that
  drove it end to end; its 3-strike rule was covered only by unit cases on
  ihq_login_verify_record_failure() / ihq_login_verify_increment_failures() (influencer-hq#160).
  Add tests/login-code-verify-handler.test.php, which runs the handler with WordPress, $wpdb
  and the start-session request stubbed and checks every state it can reach. Tests only: no
  production code change. Out of scope: ihq_issue_and_send_login_code_for_influencer()
  (ENGR-8022 is editing it), so the tests seed the pending record directly.
todos:
  - id: harness
    content: "tests/login-code-verify-handler.test.php: stubs from tests/visitor-code-verify.test.php plus an ordered event log (GET_LOCK / RELEASE_LOCK / sign-in / start-session / redirect)"
    status: completed
  - id: states
    content: "Cases: nonce, token/code shape, mutex busy, already locked, unknown token, expired (incl. last-second boundary), wrong 1/2/3, per-IP lock, right code + single use, stale counter, account fallback, start-session failure, counter DB error fails closed"
    status: completed
  - id: mutations
    content: "Hand-mutate the handler and its helpers; every mutation must fail the harness (listed in the PR)"
    status: completed
---

# [PO-3262] Test harness for the Login-page code verify handler

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3262
**Asked for by:** Steve Wolfe, after influencer-hq#160 (merged 7a2ec3d)
**Drafted by:** Claude Code (claude-opus-5-5)

## Approach

- One new file in the repo's existing style (`tests/*.test.php`, plain `check()` with `===`,
  run by `npm run test:php`). The stubs are copied from `tests/visitor-code-verify.test.php`
  rather than shared, so that file is untouched.
- `wp_send_json_*` throw, as in the existing tests, so each call returns the exact response.
- The start-session call is stubbed at `wp_remote_post`, so no seam in `inc/` is needed.
- Option, transient and mutex names are derived in the test from the IP, not through the
  helpers under test.
- `time()` is the real clock (the handler has no seam for it). Expiry uses offsets from now; the
  last-valid-second case retries until seed and verify fall in the same second.

## Alternatives considered

- **Extract a shared stub bootstrap** for both test files: less duplication, but it rewrites the
  existing test file, which is outside a tests-only harness ticket.
- **Issue the code through the send handler**: closer to a real flow, but it couples this test to
  `ihq_issue_and_send_login_code_for_influencer()`, which ENGR-8022 is changing.

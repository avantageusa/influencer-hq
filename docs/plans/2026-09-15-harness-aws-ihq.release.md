# QC IHQ sign-in bridge

This PR adds `inc/harness-auth-bridge.php`, loaded by the theme. The harness opens
`<QC portal origin>/?ihq_harness_auth=1&state=<request UUID>` in a popup. The state
is a correlation nonce, not a credential. The existing portal login page and
session authenticate the tester. A signed, five-minute HttpOnly cookie resumes
the bridge after login. Codes are delivered only to the configured harness
origin with `postMessage`; the harness also validates the popup and consumes its
request state once.

## Configuration

Set these new constants in the **QC** WordPress configuration, outside the theme:

```php
define('IHQ_HARNESS_STAGE', 'qc');
define('IHQ_HARNESS_ORIGIN', '<exact HTTPS harness origin, no trailing slash>');
define('IHQ_HARNESS_GATEWAY_URL', '<exact QC influencerhq-api base URL ending /qc>');
```

Missing/invalid configuration returns 404 for the bridge. The theme must have a
published page using `page-portal-login.php`. PHP 7.3+ is needed for cookie options;
Composer, WordPress theme metadata and PHPCompatibility validation now declare
the same PHP 7.3 minimum. Before deployment, run `composer check-platform-reqs`
on the target host; use a maintained PHP release for QC. Reuse the existing portal
server-side start-session API
key (`ihq_oauth_start_session_request_headers`). Do not enable request/response
body logging for the bridge or the account OAuth endpoints. The bridge ignores
per-user API URL overrides and does not save the token response in user metadata.
Existing login/profile helpers are unchanged.

The portal cookie continues to exist after harness Sign out. Sign out clears the
harness ID token; changing QC users requires signing out of the portal itself.
Do not configure COOP headers that sever the popup's opener relationship.

## Verification

```sh
composer check-platform-reqs
composer lint:wpcs
docker run --rm -v "$PWD:/app:ro" -w /app php:7.3-cli php tests/harness-auth-bridge.test.php
docker run --rm -v "$PWD:/app:ro" -w /app php:8.3-cli php tests/harness-auth-bridge.test.php
python3 scripts/test-harness-mutations.py
```

The tests cover signed/expired/forged handoff cookies, account-derived issuance,
HTTP failure handling, TLS and redirect restrictions. Browser tests live with
the harness. Full WordPress login and two-account QC acceptance follow deployment;
they were not run against live accounts in this PR.

## Rollout

Deploy the trusted Cognito claim and notification ownership/contract changes,
then the IHQ gateway and this portal bridge. Configure dedicated provider profiles
and exact origins. Complete the two-account QC acceptance recorded in the harness
release instructions before setting its `qcAccepted` flag. No deployment or merge
is part of these PRs. All four integration PRs target `main`. The separate notification app PR
[`avantageusa/notifications-service-api#32`](https://github.com/avantageusa/notifications-service-api/pull/32)
merged during implementation. Infrastructure PR
[`avantageusa/notifications-service-tf#15`](https://github.com/avantageusa/notifications-service-tf/pull/15)
remains separate from this work.

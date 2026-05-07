# Gotchas

### Hardcoded test OAuth authorization header in source
`inc/influencer-auth-handler.php:282` sends `Authorization: milos_testing` to the OAuth endpoint. This is a literal test token baked into the codebase, not a constant pulled from `wp-config.php` or an env var.
**Why this exists:** Appears to be a development shortcut never cleaned up before shipping.

### Plaintext passwords written to error_log on every login and register call
`inc/influencer-auth-handler.php:32,35,192,196` calls `error_log('temp influencer_register_ajax called: ' . print_r($_POST, true))`. `$_POST` at those points contains the user's password in plaintext. The prefix "temp" signals debug code never removed.
**Why this exists:** Temporary debugging left in production.

### JWT tokens decoded but never signature-verified
`inc/api-ajax-calls.php:102–108` splits the token on `.`, base64-decodes the payload, and trusts the `sub` claim. No signature verification occurs. The token is read from `ihq_id_token` WP user meta, so the attack surface is limited to DB access — but any code that can write user meta can spoof the identity the theme presents to the AWS API.
**Why this exists:** Full JWT verification requires the IdP's public key; the shortcut was taken during development.

### `_debug` object with JWT sub and internal user IDs returned to every API client
`inc/api-ajax-calls.php:124` appends `$debug` to every `wp_send_json_success()` payload. The debug object contains the AWS request URL, the JWT `sub` claim, and the internal user ID string `influencerhq-wpu-{wp_user_id}`. Client-side JS in `page-portal-challenges.php:681–747` stores this and surfaces it in DOM-inspectable debug panels.
**Why this exists:** Debug scaffolding never gated behind an environment flag.

### Nonce checking uses both hard-fail and soft-fail modes inconsistently
Some AJAX handlers call `check_ajax_referer('challenge_api_nonce', 'nonce')` (implicit `true` — dies on failure) and others call it with `false` (returns 0/false, no die). The soft-fail paths often continue executing and return an error only at a later point, or silently succeed if the downstream code doesn't check the return value.
**Why this exists:** Different developers added handlers at different times with no enforced convention.

### `init_influencer_role()` runs on every page load, not just activation
`inc/influencer-role.php:78–84` hooks `init_influencer_role()` to the `init` action. `create_influencer_role()` (called inside when role missing) calls `remove_role('influencer')` before re-creating — so a transient absence of the role causes it to be fully rebuilt each request.
**Why this exists:** Convenience; avoids needing to hook plugin activation in a theme. The idempotency check (`if (!get_role('influencer'))`) prevents the rebuild on normal requests, but there's still a `get_role()` DB call every page load.

### Admin blocking uses `$_SERVER['REQUEST_URI']` string match, not `is_admin()`
`inc/influencer-role.php:112` uses `strpos($_SERVER['REQUEST_URI'], '/wp-admin')`. Behind a reverse proxy or with a non-standard WordPress installation path, this can produce false positives or false negatives. WordPress's `is_admin()` is the canonical check and already exists elsewhere in the same file.
**Why this exists:** Written before the file's use of `is_admin()` was standardized.

### Challenge token lookup is an unindexed post_meta table scan
`inc/api-ajax-calls.php` performs `get_posts()` with `meta_query` on `_challenge_token`. WordPress's `wp_postmeta` table has no index on `meta_key + meta_value` combinations; this becomes a full table scan as challenge post count grows.
**Why this exists:** Standard WordPress pattern; custom indexes require direct DB DDL outside the theme.

### Live appearance ownership enforced only via `post_author`
`inc/api-ajax-calls.php:440–442` checks `(int) $post->post_author !== $user_id` as the sole authorization gate for live appearance actions. If an administrator reassigns post author, the original owner loses access and the new author gains it with no other record of the change.
**Why this exists:** Simplest ownership model available in WordPress CPT architecture.

### Broadcast schedule array stored in user meta with no size limit
`inc/api-ajax-calls.php:574–581` appends to a serialized array in `_kick_broadcasting_schedule` user meta with no cap on entries. A user or a compromised session can add unlimited schedule entries, bloating the meta row.
**Why this exists:** No validation step was added when the feature was built.

### Challenge invitation pre-fills invitee email without verifying ownership
`inc/api-ajax-calls.php:915–918` stores the caller-supplied `$email` directly as `_invitee_email` post meta. The challenge token URL is the only auth gate; there is no check that the email belongs to the logged-in invitee, so a link can be shared or the email field manipulated to associate the challenge with a different address.
**Why this exists:** Invitation flow assumes the link is secret and single-use; no additional binding was implemented.

### `debug_influencer_role()` outputs role data in `<head>` on every page
`inc/influencer-role.php:451–464` hooks into `wp_head` and outputs an HTML comment containing the current user's role and capability summary on every page load. Not a critical leak (capabilities are read-only metadata) but adds noise to page source and reveals internal role structure.
**Why this exists:** Debug helper never removed.

### OAuth session refresh called on every login, not just first registration
`inc/influencer-auth-handler.php:345` calls `ihq_register_oauth_user()` on every successful login to "refresh" the session. This triggers a POST to the external OAuth start-session endpoint each time, adding latency and API churn.
**Why this exists:** Simplest way to ensure tokens are fresh; a proper token-refresh flow was not implemented.

### Auth error/success state passed via 60-second cookies, not transients
`inc/influencer-auth-handler.php:15,21` sets `auth_error` and `auth_success` cookies directly. If the browser drops or delays the cookie (HTTPS redirect timing, cross-domain issues), the message is silently lost. WordPress transients keyed to user session would be more reliable.
**Why this exists:** Quick implementation; WP transients require a logged-in user ID, which isn't always available at the point these are set.

---

*This file was AI-drafted as a starting point. Refine, correct, and expand. Add new traps as they bite — that's the compounding loop.*


# Architecture

## What This Service Does

`influencer-hq` is a WordPress theme that powers the Avantage influencer portal — a web app where sports influencers can join challenges, manage their profile and broadcast schedule, track equity/earnings, and view live appearance schedules. The theme wraps a custom PHP portal on top of WordPress, proxying most business logic to an external AWS API. WordPress is used as an auth shell and session layer; all challenge/equity/account data lives in the external service.

## Major Components

| Component | File(s) | Role |
|---|---|---|
| Theme bootstrap | `functions.php` | Enqueue assets, register CPTs, localize JS nonces, wire all hooks |
| AJAX endpoint hub | `inc/api-ajax-calls.php` | All `wp_ajax_*` handlers; proxies to AWS API |
| OAuth / login | `inc/influencer-auth-handler.php` | OAuth handshake, WP user creation, session establishment |
| Email verification | `inc/email-verification-handler.php` | Send/verify email before OAuth, create WP user |
| Role & access control | `inc/influencer-role.php` | Custom `influencer` WP role, wp-admin blocking |
| Page templates | `page-portal-*.php`, `page-*.php` (17 files) | Portal screens: home, challenges, live, equity, profile, login, register |
| Template parts | `template-parts/` | Shared header, calendar widget, inline styles |

## Key Data Structures / Domain Objects

- **WP User** — stores influencer account. User meta keys: `ihq_id_token`, `ihq_access_token`, `ihq_refresh_token` (OAuth tokens, plaintext), `_ihq_country`, `_kick_username`, `_kick_broadcasting_schedule`.
- **`challenge` CPT** — one post per challenge invitation. Post meta: `_challenge_token` (lookup key), `_invitee_email`, `_invitee_first_name`.
- **`live_appearance` CPT** — one post per scheduled live event. Ownership via `post_author`.
- **`team_selection` table** — only custom DB table (`inc/teams-selection.php`), tracks team picks.
- **External API responses** — JSON from `https://02nvfvonol.execute-api.eu-west-2.amazonaws.com/qc`; shape varies by endpoint (challenges, equity, account, kick schedule).

## Important File Locations

| Path | Purpose |
|---|---|
| `functions.php` | Theme init, CPT registration, hook wiring, JS localization |
| `inc/api-ajax-calls.php` | All AJAX handlers (944 lines); main API proxy layer |
| `inc/influencer-auth-handler.php` | OAuth registration, login, token storage |
| `inc/email-verification-handler.php` | Email-first registration flow |
| `inc/influencer-role.php` | Role definition, capability map, admin redirect |
| `inc/teams-selection.php` | Custom DB table, admin UI for team picks |
| `page-portal-challenges.php` | Challenge list + detail templates (~1300 lines) |
| `page-portal-home.php` | Dashboard/home screen |
| `page-portal-login.php` | Login form, reads auth cookies |
| `page-portal-register.php` | Registration entry point |

## Request / Data Flow

**Login flow:**
1. User submits login form → `page-portal-login.php` POST → `influencer-auth-handler.php::ihq_login_ajax()`.
2. Handler authenticates against WP, then calls external API `/account/oauth/start-session` to refresh OAuth tokens.
3. Tokens stored/updated in WP user meta (`ihq_id_token`, `ihq_access_token`, `ihq_refresh_token`).
4. On success, sets auth cookie and redirects to portal home.

**Challenge API flow:**
1. JS on `page-portal-challenges.php` fires `wp_ajax_challenge_api` via fetch.
2. `inc/api-ajax-calls.php::challenge_api_ajax()` validates nonce, extracts `ihq_id_token` from user meta.
3. Decodes JWT payload (no signature verification) to extract `sub` claim.
4. Builds request to AWS API, forwards with `Authorization: Bearer {id_token}`.
5. Response (including a `_debug` object with JWT sub and request details) returned to client as JSON.

**Email verification flow (new registration):**
1. User submits email → AJAX handler sends verification email with signed token.
2. User clicks link → GET handler verifies token → creates WP user → initiates OAuth.
3. OAuth tokens written to user meta; user redirected to portal.

## External Dependencies

- **AWS API Gateway**: `https://02nvfvonol.execute-api.eu-west-2.amazonaws.com/qc` — all business logic endpoints.
- **WordPress core**: CPTs, user meta, AJAX framework, `wp_mail`.
- Standard WordPress plugin ecosystem expected (no custom plugins required by theme).

---

*This file was AI-drafted as a starting point. Refine, correct, and expand — the goal is a living document, not a one-shot deliverable.*


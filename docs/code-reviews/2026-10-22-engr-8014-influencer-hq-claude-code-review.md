---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-8014 IHQ login methods from influencerhq-api (PR #156)
date: 2026-10-22
---

# Code Review: ENGR-8014 IHQ login methods from influencerhq-api (PR #156)

Reviewed head: `6043fa978c54722e510ff45a3a19fe725bc781ad` against `main`, ticket ENGR-8014 (Jira body and comments read), contract EQ12 to EQ14 on the ENGR-7866 evaluation page, and influencerhq-api PR #87 (ENGR-8013) at `89e8f38`.

## Blockers

None.

## Warnings

- `template-parts/portal-auth-modal.php:375`: after a refusal, the "<method> isn't available right now" message can disappear while the method's controls stay hidden, so the visitor sees an empty login pane. `ihqAuthHideRefusedMethod` hides every `[data-ihq-channel-method="email"]` element for the rest of the page's life. The message, though, lives only in `#auth-login-err` / `#auth-login-code-err`, which other code clears:
  - Refused send, then the modal is closed and opened again: `openAuthModal('login')` → `switchAuthTab('login')` (line 737) → `ihqAuthLoginResetPanels()` empties and hides `#auth-login-err`. The login pane then shows no form and no message until the page reloads. With telegram off or no client id, that is the whole pane.
  - Refused verify, then **Back**: `ihqAuthLoginBackToEmail()` returns to the email step, where every control is hidden and `#auth-login-err` was never filled.
  - The same happens on Portal Login: a refused verify, then **Back** (`page-portal-login.php:758`), hides `#login-code-error` and shows an email step with nothing in it.

  EQ14 asks for the message to show while the method is hidden. **Fix:** keep the message somewhere the reset paths do not touch. For example, always render a hidden notice per page (the modal's `#auth-login-unavailable` already exists for the both-off case), mark it `data-ihq-channel-method-unavailable="<m>"`, and have `hideRefusedMethod` reveal it and set its text to `refusal.message` when it hides that method's controls. Add a `node --test` case for the reveal, and one for a reset after the refusal.

## Nits

- `template-parts/portal-auth-modal.php:199`: the "OR" divider is marked `telegram`, so it hides only when Telegram is refused. When email is refused mid-page with both shown, the divider stays above the Telegram button with nothing on its other side. The JS test pins this ("email is refused … Telegram stay"). **Fix:** hide the divider when either side is refused. For example, mark it `data-ihq-channel-method-divider` and hide it in `hideRefusedMethod` on any refusal, or wrap the email block and the divider together.
- `page-portal-login.php:382` (and `template-parts/portal-auth-modal.php:129`): Cloudflare's Turnstile `api.js` still loads when email is off, but no Turnstile widget renders then, because every Turnstile host sits inside the email-only markup. **Fix:** add `$ihq_portal_email_on &&` (modal: `$ihq_portal_auth_email_on &&`) to the condition around the script tag.

## Contract names

Each shared name the diff touches is spelled exactly as listed: `GET /v1/channels/methods` (`inc/ihq-channel-methods.php:29`, appended to `INFLUENCER_API_BASE`, which is `IHQ_API_BASE_URL` = `…/<stage>`), `method_disabled` (`inc/ihq-channel-methods.php:44`, `js/ihq-channel-methods.js:17`), `{"methods":null}` (absent feature) and `communication-methods` (comments only). These match ENGR-8013 at `89e8f38`. `serverless.yml` there adds `GET /v1/channels/methods` with no authorizer, so the theme's unauthenticated server-to-server GET is correct, and `src/handlers/channels/methods.js` answers `{"methods":{…all eight}}` or `{"methods":null}`, which is the shape `ihq_channel_methods_parse_body` expects.

## Ticket fit (Done when)

- PHP and `node --test` tests cover a method on, a method off, an absent feature and a failed call: yes. `tests/ihq-channel-methods.test.php` covers the reader, the cache and the last good answer. `tests/channel-method-ajax-refusal.test.php` runs 12 handler cases × 4 states. `js/ihq-channel-methods.test.js` covers the client.
- The AJAX handlers refuse a method that is off on the server: yes, all eight email-code and Telegram-widget handlers, each checked after the nonce. The registration handlers check the method that actually delivers the code (Telegram DM or email), which matches how `telegram_user_id` is stored only on the Telegram path.
- The theme reads no GrowthBook: yes. The only GrowthBook mentions are comments.
- With an absent feature, the modal and Portal Login render the same as on `main`, apart from the added `data-` attributes.
- Cache: one answer for 30 s in a transient. A failed call uses the last good answer, or today's lists when there is none, as the ticket says.
- Scope: EQ13's evidence names only the modal, Portal Login and the Telegram handler. The lander, home and home-claude forms are refused on the server but not hidden, as the PR states. I checked that their `fetch(...).then(r => r.json())` handlers do show `data.message` on a 403.

## Checks run

- `npm run test:js` (node v24, host): 755/755 pass.
- All 37 `tests/*.test.php` suites in `php:8.2-cli` (docker): all pass.
- `php -l` on every touched PHP file: no syntax errors.
- `python3 scripts/test-channel-methods-mutations.py`: 69/69 mutants killed.
- GitHub CI on the head commit: `JS tests` and `PHP tests` both pass.
- Contract cross-check against influencerhq-api PR #87 (ENGR-8013) at `89e8f38`.
- Not run: ESLint and stylelint (no `node_modules` in this checkout), phpcs/WPCS (not installed), a live WordPress or browser run, and the real influencerhq-api route. `npx tsc --noEmit` does not apply, because the repo has no TypeScript.

## Outside this PR

- `inc/aicoach-register.php:298`: the AI Coach register REST route still emails a sign-in code to an existing influencer (`ihq_issue_and_send_login_code_for_influencer`) with no email-switch check. With email off, the visitor gets an email whose code `ihq_verify_login_code` then refuses, and Portal Login shows "Email isn't available right now". The ticket scopes the AJAX handlers and leaves the AI Coach out, so this is an epic-level decision, not a defect here.
- `inc/email-verification-handler.php:8`: `send_verification_email` (the registration link by email) has no switch check. The PR names it as out of scope.
- End-to-end timing: influencerhq-api keeps an answer 30 s per Lambda container (`CACHE_TTL_MS`, `src/utils/communication-methods.js` on ENGR-8013), and the theme keeps it another 30 s. A switch can therefore take up to 60 s to reach an IHQ page, before any GrowthBook SDK refresh delay inside influencerhq-api. That is right at the Story's "within 1 minute", so QA should measure it.

## Summary

This is a careful, well-tested change. The reader is small and pure apart from the WordPress transport and storage calls, fails open exactly as the ticket asks, and is cached and logged sensibly. The handler gates sit after the nonce and before Turnstile. With an absent feature the rendered markup is unchanged. The contract names and the response shape match influencerhq-api's route, and the mutation script kills every mutant. The one real gap is client-side and only shows when a switch flips while a page is open: the refusal message is held only in error boxes that the modal's reopen path and the Back buttons clear, which leaves an empty login pane until the page reloads. Recommendation: **revise**. The warning is a small client-side fix (keep a persistent notice and add a test for it), and after that the PR is ready to merge. The two nits are optional. Nothing here blocks the deploy order in the DEPLOYMENT NOTE.

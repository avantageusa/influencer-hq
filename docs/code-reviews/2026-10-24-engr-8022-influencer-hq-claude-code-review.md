---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-8022 influencer-hq PR 158, IHQ login code on every verified method behind ihq_login_code_fan_out_enabled (second independent review)
date: 2026-10-24
---

# Code Review: ENGR-8022 influencer-hq PR 158, IHQ login code on every verified method

Reviewed head: f8818d2 (branch `feat/ENGR-8022-login_code_fan_out`, base `main`). This is a fresh review of the whole diff `origin/main...f8818d2`, not only the commits added since the first review (2026-10-23, at c4b3d43).
Spec: the ENGR-8022 Jira body as updated on 2026-10-24 (What changes, Done when, Design and ordering, DEPLOYMENT NOTE) and all of its comments.
Contract checked against influencerhq-api `feat/ENGR-8005-ihq_server_messages` @ 37436fc (PR #85): `src/handlers/ihq/messages.js`, `messageRequest.js`, `requests.js`, `constants.js`, `routes.js`, `serverKey.js`, `responses.js` and `serverless.yml`.

## Blockers

None.

## Warnings

- `inc/login-code-fan-out.php:142`: while the option is on, every answer carries `chat_app_hint`, including the answer to a send that D3 kept off the chat apps (`:392`). Each send also deletes the previous pending code before it mints a new one (`inc/email-verification-handler.php:1270-1273`). Take a second send 45 to 59 s after the first (the 45 s email throttle has passed), or the sixth send in an hour. The new code goes by email only. The chat app still holds the previous code, which no longer works. The code step still says "If you've connected a chat app, the code is there too." An influencer who follows the hint types the old code and gets "Invalid or expired code. Request a new one". QA will test exactly this path for Done-when 4 ("a second send within 60 seconds … sends the email only"). The same happens when the option is on but `IHQ_SERVER_API_KEY` is missing, although that is a configuration error. **Fix:** settle it with Product under PQ1 and record the choice on ENGR-8022 before QA. There are two options. (a) Use copy that stays true when a chat app holds an older code. For example: "If you've connected a chat app, the code is there too. If you asked for a new code, use the newest one." (b) Leave `chat_app_hint` out of the answer when the send skipped the fan-out. To do that, `ihq_issue_and_send_login_code_for_influencer()` reports whether the fan-out ran, and the AJAX handler adds the hint only then (and on the unknown-address path, as now). Option (b) reveals nothing new. A registered address already gets "Please wait a moment before requesting another code" (`inc/email-verification-handler.php:1260-1267`) when it asks again quickly, and an unknown address never gets that answer.
- `page-portal-login.php:471`: `resumePendingLoginFromCoach()` (`:769-797`) also opens Portal Login's code step, for an existing influencer who registered through AI Coach. That code comes from `ihq_issue_and_send_login_code_for_influencer()` and is fanned out. The resumed step never calls `setLoginChatAppHint()`, so this way into Portal Login's code step shows no hint. The ticket asks for the hint on every login page that has a code step, and Portal Login is the first one it names. The thread reply defers this to Product: a hint in the AI Coach register answer, given only for existing accounts, would break that answer's same shape for new and existing emails. That reason does not apply to this page. The hint depends only on the option, so Portal Login can render it itself and the AI Coach answer stays as it is. **Fix:** print the hint into Portal Login's script from PHP. For example, add `var IHQ_LOGIN_CHAT_APP_HINT = <?php echo wp_json_encode( ihq_login_code_fan_out_add_hint( array() )[ IHQ_LOGIN_CODE_FAN_OUT_HINT_KEY ] ?? '' ); ?>;`, or a small `ihq_login_code_fan_out_hint()` that `ihq_login_code_fan_out_add_hint()` also uses. Then call `setLoginChatAppHint(IHQ_LOGIN_CHAT_APP_HINT)` in `resumePendingLoginFromCoach()` before the code step is shown. `resetLoginPane()` already clears the hint.

## Nits

None.

## Earlier review threads, checked against the code

- **Email address in the logs** (CodeRabbit `:1316`, Steve Wolfe `:1325`): fixed. Both `send_login_code` lines log `(int) $user->ID` (`inc/email-verification-handler.php:1316`, `:1325`). The tests assert that the address is never logged, and two mutants restore the old lines. This changes one log line on the off path too. It is a log change from review, not a behaviour change.
- **When a failed email keeps the code** (first review `:1313`, Steve Wolfe `:290`): fixed as the 2026-10-24 ticket body says. `ihq_login_code_fan_out_may_have_delivered()` keeps the code for a `sent` method, a `WP_Error` (timeout or transport) or a status of 500 or higher. Any other 2xx, a 3xx and a 4xx delete the code and return today's error. Checked against #85 @ 37436fc: with `methods: "all"`, the providers are `found.connections.keys()` (`messages.js:145`), so an influencer with no chat app gets `{"results":[]}`. A 2xx whose methods all `failed` also deletes the code. The reply flags this as different from the first review's suggestion. It matches the ticket ("a 2xx with no `sent` method"). In practice the route's own 10 s channel timeout can never come back inside the 3 s wait.
- **Hint on Lander, the new home page and Portal home mr claude** (first review): fixed. Each page sets `d.chat_app_hint` with `textContent` under the code step's intro line and clears it on reset. The AI Coach resume path on Portal Login is still missing (the second warning above).
- **Sixth send compared as a whole value** and **plan `ci` todo**: fixed.
- **Key read before the D3 slot** (Steve Wolfe `:392`): fixed (`inc/login-code-fan-out.php:386-394`). A missing or empty key takes no slot and makes no request (tested).
- **Duplicate switch parsing** (first review nit): deferred by agreement. No follow-up is recorded on ENGR-8022 yet (see Outside this PR).
- **Done-when "without blocking"** (Steve Wolfe's review body): the ticket now says the call waits at most 3 seconds.

## Checked and found correct

- **Off path, the state after deploy.** The option is read with a `false` default. Only `1`/`true`/`yes`/`on` (trimmed, any case), integer 1 or boolean true count as on. With the option off, `ihq_login_code_fan_out()` makes no request and takes no slot. Both AJAX answers are today's. The English email is byte-identical to the pre-PR body (tested against a copy of it). The don't-share line appears only while the option is on, as Done-when 1 requires. Done-when 1 is met.
- **Request contract with ENGR-8005 @ 37436fc.** `POST INFLUENCER_API_BASE . '/ihq/v1/messages'` is on the same API Gateway the theme already calls for `/minify` (influencerhq-api `serverless.yml`). The body is `{platformUsername: "influencerhq-wpu-<id>", methods: "all", kind: "one_time_code", text, locale}`. `x-api-key` comes from the new `IHQ_SERVER_API_KEY`, and the route matches the header name case-insensitively (`serverKey.js`). A `%06d` code matches `ONE_TIME_CODE_PATTERN` (`/^[0-9A-Za-z]{4,10}$/`). All seven locale tags match `LOCALE_PATTERN`. The result parsing matches `MESSAGE_RESULTS` (`sent`/`not_connected`/`switched_off`/`failed`). `requestId` and the `retryAfter` field are optional, and the PR never retries, so leaving them out is right.
- **Never blocks the email path.** The 3 s `timeout` bounds the whole cURL transfer, connect included, and `redirection => 0` keeps the key from following a redirect. `INFLUENCER_API_BASE` is validated as https and untrailingslashed (`inc/ihq-env.php:124-134`). No log line contains the code or the key. When the email went out, nothing the influencer sees depends on the call (tested for a transport error, 502, 403, failed methods and an empty list). Done-when 2 is met.
- **D3.** A per-influencer transient holds the send times of the last hour. The edges are tested at 59/60 s and at the 5th/6th send, and inside the window only the email goes. The 45 s email throttle is unchanged. The read-then-write race is documented. Done-when 4 is met in code (see the first warning for what the influencer sees).
- **Placeholder guard, not gated.** The login send refuses `ihq_is_placeholder_email()` addresses before it mints a code. The AJAX handler answers them like an unknown address, hint included. `@chat.invalid` local parts are UUIDs and cannot be guessed, and `@email_telegram.com` already fails `is_email()`. So the AI Coach path's new 400 for a stand-in reveals nothing practical. Done-when 5 is met.
- **Shared names.** `one_time_code` and `sendMessage` are spelled exactly. `login-code-fan-out` appears as this repo's file names. PHP identifiers use underscores, which a PHP name requires. `communication-methods` is not touched in code.
- **Docs.** README, `inc/ihq-env.php` and the module docblock match the two DEPLOYMENT NOTEs. The plan frontmatter has `name`, `overview` and `todos`, and every todo is completed.

## Checks run

- Read the ticket body and every comment (`acli jira workitem view ENGR-8022`), all PR threads and replies, and the full diff `git diff origin/main...f8818d2`. Read the surrounding handler code, `inc/aicoach-register.php`, `js/ihq-aicoach-events.js`, the resume path in `page-portal-login.php`, `inc/placeholder-email.php`, `inc/ihq-env.php`, `inc/portal-onboarding-switch.php` and `inc/ihq-url-minify.php`. Fetched the route files listed above with `gh api` at 37436fc.
- `php tests/login-code-fan-out.test.php`: 252 checks passed (local PHP 8.5.11).
- `npm run test:php`: every PHP suite passed.
- `npm run test:js`: 744/744 passed.
- `python3 scripts/test-login-code-fan-out-mutations.py` (Docker, `php:8.2-cli`): 71/71 mutants killed.
- `php -l` on the ten touched PHP files: no syntax errors.
- CI on f8818d2: "PHP tests" success, "JS tests" success.
- Not run: `npx tsc --noEmit` (a PHP theme, with no TypeScript in the diff), phpcs (not installed), the hint in a browser, and the on path against a deployed ENGR-8005.

## Outside this PR

- **Translations will not reach the login email or the hint.** `ihq_aicoach_filter_portal_locale()` (`inc/aicoach-register.php:487-512`) returns the site locale unless the visitor is logged in. The login send runs logged out. So when PO-3422's `.mo` files land, the code email and `chat_app_hint` stay in the site language, while the chat-app message follows `_ihq_aicoach_language`. PO-3422, or a follow-up, would need a `switch_to_locale()` to the influencer's saved language around the email build.
- **Duplicate switch parsing.** `ihq_login_code_fan_out_enabled()` repeats `ihq_portal_onboarding_enabled()` line for line. The author agreed to a follow-up Task, but none is recorded on ENGR-8022 yet.
- **A registered address can still be told from an unknown one** (pre-existing). A second request within 45 s gets "Please wait…" only for a registered address (`inc/email-verification-handler.php:1260-1267`). Response time differs too (`wp_mail`, plus up to 3 s for the fan-out while on).
- **Copy still says "email".** The verify step answers "Enter the 6-digit code from your email" (`inc/email-verification-handler.php:1416`). The code-step intros on `page-home.php:229`, `page-lander.php:724` and `page-portal-home-claude.php:590` say "Enter the code from your email." The code may come from a chat app. This belongs with PQ1's copy.

## Summary

The PR does what ENGR-8022 asks and ships off. With the option missing, the only live change is the placeholder guard. The request matches ENGR-8005's contract at 37436fc field for field. A failed email now keeps the code only while a chat app may have it, exactly as the updated ticket says. Failures are logged without the code or the key and never shown. The tests are thorough: 252 checks, and 71/71 mutants killed. Every earlier thread is fixed as its reply claims, except the duplicate-switch nit, which was deferred by agreement. Nothing blocks the off state that ships. Both warnings concern the on state that QA will test. Inside the D3 window, the hint points the influencer to a chat app that holds a superseded code. And Portal Login's AI Coach resume path shows no hint, although a page-side fix needs no change to the AI Coach answer. Recommended action: revise. Settle the first warning with Product under PQ1 (copy, or no hint when the fan-out was skipped), add the hint to the resume path, then merge.

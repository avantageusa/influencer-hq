---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-8022 influencer-hq PR 158, IHQ login code on every verified method behind ihq_login_code_fan_out_enabled
date: 2026-10-23
---

# Code Review: ENGR-8022 influencer-hq PR 158, IHQ login code on every verified method

Reviewed head: 81cb42b (branch `feat/ENGR-8022-login_code_fan_out`, base `main` @ c45459d).
Spec: the ENGR-8022 Jira body (What changes, Done when, Design and ordering, DEPLOYMENT NOTE) and all of its comments, including the author's request to check the kept-code reading.
Contract checked against influencerhq-api `origin/feat/ENGR-8005-ihq_server_messages` @ ed6e7ad (`src/handlers/ihq/messages.js`, `constants.js`).

## Blockers

None.

## Warnings

- `inc/email-verification-handler.php:1313`: while the option is on, a failed `wp_mail` keeps the code whenever the request was made. That includes a 2xx answer whose results show that no chat app has the code: every method `not_connected` or `switched_off`, or an empty `results` list. Most influencers have no chat app connected yet, so this will be the usual case when an email fails with the option on. The influencer is sent to a code step that has no code anywhere, and the 45 s throttle is set. Today the same influencer sees the email error and can retry. You asked review to check this reading (Jira comment, 2026-10-23). Keeping the code after a timeout, a transport error, a non-2xx or a `failed` method is right, because influencerhq-api may still deliver. Keeping it after a 2xx that leaves nothing to deliver to is not. **Fix:** have `ihq_login_code_fan_out_send()` return one of three states, for example `delivered_or_unknown` and `nowhere`, instead of a bool. `nowhere` means a 2xx whose results hold no `sent` and no `failed` entry. Handle `nowhere` like today's failure: delete the code and return the error. Keep every other state as the PR does now. The answer the influencer sees still never depends on the call when the email went out. Update the "nothing connected" case of the "email fails" loop in `tests/login-code-fan-out.test.php` to expect today's error, and add one case for an empty `results` list.
- `inc/login-code-fan-out.php:129`: `chat_app_hint` is shown only on Portal Login and the portal auth modal. Three other live page templates post `ihq_send_login_code` from their own login JS, have their own code step, and ignore the key: `page-lander.php` (Template Name: Lander, code step at :723), `page-home.php` ("new influencer home page", :228) and `page-portal-home-claude.php` ("Portal home mr claude", :589). The ticket says that while the option is on, the code step shows the hint, and QA needs it (PQ1). On these three pages, an influencer whose code reached only a chat app is not told so. **Fix:** in each of those three send-success handlers, set the hint from `d.chat_app_hint` under the code step's intro line, and clear it on reset, as the PR does in the other two. Or get Product to record on ENGR-8022 that only Portal Login and the auth modal show the hint, so QA does not file the gap as a defect.

## Nits

- `tests/login-code-fan-out.test.php:502`: `check( 'on, sixth send within an hour: answered as usual', $result['ok'], true )` checks a single key. The coding standards ask for whole-value comparison, as the neighbouring cases do. **Fix:** compare the whole `$result` with `array( 'ok' => true, 'signup_token' => 'token1', 'expires_minutes' => 15, 'message' => GENERIC )`.
- `docs/plans/2026-10-23-engr-8022-login-code-fan-out.plan.md:38`: the `ci` todo is still `in-progress`, but GitHub Actions "PHP tests" and "JS tests" both passed on 81cb42b. **Fix:** set it to `completed`.
- `inc/login-code-fan-out.php:106`: `ihq_login_code_fan_out_enabled()` repeats `ihq_portal_onboarding_enabled()` (`inc/portal-onboarding-switch.php`) line for line, with only the option name and on-values constant changed. A third switch would be a third copy. **Fix (optional, fine as a follow-up):** move the parsing into one helper, for example `ihq_option_is_switched_on( $option_name )`, and have both switches call it.

## Checked and found correct

- **Off path (the state after deploy).** The option is read with a `false` default, and only `1`/`true`/`yes`/`on` (trimmed, any case), integer 1 or boolean true count as on, as in `ihq_portal_onboarding_enabled()`. With the option off, `ihq_login_code_fan_out()` makes no request and takes no slot. The AJAX answers are today's. The English email is byte-identical to the pre-PR body; a test compares it with a copy of the old body. Done-when 1 is met.
- **Request contract with ENGR-8005.** `POST INFLUENCER_API_BASE . '/ihq/v1/messages'` sends `{platformUsername: "influencerhq-wpu-<id>", methods: "all", kind: "one_time_code", text, locale}` with `x-api-key` from the new `IHQ_SERVER_API_KEY`, not account-api's key. `kind` matches influencerhq-api's `MESSAGE_KINDS.ONE_TIME_CODE`. A six-digit code matches `ONE_TIME_CODE_PATTERN`. All seven locale tags match `LOCALE_PATTERN` and the ticket's language table. The result parsing matches `MESSAGE_RESULTS` (`sent`/`not_connected`/`switched_off`/`failed`, with `reason` only on `failed`). `requestId` is optional, and the PR never retries, so leaving it out is right.
- **Shared names.** `one_time_code` and `sendMessage` are spelled exactly as listed. `login-code-fan-out` appears only as this repo's file names (`inc/login-code-fan-out.php`, the test file and the mutation script), spelled exactly. `communication-methods` is not touched.
- **Never blocks or leaks.** The request has a 3 s timeout and `redirection => 0`, so the key cannot follow a redirect. `INFLUENCER_API_BASE` is already validated as https. Log lines never contain the code or the key. A transport error, a timeout, a non-2xx or a failed method changes nothing in the answer (tests). The PR explains why it uses a short blocking request rather than `'blocking' => false`, and the explanation is correct: WordPress's cURL transport runs `curl_exec` to the timeout either way and then drops the result.
- **D3.** A per-influencer transient holds the send times of the last hour. Gap and count boundaries are tested at 59/60 s and at the 5th/6th send. Inside the window only the email goes. The 45 s email throttle is unchanged. The read-then-write race is documented.
- **Placeholder guard.** It is not gated. The login send refuses `ihq_is_placeholder_email()` addresses before minting a code. The AJAX handler answers them like an unknown address, hint included, so the guard reveals nothing.
- **Kept code inside the D3 window or without the key.** Nothing carries the new code there: the previous fan-out's code was invalidated when this one was minted. So handling a failed email as today is correct, and the tests cover it.
- **Hint.** It depends only on the option and is added to the known and the unknown answers alike, so it reveals nothing about which emails are registered. Both UIs clear it on reset.
- **Documentation and deploy notes.** README, `inc/ihq-env.php` and the module docblock match the two DEPLOYMENT NOTEs on the ticket. The plan frontmatter has `name`, `overview` and `todos`.

## Checks run

- Read the ticket body and all comments (`acli jira workitem view ENGR-8022`), the full diff `origin/main...81cb42b`, the surrounding handler code, `inc/aicoach-register.php` (second caller), `inc/placeholder-email.php`, `inc/ihq-env.php`, `inc/portal-onboarding-switch.php`, and the ENGR-8005 route on influencerhq-api's branch.
- `php tests/login-code-fan-out.test.php`: all checks passed (local PHP 8.5.11).
- `npm run test:php`: every PHP suite passed. `npm run test:js`: 744/744 passed.
- `scripts/test-login-code-fan-out-mutations.py`: the Docker daemon is not running here, so I ran a copy with local `php` in place of `docker run php:8.2-cli`. 53/53 mutants killed.
- `php -l` on the six touched PHP files: no syntax errors.
- CI on 81cb42b: "PHP tests" success, "JS tests" success.
- Not run: `npx tsc --noEmit` (PHP theme, no TypeScript in the diff); phpcs (not installed); the hint in a browser; the on path against a deployed ENGR-8005.

## Outside this PR

- **Timing reveals registered emails.** For a registered influencer, the login AJAX waits on `wp_mail`. With the option on, it can also wait up to 3 s on the fan-out. An unknown address answers at once. The copy is identical, but the response time still tells the two apart. This is pre-existing (`wp_mail`), and the fan-out widens it. Turnstile limits bulk use. A follow-up could send after the response, for example with `fastcgi_finish_request()` or a shutdown hook.
- **Verify step says "email".** `ihq_handle_verify_login_code_ajax()` still answers "Enter the 6-digit code from your email", but once the fan-out is on the code may arrive in a chat app. This is a copy follow-up for Product.

## Summary

The PR does what ENGR-8022 asks, ships off, and is well tested and well isolated. The off path is today's behaviour apart from the placeholder guard. The request matches ENGR-8005's contract field for field. Failures are logged and never shown, and the D3 limit, the placeholder guard and the hint are correct and covered (53/53 mutants killed). There are no blockers. The two warnings both concern the on state that QA will test. First, when the email fails and influencerhq-api answers that no chat app is connected, the code is still kept, which leaves the influencer on a code step with no code. Second, three live login pages do not show the hint. Recommended action: revise. Fix the first warning, and fix the second or get Product to record that the hint stays on two pages only. The nits are optional.

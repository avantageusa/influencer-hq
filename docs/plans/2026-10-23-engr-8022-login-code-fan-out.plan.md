---
name: IHQ login code also goes to every verified chat app, behind ihq_login_code_fan_out_enabled
overview: >
  While the WordPress option ihq_login_code_fan_out_enabled is on, the IHQ login send also posts
  the code to influencerhq-api POST /ihq/v1/messages (ENGR-8005) for every verified method, at
  most once a minute and five times an hour per influencer. A failed email keeps the code when a
  chat app may carry it, and the code step says the code is in the influencer's chat apps too. Not
  gated: the login send never mails a placeholder address. With the option off or missing,
  everything else is today's behaviour. Out of scope: influencerhq-api, the channel-service
  grant, translations (PO-3422) and phone-number login (ENGR-8023).
todos:
  - id: switch
    content: "inc/login-code-fan-out.php: ihq_login_code_fan_out_enabled() reads ihq_login_code_fan_out_enabled like ihq_portal_onboarding_enabled (missing or anything but an explicit on value is off)"
    status: completed
  - id: rate-limit
    content: "Per-influencer transient of send times: none in the last 60 s and fewer than 5 in the last hour (D3), else the email goes alone"
    status: completed
  - id: client
    content: "POST {INFLUENCER_API_BASE}/ihq/v1/messages {platformUsername: influencerhq-wpu-<id>, methods: all, kind: one_time_code, text: code, locale} with x-api-key IHQ_SERVER_API_KEY, 3 s timeout; failures logged without the code, never shown"
    status: completed
  - id: login-send
    content: "ihq_issue_and_send_login_code_for_influencer(): refuse placeholder addresses; fan out after wp_mail; keep the code on a failed wp_mail while a chat app may have it (a sent method, a timeout or a 5xx), else today's error; translatable body, don't-share line while on"
    status: completed
  - id: ajax-and-ui
    content: "ihq_handle_send_login_code_ajax(): placeholder answers like an unknown address; chat_app_hint in the answer while on; Portal Login, the auth modal, Lander, the new home page and Portal home mr claude show it on the code step"
    status: completed
  - id: config-docs
    content: "IHQ_SERVER_API_KEY documented as optional in inc/ihq-env.php and README per-instance configuration"
    status: completed
  - id: tests
    content: "tests/login-code-fan-out.test.php covering off, on, failure, rate limit and placeholder; scripts/test-login-code-fan-out-mutations.py kills every mutant"
    status: completed
  - id: verify
    content: Run PHP and JS tests, php -l on touched files and the mutation script (71/71 killed after the review fixes)
    status: completed
  - id: ci
    content: GitHub Actions Tests (PHP and JS) green on the PR
    status: completed
  - id: review-fixes
    content: "Independent, CodeRabbit and Steve Wolfe reviews: keep the code only while a chat app may have it, the hint on the three other login templates, user ID instead of the email in both logs, the key read before the D3 slot, whole-value checks"
    status: completed
---

# [ENGR-8022] IHQ login code on every verified method

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-8022
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem

The IHQ passwordless login (`ihq_issue_and_send_login_code_for_influencer()`,
`inc/email-verification-handler.php`) sends the code by email only. Epic ENGR-7868 sends every
login code to every verified method. The send also mails any address `is_email()` accepts, which
includes the `@chat.invalid` / `@email_telegram.com` stand-ins that can never deliver.

## Approach

New module `inc/login-code-fan-out.php`, loaded from `functions.php` before the login handler:

- **Switch.** `ihq_login_code_fan_out_enabled()` reads the option the same way
  `ihq_portal_onboarding_enabled()` does: `1`, `true`, `yes`, `on` (any case, trimmed), integer 1
  or boolean true are on; a missing option or anything else is off.
- **Rate limit (D3).** One transient per influencer (`ihq_login_fan_out_<user id>`) holds the send
  times of the last hour. A fan-out goes only when none is younger than 60 s and fewer than 5 are
  younger than an hour; otherwise only the email goes. The 45 s email throttle is unchanged.
- **Request.** `POST INFLUENCER_API_BASE . '/ihq/v1/messages'` with
  `{platformUsername: ihq_referral_user_id(<id>), methods: "all", kind: "one_time_code", text: <code>, locale}`.
  `locale` maps the influencer's saved `_ihq_aicoach_language` to the BCP 47 tags of the ticket's
  language table (`zh` → `zh-CN`, `yue` → `zh-HK`, `ja` → `ja-JP`, ...), and is `en` when nothing is
  saved. The channel service falls back by subtag and then to English.
- **Key.** `x-api-key` is the new optional wp-config constant `IHQ_SERVER_API_KEY`, which holds the
  value of SSM `/<stage>/influencerhq-api/IHQ_SERVER_API_KEY` (ENGR-8008: "give the IHQ server the
  same value"). It is not `IHQ_INFLUENCER_API_KEY`, which is account-api's SSO key. When it is
  unset, the fan-out is skipped and logged, and the email still goes.
- **Not blocking the login.** The influencer's answer never depends on what the call answers: it
  uses a 3 s timeout, and every failure (transport error or timeout, non-2xx, a method answering
  `failed`) is logged without the code and never shown. It is a short blocking request rather than
  `'blocking' => false` because WordPress's cURL transport still runs `curl_exec` to the timeout
  when non-blocking and then drops the answer and every error (`Requests/src/Transport/Curl.php`,
  `process_response`), so it would wait as long and log nothing. influencerhq-api allows each
  channel call 10 s and keeps running after the 3 s cut-off, so a timeout here can still deliver.

Login send (`ihq_issue_and_send_login_code_for_influencer()`):

- A placeholder address is refused like an invalid one, so no code is minted or mailed. Not gated.
- After `wp_mail`, the fan-out runs while the option is on.
- A failed `wp_mail` keeps the code and answers as a success only while a chat app may have it:
  a method reports `sent`, or the outcome is unclear (a transport error or timeout, or a 5xx,
  after which influencerhq-api may still deliver). Every other answer means no chat app has it:
  a 2xx with no `sent` method (with `methods: "all"`, an influencer with no chat app gets an empty
  results list; only switched-off or failed methods count the same) and any 4xx (a key for another
  stage sends nothing). Then, as when nothing else carries the code (option off, no key, inside the
  D3 window), the failure is handled as today: the code is deleted and the error returned. When the
  email went out, the answer never depends on the call (Steve Wolfe's review of PR #158).
- `IHQ_SERVER_API_KEY` is read before the D3 slot is claimed, so a stage without the key spends
  none (Steve Wolfe's review).
- The body strings become translatable (`esc_html__`, text domain `influencer-hq`); in English the
  email with the option off is byte-identical to today's. While the option is on the email adds
  "This is your sign-in code. Do not share it with anyone."

Login AJAX (`ihq_handle_send_login_code_ajax()`):

- A placeholder address gets the same generic answer as an unknown one, so the new guard reveals
  nothing about which stand-ins exist.
- While the option is on, both answers carry `chat_app_hint`: "If you've connected a chat app, the
  code is there too." It depends only on the option, so it says nothing about the account. With
  the option off, the answer is exactly today's.
- Portal Login (`page-portal-login.php`) and the portal auth modal
  (`template-parts/portal-auth-modal.php`) show `chat_app_hint` on the code step, under its intro
  line, and hide it on reset. These are the two login surfaces ENGR-8014 also changes.

## Alternatives considered

- Reuse `IHQ_INFLUENCER_API_KEY`: that is account-api's SSO key, and sharing it with influencerhq-api
  would couple two services' secrets.
- `'blocking' => false`: same latency bound under cURL, but no failure could be logged (see above).
- Always keep the code on a failed `wp_mail` while the option is on, as the ticket's line reads
  word for word: inside the D3 window no chat app is sent this code, so it would send the
  influencer to a code step with no code anywhere. The ticket's reason ("because the other legs
  carry it") does not hold there.
- Keep the code only when a method answered `sent` (the first draft): a call that times out at 3 s
  while influencerhq-api still delivers would delete a code the chat apps then show, and the
  influencer's answer would depend on the call's result, which the Done-when rules out.
- Append the hint to `message`: both UIs show `message` on the email step, which is hidden as soon
  as the code step opens, so the hint would never be seen there.
- Show the don't-share line with the option off too: the ticket's Done-when requires the off path
  to behave exactly as today apart from the placeholder guard.

## Blast radius

- Off (the state after deploy): the only change is the placeholder guard. An influencer whose
  account email is a stand-in gets no code email, and typing such an address answers like an
  unknown one. Every other answer and email is unchanged.
- On: each login send may wait up to 3 s more for influencerhq-api. When the email fails and the
  answer says no chat app has the code, the influencer sees today's email error and can retry. The AI Coach register path
  (`inc/aicoach-register.php`) shares the login send, so it fans out and keeps the code the same
  way. It ignores `chat_app_hint`, which only the login AJAX adds.
- `page-lander.php`, `page-home.php` and `page-portal-home-claude.php` have their own copies of
  the login JS; each shows `chat_app_hint` on its code step and clears it on reset, as Portal Login
  does (independent review, PR #158).
- Open PR #156 (ENGR-8014) edits the same AJAX handler and both login templates near these lines;
  whichever merges second may need a textual merge.

## Notes

- DEPLOYMENT NOTE (adds to the ticket's): before turning the option on for an instance, define
  `IHQ_SERVER_API_KEY` in that instance's `wp-config.php` with the value of
  `/<stage>/influencerhq-api/IHQ_SERVER_API_KEY`.
- The rate limit is read-then-write on a transient, so two sends in the same instant can both pass.
  The 45 s email throttle has the same shape, and the channel service caps sends per subject.
- PQ1: Product may change the hint copy.

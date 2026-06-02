---
name: Add IP-based registration attempt throttle
overview: >
 Registration code requests were previously throttled only by email and for a short window.
 This change adds a per-IP throttle so one IP can attempt registration only once every 5 minutes,
 while keeping the existing email throttle in place. This plan documents the implemented update
 in both the repo source file and the mirrored temp deployment copy.
todos:
 - id: add-ip-throttle-window-constant
   content: Add a dedicated 5-minute registration IP throttle constant
   status: completed
 - id: add-client-ip-resolver
   content: Add helper to resolve best-effort client IP from Cloudflare/proxy/server headers
   status: completed
 - id: enforce-registration-ip-throttle
   content: Enforce per-IP transient throttle before issuing registration code
   status: completed
 - id: mirror-to-temp-staging-file
   content: Apply same throttle changes to FileZilla temp staging copy
   status: completed
 - id: verify
   content: Run tests, lint, and CI gate before merge
   status: blocked
---

# [NO-JIRA] Registration IP throttle

**Ticket:** N/A
**Drafted by:** Cursor (Codex 5.3)

## Problem
Registration code sending was rate-limited by email only (`45` seconds), which allowed repeated attempts from the same IP across different emails. This increased abuse risk for the registration endpoint.

## Reproduction (bugs only)
1. Request a registration code from one IP using email A.
2. Immediately request another registration code from the same IP using email B.
3. Observe request succeeds because the existing throttle key was email-based, not IP-based.

## Approach
- Added `IHQ_REG_IP_ATTEMPT_WINDOW_SECONDS = 300` in `inc/email-verification-handler.php`.
- Added `ihq_get_client_ip_for_rate_limit()` to resolve IP from `HTTP_CF_CONNECTING_IP`, then `HTTP_X_FORWARDED_FOR` (first value), then `REMOTE_ADDR`.
- Updated `ihq_handle_send_registration_code_ajax()` to check/set an IP transient key `ihq_reg_send_ip_<md5(ip)>` and block with a clear error when within 5 minutes.
- Kept the existing email-based `45` second throttle unchanged as an additional guardrail.
- Mirrored the same code changes to `C:\Users\User\AppData\Local\Temp\fz3temp-8\email-verification-handler.php`.

## Alternatives considered
- Store plain IP in transient key/value — rejected to reduce direct storage of raw IP strings.
- Replace email throttle entirely with IP throttle — rejected to preserve existing behavior and layered protection.
- Use persistent custom DB table for attempts — rejected as unnecessary complexity for a short TTL limit.

## Blast radius
- Touches registration-code send flow only (`ihq_handle_send_registration_code_ajax`).
- Could affect users behind shared IPs (office/mobile carrier NAT) by enforcing one registration send per 5 minutes per source IP.
- No behavior change to login-code send flow.

## Notes
- `verify` is marked blocked because full test/lint/CI run was not executed in this step.
- Lint for the edited repo PHP file previously reported no issues in editor diagnostics.

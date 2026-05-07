---
name: ElevenLabs Talk Now — distinct agent on portal-home-claude
overview: >
  Use a dedicated ElevenLabs ConvAI agent for “Talk Now - Executive Concierge” on page-portal-home-claude.php while
  keeping the existing default agent for page-portal-home.php and any caller that omits agent_id. Server enforces an
  allowlist on POST agent_id; client receives the claude agent id via wp_localize_script (ihqElevenLabs).
todos:
  - id: constants-allowlist
    content: Add IHQ_ELEVENLABS_AGENT_DEFAULT_ID + IHQ_ELEVENLABS_AGENT_PORTAL_HOME_CLAUDE_ID and ihq_elevenlabs_resolve_agent_id() allowlist in functions.php
    status: completed
  - id: signed-url-handler
    content: Wire ihq_elevenlabs_signed_url() to resolve agent_id via helper (reject arbitrary IDs)
    status: completed
  - id: localize-agent
    content: Expose agent_id_portal_home_claude on ihqElevenLabs localize next to elevenlabs-client enqueue
    status: completed
  - id: claude-fetch-body
    content: Append agent_id to Talk Now fetch POST body on page-portal-home-claude.php only
    status: completed
  - id: verify-other-callers
    content: Confirm page-portal-home.php (and other consumers) unchanged → default agent still used
    status: completed
  - id: verify-lint
    content: Lint touched PHP
    status: completed
---

# ElevenLabs Talk Now agent — portal-home-claude

**Ticket:** _(add Jira URL if tracked)_  
**Drafted by:** Cursor (Composer)

## Problem

“Talk Now - Executive Concierge” on **`page-portal-home-claude.php`** must use ElevenLabs agent **`agent_6201kqzp5qxbeycvq88e6hy4fwq1`**. **`page-portal-home.php`** (and any flow that does not send `agent_id`) must continue using the legacy agent **`agent_2401kn7brtx3fdn93j5f4mxh70fa`**. The signed URL is fetched via shared AJAX **`ihq_elevenlabs_signed_url`**, which previously hard-coded a single `agent_id`.

## Reproduction

_N/A — configuration / feature split._ Before change: all Talk Now callers received signed URLs for one agent only.

## Approach

1. **Named constants** in `functions.php` for both agent IDs (no magic strings at call sites).
2. **`ihq_elevenlabs_resolve_agent_id()`** reads optional POST **`agent_id`**, **`sanitize_text_field`**, and returns it only if it matches an explicit **`$allowed`** array; otherwise **`IHQ_ELEVENLABS_AGENT_DEFAULT_ID`**.
3. **`ihq_elevenlabs_signed_url()`** uses `ihq_elevenlabs_resolve_agent_id()` when calling ElevenLabs `get_signed_url`.
4. **`wp_localize_script( 'elevenlabs-client', 'ihqElevenLabs', … )`** adds **`agent_id_portal_home_claude`** so the theme does not duplicate the ID string in inline JS.
5. **`page-portal-home-claude.php`** Talk Now **`fetch`** body includes **`&agent_id=`** + encoded **`ihqElevenLabs.agent_id_portal_home_claude`**.
6. **`page-portal-home.php`** unchanged — omits `agent_id` → server chooses default.

## Alternatives considered

- **Hard-code only in PHP handler:** breaks requirement to vary by template without branching on unreliable caller hints (referrer).
- **Separate AJAX actions per page:** more duplication; single endpoint + allowlisted POST is smaller blast radius.
- **Trust raw POST agent_id without allowlist:** insecure (caller could request arbitrary agents).

## Blast radius

- **`ihq_elevenlabs_signed_url`** behaviour changes only when POST **`agent_id`** matches an allowlisted value; omitting or invalid values preserve legacy agent.
- ElevenLabs API key and proxy behaviour unchanged (still in `functions.php`).
- Any future template needing another agent: add constant + allowlist entry + localize key + POST from that template only.

## Notes

- ElevenLabs signed URL response shape unchanged for the client (`signed_url` in success payload).
- If credentials move to `wp-config.php`, keep agent IDs in theme constants or options per environment as needed.

# Luna data inventory (theme nickel)

Nickel summary of **what this WordPress theme already holds or can reach** that could be fed to Luna (AI concierge / Anam Sammy / ElevenLabs ConvAI). Not a wiring plan — a map of available context.

---

## Identity & session (WP user meta)

| Field | Meta / source | Notes |
|---|---|---|
| Name | `first_name`, `last_name`, `display_name` | Synced to IHQ via `/account/players/fullname` |
| Email | WP `user_email` | Braze `external_id` often = email |
| Handle | `_ihq_handle`, `platform_handle` | Portal username / social-ish handle |
| Location | `_ihq_country`, `_ihq_city`, `_ihq_timezone` | Also `ihq_oauth_country_iso`, `billing_country` |
| Avatar | `_ihq_avatar_url` | |
| IHQ GUID | `wp_influencer_guid` | Braze influencer id |
| OAuth tokens | `ihq_id_token`, `ihq_access_token`, `ihq_refresh_token`, `ihq_token_expires` | Needed to call QC API as the user |
| Game portal URL | `hq_game_url` | Per-user override |
| Gameplay video | `_ihq_gameplay_video_url` | |
| Reg flags | `registration_date`, `email_verified`, `challenge_type` | Legacy + welcome panel |

**Luna nick:** who they are, where they are, how to address them, whether they’re verified/logged in.

---

## Communication & social

| Field | Where |
|---|---|
| Comm prefs / handles | `_ihq_comm_prefs`, `_ihq_social_handles` (email, telegram…), `_ihq_comm_email`, `communication_username`, `communication_methods` |
| Marketing IDs (Braze / start-session) | `marketingWhatsAppId`, `marketingKakaoTalkId`, `marketingLineId`, `marketingTelegramId`, `marketingWeChatId` |
| LINE enrollment | consent attrs + optional `native_line_id` (Phase 1; webhook owns subscribe) |
| Social platforms (modal) | `social_handles_json` on visitor intent / Braze |

**Luna nick:** preferred channel, how to reach them, LINE/Telegram readiness.

---

## Taste / competitions (profile)

| Field | Meta |
|---|---|
| Celebrity picks | `_ihq_cel_movie_stars`, `_ihq_cel_music_artists`, `_ihq_cel_sports_icons` |
| Intl league team | `_ihq_intl_league_team` |
| Challenge type | `challenge_type` (registration / intent) |
| Competition ratings | lander `competition_ratings` → Braze `competition_ratings_json` |

**Luna nick:** what they care about competing in / fandom signal.

---

## Visitor intent (pre-login → Braze)

Cookie / verification payload becomes Braze attrs + event `visitor_registration_code_issued`:

- Full blob: `visitor_intent_json`
- Email, country (`Language`), `platform_handle`, `challenge_type`, `captured_from`, `gate_id`
- Comm + social handles, competition ratings
- `registration_code`, `test_registry_button_url`, `source_url`

**Luna nick:** why they showed up and what they selected before the account existed.

---

## Live ops data (theme CPTs / AJAX)

| Domain | What’s stored / proxied |
|---|---|
| Live appearances | CPT `live_appearance` — request / status / update / delete AJAX |
| Challenges | Create / list / details / join; private challenge invites; CPT `challenge` + invitee meta |
| Team picks | Custom table `team_selection` (`inc/teams-selection.php`) |
| Contact form | Posts to `concierge@influencerhq.co` |

**Luna nick:** schedules, open invites, team choices, support tickets.

---

## External QC API (authoritative product data)

Base: `INFLUENCER_API_BASE` → `…/qc` (Bearer `ihq_id_token`).

| Endpoint family | Useful for Luna |
|---|---|
| `/account/players/me` | Canonical player profile from Genius |
| `/account/players/fullname` | Name write-back |
| `/referral/user/{id}/link` | Their referral / widget URL |
| `/referral/user/{id}/equity/totals` | Equity by period + levels (`L1`–`L3`, `LIVE`, legacy `KICK`) — play $ and shares |
| `/rankings/*` | Challenges list/details/join/create |

Equity embed also loads game-portal `/external/equity` in an iframe (live UI, not theme-owned JSON).

**Luna nick:** live earnings, network levels, referral link, challenge standings — the “money & status” brain.

---

## Braze (CRM twin)

Theme already reads/writes Braze (`users/export/ids`, `users/track`). Overlaps WP + visitor intent; good secondary source for marketing IDs and registration journey if Luna sits closer to CRM than WP.

---

## Portal UX context (not DB, but feedable)

| Signal | Source |
|---|---|
| Current page / gate | Portal templates; registry gates (`ihq-registry-gates.js`) — Equity Attribution, Challenges, etc. collapsed until registered |
| Help / FAQ copy | Home Concierge Q&A, More accordion, Equity accordion (product education text) |
| Geo hint | Cloudflare `HTTP_CF_IPCOUNTRY` (home phone map) |
| Locale experiment | `<html lang>` override for ConvAI testing (`portal-header`) |

**Luna nick:** “user is staring at X” + canned product answers already written in-theme.

---

## AI surfaces already in theme

| Surface | Role |
|---|---|
| ElevenLabs ConvAI | FAB / Talk Now — agents for logged-in vs guest (`functions.php`) |
| Anam avatar proxy | `inc/anam-proxy.php` — session token + persona preview (“Sammy” Executive Coach); used on POC |

Neither currently gets a rich user-context payload from WP — that’s the gap Luna would fill.

---

## What *not* to treat as Luna gold

- OAuth tokens in prompts (use server-side tool calls only).
- Hardcoded Braze keys / API secrets in repo config.
- Kick schedule meta (product path removed from portal UI; API level may still exist).
- Compression backups / lander marketing pages (noise unless asked).

---

## Feed priority (if building a Luna context pack)

1. **Identity** — name, handle, country, email channel prefs  
2. **Money** — equity totals + period + referral link (QC API)  
3. **Activity** — open challenges, live appearance requests  
4. **Intent** — visitor_intent / competition ratings / celeb picks  
5. **Page** — which portal screen + which gate they’re hitting  
6. **FAQ snippets** — More / Equity / Home copy as retrieval corpus  

---

*Drafted from theme inventory (`inc/`, portal templates, ARCHITECTURE.md). Refine as Luna’s tool schema lands.*

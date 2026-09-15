# Agent instructions

Environment-specific values (API Gateway base, game portal base, API keys, Turnstile, ElevenLabs) are **never** written into theme source. They come from `wp-config.php` constants via `inc/ihq-env.php` — use `ihq_env_get()` / `ihq_env_require()` or the derived `INFLUENCER_API_BASE` / `ihq_get_hq_game_portal_base_url()`. A new instance needs its wp-config block (README, "Per-instance configuration") before the theme is deployed to it.

Before doing any work in this repo, read `.cursor/rules/coding-standards.mdc`. That file is the canonical coding-standards document for both human and AI contributors — follow the conventions and workflows it describes.

When editing portal theme files that are mirrored in FileZilla’s temp staging folder, update **both**: (1) repo `template-parts/…` **and** (2) **`C:\Users\User\AppData\Local\Temp\fz3temp-6\`** — flat files there (e.g. `portal-styles.php`, **`portal-footer.php`**) are upload copies; WordPress still loads from **`template-parts/portal-footer.php`** on the server — copy the same file into that path when deploying.

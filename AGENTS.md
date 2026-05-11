# Agent instructions

Before doing any work in this repo, read `.cursor/rules/coding-standards.mdc`. That file is the canonical coding-standards document for both human and AI contributors — follow the conventions and workflows it describes.

When editing portal theme files that are mirrored in FileZilla’s temp staging folder, update **both**: (1) repo `template-parts/…` **and** (2) **`C:\Users\User\AppData\Local\Temp\fz3temp-6\`** — flat files there (e.g. `portal-styles.php`, **`portal-footer.php`**) are upload copies; WordPress still loads from **`template-parts/portal-footer.php`** on the server — copy the same file into that path when deploying.

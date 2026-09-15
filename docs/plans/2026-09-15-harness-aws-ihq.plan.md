---
name: AWS and IHQ harness modes
overview: QC portal sign-in bridge with exact-origin, nonce-bound SSO handoff. Implement the user-approved complete integration on separate follow-up branches.
todos:
  - id: implement
    content: QC portal sign-in bridge with exact-origin, nonce-bound SSO handoff
    status: completed
  - id: verify
    content: Run focused tests, mutation checks and relevant build or syntax checks
    status: completed
  - id: publish
    content: Publish a separate PR linked to the companion integration PRs
    status: completed
---

# AWS and IHQ harness modes

**Ticket:** Not supplied.
**Drafted by:** Codex (GPT-6)

## Approach

Preserve the anonymous AWS path and approved compact rows. IHQ uses the real QC
portal sign-in, its SSO-code exchange, and bearer requests through influencerhq-api.
Keep tokens in memory, validate popup source/origin/state, isolate attempts by mode
and account, and never fall back to AWS. Require explicit own-account grants for
subject creation and derive platform identity in the trusted Cognito issuer.
The mutable custom:userId attribute is not an authority. No provider expansion.

## Blast radius

QC portal sign-in bridge with exact-origin, nonce-bound SSO handoff. Existing harness PR #32 and Terraform PR #15 remain separate.
No merge, deployment or real provider message is part of this implementation.
No new runtime dependencies are planned; reuse existing SDKs and build tools.

## Verification

PHP bridge boundary tests and theme/bridge syntax checks passed in PHP 8.3 Docker. All 8 targeted boundary mutants were detected. Harness tests exercise rejected popup messages and one-use request state. Full WordPress sign-in and live two-account QC acceptance remain pending release configuration.

## Published PRs

- [Harness and notification ownership #34](https://github.com/avantageusa/notifications-service-api/pull/34)
- [Cognito account claim #150](https://github.com/avantageusa/cognito-triggers/pull/150)
- [IHQ portal bridge #26](https://github.com/avantageusa/influencer-hq/pull/26)
- [IHQ gateway contract #53](https://github.com/avantageusa/influencerhq-api/pull/53)

All target main. App PR #32 merged during implementation; TF PR #15 remains separate.

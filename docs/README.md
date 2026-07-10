# Together Clinic — project documentation

Read in this order:

1. **`BUILD-BRIEF-v3.md`** — the current plan. One plugin, one entry point, review-first / pay-after. Staged phases, one PR each, with paste-ready prompts. **Start here.**
2. **`eligibility-review.md`** — the code review both briefs are grounded in. Every phase's "read this first". Line references are from Eligibility v1.1.5 / Reorder v1.0.6; re-verify against current code before editing.
3. **`BUILD-BRIEF-v2.md`** — superseded by v3. Kept as the record of the clinical decisions (dose ladders, ±1 rule, switching matrix — all still binding, restated in v3 §3) and of the authorise/capture payment model v3 replaced.

## Current state

- The plugins live in this repo under `wp-content/plugins/` and deploy to Kinsta automatically on merge to `main` (GitHub Actions; the run's *Verify deployment* step prints the live plugin versions).
- Complete: v2 Phase 1 (housekeeping, 1.1.6/1.0.7) → v3 Phases 1a+1b (prescriber review gate + pay-link lifecycle, 1.2.0/1.1.0) → v3 Phase 2 (dose module: canonical ladder, reorder ±1 gate, switching matrix, 1.3.0/1.2.0) → v3 Phase 3 (plugins merged: the reorder plugin is now the `reorder/` module inside Together Clinic Eligibility Checker **2.0.0**; the standalone plugin is a self-deactivating shell).
- Next up: v3 Phase 2.5 (authorise-at-submission payment timing — blocked on the owner's Stripe connection) and Phase 4 (screen-0 router).

## Working rules

- One phase per session, one PR per phase, in v3's order.
- British English in all patient-facing copy.
- Bump the plugin version on every code PR (it is the cache-bust — load-bearing).
- Order meta via WC_Order CRUD only (HPOS-safe).
- After every merge, check the deploy run output to confirm the new version is live.

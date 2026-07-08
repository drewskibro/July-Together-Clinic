# Together Clinic — project documentation

Read in this order:

1. **`BUILD-BRIEF-v3.md`** — the current plan. One plugin, one entry point, review-first / pay-after. Staged phases, one PR each, with paste-ready prompts. **Start here.**
2. **`eligibility-review.md`** — the code review both briefs are grounded in. Every phase's "read this first". Line references are from Eligibility v1.1.5 / Reorder v1.0.6; re-verify against current code before editing.
3. **`BUILD-BRIEF-v2.md`** — superseded by v3. Kept as the record of the clinical decisions (dose ladders, ±1 rule, switching matrix — all still binding, restated in v3 §3) and of the authorise/capture payment model v3 replaced.

## Current state (as of v3 landing)

- Both plugins live in this repo under `wp-content/plugins/` and deploy to Kinsta automatically on merge to `main` (GitHub Actions; the run's *Verify deployment* step prints the live plugin versions).
- v2 Phase 1 (privacy & data-integrity housekeeping) is complete and deployed: Eligibility **1.1.6** / Reorder **1.0.7**.
- Next up: v3 Phase 1a (order-at-submit + `awaiting-review` + prescriber notifications). Prerequisite: staging site.

## Working rules

- One phase per session, one PR per phase, in v3's order.
- British English in all patient-facing copy.
- Bump the plugin version on every code PR (it is the cache-bust — load-bearing).
- Order meta via WC_Order CRUD only (HPOS-safe).
- After every merge, check the deploy run output to confirm the new version is live.

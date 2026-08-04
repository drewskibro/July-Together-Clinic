# Together Clinic — Build Brief v3: One Plugin, Review First, Pay After

**Supersedes** `BUILD-BRIEF-v2.md` (kept in this folder as the record of the clinical decisions and the v2 architecture it replaced).
**Pairs with** `docs/eligibility-review.md` (the code review — the evidence base every phase should read first).
**Written from** the merged website repo at `main` after the v1.1.6 / v1.0.7 releases (Phase 1 of v2 complete and deployed to production, verified by deploy run #32).

---

## 0. How to use this document

- Plugins live at `wp-content/plugins/together-clinic-eligibility/` and `wp-content/plugins/together-clinic-reorder/`. File references are relative to those folders unless noted.
- **Work one phase per session, in order. Each phase is one pull request.** Paste the phase's PROMPT block into a fresh Claude Code session pointed at this repo. Every prompt is written to be self-contained, but read `docs/eligibility-review.md` for the deep detail.
- Line references were verified against v1.1.6 / v1.0.7. Every prompt instructs the session to re-verify against current code before editing.
- Deploys are automatic: merge to `main` → GitHub Actions → SCP to Kinsta. The workflow's *Verify deployment* step prints the live plugin versions — after every merge, check the run output to confirm the release landed. "Is the fix actually live?" must never again be a matter of guesswork.

### Global constraints (every phase)

1. **British English** in all patient-facing copy.
2. **Extend, don't rewrite** the published assessment/reorder wizard screens. Their question flow and copy are live and patient-tested; v3 changes what happens *around* and *after* them.
3. **Bump the plugin version on every PR** that touches plugin code. The version string is the JS/CSS cache-bust — it is operationally load-bearing, not cosmetic (a stale cached `eligibility.js` has caused production failures before).
4. **Order meta via WC_Order CRUD only** (`$order->update_meta_data()` + `$order->save()`), never `update_post_meta()`. The codebase is HPOS-safe and must stay so.
5. **The prescriber is the safety barrier.** All dose logic proposes and flags; it never hard-blocks a patient. Under v3 this is structural: no treatment order can be paid for, let alone dispatched, before a prescriber decision.
6. **No custom payment-gateway code.** v3 needs none: payment is an ordinary, full, customer-present Stripe payment via WooCommerce core's order pay page. (The v2 requirement to enable Stripe manual capture is **withdrawn** — no Stripe configuration change is needed at all.)

---

## 1. What changed since v2, and why v3 exists

### 1.1 Already done (do not rebuild — verified live in production)

The whole of v2 Phase 1 shipped as Eligibility **1.1.6** / Reorder **1.0.7**:

1. Clinical summary gated to admin/clinician emails only (`$sent_to_admin` check).
2. Reorder webfonts self-hosted (no patient IPs to Google).
3. Cookie-store rehydration from the `raw_payload` JSON column (no more silently dropped clinical fields).
4. Order-attach idempotency guard upgraded to identity-based (`assessment_id` comparison).
5. Reorder purge cron (`TC_Reorder_Cron`, daily, `tc_reorder_retention_days`) + reorder `uninstall.php`.
6. Cookie-clear scope aligned with the JS (`path=/`, host-only); reorder fallback URLs unified onto `TC_Checkout::reorder_url()`.

Also done: the Kinsta deploy workflow ships both plugins (not just the theme) and verifies live versions on every run.

### 1.2 The two strategic decisions v3 encodes

**Decision A — Review first, pay after.** v2 taxed every phase with payment-limbo machinery: Stripe manual capture, a 7-day authorisation expiry, partial capture on dose reduction, void-on-reject, a safety-net cron. All of that existed only because payment happened *before* the prescriber decision. v3 flips the order: the assessment submission itself creates the order; the prescriber reviews it; approval sends the patient a payment link for the *confirmed* dose; payment is an ordinary full capture. Rejection means no money ever moved. Dose adjustment means the order is edited *before* the link is sent. The limbo — and its machinery — ceases to exist.

**Decision B — One plugin, one entry point, one data path.** The two-plugin split has a documented cost (see §6, the SPWL lessons): duplicated cookie stores, DB classes, log classes, cron classes; two data key formats (`_tc_elig_*` / `_rrqr_*`); two returning-customer predicates that can disagree (the root cause of a months-long redirect-loop bug on a sibling project); every fix written twice. The dependency arrows already point one way — reorder reads eligibility's settings, prefills from eligibility's order meta, delegates pricing to eligibility's `TC_Variation_Map` — so v3 folds the reorder plugin **into** the eligibility plugin as a lane of one flow, incrementally, keeping the published screens.

### 1.3 What Decision A dissolves (deleted problems, not deferred ones)

| v2 problem | v3 outcome |
|---|---|
| Stripe manual capture config, 7-day expiry, safety-net cron, partial capture, void-on-reject (v2 Phase 2b) | **Gone.** No holds exist. |
| Switcher price paradox — cannot authorise a payment for a dose *range* | **Gone.** Prescriber fixes the exact dose before the pay link is generated. |
| Unapproved on-hold orders polluting the reorder dose baseline | **Gone.** Unapproved orders are unpaid (`awaiting-review`) and never enter the qualifying-status set. |
| Cookie/session → checkout pipeline fragility; cross-device total loss; 24h-cookie / ~48h-session / 30-day-purge lifetime mismatch (v2 Phase 5, most of it) | **Mostly gone.** The order is created server-side at submit with clinical meta attached at birth. The pay link arrives by email and works on any device, any day. |
| Cart/checkout tampering surface: add-to-cart gating, dose clamping at checkout, dual classic/blocks validation hooks (much of v2 Phase 3's enforcement) | **Gone for the treatment lane.** There is no cart. Dose rules run once, server-side, at order creation. |
| Cross-stamping: both plugins' attach handlers firing on every order's thank-you | **Gone.** Each lane creates and stamps its own order; nothing fishes data out of a shared session at checkout time. |

### 1.4 What v3 must still build (nothing here exists today)

From `eligibility-review.md`, verified against v1.1.6/1.0.7:

- **No dose restraint of any kind is live.** A patient on Mounjaro 2.5mg can buy 15mg today. Every switcher is silently sold the starter dose regardless of their declared current dose (`selectedDose` has no UI setter for switchers; the server defaults to starter).
- **No prescriber gate.** Orders flow straight to normal WooCommerce fulfilment.
- **The reorder plugin sends no email at all** (zero `wp_mail` calls); its promised "prescriber reviews every choice" escalation is dead UI (un-localised Calendly config, `href="#"` at `templates/wizard.php` ~234).
- **Two returning-customer predicates** that can disagree (`TC_Returning_Customer::is_returning()` — cached user-meta flag behind a kill-switch — vs `TC_Reorder_Prefill::for_user()['has_previous_order']` — live order query). Three separate `template_redirect` routing authorities.
- **Email-only account takeover**: submitting an assessment with any existing customer's email silently logs the browser into that account with a persistent auth cookie (`class-tc-account.php` ~21–24, reachable via the public `tc_eligibility_save` endpoint).
- **No WordPress privacy exporter/eraser** registered in either plugin, despite both holding special-category health data.
- The dose ladder exists only as array insertion order, **triplicated** (`class-tc-variation-map.php` ~10–28, `class-tc-reorder-pricing.php` ~112–130, `eligibility.js` ~335–337).

---

## 2. Target architecture

### 2.1 The flow

```
Patient
  │
  ▼
ONE entry page — screen 0: New / Switching / I've ordered before (log in)
  │
  ├── New / Switching ──────► existing assessment wizard screens (unchanged)
  │                                   │ submit
  └── Returning (logged in) ─► existing reorder check-in screens (unchanged)
                                      │ submit
                                      ▼
                    Server: eligibility/reorder rules evaluate
                    Server: DOSE MODULE proposes dose (±1 / switching matrix / starter)
                    Server: ORDER CREATED  →  status: awaiting-review
                            (clinical meta attached at birth; flags in _tc_review_flags)
                                      │
                                      ▼
                    Prescriber review queue (wp-admin)
                    ┌───────────────┼──────────────────┐
                 Approve        Adjust dose          Reject
                    │           (edit line item,        │
                    │            then approve)          ▼
                    ▼                              status: cancelled
            status: pending-payment               patient notified
            pay-link email to patient             (no money ever moved)
                    │
             patient pays (ordinary full Stripe capture, any device)
                    ▼
            status: processing ──► dispatch ──► completed
```

Unpaid pay-links: reminder email at ~48h; auto-cancel (and clinical-meta retention handling) at ~7 days.

### 2.2 Order status model

| Status | Meaning | Money state |
|---|---|---|
| `awaiting-review` (**custom status**, registered by the plugin) | Submitted, prescriber has not decided | None taken, none held |
| `pending-payment` (core) | Approved; pay link sent | None yet |
| `processing` (core) | Paid | Fully captured |
| `completed` (core) | Dispatched/fulfilled | Captured |
| `cancelled` (core) | Rejected by prescriber, or pay link expired | None ever taken |

**Why a custom status and not core `pending`:** WooCommerce auto-cancels unpaid `pending` orders after the stock-hold window (Products → Inventory → "Hold stock (minutes)", default 60). A clinical review queue must not be silently emptied by an inventory timer. `awaiting-review` is exempt from that mechanism; orders only become `pending` once the pay link goes out. A guard must prevent any treatment order leaving `awaiting-review` except via the explicit review actions.

**Dose-baseline rule (supersedes the v2 wording):** the reorder ±1 gate anchors on the most recent **paid** order — statuses `processing`/`completed` only. `on-hold` is dropped from the *gate's* qualifying set (an `awaiting-review`/unpaid order must never raise the ceiling). The prefill's convenience lookup may keep a wider set, but gate and prefill must read the status sets from one named place so the difference is deliberate and documented, not accidental.

### 2.3 One plugin

End state: `together-clinic-eligibility` (rename cosmetically later if desired) contains both lanes. The reorder plugin's classes move across as modules sharing ONE cookie-store/session layer, ONE DB access layer (both custom tables remain — they are the system of record), ONE log, ONE cron, ONE settings screen, ONE returning-patient predicate, ONE routing authority. `together-clinic-reorder` ends as an empty shell that deactivates itself (kept one release for safe rollback, then removed).

### 2.4 Email is now the revenue path

The approval email carries the payment link. Deliverability (SPF/DKIM/DMARC on the sending domain) is a **launch prerequisite** — see §5. All transactional sends should log to the existing `TC_Log` so a "patient says they never got the link" support case is diagnosable.

---

## 3. Locked clinical & product decisions (carried from v2 unchanged)

- **Reorder, same medication:** allowed = current dose, one step up, or one step down; clamped at ladder ends; never a multi-step jump.
- **Propose + flag, never block.** Out-of-range or unverifiable situations proceed to the review queue with a flag in `_tc_review_flags`; the prescriber is the backstop. (v3 makes this cheap: "proceeding" no longer means taking money.)
- **Current dose is anchored on order history, not self-report** — now specifically *paid* history (§2.2). Unverifiable baseline (guest order not linked, re-imported products) → proceed + flag, never refuse.
- **Switching starting doses:**

  | Coming off | Current dose | Proposed start |
  |---|---|---|
  | Mounjaro → Wegovy | 2.5–7.5mg | Wegovy 0.5–1.0mg |
  | Mounjaro → Wegovy | 10–15mg | Wegovy 1.7–2.4mg |
  | Wegovy → Mounjaro | ≤1mg (0.25/0.5/1) | Mounjaro 2.5mg |
  | Wegovy → Mounjaro | 1.7 / 2.4mg | Mounjaro 5mg |
  | Same drug, new provider | any | continue at declared current dose |

  Ranges: the system proposes the range; the prescriber sets the exact dose in the review queue *before* the pay link — so the patient is always charged a known price for a confirmed dose.
- **No maximum age on the reference order** — lapsed patients are never auto-blocked; the prescriber applies clinical judgement (the submission's `_tc_review_flags` should include the reference order's age so they can).
- New-to-treatment (non-switching) patients start on the starter dose (0.25mg Wegovy / 2.5mg Mounjaro).
- An in-wizard medication change on a reorder keeps routing to a fresh assessment.

---

## 4. Build sequence

| # | Phase | One-line goal | Blocked on |
|---|---|---|---|
| 0 | Docs in repo | This PR | — |
| 1a | Order-at-submit + `awaiting-review` + notifications | Every treatment order is born held, and a prescriber hears about it | staging site |
| 1b | Review queue actions + pay-link + reminders | Approve / adjust / reject actually work end-to-end | 1a |
| 2 | Dose module (ladder + ±1 + switching matrix) | The system proposes the clinically right dose and flags deviations | 1a (flags land in a queue that exists) |
| 2.5 | Payment timing: authorise-at-submission (Option B, §9) | Card held at submission, captured on approval; pay-link becomes the fallback | 2; owner's Stripe connection (test mode) |
| 3 | Fold reorder into the eligibility plugin | One plugin, one shared infrastructure | best after 2 |
| 4 | Screen-0 router, one predicate, one authority | One front door; the SPWL loop becomes impossible | 3 |
| 5 | Security & GDPR | Close the account takeover; privacy exporters/erasers; retention for unpaid orders | 1b (pay links remove the conversion excuse for auto-login) |
| 6 | Handoff doc + dead-code sweep | Record the contracts; delete the retired checkout plumbing | all above |

Phase 2 can start once 1a is merged (parallel with 1b if two sessions run). Phases 1a→1b are deliberately small; everything else leans on them.

---

## 5. Launch prerequisites & owner checklist (operational, not code)

**Site owner (Drew):**
- [ ] Create the Kinsta **staging** environment (first Pedro task; no payment-flow work on live).
- [ ] Protect `main` on GitHub (block force-push and deletion).
- [ ] Agree the **prescriber rota and SLA**. Under v3, review speed gates *payment*, not just dispatch — slow review is now measured in revenue, not only compliance.
- [ ] **SPF/DKIM/DMARC** verified for the transactional sending domain (`care@togetherclinic.co.uk` or final choice). The pay link lives or dies by inbox placement.
- [ ] Real **Mounjaro/Wegovy products + variation map** populated (an empty/stale map is the root cause behind the redirect loop and dose-derivation failures; several guards key off it).
- [ ] Checkout page cleanup: demo products removed, "Sales tax" → "VAT", block checkout branded. (The treatment lane stops using the checkout page for *entry*, but the order-pay page and any non-treatment purchases still render through Woo.)
- [ ] Archive the old `TogetherClinicEligibilityCheckerJune2026` repo once this PR lands (single source of truth).
- [ ] Decide who reviews Pedro's PRs before merge.
- [ ] **No Stripe configuration change is needed.** (v2's manual-capture toggle requirement is withdrawn.)

**Pedro, day 1 (facts to gather and record in the PR/issue for Phase 1a):**
- [ ] Live checkout type: block (`<!-- wp:woocommerce/checkout -->`) or shortcode?
- [ ] HPOS enabled? (WooCommerce → Settings → Advanced → Features — code is agnostic either way.)
- [ ] Live values of: `tc_eligibility_variation_map`, `tc_reorder_page_id`, `tc_eligibility_assessment_page_id`, `tc_reorder_enforce_login`, `tc_redirect_shop`, `tc_use_returning_check`, `tc_eligibility_enforce_assessment_before_checkout`, `tc_eligibility_block_direct_add_to_cart`.
- [ ] Does any CDN/security layer strip query strings? (Affects legacy `?force_assessment=1` behaviour until Phase 4 replaces it.)
- [ ] WooCommerce "Hold stock (minutes)" value (context for the `awaiting-review` design).
- [ ] Confirm live plugin versions match `main` (read the latest deploy run's *Verify deployment* output).

---

## 6. Guard rails from the SPWL project (why several rules below are non-negotiable)

A sibling project with the same two-plugin architecture produced months of firefighting. Its four failure modes all have live siblings in this codebase; each maps to a v3 rule:

1. **"Is the fix actually deployed?"** — SPWL's deploy pipeline failed silently; live ran stale code. → Here, every deploy run prints live plugin versions; check it after every merge. Version bumps are mandatory per code PR.
2. **Kill-switch uncertainty** (`spwl_use_new_returning_check`) — an off toggle silently reclassified every returning customer. → This repo has `tc_use_returning_check` plus ~17 other behaviour toggles. Phase 6's handoff doc must list every option with its default and failure mode; Pedro's day-1 checklist records the live values now.
3. **Two disagreeing "is this customer returning?" predicates** — the root cause of SPWL's redirect loop. → Phase 4 mandates ONE predicate, ONE routing authority, server-side loop-breaker state. No new code may introduce a second predicate in the meantime.
4. **Every fix written twice** — the two-plugin tax. → Phase 3 removes the duplication structurally. Until it lands, any fix touching shared behaviour must be checked against both plugins explicitly in the PR description.

---

## 7. Risk register (v3-specific, with mitigations)

| Risk | Mitigation |
|---|---|
| **Conversion drop-off** — payment deferred to a later email | Fast SLA (same-day target); reminder email at ~48h; high-intent audience. If reorder-lane drop-off proves material post-launch, revisit instant-pay *for reorders only* — the one lane where price is fixed and review is quick. Decide on data, not fear. |
| **Pay-link email lands in spam** | SPF/DKIM/DMARC prerequisite (§5); log every send; surface "link sent but unpaid after 48h" in the queue so a human can follow up. |
| **Woo auto-cancels unpaid pending orders** (stock-hold timer) | Custom `awaiting-review` status is exempt; orders become `pending` only when the link goes out; reminder/expiry handled by the plugin's own cron, deliberately. |
| **Review queue neglected** (orders stall unpaid) | Daily digest email of `awaiting-review` orders older than the SLA target; queue count badge in wp-admin. |
| **New order-creation code path** (regression risk) | It replaces a *more* fragile path (cookie → cart → checkout attach). Build on staging; keep the legacy checkout-attach path intact until Phase 6's sweep, so nothing is deleted before its replacement has survived production. |
| **In-flight patients at each deploy** | Each phase must state its migration note (e.g. Phase 1a: sessions already in the cart flow complete on the old path; the flip applies to new submissions only). |

---

## 8. The phases

### PHASE 1a — Order-at-submit, `awaiting-review`, prescriber notifications

**Goal:** every treatment submission (both lanes) creates a WooCommerce order server-side, born `awaiting-review` with the full clinical payload attached, and a prescriber is notified. No treatment order can progress without a decision. The patient-facing journey ends at "submitted — our prescriber will review and you'll receive a secure payment link", instead of a checkout redirect.

**Already exists (reuse, don't rebuild):** submission handlers with the validated payload in hand (`TC_Ajax::save()` ~51–118; `TC_Reorder_Ajax` save path); the order-attach writers that map payload → order meta (`TC_Checkout::attach_assessment_to_order()`, `TC_Reorder_Checkout::attach_assessment_to_order()`) — repoint them at the newly created order; `TC_Emails` (eligibility) as the pattern for the reorder notification; `TC_Variation_Map` for dose → variation resolution.

**Acceptance:**
- Submitting an eligible assessment creates an order: correct variation line item, billing/shipping from the payload, `_tc_eligibility_raw` + flat meta + `_tc_review_flags` (empty array placeholder) attached, status `awaiting-review`, linked back to the submissions table row.
- Same for a reorder submission (with `_rrqr_*` meta and previous-order link).
- Clinician email fires for BOTH lanes (reorder gains its first `wp_mail`); patient gets a British-English confirmation describing the review-then-pay-link process.
- A treatment order cannot be moved out of `awaiting-review` by anything except the (Phase 1b) review actions — guard hooked on status transitions.
- The old add-to-cart/checkout redirect no longer fires for new treatment submissions; legacy checkout-attach hooks remain in place but idle (removed in Phase 6).
- Dead escalation UI wired: `cfg.calendlyReturning` localised (`reorder.js` ~293 vs `class-tc-reorder-plugin.php` localize array ~225–248) and the `href="#"` at reorder `templates/wizard.php` ~234 replaced with the real booking URL.

> **PROMPT — Phase 1a**
>
> Read `docs/BUILD-BRIEF-v3.md` (§2, §8 Phase 1a) and `docs/eligibility-review.md` first. Plugins under `wp-content/plugins/`. Verify every file/line against current code before editing. British English; CRUD-only order meta (HPOS); bump both plugin versions; extend, don't rewrite, the published wizard screens.
>
> Implement review-then-pay step 1 in both plugins:
> 1. Register a custom order status `awaiting-review` (label "Awaiting prescriber review"), exempt from WooCommerce's unpaid-pending auto-cancel, included in wp-admin views and order counts.
> 2. In the eligibility submit handler (`TC_Ajax::save()`, after `TC_Eligibility_Rules::evaluate()` succeeds), create the order server-side: resolve the variation via `TC_Variation_Map` from `selectedTreatment`/`selectedDose`, set billing/shipping from the payload (reuse the mapping in `TC_Account::sync_woo_billing()` / the checkout prefill map), attach the full clinical meta by reusing `TC_Checkout::attach_assessment_to_order()` against this order, add an empty `_tc_review_flags` array meta, set status `awaiting-review`, back-link the order ID onto the submissions row (`TC_DB::attach_order`). Associate the order with the patient's user account where one exists. Return a success response that sends the wizard to a "submitted for review" outcome instead of the checkout redirect (adjust the final-screen copy minimally; do not restructure screens).
> 3. Mirror the same in the reorder submit path (`TC_Reorder_Ajax`), with `_rrqr_*` meta and `previous_order_id`.
> 4. Add a status-transition guard: treatment orders (identified by the plugins' marker meta) may leave `awaiting-review` only via the review actions (Phase 1b) — block other transitions with a logged admin error notice.
> 5. Prescriber notification for BOTH lanes on submission (the reorder plugin currently has no `wp_mail` at all — add a `TC_Reorder_Emails` mirroring `TC_Emails`): patient + contact, lane (new/switching/reorder), derived current dose where applicable, requested/selected dose, flags placeholder, and a direct link to the order edit screen. Patient confirmation email explains review-then-pay-link (British English).
> 6. Wire the dead escalation UI: localise the Calendly URL the reorder JS expects (`cfg.calendlyReturning`) and replace the hardcoded `href="#"` in the reorder wizard template with the configured booking URL.
>
> Migration note in the PR: in-flight sessions already holding a cart complete via the legacy path; the new path applies to submissions after deploy. Summarise and open a pull request.

---

### PHASE 1b — Review queue actions, pay link, reminders

**Goal:** the prescriber can approve (→ pay link), adjust dose then approve, or reject — from wp-admin — and unpaid links are chased and eventually expire.

**Already exists:** the admin metabox rendering `_tc_eligibility_raw` (`class-tc-order-admin.php`) — extend for `_rrqr_raw` + flags; WooCommerce core's "customer payment page" (order-pay endpoint) and "invoice / order details" customer email — the pay link needs **no custom payment code**.

**Acceptance:**
- Order actions on `awaiting-review` treatment orders: **Approve & send payment link** (→ `pending-payment`, triggers the invoice email containing the order-pay URL), **Adjust dose** (swap the line item to another dose on the canonical ladder — Phase 2 supplies the ladder; until then a variation picker — recalculate totals, then approve), **Reject** (→ `cancelled`, patient notified with British-English copy and the booking/escalation URL).
- Approval/rejection are logged (who, when) as order notes.
- The admin metabox shows eligibility and reorder clinical data and `_tc_review_flags` inline on the order.
- Reminder cron: `pending-payment` treatment orders unpaid after ~48h get one reminder email; unpaid after 7 days are cancelled with a "link expired — contact us to reorder" email. Timings filterable.
- Daily digest email to clinician recipients listing `awaiting-review` orders older than 24h (SLA nudge), plus an admin queue view (order-list filter link is sufficient).
- Paying via the link moves the order to `processing` (normal Woo) — confirm the status guard permits that transition.

> **PROMPT — Phase 1b**
>
> Read `docs/BUILD-BRIEF-v3.md` (§2, §8 Phase 1b) and `docs/eligibility-review.md` first. Phase 1a is merged — verify its code before building on it. British English; CRUD-only meta; bump versions; no custom payment-gateway code (use WooCommerce core's order-pay endpoint and customer invoice email for the pay link).
>
> Build the prescriber review actions and the pay-link lifecycle as specified in the brief's Phase 1b acceptance list: the three order actions with correct status transitions and order notes; the extended admin metabox (`class-tc-order-admin.php`) rendering `_rrqr_raw` and `_tc_review_flags` alongside the eligibility data; the reminder/expiry cron for unpaid `pending-payment` treatment orders (48h reminder, 7-day cancel, filterable); the daily `awaiting-review` SLA digest; and patient-facing emails for approval (with pay link), rejection, reminder and expiry — all British English. Ensure the Phase 1a transition guard allows exactly these action-driven transitions plus payment→processing. Summarise and open a pull request.

---

### PHASE 2 — Dose module: canonical ladder, ±1, switching matrix

**Goal:** the system proposes the clinically correct dose for every lane and flags every deviation, at the single enforcement point v3 created (order creation). This closes the two live clinical gaps: unrestrained reorder doses and switchers being sold the starter regardless of declared dose.

**Already exists:** current-dose derivation (`TC_Reorder_Prefill`), the check-in questions, the `TC_Reorder_Pricing` → `TC_Variation_Map` delegation seam, `_tc_review_flags` (Phase 1a).

**Acceptance:**
- ONE canonical, explicitly indexed ladder per treatment with `index_of()`/`step()` helpers, consumed everywhere; the three implicit ladders (`class-tc-variation-map.php` ~10–28, `class-tc-reorder-pricing.php` ~112–130, `eligibility.js` ~335–337) reference it; step order is never derived from the filtered variation map at runtime.
- Reorder: allowed = current, +1, −1; clamped at ends; anchored on the most recent **paid** order (`processing`/`completed` — the gate's status set is named, shared, and deliberately distinct from the prefill's wider set). Wizard UI renders only in-range doses (current tagged). Out-of-range/tampered requests are clamped at order creation and flagged (`dose_out_of_range: requested X, baseline Y, supplied Z`). Unverifiable baseline → proceed on the requested dose + `dose_unverified` flag. Gapped ladder (unpriced mid-rung) → step skips to next available + flag. Reference-order age recorded in the flags. Admin `?preview_reorder=1` synthetic path skips the gate.
- Switching: `propose_start_dose( from_drug, from_dose, to_drug )` implements §3's matrix; ranges propose the range's lower dose on the order and flag `switch_proposed: …` for the prescriber to adjust upward if warranted (they hold the Adjust action); same-drug switchers continue at declared dose; new-to-treatment keeps starter. The declared current dose informs the flag but never raises a reorder ceiling.
- Cross-medication reorders keep routing to a fresh assessment (unchanged).

> **PROMPT — Phase 2**
>
> Read `docs/BUILD-BRIEF-v3.md` (§3 clinical decisions, §8 Phase 2) and `docs/eligibility-review.md` (§0.3, Phase 4 review) first. Phases 1a/1b are merged — dose outcomes land on `awaiting-review` orders and flag via `_tc_review_flags`. British English; bump versions; propose + flag, never block; verify all line references against current code.
>
> Build the dose module per the brief's Phase 2 acceptance list: the canonical indexed ladder class shared by both plugins (retiring the three implicit ladders); the ±1 reorder gate anchored on the most recent paid order with clamping, gap-skipping, endpoint handling, unverifiable-baseline flagging, reference-age recording, in-range-only wizard rendering, and the preview-path skip; and `propose_start_dose()` implementing the switching matrix with range-lower + flag semantics, replacing today's unconditional starter default for switchers (`eligibility.js` ~18–19/~202; server default `class-tc-ajax.php` ~144). Enforcement lives at order creation (the Phase 1a path) — single point, no cart/checkout hooks needed. Summarise and open a pull request.

---

### PHASE 3 — Fold the reorder plugin into the eligibility plugin

**Goal:** one plugin, one shared infrastructure. Mechanical consolidation — no behaviour change is the acceptance test.

**Approach:** move the reorder classes/assets/templates into `together-clinic-eligibility` as a `reorder` module; collapse the duplicated pairs (cookie store, DB access, log, cron, emails) onto single shared implementations parameterised by lane; one settings screen; one text domain. `together-clinic-reorder` becomes a guard shell (deactivates itself if the host plugin is active) for one release, then is removed. All options, table names, meta keys and the `[tc_reorder_form]` shortcode keep their existing names — **no data migration, no key renames** (renames are churn that breaks in-flight patients; the review is explicit on this).

**Acceptance:** byte-identical patient-facing behaviour on both lanes; one plugin active; shared classes have exactly one implementation each; deactivating the shell plugin changes nothing; rollback path documented in the PR.

> **PROMPT — Phase 3**
>
> Read `docs/BUILD-BRIEF-v3.md` (§2.3, §8 Phase 3) first. This is a mechanical consolidation with NO behaviour change. Fold `together-clinic-reorder` into `together-clinic-eligibility` as a module: move classes/assets/templates; unify the duplicated cookie-store/DB/log/cron/email layers into single lane-parameterised implementations; keep ALL existing option names, table names, meta keys, shortcodes and AJAX action names unchanged (no data migration); leave `together-clinic-reorder` as a self-deactivating shell for one release with a clear readme note. Bump the host plugin version (and the shell's). The acceptance test is behavioural identity — document how you verified it (both wizards end-to-end on staging). Summarise and open a pull request.

---

### PHASE 4 — Screen-0 router: one entry, one predicate, one authority

**Goal:** a single front door routing new / switching / returning correctly, with exactly ONE returning-patient predicate and ONE routing authority — making the SPWL redirect loop structurally impossible.

**Key requirements (from the review + v2 Phase 6, updated):**
- Screen 0 before the consent screen: logged-in users routed by THE predicate; logged-out offered New / Switching / "I've ordered before → log in and continue". Keep consent + userType screens downstream unchanged.
- ONE predicate: `TC_Reorder_Prefill::for_user()['has_previous_order']` semantics (live, invalidatable), reconciled with the `_tc_returning_customer` flag (which is currently write-once, never deleted, and behind the `tc_use_returning_check` kill-switch). Logged-in users *without* a qualifying paid order go to new/switching — never bounced at a reorder page.
- ONE routing authority absorbing the three current `template_redirect` handlers (`TC_Checkout` ×2, `TC_My_Account::redirect_shop`) and the My Account CTA swaps; server-side state as the loop-breaker (the `?force_assessment=1` query param is strippable by CDNs — retire it); remove the unforced reorder→assessment link (`class-tc-reorder-plugin.php` ~74).
- Decouple the reorder lane's rendering from its page-level access control so screen 0 can embed/hand off to it without the shortcode guard bouncing guests. Preserve the safeguarding property: age is screened before any identity capture.

> **PROMPT — Phase 4**
>
> Read `docs/BUILD-BRIEF-v3.md` (§8 Phase 4) and `docs/eligibility-review.md` (§0.4, Part 2 Phase 1) first. Phase 3 is merged (one plugin). British English; extend, don't rewrite, the published screens; bump the version; verify line references.
>
> Build the unified entry per the brief's Phase 4 requirements: prepended screen 0; the single returning-patient predicate (live order-history semantics, with the legacy `_tc_returning_customer` flag reconciled and invalidatable rather than write-once); the single routing authority replacing the three `template_redirect` handlers and My Account CTA logic, with server-side loop-breaker state replacing `?force_assessment=1`; rendering/access-control decoupling for the reorder lane; and the age-before-identity ordering preserved. Document the routing decision table in the PR. Summarise and open a pull request.

---

### PHASE 5 — Security & GDPR

**Goal:** close the email-only account takeover; give the plugin proper privacy plumbing; define retention for the new unpaid-order clinical data.

**Requirements:**
- **Stop auto-logging into existing accounts** (`class-tc-account.php` ~21–24). v3 removes the conversion excuse: the patient doesn't need a session to pay — the link arrives by email. On email-match: link the assessment to the account server-side (pointer meta), do NOT set auth cookies, and tell the patient the order will appear in their account when they log in normally. Keep auto-*creation* of new accounts (passwordless, welcome email suppressed) but stop auto-authenticating those too unless the account was created in this same request. Keep the reorder lane strictly login-first. The age screen stays before any account creation. Handle "email already registered" copy gracefully (British English). Magic-link/password-reset for legacy passwordless accounts: coordinate with the site owner; a standard WooCommerce password-reset email is the minimum viable path.
- **Register WordPress privacy exporters/erasers** for both custom tables and the plugins' order meta / user meta (special-category health data; currently none exist).
- **Retention for unpaid clinical orders:** cancelled/expired `awaiting-review`/`pending-payment` orders carry full clinical meta; define and implement a purge/anonymisation window consistent with the existing `tc_*_retention_days` options.
- Verify the existing submissions-table purges still align now that orders are created earlier (a row with an order attached must not purge while its order is merely awaiting review).

> **PROMPT — Phase 5**
>
> Read `docs/BUILD-BRIEF-v3.md` (§8 Phase 5) and `docs/eligibility-review.md` (§0.6, Part 3 items 2–3) first. This is security and privacy work on the unified plugin. British English; bump the version; verify line references.
>
> Implement the brief's Phase 5 requirements: remove silent auto-login into existing accounts (and into auto-created accounts outside the creating request) while keeping server-side assessment↔account linking and the age-before-identity ordering; graceful "email already registered" handling; WordPress privacy exporter + eraser coverage for both custom tables, plugin order meta and user meta; a retention/anonymisation pass for cancelled and expired clinical orders consistent with the existing retention options; and a check that submissions-table purges respect rows attached to orders still awaiting review. Do not weaken the returning-customer reconciliation. Summarise and open a pull request.

---

### PHASE 6 — Handoff documentation + dead-code sweep

**Goal:** record the system's contracts and delete what v3 retired, so the codebase ends smaller than it started.

- Write `docs/plugin-handoff.md`: module map of the unified plugin; every option (name, default, behaviour, failure mode when toggled — the SPWL kill-switch lesson); the `_tc_review_flags` contract (writers: dose module, switching; reader: review queue); the canonical ladder; the single predicate + routing authority; the order lifecycle (§2.2); the email catalogue; both DB tables' schemas and retention; what remains of the legacy contracts (`rrqr_data`, shared page-ID options) and what was retired.
- Sweep: remove the now-idle treatment-lane checkout plumbing (add-to-cart AJAX for the treatment lane, checkout enforcement redirects, checkout prefill for lane-created orders — verify nothing else consumes each before deleting), the `?force_assessment=1` remnants, and the reorder shell plugin.
- Update `docs/README.md` and the staging checklist to the v3 flow.

> **PROMPT — Phase 6**
>
> Read `docs/BUILD-BRIEF-v3.md` (§8 Phase 6) and the merged code. Write `docs/plugin-handoff.md` covering everything the brief lists (modules, all options with defaults and failure modes, `_tc_review_flags`, the ladder, predicate + routing authority, order lifecycle, email catalogue, table schemas + retention, retired vs live legacy contracts). Then perform the dead-code sweep the brief describes — for each removal, verify nothing still consumes it and note the verification in the PR. Bump the version. Summarise and open a pull request.

---

## 9. Decisions log (so nobody relitigates them)

| Decision | Status |
|---|---|
| **Payment timing (site owner decision, post-v3):** authorise-at-submission for ALL lanes (Option B) | **Adopted** — supersedes the two rows below. The patient's card is authorised (held, not charged) at submission; prescriber approval captures; rejection releases the hold. Switchers are authorised at the matrix-proposed dose; if the prescriber adjusts to a *more expensive* dose, the hold is released and the existing pay-link machinery serves as the automatic fallback. Requires WooCommerce Stripe "issue an authorization on checkout" + the prescriber SLA well inside Stripe's 7-day hold window. Implemented as **Phase 2.5** (after the dose module; needs the owner's Stripe connection to test). |
| v2's Stripe authorise/capture model | ~~Dropped~~ **Partially reinstated by the Option-B decision above** — but on the v3 chassis (order exists at submission; the review gate is unchanged), without v2's partial-capture machinery |
| Pay links for ALL lanes at launch | ~~Adopted~~ **Superseded** — pay-link machinery is retained as the fallback path (upward dose adjustments, expired holds) |
| One plugin (reorder folds into eligibility) | **Adopted** (Phase 3) |
| Custom `awaiting-review` status vs core `pending` | **Custom** (stock-hold auto-cancel exemption) |
| Dose baseline statuses | **Paid only** (`processing`/`completed`) for the gate; named shared constant; prefill set may stay wider, deliberately |
| Key/table/option renames during unification | **Forbidden** (no `tc_clinical` rename; review's evidence) |
| Blocks checkout hook | Keep `woocommerce_store_api_checkout_update_order_from_request` for any residual paths; do **not** switch to `…_order_processed` |
| Clinical rules (±1, switching matrix, no max reference age, propose+flag) | **Locked** (§3, unchanged from v2) |

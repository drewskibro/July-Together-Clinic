# Together Clinic — Unified Eligibility & Reorder: Build Brief v2

**Supersedes the original "Unify Eligibility (New / Switching) with Reorder" brief.**
Pairs with `eligibility-review.md` (the code review — keep both in the repo under `docs/`).

This version is written *from the actual codebase* (reviewed at the old plugins repo HEAD `2396f56`, Eligibility v1.1.5 / Reorder v1.0.6) and incorporates the site owner's clinical, payment, and product decisions. Where the original brief would have rebuilt working code or baked in a wrong assumption, this one corrects course.

---

## 0. How to use this document

- **Plugins now live at** `wp-content/plugins/together-clinic-eligibility/` and `wp-content/plugins/together-clinic-reorder/` (merged into the website repo). All file references below are relative to those plugin folders unless noted.
- **Work one phase per session, in order.** Each phase is one pull request. Paste the phase's **PROMPT** block into a fresh Claude Code session pointed at the merged website repo.
- **Step 0 for every session:** make sure `docs/eligibility-review.md` and this file are in the repo so the session can read the evidence. Each prompt is written to be self-contained, but the review has the deep detail.
- Line numbers are from the pre-merge code; a merge is a file copy so they should still hold, but **every prompt instructs the session to verify against current code before editing.**

### Global constraints (apply to every phase)
1. **British English** in all patient-facing copy.
2. **Extend, don't rewrite** the existing published assessment/reorder wizard screens.
3. **Bump both plugins' version numbers** on every PR and note changes (the version string is also the JS cache-bust — it's load-bearing, not cosmetic).
4. **Live checkout is the WooCommerce Block (Store API).** Keep the existing classic hooks too — the code is dual-mode and must stay that way.
5. **Order meta via WC_Order CRUD only** (`$order->update_meta_data()` + `$order->save()`), never `update_post_meta()` — the store is HPOS-safe and must stay so.
6. **The prescriber is the safety barrier.** All dose logic *proposes and flags*; it never hard-blocks a sale. Every treatment order is authorised (not captured) and held for prescriber sign-off before dispatch.

---

## 1. Confirmed decisions (the answers that drive the build)

### Clinical
- **Reorder, same medication:** allowed selections are the current dose, one step up, or one step down only. Never a multi-step jump (no 2.5mg → 15mg).
- **Dose-gate philosophy:** *propose + flag, never block.* Out-of-range selections don't refuse the sale — they clamp to the safe dose and flag the order for the prescriber.
- **"Current dose" is anchored on order history, not self-report.** A patient's selection drives the clinical check-in but must not raise the safety ceiling (a bypassed form could otherwise claim a high current dose to unlock a big jump). Derive current dose from the most recent qualifying order (statuses `completed/processing/on-hold` — the same set the prefill already uses).
- **Edge case — current dose can't be verified** (guest order not linked to the account; products re-imported so old orders no longer map): **still take payment and let the order through.** The prescriber-review step is the backstop. Flag it for review; do not block.
- **Switching starting doses** (first Together Clinic order for someone moving provider/medication):

  | Coming off | Current dose | Proposed start |
  |---|---|---|
  | Mounjaro → Wegovy | 2.5–7.5mg | Wegovy 0.5–1.0mg |
  | Mounjaro → Wegovy | 10–15mg | Wegovy 1.7–2.4mg |
  | Wegovy → Mounjaro | ≤1mg (0.25/0.5/1) | Mounjaro 2.5mg |
  | Wegovy → Mounjaro | 1.7 or 2.4mg | Mounjaro 5mg |
  | Wegovy → Wegovy (same drug, new provider) | any | continue at declared current dose |
  | Mounjaro → Mounjaro (same drug, new provider) | any | continue at declared current dose |

  Where a range is shown, the system **proposes** it and the **prescriber confirms** the exact point. All switching orders are auto-sold (authorised) and held for prescriber sign-off before dispatch.
- **No maximum age** on the reference order — a returning patient is never auto-blocked for having been away; the prescriber handles clinical judgement on lapsed patients.

### Payment (Stripe, manual capture)
- **Model: Authorise on checkout, capture on approval** ("Issue an authorization on checkout, and capture later" in the WooCommerce Stripe settings). No custom payment code.
- **Status mapping:** order placed → **On hold** (authorised, funds held, not taken) = *awaiting prescriber review*. Prescriber **approves** → set to **Processing** → Stripe captures → dispatch. Prescriber **rejects** → **Cancel** → authorisation voided, hold released, no money taken. Prescriber **reduces dose** → partial capture of the lower amount (allowed once; difference released).
- **Hard constraint:** capture within **7 days** or the authorisation expires and funds release. Requires a prescriber SLA well under 7 days plus a safety-net job (Phase 2) that surfaces orders approaching the limit.

### Technical (from the review)
- **Keep the existing data pipeline; extend it.** ~60% of the original brief's "Phase 2" already works (session write, dual checkout hooks, CRUD/HPOS meta, idempotency markers, session clearing).
- **Do NOT switch the blocks hook** to `woocommerce_store_api_checkout_order_processed`. The code already uses `woocommerce_store_api_checkout_update_order_from_request`, which is the better fit.
- **Reader chain:** `WC()->session` → **user-meta pointer** (logged-in) → cookie pointer → **DB rehydration from the `raw_payload` column**. (The cookie only ever holds a UUID; the DB is the real store.)
- **Keep the ExtendSchema registration** — it feeds prefill to the block checkout.

---

## 2. Build sequence

| # | Phase | Why here | Blocked on |
|---|---|---|---|
| 1 | Privacy & data-integrity housekeeping | Safe, unblocked, real value (incl. a live GDPR leak) | nothing |
| 2 | Prescriber-review gate + Stripe auth/capture | The keystone; legally required; everything leans on it | nothing (decisions locked) |
| 3 | Reorder dose gate (propose + flag) | Feeds the review gate | Phase 2 |
| 4 | Switching-dose conversion | Feeds the review gate | Phase 2 |
| 5 | Data-pipeline hardening | Ensures clinical data reliably reaches the order/prescriber | nothing (independent) |
| 6 | Screen-0 router | Consolidates routing; kills the redirect-loop root cause | best after 5 |
| 7 | Login-before-capture (security) | Closes email-only account takeover | needs password-reset/magic-link plan |
| 8 | Handoff documentation | Records the cross-plugin contracts | after the above land |

Phases 3 and 4 can run in parallel after 2. Phase 5 is independent and can be pulled forward if the orphaned-session bug is biting in production.

---

## 3. The phases

Each phase below gives the goal, what already exists (so nothing is rebuilt), what to build, acceptance criteria, and a paste-ready prompt.

---

### PHASE 1 — Privacy & data-integrity housekeeping

**Goal:** knock out isolated, low-risk fixes that need no clinical or architectural decisions — including a live GDPR leak — without touching the patient journey.

**Already exists / don't touch:** the wizard screens, dose logic, checkout hooks.

**Build:** the six fixes in the prompt (each its own commit).

**Acceptance:**
- Clinical summary appears only in admin/clinician emails, never customer-facing ones.
- No external font request from the reorder page.
- Order clinical meta contains every field (no silent drops) after a session-lost rehydration.
- A re-taken assessment overwrites the earlier one on a block draft order.
- Reorder health-data rows are purged on a schedule and on uninstall.
- Stale cookies actually clear; only one reorder fallback URL exists.

> **PROMPT — Phase 1**
>
> You're working on two WordPress plugins in this repo, `together-clinic-eligibility` and `together-clinic-reorder` (find them under `wp-content/plugins/`). First read `docs/eligibility-review.md` for context. Make the following isolated fixes, **each as its own commit**, bump both plugins' version numbers, keep all patient-facing copy in British English, and **do not** touch the assessment/reorder wizard screens or any dose logic. Verify each file/line against the current code before editing.
>
> 1. **Stop leaking health data into customer emails.** The eligibility clinical summary is injected into *all* WooCommerce order emails because the `woocommerce_email_order_meta` handler never checks `$sent_to_admin` (`class-tc-emails.php` ~121–144, hooked in `class-tc-checkout.php` ~25). Register/handle the hook so the summary is included **only** in admin/clinician-facing emails.
> 2. **Self-host the fonts.** The reorder plugin enqueues Google Fonts from `fonts.googleapis.com` (`class-tc-reorder-plugin.php` ~169–174). Download those font families into the plugin's assets and enqueue them locally; remove the external request.
> 3. **Stop silently losing clinical fields on rehydration.** `TC_Cookie_Store::hydrate_from_row()` (`class-tc-cookie-store.php` ~119–175) drops `prevWeights`/`bariatricRecent` and mangles `otherConditionsList`, though the DB row has a complete `raw_payload` JSON column. Rehydrate from `raw_payload` (JSON-decode, fall back to columns).
> 4. **Make the order-attach idempotency guard identity-based.** Both attach writers skip if the marker meta already exists (`class-tc-checkout.php` ~224–226; `class-tc-reorder-checkout.php` ~66–68), so a *re-taken* assessment never replaces the first one stamped on a block draft order. Compare the stored `assessment_id` to the current one and overwrite if different.
> 5. **Fix reorder data retention.** `TC_Reorder_DB::purge_stale()` exists but nothing calls it, and the reorder plugin has no `uninstall.php`. Schedule the purge on cron (mirror the eligibility plugin) and add an `uninstall.php` for the reorder plugin.
> 6. **Fix cookie-clear scope + fallback URLs.** The JS sets the cookie with `path=/` while `clear()` uses `COOKIEPATH`/`COOKIE_DOMAIN` (`class-tc-cookie-store.php` ~65) — align them. And unify the two reorder fallback URLs (`/reorder/` in `class-tc-checkout.php` ~92 vs `/reorder-now/` in `class-tc-my-account.php` ~77) onto the configured `tc_reorder_page_id`.
>
> When done, summarise each change and open a pull request.

---

### PHASE 2 — Prescriber-review gate + Stripe authorise/capture (the keystone)

**Goal:** no prescription medicine ships without prescriber sign-off, and money is only taken for orders that ship. This is the safety and compliance backbone; Phases 3–4 feed flags into it.

**Already exists:** the eligibility plugin emails a clinician at assessment time (`TC_Emails`). Order meta already carries the full clinical payload (`_tc_eligibility_raw`, `_rrqr_raw`, flat keys).

**Missing / build:**
- Turn on Stripe **manual capture** (config, documented in the PR): authorise on checkout, capture later.
- A dedicated **"Awaiting prescriber review"** concept mapped to WooCommerce **On hold** (which is where Stripe auth-only orders already land). Treatment orders must not auto-progress past it.
- A **prescriber notification** for BOTH lanes — the reorder plugin currently sends none (no `wp_mail` anywhere in it). Notify with: patient + contact, lane (new/switching/reorder), current dose (derived), requested dose, and any flags.
- An **approve / reduce-dose / reject** action for the prescriber (order-admin UI action buttons and/or a simple review queue): approve → set Processing (captures) → dispatch; reduce → partial capture; reject → cancel (voids auth) → notify patient.
- A **7-day safety-net cron**: surface/escalate orders whose authorisation is near expiry so a decision is forced before funds release.
- Prescriber sees the clinical data inline on the order (the admin metabox already renders `_tc_eligibility_raw`; extend for reorder `_rrqr_raw` + the new flags).

**Acceptance:**
- A placed treatment order is authorised, not captured, and sits at On hold; Stripe shows an uncaptured PaymentIntent.
- Prescriber approval captures and moves to Processing; rejection voids the hold with no capture; dose reduction partial-captures the lower amount.
- Both lanes notify the prescriber with the clinical data + dose + flags.
- Orders never auto-progress to dispatch without an approval action.
- The safety-net job lists orders within (say) 48h of the 7-day expiry.

> **PROMPT — Phase 2**
>
> Read `docs/BUILD-BRIEF-v2.md` (§1 Payment) and `docs/eligibility-review.md` first. Plugins are under `wp-content/plugins/`. Build a prescriber sign-off gate for prescription-medicine orders across both `together-clinic-eligibility` and `together-clinic-reorder`. British English; CRUD-only order meta (HPOS); block checkout is live but keep classic paths; bump versions.
>
> **Payment model (Stripe manual capture):** we authorise on checkout and capture on prescriber approval. Assume the WooCommerce Stripe setting "Issue an authorization on checkout, and capture later" is enabled (note this in the PR as a required config step). Do NOT write a custom gateway. Map the workflow onto order status: authorised order = **On hold** = *awaiting prescriber review*; **approve** → set the order to **Processing** (this triggers Stripe capture) and let dispatch proceed; **reject** → **Cancel** the order (voids the authorisation, releases the hold); **reduce dose** → adjust the order so the captured amount is the lower dose's price (Stripe allows a single partial capture). Never let a treatment order auto-progress past On hold without an explicit approval action.
>
> Build:
> 1. A clear **"Awaiting prescriber review"** state (use On hold, or a custom status that behaves like On hold for Stripe capture — justify your choice) and a guard so treatment orders can't be moved to Processing except via the approval action.
> 2. A **prescriber notification** for BOTH lanes (the reorder plugin currently sends no email at all — add one mirroring `TC_Emails`). Include patient + contact details, lane (new/switching/reorder), derived current dose, requested/selected dose, and a placeholder for the Phase 3/4 flags (design the flag meta key now, e.g. `_tc_review_flags`, even though the flags are populated later).
> 3. Prescriber **actions** on the order-admin screen: **Approve & capture**, **Reduce dose & capture** (partial), **Reject & void**, each with the correct status transition and a patient notification on approve/reject. Extend the existing admin metabox (`class-tc-order-admin.php`) to render the reorder `_rrqr_raw` data and any review flags alongside the eligibility data.
> 4. A **daily safety-net cron** that finds On-hold treatment orders whose Stripe authorisation is within 48h of the 7-day expiry and surfaces them (admin notice/email) so a decision is forced before funds release.
>
> Also fix the dead escalation UI the review notes: localise the reorder Calendly URL (`cfg.calendlyReturning`, `reorder.js` ~293, absent from the localize array at `class-tc-reorder-plugin.php` ~225–248) and the hardcoded `href="#"` at `templates/wizard.php` ~234, so a patient told "our prescriber reviews every choice" has a real booking path.
>
> Summarise the design (status flow, capture/void/partial paths, the config toggle required) and open a pull request.

---

### PHASE 3 — Reorder dose gate (propose + flag, never block)

**Goal:** guide reorder patients to a clinically safe dose (current ±1 within the same medication) without ever refusing a sale — out-of-range requests clamp to safe and flag the prescriber.

**Already exists:** current-dose derivation from order history (`TC_Reorder_Prefill`), the reorder check-in questions, the dose ladders (as array order in three places).

**Missing / build:**
- ONE canonical, explicitly-indexed dose ladder per treatment, consumed by both plugins (extend the existing `TC_Reorder_Pricing` → `TC_Variation_Map` delegation), retiring the triplicated implicit ladders.
- Server-side ±1 enforcement anchored on the **derived** current dose (not the patient's selection): allowed = current, +1, -1, **clamped** at ladder ends.
- **Never block:** if a (tampered or stale-baseline) request is out of range, clamp to the nearest allowed dose, proceed, and write a flag to `_tc_review_flags` for the prescriber.
- The reorder wizard UI renders **only** in-range doses (current tagged), so normal patients can't select out of range.
- Enforcement on both add-to-cart and checkout, on **both** checkout types — classic validation hooks don't fire on the Store API, so add block-side validation (`woocommerce_store_api_validate_cart_item` / a RouteException) as well.
- Skip the gate for the admin `?preview_reorder=1` synthetic-prefill path.

**Acceptance:**
- A patient on 5mg is offered only 2.5/5/7.5mg; a bypassed request for 15mg results in an order clamped to 7.5mg with a prescriber flag, not a refusal.
- Endpoints clamp (no step-down from the lowest, no step-up from the highest) without erroring.
- The gate reads a single indexed ladder; the old triplicated ladders are gone.
- Enforcement holds on the block checkout, not just classic.

> **PROMPT — Phase 3**
>
> Read `docs/BUILD-BRIEF-v2.md` (§1 Clinical) and `docs/eligibility-review.md` first. Plugins under `wp-content/plugins/`. Build the reorder dose guardrail in `together-clinic-reorder`. British English; bump versions; block checkout is live.
>
> **Rule:** for a reorder of the SAME medication, allowed doses are the current dose, one step up, or one step down, clamped at the ends of the ladder. "Current dose" MUST be derived from the patient's most recent qualifying order (statuses completed/processing/on-hold — reuse the prefill's set), NOT from their form selection. The selection can inform the check-in but must never raise the ceiling.
>
> **Philosophy — propose + flag, never block:** every reorder is authorised and held for prescriber review (Phase 2), so this gate never refuses a sale. If a request is out of the ±1 band (only reachable by tampering or a changed product baseline), clamp to the nearest allowed dose, let the order proceed, and record a flag in the order meta `_tc_review_flags` (e.g. `dose_out_of_range: requested X, current Y, supplied Z`) for the prescriber.
>
> Build:
> 1. **One canonical indexed ladder per treatment** (an ordered array of dose strings with an `index_of()`/`step()` helper), consumed by both plugins. Extend the existing `TC_Reorder_Pricing` → `TC_Variation_Map` delegation (`class-tc-reorder-pricing.php` ~8–19). Retire the triplicated implicit ladders (`class-tc-variation-map.php` ~10–28, `class-tc-reorder-pricing.php` ~112–130, `eligibility.js` ~335–337) by having them reference the one source. Do NOT derive step order from the filtered variation map at runtime.
> 2. **UI:** the reorder wizard renders only in-range doses (current tagged), instead of every configured dose (`reorder.js` ~310–360).
> 3. **Server enforcement** at both add-to-cart (`TC_Reorder_Ajax::save`/`add_to_cart`) and checkout. Classic checkout validation hooks do NOT run on the Store API, so add block-side validation too (`woocommerce_store_api_validate_cart_item` or a RouteException from the Store API checkout hook). Clamp + flag out-of-range; keep the price/authorised amount consistent with the (possibly clamped) dose.
> 4. Handle the **gapped ladder** case (a mid-ladder dose with no configured/priced product): decide and document whether "one step" skips to the next available dose or holds — default to skipping to the next available and flagging.
> 5. Skip the gate for the admin `?preview_reorder=1` path (`class-tc-reorder-plugin.php` ~81–95, 206–218).
>
> Cross-medication reorders are out of scope here — keep the existing medication-mismatch behaviour that routes a drug change to a fresh assessment (that's Phase 4). Summarise and open a PR.

---

### PHASE 4 — Switching-dose conversion (propose + prescriber-confirm)

**Goal:** a patient switching provider/medication is started on the clinically correct dose from the conversion matrix (not always the 0.25mg starter, which is what the code does today), proposed by the system and confirmed by the prescriber.

**Already exists:** the switching lane captures provider, current medication and current dose (self-declared). The eligibility flow currently always sells the starter dose (`selectedDose` has no UI setter; server defaults to starter) — this is the bug to fix.

**Missing / build:**
- Encode the §1 switching matrix as a `propose_start_dose(from_drug, from_dose, to_drug)` function returning a dose or a small range.
- Feed the proposed dose (or range) into the order and into `_tc_review_flags` so the prescriber confirms/sets the exact point before capture (Phase 2).
- Same-drug provider switch: continue at the declared current dose.
- Keep the existing hard block on an *in-wizard* medication change routing to a fresh assessment; the conversion applies to the switching assessment's outcome, not to reorders.

**Acceptance:**
- A switcher from 10mg Mounjaro is proposed Wegovy 1.7–2.4mg (not 0.25mg), flagged for prescriber confirmation.
- A same-drug switcher continues at their declared dose.
- The proposed dose reaches the order meta and the prescriber notification.

> **PROMPT — Phase 4**
>
> Read `docs/BUILD-BRIEF-v2.md` (§1 Clinical, switching matrix) and `docs/eligibility-review.md` first. Plugins under `wp-content/plugins/`. Fix switching-patient starting doses in `together-clinic-eligibility`. British English; bump versions; do not rewrite the published wizard screens — change only the dose the flow proposes.
>
> Today the flow sells every switcher the 0.25mg/2.5mg starter (`eligibility.js` ~18–19, ~202; server default `class-tc-ajax.php` ~144). Replace that for switching patients with a conversion function `propose_start_dose(from_drug, from_dose, to_drug)` implementing this matrix (ranges mean the system proposes and the prescriber confirms the exact point in Phase 2):
> - Mounjaro→Wegovy: current 2.5–7.5mg → Wegovy 0.5–1.0mg; current 10–15mg → Wegovy 1.7–2.4mg
> - Wegovy→Mounjaro: current ≤1mg → Mounjaro 2.5mg; current 1.7/2.4mg → Mounjaro 5mg
> - Same drug (Wegovy→Wegovy, Mounjaro→Mounjaro): continue at declared current dose
>
> The proposed dose (or range) must land on the order meta and in `_tc_review_flags` (e.g. `switch_proposed: Wegovy 1.7-2.4mg from Mounjaro 10mg`) so the prescriber confirms before capture. New-to-treatment (non-switching) patients keep the starter dose. Keep the existing behaviour that an in-wizard medication change on a *reorder* routes to a fresh assessment. Summarise and open a PR.

---

### PHASE 5 — Data-pipeline hardening

**Goal:** clinical data reaches the order (and thus the prescriber) every time, including cross-device and orphaned-session cases; stale data never leaks onto an unrelated order.

**Already exists (don't rebuild):** session write on submit, dual classic+block attach hooks, CRUD/HPOS meta, idempotency markers (now identity-based after Phase 1), session clearing.

**Missing / build:**
- **User-meta pointer read tier:** the write already exists (`_tc_eligibility_assessment_id`, `class-tc-account.php` ~18/57) but is never read. Add it as a reader fallback and, for logged-in users, fall back at attach time to `assessment_id` → DB row when session and cookie are gone (fixes the cross-device total-loss case).
- Write that pointer **inline right after `ensure_account_for()`** (not via a `wp_login` hook, which never fires on this codebase's auto-login paths).
- **Session-clear timing:** clear only after verifying the marker meta is on *this* order with a matching `assessment_id`, and on a **payment-confirmed** signal (order status processing/completed), not blindly on thank-you. Never inside the classic pre-payment hook.
- **Lane discriminator** in the session/attach so eligibility and reorder handlers stop cross-stamping each other's orders.

**Acceptance:**
- Assess on phone, log in and pay on desktop → the order still carries the full clinical data.
- A stale eligibility session no longer stamps eligibility meta onto a reorder order (and vice versa).
- The session isn't cleared until the data is confirmed on a paid order.

> **PROMPT — Phase 5**
>
> Read `docs/eligibility-review.md` (§0.2 data-attach, weak links) first. Plugins under `wp-content/plugins/`. Harden the clinical-data pipeline without rebuilding what works (session write, dual attach hooks, CRUD/HPOS meta, idempotency markers, and session clearing already exist). British English; CRUD-only meta; keep both checkout paths; bump versions.
>
> Build:
> 1. **Activate the user-meta pointer as a reader tier.** `_tc_eligibility_assessment_id` is written (`class-tc-account.php` ~18/57) but never read. In `TC_Cookie_Store::get()` and at order-attach (`class-tc-checkout.php` ~228–231), when session and cookie are both empty but a user is logged in, fall back to their `_tc_eligibility_assessment_id` → `TC_DB::get_by_assessment_id` → rehydrate from `raw_payload`. This fixes cross-device checkout losing all clinical meta. Also write that pointer inline immediately after `ensure_account_for()` (do NOT add a `wp_login` hook — the plugin's auto-login paths never fire `wp_login`).
> 2. **Condition the session clear** on a payment-confirmed signal (order status processing/completed) AND on verifying the marker meta with a matching `assessment_id` is present on that order. Replace the unconditional `woocommerce_thankyou` clear (`class-tc-checkout.php` ~216–221; `class-tc-reorder-checkout.php` ~18). Never clear inside the classic pre-payment create-order hook.
> 3. **Add a lane discriminator** so the eligibility and reorder attach handlers only stamp their own orders (today both fire on every order's thank-you and cross-stamp — `class-tc-checkout.php` ~208–214 has no product/lane check). Guard each attach on whether the order actually belongs to that lane.
>
> Summarise and open a PR.

---

### PHASE 6 — Screen-0 router (one authority, one predicate)

**Goal:** a single entry that routes new / switching / returning correctly, with exactly one routing authority and one returning-patient predicate — killing the redirect-loop root cause rather than just bounding it.

**Already exists:** the reorder lane is a callable shortcode (`[tc_reorder_form]`); logged-in returning users are already routed to reorder; the v1.1.5/v1.0.6 fix bounds the loop.

**Missing / build:**
- Screen 0 (before consent): logged-in → route by the single predicate; logged-out → new / switching / "ordered before → log in".
- **One predicate** for "reorder-eligible" — use `TC_Reorder_Prefill::for_user()['has_previous_order']` (or a repaired, invalidatable flag), so the eligibility side and reorder side can't disagree. Route logged-in-without-qualifying-order users into new/switching, not into a reorder bounce.
- **One routing authority** that absorbs the three current `template_redirect` handlers (`TC_Checkout` ×2, `TC_My_Account::redirect_shop`, `TC_Reorder_Plugin::enforce_reorder_access`) and the My Account CTA swaps; server-side state for the loop-breaker, not a strippable `?force_assessment=1` query param.
- Decouple the reorder lane's *rendering* from its page-level access control so it can be embedded on the router page without bouncing guests.

**Acceptance:** no redirect loop under any predicate mismatch; a logged-in customer with a qualifying order lands in reorder without re-assessing; a logged-in customer without one is offered new/switching; the two divergent fallback URLs and the unforced link are gone.

> **PROMPT — Phase 6**
>
> Read `docs/eligibility-review.md` (§0.4 redirect loop, and Phase 1 of Part 2) first. Plugins under `wp-content/plugins/`. Build a single unified entry/router. British English; extend, don't rewrite the published screens; bump versions.
>
> Requirements:
> 1. A **screen 0** before the current consent screen: if logged in, route by ONE predicate; if not, offer New to treatment / Switching provider / "I've ordered before → log in then continue". Prepend it; keep the existing consent + userType screens downstream.
> 2. **One returning-patient predicate** shared by both plugins — `TC_Reorder_Prefill::for_user()['has_previous_order']` (or a flag that's actually invalidated). Logged-in users WITHOUT a qualifying order must go to new/switching, never bounce to a reorder page.
> 3. **One routing authority.** Consolidate the three current `template_redirect` handlers and the My Account CTA/return-to-shop swaps into a single owner; use server-side state (not the `?force_assessment=1` query param) as the loop-breaker; unify the divergent `/reorder/` vs `/reorder-now/` fallbacks and remove the unforced "start a new assessment" link (`class-tc-reorder-plugin.php` ~74).
> 4. **Decouple** the reorder lane's rendering from its access control so screen 0 can hand off to it without the shortcode's guard bouncing guests.
>
> Document the handoff (which plugin owns routing; how the reorder lane is invoked). Summarise and open a PR.

---

### PHASE 7 — Login-before-capture (security)

**Goal:** authenticate returning customers at screen 0, and close the email-only account-takeover hole, without stranding existing passwordless accounts.

**Prerequisite (not code):** a password-reset or magic-link plan, because existing accounts were auto-created passwordless with the welcome email suppressed — those patients have never had credentials. Also plan the "email already registered" path for new patients who have a dormant auto-created account.

**Build:** login at screen 0 for the returning lane; stop the silent auto-login into an existing account on assessment submit (`class-tc-account.php` ~21–24) — require an actual login instead; keep the age screen before any account creation (safeguarding). Keep new/switching data capture behind login only where the owner accepts the conversion trade-off, or capture-then-link at the existing point but without auto-authenticating someone else's account.

> **PROMPT — Phase 7**
>
> Read `docs/eligibility-review.md` (§0.6 login state; Phase 3 of Part 2) first. This is security work. Plugins under `wp-content/plugins/`. British English; bump versions.
>
> Close the email-only account-takeover: today, submitting an eligible assessment with any existing customer's email silently logs the browser into that account with a persistent cookie, no password (`class-tc-account.php` ~21–24, reachable via the nopriv `tc_eligibility_save` endpoint). Stop the silent auto-login into EXISTING accounts — if the email matches an existing user, require them to actually log in (magic link or password) rather than auto-authenticating. Add a login option at screen 0 for the returning lane. Keep the age screen before any account creation. Assume a magic-link/password-reset flow is available for the passwordless legacy accounts (coordinate with the site owner) and handle the "email already registered" case for new patients gracefully. Do not weaken the returning-customer reconciliation. Summarise and open a PR.

---

### PHASE 8 — Handoff documentation

**Goal:** a `docs/plugin-handoff.md` recording every cross-plugin contract so the two plugins stay separable and future changes don't silently break the seam.

**Cover:** the `rrqr_data` cart-item key (eligibility's checkout gate stands down on it; the add-to-cart whitelist names the reorder AJAX actions); reorder reading `tc_eligibility_assessment_page_id` directly while both page IDs live in the eligibility settings screen; `TC_Reorder_Pricing`'s conditional delegation to `TC_Variation_Map` and the shared canonical ladder from Phase 3; reorder prefill reading `_tc_eligibility_raw`; the routing authority + predicate from Phase 6; and the `_tc_review_flags` contract from Phases 2–4.

> **PROMPT — Phase 8**
>
> Read `docs/eligibility-review.md` (Part 3 item 4) and the merged code. Write `docs/plugin-handoff.md` documenting every integration contract between `together-clinic-eligibility` and `together-clinic-reorder`: the `rrqr_data` cart-item key and the checkout-gate stand-down, the shared page-ID options and who reads them, the `TC_Reorder_Pricing`→`TC_Variation_Map` delegation and the shared canonical dose ladder, the `_tc_eligibility_raw` prefill fallback, the single routing authority and returning-patient predicate, and the `_tc_review_flags` meta contract. For each: what it is, which plugin owns it, which reads it, and what breaks if it changes. No code changes. Open a PR adding the doc.

---

## 4. Still to confirm before/at launch (operational, not code)

- **Prescriber SLA** comfortably under 7 days (Stripe authorisation window). Agree the target and who is on the rota.
- **Stripe setting** "Issue an authorization on checkout, and capture later" enabled on the live site.
- **Product catalogue + variation map** populated with the real Mounjaro/Wegovy variations (the staging checkout showed demo products — an empty map is what triggers the redirect-loop and dose-derivation failures).
- **Checkout page cleanup:** remove demo products, relabel "Sales tax" → "VAT", brand the block checkout.
- **Magic-link / password-reset** mechanism for the legacy passwordless accounts (Phase 7 prerequisite).

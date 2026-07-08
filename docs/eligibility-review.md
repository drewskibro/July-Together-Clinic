# Review: "Unify Eligibility (New / Switching) with Reorder" build brief

**Repo reviewed:** `drewskibro/TogetherClinicEligibilityCheckerJune2026` at HEAD `2396f56` (Eligibility v1.1.5 / Reorder v1.0.6).
**Method:** every claim below was traced to specific code and independently re-verified. File references are to `plugins/together-clinic-eligibility/` and `plugins/together-clinic-reorder/`.

---

## Verdict in one paragraph

The brief's instincts are largely right — the pitfalls it lists are real, and two of them are verified live bugs. But it was clearly written from memory of the Superior Pharmacy build, not from this codebase, and about **60% of Phase 2 is already implemented and working** (session write, dual checkout hooks, CRUD/HPOS meta, idempotency markers, session clearing). A contractor implementing the brief literally would rebuild working code, switch to a *worse* Store API hook than the one already in use, and adopt a reader chain that is less resilient than what exists. Meanwhile the genuinely missing, highest-value work — the **Phase 4 dose guardrail (nothing exists today; a patient on Mounjaro 2.5mg can buy 15mg)** and the **screen-0 router** — is under-specified in ways that would reproduce the exact redirect-loop failure mode the brief is trying to kill. There is also a **security hole the brief doesn't know about but accidentally fixes** (email-only account takeover), and a cluster of GDPR/clinical-governance issues it misses entirely.

---

## Part 1 — The six Phase 0 answers

### 0.1 Checkout type: cannot be determined from code; docs imply block checkout — **confirm with the live site**

The code is deliberately dual-mode. The only runtime detection is `TC_Eligibility_Plugin::checkout_is_block()` (`class-tc-eligibility-plugin.php:276-282`, `has_block('woocommerce/checkout')`), which switches between `checkout-prefill-blocks.js` and `checkout-prefill-classic.js`. Server-side, **both** order-creation paths are already hooked in both plugins (see 0.2). The docs imply block checkout is the intended production config: the pre-launch checklist requires the block (`docs/STAGING.md:93`) and the readme's FAQ describes reverting *to* classic as the fallback (`readme.txt:45-47`). Both plugins also declare `cart_checkout_blocks` compatibility (`together-clinic-eligibility.php:56`, `together-clinic-reorder.php:44`).

**Required confirmation from the site owner:** inspect the live Checkout page content for `<!-- wp:woocommerce/checkout -->` vs `[woocommerce_checkout]`. (Note: a "Caxton Pharmacy" WordPress connection was available to this session, but it is a different site — no WooCommerce, no Together Clinic plugins — so it could not answer this.)

### 0.2 Data-attach mechanism: it is NOT a fragile cookie — the brief's premise here is wrong

The actual chain, verified end-to-end:

1. Wizard answers live only in in-memory JS state (`eligibility.js:6-22`). No localStorage, no sessionStorage anywhere.
2. On final submit, `tc_eligibility_save` (admin-ajax) writes everything to the custom table `wp_tc_eligibility_submissions` including a full `raw_payload` JSON column (`class-tc-ajax.php:51-118`, `class-tc-db.php:117-170`), **and** puts the entire payload into `WC()->session` under `tc_eligibility_data` (`class-tc-ajax.php:98` → `class-tc-cookie-store.php:43-55`) — forcing the session cookie for guests (`:50-52`), which is essential (a bare `WC()->session->set()` does not persist for a cookie-less guest).
3. The browser cookie — despite the misleading `buildCookiePayload()` name — carries **only** `{"assessment_id":"<uuid>"}` (`eligibility.js:121-130`). It is a pointer, not a data store.
4. The single read path is `TC_Cookie_Store::get()` (`class-tc-cookie-store.php:14-41`): static cache → WC session → cookie-UUID → re-hydrate from the DB row.
5. Order attach: a shared writer `TC_Checkout::attach_assessment_to_order()` (`class-tc-checkout.php:223-290`) is called from **three** hooks: classic `woocommerce_checkout_create_order` (`:19`), blocks `woocommerce_store_api_checkout_update_order_from_request` (`class-tc-checkout-blocks.php:13`), and a `woocommerce_thankyou` fallback (`:20`). It writes `_tc_eligibility_raw` + ~44 flat `_tc_elig_*` keys via CRUD, guarded by an idempotency check, then back-links the order ID onto the DB row.

**Where the chain actually breaks** (the real Phase 2 work):

- **Session-or-nothing at attach.** If session and cookie are both gone at order time, attach silently no-ops (`class-tc-checkout.php:228-231`) even though the full data still sits in the DB and a user-meta pointer `_tc_eligibility_assessment_id` exists (`class-tc-account.php:18,57`) — which is **written but never read anywhere** in either plugin. This is the orphaned-data bug in this codebase's terms.
- **Cross-device is a total loss.** Assess on phone, pay on desktop: no cookie, no session, order gets nothing — and `enforce_before_checkout` (`class-tc-checkout.php:56-63`) forces a full re-assessment.
- **Lossy rehydration.** `hydrate_from_row()` (`class-tc-cookie-store.php:119-175`) drops `prevWeights` and `bariatricRecent` and substitutes the yes/no for `otherConditionsList`, despite `raw_payload` containing everything. One-function fix: rehydrate from `raw_payload`.
- **Thank-you over-attach/over-clear.** Both plugins' attach *and* clear handlers fire on **every** order's thank-you page. A stale eligibility session stamps eligibility meta onto reorder orders (which then pollutes reorder dose derivation via the `_tc_eligibility_raw` fallback, `class-tc-reorder-prefill.php:127-137`); and eligibility's clear destroys a not-yet-used assessment session when a reorder completes first. Clearing is also unconditional — it fires even when attach found nothing.
- **Expiry mismatch.** Cookie 24h (`COOKIE_LIFETIME`, `class-tc-cookie-store.php:9`) < WC session ~48h < 30-day purge of un-ordered DB rows (`class-tc-db.php:205-208`). Assess day 1, pay day 3 = data loss.

### 0.3 Reorder dose storage: derived, not stored — and **no dose rule of any kind exists**

"Current dose" is derived at page load by `TC_Reorder_Prefill` (`class-tc-reorder-prefill.php`): `wc_get_orders` for the logged-in user's `customer_id`, statuses `completed/processing/on-hold` (not just completed), newest 20; dose resolved by **reverse variation-map lookup on order line-item IDs**, falling back to order meta `_rrqr_raw` then `_tc_eligibility_raw`. User meta `_tc_last_treatment`/`_tc_last_dose` is written by the eligibility plugin but never read — dead writes.

The ±1 rule **does not exist anywhere**. `TC_Reorder_Rules::evaluate()` (`class-tc-reorder-rules.php:8-30`) checks only health-change, pregnancy, medication mismatch and under-18 — dose is never compared. The UI renders **every** configured dose with a cosmetic "Current" tag (`reorder.js:310-360`). Server-side "validation" at add-to-cart is existence-only (the treatment+dose maps to a configured product, `class-tc-reorder-ajax.php:163-179`). There is zero order-creation validation in either plugin (no `woocommerce_check_cart_items` / `checkout_process` / `after_checkout_validation` hooks at all). **A patient on Mounjaro 2.5mg can buy 15mg today.**

Worse, the promised escape valve is unwired: the copy says "Our prescriber reviews every choice" (`reorder.js:330`), but the reorder plugin contains **no `wp_mail` call at all**, the clinical-review Calendly button reads a config key that is never localised (`reorder.js:293` vs `class-tc-reorder-plugin.php:225-248`), and the health-changed screen's booking link is hardcoded `href="#"` (`templates/wizard.php:234`). The only artefact a clinician ever sees is order meta in wp-admin.

The dose ladder itself exists only as array insertion order, duplicated in **three places** (`class-tc-variation-map.php:10-28`, `class-tc-reorder-pricing.php:112-130`, `eligibility.js:335-337`), with no index or step function.

### 0.4 The redirect loop: two authorities with two disagreeing data sources — the fix **bounded** it, it did not cure it

Cause (verified against pre-fix code via `git show 4c072d2~1`): two `template_redirect` handlers, both priority 5, classifying "is this a reorder customer" from different sources:

- Eligibility reads a **persisted, write-once, never-deleted** user-meta flag `_tc_returning_customer` (`class-tc-returning-customer.php:40-51`; no `delete_user_meta` exists in either plugin).
- Reorder **recomputes live** from `wc_get_orders` by `customer_id` against the *current* variation map (`class-tc-reorder-prefill.php:46-92`).

When the flag says yes and the recompute says no (empty/changed variation map after a product re-import; a guest order flagged by billing-email match but invisible to the `customer_id` query; a qualifying order buried below the 20-order scan limit), each page bounced the user at the other: assessment → reorder → assessment → … until `ERR_TOO_MANY_REDIRECTS`.

The v1.1.5/v1.0.6 fix is two-sided: the reorder side now redirects with `?force_assessment=1` (`class-tc-reorder-plugin.php:144`), which the eligibility guard honours (`class-tc-checkout.php:80-82`), plus an empty-variation-map guard (`:84-89`). This caps any cycle at 2 hops. **But the underlying disagreement persists**: mismatched users still get a 2-hop bounce on every assessment visit; there are actually **three** redirect authorities (add `TC_My_Account::redirect_shop`, `class-tc-my-account.php:9`); the loop-breaker is a strippable GET param (a CDN or canonicalising security plugin that drops query strings resurrects the loop exactly); one unforced reorder→assessment link survives (`class-tc-reorder-plugin.php:74`); and the two fallback URLs disagree (`home_url('/reorder/')` at `class-tc-checkout.php:92` vs `home_url('/reorder-now/')` at `class-tc-my-account.php:77`).

Page ownership is asymmetric too: eligibility redirects **to** the `tc_reorder_page_id` option while the reorder plugin enforces **on** any page containing `[tc_reorder_form]` — the same page only by admin convention.

### 0.5 HPOS: the code is already fully compliant; whether HPOS is *enabled* needs owner confirmation

Exhaustively verified: **zero** `update_post_meta`/`get_post_meta` calls anywhere in either plugin; all order access via WC_Order CRUD; all past-order queries via `wc_get_orders` with HPOS-supported args; direct SQL confined to the plugins' own custom tables; both plugins declare `custom_order_tables` compatibility correctly (`together-clinic-eligibility.php:53-58`, `together-clinic-reorder.php:41-46`); admin display uses the HPOS-safe hook. Declared floor is WC 8.0 (plugin headers), so everything used is in-range. Whether HPOS is switched on is a live setting (WooCommerce → Settings → Advanced → Features) — but since the code is storage-agnostic, the answer doesn't change any build decision. **This brief phase is "don't regress", not "remediate".**

### 0.6 Login state: the reorder lane is already login-first; the eligibility lane logs in at the END — via an email-only auto-login that is a real security hole

- **Reorder:** strictly login-gated. Guests are redirected to My Account login (`class-tc-reorder-plugin.php:127-138`), all AJAX 401s guests (`class-tc-reorder-ajax.php:18-26, 233-238`), the qualifying-order lookup is `customer_id`-only. No email-lookup or cookie identity path exists. So Phase 1's login-first model already matches this lane.
- **Eligibility:** no login until after final submit. `TC_Account::ensure_account_for()` (`class-tc-account.php`) then either auto-creates a **passwordless** customer with the welcome email deliberately suppressed (`:37-47`) and logs the browser in, or — for an email matching an **existing** account — silently logs the browser in with a persistent auth cookie (`:21-24`), on nothing more than a typed email, reachable by any guest via the nopriv `tc_eligibility_save` endpoint with the shared page nonce. **That is an email-only account-takeover vector for any existing customer.** No mitigation exists in either plugin.
- The only `wp_login` hook (`class-tc-returning-customer.php:28,184-194`) reconciles historical orders into the returning flag; **nothing migrates guest session data on login.** Continuity across login currently comes from the plugin's own cookie→DB rehydration (same browser, ≤24h) plus WooCommerce core's own session migration.
- History note: the "Security check failed" bug (v1.1.2/v1.1.4) was the mid-AJAX auto-login invalidating the guest nonce; fixed by returning a fresh nonce post-login (`class-tc-ajax.php:116`, `eligibility.js:836`) — and v1.1.4's version bump was needed because stale cached JS kept failing. Lesson for the brief's "bump versions" constraint: it's operationally load-bearing (cache busting), not cosmetic.

---

## Part 2 — Phase-by-phase review of the brief

### Phase 1 (router) — right goal, dangerous predicate

**Genuinely missing and worth building:** there is no screen 0. The wizard opens on consent, and the pathway choice (screen 2) offers only new/switching — a logged-out returning patient has no route to the reorder lane from the assessment at all. The router is real new work, and prepending it is compatible with "don't rewrite existing screens" since userType selection already exists downstream.

**The trap:** "If `is_user_logged_in()` → Reorder lane directly" **conflates login with reorder-eligibility — the exact predicate mismatch that caused the production loop.** Three real cohorts break it: (a) accounts auto-created from eligible-but-never-purchased assessments (logged in, no orders); (b) patients whose only purchase was an unreconciled guest order (flag says returning, `customer_id` query says no); (c) returning patients who want to *switch* medication (the reorder lane hard-blocks medication changes and bounces them to assessment). The router must call **one shared predicate** — effectively `TC_Reorder_Prefill::for_user()['has_previous_order']` or a repaired flag with invalidation — and must route logged-in-without-qualifying-order users into the new/switching lanes. "One redirect authority" should mean one *decision source*, not just one hook; and the loop-breaker should be server-side state, not a strippable query param.

**Two things the brief doesn't know:** (1) the reorder lane already *is* a callable shortcode (`[tc_reorder_form]`) — but you cannot embed it on the router page as-is, because its access control fires on **any page containing the shortcode** and bounces every guest to login before screen 0 could render (`class-tc-reorder-plugin.php:109-152`). Decoupling lane rendering from page-level access control is the real work item. (2) `TC_My_Account` routes /shop, product archives and My Account CTAs straight into lanes by the same returning flag — a router that doesn't absorb these gets bypassed entirely.

### Phase 2 (data pipeline) — mostly already built; two of the brief's specifics are actively wrong

Already done, verified, don't pay for again:

- Session write on submit (key `tc_eligibility_data`, not `tc_clinical` — renaming is churn with zero benefit and breaks in-flight patients at deploy time).
- Dual hook coverage on both checkout types, in both plugins, plus a thank-you fallback the brief doesn't mention.
- CRUD-only meta writes, HPOS compat declared (Phase 0.5).
- Idempotency markers in both plugins.
- Session clearing after order.

**Wrong #1 — the blocks hook.** The brief mandates `woocommerce_store_api_checkout_order_processed`. The code uses `woocommerce_store_api_checkout_update_order_from_request`, which fires *before* the Store API saves the order, so the meta rides the same save. The brief's hook fires *after* save and needs its own `$order->save()`. Both are defensible, but switching is a paid lateral move at best; if a contractor "implements the brief" literally, that's a regression risk for nothing.

**Wrong #2 — the reader chain.** "Session → user meta → cookie" omits the tier that does the actual work today: **cookie-UUID → custom DB table rehydration**. The cookie never carried data; implementing the brief's chain literally would *reduce* resilience. The correct chain is: session → user-meta pointer (logged-in) → cookie pointer → DB rehydration from `raw_payload`. Note the user-meta tier should stay a *pointer* (UUID), not a payload copy — no PII duplication in `wp_usermeta`, and it's the only carrier that works cross-device.

**Also asymmetric hook semantics the brief misses:** classic `woocommerce_checkout_create_order` passes an **unsaved** order (`get_id() === 0`) — the current code's in-hook `$order->save()` is load-bearing for the DB back-link on that path but causes a double save. If you want symmetric semantics, the classic partner to a post-save blocks hook is `woocommerce_checkout_order_processed`.

**Genuinely missing (small, high-value):**
1. The user-meta **read** tier — activate the dead write; fall back at attach time to `_tc_eligibility_assessment_id` → DB row when session and cookie are gone.
2. Rehydrate from `raw_payload` instead of the lossy column mapping.
3. Fix the clear logic: clear only after verifying the marker meta exists *on this order* with a matching assessment_id, and clear on a payment-confirmed signal (order status processing/completed) rather than blind thank-you — but **not** inside the classic pre-payment hook, or failed-payment retries strand the second order.
4. Upgrade the idempotency guard from presence-check to **identity-check** (compare `assessment_id`), fixing the verified stale-draft-order bug where a re-taken assessment never replaces the first one stamped on a blocks draft order.
5. Give the unified session payload a **lane discriminator** — today both plugins' attach handlers fire on every order and cross-stamp each other's orders.

**ExtendSchema:** the brief says skip it unless checkout-time UI is needed. It's *already shipped and already needed* — it feeds `has_assessment`/prefill to the block checkout JS. The decision is keep (yes), not build-or-skip.

### Phase 3 (orphaned session) — right diagnosis, but the two mitigations are the weakest part of the brief

**Login at screen 0 (primary):** genuinely missing for the eligibility lane, and it **doubles as the fix for the email-only account-takeover hole** — scope it as security work. But the brief misses three consequences:

1. **The passwordless-account collision.** Every existing patient's account was auto-created with an empty password and the welcome email suppressed — nobody ever received credentials. Login-first requires a **password-reset wave or magic-link flow** as a launch prerequisite, not an edge case. And genuinely new patients often already have an account from a previous eligible-but-abandoned assessment (accounts are created on every eligible save, purchase or not), so "New to treatment" will hit "email already registered" friction the brief doesn't plan for.
2. **Conversion and safeguarding.** The current flow deliberately screens age *before* capturing identity. Login/capture at screen 0 creates accounts and records for minors before screening them out — a safeguarding/GDPR-children regression — and adds top-of-funnel friction with no abandoned-assessment recovery mechanism anywhere (partial rows are orphaned on reload, never followed up, purged at 30 days).
3. **The settings screen itself warns against forcing accounts** (orphan-payment race note at `class-tc-settings.php:69-73`) and STAGING.md checklists guest checkout being enabled — the brief reverses a deliberate design decision without acknowledging it.

**wp_login migration (safety net):** as specified, it will not work. The plugin's own auto-login paths (`wp_set_auth_cookie`, `wc_set_customer_auth_cookie`) **never fire `wp_login`** — that hook only fires via `wp_signon()`. The main guest→user transition in this system bypasses the brief's safety net entirely, and at that moment the session write hasn't even happened yet. The right pattern: write the user-meta pointer inline immediately after `ensure_account_for()` (the payload is in hand), and rely on WooCommerce core's session handler, which already migrates the whole guest session on same-browser login. A `wp_login` hook adds value only as a durability layer, and helps not at all cross-device.

**Cookie compliance note:** correct, and already the status quo — the existing cookie is an essential pointer. One thing the brief's privacy stance should catch but doesn't: the reorder plugin loads Google Fonts from `fonts.googleapis.com` on a clinical journey page (`class-tc-reorder-plugin.php:169-174`) — patient IPs to a third party; self-hosting is trivial.

### Phase 4 (dose guardrail) — the most important phase, and the most under-specified

Everything here is genuinely missing (see 0.3) and is the single biggest clinical-safety gap in the product. The enforcement points the brief names map cleanly onto existing seams (dose options localisation, `TC_Reorder_Ajax::save`/`add_to_cart`, plus a new checkout-time validation hook). But the spec as written will misfire:

- **"Most recent completed order" contradicts the code and the pharmacy reality.** Qualifying statuses today are completed/processing/on-hold. A patient whose latest order is still "processing" (paid, unfulfilled — normal for days) would gate against an *older* order's dose — permitting an unintended double step-up or blocking a legitimate reorder. Gate and prefill must share one status set.
- **The derivation has four blind spots that become purchase refusals** under a hard gate: unreconciled guest orders (invisible to the `customer_id` query), the 20-order scan limit, variation-map staleness after product re-imports (the exact staleness that caused the redirect loop), and multi-item orders. Decide fail-open vs fail-closed explicitly.
- **Switching patients are sold the starter dose regardless of their declared current dose** (`selectedDose` has no UI setter; server defaults to starter). A patient genuinely on Mounjaro 10mg elsewhere has a Together Clinic history saying 2.5mg; an order-history-only ±1 gate caps them at 5mg. Either the gate must also read the assessment's declared `currentDose`, or starter-restart for switchers is the intended clinical policy and the patient-facing copy must say so. This is a clinical-policy decision, not a code detail.
- **Undefined edges:** ladder endpoints (0.25/2.5mg have no step down; 2.4/15mg no step up — clamp, don't error); gapped ladders (unmapped/priceless mid-ladder doses are silently dropped from options, so "+1" can land on an unpurchasable dose); cross-medication ±1 (undefined — keep the existing medication-mismatch hard block); lapsed patients (no recency bound on the reference order; ±1 from a 6-month-old dose contradicts GLP-1 titration practice — add a max-age window that routes to re-assessment).
- **No machine-usable ladder exists.** Build ONE canonical, explicitly indexed ladder per treatment that both plugins consume (extending the existing `TC_Reorder_Pricing` → `TC_Variation_Map` delegation seam), and retire the triplicated implicit ladders. Don't derive step order from the filtered variation map at runtime.
- **"Enforce at order creation" needs blocks-specific hooks.** None of the classic validation hooks (`checkout_process`, `after_checkout_validation`, `check_cart_items`) run on the Store API route. Blocks enforcement needs `woocommerce_store_api_validate_cart_item`/`cart_errors` or a RouteException. Also: WooCommerce carts persist, so a dose valid at add-to-cart can be checked out days later after the baseline changed — define the re-validation moment. And the admin `?preview_reorder=1` path injects synthetic wegovy/0.25mg prefill that a naive gate would evaluate against.
- **The escalation loop must be wired first.** A dose gate whose escape valve is "prescriber review" needs the review pathway to exist: today the Calendly links are dead and no clinician notification is ever sent for reorders. Wiring these (localise the Calendly URL, add a reorder clinician email mirroring `TC_Emails`) belongs in Phase 4's scope.

The lightweight check-in the brief asks for already exists end-to-end (screens, custom table, order meta) — don't pay for it again.

---

## Part 3 — What the brief misses entirely

1. **The custom DB tables are the system of record and the brief never mentions them.** `wp_tc_eligibility_submissions` powers clinician/patient emails, the admin status dashboard, WP-CLI resend, retention/purge, ineligible-outcome records, and the reorder plugin's ownership checks. The session is a transport, not the store. Any "pipeline" spec must say the tables stay and define how the unified payload maps onto them.
2. **GDPR Art. 9 (special-category health data) exposure.** Clinical payloads currently live in four places (custom tables incl. IP/user-agent, ~44 order-meta keys retained indefinitely, WC session rows, clinician emails). An assessment summary — patient type, treatment+dose, BMI, DOB — is injected into **all** WooCommerce order emails **including customer-facing ones** (the `woocommerce_email_order_meta` hook is registered with a single argument, so `$sent_to_admin` is never checked — `class-tc-checkout.php:25`, `class-tc-emails.php:121-144`). Neither plugin registers WordPress privacy exporters/erasers. The brief's only privacy statement addresses the one carrier that holds no data.
3. **Reorder retention is dead code.** `TC_Reorder_DB::purge_stale()` has zero callers — no cron ever purges `wp_tc_reorder_submissions` (DOB, weight, pregnancy, side effects, IP) — and the reorder plugin ships no `uninstall.php` at all. Health-data rows accumulate indefinitely.
4. **The real cross-plugin handoff contracts** the "documented handoff" must cover: the `rrqr_data` cart-item key (eligibility's checkout gate stands down on it; the whitelist names the reorder AJAX actions); reorder reading `tc_eligibility_assessment_page_id` directly; both page IDs living in the *eligibility* settings screen while reorder decides ownership by shortcode presence; `TC_Reorder_Pricing`'s conditional delegation to `TC_Variation_Map`; reorder prefill reading `_tc_eligibility_raw` order meta; and the `?force_assessment=1` protocol. Renaming keys to `tc_clinical` without migrating these breaks checkout bypass, cart badges and dose derivation simultaneously.
5. **Abandoned-assessment recovery** doesn't exist, and login-at-screen-0 raises abandonment without adding any resume path.
6. **Multi-tab behaviour:** both lanes empty the cart before adding, and each store has a single session slot — two open wizards destroy each other. A unified single key makes last-writer-wins worse unless the payload carries a lane discriminator.

---

## Part 4 — Revised scope: what to actually pay for

**Build (new work, in priority order):**
1. **Phase 4 dose gate** — canonical indexed ladder, ±1 with clamped endpoints and defined edge policy (statuses, switchers, guests, lapsed, gaps), enforced in `save()`, `add_to_cart()`, and checkout-time validation on *both* checkout types; wire the clinician notification + Calendly escalation. Highest clinical priority; nothing exists.
2. **Screen-0 router** — with the routing decision consolidated onto ONE returning-patient predicate, server-side loop-breaker state, absorption of the TC_My_Account routes, and the reorder lane's access control decoupled from its shortcode.
3. **Login-before-capture** for the eligibility lane — scoped as security work (closes the email-only takeover), with the magic-link/password-reset wave and an "email already registered" path as launch prerequisites, and the age screen kept before account creation.
4. **User-meta pointer read tier + inline post-`ensure_account_for()` pointer write** (not a `wp_login` hook) + attach-time DB fallback — this is the actual orphaned-session fix.
5. **The handoff document** (the contracts in Part 3, item 4).

**Refine (small fixes):** condition session-clear on verified attach + payment-confirmed signal; rehydrate from `raw_payload`; identity-based idempotency guard; lane discriminator in session payloads; unify the two reorder fallback URLs; force-flag the `class-tc-reorder-plugin.php:74` link; fix the cookie clear path/domain mismatch; align cookie/session lifetimes deliberately; add the reorder purge cron + uninstall; check `$sent_to_admin` in the order-email injection; self-host the reorder fonts.

**Do NOT rebuild (already done and verified):** WC-session payload write; dual classic+blocks attach hooks (and do **not** switch to `woocommerce_store_api_checkout_order_processed`); HPOS/CRUD meta; idempotency markers (upgrade, don't replace); ExtendSchema (keep it); the reorder shortcode; returning-user redirects; current-dose derivation; the reorder check-in pipeline; login-first on the reorder lane.

**Confirm with the site owner (code cannot answer):** live checkout type (block vs shortcode); HPOS enabled or not; live values of `tc_eligibility_variation_map`, `tc_reorder_page_id`, `tc_reorder_enforce_login`, `tc_redirect_shop`, `tc_use_returning_check`; whether a CDN/security layer strips query strings; clinical policy for switching patients' first reorder dose.

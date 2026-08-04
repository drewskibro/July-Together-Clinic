=== Together Clinic Eligibility Checker ===
Contributors: togetherclinic
Tags: woocommerce, healthcare, eligibility, glp-1, weight-loss
Requires at least: 6.4
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later

Multi-step weight-loss eligibility assessment for Together Clinic with WooCommerce checkout integration.

== Description ==

A self-contained WordPress plugin that ports the Together Clinic eligibility checker into a WooCommerce-integrated wizard. Supports both block checkout and classic checkout, with operational learnings carried over from the Superior Pharmacy build.

Features:

* Multi-screen patient assessment (33 logical screens)
* New patient + switching-provider flows
* Server-side ineligibility rules (age, BMI, pregnancy, contraindicated conditions, recent bariatric surgery)
* Auto-creates WooCommerce customer account on submission
* Cookie-based session carry-over to checkout
* Block + classic checkout prefill
* Place-order debounce snippet (prevents multi-click double charges)
* Patient confirmation + clinician notification emails
* Order admin screen with full assessment summary
* WP-CLI commands for ops (`wp tc-eligibility status`, `purge-stale`, `resend-confirmation`)
* Daily cron to purge stale submissions per retention policy
* HPOS-ready

== Installation ==

1. Upload the plugin folder (or zip via Plugins → Add New → Upload Plugin).
2. Activate via the Plugins screen in WP admin.
3. Create a new Page (e.g. "Start Your Assessment", slug `/start-your-assessment/`) with just the shortcode `[tc_eligibility_wizard]` as its content. The active theme will wrap it with the site's header and footer automatically.
4. Visit **WooCommerce &rarr; Eligibility**:
   - Set the "Assessment page" to the page you just created.
   - Click "Auto-detect product IDs from SKUs" (works if products use SKUs `WG-0.25`, `WG-0.5`, ..., `MJ-2.5`, `MJ-5`, ..., `MJ-15`). Otherwise enter the 11 product IDs manually.
   - Set clinician email recipients and Calendly URLs.
5. Update any "Check Eligibility" / "Start Your Assessment" CTAs site-wide to point to the assessment page URL. Or drop `[tc_eligibility_button]` into any page / widget.
6. Ensure WooCommerce guest checkout is enabled (Accounts &amp; Privacy settings).

== Frequently Asked Questions ==

= How do I revert to classic checkout? =

The plugin works with both. Change the checkout page content from `<!-- wp:woocommerce/checkout -->` to `[woocommerce_checkout]`. No code change required.

= How do I disable a feature without redeploying? =

Use the kill switches in plugin settings or via WP-CLI:

`wp option update tc_eligibility_enforce_assessment_before_checkout 0`

= Where do I find the variation IDs to enter? =

In WP admin: Products &rarr; All Products &rarr; Edit Wegovy/Mounjaro &rarr; Variations. Each variation card shows its ID.

== Changelog ==

= 1.0.0 =
* Initial release.

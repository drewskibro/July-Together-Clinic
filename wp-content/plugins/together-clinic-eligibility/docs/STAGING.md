# Staging-first testing for Together Clinic Eligibility

Adapted from the Superior Pharmacy operational runbook. Run through these once before pushing this plugin to live.

## 1. Block outbound email on staging

Staging is a clone of live and may have real patient data. Without this guard, any test submission can fire real emails to real customers.

Drop into `wp-content/mu-plugins/` on staging:

```php
<?php
if ( ! function_exists( 'wp_mail' ) ) {
    function wp_mail( $to, $subject, $message, $headers = '', $attachments = [] ) {
        return false;
    }
}
```

Verify:

```bash
wp eval 'echo wp_mail("test@nowhere.example", "TEST", "TEST") ? "STILL SENDING - DANGER" : "BLOCKED OK";'
```

Expected: `BLOCKED OK`.

## 2. Put Stripe in test mode

Before any test transaction: WC admin → Payments → Stripe → toggle Test mode ON. Confirm Live mode is OFF.

## 3. Use the User Switching plugin

Test as a real customer without knowing their password: WP admin → Users → All Users → "Switch To". Click the bar at the top to revert.

## 4. Pre-deploy verification grep

After SFTP-uploading the plugin to live, confirm the new code is actually there before testing:

```bash
grep -c "TC_Eligibility_Plugin" wp-content/plugins/together-clinic-eligibility/together-clinic-eligibility.php
```

Expected: a non-zero number. SFTP clients preserve source mtimes — file timestamps are unreliable.

## 5. CLI sanity checks

```bash
# Submission counts
wp tc-eligibility status

# Manual purge (won't delete anything if nothing is older than retention)
wp tc-eligibility purge-stale --days=30

# Re-send confirmation for an assessment
wp tc-eligibility resend-confirmation <uuid>
```

## 6. Operational logging

All plugin decisions log to PHP error log with prefix `[tc-eligibility]`:

```bash
tail -f ~/logs/error.log | grep '\[tc-eligibility\]'
```

Browser-side debounce logs to the JS console with prefix `[tc-debounce]`.

## 7. Kill switches

Instant feature-flag rollbacks without a deploy:

```bash
# Stop redirecting checkout to assessment when no cookie
wp option update tc_eligibility_enforce_assessment_before_checkout 0

# Allow direct add-to-cart bypassing the wizard
wp option update tc_eligibility_block_direct_add_to_cart 0

# Stop sending clinician emails
wp option update tc_eligibility_send_clinician_emails 0
```

Re-enable by setting to `1`.

## 8. Pre-launch checklist

- [ ] WooCommerce guest checkout is enabled (Accounts &amp; Privacy)
- [ ] Variation IDs filled in for all 11 doses (5 Wegovy + 6 Mounjaro) in WC → Eligibility
- [ ] Calendly URLs entered
- [ ] Clinician recipients confirmed
- [ ] SMTP / transactional email setup verified (test patient confirmation arrives)
- [ ] Block checkout page contains `<!-- wp:woocommerce/checkout -->`
- [ ] Stripe webhook configured for `payment_intent.succeeded` / `payment_intent.payment_failed`
- [ ] Plugin version header bumped on every release

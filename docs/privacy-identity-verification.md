# Privacy notice — identity verification (DRAFT for sign-off)

**Status:** draft prepared by the build team, 2026-09-11. **Requires clinical/DPO
sign-off before publishing.** This is proposed wording to add to the Together
Clinic privacy policy to cover identity verification (Stripe Identity), which
processes **biometric data** (a facial image / selfie) — special-category data
under UK GDPR Article 9. It is not legal advice; please have it reviewed.

Add as a new section of the privacy policy at `/privacy-policy/`, and link to it
from the consent line shown on the verification step.

---

## Identity verification

To supply prescription-only medicines safely and lawfully, we must confirm that
you are who you say you are and that you are 18 or over. When you place your
first order, we ask you to complete an identity check as the final step.

### What we collect

- A photograph of a government-issued **photo ID document** (for example a
  passport, driving licence or national ID card).
- A **selfie**, and a **biometric facial scan** derived from it, used to confirm
  that the person holding the document is you (a "liveness" and face-match
  check).
- The **name and date of birth** read from your document, which we compare with
  the details you gave in your assessment.

### Who processes it

The identity check is carried out by **Stripe** (Stripe Payments Europe, Ltd.
and Stripe, Inc.) using **Stripe Identity**, acting as our processor. Your ID
image, selfie and biometric data are collected and processed by Stripe. See
Stripe's privacy policy at https://stripe.com/privacy for how Stripe handles
this data, including any transfer outside the UK/EEA under appropriate
safeguards.

Together Clinic receives from Stripe only the **outcome** of the check (verified
or not), the **name and date of birth** read from the document (to compare with
your assessment), and a verification reference. **We do not store your ID images
or your selfie**; those remain with Stripe under Stripe's retention policy.

### Our lawful bases

- For processing your personal data to verify identity and age: performance of
  our contract with you and compliance with our legal and regulatory obligations
  as a pharmacy supplying prescription-only medicines (UK GDPR Article 6(1)(b)
  and (c)).
- For the **biometric data** specifically (the facial scan, which is
  special-category data): your **explicit consent** (UK GDPR Article 9(2)(a)).
  We ask for this consent, separately and clearly, immediately before the check
  begins. You do not have to consent — but because we cannot safely supply a
  prescription-only medicine without confirming your identity, we will not be
  able to complete your order if you do not.

### Retention

We keep the verification outcome and the reference for as long as we are
required to retain your clinical and order records. Your ID images and biometric
data are held and deleted by Stripe according to Stripe's own retention schedule
— we do not hold copies.

### Your rights

You can withdraw consent, and exercise your other data-protection rights
(access, rectification, erasure, and others), by contacting us at
[care@togetherclinic.co.uk]. Withdrawing consent does not affect processing
carried out before withdrawal, and may mean we can no longer supply treatment.

---

## Implementation notes (not part of the published notice)

- Consent is captured on the order-received page with an explicit, unticked
  checkbox before the "Verify my identity" button is enabled; the timestamp is
  recorded against the order (`_tc_identity_consent_at`).
- Together Clinic stores only: verification status, the name/DOB comparison
  result, and the Stripe session id — never the document image or selfie.
- The `/privacy-policy/` link in the consent line must resolve to the published
  version of this notice before the feature is switched on in live.

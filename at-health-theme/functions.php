<?php
/**
 * AT Health Theme Functions
 *
 * Prefix: ah_
 * All ACF helper functions use strict null checks (=== null || === '').
 * Never use empty() — it breaks ACF true_false fields where 0 means "No".
 */

// ─── Theme Version (cache-bust via globals.css mtime) ───
define( 'AH_VERSION', file_exists( get_theme_file_path( 'assets/css/globals.css' ) )
    ? filemtime( get_theme_file_path( 'assets/css/globals.css' ) )
    : time()
);

// ─── Theme Setup ───
add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );
    // WooCommerce support
    add_theme_support( 'woocommerce' );

    add_theme_support( 'custom-logo', array(
        'height'      => 80,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    // Custom image sizes
    add_image_size( 'treatment-card', 600, 400, true );
    add_image_size( 'health-hub-featured', 800, 600, true );
    add_image_size( 'health-hub-card', 600, 400, true );
    add_image_size( 'hero-image', 1200, 800, true );

    // Nav menus
    register_nav_menus( array(
        'primary'    => __( 'Primary Navigation', 'at-health' ),
        'footer'     => __( 'Footer Navigation', 'at-health' ),
    ) );
} );

// ─── Allow SVG Uploads ───
add_filter( 'upload_mimes', function ( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
} );

// ─── Disable Gutenberg for Page Templates ───
add_filter( 'use_block_editor_for_post', function ( $use, $post ) {
    if ( $post && get_page_template_slug( $post->ID ) ) {
        $template = get_page_template_slug( $post->ID );
        if ( strpos( $template, 'page-templates/' ) === 0 ) {
            return false;
        }
    }
    return $use;
}, 10, 2 );

// ─── Add page slug as body class ───
add_filter( 'body_class', function ( $classes ) {
    if ( is_page() ) {
        global $post;
        $classes[] = 'page-' . $post->post_name;
    }
    return $classes;
} );

// ═══════════════════════════════════════════════
// ACF HELPER FUNCTIONS
// ═══════════════════════════════════════════════

/**
 * Get an ACF option field with a safe fallback.
 * NEVER use empty() — 0 is a valid value for true_false fields.
 */
function ah_option( $field_name, $default = '' ) {
    if ( function_exists( 'get_field' ) ) {
        $value = get_field( $field_name, 'option' );
        if ( $value === null || $value === '' ) {
            return $default;
        }
        return $value;
    }
    return $default;
}

/**
 * Get an ACF page-level field with a safe fallback.
 */
function ah_field( $field_name, $default = '' ) {
    if ( function_exists( 'get_field' ) ) {
        $value = get_field( $field_name );
        if ( $value === null || $value === '' ) {
            return $default;
        }
        return $value;
    }
    return $default;
}

/**
 * Shortcut helpers for commonly used global values.
 */
function ah_company_name() {
    return ah_option( 'company_name', 'Together Clinic' );
}

function ah_phone() {
    return ah_option( 'phone_number', '' );
}

function ah_phone_link() {
    return 'tel:' . preg_replace( '/[^0-9+]/', '', ah_phone() );
}

function ah_email() {
    return ah_option( 'email_address', 'info@togetherclinic.co.uk' );
}

function ah_business_hours() {
    return ah_option( 'business_hours', '9am - 5pm, Monday to Friday' );
}

function ah_no_phone_notice() {
    return ah_option( 'no_phone_notice', 'We do not offer telephone consultations. Please contact us via email or live chat.' );
}

function ah_booking_url() {
    return ah_option( 'eligibility_url', '/weight-loss-eligibility/' );
}

/**
 * Default Terms & Conditions content (client-supplied, July 2025).
 * Used as the default_value for the tm_content ACF field so the Terms
 * page renders out of the box and remains editable in WP Admin.
 * Headings/paragraphs/lists are styled by .tm-content in terms.css.
 */
function ah_terms_default_content() {
    return <<<'TERMS_HTML'
<p><strong>Last updated: July 2025</strong></p>

<h2>1. Introduction</h2>
<p>Welcome to Together Clinic. By accessing and using our website at www.togetherclinic.co.uk, you agree to comply with and be bound by the following Terms &amp; Conditions. These terms, together with our Privacy Policy, govern At Health Ltd's relationship with you in relation to this website and any services provided through it.</p>
<p>Please read these Terms &amp; Conditions carefully before using our website. If you do not agree with any part of these terms, you must not use our website or services.</p>

<h2>2. Company Details</h2>
<p><strong>Company Name:</strong> At Health Ltd (trading as Together Clinic)<br>
<strong>Website:</strong> www.togetherclinic.co.uk<br>
<strong>Email:</strong> support@togetherclinic.co.uk<br>
<strong>Superintendent Pharmacist:</strong> Ahmed Nizar Al-Liabi (GPhC No.: 2208502)<br>
<strong>GPhC Registration Status:</strong> Registered | Expiry: 31 July 2027<br>
<strong>GPhC Annotations:</strong> Independent Prescriber, Superintendent<br>
<strong>GPhC Register Link:</strong> <a href="https://www.pharmacyregulation.org/registers/pharmacist/2208502" target="_blank" rel="noopener noreferrer">https://www.pharmacyregulation.org/registers/pharmacist/2208502</a></p>

<h2>3. Use of the Website</h2>
<p><strong>Content:</strong> The content of this website is for your general information and use only. It is subject to change without notice.</p>
<p><strong>Accuracy:</strong> Neither we nor any third parties provide any warranty or guarantee as to the accuracy, timeliness, performance, completeness, or suitability of the information and materials found or offered on this website for any particular purpose. You acknowledge that such information and materials may contain inaccuracies or errors, and we expressly exclude liability for any such inaccuracies or errors to the fullest extent permitted by law.</p>
<p><strong>Medical Information:</strong> The content on this website does not constitute medical advice and should not be relied upon as such. Always seek the advice of a qualified healthcare professional regarding any medical condition or treatment.</p>

<h2>4. User Responsibilities</h2>
<p><strong>Conduct:</strong> Your use of any information or materials on this website is entirely at your own risk, for which we shall not be liable. It shall be your own responsibility to ensure that any products, services, or information available through this website meet your specific requirements.</p>
<p><strong>Accuracy of Information:</strong> When providing information to us (including during registration, consultation, or ordering processes), you must ensure that all information provided is accurate, complete, and up to date. We shall not be liable for any issues arising from incorrect or incomplete information provided by you.</p>
<p><strong>Prohibited Use:</strong> You must not misuse this website. This includes, but is not limited to: committing or encouraging a criminal offence; transmitting or distributing any virus, trojan, worm, logic bomb, or other material which is malicious, technologically harmful, in breach of confidence, or in any way offensive or obscene; accessing or attempting to access any accounts or data belonging to other users; or attempting to gain unauthorised access to the server on which our website is stored.</p>
<p><strong>Age Requirement:</strong> By using this website, you confirm that you are 18 years of age or older. Our services are not intended for individuals under the age of 18.</p>

<h2>5. Pharmaceutical &amp; Clinical Services</h2>
<p><strong>Regulated Activity:</strong> Together Clinic operates as a regulated pharmacy service under the supervision of our Superintendent Pharmacist, Ahmed Nizar Al-Liabi (GPhC No.: 2208502). All clinical and prescribing services are provided in accordance with GPhC standards and applicable UK legislation.</p>
<p><strong>Prescriptions:</strong> Any prescription-only medicines (POMs) dispensed or prescribed through Together Clinic will only be provided following an appropriate clinical assessment by a suitably qualified prescriber. We reserve the right to decline to supply any medicine where we consider it clinically inappropriate to do so.</p>
<p><strong>Clinical Judgement:</strong> Our clinicians and pharmacists retain full clinical autonomy. The provision of a service does not guarantee that a prescription or treatment will be issued. Clinical decisions are made in the best interest of the patient at all times.</p>
<p><strong>Consultation Accuracy:</strong> You must provide honest and complete information during any consultation or health assessment. Providing false or misleading information may result in a clinically inappropriate prescription being issued, for which Together Clinic cannot accept liability.</p>

<h2>6. Ordering, Payments &amp; Cancellations</h2>
<p><strong>Order Acceptance:</strong> Submission of an order or consultation request does not constitute a binding contract until we have confirmed acceptance. We reserve the right to decline any order at our discretion.</p>
<p><strong>Pricing:</strong> All prices displayed on our website are inclusive of VAT where applicable. We reserve the right to change prices at any time without prior notice. Any price changes will be communicated to you before your order is confirmed.</p>
<p><strong>Cancellations &amp; Refunds:</strong> Cancellation and refund rights are subject to our separate Refund and Cancellation Policy, which is available on our website and forms part of these Terms &amp; Conditions. Your statutory rights under the Consumer Contracts (Information, Cancellation and Additional Charges) Regulations 2013 are not affected.</p>
<p><strong>Prescription Medicines:</strong> Please note that, in accordance with GPhC guidance, once a prescription-only medicine has been dispensed and dispatched, we are unable to accept returns for reasons of patient safety. This does not affect your statutory rights where goods are faulty or not as described.</p>

<h2>7. Intellectual Property</h2>
<p><strong>Ownership:</strong> This website contains material which is owned by or licensed to At Health Ltd. This material includes, but is not limited to, the design, layout, look, appearance, graphics, and written content. Reproduction is prohibited other than in accordance with the copyright notice, which forms part of these Terms &amp; Conditions.</p>
<p><strong>Third-Party Rights:</strong> All trademarks reproduced in this website that are not the property of, or licensed to, At Health Ltd are acknowledged on the website.</p>
<p><strong>Permitted Use:</strong> You may print or download extracts from this website for your own personal, non-commercial use only. You must not modify, copy, reproduce, republish, upload, post, transmit, or distribute any content from this website for any commercial purpose without our prior written consent.</p>

<h2>8. Limitation of Liability</h2>
<p><strong>Exclusion:</strong> To the extent permitted by law, At Health Ltd (trading as Together Clinic) shall not be liable for any indirect or consequential loss or damage, including any loss of profit, business, revenue, goodwill, or anticipated savings, incurred by any user in connection with our website or in connection with the use, inability to use, or results of the use of our website, its content, or any websites linked to it.</p>
<p><strong>Force Majeure:</strong> Together Clinic will not be held responsible for any delay or failure to comply with our obligations under these terms if the delay or failure arises from any cause which is beyond our reasonable control.</p>
<p><strong>Website Availability:</strong> We do not guarantee that our website will be secure or free from bugs or viruses. We will not be liable for any loss or damage caused by a virus, distributed denial-of-service attack, or other technologically harmful material that may infect your computer equipment, computer programs, data, or other proprietary material due to your use of our website.</p>
<p>Nothing in these Terms &amp; Conditions shall exclude or limit our liability for death or personal injury caused by our negligence, fraud or fraudulent misrepresentation, or any other liability that cannot be excluded or limited by applicable law.</p>

<h2>9. Data Protection &amp; Privacy</h2>
<p>At Health Ltd is committed to protecting your personal data. Our Privacy Policy sets out how we collect, use, and store your personal information in compliance with the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018. By using our website and services, you acknowledge that you have read and understood our Privacy Policy.</p>
<p>As a healthcare provider, we process special category health data. This processing is carried out under Article 9(2)(h) UK GDPR (healthcare purposes) and in accordance with the applicable codes of conduct of the General Pharmaceutical Council.</p>

<h2>10. Third-Party Links</h2>
<p>Our website may contain links to third-party websites. These links are provided for your convenience only. We have no control over the content of those websites and accept no responsibility for them or for any loss or damage that may arise from your use of them. Our inclusion of any link does not imply our endorsement of the linked site or its operator.</p>

<h2>11. Governing Law</h2>
<p>These Terms &amp; Conditions are governed by and construed in accordance with the laws of England and Wales. You agree, as do we, to submit to the exclusive jurisdiction of the courts of England and Wales in relation to any dispute or claim arising in connection with these terms or your use of our website or services.</p>

<h2>12. Changes to These Terms &amp; Conditions</h2>
<p>At Health Ltd reserves the right to amend these Terms &amp; Conditions at any time. Any changes will be posted on this page with an updated revision date. Your continued use of our website following any changes shall constitute your acceptance of those changes. We encourage you to review this page periodically.</p>

<h2>13. Complaints</h2>
<p>If you have a complaint about any aspect of our service, please contact us in the first instance at support@togetherclinic.co.uk. We aim to acknowledge all complaints within two working days and to resolve them within 14 working days.</p>
<p>If you are dissatisfied with our response, you may also raise concerns with the General Pharmaceutical Council (GPhC) at www.pharmacyregulation.org, or with the relevant Responsible Body. For disputes relating to online purchases, you may also refer your complaint to an ADR (Alternative Dispute Resolution) provider.</p>

<h2>14. Contact Information</h2>
<p>If you have any questions about these Terms &amp; Conditions, please contact us:</p>
<p><strong>Company:</strong> At Health Ltd (t/a Together Clinic)<br>
<strong>Website:</strong> www.togetherclinic.co.uk<br>
<strong>Email:</strong> support@togetherclinic.co.uk</p>

<p>&copy; At Health Ltd. All rights reserved. Together Clinic is a trading name of At Health Ltd.</p>
TERMS_HTML;
}

/**
 * Default Refund & Cancellation Policy content (client-supplied, July 2025).
 * Used as the default_value for the rp_content ACF field.
 * Base typography styled by .tm-content; callouts by .rp-notice / .rp-stages
 * (both in terms.css, shared by the Terms and Refund Policy pages).
 */
function ah_refund_policy_default_content() {
    return <<<'REFUND_HTML'
<p><strong>Last updated: July 2025</strong></p>

<p>Together Clinic is operated by At Health Ltd, a GPhC-registered online pharmacy and independent prescribing service. All consultations, prescriptions, and medication orders are subject to clinical review and are governed by applicable UK pharmacy law, MHRA regulations, and GPhC standards of practice.</p>
<p>Please read this policy carefully before booking a consultation or placing an order. By proceeding, you confirm that you have read and understood the terms below.</p>

<div class="rp-notice">
  <p class="rp-notice-title">⚠ Important Notice</p>
  <p>Together Clinic uses a pending payment authorisation model. Your card is authorised when you order, but no money is taken until your prescription is approved.</p>
  <p>If your prescription is not approved for any reason, your authorisation is released and no charge is made to your account.</p>
  <p>Once a prescription is approved and payment is captured, dispensed medications cannot be returned in accordance with GPhC and MHRA regulations.</p>
</div>

<h2>1. Cancellation of Consultations</h2>
<p>The following cancellation terms apply to booked clinical consultations with Together Clinic.</p>

<h3>1.1 Cancellation by You</h3>
<ul>
<li>You may cancel or reschedule a consultation at any time before your appointment, free of charge, by contacting us at support@togetherclinic.co.uk.</li>
<li>If you cancel a paid consultation before it takes place, a full refund will be issued to your original payment method within 2–5 working days.</li>
<li>If you fail to attend a booked consultation without prior notice (a "no-show"), no refund will be issued. You will need to rebook and pay for a new appointment if you wish to proceed.</li>
<li>If you cancel a consultation at short notice (less than 24 hours before the scheduled time), we reserve the right to apply a cancellation fee of up to the full consultation cost. Any applicable fee will be communicated to you at the time of booking.</li>
</ul>

<h3>1.2 Cancellation by Together Clinic</h3>
<ul>
<li>In the rare event that we are required to cancel or reschedule your consultation, we will notify you as soon as possible and offer an alternative appointment at no additional charge, or a full refund if you prefer.</li>
</ul>

<h3>1.3 Consultations That Do Not Result in a Prescription</h3>
<ul>
<li>Our prescribers retain full clinical autonomy and a prescription cannot be guaranteed following a consultation. If your consultation is completed but a prescription is not issued on clinical grounds, a full refund of any amount paid will be processed to your original payment method within 2–5 working days.</li>
<li>If your consultation cannot be completed due to technical difficulties on our part, a full refund or complimentary rebook will be offered.</li>
</ul>

<h2>2. How Payment Works — Pending Authorisation Model</h2>
<p>Together Clinic uses a pending payment authorisation model. When you submit an order, your payment card is authorised but no money is taken from your account at that point. Your payment is only captured (i.e. the funds actually collected) once a clinician has completed your clinical review and a prescription has been approved and issued.</p>
<p>This means that if your prescription is not approved for any reason — including on clinical grounds — your payment authorisation is simply released and no charge is made to your account. There is nothing to refund because no payment has been taken.</p>

<div class="rp-stages">
  <p><strong>Stage 1 — Pending clinical review:</strong> Payment authorised but not taken. Cancellation allowed — authorisation released immediately.</p>
  <p><strong>Stage 2 — Prescription approved, payment captured:</strong> Cancellation no longer possible. Dispensing begins.</p>
  <p><strong>Stage 3 — Dispensed and dispatched:</strong> Cancellation not possible. Return not permitted.</p>
</div>

<h3>2.1 Payment Hold Period</h3>
<ul>
<li>Your payment authorisation will be held for a maximum of 7 days from the date of your order, pending completion of your clinical review.</li>
<li>If your clinical review is not completed within 7 days, the payment authorisation will lapse automatically, your order will be cancelled, and no charge will be made to your account. You will receive email notification if this occurs.</li>
<li>If you wish to proceed after a lapsed authorisation, you will need to place a new order.</li>
</ul>

<h3>2.2 Cancellation Before Prescription Is Approved</h3>
<ul>
<li>You may cancel your order at any time before your prescription has been approved and payment captured. Please contact us at support@togetherclinic.co.uk as soon as possible.</li>
<li>Upon cancellation at this stage, your payment authorisation will be released immediately. No charge will be made to your account. Depending on your card issuer, the released authorisation may take 2–5 working days to no longer show as pending on your statement.</li>
</ul>

<h3>2.3 After Prescription Is Approved and Payment Captured</h3>
<ul>
<li>Once a clinician has approved your prescription and payment has been captured, pharmacy dispensing begins immediately. Cancellation is no longer available at this stage.</li>
<li>This applies regardless of whether the medication has been physically dispatched.</li>
</ul>

<h3>2.4 After Dispatch</h3>
<ul>
<li>Once your order has left the pharmacy, cancellation is not possible. This is in accordance with UK medication safety regulations and MHRA guidance.</li>
<li>Prescription medications are dispensed specifically for you as an individual patient and cannot be returned to stock, resold, or reused. This is a regulatory requirement, not a commercial decision.</li>
</ul>

<h2>3. Automatic Cancellations</h2>
<p>We may need to request additional information from you before we can complete your clinical review — for example, identity verification, GP details, or supplementary medical information. If this is not received within the required timeframe, your order will be automatically cancelled and your payment authorisation released as follows:</p>
<ul>
<li>All prescription orders: payment authorisation released and order cancelled after 7 days if outstanding information is not provided.</li>
</ul>
<p>Because no payment is captured until your prescription is approved, no refund is required when an order is automatically cancelled at this stage — the authorisation simply lapses and no charge is made.</p>
<p>You will receive email notification of any automatic cancellation. Once cancelled, orders cannot be reinstated — you will need to place a new order if you wish to proceed.</p>

<h2>4. Refunds</h2>
<p>Because Together Clinic uses a pending payment authorisation model, a refund in the traditional sense is only relevant once payment has actually been captured — i.e. after your prescription has been approved and dispensing has begun. In most cases where an order does not proceed (cancellation before approval, clinical decline, lapsed authorisation), no charge is ever made and no refund is therefore required.</p>

<h3>4.1 How Refunds Are Processed</h3>
<ul>
<li>All refunds are issued to the original payment method used at checkout.</li>
<li>Refunds typically appear in your account within 2–5 working days. Depending on your bank or card issuer, processing may occasionally take up to 10 working days.</li>
<li>You will receive an email confirmation once your refund has been initiated.</li>
<li>We do not issue refunds in cash or via an alternative payment method.</li>
</ul>

<h3>4.2 When No Charge Is Made (No Refund Required)</h3>
<ul>
<li>Your order is cancelled before your prescription is approved — payment authorisation is released and no charge is made.</li>
<li>Your prescription is not approved on clinical grounds — payment authorisation is released and no charge is made.</li>
<li>Your order is automatically cancelled due to a lapsed authorisation or outstanding information (see Section 3) — no charge is made.</li>
<li>A consultation is cancelled before it takes place (see Section 1) — no charge is made.</li>
</ul>

<h3>4.3 When a Refund Will Be Issued</h3>
<ul>
<li>Your medication arrives damaged, incorrect, or is confirmed lost in transit (see Section 5) — a refund or replacement will be offered.</li>
<li>A Together Clinic error has resulted in an incorrect charge being applied after payment capture.</li>
</ul>

<h3>4.4 When a Refund Will Not Be Issued</h3>
<ul>
<li>Payment has been captured and medication has been dispensed and dispatched.</li>
<li>You fail to attend a booked consultation without prior notice.</li>
<li>You change your mind after payment has been captured and dispensing has begun.</li>
</ul>

<h2>5. Damaged, Incorrect, or Lost Orders</h2>
<p>Replacements or refunds may be issued in the following circumstances:</p>
<ul>
<li>Your medication arrives visibly damaged or compromised.</li>
<li>The incorrect medication or dosage has been supplied.</li>
<li>Your order is confirmed as lost by the courier or delivery service.</li>
</ul>
<p>To report an issue, please contact us at support@togetherclinic.co.uk with your order reference and, where possible, photographic evidence of any damage or discrepancy. We aim to investigate and resolve all such cases within 5 working days.</p>
<p>For certain prescription medicines — particularly injectables or cold-chain products — a clinical review may be required before a replacement can be approved. Where this is the case, we will notify you promptly.</p>

<h2>6. Repeat &amp; Subscription Orders</h2>
<p>Where Together Clinic offers ongoing treatment plans or subscription-based medication supply, the following additional terms apply.</p>

<h3>6.1 Pausing or Cancelling a Repeat Order</h3>
<ul>
<li>You may pause or cancel a repeat order at any time, provided the next scheduled order has not yet entered clinical review.</li>
<li>To avoid being charged for an upcoming order, please contact us at least 5 working days before your next scheduled dispatch date.</li>
<li>If your repeat order has already entered clinical review or been prescribed, it is subject to the same cancellation rules as standard orders (see Section 2).</li>
</ul>

<h3>6.2 Payment and Refunds on Repeat Orders</h3>
<ul>
<li>Each repeat order follows the same pending authorisation model as a standard order. Payment is authorised when your repeat order is queued and captured only once your prescription is approved.</li>
<li>If a repeat order is cancelled before clinical review is completed, the authorisation is released and no charge is made.</li>
<li>If a repeat order has been prescribed and dispensed, it is not eligible for a refund.</li>
</ul>

<h3>6.3 Dose or Treatment Adjustments</h3>
<ul>
<li>If your prescriber adjusts your prescribed dose or treatment between repeat orders, your upcoming order will be updated accordingly before dispatch.</li>
<li>Any resulting overpayment will be refunded to your original payment method. Any underpayment will be collected before dispatch.</li>
</ul>

<h3>6.4 Missed or Delayed Doses</h3>
<ul>
<li>If you need to skip or delay a repeat order, please contact us in advance. Medications that have already been dispensed cannot be refunded or held for future delivery.</li>
</ul>

<h2>7. Your Rights Under UK Consumer Law</h2>
<p>Prescription medications are exempt from the standard 14-day right to cancel that applies to most distance purchases under Regulation 28(1)(b) of the Consumer Contracts (Information, Cancellation and Additional Charges) Regulations 2013. This exemption applies because prescription medicines are prepared and dispensed specifically for an individual patient following a clinical assessment and cannot be resold or reused.</p>
<p>Non-prescription products (such as supplements or over-the-counter items, if offered) are not exempt from the 14-day cancellation right and may be returned within 14 days of receipt in their original, unopened condition for a full refund, provided they are not sealed goods unsealed after delivery where return is inappropriate for health or hygiene reasons.</p>
<p>Nothing in this policy limits or excludes your statutory rights under the Consumer Rights Act 2015 or any other applicable legislation, including your right to a repair, replacement, or refund where goods are faulty or not as described.</p>

<h2>8. Complaints</h2>
<p>If you are dissatisfied with how your cancellation or refund request has been handled, please contact us at support@togetherclinic.co.uk. We aim to acknowledge all complaints within two working days and to resolve them within 14 working days.</p>
<p>If you remain dissatisfied following our response, you may raise a concern with the General Pharmaceutical Council (GPhC) at www.pharmacyregulation.org, or seek independent advice from Citizens Advice (www.citizensadvice.org.uk). For unresolved disputes relating to online purchases, you may also refer your complaint to an approved Alternative Dispute Resolution (ADR) provider.</p>

<h2>9. Contact Us</h2>
<p>To request a cancellation or refund, or if you have any questions about this policy, please contact our team:</p>
<p><strong>Company:</strong> At Health Ltd (t/a Together Clinic)<br>
<strong>Website:</strong> www.togetherclinic.co.uk<br>
<strong>Email:</strong> support@togetherclinic.co.uk</p>
<p>Early contact gives us the best chance of resolving your request before a prescription has been issued.</p>

<h2>10. Regulatory Information</h2>
<p>Together Clinic is operated by At Health Ltd, a pharmacy registered with the General Pharmaceutical Council (GPhC). Our Superintendent Pharmacist is Ahmed Nizar Al-Liabi (GPhC No.: 2208502, Independent Prescriber).</p>
<p><strong>GPhC Register:</strong> <a href="https://www.pharmacyregulation.org/registers/pharmacist/2208502" target="_blank" rel="noopener noreferrer">https://www.pharmacyregulation.org/registers/pharmacist/2208502</a></p>

<p>&copy; At Health Ltd. All rights reserved. Together Clinic is a trading name of At Health Ltd.</p>
REFUND_HTML;
}

/**
 * Default Privacy Policy content (client-supplied, July 2025).
 * Used as the default_value for the pp_content ACF field.
 * Base typography styled by .tm-content; blue info box by .rp-stages;
 * processors table by .tm-table-wrap / .tm-content table (all in terms.css).
 *
 * NOTE: the source document's internal "Action required before publishing"
 * dev-note (booking-system provider TBC) is deliberately NOT rendered here —
 * it is an instruction to the client, not policy text. The booking-system
 * processor row is marked "To be confirmed" and must be completed before
 * this policy goes live.
 */
function ah_privacy_policy_default_content() {
    return <<<'PRIVACY_HTML'
<p><strong>Last updated: July 2025</strong></p>

<h2>1. Introduction</h2>
<p>Together Clinic is operated by At Health Ltd, a GPhC-registered online pharmacy and independent prescribing service. We are committed to protecting your privacy and handling your personal information responsibly, securely and transparently.</p>
<p>This Privacy Policy explains how we collect, use, store, share and protect your personal information when you visit our website at www.togetherclinic.co.uk, book a consultation, request a prescription, purchase medication or otherwise interact with us online.</p>
<p>Because Together Clinic provides prescription medication and clinical healthcare services — including weight management treatments — some of the personal information we process is classified as special category health data under UK data protection law. We take our obligations in respect of this data extremely seriously.</p>
<p>By using our website and services, you acknowledge that you have read and understood this Privacy Policy. Please read it carefully before submitting any personal information to us.</p>

<h2>2. Who We Are — Data Controller</h2>
<p>At Health Ltd (trading as Together Clinic) is the Data Controller responsible for your personal information. As Data Controller, we determine how and why your personal data is processed, and we are responsible for ensuring that processing is carried out in accordance with the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018.</p>
<p><strong>Company:</strong> At Health Ltd (t/a Together Clinic)<br>
<strong>Website:</strong> www.togetherclinic.co.uk<br>
<strong>Email:</strong> support@togetherclinic.co.uk<br>
<strong>Superintendent Pharmacist:</strong> Ahmed Nizar Al-Liabi (GPhC No.: 2208502, Independent Prescriber)</p>
<p>If you have any questions about how we handle your personal data, or wish to exercise your data protection rights, please contact us at support@togetherclinic.co.uk.</p>

<h2>3. Information We Collect</h2>

<h3>3.1 Personal Information You Provide</h3>
<p>We collect personal information that you provide directly to us, including when you register, book a consultation, complete a health assessment or place an order. This may include:</p>
<ul>
<li>Full name and date of birth</li>
<li>Contact details — email address, telephone number, postal address</li>
<li>Account login credentials</li>
<li>Consultation and health assessment responses</li>
<li>Medical history, current medications and health conditions</li>
<li>Prescription and treatment information</li>
<li>Payment information (processed securely via Stripe — we do not store full card details)</li>
<li>Identity verification documents (where required)</li>
<li>GP details and NHS number (where relevant)</li>
<li>Communications with our clinical and support teams</li>
</ul>

<h3>3.2 Special Category Health Data</h3>
<p>As a clinical prescribing service offering weight management treatments and other prescription medications, we necessarily process special category health data about you. This includes information about your physical health, medical history, weight, BMI, existing conditions and current treatments.</p>
<div class="rp-stages">
  <p>Special category health data receives additional legal protection under UK GDPR. We process this data only where we have a lawful basis to do so, and handle it with the highest standards of confidentiality in accordance with GPhC and NHS guidelines.</p>
</div>

<h3>3.3 Technical Information Collected Automatically</h3>
<p>When you visit our website, we may automatically collect certain technical information, including:</p>
<ul>
<li>IP address and approximate location</li>
<li>Browser type, version and language</li>
<li>Device type and operating system</li>
<li>Pages visited, links clicked and time spent on pages</li>
<li>Referring URL (how you arrived at our website)</li>
<li>Date and time of access</li>
<li>Cookie identifiers and session data</li>
</ul>
<p>This information is used to maintain website security, analyse usage patterns and improve the performance and usability of our website.</p>

<h2>4. How We Use Your Information</h2>
<p>We use your personal information for the following purposes:</p>
<ul>
<li>To create and manage your patient account</li>
<li>To conduct clinical consultations and health assessments</li>
<li>To issue, process and manage prescriptions</li>
<li>To dispense and deliver prescription medications</li>
<li>To process payments for consultations and medication orders</li>
<li>To manage your treatment plan, including repeat prescriptions and dose adjustments</li>
<li>To send you appointment confirmations, order updates and clinical communications</li>
<li>To verify your identity where required for regulatory compliance</li>
<li>To respond to your enquiries and provide patient support</li>
<li>To comply with our legal, regulatory and professional obligations as a GPhC-registered pharmacy</li>
<li>To prevent fraud, misuse of our services and protect patient safety</li>
<li>To improve our website, services and clinical processes</li>
<li>To send you marketing communications about our services where you have given your explicit consent</li>
</ul>
<p>You may withdraw your consent to marketing communications at any time by contacting us at support@togetherclinic.co.uk or by using the unsubscribe link in any marketing email.</p>

<h2>5. Legal Basis for Processing</h2>
<p>We process your personal information on one or more of the following lawful bases under UK GDPR:</p>
<ul>
<li><strong>Contract.</strong> Processing is necessary to provide our services to you — including conducting consultations, issuing prescriptions and delivering medication.</li>
<li><strong>Legal obligation.</strong> Processing is necessary to comply with our obligations as a GPhC-registered pharmacy, under MHRA regulations, the Human Medicines Regulations 2012, and other applicable legislation.</li>
<li><strong>Legitimate interests.</strong> Processing is necessary for our legitimate interests in operating and improving our services, preventing fraud and maintaining website security, where these interests are not overridden by your data protection rights.</li>
<li><strong>Consent.</strong> Where we rely on consent — including for marketing communications and, where required, for the processing of special category health data — we will ask for your explicit consent at the appropriate time. You may withdraw consent at any time.</li>
</ul>

<h3>5.1 Special Category Health Data</h3>
<p>Where we process special category health data, we do so under Article 9(2)(h) UK GDPR — processing necessary for the purposes of preventive or occupational medicine, medical diagnosis, the provision of health or social care or treatment — and/or with your explicit consent under Article 9(2)(a). All such processing is carried out under the obligation of professional secrecy in accordance with GPhC standards.</p>

<h2>6. Sharing Your Information</h2>
<p>We do not sell, rent or trade your personal information. We may share your information with carefully selected third-party organisations only where necessary to deliver our services, operate our systems or comply with legal obligations.</p>
<p>All third-party providers are required to process your personal information securely, confidentially and in accordance with applicable data protection law. Where required, we have Data Processing Agreements in place with our providers.</p>

<h3>6.1 Third-Party Service Providers</h3>
<p>The third parties we currently work with include:</p>
<div class="tm-table-wrap">
<table>
  <thead>
    <tr><th>Provider</th><th>Purpose</th><th>Privacy Policy</th></tr>
  </thead>
  <tbody>
    <tr><td>Stripe</td><td>Payment processing for consultations and medication orders</td><td><a href="https://stripe.com/gb/privacy" target="_blank" rel="noopener noreferrer">stripe.com/gb/privacy</a></td></tr>
    <tr><td>Gildhart (PharmoDigital Ltd)</td><td>Digital marketing, website design and management</td><td><a href="https://gildhart.com" target="_blank" rel="noopener noreferrer">gildhart.com</a></td></tr>
    <tr><td>Google Analytics</td><td>Website traffic and usage analytics</td><td><a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">policies.google.com/privacy</a></td></tr>
    <tr><td>Kinsta</td><td>Website hosting and infrastructure</td><td><a href="https://kinsta.com/legal/privacy-policy" target="_blank" rel="noopener noreferrer">kinsta.com/legal/privacy-policy</a></td></tr>
    <tr><td>Booking system</td><td>Appointment scheduling</td><td>To be confirmed</td></tr>
  </tbody>
</table>
</div>

<h3>6.2 Clinical and Regulatory Disclosure</h3>
<p>We may also share your personal information in the following circumstances:</p>
<ul>
<li>With your GP or other treating clinicians where clinically necessary or where you have requested this.</li>
<li>With the General Pharmaceutical Council (GPhC), MHRA or other regulatory bodies where required by law or for inspection purposes.</li>
<li>With law enforcement or other authorities where required by law, court order or to protect the safety of patients or the public.</li>
<li>With our professional indemnity insurers where necessary in connection with a claim or complaint.</li>
</ul>

<h2>7. Cookies</h2>
<p>Our website uses cookies and similar technologies to provide core functionality, analyse how visitors use our website, remember your preferences and support our marketing activity.</p>

<h3>7.1 What Are Cookies?</h3>
<p>Cookies are small text files placed on your device when you visit a website. They allow the website to recognise your device on subsequent visits and store certain information about your preferences or activity.</p>

<h3>7.2 Types of Cookies We Use</h3>
<ul>
<li><strong>Strictly necessary cookies:</strong> Essential for the website to function. These cannot be disabled and do not require your consent. They include cookies that manage your session, maintain security and support the checkout process.</li>
<li><strong>Analytics cookies:</strong> Used to collect anonymised information about how visitors use our website (e.g. pages visited, time spent, referral source). We use Google Analytics for this purpose. You can opt out of Google Analytics tracking at tools.google.com/dlpage/gaoptout.</li>
<li><strong>Functional cookies:</strong> Allow the website to remember choices you have made (such as your preferred language or login status) to provide a more personalised experience.</li>
<li><strong>Marketing cookies:</strong> Used to track visits across websites and deliver advertising relevant to your interests. These are only set with your consent.</li>
</ul>

<h3>7.3 Managing Cookies</h3>
<p>When you first visit our website, you will be presented with a cookie consent banner allowing you to accept or decline non-essential cookies. You can update your preferences at any time via the cookie settings link in our website footer.</p>
<p>You can also manage or delete cookies through your browser settings. Note that disabling certain cookies may affect the functionality of our website. For more information, visit www.allaboutcookies.org.</p>

<h2>8. Data Security</h2>
<p>We implement appropriate technical, organisational and physical safeguards to protect your personal information against accidental loss, unauthorised access, misuse, alteration or disclosure. These measures include:</p>
<ul>
<li>Encryption of data in transit using TLS/SSL</li>
<li>Encrypted storage of sensitive personal and health data</li>
<li>Access controls restricting data access to authorised personnel only</li>
<li>Regular security assessments and staff training</li>
<li>Secure payment processing via Stripe — we do not store full card details on our systems</li>
</ul>
<p>While we take all reasonable steps to protect your information, no method of internet transmission or electronic storage is completely secure. If you have concerns about the security of your data, please contact us at support@togetherclinic.co.uk.</p>
<p>In the event of a personal data breach that is likely to result in a high risk to your rights and freedoms, we will notify you without undue delay in accordance with our obligations under UK GDPR.</p>

<h2>9. Data Retention</h2>
<p>We retain your personal information only for as long as necessary to fulfil the purposes for which it was collected, or to comply with our legal, regulatory and professional obligations.</p>
<ul>
<li><strong>Patient health and prescription records:</strong> Retained for a minimum of 8 years from the date of last treatment in accordance with NHS and GPhC guidance (or until the patient's 25th birthday if they were a child at the time of treatment, whichever is longer).</li>
<li><strong>Financial and transaction records:</strong> Retained for 7 years in accordance with HMRC requirements.</li>
<li><strong>Marketing consent records:</strong> Retained for as long as you remain an active subscriber, plus a reasonable period to evidence consent in the event of a complaint.</li>
<li><strong>Website analytics data:</strong> Retained in anonymised or aggregated form for up to 26 months (standard Google Analytics retention).</li>
<li><strong>General enquiry and contact data:</strong> Retained for up to 2 years from the date of last contact, unless a longer period is required.</li>
</ul>
<p>When your personal information is no longer required, it will be securely deleted or anonymised.</p>

<h2>10. Your Rights Under UK GDPR</h2>
<p>Under UK GDPR, you have the following rights in relation to your personal information:</p>
<ul>
<li><strong>Right of access.</strong> You may request a copy of the personal information we hold about you (a Subject Access Request).</li>
<li><strong>Right to rectification.</strong> You may request that we correct any inaccurate or incomplete personal information.</li>
<li><strong>Right to erasure.</strong> You may request that we delete your personal information where there is no longer a lawful basis for us to hold it — subject to our legal and regulatory retention obligations (see Section 9).</li>
<li><strong>Right to restrict processing.</strong> You may request that we limit how we use your personal information in certain circumstances.</li>
<li><strong>Right to object.</strong> You may object to processing based on our legitimate interests, including the use of your data for direct marketing purposes.</li>
<li><strong>Right to data portability.</strong> Where processing is based on consent or contract and carried out by automated means, you may request a copy of your data in a structured, commonly used and machine-readable format.</li>
<li><strong>Right to withdraw consent.</strong> Where we rely on consent as our lawful basis, you may withdraw that consent at any time without affecting the lawfulness of processing carried out before withdrawal.</li>
</ul>
<p>To exercise any of these rights, please contact us at support@togetherclinic.co.uk. We will respond to your request within one calendar month. In some cases, we may need to verify your identity before processing your request.</p>
<p>If you are not satisfied with our response, you have the right to lodge a complaint with the Information Commissioner's Office (ICO) at www.ico.org.uk or by calling 0303 123 1113.</p>

<h2>11. Children's Privacy</h2>
<p>Our services are intended for adults aged 18 and over. We do not knowingly collect personal information from individuals under the age of 18. If you believe that we have inadvertently collected information from a minor, please contact us immediately at support@togetherclinic.co.uk and we will take steps to delete it.</p>

<h2>12. International Data Transfers</h2>
<p>We aim to process and store your personal information within the United Kingdom and the European Economic Area (EEA). Where any of our third-party providers process data outside the UK or EEA, we ensure that appropriate safeguards are in place — such as Standard Contractual Clauses approved by the ICO — to protect your personal information in accordance with UK GDPR.</p>

<h2>13. Third-Party Links</h2>
<p>Our website may contain links to third-party websites, platforms or services. We are not responsible for the privacy practices or content of those websites. We encourage you to review the privacy policies of any third-party sites before providing your personal information.</p>

<h2>14. Changes to This Privacy Policy</h2>
<p>We may update this Privacy Policy from time to time to reflect changes in legislation, technology, our services or the third parties we work with. Any material changes will be published on this page together with an updated "Last Updated" date. Where changes are significant, we may also notify you by email.</p>
<p>We encourage you to review this page periodically to stay informed about how we protect your personal information.</p>

<h2>15. Contact Us</h2>
<p>If you have any questions about this Privacy Policy, wish to exercise your data protection rights, or have a concern about how we handle your personal information, please contact us:</p>
<p><strong>Company:</strong> At Health Ltd (t/a Together Clinic)<br>
<strong>Website:</strong> www.togetherclinic.co.uk<br>
<strong>Email:</strong> support@togetherclinic.co.uk</p>
<p>You also have the right to raise a concern directly with the Information Commissioner's Office (ICO):</p>
<p><strong>ICO website:</strong> www.ico.org.uk<br>
<strong>ICO helpline:</strong> 0303 123 1113</p>

<p>&copy; At Health Ltd. All rights reserved. Together Clinic is a trading name of At Health Ltd.</p>
PRIVACY_HTML;
}

/**
 * Get logo URL with fallback chain: ACF option > Customizer > theme SVG.
 */
function ah_logo_url() {
    // 1. ACF option
    if ( function_exists( 'get_field' ) ) {
        $acf_logo = get_field( 'site_logo', 'option' );
        if ( $acf_logo ) {
            return is_array( $acf_logo ) ? $acf_logo['url'] : wp_get_attachment_url( $acf_logo );
        }
    }
    // 2. Customizer
    $custom_logo_id = get_theme_mod( 'custom_logo' );
    if ( $custom_logo_id ) {
        return wp_get_attachment_url( $custom_logo_id );
    }
    // 3. Fallback SVG
    return get_theme_file_uri( 'assets/images/logo.svg' );
}

// ═══════════════════════════════════════════════
// ENQUEUE STYLES & SCRIPTS
// ═══════════════════════════════════════════════

add_action( 'wp_enqueue_scripts', function () {
    // ── Global assets ──
    // Google Fonts
    wp_enqueue_style( 'ah-google-fonts',
        'https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@400;500;600;700&display=swap',
        array(), null
    );

    // Global CSS
    wp_enqueue_style( 'ah-globals',
        get_theme_file_uri( 'assets/css/globals.css' ),
        array(), AH_VERSION
    );

    // Nav CSS
    wp_enqueue_style( 'ah-nav',
        get_theme_file_uri( 'assets/css/nav.css' ),
        array( 'ah-globals' ), AH_VERSION
    );

    // Theme stylesheet (metadata only)
    wp_enqueue_style( 'ah-style',
        get_stylesheet_uri(),
        array( 'ah-globals' ), AH_VERSION
    );

    // Nav JS
    wp_enqueue_script( 'ah-nav-js',
        get_theme_file_uri( 'assets/js/nav.js' ),
        array(), AH_VERSION, true
    );

    // Scroll Reveal JS (global)
    wp_enqueue_script( 'ah-scroll-reveal',
        get_theme_file_uri( 'assets/js/scroll-reveal.js' ),
        array(), AH_VERSION, true
    );

    // ── Page-specific assets ──
    $page_assets = array(
        'page-templates/page-home.php'            => 'home',
        'page-templates/page-treatments.php'       => 'treatments',
        'page-templates/page-mounjaro.php'         => 'mounjaro',
        'page-templates/page-wegovy.php'           => 'wegovy',
        'page-templates/page-eligibility.php'      => 'eligibility',
        'page-templates/page-switching.php'        => 'switching',
        'page-templates/page-about.php'            => 'about',
        'page-templates/page-contact.php'          => 'contact',
        'page-templates/page-customer-care.php'    => 'customer-care',
        'page-templates/page-health-hub.php'       => 'health-hub',
        'page-templates/page-reorder.php'          => 'reorder',
        'page-templates/page-terms.php'            => 'terms',
        'page-templates/page-refund-policy.php'    => 'terms', // shares terms.css (.tm-content + callouts)
        'page-templates/page-privacy-policy.php'   => 'terms', // shares terms.css (.tm-content + table)
    );

    foreach ( $page_assets as $template => $slug ) {
        if ( is_page_template( $template ) ) {
            $css_path = "assets/css/{$slug}.css";
            $js_path  = "assets/js/{$slug}.js";

            if ( file_exists( get_theme_file_path( $css_path ) ) ) {
                wp_enqueue_style( "ah-{$slug}",
                    get_theme_file_uri( $css_path ),
                    array( 'ah-globals' ), AH_VERSION
                );
            }

            if ( file_exists( get_theme_file_path( $js_path ) ) ) {
                wp_enqueue_script( "ah-{$slug}-js",
                    get_theme_file_uri( $js_path ),
                    array(), AH_VERSION, true
                );
            }
            break; // Only one template matches
        }
    }
} );

// ═══════════════════════════════════════════════
// INCLUDES
// ═══════════════════════════════════════════════

// ACF Options pages
require_once get_theme_file_path( 'inc/acf-options.php' );

// ACF Field definitions
require_once get_theme_file_path( 'inc/acf-fields.php' );

// Single-post EEAT box + auto Table of Contents
require_once get_theme_file_path( 'inc/post-clinical-content.php' );

// WooCommerce product setup (admin tool)
if ( class_exists( 'WooCommerce' ) ) {
    require_once get_theme_file_path( 'inc/woocommerce-setup.php' );

    // Custom email headings — warm, supportive tone
    add_filter( 'woocommerce_email_heading_customer_processing_order', function () {
        return 'Your Weight Loss Journey Begins';
    } );
    add_filter( 'woocommerce_email_heading_customer_completed_order', function () {
        return 'Your Medication Is On Its Way';
    } );
    add_filter( 'woocommerce_email_heading_customer_note', function () {
        return 'A Message From Your Care Team';
    } );

    // Set "From" name to AT Health
    add_filter( 'woocommerce_email_from_name', function () {
        return function_exists( 'ah_company_name' ) ? ah_company_name() : 'Together Clinic';
    } );
}

// ═══════════════════════════════════════════════
// PERMALINK & CATEGORY SETUP (on theme activation)
// ═══════════════════════════════════════════════

add_action( 'after_switch_theme', function () {
    // Set permalink structure
    global $wp_rewrite;
    $wp_rewrite->set_permalink_structure( '/health-hub/%postname%/' );
    $wp_rewrite->flush_rules();

    // Create default Health Hub categories
    $categories = array( 'Weight Loss', 'GLP-1 Medications', 'Nutrition', 'Lifestyle', 'Clinical Research' );
    foreach ( $categories as $cat ) {
        if ( ! term_exists( $cat, 'category' ) ) {
            wp_insert_term( $cat, 'category' );
        }
    }
} );

// Ensure permalink structure survives deployments
add_action( 'init', function () {
    if ( false === get_transient( 'ah_permalink_check' ) ) {
        global $wp_rewrite;
        if ( $wp_rewrite->permalink_structure !== '/health-hub/%postname%/' ) {
            $wp_rewrite->set_permalink_structure( '/health-hub/%postname%/' );
            $wp_rewrite->flush_rules();
        }
        set_transient( 'ah_permalink_check', true, HOUR_IN_SECONDS );
    }
} );

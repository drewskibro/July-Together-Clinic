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

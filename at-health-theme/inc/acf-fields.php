<?php
/**
 * ACF Field Group Definitions for AT Health
 *
 * Field series organisation:
 *   A1-A9:  Global Options (branding, contact, compliance, social, navigation)
 *   B1-B13: Home Page sections
 *   C1-C3:  Blog Post fields
 *   D1-D11: Mounjaro page
 *   E1-E11: Wegovy page
 *   F1-F8:  Treatments page
 *   G1-G8:  Eligibility page
 *   H1-H8:  Switching Providers page
 *   I1-I6:  About page
 *   J1-J5:  Contact page
 *   K1-K5:  Customer Care page
 *   L1-L5:  Health Hub page
 *   M1-M5:  Reorder page
 *   N1-N3:  Terms page
 *
 * All field keys use the pattern: field_ah_[context]_[name]
 * All field names match the ah_field() / ah_option() calls in templates.
 *
 * IMPORTANT: Image fields must use return_format => 'id', never 'url'.
 * IMPORTANT: Never use empty() to check ACF values — use === null || === ''.
 */

if ( ! function_exists( 'acf_add_local_field_group' ) ) {
    return;
}

// ═══════════════════════════════════════════════
// A-SERIES: GLOBAL OPTIONS
// ═══════════════════════════════════════════════

// A1: Branding
acf_add_local_field_group( array(
    'key'      => 'group_ah_a1_branding',
    'title'    => 'A1 — Branding',
    'fields'   => array(
        array( 'key' => 'field_ah_site_logo', 'label' => 'Site Logo', 'name' => 'site_logo', 'type' => 'image', 'return_format' => 'id', 'instructions' => 'Upload the Together Clinic logo. Recommended: SVG or PNG with transparency.' ),
        array( 'key' => 'field_ah_company_name', 'label' => 'Company Name', 'name' => 'company_name', 'type' => 'text', 'default_value' => 'Together Clinic' ),
        array( 'key' => 'field_ah_company_legal_name', 'label' => 'Company Legal Name', 'name' => 'company_legal_name', 'type' => 'text', 'default_value' => 'Together Clinic Ltd' ),
        array( 'key' => 'field_ah_company_registration', 'label' => 'Company Registration Text', 'name' => 'company_registration', 'type' => 'text', 'default_value' => 'Company registered in England & Wales.' ),
        array( 'key' => 'field_ah_footer_tagline', 'label' => 'Footer Tagline', 'name' => 'footer_tagline', 'type' => 'text', 'default_value' => 'Medical Weight Loss, Delivered' ),
    ),
    'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'ah-settings-branding' ) ) ),
) );

// A2: Contact & Details
acf_add_local_field_group( array(
    'key'      => 'group_ah_a2_contact',
    'title'    => 'A2 — Contact & Details',
    'fields'   => array(
        array( 'key' => 'field_ah_business_hours', 'label' => 'Business Hours', 'name' => 'business_hours', 'type' => 'text', 'default_value' => '9am - 5pm, Monday to Friday' ),
        array( 'key' => 'field_ah_email_address', 'label' => 'Email Address', 'name' => 'email_address', 'type' => 'email', 'default_value' => 'info@togetherclinic.co.uk' ),
        array( 'key' => 'field_ah_email_response_time', 'label' => 'Email Response Time', 'name' => 'email_response_time', 'type' => 'text', 'default_value' => 'Reply within 4 hours' ),
        array( 'key' => 'field_ah_no_phone_notice', 'label' => 'No Phone Consultations Notice', 'name' => 'no_phone_notice', 'type' => 'textarea', 'rows' => 2, 'default_value' => 'We do not offer telephone consultations. Please contact us via email or live chat.' ),
        array( 'key' => 'field_ah_eligibility_url', 'label' => 'Eligibility / Booking URL', 'name' => 'eligibility_url', 'type' => 'url', 'instructions' => 'The main CTA link used across the site.' ),
    ),
    'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'ah-settings-contact' ) ) ),
) );

// A3: Compliance
acf_add_local_field_group( array(
    'key'      => 'group_ah_a3_compliance',
    'title'    => 'A3 — Registration & Compliance',
    'fields'   => array(
        array( 'key' => 'field_ah_gphc_number', 'label' => 'GPhC Registration Number', 'name' => 'gphc_number', 'type' => 'text', 'default_value' => '2081354' ),
        array( 'key' => 'field_ah_superintendent', 'label' => 'Superintendent Pharmacist', 'name' => 'superintendent', 'type' => 'text', 'default_value' => 'Ms. Simona Pantaziu' ),
        array( 'key' => 'field_ah_company_number', 'label' => 'Company Number', 'name' => 'company_number', 'type' => 'text', 'default_value' => '08563110' ),
        array( 'key' => 'field_ah_registered_name', 'label' => 'Registered Business Name', 'name' => 'registered_name', 'type' => 'text', 'default_value' => 'Prescription Point Ltd' ),
        array( 'key' => 'field_ah_registered_address', 'label' => 'Registered Address', 'name' => 'registered_address', 'type' => 'textarea', 'default_value' => '14-16 Ashton Road, Denton, Manchester M34 3EX', 'rows' => 3 ),
        array( 'key' => 'field_ah_trust_badge_1', 'label' => 'Trust Badge 1', 'name' => 'trust_badge_1', 'type' => 'text', 'default_value' => 'GPhC & MHRA Regulated' ),
        array( 'key' => 'field_ah_trust_badge_2', 'label' => 'Trust Badge 2', 'name' => 'trust_badge_2', 'type' => 'text', 'default_value' => '4.9/5 from 10,000+ patients' ),
        array( 'key' => 'field_ah_trust_badge_3', 'label' => 'Trust Badge 3', 'name' => 'trust_badge_3', 'type' => 'text', 'default_value' => '256-bit SSL Encrypted' ),
        array( 'key' => 'field_ah_trust_badge_4', 'label' => 'Trust Badge 4', 'name' => 'trust_badge_4', 'type' => 'text', 'default_value' => 'Tracked 48h Delivery' ),
    ),
    'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'ah-settings-compliance' ) ) ),
) );

// A4: Social Media
acf_add_local_field_group( array(
    'key'      => 'group_ah_a4_social',
    'title'    => 'A4 — Social Media',
    'fields'   => array(
        array( 'key' => 'field_ah_facebook_url', 'label' => 'Facebook URL', 'name' => 'facebook_url', 'type' => 'url' ),
        array( 'key' => 'field_ah_instagram_url', 'label' => 'Instagram URL', 'name' => 'instagram_url', 'type' => 'url' ),
        array( 'key' => 'field_ah_twitter_url', 'label' => 'Twitter/X URL', 'name' => 'twitter_url', 'type' => 'url' ),
        array( 'key' => 'field_ah_linkedin_url', 'label' => 'LinkedIn URL', 'name' => 'linkedin_url', 'type' => 'url' ),
    ),
    'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'ah-settings-social' ) ) ),
) );

// A5: Navigation & CTAs
acf_add_local_field_group( array(
    'key'      => 'group_ah_a5_navigation',
    'title'    => 'A5 — Navigation & CTAs',
    'fields'   => array(
        array( 'key' => 'field_ah_top_banner_text', 'label' => 'Top Banner Text', 'name' => 'top_banner_text', 'type' => 'text', 'default_value' => 'Switch to Wegovy and save up to 27%' ),
        array( 'key' => 'field_ah_top_banner_link_text', 'label' => 'Top Banner Link Text', 'name' => 'top_banner_link_text', 'type' => 'text', 'default_value' => 'Check eligibility' ),
        array( 'key' => 'field_ah_nav_cta_text', 'label' => 'Nav CTA Button Text', 'name' => 'nav_cta_text', 'type' => 'text', 'default_value' => 'Start Journey →' ),
        array( 'key' => 'field_ah_footer_cta_text', 'label' => 'Footer CTA Text', 'name' => 'footer_cta_text', 'type' => 'text', 'default_value' => 'Start Your Journey' ),
        array( 'key' => 'field_ah_privacy_url', 'label' => 'Privacy Policy URL', 'name' => 'privacy_url', 'type' => 'url' ),
        array( 'key' => 'field_ah_cookies_url', 'label' => 'Cookies Policy URL', 'name' => 'cookies_url', 'type' => 'url' ),
        array( 'key' => 'field_ah_accessibility_url', 'label' => 'Accessibility URL', 'name' => 'accessibility_url', 'type' => 'url' ),
    ),
    'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'ah-settings-navigation' ) ) ),
) );

// ═══════════════════════════════════════════════
// B-SERIES: HOME PAGE
// ═══════════════════════════════════════════════

acf_add_local_field_group( array(
    'key'      => 'group_ah_b1_home_hero',
    'title'    => 'B1 — Home: Hero Section',
    'fields'   => array(
        array( 'key' => 'field_ah_hero_eyebrow', 'label' => 'Eyebrow Text', 'name' => 'hero_eyebrow', 'type' => 'text', 'default_value' => 'Clinically Proven Weight Loss' ),
        array( 'key' => 'field_ah_hero_title', 'label' => 'Headline (HTML allowed)', 'name' => 'hero_title', 'type' => 'textarea', 'rows' => 3, 'instructions' => 'HTML allowed for colour spans.' ),
        array( 'key' => 'field_ah_hero_subtitle', 'label' => 'Subtitle', 'name' => 'hero_subtitle', 'type' => 'textarea', 'rows' => 3 ),
        array( 'key' => 'field_ah_hero_cta_text', 'label' => 'CTA Button Text', 'name' => 'hero_cta_text', 'type' => 'text', 'default_value' => 'Start Your Journey' ),
        array( 'key' => 'field_ah_hero_cta_url', 'label' => 'CTA Button URL', 'name' => 'hero_cta_url', 'type' => 'url', 'instructions' => 'Leave blank to use default eligibility URL.' ),
        array( 'key' => 'field_ah_hero_image', 'label' => 'Hero Image', 'name' => 'hero_image', 'type' => 'image', 'return_format' => 'id' ),
        array( 'key' => 'field_ah_hero_image_alt', 'label' => 'Hero Image Alt Text', 'name' => 'hero_image_alt', 'type' => 'text' ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-home.php' ) ) ),
) );

acf_add_local_field_group( array(
    'key'      => 'group_ah_b2_home_hiw',
    'title'    => 'B2 — Home: How It Works',
    'fields'   => array(
        array( 'key' => 'field_ah_hiw_eyebrow', 'label' => 'Eyebrow', 'name' => 'hiw_eyebrow', 'type' => 'text', 'default_value' => 'Proven Process' ),
        array( 'key' => 'field_ah_hiw_title', 'label' => 'Title (HTML allowed)', 'name' => 'hiw_title', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_hiw_subtitle', 'label' => 'Subtitle', 'name' => 'hiw_subtitle', 'type' => 'textarea', 'rows' => 2 ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-home.php' ) ) ),
) );

acf_add_local_field_group( array(
    'key'      => 'group_ah_b3_home_testimonials',
    'title'    => 'B3 — Home: Testimonials',
    'fields'   => array(
        array( 'key' => 'field_ah_testimonials_badge', 'label' => 'Trust Badge Text', 'name' => 'testimonials_badge', 'type' => 'text', 'default_value' => 'Rated Excellent 4.9/5 by 10,000+ patients' ),
        array( 'key' => 'field_ah_testimonials_title', 'label' => 'Section Title', 'name' => 'testimonials_title', 'type' => 'text', 'default_value' => 'Life-Changing Results' ),
        array(
            'key'        => 'field_ah_testimonials_items',
            'label'      => 'Testimonials',
            'name'       => 'testimonials_items',
            'type'       => 'repeater',
            'min'        => 0,
            'max'        => 8,
            'layout'     => 'block',
            'sub_fields' => array(
                array( 'key' => 'field_ah_testimonial_name', 'label' => 'Name', 'name' => 'name', 'type' => 'text' ),
                array( 'key' => 'field_ah_testimonial_label', 'label' => 'Label', 'name' => 'label', 'type' => 'text', 'default_value' => 'Verified Patient' ),
                array( 'key' => 'field_ah_testimonial_text', 'label' => 'Testimonial Text', 'name' => 'text', 'type' => 'textarea', 'rows' => 3 ),
            ),
        ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-home.php' ) ) ),
) );

acf_add_local_field_group( array(
    'key'      => 'group_ah_b4_home_faq',
    'title'    => 'B4 — Home: FAQ',
    'fields'   => array(
        array( 'key' => 'field_ah_faq_eyebrow', 'label' => 'Eyebrow', 'name' => 'faq_eyebrow', 'type' => 'text', 'default_value' => 'Common Questions' ),
        array( 'key' => 'field_ah_faq_title', 'label' => 'Title', 'name' => 'faq_title', 'type' => 'text', 'default_value' => "Got Questions? We've Got Answers" ),
        array( 'key' => 'field_ah_faq_subtitle', 'label' => 'Subtitle', 'name' => 'faq_subtitle', 'type' => 'text', 'default_value' => 'Everything you need to know before starting your weight loss journey.' ),
        array( 'key' => 'field_ah_faq_still_questions', 'label' => 'Still Questions Text', 'name' => 'faq_still_questions', 'type' => 'text', 'default_value' => 'Still have questions?' ),
        array( 'key' => 'field_ah_faq_chat_text', 'label' => 'Chat Link Text', 'name' => 'faq_chat_text', 'type' => 'text', 'default_value' => 'Chat with our team' ),
        array(
            'key'        => 'field_ah_faq_items',
            'label'      => 'FAQ Items',
            'name'       => 'faq_items',
            'type'       => 'repeater',
            'min'        => 0,
            'max'        => 12,
            'layout'     => 'block',
            'sub_fields' => array(
                array( 'key' => 'field_ah_faq_question', 'label' => 'Question', 'name' => 'question', 'type' => 'text' ),
                array( 'key' => 'field_ah_faq_answer', 'label' => 'Answer', 'name' => 'answer', 'type' => 'textarea', 'rows' => 4 ),
            ),
        ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-home.php' ) ) ),
) );

// ═══════════════════════════════════════════════
// D-SERIES: MOUNJARO PAGE
// ═══════════════════════════════════════════════

acf_add_local_field_group( array(
    'key'      => 'group_ah_d1_mounjaro',
    'title'    => 'D1 — Mounjaro: All Fields',
    'fields'   => array(
        array( 'key' => 'field_ah_mj_eyebrow', 'label' => 'Eyebrow', 'name' => 'mj_eyebrow', 'type' => 'text', 'default_value' => 'Tirzepatide · Once Weekly' ),
        array( 'key' => 'field_ah_mj_rating_text', 'label' => 'Rating Text', 'name' => 'mj_rating_text', 'type' => 'text', 'default_value' => '4.9 · 2,847 reviews' ),
        array( 'key' => 'field_ah_mj_title', 'label' => 'Page Title', 'name' => 'mj_title', 'type' => 'text', 'default_value' => 'Mounjaro' ),
        array( 'key' => 'field_ah_mj_description', 'label' => 'Description', 'name' => 'mj_description', 'type' => 'textarea', 'rows' => 4 ),
        array( 'key' => 'field_ah_mj_product_image', 'label' => 'Product Image', 'name' => 'mj_product_image', 'type' => 'image', 'return_format' => 'id' ),
        array( 'key' => 'field_ah_mj_price', 'label' => 'Price (number only)', 'name' => 'mj_price', 'type' => 'text', 'default_value' => '199' ),
        array( 'key' => 'field_ah_mj_cta_text', 'label' => 'CTA Text', 'name' => 'mj_cta_text', 'type' => 'text', 'default_value' => 'Start Journey →' ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-mounjaro.php' ) ) ),
) );

// ═══════════════════════════════════════════════
// E-SERIES: WEGOVY PAGE
// ═══════════════════════════════════════════════

acf_add_local_field_group( array(
    'key'      => 'group_ah_e1_wegovy',
    'title'    => 'E1 — Wegovy: All Fields',
    'fields'   => array(
        array( 'key' => 'field_ah_wg_eyebrow', 'label' => 'Eyebrow', 'name' => 'wg_eyebrow', 'type' => 'text', 'default_value' => 'Semaglutide · Once Weekly' ),
        array( 'key' => 'field_ah_wg_rating_text', 'label' => 'Rating Text', 'name' => 'wg_rating_text', 'type' => 'text', 'default_value' => '4.9 · 3,124 reviews' ),
        array( 'key' => 'field_ah_wg_title', 'label' => 'Page Title', 'name' => 'wg_title', 'type' => 'text', 'default_value' => 'Wegovy' ),
        array( 'key' => 'field_ah_wg_description', 'label' => 'Description', 'name' => 'wg_description', 'type' => 'textarea', 'rows' => 4 ),
        array( 'key' => 'field_ah_wg_product_image', 'label' => 'Product Image', 'name' => 'wg_product_image', 'type' => 'image', 'return_format' => 'id' ),
        array( 'key' => 'field_ah_wg_price', 'label' => 'Price (number only)', 'name' => 'wg_price', 'type' => 'text', 'default_value' => '179' ),
        array( 'key' => 'field_ah_wg_cta_text', 'label' => 'CTA Text', 'name' => 'wg_cta_text', 'type' => 'text', 'default_value' => 'Start Journey →' ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-wegovy.php' ) ) ),
) );

// ═══════════════════════════════════════════════
// ADDITIONAL HOME PAGE FIELDS (B5-B8)
// ═══════════════════════════════════════════════

// B5: Home — Calculator
acf_add_local_field_group( array(
    'key'   => 'group_ah_b5_home_calc',
    'title' => 'B5 — Home: Calculator',
    'fields' => array(
        array( 'key' => 'field_ah_calc_pill_text', 'label' => 'Pill Badge Text', 'name' => 'calc_pill_text', 'type' => 'text', 'default_value' => 'Takes 10 Seconds' ),
        array( 'key' => 'field_ah_calc_title', 'label' => 'Calculator Title (HTML)', 'name' => 'calc_title', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_calc_subtitle', 'label' => 'Calculator Subtitle', 'name' => 'calc_subtitle', 'type' => 'text' ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-home.php' ) ) ),
) );

// B6: Home — Treatment Showcase
acf_add_local_field_group( array(
    'key'   => 'group_ah_b6_home_treatments',
    'title' => 'B6 — Home: Treatment Showcase',
    'fields' => array(
        array( 'key' => 'field_ah_treatments_eyebrow', 'label' => 'Eyebrow', 'name' => 'treatments_eyebrow', 'type' => 'text', 'default_value' => 'Trusted by 10,000+ patients' ),
        array( 'key' => 'field_ah_treatments_title', 'label' => 'Title (HTML)', 'name' => 'treatments_title', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_treatments_subtitle', 'label' => 'Subtitle', 'name' => 'treatments_subtitle', 'type' => 'text' ),
        array( 'key' => 'field_ah_home_mounjaro_image', 'label' => 'Mounjaro Card Image', 'name' => 'home_mounjaro_image', 'type' => 'image', 'return_format' => 'id' ),
        array( 'key' => 'field_ah_home_mounjaro_stat', 'label' => 'Mounjaro Stat', 'name' => 'home_mounjaro_stat', 'type' => 'text', 'default_value' => 'Up to 22.5% loss' ),
        array( 'key' => 'field_ah_home_mounjaro_desc', 'label' => 'Mounjaro Description', 'name' => 'home_mounjaro_desc', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_home_wegovy_image', 'label' => 'Wegovy Card Image', 'name' => 'home_wegovy_image', 'type' => 'image', 'return_format' => 'id' ),
        array( 'key' => 'field_ah_home_wegovy_stat', 'label' => 'Wegovy Stat', 'name' => 'home_wegovy_stat', 'type' => 'text', 'default_value' => 'Up to 20.7% loss' ),
        array( 'key' => 'field_ah_home_wegovy_desc', 'label' => 'Wegovy Description', 'name' => 'home_wegovy_desc', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_home_included_title', 'label' => "What's Included Title", 'name' => 'home_included_title', 'type' => 'text', 'default_value' => "What's Included" ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-home.php' ) ) ),
) );

// B7: Home — Stats
acf_add_local_field_group( array(
    'key'   => 'group_ah_b7_home_stats',
    'title' => 'B7 — Home: Stats',
    'fields' => array(
        array( 'key' => 'field_ah_stats_eyebrow', 'label' => 'Eyebrow', 'name' => 'stats_eyebrow', 'type' => 'text', 'default_value' => 'Why Patients Choose Us' ),
        array( 'key' => 'field_ah_stats_title', 'label' => 'Title (HTML)', 'name' => 'stats_title', 'type' => 'textarea', 'rows' => 2 ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-home.php' ) ) ),
) );

// B7b: Home — Know Your Team
acf_add_local_field_group( array(
    'key'   => 'group_ah_b7b_home_team',
    'title' => 'B7b — Home: Know Your Team',
    'fields' => array(
        array( 'key' => 'field_ah_team_eyebrow', 'label' => 'Eyebrow', 'name' => 'team_eyebrow', 'type' => 'text', 'default_value' => 'Meet Your Pharmacists' ),
        array( 'key' => 'field_ah_team_title', 'label' => 'Title', 'name' => 'team_title', 'type' => 'text', 'default_value' => 'Know Your Team' ),
        array( 'key' => 'field_ah_team_subtitle', 'label' => 'Subtitle', 'name' => 'team_subtitle', 'type' => 'textarea', 'rows' => 2, 'default_value' => 'Our pharmacists are fully registered with the General Pharmaceutical Council (GPhC). Click any registration number to verify on the official GPhC register.' ),

        array( 'key' => 'field_ah_team_member1_photo', 'label' => 'Member 1 Photo', 'name' => 'team_member1_photo', 'type' => 'image', 'return_format' => 'id' ),
        array( 'key' => 'field_ah_team_member1_name', 'label' => 'Member 1 Name', 'name' => 'team_member1_name', 'type' => 'text', 'default_value' => 'Ahmed Nizar Al-Liabi' ),
        array( 'key' => 'field_ah_team_member1_role', 'label' => 'Member 1 Role', 'name' => 'team_member1_role', 'type' => 'text', 'default_value' => 'Superintendent Pharmacist' ),
        array( 'key' => 'field_ah_team_member1_gphc_number', 'label' => 'Member 1 GPhC Number', 'name' => 'team_member1_gphc_number', 'type' => 'text', 'default_value' => '2208502' ),
        array( 'key' => 'field_ah_team_member1_gphc_url', 'label' => 'Member 1 GPhC URL', 'name' => 'team_member1_gphc_url', 'type' => 'url', 'default_value' => 'https://www.pharmacyregulation.org/registers/pharmacist' ),

        array( 'key' => 'field_ah_team_member2_photo', 'label' => 'Member 2 Photo', 'name' => 'team_member2_photo', 'type' => 'image', 'return_format' => 'id' ),
        array( 'key' => 'field_ah_team_member2_name', 'label' => 'Member 2 Name', 'name' => 'team_member2_name', 'type' => 'text', 'default_value' => 'Sunil Thacker' ),
        array( 'key' => 'field_ah_team_member2_role', 'label' => 'Member 2 Role', 'name' => 'team_member2_role', 'type' => 'text', 'default_value' => 'Independent Pharmacist Prescriber' ),
        array( 'key' => 'field_ah_team_member2_gphc_number', 'label' => 'Member 2 GPhC Number', 'name' => 'team_member2_gphc_number', 'type' => 'text', 'default_value' => '2047968' ),
        array( 'key' => 'field_ah_team_member2_gphc_url', 'label' => 'Member 2 GPhC URL', 'name' => 'team_member2_gphc_url', 'type' => 'url', 'default_value' => 'https://www.pharmacyregulation.org/registers/pharmacist' ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-home.php' ) ) ),
) );

// B7c: Home — Health Hub Snapshot
acf_add_local_field_group( array(
    'key'   => 'group_ah_b7c_home_health_hub',
    'title' => 'B7c — Home: Health Hub',
    'fields' => array(
        array( 'key' => 'field_ah_health_hub_eyebrow', 'label' => 'Eyebrow', 'name' => 'health_hub_eyebrow', 'type' => 'text', 'default_value' => 'HEALTH HUB' ),
        array( 'key' => 'field_ah_health_hub_heading', 'label' => 'Heading', 'name' => 'health_hub_heading', 'type' => 'text', 'default_value' => 'Know More. Feel Better.' ),
        array( 'key' => 'field_ah_health_hub_subheading', 'label' => 'Subheading', 'name' => 'health_hub_subheading', 'type' => 'textarea', 'rows' => 2, 'default_value' => 'Honest health guidance from our pharmacist prescribers — written for real people, not medical textbooks.' ),

        array( 'key' => 'field_ah_health_hub_hero_category', 'label' => 'Hero Category', 'name' => 'health_hub_hero_category', 'type' => 'text', 'default_value' => 'Weight Loss' ),
        array( 'key' => 'field_ah_health_hub_hero_image', 'label' => 'Hero Image', 'name' => 'health_hub_hero_image', 'type' => 'image', 'return_format' => 'id', 'instructions' => 'Large featured image for the hero article card. Recommended 1600x900px.' ),
        array( 'key' => 'field_ah_health_hub_hero_title', 'label' => 'Hero Title', 'name' => 'health_hub_hero_title', 'type' => 'text', 'default_value' => 'Why Starting Your Weight Loss Journey Earlier Makes All the Difference' ),
        array( 'key' => 'field_ah_health_hub_hero_excerpt', 'label' => 'Hero Excerpt', 'name' => 'health_hub_hero_excerpt', 'type' => 'textarea', 'rows' => 2, 'default_value' => 'Why starting early makes all the difference to your long-term weight loss success and overall health.' ),
        array( 'key' => 'field_ah_health_hub_hero_read_time', 'label' => 'Hero Read Time', 'name' => 'health_hub_hero_read_time', 'type' => 'text', 'default_value' => '5 min read' ),
        array( 'key' => 'field_ah_health_hub_hero_url', 'label' => 'Hero URL', 'name' => 'health_hub_hero_url', 'type' => 'url', 'default_value' => '/health-hub/why-starting-your-weight-loss-journey-earlier-makes-all-the-difference/' ),

        array(
            'key'        => 'field_ah_health_hub_cards',
            'label'      => 'Supporting Cards (4 items)',
            'name'       => 'health_hub_cards',
            'type'       => 'repeater',
            'min'        => 4,
            'max'        => 4,
            'layout'     => 'block',
            'button_label' => 'Add Card',
            'sub_fields' => array(
                array( 'key' => 'field_ah_card_category', 'label' => 'Category', 'name' => 'card_category', 'type' => 'text' ),
                array( 'key' => 'field_ah_card_image', 'label' => 'Image', 'name' => 'card_image', 'type' => 'image', 'return_format' => 'id', 'instructions' => 'Card image. Recommended 800x600px.' ),
                array( 'key' => 'field_ah_card_title', 'label' => 'Title', 'name' => 'card_title', 'type' => 'text' ),
                array( 'key' => 'field_ah_card_excerpt', 'label' => 'Excerpt', 'name' => 'card_excerpt', 'type' => 'textarea', 'rows' => 2 ),
                array( 'key' => 'field_ah_card_read_time', 'label' => 'Read Time', 'name' => 'card_read_time', 'type' => 'text' ),
                array( 'key' => 'field_ah_card_url', 'label' => 'URL', 'name' => 'card_url', 'type' => 'url' ),
            ),
        ),

        array( 'key' => 'field_ah_health_hub_sixth_category', 'label' => 'Sixth Article Category', 'name' => 'health_hub_sixth_category', 'type' => 'text', 'default_value' => 'Weight Loss' ),
        array( 'key' => 'field_ah_health_hub_sixth_image', 'label' => 'Sixth Article Image', 'name' => 'health_hub_sixth_image', 'type' => 'image', 'return_format' => 'id', 'instructions' => 'Image for the sixth article card. Recommended 1200x800px.' ),
        array( 'key' => 'field_ah_health_hub_sixth_title', 'label' => 'Sixth Article Title', 'name' => 'health_hub_sixth_title', 'type' => 'text', 'default_value' => 'Five Signs Your Weight Is Affecting Your Long-Term Health' ),
        array( 'key' => 'field_ah_health_hub_sixth_excerpt', 'label' => 'Sixth Article Excerpt', 'name' => 'health_hub_sixth_excerpt', 'type' => 'textarea', 'rows' => 2, 'default_value' => 'From breathlessness to disrupted sleep — five evidence-based signs worth taking seriously.' ),
        array( 'key' => 'field_ah_health_hub_sixth_read_time', 'label' => 'Sixth Article Read Time', 'name' => 'health_hub_sixth_read_time', 'type' => 'text', 'default_value' => '5 min read' ),
        array( 'key' => 'field_ah_health_hub_sixth_url', 'label' => 'Sixth Article URL', 'name' => 'health_hub_sixth_url', 'type' => 'url', 'default_value' => '/health-hub/five-signs-your-weight-is-affecting-your-long-term-health/' ),

        array( 'key' => 'field_ah_health_hub_cta_heading', 'label' => 'CTA Heading', 'name' => 'health_hub_cta_heading', 'type' => 'text', 'default_value' => 'Ready to take the first step?' ),
        array( 'key' => 'field_ah_health_hub_cta_subtext', 'label' => 'CTA Subtext', 'name' => 'health_hub_cta_subtext', 'type' => 'textarea', 'rows' => 2, 'default_value' => 'Answer a few quick questions and find out which treatment is right for you.' ),
        array( 'key' => 'field_ah_health_hub_cta_button_text', 'label' => 'CTA Button Text', 'name' => 'health_hub_cta_button_text', 'type' => 'text', 'default_value' => 'Check Your Eligibility' ),
        array( 'key' => 'field_ah_health_hub_cta_button_url', 'label' => 'CTA Button URL', 'name' => 'health_hub_cta_button_url', 'type' => 'url' ),

        array( 'key' => 'field_ah_health_hub_explore_url', 'label' => 'Explore All URL', 'name' => 'health_hub_explore_url', 'type' => 'url', 'default_value' => '/health-hub/' ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-home.php' ) ) ),
) );

// B8: Home — How It Works (step details)
acf_add_local_field_group( array(
    'key'   => 'group_ah_b8_home_hiw_steps',
    'title' => 'B8 — Home: How It Works Steps',
    'fields' => array(
        array( 'key' => 'field_ah_hiw_step1_title', 'label' => 'Step 1 Title (HTML)', 'name' => 'hiw_step1_title', 'type' => 'text' ),
        array( 'key' => 'field_ah_hiw_step1_description', 'label' => 'Step 1 Description', 'name' => 'hiw_step1_description', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_hiw_step1_badge', 'label' => 'Step 1 Badge', 'name' => 'hiw_step1_badge', 'type' => 'text', 'default_value' => 'Takes 5 minutes' ),
        array( 'key' => 'field_ah_hiw_step2_title', 'label' => 'Step 2 Title (HTML)', 'name' => 'hiw_step2_title', 'type' => 'text' ),
        array( 'key' => 'field_ah_hiw_step2_description', 'label' => 'Step 2 Description', 'name' => 'hiw_step2_description', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_hiw_step2_badge', 'label' => 'Step 2 Badge', 'name' => 'hiw_step2_badge', 'type' => 'text', 'default_value' => 'Same-day approval' ),
        array( 'key' => 'field_ah_hiw_step3_title', 'label' => 'Step 3 Title (HTML)', 'name' => 'hiw_step3_title', 'type' => 'text' ),
        array( 'key' => 'field_ah_hiw_step3_description', 'label' => 'Step 3 Description', 'name' => 'hiw_step3_description', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_hiw_step3_badge', 'label' => 'Step 3 Badge', 'name' => 'hiw_step3_badge', 'type' => 'text', 'default_value' => 'Within 48 hours' ),
        array( 'key' => 'field_ah_hiw_trust1', 'label' => 'Trust Point 1', 'name' => 'hiw_trust1', 'type' => 'text', 'default_value' => 'No prescription transfer needed' ),
        array( 'key' => 'field_ah_hiw_trust2', 'label' => 'Trust Point 2', 'name' => 'hiw_trust2', 'type' => 'text', 'default_value' => '100% confidential service' ),
        array( 'key' => 'field_ah_hiw_trust3', 'label' => 'Trust Point 3', 'name' => 'hiw_trust3', 'type' => 'text', 'default_value' => 'Cancel anytime' ),
        array( 'key' => 'field_ah_hiw_cta_text', 'label' => 'CTA Text', 'name' => 'hiw_cta_text', 'type' => 'text', 'default_value' => 'Start Journey' ),
        array( 'key' => 'field_ah_hiw_cta_url', 'label' => 'CTA URL', 'name' => 'hiw_cta_url', 'type' => 'url' ),
        array( 'key' => 'field_ah_hiw_social_proof', 'label' => 'Social Proof Text (HTML)', 'name' => 'hiw_social_proof', 'type' => 'textarea', 'rows' => 2 ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-home.php' ) ) ),
) );

// B9: Home — CTA Section (shared template part, but needs location rules)
acf_add_local_field_group( array(
    'key'   => 'group_ah_b9_cta',
    'title' => 'B9 — CTA Section',
    'fields' => array(
        array( 'key' => 'field_ah_cta_eyebrow', 'label' => 'Eyebrow', 'name' => 'cta_eyebrow', 'type' => 'text', 'default_value' => 'Your Transformation Awaits' ),
        array( 'key' => 'field_ah_cta_title', 'label' => 'Title (HTML)', 'name' => 'cta_title', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_cta_subtitle', 'label' => 'Subtitle', 'name' => 'cta_subtitle', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_cta_button_text', 'label' => 'Button Text', 'name' => 'cta_button_text', 'type' => 'text', 'default_value' => 'Start Your Journey' ),
        array( 'key' => 'field_ah_cta_button_url', 'label' => 'Button URL', 'name' => 'cta_button_url', 'type' => 'url' ),
        array( 'key' => 'field_ah_cta_trust1', 'label' => 'Trust 1', 'name' => 'cta_trust1', 'type' => 'text', 'default_value' => 'GPhC Regulated' ),
        array( 'key' => 'field_ah_cta_trust2', 'label' => 'Trust 2', 'name' => 'cta_trust2', 'type' => 'text', 'default_value' => 'Cancel Anytime' ),
        array( 'key' => 'field_ah_cta_trust3', 'label' => 'Trust 3', 'name' => 'cta_trust3', 'type' => 'text', 'default_value' => 'Discreet Delivery' ),
        array( 'key' => 'field_ah_cta_trust4', 'label' => 'Trust 4', 'name' => 'cta_trust4', 'type' => 'text', 'default_value' => 'Same-Day Approval' ),
    ),
    'location' => array(
        array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-home.php' ) ),
        array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-eligibility.php' ) ),
        array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-switching.php' ) ),
        array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-customer-care.php' ) ),
        array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-health-hub.php' ) ),
        array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-reorder.php' ) ),
    ),
) );

// B10: Home — FAQ Subtitle (missing from B4)
// Already handled — adding faq_subtitle to B4 group
// Note: faq_subtitle, faq_still_questions, faq_chat_text used in section-faq.php

// ═══════════════════════════════════════════════
// C-SERIES: BLOG POST FIELDS
// ═══════════════════════════════════════════════

acf_add_local_field_group( array(
    'key'   => 'group_ah_c1_blog',
    'title' => 'C1 — Blog Post Fields',
    'fields' => array(
        array( 'key' => 'field_ah_reading_time', 'label' => 'Reading Time (minutes)', 'name' => 'reading_time', 'type' => 'number', 'default_value' => '' ),
    ),
    'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ) ) ),
) );

// ═══════════════════════════════════════════════
// D-SERIES ADDITIONAL: MOUNJARO PAGE
// ═══════════════════════════════════════════════

acf_add_local_field_group( array(
    'key'   => 'group_ah_d2_mounjaro_extended',
    'title' => 'D2 — Mounjaro: Dosing & FAQ',
    'fields' => array(
        array( 'key' => 'field_ah_mj_price_includes', 'label' => 'Price Includes Text', 'name' => 'mj_price_includes', 'type' => 'text', 'default_value' => 'Includes medication, consultations & support' ),
        array( 'key' => 'field_ah_mj_dosing_eyebrow', 'label' => 'Dosing Eyebrow', 'name' => 'mj_dosing_eyebrow', 'type' => 'text', 'default_value' => 'Gradual & Personalised' ),
        array( 'key' => 'field_ah_mj_dosing_title', 'label' => 'Dosing Title (HTML)', 'name' => 'mj_dosing_title', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_mj_dosing_subtitle', 'label' => 'Dosing Subtitle', 'name' => 'mj_dosing_subtitle', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_mj_dosing_note', 'label' => 'Dosing Note', 'name' => 'mj_dosing_note', 'type' => 'textarea', 'rows' => 2 ),
        array(
            'key' => 'field_ah_mj_doses', 'label' => 'Doses', 'name' => 'mj_doses', 'type' => 'repeater', 'min' => 0, 'max' => 8, 'layout' => 'table',
            'sub_fields' => array(
                array( 'key' => 'field_ah_mj_dose_dose', 'label' => 'Dose', 'name' => 'dose', 'type' => 'text' ),
                array( 'key' => 'field_ah_mj_dose_label', 'label' => 'Label', 'name' => 'label', 'type' => 'text' ),
                array( 'key' => 'field_ah_mj_dose_desc', 'label' => 'Description', 'name' => 'desc', 'type' => 'text' ),
            ),
        ),
        array(
            'key' => 'field_ah_mj_benefits', 'label' => 'Benefits List', 'name' => 'mj_benefits', 'type' => 'repeater', 'min' => 0, 'max' => 6, 'layout' => 'table',
            'sub_fields' => array(
                array( 'key' => 'field_ah_mj_benefit_text', 'label' => 'Benefit (HTML)', 'name' => 'text', 'type' => 'text' ),
            ),
        ),
        array( 'key' => 'field_ah_mj_faq_title', 'label' => 'FAQ Title', 'name' => 'mj_faq_title', 'type' => 'text', 'default_value' => 'Mounjaro FAQs' ),
        array(
            'key' => 'field_ah_mj_faqs', 'label' => 'FAQs', 'name' => 'mj_faqs', 'type' => 'repeater', 'min' => 0, 'max' => 10, 'layout' => 'block',
            'sub_fields' => array(
                array( 'key' => 'field_ah_mj_faq_q', 'label' => 'Question', 'name' => 'question', 'type' => 'text' ),
                array( 'key' => 'field_ah_mj_faq_a', 'label' => 'Answer', 'name' => 'answer', 'type' => 'textarea', 'rows' => 3 ),
            ),
        ),
        array( 'key' => 'field_ah_mj_cta_eyebrow', 'label' => 'CTA Eyebrow', 'name' => 'mj_cta_eyebrow', 'type' => 'text', 'default_value' => 'Start Today' ),
        array( 'key' => 'field_ah_mj_cta_title', 'label' => 'CTA Title (HTML)', 'name' => 'mj_cta_title', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_mj_cta_subtitle', 'label' => 'CTA Subtitle', 'name' => 'mj_cta_subtitle', 'type' => 'textarea', 'rows' => 2 ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-mounjaro.php' ) ) ),
) );

// ═══════════════════════════════════════════════
// E-SERIES ADDITIONAL: WEGOVY PAGE
// ═══════════════════════════════════════════════

acf_add_local_field_group( array(
    'key'   => 'group_ah_e2_wegovy_extended',
    'title' => 'E2 — Wegovy: Dosing & FAQ',
    'fields' => array(
        array( 'key' => 'field_ah_wg_price_includes', 'label' => 'Price Includes Text', 'name' => 'wg_price_includes', 'type' => 'text', 'default_value' => 'Includes medication, consultations & support' ),
        array( 'key' => 'field_ah_wg_dosing_eyebrow', 'label' => 'Dosing Eyebrow', 'name' => 'wg_dosing_eyebrow', 'type' => 'text', 'default_value' => 'Gradual & Personalised' ),
        array( 'key' => 'field_ah_wg_dosing_title', 'label' => 'Dosing Title (HTML)', 'name' => 'wg_dosing_title', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_wg_dosing_note', 'label' => 'Dosing Note', 'name' => 'wg_dosing_note', 'type' => 'textarea', 'rows' => 2 ),
        array(
            'key' => 'field_ah_wg_doses', 'label' => 'Doses', 'name' => 'wg_doses', 'type' => 'repeater', 'min' => 0, 'max' => 8, 'layout' => 'table',
            'sub_fields' => array(
                array( 'key' => 'field_ah_wg_dose_dose', 'label' => 'Dose', 'name' => 'dose', 'type' => 'text' ),
                array( 'key' => 'field_ah_wg_dose_label', 'label' => 'Label', 'name' => 'label', 'type' => 'text' ),
                array( 'key' => 'field_ah_wg_dose_desc', 'label' => 'Description', 'name' => 'desc', 'type' => 'text' ),
            ),
        ),
        array(
            'key' => 'field_ah_wg_benefits', 'label' => 'Benefits List', 'name' => 'wg_benefits', 'type' => 'repeater', 'min' => 0, 'max' => 6, 'layout' => 'table',
            'sub_fields' => array(
                array( 'key' => 'field_ah_wg_benefit_text', 'label' => 'Benefit (HTML)', 'name' => 'text', 'type' => 'text' ),
            ),
        ),
        array( 'key' => 'field_ah_wg_faq_title', 'label' => 'FAQ Title', 'name' => 'wg_faq_title', 'type' => 'text', 'default_value' => 'Wegovy FAQs' ),
        array(
            'key' => 'field_ah_wg_faqs', 'label' => 'FAQs', 'name' => 'wg_faqs', 'type' => 'repeater', 'min' => 0, 'max' => 10, 'layout' => 'block',
            'sub_fields' => array(
                array( 'key' => 'field_ah_wg_faq_q', 'label' => 'Question', 'name' => 'question', 'type' => 'text' ),
                array( 'key' => 'field_ah_wg_faq_a', 'label' => 'Answer', 'name' => 'answer', 'type' => 'textarea', 'rows' => 3 ),
            ),
        ),
        array( 'key' => 'field_ah_wg_cta_title', 'label' => 'CTA Title (HTML)', 'name' => 'wg_cta_title', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_wg_cta_subtitle', 'label' => 'CTA Subtitle', 'name' => 'wg_cta_subtitle', 'type' => 'textarea', 'rows' => 2 ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-wegovy.php' ) ) ),
) );

// ═══════════════════════════════════════════════
// F-SERIES: TREATMENTS PAGE
// ═══════════════════════════════════════════════

acf_add_local_field_group( array(
    'key'   => 'group_ah_f1_treatments',
    'title' => 'F1 — Treatments: All Fields',
    'fields' => array(
        array( 'key' => 'field_ah_tr_eyebrow', 'label' => 'Eyebrow', 'name' => 'tr_eyebrow', 'type' => 'text', 'default_value' => 'All Treatments' ),
        array( 'key' => 'field_ah_tr_title', 'label' => 'Title (HTML)', 'name' => 'tr_title', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_tr_subtitle', 'label' => 'Subtitle', 'name' => 'tr_subtitle', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_tr_mounjaro_image', 'label' => 'Mounjaro Card Image', 'name' => 'tr_mounjaro_image', 'type' => 'image', 'return_format' => 'id' ),
        array( 'key' => 'field_ah_tr_mounjaro_desc', 'label' => 'Mounjaro Description', 'name' => 'tr_mounjaro_desc', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_tr_mounjaro_price', 'label' => 'Mounjaro Price (e.g. "from £XX")', 'name' => 'tr_mounjaro_price', 'type' => 'text', 'default_value' => 'from £XX' ),
        array( 'key' => 'field_ah_tr_wegovy_image', 'label' => 'Wegovy Card Image', 'name' => 'tr_wegovy_image', 'type' => 'image', 'return_format' => 'id' ),
        array( 'key' => 'field_ah_tr_wegovy_desc', 'label' => 'Wegovy Description', 'name' => 'tr_wegovy_desc', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_tr_wegovy_price', 'label' => 'Wegovy Price (e.g. "from £XX")', 'name' => 'tr_wegovy_price', 'type' => 'text', 'default_value' => 'from £XX' ),
        array( 'key' => 'field_ah_tr_orlistat_image', 'label' => 'Orlistat Card Image', 'name' => 'tr_orlistat_image', 'type' => 'image', 'return_format' => 'id' ),
        array( 'key' => 'field_ah_tr_orlistat_desc', 'label' => 'Orlistat Description', 'name' => 'tr_orlistat_desc', 'type' => 'textarea', 'rows' => 2, 'default_value' => 'A clinically proven weight loss tablet that reduces the amount of fat your body absorbs from food. Suitable for patients with a Body Mass Index (BMI) of 28 or above.' ),
        array( 'key' => 'field_ah_tr_orlistat_price', 'label' => 'Orlistat Price (e.g. "from £XX")', 'name' => 'tr_orlistat_price', 'type' => 'text', 'default_value' => 'from £XX' ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-treatments.php' ) ) ),
) );

// ═══════════════════════════════════════════════
// G-SERIES: ELIGIBILITY PAGE
// ═══════════════════════════════════════════════

acf_add_local_field_group( array(
    'key'   => 'group_ah_g1_eligibility',
    'title' => 'G1 — Eligibility: All Fields',
    'fields' => array(
        array( 'key' => 'field_ah_el_eyebrow', 'label' => 'Eyebrow', 'name' => 'el_eyebrow', 'type' => 'text', 'default_value' => 'Free Eligibility Check' ),
        array( 'key' => 'field_ah_el_title', 'label' => 'Title (HTML)', 'name' => 'el_title', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_el_subtitle', 'label' => 'Subtitle', 'name' => 'el_subtitle', 'type' => 'textarea', 'rows' => 3 ),
        array( 'key' => 'field_ah_el_hero_image', 'label' => 'Hero Image', 'name' => 'el_hero_image', 'type' => 'image', 'return_format' => 'id' ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-eligibility.php' ) ) ),
) );

// ═══════════════════════════════════════════════
// H-SERIES: SWITCHING PROVIDERS PAGE
// ═══════════════════════════════════════════════

acf_add_local_field_group( array(
    'key'   => 'group_ah_h1_switching',
    'title' => 'H1 — Switching: All Fields',
    'fields' => array(
        array( 'key' => 'field_ah_sw_eyebrow', 'label' => 'Eyebrow', 'name' => 'sw_eyebrow', 'type' => 'text', 'default_value' => 'Seamless Provider Switching' ),
        array( 'key' => 'field_ah_sw_title', 'label' => 'Title (HTML)', 'name' => 'sw_title', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_sw_subtitle', 'label' => 'Subtitle', 'name' => 'sw_subtitle', 'type' => 'textarea', 'rows' => 3 ),
        array( 'key' => 'field_ah_sw_hero_image', 'label' => 'Hero Image', 'name' => 'sw_hero_image', 'type' => 'image', 'return_format' => 'id' ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-switching.php' ) ) ),
) );

// ═══════════════════════════════════════════════
// I-SERIES: ABOUT PAGE
// ═══════════════════════════════════════════════

acf_add_local_field_group( array(
    'key'   => 'group_ah_i1_about',
    'title' => 'I1 — About: All Fields',
    'fields' => array(
        array( 'key' => 'field_ah_ab_eyebrow', 'label' => 'Eyebrow', 'name' => 'ab_eyebrow', 'type' => 'text', 'default_value' => 'Our Story' ),
        array( 'key' => 'field_ah_ab_title', 'label' => 'Title (HTML)', 'name' => 'ab_title', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_ab_subtitle', 'label' => 'Subtitle', 'name' => 'ab_subtitle', 'type' => 'textarea', 'rows' => 3 ),
        array( 'key' => 'field_ah_ab_story_image', 'label' => 'Story Section Image', 'name' => 'ab_story_image', 'type' => 'image', 'return_format' => 'id' ),
        array( 'key' => 'field_ah_ab_story_title', 'label' => 'Story Title', 'name' => 'ab_story_title', 'type' => 'text', 'default_value' => 'Our Story' ),
        array( 'key' => 'field_ah_ab_story_1', 'label' => 'Story Point 1', 'name' => 'ab_story_1', 'type' => 'textarea', 'rows' => 3 ),
        array( 'key' => 'field_ah_ab_story_2', 'label' => 'Story Point 2', 'name' => 'ab_story_2', 'type' => 'textarea', 'rows' => 3 ),
        array( 'key' => 'field_ah_ab_story_3', 'label' => 'Story Point 3', 'name' => 'ab_story_3', 'type' => 'textarea', 'rows' => 3 ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-about.php' ) ) ),
) );

// ═══════════════════════════════════════════════
// J-SERIES: CONTACT PAGE
// ═══════════════════════════════════════════════

acf_add_local_field_group( array(
    'key'   => 'group_ah_j1_contact',
    'title' => 'J1 — Contact: All Fields',
    'fields' => array(
        array( 'key' => 'field_ah_ct_title', 'label' => 'Title (HTML)', 'name' => 'ct_title', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_ct_subtitle', 'label' => 'Subtitle', 'name' => 'ct_subtitle', 'type' => 'textarea', 'rows' => 2 ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-contact.php' ) ) ),
) );

// ═══════════════════════════════════════════════
// K-SERIES: CUSTOMER CARE PAGE
// ═══════════════════════════════════════════════

acf_add_local_field_group( array(
    'key'   => 'group_ah_k1_customer_care',
    'title' => 'K1 — Customer Care: All Fields',
    'fields' => array(
        array( 'key' => 'field_ah_cc_eyebrow', 'label' => 'Eyebrow', 'name' => 'cc_eyebrow', 'type' => 'text', 'default_value' => 'Customer Care' ),
        array( 'key' => 'field_ah_cc_title', 'label' => 'Title (HTML)', 'name' => 'cc_title', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_cc_subtitle', 'label' => 'Subtitle', 'name' => 'cc_subtitle', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_cc_card1_title', 'label' => 'Card 1 Title', 'name' => 'cc_card1_title', 'type' => 'text', 'default_value' => 'Discreet Delivery' ),
        array( 'key' => 'field_ah_cc_card1_desc', 'label' => 'Card 1 Description', 'name' => 'cc_card1_desc', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_cc_card2_title', 'label' => 'Card 2 Title', 'name' => 'cc_card2_title', 'type' => 'text', 'default_value' => 'Fast & Secure' ),
        array( 'key' => 'field_ah_cc_card2_desc', 'label' => 'Card 2 Description', 'name' => 'cc_card2_desc', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_cc_card3_title', 'label' => 'Card 3 Title', 'name' => 'cc_card3_title', 'type' => 'text', 'default_value' => 'Easy Management' ),
        array( 'key' => 'field_ah_cc_card3_desc', 'label' => 'Card 3 Description', 'name' => 'cc_card3_desc', 'type' => 'textarea', 'rows' => 2 ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-customer-care.php' ) ) ),
) );

// ═══════════════════════════════════════════════
// L-SERIES: HEALTH HUB PAGE
// ═══════════════════════════════════════════════

acf_add_local_field_group( array(
    'key'   => 'group_ah_l1_health_hub',
    'title' => 'L1 — Health Hub: All Fields',
    'fields' => array(
        array( 'key' => 'field_ah_hh_title', 'label' => 'Title (HTML)', 'name' => 'hh_title', 'type' => 'textarea', 'rows' => 2 ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-health-hub.php' ) ) ),
) );

// ═══════════════════════════════════════════════
// M-SERIES: REORDER PAGE
// ═══════════════════════════════════════════════

acf_add_local_field_group( array(
    'key'   => 'group_ah_m1_reorder',
    'title' => 'M1 — Reorder: All Fields',
    'fields' => array(
        array( 'key' => 'field_ah_ro_title', 'label' => 'Title (HTML)', 'name' => 'ro_title', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_ro_subtitle', 'label' => 'Subtitle', 'name' => 'ro_subtitle', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_ah_ro_selection_title', 'label' => 'Selection Title', 'name' => 'ro_selection_title', 'type' => 'text', 'default_value' => 'Which medication are you reordering?' ),
        array( 'key' => 'field_ah_ro_wegovy_price', 'label' => 'Wegovy Price', 'name' => 'ro_wegovy_price', 'type' => 'text', 'default_value' => '125' ),
        array( 'key' => 'field_ah_ro_wegovy_stat', 'label' => 'Wegovy Stat', 'name' => 'ro_wegovy_stat', 'type' => 'text', 'default_value' => '15% average weight loss within 68 weeks' ),
        array( 'key' => 'field_ah_ro_mounjaro_price', 'label' => 'Mounjaro Price', 'name' => 'ro_mounjaro_price', 'type' => 'text', 'default_value' => '145' ),
        array( 'key' => 'field_ah_ro_mounjaro_stat', 'label' => 'Mounjaro Stat', 'name' => 'ro_mounjaro_stat', 'type' => 'text', 'default_value' => '20% average weight loss within 72 weeks' ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-reorder.php' ) ) ),
) );

// ═══════════════════════════════════════════════
// N-SERIES: TERMS PAGE
// ═══════════════════════════════════════════════

acf_add_local_field_group( array(
    'key'   => 'group_ah_n1_terms',
    'title' => 'N1 — Terms: All Fields',
    'fields' => array(
        array( 'key' => 'field_ah_tm_title', 'label' => 'Title', 'name' => 'tm_title', 'type' => 'text', 'default_value' => 'Terms and Conditions' ),
        array( 'key' => 'field_ah_tm_content', 'label' => 'Terms Content', 'name' => 'tm_content', 'type' => 'wysiwyg', 'instructions' => 'Full terms and conditions. If left blank, the standard WordPress content editor is used as fallback.' ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/page-terms.php' ) ) ),
) );

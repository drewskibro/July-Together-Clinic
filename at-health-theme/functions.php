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
    return ah_option( 'company_name', 'AT Health' );
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
    return ah_option( 'eligibility_url', '/eligibility/' );
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
        return function_exists( 'ah_company_name' ) ? ah_company_name() : 'AT Health';
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

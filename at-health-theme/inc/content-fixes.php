<?php
/**
 * One-off content corrections from the owner's site check (September 2026).
 *
 * Most of what the owner flagged is text saved in the database, not theme code:
 * the policy pages hold their wording in ACF fields that were saved from the
 * old defaults, the home page FAQ and "Explore All" URL can be saved per page,
 * and the footer reads ACF options. Changing the code defaults alone does not
 * reach any of that — which is why several items were still live after the
 * compliance PR changed the code. So this migration rewrites the saved values,
 * once, on the first request after deploy.
 *
 * Every edit is a targeted replacement of the exact wording the owner named.
 * Nothing is overwritten wholesale: the live refund policy carries deliberate
 * edits (a shortened repeat-orders section) that must survive. Anything already
 * correct is left alone, so a re-run is harmless.
 *
 * ah_fix_policy_html() is also what produced the updated default content in
 * functions.php, so a fresh install and the live site end up word-for-word the
 * same.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const AH_CONTENT_FIXES_OPTION = 'ah_content_fixes_2026_09';

/**
 * Applies the owner's policy corrections to a policy page's HTML.
 * Pure string function — no WordPress calls — so it runs on both the code
 * defaults and the stored copies, whatever their <br> / whitespace style.
 *
 * @param string $html Policy HTML (terms, privacy or refund).
 * @return string Corrected HTML.
 */
function ah_fix_policy_html( $html ) {
    $html = (string) $html;
    if ( $html === '' ) {
        return $html;
    }

    // Item 3: one public address.
    $html = str_replace( 'support@togetherclinic.co.uk', 'info@togetherclinic.co.uk', $html );

    // Item 7: the company is "AT Health Ltd" (case-sensitive: leaves any correct instance alone).
    $html = str_replace( 'At Health Ltd', 'AT Health Ltd', $html );

    // Item 7: the policies are being revised now, so the revision date moves with them.
    $html = preg_replace(
        '/Last updated:\s*(?:January|February|March|April|May|June|July|August|September|October|November|December)\s+20\d\d/',
        'Last updated: September 2026',
        $html
    );

    // Item 9: the home page promises "no cancellation fees", so the refund
    // policy's late-cancellation fee goes (the owner's suggested resolution).
    $html = preg_replace( '#\s*<li>\s*If you cancel a consultation at short notice.*?</li>#s', '', $html );

    // Item 10: drop the unconfirmed booking-system processor row.
    $html = preg_replace( '#\s*<tr>\s*<td>\s*Booking system\s*</td>.*?</tr>#s', '', $html );

    // Item 10: the site has no cookie banner or footer cookie-settings link (and
    // currently sets no analytics or marketing cookies), so the policy must not
    // promise either. Stays true if non-essential cookies are added later.
    $html = preg_replace(
        '#When you first visit our website, you will be presented with a cookie consent banner.*?(?:website footer|footer)\.#s',
        'Where we use non-essential cookies, such as analytics or marketing cookies, we will ask for your consent before setting them.',
        $html
    );

    // Item 6: company number and the supplying pharmacy, added once to the
    // policy's first company-details block. No registered office (owner's instruction).
    $company_line = '#(<strong>Company(?: Name)?:</strong>\s*AT Health Ltd \((?:trading as|t/a) Together Clinic\))(\s*)(<br\s*/?>)#';

    if ( strpos( $html, '14519140' ) === false && preg_match( $company_line, $html ) ) {
        $html = preg_replace(
            $company_line,
            "$1$2$3\n<strong>Company number:</strong> 14519140 (registered in England and Wales)$3",
            $html,
            1
        );
    }

    if ( strpos( $html, '1029878' ) === false && preg_match( $company_line, $html, $m ) ) {
        $br   = $m[3];
        $html = preg_replace(
            '#(<strong>Company(?: Name)?:</strong>.*?)(</p>)#s',
            "$1$br\n<strong>Supplying pharmacy:</strong> Wilmslow Pharmacy, Unit 2 Summerfields Village Centre, Dean Row Road, Wilmslow SK9 2TA (GPhC premises no. 1029878)$2",
            $html,
            1
        );
    }

    return $html;
}

/**
 * Home page FAQ answers saved in the ACF repeater (items 1 and 8).
 */
function ah_fix_faq_text( $text ) {
    return str_replace(
        array(
            'approved by UK-registered prescribers (General Medical Council (GMC) / GPhC qualified).',
            'Treatment plans start from £149/month',
            'Treatment plans start from &pound;149/month',
        ),
        array(
            'approved by UK-registered prescribers (registered with the GPhC).',
            'Treatment plans start from £99/month',
            'Treatment plans start from &pound;99/month',
        ),
        (string) $text
    );
}

/**
 * Writes an ACF option directly (value + field-key reference), only when the
 * current value is blank or one of the known old values. A value someone set
 * deliberately is never overwritten.
 *
 * @return bool True if the option was changed.
 */
function ah_content_fixes_set_option( $name, $new, $field_key, array $replace_if = array( '' ) ) {
    $current = trim( (string) get_option( 'options_' . $name, '' ) );
    $matches = array_map( 'strtolower', array_map( 'trim', $replace_if ) );
    if ( ! in_array( strtolower( $current ), $matches, true ) ) {
        return false;
    }
    update_option( 'options_' . $name, $new );
    update_option( '_options_' . $name, $field_key );
    return true;
}

add_action( 'init', 'ah_run_content_fixes_2026_09', 20 );

function ah_run_content_fixes_2026_09() {
    if ( get_option( AH_CONTENT_FIXES_OPTION ) ) {
        return;
    }

    global $wpdb;
    $log = array();

    // ── Policy pages: the ACF field and the page body (the template falls back to it). ──
    $policies = array(
        'terms'          => 'tm_content',
        'privacy-policy' => 'pp_content',
        'refund-policy'  => 'rp_content',
    );
    foreach ( $policies as $slug => $meta_key ) {
        $page = get_page_by_path( $slug );
        if ( ! $page ) {
            continue;
        }

        $stored = get_post_meta( $page->ID, $meta_key, true );
        if ( is_string( $stored ) && $stored !== '' ) {
            $fixed = ah_fix_policy_html( $stored );
            if ( $fixed !== $stored ) {
                update_post_meta( $page->ID, $meta_key, $fixed );
                $log[] = "{$slug}: {$meta_key}";
            }
        }

        if ( $page->post_content !== '' ) {
            $fixed = ah_fix_policy_html( $page->post_content );
            if ( $fixed !== $page->post_content ) {
                // Direct write: wp_update_post() would run the content through
                // kses for a logged-out request and could strip table markup.
                $wpdb->update( $wpdb->posts, array( 'post_content' => $fixed ), array( 'ID' => $page->ID ) );
                clean_post_cache( $page->ID );
                $log[] = "{$slug}: post_content";
            }
        }
    }

    // ── Home page FAQ answers saved in the repeater, on any page that has one. ──
    $faq_rows = $wpdb->get_results(
        "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE 'faq\\_items\\_%\\_answer'"
    );
    foreach ( (array) $faq_rows as $row ) {
        $fixed = ah_fix_faq_text( $row->meta_value );
        if ( $fixed !== $row->meta_value ) {
            $wpdb->update( $wpdb->postmeta, array( 'meta_value' => $fixed ), array( 'meta_id' => $row->meta_id ) );
            $log[] = "faq answer (meta {$row->meta_id})";
        }
    }

    // ── Item 12: "Explore All" articles link saved as /eligibility/ (a missing page). ──
    $front_id = (int) get_option( 'page_on_front' );
    if ( $front_id ) {
        $explore = (string) get_post_meta( $front_id, 'health_hub_explore_url', true );
        if ( $explore !== '' && preg_match( '#/eligibility/?$#', $explore ) ) {
            update_post_meta( $front_id, 'health_hub_explore_url', '/health-hub/' );
            $log[] = 'home: explore url';
        }
    }

    // ── Footer options (items 2 and 6). ──
    if ( ah_content_fixes_set_option( 'business_hours', '9am - 6pm, Monday to Friday', 'field_ah_business_hours', array( '', '9am - 5pm, Monday to Friday' ) ) ) {
        $log[] = 'option: business_hours';
    }
    // The copyright holder is the company, not the trading name.
    if ( ah_content_fixes_set_option( 'company_legal_name', 'AT Health Ltd', 'field_ah_company_legal_name', array( '', 'Together Clinic', 'Together Clinic Ltd', 'At Health Ltd' ) ) ) {
        $log[] = 'option: company_legal_name';
    }
    $regulatory = array(
        'registered_name'            => array( 'AT Health Ltd', 'field_ah_registered_name' ),
        'company_number'             => array( '14519140', 'field_ah_company_number' ),
        'gphc_number'                => array( '1029878', 'field_ah_gphc_number' ),
        'supplying_pharmacy_name'    => array( 'Wilmslow Pharmacy', 'field_ah_supplying_pharmacy_name' ),
        'supplying_pharmacy_address' => array( 'Unit 2 Summerfields Village Centre, Dean Row Road, Wilmslow SK9 2TA', 'field_ah_supplying_pharmacy_address' ),
    );
    foreach ( $regulatory as $name => $def ) {
        if ( ah_content_fixes_set_option( $name, $def[0], $def[1] ) ) {
            $log[] = "option: {$name}";
        }
    }
    // Deliberately no registered office address (owner's instruction).

    // ── Item 16: unpublish any post still carrying WordPress's placeholder text.
    // Reversible (it becomes a draft); the Health Hub shows published posts only. ──
    $placeholder_ids = $wpdb->get_col(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'
         AND post_content LIKE '%Welcome to WordPress. This is your first post.%'"
    );
    foreach ( (array) $placeholder_ids as $post_id ) {
        wp_update_post( array( 'ID' => (int) $post_id, 'post_status' => 'draft' ) );
        $log[] = "post {$post_id}: unpublished (placeholder text)";
    }

    update_option( AH_CONTENT_FIXES_OPTION, gmdate( 'c' ) . ' | ' . ( $log ? implode( '; ', $log ) : 'nothing to change' ), false );
    error_log( '[ah-content-fixes] ' . ( $log ? implode( '; ', $log ) : 'nothing to change' ) );
}

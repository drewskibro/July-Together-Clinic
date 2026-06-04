<?php
/**
 * ACF Options Pages Registration
 *
 * Registers the main "AT Health Settings" options page and sub-pages.
 * All global settings (branding, contact, compliance, etc.) are stored here.
 */

if ( function_exists( 'acf_add_options_page' ) ) {

    // Parent page
    acf_add_options_page( array(
        'page_title'  => 'Together Clinic Settings',
        'menu_title'  => 'Together Clinic Settings',
        'menu_slug'   => 'ah-settings',
        'capability'  => 'manage_options',
        'redirect'    => true,
        'icon_url'    => 'dashicons-heart',
        'position'    => 2,
    ) );

    // Sub-pages
    acf_add_options_sub_page( array(
        'page_title'  => 'Branding',
        'menu_title'  => 'Branding',
        'menu_slug'   => 'ah-settings-branding',
        'parent_slug' => 'ah-settings',
    ) );

    acf_add_options_sub_page( array(
        'page_title'  => 'Contact & Details',
        'menu_title'  => 'Contact & Details',
        'menu_slug'   => 'ah-settings-contact',
        'parent_slug' => 'ah-settings',
    ) );

    acf_add_options_sub_page( array(
        'page_title'  => 'Registration & Compliance',
        'menu_title'  => 'Registration & Compliance',
        'menu_slug'   => 'ah-settings-compliance',
        'parent_slug' => 'ah-settings',
    ) );

    acf_add_options_sub_page( array(
        'page_title'  => 'Social Media',
        'menu_title'  => 'Social Media',
        'menu_slug'   => 'ah-settings-social',
        'parent_slug' => 'ah-settings',
    ) );

    acf_add_options_sub_page( array(
        'page_title'  => 'Navigation & CTAs',
        'menu_title'  => 'Navigation & CTAs',
        'menu_slug'   => 'ah-settings-navigation',
        'parent_slug' => 'ah-settings',
    ) );

    acf_add_options_sub_page( array(
        'page_title'  => 'Clinical Team',
        'menu_title'  => 'Clinical Team',
        'menu_slug'   => 'ah-settings-clinical-team',
        'parent_slug' => 'ah-settings',
    ) );
}

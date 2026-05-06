<?php
/**
 * AT Health — WooCommerce Product Setup Script
 *
 * Creates all weight loss medication products with correct pricing.
 * Run ONCE via: WordPress Admin > Tools > Together Clinic Product Setup
 * Or visit: /wp-admin/admin.php?page=ah-product-setup
 *
 * Products created:
 *   - Mounjaro (Tirzepatide) — 6 doses: 2.5mg to 15mg (£159–£299)
 *   - Wegovy (Semaglutide)   — 5 doses: 0.25mg to 2.4mg (£109–£229)
 *   - Orlistat (Xenical)     — 1 dose: 120mg (£50)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Register admin page
add_action( 'admin_menu', function () {
    add_management_page(
        'Together Clinic Product Setup',
        'Together Clinic Product Setup',
        'manage_woocommerce',
        'ah-product-setup',
        'ah_product_setup_page'
    );
} );

function ah_product_setup_page() {
    // Check WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        echo '<div class="wrap"><h1>Together Clinic Product Setup</h1>';
        echo '<div class="notice notice-error"><p>WooCommerce must be installed and activated first.</p></div></div>';
        return;
    }

    $ran = false;
    $results = array();

    if ( isset( $_POST['ah_create_products'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ah_create_products' ) ) {
        $results = ah_create_all_products();
        $ran = true;
    }

    echo '<div class="wrap">';
    echo '<h1>Together Clinic — Product Setup</h1>';

    if ( $ran ) {
        echo '<div class="notice notice-success"><p><strong>Products created successfully!</strong></p></div>';
        echo '<table class="widefat striped" style="max-width:800px;">';
        echo '<thead><tr><th>Product</th><th>Dose</th><th>Price</th><th>Status</th></tr></thead><tbody>';
        foreach ( $results as $r ) {
            echo '<tr>';
            echo '<td>' . esc_html( $r['name'] ) . '</td>';
            echo '<td>' . esc_html( $r['dose'] ) . '</td>';
            echo '<td>&pound;' . esc_html( $r['price'] ) . '</td>';
            echo '<td>' . esc_html( $r['status'] ) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '<p style="margin-top:20px;"><a href="' . admin_url( 'edit.php?post_type=product' ) . '" class="button button-primary">View All Products</a></p>';
    } else {
        echo '<p>This will create the following WooCommerce products:</p>';
        echo '<table class="widefat striped" style="max-width:800px;">';
        echo '<thead><tr><th>Product</th><th>Doses</th><th>Price Range</th></tr></thead><tbody>';
        echo '<tr><td><strong>Mounjaro</strong> (Tirzepatide)</td><td>2.5mg, 5mg, 7.5mg, 10mg, 12.5mg, 15mg</td><td>&pound;159 – &pound;299</td></tr>';
        echo '<tr><td><strong>Wegovy</strong> (Semaglutide)</td><td>0.25mg, 0.5mg, 1mg, 1.7mg, 2.4mg</td><td>&pound;109 – &pound;229</td></tr>';
        echo '<tr><td><strong>Orlistat</strong> (Xenical)</td><td>120mg</td><td>&pound;50</td></tr>';
        echo '</tbody></table>';
        echo '<p><strong>Total: 12 simple products</strong></p>';
        echo '<form method="post" style="margin-top:20px;">';
        wp_nonce_field( 'ah_create_products' );
        echo '<input type="submit" name="ah_create_products" value="Create All Products" class="button button-primary button-hero" />';
        echo '</form>';
    }

    echo '</div>';
}

/**
 * Create all products and return results array.
 */
function ah_create_all_products() {
    $results = array();

    // Ensure product categories exist
    $cat_mounjaro = ah_ensure_product_category( 'Mounjaro', 'mounjaro' );
    $cat_wegovy   = ah_ensure_product_category( 'Wegovy', 'wegovy' );
    $cat_orlistat = ah_ensure_product_category( 'Orlistat', 'orlistat' );
    $cat_glp1     = ah_ensure_product_category( 'GLP-1 Medications', 'glp-1-medications' );
    $cat_weight   = ah_ensure_product_category( 'Weight Loss', 'weight-loss' );

    // ── Mounjaro Products ──
    $mounjaro_doses = array(
        array( 'dose' => '2.5 mg', 'price' => '159', 'label' => 'Starter', 'sku' => 'MJ-2.5' ),
        array( 'dose' => '5 mg',   'price' => '189', 'label' => '',        'sku' => 'MJ-5' ),
        array( 'dose' => '7.5 mg', 'price' => '239', 'label' => '',        'sku' => 'MJ-7.5' ),
        array( 'dose' => '10 mg',  'price' => '269', 'label' => '',        'sku' => 'MJ-10' ),
        array( 'dose' => '12.5 mg','price' => '279', 'label' => '',        'sku' => 'MJ-12.5' ),
        array( 'dose' => '15 mg',  'price' => '299', 'label' => '',        'sku' => 'MJ-15' ),
    );

    foreach ( $mounjaro_doses as $m ) {
        $title = 'Mounjaro (Tirzepatide) ' . $m['dose'];
        if ( $m['label'] ) {
            $title = 'Mounjaro ' . $m['label'] . ' (Tirzepatide) ' . $m['dose'];
        }
        $result = ah_create_simple_product( array(
            'name'             => $title,
            'slug'             => 'mounjaro-' . sanitize_title( $m['dose'] ),
            'sku'              => $m['sku'],
            'price'            => $m['price'],
            'description'      => 'Mounjaro (tirzepatide) ' . $m['dose'] . ' once-weekly injection for weight management. Prescribed by UK-registered prescribers. Includes medication, clinical consultation, and tracked delivery.',
            'short_description'=> 'Tirzepatide ' . $m['dose'] . ' — once-weekly injection. ' . ( $m['label'] === 'Starter' ? 'Starting dose for new patients.' : 'Ongoing treatment dose.' ),
            'categories'       => array( $cat_mounjaro, $cat_glp1, $cat_weight ),
            'virtual'          => false,
            'weight'           => '0.2',
            'meta'             => array(
                '_dose'           => $m['dose'],
                '_active_ingredient' => 'Tirzepatide',
                '_frequency'      => 'Once weekly',
            ),
        ) );
        $results[] = array( 'name' => 'Mounjaro', 'dose' => $m['dose'], 'price' => $m['price'], 'status' => $result );
    }

    // ── Wegovy Products ──
    $wegovy_doses = array(
        array( 'dose' => '0.25 mg', 'price' => '109', 'label' => 'Starter', 'sku' => 'WG-0.25' ),
        array( 'dose' => '0.5 mg',  'price' => '129', 'label' => '',        'sku' => 'WG-0.5' ),
        array( 'dose' => '1 mg',    'price' => '149', 'label' => '',        'sku' => 'WG-1' ),
        array( 'dose' => '1.7 mg',  'price' => '179', 'label' => '',        'sku' => 'WG-1.7' ),
        array( 'dose' => '2.4 mg',  'price' => '229', 'label' => '',        'sku' => 'WG-2.4' ),
    );

    foreach ( $wegovy_doses as $w ) {
        $title = 'Wegovy (Semaglutide) ' . $w['dose'];
        if ( $w['label'] ) {
            $title = 'Wegovy ' . $w['label'] . ' (Semaglutide) ' . $w['dose'];
        }
        $result = ah_create_simple_product( array(
            'name'             => $title,
            'slug'             => 'wegovy-' . sanitize_title( $w['dose'] ),
            'sku'              => $w['sku'],
            'price'            => $w['price'],
            'description'      => 'Wegovy (semaglutide) ' . $w['dose'] . ' once-weekly injection for weight management. FDA and MHRA approved with proven cardiovascular benefits. Includes medication, clinical consultation, and tracked delivery.',
            'short_description'=> 'Semaglutide ' . $w['dose'] . ' — once-weekly injection. ' . ( $w['label'] === 'Starter' ? 'Starting dose for new patients.' : 'Ongoing treatment dose.' ),
            'categories'       => array( $cat_wegovy, $cat_glp1, $cat_weight ),
            'virtual'          => false,
            'weight'           => '0.2',
            'meta'             => array(
                '_dose'           => $w['dose'],
                '_active_ingredient' => 'Semaglutide',
                '_frequency'      => 'Once weekly',
            ),
        ) );
        $results[] = array( 'name' => 'Wegovy', 'dose' => $w['dose'], 'price' => $w['price'], 'status' => $result );
    }

    // ── Orlistat ──
    $result = ah_create_simple_product( array(
        'name'             => 'Orlistat Capsules (Xenical) 120 mg',
        'slug'             => 'orlistat-120mg',
        'sku'              => 'ORL-120',
        'price'            => '50',
        'description'      => 'Orlistat 120mg capsules for weight management. Works by preventing absorption of some dietary fat. Prescribed by UK-registered prescribers with clinical support.',
        'short_description'=> 'Orlistat 120mg capsules — oral weight loss treatment.',
        'categories'       => array( $cat_orlistat, $cat_weight ),
        'virtual'          => false,
        'weight'           => '0.1',
        'meta'             => array(
            '_dose'           => '120 mg',
            '_active_ingredient' => 'Orlistat',
            '_frequency'      => 'With meals (up to 3x daily)',
        ),
    ) );
    $results[] = array( 'name' => 'Orlistat', 'dose' => '120 mg', 'price' => '50', 'status' => $result );

    return $results;
}

/**
 * Create a simple WooCommerce product.
 */
function ah_create_simple_product( $args ) {
    // Check if product already exists by SKU
    $existing = wc_get_product_id_by_sku( $args['sku'] );
    if ( $existing ) {
        return 'Already exists (ID: ' . $existing . ')';
    }

    $product = new WC_Product_Simple();

    $product->set_name( $args['name'] );
    $product->set_slug( $args['slug'] );
    $product->set_sku( $args['sku'] );
    $product->set_regular_price( $args['price'] );
    $product->set_description( $args['description'] );
    $product->set_short_description( $args['short_description'] );
    $product->set_status( 'publish' );
    $product->set_catalog_visibility( 'visible' );
    $product->set_sold_individually( true );
    $product->set_manage_stock( false );
    $product->set_stock_status( 'instock' );
    $product->set_virtual( isset( $args['virtual'] ) ? $args['virtual'] : false );

    if ( isset( $args['weight'] ) ) {
        $product->set_weight( $args['weight'] );
    }

    // Set categories
    if ( ! empty( $args['categories'] ) ) {
        $product->set_category_ids( $args['categories'] );
    }

    $product_id = $product->save();

    // Save custom meta
    if ( ! empty( $args['meta'] ) ) {
        foreach ( $args['meta'] as $key => $value ) {
            update_post_meta( $product_id, $key, $value );
        }
    }

    return 'Created (ID: ' . $product_id . ')';
}

/**
 * Ensure a product category exists, return its ID.
 */
function ah_ensure_product_category( $name, $slug ) {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( $term ) {
        return $term->term_id;
    }
    $result = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );
    if ( is_wp_error( $result ) ) {
        return 0;
    }
    return $result['term_id'];
}

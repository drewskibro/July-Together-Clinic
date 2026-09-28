<?php
/**
 * Health Hub — shared data and rendering for the magazine.
 *
 * The Hub runs as a magazine, not a blog: five sections, every story shown
 * with its section, a short standfirst and a read time, and the newest story
 * (or one "stuck to the top" in WordPress) leading. The home page section,
 * the Health Hub page and the article template all render through the
 * helpers here, so a story looks the same wherever it appears.
 *
 * Editorial rules (voice, compliance, review before publishing) live in
 * docs/health-hub.md.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The five sections. Slugs are the WordPress category slugs. Each has a soft
 * tint and an abstract mark, used for the designed cover a story shows until
 * it has a photograph.
 */
function ah_hh_sections() {
    return array(
        'eat-well' => array(
            'name'  => 'Eat well',
            'intro' => 'Food that tastes good, fills you up and doesn\'t cost the earth.',
            'tint'  => array( '#fbefe2', '#f2d8bd' ),
            'ink'   => '#94562a',
            'mark'  => '<path d="M3 12.5h18a9 9 0 0 1-18 0Z"/><path d="M8.5 8.5c0-1.3 1.2-1.7 0-3.5M12 8.5c0-1.3 1.2-1.7 0-3.5M15.5 8.5c0-1.3 1.2-1.7 0-3.5"/>',
        ),
        'move-more' => array(
            'name'  => 'Move more',
            'intro' => 'Ways to be more active that fit the life you already have.',
            'tint'  => array( '#e8f4ed', '#cde6d7' ),
            'ink'   => '#2d6a4d',
            'mark'  => '<path d="M2.5 16.5c3.2 0 3.2-7 6.4-7s3.2 7 6.4 7 3.2-7 6.2-7"/>',
        ),
        'mind' => array(
            'name'  => 'Mind & motivation',
            'intro' => 'The thinking side of changing your weight: habits, setbacks and staying kind to yourself.',
            'tint'  => array( '#efecfb', '#dad4f3' ),
            'ink'   => '#5f56a6',
            'mark'  => '<path d="M12 20s-7.2-4.4-7.2-10.1A4.1 4.1 0 0 1 12 7.2a4.1 4.1 0 0 1 7.2 2.7C19.2 15.6 12 20 12 20Z"/>',
        ),
        'real-life' => array(
            'name'  => 'Real life',
            'intro' => 'Holidays, birthdays, busy weeks and everything else that happens along the way.',
            'tint'  => array( '#fcebee', '#f3d1d8' ),
            'ink'   => '#9b4557',
            'mark'  => '<path d="M3.5 18.5h17M7 18.5a5 5 0 0 1 10 0M12 6.5v3M5.9 10.4l2 2M18.1 10.4l-2 2"/>',
        ),
        'your-treatment' => array(
            'name'  => 'Your treatment',
            'intro' => 'Practical help for the weeks and months you\'re on treatment, from our pharmacists.',
            'tint'  => array( '#e9f0f9', '#d3dff0' ),
            'ink'   => '#3a5886',
            'mark'  => '<rect x="4" y="5.5" width="16" height="14.5" rx="2.5"/><path d="M8 3.5v4M16 3.5v4M4 10.5h16M9 15.2l2 2 4-4"/>',
        ),
    );
}

/** URL of the Health Hub page, optionally filtered to one section. */
function ah_hh_url( $section = '' ) {
    $page = get_page_by_path( 'health-hub' );
    $url  = $page ? get_permalink( $page ) : home_url( '/health-hub/' );
    return $section ? add_query_arg( 'section', $section, $url ) : $url;
}

/** Number of published stories. */
function ah_hh_published_count() {
    $counts = wp_count_posts( 'post' );
    return isset( $counts->publish ) ? (int) $counts->publish : 0;
}

/**
 * The section a post belongs to: its first Health Hub section category, else
 * its first real category (styled like "Mind & motivation"), else none.
 */
function ah_hh_post_section( $post_id ) {
    $sections = ah_hh_sections();
    $cats     = get_the_category( $post_id );
    $fallback = null;
    foreach ( (array) $cats as $cat ) {
        if ( isset( $sections[ $cat->slug ] ) ) {
            return array( 'slug' => $cat->slug ) + $sections[ $cat->slug ];
        }
        if ( ! $fallback && ! in_array( strtolower( $cat->slug ), array( 'uncategorized', 'uncategorised' ), true ) ) {
            $fallback = array( 'slug' => $cat->slug, 'name' => $cat->name ) + array_diff_key( $sections['mind'], array( 'name' => 1 ) );
        }
    }
    return $fallback;
}

/** Read time in minutes: the post's own "Reading time" field, else ~220 words a minute. */
function ah_hh_read_minutes( $post ) {
    $post  = get_post( $post );
    $field = function_exists( 'get_field' ) ? get_field( 'reading_time', $post->ID ) : '';
    if ( $field !== null && $field !== '' && (int) $field > 0 ) {
        return (int) $field;
    }
    $words = str_word_count( wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) ) );
    return max( 1, (int) ceil( $words / 220 ) );
}

/**
 * Everything a card needs, for a published post (null otherwise — an
 * unpublished story never shows anywhere, even if something still points at it).
 */
function ah_hh_story( $post, $dek_words = 26 ) {
    $post = get_post( $post );
    if ( ! $post || $post->post_status !== 'publish' || $post->post_type !== 'post' ) {
        return null;
    }
    $dek = has_excerpt( $post ) ? $post->post_excerpt : wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) );
    $dek = trim( str_replace( array( '[...]', '[&hellip;]', '&hellip;' ), '', wp_strip_all_tags( $dek ) ) );

    return array(
        'id'      => $post->ID,
        'title'   => get_the_title( $post ),
        'dek'     => wp_trim_words( $dek, (int) $dek_words, '…' ),
        'url'     => get_permalink( $post ),
        'image'   => (int) get_post_thumbnail_id( $post ),
        'section' => ah_hh_post_section( $post->ID ),
        'minutes' => ah_hh_read_minutes( $post ),
        'date'    => get_the_date( '', $post ),
    );
}

/**
 * A lead story plus up to $rest_count more, newest first. A story "stuck to
 * the top" in WordPress leads if there is one.
 *
 * @return array{lead: ?array, rest: array}
 */
function ah_hh_front( $rest_count = 3, $section = '' ) {
    $base = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'ignore_sticky_posts' => true,
        'orderby'             => 'date',
        'order'               => 'DESC',
    );
    if ( $section ) {
        $base['category_name'] = $section;
    }

    $lead   = null;
    $sticky = array_filter( array_map( 'intval', (array) get_option( 'sticky_posts', array() ) ) );
    if ( $sticky ) {
        $found = get_posts( $base + array( 'post__in' => $sticky, 'numberposts' => 1 ) );
        $lead  = $found ? ah_hh_story( $found[0], 34 ) : null;
    }

    // WordPress reads numberposts 0 as "one", so skip the query when none are needed.
    $need  = $rest_count + ( $lead ? 0 : 1 );
    $posts = $need > 0 ? get_posts( $base + array(
        'numberposts'  => $need,
        'post__not_in' => $lead ? array( $lead['id'] ) : array(),
    ) ) : array();
    $stories = array_values( array_filter( array_map( 'ah_hh_story', $posts ) ) );

    if ( ! $lead && $stories ) {
        $lead = ah_hh_story( $stories[0]['id'], 34 );
        array_shift( $stories );
    }
    return array( 'lead' => $lead, 'rest' => $stories );
}

/* ─────────────────────────── Rendering ─────────────────────────── */

/** "Eat well · 4 min read" */
function ah_hh_meta( $story, $on_dark = false ) {
    $sec = $story['section'];
    ob_start(); ?>
    <p class="hh-meta<?php echo $on_dark ? ' hh-meta--dark' : ''; ?>">
      <?php if ( $sec ) : ?><span class="hh-meta-section" style="--hh-ink: <?php echo esc_attr( $sec['ink'] ); ?>;"><?php echo esc_html( $sec['name'] ); ?></span><span class="hh-meta-dot" aria-hidden="true">&middot;</span><?php endif; ?>
      <span><?php echo esc_html( sprintf( '%d min read', $story['minutes'] ) ); ?></span>
    </p>
    <?php
    return (string) ob_get_clean();
}

/**
 * The story's photograph, or — until it has one — a designed cover in its
 * section's colours, so a missing image never reads as a broken card.
 */
function ah_hh_cover( $story, $size = 'health-hub-card', $ratio = 'aspect-[4/3]', $radius = 'rounded-2xl' ) {
    $sec  = $story['section'] ? $story['section'] : array_merge( ah_hh_sections()['mind'], array( 'name' => '' ) );
    $wrap = 'hh-cover relative overflow-hidden ' . $ratio . ' ' . $radius;

    if ( $story['image'] ) {
        return '<div class="' . esc_attr( $wrap ) . ' bg-gray-100">'
            . wp_get_attachment_image( $story['image'], $size, false, array( 'class' => 'hh-cover-img absolute inset-0 w-full h-full object-cover' ) )
            . '</div>';
    }

    $style = sprintf( '--hh-a:%s;--hh-b:%s;--hh-ink:%s;', $sec['tint'][0], $sec['tint'][1], $sec['ink'] );
    return '<div class="' . esc_attr( $wrap . ' hh-cover--designed' ) . '" style="' . esc_attr( $style ) . '" aria-hidden="true">'
        . '<svg class="hh-cover-mark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">' . $sec['mark'] . '</svg>' // phpcs:ignore WordPress.Security.EscapeOutput -- static markup from ah_hh_sections()
        . ( $sec['name'] !== '' ? '<span class="hh-cover-label">' . esc_html( $sec['name'] ) . '</span>' : '' )
        . '</div>';
}

/** Grid card: cover, meta, title, standfirst. */
function ah_hh_card( $story, $index = 0 ) {
    ob_start(); ?>
    <a href="<?php echo esc_url( $story['url'] ); ?>" class="hh-card group flex flex-col" data-reveal style="--stagger-index:<?php echo (int) $index; ?>;">
      <div class="mb-5"><?php echo ah_hh_cover( $story ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
      <?php echo ah_hh_meta( $story ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
      <h3 class="hh-card-title font-serif text-gray-900 text-[1.4rem] md:text-[1.5rem] leading-[1.2] tracking-[-0.015em] mt-2 mb-2.5"><?php echo esc_html( $story['title'] ); ?></h3>
      <?php if ( $story['dek'] !== '' ) : ?>
      <p class="text-[15px] text-gray-600 leading-[1.6] line-clamp-3"><?php echo esc_html( $story['dek'] ); ?></p>
      <?php endif; ?>
    </a>
    <?php
    return (string) ob_get_clean();
}

/** Compact row: small cover beside meta and title (home page list). */
function ah_hh_row( $story, $index = 0 ) {
    ob_start(); ?>
    <a href="<?php echo esc_url( $story['url'] ); ?>" class="hh-row group grid grid-cols-[96px_1fr] sm:grid-cols-[120px_1fr] gap-5 items-center py-6" data-reveal style="--stagger-index:<?php echo (int) $index; ?>;">
      <?php echo ah_hh_cover( $story, 'thumbnail', 'aspect-square', 'rounded-xl' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
      <div>
        <?php echo ah_hh_meta( $story ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
        <h3 class="hh-card-title font-serif text-gray-900 text-[1.2rem] md:text-[1.3rem] leading-[1.22] tracking-[-0.01em] mt-1.5"><?php echo esc_html( $story['title'] ); ?></h3>
      </div>
    </a>
    <?php
    return (string) ob_get_clean();
}

/**
 * Lead story: large cover and headline, standfirst, "Read the story".
 *
 * Without a photograph, a big empty cover would be the most prominent thing
 * on the page — so the lead becomes a feature panel in its section's colours
 * instead, headline set large inside it like a cover line, the section's mark
 * as a watermark. $wide spreads it across the full width (Health Hub page).
 */
function ah_hh_lead( $story, $wide = false ) {
    if ( ! $story['image'] ) {
        $sec   = $story['section'] ? $story['section'] : array_merge( ah_hh_sections()['mind'], array( 'name' => '' ) );
        $style = sprintf( '--hh-a:%s;--hh-b:%s;--hh-ink:%s;', $sec['tint'][0], $sec['tint'][1], $sec['ink'] );
        ob_start(); ?>
    <a href="<?php echo esc_url( $story['url'] ); ?>" class="hh-lead hh-lead--panel<?php echo $wide ? ' hh-lead--wide' : ''; ?> group relative flex flex-col justify-between overflow-hidden rounded-[28px] p-8 md:p-12 lg:p-14 <?php echo $wide ? 'min-h-[360px] md:min-h-[420px]' : 'min-h-[420px] lg:min-h-[520px]'; ?>" style="<?php echo esc_attr( $style ); ?>" data-reveal>
      <svg class="hh-lead-watermark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo $sec['mark']; // phpcs:ignore WordPress.Security.EscapeOutput -- static markup from ah_hh_sections() ?></svg>
      <?php echo ah_hh_meta( $story ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
      <div class="relative <?php echo $wide ? 'max-w-3xl' : 'max-w-xl'; ?> mt-16 md:mt-24">
        <h3 class="hh-card-title font-serif text-gray-900 <?php echo $wide ? 'text-[2.4rem] md:text-[3.4rem] lg:text-[4rem]' : 'text-[2.3rem] md:text-[3rem] lg:text-[3.4rem]'; ?> leading-[1.02] tracking-[-0.025em] mb-5"><?php echo esc_html( $story['title'] ); ?></h3>
        <?php if ( $story['dek'] !== '' ) : ?>
        <p class="text-base md:text-[17px] text-gray-700 leading-[1.65] mb-7 max-w-xl"><?php echo esc_html( $story['dek'] ); ?></p>
        <?php endif; ?>
        <span class="hh-lead-cta inline-flex items-center gap-2 text-[15px] font-semibold rounded-full bg-white/80 px-5 py-3 transition-all duration-300 group-hover:bg-white group-hover:gap-3" style="color: <?php echo esc_attr( $sec['ink'] ); ?>;">Read the story <span aria-hidden="true">&rarr;</span></span>
      </div>
    </a>
        <?php
        return (string) ob_get_clean();
    }

    ob_start(); ?>
    <a href="<?php echo esc_url( $story['url'] ); ?>" class="hh-lead group block" data-reveal>
      <div class="mb-6"><?php echo ah_hh_cover( $story, 'health-hub-featured', 'aspect-[16/11]', 'rounded-[28px]' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
      <?php echo ah_hh_meta( $story ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
      <h3 class="hh-card-title font-serif text-gray-900 text-[2rem] md:text-[2.6rem] leading-[1.06] tracking-[-0.02em] mt-3 mb-4" style="text-wrap: balance;"><?php echo esc_html( $story['title'] ); ?></h3>
      <?php if ( $story['dek'] !== '' ) : ?>
      <p class="text-base md:text-[17px] text-gray-600 leading-[1.65] max-w-2xl mb-5"><?php echo esc_html( $story['dek'] ); ?></p>
      <?php endif; ?>
      <span class="inline-flex items-center gap-2 text-[15px] font-semibold text-gray-900 transition-transform duration-300 group-hover:translate-x-1">Read the story <span aria-hidden="true" style="color:#8e88d0;">&rarr;</span></span>
    </a>
    <?php
    return (string) ob_get_clean();
}

/** Section pills linking to the Health Hub page (only sections with stories). */
function ah_hh_section_pills( $current = '', $with_all = false ) {
    $sections = ah_hh_sections();
    $terms    = get_terms( array( 'taxonomy' => 'category', 'slug' => array_keys( $sections ), 'hide_empty' => true ) );
    $live     = is_wp_error( $terms ) ? array() : wp_list_pluck( $terms, 'slug' );
    ob_start(); ?>
    <nav class="hh-pills flex flex-wrap gap-2.5" aria-label="Health Hub sections">
      <?php if ( $with_all ) : ?>
      <a href="<?php echo esc_url( ah_hh_url() ); ?>" class="hh-pill<?php echo $current === '' ? ' is-active' : ''; ?>">All stories</a>
      <?php endif; ?>
      <?php foreach ( $sections as $slug => $sec ) : if ( ! in_array( $slug, $live, true ) ) { continue; } ?>
      <a href="<?php echo esc_url( ah_hh_url( $slug ) ); ?>" class="hh-pill<?php echo $current === $slug ? ' is-active' : ''; ?>" style="--hh-ink: <?php echo esc_attr( $sec['ink'] ); ?>;"><?php echo esc_html( $sec['name'] ); ?></a>
      <?php endforeach; ?>
    </nav>
    <?php
    return (string) ob_get_clean();
}

/* ───────────────────── One-off setup: the section categories ───────────────────── */

add_action( 'init', 'ah_hh_ensure_sections', 25 );

function ah_hh_ensure_sections() {
    if ( get_option( 'ah_health_hub_sections_v1' ) ) {
        return;
    }
    foreach ( ah_hh_sections() as $slug => $sec ) {
        if ( ! term_exists( $slug, 'category' ) ) {
            wp_insert_term( $sec['name'], 'category', array( 'slug' => $slug, 'description' => $sec['intro'] ) );
        }
    }
    update_option( 'ah_health_hub_sections_v1', gmdate( 'c' ), false );
}

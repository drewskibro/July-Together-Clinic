<?php
/**
 * Single Post — Clinical Review (EEAT) box + auto Table of Contents
 *
 * Auto-injects two blocks into single-post content:
 *  1. Clinical Review card at the very top (above the first paragraph)
 *  2. Table of Contents after the first paragraph (only if post has 2+ H2s)
 *
 * Authors come from a global "Clinical Team" repeater on the options page.
 * Per-post fields control which team member is the writer / reviewer, and
 * provide opt-out toggles for either block.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Look up a team member by their repeater row index.
 *
 * @param mixed $index Stored value from the post's "Written by" / "Reviewed by" field.
 * @return array|null  Member data or null if not found.
 */
function ah_get_clinical_team_member( $index ) {
    if ( $index === null || $index === '' || ! function_exists( 'get_field' ) ) {
        return null;
    }
    $team = get_field( 'clinical_team', 'option' );
    if ( ! is_array( $team ) ) {
        return null;
    }
    $i = (int) $index;
    if ( ! isset( $team[ $i ] ) ) {
        return null;
    }
    return $team[ $i ];
}

/**
 * Build the Clinical Review (EEAT) card markup for the current post.
 */
function ah_render_clinical_review_box() {
    $writer_idx   = function_exists( 'get_field' ) ? get_field( 'post_written_by' ) : null;
    $reviewer_idx = function_exists( 'get_field' ) ? get_field( 'post_reviewed_by' ) : null;

    $writer   = ah_get_clinical_team_member( $writer_idx );
    $reviewer = ah_get_clinical_team_member( $reviewer_idx );

    // Don't render if neither slot is set — avoids an empty card.
    if ( ! $writer && ! $reviewer ) {
        return '';
    }

    $last_updated = get_the_modified_date( 'M j, Y' );

    ob_start();
    ?>
    <aside class="bp-review-card" aria-label="Clinical review">
      <div class="bp-review-header">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
        <span>Clinically Reviewed Content</span>
      </div>
      <div class="bp-review-columns">
        <?php if ( $writer ) : ?>
        <div class="bp-review-person">
          <?php if ( ! empty( $writer['photo'] ) ) : ?>
            <?php echo wp_get_attachment_image( $writer['photo'], 'thumbnail', false, array(
                'class' => 'bp-review-avatar',
                'alt'   => esc_attr( $writer['name'] ),
            ) ); ?>
          <?php else : ?>
            <div class="bp-review-avatar bp-review-avatar--placeholder" aria-hidden="true"></div>
          <?php endif; ?>
          <div>
            <span class="bp-review-label">Written by</span>
            <span class="bp-review-name"><?php echo esc_html( $writer['name'] ); ?></span>
            <?php if ( ! empty( $writer['role'] ) ) : ?>
              <span class="bp-review-title"><?php echo esc_html( $writer['role'] ); ?></span>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if ( $reviewer ) : ?>
        <div class="bp-review-person">
          <?php if ( ! empty( $reviewer['photo'] ) ) : ?>
            <?php echo wp_get_attachment_image( $reviewer['photo'], 'thumbnail', false, array(
                'class' => 'bp-review-avatar bp-review-avatar--verify',
                'alt'   => esc_attr( $reviewer['name'] ),
            ) ); ?>
          <?php else : ?>
            <div class="bp-review-avatar bp-review-avatar--verify bp-review-avatar--placeholder" aria-hidden="true"></div>
          <?php endif; ?>
          <div>
            <span class="bp-review-label">Reviewed &amp; fact-checked by</span>
            <span class="bp-review-name"><?php echo esc_html( $reviewer['name'] ); ?></span>
            <?php
            $review_meta = '';
            if ( ! empty( $reviewer['role'] ) ) {
                $review_meta .= $reviewer['role'];
            }
            if ( ! empty( $reviewer['gphc_number'] ) ) {
                $review_meta .= ( $review_meta !== '' ? ' &middot; ' : '' ) . 'GPhC: ' . esc_html( $reviewer['gphc_number'] );
            }
            ?>
            <?php if ( $review_meta !== '' ) : ?>
              <span class="bp-review-title"><?php echo wp_kses_post( $review_meta ); ?></span>
            <?php endif; ?>
            <?php if ( ! empty( $reviewer['verify_url'] ) ) : ?>
              <div class="bp-review-links">
                <a href="<?php echo esc_url( $reviewer['verify_url'] ); ?>" target="_blank" rel="noopener noreferrer">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                  Verify on GPhC Register
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div class="bp-review-footer">
        <span class="bp-review-meta">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          Last updated: <?php echo esc_html( $last_updated ); ?>
        </span>
        <span class="bp-review-meta bp-review-meta--check">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          Medically reviewed
        </span>
      </div>
    </aside>
    <?php
    return ob_get_clean();
}

/**
 * Scan content for H2 headings, add stable IDs to them, and build a TOC list.
 *
 * Returns [updated_content, toc_html]. If fewer than 2 H2s exist, toc_html is empty.
 */
function ah_build_toc_from_content( $content ) {
    $matches = array();
    preg_match_all( '/<h2(\s[^>]*)?>(.*?)<\/h2>/is', $content, $matches, PREG_SET_ORDER );

    if ( count( $matches ) < 2 ) {
        return array( $content, '' );
    }

    $used_slugs = array();
    $items      = array();
    $new_content = $content;

    foreach ( $matches as $m ) {
        $attrs_str  = isset( $m[1] ) ? $m[1] : '';
        $inner_html = $m[2];
        $plain_text = trim( wp_strip_all_tags( $inner_html ) );

        if ( $plain_text === '' ) {
            continue;
        }

        // Reuse an existing id if the editor already set one; otherwise generate from text.
        $id = '';
        if ( preg_match( '/\bid\s*=\s*"([^"]+)"/i', $attrs_str, $id_match ) ) {
            $id = $id_match[1];
        }
        if ( $id === '' ) {
            $slug = sanitize_title( $plain_text );
            if ( $slug === '' ) { $slug = 'section'; }
            $base = $slug; $n = 2;
            while ( in_array( $slug, $used_slugs, true ) ) {
                $slug = $base . '-' . $n;
                $n++;
            }
            $id = $slug;

            // Inject id attribute into the original <h2> tag in the content.
            $original = $m[0];
            $replaced = '<h2 id="' . esc_attr( $id ) . '"' . $attrs_str . '>' . $inner_html . '</h2>';
            $pos = strpos( $new_content, $original );
            if ( $pos !== false ) {
                $new_content = substr_replace( $new_content, $replaced, $pos, strlen( $original ) );
            }
        }

        $used_slugs[] = $id;
        $items[]      = array( 'id' => $id, 'text' => $plain_text );
    }

    if ( count( $items ) < 2 ) {
        return array( $new_content, '' );
    }

    ob_start();
    ?>
    <nav class="bp-toc" aria-label="Table of contents">
      <details class="bp-toc-details" open>
        <summary class="bp-toc-summary">
          <span class="bp-toc-summary-left">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            In This Article
          </span>
          <svg class="bp-toc-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </summary>
        <ol class="bp-toc-list">
          <?php foreach ( $items as $i => $item ) : ?>
          <li class="bp-toc-item">
            <span class="bp-toc-num"><?php echo (int) ( $i + 1 ); ?></span>
            <a class="bp-toc-link" href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['text'] ); ?></a>
          </li>
          <?php endforeach; ?>
        </ol>
      </details>
    </nav>
    <?php
    return array( $new_content, ob_get_clean() );
}

/**
 * Filter the_content on single posts: prepend EEAT box and inject TOC after the first paragraph.
 */
add_filter( 'the_content', 'ah_inject_clinical_blocks', 20 );
function ah_inject_clinical_blocks( $content ) {
    if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    $hide_box = function_exists( 'get_field' ) ? (bool) get_field( 'post_hide_clinical_box' ) : false;
    $hide_toc = function_exists( 'get_field' ) ? (bool) get_field( 'post_hide_toc' ) : false;

    // TOC processing also adds IDs to H2s — even if we hide the TOC list, run it so anchor IDs exist for sharing.
    list( $content, $toc_html ) = ah_build_toc_from_content( $content );

    // Inject TOC after the first closing </p>, falling back to top if no paragraph break.
    if ( ! $hide_toc && $toc_html !== '' ) {
        $pos = stripos( $content, '</p>' );
        if ( $pos !== false ) {
            $content = substr_replace( $content, '</p>' . $toc_html, $pos, strlen( '</p>' ) );
        } else {
            $content = $toc_html . $content;
        }
    }

    // EEAT box always goes at the very top.
    if ( ! $hide_box ) {
        $content = ah_render_clinical_review_box() . $content;
    }

    return $content;
}

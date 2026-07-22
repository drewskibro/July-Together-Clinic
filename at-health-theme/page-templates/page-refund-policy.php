<?php
/**
 * Template Name: Refund Policy
 * Description: Refund & Cancellation Policy page. Mirrors the Terms template;
 * reuses the .tm-content wrapper (styled in terms.css) plus the .rp-notice /
 * .rp-stages callout boxes.
 */
get_header();
?>

<!-- Hero -->
<section class="py-12 md:py-16" style="background:#fdf8f3;">
  <div class="ah-container">
    <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'customer-care' ) ) ); ?>" class="inline-flex items-center gap-2 text-purple-600 text-sm font-medium hover:text-purple-700 transition-colors mb-6">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Customer Care
    </a>
    <h1 class="text-4xl md:text-5xl font-serif text-gray-900"><?php echo esc_html( ah_field( 'rp_title', 'Refund &amp; Cancellation Policy' ) ); ?></h1>
  </div>
</section>

<!-- Content -->
<section class="py-10 md:py-14" style="background: #fdf8f3;">
  <div class="ah-container">
    <div class="tm-content">
      <?php
      $refund_content = ah_field( 'rp_content', '' );
      if ( $refund_content ) {
          echo wp_kses_post( $refund_content );
      } else {
          // Fallback: use WordPress content editor
          while ( have_posts() ) : the_post();
              the_content();
          endwhile;
      }
      ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-14" style="background: #f7f4f9;">
  <div class="ah-container text-center" data-reveal>
    <h2 class="text-2xl md:text-3xl font-serif text-gray-900 mb-4">Ready to start your journey?</h2>
    <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="ah-btn-purple">Check Eligibility <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></a>
  </div>
</section>

<?php get_footer(); ?>

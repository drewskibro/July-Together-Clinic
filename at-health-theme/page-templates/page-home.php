<?php
/**
 * Template Name: Home
 * Description: AT Health pharmacy homepage. Section order: Hero, How It Works, Google Reviews, Know Your Team, Safe and Secure, Health Hub, FAQs, CTA.
 */
get_header();

// Section 1: Hero
// PHASE 2: Review hero messaging - ensure pharmacy-focused not weight loss-focused
get_template_part( 'template-parts/section', 'hero' );

// Section 2: How It Works
get_template_part( 'template-parts/section', 'how-it-works' );
?>

<!-- Section 3: Google Reviews -->
<!-- PHASE 3: Replace with embedded Google Reviews widget -->
<section class="relative py-16 md:py-20 overflow-hidden" style="background: #f7f4f9;">
  <div class="ah-container-wide">
    <div class="text-center mb-12">
      <div class="inline-flex items-center gap-2 bg-white border border-amber-200 rounded-full px-5 py-2 shadow-sm mb-6">
        <span class="text-amber-500 text-sm">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
        <span class="text-sm font-semibold text-gray-700"><?php echo esc_html( ah_field( 'testimonials_badge', 'Rated Excellent 4.9/5 by 10,000+ patients' ) ); ?></span>
      </div>
      <h2 class="text-3xl md:text-4xl lg:text-5xl font-serif text-gray-900 mb-4">
        <?php echo wp_kses_post( ah_field( 'testimonials_title', 'Life-Changing Results' ) ); ?>
      </h2>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto" data-stagger>
      <?php
      $default_testimonials = array(
          array( 'name' => 'Sophie Chaudhry', 'label' => 'Verified Patient', 'text' => "I no longer struggle with weight loss. It's changed my entire relationship with food." ),
          array( 'name' => 'Stephen Matthews', 'label' => 'Verified Patient · Lost 19kg', 'text' => 'I have so far lost 3 stone (19kg). The support has been incredible.' ),
          array( 'name' => 'Marie Clayton', 'label' => 'Verified Patient', 'text' => "I've got my life back. I feel confident, energetic, and happy again." ),
          array( 'name' => 'Tanta Stefanescu', 'label' => 'Verified Patient · Lost 11kg', 'text' => 'I have lost almost 11kg and feel incredible. The best decision I\'ve made.' ),
      );

      $testimonials = ah_field( 'testimonials_items', '' );
      if ( ! is_array( $testimonials ) || count( $testimonials ) === 0 ) {
          $testimonials = $default_testimonials;
      }

      foreach ( $testimonials as $i => $t ) :
          $name  = isset( $t['name'] ) ? $t['name'] : '';
          $label = isset( $t['label'] ) ? $t['label'] : 'Verified Patient';
          $text  = isset( $t['text'] ) ? $t['text'] : '';
      ?>
      <div class="hp-testimonial" data-reveal style="--stagger-index:<?php echo (int) $i; ?>">
        <div class="flex gap-0.5 mb-4">
          <?php for ( $s = 0; $s < 5; $s++ ) : ?>
          <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
          <?php endfor; ?>
        </div>
        <p class="text-gray-700 text-[15px] leading-relaxed mb-6">"<?php echo esc_html( $text ); ?>"</p>
        <div>
          <p class="text-sm font-semibold text-gray-900"><?php echo esc_html( $name ); ?></p>
          <p class="text-xs text-gray-500"><?php echo esc_html( $label ); ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Section 4: Know Your Team -->
<!-- TO BE POPULATED -->
<section class="relative py-16 md:py-20" style="background: #fdf8f3;">
  <div class="ah-container-wide">
    <div class="text-center">
      <h2 class="text-3xl md:text-4xl lg:text-5xl font-serif text-gray-900">Know Your Team</h2>
    </div>
  </div>
</section>

<!-- Section 5: Safe and Secure (GPhC backed) -->
<!-- PHASE 3: Review and refine to focus on GPhC backing -->
<section class="relative py-16 md:py-20" style="background: #fdf8f3;">
  <div class="ah-container-wide">
    <div class="text-center mb-12 section-header">
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
        <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider"><?php echo esc_html( ah_field( 'stats_eyebrow', 'Why Patients Choose Us' ) ); ?></p>
      </div>
      <h2 class="text-3xl md:text-4xl lg:text-5xl text-gray-800 font-serif leading-[1.1]">
        <?php echo wp_kses_post( ah_field( 'stats_title', 'The Numbers Speak for Themselves' ) ); ?>
      </h2>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-6 max-w-5xl mx-auto" data-stagger>
      <div class="hp-stat-card" data-reveal style="--stagger-index:0">
        <div class="hp-stat-number"><span data-count="30" data-suffix="+">0</span></div>
        <p class="text-sm text-gray-600">Years Combined Clinical Experience</p>
      </div>
      <div class="hp-stat-card" data-reveal style="--stagger-index:1">
        <div class="hp-stat-number"><span data-count="10000" data-suffix="+">0</span></div>
        <p class="text-sm text-gray-600">Patients Treated</p>
      </div>
      <div class="hp-stat-card" data-reveal style="--stagger-index:2">
        <div class="hp-stat-number">4.9<span class="text-amber-400 text-2xl">&#9733;</span></div>
        <p class="text-sm text-gray-600">Patient Rating</p>
      </div>
      <div class="hp-stat-card" data-reveal style="--stagger-index:3">
        <div class="hp-stat-number">48h</div>
        <p class="text-sm text-gray-600">Tracked Delivery</p>
      </div>
      <div class="hp-stat-card col-span-2 md:col-span-1" data-reveal style="--stagger-index:4">
        <div class="flex items-center justify-center gap-2 mb-2">
          <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        </div>
        <p class="text-sm text-gray-600 font-semibold">GPhC & MHRA Regulated</p>
      </div>
    </div>
  </div>
</section>

<!-- Section 6: Health Hub Snapshot -->
<!-- TO BE POPULATED -->
<section class="relative py-16 md:py-20" style="background: #f7f4f9;">
  <div class="ah-container-wide">
    <div class="text-center">
      <h2 class="text-3xl md:text-4xl lg:text-5xl font-serif text-gray-900">Health Hub</h2>
    </div>
  </div>
</section>

<?php
// Section 7: FAQ
get_template_part( 'template-parts/section', 'faq' );

// Section 8: Start Your Journey CTA — points to /eligibility/ via ah_booking_url(), confirmed correct
get_template_part( 'template-parts/section', 'cta' );
?>

<!-- Phase 2 complete: Weight Loss Calculator and Treatment Showcase moved to page-treatments.php -->

<?php
get_footer();
?>

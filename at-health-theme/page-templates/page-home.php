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

// Section 8: Start Your Journey CTA
// PHASE 3: Verify CTA destination points to weight loss page
get_template_part( 'template-parts/section', 'cta' );
?>

<!-- ===================================================================== -->
<!-- HIDDEN SECTIONS - Do not delete. To be relocated in Phase 2.           -->
<!-- ===================================================================== -->

<!-- PHASE 2: Weight Loss Calculator - to be moved to weight loss page -->
<!-- GPHC-FLAG: Calculator references SURMOUNT-1 trial and tirzepatide 15mg - branded medication promotion concerns -->
<section class="relative w-full py-14 md:py-16 overflow-hidden" style="display:none; background: #fdf8f3;">
  <div class="max-w-[840px] mx-auto px-6 relative z-10">
    <div class="hp-calc-card px-8 md:px-14 py-10 md:py-14" data-reveal>
      <!-- Takes 10 seconds pill -->
      <div class="flex justify-center mb-8">
        <div class="inline-flex items-center gap-2 bg-white border border-orange-200/80 rounded-full px-5 py-2.5 shadow-sm">
          <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
          </svg>
          <span class="text-xs font-bold text-orange-600 uppercase tracking-widest"><?php echo esc_html( ah_field( 'calc_pill_text', 'Takes 10 Seconds' ) ); ?></span>
        </div>
      </div>

      <div class="text-center mb-8">
        <h2 class="text-3xl md:text-4xl font-serif text-gray-900 leading-tight mb-3">
          <?php echo wp_kses_post( ah_field( 'calc_title', 'How Much Could <em class="text-purple-600 not-italic font-serif">You</em> Lose?' ) ); ?>
        </h2>
        <p class="text-gray-500 text-base"><?php echo esc_html( ah_field( 'calc_subtitle', 'Enter your weight below — results are instant and private' ) ); ?></p>
      </div>

      <form id="weightLossForm" class="mb-8">
        <label class="block text-sm font-bold text-gray-900 mb-3">Enter your current weight</label>
        <div class="flex mb-4">
          <div class="inline-flex bg-gray-50 border border-gray-200 rounded-full p-1">
            <button type="button" data-unit="kg" class="calc-unit-btn active-unit px-5 py-2 rounded-full text-sm font-semibold transition-all duration-200">kg</button>
            <button type="button" data-unit="stone" class="calc-unit-btn px-5 py-2 rounded-full text-sm font-semibold text-gray-500 transition-all duration-200">stone</button>
            <button type="button" data-unit="lbs" class="calc-unit-btn px-5 py-2 rounded-full text-sm font-semibold text-gray-500 transition-all duration-200">lbs</button>
          </div>
        </div>
        <div class="flex items-center gap-3 mb-8">
          <div class="relative flex-1">
            <input type="number" id="calcWeight" placeholder="e.g. 95" class="w-full px-5 py-4 bg-white border-2 border-gray-200 rounded-2xl text-gray-900 text-lg font-medium focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all outline-none" required min="30" max="300" step="0.1" />
          </div>
          <span class="text-gray-400 text-base font-medium min-w-[40px]" id="calcUnitLabel">kg</span>
        </div>
        <button type="submit" class="w-full flex items-center justify-center gap-3 text-white text-base md:text-lg font-semibold px-10 py-4 rounded-2xl shadow-lg hover:shadow-xl transition-all hover-lift" style="background: linear-gradient(135deg, #b8855a 0%, #a0714d 100%);">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
          </svg>
          Calculate My Results
        </button>
      </form>

      <!-- Results (hidden initially) -->
      <div id="calcResults" class="hidden">
        <div class="bg-gradient-to-br from-purple-50 to-white rounded-2xl border border-purple-100 p-6 md:p-8 mb-6">
          <!-- GPHC-FLAG: "SURMOUNT-1 Clinical Data" reference - SURMOUNT-1 is the tirzepatide (Mounjaro) trial - branded by association -->
          <p class="text-xs font-bold text-purple-600 uppercase tracking-wider mb-3">Based on SURMOUNT-1 Clinical Data</p>
          <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="text-center">
              <div class="text-3xl md:text-4xl font-serif font-bold text-purple-700" id="resLossKg">0</div>
              <p class="text-xs text-gray-500 mt-1 font-medium">kg lost</p>
            </div>
            <div class="text-center border-x border-purple-100">
              <div class="text-3xl md:text-4xl font-serif font-bold text-purple-700" id="resLossStone">0</div>
              <p class="text-xs text-gray-500 mt-1 font-medium">stone lost</p>
            </div>
            <div class="text-center">
              <div class="text-3xl md:text-4xl font-serif font-bold text-purple-700" id="resNewWeight">0</div>
              <p class="text-xs text-gray-500 mt-1 font-medium" id="resNewWeightUnit">kg new weight</p>
            </div>
          </div>
          <div class="relative h-3 bg-purple-100 rounded-full overflow-hidden mb-4">
            <div id="resBar" class="absolute inset-y-0 left-0 bg-gradient-to-r from-purple-600 to-purple-400 rounded-full transition-all duration-1000 ease-out" style="width:0%"></div>
          </div>
          <div class="flex justify-between text-xs text-gray-400 font-medium">
            <span id="resStartLabel">95 kg</span>
            <span class="text-purple-600 font-bold" id="resPercent">-22.5%</span>
            <span id="resGoalLabel">73.6 kg</span>
          </div>
        </div>
        <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="w-full flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-base font-semibold px-8 py-4 rounded-2xl shadow-lg hover:shadow-xl transition-all hover-lift mb-6">
          Start Your Journey
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
          </svg>
        </a>
        <!-- GPHC-FLAG: References "tirzepatide 15mg" - branded active ingredient (Mounjaro) -->
        <p class="text-center text-xs text-gray-400">Results based on SURMOUNT-1 clinical trial (tirzepatide 15mg, 72-week data). Individual results may vary.</p>
      </div>
    </div>
  </div>
</section>

<!-- PHASE 2: Treatment Showcase - to be moved to weight loss page, GPhC compliance required before reinstatement -->
<!-- GPHC-FLAG: Section contains hardcoded branded medication names (Mounjaro, Wegovy) - prohibited on pharmacy homepage under GPhC guidance -->
<section class="relative py-16 md:py-20 overflow-hidden" style="display:none; background: #f7f4f9;">
  <div class="ah-container-wide">
    <div class="text-center mb-12 section-header">
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
        <div class="relative">
          <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider"><?php echo esc_html( ah_field( 'treatments_eyebrow', 'Trusted by 10,000+ patients' ) ); ?></p>
        </div>
      </div>
      <h2 class="text-3xl md:text-4xl lg:text-5xl text-gray-800 font-serif leading-[1.1] mb-4">
        <?php echo wp_kses_post( ah_field( 'treatments_title', 'Our Premium Treatment Solutions' ) ); ?>
      </h2>
      <p class="text-base md:text-lg text-gray-700 leading-[1.7] max-w-2xl mx-auto">
        <?php echo esc_html( ah_field( 'treatments_subtitle', 'Clinically proven treatments delivered with white-glove service.' ) ); ?>
      </p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto" data-stagger>
      <!-- GPHC-FLAG: Branded medication name "Mounjaro" hardcoded as h3 heading -->
      <!-- Mounjaro Card -->
      <div class="hp-treatment-card" data-reveal style="--stagger-index:0">
        <?php
        $mj_image = ah_field( 'home_mounjaro_image', '' );
        if ( $mj_image ) : ?>
          <?php echo wp_get_attachment_image( $mj_image, 'treatment-card', false, array( 'class' => 'w-full h-56 object-cover' ) ); ?>
        <?php else : ?>
          <img src="https://c.animaapp.com/mkl3lxzpWoqisd/img/mounjaro.jpg" alt="Mounjaro" class="w-full h-56 object-cover" />
        <?php endif; ?>
        <div class="p-6">
          <h3 class="text-2xl font-serif text-gray-900 mb-2">Mounjaro</h3>
          <p class="text-purple-600 font-semibold text-sm mb-3"><?php echo esc_html( ah_field( 'home_mounjaro_stat', 'Up to 22.5% loss' ) ); ?></p>
          <p class="text-gray-600 text-sm leading-relaxed mb-4"><?php echo esc_html( ah_field( 'home_mounjaro_desc', 'Dual-action GLP-1 and GIP receptor agonist. The most effective weight loss treatment available.' ) ); ?></p>
          <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'mounjaro' ) ) ); ?>" class="text-purple-600 font-semibold text-sm hover:text-purple-700 transition-colors">View Treatment &rarr;</a>
        </div>
      </div>

      <!-- GPHC-FLAG: Branded medication name "Wegovy" hardcoded as h3 heading -->
      <!-- Wegovy Card -->
      <div class="hp-treatment-card" data-reveal style="--stagger-index:1">
        <?php
        $wg_image = ah_field( 'home_wegovy_image', '' );
        if ( $wg_image ) : ?>
          <?php echo wp_get_attachment_image( $wg_image, 'treatment-card', false, array( 'class' => 'w-full h-56 object-cover' ) ); ?>
        <?php else : ?>
          <img src="https://c.animaapp.com/mkl3lxzpWoqisd/img/wegovy-%281%29.jpg" alt="Wegovy" class="w-full h-56 object-cover" />
        <?php endif; ?>
        <div class="p-6">
          <h3 class="text-2xl font-serif text-gray-900 mb-2">Wegovy</h3>
          <p class="text-purple-600 font-semibold text-sm mb-3"><?php echo esc_html( ah_field( 'home_wegovy_stat', 'Up to 20.7% loss' ) ); ?></p>
          <p class="text-gray-600 text-sm leading-relaxed mb-4"><?php echo esc_html( ah_field( 'home_wegovy_desc', 'GLP-1 receptor agonist with proven cardiovascular benefits and long-term safety data.' ) ); ?></p>
          <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'wegovy' ) ) ); ?>" class="text-purple-600 font-semibold text-sm hover:text-purple-700 transition-colors">View Treatment &rarr;</a>
        </div>
      </div>

      <!-- What's Included Card -->
      <div class="rounded-3xl p-8 text-white" data-reveal style="--stagger-index:2; background: linear-gradient(135deg, var(--ah-purple-600), var(--ah-purple-700));">
        <h3 class="text-2xl font-serif mb-6"><?php echo esc_html( ah_field( 'home_included_title', "What's Included" ) ); ?></h3>
        <ul class="space-y-4">
          <li class="flex items-start gap-3">
            <svg class="w-5 h-5 text-emerald-300 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            <div><p class="font-semibold">Expert Medical Guidance</p><p class="text-white/70 text-sm">Qualified clinicians review your case within 24 hours</p></div>
          </li>
          <li class="flex items-start gap-3">
            <svg class="w-5 h-5 text-emerald-300 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            <div><p class="font-semibold">Proven Treatments</p><p class="text-white/70 text-sm">Access clinically proven GLP-1 medications</p></div>
          </li>
          <li class="flex items-start gap-3">
            <svg class="w-5 h-5 text-emerald-300 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            <div><p class="font-semibold">Ongoing Support</p><p class="text-white/70 text-sm">Monthly check-ins track your progress</p></div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</section>

<?php
get_footer();
?>

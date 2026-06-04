<?php
/**
 * Template Name: Treatments
 * Description: All treatments overview with comparison table.
 */
get_header();
?>

<!-- Hero -->
<section class="py-16 md:py-20" style="background:#fdf8f3;">
  <div class="ah-container text-center">
    <p class="text-purple-600 text-xs font-bold uppercase tracking-wider mb-4"><?php echo esc_html( ah_field( 'tr_eyebrow', 'All Treatments' ) ); ?></p>
    <h1 class="text-4xl md:text-5xl lg:text-6xl font-serif text-gray-900 leading-tight mb-6">
      <?php echo wp_kses_post( ah_field( 'tr_title', 'Choose the right weight loss<br>treatment <span style="color:#7c6fba;">for you</span>' ) ); ?>
    </h1>
    <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-8">
      <?php echo esc_html( ah_field( 'tr_subtitle', 'Mounjaro and Wegovy prescribed by UK-registered independent prescribers. Delivered discreetly to your door within 48 hours.' ) ); ?>
    </p>
    <div class="flex flex-wrap items-center justify-center gap-6 text-sm text-gray-600">
      <?php foreach ( array( 'GPhC Regulated', 'We Verify Identity', '100% Confidential', '48h Delivery' ) as $t ) : ?>
      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
        <span class="font-medium"><?php echo esc_html( $t ); ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Weight Loss Calculator -->
<!-- Moved from homepage Phase 2. Note: calc_* ACF fields are set on the homepage; defaults render here until fields are registered on this page. -->
<!-- GPHC-FLAG: Calculator results panel references "SURMOUNT-1 Clinical Data" and "tirzepatide 15mg" — review before this section goes live -->
<section class="relative w-full py-14 md:py-16 overflow-hidden" style="background: #fdf8f3;">
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
          <!-- GPHC-FLAG: "SURMOUNT-1 Clinical Data" — SURMOUNT-1 is the tirzepatide (Mounjaro) trial, branded by association -->
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
        <!-- GPHC-FLAG: References "tirzepatide 15mg" — branded active ingredient (Mounjaro) -->
        <p class="text-center text-xs text-gray-400">Results based on SURMOUNT-1 clinical trial (tirzepatide 15mg, 72-week data). Individual results may vary.</p>
      </div>
    </div>
  </div>
</section>

<!-- Product Cards -->
<section class="py-14 md:py-16" style="background: #f7f4f9;">
  <div class="ah-container-wide">
    <div class="text-center mb-12 section-header">
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
        <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider">Choose Your Treatment</p>
      </div>
      <h2 class="text-3xl md:text-4xl lg:text-5xl text-gray-800 font-serif leading-[1.1] mb-4">Which treatment is right for you?</h2>
      <p class="text-base md:text-lg text-gray-700 max-w-2xl mx-auto">Both are clinically proven and prescribed by UK-registered doctors.</p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto" data-stagger>
      <!-- Mounjaro Card -->
      <div class="tr-treatment-card" data-reveal style="--stagger-index:0">
        <div class="relative">
          <span class="absolute top-4 left-4 bg-purple-600 text-white text-xs font-bold px-3 py-1 rounded-full">Most Popular</span>
          <?php $mj_img = ah_field( 'tr_mounjaro_image', '' ); ?>
          <?php if ( $mj_img ) : echo wp_get_attachment_image( $mj_img, 'treatment-card', false, array( 'class' => 'w-full h-56 object-cover' ) ); else : ?>
          <img src="https://c.animaapp.com/mkl3lxzpWoqisd/img/mounjaro.jpg" alt="Mounjaro" class="w-full h-56 object-cover" />
          <?php endif; ?>
        </div>
        <div class="p-8">
          <h3 class="text-3xl font-serif text-gray-900 mb-2">Mounjaro</h3>
          <p class="text-purple-600 font-bold text-lg mb-3">22.5% average weight loss</p>
          <p class="text-gray-600 text-[15px] leading-relaxed mb-6"><?php echo esc_html( ah_field( 'tr_mounjaro_desc', 'Dual-action Glucagon-Like Peptide-1 (GLP-1) and Glucose-dependent Insulinotropic Polypeptide (GIP) receptor agonist. The most effective weight loss treatment available with up to 22.5% body weight reduction.' ) ); ?></p>
          <!-- AWAITING PRICE FROM CLIENT -->
          <p class="tr-price"><?php echo esc_html( ah_field( 'tr_mounjaro_price', 'from £XX' ) ); ?></p>
          <div class="flex gap-3">
            <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="flex-1 text-center bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 rounded-xl transition-all">Start Journey</a>
            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'mounjaro' ) ) ); ?>" class="flex-1 text-center border-2 border-gray-200 hover:border-purple-300 text-gray-700 font-semibold py-3 rounded-xl transition-all">Learn More</a>
          </div>
        </div>
      </div>

      <!-- Wegovy Card -->
      <div class="tr-treatment-card" data-reveal style="--stagger-index:1">
        <div class="relative">
          <span class="absolute top-4 left-4 bg-emerald-500 text-white text-xs font-bold px-3 py-1 rounded-full">Proven Results</span>
          <?php $wg_img = ah_field( 'tr_wegovy_image', '' ); ?>
          <?php if ( $wg_img ) : echo wp_get_attachment_image( $wg_img, 'treatment-card', false, array( 'class' => 'w-full h-56 object-cover' ) ); else : ?>
          <img src="https://c.animaapp.com/mkl3lxzpWoqisd/img/wegovy-%281%29.jpg" alt="Wegovy" class="w-full h-56 object-cover" />
          <?php endif; ?>
        </div>
        <div class="p-8">
          <h3 class="text-3xl font-serif text-gray-900 mb-2">Wegovy</h3>
          <p class="text-purple-600 font-bold text-lg mb-3">20.7% average weight loss</p>
          <p class="text-gray-600 text-[15px] leading-relaxed mb-6"><?php echo esc_html( ah_field( 'tr_wegovy_desc', 'GLP-1 receptor agonist with proven cardiovascular benefits. Up to 20.7% body weight reduction and 20% reduced cardiovascular risk.' ) ); ?></p>
          <!-- AWAITING PRICE FROM CLIENT -->
          <p class="tr-price"><?php echo esc_html( ah_field( 'tr_wegovy_price', 'from £XX' ) ); ?></p>
          <div class="flex gap-3">
            <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="flex-1 text-center bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 rounded-xl transition-all">Start Journey</a>
            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'wegovy' ) ) ); ?>" class="flex-1 text-center border-2 border-gray-200 hover:border-purple-300 text-gray-700 font-semibold py-3 rounded-xl transition-all">Learn More</a>
          </div>
        </div>
      </div>

      <!-- Orlistat / Xenical Card -->
      <div class="tr-treatment-card" data-reveal style="--stagger-index:2">
        <div class="relative">
          <span class="absolute top-4 left-4 bg-blue-500 text-white text-xs font-bold px-3 py-1 rounded-full">Tablet Option</span>
          <?php $or_img = ah_field( 'tr_orlistat_image', '' ); ?>
          <?php if ( $or_img ) : echo wp_get_attachment_image( $or_img, 'treatment-card', false, array( 'class' => 'w-full h-56 object-cover' ) ); else : ?>
          <div class="w-full h-56 flex items-center justify-center" style="background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);">
            <svg class="w-20 h-20 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12.75l6 6 9-13.5"/></svg>
          </div>
          <?php endif; ?>
        </div>
        <div class="p-8">
          <h3 class="text-3xl font-serif text-gray-900 mb-2">Orlistat</h3>
          <p class="text-purple-600 font-bold text-lg mb-3">Branded as Xenical</p>
          <p class="text-gray-600 text-[15px] leading-relaxed mb-6"><?php echo esc_html( ah_field( 'tr_orlistat_desc', 'A clinically proven weight loss tablet that reduces the amount of fat your body absorbs from food. Suitable for patients with a Body Mass Index (BMI) of 28 or above.' ) ); ?></p>
          <!-- AWAITING PRICE FROM CLIENT -->
          <p class="tr-price"><?php echo esc_html( ah_field( 'tr_orlistat_price', 'from £XX' ) ); ?></p>
          <div class="flex gap-3">
            <!-- AWAITING PRODUCT PAGE BUILD -->
            <a href="#" class="flex-1 text-center bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 rounded-xl transition-all">Start Journey</a>
            <!-- AWAITING PRODUCT PAGE BUILD -->
            <a href="#" class="flex-1 text-center border-2 border-gray-200 hover:border-purple-300 text-gray-700 font-semibold py-3 rounded-xl transition-all">Learn More</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Comparison Table -->
<section class="py-14 md:py-16" style="background: #fdf8f3;">
  <div class="ah-container-wide">
    <div class="text-center mb-12 section-header">
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
        <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider">Compare Treatments</p>
      </div>
      <h2 class="text-3xl md:text-4xl font-serif text-gray-900 mb-4">Find your perfect match</h2>
    </div>
    <div class="max-w-4xl mx-auto bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm" data-reveal>
      <table class="tr-comparison-table">
        <thead>
          <tr><th></th><th>Mounjaro</th><th>Wegovy</th></tr>
        </thead>
        <tbody>
          <tr><td class="font-semibold text-gray-900">Active Ingredient</td><td>Tirzepatide</td><td>Semaglutide</td></tr>
          <tr><td class="font-semibold text-gray-900">Weight Loss</td><td class="text-purple-700 font-bold">Up to 22.5%</td><td class="text-purple-700 font-bold">Up to 20.7%</td></tr>
          <tr><td class="font-semibold text-gray-900">Starting Dose</td><td>2.5mg</td><td>0.25mg</td></tr>
          <tr><td class="font-semibold text-gray-900">Dosing Plan</td><td>20 weeks to full dose</td><td>16 weeks to full dose</td></tr>
          <tr><td class="font-semibold text-gray-900">Delivery</td><td>Within 48 hours</td><td>Within 48 hours</td></tr>
          <tr><td class="font-semibold text-gray-900">Side Effects</td><td>Nausea, diarrhoea, reduced appetite</td><td>Nausea, diarrhoea, reduced appetite</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- How It Works -->
<?php get_template_part( 'template-parts/section', 'how-it-works' ); ?>

<!-- Dark CTA -->
<section class="relative py-20 md:py-28 overflow-hidden" style="background: #0f1117;">
  <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
    <div class="w-[600px] h-[600px] rounded-full opacity-[0.07]" style="background: radial-gradient(circle, #9b8fce 0%, transparent 70%);"></div>
  </div>
  <div class="max-w-4xl mx-auto px-6 text-center relative z-10" data-reveal>
    <h2 class="text-4xl md:text-5xl lg:text-6xl font-serif text-white leading-[1.05] mb-5">
      Ready to start your<br><em class="not-italic" style="color: #a89dd6;">weight loss journey?</em>
    </h2>
    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-8">
      <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="inline-flex items-center gap-3 bg-white hover:bg-gray-100 text-gray-900 text-[15px] font-semibold px-10 py-4 rounded-xl transition-all hover-lift shadow-xl">Start Free Assessment</a>
      <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'contact-us' ) ?: get_page_by_path( 'contact' ) ) ); ?>" class="inline-flex items-center gap-3 border-2 border-white/20 hover:border-white/40 text-white text-[15px] font-semibold px-10 py-4 rounded-xl transition-all">Speak to Our Team</a>
    </div>
  </div>
</section>

<?php get_footer(); ?>

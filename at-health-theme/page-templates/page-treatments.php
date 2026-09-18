<?php
/**
 * Template Name: Treatments
 * Description: All treatments overview with comparison table.
 */
get_header();
?>

<!-- Hero: copy left, eligibility check right — one section, so no stacked padding between "hero" and "tool" -->
<?php
$tr_bmi_min  = (float) get_option( 'tc_eligibility_min_bmi_default', 27 ); // same threshold the assessment uses
$tr_bmi_full = 30.0;                                                        // NICE obesity threshold
$tr_contact  = get_page_by_path( 'contact' ) ? get_permalink( get_page_by_path( 'contact' ) ) : home_url( '/contact/' );
?>
<section class="tr-hero py-14 md:py-20" style="background:#fdf8f3;">
  <div class="ah-container-wide">
    <div class="grid lg:grid-cols-12 gap-12 lg:gap-16 items-center">

      <div class="lg:col-span-7">
        <div class="flex items-center gap-3 mb-5">
          <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
          <p class="text-purple-600 text-xs font-bold uppercase tracking-wider"><?php echo esc_html( ah_field( 'tr_eyebrow', 'All Treatments' ) ); ?></p>
        </div>
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-serif text-gray-900 leading-[1.05] tracking-[-0.02em] mb-6" style="text-wrap: balance;">
          <?php echo wp_kses_post( ah_field( 'tr_title', 'The right treatment,<br>chosen <span style="color:#7c6fba;">with a prescriber</span>' ) ); ?>
        </h1>
        <p class="text-lg text-gray-600 leading-relaxed max-w-xl mb-8">
          <?php echo esc_html( ah_field( 'tr_subtitle', 'Five prescription treatments, one clinical standard. Every assessment is reviewed by a UK-registered independent prescriber — and nothing is charged unless they approve.' ) ); ?>
        </p>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-gray-700">
          <?php foreach ( array( 'GPhC Regulated', 'We Verify Identity', 'Confidential', 'Delivered within 48 hours' ) as $t ) : ?>
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-purple-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            <span class="font-medium"><?php echo esc_html( $t ); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Eligibility check: a service-eligibility tool, not a drug-efficacy projection. Names no medicine. -->
      <div class="lg:col-span-5">
        <div class="tr-calc-card p-7 md:p-9" id="bmiCheck"
             data-bmi-min="<?php echo esc_attr( $tr_bmi_min ); ?>"
             data-bmi-full="<?php echo esc_attr( $tr_bmi_full ); ?>"
             data-cta="<?php echo esc_url( ah_booking_url() ); ?>"
             data-contact="<?php echo esc_url( $tr_contact ); ?>">
          <div class="inline-flex items-center gap-2 bg-purple-50 border border-purple-100 rounded-full px-4 py-1.5 mb-5">
            <svg class="w-3.5 h-3.5 text-purple-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
            <span class="text-[11px] font-bold text-purple-700 uppercase tracking-[0.14em]"><?php echo esc_html( ah_field( 'tr_bmi_pill', 'Takes 10 seconds' ) ); ?></span>
          </div>
          <h2 class="text-2xl md:text-[1.75rem] font-serif text-gray-900 leading-tight mb-2"><?php echo esc_html( ah_field( 'tr_bmi_title', 'Am I eligible?' ) ); ?></h2>
          <p class="text-sm text-gray-600 leading-relaxed mb-6"><?php echo esc_html( ah_field( 'tr_bmi_subtitle', 'Enter your height and weight for an instant, private indication. Nothing is stored.' ) ); ?></p>

          <form id="bmiForm" novalidate>
            <div class="inline-flex bg-gray-50 border border-gray-200 rounded-full p-1 mb-5" role="group" aria-label="Units">
              <button type="button" data-units="metric" class="tr-unit-btn active px-4 py-1.5 rounded-full text-sm font-semibold" aria-pressed="true">Metric</button>
              <button type="button" data-units="imperial" class="tr-unit-btn px-4 py-1.5 rounded-full text-sm font-semibold text-gray-500" aria-pressed="false">Imperial</button>
            </div>

            <div data-units-panel="metric" class="grid grid-cols-2 gap-3 mb-4">
              <label class="block">
                <span class="block text-xs font-semibold text-gray-700 mb-1.5">Height (cm)</span>
                <input type="number" id="bmiHeightCm" inputmode="decimal" placeholder="e.g. 170" min="120" max="230" step="0.1" class="tr-input" />
              </label>
              <label class="block">
                <span class="block text-xs font-semibold text-gray-700 mb-1.5">Weight (kg)</span>
                <input type="number" id="bmiWeightKg" inputmode="decimal" placeholder="e.g. 95" min="30" max="300" step="0.1" class="tr-input" />
              </label>
            </div>

            <div data-units-panel="imperial" class="grid grid-cols-4 gap-3 mb-4" style="display:none;">
              <label class="block">
                <span class="block text-xs font-semibold text-gray-700 mb-1.5">Height ft</span>
                <input type="number" id="bmiHeightFt" inputmode="numeric" placeholder="5" min="3" max="7" step="1" class="tr-input" />
              </label>
              <label class="block">
                <span class="block text-xs font-semibold text-gray-700 mb-1.5">in</span>
                <input type="number" id="bmiHeightIn" inputmode="numeric" placeholder="7" min="0" max="11" step="1" class="tr-input" />
              </label>
              <label class="block">
                <span class="block text-xs font-semibold text-gray-700 mb-1.5">Weight st</span>
                <input type="number" id="bmiWeightSt" inputmode="numeric" placeholder="15" min="4" max="50" step="1" class="tr-input" />
              </label>
              <label class="block">
                <span class="block text-xs font-semibold text-gray-700 mb-1.5">lb</span>
                <input type="number" id="bmiWeightLb" inputmode="numeric" placeholder="0" min="0" max="13" step="1" class="tr-input" />
              </label>
            </div>

            <p id="bmiError" class="hidden text-sm text-red-600 mb-3" role="alert"></p>

            <button type="submit" class="w-full flex items-center justify-center gap-2.5 bg-purple-600 hover:bg-purple-700 text-white text-base font-semibold px-8 py-4 rounded-xl shadow-lg hover:shadow-xl transition-all hover-lift">
              <?php echo esc_html( ah_field( 'tr_bmi_button', 'Check my eligibility' ) ); ?>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </button>
          </form>

          <div id="bmiResult" class="hidden mt-5" aria-live="polite"></div>

          <p class="text-[12px] text-gray-500 leading-relaxed mt-5">
            <?php echo esc_html( ah_field( 'tr_bmi_note', 'An indication only. Thresholds are lower for some ethnic backgrounds, and the full assessment accounts for this. A prescriber makes the final decision.' ) ); ?>
          </p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- The SURMOUNT-1 weight-loss projection calculator that lived here was removed: it was unstyled and
     non-functional on this template (its CSS/JS shipped with the homepage only), and a "you could lose
     22.5%" projection from a named drug's trial is an efficacy claim for a POM on a public landing page.
     The eligibility check in the hero replaces it. History: git log -- this file. -->

<!-- Product Cards -->
<section class="py-14 md:py-16" style="background: #f7f4f9;">
  <div class="ah-container-wide">
    <div class="text-center mb-12 section-header">
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
        <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider">Choose Your Treatment</p>
      </div>
      <h2 class="text-3xl md:text-4xl lg:text-5xl text-gray-800 font-serif leading-[1.1] mb-4">Which treatment is right for you?</h2>
      <p class="text-base md:text-lg text-gray-700 max-w-2xl mx-auto">All prescribed by UK-registered independent prescribers after a full clinical review.</p>
    </div>

    <!-- Injections: two cards, centred and a little wider, so the pair reads as a set rather than a short row -->
    <div class="max-w-4xl mx-auto mb-6">
      <h3 class="text-lg font-serif text-gray-900">Injections</h3>
      <p class="text-sm text-gray-600">Once weekly. The highest average weight loss in clinical trials.</p>
    </div>
    <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto" data-stagger>
      <!-- Mounjaro Card -->
      <div class="tr-treatment-card" data-reveal style="--stagger-index:0">
        <div class="relative">
          <span class="absolute top-4 left-4 bg-purple-600 text-white text-xs font-bold px-3 py-1 rounded-full">Most Popular</span>
          <?php $mj_img = ah_field( 'tr_mounjaro_image', '' ); ?>
          <?php if ( $mj_img ) : echo wp_get_attachment_image( $mj_img, 'treatment-card', false, array( 'class' => 'w-full h-56 object-cover' ) ); else : ?>
          <img src="https://c.animaapp.com/mkl3lxzpWoqisd/img/mounjaro.jpg" alt="Mounjaro packaging" class="w-full h-56 object-cover" />
          <?php endif; ?>
        </div>
        <div class="p-8">
          <h3 class="text-3xl font-serif text-gray-900 mb-2">Mounjaro</h3>
          <p class="text-purple-600 font-bold text-lg mb-3">22.5% average weight loss</p>
          <p class="text-gray-600 text-[15px] leading-relaxed mb-6"><?php echo esc_html( ah_field( 'tr_mounjaro_desc', 'Dual-action GLP-1 and GIP receptor agonist, taken once a week. Up to 22.5% body weight reduction in clinical trials.' ) ); ?></p>
          <?php $tr_p = (string) ah_field( 'tr_mounjaro_price', '' ); if ( $tr_p !== '' && stripos( $tr_p, 'XX' ) === false ) : ?>
          <p class="tr-price"><?php echo esc_html( $tr_p ); ?></p>
          <?php endif; ?>
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
          <img src="https://c.animaapp.com/mkl3lxzpWoqisd/img/wegovy-%281%29.jpg" alt="Wegovy packaging" class="w-full h-56 object-cover" />
          <?php endif; ?>
        </div>
        <div class="p-8">
          <h3 class="text-3xl font-serif text-gray-900 mb-2">Wegovy</h3>
          <p class="text-purple-600 font-bold text-lg mb-3">20.7% average weight loss</p>
          <p class="text-gray-600 text-[15px] leading-relaxed mb-6"><?php echo esc_html( ah_field( 'tr_wegovy_desc', 'GLP-1 receptor agonist with proven cardiovascular benefits. Up to 20.7% body weight reduction and 20% reduced cardiovascular risk.' ) ); ?></p>
          <?php $tr_p = (string) ah_field( 'tr_wegovy_price', '' ); if ( $tr_p !== '' && stripos( $tr_p, 'XX' ) === false ) : ?>
          <p class="tr-price"><?php echo esc_html( $tr_p ); ?></p>
          <?php endif; ?>
          <div class="flex gap-3">
            <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="flex-1 text-center bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 rounded-xl transition-all">Start Journey</a>
            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'wegovy' ) ) ); ?>" class="flex-1 text-center border-2 border-gray-200 hover:border-purple-300 text-gray-700 font-semibold py-3 rounded-xl transition-all">Learn More</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Oral treatments: three cards fill three columns -->
    <div class="max-w-6xl mx-auto mb-6 mt-16">
      <h3 class="text-lg font-serif text-gray-900">Oral treatments</h3>
      <p class="text-sm text-gray-600">Once daily, needle-free.</p>
    </div>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto" data-stagger>
      <!-- Wegovy Tablets Card -->
      <div class="tr-treatment-card" data-reveal style="--stagger-index:2">
        <div class="relative">
          <span class="absolute top-4 left-4 bg-indigo-500 text-white text-xs font-bold px-3 py-1 rounded-full">Needle-Free</span>
          <?php $wt_img = ah_field( 'tr_wegovy_tablets_image', '' ); ?>
          <?php if ( $wt_img ) : echo wp_get_attachment_image( $wt_img, 'treatment-card', false, array( 'class' => 'w-full h-56 object-cover' ) ); else : ?>
          <?php echo ah_treatment_visual( 'tablet', 'h-56' ); ?>
          <?php endif; ?>
        </div>
        <div class="p-8">
          <h3 class="text-3xl font-serif text-gray-900 mb-2">Wegovy Tablets</h3>
          <p class="text-purple-600 font-bold text-lg mb-3">Up to 16.6% average weight loss</p>
          <p class="text-gray-600 text-[15px] leading-relaxed mb-6"><?php echo esc_html( ah_field( 'tr_wegovy_tablets_desc', 'A once-daily oral form of semaglutide — the same active ingredient as Wegovy® injection — for adults who prefer a needle-free option for weight management.' ) ); ?></p>
          <p class="tr-price"><?php echo esc_html( ah_field( 'tr_wegovy_tablets_price', 'from £99' ) ); ?></p>
          <div class="flex gap-3">
            <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="flex-1 text-center bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 rounded-xl transition-all">Start Journey</a>
            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'wegovy-tablets' ) ) ); ?>" class="flex-1 text-center border-2 border-gray-200 hover:border-purple-300 text-gray-700 font-semibold py-3 rounded-xl transition-all">Learn More</a>
          </div>
        </div>
      </div>

      <!-- Foundayo Card -->
      <div class="tr-treatment-card" data-reveal style="--stagger-index:3">
        <div class="relative">
          <span class="absolute top-4 left-4 bg-indigo-600 text-white text-xs font-bold px-3 py-1 rounded-full">New</span>
          <?php $fd_img = ah_field( 'tr_foundayo_image', '' ); ?>
          <?php if ( $fd_img ) : echo wp_get_attachment_image( $fd_img, 'treatment-card', false, array( 'class' => 'w-full h-56 object-cover' ) ); else : ?>
          <?php echo ah_treatment_visual( 'tablet', 'h-56' ); ?>
          <?php endif; ?>
        </div>
        <div class="p-8">
          <h3 class="text-3xl font-serif text-gray-900 mb-2">Foundayo</h3>
          <p class="text-purple-600 font-bold text-lg mb-3">No fasting rules</p>
          <p class="text-gray-600 text-[15px] leading-relaxed mb-6"><?php echo esc_html( ah_field( 'tr_foundayo_desc', 'Orforglipron — the first non-peptide GLP-1 tablet licensed in the UK. Once daily, taken with or without food, with no waiting period before you eat.' ) ); ?></p>
          <p class="tr-price"><?php echo esc_html( ah_field( 'tr_foundayo_price', 'from £99' ) ); ?></p>
          <div class="flex gap-3">
            <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="flex-1 text-center bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 rounded-xl transition-all">Start Journey</a>
            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'foundayo' ) ) ); ?>" class="flex-1 text-center border-2 border-gray-200 hover:border-purple-300 text-gray-700 font-semibold py-3 rounded-xl transition-all">Learn More</a>
          </div>
        </div>
      </div>

      <!-- Orlistat / Xenical Card -->
      <div class="tr-treatment-card" data-reveal style="--stagger-index:4">
        <div class="relative">
          <span class="absolute top-4 left-4 bg-blue-500 text-white text-xs font-bold px-3 py-1 rounded-full">Capsule</span>
          <?php $or_img = ah_field( 'tr_orlistat_image', '' ); ?>
          <?php if ( $or_img ) : echo wp_get_attachment_image( $or_img, 'treatment-card', false, array( 'class' => 'w-full h-56 object-cover' ) ); else : ?>
          <?php echo ah_treatment_visual( 'tablet', 'h-56' ); ?>
          <?php endif; ?>
        </div>
        <div class="p-8">
          <h3 class="text-3xl font-serif text-gray-900 mb-2">Orlistat</h3>
          <p class="text-purple-600 font-bold text-lg mb-3">Branded as Xenical</p>
          <p class="text-gray-600 text-[15px] leading-relaxed mb-6"><?php echo esc_html( ah_field( 'tr_orlistat_desc', 'A clinically proven weight loss tablet that reduces the amount of fat your body absorbs from food. Suitable for patients with a Body Mass Index (BMI) of 28 or above.' ) ); ?></p>
          <?php $tr_p = (string) ah_field( 'tr_orlistat_price', '' ); if ( $tr_p !== '' && stripos( $tr_p, 'XX' ) === false ) : ?>
          <p class="tr-price"><?php echo esc_html( $tr_p ); ?></p>
          <?php endif; ?>
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
    <div class="max-w-4xl mx-auto space-y-10" data-reveal>

      <!-- Tablets -->
      <div>
        <h3 class="text-lg font-serif text-gray-900 mb-1">Tablets</h3>
        <p class="text-sm text-gray-600 mb-4">Once-daily, needle-free options.</p>
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm tr-table-scroll">
          <table class="tr-comparison-table">
            <thead>
              <tr><th></th><th>Foundayo</th><th>Wegovy Tablets</th></tr>
            </thead>
            <tbody>
              <tr><td class="font-semibold text-gray-900">Active Ingredient</td><td>Orforglipron</td><td>Semaglutide</td></tr>
              <tr><td class="font-semibold text-gray-900">How It's Taken</td><td class="text-purple-700 font-bold">Any time, with or without food</td><td>Empty stomach, 30 min before food</td></tr>
              <tr><td class="font-semibold text-gray-900">Dose Strengths</td><td>Six (0.8mg &ndash; 17.2mg)</td><td>Four (1.5mg &ndash; 25mg)</td></tr>
              <tr><td class="font-semibold text-gray-900">Dosing Plan</td><td>Minimum 30 days per step</td><td>Four tablet strengths</td></tr>
              <tr><td class="font-semibold text-gray-900">Delivery</td><td>Within 48 hours</td><td>Within 48 hours</td></tr>
              <tr><td class="font-semibold text-gray-900">Side Effects</td><td>Nausea, diarrhoea, reduced appetite</td><td>Nausea, diarrhoea, reduced appetite</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Injections -->
      <div>
        <h3 class="text-lg font-serif text-gray-900 mb-1">Injections</h3>
        <p class="text-sm text-gray-600 mb-4">Once-weekly, with the highest average weight loss in clinical trials.</p>
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm tr-table-scroll">
          <table class="tr-comparison-table">
            <thead>
              <tr><th></th><th>Mounjaro</th><th>Wegovy</th></tr>
            </thead>
            <tbody>
              <tr><td class="font-semibold text-gray-900">Active Ingredient</td><td>Tirzepatide</td><td>Semaglutide</td></tr>
              <tr><td class="font-semibold text-gray-900">Weight Loss</td><td class="text-purple-700 font-bold">Up to 22.5%</td><td class="text-purple-700 font-bold">Up to 20.7%</td></tr>
              <tr><td class="font-semibold text-gray-900">How It's Taken</td><td>Once-weekly injection</td><td>Once-weekly injection</td></tr>
              <tr><td class="font-semibold text-gray-900">Starting Dose</td><td>2.5mg</td><td>0.25mg</td></tr>
              <tr><td class="font-semibold text-gray-900">Dosing Plan</td><td>20 weeks to full dose</td><td>16 weeks to full dose</td></tr>
              <tr><td class="font-semibold text-gray-900">Delivery</td><td>Within 48 hours</td><td>Within 48 hours</td></tr>
              <tr><td class="font-semibold text-gray-900">Side Effects</td><td>Nausea, diarrhoea, reduced appetite</td><td>Nausea, diarrhoea, reduced appetite</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <p class="text-sm text-gray-600 text-center">Not sure which is right for you? <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="text-purple-600 font-semibold hover:text-purple-700">Complete the screening assessment</a> and a UK-registered prescriber will recommend the most appropriate option.</p>
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

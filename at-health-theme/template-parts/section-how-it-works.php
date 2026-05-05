<?php
/**
 * Template Part: How It Works Section
 * Three-step process cards with number badges.
 */

$hiw_eyebrow  = ah_field( 'hiw_eyebrow', 'Proven Process' );
$hiw_title    = ah_field( 'hiw_title', 'Start Feeling Confident<br>in Your <span class="text-purple-600">Body Again</span>' );
$hiw_subtitle = ah_field( 'hiw_subtitle', 'Lose 2-4 stone with UK-registered medical support. Walk pain-free, drop clothing sizes, and regain your energy in 6-12 months.' );

$step1_title       = ah_field( 'hiw_step1_title', 'Complete<br>Assessment' );
$step1_description = ah_field( 'hiw_step1_description', 'Answer questions about your health and weight loss goals in our secure online form.' );
$step1_badge       = ah_field( 'hiw_step1_badge', 'Takes 5 minutes' );

$step2_title       = ah_field( 'hiw_step2_title', 'Clinical<br>Review' );
$step2_description = ah_field( 'hiw_step2_description', 'UK-registered prescribers review your assessment and approve your personalised treatment.' );
$step2_badge       = ah_field( 'hiw_step2_badge', 'Same-day approval' );

$step3_title       = ah_field( 'hiw_step3_title', 'Delivered to<br>Your Door' );
$step3_description = ah_field( 'hiw_step3_description', 'Receive your medication discreetly packaged with full support and guidance materials.' );
$step3_badge       = ah_field( 'hiw_step3_badge', 'Within 48 hours' );

$hiw_trust1 = ah_field( 'hiw_trust1', 'No prescription transfer needed' );
$hiw_trust2 = ah_field( 'hiw_trust2', '100% confidential service' );
$hiw_trust3 = ah_field( 'hiw_trust3', 'Cancel anytime' );

$hiw_cta_text    = ah_field( 'hiw_cta_text', 'Start Journey' );
$hiw_cta_url     = ah_field( 'hiw_cta_url', '' );
if ( $hiw_cta_url === null || $hiw_cta_url === '' ) {
    $hiw_cta_url = ah_booking_url();
}
$hiw_social_proof = ah_field( 'hiw_social_proof', 'Over <strong class="text-gray-900 text-lg"><span data-count="10000" data-suffix="+">0</span> patients</strong> have started their journey with AT Health' );
?>

<!-- Premium How It Works Section -->
<section class="relative w-full py-14 md:py-16 overflow-hidden">
  <!-- Clean clinical background -->
  <div class="absolute inset-0" style="background: #f7f4f9;"></div>

  <div class="max-w-[1400px] mx-auto px-6 md:px-[60px] relative z-10">
    <!-- Section Header -->
    <div class="text-center mb-10 md:mb-12 section-header">
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
        <div class="relative">
          <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider"><?php echo esc_html( $hiw_eyebrow ); ?></p>
          <div class="animated-line absolute -bottom-1.5 left-0 h-[2px] bg-purple-600 rounded-full" style="width: 0;"></div>
        </div>
      </div>
      <h2 class="text-3xl md:text-4xl lg:text-5xl text-gray-800 font-serif leading-[1.1] mb-4">
        <?php echo wp_kses_post( $hiw_title ); ?>
      </h2>
      <p class="text-base md:text-lg text-gray-700 leading-[1.7] max-w-2xl mx-auto">
        <?php echo esc_html( $hiw_subtitle ); ?>
      </p>
    </div>

    <!-- Three Premium Steps -->
    <div class="relative max-w-[1200px] mx-auto">
      <!-- Connecting Line (Desktop) -->
      <div class="hidden lg:block absolute top-[140px] left-[16%] right-[16%] h-[2px] bg-purple-200 rounded-full"></div>

      <div class="grid lg:grid-cols-3 gap-8 lg:gap-10">
        <!-- Step 1: Complete Assessment -->
        <div class="relative group" data-reveal style="--stagger-index:0">
          <div class="relative bg-white rounded-[32px] p-8 lg:p-10 shadow-lg border border-gray-200 hover:border-purple-300 transition-all duration-500 hover:-translate-y-2 hover:shadow-xl">
            <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-[100px] h-[100px] bg-gradient-to-br from-purple-600 to-purple-700 rounded-full flex items-center justify-center shadow-2xl border-6 border-white group-hover:scale-110 transition-transform duration-500">
              <span class="text-5xl font-serif font-bold text-white">1</span>
            </div>
            <div class="pt-16 text-center">
              <div class="w-24 h-24 mx-auto mb-6 bg-purple-50 rounded-3xl flex items-center justify-center group-hover:scale-105 transition-transform duration-500">
                <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <h3 class="text-2xl lg:text-3xl font-serif text-gray-900 mb-5 leading-tight">
                <?php echo wp_kses_post( $step1_title ); ?>
              </h3>
              <p class="text-base text-gray-600 leading-relaxed mb-6">
                <?php echo esc_html( $step1_description ); ?>
              </p>
              <div class="inline-flex items-center gap-2 bg-purple-50 text-purple-700 px-5 py-2.5 rounded-full text-sm font-semibold">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                </svg>
                <?php echo esc_html( $step1_badge ); ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 2: Clinical Review -->
        <div class="relative group" data-reveal style="--stagger-index:1">
          <div class="relative bg-white rounded-[32px] p-8 lg:p-10 shadow-lg border border-gray-200 hover:border-purple-300 transition-all duration-500 hover:-translate-y-2 hover:shadow-xl lg:mt-8">
            <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-[100px] h-[100px] bg-gradient-to-br from-purple-600 to-purple-700 rounded-full flex items-center justify-center shadow-2xl border-6 border-white group-hover:scale-110 transition-transform duration-500">
              <span class="text-5xl font-serif font-bold text-white">2</span>
            </div>
            <div class="pt-16 text-center">
              <div class="w-24 h-24 mx-auto mb-6 bg-purple-50 rounded-3xl flex items-center justify-center group-hover:scale-105 transition-transform duration-500">
                <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
              </div>
              <h3 class="text-2xl lg:text-3xl font-serif text-gray-900 mb-5 leading-tight"><?php echo wp_kses_post( $step2_title ); ?></h3>
              <p class="text-base text-gray-600 leading-relaxed mb-4">
                <?php echo esc_html( $step2_description ); ?>
              </p>
              <p class="text-base text-gray-600 leading-relaxed mb-6">Before any prescription is issued, one of our independent pharmacist prescribers will review your consultation in full. We may contact you for further information if required.</p>
              <div class="inline-flex items-center gap-2 bg-purple-50 text-purple-700 px-5 py-2.5 rounded-full text-sm font-semibold">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <?php echo esc_html( $step2_badge ); ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 3: Delivered to Your Door -->
        <div class="relative group" data-reveal style="--stagger-index:2">
          <div class="relative bg-white rounded-[32px] p-8 lg:p-10 shadow-lg border border-gray-200 hover:border-purple-300 transition-all duration-500 hover:-translate-y-2 hover:shadow-xl">
            <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-[100px] h-[100px] bg-gradient-to-br from-purple-600 to-purple-700 rounded-full flex items-center justify-center shadow-2xl border-6 border-white group-hover:scale-110 transition-transform duration-500">
              <span class="text-5xl font-serif font-bold text-white">3</span>
            </div>
            <div class="pt-16 text-center">
              <div class="w-24 h-24 mx-auto mb-6 bg-purple-50 rounded-3xl flex items-center justify-center group-hover:scale-105 transition-transform duration-500">
                <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
              </div>
              <h3 class="text-2xl lg:text-3xl font-serif text-gray-900 mb-5 leading-tight">
                <?php echo wp_kses_post( $step3_title ); ?>
              </h3>
              <p class="text-base text-gray-600 leading-relaxed mb-6">
                <?php echo esc_html( $step3_description ); ?>
              </p>
              <div class="inline-flex items-center gap-2 bg-purple-50 text-purple-700 px-5 py-2.5 rounded-full text-sm font-semibold">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                  <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z" />
                </svg>
                <?php echo esc_html( $step3_badge ); ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Premium CTA Section -->
    <div class="text-center mt-10 md:mt-12">
      <!-- Trust Line -->
      <div class="flex flex-wrap items-center justify-center gap-8 mb-10 text-gray-700">
        <?php $trust_items = array( $hiw_trust1, $hiw_trust2, $hiw_trust3 ); ?>
        <?php foreach ( $trust_items as $trust ) : ?>
        <div class="flex items-center gap-2.5">
          <svg class="w-5 h-5 text-purple-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
          <span class="text-base font-semibold"><?php echo esc_html( $trust ); ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Main CTA -->
      <a href="<?php echo esc_url( $hiw_cta_url ); ?>" class="inline-flex items-center justify-center gap-3 bg-purple-600 hover:bg-purple-700 text-white text-lg md:text-xl font-semibold px-14 py-6 rounded-2xl shadow-2xl hover:shadow-3xl transition-all hover-lift">
        <?php echo esc_html( $hiw_cta_text ); ?>
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
        </svg>
      </a>

      <!-- Social Proof -->
      <p class="text-gray-700 text-base mt-5">
        <?php echo wp_kses_post( $hiw_social_proof ); ?>
      </p>
    </div>
  </div>
</section>

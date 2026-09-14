<?php
/**
 * Template Part: CTA Section
 * Dark background CTA with trust strip.
 */

$cta_eyebrow     = ah_field( 'cta_eyebrow', 'Start Today' );
$cta_title       = ah_field( 'cta_title', 'Ready to start<br><em class="not-italic" style="color: #a89dd6;">your journey?</em>' );
$cta_subtitle    = ah_field( 'cta_subtitle', 'Start with a five-minute assessment. A UK-registered prescriber reviews it within 24 hours — and nothing is charged unless they approve.' );
$cta_button_text = ah_field( 'cta_button_text', 'Start Your Journey' );
$cta_button_url  = ah_field( 'cta_button_url', '' );
if ( $cta_button_url === null || $cta_button_url === '' ) {
    $cta_button_url = ah_booking_url();
}

$cta_trust1 = ah_field( 'cta_trust1', 'GPhC Regulated' );
$cta_trust2 = ah_field( 'cta_trust2', 'Cancel Anytime' );
$cta_trust3 = ah_field( 'cta_trust3', 'Discreet Delivery' );
$cta_trust4 = ah_field( 'cta_trust4', 'Reviewed within 24 hours' );
?>

<!-- CTA Section -->
<section class="relative py-20 md:py-28 overflow-hidden" style="background: #0f1117;">
  <!-- Subtle radial glow -->
  <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
    <div class="w-[600px] h-[600px] rounded-full opacity-[0.07]" style="background: radial-gradient(circle, #9b8fce 0%, transparent 70%);"></div>
  </div>

  <div class="max-w-4xl mx-auto px-6 text-center relative z-10" data-reveal>
    <!-- Eyebrow -->
    <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-gray-500 mb-6"><?php echo esc_html( $cta_eyebrow ); ?></p>

    <!-- Heading -->
    <h2 class="text-4xl md:text-5xl lg:text-6xl font-serif text-white leading-[1.05] mb-5 tracking-[-0.02em]">
      <?php echo wp_kses_post( $cta_title ); ?>
    </h2>

    <!-- Subline -->
    <p class="text-base md:text-lg text-gray-400 leading-relaxed mb-10 max-w-xl mx-auto">
      <?php echo esc_html( $cta_subtitle ); ?>
    </p>

    <!-- CTA Button -->
    <a
      href="<?php echo esc_url( $cta_button_url ); ?>"
      class="inline-flex items-center gap-3 bg-white hover:bg-gray-100 text-gray-900 text-[15px] font-semibold px-10 py-4 rounded-xl transition-all hover-lift shadow-xl mb-10"
    >
      <?php echo esc_html( $cta_button_text ); ?>
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
      </svg>
    </a>

    <!-- Trust strip -->
    <div class="flex flex-wrap items-center justify-center gap-x-8 gap-y-3 border-t border-white/[0.07] pt-8">
      <?php $trust_items = array( $cta_trust1, $cta_trust2, $cta_trust3, $cta_trust4 ); ?>
      <?php foreach ( $trust_items as $trust ) : ?>
      <div class="flex items-center gap-2 text-gray-400 text-sm">
        <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
        </svg>
        <?php echo esc_html( $trust ); ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

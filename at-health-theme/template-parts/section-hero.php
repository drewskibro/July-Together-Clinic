<?php
/**
 * Template Part: Hero Section
 * Used on the homepage.
 */

$hero_eyebrow   = ah_field( 'hero_eyebrow', 'Pharmacist-led. UK Registered.' );
$hero_title     = ah_field( 'hero_title', 'Your health, in <em>trusted</em> hands.' );
$hero_subtitle  = ah_field( 'hero_subtitle', 'Together Clinic is a GPhC-registered online pharmacy built around you. Expert pharmacist prescribers, transparent pricing, and care that puts your wellbeing first — all from the comfort of home.' );
$hero_cta_text  = ah_field( 'hero_cta_text', 'Start Your Journey' );
$hero_cta_url   = ah_field( 'hero_cta_url', '' );
if ( $hero_cta_url === null || $hero_cta_url === '' ) {
    $hero_cta_url = ah_booking_url();
}
$hero_image     = ah_field( 'hero_image', '' );
$hero_image_alt = ah_field( 'hero_image_alt', 'Woman in kitchen feeling confident and healthy' );
?>

<!-- Premium Hero Section 2025 -->
<section class="ah-hero-section relative w-full overflow-hidden" style="background: #fdf8f3;">
  <div class="max-w-[1920px] mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-2 min-h-0">
      <!-- Left Column: Content -->
      <div class="flex flex-col justify-center order-2 lg:order-1 px-6 md:px-12 lg:px-20 xl:px-28 py-12 lg:py-16">
        <!-- Eyebrow -->
        <div class="mb-4 opacity-0 animate-fade-in-up delay-100" style="animation-fill-mode: forwards;">
          <p class="text-gray-400 text-[11px] font-bold uppercase tracking-[0.25em]">
            <?php echo esc_html( $hero_eyebrow ); ?>
          </p>
        </div>

        <!-- Headline -->
        <h1
          class="text-[2.75rem] sm:text-[3.25rem] md:text-[3.75rem] lg:text-[4.25rem] xl:text-[5rem] 2xl:text-[5.5rem] leading-[1.02] tracking-[-0.035em] mb-5 opacity-0 animate-fade-in-up delay-200"
          style="animation-fill-mode: forwards;"
        >
          <?php echo wp_kses_post( $hero_title ); ?>
        </h1>

        <!-- Subheadline -->
        <p
          class="text-[15px] md:text-[17px] text-gray-500 leading-[1.7] mb-8 max-w-[520px] opacity-0 animate-fade-in-up delay-300"
          style="animation-fill-mode: forwards;"
        >
          <?php echo esc_html( $hero_subtitle ); ?>
        </p>

        <!-- CTA -->
        <div
          class="flex items-center gap-5 opacity-0 animate-fade-in-up delay-400"
          style="animation-fill-mode: forwards;"
        >
          <a
            href="<?php echo esc_url( $hero_cta_url ); ?>"
            class="ah-hero-cta inline-flex items-center justify-center gap-2.5 text-white text-[15px] font-semibold px-9 py-4 rounded-lg transition-all"
            style="background: #8e88d0;"
          >
            <?php echo esc_html( $hero_cta_text ); ?>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
          </a>
        </div>
        <!-- Trust row -->
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mt-6 opacity-0 animate-fade-in-up delay-[500ms]" style="animation-fill-mode: forwards;">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span class="text-xs text-gray-500 font-medium">GPhC Regulated</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span class="text-xs text-gray-500 font-medium">100% Confidential</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <span class="text-xs text-gray-500 font-medium">48hr Delivery</span>
          </div>
        </div>
        <p class="text-xs text-gray-400 mt-2">Regulated by the General Pharmaceutical Council (GPhC)</p>

        <!-- Social proof line -->
        <p class="ah-hero-social-proof text-xs text-gray-400 mt-4 flex items-center gap-2 opacity-0 animate-fade-in-up delay-[600ms]" style="animation-fill-mode: forwards;">
          <svg class="w-3.5 h-3.5 text-purple-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
          </svg>
          Trusted by 10,000+ patients across the UK
        </p>
      </div>

      <!-- Right Column: Hero image -->
      <div class="order-1 lg:order-2 opacity-0 animate-fade-in-up delay-300" style="animation-fill-mode: forwards;">
        <div class="relative w-full h-[340px] sm:h-[420px] lg:h-full lg:min-h-[520px]">
          <?php if ( $hero_image !== null && $hero_image !== '' ) : ?>
            <?php echo wp_get_attachment_image( $hero_image, 'full', false, array(
              'class' => 'absolute inset-0 w-full h-full object-cover object-[15%]',
              'alt'   => esc_attr( $hero_image_alt ),
            ) ); ?>
          <?php else : ?>
            <img
              src="https://c.animaapp.com/mkl3lxzpWoqisd/img/uploaded-asset-1774866928466-0.jpeg"
              alt="<?php echo esc_attr( $hero_image_alt ); ?>"
              class="absolute inset-0 w-full h-full object-cover object-[15%]"
            />
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

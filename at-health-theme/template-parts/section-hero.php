<?php
/**
 * Template Part: Hero Section
 * Used on the homepage.
 *
 * Left column is five elements, top to bottom: eyebrow, headline, subtitle,
 * action row (primary CTA + returning-patient link), proof strip. Every
 * trust claim lives once, in the proof strip — nothing is repeated below it.
 * Two text tones only: ink (gray-900/700) for content, gray-600 for support.
 * All small text clears WCAG AA (4.5:1) on the cream ground.
 */

$hero_eyebrow   = ah_field( 'hero_eyebrow', 'Pharmacist-led. UK Registered.' );
$hero_title     = ah_field( 'hero_title', 'Your health, in <em>trusted</em> hands.' );
$hero_subtitle  = ah_field( 'hero_subtitle', 'Together Clinic is a GPhC-registered online pharmacy built around you. Expert pharmacist prescribers, transparent pricing, and care that puts your wellbeing first — all from the comfort of home.' );
$hero_cta_text  = ah_field( 'hero_cta_text', 'Start Your Journey' );
$hero_cta_url   = ah_field( 'hero_cta_url', '' );
if ( $hero_cta_url === null || $hero_cta_url === '' ) {
    $hero_cta_url = ah_booking_url();
}
$hero_assurance = ah_field( 'hero_assurance', 'Nothing is charged until a prescriber approves your treatment. Treatments from £99 a month, no subscription.' );
$hero_image     = ah_field( 'hero_image', '' );
$hero_image_alt = ah_field( 'hero_image_alt', 'Woman in kitchen feeling confident and healthy' );
?>

<!-- Premium Hero Section 2025 -->
<section class="ah-hero-section relative w-full overflow-hidden" style="background: #fdf8f3;">
  <div class="max-w-[1920px] mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-2 min-h-0">
      <!-- Left Column: Content -->
      <div class="flex flex-col justify-center order-2 lg:order-1 px-6 md:px-12 lg:px-20 xl:px-28 py-12 lg:py-20">

        <!-- Eyebrow: brand marker + ink text (the marker carries the colour; the text carries the contrast) -->
        <div class="flex items-center gap-3 mb-6 opacity-0 animate-fade-in-up delay-100" style="animation-fill-mode: forwards;">
          <span class="ah-hero-eyebrow-bar" aria-hidden="true"></span>
          <p class="text-[12px] font-semibold uppercase tracking-[0.18em] text-gray-700">
            <?php echo esc_html( $hero_eyebrow ); ?>
          </p>
        </div>

        <!-- Headline -->
        <h1
          class="text-[2.75rem] sm:text-[3.25rem] md:text-[3.75rem] lg:text-[4.25rem] xl:text-[5rem] 2xl:text-[5.5rem] leading-[1.02] tracking-[-0.035em] text-gray-900 mb-6 opacity-0 animate-fade-in-up delay-200"
          style="animation-fill-mode: forwards;"
        >
          <?php echo wp_kses_post( $hero_title ); ?>
        </h1>

        <!-- Subheadline -->
        <p
          class="text-[16px] md:text-[18px] text-gray-600 leading-[1.65] mb-9 max-w-[520px] opacity-0 animate-fade-in-up delay-300"
          style="animation-fill-mode: forwards;"
        >
          <?php echo esc_html( $hero_subtitle ); ?>
        </p>

        <!-- Action row: primary CTA + returning-patient link on one baseline -->
        <div
          class="flex flex-wrap items-center gap-x-7 gap-y-4 opacity-0 animate-fade-in-up delay-400"
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
          <p class="ah-hero-login text-[14px] text-gray-600">
            Already a patient? <a href="/my-account/" class="inline-block py-2.5 -my-2.5 font-semibold text-gray-900 transition-colors duration-200">Log in to reorder</a>
          </p>
        </div>

        <!-- Assurance: the payment promise (card held, never charged unless approved) and the entry price -->
        <?php if ( $hero_assurance !== null && $hero_assurance !== '' ) : ?>
        <p class="flex items-start gap-2 mt-4 text-[13.5px] leading-[1.5] text-gray-600 max-w-[520px] opacity-0 animate-fade-in-up delay-[450ms]" style="animation-fill-mode: forwards;">
          <svg class="w-4 h-4 mt-[2px] text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span><?php echo esc_html( $hero_assurance ); ?></span>
        </p>
        <?php endif; ?>

        <!-- Proof strip: three claims, once each, at a readable size -->
        <ul class="ah-hero-proof flex flex-wrap items-center gap-x-7 gap-y-3 mt-10 pt-6 opacity-0 animate-fade-in-up delay-[500ms]" style="animation-fill-mode: forwards;">
          <li class="flex items-center gap-2 text-[13px] font-medium text-gray-700">
            <svg class="w-4 h-4 text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            GPhC-registered pharmacy
          </li>
          <li class="flex items-center gap-2 text-[13px] font-medium text-gray-700">
            <svg class="w-4 h-4 text-purple-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            Rated 4.9/5 by verified patients
          </li>
          <li class="flex items-center gap-2 text-[13px] font-medium text-gray-700">
            <svg class="w-4 h-4 text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Confidential, discreet packaging
          </li>
        </ul>
      </div>

      <!-- Right Column: Hero image -->
      <div class="order-1 lg:order-2 opacity-0 animate-fade-in-up delay-300" style="animation-fill-mode: forwards;">
        <div class="relative w-full h-[340px] sm:h-[420px] lg:h-full lg:min-h-[560px] lg:rounded-l-[2.5rem] overflow-hidden">
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

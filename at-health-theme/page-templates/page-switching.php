<?php
/**
 * Template Name: Switching Providers
 * Description: Provider switching page — full conversion from static HTML with comparison table and FAQ.
 * (Template patient numbers, ratings and testimonials removed in the compliance review:
 * Together Clinic has no patient history to evidence them.)
 */
get_header();
?>

<?php // Inline styles for comparison table ?>
<style>
  .at-col-cell {
    background: linear-gradient(180deg, #2d1f6e 0%, #1e1650 100%);
    position: relative;
  }
  .at-col-cell::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(161,140,255,0.06) 0%, transparent 60%);
    pointer-events: none;
  }
  .cmp-row-light { background: #ffffff; }
  .cmp-row-tinted { background: #faf9ff; }
  .cmp-row:hover .cmp-feature-cell { background: #f5f2ff; }
  .cmp-row:hover .other-cell { background: #f5f2ff; }
  @media (max-width: 767px) {
    .cmp-desktop { display: none !important; }
    .cmp-mobile-cards { display: flex !important; }
  }
  @media (min-width: 768px) {
    .cmp-desktop { display: block !important; }
    .cmp-mobile-cards { display: none !important; }
  }
  @keyframes savings-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(52,211,153,0.3); }
    50% { box-shadow: 0 0 0 6px rgba(52,211,153,0); }
  }
  .savings-badge { animation: savings-pulse 2.5s infinite; }
</style>

  <?php
  // Hero mirrors the homepage hero (section-hero.php): same eyebrow marker,
  // ink headline with one italic accent, brand CTA + returning-patient link,
  // assurance line and a single proof strip. Shared styles live in globals.css.
  $sw_eyebrow  = ah_field( 'sw_eyebrow', 'Switching providers' );
  $sw_title    = ah_field( 'sw_title', 'Switch provider with <em>confidence</em>.' );
  $sw_subtitle = ah_field( 'sw_subtitle', 'Already on weight management treatment with another provider? We review your current treatment and dose, so switching doesn\'t have to mean starting again from the lowest dose.' );
  $sw_image    = ah_field( 'sw_hero_image', '' );
  ?>
  <section class="ah-hero-section relative w-full overflow-hidden" style="background: #fdf8f3;">
    <div class="max-w-[1920px] mx-auto">
      <div class="grid grid-cols-1 lg:grid-cols-2 min-h-0">

        <?php $sw_has_photo = ( $sw_image !== null && $sw_image !== '' ); ?>
        <!-- Left: Content (photo leads on mobile, like the homepage; the designed panel follows the text) -->
        <div class="flex flex-col justify-center <?php echo $sw_has_photo ? 'order-2' : 'order-1'; ?> lg:order-1 px-6 md:px-12 lg:px-20 xl:px-28 py-12 lg:py-20">
          <div class="flex items-center gap-3 mb-6 opacity-0 animate-fade-in-up delay-100" style="animation-fill-mode: forwards;">
            <span class="ah-hero-eyebrow-bar" aria-hidden="true"></span>
            <p class="text-[12px] font-semibold uppercase tracking-[0.18em] text-gray-700"><?php echo esc_html( $sw_eyebrow ); ?></p>
          </div>

          <h1 class="text-[2.75rem] sm:text-[3.25rem] md:text-[3.75rem] lg:text-[4.25rem] xl:text-[5rem] 2xl:text-[5.5rem] leading-[1.02] tracking-[-0.035em] text-gray-900 mb-6 opacity-0 animate-fade-in-up delay-200" style="animation-fill-mode: forwards;">
            <?php echo wp_kses_post( $sw_title ); ?>
          </h1>

          <p class="text-[16px] md:text-[18px] text-gray-600 leading-[1.65] mb-9 max-w-[520px] opacity-0 animate-fade-in-up delay-300" style="animation-fill-mode: forwards;">
            <?php echo esc_html( $sw_subtitle ); ?>
          </p>

          <div class="flex flex-wrap items-center gap-x-7 gap-y-4 opacity-0 animate-fade-in-up delay-400" style="animation-fill-mode: forwards;">
            <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="ah-hero-cta inline-flex items-center justify-center gap-2.5 text-white text-[15px] font-semibold px-9 py-4 rounded-lg transition-all" style="background: #8e88d0;">
              Start Your Switch
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
            <p class="ah-hero-login text-[14px] text-gray-600">
              Already a patient? <a href="/my-account/" class="inline-block py-2.5 -my-2.5 font-semibold text-gray-900 transition-colors duration-200">Log in to reorder</a>
            </p>
          </div>

          <p class="flex items-start gap-2 mt-4 text-[13.5px] leading-[1.5] text-gray-600 max-w-[520px] opacity-0 animate-fade-in-up delay-[450ms]" style="animation-fill-mode: forwards;">
            <svg class="w-4 h-4 mt-[2px] text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>Nothing is charged until a prescriber approves your treatment.</span>
          </p>

          <ul class="ah-hero-proof flex flex-wrap items-center gap-x-7 gap-y-3 opacity-0 animate-fade-in-up delay-[500ms]" style="animation-fill-mode: forwards;">
          <li class="flex items-center gap-2 text-[13px] font-medium text-gray-700">
            <svg class="w-4 h-4 text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            GPhC-registered pharmacy
          </li>
          <li class="flex items-center gap-2 text-[13px] font-medium text-gray-700">
            <svg class="w-4 h-4 text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
            Current dose reviewed
          </li>
          <li class="flex items-center gap-2 text-[13px] font-medium text-gray-700">
            <svg class="w-4 h-4 text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
            No prescription transfer needed
          </li>
          </ul>
        </div>

        <!-- Right: photo if one is chosen in wp-admin (Switching: Hero Image),
             otherwise a designed panel — no stock photography by default. -->
        <div class="<?php echo $sw_has_photo ? 'order-1' : 'order-2'; ?> lg:order-2 opacity-0 animate-fade-in-up delay-300" style="animation-fill-mode: forwards;">
          <div class="relative w-full h-full <?php echo $sw_has_photo ? 'min-h-[340px] sm:min-h-[420px]' : ''; ?> lg:min-h-[600px] lg:rounded-l-[2.5rem] overflow-hidden<?php echo ( $sw_image === null || $sw_image === '' ) ? ' sw-hero-panel' : ''; ?>">
            <?php if ( $sw_image !== null && $sw_image !== '' ) : ?>
              <?php echo wp_get_attachment_image( $sw_image, 'full', false, array( 'class' => 'absolute inset-0 w-full h-full object-cover' ) ); ?>
            <?php else : ?>
            <div class="relative h-full flex items-center justify-center px-6 py-12 md:px-12">
              <div class="w-full max-w-[440px] space-y-4">
                <div class="bg-white rounded-3xl p-7 md:p-9 shadow-xl border border-white/60">
                  <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-600 mb-2">What your prescriber reviews</p>
                  <p class="font-serif text-2xl text-gray-900 leading-tight mb-6">A full clinical review, not a transfer.</p>
                  <ul class="space-y-3.5">
                  <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 mt-0.5 w-5 h-5 rounded-full flex items-center justify-center" style="background:#ede6f8; color:#7d76ba;"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg></span>
                    <span class="text-[14.5px] text-gray-700 leading-snug">The medicine you take now, and your current dose</span>
                  </li>
                  <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 mt-0.5 w-5 h-5 rounded-full flex items-center justify-center" style="background:#ede6f8; color:#7d76ba;"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg></span>
                    <span class="text-[14.5px] text-gray-700 leading-snug">When you last took it, and how you have got on</span>
                  </li>
                  <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 mt-0.5 w-5 h-5 rounded-full flex items-center justify-center" style="background:#ede6f8; color:#7d76ba;"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg></span>
                    <span class="text-[14.5px] text-gray-700 leading-snug">Any side effects, and your health since you started</span>
                  </li>
                  <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 mt-0.5 w-5 h-5 rounded-full flex items-center justify-center" style="background:#ede6f8; color:#7d76ba;"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg></span>
                    <span class="text-[14.5px] text-gray-700 leading-snug">The right dose for you to continue on</span>
                  </li>
                  </ul>
                </div>
                <div class="sm:ml-10 bg-white/95 backdrop-blur-md rounded-2xl p-5 shadow-xl border border-gray-200/60">
                <div class="flex items-center gap-3 mb-2">
                  <span class="w-9 h-9 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg,#f3eff9,#ede6f8); color:#7d76ba;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                  </span>
                  <span class="font-serif text-3xl text-gray-900 leading-none">1,000+</span>
                </div>
                <?php /* Wording approved verbatim by the superintendent — do not edit. */ ?>
                <p class="text-[13px] text-gray-600 leading-snug">Our pharmacist prescribers have supported over 1,000 patients across the AT Health group.</p>
              </div>
              </div>
            </div>
            <?php endif; ?>

            <?php if ( $sw_image !== null && $sw_image !== '' ) : ?>
            <!-- Group proof point over the photo -->
            <div class="absolute bottom-5 left-5 right-5 sm:right-auto sm:bottom-8 sm:left-8 sm:max-w-[300px] bg-white/95 backdrop-blur-md rounded-2xl p-5 shadow-xl border border-gray-200/60">
                <div class="flex items-center gap-3 mb-2">
                  <span class="w-9 h-9 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg,#f3eff9,#ede6f8); color:#7d76ba;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                  </span>
                  <span class="font-serif text-3xl text-gray-900 leading-none">1,000+</span>
                </div>
                <?php /* Wording approved verbatim by the superintendent — do not edit. */ ?>
                <p class="text-[13px] text-gray-600 leading-snug">Our pharmacist prescribers have supported over 1,000 patients across the AT Health group.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>
  </section>


  <!-- ═══════════════════════════════════════════════════ -->
  <!-- HOW TO SWITCH — 3-step process on lavender bg      -->
  <!-- ═══════════════════════════════════════════════════ -->
  <section class="relative py-14 md:py-16 overflow-hidden">
    <div class="absolute inset-0" style="background: #f7f4f9;"></div>

    <div class="max-w-[1400px] mx-auto px-6 md:px-[60px] relative z-10">
      <div class="text-center mb-10 md:mb-12 section-header" data-reveal>
        <div class="flex items-center justify-center gap-3 mb-4">
          <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
          <div class="relative">
            <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider">Simple Process</p>
            <div class="animated-line absolute -bottom-1.5 left-0 h-[2px] bg-purple-600 rounded-full" style="width: 0;"></div>
          </div>
        </div>
        <h2 class="text-3xl md:text-4xl lg:text-5xl text-gray-800 font-serif leading-[1.1] mb-4">
          How Switching <span class="text-purple-600">Works</span>
        </h2>
        <p class="text-base md:text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
          No paperwork, no phone calls, no hassle. Just three quick steps.
        </p>
      </div>

      <!-- Steps -->
      <div class="relative max-w-[1200px] mx-auto">
        <div class="hidden lg:block absolute top-[140px] left-[16%] right-[16%] h-[2px] bg-purple-200 rounded-full"></div>
        <div class="grid lg:grid-cols-3 gap-8 lg:gap-10" data-stagger>

          <!-- Step 1 -->
          <div class="relative group" data-reveal style="--stagger-index:0">
            <div class="relative bg-white rounded-[32px] p-8 lg:p-10 shadow-lg border border-gray-200/80 hover:border-purple-300 transition-all duration-500 hover:-translate-y-2 hover:shadow-xl">
              <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-[100px] h-[100px] bg-gradient-to-br from-purple-600 to-purple-700 rounded-full flex items-center justify-center shadow-2xl border-6 border-white group-hover:scale-110 transition-transform duration-500">
                <span class="text-5xl font-serif font-bold text-white">1</span>
              </div>
              <div class="pt-16 text-center">
                <div class="w-24 h-24 mx-auto mb-6 bg-purple-50 rounded-3xl flex items-center justify-center group-hover:scale-105 transition-transform duration-500">
                  <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-2xl lg:text-3xl font-serif text-gray-900 mb-5 leading-tight">Tell Us<br/>About You</h3>
                <p class="text-base text-gray-600 leading-relaxed mb-6">Complete a quick online assessment with your current treatment details and medical history.</p>
                <div class="inline-flex items-center gap-2 bg-purple-50 text-purple-700 px-5 py-2.5 rounded-full text-sm font-semibold">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                  Takes 5 minutes
                </div>
              </div>
            </div>
          </div>

          <!-- Step 2 -->
          <div class="relative group" data-reveal style="--stagger-index:1">
            <div class="relative bg-white rounded-[32px] p-8 lg:p-10 shadow-lg border border-gray-200/80 hover:border-purple-300 transition-all duration-500 hover:-translate-y-2 hover:shadow-xl lg:mt-8">
              <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-[100px] h-[100px] bg-gradient-to-br from-purple-600 to-purple-700 rounded-full flex items-center justify-center shadow-2xl border-6 border-white group-hover:scale-110 transition-transform duration-500">
                <span class="text-5xl font-serif font-bold text-white">2</span>
              </div>
              <div class="pt-16 text-center">
                <div class="w-24 h-24 mx-auto mb-6 bg-purple-50 rounded-3xl flex items-center justify-center group-hover:scale-105 transition-transform duration-500">
                  <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <h3 class="text-2xl lg:text-3xl font-serif text-gray-900 mb-5 leading-tight">Clinical<br/>Approval</h3>
                <p class="text-base text-gray-600 leading-relaxed mb-6">Our UK-registered prescribers review your case and current dose in full before approving your treatment continuation.</p>
                <div class="inline-flex items-center gap-2 bg-purple-50 text-purple-700 px-5 py-2.5 rounded-full text-sm font-semibold">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                  Full clinical review
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3 -->
          <div class="relative group" data-reveal style="--stagger-index:2">
            <div class="relative bg-white rounded-[32px] p-8 lg:p-10 shadow-lg border border-gray-200/80 hover:border-purple-300 transition-all duration-500 hover:-translate-y-2 hover:shadow-xl">
              <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-[100px] h-[100px] bg-gradient-to-br from-purple-600 to-purple-700 rounded-full flex items-center justify-center shadow-2xl border-6 border-white group-hover:scale-110 transition-transform duration-500">
                <span class="text-5xl font-serif font-bold text-white">3</span>
              </div>
              <div class="pt-16 text-center">
                <div class="w-24 h-24 mx-auto mb-6 bg-purple-50 rounded-3xl flex items-center justify-center group-hover:scale-105 transition-transform duration-500">
                  <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <h3 class="text-2xl lg:text-3xl font-serif text-gray-900 mb-5 leading-tight">Medication<br/>Delivered</h3>
                <p class="text-base text-gray-600 leading-relaxed mb-6">Your medication is dispatched to your door discreetly packaged and fully tracked.</p>
                <div class="inline-flex items-center gap-2 bg-purple-50 text-purple-700 px-5 py-2.5 rounded-full text-sm font-semibold">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg>
                  Tracked &amp; discreet
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>


  <!-- ═══════════════════════════════════════════════════ -->
  <!-- WHY PATIENTS SWITCH — 3-col benefits on cream bg   -->
  <!-- ═══════════════════════════════════════════════════ -->
  <section class="relative py-14 md:py-16 overflow-hidden">
    <div class="absolute inset-0" style="background: #fdf8f3;"></div>

    <div class="max-w-[1400px] mx-auto px-6 md:px-[60px] relative z-10">
      <div class="text-center mb-10 md:mb-12" data-reveal>
        <div class="flex items-center justify-center gap-3 mb-4">
          <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
          <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider">The Together Clinic Difference</p>
        </div>
        <h2 class="text-3xl md:text-4xl lg:text-5xl text-gray-800 font-serif leading-[1.1] mb-4">
          Why Patients Switch to <span class="text-purple-600">Together Clinic</span>
        </h2>
        <p class="text-base md:text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
          Lower prices, faster delivery, and support that actually supports you.
        </p>
      </div>

      <div class="grid md:grid-cols-3 gap-6 lg:gap-8" data-stagger>

        <!-- Pay Less -->
        <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-200/80 hover:shadow-lg hover:border-purple-200 transition-all duration-500 hover:-translate-y-1" data-reveal style="--stagger-index:0">
          <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center mb-6">
            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>
          </div>
          <h3 class="text-2xl font-serif text-gray-900 mb-4">Pay Less for the Same Treatment</h3>
          <ul class="space-y-3">
            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-gray-600">Transparent pricing with no hidden consultation fees</span></li>
            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-gray-600">Mounjaro from &pound;199/month, Wegovy from &pound;179/month</span></li>
            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-gray-600">Cancel anytime with no penalties</span></li>
          </ul>
        </div>

        <!-- Better Support -->
        <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-200/80 hover:shadow-lg hover:border-purple-200 transition-all duration-500 hover:-translate-y-1" data-reveal style="--stagger-index:1">
          <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center mb-6">
            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
          </div>
          <h3 class="text-2xl font-serif text-gray-900 mb-4">Actually Get Answers When You Need Them</h3>
          <ul class="space-y-3">
            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-gray-600">Unlimited messaging with UK-registered clinicians</span></li>
            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-gray-600">Monthly check-ins to optimise your dosage</span></li>
            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-gray-600">We aim to reply to messages within 4 hours</span></li>
            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-gray-600">Real ongoing support, not just prescription renewal</span></li>
          </ul>
        </div>

        <!-- Delivery -->
        <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-200/80 hover:shadow-lg hover:border-purple-200 transition-all duration-500 hover:-translate-y-1" data-reveal style="--stagger-index:2">
          <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center mb-6">
            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg>
          </div>
          <h3 class="text-2xl font-serif text-gray-900 mb-4">Tracked, Discreet Delivery</h3>
          <ul class="space-y-3">
            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-gray-600">Every prescription reviewed by our clinical team</span></li>
            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-gray-600">Delivered to your door</span></li>
            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-gray-600">Discreet packaging, tracked delivery</span></li>
            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-gray-600">Never run out — automatic reminders before you need to reorder</span></li>
          </ul>
        </div>

      </div>
    </div>
  </section>


  <!-- ═══════════════════════════════════════════════════ -->
  <!-- PROVIDER COMPARISON TABLE — premium redesign       -->
  <!-- ═══════════════════════════════════════════════════ -->
  <style>
    /* AT Health column — lifted dark column */
    .at-col-cell {
      background: linear-gradient(180deg, #2d1f6e 0%, #1e1650 100%);
      position: relative;
    }
    .at-col-cell::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(161,140,255,0.06) 0%, transparent 60%);
      pointer-events: none;
    }

    /* Zebra stripe for light rows */
    .cmp-row-light { background: #ffffff; }
    .cmp-row-tinted { background: #faf9ff; }

    /* Hover state */
    .cmp-row:hover .cmp-feature-cell { background: #f5f2ff; }
    .cmp-row:hover .other-cell { background: #f5f2ff; }

    /* Mobile card view */
    @media (max-width: 767px) {
      .cmp-desktop { display: none !important; }
      .cmp-mobile-cards { display: flex !important; }
    }
    @media (min-width: 768px) {
      .cmp-desktop { display: block !important; }
      .cmp-mobile-cards { display: none !important; }
    }

    /* Savings badge pulse */
    @keyframes savings-pulse {
      0%, 100% { box-shadow: 0 0 0 0 rgba(52,211,153,0.3); }
      50% { box-shadow: 0 0 0 6px rgba(52,211,153,0); }
    }
    .savings-badge { animation: savings-pulse 2.5s infinite; }
  </style>

  <section class="relative w-full py-16 md:py-28 overflow-hidden">
    <div class="absolute inset-0" style="background: #f7f4f9;"></div>
    <!-- Soft glow behind table -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[900px] h-[500px] rounded-full pointer-events-none" style="background: radial-gradient(ellipse, rgba(100,80,180,0.08) 0%, transparent 65%);"></div>

    <div class="max-w-[1000px] mx-auto px-4 sm:px-6 md:px-10 relative z-10">

      <!-- Section header -->
      <div class="text-center mb-12 md:mb-20" data-reveal>
        <div class="inline-flex items-center gap-2.5 bg-white border border-purple-200 text-purple-700 text-[11px] font-bold uppercase tracking-[0.2em] px-4 py-2 rounded-full shadow-sm mb-6">
          <span class="w-1.5 h-1.5 rounded-full bg-purple-500 inline-block"></span>
          At A Glance
        </div>
        <h2 class="text-4xl md:text-5xl lg:text-[3.5rem] font-serif text-gray-900 leading-[1.06] tracking-[-0.025em] mb-4">
          How Together Clinic <em class="not-italic" style="color:#7c6fba;">Compares</em>
        </h2>
        <p class="text-base md:text-lg text-gray-500 max-w-md mx-auto leading-relaxed">
          See how we stack up against other weight loss providers.
        </p>
      </div>

      <!-- ════════════════════════════════════════
           DESKTOP TABLE (md+)
           ════════════════════════════════════════ -->
      <div class="cmp-desktop" data-reveal>
        <!-- Outer shell — gives the AT Health column the raised/floating look -->
        <div class="relative">
          <!-- AT Health column "lift" shadow — sits behind the table -->
          <div class="absolute top-0 bottom-0 rounded-3xl shadow-2xl pointer-events-none" style="left: calc(42% + 0px); width: 29%; background: linear-gradient(180deg,#2d1f6e,#1e1650); box-shadow: 0 20px 60px rgba(45,31,110,0.45), 0 8px 20px rgba(45,31,110,0.3);"></div>

          <table class="w-full border-collapse" style="position:relative; z-index:1;">

            <!-- ─── HEADER ─── -->
            <thead>
              <tr>
                <!-- Feature column header -->
                <th class="w-[42%] text-left py-7 px-8 rounded-tl-3xl" style="background:#fff; border-bottom: 1.5px solid #ede8f6;">
                  <span class="text-[10px] font-black text-gray-300 uppercase tracking-[0.28em]">Feature</span>
                </th>

                <!-- AT Health header — dark cap -->
                <th class="w-[29%] py-0 px-0 align-bottom border-0" style="background:transparent;">
                  <div class="at-col-cell flex flex-col items-center justify-end pt-6 pb-5 px-4 rounded-t-3xl" style="background: linear-gradient(160deg, #3a2878 0%, #261a68 50%, #1b1250 100%);">
                    <!-- Glow ring -->
                    <div class="w-12 h-12 rounded-full flex items-center justify-center mb-3" style="background: rgba(255,255,255,0.08); border: 1.5px solid rgba(255,255,255,0.15); box-shadow: 0 0 20px rgba(161,140,255,0.25);">
                      <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    </div>
                    <span class="text-white text-[15px] font-bold tracking-wide block">Together Clinic</span>
                    <span class="inline-flex items-center gap-1 mt-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest" style="background: rgba(52,211,153,0.15); color: #6ee7b7; border: 1px solid rgba(52,211,153,0.2);">
                      <span class="w-1 h-1 rounded-full bg-emerald-400 inline-block"></span>
                      Recommended
                    </span>
                  </div>
                </th>

                <!-- Other Providers header -->
                <th class="w-[29%] text-center py-7 px-6 rounded-tr-3xl" style="background:#fff; border-bottom: 1.5px solid #ede8f6;">
                  <span class="text-[10px] font-black text-gray-300 uppercase tracking-[0.28em]">Other Providers</span>
                </th>
              </tr>
            </thead>

            <!-- ─── BODY ─── -->
            <tbody>

              <!-- Row 1: Mounjaro price -->
              <tr class="cmp-row group">
                <td class="cmp-feature-cell py-5 px-8 transition-colors" style="background:#fff; border-bottom: 1px solid #f0ecfb;">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg,#f3f0ff,#ebe6ff);">
                      <svg class="w-4 h-4" style="color:#7c6fba;" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>
                    </div>
                    <span class="text-gray-700 font-medium text-[15px]">Mounjaro monthly price</span>
                  </div>
                </td>
                <td class="at-col-cell py-5 px-5 text-center" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                  <div class="text-white font-bold text-[17px] leading-tight">From &pound;199</div>
                  <div class="text-[11px] mt-0.5" style="color: rgba(196,183,255,0.7);">per month</div>
                </td>
                <td class="other-cell py-5 px-6 text-center transition-colors" style="background:#fff; border-bottom: 1px solid #f0ecfb;">
                  <span class="text-gray-400 text-[15px] font-medium">From &pound;249+</span>
                </td>
              </tr>

              <!-- Row 2: Wegovy price -->
              <tr class="cmp-row group">
                <td class="cmp-feature-cell py-5 px-8 transition-colors" style="background:#faf9ff; border-bottom: 1px solid #f0ecfb;">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg,#f3f0ff,#ebe6ff);">
                      <svg class="w-4 h-4" style="color:#7c6fba;" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>
                    </div>
                    <span class="text-gray-700 font-medium text-[15px]">Wegovy monthly price</span>
                  </div>
                </td>
                <td class="at-col-cell py-5 px-5 text-center" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                  <div class="text-white font-bold text-[17px]">From &pound;179</div>
                  <div class="text-[11px] mt-0.5" style="color: rgba(196,183,255,0.7);">per month</div>
                </td>
                <td class="other-cell py-5 px-6 text-center transition-colors" style="background:#faf9ff; border-bottom: 1px solid #f0ecfb;">
                  <span class="text-gray-400 text-[15px] font-medium">From &pound;229+</span>
                </td>
              </tr>

              <!-- Row 3: Hidden fees -->
              <tr class="cmp-row group">
                <td class="cmp-feature-cell py-5 px-8 transition-colors" style="background:#fff; border-bottom: 1px solid #f0ecfb;">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg,#f3f0ff,#ebe6ff);">
                      <svg class="w-4 h-4" style="color:#7c6fba;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <span class="text-gray-700 font-medium text-[15px]">Hidden consultation fees</span>
                  </div>
                </td>
                <td class="at-col-cell py-5 px-5 text-center" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                  <div class="flex flex-col items-center gap-1.5">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center savings-badge" style="background: #34d399; box-shadow: 0 4px 12px rgba(52,211,153,0.4);">
                      <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </div>
                    <span class="text-emerald-300 text-[11px] font-bold uppercase tracking-widest">None</span>
                  </div>
                </td>
                <td class="other-cell py-5 px-6 text-center transition-colors" style="background:#fff; border-bottom: 1px solid #f0ecfb;">
                  <div class="flex flex-col items-center gap-1.5">
                    <div class="w-7 h-7 rounded-full bg-red-50 border border-red-100 flex items-center justify-center">
                      <svg class="w-3.5 h-3.5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </div>
                    <span class="text-gray-400 text-[12px] font-medium">&pound;29&ndash;&pound;49</span>
                  </div>
                </td>
              </tr>

              <!-- Row 4: Response time -->
              <tr class="cmp-row group">
                <td class="cmp-feature-cell py-5 px-8 transition-colors" style="background:#faf9ff; border-bottom: 1px solid #f0ecfb;">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg,#f3f0ff,#ebe6ff);">
                      <svg class="w-4 h-4" style="color:#7c6fba;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-gray-700 font-medium text-[15px]">Response time</span>
                  </div>
                </td>
                <td class="at-col-cell py-5 px-5 text-center" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                  <div class="text-white font-bold text-[17px]">Within 4 hours</div>
                  <div class="text-[11px] mt-0.5" style="color: rgba(196,183,255,0.7);">our aim</div>
                </td>
                <td class="other-cell py-5 px-6 text-center transition-colors" style="background:#faf9ff; border-bottom: 1px solid #f0ecfb;">
                  <span class="text-gray-400 text-[15px] font-medium">24&ndash;72 hours</span>
                </td>
              </tr>

              <!-- Row 5: Monthly check-ins -->
              <tr class="cmp-row group">
                <td class="cmp-feature-cell py-5 px-8 transition-colors" style="background:#fff; border-bottom: 1px solid #f0ecfb;">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg,#f3f0ff,#ebe6ff);">
                      <svg class="w-4 h-4" style="color:#7c6fba;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                    <span class="text-gray-700 font-medium text-[15px]">Monthly clinical check-ins</span>
                  </div>
                </td>
                <td class="at-col-cell py-5 px-5 text-center" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                  <div class="flex flex-col items-center gap-1.5">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center" style="background: #34d399; box-shadow: 0 4px 12px rgba(52,211,153,0.4);">
                      <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </div>
                    <span class="text-emerald-300 text-[11px] font-bold uppercase tracking-widest">Included</span>
                  </div>
                </td>
                <td class="other-cell py-5 px-6 text-center transition-colors" style="background:#fff; border-bottom: 1px solid #f0ecfb;">
                  <div class="flex flex-col items-center gap-1.5">
                    <div class="w-7 h-7 rounded-full bg-red-50 border border-red-100 flex items-center justify-center">
                      <svg class="w-3.5 h-3.5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </div>
                    <span class="text-gray-400 text-[12px] font-medium">Extra cost</span>
                  </div>
                </td>
              </tr>

              <!-- Row 6: Delivery speed -->
              <tr class="cmp-row group">
                <td class="cmp-feature-cell py-5 px-8 transition-colors" style="background:#faf9ff; border-bottom: 1px solid #f0ecfb;">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg,#f3f0ff,#ebe6ff);">
                      <svg class="w-4 h-4" style="color:#7c6fba;" fill="currentColor" viewBox="0 0 20 20"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg>
                    </div>
                    <span class="text-gray-700 font-medium text-[15px]">Delivery</span>
                  </div>
                </td>
                <td class="at-col-cell py-5 px-5 text-center" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                  <div class="text-white font-bold text-[17px]">Tracked &amp; discreet</div>
                </td>
                <td class="other-cell py-5 px-6 text-center transition-colors" style="background:#faf9ff; border-bottom: 1px solid #f0ecfb;">
                  <span class="text-gray-400 text-[15px] font-medium">Varies</span>
                </td>
              </tr>

              <!-- Row 7: Cancel anytime -->
              <tr class="cmp-row group">
                <td class="cmp-feature-cell py-5 px-8 transition-colors" style="background:#fff;">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg,#f3f0ff,#ebe6ff);">
                      <svg class="w-4 h-4" style="color:#7c6fba;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-gray-700 font-medium text-[15px]">Cancel anytime</span>
                  </div>
                </td>
                <td class="at-col-cell py-5 px-5 text-center">
                  <div class="flex flex-col items-center gap-1.5">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center" style="background: #34d399; box-shadow: 0 4px 12px rgba(52,211,153,0.4);">
                      <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </div>
                    <span class="text-emerald-300 text-[11px] font-bold uppercase tracking-widest">Always</span>
                  </div>
                </td>
                <td class="other-cell py-5 px-6 text-center transition-colors" style="background:#fff;">
                  <span class="text-gray-400 text-[15px] font-medium">Varies</span>
                </td>
              </tr>

            </tbody>

            <!-- ─── FOOTER CTA ROW ─── -->
            <tfoot>
              <tr>
                <td class="px-8 py-6 rounded-bl-3xl" style="background: #fdf8f3; border-top: 1.5px solid #ede8f6;">
                  <p class="text-[11px] text-gray-400 italic">Prices correct as of 2026. Subject to clinical eligibility.</p>
                </td>
                <td class="at-col-cell px-5 py-6 text-center rounded-b-3xl" style="border-top: 1.5px solid rgba(255,255,255,0.08);">
                  <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="inline-flex items-center gap-2 bg-white text-[#2d1f6e] text-[13px] font-extrabold px-6 py-3 rounded-xl transition-all hover:bg-purple-50 hover:-translate-y-0.5" style="box-shadow: 0 4px 20px rgba(0,0,0,0.25);">
                    Start Switch
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                  </a>
                </td>
                <td class="px-6 py-6 text-center rounded-br-3xl" style="background: #fdf8f3; border-top: 1.5px solid #ede8f6;">
                  <span class="text-gray-300 text-sm">—</span>
                </td>
              </tr>
            </tfoot>

          </table>
        </div>
      </div>
      <!-- end desktop table -->


      <!-- ════════════════════════════════════════
           MOBILE CARDS (< md)
           ════════════════════════════════════════ -->
      <div class="cmp-mobile-cards flex-col gap-5" style="display:none;" data-reveal>

        <!-- AT Health dark card -->
        <div class="rounded-3xl overflow-hidden" style="background: linear-gradient(160deg, #3a2878 0%, #261a68 50%, #1b1250 100%); box-shadow: 0 20px 60px rgba(45,31,110,0.5), 0 8px 24px rgba(45,31,110,0.3);">
          <!-- Card header -->
          <div class="flex items-center justify-between px-6 pt-7 pb-5 border-b" style="border-color: rgba(255,255,255,0.08);">
            <div>
              <div class="flex items-center gap-2.5 mb-1">
                <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15);">
                  <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                </div>
                <span class="text-white text-lg font-bold">Together Clinic</span>
              </div>
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest" style="background: rgba(52,211,153,0.15); color: #6ee7b7; border: 1px solid rgba(52,211,153,0.2);">
                <span class="w-1 h-1 rounded-full bg-emerald-400 inline-block"></span>
                Recommended
              </span>
            </div>
          </div>
          <!-- Card rows -->
          <div>
            <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
              <span class="text-[13px]" style="color: rgba(196,183,255,0.8);">Mounjaro monthly</span>
              <span class="text-white font-bold text-[15px]">From &pound;199</span>
            </div>
            <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(255,255,255,0.02);">
              <span class="text-[13px]" style="color: rgba(196,183,255,0.8);">Wegovy monthly</span>
              <span class="text-white font-bold text-[15px]">From &pound;179</span>
            </div>
            <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
              <span class="text-[13px]" style="color: rgba(196,183,255,0.8);">Hidden fees</span>
              <span class="font-bold text-[14px] flex items-center gap-1.5" style="color:#6ee7b7;">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                None
              </span>
            </div>
            <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(255,255,255,0.02);">
              <span class="text-[13px]" style="color: rgba(196,183,255,0.8);">Response time</span>
              <span class="text-white font-bold text-[14px]">Within 4 hours</span>
            </div>
            <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
              <span class="text-[13px]" style="color: rgba(196,183,255,0.8);">Clinical check-ins</span>
              <span class="font-bold text-[14px] flex items-center gap-1.5" style="color:#6ee7b7;">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                Included
              </span>
            </div>
            <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(255,255,255,0.02);">
              <span class="text-[13px]" style="color: rgba(196,183,255,0.8);">Delivery</span>
              <span class="text-white font-bold text-[14px]">Tracked &amp; discreet</span>
            </div>
            <div class="flex items-center justify-between px-6 py-4">
              <span class="text-[13px]" style="color: rgba(196,183,255,0.8);">Cancel anytime</span>
              <span class="font-bold text-[14px] flex items-center gap-1.5" style="color:#6ee7b7;">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                Always
              </span>
            </div>
          </div>
          <!-- CTA -->
          <div class="px-6 py-5" style="border-top: 1px solid rgba(255,255,255,0.08);">
            <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="w-full flex items-center justify-center gap-2.5 bg-white text-[#2d1f6e] text-[14px] font-extrabold py-4 rounded-2xl transition-all hover:bg-purple-50" style="box-shadow: 0 6px 24px rgba(0,0,0,0.3);">
              Start My Switch
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
          </div>
        </div>

        <!-- Other Providers light card -->
        <div class="rounded-3xl overflow-hidden bg-white border border-gray-200 shadow-md">
          <div class="px-6 pt-5 pb-4" style="border-bottom: 1px solid #f0ecfb;">
            <span class="text-[10px] font-black text-gray-300 uppercase tracking-[0.28em]">Other Providers</span>
          </div>
          <div>
            <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid #f7f5ff;">
              <span class="text-gray-500 text-[13px]">Mounjaro monthly</span>
              <span class="text-gray-400 font-semibold">From &pound;249+</span>
            </div>
            <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid #f7f5ff; background:#faf9ff;">
              <span class="text-gray-500 text-[13px]">Wegovy monthly</span>
              <span class="text-gray-400 font-semibold">From &pound;229+</span>
            </div>
            <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid #f7f5ff;">
              <span class="text-gray-500 text-[13px]">Hidden fees</span>
              <span class="text-red-400 font-semibold text-[13px]">&pound;29&ndash;&pound;49</span>
            </div>
            <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid #f7f5ff; background:#faf9ff;">
              <span class="text-gray-500 text-[13px]">Response time</span>
              <span class="text-gray-400 font-semibold text-[13px]">24&ndash;72 hours</span>
            </div>
            <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid #f7f5ff;">
              <span class="text-gray-500 text-[13px]">Clinical check-ins</span>
              <span class="text-red-400 font-semibold text-[13px]">Extra cost</span>
            </div>
            <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid #f7f5ff; background:#faf9ff;">
              <span class="text-gray-500 text-[13px]">Delivery</span>
              <span class="text-gray-400 font-semibold text-[13px]">Varies</span>
            </div>
            <div class="flex items-center justify-between px-6 py-4">
              <span class="text-gray-500 text-[13px]">Cancel anytime</span>
              <span class="text-gray-400 font-semibold text-[13px]">Varies</span>
            </div>
          </div>
        </div>
        <!-- disclaimer -->
        <p class="text-center text-[11px] text-gray-400 italic px-4">Prices correct as of 2026. Subject to clinical eligibility.</p>
      </div>
      <!-- end mobile cards -->

    </div>
  </section>


  <!-- ═══════════════════════════════════════════════════ -->
  <!-- FAQ — Switching-specific questions                  -->
  <!-- ═══════════════════════════════════════════════════ -->
  <section class="relative py-14 md:py-16 overflow-hidden">
    <div class="absolute inset-0" style="background: #fdf8f3;"></div>
    <div class="max-w-[900px] mx-auto px-6 md:px-[60px] relative z-10">
      <div class="text-center mb-10 md:mb-12" data-reveal>
        <div class="flex items-center justify-center gap-3 mb-4">
          <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
          <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider">Switching FAQs</p>
        </div>
        <h2 class="text-3xl md:text-4xl lg:text-5xl text-gray-800 font-serif leading-[1.1] mb-4">
          Got Questions? We&#39;ve Got <span class="text-purple-600">Answers</span>
        </h2>
      </div>

      <div class="space-y-3" data-stagger id="faqAccordion">
        <!-- FAQ 1 -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-300 hover:border-purple-200" data-reveal style="--stagger-index:0">
          <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-6 md:px-8 py-5 text-left group">
            <span class="text-base md:text-lg font-semibold text-gray-900 pr-4 group-hover:text-purple-600 transition-colors">Do I need to transfer my prescription?</span>
            <div class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center flex-shrink-0 group-hover:bg-purple-100 transition-colors">
              <svg class="w-4 h-4 text-purple-600 transition-transform duration-300 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
          </button>
          <div class="faq-body max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
            <div class="px-6 md:px-8 pb-6 text-gray-600 leading-relaxed text-[15px]">No. You don&#39;t need to transfer anything from your current provider. Our clinical team will issue you a brand new prescription based on your assessment. Simply tell us what you&#39;re currently taking and your dosage — we handle the rest.</div>
          </div>
        </div>

        <!-- FAQ 2 -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-300 hover:border-purple-200" data-reveal style="--stagger-index:1">
          <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-6 md:px-8 py-5 text-left group">
            <span class="text-base md:text-lg font-semibold text-gray-900 pr-4 group-hover:text-purple-600 transition-colors">Will there be any gap in my treatment?</span>
            <div class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center flex-shrink-0 group-hover:bg-purple-100 transition-colors">
              <svg class="w-4 h-4 text-purple-600 transition-transform duration-300 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
          </button>
          <div class="faq-body max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
            <div class="px-6 md:px-8 pb-6 text-gray-600 leading-relaxed text-[15px]">We recommend starting your switch well before your current supply runs out. Your prescriber reviews your assessment and current dose, and once your treatment is approved we dispatch it by tracked delivery, so you can plan around the supply you have left.</div>
          </div>
        </div>

        <!-- FAQ 3 -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-300 hover:border-purple-200" data-reveal style="--stagger-index:2">
          <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-6 md:px-8 py-5 text-left group">
            <span class="text-base md:text-lg font-semibold text-gray-900 pr-4 group-hover:text-purple-600 transition-colors">What&#39;s included in the price?</span>
            <div class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center flex-shrink-0 group-hover:bg-purple-100 transition-colors">
              <svg class="w-4 h-4 text-purple-600 transition-transform duration-300 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
          </button>
          <div class="faq-body max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
            <div class="px-6 md:px-8 pb-6 text-gray-600 leading-relaxed text-[15px]">Our prices are shown on each treatment page and include everything — medication, clinical consultations, and delivery. No hidden fees, no surprise charges.</div>
          </div>
        </div>

        <!-- FAQ 4 -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-300 hover:border-purple-200" data-reveal style="--stagger-index:3">
          <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-6 md:px-8 py-5 text-left group">
            <span class="text-base md:text-lg font-semibold text-gray-900 pr-4 group-hover:text-purple-600 transition-colors">Can I switch if I&#39;m on a different dose?</span>
            <div class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center flex-shrink-0 group-hover:bg-purple-100 transition-colors">
              <svg class="w-4 h-4 text-purple-600 transition-transform duration-300 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
          </button>
          <div class="faq-body max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
            <div class="px-6 md:px-8 pb-6 text-gray-600 leading-relaxed text-[15px]">Yes, we support all available doses of Mounjaro and Wegovy. Just tell us your current dosage in the assessment and our prescribers will continue your treatment at the same level — or recommend adjustments if clinically appropriate.</div>
          </div>
        </div>

        <!-- FAQ 5 -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-300 hover:border-purple-200" data-reveal style="--stagger-index:4">
          <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-6 md:px-8 py-5 text-left group">
            <span class="text-base md:text-lg font-semibold text-gray-900 pr-4 group-hover:text-purple-600 transition-colors">Do I need to cancel with my old provider first?</span>
            <div class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center flex-shrink-0 group-hover:bg-purple-100 transition-colors">
              <svg class="w-4 h-4 text-purple-600 transition-transform duration-300 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
          </button>
          <div class="faq-body max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
            <div class="px-6 md:px-8 pb-6 text-gray-600 leading-relaxed text-[15px]">We recommend cancelling your old subscription to avoid being charged by two providers. However, you can start with Together Clinic immediately — there&#39;s no dependency on your old provider. Cancel with them in your own time once you&#39;ve received your first Together Clinic delivery.</div>
          </div>
        </div>
      </div>

      <div class="text-center mt-10" data-reveal>
        <p class="text-gray-500 text-sm mb-4">Still have questions?</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
          <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'contact-us' ) ?: get_page_by_path( 'contact' ) ) ); ?>" class="inline-flex items-center gap-2 text-purple-600 font-semibold text-base hover:text-purple-700 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            Chat with our team
          </a>
          <span class="text-gray-300 hidden sm:inline">|</span>
          <a href="mailto:<?php echo esc_attr( ah_email() ); ?>" class="inline-flex items-center gap-2 text-purple-600 font-semibold text-base hover:text-purple-700 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Email <?php echo esc_html( ah_email() ); ?>
          </a>
        </div>
        <p class="text-gray-400 text-xs mt-4 max-w-md mx-auto"><?php echo esc_html( ah_no_phone_notice() ); ?></p>
      </div>
    </div>
  </section>

  <script>
    function toggleFaq(btn) {
      const item = btn.closest('div');
      const body = item.querySelector('.faq-body');
      const icon = item.querySelector('.faq-icon');
      const isOpen = body.style.maxHeight && body.style.maxHeight !== '0px';
      document.querySelectorAll('#faqAccordion .faq-body').forEach(b => { b.style.maxHeight = '0px'; });
      document.querySelectorAll('#faqAccordion .faq-icon').forEach(i => { i.style.transform = 'rotate(0deg)'; });
      document.querySelectorAll('#faqAccordion > div').forEach(d => { d.classList.remove('border-purple-300', 'shadow-md'); });
      if (!isOpen) {
        body.style.maxHeight = body.scrollHeight + 'px';
        icon.style.transform = 'rotate(180deg)';
        item.classList.add('border-purple-300', 'shadow-md');
      }
    }
  </script>


  <!-- ═══════════════════════════════════════════════════ -->
  <!-- DARK EDITORIAL CTA                                 -->
  <!-- ═══════════════════════════════════════════════════ -->
  <section class="relative py-20 md:py-28 overflow-hidden" style="background: #0f1117;">
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
      <div class="w-[600px] h-[600px] rounded-full opacity-[0.07]" style="background: radial-gradient(circle, #9b8fce 0%, transparent 70%);"></div>
    </div>
    <div class="max-w-4xl mx-auto px-6 text-center relative z-10" data-reveal>
      <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-gray-500 mb-6">Ready to Switch?</p>
      <h2 class="text-4xl md:text-5xl lg:text-6xl font-serif text-white leading-[1.05] mb-5 tracking-[-0.02em]">
        Make the switch<br/><em class="not-italic" style="color: #a89dd6;">with confidence</em>
      </h2>
      <p class="text-base md:text-lg text-gray-400 leading-relaxed mb-10 max-w-xl mx-auto">
        Transparent pricing, pharmacist prescriber support, and your current dose reviewed.
      </p>
      <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="inline-flex items-center gap-3 bg-white hover:bg-gray-100 text-gray-900 text-[15px] font-semibold px-10 py-4 rounded-xl transition-all hover-lift shadow-xl mb-10">
        Start Your Switch
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
      </a>
      <div class="flex flex-wrap items-center justify-center gap-x-8 gap-y-3 border-t border-white/[0.07] pt-8">
        <div class="flex items-center gap-2 text-gray-400 text-sm">
          <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
          GPhC Regulated
        </div>
        <div class="flex items-center gap-2 text-gray-400 text-sm">
          <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
          Cancel Anytime
        </div>
        <div class="flex items-center gap-2 text-gray-400 text-sm">
          <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
          Discreet Delivery
        </div>
        <div class="flex items-center gap-2 text-gray-400 text-sm">
          <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
          Prescriber-Reviewed
        </div>
      </div>
    </div>
  </section>



<?php get_footer(); ?>

<?php
/**
 * Template Name: Contact
 * Description: Contact page with multiple contact methods.
 */
get_header();
?>

<!-- Hero -->
<section class="py-16 md:py-20" style="background:#fdf8f3;">
  <div class="ah-container text-center" data-reveal>
    <div class="inline-flex items-center gap-2 bg-white border border-purple-200 rounded-full px-5 py-2 shadow-sm mb-6">
      <span class="text-purple-600 text-sm font-semibold">Get In Touch</span>
    </div>
    <h1 class="text-4xl md:text-5xl lg:text-6xl font-serif text-gray-900 leading-tight mb-6">
      <?php echo wp_kses_post( ah_field( 'ct_title', 'We\'re Here to Help<br>You <span style="color:#8e88d0;">Succeed</span>' ) ); ?>
    </h1>
    <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-8">
      <?php echo esc_html( ah_field( 'ct_subtitle', 'Have questions about our treatments? Ready to start your weight loss journey? Our UK-based team is here to help.' ) ); ?>
    </p>
    <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="ah-btn-purple">Start Your Journey <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></a>
    <div class="flex flex-wrap items-center justify-center gap-6 mt-6 text-sm text-gray-500">
      <span>Response within 4 hours</span><span>·</span><span>100% Confidential</span><span>·</span><span>UK-Registered Prescribers</span>
    </div>
  </div>
</section>

<!-- Contact Methods -->
<section class="py-14 md:py-16" style="background: #f7f4f9;">
  <div class="ah-container-wide">
    <div class="text-center mb-12 section-header">
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
        <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider">Multiple Ways to Connect</p>
      </div>
      <h2 class="text-3xl md:text-4xl font-serif text-gray-900 mb-4">Choose How You'd Like to Get Started</h2>
    </div>
    <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto" data-stagger>
      <!-- Primary: Online Assessment -->
      <div class="rounded-2xl p-8 text-white" data-reveal style="--stagger-index:0; background: linear-gradient(135deg, var(--ah-purple-600), var(--ah-purple-700));">
        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mb-5">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <h3 class="text-xl font-serif mb-3">Start Online Assessment</h3>
        <p class="text-white/80 text-sm leading-relaxed mb-6">Complete our secure 5-minute assessment and get same-day approval from a UK-registered prescriber.</p>
        <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="inline-flex items-center gap-2 bg-white text-purple-700 font-semibold px-6 py-3 rounded-xl text-sm hover:bg-gray-100 transition-all">Begin Assessment</a>
        <p class="text-white/60 text-xs mt-3">Takes 5 minutes</p>
      </div>
      <!-- Hours -->
      <div class="ct-info-card" data-reveal style="--stagger-index:1">
        <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center mb-5">
          <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h3 class="text-xl font-serif text-gray-900 mb-3">Hours</h3>
        <p class="text-lg text-purple-700 font-semibold"><?php echo esc_html( ah_business_hours() ); ?></p>
        <p class="text-sm text-gray-500 mt-2"><?php echo esc_html( ah_no_phone_notice() ); ?></p>
      </div>
      <!-- Email -->
      <div class="ct-info-card" data-reveal style="--stagger-index:2">
        <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center mb-5">
          <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </div>
        <h3 class="text-xl font-serif text-gray-900 mb-3">Email Us</h3>
        <a href="mailto:<?php echo esc_attr( ah_email() ); ?>" class="text-lg text-purple-700 font-semibold hover:text-purple-600 transition-colors"><?php echo esc_html( ah_email() ); ?></a>
        <p class="text-sm text-gray-500 mt-2"><?php echo esc_html( ah_option( 'email_response_time', 'Response within 4 hours' ) ); ?></p>
      </div>
    </div>
  </div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>
<?php get_footer(); ?>

<?php
/**
 * Template Name: Customer Care
 * Description: Customer care and support page.
 */
get_header();
?>

<!-- Hero -->
<section class="py-16 md:py-20" style="background:#fdf8f3;">
  <div class="ah-container text-center" data-reveal>
    <p class="text-purple-600 text-xs font-bold uppercase tracking-wider mb-4"><?php echo esc_html( ah_field( 'cc_eyebrow', 'Customer Care' ) ); ?></p>
    <h1 class="text-4xl md:text-5xl lg:text-6xl font-serif text-gray-900 leading-tight mb-6">
      <?php echo wp_kses_post( ah_field( 'cc_title', 'We\'re Here to <span style="color:#6366f1;">Help</span>' ) ); ?>
    </h1>
    <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-8">
      <?php echo esc_html( ah_field( 'cc_subtitle', 'Your health journey is our priority. Find answers, review our policies, or get in touch with our UK-based clinical support team.' ) ); ?>
    </p>
    <div class="flex flex-wrap items-center justify-center gap-6 text-sm text-gray-600">
      <?php foreach ( array( 'UK-Based Support Team', 'Reply Within 4 Hours', 'GPhC Regulated' ) as $t ) : ?>
      <div class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="font-medium"><?php echo esc_html( $t ); ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Quick Support -->
<section class="py-14 md:py-16" style="background: #f7f4f9;">
  <div class="ah-container-wide">
    <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto" data-stagger>
      <div class="cc-channel-card" data-reveal style="--stagger-index:0">
        <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center mb-5">
          <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <h3 class="text-xl font-serif text-gray-900 mb-3"><?php echo esc_html( ah_field( 'cc_card1_title', 'Discreet Delivery' ) ); ?></h3>
        <p class="text-gray-600 text-[15px] leading-relaxed"><?php echo esc_html( ah_field( 'cc_card1_desc', 'All orders arrive in plain, unmarked packaging. Your privacy is always protected.' ) ); ?></p>
      </div>
      <div class="cc-channel-card" data-reveal style="--stagger-index:1">
        <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center mb-5">
          <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <h3 class="text-xl font-serif text-gray-900 mb-3"><?php echo esc_html( ah_field( 'cc_card2_title', 'Fast & Secure' ) ); ?></h3>
        <p class="text-gray-600 text-[15px] leading-relaxed"><?php echo esc_html( ah_field( 'cc_card2_desc', 'Tracked next-day delivery with temperature-controlled packaging to ensure medication quality.' ) ); ?></p>
      </div>
      <div class="cc-channel-card" data-reveal style="--stagger-index:2">
        <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center mb-5">
          <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <h3 class="text-xl font-serif text-gray-900 mb-3"><?php echo esc_html( ah_field( 'cc_card3_title', 'Easy Management' ) ); ?></h3>
        <p class="text-gray-600 text-[15px] leading-relaxed"><?php echo esc_html( ah_field( 'cc_card3_desc', 'Pause, change dose, or cancel anytime. No lock-in contracts or hidden fees.' ) ); ?></p>
      </div>
    </div>
  </div>
</section>

<!-- Contact CTA -->
<section class="py-14 md:py-16" style="background: #fdf8f3;">
  <div class="ah-container text-center" data-reveal>
    <h2 class="text-3xl md:text-4xl font-serif text-gray-900 mb-4">Need to get in touch?</h2>
    <p class="text-gray-600 mb-8">Our UK-based team is available <?php echo esc_html( ah_business_hours() ); ?>.</p>
    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
      <a href="mailto:<?php echo esc_attr( ah_email() ); ?>" class="ah-btn-purple">Email <?php echo esc_html( ah_email() ); ?></a>
    </div>
    <p class="text-sm text-gray-500 mt-6 max-w-xl mx-auto"><?php echo esc_html( ah_no_phone_notice() ); ?></p>
  </div>
</section>

<?php get_footer(); ?>

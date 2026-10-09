<?php
/**
 * Template Name: Eligibility
 * Description: Eligibility checker page with criteria and results.
 */
get_header();
?>

<!-- Hero -->
<section class="py-16 md:py-20" style="background:#fdf8f3;">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid lg:grid-cols-2 gap-12 items-center">
      <div data-reveal>
        <p class="text-purple-600 text-xs font-bold uppercase tracking-wider mb-4"><?php echo esc_html( ah_field( 'el_eyebrow', 'Free First Check' ) ); ?></p>
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-serif text-gray-900 leading-tight mb-6">
          <?php echo wp_kses_post( ah_field( 'el_title', 'Weight management with a <span style="color:#6366f1;">UK prescriber</span>' ) ); ?>
        </h1>
        <p class="text-lg text-gray-600 leading-relaxed mb-8 max-w-lg">
          <?php echo esc_html( ah_field( 'el_subtitle', 'Answer a few questions to find out whether a prescription weight management treatment could be suitable for you. Every assessment is reviewed by a UK-registered prescriber.' ) ); ?>
        </p>
        <a href="#eligibility-form" class="ah-btn-purple mb-6">Start Your First Check <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></a>
        <p class="text-sm text-gray-500">Takes 2 min · Free · No obligation</p>
      </div>
      <div data-reveal="right" class="relative">
        <?php $el_image = ah_field( 'el_hero_image', '' ); ?>
        <?php if ( $el_image ) : echo wp_get_attachment_image( $el_image, 'hero-image', false, array( 'class' => 'w-full rounded-3xl shadow-2xl' ) ); else : ?>
        <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=1200&h=1000&fit=crop" alt="Woman feeling confident" class="w-full rounded-3xl shadow-2xl" />
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- Eligibility Criteria -->
<section class="py-14 md:py-16" style="background: #f7f4f9;" id="eligibility-form">
  <div class="ah-container-wide">
    <div class="text-center mb-12 section-header">
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
        <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider">Licence and Service Conditions</p>
      </div>
      <h2 class="text-3xl md:text-4xl font-serif text-gray-900 mb-4">Who We Can Consider for Treatment</h2>
      <p class="text-gray-600 max-w-2xl mx-auto">The questionnaire is a first check only. A prescriber decides at your video consultation whether any treatment is suitable, and a treatment you prefer is not prescribed if it is not suitable for you.</p>
    </div>
    <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto" data-stagger>
      <div class="bg-white rounded-2xl border border-gray-200 p-8 shadow-sm" data-reveal style="--stagger-index:0">
        <h3 class="text-xl font-serif text-gray-900 mb-4">Licence Conditions</h3>
        <ul class="space-y-3">
          <?php foreach ( array( 'Wegovy (injection or tablets), Mounjaro and Foundayo: Body Mass Index (BMI) 30 or above, or 27 to 29.9 with a weight-related condition', 'Orlistat: BMI 30 or above, or 28 to 29.9 with a weight-related condition', 'Alongside a reduced-calorie diet and more physical activity' ) as $item ) : ?>
          <li class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-gray-700 text-[15px]"><?php echo esc_html( $item ); ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="bg-white rounded-2xl border border-gray-200 p-8 shadow-sm" data-reveal style="--stagger-index:1">
        <h3 class="text-xl font-serif text-gray-900 mb-4">Our Service Rules</h3>
        <ul class="space-y-3">
          <?php foreach ( array( 'Aged 18 to 85 (our service policy)', 'Not breastfeeding, and not pregnant or planning a pregnancy (our service policy for pregnancy)', 'Agree to a photo ID check, a video consultation where your weight and height are checked, and a check of your NHS Summary Care Record' ) as $item ) : ?>
          <li class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-gray-700 text-[15px]"><?php echo esc_html( $item ); ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="bg-white rounded-2xl border border-gray-200 p-8 shadow-sm" data-reveal style="--stagger-index:2">
        <h3 class="text-xl font-serif text-gray-900 mb-4">Weight-Related Conditions (BMI 27 to 29.9 for GLP-1 medicines, 28 to 29.9 for Orlistat)</h3>
        <ul class="space-y-3">
          <?php foreach ( array( 'Type 2 diabetes or pre-diabetes', 'High blood pressure', 'High cholesterol', 'Sleep apnoea', 'Heart or circulation problems' ) as $item ) : ?>
          <li class="flex items-start gap-3"><svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg><span class="text-gray-700 text-[15px]"><?php echo esc_html( $item ); ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="text-center mt-10" data-reveal>
      <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="ah-btn-purple">Start Your Assessment <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></a>
    </div>
  </div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>
<?php get_footer(); ?>

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
        <p class="text-purple-600 text-xs font-bold uppercase tracking-wider mb-4"><?php echo esc_html( ah_field( 'el_eyebrow', 'Free Eligibility Check' ) ); ?></p>
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-serif text-gray-900 leading-tight mb-6">
          <?php echo wp_kses_post( ah_field( 'el_title', 'Feel Confident in<br>Your <span style="color:#6366f1;">Body Again</span>' ) ); ?>
        </h1>
        <p class="text-lg text-gray-600 leading-relaxed mb-8 max-w-lg">
          <?php echo esc_html( ah_field( 'el_subtitle', 'Join thousands who\'ve lost 2-4 stone, dropped clothing sizes, and regained their energy. Find out if you qualify for clinically-proven treatment in under 2 minutes.' ) ); ?>
        </p>
        <a href="#eligibility-form" class="ah-btn-purple mb-6">Check Your Eligibility <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></a>
        <p class="text-sm text-gray-500">Takes 2 min · Free · No obligation</p>
      </div>
      <div data-reveal="right" class="relative">
        <?php $el_image = ah_field( 'el_hero_image', '' ); ?>
        <?php if ( $el_image ) : echo wp_get_attachment_image( $el_image, 'hero-image', false, array( 'class' => 'w-full rounded-3xl shadow-2xl' ) ); else : ?>
        <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=1200&h=1000&fit=crop" alt="Woman feeling confident" class="w-full rounded-3xl shadow-2xl" />
        <?php endif; ?>
        <div class="absolute -bottom-4 -left-4 bg-white rounded-2xl shadow-xl p-4 border border-gray-100">
          <p class="text-2xl font-serif text-purple-700 font-bold">84%</p>
          <p class="text-xs text-gray-600">Lose weight in month 1</p>
        </div>
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
        <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider">Eligibility Criteria</p>
      </div>
      <h2 class="text-3xl md:text-4xl font-serif text-gray-900 mb-4">You May Qualify If...</h2>
    </div>
    <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto" data-stagger>
      <div class="bg-white rounded-2xl border border-gray-200 p-8 shadow-sm" data-reveal style="--stagger-index:0">
        <h3 class="text-xl font-serif text-gray-900 mb-4">You're Likely Eligible</h3>
        <ul class="space-y-3">
          <?php foreach ( array( 'Body Mass Index (BMI) 30+ (or 27+ with a qualifying condition)', 'Aged 18 or over', 'Tried diet and exercise without lasting results', 'Committed to lifestyle changes', 'No contraindications to Glucagon-Like Peptide-1 (GLP-1) medications' ) as $item ) : ?>
          <li class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-gray-700 text-[15px]"><?php echo esc_html( $item ); ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="bg-white rounded-2xl border border-gray-200 p-8 shadow-sm" data-reveal style="--stagger-index:1">
        <h3 class="text-xl font-serif text-gray-900 mb-4">Qualifying Conditions (BMI 27+)</h3>
        <ul class="space-y-3">
          <?php foreach ( array( 'Type 2 diabetes or pre-diabetes', 'High blood pressure', 'High cholesterol', 'Sleep apnoea', 'Polycystic Ovary Syndrome (PCOS)', 'Joint problems or osteoarthritis' ) as $item ) : ?>
          <li class="flex items-start gap-3"><svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg><span class="text-gray-700 text-[15px]"><?php echo esc_html( $item ); ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="text-center mt-10" data-reveal>
      <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="ah-btn-purple">Check If You Qualify <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></a>
    </div>
  </div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>
<?php get_footer(); ?>

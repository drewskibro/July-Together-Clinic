<?php
/**
 * Template Name: Reorder
 * Description: Medication reorder page for existing patients.
 * NOTE: Original HTML used CQ Doctor branding — updated to AT Health.
 */
get_header();
?>

<!-- Hero -->
<section class="py-16 md:py-20" style="background:#fdf8f3;">
  <div class="ah-container text-center" data-reveal>
    <div class="inline-flex items-center gap-2 bg-purple-50 border border-purple-200 rounded-full px-5 py-2 shadow-sm mb-6">
      <span class="text-purple-600 text-sm font-semibold">Existing Patient</span>
    </div>
    <h1 class="text-4xl md:text-5xl lg:text-6xl font-serif text-gray-900 leading-tight mb-6">
      <?php echo wp_kses_post( ah_field( 'ro_title', 'Welcome Back<br>Reorder in <span style="color:#8e88d0;">Minutes</span>' ) ); ?>
    </h1>
    <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-8">
      <?php echo esc_html( ah_field( 'ro_subtitle', 'Continue your treatment with ease. A simple reorder process, tracked delivery, and ongoing prescriber support.' ) ); ?>
    </p>
    <div class="flex flex-wrap items-center justify-center gap-6 text-sm text-gray-600">
      <?php foreach ( array( 'Quick reorder process', 'Tracked delivery', 'Expert prescriber support' ) as $t ) : ?>
      <div class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="font-medium"><?php echo esc_html( $t ); ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Medication Selection -->
<section class="py-14 md:py-16" style="background: #f7f4f9;">
  <div class="ah-container-wide">
    <div class="text-center mb-12">
      <h2 class="text-3xl md:text-4xl font-serif text-gray-900 mb-4"><?php echo esc_html( ah_field( 'ro_selection_title', 'Which medication are you reordering?' ) ); ?></h2>
    </div>
    <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto" data-stagger>
      <!-- Wegovy -->
      <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300" data-reveal style="--stagger-index:0">
        <div class="p-8">
          <span class="inline-block bg-purple-50 text-purple-600 text-xs font-bold px-3 py-1 rounded-full mb-4">Returning Patient</span>
          <h3 class="text-2xl font-serif text-gray-900 mb-2">Wegovy</h3>
          <div class="flex items-baseline gap-1 mb-3">
            <span class="text-3xl font-serif text-gray-900">&pound;<?php echo esc_html( ah_field( 'ro_wegovy_price', '125' ) ); ?></span>
            <span class="text-gray-500 text-sm">/month</span>
          </div>
          <p class="text-sm text-gray-600 mb-6"><?php echo esc_html( ah_field( 'ro_wegovy_stat', '15% average weight loss within 68 weeks' ) ); ?></p>
          <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="w-full flex items-center justify-center bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 rounded-xl transition-all">Reorder Wegovy</a>
        </div>
      </div>
      <!-- Mounjaro -->
      <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300" data-reveal style="--stagger-index:1">
        <div class="p-8">
          <span class="inline-block bg-purple-50 text-purple-600 text-xs font-bold px-3 py-1 rounded-full mb-4">Returning Patient</span>
          <h3 class="text-2xl font-serif text-gray-900 mb-2">Mounjaro</h3>
          <div class="flex items-baseline gap-1 mb-3">
            <span class="text-3xl font-serif text-gray-900">&pound;<?php echo esc_html( ah_field( 'ro_mounjaro_price', '145' ) ); ?></span>
            <span class="text-gray-500 text-sm">/month</span>
          </div>
          <p class="text-sm text-gray-600 mb-6"><?php echo esc_html( ah_field( 'ro_mounjaro_stat', '20% average weight loss within 72 weeks' ) ); ?></p>
          <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="w-full flex items-center justify-center bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 rounded-xl transition-all">Reorder Mounjaro</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>
<?php get_footer(); ?>

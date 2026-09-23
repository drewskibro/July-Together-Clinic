<?php
/**
 * Template Name: About
 * Description: About AT Health page.
 */
get_header();
?>

<!-- Dark Hero -->
<section class="py-20 md:py-28" style="background: #0f1117;">
  <div class="ah-container text-center" data-reveal>
    <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-gray-500 mb-6"><?php echo esc_html( ah_field( 'ab_eyebrow', 'Our Story' ) ); ?></p>
    <h1 class="text-4xl md:text-5xl lg:text-6xl font-serif text-white leading-tight mb-6">
      <?php echo wp_kses_post( ah_field( 'ab_title', 'The People Behind<br>Your <em class="not-italic" style="color: #a89dd6;">Transformation</em>' ) ); ?>
    </h1>
    <p class="text-lg text-gray-400 max-w-2xl mx-auto">
      <?php echo esc_html( ah_field( 'ab_subtitle', 'Together Clinic is a pharmacist-led weight management service. Our pharmacist prescribers have supported over 1,000 patients across the AT Health group.' ) ); ?>
    </p>
  </div>
</section>

<!-- Our Story -->
<section class="py-16 md:py-20" style="background: #f7f4f9;">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid lg:grid-cols-2 gap-12 items-center">
      <div class="relative" data-reveal>
        <?php $ab_image = ah_field( 'ab_story_image', '' ); ?>
        <?php if ( $ab_image ) : echo wp_get_attachment_image( $ab_image, 'hero-image', false, array( 'class' => 'w-full rounded-3xl shadow-xl' ) ); else : ?>
        <img src="https://images.unsplash.com/photo-1594824476967-48c8b964273f?w=1200&h=1600&fit=crop" alt="Together Clinic medical professional" class="w-full rounded-3xl shadow-xl" />
        <?php endif; ?>
        <div class="absolute -bottom-4 -right-4 bg-purple-600 text-white rounded-2xl shadow-xl px-6 py-4">
          <p class="text-sm font-bold">Patient-First Approach</p>
        </div>
      </div>
      <div data-reveal="right">
        <h2 class="text-3xl md:text-4xl font-serif text-gray-900 mb-8"><?php echo esc_html( ah_field( 'ab_story_title', 'Our Story' ) ); ?></h2>
        <div class="space-y-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Pharmacist-led from the start</h3>
            <p class="text-gray-600 leading-relaxed"><?php echo esc_html( ah_field( 'ab_story_1', 'Together Clinic is a GPhC-registered online pharmacy led by independent pharmacist prescribers. Every treatment we supply is reviewed by a prescriber before it is dispensed — no exceptions.' ) ); ?></p>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Solving the Access Problem</h3>
            <p class="text-gray-600 leading-relaxed"><?php echo esc_html( ah_field( 'ab_story_2', 'Getting expert care used to mean waiting weeks. We removed the barriers — prescriber review within 24 hours, tracked 48-hour delivery, and transparent pricing with nothing charged until you are approved.' ) ); ?></p>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Compromise on Quality</h3>
            <p class="text-gray-600 leading-relaxed"><?php echo esc_html( ah_field( 'ab_story_3', 'Expert guidance, genuine UK-licensed medication, fast delivery, and ongoing support — everything you need for safe, effective treatment.' ) ); ?></p>
          </div>
        </div>
        <div class="mt-8 bg-purple-50 border border-purple-200 rounded-2xl p-6">
          <p class="text-purple-800 text-sm italic">"We're not here to sell you a quick fix. We're here to support a genuine, lasting transformation."</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>
<?php get_footer(); ?>

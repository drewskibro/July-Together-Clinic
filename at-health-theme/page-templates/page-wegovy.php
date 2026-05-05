<?php
/**
 * Template Name: Wegovy
 * Description: Wegovy (semaglutide) product page.
 */
get_header();
?>

<!-- Breadcrumb -->
<div style="background:#fdf8f3;" class="border-b border-gray-200/50 py-4">
  <div class="max-w-7xl mx-auto px-6">
    <div class="flex items-center gap-2 text-sm text-gray-500">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-purple-600 transition-colors">Home</a>
      <span class="text-gray-300">/</span>
      <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'treatments' ) ) ); ?>" class="hover:text-purple-600 transition-colors">Treatments</a>
      <span class="text-gray-300">/</span>
      <span class="text-gray-900 font-medium">Wegovy</span>
    </div>
  </div>
</div>

<!-- Product Hero -->
<section class="py-14 md:py-16" style="background:#fdf8f3;">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid lg:grid-cols-2 gap-12 items-start">
      <div data-reveal>
        <div class="flex items-center gap-3 mb-5">
          <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
          <p class="text-purple-600 text-xs font-bold uppercase tracking-wider"><?php echo esc_html( ah_field( 'wg_eyebrow', 'Semaglutide · Once Weekly' ) ); ?></p>
        </div>

        <div class="flex items-center gap-2 mb-4">
          <div class="flex gap-0.5 text-amber-400">
            <?php for ( $i = 0; $i < 5; $i++ ) : ?>
            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            <?php endfor; ?>
          </div>
          <span class="text-sm text-gray-500"><?php echo esc_html( ah_field( 'wg_rating_text', '4.9 · 3,124 reviews' ) ); ?></span>
        </div>

        <h1 class="text-5xl lg:text-6xl font-serif text-gray-900 mb-5 leading-[1.02] tracking-[-0.02em]"><?php echo esc_html( ah_field( 'wg_title', 'Wegovy' ) ); ?></h1>

        <p class="text-base md:text-lg text-gray-600 leading-relaxed mb-8 max-w-lg">
          <?php echo esc_html( ah_field( 'wg_description', 'Wegovy (semaglutide) is a once-weekly injection specifically approved for weight management. Clinical trials show patients lose up to 20.7% of their body weight. Food and Drug Administration (FDA) and Medicines and Healthcare products Regulatory Agency (MHRA) approved with proven cardiovascular benefits.' ) ); ?>
        </p>

        <div class="space-y-3 mb-8">
          <?php
          $benefits = array(
              '<strong>Up to 20.7% body weight loss</strong> in clinical trials',
              '<strong>Once-weekly injection</strong> — simple and convenient',
              '<strong>Reduces cardiovascular risk by 20%</strong> in clinical studies',
              '<strong>Delivered within 48 hours</strong> with ongoing support',
          );
          $acf_benefits = ah_field( 'wg_benefits', '' );
          if ( is_array( $acf_benefits ) && count( $acf_benefits ) > 0 ) {
              $benefits = wp_list_pluck( $acf_benefits, 'text' );
          }
          foreach ( $benefits as $benefit ) : ?>
          <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-purple-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <p class="text-gray-700 text-[15px]"><?php echo wp_kses_post( $benefit ); ?></p>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="flex flex-wrap items-center gap-x-6 gap-y-3 pt-6 border-t border-gray-200/70">
          <?php foreach ( array( 'FDA Approved', 'MHRA Approved', 'UK Prescribers' ) as $badge ) : ?>
          <div class="flex items-center gap-2 text-gray-600 text-sm">
            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span class="font-medium"><?php echo esc_html( $badge ); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div data-reveal="right">
        <div class="bg-white rounded-3xl border border-gray-200 overflow-hidden shadow-lg">
          <?php
          $wg_image = ah_field( 'wg_product_image', '' );
          if ( $wg_image ) :
              echo wp_get_attachment_image( $wg_image, 'hero-image', false, array( 'class' => 'w-full h-[400px] object-cover' ) );
          else : ?>
            <img src="https://c.animaapp.com/mkl3lxzpWoqisd/img/wegovy-%281%29.jpg" alt="Wegovy packaging" class="w-full h-[400px] object-cover" />
          <?php endif; ?>
          <div class="p-8">
            <div class="flex items-baseline gap-2 mb-4">
              <span class="text-sm text-gray-500">Starting From</span>
              <span class="text-4xl font-serif text-gray-900">&pound;<?php echo esc_html( ah_field( 'wg_price', '179' ) ); ?></span>
              <span class="text-gray-500 text-sm">/month</span>
            </div>
            <p class="text-sm text-gray-500 mb-6"><?php echo esc_html( ah_field( 'wg_price_includes', 'Includes medication, consultations & support' ) ); ?></p>
            <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="w-full flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-base font-semibold px-8 py-4 rounded-xl shadow-lg hover:shadow-xl transition-all hover-lift">
              <?php echo esc_html( ah_field( 'wg_cta_text', 'Start Journey →' ) ); ?>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Dosing Schedule -->
<section class="py-14 md:py-16" style="background: #f7f4f9;">
  <div class="ah-container-wide">
    <div class="text-center mb-12 section-header">
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
        <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider"><?php echo esc_html( ah_field( 'wg_dosing_eyebrow', 'Gradual & Personalised' ) ); ?></p>
      </div>
      <h2 class="text-3xl md:text-4xl lg:text-5xl text-gray-800 font-serif leading-[1.1] mb-4">
        <?php echo wp_kses_post( ah_field( 'wg_dosing_title', 'How Wegovy Dosing Works' ) ); ?>
      </h2>
    </div>
    <div class="max-w-3xl mx-auto space-y-6" data-stagger>
      <?php
      $default_doses = array(
          array( 'dose' => '0.25mg', 'label' => 'Starting Dose', 'desc' => 'Your body adjusts to semaglutide over the first 4 weeks.' ),
          array( 'dose' => '0.5mg', 'label' => 'Month 2', 'desc' => 'First increase — appetite changes typically begin.' ),
          array( 'dose' => '1mg', 'label' => 'Month 3', 'desc' => 'Many patients achieve significant results at this dose.' ),
          array( 'dose' => '1.7mg', 'label' => 'Month 4', 'desc' => 'Continued increase if clinically appropriate.' ),
          array( 'dose' => '2.4mg', 'label' => 'Maximum Dose', 'desc' => 'Full maintenance dose for optimal weight loss.' ),
      );
      $doses = ah_field( 'wg_doses', '' );
      if ( ! is_array( $doses ) || count( $doses ) === 0 ) { $doses = $default_doses; }
      foreach ( $doses as $i => $dose ) : ?>
      <div class="wg-dosing-step flex items-start gap-4" data-reveal style="--stagger-index:<?php echo (int) $i; ?>">
        <div class="w-10 h-10 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold text-sm flex-shrink-0"><?php echo (int) ( $i + 1 ); ?></div>
        <div class="flex-1 bg-white rounded-2xl border border-gray-200 p-5">
          <div class="flex items-center gap-3 mb-1">
            <span class="text-lg font-serif text-gray-900"><?php echo esc_html( $dose['dose'] ); ?></span>
            <span class="text-xs font-bold uppercase tracking-wider text-purple-600"><?php echo esc_html( $dose['label'] ); ?></span>
          </div>
          <p class="text-sm text-gray-600"><?php echo esc_html( $dose['desc'] ); ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="max-w-3xl mx-auto mt-8 bg-purple-50 border border-purple-200 rounded-2xl p-6" data-reveal>
      <p class="text-sm text-purple-800"><strong>Important: Personalised Dosing</strong> — <?php echo esc_html( ah_field( 'wg_dosing_note', 'Many patients achieve excellent results at 1mg or 1.7mg. Your prescriber will recommend the optimal dose.' ) ); ?></p>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="py-14 md:py-16" style="background: #fdf8f3;">
  <div class="ah-container-wide">
    <div class="text-center mb-12">
      <h2 class="text-3xl md:text-4xl font-serif text-gray-900 mb-4"><?php echo wp_kses_post( ah_field( 'wg_faq_title', 'Wegovy FAQs' ) ); ?></h2>
    </div>
    <div class="max-w-3xl mx-auto space-y-4 ah-faq-accordion" data-stagger>
      <?php
      $default_faqs = array(
          array( 'question' => 'How does Wegovy work?', 'answer' => 'Wegovy (semaglutide) mimics a naturally occurring hormone called Glucagon-Like Peptide-1 (GLP-1) that targets areas of the brain involved in appetite regulation. It reduces hunger, slows digestion, and helps you feel satisfied with less food.' ),
          array( 'question' => 'What are the cardiovascular benefits?', 'answer' => 'Clinical studies show Wegovy reduces cardiovascular risk by 20%. It can lower blood pressure, improve cholesterol levels, and reduce inflammation — benefits that go beyond weight loss alone.' ),
          array( 'question' => 'What side effects should I expect?', 'answer' => 'Common side effects include mild nausea, reduced appetite, and occasional digestive discomfort. These typically settle within 2-4 weeks. The gradual dosing schedule helps minimise side effects.' ),
          array( 'question' => 'How long do I need to take Wegovy?', 'answer' => 'Wegovy is designed as an ongoing treatment. Clinical evidence shows weight management is most effective with continued use. Your prescriber will work with you on a long-term plan.' ),
      );
      $faqs = ah_field( 'wg_faqs', '' );
      if ( ! is_array( $faqs ) || count( $faqs ) === 0 ) { $faqs = $default_faqs; }
      foreach ( $faqs as $i => $faq ) : ?>
      <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-300 hover:border-purple-200" data-reveal style="--stagger-index:<?php echo (int) $i; ?>">
        <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-6 md:px-8 py-5 text-left group">
          <span class="text-base md:text-lg font-semibold text-gray-900 pr-4 group-hover:text-purple-600 transition-colors"><?php echo esc_html( $faq['question'] ); ?></span>
          <div class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center flex-shrink-0 group-hover:bg-purple-100 transition-colors">
            <svg class="w-4 h-4 text-purple-600 transition-transform duration-300 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>
        </button>
        <div class="faq-body"><div class="px-6 md:px-8 pb-6 text-gray-600 leading-relaxed text-[15px]"><?php echo esc_html( $faq['answer'] ); ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Dark CTA -->
<section class="relative py-20 md:py-28 overflow-hidden" style="background: #0f1117;">
  <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
    <div class="w-[600px] h-[600px] rounded-full opacity-[0.07]" style="background: radial-gradient(circle, #9b8fce 0%, transparent 70%);"></div>
  </div>
  <div class="max-w-4xl mx-auto px-6 text-center relative z-10" data-reveal>
    <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-gray-500 mb-6">Start Today</p>
    <h2 class="text-4xl md:text-5xl lg:text-6xl font-serif text-white leading-[1.05] mb-5 tracking-[-0.02em]">
      <?php echo wp_kses_post( ah_field( 'wg_cta_title', 'Ready to start your<br><em class="not-italic" style="color: #a89dd6;">Wegovy journey?</em>' ) ); ?>
    </h2>
    <p class="text-base md:text-lg text-gray-400 leading-relaxed mb-10 max-w-xl mx-auto">
      <?php echo esc_html( ah_field( 'wg_cta_subtitle', 'Join thousands achieving life-changing results with clinically proven weight loss.' ) ); ?>
    </p>
    <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="inline-flex items-center gap-3 bg-white hover:bg-gray-100 text-gray-900 text-[15px] font-semibold px-10 py-4 rounded-xl transition-all hover-lift shadow-xl">
      Start Journey <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
    </a>
  </div>
</section>

<?php get_footer(); ?>

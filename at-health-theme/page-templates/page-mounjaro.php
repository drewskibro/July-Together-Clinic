<?php
/**
 * Template Name: Mounjaro
 * Description: Mounjaro (tirzepatide) product page with hero, dosing, reviews, FAQ, and CTA.
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
      <span class="text-gray-900 font-medium">Mounjaro</span>
    </div>
  </div>
</div>

<!-- Product Hero -->
<section class="py-14 md:py-16" style="background:#fdf8f3;">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid lg:grid-cols-2 gap-12 items-start">
      <!-- Left: Product Info -->
      <div data-reveal>
        <div class="flex items-center gap-3 mb-5">
          <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
          <p class="text-purple-600 text-xs font-bold uppercase tracking-wider"><?php echo esc_html( ah_field( 'mj_eyebrow', 'Tirzepatide · Once Weekly' ) ); ?></p>
        </div>

        <div class="flex items-center gap-2 mb-4">
          <div class="flex gap-0.5 text-amber-400">
            <?php for ( $i = 0; $i < 5; $i++ ) : ?>
            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            <?php endfor; ?>
          </div>
          <span class="text-sm text-gray-500"><?php echo esc_html( ah_field( 'mj_rating_text', '4.9 · 2,847 reviews' ) ); ?></span>
        </div>

        <h1 class="text-5xl lg:text-6xl font-serif text-gray-900 mb-5 leading-[1.02] tracking-[-0.02em]"><?php echo esc_html( ah_field( 'mj_title', 'Mounjaro' ) ); ?></h1>

        <p class="text-base md:text-lg text-gray-600 leading-relaxed mb-8 max-w-lg">
          <?php echo esc_html( ah_field( 'mj_description', 'Mounjaro (tirzepatide) is a once-weekly injection that helps you lose weight by reducing appetite and slowing digestion. Clinical trials show patients lose up to 22.5% of their body weight. Food and Drug Administration (FDA) and Medicines and Healthcare products Regulatory Agency (MHRA) approved for weight management.' ) ); ?>
        </p>

        <!-- Key Benefits -->
        <div class="space-y-3 mb-8">
          <?php
          $benefits = array(
              '<strong>Up to 22.5% body weight loss</strong> in clinical trials',
              '<strong>Once-weekly injection</strong> — convenient dosing',
              '<strong>Dual-action formula</strong> — Glucagon-Like Peptide-1 (GLP-1) and Glucose-dependent Insulinotropic Polypeptide (GIP) receptor agonist',
              '<strong>Delivered within 48 hours</strong> with ongoing support',
          );
          $acf_benefits = ah_field( 'mj_benefits', '' );
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

        <!-- Trust strip -->
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3 pt-6 border-t border-gray-200/70">
          <div class="flex items-center gap-2 text-gray-600 text-sm">
            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span class="font-medium">FDA Approved</span>
          </div>
          <div class="flex items-center gap-2 text-gray-600 text-sm">
            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span class="font-medium">MHRA Approved</span>
          </div>
          <div class="flex items-center gap-2 text-gray-600 text-sm">
            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span class="font-medium">UK Prescribers</span>
          </div>
        </div>
      </div>

      <!-- Right: Product Image + Price -->
      <div data-reveal="right">
        <div class="bg-white rounded-3xl border border-gray-200 overflow-hidden shadow-lg">
          <?php
          $mj_image = ah_field( 'mj_product_image', '' );
          if ( $mj_image ) :
              echo wp_get_attachment_image( $mj_image, 'hero-image', false, array( 'class' => 'w-full h-[400px] object-cover' ) );
          else : ?>
            <img src="https://c.animaapp.com/mkl3lxzpWoqisd/img/mounjaro.jpg" alt="Mounjaro packaging" class="w-full h-[400px] object-cover" />
          <?php endif; ?>

          <div class="p-8">
            <div class="flex items-baseline gap-2 mb-4">
              <span class="text-sm text-gray-500">Starting From</span>
              <span class="text-4xl font-serif text-gray-900">&pound;<?php echo esc_html( ah_field( 'mj_price', '199' ) ); ?></span>
              <span class="text-gray-500 text-sm">/month</span>
            </div>
            <p class="text-sm text-gray-500 mb-6"><?php echo esc_html( ah_field( 'mj_price_includes', 'Includes medication, consultations & support' ) ); ?></p>
            <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="w-full flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-base font-semibold px-8 py-4 rounded-xl shadow-lg hover:shadow-xl transition-all hover-lift">
              <?php echo esc_html( ah_field( 'mj_cta_text', 'Start Journey →' ) ); ?>
            </a>
            <div class="flex items-center justify-center gap-4 mt-4 text-xs text-gray-500">
              <span>Same-day prescription approval</span>
              <span>·</span>
              <span>Delivered within 48 hours</span>
              <span>·</span>
              <span>Cancel anytime</span>
            </div>
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
        <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider"><?php echo esc_html( ah_field( 'mj_dosing_eyebrow', 'Gradual & Personalised' ) ); ?></p>
      </div>
      <h2 class="text-3xl md:text-4xl lg:text-5xl text-gray-800 font-serif leading-[1.1] mb-4">
        <?php echo wp_kses_post( ah_field( 'mj_dosing_title', 'How Mounjaro Dosing Works' ) ); ?>
      </h2>
      <p class="text-base md:text-lg text-gray-700 leading-[1.7] max-w-2xl mx-auto">
        <?php echo esc_html( ah_field( 'mj_dosing_subtitle', 'Gradual dose increases every 4 weeks to minimise side effects and maximise results.' ) ); ?>
      </p>
    </div>

    <div class="max-w-3xl mx-auto space-y-6" data-stagger>
      <?php
      $default_doses = array(
          array( 'dose' => '2.5mg', 'label' => 'Starting Dose', 'desc' => 'Your body adjusts to the medication over the first 4 weeks.' ),
          array( 'dose' => '5mg', 'label' => 'After 4 Weeks', 'desc' => 'First increase — many patients start seeing appetite changes here.' ),
          array( 'dose' => '7.5mg', 'label' => 'If Needed', 'desc' => 'Gradual increase based on your response and tolerance.' ),
          array( 'dose' => '10mg', 'label' => 'Continued Progress', 'desc' => 'Continued dose escalation if clinically appropriate.' ),
          array( 'dose' => '12.5mg', 'label' => 'Advanced', 'desc' => 'Higher dose for patients who need additional support.' ),
          array( 'dose' => '15mg', 'label' => 'Maximum Dose', 'desc' => 'The highest available dose for maximum weight loss.' ),
      );
      $doses = ah_field( 'mj_doses', '' );
      if ( ! is_array( $doses ) || count( $doses ) === 0 ) {
          $doses = $default_doses;
      }
      foreach ( $doses as $i => $dose ) : ?>
      <div class="mj-dosing-step flex items-start gap-4" data-reveal style="--stagger-index:<?php echo (int) $i; ?>">
        <div class="w-10 h-10 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
          <?php echo (int) ( $i + 1 ); ?>
        </div>
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
      <p class="text-sm text-purple-800"><strong>Important: Personalised Dosing</strong> — <?php echo esc_html( ah_field( 'mj_dosing_note', 'Many patients achieve excellent results at 5mg or 7.5mg. Your prescriber will recommend the optimal dose based on your individual response.' ) ); ?></p>
    </div>
  </div>
</section>

<!-- FAQ Section -->
<section class="py-14 md:py-16" style="background: #fdf8f3;">
  <div class="ah-container-wide">
    <div class="text-center mb-12">
      <h2 class="text-3xl md:text-4xl font-serif text-gray-900 mb-4"><?php echo wp_kses_post( ah_field( 'mj_faq_title', 'Mounjaro FAQs' ) ); ?></h2>
    </div>
    <div class="max-w-3xl mx-auto space-y-4 ah-faq-accordion" data-stagger>
      <?php
      $default_faqs = array(
          array( 'question' => 'How does Mounjaro work?', 'answer' => 'Mounjaro (tirzepatide) works by activating both GLP-1 and GIP receptors, which reduces appetite, slows digestion, and helps regulate blood sugar. This dual-action mechanism makes it one of the most effective weight loss treatments available.' ),
          array( 'question' => 'What are the common side effects?', 'answer' => 'The most common side effects are mild nausea, reduced appetite, and occasional digestive discomfort. These typically settle within 2-4 weeks as your body adjusts. Starting at a low dose and gradually increasing helps minimise side effects.' ),
          array( 'question' => 'How quickly will I see results?', 'answer' => 'Most patients notice appetite changes within the first week and visible weight loss within 2-4 weeks. Clinical trials show average losses of 22.5% body weight over 72 weeks.' ),
          array( 'question' => 'Can I take Mounjaro with other medications?', 'answer' => 'Your prescriber will review your full medical history and current medications before approving treatment. Mounjaro can interact with some medications, so it\'s important to disclose everything during your assessment.' ),
      );
      $faqs = ah_field( 'mj_faqs', '' );
      if ( ! is_array( $faqs ) || count( $faqs ) === 0 ) {
          $faqs = $default_faqs;
      }
      foreach ( $faqs as $i => $faq ) : ?>
      <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-300 hover:border-purple-200" data-reveal style="--stagger-index:<?php echo (int) $i; ?>">
        <button onclick="toggleFaq(this)" class="w-full flex items-center justify-between px-6 md:px-8 py-5 text-left group">
          <span class="text-base md:text-lg font-semibold text-gray-900 pr-4 group-hover:text-purple-600 transition-colors"><?php echo esc_html( $faq['question'] ); ?></span>
          <div class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center flex-shrink-0 group-hover:bg-purple-100 transition-colors">
            <svg class="w-4 h-4 text-purple-600 transition-transform duration-300 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </div>
        </button>
        <div class="faq-body">
          <div class="px-6 md:px-8 pb-6 text-gray-600 leading-relaxed text-[15px]"><?php echo esc_html( $faq['answer'] ); ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA Section -->
<section class="relative py-20 md:py-28 overflow-hidden" style="background: #0f1117;">
  <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
    <div class="w-[600px] h-[600px] rounded-full opacity-[0.07]" style="background: radial-gradient(circle, #9b8fce 0%, transparent 70%);"></div>
  </div>
  <div class="max-w-4xl mx-auto px-6 text-center relative z-10" data-reveal>
    <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-gray-500 mb-6"><?php echo esc_html( ah_field( 'mj_cta_eyebrow', 'Start Today' ) ); ?></p>
    <h2 class="text-4xl md:text-5xl lg:text-6xl font-serif text-white leading-[1.05] mb-5 tracking-[-0.02em]">
      <?php echo wp_kses_post( ah_field( 'mj_cta_title', 'Ready to start your<br><em class="not-italic" style="color: #a89dd6;">Mounjaro journey?</em>' ) ); ?>
    </h2>
    <p class="text-base md:text-lg text-gray-400 leading-relaxed mb-10 max-w-xl mx-auto">
      <?php echo esc_html( ah_field( 'mj_cta_subtitle', 'Join thousands achieving life-changing results with clinically proven weight loss treatment.' ) ); ?>
    </p>
    <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="inline-flex items-center gap-3 bg-white hover:bg-gray-100 text-gray-900 text-[15px] font-semibold px-10 py-4 rounded-xl transition-all hover-lift shadow-xl mb-10">
      Start Journey
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
    </a>
    <div class="flex flex-wrap items-center justify-center gap-x-8 gap-y-3 border-t border-white/[0.07] pt-8">
      <?php foreach ( array( 'From £199/month', 'Same-day approval', 'Delivered within 48 hours', 'Cancel anytime' ) as $t ) : ?>
      <div class="flex items-center gap-2 text-gray-400 text-sm">
        <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
        <?php echo esc_html( $t ); ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php get_footer(); ?>

<?php
/**
 * Template Name: Foundayo
 * Description: Foundayo (orforglipron) oral GLP-1 product page.
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
      <span class="text-gray-900 font-medium">Foundayo</span>
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
          <p class="text-purple-600 text-xs font-bold uppercase tracking-wider"><?php echo esc_html( ah_field( 'fd_eyebrow', 'Orforglipron · Once-Daily Tablet' ) ); ?></p>
        </div>

        <div class="flex items-center gap-2 mb-4">
          <span class="inline-flex items-center gap-2 bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold uppercase tracking-wider px-3 py-1.5 rounded-full">
            <?php echo esc_html( ah_field( 'fd_flag', 'New — MHRA approved August 2026' ) ); ?>
          </span>
        </div>

        <h1 class="text-5xl lg:text-6xl font-serif text-gray-900 mb-5 leading-[1.02] tracking-[-0.02em]"><?php echo esc_html( ah_field( 'fd_title', 'Foundayo' ) ); ?></h1>

        <p class="text-base md:text-lg text-gray-600 leading-relaxed mb-8 max-w-lg">
          <?php echo esc_html( ah_field( 'fd_description', 'Foundayo (orforglipron) is a once-daily weight management tablet and the first non-peptide GLP-1 medicine licensed in the UK. Unlike other oral GLP-1 treatments, it is stable in stomach acid — so there are no fasting rules, no waiting period, and no restrictions on when you take it.' ) ); ?>
        </p>

        <div class="space-y-3 mb-8">
          <?php
          $benefits = array(
              '<strong>Take it any time of day</strong> — with or without food',
              '<strong>Once-daily tablet</strong> — no needles',
              '<strong>No fasting or waiting period</strong> before you eat or drink',
              '<strong>Six dose strengths</strong> — increased gradually under supervision',
              '<strong>Clinician-supervised treatment</strong> with ongoing support',
          );
          $acf_benefits = ah_field( 'fd_benefits', '' );
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
          <?php foreach ( array( 'MHRA Approved', 'UK Prescribers', 'No Food Restrictions' ) as $badge ) : ?>
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
          $fd_image = ah_field( 'fd_product_image', '' );
          if ( $fd_image ) :
              echo wp_get_attachment_image( $fd_image, 'hero-image', false, array( 'class' => 'w-full h-[400px] object-cover' ) );
          else : ?>
            <?php echo ah_treatment_visual( 'tablet', 'h-[400px]' ); ?>
          <?php endif; ?>
          <div class="p-8">
            <div class="flex items-baseline gap-2 mb-4">
              <span class="text-sm text-gray-500">Starting From</span>
              <span class="text-4xl font-serif text-gray-900">&pound;<?php echo esc_html( ah_field( 'fd_price', '99' ) ); ?></span>
              <span class="text-gray-500 text-sm">/month</span>
            </div>
            <p class="text-sm text-gray-500 mb-6"><?php echo esc_html( ah_field( 'fd_price_includes', 'Includes medication, consultations & support · 30-day supply' ) ); ?></p>
            <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="w-full flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-base font-semibold px-8 py-4 rounded-xl shadow-lg hover:shadow-xl transition-all hover-lift">
              <?php echo esc_html( ah_field( 'fd_cta_text', 'Start Journey →' ) ); ?>
            </a>
            <div class="flex items-center justify-center gap-4 mt-4 text-xs text-gray-500">
              <span>No needles</span>
              <span>·</span>
              <span>Tracked, discreet delivery</span>
              <span>·</span>
              <span>Cancel anytime</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- What makes it different -->
<section class="py-14 md:py-16 bg-white">
  <div class="ah-container-wide">
    <div class="text-center mb-12 section-header">
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
        <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider"><?php echo esc_html( ah_field( 'fd_diff_eyebrow', 'What Makes Foundayo Different' ) ); ?></p>
      </div>
      <h2 class="text-3xl md:text-4xl lg:text-5xl text-gray-800 font-serif leading-[1.1] mb-4">
        <?php echo wp_kses_post( ah_field( 'fd_diff_title', 'A tablet that fits around your day' ) ); ?>
      </h2>
      <p class="text-base md:text-lg text-gray-700 leading-[1.7] max-w-2xl mx-auto">
        <?php echo esc_html( ah_field( 'fd_diff_subtitle', 'Most oral GLP-1 medicines have to be taken on an empty stomach, with a strict waiting period before food. Foundayo does not.' ) ); ?>
      </p>
    </div>

    <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto" data-stagger>
      <?php
      $default_diffs = array(
          array( 'title' => 'No fasting rules', 'desc' => 'Take it with or without food, at whatever time of day suits you. There is no waiting period before eating or drinking.' ),
          array( 'title' => 'Stable in stomach acid', 'desc' => 'Foundayo is a non-peptide GLP-1 — the first licensed in the UK. Its structure means it does not need the protective dosing routine other oral GLP-1s require.' ),
          array( 'title' => 'Simple daily routine', 'desc' => 'One tablet, swallowed whole. No injections, no pens, no sharps disposal, nothing to refrigerate.' ),
      );
      $diffs = ah_field( 'fd_diffs', '' );
      if ( ! is_array( $diffs ) || count( $diffs ) === 0 ) { $diffs = $default_diffs; }
      foreach ( $diffs as $i => $diff ) : ?>
      <div class="fd-diff-card" data-reveal style="--stagger-index:<?php echo (int) $i; ?>">
        <h3 class="text-xl font-serif text-gray-900 mb-3"><?php echo esc_html( $diff['title'] ); ?></h3>
        <p class="text-[15px] text-gray-600 leading-relaxed"><?php echo esc_html( $diff['desc'] ); ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Dosing Schedule -->
<section class="py-14 md:py-16" style="background: #f7f4f9;">
  <div class="ah-container-wide">
    <div class="text-center mb-12 section-header">
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
        <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider"><?php echo esc_html( ah_field( 'fd_dosing_eyebrow', 'Gradual & Personalised' ) ); ?></p>
      </div>
      <h2 class="text-3xl md:text-4xl lg:text-5xl text-gray-800 font-serif leading-[1.1] mb-4">
        <?php echo wp_kses_post( ah_field( 'fd_dosing_title', 'How Foundayo Dosing Works' ) ); ?>
      </h2>
      <p class="text-base md:text-lg text-gray-700 leading-[1.7] max-w-2xl mx-auto">
        <?php echo esc_html( ah_field( 'fd_dosing_subtitle', 'Six dose strengths, with a minimum of 30 days at each before any increase. Most patients reach their maintenance dose after three to four months.' ) ); ?>
      </p>
    </div>
    <div class="max-w-3xl mx-auto space-y-6" data-stagger>
      <?php
      $default_doses = array(
          array( 'dose' => '0.8mg',  'label' => 'Starting Dose', 'price' => '99',  'desc' => 'At least 30 days while your body adjusts to the medication.' ),
          array( 'dose' => '2.5mg',  'label' => 'Step 2',        'price' => '119', 'desc' => 'First increase — appetite changes typically begin around here.' ),
          array( 'dose' => '5.5mg',  'label' => 'Step 3',        'price' => '129', 'desc' => 'A further increase if you are tolerating treatment well.' ),
          array( 'dose' => '9mg',    'label' => 'Step 4',        'price' => '159', 'desc' => 'Many patients find their maintenance dose at this level.' ),
          array( 'dose' => '14.5mg', 'label' => 'Step 5',        'price' => '189', 'desc' => 'A higher dose for patients who need additional support.' ),
          array( 'dose' => '17.2mg', 'label' => 'Maximum Dose',  'price' => '199', 'desc' => 'The highest licensed strength. Not everyone needs to reach it.' ),
      );
      $doses = ah_field( 'fd_doses', '' );
      if ( ! is_array( $doses ) || count( $doses ) === 0 ) { $doses = $default_doses; }
      foreach ( $doses as $i => $dose ) : ?>
      <div class="fd-dosing-step flex items-start gap-4" data-reveal style="--stagger-index:<?php echo (int) $i; ?>">
        <div class="w-10 h-10 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold text-sm flex-shrink-0"><?php echo (int) ( $i + 1 ); ?></div>
        <div class="flex-1 bg-white rounded-2xl border border-gray-200 p-5">
          <div class="flex items-center justify-between gap-3 mb-1 flex-wrap">
            <div class="flex items-center gap-3">
              <span class="text-lg font-serif text-gray-900"><?php echo esc_html( $dose['dose'] ); ?></span>
              <span class="text-xs font-bold uppercase tracking-wider text-purple-600"><?php echo esc_html( $dose['label'] ); ?></span>
            </div>
            <?php if ( ! empty( $dose['price'] ) ) : ?>
            <span class="text-sm font-semibold text-gray-900 bg-purple-50 border border-purple-100 rounded-full px-3 py-1">&pound;<?php echo esc_html( $dose['price'] ); ?> <span class="font-normal text-gray-500">· 30 days</span></span>
            <?php endif; ?>
          </div>
          <p class="text-sm text-gray-600"><?php echo esc_html( $dose['desc'] ); ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="max-w-3xl mx-auto mt-8 bg-purple-50 border border-purple-200 rounded-2xl p-6" data-reveal>
      <p class="text-sm text-purple-800"><strong>Important: Personalised Dosing</strong> — <?php echo esc_html( ah_field( 'fd_dosing_note', 'You can stay on a strength for longer if side effects need more time to settle, or if you are already losing weight steadily. Your prescriber decides each increase with you, and can adjust it at any reorder.' ) ); ?></p>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="py-14 md:py-16" style="background: #fdf8f3;">
  <div class="ah-container-wide">
    <div class="text-center mb-12">
      <h2 class="text-3xl md:text-4xl font-serif text-gray-900 mb-4"><?php echo wp_kses_post( ah_field( 'fd_faq_title', 'Foundayo FAQs' ) ); ?></h2>
    </div>
    <div class="max-w-3xl mx-auto space-y-4 ah-faq-accordion" data-stagger>
      <?php
      $default_faqs = array(
          array( 'question' => 'How is Foundayo different from Wegovy tablets?', 'answer' => 'Both are once-daily tablets, but they contain different medicines and are taken differently. Wegovy tablets contain semaglutide and must be taken on an empty stomach, with at least 30 minutes before food, drink or other medicines. Foundayo contains orforglipron, which is stable in stomach acid — so you can take it at any time of day, with or without food.' ),
          array( 'question' => 'How do I take Foundayo?', 'answer' => 'One tablet, once a day, swallowed whole with water. Do not crush, split or chew it. You can take it at whatever time of day suits you, with or without food.' ),
          array( 'question' => 'How does Foundayo work?', 'answer' => 'Foundayo (orforglipron) is a GLP-1 receptor agonist. It reduces appetite, slows digestion and helps regulate blood sugar, so you feel satisfied with less food. It is the first non-peptide GLP-1 medicine to be licensed for weight management in the UK.' ),
          array( 'question' => 'Can I switch to Foundayo from another treatment?', 'answer' => 'Yes. Use the screening form and tell us your current treatment and dose. Your prescriber will review it with you and confirm the appropriate Foundayo starting strength — switching does not always mean starting at the lowest dose.' ),
          array( 'question' => 'What side effects should I expect?', 'answer' => 'The most common side effects are mild nausea, reduced appetite and digestive discomfort, particularly in the first weeks and after each dose increase. These usually settle. The gradual titration across six strengths, with at least 30 days at each, is designed to keep side effects manageable.' ),
          array( 'question' => 'Am I eligible?', 'answer' => 'Foundayo is generally suitable for adults with a BMI of 30 or above, or 27 or above alongside a weight-related health condition. Complete the screening assessment and one of our UK-registered prescribers will confirm whether it is appropriate for you.' ),
      );
      $faqs = ah_field( 'fd_faqs', '' );
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
    <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-gray-500 mb-6"><?php echo esc_html( ah_field( 'fd_cta_eyebrow', 'Start Today' ) ); ?></p>
    <h2 class="text-4xl md:text-5xl lg:text-6xl font-serif text-white leading-[1.05] mb-5 tracking-[-0.02em]">
      <?php echo wp_kses_post( ah_field( 'fd_cta_title', 'The newest tablet,<br><em class="not-italic" style="color: #a89dd6;">on your schedule.</em>' ) ); ?>
    </h2>
    <p class="text-base md:text-lg text-gray-400 leading-relaxed mb-10 max-w-xl mx-auto">
      <?php echo esc_html( ah_field( 'fd_cta_subtitle', 'A once-daily GLP-1 tablet with no fasting rules, prescribed and supervised by UK clinicians.' ) ); ?>
    </p>
    <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="inline-flex items-center gap-3 bg-white hover:bg-gray-100 text-gray-900 text-[15px] font-semibold px-10 py-4 rounded-xl transition-all hover-lift shadow-xl mb-10">
      Start Journey <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
    </a>
    <div class="flex flex-wrap items-center justify-center gap-x-8 gap-y-3 border-t border-white/[0.07] pt-8">
      <?php foreach ( array( 'From £99/month', 'No fasting rules', 'Tracked delivery', 'Cancel anytime' ) as $t ) : ?>
      <div class="flex items-center gap-2 text-gray-400 text-sm">
        <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
        <?php echo esc_html( $t ); ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php get_footer(); ?>

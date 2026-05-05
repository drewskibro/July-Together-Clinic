<?php
/**
 * Template Part: FAQ Accordion Section
 * Uses ACF repeater for FAQ items.
 */

$faq_eyebrow  = ah_field( 'faq_eyebrow', 'Common Questions' );
$faq_title    = ah_field( 'faq_title', 'Got Questions? We\'ve Got <span class="text-purple-600">Answers</span>' );
$faq_subtitle = ah_field( 'faq_subtitle', 'Everything you need to know before starting your weight loss journey.' );

// Default FAQ items
$default_faqs = array(
    array(
        'question' => 'How do I know if I\'m eligible?',
        'answer'   => 'You\'ll need a Body Mass Index (BMI) of 30 or above (or 27+ with a weight-related health condition). Our free 5-minute online assessment checks your eligibility instantly — no General Practitioner (GP) referral needed. If you\'re not eligible, you won\'t be charged.',
    ),
    array(
        'question' => 'What are the common side effects?',
        'answer'   => 'The most common side effects are mild nausea, reduced appetite, and occasional digestive changes — these typically settle within the first 2-4 weeks as your body adjusts. Our clinical team starts you on a low dose and gradually increases it to minimise discomfort. You\'ll have direct access to our team throughout.',
    ),
    array(
        'question' => 'How much does treatment cost?',
        'answer'   => 'Treatment plans start from £149/month depending on your prescribed medication and dose. This includes the medication itself, clinical consultations, ongoing monitoring, and tracked delivery. There are no hidden fees, and you can cancel anytime — no lock-in contracts.',
    ),
    array(
        'question' => 'Can I cancel at any time?',
        'answer'   => 'Absolutely. There are no minimum commitments or cancellation fees. You can pause or cancel your treatment at any time through your patient portal or by contacting our customer care team. We believe in earning your trust every month, not locking you in.',
    ),
    array(
        'question' => 'How quickly will I see results?',
        'answer'   => 'Most patients notice appetite changes within the first week and visible weight loss within 2-4 weeks. Over 84% of our patients lose weight in their first month. Clinical trial data shows average losses of 15-22% body weight over 12 months, though individual results vary based on adherence and lifestyle.',
    ),
    array(
        'question' => 'Is this safe? Who prescribes my medication?',
        'answer'   => 'All prescriptions are reviewed and approved by UK-registered prescribers (General Medical Council (GMC) / GPhC qualified). We\'re fully regulated by the GPhC and the Medicines and Healthcare products Regulatory Agency (MHRA). Every medication we dispense is genuine, UK-sourced, and fully traceable. Your safety is our absolute priority.',
    ),
);

// Try to get ACF repeater, fallback to defaults
$faq_items = array();
if ( function_exists( 'have_rows' ) && have_rows( 'faq_items' ) ) {
    while ( have_rows( 'faq_items' ) ) {
        the_row();
        $faq_items[] = array(
            'question' => get_sub_field( 'question' ),
            'answer'   => get_sub_field( 'answer' ),
        );
    }
}
if ( count( $faq_items ) === 0 ) {
    $faq_items = $default_faqs;
}

$contact_url = get_permalink( get_page_by_path( 'contact' ) );
if ( $contact_url === null || $contact_url === '' ) {
    $contact_url = '#';
}
$contact_email = ah_email();
?>

<!-- FAQ / Objection Buster -->
<section class="relative py-14 md:py-16 overflow-hidden">
  <div class="absolute inset-0" style="background: #f7f4f9;"></div>
  <div class="max-w-[900px] mx-auto px-6 md:px-[60px] relative z-10">
    <div class="text-center mb-10 md:mb-12" data-reveal>
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
        <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider"><?php echo esc_html( $faq_eyebrow ); ?></p>
      </div>
      <h2 class="text-3xl md:text-4xl lg:text-5xl text-gray-800 font-serif leading-[1.1] mb-4">
        <?php echo wp_kses_post( $faq_title ); ?>
      </h2>
      <p class="text-base md:text-lg text-gray-600 max-w-xl mx-auto leading-relaxed">
        <?php echo esc_html( $faq_subtitle ); ?>
      </p>
    </div>

    <!-- Accordion -->
    <div class="space-y-3" data-stagger id="faqAccordion">
      <?php foreach ( $faq_items as $index => $faq ) : ?>
      <div
        class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-300 hover:border-purple-200"
        data-reveal
        style="--stagger-index:<?php echo (int) $index; ?>"
      >
        <button
          onclick="toggleFaq(this)"
          class="w-full flex items-center justify-between px-6 md:px-8 py-5 text-left group"
        >
          <span class="text-base md:text-lg font-semibold text-gray-900 pr-4 group-hover:text-purple-600 transition-colors"><?php echo esc_html( $faq['question'] ); ?></span>
          <div class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center flex-shrink-0 group-hover:bg-purple-100 transition-colors">
            <svg class="w-4 h-4 text-purple-600 transition-transform duration-300 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </button>
        <div class="faq-body max-h-0 overflow-hidden transition-all duration-300 ease-in-out">
          <div class="px-6 md:px-8 pb-6 text-gray-600 leading-relaxed text-[15px]">
            <?php echo wp_kses_post( $faq['answer'] ); ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Bottom CTA -->
    <div class="text-center mt-10" data-reveal>
      <p class="text-gray-500 text-sm mb-4"><?php echo esc_html( ah_field( 'faq_still_questions', 'Still have questions?' ) ); ?></p>
      <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
        <a href="<?php echo esc_url( $contact_url ); ?>" class="inline-flex items-center gap-2 text-purple-600 font-semibold text-base hover:text-purple-700 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <?php echo esc_html( ah_field( 'faq_chat_text', 'Chat with our team' ) ); ?>
        </a>
        <span class="text-gray-300 hidden sm:inline">|</span>
        <a href="mailto:<?php echo esc_attr( $contact_email ); ?>" class="inline-flex items-center gap-2 text-purple-600 font-semibold text-base hover:text-purple-700 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          Email <?php echo esc_html( $contact_email ); ?>
        </a>
      </div>
      <p class="text-gray-400 text-xs mt-4 max-w-md mx-auto"><?php echo esc_html( ah_no_phone_notice() ); ?></p>
    </div>
  </div>
</section>

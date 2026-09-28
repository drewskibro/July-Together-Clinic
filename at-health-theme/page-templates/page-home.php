<?php
/**
 * Template Name: Home
 * Description: AT Health pharmacy homepage. Section order: Hero, How It Works, Know Your Team, Safe and Secure, Health Hub, FAQs, CTA.
 *
 * No ratings, testimonials or patient numbers are rendered here unless they can
 * be evidenced. Unsupported template claims (4.9 rating, 10,000+ patients,
 * named testimonials) were removed in the compliance review; the only patient
 * figure is the group statement approved by the superintendent (below).
 */
get_header();

// Section 1: Hero
// PHASE 2: Review hero messaging - ensure pharmacy-focused not weight loss-focused
get_template_part( 'template-parts/section', 'hero' );

// Section 2: How It Works
get_template_part( 'template-parts/section', 'how-it-works' );
?>

<!-- Section 4: Know Your Team -->
<?php
$team_eyebrow  = ah_field( 'team_eyebrow', 'Meet Your Pharmacists' );
$team_title    = ah_field( 'team_title', 'Know Your Team' );
$team_subtitle = ah_field( 'team_subtitle', 'Our pharmacists are fully registered with the General Pharmaceutical Council (GPhC). Click any registration number to verify on the official GPhC register.' );

$team_members = array(
    array(
        'photo'       => ah_field( 'team_member1_photo', '' ),
        'name'        => ah_field( 'team_member1_name', 'Ahmed Nizar Al-Liabi' ),
        'role'        => ah_field( 'team_member1_role', 'Superintendent Pharmacist' ),
        'gphc_number' => ah_field( 'team_member1_gphc_number', '2208502' ),
        'gphc_url'    => ah_field( 'team_member1_gphc_url', 'https://www.pharmacyregulation.org/registers/pharmacist' ),
    ),
    array(
        'photo'       => ah_field( 'team_member2_photo', '' ),
        'name'        => ah_field( 'team_member2_name', 'Sunil Thacker' ),
        'role'        => ah_field( 'team_member2_role', 'Independent Pharmacist Prescriber' ),
        'gphc_number' => ah_field( 'team_member2_gphc_number', '2047968' ),
        'gphc_url'    => ah_field( 'team_member2_gphc_url', 'https://www.pharmacyregulation.org/registers/pharmacist' ),
    ),
    array(
        'photo'       => ah_field( 'team_member3_photo', '' ),
        'default_photo_url' => get_theme_file_uri( 'assets/images/malik-abdulgabar.png' ),
        'name'        => ah_field( 'team_member3_name', 'Malik Abdulgabar' ),
        'role'        => ah_field( 'team_member3_role', 'Pharmacist' ),
        'gphc_number' => ah_field( 'team_member3_gphc_number', '2232372' ),
        'gphc_url'    => ah_field( 'team_member3_gphc_url', 'https://www.pharmacyregulation.org/registers/pharmacist/2232372' ),
    ),
);
?>
<section class="relative py-16 md:py-20" style="background: #fdf8f3;">
  <div class="ah-container-wide">
    <div class="text-center mb-12 section-header">
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
        <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider"><?php echo esc_html( $team_eyebrow ); ?></p>
      </div>
      <h2 class="text-3xl md:text-4xl lg:text-5xl text-gray-800 font-serif leading-[1.1] mb-4">
        <?php echo esc_html( $team_title ); ?>
      </h2>
      <p class="text-base md:text-lg text-gray-700 leading-[1.7] max-w-2xl mx-auto">
        <?php echo esc_html( $team_subtitle ); ?>
      </p>
    </div>

    <div class="grid lg:grid-cols-3 gap-8 max-w-6xl mx-auto" data-stagger>
      <?php foreach ( $team_members as $i => $member ) : ?>
      <div class="bg-white rounded-3xl p-8 md:p-10 shadow-lg border border-gray-200 text-center" data-reveal style="--stagger-index:<?php echo (int) $i; ?>">
        <div class="w-32 h-32 mx-auto mb-6 rounded-full overflow-hidden bg-purple-50 border-4 border-white shadow-md">
          <?php if ( isset( $member['default_photo_url'] ) && (int) $member['photo'] <= 0 ) : ?>
            <img src="<?php echo esc_url( $member['default_photo_url'] ); ?>" alt="<?php echo esc_attr( $member['name'] ); ?>" width="592" height="592" class="w-full h-full object-cover" loading="lazy" decoding="async" />
          <?php elseif ( $member['photo'] !== null && $member['photo'] !== '' ) : ?>
            <?php echo wp_get_attachment_image( $member['photo'], 'medium', false, array(
                'class' => 'w-full h-full object-cover',
                'alt'   => esc_attr( $member['name'] ),
            ) ); ?>
          <?php else : ?>
            <!-- AWAITING PHOTO FROM CLIENT -->
            <svg class="w-full h-full text-purple-200" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
            </svg>
          <?php endif; ?>
        </div>
        <h3 class="text-2xl font-serif text-gray-900 mb-2"><?php echo esc_html( $member['name'] ); ?></h3>
        <p class="text-base text-gray-600 mb-4"><?php echo esc_html( $member['role'] ); ?></p>
        <?php
        // A bare register address lands on a search page. Link straight to the
        // pharmacist's own entry instead, so "every name here can be checked" is one click.
        $gphc_link = (string) $member['gphc_url'];
        if ( $member['gphc_number'] !== '' && ( $gphc_link === '' || preg_match( '#/registers/pharmacist/?$#', $gphc_link ) ) ) {
            $gphc_link = 'https://www.pharmacyregulation.org/registers/pharmacist/' . rawurlencode( (string) $member['gphc_number'] );
        }
        ?>
        <a href="<?php echo esc_url( $gphc_link ); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-semibold text-purple-600 hover:text-purple-700 transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
          GPhC: <?php echo esc_html( $member['gphc_number'] ); ?>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Section 5: Why choose us — the standard of care, in the Health Hub's warm style.
     The team (the people) is the section above; this one is the promise. -->
<?php
$why_eyebrow  = ah_field( 'stats_eyebrow', 'Why Choose Us' );
$why_title    = ah_field( 'stats_title', 'Care from Registered Pharmacists' );
$why_cta_text = ah_field( 'stats_cta_text', 'Start your assessment' );
$why_cta_url  = ah_field( 'stats_cta_url', '' );
if ( $why_cta_url === null || $why_cta_url === '' ) {
    $why_cta_url = ah_booking_url();
}
// The supplying pharmacy and its premises number (footer settings). The link
// opens the pharmacy's own entry on the GPhC register, not a search page.
$why_pharmacy = trim( (string) ah_option( 'supplying_pharmacy_name', '' ) );
$why_premises = trim( (string) ah_option( 'gphc_number', '' ) );
$why_register = $why_premises
    ? 'https://www.pharmacyregulation.org/registers/pharmacy/' . rawurlencode( $why_premises )
    : ah_field( 'stats_gphc_url', 'https://www.pharmacyregulation.org/registers/pharmacy' );

// Four promises. Each is something the service does, not a claim about results.
$why_promises = array(
    array(
        'title' => 'A pharmacist reads every assessment.',
        'text'  => 'Not an algorithm. One of our pharmacist prescribers reviews your answers in full before anything is prescribed.',
        'tint'  => array( '#efecfb', '#dad4f3' ), 'ink' => '#5f56a6',
        'mark'  => '<path d="M7 3.5h7l4 4V20a.5.5 0 0 1-.5.5h-10.5a.5.5 0 0 1-.5-.5V4a.5.5 0 0 1 .5-.5Z"/><path d="M13.5 3.5V8h4.5M9.5 14.2l2 2 3.5-3.6"/>',
    ),
    array(
        'title' => 'Only charged if you’re approved.',
        'text'  => 'Your card is held, not charged, while we review. If treatment isn’t right for you, the hold is released in full.',
        'tint'  => array( '#fbefe2', '#f2d8bd' ), 'ink' => '#94562a',
        'mark'  => '<rect x="3" y="6" width="18" height="12.5" rx="2.5"/><path d="M3 10.5h18M7 15h3"/>',
    ),
    array(
        'title' => 'Every name here can be checked.',
        'text'  => 'Our pharmacy and each of our pharmacists are on the General Pharmaceutical Council’s public register. Look us up any time.',
        'tint'  => array( '#e8f4ed', '#cde6d7' ), 'ink' => '#2d6a4d',
        'mark'  => '<circle cx="10.5" cy="10.5" r="6.2"/><path d="M15.2 15.2 20 20M8 10.6l1.8 1.8 3.4-3.5"/>',
    ),
    array(
        'title' => 'Here between orders, too.',
        'text'  => 'Our team answers your questions by email, and the Health Hub has practical help from our pharmacists for the weeks in between.',
        'tint'  => array( '#fcebee', '#f3d1d8' ), 'ink' => '#9b4557',
        'mark'  => '<path d="M4.5 18.8V6.5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-9l-4 2.3Z"/><path d="M9 10.3h6M9 13.2h3.8"/>',
    ),
);
?>
<section class="hp-why pt-2 md:pt-4 pb-16 md:pb-24" style="background: #fdf8f3;">
  <div class="ah-container-wide">
    <div class="hp-why-panel relative overflow-hidden rounded-[32px] md:rounded-[40px] px-6 py-12 sm:px-10 md:px-14 md:py-16 lg:px-20 lg:py-20" data-reveal>
      <svg class="hp-why-watermark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-7.2-4.4-7.2-10.1A4.1 4.1 0 0 1 12 7.2a4.1 4.1 0 0 1 7.2 2.7C19.2 15.6 12 20 12 20Z"/></svg>

      <div class="relative grid lg:grid-cols-12 gap-12 lg:gap-16 items-start">
        <div class="lg:col-span-5">
          <p class="text-[11px] md:text-xs font-bold uppercase tracking-[0.28em] mb-5" style="color: #7d76ba;"><?php echo esc_html( $why_eyebrow ); ?></p>
          <h2 class="font-serif text-gray-900 leading-[1.04] tracking-[-0.025em] text-[2.4rem] md:text-[3.1rem] lg:text-[3.5rem] mb-8" style="text-wrap: balance;">
            <?php echo wp_kses_post( $why_title ); ?>
          </h2>

          <!-- Group proof point. Wording approved verbatim by the superintendent
               (AT Health Ltd), who holds the consultation records that evidence it.
               Deliberately not an ACF field: this is the site's only patient figure,
               and it must not be edited without fresh evidence and approval. -->
          <p class="hp-why-statement font-serif text-gray-800 text-[1.4rem] md:text-[1.7rem] leading-[1.3] tracking-[-0.01em] mb-10">Our pharmacist prescribers have supported over 1,000 patients across the AT Health group.</p>

          <a href="<?php echo esc_url( $why_cta_url ); ?>" class="inline-flex items-center justify-center gap-2.5 bg-purple-600 hover:bg-purple-700 text-white text-[15px] font-semibold px-8 py-4 rounded-xl transition-all hover-lift shadow-lg">
            <?php echo esc_html( $why_cta_text ); ?>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
          </a>
        </div>

        <ul class="lg:col-span-7 grid sm:grid-cols-2 gap-4 md:gap-5" data-stagger>
          <?php foreach ( $why_promises as $i => $promise ) : ?>
          <li class="hp-why-promise" data-reveal style="--stagger-index:<?php echo (int) $i; ?>; --hh-a:<?php echo esc_attr( $promise['tint'][0] ); ?>; --hh-b:<?php echo esc_attr( $promise['tint'][1] ); ?>; --hh-ink:<?php echo esc_attr( $promise['ink'] ); ?>;">
            <span class="hp-why-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><?php echo $promise['mark']; // phpcs:ignore WordPress.Security.EscapeOutput -- static markup above ?></svg></span>
            <h3 class="font-serif text-gray-900 text-[1.3rem] md:text-[1.4rem] leading-[1.2] tracking-[-0.01em] mt-5 mb-2.5"><?php echo esc_html( $promise['title'] ); ?></h3>
            <p class="text-[15px] text-gray-600 leading-[1.6]"><?php echo esc_html( $promise['text'] ); ?></p>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Credential: the supplying pharmacy, verifiable in one click -->
      <div class="hp-why-credential relative mt-12 md:mt-16 flex flex-col sm:flex-row sm:items-center gap-5 sm:gap-7 rounded-2xl px-6 py-5 md:px-8 md:py-6" data-reveal>
        <span class="hp-why-seal" aria-hidden="true">GPhC</span>
        <p class="text-[14px] md:text-[15px] text-gray-700 leading-[1.55] flex-1">
          <?php if ( $why_pharmacy ) : ?>
            Medicines are supplied by <strong class="font-semibold text-gray-900"><?php echo esc_html( $why_pharmacy ); ?></strong>, a pharmacy registered with the General Pharmaceutical Council<?php echo $why_premises ? esc_html( ', premises no. ' . $why_premises ) : ''; ?>.
          <?php else : ?>
            A pharmacy registered with the General Pharmaceutical Council.
          <?php endif; ?>
        </p>
        <a href="<?php echo esc_url( $why_register ); ?>" target="_blank" rel="noopener noreferrer" class="hp-why-verify inline-flex items-center gap-2 text-[14px] font-semibold whitespace-nowrap">
          Check the register
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M7 17 17 7M9 7h8v8"/></svg>
          <span class="sr-only">(opens the GPhC register in a new tab)</span>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Section 6: Health Hub — a magazine feature that picks its own stories
     (template-parts/section-health-hub.php). Hidden until there are enough to fill it. -->
<?php get_template_part( 'template-parts/section', 'health-hub' ); ?>

<?php
// Section 7: FAQ
get_template_part( 'template-parts/section', 'faq' );

// Section 8: Start Your Journey CTA — points to /eligibility/ via ah_booking_url(), confirmed correct
get_template_part( 'template-parts/section', 'cta' );
?>

<!-- Phase 2 complete: Weight Loss Calculator and Treatment Showcase moved to page-treatments.php -->

<?php
get_footer();
?>

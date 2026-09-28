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
        <a href="<?php echo esc_url( $member['gphc_url'] ); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-semibold text-purple-600 hover:text-purple-700 transition-colors">
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

<!-- Section 5: Safe and Secure (GPhC backed) -->
<!-- PHASE 3: Review and refine to focus on GPhC backing -->
<section class="relative py-16 md:py-20" style="background: #fdf8f3;">
  <div class="ah-container-wide">
    <div class="text-center mb-12 section-header">
      <div class="flex items-center justify-center gap-3 mb-4">
        <div class="w-1 h-8 bg-purple-600 rounded-full"></div>
        <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider"><?php echo esc_html( ah_field( 'stats_eyebrow', 'Why Choose Us' ) ); ?></p>
      </div>
      <h2 class="text-3xl md:text-4xl lg:text-5xl text-gray-800 font-serif leading-[1.1]">
        <?php echo wp_kses_post( ah_field( 'stats_title', 'Care from Registered Pharmacists' ) ); ?>
      </h2>
    </div>

    <?php
    $stats_badges_lbl  = ah_field( 'stats_badges_label', 'Fully regulated' );
    $stats_gphc_url    = ah_field( 'stats_gphc_url', 'https://www.pharmacyregulation.org/registers/pharmacy' );
    $stats_cta_text    = ah_field( 'stats_cta_text', 'Start your assessment' );
    $stats_cta_url     = ah_field( 'stats_cta_url', '' );
    if ( $stats_cta_url === null || $stats_cta_url === '' ) {
        $stats_cta_url = ah_booking_url();
    }
    ?>

    <!-- Group proof point. Wording approved verbatim by the superintendent
         (AT Health Ltd), who holds the consultation records that evidence it.
         Deliberately not an ACF field: this is the site's only patient figure,
         and it must not be edited without fresh evidence and approval. -->
    <div class="hp-stat-card-v2 hp-proof-card max-w-2xl mx-auto mb-12" data-reveal>
      <div class="hp-stat-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
      </div>
      <p class="text-lg md:text-xl text-gray-800 leading-relaxed">Our pharmacist prescribers have supported over 1,000 patients across the AT Health group.</p>
    </div>

    <!-- Trust badges strip -->
    <div class="hp-trust-strip max-w-3xl mx-auto mb-10 md:mb-12" data-reveal>
      <span class="hp-trust-strip-label"><?php echo esc_html( $stats_badges_lbl ); ?></span>
      <div class="hp-trust-strip-logos">
        <a href="<?php echo esc_url( $stats_gphc_url ); ?>" target="_blank" rel="noopener noreferrer" class="hp-trust-badge">
          <span class="hp-trust-badge-mark">GPhC</span>
          <span class="hp-trust-badge-sub">Registered Pharmacy</span>
        </a>
      </div>
    </div>

    <!-- CTA -->
    <div class="text-center" data-reveal>
      <a href="<?php echo esc_url( $stats_cta_url ); ?>" class="inline-flex items-center justify-center gap-2.5 bg-purple-600 hover:bg-purple-700 text-white text-[15px] font-semibold px-9 py-4 rounded-xl transition-all hover-lift shadow-lg">
        <?php echo esc_html( $stats_cta_text ); ?>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
      </a>
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

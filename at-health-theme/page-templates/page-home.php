<?php
/**
 * Template Name: Home
 * Description: AT Health pharmacy homepage. Section order: Hero, How It Works, Google Reviews, Know Your Team, Safe and Secure, Health Hub, FAQs, CTA.
 */
get_header();

// Section 1: Hero
// PHASE 2: Review hero messaging - ensure pharmacy-focused not weight loss-focused
get_template_part( 'template-parts/section', 'hero' );

// Section 2: How It Works
get_template_part( 'template-parts/section', 'how-it-works' );
?>

<!-- Section 3: Google Reviews -->
<!-- PHASE 3: Replace with embedded Google Reviews widget -->
<section class="relative py-16 md:py-20 overflow-hidden" style="background: #f7f4f9;">
  <div class="ah-container-wide">
    <div class="text-center mb-12">
      <div class="inline-flex items-center gap-2 bg-white border border-amber-200 rounded-full px-5 py-2 shadow-sm mb-6">
        <span class="text-amber-500 text-sm">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
        <span class="text-sm font-semibold text-gray-700"><?php echo esc_html( ah_field( 'testimonials_badge', 'Rated Excellent 4.9/5 by 10,000+ patients' ) ); ?></span>
      </div>
      <h2 class="text-3xl md:text-4xl lg:text-5xl font-serif text-gray-900 mb-4">
        <?php echo wp_kses_post( ah_field( 'testimonials_title', 'Life-Changing Results' ) ); ?>
      </h2>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto" data-stagger>
      <?php
      $default_testimonials = array(
          array( 'name' => 'Sophie Chaudhry', 'label' => 'Verified Patient', 'text' => "I no longer struggle with weight loss. It's changed my entire relationship with food." ),
          array( 'name' => 'Stephen Matthews', 'label' => 'Verified Patient · Lost 19kg', 'text' => 'I have so far lost 3 stone (19kg). The support has been incredible.' ),
          array( 'name' => 'Marie Clayton', 'label' => 'Verified Patient', 'text' => "I've got my life back. I feel confident, energetic, and happy again." ),
          array( 'name' => 'Tanta Stefanescu', 'label' => 'Verified Patient · Lost 11kg', 'text' => 'I have lost almost 11kg and feel incredible. The best decision I\'ve made.' ),
      );

      $testimonials = ah_field( 'testimonials_items', '' );
      if ( ! is_array( $testimonials ) || count( $testimonials ) === 0 ) {
          $testimonials = $default_testimonials;
      }

      foreach ( $testimonials as $i => $t ) :
          $name  = isset( $t['name'] ) ? $t['name'] : '';
          $label = isset( $t['label'] ) ? $t['label'] : 'Verified Patient';
          $text  = isset( $t['text'] ) ? $t['text'] : '';
      ?>
      <div class="hp-testimonial" data-reveal style="--stagger-index:<?php echo (int) $i; ?>">
        <div class="flex gap-0.5 mb-4">
          <?php for ( $s = 0; $s < 5; $s++ ) : ?>
          <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
          <?php endfor; ?>
        </div>
        <p class="text-gray-700 text-[15px] leading-relaxed mb-6">"<?php echo esc_html( $text ); ?>"</p>
        <div>
          <p class="text-sm font-semibold text-gray-900"><?php echo esc_html( $name ); ?></p>
          <p class="text-xs text-gray-500"><?php echo esc_html( $label ); ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

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

    <div class="grid md:grid-cols-2 gap-10 max-w-4xl mx-auto" data-stagger>
      <?php foreach ( $team_members as $i => $member ) : ?>
      <div class="bg-white rounded-3xl p-8 md:p-10 shadow-lg border border-gray-200 text-center" data-reveal style="--stagger-index:<?php echo (int) $i; ?>">
        <div class="w-32 h-32 mx-auto mb-6 rounded-full overflow-hidden bg-purple-50 border-4 border-white shadow-md">
          <?php if ( $member['photo'] !== null && $member['photo'] !== '' ) : ?>
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
        <p class="text-purple-600 text-xs md:text-sm font-bold uppercase tracking-wider"><?php echo esc_html( ah_field( 'stats_eyebrow', 'Why Patients Choose Us' ) ); ?></p>
      </div>
      <h2 class="text-3xl md:text-4xl lg:text-5xl text-gray-800 font-serif leading-[1.1]">
        <?php echo wp_kses_post( ah_field( 'stats_title', 'The Numbers Speak for Themselves' ) ); ?>
      </h2>
    </div>

    <?php
    $stats_label_1     = ah_field( 'stats_label_1', 'Years combined clinical experience' );
    $stats_label_2     = ah_field( 'stats_label_2', 'Patients treated since 2020' );
    $stats_label_3     = ah_field( 'stats_label_3', 'Verified patient rating' );
    $stats_label_4     = ah_field( 'stats_label_4', 'Tracked UK delivery' );
    $stats_badges_lbl  = ah_field( 'stats_badges_label', 'Fully regulated' );
    $stats_gphc_url    = ah_field( 'stats_gphc_url', 'https://www.pharmacyregulation.org/registers/pharmacy' );
    $stats_mhra_url    = ah_field( 'stats_mhra_url', 'https://products.mhra.gov.uk/' );
    $stats_cta_text    = ah_field( 'stats_cta_text', 'Start your assessment' );
    $stats_cta_url     = ah_field( 'stats_cta_url', '' );
    if ( $stats_cta_url === null || $stats_cta_url === '' ) {
        $stats_cta_url = ah_booking_url();
    }
    ?>

    <!-- Stat cards: 4-up grid (2 cols mobile, 4 cols desktop) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 md:gap-6 max-w-5xl mx-auto mb-12" data-stagger>
      <!-- Stat 1: Years -->
      <div class="hp-stat-card-v2" data-reveal style="--stagger-index:0">
        <div class="hp-stat-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        <div class="hp-stat-number"><span data-count="30" data-suffix="+">0</span></div>
        <p class="text-sm text-gray-600 leading-snug"><?php echo esc_html( $stats_label_1 ); ?></p>
      </div>
      <!-- Stat 2: Patients -->
      <div class="hp-stat-card-v2" data-reveal style="--stagger-index:1">
        <div class="hp-stat-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="hp-stat-number"><span data-count="10000" data-suffix="+">0</span></div>
        <p class="text-sm text-gray-600 leading-snug"><?php echo esc_html( $stats_label_2 ); ?></p>
      </div>
      <!-- Stat 3: Rating -->
      <div class="hp-stat-card-v2" data-reveal style="--stagger-index:2">
        <div class="hp-stat-icon">
          <svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="0.6" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
        </div>
        <div class="hp-stat-number">4.9</div>
        <p class="text-sm text-gray-600 leading-snug"><?php echo esc_html( $stats_label_3 ); ?></p>
      </div>
      <!-- Stat 4: Delivery -->
      <div class="hp-stat-card-v2" data-reveal style="--stagger-index:3">
        <div class="hp-stat-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
        </div>
        <div class="hp-stat-number"><span data-count="48" data-suffix="h">0h</span></div>
        <p class="text-sm text-gray-600 leading-snug"><?php echo esc_html( $stats_label_4 ); ?></p>
      </div>
    </div>

    <!-- Trust badges strip -->
    <div class="hp-trust-strip max-w-3xl mx-auto mb-10 md:mb-12" data-reveal>
      <span class="hp-trust-strip-label"><?php echo esc_html( $stats_badges_lbl ); ?></span>
      <div class="hp-trust-strip-logos">
        <a href="<?php echo esc_url( $stats_gphc_url ); ?>" target="_blank" rel="noopener noreferrer" class="hp-trust-badge">
          <span class="hp-trust-badge-mark">GPhC</span>
          <span class="hp-trust-badge-sub">Registered Pharmacy</span>
        </a>
        <a href="<?php echo esc_url( $stats_mhra_url ); ?>" target="_blank" rel="noopener noreferrer" class="hp-trust-badge">
          <span class="hp-trust-badge-mark">MHRA</span>
          <span class="hp-trust-badge-sub">Regulated</span>
        </a>
      </div>
    </div>

    <!-- CTA -->
    <div class="text-center" data-reveal>
      <a href="<?php echo esc_url( $stats_cta_url ); ?>" class="inline-flex items-center justify-center gap-2.5 bg-gray-900 hover:bg-gray-800 text-white text-[15px] font-semibold px-9 py-4 rounded-xl transition-all hover-lift shadow-lg">
        <?php echo esc_html( $stats_cta_text ); ?>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
      </a>
    </div>
  </div>
</section>

<!-- Section 6: Health Hub Snapshot — driven by ACF post pickers -->
<?php
$hh_eyebrow      = ah_field( 'health_hub_eyebrow', 'HEALTH HUB' );
$hh_heading      = ah_field( 'health_hub_heading', 'Know More. Feel Better.' );
$hh_subheading   = ah_field( 'health_hub_subheading', 'Honest health guidance from our pharmacist prescribers — written for real people, not medical textbooks.' );

// Normalise a picked post ID into the shape the cards expect.
$hh_post_card = function ( $post_id, $read_time = '' ) {
    if ( ! $post_id || ! get_post( $post_id ) ) {
        return null;
    }
    $cats     = get_the_category( $post_id );
    $category = '';
    if ( $cats ) {
        foreach ( $cats as $c ) {
            if ( strtolower( $c->slug ) !== 'uncategorized' && strtolower( $c->slug ) !== 'uncategorised' ) {
                $category = $c->name;
                break;
            }
        }
        if ( $category === '' ) { $category = $cats[0]->name; }
    }
    return array(
        'title'     => get_the_title( $post_id ),
        'excerpt'   => wp_strip_all_tags( get_the_excerpt( $post_id ) ),
        'image'     => get_post_thumbnail_id( $post_id ),
        'url'       => get_permalink( $post_id ),
        'category'  => $category,
        'read_time' => $read_time,
    );
};

$hh_hero_id  = ah_field( 'health_hub_hero_post', 0 );
$hh_hero     = $hh_post_card( $hh_hero_id, ah_field( 'health_hub_hero_read_time', '5 min read' ) );

$hh_cards_raw = ah_field( 'health_hub_cards', array() );
$hh_cards     = array();
if ( is_array( $hh_cards_raw ) ) {
    foreach ( $hh_cards_raw as $row ) {
        $pid   = isset( $row['card_post'] ) ? $row['card_post'] : 0;
        $rtime = isset( $row['card_read_time'] ) ? $row['card_read_time'] : '';
        $card  = $hh_post_card( $pid, $rtime );
        if ( $card ) { $hh_cards[] = $card; }
    }
}

$hh_sixth_id = ah_field( 'health_hub_sixth_post', 0 );
$hh_sixth    = $hh_post_card( $hh_sixth_id, ah_field( 'health_hub_sixth_read_time', '5 min read' ) );

$hh_cta_heading     = ah_field( 'health_hub_cta_heading', 'Ready to take the first step?' );
$hh_cta_subtext     = ah_field( 'health_hub_cta_subtext', 'Answer a few quick questions and find out which treatment is right for you.' );
$hh_cta_button_text = ah_field( 'health_hub_cta_button_text', 'Check Your Eligibility' );
$hh_cta_button_url  = ah_field( 'health_hub_cta_button_url', '' );
if ( $hh_cta_button_url === null || $hh_cta_button_url === '' ) {
    $hh_cta_button_url = ah_booking_url();
}
$hh_explore_url = ah_field( 'health_hub_explore_url', '/health-hub/' );
?>
<section class="hh-home-section relative py-[60px] md:py-[100px]" style="background: #fdf8f3;">
  <div class="ah-container-wide">
    <!-- Section header — left aligned, editorial -->
    <div class="max-w-3xl mb-12 md:mb-16" data-reveal>
      <p class="text-[11px] md:text-xs font-bold uppercase tracking-[0.28em] mb-5" style="color: #8e88d0;">
        <?php echo esc_html( $hh_eyebrow ); ?>
      </p>
      <h2 class="font-serif text-gray-900 leading-[1.05] tracking-[-0.025em] mb-5 text-[2.5rem] md:text-[3.5rem] lg:text-[4rem]">
        <?php echo esc_html( $hh_heading ); ?>
      </h2>
      <p class="text-base md:text-lg text-gray-600 leading-[1.6] max-w-xl">
        <?php echo esc_html( $hh_subheading ); ?>
      </p>
    </div>

    <!-- ROW 1: Hero article — pulled from the picked post -->
    <?php if ( $hh_hero ) :
        $hero_has_image = ( $hh_hero['image'] !== null && $hh_hero['image'] !== '' );
    ?>
    <a href="<?php echo esc_url( $hh_hero['url'] ); ?>" class="hh-hero-article block rounded-[28px] mb-10 md:mb-12 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl group overflow-hidden" style="background: #8e88d0;" data-reveal>
      <div class="grid <?php echo $hero_has_image ? 'lg:grid-cols-2' : 'grid-cols-1'; ?> gap-0 items-stretch" style="min-height: 320px;">
        <!-- Text column -->
        <div class="flex flex-col justify-between gap-8 p-10 md:p-14 lg:p-16 min-h-[260px] order-2 lg:order-1">
          <div>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 mb-6">
              <?php if ( $hh_hero['category'] !== '' ) : ?>
              <span class="text-[11px] font-bold uppercase tracking-[0.2em]" style="color: #fdf8f4;">
                <?php echo esc_html( $hh_hero['category'] ); ?>
              </span>
              <?php endif; ?>
              <?php if ( $hh_hero['read_time'] !== '' ) : ?>
              <span class="text-[11px] uppercase tracking-[0.2em]" style="color: rgba(253,248,244,0.65);">
                <?php echo esc_html( $hh_hero['read_time'] ); ?>
              </span>
              <?php endif; ?>
            </div>
            <h3 class="font-serif leading-[1.05] tracking-[-0.02em] mb-5 text-[2rem] md:text-[2.75rem] lg:text-[3.25rem]" style="color: #fdf8f4;">
              <?php echo esc_html( $hh_hero['title'] ); ?>
            </h3>
            <?php if ( $hh_hero['excerpt'] !== '' ) : ?>
            <p class="text-base md:text-lg leading-[1.6]" style="color: rgba(253,248,244,0.85);">
              <?php echo esc_html( $hh_hero['excerpt'] ); ?>
            </p>
            <?php endif; ?>
          </div>
          <div class="inline-flex items-center gap-2 text-[15px] font-semibold transition-transform duration-300 group-hover:translate-x-1" style="color: #fdf8f4;">
            Read Article
            <span aria-hidden="true">&rarr;</span>
          </div>
        </div>
        <?php if ( $hero_has_image ) : ?>
        <div class="relative order-1 lg:order-2 min-h-[240px] lg:min-h-full">
          <?php echo wp_get_attachment_image( $hh_hero['image'], 'large', false, array(
              'class' => 'absolute inset-0 w-full h-full object-cover',
              'alt'   => esc_attr( $hh_hero['title'] ),
          ) ); ?>
        </div>
        <?php endif; ?>
      </div>
    </a>
    <?php endif; ?>

    <!-- ROW 2: Four supporting article cards -->
    <?php if ( ! empty( $hh_cards ) ) : ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8 mb-10 md:mb-12" data-stagger>
      <?php foreach ( $hh_cards as $i => $card ) : ?>
      <a href="<?php echo esc_url( $card['url'] ); ?>" class="hh-card group flex flex-col pb-6 transition-all duration-300" style="border-bottom: 1px solid rgba(142,136,208,0.2); --stagger-index:<?php echo (int) $i; ?>;" data-reveal>
        <?php if ( $card['image'] ) : ?>
        <div class="relative aspect-[4/3] mb-5 rounded-2xl overflow-hidden bg-gray-100">
          <?php echo wp_get_attachment_image( $card['image'], 'health-hub-card', false, array(
              'class' => 'absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105',
              'alt'   => esc_attr( $card['title'] ),
          ) ); ?>
        </div>
        <?php endif; ?>
        <?php if ( $card['category'] !== '' ) : ?>
        <span class="text-[11px] font-bold uppercase tracking-[0.1em] mb-4" style="color: #8e88d0;">
          <?php echo esc_html( $card['category'] ); ?>
        </span>
        <?php endif; ?>
        <h3 class="font-serif text-gray-900 text-[1.25rem] md:text-[1.35rem] leading-[1.25] tracking-[-0.01em] mb-3 transition-colors duration-300 group-hover:text-[#8e88d0]">
          <?php echo esc_html( $card['title'] ); ?>
        </h3>
        <?php if ( $card['excerpt'] !== '' ) : ?>
        <p class="text-[15px] text-gray-600 leading-[1.6] mb-4 flex-grow">
          <?php echo esc_html( $card['excerpt'] ); ?>
        </p>
        <?php endif; ?>
        <?php if ( $card['read_time'] !== '' ) : ?>
        <p class="text-xs text-gray-500 mb-5"><?php echo esc_html( $card['read_time'] ); ?></p>
        <?php endif; ?>
        <span class="inline-flex items-center gap-1.5 text-sm font-semibold transition-transform duration-300 group-hover:translate-x-1" style="color: #8e88d0;">
          Read
          <span aria-hidden="true">&rarr;</span>
        </span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ROW 3: Sixth article (60%) + CTA panel (40%) -->
    <div class="grid grid-cols-1 <?php echo $hh_sixth ? 'lg:grid-cols-5' : ''; ?> gap-6 md:gap-8" data-reveal>
      <?php if ( $hh_sixth ) : ?>
      <a href="<?php echo esc_url( $hh_sixth['url'] ); ?>" class="hh-card-large group lg:col-span-3 flex flex-col justify-between pb-8 transition-all duration-300" style="border-bottom: 1px solid rgba(142,136,208,0.2);">
        <div>
          <?php if ( $hh_sixth['image'] ) : ?>
          <div class="relative aspect-[16/9] mb-6 rounded-2xl overflow-hidden bg-gray-100">
            <?php echo wp_get_attachment_image( $hh_sixth['image'], 'large', false, array(
                'class' => 'absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105',
                'alt'   => esc_attr( $hh_sixth['title'] ),
            ) ); ?>
          </div>
          <?php endif; ?>
          <div class="flex flex-wrap items-center gap-x-5 gap-y-2 mb-5">
            <?php if ( $hh_sixth['category'] !== '' ) : ?>
            <span class="text-[11px] font-bold uppercase tracking-[0.1em]" style="color: #8e88d0;">
              <?php echo esc_html( $hh_sixth['category'] ); ?>
            </span>
            <?php endif; ?>
            <?php if ( $hh_sixth['read_time'] !== '' ) : ?>
            <span class="text-xs text-gray-500">
              <?php echo esc_html( $hh_sixth['read_time'] ); ?>
            </span>
            <?php endif; ?>
          </div>
          <h3 class="font-serif text-gray-900 text-[1.75rem] md:text-[2.25rem] leading-[1.1] tracking-[-0.015em] mb-4 transition-colors duration-300 group-hover:text-[#8e88d0]">
            <?php echo esc_html( $hh_sixth['title'] ); ?>
          </h3>
          <?php if ( $hh_sixth['excerpt'] !== '' ) : ?>
          <p class="text-base md:text-lg text-gray-600 leading-[1.6] max-w-xl">
            <?php echo esc_html( $hh_sixth['excerpt'] ); ?>
          </p>
          <?php endif; ?>
        </div>
        <span class="inline-flex items-center gap-1.5 text-[15px] font-semibold mt-6 transition-transform duration-300 group-hover:translate-x-1" style="color: #8e88d0;">
          Read Article
          <span aria-hidden="true">&rarr;</span>
        </span>
      </a>
      <?php endif; ?>

      <!-- CTA panel -->
      <div class="<?php echo $hh_sixth ? 'lg:col-span-2' : ''; ?> rounded-[28px] p-10 flex flex-col justify-center" style="background: #8e88d0;">
        <h3 class="font-serif text-[1.75rem] md:text-[2rem] leading-[1.15] tracking-[-0.015em] mb-4" style="color: #fdf8f4;">
          <?php echo esc_html( $hh_cta_heading ); ?>
        </h3>
        <p class="text-base leading-[1.6] mb-7" style="color: rgba(253,248,244,0.8);">
          <?php echo esc_html( $hh_cta_subtext ); ?>
        </p>
        <a href="<?php echo esc_url( $hh_cta_button_url ); ?>" class="inline-flex items-center justify-center gap-2 text-[15px] font-semibold px-7 py-4 rounded-xl transition-all duration-300 hover:opacity-90 self-start" style="background: #fdf8f4; color: #8e88d0;">
          <?php echo esc_html( $hh_cta_button_text ); ?>
          <span aria-hidden="true">&rarr;</span>
        </a>
      </div>
    </div>

    <!-- Section footer: Explore All link -->
    <div class="text-center mt-12 md:mt-16">
      <a href="<?php echo esc_url( $hh_explore_url ); ?>" class="hh-explore-link inline-flex items-center gap-2 text-[15px] font-semibold transition-all duration-300" style="color: #8e88d0;">
        Explore All Articles
        <span aria-hidden="true">&rarr;</span>
      </a>
    </div>
  </div>
</section>

<style>
  .hh-explore-link:hover { text-decoration: underline; text-underline-offset: 4px; }
  .hh-card:hover, .hh-card-large:hover { border-bottom-color: rgba(142,136,208,0.5) !important; }
</style>

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

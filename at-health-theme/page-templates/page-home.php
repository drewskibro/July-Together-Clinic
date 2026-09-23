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

<!-- Section 6: Health Hub Snapshot — driven by ACF post pickers -->
<?php
$hh_eyebrow      = ah_field( 'health_hub_eyebrow', 'HEALTH HUB' );
$hh_heading      = ah_field( 'health_hub_heading', 'Know More. Feel Better.' );
$hh_subheading   = ah_field( 'health_hub_subheading', 'Honest health guidance from our pharmacist prescribers — written for real people, not medical textbooks.' );

// Normalise a picked post ID into the shape the cards expect.
// $words caps the dek: WordPress's raw excerpt is 55 words ending in "[...]",
// which is a blog-archive convention, not a magazine one. Cards get a
// two-line dek; the feature and the large sixth card get a little more.
// Only published posts: get_post() also returns drafts, private and trashed
// posts, so an article taken off the site would otherwise keep its card here
// (title and excerpt visible, link 404ing). Unpublishing is enough to remove it.
$hh_post_card = function ( $post_id, $read_time = '', $words = 18 ) {
    if ( ! $post_id || get_post_status( $post_id ) !== 'publish' ) {
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
    $excerpt = wp_strip_all_tags( get_the_excerpt( $post_id ) );
    $excerpt = trim( str_replace( array( '[...]', '[&hellip;]', '&hellip;', '…' ), '', $excerpt ) );
    $excerpt = wp_trim_words( $excerpt, (int) $words, '…' ); // literal ellipsis: this string is passed through esc_html()
    return array(
        'title'     => get_the_title( $post_id ),
        'excerpt'   => $excerpt,
        'image'     => get_post_thumbnail_id( $post_id ),
        'url'       => get_permalink( $post_id ),
        'category'  => $category,
        'read_time' => $read_time,
    );
};

$hh_hero_id  = ah_field( 'health_hub_hero_post', 0 );
$hh_hero     = $hh_post_card( $hh_hero_id, ah_field( 'health_hub_hero_read_time', '5 min read' ), 30 );

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
$hh_sixth    = $hh_post_card( $hh_sixth_id, ah_field( 'health_hub_sixth_read_time', '5 min read' ), 28 );

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

    <!-- ROW 2: Supporting article cards — grid width follows how many were picked -->
    <?php if ( ! empty( $hh_cards ) ) :
        $hh_grid_cols = array(
            1 => 'grid-cols-1 max-w-md',
            2 => 'grid-cols-1 sm:grid-cols-2',
            3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
            4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
        );
        $hh_grid = $hh_grid_cols[ min( 4, max( 1, count( $hh_cards ) ) ) ];
    ?>
    <div class="grid <?php echo esc_attr( $hh_grid ); ?> gap-x-8 gap-y-12 md:gap-x-10 mb-12 md:mb-16" data-stagger>
      <?php foreach ( $hh_cards as $i => $card ) : ?>
      <a href="<?php echo esc_url( $card['url'] ); ?>" class="hh-card group flex flex-col" style="--stagger-index:<?php echo (int) $i; ?>;" data-reveal>
        <?php if ( $card['image'] ) : ?>
        <div class="relative aspect-[4/3] mb-6 rounded-2xl overflow-hidden bg-gray-100">
          <?php echo wp_get_attachment_image( $card['image'], 'health-hub-card', false, array(
              'class' => 'absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.04]',
              'alt'   => esc_attr( $card['title'] ),
          ) ); ?>
        </div>
        <?php endif; ?>

        <!-- Meta line: category · read time, on one line above the title -->
        <?php if ( $card['category'] !== '' || $card['read_time'] !== '' ) : ?>
        <div class="flex items-center gap-x-2.5 mb-3 text-[11px] uppercase tracking-[0.14em]">
          <?php if ( $card['category'] !== '' ) : ?>
          <span class="font-bold" style="color: #7d76ba;"><?php echo esc_html( $card['category'] ); ?></span>
          <?php endif; ?>
          <?php if ( $card['category'] !== '' && $card['read_time'] !== '' ) : ?>
          <span aria-hidden="true" class="text-gray-300">&middot;</span>
          <?php endif; ?>
          <?php if ( $card['read_time'] !== '' ) : ?>
          <span class="font-medium text-gray-500"><?php echo esc_html( $card['read_time'] ); ?></span>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <h3 class="font-serif text-gray-900 text-[1.4rem] md:text-[1.55rem] leading-[1.18] tracking-[-0.015em] mb-3 transition-colors duration-300 group-hover:text-[#7d76ba]" style="text-wrap: balance;">
          <?php echo esc_html( $card['title'] ); ?>
        </h3>

        <?php if ( $card['excerpt'] !== '' ) : ?>
        <p class="text-[15px] text-gray-600 leading-[1.6] line-clamp-2 mb-5">
          <?php echo esc_html( $card['excerpt'] ); ?>
        </p>
        <?php endif; ?>

        <span class="mt-auto inline-flex items-center gap-1.5 text-[13px] font-semibold text-gray-900 transition-transform duration-300 group-hover:translate-x-1">
          Read article
          <span aria-hidden="true" style="color: #8e88d0;">&rarr;</span>
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

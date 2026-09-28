<?php
/**
 * Template Name: Health Hub
 * Description: The Health Hub magazine — masthead, sections, lead story and stories.
 *
 * Run as a magazine rather than a blog (see docs/health-hub.md). The newest
 * story leads, or one "stuck to the top" in WordPress; ?section= narrows to
 * one of the five sections. Cards render through inc/health-hub.php so a
 * story looks the same here, on the home page and under articles.
 */
get_header();

$hh_sections = ah_hh_sections();

// ?section= filters to a section. The page's older ?category= links still work.
$hh_current = '';
foreach ( array( 'section', 'category' ) as $hh_param ) {
    if ( isset( $_GET[ $hh_param ] ) ) {
        $hh_current = sanitize_title( wp_unslash( $_GET[ $hh_param ] ) );
        break;
    }
}
$hh_section = ( $hh_current !== '' && isset( $hh_sections[ $hh_current ] ) ) ? $hh_sections[ $hh_current ] : null;
if ( ! $hh_section ) {
    $hh_current = '';
}

// Static pages paginate on "page"; archives on "paged".
$hh_paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );

// The lead is excluded from the grid on every page, so pagination stays consistent.
$hh_front = ah_hh_front( 0, $hh_current );
$hh_lead  = $hh_front['lead'];

$hh_args = array(
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => 9,
    'paged'               => $hh_paged,
    'ignore_sticky_posts' => true,
    'post__not_in'        => $hh_lead ? array( $hh_lead['id'] ) : array(),
);
if ( $hh_current ) {
    $hh_args['category_name'] = $hh_current;
}
$hh_query   = new WP_Query( $hh_args );
$hh_stories = array_values( array_filter( array_map( 'ah_hh_story', $hh_query->posts ) ) );
?>

<!-- Masthead -->
<section class="hh-masthead pt-14 md:pt-20 pb-10 md:pb-12" style="background: #fdf8f3;">
  <div class="ah-container-wide">
    <div class="max-w-3xl" data-reveal>
      <p class="text-[11px] md:text-xs font-bold uppercase tracking-[0.28em] mb-5" style="color: #8e88d0;">The Together Clinic Health Hub</p>
      <?php if ( $hh_section ) : ?>
        <h1 class="font-serif text-gray-900 leading-[1.04] tracking-[-0.025em] text-[2.6rem] md:text-[3.6rem] lg:text-[4.2rem] mb-5"><?php echo esc_html( $hh_section['name'] ); ?></h1>
        <p class="text-base md:text-lg text-gray-600 leading-[1.6] max-w-xl"><?php echo esc_html( $hh_section['intro'] ); ?></p>
      <?php else : ?>
        <h1 class="font-serif text-gray-900 leading-[1.04] tracking-[-0.025em] text-[2.6rem] md:text-[3.6rem] lg:text-[4.2rem] mb-5" style="text-wrap: balance;">Everything that happens between the weigh-ins.</h1>
        <p class="text-base md:text-lg text-gray-600 leading-[1.6] max-w-xl">Recipes that cost less, walks that feel good, and clear answers from our pharmacists. Written for the week you&rsquo;re having, not the one you planned.</p>
      <?php endif; ?>
    </div>
    <div class="mt-9"><?php echo ah_hh_section_pills( $hh_current, true ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
  </div>
</section>

<?php if ( ! $hh_lead && ! $hh_stories ) : ?>
<!-- Nothing published yet -->
<section class="py-20 md:py-24" style="background: #fdf8f3;">
  <div class="ah-container text-center max-w-xl mx-auto">
    <p class="font-serif text-gray-900 text-[1.75rem] md:text-[2rem] leading-[1.2] mb-4">The first stories are on their way.</p>
    <p class="text-gray-600 leading-[1.6] mb-8"><?php echo $hh_section ? 'Nothing in this section yet &mdash; have a look at the rest of the Hub in the meantime.' : 'Our pharmacists are putting the finishing touches to them now.'; ?></p>
    <?php if ( $hh_section ) : ?>
    <a href="<?php echo esc_url( ah_hh_url() ); ?>" class="hh-browse-link inline-flex items-center gap-2 text-[15px] font-semibold" style="color: #7d76ba;">All stories <span aria-hidden="true">&rarr;</span></a>
    <?php endif; ?>
  </div>
</section>
<?php else : ?>

  <?php if ( $hh_lead && $hh_paged === 1 && ! $hh_lead['image'] ) : ?>
  <!-- Lead story, no photograph yet: full-width feature panel -->
  <section class="pb-14 md:pb-20" style="background: #fdf8f3;">
    <div class="ah-container-wide"><?php echo ah_hh_lead( $hh_lead, true ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
  </section>
  <?php elseif ( $hh_lead && $hh_paged === 1 ) : ?>
  <!-- Lead story with its photograph -->
  <section class="pb-14 md:pb-20" style="background: #fdf8f3;">
    <div class="ah-container-wide">
      <div class="hh-lead-wide grid lg:grid-cols-12 gap-8 lg:gap-14 items-center">
        <a href="<?php echo esc_url( $hh_lead['url'] ); ?>" class="hh-lead group lg:col-span-7 block" data-reveal>
          <?php echo ah_hh_cover( $hh_lead, 'health-hub-featured', 'aspect-[16/11]', 'rounded-[28px]' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
        </a>
        <div class="lg:col-span-5" data-reveal>
          <?php echo ah_hh_meta( $hh_lead ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
          <h2 class="font-serif text-gray-900 text-[2rem] md:text-[2.7rem] leading-[1.06] tracking-[-0.02em] mt-3 mb-4" style="text-wrap: balance;">
            <a href="<?php echo esc_url( $hh_lead['url'] ); ?>" class="hover:text-[#7d76ba] transition-colors"><?php echo esc_html( $hh_lead['title'] ); ?></a>
          </h2>
          <?php if ( $hh_lead['dek'] !== '' ) : ?>
          <p class="text-base md:text-[17px] text-gray-600 leading-[1.65] mb-6"><?php echo esc_html( $hh_lead['dek'] ); ?></p>
          <?php endif; ?>
          <a href="<?php echo esc_url( $hh_lead['url'] ); ?>" class="hh-browse-link inline-flex items-center gap-2 text-[15px] font-semibold text-gray-900">Read the story <span aria-hidden="true" style="color:#8e88d0;">&rarr;</span></a>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( $hh_stories ) : ?>
  <!-- Stories -->
  <section class="py-14 md:py-20" style="background: #f7f4f9;">
    <div class="ah-container-wide">
      <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-gray-500 mb-8 md:mb-10"><?php echo $hh_paged > 1 ? esc_html( sprintf( 'More stories · page %d', $hh_paged ) ) : ( $hh_section ? 'More in this section' : 'Latest stories' ); ?></p>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-14" data-stagger>
        <?php foreach ( $hh_stories as $i => $story ) {
            echo ah_hh_card( $story, $i ); // phpcs:ignore WordPress.Security.EscapeOutput
        } ?>
      </div>

      <?php
      $hh_pages = paginate_links( array(
          'total'     => (int) $hh_query->max_num_pages,
          'current'   => $hh_paged,
          'prev_text' => '&larr; Newer',
          'next_text' => 'Older &rarr;',
          'add_args'  => $hh_current ? array( 'section' => $hh_current ) : false,
      ) );
      if ( $hh_pages ) : ?>
      <nav class="hh-pagination mt-16 flex justify-center" aria-label="More stories"><?php echo wp_kses_post( $hh_pages ); ?></nav>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

<?php endif; wp_reset_postdata(); ?>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>
<?php get_footer(); ?>

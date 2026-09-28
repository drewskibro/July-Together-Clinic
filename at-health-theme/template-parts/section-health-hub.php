<?php
/**
 * Home page: the Health Hub as a magazine feature.
 *
 * Picks itself: the newest published story leads (or one "stuck to the top"
 * in WordPress), with the next three beside it. Nothing to hand-pick, so it
 * cannot go stale — and it only appears once there are enough published
 * stories to look like a magazine (3 by default). No section beats an empty one.
 */
if ( ! function_exists( 'ah_hh_front' ) ) {
    return;
}
if ( ah_hh_published_count() < (int) apply_filters( 'ah_health_hub_home_min_stories', 3 ) ) {
    return;
}

$front = ah_hh_front( 3 );
if ( ! $front['lead'] ) {
    return;
}
?>
<section class="hh-home relative py-[64px] md:py-[104px]" style="background: #fdf8f3;">
  <div class="ah-container-wide">

    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-8 mb-12 md:mb-16" data-reveal>
      <div class="max-w-2xl">
        <p class="text-[11px] md:text-xs font-bold uppercase tracking-[0.28em] mb-5" style="color: #8e88d0;">Health Hub</p>
        <h2 class="font-serif text-gray-900 leading-[1.04] tracking-[-0.025em] text-[2.4rem] md:text-[3.3rem] lg:text-[3.8rem] mb-5" style="text-wrap: balance;">
          Everything that happens between the weigh-ins.
        </h2>
        <p class="text-base md:text-lg text-gray-600 leading-[1.6] max-w-xl">
          Recipes that cost less, walks that feel good, and clear answers from our pharmacists. Written for the week you&rsquo;re having, not the one you planned.
        </p>
      </div>
      <div class="lg:max-w-md lg:pb-2"><?php echo ah_hh_section_pills(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
    </div>

    <div class="grid lg:grid-cols-12 gap-10 lg:gap-14 items-start">
      <div class="lg:col-span-7">
        <?php echo ah_hh_lead( $front['lead'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
      </div>

      <div class="lg:col-span-5">
        <?php if ( $front['rest'] ) : ?>
        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-gray-500 pb-1" style="border-bottom: 1px solid rgba(142,136,208,0.18);">Also in the Hub</p>
        <div data-stagger>
          <?php foreach ( $front['rest'] as $i => $story ) {
              echo ah_hh_row( $story, $i ); // phpcs:ignore WordPress.Security.EscapeOutput
          } ?>
        </div>
        <?php endif; ?>
        <a href="<?php echo esc_url( ah_hh_url() ); ?>" class="hh-browse-link inline-flex items-center gap-2 mt-4 text-[15px] font-semibold" style="color: #7d76ba;">
          Browse the Health Hub <span aria-hidden="true">&rarr;</span>
        </a>
      </div>
    </div>

  </div>
</section>

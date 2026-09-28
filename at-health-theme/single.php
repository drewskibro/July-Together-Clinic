<?php
/**
 * Single Blog Post Template
 * Health Hub articles with featured image, metadata, and clinical authority signals.
 */
get_header();
?>

<main style="background: var(--ah-cream);">
    <?php while ( have_posts() ) : the_post(); ?>

    <!-- Hero -->
    <section class="ah-container-wide pt-12 pb-8">
        <div class="max-w-3xl mx-auto">
            <!-- Section: links back to that section of the Health Hub -->
            <?php
            $hh_section = function_exists( 'ah_hh_post_section' ) ? ah_hh_post_section( get_the_ID() ) : null;
            if ( $hh_section ) :
                $hh_is_hub_section = array_key_exists( $hh_section['slug'], ah_hh_sections() ); ?>
                <a href="<?php echo esc_url( $hh_is_hub_section ? ah_hh_url( $hh_section['slug'] ) : ah_hh_url() ); ?>" class="inline-block text-xs font-bold uppercase tracking-[0.16em] mb-4 hover:underline underline-offset-4" style="color: <?php echo esc_attr( $hh_section['ink'] ); ?>;">
                    <?php echo esc_html( $hh_section['name'] ); ?>
                </a>
            <?php endif; ?>

            <h1 class="text-3xl md:text-4xl lg:text-5xl font-serif text-gray-900 leading-tight mb-6">
                <?php the_title(); ?>
            </h1>

            <!-- Meta -->
            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 mb-8">
                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                    <?php echo esc_html( get_the_date() ); ?>
                </time>
                <?php $reading_time = function_exists( 'ah_hh_read_minutes' ) ? ah_hh_read_minutes( get_the_ID() ) : (int) ah_field( 'reading_time', 0 ); ?>
                <?php if ( $reading_time ) : ?>
                    <span>&middot;</span>
                    <span><?php echo esc_html( $reading_time ); ?> min read</span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Featured Image -->
    <?php if ( has_post_thumbnail() ) : ?>
    <section class="ah-container-wide pb-8">
        <div class="max-w-4xl mx-auto">
            <div class="rounded-2xl overflow-hidden">
                <?php the_post_thumbnail( 'health-hub-featured', array(
                    'class' => 'w-full h-auto',
                    'loading' => 'eager',
                ) ); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Content -->
    <section class="ah-container-wide pb-16">
        <div class="max-w-3xl mx-auto">
            <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed">
                <?php the_content(); ?>
            </div>
        </div>
    </section>

    <?php
    if ( function_exists( 'ah_hh_story' ) ) :
        $hh_more_ids = array();
        $hh_exclude  = array( get_the_ID() );
        if ( $hh_section ) {
            $hh_more_ids = get_posts( array( 'category_name' => $hh_section['slug'], 'numberposts' => 3, 'post__not_in' => $hh_exclude, 'fields' => 'ids' ) );
        }
        if ( count( $hh_more_ids ) < 3 ) {
            $hh_more_ids = array_merge( $hh_more_ids, get_posts( array( 'numberposts' => 3 - count( $hh_more_ids ), 'post__not_in' => array_merge( $hh_exclude, $hh_more_ids ), 'fields' => 'ids' ) ) );
        }
        $hh_more = array_values( array_filter( array_map( 'ah_hh_story', $hh_more_ids ) ) );
        if ( $hh_more ) : ?>
    <!-- Keep reading -->
    <section class="py-14 md:py-20" style="background: #f7f4f9;">
        <div class="ah-container-wide">
            <div class="flex items-end justify-between gap-6 mb-10">
                <h2 class="font-serif text-gray-900 text-[1.9rem] md:text-[2.4rem] leading-[1.1] tracking-[-0.02em]">Keep reading</h2>
                <a href="<?php echo esc_url( ah_hh_url() ); ?>" class="hh-browse-link hidden sm:inline-flex items-center gap-2 text-[15px] font-semibold" style="color: #7d76ba;">The Health Hub <span aria-hidden="true">&rarr;</span></a>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12" data-stagger>
                <?php foreach ( $hh_more as $i => $story ) {
                    echo ah_hh_card( $story, $i ); // phpcs:ignore WordPress.Security.EscapeOutput
                } ?>
            </div>
        </div>
    </section>
        <?php endif;
    endif; ?>

    <?php endwhile; ?>
</main>

<?php get_footer(); ?>

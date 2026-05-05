<?php
/**
 * Index Template — Blog listing fallback
 */
get_header();
?>

<main style="background: var(--ah-cream);">
    <section class="ah-container-wide py-16 md:py-24">
        <div class="text-center mb-12">
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-serif text-gray-900 mb-4">Health Hub</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">Expert insights on weight loss, Glucagon-Like Peptide-1 (GLP-1) medications, and healthy living.</p>
        </div>

        <?php if ( have_posts() ) : ?>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <?php while ( have_posts() ) : the_post(); ?>
            <article class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-200 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                <?php if ( has_post_thumbnail() ) : ?>
                <a href="<?php the_permalink(); ?>" class="block">
                    <?php the_post_thumbnail( 'health-hub-card', array( 'class' => 'w-full h-48 object-cover' ) ); ?>
                </a>
                <?php endif; ?>
                <div class="p-6">
                    <?php
                    $cats = get_the_category();
                    if ( $cats ) : ?>
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-600 mb-2 block">
                            <?php echo esc_html( $cats[0]->name ); ?>
                        </span>
                    <?php endif; ?>
                    <h2 class="text-xl font-serif text-gray-900 mb-3">
                        <a href="<?php the_permalink(); ?>" class="hover:text-purple-600 transition-colors">
                            <?php the_title(); ?>
                        </a>
                    </h2>
                    <p class="text-sm text-gray-600 leading-relaxed mb-4"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
                    <a href="<?php the_permalink(); ?>" class="text-sm font-semibold text-purple-600 hover:text-purple-700 transition-colors">
                        Read more &rarr;
                    </a>
                </div>
            </article>
            <?php endwhile; ?>
        </div>

        <div class="mt-12 flex justify-center">
            <?php
            the_posts_pagination( array(
                'mid_size'  => 2,
                'prev_text' => '&larr; Previous',
                'next_text' => 'Next &rarr;',
            ) );
            ?>
        </div>
        <?php else : ?>
            <p class="text-center text-gray-500 text-lg">No articles found yet. Check back soon.</p>
        <?php endif; ?>
    </section>
</main>

<?php get_footer(); ?>

    <!-- PREMIUM FOOTER -->
    <footer class="bg-[#0f1117] text-white font-sans antialiased">
        <!-- Main Content -->
        <div class="ah-container-wide pt-16 pb-12">
            <!-- Top row: Logo + tagline + CTA -->
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 mb-14 pb-10 border-b border-white/[0.06]">
                <div class="flex items-center gap-5">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="block">
                        <img src="<?php echo esc_url( ah_logo_url() ); ?>" alt="<?php echo esc_attr( ah_company_name() ); ?>" class="h-11 brightness-0 invert opacity-90" />
                    </a>
                    <div class="hidden sm:block w-px h-8 bg-white/10"></div>
                    <p class="hidden sm:block text-gray-500 text-sm font-medium">
                        <?php echo esc_html( ah_option( 'footer_tagline', 'Medical Weight Loss, Delivered' ) ); ?>
                    </p>
                </div>
                <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-500 text-white text-sm font-semibold px-7 py-3 rounded-lg transition-all">
                    <?php echo esc_html( ah_option( 'footer_cta_text', 'Start Your Journey' ) ); ?>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>

            <!-- Nav columns -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-10 lg:gap-8 mb-14">
                <!-- Treatments -->
                <div>
                    <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.18em] mb-5">Treatments</h3>
                    <ul class="space-y-3.5">
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'mounjaro' ) ) ); ?>" class="text-[15px] text-gray-300 hover:text-white transition-colors">Mounjaro</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'wegovy' ) ) ); ?>" class="text-[15px] text-gray-300 hover:text-white transition-colors">Wegovy</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'treatments' ) ) ); ?>" class="text-[15px] text-gray-300 hover:text-white transition-colors">All Treatments</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'weight-loss-eligibility' ) ?: get_page_by_path( 'eligibility' ) ) ); ?>" class="text-[15px] text-gray-300 hover:text-white transition-colors">Check Eligibility</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.18em] mb-5">Support</h3>
                    <ul class="space-y-3.5">
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'switching-providers' ) ) ); ?>" class="text-[15px] text-gray-300 hover:text-white transition-colors">Switching Providers</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'customer-care' ) ) ); ?>" class="text-[15px] text-gray-300 hover:text-white transition-colors">Customer Care</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'contact-us' ) ?: get_page_by_path( 'contact' ) ) ); ?>" class="text-[15px] text-gray-300 hover:text-white transition-colors">Contact Us</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'reorder' ) ) ); ?>" class="text-[15px] text-gray-300 hover:text-white transition-colors">Reorder</a></li>
                    </ul>
                </div>

                <!-- Learn -->
                <div>
                    <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.18em] mb-5">Learn</h3>
                    <ul class="space-y-3.5">
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'health-hub' ) ) ); ?>" class="text-[15px] text-gray-300 hover:text-white transition-colors">Health Hub</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'about' ) ) ); ?>" class="text-[15px] text-gray-300 hover:text-white transition-colors">About Us</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="col-span-2 sm:col-span-3 lg:col-span-2">
                    <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-[0.18em] mb-5">Get In Touch</h3>
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-white/[0.05] border border-white/[0.06] flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-white text-sm font-medium">Hours</p>
                                <p class="text-[11px] text-gray-500"><?php echo esc_html( ah_business_hours() ); ?></p>
                            </div>
                        </div>
                        <a href="mailto:<?php echo esc_attr( ah_email() ); ?>" class="flex items-center gap-3 group">
                            <div class="w-9 h-9 rounded-lg bg-white/[0.05] border border-white/[0.06] flex items-center justify-center flex-shrink-0 group-hover:bg-purple-600/20 group-hover:border-purple-500/30 transition-all">
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-purple-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-white text-sm font-medium group-hover:text-purple-300 transition-colors"><?php echo esc_html( ah_email() ); ?></p>
                                <p class="text-[11px] text-gray-500"><?php echo esc_html( ah_option( 'email_response_time', 'Reply within 4 hours' ) ); ?></p>
                            </div>
                        </a>
                        <p class="text-[11px] text-gray-500 leading-relaxed"><?php echo esc_html( ah_no_phone_notice() ); ?></p>

                        <!-- Newsletter -->
                        <form class="mt-6 flex gap-2" action="#" method="post">
                            <input type="email" name="email" placeholder="Your email"
                                class="flex-1 min-w-0 bg-white/[0.04] border border-white/[0.08] text-white text-sm px-4 py-2.5 rounded-lg focus:outline-none focus:border-purple-500/50 focus:ring-1 focus:ring-purple-500/20 transition-all placeholder-gray-600" />
                            <button type="submit"
                                class="flex-shrink-0 bg-white/[0.08] hover:bg-purple-600 border border-white/[0.08] hover:border-purple-500 text-gray-300 hover:text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-all">
                                Subscribe
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Trust badges -->
            <div class="flex flex-wrap items-center gap-x-8 gap-y-4 py-8 border-t border-b border-white/[0.06]">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span class="text-sm text-gray-300 font-medium"><?php echo esc_html( ah_option( 'trust_badge_1', 'GPhC & MHRA Regulated' ) ); ?></span>
                </div>
                <div class="flex items-center gap-2.5">
                    <div class="flex gap-0.5">
                        <?php for ( $i = 0; $i < 5; $i++ ) : ?>
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <?php endfor; ?>
                    </div>
                    <span class="text-sm text-gray-300 font-medium"><?php echo esc_html( ah_option( 'trust_badge_2', '4.9/5 from 10,000+ patients' ) ); ?></span>
                </div>
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span class="text-sm text-gray-300 font-medium"><?php echo esc_html( ah_option( 'trust_badge_3', '256-bit SSL Encrypted' ) ); ?></span>
                </div>
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span class="text-sm text-gray-300 font-medium"><?php echo esc_html( ah_option( 'trust_badge_4', 'Tracked 48h Delivery' ) ); ?></span>
                </div>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="ah-container-wide py-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-gray-500 text-xs">
                    &copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( ah_option( 'company_legal_name', 'Together Clinic Ltd' ) ); ?>. All rights reserved. <?php echo esc_html( ah_option( 'company_registration', 'Company registered in England & Wales.' ) ); ?>
                </p>
                <div class="flex flex-wrap justify-center gap-5 text-xs text-gray-500">
                    <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'terms' ) ) ); ?>" class="hover:text-gray-300 transition-colors">Terms</a>
                    <a href="<?php echo esc_url( ah_option( 'privacy_url', '#' ) ); ?>" class="hover:text-gray-300 transition-colors">Privacy</a>
                    <a href="<?php echo esc_url( ah_option( 'cookies_url', '#' ) ); ?>" class="hover:text-gray-300 transition-colors">Cookies</a>
                    <a href="<?php echo esc_url( ah_option( 'accessibility_url', '#' ) ); ?>" class="hover:text-gray-300 transition-colors">Accessibility</a>
                </div>
            </div>
        </div>
    </footer>

    <?php wp_footer(); ?>
</body>
</html>

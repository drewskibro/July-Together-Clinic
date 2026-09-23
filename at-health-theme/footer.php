    <!-- PREMIUM FOOTER -->
    <?php
    /*
     * Regulatory line. A registered pharmacy's website is expected to state the
     * owner, the premises GPhC registration and the superintendent. Every
     * fragment renders ONLY when its option is set — the A3 defaults are blank
     * so a template leftover can never be published as this pharmacy's details.
     */
    $ft_reg_name   = trim( (string) ah_option( 'registered_name', '' ) );
    $ft_premises   = trim( (string) ah_option( 'gphc_number', '' ) );
    $ft_super      = trim( (string) ah_option( 'superintendent', '' ) );
    $ft_super_no   = trim( (string) ah_option( 'superintendent_gphc_number', '' ) );
    $ft_company_no = trim( (string) ah_option( 'company_number', '' ) );
    $ft_address    = trim( (string) ah_option( 'registered_address', '' ) );
    $ft_has_reg    = ( $ft_reg_name || $ft_premises || $ft_super || $ft_address || $ft_company_no );

    $ft_legal_links = array(
        array( 'label' => 'Terms',         'url' => get_page_by_path( 'terms' ) ? get_permalink( get_page_by_path( 'terms' ) ) : '' ),
        array( 'label' => 'Privacy',       'url' => get_page_by_path( 'privacy-policy' ) ? get_permalink( get_page_by_path( 'privacy-policy' ) ) : '' ),
        array( 'label' => 'Refund Policy', 'url' => get_page_by_path( 'refund-policy' ) ? get_permalink( get_page_by_path( 'refund-policy' ) ) : '' ),
        array( 'label' => 'Cookies',       'url' => (string) ah_option( 'cookies_url', '' ) ),
        array( 'label' => 'Accessibility', 'url' => (string) ah_option( 'accessibility_url', '' ) ),
    );
    ?>
    <footer class="ah-footer bg-[#0f1117] text-white font-sans antialiased">
        <div class="ah-container-wide pt-16 pb-12">

            <!-- Top row: logo + tagline + CTA -->
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 mb-14 pb-10 border-b border-white/[0.08]">
                <div class="flex items-center gap-5">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="block">
                        <img src="<?php echo esc_url( ah_logo_url() ); ?>" alt="<?php echo esc_attr( ah_company_name() ); ?>" class="block h-11 w-auto brightness-0 invert opacity-90" />
                    </a>
                    <div class="hidden sm:block w-px h-8 bg-white/10"></div>
                    <p class="hidden sm:block text-gray-300 text-[15px] font-medium">
                        <?php echo esc_html( ah_option( 'footer_tagline', 'Pharmacist-led care, delivered.' ) ); ?>
                    </p>
                </div>
                <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-500 text-white text-sm font-semibold px-7 py-3 rounded-lg transition-all">
                    <?php echo esc_html( ah_option( 'footer_cta_text', 'Start Your Journey' ) ); ?>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>

            <!-- Nav columns. Labels are <h3>, which the theme sets to the serif display
                 face — at 11px that is hairline-thin on dark. font-sans + 12px + 600 fixes it. -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-10 lg:gap-8 mb-14">
                <div>
                    <h3 class="ah-footer-label">Treatments</h3>
                    <ul class="space-y-3.5">
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'mounjaro' ) ) ); ?>" class="ah-footer-link">Mounjaro</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'wegovy' ) ) ); ?>" class="ah-footer-link">Wegovy</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'wegovy-tablets' ) ) ); ?>" class="ah-footer-link">Wegovy Tablets</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'foundayo' ) ) ); ?>" class="ah-footer-link">Foundayo</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'treatments' ) ) ); ?>" class="ah-footer-link">All Treatments</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'weight-loss-eligibility' ) ?: get_page_by_path( 'eligibility' ) ) ); ?>" class="ah-footer-link">Check Eligibility</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="ah-footer-label">Support</h3>
                    <ul class="space-y-3.5">
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'switching-providers' ) ) ); ?>" class="ah-footer-link">Switching Providers</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'customer-care' ) ) ); ?>" class="ah-footer-link">Customer Care</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'contact-us' ) ?: get_page_by_path( 'contact' ) ) ); ?>" class="ah-footer-link">Contact Us</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'reorder' ) ) ); ?>" class="ah-footer-link">Reorder</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>" class="ah-footer-link">My Account</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="ah-footer-label">Learn</h3>
                    <ul class="space-y-3.5">
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'health-hub' ) ) ); ?>" class="ah-footer-link">Health Hub</a></li>
                        <li><a href="<?php echo esc_url( get_permalink( get_page_by_path( 'about' ) ) ); ?>" class="ah-footer-link">About Us</a></li>
                    </ul>
                </div>

                <div class="col-span-2 sm:col-span-3 lg:col-span-2">
                    <h3 class="ah-footer-label">Get In Touch</h3>
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-white/[0.05] border border-white/[0.08] flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-white text-sm font-medium">Hours</p>
                                <p class="text-[13px] text-gray-400"><?php echo esc_html( ah_business_hours() ); ?></p>
                            </div>
                        </div>
                        <a href="mailto:<?php echo esc_attr( ah_email() ); ?>" class="flex items-center gap-3 group">
                            <div class="w-9 h-9 rounded-lg bg-white/[0.05] border border-white/[0.08] flex items-center justify-center flex-shrink-0 group-hover:bg-purple-600/20 group-hover:border-purple-500/30 transition-all">
                                <svg class="w-4 h-4 text-purple-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-white text-sm font-medium group-hover:text-purple-300 transition-colors"><?php echo esc_html( ah_email() ); ?></p>
                                <p class="text-[13px] text-gray-400"><?php echo esc_html( ah_option( 'email_response_time', 'Reply within 4 hours' ) ); ?></p>
                            </div>
                        </a>
                        <p class="text-[13px] text-gray-400 leading-relaxed max-w-sm"><?php echo esc_html( ah_no_phone_notice() ); ?></p>
                    </div>
                </div>
            </div>

            <!-- Trust row -->
            <div class="flex flex-wrap items-center gap-x-8 gap-y-4 py-8 border-t border-b border-white/[0.08]">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span class="text-sm text-gray-200 font-medium"><?php echo esc_html( ah_option( 'trust_badge_1', 'GPhC-registered pharmacy' ) ); ?></span>
                </div>
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span class="text-sm text-gray-200 font-medium"><?php echo esc_html( ah_option( 'trust_badge_3', '256-bit SSL encrypted' ) ); ?></span>
                </div>
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span class="text-sm text-gray-200 font-medium"><?php echo esc_html( ah_option( 'trust_badge_4', 'Tracked 48h delivery' ) ); ?></span>
                </div>
            </div>

            <?php if ( $ft_has_reg ) : ?>
            <!-- Regulatory line: renders only the fragments that are set -->
            <div class="pt-8 text-[13px] text-gray-400 leading-relaxed max-w-4xl">
                <p>
                    <?php if ( $ft_reg_name ) : ?>
                        <?php echo esc_html( ah_company_name() ); ?> is a trading name of <span class="text-gray-300"><?php echo esc_html( $ft_reg_name ); ?></span><?php echo $ft_company_no ? ' (company no. ' . esc_html( $ft_company_no ) . ')' : ''; ?>.
                    <?php elseif ( $ft_company_no ) : ?>
                        Company no. <span class="text-gray-300"><?php echo esc_html( $ft_company_no ); ?></span>.
                    <?php endif; ?>
                    <?php if ( $ft_premises ) : ?>
                        Registered pharmacy with the <a href="https://www.pharmacyregulation.org/registers" class="text-gray-300 underline decoration-white/20 underline-offset-4 hover:text-white hover:decoration-purple-400 transition-colors" target="_blank" rel="noopener noreferrer">General Pharmaceutical Council</a>, premises no. <span class="text-gray-300"><?php echo esc_html( $ft_premises ); ?></span>.
                    <?php endif; ?>
                    <?php if ( $ft_super ) : ?>
                        Superintendent Pharmacist: <span class="text-gray-300"><?php echo esc_html( $ft_super ); ?></span><?php echo $ft_super_no ? ' (GPhC ' . esc_html( $ft_super_no ) . ')' : ''; ?>.
                    <?php endif; ?>
                    <?php if ( $ft_address ) : ?>
                        Registered office: <span class="text-gray-300"><?php echo esc_html( $ft_address ); ?></span>.
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Bottom bar -->
        <div class="border-t border-white/[0.08]">
            <div class="ah-container-wide py-6">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <p class="text-gray-400 text-[13px]">
                        &copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( ah_option( 'company_legal_name', 'Together Clinic Ltd' ) ); ?>. All rights reserved. <?php echo esc_html( ah_option( 'company_registration', 'Company registered in England & Wales.' ) ); ?>
                    </p>
                    <div class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-[13px]">
                        <?php foreach ( $ft_legal_links as $ft_link ) :
                            // No dead links: skip anything without a real URL.
                            if ( $ft_link['url'] === '' || $ft_link['url'] === '#' ) { continue; } ?>
                            <a href="<?php echo esc_url( $ft_link['url'] ); ?>" class="ah-footer-link ah-footer-link--sm"><?php echo esc_html( $ft_link['label'] ); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <?php wp_footer(); ?>
</body>
</html>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: {
              serif: ['DM Serif Display', 'serif'],
              sans: ['Inter', 'sans-serif']
            },
            colors: {
              purple: {
                50: '#f5f3ff',
                100: '#ede9fe',
                200: '#ddd6fe',
                300: '#c4b5fd',
                400: '#a89dd6',
                500: '#9b8fce',
                600: '#8e88d0',
                700: '#7d76ba',
                800: '#6d28d9',
                900: '#4c1d95',
              }
            },
            backgroundImage: {
              'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
            }
          }
        }
      };
    </script>
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'text-gray-900' ); ?> style="background: #fdf8f3;">

    <!-- Top Banner -->
    <div class="ah-top-banner" style="background:#8e88d0;">
        <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="block ah-container text-center text-white text-sm font-medium py-3 hover:opacity-90 transition-opacity">
            <?php echo wp_kses_post( ah_option( 'top_banner_text', 'Clinically proven weight loss treatments —' ) ); ?>
            <span class="underline ml-1"><?php echo esc_html( ah_option( 'top_banner_link_text', 'Check your eligibility' ) ); ?></span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="ah-nav bg-white border-b border-gray-100 sticky top-0 z-50">
        <div class="ah-container">
            <div class="flex items-center justify-between h-20">
                <!-- Logo -->
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <img
                        src="<?php echo esc_url( ah_logo_url() ); ?>"
                        alt="<?php echo esc_attr( ah_company_name() ); ?>"
                        class="h-16"
                    />
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden lg:flex items-center gap-8">
                    <!-- Treatments Dropdown -->
                    <div class="relative group">
                        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'treatments' ) ) ); ?>"
                           class="text-gray-700 text-sm font-medium hover:text-purple-600 flex items-center gap-1">
                            Treatments
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </a>
                        <div class="absolute top-full left-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'mounjaro' ) ) ); ?>"
                               class="block px-4 py-3 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-t-lg transition-colors">Mounjaro</a>
                            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'wegovy' ) ) ); ?>"
                               class="block px-4 py-3 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors">Wegovy</a>
                            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'treatments' ) ) ); ?>"
                               class="block px-4 py-3 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-b-lg transition-colors">All Treatments</a>
                        </div>
                    </div>

                    <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'switching-providers' ) ) ); ?>"
                       class="text-gray-700 text-sm font-medium hover:text-purple-600">Switching Providers</a>

                    <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'eligibility' ) ) ); ?>"
                       class="text-gray-700 text-sm font-medium hover:text-purple-600">Eligibility</a>

                    <!-- About Dropdown -->
                    <div class="relative group">
                        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'about' ) ) ); ?>"
                           class="text-gray-700 text-sm font-medium hover:text-purple-600 flex items-center gap-1">
                            About
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </a>
                        <div class="absolute top-full left-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'about' ) ) ); ?>"
                               class="block px-4 py-3 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-t-lg transition-colors">About Us</a>
                            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'customer-care' ) ) ); ?>"
                               class="block px-4 py-3 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 rounded-b-lg transition-colors">Customer Care</a>
                        </div>
                    </div>

                    <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'health-hub' ) ) ); ?>"
                       class="text-gray-700 text-sm font-medium hover:text-purple-600">Resources</a>

                    <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'contact-us' ) ?: get_page_by_path( 'contact' ) ) ); ?>"
                       class="text-gray-700 text-sm font-medium hover:text-purple-600">Contact</a>
                </div>

                <!-- CTA Buttons -->
                <div class="hidden sm:flex items-center gap-3 lg:ml-10">
                    <a href="/my-account/"
                       class="whitespace-nowrap text-sm font-medium px-6 py-3 rounded-lg border-[1.5px] border-purple-600 text-purple-600 hover:bg-purple-600 hover:text-white transition-all duration-300">
                        Log In
                    </a>
                    <a href="<?php echo esc_url( ah_booking_url() ); ?>"
                       class="whitespace-nowrap bg-purple-600 text-white text-sm font-medium px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors">
                        <?php echo esc_html( ah_option( 'nav_cta_text', 'Start Journey →' ) ); ?>
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <button class="lg:hidden flex items-center justify-center w-10 h-10 text-gray-600 hover:text-purple-600 transition-colors" id="menuToggle" aria-label="Open menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu Overlay -->
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 opacity-0 pointer-events-none transition-opacity duration-300" id="mobileMenuOverlay"></div>

    <!-- Mobile Menu -->
    <div class="fixed top-0 right-0 bottom-0 w-[85%] max-w-[400px] bg-white/95 backdrop-blur-xl shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-out" id="mobileMenu">
        <!-- Menu Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <div class="flex items-center gap-3">
                <img src="<?php echo esc_url( ah_logo_url() ); ?>" alt="<?php echo esc_attr( ah_company_name() ); ?>" class="h-10" />
            </div>
            <button class="flex items-center justify-center w-10 h-10 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-full transition-all" id="menuClose" aria-label="Close menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                </svg>
            </button>
        </div>

        <!-- Menu Content -->
        <div class="flex flex-col h-[calc(100%-80px)] overflow-y-auto">
            <nav class="flex-1 p-6 space-y-2">
                <!-- Treatments Accordion -->
                <div class="mobile-nav-item">
                    <button class="w-full flex items-center justify-between px-4 py-4 rounded-xl hover:bg-purple-50 transition-all group" data-mobile-toggle="mobile-treatments">
                        <span class="text-gray-700 text-base font-semibold group-hover:text-purple-600 transition-colors">Treatments</span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-300 mobile-toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="mobile-treatments" class="hidden pl-4 space-y-1">
                        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'mounjaro' ) ) ); ?>" class="block px-4 py-3 rounded-xl hover:bg-purple-50 text-gray-600 hover:text-purple-600 text-sm font-medium">Mounjaro</a>
                        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'wegovy' ) ) ); ?>" class="block px-4 py-3 rounded-xl hover:bg-purple-50 text-gray-600 hover:text-purple-600 text-sm font-medium">Wegovy</a>
                        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'treatments' ) ) ); ?>" class="block px-4 py-3 rounded-xl hover:bg-purple-50 text-gray-600 hover:text-purple-600 text-sm font-medium">All Treatments</a>
                    </div>
                </div>

                <a class="group flex items-center px-4 py-4 rounded-xl hover:bg-purple-50 transition-all" href="<?php echo esc_url( get_permalink( get_page_by_path( 'switching-providers' ) ) ); ?>">
                    <span class="text-gray-700 text-base font-semibold group-hover:text-purple-600 transition-colors">Switching Providers</span>
                </a>

                <a class="group flex items-center px-4 py-4 rounded-xl hover:bg-purple-50 transition-all" href="<?php echo esc_url( get_permalink( get_page_by_path( 'eligibility' ) ) ); ?>">
                    <span class="text-gray-700 text-base font-semibold group-hover:text-purple-600 transition-colors">Eligibility</span>
                </a>

                <!-- About Accordion -->
                <div class="mobile-nav-item">
                    <button class="w-full flex items-center justify-between px-4 py-4 rounded-xl hover:bg-purple-50 transition-all group" data-mobile-toggle="mobile-about">
                        <span class="text-gray-700 text-base font-semibold group-hover:text-purple-600 transition-colors">About</span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-300 mobile-toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="mobile-about" class="hidden pl-4 space-y-1">
                        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'about' ) ) ); ?>" class="block px-4 py-3 rounded-xl hover:bg-purple-50 text-gray-600 hover:text-purple-600 text-sm font-medium">About Us</a>
                        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'customer-care' ) ) ); ?>" class="block px-4 py-3 rounded-xl hover:bg-purple-50 text-gray-600 hover:text-purple-600 text-sm font-medium">Customer Care</a>
                    </div>
                </div>

                <a class="group flex items-center px-4 py-4 rounded-xl hover:bg-purple-50 transition-all" href="<?php echo esc_url( get_permalink( get_page_by_path( 'health-hub' ) ) ); ?>">
                    <span class="text-gray-700 text-base font-semibold group-hover:text-purple-600 transition-colors">Resources</span>
                </a>

                <a class="group flex items-center px-4 py-4 rounded-xl hover:bg-purple-50 transition-all" href="<?php echo esc_url( get_permalink( get_page_by_path( 'contact-us' ) ?: get_page_by_path( 'contact' ) ) ); ?>">
                    <span class="text-gray-700 text-base font-semibold group-hover:text-purple-600 transition-colors">Contact</span>
                </a>
            </nav>

            <!-- Menu Footer -->
            <div class="p-6 border-t border-gray-200 space-y-4">
                <a href="/my-account/" class="w-full flex items-center justify-center gap-3 border-[1.5px] border-purple-600 text-purple-600 hover:bg-purple-600 hover:text-white text-base font-semibold px-6 py-4 rounded-xl transition-all duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    Log In
                </a>
                <a href="<?php echo esc_url( ah_booking_url() ); ?>" class="w-full flex items-center justify-center gap-3 bg-purple-600 hover:bg-purple-700 text-white text-base font-semibold px-6 py-4 rounded-xl shadow-lg hover:shadow-xl transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    Start Journey
                </a>
                <a class="w-full flex items-center justify-center gap-3 text-purple-600 text-base font-semibold px-6 py-4 rounded-xl border-2 border-purple-600 hover:bg-purple-50 transition-all" href="mailto:<?php echo esc_attr( ah_email() ); ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Email <?php echo esc_html( ah_email() ); ?>
                </a>
                <p class="text-center text-gray-500 text-xs leading-relaxed px-2"><?php echo esc_html( ah_no_phone_notice() ); ?></p>
                <p class="text-center text-gray-500 text-xs pt-2">&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( ah_company_name() ); ?></p>
            </div>
        </div>
    </div>

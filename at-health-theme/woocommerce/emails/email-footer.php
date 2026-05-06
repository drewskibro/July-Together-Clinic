<?php
/**
 * AT Health — WooCommerce Email Footer
 * Overrides: woocommerce/templates/emails/email-footer.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$email   = function_exists( 'ah_email' ) ? ah_email() : 'info@togetherclinic.co.uk';
$hours   = function_exists( 'ah_business_hours' ) ? ah_business_hours() : '9am - 5pm, Monday to Friday';
$notice  = function_exists( 'ah_no_phone_notice' ) ? ah_no_phone_notice() : 'We do not offer telephone consultations. Please contact us via email or live chat.';
$company = function_exists( 'ah_company_name' ) ? ah_company_name() : 'Together Clinic';
?>
                        </td>
                    </tr>
                </table>
                <!-- End main card -->
            </td>
        </tr>

        <!-- Support bar -->
        <tr>
            <td align="center" style="padding: 24px 16px 0;">
                <table border="0" cellpadding="0" cellspacing="0" width="600" class="ah-email-container" style="max-width: 600px; width: 100%;">
                    <tr>
                        <td style="background-color: #f7f4f9; border-radius: 16px; padding: 24px 32px; text-align: center;">
                            <p style="margin: 0 0 8px; font-size: 14px; font-weight: 600; color: #111827;">Need support?</p>
                            <p style="margin: 0; font-size: 14px; color: #6b7280;">
                                Email <a href="mailto:<?php echo esc_attr( $email ); ?>" style="color: #8e88d0; font-weight: 600;"><?php echo esc_html( $email ); ?></a>
                            </p>
                            <p style="margin: 6px 0 0; font-size: 12px; color: #9ca3af;">Hours: <?php echo esc_html( $hours ); ?></p>
                            <p style="margin: 6px 0 0; font-size: 12px; color: #9ca3af;"><?php echo esc_html( $notice ); ?></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Compliance footer -->
        <tr>
            <td align="center" style="padding: 24px 16px 40px;">
                <table border="0" cellpadding="0" cellspacing="0" width="600" class="ah-email-container" style="max-width: 600px; width: 100%;">
                    <tr>
                        <td style="text-align: center; padding: 16px 0; border-top: 1px solid #e5e7eb;">
                            <!-- Trust badges -->
                            <p style="margin: 0 0 12px; font-size: 12px; color: #9ca3af;">
                                &#9989; GPhC &amp; MHRA Regulated &nbsp;&middot;&nbsp; &#128274; 256-bit SSL Encrypted &nbsp;&middot;&nbsp; &#128230; Tracked 48h Delivery
                            </p>

                            <!-- Legal -->
                            <p style="margin: 0 0 8px; font-size: 11px; color: #d1d5db;">
                                &copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( $company ); ?> Ltd. All rights reserved.
                            </p>
                            <p style="margin: 0 0 8px; font-size: 11px; color: #d1d5db;">
                                <?php echo esc_html( function_exists( 'ah_option' ) ? ah_option( 'registered_name', 'Prescription Point Ltd' ) : 'Prescription Point Ltd' ); ?>
                                (Co. No: <?php echo esc_html( function_exists( 'ah_option' ) ? ah_option( 'company_number', '08563110' ) : '08563110' ); ?>)
                                &middot; GPhC: <?php echo esc_html( function_exists( 'ah_option' ) ? ah_option( 'gphc_number', '2081354' ) : '2081354' ); ?>
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #d1d5db;">
                                Superintendent: <?php echo esc_html( function_exists( 'ah_option' ) ? ah_option( 'superintendent', 'Ms. Simona Pantaziu' ) : 'Ms. Simona Pantaziu' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

<?php
/**
 * AT Health — WooCommerce Email Header
 * Overrides: woocommerce/templates/emails/email-header.php
 *
 * @var string $email_heading
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
    <style type="text/css">
        /* Reset */
        body, table, td, p, a, li { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; background-color: #fdf8f3; }

        /* Typography */
        body, td, p { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 15px; line-height: 1.7; color: #4b5563; }
        h1, h2, h3 { font-family: 'DM Serif Display', Georgia, 'Times New Roman', serif; font-weight: 400; color: #111827; margin: 0 0 16px; }
        h1 { font-size: 28px; line-height: 1.2; }
        h2 { font-size: 22px; line-height: 1.25; }
        a { color: #8e88d0; text-decoration: none; }
        a:hover { color: #7d76ba; }

        /* Button */
        .ah-email-btn {
            display: inline-block;
            background-color: #8e88d0;
            color: #ffffff !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 10px;
            mso-padding-alt: 0;
        }
        .ah-email-btn:hover { background-color: #7d76ba; }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .ah-email-container { width: 100% !important; padding: 16px !important; }
            .ah-email-content { padding: 24px 20px !important; }
            h1 { font-size: 24px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #fdf8f3;">
    <!-- Outer wrapper -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fdf8f3;">
        <tr>
            <td align="center" style="padding: 40px 16px 20px;">
                <!-- Logo -->
                <?php
                $logo_url = function_exists( 'ah_logo_url' ) ? ah_logo_url() : '';
                if ( $logo_url ) : ?>
                    <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="160" style="display: block; margin: 0 auto 24px; height: auto;" />
                <?php else : ?>
                    <h2 style="font-family: 'DM Serif Display', Georgia, serif; font-size: 28px; color: #111827; margin: 0 0 24px; text-align: center;">Together Clinic</h2>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td align="center">
                <!-- Main card -->
                <table border="0" cellpadding="0" cellspacing="0" width="600" class="ah-email-container" style="max-width: 600px; width: 100%;">
                    <tr>
                        <td class="ah-email-content" style="background-color: #ffffff; border-radius: 20px; padding: 40px 36px; border: 1px solid #e5e7eb;">
                            <?php if ( $email_heading ) : ?>
                                <h1 style="text-align: center; margin-bottom: 24px;"><?php echo wp_kses_post( $email_heading ); ?></h1>
                            <?php endif; ?>

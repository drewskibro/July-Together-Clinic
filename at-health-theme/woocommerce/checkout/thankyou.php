<?php
/**
 * Order-received (thank-you) page — Together Clinic override.
 *
 * State-aware for the review-first clinical model:
 *  - Treatment order, card authorised (Phase 2.5): "hold placed, prescriber
 *    reviewing" journey timeline. No money taken yet and the page says so.
 *  - Treatment order, captured (pay-link fallback lane): payment received,
 *    dispensing next.
 *  - Failed: WooCommerce's standard retry actions.
 *  - Anything else: a clean generic confirmation.
 *
 * Core hooks (woocommerce_thankyou_<gateway>, woocommerce_thankyou) and the
 * order-details template are preserved so gateway instructions and records
 * render exactly as WooCommerce expects.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @version 8.1.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order">

<?php if ( $order ) : ?>

	<?php if ( $order->has_status( 'failed' ) ) : ?>

		<div class="max-w-2xl mx-auto text-center py-8">
			<div class="w-16 h-16 mx-auto mb-6 rounded-full flex items-center justify-center" style="background:#fee2e2;">
				<svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
			</div>
			<h2 class="text-2xl md:text-3xl font-serif text-gray-900 mb-4">Your payment could not be processed</h2>
			<p class="text-gray-600 mb-8">Nothing has been charged. Please try again, or contact us and we will help you complete your order.</p>
			<p class="flex flex-wrap items-center justify-center gap-4">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-7 py-3.5 rounded-xl transition-all">Try again</a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="text-sm font-semibold text-purple-600 hover:text-purple-700">My account</a>
				<?php endif; ?>
			</p>
		</div>

	<?php else : ?>

		<?php
		$is_treatment = class_exists( 'TC_Review_Status' ) && TC_Review_Status::is_treatment_order( $order );
		$authorised   = $is_treatment
			&& class_exists( 'TC_Review_Payment' )
			&& ( TC_Review_Payment::is_authorised_uncaptured( $order )
				// Belt-and-braces: an order still under review that has been
				// through the pay page is an authorisation even if gateway
				// meta is slow to land.
				|| ( class_exists( 'TC_Review_Status' ) && $order->get_status() === TC_Review_Status::STATUS && $order->get_date_paid() ) );
		$first_name   = $order->get_billing_first_name();
		?>

		<!-- Success hero -->
		<div class="max-w-3xl mx-auto text-center pb-10">
			<div class="w-20 h-20 mx-auto mb-6 rounded-full flex items-center justify-center" style="background:#ecfdf5;">
				<svg class="w-10 h-10" style="color:#10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
			</div>

			<?php if ( $is_treatment && $authorised ) : ?>
				<h2 class="text-3xl md:text-4xl font-serif text-gray-900 mb-4">Thank you<?php echo $first_name ? ', ' . esc_html( $first_name ) : ''; ?> &mdash; your order is with our prescribers</h2>
				<p class="text-base md:text-lg text-gray-600 max-w-xl mx-auto">A temporary hold has been placed on your card. <strong class="text-gray-800">No money has been taken.</strong> One of our prescribers will now review your assessment &mdash; you will hear from us by email, usually within 24 hours.</p>
			<?php elseif ( $is_treatment ) : ?>
				<h2 class="text-3xl md:text-4xl font-serif text-gray-900 mb-4">Thank you<?php echo $first_name ? ', ' . esc_html( $first_name ) : ''; ?> &mdash; payment received</h2>
				<p class="text-base md:text-lg text-gray-600 max-w-xl mx-auto">Your treatment has been approved and your payment is complete. Our pharmacy team is preparing your order for tracked, temperature-controlled delivery.</p>
			<?php else : ?>
				<h2 class="text-3xl md:text-4xl font-serif text-gray-900 mb-4">Thank you<?php echo $first_name ? ', ' . esc_html( $first_name ) : ''; ?> &mdash; order confirmed</h2>
				<p class="text-base md:text-lg text-gray-600 max-w-xl mx-auto"><?php echo esc_html( apply_filters( 'woocommerce_thankyou_order_received_text', __( 'Thank you. Your order has been received.', 'woocommerce' ), $order ) ); ?></p>
			<?php endif; ?>

			<!-- Order meta chips -->
			<div class="flex flex-wrap items-center justify-center gap-3 mt-8">
				<span class="inline-flex items-center gap-2 bg-white border border-gray-200 rounded-full px-4 py-2 text-sm text-gray-700 shadow-sm">Order <strong class="text-gray-900">#<?php echo esc_html( $order->get_order_number() ); ?></strong></span>
				<span class="inline-flex items-center gap-2 bg-white border border-gray-200 rounded-full px-4 py-2 text-sm text-gray-700 shadow-sm"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></span>
				<span class="inline-flex items-center gap-2 bg-white border border-gray-200 rounded-full px-4 py-2 text-sm text-gray-700 shadow-sm"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?><?php echo ( $is_treatment && $authorised ) ? ' <span class="text-gray-500">held, not charged</span>' : ''; ?></span>
			</div>
		</div>

		<?php if ( $is_treatment ) : ?>
		<!-- What happens next -->
		<div class="max-w-3xl mx-auto mb-12">
			<div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-8 md:p-10">
				<h3 class="text-xl font-serif text-gray-900 mb-8 text-center">What happens next</h3>
				<div class="space-y-6">
					<div class="flex items-start gap-4">
						<div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" style="background:#ecfdf5;">
							<svg class="w-5 h-5" style="color:#10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
						</div>
						<div>
							<p class="text-gray-900 font-semibold"><?php echo $authorised ? 'Card authorised' : 'Payment received'; ?></p>
							<p class="text-sm text-gray-600"><?php echo $authorised ? 'A hold has been placed for the amount shown. Nothing is charged until a prescriber approves your treatment.' : 'Your payment is complete.'; ?></p>
						</div>
					</div>
					<div class="flex items-start gap-4">
						<div class="w-10 h-10 rounded-full bg-purple-600 text-white flex items-center justify-center flex-shrink-0 font-serif font-bold">2</div>
						<div>
							<p class="text-gray-900 font-semibold">Prescriber review</p>
							<p class="text-sm text-gray-600">A GPhC-registered pharmacist prescriber reviews your assessment in full &mdash; usually within 24 hours. We may email you if anything needs clarifying.<?php echo $authorised ? ' If your treatment cannot be approved, the hold is released in full and you pay nothing.' : ''; ?></p>
						</div>
					</div>
					<div class="flex items-start gap-4">
						<div class="w-10 h-10 rounded-full bg-purple-50 text-purple-700 flex items-center justify-center flex-shrink-0 font-serif font-bold">3</div>
						<div>
							<p class="text-gray-900 font-semibold">Discreet, tracked delivery</p>
							<p class="text-sm text-gray-600">Once approved<?php echo $authorised ? ' and the payment is taken' : ''; ?>, your medication is dispatched in temperature-controlled packaging &mdash; with you within 48 hours.</p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php
		/*
		 * Patient ID upload (CLAUDE.md engineering standards):
		 * fail-closed — when secure storage is unconfigured the form is
		 * replaced by "we'll email you a link" copy; there is no fallback
		 * upload path. Files never touch the Media Library.
		 */
		$id_module     = class_exists( 'TC_Secure_Docs' );
		$id_flag       = isset( $_GET['tc_id'] ) ? sanitize_key( wp_unslash( $_GET['tc_id'] ) ) : '';
		$id_uploaded   = $id_module && ( TC_Secure_Docs::has_document( $order ) || 'uploaded' === $id_flag );
		$id_available  = $id_module && TC_Secure_Docs::is_configured();
		$id_errors     = [
			'toobig'      => 'That file is too large — please upload an image or PDF under 10MB.',
			'type'        => 'That file type is not supported — please upload a JPG, PNG, WEBP or PDF.',
			'nofile'      => 'No file was received — please choose a file and try again.',
			'rate'        => 'Too many attempts — please wait a few minutes and try again.',
			'unavailable' => 'We could not save your document just now. Please try again shortly.',
		];
		?>
		<?php if ( $id_module ) : ?>
		<div class="max-w-3xl mx-auto mb-12">
			<div class="bg-white rounded-3xl border p-8 md:p-10 <?php echo $id_uploaded ? 'border-emerald-200' : 'border-amber-300'; ?>" <?php echo $id_uploaded ? '' : 'style="box-shadow:0 0 0 4px rgba(245,158,11,0.08);"'; ?>>
				<?php if ( $id_uploaded ) : ?>
					<div class="flex items-start gap-4">
						<div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0" style="background:#ecfdf5;">
							<svg class="w-5 h-5" style="color:#10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
						</div>
						<div>
							<h3 class="text-lg font-serif text-gray-900 mb-1">ID received &mdash; you're all set</h3>
							<p class="text-sm text-gray-600">Your identity document is stored securely and will only be seen by our pharmacy team as part of your clinical review.</p>
						</div>
					</div>
				<?php elseif ( $id_available ) : ?>
					<h3 class="text-lg font-serif text-gray-900 mb-2">One more step &mdash; upload your photo ID</h3>
					<p class="text-sm text-gray-600 mb-5">UK regulations require us to verify your identity before a prescriber can approve your treatment. A passport, driving licence or other photo ID works &mdash; JPG, PNG, WEBP or PDF, up to 10MB. It is stored securely, never publicly, and only our pharmacy team can view it.</p>
					<?php if ( $id_flag && isset( $id_errors[ $id_flag ] ) ) : ?>
						<p class="text-sm font-semibold text-red-600 mb-4"><?php echo esc_html( $id_errors[ $id_flag ] ); ?></p>
					<?php endif; ?>
					<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
						<input type="hidden" name="action" value="tc_upload_id" />
						<input type="hidden" name="order_id" value="<?php echo esc_attr( $order->get_id() ); ?>" />
						<input type="hidden" name="order_key" value="<?php echo esc_attr( $order->get_order_key() ); ?>" />
						<?php wp_nonce_field( 'tc_upload_id_' . $order->get_id(), 'tc_id_nonce' ); ?>
						<input type="file" name="tc_id_file" required accept="image/jpeg,image/png,image/webp,application/pdf" class="flex-1 text-sm text-gray-700 border border-gray-300 rounded-xl px-4 py-3 bg-gray-50" />
						<button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-7 py-3.5 rounded-xl transition-all whitespace-nowrap">Upload securely</button>
					</form>
				<?php else : ?>
					<h3 class="text-lg font-serif text-gray-900 mb-2">Identity verification</h3>
					<p class="text-sm text-gray-600">We can't accept document uploads right now &mdash; we'll email you a secure link to verify your identity shortly. Your order and prescriber review are unaffected.</p>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>

		<!-- Track your order / account access -->
		<div class="max-w-3xl mx-auto mb-12">
			<div class="rounded-3xl p-8 md:p-10 text-center" style="background:#8e88d0;">
				<h3 class="text-xl font-serif mb-3" style="color:#fdf8f4;">Track your order any time</h3>
				<?php if ( is_user_logged_in() ) : ?>
					<p class="text-sm mb-6" style="color:rgba(253,248,244,0.85);">Your account shows the live status of this order &mdash; from prescriber review through to dispatch and tracking.</p>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="inline-flex items-center gap-2 text-sm font-semibold px-7 py-3.5 rounded-xl transition-all hover:opacity-90" style="background:#fdf8f4;color:#8e88d0;">View my orders &rarr;</a>
				<?php else : ?>
					<p class="text-sm mb-6" style="color:rgba(253,248,244,0.85);">We created an account for you using <strong style="color:#fdf8f4;"><?php echo esc_html( $order->get_billing_email() ); ?></strong>. Set your password to log in and follow your order from prescriber review through to dispatch.</p>
					<a href="<?php echo esc_url( wp_lostpassword_url( wc_get_page_permalink( 'myaccount' ) ) ); ?>" class="inline-flex items-center gap-2 text-sm font-semibold px-7 py-3.5 rounded-xl transition-all hover:opacity-90" style="background:#fdf8f4;color:#8e88d0;">Set my password &rarr;</a>
				<?php endif; ?>
			</div>
		</div>

		<!-- Trust -->
		<div class="max-w-3xl mx-auto mb-12">
			<div class="flex flex-wrap items-center justify-center gap-x-8 gap-y-3">
				<span class="inline-flex items-center gap-2 text-sm text-gray-600"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>GPhC Regulated</span>
				<span class="inline-flex items-center gap-2 text-sm text-gray-600"><svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>100% Confidential</span>
				<span class="inline-flex items-center gap-2 text-sm text-gray-600"><svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>Tracked, discreet delivery</span>
			</div>
		</div>
		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>

		<!-- Order record (kept for reference; styled by theme prose/table rules) -->
		<div class="max-w-3xl mx-auto">
			<details class="bg-white rounded-2xl border border-gray-200 shadow-sm px-6 py-4 md:px-8 group">
				<summary class="cursor-pointer text-sm font-semibold text-gray-700 hover:text-purple-600 transition-colors list-none flex items-center justify-between">
					Order details &amp; billing address
					<svg class="w-4 h-4 text-gray-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
				</summary>
				<div class="pt-4">
					<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
				</div>
			</details>
		</div>

	<?php endif; ?>

<?php else : ?>

	<div class="max-w-2xl mx-auto text-center py-8">
		<h2 class="text-2xl md:text-3xl font-serif text-gray-900 mb-4">Thank you</h2>
		<p class="text-gray-600"><?php echo esc_html( apply_filters( 'woocommerce_thankyou_order_received_text', __( 'Thank you. Your order has been received.', 'woocommerce' ), null ) ); ?></p>
	</div>

<?php endif; ?>

</div>

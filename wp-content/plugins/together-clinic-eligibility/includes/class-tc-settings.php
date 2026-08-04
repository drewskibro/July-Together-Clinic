<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Settings {

	const MENU_SLUG = 'tc-eligibility-settings';
	const OPTION_GROUP = 'tc_eligibility';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_filter( 'plugin_action_links_' . TC_ELIGIBILITY_BASENAME, [ $this, 'plugin_action_links' ] );
		add_action( 'admin_notices', [ $this, 'guest_checkout_notice' ] );
	}

	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			'Eligibility Checker',
			'Eligibility',
			'manage_woocommerce',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	public function register_settings() {
		$opts = [
			'tc_eligibility_assessment_page_id',
			'tc_reorder_page_id',
			'tc_eligibility_from_email',
			'tc_eligibility_from_name',
			'tc_eligibility_clinician_recipients',
			'tc_eligibility_send_clinician_emails',
			'tc_eligibility_enforce_assessment_before_checkout',
			'tc_eligibility_block_direct_add_to_cart',
			'tc_eligibility_calendly_new',
			'tc_eligibility_calendly_switching',
			'tc_eligibility_calendly_returning',
			'tc_eligibility_min_bmi_default',
			'tc_eligibility_min_bmi_south_asian',
			'tc_eligibility_retention_days',
			TC_Variation_Map::OPTION_KEY,
		];

		foreach ( $opts as $opt ) {
			register_setting( self::OPTION_GROUP, $opt );
		}
	}

	public function plugin_action_links( $links ) {
		array_unshift( $links, sprintf(
			'<a href="%s">Settings</a>',
			esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) )
		) );
		return $links;
	}

	public function guest_checkout_notice() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'tc-eligibility' ) === false ) {
			if ( ! $screen || $screen->id !== 'plugins' ) {
				return;
			}
		}

		if ( get_option( 'woocommerce_enable_guest_checkout' ) !== 'no' ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>Together Clinic Eligibility:</strong> WooCommerce guest checkout is currently <em>disabled</em>. The runbook from the Superior Pharmacy build documents an orphan-payment race when guest checkout is off and the customer enters an existing email. Recommended: <a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=account' ) ) . '">enable guest checkout</a>.</p></div>';
	}

	public function render_page() {
		if ( isset( $_POST['tc_eligibility_save'] ) && check_admin_referer( 'tc_eligibility_save_settings' ) ) {
			$this->handle_save();
			echo '<div class="updated"><p>Settings saved.</p></div>';
		}

		if ( isset( $_POST['tc_eligibility_autodetect'] ) && check_admin_referer( 'tc_eligibility_autodetect' ) ) {
			$result = TC_Variation_Map::autodetect_from_skus();
			if ( $result['found'] === $result['expected'] ) {
				echo '<div class="updated"><p>Auto-detected all ' . (int) $result['found'] . ' product IDs from SKUs.</p></div>';
			} else {
				printf(
					'<div class="notice notice-warning"><p>Auto-detected %d of %d product IDs. Missing SKUs: <code>%s</code>. Fill these in manually below.</p></div>',
					(int) $result['found'],
					(int) $result['expected'],
					esc_html( implode( ', ', $result['missing'] ) )
				);
			}
		}

		$map               = TC_Variation_Map::all();
		$assessment_page_id = (int) get_option( 'tc_eligibility_assessment_page_id', 0 );
		$assessment_url    = $assessment_page_id ? get_permalink( $assessment_page_id ) : '';

		?>
		<div class="wrap">
			<h1>Together Clinic Eligibility</h1>

			<form method="post" action="">
				<?php wp_nonce_field( 'tc_eligibility_save_settings' ); ?>

				<h2>Pages</h2>
				<table class="form-table">
					<tr>
						<th><label for="tc_eligibility_assessment_page_id">Assessment page</label></th>
						<td>
							<?php
							wp_dropdown_pages( [
								'name'              => 'tc_eligibility_assessment_page_id',
								'selected'          => get_option( 'tc_eligibility_assessment_page_id', 0 ),
								'show_option_none'  => '— Select —',
								'option_none_value' => 0,
							] );
							?>
							<p class="description">The page containing the <code>[tc_eligibility_wizard]</code> shortcode. Patients are redirected here from the checkout if they don't have a completed assessment.</p>
							<?php if ( $assessment_url ) : ?>
								<p class="description"><strong>Public URL:</strong> <a href="<?php echo esc_url( $assessment_url ); ?>" target="_blank"><?php echo esc_html( $assessment_url ); ?></a> &mdash; point any "Check Eligibility" / "Start Your Assessment" CTAs site-wide to this URL, or use the <code>[tc_eligibility_button]</code> shortcode.</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><label for="tc_reorder_page_id">Reorder page</label></th>
						<td>
							<?php
							$reorder_page_id  = (int) get_option( 'tc_reorder_page_id', 0 );
							$reorder_page_url = $reorder_page_id ? get_permalink( $reorder_page_id ) : '';
							wp_dropdown_pages( [
								'name'              => 'tc_reorder_page_id',
								'selected'          => $reorder_page_id,
								'show_option_none'  => '— Select —',
								'option_none_value' => 0,
							] );
							?>
							<p class="description">The page containing the <code>[tc_reorder_form]</code> shortcode. Verified returning customers visiting the assessment page are redirected here. Append <code>?force_assessment=1</code> to the assessment URL to bypass the redirect.</p>
							<?php if ( $reorder_page_url ) : ?>
								<p class="description">
									<strong>Public URL:</strong> <a href="<?php echo esc_url( $reorder_page_url ); ?>" target="_blank"><?php echo esc_html( $reorder_page_url ); ?></a><br>
									<strong>Admin preview:</strong> <a href="<?php echo esc_url( add_query_arg( 'preview_reorder', '1', $reorder_page_url ) ); ?>" target="_blank" class="button button-secondary" style="margin-top:4px;">Preview reorder page →</a>
									<span style="display:block;margin-top:6px;color:#6b7280;">Preview mode bypasses the previous-order check using synthetic Wegovy 0.25mg data. Admin-only.</span>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				</table>

				<h2>Email</h2>
				<table class="form-table">
					<tr>
						<th><label for="tc_eligibility_from_email">From email</label></th>
						<td><input type="email" name="tc_eligibility_from_email" id="tc_eligibility_from_email" class="regular-text" value="<?php echo esc_attr( get_option( 'tc_eligibility_from_email', 'care@togetherclinic.co.uk' ) ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="tc_eligibility_from_name">From name</label></th>
						<td><input type="text" name="tc_eligibility_from_name" id="tc_eligibility_from_name" class="regular-text" value="<?php echo esc_attr( get_option( 'tc_eligibility_from_name', 'Together Clinic' ) ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="tc_eligibility_clinician_recipients">Clinician recipients</label></th>
						<td>
							<input type="text" name="tc_eligibility_clinician_recipients" id="tc_eligibility_clinician_recipients" class="large-text" value="<?php echo esc_attr( get_option( 'tc_eligibility_clinician_recipients', 'ahmed@at-health.co.uk,care@togetherclinic.co.uk' ) ); ?>" />
							<p class="description">Comma-separated list of email addresses for clinician notifications.</p>
						</td>
					</tr>
					<tr>
						<th><label for="tc_eligibility_send_clinician_emails">Send clinician emails</label></th>
						<td>
							<label><input type="checkbox" name="tc_eligibility_send_clinician_emails" id="tc_eligibility_send_clinician_emails" value="1" <?php checked( get_option( 'tc_eligibility_send_clinician_emails', '1' ), '1' ); ?> /> Enabled</label>
							<p class="description">Kill switch. Uncheck to immediately stop clinician notifications without a deploy.</p>
						</td>
					</tr>
				</table>

				<h2>Checkout enforcement</h2>
				<table class="form-table">
					<tr>
						<th>Redirect checkout → assessment</th>
						<td>
							<label><input type="checkbox" name="tc_eligibility_enforce_assessment_before_checkout" value="1" <?php checked( get_option( 'tc_eligibility_enforce_assessment_before_checkout', '1' ), '1' ); ?> /> Enabled</label>
							<p class="description">Patients without a completed assessment cookie are redirected from the checkout to the assessment page.</p>
						</td>
					</tr>
					<tr>
						<th>Block direct add-to-cart</th>
						<td>
							<label><input type="checkbox" name="tc_eligibility_block_direct_add_to_cart" value="1" <?php checked( get_option( 'tc_eligibility_block_direct_add_to_cart', '1' ), '1' ); ?> /> Enabled</label>
							<p class="description">Patients can't add Wegovy / Mounjaro to cart from the product page without going through the assessment first.</p>
						</td>
					</tr>
				</table>

				<h2>Calendly URLs</h2>
				<p class="description">Booking links shown on the thank-you page after order. Placeholders until the client provides the real URLs.</p>
				<table class="form-table">
					<tr><th>New patient</th><td><input type="url" name="tc_eligibility_calendly_new" class="large-text" value="<?php echo esc_attr( get_option( 'tc_eligibility_calendly_new', '' ) ); ?>" placeholder="https://calendly.com/..." /></td></tr>
					<tr><th>Switching provider</th><td><input type="url" name="tc_eligibility_calendly_switching" class="large-text" value="<?php echo esc_attr( get_option( 'tc_eligibility_calendly_switching', '' ) ); ?>" placeholder="https://calendly.com/..." /></td></tr>
					<tr><th>Returning customer</th><td><input type="url" name="tc_eligibility_calendly_returning" class="large-text" value="<?php echo esc_attr( get_option( 'tc_eligibility_calendly_returning', '' ) ); ?>" placeholder="https://calendly.com/..." /></td></tr>
				</table>

				<h2>Eligibility thresholds</h2>
				<table class="form-table">
					<tr>
						<th><label for="tc_eligibility_min_bmi_default">Minimum BMI (default)</label></th>
						<td><input type="number" step="0.1" name="tc_eligibility_min_bmi_default" id="tc_eligibility_min_bmi_default" value="<?php echo esc_attr( get_option( 'tc_eligibility_min_bmi_default', 27 ) ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="tc_eligibility_min_bmi_south_asian">Minimum BMI (South Asian)</label></th>
						<td><input type="number" step="0.1" name="tc_eligibility_min_bmi_south_asian" id="tc_eligibility_min_bmi_south_asian" value="<?php echo esc_attr( get_option( 'tc_eligibility_min_bmi_south_asian', 23 ) ); ?>" /></td>
					</tr>
				</table>

				<h2>Data retention</h2>
				<table class="form-table">
					<tr>
						<th><label for="tc_eligibility_retention_days">Retain abandoned assessments for (days)</label></th>
						<td>
							<input type="number" min="1" max="365" name="tc_eligibility_retention_days" id="tc_eligibility_retention_days" value="<?php echo esc_attr( get_option( 'tc_eligibility_retention_days', 30 ) ); ?>" />
							<p class="description">Submissions without a linked order are purged after this many days. Order-linked records are kept indefinitely.</p>
						</td>
					</tr>
				</table>

				<h2>Product IDs</h2>
				<p class="description">Map each treatment + dose to the WooCommerce product ID. If your products use the standard SKUs (<code>WG-0.25</code>, <code>MJ-2.5</code>, etc.), use auto-detect below. Otherwise, find the ID in <em>Products → All Products → hover over the product</em>.</p>
				<table class="form-table">
					<?php
					$expected_skus = TC_Variation_Map::expected_sku_map();
					foreach ( $map as $treatment => $doses ) : ?>
						<tr><th colspan="2"><strong><?php echo esc_html( ucfirst( $treatment ) ); ?></strong></th></tr>
						<?php foreach ( $doses as $dose => $variation_id ) :
							$expected_sku = $expected_skus[ $treatment ][ $dose ] ?? '';
							?>
							<tr>
								<th><label><?php echo esc_html( $dose ); ?></label></th>
								<td>
									<input type="number"
										   name="tc_variation_map[<?php echo esc_attr( $treatment ); ?>][<?php echo esc_attr( $dose ); ?>]"
										   value="<?php echo esc_attr( $variation_id ); ?>"
										   class="regular-text"
										   min="0" />
									<?php if ( $expected_sku ) : ?>
										<span class="description" style="margin-left: 12px;">Expected SKU: <code><?php echo esc_html( $expected_sku ); ?></code></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</table>

				<p class="submit"><button type="submit" name="tc_eligibility_save" class="button button-primary">Save settings</button></p>
			</form>

			<form method="post" action="" style="margin-top: -24px;">
				<?php wp_nonce_field( 'tc_eligibility_autodetect' ); ?>
				<button type="submit" name="tc_eligibility_autodetect" class="button">Auto-detect product IDs from SKUs</button>
			</form>

			<hr>
			<h2>Status</h2>
			<?php $this->render_status(); ?>
		</div>
		<?php
	}

	private function handle_save() {
		$strings = [
			'tc_eligibility_from_email',
			'tc_eligibility_from_name',
			'tc_eligibility_clinician_recipients',
			'tc_eligibility_calendly_new',
			'tc_eligibility_calendly_switching',
			'tc_eligibility_calendly_returning',
		];

		foreach ( $strings as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$value = wp_unslash( $_POST[ $key ] );
				update_option( $key, sanitize_text_field( $value ) );
			}
		}

		$ints = [
			'tc_eligibility_assessment_page_id',
			'tc_reorder_page_id',
			'tc_eligibility_retention_days',
		];
		foreach ( $ints as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_option( $key, (int) $_POST[ $key ] );
			}
		}

		$floats = [
			'tc_eligibility_min_bmi_default',
			'tc_eligibility_min_bmi_south_asian',
		];
		foreach ( $floats as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_option( $key, (float) $_POST[ $key ] );
			}
		}

		$checkboxes = [
			'tc_eligibility_send_clinician_emails',
			'tc_eligibility_enforce_assessment_before_checkout',
			'tc_eligibility_block_direct_add_to_cart',
		];
		foreach ( $checkboxes as $key ) {
			update_option( $key, isset( $_POST[ $key ] ) ? '1' : '0' );
		}

		if ( isset( $_POST['tc_variation_map'] ) && is_array( $_POST['tc_variation_map'] ) ) {
			TC_Variation_Map::save( wp_unslash( $_POST['tc_variation_map'] ) );
		}
	}

	private function render_status() {
		$counts = TC_DB::count_by_status();
		echo '<table class="widefat striped" style="max-width:600px"><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>';
		foreach ( [ 'partial', 'eligible', 'ineligible', 'order_placed' ] as $status ) {
			printf( '<tr><td>%s</td><td>%d</td></tr>', esc_html( $status ), (int) ( $counts[ $status ] ?? 0 ) );
		}
		echo '</tbody></table>';
	}
}

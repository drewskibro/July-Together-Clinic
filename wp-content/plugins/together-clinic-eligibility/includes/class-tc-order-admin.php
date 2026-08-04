<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Order_Admin {

	public function __construct() {
		add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'render' ], 20 );
	}

	public function render( $order ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		$elig_raw    = $order->get_meta( TC_Checkout::ORDER_META_RAW );
		$reorder_raw = $order->get_meta( '_rrqr_raw' );

		if ( ! $elig_raw && ! $reorder_raw ) {
			return;
		}

		$this->render_review_panel( $order );

		if ( $elig_raw ) {
			$payload = json_decode( $elig_raw, true );
			if ( is_array( $payload ) ) {
				$assessment_id = (string) $order->get_meta( TC_Checkout::ORDER_META_ASSESSMENT_ID );
				$path          = TC_ELIGIBILITY_PATH . 'templates/admin-order-meta.php';
				if ( file_exists( $path ) ) {
					include $path;
				}
			}
		}

		if ( $reorder_raw ) {
			$reorder_payload = json_decode( $reorder_raw, true );
			if ( is_array( $reorder_payload ) ) {
				$this->render_reorder_panel( $order, $reorder_payload );
			}
		}

		$this->render_flags( $order );
	}

	private function render_review_panel( WC_Order $order ) {
		$status = $order->get_status();

		if ( $status === TC_Review_Status::STATUS ) {
			?>
			<div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:12px 16px;margin:16px 0;clear:both;">
				<strong>Awaiting prescriber review &mdash; no payment has been taken.</strong>
				<p style="margin:8px 0 0;">Use the <em>Order actions</em> box: <strong>Approve &amp; send payment link</strong> or <strong>Reject &amp; cancel</strong>.
				To adjust the dose first, edit the line item above (pencil icon), recalculate, click Update, then approve &mdash; the payment link always charges the order's current total.</p>
			</div>
			<?php
			return;
		}

		$decision = (string) $order->get_meta( TC_Review_Actions::META_DECISION );
		if ( ! $decision ) {
			return;
		}

		$by = (string) $order->get_meta( TC_Review_Actions::META_DECIDED_BY );
		$at = (int) $order->get_meta( TC_Review_Actions::META_DECIDED_AT );

		$is_approved = ( $decision === 'approved' );
		?>
		<div style="background:<?php echo $is_approved ? '#dcfce7' : '#fee2e2'; ?>;border-left:4px solid <?php echo $is_approved ? '#16a34a' : '#ef4444'; ?>;padding:12px 16px;margin:16px 0;clear:both;">
			<strong><?php echo $is_approved ? 'Approved' : 'Rejected'; ?></strong>
			by <?php echo esc_html( $by ?: 'unknown' ); ?>
			<?php if ( $at ) : ?>
				on <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' H:i', $at ) ); ?>
			<?php endif; ?>
			<?php if ( $is_approved ) : ?>
				&mdash; payment link sent to the patient.
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_reorder_panel( WC_Order $order, array $payload ) {
		$previous_order_id = (int) $order->get_meta( '_rrqr_previous_order_id' );

		$rows = [
			'Medication'                    => ucfirst( (string) ( $payload['currentMedication'] ?? '' ) ),
			'Current dose (order history)'  => (string) ( $payload['currentDose'] ?? '' ),
			'Requested dose'                => (string) ( $payload['selectedDose'] ?? '' ),
			'Weight now'                    => ! empty( $payload['currentWeight'] ) ? $payload['currentWeight'] . ' kg' : '',
			'Lost weight since starting'    => (string) ( $payload['hasLostWeight'] ?? '' ),
			'Appetite suppression lasting'  => (string) ( $payload['appetiteLasting'] ?? '' ),
			'Side effects'                  => (string) ( $payload['hasSideEffects'] ?? '' ),
			'Health changed'                => (string) ( $payload['healthChanged'] ?? '' ),
			'New medications'               => (string) ( $payload['newMedications'] ?? '' ),
			'New medications list'          => (string) ( $payload['newMedicationsList'] ?? '' ),
			'Could be pregnant'             => (string) ( $payload['couldBePregnant'] ?? '' ),
			'Wants clinical support'        => (string) ( $payload['wantsClinicalSupport'] ?? '' ),
		];
		?>
		<h3 style="clear:both;">Reorder Check-in</h3>
		<?php if ( $previous_order_id ) : ?>
			<p><strong>Previous order:</strong>
				<?php $prev = wc_get_order( $previous_order_id ); ?>
				<?php if ( $prev ) : ?>
					<a href="<?php echo esc_url( $prev->get_edit_order_url() ); ?>">#<?php echo esc_html( $prev->get_order_number() ); ?></a>
				<?php else : ?>
					#<?php echo esc_html( $previous_order_id ); ?>
				<?php endif; ?>
			</p>
		<?php endif; ?>
		<?php foreach ( $rows as $label => $value ) : ?>
			<?php if ( $value === '' ) { continue; } ?>
			<p><strong><?php echo esc_html( $label ); ?>:</strong> <?php echo esc_html( $value ); ?></p>
		<?php endforeach; ?>
		<?php
		$assessment_id = (string) $order->get_meta( '_rrqr_assessment_id' );
		if ( $assessment_id ) {
			printf( '<p style="color:#6b7280;font-size:11px;margin-top:12px;">Reorder assessment ID: <code>%s</code></p>', esc_html( $assessment_id ) );
		}
	}

	private function render_flags( WC_Order $order ) {
		$flags = $order->get_meta( TC_Review_Status::FLAGS_META );

		if ( empty( $flags ) || ! is_array( $flags ) ) {
			return;
		}
		?>
		<div style="background:#fee2e2;border-left:4px solid #ef4444;padding:12px 16px;margin:16px 0;clear:both;">
			<strong>Review flags</strong>
			<ul style="margin:8px 0 0;padding-left:20px;">
				<?php foreach ( $flags as $key => $flag ) : ?>
					<li><?php echo esc_html( is_string( $key ) && ! is_numeric( $key ) ? $key . ': ' . ( is_scalar( $flag ) ? $flag : wp_json_encode( $flag ) ) : ( is_scalar( $flag ) ? $flag : wp_json_encode( $flag ) ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}

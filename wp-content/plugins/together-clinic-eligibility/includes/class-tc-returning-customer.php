<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Returning_Customer {

	const META_FLAG             = '_tc_returning_customer';
	const META_SINCE            = '_tc_returning_customer_since';
	const META_LAST_TREATMENT   = '_tc_last_treatment';
	const META_LAST_DOSE        = '_tc_last_dose';
	const META_LAST_ORDER_ID    = '_tc_last_order_id';
	const META_MATCH_SOURCE     = '_tc_match_source';
	const META_RECONCILED_EMAIL = '_tc_reconciled_email';

	const OPT_KILL_SWITCH = 'tc_use_returning_check';
	const CRON_HOOK = 'tc_reconcile_recent_orders';

	public static function init() {
		add_option( self::OPT_KILL_SWITCH, '1' );

		add_action( 'woocommerce_order_status_processing', [ __CLASS__, 'on_order_status_change' ], 10, 2 );
		add_action( 'woocommerce_order_status_completed',  [ __CLASS__, 'on_order_status_change' ], 10, 2 );
		add_action( 'woocommerce_order_status_on-hold',    [ __CLASS__, 'on_order_status_change' ], 10, 2 );
		add_action( 'woocommerce_thankyou',                [ __CLASS__, 'on_thankyou' ], 20, 1 );

		add_action( 'user_register', [ __CLASS__, 'on_user_register' ], 20, 1 );
		add_action( 'wp_login',      [ __CLASS__, 'on_wp_login' ], 20, 2 );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
		add_action( self::CRON_HOOK, [ __CLASS__, 'cron_reconcile_recent' ] );
	}

	public static function is_enabled() {
		return get_option( self::OPT_KILL_SWITCH, '1' ) === '1';
	}

	public static function is_returning( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( ! $user_id ) {
			return false;
		}
		if ( ! self::is_enabled() ) {
			return false;
		}
		return get_user_meta( $user_id, self::META_FLAG, true ) === '1';
	}

	public static function get_last_treatment( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( ! $user_id ) {
			return [ 'treatment' => '', 'dose' => '', 'order_id' => 0, 'match_source' => '' ];
		}
		return [
			'treatment'    => (string) get_user_meta( $user_id, self::META_LAST_TREATMENT, true ),
			'dose'         => (string) get_user_meta( $user_id, self::META_LAST_DOSE, true ),
			'order_id'     => (int)    get_user_meta( $user_id, self::META_LAST_ORDER_ID, true ),
			'match_source' => (string) get_user_meta( $user_id, self::META_MATCH_SOURCE, true ),
		];
	}

	public static function qualifying_variation_ids() {
		if ( ! class_exists( 'TC_Variation_Map' ) ) {
			return [];
		}
		return TC_Variation_Map::qualifying_variation_ids();
	}

	public static function on_order_status_change( $order_id, $order = null ) {
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}
		if ( $order ) {
			self::mark_from_order( $order, 'status_change' );
		}
	}

	public static function on_thankyou( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			self::mark_from_order( $order, 'thankyou' );
		}
	}

	public static function mark_from_order( WC_Order $order, $invoked_from = 'unknown' ) {
		$qualifying = self::qualifying_variation_ids();
		if ( empty( $qualifying ) ) {
			return;
		}

		$matched_id = 0;
		foreach ( $order->get_items() as $item ) {
			$vid = (int) $item->get_variation_id();
			$pid = (int) $item->get_product_id();
			foreach ( [ $vid, $pid ] as $candidate ) {
				if ( $candidate && in_array( $candidate, $qualifying, true ) ) {
					$matched_id = $candidate;
					break 2;
				}
			}
		}

		if ( ! $matched_id ) {
			return;
		}

		list( $treatment, $dose ) = self::resolve_treatment_dose( $matched_id );

		$user_id      = (int) $order->get_customer_id();
		$match_source = 'customer_id';

		if ( ! $user_id ) {
			$email = self::normalize_email( $order->get_billing_email() );
			if ( $email ) {
				$user = get_user_by( 'email', $email );
				if ( $user ) {
					$user_id      = (int) $user->ID;
					$match_source = 'billing_email';
				}
			}
		}

		if ( ! $user_id ) {
			TC_Log::info( 'returning_flag_skipped_no_user', [
				'order_id'    => $order->get_id(),
				'invoked_from' => $invoked_from,
			] );
			return;
		}

		self::write_flag( $user_id, $treatment, $dose, (int) $order->get_id(), $match_source, $invoked_from );
	}

	private static function write_flag( $user_id, $treatment, $dose, $order_id, $match_source, $invoked_from ) {
		$already = get_user_meta( $user_id, self::META_FLAG, true ) === '1';

		update_user_meta( $user_id, self::META_FLAG, '1' );
		if ( ! $already ) {
			update_user_meta( $user_id, self::META_SINCE, current_time( 'mysql' ) );
		}
		if ( $treatment ) update_user_meta( $user_id, self::META_LAST_TREATMENT, $treatment );
		if ( $dose )      update_user_meta( $user_id, self::META_LAST_DOSE, $dose );
		if ( $order_id )  update_user_meta( $user_id, self::META_LAST_ORDER_ID, (int) $order_id );
		update_user_meta( $user_id, self::META_MATCH_SOURCE, $match_source );

		TC_Log::info( 'returning_flag_set', [
			'user_id'      => $user_id,
			'treatment'    => $treatment,
			'dose'         => $dose,
			'order_id'     => $order_id,
			'match_source' => $match_source,
			'invoked_from' => $invoked_from,
			'already'      => $already ? 'yes' : 'no',
		] );
	}

	private static function resolve_treatment_dose( $matched_id ) {
		if ( ! class_exists( 'TC_Variation_Map' ) ) {
			return [ '', '' ];
		}
		foreach ( TC_Variation_Map::all() as $t => $doses ) {
			foreach ( $doses as $d => $vid ) {
				if ( (int) $vid === (int) $matched_id ) {
					return [ $t, $d ];
				}
			}
		}
		return [ '', '' ];
	}

	public static function on_user_register( $user_id ) {
		$user = get_userdata( $user_id );
		if ( $user && $user->user_email ) {
			self::reconcile_user( (int) $user_id, $user->user_email, 'user_register' );
		}
	}

	public static function on_wp_login( $user_login, $user ) {
		if ( ! $user || ! is_a( $user, 'WP_User' ) ) {
			return;
		}
		$reconciled = self::normalize_email( (string) get_user_meta( $user->ID, self::META_RECONCILED_EMAIL, true ) );
		$current    = self::normalize_email( $user->user_email );
		if ( $reconciled && $reconciled === $current ) {
			return;
		}
		self::reconcile_user( (int) $user->ID, $user->user_email, 'wp_login' );
	}

	public static function reconcile_user( $user_id, $email, $invoked_from = 'manual' ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return;
		}

		$normalized = self::normalize_email( $email );
		if ( ! $normalized ) {
			return;
		}

		$statuses = apply_filters( 'tc_returning_customer_order_statuses', [ 'completed', 'processing', 'on-hold' ] );

		try {
			$orders = wc_get_orders( [
				'billing_email' => $normalized,
				'status'        => $statuses,
				'limit'         => 50,
				'orderby'       => 'date',
				'order'         => 'DESC',
				'type'          => 'shop_order',
			] );
		} catch ( Exception $e ) {
			TC_Log::warn( 'reconcile_user_failed', [ 'error' => $e->getMessage() ] );
			$orders = [];
		}

		$qualifying = self::qualifying_variation_ids();
		foreach ( $orders as $order ) {
			$matched_id = 0;
			foreach ( $order->get_items() as $item ) {
				$vid = (int) $item->get_variation_id();
				$pid = (int) $item->get_product_id();
				foreach ( [ $vid, $pid ] as $candidate ) {
					if ( $candidate && in_array( $candidate, $qualifying, true ) ) {
						$matched_id = $candidate;
						break 2;
					}
				}
			}
			if ( ! $matched_id ) {
				continue;
			}

			list( $treatment, $dose ) = self::resolve_treatment_dose( $matched_id );
			self::write_flag( $user_id, $treatment, $dose, (int) $order->get_id(), 'reconcile_by_email', $invoked_from );

			if ( ! $order->get_customer_id() ) {
				$order->set_customer_id( $user_id );
				$order->save();
			}
			break;
		}

		update_user_meta( $user_id, self::META_RECONCILED_EMAIL, $normalized );
	}

	public static function cron_reconcile_recent() {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return;
		}
		$statuses = apply_filters( 'tc_returning_customer_order_statuses', [ 'completed', 'processing', 'on-hold' ] );
		try {
			$orders = wc_get_orders( [
				'status'       => $statuses,
				'date_created' => '>' . ( time() - 2 * DAY_IN_SECONDS ),
				'limit'        => 200,
				'type'         => 'shop_order',
			] );
		} catch ( Exception $e ) {
			return;
		}
		foreach ( $orders as $order ) {
			self::mark_from_order( $order, 'cron_safety_net' );
		}
	}

	public static function normalize_email( $email ) {
		$email = strtolower( trim( (string) $email ) );
		if ( $email === '' || ! is_email( $email ) ) {
			return '';
		}
		if ( preg_match( '/^(.+)@(gmail\.com|googlemail\.com)$/i', $email, $m ) ) {
			$local = preg_replace( '/\+.*$/', '', $m[1] );
			$local = str_replace( '.', '', $local );
			$email = $local . '@gmail.com';
		}
		return $email;
	}
}

TC_Returning_Customer::init();

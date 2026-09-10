<?php
/**
 * Plugin Name: OligoPoly Manual Payment Checkout
 * Description: Customer-facing layer for manual/offline payment orders (ACH / Bank Transfer). Adds payment-required messaging to the Order Received page and customer email, a pre-submit checkout notice, an "Awaiting Payment Verification" front-end label for on-hold orders, and an internal admin note prompting payment verification. Order status handling itself stays native WooCommerce.
 * Version:     1.0.0
 * Author:      OligoPoly Laboratories
 * License:     GPL-2.0-or-later
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 11.1
 *
 * WHAT THIS PLUGIN DELIBERATELY DOES NOT DO
 * -----------------------------------------
 * - It never calls $order->set_paid( true ) for a manual gateway.
 * - It never moves an order out of on-hold. That transition is manual, by staff, in wp-admin.
 * - It never registers a custom order status. The stored slug stays `wc-on-hold`.
 * - It contains no bank account numbers, routing numbers, or payment handles. Those live in
 *   the native BACS gateway settings (WooCommerce > Settings > Payments > Direct Bank Transfer),
 *   which WooCommerce renders only in post-checkout and order contexts.
 * - It does not auto-cancel unpaid orders. See docs/MANUAL-PAYMENT-CHECKOUT.md for how that
 *   would be added later, once approved.
 *
 * @package OligoPoly_Manual_Payments
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'OLIGOPOLY_OMP_VERSION' ) ) {
	define( 'OLIGOPOLY_OMP_VERSION', '1.0.0' );
}

/**
 * Customer-facing layer for manual-verification payment methods.
 */
final class OligoPoly_Manual_Payments {

	/**
	 * Boot the plugin once WooCommerce is known to be present.
	 */
	public static function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Phase 4 — Order Received page.
		add_action( 'woocommerce_before_thankyou', array( __CLASS__, 'render_thankyou_notice' ), 10, 1 );

		// Phase 12 — customer transactional email.
		add_action( 'woocommerce_email_before_order_table', array( __CLASS__, 'render_email_notice' ), 10, 4 );

		// Phase 6 — concise notice above the Place Order button.
		add_action( 'woocommerce_review_order_before_submit', array( __CLASS__, 'render_checkout_notice' ), 10 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_checkout_script' ), 20 );

		// Phase 5 — front-end wording only. The stored status slug is untouched.
		add_filter( 'wc_order_statuses', array( __CLASS__, 'filter_front_end_status_label' ), 10, 1 );

		// Phase 8 — internal note so staff know to verify before advancing the order.
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'add_verification_admin_note' ), 20, 3 );

		// Presentation only — scoped to our own markup.
		add_action( 'wp_head', array( __CLASS__, 'render_styles' ), 20 );
	}

	/**
	 * Payment gateways whose orders require manual payment verification.
	 *
	 * Phase 11 — adding another offline method later (Venmo, Cash App, Zelle) is a one-line
	 * filter, and it inherits the same checkout -> on-hold -> verification -> processing flow:
	 *
	 *     add_filter( 'oligopoly_manual_payment_gateways', function ( $ids ) {
	 *         $ids[] = 'cashapp';
	 *         return $ids;
	 *     } );
	 *
	 * @return string[] Gateway IDs.
	 */
	public static function manual_gateways() {
		$gateways = apply_filters( 'oligopoly_manual_payment_gateways', array( 'bacs' ) );

		return array_values( array_unique( array_filter( array_map( 'strval', (array) $gateways ) ) ) );
	}

	/**
	 * Whether an order was placed with a manual method and is still awaiting verification.
	 *
	 * @param WC_Order|mixed $order Order object.
	 * @return bool
	 */
	public static function order_awaits_verification( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		if ( ! in_array( $order->get_payment_method(), self::manual_gateways(), true ) ) {
			return false;
		}

		// Already-paid online methods never reach this branch, and a verified order that has
		// moved to processing/completed stops showing payment-required messaging.
		return $order->has_status( array( 'on-hold', 'pending' ) );
	}

	/**
	 * Phase 4 — "PAYMENT REQUIRED" block on the Order Received page.
	 *
	 * Runs before the gateway's own instructions so the framing leads and the bank details
	 * (rendered by native BACS) follow it.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function render_thankyou_notice( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! self::order_awaits_verification( $order ) ) {
			return;
		}

		$order_number = $order->get_order_number();

		?>
		<section class="oligopoly-omp-panel" aria-labelledby="oligopoly-omp-heading">
			<h2 id="oligopoly-omp-heading" class="oligopoly-omp-panel__title"><?php esc_html_e( 'PAYMENT REQUIRED', 'oligopoly-manual-payments' ); ?></h2>

			<p class="oligopoly-omp-panel__lede">
				<?php
				printf(
					/* translators: %s: order number. */
					esc_html__( 'Order #%s has been reserved.', 'oligopoly-manual-payments' ),
					esc_html( $order_number )
				);
				?>
			</p>

			<ol class="oligopoly-omp-panel__steps">
				<li><?php esc_html_e( 'Complete your selected payment method.', 'oligopoly-manual-payments' ); ?></li>
				<li>
					<?php
					printf(
						/* translators: %s: order number. */
						esc_html__( 'Include Order #%s as the payment reference.', 'oligopoly-manual-payments' ),
						esc_html( $order_number )
					);
					?>
				</li>
				<li><?php esc_html_e( 'Allow the transaction to clear.', 'oligopoly-manual-payments' ); ?></li>
				<li><?php esc_html_e( 'OligoPoly will verify the payment.', 'oligopoly-manual-payments' ); ?></li>
				<li><?php esc_html_e( 'You will receive confirmation when your order moves to Processing.', 'oligopoly-manual-payments' ); ?></li>
			</ol>

			<p class="oligopoly-omp-panel__warning">
				<?php esc_html_e( 'Do not submit payment twice if the original transaction is still pending.', 'oligopoly-manual-payments' ); ?>
			</p>
		</section>
		<?php
	}

	/**
	 * Phase 12 — the same payment-required framing in the customer email.
	 *
	 * Admin copies are skipped; staff get the internal note instead.
	 *
	 * @param WC_Order      $order         Order object.
	 * @param bool          $sent_to_admin Whether this copy goes to an administrator.
	 * @param bool          $plain_text    Whether the email is plain text.
	 * @param WC_Email|null $email         Email object.
	 */
	public static function render_email_notice( $order, $sent_to_admin, $plain_text = false, $email = null ) {
		unset( $email );

		if ( $sent_to_admin || ! self::order_awaits_verification( $order ) ) {
			return;
		}

		$order_number = $order->get_order_number();

		/* translators: %s: order number. */
		$reference = sprintf( __( 'Use Order #%s as the payment reference or memo.', 'oligopoly-manual-payments' ), $order_number );

		$lines = array(
			__( 'Thank you for your order.', 'oligopoly-manual-payments' ),
			$reference,
			__( 'Your order will remain Awaiting Payment Verification until the funds have cleared.', 'oligopoly-manual-payments' ),
			__( 'Orders are not packed or shipped before payment verification.', 'oligopoly-manual-payments' ),
			__( 'You will receive another email when payment has been verified.', 'oligopoly-manual-payments' ),
			__( 'Do not submit a duplicate payment if the original transaction is still pending.', 'oligopoly-manual-payments' ),
		);

		if ( $plain_text ) {
			echo "\n" . esc_html__( 'PAYMENT REQUIRED', 'oligopoly-manual-payments' ) . "\n\n";

			foreach ( $lines as $line ) {
				echo esc_html( $line ) . "\n";
			}

			echo "\n----------------------------------------\n\n";

			return;
		}

		echo '<div style="border:2px solid #6b21a8;border-radius:6px;padding:16px;margin:0 0 24px;">';
		echo '<p style="margin:0 0 12px;font-weight:700;letter-spacing:.06em;color:#6b21a8;">' . esc_html__( 'PAYMENT REQUIRED', 'oligopoly-manual-payments' ) . '</p>';

		foreach ( $lines as $line ) {
			echo '<p style="margin:0 0 8px;">' . esc_html( $line ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Phase 6 — concise support text above the Place Order button.
	 *
	 * Rendered only when a manual gateway is actually available, and revealed by the inline
	 * script below only while such a gateway is the selected method.
	 */
	public static function render_checkout_notice() {
		if ( ! self::manual_gateway_available() ) {
			return;
		}

		printf(
			'<p class="oligopoly-omp-checkout-notice" style="display:none;">%s</p>',
			esc_html__( 'Submitting your order reserves inventory but does not indicate that payment has been received. Orders ship only after payment verification.', 'oligopoly-manual-payments' )
		);
	}

	/**
	 * Whether at least one manual gateway is available to the customer right now.
	 *
	 * @return bool
	 */
	private static function manual_gateway_available() {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return false;
		}

		$available = array_keys( WC()->payment_gateways()->get_available_payment_gateways() );

		return (bool) array_intersect( $available, self::manual_gateways() );
	}

	/**
	 * Show the pre-submit notice only while a manual method is selected.
	 *
	 * Attaches to WooCommerce's own classic-checkout handle, so it loads only where that
	 * script loads and inherits its jQuery dependency.
	 */
	public static function enqueue_checkout_script() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}

		if ( ! wp_script_is( 'wc-checkout', 'enqueued' ) ) {
			return;
		}

		wp_localize_script(
			'wc-checkout',
			'OligoPolyOMP',
			array( 'manualGateways' => self::manual_gateways() )
		);

		// Nowdoc: no PHP interpolation, so the JS below is emitted verbatim.
		$script = <<<'JS'
( function ( $ ) {
	if ( ! $ || typeof window.OligoPolyOMP === 'undefined' ) { return; }
	var manual = window.OligoPolyOMP.manualGateways || [];
	function syncNotice() {
		var selected = $( 'input[name="payment_method"]:checked' ).val() || '';
		$( '.oligopoly-omp-checkout-notice' ).toggle( manual.indexOf( selected ) !== -1 );
	}
	$( document.body ).on( 'updated_checkout payment_method_selected', syncNotice );
	$( function () { syncNotice(); } );
} )( window.jQuery );
JS;

		wp_add_inline_script( 'wc-checkout', $script );
	}

	/**
	 * Phase 5 — describe on-hold as "Awaiting Payment Verification" for customers only.
	 *
	 * The database slug stays `wc-on-hold`, and wp-admin keeps WooCommerce's own wording so the
	 * staff workflow in Phase 8 reads exactly as documented.
	 *
	 * @param array $statuses Status slug => label.
	 * @return array
	 */
	public static function filter_front_end_status_label( $statuses ) {
		if ( is_admin() || ! isset( $statuses['wc-on-hold'] ) ) {
			return $statuses;
		}

		$statuses['wc-on-hold'] = _x( 'Awaiting Payment Verification', 'Order status', 'oligopoly-manual-payments' );

		return $statuses;
	}

	/**
	 * Phase 8 — private order note reminding staff to verify funds before advancing.
	 *
	 * The third argument is passed by value as an order object on modern WooCommerce; the
	 * lookup below tolerates either shape.
	 *
	 * @param int            $order_id Order ID.
	 * @param array          $posted   Posted checkout data.
	 * @param WC_Order|mixed $order    Order object.
	 */
	public static function add_verification_admin_note( $order_id, $posted, $order = null ) {
		unset( $posted );

		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! self::order_awaits_verification( $order ) ) {
			return;
		}

		// Third argument false => private note, visible to staff only, never emailed.
		$order->add_order_note(
			__( 'Verify cleared payment before moving this order to Processing.', 'oligopoly-manual-payments' ),
			0,
			false
		);
	}

	/**
	 * Scoped styles for the Order Received panel.
	 *
	 * Every declaration is namespaced under .oligopoly-omp-, so nothing else on the site is
	 * affected. Colours follow the existing black/purple system with plain fallbacks.
	 */
	public static function render_styles() {
		if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
			return;
		}

		?>
<style id="oligopoly-omp-styles">
.oligopoly-omp-panel{border:2px solid #6b21a8;border-radius:8px;padding:1.25rem 1.5rem;margin:0 0 2rem;background:#0b0b0d;color:#f4f4f5;}
.oligopoly-omp-panel__title{margin:0 0 .75rem;font-size:1.05rem;letter-spacing:.08em;text-transform:uppercase;color:#c084fc;}
.oligopoly-omp-panel__lede{margin:0 0 1rem;font-weight:600;}
.oligopoly-omp-panel__steps{margin:0 0 1rem;padding-left:1.35rem;}
.oligopoly-omp-panel__steps li{margin:0 0 .4rem;}
.oligopoly-omp-panel__warning{margin:0;font-weight:600;color:#f0abfc;}
.oligopoly-omp-checkout-notice{margin:0 0 1rem;font-size:.875rem;line-height:1.5;opacity:.85;}
@media (max-width:480px){.oligopoly-omp-panel{padding:1rem;}}
</style>
		<?php
	}
}

add_action( 'plugins_loaded', array( 'OligoPoly_Manual_Payments', 'init' ) );

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage.
 *
 * This plugin reads orders only through the CRUD API, so it is HPOS-safe either way.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

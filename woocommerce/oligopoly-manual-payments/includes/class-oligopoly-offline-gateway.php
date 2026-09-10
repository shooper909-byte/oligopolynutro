<?php
/**
 * Base class for OligoPoly offline / manual-verification payment gateways.
 *
 * Modelled on WC_Gateway_BACS, with one deliberate difference: this class NEVER calls
 * payment_complete() or set_paid( true ), not even for a zero-total order. Every order placed
 * through a subclass lands in `on-hold` and stays there until a staff member advances it by hand.
 *
 * Payment handles (a Venmo @username, a Cash App $cashtag) are stored as gateway settings in the
 * database, exactly like the native BACS account table. They are never hard-coded here, and they
 * are rendered only in order and email contexts — never in publicly crawlable content.
 *
 * @package OligoPoly_Manual_Payments
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shared behaviour for OligoPoly's manual-verification gateways.
 */
abstract class OligoPoly_Offline_Gateway extends WC_Payment_Gateway {

	/**
	 * Label for the handle field, e.g. "Venmo username".
	 *
	 * @var string
	 */
	protected $handle_label = '';

	/**
	 * Placeholder shown in the handle field.
	 *
	 * @var string
	 */
	protected $handle_placeholder = '';

	/**
	 * Set up settings, and hook the customer-facing instructions.
	 */
	public function __construct() {
		$this->has_fields         = false;
		$this->supports           = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 20, 3 );
	}

	/**
	 * Admin settings. Subclasses supply their own defaults via default_settings().
	 */
	public function init_form_fields() {
		$defaults = $this->default_settings();

		$this->form_fields = array(
			'enabled'        => array(
				'title'   => __( 'Enable/Disable', 'oligopoly-manual-payments' ),
				'type'    => 'checkbox',
				'label'   => sprintf(
					/* translators: %s: gateway name. */
					__( 'Enable %s', 'oligopoly-manual-payments' ),
					$this->method_title
				),
				'default' => 'no',
			),
			'title'          => array(
				'title'       => __( 'Title', 'oligopoly-manual-payments' ),
				'type'        => 'text',
				'description' => __( 'The name the customer sees at checkout.', 'oligopoly-manual-payments' ),
				'default'     => $defaults['title'],
				'desc_tip'    => true,
			),
			'description'    => array(
				'title'       => __( 'Description', 'oligopoly-manual-payments' ),
				'type'        => 'textarea',
				'description' => __( 'Shown at checkout when this method is selected.', 'oligopoly-manual-payments' ),
				'default'     => $defaults['description'],
			),
			'payment_handle' => array(
				'title'       => $this->handle_label,
				'type'        => 'text',
				'description' => __( 'The account the customer sends payment to. Stored in settings, shown only on the Order Received page, the My Account order view, and order emails.', 'oligopoly-manual-payments' ),
				'default'     => '',
				'placeholder' => $this->handle_placeholder,
			),
			'instructions'   => array(
				'title'       => __( 'Instructions', 'oligopoly-manual-payments' ),
				'type'        => 'textarea',
				'description' => __( 'Shown on the Order Received page, the My Account order view, and in the customer email.', 'oligopoly-manual-payments' ),
				'default'     => $defaults['instructions'],
			),
		);
	}

	/**
	 * Default title / description / instructions for this gateway.
	 *
	 * @return array{title:string,description:string,instructions:string}
	 */
	abstract protected function default_settings();

	/**
	 * The configured payment handle.
	 *
	 * @return string
	 */
	public function get_payment_handle() {
		return trim( (string) $this->get_option( 'payment_handle' ) );
	}

	/**
	 * Hide the gateway until an account handle has actually been configured.
	 *
	 * Without this, enabling the gateway before filling in the handle would offer customers a
	 * payment method with nowhere to send the money.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( '' === $this->get_payment_handle() ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * Place the order on hold and reserve stock. Never marks the order paid.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return array( 'result' => 'failure' );
		}

		/*
		 * Deliberately unconditional. WC_Gateway_BACS calls payment_complete() when the total is
		 * zero; this gateway does not, because a manual method must never self-verify. A
		 * zero-total order simply waits for staff like any other.
		 */
		$order->update_status(
			'on-hold',
			sprintf(
				/* translators: %s: gateway title. */
				__( 'Awaiting %s payment verification.', 'oligopoly-manual-payments' ),
				$this->method_title
			)
		);

		// Same reservation behaviour as BACS.
		wc_reduce_stock_levels( $order_id );

		if ( function_exists( 'WC' ) && WC()->cart ) {
			WC()->cart->empty_cart();
		}

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Instructions on the Order Received page.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order ) {
			$this->render_instructions( $order );
		}
	}

	/**
	 * Instructions in the customer email.
	 *
	 * @param WC_Order $order         Order object.
	 * @param bool     $sent_to_admin Whether this copy goes to an administrator.
	 * @param bool     $plain_text    Whether the email is plain text.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $sent_to_admin || ! $order instanceof WC_Order ) {
			return;
		}

		if ( $order->get_payment_method() !== $this->id || ! $order->has_status( array( 'on-hold', 'pending' ) ) ) {
			return;
		}

		$this->render_instructions( $order, $plain_text );
	}

	/**
	 * Render instructions, the payment handle, and the order-number reference.
	 *
	 * @param WC_Order $order      Order object.
	 * @param bool     $plain_text Whether to render as plain text.
	 */
	public function render_instructions( $order, $plain_text = false ) {
		$instructions = trim( (string) $this->instructions );
		$handle       = $this->get_payment_handle();
		$order_number = $order->get_order_number();

		/* translators: %s: order number. */
		$reference = sprintf( __( 'Payment reference / memo: Order #%s', 'oligopoly-manual-payments' ), $order_number );

		if ( $plain_text ) {
			if ( '' !== $instructions ) {
				echo esc_html( wp_strip_all_tags( wptexturize( $instructions ) ) ) . "\n\n";
			}

			if ( '' !== $handle ) {
				/* translators: 1: handle label, 2: payment handle. */
				echo esc_html( sprintf( __( '%1$s: %2$s', 'oligopoly-manual-payments' ), $this->handle_label, $handle ) ) . "\n";
			}

			echo esc_html( $reference ) . "\n\n";

			return;
		}

		echo '<section class="oligopoly-omp-instructions">';

		if ( '' !== $instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $instructions ) ) );
		}

		echo '<ul class="oligopoly-omp-instructions__facts">';

		if ( '' !== $handle ) {
			printf(
				'<li><strong>%1$s:</strong> <span class="oligopoly-omp-handle">%2$s</span></li>',
				esc_html( $this->handle_label ),
				esc_html( $handle )
			);
		}

		printf( '<li><strong>%s</strong></li>', esc_html( $reference ) );

		echo '</ul>';
		echo '</section>';
	}
}

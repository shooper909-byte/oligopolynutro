<?php
/**
 * Venmo — manual-verification payment gateway.
 *
 * @package OligoPoly_Manual_Payments
 */

defined( 'ABSPATH' ) || exit;

/**
 * Venmo offline gateway.
 */
class OligoPoly_Gateway_Venmo extends OligoPoly_Offline_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'oligopoly_venmo';
		$this->method_title       = __( 'Venmo', 'oligopoly-manual-payments' );
		$this->method_description = __( 'Take Venmo payments. The order is created as On hold, stock is reserved, and the order stays On hold until a staff member verifies the payment has cleared. Enter the receiving Venmo username below — it is stored in settings, never in code.', 'oligopoly-manual-payments' );
		$this->handle_label       = __( 'Venmo username', 'oligopoly-manual-payments' );
		$this->handle_placeholder = '@example-lab';

		parent::__construct();
	}

	/**
	 * Default copy.
	 *
	 * @return array{title:string,description:string,instructions:string}
	 */
	protected function default_settings() {
		return array(
			'title'        => __( 'Venmo', 'oligopoly-manual-payments' ),
			'description'  => __( 'Pay by Venmo. Submit your order to receive payment instructions. Your order will be reserved and will remain awaiting payment verification until payment has cleared and been confirmed.', 'oligopoly-manual-payments' ),
			'instructions' => __( "Thank you for your order.\n\nPlease send your Venmo payment to the account shown below.\n\nIMPORTANT: Use your OligoPoly order number as the payment note.\n\nYour order will remain Awaiting Payment Verification until the payment has cleared.\n\nOrders are not packed or shipped before payment verification.\n\nYou will receive another email when payment has been verified.\n\nDo not submit a duplicate payment if the original transaction is still pending.", 'oligopoly-manual-payments' ),
		);
	}
}

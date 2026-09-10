<?php
/**
 * Cash App — manual-verification payment gateway.
 *
 * @package OligoPoly_Manual_Payments
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cash App offline gateway.
 */
class OligoPoly_Gateway_CashApp extends OligoPoly_Offline_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'oligopoly_cashapp';
		$this->method_title       = __( 'Cash App', 'oligopoly-manual-payments' );
		$this->method_description = __( 'Take Cash App payments. The order is created as On hold, stock is reserved, and the order stays On hold until a staff member verifies the payment has cleared. Enter the receiving $cashtag below — it is stored in settings, never in code. This is an offline gateway and is unrelated to Cash App Pay via Square.', 'oligopoly-manual-payments' );
		$this->handle_label       = __( 'Cashtag', 'oligopoly-manual-payments' );
		$this->handle_placeholder = '$ExampleLab';

		parent::__construct();
	}

	/**
	 * Default copy.
	 *
	 * @return array{title:string,description:string,instructions:string}
	 */
	protected function default_settings() {
		return array(
			'title'        => __( 'Cash App', 'oligopoly-manual-payments' ),
			'description'  => __( 'Pay by Cash App. Submit your order to receive payment instructions. Your order will be reserved and will remain awaiting payment verification until payment has cleared and been confirmed.', 'oligopoly-manual-payments' ),
			'instructions' => __( "Thank you for your order.\n\nPlease send your Cash App payment to the cashtag shown below.\n\nIMPORTANT: Use your OligoPoly order number as the payment note.\n\nYour order will remain Awaiting Payment Verification until the payment has cleared.\n\nOrders are not packed or shipped before payment verification.\n\nYou will receive another email when payment has been verified.\n\nDo not submit a duplicate payment if the original transaction is still pending.", 'oligopoly-manual-payments' ),
		);
	}
}

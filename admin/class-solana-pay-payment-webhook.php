<?php
/**
 * Backend webhook for handling payment info with the frontend.
 *
 * @package AZTemi\Solana_Pay_for_WC
 */

namespace AZTemi\Solana_Pay_for_WC;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


class Webhook {

	/**
	 * Handle instance of the payment gateway class.
	 *
	 * @var Solana_Pay_GW
	 */
	protected $hGateway;


	/**
	 * Handle instance of a class wrapping user session plugin data.
	 *
	 * @var Session
	 */
	protected $hSession;


	public function __construct( $gateway, $session ) {

		$this->hGateway = $gateway;
		$this->hSession = $session;
		$this->register_hooks();

	}


	/**
	 * Register an API endpoint action to handle webhook GET request
	 */
	private function register_hooks() {

		add_action( 'woocommerce_api_' . PLUGIN_ID, array( $this, 'handle_webhook_request' ) );

	}


	/**
	 * Get payment-related details of specified order
	 */
	private function get_order_details( $order_id, &$data ) {

		$order = wc_get_order( $order_id );
		if ( ! $order || ! current_user_can( 'pay_for_order', $order_id ) ) {
			return;
		}

		$prev_data = $this->hSession->get_data();
		if ( isset( $prev_data['order_id'] ) && ( $order_id === $prev_data['order_id'] ) ) {
			$data['reference'] = $prev_data['reference'];
		}

		$data['memo'] = "OrderId#{$order_id}";
		$data['amount'] = (float) $order->get_total();
		$data['currency'] = $order->get_currency();
		$data['order_id'] = $order_id;

	}


	/**
	 * Get payment-related details of checkout cart
	 */
	private function get_cart_details( $cart_created, &$data ) {

		$cart_hash = WC()->cart->get_cart_hash();
		if ( ! $cart_hash ) {
			return;
		}

		$prev_data = $this->hSession->get_data();
		if (
			isset( $prev_data['cart_hash'] ) && ( $cart_hash === $prev_data['cart_hash'] ) &&
			isset( $prev_data['cart_created'] ) && ( $cart_created === $prev_data['cart_created'] )
			) {
				$data['reference'] = $prev_data['reference'];
		}

		$data['memo'] = "@{$cart_created}";
		$data['amount'] = (float) WC()->cart->get_total('edit');
		$data['currency'] = get_woocommerce_currency_symbol( Solana_Tokens::get_store_currency('edit') );
		$data['cart_hash'] = $cart_hash;
		$data['cart_created'] = $cart_created;

	}


	/**
	 * Handle incoming webhook GET request.
	 */
	public function handle_webhook_request() {

		// validate incoming params
		$ref = isset( $_GET['ref'] ) ? trim( wc_clean( wp_unslash( $_GET['ref'] ) ) ) : '';
		$order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : false;
		$cart_created = isset( $_GET['cart_created'] ) ? trim( wc_clean( wp_unslash( $_GET['cart_created'] ) ) ) : '';

		if ( empty( $ref ) ) {
			return;
		}

		// default return data
		$data = array(
			'reference' => $ref,
			'testmode'  => $this->hGateway->get_testmode(),
			'recipient' => $this->hGateway->get_merchant_wallet_address(),
			'endpoint'  => $this->hGateway->get_rpc_endpoint(),
			'label'     => esc_html( $this->hGateway->get_brand_name() ),
			'message'   => esc_html__( 'Thank you for your order', 'solana-pay-for-woocommerce' ),
		);

		// Add order or checkout cart details that are useful for payment in the frontend
		if ( $order_id ) {
			$this->get_order_details( $order_id, $data );

		} elseif ( WC() && WC()->cart && ! empty( $cart_created ) ) {
			$this->get_cart_details( $cart_created, $data );

		} else {
			return;
		}

		// Add acceptable Solana tokens options for payment and their rates
		$options = $this->hGateway->get_accepted_solana_tokens_payment_options( $data['amount'] );
		$data = array_merge( $data, $options );

		// store the data in user session for later use during payment processing
		$this->hSession->set_data( $data );

		// send response
		header( 'HTTP/1.1 200 OK' );
		wp_send_json( $data );
		die();

	}

}

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
	 * Get Solana tokens available for payments and calculate how much the cost of the order in each token.
	 */
	private function get_payment_token_options( &$data ) {

		$tokens = $this->hGateway->get_accepted_solana_tokens();
		$table = $this->hGateway->get_tokens_table();

		$amount = $data['amount'];
		$data['tokens'] = array();

		foreach ( $tokens as $k => $v ) {
			if ( array_key_exists( $k, $table ) && $table[ $k ]['enabled'] ) {
				$decimals = $tokens[ $k ]['decimals'];
				$power = bcpow( '10', $decimals );
				$amount_pow = bcmul( $amount, $power );
				$rate = bcmul( $amount_pow, $table[ $k ]['rate'] );
				$fee = bcdiv( bcmul( $rate, $table[ $k ]['fee'] ), '100' );
				$data['tokens'][ $k ] = rtrim( bcdiv( bcadd( $rate, $fee ), $power, $decimals ), '0' );
			}
		}

	}


	/**
	 * Get the order total in specified Solana payment tokens.
	 *
	 * @param  float  $amount   Order cost in store base currency.
	 * @param  string $token_id Token ID.
	 * @return string Expected payment amount as a BC Math string.
	 */
	public function get_payment_token_amount( $amount, $token_id ) {

		$token_amount = '';
		$data = $this->hSession->get_data();

		if ( array_key_exists( $token_id, $data['tokens'] ) && ( $amount == $data['amount'] ) ) {
			$token_amount = $data['tokens'][ $token_id ];
		}

		return $token_amount;

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

		// Add acceptable Solana tokens for payment and their rates
		$this->get_payment_token_options( $data );

		// store the data in user session for later use during payment processing
		$this->hSession->set_data( $data );

		// send response
		header( 'HTTP/1.1 200 OK' );
		wp_send_json( $data );
		die();

	}

}

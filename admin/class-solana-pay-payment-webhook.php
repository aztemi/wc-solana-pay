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
		add_action( 'woocommerce_api_' . PLUGIN_ID . '_txn', array( $this, 'handle_transaction_request' ) );

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

		$data['memo'] = "Cart@{$cart_created}";
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
			wp_send_json_error( 'Bad Request', 400 );
			die();
		}

		// default return data
		$data = array(
			'amount'    => 0,
			'reference' => $ref,
			'testmode'  => $this->hGateway->get_testmode(),
			'recipient' => $this->hGateway->get_merchant_wallet_address(),
			'endpoint'  => $this->hGateway->get_rpc_endpoint(),
			'suffix'    => Solana_Tokens::get_store_currency_key_suffix(),
			'link'      => esc_url( home_url( '/?wc-api=' . PLUGIN_ID . '_txn' ) ),
			'label'     => esc_html( $this->hGateway->get_brand_name() ),
			'message'   => esc_html__( 'Thank you for your order', 'solana-pay-for-woocommerce' ),
		);

		// Add order or checkout cart details that are useful for payment in the frontend
		if ( $order_id ) {
			$this->get_order_details( $order_id, $data );

		} elseif ( WC() && WC()->cart && ! empty( $cart_created ) ) {
			$this->get_cart_details( $cart_created, $data );

		} else {
			wp_send_json_error( 'Bad Request', 400 );
			die();

		}

		// prepend memo with the brand name
		$data['memo'] = $data['label'] . ' - ' . $data['memo'];

		// validate amount
		$amount = $data['amount'];
		if ( $amount <= 0 ) {
			wp_send_json_error( 'Not Found', 404 );
			die();
		}

		// Add acceptable Solana tokens options for payment and their rates
		$options = $this->hGateway->get_accepted_solana_tokens_payment_options( $amount );
		$data = array_merge( $data, $options );

		// calculate payment details hash and use it as a unique reference id
		$hash = md5( wp_json_encode( $data ) );
		$data['id'] = $hash;

		// register payment details with the remote backend
		$testmode = $this->hGateway->get_testmode();
		if ( null === Solana_Pay::register_payment_details( $hash, $data, $testmode ) ) {
			wp_send_json_error( 'Internal Server Error', 500 );
			die();
		}

		// store the data in user session for later use during payment processing
		$this->hSession->set_data( $data );

		// send response
		header( 'HTTP/1.1 200 OK' );
		wp_send_json( $data, 200 );
		die();

	}


	/**
	 * Handle incoming Transaction request based on Solana Pay Spec.
	 */
	public function handle_transaction_request() {

		// validate incoming params
		$id = isset( $_GET['id'] ) ? trim( wc_clean( wp_unslash( $_GET['id'] ) ) ) : '';
		$token = isset( $_GET['token'] ) ? trim( wc_clean( wp_unslash( $_GET['token'] ) ) ) : '';

		if ( empty( $id ) || empty( $token ) ) {
			wp_send_json_error( 'Bad Request', 400 );
			die();
		}

		// get account from payload body
		$account = '';
		$body = @file_get_contents('php://input');
		if ( ! empty( $body ) ) {
			$body = json_decode( $body, true );
			$account = array_key_exists( 'account', $body ) ? trim( wc_clean( wp_unslash( $body['account'] ) ) ) : '';
		}

		if ( empty( $account ) ) {
			// respond to GET request
			header('Cache-Control: max-age=86400');
			$data = array(
				'label' => esc_html( $this->hGateway->get_brand_name() ),
				'icon'  => esc_attr( $this->hGateway->icon ),
			);
		} else {
			// respond to POST request
			$testmode = $this->hGateway->get_testmode();
			$txn_base64 = Solana_Pay::get_payment_transaction( $id, $account, $token, $testmode );

			if ( empty( $txn_base64 ) ) {
				wp_send_json_error( 'Not Found', 404 );
				die();
			}

			$data = array(
				'message'     => esc_html__( 'Thank you for your order', 'solana-pay-for-woocommerce' ),
				'transaction' => trim( wc_clean( wp_unslash( $txn_base64 ) ) ),
			);
		}

		// send response
		header( 'HTTP/1.1 200 OK' );
		wp_send_json( $data, 200 );
		die();

	}

}

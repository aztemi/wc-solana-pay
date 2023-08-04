<?php
/**
 * Backend webhook for handling payment info with the frontend.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


class Webhook {

	/**
	 * Handle instance of the payment gateway class.
	 *
	 * @var WC_Solana_Pay_Payment_Gateway
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

		// webhook GET endpoint for frontend to get order details
		add_action( 'woocommerce_api_' . PLUGIN_ID, array( $this, 'handle_order_request' ) );

		// webhook GET & POST endpoints for receiving payment transactions according to Solana Pay Spec
		add_action( PLUGIN_ID . '_txn', array( $this, 'handle_transaction_request' ) );

		// webhook GET & POST endpoints for checking and setting (by RPC backend) transaction confirmation status
		add_action( PLUGIN_ID . '_stat', array( $this, 'handle_status_request' ) );

	}


	/**
	 * Get payment-related details of specified order
	 */
	private function get_order_details( $order_id, &$data ) {

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$prev_data = $this->hSession->get_data();
		if ( isset( $prev_data['order_id'] ) && ( $order_id === $prev_data['order_id'] ) ) {
			$data['reference'] = $prev_data['reference'];
		}

		$data['memo'] = "Order#{$order_id}";
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
	 * Handle incoming webhook GET order request.
	 */
	public function handle_order_request() {

		// validate incoming params
		$ref = isset( $_GET['ref'] ) ? trim( wc_clean( wp_unslash( $_GET['ref'] ) ) ) : '';
		$order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : false;
		$cart_created = isset( $_GET['cart_created'] ) ? trim( wc_clean( wp_unslash( $_GET['cart_created'] ) ) ) : '';

		if ( empty( $ref ) ) {
			wp_send_json_error( 'Bad Request', 400 );
		}

		// default return data
		$data = array(
			'amount'    => 0,
			'reference' => $ref,
			'home'      => esc_url( get_rest_url( null, PLUGIN_ID . '/v1/api' ) ),
			'link'      => 'txn',
			'poll'      => 'stat',
			'testmode'  => $this->hGateway->get_testmode(),
			'recipient' => $this->hGateway->get_merchant_wallet_address(),
			'endpoint'  => $this->hGateway->get_rpc_endpoint(),
			'suffix'    => Solana_Tokens::get_store_currency_key_suffix(),
			'label'     => esc_html( $this->hGateway->get_brand_name() ),
			'message'   => esc_html__( 'Thank you for your order', 'wc-solana-pay' ),
		);

		// Add order or checkout cart details that are useful for payment in the frontend
		if ( $order_id ) {
			$this->get_order_details( $order_id, $data );

		} elseif ( WC() && WC()->cart && ! empty( $cart_created ) ) {
			$this->get_cart_details( $cart_created, $data );

		} else {
			wp_send_json_error( 'Bad Request', 400 );

		}

		// validate amount
		$amount = $data['amount'];
		if ( $amount <= 0 ) {
			wp_send_json_error( 'Not Found', 404 );
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
		}

		// store the data in user session for later use during payment processing
		$this->hSession->set_data( $data );

		// remove unused info; share only necessary data with the frontend
		unset( $data['recipient'] );

		// send response
		wp_send_json( $data, 200 );

	}


	/**
	 * Handle incoming Transaction request based on Solana Pay Spec.
	 */
	public function handle_transaction_request( $request ) {

		// validate incoming params
		$id = isset( $_GET['id'] ) ? trim( wc_clean( wp_unslash( $_GET['id'] ) ) ) : '';
		$token = isset( $_GET['token'] ) ? trim( wc_clean( wp_unslash( $_GET['token'] ) ) ) : '';

		if ( empty( $id ) || empty( $token ) ) {
			wp_send_json_error( 'Bad Request', 400 );
		}

		// request method
		$method = $request->get_method();

		// respond to GET request
		if ( 'GET' === $method ) {
			// client should cache response for 1 day
			header( 'Cache-Control: max-age=86400' );

			// send response
			$data = array(
				'label' => esc_html( $this->hGateway->get_brand_name() ),
				'icon'  => esc_attr( $this->hGateway->icon ),
			);
			wp_send_json( $data, 200 );
		}

		// respond to POST request
		if ( 'POST' === $method ) {
			// get account from request body
			$account = '';
			$request_json = $request->get_json_params();
			if ( is_array( $request_json ) && array_key_exists( 'account', $request_json ) ) {
				$account = trim( wc_clean( wp_unslash( $request_json['account'] ) ) );
			}

			// return if account is invalid
			if ( empty( $account ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			// get transaction from remote
			$testmode = $this->hGateway->get_testmode();
			$txn_base64 = Solana_Pay::get_payment_transaction( $id, $account, $token, $testmode );
			if ( empty( $txn_base64 ) ) {
				wp_send_json_error( 'Not Found', 404 );
			}

			// send response
			$data = array(
				'message'     => esc_html__( 'Thank you for your order', 'wc-solana-pay' ),
				'transaction' => trim( wc_clean( wp_unslash( $txn_base64 ) ) ),
			);
			wp_send_json( $data, 200 );
		}

	}


	/**
	 * Handle Transaction confirmation status check and notification from the RPC backend.
	 */
	public function handle_status_request( $request ) {

		// validate incoming params
		$id = isset( $_GET['id'] ) ? trim( wc_clean( wp_unslash( $_GET['id'] ) ) ) : '';
		$ref = isset( $_GET['ref'] ) ? trim( wc_clean( wp_unslash( $_GET['ref'] ) ) ) : '';

		if ( empty( $id ) || empty( $ref ) ) {
			wp_send_json_error( 'Bad Request', 400 );
		}

		// request method
		$method = $request->get_method();

		// respond to GET request
		if ( 'GET' === $method ) {
			$option_key = PLUGIN_ID . '_' . $id;
			$signature = get_option( $option_key, array() );
			wp_send_json( $signature, 200 );
		}

		// respond to POST request
		if ( 'POST' === $method ) {
			// get request body
			$request_json = $request->get_json_params();
			if ( ! is_array( $request_json ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			foreach ( $request_json as $v ) {
				$option_key = PLUGIN_ID . '_' . $v['id'];
				$signature = array(
					'signature' => trim( wc_clean( wp_unslash( $v['signature'] ) ) )
				);
				update_option( $option_key, $signature );
			}

			// send ok response
			wp_send_json_success();
		}

	}

}

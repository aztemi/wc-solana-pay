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


	public function __construct( $gateway ) {
		$this->hGateway = $gateway;
		$this->register_hooks();
	}


	/**
	 * Register an API endpoint action to handle webhook GET request
	 */
	private function register_hooks() {
		// webhook GET endpoint for frontend to get order details
		add_action( PLUGIN_ID . '_detail', array( $this, 'handle_get_order_details' ) );

		// webhook GET & POST endpoints for receiving payment transactions according to Solana Pay Spec
		add_action( PLUGIN_ID . '_txn', array( $this, 'handle_transaction_request' ) );

		// webhook GET & POST endpoints for checking and setting (by RPC backend) transaction confirmation status
		add_action( PLUGIN_ID . '_stat', array( $this, 'handle_status_request' ) );

		// webhook GET endpoint for frontend to validate payment confirmation
		add_action( PLUGIN_ID . '_confirm', array( $this, 'handle_confirm_payment' ) );

		// webhook POST endpoint for sending signed transactions to the remote RPC node
		add_action( PLUGIN_ID . '_rpc', array( $this, 'send_rpc_request' ) );
	}


	/**
	 * Handle incoming webhook GET order request.
	 */
	public function handle_get_order_details() {
		// validate incoming params
		$order_id = isset( $_GET['orderId'] ) ? absint( wp_unslash( $_GET['orderId'] ) ) : false;

		if ( ! $order_id ) {
			wp_send_json_error( 'Bad Request', 400 );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( 'Not Found', 404 );
		}

		$this->hGateway->refresh_order_details( $order );
		$details = $this->hGateway->get_order_payment_meta( $order );

		// remove unused info; share only necessary data with the frontend
		unset( $details['recipient'] );
		unset( $details['callback_url'] );

		// send response
		wp_send_json( $details, 200 );
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
			// validate json body
			$request_json = $request->get_json_params();
			$schema = array(
				'type' => 'object',
				'properties' => array(
					'account' => array(
						'type' => 'string',
						'required' => true,
					),
				),
			);
			$result = rest_validate_value_from_schema( $request_json, $schema );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			// sanitize & get account from the json body
			$request_json = rest_sanitize_value_from_schema( $request_json, $schema );
			$account = trim( wc_clean( wp_unslash( $request_json['account'] ) ) );

			// return if account is empty
			if ( empty( $account ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			// get transaction from remote
			$testmode = $this->hGateway->get_testmode();
			$res = Solana_Pay::get_payment_transaction( $id, $account, $token, $testmode );
			if ( ! isset( $res['transaction'] ) ) {
				wp_send_json_error( $res['error'], $res['status'] );
			}

			// send response
			$data = array(
				'message'     => esc_html__( 'Thank you for your order', 'wc-solana-pay' ),
				'transaction' => trim( wc_clean( wp_unslash( $res['transaction'] ) ) ),
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

		if ( empty( $id ) ) {
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
			// validate json body
			$request_json = $request->get_json_params();
			$schema = array(
				'type' => 'array',
				'minItems' => 1,
				'items' => array(
					'type' => 'object',
					'properties' => array(
						'id' => array(
							'type' => 'string',
							'required' => true,
						),
						'signature' => array(
							'type' => 'string',
							'required' => true,
						),
					),
				),
			);
			$result = rest_validate_value_from_schema( $request_json, $schema );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( 'Bad Request', 400 );
			}

			// sanitize the json body
			$request_json = rest_sanitize_value_from_schema( $request_json, $schema );

			// add signature to the option table
			foreach ( $request_json as $v ) {
				$id = trim( wc_clean( wp_unslash( $v['id'] ) ) );
				$signature = trim( wc_clean( wp_unslash( $v['signature'] ) ) );

				// return if id or signature is not specified
				if ( empty( $id ) || empty( $signature ) ) {
					wp_send_json_error( 'Bad Request', 400 );
				}

				$option_key = PLUGIN_ID . '_' . $id;
				$signature = array( 'signature' => $signature );
				update_option( $option_key, $signature );
			}

			// send ok response
			wp_send_json_success();

		}
	}


	/**
	 * Handle payment confirmation request from the frontend.
	 */
	public function handle_confirm_payment( $request ) {
		// validate incoming params
		$id = isset( $_GET['id'] ) ? trim( wc_clean( wp_unslash( $_GET['id'] ) ) ) : '';
		$order_id = isset( $_GET['orderId'] ) ? absint( wp_unslash( $_GET['orderId'] ) ) : false;

		if ( empty( $id ) ) {
			wp_send_json_error( 'Bad Request', 400 );
		}

		if ( ! $order_id ) {
			wp_send_json_error( 'Bad Request', 400 );
		}

		// confirm payment
		$res = $this->hGateway->confirm_payment( $order_id );
		wp_send_json( $res, 200 );
	}


	/**
	 * Send RPC request to remote RPC node.
	 */
	public function send_rpc_request( $request ) {
		// validate incoming params
		$id = isset( $_GET['id'] ) ? trim( wc_clean( wp_unslash( $_GET['id'] ) ) ) : '';

		if ( empty( $id ) ) {
			wp_send_json_error( 'Bad Request', 400 );
		}

		// handle POST request
		if ( 'POST' === $request->get_method() ) {
			// proxy request based on body parameters
			$body = $request->get_json_params();
			if ( is_array( $body ) && isset( $body['method'] ) && isset( $body['params'] ) ) {
				$this->proxy_raw_rpc_request( $id, $body );

			} elseif ( is_array( $body ) && isset( $body['transaction'] ) ) {
				$this->proxy_transaction_request( $id, $body );

			} else {
				wp_send_json_error( 'Bad Request', 400 );

			}

		}
	}


	/**
	 * Forward raw RPC request to remote RPC node.
	 *
	 * @param  string $id           Remote ID of the checkout order.
	 * @param  array  $request_json Request body as json array.
	 */
	public function proxy_raw_rpc_request( $id, $request_json ) {
		// validate json body
		$schema = array(
			'type' => 'object',
			'properties' => array(
				'method' => array(
					'type' => 'string',
					'required' => true,
				),
				'params' => array(
					'type' => 'array',
					'minItems' => 1,
				),
			),
		);
		$result = rest_validate_value_from_schema( $request_json, $schema );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( 'Bad Request', 400 );
		}

		// sanitize the json body
		$request_json = rest_sanitize_value_from_schema( $request_json, $schema );

		// send transaction to remote
		$testmode = $this->hGateway->get_testmode();
		$url = Solana_Pay::endpoint_url( $id, 'rpc', $testmode );
		$res = remote_request( $url, 'POST', wp_json_encode( $request_json ) );
		if ( $res['error'] ) {
			wp_send_json_error( $res['error'], $res['status'] );
		}

		// send response
		wp_send_json( $res['body'], 200 );
	}


	/**
	 * Forward signed transaction request to remote RPC node.
	 *
	 * @param  string $id           Remote ID of the checkout order.
	 * @param  array  $request_json Request body as json array.
	 */
	public function proxy_transaction_request( $id, $request_json ) {
		// validate json body
		$schema = array(
			'type' => 'object',
			'properties' => array(
				'transaction' => array(
					'type' => 'string',
					'required' => true,
				),
			),
		);
		$result = rest_validate_value_from_schema( $request_json, $schema );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( 'Bad Request', 400 );
		}

		// sanitize & get transaction from the json body
		$request_json = rest_sanitize_value_from_schema( $request_json, $schema );
		$transaction = trim( wc_clean( wp_unslash( $request_json['transaction'] ) ) );

		// return if transaction is empty
		if ( empty( $transaction ) ) {
			wp_send_json_error( 'Bad Request', 400 );
		}

		// send transaction to remote
		$testmode = $this->hGateway->get_testmode();
		$res = Solana_Pay::send_payment_transaction( $id, $transaction, $testmode );
		if ( $res['error'] ) {
			wp_send_json_error( $res['error'], $res['status'] );
		}

		// send ok response
		wp_send_json_success();
	}
}

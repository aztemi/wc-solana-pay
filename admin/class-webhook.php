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

		// webhook POST endpoint for sending signed transactions to the remote RPC node
		add_action( PLUGIN_ID . '_rpc', array( $this, 'send_rpc_request' ) );
	}


	/**
	 * Validate customer email address before processing order.
	 *
	 * If user is not logged in and registration is required, WC will attempt to create an account for the user.
	 * This makes sure the user email address is not already registered. Otherwise, account creation will fail.
	 */
	private function validate_customer_email() {
		if ( ! is_user_logged_in() && ! is_null( WC()->customer ) && ! is_null( WC()->checkout() ) ) {
			$email = WC()->customer->get_billing_email();
			$is_registration_required = filter_var( wc()->checkout()->is_registration_required(), FILTER_VALIDATE_BOOLEAN );

			if ( $is_registration_required && ! empty( $email ) ) {
				if ( ! is_email( $email ) ) {
					wp_send_json_error(
						esc_html__( 'Bad Request - Email address not valid', 'wc-solana-pay' ),
						400
					);
				}
				if ( email_exists( $email ) ) {
					wp_send_json_error(
						esc_html__( 'An account is already registered with your email address. Please log in and try again.', 'wc-solana-pay' ),
						406
					);
				}
			}
		}
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
		$data['symbol'] = get_woocommerce_currency_symbol( $data['currency'] );
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
		$data['currency'] = Solana_Tokens::get_store_currency('edit');
		$data['symbol'] = get_woocommerce_currency_symbol( $data['currency'] );
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
			'rpc'       => 'rpc',
			'testmode'  => $this->hGateway->get_testmode(),
			'recipient' => $this->hGateway->get_merchant_wallet_address(),
			'suffix'    => Solana_Tokens::get_store_currency_key_suffix(),
			'label'     => esc_html( $this->hGateway->get_brand_name() ),
			'message'   => esc_html__( 'Thank you for your order', 'wc-solana-pay' ),
		);

		// Add order or checkout cart details that are useful for payment in the frontend
		if ( $order_id ) {
			$this->get_order_details( $order_id, $data );
		} elseif ( WC() && WC()->cart && ! empty( $cart_created ) ) {
			$this->get_cart_details( $cart_created, $data );
			$this->validate_customer_email();
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

		// register order details with the remote backend
		$testmode = $this->hGateway->get_testmode();
		$res = Solana_Pay::register_order_details( $data, $testmode );
		if ( ! isset( $res['id'] ) ) {
			wp_send_json_error( $res['error'], $res['status'] );
		}

		// store the data in user session for later use during payment processing
		if ( count( $res['tokens'] ) ) {
			$data['tokens'] = $res['tokens'];
		}
		$data['id'] = $res['id'];
		$this->hSession->set_data( $data );

		// remove unused info; share only necessary data with the frontend
		unset( $data['recipient'] );
		unset( $data['callback_url'] );

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

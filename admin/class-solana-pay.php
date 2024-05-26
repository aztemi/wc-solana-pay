<?php
/**
 * Wrapper class for Solana on chain logics.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


class Solana_Pay {

	/**
	 * Default scale precision for bc math functions.
	 *
	 * @var int
	 */
	private const BC_MATH_SCALE = 10;


	/**
	 * Name of the Solana Devnet network.
	 *
	 * @var string
	 */
	public const NETWORK_DEVNET = 'devnet';


	/**
	 * Name of the Solana Mainnet-Beta network.
	 *
	 * @var string
	 */
	public const NETWORK_MAINNET_BETA = 'mainnet-beta';


	/**
	 * Remote endpoint for Testmode payment transactions.
	 *
	 * @var string
	 */
	protected const ENDPOINT_TESTMODE = 'https://wc-solana-pay-staging.juxdan.io/api/v1/';


	/**
	 * Remote endpoint for Production payment transactions.
	 *
	 * @var string
	 */
	protected const ENDPOINT_PRODUCTION = 'https://wc-solana-pay.juxdan.io/api/v1/';


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
	}


	/**
	 * Get the URL for viewing transaction details on Solana Explorer.
	 *
	 * @param  string $txn_id Payment transaction ID.
	 * @return string
	 */
	private function get_explorer_url( $txn_id ) {
		$url = 'https://explorer.solana.com/tx/' . $txn_id;
		$url .= $this->hGateway->get_testmode() ? '?cluster=devnet' : '';

		return $url;
	}


	/**
	 * Validate payment transaction on Solana network.
	 *
	 * @param  \WC_Order $order  Order object.
	 * @param  string    $amount Order cost amount in the store base currency.
	 * @return bool      true if transaction was found and correct amount was paid into merchant wallet, false otherwise.
	 */
	public function confirm_payment_onchain( $order, $amount ) {
		$order_id = $order->get_id();

		// get checkout order details from current session
		$data = $this->hSession->get_data();
		$id = $data['id'];

		// fetch payment status details for the order from remote
		$res = self::get_payment_details( $id, $this->hGateway->get_testmode() );

		// return false if checkout reference id does not match
		$received_id = $res['body']['id'];
		if ( $id !== $received_id ) {
			logger( "Process payment failed. Order: $order_id. Checkout id mismatch - expected: $id, received: $received_id." );
			return false;
		}

		// return false if payment is not confirmed
		if ( ! isset( $res['confirmed'] ) || ( true !== $res['confirmed'] ) ) {
			logger( "Process payment failed. Order: $order_id, checkout id: $id. Payment not confirmed onchain." );
			return false;
		}

		// validate paid token. Return false if paid token is not enabled by the store
		$token_id = $res['body']['currency'];
		$table = $this->hGateway->get_tokens_table();
		if ( ! array_key_exists( $token_id, $table ) || ! $table[ $token_id ]['enabled'] ) {
			logger( "Process payment failed. Order: $order_id, checkout id: $id. Token ($token_id) not enabled by merchant." );
			return false;
		}

		// validate recipient. Return false if recipient does not match
		$receiver = $res['body']['receiver'];
		$expected_receiver = $data['recipient'];
		if ( $receiver !== $expected_receiver ) {
			logger( "Process payment failed. Order: $order_id, checkout id: $id. Recipient wallet mismatch - expected: $expected_receiver, receiver: $receiver." );
			return false;
		}

		// update order meta info
		$txn_id = $res['body']['signature'];
		$tokens = $this->hGateway->get_accepted_solana_tokens();
		$symbol = $tokens[ $token_id ]['symbol'];
		$meta = array(
			'id'          => $id,
			'transaction' => $txn_id,
			'reference'   => $data['reference'],
			'receiver'    => $receiver,
			'payer'       => $res['body']['payer'],
			'received'    => rtrim( $res['body']['received'], '0' ) . ' ' . $symbol,
			'paid'        => rtrim( $res['body']['paid'], '0' ) . ' ' . $symbol,
			'url'         => $this->get_explorer_url( $txn_id ),
		);
		$this->hGateway->set_order_payment_meta( $order, $meta );

		// Complete payment and return true
		$order->add_order_note( __( 'Solana Pay payment completed', 'wc-solana-pay' ) );
		$order->payment_complete( $txn_id );

		// remove transaction option key
		$option_key = PLUGIN_ID . '_' . $meta['id'];
		delete_option( $option_key );

		return true;
	}


	/**
	 * Get Solana tokens available for payments and calculate how much the cost of the order in each token.
	 *
	 * @param  string $amount Order amount in the store base currency.
	 * @return array          List of payment options and their cost values.
	 */
	public function get_available_payment_options( $amount ) {
		$tokens = $this->hGateway->get_accepted_solana_tokens();
		$table = $this->hGateway->get_tokens_table();

		$options = array();
		$options['tokens'] = array();

		$old_scale = bcscale( self::BC_MATH_SCALE ); // set scale precision

		foreach ( $tokens as $k => $v ) {
			if ( array_key_exists( $k, $table ) && $table[ $k ]['enabled'] ) {
				$decimals = $tokens[ $k ]['decimals'];
				$power = bcpow( '10', $decimals );
				$amount_pow = bcmul( $amount, $power );
				$rate = bcmul( $amount_pow, $table[ $k ]['rate'] );
				$amount_in_token = rtrim( bcdiv( $rate, $power, $decimals ), '0' );

				$options['tokens'][ $k ] = array(
					'amount' => $amount_in_token,
					'mint' => $tokens[ $k ]['mint'],
					'dp' => $tokens[ $k ]['decimals_view'],
				);
			}
		}

		bcscale( $old_scale ); // reset back to old scale

		return $options;
	}


	/**
	 * Shorten transaction hash address for frontend UI.
	 *
	 * @param  string $address Hash or address to shorten.
	 * @param  int    $limit   Number of prepend & postpend characters to keep.
	 * @return string
	 */
	public static function shorten_hash_address( $address, $limit = 6 ) {
		return substr( $address, 0, $limit ) . '...' . substr( $address, -$limit );
	}


	/**
	 * Get remote endpoint full URL path.
	 *
	 * @param  string $ref_id   Remote session reference ID
	 * @param  string $action   Remote route action type. It can either be 'rpc' or 'txn' for RPC and Transaction handling respectively.
	 * @param  bool   $testmode Testmode status flag; true if in Testmode, false otherwise.
	 * @return string
	 */
	public static function endpoint_url( $ref_id, $action, $testmode ) {
		$network = $testmode ? self::NETWORK_DEVNET : self::NETWORK_MAINNET_BETA;
		$endpoint = $testmode ? self::ENDPOINT_TESTMODE : self::ENDPOINT_PRODUCTION;
		$ref_id = empty( $ref_id ) ? '' : '/' . $ref_id;
		$url = sprintf( '%s%s%s/?network=%s', $endpoint, $action, $ref_id, $network );
		return $url;
	}


	/**
	 * Register checkout order details with remote server.
	 *
	 * @param  string $data     Payment order details.
	 * @param  bool   $testmode Testmode status flag; true if in Testmode, false otherwise.
	 * @return array  Remote server response, containing the reference ID of the order.
	 */
	public static function register_order_details( $data, $testmode ) {
		$url = self::endpoint_url( '', 'order', $testmode );
		$response = remote_request( $url, 'POST', wp_json_encode( $data ) );
		if ( 200 === $response['status'] ) {
			$body = $response['body'];
			$response['id'] = array_key_exists( 'id', $body ) ? $body['id'] : '';
			$response['tokens'] = array_key_exists( 'tokens', $body ) ? $body['tokens'] : array();
		}

		return $response;
	}


	/**
	 * Get payment transaction from remote server.
	 *
	 * @param  string $ref_id   Remote reference ID of the transaction.
	 * @param  string $address  Wallet address of the transaction fee payer.
	 * @param  string $token_id Payment token ID.
	 * @param  bool   $testmode Testmode status flag; true if in Testmode, false otherwise.
	 * @return array
	 */
	public static function get_payment_transaction( $ref_id, $address, $token_id, $testmode ) {
		$url = self::endpoint_url( $ref_id, 'txn', $testmode );
		$url = sprintf( '%s&account=%s&token=%s', $url, $address, $token_id );
		$response = remote_request( $url );
		if ( 200 === $response['status'] ) {
			$body = $response['body'];
			$response['transaction'] = array_key_exists( 'transaction', $body ) ? $body['transaction'] : '';
		}

		return $response;
	}


	/**
	 * Get payment details from remote server.
	 *
	 * @param  string $ref_id   Remote reference ID of the order.
	 * @param  bool   $testmode Testmode status flag; true if in Testmode, false otherwise.
	 * @return array
	 */
	public static function get_payment_details( $ref_id, $testmode ) {
		$url = self::endpoint_url( $ref_id, 'payment', $testmode );
		$response = remote_request( $url );
		if ( 200 === $response['status'] ) {
			$body = $response['body'];
			$response['signature'] = array_key_exists( 'signature', $body ) ? $body['signature'] : '';
			$response['confirmed'] = array_key_exists( 'confirmed', $body ) ? $body['confirmed'] : false;
		}

		return $response;
	}


	/**
	 * Send payment transaction to the remote RPC server.
	 *
	 * @param  string $ref_id   Remote session reference ID.
	 * @param  string $txn      Base64 serialized transaction.
	 * @param  bool   $testmode Testmode status flag; true if in Testmode, false otherwise.
	 * @return array
	 */
	public static function send_payment_transaction( $ref_id, $txn, $testmode ) {
		$params = array(
			$txn,
			array(
				'encoding' => 'base64',
				'skipPreflight' => true,
			),
		);
		$url = self::endpoint_url( $ref_id, 'rpc', $testmode );

		return self::rpc_remote_post( 'sendTransaction', $params, $url );
	}


	/**
	 * Send remote call to a Solana RPC endpoint.
	 *
	 * @param  string $method Remote RPC method that will be called.
	 * @param  array  $params List of attributes for the RPC method.
	 * @param  string $url    RPC endpoint URL.
	 * @return array          Array containing RPC response in the 'result' field if request succeeds.
	 */
	public static function rpc_remote_post( $method, $params, $url ) {
		$data = wp_json_encode(
			array(
				'jsonrpc' => '2.0',
				'id'      => 1,
				'method'  => $method,
				'params'  => $params,
			)
		);

		$response = remote_request( $url, 'POST', $data );
		$error = $response['error'];
		$code = $response['status'];
		$body = $response['body'];

		if ( $error ) {
			/* translators: %s: WordPress error message, e.g. 'Timeout error' */
			wc_add_notice( sprintf( __( 'Connection to RPC node failed: %s', 'wc-solana-pay' ), $error ), 'error' );
		} else if ( 200 === $code ) {
			$response['result'] = ( array_key_exists( 'result', $body ) ? $body['result'] : array() );
		} else {
			/* translators: %d: rpc call response code error, e.g. 404 */
			wc_add_notice( sprintf( __( 'RPC remote call failed with code %d', 'wc-solana-pay' ), $code ), 'error' );
		}

		return $response;
	}
}

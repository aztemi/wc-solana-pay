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
	 * 0.5% fee for above default endpoints usage.
	 *
	 * @var string
	 */
	protected const ENDPOINT_USAGE_FEE = '0.50';


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
	 * Find confirmed payment transaction by reference from the Solana network.
	 *
	 * @param  string $txn_id Found transaction ID as output.
	 * @return bool           true if transaction is found, false otherwise.
	 */
	private function get_transaction_id( &$txn_id ) {

		$data = $this->hSession->get_data();
		$reference = $data['reference'];
		$url = self::endpoint_url( $data['id'], 'rpc', $this->hGateway->get_testmode() );

		$params = array( $reference, array( 'commitment' => 'confirmed' ) );
		$res = self::rpc_remote_post( 'getSignaturesForAddress', $params, $url );

		// Return false in case of error in post request
		if ( ! isset( $res['result'] ) ) {
			return false;
		}
		$txn = $res['result'];

		// Payment transaction not found, return false
		if ( ! count( $txn ) ) {
			wc_add_notice( __( 'Payment transaction not found. Please try again.', 'wc-solana-pay' ), 'error' );
			return false;
		}

		$txn_id = $txn[0]['signature'];

		return true;

	}


	/**
	 * Get payment transaction parsed details from the Solana network.
	 *
	 * @param  string $txn_id   Transaction ID.
	 * @param  array  $txn_data Parsed transaction details as output.
	 * @return bool             true if successful, false otherwise.
	 */
	private function get_transaction_details( $txn_id, &$txn_data ) {

		$data = $this->hSession->get_data();
		$url = self::endpoint_url( $data['id'], 'rpc', $this->hGateway->get_testmode() );

		$params = array(
			$txn_id,
			array(
				'commitment' => 'confirmed',
				'encoding'   => 'jsonParsed',
				'maxSupportedTransactionVersion' => 0,
			)
		);
		$res = self::rpc_remote_post( 'getTransaction', $params, $url );

		// Return false in case of error in post request
		if ( ! isset( $res['result'] ) ) {
			return false;
		}
		$txn_data = $res['result'];

		// Return false if unable to confirm transaction details
		if ( ! count( $txn_data ) ) {
			wc_add_notice( __( 'Unable to confirm payment transaction details. Please try again.', 'wc-solana-pay' ), 'error' );
			return false;
		}

		return true;

	}


	/**
	 * Get received balance of a payment transaction.
	 *
	 * @param  \WC_Order $order    Order object.
	 * @param  array     $txn_data Parsed transaction details.
	 * @param  string    $token_id Solana Token ID.
	 * @param  array     $balance  Received balance as output.
	 * @return bool                true if successful, false otherwise.
	 */
	private function get_transaction_balance( $order, $txn_data, $token_id, &$balance ) {

		$tokens = $this->hGateway->get_accepted_solana_tokens();

		// Return false if payment currency is not part of our supported tokens
		if ( ! array_key_exists( $token_id, $tokens ) ) {
			$order->add_order_note( __( 'Payment currency not in supported Solana tokens list.', 'wc-solana-pay' ) );
			return false;
		}

		$currency = $tokens[ $token_id ];
		$currency_mint = $currency['mint'];
		$currency_symbol = $currency['symbol'];
		$receiver = $this->hGateway->get_merchant_wallet_address();
		$payer = $this->get_transaction_payer( $txn_data );

		if ( 'SOL' === $currency_symbol ) {
			$balance['received'] = $this->get_transaction_received_sol( $txn_data, $receiver );
			$balance['paid'] = $this->get_transaction_received_sol( $txn_data, $payer );
		} else {
			$balance['received'] = $this->get_transaction_received_spltoken( $txn_data, $receiver, $currency_mint );
			$balance['paid'] = $this->get_transaction_received_spltoken( $txn_data, $payer, $currency_mint );
		}

		// Add token number of decimals & symbol
		$balance['decimals'] = $currency['decimals'];
		$balance['symbol'] = $currency_symbol;
		$balance['receiver'] = $receiver;
		$balance['payer'] = $payer;

		return true;

	}


	/**
	 * Get SOL balance of a transaction.
	 *
	 * @param  array  $txn_data  Parsed transaction details.
	 * @param  string $account58 Receiver wallet address.
	 * @return array             Pre and Post balances in SOL.
	 */
	private function get_transaction_received_sol( $txn_data, $account58 ) {

		$accounts = $txn_data['transaction']['message']['accountKeys'];
		foreach ( $accounts as $idx => $v ) {
			if ( $account58 === $v['pubkey'] ) {
				$pre_balance  = $txn_data['meta']['preBalances'][ $idx ];
				$post_balance = $txn_data['meta']['postBalances'][ $idx ];

				return array( 'pre' => $pre_balance, 'post' => $post_balance );
			}
		}

		return array( 'pre' => 0, 'post' => 0 );

	}


	/**
	 * Get a SPL Token balance of a transaction.
	 *
	 * @param  array  $txn_data  Parsed transaction details.
	 * @param  string $account58 Receiver wallet address.
	 * @param  string $spltoken  SPL token mint address.
	 * @return array             Pre and Post balances in specified SPL token.
	 */
	private function get_transaction_received_spltoken( $txn_data, $account58, $spltoken ) {

		$pre_token_balance = 0;
		$post_token_balance = 0;

		foreach ( $txn_data['meta']['preTokenBalances'] as $idx => $v ) {
			if ( ( $v['owner'] === $account58 ) && ( $v['mint'] === $spltoken ) ) {
				$pre_token_balance = $v['uiTokenAmount']['amount'];
			}
		}

		foreach ( $txn_data['meta']['postTokenBalances'] as $idx => $v ) {
			if ( ( $v['owner'] === $account58 ) && ( $v['mint'] === $spltoken ) ) {
				$post_token_balance = $v['uiTokenAmount']['amount'];
			}
		}

		return array( 'pre' => $pre_token_balance, 'post' => $post_token_balance );

	}


	/**
	 * Get the wallet address of a transaction signer (aka. Payer).
	 *
	 * @param  array  $txn_data  Parsed transaction details.
	 * @return string            Wallet address of the transaction signer.
	 */
	private function get_transaction_payer( $txn_data ) {

		// The transaction signer is considered the payer
		$accounts = $txn_data['transaction']['message']['accountKeys'];
		foreach ( $accounts as $v ) {
			if ( isset( $v['signer'] ) && $v['signer'] ) {
				return $v['pubkey'];
			}
		}

		return '';

	}


	/**
	 * Get the order total in specified Solana payment tokens.
	 *
	 * @param  float  $amount   Order cost in store base currency.
	 * @param  string $token_id Token ID.
	 * @return string Expected payment amount as a BC Math string.
	 */
	private function get_payment_token_amount( $amount, $token_id ) {

		$token_amount = '';
		$data = $this->hSession->get_data();

		if ( array_key_exists( $token_id, $data['tokens'] ) && ( $amount == $data['amount'] ) ) {
			$token_amount = $data['tokens'][ $token_id ]['amount'];
		}

		return $token_amount;

	}


	/**
	 * Validates if an expected amount is fully paid or not.
	 *
	 * @param  string $amount   Expected amount to be paid.
	 * @param  array  $balance  Payment transaction balance info.
	 * @return bool             true if received amount is greater than or equal to expected amount, false otherwise.
	 */
	private function validate_payment_amount( $amount, $balance ) {

		$decimals = $balance['decimals'];
		list( 'pre' => $pre, 'post' => $post ) = $balance['received'];

		$old_scale = bcscale( self::BC_MATH_SCALE ); // set scale precision

		// compensate for endpoint usage fee already deducted in transaction
		$percent_fee = self::endpoint_usage_fee();
		$rpc_fee = bcdiv( bcmul( $percent_fee, $amount ), 100 );
		$expected_amount = bcsub( $amount, $rpc_fee );

		$validated = bccomp(
				bcsub( $post, $pre ),
				bcmul( $expected_amount, bcpow( '10', $decimals ) )
			) >= 0;

		bcscale( $old_scale ); // reset back to old scale

		return $validated;

	}


	/**
	 * Get metadata from a payment transaction details.
	 *
	 * @param  string $txn_id   Transaction ID.
	 * @param  array  $balance  Payment transaction balance info.
	 * @return array            List of metadata of the payment transaction details.
	 */
	private function get_payment_meta( $txn_id, $balance ) {

		$data = $this->hSession->get_data();
		$symbol = $balance['symbol'];
		$decimals = $balance['decimals'];
		$meta = array(
			'id'          => $data['id'],
			'transaction' => $txn_id,
			'reference'   => $data['reference'],
			'payer'       => $balance['payer'],
			'receiver'    => $balance['receiver'],
			'url'         => $this->get_explorer_url( $txn_id ),
		);

		$old_scale = bcscale( self::BC_MATH_SCALE ); // set scale precision

		// crypto amount paid by customer
		list( 'pre' => $post, 'post' => $pre ) = $balance['paid']; // post & pre swapped since it's an outgoing balance diff
		$paid = bcdiv( bcsub( $post, $pre ), bcpow( '10', $decimals ), $decimals );
		$meta['paid'] = rtrim( $paid, '0' ) . ' ' . $symbol;

		// crypto amount received by merchant after fee is deducted
		list( 'pre' => $pre, 'post' => $post ) = $balance['received'];
		$received = bcdiv( bcsub( $post, $pre ), bcpow( '10', $decimals ), $decimals );
		$meta['received'] = rtrim( $received, '0' ) . ' ' . $symbol;

		bcscale( $old_scale ); // reset back to old scale

		return $meta;

	}


	/**
	 * Validate payment transaction on Solana network.
	 *
	 * @param  \WC_Order $order    Order object.
	 * @param  string    $amount   Order cost amount in the store base currency.
	 * @param  string    $token_id Payment token ID.
	 * @return bool      true if transaction was found and correct amount was paid into merchant wallet, false otherwise.
	 */
	public function confirm_payment_onchain( $order, $amount, $token_id ) {

		$txn_id = '';
		$txn_data = array();
		$balance = array();

		// get expected payment amount in Solana token
		$token_amount = $this->get_payment_token_amount( $amount, $token_id );
		// Return false if amount is not valid
		if ( empty( trim( $token_amount ) ) ) {
			return false;
		}

		// Get payment transaction details from Solana chain
		$rtn =
			$this->get_transaction_id( $txn_id ) &&
			$this->get_transaction_details( $txn_id, $txn_data ) &&
			$this->get_transaction_balance( $order, $txn_data, $token_id, $balance );

		// Return false in case of any error
		if ( ! $rtn ) {
			return false;
		}

		// Validate payment amount. Return false if payment is missing or not up to expected amount
		if ( ! $this->validate_payment_amount( $token_amount, $balance ) ) {
			return false;
		}

		// update order meta info
		$meta = $this->get_payment_meta( $txn_id, $balance );
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
				$fee = bcdiv( bcmul( $rate, $table[ $k ]['fee'] ), '100' );
				$amount_in_token = rtrim( bcdiv( bcadd( $rate, $fee ), $power, $decimals ), '0' );

				$options['tokens'][ $k ] = array(
					'amount' => $amount_in_token,
					'mint' => $tokens[ $k ]['mint'],
					'dp' => $tokens[ $k ]['decimals_view']
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
	 * Get RPC and transactions endpoint usage fee.
	 *
	 * @return string
	 */
	public static function endpoint_usage_fee() {

		return self::ENDPOINT_USAGE_FEE;

	}


	/**
	 * Register payment order details with remote server.
	 *
	 * @param  string $data     Payment order details.
	 * @param  bool   $testmode Testmode status flag; true if in Testmode, false otherwise.
	 * @return array  Remote server response, containing the reference ID of the order.
	 */
	public static function register_payment_details( $data, $testmode ) {

		$url = self::endpoint_url( '', 'order', $testmode );
		$response = remote_request( $url, 'POST', wp_json_encode( $data ) );
		if ( 200 === $response['status'] ) {
			$body = $response['body'];
			$response['id'] = array_key_exists( 'id', $body ) ? $body['id'] : '';
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
				'skipPreflight' => true
			)
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

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
	 * @var string
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
	 * Default RPC node endpoint for Solana Devnet.
	 *
	 * @var string
	 */
	protected const RPC_ENDPOINT_DEVNET = 'https://wc-solana-pay.juxdan.io/v1/rpc-devnet/';


	/**
	 * Default RPC node endpoint for Solana Mainnet-Beta.
	 *
	 * @var string
	 */
	protected const RPC_ENDPOINT_MAINNET_BETA = 'https://wc-solana-pay.juxdan.io/v1/rpc/';


	/**
	 * Default endpoint for remote Solana transactions handling.
	 *
	 * @var string
	 */
	protected const TRANSACTION_ENDPOINT = 'https://wc-solana-pay.juxdan.io/v1/txn/';


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
	 * Send remote call to a RPC endpoint.
	 *
	 * @param  string $method Remote RPC method that will be called.
	 * @param  array  $params List of attributes for the RPC method.
	 * @param  string $url    RPC endpoint URL.
	 * @return mixed          Result array in RPC response if request succeeds or false otherwise.
	 */
	private function rpc_remote_post( $method, $params, $url ) {

		$rtn = false;

		$data = $this->hSession->get_data();
		$id = $data['id'];
		$url .= $id . '/';

		$body = wp_json_encode(
			array(
				'jsonrpc' => '2.0',
				'id'      => 1,
				'method'  => $method,
				'params'  => $params,
			)
		);

		$response = wp_remote_post( $url, array(
			'method'      => 'POST',
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'timeout'     => 45,
			'body'        => $body,
			'data_format' => 'body',
			)
		);

		if ( is_wp_error( $response ) ) {
			wc_add_notice( __( 'Connection to RPC failed', 'wc-solana-pay' ), 'error' );
		} else {
			$response_code = wp_remote_retrieve_response_code( $response );

			if ( 200 === $response_code ) {
				$response_body = wp_remote_retrieve_body( $response );
				$response = json_decode( $response_body, true );

				$rtn = ( array_key_exists( 'result', $response ) ? $response['result'] : array() );
			} else {
				/* translators: %d: rpc call response code error, e.g. 404 */
				wc_add_notice( sprintf( __( 'RPC remote call failed with code %d', 'wc-solana-pay' ), $response_code ), 'error' );
			}
		}

		return $rtn;

	}


	/**
	 * Find confirmed payment transaction by reference from the Solana network.
	 *
	 * @param  array  $meta   List of metadata about the transaction as output.
	 * @param  string $txn_id Found transaction ID as output.
	 * @return bool           true if transaction is found, false otherwise.
	 */
	private function get_transaction_id( &$meta, &$txn_id ) {

		$data = $this->hSession->get_data();
		$reference = $data['reference'];

		$params = array( $reference, array( 'commitment' => 'confirmed' ) );
		$txn = $this->rpc_remote_post( 'getSignaturesForAddress', $params, $this->hGateway->get_rpc_endpoint() );

		// Return false in case of error in post request
		if ( ! is_array( $txn ) ) {
			return false;
		}

		// Payment transaction not found, return false
		if ( ! count( $txn ) ) {
			wc_add_notice( __( 'Payment transaction not found. Please try again.', 'wc-solana-pay' ), 'error' );
			return false;
		}

		$txn_id = $txn[0]['signature'];
		$meta = array(
			'reference' => $reference,
			'transaction' => $txn_id,
		);

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

		$params = array(
			$txn_id,
			array(
				'commitment' => 'confirmed',
				'encoding'   => 'jsonParsed',
				'maxSupportedTransactionVersion' => 0,
			)
		);
		$txn_data = $this->rpc_remote_post( 'getTransaction', $params, $this->hGateway->get_rpc_endpoint() );

		// Return false in case of error in post request
		if ( ! is_array( $txn_data ) ) {
			return false;
		}

		// Return false if unable to confirm transaction details
		if ( ! count( $txn_data ) ) {
			wc_add_notice( __( 'Unable to confirm payment transaction details. Please try again.', 'wc-solana-pay' ), 'error' );
			return false;
		}

		return true;

	}


	/**
	 * Get paid balance of a payment transaction.
	 *
	 * @param  \WC_Order $order    Order object.
	 * @param  array     $txn_data Parsed transaction details.
	 * @param  string    $token_id Solana Token ID.
	 * @param  array     $balance  Paid balance as output.
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
		$expected_receiver = $this->hGateway->get_merchant_wallet_address();

		if ( 'SOL' === $currency_symbol ) {
			$balance = $this->get_transaction_received_sol( $txn_data, $expected_receiver );
		} else {
			$balance = $this->get_transaction_received_spltoken( $txn_data, $expected_receiver, $currency_mint );
		}

		// Add token number of decimals & symbol
		$balance['decimals'] = $currency['decimals'];
		$balance['symbol'] = $currency_symbol;

		return true;

	}


	/**
	 * Get SOL balance of a transaction.
	 *
	 * @param  array  $txn_data  Parsed transaction details.
	 * @param  string $account58 Receiver wallet address.
	 * @return array             Pre and Post Paid balances in SOL.
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
	 * @return array             Pre and Post Paid balances in specified SPL token.
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
	 * @param  string $amount  Expected amount to be paid.
	 * @param  array  $balance Balance paid.
	 * @param  string $paid    Amount paid in a formatted string for the UI.
	 * @return bool            true if paid amount is greater than or equal to expected amount, false otherwise.
	 */
	private function validate_payment_amount( $amount, $balance, &$paid ) {

		list( 'pre' => $pre, 'post' => $post, 'decimals' => $decimals ) = $balance;

		$old_scale = bcscale( self::BC_MATH_SCALE ); // set scale precision

		$paid = bcdiv( bcsub( $post, $pre ), bcpow( '10', $decimals ), $decimals );
		$paid = rtrim( $paid, '0' ) . ' ' . $balance['symbol'];

		// compensate for endpoint usage fee already deducted in transaction
		$percent_fee = self::endpoints_usage_fee();
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
	 * Validate payment transaction on Solana network.
	 *
	 * @param  \WC_Order $order    Order object.
	 * @param  string    $amount   Order cost amount in the store base currency.
	 * @param  string    $token_id Payment token ID.
	 * @return bool      true if transaction was found and correct amount was paid into merchant wallet, false otherwise.
	 */
	public function confirm_payment_onchain( $order, $amount, $token_id ) {

		$paid = '';
		$txn_id = '';
		$txn_data = array();
		$balance = array();
		$meta = array();

		// get expected payment amount in Solana token
		$token_amount = $this->get_payment_token_amount( $amount, $token_id );
		// Return false if amount is not valid
		if ( empty( trim( $token_amount ) ) ) {
			return false;
		}

		// Get payment transaction details from Solana chain
		$rtn =
			$this->get_transaction_id( $meta, $txn_id ) &&
			$this->get_transaction_details( $txn_id, $txn_data ) &&
			$this->get_transaction_balance( $order, $txn_data, $token_id, $balance );

		// Return false in case of any error
		if ( ! $rtn ) {
			return false;
		}

		// Validate payment amount. Return false if payment is missing or not up to expected amount
		if ( ! $this->validate_payment_amount( $token_amount, $balance, $paid ) ) {
			return false;
		}

		// Add transaction url, payer and amount paid to order meta info
		$meta['url'] = $this->get_explorer_url( $txn_id );
		$meta['payer'] = $this->get_transaction_payer( $txn_data );
		$meta['paid'] = $paid;

		// update order meta info
		$this->hGateway->set_order_payment_meta( $order, $meta );

		// Complete payment and return true
		$order->add_order_note( __( 'Solana Pay payment completed', 'wc-solana-pay' ) );
		$order->payment_complete( $txn_id );

		return true;

	}


	/**
	 * Get Solana tokens available for payments and calculate how much the cost of the order in each token.
	 * Developer Commission for RPC usage is separated out if merchant own RPC is not provided.
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
					'mint' => $tokens[ $k ]['mint']
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
	 * RPC endpoint based on testmode.
	 *
	 * @param  bool   $testmode Testmode status flag; true if in Testmode, false otherwise.
	 * @return string
	 */
	public static function rpc_endpoint( $testmode ) {

		return $testmode ? self::RPC_ENDPOINT_DEVNET : self::RPC_ENDPOINT_MAINNET_BETA;

	}


	/**
	 * Get RPC and Transaction Endpoints usage fee.
	 *
	 * @return string
	 */
	public static function endpoints_usage_fee() {

		return self::ENDPOINT_USAGE_FEE;

	}


	/**
	 * Register payment transaction details with remote server.
	 *
	 * @param  string $ref_id   Remote reference ID of the transaction.
	 * @param  string $data     Payment transaction details.
	 * @param  bool   $testmode Testmode status flag; true if in Testmode, false otherwise.
	 * @return array|null
	 */
	public static function register_payment_details( $ref_id, $data, $testmode ) {

		$rtn = null;
		$network = $testmode ? self::NETWORK_DEVNET : self::NETWORK_MAINNET_BETA;
		$url = sprintf( '%s%s/?network=%s', self::TRANSACTION_ENDPOINT, $ref_id, $network );

		$response = wp_remote_post( $url, array(
			'method'      => 'POST',
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'timeout'     => 45,
			'body'        => wp_json_encode( $data ),
			'data_format' => 'body',
			)
		);

		if ( ! is_wp_error( $response ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {
			$response_body = wp_remote_retrieve_body( $response );
			$rtn = json_decode( $response_body, true );
		}

		return $rtn;

	}


	/**
	 * Get payment transaction from remote server.
	 *
	 * @param  string $ref_id   Remote reference ID of the transaction.
	 * @param  string $address  Wallet address of the transaction fee payer.
	 * @param  string $token_id Payment token ID.
	 * @param  bool   $testmode Testmode status flag; true if in Testmode, false otherwise.
	 * @return string
	 */
	public static function get_payment_transaction( $ref_id, $address, $token_id, $testmode ) {

		$txn = '';
		$network = $testmode ? self::NETWORK_DEVNET : self::NETWORK_MAINNET_BETA;
		$url = sprintf( '%s%s/?account=%s&token=%s&network=%s', self::TRANSACTION_ENDPOINT, $ref_id, $address, $token_id, $network );
		$response = wp_remote_get( $url, array(
			'method'      => 'GET',
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'timeout'     => 10,
			)
		);
		if ( ! is_wp_error( $response ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {
			$response_body = wp_remote_retrieve_body( $response );
			$response_array = json_decode( $response_body, true );
			$txn = array_key_exists( 'txn', $response_array ) ? $response_array['txn'] : '';
		}

		return $txn;

	}

}

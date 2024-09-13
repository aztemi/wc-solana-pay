<?php
/**
 * A class to manage Solana Tokens supported by this plugin.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


class Solana_Tokens {

	/**
	 * Suffix added to token key codes to make them unique in WC currencies list.
	 *
	 * @var string
	 */
	protected const TOKEN_KEY_SUFFIX   = '_SOLANA';


	/**
	 * Unique Key for storing tokens table settings in WP Option array.
	 *
	 * @var string
	 */
	public const TOKENS_OPTION_KEY = PLUGIN_ID . '_tokens';


	/**
	 * List of Solana tokens supported for Devnet (Testmode).
	 *
	 * @var array
	 */
	protected static $testmode_tokens;


	/**
	 * List of Solana tokens supported for Mainnet-Beta (Live mode).
	 *
	 * @var array
	 */
	protected static $livemode_tokens;


	/**
	 * List of all supported Solana tokens by this plugin.
	 *
	 * @var array
	 */
	protected static $supported_tokens;


	public function __construct() {
		// load supported tokens
		$this->load_supported_tokens();

		// register hooks that will add supported tokens to the WC Currencies list
		$this->register_hooks();
	}


	/**
	 * Load supported tokens list.
	 */
	private function load_supported_tokens() {
		$file_path = '/assets/json/supported_solana_tokens.json';
		self::$supported_tokens = $this->load_tokens_json( $file_path );

		// update live & testmode tokens list
		self::$livemode_tokens = self::$supported_tokens;
		self::$testmode_tokens = self::$supported_tokens;
		foreach ( self::$supported_tokens as $k => $v ) {
			self::$testmode_tokens[ $k ]['mint'] = $v['mint_devnet'];
		}
	}


	/**
	 * Register filters for adding WC currencies
	 */
	private function register_hooks() {
		// add filters for handling WC currencies
		add_filter( 'woocommerce_currencies', array( $this, 'add_woocommerce_currencies' ) );
		add_filter( 'woocommerce_currency_symbols', array( $this, 'add_woocommerce_currency_symbols' ) );
		add_filter( 'woocommerce_currency_symbol', array( $this, 'get_woocommerce_currency_symbol' ), 10, 2 );
	}


	/**
	 * Load tokens list from configuration json files
	 *
	 * @param  string $json_relpath Relative path of json file
	 * @return array Tokens list
	 */
	private function load_tokens_json( $json_relpath ) {
		$tokens = array();

		$json = file_get_contents( PLUGIN_DIR . $json_relpath );
		$loaded_tokens = json_decode( $json, true );

		// Add suffix to tokens codes to make them unique
		foreach ( $loaded_tokens as $k => $v ) {
			$code = strtoupper( $k . self::TOKEN_KEY_SUFFIX );
			$tokens[ $code ] = $v;
		}

		return $tokens;
	}


	/**
	 * Add supported Solana tokens to WC Currencies list.
	 */
	public function add_woocommerce_currencies( $currencies ) {
		// add all supported tokens to the currencies list
		foreach ( self::$supported_tokens as $k => $v ) {
			$currencies[ $k ] = $v['name'];
		}

		// sort the list to keep the frontend UI order
		ksort( $currencies, SORT_NATURAL|SORT_FLAG_CASE );

		return $currencies;
	}


	/**
	 * Add symbols of supported Solana tokens to WC Currencies list.
	 */
	public function add_woocommerce_currency_symbols( $currencies ) {
		// add all supported tokens to the currencies list
		foreach ( self::$supported_tokens as $k => $v ) {
			$currencies[ $k ] = $v['symbol'];
		}

		return $currencies;
	}


	/**
	 * Provide symbols of supported Solana tokens when requested.
	 */
	public function get_woocommerce_currency_symbol( $symbol, $currency ) {
		if ( array_key_exists( $currency, self::$supported_tokens ) ) {
			$symbol = self::$supported_tokens[ $currency ]['symbol'];
		}

		return $symbol;
	}


	/**
	 * Get list of Solana Tokens supported for Devnet (Testmode)
	 *
	 * @return array Tokens list
	 */
	public static function get_tokens_for_testmode() {
		/**
		 * Filters a list of Tokens supported for the Testmode.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'solana_pay_for_wc_testmode_tokens', self::$testmode_tokens );
	}


	/**
	 * Get list of Solana Tokens supported for Mainnet-Beta (Live mode)
	 *
	 * @return array Tokens list
	 */
	public static function get_tokens_for_livemode() {
		/**
		 * Filters a list of Tokens supported for the Live mode.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'solana_pay_for_wc_livemode_tokens', self::$livemode_tokens );
	}


	/**
	 * Get store base currency code.
	 *
	 * @param string $context 'view' or 'edit' context. Return value is formatted for display if context is view.
	 * @return string
	 */
	public static function get_store_currency( $context = 'view' ) {
		$currency = get_woocommerce_currency();

		// remove our suffix if context is view and the currency is one of our supported tokens
		if ( ( 'view' === $context ) && array_key_exists( $currency, self::$supported_tokens ) ) {
			$currency = str_replace( self::TOKEN_KEY_SUFFIX, '', $currency );
		}

		return $currency;
	}


	/**
	 * Get store base currency code suffix.
	 *
	 * @return string
	 */
	public static function get_store_currency_key_suffix() {
		return self::TOKEN_KEY_SUFFIX;
	}


	/**
	 * Check if rate conversion lookup is supported for store base currency or not.
	 *
	 * @return bool
	 */
	public static function is_rate_conversion_supported() {
		// list of currencies supported for rate lookup in the backend
		$supported_currencies = array(
			// Solana tokens supported
			'usdc',
			'usdt',
			'pyusd',
			'eurc',
			'euroe',
			'sol',

			// WC currencies supported on Coingecko since rate conversion uses Coingecko API
			'usd',
			'aed',
			'ars',
			'aud',
			'bdt',
			'bhd',
			'bmd',
			'brl',
			'cad',
			'chf',
			'clp',
			'cny',
			'czk',
			'dkk',
			'eur',
			'gbp',
			'gel',
			'hkd',
			'huf',
			'idr',
			'ils',
			'inr',
			'jpy',
			'krw',
			'kwd',
			'lkr',
			'mmk',
			'mxn',
			'myr',
			'ngn',
			'nok',
			'nzd',
			'php',
			'pkr',
			'pln',
			'rub',
			'sar',
			'sek',
			'sgd',
			'thb',
			'try',
			'twd',
			'uah',
			'vef',
			'vnd',
			'zar',
		);

		$store_currency = self::get_store_currency();
		return in_array( strtolower( $store_currency ), $supported_currencies );
	}


	/**
	 * Get testmode faucet tip message.
	 *
	 * @return string
	 */
	public static function testmode_faucet_tip() {
		$faucet_url = 'https://apps.aztemi.com/wc-solana-pay/faucet/';
		$faucet_link = '<a href="' . $faucet_url . '" target="_blank" rel="noopener noreferrer"><b>' . esc_html__( 'Devnet Faucet', 'wc-solana-pay' ) . '</b></a>';
		/* translators: %s: Devnet Faucet */
		return '<p>' . sprintf( esc_html__( 'Get free tokens for testing from the %s.', 'wc-solana-pay' ), $faucet_link ) . '</p>';
	}
}

<?php
/**
 * A class to manage Solana Tokens supported by this plugin.
 *
 * @package AZTemi\Solana_Pay_for_WC
 */

namespace AZTemi\Solana_Pay_for_WC;

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
		$this->load_testmode_tokens();
		$this->load_livemode_tokens();

		// register hooks that will add supported tokens to the WC Currencies list
		$this->register_hooks();

	}


	/**
	 * Load Testmode tokens list.
	 */
	private function load_testmode_tokens() {

		$file_path_testmode = '/assets/json/supported_solana_tokens_devnet.json';
		self::$testmode_tokens = $this->load_tokens_json( $file_path_testmode );

		// update all supported tokens list
		self::$supported_tokens = array_merge( self::$supported_tokens ?? array(), self::$testmode_tokens );

	}


	/**
	 * Load Live mode tokens list.
	 */
	private function load_livemode_tokens() {

		$file_path_live = '/assets/json/supported_solana_tokens_mainnet_beta.json';
		self::$livemode_tokens = $this->load_tokens_json( $file_path_live );

		// update all supported tokens list
		self::$supported_tokens = array_merge( self::$supported_tokens ?? array(), self::$livemode_tokens );

	}


	/**
	 * Register filters for adding WC currencies
	 */
	private function register_hooks() {

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

}

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
	 * Default scale precision for bc math functions.
	 *
	 * @var int
	 */
	private const BC_MATH_SCALE = 6;


	/**
	 * Suffix added to token key codes to make them unique in WC currencies list.
	 *
	 * @var string
	 */
	protected const TOKEN_KEY_SUFFIX   = '_SOLANA';


	/**
	 * Cron schedule event hook name to update tokens rate every hour.
	 *
	 * @var string
	 */
	protected const TOKENS_RATE_UPDATE_HOOK = PLUGIN_ID . '_update_rate_event';


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

		// load dependencies
		$this->load_dependencies();

		// load supported tokens
		$this->load_testmode_tokens();
		$this->load_livemode_tokens();

		// register hooks that will add supported tokens to the WC Currencies list
		$this->register_hooks();

	}


	/**
	 * Load required dependencies for this class.
	 */
	private function load_dependencies() {

		// load Cron Event class for hourly update
		require_once PLUGIN_DIR . '/admin/class-cronjob.php';
		new Cronjob( self::TOKENS_RATE_UPDATE_HOOK );

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

		// add filters for handling WC currencies
		add_filter( 'woocommerce_currencies', array( $this, 'add_woocommerce_currencies' ) );
		add_filter( 'woocommerce_currency_symbols', array( $this, 'add_woocommerce_currency_symbols' ) );
		add_filter( 'woocommerce_currency_symbol', array( $this, 'get_woocommerce_currency_symbol' ), 10, 2 );

		// add action to update tokens rates at scheduled intervals
		add_action( self::TOKENS_RATE_UPDATE_HOOK, array( __CLASS__, 'update_tokens_prices' ) );

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
	 * Get list of currencies supported by Coingecko's rate lookup APIs.
	 *
	 * @return array Supported currencies list
	 */
	private static function get_coingecko_supported_currencies() {

		static $currencies_list = array();

		if ( ! count( $currencies_list ) ) {
			$url = 'https://api.coingecko.com/api/v3/simple/supported_vs_currencies';
			$response = wp_remote_get( $url, array(
				'method'  => 'GET',
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'timeout' => 10,
				)
			);
			if ( ! is_wp_error( $response ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {
				$response_body = wp_remote_retrieve_body( $response );
				$currencies_list = json_decode( $response_body, true );
			}
		}

		return $currencies_list;

	}


	/**
	 * Get current prices of tokens in specified currecy using Coingecko's rate lookup API.
	 *
	 * @param  array  $tokens   List of tokens to get their prices
	 * @param  string $currency Base currency
	 * @return array Tokens to prices list
	 */
	private static function get_coingecko_tokens_prices( $tokens, $currency ) {

		$rtn = array();
		$token_str = trim( implode( ',', $tokens ) );
		$currency = trim( strtolower( $currency ) );

		if ( ! empty( $token_str ) && ! empty( $currency ) ) {

			// get prices from Coingecko API
			$url = sprintf( 'https://api.coingecko.com/api/v3/simple/price?ids=%s&vs_currencies=%s', $token_str, $currency );
			$response = wp_remote_get( $url, array(
				'method'  => 'GET',
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'timeout' => 10,
				)
			);

			// validate the result
			if ( ! is_wp_error( $response ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {
				$response_body = wp_remote_retrieve_body( $response );
				$prices = json_decode( $response_body, true );

				// update return list if response is valid and requested tokens are in the response
				if ( is_array( $prices ) ) {
					foreach ( $prices as $token => $price ) {
						if ( is_array( $price ) && array_key_exists( $currency, $price ) ) {
							$rtn[ $token ] = $price[ $currency ];
						}
					}
				}
			}

		}

		return $rtn;

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
	 * Update Solana tokens prices.
	 */
	public static function update_tokens_prices() {

		// prevent back-to-back execution
		static $last_execution;
		if ( isset( $last_execution ) ) {
			return;
		}

		// ensure time between execution is over 1 hour
		$one_hour = 1 * 60 * 60;
		$last_execution = get_option( self::TOKENS_RATE_UPDATE_HOOK, 0 );
		if ( time() - $last_execution < $one_hour ) {
			return;
		}

		$tokens_table = get_option( self::TOKENS_OPTION_KEY, array() );

		$supported_tokens = self::$supported_tokens;
		$store_currency = self::get_store_currency();
		$coingecko_tokens = array_column( $supported_tokens, 'coingecko' );
		$coingecko_prices = self::get_coingecko_tokens_prices( $coingecko_tokens, $store_currency );

		$old_scale = bcscale( self::BC_MATH_SCALE ); // set scale precision

		foreach ( $tokens_table as $token => $v ) {
			if ( array_key_exists( $token, $supported_tokens ) ) {
				$token_coingecko = $supported_tokens[ $token ]['coingecko'];

				if ( array_key_exists( $token_coingecko, $coingecko_prices ) ) {
					// update token rate
					$rate = bcdiv( '1', $coingecko_prices[ $token_coingecko ] );
					$tokens_table[ $token ]['rate'] = rtrim( $rate, '0' );
				}
			}

		}

		bcscale( $old_scale ); // reset back to old scale

		// update last successful execution
		update_option( self::TOKENS_OPTION_KEY, $tokens_table );
		update_option( self::TOKENS_RATE_UPDATE_HOOK, time() );

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
	 * It checks if store currency is part of Coingecko supported currencies since rate conversion uses Coingecko API.
	 *
	 * @return bool
	 */
	public static function is_rate_conversion_supported() {

		$store_currency = self::get_store_currency();
		$coingecko_currencies = self::get_coingecko_supported_currencies();

		return in_array( strtolower( $store_currency ), $coingecko_currencies );

	}

}

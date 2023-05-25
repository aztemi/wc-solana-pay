<?php
/**
 * The plugin core class.
 * It validates plugin dependencies and proceeds to load the payment gateway if all dependencies are available.
 *
 * @package AZTemi\Solana_Pay_for_WC
 */

namespace AZTemi\Solana_Pay_for_WC;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


class Solana_Pay_For_WooCommerce {

	public function __construct() {

		$this->load_dependencies();
		$this->register_hooks();

	}


	/**
	 * Load required dependencies for this class.
	 */
	private function load_dependencies() {

		// load plugin helper functions
		require_once PLUGIN_DIR . '/includes/functions.php';

	}


	/**
	 * Register action hooks
	 */
	private function register_hooks() {

		// load text domain for translation
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

	}


	/**
	 * Validate plugin dependencies.
	 *
	 * @return bool true if all dependencies are installed and activated, false otherwise.
	 */
	private function is_available() {

		$available = true;

		// check if WooCommerce is installed and activated
		if ( ! is_woocommerce_activated() ) {
			show_error_notice( __( '<b>Solana Pay for WooCommerce</b> is an extension for <b>WooCommerce</b>. Please install and activate <b>WooCommerce</b> plugin.', 'solana-pay-for-woocommerce' ) );
			$available = false;
		}

		// check if BC Math extension for bignumbers handling is installed
		if ( ! is_bcmath_installed() ) {
			show_error_notice( __( '<b>Solana Pay for WooCommerce</b> requires <b>BC Math</b>. Please install <b>BC Math</b> extension for PHP.', 'solana-pay-for-woocommerce' ) );
			$available = false;
		}

		return $available;

	}


	/**
	 * Load plugin text domain translation
	 */
	public function load_textdomain() {

		load_plugin_textdomain( 'solana-pay-for-woocommerce', false, dirname( PLUGIN_BASENAME ) . '/languages' );

	}


	/**
	 * Register and execute hooks if all dependencies are available
	 */
	public function run() {

		// return if any dependency is not available
		if ( ! $this->is_available() ) {
			return;
		}

		// load admin class and initialize its instance
		require_once PLUGIN_DIR . '/admin/class-solana-pay-for-woocommerce-admin.php';
		new Solana_Pay_For_WooCommerce_Admin();

	}

}

<?php
/**
 * The plugin core class.
 * It validates plugin dependencies and proceeds to load the payment gateway if all dependencies are available.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


class WC_Solana_Pay {

	public function __construct() {
		$this->load_dependencies();
		$this->declare_wc_hpos_compatibility();
		$this->register_hooks();
	}


	/**
	 * Load required dependencies for this class.
	 */
	private function load_dependencies() {
		require_once PLUGIN_DIR . '/includes/functions.php';
		require_once PLUGIN_DIR . '/admin/class-wc-solana-pay-admin.php';
		require_once PLUGIN_DIR . '/admin/class-solana-tokens.php';
		require_once PLUGIN_DIR . '/admin/class-wc-solana-pay-payment-gateway.php';
	}


	/**
	 * Declare compatibility for WooCommerce High-Performance Order Storage (HPOS)
	 */
	public function declare_wc_hpos_compatibility() {
		add_action(
			'before_woocommerce_init',
			function () {
				if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', PLUGIN_FILE, true );
				}
			}
		);
	}


	/**
	 * Register action hooks
	 */
	private function register_hooks() {
		// start plugin on init hook
		add_action( 'init', array( $this, 'run' ) );

		// register Solana Pay payment gateway class
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_payment_gateway_class' ) );

		// register WooCommerce Blocks integration class
		add_action( 'woocommerce_blocks_loaded', array( $this, 'register_block_support_class' ) );
	}


	/**
	 * Register and execute hooks if all dependencies are available
	 */
	public function run() {
		// load text domain for translation
		$this->load_textdomain();

		// return if any dependency is not available
		if ( ! $this->is_available() ) {
			return;
		}

		// initialize admin class instance
		new WC_Solana_Pay_Admin();

		// initialize Solana tokens class for the store cryptocurrency handling
		new Solana_Tokens();
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
			show_error_notice( __( '<b>WC Solana Pay</b> plugin is an extension for <b>WooCommerce</b>. Please install and activate <b>WooCommerce</b> plugin.', 'wc-solana-pay' ) );
			$available = false;
		}

		// check if BC Math extension for bignumbers handling is installed
		if ( ! is_bcmath_installed() ) {
			show_error_notice( __( '<b>WC Solana Pay</b> plugin requires <b>BC Math</b>. Please install <b>BC Math</b> extension for PHP.', 'wc-solana-pay' ) );
			$available = false;
		}

		return $available;
	}


	/**
	 * Load plugin text domain translation
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'wc-solana-pay', false, dirname( PLUGIN_BASENAME ) . '/languages' );
	}


	/**
	 * Register payment gateway class
	 *
	 * @param  array $gateways List of gateways currently registered
	 * @return array Extended gateways list
	 */
	public function register_payment_gateway_class( $gateways = array() ) {
		$gateways[] = __NAMESPACE__ . '\WC_Solana_Pay_Payment_Gateway';
		return $gateways;
	}


	/**
	 * Register WooCommerce Blocks integration class
	 */
	public function register_block_support_class() {
		// check if block is in use for the Checkout page
		$has_block_checkout = \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::is_checkout_block_default();

		// load block if in Admin page or Checkout has block
		$load_block = is_admin() || $has_block_checkout;

		if ( $load_block && class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once PLUGIN_DIR . '/admin/class-wc-solana-pay-payment-block.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new WC_Solana_Pay_Payment_Block() );
				}
			);
		}
	}
}

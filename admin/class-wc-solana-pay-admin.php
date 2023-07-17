<?php
/**
 * The main admin class of the plugin.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


class WC_Solana_Pay_Admin {

	public function __construct() {

		$this->load_dependencies();
		$this->register_hooks();

	}


	/**
	 * Load required dependencies for this class.
	 */
	private function load_dependencies() {

		// load Solana tokens class for Store cryptocurrency handling
		require_once PLUGIN_DIR . '/admin/class-solana-tokens.php';
		new Solana_Tokens();

	}


	/**
	 * Register all actions and filters needed to start the plugin
	 */
	private function register_hooks() {

		// register Solana Pay payment gateway class
		add_action( 'plugins_loaded', array( $this, 'load_payment_gateway_class' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_payment_gateway_class' ) );

		// Add 'Settings' link to the Installed Plugins page after plugin activation
		add_filter( 'plugin_action_links_' . PLUGIN_BASENAME, array( $this, 'add_action_links' ) );

	}

	/**
	 * Load payment gateway class
	 */
	public function load_payment_gateway_class() {

		require_once PLUGIN_DIR . '/admin/class-wc-solana-pay-payment-gateway.php';

	}


	/**
	 * Register payment gateway class
	 *
	 * @param  array $gateways List of gateways currently registered
	 * @return array Extended gateways list
	 */
	public function register_payment_gateway_class( $gateways = [] ) {

		$gateways[] = __NAMESPACE__ . '\WC_Solana_Pay_Payment_Gateway';
		return $gateways;

	}


	/**
	 * Add 'Settings' action link to the Installed Plugins admin page
	 *
	 * @param  array $links List of action links
	 * @return array Extended action links list
	 */
	public function add_action_links( $links ) {

		if ( current_user_can( 'manage_woocommerce' ) ) {
			$settings_link = sprintf(
													'<a href="%1$s">%2$s</a>',
													admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . PLUGIN_ID ),
													__( 'Settings', 'wc-solana-pay' )
											 );

			array_unshift( $links, $settings_link );
		}

		return $links;

	}

}

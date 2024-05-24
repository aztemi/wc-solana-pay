<?php
/**
 * The main admin class of the plugin.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

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
		// declare compatibility for HPOS
		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );

		// register Solana Pay payment gateway class
		add_action( 'plugins_loaded', array( $this, 'load_payment_gateway_class' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_payment_gateway_class' ) );

		// register WooCommerce Blocks integration class
		add_action( 'woocommerce_blocks_loaded', array( $this, 'register_block_support_class' ) );

		// Add 'Settings' link to the Installed Plugins page after plugin activation
		add_filter( 'plugin_action_links_' . PLUGIN_BASENAME, array( $this, 'add_action_links' ) );

		// register an endpoint for handling REST calls
		add_action( 'rest_api_init', array( $this, 'register_rest_endpoint' ) );
	}


	/**
	 * Declare compatibility for Woo High-Performance Order Storage (HPOS)
	 */
	public function declare_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', PLUGIN_FILE, true );
		}
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
	public function register_payment_gateway_class( $gateways = array() ) {
		$gateways[] = __NAMESPACE__ . '\WC_Solana_Pay_Payment_Gateway';
		return $gateways;
	}


	/**
	 * Register WooCommerce Blocks integration class
	 */
	public function register_block_support_class() {
		// check if block is in use for the Checkout page
		$checkout_page_id = wc_get_page_id( 'checkout' );
		$has_block_checkout = $checkout_page_id && has_block( 'woocommerce/checkout', $checkout_page_id );

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


	/**
	 * Action callback for registering REST API endpoint
	 */
	public function register_rest_endpoint() {
		register_rest_route( PLUGIN_ID . '/v1', '/api', array(
			'methods'  => 'GET, POST',
			'callback' => array( $this, 'handle_api_request' ),
			'permission_callback' => '__return_true',
		));
	}


	/**
	 * A handler for incoming /api endpoint request
	 *
	 * @param  WP_REST_Request  $request Incoming Request object
	 */
	public function handle_api_request( $request ) {
		$action = trim( wc_clean( wp_unslash( $request->get_param('action') ) ) );
		if ( $action ) {
			/**
			 * Action hook fired to handle incoming REST request.
			 *
			 * @since 2.1.1
			 */
			do_action( PLUGIN_ID . '_' . $action, $request );
		} else {
			wp_send_json_error( 'Bad Request', 400 );
		}
	}
}

<?php
/**
 * The gateway extension class that handles payment logic based on WC specs.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


// return if WooCommerce payment gateway class is missing
if ( ! class_exists( '\WC_Payment_Gateway' ) ) {
	return;
}

class WC_Solana_Pay_Payment_Gateway extends \WC_Payment_Gateway {

	/**
	 * Unique Key to store payment info in an order metadata.
	 *
	 * @var string
	 */
	protected const ORDER_META_KEY = PLUGIN_ID . '_payment';


	/**
	 * Testmode flag; true if Testmode is enabled, false otherwise.
	 *
	 * @var bool
	 */
	protected $is_testmode;


	/**
	 * Merchant Solana wallet address where all payments will be sent.
	 *
	 * @var string
	 */
	protected $merchant_wallet;


	/**
	 * Merchant or store name that will be displayed in payment instructions.
	 *
	 * @var string
	 */
	protected $brand_name;


	/**
	 * Payment description that customers will see on the block checkout page.
	 *
	 * @var string
	 */
	public $block_desc;


	/**
	 * List of Solana tokens that are accepted as store currency.
	 *
	 * @var array
	 */
	protected $tokens_table;


	/**
	 * Handle instance of the Session class for managing user data stored in WC session.
	 *
	 * @var Session
	 */
	protected $hSession;


	/**
	 * Handle instance of the Solana_Pay class for Solana payment verification.
	 *
	 * @var Solana_Pay
	 */
	protected $hSolanapay;


	public function __construct() {
		// load dependencies
		$this->load_dependencies();

		// setup general properties
		$this->setup_properties();

		// load configutation settings
		$this->get_settings();

		// add hooks
		$this->register_hooks();
	}


	/**
	 * Load required dependencies for this class.
	 */
	private function load_dependencies() {
		// Load session class and initialize session
		require_once PLUGIN_DIR . '/admin/class-session.php';
		$this->hSession = new Session();

		// load Solana Pay class
		require_once PLUGIN_DIR . '/admin/class-solana-pay.php';
		$this->hSolanapay = new Solana_Pay( $this, $this->hSession );

		// load webhook class for handling incoming GET request
		require_once PLUGIN_DIR . '/admin/class-webhook.php';
		new Webhook( $this, $this->hSession );

		// load public class
		require_once PLUGIN_DIR . '/public/class-wc-solana-pay-public.php';
		new WC_Solana_Pay_Public();
	}


	/**
	 * Initialize basic properties inheritted from WC_Payment_Gateway class
	 */
	private function setup_properties() {
		$this->id                 = PLUGIN_ID;
		$this->icon               = PLUGIN_URL . '/assets/img/solana_pay_black_gradient.svg';
		$this->has_fields         = false;
		$this->supports           = array( 'products' );
		$this->method_title       = __( 'WC Solana Pay', 'wc-solana-pay' );
		$this->title              = $this->method_title;
		$this->method_description = __( 'Accept payments in SOL, USDT, USDC, EURC and more with Solana Pay.', 'wc-solana-pay' );
	}


	/**
	 * Create Admin Settings form and update configuration settings
	 */
	private function get_settings() {
		// load settings
		$this->init_form_fields();
		$this->init_settings();

		// update configurations
		$this->title           = $this->get_option( 'title', $this->title );
		$this->enabled         = $this->get_option( 'enabled' );
		$this->brand_name      = $this->get_option( 'brand_name' );
		$this->description     = $this->get_option( 'description' );
		$this->merchant_wallet = $this->get_option( 'merchant_wallet', '' );
		$this->is_testmode     = Solana_Pay::NETWORK_MAINNET_BETA !== $this->get_option( 'network', Solana_Pay::NETWORK_DEVNET );
		$this->block_desc      = $this->description;

		// update settings that depend on testmode status
		if ( $this->is_testmode ) {
			$testmode_msg = ' <b>' . esc_html__( '(Test Mode enabled. Devnet in use).', 'wc-solana-pay' ) . '</b>';
			$this->method_description .= $testmode_msg;
			$this->description .= $testmode_msg . Solana_Tokens::testmode_faucet_tip();
		}

		// Get saved settings for the supported Solana tokens table
		$this->tokens_table = get_option( Solana_Tokens::TOKENS_OPTION_KEY, array() );
	}


	/**
	 * Register actions and filters for the payment gateway
	 */
	private function register_hooks() {
		// Save Admin page settings
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_tokens_table' ) );

		// Add Solana Pay payment details on Order page
		add_filter( 'woocommerce_admin_order_data_after_order_details', array( $this, 'add_payment_details_to_admin_order_page' ) );
		add_filter( 'woocommerce_order_details_after_order_table', array( $this, 'add_payment_details_to_public_order_page' ) );

		// export global JS variables
		add_action( 'wp_head', array( $this, 'export_global_js_variables' ) );
	}


	/**
	 * Check if our payment gateway is available for use in the frontend.
	 */
	public function is_available() {
		// Return false if our payment gateway is disabled
		if ( 'no' === $this->enabled ) {
			return false;
		}

		$rtn = true;
		// Return false if merchant wallet is not configured
		if ( empty( $this->merchant_wallet ) ) {
			\WC_Admin_Settings::add_error( esc_html__( 'Solana Pay setup is not complete. Please set Merchant Wallet Address', 'wc-solana-pay' ) );
			$rtn = false;
		}

		// Return false if no token is enabled
		$token_enabled = false;
		foreach ( $this->tokens_table as $k => $v ) {
			if ( $v['enabled'] ) {
				$token_enabled = true;
			}
		}
		if ( ! $token_enabled ) {
			\WC_Admin_Settings::add_error( esc_html__( 'Solana Pay setup is not complete. Please enable at least 1 Solana Token.', 'wc-solana-pay' ) );
			$rtn = false;
		}

		return $rtn && parent::is_available();
	}


	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = include PLUGIN_DIR . '/admin/partials/admin-settings.php';
	}


	/**
	 * Validate wallet address settings field. Clear the field in case of error.
	 * This is called from WC to validate a form field.
	 *
	 * @param  string $key  Field key.
	 * @param  string $value Field data.
	 * @return string
	 */
	public function validate_merchant_wallet_field( $key, $value ) {
		if ( ! preg_match( '/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $value ) ) {
			\WC_Admin_Settings::add_error( esc_html__( 'Invalid Solana Wallet Address', 'wc-solana-pay' ) );
			$value = ''; // WC saves any return value despite error; empty it to prevent a wrong value from being saved.
		}

		return $value;
	}


	/**
	 * Export global JS variables common for both admin and public pages.
	 */
	public function export_global_js_variables() {
		// Get order-id if on Order Pay page
		$pay_page = is_checkout_pay_page();
		$order_id = '';
		if ( $pay_page ) {
			$order_id = absint( get_query_var( 'order-pay' ) );
		}

		$payload = array(
			'id'       => PLUGIN_ID,
			'baseurl'  => PLUGIN_URL,
			'apiurl'   => esc_url( get_rest_url() ),
			'pay_page' => $pay_page,
			'order_id' => $order_id,
		);

		$script = 'var WC_SOLANA_PAY = ' . wp_json_encode( $payload );
		wp_print_inline_script_tag( $script );
	}


	/**
	 * Generate html for tokens table.
	 * This is called from WC to generate the custom table for currency selection on Admin Settings page.
	 *
	 * @param  string $key  Field key.
	 * @param  array  $data Field data.
	 * @return string
	 */
	public function generate_tokens_table_html( $key, $data ) {
		$html = '';
		$tablejs = get_script_path('/assets/script/admin_tokens_table*.js');

		if ( $tablejs ) {
			$base_currency = Solana_Tokens::get_store_currency('edit');
			$show_currency = Solana_Tokens::get_store_currency();
			$script = get_partial_file_html( $tablejs );

			$html = get_partial_file_html(
				'/admin/partials/admin-tokens-table.php',
				array(
					'tip'             => $data['desc_tip'],
					'title'           => $data['title'],
					'script'          => $script,
					'base_currency'   => $base_currency,
					'show_currency'   => $show_currency,
					'tokens_table'    => $this->tokens_table,
					'live_tokens'     => Solana_Tokens::get_tokens_for_livemode(),
					'testmode_tokens' => Solana_Tokens::get_tokens_for_testmode(),
				)
			);
		}

		return $html;
	}


	/**
	 * Save tokens table admin settings
	 */
	public function save_tokens_table() {
		$tokens = array();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verification already handled in WC_Admin_Settings::save()
		if (
			isset( $_POST['pwspfwc_id'] ) &&
			isset( $_POST['pwspfwc_rate'] )
		) {
			$ids    = wc_clean( wp_unslash( $_POST['pwspfwc_id'] ) );
			$rates  = wc_clean( wp_unslash( $_POST['pwspfwc_rate'] ) );

			foreach ( $ids as $i => $id ) {
				if ( ! isset( $ids[ $i ] ) ) {
					continue;
				}

				$tokens[ $ids[ $i ] ] = array(
					'id'          => $ids[ $i ],
					'rate'        => $rates[ $i ],
					'enabled'     => isset( $_POST['pwspfwc_enabled'][ $i ] ) ? true : false,
				);
			}
		}
		// phpcs:enable

		update_option( Solana_Tokens::TOKENS_OPTION_KEY, $tokens );
	}


	/**
	 * Process payment and return the result.
	 * This is called from WC to confirm payment for an order.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		// get order info and pending amount
		$order = wc_get_order( $order_id );
		$amount = $order->get_total();

		// Confirm payment transaction on Solana chain, return if not found or if balance is less.
		if ( ( $amount > 0 ) && ! $this->hSolanapay->confirm_payment_onchain( $order, $amount ) ) {
			return array(
				'result'       => 'failure',
				'redirect'     => wc_get_checkout_url(),
				'errorMessage' => __( 'Payment failed. Please try again.', 'wc-solana-pay' ),
			);
		}

		// Clear session
		$this->hSession->clear();

		// Remove cart
		if ( isset( WC()->cart ) ) {
			WC()->cart->empty_cart();
		}

		// Redirect to thank you page
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}


	/**
	 * Get transaction url from the order metadata.
	 *
	 * @param  \WC_Order $order Order object.
	 * @return string
	 */
	public function get_transaction_url( $order ) {
		$meta = $this->get_order_payment_meta( $order );

		if ( is_array( $meta ) && array_key_exists( 'url', $meta ) ) {
			$this->view_transaction_url = $meta['url'];
		}

		return parent::get_transaction_url( $order );
	}


	/**
	 * Save this plugin payment info to the metadata of an order.
	 *
	 * @param \WC_Order $order Order object.
	 * @param array     $meta  Metadata to store.
	 */
	public function set_order_payment_meta( $order, $meta ) {
		$order->update_meta_data( self::ORDER_META_KEY, $meta );
		$order->save_meta_data();
	}


	/**
	 * Get this plugin payment info from the metadata of an order.
	 *
	 * @param  \WC_Order $order Order object.
	 * @return array
	 */
	public function get_order_payment_meta( $order ) {
		if ( $this->id === $order->get_payment_method() ) {
			$meta = $order->get_meta( self::ORDER_META_KEY );
		}

		if ( ! isset( $meta ) || ! is_array( $meta ) ) {
			$meta = array();
		}

		return $meta;
	}


	/**
	 * Add payment details to the Admin Order Page
	 *
	 * @param  \WC_Order $order Order object.
	 * @return void
	 */
	public function add_payment_details_to_admin_order_page( $order ) {
		$meta = $this->get_order_payment_meta( $order );
		if ( count( $meta ) ) {
			echo wp_kses_post( get_partial_file_html( '/admin/partials/admin-payment-details.php', $meta ) );
		}
	}


	/**
	 * Add payment details to the public-facing Order Page
	 *
	 * @param  \WC_Order $order Order object.
	 * @return void
	 */
	public function add_payment_details_to_public_order_page( $order ) {
		$meta = $this->get_order_payment_meta( $order );
		if ( count( $meta ) ) {
			echo wp_kses_post( get_partial_file_html( '/public/partials/public-payment-details.php', $meta ) );
		}
	}


	/**
	 * Get a list of Solana Tokens acceptable by the store for payments
	 *
	 * @return array List of acceptable crypto tokens
	 */
	public function get_accepted_solana_tokens() {
		return $this->is_testmode ? Solana_Tokens::get_tokens_for_testmode() : Solana_Tokens::get_tokens_for_livemode();
	}


	/**
	 * Get list of accepted Solana tokens available for payments and how much the cost of the order in each token.
	 *
	 * @param  string $amount Order amount in the store base currency.
	 * @return array  List of payment options and their cost values.
	 */
	public function get_accepted_solana_tokens_payment_options( $amount ) {
		return $this->hSolanapay->get_available_payment_options( $amount );
	}


	/**
	 * Get Testmode
	 *
	 * @return bool
	 */
	public function get_testmode() {
		return $this->is_testmode;
	}


	/**
	 * Get configured Merchant Wallet Address
	 *
	 * @return string
	 */
	public function get_merchant_wallet_address() {
		return $this->merchant_wallet;
	}


	/**
	 * Get configured store brand name
	 *
	 * @return string
	 */
	public function get_brand_name() {
		return $this->brand_name;
	}


	/**
	 * Get list of configured Solana tokens that are accepted as store currencies.
	 *
	 * @return array
	 */
	public function get_tokens_table() {
		return $this->tokens_table;
	}
}

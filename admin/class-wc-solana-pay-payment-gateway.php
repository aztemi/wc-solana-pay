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
	 * Default price refresh interval in seconds.
	 *
	 * @var int
	 */
	private const PRICE_REFRESH_INTERVAL = 5 * MINUTE_IN_SECONDS;


	/**
	 * Backend response status code indicating the order was already registered and paid.
	 *
	 * @var int
	 */
	private const BACKEND_STATUS_ALREADY_PAID = 421;


	/**
	 * Testmode flag; true if Testmode is enabled, false otherwise.
	 *
	 * @var bool
	 */
	protected $is_testmode;


	/**
	 * Available payment modal display locations.
	 */
	public const MODAL_LOCATION_CHECKOUT      = 'checkout';
	public const MODAL_LOCATION_ORDER_RECEIPT = 'order_receipt';


	/**
	 * True if the payment popup modal appears on the checkout page, false otherwise.
	 *
	 * @var bool
	 */
	protected $is_checkoutpage;


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
		// load Solana Pay class
		require_once PLUGIN_DIR . '/admin/class-solana-pay.php';
		$this->hSolanapay = new Solana_Pay( $this );

		// load webhook class for handling incoming REST request
		require_once PLUGIN_DIR . '/admin/class-webhook.php';
		new Webhook( $this );

		// load public class
		require_once PLUGIN_DIR . '/public/class-wc-solana-pay-public.php';
		new WC_Solana_Pay_Public();
	}


	/**
	 * Initialize basic properties inheritted from WC_Payment_Gateway class
	 */
	private function setup_properties() {
		$this->id                 = PLUGIN_ID;
		$this->default_icon       = PLUGIN_URL . '/assets/img/solana_pay_black.svg';
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
		$this->icon            = $this->get_option( 'plugin_icon', $this->default_icon );
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

		// get payment modal placement setting
		$modal_location = $this->get_option( 'modal_location', self::MODAL_LOCATION_ORDER_RECEIPT );
		$this->is_checkoutpage = self::MODAL_LOCATION_CHECKOUT === $modal_location;
	}


	/**
	 * Register actions and filters for the payment gateway
	 */
	private function register_hooks() {
		// Save Admin page settings
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_custom_options' ) );

		// Handle payment on the pay order page
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'process_payment_on_pay_order_page' ) );

		// Add Solana Pay payment details on Order page
		add_filter( 'woocommerce_admin_order_data_after_order_details', array( $this, 'add_payment_details_to_admin_order_page' ) );
		add_filter( 'woocommerce_order_details_after_order_table', array( $this, 'add_payment_details_to_public_order_page' ) );

		// Add style to plugin icon when shown
		add_filter( 'woocommerce_gateway_icon', array( $this, 'get_icon_with_style' ), 10, 2 );

		// Condense transaction hash displayed on the order details page
		add_filter( 'woocommerce_order_get_transaction_id', array( $this, 'shorten_transaction_id' ), 10, 2 );

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
		// return if not on a checkout page
		if ( ! is_checkout_page() ) {
			return;
		}

		// Get order-id if on Order Pay page
		$pay_page = is_checkout_pay_page();
		$order_id = '';
		if ( $pay_page ) {
			$order_id = absint( get_query_var( 'order-pay' ) );
		}

		$payload = array(
			'id'        => PLUGIN_ID,
			'icon'      => $this->icon,
			'pluginUrl' => PLUGIN_URL,
			'apiUrl'    => $this->get_api_url(),
			'baseUrl'   => esc_url( get_rest_url() ),
			'payPage'   => $pay_page,
			'orderId'   => $order_id,
			'priceUpdateFreq' => self::PRICE_REFRESH_INTERVAL,
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
	 * Generate html for plugin icon.
	 * This is called from WC to generate the upload icon html on the Admin Settings page.
	 *
	 * @param  string $key  Field key.
	 * @param  array  $data Field data.
	 * @return string
	 */
	public function generate_plugin_icon_html( $key, $data ) {
		$html = '';
		$iconjs = get_script_path('/assets/script/admin_plugin_icon*.js');

		if ( $iconjs ) {
			$script = get_partial_file_html(
				$iconjs,
				array(
					'button_text'  => __( 'Use this image', 'wc-solana-pay' ),
					'select_title' => __( 'Select or Upload an Icon', 'wc-solana-pay' ),
				)
			);

			$html = get_partial_file_html(
				'/admin/partials/admin-plugin-icon.php',
				array(
					'tip'          => $data['desc_tip'],
					'title'        => $data['title'],
					'script'       => $script,
					'icon'         => $this->icon,
					'default_icon' => $this->default_icon,
				)
			);
		}

		return $html;
	}


	/**
	 * Save plugin's custom options from the admin settings
	 */
	public function save_custom_options() {
		$tokens = array();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verification already handled in WC_Admin_Settings::save()
		// Save plugin icon option
		if ( isset( $_POST['pwspfwc_plugin_icon'] ) ) {
			$this->icon = wc_clean( wp_unslash( $_POST['pwspfwc_plugin_icon'] ) );
			$this->update_option( 'plugin_icon', $this->icon );
		}

		// Save tokens table options
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
	 * Generate a checkout URL for the given order with a unique fragment to trigger the payment modal in frontend.
	 *
	 * @param \WC_Order $order
	 * @return string
	 */
	protected function get_payment_modal_url( $order ) {
		if ( ! $order ) {
			return wc_get_checkout_url();
		}

		// Base URL: checkout page or order-pay page
		$base_url = $this->is_checkoutpage ? wc_get_checkout_url() : $order->get_checkout_payment_url( true );

		// Append unique fragment to trigger the modal
		$fragment = sprintf( '#%s-%d@%d', $this->id, $order->get_id(), time() );

		return $base_url . $fragment;
	}


	/**
	 * Process payment and return the result.
	 * This is called from WC to confirm payment for an order.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		try {
			// get order info
			$order = wc_get_order( $order_id );

			// Determine whether order is already paid
			$is_paid = ( $order->is_paid() || ( 0 >= $order->get_total() ) );

			// if order not paid yet, register its details with the backend
			if ( ! $is_paid ) {
				$status = $this->register_order_details( $order );

				if ( self::BACKEND_STATUS_ALREADY_PAID === $status ) {
					// order seems paid, confirm payment onchain
					$res = $this->confirm_payment( $order_id );
					$is_paid = ( 'success' === $res['result'] );
				}
			}

			// if order is paid, cleanup and redirect
			if ( $is_paid ) {
				// Remove cart
				if ( isset( WC()->cart ) && WC()->cart ) {
					WC()->cart->empty_cart();
				}

				// Redirect to thank you page
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			}

			// Redirect customer to payment workflow
			return array(
				'result'   => 'success',
				'redirect' => $this->get_payment_modal_url( $order ),
			);
		} catch ( \Throwable $e ) {
			logger(
				sprintf(
					'Process payment failed with exception. Order: %s, error:%s, file: %s:%d',
					$order_id, $e->getMessage(), $e->getFile(), $e->getLine()
				)
			);
			return array(
				'result'       => 'failure',
				'redirect'     => wc_get_checkout_url(),
				'errorMessage' => __( 'Payment failed. Please try again.', 'wc-solana-pay' ),
			);
		}
	}


	/**
	 * Handles payment processing on the "Pay for Order" page.
	 *
	 * If the order is already paid, redirect the customer to the return URL.
	 * Otherwise, display the order details table so the customer can proceed with payment.
	 *
	 * @param int $order_id The ID of the order being processed.
	 */
	public function process_payment_on_pay_order_page( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		if ( $order->is_paid() ) {
			wp_safe_redirect( $this->get_return_url( $order ) );
			exit;
		} else {
			$args = array(
				'order_id' => $order_id,
				'url'      => $this->get_payment_modal_url( $order ),
			);
			echo wp_kses_post( get_partial_file_html( '/public/partials/public-order-details.php', $args ) );
		}
	}


	/**
	 * Register order details and update its metadata.
	 *
	 * @param  \WC_Order $order Order object.
	 * @return int Status code
	 */
	private function register_order_details( $order ) {
		$order_id = $order->get_id();
		$amount   = (float) $order->get_total();
		$currency = $order->get_currency();
		$testmode = $this->get_testmode();

		$order_details = array(
			'local_id'  => $order_id,
			'amount'    => $amount,
			'currency'  => $currency,
			'symbol'    => get_woocommerce_currency_symbol( $currency ),
			'testmode'  => $testmode,
			'recipient' => $this->get_merchant_wallet_address(),
			'suffix'    => Solana_Tokens::get_store_currency_key_suffix(),
			'label'     => esc_html( $this->get_brand_name() ),
			'message'   => esc_html__( 'Thank you for your order', 'wc-solana-pay' ),
			'home'      => $this->get_api_url(),
			'poll'      => 'stat',
			'link'      => 'txn',
			'rpc'       => 'rpc',
		);

		// Add acceptable Solana tokens options for payment and their rates
		$options = $this->get_accepted_solana_tokens_payment_options( $amount );
		$order_details = array_merge( $order_details, $options );

		// if order was previously registered, merge & update in remote otherwise register
		$prev_details = $this->get_order_payment_meta( $order );
		$res = array();

		if ( isset( $prev_details['id'] ) ) {
			// update order details in remote backend
			$order_details = array_merge( $prev_details, $order_details );
			$res = Solana_Pay::update_order_details( $order_details['id'], $order_details, $testmode );
		} else {
			// register order details with the remote backend
			$res = Solana_Pay::register_order_details( $order_details, $testmode );
		}

		if ( isset( $res['id'] ) ) {
			// store the details in order metadata for later use during payment processing
			$order_details['id'] = $res['id'];
			$order_details['timestamp'] = time();
			if ( count( $res['tokens'] ) ) {
				$order_details['tokens'] = $res['tokens'];
			}
			$this->set_order_payment_meta( $order, $order_details );
		} elseif ( self::BACKEND_STATUS_ALREADY_PAID !== $res['status'] ) {
			/* translators: %s: WordPress error message, e.g. 'Timeout error' */
			throw new \Exception( sprintf( esc_html__( 'Checkout order registration failed: %s', 'wc-solana-pay' ), esc_attr( $res['error'] ) ) );
		}

		return $res['status'];
	}


	/**
	 * Refresh order price details if price already outdated.
	 *
	 * @param  \WC_Order $order Order object.
	 */
	public function refresh_order_details( $order ) {
		$order_details = $this->get_order_payment_meta( $order );
		$should_refresh = ( time() - ( $order_details['timestamp'] ?? 0 ) ) >= self::PRICE_REFRESH_INTERVAL;

		if ( $should_refresh ) {
			try {
				$this->register_order_details( $order );
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- This is a background refresh call & can be ignored.
				// ignore & return silently
			}
		}
	}


	/**
	 * Validate payment confirmation for an order and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function confirm_payment( $order_id ) {
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
		if ( ! $order ) {
			return;
		}

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
		if ( (bool) $order && $this->id === $order->get_payment_method() ) {
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
		if ( is_array( $meta ) && ( true === $meta['confirmed'] ) ) {
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
		if ( is_array( $meta ) && ( true === $meta['confirmed'] ) ) {
			echo wp_kses_post( get_partial_file_html( '/public/partials/public-payment-details.php', $meta ) );
		}
	}


	/**
	 * Add style to plugin icon img element html
	 *
	 * @param string $icon Gateway icon.
	 * @param string $id   Gateway ID.
	 * @return string
	 */
	public function get_icon_with_style( $icon, $id ) {
		if ( (bool) $this->icon && $this->id === $id ) {
			$icon = '<img src="' . esc_url( $this->icon ) . '" alt="' . esc_attr( $this->get_title() ) . '" style="vertical-align:middle;max-height:2.5rem;"' . '" />';
		}

		return $icon;
	}


	/**
	 * Shorten the Solana transaction ID hash string displayed on the order details page
	 *
	 * @param string    $txn_id Transaction ID.
	 * @param \WC_Order $order  Order object.
	 * @return string
	 */
	public function shorten_transaction_id( $txn_id, $order ) {
		if ( $this->id === $order->get_payment_method() ) {
			$txn_id = esc_attr( Solana_Pay::shorten_hash_address( $txn_id ) );
		}

		return $txn_id;
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
	 * Get API endpoint URL
	 *
	 * @return string
	 */
	public function get_api_url() {
		return esc_url( get_rest_url( null, PLUGIN_ID . '/v1/api' ) );
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

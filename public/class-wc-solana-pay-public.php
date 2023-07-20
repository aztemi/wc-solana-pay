<?php
/**
 * The public-facing functionalities of the plugin.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


class WC_Solana_Pay_Public {

	/**
	 * Handle of enqueued main js.
	 *
	 * @var string
	 */
	protected $handle_js = '';


	public function __construct() {

		$this->register_hooks();

	}


	/**
	 * Register actions and filters for the payment gateway
	 */
	private function register_hooks() {

		// enqueue css and js
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// load scripts as modules
		add_filter( 'script_loader_tag', array( $this, 'load_enqueued_scripts_as_modules' ), 10, 2 );

		// add placeholder for the payment popup modal
		add_action( 'wp_footer', array( $this, 'add_modal_placeholder' ), -10 );

		// add custom 'Solana Pay' express checkout button in the place of 'Place Order' button
		add_filter( 'woocommerce_order_button_html', array( $this, 'add_custom_place_order_button' ) );
		add_filter( 'woocommerce_pay_order_button_html', array( $this, 'add_custom_place_order_button' ) );

	}


	/**
	 * Register stylesheets for the public-facing frontend.
	 */
	public function enqueue_styles() {

		// Enqueue DashIcons
		wp_enqueue_style( 'dashicons' );

	}


	/**
	 * Register JavaScripts for the public-facing frontend.
	 */
	public function enqueue_scripts() {

		// Get order-id if on Order Pay page
		$order_id = '';
		if ( is_checkout_pay_page() ) {
			$order_id = absint( get_query_var( 'order-pay' ) );
		}

		// Enqueue Solana Pay overlay modal script
		$modaljs = get_script_path( '/assets/script/wc_solana_pay*.js', PLUGIN_URL );
		if ( $modaljs ) {
			$this->handle_js = PLUGIN_ID . '_modaljs';

			$payload = array(
				'id'       => PLUGIN_ID,
				'baseurl'  => PLUGIN_URL,
				'pay_page' => is_checkout_pay_page(),
				'order_id' => $order_id,
			);

			wp_enqueue_script( $this->handle_js, $modaljs, ['jquery'], null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Filename already has version added by JS bundler.

			wp_localize_script( $this->handle_js, 'solana_pay_for_wc', $payload );
		}

	}


	/**
	 * Load enqueued scripts as modules.
	 *
	 */
	public function load_enqueued_scripts_as_modules( $tag, $handle ) {

		if ( $this->handle_js === $handle ) {
			$tag = str_replace( '></script>', ' type="module" defer></script>', $tag );
		}

		return $tag;

	}

	/**
	 * Add a placeholder element where the payment popup modal will be mounted.
	 * Svelte will inject our custom payment modal in it.
	 */
	public function add_modal_placeholder() {

		echo '<div id="wc_solana_pay_svelte_target"></div>';

	}


	/**
	 * Replace WC 'Place order' button with custom 'Solana Pay' button for express checkout
	 */
	public function add_custom_place_order_button( $button ) {

		$buttonjs = get_script_path('/assets/script/public_place_order_button*.js');

		if ( $buttonjs ) {
			$error_msg = esc_html__( 'Some inputs are not valid. Please fill all required fields.', 'wc-solana-pay' );

			$script = get_partial_file_html(
				$buttonjs,
				array(
					'id'        => PLUGIN_ID,
					'error_msg' => $error_msg,
					'pay_page'  => is_checkout_pay_page(),
				)
			);

			$button = get_partial_file_html(
				'/public/partials/public-place-order-button.php',
				array(
					'id'     => PLUGIN_ID,
					'button' => $button,
					'script' => $script,
				)
			);
		}

		return $button;

	}

}

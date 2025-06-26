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
		if ( is_checkout() || is_checkout_pay_page() || is_checkout_block() ) {
			$this->register_hooks();
		}
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
		// Enqueue Solana Pay overlay modal script
		$modaljs = get_script_path( '/assets/script/wc_solana_pay*.js', PLUGIN_URL );
		if ( $modaljs ) {
			$this->handle_js = PLUGIN_ID . '_modaljs';
			wp_enqueue_script( $this->handle_js, $modaljs, array( 'jquery' ), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Filename already has version added by the JS bundler.
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
		echo wp_kses_post( '<div id="wc_solana_pay_svelte_target"></div>' );
	}
}

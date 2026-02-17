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
		add_action( 'template_redirect', array( $this, 'register_hooks' ) );
		$this->register_hooks();
	}


	/**
	 * Register actions and filters for the payment gateway
	 */
	public function register_hooks() {
		// return if not on a view-order or checkout page
		if ( ! is_view_order_page() && ! is_checkout_page() ) {
			return;
		}

		// return if already enquequed
		if ( $this->handle_js && wp_script_is( $this->handle_js, 'enqueued' ) ) {
			return;
		}

		// enqueue css and js
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// only for checkout page
		if ( is_checkout_page() ) {
			// load scripts as modules
			add_filter( 'script_loader_tag', array( $this, 'load_enqueued_scripts_as_modules' ), 10, 2 );

			// add placeholder for the payment popup modal
			add_action( 'wp_footer', array( $this, 'add_modal_placeholder' ), -10 );
		}
	}


	/**
	 * Register stylesheets for the public-facing frontend.
	 */
	public function enqueue_styles() {
		// Enqueue DashIcons
		wp_enqueue_style( 'dashicons' );

		// enqueue css files
		$css = '/assets/script/style*.css';
		$css_url = get_script_path( $css, PLUGIN_URL );
		$css_path = get_script_path( $css, PLUGIN_DIR );
		$handle  = PLUGIN_ID . '_copycss';
		wp_enqueue_style( $handle, $css_url, array(), filemtime( $css_path ) );
	}


	/**
	 * Register JavaScripts for the public-facing frontend.
	 */
	public function enqueue_scripts() {
		// enqueue js files
		$js  = '/assets/script/copy_to_clipboard*.js';
		$js_url = get_script_path( $js, PLUGIN_URL );
		$js_path = get_script_path( $js, PLUGIN_DIR );
		$handle = PLUGIN_ID . '_copyjs';
		wp_enqueue_script( $handle, $js_url, array('jquery'), filemtime( $js_path ), true );

		// Enqueue Solana Pay overlay modal script
		$js  = '/assets/script/wc_solana_pay*.js';
		$js_url = get_script_path( $js, PLUGIN_URL );
		$js_path = get_script_path( $js, PLUGIN_DIR );
		$this->handle_js = PLUGIN_ID . '_modaljs';
		wp_enqueue_script( $this->handle_js, $js_url, array('jquery'), filemtime( $js_path ), true );
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

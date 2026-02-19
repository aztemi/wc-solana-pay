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
		$this->register_hooks();
	}


	/**
	 * Register all actions and filters needed to start the plugin
	 */
	private function register_hooks() {
		// Add 'Settings' link to the Installed Plugins page after plugin activation
		add_filter( 'plugin_action_links_' . PLUGIN_BASENAME, array( $this, 'add_action_links' ) );

		// register an endpoint for handling REST calls
		add_action( 'rest_api_init', array( $this, 'register_rest_endpoint' ) );

		// enqueue scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}


	/**
	 * Add 'Settings' action link to the Installed Plugins admin page
	 *
	 * @param  array $links List of action links
	 * @return array Extended action links list
	 */
	public function add_action_links( $links ) {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			$settings_link =
				sprintf(
					'<a href="%1$s">%2$s</a>',
					admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . PLUGIN_ID ),
					__( 'Settings', 'wc-solana-pay' )
				);
			array_unshift( $links, $settings_link );
		}

		return $links;
	}


	/**
	 * Enqueue custom scripts on the WooCommerce Admin Orders and Settings pages
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		if ( in_array( $hook_suffix, array( 'woocommerce_page_wc-settings', 'woocommerce_page_wc-orders', 'post.php' ) ) ) {
			wp_enqueue_style( 'dashicons' );
			wp_enqueue_media();

			// enqueue css files
			$css = '/assets/script/style*.css';
			$css_url = get_script_path( $css, PLUGIN_URL );
			$css_path = get_script_path( $css, PLUGIN_DIR );
			$handle  = PLUGIN_ID . '_admin_copycss';
			wp_enqueue_style( $handle, $css_url, array(), filemtime( $css_path ) );

			// enqueue js files
			$js  = '/assets/script/copy_to_clipboard*.js';
			$js_url = get_script_path( $js, PLUGIN_URL );
			$js_path = get_script_path( $js, PLUGIN_DIR );
			$handle = PLUGIN_ID . '_admin_copyjs';
			wp_enqueue_script( $handle, $js_url, array( 'jquery' ), filemtime( $js_path ), true );
		}
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

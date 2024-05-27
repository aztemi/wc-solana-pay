<?php
/**
 * Helper functions and utilities.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Check if WooCommerce plugin is activated or not.
 *
 * @return bool true if WooCommerce is activated, otherwise false.
 */
function is_woocommerce_activated() {
	return in_array( 'woocommerce/woocommerce.php', get_option( 'active_plugins', array() ) );
}


/**
 * Check if BC Math Arbitrary Precision Mathematics extension is installed or not.
 *
 * @return bool true if service is installed, otherwise false.
 */
function is_bcmath_installed() {
	return function_exists('bccomp');
}


/**
 * Display an error notice message on the admin screen.
 *
 * @param string $notice Error message to display.
 */
function show_error_notice( $notice ) {
	add_action(
		'admin_notices',
		function () use ( $notice ) {
			echo wp_kses_post( '<div class="notice notice-error"><p>' . $notice . '</p></div>'  );
		}
	);
}


/**
 * Add a specified log message to the WooCommerce Status Logs.
 *
 * @param string $message Message data to log.
 */
function logger( $message, $level = 'warning' ) {
	$logger = wc_get_logger();
	if ( $logger ) {
		$logger->log( $level, wc_print_r( $message, true ), array( 'source' => PLUGIN_ID ) );
	}
}


/**
 * Load a partial file and return its content HTML
 *
 * @param  string $relpath Relative path to the php file to load.
 * @param  array  $args    List of variables to import into symbol table of the file.
 * @return string          HTML string of loaded php file.
 */
function get_partial_file_html( $relpath, $args = array() ) {
	ob_start();

	extract( $args );
	include PLUGIN_DIR . $relpath;

	return ob_get_clean();
}


/**
 * Lookup the full path of a minified script file
 *
 * @param  string $relpath Relative path to the file.
 * @param  string $base    Base path to prepend to the return path
 * @return string          Path of the script file or empty string if script not found.
 */
function get_script_path( $relpath, $base = '' ) {
	$path = '';
	$scripts = glob( PLUGIN_DIR . $relpath );
	if ( count( $scripts ) ) {
		$path = str_replace( PLUGIN_DIR, $base, $scripts[0] );
	}

	return $path;
}


/**
 * Perform an HTTP request and returns its response as a formatted array
 *
 * @param  string $url      URL to retrieve.
 * @param  string $method   Request method. 'GET' or 'POST'.
 * @param  array  $postdata POST request body payload.
 * @return array            The formatted array containing status code, error if any and json body.
 */
function remote_request( $url, $method = 'GET', $postdata = array() ) {
	$rtn = array(
		'status' => 500,
		'error'  => '',
		'body'   => '',
	);

	$args = array(
		'method'  => $method,
		'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
		'timeout' => 10,
	);

	if ( 'POST' === $method ) {
		$args['body'] = $postdata;
		$args['data_format'] = 'body';
	}

	$response = wp_remote_request( $url, $args );

	if ( is_wp_error( $response ) ) {
		$rtn['error'] = $response->get_error_message();
	} else {
		$rtn['status'] = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$rtn['body'] = $body;

		if ( is_array( $body ) && isset( $body['error'] ) ) {
			$rtn['error'] = $body['error'];
		}
	}

	return $rtn;
}

<?php
/**
 * A class for handling data linked to active user session.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


class Session {

	public function __construct() {
		$this->init();
	}


	/**
	 * Initialize plugin session entry in WC session
	 */
	public function init() {
		if ( isset( WC()->session ) && ! WC()->session->{ PLUGIN_ID } ) {
			WC()->session->{ PLUGIN_ID } = array();
		}
	}


	/**
	 * Remove plugin session entry from WC session
	 */
	public function clear() {
		unset( WC()->session->{ PLUGIN_ID } );
	}


	/**
	 * Read plugin session data
	 *
	 * @return array User data retrieved from session or empty array if session is empty
	 */
	public function get_data() {
		if ( isset( WC()->session ) && isset( WC()->session->{ PLUGIN_ID } ) ) {
			return WC()->session->{ PLUGIN_ID };
		}

		return array();
	}


	/**
	 * Update plugin session data
	 *
	 * @param array $data User data to store
	 */
	public function set_data( $data ) {
		if ( isset( WC()->session ) ) {
			WC()->session->{ PLUGIN_ID } = $data;
		}
	}
}

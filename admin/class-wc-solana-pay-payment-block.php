<?php
/**
 * The extension class that handles Gutenberg Blocks integration.
 *
 * @since 2.4.0
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


final class WC_Solana_Pay_Payment_Block extends AbstractPaymentMethodType {

	/**
	 * Handle instance of the payment gateway class.
	 *
	 * @var WC_Solana_Pay_Payment_Gateway
	 */
	protected $hGateway;


	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = PLUGIN_ID;


	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->hGateway = $gateways[ $this->name ];
	}


	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->hGateway->is_available();
	}


	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$handles  = array();
		$blockjs  = get_script_path( '/assets/script/payment_block*.js', PLUGIN_URL );
		$blockphp = get_script_path( '/assets/script/payment_block*.php', PLUGIN_DIR );

		if ( $blockphp && $blockjs ) {
			$handlejs = PLUGIN_ID . '_blockjs';
			$dependency = require $blockphp ;

			wp_register_script( $handlejs, $blockjs, $dependency['dependencies'], $dependency['version'], true );
			$handles = array( $handlejs );
		}

		return $handles;
	}


	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'icon'        => $this->hGateway->icon,
			'title'       => $this->hGateway->title,
			'description' => $this->hGateway->block_desc,
			'supports'    => array_filter( $this->hGateway->supports, array( $this->hGateway, 'supports' ) ),
		);
	}
}

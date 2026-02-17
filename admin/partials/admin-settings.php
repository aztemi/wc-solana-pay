<?php
/**
 * Form fields for Admin Settings.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


return array(
	'enabled' => array(
		'title'       => __( 'Enable/Disable', 'wc-solana-pay' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable WC Solana Pay', 'wc-solana-pay' ),
		'default'     => 'no',
		'description' => __( 'This gateway must be enabled in order to use Solana Pay.', 'wc-solana-pay' ),
		'desc_tip'    => true,
	),
	'merchant_wallet' => array(
		'title'       => __( 'Merchant Wallet Address', 'wc-solana-pay' ),
		'type'        => 'text',
		'default'     => '',
		'description' => __( 'Merchant Solana wallet address to receive payments.<br /><b>Crypto transactions are not reversible, please make sure the entered address is correct.</b>', 'wc-solana-pay' ),
	),
	'network'       => array(
		'title'       => __('Solana Network', 'wc-solana-pay'),
		'type'        => 'select',
		'default'     => Solana_Pay::NETWORK_MAINNET_BETA,
		'description' => __('The Solana network cluster for processing transactions.<br /><b>"Devnet" is only for testing and has no monetary value. Select "Mainnet-Beta" to go live for real cryptocurrencies.</b>', 'wc-solana-pay'),
		'options'     => array(
			Solana_Pay::NETWORK_DEVNET => __( 'Devnet (Test Mode)', 'wc-solana-pay' ),
			Solana_Pay::NETWORK_MAINNET_BETA  => __( 'Mainnet-Beta (Production Mode)', 'wc-solana-pay' ),
		),
	),
	'tokens_table'  => array(
		'title'       => __( 'Accepted Payment Tokens', 'wc-solana-pay' ),
		'type'        => 'tokens_table',
		'desc_tip'    => __( 'Enable cryptocurrencies you want to accept for payments.', 'wc-solana-pay' ),
	),
	'brand_name'    => array(
		'title'       => __( 'Brand Name', 'wc-solana-pay' ),
		'type'        => 'text',
		'default'     => get_bloginfo( 'name' ) ?? '',
		'description' => __( 'Merchant or Store name displayed in payment instructions.', 'wc-solana-pay' ),
	),
	'title'         => array(
		'title'       => __( 'Plugin Name', 'wc-solana-pay' ),
		'type'        => 'text',
		'default'     => __( 'WC Solana Pay', 'wc-solana-pay' ),
		'description' => __( 'Payment method name displayed on the checkout page.', 'wc-solana-pay' ),
	),
	'plugin_icon'   => array(
		'title'       => __( 'Plugin Icon', 'wc-solana-pay' ),
		'type'        => 'plugin_icon',
		'desc_tip'    => __( 'Customize the plugin icon image. Recommended icon size is 48 x 32 pixels.', 'wc-solana-pay' ),
	),
	'description'   => array(
		'title'       => __( 'Description', 'wc-solana-pay' ),
		'type'        => 'textarea',
		'default'     => __( 'Complete your payment with Solana Pay.', 'wc-solana-pay' ),
		'description' => __( 'Payment method description displayed on the checkout page.', 'wc-solana-pay' ),
	),
	'modal_location' => array(
		'title'       => __( 'When to show payment popup', 'wc-solana-pay' ),
		'type'        => 'select',
		'default'     => WC_Solana_Pay_Payment_Gateway::MODAL_LOCATION_CHECKOUT,
		'description' => __( 'Choose when the payment popup appears to the customer.<br /><b>On Checkout page</b>: Opens immediately after clicking "Place order". <b>On Order received page</b>: Opens on the order confirmation page.', 'wc-solana-pay' ),
		'options' => array(
			WC_Solana_Pay_Payment_Gateway::MODAL_LOCATION_CHECKOUT      => __( 'On Checkout page', 'wc-solana-pay' ),
			WC_Solana_Pay_Payment_Gateway::MODAL_LOCATION_ORDER_RECEIPT => __( 'On Order received page', 'wc-solana-pay' ),
		),
	),
);

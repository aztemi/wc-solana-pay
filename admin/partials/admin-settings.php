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
		'description' => __( 'Enable this payment method to allow customers to pay using Solana Pay.', 'wc-solana-pay' ),
		'desc_tip'    => true,
	),
	'merchant_wallet' => array(
		'title'       => __( 'Merchant Wallet Address', 'wc-solana-pay' ),
		'type'        => 'text',
		'default'     => '',
		'description' => __(
			'Copy and paste your Solana wallet address to receive payments.<br />
			<b>Cryptocurrency transactions cannot be reversed. Please make sure the address is correct.</b>',
			'wc-solana-pay'
		),
	),
	'network'       => array(
		'title'       => __('Solana Network', 'wc-solana-pay'),
		'type'        => 'select',
		'default'     => Solana_Pay::NETWORK_MAINNET_BETA,
		'description' => __(
			'Select the Solana network used to process transactions.<br /><br />
			<b>Devnet</b> is for testing only and has no real monetary value.<br />
			<b>Mainnet-Beta</b> is the live network for real transactions.',
			'wc-solana-pay'
		),
		'options'     => array(
			Solana_Pay::NETWORK_DEVNET       => __( 'Devnet (Test Mode)', 'wc-solana-pay' ),
			Solana_Pay::NETWORK_MAINNET_BETA => __( 'Mainnet-Beta (Live)', 'wc-solana-pay' ),
		),
	),
	'tokens_table'  => array(
		'title'       => __( 'Accepted Payment Tokens', 'wc-solana-pay' ),
		'type'        => 'tokens_table',
		'desc_tip'    => __( 'Select which cryptocurrencies you want to accept for payments.', 'wc-solana-pay' ),
	),

	// -------------------------
	// Advanced Settings Section
	// -------------------------
	'advanced_section' => array(
		'title'       => __( 'Advanced Settings (Optional)', 'wc-solana-pay' ),
		'type'        => 'title',
		'description' => __( 'Configure advanced behaviour of the payment gateway.', 'wc-solana-pay' ),
	),

	'brand_name'    => array(
		'title'       => __( 'Brand Name', 'wc-solana-pay' ),
		'type'        => 'text',
		'default'     => get_bloginfo( 'name' ) ?? '',
		'description' => __( 'Merchant or Store name displayed to customers in payment instructions.', 'wc-solana-pay' ),
	),
	'title'         => array(
		'title'       => __( 'Payment Method Title', 'wc-solana-pay' ),
		'type'        => 'text',
		'default'     => __( 'WC Solana Pay', 'wc-solana-pay' ),
		'description' => __( 'The payment method name shown to customers on the checkout page.', 'wc-solana-pay' ),
	),
	'plugin_icon'   => array(
		'title'       => __( 'Payment Method Icon', 'wc-solana-pay' ),
		'type'        => 'plugin_icon',
		'desc_tip'    => __( 'Upload a custom icon for this payment method. Recommended size: 48 x 32 pixels.', 'wc-solana-pay' ),
	),
	'description'   => array(
		'title'       => __( 'Payment Description', 'wc-solana-pay' ),
		'type'        => 'textarea',
		'default'     => __( 'Complete your payment using Solana Pay.', 'wc-solana-pay' ),
		'description' => __( 'This description is shown to customers on the checkout page.', 'wc-solana-pay' ),
	),
	'modal_location' => array(
		'title'       => __( 'When to show payment popup', 'wc-solana-pay' ),
		'type'        => 'select',
		'default'     => WC_Solana_Pay_Payment_Gateway::MODAL_LOCATION_ORDER_RECEIPT,
		'description' => __(
			'Choose when the payment popup appears to the customer.<br/><br/>
			<b>On Checkout page</b>: Opens immediately after clicking "Place order".<br/>
			<b>On Order received page</b>: Opens on the order confirmation page (recommended for better compatibility).',
			'wc-solana-pay'
		),
		'options' => array(
			WC_Solana_Pay_Payment_Gateway::MODAL_LOCATION_CHECKOUT      => __( 'On Checkout page', 'wc-solana-pay' ),
			WC_Solana_Pay_Payment_Gateway::MODAL_LOCATION_ORDER_RECEIPT => __( 'On Order received page', 'wc-solana-pay' ),
		),
	),
);

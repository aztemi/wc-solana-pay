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
		'description' => __( 'The name of the payment gateway that customers will see on the checkout page.', 'wc-solana-pay' ),
	),
	'description'   => array(
		'title'       => __( 'Description', 'wc-solana-pay' ),
		'type'        => 'textarea',
		'default'     => __( 'Complete your payment with Solana Pay.', 'wc-solana-pay' ),
		'description' => __( 'Payment method description that customers will see on the checkout page.', 'wc-solana-pay' ),
	),
);

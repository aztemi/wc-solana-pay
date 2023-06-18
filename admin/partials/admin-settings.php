<?php
/**
 * Form fields for Admin Settings.
 *
 * @package AZTemi\Solana_Pay_for_WC
 */

namespace AZTemi\Solana_Pay_for_WC;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


return array(
	'enabled' => array(
		'title'       => __( 'Enable/Disable', 'solana-pay-for-woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable Solana Pay', 'solana-pay-for-woocommerce' ),
		'default'     => 'no',
		'description' => __( 'This gateway must be enabled in order to use Solana Pay.', 'solana-pay-for-woocommerce' ),
		'desc_tip'    => true,
	),
	'merchant_wallet' => array(
		'title'       => __( 'Merchant Wallet Address', 'solana-pay-for-woocommerce' ),
		'type'        => 'text',
		'default'     => '',
		'description' => __( 'Merchant Solana wallet address to receive payments.<br /><b>Crypto transactions are not reversible, please make sure entered address is correct.</b>', 'solana-pay-for-woocommerce' ),
	),
	'network'       => array(
		'title'       => __('Solana Network', 'solana-pay-for-woocommerce'),
		'type'        => 'select',
		'default'     => Solana_Pay::NETWORK_DEVNET,
		'description' => __('The Solana network cluster for processing transactions.<br /><b>"Devnet" is only for testing and has no monetary value. Select "Mainnet-Beta" to go live for real cryptocurrencies.</b>', 'solana-pay-for-woocommerce'),
		'options'     => array(
											Solana_Pay::NETWORK_DEVNET => __( 'Devnet (Test Mode)', 'solana-pay-for-woocommerce' ),
											Solana_Pay::NETWORK_MAINNET_BETA  => __( 'Mainnet-Beta (Production Mode)', 'solana-pay-for-woocommerce' ),
										 ),
	),
	'tokens_table'  => array(
		'title'       => __( 'Accepted Payment Tokens', 'solana-pay-for-woocommerce' ),
		'type'        => 'tokens_table',
		'desc_tip'    => __( 'Enable cryptocurrencies you want to accept for payments.', 'solana-pay-for-woocommerce' ),
	),
	'brand_name'    => array(
		'title'       => __( 'Brand Name', 'solana-pay-for-woocommerce' ),
		'type'        => 'text',
		'default'     => get_bloginfo( 'name' ) ?? '',
		'description' => __( 'Merchant or Store name displayed in payment instructions.', 'solana-pay-for-woocommerce' ),
	),
	'description'   => array(
		'title'       => __( 'Description', 'solana-pay-for-woocommerce' ),
		'type'        => 'textarea',
		'default'     => __( 'Complete your payment with Solana Pay.', 'solana-pay-for-woocommerce' ),
		'description' => __( 'Payment method description that customers will see on the checkout page.', 'solana-pay-for-woocommerce' ),
	),
);

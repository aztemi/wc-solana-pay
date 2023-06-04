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
	'testmode'      => array(
		'title'       => __( 'Test Mode', 'solana-pay-for-woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable Test Mode', 'solana-pay-for-woocommerce' ),
		'default'     => 'yes',
		'description' => __( 'Enable Test Mode to use Solana Devnet. Uncheck to use Solana Mainnet-Beta for Production.', 'solana-pay-for-woocommerce' ),
		'desc_tip'    => true,
	),
	'merchant_wallet' => array(
		'title'       => __( 'Merchant Wallet Address', 'solana-pay-for-woocommerce' ),
		'type'        => 'text',
		'default'     => '',
		'description' => __( 'Merchant Solana wallet address where all payments will be sent.', 'solana-pay-for-woocommerce' ),
	),
	'tokens_table'  => array(
		'title'       => __( 'Solana Tokens', 'solana-pay-for-woocommerce' ),
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

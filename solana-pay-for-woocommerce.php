<?php
/**
 * Plugin Name:       Solana Pay for WooCommerce
 * Plugin URI:        https://github.com/t4top/solana-pay-for-woocommerce
 * Description:       Add Solana Pay to your WooCommerce store. Solana Pay is a fast and open payments framework built on Solana blockchain.
 * Version:           0.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            t4top
 * Author URI:        https://github.com/t4top/
 * License:           GPLv3 or later
 * License URI:       https://github.com/t4top/solana-pay-for-woocommerce/blob/main/LICENSE
 * Text Domain:       solana-pay-for-wc
 * Domain Path:       /languages
 *
 * @package T4top\Solana_Pay_for_WC
 */

namespace T4top\Solana_Pay_for_WC;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) { die; }

// define named constants
define( 'PLUGIN_DIR', rtrim( plugin_dir_path( __FILE__ ), '/\\' ) );
define( 'PLUGIN_URL', rtrim( plugin_dir_url( __FILE__ ), '/\\' ) );
define( 'PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load plugin textdomain.
load_plugin_textdomain( 'solana-pay-for-wc', false, PLUGIN_DIR . '/languages/' );

// load plugin helper functions
require_once PLUGIN_DIR . '/includes/functions.php';

// return if WooCommerce is not active
if ( ! is_woocommerce_activated() ) {
  show_error_notice( __( '<b>Solana Pay for WooCommerce</b> requires <b>WooCommerce</b> to be installed and active.', 'solana-pay-for-wc' ) );
  return;
}

add_action( 'init', __NAMESPACE__ . '\start_session', 1 );
add_filter( 'woocommerce_payment_gateways', __NAMESPACE__ . '\register_gateway_class' );
add_action( 'plugins_loaded', __NAMESPACE__ . '\init_gateway_class' );

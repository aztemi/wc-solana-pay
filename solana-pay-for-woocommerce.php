<?php
/**
 * Plugin Name:       Solana Pay for WooCommerce
 * Plugin URI:        https://github.com/aztemi/solana-pay-for-woocommerce
 * Description:       Adds Solana Pay to your WooCommerce store for accepting payments in SOL, USDC, USDT and more.
 * Version:           0.2.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            AZTemi
 * Author URI:        https://www.aztemi.com
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       solana-pay-for-wc
 * Domain Path:       /languages
 *
 * @package AZTemi\Solana_Pay_for_WC
 */

namespace AZTemi\Solana_Pay_for_WC;

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

add_filter( 'woocommerce_payment_gateways', __NAMESPACE__ . '\register_gateway_class' );
add_action( 'plugins_loaded', __NAMESPACE__ . '\activate_gateway' );

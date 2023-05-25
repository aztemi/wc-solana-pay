<?php
/**
 * Plugin Name: Solana Pay for WooCommerce
 * Plugin URI:  https://github.com/aztemi/solana-pay-for-woocommerce
 * Description: Adds Solana Pay to your WooCommerce store and lets you accept payments in SOL, USDC, USDT and more.
 * Version:     1.0.0
 * Author:      AZTemi
 * Author URI:  https://www.aztemi.com
 * License:     GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: solana-pay-for-woocommerce
 * Domain Path: /languages
 *
 * Requires PHP:         7.2
 * Requires at least:    5.2
 * Tested up to:         6.2
 * WC requires at least: 3.0
 * WC tested up to:      7.7
 *
 * @package AZTemi\Solana_Pay_for_WC
 */

namespace AZTemi\Solana_Pay_for_WC;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

// define named constants
define( __NAMESPACE__ . '\PLUGIN_ID', 'spfwc' );
define( __NAMESPACE__ . '\PLUGIN_DIR', untrailingslashit( __DIR__ ) );
define( __NAMESPACE__ . '\PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( __NAMESPACE__ . '\PLUGIN_BASENAME', plugin_basename( __FILE__ ) );


// load plugin core class and start it
function load_plugin_class() {

	require_once PLUGIN_DIR . '/includes/class-solana-pay-for-woocommerce.php';

	$plugin = new Solana_Pay_For_WooCommerce();
	$plugin->run();

}
load_plugin_class();

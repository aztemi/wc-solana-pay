<?php
/**
 * Plugin Name: Pay with Solana Pay for WooCommerce
 * Plugin URI:  https://github.com/aztemi/wc-solana-pay
 * Description: A payment gateway for accepting crypto payments in SOL, USDC, USDT and more in your WooCommerce store.
 * Version:     2.0.0
 * Author:      AZTemi
 * Author URI:  https://www.aztemi.com
 * License:     GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wc-solana-pay
 * Domain Path: /languages
 *
 * Requires PHP:         7.2
 * Requires at least:    5.2
 * Tested up to:         6.2.2
 * WC requires at least: 3.0
 * WC tested up to:      7.8.0
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

// define named constants
define( __NAMESPACE__ . '\PLUGIN_ID', 'pwspfwc' );
define( __NAMESPACE__ . '\PLUGIN_DIR', untrailingslashit( __DIR__ ) );
define( __NAMESPACE__ . '\PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( __NAMESPACE__ . '\PLUGIN_BASENAME', plugin_basename( __FILE__ ) );


// load plugin core class and start it
function load_plugin_class() {

	require_once PLUGIN_DIR . '/includes/class-wc-solana-pay.php';

	$plugin = new WC_Solana_Pay();
	$plugin->run();

}
load_plugin_class();

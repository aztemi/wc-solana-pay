<?php
/**
 * Plugin Name: WC Solana Pay
 * Plugin URI:  https://apps.aztemi.com/wc-solana-pay
 * Description: Accept crypto payments in SOL, USDT, USDC, EURC and more in your WooCommerce store.
 * Version:     2.9.0
 * Author:      AZTemi
 * Author URI:  https://apps.aztemi.com/wc-solana-pay
 * License:     GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wc-solana-pay
 * Domain Path: /languages
 *
 * Requires PHP:         7.2
 * Requires at least:    5.2
 * Tested up to:         6.6.2
 * WC requires at least: 3.0
 * WC tested up to:      9.3.1
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

// define named constants
define( __NAMESPACE__ . '\PLUGIN_ID', 'wc-solana-pay' );
define( __NAMESPACE__ . '\PLUGIN_DIR', untrailingslashit( __DIR__ ) );
define( __NAMESPACE__ . '\PLUGIN_FILE', untrailingslashit( __FILE__ ) );
define( __NAMESPACE__ . '\PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( __NAMESPACE__ . '\PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// load plugin core class and start it
function load_plugin_class() {
	require_once PLUGIN_DIR . '/includes/class-wc-solana-pay.php';

	$plugin = new WC_Solana_Pay();
	$plugin->run();
}

load_plugin_class();

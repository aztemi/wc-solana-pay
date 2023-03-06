<?php
/**
 * Helper functions and utilities.
 *
 * @package T4top\Solana_Pay_for_WC
 */

namespace T4top\Solana_Pay_for_WC;

/**
 * Check if WooCommerce plugin is activated or not.
 *
 * @return bool true if WooCommerce is activated, otherwise false.
 */
function is_woocommerce_activated() {
  return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) );
}

/**
 * Print error notices on the admin screen.
 *
 * @param notice string Error message to display.
 */
function show_error_notice( $notice ) {
  add_action(
    'admin_notices',
    function() use( $notice ) {
      echo '<div class="notice notice-error"><p>' . $notice . '</p></div>';
    }
  );
}

/**
 * Declare gateway class
 *
 * @param  gateways array List of gateways currently registered
 * @return          array Extended gateways list
 */
function register_gateway_class( $gateways = [] ) {
  $gateways[] = __NAMESPACE__ . '\Solana_Pay_for_WooCommerce';
  return $gateways;
}

/**
 * Initialize gateway class
 */
function init_gateway_class() {
  require PLUGIN_DIR . '/includes/class-solana-pay-for-woocommerce.php';
}

<?php
/**
 * Helper functions and utilities.
 *
 * @package T4top\Solana_Pay_for_WC
 */

namespace T4top\Solana_Pay_for_WC;

use StephenHill\Base58;

/**
 * start session
 */
function start_session() {
  if ( !session_id() ) {
    session_start();
  }
}

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

/**
 * Enqueue a css style or js script to the front end html.
 *
 * @param  relpath string Relative path to file to enqueue.
 * @param  deps    array  An array of registered script handles this file depends on.
 * @return         string A unique handle name of the file
 */
function enqueue_file( $relpath, $deps = [] ) {
  $handle = str_replace( array( '/', '.' ), '_', $relpath );
  $url = PLUGIN_URL . $relpath;
  
  if ( 0 === substr_compare( $relpath, '.css', -4, 4, true ) ) {
    $path = PLUGIN_DIR . $relpath;
    wp_enqueue_style( $handle, $url, $deps, filemtime( $path ), 'all' );
  } else {
    wp_enqueue_script( $handle, $url, $deps, null, true );
  }

  return $handle;
}

/**
 * Load enqueued scripts as modules.
 *
 * @param  enqueued_scripts array  List of handles of enqueued scripts to load as modules.
 * @return                  string The <script> tag for the enqueued script.
 */
function load_enqueued_scripts_as_modules( $enqueued_scripts = [] ) {
  add_filter(
    'script_loader_tag',
    function ( $tag, $handle ) use( $enqueued_scripts )  {
      if ( in_array( $handle, $enqueued_scripts ) ) {
        $tag = str_replace( '></script>', ' type="module" defer></script>', $tag );
      }
      return $tag;
    },
    10,
    2
  );
}

/**
 * Todo
 */
function get_template_html( $relpath, $args = [] ) {
  ob_start();

  extract( $args );
  include PLUGIN_DIR . $relpath;

  return ob_get_clean();
}

<?php
/**
 * The payment gateway class.
 *
 * @package T4top\Solana_Pay_for_WC
 */

namespace T4top\Solana_Pay_for_WC;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) { die; }

// return if WooCommerce payment gateway class is missing
if ( ! class_exists( '\WC_Payment_Gateway' ) ) {
  return;
}

// return if our class is already registered
if ( class_exists( __NAMESPACE__ . '\Solana_Pay_for_WooCommerce' ) ) {
  return;
}

class Solana_Pay_for_WooCommerce extends \WC_Payment_Gateway {

  protected const DEVNET_ENDPOINT = 'https://api.devnet.solana.com';

  public function __construct() {
    // Init session for this plugin
    $this->init_session();

    // Setup general properties
    $this->setup_properties();

    // Load settings
    $this->init_form_fields();
    $this->init_settings();

    // Get settings into local variables
    $this->get_settings();

    // Actions & filters
    $this->add_actions_and_filters();
  }

  /**
   * Add initialized plugin session entry to WC session
   */
  private function init_session() {
    if ( isset( WC()->session ) && ! WC()->session->{ $this->id } ) {
      WC()->session->{ $this->id } = array();
    }
  }

  /**
   * Remove plugin session entry from WC session
   */
  private function clear_session() {
    unset( WC()->session->{ $this->id } );
  }

  /**
   * Read plugin session data
   */
  private function get_session_data() {
    if ( isset( WC()->session ) && isset( WC()->session->{ $this->id } ) ) {
      return WC()->session->{ $this->id };
    }

    return array();
  }

  /**
   * Update plugin session data
   */
  private function update_session_data( $data ) {
    if ( isset( WC()->session ) ) {
      WC()->session->{ $this->id } = $data;
    }
  }

  private function setup_properties() {
    $this->id                 = 'spfwc';
    $this->icon               = PLUGIN_URL . '/assets/img/solana_pay_black_gradient.svg';
    $this->has_fields         = false;
    $this->title              = __( 'Solana Pay', 'solana-pay-for-wc' );
    $this->method_title       = $this->title;
    $this->method_description = __( 'Take payments in SOL, USDC, USDT and more with Solana Pay.', 'solana-pay-for-wc' );
    $this->supports           = array( 'products' );
  }

  /**
   * Initialise Gateway Settings Form Fields
   */
  public function init_form_fields() {
    $this->form_fields = apply_filters( 'solana_pay_wc_form_fields',
      array(
        'enabled' => array(
          'title'       => __( 'Enable/Disable', 'solana-pay-for-wc' ),
          'type'        => 'checkbox',
          'label'       => __( 'Enable Solana Pay', 'solana-pay-for-wc' ),
          'default'     => 'no',
          'description' => __( 'This gateway must be enabled in order to use Solana Pay.', 'solana-pay-for-wc' ),
          'desc_tip'    => true,
        ),
        'testmode'      => array(
          'title'       => __( 'Test Mode', 'solana-pay-for-wc' ),
          'type'        => 'checkbox',
          'label'       => __( 'Enable Test Mode', 'solana-pay-for-wc' ),
          'default'     => 'yes',
          'description' => __( 'Enable Test Mode to use Solana Devnet. Uncheck to use Solana Mainnet-Beta for Production.', 'solana-pay-for-wc' ),
          'desc_tip'    => true,
        ),
        'merchant_wallet' => array(
          'title'       => __( 'Solana Wallet Address', 'solana-pay-for-wc' ),
          'type'        => 'text',
          'default'     => '',
          'description' => __( 'Merchant Solana wallet address where all payments will be sent.', 'solana-pay-for-wc' ),
        ),
        'cryptocurrency' => array(
          'title'       => __( 'Store Currency', 'solana-pay-for-wc' ),
          'type'        => 'select',
          'description' => __( 'Select default cryptocurrency for your products. <b>This will overwrite the Woocommerce default currency setting</b>.', 'solana-pay-for-wc' ),
          'options'     => $this->get_solana_tokens(),
        ),
        'live_rpc'      => array(
          'title'       => __( 'Mainnet-Beta RPC Endpoint', 'solana-pay-for-wc' ),
          'type'        => 'url',
          'default'     => '',
          'description' => __( 'RPC endpoint for connection to the Solana Mainnet-Beta.', 'solana-pay-for-wc' ),
        ),
        array(
          'title'       => esc_html__( 'Optional Settings', 'solana-pay-for-wc' ),
          'type'        => 'title',
          'description' => __( 'Options below are not mandatory.', 'solana-pay-for-wc' ),
        ),
        'test_rpc'      => array(
          'title'       => __( 'Devnet RPC Endpoint', 'solana-pay-for-wc' ),
          'type'        => 'url',
          'default'     => self::DEVNET_ENDPOINT,
          'description' => __( 'RPC endpoint for connection to the Solana Devnet.', 'solana-pay-for-wc' ),
        ),
        'brand_name'    => array(
          'title'       => __( 'Brand Name', 'solana-pay-for-wc' ),
          'type'        => 'text',
          'default'     => get_bloginfo( 'name' ) ?? '',
          'description' => __( 'Merchant name displayed in payment instructions.', 'solana-pay-for-wc' ),
        ),
        'description'   => array(
          'title'       => __( 'Description', 'solana-pay-for-wc' ),
          'type'        => 'textarea',
          'default'     => __( 'Complete your payment with Solana Pay.', 'solana-pay-for-wc' ),
          'description' => __( 'Payment method description that the customer will see in the checkout.', 'solana-pay-for-wc' ),
        ),
        'instructions'  => array(
          'title'       => __( 'Instructions', 'solana-pay-for-wc' ),
          'type'        => 'textarea',
          'default'     => __( 'Thank you for using Solana Pay', 'solana-pay-for-wc' ),
          'description' => __( 'Delivery or other useful instructions that will be added to the Thank You page and order emails.', 'solana-pay-for-wc' ),
        ),
      )
    );
  }

  private function get_settings() {
    $this->enabled         = $this->get_option( 'enabled' );
    $this->testmode        = 'yes' === $this->get_option( 'testmode', 'yes' );
    $this->merchant_wallet = $this->get_option( 'merchant_wallet' );
    $this->cryptocurrency  = $this->get_option( 'cryptocurrency' );
    $this->live_rpc        = $this->get_option( 'live_rpc' );
    $this->test_rpc        = $this->get_option( 'test_rpc' );
    $this->brand_name      = $this->get_option( 'brand_name' );
    $this->description     = $this->get_option( 'description' );
    $this->instructions    = $this->get_option( 'instructions' );

    if ( $this->testmode ) {
      $testmode_msg = ' (' . esc_html__( 'Test Mode enabled. Devnet in use', 'solana-pay-for-wc' ) . ')';
      $this->method_description .= $testmode_msg;
      $this->description .= $testmode_msg;
    }
  }

  /**
   * Validate wallet address settings field. Clear the field in case of error.
   */
  public function validate_merchant_wallet_field( $key, $value ) {
    if ( ! preg_match( '/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $value ) ) {
      \WC_Admin_Settings::add_error( esc_html__( 'Invalid Solana Wallet Address', 'solana-pay-for-wc' ) );
      $value = ''; // WC saves any return value despite error; empty it to prevent a wrong value from being saved.
    }

    return $value;
  }

  /**
   * Validate Live RPC Endpoint settings field. Clear the field in case of error.
   */
  public function validate_live_rpc_field( $key, $value ) {
    $post_data = $this->get_post_data();
    $testmode = isset( $post_data[ "woocommerce_{$this->id}_testmode" ] );

    if ( ! $testmode && ! wp_http_validate_url( $value ) ) {
      \WC_Admin_Settings::add_error( esc_html__( 'Invalid Mainnet-Beta RPC Endpoint', 'solana-pay-for-wc' ) );
      $value = ''; // WC saves any return value despite error; empty it to prevent a wrong value from being saved.
    }

    return $value;
  }

  /**
   * Set Test RPC Endpoint to default if not set correctly.
   */
  public function validate_test_rpc_field( $key, $value ) {
    if ( empty( trim( $value ) ) || ! wp_http_validate_url( $value ) ) {
      $value = self::DEVNET_ENDPOINT;
    }

    return $value;
  }

  private function add_actions_and_filters() {
    add_action( "woocommerce_update_options_payment_gateways_$this->id", array( $this, 'process_admin_options' ) );
    add_action( "woocommerce_thank_you_$this->id", array( $this, 'thank_you_page' ) );
    add_action( "woocommerce_api_$this->id" , array( $this, 'handle_webhook_request' ) );

    // Enqueue main script and add its target bind element
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_main_script' ) );
    add_action( 'wp_footer', array( $this, 'output_script_target_element' ), -10 );

    add_filter( 'woocommerce_order_button_html', array( $this, 'add_custom_order_button_html' ) );

    add_filter( 'plugin_action_links_' . PLUGIN_BASENAME,  array( $this, 'add_action_links' ) );
  }

  private function get_solana_tokens() {
    $tokens = get_supported_solana_tokens();
    $arr = array();
    foreach( $tokens as $k => $v ) {
      $arr[ $k ] = sprintf( '%s (%s)', $v['name'], $v['symbol'] );
    }

    return $arr;
  }

  /**
   * Update WC currency after Admin Panel options are saved.
   */
  public function process_admin_options() {
    // save regular settings
    $saved = parent::process_admin_options();

    $post_data = $this->get_post_data();
    if ( isset( $post_data[ "woocommerce_{$this->id}_cryptocurrency" ] ) ) {
      $this->cryptocurrency  = $this->get_option( 'cryptocurrency' );
      update_option( 'woocommerce_currency', $this->cryptocurrency );
    }

    return $saved;
  }

  /**
   * Add custom action links to Installed Plugins admin page
   */
  public function add_action_links( $links ) {
    array_unshift(
      $links,
      sprintf(
        '<a href="%1$s">%2$s</a>',
        admin_url( "admin.php?page=wc-settings&tab=checkout&section=$this->id" ),
        __( 'Settings', 'solana-pay-for-wc' )
      )
    );

    return $links;
  }

  /**
   * Show the instructions text on the Thank You page.
   */
  public function thank_you_page() {
    if ( $this->instructions ) {
      echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
    }
  }

  /**
   * Add custom 'Place order' button with Solana Pay icon
   */
  public function add_custom_order_button_html( $button ) {
    return get_template_html( '/includes/templates/order_button_html.php', array( 'button' => $button ) );
  }

  /**
   * Enqueue main js script
   * main js is the entry script that imports other css and js files when needed.
   */
  public function enqueue_main_script() {
    // Script is needed only on cart, checkout and pay_for_order pages. Return otherwise
    if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
      return;
    }

    // Return if our payment gateway is disabled
    if ( 'no' === $this->enabled ) {
      return;
    }

    $scripts = glob( PLUGIN_DIR . '/frontend/build/main*.js' );
    if ( count( $scripts ) ) {
      $handle = $this->id . '_mainjs';
      $mainjs = str_replace( PLUGIN_DIR, PLUGIN_URL, $scripts[0] );

      wp_enqueue_script( $handle, $mainjs, ['jquery'], null, true );
      load_enqueued_scripts_as_modules( [ $handle ] );
      wp_localize_script(
        $handle,
        'solana_pay_for_wc',
        array(
          'id'        => $this->id,
          'baseurl'   => PLUGIN_URL,
          'btn_class' => $this->get_button_classname(),
        )
      );
    }
  }

  /**
   * Add a placeholder element where Svelte in main js will be bound.
   * Svelte will inject our custom Solana modal in it.
   */
  public function output_script_target_element() {
    echo '<div id="solana_pay_for_wc_svelte_target"></div>';
  }

  /**
   * Get WordPress default class name for button element
   */
  public function get_button_classname() {
    return esc_attr( function_exists( 'wp_theme_get_element_class_name' ) ? wp_theme_get_element_class_name( 'button' ) : '' );
  }

  /**
   * Process the payment and return the result.
   *
   * @param int $order_id Order ID.
   * @return array
   */
  public function process_payment( $order_id ) {
    $order = wc_get_order( $order_id );
    $amount = $order->get_total();

    if ( $amount > 0 ) {
      // confirm payment transaction on chain
      $payment_confirmed = $this->confirm_solana_payment( $order_id );
      if ( ! $payment_confirmed ) {
        return;
      }
    }

    $order->payment_complete();
    $this->clear_session();

    // Remove cart.
    WC()->cart->empty_cart();

    // Return thankyou redirect.
    return array(
      'result'   => 'success',
      'redirect' => $this->get_return_url( $order ),
    );
  }

  public function confirm_solana_payment( $order_id ) {
    $url = self::DEVNET_ENDPOINT;
    $data = $this->get_session_data();
    $reference = $data['reference'];
    $nonce = $data['nonce'];

    $body = wp_json_encode(
      array(
        'jsonrpc' => '2.0',
        'id'      => 1,
        'method'  => 'getSignaturesForAddress',
        'params'  => array( $reference, array( 'commitment' => 'confirmed' ) )
      )
    );

    $response = wp_remote_post( $url, array(
      'method'      => 'POST',
      'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
      'timeout'     => 45,
      'body'        => $body,
      'data_format' => 'body',
      )
    );

    if ( is_wp_error( $response ) ) {
      $error_message = $response->get_error_message();
      wc_add_notice( __( 'Payment error:', 'solana-pay-for-wc' ) . '<p>' . esc_html( $error_message ) . '</p>', 'error' );
    } else {
      $response_code = wp_remote_retrieve_response_code( $response );

      if ( 200 === $response_code ) {
        $response_body = wp_remote_retrieve_body( $response );
        $response = json_decode( $response_body, true );
        $result = $response['result'];

        if ( 0 < count( $result ) ) {
          $memo = $result[0]['memo'];

          if ( str_contains( $memo, $nonce ) ) {
            $signature = $result[0]['signature'];

            // update order info
            wc_add_order_item_meta( $order_id, 'solana_pay_reference', $reference );
            wc_add_order_item_meta( $order_id, 'solana_pay_signature', $signature );
            wc_add_order_item_meta( $order_id, 'solana_pay_nonce', $nonce );

            return true;
          }
        }
      }
    }

    return false;
  }

  public function handle_webhook_request() {
    $ref = isset( $_GET[ 'ref' ] ) ? $_GET[ 'ref' ] : null;
    if ( is_null( $ref ) ) {
      return;
    }

    $data = array(
      'reference' => $ref,
      'label'     => $this->brand_name,
      'recipient' => $this->merchant_wallet,
      'currency'  => $this->cryptocurrency,
      'amount'    => WC()->cart->get_cart_contents_total(),
      'nonce'     => wp_create_nonce( substr( str_shuffle( MD5( microtime() ) ), 0, 12 ) ),
    );

    $this->update_session_data( $data );

    header( 'HTTP/1.1 200 OK' );
    wp_send_json( $data );
    die();
  }

}

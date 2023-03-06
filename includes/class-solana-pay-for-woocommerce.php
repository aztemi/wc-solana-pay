<?php
/**
 * The payment gateway class.
 *
 * @package T4top\Solana_Pay_for_WC
 */

namespace T4top\Solana_Pay_for_WC;

// return if WooCommerce payment gateway class is missing
if ( ! class_exists( '\WC_Payment_Gateway' ) ) {
  return;
}

// return if our class is already registered
if ( class_exists( __NAMESPACE__ . '\Solana_Pay_for_WooCommerce' ) ) {
  return;
}

class Solana_Pay_for_WooCommerce extends \WC_Payment_Gateway {

  /**
   * Array of enqueued scripts
   */
  protected $enqueued_scripts = [];


  public function __construct() {
    $this->id                 = strtolower( str_replace( __NAMESPACE__ . '\\', '', __CLASS__ ) );
    $this->icon               = PLUGIN_URL . '/assets/img/solana_pay_black_gradient.svg';
    $this->has_fields         = false;
    $this->title              = __( 'Solana Pay', 'solana-pay-for-wc' );
    $this->method_title       = $this->title;
    $this->method_description = __( 'Add Solana Pay to your WooCommerce store.', 'solana-pay-for-wc' );

    $this->enabled            = $this->get_option('enabled');

    // add settings form fields and initialize them
    $this->init_form_fields();
    $this->init_settings();
   
    add_action( "woocommerce_update_options_payment_gateways_$this->id", array( $this, 'process_admin_options' ) );    
    add_filter( 'plugin_action_links_' . PLUGIN_BASENAME,  array( $this, 'add_action_links' ) );
  }

  /**
   * Initialise Gateway Settings Form Fields
   */
  public function init_form_fields() {
    $this->form_fields = apply_filters('solanapay_wc_form_fields',
      array(
        'enabled' => array(
          'title'       => __('Enable/Disable', 'solana-pay-for-wc'),
          'type'        => 'checkbox',
          'label'       => __('Enable Solana Pay for your store.', 'solana-pay-for-wc'),
          'default'     => 'no',
          'desc_tip'    => true,
          'description' => __( 'In order to use Solana Pay processing, this Gateway must be enabled.', 'solana-pay-for-wc' ),
        ),
      )
    );
  }

  /**
   * Add custom action links to Installed Plugins admin page
   */
  function add_action_links( $links ) {
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
}

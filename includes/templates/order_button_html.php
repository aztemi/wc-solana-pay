<?php
/**
 * HTML partial for "Place Order" button.
 * Show custom button with Solana Pay icon when our payment gateway is selected.
 *
 * @package T4top\Solana_Pay_for_WC
 */

namespace T4top\Solana_Pay_for_WC;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) { die; }

$button_text = __( 'Pay with', 'solana-pay-for-wc' );
$img_src = PLUGIN_URL . '/assets/img/solana_pay_white_gradient.svg';
$img_alt = __( 'Solana Pay', 'solana-pay-for-wc' );
$wp_button_class = esc_attr(function_exists('wp_theme_get_element_class_name') ? wp_theme_get_element_class_name('button') : '');
?>

<span x-show="$store.initialized" x-data="{}">

  <template x-if="!$store.solana_pay_selected">
    <?php echo $button ?>
  </template>

  <template x-if="$store.solana_pay_selected">
    <button
      class="button alt <?php echo $wp_button_class ?>"
      id="solana_pay_for_wc_checkout_place_order"
      value="<?php echo esc_attr( $button_text ) ?>"
      data-value="<?php echo esc_attr( $button_text ) ?>"
    >
      <span><?php echo esc_html( $button_text ) ?></span>
      <img src="<?php echo esc_url( $img_src ) ?>" alt="<?php echo esc_attr( $img_alt ) ?>" />
    </button>
  </template>

</span>

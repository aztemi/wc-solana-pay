<?php
/**
 * HTML partial for our custom "Place Order" button on Checkout page.
 *
 * It shows
 * - "Pay with Solana Pay" express checkout button when our Solana Pay payment method is selected and
 * - default "Place Order" button when other payment methods are selected.
 *
 * @package T4top\Solana_Pay_for_WC
 */

namespace T4top\Solana_Pay_for_WC;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) { die; }

$button_text = __( 'Pay with', 'solana-pay-for-wc' );
$img_src = esc_url( PLUGIN_URL . '/assets/img/solana_pay_white_gradient.svg' );
$img_alt = esc_attr( __( 'Solana Pay', 'solana-pay-for-wc' ) );
?>

<span id="place_order_btn_wrapper"></span>
<template id="template_their_btn">
  <?php echo $button ?>
</template>
<template id="template_our_btn">
  <button type="submit" id="place_order" class="solana_pay_for_wc_place_order button alt <?php echo $btn_class ?>" style="display:flex;align-items:center;justify-content:center">
    <span><?php echo esc_html( $button_text ) ?></span>
    <img src="<?php echo $img_src ?>" alt="<?php echo $img_alt ?>" style="margin-left:0.5em" />
  </button>
</template>

<script>
  <?php echo $script ?>
</script>

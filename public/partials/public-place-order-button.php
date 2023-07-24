<?php
/**
 * HTML partial for our custom "Place Order" button on Checkout page.
 *
 * It shows
 * - "Pay with Solana Pay" express checkout button when our Solana Pay payment method is selected and
 * - default "Place Order" button when other payment methods are selected.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


// Get WordPress default class name for button element
function get_button_classname() {
	return function_exists( 'wp_theme_get_element_class_name' ) ? wp_theme_get_element_class_name( 'button' ) : '';
}

$img_src = PLUGIN_URL . '/assets/img/solana_pay_white_gradient.svg';
$img_alt = __( 'Solana Pay', 'wc-solana-pay' );
?>

<span id="place_order_btn_wrapper"></span>
<template id="template_their_btn">
	<?php echo wp_kses_post( $button ); ?>
</template>
<template id="template_our_btn">
	<button type="submit" id="place_order" class="wc_solana_pay_place_order button alt <?php echo esc_attr( get_button_classname() ); ?>" style="display:flex;align-items:center;justify-content:center">
		<img src="<?php echo esc_url( $img_src ); ?>" alt="<?php echo esc_attr( $img_alt ); ?>" />
	</button>
</template>

<?php wp_print_inline_script_tag( $script ); ?>

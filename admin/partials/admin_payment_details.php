<?php
/**
 * HTML partial for custom payment details on admin order page.
 *
 * @package AZTemi\Solana_Pay_for_WC
 */

namespace AZTemi\Solana_Pay_for_WC;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

$short_txn = esc_html( Solana_Pay::shorten_hash_address( $transaction ) );

function echo_p( $key, $value, $url = '' ) {
	$p = '<p><strong>' . esc_html( $key ) . ':</strong> ';
	if ( $url ) {
		$p .= '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $value ) . '</a>';
	} else {
		$p .= esc_html( $value );
	}
	$p .= '</p>';
	echo wp_kses_post( $p );
}
?>

<br class="clear" />
<h3><?php esc_html_e( 'Solana Pay Payment Details', 'solana-pay-for-woocommerce' ); ?></h3>
<div class="payment_details">
<?php
	echo_p( __( 'Transaction ID', 'solana-pay-for-woocommerce' ), $short_txn, $url );
	echo_p( __( 'Customer Wallet', 'solana-pay-for-woocommerce' ), $payer );
	echo_p( __( 'Payment Amount', 'solana-pay-for-woocommerce' ), $paid );
	echo_p( __( 'Reference Account', 'solana-pay-for-woocommerce' ), $reference );
?>
</div>

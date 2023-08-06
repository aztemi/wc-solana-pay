<?php
/**
 * HTML partial for custom payment details on admin order page.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

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
<h3><?php esc_html_e( 'Solana Pay Payment Details', 'wc-solana-pay' ); ?></h3>
<div class="payment_details">
<?php
	echo_p( __( 'Transaction ID', 'wc-solana-pay' ), $short_txn, $url );
	echo_p( __( 'Customer Wallet', 'wc-solana-pay' ), $payer );
	echo_p( __( 'Amount', 'wc-solana-pay' ), $paid );
	echo_p( __( 'Net Amount after fees', 'wc-solana-pay' ), $received );
?>
</div>

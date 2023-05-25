<?php
/**
 * HTML partial for custom payment details on public-facing order page.
 *
 * @package AZTemi\Solana_Pay_for_WC
 */

namespace AZTemi\Solana_Pay_for_WC;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

$short_txn = esc_html( Solana_Pay::shorten_hash_address( $transaction, 15 ) );

function echo_tr( $key, $value, $url = '' ) {
	$tr = '<tr><th>' . esc_html( $key ) . ':</th><td>';
	if ( $url ) {
		$tr .= '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $value ) . '</a>';
	} else {
		$tr .= esc_html( $value );
	}
	$tr .= '</td></tr>';
	echo wp_kses_post( $tr );
}
?>

<h2><?php esc_html_e( 'Solana Pay Payment Details', 'solana-pay-for-woocommerce' ); ?></h2>
<table class="woocommerce-table shop_table payment_details">
	<tbody>
<?php
	echo_tr( __( 'Transaction ID', 'solana-pay-for-woocommerce' ), $short_txn, $url );
	echo_tr( __( 'Customer Wallet', 'solana-pay-for-woocommerce' ), $payer );
	echo_tr( __( 'Payment Amount', 'solana-pay-for-woocommerce' ), $paid );
	echo_tr( __( 'Reference Account', 'solana-pay-for-woocommerce' ), $reference );
?>
	</tbody>
</table>

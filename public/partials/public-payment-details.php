<?php
/**
 * HTML partial for custom payment details on public-facing order page.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


$short_txn = esc_html( Solana_Pay::shorten_hash_address( $transaction, 15 ) );
$network = $testmode ? __( 'Solana Devnet (Test Mode)', 'wc-solana-pay' ) : __( 'Solana Mainnet-Beta', 'wc-solana-pay' );

function echo_tr( $key, $value, $url = '', $copy_text = '' ) {
	$tr = '<tr><th>' . esc_html( $key ) . ':</th><td><div class="pwspfwc_flex pwspfwc_items_center">';
	if ( $url ) {
		$tr .= '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $value ) . '</a>';
	} else {
		$tr .= esc_html( $value );
	}
	$tr .= (bool) $copy_text ? get_copy_button_html( $copy_text ) : '';
	$tr .= '</div></td></tr>';
	echo wp_kses_post( $tr );
}
?>

<h2><?php esc_html_e( 'Solana Pay Payment Details', 'wc-solana-pay' ); ?></h2>
<table class="woocommerce-table shop_table payment_details">
	<tbody>
<?php
	echo_tr( __( 'Transaction ID', 'wc-solana-pay' ), $short_txn, $url, $transaction );
	echo_tr( __( 'Network', 'wc-solana-pay' ), $network );
	echo_tr( __( 'Wallet Address', 'wc-solana-pay' ), $payer, '', $payer );
	echo_tr( __( 'Amount', 'wc-solana-pay' ), $paid );
?>
	</tbody>
</table>

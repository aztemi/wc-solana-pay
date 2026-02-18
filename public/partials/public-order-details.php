<?php
/**
 * Custom Order details with 'Pay Now' button on order receipt page
 *
 * Template copied from woocommerce/order/order-details.php and customized for the plugin.
 *
 * @package AZTemi\WC_Solana_Pay
 */

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


$wc_order = wc_get_order( $order_id );

if ( ! $wc_order ) {
	return;
}

$order_items        = $wc_order->get_items( 'line_item' );
$show_purchase_note = $wc_order->has_status( array( 'completed', 'processing' ) );

// We make sure the order belongs to the user. This will also be true if the user is a guest, and the order belongs to a guest (userID === 0).
$show_customer_details = $wc_order->get_user_id() === get_current_user_id();
?>

<section class="woocommerce-order-details">
	<h2 class="woocommerce-order-details__title"><?php esc_html_e( 'Pay for order', 'wc-solana-pay' ); ?></h2>

	<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
		<tbody>
			<tr>
				<th class="order-actions--heading"><?php esc_html_e( 'Complete your payment', 'wc-solana-pay' ); ?>:</th>
				<td>
					<?php
					$wp_button_class = wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '';
					$class = 'woocommerce-button' . $wp_button_class . ' button pay order-actions-button pwspfwc_nowrap';
					$label = __( 'Pay Now', 'wc-solana-pay' );

					echo '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '" aria-label="' . esc_attr( $label ) . '">' . esc_html( $label ) . '</a>';
					?>
				</td>
			</tr>
		</tbody>
	</table>
</section>

<section class="woocommerce-order-details">
	<h2 class="woocommerce-order-details__title"><?php esc_html_e( 'Order details', 'woocommerce' ); ?></h2>

	<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">

		<thead>
			<tr>
				<th class="woocommerce-table__product-name product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
				<th class="woocommerce-table__product-table product-total"><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php
			foreach ( $order_items as $item_id => $item ) {
				$product = $item->get_product();

				wc_get_template(
					'order/order-details-item.php',
					array(
						'order'              => $wc_order,
						'item_id'            => $item_id,
						'item'               => $item,
						'show_purchase_note' => $show_purchase_note,
						'purchase_note'      => $product ? $product->get_purchase_note() : '',
						'product'            => $product,
					)
				);
			}
			?>
		</tbody>

		<tfoot>
			<?php foreach ( $wc_order->get_order_item_totals() as $key => $total ) { ?>
				<tr>
					<th scope="row"><?php echo esc_html( $total['label'] ); ?></th>
					<td><?php echo wp_kses_post( $total['value'] ); ?></td>
				</tr>
			<?php } ?>
		</tfoot>
	</table>
</section>

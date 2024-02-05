<?php
/**
 * HTML partial for token details on admin settings page.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


function get_th( $value, $style = '', $colspan = '', $tip = '' ) {

	if ( $style ) {
		$style = sprintf( ' style="%s"', $style );
	}
	if ( $colspan ) {
		$colspan = sprintf( ' colspan="%s"', $colspan );
	}

	$th = sprintf( '<th%s%s>%s%s</th>', $style, $colspan, esc_html( $value ), $tip );

	return $th;

}

function get_td( $value, $style = '' ) {

	if ( $style ) {
		$style = sprintf( ' style="%s"', $style );
	}

	$td = sprintf( '<td%s>%s</td>', $style, $value );

	return $td;

}

function get_input( $name, $value, $style = '', $type = 'text', $readonly = false ) {

	if ( $style ) {
		$style = sprintf( ' style="%s"', $style );
	}
	if ( 'checkbox' === $type ) {
		$value = $value ? ' checked="checked"' : '';
	} else {
		$value = ' value="' . esc_attr( $value ) . '"';
	}
	$readonly = $readonly ? ' readonly="readonly" class="disabled"' : '';

	$input = sprintf( '<input type="%s" name="%s"%s%s%s />', $type, $name, $value, $style, $readonly );

	return $input;

}

function get_tokens_table_header( $show_currency ) {

	$header = '<tr>'
		. get_th( __( 'Enabled', 'wc-solana-pay' ), 'text-align:center;max-width:6rem' )
		. get_th( __( 'Token', 'wc-solana-pay' ), 'min-width:15rem' )
		. get_th( __( 'Auto Refresh', 'wc-solana-pay' ), 'text-align:center;min-width:9rem;max-width:12rem', '', wp_kses_post( wc_help_tip( __( 'Auto refresh exchange rate every hour if checked.', 'wc-solana-pay' ), true ) ) )
		. get_th( __( 'Exchange Rate', 'wc-solana-pay' ), '', 3 )
		. get_th( __( '% Commission', 'wc-solana-pay' ), '' )
		. get_th( sprintf( '%s: 1.00 %s', __( 'Preview', 'wc-solana-pay' ), $show_currency ), 'text-align:right;min-width:8rem' )
		. '</tr>';

	return $header;

}

function get_tokens_table_rows( $tokens_table, $testmode_tokens, $live_tokens, $base_currency, $show_currency ) {

	$rows = '';

	// Enqueue DashIcons
	wp_enqueue_style( 'dashicons' );

	// check if exchange rate lookup is available for the store base currency
	$rate_lookup_available = Solana_Tokens::is_rate_conversion_supported();

	// Create Admin Settings table row for each supported token
	$i = -1;
	$supported_tokens = array_merge( $testmode_tokens, $live_tokens );
	foreach ( $supported_tokens as $k => $v ) {
		$i++;

		// add class used in JS code based on testmode or not
		$in_live = array_key_exists( $k, $live_tokens );
		$in_testmode = array_key_exists( $k, $testmode_tokens );
		$class = 'token';
		$class .= ( $in_live && ! $in_testmode ) ? ' live_only' : '';
		$class .= ( ! $in_live && $in_testmode ) ? ' testmode_only' : '';

		// default settings
		$table = array(
			'rate'        => '1.00',
			'fee'         => Solana_Pay::endpoint_usage_fee(),
			'enabled'     => !! $in_testmode, // enable testmode tokens by default
			'autorefresh' => true,
		);

		// merge saved settings into table
		if ( count( $tokens_table ) ) {
			if ( array_key_exists( $k, $tokens_table ) ) {
				$table = array_merge( $table, $tokens_table[ $k ] );
			} else {
				$table['enabled'] = false;
			}
		}

		// input element name fields
		$id          = "pwspfwc_id[$i]";
		$fee         = "pwspfwc_fee[$i]";
		$rate        = "pwspfwc_rate[$i]";
		$enabled     = "pwspfwc_enabled[$i]";
		$autorefresh = "pwspfwc_autorefresh[$i]";

		// token icon & name
		$token_icon = '<img src="' . PLUGIN_URL . '/' . $v['icon'] . '" alt="' . $v['name'] . ' icon" style="width:1.5rem;border-radius:50%">';
		$token_name = '<span style="padding-left:0.3rem">' . esc_html( $v['symbol'] ) . ' (' . esc_html( $v['name'] ) . ')</span>';
		$token_div = '<div style="display:flex;align-items:center">' . $token_icon . $token_name . '</div>';

		// fee input & percent
		$fee_input = get_input( $fee, $table['fee'], 'max-width:5rem' );
		$percent = '<span style="padding-left:0.3rem"><strong>%</strong></span>';
		$fee_div = '<div style="display:flex;align-items:center">' . $fee_input . $percent . '</div>';

		// rate refresh icon button
		$title_attr = __( 'Click to refresh exchange rate', 'wc-solana-pay' );
		$update_icon = '<span class="button-link dashicons dashicons-update" style="text-decoration-line:none" title="' . esc_attr( $title_attr ) . '" data-coingecko="' . esc_attr( $v['coingecko'] ) . '"></span>';

		// rate auto-refresh input
		if ( $rate_lookup_available ) {
			// show checkbox if rate lookup is available
			$rate_checkbox = get_input( $autorefresh, $table['autorefresh'], '', 'checkbox' );
		} else {
			// show 'Not Available' otherwise
			$update_icon = '';
			if ( $table['autorefresh'] ) {
				$table['rate'] = '';
				$table['autorefresh'] = false;
			}
			$title_attr = __( 'Not available for the store currency', 'wc-solana-pay' );
			$rate_checkbox = '<span title="' . esc_attr( $title_attr ) . '">' . esc_html__( 'Not Available', 'wc-solana-pay' ) . '<span>';
		}

		// remove rate refresh button for the store base currency
		if ( $k === $base_currency ) {
			$update_icon = '';
			$table['rate'] = '1.00';
		}

		$tr = '<tr class="' . $class . '" data-symbol="' . esc_attr( $v['symbol'] ) . '">'
			. get_td( get_input( $id, $k, '', 'hidden' ) . get_input( $enabled, $table['enabled'], '', 'checkbox' ), 'text-align:center' )
			. get_td( $token_div, 'padding-left:0.5rem' )
			. get_td( $rate_checkbox, 'text-align:center' )
			. get_td( $update_icon, 'text-align:right;padding-right:0 !important;vertical-align:bottom' )
			. get_td( get_input( $rate, $table['rate'], 'max-width:7rem', 'text', $table['autorefresh'] ), '' )
			. get_td( '<strong>+</strong>', 'text-align:center;vertical-align:middle' )
			. get_td( $fee_div, '' )
			. get_td( '<span class="token_preview"></span>', 'text-align:right;padding-right:0.5rem' )
			. '</tr>';
		$rows .= $tr;
	}

	return $rows;

}

function get_allowed_tags() {

	$allowed_tags = wp_kses_allowed_html( 'post' );

	// safe css attributes
	add_filter( 'safe_style_css', function( $styles ) {
		$styles[] = 'display';
		$styles[] = 'text-decoration-line';
		return $styles;
	} );

	// form input field
	$allowed_tags['input'] = array(
		'id'       => true,
		'class'    => true,
		'name'     => true,
		'value'    => true,
		'type'     => true,
		'style'    => true,
		'checked'  => true,
		'readonly' => true,
	);

	return $allowed_tags;

}

$allowed_tags = get_allowed_tags();
$header = get_tokens_table_header( $show_currency );
$body = get_tokens_table_rows( $tokens_table, $testmode_tokens, $live_tokens, $base_currency, $show_currency );
?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label><?php echo esc_html( $title ); ?><?php echo wp_kses_post( wc_help_tip( $tip, true ) ); ?></label>
	</th>
	<td class="forminp">
		<div class="wc_input_table_wrapper">
			<table class="wc_gateways widefat" style="min-width:70rem;max-width:85rem" cellspacing="0" cellpadding="0">
				<thead><?php echo wp_kses_post( $header ); ?></thead>
				<tbody><?php echo wp_kses( $body, $allowed_tags ); ?></tbody>
			</table>
		</div>
		<span class="testmode_only"><?php echo wp_kses_post( Solana_Tokens::testmode_faucet_tip() ); ?></span>
		<?php wp_print_inline_script_tag( $script ); ?>
	</td>
</tr>

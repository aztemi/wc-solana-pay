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


function get_th( $value, $style = '', $colspan = '' ) {

	if ( $style ) {
		$style = sprintf( ' style="%s"', $style );
	}
	if ( $colspan ) {
		$colspan = sprintf( ' colspan="%s"', $colspan );
	}

	$th = sprintf( '<th%s%s>%s</th>', $style, $colspan, esc_html( $value ) );

	return $th;

}

function get_td( $value, $style = '' ) {

	if ( $style ) {
		$style = sprintf( ' style="%s"', $style );
	}

	$td = sprintf( '<td%s>%s</td>', $style, $value );

	return $td;

}

function get_input( $name, $value, $style = '', $type = 'text' ) {

	if ( $style ) {
		$style = sprintf( ' style="%s"', $style );
	}
	if ( 'checkbox' === $type ) {
		$value = $value ? ' checked="checked"' : '';
	} else {
		$value = ' value="' . esc_attr( $value ) . '"';
	}

	$input = sprintf( '<input type="%s" name="%s"%s%s />', $type, $name, $value, $style );

	return $input;

}

function get_tokens_table_header( $show_currency ) {

	$header = '<tr>'
		. get_th( __( 'Accept', 'wc-solana-pay' ), 'text-align:center;max-width:6rem' )
		. get_th( __( 'Token', 'wc-solana-pay' ), 'min-width:10rem' )
		. get_th( __( 'Label', 'wc-solana-pay' ) )
		. get_th( __( 'Exchange Rate', 'wc-solana-pay' ), '', 3 )
		. get_th( __( '% Commission', 'wc-solana-pay' ), '' )
		. get_th( sprintf( '%s: 1.00 %s =', __( 'Preview', 'wc-solana-pay' ), $show_currency ), 'text-align:center;min-width:8rem' )
		. '</tr>';

	return $header;

}

function get_tokens_table_rows( $tokens_table, $testmode_tokens, $live_tokens, $base_currency, $show_currency, $auto_refresh ) {

	$rows = '';

	// Get currency exchange list supported by Coingecko API
	$coingecko_currencies = get_coingecko_supported_currencies();

	// Enqueue DashIcons
	wp_enqueue_style( 'dashicons' );

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
			'label'   => $v['symbol'],
			'rate'    => '1.00',
			'fee'     => Solana_Pay::endpoints_usage_fee(),
			'enabled' => !! $in_testmode, // enable testmode tokens by default
		);

		// merge saved settings into table
		if ( array_key_exists( $k, $tokens_table ) ) {
			$table = array_merge( $table, $tokens_table[ $k ] );
		}

		// Rate Update icon button
		$update_icon = '<span class="button-link dashicons dashicons-update" style="text-decoration-line:none" title="' . esc_attr( $auto_refresh ) . '" data-symbol="' . esc_attr( $v['symbol'] ) . '" data-coingecko="' . esc_attr( $v['coingecko'] ) . '"></span>';

		// Remove Rate Update button if token or stable coin is similar to store base currency
		$stablecoin = array_key_exists( 'stablecoin', $v ) ? strtoupper( $v['stablecoin'] ) : '';
		if ( ( $k === $base_currency ) || ( $stablecoin === $base_currency ) ) {
			$update_icon = '';
			$table['rate'] = '1.00';
		}

		// Remove Rate Update button if currency not in Coingecko supported list
		if ( count( $coingecko_currencies ) && ! in_array( strtolower( $show_currency ), $coingecko_currencies ) ) {
			$update_icon = '';
		}

		// input element name fields
		$id      = "pwspfwc_id[$i]";
		$fee     = "pwspfwc_fee[$i]";
		$rate    = "pwspfwc_rate[$i]";
		$label   = "pwspfwc_label[$i]";
		$enabled = "pwspfwc_enabled[$i]";

		// token icon & name
		$token_icon = '<img src="' . PLUGIN_URL . '/' . $v['icon'] . '" alt="' . $v['name'] . ' icon" style="width:1.5rem;border-radius:50%">';
		$token_name = '<span style="padding-left:0.3rem">' . esc_html( $v['name'] ) . '</span>';
		$token_div = '<div style="display:flex;align-items:center">' . $token_icon . $token_name . '</div>';

		// fee input & percent
		$fee_input = get_input( $fee, $table['fee'], 'max-width:5rem' );
		$percent = '<span style="padding-left:0.3rem"><strong>%</strong></span>';
		$fee_div = '<div style="display:flex;align-items:center">' . $fee_input . $percent . '</div>';

		$tr = '<tr class="' . $class . '" data-decimals="' . esc_attr( $v['decimals'] ) . '">'
			. get_td( get_input( $id, $k, '', 'hidden' ) . get_input( $enabled, $table['enabled'], '', 'checkbox' ), 'text-align:center' )
			. get_td( $token_div, 'padding-left:0.5rem' )
			. get_td( get_input( $label, $table['label'], 'max-width:7rem' ), '' )
			. get_td( $update_icon, 'text-align:right;padding-right:0 !important;vertical-align:bottom' )
			. get_td( get_input( $rate, $table['rate'], 'max-width:7rem' ), '' )
			. get_td( '<strong>+</strong>', 'text-align:center;vertical-align:middle' )
			. get_td( $fee_div, '' )
			. get_td( '<span class="token_preview"></span>', 'text-align:right;padding-right:0.5rem' )
			. '</tr>';
		$rows .= $tr;
	}

	return $rows;

}

function get_coingecko_supported_currencies() {

	$currencies_list = array();
	$url = 'https://api.coingecko.com/api/v3/simple/supported_vs_currencies';
	$response = wp_remote_get( $url, array(
		'method'      => 'GET',
		'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
		'timeout'     => 10,
		)
	);
	if ( ! is_wp_error( $response ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {
		$response_body = wp_remote_retrieve_body( $response );
		$currencies_list = json_decode( $response_body, true );
	}

	return $currencies_list;

}

$header = get_tokens_table_header( $show_currency );
$body = get_tokens_table_rows( $tokens_table, $testmode_tokens, $live_tokens, $base_currency, $show_currency, $auto_refresh );
?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label><?php echo esc_html( $title ); ?><?php echo wc_help_tip( $tip, true ); ?></label>
	</th>
	<td class="forminp">
		<div class="wc_input_table_wrapper">
			<table class="wc_gateways widefat" style="min-width:60rem;max-width:75rem" cellspacing="0" cellpadding="0">
				<thead><?php echo wp_kses_post( $header ); ?></thead>
				<tbody><?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></tbody>
			</table>
		</div>
		<?php wp_print_inline_script_tag( $script ); ?>
	</td>
</tr>

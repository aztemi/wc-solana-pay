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

function get_rate_notice( $show_currency ) {
	return '<p class="description">'
		/* translators: %s: Store currency name, e.g. 'USD' */
		. sprintf( __( 'Your store currency is: %s', 'wc-solana-pay' ), '<b>' . $show_currency . '</b>' )
		. '</p><p class="description" style="padding-bottom:0.5rem">'
		. __( 'It is currently not supported for automatic exchange rate lookup. Please enter your preferred exchange rates below.', 'wc-solana-pay' )
		. '</p>';
}

function get_tokens_table_header( $show_currency, $rate_available ) {
	return '<tr>'
		. get_th( __( 'Enabled', 'wc-solana-pay' ), 'text-align:center;max-width:4rem' )
		. get_th( __( 'Token', 'wc-solana-pay' ), 'min-width:10rem' )
		. ( $rate_available ? '' : get_th( sprintf( '%s: 1.00 %s =', __( 'Exchange Rate', 'wc-solana-pay' ), $show_currency ), 'text-align:center' ) )
		. '</tr>';
}

function get_tokens_table_rows( $tokens_table, $testmode_tokens, $live_tokens, $rate_available ) {
	$rows = '';

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
			'enabled'     => (bool) $in_testmode, // enable testmode tokens by default
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
		$rate        = "pwspfwc_rate[$i]";
		$enabled     = "pwspfwc_enabled[$i]";

		// token icon & name
		$token_icon = '<img src="' . PLUGIN_URL . '/' . $v['icon'] . '" alt="' . $v['name'] . ' icon" style="width:1.5rem;border-radius:50%">';
		$token_name = '<span style="padding-left:0.3rem">' . esc_html( $v['symbol'] ) . ' (' . esc_html( $v['name'] ) . ')</span>';
		$token_div = '<div style="display:flex;align-items:center">' . $token_icon . $token_name . '</div>'
			. ( $rate_available ? get_input( $rate, $table['rate'], '', 'hidden' ) : '' );

		$tr = '<tr class="' . $class . '" data-symbol="' . esc_attr( $v['symbol'] ) . '">'
			. get_td( get_input( $id, $k, '', 'hidden' ) . get_input( $enabled, $table['enabled'], '', 'checkbox' ), 'text-align:center' )
			. get_td( $token_div, 'padding-left:0.5rem' )
			. ( $rate_available ? '' : get_td( get_input( $rate, $table['rate'], 'text-align:end;min-width:8rem;width:100%', 'text' ) ) )
			. '</tr>';
		$rows .= $tr;
	}

	return $rows;
}

function get_allowed_tags() {
	$allowed_tags = wp_kses_allowed_html( 'post' );

	// safe css attributes
	add_filter( 'safe_style_css', function ( $styles ) {
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

// show notice if exchange rate lookup is not available for the store base currency
$rate_available = Solana_Tokens::is_rate_conversion_supported();
$rate_notice = $rate_available ? '' : get_rate_notice( $show_currency );

$allowed_tags = get_allowed_tags();
$header = get_tokens_table_header( $show_currency, $rate_available );
$body = get_tokens_table_rows( $tokens_table, $testmode_tokens, $live_tokens, $rate_available );
?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label><?php echo esc_html( $title ); ?><?php echo wp_kses_post( wc_help_tip( $tip, true ) ); ?></label>
	</th>
	<td class="forminp">
		<?php echo wp_kses_post( $rate_notice ); ?>
		<div class="wc_input_table_wrapper">
			<table class="wc_gateways widefat" style="max-width:45rem" cellspacing="0" cellpadding="0">
				<thead><?php echo wp_kses_post( $header ); ?></thead>
				<tbody><?php echo wp_kses( $body, $allowed_tags ); ?></tbody>
			</table>
		</div>
		<span class="testmode_only"><?php echo wp_kses_post( Solana_Tokens::testmode_faucet_tip() ); ?></span>
		<?php wp_print_inline_script_tag( $script ); ?>
	</td>
</tr>

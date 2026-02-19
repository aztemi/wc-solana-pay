<?php
/**
 * HTML partial for the custom plugin icon on the admin settings page.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="pwspfwc_plugin_icon"><?php echo esc_html( $title ); ?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<legend class="screen-reader-text"><span><?php echo esc_html( $title ); ?></span></legend>
			<div class="alignleft" style="display:flex;gap:1rem;align-items:center;">
				<img id="pwspfwc_img_icon" src="<?php echo esc_url( $icon ); ?>" alt="<?php echo esc_attr( $title ); ?>" style="max-height:5rem;" />
				<input type="button" id="pwspfwc_btn_upload" class="button" value="<?php esc_attr_e( 'Select or Upload an Icon', 'wc-solana-pay' ); ?>" />
				<input type="button" id="pwspfwc_btn_reset" class="button" value="<?php esc_attr_e( 'Restore default Icon', 'wc-solana-pay' ); ?>" data-defaulticon="<?php echo esc_attr( $default_icon ); ?>" />
				<input class="input-text regular-input " type="hidden" name="pwspfwc_plugin_icon" id="pwspfwc_plugin_icon" value="<?php echo esc_attr( $icon ); ?>" />
			</div>
		</fieldset>
		<p class="description"><?php echo esc_html( $tip ); ?></p>
		<?php wp_print_inline_script_tag( $script ); ?>
	</td>
</tr>

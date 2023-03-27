<?php
/**
 * HTML partial for payment modal which is shown when 'Place order' button is clicked.
 *
 * @package T4top\Solana_Pay_for_WC
 */

namespace T4top\Solana_Pay_for_WC;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) { die; }

$wp_button_class = esc_attr(function_exists('wp_theme_get_element_class_name') ? wp_theme_get_element_class_name('button') : '');
?>

<span x-data="{ isOpen: false }">
  <div class="solana_pay_for_wc_overlay" x-show="isOpen" @openmodal.window="isOpen = true" @closemodal.window="isOpen = false" style="display: none;">
    <div class="modal">
      <button class="closeBtn button alt <?php echo $wp_button_class ?>" @click.prevent="$dispatch('closemodal')">x</button>
      <div id="solana_pay_for_wc_svelte_target"></div>
    </div>
  </div>
</span>

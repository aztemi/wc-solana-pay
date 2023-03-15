<script>
  import Alpine from "alpinejs";
  import { Buffer } from "buffer";
  import { onMount } from "svelte";

  let isAlpineLoaded = false;
  let SolanaPay = null;
  let alreadyCalled = false;

  function handleWindowOnload() {
    window.Buffer = Buffer;
    window.Alpine = Alpine;
    Alpine.start();
    handlePaymentMethodChange();
    Alpine.store("initialized", true);
    isAlpineLoaded = true;
  }

  function handlePaymentMethodChange() {
    const selected_method = document.querySelector("input[name='payment_method']:checked").value;
    const { id } = solana_pay_for_wc;
    Alpine.store("solana_pay_selected", selected_method === id);
    setTimeout(() => {
      if (!alreadyCalled) addClickToSolanaPayButton();
    }, 1000);
  }

  function addClickToSolanaPayButton() {
    const btn = jQuery("#solana_pay_for_wc_checkout_place_order");
    if (btn.length) {
      alreadyCalled = true;
      btn.on("click", function (event) {
        // check if Checkout form input is valid
        jQuery("form.woocommerce-checkout .validate-required:visible :input").trigger("validate");
        const invalidFields = Array.from(
          jQuery("form.woocommerce-checkout .validate-required.woocommerce-invalid:visible")
        );

        if (invalidFields.length <= 0) {
          // open Solana Pay modal
          dispatchEvent(new Event("openmodal"));
        }

        // prevent default
        event.preventDefault();
      });
    }
  }

  function isFormInputValid() {
    jQuery("form.woocommerce-checkout .validate-required:visible :input").trigger("validate");
    const invalidFields = Array.from(
      jQuery("form.woocommerce-checkout .validate-required.woocommerce-invalid:visible")
    );
    return invalidFields.length ? false : true;
  }

  onMount(async () => {
    SolanaPay = (await import("./solana_pay.svelte")).default;
  });
</script>

<svelte:window on:load={handleWindowOnload} on:change={handlePaymentMethodChange} />

{#if isAlpineLoaded && SolanaPay}
  <svelte:component this={SolanaPay} />
{/if}

<style lang="stylus">

  :global
    #solana_pay_for_wc_checkout_place_order
      display flex
      align-items center
      justify-content center
      padding-left 0.2em
      padding-right 0.2em
      span
        padding 0
        margin 0
      img
        display inline-block
        padding 0
        border 0
        max-height 1.2em
        margin-left 0.5em
    
    .solana_pay_for_wc_overlay
      position fixed
      z-index 1000
      left 0
      top 0
      width 100%
      height 100vh
      display flex
      align-items center
      justify-content center
      overflow hidden
      .modal
        position relative
        max-width 90vw
        max-height 90%
        padding 1rem 2rem
        background-color white
        border 1px solid black
        border-radius 0.5rem
        box-shadow 0 4px 23px 0 rgb(0 0 0 / 20%)
        overflow-y auto
        .closeBtn
          position absolute
          top 1rem
          right 1rem

</style>

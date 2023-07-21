<script>
  import { Keypair } from "@solana/web3.js";
  import { order } from "../store/order.js";
  import { submitCheckoutForm, getCheckoutOrderDetails } from "../utils/backend_proxy.js";
  import Header from "./header.svelte";
  import Loading from "./loading.svelte";
  import PaymentWidget from "./payment_widget.svelte";

  let showModal = false;

  $: {
    if ($order.paymentSignature) {
      // submit form and close popup modal. This will inform the backend to confirm payment
      submitCheckoutForm();
      closeModal();
    }
  }

  async function openModal() {
    order.reset();
    showModal = true;
    await getCheckoutOrder();
  }

  function closeModal() {
    showModal = false;
  }

  // query payment details from the backend
  async function getCheckoutOrder() {
    const ref = new Keypair().publicKey;
    const jsonOrder = await getCheckoutOrderDetails(ref.toBase58());
    order.setOrder(jsonOrder);
  }
</script>

<svelte:window on:openmodal={openModal} />

{#if showModal}
  <div class="pwspfwc_popup_overlay">
    <div class="pwspfwc_popup_shadow pwspfwc_popup_modal">
      <Header on:close={closeModal} />
      {#if $order.updated}
        <PaymentWidget />
      {:else}
        <Loading />
      {/if}
    </div>
  </div>
{/if}

<style lang="stylus">
  .pwspfwc_popup_overlay
    position fixed
    z-index var(--layer_overlay)
    left 0
    top 0
    width 100%
    height 100vh
    display flex
    align-items center
    justify-content center
    overflow hidden
    background-color var(--overlay_back_color)
    .pwspfwc_popup_modal
      position relative
      display block
      overflow-y auto
      max-width 90vw
      max-height 90%
      border-radius 0.5rem
      border 1px solid var(--modal_border_color)
      background-color var(--modal_back_color)

</style>

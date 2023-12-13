<script>
  import { Keypair } from "@solana/web3.js";
  import { order } from "../store/order.js";
  import { Notification, notification, showSubmitOrderStatus, EXIT, STATE } from "./notification";
  import { submitCheckoutForm, getCheckoutOrderDetails } from "../utils/backend_proxy.js";
  import Header from "./header.svelte";
  import Loading from "./loading.svelte";
  import PaymentWidget from "./payment_widget.svelte";

  const WAIT_DURATION = 2000;
  let showModal = false;

  // close modal when order times out
  $: if ($order.timedOut) closeModal();

  // If payment transaction received, show notice for few seconds, submit form and close modal
  $: {
    if ($order.paymentSignature) {
      showSubmitOrderStatus();
      setTimeout(() => {
        submitCheckoutForm();
        closeModal();
      }, WAIT_DURATION);
    }
  }

  async function openModal() {
    notification.reset();
    order.reset();
    showModal = true;
    await getCheckoutOrder();
  }

  function closeModal() {
    showModal = false;
  }

  // query payment details from the backend
  async function getCheckoutOrder() {
    let msgId = 0;
    try {
      msgId = notification.addNotice("Getting order details", STATE.LOADING);

      const ref = new Keypair().publicKey;
      const jsonOrder = await getCheckoutOrderDetails(ref.toBase58());
      order.setOrder(jsonOrder);

      notification.updateNotice(msgId, { status: STATE.OK, exit: EXIT.TIMEOUT });
    } catch (error) {
      notification.updateNotice(msgId, { status: STATE.ERROR, error: error.message, exit: EXIT.MANUAL });
      console.error(error.toString());
    }
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
      <Notification />
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
      max-height 95%
      border-radius 0.5rem
      border 1px solid var(--modal_border_color)
      background-color var(--modal_back_color)

</style>

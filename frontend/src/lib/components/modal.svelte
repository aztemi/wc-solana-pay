<script>
  import { order } from "../store/order.js";
  import { Notification, notification, showSubmitOrderStatus, EXIT, STATE } from "./notification";
  import { getCheckoutOrderDetails, isCheckoutCartValid, confirmPayment } from "../utils/backend_proxy.js";
  import { isLocalhost } from "../utils/helpers.js";
  import Header from "./header.svelte";
  import Loading from "./loading.svelte";
  import PaymentWidget from "./payment_widget.svelte";

  const WAIT_DURATION = 2000;
  let showModal = false;

  // close modal when order times out
  $: if ($order.timedOut) closeModal();

  // If successful payment transaction received, show notice for few seconds, close modal and redirect to 'Order Received' page
  $: {
    if ($order.paymentSignature) {
      confirmPayment($order.paymentId, $order.orderId)
        .then(({ result, redirect }) => {
          if (result === "success") {
            showSubmitOrderStatus();

            setTimeout(() => {
              closeModal();
              window.location.assign(redirect);
            }, WAIT_DURATION);
          }
        })
        .catch(e => console.error(e.toString()));
    }
  }

  async function openModal() {
    if (showModal) return;

    notification.reset();
    order.reset();
    showModal = true;
    await getCheckoutOrder();
    if (isLocalhost())
      notification.addNotice(
        "Transactions validation not available",
        STATE.ERROR,
        EXIT.MANUAL,
        "WordPress is on localhost. Webhook callback not available."
      );
  }

  function closeModal() {
    if (!$order.paymentSignature) {
      // remove hash from the checkout page URL
      const url = new URL(window.location.href);
      url.hash = "";
      window.location.replace(url);
    }

    showModal = false;
  }

  // query payment details from the backend
  async function getCheckoutOrder() {
    let msgId = 0;
    try {
      msgId = notification.addNotice("Getting order details", STATE.LOADING);

      const orderId = window.location.hash.split("@")[0].split("-").pop();
      const [jsonOrder] = await Promise.all([getCheckoutOrderDetails(orderId), isCheckoutCartValid()]);
      order.setOrder(jsonOrder);

      notification.updateNotice(msgId, { status: STATE.OK, exit: EXIT.TIMEOUT });
    } catch (error) {
      notification.updateNotice(msgId, { status: STATE.ERROR, error: error.message, exit: EXIT.TIMEOUT });
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
      border-radius 0.5rem
      border 1px solid var(--modal_border_color)
      background-color var(--modal_back_color)
      max-height 95%
      width 100%
      // Hack instead of `max-width min(95vw, 30rem)` due to bugs in Stylus & Svelte-preprocess (https://github.com/stylus/stylus/issues/2584)
      max-width 95vw
      @media screen and (min-width: 500px)
        max-width 480px

</style>

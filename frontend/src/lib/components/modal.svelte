<script>
  import { Keypair } from "@solana/web3.js";
  import { order } from "../store/order.js";
  import Widget from "./widget.svelte";
  import Loading from "./loading.svelte";
  import Icon from "./icons/icon.svelte";

  let showModal = false;
  const { id, baseurl, pay_page, order_id } = solana_pay_for_wc;

  $: {
    if ($order.paymentSignature) {
      // console.log("Payment confirmed on client side. Txn: ", $order.paymentSignature);
      // submit form and close popup modal. This will inform the backend to confirm payment
      const form = jQuery(pay_page ? "form#order_review" : "form.checkout");
      form.submit();
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
    try {
      const ref = new Keypair().publicKey;
      let url = `?wc-api=${id}&ref=${ref.toBase58()}&`;

      if (pay_page) {
        // pay order page
        url += `order_id=${order_id}`;
      } else {
        // checkout page
        const cartCreated = sessionStorage.getItem("wc_cart_created");
        url += `cart_created=${cartCreated}`;
      }

      const jsonOrder = await fetch(url).then(r => r.json());
      order.setOrder(jsonOrder);
    } finally {
      // do nothing
    }
  }
</script>

<svelte:window on:openmodal={openModal} />

{#if showModal}
  <div class="overlay">
    <div class="pwspfwc_popup_shadow modal">
      <div class="header">
        <img src={`${baseurl}/assets/img/solana_pay_black.svg`} alt="Solana Pay" />
        <button class="pwspfwc_icon_button" on:click={closeModal}><Icon name="close" title="Close" /></button>
      </div>
      {#if $order.updated}
        <Widget />
      {:else}
        <Loading />
      {/if}
    </div>
  </div>
{/if}

<style lang="stylus">
  .overlay
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
    .modal
      position relative
      overflow-y auto
      max-width 90vw
      max-height 90%
      border-radius 0.5rem
      border 1px solid var(--modal_border_color)
      background-color var(--modal_back_color)
      .header
        padding 0.7rem 1rem 0 1rem
        display flex
        align-items center
        justify-content space-between

</style>

<script>
  import { Keypair } from "@solana/web3.js";
  import { order } from "../store/order.js";
  import Widget from "./widget.svelte";
  import Loading from "./loading.svelte";

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
  <div class="spfwc_overlay">
    <div class="spfwc_modal spfwc_popup_shadow">
      <div class="spfwc_header">
        <img src={`${baseurl}/assets/img/solana_pay_black.svg`} alt="Solana Pay" />
        <button class="closeBtn" on:click={closeModal}><span class="dashicons dashicons-no-alt" /></button>
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
  :root
    --overlay_back_color alpha(#000, 0.7)
    --modal_back_color #fff
    --modal_border_color #000
    --popup_border_shadow_color rgb(0 0 0 / 20%)
    --popup_li_back_color #fafafa

  .spfwc_overlay
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
    background-color var(--overlay_back_color)
    .spfwc_modal
      position relative
      overflow-y auto
      max-width 90vw
      max-height 90%
      border-radius 0.5rem
      border 1px solid var(--modal_border_color)
      background-color var(--modal_back_color)
      .spfwc_header
        padding 0.7rem 1rem 0 1rem
        display flex
        align-items center
        justify-content space-between
      .closeBtn
        border 0
        line-height 1
        text-align center
        cursor pointer
        background-color transparent
        color currentcolor
        .dashicons
          font-size 3rem
          display flex
          align-items center
          justify-content center

  :global
    .spfwc_popup_shadow
      box-shadow 0 4px 24px 0 var(--popup_border_shadow_color)
    .solana_pay_for_wc_place_order
      display flex
      align-items center
      justify-content center
      span
        padding 0
        margin 0
        margin-right 0.5em
      img
        display inline-block
        padding 0
        border 0
        max-height 1.2em

</style>

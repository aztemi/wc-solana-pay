<script>
  import { onMount } from "svelte";
  import { Keypair } from "@solana/web3.js";
  import { order } from "../lib/store/order.js";
  import Modal from "../lib/components/modal.svelte";

  const { id, btn_class } = solana_pay_for_wc;
  let showModal = false;

  onMount(() => {
    getSolUSDPrice();
  });

  function openModal() {
    order.reset();
    // query data from the backend
    getCheckoutOrder().then(() => {
      showModal = true;
    });
  }

  function closeModal() {
    showModal = false;
  }

  $: {
    if ($order.paymentSignature) {
      console.log("Payment confirmed on client side. Txn: ", $order.paymentSignature);
      // submit checkout form. Backend will then be informed to confirm payment
      jQuery("form.checkout").submit();
      closeModal();
    }
  }

  function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  async function getSolUSDPrice() {
    let numTry = 0;
    const maxTry = 5; // Max number of tries in case of failure
    const url = "https://api.coingecko.com/api/v3/simple/price?ids=solana&vs_currencies=usd";

    while (numTry < maxTry && !$order.solPrice) {
      try {
        const resp = await fetch(url);
        const data = await resp.json();

        order.setSolPrice(data?.solana?.usd);
      } catch (error) {
        console.error(error);
      }
      numTry++;
      await sleep(1000);
    }
  }

  async function getCheckoutOrder() {
    try {
      const ref = new Keypair().publicKey;
      const url = `/wc-api/${id}?ref=${ref.toBase58()}`;

      const resp = await fetch(url);
      const data = await resp.json();
      order.setOrder(data);
    } catch (error) {
      console.error(error);
    }
  }
</script>

<svelte:window on:openmodal={openModal} />

{#if showModal}
  <div class="overlay">
    <div class="modal">
      <button class={`closeBtn button alt ${btn_class}`} on:click={closeModal}>x</button>
      {#if $order.updated}
        <Modal />
      {:else}
        <p>loading</p>
      {/if}
    </div>
  </div>
{/if}

<style lang="stylus">

  .overlay
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

  :global
    .solana_pay_for_wc_place_order
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

</style>

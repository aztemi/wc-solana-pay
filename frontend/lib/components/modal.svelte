<script>
  import { onMount, onDestroy } from "svelte";
  import { Connection } from "@solana/web3.js";
  import { walletStore } from "@svelte-on-solana/wallet-adapter-core";
  import { createTransfer, findReference, FindReferenceError } from "@solana/pay";
  import { order } from "../store/order.js";
  import { clickOutside } from "../actions/click_outside.js";
  import Wallet from "./wallet.svelte";
  import QrCode from "./qrcode.svelte";
  import OrDivider from "./or_divider.svelte";

  // Delay in ms between polling intervals for confirming transaction on chain
  const POLLING_DELAY = 2000;

  let connection = null;
  let pollingInterval = null;
  let dropdownOpen = false;
  let name, splAmount, splToken, icon, symbol;

  const { baseurl, pay_page, wallet_msg, or_msg, qrcode_msg } = solana_pay_for_wc;
  const { recipient, reference, label, message, memo, amount, currency, endpoint, tokens } = $order;
  const dropdownRequired = Object.keys(tokens).length > 1;

  $: {
    if ($order.activeToken) {
      const key = $order.activeToken;
      ({ name, amount: splAmount, icon, symbol, mint: splToken } = tokens[key]);

      const form = jQuery(pay_page ? "form#order_review" : "form.checkout");
      const input = form.find("input[name='spfwc_payment_token']");
      if (input.length) {
        input.val(key);
      } else {
        form.append(`<input type="hidden" name="spfwc_payment_token" value="${key}" />`);
      }
    }
  }

  $: {
    if ($walletStore?.connected && !$walletStore?.connecting && !$walletStore?.disconnecting) {
      payWithConnectedWallet($walletStore.publicKey);
    }
  }

  onMount(() => {
    connection = new Connection(endpoint, "confirmed");
    startPolling();
  });

  onDestroy(() => {
    stopPolling();
  });

  // poll every 1s for confirmed transaction on chain since we don't know if Qr-Code is scanned or not.
  function startPolling() {
    pollingInterval = setInterval(confirmPaymentTxn, POLLING_DELAY);
  }

  function stopPolling() {
    if (pollingInterval) {
      clearInterval(pollingInterval);
      pollingInterval = null;
    }
  }

  // Confirm transaction on chain
  async function confirmPaymentTxn() {
    if (!connection) return;
    try {
      const signatureInfo = await findReference(connection, reference, { finality: "confirmed" });
      if (signatureInfo?.signature) order.confirmPayment(signatureInfo.signature);
    } catch (error) {
      if (!(error instanceof FindReferenceError)) console.error(error);
    }
  }

  async function payWithConnectedWallet(payer) {
    if ($walletStore?.connected && connection) {
      const tx = await createTransfer(connection, payer, { recipient, amount: splAmount, splToken, reference, memo });
      await $walletStore.sendTransaction(tx, connection);
    }
  }
</script>

<section>
  <div class="total">
    <span class="dashicons dashicons-cart" />
    <span>
      <bdi>
        <span><b>{amount.toNumber()}</b></span>
        <span class="woocommerce-Price-currencySymbol"><b>{@html currency}</b></span>
      </bdi>
    </span>
  </div>

  <div class="topay">
    <span class="token_amount"><b>{splAmount.toNumber()}</b></span>
    <span class="tokens">
      <button
        class:nopointer={!dropdownRequired}
        on:click|preventDefault|stopPropagation={() => {
          if (dropdownRequired) dropdownOpen = !dropdownOpen;
        }}
      >
        <img src={`${baseurl}/${icon}`} alt={name} />
        <span class="token_symbol">{symbol}</span>
        {#if dropdownRequired}
          {#if dropdownOpen}
            <span class="dashicons dashicons-arrow-up-alt2" />
          {:else}
            <span class="dashicons dashicons-arrow-down-alt2" />
          {/if}
        {/if}
      </button>
      {#if dropdownOpen}
        <div class="dropdown">
          <ul
            class="popup_shadow"
            use:clickOutside={() => {
              dropdownOpen = false;
            }}
          >
            {#each Object.entries(tokens) as [key, token]}
              <li class:selected={key === $order.activeToken}>
                <button
                  on:click|preventDefault|stopPropagation={() => {
                    order.setActiveToken(key);
                    dropdownOpen = false;
                  }}
                >
                  <img src={`${baseurl}/${token.icon}`} alt={token.name} />
                  <span class="token_symbol">{token.symbol}</span>
                </button>
              </li>
            {/each}
          </ul>
        </div>
      {/if}
    </span>
  </div>

  <div class="options">
    <p>{wallet_msg}</p>
    <div class="wallet">
      <Wallet network={endpoint} />
    </div>

    <OrDivider text={or_msg} />

    <p>{qrcode_msg}</p>
    <div class="qrcode">
      {#key splToken}
        <QrCode {recipient} amount={splAmount} {splToken} {reference} {label} {message} {memo} />
      {/key}
    </div>
  </div>
</section>

<style lang="stylus">
  section
    padding 1rem 2rem
    p
      margin 0

  .total
    display flex
    align-items center
    justify-content right
    padding-right 0.5rem
    & > span
      margin-left 0.2rem

  .topay
    display flex
    align-items center
    justify-content center
    .token_amount
      font-size 2rem
      padding 0 0.5rem
    .nopointer
      cursor auto
    .tokens
      display inline-block
      button
        display flex
        align-items center
        line-height 1
        border 0
        padding 0.5rem 1rem
        width 100%
        outline none
        background-color transparent
        color currentcolor
        img
          width 1.5rem
          border-radius 50%
        .token_symbol
          font-size 1.5rem
          padding 0 0.7rem
          white-space nowrap
      .dropdown
        position relative
        z-index 2000
        ul
          list-style-type none
          position absolute
          padding 0
          top 0.2rem
          right 0
          margin 0
          width 100%
          border-radius 0.5rem
          background-color var(--modal_back_color)
          li
            padding 0
            &:hover, &.selected
              background-color var(--popup_li_back_color)
            &:first-of-type
              border-radius 0.5rem 0.5rem 0 0
            &:last-of-type
              border-radius 0 0 0.5rem 0.5rem

  .options
    padding-top 1rem
    display flex
    flex-direction column
    align-items center
    .wallet, .qrcode
      display flex
      justify-content center
    .wallet
      padding-top 0.5rem

</style>

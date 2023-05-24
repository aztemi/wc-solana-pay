<script>
  import { onMount, onDestroy } from "svelte";
  import { clusterApiUrl, Connection, PublicKey } from "@solana/web3.js";
  import { walletStore } from "@svelte-on-solana/wallet-adapter-core";
  import { createTransfer, findReference, FindReferenceError } from "@solana/pay";
  import { order } from "../store/order.js";
  import Wallet from "./wallet.svelte";
  import QrCode from "./qrcode.svelte";

  // Delay in ms between polling interval for confirmed transaction on chain
  const POLLING_DELAY = 2000;

  const { baseurl } = solana_pay_for_wc;
  const { recipient, reference, label, amount, solAmount, message, memo } = $order;
  const endpoint = clusterApiUrl("devnet");
  const USDC = {
    devnet: new PublicKey("Gh9ZwEmdLJ8DscKNTkTqPbNwLNNBjuSzaG9Vp2KGtKJr")
  };
  const splToken = USDC.devnet;

  let connection = null;
  let pollingInterval = null;

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

  $: {
    if ($walletStore?.connected && !$walletStore?.connecting && !$walletStore?.disconnecting) {
      payWithConnectedWallet($walletStore.publicKey);
    }
  }

  async function payWithConnectedWallet(payer) {
    if ($walletStore?.connected && connection) {
      const tx = await createTransfer(connection, payer, { recipient, amount, splToken, reference, memo });
      await $walletStore.sendTransaction(tx, connection);
    }
  }
</script>

<section>
  <div>
    <img src={`${baseurl}/assets/img/solana_pay_black.svg`} alt="Solana Pay" />
  </div>
  <div class="amount">
    <span>Amount:</span>
    <div>
      <p><strong>{amount} USDC</strong></p>
      {#if solAmount}
        <p>({solAmount} SOL)</p>
      {/if}
    </div>
  </div>
  <div class="container">
    <div>
      <p>Connect your Browser Wallet to pay</p>
      <div class="wallet">
        <Wallet network={endpoint} />
      </div>
    </div>
    <div class="spacer">
      <span> OR </span>
    </div>
    <div>
      <p>Scan QR Code with your Mobile Wallet to pay</p>
      <div class="qrcode">
        {#if recipient}
          {#key splToken}
            <QrCode {recipient} {amount} {splToken} {reference} {label} {message} {memo} />
          {/key}
        {/if}
      </div>
    </div>
  </div>
</section>

<style lang="stylus">
  p
    margin 0

  .amount
    display flex
    flex-direction row
    align-items center
    justify-content center
    margin 1rem 0
    div
      margin-left 2rem
      strong
        font-weight 800
        font-size 1.7rem

  .container
    display flex
    flex-direction column
    align-items center
    text-align center

    .wallet
      margin-top 1rem

    .spacer
      margin 1rem 0
      span
        &::before, &::after
          content "\00a0\00a0\00a0\00a0\00a0\00a0\00a0\00a0\00a0\00a0"
          text-decoration line-through
          padding 0 0.5rem

</style>

<script>
  import BigNumber from "bignumber.js";
  import { onMount, onDestroy } from "svelte";
  import { clusterApiUrl, Connection, PublicKey, Keypair } from "@solana/web3.js";
  import { walletStore } from "@svelte-on-solana/wallet-adapter-core";
  import { createTransfer, findReference, FindReferenceError } from "@solana/pay";
  import Wallet from "../lib/components/wallet.svelte";
  import QrCode from "../lib/components/qrcode.svelte";

  const { id } = solana_pay_for_wc;
  const endpoint = clusterApiUrl("devnet");
  const USDC = {
    devnet: new PublicKey("Gh9ZwEmdLJ8DscKNTkTqPbNwLNNBjuSzaG9Vp2KGtKJr")
  };
  const splToken = USDC.devnet;

  let connection = null;
  let stopPolling = false;
  let recipient, reference, label, amount, nonce, message, memo;
  let solAmount,
    solPrice = 0;
  let loading = false;

  onMount(() => {
    connection = new Connection(endpoint, "confirmed");
    getSolanaUSDPrice();
  });

  onDestroy(() => {
    stopPolling = true;
  });

  function handleOpenModal() {
    // style connect button
    jQuery(".wallet-adapter-button").addClass("alt");

    // query data from the backend
    getCheckoutData().then(() => {
      stopPolling = false;
      pollConfirmedTxn();
    });
  }

  function handleCloseModal() {
    stopPolling = true;
  }

  async function getCheckoutData() {
    loading = true;

    const ref = new Keypair().publicKey;
    const url = `/wc-api/${id}?ref=${ref.toBase58()}`;
    const response = await fetch(url);
    const data = await response.json();

    recipient = new PublicKey(data.recipient);
    reference = new PublicKey(data.reference);
    amount = new BigNumber(parseFloat(data.amount).toFixed(2));
    if (solPrice) solAmount = (parseFloat(data.amount) / solPrice).toFixed(2);
    label = data.label;
    nonce = data.nonce;
    message = `Thank You - #${nonce} - ${label}`;
    memo = `OrderNonce#${nonce}`;
    loading = false;
  }

  async function getSolanaUSDPrice() {
    const url = "https://api.coingecko.com/api/v3/simple/price?ids=solana&vs_currencies=usd";
    const response = await fetch(url);
    const data = await response.json();

    solPrice = data?.solana?.usd;
  }

  // poll every 1s for confirmed transaction since we don't know if Qr-Code is scanned or not.
  async function pollConfirmedTxn() {
    if (!connection) return;

    let signatureInfo;

    await new Promise((resolve, reject) => {
      const interval = setInterval(async () => {
        if (stopPolling) {
          clearInterval(interval);
          reject(new Error("Polling Cancelled"));
        }

        // console.count("Polling for transaction...");
        try {
          signatureInfo = await findReference(connection, reference, { finality: "confirmed" });
          console.log("Payment signature found: ", signatureInfo.signature);
          clearInterval(interval);
          resolve(signatureInfo);
        } catch (error) {
          if (!(error instanceof FindReferenceError)) {
            console.error(error);
            clearInterval(interval);
            reject(error);
          }
        }
      }, 1000);
    }).catch(() => {
      /* ignore */
    });

    if (signatureInfo?.signature) handlePaymentReceived();
  }

  $: {
    if ($walletStore?.connected && !$walletStore?.connecting && !$walletStore?.disconnecting) {
      triggerTxn($walletStore.publicKey);
    }
  }

  async function triggerTxn(payer) {
    if ($walletStore?.connected && connection) {
      const tx = await createTransfer(connection, payer, { recipient, amount, splToken, reference, memo });
      const res = await $walletStore.sendTransaction(tx, connection);
    }
  }

  function handlePaymentReceived() {
    // close modal
    dispatchEvent(new Event("closemodal"));

    // submit checkout form. Backend will then be informed to confirm payment
    jQuery("form.checkout.woocommerce-checkout").submit();
  }
</script>

<svelte:window on:openmodal={handleOpenModal} on:closemodal={handleCloseModal} />

<section>
  <div>
    <img src="/wp-content/plugins/solana-pay-for-woocommerce/assets/img/solana_pay_black.svg" alt="Solana Pay" />
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
        {#if loading}
          <p>loading</p>
        {:else if recipient}
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

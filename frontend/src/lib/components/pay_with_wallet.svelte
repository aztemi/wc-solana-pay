<script>
  import { onMount } from "svelte";
  import { Buffer } from "buffer";
  import { Transaction } from "@solana/web3.js";
  import { walletStore } from "@aztemi/svelte-on-solana-wallet-adapter-core";
  import { ConnectionProvider, WalletProvider } from "@aztemi/svelte-on-solana-wallet-adapter-ui";
  import { startPolling } from "../utils/poll_for_transaction";
  import { postRequest } from "../utils/post_request";
  import { notification, EXIT, STATE } from "./notification";
  import WalletSplitMultiButton from "./buttons/wallet_split_multi_button.svelte";

  export let link;
  export let endpoint;

  let wallets = [];
  let loading = false;
  const localStorageKey = "SolanaWalletAdapter";

  onMount(async () => {
    const {
      PhantomWalletAdapter,
      SolflareWalletAdapter,
      CoinbaseWalletAdapter,
      LedgerWalletAdapter,
      SafePalWalletAdapter,
      TrustWalletAdapter,
      MathWalletAdapter,
      TorusWalletAdapter
    } = await import("@solana/wallet-adapter-wallets");

    wallets = [
      new PhantomWalletAdapter(),
      new SolflareWalletAdapter(),
      new CoinbaseWalletAdapter(),
      new LedgerWalletAdapter(),
      new SafePalWalletAdapter(),
      new TrustWalletAdapter(),
      new MathWalletAdapter(),
      new TorusWalletAdapter()
    ];
  });

  async function payWithConnectedWallet() {
    let msgId = 0;
    if ($walletStore?.connected) {
      try {
        loading = true;
        msgId = notification.addNotice("Processing payment transaction", STATE.LOADING);

        // fetch the transaction
        const { transaction } = await postRequest(link, { account: $walletStore.publicKey.toBase58() });

        // extract payment transaction created in backend
        const txBuf = Buffer.from(transaction, "base64");
        let tx = Transaction.from(txBuf);

        // sign the transaction
        tx = await $walletStore.signTransaction(tx);

        // send the transaction via backend RPC endpoint
        await postRequest(endpoint, { transaction: tx.serialize().toString("base64") });

        // poll for transaction result
        startPolling();

        notification.updateNotice(msgId, { status: STATE.OK, exit: EXIT.TIMEOUT });
      } catch (error) {
        notification.updateNotice(msgId, { status: STATE.ERROR, error: error.message, exit: EXIT.MANUAL });
        console.error(error.toString());
      } finally {
        loading = false;
      }
    }
  }
</script>

<ConnectionProvider network={endpoint} />
<WalletProvider {localStorageKey} {wallets} autoConnect={true} />

<span>Pay with Browser Wallet</span>
<div>
  {#key link}
    <WalletSplitMultiButton {loading} on:payclick={payWithConnectedWallet} />
  {/key}
</div>

<style lang="stylus">
  div
    padding-top 0.5rem
    text-align center

</style>

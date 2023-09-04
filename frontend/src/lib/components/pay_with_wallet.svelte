<script>
  import { onMount } from "svelte";
  import { Buffer } from "buffer";
  import { Transaction } from "@solana/web3.js";
  import { walletStore } from "@aztemi/svelte-on-solana-wallet-adapter-core";
  import { ConnectionProvider, WalletProvider } from "@aztemi/svelte-on-solana-wallet-adapter-ui";
  import { startPolling } from "../utils/poll_for_transaction";
  import { postRequest } from "../utils/post_request";
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
      BackpackWalletAdapter,
      SlopeWalletAdapter,
      SolletExtensionWalletAdapter
    } = await import("@solana/wallet-adapter-wallets");

    wallets = [
      new PhantomWalletAdapter(),
      new SolflareWalletAdapter(),
      new BackpackWalletAdapter(),
      new SlopeWalletAdapter(),
      new SolletExtensionWalletAdapter()
    ];
  });

  async function payWithConnectedWallet() {
    if ($walletStore?.connected) {
      try {
        loading = true;
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
      } catch (error) {
        console.error(error);
      }
      loading = false;
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

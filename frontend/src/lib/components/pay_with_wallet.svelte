<script>
  import { onMount } from "svelte";
  import { Buffer } from "buffer";
  import { Connection, Transaction } from "@solana/web3.js";
  import { walletStore } from "@svelte-on-solana/wallet-adapter-core";
  import { ConnectionProvider, WalletProvider } from "@svelte-on-solana/wallet-adapter-ui";
  import { startPolling } from "../utils/poll_for_transaction";
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
        const { transaction } = await fetch(link, {
          method: "POST",
          headers: {
            Accept: "application/json",
            "Content-Type": "application/json"
          },
          body: JSON.stringify({ account: $walletStore.publicKey.toBase58() })
        })
          .then(res => {
            if (!res.ok) {
              throw new Error(`Fetch error! Status: ${res.status}`);
            }

            return res;
          })
          .then(res => res.json());

        // extract payment transaction created in backend
        const txBuf = Buffer.from(transaction, "base64");
        let tx = Transaction.from(txBuf);

        // send the transaction
        const connection = new Connection(endpoint, "confirmed");
        await $walletStore.sendTransaction(tx, connection);

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

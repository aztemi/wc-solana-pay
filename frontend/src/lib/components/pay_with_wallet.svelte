<script>
  import { onMount } from "svelte";
  import { Buffer } from "buffer";
  import { Connection, Transaction } from "@solana/web3.js";
  import { walletStore } from "@svelte-on-solana/wallet-adapter-core";
  import { ConnectionProvider, WalletProvider, WalletMultiButton } from "@svelte-on-solana/wallet-adapter-ui";

  export let link;
  export let endpoint;

  const localStorageKey = "SolanaWalletAdapter";
  let wallets = [];

  $: {
    if ($walletStore?.connected && !$walletStore?.connecting && !$walletStore?.disconnecting) {
      payWithConnectedWallet($walletStore.publicKey);
    }
  }

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

  /**
   * @param {import("@solana/web3.js").PublicKey} payer
   */
  async function payWithConnectedWallet(payer) {
    if ($walletStore?.connected) {
      try {
        // fetch the transaction
        const { transaction } = await fetch(link, {
          method: "POST",
          headers: {
            Accept: "application/json",
            "Content-Type": "application/json"
          },
          body: JSON.stringify({ account: payer.toBase58() })
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
      } catch (error) {
        console.error(error);
      }
    }
  }
</script>

<ConnectionProvider network={endpoint} />
<WalletProvider {localStorageKey} {wallets} />

<span>Pay with Browser Wallet</span>
<div>
  {#key link}
    <WalletMultiButton />
  {/key}
</div>

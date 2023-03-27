<script>
  import { onMount } from "svelte";
  import { ConnectionProvider, WalletProvider, WalletMultiButton } from "@svelte-on-solana/wallet-adapter-ui";

  export let network;

  const localStorageKey = "SolanaWalletAdapter";
  let wallets = [];

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
</script>

<ConnectionProvider {network} />
<WalletProvider {localStorageKey} {wallets} />
<WalletMultiButton />

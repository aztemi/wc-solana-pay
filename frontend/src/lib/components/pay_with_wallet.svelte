<script>
  import { onMount } from "svelte";
  import { Buffer } from "buffer";
  import { Transaction, clusterApiUrl } from "@solana/web3.js";
  import { WalletReadyState } from "@solana/wallet-adapter-base";
  import { walletStore } from "@aztemi/svelte-on-solana-wallet-adapter-core";
  import { ConnectionProvider, WalletProvider, workSpace } from "@aztemi/svelte-on-solana-wallet-adapter-ui";
  import { startPolling } from "../utils/poll_for_transaction";
  import { postRequest } from "../utils/post_request";
  import { isCheckoutCartValid } from "../utils/backend_proxy";
  import { notification, showSubmitOrderStatus, EXIT, STATE } from "./notification";
  import WalletSplitMultiButton from "./buttons/wallet_split_multi_button.svelte";

  export let link;
  export let network;

  let wallets = [];
  let loading = false;
  const localStorageKey = "SolanaWalletAdapter";
  const autoConnect = adapter => adapter && adapter.readyState === WalletReadyState.Installed;

  onMount(async () => {
    const {
      PhantomWalletAdapter,
      SolflareWalletAdapter,
      CoinbaseWalletAdapter,
      LedgerWalletAdapter,
      TrustWalletAdapter
    } = await import("@solana/wallet-adapter-wallets");

    wallets = [
      new PhantomWalletAdapter(),
      new SolflareWalletAdapter({ network }),
      new CoinbaseWalletAdapter(),
      new LedgerWalletAdapter(),
      new TrustWalletAdapter()
    ];
  });

  async function payWithConnectedWallet() {
    let msgId = 0;
    if ($walletStore?.connected) {
      try {
        loading = true;
        msgId = notification.addNotice("Processing payment transaction", STATE.LOADING);

        // fetch the transaction
        const [{ transaction: serializedTx }] = await Promise.all([
          postRequest(link, { account: $walletStore.publicKey.toBase58() }),
          isCheckoutCartValid()
        ]);

        // extract payment transaction created in backend
        const txBuf = Buffer.from(serializedTx, "base64");
        let tx = Transaction.from(txBuf);

        // sign and send the transaction
        await $walletStore.sendTransaction(tx, $workSpace.connection);

        // poll for transaction result
        startPolling();
        showSubmitOrderStatus();

        notification.updateNotice(msgId, { status: STATE.OK, exit: EXIT.TIMEOUT });
      } catch (error) {
        notification.updateNotice(msgId, { status: STATE.ERROR, error: error.message, exit: EXIT.TIMEOUT });
        console.error(error.toString());
      } finally {
        loading = false;
      }
    }
  }
</script>

<ConnectionProvider endpoint={clusterApiUrl(network)} config="confirmed" />
<WalletProvider {localStorageKey} {wallets} {autoConnect} />

<span>Pay with a browser wallet</span>
<div>
  {#key link}
    <WalletSplitMultiButton {loading} on:payclick={payWithConnectedWallet} />
  {/key}
</div>

<style lang="stylus">
  span
    font-size medium
    font-weight bold

  div
    padding-top 0.5rem
    text-align center

</style>

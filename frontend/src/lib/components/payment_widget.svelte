<script>
  import { onDestroy } from "svelte";
  import { order } from "../store/order.js";
  import { stopPolling } from "../utils/poll_for_transaction.js";
  import FiatPrice from "./fiat_price.svelte";
  import CryptoPrice from "./crypto_price.svelte";
  import PayWithWallet from "./pay_with_wallet.svelte";
  import OrDivider from "./or_divider.svelte";
  import PayWithQrcode from "./pay_with_qrcode.svelte";

  let specLink;
  const { amount, currency, rpc, link } = $order;

  $: {
    if ($order.activeToken) {
      // prepare `link` parameter in the Solana Pay Transaction Request spec
      const key = $order.activeToken;
      specLink = `${link}&token=${key}`;
    }
  }

  onDestroy(() => {
    stopPolling();
  });
</script>

<section>
  <FiatPrice amount={amount.toNumber()} {currency} />

  <CryptoPrice />

  <div class="paywith">
    <PayWithWallet link={specLink} endpoint={rpc} />
    <OrDivider />
    <PayWithQrcode link={specLink} />
  </div>
</section>

<style lang="stylus">
  section
    padding 1rem 2rem

  .paywith
    padding-top 1rem
    display flex
    flex-direction column
    align-items center

</style>

<script>
  import { onMount } from "svelte";
  import { order } from "../store/order.js";
  import { isMobile } from "../utils/is_mobile.js";
  import { startPolling } from "../utils/poll_for_transaction.js";
  import Icon from "./icons/icon.svelte";
  import QrCode from "./qrcode.svelte";
  import IconButton from "./buttons/icon_button.svelte";

  export let link;

  let qrCodeVisible = true;
  const { label, message } = $order;

  /**
   * @param {boolean} show
   */
  function showQrCode(show) {
    qrCodeVisible = show;
    if (qrCodeVisible) startPolling();
  }

  onMount(() => {
    // first hide QR code on mobile
    showQrCode(!isMobile());
  });
</script>

{#if qrCodeVisible}
  <span>Scan QR Code with Mobile Wallet</span>
  <div class="qrcode">
    {#key link}
      <QrCode {link} {label} {message} />
    {/key}
  </div>
{:else}
  <IconButton on:click={() => showQrCode(true)} class="show_qr_button">
    <Icon slot="start-icon" name="qr_scan" title="Show QR Code" />
    Show QR Code
  </IconButton>
{/if}

<style lang="stylus">
  :global
    .show_qr_button
      border 1px solid var(--overlay_back_color)
      border-radius 0.3rem
      background-color transparent
      margin-bottom 1rem

</style>

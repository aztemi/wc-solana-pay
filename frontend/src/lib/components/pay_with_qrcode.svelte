<script>
  import { order } from "../store/order.js";
  import { startPolling } from "../utils/poll_for_transaction.js";
  import Icon from "./icons/icon.svelte";
  import QrCode from "./qrcode.svelte";
  import IconButton from "./buttons/icon_button.svelte";

  export let link;

  let qrCodeVisible = false;
  const { label, message } = $order;

  /**
   * @param {boolean} show
   */
  function showQrCode(show) {
    qrCodeVisible = show;
    if (qrCodeVisible) startPolling();
  }
</script>

{#if qrCodeVisible}
  <span>Scan QR code with a mobile wallet</span>
  {#key link}
    <QrCode {link} {label} {message} />
  {/key}
{:else}
  <span>Pay by scanning a QR code</span>
  <IconButton on:click={() => showQrCode(true)} class="show_qr_button">
    <Icon slot="start-icon" name="qr_scan" title="Show QR Code" />
    Show QR Code
  </IconButton>
{/if}

<style lang="stylus">
  span
    font-size medium
    font-weight bold

  :global
    .show_qr_button
      border 1px solid var(--overlay_back_color)
      border-radius 0.3rem
      background-color transparent
      padding 0.5rem 1.5rem
      margin-top 0.5rem
      margin-bottom 1.5rem

</style>

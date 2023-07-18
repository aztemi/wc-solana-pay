<script>
  import { onMount } from "svelte";
  import { order } from "../store/order.js";
  import { isMobile } from "../utils/is_mobile.js";
  import Icon from "./icons/icon.svelte";
  import QrCode from "./qrcode.svelte";
  import IconButton from "./buttons/icon_button.svelte";

  export let link;

  let hideQrcode = false;
  const { label, message } = $order;

  onMount(() => {
    hideQrcode = isMobile();
  });
</script>

{#if hideQrcode}
  <IconButton on:click={() => (hideQrcode = false)} class="show_qr_button">
    <Icon slot="start-icon" name="qr_scan" title="Show QR Code" />
    Show QR Code
  </IconButton>
{:else}
  <span>Scan QR Code with Mobile Wallet</span>
  <div class="qrcode">
    {#key link}
      <QrCode {link} {label} {message} />
    {/key}
  </div>
{/if}

<style lang="stylus">
  :global
    .show_qr_button
      border 1px solid var(--overlay_back_color)
      border-radius 0.3rem
      background-color transparent

</style>

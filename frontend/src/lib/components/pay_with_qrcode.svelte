<script>
  import { order } from "../store/order.js";
  import QrCode from "./qrcode.svelte";

  export let link;

  const { label, message } = $order;
</script>

<span>Scan QR Code with Mobile Wallet</span>
<div class="qrcode">
  {#key link}
    <QrCode {link} {label} {message} />
  {/key}
</div>

<script>
  import { onMount } from "svelte";
  import { createQR, encodeURL } from "@solana/pay";

  export let link;
  export let label;
  export let message;
  export let size = 224;

  let canvas;

  onMount(() => {
    if (!link) return;

    const url = encodeURL({ link, label, message });
    const qrcode = createQR(url, size, "transparent");
    qrcode.append(canvas);
  });
</script>

<div bind:this={canvas} />

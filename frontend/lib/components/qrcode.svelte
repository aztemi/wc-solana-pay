<script>
  import { onMount } from "svelte";
  import { createQR, encodeURL } from "@solana/pay";

  export let recipient;
  export let amount;
  export let splToken;
  export let reference;
  export let label;
  export let message;
  export let memo;
  export let size = 256;

  let canvas;

  onMount(() => {
    if (!recipient) return;

    const url = encodeURL({ recipient, amount, splToken, reference, label, message, memo });
    const qrcode = createQR(url, size, "transparent");
    qrcode.append(canvas);
  });
</script>

<div bind:this={canvas} />

<script>
  import { Buffer } from "buffer";
  import { onMount } from "svelte";

  let SolanaPay = null;

  onMount(async () => {
    window.Buffer = Buffer;
    SolanaPay = (await import("./solana_pay.svelte")).default;
  });
</script>

{#if SolanaPay}
  <svelte:component this={SolanaPay} />
{/if}

<script>
  import { Buffer } from "buffer";
  import { onMount } from "svelte";

  let SolanaPayModal = null;

  onMount(async () => {
    window.Buffer = Buffer;
    SolanaPayModal = (await import("./solana_pay_modal.svelte")).default;
  });
</script>

{#if SolanaPayModal}
  <svelte:component this={SolanaPayModal} />
{/if}

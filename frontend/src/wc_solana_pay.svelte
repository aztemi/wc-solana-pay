<script>
  import { Buffer } from "buffer";
  import { onMount } from "svelte";
  import IconsList from "./lib/components/icons/icons_list.svelte";
  import "./lib/styles/index.styl";

  let SolanaPayModal = null;

  onMount(async () => {
    if (typeof window !== "undefined" && window.Buffer === undefined) {
      window.Buffer = Buffer;
    }
    SolanaPayModal = (await import("./lib/components/modal.svelte")).default;
  });
</script>

<IconsList />
{#if SolanaPayModal}
  <svelte:component this={SolanaPayModal} />
{/if}

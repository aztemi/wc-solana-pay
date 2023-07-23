<script>
  import { onMount, onDestroy } from "svelte";
  import { blockElement } from "../utils/backend_proxy";

  let unblock = null;

  onMount(() => {
    unblock = blockElement("#pwspfwc_loading", "var(--modal_back_color)");
  });

  onDestroy(() => {
    if (unblock) unblock();
    unblock = null;
  });
</script>

<p id="pwspfwc_loading">loading...</p>

<style lang="stylus">
  p
    text-align center
    padding 2rem 5rem

</style>

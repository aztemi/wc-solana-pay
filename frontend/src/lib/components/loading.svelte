<script>
  import { onMount, onDestroy } from "svelte";

  let loadingP = null;

  onMount(() => {
    loadingP = jQuery("#spfwc_loading");
    loadingP.block({
      message: null,
      overlayCSS: {
        background: "var(--modal_back_color)",
        opacity: 0.6
      }
    });
  });

  onDestroy(() => {
    if (loadingP) loadingP.unblock();
  });
</script>

<p id="spfwc_loading">loading...</p>

<style lang="stylus">
  p
    text-align center
    padding 2rem 5rem

</style>

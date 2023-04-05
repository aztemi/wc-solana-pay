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

<style lang="stylus">

  :global
    .solana_pay_for_wc_place_order
      display flex
      align-items center
      justify-content center
      padding-left 0.2em
      padding-right 0.2em
      span
        padding 0
        margin 0
      img
        display inline-block
        padding 0
        border 0
        max-height 1.2em
        margin-left 0.5em

    .solana_pay_for_wc_overlay
      position fixed
      z-index 1000
      left 0
      top 0
      width 100%
      height 100vh
      display flex
      align-items center
      justify-content center
      overflow hidden
      .modal
        position relative
        max-width 90vw
        max-height 90%
        padding 1rem 2rem
        background-color white
        border 1px solid black
        border-radius 0.5rem
        box-shadow 0 4px 23px 0 rgb(0 0 0 / 20%)
        overflow-y auto
        .closeBtn
          position absolute
          top 1rem
          right 1rem

</style>

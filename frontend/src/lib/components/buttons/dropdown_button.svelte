<script lang="ts">
  import Icon from "../icons/icon.svelte";

  export let open: boolean = false;
  export let disabled: boolean = false;
</script>

<button class={$$props.class} {disabled} on:click={() => (open = true)}>
  {#if $$slots["start-icon"]}
    <i>
      <slot name="start-icon" />
    </i>
  {/if}
  <slot />
  <span>
    {#if open}
      <Icon name="expand_less" title="Close" />
    {:else}
      <Icon name="expand_more" title="Open" />
    {/if}
  </span>
</button>

<style lang="stylus">
  button
    display flex
    align-items center
    justify-content space-between
    cursor pointer

    i, span
      display flex
      align-items center
      justify-content center

    i
      height 1.5rem
      width 1.5rem

    span
      height 2rem
      width 2rem

</style>

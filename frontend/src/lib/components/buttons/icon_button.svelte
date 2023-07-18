<script lang="ts">
  export let disabled: boolean = false;
</script>

<button class={$$props.class} {disabled} on:click>
  {#if $$slots["start-icon"]}
    <i>
      <slot name="start-icon" />
    </i>
  {/if}
  <slot />
</button>

<style lang="stylus">
  button
    display flex
    align-items center
    justify-content space-between
    cursor pointer

    i
      display flex
      align-items center
      justify-content center
      height 1.5rem
      width 1.5rem
      margin-right 0.7rem

</style>

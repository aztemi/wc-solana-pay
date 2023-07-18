<script lang="ts">
  export let disabled: boolean = false;
</script>

<button class={$$props.class} {disabled} on:click>
  {#if $$slots["start-icon"]}
    <i class="start-icon">
      <slot name="start-icon" />
    </i>
  {/if}
  <slot />
  {#if $$slots["end-icon"]}
    <i class="end-icon">
      <slot name="end-icon" />
    </i>
  {/if}
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

    .start-icon
      margin-right 0.7rem

    .end-icon
      margin-left 0.7rem

</style>

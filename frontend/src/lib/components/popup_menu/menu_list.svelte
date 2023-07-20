<script lang="ts">
  import { clickOutside } from "../../actions/click_outside.js";

  export let open: boolean = false;

  const closeMenu = () => (open = false);

  const handleKeyup = (e: { key: string }) => {
    if (e.key === "Escape" && open) closeMenu();
  };
</script>

<svelte:window on:keyup={handleKeyup} />
{#if open}
  <ul class={$$props.class} aria-label="dropdown-list" role="menu" use:clickOutside={closeMenu}>
    <slot />
  </ul>
{/if}

<style lang="stylus">
  ul
    list-style none
    z-index var(--layer_dropdown_list)
    position absolute
    top 100%
    right 0
    margin 0
    padding 0
    display grid
    grid-template-rows 1fr
    grid-row-gap 0.5rem
    border-radius 0.5rem
    transform translateY(0.2rem);

</style>

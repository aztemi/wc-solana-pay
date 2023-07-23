<script lang="ts">
  export let name: string;
  export let title: string;
</script>

<svg class={$$props.class} fill="currentColor">
  <title>{title || name}</title>
  <use xlink:href="#icon_{name}" href="#icon_{name}" />
</svg>

<style lang="stylus">
  svg
    width 100%
    height 100%
    overflow hidden

</style>

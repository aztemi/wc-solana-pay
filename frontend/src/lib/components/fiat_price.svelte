<script>
  import { decodeEntities } from "../utils/helpers";
  import Icon from "./icons/icon.svelte";

  export let amount;
  export let symbol;
</script>

<div>
  <span class="icon_span">
    <Icon name="cart" title="Order Total" />
  </span>
  <span>
    <bdi>
      <span><b>{amount}</b></span>
      <span class="woocommerce-Price-currencySymbol"><b>{decodeEntities(symbol)}</b></span>
    </bdi>
  </span>
</div>

<style lang="stylus">
  div
    display flex
    align-items center
    justify-content right
    padding-right 0.5rem
    .icon_span
      height 1.5rem
      width 1.5rem
      margin-right 0.2rem

</style>

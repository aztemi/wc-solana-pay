<script>
  import { createEventDispatcher } from "svelte";
  import { baseurl } from "../utils/backend_proxy";
  import Icon from "./icons/icon.svelte";

  const dispatch = createEventDispatcher();
</script>

<div>
  <img src={`${baseurl}/assets/img/solana_pay_black.svg`} alt="Solana Pay" />
  <button class="pwspfwc_icon_button" on:click={() => dispatch("close")}>
    <Icon name="close" title="Close" />
  </button>
</div>

<style lang="stylus">
  div
    padding 0.7rem 1rem 0 1rem
    display flex
    align-items center
    justify-content space-between

</style>

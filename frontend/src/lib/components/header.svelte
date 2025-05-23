<script>
  import { createEventDispatcher } from "svelte";
  import { pluginUrl } from "../utils/backend_proxy";
  import Icon from "./icons/icon.svelte";
  import CountdownTimer from "./countdown_timer.svelte";

  const dispatch = createEventDispatcher();
</script>

<div>
  <img src={`${pluginUrl}/assets/img/solana_pay_black.svg`} alt="Solana Pay" />
  <span>
    <CountdownTimer />
    <button class="pwspfwc_icon_button" on:click={() => dispatch("close")}>
      <Icon name="close" title="Close" />
    </button>
  </span>
</div>

<style lang="stylus">
  div
    padding 0.7rem 1rem 0 1rem
    display flex
    align-items center
    justify-content space-between

    span
      display flex
      align-items center

</style>

<script>
  import { slide } from "svelte/transition";
  import { notification, STATE, EXIT } from "./store";
  import Icon from "../icons/icon.svelte";
  import Spinner from "../icons/spinner.svelte";
</script>

{#if $notification.notices.length}
  <ui>
    {#each $notification.notices as { id, message, status, error, exit }}
      <li transition:slide>
        <div>
          <span class="msgspan">
            <p>{message}</p>
            {#if status === STATE.LOADING}
              <span class="icon"><Spinner /></span>
            {/if}
            {#if status === STATE.ERROR}
              <span class="icon error"><Icon name="warn" title="Failed" /></span>
            {/if}
            {#if status === STATE.OK}
              <span class="icon success"><Icon name="check" title="" /></span>
            {/if}
          </span>
          {#if exit === EXIT.MANUAL}
            <button class="pwspfwc_icon_button icon" on:click={() => notification.removeNotice(id)}>
              <Icon name="close" title="Close" />
            </button>
          {/if}
        </div>
        {#if error}
          <p class="error">{error}</p>
        {/if}
      </li>
    {/each}
  </ui>
{/if}

<style lang="stylus">
  li
    list-style-type none
    margin 0
    padding 0.5rem 1rem
    background-color var(--popup_li_back_color)
  li + li
    margin-top 0.5rem
  div
    display flex
    align-items center
    justify-content space-between
  p
    margin 0
    padding 0
    font-size medium
    &.error
      font-size 0.8rem
      overflow-x hidden
      overflow-wrap break-word
  .msgspan
    display flex
    align-items center
    p
      margin-right 0.7rem
  .icon
    display inline-block
    width 1.5rem
    height 1.5rem
  .pwspfwc_icon_button
    width 1.2rem
    height 1.2rem
  .success
    color var(--status_success_color)
  .error
    color var(--status_error_color)

</style>

<script>
  import { order } from "../store/order.js";
  import { baseurl, addTokenToCheckoutForm } from "../utils/backend_proxy.js";
  import IconButton from "./buttons/icon_button.svelte";
  import DropdownButton from "./buttons/dropdown_button.svelte";
  import MenuList from "./popup_menu/menu_list.svelte";
  import MenuItem from "./popup_menu/menu_item.svelte";

  let dropdownVisible = false;
  let name, amount, icon, symbol;

  const { tokens } = $order;
  const dropdownRequired = Object.keys(tokens).length > 1;

  $: {
    if ($order.activeToken) {
      const key = $order.activeToken;
      ({ name, amount, icon, symbol } = tokens[key]);

      // add selected payment token to an hidden field of the checkout form
      addTokenToCheckoutForm(key);
    }
  }

  const closeDropdown = () => (dropdownVisible = false);

  const setActiveToken = key => {
    order.setActiveToken(key);
    closeDropdown();
  };
</script>

<div class="topay">
  <span class="token_amount"><b>{amount}</b></span>
  <span class="tokens">
    {#if dropdownRequired}
      <DropdownButton bind:open={dropdownVisible}>
        <img slot="start-icon" src={`${baseurl}/${icon}`} alt={name} />
        <span class="token_symbol">{symbol}</span>
      </DropdownButton>
    {:else}
      <IconButton class="pwspfwc_nopointer">
        <img slot="start-icon" src={`${baseurl}/${icon}`} alt={name} />
        <span class="token_symbol">{symbol}</span>
      </IconButton>
    {/if}
    <MenuList class="pwspfwc_popup_shadow" bind:open={dropdownVisible}>
      {#each Object.entries(tokens) as [key, token]}
        <MenuItem class={key === $order.activeToken ? "selected" : ""}>
          <IconButton on:click={() => setActiveToken(key)}>
            <img slot="start-icon" src={`${baseurl}/${token.icon}`} alt={token.name} />
            <span class="token_symbol">{token.symbol}</span>
          </IconButton>
        </MenuItem>
      {/each}
    </MenuList>
  </span>
</div>

<style lang="stylus">
  .topay
    display flex
    align-items center
    justify-content center
    border 1px solid var(--overlay_back_color)
    border-radius 0.3rem
    margin-top 1rem
    position relative

    .token_amount
      font-size 2rem
      padding-left 1rem

    .tokens
      display inline-block

      :global
        button
          line-height 1
          border 0
          padding 0.5rem 1rem
          width 100%
          outline none
          background-color transparent
          color currentcolor
          img
            width 1.5rem
            border-radius 50%
          .token_symbol
            font-size 1.5rem
            padding-left 0.4rem
            white-space nowrap
          i
            margin-right 0

        ul
          background-color var(--modal_back_color)
          transform translateX(-1rem) translateY(-0.2rem)

        li
          &:hover, &.selected
            background-color var(--popup_li_back_color)
          &:first-of-type
            border-radius 0.5rem 0.5rem 0 0
          &:last-of-type
            border-radius 0 0 0.5rem 0.5rem
          button
            justify-content unset
            padding 0.5rem 2rem

</style>

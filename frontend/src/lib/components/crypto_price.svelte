<script>
  import { order } from "../store/order.js";
  import { clickOutside } from "../actions/click_outside.js";

  let dropdownVisible = false;
  let name, amount, icon, symbol;

  const { baseurl, pay_page } = solana_pay_for_wc;
  const { tokens } = $order;
  const dropdownRequired = Object.keys(tokens).length > 1;

  $: {
    if ($order.activeToken) {
      const key = $order.activeToken;
      ({ name, amount, icon, symbol } = tokens[key]);

      // add selected payment token to an hidden field of the checkout form
      const form = jQuery(pay_page ? "form#order_review" : "form.checkout");
      const input = form.find("input[name='pwspfwc_payment_token']");
      if (input.length) {
        input.val(key);
      } else {
        form.append(`<input type="hidden" name="pwspfwc_payment_token" value="${key}" />`);
      }
    }
  }
</script>

<div class="topay">
  <span class="token_amount"><b>{amount}</b></span>
  <span class="tokens">
    <button
      class:nopointer={!dropdownRequired}
      on:click|preventDefault|stopPropagation={() => {
        if (dropdownRequired) dropdownVisible = !dropdownVisible;
      }}
    >
      <img src={`${baseurl}/${icon}`} alt={name} />
      <span class="token_symbol">{symbol}</span>
      {#if dropdownRequired}
        {#if dropdownVisible}
          <span class="dashicons dashicons-arrow-up-alt2" />
        {:else}
          <span class="dashicons dashicons-arrow-down-alt2" />
        {/if}
      {/if}
    </button>
    {#if dropdownVisible}
      <div class="dropdown">
        <ul
          class="popup_shadow"
          use:clickOutside={() => {
            dropdownVisible = false;
          }}
        >
          {#each Object.entries(tokens) as [key, token]}
            <li class:selected={key === $order.activeToken}>
              <button
                on:click|preventDefault|stopPropagation={() => {
                  order.setActiveToken(key);
                  dropdownVisible = false;
                }}
              >
                <img src={`${baseurl}/${token.icon}`} alt={token.name} />
                <span class="token_symbol">{token.symbol}</span>
              </button>
            </li>
          {/each}
        </ul>
      </div>
    {/if}
  </span>
</div>

<style lang="stylus">
  .topay
    display flex
    align-items center
    justify-content center
    .token_amount
      font-size 2rem
      padding 0 0.5rem
    .nopointer
      cursor auto
    .tokens
      display inline-block
      button
        display flex
        align-items center
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
          padding 0 0.7rem
          white-space nowrap
      .dropdown
        position relative
        z-index var(--layer_dropdown_list)
        ul
          list-style-type none
          position absolute
          padding 0
          top 0.2rem
          right 0
          margin 0
          width 100%
          border-radius 0.5rem
          background-color var(--modal_back_color)
          li
            padding 0
            &:hover, &.selected
              background-color var(--popup_li_back_color)
            &:first-of-type
              border-radius 0.5rem 0.5rem 0 0
            &:last-of-type
              border-radius 0 0 0.5rem 0.5rem

</style>

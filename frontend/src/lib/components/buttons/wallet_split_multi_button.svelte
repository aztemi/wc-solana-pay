<script>
  import { createEventDispatcher } from "svelte";
  import { walletStore } from "@aztemi/svelte-on-solana-wallet-adapter-core";
  import { WalletConnectButton, WalletModal } from "@aztemi/svelte-on-solana-wallet-adapter-ui";
  import IconButton from "./icon_button.svelte";
  import DropdownButton from "./dropdown_button.svelte";
  import MenuList from "../popup_menu/menu_list.svelte";
  import MenuItem from "../popup_menu/menu_item.svelte";

  export let loading = false;
  export let maxNumberOfWallets = 3;

  let copied = false;
  let modalVisible = false;
  let dropdownVisible = false;

  const dispatch = createEventDispatcher();

  $: ({ publicKey, wallet, disconnect, connect, select, connecting } = $walletStore);
  $: base58 = publicKey && publicKey?.toBase58();
  $: content = showAddressContent($walletStore);

  const closeDropdown = () => (dropdownVisible = false);

  const openModal = () => {
    modalVisible = true;
    closeDropdown();
  };
  const closeModal = () => (modalVisible = false);

  function showAddressContent(store) {
    const base58 = store.publicKey?.toBase58();
    if (!store.wallet || !base58) return null;
    return base58.slice(0, 4) + ".." + base58.slice(-4);
  }

  async function copyAddress() {
    await navigator.clipboard.writeText(base58);
    copied = true;
    setTimeout(() => (copied = false), 400);
  }

  async function connectWallet(event) {
    closeModal();
    await select(event.detail);
    await connect();
  }

  async function disconnectWallet(event) {
    closeDropdown();
    await disconnect();
  }
</script>

{#if !wallet}
  <IconButton class="wallet-adapter-button wallet-adapter-button-trigger" on:click={openModal}>
    Select Wallet
  </IconButton>
{:else if connecting}
  <WalletConnectButton />
{:else}
  <div class="wallet-adapter-split-dropdown">
    {#if !base58}
      <WalletConnectButton />
    {:else}
      <IconButton
        on:click={() => dispatch("payclick")}
        class="paybtn wallet-adapter-button wallet-adapter-button-trigger {loading ? 'loading' : ''}"
        disabled={loading}
      >
        <img slot="start-icon" src={wallet.adapter.icon} alt={`${wallet.adapter.name} icon`} />
        Pay Now
      </IconButton>
    {/if}
    <DropdownButton class="arrowbtn wallet-adapter-button wallet-adapter-button-trigger" bind:open={dropdownVisible} />
    <MenuList class="wallet-adapter-dropdown-list wallet-adapter-dropdown-list-active" bind:open={dropdownVisible}>
      {#if base58}
        <MenuItem>
          <button class="wallet-adapter-dropdown-list-item" on:click={copyAddress}>
            {copied ? "Copied" : "Copy address"}
          </button>
        </MenuItem>
      {/if}
      <MenuItem>
        <button class="wallet-adapter-dropdown-list-item" on:click={openModal}>Connect a different wallet</button>
      </MenuItem>
      <MenuItem>
        <button class="wallet-adapter-dropdown-list-item" on:click={disconnectWallet}>Disconnect</button>
      </MenuItem>
    </MenuList>
  </div>
  {#if base58}
    <span>Connected: <strong>{content}</strong></span>
  {/if}
{/if}

{#if modalVisible}
  <WalletModal on:close={closeModal} on:connect={connectWallet} {maxNumberOfWallets} />
{/if}

<style lang="stylus">
  :global
    .wallet-adapter-split-dropdown
      position relative
      display flex

      img
        width 100%
        height 100%
        overflow hidden

      .paybtn
        border-top-right-radius 0
        border-bottom-right-radius 0
        margin-right 1px
        &:focus
          outline 0

      .arrowbtn
        border-top-left-radius 0
        border-bottom-left-radius 0
        width 2rem
        padding 0
        &:focus
          outline 0

      li button
        background-color transparent
        outline-color transparent
        &:focus
          outline-color transparent

</style>

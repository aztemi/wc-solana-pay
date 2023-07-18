<script>
  import { createEventDispatcher } from "svelte";
  import { walletStore } from "@svelte-on-solana/wallet-adapter-core";
  import { WalletConnectButton, WalletModal } from "@svelte-on-solana/wallet-adapter-ui";
  import { onClick } from "../../actions/on_click.js";
  import { clickOutside } from "../../actions/click_outside.js";
  import Icon from "../icons/icon.svelte";
  import IconButton from "./icon_button.svelte";

  export let loading = false;
  export let maxNumberOfWallets = 3;

  let copied = false;
  let modalVisible = false;
  let dropdownVisible = false;

  const dispatch = createEventDispatcher();

  $: ({ publicKey, wallet, disconnect, connect, select } = $walletStore);
  $: base58 = publicKey && publicKey?.toBase58();
  $: content = showAddressContent($walletStore);

  const openDropdown = () => (dropdownVisible = true);
  const closeDropdown = () => (dropdownVisible = false);

  const openModal = () => {
    modalVisible = true;
    closeDropdown();
  };
  const closeModal = () => (modalVisible = false);

  function handleKeyup(e) {
    if (e.key === "Escape" && dropdownVisible) {
      closeDropdown();
    }
  }

  function showAddressContent(store) {
    const base58 = store.publicKey?.toBase58();
    if (!store.wallet || !base58) return null;
    return base58.slice(0, 4) + ".." + base58.slice(-4);
  }

  async function copyAddress() {
    if (!base58) return;
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

<svelte:window on:keyup={handleKeyup} />

{#if !wallet}
  <IconButton class="wallet-adapter-button wallet-adapter-button-trigger" on:click={openModal}>
    Select Wallet
  </IconButton>
{:else if !base58}
  <WalletConnectButton />
{:else}
  <div class="wallet-adapter-split-dropdown">
    <IconButton
      on:click={() => dispatch("payclick")}
      class="wallet-adapter-button wallet-adapter-button-trigger {loading ? 'loading' : ''}"
    >
      <img slot="start-icon" src={wallet.icon} alt={`${wallet.name} icon`} />
      Pay Now
    </IconButton>
    <IconButton on:click={openDropdown} class="wallet-adapter-button wallet-adapter-button-trigger">
      {#if dropdownVisible}
        <Icon name="expand_less" title="Close" />
      {:else}
        <Icon name="expand_more" title="Open" />
      {/if}
    </IconButton>
    {#if dropdownVisible}
      <ul
        aria-label="dropdown-list"
        class="wallet-adapter-dropdown-list wallet-adapter-dropdown-list-active"
        role="menu"
        use:clickOutside={closeDropdown}
      >
        <li use:onClick={copyAddress} class="wallet-adapter-dropdown-list-item" role="menuitem">
          {copied ? "Copied" : "Copy address"}
        </li>
        <li use:onClick={openModal} class="wallet-adapter-dropdown-list-item" role="menuitem">
          Connect a different wallet
        </li>
        <li use:onClick={disconnectWallet} class="wallet-adapter-dropdown-list-item" role="menuitem">Disconnect</li>
      </ul>
    {/if}
  </div>
  <span>Connected: <strong>{content}</strong></span>
{/if}

{#if modalVisible}
  <WalletModal on:close={closeModal} on:connect={connectWallet} {maxNumberOfWallets} />
{/if}

<style lang="stylus">
  :global
    .wallet-adapter-split-dropdown
      position relative
      display flex

      button:nth-child(1)
        border-top-right-radius 0
        border-bottom-right-radius 0
        margin-right 1px
        &:focus
          outline 0

      button:nth-child(2)
        border-top-left-radius 0
        border-bottom-left-radius 0
        width 2rem
        padding 0
        &:focus
          outline 0

</style>

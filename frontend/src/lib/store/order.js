import Big from "big.js";
import { writable } from "svelte/store";
import { PublicKey } from "@solana/web3.js";
import { WalletAdapterNetwork } from "@solana/wallet-adapter-base";
import supportedTokens from "../../../../assets/json/supported_solana_tokens.json";

const emptyOrder = {
  updated: false,
  timedOut: false,
  paymentId: "",
  orderId: "",
  amount: new Big(0),
  currency: "",
  symbol: "",
  rpc: "", // RPC endpoint
  link: "", // `link` param in Solana Pay spec
  poll: "", // endpoint to poll transaction status
  network: WalletAdapterNetwork.Mainnet,
  tokens: {},
  activeToken: "",
  label: "",
  message: "",
  memo: "",
  paymentSignature: ""
};

function createOrderStore() {
  const { subscribe, update } = writable(emptyOrder);

  return {
    subscribe,

    reset: () => update(_ => Object.assign({}, emptyOrder)),

    timeout: () => update(old => Object.assign({}, old, { timedOut: true })),

    setPaymentSignature: paymentSignature => update(old => Object.assign({}, old, { paymentSignature })),

    setActiveToken: key => update(old => Object.assign({}, old, { activeToken: key })),

    setOrder: order =>
      update(old => {
        let { id, local_id, amount, tokens, suffix, rpc, home, link, poll, testmode } = order;

        let homeUrl = new URL(home);
        homeUrl.searchParams.set("id", id);
        if (local_id) homeUrl.searchParams.set("orderId", local_id);

        amount = new Big(amount);

        // Append 'devnet' to RPC endpoint in testmode.
        // A hack to make the 'getChainForEndpoint()', used in WalletProvider, detect the correct network
        const devnetLink = testmode ? "&n=devnet" : "";

        // update tokens
        let paymentTokens = {};
        let activeToken = "";

        for (const [key, value] of Object.entries(tokens)) {
          const token = key.replace(suffix, "");
          if (token in supportedTokens) {
            paymentTokens[key] = supportedTokens[token];
            paymentTokens[key]["amount"] = new Big(value.amount).round(value.dp, Big.roundUp);
            if (value.mint) paymentTokens[key]["mint"] = new PublicKey(value.mint);
            if (!activeToken) activeToken = key;
          }
        }

        return Object.assign({}, old, order, {
          updated: true,
          paymentId: id,
          orderId: local_id,
          amount,
          activeToken,
          rpc: `${homeUrl.toString()}&action=${rpc}${devnetLink}`,
          link: `${homeUrl.toString()}&action=${link}`,
          poll: `${homeUrl.toString()}&action=${poll}`,
          network: testmode ? WalletAdapterNetwork.Devnet : WalletAdapterNetwork.Mainnet,
          tokens: paymentTokens
        });
      })
  };
}

export const order = createOrderStore();

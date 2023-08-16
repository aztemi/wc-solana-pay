import BigNumber from "bignumber.js";
import { writable } from "svelte/store";
import { PublicKey } from "@solana/web3.js";
import TestmodeTokens from "../../../../assets/json/supported_solana_tokens_devnet.json";
import LiveTokens from "../../../../assets/json/supported_solana_tokens_mainnet_beta.json";

const DP = 4; // default decimal places

const emptyOrder = {
  updated: false,
  timedOut: false,
  reference: null,
  amount: new BigNumber(0),
  currency: "",
  rpc: "", // RPC endpoint
  link: "", // `link` param in Solana Pay spec
  poll: "", // endpoint to poll transaction status
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

    confirmPayment: paymentSignature => update(old => Object.assign({}, old, { paymentSignature })),

    setActiveToken: key => update(old => Object.assign({}, old, { activeToken: key })),

    setOrder: order =>
      update(old => {
        let { id, reference, amount, testmode, tokens, suffix, rpc, home, link, poll } = order;

        let homeUrl = new URL(home);
        homeUrl.searchParams.set("id", id);

        reference = new PublicKey(reference);
        amount = new BigNumber(amount);

        // update tokens
        let paymentTokens = {};
        let activeToken = "";
        const supportedTokens = testmode ? TestmodeTokens : LiveTokens;

        for (const [key, value] of Object.entries(tokens)) {
          const token = key.replace(suffix, "");
          if (token in supportedTokens) {
            paymentTokens[key] = supportedTokens[token];
            paymentTokens[key]["amount"] = new BigNumber(value.amount).decimalPlaces(DP, BigNumber.ROUND_CEIL);
            if (paymentTokens[key]["mint"]) paymentTokens[key]["mint"] = new PublicKey(paymentTokens[key]["mint"]);
            if (!activeToken) activeToken = key;
          }
        }

        return Object.assign({}, old, order, {
          updated: true,
          reference,
          amount,
          activeToken,
          rpc: `${homeUrl.toString()}&action=${rpc}`,
          link: `${homeUrl.toString()}&action=${link}`,
          poll: `${homeUrl.toString()}&action=${poll}`,
          tokens: paymentTokens
        });
      })
  };
}

export const order = createOrderStore();

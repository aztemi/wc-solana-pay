import BigNumber from "bignumber.js";
import { writable } from "svelte/store";
import { PublicKey } from "@solana/web3.js";
import TestmodeTokens from "../../../../assets/json/supported_solana_tokens_devnet.json";
import LiveTokens from "../../../../assets/json/supported_solana_tokens_mainnet_beta.json";

const DP = 4; // default decimal places

const emptyOrder = {
  updated: false,
  recipient: null,
  reference: null,
  amount: new BigNumber(0),
  currency: "",
  endpoint: "", // RPC endpoint
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

    confirmPayment: paymentSignature => update(old => Object.assign({}, old, { paymentSignature })),

    setActiveToken: key => update(old => Object.assign({}, old, { activeToken: key })),

    setOrder: order =>
      update(old => {
        let { id, recipient, reference, amount, testmode, tokens, suffix, endpoint, home, link, poll } = order;

        recipient = new PublicKey(recipient);
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
          recipient,
          reference,
          amount,
          activeToken,
          endpoint: `${endpoint}${id}/`,
          link: `${home}?wc-api=${link}&id=${id}`,
          poll: `${home}?wc-api=${poll}&id=${id}`,
          tokens: paymentTokens
        });
      })
  };
}

export const order = createOrderStore();

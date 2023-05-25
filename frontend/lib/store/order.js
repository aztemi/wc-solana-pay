import BigNumber from "bignumber.js";
import { writable } from "svelte/store";
import { PublicKey } from "@solana/web3.js";
import TestmodeTokens from "../../../assets/json/supported_solana_tokens_devnet.json";
import LiveTokens from "../../../assets/json/supported_solana_tokens_mainnet_beta.json";

const emptyOrder = {
  updated: false,
  recipient: null,
  reference: null,
  amount: new BigNumber(0),
  currency: "",
  endpoint: "",
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
        const DP = 4; // default decimal places
        let { recipient, reference, amount, testmode, tokens } = order;

        recipient = new PublicKey(recipient);
        reference = new PublicKey(reference);
        amount = new BigNumber(parseFloat(amount));

        // update tokens
        let paymentTokens = {};
        let activeToken = "";
        const keySuffix = "_SOLANA";
        const supportedTokens = testmode ? TestmodeTokens : LiveTokens;
        for (const [key, value] of Object.entries(tokens)) {
          const token = key.replace(keySuffix, "");
          if (token in supportedTokens) {
            paymentTokens[key] = supportedTokens[token];
            paymentTokens[key]["amount"] = new BigNumber(parseFloat(value)).decimalPlaces(DP, BigNumber.ROUND_CEIL);
            if (!activeToken) activeToken = key;
            if (paymentTokens[key]["mint"]) paymentTokens[key]["mint"] = new PublicKey(paymentTokens[key]["mint"]);
          }
        }

        return Object.assign({}, old, order, {
          updated: true,
          recipient,
          reference,
          amount,
          activeToken,
          tokens: paymentTokens
        });
      })
  };
}

export const order = createOrderStore();

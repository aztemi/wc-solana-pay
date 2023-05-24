import BigNumber from "bignumber.js";
import { writable } from "svelte/store";
import { PublicKey } from "@solana/web3.js";

const emptyOrder = {
  updated: false,
  solPrice: 0,
  amount: new BigNumber(0),
  solAmount: "",
  nonce: "",
  recipient: null,
  reference: null,
  label: "",
  message: "",
  memo: "",
  paymentSignature: ""
};

function createOrderStore() {
  const { subscribe, update } = writable(emptyOrder);

  return {
    subscribe,

    reset: () => update(old => Object.assign({}, emptyOrder, { solPrice: old.solPrice, updated: false })),

    setOrder: order =>
      update(old => {
        const { solPrice } = old;
        let { recipient, reference, amount, label, nonce } = order;

        recipient = new PublicKey(recipient);
        reference = new PublicKey(reference);
        amount = new BigNumber(parseFloat(amount).toFixed(2));

        let solAmount = 0;
        if (solPrice) solAmount = (amount.toNumber() / solPrice).toFixed(2);

        const message = `Thank You - (#${nonce}: ${label})`;
        const memo = `OrderNonce#${nonce}`;

        return Object.assign({}, old, {
          updated: true,
          recipient,
          reference,
          amount,
          solAmount,
          label,
          nonce,
          message,
          memo
        });
      }),

    setSolPrice: solPrice =>
      update(old => {
        const { amount } = old;
        let solAmount = 0;
        if (amount.toNumber()) solAmount = (amount.toNumber() / solPrice).toFixed(2);

        return Object.assign({}, old, { solPrice, solAmount });
      }),

    confirmPayment: paymentSignature => update(old => Object.assign({}, old, { paymentSignature }))
  };
}

export const order = createOrderStore();

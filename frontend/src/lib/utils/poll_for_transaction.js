// Poll status of payment transaction from the backend

import { order } from "../store/order.js";

const POLLING_DELAY = 10000; // Delay in ms between polling intervals

/**
 * @param {string} endpoint
 * @param {import("@solana/web3.js").PublicKey} reference
 */
export function pollForTransaction(endpoint, reference) {
  let pollingInterval = null;
  const url = `${endpoint}&ref=${reference.toBase58()}`;

  startPolling();

  function startPolling() {
    if (pollingInterval) return;
    pollingInterval = setInterval(confirmPaymentTxn, POLLING_DELAY);
  }

  function stopPolling() {
    if (pollingInterval) {
      clearInterval(pollingInterval);
      pollingInterval = null;
    }
  }

  // Confirm transaction on chain
  async function confirmPaymentTxn() {
    try {
      const json = await fetch(url).then(r => r.json());
      if (json?.signature) order.confirmPayment(json.signature);
    } catch (error) {
      console.error(error);
    }
  }

  return stopPolling;
}

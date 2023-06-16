// Find a specified transaction on chain through polling

import { Connection } from "@solana/web3.js";
import { findReference, FindReferenceError } from "@solana/pay";
import { order } from "../store/order.js";

const POLLING_DELAY = 2000; // Delay in ms between polling intervals for confirming transaction on chain

export function pollForTransaction(endpoint, reference) {
  let pollingInterval = null;

  const connection = new Connection(endpoint, "confirmed");
  startPolling();

  function startPolling() {
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
      const signatureInfo = await findReference(connection, reference);
      if (signatureInfo?.signature) order.confirmPayment(signatureInfo.signature);
    } catch (error) {
      if (!(error instanceof FindReferenceError)) console.error(error);
    }
  }

  return stopPolling;
}

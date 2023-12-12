// Poll status of payment transaction from the backend

import { get } from "svelte/store";
import { order } from "../store/order.js";
import { isLocalhost } from "./helpers.js";

const POLLING_DELAY = 10000; // Delay in ms between polling intervals

let endpoint = "";
let pollingInterval = null;

export function startPolling() {
  if (pollingInterval || isLocalhost()) return;

  const { reference, poll } = get(order);
  endpoint = `${poll}&ref=${reference.toBase58()}`;
  pollingInterval = setInterval(confirmPaymentTxn, POLLING_DELAY);
}

export function stopPolling() {
  if (pollingInterval) clearInterval(pollingInterval);
  pollingInterval = null;
}

// Confirm transaction on chain
async function confirmPaymentTxn() {
  try {
    const json = await fetch(endpoint).then(r => r.json());
    if (json?.signature) order.confirmPayment(json.signature);
  } catch (error) {
    console.error(error.toString());
  }
}

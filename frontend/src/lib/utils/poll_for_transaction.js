// Poll status of payment transaction from the backend

import { get } from "svelte/store";
import { connect } from "itty-sockets";
import { order } from "../store/order.js";
import { isLocalhost } from "./helpers.js";

const WS_RECONNECT_DELAY = 2000; // Delay in ms between websocket reconnect attempts
const WS_MAX_RECONNECT_ATTEMPTS = 5;
let socket = null;
let reconnectTimer = null;
let reconnectAttempts = 0;
let isStopped = false;

const POLLING_DELAY = 5000; // Delay in ms between polling intervals
let endpoint = "";
let pollingInterval = null;

export function startPolling() {
  // websocket
  isStopped = false;
  reconnectAttempts = 0;
  startWebsocket();

  // classic polling as a fallback, not available on localhost
  if (pollingInterval || isLocalhost()) return;

  const { poll } = get(order);
  endpoint = poll;
  pollingInterval = setInterval(confirmPaymentTxn, POLLING_DELAY);
}

export function startWebsocket() {
  if (socket || reconnectTimer || isStopped) return;

  const id = "wc-solana-pay";
  const { network, paymentId } = get(order);
  const channelId = `${id}:${network}:${paymentId}`;

  socket = connect(channelId);

  socket.on("open", () => {
    reconnectAttempts = 0;
  });

  socket.on("close", () => {
    socket = null;
    if (isStopped || reconnectAttempts >= WS_MAX_RECONNECT_ATTEMPTS) return;

    reconnectAttempts++;
    reconnectTimer = setTimeout(() => {
      reconnectTimer = null;
      startWebsocket();
    }, WS_RECONNECT_DELAY);
  });

  socket.on("new-txn", event => {
    if (event.txn?.signature) order.setPaymentSignature(event.txn.signature);
  });
}

export function stopPolling() {
  isStopped = true;

  if (reconnectTimer) clearTimeout(reconnectTimer);
  reconnectTimer = null;

  if (socket) socket.close();
  socket = null;

  if (pollingInterval) clearInterval(pollingInterval);
  pollingInterval = null;
}

// Confirm transaction on chain
async function confirmPaymentTxn() {
  try {
    const json = await fetch(endpoint).then(r => r.json());
    if (json?.signature) order.setPaymentSignature(json.signature);
  } catch (error) {
    console.error(error.toString());
  }
}

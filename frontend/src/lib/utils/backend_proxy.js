// Wrappers proxying PHP backend logics

import { decodeEntities } from "./helpers";

// jQuery
const $$ = jQuery;

// localized JS objects from PHP
export const { id, pluginUrl, apiUrl, baseUrl, payPage, orderId } = WC_SOLANA_PAY;

function getCheckoutForm() {
  return $$(payPage ? "form#order_review" : "form.checkout");
}

/**
 * Add specified payment token to an hidden field of the checkout form
 *
 * @param {string} key
 */
export function addTokenToCheckoutForm(key) {
  const form = getCheckoutForm();
  if (form) {
    const input = form.find("input[name='pwspfwc_payment_token']");
    if (input.length) {
      input.val(key);
    } else {
      form.append(`<input type="hidden" name="pwspfwc_payment_token" value="${key}" />`);
    }
  }
}

// Check if the checkout cart handling in the backend has errors or not
export async function isCheckoutCartValid() {
  if (!payPage) {
    // checkout page
    const baseurl = new URL(baseUrl);
    const url = `${baseurl.href}wc/store/v1/cart/`;
    const cart = await fetch(url).then(r => r.json());

    if (cart && cart.errors?.length) throw new Error(decodeEntities(cart.errors[0].message));
  }

  return true;
}

/**
 * Get order details from backend
 *
 * @param {string} orderId
 */
export async function getCheckoutOrderDetails(orderId) {
  return await apiRequest({ action: "detail", queryParams: { orderId } });
}

/**
 * Validate payment confirmation onchain via the backend
 *
 * @param {string} paymentId
 * @param {string} orderId
 */
export async function confirmPayment(paymentId, orderId) {
  return await apiRequest({ action: "confirm", queryParams: { id: paymentId, orderId } });
}

/**
 * Block and put a element in a loading state
 *
 * @param {string} el
 * @param {string} bgColor
 */
export function blockElement(el, bgColor) {
  let element = null;
  block();

  function block() {
    element = $$(el);
    element?.block({
      message: null,
      overlayCSS: {
        background: bgColor,
        opacity: 0.6
      }
    });
  }

  function unblock() {
    if (element) element.unblock();
    element = null;
  }

  return unblock;
}

/**
 * Send API request to backend webhooks
 */
export async function apiRequest({ action, queryParams = {}, postData = null }) {
  const url = new URL(apiUrl);
  url.searchParams.set("action", action);

  for (const [param, value] of Object.entries(queryParams)) {
    url.searchParams.set(param, value);
  }

  return await fetch(url.toString(), {
    method: postData ? "POST" : "GET",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json"
    },
    body: postData ? JSON.stringify(postData) : undefined
  }).then(async res => {
    const json = await res.json();
    if (!res.ok) throw new Error(json.data || json.error || "Unknown error");

    return json;
  });
}

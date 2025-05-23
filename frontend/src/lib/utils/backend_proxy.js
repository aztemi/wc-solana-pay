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

// submit checkout form
export function submitCheckoutForm() {
  const form = getCheckoutForm();
  form?.trigger("submit");
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
 * @param {string} ref
 */
export async function getCheckoutOrderDetails(ref) {
  let url = `?wc-api=${id}&ref=${ref}&`;

  if (pay_page) {
    // pay order page
    url += `order_id=${order_id}`;
  } else {
    // checkout page
    const cartCreated = sessionStorage.getItem("wc_cart_created");
    url += `cart_created=${cartCreated}`;
  }

  const jsonOrder = await fetch(url).then(async res => {
    const json = await res.json();
    if (!res.ok) throw new Error(json.data || json.error || "Unknown error");

    return json;
  });

  return jsonOrder;
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

// @ts-nocheck

if (typeof window !== "undefined" && !window.process) {
  window.process = { browser: true, env: { ENVIRONMENT: "BROWSER" } };
}

const { id: gatewayId, icon, apiUrl, priceUpdateFreq } = WC_SOLANA_PAY;

jQuery(function ($) {
  $(() => locationHashChanged());

  window.addEventListener("hashchange", locationHashChanged);

  function locationHashChanged() {
    const prefix = `#${gatewayId}-`;
    if (window.location.hash.startsWith(prefix) && window.aztOpenPayWidget) {
      const orderId = window.location.hash.split("@")[0].split("-").pop();
      const config = configFactory(orderId);

      aztOpenPayWidget(config);
    }
  }

  // Create configuration for the payment modal based on order Id
  function configFactory(orderId) {
    return {
      queryOrderInterval: priceUpdateFreq,
      queryOrderUrl: getApiRequestUrl({ action: "detail", queryParams: { orderId } }),
      solanaPayBaseUrl: getApiRequestUrl({ action: "txn", queryParams: { orderId } }),
      pollStatusBaseUrl: getApiRequestUrl({ action: "stat", queryParams: { orderId } }),
      confirmPaymentBaseUrl: getApiRequestUrl({ action: "confirm", queryParams: { orderId } }),
      icon,
      theme: "light",
      callback: handleCallback
    };
  }

  function getApiRequestUrl({ action, queryParams = {} }) {
    const url = new URL(apiUrl);
    url.searchParams.set("action", action);

    for (const [param, value] of Object.entries(queryParams)) {
      url.searchParams.set(param, value);
    }

    return url.href;
  }

  function handleCallback({ reason, data }) {
    if (reason === "ModalClosed") {
      // remove hash from the checkout page URL & reload
      const url = new URL(window.location.href);
      url.hash = "";
      window.location.replace(url);
    }

    if (reason === "PaymentCompleted") {
      const { redirect, errorMessage } = data;
      if (errorMessage) console.error(errorMessage);
      window.location.assign(redirect);
    }
  }
});

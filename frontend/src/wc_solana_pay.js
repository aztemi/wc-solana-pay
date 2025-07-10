// @ts-nocheck
import { Buffer } from "buffer";
import { id } from "./lib/utils/backend_proxy.js";
import WC_Solana_Pay from "./wc_solana_pay.svelte";

if (typeof window !== "undefined" && !window.Buffer) {
  window.Buffer = Buffer;
}

if (typeof window !== "undefined" && !window.process) {
  window.process = { browser: true, env: { ENVIRONMENT: "BROWSER" } };
}

jQuery(function ($) {
  const openModal = () => {
    dispatchEvent(new Event("openmodal"));
  };

  const locationHashChanged = () => {
    const prefix = `#${id}-`;
    if (window.location.hash.startsWith(prefix)) {
      // Hack! Calling `openModal` 3 times to mitigate race condition failures.
      openModal();
      setTimeout(() => openModal(), 100);
      setTimeout(() => openModal(), 200);
    }
  };

  $(() => {
    const target = document.getElementById("wc_solana_pay_svelte_target");
    if (target) new WC_Solana_Pay({ target });

    locationHashChanged();
  });

  window.addEventListener("hashchange", locationHashChanged);
});

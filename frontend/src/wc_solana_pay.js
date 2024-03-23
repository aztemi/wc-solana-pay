// @ts-nocheck
import WC_Solana_Pay from "./wc_solana_pay.svelte";

if (typeof window !== "undefined" && window.process === undefined) {
  window.process = { browser: true, env: { ENVIRONMENT: "BROWSER" } };
}

const target = document.getElementById("wc_solana_pay_svelte_target");
if (target) new WC_Solana_Pay({ target });

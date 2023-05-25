import SolanaPay from "./solana_pay.svelte";

const target = document.getElementById("solana_pay_for_wc_svelte_target");
if (target) new SolanaPay({ target });

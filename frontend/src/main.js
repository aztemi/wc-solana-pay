import App from "./app.svelte";

const target = document.getElementById("solana_pay_for_wc_svelte_target");
if (target) new App({ target });

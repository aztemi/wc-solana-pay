import { defineConfig } from "vite";
import { svelte } from "@sveltejs/vite-plugin-svelte";

let input = [];
switch (process.env.PACKAGE_NAME) {
  case "modal":
    input = ["src/wc_solana_pay.js"];
    break;
  case "button":
    input = ["src/public_place_order_button.js"];
    break;
  case "table":
    input = ["src/admin_tokens_table.js"];
    break;
  default:
    throw new Error("PACKAGE_NAME is not defined or is not valid");
}

export default defineConfig({
  plugins: [svelte()],
  base: "",
  build: {
    outDir: "../assets/script/",
    emptyOutDir: false,
    assetsDir: "",
    rollupOptions: {
      input,
      output: {
        format: "iife"
      }
    }
  }
});

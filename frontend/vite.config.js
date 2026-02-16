import { defineConfig } from "vite";
import { svelte } from "@sveltejs/vite-plugin-svelte";

let cssCodeSplit = false;
let input = [];
switch (process.env.PACKAGE_NAME) {
  case "modal":
    input = ["src/wc_solana_pay.js"];
    cssCodeSplit = true;
    break;
  case "table":
    input = ["src/admin_tokens_table.js"];
    break;
  case "icon":
    input = ["src/admin_plugin_icon.js"];
    break;
  case "copy":
    input = ["src/copy_to_clipboard.js"];
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
    cssCodeSplit,
    rollupOptions: {
      input,
      output: {
        format: "iife"
      }
    }
  },
  resolve: {
    alias: {
      crypto: "crypto-browserify"
    }
  }
});

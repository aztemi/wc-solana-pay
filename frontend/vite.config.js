import { defineConfig } from "vite";
import { svelte } from "@sveltejs/vite-plugin-svelte";

export default defineConfig({
  plugins: [svelte()],
  base: "",
  build: {
    outDir: "build",
    assetsDir: "",
    rollupOptions: {
      input: ["src/main.js", "src/place_order_button.js"]
    }
  }
});

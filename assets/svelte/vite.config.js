import { defineConfig } from "vite";
import { svelte } from "@sveltejs/vite-plugin-svelte";

export default defineConfig({
  plugins: [svelte()],
  base: "",
  build: {
    // minify: false,
    outDir: "../build",
    emptyOutDir: true,
    assetsDir: "",
    rollupOptions: {
      input: "src/main.js"
    }
  }
});

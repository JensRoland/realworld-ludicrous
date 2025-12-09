import { defineConfig } from 'vite'
import { resolve } from 'path'
import purgecss from 'vite-plugin-purgecss'
import thumbnails from './vite-plugin-thumbnails.js'

export default defineConfig({
  build: {
    outDir: 'app/public/dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        app: resolve(__dirname, 'resources/js/app.js'),
      },
      external: [/^\/fonts\//],
    },
  },
  plugins: [
    thumbnails(),
    purgecss({
      content: [
        './app/templates/**/*.php',
        './app/components/**/*.php',
        './app/pages/**/*.php',
      ],
      safelist: {
        // Keep dynamically-used classes
        standard: [
          /^btn-/,       // Button variants
          /^ion-/,       // Ionicons
          /^active$/,    // Active states
          /^disabled$/,  // Disabled states
        ],
      },
    }),
  ],
})

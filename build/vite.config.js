import { defineConfig } from 'vite'
import { resolve } from 'path'
import { FontaineTransform } from 'fontaine'
import purgecss from 'vite-plugin-purgecss'
import thumbnails from './vite-plugin-thumbnails.js'
import criticalCss from './vite-plugin-critical-css.js'

// Root directory is parent of build/
const root = resolve(__dirname, '..')

export default defineConfig({
  root,
  build: {
    outDir: 'app/public/dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        app: resolve(root, 'resources/js/app.js'),
      },
      external: [/^\/fonts\//],
    },
  },
  plugins: [
    FontaineTransform.vite({
      fallbacks: ['Arial', 'sans-serif'],
      resolvePath: (id) => new URL(`../app/public${id}`, import.meta.url),
    }),
    thumbnails(),
    purgecss({
      content: [
        resolve(root, 'app/templates/**/*.php'),
        resolve(root, 'app/components/**/*.php'),
        resolve(root, 'app/pages/**/*.php'),
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
    criticalCss(),
  ],
})

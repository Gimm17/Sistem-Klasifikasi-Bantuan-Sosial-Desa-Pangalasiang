import { defineConfig } from 'vite'
import { fileURLToPath, URL } from 'node:url'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue(), tailwindcss()],
  base: '/dist/',
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    port: 5173,
    strictPort: true,
  },
  build: {
    outDir: '../backend/public/dist',
    emptyOutDir: true,
    // Rolldown (Vite 8+) chunk strategy:
    //   vendor → vue + pinia + vue-router + axios + chart.js (cached across deploys)
    //   lucide  → semua icon @lucide/vue digabung jadi 1 chunk (mengurangi 19 req → 1)
    // ponytail: dashboard dan page lain tetap lazy-loaded per route via dynamic import.
    rolldownOptions: {
      output: {
        codeSplitting: {
          groups: [
            {
              name: 'vendor',
              test: /node_modules\/(vue|pinia|vue-router|axios|chart\.js|vue-chartjs)\//,
            },
            {
              name: 'lucide',
              test: /node_modules\/@lucide\//,
            },
          ],
        },
      },
    },
  },
})

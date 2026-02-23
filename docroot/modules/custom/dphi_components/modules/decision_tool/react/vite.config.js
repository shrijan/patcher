import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig(() => {
  return {
    build: {
      cssCodeSplit: true,
      manifest: true,
      rollupOptions: {
        input: {
          decisionTool: './src/entry.jsx',
          decisionToolFeed: './scss/feed.scss',
          decisionToolView: './scss/view.scss'
        }
      },
    },
    plugins: [react()]
  }
})

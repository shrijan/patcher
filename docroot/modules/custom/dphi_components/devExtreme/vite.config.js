import { defineConfig } from 'vite'
import { globSync } from 'glob'
import react from '@vitejs/plugin-react'
import { resolve } from 'path'

export default defineConfig(({ mode }) => {
  const env = mode === 'production' ? '"production"' : '"development"'

  const inputs = globSync('./src/**/entry.js*').reduce((acc, input) => {
    const library = input.match(/\/([^\/]+)\/entry\.jsx?$/)[1]
    acc[library] = resolve(__dirname, input)
    return acc
  }, {})

  return {
    build: {
      cssCodeSplit: true,
      manifest: true,
      rollupOptions: {
        input: inputs,
      },
    },
    define: { 'process.env.NODE_ENV': env },
    plugins: [react()],
  }
})

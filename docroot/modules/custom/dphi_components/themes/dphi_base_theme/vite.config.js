import { defineConfig } from 'vite'
import { resolve, dirname, relative } from 'path'
import { watchAndRun } from 'vite-plugin-watch-and-run'
import { globSync } from 'glob'
import react from '@vitejs/plugin-react'
import svgr from 'vite-plugin-svgr'

export default defineConfig(({ mode }) => {
  const env = mode === 'production' ? '"production"' : '"development"'

  const inputs = globSync('./src/**/entry.*').reduce((acc, input) => {
    const library = input.match(/\/([^\/]+)\/entry\.[cjsx]{2,4}$/)[1]
    acc[library] = resolve(__dirname, input)
    return acc
  }, {})

  return {
    build: {
      cssCodeSplit: true,
      manifest: true,
      rollupOptions: {
        input: inputs,
        external: ['Drupal', 'once'],
      },
    },
    css: { devSourcemap: true },
    define: { 'process.env.NODE_ENV': env },
    plugins: [
      react(),
      svgr(),
      watchAndRun([
        {
          name: 'twig-reload',
          watchKind: ['add', 'unlink'],
          watch: resolve('./templates/**/*.twig'),
          run: 'drush cr',
          delay: 300,
        },
      ]),
    ],
  }
})

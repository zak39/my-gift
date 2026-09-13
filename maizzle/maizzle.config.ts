import { defineConfig } from '@maizzle/framework'

export default defineConfig({
  content: ['emails/**/*.vue'],
  output: {
    path: '../templates/emails',
    extension: 'html.twig',
  },
})

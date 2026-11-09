// https://nuxt.com/docs/api/configuration/nuxt-config
import tailwindcss from "@tailwindcss/vite";

export default defineNuxtConfig({
  compatibilityDate: '2024-04-03',
  devtools: {
    enabled: true,
    wsUrl: process.env.BASE_URL?.replace('https://', ''),
    port: 3000,
    https: true
  },
  css: [
    '@/styles/globals.css'
  ],
  app: {
    head: {
      titleTemplate: '%s | ' + process.env.SITE_NAME,
      title: process.env.SITE_NAME
    }
  },
  devServer: {
    host: '0.0.0.0',
    port: 3000
  },
  runtimeConfig: {
    public: {
      GRAPHQL_TOKEN: process.env.GRAPHQL_TOKEN,
      CRAFT_URL: process.env.CRAFT_URL,
      BASE_URL: process.env.BASE_URL,
      SITE_NAME: process.env.SITE_NAME
    }
  },
  vite: {
    plugins: [
      tailwindcss()
    ],
    server: {
      https: true,
      hmr: {
        protocol: 'wss',
        host: process.env.BASE_URL?.replace('https://', ''),
        port: 3000
      },
      allowedHosts: [
        'starter-nuxt.ddev.site',
        '.ddev.site'
      ]
    }
  },
  nitro: {
    devServer: {
      watch: ['./server']
    }
  },
  experimental: {
    payloadExtraction: false
  }
});

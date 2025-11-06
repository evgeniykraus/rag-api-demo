import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'
import AutoImport from 'unplugin-auto-import/vite'
import Components from 'unplugin-vue-components/vite'

export default defineConfig({
  plugins: [
    vue(),
    AutoImport({
      imports: [
        'vue',
        'vue-router',
        'pinia',
        '@vueuse/core'
      ],
      dts: true,
      vueTemplate: true
    }),
    Components({
      dts: true
    })
  ],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src')
    }
  },
  server: {
    host: '0.0.0.0',
    port: 3000,
    proxy: {
      '/api': {
        // В Docker используем имя сервиса nginx, локально - localhost:8088
        target: process.env.DOCKER_ENV === 'true' ? 'http://nginx:80' : 'http://localhost:8088',
        changeOrigin: true,
        ws: true, // для WebSocket поддержки
        // Включаем передачу cookies для работы сессий
        cookieDomainRewrite: '',
        // Важно для SSE - отключаем буферизацию
        configure: (proxy, _options) => {
          proxy.on('error', (err, _req, _res) => {
            console.log('proxy error', err);
          });
        },
      }
    }
  },
  build: {
    outDir: 'dist',
    sourcemap: true
  }
})


import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { resolve } from 'node:path';
import tailwindcss from '@tailwindcss/vite';
import mkcert from 'vite-plugin-mkcert';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/js/app.ts'],
      ssr: 'resources/js/ssr.ts',
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    tailwindcss(),
    mkcert(), // Bật HTTPS cho camera
  ],
  optimizeDeps: {
    include: ['@zxing/library', 'vue-barcode-reader'], // Pre-bundle để tránh lỗi import
  },
  server: {
    host: '0.0.0.0', // Sử dụng IPv4
    port: 5173,
    https: true, // Bật HTTPS để truy cập camera trên di động
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/js'),
      'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
    },
  },
});
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import markdownThemes from './scripts/vite-markdown-themes.mjs';

export default defineConfig({
    plugins: [
        markdownThemes(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js', 'resources/js/install.js'],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

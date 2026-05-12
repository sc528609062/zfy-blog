import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin.css',
                'resources/js/app.js',
                'resources/js/admin.js',
                'resources/js/editor/index.js',
            ],
            refresh: [
                'app/Http/**',
                'app/Livewire/**',
                'app/Domain/**',
                'resources/views/**',
                'themes/**/views/**',
                'routes/**',
                'lang/**',
            ],
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            '@css': path.resolve(__dirname, 'resources/css'),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

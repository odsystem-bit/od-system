import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: [
                'resources/js/app.js',
                'resources/js/admin.js',
                'resources/js/vendor.js',
                'resources/js/influencer.js',
                'resources/css/app.css',
            ],
            refresh: true,
        }),
    ],
    server: {
        hmr: false,
    },
    build: {
        watch: false,
        rollupOptions: {
            external: (id) => id.startsWith('/images/'),
        },
    },
});
